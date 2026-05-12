<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'news';
$pageTitle   = 'News & Events';

$controller = new AdminController();
$newsList   = $controller->getAllNews();

// Edit mode
$editNews = null;
if (!empty($_GET['edit'])) {
    $editNews = $controller->getNewsById((int)$_GET['edit']);
}

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">News &amp; Events</div>
    <div class="pg-subtitle">Publish news articles and upcoming events on your website.</div>
  </div>
  <button class="btn btn--primary" onclick="openModal('addModal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add News / Event
  </button>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">All News &amp; Events</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($newsList) ?> items</span>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($newsList)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M4 22h16a2 2 0 002-2V4a2 2 0 00-2-2H8a2 2 0 00-2 2v16a2 2 0 01-2 2z"/></svg>
      <p>No news or events yet.</p>
      <button class="btn btn--primary" onclick="openModal('addModal')">Add First Item</button>
    </div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th style="width:60px;">Image</th>
          <th>Title</th>
          <th>Type</th>
          <th>Date</th>
          <th>Document</th>
          <th style="width:130px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($newsList as $n): ?>
        <?php
          $nid    = (int)($n->NEWS_EVENT_ID ?? 0);
          $title  = htmlspecialchars($n->TITLE ?? '');
          $flag   = htmlspecialchars($n->FLAG ?? 'News');
          $date   = htmlspecialchars(date('d M Y', strtotime($n->CREATED_DATE ?? '')));
          $imgExt = (string)($n->IMG_EXT ?? '');
          $docExt = (string)($n->DOC_EXT ?? '');
          $imgSrc = $imgExt ? '../assets/uploads/news/'.$nid.'.'.$imgExt : '';
          $docSrc = $docExt ? '../assets/uploads/news/'.$nid.'_doc.'.$docExt : '';
        ?>
        <tr>
          <td>
            <?php if ($imgSrc): ?>
              <img src="<?= htmlspecialchars($imgSrc) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;" alt="" onerror="this.style.display='none'">
            <?php else: ?>
              <span style="display:inline-flex;width:48px;height:48px;background:#f1f5f9;border-radius:6px;align-items:center;justify-content:center;color:#94a3b8;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </span>
            <?php endif; ?>
          </td>
          <td style="font-weight:500;"><?= $title ?></td>
          <td><span class="badge <?= $flag === 'Event' ? 'badge--violet' : 'badge--blue' ?>"><?= $flag ?></span></td>
          <td style="font-size:12px;color:var(--text-muted);"><?= $date ?></td>
          <td>
            <?php if ($docSrc): ?>
              <a href="<?= htmlspecialchars($docSrc) ?>" target="_blank" class="btn btn--outline" style="padding:3px 10px;font-size:11px;">View Doc</a>
            <?php else: ?>
              <span style="color:var(--text-muted);font-size:12px;">—</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <button class="btn btn--outline" style="padding:4px 10px;font-size:12px;"
                onclick="openEditNews(<?= $nid ?>, <?= htmlspecialchars(json_encode($n->TITLE ?? ''),ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($n->FLAG ?? 'News'),ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($n->CREATED_DATE ?? ''),ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($n->DESCRIPTION ?? ''),ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($imgExt),ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($docExt),ENT_QUOTES) ?>)">
                Edit
              </button>
              <button class="btn" style="padding:4px 10px;font-size:12px;background:#fff5f5;color:#dc2626;border:1px solid #fecaca;"
                onclick="confirmDelNews(<?= $nid ?>)">
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
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <span class="modal-title">Add News / Event</span>
      <button class="modal-close" onclick="closeModal('addModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=InsertNews') ?>" enctype="multipart/form-data" class="form-grid">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="fg">
            <label class="fc">Type <span class="req">*</span></label>
            <select name="flag" class="form-control" required>
              <option value="News">News</option>
              <option value="Event">Event</option>
            </select>
          </div>
          <div class="fg">
            <label class="fc">Date <span class="req">*</span></label>
            <input type="date" name="created_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <div class="fg">
          <label class="fc">Title <span class="req">*</span></label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="fg">
          <label class="fc">Description</label>
          <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="fg">
            <label class="fc">Image</label>
            <input type="file" name="news_image" class="form-control" accept="image/*">
          </div>
          <div class="fg">
            <label class="fc">Document (PDF/DOC)</label>
            <input type="file" name="news_doc" class="form-control" accept=".pdf,.doc,.docx">
          </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Publish</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('addModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <span class="modal-title">Edit News / Event</span>
      <button class="modal-close" onclick="closeModal('editModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateNews') ?>" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="news_event_id" id="edit_news_id">
        <input type="hidden" name="existing_img_ext" id="edit_news_img_ext">
        <input type="hidden" name="existing_doc_ext" id="edit_news_doc_ext">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="fg">
            <label class="fc">Type <span class="req">*</span></label>
            <select name="flag" id="edit_news_flag" class="form-control" required>
              <option value="News">News</option>
              <option value="Event">Event</option>
            </select>
          </div>
          <div class="fg">
            <label class="fc">Date <span class="req">*</span></label>
            <input type="date" name="created_date" id="edit_news_date" class="form-control" required>
          </div>
        </div>
        <div class="fg">
          <label class="fc">Title <span class="req">*</span></label>
          <input type="text" name="title" id="edit_news_title" class="form-control" required>
        </div>
        <div class="fg">
          <label class="fc">Description</label>
          <textarea name="description" id="edit_news_desc" class="form-control" rows="3"></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="fg">
            <label class="fc">Replace Image</label>
            <input type="file" name="news_image" class="form-control" accept="image/*">
          </div>
          <div class="fg">
            <label class="fc">Replace Document</label>
            <input type="file" name="news_doc" class="form-control" accept=".pdf,.doc,.docx">
          </div>
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
  <div class="modal" style="max-width:360px;">
    <div class="modal-header">
      <span class="modal-title">Delete Item</span>
      <button class="modal-close" onclick="closeModal('deleteModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-muted);margin-bottom:16px;">Are you sure you want to delete this news/event?</p>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteNews') ?>">
        <input type="hidden" name="news_event_id" id="del_news_id">
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn" style="background:#dc2626;color:#fff;border:none;">Yes, Delete</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('deleteModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEditNews(id, title, flag, date, desc, imgExt, docExt) {
  document.getElementById('edit_news_id').value       = id;
  document.getElementById('edit_news_title').value    = title;
  document.getElementById('edit_news_date').value     = date;
  document.getElementById('edit_news_desc').value     = desc;
  document.getElementById('edit_news_img_ext').value  = imgExt;
  document.getElementById('edit_news_doc_ext').value  = docExt;
  var sel = document.getElementById('edit_news_flag');
  for (var i=0;i<sel.options.length;i++) sel.options[i].selected = (sel.options[i].value === flag);
  openModal('editModal');
}
function confirmDelNews(id) {
  document.getElementById('del_news_id').value = id;
  openModal('deleteModal');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
