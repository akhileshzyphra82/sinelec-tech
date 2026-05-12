<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'orders';
$pageTitle   = 'Active Orders';

$controller = new AdminController();

$filters = [
    'status' => trim($_GET['status'] ?? ''),
    'search' => trim($_GET['search'] ?? ''),
];
$orders = $controller->getActiveOrders($filters);

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Active Orders</div>
    <div class="pg-subtitle">Manage and update status of all pending orders.</div>
  </div>
</div>

<!-- Filters -->
<form method="GET" action="orders" class="filter-bar">
  <select name="status" class="form-control" style="max-width:220px;">
    <option value="">All Statuses</option>
    <?php foreach (['Payment Successful','Invoice Payment Pending','Dispatched'] as $s): ?>
    <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="search" class="form-control" style="max-width:240px;" placeholder="Customer name or email…" value="<?= htmlspecialchars($filters['search']) ?>">
  <button type="submit" class="btn btn--primary">Filter</button>
  <a href="orders" class="btn btn--outline">Reset</a>
</form>

<div class="card">
  <div class="card-header">
    <span class="card-title">Active Orders</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($orders) ?> orders</span>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($orders)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16Z"/></svg>
      <p>No active orders found.</p>
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
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <?php
          $oid   = (int)($o->ORDER_ID ?? 0);
          $onum  = htmlspecialchars($o->ORDER_NUMBER ?? '');
          $cust  = htmlspecialchars($o->CUSTOMER_NAME ?? 'Guest');
          $email = htmlspecialchars($o->COMMUNICATION_EMAIL_ID ?? '');
          $city  = htmlspecialchars($o->CITY ?? '');
          $state = htmlspecialchars($o->STATE ?? '');
          $loc   = trim($city.($city && $state ? ', ' : '').$state) ?: '—';
          $items = (int)($o->ITEM_COUNT ?? 0);
          $amt   = (float)($o->ORDER_TOTAL_AMT ?? 0);
          $st    = (string)($o->ORDER_CURRENT_STATUS ?? '');
          $date  = htmlspecialchars(date('d M Y', strtotime($o->ORDER_DATE ?? '')));
          $badgeClass = match($st) {
            'Payment Successful'      => 'badge--blue',
            'Invoice Payment Pending' => 'badge--amber',
            'Dispatched'              => 'badge--violet',
            default                   => 'badge--gray',
          };
        ?>
        <tr>
          <td><strong><?= $onum ?></strong></td>
          <td>
            <div><?= $cust ?></div>
            <div style="font-size:11px;color:var(--text-muted);"><?= $email ?></div>
          </td>
          <td style="color:var(--text-muted);font-size:12px;"><?= $loc ?></td>
          <td style="text-align:center;"><?= $items ?></td>
          <td style="text-align:right;">₹<?= number_format($amt, 2) ?></td>
          <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($st) ?></span></td>
          <td style="font-size:12px;color:var(--text-muted);"><?= $date ?></td>
          <td>
            <button class="btn btn--outline" style="padding:4px 10px;font-size:12px;"
              onclick="openOrderModal(<?= $oid ?>, <?= htmlspecialchars(json_encode($onum),ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($st),ENT_QUOTES) ?>)">
              Update
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Update Status Modal -->
<div class="modal-overlay" id="statusModal">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <span class="modal-title">Update Order Status — <span id="modal_onum"></span></span>
      <button class="modal-close" onclick="closeModal('statusModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateOrderStatus') ?>" class="form-grid" id="statusForm">
        <input type="hidden" name="order_id" id="modal_order_id">
        <div class="fg">
          <label class="fc">New Status <span class="req">*</span></label>
          <select name="order_status" id="modal_status" class="form-control" onchange="toggleDispatch(this.value)" required>
            <option value="">— Select Status —</option>
            <option value="Payment Successful">Payment Successful</option>
            <option value="Invoice Payment Pending">Invoice Payment Pending</option>
            <option value="Dispatched">Dispatched</option>
            <option value="Delivered">Delivered</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div id="dispatchFields" style="display:none;">
          <div class="fg" style="margin-bottom:10px;">
            <label class="fc">Courier Company</label>
            <input type="text" name="dispatch_courier_company" class="form-control" placeholder="e.g. DTDC, BlueDart">
          </div>
          <div class="fg" style="margin-bottom:10px;">
            <label class="fc">Tracking ID</label>
            <input type="text" name="dispatch_courier_tracking_id" class="form-control">
          </div>
          <div class="fg">
            <label class="fc">Tracking URL</label>
            <input type="url" name="dispatch_courier_tracking_url" class="form-control" placeholder="https://...">
          </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Save Status</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('statusModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openOrderModal(id, num, currentStatus) {
  document.getElementById('modal_order_id').value = id;
  document.getElementById('modal_onum').textContent = num;
  var sel = document.getElementById('modal_status');
  sel.value = '';
  toggleDispatch('');
  openModal('statusModal');
}
function toggleDispatch(val) {
  document.getElementById('dispatchFields').style.display = (val === 'Dispatched') ? 'block' : 'none';
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
