<?php
/**
 * Quotation PDF View / Download
 * Opens as a standalone printable page (no admin chrome).
 * URL: admin/quotation-pdf?id=123&uid=456
 *
 * Auth:  admin session required.
 * Guard: uid param must match tbl_enquiry_quote.user_id (0 = guest quote, skips uid check).
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

/* ── 1. Session auth ── */
if (empty($_SESSION['sinelec_admin']['USER_ID'])) {
    header('location:index'); exit();
}

/* ── 2. Param validation ── */
$qid     = (int)($_GET['id']  ?? 0);
$uidParam = (int)($_GET['uid'] ?? 0);   // 0 = not provided / guest quotation

if ($qid <= 0) {
    http_response_code(400);
    echo '<p style="font-family:sans-serif;padding:40px;">Invalid quotation ID.</p>'; exit();
}

$controller = new AdminController();
$q          = $controller->getQuotationById($qid);

if (!$q) {
    http_response_code(404);
    echo '<p style="font-family:sans-serif;padding:40px;">Quotation not found.</p>'; exit();
}

/* ── 3. Cross-check uid param against stored user_id ──
 *  - If uid > 0 was passed AND the quotation has a user_id > 0,
 *    they must match. This prevents ID-enumeration (changing ?id=).
 *  - If the quotation was created without a linked user (user_id = 0)
 *    we skip this check (guest / walk-in quotation).               */
$storedUid = (int)(float)($q->USER_ID ?? 0);
if ($uidParam > 0 && $storedUid > 0 && $uidParam !== $storedUid) {
    http_response_code(403);
    echo '<p style="font-family:sans-serif;padding:40px;">Access denied — quotation does not match the provided user.</p>'; exit();
}

$products = $controller->getQuotationProducts($qid);

/* ── Pull user + address data ── */
$userId    = (int)(float)($q->USER_ID           ?? 0);
$addrId    = (int)(float)($q->USER_ADDRESS_ID   ?? 0);
$bilAddrId = (int)(float)($q->BILLING_ADDRESS_ID?? 0);

// Resolve customer info: JOINed columns from getQuotationById (via _resolved aliases) or inline fallback
$custName  = (string)($q->USER_NAME_RESOLVED    ?? $q->USER_NAME    ?? '');
$custEmail = (string)($q->USER_EMAIL_RESOLVED   ?? $q->USER_EMAIL   ?? '');
$custPhone = (string)($q->USER_PHONE_RESOLVED   ?? $q->USER_PHONE   ?? '');
$custCo    = (string)($q->COMPANY_NAME_RESOLVED ?? $q->COMPANY_NAME ?? '');

// Resolve delivery address
$delAddr   = '';
$delName   = $custName;
$delCo     = $custCo;
if ($addrId > 0) {
    $addrs  = $controller->getUserAddressesForQuote($userId);
    $delRow = null; $bilRow = null;
    foreach ($addrs as $a) {
        $aid = (int)(float)($a->USER_ADDRESS_ID ?? 0);
        if ($aid === $addrId)    $delRow = $a;
        if ($aid === $bilAddrId) $bilRow = $a;
    }
    if ($delRow) {
        $delName = (string)($delRow->USER_NAME   ?? $custName);
        $delCo   = (string)($delRow->COMPANY_NAME?? $custCo);
        $delAddr = implode(', ', array_filter([
            (string)($delRow->ADDRESS ?? ''),
            (string)($delRow->CITY   ?? ''),
            (string)($delRow->STATE  ?? ''),
            (string)($delRow->COUNTRY?? ''),
            (string)($delRow->ZIP    ?? ''),
        ]));
    }
    if (!$bilRow) $bilRow = $delRow;
    $bilName = $bilRow ? (string)($bilRow->USER_NAME    ?? $delName) : $delName;
    $bilCo   = $bilRow ? (string)($bilRow->COMPANY_NAME ?? $delCo)   : $delCo;
    $bilAddr = $bilRow ? implode(', ', array_filter([
        (string)($bilRow->ADDRESS ?? ''),
        (string)($bilRow->CITY   ?? ''),
        (string)($bilRow->STATE  ?? ''),
        (string)($bilRow->COUNTRY?? ''),
        (string)($bilRow->ZIP    ?? ''),
    ])) : $delAddr;
} else {
    // Fallback for legacy quotations that stored address inline
    $bilName = $custName; $bilCo = $custCo;
    $delAddr = implode(', ', array_filter([
        (string)($q->DELIVERY_ADDRESS ?? ''), (string)($q->DELIVERY_CITY  ?? ''),
        (string)($q->DELIVERY_STATE   ?? ''), (string)($q->DELIVERY_COUNTRY?? ''),
        (string)($q->DELIVERY_ZIP     ?? ''),
    ]));
    $bilAddr = implode(', ', array_filter([
        (string)($q->BILLING_ADDRESS ?? ''), (string)($q->BILLING_CITY   ?? ''),
        (string)($q->BILLING_STATE   ?? ''), (string)($q->BILLING_COUNTRY ?? ''),
        (string)($q->BILLING_ZIP     ?? ''),
    ]));
}

/* ── Company details ── */
$co = $controller->getCompanyDetails();
$coName    = $co ? (string)($co->NAME            ?? '') : '';
$coEmail   = $co ? (string)($co->EMAIL           ?? '') : '';
$coPhone   = $co ? (string)($co->CONTACT_NUMBER  ?? '') : '';
$coAddress = $co ? (string)($co->ADDRESS         ?? '') : '';
$coLogo    = $co ? trim((string)($co->LOGO        ?? '')) : '';
$coInstr   = $co ? trim((string)($co->INSTRUCTIONS ?? '')) : '';
// Build absolute logo URL (stored as relative path e.g. uploads/company/logo.png)
$coLogoUrl = $coLogo;


/* ── Helpers ── */
$ref      = 'QT-' . str_pad((string)$qid, 6, '0', STR_PAD_LEFT);
$date     = date('d M Y', strtotime((string)($q->ENQUIRY_DATE ?? 'now')));
$status   = (string)($q->ENQUIRY_STATUS ?? '');
$subTotal = 0;
foreach ($products as $p) {
    $subTotal += (float)($p->PRODUCT_QUANTITY ?? 0) * (float)($p->PRODUCT_AMT ?? 0);
}
$vatAmt     = (float)($q->ENQUIRY_VAT_AMT      ?? 0);
$shipAmt    = (float)($q->ENQUIRY_SHIPPING_AMT ?? 0);
$grandTotal = (float)($q->ENQUIRY_TOTAL_AMT    ?? 0);
$discAmt    = (float)($q->DISCOUNT_AMT         ?? 0);
$vatNumber  = trim((string)($q->VAT_NUMBER     ?? ''));
// Per-product discount: check if any product has a discount
$hasLineDisc = false;
foreach ($products as $_p) { if ((float)($_p->PRODUCT_DISCOUNT_PCT ?? 0) > 0) { $hasLineDisc = true; break; } }
$netSub  = $subTotal - $discAmt;
$vatPct  = ($netSub > 0 && $vatAmt > 0) ? round($vatAmt / $netSub * 100, 2) : 0;

$stColor = match($status) {
    'Quotation Pending' => '#d97706',
    'Quotation Sent'    => '#0891b2',
    'Order Generated'   => '#7c3aed',
    'Order Completed'   => '#16a34a',
    default             => '#64748b',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quotation <?= htmlspecialchars($ref) ?><?= $coName ? ' — ' . htmlspecialchars($coName) : '' ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px;
    color: #1e293b;
    background: #f1f5f9;
    padding: 24px 16px;
  }

  .no-print {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-bottom: 16px;
    max-width: 860px;
    margin-left: auto;
    margin-right: auto;
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
  }
  .btn-print { background: #2563eb; color: #fff; }
  .btn-print:hover { background: #1d4ed8; }
  .btn-back  { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
  .btn-back:hover { background: #f8fafc; }

  /* ── Paper ── */
  .paper {
    background: #fff;
    max-width: 860px;
    margin: 0 auto;
    border-radius: 10px;
    box-shadow: 0 4px 32px rgba(0,0,0,.10);
    overflow: hidden;
  }

  /* ── Header band ── */
  .pdf-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: #fff;
    padding: 32px 40px 28px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }
  .pdf-logo-name {
    font-size: 26px;
    font-weight: 800;
    letter-spacing: .08em;
    color: #fff;
  }
  .pdf-logo-tag {
    font-size: 11px;
    color: rgba(255,255,255,.75);
    margin-top: 3px;
    letter-spacing: .04em;
  }
  .pdf-ref-block {
    text-align: right;
  }
  .pdf-ref-title {
    font-size: 13px;
    color: rgba(255,255,255,.70);
    text-transform: uppercase;
    letter-spacing: .08em;
  }
  .pdf-ref-num {
    font-size: 28px;
    font-weight: 700;
    color: #fff;
    line-height: 1.15;
  }
  .pdf-ref-date { font-size: 12px; color: rgba(255,255,255,.75); margin-top: 4px; }
  .pdf-status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    background: rgba(255,255,255,.2);
    color: #fff;
    margin-top: 6px;
    letter-spacing: .04em;
  }

  /* ── Info grid ── */
  .pdf-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    border-bottom: 1px solid #e2e8f0;
  }
  .pdf-info-block {
    padding: 20px 28px;
    border-right: 1px solid #e2e8f0;
  }
  .pdf-info-block:last-child { border-right: none; }
  .pdf-info-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #94a3b8;
    margin-bottom: 6px;
    font-weight: 600;
  }
  .pdf-info-name {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
  }
  .pdf-info-line {
    font-size: 12px;
    color: #475569;
    line-height: 1.6;
  }

  /* ── Address row ── */
  .pdf-addr-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-bottom: 1px solid #e2e8f0;
  }
  .pdf-addr-block {
    padding: 16px 28px;
    border-right: 1px solid #e2e8f0;
  }
  .pdf-addr-block:last-child { border-right: none; }

  /* ── Ref fields row ── */
  .pdf-meta {
    display: flex;
    gap: 0;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12px;
  }
  .pdf-meta-item {
    flex: 1;
    padding: 12px 28px;
    border-right: 1px solid #e2e8f0;
  }
  .pdf-meta-item:last-child { border-right: none; }
  .pdf-meta-key { color: #94a3b8; font-size: 10px; text-transform: uppercase; letter-spacing: .06em; font-weight: 600; }
  .pdf-meta-val { margin-top: 3px; font-weight: 600; color: #1e293b; }

  /* ── Products table ── */
  .pdf-products {
    padding: 0;
  }
  .pdf-products table {
    width: 100%;
    border-collapse: collapse;
  }
  .pdf-products thead tr {
    background: #f8fafc;
  }
  .pdf-products th {
    padding: 11px 12px;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #64748b;
    font-weight: 600;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
  }
  .pdf-products th.right { text-align: right; }
  .pdf-products td {
    padding: 13px 12px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
    color: #1e293b;
    vertical-align: top;
  }
  .pdf-products td.right {
    text-align: right;
    white-space: nowrap;
  }
  .pdf-products tbody tr:hover { background: #f8fafc; }
  .prod-name { font-weight: 600; color: #1e293b; }
  .prod-code { font-size: 11px; color: #94a3b8; margin-top: 2px; }
  .prod-cat  { font-size: 11px; color: #94a3b8; }

  /* ── Totals ── */
  .pdf-totals {
    padding: 16px 28px 20px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
  }
  .pdf-totals-table {
    min-width: 280px;
    border-collapse: collapse;
  }
  .pdf-totals-table td {
    padding: 5px 8px;
    font-size: 13px;
    color: #475569;
  }
  .pdf-totals-table .label { color: #64748b; }
  .pdf-totals-table .amount { text-align: right; font-weight: 600; color: #1e293b; }
  .pdf-totals-table .grand-row td {
    padding-top: 10px;
    border-top: 2px solid #1e293b;
    font-size: 16px;
    font-weight: 700;
    color: #2563eb;
  }
  .pdf-totals-table .grand-row .label { color: #1e293b; }

  /* ── Footer ── */
  .pdf-footer {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 20px 28px;
    font-size: 11px;
    color: #64748b;
  }
  .pdf-footer-note { line-height: 1.7; }
  .pdf-tc-body { font-size: 11px; color: #64748b; line-height: 1.7; }
  .pdf-tc-body p  { margin: 0 0 6px; }
  .pdf-tc-body a  { color: #3b82f6; word-break: break-all; }
  .pdf-tc-body strong { color: #475569; }
  .pdf-tc-body div { margin-bottom: 4px; }

  /* ── Print styles ── */
  @media print {
    body { background: #fff; padding: 0; }
    .no-print { display: none !important; }
    .paper {
      box-shadow: none;
      border-radius: 0;
      max-width: 100%;
    }
    @page {
      margin: 12mm 10mm;
      size: A4 portrait;
    }
  }
</style>
</head>
<body>

<!-- Top action bar (hidden on print) -->
<div class="no-print">
  <button class="btn-action btn-back" onclick="history.back()">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back
  </button>
  <button class="btn-action btn-print" onclick="window.print()">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Print / Save PDF
  </button>
</div>

<div class="paper">

  <!-- ── HEADER ── -->
  <div class="pdf-header">
    <div>
      <?php if ($coLogoUrl !== ''): ?>
      <img src="<?= htmlspecialchars($coLogoUrl) ?>" alt="<?= htmlspecialchars($coName) ?>"
           style="max-height:56px;max-width:180px;object-fit:contain;margin-bottom:10px;filter:brightness(0) invert(1);">
      <?php else: ?>
      <div class="pdf-logo-name"><?= htmlspecialchars(strtoupper($coName ?: 'COMPANY')) ?></div>
      <?php endif; ?>
      <?php if ($coEmail || $coPhone): ?>
      <div style="margin-top:10px;font-size:12px;line-height:1.7;color:rgba(255,255,255,.80);">
        <?php if ($coEmail): ?><?= htmlspecialchars($coEmail) ?><br><?php endif; ?>
        <?php if ($coPhone): ?><?= htmlspecialchars($coPhone) ?><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="pdf-ref-block">
      <div class="pdf-ref-title">Quotation</div>
      <div class="pdf-ref-num"><?= htmlspecialchars($ref) ?></div>
      <div class="pdf-ref-date">Date: <?= htmlspecialchars($date) ?></div>
      <div><span class="pdf-status-badge" style="background:<?= $stColor ?>33;"><?= htmlspecialchars($status) ?></span></div>
    </div>
  </div>

  <!-- ── CUSTOMER + META ── -->
  <div class="pdf-info">
    <div class="pdf-info-block">
      <div class="pdf-info-label">Bill To</div>
      <div class="pdf-info-name"><?= htmlspecialchars($custName) ?></div>
      <?php if ($custCo): ?>
      <div class="pdf-info-line"><?= htmlspecialchars($custCo) ?></div>
      <?php endif; ?>
      <?php if ($custEmail): ?>
      <div class="pdf-info-line"><?= htmlspecialchars($custEmail) ?></div>
      <?php endif; ?>
      <?php if ($custPhone): ?>
      <div class="pdf-info-line"><?= htmlspecialchars($custPhone) ?></div>
      <?php endif; ?>
      <?php if ($vatNumber): ?>
      <div class="pdf-info-line" style="margin-top:6px;">
        <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;">VAT No.</span>
        <span style="font-family:monospace;font-size:12px;color:#1e293b;margin-left:4px;"><?= htmlspecialchars($vatNumber) ?></span>
      </div>
      <?php endif; ?>
    </div>
    <div class="pdf-info-block">
      <div class="pdf-info-label">From</div>
      <?php if ($coName): ?>
      <div class="pdf-info-name"><?= htmlspecialchars($coName) ?></div>
      <?php endif; ?>
      <?php if ($coAddress): ?>
      <div class="pdf-info-line"><?= nl2br(htmlspecialchars($coAddress)) ?></div>
      <?php endif; ?>
      <?php if ($coEmail || $coPhone): ?>
      <div class="pdf-info-line" style="margin-top:4px;">
        <?php if ($coEmail): ?><?= htmlspecialchars($coEmail) ?><?php endif; ?>
        <?php if ($coPhone): ?><br><?= htmlspecialchars($coPhone) ?><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── ADDRESSES ── -->
  <?php if ($delAddr || $bilAddr): ?>
  <div class="pdf-addr-row">
    <?php if ($delAddr): ?>
    <div class="pdf-addr-block">
      <div class="pdf-info-label">Delivery Address</div>
      <?php if (!empty($delName)): ?><div style="font-weight:600;font-size:12px;margin-bottom:2px;"><?= htmlspecialchars($delName) ?></div><?php endif; ?>
      <?php if (!empty($delCo) && $delCo !== $delName): ?><div style="font-size:11px;color:#64748b;margin-bottom:2px;"><?= htmlspecialchars($delCo) ?></div><?php endif; ?>
      <div class="pdf-info-line"><?= nl2br(htmlspecialchars($delAddr)) ?></div>
    </div>
    <?php endif; ?>
    <?php if ($bilAddr): ?>
    <div class="pdf-addr-block">
      <div class="pdf-info-label">Billing Address</div>
      <?php if (!empty($bilName)): ?><div style="font-weight:600;font-size:12px;margin-bottom:2px;"><?= htmlspecialchars($bilName) ?></div><?php endif; ?>
      <?php if (!empty($bilCo) && $bilCo !== $bilName): ?><div style="font-size:11px;color:#64748b;margin-bottom:2px;"><?= htmlspecialchars($bilCo) ?></div><?php endif; ?>
      <div class="pdf-info-line"><?= nl2br(htmlspecialchars($bilAddr)) ?></div>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ── META REFS ── -->
  <?php
    $hasCustOrd = trim((string)($q->CUSTOMER_ORDER_NO    ?? '')) !== '';
    $hasCustSup = trim((string)($q->CUSTOMER_SUPPLIER_NO ?? '')) !== '';
    if ($hasCustOrd || $hasCustSup):
  ?>
  <div class="pdf-meta">
    <?php if ($hasCustOrd): ?>
    <div class="pdf-meta-item">
      <div class="pdf-meta-key">Customer Order No.</div>
      <div class="pdf-meta-val"><?= htmlspecialchars((string)$q->CUSTOMER_ORDER_NO) ?></div>
    </div>
    <?php endif; ?>
    <?php if ($hasCustSup): ?>
    <div class="pdf-meta-item">
      <div class="pdf-meta-key">Supplier Ref. No.</div>
      <div class="pdf-meta-val"><?= htmlspecialchars((string)$q->CUSTOMER_SUPPLIER_NO) ?></div>
    </div>
    <?php endif; ?>
    <div class="pdf-meta-item">
      <div class="pdf-meta-key">Quotation Date</div>
      <div class="pdf-meta-val"><?= htmlspecialchars($date) ?></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── PRODUCTS TABLE ── -->
  <div class="pdf-products">
    <table>
      <thead>
        <tr>
          <th style="width:36px;">#</th>
          <th>Product</th>
          <th>Category</th>
          <th class="right" style="width:50px;">Qty</th>
          <th class="right" style="width:95px;">Unit Price</th>
          <th class="right" style="width:90px;">Amount</th>
          <?php if ($hasLineDisc): ?>
          <th class="right" style="width:55px;">Disc%</th>
          <th class="right" style="width:85px;">Disc Amt</th>
          <th class="right" style="width:85px;">Final Amt</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
        <tr><td colspan="<?= $hasLineDisc ? 9 : 6 ?>" style="text-align:center;padding:24px;color:#94a3b8;">No products added.</td></tr>
        <?php endif; ?>
        <?php foreach ($products as $i => $p):
          $qty          = (int)(float)($p->PRODUCT_QUANTITY      ?? 0);
          $unitPrc      = (float)($p->PRODUCT_AMT               ?? 0);
          $lineDiscPct  = (float)($p->PRODUCT_DISCOUNT_PCT      ?? 0);
          $lineAmt      = $qty * $unitPrc;
          $lineDisc     = $lineAmt * $lineDiscPct / 100;
          $lineFinal    = $lineAmt - $lineDisc;
        ?>
        <tr>
          <td style="color:#94a3b8;"><?= $i + 1 ?></td>
          <td>
            <div class="prod-name"><?= htmlspecialchars((string)($p->PRODUCT_NAME ?? 'Unknown Product')) ?></div>
            <?php if ($p->PRODUCT_CODE ?? ''): ?>
            <div class="prod-code"><?= htmlspecialchars((string)$p->PRODUCT_CODE) ?></div>
            <?php endif; ?>
          </td>
          <td class="prod-cat"><?= htmlspecialchars((string)($p->PRODUCT_CATEGORY_NAME ?? '—')) ?></td>
          <td class="right"><?= $qty ?></td>
          <td class="right">€<?= number_format($unitPrc, 2) ?></td>
          <td class="right"><strong>€<?= number_format($lineAmt, 2) ?></strong></td>
          <?php if ($hasLineDisc): ?>
          <td class="right" style="color:#64748b;"><?= $lineDiscPct > 0 ? number_format($lineDiscPct, 2).'%' : '—' ?></td>
          <td class="right" style="color:#dc2626;"><?= $lineDisc > 0 ? '−€'.number_format($lineDisc, 2) : '—' ?></td>
          <td class="right" style="font-weight:700;color:#2563eb;">€<?= number_format($lineFinal, 2) ?></td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- ── TOTALS ── -->
  <div class="pdf-totals">
    <table class="pdf-totals-table">
      <tr>
        <td class="label">Subtotal</td>
        <td class="amount">€<?= number_format($subTotal, 2) ?></td>
      </tr>
      <?php if ($discAmt > 0): ?>
      <tr>
        <td class="label">Total Discount</td>
        <td class="amount" style="color:#dc2626;">−€<?= number_format($discAmt, 2) ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($vatAmt > 0): ?>
      <tr>
        <td class="label">VAT / Tax<?= $vatPct > 0 ? ' ('.$vatPct.'%)' : '' ?></td>
        <td class="amount">€<?= number_format($vatAmt, 2) ?></td>
      </tr>
      <?php elseif ($vatNumber): ?>
      <tr>
        <td class="label" style="color:#059669;">VAT / Tax</td>
        <td class="amount" style="color:#059669;font-size:11px;font-weight:600;">Exempt (VAT No. <?= htmlspecialchars($vatNumber) ?>)</td>
      </tr>
      <?php endif; ?>
      <?php if ($shipAmt > 0): ?>
      <tr>
        <td class="label">Shipping</td>
        <td class="amount">€<?= number_format($shipAmt, 2) ?></td>
      </tr>
      <?php endif; ?>
      <tr class="grand-row">
        <td class="label">Grand Total</td>
        <td class="amount">€<?= number_format($grandTotal, 2) ?></td>
      </tr>
    </table>
  </div>

  <!-- ── FOOTER ── -->
  <div class="pdf-footer">
    <div class="pdf-footer-note">
      <strong style="color:#475569;display:block;margin-bottom:8px;">Terms &amp; Conditions</strong>
      <?php if ($coInstr !== ''): ?>
      <div class="pdf-tc-body"><?= $coInstr /* HTML stored in DB — output directly */ ?></div>
      <?php else: ?>
      <p style="margin:0;">
        This quotation is valid for 30 days from the date of issue. Prices are subject to change after the validity period.
        All prices are exclusive of applicable taxes unless mentioned. Payment terms as per agreement.
      </p>
      <?php endif; ?>
    </div>
  </div>

</div><!-- /paper -->

</body>
</html>
