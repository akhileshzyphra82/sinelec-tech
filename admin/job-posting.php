<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'job-posting';
$pageTitle   = 'Job Posting';

$controller = new AdminController();
$canView    = sinelec_can('view');
$canAdd     = sinelec_can('add');
$canEdit    = sinelec_can('edit');
$canDelete  = sinelec_can('delete');

if (!$canView) {
    sinelec_set_flash('err', 'Access denied.');
    header('location:dashboard'); exit();
}

$jobs       = $controller->getAllJobs();
$applicants = $controller->getAllApplicants();

$totalJobs    = count($jobs);
$activeJobs   = count(array_filter($jobs, fn($j) => ($j->JOB_STATUS ?? '') === 'Active'));
$inactiveJobs = $totalJobs - $activeJobs;
$totalApps    = count($applicants);

$jobsJs = array_map(fn($j) => [
    'id'     => (int)(float)($j->JOB_POST_ID    ?? 0),
    'pos'    => (string)($j->JOB_POSITION       ?? ''),
    'loc'    => (string)($j->JOB_LOCATION       ?? ''),
    'prio'   => (int)(float)($j->JOB_PRIORITY   ?? 0),
    'desc'   => (string)($j->JOB_DISCRIPTION    ?? ''),
    'status' => (string)($j->JOB_STATUS         ?? 'Active'),
    'cnt'    => (int)(float)($j->APPLICANT_COUNT ?? 0),
], $jobs);

ob_start();
?>
<!-- Quill CSS -->
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<style>
/* ── Stats ─────────────────────────────────────────────────────── */
.jp-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}
@media (max-width: 760px) { .jp-stats { grid-template-columns: repeat(2,1fr); } }
.jp-stat {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.jp-stat-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    display: grid; place-items: center;
    flex-shrink: 0;
}
.jp-stat-val  { font-size: 24px; font-weight: 800; color: #0f172a; line-height: 1; }
.jp-stat-lbl  { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

/* ── Tabs ───────────────────────────────────────────────────────── */
.jp-tab-bar {
    display: flex;
    gap: 2px;
    border-bottom: 2px solid var(--border);
    margin-bottom: 20px;
}
.jp-tab {
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    border: none;
    background: none;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    border-radius: 6px 6px 0 0;
    transition: color .15s, border-color .15s;
    display: flex;
    align-items: center;
    gap: 7px;
}
.jp-tab:hover { color: var(--primary); }
.jp-tab.is-active { color: var(--primary); border-bottom-color: var(--primary); }
.jp-tab-count {
    background: #eff6ff;
    color: var(--primary);
    border-radius: 10px;
    padding: 1px 7px;
    font-size: 11px;
    font-weight: 700;
}
.jp-tab.is-active .jp-tab-count { background: var(--primary); color: #fff; }

/* ── Toolbar ────────────────────────────────────────────────────── */
.jp-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}
.jp-search-wrap {
    position: relative;
    flex: 1;
    min-width: 200px;
    max-width: 320px;
}
.jp-search-wrap svg {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    pointer-events: none;
}
.jp-search-wrap input { padding-left: 32px; height: 36px; }

/* ── Priority Badge ─────────────────────────────────────────────── */
.jp-prio {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 9px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.jp-prio.high  { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
.jp-prio.med   { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.jp-prio.low   { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }

/* ── Applicant count link ───────────────────────────────────────── */
.jp-app-cnt {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s;
}
.jp-app-cnt:hover { background: #dbeafe; }
.jp-app-cnt.zero  { background: #f8fafc; color: #94a3b8; border-color: #e2e8f0; cursor: default; }

/* ── Candidate avatar ───────────────────────────────────────────── */
.jp-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    display: grid; place-items: center;
    font-size: 13px; font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}

/* ── Experience badge ───────────────────────────────────────────── */
.jp-exp {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    background: #f3f4f6;
    color: #374151;
}

/* ── Resume button ──────────────────────────────────────────────── */
.jp-resume-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
    text-decoration: none;
    transition: background .15s;
}
.jp-resume-btn:hover { background: #dcfce7; }

/* ── Pagination ─────────────────────────────────────────────────── */
.jp-pgbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    padding: 12px 18px;
    border-bottom: 1px solid var(--border);
    background: #fafbfc;
}
.jp-pgbar-info { font-size: 13px; color: var(--text-muted); }
.jp-pgbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.jp-pgbar-rpp {
    height: 32px;
    padding: 0 28px 0 10px;
    border: 1.5px solid var(--border);
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 10px center;
    -webkit-appearance: none; appearance: none;
    cursor: pointer; color: var(--text);
}
.jp-pgbar-rpp:focus { outline: none; border-color: var(--primary); }
.jp-pg-nav { display: flex; align-items: center; gap: 4px; }
.jp-pg-btn {
    min-width: 32px; height: 32px;
    padding: 0 8px;
    border: 1.5px solid var(--border);
    border-radius: 20px;
    font-size: 12px; font-weight: 500;
    background: #fff; color: var(--text);
    cursor: pointer;
    transition: border-color .15s, background .15s, color .15s;
    display: flex; align-items: center; justify-content: center;
}
.jp-pg-btn:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); background: #f0f4ff; }
.jp-pg-btn.active { background: var(--primary); border-color: var(--primary); color: #fff; font-weight: 700; pointer-events: none; }
.jp-pg-btn:disabled { opacity: .38; cursor: not-allowed; }
.jp-pg-dots { font-size: 13px; color: var(--text-muted); padding: 0 4px; }

/* ── Desc preview (stripped) ────────────────────────────────────── */
.jp-desc-preview {
    font-size: 12px;
    color: var(--text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 260px;
}

/* ── View desc modal ────────────────────────────────────────────── */
.jp-desc-body { font-size: 13px; line-height: 1.7; color: var(--text); }
.jp-desc-body h1,.jp-desc-body h2,.jp-desc-body h3 { margin: 14px 0 6px; font-weight: 700; }
.jp-desc-body ul,.jp-desc-body ol { padding-left: 20px; margin: 8px 0; }
.jp-desc-body li { margin-bottom: 4px; }
.jp-desc-body p  { margin: 6px 0; }
.jp-desc-body a  { color: var(--primary); }

/* ── No-results row ─────────────────────────────────────────────── */
.jp-no-results {
    display: none;
    padding: 40px;
    text-align: center;
    color: var(--text-muted);
    font-size: 13px;
}
</style>

<!-- ══════════════════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════════════════ -->
<div class="pg-header">
    <div>
        <div class="pg-title">Job Posting</div>
        <div class="pg-subtitle">Manage open positions and review candidate applications.</div>
    </div>
    <?php if ($canAdd): ?>
    <button class="btn btn--primary" onclick="openJobModal(0)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Job Post
    </button>
    <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════════════════
     STATS
══════════════════════════════════════════════════════ -->
<div class="jp-stats">
    <div class="jp-stat">
        <div class="jp-stat-icon" style="background:#eff6ff;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        </div>
        <div>
            <div class="jp-stat-val"><?= $totalJobs ?></div>
            <div class="jp-stat-lbl">Total Positions</div>
        </div>
    </div>
    <div class="jp-stat">
        <div class="jp-stat-icon" style="background:#f0fdf4;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
            <div class="jp-stat-val" style="color:#15803d;"><?= $activeJobs ?></div>
            <div class="jp-stat-lbl">Active</div>
        </div>
    </div>
    <div class="jp-stat">
        <div class="jp-stat-icon" style="background:#fff5f5;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div>
            <div class="jp-stat-val" style="color:#dc2626;"><?= $inactiveJobs ?></div>
            <div class="jp-stat-lbl">Inactive</div>
        </div>
    </div>
    <div class="jp-stat">
        <div class="jp-stat-icon" style="background:#fdf4ff;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9333ea" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <div class="jp-stat-val" style="color:#9333ea;"><?= $totalApps ?></div>
            <div class="jp-stat-lbl">Total Applications</div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════
     TAB BAR
══════════════════════════════════════════════════════ -->
<div class="jp-tab-bar">
    <button class="jp-tab is-active" id="tabBtnPosts" onclick="switchTab('posts')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        Job Posts
        <span class="jp-tab-count" id="tabCntPosts"><?= $totalJobs ?></span>
    </button>
    <button class="jp-tab" id="tabBtnApps" onclick="switchTab('apps')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Applications
        <span class="jp-tab-count" id="tabCntApps"><?= $totalApps ?></span>
    </button>
</div>

<!-- ══════════════════════════════════════════════════════
     TAB: JOB POSTS
══════════════════════════════════════════════════════ -->
<div id="panelPosts">

    <!-- Toolbar -->
    <div class="jp-toolbar">
        <div class="jp-search-wrap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="jobSearch" class="form-control" placeholder="Search position or location…" oninput="filterJobs()">
        </div>
        <select id="jobStatusFilter" class="form-control" style="height:36px;max-width:160px;" onchange="filterJobs()">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="In-Active">Inactive</option>
        </select>
    </div>

    <?php if (empty($jobs)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                <h3>No job posts yet</h3>
                <p>Create your first open position to start receiving applications.</p>
                <?php if ($canAdd): ?><button class="btn btn--primary" onclick="openJobModal(0)">Add First Post</button><?php endif; ?>
            </div>
        </div>
    </div>
    <?php else: ?>

    <div class="card">
        <!-- Pagination bar -->
        <div class="jp-pgbar" id="jobPgBar">
            <div class="jp-pgbar-info" id="jobPgInfo">Showing 1–<?= min(10,$totalJobs) ?> of <?= $totalJobs ?> positions</div>
            <div class="jp-pgbar-right">
                <span style="font-size:13px;font-weight:600;color:var(--text);">Per page</span>
                <select id="jobRpp" class="jp-pgbar-rpp">
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
                <div class="jp-pg-nav" id="jobNav"></div>
            </div>
        </div>

        <div class="card-body card-body--flush">
            <table class="dt" id="jobTable">
                <thead>
                    <tr>
                        <th style="width:36px;">#</th>
                        <th>Position</th>
                        <th>Description</th>
                        <th style="width:110px;">Location</th>
                        <th style="width:80px;text-align:center;">Priority</th>
                        <th style="width:110px;text-align:center;">Applicants</th>
                        <th style="width:90px;">Status</th>
                        <?php if ($canEdit || $canDelete): ?>
                        <th style="width:50px;text-align:center;">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="jobTbody">
                    <?php foreach ($jobs as $i => $j):
                        $jid  = (int)(float)($j->JOB_POST_ID    ?? 0);
                        $pos  = htmlspecialchars($j->JOB_POSITION  ?? '');
                        $loc  = htmlspecialchars($j->JOB_LOCATION  ?? '—');
                        $prio = (int)(float)($j->JOB_PRIORITY      ?? 0);
                        $cnt  = (int)(float)($j->APPLICANT_COUNT   ?? 0);
                        $sts  = (string)($j->JOB_STATUS            ?? 'Active');
                        $desc = strip_tags((string)($j->JOB_DISCRIPTION ?? ''));
                        $prioClass = $prio >= 3 ? 'high' : ($prio === 2 ? 'med' : 'low');
                        $prioLabel = $prio >= 3 ? 'High' : ($prio === 2 ? 'Medium' : 'Low');
                        $searchStr = strtolower(($j->JOB_POSITION ?? '').' '.($j->JOB_LOCATION ?? '').' '.$sts);
                    ?>
                    <tr data-search="<?= htmlspecialchars($searchStr) ?>" data-status="<?= htmlspecialchars($sts) ?>" data-seq="<?= $i+1 ?>">
                        <td class="td-sm job-sno"><?= $i+1 ?></td>
                        <td>
                            <div style="font-weight:600;color:var(--text);font-size:13px;"><?= $pos ?></div>
                        </td>
                        <td>
                            <?php if ($desc): ?>
                            <div class="jp-desc-preview"><?= htmlspecialchars($desc) ?></div>
                            <button class="btn btn--outline" style="padding:2px 8px;font-size:11px;margin-top:4px;" onclick="viewDesc(<?= $jid ?>)">View</button>
                            <?php else: ?>
                            <span style="color:var(--text-muted);font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($j->JOB_LOCATION ?? '') !== ''): ?>
                            <div style="display:flex;align-items:center;gap:4px;font-size:12px;color:var(--text-muted);">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <?= $loc ?>
                            </div>
                            <?php else: ?><span style="color:var(--text-muted);font-size:12px;">—</span><?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <span class="jp-prio <?= $prioClass ?>"><?= $prioLabel ?> (<?= $prio ?>)</span>
                        </td>
                        <td style="text-align:center;">
                            <?php if ($cnt > 0): ?>
                            <a class="jp-app-cnt" onclick="switchTab('apps');filterAppsByJob(<?= $jid ?>)" href="javascript:void(0)">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                <?= $cnt ?>
                            </a>
                            <?php else: ?>
                            <span class="jp-app-cnt zero">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $sts === 'Active' ? 'badge--green' : 'badge--red' ?>">
                                <?= htmlspecialchars($sts) ?>
                            </span>
                        </td>
                        <?php if ($canEdit || $canDelete): ?>
                        <td>
                            <div class="kbm-wrap">
                                <button class="kbm-btn" onclick="toggleKbm(this)" title="Actions">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                                </button>
                                <div class="kbm-drop">
                                    <?php if ($canEdit): ?>
                                    <button class="kbm-item" onclick="closeKbm(this);openJobModal(<?= $jid ?>)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 013 3L12 15l-4 1 1-4z"/></svg>
                                        Edit Post
                                    </button>
                                    <?php endif; ?>
                                    <button class="kbm-item" onclick="closeKbm(this);switchTab('apps');filterAppsByJob(<?= $jid ?>)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                        View Applicants
                                    </button>
                                    <?php if ($canDelete): ?>
                                    <?php if ($canEdit): ?><div class="kbm-divider"></div><?php endif; ?>
                                    <button class="kbm-item kbm-item--danger"
                                        data-job-id="<?= $jid ?>"
                                        data-job-pos="<?= $pos ?>"
                                        data-job-cnt="<?= $cnt ?>"
                                        onclick="closeKbm(this);confirmDelJob(this)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                        Delete Post
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="jp-no-results" id="jobNoResults">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto 10px;opacity:.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                No job posts match your filters.
            </div>
        </div>
    </div>
    <?php endif; ?>
</div><!-- /panelPosts -->


<!-- ══════════════════════════════════════════════════════
     TAB: APPLICATIONS
══════════════════════════════════════════════════════ -->
<div id="panelApps" style="display:none;">

    <!-- Toolbar -->
    <div class="jp-toolbar">
        <div class="jp-search-wrap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="appSearch" class="form-control" placeholder="Search name or email…" oninput="filterApps()">
        </div>
        <select id="appJobFilter" class="form-control" style="height:36px;max-width:220px;" onchange="filterApps()">
            <option value="">All Positions</option>
            <?php foreach ($jobs as $j): ?>
            <option value="<?= (int)(float)($j->JOB_POST_ID ?? 0) ?>">
                <?= htmlspecialchars($j->JOB_POSITION ?? '') ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (empty($applicants)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                <h3>No applications yet</h3>
                <p>Candidates who apply through the careers page will appear here.</p>
            </div>
        </div>
    </div>
    <?php else: ?>

    <div class="card">
        <!-- Pagination bar -->
        <div class="jp-pgbar" id="appPgBar">
            <div class="jp-pgbar-info" id="appPgInfo">Showing 1–<?= min(10,$totalApps) ?> of <?= $totalApps ?> applications</div>
            <div class="jp-pgbar-right">
                <span style="font-size:13px;font-weight:600;color:var(--text);">Per page</span>
                <select id="appRpp" class="jp-pgbar-rpp">
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
                <div class="jp-pg-nav" id="appNav"></div>
            </div>
        </div>

        <div class="card-body card-body--flush">
            <table class="dt" id="appTable">
                <thead>
                    <tr>
                        <th style="width:36px;">#</th>
                        <th>Candidate</th>
                        <th>Contact</th>
                        <th style="width:80px;text-align:center;">Experience</th>
                        <th>Applied For</th>
                        <th style="width:100px;">Applied Date</th>
                        <th style="width:90px;">Resume</th>
                        <?php if ($canDelete): ?>
                        <th style="width:50px;text-align:center;">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="appTbody">
                    <?php
                    $avatarColors = ['#6366f1','#8b5cf6','#0891b2','#059669','#d97706','#dc2626','#7c3aed','#0284c7'];
                    foreach ($applicants as $i => $a):
                        $appId   = (int)(float)($a->CANDIDATE_APPLIED_JOB_ID ?? 0);
                        $jobId   = (int)(float)($a->JOB_POST_ID              ?? 0);
                        $name    = htmlspecialchars($a->CANDIDATE_NAME        ?? '');
                        $email   = htmlspecialchars($a->CANDIDATE_EMAIL       ?? '');
                        $phone   = (string)($a->CANDIDATE_PHONE              ?? '');
                        $exp     = (int)($a->CANDIDATE_EXPERIENCE            ?? 0);
                        $resExt  = (string)($a->RESUME_FILE_EXT              ?? '');
                        $appDate = $a->APPLIED_DATE ?? '';
                        $jobPos  = htmlspecialchars($a->JOB_POSITION          ?? '—');
                        $initial = strtoupper(substr(trim($a->CANDIDATE_NAME ?? ''), 0, 1)) ?: 'C';
                        $avatarBg = $avatarColors[$appId % count($avatarColors)];
                        $resSrc  = $resExt !== '' ? '../assets/uploads/resumes/'.$appId.'.'.$resExt : '';
                        $dateDisp = $appDate ? date('d M Y', strtotime($appDate)) : '—';
                        $expDisp  = $exp === 1 ? '1 yr' : ($exp > 1 ? $exp.' yrs' : 'Fresher');
                        $searchStr = strtolower(($a->CANDIDATE_NAME ?? '').' '.($a->CANDIDATE_EMAIL ?? ''));
                    ?>
                    <tr data-search="<?= htmlspecialchars($searchStr) ?>" data-job="<?= $jobId ?>" data-seq="<?= $i+1 ?>">
                        <td class="td-sm app-sno"><?= $i+1 ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="jp-avatar" style="background:<?= $avatarBg ?>;"><?= htmlspecialchars($initial) ?></div>
                                <div>
                                    <div style="font-weight:600;font-size:13px;color:var(--text);"><?= $name ?></div>
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:1px;"><?= $email ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($phone !== '' && (int)(float)$phone > 0): ?>
                            <div style="display:flex;align-items:center;gap:5px;font-size:12px;">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 014.36 12 19.79 19.79 0 011.19 3.38 2 2 0 013.17 1.2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L7.09 8.91A16 16 0 0013 14.83l1.27-1.27a2 2 0 012.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                                <?= htmlspecialchars((string)(int)(float)$phone) ?>
                            </div>
                            <?php else: ?>
                            <span style="color:var(--text-muted);font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <span class="jp-exp"><?= htmlspecialchars($expDisp) ?></span>
                        </td>
                        <td>
                            <span class="badge badge--blue"><?= $jobPos ?></span>
                        </td>
                        <td style="font-size:12px;color:var(--text-muted);"><?= $dateDisp ?></td>
                        <td>
                            <?php if ($resSrc): ?>
                            <a href="<?= htmlspecialchars($resSrc) ?>" target="_blank" class="jp-resume-btn">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Download
                            </a>
                            <?php else: ?>
                            <span style="color:var(--text-muted);font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($canDelete): ?>
                        <td>
                            <div class="kbm-wrap">
                                <button class="kbm-btn" onclick="toggleKbm(this)" title="Actions">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                                </button>
                                <div class="kbm-drop">
                                    <button class="kbm-item kbm-item--danger"
                                        data-app-id="<?= $appId ?>"
                                        data-app-name="<?= $name ?>"
                                        onclick="closeKbm(this);confirmDelApp(this)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="jp-no-results" id="appNoResults">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto 10px;opacity:.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                No applications match your filters.
            </div>
        </div>
    </div>
    <?php endif; ?>
</div><!-- /panelApps -->


<!-- ══════════════════════════════════════════════════════
     MODAL: ADD / EDIT JOB
══════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="jobModal">
    <div class="modal" style="max-width:620px;max-height:92vh;display:flex;flex-direction:column;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
            <div>
                <div class="modal-title" id="jobModalTitle">Add Job Post</div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="jobModalSub">Fill in the position details below.</div>
            </div>
            <button class="modal-close" onclick="closeModal('jobModal')" style="font-size:22px;line-height:1;">×</button>
        </div>
        <div style="overflow-y:auto;flex:1;padding:22px;">
            <form method="POST" id="jobForm" autocomplete="off">
                <input type="hidden" name="job_post_id" id="fJobId" value="0">
                <input type="hidden" name="job_discription" id="fJobDescHidden">

                <!-- Position -->
                <div class="fg" style="margin-bottom:14px;">
                    <label class="form-label">Position / Title <span class="req">*</span></label>
                    <input type="text" name="job_position" id="fJobPos" class="form-control" placeholder="e.g. Senior Electronics Engineer" required>
                </div>

                <!-- Location + Priority -->
                <div class="form-row cols-2" style="margin-bottom:14px;">
                    <div class="fg">
                        <label class="form-label">Location</label>
                        <input type="text" name="job_location" id="fJobLoc" class="form-control" placeholder="e.g. Berlin, Remote">
                    </div>
                    <div class="fg">
                        <label class="form-label">Priority
                            <span style="font-size:11px;color:var(--text-muted);font-weight:400;">(1=Low, 2=Med, 3+=High)</span>
                        </label>
                        <input type="number" name="job_priority" id="fJobPrio" class="form-control" value="1" min="0" max="10">
                    </div>
                </div>

                <!-- Status -->
                <div class="fg" style="margin-bottom:14px;">
                    <label class="form-label">Status</label>
                    <select name="job_status" id="fJobStatus" class="form-control">
                        <option value="Active">Active</option>
                        <option value="In-Active">Inactive</option>
                    </select>
                </div>

                <!-- Description (Quill) -->
                <div class="fg" style="margin-bottom:20px;">
                    <label class="form-label">Job Description & Requirements</label>
                    <div id="jobDescEditor" style="min-height:200px;border-radius:0 0 6px 6px;font-size:13px;"></div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Use formatting to highlight requirements, responsibilities, and benefits.</div>
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:14px;border-top:1px solid var(--border);">
                    <button type="button" class="btn btn--outline" onclick="closeModal('jobModal')">Cancel</button>
                    <button type="submit" class="btn btn--primary" id="jobSubmitBtn">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════
     MODAL: VIEW DESCRIPTION
══════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="descModal">
    <div class="modal" style="max-width:640px;max-height:88vh;display:flex;flex-direction:column;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
            <div>
                <div class="modal-title" id="descModalTitle">Job Description</div>
            </div>
            <button class="modal-close" onclick="closeModal('descModal')" style="font-size:22px;line-height:1;">×</button>
        </div>
        <div style="overflow-y:auto;flex:1;padding:22px;">
            <div class="jp-desc-body" id="descModalBody"></div>
        </div>
        <div style="padding:14px 22px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;flex-shrink:0;">
            <button class="btn btn--outline" onclick="closeModal('descModal')">Close</button>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════
     MODAL: DELETE JOB
══════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="delJobModal">
    <div class="modal modal--sm" style="max-width:420px;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <span class="modal-title">Delete Job Post</span>
            <button class="modal-close" onclick="closeModal('delJobModal')">×</button>
        </div>
        <div class="modal-body">
            <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:18px;">
                <div style="width:40px;height:40px;border-radius:50%;background:#fff5f5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div>
                    <div style="font-weight:600;margin-bottom:5px;font-size:14px;">Are you sure?</div>
                    <div style="font-size:13px;color:var(--text-muted);" id="delJobMsg"></div>
                </div>
            </div>
            <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteJob') ?>">
                <input type="hidden" name="job_post_id" id="delJobId">
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn btn--outline" onclick="closeModal('delJobModal')">Cancel</button>
                    <button type="submit" class="btn" style="background:#dc2626;color:#fff;border:1px solid #dc2626;" id="delJobBtn">Delete Post</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════
     MODAL: DELETE APPLICANT
══════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="delAppModal">
    <div class="modal modal--sm" style="max-width:400px;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <span class="modal-title">Delete Application</span>
            <button class="modal-close" onclick="closeModal('delAppModal')">×</button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);margin-bottom:18px;font-size:13px;">
                Delete application from <strong id="delAppName"></strong>? This cannot be undone.
            </p>
            <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteApplicant') ?>">
                <input type="hidden" name="candidate_applied_job_id" id="delAppId">
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn btn--outline" onclick="closeModal('delAppModal')">Cancel</button>
                    <button type="submit" class="btn" style="background:#dc2626;color:#fff;border:1px solid #dc2626;">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════
     EMBEDDED DATA + JS
══════════════════════════════════════════════════════ -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
/* ── Baked data ────────────────────────────────────────────────── */
const JOBS_DATA = <?= json_encode(array_values($jobsJs)) ?>;

/* ── Quill (lazy init on first modal open) ─────────────────────── */
var _qJob = null;
function getQuill() {
    if (!_qJob) {
        _qJob = new Quill('#jobDescEditor', {
            theme: 'snow',
            modules: { toolbar: [
                [{ header: [1,2,3,false] }],
                ['bold','italic','underline','strike'],
                [{ list:'ordered' },{ list:'bullet' }],
                ['link'], ['clean']
            ]},
            placeholder: 'Describe responsibilities, requirements, and benefits…'
        });
    }
    return _qJob;
}

/* ── Tab switching ─────────────────────────────────────────────── */
function switchTab(tab) {
    var isPosts = tab === 'posts';
    document.getElementById('panelPosts').style.display  = isPosts ? '' : 'none';
    document.getElementById('panelApps').style.display   = isPosts ? 'none' : '';
    document.getElementById('tabBtnPosts').classList.toggle('is-active', isPosts);
    document.getElementById('tabBtnApps').classList.toggle('is-active', !isPosts);
    history.replaceState(null, '', '#' + tab);
}

/* ── Filter: jobs ──────────────────────────────────────────────── */
var _jobFiltered = [];
var _jobPage = 1, _jobRpp = 10;

function filterJobs() {
    var q    = (document.getElementById('jobSearch')?.value || '').toLowerCase().trim();
    var sts  = document.getElementById('jobStatusFilter')?.value || '';
    var rows = Array.from(document.querySelectorAll('#jobTbody tr'));
    _jobFiltered = rows.filter(function(r) {
        var matchQ   = !q   || r.dataset.search.includes(q);
        var matchSts = !sts || r.dataset.status === sts;
        return matchQ && matchSts;
    });
    _jobPage = 1;
    renderJobPage();
}

function renderJobPage() {
    var rows  = Array.from(document.querySelectorAll('#jobTbody tr'));
    var total = _jobFiltered.length;
    var pages = Math.max(1, Math.ceil(total / _jobRpp));
    _jobPage  = Math.min(_jobPage, pages);
    var start = (_jobPage - 1) * _jobRpp;
    var end   = Math.min(start + _jobRpp, total);

    rows.forEach(function(r) { r.style.display = 'none'; });
    _jobFiltered.slice(start, end).forEach(function(r, idx) {
        r.style.display = '';
        var sno = r.querySelector('.job-sno');
        if (sno) sno.textContent = start + idx + 1;
    });

    var noRes = document.getElementById('jobNoResults');
    if (noRes) noRes.style.display = total === 0 ? 'block' : 'none';

    var info = document.getElementById('jobPgInfo');
    if (info) info.textContent = total === 0 ? 'No positions found' : 'Showing '+(start+1)+'–'+end+' of '+total+' positions';

    renderPgNav('jobNav', 'goJobPage', _jobPage, pages);
}

/* ── Filter: apps ──────────────────────────────────────────────── */
var _appFiltered = [];
var _appPage = 1, _appRpp = 10;

function filterApps() {
    var q    = (document.getElementById('appSearch')?.value || '').toLowerCase().trim();
    var jid  = parseInt(document.getElementById('appJobFilter')?.value || '0', 10) || 0;
    var rows = Array.from(document.querySelectorAll('#appTbody tr'));
    _appFiltered = rows.filter(function(r) {
        var matchQ  = !q   || r.dataset.search.includes(q);
        var matchJ  = !jid || parseInt(r.dataset.job, 10) === jid;
        return matchQ && matchJ;
    });
    _appPage = 1;
    renderAppPage();
}

function filterAppsByJob(jobId) {
    var sel = document.getElementById('appJobFilter');
    if (sel) sel.value = jobId;
    filterApps();
}

function renderAppPage() {
    var rows  = Array.from(document.querySelectorAll('#appTbody tr'));
    var total = _appFiltered.length;
    var pages = Math.max(1, Math.ceil(total / _appRpp));
    _appPage  = Math.min(_appPage, pages);
    var start = (_appPage - 1) * _appRpp;
    var end   = Math.min(start + _appRpp, total);

    rows.forEach(function(r) { r.style.display = 'none'; });
    _appFiltered.slice(start, end).forEach(function(r, idx) {
        r.style.display = '';
        var sno = r.querySelector('.app-sno');
        if (sno) sno.textContent = start + idx + 1;
    });

    var noRes = document.getElementById('appNoResults');
    if (noRes) noRes.style.display = total === 0 ? 'block' : 'none';

    var info = document.getElementById('appPgInfo');
    if (info) info.textContent = total === 0 ? 'No applications found' : 'Showing '+(start+1)+'–'+end+' of '+total+' applications';

    renderPgNav('appNav', 'goAppPage', _appPage, pages);
}

/* ── Named page-jump globals ────────────────────────────────────── */
window.goJobPage = function(p) { _jobPage = p; renderJobPage(); };
window.goAppPage = function(p) { _appPage = p; renderAppPage(); };

/* ── Shared pagination renderer ────────────────────────────────── */
function renderPgNav(containerId, fnName, cur, pages) {
    var nav = document.getElementById(containerId);
    if (!nav) return;
    var html = '<button class="jp-pg-btn" onclick="'+fnName+'('+(cur-1)+')"'+(cur<=1?' disabled':'')+'>&lsaquo;</button>';
    buildNums(cur, pages).forEach(function(p) {
        if (p==='...') {
            html += '<span class="jp-pg-dots">…</span>';
        } else {
            html += '<button class="jp-pg-btn'+(p===cur?' active':'')+'" onclick="'+fnName+'('+p+')">'+p+'</button>';
        }
    });
    html += '<button class="jp-pg-btn" onclick="'+fnName+'('+(cur+1)+')"'+(cur>=pages?' disabled':'')+'>&rsaquo;</button>';
    nav.innerHTML = html;
}
function buildNums(cur, pages) {
    if (pages<=7) { var a=[]; for(var i=1;i<=pages;i++) a.push(i); return a; }
    if (cur<=4)        return [1,2,3,4,5,'...',pages];
    if (cur>=pages-3)  return [1,'...',pages-4,pages-3,pages-2,pages-1,pages];
    return [1,'...',cur-1,cur,cur+1,'...',pages];
}

/* ── RPP change ─────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    var jobRpp = document.getElementById('jobRpp');
    var appRpp = document.getElementById('appRpp');
    if (jobRpp) jobRpp.addEventListener('change', function(){ _jobRpp=parseInt(this.value)||10; _jobPage=1; renderJobPage(); });
    if (appRpp) appRpp.addEventListener('change', function(){ _appRpp=parseInt(this.value)||10; _appPage=1; renderAppPage(); });

    /* Initialize filtered arrays and render */
    _jobFiltered = Array.from(document.querySelectorAll('#jobTbody tr'));
    renderJobPage();
    _appFiltered = Array.from(document.querySelectorAll('#appTbody tr'));
    renderAppPage();

    /* Restore tab from hash */
    var hash = window.location.hash.replace('#','');
    if (hash === 'apps') switchTab('apps');
});

/* ── Add / Edit Job modal ──────────────────────────────────────── */
var _saveSvg = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>';

function openJobModal(jobId) {
    var q = getQuill();
    document.getElementById('jobForm').reset();
    document.getElementById('fJobId').value = jobId;

    if (jobId > 0) {
        var d = JOBS_DATA.find(function(j){ return j.id === jobId; });
        if (d) {
            document.getElementById('fJobPos').value    = d.pos;
            document.getElementById('fJobLoc').value    = d.loc;
            document.getElementById('fJobPrio').value   = d.prio;
            document.getElementById('fJobStatus').value = d.status;
            q.enable(); q.root.innerHTML = '';
            if (d.desc) q.clipboard.dangerouslyPasteHTML(d.desc);
        }
        document.getElementById('jobModalTitle').textContent = 'Edit Job Post';
        document.getElementById('jobModalSub').textContent   = 'Update the position details.';
        document.getElementById('jobSubmitBtn').innerHTML     = _saveSvg + ' Update Post';
        document.getElementById('jobForm').action = 'service?urlstring=<?= EncryptURL('action=UpdateJob') ?>';
    } else {
        q.enable(); q.setText('');
        document.getElementById('fJobPrio').value = 1;
        document.getElementById('jobModalTitle').textContent = 'Add Job Post';
        document.getElementById('jobModalSub').textContent   = 'Fill in the position details below.';
        document.getElementById('jobSubmitBtn').innerHTML     = _saveSvg + ' Add Post';
        document.getElementById('jobForm').action = 'service?urlstring=<?= EncryptURL('action=InsertJob') ?>';
    }
    openModal('jobModal');
}

/* Sync Quill → hidden input on submit */
document.getElementById('jobForm').addEventListener('submit', function() {
    var q = getQuill();
    var h = q ? q.root.innerHTML : '';
    document.getElementById('fJobDescHidden').value = (h === '<p><br></p>') ? '' : h;
});

/* ── View description ──────────────────────────────────────────── */
function viewDesc(jobId) {
    var d = JOBS_DATA.find(function(j){ return j.id === jobId; });
    if (!d) return;
    document.getElementById('descModalTitle').textContent = d.pos;
    document.getElementById('descModalBody').innerHTML    = d.desc || '<p style="color:var(--text-muted);">No description provided.</p>';
    openModal('descModal');
}

/* ── Delete job ────────────────────────────────────────────────── */
function confirmDelJob(btn) {
    var jobId = parseInt(btn.getAttribute('data-job-id'), 10);
    var pos   = btn.getAttribute('data-job-pos') || '';
    var cnt   = parseInt(btn.getAttribute('data-job-cnt'), 10) || 0;
    document.getElementById('delJobId').value = jobId;
    var msg = 'You are about to permanently delete <strong>' + pos + '</strong>.';
    if (cnt > 0) {
        msg += '<br><br><span style="color:#dc2626;font-weight:600;">⚠ This post has ' + cnt + ' applicant(s). Delete all applicants first before deleting this post.</span>';
        document.getElementById('delJobBtn').disabled = true;
    } else {
        document.getElementById('delJobBtn').disabled = false;
    }
    document.getElementById('delJobMsg').innerHTML = msg;
    openModal('delJobModal');
}

/* ── Delete applicant ──────────────────────────────────────────── */
function confirmDelApp(btn) {
    var appId = parseInt(btn.getAttribute('data-app-id'), 10);
    var name  = btn.getAttribute('data-app-name') || '';
    document.getElementById('delAppId').value         = appId;
    document.getElementById('delAppName').textContent = name;
    openModal('delAppModal');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
