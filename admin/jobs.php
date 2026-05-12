<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'jobs';
$pageTitle   = 'Job Posts';

$controller = new AdminController();
$jobs       = $controller->getAllJobs();

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Job Posts</div>
    <div class="pg-subtitle">Manage open positions listed on the careers page.</div>
  </div>
  <button class="btn btn--primary" onclick="openModal('addModal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Job Post
  </button>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">All Job Posts</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($jobs) ?> posts</span>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($jobs)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
      <p>No job posts yet.</p>
      <button class="btn btn--primary" onclick="openModal('addModal')">Add First Post</button>
    </div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th>Position</th>
          <th>Location</th>
          <th>Priority</th>
          <th style="text-align:center;">Applicants</th>
          <th>Status</th>
          <th style="width:150px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($jobs as $j): ?>
        <?php
          $jid  = (int)($j->JOB_POST_ID ?? 0);
          $pos  = htmlspecialchars($j->JOB_POSITION ?? '');
          $loc  = htmlspecialchars($j->JOB_LOCATION ?? '—');
          $prio = (int)($j->JOB_PRIORITY ?? 0);
          $cnt  = (int)($j->APPLICANT_COUNT ?? 0);
          $sts  = htmlspecialchars($j->JOB_STATUS ?? 'Active');
          $desc = htmlspecialchars($j->JOB_DISCRIPTION ?? '');
        ?>
        <tr>
          <td style="font-weight:500;"><?= $pos ?></td>
          <td style="font-size:12px;color:var(--text-muted);"><?= $loc ?></td>
          <td><?= $prio ?></td>
          <td style="text-align:center;">
            <a href="applicants?job_id=<?= $jid ?>" style="color:#2563eb;font-weight:500;"><?= $cnt ?></a>
          </td>
          <td><span class="badge <?= $sts === 'Active' ? 'badge--green' : 'badge--red' ?>"><?= $sts ?></span></td>
          <td>
            <div style="display:flex;gap:6px;">
              <button class="btn btn--outline" style="padding:4px 10px;font-size:12px;"
                onclick="openEditJob(<?= $jid ?>, <?= htmlspecialchars(json_encode($j->JOB_POSITION ?? ''),ENT_QUOTES) ?>, <?= $prio ?>, <?= htmlspecialchars(json_encode($j->JOB_LOCATION ?? ''),ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($j->JOB_DISCRIPTION ?? ''),ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($j->JOB_STATUS ?? 'Active'),ENT_QUOTES) ?>)">
                Edit
              </button>
              <button class="btn" style="padding:4px 10px;font-size:12px;background:#fff5f5;color:#dc2626;border:1px solid #fecaca;"
                onclick="confirmDelJob(<?= $jid ?>, <?= $cnt ?>)">
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

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal" style="max-width:540px;">
    <div class="modal-header">
      <span class="modal-title">Add Job Post</span>
      <button class="modal-close" onclick="closeModal('addModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=InsertJob') ?>" class="form-grid">
        <div class="fg">
          <label class="fc">Position <span class="req">*</span></label>
          <input type="text" name="job_position" class="form-control" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="fg">
            <label class="fc">Location</label>
            <input type="text" name="job_location" class="form-control" placeholder="e.g. Mumbai, Remote">
          </div>
          <div class="fg">
            <label class="fc">Priority</label>
            <input type="number" name="job_priority" class="form-control" value="0" min="0">
          </div>
        </div>
        <div class="fg">
          <label class="fc">Description / Requirements</label>
          <textarea name="job_discription" class="form-control" rows="4"></textarea>
        </div>
        <div class="fg">
          <label class="fc">Status</label>
          <select name="job_status" class="form-control">
            <option value="Active">Active</option>
            <option value="In-Active">In-Active</option>
          </select>
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Add Post</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('addModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal" style="max-width:540px;">
    <div class="modal-header">
      <span class="modal-title">Edit Job Post</span>
      <button class="modal-close" onclick="closeModal('editModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateJob') ?>" class="form-grid">
        <input type="hidden" name="job_post_id" id="edit_job_id">
        <div class="fg">
          <label class="fc">Position <span class="req">*</span></label>
          <input type="text" name="job_position" id="edit_job_pos" class="form-control" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="fg">
            <label class="fc">Location</label>
            <input type="text" name="job_location" id="edit_job_loc" class="form-control">
          </div>
          <div class="fg">
            <label class="fc">Priority</label>
            <input type="number" name="job_priority" id="edit_job_prio" class="form-control" min="0">
          </div>
        </div>
        <div class="fg">
          <label class="fc">Description / Requirements</label>
          <textarea name="job_discription" id="edit_job_desc" class="form-control" rows="4"></textarea>
        </div>
        <div class="fg">
          <label class="fc">Status</label>
          <select name="job_status" id="edit_job_status" class="form-control">
            <option value="Active">Active</option>
            <option value="In-Active">In-Active</option>
          </select>
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Save Changes</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('editModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <span class="modal-title">Delete Job Post</span>
      <button class="modal-close" onclick="closeModal('deleteModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p id="del_job_msg" style="color:var(--text-muted);margin-bottom:16px;"></p>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteJob') ?>">
        <input type="hidden" name="job_post_id" id="del_job_id">
        <div style="display:flex;gap:10px;">
          <button type="submit" id="del_job_btn" class="btn" style="background:#dc2626;color:#fff;border:none;">Yes, Delete</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('deleteModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEditJob(id, pos, prio, loc, desc, status) {
  document.getElementById('edit_job_id').value    = id;
  document.getElementById('edit_job_pos').value   = pos;
  document.getElementById('edit_job_prio').value  = prio;
  document.getElementById('edit_job_loc').value   = loc;
  document.getElementById('edit_job_desc').value  = desc;
  var sel = document.getElementById('edit_job_status');
  for (var i=0;i<sel.options.length;i++) sel.options[i].selected = (sel.options[i].value === status);
  openModal('editModal');
}
function confirmDelJob(id, appCount) {
  document.getElementById('del_job_id').value = id;
  var msg = document.getElementById('del_job_msg');
  var btn = document.getElementById('del_job_btn');
  if (appCount > 0) {
    msg.innerHTML = '<span style="color:#dc2626;">This post has <strong>' + appCount + '</strong> applicant(s). You must delete them first before deleting this post.</span>';
    btn.disabled = true; btn.style.opacity = '0.5';
  } else {
    msg.textContent = 'Are you sure you want to delete this job post?';
    btn.disabled = false; btn.style.opacity = '1';
  }
  openModal('deleteModal');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
