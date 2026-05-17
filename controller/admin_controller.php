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

    public function addProductImage(int $productId, string $ext, string $imageFor, string $title = '', int $prio = 0, string $imageName = '', string $displayFlag = 'Yes', string $hyperLink = ''): int
    {
        $ext         = addslashes($ext);
        $imageFor    = in_array($imageFor, ['Product', 'Product Mannual']) ? $imageFor : 'Product';
        $title       = addslashes($title);
        $imageName   = addslashes($imageName);
        $displayFlag = in_array($displayFlag, ['Yes', 'No']) ? $displayFlag : 'Yes';
        $hyperLink   = addslashes($hyperLink);
        $date        = date('Y-m-d');

        /* Try full INSERT (with image_name + hyper_link columns).
           If those columns don't exist yet, fall back to base INSERT. */
        $newId = 0;
        try {
            $sql   = "INSERT INTO tbl_product_img(product_id,image_ext,image_name,priorty,display_flag,image_for,product_manual_title,hyper_link,manual_upload_date)
                      VALUES({$productId},'{$ext}','{$imageName}',{$prio},'{$displayFlag}','{$imageFor}','{$title}','{$hyperLink}','{$date}')";
            $newId = (int)$this->db->insert($sql);
        } catch (Exception $e) {
            /* Columns image_name / hyper_link may not exist yet — fall back */
            error_log('addProductImage full INSERT failed, trying base: '.$e->getMessage());
            try {
                $sql   = "INSERT INTO tbl_product_img(product_id,image_ext,priorty,display_flag,image_for,product_manual_title,manual_upload_date)
                          VALUES({$productId},'{$ext}',{$prio},'{$displayFlag}','{$imageFor}','{$title}','{$date}')";
                $newId = (int)$this->db->insert($sql);
            } catch (Exception $e2) {
                error_log('addProductImage base INSERT also failed: '.$e2->getMessage());
                return 0;
            }
        }

        if ($newId > 0) {
            $this->logActivity('add', 'tbl_product_img', $sql ?? '', null,
                ['product_id'=>$productId,'image_ext'=>$ext,'image_name'=>$imageName,
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

            $name        = addslashes(trim($d['name']             ?? ''));
            $logo        = addslashes(trim($d['logo']             ?? ''));
            $description = addslashes(trim($d['description']      ?? ''));
            $contact     = addslashes(trim($d['contact_number']   ?? ''));
            $email       = addslashes(trim($d['email']            ?? ''));
            $address     = addslashes(trim($d['address']          ?? ''));
            $fax         = addslashes(trim($d['fax']              ?? ''));
            $facebook    = addslashes(trim($d['facebook_url']     ?? ''));
            $instagram   = addslashes(trim($d['instagram_url']    ?? ''));
            $linkedin    = addslashes(trim($d['linkedin_url']     ?? ''));
            $twitter     = addslashes(trim($d['twitter_url']      ?? ''));
            $youtube     = addslashes(trim($d['youtube_url']      ?? ''));
            $whatsapp    = addslashes(trim($d['whatsapp_number']  ?? ''));
            $support     = addslashes(trim($d['support_mail_id']  ?? ''));
            $instructions= addslashes(trim($d['instructions']     ?? ''));

            if ($id > 0) {
                $sql = "UPDATE tbl_company SET
                    name='$name', logo='$logo', description='$description',
                    contact_number='$contact', email='$email', address='$address', fax='$fax',
                    facebook_url='$facebook', instagram_url='$instagram', linkedin_url='$linkedin',
                    twitter_url='$twitter', youtube_url='$youtube', whatsapp_number='$whatsapp',
                    support_mail_id='$support', instructions='$instructions'
                    WHERE company_id=$id";
                $this->db->update($sql);
            } else {
                $sql = "INSERT INTO tbl_company
                    (name,logo,description,contact_number,email,address,fax,
                     facebook_url,instagram_url,linkedin_url,twitter_url,youtube_url,
                     whatsapp_number,support_mail_id,instructions)
                    VALUES('$name','$logo','$description','$contact','$email','$address','$fax',
                     '$facebook','$instagram','$linkedin','$twitter','$youtube',
                     '$whatsapp','$support','$instructions')";
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
                "SELECT ua.user_address_id, ua.user_id, ua.label,
                        ua.user_name, ua.company_name,
                        ua.address, ua.address_line_one, ua.address_line_two, ua.landmark,
                        ua.city, ua.state, ua.zip,
                        ua.country_id, ua.country, ua.eu_vat,
                        ua.delivery_phone_no, ua.mobile_country_code,
                        ua.recipient_name, ua.recipient_email, ua.recipient_contact,
                        c.country AS country_name
                 FROM tbl_user_address ua
                 LEFT JOIN tbl_country c ON c.country_id = ua.country_id
                 WHERE ua.user_id = ".$userId."
                 ORDER BY ua.user_address_id ASC"
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
}
?>
