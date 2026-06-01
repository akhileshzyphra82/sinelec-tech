<?php
/**
 * Admin Support AJAX Endpoints
 *
 * POST ?action=save_category     — Add or update a support category
 * POST ?action=toggle_category   — Toggle category active status
 * POST ?action=change_status     — Change ticket status
 * POST ?action=change_priority   — Change ticket priority
 * POST ?action=assign_ticket     — Assign ticket to admin
 * POST ?action=add_note          — Add internal note or reply
 * POST ?action=upload_attachment — Upload attachment to a message
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$admin = $_SESSION['sinelec_admin'] ?? [];
if (empty($admin['USER_ID'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized.']);
    exit;
}

require_once __DIR__ . '/../../common/functions.php';
require_once __DIR__ . '/../../controller/support_controller.php';

function spOut(array $d): never { echo json_encode($d); exit; }

$ctrl       = new SupportController();
$adminId    = (int)(float)($admin['USER_ID']   ?? 0);
$adminName  = (string)($admin['NAME']          ?? 'Admin');
$adminType  = (int)(float)($admin['USER_TYPE_ID'] ?? 1);
$action     = (string)($_GET['action'] ?? '');

$raw  = file_get_contents('php://input');
$data = $raw ? json_decode($raw, true) : [];
if (!is_array($data)) $data = [];

/* ── save_category ── */
if ($action === 'save_category') {
    if (!sinelec_can('add') && !sinelec_can('edit')) spOut(['ok' => false, 'msg' => 'No permission.']);
    $ok = $ctrl->saveCategory($data);
    spOut(['ok' => $ok, 'msg' => $ok ? 'Saved.' : 'Save failed.']);
}

/* ── toggle_category ── */
if ($action === 'toggle_category') {
    if (!sinelec_can('edit')) spOut(['ok' => false, 'msg' => 'No permission.']);
    $catId = (int)(float)($data['category_id'] ?? 0);
    $val   = (int)($data['is_active'] ?? 0);
    if ($catId <= 0) spOut(['ok' => false, 'msg' => 'Invalid category.']);
    $ctrl->toggleCategoryActive($catId, $val);
    spOut(['ok' => true]);
}

/* ── change_status ── */
if ($action === 'change_status') {
    if (!sinelec_can('edit')) spOut(['ok' => false, 'msg' => 'No permission.']);
    $ticketId  = (int)(float)($data['ticket_id'] ?? 0);
    $newStatus = trim((string)($data['status'] ?? ''));
    if ($ticketId <= 0 || $newStatus === '') spOut(['ok' => false, 'msg' => 'Invalid parameters.']);

    $ok = $ctrl->changeStatus($ticketId, $newStatus, $adminId, $adminType, $adminName);
    if (!$ok) spOut(['ok' => false, 'msg' => 'Invalid status value.']);

    // Email user about status change
    $db   = new MySQLDB();
    $rows = $db->select(
        "SELECT t.ticket_number, t.subject, u.communication_email_id AS email, u.name
         FROM tbl_support_ticket t
         LEFT JOIN tbl_user u ON u.user_id = t.user_id
         WHERE t.ticket_id = $ticketId LIMIT 1"
    );
    if (!empty($rows)) {
        $t = $rows[0];
        $ctrl->sendStatusEmail(
            (string)$t->EMAIL,
            (string)$t->NAME,
            (string)$t->TICKET_NUMBER,
            $newStatus
        );
    }

    spOut(['ok' => true, 'new_status' => $newStatus]);
}

/* ── change_priority ── */
if ($action === 'change_priority') {
    if (!sinelec_can('edit')) spOut(['ok' => false, 'msg' => 'No permission.']);
    $ticketId = (int)(float)($data['ticket_id'] ?? 0);
    $priority = trim((string)($data['priority'] ?? ''));
    if ($ticketId <= 0 || $priority === '') spOut(['ok' => false, 'msg' => 'Invalid parameters.']);
    $ctrl->changePriority($ticketId, $priority);
    spOut(['ok' => true]);
}

/* ── assign_ticket ── */
if ($action === 'assign_ticket') {
    if (!sinelec_can('edit')) spOut(['ok' => false, 'msg' => 'No permission.']);
    $ticketId    = (int)(float)($data['ticket_id']     ?? 0);
    $assignAdminId = (int)(float)($data['admin_id']    ?? 0);
    if ($ticketId <= 0) spOut(['ok' => false, 'msg' => 'Invalid ticket.']);
    $ctrl->assignTicket($ticketId, $assignAdminId);
    spOut(['ok' => true]);
}

/* ── add_note (internal note or admin reply) ── */
if ($action === 'add_note') {
    if (!sinelec_can('add')) spOut(['ok' => false, 'msg' => 'No permission.']);
    $ticketId   = (int)(float)($data['ticket_id'] ?? 0);
    $body       = trim((string)($data['body'] ?? ''));
    $isInternal = (bool)($data['is_internal'] ?? false);

    if ($ticketId <= 0 || $body === '') spOut(['ok' => false, 'msg' => 'Message body is required.']);

    // Verify ticket exists
    $db   = new MySQLDB();
    $rows = $db->select(
        "SELECT t.ticket_id, t.ticket_number, t.subject, t.current_status,
                u.communication_email_id AS email, u.name
         FROM tbl_support_ticket t
         LEFT JOIN tbl_user u ON u.user_id = t.user_id
         WHERE t.ticket_id = $ticketId LIMIT 1"
    );
    if (empty($rows)) spOut(['ok' => false, 'msg' => 'Ticket not found.']);
    $ticket = $rows[0];

    $msgId = $ctrl->addMessage($ticketId, $body, $adminId, $adminType, $adminName, $isInternal);
    if (!$msgId) spOut(['ok' => false, 'msg' => 'Failed to add message.']);

    // If public reply, notify the customer by email
    if (!$isInternal) {
        $ctrl->sendReplyEmail(
            (string)$ticket->EMAIL,
            (string)$ticket->NAME,
            (string)$ticket->TICKET_NUMBER,
            (string)$ticket->SUBJECT,
            $body
        );
    }

    spOut(['ok' => true, 'message_id' => $msgId]);
}

/* ── upload_attachment (admin) ── */
if ($action === 'upload_attachment') {
    if (!sinelec_can('add')) spOut(['ok' => false, 'msg' => 'No permission.']);

    $ticketNumber = trim((string)($_POST['ticket_number'] ?? ''));
    $ticketId     = (int)(float)($_POST['ticket_id']     ?? 0);
    $messageId    = (int)(float)($_POST['message_id']    ?? 0);

    if ($ticketNumber === '' || $ticketId <= 0) spOut(['ok' => false, 'msg' => 'Missing ticket reference.']);

    $files    = $_FILES['files'] ?? null;
    if (!$files) spOut(['ok' => false, 'msg' => 'No files provided.']);

    $fileList = [];
    if (is_array($files['name'])) {
        foreach ($files['name'] as $i => $name) {
            $fileList[] = ['name' => $name, 'tmp_name' => $files['tmp_name'][$i], 'size' => $files['size'][$i], 'error' => $files['error'][$i]];
        }
    } else {
        $fileList[] = $files;
    }

    $saved  = [];
    $errors = [];
    foreach ($fileList as $file) {
        $res = $ctrl->uploadAttachment($file, $ticketNumber);
        if (!$res['ok']) { $errors[] = ($file['name'] ?? 'file') . ': ' . $res['msg']; continue; }
        $attId = $ctrl->saveAttachment($messageId > 0 ? $messageId : 0, $ticketId, $res['file_name'], $res['file_path']);
        $saved[] = ['attachment_id' => $attId, 'file_name' => $res['file_name'], 'file_path' => $res['file_path']];
    }

    spOut(['ok' => count($saved) > 0, 'saved' => $saved, 'errors' => $errors]);
}

/* ── search_customers ── */
if ($action === 'search_customers') {
    $q = trim((string)($data['query'] ?? ''));
    if (strlen($q) < 2) spOut(['ok' => true, 'customers' => []]);
    $rows = $ctrl->searchCustomers($q);
    $out  = [];
    foreach ($rows as $r) {
        $out[] = [
            'user_id'      => (int)(float)($r->USER_ID      ?? 0),
            'name'         => (string)($r->NAME             ?? ''),
            'email'        => (string)($r->EMAIL            ?? ''),
            'company_name' => (string)($r->COMPANY_NAME     ?? ''),
        ];
    }
    spOut(['ok' => true, 'customers' => $out]);
}

/* ── create_ticket (admin creates on behalf of customer) ── */
if ($action === 'create_ticket') {
    if (!sinelec_can('add')) spOut(['ok' => false, 'msg' => 'No permission.']);
    $result = $ctrl->createTicketByAdmin($data, $adminId, $adminName);
    spOut($result);
}

/* ── delete_ticket (admin-created tickets only) ── */
if ($action === 'delete_ticket') {
    if (!sinelec_can('delete')) spOut(['ok' => false, 'msg' => 'No permission.']);
    $ticketId = (int)(float)($data['ticket_id'] ?? 0);
    if ($ticketId <= 0) spOut(['ok' => false, 'msg' => 'Invalid ticket.']);
    $ok = $ctrl->deleteTicket($ticketId);
    spOut(['ok' => $ok, 'msg' => $ok ? 'Ticket deleted.' : 'Cannot delete this ticket.']);
}

spOut(['ok' => false, 'msg' => 'Unknown action.']);
