<?php
require_once __DIR__ . '/../config/db_helper.php';

class AdminController
{
    private MySQLDB $db;

    public function __construct() { $this->db = new MySQLDB(); }

    /* ─────────────────────────────────────────────────────────────
       AUTH
    ───────────────────────────────────────────────────────────── */
    public function loginAdmin(array $post): array
    {
        try {
            $email    = addslashes(strtolower(trim($post['username'] ?? '')));
            $password = (string)($post['password'] ?? '');
            if ($email === '' || $password === '') return [];
            $rows = $this->db->select(
                "SELECT * FROM tbl_user WHERE communication_email_id='".$email."' AND user_type_id=1 LIMIT 1"
            );
            if (empty($rows)) return [];
            $u = $rows[0];
            $hash = (string)($u->ERP_PASSWORD ?? '');
            if ($hash !== '' && password_verify($password, $hash)) return $this->mapUser($u);
            return [];
        } catch (Exception $e) { error_log('AdminController::loginAdmin: '.$e->getMessage()); return []; }
    }

    private function mapUser(object $u): array {
        return [
            'user_id'  => (int)($u->USER_ID ?? 0),
            'name'     => (string)($u->NAME ?? ''),
            'email'    => (string)($u->COMMUNICATION_EMAIL_ID ?? ''),
            'user_type_id' => (int)($u->USER_TYPE_ID ?? 0),
        ];
    }

    public function getAdminProfile(int $userId): ?object
    {
        try {
            $r = $this->db->select("SELECT * FROM tbl_user WHERE user_id=".$userId." AND user_type_id=1 LIMIT 1");
            return $r[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function updateAdminProfile(array $d): bool
    {
        try {
            $id   = (int)$d['user_id'];
            $name = addslashes(trim($d['name'] ?? ''));
            $isd  = (int)($d['communication_mobile_num_isd'] ?? 91);
            $mob  = addslashes(trim($d['communication_mobile_num'] ?? ''));
            $comp = addslashes(trim($d['company_name'] ?? ''));
            $desig= addslashes(trim($d['designation'] ?? ''));
            $rows = $this->db->update(
                "UPDATE tbl_user SET name='".$name."',communication_mobile_num_isd=".$isd.",
                 communication_mobile_num='".$mob."',company_name='".$comp."',designation='".$desig."'
                 WHERE user_id=".$id." AND user_type_id=1 LIMIT 1"
            );
            if ($rows >= 0) {
                // keep session name in sync
                $_SESSION['sinelec_admin']['NAME'] = $name ? stripslashes($name) : ($_SESSION['sinelec_admin']['NAME'] ?? '');
            }
            return $rows >= 0;
        } catch (Exception $e) { error_log('updateAdminProfile: '.$e->getMessage()); return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       DASHBOARD
    ───────────────────────────────────────────────────────────── */
    public function getDashboardStats(): array
    {
        try {
            $s = [];
            $r = $this->db->select("SELECT COUNT(*) AS C FROM tbl_order WHERE order_current_status NOT IN ('Cart','Delivered','Cancel Order')");
            $s['active_orders'] = (int)($r[0]->C ?? 0);
            $r = $this->db->select("SELECT COUNT(*) AS C FROM tbl_order WHERE order_current_status='Cancel Order'");
            $s['cancelled_orders'] = (int)($r[0]->C ?? 0);
            $r = $this->db->select("SELECT COUNT(*) AS C FROM tbl_product WHERE product_id>0");
            $s['products'] = (int)($r[0]->C ?? 0);
            $r = $this->db->select("SELECT COUNT(*) AS C FROM tbl_user WHERE user_type_id=2");
            $s['customers'] = (int)($r[0]->C ?? 0);
            $r = $this->db->select("SELECT COUNT(*) AS C FROM tbl_enquiry_quote WHERE enquiry_status NOT IN ('Order Completed')");
            $s['enquiries'] = (int)($r[0]->C ?? 0);
            /* pending quotes (Quotation Pending only) */
            $r = $this->db->select("SELECT COUNT(*) AS C FROM tbl_enquiry_quote WHERE enquiry_status='Quotation Pending'");
            $s['pending_quotes'] = (int)($r[0]->C ?? 0);
            /* total revenue from completed/delivered */
            $r = $this->db->select("SELECT COALESCE(SUM(order_total_amt),0) AS T FROM tbl_order WHERE order_current_status IN ('Delivered','Payment Successful','Invoice Payment Successful','Bank Transfer Payment Successful','Online Successful','Other Channel Sell Successful')");
            $s['total_revenue'] = (float)($r[0]->T ?? 0);
            /* dispatched today */
            $r = $this->db->select("SELECT COUNT(*) AS C FROM tbl_order WHERE order_current_status='Dispatched'");
            $s['dispatched'] = (int)($r[0]->C ?? 0);
            /* low stock: products where total_product <= product_threshold */
            $r = $this->db->select("SELECT COUNT(*) AS C FROM tbl_product WHERE total_product <= product_threshold AND product_id>0");
            $s['low_stock'] = (int)($r[0]->C ?? 0);
            /* new customers this month */
            $r = $this->db->select("SELECT COUNT(*) AS C FROM tbl_user WHERE user_type_id=2 AND MONTH(user_id)=MONTH(CURDATE())");
            /* user table has no created_at — use a safe fallback */
            $s['new_customers_month'] = 0;
            /* orders this month */
            $r = $this->db->select("SELECT COUNT(*) AS C FROM tbl_order WHERE order_current_status!='Cart' AND MONTH(order_date)=MONTH(CURDATE()) AND YEAR(order_date)=YEAR(CURDATE())");
            $s['orders_this_month'] = (int)($r[0]->C ?? 0);
            return $s;
        } catch (Exception $e) {
            error_log('getDashboardStats: '.$e->getMessage());
            return ['active_orders'=>0,'cancelled_orders'=>0,'products'=>0,'customers'=>0,'enquiries'=>0,'pending_quotes'=>0,'total_revenue'=>0,'dispatched'=>0,'low_stock'=>0,'orders_this_month'=>0];
        }
    }

    public function getRecentOrders(int $limit = 5): array
    {
        try {
            return $this->db->select(
                "SELECT o.order_id, o.order_number, o.order_current_status, o.order_total_amt, o.order_date, u.name, u.communication_email_id
                 FROM tbl_order o LEFT JOIN tbl_user u ON u.user_id=o.user_id
                 WHERE o.order_current_status != 'Cart' ORDER BY o.order_id DESC LIMIT ".(int)$limit
            );
        } catch (Exception $e) { error_log('getRecentOrders: '.$e->getMessage()); return []; }
    }

    public function getMonthlyOrderChart(): array
    {
        try {
            $rows = $this->db->select(
                "SELECT DATE_FORMAT(order_date,'%b %Y') AS lbl,
                        DATE_FORMAT(order_date,'%Y-%m') AS ym,
                        COUNT(*) AS orders,
                        COALESCE(SUM(order_total_amt),0) AS revenue
                 FROM tbl_order
                 WHERE order_current_status != 'Cart'
                   AND order_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
                 GROUP BY ym, lbl ORDER BY ym ASC"
            );
            /* ensure all 12 months present */
            $map = [];
            foreach ($rows as $r) { $map[$r->ym] = $r; }
            $labels = $orders = $revenue = [];
            for ($i = 11; $i >= 0; $i--) {
                $ym  = date('Y-m', strtotime("-$i month"));
                $lbl = date('M Y',  strtotime("-$i month"));
                $labels[]  = $lbl;
                $orders[]  = isset($map[$ym]) ? (int)$map[$ym]->orders : 0;
                $revenue[] = isset($map[$ym]) ? round((float)$map[$ym]->revenue, 2) : 0;
            }
            return compact('labels', 'orders', 'revenue');
        } catch (Exception $e) { error_log('getMonthlyOrderChart: '.$e->getMessage()); return ['labels'=>[],'orders'=>[],'revenue'=>[]]; }
    }

    public function getOrderStatusBreakdown(): array
    {
        try {
            $rows = $this->db->select(
                "SELECT order_current_status AS status, COUNT(*) AS cnt
                 FROM tbl_order WHERE order_current_status != 'Cart'
                 GROUP BY order_current_status ORDER BY cnt DESC"
            );
            $labels = $data = [];
            foreach ($rows as $r) { $labels[] = $r->status; $data[] = (int)$r->cnt; }
            return compact('labels', 'data');
        } catch (Exception $e) { error_log('getOrderStatusBreakdown: '.$e->getMessage()); return ['labels'=>[],'data'=>[]]; }
    }

    public function getPendingEnquiries(int $limit = 8): array
    {
        try {
            return $this->db->select(
                "SELECT eq.enquiry_quote_id, eq.user_name, eq.company_name, eq.user_email,
                        eq.delivery_country, eq.enquiry_date, eq.enquiry_status,
                        COUNT(eqp.enquiry_quote_product_id) AS item_count
                 FROM tbl_enquiry_quote eq
                 LEFT JOIN tbl_enquiry_quote_product eqp ON eqp.enquiry_quote_id=eq.enquiry_quote_id
                 WHERE eq.enquiry_status='Quotation Pending'
                 GROUP BY eq.enquiry_quote_id ORDER BY eq.enquiry_date DESC LIMIT ".(int)$limit
            );
        } catch (Exception $e) { error_log('getPendingEnquiries: '.$e->getMessage()); return []; }
    }

    public function getRecentEnquiries(int $limit = 5): array
    {
        try {
            return $this->db->select(
                "SELECT eq.enquiry_quote_id, eq.user_name, eq.company_name, eq.enquiry_date, eq.enquiry_status, eq.delivery_country,
                        COUNT(eqp.enquiry_quote_product_id) AS item_count
                 FROM tbl_enquiry_quote eq
                 LEFT JOIN tbl_enquiry_quote_product eqp ON eqp.enquiry_quote_id=eq.enquiry_quote_id
                 GROUP BY eq.enquiry_quote_id ORDER BY eq.enquiry_date DESC LIMIT ".(int)$limit
            );
        } catch (Exception $e) { error_log('getRecentEnquiries: '.$e->getMessage()); return []; }
    }

    public function getLowStockProducts(int $limit = 6): array
    {
        try {
            return $this->db->select(
                "SELECT p.product_id, p.product_name, p.product_code, p.total_product, p.product_threshold, c.product_category_name
                 FROM tbl_product p
                 LEFT JOIN tbl_product_category c ON c.product_category_id=p.product_category_id
                 WHERE p.total_product <= p.product_threshold AND p.product_id>0
                 ORDER BY (p.total_product - p.product_threshold) ASC LIMIT ".(int)$limit
            );
        } catch (Exception $e) { error_log('getLowStockProducts: '.$e->getMessage()); return []; }
    }

    public function getTopSellingProducts(int $limit = 5): array
    {
        try {
            return $this->db->select(
                "SELECT p.product_id, p.product_name, p.product_code, p.total_sold, p.product_amt,
                        c.product_category_name
                 FROM tbl_product p
                 LEFT JOIN tbl_product_category c ON c.product_category_id=p.product_category_id
                 WHERE p.total_sold > 0
                 ORDER BY p.total_sold DESC LIMIT ".(int)$limit
            );
        } catch (Exception $e) { error_log('getTopSellingProducts: '.$e->getMessage()); return []; }
    }

    /* ─────────────────────────────────────────────────────────────
       CATEGORIES
    ───────────────────────────────────────────────────────────── */
    public function getAllCategories(): array
    {
        try {
            return $this->db->select(
                "SELECT pc.*, parent.product_category_name AS parent_name
                 FROM tbl_product_category pc
                 LEFT JOIN tbl_product_category parent ON parent.product_category_id=pc.parent_category_id
                 ORDER BY pc.parent_category_id, pc.priority, pc.product_category_name"
            );
        } catch (Exception $e) { error_log('getAllCategories: '.$e->getMessage()); return []; }
    }

    public function getParentCategories(): array
    {
        try {
            return $this->db->select(
                "SELECT * FROM tbl_product_category WHERE parent_category_id=0 ORDER BY product_category_name"
            );
        } catch (Exception $e) { return []; }
    }

    public function getCategoryById(int $id): ?object
    {
        try {
            $r = $this->db->select("SELECT * FROM tbl_product_category WHERE product_category_id=".$id." LIMIT 1");
            return $r[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function insertCategory(array $d): int
    {
        try {
            $name   = addslashes(trim($d['name']));
            $parent = (int)($d['parent_id'] ?? 0);
            $prio   = (int)($d['priority'] ?? 0);
            $desc   = addslashes(trim($d['description'] ?? ''));
            $ext    = addslashes(trim($d['ext'] ?? ''));
            return (int)$this->db->insert(
                "INSERT INTO tbl_product_category(product_category_name,parent_category_id,priority,description,ext)
                 VALUES('".$name."',".$parent.",".$prio.",'".$desc."','".$ext."')"
            );
        } catch (Exception $e) { error_log('insertCategory: '.$e->getMessage()); return 0; }
    }

    public function updateCategory(array $d): bool
    {
        try {
            $id     = (int)$d['id'];
            $name   = addslashes(trim($d['name']));
            $parent = (int)($d['parent_id'] ?? 0);
            $prio   = (int)($d['priority'] ?? 0);
            $desc   = addslashes(trim($d['description'] ?? ''));
            $ext    = addslashes(trim($d['ext'] ?? ''));
            $rows   = $this->db->update(
                "UPDATE tbl_product_category SET product_category_name='".$name."',parent_category_id=".$parent.",
                 priority=".$prio.",description='".$desc."',ext='".$ext."'
                 WHERE product_category_id=".$id
            );
            return $rows >= 0;
        } catch (Exception $e) { error_log('updateCategory: '.$e->getMessage()); return false; }
    }

    public function deleteCategory(int $id): bool
    {
        try {
            $c1 = $this->db->select("SELECT COUNT(*) AS C FROM tbl_product WHERE product_category_id=".$id);
            $c2 = $this->db->select("SELECT COUNT(*) AS C FROM tbl_product_category WHERE parent_category_id=".$id);
            if ((int)($c1[0]->C??0)>0 || (int)($c2[0]->C??0)>0) return false;
            $this->db->update("DELETE FROM tbl_product_category WHERE product_category_id=".$id);
            return true;
        } catch (Exception $e) { error_log('deleteCategory: '.$e->getMessage()); return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       PRODUCTS
    ───────────────────────────────────────────────────────────── */
    public function getAllProducts(array $filters = []): array
    {
        try {
            $where = 'WHERE p.product_id>0';
            if (!empty($filters['cat']))  $where .= " AND p.product_category_id=".(int)$filters['cat'];
            if (!empty($filters['name'])) $where .= " AND p.product_name LIKE '".addslashes($filters['name'])."%'";
            if (!empty($filters['code'])) $where .= " AND p.product_code LIKE '".addslashes($filters['code'])."%'";
            if (!empty($filters['status'])) $where .= " AND p.product_status='".addslashes($filters['status'])."'";
            return $this->db->select(
                "SELECT p.*, pc.product_category_name,
                 (SELECT image_ext FROM tbl_product_img WHERE product_id=p.product_id AND image_for='Product' LIMIT 1) AS thumb_ext
                 FROM tbl_product p
                 LEFT JOIN tbl_product_category pc ON pc.product_category_id=p.product_category_id
                 ".$where." ORDER BY p.product_id DESC"
            );
        } catch (Exception $e) { error_log('getAllProducts: '.$e->getMessage()); return []; }
    }

    public function getProductById(int $id): ?object
    {
        try {
            $r = $this->db->select("SELECT p.*, pc.product_category_name FROM tbl_product p LEFT JOIN tbl_product_category pc ON pc.product_category_id=p.product_category_id WHERE p.product_id=".$id." LIMIT 1");
            return $r[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function getProductImages(int $productId): array
    {
        try {
            return $this->db->select("SELECT * FROM tbl_product_img WHERE product_id=".$productId." ORDER BY priorty");
        } catch (Exception $e) { return []; }
    }

    public function insertProduct(array $d): int
    {
        try {
            $name    = addslashes(trim($d['product_name']));
            $code    = addslashes(trim($d['product_code'] ?? ''));
            $catId   = (int)($d['product_category_id'] ?? 0);
            $desc    = addslashes(trim($d['product_description'] ?? ''));
            $spec    = addslashes(trim($d['product_specification'] ?? ''));
            $details = addslashes(trim($d['product_details'] ?? ''));
            $amt     = (float)($d['product_amt'] ?? 0);
            $tax     = (float)($d['product_tax'] ?? 0);
            $disc    = (float)($d['product_discount'] ?? 0);
            $prio    = (int)($d['priority'] ?? 0);
            $status  = in_array($d['product_status']??'',['Active','In-Active']) ? $d['product_status'] : 'Active';
            $display = in_array($d['display_flag']??'',['Yes','No']) ? $d['display_flag'] : 'Yes';
            return (int)$this->db->insert(
                "INSERT INTO tbl_product(product_name,product_code,product_entry_date,product_category_id,display_flag,
                 product_status,product_description,product_specification,priorty,product_details,product_amt,product_tax,product_discount)
                 VALUES('".$name."','".$code."','".date('Y-m-d')."',".$catId.",'".$display."','".$status."','".$desc."','".$spec."',".$prio.",'".$details."',".$amt.",".$tax.",".$disc.")"
            );
        } catch (Exception $e) { error_log('insertProduct: '.$e->getMessage()); return 0; }
    }

    public function updateProduct(array $d): bool
    {
        try {
            $id      = (int)$d['product_id'];
            $name    = addslashes(trim($d['product_name']));
            $code    = addslashes(trim($d['product_code'] ?? ''));
            $catId   = (int)($d['product_category_id'] ?? 0);
            $desc    = addslashes(trim($d['product_description'] ?? ''));
            $spec    = addslashes(trim($d['product_specification'] ?? ''));
            $details = addslashes(trim($d['product_details'] ?? ''));
            $amt     = (float)($d['product_amt'] ?? 0);
            $tax     = (float)($d['product_tax'] ?? 0);
            $disc    = (float)($d['product_discount'] ?? 0);
            $prio    = (int)($d['priority'] ?? 0);
            $status  = in_array($d['product_status']??'',['Active','In-Active']) ? $d['product_status'] : 'Active';
            $display = in_array($d['display_flag']??'',['Yes','No']) ? $d['display_flag'] : 'Yes';
            $rows = $this->db->update(
                "UPDATE tbl_product SET product_name='".$name."',product_code='".$code."',product_category_id=".$catId.",
                 display_flag='".$display."',product_status='".$status."',product_description='".$desc."',
                 product_specification='".$spec."',priorty=".$prio.",product_details='".$details."',
                 product_amt=".$amt.",product_tax=".$tax.",product_discount=".$disc."
                 WHERE product_id=".$id
            );
            return $rows >= 0;
        } catch (Exception $e) { error_log('updateProduct: '.$e->getMessage()); return false; }
    }

    public function addProductImage(int $productId, string $ext, string $imageFor, string $title = '', int $prio = 0): int
    {
        try {
            $ext   = addslashes($ext);
            $imageFor = in_array($imageFor,['Product','Manual']) ? $imageFor : 'Product';
            $title = addslashes($title);
            return (int)$this->db->insert(
                "INSERT INTO tbl_product_img(product_id,image_ext,priorty,display_flag,image_for,product_manual_title,manual_upload_date)
                 VALUES(".$productId.",'".$ext."',".$prio.",'Yes','".$imageFor."','".$title."','".date('Y-m-d')."')"
            );
        } catch (Exception $e) { error_log('addProductImage: '.$e->getMessage()); return 0; }
    }

    public function deleteProductImage(int $imageId): bool
    {
        try {
            $this->db->update("DELETE FROM tbl_product_img WHERE image_id=".$imageId);
            return true;
        } catch (Exception $e) { return false; }
    }

    public function deleteProduct(int $id): bool
    {
        try {
            $c1 = $this->db->select("SELECT COUNT(*) AS C FROM tbl_enquiry_quote_product WHERE product_id=".$id);
            $c2 = $this->db->select("SELECT COUNT(*) AS C FROM tbl_product_purchase WHERE product_id=".$id);
            if ((int)($c1[0]->C??0)>0 || (int)($c2[0]->C??0)>0) return false;
            $this->db->update("DELETE FROM tbl_product_img WHERE product_id=".$id);
            $this->db->update("DELETE FROM tbl_product WHERE product_id=".$id);
            return true;
        } catch (Exception $e) { error_log('deleteProduct: '.$e->getMessage()); return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       PURCHASE
    ───────────────────────────────────────────────────────────── */
    public function getAllPurchaseRecords(int $productId = 0): array
    {
        try {
            $where = $productId > 0 ? "WHERE pp.product_id=".$productId : 'WHERE pp.product_purchase_id>0';
            return $this->db->select(
                "SELECT pp.*, p.product_name, p.product_code, pc.product_category_name
                 FROM tbl_product_purchase pp
                 LEFT JOIN tbl_product p ON p.product_id=pp.product_id
                 LEFT JOIN tbl_product_category pc ON pc.product_category_id=p.product_category_id
                 ".$where." ORDER BY pp.date_of_purchase DESC"
            );
        } catch (Exception $e) { error_log('getAllPurchaseRecords: '.$e->getMessage()); return []; }
    }

    public function insertPurchase(array $d): bool
    {
        try {
            $productId  = (int)$d['product_id'];
            $qty        = (int)$d['quantity_purchased'];
            $date       = addslashes(trim($d['date_of_purchase']));
            $from       = addslashes(trim($d['purchased_from'] ?? ''));
            $receipt    = addslashes(trim($d['receipt_no'] ?? ''));
            $amt        = (float)($d['purchase_amt'] ?? 0);
            $threshold  = (int)($d['product_threshold'] ?? 0);

            // update stock
            $stock = $this->db->select("SELECT total_product, total_remaining FROM tbl_product WHERE product_id=".$productId);
            if (!empty($stock)) {
                $total    = (int)($stock[0]->TOTAL_PRODUCT ?? 0) + $qty;
                $remaining= (int)($stock[0]->TOTAL_REMAINING ?? 0) + $qty;
                $this->db->update(
                    "UPDATE tbl_product SET total_product=".$total.",total_remaining=".$remaining.",product_threshold=".$threshold." WHERE product_id=".$productId
                );
            }
            $this->db->insert(
                "INSERT INTO tbl_product_purchase(product_id,quantity_purchased,date_of_purchase,purchased_from,receipt_no,purchase_amt)
                 VALUES(".$productId.",".$qty.",'".$date."','".$from."','".$receipt."',".$amt.")"
            );
            return true;
        } catch (Exception $e) { error_log('insertPurchase: '.$e->getMessage()); return false; }
    }

    public function deletePurchase(int $id): bool
    {
        try {
            $pp = $this->db->select("SELECT * FROM tbl_product_purchase WHERE product_purchase_id=".$id." LIMIT 1");
            if (empty($pp)) return false;
            $productId = (int)($pp[0]->PRODUCT_ID ?? 0);
            $qty       = (int)($pp[0]->QUANTITY_PURCHASED ?? 0);
            $stock = $this->db->select("SELECT total_product, total_remaining FROM tbl_product WHERE product_id=".$productId);
            if (!empty($stock)) {
                $total    = max(0, (int)($stock[0]->TOTAL_PRODUCT ?? 0) - $qty);
                $remaining= max(0, (int)($stock[0]->TOTAL_REMAINING ?? 0) - $qty);
                $this->db->update("UPDATE tbl_product SET total_product=".$total.",total_remaining=".$remaining." WHERE product_id=".$productId);
            }
            $this->db->update("DELETE FROM tbl_product_purchase WHERE product_purchase_id=".$id);
            return true;
        } catch (Exception $e) { error_log('deletePurchase: '.$e->getMessage()); return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       STOCK
    ───────────────────────────────────────────────────────────── */
    public function getStockRecords(array $filters = []): array
    {
        try {
            $where = 'WHERE p.product_id>0';
            if (!empty($filters['cat']))  $where .= " AND p.product_category_id=".(int)$filters['cat'];
            if (!empty($filters['name'])) $where .= " AND p.product_name LIKE '".addslashes($filters['name'])."%'";
            return $this->db->select(
                "SELECT p.product_id, p.product_name, p.product_code, p.total_product, p.total_sold, p.total_remaining, p.product_threshold,
                 pc.product_category_name
                 FROM tbl_product p
                 LEFT JOIN tbl_product_category pc ON pc.product_category_id=p.product_category_id
                 ".$where." ORDER BY p.product_name"
            );
        } catch (Exception $e) { error_log('getStockRecords: '.$e->getMessage()); return []; }
    }

    /* ─────────────────────────────────────────────────────────────
       ORDERS
    ───────────────────────────────────────────────────────────── */
    public function getActiveOrders(array $filters = []): array
    {
        try {
            $where = "WHERE o.order_current_status NOT IN ('Cart','Delivered','Cancelled')";
            if (!empty($filters['status'])) $where .= " AND o.order_current_status='".addslashes($filters['status'])."'";
            if (!empty($filters['search'])) $where .= " AND (u.name LIKE '%".addslashes($filters['search'])."%' OR u.communication_email_id LIKE '%".addslashes($filters['search'])."%')";
            return $this->db->select(
                "SELECT o.*, u.name AS customer_name, u.communication_email_id,
                 ua.city, ua.state, country.country AS country_name,
                 (SELECT COUNT(*) FROM tbl_add_cart WHERE order_id=o.order_id) AS item_count
                 FROM tbl_order o
                 LEFT JOIN tbl_user u ON u.user_id=o.user_id
                 LEFT JOIN tbl_user_address ua ON ua.user_address_id=o.user_address_id
                 LEFT JOIN tbl_country country ON country.country_id=ua.country_id
                 ".$where." ORDER BY o.order_id DESC"
            );
        } catch (Exception $e) { error_log('getActiveOrders: '.$e->getMessage()); return []; }
    }

    public function getOrderHistory(array $filters = []): array
    {
        try {
            $where = "WHERE o.order_current_status IN ('Delivered','Cancelled')";
            if (!empty($filters['search'])) $where .= " AND (u.name LIKE '%".addslashes($filters['search'])."%' OR u.communication_email_id LIKE '%".addslashes($filters['search'])."%')";
            return $this->db->select(
                "SELECT o.*, u.name AS customer_name, u.communication_email_id,
                 ua.city, ua.state, country.country AS country_name,
                 (SELECT COUNT(*) FROM tbl_add_cart WHERE order_id=o.order_id) AS item_count
                 FROM tbl_order o
                 LEFT JOIN tbl_user u ON u.user_id=o.user_id
                 LEFT JOIN tbl_user_address ua ON ua.user_address_id=o.user_address_id
                 LEFT JOIN tbl_country country ON country.country_id=ua.country_id
                 ".$where." ORDER BY o.order_id DESC"
            );
        } catch (Exception $e) { error_log('getOrderHistory: '.$e->getMessage()); return []; }
    }

    public function getOrderItems(int $orderId): array
    {
        try {
            return $this->db->select(
                "SELECT ac.*, p.product_name, p.product_code FROM tbl_add_cart ac
                 LEFT JOIN tbl_product p ON p.product_id=ac.product_id
                 WHERE ac.order_id=".$orderId
            );
        } catch (Exception $e) { return []; }
    }

    public function getOrderHistory_byId(int $orderId): array
    {
        try {
            return $this->db->select(
                "SELECT * FROM tbl_order_history WHERE order_id=".$orderId." ORDER BY order_history_id DESC"
            );
        } catch (Exception $e) { return []; }
    }

    public function updateOrderStatus(int $orderId, string $status, array $extra = []): bool
    {
        try {
            $allowedStatuses = ['Payment Successful','Dispatched','Delivered','Cancelled','Invoice Payment Pending'];
            if (!in_array($status, $allowedStatuses)) return false;
            $courier  = addslashes(trim($extra['courier_company'] ?? ''));
            $trackId  = addslashes(trim($extra['tracking_id'] ?? ''));
            $trackUrl = addslashes(trim($extra['tracking_url'] ?? ''));
            if ($status === 'Dispatched') {
                // update inventory when dispatched
                $items = $this->getOrderItems($orderId);
                foreach ($items as $item) {
                    $pid = (int)($item->PRODUCT_ID ?? 0);
                    $qty = (int)($item->QUANTITY ?? 0);
                    if ($pid > 0 && $qty > 0) {
                        $s = $this->db->select("SELECT total_sold, total_remaining FROM tbl_product WHERE product_id=".$pid);
                        if (!empty($s)) {
                            $sold = (int)($s[0]->TOTAL_SOLD ?? 0) + $qty;
                            $rem  = max(0, (int)($s[0]->TOTAL_REMAINING ?? 0) - $qty);
                            $this->db->update("UPDATE tbl_product SET total_sold=".$sold.",total_remaining=".$rem." WHERE product_id=".$pid);
                        }
                    }
                }
                $this->db->update(
                    "UPDATE tbl_order SET order_current_status='".$status."',dispatch_courier_company='".$courier."',
                     dispatch_courier_tracking_id='".$trackId."',dispatch_courier_tracking_url='".$trackUrl."'
                     WHERE order_id=".$orderId
                );
            } else {
                $this->db->update("UPDATE tbl_order SET order_current_status='".$status."' WHERE order_id=".$orderId);
            }
            $this->db->insert("INSERT INTO tbl_order_history(order_id,order_status) VALUES(".$orderId.",'".$status."')");
            return true;
        } catch (Exception $e) { error_log('updateOrderStatus: '.$e->getMessage()); return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       ENQUIRIES
    ───────────────────────────────────────────────────────────── */
    public function getAllEnquiries(array $filters = []): array
    {
        try {
            $where = 'WHERE eq.enquiry_quote_id>0';
            if (!empty($filters['status'])) $where .= " AND eq.enquiry_status='".addslashes($filters['status'])."'";
            if (!empty($filters['search'])) $where .= " AND (eq.user_name LIKE '%".addslashes($filters['search'])."%' OR eq.user_email LIKE '%".addslashes($filters['search'])."%' OR eq.company_name LIKE '%".addslashes($filters['search'])."%')";
            return $this->db->select(
                "SELECT eq.*, (SELECT COUNT(*) FROM tbl_enquiry_quote_product WHERE enquiry_quote_id=eq.enquiry_quote_id) AS product_count
                 FROM tbl_enquiry_quote eq
                 ".$where." ORDER BY eq.enquiry_quote_id DESC"
            );
        } catch (Exception $e) { error_log('getAllEnquiries: '.$e->getMessage()); return []; }
    }

    public function getEnquiryById(int $id): ?object
    {
        try {
            $r = $this->db->select(
                "SELECT eq.*, u.name AS u_name FROM tbl_enquiry_quote eq
                 LEFT JOIN tbl_user u ON u.user_id=eq.user_id
                 WHERE eq.enquiry_quote_id=".$id." LIMIT 1"
            );
            return $r[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function getEnquiryProducts(int $enquiryId): array
    {
        try {
            return $this->db->select(
                "SELECT eqp.*, p.product_name, p.product_code, pc.product_category_name
                 FROM tbl_enquiry_quote_product eqp
                 LEFT JOIN tbl_product p ON p.product_id=eqp.product_id
                 LEFT JOIN tbl_product_category pc ON pc.product_category_id=eqp.product_category_id
                 WHERE eqp.enquiry_quote_id=".$enquiryId
            );
        } catch (Exception $e) { return []; }
    }

    public function updateEnquiryStatus(int $id, string $status): bool
    {
        try {
            $allowed = ['Quotation Pending','Quotation Sent','Order Generated','Order Completed','Cancelled'];
            if (!in_array($status, $allowed)) return false;
            $this->db->update("UPDATE tbl_enquiry_quote SET enquiry_status='".addslashes($status)."' WHERE enquiry_quote_id=".$id);
            return true;
        } catch (Exception $e) { error_log('updateEnquiryStatus: '.$e->getMessage()); return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       CUSTOMERS
    ───────────────────────────────────────────────────────────── */
    public function getAllCustomers(array $filters = []): array
    {
        try {
            $where = "WHERE user_type_id=2";
            if (!empty($filters['search'])) $where .= " AND (name LIKE '%".addslashes($filters['search'])."%' OR communication_email_id LIKE '%".addslashes($filters['search'])."%')";
            return $this->db->select(
                "SELECT user_id, name, communication_email_id, communication_mobile_num_isd, communication_mobile_num,
                 company_name, designation, verified_flag, account_activation_flag,
                 (SELECT COUNT(*) FROM tbl_order WHERE user_id=tbl_user.user_id AND order_current_status!='Cart') AS order_count
                 FROM tbl_user ".$where." ORDER BY user_id DESC"
            );
        } catch (Exception $e) { error_log('getAllCustomers: '.$e->getMessage()); return []; }
    }

    public function getCustomerById(int $id): ?object
    {
        try {
            $r = $this->db->select("SELECT * FROM tbl_user WHERE user_id=".$id." AND user_type_id=2 LIMIT 1");
            return $r[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function getCustomerAddresses(int $userId): array
    {
        try {
            return $this->db->select(
                "SELECT ua.*, c.country AS country_name FROM tbl_user_address ua
                 LEFT JOIN tbl_country c ON c.country_id=ua.country_id
                 WHERE ua.user_id=".$userId
            );
        } catch (Exception $e) { return []; }
    }

    /* ─────────────────────────────────────────────────────────────
       BANNERS
    ───────────────────────────────────────────────────────────── */
    public function getAllBanners(): array
    {
        try {
            return $this->db->select("SELECT * FROM tbl_banner ORDER BY priority");
        } catch (Exception $e) { return []; }
    }

    public function insertBanner(array $d): int
    {
        try {
            $name  = addslashes(trim($d['banner_name']));
            $prio  = (int)($d['priority'] ?? 0);
            $desc  = addslashes(trim($d['banner_description'] ?? ''));
            $link  = addslashes(trim($d['hyperlink'] ?? ''));
            $ext   = addslashes(trim($d['ext'] ?? ''));
            return (int)$this->db->insert(
                "INSERT INTO tbl_banner(banner_name,banner_img_ext,priority,banner_description,hyperlink)
                 VALUES('".$name."','".$ext."',".$prio.",'".$desc."','".$link."')"
            );
        } catch (Exception $e) { error_log('insertBanner: '.$e->getMessage()); return 0; }
    }

    public function deleteBanner(int $id): bool
    {
        try {
            $this->db->update("DELETE FROM tbl_banner WHERE banner_id=".$id);
            return true;
        } catch (Exception $e) { return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       NEWS & EVENTS
    ───────────────────────────────────────────────────────────── */
    public function getAllNews(): array
    {
        try {
            return $this->db->select("SELECT * FROM tbl_news_event ORDER BY news_event_id DESC");
        } catch (Exception $e) { return []; }
    }

    public function getNewsById(int $id): ?object
    {
        try {
            $r = $this->db->select("SELECT * FROM tbl_news_event WHERE news_event_id=".$id." LIMIT 1");
            return $r[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function insertNews(array $d): int
    {
        try {
            $flag   = in_array($d['flag']??'',['News','Event']) ? $d['flag'] : 'News';
            $title  = addslashes(trim($d['title']));
            $date   = addslashes(trim($d['created_date']));
            $desc   = addslashes(trim($d['description'] ?? ''));
            $imgExt = addslashes(trim($d['img_ext'] ?? ''));
            $docExt = addslashes(trim($d['doc_ext'] ?? ''));
            $empId  = (int)($_SESSION['sinelec_admin']['USER_ID'] ?? 0);
            return (int)$this->db->insert(
                "INSERT INTO tbl_news_event(flag,title,created_date,description,created_by,img_ext,doc_ext)
                 VALUES('".$flag."','".$title."','".$date."','".$desc."',".$empId.",'".$imgExt."','".$docExt."')"
            );
        } catch (Exception $e) { error_log('insertNews: '.$e->getMessage()); return 0; }
    }

    public function updateNews(array $d): bool
    {
        try {
            $id     = (int)$d['news_event_id'];
            $flag   = in_array($d['flag']??'',['News','Event']) ? $d['flag'] : 'News';
            $title  = addslashes(trim($d['title']));
            $date   = addslashes(trim($d['created_date']));
            $desc   = addslashes(trim($d['description'] ?? ''));
            $imgExt = addslashes(trim($d['img_ext'] ?? ''));
            $docExt = addslashes(trim($d['doc_ext'] ?? ''));
            $empId  = (int)($_SESSION['sinelec_admin']['USER_ID'] ?? 0);
            $rows = $this->db->update(
                "UPDATE tbl_news_event SET flag='".$flag."',title='".$title."',created_date='".$date."',
                 description='".$desc."',created_by=".$empId.",img_ext='".$imgExt."',doc_ext='".$docExt."'
                 WHERE news_event_id=".$id
            );
            return $rows >= 0;
        } catch (Exception $e) { error_log('updateNews: '.$e->getMessage()); return false; }
    }

    public function deleteNews(int $id): bool
    {
        try {
            $this->db->update("DELETE FROM tbl_news_event WHERE news_event_id=".$id);
            return true;
        } catch (Exception $e) { return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       FAQ
    ───────────────────────────────────────────────────────────── */
    public function getAllFAQ(): array
    {
        try {
            return $this->db->select("SELECT * FROM tbl_faq ORDER BY faq_order ASC");
        } catch (Exception $e) { return []; }
    }

    public function getFAQById(int $id): ?object
    {
        try {
            $r = $this->db->select("SELECT * FROM tbl_faq WHERE faq_id=".$id." LIMIT 1");
            return $r[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function insertFAQ(array $d): int
    {
        try {
            $q   = addslashes(trim($d['faq_question']));
            $a   = addslashes(trim($d['faq_answer']));
            $ord = (int)($d['faq_order'] ?? 0);
            return (int)$this->db->insert(
                "INSERT INTO tbl_faq(faq_question,faq_answer,faq_order) VALUES('".$q."','".$a."',".$ord.")"
            );
        } catch (Exception $e) { error_log('insertFAQ: '.$e->getMessage()); return 0; }
    }

    public function updateFAQ(array $d): bool
    {
        try {
            $id  = (int)$d['faq_id'];
            $q   = addslashes(trim($d['faq_question']));
            $a   = addslashes(trim($d['faq_answer']));
            $ord = (int)($d['faq_order'] ?? 0);
            $rows = $this->db->update(
                "UPDATE tbl_faq SET faq_question='".$q."',faq_answer='".$a."',faq_order=".$ord." WHERE faq_id=".$id
            );
            return $rows >= 0;
        } catch (Exception $e) { error_log('updateFAQ: '.$e->getMessage()); return false; }
    }

    public function deleteFAQ(int $id): bool
    {
        try {
            $this->db->update("DELETE FROM tbl_faq WHERE faq_id=".$id);
            return true;
        } catch (Exception $e) { return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       JOBS
    ───────────────────────────────────────────────────────────── */
    public function getAllJobs(): array
    {
        try {
            return $this->db->select(
                "SELECT jc.*, (SELECT COUNT(*) FROM tbl_candidate_applied_for_job WHERE job_post_id=jc.job_post_id) AS applicant_count
                 FROM tbl_job_career jc ORDER BY jc.job_post_id DESC"
            );
        } catch (Exception $e) { return []; }
    }

    public function getJobById(int $id): ?object
    {
        try {
            $r = $this->db->select("SELECT * FROM tbl_job_career WHERE job_post_id=".$id." LIMIT 1");
            return $r[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function insertJob(array $d): int
    {
        try {
            $pos    = addslashes(trim($d['job_position']));
            $prio   = (int)($d['job_priority'] ?? 0);
            $loc    = addslashes(trim($d['job_location'] ?? ''));
            $desc   = addslashes(trim($d['job_discription'] ?? ''));
            $status = in_array($d['job_status']??'',['Active','In-Active']) ? $d['job_status'] : 'Active';
            return (int)$this->db->insert(
                "INSERT INTO tbl_job_career(job_position,job_priority,job_location,job_discription,job_status)
                 VALUES('".$pos."',".$prio.",'".$loc."','".$desc."','".$status."')"
            );
        } catch (Exception $e) { error_log('insertJob: '.$e->getMessage()); return 0; }
    }

    public function updateJob(array $d): bool
    {
        try {
            $id     = (int)$d['job_post_id'];
            $pos    = addslashes(trim($d['job_position']));
            $prio   = (int)($d['job_priority'] ?? 0);
            $loc    = addslashes(trim($d['job_location'] ?? ''));
            $desc   = addslashes(trim($d['job_discription'] ?? ''));
            $status = in_array($d['job_status']??'',['Active','In-Active']) ? $d['job_status'] : 'Active';
            $rows = $this->db->update(
                "UPDATE tbl_job_career SET job_position='".$pos."',job_priority=".$prio.",job_location='".$loc."',
                 job_discription='".$desc."',job_status='".$status."' WHERE job_post_id=".$id
            );
            return $rows >= 0;
        } catch (Exception $e) { error_log('updateJob: '.$e->getMessage()); return false; }
    }

    public function deleteJob(int $id): bool
    {
        try {
            $c = $this->db->select("SELECT COUNT(*) AS C FROM tbl_candidate_applied_for_job WHERE job_post_id=".$id);
            if ((int)($c[0]->C??0) > 0) return false;
            $this->db->update("DELETE FROM tbl_job_career WHERE job_post_id=".$id);
            return true;
        } catch (Exception $e) { return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       APPLICANTS
    ───────────────────────────────────────────────────────────── */
    public function getAllApplicants(array $filters = []): array
    {
        try {
            $where = "WHERE jc.job_status='Active'";
            if (!empty($filters['job_id'])) $where .= " AND cafj.job_post_id=".(int)$filters['job_id'];
            if (!empty($filters['search'])) $where .= " AND (cafj.candidate_name LIKE '%".addslashes($filters['search'])."%' OR cafj.candidate_email LIKE '%".addslashes($filters['search'])."%')";
            return $this->db->select(
                "SELECT cafj.*, jc.job_position FROM tbl_candidate_applied_for_job cafj
                 LEFT JOIN tbl_job_career jc ON cafj.job_post_id=jc.job_post_id
                 ".$where." ORDER BY cafj.applied_date DESC"
            );
        } catch (Exception $e) { error_log('getAllApplicants: '.$e->getMessage()); return []; }
    }

    public function deleteApplicant(int $id): bool
    {
        try {
            $this->db->update("DELETE FROM tbl_candidate_applied_for_job WHERE candidate_applied_job_id=".$id);
            return true;
        } catch (Exception $e) { return false; }
    }
}
?>
