<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'dashboard';
$pageTitle   = 'Dashboard';

/* ══════════════════════════════════════════════
   SESSION & ROLE DETECTION
══════════════════════════════════════════════ */
$sess        = $_SESSION['sinelec_admin']  ?? [];
$userTypeId  = (int)($sess['USER_TYPE_ID'] ?? 0);
$roleId      = (int)($sess['ROLE_ID']      ?? 0);
$userName    = (string)($sess['NAME']      ?? 'User');
$userEmail   = (string)($sess['EMAIL']     ?? '');
$perms       = $sess['PERMISSIONS']        ?? [];   /* [menu_id => [can_view,...]] */
$isAdmin     = ($userTypeId === 1);
$firstName   = explode(' ', trim($userName))[0];

/* ── Role name lookup from menu_data ── */
$menuData    = $sess['MENU_DATA'] ?? [];
$roleName    = $isAdmin ? 'Administrator' : ($sess['ROLE_NAME'] ?? 'Employee');

/* Try to get role name from MENU_DATA group labels */
if (!$isAdmin && !empty($menuData)) {
    // Role name not directly in session; we derive from permissions scope
    $roleName = 'Employee';
}

/* ── Access flags ── */
function db2Can(int $menuId): bool {
    global $isAdmin, $perms;
    return $isAdmin || !empty($perms[$menuId]['can_view']);
}

/* Section visibility */
$showOrders    = db2Can(3);                     /* Operations → Order List     */
$showFinance   = db2Can(20) || db2Can(21) || db2Can(22); /* Finance & Taxation  */
$showAnalytics = db2Can(24);                    /* Analytics → Sales & Revenue */
$showProducts  = db2Can(9)  || db2Can(12);     /* Products / Inventory        */
$showCustomers = db2Can(8);                     /* Business Dev → Customers    */
$showQuotes    = db2Can(6);                     /* Business Dev → Quotation    */
$showRefunds   = db2Can(21);                    /* Finance → Refund            */
$showHR        = db2Can(18);                    /* HR → Job Post               */
$showMarketing = db2Can(13) || db2Can(16);     /* Marketing                   */
$showChart     = $showFinance || $showAnalytics;

/* If user has zero section access show friendly empty state */
$hasSomething  = $showOrders || $showFinance || $showProducts || $showCustomers
               || $showQuotes || $showRefunds || $showHR || $showMarketing;

/* ── Load data via single call ── */
$ctrl  = new AdminController();
$data  = $ctrl->getDashboardV2([
    'orders'    => $showOrders,
    'finance'   => $showChart,
    'products'  => $showProducts,
    'customers' => $showCustomers,
    'quotes'    => $showQuotes,
    'refunds'   => $showRefunds,
    'hr'        => $showHR,
]);

/* ── Shortcuts ── */
$oKpi  = $data['order_kpis']      ?? null;
$fKpi  = $data['finance_kpis']    ?? null;
$pKpi  = $data['product_kpis']    ?? null;
$cKpi  = $data['customer_kpis']   ?? null;
$qKpi  = $data['quote_kpis']      ?? null;
$rKpi  = $data['refund_kpis']     ?? null;
$hKpi  = $data['hr_kpis']         ?? null;
$chart = $data['revenue_chart']   ?? ['labels'=>[],'revArr'=>[],'ordArr'=>[],'colArr'=>[]];

/* ── Helpers ── */
function db2Fmt(float $v): string {
    if ($v >= 1_000_000) return '€'.number_format($v/1_000_000,2).'M';
    if ($v >= 1_000)     return '€'.number_format($v/1_000,1).'K';
    return '€'.number_format($v,2);
}
function db2PayBadge(string $s): string {
    return match($s) {
        'Payment Successful'  => '<span class="db2-badge db2-badge--green">Paid</span>',
        'Payment Pending'     => '<span class="db2-badge db2-badge--amber">Pending</span>',
        'Payment Failed'      => '<span class="db2-badge db2-badge--red">Failed</span>',
        'Refund Initiated'    => '<span class="db2-badge db2-badge--purple">Refund ↑</span>',
        'Refund Completed'    => '<span class="db2-badge db2-badge--purple">Refunded</span>',
        default               => '<span class="db2-badge db2-badge--grey">'.htmlspecialchars($s).'</span>',
    };
}
function db2OrderBadge(string $s): string {
    return match($s) {
        'Order Delivered'  => '<span class="db2-badge db2-badge--green">Delivered</span>',
        'Order Dispatch'   => '<span class="db2-badge db2-badge--blue">Dispatched</span>',
        'Order In Transit' => '<span class="db2-badge db2-badge--blue">In Transit</span>',
        'Order Confirmed'  => '<span class="db2-badge db2-badge--teal">Confirmed</span>',
        'Order Pending'    => '<span class="db2-badge db2-badge--amber">Pending</span>',
        'Order Packed'     => '<span class="db2-badge db2-badge--teal">Packed</span>',
        'Order Cancelled'  => '<span class="db2-badge db2-badge--red">Cancelled</span>',
        default            => '<span class="db2-badge db2-badge--grey">'.htmlspecialchars($s).'</span>',
    };
}
function db2QuoteBadge(string $s): string {
    return match($s) {
        'Quotation Pending' => '<span class="db2-badge db2-badge--amber">Pending</span>',
        'Quotation Sent'    => '<span class="db2-badge db2-badge--blue">Sent</span>',
        'Order Generated'   => '<span class="db2-badge db2-badge--teal">Order</span>',
        'Order Completed'   => '<span class="db2-badge db2-badge--green">Done</span>',
        default             => '<span class="db2-badge db2-badge--grey">'.htmlspecialchars($s).'</span>',
    };
}
function db2Ago(string $dt): string {
    $diff = time() - strtotime($dt);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return (int)($diff/60).'m ago';
    if ($diff < 86400)   return (int)($diff/3600).'h ago';
    if ($diff < 604800)  return (int)($diff/86400).'d ago';
    return date('d M', strtotime($dt));
}

/* Greeting */
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

ob_start();
?>
<style>
/* ═══════════════════════════════════════════════
   Dashboard V2 — Clean E-Commerce Style
═══════════════════════════════════════════════ */
.db2-wrap             { max-width:1460px; margin:0 auto; padding:0 2px; }

/* Welcome banner */
.db2-welcome          { background:linear-gradient(135deg,#1e3a8a 0%,#1d4ed8 55%,#2563eb 100%);
                         border-radius:14px; padding:22px 28px; margin-bottom:20px;
                         display:flex; align-items:center; justify-content:space-between;
                         flex-wrap:wrap; gap:16px; color:#fff; }
.db2-welcome-left h2  { font-size:22px; font-weight:800; margin:0 0 4px; }
.db2-welcome-left p   { font-size:12px; opacity:.8; margin:0; }
.db2-welcome-meta     { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.db2-role-chip        { background:rgba(255,255,255,.18); border-radius:20px;
                         padding:4px 12px; font-size:11px; font-weight:700; }
.db2-date-chip        { background:rgba(255,255,255,.12); border-radius:20px;
                         padding:4px 12px; font-size:11px; opacity:.9; }
.db2-quick-actions    { display:flex; gap:8px; flex-wrap:wrap; }
.db2-qa-btn           { background:rgba(255,255,255,.15); border:1.5px solid rgba(255,255,255,.3);
                         color:#fff; padding:6px 14px; border-radius:8px; font-size:11px;
                         font-weight:700; text-decoration:none; transition:all .15s; white-space:nowrap; }
.db2-qa-btn:hover     { background:rgba(255,255,255,.28); }

/* KPI strip */
.db2-kpi-strip        { display:grid; gap:10px; margin-bottom:20px; }
.db2-kpi-card         { background:#fff; border:1px solid #e2e8f0; border-radius:12px;
                         padding:14px 16px; position:relative; overflow:hidden; }
.db2-kpi-card__bar    { position:absolute; top:0; left:0; right:0; height:3px; border-radius:12px 12px 0 0; }
.db2-kpi-card__icon   { width:36px; height:36px; border-radius:9px; display:flex;
                         align-items:center; justify-content:center; margin-bottom:10px; }
.db2-kpi-card__icon svg{ width:18px; height:18px; }
.db2-kpi-card__lbl    { font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase;
                         letter-spacing:.5px; margin-bottom:4px; }
.db2-kpi-card__val    { font-size:22px; font-weight:900; color:#1e293b; line-height:1; margin-bottom:3px; }
.db2-kpi-card__sub    { font-size:10px; color:#94a3b8; }
.db2-kpi-card__alert  { display:inline-block; font-size:9px; font-weight:700; padding:2px 7px;
                         border-radius:10px; margin-top:4px; }

/* Charts + metrics row */
.db2-main-row         { display:grid; grid-template-columns:1fr 340px; gap:14px; margin-bottom:20px; }
.db2-card             { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
.db2-card-hdr         { padding:14px 18px; border-bottom:1px solid #f1f5f9;
                         display:flex; align-items:center; justify-content:space-between; }
.db2-card-hdr h3      { font-size:13px; font-weight:700; color:#1e293b; margin:0;
                         display:flex; align-items:center; gap:6px; }
.db2-card-hdr a       { font-size:11px; color:#2563eb; font-weight:600; text-decoration:none; }
.db2-card-hdr a:hover { text-decoration:underline; }
.db2-chart-wrap       { padding:16px; position:relative; height:260px; }

/* Right panel — order funnel */
.db2-right-panel      { display:flex; flex-direction:column; gap:14px; }
.db2-funnel-row       { display:flex; align-items:center; justify-content:space-between;
                         padding:8px 16px; border-bottom:1px solid #f8fafc; }
.db2-funnel-row:last-child { border-bottom:none; }
.db2-funnel-row .label{ font-size:12px; color:#475569; display:flex; align-items:center; gap:6px; }
.db2-funnel-row .dot  { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.db2-funnel-row .count{ font-size:15px; font-weight:800; color:#1e293b; }

/* Quick links grid */
.db2-quicklinks       { display:grid; grid-template-columns:1fr 1fr; gap:8px; padding:14px; }
.db2-ql-item          { display:flex; align-items:center; gap:8px; padding:8px 10px;
                         border:1.5px solid #e2e8f0; border-radius:9px; text-decoration:none;
                         transition:all .15s; }
.db2-ql-item:hover    { border-color:#bfdbfe; background:#eff6ff; }
.db2-ql-icon          { width:28px; height:28px; border-radius:7px; display:flex;
                         align-items:center; justify-content:center; flex-shrink:0; }
.db2-ql-icon svg      { width:14px; height:14px; }
.db2-ql-txt           { font-size:11px; font-weight:600; color:#374151; line-height:1.2; }

/* Tables section */
.db2-tables-row       { display:grid; gap:14px; margin-bottom:20px; }
.db2-tbl              { width:100%; border-collapse:collapse; font-size:12px; }
.db2-tbl th           { padding:9px 14px; background:#f8fafc; color:#64748b; font-size:10px;
                         font-weight:700; text-transform:uppercase; letter-spacing:.4px;
                         border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.db2-tbl td           { padding:9px 14px; border-bottom:1px solid #f8fafc;
                         color:#374151; vertical-align:middle; }
.db2-tbl tr:last-child td { border-bottom:none; }
.db2-tbl tr:hover td  { background:#f8fafc; }
.db2-tbl-wrap         { overflow-x:auto; }

/* Badges */
.db2-badge            { display:inline-block; padding:2px 8px; border-radius:12px;
                         font-size:10px; font-weight:700; white-space:nowrap; }
.db2-badge--green     { background:#dcfce7; color:#15803d; }
.db2-badge--amber     { background:#fef9c3; color:#b45309; }
.db2-badge--red       { background:#fee2e2; color:#b91c1c; }
.db2-badge--blue      { background:#dbeafe; color:#1d4ed8; }
.db2-badge--teal      { background:#ccfbf1; color:#0f766e; }
.db2-badge--purple    { background:#ede9fe; color:#6d28d9; }
.db2-badge--grey      { background:#f1f5f9; color:#64748b; }

/* Low stock bar */
.db2-stock-bar        { height:5px; background:#f1f5f9; border-radius:3px; margin-top:3px; }
.db2-stock-fill       { height:100%; border-radius:3px; }

/* Empty state */
.db2-empty            { text-align:center; padding:50px 20px; color:#94a3b8; }
.db2-empty svg        { width:48px; height:48px; opacity:.3; margin-bottom:12px; }
.db2-empty p          { font-size:14px; margin:0 0 6px; font-weight:600; }
.db2-empty small      { font-size:12px; }

/* Section heading */
.db2-section-label    { font-size:10px; font-weight:700; color:#94a3b8; text-transform:uppercase;
                         letter-spacing:.8px; margin:0 0 8px; padding:0 2px; }
</style>

<div class="db2-wrap">

<!-- ══════════════════════════════════════
     WELCOME BANNER
══════════════════════════════════════ -->
<div class="db2-welcome">
  <div class="db2-welcome-left">
    <h2><?= $greeting ?>, <?= htmlspecialchars($firstName) ?>! 👋</h2>
    <p>
      <?= htmlspecialchars($userEmail) ?> &nbsp;·&nbsp;
      <?= date('l, d F Y') ?> &nbsp;·&nbsp; <?= date('H:i') ?>
    </p>
  </div>
  <div style="display:flex;flex-direction:column;align-items:flex-end;gap:10px;">
    <div class="db2-welcome-meta">
      <span class="db2-role-chip">
        <?= $isAdmin ? '👑 Admin' : '👤 ' . htmlspecialchars($roleName) ?>
      </span>
      <?php if (!$isAdmin && $roleId): ?>
        <span class="db2-date-chip">Role ID: <?= $roleId ?></span>
      <?php endif; ?>
    </div>
    <!-- Quick action buttons based on access -->
    <div class="db2-quick-actions">
      <?php if ($showOrders): ?>
        <a href="order-list" class="db2-qa-btn">📦 Orders</a>
      <?php endif; ?>
      <?php if ($showQuotes): ?>
        <a href="quotation" class="db2-qa-btn">📋 Quotes</a>
      <?php endif; ?>
      <?php if ($showFinance): ?>
        <a href="open-invoices" class="db2-qa-btn">💰 Invoices</a>
      <?php endif; ?>
      <?php if ($showAnalytics): ?>
        <a href="sales-revenue" class="db2-qa-btn">📊 Analytics</a>
      <?php endif; ?>
      <?php if ($showProducts): ?>
        <a href="product-inventory-management" class="db2-qa-btn">🏭 Inventory</a>
      <?php endif; ?>
      <?php if ($showRefunds): ?>
        <a href="refund" class="db2-qa-btn">↩ Refunds</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if (!$hasSomething): ?>
<!-- ── No Access State ── -->
<div class="db2-card">
  <div class="db2-empty">
    <?= sb_icon_svg('lock') ?>
    <p>No dashboard data available</p>
    <small>Your role does not have access to any reporting modules yet.<br>Contact your administrator.</small>
  </div>
</div>
<?php else: ?>

<!-- ══════════════════════════════════════
     KPI STRIP — dynamic by role
══════════════════════════════════════ -->
<?php
/* Build KPI card list dynamically */
$kpiCards = [];

if ($showFinance && $fKpi) {
    $kpiCards[] = [
        'label' => 'Revenue Collected',
        'value' => db2Fmt((float)($fKpi->COLLECTED ?? 0)),
        'sub'   => 'This month: '.db2Fmt((float)($fKpi->THIS_MONTH_COL ?? 0)),
        'color' => '#16a34a',
        'icon'  => 'revenue',
    ];
    $kpiCards[] = [
        'label' => 'Pending Payments',
        'value' => number_format((int)($fKpi->PENDING_COUNT ?? 0)),
        'sub'   => db2Fmt((float)($fKpi->PENDING_REV ?? 0)),
        'color' => '#f59e0b',
        'icon'  => 'clock',
        'alert' => ((int)($fKpi->PENDING_COUNT ?? 0)) > 0 ? ['bg'=>'#fef9c3','c'=>'#b45309','text'=>'Needs attention'] : null,
    ];
}
if ($showOrders && $oKpi) {
    $kpiCards[] = [
        'label' => 'Total Orders',
        'value' => number_format((int)($oKpi->TOTAL ?? 0)),
        'sub'   => 'This month: '.(int)($oKpi->THIS_MONTH ?? 0),
        'color' => '#2563eb',
        'icon'  => 'order',
    ];
    if ((int)($oKpi->PENDING ?? 0) + (int)($oKpi->CONFIRMED ?? 0) > 0) {
        $kpiCards[] = [
            'label' => 'Active / In Process',
            'value' => number_format((int)($oKpi->PENDING ?? 0) + (int)($oKpi->CONFIRMED ?? 0) + (int)($oKpi->IN_TRANSIT ?? 0)),
            'sub'   => (int)($oKpi->IN_TRANSIT ?? 0).' in transit',
            'color' => '#7c3aed',
            'icon'  => 'truck',
        ];
    }
}
if ($showProducts && $pKpi) {
    $kpiCards[] = [
        'label' => 'Active Products',
        'value' => number_format((int)($pKpi->ACTIVE_PRODUCTS ?? 0)),
        'sub'   => (int)($pKpi->TOTAL_PRODUCTS ?? 0).' total in catalog',
        'color' => '#0891b2',
        'icon'  => 'product',
    ];
    $lowStockCount = (int)($pKpi->LOW_STOCK ?? 0);
    if ($lowStockCount > 0) {
        $kpiCards[] = [
            'label' => 'Low / Out of Stock',
            'value' => $lowStockCount,
            'sub'   => (int)($pKpi->OUT_OF_STOCK ?? 0).' out of stock',
            'color' => '#dc2626',
            'icon'  => 'warning',
            'alert' => ['bg'=>'#fee2e2','c'=>'#b91c1c','text'=>'Restock needed'],
        ];
    }
}
if ($showCustomers && $cKpi) {
    $kpiCards[] = [
        'label' => 'Total Customers',
        'value' => number_format((int)($cKpi->TOTAL_CUSTOMERS ?? 0)),
        'sub'   => 'Registered buyers',
        'color' => '#0ea5e9',
        'icon'  => 'user',
    ];
}
if ($showQuotes && $qKpi) {
    $kpiCards[] = [
        'label' => 'Quotations',
        'value' => number_format((int)($qKpi->TOTAL_QUOTES ?? 0)),
        'sub'   => (int)($qKpi->PENDING ?? 0).' pending · '.(int)($qKpi->CONVERTED ?? 0).' converted',
        'color' => '#8b5cf6',
        'icon'  => 'document',
        'alert' => ((int)($qKpi->PENDING ?? 0)) > 0 ? ['bg'=>'#ede9fe','c'=>'#6d28d9','text'=>(int)$qKpi->PENDING.' pending'] : null,
    ];
}
if ($showRefunds && $rKpi) {
    $pendingApproval = (int)($rKpi->PENDING_APPROVAL ?? 0);
    if ($pendingApproval > 0) {
        $kpiCards[] = [
            'label' => 'Refund Requests',
            'value' => number_format((int)($rKpi->TOTAL_RETURNS ?? 0)),
            'sub'   => $pendingApproval.' awaiting approval',
            'color' => '#dc2626',
            'icon'  => 'refund',
            'alert' => ['bg'=>'#fee2e2','c'=>'#b91c1c','text'=>$pendingApproval.' pending'],
        ];
    }
}
if ($showHR && $hKpi) {
    $kpiCards[] = [
        'label' => 'Job Openings',
        'value' => (int)($hKpi->ACTIVE_JOBS ?? 0),
        'sub'   => (int)($hKpi->TOTAL_JOBS ?? 0).' total positions',
        'color' => '#f59e0b',
        'icon'  => 'users',
    ];
}

$colCount = max(2, min(8, count($kpiCards)));
?>

<div class="db2-kpi-strip" style="grid-template-columns:repeat(<?= $colCount ?>,1fr);">
  <?php foreach ($kpiCards as $card): ?>
  <div class="db2-kpi-card">
    <div class="db2-kpi-card__bar" style="background:<?= $card['color'] ?>;"></div>
    <div class="db2-kpi-card__icon" style="background:<?= $card['color'] ?>18;">
      <?php
        $iconMap = [
          'revenue'=>'sales','order'=>'receipt_long','truck'=>'local_shipping',
          'product'=>'inventory','warning'=>'warning','user'=>'person',
          'document'=>'description','refund'=>'undo','users'=>'group','clock'=>'schedule',
        ];
        echo sb_icon_svg($iconMap[$card['icon']] ?? 'info');
      ?>
    </div>
    <div class="db2-kpi-card__lbl"><?= $card['label'] ?></div>
    <div class="db2-kpi-card__val" style="color:<?= $card['color'] ?>;"><?= $card['value'] ?></div>
    <div class="db2-kpi-card__sub"><?= $card['sub'] ?></div>
    <?php if (!empty($card['alert'])): ?>
      <span class="db2-kpi-card__alert" style="background:<?= $card['alert']['bg'] ?>;color:<?= $card['alert']['c'] ?>;">
        ⚠ <?= $card['alert']['text'] ?>
      </span>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══════════════════════════════════════
     MAIN ROW: Chart + Right Panel
══════════════════════════════════════ -->
<div class="db2-main-row" style="<?= !$showChart ? 'grid-template-columns:1fr;' : '' ?>">

  <!-- LEFT: Revenue Chart OR Order Status Funnel -->
  <?php if ($showChart && !empty($chart['labels'])): ?>
  <div class="db2-card">
    <div class="db2-card-hdr">
      <h3>📈 Revenue Trend — Last 6 Months</h3>
      <?php if ($showAnalytics): ?><a href="sales-revenue">Full Report →</a><?php endif; ?>
    </div>
    <div class="db2-chart-wrap">
      <canvas id="db2RevenueChart"></canvas>
    </div>
  </div>
  <?php elseif ($showOrders && $oKpi): ?>
  <div class="db2-card">
    <div class="db2-card-hdr">
      <h3>📦 Order Pipeline Overview</h3>
      <a href="order-list">View All →</a>
    </div>
    <div style="padding:20px;">
      <?php
      $pipeline = [
        ['Order Pending',    (int)($oKpi->PENDING    ?? 0), '#f59e0b'],
        ['Order Confirmed',  (int)($oKpi->CONFIRMED  ?? 0), '#2563eb'],
        ['In Transit',       (int)($oKpi->IN_TRANSIT ?? 0), '#7c3aed'],
        ['Delivered',        (int)($oKpi->DELIVERED  ?? 0), '#16a34a'],
        ['Cancelled',        (int)($oKpi->CANCELLED  ?? 0), '#dc2626'],
      ];
      $maxPipe = max(1, max(array_column($pipeline, 1)));
      foreach ($pipeline as [$label, $count, $clr]):
      ?>
      <div style="margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
          <span style="color:#475569;font-weight:600;"><?= $label ?></span>
          <span style="font-weight:800;color:<?= $clr ?>;"><?= number_format($count) ?></span>
        </div>
        <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
          <div style="height:100%;width:<?= round($count/$maxPipe*100) ?>%;background:<?= $clr ?>;border-radius:4px;transition:width .4s;"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- RIGHT: Order funnel (if chart shown) + Quick Links -->
  <div class="db2-right-panel">

    <?php if ($showChart && $showOrders && $oKpi): ?>
    <div class="db2-card">
      <div class="db2-card-hdr">
        <h3>📦 Order Status</h3>
        <a href="order-list">All →</a>
      </div>
      <?php
      $statusRows = [
        ['Order Pending',   (int)($oKpi->PENDING    ?? 0), '#f59e0b', 'Pending'],
        ['In Transit',      (int)($oKpi->IN_TRANSIT ?? 0), '#7c3aed', 'Shipping'],
        ['Delivered',       (int)($oKpi->DELIVERED  ?? 0), '#16a34a', 'Delivered'],
        ['Cancelled',       (int)($oKpi->CANCELLED  ?? 0), '#dc2626', 'Cancelled'],
      ];
      foreach ($statusRows as [$lbl, $cnt, $clr, $tag]):
      ?>
      <div class="db2-funnel-row">
        <div class="label">
          <span class="dot" style="background:<?= $clr ?>;"></span>
          <?= $lbl ?>
        </div>
        <div class="count" style="color:<?= $clr ?>;"><?= number_format($cnt) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Quick Links -->
    <div class="db2-card">
      <div class="db2-card-hdr"><h3>⚡ Quick Links</h3></div>
      <div class="db2-quicklinks">
        <?php
        $ql = [];
        if ($showOrders)    $ql[] = ['order-list',                    'receipt_long', '#dbeafe','#1d4ed8', 'Order List'];
        if ($showQuotes)    $ql[] = ['quotation',                     'description',  '#ede9fe','#7c3aed', 'Quotations'];
        if ($showProducts)  $ql[] = ['products',                      'inventory',    '#ccfbf1','#0f766e', 'Products'];
        if ($showProducts)  $ql[] = ['product-inventory-management',  'warehouse',    '#fef9c3','#b45309', 'Inventory'];
        if ($showCustomers) $ql[] = ['customers',                     'person',       '#dbeafe','#1d4ed8', 'Customers'];
        if ($showFinance)   $ql[] = ['open-invoices',                 'receipt_long', '#dcfce7','#15803d', 'Invoices'];
        if ($showRefunds)   $ql[] = ['refund',                        'undo',         '#fee2e2','#b91c1c', 'Refunds'];
        if ($showFinance)   $ql[] = ['monthly-vat-filling-document',  'percent',      '#f0fdf4','#16a34a', 'VAT Filing'];
        if ($showAnalytics) $ql[] = ['sales-revenue',                 'sales',        '#ede9fe','#7c3aed', 'Sales Report'];
        if ($showHR)        $ql[] = ['job-posting',                   'group',        '#fef9c3','#b45309', 'HR / Jobs'];
        if ($showMarketing) $ql[] = ['banners',                       'image',        '#f0fdf4','#16a34a', 'Banners'];
        if ($isAdmin)       $ql[] = ['employee-list',                 'people',       '#f8fafc','#64748b', 'Employees'];
        foreach (array_slice($ql, 0, 8) as [$href, $icon, $bg, $clr, $label]):
        ?>
        <a href="<?= $href ?>" class="db2-ql-item">
          <div class="db2-ql-icon" style="background:<?= $bg ?>;">
            <svg viewBox="0 0 24 24" fill="<?= $clr ?>" xmlns="http://www.w3.org/2000/svg">
              <?php
                $paths=[
                  'receipt_long'=>'<path d="M19.5 3.5L18 2l-1.5 1.5L15 2l-1.5 1.5L12 2l-1.5 1.5L9 2 7.5 3.5 6 2 4.5 3.5 3 2v20l1.5-1.5L6 22l1.5-1.5L9 22l1.5-1.5L12 22l1.5-1.5L15 22l1.5-1.5L18 22l1.5-1.5L21 22V2l-1.5 1.5zM19 19.09H5V4.91h14v14.18zM6 15h12v2H6zm0-4h12v2H6zm0-4h12v2H6z"/>',
                  'inventory'=>'<path d="M20 2H4v2l8 6 8-6V2zm0 20H4V8l8 6 8-6v14z"/>',
                  'description'=>'<path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11zM8 15h8v2H8zm0-4h8v2H8zm0-4h4v2H8z"/>',
                  'warehouse'=>'<path d="M22 21V7L12 3 2 7v14h5v-9h10v9h5zm-7 0h-2v-4h2v4zm-3 0H9v-4h3v4z"/>',
                  'undo'=>'<path d="M12.5 8c-2.65 0-5.05 1-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/>',
                  'person'=>'<path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>',
                  'group'=>'<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
                  'sales'=>'<path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>',
                  'image'=>'<path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>',
                  'percent'=>'<path d="M7.5 11C9.43 11 11 9.43 11 7.5S9.43 4 7.5 4 4 5.57 4 7.5 5.57 11 7.5 11zm0-5C8.33 6 9 6.67 9 7.5S8.33 9 7.5 9 6 8.33 6 7.5 6.67 6 7.5 6zM4.0 20.5l1.5 1.5 16-16-1.5-1.5zm12 .5c-1.93 0-3.5 1.57-3.5 3.5S14.07 24 16 24s3.5-1.57 3.5-3.5S17.93 21 16 21zm0 5c-.83 0-1.5-.67-1.5-1.5S15.17 23 16 23s1.5.67 1.5 1.5S16.83 26 16 26z"/>',
                  'people'=>'<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
                  'schedule'=>'<path d="M12 1C5.93 1 1 5.93 1 12s4.93 11 11 11 11-4.93 11-11S18.07 1 12 1zm0 20c-4.97 0-9-4.03-9-9s4.03-9 9-9 9 4.03 9 9-4.03 9-9 9zm.5-14H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>',
                  'warning'=>'<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>',
                ];
                echo $paths[$icon] ?? '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>';
              ?>
            </svg>
          </div>
          <span class="db2-ql-txt"><?= htmlspecialchars($label) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

  </div><!-- .db2-right-panel -->
</div><!-- .db2-main-row -->

<!-- ══════════════════════════════════════
     TABLES ROW
══════════════════════════════════════ -->
<?php
$tables = [];
if ($showOrders && !empty($data['recent_orders']))   $tables[] = 'orders';
if ($showQuotes && !empty($data['pending_quotes']))  $tables[] = 'quotes';
if ($showProducts && !empty($data['low_stock_items'])) $tables[] = 'stock';
if ($showHR && !empty($data['recent_applicants']))   $tables[] = 'hr';
$tableCols = count($tables) >= 2 ? 'repeat('.min(2,count($tables)).',1fr)' : '1fr';
?>
<?php if (!empty($tables)): ?>
<div class="db2-tables-row" style="grid-template-columns:<?= $tableCols ?>;">

  <?php if (in_array('orders', $tables)): ?>
  <!-- Recent Orders -->
  <div class="db2-card">
    <div class="db2-card-hdr">
      <h3>🧾 Recent Orders</h3>
      <a href="order-list">View All →</a>
    </div>
    <div class="db2-tbl-wrap">
    <table class="db2-tbl">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Mode</th>
          <th>Status</th>
          <th>Pay</th>
          <th style="text-align:right;">Amount</th>
          <th>When</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data['recent_orders'] as $o):
          $modeClr = match((string)($o->ORDER_MODE??'')) {
              'Payment Gateway' => ['#ede9fe','#7c3aed'],
              'Bank Transfer'   => ['#dbeafe','#1d4ed8'],
              default           => ['#f0fdf4','#15803d'],
          };
        ?>
        <tr>
          <td>
            <a href="order-details?id=<?= EncryptURL('id='.(int)$o->USER_ORDER_ID) ?>"
               style="font-weight:700;color:#1e40af;text-decoration:none;font-size:11px;">
              <?= htmlspecialchars($o->ORDER_NUMBER ?? '') ?>
            </a>
          </td>
          <td>
            <div style="font-weight:600;font-size:12px;"><?= htmlspecialchars($o->CUST_NAME ?? '—') ?></div>
            <?php if (!empty($o->CUST_COMPANY)): ?>
              <div style="font-size:10px;color:#94a3b8;"><?= htmlspecialchars($o->CUST_COMPANY) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span style="background:<?= $modeClr[0] ?>;color:<?= $modeClr[1] ?>;
                          font-size:10px;font-weight:600;padding:2px 7px;border-radius:10px;">
              <?= htmlspecialchars($o->ORDER_MODE ?? '—') ?>
            </span>
          </td>
          <td><?= db2OrderBadge((string)($o->ORDER_STATUS ?? '')) ?></td>
          <td><?= db2PayBadge((string)($o->PAYMENT_STATUS ?? '')) ?></td>
          <td style="text-align:right;font-weight:700;font-size:13px;">
            €<?= number_format((float)$o->FINAL_TOTAL_AMT, 2) ?>
          </td>
          <td style="font-size:11px;color:#94a3b8;white-space:nowrap;">
            <?= $o->ORDER_DATE ? db2Ago($o->ORDER_DATE) : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <?php endif; ?>

  <?php if (in_array('quotes', $tables)): ?>
  <!-- Pending Quotations -->
  <div class="db2-card">
    <div class="db2-card-hdr">
      <h3>📋 Pending Quotations</h3>
      <a href="quotation">View All →</a>
    </div>
    <div class="db2-tbl-wrap">
    <table class="db2-tbl">
      <thead>
        <tr>
          <th>#</th>
          <th>Customer</th>
          <th>Country</th>
          <th>Items</th>
          <th>Status</th>
          <th>When</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data['pending_quotes'] as $q): ?>
        <tr>
          <td style="font-weight:700;color:#7c3aed;font-size:11px;">
            #<?= (int)$q->ENQUIRY_QUOTE_ID ?>
          </td>
          <td>
            <div style="font-weight:600;font-size:12px;"><?= htmlspecialchars($q->USER_NAME ?? '—') ?></div>
            <?php if (!empty($q->COMPANY_NAME)): ?>
              <div style="font-size:10px;color:#94a3b8;"><?= htmlspecialchars($q->COMPANY_NAME) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:11px;color:#64748b;"><?= htmlspecialchars($q->DELIVERY_COUNTRY ?? '—') ?></td>
          <td style="text-align:center;font-weight:700;"><?= (int)($q->ITEM_COUNT ?? 0) ?></td>
          <td><?= db2QuoteBadge((string)($q->ENQUIRY_STATUS ?? '')) ?></td>
          <td style="font-size:11px;color:#94a3b8;white-space:nowrap;">
            <?= $q->ENQUIRY_DATE ? db2Ago($q->ENQUIRY_DATE) : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <?php endif; ?>

  <?php if (in_array('stock', $tables)): ?>
  <!-- Low Stock Alert -->
  <div class="db2-card">
    <div class="db2-card-hdr">
      <h3>⚠️ Low / Out of Stock</h3>
      <a href="product-inventory-management">Full Inventory →</a>
    </div>
    <div class="db2-tbl-wrap">
    <table class="db2-tbl">
      <thead>
        <tr>
          <th>Product</th>
          <th>Code</th>
          <th style="text-align:center;">Stock</th>
          <th style="text-align:center;">Threshold</th>
          <th>Health</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data['low_stock_items'] as $item):
          $rem   = (int)($item->TOTAL_REMAINING ?? 0);
          $thr   = max(1,(int)($item->PRODUCT_THRESHOLD ?? 1));
          $pct   = min(100, round($rem/$thr*100));
          $clr   = $rem === 0 ? '#dc2626' : ($pct < 50 ? '#f59e0b' : '#16a34a');
        ?>
        <tr>
          <td style="max-width:160px;">
            <div style="font-weight:600;font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                 title="<?= htmlspecialchars($item->PRODUCT_NAME??'') ?>">
              <?= htmlspecialchars($item->PRODUCT_NAME ?? '') ?>
            </div>
          </td>
          <td style="font-size:10px;color:#94a3b8;"><?= htmlspecialchars($item->PRODUCT_CODE ?? '') ?></td>
          <td style="text-align:center;font-weight:800;color:<?= $clr ?>;"><?= $rem ?></td>
          <td style="text-align:center;color:#94a3b8;"><?= $thr ?></td>
          <td style="min-width:80px;">
            <div class="db2-stock-bar">
              <div class="db2-stock-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>;"></div>
            </div>
            <div style="font-size:9px;color:<?= $clr ?>;font-weight:700;margin-top:2px;">
              <?= $rem === 0 ? 'OUT OF STOCK' : ($pct < 50 ? 'CRITICAL' : 'LOW') ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <?php endif; ?>

  <?php if (in_array('hr', $tables)): ?>
  <!-- Recent Job Applicants -->
  <div class="db2-card">
    <div class="db2-card-hdr">
      <h3>👥 Recent Applicants</h3>
      <a href="job-posting">All Positions →</a>
    </div>
    <div class="db2-tbl-wrap">
    <table class="db2-tbl">
      <thead>
        <tr>
          <th>Applicant</th>
          <th>Position</th>
          <th>Applied</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data['recent_applicants'] as $ap): ?>
        <tr>
          <td>
            <div style="font-weight:600;font-size:12px;"><?= htmlspecialchars($ap->CANDIDATE_NAME ?? '—') ?></div>
            <div style="font-size:10px;color:#94a3b8;"><?= htmlspecialchars($ap->CANDIDATE_EMAIL ?? '') ?></div>
          </td>
          <td style="font-size:11px;"><?= htmlspecialchars($ap->JOB_POSITION ?? '—') ?></td>
          <td style="font-size:11px;color:#94a3b8;white-space:nowrap;">
            <?= $ap->APPLIED_DATE ? date('d M Y', strtotime($ap->APPLIED_DATE)) : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php endif; /* tables */ ?>

<?php endif; /* hasSomething */ ?>
</div><!-- .db2-wrap -->

<!-- Chart.js — only load if chart visible -->
<?php if ($showChart && !empty($chart['labels'])): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function() {
  const labels  = <?= json_encode($chart['labels']) ?>;
  const revenue = <?= json_encode($chart['revArr']) ?>;
  const collected = <?= json_encode($chart['colArr']) ?>;
  const orders  = <?= json_encode($chart['ordArr']) ?>;

  Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
  Chart.defaults.color = '#64748b';

  const ctx = document.getElementById('db2RevenueChart')?.getContext('2d');
  if (!ctx) return;

  new Chart(ctx, {
    data: {
      labels,
      datasets: [
        {
          type: 'bar',
          label: 'Total Billed (€)',
          data: revenue,
          backgroundColor: 'rgba(37,99,235,0.15)',
          borderColor: '#2563eb',
          borderWidth: 1.5,
          borderRadius: 5,
          yAxisID: 'yRev', order: 3,
        },
        {
          type: 'bar',
          label: 'Collected (€)',
          data: collected,
          backgroundColor: 'rgba(22,163,74,0.2)',
          borderColor: '#16a34a',
          borderWidth: 1.5,
          borderRadius: 5,
          yAxisID: 'yRev', order: 2,
        },
        {
          type: 'line',
          label: 'Orders',
          data: orders,
          borderColor: '#f59e0b',
          backgroundColor: 'rgba(245,158,11,0.08)',
          borderWidth: 2.5,
          pointBackgroundColor: '#f59e0b',
          pointRadius: 4,
          fill: true,
          tension: 0.35,
          yAxisID: 'yOrd', order: 1,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          labels: { boxWidth: 10, font: { size: 11 }, padding: 16 }
        },
        tooltip: {
          backgroundColor: '#0f172a', padding: 10, cornerRadius: 8,
          callbacks: {
            label: ctx => ctx.dataset.yAxisID === 'yRev'
              ? ' ' + ctx.dataset.label + ': €' + ctx.parsed.y.toLocaleString('de-DE',{minimumFractionDigits:2})
              : ' Orders: ' + ctx.parsed.y
          }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        yRev: {
          position: 'left', grid: { color: 'rgba(0,0,0,0.04)' },
          ticks: { font: {size:11}, callback: v => '€'+(v>=1000?(v/1000).toFixed(1)+'K':v) }
        },
        yOrd: {
          position: 'right', grid: { display: false },
          ticks: { font: {size:11}, stepSize: 1 }
        }
      }
    }
  });
})();
</script>
<?php endif; ?>
<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
