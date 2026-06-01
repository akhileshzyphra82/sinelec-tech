<?php
/**
 * SupportController — Ticketing system data layer
 */
require_once __DIR__ . '/../config/db_helper.php';
require_once __DIR__ . '/../common/functions.php';

class SupportController
{
    private MySQLDB $db;

    public function __construct()
    {
        $this->db = new MySQLDB();
    }

    /* ═══════════════════════════════════════════
       TICKET NUMBER
    ═══════════════════════════════════════════ */
    public function generateTicketNumber(): string
    {
        $year = date('Y');
        $rows = $this->db->select(
            "SELECT MAX(CAST(SUBSTRING(ticket_number, 9) AS UNSIGNED)) AS last_seq
             FROM tbl_support_ticket
             WHERE ticket_number LIKE 'TKT-{$year}%'"
        );
        $seq = (int)(($rows[0] ?? null)?->LAST_SEQ ?? 0) + 1;
        return 'TKT-' . $year . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /* ═══════════════════════════════════════════
       CATEGORIES
    ═══════════════════════════════════════════ */
    public function getCategories(): array
    {
        try {
            return $this->db->select(
                "SELECT category_id, category_name, category_type
                 FROM tbl_support_category
                 WHERE is_active = 1
                 ORDER BY sort_order ASC, category_name ASC"
            );
        } catch (\Throwable $e) {
            error_log('SupportController::getCategories — ' . $e->getMessage());
            return [];
        }
    }

    public function getAllCategoriesAdmin(): array
    {
        try {
            return $this->db->select(
                "SELECT category_id, category_name, category_type, sort_order, is_active, created_at
                 FROM tbl_support_category
                 ORDER BY sort_order ASC, category_name ASC"
            );
        } catch (\Throwable $e) {
            error_log('SupportController::getAllCategoriesAdmin — ' . $e->getMessage());
            return [];
        }
    }

    public function saveCategory(array $data): bool
    {
        $id   = (int)($data['category_id'] ?? 0);
        $name = $this->esc((string)($data['category_name'] ?? ''));
        $type = $this->esc((string)($data['category_type'] ?? 'Other'));
        $sort = (int)($data['sort_order'] ?? 0);

        if ($id > 0) {
            return $this->db->update(
                "UPDATE tbl_support_category
                 SET category_name='$name', category_type='$type', sort_order=$sort
                 WHERE category_id=$id"
            ) >= 0;
        }

        return $this->db->insert(
            "INSERT INTO tbl_support_category (category_name, category_type, sort_order, is_active)
             VALUES ('$name', '$type', $sort, 1)"
        ) > 0;
    }

    public function toggleCategoryActive(int $id, int $val): void
    {
        $val = $val ? 1 : 0;
        $this->db->update(
            "UPDATE tbl_support_category SET is_active=$val WHERE category_id=$id"
        );
    }

    /* ═══════════════════════════════════════════
       ORDER DATA (for new ticket form)
    ═══════════════════════════════════════════ */
    public function getUserOrders(int $userId): array
    {
        try {
            return $this->db->select(
                "SELECT user_order_id, order_number, order_date, final_total_amt, order_status
                 FROM tbl_user_order
                 WHERE user_id = $userId
                 ORDER BY order_date DESC"
            );
        } catch (\Throwable $e) {
            error_log('SupportController::getUserOrders — ' . $e->getMessage());
            return [];
        }
    }

    public function getOrderItems(int $orderId, int $userId): array
    {
        try {
            return $this->db->select(
                "SELECT oi.user_order_item_id, oi.product_id,
                        p.product_name, p.product_code,
                        oi.quantity, oi.product_amt, oi.final_amt,
                        (SELECT pi.product_image_path FROM tbl_product_img pi
                         WHERE pi.product_id = oi.product_id
                         ORDER BY pi.priorty LIMIT 1) AS image_path
                 FROM tbl_user_order_item oi
                 LEFT JOIN tbl_product p ON p.product_id = oi.product_id
                 WHERE oi.user_order_id = $orderId
                   AND oi.item_status = 'Active'
                   AND oi.order_type = 'Order'
                   AND (SELECT user_id FROM tbl_user_order WHERE user_order_id = $orderId LIMIT 1) = $userId"
            );
        } catch (\Throwable $e) {
            error_log('SupportController::getOrderItems — ' . $e->getMessage());
            return [];
        }
    }

    public function validateReturnQty(int $orderItemId, int $qty): bool
    {
        $rows = $this->db->select(
            "SELECT quantity FROM tbl_user_order_item WHERE user_order_item_id=$orderItemId"
        );
        if (empty($rows)) return false;
        $ordered = (int)$rows[0]->QUANTITY;
        return $qty >= 1 && $qty <= $ordered;
    }

    /* ═══════════════════════════════════════════
       CREATE TICKET
    ═══════════════════════════════════════════ */
    public function createTicket(array $data, int $userId, string $userName, string $userEmail): array
    {
        try {
            $ticketNumber = $this->generateTicketNumber();
            $categoryId   = (int)($data['category_id'] ?? 0);
            $orderId      = (int)($data['order_id'] ?? 0);
            $subject      = $this->esc(trim((string)($data['subject'] ?? '')));
            $description  = trim((string)($data['description'] ?? ''));
            $returnItems  = (array)($data['return_items'] ?? []);

            if (!$categoryId || !$subject) {
                return ['ok' => false, 'msg' => 'Category and subject are required.'];
            }

            $orderVal = $orderId > 0 ? $orderId : 'NULL';

            // 1. Insert ticket
            $ticketId = $this->db->insert(
                "INSERT INTO tbl_support_ticket
                    (ticket_number, user_id, order_id, category_id, subject, current_status, priority)
                 VALUES
                    ('$ticketNumber', $userId, $orderVal, $categoryId, '$subject', 'Open', 'Normal')"
            );

            if (!$ticketId) {
                return ['ok' => false, 'msg' => 'Failed to create ticket. Please try again.'];
            }

            // 2. Insert return items if any
            foreach ($returnItems as $item) {
                $orderItemId = (int)($item['order_item_id'] ?? 0);
                $returnQty   = (int)($item['return_qty']   ?? 0);
                $reason      = $this->esc(trim((string)($item['return_reason'] ?? '')));
                $productId   = (int)($item['product_id']   ?? 0);
                $itemOrderId = (int)($item['order_id']     ?? $orderId);

                if (!$orderItemId || $returnQty < 1) continue;
                if (!$this->validateReturnQty($orderItemId, $returnQty)) continue;

                $productVal = $productId > 0 ? $productId : 'NULL';
                $reasonVal  = $reason !== '' ? "'$reason'" : 'NULL';

                $this->db->insert(
                    "INSERT INTO tbl_support_return_item
                        (ticket_id, order_id, order_item_id, product_id, return_qty, return_reason)
                     VALUES ($ticketId, $itemOrderId, $orderItemId, $productVal, $returnQty, $reasonVal)"
                );
            }

            // 3. Insert first message (user description) — always insert so attachments have a valid message_id
            $senderName   = $this->esc($userName);
            $firstMsgBody = $description !== '' ? $description : $subject;
            $descEsc      = $this->esc($firstMsgBody);
            $firstMsgId   = (int)$this->db->insert(
                "INSERT INTO tbl_support_message
                    (ticket_id, last_status, user_type_id, sender_id, sender_name, message_body,
                     is_internal_note, is_auto_reply)
                 VALUES ($ticketId, 'Open', 2, $userId, '$senderName', '$descEsc', 0, 0)"
            );

            // 4. Insert auto-reply (sender_id NULL — no real user, FK requires NULL not 0)
            $autoBody = $this->esc('Thank you for contacting us. Our support executive will reply to you soon. Your ticket number is ' . $ticketNumber . '.');
            $this->db->insert(
                "INSERT INTO tbl_support_message
                    (ticket_id, last_status, user_type_id, sender_id, sender_name, message_body,
                     is_internal_note, is_auto_reply)
                 VALUES ($ticketId, 'Open', 1, NULL, 'Sinelec Support', '$autoBody', 0, 1)"
            );

            // 5. Send emails
            $this->sendTicketCreatedEmail($userEmail, $userName, $ticketNumber, $subject);
            $this->notifySupport($ticketNumber, $userName, $subject, $description);

            return [
                'ok'            => true,
                'ticket_id'     => $ticketId,
                'ticket_number' => $ticketNumber,
                'message_id'    => $firstMsgId,  // needed by upload so FK is satisfied
            ];

        } catch (\Throwable $e) {
            error_log('SupportController::createTicket — ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'An error occurred. Please try again.'];
        }
    }

    /* ═══════════════════════════════════════════
       GET TICKETS (website user)
    ═══════════════════════════════════════════ */
    public function getUserTickets(int $userId, string $status = ''): array
    {
        $where = "WHERE t.user_id = $userId";
        if ($status !== '' && $status !== 'All') {
            $s = $this->esc($status);
            $where .= " AND t.current_status = '$s'";
        }

        try {
            return $this->db->select(
                "SELECT t.ticket_id, t.ticket_number, t.subject, t.current_status, t.priority,
                        t.created_at, t.updated_at,
                        c.category_name, c.category_type,
                        o.order_number,
                        (SELECT COUNT(*) FROM tbl_support_message m
                         WHERE m.ticket_id = t.ticket_id AND m.is_internal_note = 0) AS msg_count,
                        (SELECT message_body FROM tbl_support_message m2
                         WHERE m2.ticket_id = t.ticket_id AND m2.is_internal_note = 0
                         ORDER BY m2.created_at DESC LIMIT 1) AS last_message,
                        (SELECT created_at FROM tbl_support_message m3
                         WHERE m3.ticket_id = t.ticket_id AND m3.is_internal_note = 0
                         ORDER BY m3.created_at DESC LIMIT 1) AS last_msg_at
                 FROM tbl_support_ticket t
                 LEFT JOIN tbl_support_category c ON c.category_id = t.category_id
                 LEFT JOIN tbl_user_order o ON o.user_order_id = t.order_id
                 $where
                 ORDER BY t.updated_at DESC"
            );
        } catch (\Throwable $e) {
            error_log('SupportController::getUserTickets — ' . $e->getMessage());
            return [];
        }
    }

    /* ═══════════════════════════════════════════
       GET TICKET DETAIL (website)
    ═══════════════════════════════════════════ */
    public function getTicketByNumber(string $ticketNumber, int $userId): ?object
    {
        try {
            $tn = $this->esc($ticketNumber);
            $rows = $this->db->select(
                "SELECT t.*, c.category_name, c.category_type, o.order_number
                 FROM tbl_support_ticket t
                 LEFT JOIN tbl_support_category c ON c.category_id = t.category_id
                 LEFT JOIN tbl_user_order o ON o.user_order_id = t.order_id
                 WHERE t.ticket_number = '$tn' AND t.user_id = $userId
                 LIMIT 1"
            );
            return $rows[0] ?? null;
        } catch (\Throwable $e) {
            error_log('SupportController::getTicketByNumber — ' . $e->getMessage());
            return null;
        }
    }

    /* ═══════════════════════════════════════════
       GET TICKET BY ID (admin)
    ═══════════════════════════════════════════ */
    public function getTicketById(int $ticketId): ?object
    {
        try {
            $rows = $this->db->select(
                "SELECT t.*, c.category_name, c.category_type,
                        o.order_number, o.final_total_amt,
                        u.name AS user_name, u.communication_email_id AS user_email, u.communication_mobile_num AS user_mobile
                 FROM tbl_support_ticket t
                 LEFT JOIN tbl_support_category c ON c.category_id = t.category_id
                 LEFT JOIN tbl_user_order o ON o.user_order_id = t.order_id
                 LEFT JOIN tbl_user u ON u.user_id = t.user_id
                 WHERE t.ticket_id = $ticketId
                 LIMIT 1"
            );
            return $rows[0] ?? null;
        } catch (\Throwable $e) {
            error_log('SupportController::getTicketById — ' . $e->getMessage());
            return null;
        }
    }

    /* ═══════════════════════════════════════════
       GET ALL TICKETS (admin list)
    ═══════════════════════════════════════════ */
    public function getAllTickets(array $filters = []): array
    {
        $where = 'WHERE 1=1';

        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $s = $this->esc($filters['status']);
            $where .= " AND t.current_status = '$s'";
        }
        if (!empty($filters['category_id'])) {
            $where .= ' AND t.category_id = ' . (int)$filters['category_id'];
        }
        if (!empty($filters['priority'])) {
            $p = $this->esc($filters['priority']);
            $where .= " AND t.priority = '$p'";
        }
        if (!empty($filters['assigned_admin_id'])) {
            $where .= ' AND t.assigned_admin_id = ' . (int)$filters['assigned_admin_id'];
        }
        if (!empty($filters['search'])) {
            $q = $this->esc($filters['search']);
            $where .= " AND (t.ticket_number LIKE '%$q%' OR t.subject LIKE '%$q%' OR u.name LIKE '%$q%')";
        }
        if (!empty($filters['date_from'])) {
            $df = $this->esc($filters['date_from']);
            $where .= " AND DATE(t.created_at) >= '$df'";
        }
        if (!empty($filters['date_to'])) {
            $dt = $this->esc($filters['date_to']);
            $where .= " AND DATE(t.created_at) <= '$dt'";
        }

        $limit  = (int)($filters['limit']  ?? 50);
        $offset = (int)($filters['offset'] ?? 0);

        try {
            return $this->db->select(
                "SELECT t.ticket_id, t.ticket_number, t.subject, t.current_status, t.priority,
                        t.created_at, t.updated_at, t.assigned_admin_id, t.is_admin_created,
                        c.category_name, c.category_type,
                        u.name AS user_name, u.communication_email_id AS user_email,
                        o.order_number,
                        (SELECT COUNT(*) FROM tbl_support_message m
                         WHERE m.ticket_id = t.ticket_id AND m.is_internal_note = 0) AS msg_count,
                        (SELECT created_at FROM tbl_support_message m2
                         WHERE m2.ticket_id = t.ticket_id
                         ORDER BY m2.created_at DESC LIMIT 1) AS last_msg_at,
                        adm.name AS assigned_admin_name
                 FROM tbl_support_ticket t
                 LEFT JOIN tbl_support_category c ON c.category_id = t.category_id
                 LEFT JOIN tbl_user u ON u.user_id = t.user_id
                 LEFT JOIN tbl_user_order o ON o.user_order_id = t.order_id
                 LEFT JOIN tbl_user adm ON adm.user_id = t.assigned_admin_id
                 $where
                 ORDER BY t.updated_at DESC
                 LIMIT $limit OFFSET $offset"
            );
        } catch (\Throwable $e) {
            error_log('SupportController::getAllTickets — ' . $e->getMessage());
            return [];
        }
    }

    public function countAllTickets(array $filters = []): int
    {
        $where = 'WHERE 1=1';
        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $s = $this->esc($filters['status']);
            $where .= " AND t.current_status = '$s'";
        }
        if (!empty($filters['search'])) {
            $q = $this->esc($filters['search']);
            $where .= " AND (t.ticket_number LIKE '%$q%' OR t.subject LIKE '%$q%' OR u.name LIKE '%$q%')";
        }
        try {
            $rows = $this->db->select(
                "SELECT COUNT(*) AS cnt FROM tbl_support_ticket t
                 LEFT JOIN tbl_user u ON u.user_id = t.user_id $where"
            );
            return (int)(($rows[0] ?? null)?->CNT ?? 0);
        } catch (\Throwable $e) {
            error_log('SupportController::countAllTickets — ' . $e->getMessage());
            return 0;
        }
    }

    /* ═══════════════════════════════════════════
       MESSAGES
    ═══════════════════════════════════════════ */
    public function getTicketMessages(int $ticketId, bool $includeInternal = false): array
    {
        try {
            $internalClause = $includeInternal ? '' : 'AND m.is_internal_note = 0';
            // Use LEFT JOIN + GROUP BY instead of a correlated subquery — more reliable
            // with mysqli bind_result() across all MySQL/PHP driver combinations.
            return $this->db->select(
                "SELECT m.message_id, m.ticket_id, m.last_status, m.user_type_id,
                        m.sender_id, m.sender_name, m.message_body,
                        m.is_internal_note, m.is_auto_reply, m.created_at,
                        GROUP_CONCAT(
                            CONCAT(a.attachment_id, '|', a.file_name, '|', a.file_path)
                            ORDER BY a.attachment_id ASC
                            SEPARATOR ';;'
                        ) AS attachments_raw
                 FROM tbl_support_message m
                 LEFT JOIN tbl_support_attachment a ON a.message_id = m.message_id
                 WHERE m.ticket_id = $ticketId $internalClause
                 GROUP BY m.message_id, m.ticket_id, m.last_status, m.user_type_id,
                          m.sender_id, m.sender_name, m.message_body,
                          m.is_internal_note, m.is_auto_reply, m.created_at
                 ORDER BY m.created_at ASC"
            );
        } catch (\Throwable $e) {
            error_log('SupportController::getTicketMessages — ' . $e->getMessage());
            return [];
        }
    }

    public function addMessage(
        int    $ticketId,
        string $body,
        int    $userId,
        int    $userTypeId,
        string $senderName,
        bool   $isInternalNote = false
    ): int {
        // Get current ticket status to snapshot as last_status
        $rows = $this->db->select(
            "SELECT current_status FROM tbl_support_ticket WHERE ticket_id=$ticketId LIMIT 1"
        );
        $lastStatus = (string)(($rows[0] ?? null)?->CURRENT_STATUS ?? 'Open');
        $lastStatus = $this->esc($lastStatus);

        $bodyEsc   = $this->esc($body);
        $nameEsc   = $this->esc($senderName);
        $internal  = $isInternalNote ? 1 : 0;

        $msgId = $this->db->insert(
            "INSERT INTO tbl_support_message
                (ticket_id, last_status, user_type_id, sender_id, sender_name, message_body,
                 is_internal_note, is_auto_reply)
             VALUES ($ticketId, '$lastStatus', $userTypeId, $userId, '$nameEsc', '$bodyEsc',
                     $internal, 0)"
        );

        // Update ticket updated_at
        $this->db->update("UPDATE tbl_support_ticket SET updated_at=NOW() WHERE ticket_id=$ticketId");

        return (int)$msgId;
    }

    /* ═══════════════════════════════════════════
       CREATE TICKET BY ADMIN (on behalf of customer)
    ═══════════════════════════════════════════ */
    public function createTicketByAdmin(array $data, int $adminId, string $adminName): array
    {
        try {
            $ticketNumber = $this->generateTicketNumber();
            $userId       = (int)($data['user_id']     ?? 0);
            $categoryId   = (int)($data['category_id'] ?? 0);
            $subject      = $this->esc(trim((string)($data['subject']      ?? '')));
            $description  = trim((string)($data['description'] ?? ''));
            $priority     = $this->esc((string)($data['priority'] ?? 'Normal'));

            if (!$userId || !$categoryId || !$subject) {
                return ['ok' => false, 'msg' => 'Customer, category and subject are required.'];
            }

            // Verify user exists and is a customer
            $uRows = $this->db->select(
                "SELECT user_id, name, communication_email_id FROM tbl_user
                 WHERE user_id = $userId AND user_type_id = 2 LIMIT 1"
            );
            if (empty($uRows)) {
                return ['ok' => false, 'msg' => 'Customer not found.'];
            }
            $customer = $uRows[0];

            $ticketId = $this->db->insert(
                "INSERT INTO tbl_support_ticket
                    (ticket_number, user_id, category_id, subject, current_status,
                     priority, assigned_admin_id, is_admin_created)
                 VALUES
                    ('$ticketNumber', $userId, $categoryId, '$subject', 'Open',
                     '$priority', $adminId, 1)"
            );
            if (!$ticketId) {
                return ['ok' => false, 'msg' => 'Failed to create ticket.'];
            }

            // Insert description as first message (from admin, on behalf of customer)
            if ($description !== '') {
                $descEsc   = $this->esc($description);
                $nameEsc   = $this->esc($adminName);
                $this->db->insert(
                    "INSERT INTO tbl_support_message
                        (ticket_id, last_status, user_type_id, sender_id, sender_name,
                         message_body, is_internal_note, is_auto_reply)
                     VALUES ($ticketId, 'Open', 1, $adminId, '$nameEsc', '$descEsc', 0, 0)"
                );
            }

            return [
                'ok'            => true,
                'ticket_id'     => $ticketId,
                'ticket_number' => $ticketNumber,
            ];
        } catch (\Throwable $e) {
            error_log('SupportController::createTicketByAdmin — ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'An error occurred. Please try again.'];
        }
    }

    /* ═══════════════════════════════════════════
       DELETE TICKET (admin-created only)
    ═══════════════════════════════════════════ */
    public function deleteTicket(int $ticketId): bool
    {
        try {
            // Only delete admin-created tickets
            $rows = $this->db->select(
                "SELECT ticket_id FROM tbl_support_ticket
                 WHERE ticket_id = $ticketId AND is_admin_created = 1 LIMIT 1"
            );
            if (empty($rows)) return false;

            // Delete attachments, messages, return items, then ticket
            $this->db->update(
                "DELETE a FROM tbl_support_attachment a
                 JOIN tbl_support_message m ON m.message_id = a.message_id
                 WHERE m.ticket_id = $ticketId"
            );
            $this->db->update("DELETE FROM tbl_support_message     WHERE ticket_id = $ticketId");
            $this->db->update("DELETE FROM tbl_support_return_item WHERE ticket_id = $ticketId");
            $this->db->update("DELETE FROM tbl_support_ticket       WHERE ticket_id = $ticketId AND is_admin_created = 1");
            return true;
        } catch (\Throwable $e) {
            error_log('SupportController::deleteTicket — ' . $e->getMessage());
            return false;
        }
    }

    /* ═══════════════════════════════════════════
       SEARCH CUSTOMERS (for Add Ticket modal)
    ═══════════════════════════════════════════ */
    public function searchCustomers(string $query): array
    {
        try {
            $q = $this->esc($query);
            return $this->db->select(
                "SELECT user_id, name, communication_email_id AS email, company_name
                 FROM tbl_user
                 WHERE user_type_id = 2
                   AND (name LIKE '%$q%' OR communication_email_id LIKE '%$q%' OR company_name LIKE '%$q%')
                 ORDER BY name ASC
                 LIMIT 20"
            );
        } catch (\Throwable $e) {
            error_log('SupportController::searchCustomers — ' . $e->getMessage());
            return [];
        }
    }

    public function getAllCustomers(): array
    {
        try {
            return $this->db->select(
                "SELECT user_id,
                        name AS user_name,
                        communication_email_id AS user_email,
                        IFNULL(company_name,'') AS company_name
                 FROM tbl_user
                 WHERE user_type_id = 2
                 ORDER BY name ASC"
            );
        } catch (\Throwable $e) {
            error_log('SupportController::getAllCustomers — ' . $e->getMessage());
            return [];
        }
    }

    /* ═══════════════════════════════════════════
       STATUS CHANGE
    ═══════════════════════════════════════════ */
    public function changeStatus(
        int    $ticketId,
        string $newStatus,
        int    $actorId,
        int    $actorTypeId,
        string $actorName
    ): bool {
        $valid = ['Open', 'In Progress', 'Resolved', 'Closed', 'Reopened'];
        if (!in_array($newStatus, $valid, true)) return false;

        $statusEsc = $this->esc($newStatus);
        $nameEsc   = $this->esc($actorName);
        $closedVal = in_array($newStatus, ['Closed']) ? ", closed_at=NOW()" : '';

        $this->db->update(
            "UPDATE tbl_support_ticket
             SET current_status='$statusEsc', updated_at=NOW() $closedVal
             WHERE ticket_id=$ticketId"
        );

        // Insert system message to record the status change in thread
        $sysBody    = $this->esc("Status changed to $newStatus");
        $actorIdVal = $actorId > 0 ? $actorId : 'NULL';
        $this->db->insert(
            "INSERT INTO tbl_support_message
                (ticket_id, last_status, user_type_id, sender_id, sender_name, message_body,
                 is_internal_note, is_auto_reply)
             VALUES ($ticketId, '$statusEsc', $actorTypeId, $actorIdVal, '$nameEsc', '$sysBody', 0, 0)"
        );

        return true;
    }

    /* ═══════════════════════════════════════════
       ASSIGN / PRIORITY (admin)
    ═══════════════════════════════════════════ */
    public function assignTicket(int $ticketId, int $adminId): void
    {
        $this->db->update(
            "UPDATE tbl_support_ticket
             SET assigned_admin_id=$adminId, updated_at=NOW()
             WHERE ticket_id=$ticketId"
        );
    }

    public function changePriority(int $ticketId, string $priority): void
    {
        $valid = ['Low', 'Normal', 'High', 'Urgent'];
        if (!in_array($priority, $valid, true)) return;
        $p = $this->esc($priority);
        $this->db->update(
            "UPDATE tbl_support_ticket SET priority='$p', updated_at=NOW() WHERE ticket_id=$ticketId"
        );
    }

    /* ═══════════════════════════════════════════
       RETURN ITEMS
    ═══════════════════════════════════════════ */
    public function getReturnItems(int $ticketId): array
    {
        try {
            return $this->db->select(
                "SELECT ri.return_item_id, ri.order_item_id, ri.product_id,
                        ri.return_qty, ri.return_reason,
                        p.product_name, p.product_code,
                        oi.quantity AS ordered_qty, oi.product_amt
                 FROM tbl_support_return_item ri
                 LEFT JOIN tbl_user_order_item oi ON oi.user_order_item_id = ri.order_item_id
                 LEFT JOIN tbl_product p ON p.product_id = ri.product_id
                 WHERE ri.ticket_id = $ticketId"
            );
        } catch (\Throwable $e) {
            error_log('SupportController::getReturnItems — ' . $e->getMessage());
            return [];
        }
    }

    /* ═══════════════════════════════════════════
       ATTACHMENTS
    ═══════════════════════════════════════════ */
    public function saveAttachment(int $messageId, int $ticketId, string $fileName, string $filePath): int
    {
        try {
            // The file_name column uses latin1 charset — strip any multi-byte UTF-8 characters
            // (e.g. Mac OS narrow-no-break-space U+202F in screenshot names) to prevent
            // "Incorrect string value" errors. Replace out-of-range chars with underscore.
            $safeFileName = preg_replace('/[^\x20-\x7E]/u', '_', $fileName) ?? $fileName;
            $safeFileName = trim($safeFileName, " \t_");
            if ($safeFileName === '') {
                $ext          = pathinfo($fileName, PATHINFO_EXTENSION);
                $safeFileName = 'attachment' . ($ext !== '' ? '.' . $ext : '');
            }

            $fn  = $this->esc($safeFileName);
            $fp  = $this->esc($filePath);
            return (int)$this->db->insert(
                "INSERT INTO tbl_support_attachment (message_id, ticket_id, file_name, file_path)
                 VALUES ($messageId, $ticketId, '$fn', '$fp')"
            );
        } catch (\Throwable $e) {
            error_log('SupportController::saveAttachment — ' . $e->getMessage() . " | msgId=$messageId, ticketId=$ticketId, file=$fileName");
            return 0;
        }
    }

    public function getAdminUsers(): array
    {
        try {
            return $this->db->select(
                "SELECT user_id, name FROM tbl_user
                 WHERE user_type_id IN (1, 3)
                 ORDER BY name ASC"
            );
        } catch (\Throwable $e) {
            error_log('SupportController::getAdminUsers — ' . $e->getMessage());
            return [];
        }
    }

    /* ═══════════════════════════════════════════
       UPLOAD FILE (local)
    ═══════════════════════════════════════════ */
    public function uploadAttachment(array $file, string $ticketNumber): array
    {
        $allowed    = ['jpg','jpeg','png','webp','gif','pdf'];
        $maxBytes   = 5 * 1024 * 1024;  // 5 MB
        $maxFiles   = 5;

        $originalName = (string)($file['name'] ?? '');
        $tmpPath      = (string)($file['tmp_name'] ?? '');
        $size         = (int)($file['size'] ?? 0);
        $error        = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'msg' => 'Upload error code: ' . $error];
        }
        if ($size > $maxBytes) {
            return ['ok' => false, 'msg' => 'File too large. Maximum 5 MB.'];
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            return ['ok' => false, 'msg' => 'File type not allowed. Use: ' . implode(', ', $allowed)];
        }

        // Sanitise filename
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $safeName = substr($safeName, 0, 80);
        $uniqueName = time() . '_' . $safeName . '.' . $ext;

        $uploadDir = __DIR__ . '/../uploads/support/' . $ticketNumber . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destPath = $uploadDir . $uniqueName;
        if (!move_uploaded_file($tmpPath, $destPath)) {
            return ['ok' => false, 'msg' => 'Failed to save file.'];
        }

        $publicPath = 'uploads/support/' . $ticketNumber . '/' . $uniqueName;
        return [
            'ok'          => true,
            'file_name'   => $originalName,
            'file_path'   => $publicPath,
            'unique_name' => $uniqueName,
        ];
    }

    /* ═══════════════════════════════════════════
       STATS (admin dashboard widget)
    ═══════════════════════════════════════════ */
    public function getTicketStats(): array
    {
        $stats = ['Open' => 0, 'In Progress' => 0, 'Resolved' => 0, 'Closed' => 0, 'Reopened' => 0];
        try {
            $rows = $this->db->select(
                "SELECT current_status, COUNT(*) AS cnt
                 FROM tbl_support_ticket
                 GROUP BY current_status"
            );
            foreach ($rows as $r) {
                $stats[(string)($r->CURRENT_STATUS ?? '')] = (int)($r->CNT ?? 0);
            }
        } catch (\Throwable $e) {
            error_log('SupportController::getTicketStats — ' . $e->getMessage());
        }
        $stats['Total'] = array_sum($stats);
        return $stats;
    }

    /* ═══════════════════════════════════════════
       EMAIL HELPERS
    ═══════════════════════════════════════════ */
    public function sendTicketCreatedEmail(string $toEmail, string $userName, string $ticketNumber, string $subject): void
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return;
        $body = $this->emailTemplate(
            "Support Ticket Created — $ticketNumber",
            htmlspecialchars($userName),
            "Your support ticket has been received. Our team will respond shortly.",
            [
                ['label' => 'Ticket #',  'value' => $ticketNumber],
                ['label' => 'Subject',   'value' => $subject],
                ['label' => 'Status',    'value' => 'Open'],
            ],
            "You can track your ticket status in your account under <strong>Support &amp; Help</strong>."
        );
        sinelec_send_mail([[
            'to_mail_id' => $toEmail,
            'subject'    => "[Ticket $ticketNumber] Support request received",
            'body'       => $body,
        ]]);
    }

    public function notifySupport(string $ticketNumber, string $userName, string $subject, string $desc): void
    {
        $supportEmail = (string)sinelec_env('MAIL_FROM_ADDRESS', '');
        if (!filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) return;
        $truncDesc = htmlspecialchars(mb_substr($desc, 0, 300));
        $body = $this->emailTemplate(
            "New Ticket — $ticketNumber",
            "Support Team",
            "A new support ticket has been submitted.",
            [
                ['label' => 'Ticket #',  'value' => $ticketNumber],
                ['label' => 'Customer',  'value' => $userName],
                ['label' => 'Subject',   'value' => $subject],
            ],
            "<strong>Description:</strong><br>$truncDesc"
        );
        sinelec_send_mail([[
            'to_mail_id' => $supportEmail,
            'subject'    => "[New Ticket $ticketNumber] $subject",
            'body'       => $body,
        ]]);
    }

    public function sendReplyEmail(string $toEmail, string $userName, string $ticketNumber, string $subject, string $replyBody): void
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return;
        $preview = htmlspecialchars(mb_substr(strip_tags($replyBody), 0, 300));
        $body = $this->emailTemplate(
            "New Reply on Ticket $ticketNumber",
            htmlspecialchars($userName),
            "Our support team has replied to your ticket.",
            [
                ['label' => 'Ticket #', 'value' => $ticketNumber],
                ['label' => 'Subject',  'value' => $subject],
            ],
            "<strong>Reply:</strong><br>$preview"
        );
        sinelec_send_mail([[
            'to_mail_id' => $toEmail,
            'subject'    => "[Ticket $ticketNumber] New reply from support",
            'body'       => $body,
        ]]);
    }

    public function sendStatusEmail(string $toEmail, string $userName, string $ticketNumber, string $newStatus): void
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return;
        $body = $this->emailTemplate(
            "Ticket $ticketNumber — Status Updated",
            htmlspecialchars($userName),
            "Your ticket status has been updated.",
            [
                ['label' => 'Ticket #',   'value' => $ticketNumber],
                ['label' => 'New Status', 'value' => $newStatus],
            ],
            $newStatus === 'Resolved'
                ? 'If your issue is resolved, no further action is needed. You can also reopen the ticket from your account.'
                : 'You can view the full ticket thread in your account under <strong>Support &amp; Help</strong>.'
        );
        sinelec_send_mail([[
            'to_mail_id' => $toEmail,
            'subject'    => "[Ticket $ticketNumber] Status changed to $newStatus",
            'body'       => $body,
        ]]);
    }

    private function emailTemplate(string $heading, string $recipientName, string $intro, array $details, string $footer): string
    {
        $rows = '';
        foreach ($details as $d) {
            $label = htmlspecialchars($d['label']);
            $value = htmlspecialchars($d['value']);
            $rows .= "<tr><td style='padding:6px 0;color:#64748b;font-size:13px;width:110px;vertical-align:top'>$label</td><td style='padding:6px 0;font-size:13px;font-weight:600;color:#1a2332'>$value</td></tr>";
        }
        $year = date('Y');
        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#EEF2F7;font-family:'Segoe UI',Inter,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#EEF2F7;padding:32px 0">
  <tr><td align="center">
    <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:14px;overflow:hidden;max-width:520px;box-shadow:0 4px 24px rgba(0,0,0,.10)">
      <tr><td style="background:#0a1a30;padding:26px 36px;text-align:center">
        <p style="margin:0;color:#fff;font-size:20px;font-weight:700;letter-spacing:.5px">Sinelec Technologies</p>
        <p style="margin:6px 0 0;color:#93c5fd;font-size:12px">Support Centre</p>
      </td></tr>
      <tr><td style="padding:32px 36px 24px">
        <h2 style="margin:0 0 6px;color:#0a1a30;font-size:20px;font-weight:700">{$heading}</h2>
        <p style="margin:0 0 20px;color:#4a5568;font-size:14px">Hi {$recipientName}, {$intro}</p>
        <table cellpadding="0" cellspacing="0" style="width:100%;background:#f8fafc;border-radius:10px;padding:16px;margin-bottom:20px">
          <tr><td><table width="100%" cellpadding="0" cellspacing="0">$rows</table></td></tr>
        </table>
        <p style="margin:0;color:#64748b;font-size:13px;line-height:1.7">{$footer}</p>
      </td></tr>
      <tr><td style="background:#f7f9fc;padding:16px 36px;text-align:center;border-top:1px solid #e2e8f0">
        <p style="margin:0;color:#a0aec0;font-size:12px">&copy; {$year} Sinelec Technologies. All rights reserved.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;
    }

    /* ═══════════════════════════════════════════
       UTILITY
    ═══════════════════════════════════════════ */
    private function esc(string $val): string
    {
        return addslashes($val);
    }

    public function fmtDate(string $d): string
    {
        $ts = strtotime($d);
        return $ts ? date('M d, Y', $ts) : $d;
    }

    public function fmtDateTime(string $d): string
    {
        $ts = strtotime($d);
        return $ts ? date('M d, Y, H:i', $ts) : $d;
    }

    public function timeAgo(string $d): string
    {
        $ts   = strtotime($d);
        $diff = time() - $ts;
        if ($diff < 60)     return 'Just now';
        if ($diff < 3600)   return floor($diff / 60) . 'm ago';
        if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M d, Y', $ts);
    }
}
