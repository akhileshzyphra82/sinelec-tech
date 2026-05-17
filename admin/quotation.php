<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'quotation';
$pageTitle   = 'Quotations';

$controller = new AdminController();

$canView   = sinelec_can('view');
$canAdd    = sinelec_can('add');
$canEdit   = sinelec_can('edit');
$canDelete = sinelec_can('delete');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

/* ── Data ── */
$search  = trim($_GET['search'] ?? '');
$statusF = trim($_GET['status'] ?? '');

$quotations  = $controller->getAllQuotations($search, $statusF);
$allQtProds  = $controller->getAllQuotationProductsIndexed();
$customers   = $controller->getCustomersForQuote();
$categories  = $controller->getAllCategories();
$allProducts = $controller->getAllProducts();
$countries   = $controller->getAllCountries();

/* ── Stats ── */
$statTotal = 0; $statPending = 0; $statSent = 0; $statGen = 0; $statDone = 0;
foreach ($quotations as $q) {
    $statTotal++;
    $st = strtolower((string)($q->ENQUIRY_STATUS ?? ''));
    if (str_contains($st, 'pending'))       $statPending++;
    elseif (str_contains($st, 'sent'))      $statSent++;
    elseif (str_contains($st, 'generated')) $statGen++;
    elseif (str_contains($st, 'completed')) $statDone++;
}

/* ── Category → products map for JS ── */
$catProdMap = [];
foreach ($allProducts as $p) {
    $cid = (int)(float)($p->PRODUCT_CATEGORY_ID ?? 0);
    $catProdMap[$cid][] = [
        'id'    => (int)(float)($p->PRODUCT_ID    ?? 0),
        'name'  => (string)($p->PRODUCT_NAME      ?? ''),
        'code'  => (string)($p->PRODUCT_CODE      ?? ''),
        'price' => (float)($p->PRODUCT_AMT        ?? 0),   // was SELLING_PRICE
        'stock' => (int)(float)($p->TOTAL_PRODUCT ?? 0),
    ];
}

/* ── Bake product rows (indexed by quote id) ── */
$QUOT_PRODS = (object)array_map(function($rows) {
    return array_values(array_map(function($r) {
        return [
            'cat_id'   => (int)(float)($r->PRODUCT_CATEGORY_ID    ?? 0),
            'prod_id'  => (int)(float)($r->PRODUCT_ID             ?? 0),
            'name'     => (string)($r->PRODUCT_NAME               ?? ''),
            'code'     => (string)($r->PRODUCT_CODE               ?? ''),
            'qty'      => (int)(float)($r->PRODUCT_QUANTITY       ?? 1),
            'price'    => (float)($r->PRODUCT_AMT                 ?? 0),
            'disc_pct' => (float)($r->PRODUCT_DISCOUNT_PCT        ?? 0),
        ];
    }, $rows));
}, $allQtProds);

/* ── Bake quote header data (only IDs + amounts + status) ── */
$QUOT_DATA = [];
foreach ($quotations as $q) {
    $qid     = (int)(float)($q->ENQUIRY_QUOTE_ID ?? 0);
    $vatAmt  = (float)($q->ENQUIRY_VAT_AMT      ?? 0);
    $shipAmt = (float)($q->ENQUIRY_SHIPPING_AMT ?? 0);
    $total   = (float)($q->ENQUIRY_TOTAL_AMT    ?? 0);
    $sub     = $total - $vatAmt - $shipAmt;
    $vatPct  = ($sub > 0 && $vatAmt > 0) ? round($vatAmt / $sub * 100, 2) : 0;
    $QUOT_DATA[$qid] = [
        'user_id'    => (int)(float)($q->USER_ID          ?? 0),
        'user_name'  => (string)($q->USER_NAME            ?? ''),
        'user_email' => (string)($q->USER_EMAIL           ?? ''),
        'company'    => (string)($q->COMPANY_NAME         ?? ''),
        'addr_id'    => (int)(float)($q->USER_ADDRESS_ID  ?? 0),
        'bil_id'     => (int)(float)($q->BILLING_ADDRESS_ID ?? 0),
        'status'     => (string)($q->ENQUIRY_STATUS       ?? 'Quotation Pending'),
        'cust_ord'   => (string)($q->CUSTOMER_ORDER_NO    ?? ''),
        'cust_sup'   => (string)($q->CUSTOMER_SUPPLIER_NO ?? ''),
        'vat_pct'    => $vatPct,
        'ship_amt'   => $shipAmt,
        'vat_number' => (string)($q->VAT_NUMBER ?? ''),
        'disc_pct'   => (float)($q->DISCOUNT_PERCENTAGE ?? 0),
        'disc_amt'   => (float)($q->DISCOUNT_AMT        ?? 0),
    ];
}

/* ── Bake countries list for JS ── */
$countriesList = [];
foreach ($countries as $c) {
    $countriesList[] = [
        'id'   => (int)(float)($c->COUNTRY_ID ?? 0),
        'name' => (string)($c->COUNTRY ?? ''),
    ];
}

ob_start();
?>

<!-- ── PAGE HEADER ── -->
<div class="pg-header">
  <div>
    <div class="pg-title">Quotations</div>
    <div class="pg-subtitle">Create and manage customer quotations, track status and send by email.</div>
  </div>
  <?php if ($canAdd): ?>
  <button class="btn btn--primary" onclick="openNewQuotModal()">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Quotation
  </button>
  <?php endif; ?>
</div>

<!-- ── STATS ROW ── -->
<div class="quot-stat-row">
  <?php
  $statCards = [
    ['label'=>'Total',     'val'=>$statTotal,   'cls'=>'kpi-tile--blue',    'icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>'],
    ['label'=>'Pending',   'val'=>$statPending, 'cls'=>'kpi-tile--orange',  'icon'=>'<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'],
    ['label'=>'Sent',      'val'=>$statSent,    'cls'=>'kpi-tile--sky',     'icon'=>'<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>'],
    ['label'=>'Generated', 'val'=>$statGen,     'cls'=>'kpi-tile--violet',  'icon'=>'<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>'],
    ['label'=>'Completed', 'val'=>$statDone,    'cls'=>'kpi-tile--emerald', 'icon'=>'<polyline points="20 6 9 17 4 12"/>'],
  ];
  foreach ($statCards as $sc): ?>
  <div class="quot-stat-tile kpi-tile <?= $sc['cls'] ?>">
    <div class="quot-stat-icon kpi-tile-icon">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $sc['icon'] ?></svg>
    </div>
    <div>
      <div class="quot-stat-label"><?= $sc['label'] ?></div>
      <div class="quot-stat-value"><?= $sc['val'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── FILTER BAR ── -->
<div style="display:flex;gap:10px;margin-bottom:18px;align-items:center;flex-wrap:wrap;">
  <div style="position:relative;flex:1;min-width:220px;max-width:340px;">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="quotSearch" class="form-control" placeholder="Search customer, company, ref #…"
           style="padding-left:32px;height:36px;" oninput="quotOnSearch()">
  </div>
  <select id="quotStatusF" class="form-control" style="height:36px;width:180px;" onchange="quotOnSearch()">
    <option value="">All Statuses</option>
    <option value="Quotation Pending">Pending</option>
    <option value="Quotation Sent">Sent</option>
    <option value="Order Generated">Order Generated</option>
    <option value="Order Completed">Completed</option>
    <option value="Quotation Cancel">Cancelled</option>
  </select>
  <button class="btn btn--outline" style="height:36px;padding:0 14px;font-size:12px;" onclick="exportQuotCsv()">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Export
  </button>
</div>

<!-- ── TABLE ── -->
<div class="card">

  <?php if (empty($quotations)): ?>
  <div class="card-body">
    <div class="empty-state">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <h3>No quotations yet</h3>
      <p>Create your first quotation to get started.</p>
      <?php if ($canAdd): ?>
      <button class="btn btn--primary" onclick="openNewQuotModal()">New Quotation</button>
      <?php endif; ?>
    </div>
  </div>
  <?php else: ?>

  <!-- Pagination bar (top) -->
  <div class="emp-pgbar" id="quotPgBar">
    <div class="emp-pgbar-info" id="quotPgInfo">Showing 1–20 of <?= count($quotations) ?> records</div>
    <div class="emp-pgbar-right">
      <span class="emp-pgbar-rpp-label">Records per page</span>
      <select id="quotRpp" class="emp-pgbar-rpp-sel">
        <option value="20" selected>20</option>
        <option value="30">30</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <button class="emp-pgbar-apply" onclick="quotApplyRpp()">Apply</button>
      <div class="emp-pgbar-nav" id="quotNav"></div>
    </div>
  </div>

  <div class="card-body card-body--flush">
    <table class="dt" id="quotTable">
      <thead>
        <tr>
          <th>Ref #</th>
          <th>Customer</th>
          <th>Company</th>
          <th style="text-align:center;">Items</th>
          <th style="text-align:right;">Total (€)</th>
          <th>Status</th>
          <th>Date</th>
          <th style="text-align:center;width:60px;">Actions</th>
        </tr>
      </thead>
      <tbody id="quotTbody">
        <?php foreach ($quotations as $i => $q):
          $qid       = (int)(float)($q->ENQUIRY_QUOTE_ID ?? 0);
          $ref       = 'QT-' . str_pad((string)$qid, 6, '0', STR_PAD_LEFT);
          $st        = (string)($q->ENQUIRY_STATUS ?? 'Quotation Pending');
          $bc        = match($st) {
            'Quotation Pending' => 'badge--amber',
            'Quotation Sent'    => 'badge--blue',
            'Order Generated'   => 'badge--violet',
            'Order Completed'   => 'badge--green',
            'Quotation Cancel'  => 'badge--red',
            default             => 'badge--grey',
          };
          $prodCount = (int)($q->PRODUCT_COUNT ?? 0);
          $total     = number_format((float)($q->ENQUIRY_TOTAL_AMT ?? 0), 2);
          $dateRaw   = (string)($q->ENQUIRY_DATE ?? '');
          $dateFmt   = $dateRaw ? date('d M Y', strtotime($dateRaw)) : '—';
          $timeFmt   = $dateRaw ? date('h:i A', strtotime($dateRaw)) : '';
          $custName  = (string)($q->USER_NAME  ?? '');
          $custEmail = (string)($q->USER_EMAIL ?? '');
          $custPhone = (string)($q->USER_PHONE ?? '');
          $company   = (string)($q->COMPANY_NAME ?? '—');
          $searchStr = strtolower($custName . ' ' . $custEmail . ' ' . $company . ' ' . $ref);
        ?>
        <tr class="quot-row" data-search="<?= htmlspecialchars($searchStr) ?>" data-status="<?= htmlspecialchars($st) ?>" data-seq="<?= $i+1 ?>">
          <td><span style="font-weight:600;color:var(--primary);"><?= htmlspecialchars($ref) ?></span></td>
          <td>
            <div style="font-weight:500;line-height:1.4;"><?= htmlspecialchars($custName) ?></div>
            <?php if ($custEmail): ?>
              <div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars($custEmail) ?></div>
            <?php endif; ?>
            <?php if ($custPhone): ?>
              <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($custPhone) ?></div>
            <?php endif; ?>
          </td>
          <td style="color:var(--text-muted);font-size:12px;"><?= htmlspecialchars($company) ?></td>
          <td style="text-align:center;"><?= $prodCount ?></td>
          <td style="text-align:right;font-weight:600;">€<?= $total ?></td>
          <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($st) ?></span></td>
          <td>
            <div style="font-size:12px;color:var(--text-muted);"><?= $dateFmt ?></div>
            <?php if ($timeFmt): ?>
              <div style="font-size:11px;color:var(--text-muted);opacity:.7;"><?= $timeFmt ?></div>
            <?php endif; ?>
          </td>
          <td style="text-align:center;">
            <div style="display:inline-flex;align-items:center;gap:4px;">
              <a href="quotation-pdf?id=<?= $qid ?>&uid=<?= (int)(float)($q->USER_ID ?? 0) ?>" target="_blank"
                 title="View PDF"
                 style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:#f1f5f9;color:#64748b;text-decoration:none;flex-shrink:0;"
                 onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/></svg>
              </a>
              <div class="kbm-wrap">
                <button class="kbm-btn" onclick="toggleKbm(this)" title="More actions">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="5"  r="1.7"/>
                    <circle cx="12" cy="12" r="1.7"/>
                    <circle cx="12" cy="19" r="1.7"/>
                  </svg>
                </button>
                <div class="kbm-drop">
                  <?php if ($canEdit): ?>
                  <button class="kbm-item" onclick="closeKbm(this);openQuotEditModal(<?= $qid ?>)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit
                  </button>
                  <button class="kbm-item" onclick="closeKbm(this);openStatusModal(<?= $qid ?>,<?= htmlspecialchars(json_encode($st), ENT_QUOTES) ?>)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                    Update Status
                  </button>
                  <?php endif; ?>
                  <button class="kbm-item" onclick="closeKbm(this);resendQuotEmail(<?= $qid ?>,<?= htmlspecialchars(json_encode((string)($q->USER_EMAIL??'')), ENT_QUOTES) ?>)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Resend Email
                  </button>
                  <?php if ($canDelete): ?>
                  <div class="kbm-divider"></div>
                  <button class="kbm-item kbm-item--danger" onclick="closeKbm(this);openDeleteQuotModal(<?= $qid ?>,<?= htmlspecialchars(json_encode($ref), ENT_QUOTES) ?>)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    Delete
                  </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div id="quotNoResults" style="display:none;padding:40px;text-align:center;color:var(--text-muted);font-size:13px;">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto 10px;opacity:.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      No quotations match your search.
    </div>
  </div>
  <?php endif; ?>
</div>


<!-- ════════════════════════════════════════════════════
     ADD / EDIT QUOTATION MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="quotModal">
  <div class="modal" style="max-width:1060px;width:96%;max-height:92vh;display:flex;flex-direction:column;">

    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div>
        <div class="modal-title" id="quotModalTitle">New Quotation</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">Fill in customer info, address and products.</div>
      </div>
      <button class="modal-close" onclick="closeModal('quotModal')" style="font-size:22px;line-height:1;">×</button>
    </div>

    <!-- Tab Bar -->
    <div class="prod-tabs" style="flex-shrink:0;" id="quotTabBar">
      <button class="prod-tab active" data-qtab="customer" onclick="switchQTab('customer')">Customer Info</button>
      <button class="prod-tab" data-qtab="address" onclick="switchQTab('address')">Address</button>
      <button class="prod-tab" data-qtab="products" onclick="switchQTab('products')">Products</button>
      <button class="prod-tab" data-qtab="summary" onclick="switchQTab('summary')">Summary</button>
    </div>

    <div style="overflow-y:auto;flex:1;padding:22px;">
      <form id="quotForm" method="POST">
        <input type="hidden" name="enquiry_quote_id"  id="qfId"        value="0">
        <input type="hidden" name="user_id"            id="qfUserId"    value="0">
        <input type="hidden" name="user_address_id"    id="qfAddrId"    value="0">
        <input type="hidden" name="billing_address_id" id="qfBilAddrId" value="0">

        <!-- ══════════ Tab: Customer ══════════ -->
        <div class="qt-panel" id="qt-customer">

          <!-- ① Select existing customer -->
          <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px;">
            <div style="font-weight:600;font-size:13px;margin-bottom:10px;">Select Existing Customer</div>
            <div class="cust-ts-wrap" id="custTsWrap">
              <input type="text" id="custTsInput" class="form-control" placeholder="Type name, email or company to search…"
                     autocomplete="off" oninput="custTsFilter()" onfocus="custTsOpen()" style="padding-right:34px;">
              <span class="cust-ts-clear" id="custTsClear" onclick="custTsClear()" title="Clear">×</span>
              <div class="cust-ts-drop" id="custTsDrop">
                <?php foreach ($customers as $cu):
                  $cuid   = (int)(float)($cu->USER_ID ?? 0);
                  $cname  = (string)($cu->USER_NAME  ?? '');
                  $cemail = (string)($cu->USER_EMAIL ?? '');
                  $ccomp  = (string)($cu->COMPANY_NAME ?? '');
                  $clabel = $cname . ($cemail ? ' '.$cemail : '') . ($ccomp ? ' '.$ccomp : '');
                ?>
                <div class="cust-ts-opt" tabindex="0"
                     data-uid="<?= $cuid ?>"
                     data-name="<?= htmlspecialchars($cname) ?>"
                     data-email="<?= htmlspecialchars($cemail) ?>"
                     data-phone="<?= htmlspecialchars((string)($cu->USER_PHONE ?? '')) ?>"
                     data-isd="<?= (int)($cu->USER_PHONE_ISD ?? 91) ?>"
                     data-company="<?= htmlspecialchars($ccomp) ?>"
                     data-label="<?= htmlspecialchars(strtolower($clabel)) ?>"
                     onclick="custTsSelect(this)">
                  <span class="cust-ts-name"><?= htmlspecialchars($cname) ?></span>
                  <?php if ($cemail): ?><span class="cust-ts-email"><?= htmlspecialchars($cemail) ?></span><?php endif; ?>
                  <?php if ($ccomp):  ?><span class="cust-ts-co"><?= htmlspecialchars($ccomp) ?></span><?php endif; ?>
                </div>
                <?php endforeach; ?>
                <div class="cust-ts-none" id="custTsNone" style="display:none;">No customers match.</div>
              </div>
            </div>
            <!-- Selected customer chip -->
            <div id="custSelectedChip" style="display:none;margin-top:10px;padding:10px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;display:none;align-items:center;gap:10px;">
              <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:13px;color:#1e40af;" id="custChipName"></div>
                <div style="font-size:11px;color:#64748b;" id="custChipMeta"></div>
              </div>
              <button type="button" onclick="custTsClear()" style="background:none;border:none;cursor:pointer;color:#64748b;font-size:18px;line-height:1;flex-shrink:0;" title="Change customer">×</button>
            </div>
          </div>

          <!-- ② OR add new customer -->
          <div style="text-align:center;margin-bottom:16px;">
            <span style="font-size:12px;color:var(--text-muted);">— or —</span>
            <button type="button" class="btn btn--outline" style="margin-left:10px;font-size:12px;height:30px;padding:0 12px;" onclick="toggleNewCustForm()">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add New Customer
            </button>
          </div>

          <!-- Inline new customer form -->
          <div id="newCustForm" style="display:none;background:#fff9f0;border:1px solid #fed7aa;border-radius:10px;padding:16px;margin-bottom:16px;">
            <div style="font-weight:600;font-size:13px;margin-bottom:12px;color:#c2410c;">New Customer Details</div>
            <div class="form-row cols-2" style="margin-bottom:12px;">
              <div class="fg">
                <label>Full Name <span class="req">*</span></label>
                <input type="text" id="ncName" class="form-control" placeholder="Customer full name">
              </div>
              <div class="fg">
                <label>Email <span class="req">*</span></label>
                <input type="email" id="ncEmail" class="form-control" placeholder="customer@example.com">
              </div>
            </div>
            <div class="form-row cols-2" style="margin-bottom:12px;">
              <div class="fg">
                <label>Phone</label>
                <div style="display:flex;gap:6px;">
                  <select id="ncIsd" class="form-control" style="width:80px;flex-shrink:0;height:38px;">
                    <option value="91">+91</option><option value="1">+1</option>
                    <option value="44">+44</option><option value="971">+971</option>
                    <option value="966">+966</option><option value="65">+65</option>
                    <option value="60">+60</option><option value="49">+49</option>
                    <option value="33">+33</option><option value="61">+61</option>
                  </select>
                  <input type="text" id="ncPhone" class="form-control" placeholder="Mobile number">
                </div>
              </div>
              <div class="fg">
                <label>Company</label>
                <input type="text" id="ncCompany" class="form-control" placeholder="Company name">
              </div>
            </div>
            <div class="form-row cols-2" style="margin-bottom:14px;">
              <div class="fg">
                <label>Designation</label>
                <input type="text" id="ncDesig" class="form-control" placeholder="Job title">
              </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
              <button type="button" class="btn btn--primary" style="font-size:12px;height:34px;" onclick="submitNewCustomer()">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg>
                Create Customer
              </button>
              <button type="button" class="btn btn--ghost" style="font-size:12px;height:34px;" onclick="toggleNewCustForm()">Cancel</button>
              <span id="ncSpinner" style="display:none;font-size:12px;color:var(--text-muted);">Saving…</span>
              <span id="ncError"   style="display:none;font-size:12px;color:#ef4444;"></span>
            </div>
          </div>

          <!-- Quotation reference fields -->
          <div style="border-top:1px solid var(--border);padding-top:16px;">
            <div style="font-weight:600;font-size:13px;margin-bottom:12px;">Quotation Details</div>
            <div class="form-row cols-2" style="margin-bottom:12px;">
              <div class="fg">
                <label>Customer Order No.</label>
                <input type="text" name="customer_order_no" id="qfCustOrd" class="form-control" placeholder="Customer's PO number">
              </div>
              <div class="fg">
                <label>Supplier Ref. No.</label>
                <input type="text" name="customer_supplier_no" id="qfCustSup" class="form-control" placeholder="Supplier reference">
              </div>
            </div>
            <div class="fg" style="margin-bottom:0;">
              <label>Status</label>
              <select name="enquiry_status" id="qfStatus" class="form-control">
                <option value="Quotation Pending">Quotation Pending</option>
                <option value="Quotation Sent">Quotation Sent</option>
                <option value="Order Generated">Order Generated</option>
                <option value="Order Completed">Order Completed</option>
                <option value="Quotation Cancel">Quotation Cancel</option>
              </select>
            </div>
          </div>
        </div>

        <!-- ══════════ Tab: Address ══════════ -->
        <div class="qt-panel" id="qt-address" style="display:none;">

          <!-- No customer warning -->
          <div id="addrNoCustomer" style="text-align:center;padding:40px 20px;color:var(--text-muted);">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" style="margin:0 auto 10px;display:block;opacity:.4"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            <div style="font-size:13px;">Please select a customer on the previous tab first.</div>
          </div>

          <!-- Address panels (shown when customer is selected) -->
          <div id="addrPanels" style="display:none;">
            <!-- Manage addresses link -->
            <div style="text-align:right;margin-bottom:12px;">
              <a id="addrManageLink" href="#" target="_blank" style="font-size:12px;color:var(--primary);">
                Manage all addresses →
              </a>
            </div>

            <!-- ── Delivery Address ── -->
            <div style="margin-bottom:22px;">
              <div style="font-weight:700;font-size:13px;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Delivery Address
              </div>
              <div id="delAddrSelector"></div>
              <div id="delAddrCard" class="addr-card" style="display:none;"></div>
              <button type="button" class="addr-add-btn" id="delAddNewBtn" onclick="showAddrForm('del')" style="display:none;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add New Address
              </button>
              <div id="delAddrForm" class="addr-form-panel" style="display:none;"></div>
            </div>

            <!-- ── Billing Address ── -->
            <div>
              <div style="font-weight:700;font-size:13px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;">
                <span style="display:flex;align-items:center;gap:8px;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                  Billing Address
                </span>
                <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:500;cursor:pointer;">
                  <input type="checkbox" id="bilSameAsDel" onchange="toggleBilSame()" checked style="accent-color:var(--primary);">
                  Same as Delivery
                </label>
              </div>
              <div id="bilAddrWrap" style="display:none;">
                <div id="bilAddrSelector"></div>
                <div id="bilAddrCard" class="addr-card" style="display:none;"></div>
                <button type="button" class="addr-add-btn" id="bilAddNewBtn" onclick="showAddrForm('bil')" style="display:none;">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  Add New Address
                </button>
                <div id="bilAddrForm" class="addr-form-panel" style="display:none;"></div>
              </div>
              <div id="bilSameNote" style="padding:12px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:12px;color:#166534;">
                ✓ Billing address will be same as delivery address.
              </div>
            </div>
          </div>
        </div>

        <!-- ── Products ── -->
        <div class="qt-panel" id="qt-products" style="display:none;">
          <div id="quotProdRows"></div>
          <button type="button" class="btn btn--outline" style="margin-top:10px;font-size:12px;" onclick="addQuotProdRow()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Row
          </button>
        </div>

        <!-- ── Summary ── -->
        <div class="qt-panel" id="qt-summary" style="display:none;">
          <!-- Products read-only list -->
          <div style="margin-bottom:18px;">
            <div style="font-weight:600;font-size:13px;margin-bottom:8px;">Products in this Quotation</div>
            <div id="qSummaryProdList"></div>
          </div>
          <!-- Totals -->
          <div style="width:100%;border:1px solid var(--border);border-radius:8px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:10px 14px;color:var(--text-muted);">Subtotal</td>
                <td style="text-align:right;padding:10px 14px;font-weight:600;">€<span id="qSub">0.00</span></td>
              </tr>
              <tr style="border-bottom:1px solid var(--border);">
                <td colspan="2" style="padding:10px 14px;">
                  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span style="color:var(--text-muted);white-space:nowrap;font-size:13px;">VAT Number</span>
                    <input type="text" name="vat_number" id="qVatNumber" class="form-control"
                           placeholder="e.g. DE123456789 — leave blank to apply VAT"
                           style="flex:1;min-width:200px;height:32px;padding:2px 10px;font-size:12px;font-family:monospace;"
                           oninput="onVatNumberInput(this.value)">
                    <span id="qVatNumBadge" style="display:none;font-size:11px;font-weight:600;color:#059669;background:#dcfce7;border:1px solid #86efac;border-radius:12px;padding:2px 10px;white-space:nowrap;">
                      VAT Exempt (0%)
                    </span>
                  </div>
                </td>
              </tr>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:10px 14px;">
                  <span style="color:var(--text-muted);">VAT / Tax</span>
                  <input type="number" name="vat_pct" id="qVatPct" class="form-control"
                         style="width:70px;display:inline-block;margin-left:8px;height:28px;padding:2px 8px;font-size:12px;"
                         value="19" min="0" max="100" step="0.01" oninput="recalcTotals()">
                  <span style="color:var(--text-muted);font-size:12px;">%</span>
                </td>
                <td style="text-align:right;padding:10px 14px;">€<span id="qVat">0.00</span></td>
              </tr>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:10px 14px;">
                  <span style="color:var(--text-muted);">Shipping</span>
                  <input type="number" name="shipping_amt" id="qShip" class="form-control"
                         style="width:90px;display:inline-block;margin-left:8px;height:28px;padding:2px 8px;font-size:12px;"
                         value="0" min="0" step="0.01" oninput="recalcTotals()">
                </td>
                <td style="text-align:right;padding:10px 14px;">€<span id="qShipDisp">0.00</span></td>
              </tr>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:10px 14px;color:var(--text-muted);">Total Discount</td>
                <td style="text-align:right;padding:10px 14px;color:#dc2626;font-weight:600;">−€<span id="qDiscAmt">0.00</span></td>
              </tr>
              <tr style="background:#f8fafc;">
                <td style="padding:12px 14px;font-weight:700;font-size:14px;">Grand Total</td>
                <td style="text-align:right;padding:12px 14px;font-weight:700;font-size:16px;color:var(--primary);">€<span id="qGrand">0.00</span></td>
              </tr>
            </table>
          </div>
          <input type="hidden" name="enquiry_vat_amt"      id="qfVatAmt"   value="0">
          <input type="hidden" name="enquiry_shipping_amt" id="qfShipAmt"  value="0">
          <input type="hidden" name="enquiry_total_amt"    id="qfTotalAmt" value="0">
          <input type="hidden" name="discount_amt" id="qfDiscAmt" value="0">

          <!-- Duplicate button — shown only in edit mode -->
          <div id="qDuplicateWrap" style="display:none;margin-top:20px;padding:16px;background:#f0f9ff;border:1px dashed #7dd3fc;border-radius:10px;">
            <div style="font-size:13px;font-weight:600;color:#0369a1;margin-bottom:6px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
              Create New Quotation from this Data
            </div>
            <div style="font-size:12px;color:#0c4a6e;margin-bottom:12px;line-height:1.6;">
              Make any edits above, then click below to generate a fresh quotation with the same customer, address and products — without overwriting this one.
            </div>
            <button type="button" onclick="duplicateAsNewQuot()"
              style="display:inline-flex;align-items:center;gap:7px;background:#0369a1;color:#fff;border:none;padding:9px 20px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
              Duplicate as New Quotation
            </button>
          </div>
          <input type="hidden" name="is_duplicate" id="qfIsDuplicate" value="0">
        </div>

      </form>
    </div><!-- /scrollable body -->

    <!-- Footer nav -->
    <div class="modal-footer" style="display:flex;justify-content:space-between;align-items:center;padding:14px 22px;border-top:1px solid var(--border);flex-shrink:0;">
      <div>
        <button type="button" class="btn btn--outline" id="qPrevBtn" style="display:none;" onclick="prevQTab()">← Prev</button>
      </div>
      <div style="display:flex;gap:8px;">
        <button type="button" class="btn btn--ghost" onclick="closeModal('quotModal')">Cancel</button>
        <button type="button" class="btn btn--outline" id="qNextBtn" onclick="nextQTab()">Next →</button>
        <button type="button" class="btn btn--primary" id="qSaveBtn" style="display:none;" onclick="submitQuotForm()">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          Save Quotation
        </button>
      </div>
    </div>

  </div>
</div>


<!-- ════════════════════════════════════════════════════
     STATUS MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="statusModal">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <span class="modal-title">Update Status</span>
      <button class="modal-close" onclick="closeModal('statusModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form id="statusForm" method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateQuotationStatus') ?>">
        <input type="hidden" name="enquiry_quote_id" id="sfQid" value="0">
        <div class="fg">
          <label>New Status <span class="req">*</span></label>
          <select name="enquiry_status" id="sfStatus" class="form-control" onchange="onStatusChange(this.value)">
            <option value="Quotation Pending">Quotation Pending</option>
            <option value="Quotation Sent">Quotation Sent</option>
            <option value="Order Generated">Order Generated</option>
            <option value="Order Completed">Order Completed</option>
            <option value="Quotation Cancel" style="color:#dc2626;font-weight:600;">Quotation Cancel</option>
          </select>
        </div>
        <!-- Cancel reason — shown only when "Quotation Cancel" selected -->
        <div id="sfCancelWrap" style="display:none;margin-top:4px;">
          <div style="display:flex;align-items:center;gap:6px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 12px;margin-bottom:10px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span style="font-size:12px;color:#b91c1c;font-weight:500;">This action will mark the quotation as cancelled and notify the customer.</span>
          </div>
          <div class="fg" style="margin-bottom:0;">
            <label>Reason for Cancellation <span class="req">*</span></label>
            <textarea name="remark" id="sfRemark" class="form-control" rows="3"
              placeholder="e.g. Customer requested cancellation, price not agreed, duplicate quotation…"
              style="resize:vertical;min-height:80px;"></textarea>
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:18px;">
          <button type="button" class="btn btn--ghost" onclick="closeModal('statusModal')">Close</button>
          <button type="submit" id="sfSubmitBtn" class="btn btn--primary">Update Status</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════════════════
     DELETE CONFIRM MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="delQuotModal">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <span class="modal-title">Delete Quotation</span>
      <button class="modal-close" onclick="closeModal('delQuotModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="margin:0 0 18px;font-size:14px;">Are you sure you want to delete <strong id="delQuotRef"></strong>? This cannot be undone.</p>
      <form id="delQuotForm" method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteQuotation') ?>">
        <input type="hidden" name="enquiry_quote_id" id="dqfId" value="0">
        <div style="display:flex;justify-content:flex-end;gap:8px;">
          <button type="button" class="btn btn--ghost" onclick="closeModal('delQuotModal')">Cancel</button>
          <button type="submit" class="btn btn--danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Resend hidden form -->
<form id="resendForm" method="POST" action="service?urlstring=<?= EncryptURL('action=ResendQuotation') ?>" style="display:none;">
  <input type="hidden" name="enquiry_quote_id" id="resendQid" value="0">
</form>


<!-- ════════════════════════════════════════════════════
     PAGE STYLES
════════════════════════════════════════════════════ -->
<style>
/* ── Tab bar (prod-tab system) ── */
.prod-tabs {
  display: flex; gap: 0; border-bottom: 1px solid var(--border);
  flex-shrink: 0; padding: 0 22px; background: #fafbfc;
}
.prod-tab {
  padding: 10px 16px; font-size: 13px; font-weight: 500;
  color: var(--text-muted); background: none; border: none;
  border-bottom: 2px solid transparent; cursor: pointer;
  transition: all .15s; white-space: nowrap;
}
.prod-tab:hover { color: var(--text); }
.prod-tab.active {
  color: var(--primary); border-bottom-color: var(--primary); font-weight: 600;
}

/* ── Customer typesearch ── */
.cust-ts-wrap {
  position: relative;
}
.cust-ts-clear {
  position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
  font-size: 17px; line-height: 1; color: #9ca3af; cursor: pointer;
  display: none; padding: 2px 4px; border-radius: 4px;
  transition: color .15s;
}
.cust-ts-clear:hover { color: #ef4444; }
.cust-ts-clear.visible { display: block; }
.cust-ts-drop {
  position: absolute; top: calc(100% + 4px); left: 0; right: 0;
  max-height: 240px; overflow-y: auto; background: #fff;
  border: 1.5px solid var(--border); border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0,0,0,.1);
  z-index: 9999; display: none;
}
.cust-ts-drop.open { display: block; }
.cust-ts-opt {
  padding: 9px 12px; cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
  transition: background .1s;
  display: flex; flex-direction: column; gap: 2px;
}
.cust-ts-opt:last-child { border-bottom: none; }
.cust-ts-opt:hover,
.cust-ts-opt:focus  { background: #f0f4ff; outline: none; }
.cust-ts-name  { font-size: 13px; font-weight: 600; color: var(--text); }
.cust-ts-email { font-size: 11px; color: var(--text-muted); }
.cust-ts-co    { font-size: 11px; color: #7c3aed; font-weight: 500; }
.cust-ts-none  { padding: 14px 12px; text-align: center; font-size: 13px; color: var(--text-muted); }

/* ── Compact stat row ── */
.quot-stat-row {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 10px;
  margin-bottom: 18px;
}
.quot-stat-tile.kpi-tile {
  padding: 10px 12px;
  gap: 8px;
  border-radius: 9px;
}
.quot-stat-icon.kpi-tile-icon {
  width: 26px;
  height: 26px;
  border-radius: 6px;
  flex-shrink: 0;
}
.quot-stat-label {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .04em;
  color: #64748b;
  line-height: 1;
  margin-bottom: 3px;
}
.quot-stat-value {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
}
@media (max-width: 768px) {
  .quot-stat-row { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 480px) {
  .quot-stat-row { grid-template-columns: repeat(2, 1fr); }
}

/* ── Address selector/card/form ── */
.addr-sel {
  width: 100%; height: 38px; padding: 0 12px;
  border: 1.5px solid var(--border); border-radius: 8px;
  font-size: 13px; background: #fff; color: var(--text); cursor: pointer;
  -webkit-appearance: none; appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 12px center;
  padding-right: 32px;
  margin-bottom: 10px;
}
.addr-sel:focus { outline: none; border-color: var(--primary); }
.addr-card {
  padding: 12px 14px; background: #f0fdf4; border: 1px solid #bbf7d0;
  border-radius: 8px; font-size: 12px; line-height: 1.6; margin-bottom: 8px;
}
.addr-card-label { display:inline-block;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;background:#d1fae5;color:#065f46; }
.addr-card-name  { font-weight:700;font-size:13px;color:var(--text); }
.addr-card-line  { color:var(--text-muted); }
.addr-add-btn {
  display: flex; align-items: center; gap: 6px; padding: 7px 14px;
  font-size: 12px; font-weight: 600; color: var(--primary);
  background: #eff6ff; border: 1.5px dashed #93c5fd; border-radius: 8px;
  cursor: pointer; transition: background .15s;
}
.addr-add-btn:hover { background: #dbeafe; }
.addr-form-panel {
  background: #fff; border: 1.5px solid var(--border); border-radius: 10px;
  padding: 16px; margin-top: 10px;
}
.addr-label-toggle { display:flex;gap:6px;margin-bottom:14px; }
.addr-label-btn {
  padding: 6px 14px; border: 1.5px solid var(--border); border-radius: 20px;
  font-size: 12px; font-weight: 500; background: #fff; color: var(--text-muted);
  cursor: pointer; transition: all .15s; display:flex;align-items:center;gap:5px;
}
.addr-label-btn.active { border-color:var(--primary);background:#eff6ff;color:var(--primary);font-weight:700; }

/* ── New customer inline form spinner ── */
#ncSpinner { font-size:12px; color:var(--text-muted); }

/* Product row inside modal */
.qp-row {
  display: grid;
  grid-template-columns: 1.8fr 2.8fr 70px 130px 90px 110px 32px;
  gap: 8px;
  align-items: start;
  margin-bottom: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border);
}
@media(max-width:700px) {
  .qp-row { grid-template-columns: 1fr 1fr; }
}
.qp-amt { font-size:13px; font-weight:700; color:var(--primary); white-space:nowrap; margin-top:22px; display:block; }
.qp-lbl { font-size:11px; color:var(--text-muted); display:block; margin-bottom:4px; }
.qp-rm  { margin-top:22px; }

/* Summary table */
#qSummaryProdList table { width:100%; border-collapse:collapse; font-size:12px; }
#qSummaryProdList th { padding:6px 10px; background:#f1f5f9; font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); }
#qSummaryProdList td { padding:7px 10px; border-bottom:1px solid #f1f5f9; }

/* Pagination (emp-pgbar) — copied from customers.php */
.emp-pgbar {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 10px; padding: 12px 18px;
  border-bottom: 1px solid var(--border);
  background: #fafbfc; border-radius: var(--radius) var(--radius) 0 0;
}
.emp-pgbar-info { font-size: 13px; color: var(--text-muted); white-space: nowrap; }
.emp-pgbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.emp-pgbar-rpp-label { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; }
.emp-pgbar-rpp-sel {
  height: 34px; padding: 0 28px 0 10px;
  border: 1.5px solid var(--border); border-radius: 20px;
  font-size: 13px; font-weight: 600;
  background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 10px center;
  -webkit-appearance: none; appearance: none; cursor: pointer; color: var(--text); min-width: 70px;
}
.emp-pgbar-rpp-sel:focus { outline: none; border-color: var(--primary); }
.emp-pgbar-apply {
  height: 34px; padding: 0 16px; border: 1.5px solid var(--border); border-radius: 20px;
  font-size: 13px; font-weight: 600; background: #fff; color: var(--text); cursor: pointer;
  transition: border-color .15s, background .15s;
}
.emp-pgbar-apply:hover { border-color: var(--primary); background: #f0f4ff; color: var(--primary); }
.emp-pgbar-nav { display: flex; align-items: center; gap: 4px; }
.pg-btn {
  min-width: 34px; height: 34px; padding: 0 10px;
  border: 1.5px solid var(--border); border-radius: 20px;
  font-size: 13px; font-weight: 500; background: #fff; color: var(--text);
  cursor: pointer; transition: border-color .15s, background .15s, color .15s;
  white-space: nowrap; display: flex; align-items: center; justify-content: center;
}
.pg-btn:hover:not(:disabled) { border-color: var(--primary); background: #f0f4ff; color: var(--primary); }
.pg-btn:disabled { opacity: .4; cursor: default; }
.pg-active { background: var(--primary) !important; color: #fff !important; border-color: var(--primary) !important; }
.pg-dots { padding: 0 4px; color: var(--text-muted); font-size: 13px; display: flex; align-items: center; }
</style>


<!-- ════════════════════════════════════════════════════
     SERVER-SIDE DATA + JAVASCRIPT
════════════════════════════════════════════════════ -->
<script>
/* ── Baked data ── */
const CAT_PROD_MAP   = <?= json_encode($catProdMap, JSON_UNESCAPED_UNICODE) ?>;
const QUOT_PRODUCTS  = <?= json_encode($QUOT_PRODS, JSON_UNESCAPED_UNICODE) ?>;
const _QUOT_DATA     = <?= json_encode((object)$QUOT_DATA, JSON_UNESCAPED_UNICODE) ?>;
const _COUNTRIES     = <?= json_encode($countriesList, JSON_UNESCAPED_UNICODE) ?>;
const _SVC_NEW_CUST  = 'service?urlstring=<?= EncryptURL('action=CreateQuoteCustomer') ?>';
const _SVC_GET_ADDR  = 'service?urlstring=<?= EncryptURL('action=GetUserAddresses') ?>';
const _SVC_SAVE_ADDR = 'service?urlstring=<?= EncryptURL('action=SaveQuoteAddress') ?>';

/* Category options HTML */
const _CAT_OPTS_HTML = <?php
  // Build parent map first
  $parentMap = [];
  foreach ($categories as $cat) {
    if ((int)(float)($cat->PARENT_CATEGORY_ID ?? 0) === 0) {
      $parentMap[(int)(float)($cat->PRODUCT_CATEGORY_ID ?? 0)] = (string)($cat->PRODUCT_CATEGORY_NAME ?? '');
    }
  }
  $html = '<option value="">— Select Sub-Category —</option>';
  // Group subcategories under parent
  $grouped = [];
  foreach ($categories as $cat) {
    $pid = (int)(float)($cat->PARENT_CATEGORY_ID ?? 0);
    if ($pid > 0) {
      $grouped[$pid][] = $cat;
    }
  }
  foreach ($grouped as $pid => $subs) {
    $parentName = $parentMap[$pid] ?? 'Other';
    $html .= '<optgroup label="'.htmlspecialchars($parentName).'">';
    foreach ($subs as $sub) {
      $cid = (int)(float)($sub->PRODUCT_CATEGORY_ID ?? 0);
      $html .= '<option value="'.$cid.'">'.htmlspecialchars((string)($sub->PRODUCT_CATEGORY_NAME ?? '')).'</option>';
    }
    $html .= '</optgroup>';
  }
  echo json_encode($html);
?>;

/* ── Tab system ── */
const _qTabs = ['customer','address','products','summary'];
let _qCurTab = 'customer';

function switchQTab(tab) {
  _qTabs.forEach(t => {
    document.getElementById('qt-'+t).style.display = t===tab ? '' : 'none';
  });
  document.querySelectorAll('#quotTabBar .prod-tab').forEach(b => {
    b.classList.toggle('active', b.dataset.qtab === tab);
  });
  _qCurTab = tab;
  _updateQNavBtns(tab);
  if (tab === 'summary') _refreshSummary();
}

function _updateQNavBtns(tab) {
  const idx = _qTabs.indexOf(tab);
  document.getElementById('qPrevBtn').style.display = idx === 0 ? 'none' : '';
  document.getElementById('qNextBtn').style.display = idx === _qTabs.length-1 ? 'none' : '';
  document.getElementById('qSaveBtn').style.display = idx === _qTabs.length-1 ? '' : 'none';
}

function nextQTab() { const i=_qTabs.indexOf(_qCurTab); if(i<_qTabs.length-1) switchQTab(_qTabs[i+1]); }
function prevQTab() { const i=_qTabs.indexOf(_qCurTab); if(i>0) switchQTab(_qTabs[i-1]); }

/* ── Open New Quotation modal ── */
function openNewQuotModal() {
  _quotInitModal(0);
}

/* ── Open Edit Quotation modal ── */
function openQuotEditModal(qid) {
  _quotInitModal(qid);
}

function _quotInitModal(qid) {
  const form = document.getElementById('quotForm');
  form.action = 'service?urlstring=<?= EncryptURL('action=SaveQuotation') ?>';
  form.reset();
  document.getElementById('qfId').value        = qid;
  document.getElementById('qfUserId').value    = 0;
  document.getElementById('qfAddrId').value    = 0;
  document.getElementById('qfBilAddrId').value = 0;
  custTsClear();
  _resetAddrPanels();
  document.getElementById('qVatPct').value    = 19;
  document.getElementById('qVatNumber').value = '';
  document.getElementById('qVatNumBadge').style.display = 'none';
  document.getElementById('qShip').value    = 0;
  document.getElementById('quotProdRows').innerHTML = '';
  document.getElementById('quotModalTitle').textContent = qid > 0 ? 'Edit Quotation' : 'New Quotation';
  document.getElementById('qDuplicateWrap').style.display = qid > 0 ? 'block' : 'none';
  document.getElementById('qfIsDuplicate').value = '0';
  switchQTab('customer');

  if (qid > 0) {
    const q = _QUOT_DATA[String(qid)];
    if (q) {
      // Set customer typesearch display
      if (q.user_id > 0) {
        document.getElementById('qfUserId').value = q.user_id;
        _showCustChip(q.user_name || '', q.user_email || '', q.company || '');
        // Load this user's addresses, then pre-select stored IDs
        _loadUserAddresses(q.user_id, q.addr_id || 0, q.bil_id || 0);
      }
      document.getElementById('qfCustOrd').value = q.cust_ord || '';
      document.getElementById('qfCustSup').value = q.cust_sup || '';
      document.getElementById('qfStatus').value  = q.status   || 'Quotation Pending';
      const savedVatNum = q.vat_number || '';
      document.getElementById('qVatNumber').value = savedVatNum;
      // If vat_number was saved, honour stored vat_pct (likely 0); else default 19
      document.getElementById('qVatPct').value = (q.vat_pct !== undefined && q.vat_pct !== null)
        ? q.vat_pct : (savedVatNum ? 0 : 19);
      onVatNumberInput(savedVatNum);
      document.getElementById('qShip').value     = q.ship_amt || 0;
    }
    const prods = QUOT_PRODUCTS[String(qid)] || [];
    prods.length ? prods.forEach(p => addQuotProdRow(p)) : addQuotProdRow();
  } else {
    addQuotProdRow();
  }
  recalcTotals();
  openModal('quotModal');
}

document.addEventListener('DOMContentLoaded', function() { quotInit(); });

/* ════════════════════════════════════════
   CUSTOMER TYPESEARCH
════════════════════════════════════════ */
function custTsOpen() {
  document.getElementById('custTsDrop').classList.add('open');
  custTsFilter();
  setTimeout(() => document.addEventListener('click', _custTsOutside, true), 0);
}
function _custTsOutside(e) {
  const wrap = document.getElementById('custTsWrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('custTsDrop').classList.remove('open');
    document.removeEventListener('click', _custTsOutside, true);
  }
}
function custTsFilter() {
  const q    = (document.getElementById('custTsInput').value || '').toLowerCase().trim();
  const opts = document.querySelectorAll('#custTsDrop .cust-ts-opt');
  let shown  = 0;
  opts.forEach(o => {
    const match = !q || (o.dataset.label || '').includes(q);
    o.style.display = match ? '' : 'none';
    if (match) shown++;
  });
  const none = document.getElementById('custTsNone');
  if (none) none.style.display = shown === 0 ? '' : 'none';
  document.getElementById('custTsDrop').classList.add('open');
}
function custTsSelect(optEl) {
  const uid  = optEl.dataset.uid     || '0';
  const name = optEl.dataset.name    || '';
  document.getElementById('custTsInput').value = name + (optEl.dataset.email ? ' — ' + optEl.dataset.email : '');
  document.getElementById('custTsClear').classList.add('visible');
  document.getElementById('custTsDrop').classList.remove('open');
  document.removeEventListener('click', _custTsOutside, true);
  document.getElementById('qfUserId').value = uid;
  _showCustChip(name, optEl.dataset.email || '', optEl.dataset.company || '');
  _loadUserAddresses(parseInt(uid), 0, 0);
}
function custTsClear() {
  document.getElementById('custTsInput').value = '';
  document.getElementById('custTsClear').classList.remove('visible');
  document.getElementById('custTsDrop').classList.remove('open');
  document.removeEventListener('click', _custTsOutside, true);
  document.getElementById('qfUserId').value    = 0;
  document.getElementById('qfAddrId').value    = 0;
  document.getElementById('qfBilAddrId').value = 0;
  document.querySelectorAll('#custTsDrop .cust-ts-opt').forEach(o => o.style.display = '');
  const none = document.getElementById('custTsNone');
  if (none) none.style.display = 'none';
  _hideCustChip();
  _resetAddrPanels();
}
function _showCustChip(name, email, company) {
  const chip = document.getElementById('custSelectedChip');
  document.getElementById('custChipName').textContent = name;
  document.getElementById('custChipMeta').textContent =
    [email, company].filter(Boolean).join(' · ');
  chip.style.display = 'flex';
  // Hide the input row
  document.getElementById('custTsWrap').style.display = 'none';
}
function _hideCustChip() {
  document.getElementById('custSelectedChip').style.display = 'none';
  document.getElementById('custTsWrap').style.display = '';
}

/* ── New Customer inline form ── */
function toggleNewCustForm() {
  const f = document.getElementById('newCustForm');
  f.style.display = f.style.display === 'none' ? '' : 'none';
  document.getElementById('ncError').style.display   = 'none';
  document.getElementById('ncSpinner').style.display = 'none';
}
async function submitNewCustomer() {
  const name  = document.getElementById('ncName').value.trim();
  const email = document.getElementById('ncEmail').value.trim();
  if (!name || !email) {
    document.getElementById('ncError').textContent    = 'Name and email are required.';
    document.getElementById('ncError').style.display  = '';
    return;
  }
  document.getElementById('ncSpinner').style.display = '';
  document.getElementById('ncError').style.display   = 'none';
  const body = new URLSearchParams({
    name, email,
    phone:        document.getElementById('ncPhone').value.trim(),
    phone_isd:    document.getElementById('ncIsd').value,
    company_name: document.getElementById('ncCompany').value.trim(),
    designation:  document.getElementById('ncDesig').value.trim(),
  });
  try {
    const res  = await fetch(_SVC_NEW_CUST, {method:'POST', body});
    const data = await res.json();
    document.getElementById('ncSpinner').style.display = 'none';
    if (data.success) {
      // Auto-select the new customer
      document.getElementById('qfUserId').value = data.user_id;
      _showCustChip(data.user_name, data.user_email, data.company || '');
      toggleNewCustForm();
      _loadUserAddresses(data.user_id, 0, 0);
      // Also inject this customer into the typesearch list for future use
      const opt = document.createElement('div');
      opt.className = 'cust-ts-opt';
      opt.tabIndex  = 0;
      opt.setAttribute('data-uid',     data.user_id);
      opt.setAttribute('data-name',    data.user_name);
      opt.setAttribute('data-email',   data.user_email);
      opt.setAttribute('data-phone',   data.user_phone || '');
      opt.setAttribute('data-isd',     data.phone_isd  || 91);
      opt.setAttribute('data-company', data.company    || '');
      opt.setAttribute('data-label',   (data.user_name + ' ' + data.user_email + ' ' + (data.company||'')).toLowerCase());
      opt.onclick = function() { custTsSelect(this); };
      opt.innerHTML = '<span class="cust-ts-name">'+ data.user_name +'</span>'
                    + '<span class="cust-ts-email">'+ data.user_email +'</span>';
      document.getElementById('custTsDrop').prepend(opt);
    } else {
      document.getElementById('ncError').textContent   = data.msg || 'Error creating customer.';
      document.getElementById('ncError').style.display = '';
    }
  } catch(e) {
    document.getElementById('ncSpinner').style.display = 'none';
    document.getElementById('ncError').textContent    = 'Network error. Please try again.';
    document.getElementById('ncError').style.display  = '';
  }
}

/* ════════════════════════════════════════
   ADDRESS PANELS
════════════════════════════════════════ */
let _addrData = []; // loaded addresses for current user

function _resetAddrPanels() {
  _addrData = [];
  document.getElementById('addrNoCustomer').style.display = '';
  document.getElementById('addrPanels').style.display     = 'none';
  document.getElementById('delAddrSelector').innerHTML    = '';
  document.getElementById('delAddrCard').style.display    = 'none';
  document.getElementById('delAddrCard').innerHTML        = '';
  document.getElementById('delAddNewBtn').style.display   = 'none';
  document.getElementById('delAddrForm').style.display    = 'none';
  document.getElementById('delAddrForm').innerHTML        = '';
  document.getElementById('bilAddrSelector').innerHTML    = '';
  document.getElementById('bilAddrCard').style.display    = 'none';
  document.getElementById('bilAddrCard').innerHTML        = '';
  document.getElementById('bilAddNewBtn').style.display   = 'none';
  document.getElementById('bilAddrForm').style.display    = 'none';
  document.getElementById('bilAddrForm').innerHTML        = '';
  document.getElementById('bilSameAsDel').checked         = true;
  document.getElementById('bilAddrWrap').style.display    = 'none';
  document.getElementById('bilSameNote').style.display    = '';
  document.getElementById('qfAddrId').value               = 0;
  document.getElementById('qfBilAddrId').value            = 0;
}

async function _loadUserAddresses(userId, preDelId, preBilId) {
  document.getElementById('addrNoCustomer').style.display = 'none';
  document.getElementById('addrPanels').style.display     = '';
  // Update manage link
  const mlink = document.getElementById('addrManageLink');
  if (mlink) mlink.href = 'customer-address?user_id=' + userId;

  document.getElementById('delAddrSelector').innerHTML = '<div style="font-size:12px;color:var(--text-muted);padding:8px 0;">Loading addresses…</div>';
  try {
    const res   = await fetch(_SVC_GET_ADDR, {method:'POST', body: new URLSearchParams({user_id: userId})});
    _addrData   = await res.json();
  } catch(e) { _addrData = []; }

  _renderAddrSelector('del', _addrData, preDelId);
  _renderAddrSelector('bil', _addrData, preBilId > 0 ? preBilId : preDelId);
  // Show add-new buttons
  document.getElementById('delAddNewBtn').style.display = '';
  document.getElementById('bilAddNewBtn').style.display = '';
}

function _renderAddrSelector(panel, addrs, preSelId) {
  const selEl  = document.getElementById(panel + 'AddrSelector');
  const cardEl = document.getElementById(panel + 'AddrCard');
  if (!addrs.length) {
    selEl.innerHTML = '<div style="font-size:12px;color:var(--text-muted);padding:4px 0;margin-bottom:8px;">No saved addresses. Add one below.</div>';
    cardEl.style.display = 'none';
    return;
  }
  let html = '<select class="addr-sel" onchange="_onAddrSelChange(this,\''+panel+'\')">';
  html    += '<option value="0">— Select address —</option>';
  addrs.forEach(a => {
    const sel = a.id === preSelId ? ' selected' : '';
    html += '<option value="'+a.id+'"'+sel+'>'+_addrLabel(a)+' — '+_addrShort(a)+'</option>';
  });
  html += '</select>';
  selEl.innerHTML = html;

  if (preSelId > 0) {
    const found = addrs.find(a => a.id === preSelId);
    if (found) { _showAddrCard(panel, found); _setAddrId(panel, found.id); }
  }
}

function _onAddrSelChange(sel, panel) {
  const id    = parseInt(sel.value);
  const cardEl= document.getElementById(panel+'AddrCard');
  if (!id) { cardEl.style.display = 'none'; _setAddrId(panel, 0); return; }
  const addr  = _addrData.find(a => a.id === id);
  if (addr) { _showAddrCard(panel, addr); _setAddrId(panel, id); }
}

function _showAddrCard(panel, addr) {
  const cardEl = document.getElementById(panel + 'AddrCard');
  cardEl.innerHTML =
    '<span class="addr-card-label">' + (addr.label||'Home') + '</span><br>'
  + (addr.name    ? '<span class="addr-card-name">'+ _esc(addr.name) +'</span><br>' : '')
  + (addr.company ? '<span class="addr-card-line">'+ _esc(addr.company) +'</span><br>' : '')
  + (addr.address ? '<span class="addr-card-line">'+ _esc(addr.address) +'</span><br>' : '')
  + (addr.city||addr.state ? '<span class="addr-card-line">'+ _esc([addr.city,addr.state].filter(Boolean).join(', '))+'</span><br>' : '')
  + (addr.country ? '<span class="addr-card-line">'+ _esc(addr.country) +'</span><br>' : '')
  + (addr.zip     ? '<span class="addr-card-line">'+ _esc(addr.zip) +'</span>' : '');
  cardEl.style.display = '';
}

function _setAddrId(panel, id) {
  if (panel === 'del') {
    document.getElementById('qfAddrId').value = id;
    if (document.getElementById('bilSameAsDel').checked) {
      document.getElementById('qfBilAddrId').value = id;
    }
  } else {
    document.getElementById('qfBilAddrId').value = id;
  }
}

function toggleBilSame() {
  const same    = document.getElementById('bilSameAsDel').checked;
  document.getElementById('bilAddrWrap').style.display = same ? 'none' : '';
  document.getElementById('bilSameNote').style.display = same ? ''     : 'none';
  if (same) {
    document.getElementById('qfBilAddrId').value = document.getElementById('qfAddrId').value;
  }
}

/* ── Address add-new inline form ── */
let _addrFormPanel = '';
function showAddrForm(panel) {
  _addrFormPanel = panel;
  const formEl   = document.getElementById(panel + 'AddrForm');
  formEl.style.display = '';
  formEl.innerHTML = _buildAddrFormHtml(panel);
  document.getElementById(panel + 'AddNewBtn').style.display = 'none';
}

function _buildAddrFormHtml(panel) {
  // Build country options
  let ctryOpts = '<option value="0">— Select Country —</option>';
  _COUNTRIES.forEach(c => {
    const sel = c.name === 'India' ? ' selected' : '';
    ctryOpts += '<option value="'+c.id+'"'+sel+'>'+_esc(c.name)+'</option>';
  });

  return `
  <div style="font-weight:600;font-size:12px;color:var(--text-muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.04em;">New Address</div>
  <!-- Label toggle -->
  <div class="fg" style="margin-bottom:12px;">
    <label>Address Label</label>
    <div class="addr-label-toggle" id="${panel}LabelToggle">
      <button type="button" class="addr-label-btn active" data-lv="Home"   onclick="setAddrLabel('${panel}','Home')">🏠 Home</button>
      <button type="button" class="addr-label-btn"        data-lv="Office" onclick="setAddrLabel('${panel}','Office')">🏢 Office</button>
      <button type="button" class="addr-label-btn"        data-lv="Other"  onclick="setAddrLabel('${panel}','Other')">📍 Other</button>
    </div>
    <input type="hidden" id="${panel}AddrLabel" value="Home">
  </div>
  <div class="form-row cols-2" style="margin-bottom:12px;">
    <div class="fg"><label>Country <span class="req">*</span></label>
      <select id="${panel}AddrCountry" class="form-control" style="height:38px;" onchange="_onAddrCountryChange('${panel}',this)">
        ${ctryOpts}
      </select>
    </div>
    <div class="fg"><label>Full Name / Company Name <span class="req">*</span></label>
      <input type="text" id="${panel}AddrCompany" class="form-control" placeholder="Full name or company name">
    </div>
  </div>
  <div class="form-row cols-2" style="margin-bottom:12px;">
    <div class="fg"><label>Contact Name <span class="req">*</span></label>
      <input type="text" id="${panel}AddrName" class="form-control" placeholder="Contact person name">
    </div>
    <div class="fg"><label>Phone Number <span class="req">*</span></label>
      <div style="display:flex;gap:6px;">
        <select id="${panel}AddrMcc" class="form-control" style="width:80px;flex-shrink:0;height:38px;">
          <option value="91">+91</option><option value="1">+1</option>
          <option value="44">+44</option><option value="971">+971</option>
          <option value="966">+966</option><option value="65">+65</option>
          <option value="60">+60</option><option value="49">+49</option>
          <option value="33">+33</option><option value="61">+61</option>
        </select>
        <input type="text" id="${panel}AddrPhone" class="form-control" placeholder="Phone number">
      </div>
    </div>
  </div>
  <div class="fg" style="margin-bottom:12px;">
    <label>Address Line 1 <span class="req">*</span></label>
    <input type="text" id="${panel}AddrLine1" class="form-control" placeholder="Street name and number">
  </div>
  <div class="fg" style="margin-bottom:12px;">
    <label>Address Line 2</label>
    <input type="text" id="${panel}AddrLine2" class="form-control" placeholder="Apartment, suite, floor, unit">
  </div>
  <div class="fg" style="margin-bottom:12px;">
    <label>Address Line 3</label>
    <input type="text" id="${panel}AddrLine3" class="form-control" placeholder="Landmark, area, locality">
  </div>
  <div class="form-row cols-2" style="margin-bottom:12px;">
    <div class="fg"><label>Postal Code <span class="req">*</span></label>
      <input type="text" id="${panel}AddrZip" class="form-control" placeholder="6-digit postal code">
    </div>
    <div class="fg"><label>City <span class="req">*</span></label>
      <input type="text" id="${panel}AddrCity" class="form-control" placeholder="City">
    </div>
  </div>
  <div class="fg" style="margin-bottom:12px;">
    <label>State / Region</label>
    <input type="text" id="${panel}AddrState" class="form-control" placeholder="State or region">
  </div>
  <div class="fg" style="margin-bottom:14px;">
    <label>Additional Address Information</label>
    <textarea id="${panel}AddrLandmark" class="form-control" rows="2" placeholder="Special delivery instructions, gate code, access notes…" style="resize:vertical;"></textarea>
  </div>
  <!-- Recipient Details -->
  <details style="margin-bottom:14px;">
    <summary style="font-size:12px;font-weight:600;cursor:pointer;color:var(--text-muted);padding:6px 0;">
      Recipient Details <span style="font-weight:400;">(Optional)</span>
    </summary>
    <div style="padding-top:12px;">
      <div class="form-row cols-2" style="margin-bottom:12px;">
        <div class="fg"><label>Name</label>
          <input type="text" id="${panel}AddrRecName" class="form-control" placeholder="Recipient name">
        </div>
        <div class="fg"><label>Email</label>
          <input type="email" id="${panel}AddrRecEmail" class="form-control" placeholder="email@example.com">
        </div>
      </div>
      <div class="fg"><label>Contact Number</label>
        <input type="text" id="${panel}AddrRecPhone" class="form-control" placeholder="Phone number">
      </div>
    </div>
  </details>
  <input type="hidden" id="${panel}AddrCountryId" value="101">
  <input type="hidden" id="${panel}AddrCountryName" value="India">
  <div style="display:flex;gap:8px;align-items:center;">
    <button type="button" class="btn btn--primary" style="font-size:12px;height:34px;" onclick="submitAddrForm('${panel}')">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg>
      Save Address
    </button>
    <button type="button" class="btn btn--ghost" style="font-size:12px;height:34px;" onclick="cancelAddrForm('${panel}')">Cancel</button>
    <span id="${panel}AddrSpinner" style="display:none;font-size:12px;color:var(--text-muted);">Saving…</span>
    <span id="${panel}AddrError"   style="display:none;font-size:12px;color:#ef4444;"></span>
  </div>`;
}

function _onAddrCountryChange(panel, sel) {
  const opt = sel.options[sel.selectedIndex];
  document.getElementById(panel+'AddrCountryId').value   = opt.value;
  document.getElementById(panel+'AddrCountryName').value = opt.text;
}

function setAddrLabel(panel, val) {
  document.getElementById(panel+'AddrLabel').value = val;
  document.querySelectorAll('#'+panel+'LabelToggle .addr-label-btn').forEach(b => {
    b.classList.toggle('active', b.dataset.lv === val);
  });
}

async function submitAddrForm(panel) {
  const uid  = parseInt(document.getElementById('qfUserId').value) || 0;
  const name = (document.getElementById(panel+'AddrName').value    || '').trim();
  const addr = (document.getElementById(panel+'AddrLine1').value   || '').trim();
  const city = (document.getElementById(panel+'AddrCity').value    || '').trim();
  const zip  = (document.getElementById(panel+'AddrZip').value     || '').trim();
  if (!uid)  { document.getElementById(panel+'AddrError').textContent='No customer selected.'; document.getElementById(panel+'AddrError').style.display=''; return; }
  if (!name) { document.getElementById(panel+'AddrError').textContent='Contact name is required.'; document.getElementById(panel+'AddrError').style.display=''; return; }
  if (!addr) { document.getElementById(panel+'AddrError').textContent='Address Line 1 is required.'; document.getElementById(panel+'AddrError').style.display=''; return; }
  if (!city) { document.getElementById(panel+'AddrError').textContent='City is required.'; document.getElementById(panel+'AddrError').style.display=''; return; }
  if (!zip)  { document.getElementById(panel+'AddrError').textContent='Postal code is required.'; document.getElementById(panel+'AddrError').style.display=''; return; }

  document.getElementById(panel+'AddrSpinner').style.display = '';
  document.getElementById(panel+'AddrError').style.display   = 'none';

  const body = new URLSearchParams({
    user_id:           uid,
    label:             document.getElementById(panel+'AddrLabel').value,
    addr_user_name:    name,
    addr_company_name: document.getElementById(panel+'AddrCompany').value.trim(),
    delivery_phone_no: document.getElementById(panel+'AddrPhone').value.trim(),
    mobile_country_code: document.getElementById(panel+'AddrMcc').value,
    address:           addr,
    address_line_one:  document.getElementById(panel+'AddrLine2').value.trim(),
    address_line_two:  document.getElementById(panel+'AddrLine3').value.trim(),
    landmark:          document.getElementById(panel+'AddrLandmark').value.trim(),
    city,
    state:             document.getElementById(panel+'AddrState').value.trim(),
    zip,
    country_id:        document.getElementById(panel+'AddrCountryId').value,
    country:           document.getElementById(panel+'AddrCountryName').value,
    recipient_name:    (document.getElementById(panel+'AddrRecName')  || {}).value || '',
    recipient_email:   (document.getElementById(panel+'AddrRecEmail') || {}).value || '',
    recipient_contact: (document.getElementById(panel+'AddrRecPhone') || {}).value || '',
  });

  try {
    const res  = await fetch(_SVC_SAVE_ADDR, {method:'POST', body});
    const data = await res.json();
    document.getElementById(panel+'AddrSpinner').style.display = 'none';
    if (data.success) {
      // Add to _addrData and re-render selector with new address pre-selected
      const newAddr = {id:data.id, label:data.label, name:data.name||name, company:data.company||'',
                       address:data.address||addr, city:data.city||city, state:data.state||'',
                       zip:data.zip||zip, country:data.country||''};
      _addrData.push(newAddr);
      _renderAddrSelector(panel, _addrData, data.id);
      _setAddrId(panel, data.id);
      document.getElementById(panel+'AddrForm').style.display   = 'none';
      document.getElementById(panel+'AddNewBtn').style.display  = '';
    } else {
      document.getElementById(panel+'AddrError').textContent   = data.msg || 'Error saving address.';
      document.getElementById(panel+'AddrError').style.display = '';
    }
  } catch(e) {
    document.getElementById(panel+'AddrSpinner').style.display = 'none';
    document.getElementById(panel+'AddrError').textContent     = 'Network error. Please try again.';
    document.getElementById(panel+'AddrError').style.display   = '';
  }
}

function cancelAddrForm(panel) {
  document.getElementById(panel+'AddrForm').style.display  = 'none';
  document.getElementById(panel+'AddNewBtn').style.display = '';
}

function _addrLabel(a) { return a.label || 'Home'; }
function _addrShort(a) {
  return [a.address, a.city, a.state, a.country].filter(Boolean).slice(0,3).join(', ');
}
function _esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Product rows ── */
let _qpIdx = 0;

function addQuotProdRow(data) {
  const idx = _qpIdx++;
  const row = document.createElement('div');
  row.className = 'qp-row';
  row.innerHTML = `
    <div>
      <span class="qp-lbl">Category</span>
      <select name="prod_cat_id[]" class="form-control qp-cat" style="height:34px;font-size:12px;" onchange="loadQPProds(this,${idx})">${_CAT_OPTS_HTML}</select>
    </div>
    <div>
      <span class="qp-lbl">Product</span>
      <select name="prod_prod_id[]" class="form-control qp-prod" id="qp-prod-${idx}" style="height:34px;font-size:12px;" onchange="onQPProdSel(this,${idx})">
        <option value="">— Select Product —</option>
      </select>
      <div id="qp-stock-${idx}" style="min-height:15px;margin-top:2px;"></div>
    </div>
    <div>
      <span class="qp-lbl">Qty</span>
      <input type="number" name="prod_qty[]" class="form-control qp-qty" id="qp-qty-${idx}" min="1" value="1" style="height:34px;font-size:12px;" oninput="recalcTotals()">
    </div>
    <div>
      <span class="qp-lbl">Unit Price (€)</span>
      <input type="number" name="prod_price[]" class="form-control qp-price" id="qp-price-${idx}" min="0" step="0.01" value="0" style="height:34px;font-size:12px;" oninput="recalcTotals()">
    </div>
    <div>
      <span class="qp-lbl">Disc %</span>
      <input type="number" name="prod_disc[]" class="form-control qp-disc" id="qp-disc-${idx}" min="0" max="100" step="0.01" value="0" style="height:34px;font-size:12px;" oninput="recalcTotals()">
    </div>
    <div>
      <span class="qp-lbl">Final Amt</span>
      <span class="qp-amt" id="qp-amt-${idx}">€0.00</span>
    </div>
    <div class="qp-rm">
      <button type="button" onclick="removeQPRow(this)" style="background:none;border:none;cursor:pointer;color:#ef4444;font-size:22px;line-height:1;padding:0;">×</button>
    </div>
  `;
  document.getElementById('quotProdRows').appendChild(row);

  if (data) {
    const catSel = row.querySelector('.qp-cat');
    if (data.cat_id) {
      catSel.value = data.cat_id;
      _fillProdSel(row.querySelector('.qp-prod'), data.cat_id);
    }
    setTimeout(() => {
      if (data.prod_id) row.querySelector('.qp-prod').value = data.prod_id;
      if (data.qty)     document.getElementById('qp-qty-'   + idx).value = data.qty;
      if (data.price  !== undefined) document.getElementById('qp-price-' + idx).value = Number(data.price).toFixed(2);
      if (data.disc_pct !== undefined) document.getElementById('qp-disc-'  + idx).value = Number(data.disc_pct).toFixed(2);
      recalcTotals();
    }, 10);
  }
  recalcTotals();
}

function removeQPRow(btn) {
  const c = document.getElementById('quotProdRows');
  if (c.querySelectorAll('.qp-row').length <= 1) return;
  btn.closest('.qp-row').remove();
  recalcTotals();
}

function _fillProdSel(sel, catId) {
  sel.innerHTML = '<option value="">— Select Product —</option>';
  (CAT_PROD_MAP[catId] || []).forEach(p => {
    const o = document.createElement('option');
    o.value = p.id;
    o.textContent = p.name + (p.code ? ' (' + p.code + ')' : '');
    o.dataset.price = p.price;
    o.dataset.stock = p.stock;
    sel.appendChild(o);
  });
}

function loadQPProds(catSel, idx) {
  _fillProdSel(document.getElementById('qp-prod-'+idx), catSel.value);
  const stockEl = document.getElementById('qp-stock-'+idx);
  if (stockEl) stockEl.textContent = '';
  recalcTotals();
}

function onQPProdSel(prodSel, idx) {
  const opt   = prodSel.options[prodSel.selectedIndex];
  const price = parseFloat(opt.dataset.price || 0);
  const stock = parseInt(opt.dataset.stock   ?? -1);
  if (price > 0) document.getElementById('qp-price-'+idx).value = price.toFixed(2);
  const stockEl = document.getElementById('qp-stock-'+idx);
  if (stockEl) {
    if (!prodSel.value) { stockEl.textContent = ''; }
    else if (stock <= 0) { stockEl.innerHTML = '<span style="color:#dc2626;font-size:11px;">Out of Stock</span>'; }
    else { stockEl.innerHTML = '<span style="color:#059669;font-size:11px;">In Stock: '+stock+'</span>'; }
  }
  recalcTotals();
}

/* ── Totals ── */
function recalcTotals() {
  let sub = 0, totalDisc = 0;
  document.querySelectorAll('#quotProdRows .qp-row').forEach(r => {
    const qty     = parseFloat(r.querySelector('.qp-qty').value)   || 0;
    const prc     = parseFloat(r.querySelector('.qp-price').value) || 0;
    const discPct = parseFloat(r.querySelector('.qp-disc')?.value  || 0);
    const gross   = qty * prc;
    const disc    = gross * discPct / 100;
    const final   = gross - disc;
    const amtEl   = r.querySelector('.qp-amt');
    if (amtEl) amtEl.textContent = '€' + final.toFixed(2);
    sub       += gross;
    totalDisc += disc;
  });
  const netSub  = sub - totalDisc;
  const vatPct  = parseFloat(document.getElementById('qVatPct').value) || 0;
  const shipAmt = parseFloat(document.getElementById('qShip').value)   || 0;
  const vatAmt  = netSub * vatPct / 100;
  const grand   = netSub + vatAmt + shipAmt;
  document.getElementById('qSub').textContent      = sub.toFixed(2);
  document.getElementById('qDiscAmt').textContent  = totalDisc.toFixed(2);
  document.getElementById('qVat').textContent      = vatAmt.toFixed(2);
  document.getElementById('qShipDisp').textContent = shipAmt.toFixed(2);
  document.getElementById('qGrand').textContent    = grand.toFixed(2);
  document.getElementById('qfVatAmt').value   = vatAmt.toFixed(2);
  document.getElementById('qfShipAmt').value  = shipAmt.toFixed(2);
  document.getElementById('qfDiscAmt').value  = totalDisc.toFixed(2);
  document.getElementById('qfTotalAmt').value = grand.toFixed(2);
}

function onVatNumberInput(val) {
  const hasVat = val.trim() !== '';
  const badge  = document.getElementById('qVatNumBadge');
  const vatPct = document.getElementById('qVatPct');
  badge.style.display = hasVat ? 'inline-block' : 'none';
  if (hasVat) {
    vatPct.value = 0;
  } else if (parseFloat(vatPct.value) === 0) {
    // Only restore default when going from exempt back to blank
    vatPct.value = 19;
  }
  recalcTotals();
}

function _refreshSummary() {
  recalcTotals();
  let html = `<table style="width:100%;border-collapse:collapse;font-size:12px;">
    <thead>
      <tr style="background:#f8fafc;border-bottom:2px solid var(--border);">
        <th style="padding:8px 10px;text-align:left;font-weight:600;">#</th>
        <th style="padding:8px 10px;text-align:left;font-weight:600;">Product</th>
        <th style="padding:8px 10px;text-align:left;font-weight:600;">Category</th>
        <th style="padding:8px 10px;text-align:center;font-weight:600;">Qty</th>
        <th style="padding:8px 10px;text-align:right;font-weight:600;">Unit Price</th>
        <th style="padding:8px 10px;text-align:right;font-weight:600;">Amount</th>
        <th style="padding:8px 10px;text-align:center;font-weight:600;">Disc %</th>
        <th style="padding:8px 10px;text-align:right;font-weight:600;">Disc Amt</th>
        <th style="padding:8px 10px;text-align:right;font-weight:600;">Final Amt</th>
      </tr>
    </thead><tbody>`;
  let sn = 1;
  document.querySelectorAll('#quotProdRows .qp-row').forEach(r => {
    const prodSel  = r.querySelector('.qp-prod');
    const catSel   = r.querySelector('.qp-cat');
    if (!prodSel.value) return;
    const name     = prodSel.options[prodSel.selectedIndex]?.textContent || '—';
    const catName  = catSel.options[catSel.selectedIndex]?.textContent   || '—';
    const qty      = parseInt(r.querySelector('.qp-qty').value)           || 0;
    const prc      = parseFloat(r.querySelector('.qp-price').value)       || 0;
    const discPct  = parseFloat(r.querySelector('.qp-disc')?.value        || 0);
    const gross    = qty * prc;
    const discAmt  = gross * discPct / 100;
    const finalAmt = gross - discAmt;
    html += `<tr style="border-bottom:1px solid var(--border);">
      <td style="padding:7px 10px;color:var(--text-muted);">${sn++}</td>
      <td style="padding:7px 10px;font-weight:500;">${name}</td>
      <td style="padding:7px 10px;color:var(--text-muted);font-size:11px;">${catName}</td>
      <td style="padding:7px 10px;text-align:center;">${qty}</td>
      <td style="padding:7px 10px;text-align:right;">€${prc.toFixed(2)}</td>
      <td style="padding:7px 10px;text-align:right;">€${gross.toFixed(2)}</td>
      <td style="padding:7px 10px;text-align:center;">${discPct > 0 ? discPct+'%' : '—'}</td>
      <td style="padding:7px 10px;text-align:right;color:#dc2626;">${discAmt > 0 ? '−€'+discAmt.toFixed(2) : '—'}</td>
      <td style="padding:7px 10px;text-align:right;font-weight:700;color:var(--primary);">€${finalAmt.toFixed(2)}</td>
    </tr>`;
  });
  html += '</tbody></table>';
  document.getElementById('qSummaryProdList').innerHTML = html;
}

function submitQuotForm() {
  recalcTotals();
  const uid = parseInt(document.getElementById('qfUserId').value) || 0;
  if (!uid) {
    switchQTab('customer');
    alert('Please select or create a customer first.'); return;
  }
  const addrId = parseInt(document.getElementById('qfAddrId').value) || 0;
  if (!addrId) {
    switchQTab('address');
    alert('Please select a delivery address.'); return;
  }
  // Billing: if "same as delivery" is checked, copy delivery addr id
  if (document.getElementById('bilSameAsDel').checked) {
    document.getElementById('qfBilAddrId').value = addrId;
  }
  let hasProd = false;
  document.querySelectorAll('#quotProdRows .qp-prod').forEach(s => { if(s.value) hasProd=true; });
  if (!hasProd) { switchQTab('products'); alert('At least one product must be selected.'); return; }
  document.getElementById('quotForm').submit();
}

function duplicateAsNewQuot() {
  recalcTotals();
  const uid = parseInt(document.getElementById('qfUserId').value) || 0;
  if (!uid) { switchQTab('customer'); alert('Please select or create a customer first.'); return; }
  const addrId = parseInt(document.getElementById('qfAddrId').value) || 0;
  if (!addrId) { switchQTab('address'); alert('Please select a delivery address.'); return; }
  if (document.getElementById('bilSameAsDel').checked) {
    document.getElementById('qfBilAddrId').value = addrId;
  }
  let hasProd = false;
  document.querySelectorAll('#quotProdRows .qp-prod').forEach(s => { if(s.value) hasProd=true; });
  if (!hasProd) { switchQTab('products'); alert('At least one product must be selected.'); return; }
  if (!confirm('This will create a brand-new quotation with the current data. Continue?')) return;
  // Clear the ID so the backend creates a fresh record
  document.getElementById('qfId').value        = '0';
  document.getElementById('qfIsDuplicate').value = '1';
  document.getElementById('quotForm').submit();
}

/* ── Status modal ── */
function onStatusChange(val) {
  const wrap = document.getElementById('sfCancelWrap');
  const btn  = document.getElementById('sfSubmitBtn');
  const isCancel = val === 'Quotation Cancel';
  wrap.style.display = isCancel ? 'block' : 'none';
  btn.textContent    = isCancel ? 'Cancel Quotation' : 'Update Status';
  btn.className      = isCancel ? 'btn btn--danger'   : 'btn btn--primary';
  if (!isCancel) document.getElementById('sfRemark').value = '';
}

function openStatusModal(qid, st) {
  document.getElementById('sfQid').value    = qid;
  document.getElementById('sfStatus').value = st;
  document.getElementById('sfRemark').value = '';
  onStatusChange(st);
  openModal('statusModal');
}

/* ── Delete modal ── */
function openDeleteQuotModal(qid, ref) {
  document.getElementById('dqfId').value      = qid;
  document.getElementById('delQuotRef').textContent = ref;
  openModal('delQuotModal');
}

/* ── Resend ── */
function resendQuotEmail(qid, email) {
  if (!confirm('Resend quotation email to ' + (email||'customer') + '?')) return;
  document.getElementById('resendQid').value = qid;
  document.getElementById('resendForm').submit();
}

/* ── Search / filter ── */
function quotOnSearch() {
  const q  = (document.getElementById('quotSearch').value||'').toLowerCase();
  const st = (document.getElementById('quotStatusF').value||'').toLowerCase();
  document.querySelectorAll('#quotTbody .quot-row').forEach(tr => {
    const ms = tr.dataset.search || '';
    const ts = (tr.dataset.status||'').toLowerCase();
    tr.style.display = (!q||ms.includes(q)) && (!st||ts===st) ? '' : 'none';
  });
  _quotPage = 1;
  quotRender();
}

/* ── Pagination ── */
let _quotRpp  = 20;
let _quotPage = 1;

function quotApplyRpp() {
  _quotRpp  = parseInt(document.getElementById('quotRpp').value) || 20;
  _quotPage = 1;
  quotRender();
}

function quotGoPage(p) { _quotPage = p; quotRender(); }

function quotRender() {
  const rows  = Array.from(document.querySelectorAll('#quotTbody .quot-row')).filter(r => r.style.display !== 'none');
  /* hide all first */
  document.querySelectorAll('#quotTbody .quot-row').forEach(r => { if(r.style.display!=='none') r.style.display='none'; });
  const total  = rows.length;
  const pages  = Math.max(1, Math.ceil(total / _quotRpp));
  _quotPage    = Math.min(Math.max(1,_quotPage), pages);
  const start  = (_quotPage-1)*_quotRpp;
  const end    = Math.min(start+_quotRpp, total);
  rows.slice(start,end).forEach(r => r.style.display='');

  const noRes = document.getElementById('quotNoResults');
  if (noRes) noRes.style.display = total===0 ? 'block':'none';

  const info = document.getElementById('quotPgInfo');
  if (info) info.textContent = total===0 ? 'No records found' : 'Showing '+(start+1)+'–'+end+' of '+total+' records';

  _quotRenderNav(_quotPage, pages);
}

function _quotRenderNav(cur, pages) {
  const nav = document.getElementById('quotNav');
  if (!nav) return;
  let h = '<button class="pg-btn" onclick="quotGoPage('+(cur-1)+')"'+(cur<=1?' disabled':'')+'>Prev</button>';
  _quotPageNums(cur,pages).forEach(p => {
    if (p==='...') h += '<span class="pg-dots">…</span>';
    else h += '<button class="pg-btn'+(p===cur?' pg-active':'')+'" onclick="quotGoPage('+p+')">'+p+'</button>';
  });
  h += '<button class="pg-btn" onclick="quotGoPage('+(cur+1)+')"'+(cur>=pages?' disabled':'')+'>Next</button>';
  nav.innerHTML = h;
}

function _quotPageNums(cur, pages) {
  if (pages<=7) { const a=[]; for(let i=1;i<=pages;i++) a.push(i); return a; }
  if (cur<=4)        return [1,2,3,4,5,'...',pages];
  if (cur>=pages-3)  return [1,'...',pages-4,pages-3,pages-2,pages-1,pages];
  return [1,'...',cur-1,cur,cur+1,'...',pages];
}

/* ── Export CSV ── */
function exportQuotCsv() {
  const rows = [['Ref #','Customer','Company','Items','Total (€)','Status','Date']];
  document.querySelectorAll('#quotTbody .quot-row').forEach(tr => {
    const c = tr.querySelectorAll('td');
    rows.push([c[0].textContent.trim(), c[1].textContent.trim(), c[2].textContent.trim(),
               c[3].textContent.trim(), c[4].textContent.trim(), c[5].textContent.trim(), c[6].textContent.trim()]);
  });
  const csv = rows.map(r => r.map(c=>'"'+String(c).replace(/"/g,'""')+'"').join(',')).join('\n');
  const a   = document.createElement('a');
  a.href    = 'data:text/csv;charset=utf-8,'+encodeURIComponent(csv);
  a.download= 'quotations_<?= date('Ymd') ?>.csv';
  a.click();
}

/* ── Init ── */
function quotInit() {
  quotRender();
  switchQTab('customer');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
