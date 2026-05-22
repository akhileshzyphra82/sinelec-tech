<?php
/**
 * Order Invoice View / Print
 * Standalone printable invoice page (no admin chrome).
 * URL: admin/order-invoice?id=123
 *
 * Auth:  admin session required.
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

/* ── 1. Auth ── */
$isAdmin = !empty($_SESSION['sinelec_admin']['USER_ID']);
if (!$isAdmin) {
    header('location:login'); exit();
}

/* ── 2. Param validation ── */
$oid = (int)($_GET['id'] ?? 0);
if ($oid <= 0) {
    http_response_code(400);
    echo '<p style="font-family:sans-serif;padding:40px;color:#dc2626;">Invalid order ID.</p>'; exit();
}

$controller = new AdminController();
$order      = $controller->getUserOrderById($oid);

if (!$order) {
    http_response_code(404);
    echo '<p style="font-family:sans-serif;padding:40px;color:#dc2626;">Order not found.</p>'; exit();
}

$items   = $controller->getUserOrderItems($oid);
$history = $controller->getUserOrderHistory($oid);

/* ── 3. Company details ── */
$co      = $controller->getCompanyDetails();
$coName  = $co ? trim((string)($co->NAME           ?? '')) : '';
$coEmail = $co ? trim((string)($co->EMAIL          ?? '')) : '';
$coPhone = $co ? trim((string)($co->CONTACT_NUMBER ?? '')) : '';
$coAddr  = $co ? trim((string)($co->ADDRESS        ?? '')) : '';
$coLogo  = $co ? trim((string)($co->LOGO           ?? '')) : '';

/* ── 4. Order fields ── */
$orderNo    = (string)($order->ORDER_NUMBER      ?? 'N/A');
$orderDate  = (string)($order->ORDER_DATE        ?? '');
$oStatus    = (string)($order->ORDER_STATUS      ?? '');
$pStatus    = (string)($order->PAYMENT_STATUS    ?? '');
$orderMode  = (string)($order->ORDER_MODE        ?? '');
$custPoId   = (string)($order->CUSTOMER_PO_ID    ?? '');
$custSupNo  = (string)($order->CUSTOMER_SUPPLIER_NO ?? '');
$quoteId    = (int)(float)($order->ENQUIRY_QUOTE_ID ?? 0);
$orderYear  = (string)($order->ORDER_YEAR        ?? date('Y', strtotime($orderDate ?: 'now')));

/* ── 5. Customer fields ── */
$custName    = (string)($order->CUST_NAME    ?? '');
$custEmail   = (string)($order->CUST_EMAIL   ?? '');
$custPhone   = (string)($order->CUST_PHONE   ?? '');
$custCompany = (string)($order->CUST_COMPANY ?? '');

/* ── 6. Delivery address ── */
$delLine1   = trim((string)($order->ADDRESS_LINE_ONE ?? ''));
$delLine2   = trim((string)($order->ADDRESS_LINE_TWO ?? ''));
if ($delLine1 === '' && $delLine2 === '') $delLine1 = trim((string)($order->ADDRESS ?? ''));
$delCity    = trim((string)($order->CITY             ?? ''));
$delState   = trim((string)($order->STATE            ?? ''));
$delZip     = trim((string)($order->ZIP              ?? ''));
$delCountry = trim((string)($order->COUNTRY          ?? ''));
$delPhone   = trim((string)($order->DELIVERY_PHONE_NO ?? ''));
$rcptName   = trim((string)($order->RECIPIENT_NAME   ?? ''));
$rcptEmail  = trim((string)($order->RECIPIENT_EMAIL  ?? ''));
$addrCompany= trim((string)($order->ADDR_COMPANY     ?? ''));

/* ── 7. Financials ── */
$subTotal  = 0;
foreach ($items as $it) {
    $subTotal += (float)($it->FINAL_AMT ?? 0);
}
$shipAmt   = (float)($order->SHIPPING_AMT       ?? 0);
$discAmt   = (float)($order->DISCOUNT_AMT       ?? 0);
$taxAmt    = (float)($order->TAX_TOTAL_AMOUNT   ?? 0);
$finalAmt  = (float)($order->FINAL_TOTAL_AMT    ?? 0);
if ($finalAmt <= 0) $finalAmt = $subTotal + $shipAmt - $discAmt + $taxAmt;

/* ── 8. Date formatting ── */
$orderDateFmt = $orderDate ? date('d M Y', strtotime($orderDate)) : '—';
$orderTimeFmt = $orderDate ? date('H:i', strtotime($orderDate)) : '';

/* ── 9. Status colours ── */
$oStatusColor = match($oStatus) {
    'Order Pending'     => '#d97706',
    'Order Confirmed'   => '#2563eb',
    'Order Packed'      => '#7c3aed',
    'Order Dispatch'    => '#0891b2',
    'Order In Transit'  => '#0d9488',
    'Order Delivered'   => '#16a34a',
    'Order Cancelled'   => '#dc2626',
    default             => '#64748b',
};
$pStatusColor = match($pStatus) {
    'Payment Pending'   => '#d97706',
    'Payment Successful'=> '#16a34a',
    'Payment Failed'    => '#dc2626',
    'Refund Initiated'  => '#7c3aed',
    'Refund Completed'  => '#0891b2',
    'Not Required'      => '#64748b',
    default             => '#64748b',
};

/* ── 10. Source label ── */
$sourceLabel = ($quoteId > 0) ? 'Quote #'.$quoteId : 'Direct Order';
$sourceIcon  = ($quoteId > 0)
    ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'
    : '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';

/* ── 11. Check if any items have discount/tax ── */
$hasDisc = false; $hasTax = false;
foreach ($items as $it) {
    if ((float)($it->DISCOUNT_PERCENTAGE ?? 0) > 0) $hasDisc = true;
    if ((float)($it->TAX_PERCENTAGE      ?? 0) > 0) $hasTax  = true;
}

/* ── helper: render address block ── */
function inv_addr(string $company, string $line1, string $line2, string $city, string $state, string $zip, string $country, string $phone): string {
    $parts = [];
    if ($company)                         $parts[] = '<strong>'.htmlspecialchars($company).'</strong>';
    if ($line1)                           $parts[] = htmlspecialchars($line1);
    if ($line2)                           $parts[] = htmlspecialchars($line2);
    $cityPart = trim($city . ($state ? ', '.$state : '') . ($zip ? ' '.$zip : ''));
    if ($cityPart)                        $parts[] = htmlspecialchars($cityPart);
    if ($country)                         $parts[] = htmlspecialchars($country);
    if ($phone)                           $parts[] = 'Tel: '.htmlspecialchars($phone);
    return implode('<br>', $parts);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice <?= htmlspecialchars($orderNo) ?><?= $coName ? ' — '.htmlspecialchars($coName) : '' ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px;
    color: #1e293b;
    background: #f1f5f9;
    padding: 24px 16px;
  }

  /* ── Action bar ── */
  .no-print {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    align-items: center;
    max-width: 900px;
    margin: 0 auto 16px;
    flex-wrap: wrap;
  }

  .btn-action {
    padding: 8px 18px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: background .15s;
  }
  .btn-print  { background: #2563eb; color: #fff; }
  .btn-print:hover  { background: #1d4ed8; }
  .btn-back   { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
  .btn-back:hover   { background: #f8fafc; }
  .btn-status { background: #f0fdf4; color: #16a34a; border: 1px solid #86efac; }
  .btn-status:hover { background: #dcfce7; }

  /* ── Paper ── */
  .paper {
    background: #fff;
    max-width: 900px;
    margin: 0 auto;
    border-radius: 10px;
    box-shadow: 0 4px 32px rgba(0,0,0,.10);
    overflow: hidden;
  }

  /* ── Header band ── */
  .inv-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: #fff;
    padding: 32px 40px 28px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
  }
  .inv-logo-wrap { display: flex; align-items: center; gap: 14px; }
  .inv-logo-img  { height: 50px; max-width: 140px; object-fit: contain; filter: brightness(0) invert(1); }
  .inv-co-name   { font-size: 24px; font-weight: 800; letter-spacing: .06em; color: #fff; }
  .inv-co-sub    { font-size: 11px; color: rgba(255,255,255,.7); margin-top: 3px; }
  .inv-ref-block { text-align: right; flex-shrink: 0; }
  .inv-doc-title { font-size: 11px; color: rgba(255,255,255,.65); text-transform: uppercase; letter-spacing: .1em; }
  .inv-ref-num   { font-size: 26px; font-weight: 800; color: #fff; line-height: 1.2; margin: 3px 0; }
  .inv-ref-date  { font-size: 12px; color: rgba(255,255,255,.75); }
  .inv-status-row { display: flex; gap: 6px; justify-content: flex-end; margin-top: 8px; flex-wrap: wrap; }
  .inv-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    background: rgba(255,255,255,.18);
    color: #fff;
    letter-spacing: .04em;
  }

  /* ── Info strip (source / mode / PO) ── */
  .inv-strip {
    display: flex;
    gap: 0;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
  }
  .inv-strip-item {
    padding: 12px 22px;
    border-right: 1px solid #e2e8f0;
    flex: 1;
    min-width: 120px;
  }
  .inv-strip-item:last-child { border-right: none; }
  .inv-strip-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #94a3b8;
    font-weight: 600;
    margin-bottom: 3px;
  }
  .inv-strip-val {
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 5px;
  }

  /* ── Address grid ── */
  .inv-addr-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-bottom: 1px solid #e2e8f0;
  }
  .inv-addr-block {
    padding: 22px 28px;
    border-right: 1px solid #e2e8f0;
  }
  .inv-addr-block:last-child { border-right: none; }
  .inv-addr-title {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 700;
    color: #94a3b8;
    margin-bottom: 8px;
  }
  .inv-addr-name {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
  }
  .inv-addr-line {
    font-size: 12px;
    color: #475569;
    line-height: 1.65;
  }

  /* ── Items table ── */
  .inv-table-wrap { padding: 0 28px 8px; }
  .inv-section-title {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 700;
    color: #94a3b8;
    padding: 18px 0 10px;
  }
  .inv-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
  }
  .inv-table thead th {
    background: #f1f5f9;
    color: #64748b;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .07em;
    font-weight: 700;
    padding: 9px 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
  }
  .inv-table thead th:first-child { border-radius: 6px 0 0 0; }
  .inv-table thead th:last-child  { border-radius: 0 6px 0 0; text-align: right; }
  .inv-table tbody td {
    padding: 11px 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
  }
  .inv-table tbody tr:last-child td { border-bottom: none; }
  .inv-table tbody tr:hover td { background: #fafbfc; }
  .item-name   { font-weight: 600; color: #1e293b; font-size: 13px; }
  .item-code   { font-size: 10px; color: #94a3b8; margin-top: 2px; }
  .item-cat    { font-size: 10px; color: #64748b; margin-top: 1px; }
  .num-right   { text-align: right; }
  .num-muted   { color: #64748b; }
  .num-disc    { color: #dc2626; font-size: 11px; }
  .num-tax     { color: #7c3aed; font-size: 11px; }
  .num-total   { font-weight: 700; color: #1e293b; }

  /* ── Totals ── */
  .inv-totals {
    display: flex;
    justify-content: flex-end;
    padding: 0 28px 24px;
  }
  .inv-totals-table {
    width: 300px;
    border-collapse: collapse;
    font-size: 13px;
  }
  .inv-totals-table td {
    padding: 6px 10px;
    color: #475569;
  }
  .inv-totals-table td:last-child { text-align: right; font-weight: 600; color: #1e293b; }
  .inv-totals-table tr.subtotal td { border-top: 1px solid #f1f5f9; }
  .inv-totals-table tr.grand td {
    border-top: 2px solid #e2e8f0;
    font-size: 15px;
    font-weight: 800;
    color: #1e293b;
    padding-top: 10px;
  }
  .inv-totals-table tr.disc td:last-child { color: #dc2626; }
  .inv-totals-table tr.ship td:last-child { color: #2563eb; }
  .inv-totals-table tr.tax td:last-child  { color: #7c3aed; }

  /* ── Timeline / History ── */
  .inv-history { padding: 20px 28px 28px; border-top: 1px solid #f1f5f9; }
  .inv-history-title {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 700;
    color: #94a3b8;
    margin-bottom: 16px;
  }
  .timeline { position: relative; padding-left: 24px; }
  .timeline::before {
    content: '';
    position: absolute;
    left: 7px; top: 0; bottom: 0;
    width: 2px;
    background: #e2e8f0;
  }
  .tl-item { position: relative; margin-bottom: 16px; }
  .tl-dot {
    position: absolute;
    left: -20px;
    top: 3px;
    width: 12px; height: 12px;
    border-radius: 50%;
    background: #2563eb;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e2e8f0;
  }
  .tl-dot.delivered { background: #16a34a; }
  .tl-dot.cancelled { background: #dc2626; }
  .tl-dot.pending   { background: #d97706; }
  .tl-meta { font-size: 11px; color: #94a3b8; margin-bottom: 2px; }
  .tl-status { font-size: 12px; font-weight: 700; color: #1e293b; }
  .tl-pay    { font-size: 11px; color: #64748b; margin-top: 1px; }
  .tl-remark { font-size: 11px; color: #475569; font-style: italic; margin-top: 2px; }

  /* ── Footer ── */
  .inv-footer {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 18px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }
  .inv-footer-left  { font-size: 11px; color: #64748b; line-height: 1.6; }
  .inv-footer-right { font-size: 11px; color: #94a3b8; text-align: right; }

  /* ── Print media ── */
  @media print {
    body { background: #fff; padding: 0; }
    .no-print { display: none !important; }
    .paper { box-shadow: none; border-radius: 0; max-width: 100%; }
    .inv-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .inv-strip  { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .inv-table thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .tl-dot { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @page { margin: 10mm; }
  }
</style>
</head>
<body>

<!-- ── Action bar (screen only) ── -->
<div class="no-print">
  <a href="order-list" class="btn-action btn-back">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Orders
  </a>
  <button class="btn-action btn-print" onclick="window.print()">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Print / Save PDF
  </button>
</div>

<!-- ── Invoice Paper ── -->
<div class="paper">

  <!-- Header -->
  <div class="inv-header">
    <div class="inv-logo-wrap">
      <?php if ($coLogo): ?>
        <img src="<?= htmlspecialchars($coLogo) ?>" alt="<?= htmlspecialchars($coName) ?>" class="inv-logo-img">
      <?php endif; ?>
      <div>
        <div class="inv-co-name"><?= htmlspecialchars($coName ?: 'Company') ?></div>
        <?php if ($coAddr): ?><div class="inv-co-sub"><?= htmlspecialchars($coAddr) ?></div><?php endif; ?>
        <?php if ($coEmail || $coPhone): ?>
        <div class="inv-co-sub"><?= implode(' | ', array_filter([htmlspecialchars($coEmail), htmlspecialchars($coPhone)])) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="inv-ref-block">
      <div class="inv-doc-title">Tax Invoice</div>
      <div class="inv-ref-num"><?= htmlspecialchars($orderNo) ?></div>
      <div class="inv-ref-date">
        <?= $orderDateFmt ?>
        <?php if ($orderTimeFmt): ?>&nbsp;·&nbsp;<?= $orderTimeFmt ?><?php endif; ?>
      </div>
      <div class="inv-status-row">
        <span class="inv-badge" style="background:<?= $oStatusColor ?>cc;"><?= htmlspecialchars($oStatus) ?></span>
        <span class="inv-badge" style="background:<?= $pStatusColor ?>cc;"><?= htmlspecialchars($pStatus) ?></span>
      </div>
    </div>
  </div>

  <!-- Info strip -->
  <div class="inv-strip">
    <div class="inv-strip-item">
      <div class="inv-strip-label">Source</div>
      <div class="inv-strip-val">
        <?= $sourceIcon ?>
        <?= htmlspecialchars($sourceLabel) ?>
      </div>
    </div>
    <div class="inv-strip-item">
      <div class="inv-strip-label">Payment Method</div>
      <div class="inv-strip-val">
        <?php
        $modeIcon = match($orderMode) {
            'Invoice'          => '📄',
            'Bank Transfer'    => '🏦',
            'Payment Gateway'  => '💳',
            default            => '—',
        };
        ?>
        <?= $modeIcon ?>&nbsp;<?= htmlspecialchars($orderMode ?: '—') ?>
      </div>
    </div>
    <?php if ($custPoId): ?>
    <div class="inv-strip-item">
      <div class="inv-strip-label">Customer PO</div>
      <div class="inv-strip-val"><?= htmlspecialchars($custPoId) ?></div>
    </div>
    <?php endif; ?>
    <?php if ($custSupNo): ?>
    <div class="inv-strip-item">
      <div class="inv-strip-label">Supplier No.</div>
      <div class="inv-strip-val"><?= htmlspecialchars($custSupNo) ?></div>
    </div>
    <?php endif; ?>
    <?php if ($quoteId > 0): ?>
    <div class="inv-strip-item">
      <div class="inv-strip-label">From Quotation</div>
      <div class="inv-strip-val">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <span style="color:#7c3aed;">Quote #<?= $quoteId ?></span>
      </div>
    </div>
    <?php endif; ?>
    <div class="inv-strip-item">
      <div class="inv-strip-label">Items</div>
      <div class="inv-strip-val"><?= count($items) ?> line<?= count($items) !== 1 ? 's' : '' ?></div>
    </div>
  </div>

  <!-- Addresses -->
  <div class="inv-addr-grid">
    <!-- Bill From (Company) -->
    <div class="inv-addr-block">
      <div class="inv-addr-title">Bill From</div>
      <div class="inv-addr-name"><?= htmlspecialchars($coName ?: 'Company') ?></div>
      <div class="inv-addr-line">
        <?php
        $fromParts = array_filter([$coAddr, $coEmail, $coPhone]);
        echo implode('<br>', array_map('htmlspecialchars', $fromParts));
        ?>
      </div>
    </div>
    <!-- Bill To (Customer) -->
    <div class="inv-addr-block">
      <div class="inv-addr-title">Bill To</div>
      <div class="inv-addr-name"><?= htmlspecialchars($custName ?: 'Customer') ?></div>
      <div class="inv-addr-line">
        <?php if ($custCompany): ?><strong><?= htmlspecialchars($custCompany) ?></strong><br><?php endif; ?>
        <?php
        $custParts = array_filter([$custEmail, $custPhone]);
        echo implode('<br>', array_map('htmlspecialchars', $custParts));
        ?>
      </div>
    </div>
  </div>

  <!-- Delivery address (if present) -->
  <?php if ($delLine1 || $rcptName): ?>
  <div style="padding:0 28px 0;border-bottom:1px solid #e2e8f0;">
    <div style="padding:16px 0 14px;">
      <div class="inv-addr-title">Delivery / Shipping Address</div>
      <div class="inv-addr-line">
        <?php
        $delParts = [];
        if ($rcptName)   $delParts[] = '<strong>'.htmlspecialchars($rcptName).'</strong>';
        if ($addrCompany)$delParts[] = htmlspecialchars($addrCompany);
        if ($delLine1)   $delParts[] = htmlspecialchars($delLine1);
        if ($delLine2)   $delParts[] = htmlspecialchars($delLine2);
        $c = trim($delCity.($delState ? ', '.$delState : '').($delZip ? ' '.$delZip : ''));
        if ($c)          $delParts[] = htmlspecialchars($c);
        if ($delCountry) $delParts[] = htmlspecialchars($delCountry);
        if ($delPhone)   $delParts[] = 'Tel: '.htmlspecialchars($delPhone);
        if ($rcptEmail)  $delParts[] = htmlspecialchars($rcptEmail);
        echo implode('<br>', $delParts);
        ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Items table -->
  <div class="inv-table-wrap">
    <div class="inv-section-title">Order Items</div>
    <?php if (empty($items)): ?>
      <div style="padding:20px 0;text-align:center;color:#94a3b8;font-size:13px;">No items found for this order.</div>
    <?php else: ?>
    <table class="inv-table">
      <thead>
        <tr>
          <th style="width:36px;">#</th>
          <th>Product</th>
          <th style="text-align:right;width:70px;">Qty</th>
          <th style="text-align:right;width:100px;">Unit Price</th>
          <?php if ($hasDisc): ?><th style="text-align:right;width:90px;">Discount</th><?php endif; ?>
          <?php if ($hasTax):  ?><th style="text-align:right;width:80px;">Tax</th><?php endif; ?>
          <th style="text-align:right;width:100px;">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $it):
          $prodName = htmlspecialchars((string)($it->PRODUCT_NAME          ?? '—'));
          $prodCode = htmlspecialchars((string)($it->PRODUCT_CODE          ?? ''));
          $catName  = htmlspecialchars((string)($it->PRODUCT_CATEGORY_NAME ?? ''));
          $qty      = (float)($it->QUANTITY            ?? 0);
          $unitAmt  = (float)($it->PRODUCT_AMT         ?? 0);
          $discPct  = (float)($it->DISCOUNT_PERCENTAGE ?? 0);
          $taxPct   = (float)($it->TAX_PERCENTAGE      ?? 0);
          $finalAmt = (float)($it->FINAL_AMT            ?? 0);
        ?>
        <tr>
          <td style="color:#94a3b8;font-size:11px;"><?= $i+1 ?></td>
          <td>
            <div class="item-name"><?= $prodName ?></div>
            <?php if ($prodCode): ?><div class="item-code">SKU: <?= $prodCode ?></div><?php endif; ?>
            <?php if ($catName):  ?><div class="item-cat"><?= $catName ?></div><?php endif; ?>
          </td>
          <td class="num-right num-muted"><?= rtrim(rtrim(number_format($qty, 2), '0'), '.') ?></td>
          <td class="num-right">€<?= number_format($unitAmt, 2) ?></td>
          <?php if ($hasDisc): ?>
          <td class="num-right">
            <?php if ($discPct > 0): ?>
              <span class="num-disc"><?= number_format($discPct, 1) ?>%</span>
            <?php else: ?>
              <span class="num-muted">—</span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
          <?php if ($hasTax): ?>
          <td class="num-right">
            <?php if ($taxPct > 0): ?>
              <span class="num-tax"><?= number_format($taxPct, 1) ?>%</span>
            <?php else: ?>
              <span class="num-muted">—</span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
          <td class="num-right num-total">€<?= number_format($finalAmt, 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Totals -->
  <div class="inv-totals">
    <table class="inv-totals-table">
      <tr class="subtotal">
        <td>Subtotal</td>
        <td>€<?= number_format($subTotal, 2) ?></td>
      </tr>
      <?php if ($shipAmt > 0): ?>
      <tr class="ship">
        <td>Shipping</td>
        <td>€<?= number_format($shipAmt, 2) ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($discAmt > 0): ?>
      <tr class="disc">
        <td>Discount</td>
        <td>−€<?= number_format($discAmt, 2) ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($taxAmt > 0): ?>
      <tr class="tax">
        <td>Tax / VAT</td>
        <td>€<?= number_format($taxAmt, 2) ?></td>
      </tr>
      <?php endif; ?>
      <tr class="grand">
        <td>Grand Total</td>
        <?php
        $displayFinal = (float)($order->FINAL_TOTAL_AMT ?? 0);
        if ($displayFinal <= 0) $displayFinal = $subTotal + $shipAmt - $discAmt + $taxAmt;
        ?>
        <td style="color:#2563eb;">€<?= number_format($displayFinal, 2) ?></td>
      </tr>
    </table>
  </div>

  <!-- Order History Timeline -->
  <?php if (!empty($history)): ?>
  <div class="inv-history">
    <div class="inv-history-title">Order History</div>
    <div class="timeline">
      <?php foreach ($history as $h):
        $hOs  = (string)($h->HISTORY_ORDER_STATUS   ?? '');
        $hPs  = (string)($h->HISTORY_PAYMENT_STATUS ?? '');
        $hRem = trim((string)($h->HISTORY_REMARKS   ?? ''));
        $hBy  = (string)($h->CHANGED_BY_NAME        ?? 'System');
        $hDt  = (string)($h->CREATED_AT             ?? '');
        $hDtF = $hDt ? date('d M Y, H:i', strtotime($hDt)) : '—';
        $dotClass = match($hOs) {
            'Order Delivered'   => 'delivered',
            'Order Cancelled'   => 'cancelled',
            'Order Pending'     => 'pending',
            default             => '',
        };
      ?>
      <div class="tl-item">
        <div class="tl-dot <?= $dotClass ?>"></div>
        <div class="tl-meta"><?= htmlspecialchars($hDtF) ?> &middot; <?= htmlspecialchars($hBy) ?></div>
        <div class="tl-status"><?= htmlspecialchars($hOs) ?></div>
        <?php if ($hPs): ?><div class="tl-pay"><?= htmlspecialchars($hPs) ?></div><?php endif; ?>
        <?php if ($hRem): ?><div class="tl-remark">"<?= htmlspecialchars($hRem) ?>"</div><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Footer -->
  <div class="inv-footer">
    <div class="inv-footer-left">
      <?php if ($coName): ?><strong><?= htmlspecialchars($coName) ?></strong><br><?php endif; ?>
      <?php
      $footParts = array_filter([$coAddr, $coEmail, $coPhone]);
      echo htmlspecialchars(implode(' · ', $footParts));
      ?>
    </div>
    <div class="inv-footer-right">
      Invoice <strong><?= htmlspecialchars($orderNo) ?></strong><br>
      Generated <?= date('d M Y') ?>
    </div>
  </div>

</div><!-- /.paper -->

</body>
</html>
