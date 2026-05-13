<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'faq';
$pageTitle   = 'FAQ';

$controller = new AdminController();
$faqs       = $controller->getAllFAQ();

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">FAQ</div>
    <div class="pg-subtitle">Manage frequently asked questions displayed on the website.</div>
  </div>
  <button class="btn btn--primary" onclick="openModal('addModal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add FAQ
  </button>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">All FAQs</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($faqs) ?> entries</span>
  </div>
  <div class="card-body card-body--flush">
    <?php if (empty($faqs)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <p>No FAQs yet.</p>
      <button class="btn btn--primary" onclick="openModal('addModal')">Add First FAQ</button>
    </div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th style="width:60px;">Order</th>
          <th>Question</th>
          <th>Answer</th>
          <th style="width:120px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($faqs as $f): ?>
        <?php
          $fid = (int)($f->FAQ_ID ?? 0);
          $q   = htmlspecialchars($f->FAQ_QUESTION ?? '');
          $a   = htmlspecialchars($f->FAQ_ANSWER ?? '');
          $ord = (int)($f->FAQ_ORDER ?? 0);
        ?>
        <tr>
          <td style="text-align:center;color:var(--text-muted);"><?= $ord ?></td>
          <td style="font-weight:500;max-width:250px;"><?= $q ?></td>
          <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-muted);font-size:12px;"><?= $a ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <button class="btn btn--outline" style="padding:4px 10px;font-size:12px;"
                onclick="openEditFAQ(<?= $fid ?>, <?= htmlspecialchars(json_encode($f->FAQ_QUESTION ?? ''),ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($f->FAQ_ANSWER ?? ''),ENT_QUOTES) ?>, <?= $ord ?>)">
                Edit
              </button>
              <button class="btn" style="padding:4px 10px;font-size:12px;background:#fff5f5;color:#dc2626;border:1px solid #fecaca;"
                onclick="confirmDelFAQ(<?= $fid ?>)">
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
      <span class="modal-title">Add FAQ</span>
      <button class="modal-close" onclick="closeModal('addModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=InsertFAQ') ?>" class="form-grid">
        <div class="fg">
          <label>Question <span class="req">*</span></label>
          <input type="text" name="faq_question" class="form-control" required>
        </div>
        <div class="fg">
          <label>Answer <span class="req">*</span></label>
          <textarea name="faq_answer" class="form-control" rows="4" required></textarea>
        </div>
        <div class="fg">
          <label>Display Order</label>
          <input type="number" name="faq_order" class="form-control" value="0" min="0">
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Add FAQ</button>
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
      <span class="modal-title">Edit FAQ</span>
      <button class="modal-close" onclick="closeModal('editModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateFAQ') ?>" class="form-grid">
        <input type="hidden" name="faq_id" id="edit_faq_id">
        <div class="fg">
          <label>Question <span class="req">*</span></label>
          <input type="text" name="faq_question" id="edit_faq_q" class="form-control" required>
        </div>
        <div class="fg">
          <label>Answer <span class="req">*</span></label>
          <textarea name="faq_answer" id="edit_faq_a" class="form-control" rows="4" required></textarea>
        </div>
        <div class="fg">
          <label>Display Order</label>
          <input type="number" name="faq_order" id="edit_faq_ord" class="form-control" min="0">
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Save Changes</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('editModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal" style="max-width:360px;">
    <div class="modal-header">
      <span class="modal-title">Delete FAQ</span>
      <button class="modal-close" onclick="closeModal('deleteModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-muted);margin-bottom:16px;">Are you sure you want to delete this FAQ?</p>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteFAQ') ?>">
        <input type="hidden" name="faq_id" id="del_faq_id">
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn" style="background:#dc2626;color:#fff;border:none;">Yes, Delete</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('deleteModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>


<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
