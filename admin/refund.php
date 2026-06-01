<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'refund';
$pageTitle   = 'Refund Management';

$controller = new AdminController();

$canView = sinelec_can('view');
$canEdit = sinelec_can('edit');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

/* ── Filters ── */
$fSearch  = trim($_GET['search']         ?? '');
$fRS      = trim($_GET['return_status']  ?? '');
$fPS      = trim($_GET['payment_status'] ?? '');
$fFrom    = trim($_GET['date_from']      ?? '');
$fTo      = trim($_GET['date_to']        ?? '');
$fPending = !empty($_GET['pending_only']);

/* ── Data ── */
$refunds = $controller->getRefundReport([
    'search'         => $fSearch,
    'return_status'  => $fRS,
    'payment_status' => $fPS,
    'date_from'      => $fFrom,
    'date_to'        => $fTo,
    'pending_only'   => $fPending,
]);
$stats = $controller->getRefundStats();

/* ── KPI values ── */
$sTot       = (int)(float)($stats->TOTAL              ?? 0);
$sPending   = (int)(float)($stats->PENDING_APPROVAL   ?? 0);
$sProcess   = (int)(float)($stats->IN_PROCESS         ?? 0);
$sInitiated = (int)(float)($stats->REFUND_INITIATED   ?? 0);
$sCompleted = (int)(float)($stats->COMPLETED          ?? 0);
$sRejected  = (int)(float)($stats->REJECTED           ?? 0);
$sTotalVal  = (float)($stats->TOTAL_REQUEST_VALUE     ?? 0);
$sCompAmt   = (float)($stats->COMPLETED_AMT           ?? 0);
$sInitAmt   = (float)($stats->INITIATED_AMT           ?? 0);
$sHandling  = (float)($stats->TOTAL_HANDLING_FEES     ?? 0);

/* ── Pipeline stage counts from live row set (respects active filters) ── */
$pipelineCounts = [
    'Return Requested'         => 0,
    'Return Request Approved'  => 0,
    'Pickup Scheduled'         => 0,
    'Pickup Completed'         => 0,
    'QC Approved'              => 0,
    'Return Completed'         => 0,
    'Return Request Cancelled' => 0,
];
foreach ($refunds as $r) {
    $rs = (string)($r->RETURN_STATUS ?? '');
    if (isset($pipelineCounts[$rs])) $pipelineCounts[$rs]++;
}

/* ── Badge helpers ── */
function rfReturnBadge(string $s): array {
    return match($s) {
        'Return Requested'         => ['#d97706','#fefce8','#fde68a'],
        'Return Request Approved'  => ['#0284c7','#dbeafe','#bfdbfe'],
        'Pickup Scheduled'         => ['#7c3aed','#f3e8ff','#c4b5fd'],
        'Pickup Completed'         => ['#0891b2','#e0f2fe','#a5f3fc'],
        'QC Approved'              => ['#059669','#ecfdf5','#6ee7b7'],
        'Return Completed'         => ['#16a34a','#f0fdf4','#86efac'],
        'Return Request Cancelled' => ['#dc2626','#fee2e2','#fca5a5'],
        default                    => ['#64748b','#f8fafc','#e2e8f0'],
    };
}
function rfPayBadge(string $s): array {
    return match($s) {
        'Refund Initiated'   => ['#7c3aed','#f3e8ff','#c4b5fd'],
        'Refund Completed'   => ['#16a34a','#f0fdf4','#86efac'],
        'Payment Pending'    => ['#d97706','#fefce8','#fde68a'],
        'Not Required'       => ['#64748b','#f8fafc','#e2e8f0'],
        default              => ['#64748b','#f8fafc','#e2e8f0'],
    };
}
function rfRowStyle(string $rs, string $ps): string {
    if ($rs === 'Return Requested')         return 'background:#fefce8;border-left:3px solid #d97706;';
    if ($rs === 'Return Request Cancelled') return 'background:#fff1f2;border-left:3px solid #dc2626;';
    if ($ps === 'Refund Initiated')         return 'background:#f5f3ff;border-left:3px solid #7c3aed;';
    if ($rs === 'Return Completed')         return 'background:#f0fdf4;border-left:3px solid #16a34a;';
    return 'border-left:3px solid transparent;';
}
function rfAgeStyle(int $days, string $rs): string {
    if (in_array($rs,['Return Completed','Return Request Cancelled'])) return 'color:#94a3b8;';
    if ($days > 14) return 'color:#dc2626;font-weight:800;';
    if ($days > 7)  return 'color:#ea580c;font-weight:700;';
    return 'color:#64748b;';
}

ob_start();
?>

<!-- ══════════════════ PAGE HEADER ══════════════════ -->
<div class="pg-header">
  <div>
    <h1 class="pg-title">Refund Management</h1>
    <p class="pg-sub">Track return requests, refund pipeline status and completion across all orders.</p>
  </div>
  <button class="btn btn--outline" style="height:36px;padding:0 14px;font-size:12px;" onclick="rfExportCsv()">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Export CSV
  </button>
</div>

<!-- ══════════════════ KPI TILES ══════════════════ -->
<div style="display:grid;grid-template-columns:repeat(8,1fr);gap:8px;margin-bottom:14px;">
  <?php
  $kpis = [
    ['Total Requests',   $sTot,                         '#4f46e5','#ede9fe','🔄'],
    ['Pending Approval', $sPending,                     '#d97706','#fefce8','⏳'],
    ['In Process',       $sProcess,                     '#0284c7','#dbeafe','🔃'],
    ['Refund Initiated', $sInitiated,                   '#7c3aed','#f3e8ff','💸'],
    ['Completed',        $sCompleted,                   '#16a34a','#f0fdf4','✅'],
    ['Rejected',         $sRejected,                    '#dc2626','#fee2e2','❌'],
    ['Total Value',      '€'.number_format($sTotalVal,2),'#0891b2','#e0f2fe','💰'],
    ['Refunded',         '€'.number_format($sCompAmt,2), '#059669','#ecfdf5','✔'],
  ];
  foreach ($kpis as [$lbl,$val,$clr,$bg,$icon]):
  ?>
  <div style="background:<?= $bg ?>;border-radius:8px;padding:9px 11px;border:1px solid <?= $clr ?>33;">
    <div style="font-size:10px;font-weight:600;color:<?= $clr ?>;display:flex;align-items:center;gap:3px;margin-bottom:4px;white-space:nowrap;overflow:hidden;">
      <span><?= $icon ?></span><?= $lbl ?>
    </div>
    <div style="font-size:15px;font-weight:800;color:<?= $clr ?>;line-height:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $val ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══════════════════ REFUND PIPELINE ══════════════════ -->
<div class="card" style="padding:14px 20px;margin-bottom:16px;">
  <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">Refund Lifecycle Pipeline</div>
  <div style="display:flex;align-items:stretch;gap:0;overflow-x:auto;">
    <?php
    $stages = [
      ['Return Requested',        '⏳','#d97706','#fefce8',"Return\nRequested"],
      ['Return Request Approved',  '✓', '#0284c7','#dbeafe',"Request\nApproved"],
      ['Pickup Scheduled',         '🚚','#7c3aed','#f3e8ff',"Pickup\nScheduled"],
      ['Pickup Completed',         '📦','#0891b2','#e0f2fe',"Pickup\nCompleted"],
      ['QC Approved',              '🔍','#059669','#ecfdf5',"QC\nApproved"],
      ['Return Completed',         '✅','#16a34a','#f0fdf4',"Return\nCompleted"],
    ];
    $lastIdx = count($stages) - 1;
    foreach ($stages as $idx => [$key,$ico,$clr,$bg,$lbl]):
      $cnt = $pipelineCounts[$key] ?? 0;
    ?>
    <div style="flex:1;min-width:90px;display:flex;align-items:center;">
      <!-- Stage box -->
      <a href="refund?return_status=<?= urlencode($key) ?>"
         style="flex:1;display:flex;flex-direction:column;align-items:center;padding:10px 6px;
                background:<?= $cnt>0?$bg:'#f8fafc' ?>;
                border:1.5px solid <?= $cnt>0?$clr.'66':'#e2e8f0' ?>;
                border-radius:8px;text-decoration:none;transition:all .15s;cursor:pointer;">
        <div style="font-size:16px;line-height:1;margin-bottom:4px;"><?= $ico ?></div>
        <div style="font-size:18px;font-weight:800;color:<?= $cnt>0?$clr:'#cbd5e1' ?>;line-height:1;"><?= $cnt ?></div>
        <div style="font-size:9px;font-weight:600;color:<?= $cnt>0?$clr:'#94a3b8' ?>;text-align:center;margin-top:3px;line-height:1.3;white-space:pre;"><?= $lbl ?></div>
      </a>
      <!-- Arrow -->
      <?php if ($idx < $lastIdx): ?>
      <div style="flex-shrink:0;padding:0 4px;color:#cbd5e1;font-size:16px;font-weight:300;">›</div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <!-- Rejected (separate) -->
    <div style="display:flex;align-items:center;margin-left:10px;">
      <div style="width:1px;background:#e2e8f0;height:40px;margin-right:10px;"></div>
      <?php $rejCnt = $pipelineCounts['Return Request Cancelled'] ?? 0; ?>
      <a href="refund?return_status=Return+Request+Cancelled"
         style="display:flex;flex-direction:column;align-items:center;padding:10px 14px;
                background:<?= $rejCnt>0?'#fee2e2':'#f8fafc' ?>;
                border:1.5px solid <?= $rejCnt>0?'#fca5a5':'#e2e8f0' ?>;
                border-radius:8px;text-decoration:none;min-width:80px;">
        <div style="font-size:16px;line-height:1;margin-bottom:4px;">❌</div>
        <div style="font-size:18px;font-weight:800;color:<?= $rejCnt>0?'#dc2626':'#cbd5e1' ?>;line-height:1;"><?= $rejCnt ?></div>
        <div style="font-size:9px;font-weight:600;color:<?= $rejCnt>0?'#dc2626':'#94a3b8' ?>;text-align:center;margin-top:3px;line-height:1.3;white-space:pre;">Request&#10;Cancelled</div>
      </a>
    </div>
  </div>

  <?php if ($sHandling > 0): ?>
  <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border);display:flex;gap:20px;flex-wrap:wrap;">
    <div style="font-size:12px;color:#64748b;">
      Total handling fees deducted: <strong style="color:#dc2626;">€<?= number_format($sHandling,2) ?></strong>
    </div>
    <div style="font-size:12px;color:#64748b;">
      Refund Initiated (in-flight): <strong style="color:#7c3aed;">€<?= number_format($sInitAmt,2) ?></strong>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ══════════════════ FILTER BAR ══════════════════ -->
<form method="GET" id="rfFilterForm" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;align-items:flex-end;">
  <!-- Search -->
  <div style="position:relative;flex:1;min-width:200px;max-width:280px;">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af;" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="search" class="form-control"
           placeholder="Return #, original order #, customer…"
           value="<?= htmlspecialchars($fSearch) ?>" style="padding-left:30px;height:36px;">
  </div>
  <!-- Return status -->
  <select name="return_status" class="form-control" style="height:36px;width:auto;min-width:190px;">
    <option value="">All Return Statuses</option>
    <?php foreach([
      'Return Requested','Return Request Approved','Return Request Cancelled',
      'Pickup Scheduled','Pickup Completed','QC Approved','Return Completed',
    ] as $s): ?>
    <option value="<?= $s ?>" <?= $fRS===$s?'selected':'' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <!-- Payment status -->
  <select name="payment_status" class="form-control" style="height:36px;width:auto;min-width:160px;">
    <option value="">All Payment Status</option>
    <?php foreach(['Payment Pending','Refund Initiated','Refund Completed','Not Required'] as $s): ?>
    <option value="<?= $s ?>" <?= $fPS===$s?'selected':'' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <!-- Date range -->
  <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($fFrom) ?>" style="height:36px;width:130px;" title="Return request from">
  <input type="date" name="date_to"   class="form-control" value="<?= htmlspecialchars($fTo) ?>"   style="height:36px;width:130px;" title="Return request to">
  <!-- Pending toggle -->
  <label style="display:flex;align-items:center;gap:6px;height:36px;font-size:12px;font-weight:600;color:#d97706;cursor:pointer;white-space:nowrap;background:#fefce8;border:1.5px solid #fde68a;border-radius:8px;padding:0 12px;">
    <input type="checkbox" name="pending_only" value="1" <?= $fPending?'checked':'' ?> style="accent-color:#d97706;">
    Pending only
  </label>
  <button type="submit" class="btn btn--primary" style="height:36px;padding:0 16px;">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="16" y2="12"/><line x1="4" y1="18" x2="12" y2="18"/></svg>
    Apply
  </button>
  <?php if ($fSearch||$fRS||$fPS||$fFrom||$fTo||$fPending): ?>
  <a href="refund" class="btn btn--ghost" style="height:36px;padding:0 14px;font-size:12px;">Clear</a>
  <?php endif; ?>
</form>

<!-- ══════════════════ TABLE CARD ══════════════════ -->
<div class="card" style="overflow:hidden;">

  <!-- Top Pagination Bar -->
  <div class="rf-pgbar">
    <div class="rf-pgbar__info">
      Showing <strong id="rfRangeStart">1</strong>–<strong id="rfRangeEnd">20</strong>
      of <strong id="rfCount"><?= count($refunds) ?></strong> request<?= count($refunds)!==1?'s':'' ?>
    </div>
    <div class="rf-pgbar__perpage">
      <span style="font-size:13px;font-weight:600;color:#374151;white-space:nowrap;">Per page</span>
      <div style="position:relative;display:inline-flex;align-items:center;">
        <select id="rfPerPage" class="rf-pgbar__sel">
          <option value="10">10</option>
          <option value="20" selected>20</option>
          <option value="30">30</option>
          <option value="50">50</option>
        </select>
        <svg style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#64748b;" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <button type="button" class="rf-pgbar__apply" onclick="rfApplyPerPage()">Apply</button>
    </div>
    <div id="rfPager" class="rf-pgbar__pager"></div>
  </div>

  <!-- Row colour legend -->
  <div style="padding:8px 18px;background:#fafbfc;border-bottom:1px solid var(--border);display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
    <span style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;">Key:</span>
    <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:#d97706;font-weight:600;"><span style="width:10px;height:10px;background:#fef9c3;border-left:3px solid #d97706;border-radius:1px;display:inline-block;"></span>Pending Approval</span>
    <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:#7c3aed;font-weight:600;"><span style="width:10px;height:10px;background:#f5f3ff;border-left:3px solid #7c3aed;border-radius:1px;display:inline-block;"></span>Refund Initiated</span>
    <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:#16a34a;font-weight:600;"><span style="width:10px;height:10px;background:#f0fdf4;border-left:3px solid #16a34a;border-radius:1px;display:inline-block;"></span>Return Completed</span>
    <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:#dc2626;font-weight:600;"><span style="width:10px;height:10px;background:#fff1f2;border-left:3px solid #dc2626;border-radius:1px;display:inline-block;"></span>Cancelled / Rejected</span>
  </div>

  <!-- Table -->
  <div style="overflow-x:auto;">
    <table class="dt" id="rfTable">
      <thead>
        <tr>
          <th style="width:36px;">#</th>
          <th style="width:140px;">Return Ref</th>
          <th style="width:130px;">Original Order</th>
          <th style="min-width:170px;">Customer</th>
          <th style="min-width:180px;">Return Reason</th>
          <th style="width:170px;">Return Status</th>
          <th style="width:140px;">Payment Status</th>
          <th style="width:100px;text-align:right;">Refund Amt</th>
          <th style="width:90px;text-align:right;">Handling</th>
          <th style="width:100px;text-align:right;">Net Refund</th>
          <th style="width:95px;text-align:center;">Requested</th>
          <th style="width:60px;text-align:center;">Age</th>
          <th style="width:80px;text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody id="rfTbody">
        <?php foreach ($refunds as $i => $r):
          $rid       = (int)(float)($r->USER_ORDER_ID    ?? 0);
          $rNo       = (string)($r->ORDER_NUMBER         ?? '');
          $rs        = (string)($r->RETURN_STATUS        ?? '');
          $ps        = (string)($r->PAYMENT_STATUS       ?? '');
          $total     = (float)($r->FINAL_TOTAL_AMT       ?? 0);
          $handling  = (float)($r->RETURN_HANDLING_FEE   ?? 0);
          $netRefund = $total - $handling;
          $reason    = (string)($r->USER_RETURN_REASON   ?? '');
          $rejectR   = (string)($r->ADMIN_RETURN_REJECT_REASON ?? '');
          $age       = (int)(float)($r->AGE_DAYS         ?? 0);
          $cName     = (string)($r->CUST_NAME            ?? '');
          $cEmail    = (string)($r->CUST_EMAIL           ?? '');
          $cCompany  = (string)($r->CUST_COMPANY         ?? '');
          $origNo    = (string)($r->ORIG_ORDER_NUMBER    ?? '');
          $origAmt   = (float)($r->ORIG_ORDER_AMT        ?? 0);
          $origDate  = (string)($r->ORIG_ORDER_DATE      ?? '');
          $dateFmt   = (string)($r->ORDER_DATE           ?? '');
          $dateFmt   = $dateFmt ? date('d M Y', strtotime($dateFmt)) : '—';
          $itemCnt   = (int)(float)($r->ITEM_COUNT       ?? 0);

          [$rsClr,$rsBg] = rfReturnBadge($rs);
          [$psClr,$psBg] = rfPayBadge($ps);
          $rowStyle      = rfRowStyle($rs, $ps);
          $ageStyle      = rfAgeStyle($age, $rs);

          $reasonShort   = mb_strlen($reason) > 55 ? mb_substr($reason, 0, 55) . '…' : $reason;
        ?>
        <tr class="rf-row" data-seq="<?= $i+1 ?>" style="<?= $rowStyle ?>">

          <td class="td-sm rf-sno" style="font-size:12px;color:var(--text-muted);font-weight:600;"><?= $i+1 ?></td>

          <!-- Return Ref -->
          <td>
            <div style="font-weight:700;color:#4f46e5;font-size:13px;"><?= htmlspecialchars($rNo) ?></div>
            <div style="font-size:10px;color:var(--text-muted);font-family:monospace;">#<?= $rid ?></div>
            <?php if ($itemCnt > 0): ?>
            <div style="font-size:10px;color:var(--text-muted);margin-top:1px;"><?= $itemCnt ?> item<?= $itemCnt!=1?'s':'' ?></div>
            <?php endif; ?>
          </td>

          <!-- Original Order -->
          <td>
            <?php if ($origNo): ?>
            <div style="font-weight:700;color:#1e293b;font-size:12px;"><?= htmlspecialchars($origNo) ?></div>
            <?php if ($origDate): ?>
            <div style="font-size:10px;color:var(--text-muted);"><?= date('d M Y', strtotime($origDate)) ?></div>
            <?php endif; ?>
            <div style="font-size:11px;font-weight:600;color:#059669;margin-top:1px;">€<?= number_format($origAmt,2) ?></div>
            <?php else: ?>
            <div style="font-size:12px;color:#cbd5e1;">—</div>
            <?php endif; ?>
          </td>

          <!-- Customer -->
          <td>
            <div style="font-weight:600;font-size:13px;color:var(--text);"><?= htmlspecialchars($cName ?: '—') ?></div>
            <?php if ($cCompany): ?><div style="font-size:11px;color:var(--text-muted);font-style:italic;"><?= htmlspecialchars($cCompany) ?></div><?php endif; ?>
            <?php if ($cEmail):   ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($cEmail) ?></div><?php endif; ?>
          </td>

          <!-- Return Reason -->
          <td>
            <?php if ($reason): ?>
            <div style="font-size:12px;color:#475569;line-height:1.4;" title="<?= htmlspecialchars($reason) ?>"><?= htmlspecialchars($reasonShort) ?></div>
            <?php else: ?>
            <div style="font-size:12px;color:#cbd5e1;font-style:italic;">Not specified</div>
            <?php endif; ?>
            <?php if ($rejectR && $rs === 'Return Request Cancelled'): ?>
            <div style="font-size:10px;color:#dc2626;font-weight:600;margin-top:3px;display:flex;align-items:center;gap:3px;">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <?= htmlspecialchars(mb_substr($rejectR,0,40).(mb_strlen($rejectR)>40?'…':'')) ?>
            </div>
            <?php endif; ?>
          </td>

          <!-- Return Status -->
          <td>
            <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;
                         background:<?= $rsBg ?>;color:<?= $rsClr ?>;border:1px solid <?= $rsClr ?>44;
                         white-space:nowrap;display:inline-block;">
              <?= htmlspecialchars($rs ?: '—') ?>
            </span>
          </td>

          <!-- Payment Status -->
          <td>
            <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;
                         background:<?= $psBg ?>;color:<?= $psClr ?>;white-space:nowrap;display:inline-block;">
              <?= htmlspecialchars($ps) ?>
            </span>
          </td>

          <!-- Refund Amount -->
          <td style="text-align:right;">
            <div style="font-size:14px;font-weight:800;color:#dc2626;">€<?= number_format($total,2) ?></div>
          </td>

          <!-- Handling Fee -->
          <td style="text-align:right;">
            <?php if ($handling > 0): ?>
            <div style="font-size:12px;font-weight:700;color:#ea580c;">−€<?= number_format($handling,2) ?></div>
            <?php else: ?>
            <div style="font-size:12px;color:#cbd5e1;">—</div>
            <?php endif; ?>
          </td>

          <!-- Net Refund -->
          <td style="text-align:right;">
            <div style="font-size:13px;font-weight:800;color:<?= $netRefund>0?'#059669':'#94a3b8' ?>;">
              €<?= number_format($netRefund,2) ?>
            </div>
          </td>

          <!-- Date -->
          <td style="text-align:center;font-size:12px;color:var(--text-muted);"><?= $dateFmt ?></td>

          <!-- Age -->
          <td style="text-align:center;">
            <div style="font-size:13px;<?= $ageStyle ?>"><?= $age ?>d</div>
          </td>

          <!-- Actions -->
          <td style="text-align:center;">
            <div style="display:inline-flex;gap:4px;">
              <button title="View Details"
                      onclick="rfViewDetail(<?= $rid ?>)"
                      style="width:28px;height:28px;border-radius:6px;border:none;background:#f1f5f9;color:#64748b;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"
                      onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
              <?php if ($canEdit && !in_array($rs,['Return Completed','Return Request Cancelled'])): ?>
              <button title="Update Status"
                      onclick="rfOpenStatusModal(<?= $rid ?>,<?= htmlspecialchars(json_encode($rs),ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($ps),ENT_QUOTES) ?>)"
                      style="width:28px;height:28px;border-radius:6px;border:none;background:#eff6ff;color:#2563eb;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"
                      onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Empty state -->
  <div id="rfEmpty" style="display:none;padding:50px 20px;text-align:center;">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#c0ccd8" stroke-width="1.2" style="margin:0 auto 12px;display:block;"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    <div style="font-size:14px;font-weight:600;color:var(--text-muted);">No refund requests found.</div>
    <div style="font-size:12px;color:#94a3b8;margin-top:4px;">Try adjusting or clearing the filters above.</div>
  </div>

</div><!-- /card -->

<!-- ═══════════════════════════════════════════════════
     VIEW DETAIL MODAL
═══════════════════════════════════════════════════ -->
<div id="rfDetailModal" class="modal-overlay">
  <div class="modal" style="max-width:800px;width:96%;max-height:92vh;display:flex;flex-direction:column;">
    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div>
        <div style="font-size:16px;font-weight:800;color:#1e293b;" id="rfDetailTitle">Refund Details</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:1px;" id="rfDetailSub">Loading…</div>
      </div>
      <button onclick="closeModal('rfDetailModal')" style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:26px;line-height:1;">×</button>
    </div>
    <div id="rfDetailBody" style="overflow-y:auto;flex:1;min-height:0;padding:20px;">
      <div style="text-align:center;padding:40px;color:var(--text-muted);">Loading…</div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     UPDATE STATUS MODAL
═══════════════════════════════════════════════════ -->
<div id="rfStatusModal" class="modal-overlay">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header">
      <span class="modal-title">Update Refund Status</span>
      <button class="modal-close" onclick="closeModal('rfStatusModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form id="rfStatusForm" method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateReturnStatus') ?>">
        <input type="hidden" name="refund_order_id" id="rfsOrderId" value="0">

        <div class="fg" style="margin-bottom:12px;">
          <label>Return Status <span class="req">*</span></label>
          <select name="return_status" id="rfsReturnStatus" class="form-control" onchange="rfOnStatusChange(this.value)">
            <?php foreach([
              'Return Requested','Return Request Approved','Return Request Cancelled',
              'Pickup Scheduled','Pickup Completed','QC Approved','Return Completed',
            ] as $s): ?>
            <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="fg" style="margin-bottom:12px;">
          <label>Payment Status <span class="req">*</span></label>
          <select name="payment_status" id="rfsPayStatus" class="form-control">
            <?php foreach(['Payment Pending','Refund Initiated','Refund Completed','Not Required'] as $s): ?>
            <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Reject reason panel — shown only when cancelled -->
        <div id="rfsRejectPanel" style="display:none;background:#fff1f2;border:1px solid #fca5a5;border-radius:8px;padding:12px 14px;margin-bottom:12px;">
          <div style="font-size:12px;font-weight:700;color:#dc2626;margin-bottom:6px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:3px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            Rejection Reason
          </div>
          <textarea name="reject_reason" id="rfsRejectReason" class="form-control" rows="2"
                    placeholder="Explain why the return request is being rejected…"
                    style="resize:vertical;min-height:60px;font-size:12px;"></textarea>
        </div>

        <div class="fg" style="margin-bottom:18px;">
          <label>Remark <span style="font-size:11px;color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <textarea name="remark" class="form-control" rows="2"
                    placeholder="Internal note or customer-facing update…"
                    style="resize:vertical;min-height:56px;"></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
          <button type="button" class="btn btn--ghost" onclick="closeModal('rfStatusModal')">Cancel</button>
          <button type="submit" class="btn btn--primary">Update &amp; Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════ -->
<style>
/* ══ Pagination ══ */
.rf-pgbar {
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;flex-wrap:wrap;padding:13px 18px;
  border-bottom:1px solid var(--border);background:#fff;
}
.rf-pgbar__info { font-size:13px;color:#64748b;white-space:nowrap; }
.rf-pgbar__info strong { color:#1e293b;font-weight:700; }
.rf-pgbar__perpage { display:flex;align-items:center;gap:10px;flex-shrink:0; }
.rf-pgbar__sel {
  -webkit-appearance:none;appearance:none;height:36px;padding:0 32px 0 14px;
  border:1.5px solid #e2e8f0;border-radius:20px;
  font-size:13px;font-weight:600;color:#1e293b;
  background:#fff;cursor:pointer;outline:none;transition:border-color .15s;
}
.rf-pgbar__sel:hover,.rf-pgbar__sel:focus { border-color:#6366f1; }
.rf-pgbar__apply {
  height:36px;padding:0 20px;background:#1e293b;color:#fff;
  border:none;border-radius:20px;font-size:13px;font-weight:600;
  cursor:pointer;transition:background .15s;
}
.rf-pgbar__apply:hover { background:#0f172a; }
.rf-pgbar__pager { display:flex;align-items:center;gap:5px;flex-wrap:wrap; }
.rf-pg-nav {
  height:36px;padding:0 16px;border:1.5px solid #e2e8f0;border-radius:20px;
  background:#fff;font-size:13px;font-weight:600;color:#374151;
  cursor:pointer;white-space:nowrap;transition:border-color .15s,color .15s;
}
.rf-pg-nav:hover:not(:disabled) { border-color:#6366f1;color:#6366f1; }
.rf-pg-nav:disabled { color:#cbd5e1;border-color:#f1f5f9;cursor:default; }
.rf-pg-num {
  width:36px;height:36px;border:1.5px solid #e2e8f0;border-radius:50%;
  background:#fff;font-size:13px;font-weight:600;color:#374151;
  cursor:pointer;display:inline-flex;align-items:center;justify-content:center;
  transition:border-color .15s,color .15s;flex-shrink:0;
}
.rf-pg-num:hover { border-color:#6366f1;color:#6366f1; }
.rf-pg-dots { font-size:13px;color:#94a3b8;padding:0 2px;display:inline-flex;align-items:center; }
@media(max-width:640px){ .rf-pgbar { flex-direction:column;align-items:flex-start;gap:10px; } }
</style>

<!-- ═══════════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════════ -->
<script>
/* ══════════════════════════════════════════
   PAGINATION
══════════════════════════════════════════ */
var _rfPage = 1, _rfPerPage = 20, _rfRows = [];

function rfInit() {
  _rfRows = Array.from(document.querySelectorAll('#rfTbody .rf-row'));
  rfRender();
}
function rfApplyPerPage() {
  _rfPerPage = parseInt(document.getElementById('rfPerPage').value, 10) || 20;
  _rfPage = 1; rfRender();
}
function rfRender() {
  var pp = _rfPerPage, total = _rfRows.length;
  var pages = Math.max(1, Math.ceil(total / pp));
  if (_rfPage > pages) _rfPage = pages;
  if (_rfPage < 1)     _rfPage = 1;
  var start = (_rfPage - 1) * pp, end = Math.min(start + pp, total);
  _rfRows.forEach(function(r, i) {
    var vis = i >= start && i < end;
    r.style.display = vis ? '' : 'none';
    if (vis) r.querySelector('.rf-sno').textContent = i + 1;
  });
  document.getElementById('rfCount').textContent      = total;
  document.getElementById('rfRangeStart').textContent = total > 0 ? start + 1 : 0;
  document.getElementById('rfRangeEnd').textContent   = end;
  document.getElementById('rfEmpty').style.display    = total === 0 ? 'block' : 'none';
  _rfBuildPager(pages);
}
function _rfBuildPager(pages) {
  var pager = document.getElementById('rfPager');
  pager.innerHTML = '';
  pager.appendChild(_rfNavBtn('Prev', _rfPage - 1, _rfPage <= 1));
  if (pages > 1) {
    _rfPageNums(_rfPage, pages).forEach(function(n) {
      if (n === -1) { var d = document.createElement('span'); d.className = 'rf-pg-dots'; d.textContent = '...'; pager.appendChild(d); }
      else pager.appendChild(_rfNumBtn(n));
    });
  }
  pager.appendChild(_rfNavBtn('Next', _rfPage + 1, _rfPage >= pages));
}
function _rfPageNums(cur, total) {
  if (total <= 1) return [];
  var set = new Set();
  if (cur !== 1) set.add(1); if (cur !== total) set.add(total);
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
function _rfNavBtn(label, pg, disabled) {
  var b = document.createElement('button');
  b.textContent = label; b.className = 'rf-pg-nav'; b.disabled = disabled;
  if (!disabled) b.onclick = function() { _rfPage = pg; rfRender(); };
  return b;
}
function _rfNumBtn(pg) {
  var b = document.createElement('button');
  b.textContent = String(pg); b.className = 'rf-pg-num';
  b.onclick = function() { _rfPage = pg; rfRender(); };
  return b;
}
document.addEventListener('DOMContentLoaded', rfInit);

/* ══════════════════════════════════════════
   VIEW DETAIL MODAL
══════════════════════════════════════════ */
function rfViewDetail(rid) {
  document.getElementById('rfDetailTitle').textContent = 'Refund Details';
  document.getElementById('rfDetailSub').textContent   = 'Loading request #' + rid + '…';
  document.getElementById('rfDetailBody').innerHTML    = '<div style="text-align:center;padding:40px;color:var(--text-muted);">Loading…</div>';
  openModal('rfDetailModal');

  fetch('service?urlstring=<?= EncryptURL('action=GetRefundOrderDetail') ?>&id=' + rid)
    .then(function(r){ return r.json(); })
    .catch(function(){ return null; })
    .then(function(data) {
      if (!data || !data.ok) {
        document.getElementById('rfDetailBody').innerHTML = '<div style="padding:20px;color:#dc2626;">Failed to load refund details.</div>';
        return;
      }
      _rfRenderDetail(data);
    });
}

function _rfRenderDetail(data) {
  var o = data.order, items = data.items || [], hist = data.history || [];

  document.getElementById('rfDetailTitle').textContent = o.order_number || 'Return Order';
  document.getElementById('rfDetailSub').textContent   = 'Return request detail and status history';

  var rsBadge = _rfBadgeHtml(o.return_status, _rfRsColor(o.return_status));
  var psBadge = _rfBadgeHtml(o.payment_status, _rfPsColor(o.payment_status));
  var netRef  = parseFloat(o.final_total || 0) - parseFloat(o.handling_fee || 0);

  var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">';
  /* Return order card */
  html += '<div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 14px;">'
    +'<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:4px;">Return Reference</div>'
    +'<div style="font-size:16px;font-weight:800;color:#4f46e5;">' + _esc(o.order_number) + '</div>'
    +'<div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap;">' + rsBadge + psBadge + '</div>'
    +'</div>';
  /* Original order card */
  html += '<div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 14px;">'
    +'<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:4px;">Original Order</div>'
    +'<div style="font-size:15px;font-weight:700;color:#1e293b;">' + _esc(o.orig_order_number || '—') + '</div>'
    +(o.orig_order_date ? '<div style="font-size:11px;color:#64748b;margin-top:2px;">' + _dateFmt(o.orig_order_date) + '</div>' : '')
    +'<div style="font-size:14px;font-weight:700;color:#059669;margin-top:2px;">€' + parseFloat(o.orig_order_amt||0).toFixed(2) + '</div>'
    +'</div>';
  html += '</div>';

  /* Customer */
  html += '<div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-bottom:12px;">'
    +'<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:6px;">Customer</div>'
    +'<div style="font-size:13px;font-weight:700;color:#1e293b;">' + _esc(o.cust_name || '—') + '</div>'
    +(o.cust_company ? '<div style="font-size:11px;color:#64748b;font-style:italic;">' + _esc(o.cust_company) + '</div>' : '')
    +(o.cust_email   ? '<div style="font-size:11px;color:#64748b;">' + _esc(o.cust_email)   + '</div>' : '')
    +(o.cust_phone   ? '<div style="font-size:11px;color:#64748b;">📞 ' + _esc(o.cust_phone) + '</div>' : '')
    +'</div>';

  /* Return reason */
  if (o.return_reason) {
    html += '<div style="background:#fefce8;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;margin-bottom:12px;">'
      +'<div style="font-size:10px;font-weight:700;color:#d97706;text-transform:uppercase;margin-bottom:6px;">Customer Return Reason</div>'
      +'<div style="font-size:13px;color:#78350f;line-height:1.5;">' + _esc(o.return_reason) + '</div>'
      +'</div>';
  }
  if (o.reject_reason && o.return_status === 'Return Request Cancelled') {
    html += '<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px 14px;margin-bottom:12px;">'
      +'<div style="font-size:10px;font-weight:700;color:#dc2626;text-transform:uppercase;margin-bottom:6px;">Admin Rejection Reason</div>'
      +'<div style="font-size:13px;color:#7f1d1d;line-height:1.5;">' + _esc(o.reject_reason) + '</div>'
      +'</div>';
  }

  /* Items table */
  if (items.length) {
    html += '<div style="margin-bottom:14px;">'
      +'<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:8px;">Return Items</div>'
      +'<table style="width:100%;border-collapse:collapse;font-size:12px;">'
      +'<thead><tr style="background:#f1f5f9;">'
      +'<th style="padding:6px 10px;text-align:left;color:#64748b;font-weight:600;">Product</th>'
      +'<th style="padding:6px 10px;text-align:center;color:#64748b;font-weight:600;">Qty</th>'
      +'<th style="padding:6px 10px;text-align:right;color:#64748b;font-weight:600;">Unit</th>'
      +'<th style="padding:6px 10px;text-align:right;color:#64748b;font-weight:600;">Total</th>'
      +'</tr></thead><tbody>';
    items.forEach(function(it) {
      html += '<tr style="border-bottom:1px solid #f1f5f9;">'
        +'<td style="padding:7px 10px;"><div style="font-weight:600;color:#1e293b;">'+_esc(it.product_name)+'</div>'
        +(it.product_code?'<div style="font-size:10px;color:#94a3b8;font-family:monospace;">'+_esc(it.product_code)+'</div>':'')
        +'</td>'
        +'<td style="padding:7px 10px;text-align:center;">'+it.quantity+'</td>'
        +'<td style="padding:7px 10px;text-align:right;">€'+parseFloat(it.product_amt||0).toFixed(2)+'</td>'
        +'<td style="padding:7px 10px;text-align:right;font-weight:700;">€'+parseFloat(it.final_amt||0).toFixed(2)+'</td>'
        +'</tr>';
    });
    html += '</tbody></table></div>';
  }

  /* Financial summary */
  html += '<div style="border:1px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:14px;">'
    +'<table style="width:100%;border-collapse:collapse;font-size:13px;">'
    +'<tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:9px 14px;color:#64748b;">Refund Amount</td><td style="text-align:right;padding:9px 14px;font-weight:700;color:#dc2626;">€'+parseFloat(o.final_total||0).toFixed(2)+'</td></tr>';
  if (parseFloat(o.handling_fee||0) > 0) {
    html += '<tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:9px 14px;color:#64748b;">Handling Fee Deducted</td><td style="text-align:right;padding:9px 14px;font-weight:700;color:#ea580c;">−€'+parseFloat(o.handling_fee).toFixed(2)+'</td></tr>';
  }
  html += '<tr style="background:#f8fafc;"><td style="padding:10px 14px;font-weight:700;font-size:14px;">Net Refund to Customer</td><td style="text-align:right;padding:10px 14px;font-weight:800;font-size:15px;color:#059669;">€'+netRef.toFixed(2)+'</td></tr>'
    +'</table></div>';

  /* History timeline */
  if (hist.length) {
    html += '<div><div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:12px;">Status History</div>'
      +'<div style="position:relative;padding-left:22px;">'
      +'<div style="position:absolute;left:5px;top:0;bottom:0;width:2px;background:#e2e8f0;"></div>';
    hist.forEach(function(h, idx) {
      var dotClr = h.status === 'Return Completed' ? '#16a34a'
                 : h.status === 'Return Request Cancelled' ? '#dc2626'
                 : h.status === 'Return Requested' ? '#d97706' : '#2563eb';
      var isLast = idx === hist.length - 1;
      html += '<div style="position:relative;margin-bottom:'+(isLast?'0':'16px')+';">'
        +'<div style="position:absolute;left:-18px;top:3px;width:12px;height:12px;border-radius:50%;background:'+dotClr+';border:2px solid #fff;box-shadow:0 0 0 2px #e2e8f0;"></div>'
        +'<div style="font-size:11px;color:#94a3b8;margin-bottom:2px;">'+_dateFmt(h.date)+' &middot; '+_esc(h.by)+'</div>'
        +'<div style="font-size:13px;font-weight:700;color:#1e293b;">'+(h.status||'—')+'</div>'
        +(h.pay_status?'<div style="font-size:11px;color:#64748b;">'+_esc(h.pay_status)+'</div>':'')
        +(h.remark?'<div style="font-size:11px;color:#475569;font-style:italic;margin-top:2px;">&ldquo;'+_esc(h.remark)+'&rdquo;</div>':'')
        +'</div>';
    });
    html += '</div></div>';
  }

  document.getElementById('rfDetailBody').innerHTML = html;
}

/* ══════════════════════════════════════════
   UPDATE STATUS MODAL
══════════════════════════════════════════ */
function rfOpenStatusModal(rid, currentRS, currentPS) {
  document.getElementById('rfsOrderId').value = rid;
  var rsSel = document.getElementById('rfsReturnStatus');
  var psSel = document.getElementById('rfsPayStatus');
  for (var i = 0; i < rsSel.options.length; i++) if (rsSel.options[i].value === currentRS) { rsSel.selectedIndex = i; break; }
  for (var j = 0; j < psSel.options.length; j++) if (psSel.options[j].value === currentPS) { psSel.selectedIndex = j; break; }
  document.getElementById('rfsRejectReason').value = '';
  rfOnStatusChange(currentRS);
  openModal('rfStatusModal');
}
function rfOnStatusChange(val) {
  document.getElementById('rfsRejectPanel').style.display = (val === 'Return Request Cancelled') ? '' : 'none';
  /* Auto-suggest payment status */
  var psSel = document.getElementById('rfsPayStatus');
  if (val === 'Return Completed') {
    for (var i = 0; i < psSel.options.length; i++) if (psSel.options[i].value === 'Refund Initiated') { psSel.selectedIndex = i; break; }
  }
}

/* ══════════════════════════════════════════
   CSV EXPORT
══════════════════════════════════════════ */
function rfExportCsv() {
  var headers = ['#','Return Ref','Original Order','Customer','Company','Return Status','Payment Status','Refund Amt (€)','Handling (€)','Net Refund (€)','Date','Age (days)'];
  var rows = [headers];
  document.querySelectorAll('#rfTbody .rf-row').forEach(function(r, i) {
    var c = r.querySelectorAll('td');
    rows.push([
      i+1,
      c[1]?.querySelector('div:first-child')?.textContent?.trim()||'',
      c[2]?.querySelector('div:first-child')?.textContent?.trim()||'',
      c[3]?.querySelector('div:first-child')?.textContent?.trim()||'',
      c[3]?.querySelectorAll('div')[1]?.textContent?.trim()||'',
      c[5]?.querySelector('span')?.textContent?.trim()||'',
      c[6]?.querySelector('span')?.textContent?.trim()||'',
      c[7]?.querySelector('div')?.textContent?.replace('€','').trim()||'',
      c[8]?.querySelector('div')?.textContent?.replace(/[−€]/g,'').trim()||'0',
      c[9]?.querySelector('div')?.textContent?.replace('€','').trim()||'',
      c[10]?.textContent?.trim()||'',
      c[11]?.querySelector('div')?.textContent?.replace('d','').trim()||'',
    ]);
  });
  var csv = rows.map(function(r){return r.map(function(c){return '"'+String(c).replace(/"/g,'""')+'"';}).join(',');}).join('\n');
  var a = document.createElement('a');
  a.href = 'data:text/csv;charset=utf-8,﻿'+encodeURIComponent(csv);
  a.download = 'refunds-'+new Date().toISOString().slice(0,10)+'.csv';
  document.body.appendChild(a); a.click(); a.remove();
}

/* ── Helpers ── */
function _esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function _dateFmt(s) {
  var d = new Date(s); if (isNaN(d)) return s||'';
  return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
}
function _rfBadgeHtml(txt, clr) {
  return '<span style="font-size:10px;font-weight:700;padding:2px 9px;border-radius:20px;background:'+clr[1]+';color:'+clr[0]+';white-space:nowrap;display:inline-block;">'+_esc(txt)+'</span>';
}
function _rfRsColor(s) {
  var m={'Return Requested':['#d97706','#fefce8'],'Return Request Approved':['#0284c7','#dbeafe'],
         'Pickup Scheduled':['#7c3aed','#f3e8ff'],'Pickup Completed':['#0891b2','#e0f2fe'],
         'QC Approved':['#059669','#ecfdf5'],'Return Completed':['#16a34a','#f0fdf4'],
         'Return Request Cancelled':['#dc2626','#fee2e2']};
  return m[s]||['#64748b','#f8fafc'];
}
function _rfPsColor(s) {
  var m={'Refund Initiated':['#7c3aed','#f3e8ff'],'Refund Completed':['#16a34a','#f0fdf4'],
         'Payment Pending':['#d97706','#fefce8'],'Not Required':['#64748b','#f8fafc']};
  return m[s]||['#64748b','#f8fafc'];
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
