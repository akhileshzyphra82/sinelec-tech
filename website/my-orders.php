<?php
require_once __DIR__ . '/account-helpers.php';
require_once __DIR__ . '/../controller/website_controller.php';

$user   = sinelec_require_login();
$userId = (int)($user['USER_ID'] ?? 0);

$currentPage = 'my-orders';
$pageTitle   = 'My Orders | Sinelec Technologies';
require_once __DIR__ . '/header.php';

$ctrl     = new WebsiteController();
$BASE_URL = rtrim((string)sinelec_env('PUBLIC_BASE_URL', ''), '/');
$allOrders   = $ctrl->getCustomerOrders($userId);
$bankDetails = $ctrl->getBankDetails();

/* PayPal retry — check if any failed/pending PG orders exist so we can load the SDK */
$ppClientId = trim((string)preg_replace('/#.*$/', '', (string)sinelec_env('PAYPAL_CLIENT_ID', '')));
$ppCurrency = strtoupper(trim((string)preg_replace('/#.*$/', '', (string)sinelec_env('CURRENCY', 'EUR'))) ?: 'EUR');

/* Split by status */
$deliveredStatuses = ['Order Delivered'];
$pendingOrders   = array_filter($allOrders, fn($o) => !in_array((string)($o->ORDER_STATUS ?? ''), $deliveredStatuses));
$deliveredOrders = array_filter($allOrders, fn($o) =>  in_array((string)($o->ORDER_STATUS ?? ''), $deliveredStatuses));
$pendingOrders   = array_values($pendingOrders);
$deliveredOrders = array_values($deliveredOrders);
$returns         = []; /* Returns from tbl_user_order where order_type != 'Order' — future */

$initialTab    = in_array($_GET['tab'] ?? '', ['pending','delivered','returns'], true) ? $_GET['tab'] : 'pending';
$paymentNotice = trim($_GET['payment'] ?? '');   /* success | failed | cancelled */
$noticeOrder   = htmlspecialchars(trim($_GET['order'] ?? ''));

/* Helper: format order date */
function coFmtDate(string $d): string {
    $ts = strtotime($d);
    return $ts ? date('M d, Y', $ts) : $d;
}

/* Helper: first item image */
function coOrderImage(object $order, string $BASE_URL): string {
    $items = $order->items ?? [];
    if (!empty($items)) {
        $img = (string)($items[0]->IMAGE_PATH ?? '');
        if ($img !== '') return $BASE_URL . '/' . ltrim($img, '/');
    }
    return '';
}

/* Helper: first item name */
function coOrderName(object $order): string {
    $items = $order->items ?? [];
    if (!empty($items)) return trim((string)($items[0]->PRODUCT_NAME ?? ''));
    return 'Order ' . htmlspecialchars((string)($order->ORDER_NUMBER ?? ''));
}

/* Helper: first item SKU */
function coOrderSku(object $order): string {
    $items = $order->items ?? [];
    if (!empty($items)) return trim((string)($items[0]->PRODUCT_CODE ?? ''));
    return '';
}

/* Helper: total quantity across items */
function coOrderQty(object $order): int {
    $items = $order->items ?? [];
    return array_sum(array_map(fn($i) => (int)(float)($i->QUANTITY ?? 0), $items));
}

/* Helper: payment status color */
function coPayStatusColor(string $ps): string {
    return match($ps) {
        'Payment Successful' => '#16a34a',
        'Payment Failed'     => '#dc2626',
        'Not Required'       => '#6b7280',
        default              => '#d97706',   /* Payment Pending, anything else */
    };
}

/* Helper: badge color class by status */
function coStatusClass(string $status): string {
    $map = [
        'Order Pending'   => '',
        'Order Confirmed' => 'is-approved',
        'Order Packed'    => 'is-approved',
        'Order Dispatch'  => 'is-approved',
        'Order In Transit'=> 'is-approved',
        'Order Delivered' => 'is-delivered',
        'Order Cancelled' => 'is-cancelled',
    ];
    return $map[$status] ?? '';
}
?>

<main class="account-page">
  <div class="wrap">
    <div class="account-shell">
      <?php sinelec_render_account_nav($currentPage); ?>

      <section class="account-main">
        <article class="account-panel orders-shell">
          <div class="orders-page-head">
            <div>
              <h1>My Orders</h1>
              <p>Track pending and delivered orders with complete shipment updates.</p>
            </div>
          </div>

          <?php if ($paymentNotice === 'success'): ?>
          <div class="mo-notice mo-notice--success" id="moNotice">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span>Payment successful<?= $noticeOrder ? ' — Order <strong>' . $noticeOrder . '</strong>' : '' ?>. Confirmation email has been sent.</span>
            <button onclick="document.getElementById('moNotice').remove()" aria-label="Dismiss">&times;</button>
          </div>
          <?php elseif ($paymentNotice === 'failed'): ?>
          <div class="mo-notice mo-notice--error" id="moNotice">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <span>Payment failed. Your order has been saved below — you can delete it or contact support.</span>
            <button onclick="document.getElementById('moNotice').remove()" aria-label="Dismiss">&times;</button>
          </div>
          <?php elseif ($paymentNotice === 'cancelled'): ?>
          <div class="mo-notice mo-notice--warn" id="moNotice">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>Payment cancelled. Your order has been saved below — you can delete it or retry checkout.</span>
            <button onclick="document.getElementById('moNotice').remove()" aria-label="Dismiss">&times;</button>
          </div>
          <?php endif; ?>

          <div class="orders-tab-row" role="tablist" aria-label="My orders tabs">
            <button type="button" class="orders-tab-btn<?= $initialTab === 'pending'   ? ' is-active' : '' ?>" data-tab="pending"   role="tab" aria-selected="<?= $initialTab === 'pending'   ? 'true' : 'false' ?>">Active Orders (<?= count($pendingOrders) ?>)</button>
            <button type="button" class="orders-tab-btn<?= $initialTab === 'delivered' ? ' is-active' : '' ?>" data-tab="delivered" role="tab" aria-selected="<?= $initialTab === 'delivered' ? 'true' : 'false' ?>">Delivered (<?= count($deliveredOrders) ?>)</button>
            <button type="button" class="orders-tab-btn<?= $initialTab === 'returns'   ? ' is-active' : '' ?>" data-tab="returns"   role="tab" aria-selected="<?= $initialTab === 'returns'   ? 'true' : 'false' ?>">RMA Returns (<?= count($returns) ?>)</button>
          </div>

          <!-- Active / Pending orders -->
          <div class="orders-tab-panel<?= $initialTab === 'pending' ? ' is-active' : '' ?>" data-panel="pending">
            <?php if (empty($pendingOrders)): ?>
            <div class="orders-empty-state">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" opacity=".3"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
              <p>No active orders yet. <a href="products">Browse Products</a></p>
            </div>
            <?php else: ?>
            <?php foreach ($pendingOrders as $order):
              $orderId  = (int)(float)($order->USER_ORDER_ID ?? 0);
              $orderNo  = htmlspecialchars((string)($order->ORDER_NUMBER   ?? ''));
              $status   = (string)($order->ORDER_STATUS   ?? '');
              $payStatus= (string)($order->PAYMENT_STATUS ?? '');
              $orderDate= coFmtDate((string)($order->ORDER_DATE ?? ''));
              $total    = (float)($order->FINAL_TOTAL_AMT ?? 0);
              $payMode  = htmlspecialchars((string)($order->ORDER_MODE ?? ''));
              $tracking    = htmlspecialchars((string)($order->DISPATCH_COURIER_TRACKING_ID ?? ''));
              $trackingTpl = (string)($order->COURIER_TRACKING_TPL ?? '');
              $trackingUrl = ($tracking !== '' && $trackingTpl !== '') ? str_replace('{tracking_id}', rawurlencode(html_entity_decode($tracking)), $trackingTpl) : '';
              $imgUrl   = coOrderImage($order, $BASE_URL);
              $prodName = htmlspecialchars(coOrderName($order));
              $sku      = htmlspecialchars(coOrderSku($order));
              $qty      = coOrderQty($order);
              $stClass  = coStatusClass($status);
              $itemCount= count($order->items ?? []);
              $orderMode= (string)($order->ORDER_MODE ?? '');
              $canDelete = $orderMode === 'Payment Gateway'
                        && in_array($payStatus, ['Payment Pending', 'Payment Failed'])
                        && $status === 'Order Pending';
              $subNote  = match(true) {
                  $canDelete                        => 'Payment not completed. Delete this order or contact support.',
                  str_contains($status, 'Transit')  => 'Your order is on its way.',
                  str_contains($status, 'Dispatch') => 'Your order has been dispatched.',
                  str_contains($status, 'Packed')   => 'Your order is packed and ready.',
                  str_contains($status, 'Confirmed')=> 'Your order has been confirmed.',
                  default                           => 'Your order has been placed.',
              };
            ?>
            <article class="order-list-card">
              <div class="order-list-image-wrap">
                <img src="<?= $imgUrl ? htmlspecialchars($imgUrl) : '' ?>" alt="<?= $prodName ?>" class="order-list-image"
                     data-sku="<?= htmlspecialchars($sku ?? '') ?>"
                     onerror="this.onerror=null;this.src=_IMG_PH"
                     <?= !$imgUrl ? 'style="display:none;"' : '' ?>>
                <?php if (!$imgUrl): ?>
                <div class="order-list-img-ph">
                  <svg viewBox="0 0 38 38" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
                    <rect x="9" y="9" width="20" height="20" rx="2"/>
                    <line x1="9" y1="14.5" x2="5" y2="14.5"/>
                    <line x1="9" y1="19" x2="5" y2="19"/>
                    <line x1="9" y1="23.5" x2="5" y2="23.5"/>
                    <line x1="29" y1="14.5" x2="33" y2="14.5"/>
                    <line x1="29" y1="19" x2="33" y2="19"/>
                    <line x1="29" y1="23.5" x2="33" y2="23.5"/>
                    <rect x="14" y="14" width="10" height="10" rx="1"/>
                    <circle cx="16.5" cy="16.5" r="1"/>
                    <circle cx="21.5" cy="16.5" r="1"/>
                    <circle cx="16.5" cy="21.5" r="1"/>
                    <circle cx="21.5" cy="21.5" r="1"/>
                  </svg>
                </div>
                <?php endif; ?>
              </div>

              <div class="order-list-product">
                <h2><?= $prodName ?><?= $itemCount > 1 ? ' <span style="font-size:12px;font-weight:400;color:#7a93b0;">+' . ($itemCount - 1) . ' more</span>' : '' ?></h2>
                <p class="order-list-muted">Order: <strong><?= $orderNo ?></strong><?= $sku ? ' | SKU: ' . $sku : '' ?></p>
                <p class="order-list-muted">Qty: <?= $qty ?> | Date: <?= $orderDate ?></p>
                <p class="order-list-muted">Payment: <?= $payMode ?> — <span style="color:<?= coPayStatusColor($payStatus) ?>;font-weight:600;"><?= htmlspecialchars($payStatus) ?></span></p>
                <?php if ((string)($order->ORDER_MODE ?? '') === 'Bank Transfer' && $payStatus === 'Payment Pending' && !empty($bankDetails)): ?>
                <button type="button" class="mo-bank-btn" onclick="moToggleBank(this)">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                  View Bank Details
                </button>
                <div class="mo-bank-details" hidden>
                  <?php foreach ($bankDetails as $b):
                    $bHolder = htmlspecialchars(trim((string)($b->ACCOUNT_HOLDER_NAME ?? '')));
                    $bName   = htmlspecialchars(trim((string)($b->BANK_NAME           ?? '')));
                    $bAcct   = htmlspecialchars(trim((string)($b->ACCOUNT_NUMBER      ?? '')));
                    $bIban   = htmlspecialchars(trim((string)($b->IBAN_NUMBER         ?? '')));
                    $bSwift  = htmlspecialchars(trim((string)($b->SWIFT_CODE          ?? '')));
                    $bCur    = htmlspecialchars(trim((string)($b->CURRENCY            ?? 'EURO')));
                  ?>
                  <div class="mo-bank-row"><?php if ($bHolder): ?><span><?= $bHolder ?></span><?php endif; ?><?php if ($bName): ?><span><?= $bName ?></span><?php endif; ?></div>
                  <?php if ($bAcct):  ?><div class="mo-bank-row"><span class="mo-bank-k">Account:</span> <span class="mo-bank-v"><?= $bAcct ?></span></div><?php endif; ?>
                  <?php if ($bIban):  ?><div class="mo-bank-row"><span class="mo-bank-k">IBAN:</span> <span class="mo-bank-v"><?= $bIban ?></span></div><?php endif; ?>
                  <?php if ($bSwift): ?><div class="mo-bank-row"><span class="mo-bank-k">SWIFT:</span> <span class="mo-bank-v"><?= $bSwift ?></span></div><?php endif; ?>
                  <?php if ($bCur):   ?><div class="mo-bank-row"><span class="mo-bank-k">Currency:</span> <span class="mo-bank-v"><?= $bCur ?></span></div><?php endif; ?>
                  <?php endforeach; ?>
                  <div class="mo-bank-ref">Use order number <strong><?= $orderNo ?></strong> as payment reference.</div>
                </div>
                <?php endif; ?>
              </div>

              <div class="order-list-price">
                <strong>€<?= number_format($total, 2) ?></strong>
                <div class="order-list-actions order-list-actions-price" style="display:flex;flex-direction:column;gap:5px;align-items:flex-end;margin-top:6px;">
                  <?php if (!$canDelete): ?>
                  <a href="../admin/order-invoice?id=<?= $orderId ?>" target="_blank" class="mo-action-btn mo-action-btn--blue">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="11" y2="17"/></svg>
                    Invoice
                  </a>
                  <?php endif; ?>
                  <?php if ($trackingUrl !== ''): ?>
                  <a href="<?= htmlspecialchars($trackingUrl) ?>" target="_blank" class="mo-action-btn mo-action-btn--green">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Track Order
                  </a>
                  <?php elseif ($tracking !== ''): ?>
                  <span class="mo-action-btn mo-action-btn--green" title="Tracking ID: <?= $tracking ?>">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?= $tracking ?>
                  </span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="order-list-status<?= $canDelete ? ' order-list-status--unpaid' : '' ?>">
                <div class="order-list-status-info">
                  <h3 class="<?= $canDelete ? 'is-failed' : $stClass ?>"><span class="status-dot"></span><?= htmlspecialchars($canDelete ? $payStatus : $status) ?></h3>
                  <p><?= htmlspecialchars($subNote) ?></p>
                </div>
                <div class="order-list-status-btns">
                  <?php if (!$canDelete): ?>
                  <button type="button" class="mo-status-btn mo-status-btn--grey" onclick="moOpenDetails(<?= $orderId ?>)">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    View Details
                  </button>
                  <?php endif; ?>
                  <?php if ($canDelete): ?>
                  <button type="button" class="mo-status-btn mo-status-btn--pay" id="mo-pay-<?= $orderId ?>"
                    onclick="moPayRetry(<?= $orderId ?>)">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Pay &amp; Complete Order
                  </button>
                  <?php endif; ?>
                  <?php if ($canDelete): ?>
                  <button type="button" class="mo-status-btn mo-status-btn--delete" id="mo-del-<?= $orderId ?>" onclick="moDeletePending(<?= $orderId ?>, this)">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    Delete Order
                  </button>
                  <?php endif; ?>
                  <a href="new-ticket?order_id=<?= $orderId ?>" class="mo-status-btn mo-status-btn--support">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Support &amp; Help
                  </a>
                </div>
              </div>
            </article>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <!-- Delivered orders -->
          <div class="orders-tab-panel<?= $initialTab === 'delivered' ? ' is-active' : '' ?>" data-panel="delivered">
            <?php if (empty($deliveredOrders)): ?>
            <div class="orders-empty-state">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" opacity=".3"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              <p>No delivered orders yet.</p>
            </div>
            <?php else: ?>
            <?php foreach ($deliveredOrders as $order):
              $orderId  = (int)(float)($order->USER_ORDER_ID ?? 0);
              $orderNo  = htmlspecialchars((string)($order->ORDER_NUMBER ?? ''));
              $orderDate= coFmtDate((string)($order->ORDER_DATE ?? ''));
              $total    = (float)($order->FINAL_TOTAL_AMT ?? 0);
              $imgUrl   = coOrderImage($order, $BASE_URL);
              $prodName = htmlspecialchars(coOrderName($order));
              $sku      = htmlspecialchars(coOrderSku($order));
              $qty      = coOrderQty($order);
              $itemCount= count($order->items ?? []);
            ?>
            <article class="order-list-card">
              <div class="order-list-image-wrap">
                <img src="<?= $imgUrl ? htmlspecialchars($imgUrl) : '' ?>" alt="<?= $prodName ?>" class="order-list-image"
                     data-sku="<?= htmlspecialchars($sku ?? '') ?>"
                     onerror="this.onerror=null;this.src=_IMG_PH"
                     <?= !$imgUrl ? 'style="display:none;"' : '' ?>>
                <?php if (!$imgUrl): ?>
                <div class="order-list-img-ph">
                  <svg viewBox="0 0 38 38" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
                    <rect x="9" y="9" width="20" height="20" rx="2"/>
                    <line x1="9" y1="14.5" x2="5" y2="14.5"/>
                    <line x1="9" y1="19" x2="5" y2="19"/>
                    <line x1="9" y1="23.5" x2="5" y2="23.5"/>
                    <line x1="29" y1="14.5" x2="33" y2="14.5"/>
                    <line x1="29" y1="19" x2="33" y2="19"/>
                    <line x1="29" y1="23.5" x2="33" y2="23.5"/>
                    <rect x="14" y="14" width="10" height="10" rx="1"/>
                    <circle cx="16.5" cy="16.5" r="1"/>
                    <circle cx="21.5" cy="16.5" r="1"/>
                    <circle cx="16.5" cy="21.5" r="1"/>
                    <circle cx="21.5" cy="21.5" r="1"/>
                  </svg>
                </div>
                <?php endif; ?>
              </div>

              <div class="order-list-product">
                <h2><?= $prodName ?><?= $itemCount > 1 ? ' <span style="font-size:12px;font-weight:400;color:#7a93b0;">+' . ($itemCount - 1) . ' more</span>' : '' ?></h2>
                <p class="order-list-muted">Order: <strong><?= $orderNo ?></strong><?= $sku ? ' | SKU: ' . $sku : '' ?></p>
                <p class="order-list-muted">Qty: <?= $qty ?> | Date: <?= $orderDate ?></p>
              </div>

              <div class="order-list-price">
                <strong>€<?= number_format($total, 2) ?></strong>
                <div class="order-list-actions order-list-actions-price" style="display:flex;flex-direction:column;gap:5px;align-items:flex-end;margin-top:6px;">
                  <a href="../admin/order-invoice?id=<?= $orderId ?>" target="_blank" class="mo-action-btn mo-action-btn--blue">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="11" y2="17"/></svg>
                    Invoice
                  </a>
                </div>
              </div>

              <div class="order-list-status">
                <div class="order-list-status-info">
                  <h3 class="is-delivered"><span class="status-dot"></span>Delivered</h3>
                  <p>Your order has been delivered successfully.</p>
                </div>
                <div class="order-list-status-btns">
                  <button type="button" class="mo-status-btn mo-status-btn--grey" onclick="moOpenDetails(<?= $orderId ?>)">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    View Details
                  </button>
                  <a href="new-ticket?order_id=<?= $orderId ?>" class="mo-status-btn mo-status-btn--support">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Support &amp; Help
                  </a>
                </div>
              </div>
            </article>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <!-- ── Returns Tab Panel ──────────────────────────── -->
          <div class="orders-tab-panel<?= $initialTab === 'returns' ? ' is-active' : '' ?>" data-panel="returns">
            <?php if (empty($returns)): ?>
            <div class="orders-empty-state">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" opacity=".3"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
              <p>No return requests found.</p>
            </div>
            <?php else: ?>
            <?php foreach ($returns as $rma): ?>
            <?php
              $rmaStatus = (string)($rma['status'] ?? '');
              $rmaLabel  = htmlspecialchars((string)($rma['status_label'] ?? ''));
              $dotClass  = $rmaStatus === 'completed' ? ' is-delivered' : ($rmaStatus === 'approved' ? ' is-approved' : '');
            ?>
            <article class="order-list-card">
              <div class="order-list-image-wrap">
                <img src="<?= htmlspecialchars((string)$rma['image']) ?>" alt="<?= htmlspecialchars((string)$rma['product']) ?>" class="order-list-image">
              </div>

              <div class="order-list-product">
                <h2><?= htmlspecialchars((string)$rma['product']) ?></h2>
                <p class="order-list-muted">RMA: <?= htmlspecialchars((string)$rma['rma_no']) ?> | Order: <?= htmlspecialchars((string)$rma['order_no']) ?></p>
                <p class="order-list-muted">SKU: <?= htmlspecialchars((string)$rma['sku']) ?> | Qty: <?= (int)$rma['qty'] ?> | Date: <?= htmlspecialchars((string)$rma['date']) ?></p>
                <p class="order-list-muted rma-reason-label">Reason: <?= htmlspecialchars((string)$rma['reason']) ?></p>
              </div>

              <div class="order-list-price">
                <strong>€<?= number_format((float)$rma['refund'], 2) ?></strong>
                <div style="font-size:10px;color:#64748b;margin-top:2px;">Est. Refund</div>
              </div>

              <div class="order-list-status">
                <h3 class="<?= $dotClass ?>">
                  <span class="status-dot rma-status-dot rma-status-dot--<?= htmlspecialchars($rmaStatus) ?>"></span>
                  <?= $rmaLabel ?>
                </h3>
                <p><?= htmlspecialchars((string)($rma['note'] ?? '')) ?></p>
              </div>
            </article>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </article>
      </section>
    </div>
  </div>
</main>

<!-- Order Details Drawer -->
<div class="mo-drawer-overlay" id="moDrawerOverlay" onclick="moCloseDetails()"></div>
<aside class="mo-drawer" id="moDrawer" role="dialog" aria-modal="true" aria-label="Order Details">
  <div class="mo-drawer-head">
    <h2 id="moDrawerTitle">Order Details</h2>
    <a id="moDrawerInvoice" href="#" target="_blank" class="mo-action-btn mo-action-btn--blue" style="font-size:11px;padding:5px 10px;text-decoration:none;">
      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Invoice
    </a>
    <button class="mo-drawer-close" onclick="moCloseDetails()" aria-label="Close">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="mo-drawer-body" id="moDrawerBody">
    <!-- populated by JS -->
  </div>
</aside>

<!-- PayPal Retry Modal -->
<div class="mo-pp-overlay" id="moPpOverlay" onclick="moPpClose()"></div>
<div class="mo-pp-modal" id="moPpModal" role="dialog" aria-modal="true" aria-labelledby="moPpTitle">
  <div class="mo-pp-modal-head">
    <div class="mo-pp-modal-titles">
      <h3 id="moPpTitle">Complete Payment</h3>
      <p id="moPpSubtitle"></p>
    </div>
    <button class="mo-drawer-close" onclick="moPpClose()" aria-label="Close">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="mo-pp-modal-body">
    <div id="moPpStatus"></div>
    <div id="moPpBtnContainer"></div>
  </div>
</div>

<style>
.orders-shell {
  padding: 18px;
  background: #f3f5f7;
  border-radius: 20px;
}
.orders-page-head {
  margin-bottom: 14px;
}
.orders-page-head h1 {
  font-size: clamp(1.2rem, 2vw, 1.6rem);
  color: #182a43;
}
.orders-page-head p {
  margin-top: 4px;
  color: #5f728b;
  font-size: 12px;
}
.orders-tab-row {
  display: inline-flex;
  gap: 8px;
  padding: 5px;
  border-radius: 14px;
  background: #e8edf3;
  border: 1px solid #d6dde6;
  margin-bottom: 14px;
}
.orders-tab-btn {
  min-height: 38px;
  padding: 0 18px;
  border-radius: 10px;
  border: 1px solid transparent;
  background: transparent;
  color: #324b69;
  font-size: 12px;
  font-weight: 800;
  transition: all .2s ease;
}
.orders-tab-btn.is-active {
  background: #ffffff;
  color: #112d4b;
  border-color: #d3dbe5;
  box-shadow: 0 4px 10px rgba(20, 33, 56, .06);
}
.orders-tab-panel { display: none; gap: 12px; }
.orders-tab-panel.is-active { display: grid; }

.order-list-card {
  display: grid;
  grid-template-columns: 128px minmax(0, 1.3fr) minmax(110px, .45fr) minmax(260px, 1fr);
  gap: 14px;
  padding: 14px 16px;
  border-radius: 10px;
  border: 1px solid #d3d9e0;
  background: #ffffff;
}

.order-list-image-wrap {
  width: 80px;
  height: 70px;
  border-radius: 8px;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid #dde4ec;
  flex-shrink: 0;
  position: relative;
}
.order-list-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.order-list-img-ph {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
}
.order-list-product {
  min-width: 0;
}
.order-list-product h2 {
  font-size: 17px;
  line-height: 1.3;
  color: #232f3f;
  font-weight: 700;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.order-list-muted {
  margin-top: 7px;
  font-size: 12px;
  color: #6a727d;
  line-height: 1.45;
}

.order-list-price {
  display: grid;
  align-content: start;
  gap: 6px;
  padding-top: 6px;
}
.order-list-price strong {
  font-size: 17px;
  line-height: 1;
  font-weight: 700;
  color: #212a35;
}

.order-list-status {
  padding-top: 6px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  flex-wrap: wrap;
}
.order-list-status-info { flex: 1; min-width: 0; }
.order-list-status-btns {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
  align-items: center;
}
.mo-status-btn {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 500; padding: 6px 13px;
  border-radius: 7px; cursor: pointer; border: 1px solid transparent;
  text-decoration: none; white-space: nowrap; transition: background .15s, border-color .15s;
}
.mo-status-btn--grey { background:#f1f5f9; color:#475569; border-color:#e2e8f0; }
.mo-status-btn--grey:hover { background:#e2e8f0; border-color:#cbd5e1; }
.mo-status-btn--support { background:#fff7ed; color:#c2410c; border-color:#fed7aa; }
.mo-status-btn--support:hover { background:#ffedd5; border-color:#fdba74; }
.order-list-status h3 {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #1f2c3e;
  font-size: 15px;
  line-height: 1.2;
  font-weight: 700;
}
.status-dot {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 4px solid #26a042;
  background: #fff;
  flex-shrink: 0;
}
.order-list-status h3.is-delivered .status-dot {
  background: #25a342;
  border-color: #25a342;
}
.order-list-status p {
  margin-top: 8px;
  color: #2e343c;
  font-size: 12px;
}
.order-list-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}
.order-list-actions a {
  min-height: 30px;
  padding: 0 10px;
  border-radius: 7px;
  border: 1px solid #d1d9e2;
  background: #fff;
  color: #1f4c97;
  font-size: 11px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
}
.order-list-actions-price {
  margin-top: 8px;
}
.order-list-actions a.review-link {
  border: none;
  padding: 0;
  color: #1e5ece;
  background: transparent;
}

@media (max-width: 1500px) {
  .order-list-card {
    grid-template-columns: 112px minmax(0, 1fr) minmax(110px, .4fr);
  }
  .order-list-status {
    grid-column: 1 / -1;
    border-top: 1px dashed #d6dde6;
    padding-top: 12px;
  }
  .order-list-price strong { font-size: 19px; }
  .order-list-status h3 { font-size: 17px; }
  .order-list-status p { font-size: 13px; }
}
/* ── RMA-specific styles ─────────────────────────────── */
.rma-reason-label { color: #b45309 !important; }

.rma-status-dot--approved  { border-color: #2563eb; background: #fff; }
.rma-status-dot--processing { border-color: #d97706; background: #fff; }
.rma-status-dot--completed { background: #16a34a; border-color: #16a34a; }

.order-list-status h3.is-approved .status-dot { border-color: #2563eb; background: #fff; }

/* ── Bank details toggle ─────────────────────────────── */
.mo-bank-btn{display:inline-flex;align-items:center;gap:5px;margin-top:6px;padding:4px 10px;border:1px solid #fde68a;background:#fffbeb;color:#92400e;font-size:10px;font-weight:700;border-radius:6px;cursor:pointer;}
.mo-bank-btn:hover{background:#fef3c7;}
.mo-action-btn{display:inline-flex;align-items:center;gap:4px;padding:5px 11px;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;cursor:pointer;}
.mo-action-btn--blue{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;}
.mo-action-btn--blue:hover{background:#dbeafe;}
.mo-action-btn--green{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
.mo-action-btn--green:hover{background:#dcfce7;}
.mo-bank-details{margin-top:8px;padding:10px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:11px;display:flex;flex-direction:column;gap:5px;}
.mo-bank-row{display:flex;align-items:baseline;gap:6px;flex-wrap:wrap;}
.mo-bank-k{color:#92400e;min-width:72px;font-weight:600;}
.mo-bank-v{font-family:monospace;color:#1a3352;font-weight:700;letter-spacing:.3px;font-size:11px;}
.mo-bank-ref{font-size:11px;color:#92400e;border-top:1px solid #fde68a;padding-top:6px;margin-top:2px;}

/* ── Empty state ─────────────────────────────────────── */
.orders-empty-state {
  text-align: center;
  padding: 56px 20px;
  color: #94a3b8;
  display: flex; flex-direction: column; align-items: center; gap: 12px;
}
.orders-empty-state p { font-size: 14px; }

@media (max-width: 900px) {
  .orders-tab-row {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
  }
  .orders-tab-btn { width: 100%; }
  .order-list-card {
    grid-template-columns: 88px minmax(0, 1fr);
    gap: 12px;
    padding: 12px;
  }
  .order-list-image-wrap {
    width: 84px;
    height: 84px;
  }
  .order-list-product h2 {
    font-size: 15px;
  }
  .order-list-muted {
    font-size: 11px;
  }
  .order-list-price {
    grid-column: 2;
    display: grid;
    align-content: start;
    gap: 6px;
    padding-top: 0;
  }
  .order-list-price strong {
    font-size: 16px;
  }
  .order-list-status {
    grid-column: 1 / -1;
    border-top: 1px dashed #d6dde6;
    padding-top: 12px;
  }
  .order-list-status h3 {
    font-size: 14px;
  }
  .order-list-status p {
    font-size: 11px;
  }
}
@media (max-width: 640px) {
  .orders-shell {
    padding: 12px;
  }
  .orders-page-head h1 {
    font-size: 1rem;
    line-height: 1.2;
  }
  .orders-page-head p {
    font-size: 10px;
    line-height: 1.4;
  }
  .orders-tab-row {
    grid-template-columns: 1fr 1fr;
    padding: 4px;
    gap: 6px;
    margin-bottom: 10px;
  }
  .orders-tab-btn {
    min-height: 32px;
    font-size: 10.5px;
    padding: 0 10px;
  }
  .order-list-card {
    padding: 10px;
    gap: 10px;
  }
  .order-list-product h2 {
    font-size: 13px;
    line-height: 1.3;
  }
  .order-list-muted {
    margin-top: 5px;
    font-size: 10px;
    line-height: 1.35;
  }
  .order-list-price strong {
    font-size: 14px;
  }
  .order-list-status h3 {
    font-size: 12px;
    gap: 8px;
  }
  .order-list-status p {
    font-size: 10px;
    margin-top: 6px;
  }
  .status-dot {
    width: 12px;
    height: 12px;
    border-width: 3px;
  }
  .order-list-actions a {
    min-height: 28px;
    padding-inline: 8px;
    font-size: 10px;
  }
}

/* mo-action-btn--grey */
.mo-action-btn--grey { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.mo-action-btn--grey:hover { background:#e2e8f0; }

/* Delete button */
.mo-status-btn--delete { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
.mo-status-btn--delete:hover { background:#fee2e2; border-color:#f87171; }
.mo-status-btn--delete:disabled { opacity:.55; cursor:not-allowed; }

/* Pay & Complete Order button */
.mo-status-btn--pay { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; font-weight:700; }
.mo-status-btn--pay:hover { background:#dbeafe; border-color:#93c5fd; }
.mo-status-btn--pay:disabled { opacity:.55; cursor:not-allowed; }

/* PayPal Retry Modal */
.mo-pp-overlay {
  position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1100;
  opacity:0; pointer-events:none; transition:opacity .25s;
}
.mo-pp-overlay.is-open { opacity:1; pointer-events:all; }
.mo-pp-modal {
  position:fixed; top:50%; left:50%; transform:translate(-50%,-50%) scale(.95);
  width:420px; max-width:calc(100vw - 32px); background:#fff; border-radius:16px;
  box-shadow:0 20px 60px rgba(0,0,0,.22); z-index:1101;
  opacity:0; pointer-events:none; transition:opacity .25s, transform .25s;
  overflow:hidden;
}
.mo-pp-modal.is-open { opacity:1; pointer-events:all; transform:translate(-50%,-50%) scale(1); }
.mo-pp-modal-head {
  display:flex; align-items:flex-start; gap:12px; padding:20px 20px 16px;
  border-bottom:1px solid #e8edf3;
}
.mo-pp-modal-titles { flex:1; min-width:0; }
.mo-pp-modal-titles h3 { font-size:16px; font-weight:700; color:#1a2332; margin:0 0 3px; }
.mo-pp-modal-titles p  { font-size:12px; color:#64748b; margin:0; }
.mo-pp-modal-body { padding:20px; }
.mo-pp-loading { text-align:center; color:#64748b; font-size:13px; padding:24px 0; margin:0;
  display:flex; align-items:center; justify-content:center; gap:8px; }
.mo-pp-loading::before {
  content:''; width:16px; height:16px; border-radius:50%;
  border:2px solid #e2e8f0; border-top-color:#3b82f6;
  animation:ppSpin .7s linear infinite; flex-shrink:0;
}
@keyframes ppSpin { to { transform:rotate(360deg); } }
.mo-pp-error { color:#dc2626; font-size:13px; text-align:center; padding:16px 0; margin:0;
  background:#fef2f2; border-radius:8px; border:1px solid #fecaca; }
#moPpBtnContainer { min-height:0; }
#moPpBtnContainer:not(:empty) { margin-top:8px; }

/* Unpaid / failed order card highlight */
.order-list-status--unpaid { background:#fef2f2; border-radius:8px; padding:10px 12px; }
.order-list-status h3.is-failed { color:#dc2626; }
.order-list-status h3.is-failed .status-dot { background:#dc2626; border-color:#dc2626; }

/* Payment-result notices */
.mo-notice { display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:10px;
             font-size:13px; margin-bottom:14px; }
.mo-notice svg { flex-shrink:0; }
.mo-notice span { flex:1; }
.mo-notice button { background:none; border:none; font-size:18px; cursor:pointer;
                    line-height:1; color:inherit; padding:0 0 0 8px; opacity:.6; }
.mo-notice button:hover { opacity:1; }
.mo-notice--success { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }
.mo-notice--error   { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }
.mo-notice--warn    { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }

/* Order Details Drawer */
.mo-drawer-overlay {
  position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000;
  opacity:0; pointer-events:none; transition:opacity .25s;
}
.mo-drawer-overlay.is-open { opacity:1; pointer-events:all; }
.mo-drawer {
  position:fixed; top:0; right:0; bottom:0; width:520px; max-width:100vw;
  background:#fff; z-index:1001; overflow-y:auto;
  transform:translateX(100%); transition:transform .3s cubic-bezier(.4,0,.2,1);
  display:flex; flex-direction:column;
}
.mo-drawer.is-open { transform:translateX(0); }
.mo-drawer-head {
  position:sticky; top:0; background:#fff; z-index:2;
  border-bottom:1px solid #e8edf3;
  padding:18px 22px 14px; display:flex; align-items:center; gap:10px;
}
.mo-drawer-head h2 { flex:1; font-size:17px; font-weight:700; color:#1a2332; margin:0; }
.mo-drawer-close {
  width:32px; height:32px; border:none; background:#f1f5f9; border-radius:50%;
  cursor:pointer; display:flex; align-items:center; justify-content:center;
  color:#64748b; flex-shrink:0;
}
.mo-drawer-close:hover { background:#e2e8f0; }
.mo-drawer-body { padding:20px 22px; flex:1; }
.mo-drawer-section { margin-bottom:24px; }
.mo-drawer-section-title {
  font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
  color:#94a3b8; margin:0 0 14px; padding-bottom:8px;
  border-bottom:1px solid #f0f4f8;
}

/* Timeline */
.mo-timeline { list-style:none; margin:0; padding:0; }
.mo-timeline-item {
  display:flex; gap:14px; position:relative; padding-bottom:20px;
}
.mo-timeline-item:last-child { padding-bottom:0; }
.mo-tl-left { display:flex; flex-direction:column; align-items:center; flex-shrink:0; width:18px; }
.mo-tl-dot {
  width:16px; height:16px; border-radius:50%; flex-shrink:0;
  border:2px solid #d1d5db; background:#fff;
  display:flex; align-items:center; justify-content:center;
}
.mo-tl-dot.done { background:#22c55e; border-color:#22c55e; }
.mo-tl-dot.active { background:#3b82f6; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.18); }
.mo-tl-dot.cancelled { background:#ef4444; border-color:#ef4444; }
.mo-tl-line {
  width:2px; flex:1; min-height:14px; margin-top:2px;
  background:#e5e7eb;
}
.mo-tl-line.done { background:#22c55e; }
.mo-timeline-item:last-child .mo-tl-line { display:none; }
.mo-tl-right { flex:1; min-width:0; }
.mo-tl-label { font-size:14px; font-weight:600; color:#1a2332; line-height:1.3; }
.mo-tl-label.active { color:#3b82f6; }
.mo-tl-label.cancelled { color:#ef4444; }
.mo-tl-sub { font-size:12px; color:#64748b; margin-top:2px; }
.mo-tl-remarks { font-size:11px; color:#94a3b8; font-style:italic; margin-top:3px; line-height:1.4; }
.mo-tl-active-row .mo-tl-remarks { color:#6b9ec7; }
.mo-tl-active-row { background:#f0f9ff; border-radius:8px; padding:8px 10px; margin:-4px -4px 0; }

/* Items table */
.mo-items-table { width:100%; border-collapse:collapse; }
.mo-items-table th {
  font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.04em;
  color:#94a3b8; text-align:left; padding:0 0 8px;
  border-bottom:1px solid #f0f4f8;
}
.mo-items-table td { padding:10px 0; border-bottom:1px solid #f8fafc; vertical-align:top; }
.mo-items-table tr:last-child td { border-bottom:none; }
.mo-item-name { font-size:13px; font-weight:600; color:#1a2332; }
.mo-item-sku { font-size:11px; color:#94a3b8; margin-top:2px; }
.mo-item-qty { font-size:12px; color:#64748b; }
.mo-item-price { font-size:13px; font-weight:600; color:#1a2332; text-align:right; white-space:nowrap; }

/* Totals */
.mo-totals { background:#f8fafc; border-radius:8px; padding:14px 16px; }
.mo-total-row { display:flex; justify-content:space-between; align-items:center; font-size:13px; padding:3px 0; color:#475569; }
.mo-total-row.grand { font-size:15px; font-weight:700; color:#1a2332; border-top:1px solid #e2e8f0; margin-top:8px; padding-top:10px; }

/* Address cards */
.mo-addr-card { background:#f8fafc; border-radius:8px; padding:14px 16px; font-size:13px; color:#374151; line-height:1.7; }
.mo-addr-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; margin-bottom:6px; }
.mo-addr-name { font-weight:600; color:#1a2332; font-size:14px; }
.mo-addr-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media (max-width:540px) {
  .mo-drawer { width:100vw; }
  .mo-addr-grid { grid-template-columns:1fr; }
}
</style>

<?php
$moOrdersJs = [];
foreach (array_merge($pendingOrders, $deliveredOrders) as $o) {
    $oid = (int)(float)($o->USER_ORDER_ID ?? 0);

    // build items array
    $jsItems = [];
    foreach (($o->items ?? []) as $it) {
        $imgPath = (string)($it->IMAGE_PATH ?? '');
        $jsItems[] = [
            'name'       => (string)($it->PRODUCT_NAME ?? ''),
            'sku'        => (string)($it->PRODUCT_CODE ?? ''),
            'qty'        => (int)(float)($it->QUANTITY ?? 1),
            'unit_price' => (float)($it->PRODUCT_AMT ?? 0),
            'total'      => (float)($it->FINAL_AMT ?? 0),
            'image'      => $imgPath !== '' ? ($BASE_URL . '/' . ltrim($imgPath, '/')) : '',
        ];
    }

    // build history array
    $jsHistory = [];
    foreach (($o->history ?? []) as $h) {
        $jsHistory[] = [
            'status'  => (string)($h->HISTORY_ORDER_STATUS ?? ''),
            'remarks' => (string)($h->HISTORY_REMARKS ?? ''),
            'date'    => (string)($h->CREATED_AT ?? ''),
        ];
    }

    // delivery address
    $delAddr = array_filter([
        (string)($o->ADDRESS_LINE_ONE ?? ''),
        (string)($o->ADDRESS_LINE_TWO ?? ''),
        (string)($o->CITY ?? ''),
        (string)($o->STATE ?? ''),
        (string)($o->ZIP ?? ''),
        (string)($o->COUNTRY ?? ''),
    ]);

    // billing address
    $bilAddr = array_filter([
        (string)($o->BIL_LINE1 ?? ''),
        (string)($o->BIL_LINE2 ?? ''),
        (string)($o->BIL_CITY ?? ''),
        (string)($o->BIL_STATE ?? ''),
        (string)($o->BIL_ZIP ?? ''),
        (string)($o->BIL_COUNTRY ?? ''),
    ]);

    $moOrdersJs[$oid] = [
        'id'             => $oid,
        'number'         => (string)($o->ORDER_NUMBER ?? ''),
        'date'           => coFmtDate((string)($o->ORDER_DATE ?? '')),
        'status'         => (string)($o->ORDER_STATUS ?? ''),
        'payment_status' => (string)($o->PAYMENT_STATUS ?? ''),
        'payment_mode'   => (string)($o->ORDER_MODE ?? ''),
        'subtotal'       => (float)($o->ORDER_TOTAL_AMT ?? 0),
        'shipping'       => (float)($o->SHIPPING_AMT ?? 0),
        'vat'            => (float)($o->TAX_TOTAL_AMOUNT ?? 0),
        'total'          => (float)($o->FINAL_TOTAL_AMT ?? 0),
        'vat_number'     => (string)($o->VAT_NUMBER ?? ''),
        'tracking_id'    => (string)($o->DISPATCH_COURIER_TRACKING_ID ?? ''),
        'courier'        => (string)($o->COURIER_NAME ?? ''),
        'tracking_url'   => (string)($o->COURIER_TRACKING_TPL ?? ''),
        'del_name'       => (string)($o->RECIPIENT_NAME ?? ''),
        'del_company'    => (string)($o->DEL_COMPANY ?? ''),
        'del_addr'       => implode(', ', $delAddr),
        'bil_name'       => (string)($o->BIL_NAME ?? ''),
        'bil_company'    => (string)($o->BIL_COMPANY ?? ''),
        'bil_addr'       => implode(', ', $bilAddr),
        'items'          => $jsItems,
        'history'        => $jsHistory,
        'invoice_url'    => '../admin/order-invoice?id=' . $oid,
    ];
}
?>
<script>
window._moOrders = <?= json_encode($moOrdersJs, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>

<script>
function moToggleBank(btn) {
  const box = btn.nextElementSibling;
  if (!box) return;
  box.hidden = !box.hidden;
  btn.textContent = box.hidden ? '▸ View Bank Details' : '▾ Hide Bank Details';
}

document.addEventListener('DOMContentLoaded', function () {
  var tabButtons = Array.from(document.querySelectorAll('.orders-tab-btn'));
  var panels     = Array.from(document.querySelectorAll('.orders-tab-panel'));

  function activateTab(tabId) {
    tabButtons.forEach(function (item) {
      var isActive = item.getAttribute('data-tab') === tabId;
      item.classList.toggle('is-active', isActive);
      item.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
    panels.forEach(function (panel) {
      panel.classList.toggle('is-active', panel.getAttribute('data-panel') === tabId);
    });
  }

  tabButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      activateTab(btn.getAttribute('data-tab'));
    });
  });
});

function moOpenDetails(orderId) {
    var o = window._moOrders[orderId];
    if (!o) return;

    // Header
    document.getElementById('moDrawerTitle').textContent = 'Order #' + o.number;
    document.getElementById('moDrawerInvoice').href = o.invoice_url;

    // Build body HTML
    var html = '';

    // ── Tracking timeline ──
    var steps = [
        { key: 'Order Pending',    label: 'Order Placed' },
        { key: 'Order Confirmed',  label: 'Order Confirmed' },
        { key: 'Order Packed',     label: 'Packed' },
        { key: 'Order Dispatch',   label: 'Shipped' },
        { key: 'Order In Transit', label: 'In Transit' },
        { key: 'Order Delivered',  label: 'Delivered' },
    ];
    var cancelled = o.status === 'Order Cancelled';

    // Find which steps have history entries
    var doneKeys = {};
    var stepDates = {};
    var stepRemarks = {};
    o.history.forEach(function(h) {
        if (h.status) {
            doneKeys[h.status] = true;
            if (!stepDates[h.status])   stepDates[h.status]   = h.date;
            if (!stepRemarks[h.status]) stepRemarks[h.status] = h.remarks;
        }
    });

    // Find the current active step index
    var activeIdx = -1;
    if (!cancelled) {
        for (var i = steps.length - 1; i >= 0; i--) {
            if (doneKeys[steps[i].key] || o.status === steps[i].key) {
                activeIdx = i; break;
            }
        }
    }

    html += '<div class="mo-drawer-section">';
    html += '<p class="mo-drawer-section-title">Tracking</p>';

    if (cancelled) {
        html += '<ul class="mo-timeline">';
        html += '<li class="mo-timeline-item"><div class="mo-tl-left"><div class="mo-tl-dot cancelled"></div><div class="mo-tl-line"></div></div><div class="mo-tl-right"><div class="mo-tl-label cancelled">Order Cancelled</div>';
        var cLast = o.history.length ? o.history[o.history.length-1] : null;
        if (cLast && cLast.date)    html += '<div class="mo-tl-sub">' + _moFmtDate(cLast.date) + '</div>';
        if (cLast && cLast.remarks) html += '<div class="mo-tl-remarks">' + _moEsc(cLast.remarks) + '</div>';
        html += '</div></li></ul>';
    } else {
        html += '<ul class="mo-timeline">';
        steps.forEach(function(step, idx) {
            var isDone   = idx < activeIdx || (doneKeys[step.key] && idx <= activeIdx);
            var isActive = idx === activeIdx;
            var isPending= !isDone && !isActive;

            var dotCls  = isDone ? 'done' : (isActive ? 'active' : '');
            var lineCls = isDone ? 'done' : '';
            var lblCls  = isActive ? 'active' : '';

            var dateStr   = stepDates[step.key]   ? _moFmtDate(stepDates[step.key]) : '';
            var remarkStr = stepRemarks[step.key] ? stepRemarks[step.key] : '';

            html += '<li class="mo-timeline-item">';
            html += '<div class="mo-tl-left"><div class="mo-tl-dot ' + dotCls + '">';
            if (isDone) html += '<svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>';
            html += '</div><div class="mo-tl-line ' + lineCls + '"></div></div>';
            html += '<div class="mo-tl-right">';
            if (isActive) {
                html += '<div class="mo-tl-active-row"><div class="mo-tl-label ' + lblCls + '">' + step.label + '</div>';
                if (dateStr)   html += '<div class="mo-tl-sub">'     + dateStr                + '</div>';
                if (remarkStr) html += '<div class="mo-tl-remarks">' + _moEsc(remarkStr)      + '</div>';
                html += '</div>';
            } else {
                html += '<div class="mo-tl-label ' + lblCls + '" style="' + (isPending ? 'color:#94a3b8;font-weight:400;' : '') + '">' + step.label + '</div>';
                if (dateStr)   html += '<div class="mo-tl-sub">'     + dateStr                + '</div>';
                if (remarkStr) html += '<div class="mo-tl-remarks">' + _moEsc(remarkStr)      + '</div>';
            }
            html += '</div></li>';
        });
        html += '</ul>';
    }
    html += '</div>';

    // ── Items ──
    html += '<div class="mo-drawer-section">';
    html += '<p class="mo-drawer-section-title">Items</p>';
    html += '<table class="mo-items-table"><thead><tr><th>Product</th><th>Qty</th><th style="text-align:right">Price</th></tr></thead><tbody>';
    o.items.forEach(function(it) {
        html += '<tr>';
        html += '<td><div class="mo-item-name">' + _moEsc(it.name) + '</div>';
        if (it.sku) html += '<div class="mo-item-sku">SKU: ' + _moEsc(it.sku) + '</div>';
        html += '</td>';
        html += '<td class="mo-item-qty">\xd7' + it.qty + '</td>';
        html += '<td class="mo-item-price">€' + it.total.toFixed(2) + '</td>';
        html += '</tr>';
    });
    html += '</tbody></table>';
    html += '</div>';

    // ── Totals ──
    html += '<div class="mo-drawer-section">';
    html += '<p class="mo-drawer-section-title">Price Breakdown</p>';
    html += '<div class="mo-totals">';
    html += '<div class="mo-total-row"><span>Subtotal</span><span>€' + o.subtotal.toFixed(2) + '</span></div>';
    html += '<div class="mo-total-row"><span>Shipping</span><span>€' + o.shipping.toFixed(2) + '</span></div>';
    if (o.vat_number) {
        html += '<div class="mo-total-row"><span>VAT (Exempt — ' + _moEsc(o.vat_number) + ')</span><span>€0.00</span></div>';
    } else {
        html += '<div class="mo-total-row"><span>VAT (19%)</span><span>€' + o.vat.toFixed(2) + '</span></div>';
    }
    html += '<div class="mo-total-row grand"><span>Total</span><span>€' + o.total.toFixed(2) + '</span></div>';
    html += '</div></div>';

    // ── Addresses ──
    html += '<div class="mo-drawer-section">';
    html += '<p class="mo-drawer-section-title">Addresses</p>';
    html += '<div class="mo-addr-grid">';

    // Delivery
    html += '<div class="mo-addr-card">';
    html += '<div class="mo-addr-label">Delivery</div>';
    if (o.del_name) html += '<div class="mo-addr-name">' + _moEsc(o.del_name) + '</div>';
    if (o.del_company) html += '<div>' + _moEsc(o.del_company) + '</div>';
    if (o.del_addr) html += '<div>' + _moEsc(o.del_addr) + '</div>';
    html += '</div>';

    // Billing
    html += '<div class="mo-addr-card">';
    html += '<div class="mo-addr-label">Billing</div>';
    var bilName = o.bil_name || o.del_name;
    var bilCo   = o.bil_company || o.del_company;
    var bilAddr = o.bil_addr || o.del_addr;
    if (bilName) html += '<div class="mo-addr-name">' + _moEsc(bilName) + '</div>';
    if (bilCo) html += '<div>' + _moEsc(bilCo) + '</div>';
    if (bilAddr) html += '<div>' + _moEsc(bilAddr) + '</div>';
    html += '</div>';

    html += '</div></div>'; // end addr-grid + section

    document.getElementById('moDrawerBody').innerHTML = html;
    document.getElementById('moDrawer').classList.add('is-open');
    document.getElementById('moDrawerOverlay').classList.add('is-open');
    document.body.style.overflow = 'hidden';
}

function moCloseDetails() {
    document.getElementById('moDrawer').classList.remove('is-open');
    document.getElementById('moDrawerOverlay').classList.remove('is-open');
    document.body.style.overflow = '';
}

function _moFmtDate(str) {
    if (!str) return '';
    var d = new Date(str.replace(' ', 'T'));
    if (isNaN(d)) return str;
    return d.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}) + ' — ' + d.toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit'});
}

function _moEsc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { moCloseDetails(); moPpClose(); }
});

/* ── Delete unpaid PayPal order ─────────────────────────── */
function moDeletePending(orderId, btn) {
    if (!confirm('Delete this unpaid order? This cannot be undone.')) return;

    btn.disabled = true;
    btn.textContent = 'Deleting…';

    fetch('ajax/order?action=delete_pending', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            /* Remove the card from the DOM */
            var card = btn.closest('article.order-list-card');
            if (card) {
                card.style.transition = 'opacity .3s';
                card.style.opacity = '0';
                setTimeout(function() { card.remove(); }, 300);
            }
        } else {
            btn.disabled = false;
            btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg> Delete Order';
            alert(data.msg || 'Could not delete order. Please try again.');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg> Delete Order';
        alert('Network error. Please try again.');
    });
}
</script>

<?php if ($ppClientId): ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars($ppClientId) ?>&currency=<?= htmlspecialchars($ppCurrency) ?>&intent=capture"></script>
<?php endif; ?>

<script>
/* ── PayPal Retry Flow ───────────────────────────────── */
var _moPpOrderId = null;
var _moPpButtons = null;

function moPayRetry(orderId) {
    var o = window._moOrders ? window._moOrders[orderId] : null;
    var orderNumber = o ? o.number : ('Order #' + orderId);
    var amount      = o ? o.total.toFixed(2) : '';

    _moPpOrderId = null;

    // Open modal with loading state
    document.getElementById('moPpTitle').textContent    = 'Complete Payment';
    document.getElementById('moPpSubtitle').textContent = 'Order ' + orderNumber + (amount ? ' — €' + amount : '');
    document.getElementById('moPpStatus').innerHTML     = '<p class="mo-pp-loading">Connecting to PayPal…</p>';
    document.getElementById('moPpBtnContainer').innerHTML = '';
    document.getElementById('moPpOverlay').classList.add('is-open');
    document.getElementById('moPpModal').classList.add('is-open');
    document.body.style.overflow = 'hidden';

    fetch('ajax/paypal?action=retry', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.ok) {
            document.getElementById('moPpStatus').innerHTML =
                '<p class="mo-pp-error">' + (data.msg || 'Failed to initiate payment. Please try again.') + '</p>';
            return;
        }

        _moPpOrderId = data.paypal_order_id;
        document.getElementById('moPpStatus').innerHTML = '';

        if (_moPpButtons) { try { _moPpButtons.close(); } catch(e) {} _moPpButtons = null; }

        if (!window.paypal || !window.paypal.Buttons) {
            document.getElementById('moPpStatus').innerHTML =
                '<p class="mo-pp-error">PayPal is not available. Please refresh the page and try again.</p>';
            return;
        }

        _moPpButtons = window.paypal.Buttons({
            createOrder: function() {
                return Promise.resolve(_moPpOrderId);
            },

            onApprove: function() {
                document.getElementById('moPpStatus').innerHTML = '<p class="mo-pp-loading">Processing payment…</p>';
                document.getElementById('moPpBtnContainer').innerHTML = '';
                var captureId = _moPpOrderId;
                _moPpOrderId  = null;   // clear so moPpClose won't cancel

                fetch('ajax/paypal?action=capture', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ paypal_order_id: captureId }),
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    _moPpCloseModal();
                    window.location.href = res.ok
                        ? (res.redirect || 'my-orders?payment=success')
                        : 'my-orders?payment=failed';
                })
                .catch(function() {
                    _moPpCloseModal();
                    window.location.href = 'my-orders?payment=failed';
                });
            },

            onCancel: function() {
                var cid = _moPpOrderId; _moPpOrderId = null;
                if (cid) fetch('ajax/paypal?action=cancel', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ paypal_order_id: cid }),
                }).catch(function(){});
                _moPpCloseModal();
            },

            onError: function(err) {
                console.error('PayPal retry error', err);
                var cid = _moPpOrderId; _moPpOrderId = null;
                if (cid) fetch('ajax/paypal?action=cancel', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ paypal_order_id: cid }),
                }).catch(function(){});
                document.getElementById('moPpStatus').innerHTML =
                    '<p class="mo-pp-error">Payment error. Please try again or contact support.</p>';
                document.getElementById('moPpBtnContainer').innerHTML = '';
            },
        });

        _moPpButtons.render('#moPpBtnContainer').catch(function() {
            document.getElementById('moPpStatus').innerHTML =
                '<p class="mo-pp-error">Could not load PayPal buttons. Please refresh and try again.</p>';
        });
    })
    .catch(function() {
        document.getElementById('moPpStatus').innerHTML =
            '<p class="mo-pp-error">Network error. Please try again.</p>';
    });
}

function moPpClose() {
    // Cancel the PayPal order if one was created but not completed
    if (_moPpOrderId) {
        var cid = _moPpOrderId; _moPpOrderId = null;
        fetch('ajax/paypal?action=cancel', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ paypal_order_id: cid }),
        }).catch(function(){});
    }
    _moPpCloseModal();
}

function _moPpCloseModal() {
    document.getElementById('moPpOverlay').classList.remove('is-open');
    document.getElementById('moPpModal').classList.remove('is-open');
    document.body.style.overflow = '';
    if (_moPpButtons) { try { _moPpButtons.close(); } catch(e) {} _moPpButtons = null; }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
