<?php
require_once __DIR__ . '/account-helpers.php';
require_once __DIR__ . '/order-data.php';
$user = sinelec_require_login();
$currentPage = 'my-orders';
$pageTitle = 'My Orders | Sinelec Technologies';
require_once __DIR__ . '/header.php';

$pendingOrders = sinelec_orders_by_status('pending');
$deliveredOrders = sinelec_orders_by_status('delivered');
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

          <div class="orders-tab-row" role="tablist" aria-label="My orders tabs">
            <button type="button" class="orders-tab-btn is-active" data-tab="pending" role="tab" aria-selected="true">Order Pending (<?= count($pendingOrders) ?>)</button>
            <button type="button" class="orders-tab-btn" data-tab="delivered" role="tab" aria-selected="false">Order Delivered (<?= count($deliveredOrders) ?>)</button>
          </div>

          <div class="orders-tab-panel is-active" data-panel="pending">
            <?php foreach ($pendingOrders as $order): ?>
              <?php
                $statusLabel = trim((string)($order['status_label'] ?? ''));
                $subStatus = stripos($statusLabel, 'transit') !== false
                  ? 'Your item has been shipped.'
                  : 'Your order has been placed.';
              ?>
              <article class="order-list-card">
                <div class="order-list-image-wrap">
                  <img src="<?= htmlspecialchars((string)$order['image']) ?>" alt="<?= htmlspecialchars((string)$order['product']) ?>" class="order-list-image">
                </div>

                <div class="order-list-product">
                  <h2><?= htmlspecialchars((string)$order['product']) ?></h2>
                  <p class="order-list-muted">Order: <?= htmlspecialchars((string)$order['order_no']) ?> | SKU: <?= htmlspecialchars((string)$order['sku']) ?></p>
                  <p class="order-list-muted">Qty: <?= (int)$order['qty'] ?> | Date: <?= htmlspecialchars((string)$order['date']) ?></p>
                </div>

                <div class="order-list-price">
                  <strong>€<?= number_format((float)$order['total'], 2) ?></strong>
                  <div class="order-list-actions order-list-actions-price">
                    <a href="order-details?order=<?= urlencode((string)$order['order_no']) ?>">Track &amp; Details</a>
                  </div>
                </div>

                <div class="order-list-status">
                  <h3><span class="status-dot"></span>Delivery expected by <?= htmlspecialchars((string)$order['eta']) ?></h3>
                  <p><?= htmlspecialchars($subStatus) ?></p>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <div class="orders-tab-panel" data-panel="delivered">
            <?php foreach ($deliveredOrders as $order): ?>
              <article class="order-list-card">
                <div class="order-list-image-wrap">
                  <img src="<?= htmlspecialchars((string)$order['image']) ?>" alt="<?= htmlspecialchars((string)$order['product']) ?>" class="order-list-image">
                </div>

                <div class="order-list-product">
                  <h2><?= htmlspecialchars((string)$order['product']) ?></h2>
                  <p class="order-list-muted">Order: <?= htmlspecialchars((string)$order['order_no']) ?> | SKU: <?= htmlspecialchars((string)$order['sku']) ?></p>
                  <p class="order-list-muted">Qty: <?= (int)$order['qty'] ?> | Date: <?= htmlspecialchars((string)$order['date']) ?></p>
                </div>

                <div class="order-list-price">
                  <strong>€<?= number_format((float)$order['total'], 2) ?></strong>
                  <div class="order-list-actions order-list-actions-price">
                    <a href="order-details?order=<?= urlencode((string)$order['order_no']) ?>#invoice">Download Invoice</a>
                    <a href="order-details?order=<?= urlencode((string)$order['order_no']) ?>">View Details</a>
                  </div>
                </div>

                <div class="order-list-status">
                  <h3 class="is-delivered"><span class="status-dot"></span>Delivered on <?= htmlspecialchars((string)$order['delivered_on']) ?></h3>
                  <p>Your item has been delivered.</p>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </article>
      </section>
    </div>
  </div>
</main>

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
  background: #f4f6f9;
  border: 1px solid #dde4ec;
}
.order-list-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
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
}
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
@media (max-width: 900px) {
  .orders-tab-row {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const tabButtons = Array.from(document.querySelectorAll('.orders-tab-btn'));
  const panels = Array.from(document.querySelectorAll('.orders-tab-panel'));

  tabButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      const tabId = btn.getAttribute('data-tab');
      tabButtons.forEach(function (item) {
        const isActive = item === btn;
        item.classList.toggle('is-active', isActive);
        item.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
      panels.forEach(function (panel) {
        panel.classList.toggle('is-active', panel.getAttribute('data-panel') === tabId);
      });
    });
  });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
