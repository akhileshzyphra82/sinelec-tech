<?php
require_once __DIR__ . '/account-helpers.php';
require_once __DIR__ . '/../controller/support_controller.php';

$user        = sinelec_require_login();
$userId      = (int)($user['USER_ID'] ?? 0);
$currentPage = 'support';
$pageTitle   = 'New Support Ticket | Sinelec Technologies';
require_once __DIR__ . '/header.php';

$ctrl       = new SupportController();
$categories = $ctrl->getCategories();
$userOrders = $ctrl->getUserOrders($userId);
$BASE_URL   = rtrim((string)sinelec_env('PUBLIC_BASE_URL', ''), '/');

/* Pre-select order from URL (coming from my-orders page) */
$preOrderId   = (int)($_GET['order_id']   ?? 0);
$preCategoryType = trim($_GET['type'] ?? '');   /* 'return' → pre-select Return & Refund */

/* Build order items JS map for pre-population */
$orderItemsMap = [];
foreach ($userOrders as $o) {
    $oid   = (int)(float)($o->USER_ORDER_ID ?? 0);
    $items = $ctrl->getOrderItems($oid, $userId);
    $jsItems = [];
    foreach ($items as $it) {
        $jsItems[] = [
            'order_item_id' => (int)(float)($it->USER_ORDER_ITEM_ID ?? 0),
            'product_id'    => (int)(float)($it->PRODUCT_ID ?? 0),
            'product_name'  => (string)($it->PRODUCT_NAME  ?? ''),
            'product_code'  => (string)($it->PRODUCT_CODE  ?? ''),
            'quantity'      => (int)(float)($it->QUANTITY   ?? 0),
            'unit_price'    => (float)($it->PRODUCT_AMT    ?? 0),
            'image'         => ($it->IMAGE_PATH ?? '') !== '' ? ($BASE_URL . '/' . ltrim((string)$it->IMAGE_PATH, '/')) : '',
        ];
    }
    $orderItemsMap[$oid] = $jsItems;
}
?>

<main class="account-page">
  <div class="wrap">
    <div class="account-shell">
      <?php sinelec_render_account_nav($currentPage); ?>

      <section class="account-main">
        <article class="account-panel nt-shell">

          <!-- Header -->
          <div class="nt-head">
            <a href="my-tickets" class="nt-back">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
              My Tickets
            </a>
            <h1>New Support Ticket</h1>
            <p>Describe your issue and our team will respond shortly.</p>
          </div>

          <!-- Step indicator -->
          <div class="nt-steps" id="ntSteps">
            <div class="nt-step is-active" data-step="1"><span class="nt-step-num">1</span><span class="nt-step-label">Category</span></div>
            <div class="nt-step-line"></div>
            <div class="nt-step" data-step="2"><span class="nt-step-num">2</span><span class="nt-step-label">Details</span></div>
            <div class="nt-step-line"></div>
            <div class="nt-step" data-step="3"><span class="nt-step-num">3</span><span class="nt-step-label">Description</span></div>
          </div>

          <form id="ntForm" novalidate>

            <!-- ── Step 1: Category ── -->
            <div class="nt-step-panel is-active" id="ntPanel1">
              <h2 class="nt-section-title">What do you need help with?</h2>
              <div class="nt-cat-grid" id="ntCatGrid">
                <?php foreach ($categories as $cat):
                  $cid   = (int)(float)($cat->CATEGORY_ID   ?? 0);
                  $cname = htmlspecialchars((string)($cat->CATEGORY_NAME ?? ''));
                  $ctype = (string)($cat->CATEGORY_TYPE ?? 'Other');
                  $isReturn     = in_array($ctype, ['Return & Refund','Return & Replacement'], true);
                  $isPayment    = str_contains($ctype, 'Payment');
                  $isOrderIssue = $ctype === 'Order Issue';
                  $iconClass    = $isReturn ? 'return' : ($isPayment ? 'payment' : ($isOrderIssue ? 'order' : 'other'));
                ?>
                <button type="button" class="nt-cat-card" data-cat-id="<?= $cid ?>" data-cat-type="<?= htmlspecialchars($ctype) ?>" data-cat-name="<?= $cname ?>">
                  <div class="nt-cat-icon nt-cat-icon--<?= $iconClass ?>">
                    <?php if ($isReturn): ?>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                    <?php elseif ($isPayment): ?>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    <?php elseif ($isOrderIssue): ?>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    <?php else: ?>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <?php endif; ?>
                  </div>
                  <span class="nt-cat-label"><?= $cname ?></span>
                </button>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="category_id"   id="ntCategoryId">
              <input type="hidden" name="category_type" id="ntCategoryType">
              <div class="nt-step-nav">
                <span></span>
                <button type="button" class="nt-btn-next" id="ntStep1Next" disabled onclick="ntGoStep(2)">
                  Next: Add Details
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
              </div>
            </div>

            <!-- ── Step 2: Details (order + return items OR subject) ── -->
            <div class="nt-step-panel" id="ntPanel2">
              <h2 class="nt-section-title" id="ntStep2Title">Order &amp; Return Details</h2>

              <!-- Order selector (shown for Return types AND Payment) -->
              <div id="ntOrderSection">
                <label class="nt-label" id="ntOrderLabel">Select Your Order <span id="ntOrderRequired" class="nt-required">*</span></label>
                <div class="nt-select-wrap">
                  <select class="nt-select" id="ntOrderSelect" name="order_id">
                    <option value="">— Select Order —</option>
                    <?php foreach ($userOrders as $o): ?>
                    <option value="<?= (int)(float)($o->USER_ORDER_ID ?? 0) ?>"
                            data-number="<?= htmlspecialchars((string)($o->ORDER_NUMBER ?? '')) ?>">
                      #<?= htmlspecialchars((string)($o->ORDER_NUMBER ?? '')) ?>
                      — <?= htmlspecialchars((string)($o->ORDER_STATUS ?? '')) ?>
                      (<?= date('M d, Y', strtotime((string)($o->ORDER_DATE ?? 'now'))) ?>)
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- Return items (shown only for Return & Refund / Return & Replacement) -->
              <div id="ntReturnSection" style="display:none; margin-top:18px">
                <label class="nt-label">Select Products to Return <span class="nt-required">*</span></label>
                <div id="ntReturnItemsList">
                  <p class="nt-hint">Select an order above to see its products.</p>
                </div>
              </div>

              <!-- Order items (shown only for Order Issue) — all pre-checked -->
              <div id="ntOrderItemsSection" style="display:none; margin-top:18px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;flex-wrap:wrap;gap:6px;">
                  <label class="nt-label" style="margin:0;">Affected Products</label>
                  <div style="display:flex;gap:10px;">
                    <button type="button" class="nt-link-btn" onclick="ntOrderItemsSelectAll(true)">Select All</button>
                    <button type="button" class="nt-link-btn" onclick="ntOrderItemsSelectAll(false)">Deselect All</button>
                  </div>
                </div>
                <p class="nt-hint" style="margin-bottom:10px;">All products are pre-selected. Uncheck any that are not affected.</p>
                <div id="ntOrderItemsList">
                  <p class="nt-hint">Select an order above to see its products.</p>
                </div>
              </div>

              <!-- Subject (shown for Other type) -->
              <div id="ntSubjectSection" style="display:none; margin-top:18px">
                <label class="nt-label" for="ntSubjectInput">Subject <span class="nt-required">*</span></label>
                <input type="text" id="ntSubjectInput" class="nt-input" placeholder="Briefly describe your issue" maxlength="120">
                <p class="nt-hint">Max 120 characters</p>
              </div>

              <!-- Auto-subject for non-Other types -->
              <input type="hidden" id="ntSubjectHidden" name="subject">

              <div class="nt-step-nav">
                <button type="button" class="nt-btn-back" onclick="ntGoStep(1)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                  Back
                </button>
                <button type="button" class="nt-btn-next" id="ntStep2Next" onclick="ntGoStep(3)">
                  Next: Description
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
              </div>
            </div>

            <!-- ── Step 3: Description + Attachments ── -->
            <div class="nt-step-panel" id="ntPanel3">
              <h2 class="nt-section-title">Describe Your Issue</h2>

              <label class="nt-label" for="ntDesc">Description <span class="nt-required">*</span></label>
              <textarea id="ntDesc" name="description" class="nt-textarea"
                placeholder="Provide as much detail as possible — what happened, when, and any error messages you saw." rows="6" maxlength="2000"></textarea>
              <p class="nt-hint"><span id="ntDescCount">0</span>/2000 characters</p>

              <label class="nt-label" style="margin-top:16px">Attachments <span style="color:#94a3b8;font-weight:400">(Optional — up to 5 files, max 5 MB each)</span></label>
              <div class="nt-drop-zone" id="ntDropZone">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <p>Drag &amp; drop files here or <label for="ntFileInput" style="color:#3b82f6;cursor:pointer;font-weight:600">browse</label></p>
                <p style="font-size:11px;color:#94a3b8">JPG, PNG, PDF, GIF, WEBP — max 5 MB each</p>
                <input type="file" id="ntFileInput" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" multiple style="display:none">
              </div>
              <div id="ntFileList" class="nt-file-list"></div>

              <!-- Summary box -->
              <div class="nt-summary-box" id="ntSummary">
                <div class="nt-summary-row"><span>Category:</span><strong id="ntSumCat">—</strong></div>
                <div class="nt-summary-row"><span>Subject:</span><strong id="ntSumSubj">—</strong></div>
                <div class="nt-summary-row" id="ntSumOrderRow" style="display:none"><span>Order:</span><strong id="ntSumOrder">—</strong></div>
                <div class="nt-summary-row" id="ntSumReturnRow" style="display:none"><span>Return items:</span><strong id="ntSumReturn">—</strong></div>
                <div class="nt-summary-row" id="ntSumOrderItemsRow" style="display:none"><span>Affected products:</span><strong id="ntSumOrderItems">—</strong></div>
              </div>

              <div id="ntErrBox" class="nt-error" style="display:none"></div>

              <div class="nt-step-nav">
                <button type="button" class="nt-btn-back" onclick="ntGoStep(2)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                  Back
                </button>
                <button type="submit" class="nt-btn-submit" id="ntSubmitBtn">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  Submit Ticket
                </button>
              </div>
            </div>

          </form>

          <!-- Success screen -->
          <div id="ntSuccess" style="display:none; text-align:center; padding:48px 20px">
            <div style="width:64px;height:64px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h2 style="color:#065f46;margin:0 0 8px">Ticket Submitted!</h2>
            <p style="color:#374151;margin:0 0 6px">Ticket number: <strong id="ntSuccessNum" style="color:#1d4ed8"></strong></p>
            <p style="color:#64748b;font-size:13px;margin:0 0 24px">Our support executive will reply to you soon.</p>
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
              <a id="ntSuccessLink" href="#" class="nt-btn-next" style="text-decoration:none">View Ticket</a>
              <a href="my-tickets" style="padding:10px 20px;border-radius:10px;border:1px solid #e2e8f0;color:#374151;text-decoration:none;font-size:13px;font-weight:600">All Tickets</a>
            </div>
          </div>

        </article>
      </section>
    </div>
  </div>
</main>

<style>
.nt-shell { padding:20px; background:#f3f5f7; border-radius:20px; }
.nt-head { margin-bottom:20px; }
.nt-back {
  display:inline-flex; align-items:center; gap:5px;
  font-size:12px; color:#64748b; text-decoration:none; margin-bottom:10px;
  font-weight:600;
}
.nt-back:hover { color:#1d4ed8; }
.nt-head h1 { font-size:clamp(1.2rem,2vw,1.5rem); color:#182a43; margin:0 0 4px; }
.nt-head p  { color:#64748b; font-size:13px; margin:0; }

/* Steps */
.nt-steps {
  display:flex; align-items:center; gap:0; margin-bottom:24px;
  background:#fff; border-radius:12px; padding:14px 20px;
  border:1px solid #e2e8f0;
}
.nt-step { display:flex; align-items:center; gap:8px; }
.nt-step-num {
  width:28px; height:28px; border-radius:50%; background:#e2e8f0;
  color:#64748b; font-size:12px; font-weight:700;
  display:flex; align-items:center; justify-content:center;
  transition:all .2s;
}
.nt-step.is-active .nt-step-num  { background:#1d4ed8; color:#fff; }
.nt-step.is-done   .nt-step-num  { background:#10b981; color:#fff; }
.nt-step-label { font-size:12px; font-weight:600; color:#94a3b8; }
.nt-step.is-active .nt-step-label { color:#1d4ed8; }
.nt-step.is-done   .nt-step-label { color:#10b981; }
.nt-step-line { flex:1; height:2px; background:#e2e8f0; margin:0 10px; min-width:20px; }

/* Panels */
.nt-step-panel { display:none; }
.nt-step-panel.is-active { display:block; }

.nt-section-title { font-size:15px; font-weight:700; color:#1a2332; margin:0 0 16px; }

/* Category grid — 2 columns */
.nt-cat-grid {
  display:grid; grid-template-columns:repeat(2, 1fr);
  gap:10px; margin-bottom:20px;
}
.nt-cat-card {
  display:flex; flex-direction:row; align-items:center; gap:12px;
  padding:14px 16px; border-radius:12px; border:2px solid #e2e8f0;
  background:#fff; cursor:pointer; transition:all .15s;
  text-align:left;
}
.nt-cat-card:hover { border-color:#93c5fd; background:#f0f9ff; }
.nt-cat-card.is-selected { border-color:#1d4ed8; background:#eff6ff; }
.nt-cat-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.nt-cat-icon--return  { background:#ede9fe; color:#7c3aed; }
.nt-cat-icon--payment { background:#fef3c7; color:#d97706; }
.nt-cat-icon--order   { background:#dcfce7; color:#16a34a; }
.nt-cat-icon--other   { background:#e0f2fe; color:#0369a1; }
.nt-cat-label { font-size:13px; font-weight:700; color:#1a2332; line-height:1.3; }

/* Form fields */
.nt-label { display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px; text-transform:uppercase; letter-spacing:.04em; }
.nt-required { color:#ef4444; }
.nt-input, .nt-select, .nt-textarea {
  width:100%; padding:10px 14px; border-radius:8px;
  border:1.5px solid #e2e8f0; background:#fff;
  font-size:13px; color:#1a2332; outline:none;
  transition:border-color .15s; box-sizing:border-box;
}
.nt-input:focus, .nt-select:focus, .nt-textarea:focus { border-color:#3b82f6; }
.nt-textarea { resize:vertical; min-height:120px; font-family:inherit; }
.nt-select-wrap { position:relative; }
.nt-hint { font-size:11px; color:#94a3b8; margin:4px 0 0; }
.nt-link-btn { background:none; border:none; padding:0; font-size:12px; font-weight:600; color:#3b82f6; cursor:pointer; text-decoration:underline; }
.nt-link-btn:hover { color:#1d4ed8; }

/* Return items */
.nt-return-item {
  display:grid; grid-template-columns:auto 1fr auto;
  gap:12px; align-items:center; padding:12px 14px;
  background:#fff; border:1.5px solid #e2e8f0; border-radius:10px;
  margin-bottom:8px;
}
.nt-return-item.is-selected { border-color:#7c3aed; background:#faf5ff; }
.nt-return-img { width:44px; height:44px; border-radius:8px; object-fit:cover; background:#f1f5f9; }
.nt-return-info h4 { font-size:13px; font-weight:700; color:#1a2332; margin:0 0 2px; }
.nt-return-info p  { font-size:11px; color:#64748b; margin:0; }
.nt-return-qty-wrap { display:flex; align-items:center; gap:8px; }
.nt-return-qty-wrap label { font-size:11px; color:#64748b; white-space:nowrap; }
.nt-qty-input {
  width:64px; padding:6px 8px; border-radius:6px;
  border:1.5px solid #e2e8f0; font-size:13px; font-weight:700; text-align:center;
}
.nt-qty-input:focus { border-color:#7c3aed; outline:none; }

/* Drop zone */
.nt-drop-zone {
  border:2px dashed #d1d5db; border-radius:10px; padding:24px 20px;
  text-align:center; cursor:pointer; transition:border-color .15s, background .15s;
}
.nt-drop-zone:hover, .nt-drop-zone.is-over { border-color:#3b82f6; background:#f0f9ff; }
.nt-drop-zone p { margin:6px 0; color:#64748b; font-size:13px; }

/* File list */
.nt-file-list { display:flex; flex-direction:column; gap:6px; margin-top:8px; }
.nt-file-item {
  display:flex; align-items:center; gap:10px; padding:8px 12px;
  background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;
}
.nt-file-item-name { flex:1; font-size:12px; color:#374151; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.nt-file-item-size { font-size:11px; color:#94a3b8; flex-shrink:0; }
.nt-file-item-rm { background:none; border:none; cursor:pointer; color:#ef4444; padding:0; display:flex; align-items:center; }

/* Summary */
.nt-summary-box {
  background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px;
  padding:14px 16px; margin:16px 0; display:flex; flex-wrap:wrap; gap:10px 24px;
}
.nt-summary-row { display:flex; align-items:center; gap:8px; font-size:12px; }
.nt-summary-row span { color:#64748b; }
.nt-summary-row strong { color:#1a2332; }

/* Error */
.nt-error { background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px 14px; color:#dc2626; font-size:13px; margin-top:12px; }

/* Nav buttons */
.nt-step-nav { display:flex; justify-content:space-between; align-items:center; margin-top:20px; gap:10px; }
.nt-btn-back {
  display:inline-flex; align-items:center; gap:6px; padding:10px 18px;
  border-radius:8px; border:1.5px solid #e2e8f0; background:#fff;
  color:#374151; font-size:13px; font-weight:600; cursor:pointer;
  transition:border-color .15s;
}
.nt-btn-back:hover { border-color:#93c5fd; }
.nt-btn-next, .nt-btn-submit {
  display:inline-flex; align-items:center; gap:6px; padding:10px 22px;
  border-radius:8px; border:none; background:#1d4ed8;
  color:#fff; font-size:13px; font-weight:700; cursor:pointer;
  transition:background .15s;
}
.nt-btn-next:hover, .nt-btn-submit:hover { background:#1e40af; }
.nt-btn-next:disabled { background:#94a3b8; cursor:not-allowed; }
.nt-btn-submit { background:#059669; }
.nt-btn-submit:hover { background:#047857; }
.nt-btn-submit:disabled { background:#94a3b8; cursor:not-allowed; }

@media (max-width:640px) {
  .nt-cat-grid { grid-template-columns:1fr; }
  .nt-steps { padding:10px 12px; gap:4px; }
  .nt-step-label { display:none; }
}
</style>

<script>
window._ntOrderItems = <?= json_encode($orderItemsMap, JSON_HEX_TAG) ?>;
var _ntSelectedFiles = [];
var _ntReturnData    = {};   /* order_item_id => {checked, qty} */
var _ntOrderItemData = {};   /* order_item_id => {checked, qty, product_id} — for Order Issue */

/* ── Step navigation ── */
function ntGoStep(n) {
    if (n === 2 && !ntValidateStep1()) return;
    if (n === 3 && !ntValidateStep2()) return;

    document.querySelectorAll('.nt-step-panel').forEach(function(p, i) {
        p.classList.toggle('is-active', i + 1 === n);
    });
    document.querySelectorAll('.nt-step').forEach(function(s, i) {
        s.classList.remove('is-active','is-done');
        if (i + 1 < n)  s.classList.add('is-done');
        if (i + 1 === n) s.classList.add('is-active');
    });

    if (n === 3) ntUpdateSummary();
    window.scrollTo({top: 0, behavior: 'smooth'});
}

/* ── Step 1 validation ── */
function ntValidateStep1() {
    var cid = document.getElementById('ntCategoryId').value;
    if (!cid) { alert('Please select a category.'); return false; }
    return true;
}

/* ── Category selection ── */
document.querySelectorAll('.nt-cat-card').forEach(function(card) {
    card.addEventListener('click', function() {
        document.querySelectorAll('.nt-cat-card').forEach(function(c) { c.classList.remove('is-selected'); });
        card.classList.add('is-selected');
        document.getElementById('ntCategoryId').value   = card.dataset.catId;
        document.getElementById('ntCategoryType').value = card.dataset.catType;
        document.getElementById('ntStep1Next').disabled = false;

        var ctype = card.dataset.catType;
        var cname = card.dataset.catName;
        ntConfigureStep2(ctype, cname);
    });
});

function ntConfigureStep2(ctype, cname) {
    var isReturn     = (ctype === 'Return & Refund' || ctype === 'Return & Replacement');
    var isPayment    = ctype.indexOf('Payment') !== -1;
    var isOrderIssue = ctype === 'Order Issue';
    var isOther      = (ctype === 'Other');

    document.getElementById('ntStep2Title').textContent =
        isReturn     ? 'Order & Return Details' :
        isOrderIssue ? 'Select Order & Affected Products' :
        isPayment    ? 'Link Your Order' :
                       'Ticket Subject';

    var showOrder = isReturn || isPayment || isOrderIssue;
    document.getElementById('ntOrderSection').style.display        = showOrder ? 'block' : 'none';
    document.getElementById('ntOrderRequired').style.display       = (isReturn || isOrderIssue) ? 'inline' : 'none';
    document.getElementById('ntReturnSection').style.display       = isReturn     ? 'block' : 'none';
    document.getElementById('ntOrderItemsSection').style.display   = isOrderIssue ? 'block' : 'none';
    document.getElementById('ntSubjectSection').style.display      = isOther      ? 'block' : 'none';

    if (!isOther) {
        document.getElementById('ntSubjectHidden').value = cname;
    } else {
        document.getElementById('ntSubjectHidden').value = '';
    }

    /* If an order is already selected, populate items for the new type */
    var oid = parseInt(document.getElementById('ntOrderSelect').value, 10);
    if (oid) {
        if (isReturn)     ntRenderReturnItems(oid);
        if (isOrderIssue) ntRenderOrderItems(oid);
    }
}

/* ── Order select → populate return items / order items ── */
document.getElementById('ntOrderSelect').addEventListener('change', function() {
    var oid   = parseInt(this.value, 10);
    var ctype = document.getElementById('ntCategoryType').value;
    var isReturn     = (ctype === 'Return & Refund' || ctype === 'Return & Replacement');
    var isOrderIssue = (ctype === 'Order Issue');

    _ntReturnData    = {};
    _ntOrderItemData = {};

    if (oid) {
        if (isReturn)     ntRenderReturnItems(oid);
        if (isOrderIssue) ntRenderOrderItems(oid);
    }
});

function ntRenderReturnItems(oid) {
    var items = window._ntOrderItems[oid] || [];
    var container = document.getElementById('ntReturnItemsList');

    if (!items.length) {
        container.innerHTML = '<p class="nt-hint">No products found for this order.</p>';
        return;
    }

    var html = '';
    items.forEach(function(it) {
        var img = it.image ? '<img src="' + it.image + '" class="nt-return-img" onerror="this.style.display=\'none\'">' : '<div class="nt-return-img"></div>';
        html += '<div class="nt-return-item" id="ntItem_' + it.order_item_id + '" data-item-id="' + it.order_item_id + '" data-max="' + it.quantity + '" data-product-id="' + it.product_id + '" data-name="' + ntEsc(it.product_name) + '">';
        html += '<input type="checkbox" onchange="ntToggleReturnItem(this,' + it.order_item_id + ')">';
        html += '<div style="display:flex;gap:10px;align-items:center"><div class="nt-return-img" style="background:#f1f5f9;border-radius:8px;overflow:hidden;flex-shrink:0">' + img + '</div>';
        html += '<div class="nt-return-info"><h4>' + ntEsc(it.product_name) + '</h4>';
        html += '<p>SKU: ' + ntEsc(it.product_code) + ' · Ordered: ' + it.quantity + '</p></div></div>';
        html += '<div class="nt-return-qty-wrap"><label>Return qty</label>';
        html += '<input type="number" class="nt-qty-input" id="ntQty_' + it.order_item_id + '" min="1" max="' + it.quantity + '" value="1" disabled onchange="ntUpdateQty(this,' + it.order_item_id + ')">';
        html += '</div>';
        html += '</div>';
    });

    container.innerHTML = html;
}

/* ── Render order items for "Order Issue" (all pre-checked) ── */
function ntRenderOrderItems(oid) {
    var items     = window._ntOrderItems[oid] || [];
    var container = document.getElementById('ntOrderItemsList');

    if (!items.length) {
        container.innerHTML = '<p class="nt-hint">No products found for this order.</p>';
        _ntOrderItemData = {};
        return;
    }

    _ntOrderItemData = {};
    var html = '';
    items.forEach(function(it) {
        /* Pre-select all by default */
        _ntOrderItemData[it.order_item_id] = { checked: true, qty: it.quantity, product_id: it.product_id };

        var img = it.image
            ? '<img src="' + it.image + '" class="nt-return-img" onerror="this.style.display=\'none\'">'
            : '<div class="nt-return-img" style="background:#f1f5f9;"></div>';

        html += '<div class="nt-return-item is-selected" id="ntOItem_' + it.order_item_id + '"'
              + ' data-item-id="' + it.order_item_id + '"'
              + ' data-product-id="' + it.product_id + '"'
              + ' data-qty="' + it.quantity + '"'
              + ' data-name="' + ntEsc(it.product_name) + '">';
        html += '<input type="checkbox" checked onchange="ntToggleOrderItem(this,' + it.order_item_id + ')">';
        html += '<div style="display:flex;gap:10px;align-items:center;flex:1;">';
        html += '<div class="nt-return-img" style="background:#f1f5f9;border-radius:8px;overflow:hidden;flex-shrink:0">' + img + '</div>';
        html += '<div class="nt-return-info"><h4>' + ntEsc(it.product_name) + '</h4>';
        html += '<p>SKU: ' + ntEsc(it.product_code) + ' &nbsp;·&nbsp; Qty: ' + it.quantity + '</p></div>';
        html += '</div>';
        html += '</div>';
    });

    container.innerHTML = html;
}

function ntToggleOrderItem(cb, itemId) {
    var wrap = document.getElementById('ntOItem_' + itemId);
    wrap.classList.toggle('is-selected', cb.checked);
    if (!_ntOrderItemData[itemId]) _ntOrderItemData[itemId] = {};
    _ntOrderItemData[itemId].checked = cb.checked;
}

function ntOrderItemsSelectAll(checked) {
    document.querySelectorAll('#ntOrderItemsList .nt-return-item input[type=checkbox]').forEach(function(cb) {
        cb.checked = checked;
        var wrap = cb.closest('.nt-return-item');
        if (wrap) wrap.classList.toggle('is-selected', checked);
        var itemId = parseInt(wrap ? wrap.dataset.itemId : 0, 10);
        if (itemId && _ntOrderItemData[itemId]) _ntOrderItemData[itemId].checked = checked;
    });
}

function ntToggleReturnItem(cb, itemId) {
    var wrap = document.getElementById('ntItem_' + itemId);
    var qtyInput = document.getElementById('ntQty_' + itemId);
    wrap.classList.toggle('is-selected', cb.checked);
    qtyInput.disabled = !cb.checked;
    if (!_ntReturnData[itemId]) _ntReturnData[itemId] = {};
    _ntReturnData[itemId].checked = cb.checked;
    _ntReturnData[itemId].qty = parseInt(qtyInput.value, 10) || 1;
}

function ntUpdateQty(input, itemId) {
    var max = parseInt(input.getAttribute('max'), 10);
    var val = parseInt(input.value, 10);
    if (val < 1) { input.value = 1; val = 1; }
    if (val > max) { input.value = max; val = max; }
    if (_ntReturnData[itemId]) _ntReturnData[itemId].qty = val;
}

/* ── Step 2 validation ── */
function ntValidateStep2() {
    var ctype        = document.getElementById('ntCategoryType').value;
    var isReturn     = (ctype === 'Return & Refund' || ctype === 'Return & Replacement');
    var isOrderIssue = (ctype === 'Order Issue');
    var isOther      = (ctype === 'Other');
    var orderId      = document.getElementById('ntOrderSelect').value;

    if (isReturn) {
        if (!orderId) { alert('Please select an order.'); return false; }
        var hasItem = Object.values(_ntReturnData).some(function(d) { return d.checked; });
        if (!hasItem) { alert('Please select at least one product to return.'); return false; }
    }

    if (isOrderIssue) {
        if (!orderId) { alert('Please select an order.'); return false; }
        /* Products are optional — at least one should remain checked */
        var hasOrderItem = Object.values(_ntOrderItemData).some(function(d) { return d.checked; });
        if (!hasOrderItem) { alert('Please select at least one affected product.'); return false; }
    }

    if (isOther) {
        var subj = document.getElementById('ntSubjectInput').value.trim();
        if (!subj) { alert('Please enter a subject for your ticket.'); return false; }
        document.getElementById('ntSubjectHidden').value = subj;
    }

    return true;
}

/* ── Summary update ── */
function ntUpdateSummary() {
    var catCard      = document.querySelector('.nt-cat-card.is-selected');
    var catName      = catCard ? catCard.dataset.catName : '—';
    var subj         = document.getElementById('ntSubjectHidden').value ||
                       document.getElementById('ntSubjectInput').value;
    var orderSel     = document.getElementById('ntOrderSelect');
    var ordNum       = (orderSel.options[orderSel.selectedIndex] || {}).dataset?.number || '';
    var ctype        = document.getElementById('ntCategoryType').value;
    var isReturn     = (ctype === 'Return & Refund' || ctype === 'Return & Replacement');
    var isOrderIssue = (ctype === 'Order Issue');

    document.getElementById('ntSumCat').textContent  = catName;
    document.getElementById('ntSumSubj').textContent = subj || catName;

    var orderRow = document.getElementById('ntSumOrderRow');
    if (ordNum) {
        orderRow.style.display = 'flex';
        document.getElementById('ntSumOrder').textContent = '#' + ordNum;
    } else {
        orderRow.style.display = 'none';
    }

    var returnRow = document.getElementById('ntSumReturnRow');
    if (isReturn) {
        var cnt = Object.values(_ntReturnData).filter(function(d) { return d.checked; }).length;
        returnRow.style.display = 'flex';
        document.getElementById('ntSumReturn').textContent = cnt + ' product' + (cnt !== 1 ? 's' : '');
    } else {
        returnRow.style.display = 'none';
    }

    var orderItemsRow = document.getElementById('ntSumOrderItemsRow');
    if (isOrderIssue) {
        var oiCnt = Object.values(_ntOrderItemData).filter(function(d) { return d.checked; }).length;
        orderItemsRow.style.display = 'flex';
        document.getElementById('ntSumOrderItems').textContent = oiCnt + ' product' + (oiCnt !== 1 ? 's' : '');
    } else {
        orderItemsRow.style.display = 'none';
    }
}

/* ── Description char count ── */
document.getElementById('ntDesc').addEventListener('input', function() {
    document.getElementById('ntDescCount').textContent = this.value.length;
});

/* ── File upload ── */
var dropZone  = document.getElementById('ntDropZone');
var fileInput = document.getElementById('ntFileInput');

dropZone.addEventListener('click', function() { fileInput.click(); });
fileInput.addEventListener('change', function() { ntAddFiles(this.files); this.value = ''; });

dropZone.addEventListener('dragover',  function(e) { e.preventDefault(); dropZone.classList.add('is-over'); });
dropZone.addEventListener('dragleave', function()  { dropZone.classList.remove('is-over'); });
dropZone.addEventListener('drop', function(e) {
    e.preventDefault(); dropZone.classList.remove('is-over');
    ntAddFiles(e.dataTransfer.files);
});

function ntAddFiles(files) {
    Array.from(files).forEach(function(f) {
        if (_ntSelectedFiles.length >= 5) return;
        if (f.size > 5 * 1024 * 1024) { alert(f.name + ' exceeds 5 MB limit.'); return; }
        _ntSelectedFiles.push(f);
    });
    ntRenderFileList();
}

function ntRenderFileList() {
    var list = document.getElementById('ntFileList');
    list.innerHTML = _ntSelectedFiles.map(function(f, i) {
        var sz = f.size < 1024 ? f.size + ' B' : f.size < 1048576 ? (f.size/1024).toFixed(1) + ' KB' : (f.size/1048576).toFixed(1) + ' MB';
        return '<div class="nt-file-item">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>' +
            '<span class="nt-file-item-name">' + ntEsc(f.name) + '</span>' +
            '<span class="nt-file-item-size">' + sz + '</span>' +
            '<button type="button" class="nt-file-item-rm" onclick="ntRemoveFile(' + i + ')" title="Remove">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
            '</button></div>';
    }).join('');
}

function ntRemoveFile(i) {
    _ntSelectedFiles.splice(i, 1);
    ntRenderFileList();
}

/* ── Form submit ── */
document.getElementById('ntForm').addEventListener('submit', function(e) {
    e.preventDefault();
    ntSubmitTicket();
});

async function ntSubmitTicket() {
    var btn  = document.getElementById('ntSubmitBtn');
    var errBox = document.getElementById('ntErrBox');
    errBox.style.display = 'none';

    var desc = document.getElementById('ntDesc').value.trim();
    if (!desc) { errBox.textContent = 'Please enter a description.'; errBox.style.display = 'block'; return; }

    btn.disabled = true;
    btn.textContent = 'Submitting…';

    var ctype        = document.getElementById('ntCategoryType').value;
    var isReturn     = (ctype === 'Return & Refund' || ctype === 'Return & Replacement');
    var isOrderIssue = (ctype === 'Order Issue');
    var orderId      = parseInt(document.getElementById('ntOrderSelect').value, 10) || 0;

    var returnItems = [];
    if (isReturn) {
        var items = document.querySelectorAll('#ntReturnItemsList .nt-return-item');
        items.forEach(function(row) {
            var cb = row.querySelector('input[type=checkbox]');
            if (!cb || !cb.checked) return;
            var itemId = parseInt(row.dataset.itemId, 10);
            var qty    = parseInt(document.getElementById('ntQty_' + itemId).value, 10);
            var prodId = parseInt(row.dataset.productId, 10);
            returnItems.push({ order_item_id: itemId, product_id: prodId, order_id: orderId, return_qty: qty, return_reason: '' });
        });
    }
    if (isOrderIssue) {
        document.querySelectorAll('#ntOrderItemsList .nt-return-item').forEach(function(row) {
            var cb = row.querySelector('input[type=checkbox]');
            if (!cb || !cb.checked) return;
            var itemId = parseInt(row.dataset.itemId, 10);
            var qty    = parseInt(row.dataset.qty, 10) || 1;
            var prodId = parseInt(row.dataset.productId, 10);
            returnItems.push({ order_item_id: itemId, product_id: prodId, order_id: orderId, return_qty: qty, return_reason: '' });
        });
    }

    var payload = {
        category_id:  parseInt(document.getElementById('ntCategoryId').value, 10),
        order_id:     orderId,
        subject:      document.getElementById('ntSubjectHidden').value ||
                      document.getElementById('ntSubjectInput').value,
        description:  desc,
        return_items: returnItems,
    };

    try {
        var res = await fetch('ajax/ticket?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        var data = await res.json();

        if (!data.ok) {
            errBox.textContent = data.msg || 'Could not create ticket.';
            errBox.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Submit Ticket';
            return;
        }

        /* Upload files if any */
        if (_ntSelectedFiles.length && data.ticket_number) {
            var fd = new FormData();
            fd.append('ticket_id',     data.ticket_id);
            fd.append('ticket_number', data.ticket_number);
            fd.append('message_id',    data.message_id || 0);
            _ntSelectedFiles.forEach(function(f) { fd.append('files[]', f); });
            await fetch('ajax/ticket?action=upload', { method: 'POST', body: fd }).catch(function(){});
        }

        /* Show success */
        document.getElementById('ntForm').style.display = 'none';
        document.getElementById('ntSteps').style.display = 'none';
        document.getElementById('ntSuccess').style.display = 'block';
        document.getElementById('ntSuccessNum').textContent = data.ticket_number;
        document.getElementById('ntSuccessLink').href = 'ticket-detail?id=' + data.ticket_number;

    } catch(err) {
        errBox.textContent = 'Network error. Please try again.';
        errBox.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Submit Ticket';
    }
}

function ntEsc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Pre-select from order list ── */
(function() {
    var preOrderId   = <?= $preOrderId ?>;
    var preType      = <?= json_encode($preCategoryType) ?>;

    if (preOrderId) {
        var sel = document.getElementById('ntOrderSelect');
        for (var i = 0; i < sel.options.length; i++) {
            if (parseInt(sel.options[i].value, 10) === preOrderId) {
                sel.selectedIndex = i;
                break;
            }
        }
    }

    /* Auto-click the matching category card based on URL ?type= param */
    var typeMap = {
        'return': 'Return & Refund',
        'order':  'Order Issue',
    };
    var targetType = typeMap[preType] || null;
    if (targetType) {
        var cards = document.querySelectorAll('.nt-cat-card');
        for (var c = 0; c < cards.length; c++) {
            if (cards[c].dataset.catType === targetType) {
                cards[c].click();
                break;
            }
        }
    }
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
