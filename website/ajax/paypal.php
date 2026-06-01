<?php
/**
 * PayPal REST API v2 — Create · Capture · Cancel
 *
 * POST ?action=create   { cart payload }
 *   1. Places order in DB immediately (payment_status='Payment Pending', order_status='Order Pending')
 *   2. Creates PayPal order via /v2/checkout/orders
 *   3. Stores { user_order_id, order_number, paypal_order_id, user_id } in session
 *   4. Returns { ok, paypal_order_id }
 *
 * POST ?action=capture  { paypal_order_id }
 *   1. Validates session
 *   2. Captures PayPal payment → must be COMPLETED
 *   3. Updates payment_status='Payment Successful' + pay_pal_tx_id
 *   4. Returns { ok, order_number }
 *
 * POST ?action=cancel   { paypal_order_id }
 *   1. Validates session
 *   2. Updates payment_status='Payment Failed'
 *   3. Returns { ok }
 *
 * If PayPal creation/capture fails after the DB order was saved, the order is
 * automatically marked 'Payment Failed' so the admin can follow up.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../common/functions.php';
require_once __DIR__ . '/../../website/account-helpers.php';
require_once __DIR__ . '/../../controller/website_controller.php';

function ppOut(array $d): void {
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    exit;
}

/* ── Auth ─────────────────────────────────────────────────── */
$user   = sinelec_get_signed_in_user();
$userId = (int)($user['USER_ID'] ?? 0);
if ($userId <= 0) ppOut(['ok' => false, 'msg' => 'Authentication required. Please sign in.']);

$ctrl   = new WebsiteController();
$action = trim($_GET['action'] ?? '');

/* ── Helpers ──────────────────────────────────────────────── */

/** Strip inline .env comments: "sandbox   #(or live)" → "sandbox" */
function ppCleanEnv(string $key, string $default = ''): string {
    $raw = (string)sinelec_env($key, $default);
    return trim((string)preg_replace('/#.*$/', '', $raw));
}

function ppBaseUrl(): string {
    $mode = strtolower(ppCleanEnv('PAYPAL_MODE', 'sandbox'));
    return ($mode === 'live')
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';
}

function ppGetAccessToken(): string {
    $clientId = ppCleanEnv('PAYPAL_CLIENT_ID');
    $secret   = ppCleanEnv('PAYPAL_SECRET');

    if (!$clientId || !$secret) {
        throw new \RuntimeException('PayPal credentials (PAYPAL_CLIENT_ID / PAYPAL_SECRET) are not configured in .env');
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => ppBaseUrl() . '/v1/oauth2/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => "$clientId:$secret",
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Accept-Language: en_US'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($code === 401) {
        throw new \RuntimeException(
            'PayPal authentication failed (401). ' .
            'Check that PAYPAL_CLIENT_ID and PAYPAL_SECRET match PAYPAL_MODE (' .
            ppCleanEnv('PAYPAL_MODE', 'sandbox') . ').'
        );
    }
    if (!$res || $code !== 200) {
        throw new \RuntimeException("PayPal token error HTTP $code. cURL: $err");
    }

    $json  = json_decode($res, true);
    $token = (string)($json['access_token'] ?? '');
    if (!$token) throw new \RuntimeException('Empty access_token in PayPal token response.');
    return $token;
}

/* ══════════════════════════════════════════════════════════
   POST  ?action=create
   1. Place order in DB (Payment Pending, Order Pending)
   2. Get PayPal OAuth token
   3. Create PayPal Order (v2) with the saved order's total
   4. Store { user_order_id, order_number, paypal_order_id } in session
   5. Return { ok, paypal_order_id }
   ══════════════════════════════════════════════════════════ */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $raw = file_get_contents('php://input');
    $d   = $raw ? json_decode($raw, true) : null;
    if (!is_array($d)) ppOut(['ok' => false, 'msg' => 'Invalid request body.']);

    /* 1 ── Place order in DB first (Payment Pending) */
    $result = $ctrl->placeOrder($d, $userId);
    if (!($result['ok'] ?? false)) ppOut($result);

    $orderId     = (int)($result['order_id']     ?? 0);
    $orderNumber = (string)($result['order_number'] ?? '');
    $finalTotal  = number_format((float)($result['final_total'] ?? 0), 2, '.', '');
    $currency    = strtoupper(ppCleanEnv('CURRENCY', 'EUR'));

    /* 2 ── Get PayPal access token */
    try {
        $token   = ppGetAccessToken();
        $baseUrl = ppBaseUrl();
    } catch (\Throwable $e) {
        $errMsg = $e->getMessage();
        error_log('PayPal create – token: ' . $errMsg . ' | orderId=' . $orderId);
        $ctrl->updateOrderPaymentStatus($orderId, 'Payment Failed', '', $userId);
        $userMsg = strpos($errMsg, '401') !== false
            ? 'PayPal credentials mismatch. Check PAYPAL_CLIENT_ID and PAYPAL_SECRET in .env.'
            : 'PayPal is currently unavailable. Please try another payment method.';
        ppOut(['ok' => false, 'msg' => $userMsg, 'redirect' => 'my-orders?payment=failed']);
    }

    /* 3 ── Create PayPal Order */
    $ppPayload = json_encode([
        'intent'         => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => 'ORDER-' . $orderId,
            'description'  => 'Sinelec Technologies Order #' . $orderNumber,
            'amount'       => [
                'currency_code' => $currency,
                'value'         => $finalTotal,
            ],
        ]],
        'application_context' => [
            'brand_name'          => 'Sinelec Technologies',
            'shipping_preference' => 'NO_SHIPPING',
            'user_action'         => 'PAY_NOW',
        ],
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $baseUrl . '/v2/checkout/orders',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $ppPayload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'PayPal-Request-Id: sinelec-' . $orderId . '-' . time(),
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if (!$res || $code !== 201) {
        error_log("PayPal create order: HTTP $code | curl: $err | body: $res | orderId=$orderId");
        $ctrl->updateOrderPaymentStatus($orderId, 'Payment Failed', '', $userId);
        ppOut(['ok' => false, 'msg' => 'Could not connect to PayPal. Please try again.', 'redirect' => 'my-orders?payment=failed']);
    }

    $ppOrder   = json_decode($res, true);
    $ppOrderId = (string)($ppOrder['id'] ?? '');
    if (!$ppOrderId) {
        error_log('PayPal create order: no id in response: ' . $res . ' | orderId=' . $orderId);
        $ctrl->updateOrderPaymentStatus($orderId, 'Payment Failed', '', $userId);
        ppOut(['ok' => false, 'msg' => 'PayPal did not return an order ID. Please try again.', 'redirect' => 'my-orders?payment=failed']);
    }

    /* 4 ── Store only essential identifiers in session */
    $_SESSION['paypal_pending'] = [
        'user_order_id'   => $orderId,
        'order_number'    => $orderNumber,
        'paypal_order_id' => $ppOrderId,
        'user_id'         => $userId,
        'created_at'      => time(),
    ];

    ppOut(['ok' => true, 'paypal_order_id' => $ppOrderId]);
}

/* ══════════════════════════════════════════════════════════
   POST  ?action=capture
   1. Validate session (paypal_order_id + user_id must match)
   2. Capture PayPal payment → must be COMPLETED
   3. Update payment_status='Payment Successful' + pay_pal_tx_id
   4. Clear session
   5. Return { ok, order_number }
   ══════════════════════════════════════════════════════════ */
if ($action === 'capture' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $raw = file_get_contents('php://input');
    $d   = $raw ? json_decode($raw, true) : null;

    $ppOrderId = trim((string)($d['paypal_order_id'] ?? ''));
    if (!$ppOrderId) ppOut(['ok' => false, 'msg' => 'Missing PayPal order ID.']);

    /* Validate session */
    $pending = $_SESSION['paypal_pending'] ?? null;
    if (
        !$pending ||
        ($pending['paypal_order_id'] ?? '') !== $ppOrderId ||
        (int)($pending['user_id']     ?? 0)  !== $userId
    ) {
        error_log("PayPal capture: session mismatch — user $userId, ppOrderId=$ppOrderId");
        ppOut(['ok' => false, 'msg' => 'Invalid payment session. Please start checkout again.']);
    }

    /* Session expiry (15 min) */
    if ((time() - (int)($pending['created_at'] ?? 0)) > 900) {
        unset($_SESSION['paypal_pending']);
        ppOut(['ok' => false, 'msg' => 'Payment session expired. Please place your order again.']);
    }

    $orderId     = (int)($pending['user_order_id'] ?? 0);
    $orderNumber = (string)($pending['order_number'] ?? '');

    /* Get fresh token */
    try {
        $token   = ppGetAccessToken();
        $baseUrl = ppBaseUrl();
    } catch (\Throwable $e) {
        error_log('PayPal capture – token: ' . $e->getMessage() . ' | orderId=' . $orderId);
        ppOut(['ok' => false, 'msg' => 'PayPal unavailable. Your order #' . $orderNumber . ' is saved. Please contact support to complete payment.']);
    }

    /* Capture PayPal payment */
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $baseUrl . '/v2/checkout/orders/' . $ppOrderId . '/capture',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '{}',
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if (!$res || ($code !== 200 && $code !== 201)) {
        error_log("PayPal capture: HTTP $code | curl: $err | body: $res | orderId=$orderId");
        $ctrl->updateOrderPaymentStatus($orderId, 'Payment Failed', '', $userId);
        unset($_SESSION['paypal_pending']);
        ppOut(['ok' => false, 'msg' => 'Payment capture failed. Order #' . $orderNumber . ' saved.', 'redirect' => 'my-orders?payment=failed']);
    }

    $captureData   = json_decode($res, true);
    $captureStatus = strtoupper((string)($captureData['status'] ?? ''));

    if ($captureStatus !== 'COMPLETED') {
        error_log("PayPal capture status '$captureStatus' for ppOrderId=$ppOrderId | orderId=$orderId");
        $ctrl->updateOrderPaymentStatus($orderId, 'Payment Failed', '', $userId);
        unset($_SESSION['paypal_pending']);
        ppOut(['ok' => false, 'msg' => 'Payment not completed (status: ' . $captureStatus . ').', 'redirect' => 'my-orders?payment=failed']);
    }

    $ppTxnId = (string)($captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? '');

    /* Payment confirmed — update status to Payment Successful (also sends confirmation email) */
    $ctrl->updateOrderPaymentStatus($orderId, 'Payment Successful', $ppTxnId, $userId);

    /* Clear session */
    unset($_SESSION['paypal_pending']);

    ppOut(['ok' => true, 'order_number' => $orderNumber, 'redirect' => 'my-orders?payment=success&order=' . urlencode($orderNumber)]);
}

/* ══════════════════════════════════════════════════════════
   POST  ?action=cancel
   Called when user closes/cancels the PayPal popup
   1. Validate session
   2. Update payment_status='Payment Failed'
   3. Clear session
   4. Return { ok }
   ══════════════════════════════════════════════════════════ */
if ($action === 'cancel' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $raw       = file_get_contents('php://input');
    $d         = $raw ? json_decode($raw, true) : null;
    $ppOrderId = trim((string)($d['paypal_order_id'] ?? ''));

    $pending = $_SESSION['paypal_pending'] ?? null;

    if (
        $pending &&
        ($pending['paypal_order_id'] ?? '') === $ppOrderId &&
        (int)($pending['user_id'] ?? 0) === $userId
    ) {
        $orderId = (int)($pending['user_order_id'] ?? 0);
        if ($orderId > 0) {
            $ctrl->updateOrderPaymentStatus($orderId, 'Payment Failed', '', $userId);
        }
        unset($_SESSION['paypal_pending']);
    }

    ppOut(['ok' => true]);
}

/* ══════════════════════════════════════════════════════════
   POST  ?action=retry
   Re-open PayPal for an existing failed/pending PG order.
   1. Verify order belongs to user & is retryable
   2. Reset order to Payment Pending
   3. Create new PayPal order using existing order total
   4. Store session
   5. Return { ok, paypal_order_id }
   ══════════════════════════════════════════════════════════ */
if ($action === 'retry' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $raw     = file_get_contents('php://input');
    $d       = $raw ? json_decode($raw, true) : null;
    $orderId = (int)($d['order_id'] ?? 0);
    if ($orderId <= 0) ppOut(['ok' => false, 'msg' => 'Invalid order ID.']);

    $order = $ctrl->getPaymentGatewayOrderForRetry($orderId, $userId);
    if (!$order) ppOut(['ok' => false, 'msg' => 'Order not found or not eligible for retry.']);

    $orderNumber = (string)($order->ORDER_NUMBER     ?? '');
    $finalTotal  = number_format((float)($order->FINAL_TOTAL_AMT ?? 0), 2, '.', '');
    $currency    = strtoupper(ppCleanEnv('CURRENCY', 'EUR'));

    /* Reset status to Payment Pending */
    $ctrl->resetOrderToPaymentPending($orderId, $userId);

    /* Get PayPal access token */
    try {
        $token   = ppGetAccessToken();
        $baseUrl = ppBaseUrl();
    } catch (\Throwable $e) {
        error_log('PayPal retry – token: ' . $e->getMessage() . ' | orderId=' . $orderId);
        ppOut(['ok' => false, 'msg' => 'PayPal is currently unavailable. Please try again later.']);
    }

    /* Create new PayPal Order */
    $ppPayload = json_encode([
        'intent'         => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => 'ORDER-' . $orderId,
            'description'  => 'Sinelec Technologies Order #' . $orderNumber,
            'amount'       => [
                'currency_code' => $currency,
                'value'         => $finalTotal,
            ],
        ]],
        'application_context' => [
            'brand_name'          => 'Sinelec Technologies',
            'shipping_preference' => 'NO_SHIPPING',
            'user_action'         => 'PAY_NOW',
        ],
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $baseUrl . '/v2/checkout/orders',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $ppPayload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'PayPal-Request-Id: sinelec-retry-' . $orderId . '-' . time(),
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if (!$res || $code !== 201) {
        error_log("PayPal retry create: HTTP $code | curl: $err | body: $res | orderId=$orderId");
        ppOut(['ok' => false, 'msg' => 'Could not connect to PayPal. Please try again.']);
    }

    $ppOrder   = json_decode($res, true);
    $ppOrderId = (string)($ppOrder['id'] ?? '');
    if (!$ppOrderId) {
        error_log('PayPal retry create: no id | orderId=' . $orderId);
        ppOut(['ok' => false, 'msg' => 'PayPal did not return an order ID. Please try again.']);
    }

    /* Store session */
    $_SESSION['paypal_pending'] = [
        'user_order_id'   => $orderId,
        'order_number'    => $orderNumber,
        'paypal_order_id' => $ppOrderId,
        'user_id'         => $userId,
        'created_at'      => time(),
    ];

    ppOut(['ok' => true, 'paypal_order_id' => $ppOrderId]);
}

ppOut(['ok' => false, 'msg' => 'Unknown action.']);
