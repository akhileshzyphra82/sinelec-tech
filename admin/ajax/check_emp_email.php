<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../common/functions.php';
require_once __DIR__ . '/../../controller/admin_controller.php';

header('Content-Type: application/json');

/* Must be logged in */
if (empty($_SESSION['sinelec_admin']['USER_ID'])) {
    echo json_encode(['exists' => false]);
    exit();
}

$email     = strtolower(trim($_GET['email'] ?? ''));
$excludeId = (int)($_GET['exclude_id'] ?? 0);

if ($email === '') {
    echo json_encode(['exists' => false]);
    exit();
}

$ctrl   = new AdminController();
$exists = $ctrl->checkEmployeeEmailExists($email, $excludeId);

echo json_encode(['exists' => $exists]);
