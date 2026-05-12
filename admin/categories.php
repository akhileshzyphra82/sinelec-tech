<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'categories';
$pageTitle   = 'Product Categories';

$controller = new AdminController();
$categories = $controller->getAllCategories();
$parents    = $controller->getParentCategories();

// Edit mode
$editCat = null;
if (!empty($_GET['edit'])) {
    $editCat = $controller->getCategoryById((int)$_GET['edit']);
}

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Product Categories</div>
    <div class="pg-subtitle">Manage top-level and sub-categories for your product catalogue.</div>
  </div>
  <button class="btn btn--primary" onclick="openModal('addModal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Category
  </button>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">All Categories</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($categories) ?> total</span>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($categories)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
      <p>No categories yet.</p>
      <button class="btn btn--primary" onclick="openModal('addModal')">Add First Category</button>
    </div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th style="width:60px;">Image</th>
          <th>Category Name</th>
          <th>Parent</th>
          <th>Priority</th>
          <th>Description</th>
          <th style="width:120px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
        <?php
          $catId   = (int)($cat->PRODUCT_CATEGORY_ID ?? 0);
          $catName = htmlspecialchars($cat->PRODUCT_CATEGORY_NAME ?? '');
          $parent  = htmlspecialchars($cat->PARENT_NAME ?? '—');
          $prio    = (int)($cat->PRIORITY ?? 0);
          $desc    = htmlspecialchars($cat->DESCRIPTION ?? '');
          $ext     = (string)($cat->EXT ?? '');
          $imgSrc  = ($ext !== '') ? '../assets/uploads/categories/'.$catId.'.'.$ext : '';
        ?>
        <tr>
          <td>
            <?php if ($imgSrc): ?>
              <img src="<?= htmlspecialchars($imgSrc) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;" alt="">
            <?php else: ?>
              <span style="display:inline-flex;width:44px;height:44px;background:#f1f5f9;border-radius:6px;align-items:center;justify-content:center;color:#94a3b8;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </span>
            <?php endif; ?>
          </td>
          <td><strong><?= $catName ?></strong></td>
          <td><?= $parent ?></td>
          <td><?= $prio ?></td>
          <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-muted);font-size:12px;"><?= $desc ?: '—' ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <button class="btn btn--outline" style="padding:4px 10px;font-size:12px;"
                onclick="openEditModal(<?= $catId ?>,<?= htmlspecialchars(json_encode($cat->PRODUCT_CATEGORY_NAME ?? ''),ENT_QUOTES) ?>,<?= (int)($cat->PARENT_CATEGORY_ID ?? 0) ?>,<?= $prio ?>,<?= htmlspecialchars(json_encode($cat->DESCRIPTION ?? ''),ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($ext),ENT_QUOTES) ?>)">
                Edit
              </button>
              <button class="btn" style="padding:4px 10px;font-size:12px;background:#fff5f5;color:#dc2626;border:1px solid #fecaca;"
                onclick="confirmDelete(<?= $catId ?>, <?= htmlspecialchars(json_encode($catName),ENT_QUOTES) ?>)">
                Delete
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- ── Add Modal ── -->
<div class="modal-overlay" id="addModal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <span class="modal-title">Add Category</span>
      <button class="modal-close" onclick="closeModal('addModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=InsertCategory') ?>" enctype="multipart/form-data" class="form-grid">
        <div class="fg">
          <label class="fc">Category Name <span class="req">*</span></label>
          <input type="text" name="product_category_name" class="form-control" required>
        </div>
        <div class="fg">
          <label class="fc">Parent Category</label>
          <select name="parent_category_id" class="form-control">
            <option value="0">— None (Top-level) —</option>
            <?php foreach ($parents as $p): ?>
            <option value="<?= (int)($p->PRODUCT_CATEGORY_ID ?? 0) ?>"><?= htmlspecialchars($p->PRODUCT_CATEGORY_NAME ?? '') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label class="fc">Priority</label>
          <input type="number" name="priority" class="form-control" value="0" min="0">
        </div>
        <div class="fg">
          <label class="fc">Description</label>
          <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
        <div class="fg">
          <label class="fc">Category Image</label>
          <input type="file" name="category_image" class="form-control" accept="image/*">
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Add Category</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('addModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Edit Modal ── -->
<div class="modal-overlay" id="editModal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <span class="modal-title">Edit Category</span>
      <button class="modal-close" onclick="closeModal('editModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateCategory') ?>" enctype="multipart/form-data" class="form-grid" id="editCatForm">
        <input type="hidden" name="product_category_id" id="edit_cat_id">
        <input type="hidden" name="existing_ext" id="edit_existing_ext">
        <div class="fg">
          <label class="fc">Category Name <span class="req">*</span></label>
          <input type="text" name="product_category_name" id="edit_cat_name" class="form-control" required>
        </div>
        <div class="fg">
          <label class="fc">Parent Category</label>
          <select name="parent_category_id" id="edit_cat_parent" class="form-control">
            <option value="0">— None (Top-level) —</option>
            <?php foreach ($parents as $p): ?>
            <option value="<?= (int)($p->PRODUCT_CATEGORY_ID ?? 0) ?>"><?= htmlspecialchars($p->PRODUCT_CATEGORY_NAME ?? '') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label class="fc">Priority</label>
          <input type="number" name="priority" id="edit_cat_prio" class="form-control" min="0">
        </div>
        <div class="fg">
          <label class="fc">Description</label>
          <textarea name="description" id="edit_cat_desc" class="form-control" rows="2"></textarea>
        </div>
        <div class="fg">
          <label class="fc">Replace Image (optional)</label>
          <input type="file" name="category_image" class="form-control" accept="image/*">
          <span id="edit_img_preview" style="display:none;margin-top:6px;"></span>
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Save Changes</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('editModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Delete Confirm Modal ── -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <span class="modal-title">Delete Category</span>
      <button class="modal-close" onclick="closeModal('deleteModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-muted);margin-bottom:16px;">Are you sure you want to delete <strong id="del_cat_name"></strong>? This cannot be undone.</p>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteCategory') ?>">
        <input type="hidden" name="product_category_id" id="del_cat_id">
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn" style="background:#dc2626;color:#fff;border:none;">Yes, Delete</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('deleteModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEditModal(id, name, parentId, prio, desc, ext) {
  document.getElementById('edit_cat_id').value    = id;
  document.getElementById('edit_cat_name').value  = name;
  document.getElementById('edit_cat_prio').value  = prio;
  document.getElementById('edit_cat_desc').value  = desc;
  document.getElementById('edit_existing_ext').value = ext;
  var sel = document.getElementById('edit_cat_parent');
  for (var i=0;i<sel.options.length;i++) {
    sel.options[i].selected = (parseInt(sel.options[i].value) === parentId);
  }
  var prev = document.getElementById('edit_img_preview');
  if (ext) {
    prev.style.display = 'block';
    prev.innerHTML = '<img src="../assets/uploads/categories/'+id+'.'+ext+'" style="height:60px;border-radius:6px;border:1px solid #e2e8f0;" onerror="this.parentNode.style.display=\'none\'">';
  } else { prev.style.display='none'; }
  openModal('editModal');
}
function confirmDelete(id, name) {
  document.getElementById('del_cat_id').value   = id;
  document.getElementById('del_cat_name').textContent = name;
  openModal('deleteModal');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
