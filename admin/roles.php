<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'roles';
$pageTitle   = 'Roles & Permissions';

$controller  = new AdminController();

/* ── Permission checks (employee only; admin always passes) ── */
$canView   = sinelec_can('view');
$canAdd    = sinelec_can('add');
$canEdit   = sinelec_can('edit');
$canDelete = sinelec_can('delete');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

$roles    = $controller->getAllRoles();
$modules  = $controller->getModulesWithMenus();
$allPerms = $controller->getAllRolePermissions();   // [role_id][menu_id] → perms

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Roles &amp; Permissions</div>
    <div class="pg-sub">Manage roles and control what each role can access.</div>
  </div>
  <?php if ($canAdd): ?>
  <button class="btn btn--primary" onclick="openRoleModal(0)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Role
  </button>
  <?php endif; ?>
</div>

<!-- ── Search + Filter Bar ── -->
<div style="display:flex;gap:10px;margin-bottom:18px;align-items:center;flex-wrap:wrap;">
  <div style="position:relative;flex:1;min-width:220px;max-width:340px;">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="roleSearch" class="form-control" placeholder="Search roles…" style="padding-left:32px;height:36px;" oninput="filterRoles()">
  </div>
  <span style="font-size:12px;color:var(--text-muted);" id="roleCount"><?= count($roles) ?> role<?= count($roles) !== 1 ? 's' : '' ?></span>
</div>

<!-- ── Roles Table ── -->
<div class="card">
  <div class="card-body card-body--flush">
    <?php if (empty($roles)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <h3>No roles yet</h3>
      <p>Create your first role to start assigning permissions.</p>
      <?php if ($canAdd): ?><button class="btn btn--primary" onclick="openRoleModal(0)">Create First Role</button><?php endif; ?>
    </div>
    <?php else: ?>
    <table class="dt" id="rolesTable">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Role Name</th>
          <th>Code</th>
          <th style="width:80px;">Priority</th>
          <th style="width:100px;">Status</th>
          <th style="width:140px;">Menus w/ Access</th>
          <th style="width:60px;text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($roles as $i => $role):
          $roleId    = (int)($role->ROLE_ID ?? 0);
          $roleName  = htmlspecialchars($role->ROLE_NAME ?? '');
          $roleCode  = htmlspecialchars($role->ROLE_CODE ?? '');
          $rolePrio  = (int)($role->PRIORITY ?? 0);
          $roleStatus= (int)($role->STATUS ?? 1);
          $menuCount = (int)($role->MENU_COUNT ?? 0);
          $statusLabel = $roleStatus === 1 ? 'Active' : ($roleStatus === 0 ? 'Inactive' : 'Archived');
          $statusClass = $roleStatus === 1 ? 'badge--green' : ($roleStatus === 0 ? 'badge--amber' : 'badge--grey');
          $roleDesc  = htmlspecialchars($role->DESCRIPTION ?? '');
        ?>
        <tr data-search="<?= strtolower($roleName.' '.$roleCode) ?>">
          <td class="td-sm"><?= $i + 1 ?></td>
          <td>
            <strong style="color:var(--text);"><?= $roleName ?></strong>
            <?php if ($roleDesc): ?>
              <div style="font-size:11px;color:var(--text-muted);margin-top:2px;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $roleDesc ?></div>
            <?php endif; ?>
          </td>
          <td><code style="background:#f1f5f9;padding:2px 7px;border-radius:4px;font-size:11px;letter-spacing:.3px;color:#6d28d9;"><?= $roleCode ?: '—' ?></code></td>
          <td class="td-center"><?= $rolePrio ?></td>
          <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
          <td class="td-center">
            <span class="role-menus-pill">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
              <?= $menuCount ?>
            </span>
          </td>
          <td>
            <?php if ($canEdit || $canDelete): ?>
            <div class="kbm-wrap">
              <button class="kbm-btn" onclick="toggleKbm(this)" title="Actions">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                  <circle cx="12" cy="5"  r="1.7"/>
                  <circle cx="12" cy="12" r="1.7"/>
                  <circle cx="12" cy="19" r="1.7"/>
                </svg>
              </button>
              <div class="kbm-drop">
                <?php if ($canEdit): ?>
                <button class="kbm-item" onclick="closeKbm(this);openRoleModal(<?= $roleId ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <?php if ($canEdit): ?><div class="kbm-divider"></div><?php endif; ?>
                <button class="kbm-item kbm-item--danger" onclick="closeKbm(this);confirmDeleteRole(<?= $roleId ?>,<?= htmlspecialchars(json_encode($roleName),ENT_QUOTES) ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                  Delete
                </button>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; /* canEdit || canDelete */ ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>


<!-- ════════════════════════════════════════════════════
     ADD / EDIT ROLE MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="roleModal">
  <div class="modal modal--roles modal--scrollable" style="max-height:92vh;display:flex;flex-direction:column;">

    <!-- Header -->
    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div>
        <div class="modal-title" id="roleModalTitle">Add New Role</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">Create role details and assign module/menu permissions.</div>
      </div>
      <button class="modal-close" onclick="closeModal('roleModal')" style="font-size:22px;line-height:1;">×</button>
    </div>

    <!-- Scrollable body -->
    <div style="overflow-y:auto;flex:1;padding:22px;" id="roleModalBody">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=SaveRole') ?>" id="roleForm">
        <input type="hidden" name="role_id" id="fRoleId" value="0">

        <!-- Section 1: Role Information -->
        <div style="margin-bottom:22px;">
          <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Role Information
          </div>
          <div class="form-row cols-2" style="margin-bottom:12px;">
            <div class="fg">
              <label>Role Name <span class="req">*</span></label>
              <input type="text" name="role_name" id="fRoleName" class="form-control" placeholder="e.g. Sales Manager" required>
            </div>
            <div class="fg">
              <label>Role Code <span class="req">*</span></label>
              <input type="text" name="role_code" id="fRoleCode" class="form-control" placeholder="e.g. SALES_MGR" style="text-transform:uppercase;" oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9_]/g,'')">
            </div>
          </div>
          <div class="form-row cols-2" style="margin-bottom:12px;">
            <div class="fg">
              <label>Hierarchy Level</label>
              <input type="number" name="priority" id="fRolePriority" class="form-control" value="0" min="0" placeholder="0 = highest">
              <span class="fg-hint">Lower number = higher in hierarchy</span>
            </div>
            <div class="fg">
              <label>Status</label>
              <select name="status" id="fRoleStatus" class="form-control">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
          <div class="fg">
            <label>Description</label>
            <textarea name="description" id="fRoleDesc" class="form-control" rows="2" placeholder="Brief role description…"></textarea>
          </div>
        </div>

        <!-- Section 2: Module & Menu Permissions -->
        <div class="perm-section">
          <div class="perm-section-hd">
            <span style="display:flex;align-items:center;gap:7px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0110 0v3"/></svg>
              Module &amp; Menu Permissions
            </span>
            <div class="perm-section-hd-actions">
              <button type="button" class="btn btn--outline btn--sm" onclick="setAllPerms(true)">Enable All</button>
              <button type="button" class="btn btn--outline btn--sm" onclick="setAllPerms(false)">Clear All</button>
            </div>
          </div>
          <div class="perm-scroll">
            <table class="perm-table">
              <thead>
                <tr>
                  <th>Module / Menu</th>
                  <th>Enable</th>
                  <th>Add</th>
                  <th>Edit</th>
                  <th>Delete</th>
                  <th>View</th>
                </tr>
              </thead>
              <tbody id="permTableBody">
                <?php foreach ($modules as $mod): ?>
                <?php $modId = $mod['module_id']; ?>
                <!-- Module Header Row -->
                <tr class="perm-mod-row" data-mod="<?= $modId ?>">
                  <td>
                    <svg class="perm-mod-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
                    <?= htmlspecialchars($mod['module_name']) ?>
                  </td>
                  <td>
                    <label class="perm-toggle">
                      <input type="checkbox" class="mod-master" data-mod="<?= $modId ?>" onchange="toggleModule(this)">
                      <span class="perm-slider"></span>
                    </label>
                  </td>
                  <td>
                    <label class="perm-toggle">
                      <input type="checkbox" class="mod-col" data-mod="<?= $modId ?>" data-col="can_add" onchange="toggleModuleCol(this)">
                      <span class="perm-slider"></span>
                    </label>
                  </td>
                  <td>
                    <label class="perm-toggle">
                      <input type="checkbox" class="mod-col" data-mod="<?= $modId ?>" data-col="can_edit" onchange="toggleModuleCol(this)">
                      <span class="perm-slider"></span>
                    </label>
                  </td>
                  <td>
                    <label class="perm-toggle">
                      <input type="checkbox" class="mod-col" data-mod="<?= $modId ?>" data-col="can_delete" onchange="toggleModuleCol(this)">
                      <span class="perm-slider"></span>
                    </label>
                  </td>
                  <td>
                    <label class="perm-toggle">
                      <input type="checkbox" class="mod-col" data-mod="<?= $modId ?>" data-col="can_view" onchange="toggleModuleCol(this)">
                      <span class="perm-slider"></span>
                    </label>
                  </td>
                </tr>
                <!-- Menu Rows -->
                <?php foreach ($mod['menus'] as $menu): ?>
                <?php $menuId = $menu['menu_id']; ?>
                <tr class="perm-menu-row" data-mod="<?= $modId ?>" data-menu="<?= $menuId ?>">
                  <td>
                    <div class="perm-menu-name">
                      <span class="perm-menu-dot"></span>
                      <?= htmlspecialchars($menu['menu_name']) ?>
                    </div>
                  </td>
                  <td>
                    <label class="perm-toggle">
                      <input type="checkbox" class="menu-enable" data-menu="<?= $menuId ?>" data-mod="<?= $modId ?>" onchange="toggleMenuEnable(this)">
                      <span class="perm-slider"></span>
                    </label>
                  </td>
                  <td>
                    <label class="perm-toggle">
                      <input type="checkbox" name="perms[<?= $menuId ?>][can_add]" value="1" class="perm-cb" data-menu="<?= $menuId ?>" data-col="can_add" disabled onchange="onPermChange(this)">
                      <span class="perm-slider"></span>
                    </label>
                  </td>
                  <td>
                    <label class="perm-toggle">
                      <input type="checkbox" name="perms[<?= $menuId ?>][can_edit]" value="1" class="perm-cb" data-menu="<?= $menuId ?>" data-col="can_edit" disabled onchange="onPermChange(this)">
                      <span class="perm-slider"></span>
                    </label>
                  </td>
                  <td>
                    <label class="perm-toggle">
                      <input type="checkbox" name="perms[<?= $menuId ?>][can_delete]" value="1" class="perm-cb" data-menu="<?= $menuId ?>" data-col="can_delete" disabled onchange="onPermChange(this)">
                      <span class="perm-slider"></span>
                    </label>
                  </td>
                  <td>
                    <input type="hidden" name="perms[<?= $menuId ?>][can_view]" value="0" id="hv_<?= $menuId ?>">
                    <label class="perm-toggle">
                      <input type="checkbox" class="menu-view" data-menu="<?= $menuId ?>" disabled onchange="onViewChange(this)">
                      <span class="perm-slider"></span>
                    </label>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Footer buttons -->
        <div style="display:flex;gap:10px;margin-top:22px;justify-content:flex-end;">
          <button type="button" class="btn btn--outline" onclick="closeModal('roleModal')">Cancel</button>
          <button type="submit" class="btn btn--primary" id="roleSubmitBtn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Role
          </button>
        </div>

      </form>
    </div><!-- /modal body -->
  </div>
</div>


<!-- ── Delete Confirm Modal ── -->
<div class="modal-overlay" id="deleteRoleModal">
  <div class="modal modal--sm">
    <div class="modal-hd" style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <span class="modal-title">Delete Role</span>
      <button class="modal-close" onclick="closeModal('deleteRoleModal')">×</button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:20px;">
        <div style="width:38px;height:38px;border-radius:50%;background:#fff5f5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div>
          <div style="font-weight:600;margin-bottom:4px;">Are you sure?</div>
          <div style="font-size:13px;color:var(--text-muted);">You are about to delete role <strong id="delRoleName"></strong>. This action cannot be undone.</div>
        </div>
      </div>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteRole') ?>">
        <input type="hidden" name="role_id" id="delRoleId">
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button type="button" class="btn btn--outline" onclick="closeModal('deleteRoleModal')">Cancel</button>
          <button type="submit" class="btn btn--danger-solid">Delete Role</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ── Embedded Data + JS ── -->
<script>
const ALL_PERMS = <?= json_encode($allPerms, JSON_FORCE_OBJECT) ?>;
const ROLE_DATA = <?= json_encode(array_map(function($r) {
  return [
    'id'     => (int)($r->ROLE_ID ?? 0),
    'name'   => (string)($r->ROLE_NAME ?? ''),
    'code'   => (string)($r->ROLE_CODE ?? ''),
    'prio'   => (int)($r->PRIORITY ?? 0),
    'status' => (int)($r->STATUS ?? 1),
    'desc'   => (string)($r->DESCRIPTION ?? ''),
  ];
}, $roles), JSON_FORCE_OBJECT) ?>;

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function filterRoles() {
  const q = document.getElementById('roleSearch').value.toLowerCase().trim();
  let vis = 0;
  document.querySelectorAll('#rolesTable tbody tr').forEach(r => {
    const match = !q || r.dataset.search.includes(q);
    r.style.display = match ? '' : 'none';
    if (match) vis++;
  });
  document.getElementById('roleCount').textContent = vis + ' role' + (vis !== 1 ? 's' : '');
}

function resetPermTable() {
  document.querySelectorAll('.menu-enable').forEach(cb => {
    cb.checked = false;
    setMenuInteractive(cb.dataset.menu, false);
    setAllMenuPerms(cb.dataset.menu, false);
  });
  document.querySelectorAll('.mod-master,.mod-col').forEach(cb => cb.checked = false);
}

function openRoleModal(roleId) {
  resetPermTable();
  document.getElementById('fRoleId').value      = roleId || 0;
  document.getElementById('fRoleName').value    = '';
  document.getElementById('fRoleCode').value    = '';
  document.getElementById('fRolePriority').value= 0;
  document.getElementById('fRoleStatus').value  = 1;
  document.getElementById('fRoleDesc').value    = '';

  const saveSvg = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>';

  if (roleId > 0) {
    document.getElementById('roleModalTitle').textContent = 'Edit Role';
    document.getElementById('roleSubmitBtn').innerHTML    = saveSvg + ' Update Role';

    /* Fill text fields from embedded data */
    const d = Object.values(ROLE_DATA).find(r => r.id === roleId);
    if (d) {
      document.getElementById('fRoleName').value     = d.name;
      document.getElementById('fRoleCode').value     = d.code;
      document.getElementById('fRolePriority').value = d.prio;
      document.getElementById('fRoleStatus').value   = d.status;
      document.getElementById('fRoleDesc').value     = d.desc;
    }

    /* Fill permissions */
    const perms = ALL_PERMS[roleId] || {};
    Object.keys(perms).forEach(menuId => {
      const p = perms[menuId];
      if (!(p.can_view || p.can_add || p.can_edit || p.can_delete)) return;
      const enableCb = $menuEnable(menuId);
      if (!enableCb) return;
      /* Enable the menu first, making all perm inputs interactive */
      enableCb.checked = true;
      setMenuInteractive(menuId, true);
      /* Set each perm to its stored value individually */
      ['can_add','can_edit','can_delete'].forEach(col => {
        const cb = $menuPerm(menuId, col);
        if (cb) cb.checked = !!p[col];
      });
      const viewCb = $menuView(menuId);
      if (viewCb) { viewCb.checked = !!p.can_view; syncViewHidden(menuId, !!p.can_view); }
    });
    syncAllModuleMasters();

  } else {
    document.getElementById('roleModalTitle').textContent = 'Add New Role';
    document.getElementById('roleSubmitBtn').innerHTML    = saveSvg + ' Save Role';
  }

  openModal('roleModal');
  document.getElementById('roleModalBody').scrollTop = 0;
}

/* ═══════════════════════════════════════════════════════════════
   PERMISSION TOGGLE ENGINE
   ═══════════════════════════════════════════════════════════════ */

/* ── Low-level accessors ── */
function $menuEnable(menuId)     { return document.querySelector(`.menu-enable[data-menu="${menuId}"]`); }
function $menuPerm(menuId, col)  { return document.querySelector(`.perm-cb[data-menu="${menuId}"][data-col="${col}"]`); }
function $menuView(menuId)       { return document.querySelector(`.menu-view[data-menu="${menuId}"]`); }
function $modMaster(modId)       { return document.querySelector(`.mod-master[data-mod="${modId}"]`); }
function $modColMaster(modId,col){ return document.querySelector(`.mod-col[data-mod="${modId}"][data-col="${col}"]`); }
function $menuRows(modId)        { return [...document.querySelectorAll(`.perm-menu-row[data-mod="${modId}"]`)]; }

const ALL_COLS = ['can_add','can_edit','can_delete','can_view'];

/* Keep hidden can_view input in sync */
function syncViewHidden(menuId, checked) {
  const hv = document.getElementById('hv_' + menuId);
  if (hv) hv.value = checked ? 1 : 0;
}

/* ── Core: enable/disable the 4 perm inputs for a menu ──
   Separates "make interactive" from "set checked value"          */
function setMenuInteractive(menuId, enable) {
  ['can_add','can_edit','can_delete'].forEach(col => {
    const cb = $menuPerm(menuId, col);
    if (cb) cb.disabled = !enable;
  });
  const vCb = $menuView(menuId);
  if (vCb) vCb.disabled = !enable;
}

/* ── Core: set all 4 perms for a menu to a specific checked value ── */
function setAllMenuPerms(menuId, checked) {
  ['can_add','can_edit','can_delete'].forEach(col => {
    const cb = $menuPerm(menuId, col);
    if (cb) cb.checked = checked;
  });
  const vCb = $menuView(menuId);
  if (vCb) { vCb.checked = checked; syncViewHidden(menuId, checked); }
}

/* ── Check whether a menu has ANY perm currently checked ── */
function menuHasAnyPerm(menuId) {
  const hasPerm = ['can_add','can_edit','can_delete'].some(col => {
    const cb = $menuPerm(menuId, col);
    return cb && cb.checked;
  });
  const vCb = $menuView(menuId);
  return hasPerm || (vCb && vCb.checked);
}

/* ── Sync module-level masters based on actual child state ──
   master   = checked if ANY menu in module is enabled
   col_add  = checked if ALL enabled menus have that col checked  */
function syncModuleMasters(modId) {
  const rows = $menuRows(modId);

  /* Module Enable master: ON if any menu is enabled */
  const anyEnabled = rows.some(r => {
    const cb = $menuEnable(r.dataset.menu);
    return cb && cb.checked;
  });
  const masterCb = $modMaster(modId);
  if (masterCb) masterCb.checked = anyEnabled;

  /* Column masters: ON only if ALL enabled menus have that perm */
  ALL_COLS.forEach(col => {
    const colMaster = $modColMaster(modId, col);
    if (!colMaster) return;
    const enabledRows = rows.filter(r => { const cb = $menuEnable(r.dataset.menu); return cb && cb.checked; });
    if (enabledRows.length === 0) { colMaster.checked = false; return; }
    const allHave = enabledRows.every(r => {
      const menuId = r.dataset.menu;
      if (col === 'can_view') { const v = $menuView(menuId); return v && v.checked; }
      const c = $menuPerm(menuId, col); return c && c.checked;
    });
    colMaster.checked = allHave;
  });
}

function syncAllModuleMasters() {
  document.querySelectorAll('.perm-mod-row').forEach(r => syncModuleMasters(r.dataset.mod));
}

/* ═══════════════════════════════════════════════════════════════
   EVENT HANDLERS  (called from HTML onchange attributes)
   ═══════════════════════════════════════════════════════════════ */

/*
 * MODULE ENABLE master toggled
 * ON  → enable ALL menus + check ALL perms for all menus + check all col masters
 * OFF → disable ALL menus + uncheck/disable all perms + uncheck all col masters
 */
function toggleModule(cb) {
  const modId   = cb.dataset.mod;
  const checked = cb.checked;
  $menuRows(modId).forEach(row => {
    const menuId    = row.dataset.menu;
    const enableCb  = $menuEnable(menuId);
    if (!enableCb) return;
    enableCb.checked = checked;
    setMenuInteractive(menuId, checked);
    setAllMenuPerms(menuId, checked);
  });
  /* Sync column masters to match module master */
  ALL_COLS.forEach(col => {
    const cm = $modColMaster(modId, col);
    if (cm) cm.checked = checked;
  });
}

/*
 * MODULE COLUMN master (Add / Edit / Delete / View) toggled
 * ON  → for every menu in module: enable menu + check that specific perm
 * OFF → for every menu in module: uncheck that perm;
 *       if menu has NO perms left → disable menu Enable
 */
function toggleModuleCol(cb) {
  const modId   = cb.dataset.mod;
  const col     = cb.dataset.col;
  const checked = cb.checked;

  $menuRows(modId).forEach(row => {
    const menuId   = row.dataset.menu;
    const enableCb = $menuEnable(menuId);
    if (!enableCb) return;

    if (checked) {
      /* Enable the menu and make its perms interactive */
      if (!enableCb.checked) {
        enableCb.checked = true;
        setMenuInteractive(menuId, true);
      }
      /* Set only this specific column */
      if (col === 'can_view') {
        const vCb = $menuView(menuId);
        if (vCb) { vCb.checked = true; syncViewHidden(menuId, true); }
      } else {
        const pCb = $menuPerm(menuId, col);
        if (pCb) pCb.checked = true;
      }
    } else {
      /* Uncheck only this column */
      if (col === 'can_view') {
        const vCb = $menuView(menuId);
        if (vCb) { vCb.checked = false; syncViewHidden(menuId, false); }
      } else {
        const pCb = $menuPerm(menuId, col);
        if (pCb) pCb.checked = false;
      }
      /* If menu has no perms left at all, disable the menu Enable */
      if (!menuHasAnyPerm(menuId)) {
        enableCb.checked = false;
        setMenuInteractive(menuId, false);
      }
    }
  });

  /* Sync module Enable master */
  syncModuleMasters(modId);
}

/*
 * MENU ENABLE toggled
 * ON  → enable perm inputs + check ALL perms (Add/Edit/Delete/View)
 * OFF → uncheck/disable all perms
 * Either way → sync module masters
 */
function toggleMenuEnable(cb) {
  const menuId = cb.dataset.menu;
  const modId  = cb.dataset.mod;
  setMenuInteractive(menuId, cb.checked);
  if (cb.checked) {
    setAllMenuPerms(menuId, true);
  } else {
    setAllMenuPerms(menuId, false);
  }
  syncModuleMasters(modId);
}

/*
 * INDIVIDUAL PERMISSION toggled (Add / Edit / Delete)
 * ON  → auto-enable this menu's Enable (make interactive, don't touch other perms)
 *       sync module masters
 * OFF → if no perms remain on this menu → disable menu Enable
 *       sync module masters
 */
function onPermChange(cb) {
  const menuId = cb.dataset.menu;
  const modId  = document.querySelector(`.perm-menu-row[data-menu="${menuId}"]`)?.dataset.mod;
  const enableCb = $menuEnable(menuId);
  if (!enableCb) return;

  if (cb.checked) {
    /* Enable menu if not already */
    if (!enableCb.checked) {
      enableCb.checked = true;
      setMenuInteractive(menuId, true);
      /* do NOT auto-check other perms — only this one */
    }
  } else {
    /* If no perms remain, disable the menu */
    if (!menuHasAnyPerm(menuId)) {
      enableCb.checked = false;
      setMenuInteractive(menuId, false);
    }
  }
  if (modId) syncModuleMasters(modId);
}

/*
 * VIEW toggle (can_view)
 * ON  → auto-enable menu Enable; sync hidden + module masters
 * OFF → sync hidden; if no perms remain → disable menu Enable
 */
function onViewChange(cb) {
  const menuId   = cb.dataset.menu;
  const modId    = document.querySelector(`.perm-menu-row[data-menu="${menuId}"]`)?.dataset.mod;
  const enableCb = $menuEnable(menuId);
  syncViewHidden(menuId, cb.checked);

  if (cb.checked) {
    if (enableCb && !enableCb.checked) {
      enableCb.checked = true;
      setMenuInteractive(menuId, true);
    }
  } else {
    if (enableCb && !menuHasAnyPerm(menuId)) {
      enableCb.checked = false;
      setMenuInteractive(menuId, false);
    }
  }
  if (modId) syncModuleMasters(modId);
}

/* ── Enable All / Clear All buttons ── */
function setAllPerms(enable) {
  document.querySelectorAll('.perm-mod-row').forEach(modRow => {
    const masterCb = modRow.querySelector('.mod-master');
    if (masterCb) {
      masterCb.checked = enable;
      toggleModule(masterCb);
    }
  });
}

/* ── Before submit: ensure hidden can_view is 0 for disabled menus ── */
document.getElementById('roleForm').addEventListener('submit', function() {
  document.querySelectorAll('.menu-enable').forEach(cb => {
    if (!cb.checked) syncViewHidden(cb.dataset.menu, false);
  });
});

/* ── Delete role ── */
function confirmDeleteRole(roleId, roleName) {
  document.getElementById('delRoleId').value    = roleId;
  document.getElementById('delRoleName').textContent = roleName;
  openModal('deleteRoleModal');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
