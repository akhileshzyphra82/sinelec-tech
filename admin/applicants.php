<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'applicants';
$pageTitle   = 'Job Applications';

$controller = new AdminController();
$jobs       = $controller->getAllJobs();

$filters = [
    'job_id' => (int)($_GET['job_id'] ?? 0),
    'search' => trim($_GET['search'] ?? ''),
];
$applicants = $controller->getAllApplicants($filters);

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Job Applications</div>
    <div class="pg-subtitle">Review candidates who have applied for open positions.</div>
  </div>
</div>

<!-- Filters -->
<form method="GET" action="applicants" class="filter-bar">
  <select name="job_id" class="form-control" style="max-width:240px;">
    <option value="">All Positions</option>
    <?php foreach ($jobs as $j): ?>
    <option value="<?= (int)($j->JOB_POST_ID ?? 0) ?>" <?= $filters['job_id'] == (int)($j->JOB_POST_ID ?? 0) ? 'selected' : '' ?>>
      <?= htmlspecialchars($j->JOB_POSITION ?? '') ?>
    </option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="search" class="form-control" style="max-width:240px;" placeholder="Name or email…" value="<?= htmlspecialchars($filters['search']) ?>">
  <button type="submit" class="btn btn--primary">Filter</button>
  <a href="applicants" class="btn btn--outline">Reset</a>
</form>

<div class="card">
  <div class="card-header">
    <span class="card-title">Applications</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($applicants) ?> found</span>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($applicants)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <p>No applications found.</p>
    </div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th>Candidate</th>
          <th>Email</th>
          <th>Mobile</th>
          <th>Position Applied</th>
          <th>Resume</th>
          <th>Applied Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($applicants as $a): ?>
        <?php
          $appId   = (int)($a->CANDIDATE_APPLIED_JOB_ID ?? 0);
          $name    = htmlspecialchars($a->CANDIDATE_NAME ?? '');
          $email   = htmlspecialchars($a->CANDIDATE_EMAIL ?? '');
          $mobile  = htmlspecialchars($a->CANDIDATE_MOBILE ?? '—');
          $pos     = htmlspecialchars($a->JOB_POSITION ?? '—');
          $resExt  = (string)($a->RESUME_EXT ?? '');
          $date    = htmlspecialchars(date('d M Y', strtotime($a->APPLIED_DATE ?? '')));
          $resSrc  = $resExt ? '../assets/uploads/resumes/'.$appId.'.'.$resExt : '';
        ?>
        <tr>
          <td style="font-weight:500;"><?= $name ?></td>
          <td style="font-size:12px;"><?= $email ?></td>
          <td style="font-size:12px;"><?= $mobile ?></td>
          <td>
            <span class="badge badge--blue"><?= $pos ?></span>
          </td>
          <td>
            <?php if ($resSrc): ?>
              <a href="<?= htmlspecialchars($resSrc) ?>" target="_blank" class="btn btn--outline" style="padding:3px 10px;font-size:11px;">Download</a>
            <?php else: ?>
              <span style="color:var(--text-muted);font-size:12px;">—</span>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--text-muted);"><?= $date ?></td>
          <td>
            <button class="btn" style="padding:4px 10px;font-size:12px;background:#fff5f5;color:#dc2626;border:1px solid #fecaca;"
              onclick="confirmDelApp(<?= $appId ?>, <?= htmlspecialchars(json_encode($name),ENT_QUOTES) ?>)">
              Delete
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <span class="modal-title">Delete Application</span>
      <button class="modal-close" onclick="closeModal('deleteModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-muted);margin-bottom:16px;">Delete application from <strong id="del_app_name"></strong>?</p>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteApplicant') ?>">
        <input type="hidden" name="candidate_applied_job_id" id="del_app_id">
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn" style="background:#dc2626;color:#fff;border:none;">Yes, Delete</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('deleteModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function confirmDelApp(id, name) {
  document.getElementById('del_app_id').value    = id;
  document.getElementById('del_app_name').textContent = name;
  openModal('deleteModal');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
