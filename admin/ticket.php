<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/support_controller.php';

$currentPage = 'ticket';
$pageTitle   = 'Support Tickets';

$canView = sinelec_can('view');
if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

$ctrl = new SupportController();

/* Filters (server-side for DB query) */
$fSearch   = trim($_GET['search']       ?? '');
$fStatus   = trim($_GET['status']       ?? '');
$fPriority = trim($_GET['priority']     ?? '');
$fCatId    = (int)($_GET['category_id'] ?? 0);
$fFrom     = trim($_GET['date_from']    ?? '');
$fTo       = trim($_GET['date_to']      ?? '');

$filters = [
    'search'      => $fSearch,
    'status'      => $fStatus,
    'priority'    => $fPriority,
    'category_id' => $fCatId,
    'date_from'   => $fFrom,
    'date_to'     => $fTo,
    'limit'       => 2000,   // load all — JS handles pagination
    'offset'      => 0,
];

$tickets    = $ctrl->getAllTickets($filters);
$stats      = $ctrl->getTicketStats();
$categories = $ctrl->getCategories();
$allCustomers = $ctrl->getAllCustomers();

function tkAdmStatusBadge(string $s): string {
    $map = [
        'Open'        => 'badge--blue',
        'In Progress' => 'badge--amber',
        'Resolved'    => 'badge--green',
        'Closed'      => 'badge--grey',
        'Reopened'    => 'badge--violet',
    ];
    $cls = $map[$s] ?? 'badge--grey';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars($s) . '</span>';
}

function tkAdmPriBadge(string $p): string {
    $map = ['Urgent' => 'badge--red','High' => 'badge--amber','Normal' => 'badge--grey','Low' => 'badge--grey'];
    $cls = $map[$p] ?? 'badge--grey';
    return '<span class="badge ' . $cls . '" style="font-size:10px;">' . htmlspecialchars($p) . '</span>';
}

ob_start();
?>

<!-- ══ Page header ══ -->
<div class="pg-header">
  <div>
    <h1 class="pg-title">Support Tickets</h1>
    <p class="pg-sub">View and manage all customer support tickets.</p>
  </div>
  <div style="display:flex;gap:8px;">
    <?php if (sinelec_can('add')): ?>
    <button class="btn btn--primary" onclick="tkOpenAdd()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Ticket
    </button>
    <?php endif; ?>
    <a href="support-categories" class="btn btn--ghost">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
      Categories
    </a>
  </div>
</div>

<!-- ══ Stats tiles ══ -->
<?php
$statTiles = [
    ['Total',       $stats['Total']       ?? 0, '#4f46e5', '#ede9fe'],
    ['Open',        $stats['Open']        ?? 0, '#1d4ed8', '#dbeafe'],
    ['In Progress', $stats['In Progress'] ?? 0, '#d97706', '#fef3c7'],
    ['Resolved',    $stats['Resolved']    ?? 0, '#16a34a', '#dcfce7'],
    ['Closed',      $stats['Closed']      ?? 0, '#475569', '#f1f5f9'],
];
?>
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:22px;">
  <?php foreach ($statTiles as [$lbl,$val,$clr,$bg]): ?>
  <div style="background:<?= $bg ?>;border-radius:12px;padding:14px 16px;border:1px solid <?= $clr ?>22;">
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:<?= $clr ?>;margin-bottom:6px;"><?= $lbl ?></div>
    <div style="font-size:26px;font-weight:800;color:<?= $clr ?>;"><?= $val ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══ Filter bar ══ -->
<form method="GET" id="tkFilterForm" style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;align-items:flex-end;">
  <div style="position:relative;flex:1;min-width:200px;max-width:280px;">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af;" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="search" class="form-control" placeholder="Search ticket #, subject, customer…" value="<?= htmlspecialchars($fSearch) ?>" style="padding-left:30px;height:36px;">
  </div>
  <select name="status" class="form-control" style="height:36px;width:auto;min-width:140px;">
    <option value="">All Status</option>
    <?php foreach (['Open','In Progress','Resolved','Closed','Reopened'] as $s): ?>
    <option value="<?= $s ?>" <?= $fStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <select name="priority" class="form-control" style="height:36px;width:auto;min-width:120px;">
    <option value="">All Priority</option>
    <?php foreach (['Urgent','High','Normal','Low'] as $p): ?>
    <option value="<?= $p ?>" <?= $fPriority === $p ? 'selected' : '' ?>><?= $p ?></option>
    <?php endforeach; ?>
  </select>
  <select name="category_id" class="form-control" style="height:36px;width:auto;min-width:160px;">
    <option value="0">All Categories</option>
    <?php foreach ($categories as $cat): ?>
    <option value="<?= (int)(float)($cat->CATEGORY_ID ?? 0) ?>"
            <?= $fCatId === (int)(float)($cat->CATEGORY_ID ?? 0) ? 'selected' : '' ?>>
      <?= htmlspecialchars((string)($cat->CATEGORY_NAME ?? '')) ?>
    </option>
    <?php endforeach; ?>
  </select>
  <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($fFrom) ?>" style="height:36px;width:130px;" title="From date">
  <input type="date" name="date_to"   class="form-control" value="<?= htmlspecialchars($fTo) ?>"   style="height:36px;width:130px;" title="To date">
  <button type="submit" class="btn btn--primary" style="height:36px;padding:0 16px;">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="16" y2="12"/><line x1="4" y1="18" x2="12" y2="18"/></svg>
    Apply
  </button>
  <?php if ($fSearch || $fStatus || $fPriority || $fCatId || $fFrom || $fTo): ?>
  <a href="ticket" class="btn btn--ghost" style="height:36px;padding:0 14px;font-size:12px;">Clear</a>
  <?php endif; ?>
</form>

<!-- ══ Table card ══ -->
<div class="card" style="overflow:hidden;">

  <?php if (empty($tickets)): ?>
  <div class="card-body">
    <div class="empty-state">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <h3>No tickets found</h3>
      <p>Try clearing your filters or check back later.</p>
    </div>
  </div>
  <?php else: ?>

  <!-- ── Pagination bar ── -->
  <div class="tk-pgbar">
    <div class="tk-pgbar__info">
      Showing <strong id="tkRangeStart">1</strong>–<strong id="tkRangeEnd">20</strong>
      of <strong id="tkCount"><?= count($tickets) ?></strong> ticket<?= count($tickets) !== 1 ? 's' : '' ?>
    </div>
    <div class="tk-pgbar__perpage">
      <span class="tk-pgbar__perpage-label">Per page</span>
      <div class="tk-pgbar__sel-wrap">
        <select id="tkPerPage" class="tk-pgbar__sel">
          <option value="10">10</option>
          <option value="20" selected>20</option>
          <option value="30">30</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <svg class="tk-pgbar__sel-arrow" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <button type="button" class="tk-pgbar__apply" onclick="tkApplyPerPage()">Apply</button>
    </div>
    <div id="tkPager" class="tk-pgbar__pager"></div>
  </div>

  <!-- ── Table ── -->
  <div style="overflow-x:auto;">
    <table class="dt" id="tkTable">
      <thead>
        <tr>
          <th style="width:36px;">#</th>
          <th>Ticket #</th>
          <th>Customer</th>
          <th>Category</th>
          <th style="min-width:180px;">Subject</th>
          <th>Priority</th>
          <th>Status</th>
          <th style="text-align:center;">Msgs</th>
          <th>Last Activity</th>
          <th>Created</th>
          <th style="width:80px;text-align:center;">Action</th>
        </tr>
      </thead>
      <tbody id="tkTbody">
        <?php foreach ($tickets as $i => $t):
          $tId       = (int)(float)($t->TICKET_ID    ?? 0);
          $tNum      = (string)($t->TICKET_NUMBER    ?? '');
          $subject   = (string)($t->SUBJECT          ?? '');
          $status    = (string)($t->CURRENT_STATUS   ?? 'Open');
          $priority  = (string)($t->PRIORITY         ?? 'Normal');
          $catName   = (string)($t->CATEGORY_NAME    ?? '—');
          $catType   = (string)($t->CATEGORY_TYPE    ?? 'Other');
          $userName  = (string)($t->USER_NAME        ?? '—');
          $userEmail = (string)($t->USER_EMAIL       ?? '');
          $orderNum  = (string)($t->ORDER_NUMBER     ?? '');
          $msgCount  = (int)($t->MSG_COUNT           ?? 0);
          $createdAt = (string)($t->CREATED_AT       ?? '');
          $lastMsgAt = (string)($t->LAST_MSG_AT      ?? $createdAt);
          $isAdminCreated = (bool)(int)($t->IS_ADMIN_CREATED ?? 0);
          $createdFmt = $ctrl->fmtDate($createdAt);
          $lastFmt    = $ctrl->timeAgo($lastMsgAt);
          $searchStr  = strtolower($tNum.' '.$subject.' '.$userName.' '.$userEmail.' '.$status.' '.$priority);
        ?>
        <tr class="tk-row" data-search="<?= htmlspecialchars($searchStr) ?>" data-seq="<?= $i + 1 ?>">
          <td class="tk-sno td-sm" style="font-size:12px;color:var(--muted);font-weight:600;"><?= $i + 1 ?></td>
          <td>
            <a href="support-ticket-detail?id=<?= $tId ?>" style="font-family:monospace;font-weight:700;color:var(--primary);text-decoration:none;font-size:12px;">
              <?= htmlspecialchars($tNum) ?>
            </a>
          </td>
          <td>
            <div style="font-size:13px;font-weight:600;color:var(--text);"><?= htmlspecialchars($userName) ?></div>
            <?php if ($userEmail): ?>
            <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($userEmail) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-size:12px;font-weight:600;color:var(--text);"><?= htmlspecialchars($catName) ?></div>
            <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($catType) ?></div>
          </td>
          <td style="max-width:220px;">
            <div style="font-size:13px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;" title="<?= htmlspecialchars($subject) ?>">
              <?= htmlspecialchars($subject) ?>
            </div>
            <?php if ($orderNum): ?>
            <div style="font-size:11px;color:var(--muted);">Order: <?= htmlspecialchars($orderNum) ?></div>
            <?php endif; ?>
          </td>
          <td><?= tkAdmPriBadge($priority) ?></td>
          <td><?= tkAdmStatusBadge($status) ?></td>
          <td style="text-align:center;font-size:13px;color:var(--muted);"><?= $msgCount ?></td>
          <td style="font-size:12px;color:var(--muted);white-space:nowrap;"><?= htmlspecialchars($lastFmt) ?></td>
          <td style="font-size:12px;color:var(--muted);white-space:nowrap;"><?= htmlspecialchars($createdFmt) ?></td>
          <td style="text-align:center;">
            <div class="kbm-wrap">
              <button class="kbm-btn" onclick="toggleKbm(this)" title="Actions">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
              </button>
              <div class="kbm-drop">
                <a class="kbm-item" href="support-ticket-detail?id=<?= $tId ?>">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  View Ticket
                </a>
                <?php if ($isAdminCreated && sinelec_can('delete')): ?>
                <div class="kbm-divider"></div>
                <button class="kbm-item kbm-item--danger" onclick="closeKbm(this);tkConfirmDelete(<?= $tId ?>,<?= htmlspecialchars(json_encode($tNum), ENT_QUOTES) ?>)">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                  Delete Ticket
                </button>
                <?php endif; ?>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div id="tkEmpty" style="display:none;padding:40px 0;text-align:center;font-size:13px;color:var(--muted);">No tickets match your search.</div>
  </div>
  <?php endif; ?>
</div>

<style>
/* ══ Pagination bar (mirrors inv-pgbar) ══ */
.tk-pgbar {
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;flex-wrap:wrap;
  padding:14px 20px;
  border-bottom:1px solid var(--border);
  background:#fff;
}
.tk-pgbar__info { font-size:13px;color:#64748b;white-space:nowrap; }
.tk-pgbar__info strong { color:#1e293b;font-weight:700; }
.tk-pgbar__perpage { display:flex;align-items:center;gap:10px;flex-shrink:0; }
.tk-pgbar__perpage-label { font-size:13px;font-weight:600;color:#374151;white-space:nowrap; }
.tk-pgbar__sel-wrap { position:relative;display:inline-flex;align-items:center; }
.tk-pgbar__sel {
  -webkit-appearance:none;appearance:none;
  height:36px;padding:0 32px 0 14px;
  border:1.5px solid #e2e8f0;border-radius:20px;
  font-size:13px;font-weight:600;color:#1e293b;
  background:#fff;cursor:pointer;outline:none;transition:border-color .15s;
}
.tk-pgbar__sel:hover,.tk-pgbar__sel:focus { border-color:#6366f1; }
.tk-pgbar__sel-arrow { position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#64748b; }
.tk-pgbar__apply {
  height:36px;padding:0 20px;background:#1e293b;color:#fff;
  border:none;border-radius:20px;font-size:13px;font-weight:600;
  cursor:pointer;white-space:nowrap;transition:background .15s;
}
.tk-pgbar__apply:hover { background:#0f172a; }
.tk-pgbar__pager { display:flex;align-items:center;gap:5px;flex-wrap:wrap; }
.tk-pg-nav {
  height:36px;padding:0 16px;
  border:1.5px solid #e2e8f0;border-radius:20px;
  background:#fff;font-size:13px;font-weight:600;color:#374151;
  cursor:pointer;white-space:nowrap;transition:border-color .15s,color .15s;
}
.tk-pg-nav:hover:not(:disabled) { border-color:#6366f1;color:#6366f1; }
.tk-pg-nav:disabled,.tk-pg-nav--disabled { color:#cbd5e1;border-color:#f1f5f9;cursor:default; }
.tk-pg-num {
  width:36px;height:36px;
  border:1.5px solid #e2e8f0;border-radius:50%;
  background:#fff;font-size:13px;font-weight:600;color:#374151;
  cursor:pointer;display:inline-flex;align-items:center;justify-content:center;
  transition:border-color .15s,color .15s,background .15s;flex-shrink:0;
}
.tk-pg-num:hover { border-color:#6366f1;color:#6366f1; }
.tk-pg-num.is-cur { background:#4f46e5;border-color:#4f46e5;color:#fff; }
.tk-pg-dots { font-size:13px;color:#94a3b8;padding:0 2px;display:inline-flex;align-items:center; }
@media(max-width:640px){ .tk-pgbar { flex-direction:column;align-items:flex-start;gap:10px; } }
</style>

<script>
/* ══ Ticket Table — Client-side Pagination ══ */
var _tkPage    = 1;
var _tkPerPage = 20;
var _tkRows    = [];

function tkInit() {
  _tkRows = Array.from(document.querySelectorAll('#tkTbody .tk-row'));
  tkRender();
}

function tkApplyPerPage() {
  _tkPerPage = parseInt(document.getElementById('tkPerPage').value, 10) || 20;
  _tkPage = 1;
  tkRender();
}

function tkRender() {
  var pp = _tkPerPage, total = _tkRows.length;
  var pages = Math.max(1, Math.ceil(total / pp));
  if (_tkPage > pages) _tkPage = pages;
  if (_tkPage < 1)     _tkPage = 1;
  var start = (_tkPage - 1) * pp;
  var end   = Math.min(start + pp, total);

  _tkRows.forEach(function(r, i) {
    var vis = (i >= start && i < end);
    r.style.display = vis ? '' : 'none';
    if (vis) r.querySelector('.tk-sno').textContent = i + 1;
  });

  document.getElementById('tkCount').textContent      = total;
  document.getElementById('tkRangeStart').textContent = total > 0 ? start + 1 : 0;
  document.getElementById('tkRangeEnd').textContent   = end;
  document.getElementById('tkEmpty').style.display    = total === 0 ? 'block' : 'none';
  _tkBuildPager(pages);
}

function _tkBuildPager(pages) {
  var pager = document.getElementById('tkPager');
  pager.innerHTML = '';
  pager.appendChild(_tkNavBtn('Prev', _tkPage - 1, _tkPage <= 1));
  if (pages > 1) {
    _tkPageNums(_tkPage, pages).forEach(function(n) {
      if (n === -1) {
        var dots = document.createElement('span');
        dots.className = 'tk-pg-dots'; dots.textContent = '...';
        pager.appendChild(dots);
      } else {
        pager.appendChild(_tkNumBtn(n));
      }
    });
  }
  pager.appendChild(_tkNavBtn('Next', _tkPage + 1, _tkPage >= pages));
}

function _tkPageNums(cur, total) {
  if (total <= 1) return [];
  var set = new Set();
  if (cur !== 1)     set.add(1);
  if (cur !== total) set.add(total);
  var before = Math.min(2, cur - 1);
  var after  = Math.min(2, total - cur);
  if (before + after < 4) {
    if (cur <= 3)              after  = Math.min(4 - before, total - cur);
    else if (cur >= total - 2) before = Math.min(4 - after,  cur - 1);
  }
  for (var p = cur - before; p <= cur + after; p++) {
    if (p >= 1 && p <= total && p !== cur) set.add(p);
  }
  var arr = Array.from(set).sort(function(a,b){ return a - b; });
  var result = [];
  for (var i = 0; i < arr.length; i++) {
    if (i > 0 && arr[i] - arr[i-1] > 1) result.push(-1);
    result.push(arr[i]);
  }
  return result;
}

function _tkNavBtn(label, pg, disabled) {
  var b = document.createElement('button');
  b.textContent = label;
  b.className   = 'tk-pg-nav' + (disabled ? ' tk-pg-nav--disabled' : '');
  b.disabled    = disabled;
  if (!disabled) b.onclick = function() { _tkPage = pg; tkRender(); };
  return b;
}

function _tkNumBtn(pg) {
  var b = document.createElement('button');
  b.textContent = String(pg);
  b.className   = 'tk-pg-num' + (pg === _tkPage ? ' is-cur' : '');
  b.onclick     = function() { _tkPage = pg; tkRender(); };
  return b;
}

document.addEventListener('DOMContentLoaded', tkInit);

/* ══ Delete Ticket ══ */
function tkConfirmDelete(ticketId, ticketNum) {
  if (!confirm('Delete ticket ' + ticketNum + '?\n\nThis will permanently remove the ticket and all its messages. This cannot be undone.')) return;
  fetch('ajax/support?action=delete_ticket', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ticket_id: ticketId })
  })
  .then(function(r){ return r.json(); })
  .then(function(d){
    if (d.ok) {
      // Remove the row from the table
      var rows = Array.from(document.querySelectorAll('#tkTbody .tk-row'));
      rows.forEach(function(r) {
        var link = r.querySelector('a[href*="support-ticket-detail"]');
        if (link && link.href.includes('id=' + ticketId)) r.remove();
      });
      _tkRows = Array.from(document.querySelectorAll('#tkTbody .tk-row'));
      tkRender();
    } else {
      alert(d.msg || 'Failed to delete ticket.');
    }
  })
  .catch(function(){ alert('Network error. Please try again.'); });
}

/* ══ Add Ticket Modal ══ */
function tkOpenAdd() {
  tkCustClear();
  document.getElementById('tkSubject').value        = '';
  document.getElementById('tkDesc').value           = '';
  document.getElementById('tkCatSel').value         = '';
  document.getElementById('tkPriSel').value         = 'Normal';
  document.getElementById('tkAddErr').style.display = 'none';
  // reset customer filter to show all
  document.querySelectorAll('#tkCustDrop .cust-ts-opt').forEach(function(o){ o.style.display = ''; });
  var none = document.getElementById('tkCustNone');
  if (none) none.style.display = 'none';
  openModal('tkAddModal');
}

/* ── Customer typeahead (preloaded) ── */
function tkCustOpen() {
  document.getElementById('tkCustDrop').classList.add('open');
  tkCustFilter();
  setTimeout(function(){ document.addEventListener('click', _tkCustOutside, true); }, 0);
}
function _tkCustOutside(e) {
  var wrap = document.getElementById('tkCustTsWrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('tkCustDrop').classList.remove('open');
    document.removeEventListener('click', _tkCustOutside, true);
  }
}
function tkCustFilter() {
  var q    = (document.getElementById('tkCustInput').value || '').toLowerCase().trim();
  var opts = document.querySelectorAll('#tkCustDrop .cust-ts-opt');
  var shown = 0;
  opts.forEach(function(o) {
    var match = !q || (o.dataset.label || '').includes(q);
    o.style.display = match ? '' : 'none';
    if (match) shown++;
  });
  var none = document.getElementById('tkCustNone');
  if (none) none.style.display = shown === 0 ? '' : 'none';
  document.getElementById('tkCustDrop').classList.add('open');
}
function tkCustSelect(optEl) {
  var uid  = optEl.dataset.uid   || '0';
  var name = optEl.dataset.name  || '';
  document.getElementById('tkCustInput').value = name + (optEl.dataset.email ? ' — ' + optEl.dataset.email : '');
  document.getElementById('tkCustClear').classList.add('visible');
  document.getElementById('tkCustDrop').classList.remove('open');
  document.removeEventListener('click', _tkCustOutside, true);
  document.getElementById('tkCustId').value = uid;
  // show chip
  document.getElementById('tkCustChipName').textContent = name;
  document.getElementById('tkCustChipMeta').textContent =
    [optEl.dataset.email, optEl.dataset.company].filter(Boolean).join(' · ');
  document.getElementById('tkCustChip').style.display    = 'flex';
  document.getElementById('tkCustTsWrap').style.display  = 'none';
}
function tkCustClear() {
  document.getElementById('tkCustInput').value = '';
  document.getElementById('tkCustClear').classList.remove('visible');
  document.getElementById('tkCustDrop').classList.remove('open');
  document.removeEventListener('click', _tkCustOutside, true);
  document.getElementById('tkCustId').value              = '';
  document.getElementById('tkCustChip').style.display    = 'none';
  document.getElementById('tkCustTsWrap').style.display  = '';
  document.querySelectorAll('#tkCustDrop .cust-ts-opt').forEach(function(o){ o.style.display = ''; });
  var none = document.getElementById('tkCustNone');
  if (none) none.style.display = 'none';
}

function tkSubmitAdd() {
  var custId  = document.getElementById('tkCustId').value;
  var catId   = document.getElementById('tkCatSel').value;
  var subject = document.getElementById('tkSubject').value.trim();
  var desc    = document.getElementById('tkDesc').value.trim();
  var pri     = document.getElementById('tkPriSel').value;
  var errEl   = document.getElementById('tkAddErr');

  if (!custId) { errEl.textContent = 'Please select a customer.'; errEl.style.display = ''; return; }
  if (!catId)  { errEl.textContent = 'Please select a category.'; errEl.style.display = ''; return; }
  if (!subject){ errEl.textContent = 'Subject is required.';       errEl.style.display = ''; return; }
  errEl.style.display = 'none';

  var btn = document.getElementById('tkAddSubmitBtn');
  btn.disabled    = true;
  btn.textContent = 'Creating…';

  fetch('ajax/support?action=create_ticket', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ user_id: parseInt(custId), category_id: parseInt(catId), subject: subject, description: desc, priority: pri })
  })
  .then(function(r){ return r.json(); })
  .then(function(d){
    btn.disabled    = false;
    btn.textContent = 'Create Ticket';
    if (d.ok) {
      closeModal('tkAddModal');
      window.location.reload();
    } else {
      errEl.textContent = d.msg || 'Failed to create ticket.';
      errEl.style.display = '';
    }
  })
  .catch(function(){
    btn.disabled    = false;
    btn.textContent = 'Create Ticket';
    errEl.textContent = 'Network error. Please try again.';
    errEl.style.display = '';
  });
}

</script>

<!-- ══ Add Ticket Modal ══ -->
<div class="modal-overlay" id="tkAddModal" onclick="if(event.target===this)closeModal('tkAddModal')">
  <div class="modal modal--lg">
    <div class="modal-hd">
      <span class="modal-title">Create Ticket on Behalf of Customer</span>
      <button class="modal-close" onclick="closeModal('tkAddModal')">&times;</button>
    </div>
    <div class="modal-body">

      <!-- Customer Typeahead (preloaded — same pattern as quotation.php) -->
      <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label">Customer <span class="req">*</span></label>
        <input type="hidden" id="tkCustId">
        <div class="cust-ts-wrap" id="tkCustTsWrap">
          <input type="text" id="tkCustInput" class="form-control" placeholder="Type name, email or company to search…"
                 autocomplete="off" oninput="tkCustFilter()" onfocus="tkCustOpen()" style="padding-right:34px;">
          <span class="cust-ts-clear" id="tkCustClear" onclick="tkCustClear()" title="Clear">×</span>
          <div class="cust-ts-drop" id="tkCustDrop">
            <?php foreach ($allCustomers as $cu):
              $cuid   = (int)(float)($cu->USER_ID    ?? 0);
              $cname  = (string)($cu->USER_NAME      ?? '');
              $cemail = (string)($cu->USER_EMAIL     ?? '');
              $ccomp  = (string)($cu->COMPANY_NAME   ?? '');
              $clabel = strtolower($cname . ' ' . $cemail . ' ' . $ccomp);
            ?>
            <div class="cust-ts-opt" tabindex="0"
                 data-uid="<?= $cuid ?>"
                 data-name="<?= htmlspecialchars($cname) ?>"
                 data-email="<?= htmlspecialchars($cemail) ?>"
                 data-company="<?= htmlspecialchars($ccomp) ?>"
                 data-label="<?= htmlspecialchars($clabel) ?>"
                 onclick="tkCustSelect(this)">
              <span class="cust-ts-name"><?= htmlspecialchars($cname) ?></span>
              <?php if ($cemail): ?><span class="cust-ts-email"><?= htmlspecialchars($cemail) ?></span><?php endif; ?>
              <?php if ($ccomp):  ?><span class="cust-ts-co"><?= htmlspecialchars($ccomp) ?></span><?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="cust-ts-none" id="tkCustNone" style="display:none;">No customers match.</div>
          </div>
        </div>
        <!-- Selected customer chip -->
        <div id="tkCustChip" style="display:none;margin-top:8px;padding:10px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;align-items:center;gap:10px;">
          <div style="flex:1;min-width:0;">
            <div style="font-weight:600;font-size:13px;color:#1e40af;" id="tkCustChipName"></div>
            <div style="font-size:11px;color:#64748b;" id="tkCustChipMeta"></div>
          </div>
          <button type="button" onclick="tkCustClear()" style="background:none;border:none;cursor:pointer;color:#64748b;font-size:18px;line-height:1;flex-shrink:0;" title="Change customer">×</button>
        </div>
      </div>

      <!-- Category & Priority row -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
        <div class="form-group">
          <label class="form-label">Category <span class="req">*</span></label>
          <select id="tkCatSel" class="form-control">
            <option value="">— Select category —</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)(float)($cat->CATEGORY_ID ?? 0) ?>">
              <?= htmlspecialchars((string)($cat->CATEGORY_NAME ?? '')) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Priority</label>
          <select id="tkPriSel" class="form-control">
            <option value="Normal" selected>Normal</option>
            <option value="Low">Low</option>
            <option value="High">High</option>
            <option value="Urgent">Urgent</option>
          </select>
        </div>
      </div>

      <!-- Subject -->
      <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label">Subject <span class="req">*</span></label>
        <input type="text" id="tkSubject" class="form-control" placeholder="Brief description of the issue" maxlength="200">
      </div>

      <!-- Description -->
      <div class="form-group" style="margin-bottom:6px;">
        <label class="form-label">Description</label>
        <textarea id="tkDesc" class="form-control" rows="4" placeholder="Detailed description of the customer's issue…" style="resize:vertical;"></textarea>
      </div>

      <!-- Error -->
      <div id="tkAddErr" class="alert alert--error" style="display:none;margin-top:12px;"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn--ghost" onclick="closeModal('tkAddModal')">Cancel</button>
      <button class="btn btn--primary" id="tkAddSubmitBtn" onclick="tkSubmitAdd()">Create Ticket</button>
    </div>
  </div>
</div>

<style>
/* ── Customer typeahead (scoped to ticket modal) ── */
.cust-ts-wrap  { position:relative; }
.cust-ts-clear {
  position:absolute;right:8px;top:50%;transform:translateY(-50%);
  font-size:17px;line-height:1;color:#9ca3af;cursor:pointer;
  display:none;padding:2px 4px;border-radius:4px;transition:color .15s;
}
.cust-ts-clear:hover { color:#ef4444; }
.cust-ts-clear.visible { display:block; }
.cust-ts-drop {
  position:absolute;top:calc(100% + 4px);left:0;right:0;
  max-height:240px;overflow-y:auto;background:#fff;
  border:1.5px solid var(--border);border-radius:8px;
  box-shadow:0 8px 24px rgba(0,0,0,.1);
  z-index:9999;display:none;
}
.cust-ts-drop.open { display:block; }
.cust-ts-opt {
  padding:9px 12px;cursor:pointer;
  border-bottom:1px solid #f1f5f9;
  transition:background .1s;
  display:flex;flex-direction:column;gap:2px;
}
.cust-ts-opt:last-child { border-bottom:none; }
.cust-ts-opt:hover,
.cust-ts-opt:focus { background:#f0f4ff;outline:none; }
.cust-ts-name  { font-size:13px;font-weight:600;color:var(--text); }
.cust-ts-email { font-size:11px;color:var(--text-muted); }
.cust-ts-co    { font-size:11px;color:#7c3aed;font-weight:500; }
.cust-ts-none  { padding:14px 12px;text-align:center;font-size:13px;color:var(--text-muted); }
</style>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
