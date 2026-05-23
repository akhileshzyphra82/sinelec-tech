<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'sales-revenue';
$pageTitle   = 'Sales & Revenue';

$canView = sinelec_can('view');
$canEdit = sinelec_can('edit');
if (!$canView) {
    sinelec_set_flash('err', 'No permission to view Sales & Revenue.');
    header('location:dashboard'); exit();
}

$controller = new AdminController();

/* ══════════════════════════════════════════
   FILTERS & PERIOD PRESETS
══════════════════════════════════════════ */
$preset    = trim($_GET['preset']         ?? 'this_month');
$dateFrom  = trim($_GET['date_from']      ?? '');
$dateTo    = trim($_GET['date_to']        ?? '');
$catId     = (int)($_GET['category_id']  ?? 0);
$orderMode = trim($_GET['order_mode']     ?? '');
$payStatus = trim($_GET['payment_status'] ?? '');
$search    = trim($_GET['search']         ?? '');

/* Resolve preset → dates */
if ($preset !== 'custom') {
    switch ($preset) {
        case 'today':
            $dateFrom = $dateTo = date('Y-m-d'); break;
        case 'yesterday':
            $dateFrom = $dateTo = date('Y-m-d', strtotime('-1 day')); break;
        case 'this_week':
            $dateFrom = date('Y-m-d', strtotime('monday this week'));
            $dateTo   = date('Y-m-d'); break;
        case 'last_week':
            $dateFrom = date('Y-m-d', strtotime('monday last week'));
            $dateTo   = date('Y-m-d', strtotime('sunday last week')); break;
        case 'last_month':
            $dateFrom = date('Y-m-01', strtotime('first day of last month'));
            $dateTo   = date('Y-m-t',  strtotime('last day of last month')); break;
        case 'last_3_months':
            $dateFrom = date('Y-m-d', strtotime('-3 months'));
            $dateTo   = date('Y-m-d'); break;
        case 'last_6_months':
            $dateFrom = date('Y-m-d', strtotime('-6 months'));
            $dateTo   = date('Y-m-d'); break;
        case 'this_year':
            $dateFrom = date('Y-01-01');
            $dateTo   = date('Y-m-d'); break;
        case 'last_year':
            $yr       = (int)date('Y') - 1;
            $dateFrom = "$yr-01-01";
            $dateTo   = "$yr-12-31"; break;
        case 'all_time':
            $dateFrom = $dateTo = ''; break;
        default: /* this_month */
            $dateFrom = date('Y-m-01');
            $dateTo   = date('Y-m-d'); break;
    }
}

$filters = [
    'date_from'      => $dateFrom,
    'date_to'        => $dateTo,
    'order_mode'     => $orderMode,
    'payment_status' => $payStatus,
    'category_id'    => $catId ?: '',
    'search'         => $search,
];

/* ── Comparison period (same span, immediately before) ── */
$prevSummary = null;
if ($dateFrom && $dateTo) {
    $spanDays  = max(1, (int)(( strtotime($dateTo) - strtotime($dateFrom)) / 86400) + 1);
    $prevTo    = date('Y-m-d', strtotime($dateFrom) - 86400);
    $prevFrom  = date('Y-m-d', strtotime($prevTo)   - ($spanDays - 1) * 86400);
    $prevSummary = $controller->getSalesRevenueSummary([
        'date_from' => $prevFrom, 'date_to' => $prevTo,
        'order_mode' => $orderMode, 'payment_status' => $payStatus,
    ]);
}

/* ── Load all report data ── */
$summary     = $controller->getSalesRevenueSummary($filters);
$trendData   = $controller->getSalesTrendData($filters);
$categories  = $controller->getSalesByCategory($filters);
$topProducts = $controller->getSalesTopProducts($filters, 15);
$topCustomers= $controller->getSalesTopCustomers($filters, 10);
$byMode      = $controller->getSalesByMode($filters);
$orders      = $controller->getSalesOrderList($filters);
$allCats     = $controller->getAllCategories();

/* ── Helper functions ── */
function srFmt(float $v): string {
    return '€ ' . number_format($v, 2);
}
function srFmtK(float $v): string {
    if ($v >= 1000000) return '€' . number_format($v/1000000, 1) . 'M';
    if ($v >= 1000)    return '€' . number_format($v/1000, 1) . 'K';
    return '€' . number_format($v, 0);
}
function srDelta(?object $cur, ?object $prev, string $col): string {
    $c = (float)($cur->$col  ?? 0);
    $p = (float)($prev->$col ?? 0);
    if ($p == 0) return '';
    $pct = round(($c - $p) / $p * 100, 1);
    $clr = $pct >= 0 ? '#16a34a' : '#dc2626';
    $arr = $pct >= 0 ? '▲' : '▼';
    return "<span style='font-size:10px;font-weight:700;color:$clr;margin-left:4px;'>$arr " . abs($pct) . "%</span>";
}
function srPayBadge(string $s): string {
    return match($s) {
        'Payment Successful' => "<span style='background:#dcfce7;color:#16a34a;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;white-space:nowrap;'>✓ Paid</span>",
        'Payment Pending'    => "<span style='background:#fffbeb;color:#b45309;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;white-space:nowrap;'>Pending</span>",
        'Payment Failed'     => "<span style='background:#fee2e2;color:#dc2626;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;white-space:nowrap;'>Failed</span>",
        'Refund Initiated'   => "<span style='background:#ede9fe;color:#7c3aed;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;white-space:nowrap;'>Refund ↑</span>",
        'Refund Completed'   => "<span style='background:#ede9fe;color:#7c3aed;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;white-space:nowrap;'>Refunded</span>",
        default              => "<span style='background:#f1f5f9;color:#64748b;font-size:10px;padding:2px 7px;border-radius:10px;'>" . htmlspecialchars($s) . "</span>",
    };
}
function srOrderBadge(string $s): string {
    return match($s) {
        'Order Delivered'   => "<span style='background:#dcfce7;color:#16a34a;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;'>Delivered</span>",
        'Order Cancelled'   => "<span style='background:#fee2e2;color:#dc2626;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;'>Cancelled</span>",
        'Order Dispatch'    => "<span style='background:#dbeafe;color:#1d4ed8;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;'>Dispatched</span>",
        'Order In Transit'  => "<span style='background:#dbeafe;color:#1d4ed8;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;'>In Transit</span>",
        'Order Confirmed'   => "<span style='background:#f0fdf4;color:#15803d;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;'>Confirmed</span>",
        'Order Pending'     => "<span style='background:#fffbeb;color:#b45309;font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;'>Pending</span>",
        default             => "<span style='background:#f1f5f9;color:#64748b;font-size:10px;padding:2px 7px;border-radius:10px;'>" . htmlspecialchars($s) . "</span>",
    };
}

/* KPI shortcuts */
$kTotalRev  = (float)($summary->TOTAL_REVENUE   ?? 0);
$kNetRev    = (float)($summary->NET_REVENUE      ?? 0);
$kVAT       = (float)($summary->TOTAL_VAT        ?? 0);
$kOrders    = (int)  ($summary->TOTAL_ORDERS     ?? 0);
$kAOV       = (float)($summary->AVG_ORDER_VALUE  ?? 0);
$kCollected = (float)($summary->COLLECTED        ?? 0);
$kPending   = (float)($summary->PENDING          ?? 0);
$kDiscount  = (float)($summary->TOTAL_DISCOUNTS  ?? 0);
$kShipping  = (float)($summary->SHIPPING_REVENUE ?? 0);
$kCustomers = (int)  ($summary->UNIQUE_CUSTOMERS ?? 0);
$kCollRate  = $kTotalRev > 0 ? round($kCollected / $kTotalRev * 100, 1) : 0;

/* Chart data arrays */
$chartLabels  = array_map(fn($r) => $r->LABEL,       $trendData);
$chartRevenue = array_map(fn($r) => (float)$r->REVENUE,    $trendData);
$chartOrders  = array_map(fn($r) => (int)$r->ORDERS,       $trendData);
$chartNet     = array_map(fn($r) => (float)$r->NET_REVENUE, $trendData);
$chartVat     = array_map(fn($r) => (float)$r->VAT,         $trendData);

/* Mode palette */
$modePalette = ['Payment Gateway'=>'#8b5cf6','Bank Transfer'=>'#2563eb','Invoice'=>'#16a34a','Unknown'=>'#94a3b8'];

/* Period label */
$periodLabel = $dateFrom
    ? date('d M Y', strtotime($dateFrom)) . ($dateTo && $dateTo !== $dateFrom ? ' – ' . date('d M Y', strtotime($dateTo)) : '')
    : 'All Time';

/* Active preset name */
$presetNames = [
    'today'=>'Today','yesterday'=>'Yesterday','this_week'=>'This Week','last_week'=>'Last Week',
    'this_month'=>'This Month','last_month'=>'Last Month','last_3_months'=>'Last 3 Months',
    'last_6_months'=>'Last 6 Months','this_year'=>'This Year','last_year'=>'Last Year',
    'all_time'=>'All Time','custom'=>'Custom Range',
];

ob_start();
?>
<style>
/* ═══════════════════════ Sales & Revenue Styles ═══════════════════════ */
.sr-wrap            { max-width:1500px; margin:0 auto; padding:0 2px; }
.sr-hdr             { display:flex; align-items:center; justify-content:space-between;
                      flex-wrap:wrap; gap:10px; margin-bottom:16px; }
.sr-hdr h1          { font-size:20px; font-weight:800; color:#1e293b; margin:0; }

/* Period presets */
.sr-presets         { display:flex; flex-wrap:wrap; gap:4px; margin-bottom:12px; }
.sr-preset-btn      { padding:5px 12px; border-radius:20px; font-size:11px; font-weight:600;
                      border:1.5px solid #e2e8f0; background:#fff; color:#475569;
                      cursor:pointer; text-decoration:none; transition:all .15s; }
.sr-preset-btn:hover{ background:#f1f5f9; border-color:#cbd5e1; }
.sr-preset-btn.active{ background:#1e40af; color:#fff; border-color:#1e40af; }

/* Filter bar */
.sr-filter-bar      { background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                      padding:12px 16px; margin-bottom:16px;
                      display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.sr-filter-bar input,
.sr-filter-bar select{ padding:6px 10px; border:1.5px solid #e2e8f0; border-radius:6px;
                        font-size:12px; color:#1e293b; background:#f8fafc; }
.sr-filter-bar input:focus,
.sr-filter-bar select:focus{ outline:none; border-color:#3b82f6; }
.sr-filter-label    { font-size:11px; font-weight:600; color:#64748b; }
.sr-btn             { padding:6px 14px; border-radius:7px; font-size:12px; font-weight:600;
                      border:none; cursor:pointer; text-decoration:none; display:inline-flex;
                      align-items:center; gap:4px; }
.sr-btn-primary     { background:#1e40af; color:#fff; }
.sr-btn-outline     { background:#fff; color:#475569; border:1.5px solid #cbd5e1; }
.sr-btn-outline:hover{ background:#f8fafc; }

/* Period banner */
.sr-period-banner   { background:linear-gradient(135deg,#1e40af,#3b82f6); color:#fff;
                      border-radius:10px; padding:12px 20px; margin-bottom:16px;
                      display:flex; align-items:center; justify-content:space-between;
                      flex-wrap:wrap; gap:8px; }
.sr-period-banner .period-txt { font-size:13px; font-weight:700; }
.sr-period-banner .period-sub { font-size:11px; opacity:.8; margin-top:2px; }
.sr-period-banner .vs-badge   { background:rgba(255,255,255,.2); border-radius:6px;
                                 padding:4px 10px; font-size:11px; }

/* KPI grid */
.sr-kpi             { display:grid; grid-template-columns:repeat(10,1fr); gap:8px; margin-bottom:16px; }
.sr-kpi-card        { background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                      padding:10px 12px; position:relative; overflow:hidden; }
.sr-kpi-card::before{ content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.sr-kpi-card .lbl   { font-size:9px; font-weight:700; color:#64748b; text-transform:uppercase;
                      letter-spacing:.5px; margin-bottom:3px; }
.sr-kpi-card .val   { font-size:14px; font-weight:800; color:#1e293b; line-height:1.1; }
.sr-kpi-card .sub   { font-size:9px; color:#94a3b8; margin-top:2px; }

/* Charts row */
.sr-charts-row      { display:grid; grid-template-columns:1fr 320px; gap:12px; margin-bottom:16px; }
.sr-chart-card      { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; }
.sr-chart-card h3   { font-size:13px; font-weight:700; color:#1e293b; margin:0 0 12px;
                      display:flex; align-items:center; gap:6px; }
.sr-chart-wrap      { position:relative; height:260px; }
.sr-right-col       { display:flex; flex-direction:column; gap:12px; }

/* Mode bars */
.sr-mode-bar-row    { margin-bottom:8px; }
.sr-mode-bar-row .mode-name { font-size:11px; font-weight:600; color:#374151; margin-bottom:3px;
                               display:flex; justify-content:space-between; }
.sr-mode-bar-track  { height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden; }
.sr-mode-bar-fill   { height:100%; border-radius:4px; transition:width .4s; }
.sr-mode-stats      { display:flex; gap:10px; margin-top:3px; }
.sr-mode-stats span { font-size:10px; color:#64748b; }
.sr-mode-stats .col { color:#16a34a; }
.sr-mode-stats .pen { color:#f59e0b; }
.sr-mode-stats .fai { color:#dc2626; }

/* Analytics row */
.sr-analytics-row   { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
.sr-table-card      { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
.sr-table-card-hdr  { padding:12px 16px; border-bottom:1px solid #f1f5f9;
                      display:flex; justify-content:space-between; align-items:center; }
.sr-table-card-hdr h3 { font-size:13px; font-weight:700; color:#1e293b; margin:0; }
.sr-tbl             { width:100%; border-collapse:collapse; font-size:12px; }
.sr-tbl th          { padding:8px 12px; background:#f8fafc; color:#64748b; font-size:10px;
                      font-weight:700; text-transform:uppercase; letter-spacing:.4px;
                      border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.sr-tbl td          { padding:8px 12px; border-bottom:1px solid #f8fafc; color:#374151;
                      vertical-align:middle; }
.sr-tbl tr:last-child td { border-bottom:none; }
.sr-tbl tr:hover td { background:#f8fafc; }
.sr-tbl tfoot td    { background:#f1f5f9; font-weight:700; color:#1e293b;
                      border-top:2px solid #e2e8f0; }
.sr-mini-bar        { display:flex; align-items:center; gap:6px; }
.sr-mini-bar-track  { width:60px; height:5px; background:#f1f5f9; border-radius:3px; flex-shrink:0; }
.sr-mini-bar-fill   { height:100%; border-radius:3px; background:#3b82f6; }
.sr-rank-badge      { width:20px; height:20px; border-radius:50%; background:#f1f5f9;
                      color:#475569; font-size:10px; font-weight:700;
                      display:inline-flex; align-items:center; justify-content:center; }
.sr-rank-1          { background:#fef9c3; color:#854d0e; }
.sr-rank-2          { background:#f3f4f6; color:#374151; }
.sr-rank-3          { background:#fef3c7; color:#92400e; }

/* Customers table full width */
.sr-full-card       { background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                      overflow:hidden; margin-bottom:16px; }

/* Transaction table */
.sr-tx-card         { background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                      overflow:hidden; margin-bottom:16px; }
.sr-tx-hdr          { padding:12px 16px; border-bottom:1px solid #f1f5f9;
                      display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; }
.sr-tbl-wrap        { overflow-x:auto; }

/* Pagination */
.sr-pgbar           { display:flex; align-items:center; justify-content:space-between;
                      padding:10px 16px; border-bottom:1px solid #f1f5f9; flex-wrap:wrap; gap:8px; }
.sr-pgbar__info     { font-size:12px; color:#64748b; }
.sr-pgbar__pager    { display:flex; gap:4px; align-items:center; }
.sr-page-btn        { width:28px; height:28px; border-radius:50%; border:1.5px solid #e2e8f0;
                      background:#fff; font-size:12px; font-weight:600; color:#475569;
                      cursor:pointer; display:flex; align-items:center; justify-content:center; }
.sr-page-btn:hover  { background:#f1f5f9; }
.sr-page-btn.cur    { background:#1e40af; color:#fff; border-color:#1e40af; }
.sr-page-nav        { padding:0 10px; height:28px; border-radius:14px; border:1.5px solid #e2e8f0;
                      background:#fff; font-size:12px; font-weight:600; color:#475569;
                      cursor:pointer; display:flex; align-items:center; gap:4px; }
.sr-page-nav:disabled{ opacity:.4; cursor:not-allowed; }

/* Empty state */
.sr-empty           { text-align:center; padding:40px; color:#94a3b8; font-size:13px; }

/* Custom date row */
#srCustomDates      { display:<?= $preset === 'custom' ? 'flex' : 'none' ?>;
                       align-items:center; gap:8px; flex-wrap:wrap; }

/* Print */
@media print {
    .sr-presets,.sr-filter-bar,.sr-hdr .sr-btn,.no-print { display:none !important; }
    .sr-chart-wrap { height:200px; }
}
</style>

<div class="sr-wrap">

<!-- ── Header ── -->
<div class="sr-hdr">
  <h1>📊 Sales &amp; Revenue Report</h1>
  <div style="display:flex;gap:8px;flex-wrap:wrap;" class="no-print">
    <a href="javascript:window.print()" class="sr-btn sr-btn-outline">🖨️ Print</a>
    <a href="#" onclick="srExportCsv()" class="sr-btn sr-btn-outline">📥 CSV</a>
  </div>
</div>

<!-- ── Period Presets ── -->
<div class="sr-presets no-print">
  <?php
  $presetList = [
    'today'=>'Today','yesterday'=>'Yesterday','this_week'=>'This Week','last_week'=>'Last Week',
    'this_month'=>'This Month','last_month'=>'Last Month','last_3_months'=>'Last 3 M',
    'last_6_months'=>'Last 6 M','this_year'=>'This Year','last_year'=>'Last Year',
    'all_time'=>'All Time','custom'=>'Custom…',
  ];
  foreach ($presetList as $k => $lbl):
    $qs = http_build_query(array_merge(['preset'=>$k], $k==='custom'?['date_from'=>$dateFrom,'date_to'=>$dateTo]:[],
                                       $orderMode ? ['order_mode'=>$orderMode] : [],
                                       $payStatus ? ['payment_status'=>$payStatus] : [],
                                       $catId     ? ['category_id'=>$catId] : []));
  ?>
  <a href="sales-revenue?<?= $qs ?>" class="sr-preset-btn <?= $preset===$k?'active':'' ?>"><?= $lbl ?></a>
  <?php endforeach; ?>
</div>

<!-- ── Filter Bar ── -->
<form method="GET" action="" class="sr-filter-bar no-print">
  <input type="hidden" name="preset" value="<?= htmlspecialchars($preset) ?>">
  <!-- Custom date range -->
  <div id="srCustomDates">
    <span class="sr-filter-label">From</span>
    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
    <span class="sr-filter-label">To</span>
    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
  </div>
  <!-- Filters -->
  <select name="category_id">
    <option value="">All Categories</option>
    <?php foreach ($allCats as $cat):
      $pid = (int)($cat->PARENT_CATEGORY_ID ?? 0);
      $label = $pid > 0 ? '↳ ' . htmlspecialchars($cat->PRODUCT_CATEGORY_NAME ?? '') : htmlspecialchars($cat->PRODUCT_CATEGORY_NAME ?? '');
    ?>
    <option value="<?= (int)$cat->PRODUCT_CATEGORY_ID ?>" <?= $catId===(int)$cat->PRODUCT_CATEGORY_ID?'selected':'' ?>>
      <?= $label ?>
    </option>
    <?php endforeach; ?>
  </select>
  <select name="order_mode">
    <option value="">All Modes</option>
    <?php foreach (['Payment Gateway','Bank Transfer','Invoice'] as $m): ?>
    <option value="<?= $m ?>" <?= $orderMode===$m?'selected':'' ?>><?= $m ?></option>
    <?php endforeach; ?>
  </select>
  <select name="payment_status">
    <option value="">All Payment Status</option>
    <?php foreach (['Payment Successful','Payment Pending','Payment Failed','Refund Initiated','Refund Completed'] as $ps): ?>
    <option value="<?= $ps ?>" <?= $payStatus===$ps?'selected':'' ?>><?= $ps ?></option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="search" placeholder="Order#, customer, company…" value="<?= htmlspecialchars($search) ?>" style="width:180px;">
  <button type="submit" class="sr-btn sr-btn-primary">Apply</button>
  <a href="sales-revenue" class="sr-btn sr-btn-outline">Clear</a>
</form>

<!-- ── Period Banner ── -->
<div class="sr-period-banner">
  <div>
    <div class="period-txt">
      <?= $presetNames[$preset] ?? 'Custom' ?> &nbsp;·&nbsp; <?= htmlspecialchars($periodLabel) ?>
      <?php if ($catId): ?>&nbsp;·&nbsp; <?php foreach($allCats as $c) if((int)$c->PRODUCT_CATEGORY_ID===$catId) echo htmlspecialchars($c->PRODUCT_CATEGORY_NAME); ?><?php endif; ?>
      <?php if ($orderMode): ?>&nbsp;·&nbsp; <?= htmlspecialchars($orderMode) ?><?php endif; ?>
    </div>
    <div class="period-sub"><?= $kOrders ?> order(s) · <?= $kCustomers ?> customer(s)</div>
  </div>
  <?php if ($prevSummary && (float)($prevSummary->TOTAL_REVENUE??0) > 0): ?>
    <div class="vs-badge">
      vs previous period: <?= srDelta($summary, $prevSummary, 'TOTAL_REVENUE') ?>
      &nbsp;<span style="font-size:10px;opacity:.8;">(<?= srFmt((float)($prevSummary->TOTAL_REVENUE??0)) ?>)</span>
    </div>
  <?php endif; ?>
</div>

<!-- ══════════════════════════════════════
     KPI CARDS (10 across)
══════════════════════════════════════ -->
<div class="sr-kpi">
  <?php
  $cards = [
    ['Total Revenue','TOTAL_REVENUE',srFmt($kTotalRev),'Gross incl. VAT+shipping','#2563eb'],
    ['Net Revenue','NET_REVENUE',srFmt($kNetRev),'Excl. VAT','#0891b2'],
    ['Output VAT','TOTAL_VAT',srFmt($kVAT),'Tax collected','#dc2626'],
    ['Orders','TOTAL_ORDERS',(string)$kOrders,'Total orders placed','#7c3aed'],
    ['Avg Order Value','AVG_ORDER_VALUE',srFmt($kAOV),'Per order','#f59e0b'],
    ['Collected','COLLECTED',srFmt($kCollected),'Payment Successful','#16a34a'],
    ['Pending','PENDING',srFmt($kPending),'Awaiting payment','#f97316'],
    ['Discounts','TOTAL_DISCOUNTS',srFmt($kDiscount),'Total savings given','#ec4899'],
    ['Shipping','SHIPPING_REVENUE',srFmt($kShipping),'Shipping charged','#64748b'],
    ['Collection Rate','',number_format($kCollRate,1).'%','Of billed revenue','#10b981'],
  ];
  foreach ($cards as $i => [$lbl, $col, $val, $sub, $clr]):
  ?>
  <div class="sr-kpi-card" style="border-top-color:<?= $clr ?>;">
    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:<?= $clr ?>;border-radius:10px 10px 0 0;"></div>
    <div class="lbl"><?= $lbl ?></div>
    <div class="val" style="color:<?= ($col==='PENDING'||$col==='TOTAL_VAT') ? $clr : '#1e293b' ?>;"><?= $val ?></div>
    <div class="sub">
      <?= $sub ?>
      <?php if ($col && $prevSummary): echo srDelta($summary, $prevSummary, $col); endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══════════════════════════════════════
     CHARTS ROW
══════════════════════════════════════ -->
<div class="sr-charts-row">

  <!-- Revenue Trend Chart -->
  <div class="sr-chart-card">
    <h3>
      📈 Revenue Trend
      <span style="font-size:10px;font-weight:500;color:#94a3b8;margin-left:4px;">
        <?= count($trendData) ?> data point(s)
        · <?= count($trendData) <= 31 ? 'Daily' : (count($trendData) <= 13 ? 'Weekly' : 'Monthly') ?>
      </span>
      <!-- Legend -->
      <span style="margin-left:auto;display:flex;gap:12px;">
        <span style="font-size:10px;color:#2563eb;font-weight:600;">■ Gross Revenue</span>
        <span style="font-size:10px;color:#0ea5e9;font-weight:600;">■ Net Revenue</span>
        <span style="font-size:10px;color:#10b981;font-weight:600;">● Orders</span>
      </span>
    </h3>
    <?php if (empty($trendData)): ?>
      <div class="sr-empty">No revenue data for this period.</div>
    <?php else: ?>
    <div class="sr-chart-wrap"><canvas id="srTrendChart"></canvas></div>
    <?php endif; ?>
  </div>

  <!-- Right column: mode + status -->
  <div class="sr-right-col">

    <!-- By Payment Mode -->
    <div class="sr-chart-card" style="flex:1;">
      <h3>💳 By Payment Mode</h3>
      <?php if (empty($byMode)): ?>
        <div class="sr-empty" style="padding:20px;">No data.</div>
      <?php else:
        $totalModeRev = array_sum(array_map(fn($m) => (float)$m->REVENUE, $byMode));
        foreach ($byMode as $m):
          $mRev   = (float)$m->REVENUE;
          $mPct   = $totalModeRev > 0 ? round($mRev/$totalModeRev*100) : 0;
          $mClr   = $modePalette[(string)$m->ORDER_MODE] ?? '#94a3b8';
          $mCol   = (float)$m->COLLECTED;
          $mPen   = (float)$m->PENDING;
          $mFai   = (float)$m->FAILED;
      ?>
      <div class="sr-mode-bar-row">
        <div class="mode-name">
          <span style="color:<?= $mClr ?>;font-weight:700;"><?= htmlspecialchars($m->ORDER_MODE) ?></span>
          <span><?= srFmt($mRev) ?> (<?= $mPct ?>%)</span>
        </div>
        <div class="sr-mode-bar-track">
          <div class="sr-mode-bar-fill" style="width:<?= $mPct ?>%;background:<?= $mClr ?>;"></div>
        </div>
        <div class="sr-mode-stats">
          <span class="col">✓ <?= srFmtK($mCol) ?></span>
          <span class="pen">⏳ <?= srFmtK($mPen) ?></span>
          <?php if ($mFai > 0): ?><span class="fai">✕ <?= srFmtK($mFai) ?></span><?php endif; ?>
          <span style="color:#94a3b8;"><?= (int)$m->ORDER_COUNT ?> orders</span>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- Collection rate gauge -->
    <div class="sr-chart-card">
      <h3>🎯 Collection Rate</h3>
      <div style="text-align:center;padding:4px 0;">
        <div style="font-size:32px;font-weight:900;color:<?= $kCollRate>=80?'#16a34a':($kCollRate>=50?'#f59e0b':'#dc2626'); ?>;">
          <?= $kCollRate ?>%
        </div>
        <div style="font-size:11px;color:#64748b;margin:4px 0 8px;">of billed revenue collected</div>
        <div style="background:#f1f5f9;border-radius:8px;height:10px;overflow:hidden;">
          <div style="background:<?= $kCollRate>=80?'#16a34a':($kCollRate>=50?'#f59e0b':'#dc2626') ?>;
                      height:100%;width:<?= min(100,$kCollRate) ?>%;border-radius:8px;transition:width .5s;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:10px;color:#94a3b8;">
          <span>Collected <?= srFmt($kCollected) ?></span>
          <span>Pending <?= srFmt($kPending) ?></span>
        </div>
      </div>
    </div>

  </div><!-- .sr-right-col -->
</div>

<!-- ══════════════════════════════════════
     ANALYTICS ROW: Category + Products
══════════════════════════════════════ -->
<div class="sr-analytics-row">

  <!-- Category Revenue -->
  <div class="sr-table-card">
    <div class="sr-table-card-hdr">
      <h3>📂 Revenue by Category</h3>
      <span style="font-size:11px;color:#94a3b8;"><?= count($categories) ?> categories</span>
    </div>
    <?php if (empty($categories)): ?>
      <div class="sr-empty">No category data.</div>
    <?php else: ?>
    <div class="sr-tbl-wrap">
    <table class="sr-tbl">
      <thead>
        <tr>
          <th>Category</th>
          <th style="text-align:right;">Orders</th>
          <th style="text-align:right;">Units</th>
          <th style="text-align:right;">Net Rev</th>
          <th style="text-align:right;">Gross Rev</th>
          <th style="text-align:right;">Share</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $catTotNet = 0; $catTotGross = 0; $catTotUnits = 0; $catTotOrds = 0;
        foreach ($categories as $cat):
          $catTotNet   += (float)$cat->NET_REVENUE;
          $catTotGross += (float)$cat->GROSS_REVENUE;
          $catTotUnits += (int)$cat->UNITS_SOLD;
          $catTotOrds  += (int)$cat->ORDER_COUNT;
          $pct = (float)($cat->PCT_SHARE ?? 0);
        ?>
        <tr>
          <td>
            <div style="font-weight:600;font-size:12px;"><?= htmlspecialchars($cat->CATEGORY_NAME ?? '') ?></div>
            <?php if (($cat->PARENT_CATEGORY_ID ?? 0) > 0): ?>
              <div style="font-size:10px;color:#94a3b8;">in <?= htmlspecialchars($cat->PARENT_NAME ?? '') ?></div>
            <?php endif; ?>
          </td>
          <td style="text-align:right;"><?= number_format((int)$cat->ORDER_COUNT) ?></td>
          <td style="text-align:right;"><?= number_format((int)$cat->UNITS_SOLD) ?></td>
          <td style="text-align:right;"><?= srFmt((float)$cat->NET_REVENUE) ?></td>
          <td style="text-align:right;font-weight:700;"><?= srFmt((float)$cat->GROSS_REVENUE) ?></td>
          <td>
            <div class="sr-mini-bar">
              <div class="sr-mini-bar-track"><div class="sr-mini-bar-fill" style="width:<?= min(100,$pct) ?>%;"></div></div>
              <span style="font-size:10px;font-weight:600;"><?= $pct ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td>Total</td>
          <td style="text-align:right;"><?= number_format($catTotOrds) ?></td>
          <td style="text-align:right;"><?= number_format($catTotUnits) ?></td>
          <td style="text-align:right;"><?= srFmt($catTotNet) ?></td>
          <td style="text-align:right;"><?= srFmt($catTotGross) ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Top Products -->
  <div class="sr-table-card">
    <div class="sr-table-card-hdr">
      <h3>🏆 Top Products</h3>
      <span style="font-size:11px;color:#94a3b8;">by revenue</span>
    </div>
    <?php if (empty($topProducts)): ?>
      <div class="sr-empty">No product data.</div>
    <?php else: ?>
    <div class="sr-tbl-wrap">
    <table class="sr-tbl">
      <thead>
        <tr>
          <th>#</th>
          <th>Product</th>
          <th style="text-align:right;">Units</th>
          <th style="text-align:right;">Revenue</th>
          <th style="text-align:right;">Avg Price</th>
          <th>Share</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($topProducts as $p):
          $pPct = (float)($p->PCT_SHARE ?? 0);
          $rnkClass = match((int)($p->RANK??0)) {1=>'sr-rank-1',2=>'sr-rank-2',3=>'sr-rank-3',default=>''};
        ?>
        <tr>
          <td><span class="sr-rank-badge <?= $rnkClass ?>"><?= (int)($p->RANK??0) ?></span></td>
          <td>
            <div style="font-weight:600;font-size:11px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                 title="<?= htmlspecialchars($p->PRODUCT_NAME??'') ?>">
              <?= htmlspecialchars($p->PRODUCT_NAME ?? '') ?>
            </div>
            <div style="font-size:10px;color:#94a3b8;"><?= htmlspecialchars($p->PRODUCT_CODE ?? '') ?></div>
          </td>
          <td style="text-align:right;"><?= number_format((int)$p->UNITS_SOLD) ?></td>
          <td style="text-align:right;font-weight:700;"><?= srFmt((float)$p->GROSS_REVENUE) ?></td>
          <td style="text-align:right;color:#64748b;"><?= srFmt((float)($p->AVG_UNIT_PRICE??0)) ?></td>
          <td>
            <div class="sr-mini-bar">
              <div class="sr-mini-bar-track"><div class="sr-mini-bar-fill" style="width:<?= min(100,$pPct) ?>%;background:#7c3aed;"></div></div>
              <span style="font-size:10px;font-weight:600;"><?= $pPct ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>

</div><!-- .sr-analytics-row -->

<!-- ══════════════════════════════════════
     TOP CUSTOMERS
══════════════════════════════════════ -->
<div class="sr-full-card">
  <div class="sr-table-card-hdr">
    <h3>👥 Top Customers</h3>
    <span style="font-size:11px;color:#94a3b8;"><?= count($topCustomers) ?> customer(s) · <?= $kCustomers ?> total</span>
  </div>
  <?php if (empty($topCustomers)): ?>
    <div class="sr-empty">No customer data.</div>
  <?php else: ?>
  <div class="sr-tbl-wrap">
  <table class="sr-tbl">
    <thead>
      <tr>
        <th>#</th>
        <th>Customer</th>
        <th>Company</th>
        <th style="text-align:right;">Orders</th>
        <th style="text-align:right;">Total Revenue</th>
        <th style="text-align:right;">Avg Order</th>
        <th style="text-align:right;">Collected</th>
        <th style="text-align:right;">Pending</th>
        <th>Rev Share</th>
        <th>Last Order</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($topCustomers as $c):
        $cPct = (float)($c->PCT_SHARE ?? 0);
        $rnkClass = match((int)($c->RANK??0)) {1=>'sr-rank-1',2=>'sr-rank-2',3=>'sr-rank-3',default=>''};
        $collRate = (float)($c->TOTAL_REVENUE??0) > 0
            ? round((float)$c->COLLECTED / (float)$c->TOTAL_REVENUE * 100) : 0;
      ?>
      <tr>
        <td><span class="sr-rank-badge <?= $rnkClass ?>"><?= (int)($c->RANK??0) ?></span></td>
        <td>
          <div style="font-weight:600;"><?= htmlspecialchars($c->CUST_NAME ?? '—') ?></div>
          <div style="font-size:10px;color:#94a3b8;"><?= htmlspecialchars($c->CUST_EMAIL ?? '') ?></div>
        </td>
        <td style="font-size:12px;"><?= htmlspecialchars($c->CUST_COMPANY ?? '—') ?></td>
        <td style="text-align:right;"><?= (int)$c->ORDER_COUNT ?></td>
        <td style="text-align:right;font-weight:700;"><?= srFmt((float)$c->TOTAL_REVENUE) ?></td>
        <td style="text-align:right;color:#64748b;"><?= srFmt((float)$c->AVG_ORDER_VALUE) ?></td>
        <td style="text-align:right;color:#16a34a;font-weight:600;"><?= srFmt((float)$c->COLLECTED) ?></td>
        <td style="text-align:right;color:<?= (float)$c->PENDING>0?'#f59e0b':'#94a3b8' ?>;font-weight:600;">
          <?= srFmt((float)$c->PENDING) ?>
        </td>
        <td>
          <div class="sr-mini-bar">
            <div class="sr-mini-bar-track"><div class="sr-mini-bar-fill" style="width:<?= min(100,$cPct) ?>%;background:#16a34a;"></div></div>
            <span style="font-size:10px;font-weight:600;"><?= $cPct ?>%</span>
          </div>
        </td>
        <td style="font-size:11px;white-space:nowrap;color:#64748b;">
          <?= $c->LAST_ORDER_DATE ? date('d M Y', strtotime($c->LAST_ORDER_DATE)) : '—' ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<!-- ══════════════════════════════════════
     ALL TRANSACTIONS (paginated)
══════════════════════════════════════ -->
<div class="sr-tx-card">
  <div class="sr-tx-hdr">
    <h3 style="font-size:13px;font-weight:700;color:#1e293b;margin:0;">🧾 All Transactions</h3>
    <span style="font-size:11px;color:#94a3b8;"><?= count($orders) ?> record(s) · <?= htmlspecialchars($periodLabel) ?></span>
  </div>

  <!-- Pagination bar -->
  <div class="sr-pgbar" id="srPgBar">
    <div class="sr-pgbar__info">
      Showing <strong id="srRangeStart">1</strong>–<strong id="srRangeEnd">25</strong>
      of <strong id="srCount"><?= count($orders) ?></strong>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
      <label style="font-size:11px;color:#64748b;">Per page
        <select id="srPerPage" style="margin-left:4px;padding:4px 6px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:12px;" onchange="srInitPager()">
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </label>
    </div>
    <div id="srPager" class="sr-pgbar__pager"></div>
  </div>

  <!-- Table -->
  <div class="sr-tbl-wrap">
  <table class="sr-tbl" id="srOrdersTbl">
    <thead>
      <tr>
        <th>#</th>
        <th>Order #</th>
        <th>Date</th>
        <th>Customer</th>
        <th>Mode</th>
        <th>Order Status</th>
        <th>Pay Status</th>
        <th style="text-align:right;">Net (ex.VAT)</th>
        <th style="text-align:right;">VAT</th>
        <th style="text-align:right;">Shipping</th>
        <th style="text-align:right;">Discount</th>
        <th style="text-align:right;">Total</th>
        <th>Invoice</th>
      </tr>
    </thead>
    <tbody id="srTbody">
      <?php
      $txTotNet=0; $txTotVat=0; $txTotShip=0; $txTotDisc=0; $txTotGross=0;
      foreach ($orders as $idx => $o):
        $oNet   = (float)$o->FINAL_TOTAL_AMT - (float)$o->TAX_TOTAL_AMOUNT;
        $oVat   = (float)$o->TAX_TOTAL_AMOUNT;
        $oShip  = (float)$o->SHIPPING_AMT;
        $oDisc  = (float)$o->DISCOUNT_AMT;
        $oTotal = (float)$o->FINAL_TOTAL_AMT;
        $txTotNet+=$oNet; $txTotVat+=$oVat; $txTotShip+=$oShip; $txTotDisc+=$oDisc; $txTotGross+=$oTotal;

        $modeClr = match((string)($o->ORDER_MODE??'')) {
            'Payment Gateway' => ['#ede9fe','#7c3aed'],
            'Bank Transfer'   => ['#dbeafe','#1d4ed8'],
            default           => ['#f0fdf4','#15803d'],
        };
        $rowStyle = match((string)($o->PAYMENT_STATUS??'')) {
            'Payment Pending'  => 'border-left:3px solid #fbbf24;',
            'Payment Failed'   => 'border-left:3px solid #ef4444;',
            'Refund Initiated',
            'Refund Completed' => 'border-left:3px solid #8b5cf6;',
            default            => '',
        };
      ?>
      <tr data-row="<?= $idx ?>" style="<?= $rowStyle ?>">
        <td style="color:#94a3b8;font-size:11px;"><?= $idx+1 ?></td>
        <td>
          <a href="order-details?id=<?= EncryptURL('id='.(int)$o->USER_ORDER_ID) ?>"
             style="font-weight:700;color:#1e40af;font-size:11px;text-decoration:none;">
            <?= htmlspecialchars($o->ORDER_NUMBER ?? '') ?>
          </a>
          <?php if (!empty($o->CUSTOMER_PO_ID)): ?>
            <div style="font-size:9px;color:#94a3b8;">PO: <?= htmlspecialchars($o->CUSTOMER_PO_ID) ?></div>
          <?php endif; ?>
        </td>
        <td style="white-space:nowrap;font-size:11px;">
          <?= $o->ORDER_DATE ? date('d M Y', strtotime($o->ORDER_DATE)) : '—' ?>
          <div style="color:#94a3b8;font-size:10px;"><?= $o->ORDER_DATE ? date('H:i', strtotime($o->ORDER_DATE)) : '' ?></div>
        </td>
        <td>
          <div style="font-weight:600;font-size:12px;"><?= htmlspecialchars($o->CUST_NAME ?? '—') ?></div>
          <?php if (!empty($o->CUST_COMPANY)): ?>
            <div style="font-size:10px;color:#64748b;"><?= htmlspecialchars($o->CUST_COMPANY) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <span style="background:<?= $modeClr[0] ?>;color:<?= $modeClr[1] ?>;font-size:10px;
                        font-weight:600;padding:2px 7px;border-radius:10px;white-space:nowrap;">
            <?= htmlspecialchars($o->ORDER_MODE ?? '—') ?>
          </span>
        </td>
        <td><?= srOrderBadge((string)($o->ORDER_STATUS??'')) ?></td>
        <td><?= srPayBadge((string)($o->PAYMENT_STATUS??'')) ?></td>
        <td style="text-align:right;font-weight:600;"><?= srFmt($oNet) ?></td>
        <td style="text-align:right;color:<?= $oVat>0?'#dc2626':'#94a3b8' ?>;">
          <?= srFmt($oVat) ?>
        </td>
        <td style="text-align:right;color:#64748b;"><?= srFmt($oShip) ?></td>
        <td style="text-align:right;color:<?= $oDisc>0?'#16a34a':'#94a3b8' ?>;">
          <?= $oDisc > 0 ? '–'.srFmt($oDisc) : srFmt(0) ?>
        </td>
        <td style="text-align:right;font-weight:700;font-size:13px;"><?= srFmt($oTotal) ?></td>
        <td style="font-size:11px;color:#64748b;">
          <?= htmlspecialchars($o->INVOICE_NO ?? '—') ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="7" style="text-align:right;">Period Totals</td>
        <td style="text-align:right;"><?= srFmt($txTotNet) ?></td>
        <td style="text-align:right;color:#dc2626;"><?= srFmt($txTotVat) ?></td>
        <td style="text-align:right;"><?= srFmt($txTotShip) ?></td>
        <td style="text-align:right;color:#16a34a;">–<?= srFmt($txTotDisc) ?></td>
        <td style="text-align:right;"><?= srFmt($txTotGross) ?></td>
        <td></td>
      </tr>
    </tfoot>
  </table>
  </div>
</div>

</div><!-- .sr-wrap -->

<!-- ═══════════════════════════════════════
     CHART.JS + PAGE SCRIPTS
═══════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
  /* ── Chart data from PHP ── */
  const labels  = <?= json_encode($chartLabels)  ?>;
  const revenue = <?= json_encode($chartRevenue) ?>;
  const netRev  = <?= json_encode($chartNet)     ?>;
  const orders  = <?= json_encode($chartOrders)  ?>;

  Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
  Chart.defaults.color = '#64748b';

  const ctx = document.getElementById('srTrendChart')?.getContext('2d');
  if (ctx && labels.length) {
    new Chart(ctx, {
      data: {
        labels,
        datasets: [
          {
            type: 'bar',
            label: 'Gross Revenue (€)',
            data: revenue,
            backgroundColor: 'rgba(37,99,235,0.18)',
            borderColor: '#2563eb',
            borderWidth: 1.5,
            borderRadius: 4,
            yAxisID: 'yRev',
            order: 3,
          },
          {
            type: 'bar',
            label: 'Net Revenue (€)',
            data: netRev,
            backgroundColor: 'rgba(8,145,178,0.25)',
            borderColor: '#0891b2',
            borderWidth: 1.5,
            borderRadius: 4,
            yAxisID: 'yRev',
            order: 2,
          },
          {
            type: 'line',
            label: 'Orders',
            data: orders,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#10b981',
            pointRadius: labels.length <= 7 ? 4 : 2,
            fill: true,
            tension: 0.35,
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
              label: ctx => {
                if (ctx.dataset.yAxisID === 'yRev')
                  return ' ' + ctx.dataset.label.replace(' (€)','') + ': €' +
                         ctx.parsed.y.toLocaleString('de-DE', {minimumFractionDigits:2});
                return ' Orders: ' + ctx.parsed.y;
              }
            }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 10 } } },
          yRev: {
            position: 'left',
            grid: { color: 'rgba(0,0,0,0.04)' },
            ticks: { font: {size:10}, callback: v => '€'+(v>=1000?(v/1000).toFixed(1)+'K':v) }
          },
          yOrd: {
            position: 'right', grid: { display: false },
            ticks: { font: {size:10}, stepSize: 1 }
          }
        }
      }
    });
  }
})();

/* ══════════════════
   Client-side pagination for transaction table
══════════════════ */
const srRows = Array.from(document.querySelectorAll('#srTbody tr[data-row]'));
let srPage   = 1;
let srPer    = 25;

function srInitPager() {
  srPer  = parseInt(document.getElementById('srPerPage').value) || 25;
  srPage = 1;
  srRender();
}

function srGoPage(p) {
  const total = Math.ceil(srRows.length / srPer);
  srPage = Math.max(1, Math.min(p, total));
  srRender();
}

function srRender() {
  const total = Math.ceil(srRows.length / srPer) || 1;
  const start = (srPage - 1) * srPer;
  const end   = Math.min(start + srPer, srRows.length);

  srRows.forEach((tr, i) => tr.style.display = (i >= start && i < end) ? '' : 'none');

  document.getElementById('srRangeStart').textContent = srRows.length ? start + 1 : 0;
  document.getElementById('srRangeEnd').textContent   = end;
  document.getElementById('srCount').textContent      = srRows.length;

  /* Build pager */
  const pager  = document.getElementById('srPager');
  pager.innerHTML = '';

  const prev = document.createElement('button');
  prev.className = 'sr-page-nav'; prev.textContent = '‹ Prev';
  prev.disabled  = srPage === 1;
  prev.onclick   = () => srGoPage(srPage - 1);
  pager.appendChild(prev);

  const maxBtns = 7;
  let pStart = Math.max(1, srPage - Math.floor(maxBtns/2));
  let pEnd   = Math.min(total, pStart + maxBtns - 1);
  if (pEnd - pStart < maxBtns - 1) pStart = Math.max(1, pEnd - maxBtns + 1);

  for (let p = pStart; p <= pEnd; p++) {
    if (p === srPage) continue; /* skip current */
    const btn = document.createElement('button');
    btn.className = 'sr-page-btn';
    btn.textContent = p;
    btn.onclick = () => srGoPage(p);
    pager.appendChild(btn);
  }

  const next = document.createElement('button');
  next.className = 'sr-page-nav'; next.textContent = 'Next ›';
  next.disabled  = srPage === total;
  next.onclick   = () => srGoPage(srPage + 1);
  pager.appendChild(next);
}

srRender();

/* ══════════════════
   CSV Export
══════════════════ */
function srExportCsv() {
  const tbl = document.getElementById('srOrdersTbl');
  if (!tbl) return;
  /* Show all rows temporarily for export */
  srRows.forEach(tr => tr.style.display = '');
  let csv = [];
  tbl.querySelectorAll('thead tr, tbody tr, tfoot tr').forEach(row => {
    const cols = [...row.querySelectorAll('th,td')]
      .map(c => '"' + c.innerText.replace(/"/g,'""').trim() + '"');
    csv.push(cols.join(','));
  });
  srRender(); /* restore pagination */
  const blob = new Blob([csv.join('\n')], {type:'text/csv'});
  const a    = document.createElement('a');
  a.href     = URL.createObjectURL(blob);
  a.download = 'sales-revenue-<?= date('Y-m-d') ?>.csv';
  a.click();
}

/* Toggle custom date input */
document.querySelectorAll('.sr-preset-btn').forEach(btn => {
  btn.addEventListener('click', function(e) {
    const href  = this.getAttribute('href') || '';
    const isCustom = href.includes('preset=custom');
    document.getElementById('srCustomDates').style.display = isCustom ? 'flex' : 'none';
    document.querySelector('input[name="preset"]').value = isCustom ? 'custom' : '';
  });
});
</script>
<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
