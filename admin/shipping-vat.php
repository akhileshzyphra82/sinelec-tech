<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../config/db_helper.php';

$currentPage = 'shipping-vat';
$pageTitle   = 'Shipping & VAT';

$canView = sinelec_can('view');
$canEdit = sinelec_can('edit');

if (!$canView) {
    sinelec_set_flash('err', 'No permission.');
    header('location:dashboard'); exit();
}

$db        = new MySQLDB();
$countries = $db->select(
    "SELECT country_id, country, shipping_amt,
            standard_b2c_vat, standard_b2b_vat,
            oss_b2c_vat, oss_b2b_vat, applied_vat
     FROM tbl_country
     ORDER BY country ASC"
);

ob_start();
?>

<!-- ── Page header ── -->
<div class="pg-header">
  <div>
    <div class="pg-title">Shipping &amp; VAT</div>
    <div class="pg-sub">Configure shipping rates and VAT rules per country. Check rows you want to update, then click Save.</div>
  </div>
</div>

<!-- ── Toolbar ── -->
<div class="filter-bar" style="margin-bottom:16px">
  <div style="position:relative;flex:1;max-width:320px">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="svSearch" class="form-control" placeholder="Search country…"
           oninput="svFilter()" style="padding-left:34px">
  </div>
  <span id="svVisibleCount" style="font-size:12px;color:#64748b">200 countries</span>
</div>

<!-- ── Table ── -->
<div class="card" style="overflow:hidden">
  <div style="overflow-x:auto">
    <table class="data-table sv-table" id="svTable">
      <thead>
        <tr>
          <th class="sv-th-cb" style="width:52px">
            <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
              <input type="checkbox" id="svSelectAllCb" onchange="svSelectAll(this.checked)"
                     title="Select all visible rows"
                     style="width:15px;height:15px;accent-color:#1d4ed8;cursor:pointer"
                     <?= $canEdit ? '' : 'disabled' ?>>
              <span style="font-size:9px;font-weight:700;color:#6b7280;letter-spacing:.03em;text-transform:uppercase">All</span>
            </div>
          </th>
          <th style="min-width:160px">Country</th>
          <th class="sv-th-group" style="min-width:100px">
            <span class="sv-col-icon sv-col-ship">€</span>
            Shipping
          </th>
          <!-- Standard group -->
          <th class="sv-th-group sv-group-std" style="min-width:90px">
            <span class="sv-group-label">Standard</span>
            B2C VAT %
          </th>
          <th class="sv-th-group sv-group-std" style="min-width:90px">
            <span class="sv-group-label sv-group-label--ghost">Standard</span>
            B2B VAT %
          </th>
          <!-- OSS group -->
          <th class="sv-th-group sv-group-oss" style="min-width:90px">
            <span class="sv-group-label sv-group-label--oss">OSS</span>
            B2C VAT %
          </th>
          <th class="sv-th-group sv-group-oss" style="min-width:90px">
            <span class="sv-group-label sv-group-label--oss sv-group-label--ghost">OSS</span>
            B2B VAT %
          </th>
          <!-- Applied VAT -->
          <th style="min-width:140px;text-align:center">
            Applied VAT
            <?php if ($canEdit): ?>
            <div style="margin-top:6px">
              <label style="display:inline-flex;align-items:center;gap:5px;font-size:9px;font-weight:700;
                            color:#7c3aed;text-transform:uppercase;letter-spacing:.04em;cursor:pointer;
                            background:#ede9fe;padding:3px 8px;border-radius:20px;white-space:nowrap"
                     title="Toggle all visible rows between Standard and OSS">
                <input type="checkbox" id="svBulkOssCb" onchange="svBulkOSS(this.checked)"
                       style="width:12px;height:12px;accent-color:#7c3aed;cursor:pointer">
                All to OSS
              </label>
            </div>
            <?php endif; ?>
          </th>
        </tr>
      </thead>
      <tbody id="svTbody">
        <?php foreach ($countries as $c):
          $cid  = (int)(float)($c->COUNTRY_ID ?? 0);
          $name = htmlspecialchars((string)($c->COUNTRY ?? ''));
          $ship = $c->SHIPPING_AMT ?? '19.99';
          $sb2c = $c->STANDARD_B2C_VAT ?? '';
          $sb2b = $c->STANDARD_B2B_VAT ?? '';
          $ob2c = $c->OSS_B2C_VAT ?? '';
          $ob2b = $c->OSS_B2B_VAT ?? '';
          $av   = (string)($c->APPLIED_VAT ?? 'Standard');
          $isOSS = $av === 'OSS';
        ?>
        <tr class="sv-row" id="svRow<?= $cid ?>" data-id="<?= $cid ?>" data-name="<?= strtolower($name) ?>">
          <!-- Checkbox -->
          <td class="sv-td-cb">
            <input type="checkbox" class="sv-cb" id="svCb<?= $cid ?>"
                   onchange="svOnCheck(<?= $cid ?>, this.checked)"
                   style="width:15px;height:15px;accent-color:#1d4ed8;cursor:pointer"
                   <?= $canEdit ? '' : 'disabled' ?>>
          </td>
          <!-- Country name -->
          <td class="sv-td-name">
            <span style="font-size:13px;font-weight:600;color:#1a2332"><?= $name ?></span>
          </td>
          <!-- Shipping -->
          <td>
            <input type="number" class="sv-input sv-input--ship" id="svShip<?= $cid ?>"
                   value="<?= htmlspecialchars((string)$ship) ?>"
                   min="0" step="0.01" placeholder="0.00"
                   oninput="svMarkDirty(<?= $cid ?>)"
                   <?= $canEdit ? '' : 'readonly' ?>>
          </td>
          <!-- Standard B2C -->
          <td class="sv-cell-std">
            <input type="number" class="sv-input sv-input--vat" id="svSB2C<?= $cid ?>"
                   value="<?= $sb2c !== '' && $sb2c !== null ? htmlspecialchars((string)$sb2c) : '' ?>"
                   min="0" max="100" step="0.01" placeholder="—"
                   oninput="svMarkDirty(<?= $cid ?>)"
                   <?= $canEdit ? '' : 'readonly' ?>>
          </td>
          <!-- Standard B2B -->
          <td class="sv-cell-std">
            <input type="number" class="sv-input sv-input--vat" id="svSB2B<?= $cid ?>"
                   value="<?= $sb2b !== '' && $sb2b !== null ? htmlspecialchars((string)$sb2b) : '' ?>"
                   min="0" max="100" step="0.01" placeholder="—"
                   oninput="svMarkDirty(<?= $cid ?>)"
                   <?= $canEdit ? '' : 'readonly' ?>>
          </td>
          <!-- OSS B2C -->
          <td class="sv-cell-oss">
            <input type="number" class="sv-input sv-input--vat" id="svOB2C<?= $cid ?>"
                   value="<?= $ob2c !== '' && $ob2c !== null ? htmlspecialchars((string)$ob2c) : '' ?>"
                   min="0" max="100" step="0.01" placeholder="—"
                   oninput="svMarkDirty(<?= $cid ?>)"
                   <?= $canEdit ? '' : 'readonly' ?>>
          </td>
          <!-- OSS B2B -->
          <td class="sv-cell-oss">
            <input type="number" class="sv-input sv-input--vat" id="svOB2B<?= $cid ?>"
                   value="<?= $ob2b !== '' && $ob2b !== null ? htmlspecialchars((string)$ob2b) : '' ?>"
                   min="0" max="100" step="0.01" placeholder="—"
                   oninput="svMarkDirty(<?= $cid ?>)"
                   <?= $canEdit ? '' : 'readonly' ?>>
          </td>
          <!-- Applied VAT toggle -->
          <td style="text-align:center">
            <div class="sv-vat-toggle" id="svVatToggle<?= $cid ?>">
              <button type="button"
                      class="sv-vat-btn sv-vat-std <?= !$isOSS ? 'is-active' : '' ?>"
                      onclick="svSetVAT(<?= $cid ?>, 'Standard')"
                      <?= $canEdit ? '' : 'disabled' ?>>
                Std
              </button>
              <button type="button"
                      class="sv-vat-btn sv-vat-oss <?= $isOSS ? 'is-active' : '' ?>"
                      onclick="svSetVAT(<?= $cid ?>, 'OSS')"
                      <?= $canEdit ? '' : 'disabled' ?>>
                OSS
              </button>
            </div>
            <input type="hidden" id="svAV<?= $cid ?>" value="<?= $av ?>">
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- ── Sticky save bar (fixed bottom) ── -->
<?php if ($canEdit): ?>
<div class="sv-save-bar" id="svSaveBar" style="display:none">
  <span id="svSaveBarInfo" style="font-size:13px;color:#374151;font-weight:600"></span>
  <div style="display:flex;gap:8px">
    <button class="btn btn--ghost btn--sm" onclick="svClearSelection()">Clear Selection</button>
    <button class="btn btn--primary" id="svSaveBtnBottom" onclick="svSave()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      Save Selected
    </button>
  </div>
</div>
<?php endif; ?>

<style>
/* ── Shipping & VAT page ── */
.sv-table { border-collapse: collapse; width: 100%; min-width: 780px; }
.sv-table thead th {
  background: #f8fafc; border-bottom: 2px solid #e2e8f0;
  padding: 10px 12px; font-size: 11px; font-weight: 700;
  color: #374151; text-transform: uppercase; letter-spacing: .04em;
  white-space: nowrap; vertical-align: bottom;
}
.sv-table tbody td {
  padding: 7px 10px; border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}
.sv-row:hover { background: #fafbfc; }
.sv-row.is-selected { background: #eff6ff; }
.sv-row.is-dirty { background: #fffbeb; }
.sv-row.is-dirty.is-selected { background: #fef3c7; }
.sv-row.sv-hidden { display: none; }

/* Column group accents */
.sv-group-label {
  display: inline-block; font-size: 9px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .05em;
  background: #dbeafe; color: #1d4ed8;
  padding: 2px 6px; border-radius: 4px; margin-bottom: 4px;
}
.sv-group-label--oss { background: #ede9fe; color: #7c3aed; }
.sv-group-label--ghost { opacity: 0; }

.sv-cell-std { background: #fafcff; }
.sv-cell-oss { background: #fdfaff; }
.sv-row:hover .sv-cell-std { background: #f0f7ff; }
.sv-row:hover .sv-cell-oss { background: #f6f0ff; }

/* Inputs */
.sv-input {
  width: 100%; padding: 5px 8px; border: 1.5px solid #e2e8f0;
  border-radius: 6px; font-size: 13px; color: #1a2332;
  background: #fff; outline: none; transition: border-color .15s;
  -moz-appearance: textfield;
}
.sv-input::-webkit-outer-spin-button,
.sv-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.sv-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
.sv-input--ship { max-width: 88px; }
.sv-input--vat  { max-width: 78px; }

/* Applied VAT toggle buttons */
.sv-vat-toggle { display: inline-flex; border: 1.5px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.sv-vat-btn {
  padding: 4px 10px; font-size: 11px; font-weight: 700;
  border: none; cursor: pointer; transition: background .15s, color .15s;
  background: #f8fafc; color: #9ca3af;
}
.sv-vat-btn:disabled { cursor: not-allowed; opacity: .6; }
.sv-vat-std.is-active { background: #dbeafe; color: #1d4ed8; }
.sv-vat-oss.is-active { background: #ede9fe; color: #7c3aed; }

/* Checkbox col */
.sv-th-cb, .sv-td-cb { width: 42px; text-align: center; padding: 0 8px; }

/* Col icon */
.sv-col-icon {
  display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; border-radius: 4px;
  background: #dcfce7; color: #16a34a;
  font-size: 11px; font-weight: 800; margin-bottom: 4px;
}

/* Save bar — fixed at the bottom of the viewport */
.sv-save-bar {
  position: fixed; bottom: 0; left: 0; right: 0; z-index: 200;
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 24px; background: #fff;
  border-top: 2px solid #e2e8f0;
  box-shadow: 0 -4px 16px rgba(0,0,0,.08);
  gap: 12px; flex-wrap: wrap;
}
/* Push page content up so the fixed bar doesn't overlap it */
body.sv-bar-visible { padding-bottom: 64px; }

/* Dirty indicator dot */
.sv-dirty-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #f59e0b; display: inline-block; margin-right: 4px;
  vertical-align: middle;
}
</style>

<script>
/* ── State ── */
var _svSelected = new Set();   /* country_id numbers */
var _svDirty    = new Set();   /* country_id numbers with changed values */
var _svTotal    = <?= count($countries) ?>;
var _svVisible  = _svTotal;

/* ── Search / filter ── */
function svFilter() {
  var q    = document.getElementById('svSearch').value.toLowerCase().trim();
  var rows = document.querySelectorAll('#svTbody .sv-row');
  var vis  = 0;
  rows.forEach(function(row) {
    var name = row.dataset.name || '';
    var hide = q !== '' && name.indexOf(q) === -1;
    row.classList.toggle('sv-hidden', hide);
    if (!hide) vis++;
  });
  _svVisible = vis;
  document.getElementById('svVisibleCount').textContent = vis + ' countr' + (vis === 1 ? 'y' : 'ies');
  _svUpdateSelectAll();
}

/* ── Select / deselect ── */
function svOnCheck(cid, checked) {
  if (checked) { _svSelected.add(cid); }
  else         { _svSelected.delete(cid); }
  var row = document.getElementById('svRow' + cid);
  if (row) row.classList.toggle('is-selected', checked);
  _svUpdateUI();
}

function svSelectAll(checked) {
  document.querySelectorAll('#svTbody .sv-row:not(.sv-hidden)').forEach(function(row) {
    var cid = parseInt(row.dataset.id, 10);
    var cb  = document.getElementById('svCb' + cid);
    if (cb && !cb.disabled) {
      cb.checked = checked;
      if (checked) { _svSelected.add(cid); row.classList.add('is-selected'); }
      else          { _svSelected.delete(cid); row.classList.remove('is-selected'); }
    }
  });
  _svUpdateUI();
}

function svClearSelection() {
  _svSelected.forEach(function(cid) {
    var cb = document.getElementById('svCb' + cid);
    if (cb) cb.checked = false;
    var row = document.getElementById('svRow' + cid);
    if (row) row.classList.remove('is-selected');
  });
  _svSelected.clear();
  _svUpdateUI();
}

function _svUpdateSelectAll() {
  var visChecked = 0, visTotal = 0;
  document.querySelectorAll('#svTbody .sv-row:not(.sv-hidden)').forEach(function(row) {
    var cid = parseInt(row.dataset.id, 10);
    visTotal++;
    if (_svSelected.has(cid)) visChecked++;
  });
  var cb = document.getElementById('svSelectAllCb');
  cb.indeterminate = visChecked > 0 && visChecked < visTotal;
  cb.checked       = visTotal > 0 && visChecked === visTotal;
}

/* ── Dirty (auto-check when user edits a field) ── */
function svMarkDirty(cid) {
  _svDirty.add(cid);
  var row = document.getElementById('svRow' + cid);
  if (row) row.classList.add('is-dirty');
  /* Auto-select the row */
  var cb = document.getElementById('svCb' + cid);
  if (cb && !cb.checked && !cb.disabled) {
    cb.checked = true;
    _svSelected.add(cid);
    if (row) row.classList.add('is-selected');
    _svUpdateUI();
  }
}

/* ── Applied VAT toggle ── */
function svSetVAT(cid, mode) {
  document.getElementById('svAV' + cid).value = mode;

  var wrap   = document.getElementById('svVatToggle' + cid);
  var stdBtn = wrap.querySelector('.sv-vat-std');
  var ossBtn = wrap.querySelector('.sv-vat-oss');
  stdBtn.classList.toggle('is-active', mode === 'Standard');
  ossBtn.classList.toggle('is-active', mode === 'OSS');

  svMarkDirty(cid);
}

/* ── Bulk OSS toggle (header checkbox) ── */
function svBulkOSS(toOSS) {
  var mode = toOSS ? 'OSS' : 'Standard';
  document.querySelectorAll('#svTbody .sv-row:not(.sv-hidden)').forEach(function(row) {
    var cid = parseInt(row.dataset.id, 10);
    svSetVAT(cid, mode);
  });
}

/* ── Update save bar ── */
function _svUpdateUI() {
  var count    = _svSelected.size;
  var saveBar  = document.getElementById('svSaveBar');
  var saveBtn  = document.getElementById('svSaveBtnBottom');
  var saveInfo = document.getElementById('svSaveBarInfo');

  var show = count > 0;
  if (saveBar)  saveBar.style.display = show ? 'flex' : 'none';
  if (saveBtn)  saveBtn.disabled      = !show;
  document.body.classList.toggle('sv-bar-visible', show);

  if (saveInfo) {
    saveInfo.innerHTML = '<span class="sv-dirty-dot"></span>' + count +
      ' countr' + (count === 1 ? 'y' : 'ies') + ' selected';
  }
  _svUpdateSelectAll();
}

/* ── Toast helper — plugs into the admin's existing #toastStack ── */
function svToast(message, type) {
  /* type: 'ok' | 'warn' | 'err' */
  type = type || 'ok';
  var icons = {
    ok:   '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>',
    warn: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    err:  '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
  };

  /* Re-use existing stack or create one */
  var stack = document.getElementById('toastStack');
  if (!stack) {
    stack = document.createElement('div');
    stack.id = 'toastStack';
    stack.className = 'toast-stack';
    document.body.appendChild(stack);
  }

  var toast = document.createElement('div');
  toast.className = 'toast toast--' + type;
  toast.innerHTML =
    '<span style="flex-shrink:0;margin-top:1px">' + (icons[type] || icons.ok) + '</span>' +
    '<span>' + message + '</span>' +
    '<button class="toast-close" onclick="this.closest(\'.toast\').remove()">×</button>';

  stack.appendChild(toast);

  /* Auto-dismiss after 4 s */
  setTimeout(function() {
    toast.style.transition = 'opacity .35s, transform .35s';
    toast.style.opacity    = '0';
    toast.style.transform  = 'translateX(14px)';
    setTimeout(function() { toast.remove(); }, 370);
  }, 4000);
}

/* ── Save ── */
async function svSave() {
  var count = _svSelected.size;
  if (count === 0) return;

  var btn = document.getElementById('svSaveBtnBottom');
  if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

  /* Collect payload */
  var payload = [];
  _svSelected.forEach(function(cid) {
    var ship = parseFloat(document.getElementById('svShip' + cid)?.value) || 0;
    var sb2c = document.getElementById('svSB2C' + cid)?.value.trim();
    var sb2b = document.getElementById('svSB2B' + cid)?.value.trim();
    var ob2c = document.getElementById('svOB2C' + cid)?.value.trim();
    var ob2b = document.getElementById('svOB2B' + cid)?.value.trim();
    var av   = document.getElementById('svAV'   + cid)?.value || 'Standard';

    payload.push({
      country_id:       cid,
      shipping_amt:     ship,
      standard_b2c_vat: sb2c !== '' ? parseFloat(sb2c) : null,
      standard_b2b_vat: sb2b !== '' ? parseFloat(sb2b) : null,
      oss_b2c_vat:      ob2c !== '' ? parseFloat(ob2c) : null,
      oss_b2b_vat:      ob2b !== '' ? parseFloat(ob2b) : null,
      applied_vat:      av,
    });
  });

  try {
    var res  = await fetch('ajax/shipping-vat?action=save', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload),
    });
    var data = await res.json();

    if (data.ok) {
      svToast(data.msg || 'Saved ' + count + ' countr' + (count === 1 ? 'y' : 'ies') + ' successfully.', 'ok');
      /* Clear dirty state for saved rows */
      _svSelected.forEach(function(cid) {
        _svDirty.delete(cid);
        var row = document.getElementById('svRow' + cid);
        if (row) row.classList.remove('is-dirty');
      });
      svClearSelection();
    } else {
      svToast(data.msg || 'Failed to save. Please try again.', 'err');
    }
  } catch(e) {
    svToast('Network error. Please try again.', 'err');
  }

  /* Restore button */
  if (btn) {
    btn.disabled = _svSelected.size === 0;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Selected';
  }
}

/* ── Init ── */
_svUpdateUI();
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
