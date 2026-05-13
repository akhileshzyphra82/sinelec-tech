<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'dashboard';
$pageTitle   = 'Dashboard';

$ctrl = new AdminController();
$stats       = $ctrl->getDashboardStats();
$recentOrders= $ctrl->getRecentOrders(8);
$chartData   = $ctrl->getMonthlyOrderChart();
$donutData   = $ctrl->getOrderStatusBreakdown();
$pendingRFQs = $ctrl->getPendingEnquiries(8);
$recentEnqs  = $ctrl->getRecentEnquiries(5);
$lowStock    = $ctrl->getLowStockProducts(6);
$topProducts = $ctrl->getTopSellingProducts(5);

$adminFirstName = explode(' ', trim((string)($_SESSION['sinelec_admin']['NAME'] ?? 'Admin')))[0];

/* helpers */
function fmtMoney(float $v): string {
    if ($v >= 1_000_000) return '€'.number_format($v/1_000_000,2).'M';
    if ($v >= 1_000)     return '€'.number_format($v/1_000,1).'K';
    return '€'.number_format($v,2);
}
function orderBadge(string $st): string {
    return match($st) {
        'Payment Successful','Invoice Payment Successful',
        'Bank Transfer Payment Successful','Online Successful',
        'Other Channel Sell Successful'          => 'badge--blue',
        'Dispatched','Dispatched Invoice Payment Pending' => 'badge--violet',
        'Delivered'                              => 'badge--green',
        'Cancel Order'                           => 'badge--red',
        'Invoice Payment Pending','Bank Transfer Payment Pending',
        'Checkout'                               => 'badge--amber',
        default                                  => 'badge--grey',
    };
}
function enquiryBadge(string $st): string {
    return match($st) {
        'Quotation Pending'  => 'badge--amber',
        'Quotation Sent'     => 'badge--blue',
        'Order Generated'    => 'badge--violet',
        'Order Completed'    => 'badge--green',
        default              => 'badge--grey',
    };
}
function timeAgo(string $dt): string {
    $diff = time() - strtotime($dt);
    if ($diff < 60)        return 'just now';
    if ($diff < 3600)      return floor($diff/60).'m ago';
    if ($diff < 86400)     return floor($diff/3600).'h ago';
    if ($diff < 2592000)   return floor($diff/86400).'d ago';
    return date('d M Y', strtotime($dt));
}

$pendingCount = (int)($stats['pending_quotes'] ?? 0);

ob_start();
?>

<!-- ═══ PAGE HEADER ═══════════════════════════════════════════════ -->
<div class="dash-topbar">
  <div>
    <div class="pg-title">Good <?= (date('H')<12?'Morning':(date('H')<17?'Afternoon':'Evening')) ?>, <?= htmlspecialchars($adminFirstName) ?> 👋</div>
    <div class="pg-subtitle">Here's what's happening with your store today — <?= date('l, d F Y') ?></div>
  </div>
  <div class="dash-topbar-actions">
    <a href="enquiries" class="btn btn--primary btn--sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
      Enquiries
      <?php if ($pendingCount > 0): ?><span class="btn-badge"><?= $pendingCount ?></span><?php endif; ?>
    </a>
    <a href="orders" class="btn btn--outline btn--sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
      Active Orders
    </a>
  </div>
</div>

<!-- ═══ KPI CARDS — single row, compact ══════════════════════════ -->
<div class="kpi-row">

  <a href="orders" class="kpi-tile kpi-tile--blue" style="text-decoration:none">
    <div class="kpi-tile-icon">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
        <path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16Z"/>
        <path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
      </svg>
    </div>
    <div class="kpi-tile-body">
      <div class="kpi-tile-label">Active Orders</div>
      <div class="kpi-tile-value"><?= number_format((int)($stats['active_orders'] ?? 0)) ?></div>
      <div class="kpi-tile-sub"><?= number_format((int)($stats['orders_this_month'] ?? 0)) ?> this month</div>
    </div>
  </a>

  <a href="orders-history" class="kpi-tile kpi-tile--rose" style="text-decoration:none">
    <div class="kpi-tile-icon">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
        <circle cx="12" cy="12" r="10"/>
        <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
      </svg>
    </div>
    <div class="kpi-tile-body">
      <div class="kpi-tile-label">Cancel Orders</div>
      <div class="kpi-tile-value"><?= number_format((int)($stats['cancelled_orders'] ?? 0)) ?></div>
      <div class="kpi-tile-sub">Total cancelled</div>
    </div>
  </a>

  <div class="kpi-tile kpi-tile--emerald">
    <div class="kpi-tile-icon">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
      </svg>
    </div>
    <div class="kpi-tile-body">
      <div class="kpi-tile-label">Total Revenue</div>
      <div class="kpi-tile-value"><?= fmtMoney((float)($stats['total_revenue'] ?? 0)) ?></div>
      <div class="kpi-tile-sub">All completed orders</div>
    </div>
  </div>

  <a href="customers" class="kpi-tile kpi-tile--violet" style="text-decoration:none">
    <div class="kpi-tile-icon">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
      </svg>
    </div>
    <div class="kpi-tile-body">
      <div class="kpi-tile-label">Customers</div>
      <div class="kpi-tile-value"><?= number_format((int)($stats['customers'] ?? 0)) ?></div>
      <div class="kpi-tile-sub">Registered accounts</div>
    </div>
  </a>

  <a href="products" class="kpi-tile kpi-tile--indigo" style="text-decoration:none">
    <div class="kpi-tile-icon">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
        <rect x="3" y="3" width="18" height="18" rx="3"/><rect x="8" y="8" width="8" height="8" rx="1.5"/>
      </svg>
    </div>
    <div class="kpi-tile-body">
      <div class="kpi-tile-label">Products</div>
      <div class="kpi-tile-value"><?= number_format((int)($stats['products'] ?? 0)) ?></div>
      <div class="kpi-tile-sub">In catalogue</div>
    </div>
  </a>

  <a href="enquiries" class="kpi-tile kpi-tile--orange" style="text-decoration:none">
    <div class="kpi-tile-icon">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
      </svg>
    </div>
    <div class="kpi-tile-body">
      <div class="kpi-tile-label">Open Enquiries</div>
      <div class="kpi-tile-value"><?= number_format((int)($stats['enquiries'] ?? 0)) ?></div>
      <div class="kpi-tile-sub"><?= number_format($pendingCount) ?> awaiting quotation</div>
    </div>
  </a>

  <a href="orders" class="kpi-tile kpi-tile--sky" style="text-decoration:none">
    <div class="kpi-tile-icon">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
        <path d="M5 12h14"/><path d="M12 5l7 7-7 7"/>
      </svg>
    </div>
    <div class="kpi-tile-body">
      <div class="kpi-tile-label">Dispatched</div>
      <div class="kpi-tile-value"><?= number_format((int)($stats['dispatched'] ?? 0)) ?></div>
      <div class="kpi-tile-sub">Awaiting delivery</div>
    </div>
  </a>

  <a href="stock" class="kpi-tile <?= (int)($stats['low_stock']??0) > 0 ? 'kpi-tile--red kpi-tile--alert' : 'kpi-tile--teal' ?>" style="text-decoration:none">
    <div class="kpi-tile-icon">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
    </div>
    <div class="kpi-tile-body">
      <div class="kpi-tile-label">Low Stock</div>
      <div class="kpi-tile-value"><?= number_format((int)($stats['low_stock'] ?? 0)) ?></div>
      <div class="kpi-tile-sub"><?= (int)($stats['low_stock']??0)>0 ? 'Need restocking!' : 'All levels OK' ?></div>
    </div>
    <?php if ((int)($stats['low_stock']??0) > 0): ?>
    <span class="kpi-tile-pulse"></span>
    <?php endif; ?>
  </a>

</div>

<!-- ═══ PENDING QUOTES ALERT BANNER ═══════════════════════════════ -->
<?php if ($pendingCount > 0): ?>
<div class="rfq-alert-banner">
  <div class="rfq-alert-left">
    <span class="rfq-alert-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
      </svg>
    </span>
    <div>
      <div class="rfq-alert-title"><strong><?= $pendingCount ?> Quote Request<?= $pendingCount>1?'s':'' ?> Awaiting Response</strong></div>
      <div class="rfq-alert-sub">Customers are waiting for quotations. Review and send quotes to close deals.</div>
    </div>
  </div>
  <a href="enquiries" class="btn btn--white btn--sm">Review All Enquiries →</a>
</div>
<?php endif; ?>

<!-- ═══ PENDING RFQ TABLE ══════════════════════════════════════════ -->
<div class="card rfq-card">
  <div class="card-header">
    <span class="card-title">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px;color:#f59e0b"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
      Pending Quote Requests
      <?php if ($pendingCount > 0): ?>
      <span class="badge badge--amber" style="margin-left:8px;font-size:11px"><?= $pendingCount ?> Pending</span>
      <?php endif; ?>
    </span>
    <a href="enquiries" class="btn btn--outline btn--sm">View All</a>
  </div>
  <div class="card-body card-body--flush">
    <?php if (empty($pendingRFQs)): ?>
    <div class="empty-state">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
      <h3>No Pending Quotes</h3>
      <p>All quote requests have been responded to.</p>
    </div>
    <?php else: ?>
    <div class="rfq-table-wrap">
      <table class="dt">
        <thead>
          <tr>
            <th style="width:44px">#</th>
            <th>Customer</th>
            <th>Company</th>
            <th>Country</th>
            <th style="text-align:center">Items</th>
            <th>Received</th>
            <th style="text-align:center">Status</th>
            <th style="width:100px">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingRFQs as $i => $q): ?>
          <tr class="rfq-row">
            <td class="td-center" style="font-size:12px;color:var(--muted)"><?= (int)($q->enquiry_quote_id ?? 0) ?></td>
            <td>
              <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($q->user_name ?? '') ?></div>
              <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($q->user_email ?? '') ?></div>
            </td>
            <td style="font-size:13px"><?= htmlspecialchars($q->company_name ?? '—') ?></td>
            <td style="font-size:13px"><?= htmlspecialchars($q->delivery_country ?? '—') ?></td>
            <td class="td-center">
              <span class="item-count-badge"><?= (int)($q->item_count ?? 0) ?></span>
            </td>
            <td style="font-size:12px;color:var(--muted)">
              <?= timeAgo((string)($q->enquiry_date ?? '')) ?>
              <div style="font-size:11px"><?= date('d M Y', strtotime((string)($q->enquiry_date ?? ''))) ?></div>
            </td>
            <td class="td-center">
              <span class="badge badge--amber"><?= htmlspecialchars($q->enquiry_status ?? '') ?></span>
            </td>
            <td>
              <a href="enquiries?view=<?= (int)($q->enquiry_quote_id ?? 0) ?>" class="btn btn--primary btn--xs">
                Send Quote
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ═══ CHARTS ROW ═════════════════════════════════════════════════ -->
<div class="dash-row-2col">

  <!-- Monthly Revenue + Orders chart -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Revenue &amp; Orders — Last 12 Months</span>
      <div class="chart-legend">
        <span class="cl-dot" style="background:#2563eb"></span><span class="cl-lbl">Revenue</span>
        <span class="cl-dot" style="background:#10b981"></span><span class="cl-lbl">Orders</span>
      </div>
    </div>
    <div class="card-body" style="padding-top:8px">
      <div class="chart-wrap">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Order Status donut -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Orders by Status</span>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:16px;padding-top:8px">
      <div class="chart-wrap chart-wrap--donut">
        <canvas id="statusDonut"></canvas>
      </div>
      <div class="donut-legend" id="donutLegend"></div>
    </div>
  </div>

</div>

<!-- ═══ RECENT ORDERS + TOP PRODUCTS ══════════════════════════════ -->
<div class="dash-row-2col">

  <!-- Recent Orders -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Recent Orders</span>
      <a href="orders" class="btn btn--outline btn--sm">View All</a>
    </div>
    <div class="card-body card-body--flush">
      <?php if (empty($recentOrders)): ?>
      <div class="empty-state"><p>No orders yet.</p></div>
      <?php else: ?>
      <table class="dt">
        <thead>
          <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Status</th>
            <th style="text-align:right">Amount</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $o):
            $st = (string)($o->order_current_status ?? '');
          ?>
          <tr>
            <td><a href="orders?view=<?= (int)($o->order_id??0) ?>" style="font-weight:600;color:var(--primary);text-decoration:none;font-size:13px">#<?= htmlspecialchars((string)($o->order_number ?? $o->order_id ?? '')) ?></a></td>
            <td>
              <div style="font-size:13px;font-weight:500"><?= htmlspecialchars($o->name ?? 'Guest') ?></div>
              <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($o->communication_email_id ?? '') ?></div>
            </td>
            <td><span class="badge <?= orderBadge($st) ?>"><?= htmlspecialchars($st) ?></span></td>
            <td style="text-align:right;font-weight:600;font-size:13px">€<?= number_format((float)($o->order_total_amt??0),2) ?></td>
            <td style="font-size:12px;color:var(--muted)"><?= date('d M Y', strtotime((string)($o->order_date??''))) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Top Selling Products -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Top Selling Products</span>
      <a href="products" class="btn btn--outline btn--sm">View All</a>
    </div>
    <div class="card-body card-body--flush">
      <?php if (empty($topProducts)): ?>
      <div class="empty-state"><p>No sales data yet.</p></div>
      <?php else: ?>
      <?php
        $maxSold = (int)($topProducts[0]->total_sold ?? 1);
        $maxSold = max($maxSold, 1);
      ?>
      <div class="top-prod-list">
        <?php foreach ($topProducts as $i => $p): ?>
        <div class="top-prod-row">
          <span class="top-prod-rank"><?= $i+1 ?></span>
          <div class="top-prod-info">
            <div class="top-prod-name"><?= htmlspecialchars($p->product_name ?? '') ?></div>
            <div class="top-prod-code"><?= htmlspecialchars($p->product_code ?? '') ?> &bull; <?= htmlspecialchars($p->product_category_name ?? '') ?></div>
            <div class="top-prod-bar-wrap">
              <div class="top-prod-bar" style="width:<?= round((int)($p->total_sold??0) / $maxSold * 100) ?>%"></div>
            </div>
          </div>
          <div class="top-prod-sold">
            <div style="font-size:14px;font-weight:700;color:var(--text)"><?= number_format((int)($p->total_sold??0)) ?></div>
            <div style="font-size:11px;color:var(--muted)">units</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- ═══ LOW STOCK + RECENT ENQUIRIES ══════════════════════════════ -->
<div class="dash-row-2col">

  <!-- Low Stock Alerts -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">
        <?php if ((int)($stats['low_stock']??0) > 0): ?>
        <span style="display:inline-flex;align-items:center;gap:6px;color:#dc2626">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Low Stock Alerts
        </span>
        <?php else: ?>
        Stock Status
        <?php endif; ?>
      </span>
      <a href="stock" class="btn btn--outline btn--sm">View Stock</a>
    </div>
    <div class="card-body card-body--flush">
      <?php if (empty($lowStock)): ?>
      <div class="empty-state">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><polyline points="20 6 9 17 4 12"/></svg>
        <h3>All Stock Levels OK</h3>
        <p>No products are below their minimum threshold.</p>
      </div>
      <?php else: ?>
      <table class="dt">
        <thead>
          <tr>
            <th>Product</th>
            <th style="text-align:center">In Stock</th>
            <th style="text-align:center">Min.</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lowStock as $p):
            $qty   = (int)($p->total_product ?? 0);
            $min   = (int)($p->product_threshold ?? 1);
            $isOut = $qty <= 0;
          ?>
          <tr>
            <td>
              <div style="font-size:13px;font-weight:500"><?= htmlspecialchars($p->product_name ?? '') ?></div>
              <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($p->product_code ?? '') ?></div>
            </td>
            <td class="td-center" style="font-weight:700;color:<?= $isOut ? '#dc2626' : '#b45309' ?>;font-size:14px"><?= $qty ?></td>
            <td class="td-center" style="font-size:13px;color:var(--muted)"><?= $min ?></td>
            <td>
              <?php if ($isOut): ?>
              <span class="badge badge--red">Out of Stock</span>
              <?php else: ?>
              <span class="badge badge--amber">Low Stock</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Enquiries (all statuses) -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Recent Enquiries</span>
      <a href="enquiries" class="btn btn--outline btn--sm">View All</a>
    </div>
    <div class="card-body card-body--flush">
      <?php if (empty($recentEnqs)): ?>
      <div class="empty-state"><p>No enquiries yet.</p></div>
      <?php else: ?>
      <table class="dt">
        <thead>
          <tr>
            <th>Customer / Company</th>
            <th style="text-align:center">Items</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentEnqs as $q):
            $st = (string)($q->enquiry_status ?? '');
          ?>
          <tr>
            <td>
              <div style="font-size:13px;font-weight:500"><?= htmlspecialchars($q->user_name ?? '') ?></div>
              <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($q->company_name ?? '') ?> &bull; <?= htmlspecialchars($q->delivery_country ?? '') ?></div>
            </td>
            <td class="td-center"><span class="item-count-badge"><?= (int)($q->item_count ?? 0) ?></span></td>
            <td><span class="badge <?= enquiryBadge($st) ?>"><?= htmlspecialchars($st) ?></span></td>
            <td style="font-size:12px;color:var(--muted)"><?= timeAgo((string)($q->enquiry_date ?? '')) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- ═══ CHART.JS + DASHBOARD SCRIPTS ══════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
  /* ── data from PHP ── */
  const chartLabels  = <?= json_encode($chartData['labels']  ?? []) ?>;
  const chartOrders  = <?= json_encode($chartData['orders']  ?? []) ?>;
  const chartRevenue = <?= json_encode($chartData['revenue'] ?? []) ?>;

  const donutLabels  = <?= json_encode($donutData['labels'] ?? []) ?>;
  const donutValues  = <?= json_encode($donutData['data']   ?? []) ?>;

  /* ── palette ── */
  const COLORS = [
    '#2563eb','#10b981','#f59e0b','#8b5cf6','#ef4444',
    '#0ea5e9','#f97316','#14b8a6','#ec4899','#6366f1',
    '#22c55e','#a855f7','#06b6d4','#84cc16'
  ];

  Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
  Chart.defaults.color = '#64748b';

  /* ── Revenue + Orders line chart ── */
  const revCtx = document.getElementById('revenueChart')?.getContext('2d');
  if (revCtx && chartLabels.length) {
    new Chart(revCtx, {
      type: 'bar',
      data: {
        labels: chartLabels,
        datasets: [
          {
            label: 'Revenue (€)',
            data: chartRevenue,
            backgroundColor: 'rgba(37,99,235,0.15)',
            borderColor: '#2563eb',
            borderWidth: 2,
            borderRadius: 5,
            yAxisID: 'yRev',
            order: 2,
          },
          {
            label: 'Orders',
            data: chartOrders,
            type: 'line',
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#10b981',
            pointRadius: 3,
            fill: true,
            tension: 0.4,
            yAxisID: 'yOrd',
            order: 1,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0f172a',
            padding: 10,
            cornerRadius: 8,
            callbacks: {
              label: ctx => ctx.dataset.yAxisID === 'yRev'
                ? ' Revenue: €' + ctx.parsed.y.toLocaleString('de-DE', {minimumFractionDigits:2})
                : ' Orders: ' + ctx.parsed.y
            }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 11 } } },
          yRev: {
            position: 'left',
            grid: { color: 'rgba(0,0,0,0.05)' },
            ticks: {
              font: { size: 11 },
              callback: v => '€' + (v >= 1000 ? (v/1000).toFixed(1)+'K' : v)
            }
          },
          yOrd: {
            position: 'right',
            grid: { display: false },
            ticks: { font: { size: 11 }, stepSize: 1 }
          }
        }
      }
    });
  }

  /* ── Order Status donut ── */
  const donutCtx = document.getElementById('statusDonut')?.getContext('2d');
  if (donutCtx && donutLabels.length) {
    new Chart(donutCtx, {
      type: 'doughnut',
      data: {
        labels: donutLabels,
        datasets: [{
          data: donutValues,
          backgroundColor: COLORS.slice(0, donutLabels.length),
          borderWidth: 2,
          borderColor: '#fff',
          hoverOffset: 6,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0f172a',
            padding: 10,
            cornerRadius: 8,
          }
        }
      }
    });

    /* build custom legend */
    const total = donutValues.reduce((a, b) => a + b, 0);
    const leg = document.getElementById('donutLegend');
    if (leg) {
      leg.innerHTML = donutLabels.map((lbl, i) => `
        <div class="dl-row">
          <span class="dl-dot" style="background:${COLORS[i % COLORS.length]}"></span>
          <span class="dl-lbl">${lbl}</span>
          <span class="dl-val">${donutValues[i]}</span>
          <span class="dl-pct">${total ? Math.round(donutValues[i]/total*100) : 0}%</span>
        </div>`).join('');
    }
  } else if (donutCtx) {
    donutCtx.canvas.parentElement.innerHTML = '<div class="empty-state" style="padding:40px 0"><p>No order data yet.</p></div>';
  }
})();
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
