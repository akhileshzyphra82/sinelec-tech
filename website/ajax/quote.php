<?php
header('Content-Type: application/json');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../common/functions.php';
require_once __DIR__ . '/../../controller/website_controller.php';

$controller = new WebsiteController();
$action     = $_GET['action'] ?? $_POST['action'] ?? '';
$user       = $_SESSION['sinelec_user'] ?? [];
$userId     = (int)($user['USER_ID'] ?? 0);

function jsonOut(array $d): void { echo json_encode($d); exit; }

/* ── Must be authenticated for all endpoints ───────────────── */
if ($userId <= 0) jsonOut(['ok' => false, 'msg' => 'Authentication required.']);

/* ── GET: products by category ─────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_products') {
    $catId    = (int)($_GET['cat_id'] ?? 0);
    $products = $controller->getProductsByCategory($catId);
    $out = [];
    foreach ($products as $p) {
        $out[] = [
            'ID'    => (int)(float)($p->PRODUCT_ID   ?? $p->product_id   ?? 0),
            'NAME'  => (string)($p->PRODUCT_NAME     ?? $p->product_name ?? ''),
            'CODE'  => (string)($p->PRODUCT_CODE     ?? $p->product_code ?? ''),
            'PRICE' => (float)($p->PRODUCT_AMT       ?? $p->product_amt  ?? 0),
            'STOCK' => (int)(float)($p->STOCK        ?? $p->total_product ?? $p->stock ?? 0),
        ];
    }
    jsonOut(['ok' => true, 'products' => $out]);
}

/* ── GET: saved addresses ───────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_addresses') {
    $rows = $controller->getUserAddresses($userId);
    $out  = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'       => (int)(float)($r->USER_ADDRESS_ID  ?? 0),
            'label'    => (string)($r->LABEL                ?? 'Home'),
            'name'     => (string)($r->RECIPIENT_NAME       ?? ''),
            'phone'    => (string)($r->DELIVERY_PHONE_NO    ?? ''),
            'line1'    => (string)($r->ADDRESS_LINE_ONE     ?? ''),
            'line2'    => (string)($r->ADDRESS_LINE_TWO     ?? ''),
            'landmark' => (string)($r->LANDMARK             ?? ''),
            'city'     => (string)($r->CITY                 ?? ''),
            'state'    => (string)($r->STATE                ?? ''),
            'zip'      => (string)($r->ZIP                  ?? ''),
            'country'  => (string)($r->COUNTRY              ?? ''),
            'company'  => (string)($r->COMPANY_NAME         ?? ''),
            'eu_vat'   => (string)($r->EU_VAT               ?? ''),
        ];
    }
    jsonOut(['ok' => true, 'addresses' => $out]);
}

/* ── POST: submit quotation ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit') {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) jsonOut(['ok' => false, 'msg' => 'Invalid request.']);

    /* Verify Cloudflare Turnstile */
    $cfToken = trim($data['cf_token'] ?? '');
    $cfResult = sinelec_validate_turnstile($cfToken, $_SERVER['REMOTE_ADDR'] ?? null);
    if (empty($cfResult['success'])) {
        jsonOut(['ok' => false, 'msg' => 'Security verification failed. Please refresh and try again.']);
    }

    /* Update user details if changed */
    $newName  = trim($data['user_name']      ?? '');
    $newPhone = trim($data['user_phone']     ?? '');
    $newIsd   = trim($data['user_phone_isd'] ?? '');
    $newComp  = trim($data['user_company']   ?? '');
    if ($newName !== '' || $newPhone !== '' || $newComp !== '') {
        $controller->updateUserDetails($userId, $newName, $newPhone, $newIsd, $newComp);
        if ($newName  !== '') $_SESSION['sinelec_user']['NAME']                         = $newName;
        if ($newPhone !== '') $_SESSION['sinelec_user']['COMMUNICATION_MOBILE_NUM']     = $newPhone;
        if ($newIsd   !== '') $_SESSION['sinelec_user']['COMMUNICATION_MOBILE_NUM_ISD'] = $newIsd;
        if ($newComp  !== '') $_SESSION['sinelec_user']['COMPANY_NAME']                 = $newComp;
    }

    $result = $controller->submitCustomerQuote($data, $userId);
    jsonOut($result);
}

jsonOut(['ok' => false, 'msg' => 'Unknown action.']);
