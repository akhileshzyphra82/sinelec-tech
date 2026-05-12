<?php
require_once __DIR__ . '/account-helpers.php';
require_once __DIR__ . '/order-data.php';

$user = sinelec_require_login();
$currentPage = 'my-orders';

$orderNo = trim((string)($_GET['order'] ?? ''));
$order = $orderNo !== '' ? sinelec_find_order($orderNo) : null;
if (!$order) {
    $allOrders = sinelec_order_data();
    $order = $allOrders[0] ?? null;
}

if (!$order) {
    sinelec_set_flash('warn', 'Order not found.');
    header('location:my-orders');
    exit();
}

$pageTitle = 'Order Details ' . $order['order_no'] . ' | Sinelec Technologies';
require_once __DIR__ . '/header.php';

$timeline = is_array($order['timeline'] ?? null) ? $order['timeline'] : [];
if (count($timeline) === 0) {
    $timeline = [
        ['label' => 'Order Confirmed', 'time' => $order['date'], 'state' => 'done'],
        ['label' => 'Shipped', 'time' => 'In progress', 'state' => 'active'],
        ['label' => 'Out For Delivery', 'time' => 'Waiting', 'state' => 'upcoming'],
        ['label' => 'Delivered', 'time' => $order['eta'], 'state' => 'upcoming'],
    ];
}

$listingPrice = round((float)$order['total'] * 1.17, 2);
$specialPrice = (float)$order['total'];
$totalFees = 7.00;
$otherDiscount = round((float)$order['total'] * 0.09, 2);
$grandTotal = round($specialPrice + $totalFees - $otherDiscount, 2);

$displayAddress = trim((string)($order['delivery_address'] ?? 'Brachvogelweg 9, 85375 Neufahrn, Germany'));
$displayContact = trim((string)($user['NAME'] ?? 'Sinelec Customer')) . '  ' . trim((string)($user['COMMUNICATION_MOBILE_NUM'] ?? '+49 (0)8165-9906178'));
?>

<main class="account-page">
  <div class="wrap">
    <div class="account-shell account-shell-wide">
      <?php sinelec_render_account_nav($currentPage); ?>

      <section class="account-main order-track-main">
        <div class="order-track-grid">
          <article class="order-track-left">
            <section class="order-track-top">
              <div class="order-track-product">
                <h1><?= htmlspecialchars((string)$order['product']) ?></h1>
                <p class="order-track-variant">Black</p>
                <p class="order-track-seller">Seller: Sinelec Technologies</p>
                <p class="order-track-price">€<?= number_format((float)$order['total'], 2) ?></p>
              </div>
              <div class="order-track-thumb-wrap">
                <img src="<?= htmlspecialchars((string)$order['image']) ?>" alt="<?= htmlspecialchars((string)$order['product']) ?>" class="order-track-thumb">
              </div>
            </section>

            <section class="order-track-timeline">
              <?php foreach ($timeline as $index => $step): ?>
                <?php
                  $state = strtolower((string)($step['state'] ?? 'upcoming'));
                  $isDone = $state === 'done';
                  $isActive = $state === 'active';
                  $rowClass = $isActive ? ' is-active' : '';
                  $dotClass = $isDone || $isActive ? ' is-done' : '';
                ?>
                <div class="track-step-row<?= $rowClass ?>">
                  <div class="track-step-line-col">
                    <span class="track-step-dot<?= $dotClass ?>"></span>
                    <?php if ($index < count($timeline) - 1): ?>
                      <span class="track-step-line"></span>
                    <?php endif; ?>
                  </div>
                  <div class="track-step-text">
                    <h3><?= htmlspecialchars((string)$step['label']) ?></h3>
                    <p><?= htmlspecialchars((string)$step['time']) ?></p>
                  </div>
                </div>
              <?php endforeach; ?>

              <a href="#" class="track-updates-link">See All Updates</a>
            </section>

            <section class="order-track-info-row">
              Delivery executive details will be available once the order is out for delivery.
            </section>

            <section class="order-track-bottom-actions">
              <a href="my-orders" class="track-bottom-btn">Cancel</a>
              <a href="contact" class="track-bottom-btn">Chat with us</a>
            </section>
          </article>

          <aside class="order-track-right">
            <article class="order-side-card">
              <h2>Delivery details</h2>
              <a href="delivery-address" class="delivery-detail-row">
                <span class="delivery-detail-icon">
                  <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l9-8 9 8"></path><path d="M5 10v10h14V10"></path></svg>
                </span>
                <span class="delivery-detail-text"><strong>Home</strong> <?= htmlspecialchars($displayAddress) ?></span>
                <span class="delivery-detail-arrow">›</span>
              </a>
              <a href="profile" class="delivery-detail-row">
                <span class="delivery-detail-icon">
                  <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c2-4 14-4 16 0"></path></svg>
                </span>
                <span class="delivery-detail-text"><strong><?= htmlspecialchars((string)$user['NAME'] ?: 'Sinelec Customer') ?></strong> <?= htmlspecialchars($displayContact) ?></span>
                <span class="delivery-detail-arrow">›</span>
              </a>
            </article>

            <article class="order-side-card">
              <h2>Price details</h2>
              <div class="price-grid">
                <div class="price-row"><span>Listing price</span><strong class="is-strike">€<?= number_format($listingPrice, 2) ?></strong></div>
                <div class="price-row"><span>Special price</span><strong>€<?= number_format($specialPrice, 2) ?></strong></div>
                <div class="price-row"><span>Total fees</span><strong>€<?= number_format($totalFees, 2) ?></strong></div>
                <div class="price-row"><span>Other discount</span><strong class="is-green">-€<?= number_format($otherDiscount, 2) ?></strong></div>
              </div>
              <div class="price-total-row">
                <span>Total amount</span>
                <strong>€<?= number_format($grandTotal, 2) ?></strong>
              </div>
              <div class="paid-by-box">
                <span>Paid By</span>
                <strong><?= htmlspecialchars((string)$order['payment_method']) ?></strong>
              </div>
            </article>
          </aside>
        </div>
      </section>
    </div>
  </div>
</main>

<style>
.account-shell.account-shell-wide {
  grid-template-columns: 260px minmax(0, 1fr);
}
.order-track-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.55fr) minmax(320px, .8fr);
  gap: 16px;
}
.order-track-left,
.order-side-card {
  background: #fff;
  border: 1px solid #dde5ef;
  border-radius: 0;
}
.order-track-left {
  border-radius: 4px;
  overflow: hidden;
}
.order-track-top {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 124px;
  gap: 14px;
  padding: 22px 24px;
}
.order-track-product h1 {
  color: #27313f;
  font-size: clamp(1.15rem, 1.6vw, 1.45rem);
  line-height: 1.25;
  font-weight: 600;
}
.order-track-variant {
  margin-top: 10px;
  color: #777f8a;
  font-size: 13px;
}
.order-track-seller {
  margin-top: 8px;
  color: #7a838f;
  font-size: 13px;
  font-weight: 600;
}
.order-track-price {
  margin-top: 8px;
  color: #1e2430;
  font-size: 28px;
  font-weight: 700;
  line-height: 1;
}
.order-track-thumb-wrap {
  width: 116px;
  height: 116px;
  border: 1px solid #dde6ef;
  border-radius: 12px;
  overflow: hidden;
  background: #f7fafc;
}
.order-track-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.order-track-timeline {
  border-top: 1px solid #e6ebf2;
  padding: 18px 24px 22px;
}
.track-step-row {
  display: grid;
  grid-template-columns: 28px minmax(0, 1fr);
  gap: 12px;
}
.track-step-row + .track-step-row {
  margin-top: 8px;
}
.track-step-line-col {
  display: grid;
  justify-items: center;
}
.track-step-dot {
  width: 15px;
  height: 15px;
  border-radius: 50%;
  border: 3px solid #b7bec8;
  background: #fff;
  position: relative;
  z-index: 2;
}
.track-step-dot.is-done {
  border-color: #2ea043;
  background: #2ea043;
}
.track-step-line {
  width: 2px;
  min-height: 44px;
  background: #d2d9e1;
  margin-top: 3px;
}
.track-step-row.is-active {
  background: #eaf3e9;
  border-radius: 2px;
  padding: 8px;
  margin-left: -8px;
  margin-right: -8px;
}
.track-step-text h3 {
  color: #2a313b;
  font-size: 15px;
  font-weight: 600;
  line-height: 1.25;
}
.track-step-text p {
  margin-top: 4px;
  color: #5f6a77;
  font-size: 12px;
  line-height: 1.4;
}
.track-updates-link {
  display: inline-block;
  margin-top: 16px;
  color: #2459c4;
  font-size: 13px;
  font-weight: 700;
}
.order-track-info-row {
  border-top: 1px solid #e6ebf2;
  padding: 18px 24px;
  color: #2f3947;
  font-size: 13px;
}
.order-track-bottom-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  border-top: 1px solid #e6ebf2;
}
.track-bottom-btn {
  min-height: 84px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #232e3e;
  font-size: 16px;
  font-weight: 600;
}
.track-bottom-btn + .track-bottom-btn {
  border-left: 1px solid #e6ebf2;
}
.order-track-right {
  display: grid;
  gap: 12px;
  align-content: start;
}
.order-side-card {
  padding: 18px;
  border-radius: 4px;
}
.order-side-card h2 {
  color: #2b3340;
  font-size: 23px;
  font-weight: 600;
}
.delivery-detail-row {
  margin-top: 14px;
  border: 1px solid #e4e9f0;
  background: #f8fafc;
  border-radius: 15px;
  padding: 12px;
  display: grid;
  grid-template-columns: 24px minmax(0, 1fr) 20px;
  align-items: center;
  gap: 10px;
  color: #2f3846;
}
.delivery-detail-icon {
  color: #394352;
}
.delivery-detail-text {
  font-size: 14px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.delivery-detail-text strong {
  font-weight: 700;
}
.delivery-detail-arrow {
  font-size: 18px;
  color: #394250;
  line-height: 1;
  text-align: right;
}
.price-grid {
  margin-top: 14px;
  border: 1px solid #e4e9f0;
  border-bottom: none;
  border-radius: 15px 15px 0 0;
  background: #fafbfd;
  padding: 14px;
}
.price-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  color: #333c4a;
  font-size: 14px;
  line-height: 1.6;
}
.price-row strong {
  color: #1f2935;
}
.price-row .is-strike {
  text-decoration: line-through;
  color: #586375;
}
.price-row .is-green {
  color: #1f973f;
}
.price-total-row {
  border: 1px solid #e4e9f0;
  border-top: none;
  padding: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 16px;
  font-weight: 700;
  color: #1d2735;
}
.paid-by-box {
  border: 1px solid #e4e9f0;
  border-top: none;
  border-radius: 0 0 15px 15px;
  background: #fff;
  padding: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  font-size: 13px;
  color: #333c49;
}
.paid-by-box strong {
  color: #1f2936;
  font-size: 13px;
}
@media (max-width: 1400px) {
  .order-track-grid {
    grid-template-columns: 1fr;
  }
  .order-side-card h2 { font-size: 20px; }
  .delivery-detail-text,
  .price-row,
  .price-total-row,
  .paid-by-box {
    font-size: 13px;
  }
  .delivery-detail-arrow { font-size: 16px; }
  .track-bottom-btn {
    font-size: 15px;
    min-height: 58px;
  }
}
@media (max-width: 1100px) {
  .account-shell.account-shell-wide {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 760px) {
  .order-track-top {
    grid-template-columns: 1fr;
  }
  .order-track-thumb-wrap {
    width: 92px;
    height: 92px;
  }
  .order-track-product h1 {
    font-size: 18px;
  }
  .order-track-price {
    font-size: 22px;
  }
  .track-step-text h3 {
    font-size: 14px;
  }
  .track-step-text p,
  .order-track-info-row,
  .track-updates-link {
    font-size: 13px;
  }
  .order-track-bottom-actions {
    grid-template-columns: 1fr;
  }
  .track-bottom-btn + .track-bottom-btn {
    border-left: none;
    border-top: 1px solid #e6ebf2;
  }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
