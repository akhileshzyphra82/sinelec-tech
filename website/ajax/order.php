<?php
/**
 * Order AJAX — all pricing calculated server-side; client prices never trusted.
 *
 * GET  action=get_shipping&address_id=X  → { ok, shipping_amt, country }
 * POST action=place                      → { ok, order_number, payment_type } | { ok:false, msg }
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../common/functions.php';
require_once __DIR__ . '/../../website/account-helpers.php';
require_once __DIR__ . '/../../controller/website_controller.php';

function orderJsonOut(array $d): void {
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    exit;
}

$user   = sinelec_get_signed_in_user();
$userId = (int)($user['USER_ID'] ?? 0);

if ($userId <= 0) {
    orderJsonOut(['ok' => false, 'msg' => 'Authentication required. Please sign in.']);
}

$ctrl   = new WebsiteController();
$action = trim($_GET['action'] ?? '');

/* ── GET: shipping cost for a delivery address ───────────── */
if ($action === 'get_shipping') {
    $addrId = (int)($_GET['address_id'] ?? 0);
    if ($addrId <= 0) orderJsonOut(['ok' => false, 'msg' => 'Invalid address.']);

    $info = $ctrl->getAddressShipping($addrId, $userId);
    if (!$info) orderJsonOut(['ok' => false, 'msg' => 'Address not found.']);

    orderJsonOut([
        'ok'           => true,
        'shipping_amt' => round((float)($info->SHIPPING_AMT ?? 19.99), 2),
        'country'      => (string)($info->COUNTRY_NAME ?? $info->COUNTRY ?? ''),
    ]);
}

/* ── POST: place order ───────────────────────────────────── */
if ($action === 'place' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $d   = $raw ? json_decode($raw, true) : null;
    if (!is_array($d)) orderJsonOut(['ok' => false, 'msg' => 'Invalid request body.']);
    orderJsonOut($ctrl->placeOrder($d, $userId));
}

/* ── POST: delete unpaid Payment Gateway order ───────────── */
if ($action === 'delete_pending' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw     = file_get_contents('php://input');
    $d       = $raw ? json_decode($raw, true) : null;
    $orderId = (int)($d['order_id'] ?? 0);
    if ($orderId <= 0) orderJsonOut(['ok' => false, 'msg' => 'Invalid order ID.']);
    orderJsonOut($ctrl->deleteUnpaidPaymentGatewayOrder($orderId, $userId));
}

orderJsonOut(['ok' => false, 'msg' => 'Unknown action.']);
