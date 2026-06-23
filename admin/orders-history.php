<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'orders-history';
$pageTitle   = 'Order History';

$controller = new AdminController();

$filters = ['search' => trim($_GET['search'] ?? '')];
$orders  = $controller->getOrderHistory($filters);

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Order History</div>
    <div class="pg-subtitle">Completed and cancelled orders archive.</div>
  </div>
</div>

<!-- Filter -->
<form method="GET" action="orders-history" class="filter-bar">
  <input type="text" name="search" class="form-control" style="max-width:280px;" placeholder="Customer name or email…" value="<?= htmlspecialchars($filters['search']) ?>">
  <button type="submit" class="btn btn--primary">Search</button>
  <a href="orders-history" class="btn btn--outline">Reset</a>
</form>

<div class="card">
  <div class="card-header">
    <span class="card-title">Completed &amp; Cancelled Orders</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($orders) ?> orders</span>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($orders)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M3 12a9 9 0 105.195-8.195"/><polyline points="3 3 3 9 9 9"/></svg>
      <p>No order history found.</p>
    </div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Location</th>
          <th style="text-align:center;">Items</th>
          <th style="text-align:right;">Amount</th>
          <th>Status</th>
          <th>Date</th>
          <th>Detail</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <?php
          $oid  = (int)(float)($o->USER_ORDER_ID ?? 0);
          $onum = htmlspecialchars($o->ORDER_NUMBER ?? '');
          $cust = htmlspecialchars($o->CUSTOMER_NAME ?? 'Guest');
          $city = htmlspecialchars($o->CITY ?? '');
          $st   = htmlspecialchars($o->STATE ?? '');
          $loc  = trim($city.($city && $st ? ', ' : '').$st) ?: '—';
          $items= (int)($o->ITEM_COUNT ?? 0);
          $amt  = (float)($o->FINAL_TOTAL_AMT ?? 0);
          $sts  = (string)($o->ORDER_STATUS ?? '');
          $date = htmlspecialchars(date('d M Y', strtotime($o->ORDER_DATE ?? '')));
          $bc   = $sts === 'Order Delivered' ? 'badge--green' : 'badge--red';
        ?>
        <tr>
          <td><strong><?= $onum ?></strong></td>
          <td>
            <div><?= $cust ?></div>
            <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($o->COMMUNICATION_EMAIL_ID ?? '') ?></div>
          </td>
          <td style="font-size:12px;color:var(--text-muted);"><?= $loc ?></td>
          <td style="text-align:center;"><?= $items ?></td>
          <td style="text-align:right;">€<?= number_format($amt, 2) ?></td>
          <td><span class="badge <?= $bc ?>"><?= $sts ?></span></td>
          <td style="font-size:12px;color:var(--text-muted);"><?= $date ?></td>
          <td>
            <button class="btn btn--outline" style="padding:4px 10px;font-size:12px;"
              onclick="viewOrderDetail(<?= $oid ?>)">View</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Order Detail Modal -->
<div class="modal-overlay" id="detailModal">
  <div class="modal" style="max-width:640px;">
    <div class="modal-header">
      <span class="modal-title">Order Detail</span>
      <button class="modal-close" onclick="closeModal('detailModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body" id="detailBody" style="min-height:80px;text-align:center;color:var(--text-muted);">
      Loading…
    </div>
  </div>
</div>


<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
