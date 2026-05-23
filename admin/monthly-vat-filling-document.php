<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'monthly-vat-filling-document';
$pageTitle   = 'VAT Filing';

$canView = sinelec_can('view');
$canEdit = sinelec_can('edit');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view VAT Filing.');
    header('location:dashboard'); exit();
}

$controller = new AdminController();

/* ── URL parameters ── */
$view  = (isset($_GET['view']) && $_GET['view'] === 'yearly') ? 'yearly' : 'monthly';
$year  = max(2020, min(2035, (int)($_GET['year']  ?? date('Y'))));
$month = max(1,    min(12,   (int)($_GET['month'] ?? date('n'))));

/* ── Flash (from redirect after SaveVatFiling) ── */
$flashType = htmlspecialchars($_GET['flash_type'] ?? '');
$flashMsg  = htmlspecialchars($_GET['flash_msg']  ?? '');

/* ── Load data ── */
if ($view === 'monthly') {
    $d        = $controller->getVatMonthlyData($year, $month);
    $summary  = $d['summary'];
    $byRate   = $d['byRate'];
    $orders   = $d['orders'];
    $filing   = $d['filing'];
} else {
    $d            = $controller->getVatYearlyData($year);
    $monthly      = $d['monthly'];    /* array of monthly row objects */
    $totals       = $d['totals'];
    $byRate       = $d['byRate'];
    $filingMap    = $d['filingMap'];  /* [monthNum => stdClass] */
    $annualFiling = $d['annualFiling'];
}

/* ── Helpers ── */
$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$monthShort = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

function vatFmt(float $v): string {
    return '€ ' . number_format($v, 2);
}

function vatStatusBadge(?object $f, bool $small = false): string {
    $sz = $small ? 'font-size:10px;padding:2px 7px;' : 'font-size:11px;padding:3px 10px;';
    if (!$f) {
        return "<span style='$sz background:#f1f5f9;color:#64748b;border-radius:20px;font-weight:600;display:inline-block;'>Draft</span>";
    }
    return match($f->FILING_STATUS ?? 'Draft') {
        'Filed'   => "<span style='$sz background:#dcfce7;color:#16a34a;border-radius:20px;font-weight:600;display:inline-block;'>✓ Filed</span>",
        'Overdue' => "<span style='$sz background:#fee2e2;color:#dc2626;border-radius:20px;font-weight:600;display:inline-block;'>⚠ Overdue</span>",
        default   => "<span style='$sz background:#fef9c3;color:#b45309;border-radius:20px;font-weight:600;display:inline-block;'>Draft</span>",
    };
}

function monthFilingColor(?object $f): array {
    /* returns [bg, border, text] */
    if (!$f) return ['#f8fafc','#e2e8f0','#94a3b8'];
    return match($f->FILING_STATUS ?? 'Draft') {
        'Filed'   => ['#f0fdf4','#86efac','#16a34a'],
        'Overdue' => ['#fff1f2','#fca5a5','#dc2626'],
        default   => ['#fffbeb','#fde68a','#b45309'],
    };
}

/* Year options for dropdowns */
$yearOptions = range((int)date('Y') + 1, 2020);
$periodKey   = sprintf('%04d-%02d', $year, $month);

ob_start();
?>
<style>
/* ════════════════════════════════════════
   VAT Filing — page styles
════════════════════════════════════════ */
.vat-wrap           { max-width:1400px; margin:0 auto; padding:0 4px; }
.vat-hdr            { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:20px; }
.vat-hdr h1         { font-size:20px; font-weight:800; color:#1e293b; margin:0; }
.vat-hdr h1 small   { font-size:13px; font-weight:500; color:#64748b; margin-left:8px; }
.vat-toggle         { display:flex; background:#f1f5f9; border-radius:8px; padding:3px; gap:2px; }
.vat-toggle a       { padding:6px 18px; border-radius:6px; font-size:13px; font-weight:600;
                       text-decoration:none; color:#64748b; transition:all .15s; }
.vat-toggle a.active{ background:#fff; color:#1e293b; box-shadow:0 1px 4px rgba(0,0,0,.12); }
.vat-hdr-right      { display:flex; align-items:center; gap:8px; }
.vat-btn            { display:inline-flex; align-items:center; gap:5px; padding:7px 14px;
                       border-radius:7px; font-size:12px; font-weight:600; cursor:pointer;
                       border:none; text-decoration:none; }
.vat-btn-outline    { background:#fff; color:#475569; border:1.5px solid #cbd5e1; }
.vat-btn-outline:hover{ background:#f8fafc; }
.vat-btn-primary    { background:#1e40af; color:#fff; }
.vat-btn-primary:hover{ background:#1d3db8; }
.vat-btn-green      { background:#16a34a; color:#fff; }
.vat-btn-green:hover{ background:#15803d; }

/* Period selector */
.vat-period-bar     { background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                       padding:14px 18px; margin-bottom:20px;
                       display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.vat-period-bar label{ font-size:12px; font-weight:600; color:#475569; margin-right:4px; }
.vat-period-bar select{ padding:6px 10px; border:1.5px solid #e2e8f0; border-radius:6px;
                          font-size:13px; color:#1e293b; background:#f8fafc; }
.vat-period-bar select:focus{ outline:none; border-color:#3b82f6; }

/* KPI grid */
.vat-kpi            { display:grid; grid-template-columns:repeat(7,1fr); gap:10px; margin-bottom:20px; }
.vat-kpi-card       { background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                       padding:12px 14px; }
.vat-kpi-card .lbl  { font-size:10px; font-weight:600; color:#64748b; text-transform:uppercase;
                       letter-spacing:.5px; margin-bottom:4px; }
.vat-kpi-card .val  { font-size:16px; font-weight:800; color:#1e293b; line-height:1.1; }
.vat-kpi-card .sub  { font-size:10px; color:#94a3b8; margin-top:2px; }

/* VAT return boxes (EU-style) */
.vat-return-box     { background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                       padding:18px 22px; margin-bottom:20px; }
.vat-return-box h3  { font-size:13px; font-weight:700; color:#1e293b; margin:0 0 14px;
                       padding-bottom:10px; border-bottom:2px solid #e2e8f0;
                       display:flex; align-items:center; gap:8px; }
.vrb-grid           { display:grid; grid-template-columns:1fr 1fr 1fr; gap:1px;
                       background:#e2e8f0; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; }
.vrb-section        { background:#fff; padding:14px 16px; }
.vrb-section h4     { font-size:10px; font-weight:700; color:#94a3b8; text-transform:uppercase;
                       letter-spacing:.8px; margin:0 0 10px; }
.vrb-row            { display:flex; justify-content:space-between; align-items:baseline;
                       padding:5px 0; border-bottom:1px dashed #f1f5f9; }
.vrb-row:last-child { border-bottom:none; }
.vrb-row .bnum      { font-size:10px; font-weight:700; color:#94a3b8; margin-right:6px; }
.vrb-row .bdesc     { font-size:12px; color:#475569; }
.vrb-row .bamount   { font-size:14px; font-weight:700; color:#1e293b; }
.vrb-row.net        { background:#f0f9ff; margin:-1px -16px -14px; padding:10px 16px;
                       border-radius:0 0 0 0; }
.vrb-row.net .bdesc { font-weight:700; color:#0369a1; }
.vrb-row.net .bamount{ color:#0369a1; font-size:16px; }

/* Rate breakdown */
.vat-section        { background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                       margin-bottom:20px; }
.vat-section-hdr    { padding:14px 18px; border-bottom:1px solid #f1f5f9;
                       display:flex; align-items:center; justify-content:space-between; }
.vat-section-hdr h3 { font-size:13px; font-weight:700; color:#1e293b; margin:0; }
.vat-tbl            { width:100%; border-collapse:collapse; font-size:12px; }
.vat-tbl th         { padding:9px 14px; background:#f8fafc; color:#64748b; font-size:10px;
                       font-weight:700; text-transform:uppercase; letter-spacing:.4px;
                       border-bottom:1px solid #e2e8f0; }
.vat-tbl td         { padding:9px 14px; border-bottom:1px solid #f8fafc; color:#374151; }
.vat-tbl tr:last-child td { border-bottom:none; }
.vat-tbl tr:hover td{ background:#f8fafc; }
.vat-tbl tfoot td   { background:#f1f5f9; font-weight:700; color:#1e293b; border-top:2px solid #e2e8f0; }
.vat-rate-badge     { display:inline-block; padding:2px 8px; border-radius:12px;
                       font-size:10px; font-weight:700; }

/* Order table */
.vat-tbl-wrap       { overflow-x:auto; }

/* Filing declaration */
.vat-filing-card    { background:#fff; border:1.5px solid #bfdbfe; border-radius:10px;
                       padding:18px 22px; margin-bottom:20px; }
.vat-filing-card h3 { font-size:13px; font-weight:700; color:#1e40af; margin:0 0 16px;
                       display:flex; align-items:center; gap:8px; }
.vat-form-grid      { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:14px; }
.vat-form-full      { grid-column:1/-1; }
.vf-label           { display:block; font-size:11px; font-weight:600; color:#475569;
                       margin-bottom:5px; }
.vf-input           { width:100%; padding:8px 10px; border:1.5px solid #e2e8f0;
                       border-radius:7px; font-size:13px; color:#1e293b; box-sizing:border-box; }
.vf-input:focus     { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.12); }
.vf-select          { appearance:none; background:#f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M6 8L1 3h10z' fill='%2364748b'/%3E%3C/svg%3E") right 10px center no-repeat; }

/* Yearly view */
.vat-year-kpi       { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
.vat-qtr-card       { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 16px; }
.vat-qtr-card h4    { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;
                       letter-spacing:.5px; margin:0 0 10px; }
.vat-qtr-card .qval { font-size:18px; font-weight:800; color:#1e293b; }
.vat-qtr-card .qsub { font-size:11px; color:#64748b; margin-top:2px; }
.vat-qtr-card .qvat { font-size:13px; font-weight:700; color:#dc2626; margin-top:6px; }

/* Yearly table row status dot */
.status-dot         { width:8px; height:8px; border-radius:50%; display:inline-block;
                       margin-right:5px; vertical-align:middle; }

/* Flash */
.vat-flash          { padding:10px 16px; border-radius:8px; margin-bottom:16px;
                       font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px; }
.vat-flash.ok       { background:#dcfce7; color:#15803d; border:1px solid #86efac; }
.vat-flash.err      { background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; }

/* Empty state */
.vat-empty          { text-align:center; padding:50px 20px; color:#94a3b8; font-size:13px; }

/* Print */
@media print {
    .vat-toggle,.vat-period-bar,.vat-hdr-right,.vat-filing-card,
    .vat-btn,.no-print { display:none !important; }
    .vat-return-box,.vat-section,.vat-kpi-card { break-inside:avoid; }
    body { font-size:11px; }
}
</style>

<div class="vat-wrap">

  <!-- ── Header ── -->
  <div class="vat-hdr">
    <h1>
      <?= $view === 'yearly' ? '📊' : '🧾' ?>
      <?= $view === 'yearly'
          ? "Annual VAT Summary <small>$year</small>"
          : "Monthly VAT Return <small>" . $monthNames[$month] . " $year</small>" ?>
      <?php if ($view === 'monthly'): ?>
        &nbsp;<?= vatStatusBadge($filing ?? null) ?>
      <?php else: ?>
        &nbsp;<?= vatStatusBadge($annualFiling ?? null) ?>
      <?php endif; ?>
    </h1>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <!-- Monthly / Yearly toggle -->
      <div class="vat-toggle">
        <a href="monthly-vat-filling-document?view=monthly&month=<?= $month ?>&year=<?= $year ?>"
           class="<?= $view === 'monthly' ? 'active' : '' ?>">📅 Monthly</a>
        <a href="monthly-vat-filling-document?view=yearly&year=<?= $year ?>"
           class="<?= $view === 'yearly' ? 'active' : '' ?>">📊 Yearly</a>
      </div>
      <div class="vat-hdr-right">
        <a href="javascript:window.print()" class="vat-btn vat-btn-outline no-print">🖨️ Print</a>
        <a href="#vatExportCsv" onclick="vatExportCsv()" class="vat-btn vat-btn-outline no-print">📥 Export CSV</a>
      </div>
    </div>
  </div>

  <!-- ── Flash ── -->
  <?php if ($flashMsg): ?>
    <div class="vat-flash <?= $flashType === 'ok' ? 'ok' : 'err' ?>">
      <?= $flashType === 'ok' ? '✓' : '✕' ?> <?= $flashMsg ?>
    </div>
  <?php endif; ?>

  <!-- ── Period selector ── -->
  <div class="vat-period-bar no-print">
    <form method="GET" action="" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <input type="hidden" name="view" value="<?= $view ?>">
      <?php if ($view === 'monthly'): ?>
        <label>Month
          <select name="month">
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
            <?php endfor; ?>
          </select>
        </label>
      <?php endif; ?>
      <label>Year
        <select name="year">
          <?php foreach ($yearOptions as $y): ?>
            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button type="submit" class="vat-btn vat-btn-primary">Apply</button>
      <?php if ($view === 'monthly'): ?>
        <span style="font-size:11px;color:#94a3b8;">Period: 01/<?= sprintf('%02d',$month) ?>/<?= $year ?> – <?= date('d', mktime(0,0,0,$month+1,0,$year)) ?>/<?= sprintf('%02d',$month) ?>/<?= $year ?></span>
      <?php endif; ?>
    </form>
  </div>

<?php /* ════════════════════════════════════════════════════
         MONTHLY VIEW
════════════════════════════════════════════════════ */ if ($view === 'monthly'): ?>

  <?php
  $sOrderCount   = (float)($summary->ORDER_COUNT       ?? 0);
  $sTotalBilled  = (float)($summary->TOTAL_BILLED      ?? 0);
  $sNetExcl      = (float)($summary->NET_EXCL_VAT      ?? 0);
  $sOutputVat    = (float)($summary->OUTPUT_VAT        ?? 0);
  $sZeroRated    = (float)($summary->ZERO_RATED_NET    ?? 0);
  $sStdRated     = (float)($summary->STD_RATED_NET     ?? 0);
  $sCollected    = (float)($summary->COLLECTED         ?? 0);
  $sPending      = (float)($summary->PENDING           ?? 0);
  $sShipping     = (float)($summary->SHIPPING_REVENUE  ?? 0);
  $sDiscounts    = (float)($summary->TOTAL_DISCOUNTS   ?? 0);
  $sInputVat     = 0.00; /* purchases don't carry VAT in this schema */
  $sNetVat       = $sOutputVat - $sInputVat;
  ?>

  <!-- ── KPI Cards ── -->
  <div class="vat-kpi">
    <div class="vat-kpi-card" style="border-top:3px solid #3b82f6;">
      <div class="lbl">Total Orders</div>
      <div class="val"><?= number_format($sOrderCount) ?></div>
      <div class="sub">This period</div>
    </div>
    <div class="vat-kpi-card" style="border-top:3px solid #8b5cf6;">
      <div class="lbl">Gross Billed</div>
      <div class="val" style="font-size:13px;"><?= vatFmt($sTotalBilled) ?></div>
      <div class="sub">Incl. VAT + shipping</div>
    </div>
    <div class="vat-kpi-card" style="border-top:3px solid #0891b2;">
      <div class="lbl">Net Sales</div>
      <div class="val" style="font-size:13px;"><?= vatFmt($sNetExcl) ?></div>
      <div class="sub">Excl. VAT</div>
    </div>
    <div class="vat-kpi-card" style="border-top:3px solid #dc2626;">
      <div class="lbl">Output VAT Due</div>
      <div class="val" style="font-size:13px;color:#dc2626;"><?= vatFmt($sOutputVat) ?></div>
      <div class="sub">Box 1</div>
    </div>
    <div class="vat-kpi-card" style="border-top:3px solid #f59e0b;">
      <div class="lbl">Zero-Rated</div>
      <div class="val" style="font-size:13px;"><?= vatFmt($sZeroRated) ?></div>
      <div class="sub">0% VAT sales</div>
    </div>
    <div class="vat-kpi-card" style="border-top:3px solid #16a34a;">
      <div class="lbl">Collected</div>
      <div class="val" style="font-size:13px;color:#16a34a;"><?= vatFmt($sCollected) ?></div>
      <div class="sub">Payment Successful</div>
    </div>
    <div class="vat-kpi-card" style="border-top:3px solid #f97316;">
      <div class="lbl">Pending</div>
      <div class="val" style="font-size:13px;color:#ea580c;"><?= vatFmt($sPending) ?></div>
      <div class="sub">Awaiting payment</div>
    </div>
  </div>

  <!-- ── EU-Style VAT Return Boxes ── -->
  <div class="vat-return-box">
    <h3><?= sb_icon_svg('receipt_long') ?> VAT Return Summary — <?= $monthNames[$month] . ' ' . $year ?></h3>
    <div class="vrb-grid">
      <!-- Output -->
      <div class="vrb-section">
        <h4>Output Tax (Sales)</h4>
        <div class="vrb-row">
          <div><span class="bnum">Box 1</span><span class="bdesc">VAT due on sales</span></div>
          <div class="bamount" style="color:#dc2626;"><?= vatFmt($sOutputVat) ?></div>
        </div>
        <div class="vrb-row">
          <div><span class="bnum">Box 6</span><span class="bdesc">Total sales (net, excl. VAT)</span></div>
          <div class="bamount"><?= vatFmt($sNetExcl) ?></div>
        </div>
        <div class="vrb-row">
          <div><span class="bnum"></span><span class="bdesc">  – Standard-rated</span></div>
          <div class="bamount" style="font-size:12px;"><?= vatFmt($sStdRated) ?></div>
        </div>
        <div class="vrb-row">
          <div><span class="bnum"></span><span class="bdesc">  – Zero-rated</span></div>
          <div class="bamount" style="font-size:12px;"><?= vatFmt($sZeroRated) ?></div>
        </div>
      </div>
      <!-- Input -->
      <div class="vrb-section">
        <h4>Input Tax (Purchases)</h4>
        <div class="vrb-row">
          <div><span class="bnum">Box 4</span><span class="bdesc">VAT reclaimable on purchases</span></div>
          <div class="bamount" style="color:#16a34a;"><?= vatFmt($sInputVat) ?></div>
        </div>
        <div class="vrb-row">
          <div><span class="bnum">Box 7</span><span class="bdesc">Total purchases (net)</span></div>
          <div class="bamount">—</div>
        </div>
        <div class="vrb-row" style="padding-top:10px;">
          <div colspan="2" style="font-size:10px;color:#94a3b8;font-style:italic;">
            ℹ Purchase VAT (input tax) is not currently tracked in this system. Input VAT = €0.00.
          </div>
        </div>
      </div>
      <!-- Net -->
      <div class="vrb-section">
        <h4>Net VAT Position</h4>
        <div class="vrb-row">
          <div><span class="bnum"></span><span class="bdesc">Output VAT (Box 1)</span></div>
          <div class="bamount"><?= vatFmt($sOutputVat) ?></div>
        </div>
        <div class="vrb-row">
          <div><span class="bnum"></span><span class="bdesc">Less: Input VAT (Box 4)</span></div>
          <div class="bamount">– <?= vatFmt($sInputVat) ?></div>
        </div>
        <div class="vrb-row net">
          <div><span class="bnum">Box 5</span><span class="bdesc">Net VAT <?= $sNetVat >= 0 ? 'Due' : 'Repayable' ?></span></div>
          <div class="bamount" style="font-size:18px;color:<?= $sNetVat >= 0 ? '#dc2626' : '#16a34a' ?>;">
            <?= $sNetVat >= 0 ? '' : '(' ?><?= vatFmt(abs($sNetVat)) ?><?= $sNetVat < 0 ? ')' : '' ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── VAT Rate Breakdown ── -->
  <div class="vat-section">
    <div class="vat-section-hdr">
      <h3>VAT Rate Breakdown</h3>
      <span style="font-size:11px;color:#94a3b8;"><?= count($byRate) ?> rate(s) applied this period</span>
    </div>
    <?php if (empty($byRate)): ?>
      <div class="vat-empty">No transactions recorded for <?= $monthNames[$month] . ' ' . $year ?>.</div>
    <?php else: ?>
    <div class="vat-tbl-wrap">
    <table class="vat-tbl">
      <thead>
        <tr>
          <th>VAT Rate</th>
          <th style="text-align:right;">Transactions</th>
          <th style="text-align:right;">Net (Excl. VAT)</th>
          <th style="text-align:right;">VAT Amount</th>
          <th style="text-align:right;">Gross (Incl. VAT)</th>
          <th style="text-align:right;">% of Total VAT</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $rTotNet = 0; $rTotVat = 0; $rTotGross = 0; $rTotTx = 0;
        foreach ($byRate as $r):
          $rRate  = (float)$r->VAT_RATE;
          $rNet   = (float)$r->NET_EXCL_VAT;
          $rVat   = (float)$r->VAT_AMOUNT;
          $rGross = (float)$r->GROSS_INCL_VAT;
          $rTx    = (int)$r->TRANSACTION_COUNT;
          $rTotNet += $rNet; $rTotVat += $rVat; $rTotGross += $rGross; $rTotTx += $rTx;
          $pct    = $sOutputVat > 0 ? round($rVat / $sOutputVat * 100, 1) : 0;
          $badgeBg = $rRate == 0 ? '#f0fdf4' : '#fef2f2';
          $badgeClr= $rRate == 0 ? '#16a34a' : '#dc2626';
        ?>
        <tr>
          <td>
            <span class="vat-rate-badge" style="background:<?= $badgeBg ?>;color:<?= $badgeClr ?>;">
              <?= $rRate ?>%
            </span>
            <?= $rRate == 0 ? '<span style="font-size:10px;color:#94a3b8;margin-left:4px;">Zero-Rated</span>' : '<span style="font-size:10px;color:#94a3b8;margin-left:4px;">Standard</span>' ?>
          </td>
          <td style="text-align:right;"><?= number_format($rTx) ?></td>
          <td style="text-align:right;"><?= vatFmt($rNet) ?></td>
          <td style="text-align:right;font-weight:700;color:#dc2626;"><?= vatFmt($rVat) ?></td>
          <td style="text-align:right;"><?= vatFmt($rGross) ?></td>
          <td style="text-align:right;">
            <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;">
              <div style="width:60px;height:5px;background:#f1f5f9;border-radius:3px;">
                <div style="width:<?= min(100,$pct) ?>%;height:100%;background:#3b82f6;border-radius:3px;"></div>
              </div>
              <span><?= $pct ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td>Total</td>
          <td style="text-align:right;"><?= number_format($rTotTx) ?></td>
          <td style="text-align:right;"><?= vatFmt($rTotNet) ?></td>
          <td style="text-align:right;color:#dc2626;"><?= vatFmt($rTotVat) ?></td>
          <td style="text-align:right;"><?= vatFmt($rTotGross) ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── Order Detail Table ── -->
  <div class="vat-section">
    <div class="vat-section-hdr">
      <h3>Transaction Detail</h3>
      <span style="font-size:11px;color:#94a3b8;"><?= count($orders) ?> order(s)</span>
    </div>
    <?php if (empty($orders)): ?>
      <div class="vat-empty">No orders found for <?= $monthNames[$month] . ' ' . $year ?>.</div>
    <?php else: ?>
    <div class="vat-tbl-wrap">
    <table class="vat-tbl" id="vatOrdersTbl">
      <thead>
        <tr>
          <th>#</th>
          <th>Order #</th>
          <th>Date</th>
          <th>Customer / Company</th>
          <th>Mode</th>
          <th>Pay Status</th>
          <th>Invoice #</th>
          <th>VAT #</th>
          <th style="text-align:right;">Net (Excl.VAT)</th>
          <th style="text-align:right;">VAT Rate(s)</th>
          <th style="text-align:right;">VAT Amt</th>
          <th style="text-align:right;">Shipping</th>
          <th style="text-align:right;">Total Billed</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $oTotNet = 0; $oTotVat = 0; $oTotShip = 0; $oTotBilled = 0;
        foreach ($orders as $idx => $o):
          $oNet    = (float)$o->FINAL_TOTAL_AMT - (float)$o->TAX_TOTAL_AMOUNT;
          $oVat    = (float)$o->TAX_TOTAL_AMOUNT;
          $oShip   = (float)$o->SHIPPING_AMT;
          $oBilled = (float)$o->FINAL_TOTAL_AMT;
          $oTotNet += $oNet; $oTotVat += $oVat; $oTotShip += $oShip; $oTotBilled += $oBilled;

          $payClr = match((string)$o->PAYMENT_STATUS) {
              'Payment Successful' => ['#dcfce7','#16a34a'],
              'Payment Pending'    => ['#fffbeb','#b45309'],
              'Payment Failed'     => ['#fee2e2','#dc2626'],
              default              => ['#f1f5f9','#64748b'],
          };
          $modeClr = match((string)$o->ORDER_MODE) {
              'Payment Gateway' => ['#ede9fe','#7c3aed'],
              'Bank Transfer'   => ['#dbeafe','#1d4ed8'],
              default           => ['#f0fdf4','#15803d'],
          };
          $rowBg = $oVat > 0 ? '' : 'background:#f0fdf4;';
        ?>
        <tr style="<?= $rowBg ?>">
          <td style="color:#94a3b8;"><?= $idx + 1 ?></td>
          <td>
            <a href="order-details?id=<?= EncryptURL('id='.(int)$o->USER_ORDER_ID) ?>"
               style="font-weight:700;color:#1e40af;font-size:11px;text-decoration:none;">
              <?= htmlspecialchars($o->ORDER_NUMBER ?? '') ?>
            </a>
          </td>
          <td style="white-space:nowrap;font-size:11px;">
            <?= $o->ORDER_DATE ? date('d M Y', strtotime($o->ORDER_DATE)) : '—' ?><br>
            <span style="color:#94a3b8;"><?= $o->ORDER_DATE ? date('H:i', strtotime($o->ORDER_DATE)) : '' ?></span>
          </td>
          <td>
            <div style="font-weight:600;font-size:12px;"><?= htmlspecialchars($o->CUST_NAME ?? '—') ?></div>
            <?php if (!empty($o->CUST_COMPANY)): ?>
              <div style="font-size:10px;color:#64748b;"><?= htmlspecialchars($o->CUST_COMPANY) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span style="background:<?= $modeClr[0] ?>;color:<?= $modeClr[1] ?>;font-size:10px;
                          font-weight:600;padding:2px 7px;border-radius:10px;">
              <?= htmlspecialchars($o->ORDER_MODE ?? '—') ?>
            </span>
          </td>
          <td>
            <span style="background:<?= $payClr[0] ?>;color:<?= $payClr[1] ?>;font-size:10px;
                          font-weight:600;padding:2px 7px;border-radius:10px;white-space:nowrap;">
              <?= htmlspecialchars($o->PAYMENT_STATUS ?? '—') ?>
            </span>
          </td>
          <td style="font-size:11px;color:#475569;"><?= htmlspecialchars($o->INVOICE_NO ?? '—') ?></td>
          <td style="font-size:11px;color:#475569;"><?= htmlspecialchars($o->VAT_NUMBER ?? '—') ?></td>
          <td style="text-align:right;font-weight:600;"><?= vatFmt($oNet) ?></td>
          <td style="text-align:right;">
            <?php if ($o->VAT_RATES): ?>
              <?php foreach (explode(', ', $o->VAT_RATES) as $vr): ?>
                <span class="vat-rate-badge" style="background:#fef2f2;color:#dc2626;"><?= htmlspecialchars($vr) ?></span>
              <?php endforeach; ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td style="text-align:right;font-weight:700;color:<?= $oVat > 0 ? '#dc2626' : '#94a3b8' ?>;">
            <?= vatFmt($oVat) ?>
          </td>
          <td style="text-align:right;color:#64748b;"><?= vatFmt($oShip) ?></td>
          <td style="text-align:right;font-weight:700;"><?= vatFmt($oBilled) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="8" style="text-align:right;">Totals</td>
          <td style="text-align:right;"><?= vatFmt($oTotNet) ?></td>
          <td></td>
          <td style="text-align:right;color:#dc2626;"><?= vatFmt($oTotVat) ?></td>
          <td style="text-align:right;"><?= vatFmt($oTotShip) ?></td>
          <td style="text-align:right;"><?= vatFmt($oTotBilled) ?></td>
        </tr>
      </tfoot>
    </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── Filing Declaration ── -->
  <?php if ($canEdit): ?>
  <div class="vat-filing-card no-print">
    <h3>📋 Filing Declaration — <?= $monthNames[$month] . ' ' . $year ?></h3>
    <form method="POST" action="service?urlstring=<?= EncryptURL('action=SaveVatFiling') ?>">
      <input type="hidden" name="filing_period" value="<?= $periodKey ?>">
      <input type="hidden" name="filing_type"   value="Monthly">
      <input type="hidden" name="output_vat"    value="<?= number_format($sOutputVat, 2, '.', '') ?>">
      <input type="hidden" name="input_vat"     value="0.00">
      <input type="hidden" name="net_sales"     value="<?= number_format($sNetExcl, 2, '.', '') ?>">

      <div class="vat-form-grid">
        <div>
          <label class="vf-label">Filing Status *</label>
          <select name="filing_status" class="vf-input vf-select" required>
            <?php foreach (['Draft','Filed','Overdue'] as $s): ?>
              <option value="<?= $s ?>" <?= ($filing && ($filing->FILING_STATUS ?? '') === $s) ? 'selected' : (!$filing && $s === 'Draft' ? 'selected' : '') ?>>
                <?= $s ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="vf-label">Tax Authority Reference #</label>
          <input type="text" name="reference_no" class="vf-input"
                 placeholder="e.g. VAT-2026-05-REF123"
                 value="<?= htmlspecialchars($filing->REFERENCE_NO ?? '') ?>">
        </div>
        <div>
          <label class="vf-label">Output VAT (auto-filled)</label>
          <input type="text" class="vf-input" value="<?= vatFmt($sOutputVat) ?>" readonly
                 style="background:#f8fafc;color:#64748b;">
        </div>
        <div class="vat-form-full">
          <label class="vf-label">Notes / Remarks</label>
          <textarea name="notes" class="vf-input" rows="2"
                    placeholder="Any notes for this VAT return period..."><?= htmlspecialchars($filing->NOTES ?? '') ?></textarea>
        </div>
      </div>

      <?php if ($filing && !empty($filing->FILED_AT)): ?>
        <div style="font-size:11px;color:#16a34a;margin-bottom:12px;">
          ✓ Last filed on <?= date('d M Y H:i', strtotime($filing->FILED_AT)) ?>
          <?php if (!empty($filing->REFERENCE_NO)): ?> · Ref: <strong><?= htmlspecialchars($filing->REFERENCE_NO) ?></strong><?php endif; ?>
        </div>
      <?php endif; ?>

      <div style="display:flex;gap:10px;align-items:center;">
        <button type="submit" class="vat-btn vat-btn-green">💾 Save Filing Record</button>
        <span style="font-size:11px;color:#94a3b8;">
          Net VAT for this period: <strong style="color:#dc2626;"><?= vatFmt($sNetVat) ?></strong>
        </span>
      </div>
    </form>
  </div>
  <?php endif; ?>

<?php /* ════════════════════════════════════════════════════
         YEARLY VIEW
════════════════════════════════════════════════════ */ else: ?>

  <?php
  $yOrders  = (float)($totals->ORDER_COUNT   ?? 0);
  $yGross   = (float)($totals->GROSS_SALES   ?? 0);
  $yVat     = (float)($totals->OUTPUT_VAT    ?? 0);
  $yNet     = (float)($totals->NET_EXCL_VAT  ?? 0);
  $yBilled  = (float)($totals->TOTAL_BILLED  ?? 0);
  $yCollect = (float)($totals->COLLECTED     ?? 0);
  $yDisc    = (float)($totals->DISCOUNTS     ?? 0);
  $yShip    = (float)($totals->SHIPPING      ?? 0);

  /* Build quarterly aggregates from monthly data */
  $qtrs = [1=>['net'=>0,'vat'=>0,'orders'=>0,'billed'=>0], 2=>['net'=>0,'vat'=>0,'orders'=>0,'billed'=>0],
           3=>['net'=>0,'vat'=>0,'orders'=>0,'billed'=>0], 4=>['net'=>0,'vat'=>0,'orders'=>0,'billed'=>0]];
  $monthlyMap = [];
  foreach ($monthly as $row) {
      $mn = (int)$row->MONTH_NUM;
      $monthlyMap[$mn] = $row;
      $q = (int)ceil($mn / 3);
      $qtrs[$q]['net']    += (float)($row->NET_EXCL_VAT ?? 0);
      $qtrs[$q]['vat']    += (float)($row->OUTPUT_VAT   ?? 0);
      $qtrs[$q]['orders'] += (int)($row->ORDER_COUNT    ?? 0);
      $qtrs[$q]['billed'] += (float)($row->TOTAL_BILLED ?? 0);
  }
  $qtrMonths = [1=>'Jan–Mar', 2=>'Apr–Jun', 3=>'Jul–Sep', 4=>'Oct–Dec'];
  $qtrColors = [1=>'#3b82f6', 2=>'#8b5cf6', 3=>'#f59e0b', 4=>'#16a34a'];
  ?>

  <!-- ── Annual KPI Strip ── -->
  <div class="vat-kpi" style="grid-template-columns:repeat(6,1fr);">
    <div class="vat-kpi-card" style="border-top:3px solid #3b82f6;">
      <div class="lbl">Total Orders</div>
      <div class="val"><?= number_format($yOrders) ?></div>
      <div class="sub"><?= $year ?> full year</div>
    </div>
    <div class="vat-kpi-card" style="border-top:3px solid #8b5cf6;">
      <div class="lbl">Total Billed</div>
      <div class="val" style="font-size:13px;"><?= vatFmt($yBilled) ?></div>
      <div class="sub">Incl. VAT</div>
    </div>
    <div class="vat-kpi-card" style="border-top:3px solid #0891b2;">
      <div class="lbl">Net Sales</div>
      <div class="val" style="font-size:13px;"><?= vatFmt($yNet) ?></div>
      <div class="sub">Excl. VAT (Box 6)</div>
    </div>
    <div class="vat-kpi-card" style="border-top:3px solid #dc2626;">
      <div class="lbl">Annual Output VAT</div>
      <div class="val" style="font-size:13px;color:#dc2626;"><?= vatFmt($yVat) ?></div>
      <div class="sub">Box 1 full year</div>
    </div>
    <div class="vat-kpi-card" style="border-top:3px solid #16a34a;">
      <div class="lbl">Collected</div>
      <div class="val" style="font-size:13px;color:#16a34a;"><?= vatFmt($yCollect) ?></div>
      <div class="sub">Payment Successful</div>
    </div>
    <div class="vat-kpi-card" style="border-top:3px solid #f97316;">
      <div class="lbl">Total Discounts</div>
      <div class="val" style="font-size:13px;"><?= vatFmt($yDisc) ?></div>
      <div class="sub">Applied this year</div>
    </div>
  </div>

  <!-- ── Quarterly Cards ── -->
  <div class="vat-year-kpi">
    <?php foreach ([1,2,3,4] as $q): ?>
    <div class="vat-qtr-card" style="border-top:3px solid <?= $qtrColors[$q] ?>;">
      <h4>Q<?= $q ?> — <?= $qtrMonths[$q] ?></h4>
      <div class="qval"><?= vatFmt($qtrs[$q]['billed']) ?></div>
      <div class="qsub"><?= number_format($qtrs[$q]['orders']) ?> orders · Net <?= vatFmt($qtrs[$q]['net']) ?></div>
      <div class="qvat">VAT: <?= vatFmt($qtrs[$q]['vat']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Monthly Breakdown Table ── -->
  <div class="vat-section">
    <div class="vat-section-hdr">
      <h3><?= $year ?> — Month-by-Month VAT Breakdown</h3>
      <span style="font-size:11px;color:#94a3b8;">Click a month to view full return</span>
    </div>
    <div class="vat-tbl-wrap">
    <table class="vat-tbl">
      <thead>
        <tr>
          <th>Month</th>
          <th>Quarter</th>
          <th style="text-align:right;">Orders</th>
          <th style="text-align:right;">Net Sales</th>
          <th style="text-align:right;">Output VAT</th>
          <th style="text-align:right;">Total Billed</th>
          <th style="text-align:right;">Collected</th>
          <th style="text-align:center;">Filing Status</th>
          <th style="text-align:center;">Reference #</th>
          <th style="text-align:center;" class="no-print">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $qBorder = [1=>'#bfdbfe', 2=>'#ddd6fe', 3=>'#fde68a', 4=>'#bbf7d0'];
        for ($m = 1; $m <= 12; $m++):
          $row  = $monthlyMap[$m]  ?? null;
          $mFil = $filingMap[$m]   ?? null;
          $q    = (int)ceil($m / 3);
          $mNet = $row ? (float)$row->NET_EXCL_VAT  : 0;
          $mVat = $row ? (float)$row->OUTPUT_VAT    : 0;
          $mBil = $row ? (float)$row->TOTAL_BILLED  : 0;
          $mCol = $row ? (float)$row->COLLECTED     : 0;
          $mOrd = $row ? (int)$row->ORDER_COUNT      : 0;
          [$rowBg, $rowBorder, $rowTxt] = monthFilingColor($mFil);
          $isFuture = ($year > (int)date('Y')) || ($year == (int)date('Y') && $m > (int)date('n'));
        ?>
        <tr style="background:<?= $rowBg ?>;border-left:3px solid <?= $rowBorder ?>;">
          <td style="font-weight:700;">
            <a href="monthly-vat-filling-document?view=monthly&month=<?= $m ?>&year=<?= $year ?>"
               style="color:#1e40af;text-decoration:none;">
              <?= $monthNames[$m] ?>
            </a>
          </td>
          <td>
            <span style="font-size:10px;font-weight:600;color:<?= $qtrColors[$q] ?>;">Q<?= $q ?></span>
          </td>
          <td style="text-align:right;"><?= $mOrd > 0 ? number_format($mOrd) : '—' ?></td>
          <td style="text-align:right;"><?= $mNet > 0 ? vatFmt($mNet) : '—' ?></td>
          <td style="text-align:right;font-weight:700;color:<?= $mVat > 0 ? '#dc2626' : '#94a3b8' ?>;">
            <?= $mVat > 0 ? vatFmt($mVat) : '—' ?>
          </td>
          <td style="text-align:right;"><?= $mBil > 0 ? vatFmt($mBil) : '—' ?></td>
          <td style="text-align:right;color:#16a34a;"><?= $mCol > 0 ? vatFmt($mCol) : '—' ?></td>
          <td style="text-align:center;"><?= vatStatusBadge($mFil, true) ?></td>
          <td style="text-align:center;font-size:11px;color:#475569;">
            <?= $mFil ? htmlspecialchars($mFil->REFERENCE_NO ?? '—') : '—' ?>
          </td>
          <td style="text-align:center;" class="no-print">
            <a href="monthly-vat-filling-document?view=monthly&month=<?= $m ?>&year=<?= $year ?>"
               class="vat-btn vat-btn-outline" style="padding:4px 10px;font-size:10px;">
              <?= $isFuture ? '📅 View' : '📋 View/File' ?>
            </a>
          </td>
        </tr>
        <?php endfor; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2"><strong>Annual Total</strong></td>
          <td style="text-align:right;"><strong><?= number_format($yOrders) ?></strong></td>
          <td style="text-align:right;"><strong><?= vatFmt($yNet) ?></strong></td>
          <td style="text-align:right;color:#dc2626;"><strong><?= vatFmt($yVat) ?></strong></td>
          <td style="text-align:right;"><strong><?= vatFmt($yBilled) ?></strong></td>
          <td style="text-align:right;color:#16a34a;"><strong><?= vatFmt($yCollect) ?></strong></td>
          <td colspan="3"></td>
        </tr>
      </tfoot>
    </table>
    </div>
  </div>

  <!-- ── Annual VAT Rate Breakdown ── -->
  <?php if (!empty($byRate)): ?>
  <div class="vat-section">
    <div class="vat-section-hdr">
      <h3>Annual VAT by Rate — <?= $year ?></h3>
    </div>
    <div class="vat-tbl-wrap">
    <table class="vat-tbl">
      <thead>
        <tr>
          <th>VAT Rate</th>
          <th style="text-align:right;">Transactions</th>
          <th style="text-align:right;">Net (Excl. VAT)</th>
          <th style="text-align:right;">VAT Collected</th>
          <th style="text-align:right;">Gross (Incl. VAT)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($byRate as $r):
          $rRate = (float)$r->VAT_RATE;
          $badgeBg  = $rRate == 0 ? '#f0fdf4' : '#fef2f2';
          $badgeClr = $rRate == 0 ? '#16a34a' : '#dc2626';
        ?>
        <tr>
          <td>
            <span class="vat-rate-badge" style="background:<?= $badgeBg ?>;color:<?= $badgeClr ?>;"><?= $rRate ?>%</span>
            <?= $rRate == 0 ? '<span style="font-size:10px;color:#94a3b8;margin-left:4px;">Zero-Rated</span>' : '<span style="font-size:10px;color:#94a3b8;margin-left:4px;">Standard</span>' ?>
          </td>
          <td style="text-align:right;"><?= number_format((int)$r->TRANSACTION_COUNT) ?></td>
          <td style="text-align:right;"><?= vatFmt((float)$r->NET_EXCL_VAT) ?></td>
          <td style="text-align:right;font-weight:700;color:#dc2626;"><?= vatFmt((float)$r->VAT_AMOUNT) ?></td>
          <td style="text-align:right;"><?= vatFmt((float)$r->GROSS_INCL_VAT) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td>Total</td>
          <td style="text-align:right;"><?= number_format(array_sum(array_map(fn($r)=>(int)$r->TRANSACTION_COUNT,$byRate))) ?></td>
          <td style="text-align:right;"><?= vatFmt(array_sum(array_map(fn($r)=>(float)$r->NET_EXCL_VAT,$byRate))) ?></td>
          <td style="text-align:right;color:#dc2626;"><?= vatFmt(array_sum(array_map(fn($r)=>(float)$r->VAT_AMOUNT,$byRate))) ?></td>
          <td style="text-align:right;"><?= vatFmt(array_sum(array_map(fn($r)=>(float)$r->GROSS_INCL_VAT,$byRate))) ?></td>
        </tr>
      </tfoot>
    </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Annual Filing Declaration ── -->
  <?php if ($canEdit): ?>
  <div class="vat-filing-card no-print">
    <h3>📋 Annual VAT Filing Declaration — <?= $year ?></h3>
    <p style="font-size:12px;color:#64748b;margin:0 0 16px;">
      Use this section to record your annual VAT submission to the tax authority.
      This is separate from the individual monthly returns.
    </p>
    <form method="POST" action="service?urlstring=<?= EncryptURL('action=SaveVatFiling') ?>">
      <input type="hidden" name="filing_period" value="<?= $year ?>">
      <input type="hidden" name="filing_type"   value="Yearly">
      <input type="hidden" name="output_vat"    value="<?= number_format($yVat, 2, '.', '') ?>">
      <input type="hidden" name="input_vat"     value="0.00">
      <input type="hidden" name="net_sales"     value="<?= number_format($yNet, 2, '.', '') ?>">

      <div class="vat-form-grid">
        <div>
          <label class="vf-label">Annual Filing Status *</label>
          <select name="filing_status" class="vf-input vf-select" required>
            <?php foreach (['Draft','Filed','Overdue'] as $s): ?>
              <option value="<?= $s ?>" <?= ($annualFiling && ($annualFiling->FILING_STATUS ?? '') === $s) ? 'selected' : (!$annualFiling && $s === 'Draft' ? 'selected' : '') ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="vf-label">Tax Authority Reference #</label>
          <input type="text" name="reference_no" class="vf-input"
                 placeholder="e.g. ANNUAL-VAT-<?= $year ?>-REF"
                 value="<?= htmlspecialchars($annualFiling->REFERENCE_NO ?? '') ?>">
        </div>
        <div>
          <label class="vf-label">Annual Output VAT (auto)</label>
          <input type="text" class="vf-input" value="<?= vatFmt($yVat) ?>" readonly
                 style="background:#f8fafc;color:#64748b;">
        </div>
        <div class="vat-form-full">
          <label class="vf-label">Notes / Remarks</label>
          <textarea name="notes" class="vf-input" rows="2"
                    placeholder="Notes for the annual VAT return..."><?= htmlspecialchars($annualFiling->NOTES ?? '') ?></textarea>
        </div>
      </div>

      <?php if ($annualFiling && !empty($annualFiling->FILED_AT)): ?>
        <div style="font-size:11px;color:#16a34a;margin-bottom:12px;">
          ✓ Annual return filed on <?= date('d M Y H:i', strtotime($annualFiling->FILED_AT)) ?>
          <?php if (!empty($annualFiling->REFERENCE_NO)): ?> · Ref: <strong><?= htmlspecialchars($annualFiling->REFERENCE_NO) ?></strong><?php endif; ?>
        </div>
      <?php endif; ?>

      <div style="display:flex;gap:10px;align-items:center;">
        <button type="submit" class="vat-btn vat-btn-green">💾 Save Annual Filing</button>
        <span style="font-size:11px;color:#94a3b8;">
          Annual Net VAT: <strong style="color:#dc2626;"><?= vatFmt($yVat) ?></strong>
        </span>
      </div>
    </form>
  </div>
  <?php endif; ?>

<?php endif; /* end yearly view */ ?>

</div><!-- .vat-wrap -->

<script>
/* CSV Export */
function vatExportCsv() {
  const tbl = document.getElementById('vatOrdersTbl');
  if (!tbl) { alert('No transaction data to export.'); return; }
  let csv = [];
  tbl.querySelectorAll('tr').forEach(row => {
    let cols = [...row.querySelectorAll('th,td')].map(c =>
      '"' + c.innerText.replace(/"/g,'""').trim() + '"'
    );
    csv.push(cols.join(','));
  });
  const blob = new Blob([csv.join('\n')], {type:'text/csv'});
  const a    = document.createElement('a');
  a.href     = URL.createObjectURL(blob);
  a.download = 'vat-<?= $view === 'yearly' ? $year : $periodKey ?>.csv';
  a.click();
}
</script>
<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
