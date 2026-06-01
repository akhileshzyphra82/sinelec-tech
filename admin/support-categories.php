<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/support_controller.php';

$currentPage = 'support-categories';
$pageTitle   = 'Support Categories';

$canView   = sinelec_can('view');
$canAdd    = sinelec_can('add');
$canEdit   = sinelec_can('edit');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

$ctrl       = new SupportController();
$categories = $ctrl->getAllCategoriesAdmin();
$flash      = sinelec_consume_flash();

$catTypes = ['Return & Refund', 'Return & Replacement', 'Payment Issue', 'Other'];

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Support Categories</div>
    <div class="pg-sub">Manage ticket categories and types shown to customers.</div>
  </div>
  <?php if ($canAdd): ?>
  <button class="btn btn--primary" onclick="scOpenModal(0)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Category
  </button>
  <?php endif; ?>
</div>

<?php if ($flash): ?>
<div class="alert alert--<?= $flash['type'] === 'ok' ? 'success' : 'error' ?>" style="margin-bottom:16px">
  <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<div id="scAjaxMsg" style="display:none;margin-bottom:14px" class="alert"></div>

<div class="card">
  <?php if (empty($categories)): ?>
  <div class="card-body">
    <div class="empty-state">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
      <h3>No categories yet</h3>
      <p>Add your first support category to get started.</p>
      <?php if ($canAdd): ?><button class="btn btn--primary" onclick="scOpenModal(0)">Add Category</button><?php endif; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="data-table" id="scTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Category Name</th>
          <th>Type</th>
          <th>Sort Order</th>
          <th>Status</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $i => $cat):
          $catId     = (int)(float)($cat->CATEGORY_ID ?? 0);
          $name      = htmlspecialchars((string)($cat->CATEGORY_NAME ?? ''));
          $type      = (string)($cat->CATEGORY_TYPE ?? 'Other');
          $sortOrder = (int)($cat->SORT_ORDER ?? 0);
          $isActive  = (int)($cat->IS_ACTIVE ?? 1);
          $createdAt = (string)($cat->CREATED_AT ?? '');
          $ts        = strtotime($createdAt);
          $dateFmt   = $ts ? date('M d, Y', $ts) : '—';

          $typeColors = [
            'Return & Refund'      => 'background:#f3e8ff;color:#7c3aed',
            'Return & Replacement' => 'background:#ede9fe;color:#6d28d9',
            'Payment Issue'        => 'background:#fef3c7;color:#92400e',
            'Other'                => 'background:#dbeafe;color:#1d4ed8',
          ];
          $typeStyle = $typeColors[$type] ?? 'background:#f1f5f9;color:#64748b';
        ?>
        <tr id="scRow<?= $catId ?>">
          <td style="color:#64748b;font-size:13px"><?= $i + 1 ?></td>
          <td>
            <span class="sc-name" id="scName<?= $catId ?>"><?= $name ?></span>
          </td>
          <td>
            <span style="<?= $typeStyle ?>;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px">
              <?= htmlspecialchars($type) ?>
            </span>
          </td>
          <td style="font-size:13px;color:#64748b"><?= $sortOrder ?></td>
          <td>
            <label class="sc-toggle" title="<?= $isActive ? 'Active — click to deactivate' : 'Inactive — click to activate' ?>">
              <input type="checkbox" <?= $isActive ? 'checked' : '' ?>
                     onchange="scToggle(<?= $catId ?>, this.checked ? 1 : 0)"
                     <?= $canEdit ? '' : 'disabled' ?>>
              <span class="sc-toggle-track"></span>
            </label>
          </td>
          <td style="font-size:13px;color:#64748b"><?= $dateFmt ?></td>
          <td>
            <?php if ($canEdit): ?>
            <button class="btn btn--sm btn--ghost" onclick="scOpenModal(<?= $catId ?>)" title="Edit">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit
            </button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="scModalOverlay" style="display:none" onclick="if(event.target===this)scCloseModal()"></div>
<div class="modal" id="scModal" style="display:none;max-width:480px">
  <div class="modal-header">
    <h3 id="scModalTitle">Add Category</h3>
    <button class="modal-close" onclick="scCloseModal()">&times;</button>
  </div>
  <div class="modal-body">
    <form id="scForm">
      <input type="hidden" id="scCatId" value="0">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Category Name <span class="req">*</span></label>
          <input type="text" id="scCatName" class="form-control" placeholder="e.g. Return & Refund" maxlength="80">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Category Type <span class="req">*</span></label>
          <select id="scCatType" class="form-control">
            <?php foreach ($catTypes as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input type="number" id="scSortOrder" class="form-control" value="0" min="0" max="999">
        </div>
      </div>
      <div id="scFormErr" class="alert alert--error" style="display:none;margin-top:10px"></div>
    </form>
  </div>
  <div class="modal-footer">
    <button class="btn btn--ghost" onclick="scCloseModal()">Cancel</button>
    <button class="btn btn--primary" id="scSaveBtn" onclick="scSave()">Save Category</button>
  </div>
</div>

<!-- Inline data for JS -->
<script>
var _scCategories = <?= json_encode(array_map(fn($c) => [
    'category_id'   => (int)(float)($c->CATEGORY_ID ?? 0),
    'category_name' => (string)($c->CATEGORY_NAME ?? ''),
    'category_type' => (string)($c->CATEGORY_TYPE ?? 'Other'),
    'sort_order'    => (int)($c->SORT_ORDER ?? 0),
    'is_active'     => (int)($c->IS_ACTIVE ?? 1),
], $categories)) ?>;
</script>

<style>
.sc-toggle { position:relative; display:inline-block; width:38px; height:22px; cursor:pointer; }
.sc-toggle input { opacity:0; width:0; height:0; }
.sc-toggle-track { position:absolute; inset:0; background:#d1d5db; border-radius:20px; transition:.2s; }
.sc-toggle-track::after { content:''; position:absolute; width:16px; height:16px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; }
.sc-toggle input:checked + .sc-toggle-track { background:#16a34a; }
.sc-toggle input:checked + .sc-toggle-track::after { transform:translateX(16px); }
</style>

<script>
/* ── Modal open/close ── */
function scOpenModal(catId) {
    document.getElementById('scFormErr').style.display = 'none';
    if (catId === 0) {
        document.getElementById('scModalTitle').textContent = 'Add Category';
        document.getElementById('scCatId').value    = '0';
        document.getElementById('scCatName').value  = '';
        document.getElementById('scCatType').value  = 'Other';
        document.getElementById('scSortOrder').value = '0';
    } else {
        var cat = _scCategories.find(function(c){ return c.category_id == catId; });
        if (!cat) return;
        document.getElementById('scModalTitle').textContent = 'Edit Category';
        document.getElementById('scCatId').value    = cat.category_id;
        document.getElementById('scCatName').value  = cat.category_name;
        document.getElementById('scCatType').value  = cat.category_type;
        document.getElementById('scSortOrder').value = cat.sort_order;
    }
    document.getElementById('scModalOverlay').style.display = '';
    document.getElementById('scModal').style.display = '';
    setTimeout(function(){ document.getElementById('scCatName').focus(); }, 80);
}

function scCloseModal() {
    document.getElementById('scModalOverlay').style.display = 'none';
    document.getElementById('scModal').style.display = 'none';
}

/* ── Save category ── */
async function scSave() {
    var errEl   = document.getElementById('scFormErr');
    errEl.style.display = 'none';
    var catId   = parseInt(document.getElementById('scCatId').value) || 0;
    var name    = document.getElementById('scCatName').value.trim();
    var type    = document.getElementById('scCatType').value;
    var sort    = parseInt(document.getElementById('scSortOrder').value) || 0;

    if (!name) { errEl.textContent = 'Category name is required.'; errEl.style.display = ''; return; }

    var btn = document.getElementById('scSaveBtn');
    btn.disabled = true; btn.textContent = 'Saving…';

    try {
        var res  = await fetch('ajax/support?action=save_category', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ category_id: catId, category_name: name, category_type: type, sort_order: sort })
        });
        var data = await res.json();
        if (!data.ok) { errEl.textContent = data.msg || 'Save failed.'; errEl.style.display = ''; }
        else { scCloseModal(); location.reload(); }
    } catch(e) {
        errEl.textContent = 'Network error.';
        errEl.style.display = '';
    } finally {
        btn.disabled = false; btn.textContent = 'Save Category';
    }
}

/* ── Toggle active ── */
async function scToggle(catId, val) {
    try {
        var res  = await fetch('ajax/support?action=toggle_category', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ category_id: catId, is_active: val })
        });
        var data = await res.json();
        if (!data.ok) {
            var msg = document.getElementById('scAjaxMsg');
            msg.textContent = data.msg || 'Failed to update.';
            msg.className = 'alert alert--error';
            msg.style.display = '';
            setTimeout(function(){ msg.style.display = 'none'; }, 4000);
            location.reload(); // revert toggle state
        }
    } catch(e) { location.reload(); }
}

/* ESC key */
document.addEventListener('keydown', function(e){ if(e.key==='Escape') scCloseModal(); });
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
