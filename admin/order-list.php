<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'order-list';
$pageTitle   = 'Orders';

$controller = new AdminController();

$canView   = sinelec_can('view');
$canAdd    = sinelec_can('add');
$canEdit   = sinelec_can('edit');
$canDelete = sinelec_can('delete');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

/* ── Filters ── */
$fSearch  = trim($_GET['search']         ?? '');
$fOStatus = trim($_GET['order_status']   ?? '');
$fPStatus = trim($_GET['payment_status'] ?? '');
$fMode    = trim($_GET['order_mode']     ?? '');
$fSource  = trim($_GET['source']         ?? '');
$fFrom    = trim($_GET['date_from']      ?? '');
$fTo      = trim($_GET['date_to']        ?? '');

$orders      = $controller->getAllUserOrders([
    'search'         => $fSearch,
    'order_status'   => $fOStatus,
    'payment_status' => $fPStatus,
    'order_mode'     => $fMode,
    'source'         => $fSource,
    'date_from'      => $fFrom,
    'date_to'        => $fTo,
]);
$customers        = $controller->getCustomersForQuote();
$allProducts      = $controller->getAllProducts(['status'=>'Active']);
$allCats          = $controller->getAllCategories();
$countries        = $controller->getAllCountries();
$courierCompanies = $controller->getCourierCompanies();
$pubBase     = rtrim(sinelec_env('PUBLIC_BASE_URL'), '/');

/* ── Countries list for JS ── */
$coCountriesList = [];
foreach ($countries as $c) {
    $coCountriesList[] = [
        'id'   => (int)(float)($c->COUNTRY_ID ?? 0),
        'name' => (string)($c->COUNTRY        ?? ''),
    ];
}

/* ── Build catProdMap for JS ── */
$catProdMap = [];
foreach ($allProducts as $p) {
    $cid = (int)(float)($p->PRODUCT_CATEGORY_ID ?? 0);
    $catProdMap[$cid][] = [
        'id'    => (int)(float)($p->PRODUCT_ID       ?? 0),
        'name'  => (string)($p->PRODUCT_NAME          ?? ''),
        'code'  => (string)($p->PRODUCT_CODE          ?? ''),
        'price' => (float)($p->PRODUCT_AMT            ?? 0),
        'tax'   => (float)($p->PRODUCT_TAX            ?? 0),
        'disc'  => (float)($p->PRODUCT_DISCOUNT       ?? 0),
        'stock' => (int)(float)($p->PRODUCT_QUANTITY  ?? -1),
    ];
}

/* ── Category options HTML for Create Order product rows ── */
$coParentCats = [];
$coChildCats  = [];
foreach ($allCats as $cat) {
    $cid   = (int)(float)($cat->PRODUCT_CATEGORY_ID  ?? 0);
    $pid   = (int)(float)($cat->PARENT_CATEGORY_ID   ?? 0);
    $cname = (string)($cat->PRODUCT_CATEGORY_NAME     ?? '');
    if ($pid === 0) $coParentCats[$cid] = $cname;
    else            $coChildCats[$pid][] = ['id' => $cid, 'name' => $cname];
}
$coCatOptsHtml = '<option value="">— Select Category —</option>';
foreach ($coParentCats as $pid => $pname) {
    if (!empty($coChildCats[$pid])) {
        $coCatOptsHtml .= '<optgroup label="'.htmlspecialchars($pname).'">';
        foreach ($coChildCats[$pid] as $sub) {
            $coCatOptsHtml .= '<option value="'.$sub['id'].'">'.htmlspecialchars($sub['name']).'</option>';
        }
        $coCatOptsHtml .= '</optgroup>';
    } else {
        $coCatOptsHtml .= '<option value="'.$pid.'">'.htmlspecialchars($pname).'</option>';
    }
}

/* ── Stats ── */
$sTot = $sPend = $sConf = $sDisp = $sDeliv = $sCanc = 0;
foreach ($orders as $o) {
    $sTot++;
    $st = (string)($o->ORDER_STATUS ?? '');
    if ($st === 'Order Pending')                              $sPend++;
    elseif (in_array($st, ['Order Confirmed','Order Packed']))$sConf++;
    elseif (in_array($st, ['Order Dispatch','Order In Transit'])) $sDisp++;
    elseif ($st === 'Order Delivered')                        $sDeliv++;
    elseif ($st === 'Order Cancelled')                        $sCanc++;
}

/* ── Order status / payment colour helpers ── */
function olOrderBadge(string $st): string {
    $map = [
        'Order Pending'    => 'badge--amber',
        'Order Confirmed'  => 'badge--blue',
        'Order Packed'     => 'badge--violet',
        'Order Dispatch'   => 'badge--sky',
        'Order In Transit' => 'badge--sky',
        'Order Delivered'  => 'badge--green',
        'Order Cancelled'  => 'badge--red',
    ];
    return $map[$st] ?? 'badge--grey';
}
function olPayBadge(string $ps): string {
    $map = [
        'Payment Pending'   => 'badge--amber',
        'Payment Successful'=> 'badge--green',
        'Payment Failed'    => 'badge--red',
        'Refund Initiated'  => 'badge--violet',
        'Refund Completed'  => 'badge--blue',
        'Not Required'      => 'badge--grey',
    ];
    return $map[$ps] ?? 'badge--grey';
}

ob_start();
?>

<!-- ══════════════════ PAGE HEADER ══════════════════ -->
<div class="pg-header">
  <div>
    <h1 class="pg-title">Orders</h1>
    <p class="pg-sub">Manage all customer orders, update statuses and view invoices.</p>
  </div>
  <?php if ($canAdd): ?>
  <button class="btn btn--primary" onclick="openModal('createOrderModal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Create Order
  </button>
  <?php endif; ?>
</div>

<!-- ══════════════════ STATS TILES ══════════════════ -->
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:22px;">
  <?php
  $tiles = [
    ['Total',      $sTot,   '#4f46e5','#ede9fe'],
    ['Pending',    $sPend,  '#d97706','#fef3c7'],
    ['Confirmed',  $sConf,  '#0284c7','#dbeafe'],
    ['In Transit', $sDisp,  '#0891b2','#e0f2fe'],
    ['Delivered',  $sDeliv, '#16a34a','#dcfce7'],
    ['Cancelled',  $sCanc,  '#dc2626','#fee2e2'],
  ];
  foreach ($tiles as [$lbl,$val,$clr,$bg]):
  ?>
  <div style="background:<?= $bg ?>;border-radius:12px;padding:14px 16px;border:1px solid <?= $clr ?>22;">
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:<?= $clr ?>;margin-bottom:6px;"><?= $lbl ?></div>
    <div style="font-size:26px;font-weight:800;color:<?= $clr ?>;"><?= $val ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══════════════════ FILTER BAR ══════════════════ -->
<form method="GET" id="olFilterForm" style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;align-items:flex-end;">
  <div style="position:relative;flex:1;min-width:200px;max-width:280px;">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af;" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="search" class="form-control" placeholder="Order #, customer, email…" value="<?= htmlspecialchars($fSearch) ?>" style="padding-left:30px;height:36px;">
  </div>
  <select name="order_status" class="form-control" style="height:36px;width:auto;min-width:155px;">
    <option value="">All Statuses</option>
    <?php foreach(['Order Pending','Order Confirmed','Order Packed','Order Dispatch','Order In Transit','Order Delivered','Order Cancelled'] as $s): ?>
    <option value="<?= $s ?>" <?= $fOStatus===$s?'selected':'' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <select name="payment_status" class="form-control" style="height:36px;width:auto;min-width:155px;">
    <option value="">All Payments</option>
    <?php foreach(['Payment Pending','Payment Successful','Payment Failed','Refund Initiated','Refund Completed','Not Required'] as $s): ?>
    <option value="<?= $s ?>" <?= $fPStatus===$s?'selected':'' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <select name="order_mode" class="form-control" style="height:36px;width:auto;min-width:140px;">
    <option value="">All Modes</option>
    <?php foreach(['Payment Gateway','Bank Transfer','Invoice'] as $m): ?>
    <option value="<?= $m ?>" <?= $fMode===$m?'selected':'' ?>><?= $m ?></option>
    <?php endforeach; ?>
  </select>
  <select name="source" class="form-control" style="height:36px;width:auto;min-width:130px;">
    <option value="">All Sources</option>
    <option value="Website"      <?= $fSource==='Website'      ?'selected':'' ?>>Website</option>
    <option value="Quotation"    <?= $fSource==='Quotation'    ?'selected':'' ?>>Quotation</option>
    <option value="Direct Order" <?= $fSource==='Direct Order' ?'selected':'' ?>>Direct Order</option>
  </select>
  <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($fFrom) ?>" style="height:36px;width:130px;" title="From date">
  <input type="date" name="date_to"   class="form-control" value="<?= htmlspecialchars($fTo) ?>"   style="height:36px;width:130px;" title="To date">
  <button type="submit" class="btn btn--primary" style="height:36px;padding:0 16px;">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="16" y2="12"/><line x1="4" y1="18" x2="12" y2="18"/></svg>
    Apply
  </button>
  <?php if ($fSearch||$fOStatus||$fPStatus||$fMode||$fSource||$fFrom||$fTo): ?>
  <a href="order-list" class="btn btn--ghost" style="height:36px;padding:0 14px;font-size:12px;">Clear</a>
  <?php endif; ?>
  <div style="margin-left:auto;">
    <button type="button" class="btn btn--outline" style="height:36px;padding:0 14px;font-size:12px;" onclick="olExportCsv()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export CSV
    </button>
  </div>
</form>

<!-- ══════════════════ TABLE CARD ══════════════════ -->
<div class="card" style="overflow:hidden;">
  <!-- ── Pagination bar (top) ── -->
  <div class="ol-pgbar">
    <!-- Left: record range info -->
    <div class="ol-pgbar__info">
      Showing
      <strong id="olRangeStart">1</strong>–<strong id="olRangeEnd">20</strong>
      of
      <strong id="olCount"><?= count($orders) ?></strong>
      record<?= count($orders) !== 1 ? 's' : '' ?>
    </div>
    <!-- Center: per-page control -->
    <div class="ol-pgbar__perpage">
      <span class="ol-pgbar__perpage-label">Records per page</span>
      <div class="ol-pgbar__sel-wrap">
        <select id="olPerPage" class="ol-pgbar__sel">
          <option value="10">10</option>
          <option value="20" selected>20</option>
          <option value="30">30</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <svg class="ol-pgbar__sel-arrow" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <button type="button" class="ol-pgbar__apply" onclick="olApplyPerPage()">Apply</button>
    </div>
    <!-- Right: page buttons -->
    <div id="olPager" class="ol-pgbar__pager"></div>
  </div>
  <div style="overflow-x:auto;">
    <table class="dt" id="olTable">
      <thead>
        <tr>
          <th style="width:36px;">#</th>
          <th style="width:130px;">Order #</th>
          <th>Customer</th>
          <th style="width:60px;text-align:center;">Items</th>
          <th style="width:110px;text-align:right;">Total</th>
          <th style="width:140px;">Order Status</th>
          <th style="width:140px;">Payment</th>
          <th style="width:160px;">Source / Mode / Date</th>
          <th style="width:60px;text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody id="olTbody">
        <?php foreach ($orders as $i => $o):
          $oid      = (int)(float)($o->USER_ORDER_ID ?? 0);
          $orderNo  = (string)($o->ORDER_NUMBER     ?? '');
          $oStatus  = (string)($o->ORDER_STATUS     ?? '');
          $pStatus  = (string)($o->PAYMENT_STATUS   ?? '');
          $mode     = (string)($o->ORDER_MODE       ?? '');
          $cName    = (string)($o->CUST_NAME        ?? '');
          $cEmail   = (string)($o->CUST_EMAIL       ?? '');
          $cPhone   = (string)($o->CUST_PHONE       ?? '');
          $cCo      = (string)($o->CUST_COMPANY     ?? '');
          $qid      = (int)(float)($o->QUOTE_ID     ?? 0);
          $items    = (int)($o->ITEM_COUNT           ?? 0);
          $total    = number_format((float)($o->FINAL_TOTAL_AMT ?? 0), 2);
          $dateRaw  = (string)($o->ORDER_DATE        ?? '');
          $dateFmt  = $dateRaw ? date('d M Y', strtotime($dateRaw)) : '—';
          $timeFmt  = $dateRaw ? date('h:i A', strtotime($dateRaw)) : '';
          $oClass   = olOrderBadge($oStatus);
          $pClass   = olPayBadge($pStatus);
          $srcOrder = (string)($o->SOURCE_ORDER ?? '');
          [$srcLabel, $srcClass] = match($srcOrder) {
              'Website'      => ['Website',      'badge--blue'],
              'Quotation'    => ['Quotation',    'badge--violet'],
              'Direct Order' => ['Direct Order', 'badge--green'],
              default        => [$srcOrder ?: 'Website', 'badge--grey'],
          };
          $trackId  = trim((string)($o->DISPATCH_COURIER_TRACKING_ID ?? ''));
          $trackTpl = trim((string)($o->COURIER_TRACKING_TPL ?? ''));
          $trackUrl = ($trackId !== '' && $trackTpl !== '') ? str_replace('{tracking_id}', rawurlencode($trackId), $trackTpl) : '';
          $modeIcon = match($mode) { 'Invoice' => '📄', 'Bank Transfer' => '🏦', 'Payment Gateway' => '💳', default => '' };
          $searchStr = strtolower($orderNo.' '.$cName.' '.$cEmail.' '.$cCo.' '.$srcLabel);
        ?>
        <tr class="ol-row" data-seq="<?= $i+1 ?>"
            data-search="<?= htmlspecialchars($searchStr) ?>"
            data-os="<?= htmlspecialchars($oStatus) ?>"
            data-ps="<?= htmlspecialchars($pStatus) ?>">
          <td class="td-sm ol-sno" style="font-size:12px;color:var(--text-muted);font-weight:600;"><?= $i+1 ?></td>
          <td>
            <div style="font-weight:700;color:var(--primary);font-size:13px;"><?= htmlspecialchars($orderNo) ?></div>
            <div style="font-size:10px;color:var(--text-muted);margin-top:1px;font-family:monospace;">#<?= $oid ?></div>
          </td>
          <td>
            <div style="font-weight:600;font-size:13px;color:var(--text);"><?= htmlspecialchars($cName) ?></div>
            <?php if ($cEmail): ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($cEmail) ?></div><?php endif; ?>
            <?php if ($cPhone): ?><div style="font-size:11px;color:var(--text-muted);">📞 <?= htmlspecialchars($cPhone) ?></div><?php endif; ?>
            <?php if ($cCo):    ?><div style="font-size:11px;color:var(--text-muted);font-style:italic;"><?= htmlspecialchars($cCo) ?></div><?php endif; ?>
          </td>
          <td style="text-align:center;font-size:13px;font-weight:600;"><?= $items ?></td>
          <td style="text-align:right;font-size:13px;font-weight:700;color:var(--text);">€<?= $total ?></td>
          <td><span class="badge <?= $oClass ?>"><?= htmlspecialchars($oStatus) ?></span></td>
          <td><span class="badge <?= $pClass ?>" style="font-size:10px;"><?= htmlspecialchars($pStatus) ?></span></td>
          <td>
            <span class="badge <?= $srcClass ?>" style="font-size:10px;"><?= htmlspecialchars($srcLabel) ?></span>
            <?php if ($mode): ?><div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= $modeIcon ?> <?= htmlspecialchars($mode) ?></div><?php endif; ?>
            <div style="font-size:11px;color:var(--text-muted);margin-top:3px;"><?= $dateFmt ?></div>
            <?php if($timeFmt): ?><div style="font-size:10px;color:var(--text-muted);opacity:.7;"><?= $timeFmt ?></div><?php endif; ?>
          </td>
          <td style="text-align:center;">
            <div style="display:inline-flex;flex-direction:column;align-items:center;gap:4px;">
              <div style="display:inline-flex;align-items:center;gap:4px;">
              <!-- Invoice PDF -->
              <a href="order-invoice?id=<?= $oid ?>" target="_blank" title="Download Invoice"
                 style="display:inline-flex;align-items:center;gap:4px;padding:4px 9px;border-radius:6px;background:#eff6ff;color:#1d4ed8;text-decoration:none;font-size:11px;font-weight:600;white-space:nowrap;"
                 onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="11" y2="17"/></svg>
                Invoice
              </a>
              <?php if ($trackId !== ''): ?>
              <?php if ($trackUrl !== ''): ?>
              <a href="<?= htmlspecialchars($trackUrl) ?>" target="_blank" title="Track Order: <?= htmlspecialchars($trackId) ?>"
                 style="display:inline-flex;align-items:center;gap:4px;padding:4px 9px;border-radius:6px;background:#f0fdf4;color:#15803d;text-decoration:none;font-size:11px;font-weight:600;white-space:nowrap;"
                 onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Track
              </a>
              <?php else: ?>
              <span title="Tracking ID: <?= htmlspecialchars($trackId) ?>"
                    style="display:inline-flex;align-items:center;gap:4px;padding:4px 9px;border-radius:6px;background:#f0fdf4;color:#15803d;font-size:11px;font-weight:600;white-space:nowrap;cursor:default;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?= htmlspecialchars($trackId) ?>
              </span>
              <?php endif; ?>
              <?php endif; ?>
              </div>
              <div class="kbm-wrap">
                <button class="kbm-btn" onclick="toggleKbm(this)" title="More actions">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                </button>
                <div class="kbm-drop">
                  <button class="kbm-item" onclick="closeKbm(this);olViewOrder(<?= $oid ?>)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    View Details
                  </button>
                  <?php if ($canEdit): ?>
                  <button class="kbm-item" onclick="closeKbm(this);olEditOrder(<?= $oid ?>)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit Order
                  </button>
                  <button class="kbm-item" onclick="closeKbm(this);olOpenStatusModal(<?= $oid ?>,<?= htmlspecialchars(json_encode($oStatus),ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($pStatus),ENT_QUOTES) ?>)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                    Update Status
                  </button>
                  <?php endif; ?>
                  <?php if ($canDelete): ?>
                  <div class="kbm-divider"></div>
                  <button class="kbm-item kbm-item--danger" onclick="closeKbm(this);olConfirmDelete(<?= $oid ?>,<?= htmlspecialchars(json_encode($orderNo),ENT_QUOTES) ?>)">
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
  </div>
  <div id="olEmpty" style="display:none;padding:44px 20px;text-align:center;">
    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#c0ccd8" stroke-width="1.3" style="margin:0 auto 10px;display:block;"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
    <div style="font-size:13px;color:var(--text-muted);">No orders match your filters.</div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     CREATE ORDER MODAL
═══════════════════════════════════════════════════ -->
<div id="createOrderModal" class="modal-overlay" onclick="if(event.target===this){closeModal('createOrderModal');_coResetForm();}">
  <div class="modal" style="max-width:1060px;width:96%;max-height:92vh;display:flex;flex-direction:column;">

    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div>
        <div class="modal-title" id="coModalTitle">Create Order</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="coModalSubtitle">Fill in customer info, address and products.</div>
      </div>
      <button onclick="closeModal('createOrderModal');_coResetForm()" style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:26px;line-height:1;">×</button>
    </div>

    <!-- Tab Bar -->
    <div class="prod-tabs" style="flex-shrink:0;" id="coTabBar">
      <button class="prod-tab active" data-cotab="customer" onclick="coSwitchTab('customer')">Customer Info</button>
      <button class="prod-tab" data-cotab="address"  onclick="coSwitchTab('address')">Address</button>
      <button class="prod-tab" data-cotab="products" onclick="coSwitchTab('products')">Products</button>
      <button class="prod-tab" data-cotab="summary"  onclick="coSwitchTab('summary')">Summary</button>
    </div>

    <div style="overflow-y:auto;flex:1;min-height:0;">
      <form id="createOrderForm" method="POST" action="service?urlstring=<?= EncryptURL('action=CreateDirectOrder') ?>">
        <input type="hidden" name="user_order_id"      id="coEditOrderId" value="0">
        <input type="hidden" name="user_id"            id="coUserId"    value="0">
        <input type="hidden" name="user_address_id"    id="coAddrId"    value="0">
        <input type="hidden" name="billing_address_id" id="coBilAddrId" value="0">
        <div style="padding:22px;">

          <!-- ══ TAB: Customer Info ══ -->
          <div class="co-panel" id="co-tab-customer">
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px;">
              <div style="font-weight:600;font-size:13px;margin-bottom:10px;">Select Existing Customer</div>
              <div style="position:relative;" id="coTsWrap">
                <input type="text" id="coCustomerInput" class="form-control"
                       placeholder="Type name, email or company to search…"
                       autocomplete="off" oninput="coTsFilter()" onfocus="coTsFilter()" style="height:38px;">
                <div id="coTsDrop" class="cust-ts-drop"></div>
              </div>
              <div id="coCustomerCard" style="display:none;margin-top:10px;padding:10px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;align-items:center;gap:10px;">
                <div style="flex:1;min-width:0;">
                  <div style="font-weight:600;font-size:13px;color:#1e40af;" id="coCustomerName"></div>
                  <div style="font-size:11px;color:#64748b;" id="coCustomerEmail"></div>
                </div>
                <button type="button" onclick="coClearCustomer()" style="background:none;border:none;cursor:pointer;color:#64748b;font-size:20px;line-height:1;flex-shrink:0;">×</button>
              </div>
            </div>

            <div style="text-align:center;margin-bottom:16px;">
              <span style="font-size:12px;color:var(--text-muted);">— or —</span>
              <button type="button" class="btn btn--outline" style="margin-left:10px;font-size:12px;height:30px;padding:0 12px;" onclick="coToggleNewCust()">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add New Customer
              </button>
            </div>
            <div id="coNewCustForm" style="display:none;background:#fff9f0;border:1px solid #fed7aa;border-radius:10px;padding:16px;margin-bottom:16px;">
              <div style="font-weight:600;font-size:13px;margin-bottom:12px;color:#c2410c;">New Customer Details</div>
              <div class="form-row cols-2" style="margin-bottom:12px;">
                <div class="fg"><label>Full Name <span class="req">*</span></label><input type="text" id="coNcName" class="form-control" placeholder="Customer full name"></div>
                <div class="fg"><label>Email <span class="req">*</span></label><input type="email" id="coNcEmail" class="form-control" placeholder="customer@example.com"></div>
              </div>
              <div class="form-row cols-2" style="margin-bottom:12px;">
                <div class="fg">
                  <label>Phone</label>
                  <div style="display:flex;gap:6px;">
                    <select id="coNcIsd" class="form-control" style="width:80px;flex-shrink:0;height:38px;">
                      <option value="91">+91</option><option value="1">+1</option><option value="44">+44</option>
                      <option value="971">+971</option><option value="966">+966</option><option value="65">+65</option>
                      <option value="60">+60</option><option value="49">+49</option><option value="33">+33</option><option value="61">+61</option>
                    </select>
                    <input type="text" id="coNcPhone" class="form-control" placeholder="Mobile number">
                  </div>
                </div>
                <div class="fg"><label>Company</label><input type="text" id="coNcCompany" class="form-control" placeholder="Company name"></div>
              </div>
              <div class="form-row cols-2" style="margin-bottom:14px;">
                <div class="fg"><label>Designation</label><input type="text" id="coNcDesig" class="form-control" placeholder="Job title"></div>
              </div>
              <div style="display:flex;gap:8px;align-items:center;">
                <button type="button" class="btn btn--primary" style="font-size:12px;height:34px;" onclick="coSubmitNewCustomer()">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg>
                  Create Customer
                </button>
                <button type="button" class="btn btn--ghost" style="font-size:12px;height:34px;" onclick="coToggleNewCust()">Cancel</button>
                <span id="coNcSpinner" style="display:none;font-size:12px;color:var(--text-muted);">Saving…</span>
                <span id="coNcError"   style="display:none;font-size:12px;color:#ef4444;"></span>
              </div>
            </div>

            <div style="border-top:1px solid var(--border);padding-top:16px;">
              <div style="font-weight:600;font-size:13px;margin-bottom:12px;">Order Details</div>
              <div class="form-row cols-2">
                <div class="fg"><label>Customer PO #</label><input type="text" name="customer_po_id" class="form-control" placeholder="PO reference"></div>
                <div class="fg"><label>Supplier Reference</label><input type="text" name="customer_supplier_no" class="form-control" placeholder="Supplier ref"></div>
              </div>
            </div>
          </div>

          <!-- ══ TAB: Address ══ -->
          <div class="co-panel" id="co-tab-address" style="display:none;">
            <div id="coAddrNoCustomer" style="text-align:center;padding:40px 20px;color:var(--text-muted);">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" style="margin:0 auto 10px;display:block;opacity:.4"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
              <div style="font-size:13px;">Please select a customer on the previous tab first.</div>
            </div>
            <div id="coAddrPanels" style="display:none;">
              <!-- Manage link -->
              <div style="text-align:right;margin-bottom:12px;">
                <a id="coAddrManageLink" href="#" target="_blank" style="font-size:12px;color:var(--primary);">Manage all addresses →</a>
              </div>
              <!-- Delivery Address -->
              <div style="margin-bottom:22px;">
                <div style="font-weight:700;font-size:13px;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  Delivery Address
                </div>
                <div id="coDelAddrSelector"></div>
                <div id="coDelAddrCard" class="addr-card" style="display:none;"></div>
                <button type="button" class="addr-add-btn" id="coDelAddNewBtn" onclick="coShowAddrForm('del')" style="display:none;">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  Add New Address
                </button>
                <div id="coDelAddrForm" class="addr-form-panel" style="display:none;"></div>
              </div>
              <!-- Billing Address -->
              <div>
                <div style="font-weight:700;font-size:13px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;">
                  <span style="display:flex;align-items:center;gap:8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Billing Address
                  </span>
                  <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:500;cursor:pointer;">
                    <input type="checkbox" id="coBilSameAsDel" onchange="coToggleBilSame()" checked style="accent-color:var(--primary);">
                    Same as Delivery
                  </label>
                </div>
                <div id="coBilAddrWrap" style="display:none;">
                  <div id="coBilAddrSelector"></div>
                  <div id="coBilAddrCard" class="addr-card" style="display:none;"></div>
                  <button type="button" class="addr-add-btn" id="coBilAddNewBtn" onclick="coShowAddrForm('bil')" style="display:none;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add New Address
                  </button>
                  <div id="coBilAddrForm" class="addr-form-panel" style="display:none;"></div>
                </div>
                <div id="coBilSameNote" style="padding:12px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:12px;color:#166534;">
                  ✓ Billing address will be same as delivery address.
                </div>
              </div>
            </div>
          </div>

          <!-- ══ TAB: Products ══ -->
          <div class="co-panel" id="co-tab-products" style="display:none;">
            <div id="coProductRows"></div>
            <button type="button" class="btn btn--outline" style="margin-top:10px;font-size:12px;" onclick="coAddProductRow()">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add Row
            </button>
          </div>

          <!-- ══ TAB: Summary ══ -->
          <div class="co-panel" id="co-tab-summary" style="display:none;">
            <div style="margin-bottom:18px;">
              <div style="font-weight:600;font-size:13px;margin-bottom:8px;">Products in this Order</div>
              <div id="coSummaryProdList"></div>
            </div>
            <div style="width:100%;border:1px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:20px;">
              <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <tr style="border-bottom:1px solid var(--border);">
                  <td style="padding:10px 14px;color:var(--text-muted);">Subtotal</td>
                  <td style="text-align:right;padding:10px 14px;font-weight:600;">€<span id="coSub">0.00</span></td>
                </tr>
                <tr style="border-bottom:1px solid var(--border);">
                  <td colspan="2" style="padding:10px 14px;">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                      <span style="color:var(--text-muted);white-space:nowrap;font-size:13px;">VAT Number</span>
                      <input type="text" name="vat_number" id="coVatNumber" class="form-control"
                             placeholder="e.g. DE123456789 — leave blank to apply VAT"
                             style="flex:1;min-width:200px;height:32px;padding:2px 10px;font-size:12px;font-family:monospace;"
                             oninput="coOnVatInput(this.value)">
                      <span id="coVatNumBadge" style="display:none;font-size:11px;font-weight:600;color:#059669;background:#dcfce7;border:1px solid #86efac;border-radius:12px;padding:2px 10px;white-space:nowrap;">VAT Exempt (0%)</span>
                    </div>
                  </td>
                </tr>
                <tr style="border-bottom:1px solid var(--border);">
                  <td style="padding:10px 14px;">
                    <span style="color:var(--text-muted);">VAT / Tax</span>
                    <input type="number" name="vat_pct" id="coVatPct" class="form-control"
                           style="width:70px;display:inline-block;margin-left:8px;height:28px;padding:2px 8px;font-size:12px;"
                           value="19" min="0" max="100" step="0.01" oninput="coRecalc()">
                    <span style="color:var(--text-muted);font-size:12px;">%</span>
                  </td>
                  <td style="text-align:right;padding:10px 14px;">€<span id="coVat">0.00</span></td>
                </tr>
                <tr style="border-bottom:1px solid var(--border);">
                  <td style="padding:10px 14px;">
                    <span style="color:var(--text-muted);">Shipping</span>
                    <input type="number" name="shipping_amt" id="coShipping" class="form-control"
                           style="width:90px;display:inline-block;margin-left:8px;height:28px;padding:2px 8px;font-size:12px;"
                           value="0" min="0" step="0.01" oninput="coRecalc()">
                  </td>
                  <td style="text-align:right;padding:10px 14px;">€<span id="coShipDisp">0.00</span></td>
                </tr>
                <tr style="border-bottom:1px solid var(--border);">
                  <td style="padding:10px 14px;color:var(--text-muted);">Total Discount</td>
                  <td style="text-align:right;padding:10px 14px;color:#dc2626;font-weight:600;">−€<span id="coDiscAmt">0.00</span></td>
                </tr>
                <tr style="background:#f8fafc;">
                  <td style="padding:12px 14px;font-weight:700;font-size:14px;">Grand Total</td>
                  <td style="text-align:right;padding:12px 14px;font-weight:700;font-size:16px;color:var(--primary);">€<span id="coGrand">0.00</span></td>
                </tr>
              </table>
            </div>
            <input type="hidden" name="vat_amt"      id="coVatAmt"   value="0">
            <input type="hidden" name="discount_amt" id="coDiscAmtH" value="0">
            <input type="hidden" name="tax_amt"      id="coTaxAmtH"  value="0">

            <div style="font-size:11px;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Payment Method <span class="req">*</span></div>
            <div style="display:flex;flex-direction:column;gap:8px;" id="coPayModeGroup">
              <label class="go-pay-card" for="coMode-invoice">
                <input type="radio" name="order_mode" id="coMode-invoice" value="Invoice" required>
                <div class="go-pay-card-inner">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="11" y2="17"/></svg>
                  <div>
                    <div style="font-size:13px;font-weight:700;color:#1e293b;">Invoice</div>
                    <div style="font-size:11px;color:#6b7280;margin-top:1px;">Order confirmed immediately · Payment not required</div>
                  </div>
                </div>
              </label>
              <label class="go-pay-card" for="coMode-bank">
                <input type="radio" name="order_mode" id="coMode-bank" value="Bank Transfer" required>
                <div class="go-pay-card-inner">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                  <div>
                    <div style="font-size:13px;font-weight:700;color:#1e293b;">Bank Transfer</div>
                    <div style="font-size:11px;color:#6b7280;margin-top:1px;">Order pending · Payment via bank transfer</div>
                  </div>
                </div>
              </label>
            </div>
          </div>

        </div><!-- /padding -->
      </form>
    </div>

    <!-- Footer nav -->
    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 22px;border-top:1px solid var(--border);flex-shrink:0;background:#f8fafc;">
      <div>
        <button type="button" class="btn btn--outline" id="coPrevBtn" style="display:none;" onclick="coPrevTab()">← Prev</button>
      </div>
      <div style="display:flex;gap:8px;">
        <button type="button" class="btn btn--ghost" onclick="closeModal('createOrderModal');_coResetForm()">Cancel</button>
        <button type="button" class="btn btn--outline" id="coNextBtn" onclick="coNextTab()">Next →</button>
        <button type="button" class="btn btn--primary" id="coSaveBtn" style="display:none;" onclick="coSubmitForm()">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          <span id="coSaveBtnTxt">Create Order</span>
        </button>
      </div>
    </div>

  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     VIEW ORDER MODAL
═══════════════════════════════════════════════════ -->
<div id="viewOrderModal" class="modal-overlay" onclick="if(event.target===this)closeModal('viewOrderModal')">
  <div class="modal" style="max-width:860px;max-height:92vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="flex-shrink:0;">
      <span class="modal-title">Order Details</span>
      <div style="display:flex;align-items:center;gap:8px;">
        <a id="voInvoiceLink" href="#" target="_blank" class="btn btn--outline" style="height:30px;padding:0 12px;font-size:11px;">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Invoice PDF
        </a>
        <button class="modal-close" onclick="closeModal('viewOrderModal')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
    </div>
    <div id="voBody" style="overflow-y:auto;flex:1;min-height:0;padding:20px;">
      <div style="text-align:center;padding:30px;color:var(--text-muted);">Loading…</div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     UPDATE STATUS MODAL
═══════════════════════════════════════════════════ -->
<div id="updateStatusModal" class="modal-overlay" onclick="if(event.target===this)closeModal('updateStatusModal')">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <span class="modal-title">Update Order Status</span>
      <button class="modal-close" onclick="closeModal('updateStatusModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form id="updateStatusForm" method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateUserOrderStatus') ?>">
        <input type="hidden" name="user_order_id" id="usOrderId" value="0">
        <div class="fg" style="margin-bottom:12px;">
          <label>Order Status <span class="req">*</span></label>
          <select name="order_status" id="usOrderStatus" class="form-control" onchange="usOnStatusChange(this.value)">
            <?php foreach(['Order Pending','Order Confirmed','Order Packed','Order Dispatch','Order In Transit','Order Delivered','Order Cancelled'] as $s): ?>
            <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg" style="margin-bottom:12px;">
          <label>Payment Status <span class="req">*</span></label>
          <select name="payment_status" id="usPaymentStatus" class="form-control">
            <?php foreach(['Payment Pending','Payment Successful','Payment Failed','Refund Initiated','Refund Completed','Not Required'] as $s): ?>
            <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- ── Dispatch panel (visible only when Order Dispatch is selected) ── -->
        <div id="usDispatchPanel" style="display:none;background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:14px 16px;margin-bottom:12px;">
          <div style="font-size:12px;font-weight:700;color:#0369a1;margin-bottom:12px;display:flex;align-items:center;gap:6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            Dispatch Details
          </div>
          <div class="fg" style="margin-bottom:10px;">
            <label>Courier Company <span class="req">*</span></label>
            <select name="courier_company_id" id="usCourierId" class="form-control" onchange="usOnCourierChange(this)">
              <option value="0">— Select Courier —</option>
              <?php foreach($courierCompanies as $cc): ?>
              <option value="<?= (int)(float)($cc->COURIER_COMPANY_ID ?? 0) ?>"
                      data-url="<?= htmlspecialchars((string)($cc->TRACKING_URL ?? ''), ENT_QUOTES) ?>">
                <?= htmlspecialchars((string)($cc->COURIER_COMPANY_NAME ?? '')) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg" style="margin-bottom:0;">
            <label>Tracking ID <span style="font-size:11px;color:var(--text-muted);font-weight:400;">(optional)</span></label>
            <input type="text" name="dispatch_courier_tracking_id" id="usTrackId" class="form-control"
                   placeholder="Enter tracking number…" oninput="usOnTrackInput(this.value)">
            <div id="usTrackLink" style="display:none;margin-top:6px;"></div>
          </div>
        </div>

        <div class="fg" style="margin-bottom:18px;">
          <label>Remark <span style="font-size:11px;color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <textarea name="remark" class="form-control" rows="2" placeholder="e.g. Dispatched via DHL, tracking #12345…" style="resize:vertical;min-height:60px;"></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
          <button type="button" class="btn btn--ghost" onclick="closeModal('updateStatusModal')">Cancel</button>
          <button type="submit" class="btn btn--primary" id="usSubmitBtn">Update Status &amp; Notify</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     DELETE CONFIRM MODAL
═══════════════════════════════════════════════════ -->
<div id="deleteOrderModal" class="modal-overlay" onclick="if(event.target===this)closeModal('deleteOrderModal')">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <span class="modal-title">Delete Order</span>
      <button class="modal-close" onclick="closeModal('deleteOrderModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="margin:0 0 18px;font-size:14px;color:var(--text);">Are you sure you want to delete order <strong id="doOrderNo"></strong>? This action cannot be undone.</p>
      <form id="deleteOrderForm" method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteUserOrder') ?>">
        <input type="hidden" name="user_order_id" id="doOrderId" value="0">
        <div style="display:flex;justify-content:flex-end;gap:8px;">
          <button type="button" class="btn btn--ghost" onclick="closeModal('deleteOrderModal')">Cancel</button>
          <button type="submit" class="btn btn--danger-solid">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════ -->
<style>
/* ── Payment cards (reused from quotation) ── */
.go-pay-card { display:block; cursor:pointer; }
.go-pay-card input[type="radio"] { display:none; }
.go-pay-card-inner { display:flex;align-items:center;gap:12px;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;transition:border-color .15s,background .15s; }
.go-pay-card:hover .go-pay-card-inner { border-color:#6366f1;background:#f5f3ff; }
.go-pay-card input:checked ~ .go-pay-card-inner { border-color:#4f46e5;background:#f5f3ff;box-shadow:0 0 0 3px rgba(79,70,229,.12); }
.go-pay-card-inner svg { color:#6b7280;flex-shrink:0; }
.go-pay-card input:checked ~ .go-pay-card-inner svg { color:#4f46e5; }
/* ── Customer typesearch ── */
.cust-ts-drop { display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid #dde3ec;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.12);max-height:220px;overflow-y:auto;z-index:300; }
.cust-ts-drop.open { display:block; }
.cust-ts-item { padding:9px 14px;cursor:pointer;font-size:13px; }
.cust-ts-item:hover { background:#f0f4ff; }
.cust-ts-item .cust-ts-name { font-weight:600;color:#1e293b; }
.cust-ts-item .cust-ts-sub  { font-size:11px;color:#64748b; }
/* ── badge extras ── */
.badge--sky { background:#e0f2fe;color:#0369a1;border-color:#bae6fd; }
/* ── Tab bar ── */
.prod-tabs { display:flex;gap:0;border-bottom:1px solid var(--border);flex-shrink:0;padding:0 22px;background:#fafbfc; }
.prod-tab { padding:10px 16px;font-size:13px;font-weight:500;color:var(--text-muted);background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;transition:all .15s;white-space:nowrap; }
.prod-tab:hover { color:var(--text); }
.prod-tab.active { color:var(--primary);border-bottom-color:var(--primary);font-weight:600; }
/* ── Tab panels ── */
.co-panel { display:block; }
/* ── Address card ── */
.addr-card { padding:12px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:12px;line-height:1.6;margin-bottom:8px; }
.addr-card-label { display:inline-block;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;background:#d1fae5;color:#065f46; }
.addr-card-name  { font-weight:700;font-size:13px;color:var(--text); }
.addr-card-line  { color:var(--text-muted); }
/* ── Address selector ── */
.addr-sel { width:100%;height:38px;padding:0 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;background:#fff;color:var(--text);cursor:pointer;margin-bottom:10px; }
.addr-sel:focus { outline:none;border-color:var(--primary); }
/* ── Add new address button ── */
.addr-add-btn { display:flex;align-items:center;gap:6px;padding:7px 14px;font-size:12px;font-weight:600;color:var(--primary);background:#eff6ff;border:1.5px dashed #93c5fd;border-radius:8px;cursor:pointer;transition:background .15s;width:100%;margin-top:6px; }
.addr-add-btn:hover { background:#dbeafe; }
/* ── Inline address form panel ── */
.addr-form-panel { background:#fff;border:1.5px solid var(--border);border-radius:10px;padding:16px;margin-top:10px; }
/* ── Address label toggle ── */
.addr-label-toggle { display:flex;gap:6px;margin-bottom:14px; }
.addr-label-btn { padding:6px 14px;border:1.5px solid var(--border);border-radius:20px;font-size:12px;font-weight:500;background:#fff;color:var(--text-muted);cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:5px; }
.addr-label-btn.active { border-color:var(--primary);background:#eff6ff;color:var(--primary);font-weight:700; }
/* ── qp-row (category→product rows) ── */
.co-qp-row { display:grid;grid-template-columns:1.8fr 2.8fr 70px 130px 90px 110px 32px;gap:8px;align-items:start;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--border); }
@media(max-width:700px) { .co-qp-row { grid-template-columns:1fr 1fr; } }
.co-qp-row .qp-lbl { font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px; }
.co-qp-row .qp-amt { font-size:13px;font-weight:700;color:var(--primary);white-space:nowrap;margin-top:22px;display:block; }
.co-qp-row .qp-rm  { margin-top:22px; }

/* ══ Pagination bar ══ */
.ol-pgbar {
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;flex-wrap:wrap;
  padding:14px 20px;
  border-bottom:1px solid var(--border);
  background:#fff;
}
/* Left — info text */
.ol-pgbar__info {
  font-size:13px;color:#64748b;white-space:nowrap;
}
.ol-pgbar__info strong { color:#1e293b;font-weight:700; }
/* Center — per-page */
.ol-pgbar__perpage {
  display:flex;align-items:center;gap:10px;flex-shrink:0;
}
.ol-pgbar__perpage-label {
  font-size:13px;font-weight:600;color:#374151;white-space:nowrap;
}
.ol-pgbar__sel-wrap {
  position:relative;display:inline-flex;align-items:center;
}
.ol-pgbar__sel {
  -webkit-appearance:none;appearance:none;
  height:36px;padding:0 32px 0 14px;
  border:1.5px solid #e2e8f0;border-radius:20px;
  font-size:13px;font-weight:600;color:#1e293b;
  background:#fff;cursor:pointer;outline:none;
  transition:border-color .15s;
}
.ol-pgbar__sel:hover,.ol-pgbar__sel:focus { border-color:#6366f1; }
.ol-pgbar__sel-arrow {
  position:absolute;right:10px;top:50%;transform:translateY(-50%);
  pointer-events:none;color:#64748b;
}
.ol-pgbar__apply {
  height:36px;padding:0 20px;
  background:#1e293b;color:#fff;
  border:none;border-radius:20px;
  font-size:13px;font-weight:600;
  cursor:pointer;white-space:nowrap;
  transition:background .15s;
}
.ol-pgbar__apply:hover { background:#0f172a; }
/* Right — pager */
.ol-pgbar__pager {
  display:flex;align-items:center;gap:5px;flex-wrap:wrap;
}
/* Prev / Next buttons */
.ol-pg-nav {
  height:36px;padding:0 16px;
  border:1.5px solid #e2e8f0;border-radius:20px;
  background:#fff;font-size:13px;font-weight:600;color:#374151;
  cursor:pointer;white-space:nowrap;
  transition:border-color .15s,color .15s;
}
.ol-pg-nav:hover:not(:disabled) { border-color:#6366f1;color:#6366f1; }
.ol-pg-nav--disabled,.ol-pg-nav:disabled {
  color:#cbd5e1;border-color:#f1f5f9;cursor:default;
}
/* Number circle buttons */
.ol-pg-num {
  width:36px;height:36px;
  border:1.5px solid #e2e8f0;border-radius:50%;
  background:#fff;font-size:13px;font-weight:600;color:#374151;
  cursor:pointer;display:inline-flex;align-items:center;justify-content:center;
  transition:border-color .15s,color .15s,background .15s;flex-shrink:0;
}
.ol-pg-num:hover { border-color:#6366f1;color:#6366f1; }
/* Ellipsis */
.ol-pg-dots {
  font-size:13px;color:#94a3b8;padding:0 2px;
  display:inline-flex;align-items:center;
}
@media(max-width:640px){
  .ol-pgbar { flex-direction:column;align-items:flex-start;gap:10px; }
  .ol-pgbar__perpage,.ol-pgbar__pager { flex-wrap:wrap; }
}
</style>

<!-- ═══════════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════════ -->
<script>
/* ── Data ── */
const OL_CUSTOMERS = <?= json_encode(array_map(fn($c) => [
    'id'      => (int)(float)($c->USER_ID    ?? 0),
    'name'    => (string)($c->USER_NAME      ?? ''),
    'email'   => (string)($c->USER_EMAIL     ?? ''),
    'phone'   => (string)($c->USER_PHONE     ?? ''),
    'company' => (string)($c->COMPANY_NAME   ?? ''),
], $customers)) ?>;

const OL_CAT_PRODS = <?= json_encode($catProdMap) ?>;
const _CO_CAT_OPTS_HTML  = <?= json_encode($coCatOptsHtml) ?>;
const _CO_COUNTRIES      = <?= json_encode($coCountriesList, JSON_UNESCAPED_UNICODE) ?>;
const _CO_SVC_GET_ADDR   = 'service?urlstring=<?= EncryptURL('action=GetUserAddresses') ?>';
const _CO_SVC_SAVE_ADDR  = 'service?urlstring=<?= EncryptURL('action=SaveQuoteAddress') ?>';
const OL_PRODUCTS  = <?= json_encode(array_map(fn($p) => [
    'id'     => (int)(float)($p->PRODUCT_ID          ?? 0),
    'name'   => (string)($p->PRODUCT_NAME             ?? ''),
    'code'   => (string)($p->PRODUCT_CODE             ?? ''),
    'cat_id' => (int)(float)($p->PRODUCT_CATEGORY_ID  ?? 0),
    'amt'    => (float)($p->PRODUCT_AMT               ?? 0),
    'tax'    => (float)($p->PRODUCT_TAX               ?? 0),
    'disc'   => (float)($p->PRODUCT_DISCOUNT          ?? 0),
], $allProducts)) ?>;

/* ══════════════════════════════════════════
   TABLE PAGINATION
══════════════════════════════════════════ */
var _olPage    = 1;
var _olPerPage = 20;   /* applied value — only changes on Apply click */
var _olRows    = [];

/* Init on DOM ready */
function olInit() {
  _olRows = Array.from(document.querySelectorAll('#olTbody .ol-row'));
  olRender();
}

/* Apply button handler */
function olApplyPerPage() {
  var v = parseInt(document.getElementById('olPerPage').value, 10) || 20;
  _olPerPage = v;
  _olPage    = 1;
  olRender();
}

/* Main render */
function olRender() {
  var pp    = _olPerPage;
  var total = _olRows.length;
  var pages = Math.max(1, Math.ceil(total / pp));
  if (_olPage > pages) _olPage = pages;
  if (_olPage < 1)     _olPage = 1;

  var start = (_olPage - 1) * pp;
  var end   = Math.min(start + pp, total);

  /* Show / hide rows and update serial numbers */
  _olRows.forEach(function(r, i) {
    var vis = (i >= start && i < end);
    r.style.display = vis ? '' : 'none';
    if (vis) r.querySelector('.ol-sno').textContent = i + 1;
  });

  /* Update info text */
  document.getElementById('olCount').textContent      = total;
  document.getElementById('olRangeStart').textContent = total > 0 ? start + 1 : 0;
  document.getElementById('olRangeEnd').textContent   = end;
  document.getElementById('olEmpty').style.display    = total === 0 ? 'block' : 'none';

  /* Build pager */
  _olBuildPager(pages);
}

/* Build page buttons */
function _olBuildPager(pages) {
  var pager = document.getElementById('olPager');
  pager.innerHTML = '';

  /* Prev button */
  pager.appendChild(_olNavBtn('Prev', _olPage - 1, _olPage <= 1));

  if (pages > 1) {
    var nums = _olPageNums(_olPage, pages);
    nums.forEach(function(n) {
      if (n === -1) {
        /* Ellipsis */
        var dots = document.createElement('span');
        dots.className   = 'ol-pg-dots';
        dots.textContent = '...';
        pager.appendChild(dots);
      } else {
        pager.appendChild(_olNumBtn(n));
      }
    });
  }

  /* Next button */
  pager.appendChild(_olNavBtn('Next', _olPage + 1, _olPage >= pages));
}

/*
 * Compute visible page numbers (current page is NOT included —
 * position is conveyed by the "Showing X–Y of Z" text).
 * Returns array where -1 means ellipsis.
 */
function _olPageNums(cur, total) {
  if (total <= 1) return [];

  var set = new Set();

  /* Always show first and last (unless they are the current page) */
  if (cur !== 1)     set.add(1);
  if (cur !== total) set.add(total);

  /* Build a window of up to 4 adjacent pages around cur, excluding cur */
  var before = Math.min(2, cur - 1);
  var after  = Math.min(2, total - cur);
  /* Expand to fill 4 total when near the edges */
  if (before + after < 4) {
    if (cur <= 3)           after  = Math.min(4 - before, total - cur);
    else if (cur >= total - 2) before = Math.min(4 - after,  cur - 1);
  }
  for (var p = cur - before; p <= cur + after; p++) {
    if (p >= 1 && p <= total && p !== cur) set.add(p);
  }

  var arr = Array.from(set).sort(function(a, b) { return a - b; });

  /* Insert -1 (ellipsis) wherever there is a gap > 1 */
  var result = [];
  for (var i = 0; i < arr.length; i++) {
    if (i > 0 && arr[i] - arr[i - 1] > 1) result.push(-1);
    result.push(arr[i]);
  }
  return result;
}

/* Prev / Next pill button */
function _olNavBtn(label, pg, disabled) {
  var b = document.createElement('button');
  b.textContent = label;
  b.className   = 'ol-pg-nav' + (disabled ? ' ol-pg-nav--disabled' : '');
  b.disabled    = disabled;
  if (!disabled) b.onclick = function() { _olPage = pg; olRender(); };
  return b;
}

/* Numbered circle button */
function _olNumBtn(pg) {
  var b = document.createElement('button');
  b.textContent = String(pg);
  b.className   = 'ol-pg-num';
  b.onclick     = function() { _olPage = pg; olRender(); };
  return b;
}

document.addEventListener('DOMContentLoaded', olInit);

/* ── CSV Export ── */
function olExportCsv() {
  var rows = [['Order#','Customer','Email','Source','Items','Total','Order Status','Payment','Mode','Date']];
  document.querySelectorAll('#olTbody .ol-row').forEach(function(r) {
    var cells = r.querySelectorAll('td');
    rows.push([
      cells[1]?.querySelector('div')?.textContent?.trim()||'',
      cells[2]?.querySelector('div')?.textContent?.trim()||'',
      cells[2]?.querySelectorAll('div')[1]?.textContent?.trim()||'',
      cells[3]?.textContent?.trim()||'',
      cells[4]?.textContent?.trim()||'',
      cells[5]?.textContent?.trim()||'',
      cells[6]?.textContent?.trim()||'',
      cells[7]?.textContent?.trim()||'',
      cells[8]?.textContent?.trim()||'',
      cells[9]?.querySelector('div')?.textContent?.trim()||'',
    ]);
  });
  var csv = rows.map(function(r) { return r.map(function(c) { return '"'+String(c).replace(/"/g,'""')+'"'; }).join(','); }).join('\n');
  var a = document.createElement('a'); a.href = 'data:text/csv;charset=utf-8,﻿'+encodeURIComponent(csv);
  a.download = 'orders-'+new Date().toISOString().slice(0,10)+'.csv'; document.body.appendChild(a); a.click(); a.remove();
}

/* ── Update Status Modal ── */
function olOpenStatusModal(oid, os, ps) {
  document.getElementById('usOrderId').value = oid;
  var osSel = document.getElementById('usOrderStatus');
  var psSel = document.getElementById('usPaymentStatus');
  for (var i = 0; i < osSel.options.length; i++) { if (osSel.options[i].value === os) { osSel.selectedIndex = i; break; } }
  for (var j = 0; j < psSel.options.length; j++) { if (psSel.options[j].value === ps) { psSel.selectedIndex = j; break; } }
  /* Reset dispatch panel */
  document.getElementById('usCourierId').value  = '0';
  document.getElementById('usTrackId').value    = '';
  document.getElementById('usTrackLink').style.display  = 'none';
  document.getElementById('usTrackLink').innerHTML      = '';
  usOnStatusChange(os);
  openModal('updateStatusModal');
}

/* ── Dispatch panel visibility ── */
function usOnStatusChange(val) {
  var isDispatch = (val === 'Order Dispatch');
  var panel = document.getElementById('usDispatchPanel');
  if (panel) panel.style.display = isDispatch ? '' : 'none';
  if (!isDispatch) {
    document.getElementById('usCourierId').value       = '0';
    document.getElementById('usTrackId').value         = '';
    document.getElementById('usTrackLink').style.display = 'none';
  }
}

/* ── Courier change → update preview link ── */
function usOnCourierChange(sel) {
  var urlTpl  = (sel.options[sel.selectedIndex] || {}).dataset ? (sel.options[sel.selectedIndex].dataset.url || '') : '';
  var trackId = (document.getElementById('usTrackId').value || '').trim();
  _usRenderTrackLink(urlTpl, trackId);
}

/* ── Track input → update preview link ── */
function usOnTrackInput(val) {
  var sel    = document.getElementById('usCourierId');
  var urlTpl = (sel.options[sel.selectedIndex] || {}).dataset ? (sel.options[sel.selectedIndex].dataset.url || '') : '';
  _usRenderTrackLink(urlTpl, val.trim());
}

function _usRenderTrackLink(urlTpl, trackId) {
  var el = document.getElementById('usTrackLink');
  if (!el) return;
  if (!urlTpl || !trackId) { el.style.display = 'none'; el.innerHTML = ''; return; }
  var href = urlTpl.replace('{tracking_id}', encodeURIComponent(trackId));
  el.innerHTML = '<a href="'+href+'" target="_blank" rel="noopener" '
    +'style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:#0369a1;font-weight:600;text-decoration:none;">'
    +'<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">'
    +'<path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>'
    +'<polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>'
    +'Preview tracking link</a>';
  el.style.display = '';
}

/* ── Delete Modal ── */
function olConfirmDelete(oid, orderNo) {
  document.getElementById('doOrderId').value = oid;
  document.getElementById('doOrderNo').textContent = orderNo;
  openModal('deleteOrderModal');
}

/* ── View Order Modal ── */
function olViewOrder(oid) {
  document.getElementById('voBody').innerHTML = '<div style="text-align:center;padding:30px;color:var(--text-muted);">Loading…</div>';
  document.getElementById('voInvoiceLink').href = 'order-invoice?id=' + oid;
  openModal('viewOrderModal');
  fetch('service?urlstring=<?= EncryptURL('action=GetOrderDetails') ?>&id=' + oid)
    .then(function(r) { return r.json(); })
    .catch(function() { return null; })
    .then(function(data) {
      if (!data || !data.order) {
        document.getElementById('voBody').innerHTML = '<div style="padding:20px;color:#dc2626;">Failed to load order details.</div>';
        return;
      }
      var o = data.order; var items = data.items || []; var hist = data.history || [];
      var _srcMap = { 'Website': 'badge--blue', 'Quotation': 'badge--violet', 'Direct Order': 'badge--green' };
      var _srcCls = _srcMap[o.source_order] || 'badge--grey';
      var srcBadge = '<span class="badge ' + _srcCls + '" style="font-size:10px;">' + (o.source_order || 'Website') + '</span>';
      var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">'
        +'<div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 14px;">'
        +'<div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px;">Order Number</div>'
        +'<div style="font-size:17px;font-weight:800;color:#4f46e5;">'+o.order_number+'</div>'
        +'<div style="margin-top:4px;">'+srcBadge+'</div></div>'
        +'<div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 14px;">'
        +'<div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px;">Total Amount</div>'
        +'<div style="font-size:17px;font-weight:800;color:#059669;">€'+o.final_total+'</div>'
        +'<div style="font-size:11px;color:#94a3b8;margin-top:2px;">'+o.order_mode+'</div></div></div>'
        +'<div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-bottom:14px;">'
        +'<div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:8px;">Customer</div>'
        +'<div style="font-size:13px;font-weight:700;color:#1e293b;">'+o.cust_name+'</div>'
        +(o.cust_email ? '<div style="font-size:12px;color:#64748b;">'+o.cust_email+'</div>' : '')
        +(o.cust_company ? '<div style="font-size:12px;color:#64748b;">'+o.cust_company+'</div>' : '')+'</div>'
        +'<div style="margin-bottom:14px;">'
        +'<div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:8px;">Items</div>'
        +'<table style="width:100%;border-collapse:collapse;font-size:12px;">'
        +'<thead><tr style="background:#f1f5f9;"><th style="padding:6px 10px;text-align:left;font-weight:600;color:#64748b;">Product</th><th style="padding:6px 10px;text-align:center;">Qty</th><th style="padding:6px 10px;text-align:right;">Unit</th><th style="padding:6px 10px;text-align:right;">Total</th></tr></thead><tbody>';
      items.forEach(function(it) {
        html += '<tr style="border-bottom:1px solid #f1f5f9;">'
          +'<td style="padding:7px 10px;"><div style="font-weight:600;color:#1e293b;">'+it.product_name+'</div>'+(it.product_code ? '<div style="font-size:10px;color:#94a3b8;font-family:monospace;">'+it.product_code+'</div>' : '')+'</td>'
          +'<td style="padding:7px 10px;text-align:center;">'+it.qty+'</td>'
          +'<td style="padding:7px 10px;text-align:right;">€'+it.unit_amt+'</td>'
          +'<td style="padding:7px 10px;text-align:right;font-weight:700;">€'+it.final_amt+'</td></tr>';
      });
      html += '</tbody></table></div>';

      /* ── Financial summary ── */
      var sumRow = function(label, val, style) {
        return '<tr style="border-bottom:1px solid #f1f5f9;">'
          +'<td style="padding:9px 14px;font-size:13px;color:#64748b;">'+label+'</td>'
          +'<td style="padding:9px 14px;font-size:13px;font-weight:600;text-align:right;'+(style||'color:#1e293b;')+'">'+val+'</td>'
          +'</tr>';
      };
      html += '<div style="margin-bottom:14px;">'
        +'<div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Order Summary</div>'
        +'<table style="width:100%;border-collapse:collapse;border:1px solid var(--border);border-radius:8px;overflow:hidden;font-size:13px;">'
        +'<tbody>';
      html += sumRow('Subtotal', '€'+o.subtotal, '');
      if (o.vat_number) {
        html += '<tr style="border-bottom:1px solid #f1f5f9;">'
          +'<td style="padding:9px 14px;font-size:13px;color:#64748b;">VAT Number</td>'
          +'<td style="padding:9px 14px;font-size:12px;font-weight:600;text-align:right;font-family:monospace;color:#475569;">'+o.vat_number+'</td></tr>';
      }
      if (parseFloat(o.tax_amt) > 0) {
        var taxLabel = 'VAT / Tax' + (o.tax_pct > 0 ? ' <span style="display:inline-block;background:#f3f0ff;color:#7c3aed;border-radius:4px;padding:1px 6px;font-size:11px;font-weight:700;margin-left:4px;">'+o.tax_pct+'%</span>' : '');
        html += '<tr style="border-bottom:1px solid #f1f5f9;">'
          +'<td style="padding:9px 14px;font-size:13px;color:#64748b;">'+taxLabel+'</td>'
          +'<td style="padding:9px 14px;font-size:13px;font-weight:600;text-align:right;color:#7c3aed;">€'+o.tax_amt+'</td></tr>';
      }
      if (parseFloat(o.shipping_amt) > 0) {
        html += sumRow('Shipping', '€'+o.shipping_amt, 'color:#2563eb;');
      }
      if (parseFloat(o.discount_amt) > 0) {
        html += sumRow('Total Discount', '−€'+o.discount_amt, 'color:#dc2626;');
      }
      html += '<tr style="background:#f8fafc;">'
        +'<td style="padding:11px 14px;font-size:14px;font-weight:700;color:#1e293b;">Grand Total</td>'
        +'<td style="padding:11px 14px;font-size:15px;font-weight:800;text-align:right;color:#2563eb;">€'+o.final_total+'</td>'
        +'</tr>';
      /* Payment mode row — highlighted */
      var modeIcon = o.order_mode === 'Invoice' ? '📄' : (o.order_mode === 'Bank Transfer' ? '🏦' : '💳');
      html += '<tr>'
        +'<td colspan="2" style="padding:10px 14px;">'
        +'<div style="display:inline-flex;align-items:center;gap:8px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:7px;padding:7px 14px;">'
        +'<span style="font-size:15px;">'+modeIcon+'</span>'
        +'<span style="font-size:12px;font-weight:700;color:#1d4ed8;">Payment Method:</span>'
        +'<span style="font-size:13px;font-weight:700;color:#1e40af;">'+o.order_mode+'</span>'
        +'</div></td></tr>'
        +'</tbody></table></div>';

      /* ── Status badges ── */
      html += '<div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-bottom:14px;">'
        +'<div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:8px;">Status</div>'
        +'<div style="display:flex;gap:8px;flex-wrap:wrap;"><span class="badge '+o.os_class+'">'+o.order_status+'</span><span class="badge '+o.ps_class+'" style="font-size:10px;">'+o.payment_status+'</span></div></div>';

      /* ── Dispatch / Tracking (only when courier info is present) ── */
      if (o.courier_name || o.courier_tracking_id) {
        var trackingUrl = '';
        if (o.courier_tracking_tpl && o.courier_tracking_id) {
          trackingUrl = o.courier_tracking_tpl.replace('{tracking_id}', encodeURIComponent(o.courier_tracking_id));
        }
        html += '<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:14px 16px;margin-bottom:14px;">'
          +'<div style="font-size:11px;font-weight:700;color:#0369a1;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;display:flex;align-items:center;gap:6px;">'
          +'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>'
          +'Dispatch Info</div>'
          +'<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">';
        if (o.courier_name) {
          html += '<div>'
            +'<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:3px;">Courier</div>'
            +'<div style="font-size:13px;font-weight:700;color:#0c4a6e;">'+o.courier_name+'</div>'
            +'</div>';
        }
        if (o.dispatch_date && o.dispatch_date !== '0000-00-00 00:00:00') {
          var dDate = new Date(o.dispatch_date);
          var dFmt  = isNaN(dDate) ? o.dispatch_date : dDate.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
          html += '<div>'
            +'<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:3px;">Dispatch Date</div>'
            +'<div style="font-size:13px;font-weight:600;color:#0369a1;">'+dFmt+'</div>'
            +'</div>';
        }
        if (o.courier_tracking_id) {
          html += '<div style="grid-column:1/-1;">'
            +'<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:3px;">Tracking ID</div>'
            +'<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">'
            +'<span style="font-size:14px;font-weight:800;color:#0369a1;font-family:monospace;letter-spacing:.04em;">'+o.courier_tracking_id+'</span>';
          if (trackingUrl) {
            html += '<a href="'+trackingUrl+'" target="_blank" rel="noopener" '
              +'style="display:inline-flex;align-items:center;gap:5px;background:#0369a1;color:#fff;font-size:11px;font-weight:700;padding:5px 14px;border-radius:6px;text-decoration:none;white-space:nowrap;">'
              +'<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">'
              +'<path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>'
              +'<polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>'
              +'Track Order</a>';
          }
          html += '</div></div>';
        }
        html += '</div></div>';
      }

      /* ── Address grid ── */
      function fmtAddr(name, company, line1, line2, city, state, zip, country, phone) {
        var parts = [];
        if (name)    parts.push('<strong style="color:#1e293b;">'+name+'</strong>');
        if (company) parts.push('<span style="color:#475569;">'+company+'</span>');
        if (line1)   parts.push(line1);
        if (line2)   parts.push(line2);
        var cityLine = [city, state].filter(Boolean).join(', ') + (zip ? ' '+zip : '');
        if (cityLine.trim()) parts.push(cityLine.trim());
        if (country) parts.push(country);
        if (phone)   parts.push('📞 '+phone);
        return parts.length ? parts.join('<br>') : '<span style="color:#94a3b8;font-style:italic;">Not specified</span>';
      }
      var hasDelAddr = o.del_line1 || o.del_name || o.del_city;
      var hasBilAddr = o.bil_line1 || o.bil_name || o.bil_city;
      if (hasDelAddr || hasBilAddr) {
        html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">';
        html += '<div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 14px;">'
          +'<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;">🚚 Delivery Address</div>'
          +'<div style="font-size:12px;color:#475569;line-height:1.7;">'+fmtAddr(o.del_name,o.del_company,o.del_line1,o.del_line2,o.del_city,o.del_state,o.del_zip,o.del_country,o.del_phone)+'</div>'
          +'</div>';
        html += '<div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 14px;">'
          +'<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;">🧾 Billing Address</div>'
          +'<div style="font-size:12px;color:#475569;line-height:1.7;">'+fmtAddr(o.bil_name,o.bil_company,o.bil_line1,o.bil_line2,o.bil_city,o.bil_state,o.bil_zip,o.bil_country,o.bil_phone)+'</div>'
          +'</div>';
        html += '</div>';
      }
      if (hist.length) {
        html += '<div>'
          +'<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;">Order History</div>'
          +'<div style="position:relative;padding-left:22px;">'
          +'<div style="position:absolute;left:5px;top:0;bottom:0;width:2px;background:#e2e8f0;"></div>';
        hist.forEach(function(h, idx) {
          var dotColor = '#2563eb';
          if (h.status === 'Order Delivered')  dotColor = '#16a34a';
          else if (h.status === 'Order Cancelled') dotColor = '#dc2626';
          else if (h.status === 'Order Pending')   dotColor = '#d97706';
          var isLast = idx === hist.length - 1;
          html += '<div style="position:relative;margin-bottom:'+(isLast?'0':'16px')+';">'
            +'<div style="position:absolute;left:-18px;top:3px;width:12px;height:12px;border-radius:50%;background:'+dotColor+';border:2px solid #fff;box-shadow:0 0 0 2px #e2e8f0;"></div>'
            +'<div style="font-size:11px;color:#94a3b8;margin-bottom:2px;">'+h.date+' &middot; '+h.by+'</div>'
            +'<div style="font-size:13px;font-weight:700;color:#1e293b;">'+h.status+'</div>'
            +(h.pay_status ? '<div style="font-size:11px;color:#64748b;margin-top:1px;">'+h.pay_status+'</div>' : '')
            +(h.remark ? '<div style="font-size:11px;color:#475569;font-style:italic;margin-top:2px;">&ldquo;'+h.remark+'&rdquo;</div>' : '')
            +'</div>';
        });
        html += '</div></div>';
      }
      document.getElementById('voBody').innerHTML = html;
    });
}

/* ── Edit mode flag ── */
var _coEditMode = false;

/* ── Edit Order — pre-fill form from existing order ── */
async function olEditOrder(oid) {
  /* Fetch full order data */
  var data;
  try {
    var r = await fetch('service?urlstring=<?= EncryptURL('action=GetOrderDetails') ?>&id=' + oid);
    data  = await r.json();
  } catch(e) { alert('Failed to load order details. Please try again.'); return; }
  if (!data || !data.order) { alert('Failed to load order details.'); return; }

  var o     = data.order;
  var items = data.items || [];

  /* Reset form to clean state first */
  _coResetForm();

  /* Switch to edit mode */
  _coEditMode = true;
  document.getElementById('coEditOrderId').value         = oid;
  document.getElementById('coModalTitle').textContent    = 'Edit Order';
  document.getElementById('coModalSubtitle').textContent = 'Update the existing order details.';
  document.getElementById('coSaveBtnTxt').textContent    = 'Save Changes';

  /* ── Customer ── */
  _coSelUserId = o.user_id;
  document.getElementById('coUserId').value              = o.user_id;
  document.getElementById('coCustomerInput').value       = o.cust_name || '';
  document.getElementById('coCustomerName').textContent  = (o.cust_name || '') + (o.cust_company ? ' · ' + o.cust_company : '');
  document.getElementById('coCustomerEmail').textContent = o.cust_email || '';
  document.getElementById('coCustomerCard').style.display = 'flex';

  /* ── PO # / Supplier Ref ── */
  var poEl  = document.querySelector('#createOrderForm input[name="customer_po_id"]');
  var supEl = document.querySelector('#createOrderForm input[name="customer_supplier_no"]');
  if (poEl)  poEl.value  = o.customer_po_id       || '';
  if (supEl) supEl.value = o.customer_supplier_no  || '';

  /* ── Addresses (async) ── */
  if (o.user_id > 0) {
    await coLoadAddresses(o.user_id, {
      del: o.user_address_id    || 0,
      bil: o.billing_address_id || 0
    });
  }

  /* ── Products ── */
  document.getElementById('coProductRows').innerHTML = '';
  _coProdRowIdx = 0;
  if (items.length > 0) {
    items.forEach(function(it) {
      var idx = _coProdRowIdx;           /* capture before increment */
      coAddProductRow();
      /* set category */
      var lastRow = document.querySelector('#coProductRows .co-qp-row:last-child');
      var catSel  = lastRow ? lastRow.querySelector('select[name="cat_ids[]"]') : null;
      var prodSel = document.getElementById('co-prod-' + idx);
      if (catSel && it.cat_id) {
        catSel.value = String(it.cat_id);
        if (prodSel) {
          _coFillProdSel(prodSel, String(it.cat_id));
          if (it.product_id) prodSel.value = String(it.product_id);
        }
      }
      var qtyEl   = document.getElementById('co-qty-'   + idx);
      var priceEl = document.getElementById('co-price-' + idx);
      var discEl  = document.getElementById('co-disc-'  + idx);
      if (qtyEl)   qtyEl.value   = it.qty;
      if (priceEl) priceEl.value = it.unit_amt;
      if (discEl)  discEl.value  = parseFloat(it.disc_pct || 0);
    });
  } else {
    coAddProductRow(); /* ensure at least one blank row */
  }

  /* ── VAT / Tax / Shipping ── */
  var vatNumEl = document.getElementById('coVatNumber');
  var vatPctEl = document.getElementById('coVatPct');
  var shipEl   = document.getElementById('coShipping');
  var badgeEl  = document.getElementById('coVatNumBadge');
  if (vatNumEl) vatNumEl.value = o.vat_number || '';
  if (vatPctEl) { vatPctEl.readOnly = false; vatPctEl.value = o.tax_pct > 0 ? o.tax_pct : 19; }
  if (shipEl)   shipEl.value   = parseFloat(o.shipping_amt) || 0;
  if (o.vat_number && o.vat_number.trim().length > 3) {
    coOnVatInput(o.vat_number);
  } else {
    if (badgeEl) badgeEl.style.display = 'none';
  }

  /* ── Payment mode ── */
  var modeVal = o.order_mode || 'Invoice';
  document.querySelectorAll('#coPayModeGroup input[type="radio"]').forEach(function(r) {
    r.checked = (r.value === modeVal);
  });

  coRecalc();
  coSwitchTab('customer');
  openModal('createOrderModal');
}

/* ── Tab system ── */
var _coTabs    = ['customer','address','products','summary'];
var _coCurTab  = 'customer';

function coSwitchTab(tab) {
  _coTabs.forEach(function(t) {
    document.getElementById('co-tab-'+t).style.display = t===tab ? '' : 'none';
  });
  document.querySelectorAll('#coTabBar .prod-tab').forEach(function(b) {
    b.classList.toggle('active', b.dataset.cotab === tab);
  });
  _coCurTab = tab;
  var idx = _coTabs.indexOf(tab);
  document.getElementById('coPrevBtn').style.display = idx === 0 ? 'none' : '';
  document.getElementById('coNextBtn').style.display = idx === _coTabs.length-1 ? 'none' : '';
  document.getElementById('coSaveBtn').style.display = idx === _coTabs.length-1 ? '' : 'none';
  if (tab === 'summary') coUpdateSummary();
}
function coNextTab() { var i=_coTabs.indexOf(_coCurTab); if(i<_coTabs.length-1) coSwitchTab(_coTabs[i+1]); }
function coPrevTab() { var i=_coTabs.indexOf(_coCurTab); if(i>0) coSwitchTab(_coTabs[i-1]); }

/* ── Customer typesearch ── */
var _coCustomers = OL_CUSTOMERS;
var _coSelUserId = 0;

function coTsFilter() {
  var q    = (document.getElementById('coCustomerInput').value || '').toLowerCase().trim();
  var drop = document.getElementById('coTsDrop');
  var list = q.length > 0
    ? _coCustomers.filter(function(c) { return (c.name+' '+c.email+' '+c.company).toLowerCase().includes(q); }).slice(0,15)
    : _coCustomers.slice(0, 10);
  if (!list.length) { drop.classList.remove('open'); return; }
  drop.innerHTML = list.map(function(c) {
    return '<div class="cust-ts-item" onclick="coTsSelect('+c.id+')">'
      +'<div class="cust-ts-name">'+c.name+'</div>'
      +'<div class="cust-ts-sub">'+(c.email||'')+(c.company?' · '+c.company:'')+'</div></div>';
  }).join('');
  drop.classList.add('open');
}

function coTsSelect(id) {
  var c = _coCustomers.find(function(x) { return x.id === id; });
  if (!c) return;
  _coSelUserId = id;
  document.getElementById('coUserId').value = id;
  document.getElementById('coCustomerInput').value = c.name;
  document.getElementById('coTsDrop').classList.remove('open');
  document.getElementById('coCustomerName').textContent  = c.name + (c.company ? ' · '+c.company : '');
  document.getElementById('coCustomerEmail').textContent = c.email;
  document.getElementById('coCustomerCard').style.display = 'flex';
  coLoadAddresses(id);
}

function coClearCustomer() {
  _coSelUserId = 0;
  document.getElementById('coUserId').value        = 0;
  document.getElementById('coCustomerInput').value = '';
  document.getElementById('coCustomerCard').style.display = 'none';
  _coResetAddrPanels();
}

/* ════════════════════════════════════════
   ADDRESS PANELS
════════════════════════════════════════ */
var _coAddrData = [];

function _coResetAddrPanels() {
  _coAddrData = [];
  document.getElementById('coAddrNoCustomer').style.display = '';
  document.getElementById('coAddrPanels').style.display     = 'none';
  document.getElementById('coDelAddrSelector').innerHTML    = '';
  document.getElementById('coDelAddrCard').style.display    = 'none';
  document.getElementById('coDelAddrCard').innerHTML        = '';
  document.getElementById('coDelAddNewBtn').style.display   = 'none';
  document.getElementById('coDelAddrForm').style.display    = 'none';
  document.getElementById('coDelAddrForm').innerHTML        = '';
  document.getElementById('coBilAddrSelector').innerHTML    = '';
  document.getElementById('coBilAddrCard').style.display    = 'none';
  document.getElementById('coBilAddrCard').innerHTML        = '';
  document.getElementById('coBilAddNewBtn').style.display   = 'none';
  document.getElementById('coBilAddrForm').style.display    = 'none';
  document.getElementById('coBilAddrForm').innerHTML        = '';
  document.getElementById('coBilSameAsDel').checked         = true;
  document.getElementById('coBilAddrWrap').style.display    = 'none';
  document.getElementById('coBilSameNote').style.display    = '';
  document.getElementById('coAddrId').value    = 0;
  document.getElementById('coBilAddrId').value = 0;
}

async function coLoadAddresses(uid, presel) {
  /* presel (optional) = { del: deliveryAddrId, bil: billingAddrId } */
  document.getElementById('coAddrNoCustomer').style.display = 'none';
  document.getElementById('coAddrPanels').style.display     = '';
  var mlink = document.getElementById('coAddrManageLink');
  if (mlink) mlink.href = 'customer-address?user_id=' + uid;
  document.getElementById('coDelAddrSelector').innerHTML = '<div style="font-size:12px;color:var(--text-muted);padding:8px 0;">Loading addresses…</div>';
  try {
    var res = await fetch(_CO_SVC_GET_ADDR, {method:'POST', body: new URLSearchParams({user_id: uid})});
    _coAddrData = await res.json();
  } catch(e) { _coAddrData = []; }
  var preDelId = presel ? (presel.del || 0) : 0;
  var preBilId = presel ? (presel.bil || 0) : 0;
  _coRenderAddrSelector('coDel', _coAddrData, preDelId);
  _coRenderAddrSelector('coBil', _coAddrData, preBilId);
  document.getElementById('coDelAddNewBtn').style.display = '';
  document.getElementById('coBilAddNewBtn').style.display = '';
  /* If billing differs from delivery, expand billing section */
  if (presel && preBilId > 0 && preDelId > 0 && preBilId !== preDelId) {
    document.getElementById('coBilSameAsDel').checked  = false;
    document.getElementById('coBilAddrWrap').style.display = '';
    document.getElementById('coBilSameNote').style.display = 'none';
  }
}

function _coRenderAddrSelector(panel, addrs, preSelId) {
  var selEl  = document.getElementById(panel + 'AddrSelector');
  var cardEl = document.getElementById(panel + 'AddrCard');
  if (!addrs.length) {
    selEl.innerHTML = '<div style="font-size:12px;color:var(--text-muted);padding:4px 0;margin-bottom:8px;">No saved addresses. Add one below.</div>';
    cardEl.style.display = 'none';
    return;
  }
  var html = '<select class="addr-sel" onchange="_coOnAddrSelChange(this,\''+panel+'\')">';
  html    += '<option value="0">— Select address —</option>';
  addrs.forEach(function(a) {
    var sel = a.id === preSelId ? ' selected' : '';
    html += '<option value="'+a.id+'"'+sel+'>'+_coAddrLabel(a)+' — '+_coAddrShort(a)+'</option>';
  });
  html += '</select>';
  selEl.innerHTML = html;
  if (preSelId > 0) {
    var found = addrs.find(function(a) { return a.id === preSelId; });
    if (found) { _coShowAddrCard(panel, found); _coSetAddrId(panel, found.id); }
  }
}

function _coOnAddrSelChange(sel, panel) {
  var id     = parseInt(sel.value);
  var cardEl = document.getElementById(panel + 'AddrCard');
  if (!id) { cardEl.style.display = 'none'; _coSetAddrId(panel, 0); return; }
  var addr = _coAddrData.find(function(a) { return a.id === id; });
  if (addr) { _coShowAddrCard(panel, addr); _coSetAddrId(panel, id); }
}

function _coShowAddrCard(panel, addr) {
  var cardEl = document.getElementById(panel + 'AddrCard');
  cardEl.innerHTML =
    '<span class="addr-card-label">' + (addr.label||'Home') + '</span><br>'
  + (addr.name    ? '<span class="addr-card-name">'+ _coEsc(addr.name) +'</span><br>' : '')
  + (addr.company ? '<span class="addr-card-line">'+ _coEsc(addr.company) +'</span><br>' : '')
  + (addr.address ? '<span class="addr-card-line">'+ _coEsc(addr.address) +'</span><br>' : '')
  + (addr.city||addr.state ? '<span class="addr-card-line">'+ _coEsc([addr.city,addr.state].filter(Boolean).join(', '))+'</span><br>' : '')
  + (addr.country ? '<span class="addr-card-line">'+ _coEsc(addr.country) +'</span><br>' : '')
  + (addr.zip     ? '<span class="addr-card-line">'+ _coEsc(addr.zip) +'</span>' : '');
  cardEl.style.display = '';
}

function _coSetAddrId(panel, id) {
  if (panel === 'coDel') {
    document.getElementById('coAddrId').value = id;
    if (document.getElementById('coBilSameAsDel').checked) {
      document.getElementById('coBilAddrId').value = id;
    }
  } else {
    document.getElementById('coBilAddrId').value = id;
  }
}

function coToggleBilSame() {
  var checked = document.getElementById('coBilSameAsDel').checked;
  document.getElementById('coBilAddrWrap').style.display = checked ? 'none' : '';
  document.getElementById('coBilSameNote').style.display = checked ? '' : 'none';
  if (checked) document.getElementById('coBilAddrId').value = document.getElementById('coAddrId').value;
}

/* ── Add-new address inline form ── */
function _coAddrEls(panel) {
  /* panel is 'del' or 'bil' — maps to coDelAddrForm / coBilAddrForm etc. */
  var cap = panel === 'del' ? 'Del' : 'Bil';
  return {
    form:  document.getElementById('co'+cap+'AddrForm'),
    btn:   document.getElementById('co'+cap+'AddNewBtn'),
    panel: 'co'+cap,
  };
}

function coShowAddrForm(panel) {
  var els = _coAddrEls(panel);
  if (!els.form) return;
  els.form.style.display = '';
  els.form.innerHTML = _coBuildAddrFormHtml(els.panel);
  if (els.btn) els.btn.style.display = 'none';
}

function _coBuildAddrFormHtml(panel) {
  var ctryOpts = '<option value="0">— Select Country —</option>';
  _CO_COUNTRIES.forEach(function(c) {
    ctryOpts += '<option value="'+c.id+'">'+_coEsc(c.name)+'</option>';
  });
  return '<div style="font-weight:600;font-size:12px;color:var(--text-muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.04em;">New Address</div>'
  +'<div class="fg" style="margin-bottom:12px;">'
    +'<label>Address Label</label>'
    +'<div class="addr-label-toggle" id="'+panel+'LabelToggle">'
      +'<button type="button" class="addr-label-btn active" data-lv="Home"   onclick="coSetAddrLabel(\''+panel+'\',\'Home\')">Home</button>'
      +'<button type="button" class="addr-label-btn"        data-lv="Office" onclick="coSetAddrLabel(\''+panel+'\',\'Office\')">Office</button>'
      +'<button type="button" class="addr-label-btn"        data-lv="Other"  onclick="coSetAddrLabel(\''+panel+'\',\'Other\')">Other</button>'
    +'</div>'
    +'<input type="hidden" id="'+panel+'AddrLabel" value="Home">'
  +'</div>'
  +'<div class="form-row cols-2" style="margin-bottom:12px;">'
    +'<div class="fg"><label>Country <span class="req">*</span></label>'
      +'<select id="'+panel+'AddrCountry" class="form-control" style="height:38px;" onchange="_coOnAddrCountryChange(\''+panel+'\',this)">'+ctryOpts+'</select>'
    +'</div>'
    +'<div class="fg"><label>Full Name / Company Name <span class="req">*</span></label>'
      +'<input type="text" id="'+panel+'AddrCompany" class="form-control" placeholder="Full name or company name">'
    +'</div>'
  +'</div>'
  +'<div class="form-row cols-2" style="margin-bottom:12px;">'
    +'<div class="fg"><label>Contact Name <span class="req">*</span></label>'
      +'<input type="text" id="'+panel+'AddrName" class="form-control" placeholder="Contact person name">'
    +'</div>'
    +'<div class="fg"><label>Phone Number <span class="req">*</span></label>'
      +'<div style="display:flex;gap:6px;">'
        +'<select id="'+panel+'AddrMcc" class="form-control" style="width:80px;flex-shrink:0;height:38px;">'
          +'<option value="91">+91</option><option value="1">+1</option><option value="44">+44</option>'
          +'<option value="971">+971</option><option value="966">+966</option><option value="65">+65</option>'
          +'<option value="60">+60</option><option value="49">+49</option><option value="33">+33</option><option value="61">+61</option>'
        +'</select>'
        +'<input type="text" id="'+panel+'AddrPhone" class="form-control" placeholder="Phone number">'
      +'</div>'
    +'</div>'
  +'</div>'
  +'<div class="fg" style="margin-bottom:12px;"><label>Address Line 1 <span class="req">*</span></label>'
    +'<input type="text" id="'+panel+'AddrLine1" class="form-control" placeholder="Street name and number">'
  +'</div>'
  +'<div class="fg" style="margin-bottom:12px;"><label>Address Line 2</label>'
    +'<input type="text" id="'+panel+'AddrLine2" class="form-control" placeholder="Apartment, suite, floor, unit">'
  +'</div>'
  +'<div class="fg" style="margin-bottom:12px;"><label>Address Line 3</label>'
    +'<input type="text" id="'+panel+'AddrLine3" class="form-control" placeholder="Landmark, area, locality">'
  +'</div>'
  +'<div class="form-row cols-2" style="margin-bottom:12px;">'
    +'<div class="fg"><label>Postal Code <span class="req">*</span></label>'
      +'<input type="text" id="'+panel+'AddrZip" class="form-control" placeholder="Postal code">'
    +'</div>'
    +'<div class="fg"><label>City <span class="req">*</span></label>'
      +'<input type="text" id="'+panel+'AddrCity" class="form-control" placeholder="City">'
    +'</div>'
  +'</div>'
  +'<div class="fg" style="margin-bottom:12px;"><label>State / Region</label>'
    +'<input type="text" id="'+panel+'AddrState" class="form-control" placeholder="State or region">'
  +'</div>'
  +'<div class="fg" style="margin-bottom:14px;"><label>Additional Address Information</label>'
    +'<textarea id="'+panel+'AddrLandmark" class="form-control" rows="2" placeholder="Special delivery instructions…" style="resize:vertical;"></textarea>'
  +'</div>'
  +'<input type="hidden" id="'+panel+'AddrCountryId" value="0">'
  +'<input type="hidden" id="'+panel+'AddrCountryName" value="">'
  +'<div style="display:flex;gap:8px;align-items:center;">'
    +'<button type="button" class="btn btn--primary" style="font-size:12px;height:34px;" onclick="coSubmitAddrForm(\''+panel+'\')">'
      +'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg>'
      +'Save Address'
    +'</button>'
    +'<button type="button" class="btn btn--ghost" style="font-size:12px;height:34px;" onclick="coCancelAddrForm(\''+panel+'\')">Cancel</button>'
    +'<span id="'+panel+'AddrSpinner" style="display:none;font-size:12px;color:var(--text-muted);">Saving…</span>'
    +'<span id="'+panel+'AddrError"   style="display:none;font-size:12px;color:#ef4444;"></span>'
  +'</div>';
}

function _coOnAddrCountryChange(panel, sel) {
  var opt = sel.options[sel.selectedIndex];
  document.getElementById(panel+'AddrCountryId').value   = opt.value;
  document.getElementById(panel+'AddrCountryName').value = opt.text;
}

function coSetAddrLabel(panel, val) {
  document.getElementById(panel+'AddrLabel').value = val;
  document.querySelectorAll('#'+panel+'LabelToggle .addr-label-btn').forEach(function(b) {
    b.classList.toggle('active', b.dataset.lv === val);
  });
}

async function coSubmitAddrForm(panel) {
  var uid  = parseInt(document.getElementById('coUserId').value) || 0;
  var name = (document.getElementById(panel+'AddrName')  ? document.getElementById(panel+'AddrName').value  : '').trim();
  var addr = (document.getElementById(panel+'AddrLine1') ? document.getElementById(panel+'AddrLine1').value : '').trim();
  var city = (document.getElementById(panel+'AddrCity')  ? document.getElementById(panel+'AddrCity').value  : '').trim();
  var zip  = (document.getElementById(panel+'AddrZip')   ? document.getElementById(panel+'AddrZip').value   : '').trim();
  var errEl = document.getElementById(panel+'AddrError');
  function _showErr(msg) { if(errEl){errEl.textContent=msg;errEl.style.display='';} }
  if (!uid)  { _showErr('No customer selected.'); return; }
  if (!name) { _showErr('Contact name is required.'); return; }
  if (!addr) { _showErr('Address Line 1 is required.'); return; }
  if (!city) { _showErr('City is required.'); return; }
  if (!zip)  { _showErr('Postal code is required.'); return; }
  var spinEl = document.getElementById(panel+'AddrSpinner');
  if (spinEl) spinEl.style.display = '';
  if (errEl)  errEl.style.display  = 'none';
  var body = new URLSearchParams({
    user_id:             uid,
    label:               (document.getElementById(panel+'AddrLabel')       ? document.getElementById(panel+'AddrLabel').value       : 'Home'),
    addr_user_name:      name,
    addr_company_name:   (document.getElementById(panel+'AddrCompany')     ? document.getElementById(panel+'AddrCompany').value.trim() : ''),
    delivery_phone_no:   (document.getElementById(panel+'AddrPhone')       ? document.getElementById(panel+'AddrPhone').value.trim()   : ''),
    mobile_country_code: (document.getElementById(panel+'AddrMcc')         ? document.getElementById(panel+'AddrMcc').value            : '91'),
    address:             addr,
    address_line_one:    (document.getElementById(panel+'AddrLine2')       ? document.getElementById(panel+'AddrLine2').value.trim()   : ''),
    address_line_two:    (document.getElementById(panel+'AddrLine3')       ? document.getElementById(panel+'AddrLine3').value.trim()   : ''),
    landmark:            (document.getElementById(panel+'AddrLandmark')    ? document.getElementById(panel+'AddrLandmark').value.trim(): ''),
    city:                city,
    state:               (document.getElementById(panel+'AddrState')       ? document.getElementById(panel+'AddrState').value.trim()   : ''),
    zip:                 zip,
    country_id:          (document.getElementById(panel+'AddrCountryId')   ? document.getElementById(panel+'AddrCountryId').value      : '0'),
    country:             (document.getElementById(panel+'AddrCountryName') ? document.getElementById(panel+'AddrCountryName').value    : ''),
  });
  try {
    var res  = await fetch(_CO_SVC_SAVE_ADDR, {method:'POST', body: body});
    var data = await res.json();
    if (spinEl) spinEl.style.display = 'none';
    if (data.success) {
      var newAddr = {id:data.id, label:data.label||'Home', name:data.name||name, company:data.company||'',
                     address:data.address||addr, city:data.city||city, state:data.state||'',
                     zip:data.zip||zip, country:data.country||''};
      _coAddrData.push(newAddr);
      _coRenderAddrSelector(panel, _coAddrData, data.id);
      _coSetAddrId(panel, data.id);
      var cap = panel === 'coDel' ? 'Del' : 'Bil';
      var formEl = document.getElementById('co'+cap+'AddrForm');
      var btnEl  = document.getElementById('co'+cap+'AddNewBtn');
      if (formEl) formEl.style.display = 'none';
      if (btnEl)  btnEl.style.display  = '';
    } else {
      if (errEl) { errEl.textContent = data.msg || 'Error saving address.'; errEl.style.display = ''; }
    }
  } catch(e) {
    if (spinEl) spinEl.style.display = 'none';
    if (errEl)  { errEl.textContent = 'Network error. Please try again.'; errEl.style.display = ''; }
  }
}

function coCancelAddrForm(panel) {
  var cap = panel === 'coDel' ? 'Del' : 'Bil';
  var formEl = document.getElementById('co'+cap+'AddrForm');
  var btnEl  = document.getElementById('co'+cap+'AddNewBtn');
  if (formEl) formEl.style.display = 'none';
  if (btnEl)  btnEl.style.display  = '';
}

function _coAddrLabel(a) { return a.label || 'Home'; }
function _coAddrShort(a) {
  return [a.address, a.city, a.state, a.country].filter(Boolean).slice(0,3).join(', ');
}
function _coEsc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('click', function(e) {
  if (!e.target.closest('#coCustomerInput') && !e.target.closest('#coTsDrop')) {
    var drop = document.getElementById('coTsDrop');
    if (drop) drop.classList.remove('open');
  }
});

/* ── New customer inline form ── */
function coToggleNewCust() {
  var f = document.getElementById('coNewCustForm');
  f.style.display = f.style.display === 'none' ? '' : 'none';
  document.getElementById('coNcError').style.display   = 'none';
  document.getElementById('coNcSpinner').style.display = 'none';
}

async function coSubmitNewCustomer() {
  var name  = document.getElementById('coNcName').value.trim();
  var email = document.getElementById('coNcEmail').value.trim();
  if (!name || !email) {
    document.getElementById('coNcError').textContent   = 'Name and email are required.';
    document.getElementById('coNcError').style.display = '';
    return;
  }
  document.getElementById('coNcSpinner').style.display = '';
  document.getElementById('coNcError').style.display   = 'none';
  var body = new URLSearchParams({
    name, email,
    phone:        document.getElementById('coNcPhone').value.trim(),
    phone_isd:    document.getElementById('coNcIsd').value,
    company_name: document.getElementById('coNcCompany').value.trim(),
    designation:  document.getElementById('coNcDesig').value.trim(),
  });
  try {
    var res  = await fetch('service?urlstring=<?= EncryptURL('action=CreateQuoteCustomer') ?>', {method:'POST', body});
    var data = await res.json();
    document.getElementById('coNcSpinner').style.display = 'none';
    if (data.success) {
      _coCustomers.unshift({id:data.user_id, name:data.user_name, email:data.user_email, company:data.company||''});
      _coSelUserId = data.user_id;
      document.getElementById('coUserId').value = data.user_id;
      document.getElementById('coCustomerInput').value = data.user_name;
      document.getElementById('coCustomerName').textContent  = data.user_name + (data.company ? ' · '+data.company : '');
      document.getElementById('coCustomerEmail').textContent = data.user_email;
      document.getElementById('coCustomerCard').style.display = 'flex';
      coToggleNewCust();
      coLoadAddresses(data.user_id);
    } else {
      document.getElementById('coNcError').textContent   = data.msg || 'Error creating customer.';
      document.getElementById('coNcError').style.display = '';
    }
  } catch(e) {
    document.getElementById('coNcSpinner').style.display = 'none';
    document.getElementById('coNcError').textContent   = 'Network error. Please try again.';
    document.getElementById('coNcError').style.display = '';
  }
}

/* ── Product rows ── */
var _coProdRowIdx = 0;

function coAddProductRow() {
  var idx = _coProdRowIdx++;
  var row = document.createElement('div');
  row.className = 'co-qp-row';
  row.innerHTML =
    '<div>'
      +'<span class="qp-lbl">Category</span>'
      +'<select name="cat_ids[]" class="form-control" style="height:34px;font-size:12px;" onchange="coLoadProds(this,'+idx+')">'+_CO_CAT_OPTS_HTML+'</select>'
    +'</div>'
    +'<div>'
      +'<span class="qp-lbl">Product</span>'
      +'<select name="prod_ids[]" class="form-control" id="co-prod-'+idx+'" style="height:34px;font-size:12px;" onchange="coOnProdSel(this,'+idx+')">'
        +'<option value="">— Select Product —</option>'
      +'</select>'
      +'<div id="co-stock-'+idx+'" style="min-height:15px;margin-top:2px;"></div>'
    +'</div>'
    +'<div>'
      +'<span class="qp-lbl">Qty</span>'
      +'<input type="number" name="quantities[]" id="co-qty-'+idx+'" class="form-control" value="1" min="1" step="1" style="height:34px;font-size:12px;" oninput="coRecalc()">'
    +'</div>'
    +'<div>'
      +'<span class="qp-lbl">Unit Price (€)</span>'
      +'<input type="number" name="unit_amts[]" id="co-price-'+idx+'" class="form-control" value="0" min="0" step="0.01" style="height:34px;font-size:12px;" oninput="coRecalc()">'
    +'</div>'
    +'<div>'
      +'<span class="qp-lbl">Disc %</span>'
      +'<input type="number" name="disc_pcts[]" id="co-disc-'+idx+'" class="form-control" value="0" min="0" max="100" step="0.01" style="height:34px;font-size:12px;" oninput="coRecalc()">'
    +'</div>'
    +'<div>'
      +'<span class="qp-lbl">Final Amt</span>'
      +'<span class="qp-amt" id="co-amt-'+idx+'">€0.00</span>'
    +'</div>'
    +'<div class="qp-rm">'
      +'<button type="button" onclick="_coRemoveProdRow(this)" style="background:none;border:none;cursor:pointer;color:#ef4444;font-size:22px;line-height:1;padding:0;">×</button>'
    +'</div>';
  document.getElementById('coProductRows').appendChild(row);
  coRecalc();
}

function _coRemoveProdRow(btn) {
  var c = document.getElementById('coProductRows');
  if (c.querySelectorAll('.co-qp-row').length <= 1) return;
  btn.closest('.co-qp-row').remove();
  coRecalc();
}

function _coFillProdSel(sel, catId) {
  sel.innerHTML = '<option value="">— Select Product —</option>';
  (OL_CAT_PRODS[catId] || []).forEach(function(p) {
    var o = document.createElement('option');
    o.value = p.id;
    o.textContent = p.name + (p.code ? ' (' + p.code + ')' : '');
    o.dataset.price = p.price;
    o.dataset.stock = p.stock;
    sel.appendChild(o);
  });
}

function coLoadProds(catSel, idx) {
  _coFillProdSel(document.getElementById('co-prod-'+idx), catSel.value);
  var stockEl = document.getElementById('co-stock-'+idx);
  if (stockEl) stockEl.textContent = '';
  coRecalc();
}

function coOnProdSel(prodSel, idx) {
  var opt   = prodSel.options[prodSel.selectedIndex];
  var price = parseFloat(opt.dataset.price || 0);
  var stock = parseInt(opt.dataset.stock   != null ? opt.dataset.stock : -1);
  if (price > 0) document.getElementById('co-price-'+idx).value = price.toFixed(2);
  var stockEl = document.getElementById('co-stock-'+idx);
  if (stockEl) {
    if (!prodSel.value) { stockEl.textContent = ''; }
    else if (stock <= 0) { stockEl.innerHTML = '<span style="color:#dc2626;font-size:11px;">Out of Stock</span>'; }
    else { stockEl.innerHTML = '<span style="color:#059669;font-size:11px;">In Stock: '+stock+'</span>'; }
  }
  coRecalc();
}

/* ── Recalculate totals ── */
function coRecalc() {
  var subtotalGross = 0, totalDisc = 0;
  document.querySelectorAll('#coProductRows .co-qp-row').forEach(function(row, i) {
    var qtyEl   = row.querySelector('input[name="quantities[]"]');
    var priceEl = row.querySelector('input[name="unit_amts[]"]');
    var discEl  = row.querySelector('input[name="disc_pcts[]"]');
    var amtEl   = row.querySelector('.qp-amt');
    var qty   = parseFloat(qtyEl   ? qtyEl.value   : 0) || 0;
    var price = parseFloat(priceEl ? priceEl.value  : 0) || 0;
    var disc  = parseFloat(discEl  ? discEl.value   : 0) || 0;
    var gross = qty * price;
    var d     = gross * disc / 100;
    var final = gross - d;
    if (amtEl) amtEl.textContent = '€' + final.toFixed(2);
    subtotalGross += gross;
    totalDisc     += d;
  });
  var subtotalNet = subtotalGross - totalDisc;
  var vatPct = parseFloat(document.getElementById('coVatPct') ? document.getElementById('coVatPct').value : 0) || 0;
  var vatAmt = subtotalNet * vatPct / 100;
  var ship   = parseFloat(document.getElementById('coShipping') ? document.getElementById('coShipping').value : 0) || 0;
  var grand  = subtotalNet + vatAmt + ship;
  function _s(id,v) { var el=document.getElementById(id); if(el) el.textContent=v; }
  function _sv(id,v){ var el=document.getElementById(id); if(el) el.value=v; }
  _s('coSub',     subtotalGross.toFixed(2));
  _s('coDiscAmt', totalDisc.toFixed(2));
  _s('coVat',     vatAmt.toFixed(2));
  _s('coShipDisp',ship.toFixed(2));
  _s('coGrand',   grand.toFixed(2));
  _sv('coVatAmt',   vatAmt.toFixed(2));
  _sv('coDiscAmtH', totalDisc.toFixed(2));
  _sv('coTaxAmtH',  vatAmt.toFixed(2));
}

function coOnVatInput(val) {
  var exempt = val.trim().length > 3;
  var badge  = document.getElementById('coVatNumBadge');
  var vatP   = document.getElementById('coVatPct');
  if (badge) badge.style.display = exempt ? '' : 'none';
  if (vatP)  { if (exempt) { vatP.value = '0'; vatP.readOnly = true; } else { vatP.readOnly = false; } }
  coRecalc();
}

/* ── Summary tab refresh ── */
function coUpdateSummary() {
  var rows = document.querySelectorAll('#coProductRows .co-qp-row');
  var html = '';
  if (!rows.length) {
    html = '<div style="color:var(--text-muted);font-size:13px;padding:10px 0;">No products added yet.</div>';
  } else {
    html = '<table style="width:100%;border-collapse:collapse;font-size:12px;">'
      +'<thead><tr style="background:#f1f5f9;">'
      +'<th style="padding:6px 10px;text-align:left;font-weight:600;color:#64748b;">Product</th>'
      +'<th style="padding:6px 10px;text-align:center;font-weight:600;color:#64748b;">Qty</th>'
      +'<th style="padding:6px 10px;text-align:right;font-weight:600;color:#64748b;">Unit €</th>'
      +'<th style="padding:6px 10px;text-align:right;font-weight:600;color:#64748b;">Final €</th>'
      +'</tr></thead><tbody>';
    rows.forEach(function(row) {
      var prodSel  = row.querySelector('select[name="prod_ids[]"]');
      var name     = prodSel && prodSel.value ? (prodSel.options[prodSel.selectedIndex] ? prodSel.options[prodSel.selectedIndex].textContent : '—') : '—';
      var qtyEl    = row.querySelector('input[name="quantities[]"]');
      var priceEl  = row.querySelector('input[name="unit_amts[]"]');
      var discEl   = row.querySelector('input[name="disc_pcts[]"]');
      var qty      = parseFloat(qtyEl   ? qtyEl.value   : 0) || 0;
      var price    = parseFloat(priceEl ? priceEl.value  : 0) || 0;
      var disc     = parseFloat(discEl  ? discEl.value   : 0) || 0;
      var total    = qty * price * (1 - disc/100);
      html += '<tr style="border-bottom:1px solid #f1f5f9;">'
        +'<td style="padding:7px 10px;font-weight:600;color:#1e293b;">'+name+'</td>'
        +'<td style="padding:7px 10px;text-align:center;">'+qty+'</td>'
        +'<td style="padding:7px 10px;text-align:right;">€'+price.toFixed(2)+'</td>'
        +'<td style="padding:7px 10px;text-align:right;font-weight:700;">€'+total.toFixed(2)+'</td>'
        +'</tr>';
    });
    html += '</tbody></table>';
  }
  var el = document.getElementById('coSummaryProdList');
  if (el) el.innerHTML = html;
  coRecalc();
}

/* ── Submit ── */
function coSubmitForm() {
  if (!parseInt(document.getElementById('coUserId').value)) {
    alert('Please select a customer first.'); coSwitchTab('customer'); return;
  }
  if (!parseInt(document.getElementById('coAddrId').value)) {
    alert('Please select a delivery address.'); coSwitchTab('address'); return;
  }
  if (!document.querySelector('#coPayModeGroup input[type="radio"]:checked')) {
    alert('Please select a payment method.'); return;
  }
  if (!document.querySelectorAll('#coProductRows .co-qp-row').length) {
    alert('Please add at least one product.'); coSwitchTab('products'); return;
  }
  coRecalc();
  document.getElementById('createOrderForm').submit();
}

/* ── Init & reset ── */
document.addEventListener('DOMContentLoaded', function() {
  coAddProductRow();
  coSwitchTab('customer');
});

function _coResetForm() {
  /* Reset edit mode */
  _coEditMode = false;
  document.getElementById('coEditOrderId').value = 0;
  document.getElementById('coModalTitle').textContent    = 'Create Order';
  document.getElementById('coModalSubtitle').textContent = 'Fill in customer info, address and products.';
  document.getElementById('coSaveBtnTxt').textContent    = 'Create Order';

  _coSelUserId = 0;
  document.getElementById('coUserId').value = 0;
  document.getElementById('coCustomerInput').value = '';
  document.getElementById('coCustomerCard').style.display = 'none';
  _coResetAddrPanels();
  document.getElementById('coProductRows').innerHTML = '';
  _coProdRowIdx = 0;
  coAddProductRow();
  var vatN  = document.getElementById('coVatNumber');
  var vatP  = document.getElementById('coVatPct');
  var ship  = document.getElementById('coShipping');
  var badge = document.getElementById('coVatNumBadge');
  if (vatN)  vatN.value = '';
  if (vatP)  { vatP.value = '19'; vatP.readOnly = false; }
  if (ship)  ship.value = '0';
  if (badge) badge.style.display = 'none';
  document.querySelectorAll('#coPayModeGroup input[type="radio"]').forEach(function(r) { r.checked = false; });
  coSwitchTab('customer');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
