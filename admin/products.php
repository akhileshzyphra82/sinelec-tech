<?php
/*
 * Run these once if not already done:
 *
 * ALTER TABLE tbl_product_img MODIFY COLUMN image_ext VARCHAR(500) NOT NULL DEFAULT '';
 * ALTER TABLE tbl_product_img
 *     ADD COLUMN IF NOT EXISTS image_name  VARCHAR(150) NOT NULL DEFAULT '',
 *     ADD COLUMN IF NOT EXISTS hyper_link  VARCHAR(300)          DEFAULT NULL;
 *
 * (MySQL < 8 does not support IF NOT EXISTS on columns — run ADD COLUMN only if missing.)
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'products';
$pageTitle   = 'Products';

$controller = new AdminController();

$canView   = sinelec_can('view');
$canAdd    = sinelec_can('add');
$canEdit   = sinelec_can('edit');
$canDelete = sinelec_can('delete');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

$products    = $controller->getAllProducts([]);
$allCats     = $controller->getAllCategories();
$allImages   = $controller->getAllProductImagesIndexed();

/* Sample codes — one query, indexed by product_id */
$allSampleCodes = $controller->getAllSampleCodesIndexed();
$pubBase   = rtrim(sinelec_env('PUBLIC_BASE_URL'), '/');

/* Category flat lookup */
$catMap = [];
foreach ($allCats as $c) {
    $cid = (int)(float)($c->PRODUCT_CATEGORY_ID ?? 0);
    if ($cid > 0) $catMap[$cid] = (string)($c->PRODUCT_CATEGORY_NAME ?? '');
}

ob_start();
?>

<!-- Quill CSS -->
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<div class="pg-header">
  <div>
    <div class="pg-title">Products</div>
    <div class="pg-sub">Manage your product catalogue — <?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> total.</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <button class="btn btn--outline" onclick="exportProdXLS()" id="exportBtn">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export XLS
    </button>
    <?php if ($canAdd): ?>
    <button class="btn btn--primary" onclick="openProdModal(0)">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Product
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- Filters -->
<div style="display:flex;gap:10px;margin-bottom:18px;align-items:center;flex-wrap:wrap;">
  <div style="position:relative;flex:1;min-width:220px;max-width:340px;">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="prodSearch" class="form-control" placeholder="Search name, code, label…" style="padding-left:32px;height:36px;" oninput="prodOnSearch()">
  </div>
  <select id="prodCatFilter" class="form-control" style="height:36px;width:auto;min-width:160px;" onchange="prodOnSearch()">
    <option value="">All Categories</option>
    <?php foreach ($allCats as $c): ?>
    <option value="<?= (int)(float)($c->PRODUCT_CATEGORY_ID ?? 0) ?>">
      <?= htmlspecialchars((string)($c->PRODUCT_CATEGORY_NAME ?? '')) ?>
    </option>
    <?php endforeach; ?>
  </select>
  <select id="prodStatusFilter" class="form-control" style="height:36px;width:auto;min-width:130px;" onchange="prodOnSearch()">
    <option value="">All Status</option>
    <option value="Active">Active</option>
    <option value="In-Active">In-Active</option>
  </select>
</div>

<!-- Table card -->
<div class="card">
  <?php if (empty($products)): ?>
  <div class="card-body">
    <div class="empty-state">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
      <h3>No products found</h3>
      <p>Add your first product to get started.</p>
      <?php if ($canAdd): ?><button class="btn btn--primary" onclick="openProdModal(0)">Add Product</button><?php endif; ?>
    </div>
  </div>
  <?php else: ?>

  <!-- Pagination Bar -->
  <div class="emp-pgbar" id="prodPgBar">
    <div class="emp-pgbar-info" id="prodPgInfo">Showing 1–20 of <?= count($products) ?> products</div>
    <div class="emp-pgbar-right">
      <span class="emp-pgbar-rpp-label">Per page</span>
      <select id="prodRpp" class="emp-pgbar-rpp-sel">
        <option value="20" selected>20</option>
        <option value="30">30</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <button class="emp-pgbar-apply" onclick="prodApplyRpp()">Apply</button>
      <div class="emp-pgbar-nav" id="prodNav"></div>
    </div>
  </div>

  <div class="card-body card-body--flush">
    <table class="dt" id="prodTable">
      <thead>
        <tr>
          <th style="width:44px;">S.No.</th>
          <th style="width:56px;">Image</th>
          <th>Product</th>
          <th style="width:150px;">Category</th>
          <th style="width:90px;text-align:right;">Price</th>
          <th style="width:90px;text-align:center;">Status</th>
          <th style="width:100px;text-align:center;">Stock</th>
          <th style="width:80px;text-align:center;">Label</th>
          <th style="width:60px;text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody id="prodTbody">
        <?php foreach ($products as $i => $p):
          $pid       = (int)(float)($p->PRODUCT_ID ?? 0);
          $pName     = (string)($p->PRODUCT_NAME ?? '');
          $pCode     = (string)($p->PRODUCT_CODE ?? '');
          $catId     = (int)(float)($p->PRODUCT_CATEGORY_ID ?? 0);
          $catName   = (string)($p->PRODUCT_CATEGORY_NAME ?? '');
          $pStatus   = (string)($p->PRODUCT_STATUS ?? 'Active');
          $thumbExt  = (string)($p->THUMB_EXT ?? '');
          $thumbUrl  = ($thumbExt !== '' && strpos($thumbExt, '/') !== false) ? $pubBase.'/'.$thumbExt : '';
          $amt       = (float)($p->PRODUCT_AMT ?? 0);
          $label     = (string)($p->LABEL ?? '');
          $remaining = (int)(float)($p->TOTAL_REMAINING ?? 0);
          $total     = (int)(float)($p->TOTAL_PRODUCT ?? 0);
          $threshold = (float)($p->PRODUCT_THRESHOLD ?? 1);
          $initial   = strtoupper(substr(trim($pName), 0, 1)) ?: '?';
          $searchStr = strtolower($pName.' '.$pCode.' '.$catName.' '.$label);

          /* Stock colour */
          if ($total === 0) {
              $stockStyle = 'color:var(--text-muted);';
              $stockTxt   = '—';
          } elseif ($remaining <= 0) {
              $stockStyle = 'background:#fee2e2;color:#dc2626;';
              $stockTxt   = 'Out: 0/'.$total;
          } elseif ($remaining <= $threshold) {
              $stockStyle = 'background:#fef3c7;color:#b45309;';
              $stockTxt   = 'Low: '.$remaining.'/'.$total;
          } else {
              $stockStyle = 'background:#dcfce7;color:#15803d;';
              $stockTxt   = $remaining.'/'.$total;
          }
        ?>
        <tr class="prod-row"
            data-search="<?= htmlspecialchars($searchStr) ?>"
            data-cat="<?= $catId ?>"
            data-status="<?= htmlspecialchars($pStatus) ?>"
            data-seq="<?= $i + 1 ?>">
          <td class="td-sm prod-sno"><?= $i + 1 ?></td>
          <td>
            <?php if ($thumbUrl): ?>
              <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="" class="prod-thumb">
            <?php else: ?>
              <div class="prod-initial"><?= htmlspecialchars($initial) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600;color:var(--text);font-size:13px;line-height:1.3;"><?= htmlspecialchars($pName) ?></div>
            <?php if ($pCode): ?>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px;font-family:monospace;"><?= htmlspecialchars($pCode) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--text-muted);"><?= $catName ? htmlspecialchars($catName) : '<span style="color:var(--text-muted);">—</span>' ?></td>
          <td style="text-align:right;font-size:13px;font-weight:600;color:var(--text);">
            <?= $amt > 0 ? '₹'.number_format($amt, 2) : '<span style="color:var(--text-muted);">—</span>' ?>
          </td>
          <td style="text-align:center;">
            <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;display:inline-block;<?= $pStatus==='Active' ? 'background:#dcfce7;color:#15803d;' : 'background:#fee2e2;color:#dc2626;' ?>">
              <?= htmlspecialchars($pStatus) ?>
            </span>
          </td>
          <td style="text-align:center;">
            <?php if ($total > 0): ?>
            <span style="<?= $stockStyle ?>font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px;display:inline-block;white-space:nowrap;">
              <?= htmlspecialchars($stockTxt) ?>
            </span>
            <?php else: ?>
            <span style="color:var(--text-muted);font-size:12px;">—</span>
            <?php endif; ?>
          </td>
          <td style="text-align:center;">
            <?php if ($label): ?>
            <span style="background:#eef2ff;color:#4f46e5;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;display:inline-block;white-space:nowrap;">
              <?= htmlspecialchars($label) ?>
            </span>
            <?php else: ?>
            <span style="color:var(--text-muted);font-size:12px;">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($canEdit || $canDelete): ?>
            <div class="kbm-wrap">
              <button class="kbm-btn" onclick="toggleKbm(this)" title="Actions">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
              </button>
              <div class="kbm-drop">
                <?php if ($canEdit): ?>
                <button class="kbm-item" onclick="closeKbm(this);openProdModal(<?= $pid ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </button>
                <?php endif; ?>
                <button class="kbm-item" onclick="closeKbm(this);openViewModal(<?= $pid ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  View Details
                </button>
                <?php if ($canEdit): ?>
                <button class="kbm-item" onclick="closeKbm(this);openImagesModal(<?= $pid ?>,<?= htmlspecialchars(json_encode($pName), ENT_QUOTES) ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  Manage Images
                </button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <div class="kbm-divider"></div>
                <button class="kbm-item kbm-item--danger" onclick="closeKbm(this);confirmDeleteProd(<?= $pid ?>,<?= htmlspecialchars(json_encode($pName), ENT_QUOTES) ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                  Delete
                </button>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div id="prodNoResults" style="display:none;padding:40px;text-align:center;color:var(--text-muted);font-size:13px;">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto 10px;opacity:.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      No products match your search.
    </div>
  </div>
  <?php endif; ?>
</div>


<!-- ════════════════════════════════════════════════════
     ADD / EDIT MODAL  (tabbed)
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="prodModal">
  <div class="modal prod-modal-wide">

    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div>
        <div class="modal-title" id="prodModalTitle">Add Product</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="prodModalSub">Fill in the product details below.</div>
      </div>
      <button class="modal-close" onclick="closeModal('prodModal')" style="font-size:22px;line-height:1;">×</button>
    </div>

    <!-- Tabs -->
    <div class="prod-tabs" id="prodTabs">
      <button class="prod-tab active" data-tab="basic" onclick="switchProdTab('basic',this)">Basic Info</button>
      <button class="prod-tab" data-tab="pricing" onclick="switchProdTab('pricing',this)">Pricing</button>
      <button class="prod-tab" data-tab="desc" onclick="switchProdTab('desc',this)">Description</button>
      <button class="prod-tab" data-tab="spec" onclick="switchProdTab('spec',this)">Specification</button>
      <button class="prod-tab" data-tab="dets" onclick="switchProdTab('dets',this)">Details</button>
      <button class="prod-tab" data-tab="sample" onclick="switchProdTab('sample',this)">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;vertical-align:-2px;"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        Sample Code
      </button>
    </div>

    <div style="overflow-y:auto;flex:1;padding:22px;">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=SaveProduct') ?>" id="prodForm" autocomplete="off">
        <input type="hidden" name="product_id" id="fProdId" value="0">
        <input type="hidden" name="product_description"  id="fProdDescHidden">
        <input type="hidden" name="product_specification" id="fProdSpecHidden">
        <input type="hidden" name="product_details"      id="fProdDetsHidden">

        <!-- ─── TAB: Basic Info ─── -->
        <div id="pt-basic" class="prod-tab-panel">
          <div class="form-row cols-2" style="margin-bottom:14px;">
            <div class="fg">
              <label>Product Name <span class="req">*</span></label>
              <input type="text" name="product_name" id="fProdName" class="form-control" placeholder="e.g. Industrial Motor Drive" required>
            </div>
            <div class="fg">
              <label>Product Code / SKU</label>
              <input type="text" name="product_code" id="fProdCode" class="form-control" placeholder="e.g. IMD-4500X">
            </div>
          </div>
          <div class="form-row cols-2" style="margin-bottom:14px;">
            <div class="fg">
              <label>Category</label>
              <select name="product_category_id" id="fProdCat" class="form-control">
                <option value="0">— Uncategorised —</option>
                <?php foreach ($allCats as $c): ?>
                <option value="<?= (int)(float)($c->PRODUCT_CATEGORY_ID ?? 0) ?>">
                  <?= htmlspecialchars((string)($c->PRODUCT_CATEGORY_NAME ?? '')) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="fg">
              <label>Status</label>
              <select name="product_status" id="fProdStatus" class="form-control">
                <option value="Active">Active</option>
                <option value="In-Active">In-Active</option>
              </select>
            </div>
          </div>
          <div class="form-row cols-3" style="margin-bottom:14px;">
            <div class="fg">
              <label>Display</label>
              <select name="display_flag" id="fProdDisplay" class="form-control">
                <option value="Yes">Yes</option>
                <option value="No">No</option>
              </select>
            </div>
            <div class="fg">
              <label>Priority</label>
              <input type="number" name="priorty" id="fProdPrio" class="form-control" placeholder="0" min="0">
            </div>
            <div class="fg">
              <label>Label</label>
              <select name="label" id="fProdLabel" class="form-control">
                <option value="">— None —</option>
                <option value="New">New</option>
                <option value="Popular">Popular</option>
                <option value="Best Seller">Best Seller</option>
                <option value="Trending">Trending</option>
                <option value="Hot">Hot</option>
                <option value="Featured">Featured</option>
                <option value="Top Rated">Top Rated</option>
                <option value="Limited Edition">Limited Edition</option>
                <option value="Sale">Sale</option>
                <option value="Clearance">Clearance</option>
                <option value="Coming Soon">Coming Soon</option>
              </select>
            </div>
          </div>
          <div class="form-row cols-2" style="margin-bottom:0;">
            <div class="fg">
              <label>Rating <span style="font-size:11px;color:var(--text-muted);font-weight:400;">(0.0 – 5.0)</span></label>
              <input type="number" name="rating" id="fProdRating" class="form-control" placeholder="e.g. 4.5" min="0" max="5" step="0.1">
            </div>
            <div class="fg">
              <label>Stock Threshold</label>
              <input type="number" name="product_threshold" id="fProdThreshold" class="form-control" placeholder="1" min="0" value="1">
            </div>
          </div>
        </div>

        <!-- ─── TAB: Pricing ─── -->
        <div id="pt-pricing" class="prod-tab-panel" style="display:none;">
          <div class="form-row cols-2" style="margin-bottom:14px;">
            <div class="fg">
              <label>Price (₹)</label>
              <input type="number" name="product_amt" id="fProdAmt" class="form-control" placeholder="0.00" min="0" step="0.01">
            </div>
            <div class="fg">
              <label>Tax (%)</label>
              <input type="number" name="product_tax" id="fProdTax" class="form-control" placeholder="0" min="0" step="0.1">
            </div>
          </div>
          <div class="form-row cols-2" style="margin-bottom:14px;">
            <div class="fg">
              <label>Discount (%)</label>
              <input type="number" name="product_discount" id="fProdDisc" class="form-control" placeholder="0" min="0" max="100" step="0.1">
            </div>
            <div class="fg">
              <label>Offer Percentage (%)</label>
              <input type="number" name="offer_percentage" id="fProdOffer" class="form-control" placeholder="0" min="0" max="100" step="0.1">
            </div>
          </div>
          <!-- Live price preview -->
          <div id="pricePreview" style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:14px 16px;margin-top:4px;">
            <div style="font-size:12px;color:var(--text-muted);font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px;">Price Preview</div>
            <div style="display:flex;gap:24px;flex-wrap:wrap;">
              <div><div style="font-size:11px;color:var(--text-muted);">Base Price</div><div id="ppBase" style="font-size:16px;font-weight:700;color:var(--text);">₹0.00</div></div>
              <div><div style="font-size:11px;color:var(--text-muted);">After Tax</div><div id="ppTax" style="font-size:16px;font-weight:700;color:#4f46e5;">₹0.00</div></div>
              <div><div style="font-size:11px;color:var(--text-muted);">After Discount</div><div id="ppDisc" style="font-size:16px;font-weight:700;color:#059669;">₹0.00</div></div>
            </div>
          </div>
        </div>

        <!-- ─── TAB: Description ─── -->
        <div id="pt-desc" class="prod-tab-panel" style="display:none;">
          <div class="fg">
            <label>Product Description</label>
            <div id="prodDescEditor" style="min-height:240px;border-radius:0 0 6px 6px;font-size:13px;"></div>
          </div>
        </div>

        <!-- ─── TAB: Specification ─── -->
        <div id="pt-spec" class="prod-tab-panel" style="display:none;">
          <div class="fg">
            <label>Product Specification</label>
            <div id="prodSpecEditor" style="min-height:240px;border-radius:0 0 6px 6px;font-size:13px;"></div>
          </div>
        </div>

        <!-- ─── TAB: Details ─── -->
        <div id="pt-dets" class="prod-tab-panel" style="display:none;">
          <div class="fg">
            <label>Additional Details</label>
            <div id="prodDetsEditor" style="min-height:240px;border-radius:0 0 6px 6px;font-size:13px;"></div>
          </div>
        </div>

        <!-- ─── TAB: Sample Code ─── -->
        <div id="pt-sample" class="prod-tab-panel" style="display:none;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div>
              <div style="font-size:13px;font-weight:600;color:var(--text);">Sample Code Repository Links</div>
              <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">Add one or more repository / documentation URLs for this product.</div>
            </div>
            <button type="button" class="btn btn--outline" style="height:34px;font-size:12px;padding:0 14px;" onclick="addSampleRow()">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add More
            </button>
          </div>

          <!-- Column headers -->
          <div class="sc-header-row">
            <div>Language / Technology</div>
            <div>IDE / Compiler</div>
            <div>Type</div>
            <div>OS</div>
            <div>URL</div>
            <div>Upload Date</div>
            <div></div>
          </div>

          <!-- Dynamic rows container -->
          <div id="sampleCodeRows"></div>
        </div>

        <div style="display:flex;gap:10px;justify-content:space-between;padding-top:16px;border-top:1px solid var(--border);margin-top:20px;align-items:center;">
          <button type="button" class="btn btn--outline" onclick="closeModal('prodModal')">Cancel</button>
          <div style="display:flex;gap:8px;align-items:center;">
            <button type="button" class="btn btn--outline" id="prodPrevBtn" onclick="prodNavTab(-1)" style="display:none;">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
              Previous
            </button>
            <button type="button" class="btn btn--secondary" id="prodNextBtn" onclick="prodNavTab(1)">
              Next
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <button type="submit" class="btn btn--primary" id="prodSubmitBtn">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              Save Product
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════════════════
     MANAGE IMAGES MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="imagesModal">
  <div class="modal" style="max-width:760px;max-height:94vh;display:flex;flex-direction:column;">
    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div>
        <div class="modal-title" id="imagesModalTitle">Manage Images</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="imagesModalSub">Upload and manage product images &amp; manuals.</div>
      </div>
      <button class="modal-close" onclick="closeModal('imagesModal')" style="font-size:22px;line-height:1;">×</button>
    </div>

    <!-- Image modal tabs -->
    <div style="display:flex;border-bottom:1px solid var(--border);background:#fafbfc;flex-shrink:0;padding:0 22px;">
      <button class="img-tab active" id="imgTab-product" onclick="switchImgTab('product')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        Product Images
      </button>
      <button class="img-tab" id="imgTab-manual" onclick="switchImgTab('manual')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Manual / Documents
      </button>
    </div>

    <div style="overflow-y:auto;flex:1;">

      <!-- ── PRODUCT IMAGES TAB ── -->
      <div id="imgPanel-product" style="padding:20px;">

        <!-- Upload Section -->
        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:20px;">
          <div style="font-size:12px;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;">Upload Product Images</div>

          <div style="display:flex;align-items:flex-end;gap:10px;margin-bottom:14px;">
            <div class="fg" style="max-width:180px;">
              <label style="font-size:12px;">How many images to upload?</label>
              <input type="number" id="prodImgCount" class="form-control" value="1" min="1" max="20" style="height:34px;">
            </div>
            <button type="button" class="btn btn--outline" style="height:34px;padding:0 14px;font-size:13px;" onclick="buildImgBoxes('product')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add Boxes
            </button>
          </div>

          <form method="POST" action="service?urlstring=<?= EncryptURL('action=AddProductImages') ?>" enctype="multipart/form-data" id="imgUploadFormProduct">
            <input type="hidden" name="product_id" id="imgUploadPidProduct">
            <input type="hidden" name="image_for" value="Product">

            <div id="imgBoxesProduct"></div>

            <div style="display:flex;justify-content:flex-end;margin-top:12px;">
              <button type="submit" class="btn btn--primary" id="imgUploadBtnProduct" disabled>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>
                Upload Images
              </button>
            </div>
          </form>
        </div>

        <!-- Existing product images -->
        <div>
          <div style="font-size:12px;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Uploaded Product Images</div>
          <div id="existingProductImagesArea"></div>
        </div>
      </div>

      <!-- ── MANUAL TAB ── -->
      <div id="imgPanel-manual" style="padding:20px;display:none;">

        <!-- Upload Section -->
        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:20px;">
          <div style="font-size:12px;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;">Upload Manual / Documents</div>

          <div style="display:flex;align-items:flex-end;gap:10px;margin-bottom:14px;">
            <div class="fg" style="max-width:180px;">
              <label style="font-size:12px;">How many files to upload?</label>
              <input type="number" id="prodManualCount" class="form-control" value="1" min="1" max="20" style="height:34px;">
            </div>
            <button type="button" class="btn btn--outline" style="height:34px;padding:0 14px;font-size:13px;" onclick="buildImgBoxes('manual')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add Boxes
            </button>
          </div>

          <form method="POST" action="service?urlstring=<?= EncryptURL('action=AddProductImages') ?>" enctype="multipart/form-data" id="imgUploadFormManual">
            <input type="hidden" name="product_id" id="imgUploadPidManual">
            <input type="hidden" name="image_for" value="Product Mannual">

            <div id="imgBoxesManual"></div>

            <div style="display:flex;justify-content:flex-end;margin-top:12px;">
              <button type="submit" class="btn btn--primary" id="imgUploadBtnManual" disabled>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>
                Upload Manuals
              </button>
            </div>
          </form>
        </div>

        <!-- Existing manuals -->
        <div>
          <div style="font-size:12px;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Uploaded Manuals</div>
          <div id="existingManualsArea"></div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Pre-rendered image panels (hidden, split by type) -->
<?php foreach ($products as $p):
  $pid      = (int)(float)($p->PRODUCT_ID ?? 0);
  $imgs     = $allImages[$pid] ?? [];
  $prodImgs = array_filter($imgs, function($i) { return ($i->IMAGE_FOR ?? '') !== 'Product Mannual'; });
  $manuals  = array_filter($imgs, function($i) { return ($i->IMAGE_FOR ?? '') === 'Product Mannual'; });
?>
<!-- Product images panel -->
<div id="prod-imgs-product-<?= $pid ?>" style="display:none;">
  <?php if (empty($prodImgs)): ?>
  <div class="img-empty-state">No product images uploaded yet.</div>
  <?php else: ?>
  <div class="prod-img-list">
    <?php foreach ($prodImgs as $img):
      $imgId   = (int)(float)($img->IMAGE_ID ?? 0);
      $imgExt  = (string)($img->IMAGE_EXT ?? '');
      $imgName = (string)($img->IMAGE_NAME ?? '');
      $imgPrio = (int)($img->PRIORTY ?? 0);
      $imgDisp = (string)($img->DISPLAY_FLAG ?? 'Yes');
      $imgUrl  = ($imgExt !== '' && strpos($imgExt, '/') !== false) ? $pubBase.'/'.$imgExt : '';
    ?>
    <div class="prod-img-list-row">
      <div class="prod-img-list-thumb">
        <?php if ($imgUrl): ?>
        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">
        <?php else: ?>
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f1f5f9;border-radius:6px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/></svg>
        </div>
        <?php endif; ?>
      </div>
      <div class="prod-img-list-info" style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:600;color:var(--text);"><?= $imgName ? htmlspecialchars($imgName) : '<span style="color:var(--text-muted);font-weight:400;">Untitled</span>' ?></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">
          Priority: <?= $imgPrio ?> &nbsp;·&nbsp;
          Display: <span style="color:<?= $imgDisp==='Yes' ? '#15803d' : '#dc2626' ?>;font-weight:600;"><?= $imgDisp ?></span>
        </div>
      </div>
      <div style="display:flex;gap:6px;flex-shrink:0;align-items:center;">
        <?php if ($imgUrl): ?>
        <a href="<?= htmlspecialchars($imgUrl) ?>" target="_blank" class="prod-img-view-btn" title="View full image">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          View
        </a>
        <?php endif; ?>
        <?php if ($canDelete): ?>
        <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteProductImage') ?>" style="margin:0;" onsubmit="return confirm('Delete this image?');">
          <input type="hidden" name="image_id" value="<?= $imgId ?>">
          <button type="submit" class="prod-img-del-btn" title="Remove">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
            Remove
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Manuals panel -->
<div id="prod-imgs-manual-<?= $pid ?>" style="display:none;">
  <?php if (empty($manuals)): ?>
  <div class="img-empty-state">No manuals uploaded yet.</div>
  <?php else: ?>
  <div class="prod-img-list">
    <?php foreach ($manuals as $img):
      $imgId     = (int)(float)($img->IMAGE_ID ?? 0);
      $imgExt    = (string)($img->IMAGE_EXT ?? '');
      $imgName   = (string)($img->IMAGE_NAME ?? '');
      $imgTitle  = (string)($img->PRODUCT_MANUAL_TITLE ?? '');
      $imgPrio   = (int)($img->PRIORTY ?? 0);
      $imgDisp   = (string)($img->DISPLAY_FLAG ?? 'Yes');
      $hyperLink = (string)($img->HYPER_LINK ?? '');
      $fileUrl   = ($imgExt !== '' && strpos($imgExt, '/') !== false) ? $pubBase.'/'.$imgExt : '';
    ?>
    <div class="prod-img-list-row" style="align-items:flex-start;">
      <div class="prod-img-list-thumb" style="background:#eef2ff;border-radius:8px;flex-shrink:0;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <div class="prod-img-list-info" style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:600;color:var(--text);"><?= $imgName ? htmlspecialchars($imgName) : '<span style="color:var(--text-muted);font-weight:400;">Untitled</span>' ?></div>
        <?php if ($imgTitle): ?>
        <div style="font-size:12px;color:var(--text-muted);margin-top:1px;"><?= htmlspecialchars($imgTitle) ?></div>
        <?php endif; ?>
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
          Priority: <?= $imgPrio ?> &nbsp;·&nbsp;
          Display: <span style="color:<?= $imgDisp==='Yes' ? '#15803d' : '#dc2626' ?>;font-weight:600;"><?= $imgDisp ?></span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;">
          <?php if ($fileUrl): ?>
          <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" class="manual-open-btn">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Open Uploaded File ↗
          </a>
          <?php endif; ?>
          <?php if ($hyperLink): ?>
          <a href="<?= htmlspecialchars($hyperLink) ?>" target="_blank" class="manual-open-btn" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
            Open Direct Link ↗
          </a>
          <?php endif; ?>
          <?php if (!$fileUrl && !$hyperLink): ?>
          <span style="font-size:11px;color:var(--text-muted);font-style:italic;">No link available</span>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($canDelete): ?>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteProductImage') ?>" style="margin:0;flex-shrink:0;" onsubmit="return confirm('Delete this manual?');">
        <input type="hidden" name="image_id" value="<?= $imgId ?>">
        <button type="submit" class="prod-img-del-btn" title="Remove">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
          Remove
        </button>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>


<!-- ════════════════════════════════════════════════════
     VIEW DETAILS MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="viewProdModal">
  <div class="modal prod-view-modal">
    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div class="modal-title" id="viewProdTitle">Product Details</div>
      <div style="display:flex;gap:8px;align-items:center;">
        <?php if ($canEdit): ?>
        <button class="btn btn--outline" id="viewProdEditBtn" style="height:30px;font-size:12px;padding:0 12px;" onclick="openProdModalFromView()">Edit</button>
        <?php endif; ?>
        <button class="modal-close" onclick="closeModal('viewProdModal')" style="font-size:22px;line-height:1;">×</button>
      </div>
    </div>
    <div style="overflow-y:auto;flex:1;">
      <div class="prod-view-grid">

        <!-- Left: image gallery -->
        <div class="prod-view-left">
          <div class="prod-view-main-img-wrap">
            <img id="viewMainImg" src="" alt="" class="prod-view-main-img" style="display:none;">
            <div id="viewMainImgPlaceholder" class="prod-view-main-placeholder">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="opacity:.3"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              <span style="font-size:12px;color:var(--text-muted);margin-top:8px;">No image</span>
            </div>
          </div>
          <div id="viewThumbStrip" class="prod-view-thumb-strip"></div>
        </div>

        <!-- Right: details -->
        <div class="prod-view-right" style="padding:22px;overflow-y:auto;">
          <div id="viewProdName" style="font-size:18px;font-weight:700;color:var(--text);line-height:1.3;margin-bottom:4px;"></div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:16px;">
            <span id="viewProdCode" style="font-family:monospace;font-size:12px;background:#f1f5f9;padding:2px 8px;border-radius:4px;color:var(--text-muted);display:none;"></span>
            <span id="viewProdStatusBadge" style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:20px;"></span>
            <span id="viewProdLabelBadge" style="background:#eef2ff;color:#4f46e5;font-size:11px;font-weight:600;padding:2px 10px;border-radius:20px;display:none;"></span>
          </div>

          <div class="prod-view-info-grid">
            <div class="prod-view-info-item"><span class="prod-view-info-label">Category</span><span id="viewProdCat" class="prod-view-info-val"></span></div>
            <div class="prod-view-info-item"><span class="prod-view-info-label">Price</span><span id="viewProdAmt" class="prod-view-info-val"></span></div>
            <div class="prod-view-info-item"><span class="prod-view-info-label">Tax</span><span id="viewProdTax" class="prod-view-info-val"></span></div>
            <div class="prod-view-info-item"><span class="prod-view-info-label">Discount</span><span id="viewProdDisc" class="prod-view-info-val"></span></div>
            <div class="prod-view-info-item"><span class="prod-view-info-label">Offer %</span><span id="viewProdOffer" class="prod-view-info-val"></span></div>
            <div class="prod-view-info-item"><span class="prod-view-info-label">Rating</span><span id="viewProdRating" class="prod-view-info-val"></span></div>
            <div class="prod-view-info-item"><span class="prod-view-info-label">In Stock</span><span id="viewProdStock" class="prod-view-info-val"></span></div>
            <div class="prod-view-info-item"><span class="prod-view-info-label">Total Sold</span><span id="viewProdSold" class="prod-view-info-val"></span></div>
            <div class="prod-view-info-item"><span class="prod-view-info-label">Threshold</span><span id="viewProdThreshold" class="prod-view-info-val"></span></div>
            <div class="prod-view-info-item"><span class="prod-view-info-label">Display</span><span id="viewProdDisplay" class="prod-view-info-val"></span></div>
          </div>

          <!-- Content tabs -->
          <div style="margin-top:18px;border-top:1px solid var(--border);padding-top:16px;">
            <div class="prod-view-tabs">
              <button class="prod-view-tab active" onclick="switchViewTab('vdesc',this)">Description</button>
              <button class="prod-view-tab" onclick="switchViewTab('vspec',this)">Specification</button>
              <button class="prod-view-tab" onclick="switchViewTab('vdets',this)">Details</button>
              <button class="prod-view-tab" onclick="switchViewTab('vsample',this)">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:3px;vertical-align:-1px;"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                Sample Code
              </button>
            </div>
            <div id="vdesc" class="prod-view-tab-panel cat-desc-view" style="margin-top:12px;"></div>
            <div id="vspec" class="prod-view-tab-panel cat-desc-view" style="display:none;margin-top:12px;"></div>
            <div id="vdets" class="prod-view-tab-panel cat-desc-view" style="display:none;margin-top:12px;"></div>
            <div id="vsample" class="prod-view-tab-panel" style="display:none;margin-top:12px;">
              <div id="vsampleContent"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- ── Delete Confirm Modal ── -->
<div class="modal-overlay" id="deleteProdModal">
  <div class="modal modal--sm">
    <div class="modal-hd" style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <span class="modal-title">Delete Product</span>
      <button class="modal-close" onclick="closeModal('deleteProdModal')">×</button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:20px;">
        <div style="width:40px;height:40px;border-radius:50%;background:#fff5f5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div>
          <div style="font-weight:600;margin-bottom:5px;font-size:14px;">Are you sure?</div>
          <div style="font-size:13px;color:var(--text-muted);">Delete <strong id="delProdName"></strong>? Products with enquiries or purchases cannot be deleted.</div>
        </div>
      </div>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteProduct') ?>">
        <input type="hidden" name="product_id" id="delProdId">
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button type="button" class="btn btn--outline" onclick="closeModal('deleteProdModal')">Cancel</button>
          <button type="submit" class="btn btn--danger-solid">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ── Scoped CSS ── -->
<style>
/* Product thumb */
.prod-thumb { width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid var(--border); }
.prod-initial { width:44px;height:44px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;font-weight:700; }

/* Wide modal */
.prod-modal-wide { max-width:780px;max-height:94vh;display:flex;flex-direction:column;width:100%; }

/* Modal tabs */
.prod-tabs { display:flex;gap:0;border-bottom:1px solid var(--border);flex-shrink:0;padding:0 22px;background:#fafbfc; }
.prod-tab { padding:10px 16px;font-size:13px;font-weight:500;color:var(--text-muted);background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;transition:all .15s;white-space:nowrap; }
.prod-tab:hover { color:var(--text); }
.prod-tab.active { color:var(--primary);border-bottom-color:var(--primary);font-weight:600; }

/* cols-3 grid */
.form-row.cols-3 { display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px; }

/* Pagination (reuse emp-pgbar) */
.emp-pgbar { display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:8px; }
.emp-pgbar-info { font-size:13px;color:var(--text-muted);white-space:nowrap; }
.emp-pgbar-right { display:flex;align-items:center;gap:8px;flex-wrap:wrap; }
.emp-pgbar-rpp-label { font-size:13px;font-weight:600;color:var(--text);white-space:nowrap; }
.emp-pgbar-rpp-sel { height:32px;padding:0 8px;border:1px solid var(--border);border-radius:6px;font-size:13px;color:var(--text);background:#fff;cursor:pointer; }
.emp-pgbar-rpp-sel:focus { outline:none;border-color:var(--primary); }
.emp-pgbar-apply { height:32px;padding:0 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;font-weight:600;color:var(--text);background:#fff;cursor:pointer;transition:all .15s; }
.emp-pgbar-apply:hover { border-color:var(--primary);background:#f0f4ff;color:var(--primary); }
.emp-pgbar-nav { display:flex;align-items:center;gap:4px; }
.pg-btn { height:32px;min-width:32px;padding:0 10px;border:1px solid var(--border);border-radius:6px;font-size:13px;color:var(--text);background:#fff;cursor:pointer;transition:all .15s; }
.pg-btn:hover:not(:disabled) { border-color:var(--primary);color:var(--primary);background:#f0f4ff; }
.pg-btn.pg-active { background:var(--primary);border-color:var(--primary);color:#fff;font-weight:700;pointer-events:none; }
.pg-btn:disabled { opacity:.38;cursor:not-allowed; }
.pg-dots { font-size:13px;color:var(--text-muted);padding:0 4px;line-height:32px; }

/* Image modal tabs */
.img-tab { padding:10px 16px;font-size:13px;font-weight:500;color:var(--text-muted);background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:6px;white-space:nowrap; }
.img-tab:hover { color:var(--text); }
.img-tab.active { color:var(--primary);border-bottom-color:var(--primary);font-weight:600; }

/* Upload boxes */
.img-upload-box { border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:10px;background:#fff;position:relative; }
.img-upload-box-header { font-size:12px;font-weight:700;color:var(--text-muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.4px; }
.img-upload-file-zone { border:1.5px dashed var(--border);border-radius:6px;cursor:pointer;min-height:64px;display:flex;align-items:center;justify-content:center;padding:12px;gap:8px;transition:border-color .15s;background:#fafbfc; }
.img-upload-file-zone:hover { border-color:var(--primary);background:#f8f9ff; }
.img-upload-file-zone.has-file { border-color:#22c55e;background:#f0fdf4; }

/* Upload box form grid */
.img-box-grid-2 { display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px; }
.img-box-grid-3 { display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-top:10px; }

/* Image list (existing) */
.prod-img-list { display:flex;flex-direction:column;gap:8px; }
.prod-img-list-row { display:flex;align-items:center;gap:12px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:#fff; }
.prod-img-list-thumb { width:48px;height:48px;border-radius:6px;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#f1f5f9; }
.prod-img-list-info { flex:1;min-width:0; }
.prod-img-del-btn { display:flex;align-items:center;gap:5px;padding:5px 10px;border-radius:6px;border:1px solid #fecaca;background:#fff5f5;cursor:pointer;color:#dc2626;font-size:12px;font-weight:600;transition:all .15s;white-space:nowrap; }
.prod-img-del-btn:hover { background:#fee2e2; }
.prod-img-view-btn { display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--text);font-size:12px;font-weight:600;transition:all .15s;white-space:nowrap;text-decoration:none; }
.prod-img-view-btn:hover { border-color:var(--primary);color:var(--primary);background:#f0f4ff; }
.manual-open-btn { display:inline-flex;align-items:center;gap:5px;padding:4px 9px;border-radius:6px;border:1px solid #c7d2fe;background:#eef2ff;color:#4f46e5;font-size:11px;font-weight:600;text-decoration:none;transition:all .15s;white-space:nowrap; }
.manual-open-btn:hover { background:#e0e7ff; }
.img-empty-state { padding:20px;text-align:center;font-size:13px;color:var(--text-muted); }

/* Save & Next btn */
.btn--secondary { background:#f0f4ff;color:#4f46e5;border:1px solid #c7d2fe;font-weight:600; }
.btn--secondary:hover { background:#e0e7ff; }

/* Sample Code tab */
.sc-header-row { display:grid;grid-template-columns:1fr 1fr .8fr .8fr 1.6fr .9fr 36px;gap:8px;padding:0 4px 6px;border-bottom:1px solid var(--border);margin-bottom:6px; }
.sc-header-row > div { font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px; }
.sc-row { display:grid;grid-template-columns:1fr 1fr .8fr .8fr 1.6fr .9fr 36px;gap:8px;align-items:center;padding:6px 4px;border-bottom:1px solid #f1f5f9; }
.sc-row:last-child { border-bottom:none; }
.sc-row input { height:36px; }
.sc-row .sc-date { height:36px;background:#f8fafc;border:1px solid var(--border);border-radius:6px;padding:0 10px;font-size:12px;color:var(--text-muted);display:flex;align-items:center;white-space:nowrap;overflow:hidden; }
.sc-del-btn { width:32px;height:32px;border-radius:6px;border:1px solid #fecaca;background:#fff5f5;cursor:pointer;color:#dc2626;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s; }
.sc-del-btn:hover { background:#fee2e2; }
/* Sample code view table */
.sc-vth { padding:8px 10px;font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;border-bottom:2px solid var(--border);text-align:left;white-space:nowrap; }
.sc-vtd { padding:8px 10px;font-size:12px;color:var(--text);vertical-align:top; }

/* View details modal */
.prod-view-modal { max-width:900px;max-height:94vh;display:flex;flex-direction:column;width:100%; }
.prod-view-grid { display:grid;grid-template-columns:260px 1fr;height:100%;min-height:500px; }
.prod-view-left { border-right:1px solid var(--border);padding:20px;display:flex;flex-direction:column;gap:12px;background:#fafbfc; }
.prod-view-main-img-wrap { border:1px solid var(--border);border-radius:10px;overflow:hidden;aspect-ratio:1;background:#fff; }
.prod-view-main-img { width:100%;height:100%;object-fit:contain;display:block; }
.prod-view-main-placeholder { width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center; }
.prod-view-thumb-strip { display:flex;gap:6px;flex-wrap:wrap; }
.prod-view-thumb { width:52px;height:52px;object-fit:cover;border-radius:6px;border:2px solid transparent;cursor:pointer;transition:border-color .15s; }
.prod-view-thumb:hover,.prod-view-thumb.active { border-color:var(--primary); }
.prod-view-info-grid { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
.prod-view-info-item { background:#f8fafc;border-radius:6px;padding:8px 10px;border:1px solid var(--border); }
.prod-view-info-label { font-size:10px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:2px; }
.prod-view-info-val { font-size:13px;font-weight:600;color:var(--text); }

/* View content tabs */
.prod-view-tabs { display:flex;gap:0;border-bottom:1px solid var(--border); }
.prod-view-tab { padding:7px 14px;font-size:12px;font-weight:500;color:var(--text-muted);background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;transition:all .15s; }
.prod-view-tab:hover { color:var(--text); }
.prod-view-tab.active { color:var(--primary);border-bottom-color:var(--primary);font-weight:600; }
</style>


<!-- Quill JS + SheetJS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
/* ── Baked data ─────────────────────────────────── */
const PROD_DATA = <?= json_encode(array_values(array_map(function($p) {
    return [
        'id'        => (int)(float)($p->PRODUCT_ID ?? 0),
        'name'      => (string)($p->PRODUCT_NAME ?? ''),
        'code'      => (string)($p->PRODUCT_CODE ?? ''),
        'cat_id'    => (int)(float)($p->PRODUCT_CATEGORY_ID ?? 0),
        'cat_name'  => (string)($p->PRODUCT_CATEGORY_NAME ?? ''),
        'status'    => (string)($p->PRODUCT_STATUS ?? 'Active'),
        'display'   => (string)($p->DISPLAY_FLAG ?? 'Yes'),
        'amt'       => (float)($p->PRODUCT_AMT ?? 0),
        'tax'       => (float)($p->PRODUCT_TAX ?? 0),
        'disc'      => (float)($p->PRODUCT_DISCOUNT ?? 0),
        'offer_pct' => ($p->OFFER_PERCENTAGE !== null && $p->OFFER_PERCENTAGE !== '') ? (float)$p->OFFER_PERCENTAGE : '',
        'rating'    => ($p->RATING !== null && $p->RATING !== '') ? (float)$p->RATING : '',
        'label'     => (string)($p->LABEL ?? ''),
        'prio'      => (int)($p->PRIORTY ?? 0),
        'threshold' => (float)($p->PRODUCT_THRESHOLD ?? 1),
        'desc'      => (string)($p->PRODUCT_DESCRIPTION ?? ''),
        'spec'      => (string)($p->PRODUCT_SPECIFICATION ?? ''),
        'dets'      => (string)($p->PRODUCT_DETAILS ?? ''),
        'remaining' => (int)(float)($p->TOTAL_REMAINING ?? 0),
        'sold'      => (int)(float)($p->TOTAL_SOLD ?? 0),
        'total'     => (int)(float)($p->TOTAL_PRODUCT ?? 0),
    ];
}, $products))) ?>;

const PROD_IMAGES = <?= json_encode(array_map(function($imgs) use ($pubBase) {
    return array_map(function($img) use ($pubBase) {
        $ext = (string)($img->IMAGE_EXT ?? '');
        return [
            'id'        => (int)(float)($img->IMAGE_ID ?? 0),
            'url'       => ($ext !== '' && strpos($ext, '/') !== false) ? $pubBase.'/'.$ext : '',
            'for'       => (string)($img->IMAGE_FOR ?? 'Product'),
            'name'      => (string)($img->IMAGE_NAME ?? ''),
            'title'     => (string)($img->PRODUCT_MANUAL_TITLE ?? ''),
            'hyper'     => (string)($img->HYPER_LINK ?? ''),
            'prio'      => (int)($img->PRIORTY ?? 0),
            'display'   => (string)($img->DISPLAY_FLAG ?? 'Yes'),
        ];
    }, $imgs);
}, $allImages)) ?>;

const PUB_BASE = <?= json_encode($pubBase) ?>;

const PROD_SAMPLE = <?= json_encode((object)array_map(function($rows) {
    return array_values(array_map(function($r) {
        return [
            'id'   => (int)(float)($r->PRODUCT_SAMPLE_CODE_ID ?? 0),
            'lang' => (string)($r->LANGUAGE_TECHNOLOGY ?? ''),
            'ide'  => (string)($r->IDE_COMPILER ?? ''),
            'type' => (string)($r->TYPE ?? ''),
            'os'   => (string)($r->OS ?? ''),
            'url'  => (string)($r->EXT ?? ''),
            'date' => (string)($r->DATE ?? ''),
        ];
    }, $rows));
}, $allSampleCodes)) ?>;

var _viewProdId = 0;


/* ═══════════════════════════════════════════════════
   QUILL EDITORS (3 instances)
   ═══════════════════════════════════════════════════ */
var qDesc, qSpec, qDets;
document.addEventListener('DOMContentLoaded', function () {
  var opts = {
    theme: 'snow',
    modules: { toolbar: [[{header:[1,2,3,false]}],['bold','italic','underline','strike'],[{list:'ordered'},{list:'bullet'}],['link'],['clean']] }
  };
  qDesc = new Quill('#prodDescEditor', Object.assign({}, opts, {placeholder:'Write product description…'}));
  qSpec = new Quill('#prodSpecEditor', Object.assign({}, opts, {placeholder:'Write product specification…'}));
  qDets = new Quill('#prodDetsEditor', Object.assign({}, opts, {placeholder:'Write additional details…'}));
});

document.getElementById('prodForm').addEventListener('submit', function () {
  var clean = function(q) { var h = q ? q.root.innerHTML : ''; return h === '<p><br></p>' ? '' : h; };
  document.getElementById('fProdDescHidden').value = clean(qDesc);
  document.getElementById('fProdSpecHidden').value = clean(qSpec);
  document.getElementById('fProdDetsHidden').value = clean(qDets);
});


/* ═══════════════════════════════════════════════════
   TAB SWITCHER (modal + view)
   ═══════════════════════════════════════════════════ */
function switchProdTab(tab, btn) {
  document.querySelectorAll('#prodTabs .prod-tab').forEach(function(b) { b.classList.remove('active'); });
  document.querySelectorAll('.prod-tab-panel').forEach(function(p) { p.style.display = 'none'; });
  btn.classList.add('active');
  document.getElementById('pt-' + tab).style.display = 'block';
  /* Notify Quill to re-render when its panel becomes visible */
  if (tab === 'desc' && qDesc) qDesc.update();
  if (tab === 'spec' && qSpec) qSpec.update();
  if (tab === 'dets' && qDets) qDets.update();
}

function switchViewTab(tab, btn) {
  document.querySelectorAll('.prod-view-tab').forEach(function(b) { b.classList.remove('active'); });
  document.querySelectorAll('.prod-view-tab-panel').forEach(function(p) { p.style.display = 'none'; });
  btn.classList.add('active');
  document.getElementById(tab).style.display = 'block';
}


/* ═══════════════════════════════════════════════════
   PAGINATION ENGINE
   ═══════════════════════════════════════════════════ */
(function () {
  var allRows  = [];
  var filtered = [];
  var curPage  = 1;
  var rpp      = 20;

  function init() {
    allRows  = Array.from(document.querySelectorAll('#prodTbody .prod-row'));
    filtered = allRows.slice();
    render();
  }

  window.prodOnSearch = function () {
    var q      = document.getElementById('prodSearch').value.toLowerCase().trim();
    var catFil = document.getElementById('prodCatFilter').value;
    var stFil  = document.getElementById('prodStatusFilter').value;
    filtered = allRows.filter(function (r) {
      return (!q || r.dataset.search.includes(q))
          && (!catFil || r.dataset.cat === catFil)
          && (!stFil  || r.dataset.status === stFil);
    });
    /* Expose for export */
    window._prodFiltered = filtered;
    curPage = 1;
    render();
  };

  window.prodApplyRpp = function () {
    rpp     = parseInt(document.getElementById('prodRpp').value, 10) || 20;
    curPage = 1;
    render();
  };

  window.prodGoPage = function (p) { curPage = p; render(); };

  function render() {
    var total = filtered.length;
    var pages = Math.max(1, Math.ceil(total / rpp));
    curPage   = Math.min(curPage, pages);
    var start = (curPage - 1) * rpp;
    var end   = Math.min(start + rpp, total);

    allRows.forEach(function (r) { r.style.display = 'none'; });
    filtered.slice(start, end).forEach(function (r, idx) {
      r.style.display = '';
      var sno = r.querySelector('.prod-sno');
      if (sno) sno.textContent = start + idx + 1;
    });

    var noRes = document.getElementById('prodNoResults');
    if (noRes) noRes.style.display = total === 0 ? 'block' : 'none';

    var info = document.getElementById('prodPgInfo');
    if (info) info.textContent = total === 0 ? 'No products found' : 'Showing ' + (start + 1) + '–' + end + ' of ' + total + ' products';

    buildNav(curPage, pages);
  }

  function buildNav(cur, pages) {
    var nav = document.getElementById('prodNav');
    if (!nav) return;
    var h = '';
    h += '<button class="pg-btn" onclick="prodGoPage(' + (cur-1) + ')"' + (cur<=1 ? ' disabled' : '') + '>Prev</button>';
    pageNums(cur, pages).forEach(function(p) {
      h += p === '...' ? '<span class="pg-dots">…</span>'
         : '<button class="pg-btn' + (p===cur?' pg-active':'') + '" onclick="prodGoPage('+p+')">' + p + '</button>';
    });
    h += '<button class="pg-btn" onclick="prodGoPage(' + (cur+1) + ')"' + (cur>=pages ? ' disabled' : '') + '>Next</button>';
    nav.innerHTML = h;
  }

  function pageNums(cur, pages) {
    if (pages <= 7) { var a=[]; for(var i=1;i<=pages;i++) a.push(i); return a; }
    if (cur <= 4) return [1,2,3,4,5,'...',pages];
    if (cur >= pages-3) return [1,'...',pages-4,pages-3,pages-2,pages-1,pages];
    return [1,'...',cur-1,cur,cur+1,'...',pages];
  }

  /* Expose filtered list for export */
  window._prodFiltered = [];
  window._prodAllRows  = [];

  var _origInit = init;
  init = function() {
    _origInit();
    window._prodAllRows  = allRows.slice();
    window._prodFiltered = filtered.slice();
  };

  document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', init) : init();
})();


/* ═══════════════════════════════════════════════════
   PRICE PREVIEW
   ═══════════════════════════════════════════════════ */
function updatePricePreview() {
  var b   = parseFloat(document.getElementById('fProdAmt').value)  || 0;
  var t   = parseFloat(document.getElementById('fProdTax').value)   || 0;
  var d   = parseFloat(document.getElementById('fProdDisc').value)  || 0;
  var wt  = b * (1 + t / 100);
  var fin = wt * (1 - d / 100);
  document.getElementById('ppBase').textContent = '₹' + b.toFixed(2);
  document.getElementById('ppTax').textContent  = '₹' + wt.toFixed(2);
  document.getElementById('ppDisc').textContent = '₹' + fin.toFixed(2);
}
['fProdAmt','fProdTax','fProdDisc'].forEach(function(id) {
  var el = document.getElementById(id);
  if (el) el.addEventListener('input', updatePricePreview);
});


/* ═══════════════════════════════════════════════════
   ADD / EDIT MODAL
   ═══════════════════════════════════════════════════ */
var _editProdId = 0;
var prodSaveSvg = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>';

function openProdModal(prodId) {
  _editProdId = prodId || 0;

  /* Reset all fields */
  ['fProdName','fProdCode','fProdRating'].forEach(function(id) {
    var el = document.getElementById(id); if(el) el.value = '';
  });
  document.getElementById('fProdLabel').value = '';
  ['fProdAmt','fProdTax','fProdDisc','fProdOffer'].forEach(function(id) {
    var el = document.getElementById(id); if(el) el.value = '';
  });
  updatePricePreview();
  document.getElementById('fProdId').value        = _editProdId;
  document.getElementById('fProdPrio').value       = '0';
  document.getElementById('fProdThreshold').value  = '1';
  document.getElementById('fProdStatus').value     = 'Active';
  document.getElementById('fProdDisplay').value    = 'Yes';
  document.getElementById('fProdCat').value        = '0';
  if (qDesc) qDesc.setContents([]);
  if (qSpec) qSpec.setContents([]);
  if (qDets) qDets.setContents([]);

  /* Reset to first tab & update nav buttons */
  _prodCurTab = 'basic';
  switchProdTab('basic', document.querySelector('[data-tab="basic"]'));
  _updateProdNavBtns('basic');

  /* Populate sample code rows */
  populateSampleCodes(_editProdId > 0 ? _editProdId : 0);

  if (_editProdId > 0) {
    var d = PROD_DATA.find(function(p) { return p.id === _editProdId; });
    if (d) {
      document.getElementById('fProdName').value       = d.name;
      document.getElementById('fProdCode').value       = d.code;
      document.getElementById('fProdLabel').value      = d.label;
      document.getElementById('fProdRating').value     = d.rating !== '' ? d.rating : '';
      document.getElementById('fProdPrio').value       = d.prio;
      document.getElementById('fProdThreshold').value  = d.threshold;
      document.getElementById('fProdStatus').value     = d.status;
      document.getElementById('fProdDisplay').value    = d.display;
      document.getElementById('fProdAmt').value        = d.amt || '';
      document.getElementById('fProdTax').value        = d.tax || '';
      document.getElementById('fProdDisc').value       = d.disc || '';
      updatePricePreview();
      document.getElementById('fProdOffer').value      = d.offer_pct !== '' ? d.offer_pct : '';
      var cSel = document.getElementById('fProdCat');
      for (var i = 0; i < cSel.options.length; i++) {
        if (parseInt(cSel.options[i].value) === d.cat_id) { cSel.selectedIndex = i; break; }
      }
      if (qDesc && d.desc) qDesc.clipboard.dangerouslyPasteHTML(d.desc);
      if (qSpec && d.spec) qSpec.clipboard.dangerouslyPasteHTML(d.spec);
      if (qDets && d.dets) qDets.clipboard.dangerouslyPasteHTML(d.dets);
    }
    document.getElementById('prodModalTitle').textContent = 'Edit Product';
    document.getElementById('prodModalSub').textContent   = 'Update the product details.';
    document.getElementById('prodSubmitBtn').innerHTML    = prodSaveSvg + ' Update Product';
  } else {
    document.getElementById('prodModalTitle').textContent = 'Add Product';
    document.getElementById('prodModalSub').textContent   = 'Fill in the product details below.';
    document.getElementById('prodSubmitBtn').innerHTML    = prodSaveSvg + ' Save Product';
  }

  openModal('prodModal');
}

function openProdModalFromView() {
  closeModal('viewProdModal');
  setTimeout(function() { openProdModal(_viewProdId); }, 150);
}


/* ═══════════════════════════════════════════════════
   SAMPLE CODE TAB
   ═══════════════════════════════════════════════════ */
var _scRowCount = 0;

function _scToday() {
  var d = new Date();
  return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

function addSampleRow(data) {
  data = data || {};
  var idx  = _scRowCount++;
  var row  = document.createElement('div');
  row.className = 'sc-row';
  row.dataset.idx = idx;

  var fields = [
    { name:'sample_lang', placeholder:'e.g. C++',          val: data.lang || '' },
    { name:'sample_ide',  placeholder:'e.g. Arduino IDE',  val: data.ide  || '' },
    { name:'sample_type', placeholder:'e.g. Library',      val: data.type || '' },
    { name:'sample_os',   placeholder:'e.g. Windows',      val: data.os   || '' },
    { name:'sample_url',  placeholder:'https://github.com/…', val: data.url || '' },
  ];

  var html = '';
  fields.forEach(function(f) {
    html += '<div><input type="' + (f.name === 'sample_url' ? 'url' : 'text') + '"' +
            ' name="' + f.name + '[]"' +
            ' class="form-control"' +
            ' placeholder="' + f.placeholder + '"' +
            ' value="' + (f.val.replace(/"/g,'&quot;')) + '"' +
            ' style="height:36px;font-size:13px;"></div>';
  });

  var dateStr = data.date ? data.date.slice(0,10) : _scToday();
  html += '<div><div class="sc-date">' + dateStr + '</div></div>';
  html += '<button type="button" class="sc-del-btn" onclick="removeSampleRow(this)" title="Remove">' +
            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
          '</button>';

  row.innerHTML = html;
  document.getElementById('sampleCodeRows').appendChild(row);
}

function removeSampleRow(btn) {
  var row = btn.closest('.sc-row');
  if (row) row.remove();
  /* Keep at least one row */
  var rows = document.getElementById('sampleCodeRows');
  if (rows && rows.children.length === 0) addSampleRow();
}

function populateSampleCodes(prodId) {
  var container = document.getElementById('sampleCodeRows');
  if (!container) return;
  container.innerHTML = '';
  _scRowCount = 0;
  /* JSON_FORCE_OBJECT gives string keys — use String() for safe lookup */
  var codes = PROD_SAMPLE[String(prodId)] || [];
  if (codes.length > 0) {
    codes.forEach(function(c) { addSampleRow(c); });
  } else {
    addSampleRow(); /* at least one blank row */
  }
}


/* ═══════════════════════════════════════════════════
   PROD MODAL SAVE & NEXT NAVIGATION
   ═══════════════════════════════════════════════════ */
var _prodTabOrder = ['basic','pricing','desc','spec','dets','sample'];
var _prodCurTab   = 'basic';

function _updateProdNavBtns(tab) {
  var idx     = _prodTabOrder.indexOf(tab);
  var isFirst = idx === 0;
  var isLast  = idx === _prodTabOrder.length - 1;
  document.getElementById('prodPrevBtn').style.display = isFirst ? 'none' : '';
  document.getElementById('prodNextBtn').style.display = isLast  ? 'none' : '';
  /* Save is always visible */
}

/* Override switchProdTab to also update nav buttons */
var _origSwitchProdTab = switchProdTab;
switchProdTab = function(tab, btn) {
  _origSwitchProdTab(tab, btn);
  _prodCurTab = tab;
  _updateProdNavBtns(tab);
};

function prodNavTab(dir) {
  var idx = _prodTabOrder.indexOf(_prodCurTab);
  var next = _prodTabOrder[idx + dir];
  if (!next) return;
  var btn = document.querySelector('[data-tab="' + next + '"]');
  if (btn) switchProdTab(next, btn);
}


/* ═══════════════════════════════════════════════════
   MANAGE IMAGES MODAL
   ═══════════════════════════════════════════════════ */
var _imgModalProdId = 0;

function switchImgTab(type) {
  document.querySelectorAll('.img-tab').forEach(function(t) { t.classList.remove('active'); });
  document.getElementById('imgTab-' + type).classList.add('active');
  document.getElementById('imgPanel-product').style.display = type === 'product' ? 'block' : 'none';
  document.getElementById('imgPanel-manual').style.display  = type === 'manual'  ? 'block' : 'none';
}

function openImagesModal(prodId, prodName) {
  _imgModalProdId = prodId;
  document.getElementById('imagesModalTitle').textContent = 'Manage Images';
  document.getElementById('imagesModalSub').textContent   = prodName;

  /* Set product_id in both forms */
  document.getElementById('imgUploadPidProduct').value = prodId;
  document.getElementById('imgUploadPidManual').value  = prodId;

  /* Inject pre-rendered panels */
  var prodArea   = document.getElementById('existingProductImagesArea');
  var manualArea = document.getElementById('existingManualsArea');
  var prodPanel  = document.getElementById('prod-imgs-product-' + prodId);
  var manPanel   = document.getElementById('prod-imgs-manual-' + prodId);
  prodArea.innerHTML   = prodPanel  ? prodPanel.innerHTML  : '<div class="img-empty-state">No product images uploaded yet.</div>';
  manualArea.innerHTML = manPanel   ? manPanel.innerHTML   : '<div class="img-empty-state">No manuals uploaded yet.</div>';

  /* Reset upload boxes */
  document.getElementById('imgBoxesProduct').innerHTML = '';
  document.getElementById('imgBoxesManual').innerHTML  = '';
  document.getElementById('imgUploadBtnProduct').disabled = true;
  document.getElementById('imgUploadBtnManual').disabled  = true;
  document.getElementById('prodImgCount').value    = '1';
  document.getElementById('prodManualCount').value = '1';

  /* Always open on product tab */
  switchImgTab('product');
  openModal('imagesModal');
}

function buildImgBoxes(type) {
  var isManual  = type === 'manual';
  var countEl   = document.getElementById(isManual ? 'prodManualCount' : 'prodImgCount');
  var container = document.getElementById(isManual ? 'imgBoxesManual' : 'imgBoxesProduct');
  var uploadBtn = document.getElementById(isManual ? 'imgUploadBtnManual' : 'imgUploadBtnProduct');
  var count     = Math.max(1, Math.min(20, parseInt(countEl.value, 10) || 1));

  container.innerHTML = '';
  uploadBtn.disabled  = true;

  for (var i = 0; i < count; i++) {
    var boxId  = type + '-box-' + i;
    var fileId = type + '-file-' + i;
    var linkId = type + '-link-' + i;
    var accept = isManual ? '*/*' : 'image/jpeg,image/png,image/webp,image/gif';

    var box = document.createElement('div');
    box.className = 'img-upload-box';

    var html =
      '<div class="img-upload-box-header">' +
        '<span style="background:var(--primary);color:#fff;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;margin-right:6px;">' + (i+1) + '</span>' +
        (isManual ? 'Manual ' : 'Image ') + (i+1) + ' of ' + count +
      '</div>';

    if (isManual) {
      /* Manual: BOTH upload zone AND direct URL side by side */
      html +=
        '<div style="display:grid;grid-template-columns:1fr auto 1fr;gap:10px;align-items:center;margin-bottom:10px;">' +
          '<div>' +
            '<label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:6px;">Upload File <span style="font-weight:400;text-transform:none;">(optional)</span></label>' +
            '<div class="img-upload-file-zone" id="zone-' + boxId + '" onclick="document.getElementById(\'' + fileId + '\').click()" style="min-height:56px;padding:10px;">' +
              '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>' +
              '<span id="zone-lbl-' + boxId + '" style="font-size:12px;color:#9ca3af;margin-left:6px;">Click to choose</span>' +
            '</div>' +
            '<input type="file" name="product_images[]" id="' + fileId + '" accept="' + accept + '" style="display:none;" onchange="onImgBoxFileChange(this,\'' + boxId + '\',\'' + type + '\')">' +
          '</div>' +
          '<div style="text-align:center;font-size:12px;font-weight:700;color:var(--text-muted);padding-top:22px;">OR</div>' +
          '<div>' +
            '<label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:6px;">Direct URL <span style="font-weight:400;text-transform:none;">(optional)</span></label>' +
            '<input type="text" name="hyper_links[]" id="' + linkId + '" class="form-control" placeholder="https://…" style="height:56px;" oninput="onImgBoxLinkInput(\'' + type + '\')">' +
          '</div>' +
        '</div>';
    } else {
      /* Product image: upload zone only */
      html +=
        '<div class="img-upload-file-zone" id="zone-' + boxId + '" onclick="document.getElementById(\'' + fileId + '\').click()" style="margin-bottom:10px;">' +
          '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>' +
          '<span id="zone-lbl-' + boxId + '" style="font-size:12px;color:#9ca3af;margin-left:6px;">Click to choose image</span>' +
        '</div>' +
        '<input type="file" name="product_images[]" id="' + fileId + '" accept="' + accept + '" style="display:none;" onchange="onImgBoxFileChange(this,\'' + boxId + '\',\'' + type + '\')">';
    }

    /* Common fields row 1: Name + Priority */
    html +=
      '<div class="img-box-grid-2">' +
        '<div class="fg"><label style="font-size:12px;">File Name <span class="req">*</span></label>' +
          '<input type="text" name="image_names[]" class="form-control" placeholder="e.g. Front View" style="height:34px;"></div>' +
        '<div class="fg"><label style="font-size:12px;">Priority</label>' +
          '<input type="number" name="priorities[]" class="form-control" value="' + (i+1) + '" min="0" style="height:34px;"></div>' +
      '</div>';

    /* Common fields row 2: Display + Manual Title (manual only) */
    html +=
      '<div class="img-box-grid-2" style="margin-top:8px;">' +
        '<div class="fg"><label style="font-size:12px;">Display</label>' +
          '<select name="display_flags[]" class="form-control" style="height:34px;">' +
            '<option value="Yes">Yes</option><option value="No">No</option>' +
          '</select></div>' +
        (isManual
          ? '<div class="fg"><label style="font-size:12px;">Manual Title</label>' +
              '<input type="text" name="manual_titles[]" class="form-control" placeholder="e.g. Installation Guide" style="height:34px;"></div>'
          : '<div></div>') +
      '</div>';

    if (!isManual) {
      /* Hidden dummy arrays to keep POST index alignment for non-manual forms */
      html += '<input type="hidden" name="hyper_links[]" value=""><input type="hidden" name="manual_titles[]" value="">';
    }

    box.innerHTML = html;
    container.appendChild(box);
  }
}

function _checkImgUploadBtn(type) {
  var isManual  = type === 'manual';
  var container = document.getElementById(isManual ? 'imgBoxesManual' : 'imgBoxesProduct');
  var uploadBtn = document.getElementById(isManual ? 'imgUploadBtnManual' : 'imgUploadBtnProduct');
  var hasAny    = false;

  container.querySelectorAll('input[type="file"]').forEach(function(f) {
    if (f.files && f.files.length > 0) hasAny = true;
  });
  if (isManual) {
    container.querySelectorAll('input[name="hyper_links[]"]').forEach(function(l) {
      if (l.value.trim() !== '') hasAny = true;
    });
  }
  uploadBtn.disabled = !hasAny;
}

function onImgBoxFileChange(input, boxId, type) {
  var zone = document.getElementById('zone-' + boxId);
  var lbl  = document.getElementById('zone-lbl-' + boxId);
  if (input.files && input.files.length > 0) {
    var fname = input.files[0].name;
    lbl.textContent = fname.length > 28 ? fname.slice(0, 26) + '…' : fname;
    lbl.style.color = '#15803d';
    zone.classList.add('has-file');
  } else {
    lbl.textContent = type === 'manual' ? 'Click to choose' : 'Click to choose image';
    lbl.style.color = '#9ca3af';
    zone.classList.remove('has-file');
  }
  _checkImgUploadBtn(type);
}

function onImgBoxLinkInput(type) {
  _checkImgUploadBtn(type);
}


/* ═══════════════════════════════════════════════════
   VIEW DETAILS MODAL
   ═══════════════════════════════════════════════════ */
function openViewModal(prodId) {
  _viewProdId = prodId;
  var d = PROD_DATA.find(function(p) { return p.id === prodId; });
  if (!d) return;

  document.getElementById('viewProdTitle').textContent   = d.name;
  document.getElementById('viewProdName').textContent    = d.name;

  var codeEl = document.getElementById('viewProdCode');
  codeEl.textContent = d.code;
  codeEl.style.display = d.code ? 'inline' : 'none';

  var sBadge = document.getElementById('viewProdStatusBadge');
  sBadge.textContent = d.status;
  sBadge.style.cssText = d.status === 'Active'
    ? 'font-size:11px;font-weight:600;padding:2px 10px;border-radius:20px;background:#dcfce7;color:#15803d;'
    : 'font-size:11px;font-weight:600;padding:2px 10px;border-radius:20px;background:#fee2e2;color:#dc2626;';

  var lBadge = document.getElementById('viewProdLabelBadge');
  lBadge.textContent = d.label;
  lBadge.style.display = d.label ? 'inline' : 'none';

  document.getElementById('viewProdCat').textContent       = d.cat_name || '—';
  document.getElementById('viewProdAmt').textContent       = d.amt > 0 ? '₹' + parseFloat(d.amt).toFixed(2) : '—';
  document.getElementById('viewProdTax').textContent       = d.tax > 0 ? d.tax + '%' : '—';
  document.getElementById('viewProdDisc').textContent      = d.disc > 0 ? d.disc + '%' : '—';
  document.getElementById('viewProdOffer').textContent     = d.offer_pct !== '' ? d.offer_pct + '%' : '—';
  document.getElementById('viewProdRating').textContent    = d.rating !== '' ? d.rating + ' / 5' : '—';
  document.getElementById('viewProdStock').textContent     = d.total > 0 ? d.remaining + ' / ' + d.total : '—';
  document.getElementById('viewProdSold').textContent      = d.sold > 0 ? d.sold : '—';
  document.getElementById('viewProdThreshold').textContent = d.threshold;
  document.getElementById('viewProdDisplay').textContent   = d.display;

  /* Description / Spec / Details */
  document.getElementById('vdesc').innerHTML = d.desc  || '<p style="color:var(--text-muted);font-size:13px;">No description.</p>';
  document.getElementById('vspec').innerHTML = d.spec  || '<p style="color:var(--text-muted);font-size:13px;">No specification.</p>';
  document.getElementById('vdets').innerHTML = d.dets  || '<p style="color:var(--text-muted);font-size:13px;">No additional details.</p>';

  /* Sample code tab */
  var scCodes = PROD_SAMPLE[String(prodId)] || [];
  var scEl = document.getElementById('vsampleContent');
  if (scCodes.length === 0) {
    scEl.innerHTML = '<p style="color:var(--text-muted);font-size:13px;">No sample code entries.</p>';
  } else {
    var scHtml =
      '<div style="overflow-x:auto;">' +
      '<table style="width:100%;border-collapse:collapse;font-size:13px;">' +
      '<thead><tr style="background:#f8fafc;">' +
        '<th class="sc-vth">Language / Technology</th>' +
        '<th class="sc-vth">IDE / Compiler</th>' +
        '<th class="sc-vth">Type</th>' +
        '<th class="sc-vth">OS</th>' +
        '<th class="sc-vth">Repository URL</th>' +
        '<th class="sc-vth">Date</th>' +
      '</tr></thead><tbody>';
    scCodes.forEach(function(c, i) {
      var bg = i % 2 === 0 ? '#fff' : '#f8fafc';
      var urlCell = c.url
        ? '<a href="' + c.url + '" target="_blank" style="color:#4f46e5;word-break:break-all;">' + c.url + ' ↗</a>'
        : '<span style="color:var(--text-muted);">—</span>';
      scHtml +=
        '<tr style="background:' + bg + ';border-bottom:1px solid #f1f5f9;">' +
          '<td class="sc-vtd">' + (c.lang || '—') + '</td>' +
          '<td class="sc-vtd">' + (c.ide  || '—') + '</td>' +
          '<td class="sc-vtd">' + (c.type || '—') + '</td>' +
          '<td class="sc-vtd">' + (c.os   || '—') + '</td>' +
          '<td class="sc-vtd">' + urlCell + '</td>' +
          '<td class="sc-vtd" style="white-space:nowrap;color:var(--text-muted);">' + (c.date ? c.date.slice(0,10) : '—') + '</td>' +
        '</tr>';
    });
    scHtml += '</tbody></table></div>';
    scEl.innerHTML = scHtml;
  }

  /* Reset to first tab */
  switchViewTab('vdesc', document.querySelector('.prod-view-tab'));

  /* Image gallery */
  var imgs = PROD_IMAGES[prodId] || [];
  var mainImg  = document.getElementById('viewMainImg');
  var mainPH   = document.getElementById('viewMainImgPlaceholder');
  var thumbStrip = document.getElementById('viewThumbStrip');
  thumbStrip.innerHTML = '';

  var prodImgs = imgs.filter(function(i) { return i.for !== 'Product Mannual' && i.url; });
  if (prodImgs.length > 0) {
    mainImg.src = prodImgs[0].url;
    mainImg.style.display = 'block';
    mainPH.style.display  = 'none';
    prodImgs.forEach(function(img, idx) {
      var t = document.createElement('img');
      t.src = img.url;
      t.className = 'prod-view-thumb' + (idx === 0 ? ' active' : '');
      t.onclick = function() {
        mainImg.src = img.url;
        document.querySelectorAll('.prod-view-thumb').forEach(function(th) { th.classList.remove('active'); });
        t.classList.add('active');
      };
      thumbStrip.appendChild(t);
    });
  } else {
    mainImg.style.display = 'none';
    mainPH.style.display  = 'flex';
  }

  openModal('viewProdModal');
}


/* ═══════════════════════════════════════════════════
   DELETE
   ═══════════════════════════════════════════════════ */
function confirmDeleteProd(prodId, name) {
  document.getElementById('delProdId').value         = prodId;
  document.getElementById('delProdName').textContent = name;
  openModal('deleteProdModal');
}


/* ═══════════════════════════════════════════════════
   EXPORT XLS
   ═══════════════════════════════════════════════════ */
function exportProdXLS() {
  var q      = document.getElementById('prodSearch').value.toLowerCase().trim();
  var catFil = document.getElementById('prodCatFilter').value;
  var stFil  = document.getElementById('prodStatusFilter').value;

  var headers = ['S.No.','Product Name','Code','Category','Status','Price (₹)','Tax %','Discount %','Offer %','Rating','Label','Stock Remaining','Total Stock','Total Sold','Threshold','Display'];
  var rows = [headers];
  var idx = 1;

  PROD_DATA.forEach(function(p) {
    var nm = (p.name + ' ' + p.code + ' ' + p.cat_name + ' ' + p.label).toLowerCase();
    if (q && !nm.includes(q)) return;
    if (catFil && String(p.cat_id) !== catFil) return;
    if (stFil  && p.status !== stFil) return;
    rows.push([
      idx++, p.name, p.code, p.cat_name, p.status,
      p.amt, p.tax, p.disc,
      p.offer_pct !== '' ? p.offer_pct : '',
      p.rating    !== '' ? p.rating    : '',
      p.label, p.remaining, p.total, p.sold, p.threshold, p.display
    ]);
  });

  var wb = XLSX.utils.book_new();
  var ws = XLSX.utils.aoa_to_sheet(rows);
  ws['!cols'] = [
    {wch:6},{wch:32},{wch:16},{wch:22},{wch:10},
    {wch:12},{wch:8},{wch:12},{wch:10},{wch:8},
    {wch:12},{wch:14},{wch:12},{wch:12},{wch:12},{wch:10}
  ];
  XLSX.utils.book_append_sheet(wb, ws, 'Products');
  XLSX.writeFile(wb, 'products_' + new Date().toISOString().slice(0,10) + '.xlsx');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
