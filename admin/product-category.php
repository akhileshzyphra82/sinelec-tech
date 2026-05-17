<?php
/*
 * NOTE: Run this once to widen the ext column for R2 keys:
 * ALTER TABLE tbl_product_category MODIFY COLUMN ext VARCHAR(500);
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'product-category';
$pageTitle   = 'Product Categories';

$controller = new AdminController();

$canView   = sinelec_can('view');
$canAdd    = sinelec_can('add');
$canEdit   = sinelec_can('edit');
$canDelete = sinelec_can('delete');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

$allCats = $controller->getAllCategories();
//echo "<pre>"; print_r($allCats); echo "</pre>"; die;
$pubBase = rtrim(sinelec_env('PUBLIC_BASE_URL'), '/');

/* Split into parents and children map */
$parents  = [];
$childMap = []; /* [parent_id => [children]] */
foreach ($allCats as $c) {
    $pid = (int)($c->PARENT_CATEGORY_ID ?? 0);
    if ($pid === 0) {
        $parents[] = $c;
    } else {
        $childMap[$pid][] = $c;
    }
}



/* For the parent dropdown in form — only top-level categories */
$parentOptions = $parents;
//echo "<pre>"; print_r($childMap); echo "</pre>"; die;
ob_start();
?>

<!-- Quill CSS -->
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<div class="pg-header">
  <div>
    <div class="pg-title">Product Categories</div>
    <div class="pg-sub">Manage product categories and sub-categories.</div>
  </div>
  <?php if ($canAdd): ?>
  <button class="btn btn--primary" onclick="openCatModal(0)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Category
  </button>
  <?php endif; ?>
</div>

<!-- Search -->
<div style="display:flex;gap:10px;margin-bottom:18px;align-items:center;flex-wrap:wrap;">
  <div style="position:relative;flex:1;min-width:220px;max-width:340px;">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="catSearch" class="form-control" placeholder="Search categories…" style="padding-left:32px;height:36px;" oninput="catOnSearch()">
  </div>
</div>

<!-- Table card -->
<div class="card">
  <?php if (empty($parents)): ?>
  <div class="card-body">
    <div class="empty-state">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
      <h3>No categories found</h3>
      <p>Add your first product category to get started.</p>
      <?php if ($canAdd): ?><button class="btn btn--primary" onclick="openCatModal(0)">Add Category</button><?php endif; ?>
    </div>
  </div>
  <?php else: ?>

  <div class="card-body card-body--flush">
    <table class="dt" id="catTable">
      <thead>
        <tr>
          <th style="width:44px;">S.No.</th>
          <th style="width:58px;">Image</th>
          <th>Category</th>
          <th style="width:130px;text-align:center;">Sub-categories</th>
          <th style="width:80px;text-align:center;">Priority</th>
          <th style="width:60px;text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody id="catTbody">

        <?php foreach ($parents as $i => $cat):
          $cid      = (int)($cat->PRODUCT_CATEGORY_ID ?? 0);
          $name     = (string)($cat->PRODUCT_CATEGORY_NAME ?? '');
          $priority = (int)($cat->PRIORITY ?? 0);
          $desc     = (string)($cat->DESCRIPTION ?? '');
          $extKey   = (string)($cat->EXT ?? '');
          $subCount = (int)($cat->SUB_COUNT ?? 0);
          $imgUrl   = $extKey !== '' ? (strpos($extKey, '/') !== false ? $pubBase.'/'.$extKey : '../assets/uploads/categories/'.$cid.'.'.$extKey) : '';
          $initial  = strtoupper(substr(trim($name), 0, 1)) ?: '?';
          $subs     = $childMap[$cid] ?? [];
        ?>

        <!-- ── Parent row ── -->
        <tr class="cat-parent-row" data-cat-id="<?= $cid ?>" data-search="<?= htmlspecialchars(strtolower($name)) ?>">
          <td class="td-sm cat-sno"><?= $i + 1 ?></td>
          <td>
            <?php if ($imgUrl): ?>
              <img src="<?= htmlspecialchars($imgUrl) ?>" alt="" class="cat-thumb">
            <?php else: ?>
              <div class="cat-initial" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);"><?= htmlspecialchars($initial) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600;color:var(--text);font-size:13px;"><?= htmlspecialchars($name) ?></div>
            <div style="font-size:11px;color:#6366f1;font-weight:500;margin-top:2px;">Parent category</div>
          </td>
          <td style="text-align:center;">
            <?php if ($subCount > 0): ?>
            <button class="sub-toggle-btn" onclick="toggleSubs(<?= $cid ?>, this)" title="Click to expand sub-categories">
              <span class="sub-count-badge"><?= $subCount ?></span>
              <svg class="sub-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <?php else: ?>
            <span style="font-size:12px;color:var(--text-muted);">—</span>
            <?php endif; ?>
          </td>
          <td style="text-align:center;">
            <span style="font-size:13px;font-weight:600;color:var(--text);"><?= $priority ?: '<span style="color:var(--text-muted);">—</span>' ?></span>
          </td>
          <td>
            <?php if ($canEdit || $canDelete): ?>
            <div class="kbm-wrap">
              <button class="kbm-btn" onclick="toggleKbm(this)" title="Actions">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
              </button>
              <div class="kbm-drop">
                <?php if ($canEdit): ?>
                <button class="kbm-item" onclick="closeKbm(this);openCatModal(<?= $cid ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </button>
                <?php endif; ?>
                <?php if ($desc): ?>
                <button class="kbm-item" onclick="closeKbm(this);openDescModal(<?= htmlspecialchars(json_encode($name), ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($desc), ENT_QUOTES) ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                  View Description
                </button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <?php if ($canEdit || $desc): ?><div class="kbm-divider"></div><?php endif; ?>
                <button class="kbm-item kbm-item--danger" onclick="closeKbm(this);confirmDeleteCat(<?= $cid ?>,<?= htmlspecialchars(json_encode($name), ENT_QUOTES) ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                  Delete
                </button>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
          </td>
        </tr>

        <?php foreach ($subs as $sub):
          $sid     = (int)($sub->PRODUCT_CATEGORY_ID ?? 0);
          $sName   = (string)($sub->PRODUCT_CATEGORY_NAME ?? '');
          $sPrio   = (int)($sub->PRIORITY ?? 0);
          $sDesc   = (string)($sub->DESCRIPTION ?? '');
          $sExt    = (string)($sub->EXT ?? '');
          $sImg    = $sExt !== '' ? (strpos($sExt, '/') !== false ? $pubBase.'/'.$sExt : '../assets/uploads/categories/'.$sid.'.'.$sExt) : '';
          $sInit   = strtoupper(substr(trim($sName), 0, 1)) ?: '?';
        ?>
        <!-- ── Sub-category row ── -->
        <tr class="cat-sub-row" data-parent-id="<?= $cid ?>" style="display:none;">
          <td></td>
          <td>
            <?php if ($sImg): ?>
              <img src="<?= htmlspecialchars($sImg) ?>" alt="" class="cat-thumb">
            <?php else: ?>
              <div class="cat-initial" style="background:linear-gradient(135deg,#0ea5e9,#06b6d4);width:30px;height:30px;font-size:12px;"><?= htmlspecialchars($sInit) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:3px;height:32px;background:linear-gradient(180deg,#6366f1,#8b5cf6);border-radius:2px;flex-shrink:0;"></div>
              <div>
                <div style="font-size:13px;color:var(--text);font-weight:500;"><?= htmlspecialchars($sName) ?></div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:1px;">Sub-category</div>
              </div>
            </div>
          </td>
          <td style="text-align:center;"><span style="color:var(--text-muted);font-size:12px;">—</span></td>
          <td style="text-align:center;"><span style="font-size:13px;font-weight:600;color:var(--text);"><?= $sPrio ?: '<span style="color:var(--text-muted);">—</span>' ?></span></td>
          <td>
            <?php if ($canEdit || $canDelete): ?>
            <div class="kbm-wrap">
              <button class="kbm-btn" onclick="toggleKbm(this)" title="Actions">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
              </button>
              <div class="kbm-drop">
                <?php if ($canEdit): ?>
                <button class="kbm-item" onclick="closeKbm(this);openCatModal(<?= $sid ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </button>
                <?php endif; ?>
                <?php if ($sDesc): ?>
                <button class="kbm-item" onclick="closeKbm(this);openDescModal(<?= htmlspecialchars(json_encode($sName), ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($sDesc), ENT_QUOTES) ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                  View Description
                </button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <?php if ($canEdit || $sDesc): ?><div class="kbm-divider"></div><?php endif; ?>
                <button class="kbm-item kbm-item--danger" onclick="closeKbm(this);confirmDeleteCat(<?= $sid ?>,<?= htmlspecialchars(json_encode($sName), ENT_QUOTES) ?>)">
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

        <?php endforeach; ?>
      </tbody>
    </table>
    <div id="catNoResults" style="display:none;padding:40px;text-align:center;color:var(--text-muted);font-size:13px;">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto 10px;opacity:.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      No categories match your search.
    </div>
  </div>
  <?php endif; ?>
</div>


<!-- ════════════════════════════════════════════════════
     ADD / EDIT MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="catModal">
  <div class="modal" style="max-width:680px;max-height:94vh;display:flex;flex-direction:column;">

    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div>
        <div class="modal-title" id="catModalTitle">Add Category</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="catModalSub">Fill in the category details below.</div>
      </div>
      <button class="modal-close" onclick="closeModal('catModal')" style="font-size:22px;line-height:1;">×</button>
    </div>

    <div style="overflow-y:auto;flex:1;padding:22px;">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=SaveProductCategory') ?>" id="catForm" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="product_category_id" id="fCatId" value="0">
        <input type="hidden" name="existing_ext" id="fCatExistingExt" value="">
        <input type="hidden" name="description" id="fCatDescHidden">

        <!-- Name + Parent -->
        <div class="form-row cols-2" style="margin-bottom:14px;">
          <div class="fg">
            <label>Category Name <span class="req">*</span></label>
            <input type="text" name="product_category_name" id="fCatName" class="form-control" placeholder="e.g. Electronics" required>
          </div>
          <div class="fg">
            <label>Parent Category</label>
            <select name="parent_category_id" id="fCatParent" class="form-control">
              <option value="0">— None (Top-level) —</option>
              <?php foreach ($parentOptions as $p): ?>
              <option value="<?= (int)($p->PRODUCT_CATEGORY_ID ?? 0) ?>" data-name="<?= htmlspecialchars($p->PRODUCT_CATEGORY_NAME ?? '') ?>">
                <?= htmlspecialchars($p->PRODUCT_CATEGORY_NAME ?? '') ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Priority + Image -->
        <div class="form-row cols-2" style="margin-bottom:14px;">
          <div class="fg">
            <label>Priority</label>
            <input type="number" name="priority" id="fCatPriority" class="form-control" placeholder="e.g. 1" min="0">
          </div>
          <div class="fg">
            <label>Category Image <span style="font-size:11px;color:var(--text-muted);font-weight:400;">(jpg, png, webp — max 5 MB)</span></label>
            <div class="cat-upload-zone" id="catUploadZone" onclick="document.getElementById('fCatImage').click()">
              <input type="file" name="category_image" id="fCatImage" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" onchange="catOnFileSelect(this)">
              <!-- Empty state -->
              <div id="catUploadEmpty">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <span style="font-size:12px;color:#9ca3af;margin-top:4px;">Click to upload image</span>
              </div>
              <!-- Preview state -->
              <div id="catUploadPreview" style="display:none;pointer-events:none;">
                <img id="catUploadThumb" src="" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:6px;border:1px solid var(--border);flex-shrink:0;">
                <div style="flex:1;min-width:0;text-align:left;">
                  <div id="catUploadName" style="font-size:12px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                  <div id="catUploadSize" style="font-size:11px;color:var(--text-muted);margin-top:1px;"></div>
                </div>
                <button type="button" onclick="event.stopPropagation();catRemoveImg()" style="background:none;border:none;cursor:pointer;color:#9ca3af;padding:4px;pointer-events:all;" title="Remove">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Description — Quill editor -->
        <div class="fg" style="margin-bottom:20px;">
          <label>Description</label>
          <div id="catDescEditor" style="min-height:160px;border-radius:0 0 6px 6px;font-size:13px;"></div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:14px;border-top:1px solid var(--border);">
          <button type="button" class="btn btn--outline" onclick="closeModal('catModal')">Cancel</button>
          <button type="submit" class="btn btn--primary" id="catSubmitBtn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Category
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════════════════
     DESCRIPTION VIEW MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="descModal">
  <div class="modal" style="max-width:600px;max-height:85vh;display:flex;flex-direction:column;">
    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div class="modal-title" id="descModalTitle">Description</div>
      <button class="modal-close" onclick="closeModal('descModal')" style="font-size:22px;line-height:1;">×</button>
    </div>
    <div style="overflow-y:auto;flex:1;padding:22px;">
      <div id="descModalBody" class="cat-desc-view"></div>
    </div>
  </div>
</div>


<!-- ── Delete Confirm Modal ── -->
<div class="modal-overlay" id="deleteCatModal">
  <div class="modal modal--sm">
    <div class="modal-hd" style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <span class="modal-title">Delete Category</span>
      <button class="modal-close" onclick="closeModal('deleteCatModal')">×</button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:20px;">
        <div style="width:40px;height:40px;border-radius:50%;background:#fff5f5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
        </div>
        <div>
          <div style="font-weight:600;margin-bottom:5px;font-size:14px;">Are you sure?</div>
          <div style="font-size:13px;color:var(--text-muted);">Delete <strong id="delCatName"></strong>? Categories with products or sub-categories cannot be deleted.</div>
        </div>
      </div>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteProductCategory') ?>">
        <input type="hidden" name="product_category_id" id="delCatId">
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button type="button" class="btn btn--outline" onclick="closeModal('deleteCatModal')">Cancel</button>
          <button type="submit" class="btn btn--danger-solid">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ── Scoped CSS ── -->
<style>
/* Category table */
.cat-thumb { width:38px;height:38px;object-fit:cover;border-radius:6px;border:1px solid var(--border); }
.cat-initial { width:38px;height:38px;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;font-weight:700; }
.cat-parent-row > td { background:#fff; }
.cat-sub-row > td { background:#f8fafc; }
.cat-sub-row > td:first-child { border-left:3px solid #6366f1; }

/* Sub-category toggle button */
.sub-toggle-btn { display:inline-flex;align-items:center;gap:5px;background:#eef2ff;border:none;border-radius:20px;padding:4px 10px;cursor:pointer;transition:background .15s;font-size:12px;color:#4f46e5; }
.sub-toggle-btn:hover { background:#e0e7ff; }
.sub-count-badge { font-weight:700; }
.sub-chevron { transition:transform .2s;flex-shrink:0; }
.sub-chevron.open { transform:rotate(180deg); }

/* Image upload zone */
.cat-upload-zone { border:1.5px dashed var(--border);border-radius:8px;cursor:pointer;transition:border-color .15s;min-height:64px;display:flex;align-items:center;justify-content:center;padding:8px 12px;gap:10px; }
.cat-upload-zone:hover { border-color:var(--primary);background:#f8f9ff; }
#catUploadEmpty { display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px; }
#catUploadPreview { display:flex;align-items:center;gap:10px;width:100%; }

/* Quill overrides */
.ql-container { border-radius:0 0 6px 6px!important;border-color:var(--border)!important;font-family:inherit!important; }
.ql-toolbar { border-radius:6px 6px 0 0!important;border-color:var(--border)!important;background:#fafbfc; }
.ql-editor { min-height:140px;font-size:13px; }

/* Description view */
.cat-desc-view { font-size:13px;line-height:1.7;color:var(--text); }
.cat-desc-view h1,.cat-desc-view h2,.cat-desc-view h3 { margin:.5em 0;font-weight:700; }
.cat-desc-view ul,.cat-desc-view ol { padding-left:1.4em;margin:.4em 0; }
.cat-desc-view a { color:var(--primary);text-decoration:underline; }
.cat-desc-view p { margin:.3em 0; }
</style>


<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<!-- ── Embedded Data + JS ── -->
<script>
const CAT_DATA = <?= json_encode(array_map(function($c) {
    return [
        'id'        => (int)($c->PRODUCT_CATEGORY_ID ?? 0),
        'name'      => (string)($c->PRODUCT_CATEGORY_NAME ?? ''),
        'parent_id' => (int)($c->PARENT_CATEGORY_ID ?? 0),
        'priority'  => (int)($c->PRIORITY ?? 0),
        'desc'      => (string)($c->DESCRIPTION ?? ''),
        'ext'       => (string)($c->EXT ?? ''),
    ];
}, $allCats), JSON_FORCE_OBJECT) ?>;

const PUB_BASE = <?= json_encode($pubBase) ?>;

/* ═══════════════════════════════════════════════════════
   QUILL EDITOR
   ═══════════════════════════════════════════════════════ */
var quill = null;
document.addEventListener('DOMContentLoaded', function () {
  quill = new Quill('#catDescEditor', {
    theme: 'snow',
    placeholder: 'Write a description…',
    modules: {
      toolbar: [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link'],
        ['clean']
      ]
    }
  });
});

/* Copy Quill HTML to hidden input before form submit */
document.getElementById('catForm').addEventListener('submit', function () {
  var html = quill ? quill.root.innerHTML : '';
  if (html === '<p><br></p>') html = '';
  document.getElementById('fCatDescHidden').value = html;
});


/* ═══════════════════════════════════════════════════════
   SEARCH + ACCORDION
   ═══════════════════════════════════════════════════════ */
(function () {
  var allParents = [];
  var expanded   = {};

  function init() {
    allParents = Array.from(document.querySelectorAll('#catTbody .cat-parent-row'));
  }

  window.catOnSearch = function () {
    var q = document.getElementById('catSearch').value.toLowerCase().trim();
    allParents.forEach(function (r) {
      var show = !q || r.dataset.search.includes(q);
      r.style.display = show ? '' : 'none';
      /* Also hide sub-rows if parent is hidden */
      if (!show) {
        var cid = r.dataset.catId;
        document.querySelectorAll('.cat-sub-row[data-parent-id="'+cid+'"]').forEach(function(s) {
          s.style.display = 'none';
        });
      } else if (expanded[r.dataset.catId]) {
        /* Re-show sub-rows if parent was expanded */
        document.querySelectorAll('.cat-sub-row[data-parent-id="'+r.dataset.catId+'"]').forEach(function(s) {
          s.style.display = '';
        });
      }
    });
    var noRes = document.getElementById('catNoResults');
    if (noRes) {
      var anyVisible = allParents.some(function(r) { return r.style.display !== 'none'; });
      noRes.style.display = anyVisible ? 'none' : 'block';
    }
  };

  window.toggleSubs = function (catId, btn) {
    expanded[catId] = !expanded[catId];
    var chevron = btn.querySelector('.sub-chevron');
    if (chevron) chevron.classList.toggle('open', expanded[catId]);
    document.querySelectorAll('.cat-sub-row[data-parent-id="'+catId+'"]').forEach(function(r) {
      r.style.display = expanded[catId] ? '' : 'none';
    });
  };

  document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', init) : init();
})();


/* ═══════════════════════════════════════════════════════
   IMAGE UPLOAD WIDGET
   ═══════════════════════════════════════════════════════ */
function catOnFileSelect(input) {
  if (!input.files || !input.files[0]) return;
  var file   = input.files[0];
  var reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('catUploadThumb').src = e.target.result;
  };
  reader.readAsDataURL(file);
  document.getElementById('catUploadName').textContent = file.name;
  document.getElementById('catUploadSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
  document.getElementById('catUploadEmpty').style.display   = 'none';
  document.getElementById('catUploadPreview').style.display = 'flex';
}

function catRemoveImg() {
  document.getElementById('fCatImage').value = '';
  document.getElementById('fCatExistingExt').value = '';
  document.getElementById('catUploadThumb').src = '';
  document.getElementById('catUploadEmpty').style.display   = 'flex';
  document.getElementById('catUploadPreview').style.display = 'none';
}


/* ═══════════════════════════════════════════════════════
   ADD / EDIT MODAL
   ═══════════════════════════════════════════════════════ */
var _currentCatId = 0;
var catSaveSvg = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>';

function openCatModal(catId) {
  _currentCatId = catId || 0;

  document.getElementById('fCatId').value          = _currentCatId;
  document.getElementById('fCatExistingExt').value = '';
  document.getElementById('fCatName').value        = '';
  document.getElementById('fCatPriority').value    = '';
  document.getElementById('fCatParent').value      = '0';
  document.getElementById('fCatImage').value       = '';
  document.getElementById('catUploadEmpty').style.display   = 'flex';
  document.getElementById('catUploadPreview').style.display = 'none';
  if (quill) quill.setContents([]);

  /* Hide self from parent dropdown when editing */
  Array.from(document.getElementById('fCatParent').options).forEach(function(opt) {
    opt.style.display = (parseInt(opt.value) === _currentCatId && _currentCatId > 0) ? 'none' : '';
  });

  if (_currentCatId > 0) {
    document.getElementById('catModalTitle').textContent = 'Edit Category';
    document.getElementById('catModalSub').textContent   = 'Update the category details below.';
    document.getElementById('catSubmitBtn').innerHTML    = catSaveSvg + ' Update Category';

    var d = Object.values(CAT_DATA).find(function(c) { return c.id === _currentCatId; });
    if (d) {
      document.getElementById('fCatName').value        = d.name;
      document.getElementById('fCatPriority').value    = d.priority || '';
      document.getElementById('fCatExistingExt').value = d.ext;

      /* Set description in Quill */
      if (quill && d.desc) quill.clipboard.dangerouslyPasteHTML(d.desc);

      /* Set parent select */
      var pSel = document.getElementById('fCatParent');
      for (var i = 0; i < pSel.options.length; i++) {
        if (parseInt(pSel.options[i].value) === d.parent_id) { pSel.selectedIndex = i; break; }
      }

      /* Show existing image */
      if (d.ext) {
        var src = d.ext.indexOf('/') !== -1
          ? PUB_BASE + '/' + d.ext
          : '../assets/uploads/categories/' + d.id + '.' + d.ext;
        document.getElementById('catUploadThumb').src = src;
        document.getElementById('catUploadName').textContent = 'Current image';
        document.getElementById('catUploadSize').textContent = d.ext.split('/').pop();
        document.getElementById('catUploadEmpty').style.display   = 'none';
        document.getElementById('catUploadPreview').style.display = 'flex';
      }
    }
  } else {
    document.getElementById('catModalTitle').textContent = 'Add Category';
    document.getElementById('catModalSub').textContent   = 'Fill in the category details below.';
    document.getElementById('catSubmitBtn').innerHTML    = catSaveSvg + ' Save Category';
  }

  openModal('catModal');
}


/* ═══════════════════════════════════════════════════════
   DESCRIPTION VIEW MODAL
   ═══════════════════════════════════════════════════════ */
function openDescModal(name, html) {
  document.getElementById('descModalTitle').textContent = name + ' — Description';
  document.getElementById('descModalBody').innerHTML    = html || '<p style="color:var(--text-muted);">No description available.</p>';
  openModal('descModal');
}


/* ═══════════════════════════════════════════════════════
   DELETE CONFIRM
   ═══════════════════════════════════════════════════════ */
function confirmDeleteCat(catId, name) {
  document.getElementById('delCatId').value         = catId;
  document.getElementById('delCatName').textContent = name;
  openModal('deleteCatModal');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
