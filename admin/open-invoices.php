<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'open-invoices';
$pageTitle   = 'Payment Report';

$controller = new AdminController();

$canView = sinelec_can('view');
$canEdit = sinelec_can('edit');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

/* ── Filters ── */
$fSearch   = trim($_GET['search']         ?? '');
$fMode     = trim($_GET['order_mode']     ?? '');
$fPS       = trim($_GET['payment_status'] ?? '');
$fOS       = trim($_GET['order_status']   ?? '');
$fFrom     = trim($_GET['date_from']      ?? '');
$fTo       = trim($_GET['date_to']        ?? '');
$fOverdue  = !empty($_GET['overdue_only']);

/* ── Data ── */
$orders    = $controller->getOpenInvoicesReport([
    'search'         => $fSearch,
    'order_mode'     => $fMode,
    'payment_status' => $fPS,
    'order_status'   => $fOS,
    'date_from'      => $fFrom,
    'date_to'        => $fTo,
    'overdue_only'   => $fOverdue,
]);
$modeStatRows = $controller->getPaymentModeStats();

/* ────────────────────────────────────────────
   Build mode-level stats map
   modeStats[mode][paid|pending|failed|refund|other]
──────────────────────────────────────────── */
$modeStats = [];
$gTotalAmt = $gPaidAmt = $gPendingAmt = $gFailedAmt = 0;
$gPendingCnt = $gFailedCnt = $gOverdueCnt = 0;

foreach ($modeStatRows as $s) {
    $mode = (string)($s->ORDER_MODE    ?? 'Unknown');
    $ps   = (string)($s->PAYMENT_STATUS ?? '');
    $cnt  = (int)(float)($s->CNT       ?? 0);
    $amt  = (float)($s->TOTAL_AMT      ?? 0);

    if (!isset($modeStats[$mode])) {
        $modeStats[$mode] = [
            'total_cnt'=>0,'total_amt'=>0,
            'paid_cnt'=>0,'paid_amt'=>0,
            'pending_cnt'=>0,'pending_amt'=>0,
            'failed_cnt'=>0,'failed_amt'=>0,
            'refund_cnt'=>0,'refund_amt'=>0,
            'other_cnt'=>0,'other_amt'=>0,
        ];
    }
    $modeStats[$mode]['total_cnt'] += $cnt;
    $modeStats[$mode]['total_amt'] += $amt;
    $gTotalAmt += $amt;

    if (in_array($ps, ['Payment Successful','Not Required'])) {
        $modeStats[$mode]['paid_cnt'] += $cnt;
        $modeStats[$mode]['paid_amt'] += $amt;
        $gPaidAmt += $amt;
    } elseif ($ps === 'Payment Pending') {
        $modeStats[$mode]['pending_cnt'] += $cnt;
        $modeStats[$mode]['pending_amt'] += $amt;
        $gPendingAmt += $amt;
        $gPendingCnt += $cnt;
    } elseif ($ps === 'Payment Failed') {
        $modeStats[$mode]['failed_cnt'] += $cnt;
        $modeStats[$mode]['failed_amt'] += $amt;
        $gFailedAmt += $amt;
        $gFailedCnt += $cnt;
    } elseif (in_array($ps, ['Refund Initiated','Refund Completed'])) {
        $modeStats[$mode]['refund_cnt'] += $cnt;
        $modeStats[$mode]['refund_amt'] += $amt;
    } else {
        $modeStats[$mode]['other_cnt'] += $cnt;
        $modeStats[$mode]['other_amt'] += $amt;
    }
}

/* Overdue: payment pending/failed AND age > 30 days (computed from orders array) */
foreach ($orders as $o) {
    $ps  = (string)($o->PAYMENT_STATUS ?? '');
    $age = (int)(float)($o->AGE_DAYS   ?? 0);
    if (in_array($ps, ['Payment Pending','Payment Failed']) && $age > 30) {
        $gOverdueCnt++;
    }
}

/* ── Badge helpers ── */
function oiPayBadge(string $ps): array {
    return match($ps) {
        'Payment Pending'    => ['#d97706','#fefce8','#fde68a'],
        'Payment Successful' => ['#16a34a','#f0fdf4','#86efac'],
        'Payment Failed'     => ['#dc2626','#fee2e2','#fca5a5'],
        'Refund Initiated'   => ['#7c3aed','#f3e8ff','#c4b5fd'],
        'Refund Completed'   => ['#2563eb','#eff6ff','#bfdbfe'],
        'Not Required'       => ['#64748b','#f8fafc','#e2e8f0'],
        default              => ['#64748b','#f8fafc','#e2e8f0'],
    };
}
function oiOrderBadge(string $st): array {
    return match($st) {
        'Order Pending'    => ['#d97706','#fefce8'],
        'Order Confirmed'  => ['#0284c7','#dbeafe'],
        'Order Packed'     => ['#7c3aed','#f3e8ff'],
        'Order Dispatch'   => ['#0891b2','#e0f2fe'],
        'Order In Transit' => ['#0891b2','#e0f2fe'],
        'Order Delivered'  => ['#16a34a','#f0fdf4'],
        'Order Cancelled'  => ['#dc2626','#fee2e2'],
        default            => ['#64748b','#f8fafc'],
    };
}
function oiModeBadge(string $mode): array {
    return match($mode) {
        'Invoice'         => ['#4f46e5','#ede9fe','📄'],
        'Bank Transfer'   => ['#0891b2','#e0f2fe','🏦'],
        'Payment Gateway' => ['#059669','#ecfdf5','💳'],
        default           => ['#64748b','#f8fafc','💰'],
    };
}
function oiAgeColor(int $days, string $ps): string {
    if (!in_array($ps, ['Payment Pending','Payment Failed'])) return '#94a3b8';
    if ($days > 60) return '#dc2626';
    if ($days > 30) return '#ea580c';
    if ($days > 14) return '#d97706';
    return '#64748b';
}

ob_start();
?>

<!-- ══════════════════ PAGE HEADER ══════════════════ -->
<div class="pg-header">
  <div>
    <h1 class="pg-title">Payment Report</h1>
    <p class="pg-sub">Open invoices, pending payments and collection status across all payment modes.</p>
  </div>
  <button class="btn btn--outline" style="height:36px;padding:0 14px;font-size:12px;" onclick="oiExportCsv()">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Export CSV
  </button>
</div>

<!-- ══════════════════ KPI TILES ══════════════════ -->
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin-bottom:16px;">
  <?php
  $kpis = [
    ['Total Billed',    '€'.number_format($gTotalAmt,2),   '#4f46e5','#ede9fe','💰'],
    ['Collected',       '€'.number_format($gPaidAmt,2),    '#16a34a','#f0fdf4','✅'],
    ['Pending Amount',  '€'.number_format($gPendingAmt,2), '#d97706','#fefce8','⏳'],
    ['Pending Orders',  number_format($gPendingCnt),       '#ea580c','#fff7ed','📋'],
    ['Failed',          number_format($gFailedCnt),         '#dc2626','#fee2e2','❌'],
    ['Overdue (>30d)',  number_format($gOverdueCnt),        '#7c3aed','#f3e8ff','🔔'],
  ];
  foreach ($kpis as [$lbl,$val,$clr,$bg,$icon]):
  ?>
  <div style="background:<?= $bg ?>;border-radius:8px;padding:9px 12px;border:1px solid <?= $clr ?>33;">
    <div style="font-size:10px;font-weight:600;color:<?= $clr ?>;display:flex;align-items:center;gap:3px;margin-bottom:4px;white-space:nowrap;">
      <span><?= $icon ?></span><?= $lbl ?>
    </div>
    <div style="font-size:15px;font-weight:800;color:<?= $clr ?>;line-height:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $val ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══════════════════ PAYMENT MODE SUMMARY CARDS ══════════════════ -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px;">
  <?php
  $modeCards = [
    ['Invoice',         '📄','#4f46e5','#ede9fe','#c7d2fe'],
    ['Bank Transfer',   '🏦','#0891b2','#e0f2fe','#a5f3fc'],
    ['Payment Gateway', '💳','#059669','#ecfdf5','#6ee7b7'],
  ];
  foreach ($modeCards as [$mKey,$mIcon,$mClr,$mBg,$mBorder]):
    $ms = $modeStats[$mKey] ?? ['total_cnt'=>0,'total_amt'=>0,'paid_cnt'=>0,'paid_amt'=>0,'pending_cnt'=>0,'pending_amt'=>0,'failed_cnt'=>0,'failed_amt'=>0,'refund_cnt'=>0,'refund_amt'=>0];
    $barTotal = $ms['total_amt'] > 0 ? $ms['total_amt'] : 1;
    $paidPct    = round($ms['paid_amt']    / $barTotal * 100);
    $pendingPct = round($ms['pending_amt'] / $barTotal * 100);
    $failedPct  = round($ms['failed_amt']  / $barTotal * 100);
    $otherPct   = max(0, 100 - $paidPct - $pendingPct - $failedPct);
  ?>
  <div style="background:#fff;border:1px solid <?= $mBorder ?>;border-radius:12px;padding:16px 18px;">
    <!-- Card header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
      <div style="display:flex;align-items:center;gap:8px;">
        <div style="width:34px;height:34px;border-radius:8px;background:<?= $mBg ?>;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;"><?= $mIcon ?></div>
        <div>
          <div style="font-size:13px;font-weight:800;color:#1e293b;"><?= $mKey ?></div>
          <div style="font-size:11px;color:#94a3b8;"><?= number_format($ms['total_cnt']) ?> order<?= $ms['total_cnt']!=1?'s':'' ?></div>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:14px;font-weight:800;color:<?= $mClr ?>;">€<?= number_format($ms['total_amt'],2) ?></div>
        <div style="font-size:10px;color:#94a3b8;">total billed</div>
      </div>
    </div>

    <!-- Progress bar -->
    <div style="display:flex;height:6px;border-radius:3px;overflow:hidden;background:#f1f5f9;margin-bottom:12px;">
      <?php if ($paidPct > 0):    ?><div style="flex:0 0 <?= $paidPct ?>%;background:#16a34a;" title="Paid <?= $paidPct ?>%"></div><?php endif; ?>
      <?php if ($pendingPct > 0): ?><div style="flex:0 0 <?= $pendingPct ?>%;background:#d97706;" title="Pending <?= $pendingPct ?>%"></div><?php endif; ?>
      <?php if ($failedPct > 0):  ?><div style="flex:0 0 <?= $failedPct ?>%;background:#dc2626;" title="Failed <?= $failedPct ?>%"></div><?php endif; ?>
      <?php if ($otherPct > 0):   ?><div style="flex:0 0 <?= $otherPct ?>%;background:#e2e8f0;"></div><?php endif; ?>
    </div>

    <!-- Stat rows -->
    <div style="display:flex;flex-direction:column;gap:6px;">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#475569;">
          <span style="width:8px;height:8px;border-radius:50%;background:#16a34a;display:inline-block;flex-shrink:0;"></span>
          Collected <span style="color:#94a3b8;font-size:11px;">(<?= $ms['paid_cnt'] ?>)</span>
        </div>
        <div style="font-size:13px;font-weight:700;color:#16a34a;">€<?= number_format($ms['paid_amt'],2) ?></div>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#475569;">
          <span style="width:8px;height:8px;border-radius:50%;background:#d97706;display:inline-block;flex-shrink:0;"></span>
          Pending <span style="color:#94a3b8;font-size:11px;">(<?= $ms['pending_cnt'] ?>)</span>
        </div>
        <div style="font-size:13px;font-weight:700;color:#d97706;">€<?= number_format($ms['pending_amt'],2) ?></div>
      </div>
      <?php if ($ms['failed_cnt'] > 0): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#475569;">
          <span style="width:8px;height:8px;border-radius:50%;background:#dc2626;display:inline-block;flex-shrink:0;"></span>
          Failed <span style="color:#94a3b8;font-size:11px;">(<?= $ms['failed_cnt'] ?>)</span>
        </div>
        <div style="font-size:13px;font-weight:700;color:#dc2626;">€<?= number_format($ms['failed_amt'],2) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($ms['refund_cnt'] > 0): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#475569;">
          <span style="width:8px;height:8px;border-radius:50%;background:#7c3aed;display:inline-block;flex-shrink:0;"></span>
          Refund <span style="color:#94a3b8;font-size:11px;">(<?= $ms['refund_cnt'] ?>)</span>
        </div>
        <div style="font-size:13px;font-weight:700;color:#7c3aed;">€<?= number_format($ms['refund_amt'],2) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══════════════════ FILTER BAR ══════════════════ -->
<form method="GET" id="oiFilterForm" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:flex-end;">
  <!-- Search -->
  <div style="position:relative;flex:1;min-width:200px;max-width:280px;">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af;" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="search" class="form-control" placeholder="Order #, customer, invoice #…"
           value="<?= htmlspecialchars($fSearch) ?>" style="padding-left:30px;height:36px;">
  </div>
  <!-- Mode -->
  <select name="order_mode" class="form-control" style="height:36px;width:auto;min-width:155px;">
    <option value="">All Modes</option>
    <?php foreach(['Invoice','Bank Transfer','Payment Gateway'] as $m): ?>
    <option value="<?= $m ?>" <?= $fMode===$m?'selected':'' ?>><?= $m ?></option>
    <?php endforeach; ?>
  </select>
  <!-- Payment Status -->
  <select name="payment_status" class="form-control" style="height:36px;width:auto;min-width:165px;">
    <option value="">All Payment Status</option>
    <?php foreach(['Payment Pending','Payment Successful','Payment Failed','Refund Initiated','Refund Completed','Not Required'] as $s): ?>
    <option value="<?= $s ?>" <?= $fPS===$s?'selected':'' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <!-- Order Status -->
  <select name="order_status" class="form-control" style="height:36px;width:auto;min-width:150px;">
    <option value="">All Order Status</option>
    <?php foreach(['Order Pending','Order Confirmed','Order Packed','Order Dispatch','Order In Transit','Order Delivered','Order Cancelled'] as $s): ?>
    <option value="<?= $s ?>" <?= $fOS===$s?'selected':'' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <!-- Date range -->
  <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($fFrom) ?>" style="height:36px;width:130px;" title="From date">
  <input type="date" name="date_to"   class="form-control" value="<?= htmlspecialchars($fTo) ?>"   style="height:36px;width:130px;" title="To date">
  <!-- Overdue toggle -->
  <label style="display:flex;align-items:center;gap:6px;height:36px;font-size:12px;font-weight:600;color:#ea580c;cursor:pointer;white-space:nowrap;background:#fff7ed;border:1.5px solid #fed7aa;border-radius:8px;padding:0 12px;">
    <input type="checkbox" name="overdue_only" value="1" <?= $fOverdue?'checked':'' ?> style="accent-color:#ea580c;">
    Overdue only (&gt;30d)
  </label>
  <button type="submit" class="btn btn--primary" style="height:36px;padding:0 16px;">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="16" y2="12"/><line x1="4" y1="18" x2="12" y2="18"/></svg>
    Apply
  </button>
  <?php if($fSearch||$fMode||$fPS||$fOS||$fFrom||$fTo||$fOverdue): ?>
  <a href="open-invoices" class="btn btn--ghost" style="height:36px;padding:0 14px;font-size:12px;">Clear</a>
  <?php endif; ?>
</form>

<!-- ══════════════════ TABLE CARD ══════════════════ -->
<div class="card" style="overflow:hidden;">

  <!-- ── Top Pagination Bar ── -->
  <div class="oi-pgbar">
    <div class="oi-pgbar__info">
      Showing <strong id="oiRangeStart">1</strong>–<strong id="oiRangeEnd">20</strong>
      of <strong id="oiCount"><?= count($orders) ?></strong> order<?= count($orders)!==1?'s':'' ?>
    </div>
    <div class="oi-pgbar__perpage">
      <span style="font-size:13px;font-weight:600;color:#374151;white-space:nowrap;">Per page</span>
      <div style="position:relative;display:inline-flex;align-items:center;">
        <select id="oiPerPage" class="oi-pgbar__sel">
          <option value="10">10</option>
          <option value="20" selected>20</option>
          <option value="30">30</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <svg style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#64748b;" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <button type="button" class="oi-pgbar__apply" onclick="oiApplyPerPage()">Apply</button>
    </div>
    <div id="oiPager" class="oi-pgbar__pager"></div>
  </div>

  <!-- ── Legend ── -->
  <div style="padding:8px 18px;background:#fafbfc;border-bottom:1px solid var(--border);display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
    <span style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;">Row colour key:</span>
    <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:#d97706;font-weight:600;"><span style="width:10px;height:10px;border-radius:2px;background:#fef9c3;border-left:3px solid #d97706;"></span>Payment Pending</span>
    <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:#dc2626;font-weight:600;"><span style="width:10px;height:10px;border-radius:2px;background:#fee2e2;border-left:3px solid #dc2626;"></span>Payment Failed</span>
    <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:#ea580c;font-weight:600;"><span style="width:10px;height:10px;border-radius:2px;background:#fff7ed;border-left:3px solid #ea580c;"></span>Overdue (&gt;30 days)</span>
  </div>

  <!-- ── Table ── -->
  <div style="overflow-x:auto;">
    <table class="dt" id="oiTable">
      <thead>
        <tr>
          <th style="width:36px;">#</th>
          <th style="width:140px;">Order #</th>
          <th style="min-width:180px;">Customer</th>
          <th style="width:130px;text-align:center;">Mode</th>
          <th style="width:135px;">Order Status</th>
          <th style="width:150px;">Payment Status</th>
          <th style="width:110px;text-align:right;">Amount</th>
          <th style="width:170px;">Reference</th>
          <th style="width:100px;text-align:center;">Date</th>
          <th style="width:70px;text-align:center;">Age</th>
          <th style="width:46px;text-align:center;">Inv.</th>
        </tr>
      </thead>
      <tbody id="oiTbody">
        <?php foreach ($orders as $i => $o):
          $oid      = (int)(float)($o->USER_ORDER_ID  ?? 0);
          $orderNo  = (string)($o->ORDER_NUMBER       ?? '');
          $mode     = (string)($o->ORDER_MODE         ?? '');
          $oStatus  = (string)($o->ORDER_STATUS       ?? '');
          $pStatus  = (string)($o->PAYMENT_STATUS     ?? '');
          $total    = (float)($o->FINAL_TOTAL_AMT     ?? 0);
          $age      = (int)(float)($o->AGE_DAYS       ?? 0);
          $cName    = (string)($o->CUST_NAME          ?? '');
          $cEmail   = (string)($o->CUST_EMAIL         ?? '');
          $cCompany = (string)($o->CUST_COMPANY       ?? '');
          $cPhone   = (string)($o->CUST_PHONE         ?? '');
          $invNo    = (string)($o->INVOICE_NO         ?? '');
          $bankRef  = (string)($o->BANK_REFERENCE_NO  ?? '');
          $txnId    = (string)($o->TRANSACTION_ID     ?? '');
          $poId     = (string)($o->CUSTOMER_PO_ID     ?? '');
          $itemCnt  = (int)(float)($o->ITEM_COUNT     ?? 0);
          $qid      = (int)(float)($o->ENQUIRY_QUOTE_ID ?? 0);
          $dateRaw  = (string)($o->ORDER_DATE         ?? '');
          $dateFmt  = $dateRaw ? date('d M Y', strtotime($dateRaw)) : '—';

          /* Row highlight class */
          $isOverdue = in_array($pStatus,['Payment Pending','Payment Failed']) && $age > 30;
          if ($isOverdue) {
              $rowBg = 'background:#fff7ed;'; $rowBorder = 'border-left:3px solid #ea580c;';
          } elseif ($pStatus === 'Payment Pending') {
              $rowBg = 'background:#fefce8;'; $rowBorder = 'border-left:3px solid #d97706;';
          } elseif ($pStatus === 'Payment Failed') {
              $rowBg = 'background:#fff1f2;'; $rowBorder = 'border-left:3px solid #dc2626;';
          } else {
              $rowBg = ''; $rowBorder = 'border-left:3px solid transparent;';
          }

          [$pClr,$pBg]  = oiPayBadge($pStatus);
          [$oClr,$oBg]  = oiOrderBadge($oStatus);
          [$mClr,$mBg,$mIco] = oiModeBadge($mode);
          $ageClr = oiAgeColor($age, $pStatus);

          /* Reference string to show */
          $refMain = $invNo ?: ($bankRef ?: ($txnId ?: '—'));
          $refLabel = $invNo ? 'Invoice' : ($bankRef ? 'Bank Ref' : ($txnId ? 'Txn ID' : ''));
        ?>
        <tr class="oi-row" data-seq="<?= $i+1 ?>" style="<?= $rowBg . $rowBorder ?>">

          <td class="td-sm oi-sno" style="font-size:12px;color:var(--text-muted);font-weight:600;"><?= $i+1 ?></td>

          <!-- Order # -->
          <td>
            <div style="font-weight:700;color:#4f46e5;font-size:13px;"><?= htmlspecialchars($orderNo) ?></div>
            <div style="font-size:10px;color:var(--text-muted);font-family:monospace;">#<?= $oid ?></div>
            <?php if ($qid > 0): ?>
            <div style="margin-top:2px;"><span style="font-size:9px;padding:1px 6px;border-radius:10px;background:#f3e8ff;color:#7c3aed;font-weight:700;">QT-<?= str_pad($qid,6,'0',STR_PAD_LEFT) ?></span></div>
            <?php endif; ?>
            <?php if ($itemCnt > 0): ?>
            <div style="font-size:10px;color:var(--text-muted);margin-top:1px;"><?= $itemCnt ?> item<?= $itemCnt!=1?'s':'' ?></div>
            <?php endif; ?>
          </td>

          <!-- Customer -->
          <td>
            <div style="font-weight:600;font-size:13px;color:var(--text);"><?= htmlspecialchars($cName ?: '—') ?></div>
            <?php if ($cCompany): ?><div style="font-size:11px;color:var(--text-muted);font-style:italic;"><?= htmlspecialchars($cCompany) ?></div><?php endif; ?>
            <?php if ($cEmail):   ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($cEmail) ?></div><?php endif; ?>
            <?php if ($poId):     ?><div style="font-size:10px;color:#64748b;margin-top:2px;">PO: <span style="font-family:monospace;font-weight:600;"><?= htmlspecialchars($poId) ?></span></div><?php endif; ?>
          </td>

          <!-- Mode badge -->
          <td style="text-align:center;">
            <span style="font-size:11px;font-weight:700;padding:4px 11px;border-radius:20px;background:<?= $mBg ?>;color:<?= $mClr ?>;white-space:nowrap;display:inline-flex;align-items:center;gap:4px;">
              <span><?= $mIco ?></span><?= htmlspecialchars($mode) ?>
            </span>
          </td>

          <!-- Order Status -->
          <td>
            <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:<?= $oBg ?>;color:<?= $oClr ?>;white-space:nowrap;"><?= htmlspecialchars($oStatus) ?></span>
          </td>

          <!-- Payment Status -->
          <td>
            <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:<?= $pBg ?>;color:<?= $pClr ?>;border:1px solid <?= $pClr ?>44;white-space:nowrap;">
              <?= htmlspecialchars($pStatus) ?>
            </span>
            <?php if ($isOverdue): ?>
            <div style="margin-top:3px;font-size:10px;font-weight:700;color:#ea580c;">⚠ Overdue</div>
            <?php endif; ?>
          </td>

          <!-- Amount -->
          <td style="text-align:right;">
            <div style="font-size:14px;font-weight:800;color:<?= in_array($pStatus,['Payment Pending','Payment Failed'])?'#dc2626':'#059669' ?>;">
              €<?= number_format($total, 2) ?>
            </div>
            <?php if (in_array($pStatus,['Payment Pending','Payment Failed'])): ?>
            <div style="font-size:10px;font-weight:700;color:#dc2626;margin-top:1px;">Outstanding</div>
            <?php elseif ($pStatus === 'Payment Successful'): ?>
            <div style="font-size:10px;color:#16a34a;margin-top:1px;">Paid ✓</div>
            <?php endif; ?>
          </td>

          <!-- Reference -->
          <td>
            <?php if ($refMain !== '—'): ?>
            <div style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px;"><?= $refLabel ?></div>
            <div style="font-size:11px;font-family:monospace;font-weight:700;color:#1e293b;"><?= htmlspecialchars($refMain) ?></div>
            <?php else: ?>
            <div style="font-size:12px;color:#cbd5e1;">—</div>
            <?php endif; ?>
          </td>

          <!-- Date -->
          <td style="text-align:center;font-size:12px;color:var(--text-muted);"><?= $dateFmt ?></td>

          <!-- Age -->
          <td style="text-align:center;">
            <div style="font-size:13px;font-weight:800;color:<?= $ageClr ?>;"><?= $age ?>d</div>
          </td>

          <!-- Invoice PDF -->
          <td style="text-align:center;">
            <a href="order-invoice?id=<?= $oid ?>" target="_blank" title="View Invoice PDF"
               style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:#f1f5f9;color:#64748b;text-decoration:none;"
               onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="11" y2="17"/></svg>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Empty state -->
  <div id="oiEmpty" style="display:none;padding:50px 20px;text-align:center;">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#c0ccd8" stroke-width="1.2" style="margin:0 auto 12px;display:block;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/></svg>
    <div style="font-size:14px;font-weight:600;color:var(--text-muted);">No orders match your filters.</div>
    <div style="font-size:12px;color:#94a3b8;margin-top:4px;">Try adjusting or clearing the filters above.</div>
  </div>

</div><!-- /card -->

<!-- ═══════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════ -->
<style>
/* ══ Pagination bar ══ */
.oi-pgbar {
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;flex-wrap:wrap;padding:13px 18px;
  border-bottom:1px solid var(--border);background:#fff;
}
.oi-pgbar__info { font-size:13px;color:#64748b;white-space:nowrap; }
.oi-pgbar__info strong { color:#1e293b;font-weight:700; }
.oi-pgbar__perpage { display:flex;align-items:center;gap:10px;flex-shrink:0; }
.oi-pgbar__sel {
  -webkit-appearance:none;appearance:none;
  height:36px;padding:0 32px 0 14px;
  border:1.5px solid #e2e8f0;border-radius:20px;
  font-size:13px;font-weight:600;color:#1e293b;
  background:#fff;cursor:pointer;outline:none;transition:border-color .15s;
}
.oi-pgbar__sel:hover,.oi-pgbar__sel:focus { border-color:#6366f1; }
.oi-pgbar__apply {
  height:36px;padding:0 20px;background:#1e293b;color:#fff;
  border:none;border-radius:20px;font-size:13px;font-weight:600;
  cursor:pointer;white-space:nowrap;transition:background .15s;
}
.oi-pgbar__apply:hover { background:#0f172a; }
.oi-pgbar__pager { display:flex;align-items:center;gap:5px;flex-wrap:wrap; }
.oi-pg-nav {
  height:36px;padding:0 16px;border:1.5px solid #e2e8f0;border-radius:20px;
  background:#fff;font-size:13px;font-weight:600;color:#374151;
  cursor:pointer;white-space:nowrap;transition:border-color .15s,color .15s;
}
.oi-pg-nav:hover:not(:disabled) { border-color:#6366f1;color:#6366f1; }
.oi-pg-nav:disabled { color:#cbd5e1;border-color:#f1f5f9;cursor:default; }
.oi-pg-num {
  width:36px;height:36px;border:1.5px solid #e2e8f0;border-radius:50%;
  background:#fff;font-size:13px;font-weight:600;color:#374151;
  cursor:pointer;display:inline-flex;align-items:center;justify-content:center;
  transition:border-color .15s,color .15s;flex-shrink:0;
}
.oi-pg-num:hover { border-color:#6366f1;color:#6366f1; }
.oi-pg-dots { font-size:13px;color:#94a3b8;padding:0 2px;display:inline-flex;align-items:center; }
@media(max-width:640px){ .oi-pgbar { flex-direction:column;align-items:flex-start;gap:10px; } }
</style>

<!-- ═══════════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════════ -->
<script>
/* ══════════════════════════════════════════
   PAGINATION
══════════════════════════════════════════ */
var _oiPage    = 1;
var _oiPerPage = 20;
var _oiRows    = [];

function oiInit() {
  _oiRows = Array.from(document.querySelectorAll('#oiTbody .oi-row'));
  oiRender();
}

function oiApplyPerPage() {
  _oiPerPage = parseInt(document.getElementById('oiPerPage').value, 10) || 20;
  _oiPage = 1;
  oiRender();
}

function oiRender() {
  var pp = _oiPerPage, total = _oiRows.length;
  var pages = Math.max(1, Math.ceil(total / pp));
  if (_oiPage > pages) _oiPage = pages;
  if (_oiPage < 1)     _oiPage = 1;
  var start = (_oiPage - 1) * pp;
  var end   = Math.min(start + pp, total);

  _oiRows.forEach(function(r, i) {
    var vis = (i >= start && i < end);
    r.style.display = vis ? '' : 'none';
    if (vis) r.querySelector('.oi-sno').textContent = i + 1;
  });

  document.getElementById('oiCount').textContent      = total;
  document.getElementById('oiRangeStart').textContent = total > 0 ? start + 1 : 0;
  document.getElementById('oiRangeEnd').textContent   = end;
  document.getElementById('oiEmpty').style.display    = total === 0 ? 'block' : 'none';
  _oiBuildPager(pages);
}

function _oiBuildPager(pages) {
  var pager = document.getElementById('oiPager');
  pager.innerHTML = '';
  pager.appendChild(_oiNavBtn('Prev', _oiPage - 1, _oiPage <= 1));
  if (pages > 1) {
    _oiPageNums(_oiPage, pages).forEach(function(n) {
      if (n === -1) {
        var d = document.createElement('span');
        d.className = 'oi-pg-dots'; d.textContent = '...';
        pager.appendChild(d);
      } else { pager.appendChild(_oiNumBtn(n)); }
    });
  }
  pager.appendChild(_oiNavBtn('Next', _oiPage + 1, _oiPage >= pages));
}

function _oiPageNums(cur, total) {
  if (total <= 1) return [];
  var set = new Set();
  if (cur !== 1)     set.add(1);
  if (cur !== total) set.add(total);
  var before = Math.min(2, cur - 1), after = Math.min(2, total - cur);
  if (before + after < 4) {
    if (cur <= 3)              after  = Math.min(4 - before, total - cur);
    else if (cur >= total - 2) before = Math.min(4 - after,  cur - 1);
  }
  for (var p = cur - before; p <= cur + after; p++)
    if (p >= 1 && p <= total && p !== cur) set.add(p);
  var arr = Array.from(set).sort(function(a,b){return a-b;}), result = [];
  for (var i = 0; i < arr.length; i++) {
    if (i > 0 && arr[i] - arr[i-1] > 1) result.push(-1);
    result.push(arr[i]);
  }
  return result;
}

function _oiNavBtn(label, pg, disabled) {
  var b = document.createElement('button');
  b.textContent = label;
  b.className   = 'oi-pg-nav';
  b.disabled    = disabled;
  if (!disabled) b.onclick = function() { _oiPage = pg; oiRender(); };
  return b;
}
function _oiNumBtn(pg) {
  var b = document.createElement('button');
  b.textContent = String(pg); b.className = 'oi-pg-num';
  b.onclick = function() { _oiPage = pg; oiRender(); };
  return b;
}

document.addEventListener('DOMContentLoaded', oiInit);

/* ══════════════════════════════════════════
   CSV EXPORT
══════════════════════════════════════════ */
function oiExportCsv() {
  var headers = ['#','Order #','Customer','Company','Email','Mode','Order Status','Payment Status','Amount (€)','Reference','Date','Age (days)'];
  var rows = [headers];
  document.querySelectorAll('#oiTbody .oi-row').forEach(function(r, i) {
    var cells = r.querySelectorAll('td');
    rows.push([
      i + 1,
      cells[1]?.querySelector('div:first-child')?.textContent?.trim()     || '',
      cells[2]?.querySelector('div:first-child')?.textContent?.trim()     || '',
      cells[2]?.querySelectorAll('div')[1]?.textContent?.trim()           || '',
      cells[2]?.querySelectorAll('div')[2]?.textContent?.trim()           || '',
      cells[3]?.querySelector('span')?.textContent?.trim()                || '',
      cells[4]?.querySelector('span')?.textContent?.trim()                || '',
      cells[5]?.querySelector('span')?.textContent?.trim()                || '',
      cells[6]?.querySelector('div:first-child')?.textContent?.replace(/[^0-9.,]/g,'').trim() || '',
      cells[7]?.querySelector('div:last-child')?.textContent?.trim()      || '',
      cells[8]?.textContent?.trim()                                        || '',
      cells[9]?.querySelector('div')?.textContent?.replace('d','').trim() || '',
    ]);
  });
  var csv = rows.map(function(r) {
    return r.map(function(c) { return '"' + String(c).replace(/"/g,'""') + '"'; }).join(',');
  }).join('\n');
  var a = document.createElement('a');
  a.href = 'data:text/csv;charset=utf-8,﻿' + encodeURIComponent(csv);
  a.download = 'payment-report-' + new Date().toISOString().slice(0,10) + '.csv';
  document.body.appendChild(a); a.click(); a.remove();
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
