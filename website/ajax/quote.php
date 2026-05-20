<?php
header('Content-Type: application/json');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../common/functions.php';
require_once __DIR__ . '/../../controller/website_controller.php';

$controller = new WebsiteController();
$action     = $_GET['action'] ?? $_POST['action'] ?? '';

$user       = $_SESSION['sinelec_user'] ?? [];
$loggedInId = (int)($user['user_id'] ?? 0);

function jsonOut(array $d): void { echo json_encode($d); exit; }

/* ── GET: products by category ─────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_products') {
    $catId    = (int)($_GET['cat_id'] ?? 0);
    $products = $controller->getProductsByCategory($catId);
    $out = [];
    foreach ($products as $p) {
        $out[] = [
            'id'    => (int)(float)($p->PRODUCT_ID    ?? 0),
            'name'  => (string)($p->PRODUCT_NAME      ?? ''),
            'code'  => (string)($p->PRODUCT_CODE      ?? ''),
            'price' => (float)($p->PRODUCT_AMT        ?? 0),
            'stock' => (int)(float)($p->STOCK          ?? 0),
        ];
    }
    jsonOut(['ok' => true, 'products' => $out]);
}

/* ── GET: user addresses (logged-in only) ───────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_addresses') {
    if ($loggedInId <= 0) jsonOut(['ok' => false, 'addresses' => []]);
    $rows = $controller->getUserAddresses($loggedInId);
    $out  = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'      => (int)(float)($r->USER_ADDRESS_ID  ?? 0),
            'label'   => (string)($r->LABEL                ?? 'Home'),
            'name'    => (string)($r->USER_NAME            ?? ''),
            'company' => (string)($r->COMPANY_NAME         ?? ''),
            'line1'   => (string)($r->ADDRESS_LINE_ONE     ?? ''),
            'line2'   => (string)($r->ADDRESS_LINE_TWO     ?? ''),
            'landmark'=> (string)($r->LANDMARK             ?? ''),
            'city'    => (string)($r->CITY                 ?? ''),
            'state'   => (string)($r->STATE                ?? ''),
            'zip'     => (string)($r->ZIP                  ?? ''),
            'country' => (string)($r->COUNTRY              ?? ''),
            'phone'   => (string)($r->DELIVERY_PHONE_NO    ?? ''),
        ];
    }
    jsonOut(['ok' => true, 'addresses' => $out]);
}

/* ── POST: submit quotation ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit') {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) jsonOut(['ok' => false, 'msg' => 'Invalid request.']);

    /* If using an existing saved address, pass it through */
    if ($loggedInId > 0 && !empty($data['existing_address_id'])) {
        $existingAddrId = (int)$data['existing_address_id'];
        /* Skip creating new address — override in controller */
        $data['_use_existing_addr'] = $existingAddrId;
    }

    /* Fill in logged-in user info if available */
    if ($loggedInId > 0) {
        $data['name']  = $data['name']  ?: ($user['name']  ?? '');
        $data['email'] = $data['email'] ?: ($user['email'] ?? '');
        $data['phone'] = $data['phone'] ?: ($user['communication_mobile_num'] ?? '');
        $data['company_name'] = $data['company_name'] ?: ($user['company_name'] ?? '');
    }

    $result = $controller->submitCustomerQuote($data, $loggedInId);
    jsonOut($result);
}

jsonOut(['ok' => false, 'msg' => 'Unknown action.']);
