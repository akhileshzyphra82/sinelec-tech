<?php
/**
 * Support Ticket AJAX Endpoints
 *
 * POST ?action=create  — Create a new ticket (JSON body)
 * POST ?action=reply   — Add a message to an existing ticket (JSON body)
 * POST ?action=upload  — Upload attachment(s) to a ticket (multipart/form-data)
 * POST ?action=status  — User status actions: reopen or confirm-resolved (JSON body)
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../common/functions.php';
require_once __DIR__ . '/../../controller/support_controller.php';

function tkOut(array $data): never
{
    echo json_encode($data);
    exit;
}

// Auth check
$user = $_SESSION['sinelec_user'] ?? [];
$userId = (int)(float)($user['USER_ID'] ?? 0);
if ($userId <= 0) {
    tkOut(['ok' => false, 'msg' => 'Not authenticated.']);
}

$userTypeId = (int)(float)($user['USER_TYPE_ID'] ?? 2);
$userName   = (string)($user['NAME']  ?? 'Customer');
$userEmail  = (string)($user['EMAIL'] ?? '');

$ctrl   = new SupportController();
$action = (string)($_GET['action'] ?? '');

/* ════════════════════════════════════════════════════
   ACTION: create
════════════════════════════════════════════════════ */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw  = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;

    if (!is_array($data)) {
        tkOut(['ok' => false, 'msg' => 'Invalid request payload.']);
    }

    $result = $ctrl->createTicket($data, $userId, $userName, $userEmail);
    tkOut($result);
}

/* ════════════════════════════════════════════════════
   ACTION: reply
════════════════════════════════════════════════════ */
if ($action === 'reply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw  = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;

    if (!is_array($data)) {
        tkOut(['ok' => false, 'msg' => 'Invalid request payload.']);
    }

    $ticketId = (int)(float)($data['ticket_id'] ?? 0);
    $body     = trim((string)($data['body'] ?? ''));

    if ($ticketId <= 0 || $body === '') {
        tkOut(['ok' => false, 'msg' => 'Message body is required.']);
    }

    // Verify the ticket belongs to this user
    $rows = (new MySQLDB())->select(
        "SELECT t.ticket_id, t.ticket_number, t.subject, t.current_status,
                u.communication_email_id AS user_email, u.name AS user_name
         FROM tbl_support_ticket t
         LEFT JOIN tbl_user u ON u.user_id = t.user_id
         WHERE t.ticket_id = $ticketId AND t.user_id = $userId
         LIMIT 1"
    );
    if (empty($rows)) {
        tkOut(['ok' => false, 'msg' => 'Ticket not found.']);
    }
    $ticket = $rows[0];

    // Closed tickets cannot receive replies
    if ($ticket->CURRENT_STATUS === 'Closed') {
        tkOut(['ok' => false, 'msg' => 'This ticket is closed. Please reopen it first.']);
    }

    $msgId = $ctrl->addMessage($ticketId, $body, $userId, $userTypeId, $userName, false);

    if (!$msgId) {
        tkOut(['ok' => false, 'msg' => 'Failed to send message. Please try again.']);
    }

    // Notify support team by email
    $ctrl->notifySupport(
        (string)$ticket->TICKET_NUMBER,
        $userName,
        (string)$ticket->SUBJECT,
        $body
    );

    tkOut([
        'ok'         => true,
        'message_id' => $msgId,
    ]);
}

/* ════════════════════════════════════════════════════
   ACTION: upload
════════════════════════════════════════════════════ */
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketNumber = trim((string)($_POST['ticket_number'] ?? ''));
    $ticketId     = (int)(float)($_POST['ticket_id'] ?? 0);
    $messageId    = (int)(float)($_POST['message_id'] ?? 0);

    if ($ticketNumber === '' || $ticketId <= 0) {
        tkOut(['ok' => false, 'msg' => 'Missing ticket reference.']);
    }

    // Verify ownership
    $db   = new MySQLDB();
    $rows = $db->select(
        "SELECT ticket_id FROM tbl_support_ticket
         WHERE ticket_id = $ticketId AND user_id = $userId LIMIT 1"
    );
    if (empty($rows)) {
        tkOut(['ok' => false, 'msg' => 'Ticket not found.']);
    }

    $files = $_FILES['files'] ?? null;
    if (!$files) {
        tkOut(['ok' => false, 'msg' => 'No files provided.']);
    }

    // Normalise to array of individual file entries
    $fileList = [];
    if (is_array($files['name'])) {
        foreach ($files['name'] as $i => $name) {
            $fileList[] = [
                'name'     => $name,
                'tmp_name' => $files['tmp_name'][$i],
                'size'     => $files['size'][$i],
                'error'    => $files['error'][$i],
            ];
        }
    } else {
        $fileList[] = $files;
    }

    $saved   = [];
    $errors  = [];

    foreach ($fileList as $file) {
        $res = $ctrl->uploadAttachment($file, $ticketNumber);
        if (!$res['ok']) {
            $errors[] = ($file['name'] ?? 'file') . ': ' . $res['msg'];
            continue;
        }

        // Only link to a message if messageId was provided (reply attachments)
        $msgIdForDb = $messageId > 0 ? $messageId : 0;
        $attId = $ctrl->saveAttachment($msgIdForDb, $ticketId, $res['file_name'], $res['file_path']);

        $saved[] = [
            'attachment_id' => $attId,
            'file_name'     => $res['file_name'],
            'file_path'     => $res['file_path'],
        ];
    }

    tkOut([
        'ok'     => count($saved) > 0,
        'saved'  => $saved,
        'errors' => $errors,
    ]);
}

/* ════════════════════════════════════════════════════
   ACTION: status
   Allowed user actions: reopen, confirm_resolved
════════════════════════════════════════════════════ */
if ($action === 'status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw  = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;

    if (!is_array($data)) {
        tkOut(['ok' => false, 'msg' => 'Invalid request payload.']);
    }

    $ticketId  = (int)(float)($data['ticket_id'] ?? 0);
    $userAction = trim((string)($data['user_action'] ?? ''));

    if ($ticketId <= 0) {
        tkOut(['ok' => false, 'msg' => 'Invalid ticket ID.']);
    }

    // Verify ownership + get current state
    $db   = new MySQLDB();
    $rows = $db->select(
        "SELECT t.ticket_id, t.current_status, t.ticket_number, t.subject,
                u.communication_email_id AS user_email
         FROM tbl_support_ticket t
         LEFT JOIN tbl_user u ON u.user_id = t.user_id
         WHERE t.ticket_id = $ticketId AND t.user_id = $userId
         LIMIT 1"
    );
    if (empty($rows)) {
        tkOut(['ok' => false, 'msg' => 'Ticket not found.']);
    }
    $ticket = $rows[0];

    // Map user action → new status (restricted — users cannot set In Progress or Closed)
    $statusMap = [
        'reopen'           => 'Reopened', // from Closed or Resolved
        'confirm_resolved' => 'Closed',   // user confirms issue is resolved
    ];

    if (!array_key_exists($userAction, $statusMap)) {
        tkOut(['ok' => false, 'msg' => 'Unknown action.']);
    }

    $newStatus = $statusMap[$userAction];
    $current   = (string)$ticket->CURRENT_STATUS;

    // Validate transitions
    if ($userAction === 'reopen' && !in_array($current, ['Resolved', 'Closed'], true)) {
        tkOut(['ok' => false, 'msg' => 'Ticket cannot be reopened from its current status.']);
    }
    if ($userAction === 'confirm_resolved' && $current !== 'Resolved') {
        tkOut(['ok' => false, 'msg' => 'Ticket is not in Resolved status.']);
    }

    $ok = $ctrl->changeStatus($ticketId, $newStatus, $userId, $userTypeId, $userName);
    if (!$ok) {
        tkOut(['ok' => false, 'msg' => 'Failed to update status.']);
    }

    // Send status update email to user
    $ctrl->sendStatusEmail(
        (string)$ticket->USER_EMAIL,
        $userName,
        (string)$ticket->TICKET_NUMBER,
        $newStatus
    );

    tkOut([
        'ok'         => true,
        'new_status' => $newStatus,
    ]);
}

/* ════════════════════════════════════════════════════
   Fallback — unknown action
════════════════════════════════════════════════════ */
tkOut(['ok' => false, 'msg' => 'Unknown action.']);
