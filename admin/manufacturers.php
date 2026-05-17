<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'manufacturers';
$pageTitle   = 'Manufacturers';

$controller = new AdminController();

$canView   = sinelec_can('view');
$canAdd    = sinelec_can('add');
$canEdit   = sinelec_can('edit');
$canDelete = sinelec_can('delete');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

$manufacturers = $controller->getAllManufacturers();
$allCats       = $controller->getAllCategories();
$countries     = $controller->getAllCountries();
$pubBase       = rtrim(sinelec_env('PUBLIC_BASE_URL'), '/');

/* Build category lookup map [id => name] */
$catMap = [];
foreach ($allCats as $c) {
    $catId = (int)(float)($c->PRODUCT_CATEGORY_ID ?? 0);
    if ($catId > 0) $catMap[$catId] = (string)($c->PRODUCT_CATEGORY_NAME ?? '');
}

/* Status label helper */
$statusLabels = [1 => 'Active', 0 => 'Inactive', 2 => 'Archived'];
$statusColors = [
    1 => 'background:#dcfce7;color:#15803d;',
    0 => 'background:#fee2e2;color:#dc2626;',
    2 => 'background:#f1f5f9;color:#64748b;',
];

ob_start();
?>

<!-- Quill CSS -->
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<div class="pg-header">
  <div>
    <div class="pg-title">Manufacturers</div>
    <div class="pg-sub">Manage product manufacturers and brands.</div>
  </div>
  <?php if ($canAdd): ?>
  <button class="btn btn--primary" onclick="openMfrModal(0)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Manufacturer
  </button>
  <?php endif; ?>
</div>

<!-- Search + Filter -->
<div style="display:flex;gap:10px;margin-bottom:18px;align-items:center;flex-wrap:wrap;">
  <div style="position:relative;flex:1;min-width:220px;max-width:340px;">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="mfrSearch" class="form-control" placeholder="Search manufacturers…" style="padding-left:32px;height:36px;" oninput="mfrOnSearch()">
  </div>
  <select id="mfrStatusFilter" class="form-control" style="height:36px;width:auto;min-width:130px;" onchange="mfrOnSearch()">
    <option value="">All Status</option>
    <option value="1">Active</option>
    <option value="0">Inactive</option>
    <option value="2">Archived</option>
  </select>
</div>

<!-- Table card -->
<div class="card">
  <?php if (empty($manufacturers)): ?>
  <div class="card-body">
    <div class="empty-state">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
      <h3>No manufacturers found</h3>
      <p>Add your first manufacturer to get started.</p>
      <?php if ($canAdd): ?><button class="btn btn--primary" onclick="openMfrModal(0)">Add Manufacturer</button><?php endif; ?>
    </div>
  </div>
  <?php else: ?>

  <!-- ── Pagination Bar ── -->
  <div class="emp-pgbar" id="mfrPgBar">
    <div class="emp-pgbar-info" id="mfrPgInfo">Showing 1–10 of <?= count($manufacturers) ?> records</div>
    <div class="emp-pgbar-right">
      <span class="emp-pgbar-rpp-label">Records per page</span>
      <select id="mfrRpp" class="emp-pgbar-rpp-sel">
        <option value="10" selected>10</option>
        <option value="20">20</option>
        <option value="30">30</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <button class="emp-pgbar-apply" onclick="mfrApplyRpp()">Apply</button>
      <div class="emp-pgbar-nav" id="mfrNav"></div>
    </div>
  </div>

  <div class="card-body card-body--flush">
    <table class="dt" id="mfrTable">
      <thead>
        <tr>
          <th style="width:44px;">S.No.</th>
          <th style="width:58px;">Logo</th>
          <th>Name</th>
          <th style="width:140px;">Country</th>
          <th>Categories</th>
          <th style="width:90px;text-align:center;">Status</th>
          <th style="width:60px;text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody id="mfrTbody">
        <?php foreach ($manufacturers as $i => $mfr):
          $mid        = (int)($mfr->MANUFACTURER_ID ?? 0);
          $mName      = (string)($mfr->NAME ?? '');
          $logoKey    = (string)($mfr->LOGO ?? '');
          $countryId  = (int)($mfr->COUNTRY_ID ?? 0);
          $countryName= (string)($mfr->COUNTRY_NAME ?? '');
          $desc       = (string)($mfr->DESCRIPTION ?? '');
          $catIdsStr  = (string)($mfr->PRODUCT_CATEGORY_IDS ?? '');
          $status     = (int)($mfr->STATUS ?? 1);
          $logoUrl    = $logoKey !== '' ? $pubBase.'/'.$logoKey : '';
          $initial    = strtoupper(substr(trim($mName), 0, 1)) ?: '?';

          /* Resolve category names */
          $catNames = [];
          if ($catIdsStr !== '') {
              foreach (explode(',', $catIdsStr) as $cid) {
                  $cid = (int)trim($cid);
                  if ($cid > 0 && isset($catMap[$cid])) $catNames[] = $catMap[$cid];
              }
          }
          $catDisplay = !empty($catNames) ? implode(', ', $catNames) : '—';
          $statusLabel = $statusLabels[$status] ?? 'Active';
          $statusStyle = $statusColors[$status] ?? $statusColors[1];
        ?>
        <tr class="mfr-row"
            data-search="<?= htmlspecialchars(strtolower($mName)) ?>"
            data-status="<?= $status ?>"
            data-seq="<?= $i + 1 ?>">
          <td class="td-sm mfr-sno"><?= $i + 1 ?></td>
          <td>
            <?php if ($logoUrl): ?>
              <img src="<?= htmlspecialchars($logoUrl) ?>" alt="" class="mfr-thumb">
            <?php else: ?>
              <div class="mfr-initial" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);"><?= htmlspecialchars($initial) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600;color:var(--text);font-size:13px;"><?= htmlspecialchars($mName) ?></div>
          </td>
          <td>
            <span style="font-size:13px;color:var(--text-muted);">
              <?= $countryName !== '' ? htmlspecialchars($countryName) : '<span style="color:var(--text-muted);">—</span>' ?>
            </span>
          </td>
          <td>
            <span style="font-size:12px;color:var(--text-muted);line-height:1.5;" title="<?= htmlspecialchars($catDisplay) ?>">
              <?php
                if (!empty($catNames)) {
                    // Show max 2 + count of rest
                    $shown = array_slice($catNames, 0, 2);
                    $rest  = count($catNames) - count($shown);
                    echo htmlspecialchars(implode(', ', $shown));
                    if ($rest > 0) echo ' <span style="background:#eef2ff;color:#4f46e5;border-radius:10px;padding:1px 7px;font-size:11px;font-weight:600;">+' . $rest . '</span>';
                } else {
                    echo '—';
                }
              ?>
            </span>
          </td>
          <td style="text-align:center;">
            <span style="<?= $statusStyle ?>font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;display:inline-block;">
              <?= htmlspecialchars($statusLabel) ?>
            </span>
          </td>
          <td>
            <?php if ($canEdit || $canDelete): ?>
            <div class="kbm-wrap">
              <button class="kbm-btn" onclick="toggleKbm(this)" title="Actions">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
              </button>
              <div class="kbm-drop">
                <?php if ($canEdit): ?>
                <button class="kbm-item" onclick="closeKbm(this);openMfrModal(<?= $mid ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </button>
                <?php endif; ?>
                <?php if ($desc): ?>
                <button class="kbm-item" onclick="closeKbm(this);openMfrDescModal(<?= htmlspecialchars(json_encode($mName), ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($desc), ENT_QUOTES) ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                  View Description
                </button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <?php if ($canEdit || $desc): ?><div class="kbm-divider"></div><?php endif; ?>
                <button class="kbm-item kbm-item--danger" onclick="closeKbm(this);confirmDeleteMfr(<?= $mid ?>,<?= htmlspecialchars(json_encode($mName), ENT_QUOTES) ?>)">
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
    <div id="mfrNoResults" style="display:none;padding:40px;text-align:center;color:var(--text-muted);font-size:13px;">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto 10px;opacity:.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      No manufacturers match your search.
    </div>
  </div>
  <?php endif; ?>
</div>


<!-- ════════════════════════════════════════════════════
     ADD / EDIT MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="mfrModal">
  <div class="modal" style="max-width:720px;max-height:94vh;display:flex;flex-direction:column;">

    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div>
        <div class="modal-title" id="mfrModalTitle">Add Manufacturer</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="mfrModalSub">Fill in the manufacturer details below.</div>
      </div>
      <button class="modal-close" onclick="closeModal('mfrModal')" style="font-size:22px;line-height:1;">×</button>
    </div>

    <div style="overflow-y:auto;flex:1;padding:22px;">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=SaveManufacturer') ?>" id="mfrForm" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="manufacturer_id" id="fMfrId" value="0">
        <input type="hidden" name="existing_logo"   id="fMfrExistingLogo" value="">
        <input type="hidden" name="description"     id="fMfrDescHidden">

        <!-- Name + Country -->
        <div class="form-row cols-2" style="margin-bottom:14px;">
          <div class="fg">
            <label>Manufacturer Name <span class="req">*</span></label>
            <input type="text" name="name" id="fMfrName" class="form-control" placeholder="e.g. Siemens" required>
          </div>
          <div class="fg">
            <label>Country</label>
            <select name="country_id" id="fMfrCountry" class="form-control">
              <option value="0">— Select Country —</option>
              <?php foreach ($countries as $ctr): ?>
              <option value="<?= (int)($ctr->COUNTRY_ID ?? 0) ?>">
                <?= htmlspecialchars((string)($ctr->COUNTRY ?? '')) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Status + Logo -->
        <div class="form-row cols-2" style="margin-bottom:14px;">
          <div class="fg">
            <label>Status</label>
            <select name="status" id="fMfrStatus" class="form-control">
              <option value="1">Active</option>
              <option value="0">Inactive</option>
              <option value="2">Archived</option>
            </select>
          </div>
          <div class="fg">
            <label>Logo <span style="font-size:11px;color:var(--text-muted);font-weight:400;">(jpg, png, webp — max 5 MB)</span></label>
            <div class="mfr-upload-zone" id="mfrUploadZone" onclick="document.getElementById('fMfrLogo').click()">
              <input type="file" name="manufacturer_logo" id="fMfrLogo" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" onchange="mfrOnFileSelect(this)">
              <div id="mfrUploadEmpty">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <span style="font-size:12px;color:#9ca3af;margin-top:4px;">Click to upload logo</span>
              </div>
              <div id="mfrUploadPreview" style="display:none;pointer-events:none;">
                <img id="mfrUploadThumb" src="" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:6px;border:1px solid var(--border);flex-shrink:0;">
                <div style="flex:1;min-width:0;text-align:left;">
                  <div id="mfrUploadName" style="font-size:12px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                  <div id="mfrUploadSize" style="font-size:11px;color:var(--text-muted);margin-top:1px;"></div>
                </div>
                <button type="button" onclick="event.stopPropagation();mfrRemoveLogo()" style="background:none;border:none;cursor:pointer;color:#9ca3af;padding:4px;pointer-events:all;" title="Remove">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Product Categories (checkboxes) -->
        <?php if (!empty($catMap)): ?>
        <div class="fg" style="margin-bottom:14px;">
          <label>Product Categories <span style="font-size:11px;color:var(--text-muted);font-weight:400;">(select all that apply)</span></label>
          <div class="mfr-cat-checkboxes" id="mfrCatCheckboxes">
            <?php foreach ($catMap as $catId => $catName): ?>
            <label class="mfr-cat-check-label">
              <input type="checkbox" name="product_category_ids[]" value="<?= $catId ?>" class="mfr-cat-cb">
              <?= htmlspecialchars($catName) ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Description — Quill editor -->
        <div class="fg" style="margin-bottom:20px;">
          <label>Description</label>
          <div id="mfrDescEditor" style="min-height:160px;border-radius:0 0 6px 6px;font-size:13px;"></div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:14px;border-top:1px solid var(--border);">
          <button type="button" class="btn btn--outline" onclick="closeModal('mfrModal')">Cancel</button>
          <button type="submit" class="btn btn--primary" id="mfrSubmitBtn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Manufacturer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════════════════
     DESCRIPTION VIEW MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="mfrDescModal">
  <div class="modal" style="max-width:600px;max-height:85vh;display:flex;flex-direction:column;">
    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div class="modal-title" id="mfrDescModalTitle">Description</div>
      <button class="modal-close" onclick="closeModal('mfrDescModal')" style="font-size:22px;line-height:1;">×</button>
    </div>
    <div style="overflow-y:auto;flex:1;padding:22px;">
      <div id="mfrDescModalBody" class="cat-desc-view"></div>
    </div>
  </div>
</div>


<!-- ── Delete Confirm Modal ── -->
<div class="modal-overlay" id="deleteMfrModal">
  <div class="modal modal--sm">
    <div class="modal-hd" style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <span class="modal-title">Delete Manufacturer</span>
      <button class="modal-close" onclick="closeModal('deleteMfrModal')">×</button>
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
          <div style="font-size:13px;color:var(--text-muted);">Delete <strong id="delMfrName"></strong>? This action cannot be undone.</div>
        </div>
      </div>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteManufacturer') ?>">
        <input type="hidden" name="manufacturer_id" id="delMfrId">
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button type="button" class="btn btn--outline" onclick="closeModal('deleteMfrModal')">Cancel</button>
          <button type="submit" class="btn btn--danger-solid">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ── Scoped CSS ── -->
<style>
.mfr-thumb { width:42px;height:42px;object-fit:cover;border-radius:8px;border:1px solid var(--border); }
.mfr-initial { width:42px;height:42px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;font-weight:700; }

.mfr-upload-zone { border:1.5px dashed var(--border);border-radius:8px;cursor:pointer;transition:border-color .15s;min-height:64px;display:flex;align-items:center;justify-content:center;padding:8px 12px;gap:10px; }
.mfr-upload-zone:hover { border-color:var(--primary);background:#f8f9ff; }
#mfrUploadEmpty { display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px; }
#mfrUploadPreview { display:flex;align-items:center;gap:10px;width:100%; }

.mfr-cat-checkboxes { display:flex;flex-wrap:wrap;gap:8px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:#fafbfc;max-height:160px;overflow-y:auto; }
.mfr-cat-check-label { display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--text);cursor:pointer;padding:3px 8px;border-radius:6px;border:1px solid var(--border);background:#fff;transition:all .15s;white-space:nowrap; }
.mfr-cat-check-label:hover { border-color:var(--primary);background:#eef2ff;color:#4f46e5; }
.mfr-cat-check-label input { accent-color:var(--primary);cursor:pointer; }

/* Pagination bar */
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
</style>


<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<!-- ── Embedded Data + JS ── -->
<script>
const MFR_DATA = <?= json_encode(array_map(function($m) {
    return [
        'id'         => (int)($m->MANUFACTURER_ID ?? 0),
        'name'       => (string)($m->NAME ?? ''),
        'logo'       => (string)($m->LOGO ?? ''),
        'country_id' => (int)($m->COUNTRY_ID ?? 0),
        'desc'       => (string)($m->DESCRIPTION ?? ''),
        'cat_ids'    => (string)($m->PRODUCT_CATEGORY_IDS ?? ''),
        'status'     => (int)($m->STATUS ?? 1),
    ];
}, $manufacturers), JSON_FORCE_OBJECT) ?>;

const MFR_PUB_BASE = <?= json_encode($pubBase) ?>;

/* ═══════════════════════════════════════════════════════
   QUILL EDITOR
   ═══════════════════════════════════════════════════════ */
var mfrQuill = null;
document.addEventListener('DOMContentLoaded', function () {
  mfrQuill = new Quill('#mfrDescEditor', {
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

document.getElementById('mfrForm').addEventListener('submit', function () {
  var html = mfrQuill ? mfrQuill.root.innerHTML : '';
  if (html === '<p><br></p>') html = '';
  document.getElementById('fMfrDescHidden').value = html;
});


/* ═══════════════════════════════════════════════════════
   PAGINATION + SEARCH + STATUS FILTER
   ═══════════════════════════════════════════════════════ */
(function () {
  var allRows  = [];
  var filtered = [];
  var curPage  = 1;
  var rpp      = 10;

  function init() {
    allRows  = Array.from(document.querySelectorAll('#mfrTbody .mfr-row'));
    filtered = allRows.slice();
    render();
  }

  window.mfrOnSearch = function () {
    var q      = document.getElementById('mfrSearch').value.toLowerCase().trim();
    var stFilt = document.getElementById('mfrStatusFilter').value;
    filtered = allRows.filter(function (r) {
      var nameMatch   = !q || r.dataset.search.includes(q);
      var statusMatch = stFilt === '' || r.dataset.status === stFilt;
      return nameMatch && statusMatch;
    });
    curPage = 1;
    render();
  };

  window.mfrApplyRpp = function () {
    rpp     = parseInt(document.getElementById('mfrRpp').value, 10) || 10;
    curPage = 1;
    render();
  };

  window.mfrGoPage = function (p) {
    curPage = p;
    render();
  };

  function render() {
    var total = filtered.length;
    var pages = Math.max(1, Math.ceil(total / rpp));
    curPage   = Math.min(curPage, pages);
    var start = (curPage - 1) * rpp;
    var end   = Math.min(start + rpp, total);

    allRows.forEach(function (r) { r.style.display = 'none'; });
    filtered.slice(start, end).forEach(function (r, idx) {
      r.style.display = '';
      var sno = r.querySelector('.mfr-sno');
      if (sno) sno.textContent = start + idx + 1;
    });

    var noRes = document.getElementById('mfrNoResults');
    if (noRes) noRes.style.display = total === 0 ? 'block' : 'none';

    var info = document.getElementById('mfrPgInfo');
    if (info) {
      info.textContent = total === 0
        ? 'No records found'
        : 'Showing ' + (start + 1) + '–' + end + ' of ' + total + ' records';
    }

    renderNav(curPage, pages);
  }

  function renderNav(cur, pages) {
    var nav = document.getElementById('mfrNav');
    if (!nav) return;
    var html = '';
    html += '<button class="pg-btn" onclick="mfrGoPage(' + (cur - 1) + ')"' + (cur <= 1 ? ' disabled' : '') + '>Prev</button>';
    buildPageNums(cur, pages).forEach(function (p) {
      if (p === '...') {
        html += '<span class="pg-dots">…</span>';
      } else {
        html += '<button class="pg-btn' + (p === cur ? ' pg-active' : '') + '" onclick="mfrGoPage(' + p + ')">' + p + '</button>';
      }
    });
    html += '<button class="pg-btn" onclick="mfrGoPage(' + (cur + 1) + ')"' + (cur >= pages ? ' disabled' : '') + '>Next</button>';
    nav.innerHTML = html;
  }

  function buildPageNums(cur, pages) {
    if (pages <= 7) {
      var a = [];
      for (var i = 1; i <= pages; i++) a.push(i);
      return a;
    }
    if (cur <= 4) return [1, 2, 3, 4, 5, '...', pages];
    if (cur >= pages - 3) return [1, '...', pages - 4, pages - 3, pages - 2, pages - 1, pages];
    return [1, '...', cur - 1, cur, cur + 1, '...', pages];
  }

  document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', init)
    : init();
})();


/* ═══════════════════════════════════════════════════════
   LOGO UPLOAD WIDGET
   ═══════════════════════════════════════════════════════ */
function mfrOnFileSelect(input) {
  if (!input.files || !input.files[0]) return;
  var file = input.files[0];
  var reader = new FileReader();
  reader.onload = function (e) {
    document.getElementById('mfrUploadThumb').src = e.target.result;
  };
  reader.readAsDataURL(file);
  document.getElementById('mfrUploadName').textContent = file.name;
  document.getElementById('mfrUploadSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
  document.getElementById('mfrUploadEmpty').style.display   = 'none';
  document.getElementById('mfrUploadPreview').style.display = 'flex';
}

function mfrRemoveLogo() {
  document.getElementById('fMfrLogo').value         = '';
  document.getElementById('fMfrExistingLogo').value = '';
  document.getElementById('mfrUploadThumb').src     = '';
  document.getElementById('mfrUploadEmpty').style.display   = 'flex';
  document.getElementById('mfrUploadPreview').style.display = 'none';
}


/* ═══════════════════════════════════════════════════════
   ADD / EDIT MODAL
   ═══════════════════════════════════════════════════════ */
var _currentMfrId = 0;
var mfrSaveSvg = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>';

function openMfrModal(mfrId) {
  _currentMfrId = mfrId || 0;

  /* Reset form */
  document.getElementById('fMfrId').value           = _currentMfrId;
  document.getElementById('fMfrExistingLogo').value = '';
  document.getElementById('fMfrName').value         = '';
  document.getElementById('fMfrCountry').value      = '0';
  document.getElementById('fMfrStatus').value       = '1';
  document.getElementById('fMfrLogo').value         = '';
  document.getElementById('mfrUploadEmpty').style.display   = 'flex';
  document.getElementById('mfrUploadPreview').style.display = 'none';
  if (mfrQuill) mfrQuill.setContents([]);

  /* Uncheck all categories */
  document.querySelectorAll('.mfr-cat-cb').forEach(function (cb) { cb.checked = false; });

  if (_currentMfrId > 0) {
    document.getElementById('mfrModalTitle').textContent = 'Edit Manufacturer';
    document.getElementById('mfrModalSub').textContent   = 'Update the manufacturer details below.';
    document.getElementById('mfrSubmitBtn').innerHTML    = mfrSaveSvg + ' Update Manufacturer';

    var d = Object.values(MFR_DATA).find(function (m) { return m.id === _currentMfrId; });
    if (d) {
      document.getElementById('fMfrName').value         = d.name;
      document.getElementById('fMfrExistingLogo').value = d.logo;
      document.getElementById('fMfrStatus').value       = String(d.status);

      /* Set description in Quill */
      if (mfrQuill && d.desc) mfrQuill.clipboard.dangerouslyPasteHTML(d.desc);

      /* Set country */
      var cSel = document.getElementById('fMfrCountry');
      for (var i = 0; i < cSel.options.length; i++) {
        if (parseInt(cSel.options[i].value) === d.country_id) { cSel.selectedIndex = i; break; }
      }

      /* Tick category checkboxes */
      if (d.cat_ids) {
        var ids = d.cat_ids.split(',').map(function (v) { return v.trim(); });
        document.querySelectorAll('.mfr-cat-cb').forEach(function (cb) {
          cb.checked = ids.indexOf(cb.value) !== -1;
        });
      }

      /* Show existing logo */
      if (d.logo) {
        var src = MFR_PUB_BASE + '/' + d.logo;
        document.getElementById('mfrUploadThumb').src = src;
        document.getElementById('mfrUploadName').textContent = 'Current logo';
        document.getElementById('mfrUploadSize').textContent = d.logo.split('/').pop();
        document.getElementById('mfrUploadEmpty').style.display   = 'none';
        document.getElementById('mfrUploadPreview').style.display = 'flex';
      }
    }
  } else {
    document.getElementById('mfrModalTitle').textContent = 'Add Manufacturer';
    document.getElementById('mfrModalSub').textContent   = 'Fill in the manufacturer details below.';
    document.getElementById('mfrSubmitBtn').innerHTML    = mfrSaveSvg + ' Save Manufacturer';
  }

  openModal('mfrModal');
}


/* ═══════════════════════════════════════════════════════
   DESCRIPTION VIEW MODAL
   ═══════════════════════════════════════════════════════ */
function openMfrDescModal(name, html) {
  document.getElementById('mfrDescModalTitle').textContent = name + ' — Description';
  document.getElementById('mfrDescModalBody').innerHTML    = html || '<p style="color:var(--text-muted);">No description available.</p>';
  openModal('mfrDescModal');
}


/* ═══════════════════════════════════════════════════════
   DELETE CONFIRM
   ═══════════════════════════════════════════════════════ */
function confirmDeleteMfr(mfrId, name) {
  document.getElementById('delMfrId').value         = mfrId;
  document.getElementById('delMfrName').textContent = name;
  openModal('deleteMfrModal');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
