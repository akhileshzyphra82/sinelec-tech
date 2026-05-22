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
$customers   = $controller->getCustomersForQuote();
$allProducts = $controller->getAllProducts(['status'=>'Active']);
$allCats     = $controller->getAllCategories();
$pubBase     = rtrim(sinelec_env('PUBLIC_BASE_URL'), '/');

/* ── Build catProdMap for JS ── */
$catProdMap = [];
foreach ($allProducts as $p) {
    $cid = (int)(float)($p->PRODUCT_CATEGORY_ID ?? 0);
    $catProdMap[$cid][] = [
        'id'   => (int)(float)($p->PRODUCT_ID   ?? 0),
        'name' => (string)($p->PRODUCT_NAME      ?? ''),
        'code' => (string)($p->PRODUCT_CODE      ?? ''),
        'amt'  => (float)($p->PRODUCT_AMT        ?? 0),
        'tax'  => (float)($p->PRODUCT_TAX        ?? 0),
        'disc' => (float)($p->PRODUCT_DISCOUNT   ?? 0),
    ];
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
    <option value="quotation" <?= $fSource==='quotation'?'selected':'' ?>>From Quotation</option>
    <option value="direct"    <?= $fSource==='direct'   ?'selected':'' ?>>Direct</option>
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
  <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
    <span style="font-size:13px;font-weight:600;color:var(--text);">
      Showing <span id="olCount"><?= count($orders) ?></span> order<?= count($orders)!==1?'s':'' ?>
    </span>
    <div style="display:flex;align-items:center;gap:6px;">
      <span style="font-size:12px;color:var(--text-muted);">Per page:</span>
      <select id="olPerPage" class="form-control" style="height:30px;font-size:12px;width:70px;" onchange="olRender()">
        <option value="20">20</option><option value="30">30</option>
        <option value="50">50</option><option value="100">100</option>
      </select>
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="dt" id="olTable">
      <thead>
        <tr>
          <th style="width:40px;">S.No.</th>
          <th style="width:130px;">Order #</th>
          <th>Customer</th>
          <th style="width:120px;">Source</th>
          <th style="width:60px;text-align:center;">Items</th>
          <th style="width:110px;text-align:right;">Total</th>
          <th style="width:140px;">Order Status</th>
          <th style="width:140px;">Payment</th>
          <th style="width:110px;">Mode</th>
          <th style="width:95px;">Date</th>
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
          $cCo      = (string)($o->CUST_COMPANY     ?? '');
          $qid      = (int)(float)($o->QUOTE_ID     ?? 0);
          $items    = (int)($o->ITEM_COUNT           ?? 0);
          $total    = number_format((float)($o->FINAL_TOTAL_AMT ?? 0), 2);
          $dateRaw  = (string)($o->ORDER_DATE        ?? '');
          $dateFmt  = $dateRaw ? date('d M Y', strtotime($dateRaw)) : '—';
          $timeFmt  = $dateRaw ? date('h:i A', strtotime($dateRaw)) : '';
          $oClass   = olOrderBadge($oStatus);
          $pClass   = olPayBadge($pStatus);
          $srcLabel = $qid > 0 ? 'QT-'.str_pad((string)$qid,6,'0',STR_PAD_LEFT) : 'Direct';
          $srcClass = $qid > 0 ? 'badge--violet' : 'badge--grey';
          $searchStr = strtolower($orderNo.' '.$cName.' '.$cEmail.' '.$cCo.' '.$srcLabel);
        ?>
        <tr class="ol-row" data-seq="<?= $i+1 ?>"
            data-search="<?= htmlspecialchars($searchStr) ?>"
            data-os="<?= htmlspecialchars($oStatus) ?>"
            data-ps="<?= htmlspecialchars($pStatus) ?>">
          <td class="td-sm ol-sno"><?= $i+1 ?></td>
          <td>
            <div style="font-weight:700;color:var(--primary);font-size:13px;"><?= htmlspecialchars($orderNo) ?></div>
            <div style="font-size:10px;color:var(--text-muted);margin-top:1px;font-family:monospace;">#<?= $oid ?></div>
          </td>
          <td>
            <div style="font-weight:600;font-size:13px;color:var(--text);"><?= htmlspecialchars($cName) ?></div>
            <?php if ($cEmail): ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($cEmail) ?></div><?php endif; ?>
            <?php if ($cCo):    ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($cCo) ?></div><?php endif; ?>
          </td>
          <td><span class="badge <?= $srcClass ?>" style="font-size:10px;"><?= htmlspecialchars($srcLabel) ?></span></td>
          <td style="text-align:center;font-size:13px;font-weight:600;"><?= $items ?></td>
          <td style="text-align:right;font-size:13px;font-weight:700;color:var(--text);">€<?= $total ?></td>
          <td><span class="badge <?= $oClass ?>"><?= htmlspecialchars($oStatus) ?></span></td>
          <td><span class="badge <?= $pClass ?>" style="font-size:10px;"><?= htmlspecialchars($pStatus) ?></span></td>
          <td style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($mode) ?></td>
          <td>
            <div style="font-size:12px;color:var(--text-muted);"><?= $dateFmt ?></div>
            <?php if($timeFmt): ?><div style="font-size:10px;color:var(--text-muted);opacity:.7;"><?= $timeFmt ?></div><?php endif; ?>
          </td>
          <td style="text-align:center;">
            <div style="display:inline-flex;align-items:center;gap:4px;">
              <!-- Invoice PDF -->
              <a href="order-invoice?id=<?= $oid ?>" target="_blank" title="View Invoice"
                 style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:#f1f5f9;color:#64748b;text-decoration:none;"
                 onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="11" y2="17"/></svg>
              </a>
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
  <!-- Pagination -->
  <div style="padding:12px 18px;border-top:1px solid var(--border);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
    <span style="font-size:12px;color:var(--text-muted);margin-right:4px;" id="olPgInfo"></span>
    <div id="olPager" style="display:flex;gap:4px;flex-wrap:wrap;"></div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     CREATE ORDER MODAL
═══════════════════════════════════════════════════ -->
<div id="createOrderModal" class="modal-overlay" onclick="if(event.target===this)closeModal('createOrderModal')">
  <div class="modal" style="max-width:780px;max-height:92vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="background:linear-gradient(135deg,#4f46e5 0%,#6366f1 100%);flex-shrink:0;">
      <span class="modal-title" style="color:#fff;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:6px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Create Order
      </span>
      <button class="modal-close" onclick="closeModal('createOrderModal')" style="color:#fff;opacity:.8;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div style="overflow-y:auto;flex:1;min-height:0;">
      <form id="createOrderForm" method="POST" action="service?urlstring=<?= EncryptURL('action=CreateDirectOrder') ?>">
        <div style="padding:20px 22px;">

          <!-- Customer -->
          <div style="font-size:11px;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">Customer</div>
          <div style="position:relative;margin-bottom:16px;">
            <input type="hidden" name="user_id" id="coUserId" value="0">
            <input type="text" id="coCustomerInput" class="form-control" placeholder="Search customer by name or email…" autocomplete="off" oninput="coTsFilter()" onfocus="coTsFilter()" style="height:38px;">
            <div id="coTsDrop" class="cust-ts-drop"></div>
          </div>
          <div id="coCustomerCard" style="display:none;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;">
            <div>
              <div style="font-size:13px;font-weight:700;color:#15803d;" id="coCustomerName"></div>
              <div style="font-size:11px;color:#166534;" id="coCustomerEmail"></div>
            </div>
            <button type="button" onclick="coClearCustomer()" style="background:none;border:none;cursor:pointer;color:#6b7280;font-size:18px;line-height:1;">×</button>
          </div>

          <!-- Address -->
          <div id="coAddrSection" style="display:none;margin-bottom:16px;">
            <div style="font-size:11px;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Delivery Address</div>
            <select name="user_address_id" id="coAddrSel" class="form-control" style="height:36px;">
              <option value="0">— Select address —</option>
            </select>
          </div>

          <!-- Products -->
          <div style="font-size:11px;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Products</div>
          <div id="coProductRows"></div>
          <button type="button" class="btn btn--outline" style="height:32px;font-size:12px;padding:0 12px;margin-bottom:16px;" onclick="coAddProductRow()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Product
          </button>

          <!-- Charges -->
          <div class="form-row cols-3" style="margin-bottom:16px;">
            <div class="fg">
              <label style="font-size:12px;">Shipping (€)</label>
              <input type="number" name="shipping_amt" id="coShipping" class="form-control" value="0" min="0" step="0.01" oninput="coRecalc()" style="height:36px;">
            </div>
            <div class="fg">
              <label style="font-size:12px;">Discount (€)</label>
              <input type="number" name="discount_amt" id="coDiscount" class="form-control" value="0" min="0" step="0.01" oninput="coRecalc()" style="height:36px;">
            </div>
            <div class="fg">
              <label style="font-size:12px;">Tax (€)</label>
              <input type="number" name="tax_amt" id="coTax" class="form-control" value="0" min="0" step="0.01" oninput="coRecalc()" style="height:36px;">
            </div>
          </div>

          <!-- Totals preview -->
          <div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 16px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-muted);margin-bottom:4px;">
              <span>Subtotal</span><span id="coSubtotal">€0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-muted);margin-bottom:4px;">
              <span>+ Shipping</span><span id="coShipPreview">€0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-muted);margin-bottom:4px;">
              <span>− Discount</span><span id="coDiscPreview">€0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-muted);margin-bottom:8px;padding-bottom:8px;border-bottom:1px solid var(--border);">
              <span>+ Tax</span><span id="coTaxPreview">€0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:15px;font-weight:800;color:var(--text);">
              <span>Total</span><span id="coTotal" style="color:#059669;">€0.00</span>
            </div>
          </div>

          <!-- Payment Method -->
          <div style="font-size:11px;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Payment Method <span class="req">*</span></div>
          <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px;" id="coPayModeGroup">
            <label class="go-pay-card" for="coMode-invoice">
              <input type="radio" name="order_mode" id="coMode-invoice" value="Invoice" required>
              <div class="go-pay-card-inner">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <div><div style="font-size:13px;font-weight:700;color:#1e293b;">Invoice</div><div style="font-size:11px;color:#6b7280;">Order confirmed · Payment not required</div></div>
              </div>
            </label>
            <label class="go-pay-card" for="coMode-bank">
              <input type="radio" name="order_mode" id="coMode-bank" value="Bank Transfer" required>
              <div class="go-pay-card-inner">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                <div><div style="font-size:13px;font-weight:700;color:#1e293b;">Bank Transfer</div><div style="font-size:11px;color:#6b7280;">Order pending · Payment via bank</div></div>
              </div>
            </label>
            <label class="go-pay-card" for="coMode-gateway">
              <input type="radio" name="order_mode" id="coMode-gateway" value="Payment Gateway" required>
              <div class="go-pay-card-inner">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                <div><div style="font-size:13px;font-weight:700;color:#1e293b;">Payment Gateway</div><div style="font-size:11px;color:#6b7280;">Order pending · Customer pays online</div></div>
              </div>
            </label>
          </div>

          <!-- Optional fields -->
          <div class="form-row cols-2" style="margin-bottom:4px;">
            <div class="fg">
              <label style="font-size:12px;">Customer PO #</label>
              <input type="text" name="customer_po_id" class="form-control" placeholder="PO reference" style="height:36px;">
            </div>
            <div class="fg">
              <label style="font-size:12px;">Supplier Reference</label>
              <input type="text" name="customer_supplier_no" class="form-control" placeholder="Supplier ref" style="height:36px;">
            </div>
          </div>

        </div>
        <div style="padding:14px 22px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px;background:#f8fafc;flex-shrink:0;">
          <button type="button" class="btn btn--ghost" onclick="closeModal('createOrderModal')">Cancel</button>
          <button type="submit" class="btn btn--primary" id="coSubmitBtn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Create Order
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     VIEW ORDER MODAL
═══════════════════════════════════════════════════ -->
<div id="viewOrderModal" class="modal-overlay" onclick="if(event.target===this)closeModal('viewOrderModal')">
  <div class="modal" style="max-width:640px;max-height:92vh;display:flex;flex-direction:column;">
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
  <div class="modal" style="max-width:420px;">
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
          <select name="order_status" id="usOrderStatus" class="form-control">
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
        <div class="fg" style="margin-bottom:18px;">
          <label>Remark <span style="font-size:11px;color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <textarea name="remark" class="form-control" rows="2" placeholder="e.g. Dispatched via DHL, tracking #12345…" style="resize:vertical;min-height:60px;"></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
          <button type="button" class="btn btn--ghost" onclick="closeModal('updateStatusModal')">Cancel</button>
          <button type="submit" class="btn btn--primary">Update Status &amp; Notify</button>
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
/* ── Product row ── */
.co-prod-row { display:grid;grid-template-columns:1fr 70px 80px 60px 60px 28px;gap:6px;align-items:end;margin-bottom:8px;padding:10px;background:#f8fafc;border:1px solid var(--border);border-radius:8px; }
.co-prod-row label { font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px; }
.co-prod-row input,.co-prod-row select { height:34px;font-size:12px; }
/* ── badge extras ── */
.badge--sky { background:#e0f2fe;color:#0369a1;border-color:#bae6fd; }
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
const OL_PRODUCTS  = <?= json_encode(array_map(fn($p) => [
    'id'     => (int)(float)($p->PRODUCT_ID          ?? 0),
    'name'   => (string)($p->PRODUCT_NAME             ?? ''),
    'code'   => (string)($p->PRODUCT_CODE             ?? ''),
    'cat_id' => (int)(float)($p->PRODUCT_CATEGORY_ID  ?? 0),
    'amt'    => (float)($p->PRODUCT_AMT               ?? 0),
    'tax'    => (float)($p->PRODUCT_TAX               ?? 0),
    'disc'   => (float)($p->PRODUCT_DISCOUNT          ?? 0),
], $allProducts)) ?>;

/* ── Table pagination ── */
var _olPage = 1;
var _olRows = [];

function olInit() {
  _olRows = Array.from(document.querySelectorAll('#olTbody .ol-row'));
  olRender();
}

function olRender() {
  var pp    = parseInt(document.getElementById('olPerPage').value, 10) || 20;
  var total = _olRows.length;
  var pages = Math.max(1, Math.ceil(total / pp));
  if (_olPage > pages) _olPage = 1;
  var start = (_olPage - 1) * pp;
  var end   = start + pp;
  var shown = 0;
  _olRows.forEach(function(r, i) {
    var vis = i >= start && i < end;
    r.style.display = vis ? '' : 'none';
    if (vis) { shown++; r.querySelector('.ol-sno').textContent = i + 1; }
  });
  document.getElementById('olCount').textContent = total;
  document.getElementById('olEmpty').style.display = total === 0 ? 'block' : 'none';
  document.getElementById('olPgInfo').textContent = total > 0 ? 'Page ' + _olPage + ' of ' + pages : '';
  var pager = document.getElementById('olPager');
  pager.innerHTML = '';
  if (pages <= 1) return;
  function pgBtn(lbl, pg, active, disabled) {
    var b = document.createElement('button');
    b.textContent = lbl;
    b.className = 'pg-btn' + (active ? ' pg-active' : '');
    b.disabled = disabled;
    b.onclick = function() { _olPage = pg; olRender(); };
    return b;
  }
  pager.appendChild(pgBtn('Prev', _olPage - 1, false, _olPage <= 1));
  var lo = Math.max(1, _olPage - 2), hi = Math.min(pages, _olPage + 2);
  if (lo > 1) { pager.appendChild(pgBtn('1', 1, false, false)); if (lo > 2) pager.innerHTML += '<span class="pg-dots">…</span>'; }
  for (var p = lo; p <= hi; p++) pager.appendChild(pgBtn(String(p), p, p === _olPage, false));
  if (hi < pages) { if (hi < pages - 1) pager.innerHTML += '<span class="pg-dots">…</span>'; pager.appendChild(pgBtn(String(pages), pages, false, false)); }
  pager.appendChild(pgBtn('Next', _olPage + 1, false, _olPage >= pages));
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
  openModal('updateStatusModal');
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
      var srcBadge = o.quote_id > 0 ? '<span class="badge badge--violet" style="font-size:10px;">QT-'+String(o.quote_id).padStart(6,'0')+'</span>' : '<span class="badge badge--grey" style="font-size:10px;">Direct</span>';
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
      html += '</tbody></table></div>'
        +'<div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-bottom:14px;">'
        +'<div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:8px;">Status</div>'
        +'<div style="display:flex;gap:8px;flex-wrap:wrap;"><span class="badge '+o.os_class+'">'+o.order_status+'</span><span class="badge '+o.ps_class+'" style="font-size:10px;">'+o.payment_status+'</span></div></div>';
      if (hist.length) {
        html += '<div><div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:8px;">History</div>'
          +'<div style="display:flex;flex-direction:column;gap:6px;">';
        hist.forEach(function(h) {
          html += '<div style="display:flex;gap:8px;align-items:flex-start;font-size:12px;">'
            +'<div style="width:6px;height:6px;border-radius:50%;background:#6366f1;margin-top:5px;flex-shrink:0;"></div>'
            +'<div><span style="font-weight:600;color:#1e293b;">'+(h.status||'')+'</span>'
            +(h.pay_status ? ' · <span style="color:#64748b;">'+h.pay_status+'</span>' : '')
            +(h.remark ? '<div style="color:#94a3b8;font-size:11px;">'+h.remark+'</div>' : '')
            +'<div style="color:#94a3b8;font-size:10px;">'+h.date+' · '+h.by+'</div></div></div>';
        });
        html += '</div></div>';
      }
      document.getElementById('voBody').innerHTML = html;
    });
}

/* ── Customer typesearch ── */
var _coCustomers = OL_CUSTOMERS;
var _coSelUserId = 0;

function coTsFilter() {
  var q   = (document.getElementById('coCustomerInput').value || '').toLowerCase().trim();
  var drop = document.getElementById('coTsDrop');
  var list = q.length > 0
    ? _coCustomers.filter(function(c) { return (c.name+c.email+c.company).toLowerCase().includes(q); }).slice(0, 15)
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
  document.getElementById('coUserId').value          = id;
  document.getElementById('coCustomerInput').value   = c.name;
  document.getElementById('coTsDrop').classList.remove('open');
  document.getElementById('coCustomerName').textContent  = c.name + (c.company ? ' · ' + c.company : '');
  document.getElementById('coCustomerEmail').textContent = c.email;
  var card = document.getElementById('coCustomerCard');
  card.style.display = 'flex';
  coLoadAddresses(id);
}

function coClearCustomer() {
  _coSelUserId = 0;
  document.getElementById('coUserId').value = 0;
  document.getElementById('coCustomerInput').value = '';
  document.getElementById('coCustomerCard').style.display = 'none';
  document.getElementById('coAddrSection').style.display  = 'none';
  document.getElementById('coAddrSel').innerHTML = '<option value="0">— Select address —</option>';
}

function coLoadAddresses(uid) {
  fetch('service?urlstring=<?= EncryptURL('action=GetUserAddresses') ?>', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'user_id=' + uid
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    var sel = document.getElementById('coAddrSel');
    sel.innerHTML = '<option value="0">— Select address —</option>';
    (data || []).forEach(function(a) {
      var opt = document.createElement('option');
      opt.value = a.id;
      opt.textContent = [a.label, a.address, a.city, a.country].filter(Boolean).join(', ');
      sel.appendChild(opt);
    });
    document.getElementById('coAddrSection').style.display = 'block';
  })
  .catch(function() {});
}

document.addEventListener('click', function(e) {
  if (!e.target.closest('#coCustomerInput') && !e.target.closest('#coTsDrop')) {
    document.getElementById('coTsDrop').classList.remove('open');
  }
});

/* ── Product rows ── */
var _coProdRowCount = 0;

function coAddProductRow(data) {
  data = data || {};
  var idx = _coProdRowCount++;
  var row = document.createElement('div');
  row.className = 'co-prod-row';
  row.dataset.idx = idx;

  var prodOpts = OL_PRODUCTS.map(function(p) {
    return '<option value="'+p.id+'" data-cat="'+p.cat_id+'" data-amt="'+p.amt+'" data-tax="'+p.tax+'" data-disc="'+p.disc+'">'
      + p.name + (p.code ? ' ['+p.code+']' : '') + '</option>';
  }).join('');

  row.innerHTML =
    '<div>'
      +'<label>Product</label>'
      +'<select name="prod_ids[]" class="form-control co-prod-sel" style="height:34px;font-size:12px;" onchange="coOnProdSel(this,'+idx+')">'
        +'<option value="0">— Select —</option>'+prodOpts
      +'</select>'
      +'<input type="hidden" name="cat_ids[]" id="coCat-'+idx+'" value="0">'
    +'</div>'
    +'<div><label>Qty</label><input type="number" name="quantities[]" id="coQty-'+idx+'" class="form-control" value="1" min="0.01" step="0.01" oninput="coRecalc()"></div>'
    +'<div><label>Unit €</label><input type="number" name="unit_amts[]" id="coUnit-'+idx+'" class="form-control" value="0" min="0" step="0.01" oninput="coRecalc()"></div>'
    +'<div><label>Disc %</label><input type="number" name="disc_pcts[]" id="coDisc-'+idx+'" class="form-control" value="0" min="0" max="100" step="0.01" oninput="coRecalc()"></div>'
    +'<div><label>Tax %</label><input type="number" name="tax_pcts[]" id="coTaxP-'+idx+'" class="form-control" value="0" min="0" step="0.01" oninput="coRecalc()"></div>'
    +'<div style="padding-top:18px;"><button type="button" onclick="this.closest(\'.co-prod-row\').remove();coRecalc();" style="background:none;border:none;cursor:pointer;color:#dc2626;width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:6px;" onmouseover="this.style.background=\'#fff5f5\'" onmouseout="this.style.background=\'none\'">'
      +'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
    +'</button></div>';

  document.getElementById('coProductRows').appendChild(row);
}

function coOnProdSel(sel, idx) {
  var opt = sel.options[sel.selectedIndex];
  var catId = opt.dataset.cat || '0';
  var amt   = parseFloat(opt.dataset.amt  || 0);
  var tax   = parseFloat(opt.dataset.tax  || 0);
  var disc  = parseFloat(opt.dataset.disc || 0);
  document.getElementById('coCat-'+idx).value  = catId;
  document.getElementById('coUnit-'+idx).value = amt.toFixed(2);
  document.getElementById('coDisc-'+idx).value = disc.toFixed(2);
  document.getElementById('coTaxP-'+idx).value = tax.toFixed(2);
  coRecalc();
}

function coRecalc() {
  var subtotal = 0;
  document.querySelectorAll('.co-prod-row').forEach(function(row, i) {
    var idx  = row.dataset.idx;
    var qty  = parseFloat(document.getElementById('coQty-'+idx)?.value || 0);
    var unit = parseFloat(document.getElementById('coUnit-'+idx)?.value || 0);
    var disc = parseFloat(document.getElementById('coDisc-'+idx)?.value || 0);
    var tax  = parseFloat(document.getElementById('coTaxP-'+idx)?.value || 0);
    var line = (unit * (1 - disc/100) * (1 + tax/100)) * qty;
    subtotal += isNaN(line) ? 0 : line;
  });
  var ship = parseFloat(document.getElementById('coShipping').value || 0);
  var disc = parseFloat(document.getElementById('coDiscount').value || 0);
  var tax  = parseFloat(document.getElementById('coTax').value      || 0);
  var total = subtotal + ship - disc + tax;
  document.getElementById('coSubtotal').textContent   = '€' + subtotal.toFixed(2);
  document.getElementById('coShipPreview').textContent = '€' + (isNaN(ship)?0:ship).toFixed(2);
  document.getElementById('coDiscPreview').textContent = '€' + (isNaN(disc)?0:disc).toFixed(2);
  document.getElementById('coTaxPreview').textContent  = '€' + (isNaN(tax)?0:tax).toFixed(2);
  document.getElementById('coTotal').textContent      = '€' + (isNaN(total)?0:total).toFixed(2);
}

document.addEventListener('DOMContentLoaded', function() {
  coAddProductRow();
  /* Reset form when modal closes */
  document.getElementById('createOrderModal').addEventListener('click', function(e) {
    if (e.target === this) _coResetForm();
  });
  document.querySelector('#createOrderModal .modal-close').addEventListener('click', _coResetForm);
  document.querySelector('#createOrderModal .btn--ghost').addEventListener('click', _coResetForm);
});

function _coResetForm() {
  document.getElementById('coUserId').value = 0;
  document.getElementById('coCustomerInput').value = '';
  document.getElementById('coCustomerCard').style.display = 'none';
  document.getElementById('coAddrSection').style.display  = 'none';
  document.getElementById('coProductRows').innerHTML = '';
  _coProdRowCount = 0;
  coAddProductRow();
  coRecalc();
  document.querySelectorAll('#coPayModeGroup input[type="radio"]').forEach(function(r) { r.checked = false; });
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
