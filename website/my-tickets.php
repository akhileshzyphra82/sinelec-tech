<?php
require_once __DIR__ . '/account-helpers.php';
require_once __DIR__ . '/../controller/support_controller.php';

$user       = sinelec_require_login();
$userId     = (int)($user['USER_ID'] ?? 0);
$currentPage = 'support';
$pageTitle   = 'Support & Help | Sinelec Technologies';
require_once __DIR__ . '/header.php';

$ctrl      = new SupportController();
$statusFilter = trim($_GET['status'] ?? 'All');
$validStatuses = ['All', 'Open', 'In Progress', 'Resolved', 'Closed', 'Reopened'];
if (!in_array($statusFilter, $validStatuses, true)) $statusFilter = 'All';

$allTickets  = $ctrl->getUserTickets($userId);

$grouped = [
    'All'         => $allTickets,
    'Open'        => [],
    'In Progress' => [],
    'Resolved'    => [],
    'Closed'      => [],
];
foreach ($allTickets as $t) {
    $s = (string)($t->CURRENT_STATUS ?? '');
    if (isset($grouped[$s])) $grouped[$s][] = $t;
    if ($s === 'Reopened') {
        $grouped['Open'][] = $t;
    }
}

function tktStatusBadge(string $s): string {
    $map = [
        'Open'        => 'background:#dbeafe;color:#1d4ed8',
        'In Progress' => 'background:#fef3c7;color:#92400e',
        'Resolved'    => 'background:#d1fae5;color:#065f46',
        'Closed'      => 'background:#f1f5f9;color:#475569',
        'Reopened'    => 'background:#fce7f3;color:#9d174d',
    ];
    $style = $map[$s] ?? 'background:#f1f5f9;color:#475569';
    return '<span style="' . $style . ';font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;white-space:nowrap">' . htmlspecialchars($s) . '</span>';
}

function tktPriBadge(string $p): string {
    $map = [
        'Urgent' => '#dc2626',
        'High'   => '#ea580c',
        'Normal' => '#64748b',
        'Low'    => '#94a3b8',
    ];
    $c = $map[$p] ?? '#64748b';
    return '<span style="color:' . $c . ';font-size:11px;font-weight:700">' . htmlspecialchars($p) . '</span>';
}
?>

<main class="account-page">
  <div class="wrap">
    <div class="account-shell">
      <?php sinelec_render_account_nav($currentPage); ?>

      <section class="account-main">
        <article class="account-panel tickets-shell">

          <!-- Page Head -->
          <div class="tkt-page-head">
            <div>
              <h1>Support &amp; Help</h1>
              <p>Raise tickets, track issues, and chat with our support team.</p>
            </div>
            <a href="new-ticket" class="tkt-new-btn">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              New Ticket
            </a>
          </div>

          <!-- Stats bar -->
          <div class="tkt-stats-bar">
            <?php
            $statDefs = [
                ['label'=>'Total',       'key'=>'All',         'color'=>'#3b82f6'],
                ['label'=>'Open',        'key'=>'Open',        'color'=>'#f59e0b'],
                ['label'=>'In Progress', 'key'=>'In Progress', 'color'=>'#8b5cf6'],
                ['label'=>'Resolved',    'key'=>'Resolved',    'color'=>'#10b981'],
                ['label'=>'Closed',      'key'=>'Closed',      'color'=>'#6b7280'],
            ];
            foreach ($statDefs as $sd):
                $cnt = count($grouped[$sd['key']] ?? []);
            ?>
            <div class="tkt-stat-item">
              <span class="tkt-stat-num" style="color:<?= $sd['color'] ?>"><?= $cnt ?></span>
              <span class="tkt-stat-label"><?= $sd['label'] ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Tabs -->
          <div class="tkt-tab-row" role="tablist">
            <?php foreach (['All','Open','In Progress','Resolved','Closed'] as $tab): ?>
            <button type="button"
              class="tkt-tab-btn<?= $statusFilter === $tab ? ' is-active' : '' ?>"
              data-tab="<?= htmlspecialchars($tab) ?>"
              role="tab"
              aria-selected="<?= $statusFilter === $tab ? 'true' : 'false' ?>">
              <?= htmlspecialchars($tab) ?>
              <span class="tkt-tab-count"><?= count($grouped[$tab] ?? []) ?></span>
            </button>
            <?php endforeach; ?>
          </div>

          <!-- Ticket lists -->
          <?php foreach (['All','Open','In Progress','Resolved','Closed'] as $panel): ?>
          <div class="tkt-panel<?= $statusFilter === $panel ? ' is-active' : '' ?>" data-panel="<?= htmlspecialchars($panel) ?>">
            <?php $list = $grouped[$panel] ?? []; ?>
            <?php if (empty($list)): ?>
            <div class="tkt-empty">
              <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" opacity=".3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              <p><?= $panel === 'All' ? 'No tickets yet. <a href="new-ticket">Raise your first ticket</a>.' : "No $panel tickets." ?></p>
            </div>
            <?php else: ?>
            <?php foreach ($list as $t):
              $tid    = (int)(float)($t->TICKET_ID ?? 0);
              $tno    = htmlspecialchars((string)($t->TICKET_NUMBER   ?? ''));
              $tsubj  = htmlspecialchars((string)($t->SUBJECT         ?? ''));
              $tstat  = (string)($t->CURRENT_STATUS ?? '');
              $tpri   = (string)($t->PRIORITY       ?? 'Normal');
              $tcat   = htmlspecialchars((string)($t->CATEGORY_NAME   ?? ''));
              $tctype = (string)($t->CATEGORY_TYPE  ?? '');
              $torder = htmlspecialchars((string)($t->ORDER_NUMBER    ?? ''));
              $tdate  = $ctrl->fmtDate((string)($t->CREATED_AT       ?? ''));
              $tmsgAt = (string)($t->LAST_MSG_AT    ?? $t->UPDATED_AT ?? '');
              $tago   = $tmsgAt ? $ctrl->timeAgo($tmsgAt) : '';
              $tmsg   = htmlspecialchars(mb_substr(strip_tags((string)($t->LAST_MESSAGE ?? '')), 0, 90));
              $tcnt   = (int)($t->MSG_COUNT ?? 0);
              $isReturn = in_array($tctype, ['Return & Refund','Return & Replacement'], true);
            ?>
            <a href="ticket-detail?id=<?= $tno ?>" class="tkt-card">
              <div class="tkt-card-left">
                <div class="tkt-card-icon tkt-icon-<?= $isReturn ? 'return' : (str_contains($tctype,'Payment') ? 'payment' : 'other') ?>">
                  <?php if ($isReturn): ?>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                  <?php elseif (str_contains($tctype,'Payment')): ?>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                  <?php else: ?>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                  <?php endif; ?>
                </div>
              </div>

              <div class="tkt-card-body">
                <div class="tkt-card-top">
                  <span class="tkt-card-num">#<?= $tno ?></span>
                  <span class="tkt-card-cat"><?= $tcat ?></span>
                  <?php if ($torder): ?><span class="tkt-card-order">Order: <?= $torder ?></span><?php endif; ?>
                </div>
                <h3 class="tkt-card-subject"><?= $tsubj ?></h3>
                <?php if ($tmsg): ?><p class="tkt-card-preview"><?= $tmsg ?></p><?php endif; ?>
                <div class="tkt-card-meta">
                  <span><?= $tcnt ?> message<?= $tcnt !== 1 ? 's' : '' ?></span>
                  <span>·</span>
                  <span>Created <?= $tdate ?></span>
                  <?php if ($tago): ?><span>·</span><span>Last activity <?= $tago ?></span><?php endif; ?>
                </div>
              </div>

              <div class="tkt-card-right">
                <?= tktStatusBadge($tstat) ?>
                <div style="margin-top:8px"><?= tktPriBadge($tpri) ?></div>
                <svg class="tkt-card-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
              </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>

        </article>
      </section>
    </div>
  </div>
</main>

<style>
.tickets-shell { padding:18px; background:#f3f5f7; border-radius:20px; }
.tkt-page-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.tkt-page-head h1 { font-size:clamp(1.2rem,2vw,1.6rem); color:#182a43; margin:0 0 4px; }
.tkt-page-head p  { color:#5f728b; font-size:12px; margin:0; }
.tkt-new-btn {
  display:inline-flex; align-items:center; gap:7px;
  padding:10px 20px; border-radius:10px;
  background:#1d4ed8; color:#fff; font-size:13px; font-weight:700;
  text-decoration:none; white-space:nowrap;
  transition:background .15s;
}
.tkt-new-btn:hover { background:#1e40af; }

/* Stats bar */
.tkt-stats-bar {
  display:flex; gap:0; background:#fff; border-radius:12px;
  border:1px solid #e2e8f0; margin-bottom:14px; overflow:hidden;
}
.tkt-stat-item {
  flex:1; display:flex; flex-direction:column; align-items:center;
  padding:12px 8px; border-right:1px solid #e8edf3;
}
.tkt-stat-item:last-child { border-right:none; }
.tkt-stat-num   { font-size:22px; font-weight:800; line-height:1; }
.tkt-stat-label { font-size:10px; color:#64748b; font-weight:600; margin-top:3px; text-transform:uppercase; letter-spacing:.04em; }

/* Tabs */
.tkt-tab-row {
  display:inline-flex; gap:6px; padding:4px; border-radius:12px;
  background:#e8edf3; border:1px solid #d6dde6; margin-bottom:14px;
  flex-wrap:wrap;
}
.tkt-tab-btn {
  min-height:34px; padding:0 14px; border-radius:8px;
  border:1px solid transparent; background:transparent;
  color:#324b69; font-size:12px; font-weight:700; cursor:pointer;
  display:inline-flex; align-items:center; gap:6px;
  transition:all .15s;
}
.tkt-tab-btn.is-active { background:#fff; color:#112d4b; border-color:#d3dbe5; box-shadow:0 2px 8px rgba(20,33,56,.06); }
.tkt-tab-count {
  background:#e2e8f0; color:#475569; font-size:10px; font-weight:700;
  border-radius:10px; padding:1px 7px; min-width:20px; text-align:center;
}
.tkt-tab-btn.is-active .tkt-tab-count { background:#dbeafe; color:#1d4ed8; }

/* Panels */
.tkt-panel { display:none; flex-direction:column; gap:8px; }
.tkt-panel.is-active { display:flex; }

/* Empty */
.tkt-empty { text-align:center; padding:48px 20px; color:#94a3b8; display:flex; flex-direction:column; align-items:center; gap:10px; }
.tkt-empty p { font-size:14px; }
.tkt-empty a { color:#3b82f6; text-decoration:none; font-weight:600; }

/* Card */
.tkt-card {
  display:flex; gap:14px; align-items:flex-start;
  padding:14px 16px; border-radius:12px;
  background:#fff; border:1px solid #e2e8f0;
  text-decoration:none; color:inherit;
  transition:border-color .15s, box-shadow .15s;
}
.tkt-card:hover { border-color:#93c5fd; box-shadow:0 4px 16px rgba(59,130,246,.1); }

.tkt-card-left { flex-shrink:0; }
.tkt-card-icon {
  width:42px; height:42px; border-radius:10px;
  display:flex; align-items:center; justify-content:center;
  flex-shrink:0;
}
.tkt-icon-return  { background:#ede9fe; color:#7c3aed; }
.tkt-icon-payment { background:#fef3c7; color:#d97706; }
.tkt-icon-other   { background:#e0f2fe; color:#0369a1; }

.tkt-card-body { flex:1; min-width:0; }
.tkt-card-top { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px; }
.tkt-card-num   { font-size:11px; font-weight:700; color:#3b82f6; }
.tkt-card-cat   { font-size:11px; color:#64748b; background:#f1f5f9; padding:2px 8px; border-radius:6px; }
.tkt-card-order { font-size:11px; color:#8b5cf6; background:#f5f3ff; padding:2px 8px; border-radius:6px; }
.tkt-card-subject {
  font-size:14px; font-weight:700; color:#1a2332; margin:0 0 4px;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.tkt-card-preview { font-size:12px; color:#64748b; margin:0 0 6px; line-height:1.5;
  display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden; }
.tkt-card-meta { font-size:11px; color:#94a3b8; display:flex; gap:6px; flex-wrap:wrap; }

.tkt-card-right { flex-shrink:0; display:flex; flex-direction:column; align-items:flex-end; gap:4px; padding-top:2px; }
.tkt-card-arrow { color:#cbd5e1; margin-top:auto; }

@media (max-width:640px) {
  .tkt-stats-bar { display:grid; grid-template-columns:repeat(3,1fr); }
  .tkt-stat-item { border-right:1px solid #e8edf3; border-bottom:1px solid #e8edf3; }
  .tkt-card { padding:12px; gap:10px; }
  .tkt-card-icon { width:36px; height:36px; }
  .tkt-card-subject { font-size:13px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var tabs   = Array.from(document.querySelectorAll('.tkt-tab-btn'));
  var panels = Array.from(document.querySelectorAll('.tkt-panel'));

  tabs.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var tab = btn.getAttribute('data-tab');
      tabs.forEach(function(b) {
        var active = b.getAttribute('data-tab') === tab;
        b.classList.toggle('is-active', active);
        b.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      panels.forEach(function(p) {
        p.classList.toggle('is-active', p.getAttribute('data-panel') === tab);
      });
    });
  });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
