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
                "SELECT * FROM tbl_user WHERE communication_email_id='".$email."' AND user_type_id IN (1,3) LIMIT 1"
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
            'user_id'      => (int)($u->USER_ID ?? 0),
            'name'         => (string)($u->NAME ?? ''),
            'email'        => (string)($u->COMMUNICATION_EMAIL_ID ?? ''),
            'user_type_id' => (int)($u->USER_TYPE_ID ?? 0),
            'role_id'      => (int)($u->ROLE_ID ?? 0),
        ];
    }

    /**
     * Writes one row to tbl_activity_log.
     * Never throws — logging must not break the calling operation.
     *
     * @param string       $activityType  'add' | 'edit' | 'delete'
     * @param string       $tableName     Primary table affected
     * @param string       $query         The SQL that was executed
     * @param mixed        $oldData       Row state BEFORE change (object/array) — null for inserts
     * @param mixed        $newData       Row state AFTER change (array) — null for deletes
     */
    private function logActivity(
        string $activityType,
        string $tableName,
        string $query,
        mixed  $oldData = null,
        mixed  $newData = null
    ): void {
        try {
            $userId     = (int)($_SESSION['sinelec_admin']['USER_ID']      ?? 0);
            $userTypeId = (int)($_SESSION['sinelec_admin']['USER_TYPE_ID'] ?? 0);
            $fileName   = addslashes(
                parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? 'cli'
            );
            $qEsc   = addslashes($query);
            $tEsc   = addslashes($tableName);
            $tyEsc  = addslashes($activityType);
            $oldSql = $oldData !== null
                ? "'".addslashes(json_encode($oldData, JSON_UNESCAPED_UNICODE))."'"
                : 'NULL';
            $newSql = $newData !== null
                ? "'".addslashes(json_encode($newData, JSON_UNESCAPED_UNICODE))."'"
                : 'NULL';

            $this->db->insert(
                "INSERT INTO tbl_activity_log
                 (user_id, user_type_id, activity_type, file_name, activity_query, table_name, old_data, new_data)
                 VALUES($userId, $userTypeId, '$tyEsc', '$fileName', '$qEsc', '$tEsc', $oldSql, $newSql)"
            );
        } catch (Exception $e) {
            error_log('logActivity: '.$e->getMessage());
        }
    }

    /**
     * Returns grouped sidebar menu for the current user.
     * Admin (type 1): all active modules + menus.
     * Employee (type 3): only menus where can_view=1 for their role.
     */
    /**
     * Returns sidebar menu with per-item permission flags.
     * Admin  (type 1): all menus, can_view/add/edit/delete = true.
     * Employee (type 3): only can_view=1 menus for their role, with actual permission flags.
     * Result is meant to be cached in $_SESSION['sinelec_admin']['MENU_DATA'].
     */
    public function getAdminMenu(): array
    {
        try {
            $userTypeId = (int)($_SESSION['sinelec_admin']['USER_TYPE_ID'] ?? 0);

            if ($userTypeId === 1) {
                $rows = $this->db->select(
                    "SELECT mo.module_id, mo.module_name, mo.icon AS module_icon,
                            mn.menu_id, mn.menu_name, mn.icon AS menu_icon, mn.path_link
                     FROM tbl_module mo
                     JOIN tbl_menu mn ON mn.module_id = mo.module_id
                     WHERE mo.status = 1 AND mn.status = 1
                     ORDER BY mo.priority ASC, mn.priority ASC"
                );
                $grouped = [];
                foreach ($rows as $r) {
                    $mid = (int)$r->MODULE_ID;
                    if (!isset($grouped[$mid])) {
                        $grouped[$mid] = [
                            'module_id'   => $mid,
                            'group'       => (string)$r->MODULE_NAME,
                            'module_icon' => (string)($r->MODULE_ICON ?? ''),
                            'items'       => [],
                        ];
                    }
                    $path = ltrim((string)($r->PATH_LINK ?? ''), '/');
                    $grouped[$mid]['items'][] = [
                        'key'        => $path,
                        'menu_id'    => (int)$r->MENU_ID,
                        'module_id'  => $mid,
                        'label'      => (string)$r->MENU_NAME,
                        'href'       => $path,
                        'icon'       => (string)($r->MENU_ICON ?? ''),
                        'can_view'   => true,
                        'can_add'    => true,
                        'can_edit'   => true,
                        'can_delete' => true,
                    ];
                }
                return array_values($grouped);
            }

            /* ── Employee: fetch only permitted menus with actual flags ── */
            $roleId = (int)($_SESSION['sinelec_admin']['ROLE_ID'] ?? 0);
            if ($roleId === 0) return [];

            $rows = $this->db->select(
                "SELECT mo.module_id, mo.module_name, mo.icon AS module_icon,
                        mn.menu_id, mn.menu_name, mn.icon AS menu_icon, mn.path_link,
                        rp.can_view, rp.can_add, rp.can_edit, rp.can_delete
                 FROM tbl_module mo
                 JOIN tbl_menu mn ON mn.module_id = mo.module_id
                 JOIN tbl_roles_permission rp
                      ON rp.menu_id = mn.menu_id AND rp.role_id = ".(int)$roleId."
                 WHERE mo.status = 1 AND mn.status = 1 AND rp.can_view = 1
                 ORDER BY mo.priority ASC, mn.priority ASC"
            );
            $grouped = [];
            foreach ($rows as $r) {
                $mid = (int)$r->MODULE_ID;
                if (!isset($grouped[$mid])) {
                    $grouped[$mid] = [
                        'module_id'   => $mid,
                        'group'       => (string)$r->MODULE_NAME,
                        'module_icon' => (string)($r->MODULE_ICON ?? ''),
                        'items'       => [],
                    ];
                }
                $path = ltrim((string)($r->PATH_LINK ?? ''), '/');
                $grouped[$mid]['items'][] = [
                    'key'        => $path,
                    'menu_id'    => (int)$r->MENU_ID,
                    'module_id'  => $mid,
                    'label'      => (string)$r->MENU_NAME,
                    'href'       => $path,
                    'icon'       => (string)($r->MENU_ICON ?? ''),
                    'can_view'   => (bool)$r->CAN_VIEW,
                    'can_add'    => (bool)$r->CAN_ADD,
                    'can_edit'   => (bool)$r->CAN_EDIT,
                    'can_delete' => (bool)$r->CAN_DELETE,
                ];
            }
            return array_values($grouped);
        } catch (Exception $e) {
            error_log('getAdminMenu: '.$e->getMessage());
            return [];
        }
    }

    public function getAdminProfile(int $userId): ?object
    {
        try {
            $r = $this->db->select("SELECT * FROM tbl_user WHERE user_id=".$userId." AND user_type_id IN (1,3) LIMIT 1");
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
            $oldRow = $this->db->select("SELECT * FROM tbl_user WHERE user_id=$id LIMIT 1")[0] ?? null;
            $sql = "UPDATE tbl_user SET name='".$name."',communication_mobile_num_isd=".$isd.",
                 communication_mobile_num='".$mob."',company_name='".$comp."',designation='".$desig."'
                 WHERE user_id=".$id." AND user_type_id IN (1,3) LIMIT 1";
            $rows = $this->db->update($sql);
            if ($rows >= 0) {
                // keep session name in sync
                $_SESSION['sinelec_admin']['NAME'] = $name ? stripslashes($name) : ($_SESSION['sinelec_admin']['NAME'] ?? '');
                $this->logActivity('edit', 'tbl_user', $sql,
                    $oldRow !== null ? (array)$oldRow : null,
                    ['user_id'=>$id,'name'=>$name,'communication_mobile_num_isd'=>$isd,
                     'communication_mobile_num'=>$mob,'company_name'=>$comp,'designation'=>$desig]
                );
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
                "SELECT pc.*,
                        parent.product_category_name AS parent_name,
                        (SELECT COUNT(*) FROM tbl_product_category sub
                         WHERE sub.parent_category_id = pc.product_category_id) AS sub_count
                 FROM tbl_product_category pc
                 LEFT JOIN tbl_product_category parent ON parent.product_category_id = pc.parent_category_id
                 ORDER BY pc.parent_category_id ASC, pc.priority ASC, pc.product_category_name ASC"
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
            $sql = "INSERT INTO tbl_product_category(product_category_name,parent_category_id,priority,description,ext)
                 VALUES('".$name."',".$parent.",".$prio.",'".$desc."','".$ext."')";
            $newId = (int)$this->db->insert($sql);
            if ($newId > 0) {
                $this->logActivity('add', 'tbl_product_category', $sql,
                    null,
                    ['product_category_name'=>$name,'parent_category_id'=>$parent,
                     'priority'=>$prio,'description'=>$desc,'ext'=>$ext]
                );
            }
            return $newId;
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
            $oldRow = $this->getCategoryById($id);
            $sql = "UPDATE tbl_product_category SET product_category_name='".$name."',parent_category_id=".$parent.",
                 priority=".$prio.",description='".$desc."',ext='".$ext."'
                 WHERE product_category_id=".$id;
            $rows = $this->db->update($sql);
            if ($rows >= 0) {
                $this->logActivity('edit', 'tbl_product_category', $sql,
                    $oldRow !== null ? (array)$oldRow : null,
                    ['product_category_id'=>$id,'product_category_name'=>$name,
                     'parent_category_id'=>$parent,'priority'=>$prio,'description'=>$desc,'ext'=>$ext]
                );
            }
            return $rows >= 0;
        } catch (Exception $e) { error_log('updateCategory: '.$e->getMessage()); return false; }
    }

    public function deleteCategory(int $id): bool
    {
        try {
            $c1 = $this->db->select("SELECT COUNT(*) AS C FROM tbl_product WHERE product_category_id=".$id);
            $c2 = $this->db->select("SELECT COUNT(*) AS C FROM tbl_product_category WHERE parent_category_id=".$id);
            if ((int)($c1[0]->C??0)>0 || (int)($c2[0]->C??0)>0) return false;
            $oldRow = $this->getCategoryById($id);
            $sql = "DELETE FROM tbl_product_category WHERE product_category_id=".$id;
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_product_category', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                null
            );
            return true;
        } catch (Exception $e) { error_log('deleteCategory: '.$e->getMessage()); return false; }
    }

    /* Single save — insert when id=0, update when id>0 */
    public function saveProductCategory(array $d): int|false
    {
        $id = (int)($d['id'] ?? 0);
        if ($id > 0) {
            $ok = $this->updateCategory([
                'id'          => $id,
                'name'        => $d['name'] ?? '',
                'parent_id'   => $d['parent_id'] ?? 0,
                'priority'    => $d['priority'] ?? 0,
                'description' => $d['description'] ?? '',
                'ext'         => $d['ext'] ?? '',
            ]);
            return $ok ? $id : false;
        }
        $newId = $this->insertCategory([
            'name'        => $d['name'] ?? '',
            'parent_id'   => $d['parent_id'] ?? 0,
            'priority'    => $d['priority'] ?? 0,
            'description' => $d['description'] ?? '',
            'ext'         => $d['ext'] ?? '',
        ]);
        return $newId > 0 ? $newId : false;
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
                 (SELECT product_image_path FROM tbl_product_img WHERE product_id=p.product_id AND image_for='Product' LIMIT 1) AS thumb_ext
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
            $sql = "INSERT INTO tbl_product(product_name,product_code,product_entry_date,product_category_id,display_flag,
                 product_status,product_description,product_specification,priorty,product_details,product_amt,product_tax,product_discount)
                 VALUES('".$name."','".$code."','".date('Y-m-d')."',".$catId.",'".$display."','".$status."','".$desc."','".$spec."',".$prio.",'".$details."',".$amt.",".$tax.",".$disc.")";
            $newId = (int)$this->db->insert($sql);
            if ($newId > 0) {
                $this->logActivity('add', 'tbl_product', $sql,
                    null,
                    ['product_name'=>$name,'product_code'=>$code,'product_category_id'=>$catId,
                     'display_flag'=>$display,'product_status'=>$status,'product_description'=>$desc,
                     'product_specification'=>$spec,'priorty'=>$prio,'product_details'=>$details,
                     'product_amt'=>$amt,'product_tax'=>$tax,'product_discount'=>$disc]
                );
            }
            return $newId;
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
            $oldRow = $this->getProductById($id);
            $sql = "UPDATE tbl_product SET product_name='".$name."',product_code='".$code."',product_category_id=".$catId.",
                 display_flag='".$display."',product_status='".$status."',product_description='".$desc."',
                 product_specification='".$spec."',priorty=".$prio.",product_details='".$details."',
                 product_amt=".$amt.",product_tax=".$tax.",product_discount=".$disc."
                 WHERE product_id=".$id;
            $rows = $this->db->update($sql);
            if ($rows >= 0) {
                $this->logActivity('edit', 'tbl_product', $sql,
                    $oldRow !== null ? (array)$oldRow : null,
                    ['product_id'=>$id,'product_name'=>$name,'product_code'=>$code,
                     'product_category_id'=>$catId,'display_flag'=>$display,'product_status'=>$status,
                     'product_description'=>$desc,'product_specification'=>$spec,'priorty'=>$prio,
                     'product_details'=>$details,'product_amt'=>$amt,'product_tax'=>$tax,'product_discount'=>$disc]
                );
            }
            return $rows >= 0;
        } catch (Exception $e) { error_log('updateProduct: '.$e->getMessage()); return false; }
    }

    public function addProductImage(int $productId, string $path, string $imageFor, string $title = '', int $prio = 0, string $imageName = '', string $displayFlag = 'Yes', string $hyperLink = ''): int
    {
        $path        = addslashes($path);
        $imageFor    = in_array($imageFor, ['Product', 'Product Mannual']) ? $imageFor : 'Product';
        $title       = addslashes($title);
        $imageName   = addslashes($imageName);
        $displayFlag = in_array($displayFlag, ['Yes', 'No']) ? $displayFlag : 'Yes';
        $hyperLink   = addslashes($hyperLink);
        $date        = date('Y-m-d');

        $sql   = "INSERT INTO tbl_product_img(product_id,product_image_path,image_name,priorty,display_flag,image_for,product_manual_title,hyper_link,manual_upload_date)
                  VALUES({$productId},'{$path}','{$imageName}',{$prio},'{$displayFlag}','{$imageFor}','{$title}','{$hyperLink}','{$date}')";
        $newId = (int)$this->db->insert($sql);

        if ($newId > 0) {
            $this->logActivity('add', 'tbl_product_img', $sql, null,
                ['product_id'=>$productId,'product_image_path'=>$path,'image_name'=>$imageName,
                 'priorty'=>$prio,'display_flag'=>$displayFlag,'image_for'=>$imageFor,
                 'product_manual_title'=>$title,'hyper_link'=>$hyperLink]
            );
        }
        return $newId;
    }

    public function deleteProductImage(int $imageId): bool
    {
        try {
            $oldRow = $this->db->select("SELECT * FROM tbl_product_img WHERE image_id=$imageId LIMIT 1")[0] ?? null;
            $sql = "DELETE FROM tbl_product_img WHERE image_id=".$imageId;
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_product_img', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                null
            );
            return true;
        } catch (Exception $e) { return false; }
    }

    public function deleteProduct(int $id): bool
    {
        try {
            $c1 = $this->db->select("SELECT COUNT(*) AS C FROM tbl_enquiry_quote_product WHERE product_id=".$id);
            $c2 = $this->db->select("SELECT COUNT(*) AS C FROM tbl_product_purchase WHERE product_id=".$id);
            if ((int)($c1[0]->C??0)>0 || (int)($c2[0]->C??0)>0) return false;
            $oldRow = $this->getProductById($id);
            $this->db->update("DELETE FROM tbl_product_img WHERE product_id=".$id);
            $sql = "DELETE FROM tbl_product WHERE product_id=".$id;
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_product', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                null
            );
            return true;
        } catch (Exception $e) { error_log('deleteProduct: '.$e->getMessage()); return false; }
    }

    public function saveProduct(array $d): int|false
    {
        try {
            $id        = (int)($d['product_id'] ?? 0);
            $name      = addslashes(trim($d['product_name'] ?? ''));
            $code      = addslashes(trim($d['product_code'] ?? ''));
            $catId     = (int)($d['product_category_id'] ?? 0);
            $desc      = addslashes(trim($d['product_description'] ?? ''));
            $spec      = addslashes(trim($d['product_specification'] ?? ''));
            $details   = addslashes(trim($d['product_details'] ?? ''));
            $amt       = (float)($d['product_amt'] ?? 0);
            $tax       = (float)($d['product_tax'] ?? 0);
            $disc      = (float)($d['product_discount'] ?? 0);
            $prio      = (int)($d['priorty'] ?? 0);
            $threshold = (float)($d['product_threshold'] ?? 1);
            $status    = in_array($d['product_status'] ?? '', ['Active','In-Active']) ? $d['product_status'] : 'Active';
            $display   = in_array($d['display_flag'] ?? '', ['Yes','No']) ? $d['display_flag'] : 'Yes';
            $label     = addslashes(trim($d['label'] ?? ''));
            $ratingRaw = trim($d['rating'] ?? '');
            $offerRaw  = trim($d['offer_percentage'] ?? '');

            if ($name === '') return false;

            $labelVal  = $label !== '' ? "'".$label."'" : 'NULL';
            $ratingVal = ($ratingRaw !== '' && is_numeric($ratingRaw)) ? (float)$ratingRaw : 'NULL';
            $offerVal  = ($offerRaw  !== '' && is_numeric($offerRaw))  ? (float)$offerRaw  : 'NULL';

            if ($id > 0) {
                $oldRow = $this->getProductById($id);
                $sql = "UPDATE tbl_product SET
                     product_name='".$name."', product_code='".$code."',
                     product_category_id=".$catId.", display_flag='".$display."',
                     product_status='".$status."', product_description='".$desc."',
                     product_specification='".$spec."', priorty=".$prio.",
                     product_details='".$details."', product_amt=".$amt.",
                     product_tax=".$tax.", product_discount=".$disc.",
                     label=".$labelVal.", rating=".$ratingVal.",
                     offer_percentage=".$offerVal.", product_threshold=".$threshold."
                     WHERE product_id=".$id;
                $this->db->update($sql);
                $this->logActivity('edit', 'tbl_product', $sql,
                    $oldRow !== null ? (array)$oldRow : null,
                    ['product_id'=>$id,'product_name'=>$name,'product_code'=>$code,
                     'product_category_id'=>$catId,'product_status'=>$status]
                );
                return $id;
            }

            $sql = "INSERT INTO tbl_product
                 (product_name, product_code, product_entry_date, product_category_id,
                  display_flag, product_status, product_description, product_specification,
                  priorty, product_details, product_amt, product_tax, product_discount,
                  label, rating, offer_percentage, product_threshold)
                 VALUES('".$name."', '".$code."', '".date('Y-m-d')."', ".$catId.",
                        '".$display."', '".$status."', '".$desc."', '".$spec."',
                        ".$prio.", '".$details."', ".$amt.", ".$tax.", ".$disc.",
                        ".$labelVal.", ".$ratingVal.", ".$offerVal.", ".$threshold.")";
            $newId = (int)$this->db->insert($sql);
            if ($newId > 0) {
                $this->logActivity('add', 'tbl_product', $sql, null,
                    ['product_name'=>$name,'product_code'=>$code,'product_category_id'=>$catId,
                     'product_status'=>$status,'product_amt'=>$amt]
                );
            }
            return $newId > 0 ? $newId : false;
        } catch (Exception $e) { error_log('saveProduct: '.$e->getMessage()); return false; }
    }

    public function getProductImageById(int $id): ?object
    {
        try {
            $r = $this->db->select("SELECT * FROM tbl_product_img WHERE image_id=".$id." LIMIT 1");
            return $r[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function getAllProductImagesIndexed(): array
    {
        try {
            $rows    = $this->db->select(
                "SELECT * FROM tbl_product_img ORDER BY product_id ASC, priorty ASC, image_id ASC"
            );
            $indexed = [];
            foreach ($rows as $r) {
                $pid = (int)(float)($r->PRODUCT_ID ?? 0);
                $indexed[$pid][] = $r;
            }
            return $indexed;
        } catch (Exception $e) { error_log('getAllProductImagesIndexed: '.$e->getMessage()); return []; }
    }

    /* SAMPLE CODES ──────────────────────────────────────────────── */
    public function getAllSampleCodesIndexed(): array
    {
        try {
            $rows    = $this->db->select(
                "SELECT * FROM tbl_product_sample_code ORDER BY product_id ASC, product_sample_code_id ASC"
            );
            $indexed = [];
            foreach ($rows as $r) {
                $pid = (int)(float)($r->PRODUCT_ID ?? 0);
                if ($pid > 0) $indexed[$pid][] = $r;
            }
            return $indexed;
        } catch (Exception $e) { error_log('getAllSampleCodesIndexed: '.$e->getMessage()); return []; }
    }

    public function getSampleCodesByProductId(int $pid): array
    {
        try {
            return $this->db->select(
                "SELECT * FROM tbl_product_sample_code WHERE product_id={$pid} ORDER BY product_sample_code_id ASC"
            );
        } catch (Exception $e) { error_log('getSampleCodesByProductId: '.$e->getMessage()); return []; }
    }

    public function saveSampleCodes(int $pid, array $codes): void
    {
        try {
            /* Delete all existing rows for this product then re-insert */
            $this->db->update("DELETE FROM tbl_product_sample_code WHERE product_id={$pid}");
            foreach ($codes as $c) {
                $lang = addslashes(trim((string)($c['lang'] ?? '')));
                $ide  = addslashes(trim((string)($c['ide']  ?? '')));
                $type = addslashes(trim((string)($c['type'] ?? '')));
                $os   = addslashes(trim((string)($c['os']   ?? '')));
                $url  = addslashes(trim((string)($c['url']  ?? '')));
                if ($lang === '' && $ide === '' && $url === '') continue; /* skip blank rows */
                $this->db->insert(
                    "INSERT INTO tbl_product_sample_code(product_id,language_technology,ide_compiler,type,os,ext)
                     VALUES({$pid},'{$lang}','{$ide}','{$type}','{$os}','{$url}')"
                );
            }
        } catch (Exception $e) { error_log('saveSampleCodes: '.$e->getMessage()); }
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
            $sql = "INSERT INTO tbl_product_purchase(product_id,quantity_purchased,date_of_purchase,purchased_from,receipt_no,purchase_amt)
                 VALUES(".$productId.",".$qty.",'".$date."','".$from."','".$receipt."',".$amt.")";
            $this->db->insert($sql);
            $this->logActivity('add', 'tbl_product_purchase', $sql,
                null,
                ['product_id'=>$productId,'quantity_purchased'=>$qty,'date_of_purchase'=>$date,
                 'purchased_from'=>$from,'receipt_no'=>$receipt,'purchase_amt'=>$amt]
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
            $sql = "DELETE FROM tbl_product_purchase WHERE product_purchase_id=".$id;
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_product_purchase', $sql,
                (array)$pp[0],
                null
            );
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
            $oldRow = $this->db->select("SELECT * FROM tbl_order WHERE order_id=$orderId LIMIT 1")[0] ?? null;
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
                $sql = "UPDATE tbl_order SET order_current_status='".$status."',dispatch_courier_company='".$courier."',
                     dispatch_courier_tracking_id='".$trackId."',dispatch_courier_tracking_url='".$trackUrl."'
                     WHERE order_id=".$orderId;
                $this->db->update($sql);
            } else {
                $sql = "UPDATE tbl_order SET order_current_status='".$status."' WHERE order_id=".$orderId;
                $this->db->update($sql);
            }
            $this->db->insert("INSERT INTO tbl_order_history(order_id,order_status) VALUES(".$orderId.",'".$status."')");
            $this->logActivity('edit', 'tbl_order', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                ['order_id'=>$orderId,'order_current_status'=>$status,
                 'dispatch_courier_company'=>$courier,'dispatch_courier_tracking_id'=>$trackId,
                 'dispatch_courier_tracking_url'=>$trackUrl]
            );
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
            $oldRow = $this->getEnquiryById($id);
            $sql = "UPDATE tbl_enquiry_quote SET enquiry_status='".addslashes($status)."' WHERE enquiry_quote_id=".$id;
            $this->db->update($sql);
            $this->logActivity('edit', 'tbl_enquiry_quote', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                ['enquiry_quote_id'=>$id,'enquiry_status'=>$status]
            );
            return true;
        } catch (Exception $e) { error_log('updateEnquiryStatus: '.$e->getMessage()); return false; }
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

    public function getBannerById(int $id): ?object
    {
        try {
            $r = $this->db->select("SELECT * FROM tbl_banner WHERE banner_id=".$id." LIMIT 1");
            return $r[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function insertBanner(array $d): int
    {
        try {
            $name  = addslashes(trim($d['banner_name']));
            $prio  = (int)($d['priority'] ?? 0);
            $desc  = addslashes(trim($d['banner_description'] ?? ''));
            $link  = addslashes(trim($d['hyperlink'] ?? ''));
            $ext   = addslashes(trim($d['ext'] ?? ''));
            $flag  = in_array($d['display_flag'] ?? 'Yes', ['Yes', 'No']) ? $d['display_flag'] : 'Yes';
            $color   = addslashes(trim($d['banner_bg_color'] ?? ''));
            $tags    = addslashes(trim($d['tags']         ?? ''));
            $btnOne  = addslashes(trim($d['btn_one']      ?? ''));
            $btnOneL = addslashes(trim($d['btn_one_link'] ?? ''));
            $btnTwo  = addslashes(trim($d['btn_two']      ?? ''));
            $btnTwoL = addslashes(trim($d['btn_two_link'] ?? ''));
            $sql = "INSERT INTO tbl_banner(banner_name,banner_img_ext,priority,banner_description,hyperlink,display_flag,color,tags,btn_one,btn_one_link,btn_two,btn_two_link)
                    VALUES('".$name."','".$ext."',".$prio.",'".$desc."','".$link."','".$flag."','".$color."','".$tags."','".$btnOne."','".$btnOneL."','".$btnTwo."','".$btnTwoL."')";
            $newId = (int)$this->db->insert($sql);
            if ($newId > 0) {
                $this->logActivity('add', 'tbl_banner', $sql,
                    null,
                    ['banner_name'=>$name,'banner_img_ext'=>$ext,'priority'=>$prio,'banner_description'=>$desc,
                     'hyperlink'=>$link,'display_flag'=>$flag,'color'=>$color,'tags'=>$tags,
                     'btn_one'=>$btnOne,'btn_one_link'=>$btnOneL,'btn_two'=>$btnTwo,'btn_two_link'=>$btnTwoL]
                );
            }
            return $newId;
        } catch (Exception $e) { error_log('insertBanner: '.$e->getMessage()); return 0; }
    }

    public function updateBanner(array $d): bool
    {
        try {
            $id    = (int)$d['banner_id'];
            $old   = $this->db->select("SELECT * FROM tbl_banner WHERE banner_id=$id LIMIT 1")[0] ?? null;
            $name  = addslashes(trim($d['banner_name']));
            $prio  = (int)($d['priority'] ?? 0);
            $desc  = addslashes(trim($d['banner_description'] ?? ''));
            $link  = addslashes(trim($d['hyperlink'] ?? ''));
            $ext   = addslashes(trim($d['ext'] ?? ''));
            $flag  = in_array($d['display_flag'] ?? 'Yes', ['Yes', 'No']) ? $d['display_flag'] : 'Yes';
            $color   = addslashes(trim($d['banner_bg_color'] ?? ''));
            $tags    = addslashes(trim($d['tags']         ?? ''));
            $btnOne  = addslashes(trim($d['btn_one']      ?? ''));
            $btnOneL = addslashes(trim($d['btn_one_link'] ?? ''));
            $btnTwo  = addslashes(trim($d['btn_two']      ?? ''));
            $btnTwoL = addslashes(trim($d['btn_two_link'] ?? ''));
            $sql = "UPDATE tbl_banner SET banner_name='$name', banner_img_ext='$ext', priority=$prio,
                    banner_description='$desc', hyperlink='$link', display_flag='$flag', color='$color',
                    tags='$tags', btn_one='$btnOne', btn_one_link='$btnOneL', btn_two='$btnTwo', btn_two_link='$btnTwoL'
                    WHERE banner_id=$id";
            $this->db->update($sql);
            $this->logActivity('edit', 'tbl_banner', $sql,
                $old !== null ? (array)$old : null,
                ['banner_name'=>$name,'banner_img_ext'=>$ext,'priority'=>$prio,'banner_description'=>$desc,
                 'hyperlink'=>$link,'display_flag'=>$flag,'color'=>$color,'tags'=>$tags,
                 'btn_one'=>$btnOne,'btn_one_link'=>$btnOneL,'btn_two'=>$btnTwo,'btn_two_link'=>$btnTwoL]
            );
            return true;
        } catch (Exception $e) { error_log('updateBanner: '.$e->getMessage()); return false; }
    }

    public function deleteBanner(int $id): bool
    {
        try {
            $oldRow = $this->db->select("SELECT * FROM tbl_banner WHERE banner_id=$id LIMIT 1")[0] ?? null;
            $sql = "DELETE FROM tbl_banner WHERE banner_id=".$id;
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_banner', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                null
            );
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
            $sql = "INSERT INTO tbl_news_event(flag,title,created_date,description,created_by,img_ext,doc_ext)
                 VALUES('".$flag."','".$title."','".$date."','".$desc."',".$empId.",'".$imgExt."','".$docExt."')";
            $newId = (int)$this->db->insert($sql);
            if ($newId > 0) {
                $this->logActivity('add', 'tbl_news_event', $sql,
                    null,
                    ['flag'=>$flag,'title'=>$title,'created_date'=>$date,'description'=>$desc,
                     'created_by'=>$empId,'img_ext'=>$imgExt,'doc_ext'=>$docExt]
                );
            }
            return $newId;
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
            $oldRow = $this->getNewsById($id);
            $sql = "UPDATE tbl_news_event SET flag='".$flag."',title='".$title."',created_date='".$date."',
                 description='".$desc."',created_by=".$empId.",img_ext='".$imgExt."',doc_ext='".$docExt."'
                 WHERE news_event_id=".$id;
            $rows = $this->db->update($sql);
            if ($rows >= 0) {
                $this->logActivity('edit', 'tbl_news_event', $sql,
                    $oldRow !== null ? (array)$oldRow : null,
                    ['news_event_id'=>$id,'flag'=>$flag,'title'=>$title,'created_date'=>$date,
                     'description'=>$desc,'created_by'=>$empId,'img_ext'=>$imgExt,'doc_ext'=>$docExt]
                );
            }
            return $rows >= 0;
        } catch (Exception $e) { error_log('updateNews: '.$e->getMessage()); return false; }
    }

    public function deleteNews(int $id): bool
    {
        try {
            $oldRow = $this->getNewsById($id);
            $sql = "DELETE FROM tbl_news_event WHERE news_event_id=".$id;
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_news_event', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                null
            );
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
            $sql = "INSERT INTO tbl_faq(faq_question,faq_answer,faq_order) VALUES('".$q."','".$a."',".$ord.")";
            $newId = (int)$this->db->insert($sql);
            if ($newId > 0) {
                $this->logActivity('add', 'tbl_faq', $sql,
                    null,
                    ['faq_question'=>$q,'faq_answer'=>$a,'faq_order'=>$ord]
                );
            }
            return $newId;
        } catch (Exception $e) { error_log('insertFAQ: '.$e->getMessage()); return 0; }
    }

    public function updateFAQ(array $d): bool
    {
        try {
            $id  = (int)$d['faq_id'];
            $q   = addslashes(trim($d['faq_question']));
            $a   = addslashes(trim($d['faq_answer']));
            $ord = (int)($d['faq_order'] ?? 0);
            $oldRow = $this->getFAQById($id);
            $sql = "UPDATE tbl_faq SET faq_question='".$q."',faq_answer='".$a."',faq_order=".$ord." WHERE faq_id=".$id;
            $rows = $this->db->update($sql);
            if ($rows >= 0) {
                $this->logActivity('edit', 'tbl_faq', $sql,
                    $oldRow !== null ? (array)$oldRow : null,
                    ['faq_id'=>$id,'faq_question'=>$q,'faq_answer'=>$a,'faq_order'=>$ord]
                );
            }
            return $rows >= 0;
        } catch (Exception $e) { error_log('updateFAQ: '.$e->getMessage()); return false; }
    }

    public function deleteFAQ(int $id): bool
    {
        try {
            $oldRow = $this->getFAQById($id);
            $sql = "DELETE FROM tbl_faq WHERE faq_id=".$id;
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_faq', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                null
            );
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
            $sql = "INSERT INTO tbl_job_career(job_position,job_priority,job_location,job_discription,job_status)
                 VALUES('".$pos."',".$prio.",'".$loc."','".$desc."','".$status."')";
            $newId = (int)$this->db->insert($sql);
            if ($newId > 0) {
                $this->logActivity('add', 'tbl_job_career', $sql,
                    null,
                    ['job_position'=>$pos,'job_priority'=>$prio,'job_location'=>$loc,
                     'job_discription'=>$desc,'job_status'=>$status]
                );
            }
            return $newId;
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
            $oldRow = $this->getJobById($id);
            $sql = "UPDATE tbl_job_career SET job_position='".$pos."',job_priority=".$prio.",job_location='".$loc."',
                 job_discription='".$desc."',job_status='".$status."' WHERE job_post_id=".$id;
            $rows = $this->db->update($sql);
            if ($rows >= 0) {
                $this->logActivity('edit', 'tbl_job_career', $sql,
                    $oldRow !== null ? (array)$oldRow : null,
                    ['job_post_id'=>$id,'job_position'=>$pos,'job_priority'=>$prio,
                     'job_location'=>$loc,'job_discription'=>$desc,'job_status'=>$status]
                );
            }
            return $rows >= 0;
        } catch (Exception $e) { error_log('updateJob: '.$e->getMessage()); return false; }
    }

    public function deleteJob(int $id): bool
    {
        try {
            $c = $this->db->select("SELECT COUNT(*) AS C FROM tbl_candidate_applied_for_job WHERE job_post_id=".$id);
            if ((int)($c[0]->C??0) > 0) return false;
            $oldRow = $this->getJobById($id);
            $sql = "DELETE FROM tbl_job_career WHERE job_post_id=".$id;
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_job_career', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                null
            );
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

    public function getApplicantById(int $id): ?object
    {
        try {
            return $this->db->select("SELECT * FROM tbl_candidate_applied_for_job WHERE candidate_applied_job_id=$id LIMIT 1")[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function deleteApplicant(int $id): bool
    {
        try {
            $oldRow = $this->db->select("SELECT * FROM tbl_candidate_applied_for_job WHERE candidate_applied_job_id=$id LIMIT 1")[0] ?? null;
            $sql = "DELETE FROM tbl_candidate_applied_for_job WHERE candidate_applied_job_id=".$id;
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_candidate_applied_for_job', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                null
            );
            return true;
        } catch (Exception $e) { return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       ROLES
    ───────────────────────────────────────────────────────────── */
    public function getAllRoles(): array
    {
        try {
            return $this->db->select(
                "SELECT r.*,
                 COUNT(DISTINCT rp.menu_id) AS MENU_COUNT
                 FROM tbl_roles r
                 LEFT JOIN tbl_roles_permission rp ON rp.role_id = r.role_id
                 GROUP BY r.role_id
                 ORDER BY r.priority ASC, r.role_name ASC"
            );
        } catch (Exception $e) { error_log('getAllRoles: '.$e->getMessage()); return []; }
    }

    public function getRoleById(int $id): ?object
    {
        try {
            $rows = $this->db->select("SELECT * FROM tbl_roles WHERE role_id=".$id);
            return $rows[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function getModulesWithMenus(): array
    {
        try {
            $rows = $this->db->select(
                "SELECT mo.module_id AS MODULE_ID, mo.module_name AS MODULE_NAME,
                        mn.menu_id AS MENU_ID, mn.menu_name AS MENU_NAME
                 FROM tbl_module mo
                 JOIN tbl_menu mn ON mn.module_id = mo.module_id
                 WHERE mo.status = 1 AND mn.status = 1
                 ORDER BY mo.priority ASC, mn.priority ASC"
            );
            $grouped = [];
            foreach ($rows as $r) {
                $mid = (int)$r->MODULE_ID;
                if (!isset($grouped[$mid])) {
                    $grouped[$mid] = [
                        'module_id'   => $mid,
                        'module_name' => (string)$r->MODULE_NAME,
                        'menus'       => [],
                    ];
                }
                $grouped[$mid]['menus'][] = [
                    'menu_id'   => (int)$r->MENU_ID,
                    'menu_name' => (string)$r->MENU_NAME,
                ];
            }
            return array_values($grouped);
        } catch (Exception $e) { error_log('getModulesWithMenus: '.$e->getMessage()); return []; }
    }

    public function getAllRolePermissions(): array
    {
        try {
            $rows = $this->db->select(
                "SELECT role_id AS ROLE_ID, menu_id AS MENU_ID,
                        can_view AS CAN_VIEW, can_add AS CAN_ADD,
                        can_edit AS CAN_EDIT, can_delete AS CAN_DELETE
                 FROM tbl_roles_permission"
            );
            $map = [];
            foreach ($rows as $r) {
                $map[(int)$r->ROLE_ID][(int)$r->MENU_ID] = [
                    'can_view'   => (int)$r->CAN_VIEW,
                    'can_add'    => (int)$r->CAN_ADD,
                    'can_edit'   => (int)$r->CAN_EDIT,
                    'can_delete' => (int)$r->CAN_DELETE,
                ];
            }
            return $map;
        } catch (Exception $e) { return []; }
    }

    public function saveRole(array $d, array $perms): int|false
    {
        try {
            $roleId  = (int)($d['role_id'] ?? 0);
            $name    = addslashes(trim($d['role_name'] ?? ''));
            $code    = addslashes(strtoupper(preg_replace('/\s+/', '_', trim($d['role_code'] ?? ''))));
            $desc    = addslashes(trim($d['description'] ?? ''));
            $prio    = (int)($d['priority'] ?? 0);
            $status  = (int)($d['status'] ?? 1) === 0 ? 0 : 1;

            if ($roleId > 0) {
                $oldRow = $this->getRoleById($roleId);
                $sql = "UPDATE tbl_roles SET role_name='".$name."', role_code='".$code."',
                     description='".$desc."', priority=".$prio.", status=".$status."
                     WHERE role_id=".$roleId;
                $this->db->update($sql);
                $this->db->update("DELETE FROM tbl_roles_permission WHERE role_id=".$roleId);
                $this->logActivity('edit', 'tbl_roles', $sql,
                    $oldRow !== null ? (array)$oldRow : null,
                    ['role_id'=>$roleId,'role_name'=>$name,'role_code'=>$code,
                     'description'=>$desc,'priority'=>$prio,'status'=>$status]
                );
            } else {
                $sql = "INSERT INTO tbl_roles(role_name, role_code, description, priority, status)
                     VALUES('".$name."', '".$code."', '".$desc."', ".$prio.", ".$status.")";
                $roleId = (int)$this->db->insert($sql);
                if ($roleId <= 0) return false;
                $this->logActivity('add', 'tbl_roles', $sql,
                    null,
                    ['role_name'=>$name,'role_code'=>$code,'description'=>$desc,
                     'priority'=>$prio,'status'=>$status]
                );
            }

            /* build menu→module_id map in one query */
            $menuMap = [];
            if (!empty($perms)) {
                $menuIds = implode(',', array_map('intval', array_keys($perms)));
                $mRows   = $this->db->select(
                    "SELECT menu_id AS MENU_ID, module_id AS MODULE_ID
                     FROM tbl_menu WHERE menu_id IN (".$menuIds.")"
                );
                foreach ($mRows as $mr) {
                    $menuMap[(int)$mr->MENU_ID] = (int)$mr->MODULE_ID;
                }
            }

            foreach ($perms as $menuId => $p) {
                $menuId   = (int)$menuId;
                $moduleId = (int)($menuMap[$menuId] ?? 0);
                if ($menuId <= 0 || $moduleId <= 0) continue;
                $cv = empty($p['can_view'])   ? 0 : 1;
                $ca = empty($p['can_add'])    ? 0 : 1;
                $ce = empty($p['can_edit'])   ? 0 : 1;
                $cd = empty($p['can_delete']) ? 0 : 1;
                if (!($cv || $ca || $ce || $cd)) continue;
                $this->db->insert(
                    "INSERT INTO tbl_roles_permission
                     (role_id, module_id, menu_id, can_view, can_add, can_edit, can_delete)
                     VALUES(".$roleId.",".$moduleId.",".$menuId.",".$cv.",".$ca.",".$ce.",".$cd.")"
                );
            }
            return $roleId;
        } catch (Exception $e) { error_log('saveRole: '.$e->getMessage()); return false; }
    }

    public function deleteRole(int $id): bool
    {
        try {
            $c = $this->db->select("SELECT COUNT(*) AS C FROM tbl_user WHERE role_id=".$id);
            if ((int)($c[0]->C ?? 0) > 0) return false;
            $oldRow = $this->getRoleById($id);
            $this->db->update("DELETE FROM tbl_roles_permission WHERE role_id=".$id);
            $sql = "DELETE FROM tbl_roles WHERE role_id=".$id;
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_roles', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                null
            );
            return true;
        } catch (Exception $e) { error_log('deleteRole: '.$e->getMessage()); return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       EMPLOYEES  (user_type_id = 3)
    ───────────────────────────────────────────────────────────── */
    public function getAllEmployees(array $filters = []): array
    {
        try {
            $where = "WHERE u.user_type_id = 3";
            if (!empty($filters['search'])) {
                $s = addslashes($filters['search']);
                $where .= " AND (u.name LIKE '%".$s."%' OR u.communication_email_id LIKE '%".$s."%' OR u.designation LIKE '%".$s."%')";
            }
            if (!empty($filters['role'])) $where .= " AND u.role_id=".(int)$filters['role'];
            if (isset($filters['status']) && $filters['status'] !== '') {
                $where .= " AND u.account_activation_flag='".addslashes($filters['status'])."'";
            }
            return $this->db->select(
                "SELECT u.user_id, u.name, u.communication_email_id,
                        u.communication_mobile_num_isd, u.communication_mobile_num,
                        u.company_name, u.designation,
                        u.account_activation_flag, u.verified_flag,
                        u.role_id, r.role_name
                 FROM tbl_user u  
                 LEFT JOIN tbl_roles r ON r.role_id = u.role_id
                 ".$where." ORDER BY u.user_id DESC"
            );
        } catch (Exception $e) { error_log('getAllEmployees: '.$e->getMessage()); return []; }
    }

    public function getEmployeeById(int $id): ?object
    {
        try {
            $rows = $this->db->select(
                "SELECT * FROM tbl_user WHERE user_id=".$id." AND user_type_id=3 LIMIT 1"
            );
            return $rows[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    /**
     * Insert or update an employee.
     * Returns: new/existing user_id on success, -1 if email already exists, false on error.
     */
    public function saveEmployee(array $d): int|false
    {
        try {
            $userId  = (int)($d['user_id'] ?? 0);
            $name    = addslashes(trim($d['name'] ?? ''));
            $email   = addslashes(strtolower(trim($d['communication_email_id'] ?? '')));
            $mobile  = addslashes(trim($d['communication_mobile_num'] ?? ''));
            $isd     = (int)($d['communication_mobile_num_isd'] ?? 91);
            $company = addslashes(trim($d['company_name'] ?? ''));
            $desig   = addslashes(trim($d['designation'] ?? ''));
            $roleId  = (int)($d['role_id'] ?? 0);
            $status  = ($d['account_activation_flag'] ?? '1') === '0' ? '0' : '1';
            $password = trim($d['password'] ?? '');

            if ($userId > 0) {
                /* ── Edit (no password update here — use resetEmployeePassword) ── */
                $oldRow = $this->getEmployeeById($userId);
                $roleVal = $roleId > 0 ? $roleId : 'NULL';
                $sql = "UPDATE tbl_user
                     SET name='".$name."',
                         communication_mobile_num_isd=".$isd.",
                         communication_mobile_num='".$mobile."',
                         company_name='".$company."',
                         designation='".$desig."',
                         role_id=".$roleVal.",
                         account_activation_flag='".$status."'
                     WHERE user_id=".$userId." AND user_type_id=3";
                $this->db->update($sql);
                $this->logActivity('edit', 'tbl_user', $sql,
                    $oldRow !== null ? (array)$oldRow : null,
                    ['user_id'=>$userId,'name'=>$name,'communication_mobile_num_isd'=>$isd,
                     'communication_mobile_num'=>$mobile,'company_name'=>$company,
                     'designation'=>$desig,'role_id'=>$roleId,'account_activation_flag'=>$status]
                );
                return $userId;
            } else {
                /* ── Insert ── */
                if ($email === '' || $password === '') return false;
                $dup = $this->db->select(
                    "SELECT COUNT(*) AS C FROM tbl_user WHERE communication_email_id='".$email."'"
                );
                if ((int)($dup[0]->C ?? 0) > 0) return -1;
                $hash    = addslashes(password_hash($password, PASSWORD_DEFAULT));
                $actKey  = bin2hex(random_bytes(16));
                $roleVal = $roleId > 0 ? $roleId : 'NULL';
                $sql = "INSERT INTO tbl_user
                     (user_type_id, name, communication_email_id, erp_password,
                      communication_mobile_num_isd, communication_mobile_num,
                      company_name, designation, role_id,
                      account_activation_flag, random_activation_key, verified_flag, is_pwd_updated)
                     VALUES(3, '".$name."', '".$email."', '".$hash."',
                            ".$isd.", '".$mobile."', '".$company."', '".$desig."', ".$roleVal.",
                            '".$status."', '".$actKey."', 'Yes', 1)";
                $id = (int)$this->db->insert($sql);
                if ($id > 0) {
                    $this->logActivity('add', 'tbl_user', $sql,
                        null,
                        ['user_type_id'=>3,'name'=>$name,'communication_email_id'=>$email,
                         'erp_password'=>'[hashed]','communication_mobile_num_isd'=>$isd,
                         'communication_mobile_num'=>$mobile,'company_name'=>$company,
                         'designation'=>$desig,'role_id'=>$roleId,
                         'account_activation_flag'=>$status,'verified_flag'=>'Yes']
                    );
                }
                return $id > 0 ? $id : false;
            }
        } catch (Exception $e) { error_log('saveEmployee: '.$e->getMessage()); return false; }
    }

    public function checkEmployeeEmailExists(string $email, int $excludeUserId = 0): bool
    {
        try {
            $email = addslashes(strtolower(trim($email)));
            $sql   = "SELECT COUNT(*) AS C FROM tbl_user WHERE communication_email_id='".$email."'";
            if ($excludeUserId > 0) $sql .= " AND user_id != ".$excludeUserId;
            $r = $this->db->select($sql);
            return (int)($r[0]->C ?? 0) > 0;
        } catch (Exception $e) { return false; }
    }

    public function resetEmployeePassword(int $userId, string $password): bool
    {
        try {
            $hash = addslashes(password_hash($password, PASSWORD_DEFAULT));
            $sql = "UPDATE tbl_user SET erp_password='".$hash."', is_pwd_updated=1
                 WHERE user_id=".$userId." AND user_type_id=3 LIMIT 1";
            $this->db->update($sql);
            $this->logActivity('edit', 'tbl_user', $sql,
                null,
                ['user_id'=>$userId,'action'=>'password_reset']
            );
            return true;
        } catch (Exception $e) { error_log('resetEmployeePassword: '.$e->getMessage()); return false; }
    }

    public function deleteEmployee(int $id): bool
    {
        try {
            $oldRow = $this->getEmployeeById($id);
            $sql = "DELETE FROM tbl_user WHERE user_id=".$id." AND user_type_id=3 LIMIT 1";
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_user', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                null
            );
            return true;
        } catch (Exception $e) { error_log('deleteEmployee: '.$e->getMessage()); return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       CUSTOMERS  (user_type_id = 2)
    ───────────────────────────────────────────────────────────── */
    public function getAllCustomers(array $filters = []): array
    {
        try {
            $where = "WHERE u.user_type_id = 2";
            if (!empty($filters['search'])) {
                $s = addslashes($filters['search']);
                $where .= " AND (u.name LIKE '%".$s."%' OR u.communication_email_id LIKE '%".$s."%' OR u.designation LIKE '%".$s."%')";
            }
            if (!empty($filters['role'])) $where .= " AND u.role_id=".(int)$filters['role'];
            if (isset($filters['status']) && $filters['status'] !== '') {
                $where .= " AND u.account_activation_flag='".addslashes($filters['status'])."'";
            }
            return $this->db->select(
                "SELECT u.user_id, u.name, u.communication_email_id,
                        u.communication_mobile_num_isd, u.communication_mobile_num,
                        u.company_name, u.designation,
                        u.account_activation_flag, u.verified_flag,
                        u.role_id, r.role_name
                 FROM tbl_user u
                 LEFT JOIN tbl_roles r ON r.role_id = u.role_id
                 ".$where." ORDER BY u.user_id DESC"
            );
        } catch (Exception $e) { error_log('getAllCustomers: '.$e->getMessage()); return []; }
    }

    public function getCustomerById(int $id): ?object
    {
        try {
            $r = $this->db->select(
                "SELECT * FROM tbl_user WHERE user_id=".$id." AND user_type_id=2 LIMIT 1"
            );
            return $r[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function saveCustomer(array $d): int|false
    {
        try {
            $userId  = (int)($d['user_id'] ?? 0);
            $name    = addslashes(trim($d['name'] ?? ''));
            $email   = addslashes(strtolower(trim($d['communication_email_id'] ?? '')));
            $mobile  = addslashes(trim($d['communication_mobile_num'] ?? ''));
            $isd     = (int)($d['communication_mobile_num_isd'] ?? 91);
            $company = addslashes(trim($d['company_name'] ?? ''));
            $desig   = addslashes(trim($d['designation'] ?? ''));
            $roleId  = (int)($d['role_id'] ?? 0);
            $status  = ($d['account_activation_flag'] ?? '1') === '0' ? '0' : '1';
            $password = trim($d['password'] ?? '');

            if ($userId > 0) {
                $oldRow  = $this->getCustomerById($userId);
                $roleVal = $roleId > 0 ? $roleId : 'NULL';
                $sql = "UPDATE tbl_user
                     SET name='".$name."',
                         communication_mobile_num_isd=".$isd.",
                         communication_mobile_num='".$mobile."',
                         company_name='".$company."',
                         designation='".$desig."',
                         role_id=".$roleVal.",
                         account_activation_flag='".$status."'
                     WHERE user_id=".$userId." AND user_type_id=2";
                $this->db->update($sql);
                $this->logActivity('edit', 'tbl_user', $sql,
                    $oldRow !== null ? (array)$oldRow : null,
                    ['user_id'=>$userId,'name'=>$name,'communication_mobile_num_isd'=>$isd,
                     'communication_mobile_num'=>$mobile,'company_name'=>$company,
                     'designation'=>$desig,'role_id'=>$roleId,'account_activation_flag'=>$status]
                );
                return $userId;
            } else {
                if ($email === '' || $password === '') return false;
                $dup = $this->db->select(
                    "SELECT COUNT(*) AS C FROM tbl_user WHERE communication_email_id='".$email."'"
                );
                if ((int)($dup[0]->C ?? 0) > 0) return -1;
                $hash    = addslashes(password_hash($password, PASSWORD_DEFAULT));
                $actKey  = bin2hex(random_bytes(16));
                $roleVal = $roleId > 0 ? $roleId : 'NULL';
                $sql = "INSERT INTO tbl_user
                     (user_type_id, name, communication_email_id, erp_password,
                      communication_mobile_num_isd, communication_mobile_num,
                      company_name, designation, role_id,
                      account_activation_flag, random_activation_key, verified_flag, is_pwd_updated)
                     VALUES(2, '".$name."', '".$email."', '".$hash."',
                            ".$isd.", '".$mobile."', '".$company."', '".$desig."', ".$roleVal.",
                            '".$status."', '".$actKey."', 'Yes', 1)";
                $id = (int)$this->db->insert($sql);
                if ($id > 0) {
                    $this->logActivity('add', 'tbl_user', $sql,
                        null,
                        ['user_type_id'=>2,'name'=>$name,'communication_email_id'=>$email,
                         'erp_password'=>'[hashed]','communication_mobile_num_isd'=>$isd,
                         'communication_mobile_num'=>$mobile,'company_name'=>$company,
                         'designation'=>$desig,'role_id'=>$roleId,
                         'account_activation_flag'=>$status,'verified_flag'=>'Yes']
                    );
                }
                return $id > 0 ? $id : false;
            }
        } catch (Exception $e) { error_log('saveCustomer: '.$e->getMessage()); return false; }
    }

    public function resetCustomerPassword(int $userId, string $password): bool
    {
        try {
            $hash = addslashes(password_hash($password, PASSWORD_DEFAULT));
            $sql = "UPDATE tbl_user SET erp_password='".$hash."', is_pwd_updated=1
                 WHERE user_id=".$userId." AND user_type_id=2 LIMIT 1";
            $this->db->update($sql);
            $this->logActivity('edit', 'tbl_user', $sql,
                null,
                ['user_id'=>$userId,'action'=>'password_reset']
            );
            return true;
        } catch (Exception $e) { error_log('resetCustomerPassword: '.$e->getMessage()); return false; }
    }

    public function deleteCustomer(int $id): bool
    {
        try {
            $oldRow = $this->getCustomerById($id);
            $sql = "DELETE FROM tbl_user WHERE user_id=".$id." AND user_type_id=2 LIMIT 1";
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_user', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                null
            );
            return true;
        } catch (Exception $e) { error_log('deleteCustomer: '.$e->getMessage()); return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       COMPANY
    ───────────────────────────────────────────────────────────── */
    public function getCompanyDetails(): ?object
    {
        try {
            $r = $this->db->select("SELECT * FROM tbl_company ORDER BY company_id ASC LIMIT 1");
            return $r[0] ?? null;
        } catch (Exception $e) { error_log('getCompanyDetails: '.$e->getMessage()); return null; }
    }

    public function updateCompanyDetails(array $d): bool
    {
        try {
            $co = $this->getCompanyDetails();
            $id = $co ? (int)(float)($co->COMPANY_ID ?? 0) : 0;

            $name          = addslashes(trim($d['name']                  ?? ''));
            $logo          = addslashes(trim($d['logo']                  ?? ''));
            $description   = addslashes(trim($d['description']           ?? ''));
            $contact       = addslashes(trim($d['contact_number']        ?? ''));
            $email         = addslashes(trim($d['email']                 ?? ''));
            $address       = addslashes(trim($d['address']               ?? ''));
            $fax           = addslashes(trim($d['fax']                   ?? ''));
            $facebook      = addslashes(trim($d['facebook_url']          ?? ''));
            $instagram     = addslashes(trim($d['instagram_url']         ?? ''));
            $linkedin      = addslashes(trim($d['linkedin_url']          ?? ''));
            $twitter       = addslashes(trim($d['twitter_url']           ?? ''));
            $youtube       = addslashes(trim($d['youtube_url']           ?? ''));
            $whatsapp      = addslashes(trim($d['whatsapp_number']       ?? ''));
            $support       = addslashes(trim($d['support_mail_id']       ?? ''));
            $instructions  = addslashes(trim($d['instructions']          ?? ''));
            $aboutUs       = addslashes(trim($d['about_us']              ?? ''));
            $legal         = addslashes(trim($d['legal_information']     ?? ''));
            $disclaimer    = addslashes(trim($d['disclaimer']            ?? ''));
            $privacy       = addslashes(trim($d['privacy_policy']        ?? ''));
            $terms         = addslashes(trim($d['terms_of_use']          ?? ''));
            $botName       = addslashes(trim($d['bot_name']              ?? ''));
            $mapUrl        = addslashes(trim($d['map_url']               ?? ''));
            $branchAddr    = addslashes(trim($d['branch_office_address'] ?? ''));
            $officeHrs     = addslashes(trim($d['office_hrs']            ?? ''));

            if ($id > 0) {
                $sql = "UPDATE tbl_company SET
                    name='$name', logo='$logo', description='$description',
                    contact_number='$contact', email='$email', address='$address', fax='$fax',
                    facebook_url='$facebook', instagram_url='$instagram', linkedin_url='$linkedin',
                    twitter_url='$twitter', youtube_url='$youtube', whatsapp_number='$whatsapp',
                    support_mail_id='$support', instructions='$instructions',
                    about_us='$aboutUs', legal_information='$legal', disclaimer='$disclaimer',
                    privacy_policy='$privacy', terms_of_use='$terms',
                    bot_name='$botName', map_url='$mapUrl',
                    branch_office_address='$branchAddr', office_hrs='$officeHrs'
                    WHERE company_id=$id";
                $this->db->update($sql);
            } else {
                $sql = "INSERT INTO tbl_company
                    (name,logo,description,contact_number,email,address,fax,
                     facebook_url,instagram_url,linkedin_url,twitter_url,youtube_url,
                     whatsapp_number,support_mail_id,instructions,
                     about_us,legal_information,disclaimer,privacy_policy,terms_of_use,
                     bot_name,map_url,branch_office_address,office_hrs)
                    VALUES('$name','$logo','$description','$contact','$email','$address','$fax',
                     '$facebook','$instagram','$linkedin','$twitter','$youtube',
                     '$whatsapp','$support','$instructions',
                     '$aboutUs','$legal','$disclaimer','$privacy','$terms',
                     '$botName','$mapUrl','$branchAddr','$officeHrs')";
                $this->db->insert($sql);
            }
            $this->logActivity('edit', 'tbl_company', '', null, ['name' => $name]);
            return true;
        } catch (Exception $e) { error_log('updateCompanyDetails: '.$e->getMessage()); return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       COUNTRIES
    ───────────────────────────────────────────────────────────── */
    public function getAllCountries(): array
    {
        try {
            return $this->db->select(
                "SELECT country_id, country FROM tbl_country ORDER BY country ASC"
            );
        } catch (Exception $e) { error_log('getAllCountries: '.$e->getMessage()); return []; }
    }

    /* ─────────────────────────────────────────────────────────────
       MANUFACTURERS
    ───────────────────────────────────────────────────────────── */
    public function getAllManufacturers(string $search = ''): array
    {
        try {
            $where = '';
            if ($search !== '') {
                $s = addslashes($search);
                $where = "WHERE m.name LIKE '%".$s."%'";
            }
            return $this->db->select(
                "SELECT m.*, c.country AS country_name
                 FROM tbl_manufacturers m
                 LEFT JOIN tbl_country c ON c.country_id = m.country_id
                 ".$where."
                 ORDER BY m.name ASC"
            );
        } catch (Exception $e) { error_log('getAllManufacturers: '.$e->getMessage()); return []; }
    }

    public function getManufacturerById(int $id): ?object
    {
        try {
            $r = $this->db->select(
                "SELECT m.*, c.country AS country_name
                 FROM tbl_manufacturers m
                 LEFT JOIN tbl_country c ON c.country_id = m.country_id
                 WHERE m.manufacturer_id=".$id." LIMIT 1"
            );
            return $r[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function saveManufacturer(array $d): int|false
    {
        try {
            $id         = (int)($d['id'] ?? 0);
            $name       = addslashes(trim($d['name'] ?? ''));
            $logo       = addslashes(trim($d['logo'] ?? ''));
            $countryId  = (int)($d['country_id'] ?? 0);
            $desc       = addslashes(trim($d['description'] ?? ''));
            $catIds     = addslashes(trim($d['product_category_ids'] ?? ''));
            $status     = (int)($d['status'] ?? 1);
            $countryVal = $countryId > 0 ? $countryId : 'NULL';

            if ($id > 0) {
                $oldRow = $this->getManufacturerById($id);
                $sql = "UPDATE tbl_manufacturers
                     SET name='".$name."',
                         logo='".$logo."',
                         country_id=".$countryVal.",
                         description='".$desc."',
                         product_category_ids='".$catIds."',
                         status=".$status."
                     WHERE manufacturer_id=".$id." LIMIT 1";
                $this->db->update($sql);
                $this->logActivity('edit', 'tbl_manufacturers', $sql,
                    $oldRow !== null ? (array)$oldRow : null,
                    ['manufacturer_id'=>$id,'name'=>$name,'logo'=>$logo,'country_id'=>$countryId,
                     'description'=>$desc,'product_category_ids'=>$catIds,'status'=>$status]
                );
                return $id;
            }

            if ($name === '') return false;
            $sql = "INSERT INTO tbl_manufacturers
                 (name, logo, country_id, description, product_category_ids, status)
                 VALUES('".$name."', '".$logo."', ".$countryVal.", '".$desc."', '".$catIds."', ".$status.")";
            $newId = (int)$this->db->insert($sql);
            if ($newId > 0) {
                $this->logActivity('add', 'tbl_manufacturers', $sql,
                    null,
                    ['name'=>$name,'logo'=>$logo,'country_id'=>$countryId,
                     'description'=>$desc,'product_category_ids'=>$catIds,'status'=>$status]
                );
            }
            return $newId > 0 ? $newId : false;
        } catch (Exception $e) { error_log('saveManufacturer: '.$e->getMessage()); return false; }
    }

    /* ─────────────────────────────────────────────────────────────
       QUOTATION
    ───────────────────────────────────────────────────────────── */
    public function getAllQuotations(string $search = '', string $status = ''): array
    {
        try {
            $where = '1=1';
            if ($search !== '') {
                $s = addslashes($search);
                $where .= " AND (COALESCE(u.name, eq.user_name) LIKE '%$s%'
                             OR COALESCE(u.communication_email_id, eq.user_email) LIKE '%$s%'
                             OR COALESCE(u.company_name, eq.company_name) LIKE '%$s%'
                             OR eq.enquiry_quote_id = '$s')";
            }
            if ($status !== '') {
                $st = addslashes($status);
                $where .= " AND eq.enquiry_status = '$st'";
            }
            return $this->db->select(
                "SELECT eq.enquiry_quote_id, eq.user_id, eq.user_address_id, eq.billing_address_id,
                        eq.enquiry_date, eq.enquiry_status, eq.customer_order_no, eq.customer_supplier_no,
                        eq.enquiry_vat_amt, eq.enquiry_shipping_amt, eq.enquiry_total_amt,
                        eq.discount_percentage, eq.discount_amt, eq.vat_number,
                        COALESCE(u.name, eq.user_name) AS user_name,
                        COALESCE(u.communication_email_id, eq.user_email) AS user_email,
                        COALESCE(u.communication_mobile_num, eq.user_phone) AS user_phone,
                        COALESCE(u.company_name, eq.company_name) AS company_name,
                        COUNT(eqp.enquiry_quote_product_id) AS product_count
                 FROM tbl_enquiry_quote eq
                 LEFT JOIN tbl_user u ON u.user_id = eq.user_id AND eq.user_id > 0
                 LEFT JOIN tbl_enquiry_quote_product eqp ON eqp.enquiry_quote_id = eq.enquiry_quote_id
                 WHERE $where
                 GROUP BY eq.enquiry_quote_id
                 ORDER BY eq.enquiry_date DESC"
            );
        } catch (Exception $e) { error_log('getAllQuotations: '.$e->getMessage()); return []; }
    }

    public function getQuotationById(int $id): ?object
    {
        try {
            $rows = $this->db->select(
                "SELECT eq.*,
                        COALESCE(u.name, eq.user_name) AS user_name_resolved,
                        COALESCE(u.communication_email_id, eq.user_email) AS user_email_resolved,
                        COALESCE(u.communication_mobile_num, eq.user_phone) AS user_phone_resolved,
                        COALESCE(u.company_name, eq.company_name) AS company_name_resolved
                 FROM tbl_enquiry_quote eq
                 LEFT JOIN tbl_user u ON u.user_id = eq.user_id AND eq.user_id > 0
                 WHERE eq.enquiry_quote_id=$id LIMIT 1"
            );
            return $rows[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function getQuotationProducts(int $id): array
    {
        try {
            return $this->db->select(
                "SELECT eqp.*, p.product_name, p.product_code, pc.product_category_name
                 FROM tbl_enquiry_quote_product eqp
                 LEFT JOIN tbl_product p  ON p.product_id  = eqp.product_id
                 LEFT JOIN tbl_product_category pc ON pc.product_category_id = eqp.product_category_id
                 WHERE eqp.enquiry_quote_id = $id
                 ORDER BY eqp.enquiry_quote_product_id ASC"
            );
        } catch (Exception $e) { return []; }
    }

    public function getAllQuotationProductsIndexed(): array
    {
        try {
            $rows    = $this->db->select(
                "SELECT eqp.*, p.product_name, p.product_code, pc.product_category_name
                 FROM tbl_enquiry_quote_product eqp
                 LEFT JOIN tbl_product p ON p.product_id = eqp.product_id
                 LEFT JOIN tbl_product_category pc ON pc.product_category_id = eqp.product_category_id
                 ORDER BY eqp.enquiry_quote_id ASC, eqp.enquiry_quote_product_id ASC"
            );
            $indexed = [];
            foreach ($rows as $r) {
                $qid = (int)(float)($r->ENQUIRY_QUOTE_ID ?? 0);
                $indexed[$qid][] = $r;
            }
            return $indexed;
        } catch (Exception $e) { error_log('getAllQuotationProductsIndexed: '.$e->getMessage()); return []; }
    }

    public function saveQuotation(array $d): int|false
    {
        try {
            $id        = (int)($d['enquiry_quote_id']     ?? 0);
            $userId    = (int)($d['user_id']              ?? 0);
            $addrId    = (int)($d['user_address_id']      ?? 0);
            $bilAddrId = (int)($d['billing_address_id']   ?? 0);
            $status    = in_array($d['enquiry_status'] ?? '', ['Quotation Pending','Quotation Sent','Order Generated','Order Completed'])
                         ? $d['enquiry_status'] : 'Quotation Pending';
            $vatAmt   = (float)($d['enquiry_vat_amt']      ?? 0);
            $shipAmt  = (float)($d['enquiry_shipping_amt'] ?? 0);
            $totalAmt = (float)($d['enquiry_total_amt']    ?? 0);
            $discPct  = (float)($d['discount_percentage']  ?? 0);
            $discAmt  = (float)($d['discount_amt']         ?? 0);
            $custOrd  = addslashes($d['customer_order_no']    ?? '');
            $custSup  = addslashes($d['customer_supplier_no'] ?? '');
            $vatNum   = addslashes(trim($d['vat_number'] ?? ''));
            $bilVal   = $bilAddrId > 0 ? $bilAddrId : ($addrId > 0 ? $addrId : 0);

            if ($id > 0) {
                $sql = "UPDATE tbl_enquiry_quote SET
                    user_id=$userId, user_address_id=$addrId, billing_address_id=$bilVal,
                    enquiry_status='$status',
                    enquiry_vat_amt=$vatAmt, enquiry_shipping_amt=$shipAmt, enquiry_total_amt=$totalAmt,
                    discount_percentage=$discPct, discount_amt=$discAmt,
                    vat_number='$vatNum',
                    customer_order_no='$custOrd', customer_supplier_no='$custSup'
                    WHERE enquiry_quote_id=$id";
                $this->db->update($sql);
                $this->logActivity('edit', 'tbl_enquiry_quote', $sql, null, ['id'=>$id]);
                return $id;
            } else {
                $sql = "INSERT INTO tbl_enquiry_quote(
                    user_id, user_address_id, billing_address_id, enquiry_status,
                    enquiry_vat_amt, enquiry_shipping_amt, enquiry_total_amt,
                    discount_percentage, discount_amt, vat_number,
                    customer_order_no, customer_supplier_no, order_id)
                    VALUES($userId, $addrId, $bilVal, '$status',
                    $vatAmt, $shipAmt, $totalAmt, $discPct, $discAmt, '$vatNum',
                    '$custOrd', '$custSup', 0)";
                $newId = (int)$this->db->insert($sql);
                if ($newId > 0) $this->logActivity('add', 'tbl_enquiry_quote', $sql, null, ['id'=>$newId]);
                return $newId > 0 ? $newId : false;
            }
        } catch (Exception $e) { error_log('saveQuotation: '.$e->getMessage()); return false; }
    }

    public function saveQuotationProducts(int $qid, array $products): void
    {
        try {
            $this->db->update("DELETE FROM tbl_enquiry_quote_product WHERE enquiry_quote_id=$qid");
            foreach ($products as $p) {
                $catId   = (int)($p['cat_id']   ?? 0);
                $prodId  = (int)($p['prod_id']  ?? 0);
                $qty     = (int)($p['qty']      ?? 0);
                $price   = (float)($p['price']  ?? 0);
                $discPct = (float)($p['disc_pct'] ?? 0);
                if ($prodId <= 0 || $qty <= 0) continue;
                $this->db->insert(
                    "INSERT INTO tbl_enquiry_quote_product
                     (enquiry_quote_id,product_category_id,product_id,product_quantity,product_amt,product_discount_pct)
                     VALUES($qid,$catId,$prodId,$qty,$price,$discPct)"
                );
            }
        } catch (Exception $e) { error_log('saveQuotationProducts: '.$e->getMessage()); }
    }

    public function deleteQuotation(int $id): bool
    {
        try {
            $this->db->update("DELETE FROM tbl_enquiry_quote WHERE enquiry_quote_id=$id");
            $this->logActivity('delete', 'tbl_enquiry_quote', '', null, ['id'=>$id]);
            return true;
        } catch (Exception $e) { return false; }
    }

    /* Returns set of enquiry_quote_ids that already have an order in tbl_user_order */
    public function getQuotationIdsWithOrders(): array
    {
        try {
            $rows = $this->db->select(
                "SELECT DISTINCT enquiry_quote_id FROM tbl_user_order
                 WHERE enquiry_quote_id IS NOT NULL AND enquiry_quote_id > 0"
            );
            $ids = [];
            foreach ($rows as $r) { $ids[] = (int)(float)($r->ENQUIRY_QUOTE_ID ?? 0); }
            return $ids;
        } catch (Exception $e) { return []; }
    }

    /* Creates tbl_user_order + items + first history row from a quotation.
       Returns the new user_order_id, or 0 on failure. */
    public function generateOrderFromQuotation(array $d): int
    {
        $qid        = (int)($d['enquiry_quote_id'] ?? 0);
        $userId     = (int)($d['user_id']          ?? 0);
        $addrId     = (int)($d['user_address_id']   ?? 0);
        $bilAddrId  = (int)($d['billing_address_id'] ?? $addrId);
        $payMode    = in_array($d['order_mode'] ?? '', ['Payment Gateway','Bank Transfer','Invoice'])
                      ? $d['order_mode'] : 'Invoice';
        $custPoId   = addslashes(trim($d['customer_po_id']       ?? ''));
        $custSupNo  = addslashes(trim($d['customer_supplier_no'] ?? ''));
        $totalProd  = (float)($d['order_total_amt']    ?? 0);
        $shipAmt    = (float)($d['shipping_amt']       ?? 0);
        $discAmt    = (float)($d['discount_amt']       ?? 0);
        $taxAmt     = (float)($d['tax_total_amount']   ?? 0);
        $finalAmt   = (float)($d['final_total_amt']    ?? 0);
        $adminUid   = (int)($d['changed_by_user_id']   ?? 0);
        $items      = $d['items'] ?? [];
        $year       = (int)date('Y');

        $orderStatus  = ($payMode === 'Invoice') ? 'Order Confirmed' : 'Order Pending';
        $payStatus    = ($payMode === 'Invoice') ? 'Not Required'    : 'Payment Pending';

        try {
            /* 1. Insert order with temp order_number */
            $newId = (int)$this->db->insert(
                "INSERT INTO tbl_user_order
                 (order_type, user_id, order_number, order_year,
                  customer_po_id, customer_supplier_no,
                  order_mode, order_status, payment_status,
                  order_total_amt, shipping_amt, discount_amt, tax_total_amount, final_total_amt,
                  user_address_id, billing_user_address_id, enquiry_quote_id)
                 VALUES('Order',$userId,'PENDING',$year,
                  '$custPoId','$custSupNo',
                  '$payMode','$orderStatus','$payStatus',
                  $totalProd,$shipAmt,$discAmt,$taxAmt,$finalAmt,
                  $addrId,$bilAddrId,$qid)"
            );
            if ($newId <= 0) return 0;

            /* 2. Set proper order number */
            $orderNo = 'ORD-'.$year.'-'.str_pad((string)$newId, 6, '0', STR_PAD_LEFT);
            $this->db->update("UPDATE tbl_user_order SET order_number='$orderNo' WHERE user_order_id=$newId");

            /* 3. Insert order items */
            foreach ($items as $item) {
                $catId   = (int)($item['product_category_id'] ?? 0);
                $prodId  = (int)($item['product_id']          ?? 0);
                $qty     = (float)($item['product_quantity']  ?? 1);
                $unitAmt = (float)($item['product_amt']       ?? 0);
                $discPct = (float)($item['product_discount_pct'] ?? 0);
                $discIt  = round($unitAmt * $discPct / 100, 2);
                $taxPct  = 0;
                $taxIt   = 0;
                $finalIt = round(($unitAmt - $discIt) * $qty, 2);
                $discItT = round($discIt * $qty, 2);
                if ($prodId <= 0 || $catId <= 0) continue;
                $this->db->insert(
                    "INSERT INTO tbl_user_order_item
                     (user_order_id, product_category_id, product_id, quantity,
                      product_amt, discount_percentage, discount_amt,
                      tax_percentage, tax_amt, final_amt, item_status, order_type)
                     VALUES($newId,$catId,$prodId,$qty,
                      $unitAmt,$discPct,$discItT,
                      $taxPct,$taxIt,$finalIt,'Active','Order')"
                );
            }

            /* 4. Insert first history row */
            $histRemark = addslashes('Order generated from quotation QT-'.str_pad((string)$qid, 6, '0', STR_PAD_LEFT).' via '.$payMode);
            $this->db->insert(
                "INSERT INTO tbl_user_order_history
                 (user_order_id, history_type, history_order_status, history_payment_status,
                  history_remarks, changed_by_user_id)
                 VALUES($newId,'Order','$orderStatus','$payStatus','$histRemark',$adminUid)"
            );

            /* 5. Mark quotation as Order Generated */
            $this->db->update("UPDATE tbl_enquiry_quote SET enquiry_status='Order Generated' WHERE enquiry_quote_id=$qid");

            $this->logActivity('add', 'tbl_user_order', '', null, ['order_id'=>$newId,'quote_id'=>$qid]);
            return $newId;
        } catch (Exception $e) {
            error_log('generateOrderFromQuotation: '.$e->getMessage());
            return 0;
        }
    }

    public function updateQuotationStatus(int $id, string $status, string $remark = ''): bool
    {
        try {
            $valid = ['Quotation Pending','Quotation Sent','Order Generated','Order Completed','Quotation Cancel'];
            if (!in_array($status, $valid)) return false;
            $s = addslashes($status);
            $r = addslashes(trim($remark));
            $remarkSql = $r !== '' ? ", remark='$r'" : '';
            $this->db->update("UPDATE tbl_enquiry_quote SET enquiry_status='$s'$remarkSql WHERE enquiry_quote_id=$id");
            $this->logActivity('edit','tbl_enquiry_quote','',null,['id'=>$id,'status'=>$status]);
            return true;
        } catch (Exception $e) { return false; }
    }

    public function getCustomersForQuote(): array
    {
        try {
            return $this->db->select(
                "SELECT u.user_id,
                        u.name AS user_name,
                        u.communication_email_id AS user_email,
                        IFNULL(u.communication_mobile_num,'') AS user_phone,
                        IFNULL(u.communication_mobile_num_isd, 91) AS user_phone_isd,
                        IFNULL(u.company_name,'') AS company_name,
                        IFNULL(u.designation,'') AS designation
                 FROM tbl_user u WHERE u.user_type_id = 2 ORDER BY u.name ASC"
            );
        } catch (Exception $e) { return []; }
    }

    /* Quick-create a customer from the quotation modal (no password required from user) */
    public function quickCreateCustomer(array $d): int|false
    {
        try {
            $name    = addslashes(trim($d['name']    ?? ''));
            $email   = addslashes(strtolower(trim($d['communication_email_id'] ?? '')));
            $mobile  = addslashes(trim($d['communication_mobile_num']     ?? ''));
            $isd     = (int)($d['communication_mobile_num_isd'] ?? 91);
            $company = addslashes(trim($d['company_name'] ?? ''));
            $desig   = addslashes(trim($d['designation']  ?? ''));

            if ($name === '' || $email === '') return false;

            $dup = $this->db->select(
                "SELECT COUNT(*) AS C FROM tbl_user WHERE communication_email_id='".$email."'"
            );
            if ((int)($dup[0]->C ?? 0) > 0) return -1; // duplicate email

            $password = bin2hex(random_bytes(8));
            $hash     = addslashes(password_hash($password, PASSWORD_DEFAULT));
            $actKey   = bin2hex(random_bytes(16));

            $sql = "INSERT INTO tbl_user
                 (user_type_id, name, communication_email_id, erp_password,
                  communication_mobile_num_isd, communication_mobile_num,
                  company_name, designation,
                  account_activation_flag, random_activation_key, verified_flag, is_pwd_updated)
                 VALUES(2, '".$name."', '".$email."', '".$hash."',
                        ".$isd.", '".$mobile."', '".$company."', '".$desig."',
                        '1', '".$actKey."', 'Yes', 0)";
            $newId = (int)$this->db->insert($sql);
            if ($newId > 0) {
                $this->logActivity('add', 'tbl_user', $sql, null,
                    ['source'=>'quotation_quick_create','user_type_id'=>2,'name'=>$name,'email'=>$email]
                );
            }
            return $newId > 0 ? $newId : false;
        } catch (Exception $e) { error_log('quickCreateCustomer: '.$e->getMessage()); return false; }
    }

    /* Get all addresses for a user — used by quotation address selector */
    public function getUserAddressesForQuote(int $userId): array
    {
        try {
            return $this->db->select(
                "SELECT user_address_id, user_id, label,
                        user_name, company_name,
                        address, address_line_one, address_line_two, landmark,
                        city, state, zip, country,
                        delivery_phone_no, mobile_country_code,
                        recipient_name, recipient_email, recipient_contact
                 FROM tbl_user_address
                 WHERE user_id = ".$userId."
                 ORDER BY user_address_id ASC"
            );
        } catch (Exception $e) { error_log('getUserAddressesForQuote: '.$e->getMessage()); return []; }
    }

    /* Save a new address record to tbl_user_address */
    public function saveUserAddress(array $d): int|false
    {
        try {
            $userId    = (int)($d['user_id'] ?? 0);
            if ($userId <= 0) return false;
            $label     = in_array($d['label'] ?? '', ['Home','Office','Other']) ? $d['label'] : 'Home';
            $userName  = addslashes(trim($d['addr_user_name']   ?? ''));
            $company   = addslashes(trim($d['addr_company_name']?? ''));
            $phone     = addslashes(trim($d['delivery_phone_no']?? ''));
            $mcc       = (int)($d['mobile_country_code'] ?? 91);
            $address   = addslashes(trim($d['address']          ?? ''));
            $line1     = addslashes(trim($d['address_line_one'] ?? ''));
            $line2     = addslashes(trim($d['address_line_two'] ?? ''));
            $landmark  = addslashes(trim($d['landmark']         ?? ''));
            $city      = addslashes(trim($d['city']             ?? ''));
            $state     = addslashes(trim($d['state']            ?? ''));
            $zip       = addslashes(trim($d['zip']              ?? ''));
            $countryId = (int)($d['country_id'] ?? 0);
            $country   = addslashes(trim($d['country']          ?? ''));
            $euVat     = addslashes(trim($d['eu_vat']           ?? ''));
            $recName   = addslashes(trim($d['recipient_name']    ?? ''));
            $recEmail  = addslashes(trim($d['recipient_email']   ?? ''));
            $recPhone  = addslashes(trim($d['recipient_contact'] ?? ''));

            $sql = "INSERT INTO tbl_user_address
                 (user_id, label, user_name, company_name, delivery_phone_no, mobile_country_code,
                  address, address_line_one, address_line_two, landmark,
                  city, state, zip, country_id, country, eu_vat,
                  recipient_name, recipient_email, recipient_contact)
                 VALUES($userId, '$label', '$userName', '$company', '$phone', $mcc,
                        '$address', '$line1', '$line2', '$landmark',
                        '$city', '$state', '$zip', $countryId, '$country', '$euVat',
                        '$recName', '$recEmail', '$recPhone')";
            $newId = (int)$this->db->insert($sql);
            return $newId > 0 ? $newId : false;
        } catch (Exception $e) { error_log('saveUserAddress: '.$e->getMessage()); return false; }
    }

    public function deleteManufacturer(int $id): bool
    {
        try {
            $oldRow = $this->getManufacturerById($id);
            $sql = "DELETE FROM tbl_manufacturers WHERE manufacturer_id=".$id." LIMIT 1";
            $this->db->update($sql);
            $this->logActivity('delete', 'tbl_manufacturers', $sql,
                $oldRow !== null ? (array)$oldRow : null,
                null
            );
            return true;
        } catch (Exception $e) { error_log('deleteManufacturer: '.$e->getMessage()); return false; }
    }

    /* ═══════════════════════════════════════════════════
       USER ORDERS (tbl_user_order)
    ═══════════════════════════════════════════════════ */

    public function getAllUserOrders(array $f = []): array
    {
        try {
            $where = '1=1';
            if (!empty($f['search'])) {
                $s = addslashes($f['search']);
                $where .= " AND (o.order_number LIKE '%$s%'
                             OR COALESCE(u.name,'') LIKE '%$s%'
                             OR COALESCE(u.communication_email_id,'') LIKE '%$s%'
                             OR COALESCE(u.company_name,'') LIKE '%$s%')";
            }
            if (!empty($f['order_status']))   $where .= " AND o.order_status='".addslashes($f['order_status'])."'";
            if (!empty($f['payment_status'])) $where .= " AND o.payment_status='".addslashes($f['payment_status'])."'";
            if (!empty($f['order_mode']))     $where .= " AND o.order_mode='".addslashes($f['order_mode'])."'";
            if (!empty($f['source'])) {
                if ($f['source'] === 'quotation') $where .= " AND o.enquiry_quote_id IS NOT NULL AND o.enquiry_quote_id > 0";
                elseif ($f['source'] === 'direct') $where .= " AND (o.enquiry_quote_id IS NULL OR o.enquiry_quote_id = 0)";
            }
            if (!empty($f['date_from'])) $where .= " AND DATE(o.order_date) >= '".addslashes($f['date_from'])."'";
            if (!empty($f['date_to']))   $where .= " AND DATE(o.order_date) <= '".addslashes($f['date_to'])."'";
            return $this->db->select(
                "SELECT o.*,
                        COALESCE(u.name, '') AS cust_name,
                        COALESCE(u.communication_email_id, '') AS cust_email,
                        COALESCE(u.communication_mobile_num, '') AS cust_phone,
                        COALESCE(u.company_name, '') AS cust_company,
                        eq.enquiry_quote_id AS quote_id,
                        (SELECT COUNT(*) FROM tbl_user_order_item i WHERE i.user_order_id = o.user_order_id AND i.item_status='Active') AS item_count
                 FROM tbl_user_order o
                 LEFT JOIN tbl_user u ON u.user_id = o.user_id
                 LEFT JOIN tbl_enquiry_quote eq ON eq.enquiry_quote_id = o.enquiry_quote_id
                 WHERE $where
                 ORDER BY o.user_order_id DESC"
            );
        } catch (Exception $e) { error_log('getAllUserOrders: '.$e->getMessage()); return []; }
    }

    public function getUserOrderById(int $id): ?object
    {
        try {
            $rows = $this->db->select(
                "SELECT o.*,
                        COALESCE(u.name,'') AS cust_name,
                        COALESCE(u.communication_email_id,'') AS cust_email,
                        COALESCE(u.communication_mobile_num,'') AS cust_phone,
                        COALESCE(u.company_name,'') AS cust_company,
                        a.company_name  AS addr_company,  a.recipient_name,
                        a.address,      a.address_line_one,  a.address_line_two,
                        a.city,         a.state,   a.zip,    a.country,
                        a.delivery_phone_no, a.recipient_email,
                        b.company_name  AS bil_company,   b.recipient_name  AS bil_recipient_name,
                        b.address       AS bil_address,
                        b.address_line_one AS bil_line1,  b.address_line_two AS bil_line2,
                        b.city          AS bil_city,      b.state AS bil_state,
                        b.zip           AS bil_zip,       b.country AS bil_country,
                        b.delivery_phone_no AS bil_phone, b.recipient_email AS bil_email,
                        cc.courier_company_name, cc.tracking_url AS courier_tracking_url
                 FROM tbl_user_order o
                 LEFT JOIN tbl_user u         ON u.user_id          = o.user_id
                 LEFT JOIN tbl_user_address a  ON a.user_address_id  = o.user_address_id
                 LEFT JOIN tbl_user_address b  ON b.user_address_id  = o.billing_user_address_id
                 LEFT JOIN tbl_courier_company cc ON cc.courier_company_id = o.courier_company_id
                 WHERE o.user_order_id = $id LIMIT 1"
            );
            return $rows[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    public function getUserOrderItems(int $orderId): array
    {
        try {
            return $this->db->select(
                "SELECT i.*, p.product_name, p.product_code, pc.product_category_name
                 FROM tbl_user_order_item i
                 LEFT JOIN tbl_product p  ON p.product_id  = i.product_id
                 LEFT JOIN tbl_product_category pc ON pc.product_category_id = i.product_category_id
                 WHERE i.user_order_id = $orderId AND i.item_status = 'Active'
                 ORDER BY i.user_order_item_id ASC"
            );
        } catch (Exception $e) { return []; }
    }

    public function getUserOrderHistory(int $orderId): array
    {
        try {
            return $this->db->select(
                "SELECT h.*, COALESCE(u.name,'System') AS changed_by_name
                 FROM tbl_user_order_history h
                 LEFT JOIN tbl_user u ON u.user_id = h.changed_by_user_id
                 WHERE h.user_order_id = $orderId
                 ORDER BY h.user_order_history_id ASC"
            );
        } catch (Exception $e) { return []; }
    }

    public function getCourierCompanies(): array
    {
        try {
            return $this->db->select(
                "SELECT courier_company_id, courier_company_name, phone, email, tracking_url
                 FROM tbl_courier_company WHERE status='Active'
                 ORDER BY courier_company_name ASC"
            );
        } catch (Exception $e) { return []; }
    }

    public function getCourierCompanyById(int $id): ?object
    {
        if ($id <= 0) return null;
        try {
            $rows = $this->db->select(
                "SELECT courier_company_id, courier_company_name, tracking_url
                 FROM tbl_courier_company WHERE courier_company_id=$id LIMIT 1"
            );
            return $rows[0] ?? null;
        } catch (Exception $e) { return null; }
    }

    /**
     * @param array $extra  Optional dispatch data:
     *                      courier_company_id, dispatch_courier_tracking_id
     */
    public function updateUserOrderStatus(int $id, string $orderStatus, string $payStatus, string $remark, int $adminUid, array $extra = []): bool
    {
        $validOs = ['Order Pending','Order Confirmed','Order Packed','Order Dispatch','Order In Transit','Order Delivered','Order Cancelled'];
        $validPs = ['Payment Pending','Payment Successful','Payment Failed','Refund Initiated','Refund Completed','Not Required'];
        if (!in_array($orderStatus, $validOs) || !in_array($payStatus, $validPs)) return false;
        try {
            $os = addslashes($orderStatus);
            $ps = addslashes($payStatus);

            /* ── Dispatch extras ── */
            $extraSql = '';
            if ($orderStatus === 'Order Dispatch') {
                $courierId = (int)($extra['courier_company_id'] ?? 0);
                $trackId   = addslashes(trim($extra['dispatch_courier_tracking_id'] ?? ''));
                $extraSql .= ', dispatch_date=NOW()';
                if ($courierId > 0) $extraSql .= ", courier_company_id=$courierId";
                if ($trackId !== '') $extraSql .= ", dispatch_courier_tracking_id='$trackId'";
            }

            $this->db->update(
                "UPDATE tbl_user_order SET order_status='$os', payment_status='$ps'$extraSql WHERE user_order_id=$id"
            );
            $r = addslashes(trim($remark));
            $this->db->insert(
                "INSERT INTO tbl_user_order_history
                 (user_order_id, history_type, history_order_status, history_payment_status, history_remarks, changed_by_user_id)
                 VALUES($id,'Order','$os','$ps','$r',$adminUid)"
            );
            $this->logActivity('edit','tbl_user_order','',null,['order_id'=>$id,'order_status'=>$orderStatus,'payment_status'=>$payStatus]);
            return true;
        } catch (Exception $e) { error_log('updateUserOrderStatus: '.$e->getMessage()); return false; }
    }

    public function createDirectOrder(array $d): int
    {
        $userId    = (int)($d['user_id']              ?? 0);
        $addrId    = (int)($d['user_address_id']       ?? 0);
        $bilAddrId = (int)($d['billing_address_id']    ?? $addrId);
        $payMode   = in_array($d['order_mode'] ?? '', ['Payment Gateway','Bank Transfer','Invoice'])
                     ? $d['order_mode'] : 'Invoice';
        $custPoId  = addslashes(trim($d['customer_po_id']       ?? ''));
        $custSupNo = addslashes(trim($d['customer_supplier_no'] ?? ''));
        $shipAmt   = (float)($d['shipping_amt']      ?? 0);
        $discAmt   = (float)($d['discount_amt']      ?? 0);
        $taxAmt    = (float)($d['tax_amt']            ?? 0);
        $adminUid  = (int)($d['changed_by_user_id']  ?? 0);
        $items     = $d['items'] ?? [];
        $year      = (int)date('Y');

        $orderStatus = ($payMode === 'Invoice') ? 'Order Confirmed' : 'Order Pending';
        $payStatus   = ($payMode === 'Invoice') ? 'Not Required'    : 'Payment Pending';

        $totalProd = 0;
        foreach ($items as $item) {
            $qty     = (float)($item['qty']      ?? 1);
            $unitAmt = (float)($item['unit_amt'] ?? 0);
            $discPct = (float)($item['disc_pct'] ?? 0);
            $totalProd += round($unitAmt * (1 - $discPct / 100) * $qty, 2);
        }
        $finalAmt = round($totalProd + $shipAmt - $discAmt + $taxAmt, 2);

        try {
            $newId = (int)$this->db->insert(
                "INSERT INTO tbl_user_order
                 (order_type, user_id, order_number, order_year,
                  customer_po_id, customer_supplier_no,
                  order_mode, order_status, payment_status,
                  order_total_amt, shipping_amt, discount_amt, tax_total_amount, final_total_amt,
                  user_address_id, billing_user_address_id)
                 VALUES('Order',$userId,'PENDING',$year,
                  '$custPoId','$custSupNo',
                  '$payMode','$orderStatus','$payStatus',
                  $totalProd,$shipAmt,$discAmt,$taxAmt,$finalAmt,
                  $addrId,$bilAddrId)"
            );
            if ($newId <= 0) return 0;

            $orderNo = 'ORD-'.$year.'-'.str_pad((string)$newId, 6, '0', STR_PAD_LEFT);
            $this->db->update("UPDATE tbl_user_order SET order_number='$orderNo' WHERE user_order_id=$newId");

            foreach ($items as $item) {
                $catId   = (int)($item['cat_id']   ?? 0);
                $prodId  = (int)($item['prod_id']  ?? 0);
                $qty     = (float)($item['qty']     ?? 1);
                $unitAmt = (float)($item['unit_amt'] ?? 0);
                $discPct = (float)($item['disc_pct'] ?? 0);
                $taxPct  = (float)($item['tax_pct']  ?? 0);
                $discIt  = round($unitAmt * $discPct / 100, 2);
                $taxIt   = round(($unitAmt - $discIt) * $taxPct / 100, 2);
                $finalIt = round(($unitAmt - $discIt + $taxIt) * $qty, 2);
                if ($prodId <= 0 || $catId <= 0) continue;
                $this->db->insert(
                    "INSERT INTO tbl_user_order_item
                     (user_order_id, product_category_id, product_id, quantity,
                      product_amt, discount_percentage, discount_amt,
                      tax_percentage, tax_amt, final_amt, item_status, order_type)
                     VALUES($newId,$catId,$prodId,$qty,
                      $unitAmt,$discPct,".round($discIt*$qty,2).",
                      $taxPct,$taxIt,$finalIt,'Active','Order')"
                );
            }

            $this->db->insert(
                "INSERT INTO tbl_user_order_history
                 (user_order_id, history_type, history_order_status, history_payment_status,
                  history_remarks, changed_by_user_id)
                 VALUES($newId,'Order','$orderStatus','$payStatus','Order created directly by admin',$adminUid)"
            );

            $this->logActivity('add','tbl_user_order','',null,['order_id'=>$newId,'source'=>'direct']);
            return $newId;
        } catch (Exception $e) { error_log('createDirectOrder: '.$e->getMessage()); return 0; }
    }

    public function updateDirectOrder(array $d): bool
    {
        $orderId   = (int)($d['order_id']              ?? 0);
        $addrId    = (int)($d['user_address_id']        ?? 0);
        $bilAddrId = (int)($d['billing_address_id']     ?? $addrId);
        $payMode   = in_array($d['order_mode'] ?? '', ['Payment Gateway','Bank Transfer','Invoice'])
                     ? $d['order_mode'] : 'Invoice';
        $custPoId  = addslashes(trim($d['customer_po_id']       ?? ''));
        $custSupNo = addslashes(trim($d['customer_supplier_no'] ?? ''));
        $vatNum    = addslashes(trim($d['vat_number']           ?? ''));
        $shipAmt   = (float)($d['shipping_amt']     ?? 0);
        $discAmt   = (float)($d['discount_amt']     ?? 0);
        $taxAmt    = (float)($d['tax_amt']           ?? 0);
        $adminUid  = (int)($d['changed_by_user_id'] ?? 0);
        $items     = $d['items'] ?? [];
        if ($orderId <= 0) return false;

        /* Re-calculate totals server-side */
        $totalProd = 0;
        foreach ($items as $item) {
            $qty     = (float)($item['qty']      ?? 1);
            $unitAmt = (float)($item['unit_amt'] ?? 0);
            $discPct = (float)($item['disc_pct'] ?? 0);
            $totalProd += round($unitAmt * (1 - $discPct / 100) * $qty, 2);
        }
        $finalAmt = round($totalProd + $shipAmt - $discAmt + $taxAmt, 2);

        try {
            /* Fetch current status for history */
            $existing = $this->db->select(
                "SELECT order_status, payment_status FROM tbl_user_order WHERE user_order_id=$orderId LIMIT 1"
            );
            $curOs = addslashes((string)($existing[0]->ORDER_STATUS   ?? 'Order Pending'));
            $curPs = addslashes((string)($existing[0]->PAYMENT_STATUS ?? 'Payment Pending'));

            /* Update order header */
            $vatClause = $vatNum !== '' ? ", vat_number='$vatNum'" : '';
            $this->db->update(
                "UPDATE tbl_user_order SET
                 user_address_id=$addrId,
                 billing_user_address_id=$bilAddrId,
                 order_mode='$payMode',
                 customer_po_id='$custPoId',
                 customer_supplier_no='$custSupNo',
                 shipping_amt=$shipAmt,
                 discount_amt=$discAmt,
                 tax_total_amount=$taxAmt,
                 order_total_amt=$totalProd,
                 final_total_amt=$finalAmt
                 $vatClause
                 WHERE user_order_id=$orderId"
            );

            /* Replace line items */
            $this->db->update("DELETE FROM tbl_user_order_item WHERE user_order_id=$orderId");
            foreach ($items as $item) {
                $catId   = (int)($item['cat_id']    ?? 0);
                $prodId  = (int)($item['prod_id']   ?? 0);
                $qty     = (float)($item['qty']      ?? 1);
                $unitAmt = (float)($item['unit_amt'] ?? 0);
                $discPct = (float)($item['disc_pct'] ?? 0);
                $taxPct  = (float)($item['tax_pct']  ?? 0);
                $discIt  = round($unitAmt * $discPct / 100, 2);
                $taxIt   = round(($unitAmt - $discIt) * $taxPct / 100, 2);
                $finalIt = round(($unitAmt - $discIt + $taxIt) * $qty, 2);
                if ($prodId <= 0 || $catId <= 0) continue;
                $this->db->insert(
                    "INSERT INTO tbl_user_order_item
                     (user_order_id, product_category_id, product_id, quantity,
                      product_amt, discount_percentage, discount_amt,
                      tax_percentage, tax_amt, final_amt, item_status, order_type)
                     VALUES($orderId,$catId,$prodId,$qty,
                      $unitAmt,$discPct,".round($discIt * $qty, 2).",
                      $taxPct,$taxIt,$finalIt,'Active','Order')"
                );
            }

            /* History row */
            $this->db->insert(
                "INSERT INTO tbl_user_order_history
                 (user_order_id, history_type, history_order_status, history_payment_status,
                  history_remarks, changed_by_user_id)
                 VALUES($orderId,'Order','$curOs','$curPs','Order updated by admin',$adminUid)"
            );

            $this->logActivity('edit', 'tbl_user_order', '', null, ['order_id' => $orderId, 'source' => 'direct_edit']);
            return true;
        } catch (Exception $e) {
            error_log('updateDirectOrder: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteUserOrder(int $id): bool
    {
        try {
            $this->db->update("DELETE FROM tbl_user_order WHERE user_order_id=$id");
            $this->logActivity('delete','tbl_user_order','',null,['order_id'=>$id]);
            return true;
        } catch (Exception $e) { return false; }
    }

    /* ═══════════════════════════════════════════════════════════
       REFUND MANAGEMENT
    ═══════════════════════════════════════════════════════════ */

    /**
     * Aggregate KPI counts and amounts for the refund dashboard tiles.
     * Covers Return & Refund orders plus any Order with a refund payment_status.
     */
    public function getRefundStats(): ?object
    {
        try {
            $rows = $this->db->select(
                "SELECT
                    COUNT(*)   AS total,
                    SUM(CASE WHEN order_return_replacement_status = 'Return Requested'         THEN 1 ELSE 0 END) AS pending_approval,
                    SUM(CASE WHEN order_return_replacement_status IN
                             ('Return Request Approved','Pickup Scheduled',
                              'Pickup Completed','QC Approved')                               THEN 1 ELSE 0 END) AS in_process,
                    SUM(CASE WHEN payment_status = 'Refund Initiated'                         THEN 1 ELSE 0 END) AS refund_initiated,
                    SUM(CASE WHEN order_return_replacement_status = 'Return Completed'
                              OR  payment_status                  = 'Refund Completed'        THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN order_return_replacement_status = 'Return Request Cancelled' THEN 1 ELSE 0 END) AS rejected,
                    COALESCE(SUM(final_total_amt),    0) AS total_request_value,
                    COALESCE(SUM(CASE WHEN order_return_replacement_status = 'Return Completed'
                                       OR payment_status = 'Refund Completed'
                                      THEN final_total_amt ELSE 0 END), 0) AS completed_amt,
                    COALESCE(SUM(CASE WHEN payment_status = 'Refund Initiated'
                                      THEN final_total_amt ELSE 0 END), 0) AS initiated_amt,
                    COALESCE(SUM(return_handling_fee), 0) AS total_handling_fees
                 FROM tbl_user_order
                 WHERE order_type = 'Return & Refund'"
            );
            return $rows[0] ?? null;
        } catch (Exception $e) {
            error_log('getRefundStats: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Full refund report — Return & Refund orders joined to original order + customer.
     * Sorted by urgency: pending first, then in-process, then completed/cancelled last.
     */
    public function getRefundReport(array $f = []): array
    {
        $where = "r.order_type = 'Return & Refund'";

        if (!empty($f['search'])) {
            $s = addslashes($f['search']);
            $where .= " AND (r.order_number LIKE '%$s%'
                          OR COALESCE(orig.order_number,'') LIKE '%$s%'
                          OR COALESCE(u.name,'') LIKE '%$s%'
                          OR COALESCE(u.communication_email_id,'') LIKE '%$s%'
                          OR COALESCE(u.company_name,'') LIKE '%$s%')";
        }
        if (!empty($f['return_status']))  $where .= " AND r.order_return_replacement_status='".addslashes($f['return_status'])."'";
        if (!empty($f['payment_status'])) $where .= " AND r.payment_status='".addslashes($f['payment_status'])."'";
        if (!empty($f['date_from']))      $where .= " AND DATE(r.order_date) >= '".addslashes($f['date_from'])."'";
        if (!empty($f['date_to']))        $where .= " AND DATE(r.order_date) <= '".addslashes($f['date_to'])."'";
        if (!empty($f['pending_only']))   $where .= " AND r.order_return_replacement_status = 'Return Requested'";

        try {
            return $this->db->select(
                "SELECT
                    r.user_order_id,
                    r.order_number,
                    r.order_date,
                    r.order_return_replacement_status   AS return_status,
                    r.payment_status,
                    r.final_total_amt,
                    r.return_handling_fee,
                    r.user_return_reason,
                    r.admin_return_reject_reason,
                    r.user_order_return_id,
                    r.order_total_amt,
                    r.tax_total_amount,
                    r.shipping_amt,
                    r.discount_amt,
                    COALESCE(u.name,'')                     AS cust_name,
                    COALESCE(u.communication_email_id,'')   AS cust_email,
                    COALESCE(u.company_name,'')             AS cust_company,
                    COALESCE(u.communication_mobile_num,'') AS cust_phone,
                    COALESCE(orig.order_number,'')          AS orig_order_number,
                    orig.order_date                         AS orig_order_date,
                    COALESCE(orig.final_total_amt,0)        AS orig_order_amt,
                    DATEDIFF(NOW(), r.order_date)           AS age_days,
                    (SELECT COUNT(*) FROM tbl_user_order_item i
                     WHERE i.user_order_id = r.user_order_id
                       AND i.item_status   = 'Active')      AS item_count
                 FROM tbl_user_order r
                 LEFT JOIN tbl_user u
                        ON u.user_id = r.user_id
                 LEFT JOIN tbl_user_order orig
                        ON orig.user_order_id = r.user_order_return_id
                 WHERE {$where}
                 ORDER BY
                     CASE r.order_return_replacement_status
                         WHEN 'Return Requested'         THEN 1
                         WHEN 'Return Request Approved'  THEN 2
                         WHEN 'Pickup Scheduled'         THEN 3
                         WHEN 'Pickup Completed'         THEN 4
                         WHEN 'QC Approved'              THEN 5
                         WHEN 'Return Completed'         THEN 6
                         WHEN 'Return Request Cancelled' THEN 7
                         ELSE 8
                     END,
                     r.order_date DESC"
            );
        } catch (Exception $e) {
            error_log('getRefundReport: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update return/refund status and log history.
     */
    public function updateReturnStatus(
        int    $orderId,
        string $returnStatus,
        string $payStatus,
        string $remark,
        string $rejectReason,
        int    $adminUid
    ): bool {
        try {
            $rs = addslashes($returnStatus);
            $ps = addslashes($payStatus);
            $rm = addslashes($remark);
            $rj = addslashes($rejectReason);

            $rejectClause = $rejectReason !== ''
                ? ", admin_return_reject_reason='$rj'"
                : '';

            $this->db->update(
                "UPDATE tbl_user_order SET
                 order_return_replacement_status = '$rs',
                 payment_status                  = '$ps'
                 {$rejectClause}
                 WHERE user_order_id = {$orderId}"
            );

            $this->db->insert(
                "INSERT INTO tbl_user_order_history
                 (user_order_id, history_type,
                  history_order_return_replacement_status,
                  history_payment_status, history_remarks, changed_by_user_id)
                 VALUES({$orderId},'Return & Refund','$rs','$ps','$rm',{$adminUid})"
            );

            $this->logActivity('edit', 'tbl_user_order', '', null,
                ['order_id' => $orderId, 'return_status' => $returnStatus]);
            return true;
        } catch (Exception $e) {
            error_log('updateReturnStatus: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Full detail for a single refund — order header, items, history.
     * Used by the AJAX modal.
     */
    public function getRefundOrderDetail(int $id): array
    {
        try {
            $order = $this->db->select(
                "SELECT r.*,
                        COALESCE(u.name,'')                     AS cust_name,
                        COALESCE(u.communication_email_id,'')   AS cust_email,
                        COALESCE(u.company_name,'')             AS cust_company,
                        COALESCE(u.communication_mobile_num,'') AS cust_phone,
                        COALESCE(orig.order_number,'')          AS orig_order_number,
                        orig.order_date                         AS orig_order_date,
                        COALESCE(orig.final_total_amt,0)        AS orig_order_amt
                 FROM tbl_user_order r
                 LEFT JOIN tbl_user u         ON u.user_id          = r.user_id
                 LEFT JOIN tbl_user_order orig ON orig.user_order_id = r.user_order_return_id
                 WHERE r.user_order_id = {$id}
                   AND r.order_type   = 'Return & Refund'
                 LIMIT 1"
            );
            if (empty($order)) return [];

            $items = $this->db->select(
                "SELECT i.*, p.product_name, p.product_code, pc.product_category_name
                 FROM tbl_user_order_item i
                 LEFT JOIN tbl_product p          ON p.product_id          = i.product_id
                 LEFT JOIN tbl_product_category pc ON pc.product_category_id = i.product_category_id
                 WHERE i.user_order_id = {$id}
                   AND i.item_status   = 'Active'
                 ORDER BY i.user_order_item_id ASC"
            );

            $history = $this->db->select(
                "SELECT h.*, COALESCE(u.name,'System') AS changed_by
                 FROM tbl_user_order_history h
                 LEFT JOIN tbl_user u ON u.user_id = h.changed_by_user_id
                 WHERE h.user_order_id = {$id}
                   AND h.history_type  = 'Return & Refund'
                 ORDER BY h.created_at ASC"
            );

            return ['order' => $order[0], 'items' => $items, 'history' => $history];
        } catch (Exception $e) {
            error_log('getRefundOrderDetail: ' . $e->getMessage());
            return [];
        }
    }

    /* ═══════════════════════════════════════════════════════════
       OPEN INVOICES / PAYMENT REPORT
    ═══════════════════════════════════════════════════════════ */

    /**
     * Aggregate counts and amounts grouped by order_mode × payment_status.
     * Used to populate the three payment-mode summary cards.
     */
    public function getPaymentModeStats(): array
    {
        try {
            return $this->db->select(
                "SELECT
                    COALESCE(order_mode, 'Unknown') AS order_mode,
                    payment_status,
                    COUNT(*)                        AS cnt,
                    COALESCE(SUM(final_total_amt),0) AS total_amt
                 FROM tbl_user_order
                 WHERE order_type   = 'Order'
                   AND order_status != 'Cart'
                   AND order_status IS NOT NULL
                 GROUP BY order_mode, payment_status
                 ORDER BY order_mode, payment_status"
            );
        } catch (Exception $e) {
            error_log('getPaymentModeStats: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Full payment report row set — all non-cart orders, richly joined.
     * Sorted so Payment Pending / Failed rows bubble to the top, oldest-first
     * within each urgency tier (most actionable items first).
     */
    public function getOpenInvoicesReport(array $f = []): array
    {
        $where = "o.order_type = 'Order'
                  AND o.order_status  != 'Cart'
                  AND o.order_status  IS NOT NULL";

        if (!empty($f['search'])) {
            $s = addslashes($f['search']);
            $where .= " AND (o.order_number LIKE '%$s%'
                          OR COALESCE(u.name,'') LIKE '%$s%'
                          OR COALESCE(u.communication_email_id,'') LIKE '%$s%'
                          OR COALESCE(u.company_name,'') LIKE '%$s%'
                          OR COALESCE(o.invoice_no,'') LIKE '%$s%'
                          OR COALESCE(o.bank_reference_no,'') LIKE '%$s%')";
        }
        if (!empty($f['order_mode']))      $where .= " AND o.order_mode='".addslashes($f['order_mode'])."'";
        if (!empty($f['payment_status']))  $where .= " AND o.payment_status='".addslashes($f['payment_status'])."'";
        if (!empty($f['order_status']))    $where .= " AND o.order_status='".addslashes($f['order_status'])."'";
        if (!empty($f['date_from']))       $where .= " AND DATE(o.order_date) >= '".addslashes($f['date_from'])."'";
        if (!empty($f['date_to']))         $where .= " AND DATE(o.order_date) <= '".addslashes($f['date_to'])."'";
        if (!empty($f['overdue_only']))    $where .= " AND o.payment_status IN ('Payment Pending','Payment Failed')
                                                       AND DATEDIFF(NOW(), o.order_date) > 30";

        try {
            return $this->db->select(
                "SELECT
                    o.user_order_id,
                    o.order_number,
                    o.order_date,
                    o.order_mode,
                    o.order_status,
                    o.payment_status,
                    o.final_total_amt,
                    o.order_total_amt,
                    o.tax_total_amount,
                    o.shipping_amt,
                    o.discount_amt,
                    o.invoice_no,
                    o.bank_reference_no,
                    o.transaction_id,
                    o.pay_pal_tx_id,
                    o.customer_po_id,
                    o.customer_supplier_no,
                    o.enquiry_quote_id,
                    COALESCE(u.name,'')                        AS cust_name,
                    COALESCE(u.communication_email_id,'')      AS cust_email,
                    COALESCE(u.communication_mobile_num,'')    AS cust_phone,
                    COALESCE(u.company_name,'')                AS cust_company,
                    DATEDIFF(NOW(), o.order_date)              AS age_days,
                    (SELECT COUNT(*) FROM tbl_user_order_item i
                     WHERE i.user_order_id = o.user_order_id
                       AND i.item_status   = 'Active')         AS item_count
                 FROM tbl_user_order o
                 LEFT JOIN tbl_user u ON u.user_id = o.user_id
                 WHERE {$where}
                 ORDER BY
                     CASE o.payment_status
                         WHEN 'Payment Pending'  THEN 1
                         WHEN 'Payment Failed'   THEN 2
                         WHEN 'Refund Initiated' THEN 3
                         ELSE 4
                     END,
                     o.order_date ASC"
            );
        } catch (Exception $e) {
            error_log('getOpenInvoicesReport: ' . $e->getMessage());
            return [];
        }
    }

    /* ═══════════════════════════════════════════════════════════
       INVENTORY MANAGEMENT
    ═══════════════════════════════════════════════════════════ */

    /**
     * Full inventory report with stock health, aging, and movement KPIs per product.
     */
    public function getInventoryReport(array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[]  = '(p.product_name LIKE ? OR p.product_code LIKE ?)';
            $params[] = $s;
            $params[] = $s;
        }
        if (!empty($filters['category_id'])) {
            $cid = (int)$filters['category_id'];
            /* match the category itself or any child whose parent matches */
            $where[]  = '(p.product_category_id = ? OR c.parent_category_id = ?)';
            $params[] = $cid;
            $params[] = $cid;
        }
        if (!empty($filters['status'])) {
            $where[]  = 'p.product_status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['stock_health'])) {
            switch ($filters['stock_health']) {
                case 'out':
                    $where[] = 'p.total_remaining <= 0'; break;
                case 'critical':
                    $where[] = 'p.total_remaining > 0 AND p.total_remaining <= p.product_threshold'; break;
                case 'warning':
                    $where[] = 'p.total_remaining > p.product_threshold AND p.total_remaining <= (p.product_threshold * 2)'; break;
                case 'healthy':
                    $where[] = 'p.total_remaining > (p.product_threshold * 2)'; break;
            }
        }

        $whereStr = implode(' AND ', $where);

        $sql = "SELECT
                    p.product_id,
                    p.product_name,
                    p.product_code,
                    p.product_status,
                    p.product_amt,
                    p.product_threshold,
                    p.total_product,
                    p.total_sold,
                    p.total_remaining,
                    p.product_entry_date,
                    c.product_category_name,
                    c.parent_category_id,
                    pc.product_category_name  AS parent_category_name,
                    (SELECT MAX(pp2.date_of_purchase)
                     FROM tbl_product_purchase pp2
                     WHERE pp2.product_id = p.product_id)              AS last_purchase_date,
                    (SELECT COUNT(*)
                     FROM tbl_product_purchase pp3
                     WHERE pp3.product_id = p.product_id)              AS purchase_count,
                    (SELECT COALESCE(SUM(pp4.purchase_amt * pp4.quantity_purchased),0)
                     FROM tbl_product_purchase pp4
                     WHERE pp4.product_id = p.product_id)              AS total_purchase_cost,
                    (SELECT MAX(o2.order_date)
                     FROM tbl_user_order_item oi2
                     JOIN tbl_user_order o2 ON o2.user_order_id = oi2.user_order_id
                     WHERE oi2.product_id = p.product_id
                       AND oi2.item_status = 'Active'
                       AND oi2.order_type  = 'Order'
                       AND o2.order_status NOT IN ('Cart','Order Cancelled')) AS last_sale_date,
                    (SELECT COUNT(DISTINCT oi3.user_order_id)
                     FROM tbl_user_order_item oi3
                     JOIN tbl_user_order o3 ON o3.user_order_id = oi3.user_order_id
                     WHERE oi3.product_id = p.product_id
                       AND oi3.item_status = 'Active'
                       AND oi3.order_type  = 'Order'
                       AND o3.order_status NOT IN ('Cart','Order Cancelled')) AS order_count,
                    (SELECT pi2.image_ext
                     FROM tbl_product_img pi2
                     WHERE pi2.product_id = p.product_id
                       AND pi2.image_for  = 'Product'
                     ORDER BY pi2.priorty ASC LIMIT 1)                 AS thumb_ext
                FROM tbl_product p
                LEFT JOIN tbl_product_category c
                       ON c.product_category_id = p.product_category_id
                LEFT JOIN tbl_product_category pc
                       ON pc.product_category_id = c.parent_category_id
                WHERE {$whereStr}
                ORDER BY p.total_remaining ASC, p.product_name ASC";

        try {
            return $this->db->select($sql, !empty($params) ? $params : null);
        } catch (Exception $e) {
            error_log('getInventoryReport: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Aggregate KPI stats for the inventory dashboard tiles.
     */
    public function getInventoryStats(): ?object
    {
        try {
            $rows = $this->db->select(
                "SELECT
                    COUNT(*)                                                               AS total_products,
                    SUM(CASE WHEN product_status='Active'  THEN 1 ELSE 0 END)             AS active_products,
                    SUM(CASE WHEN total_remaining <= 0     THEN 1 ELSE 0 END)             AS out_of_stock,
                    SUM(CASE WHEN total_remaining > 0
                              AND total_remaining <= product_threshold               THEN 1 ELSE 0 END) AS critical_stock,
                    SUM(CASE WHEN total_remaining > product_threshold
                              AND total_remaining <= (product_threshold * 2)         THEN 1 ELSE 0 END) AS warning_stock,
                    SUM(CASE WHEN total_remaining > (product_threshold * 2)          THEN 1 ELSE 0 END) AS healthy_stock,
                    COALESCE(SUM(total_remaining), 0)                                      AS total_units_in_stock,
                    COALESCE(SUM(total_sold), 0)                                           AS total_units_sold,
                    COALESCE(SUM(total_remaining * product_amt), 0)                        AS total_stock_value
                FROM tbl_product
                WHERE product_status = 'Active'"
            );
            return $rows[0] ?? null;
        } catch (Exception $e) {
            error_log('getInventoryStats: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Full IN/OUT movement ledger for one product, optionally date-filtered.
     * Returns movements newest-first; each row also carries RUNNING_BALANCE
     * (computed in PHP so no balance column needed in the DB).
     */
    public function getProductMovementLedger(int $productId, string $dateFrom = '', string $dateTo = ''): array
    {
        $dateWhere = '';
        if ($dateFrom !== '') $dateWhere .= " AND movement_date >= '" . addslashes($dateFrom) . "'";
        if ($dateTo   !== '') $dateWhere .= " AND movement_date <= '" . addslashes($dateTo)   . " 23:59:59'";

        $sql = "SELECT * FROM (
                    SELECT
                        'IN'                          AS movement_type,
                        pp.product_purchase_id        AS ref_id,
                        CAST(pp.date_of_purchase AS DATETIME) AS movement_date,
                        pp.quantity_purchased         AS quantity,
                        pp.purchased_from             AS reference_name,
                        COALESCE(pp.receipt_no, '')   AS reference_no,
                        pp.purchase_amt               AS unit_cost,
                        0                             AS order_id,
                        ''                            AS order_number,
                        ''                            AS customer_name
                    FROM tbl_product_purchase pp
                    WHERE pp.product_id = {$productId}

                    UNION ALL

                    SELECT
                        'OUT'                         AS movement_type,
                        oi.user_order_item_id         AS ref_id,
                        o.order_date                  AS movement_date,
                        oi.quantity                   AS quantity,
                        u.name                        AS reference_name,
                        o.order_number                AS reference_no,
                        oi.product_amt                AS unit_cost,
                        o.user_order_id               AS order_id,
                        o.order_number                AS order_number,
                        u.name                        AS customer_name
                    FROM tbl_user_order_item oi
                    JOIN tbl_user_order o  ON o.user_order_id = oi.user_order_id
                    JOIN tbl_user u        ON u.user_id       = o.user_id
                    WHERE oi.product_id   = {$productId}
                      AND oi.item_status  = 'Active'
                      AND oi.order_type   = 'Order'
                      AND o.order_status NOT IN ('Cart','Order Cancelled')
                ) ledger
                WHERE 1=1 {$dateWhere}
                ORDER BY movement_date ASC, ref_id ASC";

        try {
            $rows = $this->db->select($sql);
        } catch (Exception $e) {
            error_log('getProductMovementLedger: ' . $e->getMessage());
            return [];
        }

        /* Compute running balance (oldest→newest, then reverse for display) */
        $balance = 0;
        foreach ($rows as $row) {
            $qty = (float)($row->QUANTITY ?? 0);
            if ((string)($row->MOVEMENT_TYPE ?? '') === 'IN') {
                $balance += $qty;
            } else {
                $balance -= $qty;
            }
            $row->RUNNING_BALANCE = $balance;
        }

        return array_reverse($rows); /* newest first */
    }

    /* ═══════════════════════════════════════════════════════════
       VAT FILING
    ═══════════════════════════════════════════════════════════ */

    /**
     * Full data for one calendar month's VAT return.
     * Returns: summary (totals), byRate (per-rate breakdown),
     *          orders (line list), filing (saved status record or null).
     */
    public function getVatMonthlyData(int $year, int $month): array
    {
        try {
            $summary = $this->db->select(
                "SELECT
                     COUNT(DISTINCT o.user_order_id)                                  AS order_count,
                     SUM(o.order_total_amt)                                           AS gross_sales,
                     SUM(o.tax_total_amount)                                          AS output_vat,
                     SUM(o.discount_amt)                                              AS total_discounts,
                     SUM(o.shipping_amt)                                              AS shipping_revenue,
                     SUM(o.final_total_amt)                                           AS total_billed,
                     SUM(o.final_total_amt - o.tax_total_amount)                      AS net_excl_vat,
                     SUM(CASE WHEN o.payment_status='Payment Successful'
                              THEN o.final_total_amt ELSE 0 END)                      AS collected,
                     SUM(CASE WHEN o.payment_status='Payment Pending'
                              THEN o.final_total_amt ELSE 0 END)                      AS pending,
                     SUM(CASE WHEN o.tax_total_amount > 0
                              THEN (o.final_total_amt - o.tax_total_amount) ELSE 0 END) AS std_rated_net,
                     SUM(CASE WHEN o.tax_total_amount = 0
                              THEN (o.final_total_amt - o.tax_total_amount) ELSE 0 END) AS zero_rated_net
                 FROM tbl_user_order o
                 WHERE o.order_type    = 'Order'
                   AND o.order_status != 'Cart'
                   AND o.order_status  IS NOT NULL
                   AND YEAR(o.order_date)  = $year
                   AND MONTH(o.order_date) = $month"
            )[0] ?? null;

            $byRate = $this->db->select(
                "SELECT
                     i.tax_percentage                    AS vat_rate,
                     COUNT(DISTINCT o.user_order_id)     AS transaction_count,
                     SUM(i.final_amt - i.tax_amt)        AS net_excl_vat,
                     SUM(i.tax_amt)                      AS vat_amount,
                     SUM(i.final_amt)                    AS gross_incl_vat
                 FROM tbl_user_order_item i
                 JOIN tbl_user_order o ON o.user_order_id = i.user_order_id
                 WHERE o.order_type    = 'Order'
                   AND i.item_status  = 'Active'
                   AND YEAR(o.order_date)  = $year
                   AND MONTH(o.order_date) = $month
                 GROUP BY i.tax_percentage
                 ORDER BY i.tax_percentage DESC"
            );

            $orders = $this->db->select(
                "SELECT
                     o.user_order_id,
                     o.order_number,
                     o.order_date,
                     o.order_mode,
                     o.order_status,
                     o.payment_status,
                     o.invoice_no,
                     o.vat_number,
                     o.customer_po_id,
                     o.order_total_amt,
                     o.tax_total_amount,
                     o.discount_amt,
                     o.shipping_amt,
                     o.final_total_amt,
                     u.name                           AS cust_name,
                     u.communication_email_id         AS cust_email,
                     u.company_name                   AS cust_company,
                     COUNT(i.user_order_item_id)       AS item_count,
                     GROUP_CONCAT(
                         DISTINCT CONCAT(i.tax_percentage,'%')
                         ORDER BY i.tax_percentage DESC
                         SEPARATOR ', '
                     )                                AS vat_rates
                 FROM tbl_user_order o
                 JOIN tbl_user u ON u.user_id = o.user_id
                 LEFT JOIN tbl_user_order_item i
                        ON i.user_order_id = o.user_order_id
                       AND i.item_status   = 'Active'
                 WHERE o.order_type    = 'Order'
                   AND o.order_status != 'Cart'
                   AND o.order_status  IS NOT NULL
                   AND YEAR(o.order_date)  = $year
                   AND MONTH(o.order_date) = $month
                 GROUP BY o.user_order_id
                 ORDER BY o.order_date ASC"
            );

            $ym     = sprintf('%04d-%02d', $year, $month);
            $filing = $this->db->select(
                "SELECT * FROM tbl_vat_filing
                  WHERE filing_period = '$ym'
                    AND filing_type   = 'Monthly'
                  LIMIT 1"
            )[0] ?? null;

            return compact('summary', 'byRate', 'orders', 'filing');
        } catch (Exception $e) {
            error_log('getVatMonthlyData: ' . $e->getMessage());
            return ['summary' => null, 'byRate' => [], 'orders' => [], 'filing' => null];
        }
    }

    /**
     * Full-year VAT data: 12 monthly rows, totals, rate breakdown,
     * per-month filing map, and annual filing record.
     */
    public function getVatYearlyData(int $year): array
    {
        try {
            $monthly = $this->db->select(
                "SELECT
                     MONTH(o.order_date)                                         AS month_num,
                     MONTHNAME(o.order_date)                                     AS month_name,
                     COUNT(DISTINCT o.user_order_id)                             AS order_count,
                     SUM(o.order_total_amt)                                      AS gross_sales,
                     SUM(o.tax_total_amount)                                     AS output_vat,
                     SUM(o.discount_amt)                                         AS discounts,
                     SUM(o.shipping_amt)                                         AS shipping,
                     SUM(o.final_total_amt)                                      AS total_billed,
                     SUM(o.final_total_amt - o.tax_total_amount)                 AS net_excl_vat,
                     SUM(CASE WHEN o.payment_status='Payment Successful'
                              THEN o.final_total_amt ELSE 0 END)                 AS collected
                 FROM tbl_user_order o
                 WHERE o.order_type    = 'Order'
                   AND o.order_status != 'Cart'
                   AND o.order_status  IS NOT NULL
                   AND YEAR(o.order_date) = $year
                 GROUP BY month_num
                 ORDER BY month_num ASC"
            );

            $totals = $this->db->select(
                "SELECT
                     COUNT(DISTINCT o.user_order_id)                             AS order_count,
                     SUM(o.order_total_amt)                                      AS gross_sales,
                     SUM(o.tax_total_amount)                                     AS output_vat,
                     SUM(o.discount_amt)                                         AS discounts,
                     SUM(o.shipping_amt)                                         AS shipping,
                     SUM(o.final_total_amt)                                      AS total_billed,
                     SUM(o.final_total_amt - o.tax_total_amount)                 AS net_excl_vat,
                     SUM(CASE WHEN o.payment_status='Payment Successful'
                              THEN o.final_total_amt ELSE 0 END)                 AS collected
                 FROM tbl_user_order o
                 WHERE o.order_type    = 'Order'
                   AND o.order_status != 'Cart'
                   AND o.order_status  IS NOT NULL
                   AND YEAR(o.order_date) = $year"
            )[0] ?? null;

            $byRate = $this->db->select(
                "SELECT
                     i.tax_percentage                    AS vat_rate,
                     COUNT(DISTINCT o.user_order_id)     AS transaction_count,
                     SUM(i.final_amt - i.tax_amt)        AS net_excl_vat,
                     SUM(i.tax_amt)                      AS vat_amount,
                     SUM(i.final_amt)                    AS gross_incl_vat
                 FROM tbl_user_order_item i
                 JOIN tbl_user_order o ON o.user_order_id = i.user_order_id
                 WHERE o.order_type    = 'Order'
                   AND i.item_status  = 'Active'
                   AND YEAR(o.order_date) = $year
                 GROUP BY i.tax_percentage
                 ORDER BY i.tax_percentage DESC"
            );

            /* Per-month filing statuses */
            $filingRows = $this->db->select(
                "SELECT * FROM tbl_vat_filing
                  WHERE filing_period LIKE '{$year}-%'
                    AND filing_type = 'Monthly'"
            );
            $filingMap = [];
            foreach ($filingRows as $fr) {
                $mn = (int)substr((string)$fr->FILING_PERIOD, 5, 2);
                $filingMap[$mn] = $fr;
            }

            $annualFiling = $this->db->select(
                "SELECT * FROM tbl_vat_filing
                  WHERE filing_period = '$year'
                    AND filing_type   = 'Yearly'
                  LIMIT 1"
            )[0] ?? null;

            return compact('monthly', 'totals', 'byRate', 'filingMap', 'annualFiling');
        } catch (Exception $e) {
            error_log('getVatYearlyData: ' . $e->getMessage());
            return ['monthly' => [], 'totals' => null, 'byRate' => [], 'filingMap' => [], 'annualFiling' => null];
        }
    }

    /**
     * Upsert a VAT filing record (monthly or yearly).
     */
    public function saveVatFiling(
        string $period,
        string $type,
        float  $outputVat,
        float  $inputVat,
        float  $netSales,
        string $status,
        string $refNo,
        string $notes,
        int    $adminUid
    ): bool {
        try {
            $period    = addslashes($period);
            $type      = addslashes($type);
            $status    = addslashes($status);
            $refNo     = addslashes($refNo);
            $notes     = addslashes($notes);
            $netVat    = round($outputVat - $inputVat, 2);
            $filedAt   = ($status === 'Filed') ? 'NOW()' : 'NULL';
            $filedBy   = ($status === 'Filed') ? $adminUid : 'NULL';

            $existing = $this->db->select(
                "SELECT vat_filing_id FROM tbl_vat_filing
                  WHERE filing_period = '$period'
                    AND filing_type   = '$type'
                  LIMIT 1"
            )[0] ?? null;

            if ($existing) {
                $affected = $this->db->update(
                    "UPDATE tbl_vat_filing SET
                         output_vat        = $outputVat,
                         input_vat         = $inputVat,
                         net_vat_liability = $netVat,
                         total_net_sales   = $netSales,
                         filing_status     = '$status',
                         filed_by          = $filedBy,
                         filed_at          = $filedAt,
                         reference_no      = '$refNo',
                         notes             = '$notes'
                     WHERE filing_period = '$period'
                       AND filing_type   = '$type'"
                );
                return $affected !== false;
            } else {
                $newId = (int)$this->db->insert(
                    "INSERT INTO tbl_vat_filing
                         (filing_period, filing_type, output_vat, input_vat,
                          net_vat_liability, total_net_sales, filing_status,
                          filed_by, filed_at, reference_no, notes)
                     VALUES
                         ('$period', '$type', $outputVat, $inputVat,
                          $netVat, $netSales, '$status',
                          $filedBy, $filedAt, '$refNo', '$notes')"
                );
                return $newId > 0;
            }
        } catch (Exception $e) {
            error_log('saveVatFiling: ' . $e->getMessage());
            return false;
        }
    }

    /* ═══════════════════════════════════════════════════════════
       DASHBOARD V2  (role-aware, uses tbl_user_order)
    ═══════════════════════════════════════════════════════════ */

    /**
     * Load only the dashboard sections the current user needs.
     * $flags: assoc array — ['orders'=>true, 'finance'=>true, ...]
     * Returns structured array keyed by section.
     */
    public function getDashboardV2(array $flags): array
    {
        $d = [];
        try {

            /* ── ORDERS ─────────────────────────────────── */
            if (!empty($flags['orders'])) {
                $d['order_kpis'] = $this->db->select(
                    "SELECT
                         COUNT(DISTINCT CASE WHEN order_status != 'Cart'                            THEN user_order_id END) AS total,
                         COUNT(DISTINCT CASE WHEN order_status = 'Order Pending'                    THEN user_order_id END) AS pending,
                         COUNT(DISTINCT CASE WHEN order_status = 'Order Confirmed'                  THEN user_order_id END) AS confirmed,
                         COUNT(DISTINCT CASE WHEN order_status IN ('Order Dispatch','Order In Transit','Order Packed') THEN user_order_id END) AS in_transit,
                         COUNT(DISTINCT CASE WHEN order_status = 'Order Delivered'                  THEN user_order_id END) AS delivered,
                         COUNT(DISTINCT CASE WHEN order_status = 'Order Cancelled'                  THEN user_order_id END) AS cancelled,
                         COUNT(DISTINCT CASE WHEN order_status != 'Cart'
                                             AND YEAR(order_date)  = YEAR(CURDATE())
                                             AND MONTH(order_date) = MONTH(CURDATE())  THEN user_order_id END) AS this_month
                     FROM tbl_user_order
                     WHERE order_type = 'Order'"
                )[0] ?? null;

                $d['recent_orders'] = $this->db->select(
                    "SELECT o.user_order_id, o.order_number, o.order_date, o.order_status,
                            o.payment_status, o.final_total_amt, o.order_mode,
                            u.name AS cust_name, u.company_name AS cust_company
                     FROM tbl_user_order o
                     JOIN tbl_user u ON u.user_id = o.user_id
                     WHERE o.order_type = 'Order' AND o.order_status != 'Cart'
                     ORDER BY o.order_date DESC LIMIT 7"
                );
            }

            /* ── FINANCE ─────────────────────────────────── */
            if (!empty($flags['finance'])) {
                $d['finance_kpis'] = $this->db->select(
                    "SELECT
                         SUM(CASE WHEN payment_status='Payment Successful' THEN final_total_amt ELSE 0 END)  AS collected,
                         SUM(CASE WHEN payment_status='Payment Pending'    THEN final_total_amt ELSE 0 END)  AS pending_rev,
                         SUM(CASE WHEN payment_status='Payment Failed'     THEN final_total_amt ELSE 0 END)  AS failed_rev,
                         SUM(CASE WHEN YEAR(order_date)=YEAR(CURDATE()) AND MONTH(order_date)=MONTH(CURDATE())
                                   AND payment_status='Payment Successful'  THEN final_total_amt ELSE 0 END) AS this_month_col,
                         COUNT(DISTINCT CASE WHEN payment_status='Payment Pending' THEN user_order_id END)   AS pending_count
                     FROM tbl_user_order
                     WHERE order_type = 'Order' AND order_status != 'Cart'"
                )[0] ?? null;

                /* 6-month revenue chart */
                $rows = $this->db->select(
                    "SELECT DATE_FORMAT(order_date,'%b %Y')  AS label,
                            DATE_FORMAT(order_date,'%Y-%m')  AS ym,
                            COUNT(DISTINCT user_order_id)     AS orders,
                            SUM(final_total_amt)              AS revenue,
                            SUM(CASE WHEN payment_status='Payment Successful'
                                     THEN final_total_amt ELSE 0 END) AS collected
                     FROM tbl_user_order
                     WHERE order_type='Order' AND order_status!='Cart'
                       AND order_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
                     GROUP BY ym ORDER BY ym ASC"
                ) ?: [];

                $map = [];
                foreach ($rows as $row) $map[(string)($row->YM ?? '')] = $row;

                $labels = $revArr = $ordArr = $colArr = [];
                for ($i = 5; $i >= 0; $i--) {
                    $ym       = date('Y-m', strtotime("-$i month"));
                    $labels[] = date('M Y',  strtotime("-$i month"));
                    $row      = $map[$ym] ?? null;
                    $ordArr[] = $row ? (int)  ($row->ORDERS    ?? 0)    : 0;
                    $revArr[] = $row ? round((float)($row->REVENUE   ?? 0), 2) : 0;
                    $colArr[] = $row ? round((float)($row->COLLECTED ?? 0), 2) : 0;
                }
                $d['revenue_chart'] = compact('labels', 'revArr', 'ordArr', 'colArr');
            }

            /* ── PRODUCTS / INVENTORY ─────────────────────── */
            if (!empty($flags['products'])) {
                $d['product_kpis'] = $this->db->select(
                    "SELECT
                         COUNT(*)                                                                    AS total_products,
                         SUM(CASE WHEN product_status='Active'   THEN 1 ELSE 0 END)                AS active_products,
                         SUM(CASE WHEN total_remaining <= product_threshold
                                   AND product_status='Active'   THEN 1 ELSE 0 END)                AS low_stock,
                         SUM(CASE WHEN total_remaining = 0
                                   AND product_status='Active'   THEN 1 ELSE 0 END)                AS out_of_stock
                     FROM tbl_product"
                )[0] ?? null;

                $d['low_stock_items'] = $this->db->select(
                    "SELECT product_id, product_name, product_code,
                            total_remaining, product_threshold
                     FROM tbl_product
                     WHERE total_remaining <= product_threshold
                       AND product_status = 'Active'
                     ORDER BY total_remaining ASC LIMIT 5"
                );
            }

            /* ── CUSTOMERS ─────────────────────────────────── */
            if (!empty($flags['customers'])) {
                $d['customer_kpis'] = $this->db->select(
                    "SELECT COUNT(*) AS total_customers FROM tbl_user WHERE user_type_id = 2"
                )[0] ?? null;
            }

            /* ── QUOTATIONS ─────────────────────────────────── */
            if (!empty($flags['quotes'])) {
                $d['quote_kpis'] = $this->db->select(
                    "SELECT COUNT(*) AS total_quotes,
                         SUM(CASE WHEN enquiry_status='Quotation Pending' THEN 1 ELSE 0 END) AS pending,
                         SUM(CASE WHEN enquiry_status='Quotation Sent'    THEN 1 ELSE 0 END) AS sent,
                         SUM(CASE WHEN enquiry_status='Order Generated'   THEN 1 ELSE 0 END) AS converted,
                         SUM(CASE WHEN enquiry_status='Order Completed'   THEN 1 ELSE 0 END) AS completed
                     FROM tbl_enquiry_quote"
                )[0] ?? null;

                $d['pending_quotes'] = $this->db->select(
                    "SELECT eq.enquiry_quote_id, eq.user_name, eq.company_name,
                            eq.enquiry_date, eq.enquiry_status, eq.delivery_country,
                            COUNT(eqp.enquiry_quote_product_id) AS item_count
                     FROM tbl_enquiry_quote eq
                     LEFT JOIN tbl_enquiry_quote_product eqp
                            ON eqp.enquiry_quote_id = eq.enquiry_quote_id
                     WHERE eq.enquiry_status = 'Quotation Pending'
                     GROUP BY eq.enquiry_quote_id
                     ORDER BY eq.enquiry_date ASC LIMIT 7"
                );
            }

            /* ── REFUNDS ─────────────────────────────────── */
            if (!empty($flags['refunds'])) {
                $d['refund_kpis'] = $this->db->select(
                    "SELECT
                         COUNT(*)                                                                    AS total_returns,
                         SUM(CASE WHEN order_return_replacement_status='Return Requested' THEN 1 ELSE 0 END) AS pending_approval,
                         SUM(CASE WHEN order_return_replacement_status IN
                                  ('Return Request Approved','Pickup Scheduled','Pickup Completed','QC Approved')
                                  THEN 1 ELSE 0 END)                                                AS in_process,
                         SUM(final_total_amt)                                                        AS total_return_value
                     FROM tbl_user_order
                     WHERE order_type = 'Return & Refund'"
                )[0] ?? null;
            }

            /* ── HR ─────────────────────────────────────────── */
            if (!empty($flags['hr'])) {
                $d['hr_kpis'] = $this->db->select(
                    "SELECT COUNT(*) AS total_jobs,
                         SUM(CASE WHEN job_status='Active' THEN 1 ELSE 0 END) AS active_jobs
                     FROM tbl_job_career"
                )[0] ?? null;

                $d['recent_applicants'] = $this->db->select(
                    "SELECT ca.candidate_applied_job_id, ca.candidate_name,
                            ca.candidate_email, ca.applied_date, jc.job_position
                     FROM tbl_candidate_applied_for_job ca
                     JOIN tbl_job_career jc ON jc.job_post_id = ca.job_post_id
                     ORDER BY ca.applied_date DESC LIMIT 5"
                );
            }

        } catch (Exception $e) {
            error_log('getDashboardV2: ' . $e->getMessage());
        }

        return $d;
    }

    /* ═══════════════════════════════════════════════════════════
       SALES & REVENUE REPORT
    ═══════════════════════════════════════════════════════════ */

    /**
     * Build the shared WHERE clause for all sales-revenue queries.
     * Only references tbl_user_order aliased as `o`.
     */
    private function srWhere(array $f): string
    {
        $conds = [
            "o.order_type    = 'Order'",
            "o.order_status != 'Cart'",
            "o.order_status  IS NOT NULL",
        ];

        if (!empty($f['date_from']))
            $conds[] = "DATE(o.order_date) >= '" . addslashes($f['date_from']) . "'";
        if (!empty($f['date_to']))
            $conds[] = "DATE(o.order_date) <= '" . addslashes($f['date_to'])   . "'";
        if (!empty($f['order_mode']))
            $conds[] = "o.order_mode = '" . addslashes($f['order_mode']) . "'";
        if (!empty($f['payment_status']))
            $conds[] = "o.payment_status = '" . addslashes($f['payment_status']) . "'";

        /* Category filter — subquery so it works on any joining query */
        if (!empty($f['category_id'])) {
            $cid     = (int)$f['category_id'];
            $conds[] = "o.user_order_id IN (
                            SELECT DISTINCT i2.user_order_id
                            FROM   tbl_user_order_item i2
                            WHERE  i2.item_status = 'Active'
                              AND  i2.product_category_id IN (
                                       SELECT product_category_id
                                       FROM   tbl_product_category
                                       WHERE  product_category_id = $cid
                                          OR  parent_category_id  = $cid
                                   )
                        )";
        }

        /* Search — order_number OR customer name/company via EXISTS */
        if (!empty($f['search'])) {
            $s       = addslashes($f['search']);
            $conds[] = "(o.order_number LIKE '%$s%'
                         OR EXISTS (
                             SELECT 1 FROM tbl_user u2
                             WHERE  u2.user_id = o.user_id
                               AND  (u2.name          LIKE '%$s%'
                                 OR  u2.company_name  LIKE '%$s%'
                                 OR  u2.communication_email_id LIKE '%$s%')
                         ))";
        }

        return 'WHERE ' . implode(' AND ', $conds);
    }

    /** Overall KPI summary for the selected period. */
    public function getSalesRevenueSummary(array $f = []): ?object
    {
        $w = $this->srWhere($f);
        try {
            return $this->db->select(
                "SELECT
                     COUNT(DISTINCT o.user_order_id)                               AS total_orders,
                     COUNT(DISTINCT o.user_id)                                     AS unique_customers,
                     SUM(o.order_total_amt)                                        AS gross_product,
                     SUM(o.tax_total_amount)                                       AS total_vat,
                     SUM(o.discount_amt)                                           AS total_discounts,
                     SUM(o.shipping_amt)                                           AS shipping_revenue,
                     SUM(o.final_total_amt)                                        AS total_revenue,
                     SUM(o.final_total_amt - o.tax_total_amount)                   AS net_revenue,
                     COALESCE(AVG(o.final_total_amt), 0)                           AS avg_order_value,
                     SUM(CASE WHEN o.payment_status = 'Payment Successful'
                              THEN o.final_total_amt ELSE 0 END)                   AS collected,
                     SUM(CASE WHEN o.payment_status = 'Payment Pending'
                              THEN o.final_total_amt ELSE 0 END)                   AS pending,
                     SUM(CASE WHEN o.payment_status = 'Payment Failed'
                              THEN o.final_total_amt ELSE 0 END)                   AS failed,
                     SUM(CASE WHEN o.payment_status IN ('Refund Initiated','Refund Completed')
                              THEN o.final_total_amt ELSE 0 END)                   AS refunded,
                     SUM(CASE WHEN o.order_status = 'Order Delivered'
                              THEN o.final_total_amt ELSE 0 END)                   AS delivered_revenue,
                     SUM(CASE WHEN o.order_status = 'Order Cancelled'
                              THEN o.final_total_amt ELSE 0 END)                   AS cancelled_revenue,
                     COUNT(DISTINCT CASE WHEN o.order_status = 'Order Delivered'
                              THEN o.user_order_id END)                            AS delivered_count,
                     COUNT(DISTINCT CASE WHEN o.order_status = 'Order Cancelled'
                              THEN o.user_order_id END)                            AS cancelled_count
                 FROM tbl_user_order o
                 $w"
            )[0] ?? null;
        } catch (Exception $e) {
            error_log('getSalesRevenueSummary: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Revenue trend data grouped by day / week / month depending on span.
     * Returns array of rows with LABEL, ORDERS, REVENUE, NET_REVENUE, VAT, DISCOUNTS, SHIPPING, COLLECTED.
     */
    public function getSalesTrendData(array $f = []): array
    {
        $w = $this->srWhere($f);

        /* Auto-granularity */
        $gran = 'monthly';
        if (!empty($f['date_from']) && !empty($f['date_to'])) {
            $days = (int)((strtotime($f['date_to']) - strtotime($f['date_from'])) / 86400) + 1;
            if      ($days <= 31) $gran = 'daily';
            elseif  ($days <= 93) $gran = 'weekly';
            else                  $gran = 'monthly';
        }

        [$groupExpr, $labelExpr, $sortExpr] = match($gran) {
            'daily'  => [
                "DATE(o.order_date)",
                "DATE_FORMAT(o.order_date, '%d %b')",
                "DATE(o.order_date)",
            ],
            'weekly' => [
                "YEARWEEK(o.order_date, 1)",
                "CONCAT('Wk ', WEEK(o.order_date,1), ' \\'', RIGHT(YEAR(o.order_date),2))",
                "YEARWEEK(o.order_date, 1)",
            ],
            default  => [
                "DATE_FORMAT(o.order_date, '%Y-%m')",
                "DATE_FORMAT(o.order_date, '%b %Y')",
                "DATE_FORMAT(o.order_date, '%Y-%m')",
            ],
        };

        try {
            return $this->db->select(
                "SELECT
                     $labelExpr                                                 AS label,
                     COUNT(DISTINCT o.user_order_id)                           AS orders,
                     SUM(o.final_total_amt)                                    AS revenue,
                     SUM(o.final_total_amt - o.tax_total_amount)               AS net_revenue,
                     SUM(o.tax_total_amount)                                   AS vat,
                     SUM(o.discount_amt)                                       AS discounts,
                     SUM(o.shipping_amt)                                       AS shipping,
                     SUM(CASE WHEN o.payment_status='Payment Successful'
                              THEN o.final_total_amt ELSE 0 END)               AS collected
                 FROM tbl_user_order o
                 $w
                 GROUP BY $groupExpr
                 ORDER BY $sortExpr ASC"
            ) ?: [];
        } catch (Exception $e) {
            error_log('getSalesTrendData: ' . $e->getMessage());
            return [];
        }
    }

    /** Revenue breakdown by product category. */
    public function getSalesByCategory(array $f = []): array
    {
        $w = $this->srWhere($f);
        try {
            $rows = $this->db->select(
                "SELECT
                     pc.product_category_id,
                     pc.product_category_name                                  AS category_name,
                     COALESCE(pp.product_category_name, '—')                  AS parent_name,
                     pc.parent_category_id,
                     COUNT(DISTINCT o.user_order_id)                           AS order_count,
                     SUM(i.quantity)                                           AS units_sold,
                     SUM(i.final_amt - i.tax_amt)                              AS net_revenue,
                     SUM(i.tax_amt)                                            AS vat_amount,
                     SUM(i.final_amt)                                          AS gross_revenue,
                     SUM(i.discount_amt)                                       AS discounts
                 FROM tbl_user_order o
                 JOIN tbl_user_order_item i  ON i.user_order_id = o.user_order_id
                                            AND i.item_status   = 'Active'
                 JOIN tbl_product_category pc ON pc.product_category_id = i.product_category_id
                 LEFT JOIN tbl_product_category pp ON pp.product_category_id = pc.parent_category_id
                 $w
                 GROUP BY pc.product_category_id
                 ORDER BY gross_revenue DESC"
            ) ?: [];

            $total = array_sum(array_map(fn($r) => (float)$r->GROSS_REVENUE, $rows));
            foreach ($rows as $row) {
                $row->PCT_SHARE = $total > 0
                    ? round((float)$row->GROSS_REVENUE / $total * 100, 1) : 0;
            }
            return $rows;
        } catch (Exception $e) {
            error_log('getSalesByCategory: ' . $e->getMessage());
            return [];
        }
    }

    /** Top-N products by revenue. */
    public function getSalesTopProducts(array $f = [], int $limit = 15): array
    {
        $w = $this->srWhere($f);
        try {
            $rows = $this->db->select(
                "SELECT
                     p.product_id,
                     p.product_name,
                     p.product_code,
                     pc.product_category_name                                  AS category_name,
                     SUM(i.quantity)                                           AS units_sold,
                     SUM(i.final_amt - i.tax_amt)                              AS net_revenue,
                     SUM(i.tax_amt)                                            AS vat_amount,
                     SUM(i.final_amt)                                          AS gross_revenue,
                     ROUND(SUM(i.final_amt) / NULLIF(SUM(i.quantity), 0), 2)  AS avg_unit_price,
                     SUM(i.discount_amt)                                       AS discounts
                 FROM tbl_user_order o
                 JOIN tbl_user_order_item i  ON i.user_order_id = o.user_order_id
                                            AND i.item_status   = 'Active'
                 JOIN tbl_product p          ON p.product_id    = i.product_id
                 JOIN tbl_product_category pc ON pc.product_category_id = i.product_category_id
                 $w
                 GROUP BY i.product_id
                 ORDER BY gross_revenue DESC
                 LIMIT $limit"
            ) ?: [];

            $total = array_sum(array_map(fn($r) => (float)$r->GROSS_REVENUE, $rows));
            foreach ($rows as $idx => $row) {
                $row->RANK      = $idx + 1;
                $row->PCT_SHARE = $total > 0
                    ? round((float)$row->GROSS_REVENUE / $total * 100, 1) : 0;
            }
            return $rows;
        } catch (Exception $e) {
            error_log('getSalesTopProducts: ' . $e->getMessage());
            return [];
        }
    }

    /** Top-N customers by revenue. */
    public function getSalesTopCustomers(array $f = [], int $limit = 10): array
    {
        $w = $this->srWhere($f);
        try {
            $rows = $this->db->select(
                "SELECT
                     u.user_id,
                     u.name                                                    AS cust_name,
                     u.company_name                                            AS cust_company,
                     u.communication_email_id                                  AS cust_email,
                     COUNT(DISTINCT o.user_order_id)                           AS order_count,
                     SUM(o.final_total_amt)                                    AS total_revenue,
                     ROUND(AVG(o.final_total_amt), 2)                          AS avg_order_value,
                     MAX(o.order_date)                                         AS last_order_date,
                     SUM(CASE WHEN o.payment_status = 'Payment Successful'
                              THEN o.final_total_amt ELSE 0 END)               AS collected,
                     SUM(CASE WHEN o.payment_status = 'Payment Pending'
                              THEN o.final_total_amt ELSE 0 END)               AS pending
                 FROM tbl_user_order o
                 JOIN tbl_user u ON u.user_id = o.user_id
                 $w
                 GROUP BY o.user_id
                 ORDER BY total_revenue DESC
                 LIMIT $limit"
            ) ?: [];

            $total = array_sum(array_map(fn($r) => (float)$r->TOTAL_REVENUE, $rows));
            foreach ($rows as $idx => $row) {
                $row->RANK      = $idx + 1;
                $row->PCT_SHARE = $total > 0
                    ? round((float)$row->TOTAL_REVENUE / $total * 100, 1) : 0;
            }
            return $rows;
        } catch (Exception $e) {
            error_log('getSalesTopCustomers: ' . $e->getMessage());
            return [];
        }
    }

    /** Revenue split by payment mode. */
    public function getSalesByMode(array $f = []): array
    {
        $w = $this->srWhere($f);
        try {
            return $this->db->select(
                "SELECT
                     COALESCE(o.order_mode, 'Unknown')                         AS order_mode,
                     COUNT(DISTINCT o.user_order_id)                           AS order_count,
                     SUM(o.final_total_amt)                                    AS revenue,
                     SUM(CASE WHEN o.payment_status = 'Payment Successful'
                              THEN o.final_total_amt ELSE 0 END)               AS collected,
                     SUM(CASE WHEN o.payment_status = 'Payment Pending'
                              THEN o.final_total_amt ELSE 0 END)               AS pending,
                     SUM(CASE WHEN o.payment_status = 'Payment Failed'
                              THEN o.final_total_amt ELSE 0 END)               AS failed
                 FROM tbl_user_order o
                 $w
                 GROUP BY o.order_mode
                 ORDER BY revenue DESC"
            ) ?: [];
        } catch (Exception $e) {
            error_log('getSalesByMode: ' . $e->getMessage());
            return [];
        }
    }

    /** Full order list for the transaction table (newest first). */
    public function getSalesOrderList(array $f = []): array
    {
        $w = $this->srWhere($f);
        try {
            return $this->db->select(
                "SELECT
                     o.user_order_id,
                     o.order_number,
                     o.order_date,
                     o.order_mode,
                     o.order_status,
                     o.payment_status,
                     o.invoice_no,
                     o.customer_po_id,
                     o.order_total_amt,
                     o.tax_total_amount,
                     o.discount_amt,
                     o.shipping_amt,
                     o.final_total_amt,
                     o.delivered_date,
                     u.name                                                    AS cust_name,
                     u.company_name                                            AS cust_company,
                     u.communication_email_id                                  AS cust_email,
                     COUNT(i.user_order_item_id)                               AS item_count,
                     SUM(i.quantity)                                           AS total_qty
                 FROM tbl_user_order o
                 JOIN tbl_user u ON u.user_id = o.user_id
                 LEFT JOIN tbl_user_order_item i
                        ON i.user_order_id = o.user_order_id
                       AND i.item_status   = 'Active'
                 $w
                 GROUP BY o.user_order_id
                 ORDER BY o.order_date DESC"
            ) ?: [];
        } catch (Exception $e) {
            error_log('getSalesOrderList: ' . $e->getMessage());
            return [];
        }
    }
}
?>
