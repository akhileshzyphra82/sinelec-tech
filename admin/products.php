<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'products';
$pageTitle   = 'Products';

$controller = new AdminController();
$categories = $controller->getParentCategories();

// ── Detail / Edit view ──
$product     = null;
$productImgs = [];
if (!empty($_GET['id'])) {
    $product     = $controller->getProductById((int)$_GET['id']);
    $productImgs = $controller->getProductImages((int)$_GET['id']);
}

// ── List view filters ──
$filters = [
    'cat'    => (int)($_GET['cat'] ?? 0),
    'name'   => trim($_GET['name'] ?? ''),
    'code'   => trim($_GET['code'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
];
$products = $product ? [] : $controller->getAllProducts($filters);
$allCats  = $controller->getAllCategories();

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title"><?= $product ? htmlspecialchars($product->PRODUCT_NAME ?? 'Product') : 'Products' ?></div>
    <div class="pg-subtitle"><?= $product ? 'Edit product details and manage images.' : 'Manage your product catalogue.' ?></div>
  </div>
  <?php if (!$product): ?>
  <button class="btn btn--primary" onclick="openModal('addModal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Product
  </button>
  <?php else: ?>
  <a href="products" class="btn btn--outline">← Back to Products</a>
  <?php endif; ?>
</div>

<?php if ($product): ?>
<!-- ═══════════════════════════════
     PRODUCT DETAIL / EDIT VIEW
═══════════════════════════════ -->
<?php
  $pid = (int)($product->PRODUCT_ID ?? 0);
  $sts = (string)($product->PRODUCT_STATUS ?? 'Active');
?>

<div class="prod-detail-grid">

  <!-- Edit Form Card -->
  <div class="card">
    <div class="card-header"><span class="card-title">Edit Details</span></div>
    <div class="card-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateProduct') ?>" class="form-grid">
        <input type="hidden" name="product_id" value="<?= $pid ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="fg">
            <label>Product Name <span class="req">*</span></label>
            <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product->PRODUCT_NAME ?? '') ?>" required>
          </div>
          <div class="fg">
            <label>Product Code / SKU</label>
            <input type="text" name="product_code" class="form-control" value="<?= htmlspecialchars($product->PRODUCT_CODE ?? '') ?>">
          </div>
        </div>
        <div class="fg">
          <label>Category</label>
          <select name="product_category_id" class="form-control">
            <option value="0">— Uncategorised —</option>
            <?php foreach ($allCats as $c): ?>
            <option value="<?= (int)($c->PRODUCT_CATEGORY_ID ?? 0) ?>"
              <?= (int)($product->PRODUCT_CATEGORY_ID ?? 0) === (int)($c->PRODUCT_CATEGORY_ID ?? 0) ? 'selected' : '' ?>>
              <?= ($c->PARENT_CATEGORY_ID ?? 0) > 0 ? '  › ' : '' ?><?= htmlspecialchars($c->PRODUCT_CATEGORY_NAME ?? '') ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label>Description</label>
          <textarea name="product_description" class="form-control" rows="3"><?= htmlspecialchars($product->PRODUCT_DESCRIPTION ?? '') ?></textarea>
        </div>
        <div class="fg">
          <label>Specification</label>
          <textarea name="product_specification" class="form-control" rows="3"><?= htmlspecialchars($product->PRODUCT_SPECIFICATION ?? '') ?></textarea>
        </div>
        <div class="fg">
          <label>Additional Details</label>
          <textarea name="product_details" class="form-control" rows="2"><?= htmlspecialchars($product->PRODUCT_DETAILS ?? '') ?></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
          <div class="fg">
            <label>Price (₹)</label>
            <input type="number" name="product_amt" class="form-control" step="0.01" min="0" value="<?= (float)($product->PRODUCT_AMT ?? 0) ?>">
          </div>
          <div class="fg">
            <label>Tax (%)</label>
            <input type="number" name="product_tax" class="form-control" step="0.01" min="0" value="<?= (float)($product->PRODUCT_TAX ?? 0) ?>">
          </div>
          <div class="fg">
            <label>Discount (%)</label>
            <input type="number" name="product_discount" class="form-control" step="0.01" min="0" value="<?= (float)($product->PRODUCT_DISCOUNT ?? 0) ?>">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
          <div class="fg">
            <label>Priority</label>
            <input type="number" name="priority" class="form-control" min="0" value="<?= (int)($product->PRIORTY ?? 0) ?>">
          </div>
          <div class="fg">
            <label>Status</label>
            <select name="product_status" class="form-control">
              <option value="Active" <?= $sts === 'Active' ? 'selected' : '' ?>>Active</option>
              <option value="In-Active" <?= $sts === 'In-Active' ? 'selected' : '' ?>>In-Active</option>
            </select>
          </div>
          <div class="fg">
            <label>Display on Site</label>
            <select name="display_flag" class="form-control">
              <option value="Yes" <?= ($product->DISPLAY_FLAG ?? 'Yes') === 'Yes' ? 'selected' : '' ?>>Yes</option>
              <option value="No" <?= ($product->DISPLAY_FLAG ?? 'Yes') === 'No' ? 'selected' : '' ?>>No</option>
            </select>
          </div>
        </div>
        <div style="margin-top:4px;">
          <button type="submit" class="btn btn--primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Stock summary + delete -->
  <div>
    <div class="card" style="margin-bottom:16px;">
      <div class="card-header"><span class="card-title">Stock Summary</span></div>
      <div class="card-body">
        <table style="width:100%;font-size:13px;border-collapse:collapse;">
          <tr><td style="padding:5px 0;color:var(--text-muted);">Total In</td><td style="text-align:right;font-weight:600;"><?= (int)($product->TOTAL_PRODUCT ?? 0) ?></td></tr>
          <tr><td style="padding:5px 0;color:var(--text-muted);">Sold</td><td style="text-align:right;"><?= (int)($product->TOTAL_SOLD ?? 0) ?></td></tr>
          <tr><td style="padding:5px 0;color:var(--text-muted);">Remaining</td><td style="text-align:right;font-weight:600;color:<?= (int)($product->TOTAL_REMAINING ?? 0) > 0 ? '#16a34a' : '#dc2626' ?>;"><?= (int)($product->TOTAL_REMAINING ?? 0) ?></td></tr>
          <tr><td style="padding:5px 0;color:var(--text-muted);">Threshold</td><td style="text-align:right;"><?= (int)($product->PRODUCT_THRESHOLD ?? 0) ?></td></tr>
        </table>
        <a href="purchase" class="btn btn--outline" style="width:100%;margin-top:10px;text-align:center;display:block;font-size:12px;">Add Purchase Record</a>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title" style="color:#dc2626;">Danger Zone</span></div>
      <div class="card-body">
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">Permanently delete this product. This cannot be undone.</p>
        <button class="btn" style="width:100%;background:#fff5f5;color:#dc2626;border:1px solid #fecaca;font-size:13px;"
          onclick="confirmDeleteProduct(<?= $pid ?>, <?= htmlspecialchars(json_encode($product->PRODUCT_NAME ?? ''),ENT_QUOTES) ?>)">
          Delete Product
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Images Section -->
<div class="card" style="margin-top:16px;">
  <div class="card-header">
    <span class="card-title">Product Images &amp; Documents</span>
    <button class="btn btn--primary" style="font-size:12px;padding:5px 12px;" onclick="openModal('addImgModal')">Upload Image</button>
  </div>
  <div class="card-body">
    <?php if (empty($productImgs)): ?>
    <div class="empty-state" style="padding:20px;">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <p style="font-size:13px;">No images yet.</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-wrap:wrap;gap:12px;">
      <?php foreach ($productImgs as $img): ?>
      <?php
        $imgId  = (int)($img->IMAGE_ID ?? 0);
        $imgExt = (string)($img->IMAGE_EXT ?? '');
        $imgFor = (string)($img->IMAGE_FOR ?? 'Product');
        $title  = htmlspecialchars($img->PRODUCT_MANUAL_TITLE ?? '');
        $src    = '../assets/uploads/products/'.$imgId.'.'.$imgExt;
      ?>
      <div style="position:relative;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;width:130px;">
        <img src="<?= htmlspecialchars($src) ?>" style="width:130px;height:100px;object-fit:cover;display:block;" alt="" onerror="this.style.background='#f1f5f9'">
        <div style="padding:6px 8px;background:#fff;">
          <div style="font-size:10px;color:var(--text-muted);margin-bottom:4px;"><?= $imgFor ?><?= $title ? ' — '.$title : '' ?></div>
          <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteProductImage') ?>" onsubmit="return confirm('Remove this image?')">
            <input type="hidden" name="image_id" value="<?= $imgId ?>">
            <input type="hidden" name="product_id" value="<?= $pid ?>">
            <button type="submit" style="width:100%;padding:3px;font-size:11px;background:#fff5f5;color:#dc2626;border:1px solid #fecaca;border-radius:4px;cursor:pointer;">Remove</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Add Image Modal -->
<div class="modal-overlay" id="addImgModal">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header">
      <span class="modal-title">Upload Image / Document</span>
      <button class="modal-close" onclick="closeModal('addImgModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=AddProductImage') ?>" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="product_id" value="<?= $pid ?>">
        <div class="fg">
          <label>Image File <span class="req">*</span></label>
          <input type="file" name="product_image" class="form-control" accept="image/*" required>
        </div>
        <div class="fg">
          <label>Image For</label>
          <select name="image_for" class="form-control">
            <option value="Product">Product Image</option>
            <option value="Manual">Manual / Document</option>
          </select>
        </div>
        <div class="fg">
          <label>Title (for manuals)</label>
          <input type="text" name="product_manual_title" class="form-control">
        </div>
        <div class="fg">
          <label>Priority</label>
          <input type="number" name="img_priority" class="form-control" value="0" min="0">
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Upload</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('addImgModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Product Modal -->
<div class="modal-overlay" id="delProdModal">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <span class="modal-title">Delete Product</span>
      <button class="modal-close" onclick="closeModal('delProdModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-muted);margin-bottom:16px;">Delete <strong id="del_prod_name"></strong>? This cannot be undone.</p>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteProduct') ?>">
        <input type="hidden" name="product_id" id="del_prod_id">
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn" style="background:#dc2626;color:#fff;border:none;">Yes, Delete</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('delProdModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ═══════════════════════════════
     PRODUCT LIST VIEW
═══════════════════════════════ -->

<!-- Filters -->
<form method="GET" action="products" class="filter-bar">
  <select name="cat" class="form-control" style="max-width:180px;">
    <option value="">All Categories</option>
    <?php foreach ($allCats as $c): ?>
    <option value="<?= (int)($c->PRODUCT_CATEGORY_ID ?? 0) ?>" <?= $filters['cat'] == (int)($c->PRODUCT_CATEGORY_ID ?? 0) ? 'selected' : '' ?>>
      <?= ($c->PARENT_CATEGORY_ID ?? 0) > 0 ? '  › ' : '' ?><?= htmlspecialchars($c->PRODUCT_CATEGORY_NAME ?? '') ?>
    </option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="name" class="form-control" style="max-width:180px;" placeholder="Name…" value="<?= htmlspecialchars($filters['name']) ?>">
  <input type="text" name="code" class="form-control" style="max-width:140px;" placeholder="Code / SKU…" value="<?= htmlspecialchars($filters['code']) ?>">
  <select name="status" class="form-control" style="max-width:140px;">
    <option value="">All Statuses</option>
    <option value="Active" <?= $filters['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
    <option value="In-Active" <?= $filters['status'] === 'In-Active' ? 'selected' : '' ?>>In-Active</option>
  </select>
  <button type="submit" class="btn btn--primary">Filter</button>
  <a href="products" class="btn btn--outline">Reset</a>
</form>

<div class="card">
  <div class="card-header">
    <span class="card-title">Products</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($products) ?> found</span>
  </div>
  <div class="card-body card-body--flush">
    <?php if (empty($products)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3" y="3" width="18" height="18" rx="3"/><rect x="8" y="8" width="8" height="8" rx="1.5"/></svg>
      <p>No products found.</p>
      <button class="btn btn--primary" onclick="openModal('addModal')">Add First Product</button>
    </div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th style="width:56px;">Image</th>
          <th>Product</th>
          <th>Category</th>
          <th style="text-align:right;">Price</th>
          <th style="text-align:right;">Stock</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <?php
          $pid     = (int)($p->PRODUCT_ID ?? 0);
          $name    = htmlspecialchars($p->PRODUCT_NAME ?? '');
          $code    = htmlspecialchars($p->PRODUCT_CODE ?? '');
          $cat     = htmlspecialchars($p->PRODUCT_CATEGORY_NAME ?? '—');
          $price   = (float)($p->PRODUCT_AMT ?? 0);
          $stock   = (int)($p->TOTAL_REMAINING ?? 0);
          $sts     = (string)($p->PRODUCT_STATUS ?? 'Active');
          $thumbExt= (string)($p->THUMB_EXT ?? '');
          $thumb   = $thumbExt ? '../assets/uploads/products/'.$pid.'_thumb.'.$thumbExt : '';
          // Try real image from tbl_product_img first image
          $imgId   = 0; // product image id would need sub-query; use product_id as fallback
        ?>
        <tr>
          <td>
            <div style="width:44px;height:44px;border-radius:6px;border:1px solid #e2e8f0;overflow:hidden;background:#f8fafc;display:flex;align-items:center;justify-content:center;">
              <?php if ($thumbExt): ?>
              <?php
                // thumb_ext is the first image's ext from tbl_product_img sub-query
                // We need the image_id — store as product img where product_id=pid LIMIT 1
                // Since we don't have image_id here, show a placeholder with note
              ?>
              <span style="font-size:9px;color:#94a3b8;text-align:center;padding:2px;">IMG</span>
              <?php else: ?>
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <div style="font-weight:500;"><?= $name ?></div>
            <?php if ($code): ?><div style="font-size:11px;color:var(--text-muted);"><?= $code ?></div><?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--text-muted);"><?= $cat ?></td>
          <td style="text-align:right;">₹<?= number_format($price, 2) ?></td>
          <td style="text-align:right;font-weight:500;<?= $stock <= 0 ? 'color:#dc2626;' : '' ?>"><?= $stock ?></td>
          <td><span class="badge <?= $sts === 'Active' ? 'badge--green' : 'badge--red' ?>"><?= $sts ?></span></td>
          <td>
            <a href="products?id=<?= $pid ?>" class="btn btn--outline" style="padding:4px 10px;font-size:12px;">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- ── Add Product Modal ── -->
<div class="modal-overlay" id="addModal">
  <div class="modal" style="max-width:640px;max-height:90vh;overflow-y:auto;">
    <div class="modal-header" style="position:sticky;top:0;background:#fff;z-index:1;">
      <span class="modal-title">Add Product</span>
      <button class="modal-close" onclick="closeModal('addModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=InsertProduct') ?>" enctype="multipart/form-data" class="form-grid">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="fg">
            <label>Product Name <span class="req">*</span></label>
            <input type="text" name="product_name" class="form-control" required>
          </div>
          <div class="fg">
            <label>Product Code / SKU</label>
            <input type="text" name="product_code" class="form-control">
          </div>
        </div>
        <div class="fg">
          <label>Category</label>
          <select name="product_category_id" class="form-control">
            <option value="0">— Uncategorised —</option>
            <?php foreach ($allCats as $c): ?>
            <option value="<?= (int)($c->PRODUCT_CATEGORY_ID ?? 0) ?>">
              <?= ($c->PARENT_CATEGORY_ID ?? 0) > 0 ? '  › ' : '' ?><?= htmlspecialchars($c->PRODUCT_CATEGORY_NAME ?? '') ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label>Description</label>
          <textarea name="product_description" class="form-control" rows="2"></textarea>
        </div>
        <div class="fg">
          <label>Specification</label>
          <textarea name="product_specification" class="form-control" rows="2"></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
          <div class="fg">
            <label>Price (₹)</label>
            <input type="number" name="product_amt" class="form-control" step="0.01" min="0" value="0">
          </div>
          <div class="fg">
            <label>Tax (%)</label>
            <input type="number" name="product_tax" class="form-control" step="0.01" min="0" value="0">
          </div>
          <div class="fg">
            <label>Discount (%)</label>
            <input type="number" name="product_discount" class="form-control" step="0.01" min="0" value="0">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
          <div class="fg">
            <label>Priority</label>
            <input type="number" name="priority" class="form-control" value="0" min="0">
          </div>
          <div class="fg">
            <label>Status</label>
            <select name="product_status" class="form-control">
              <option value="Active">Active</option>
              <option value="In-Active">In-Active</option>
            </select>
          </div>
          <div class="fg">
            <label>Display on Site</label>
            <select name="display_flag" class="form-control">
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select>
          </div>
        </div>
        <div class="fg">
          <label>Main Product Image</label>
          <input type="file" name="product_image" class="form-control" accept="image/*">
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Add Product</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('addModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php endif; ?>


<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
