<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'banners';
$pageTitle   = 'Banners';

$controller = new AdminController();
$banners    = $controller->getAllBanners();

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Banners</div>
    <div class="pg-subtitle">Manage website homepage banners and promotional images.</div>
  </div>
  <button class="btn btn--primary" onclick="openModal('addModal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Banner
  </button>
</div>

<!-- Banner Cards -->
<?php if (empty($banners)): ?>
<div class="card">
  <div class="card-body">
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <p>No banners yet.</p>
      <button class="btn btn--primary" onclick="openModal('addModal')">Add First Banner</button>
    </div>
  </div>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
  <?php foreach ($banners as $b): ?>
  <?php
    $bid  = (int)($b->BANNER_ID ?? 0);
    $name = htmlspecialchars($b->BANNER_NAME ?? '');
    $desc = htmlspecialchars($b->BANNER_DESCRIPTION ?? '');
    $prio = (int)($b->PRIORITY ?? 0);
    $link = htmlspecialchars($b->HYPERLINK ?? '');
    $ext  = (string)($b->BANNER_IMG_EXT ?? '');
    $src  = $ext ? '../assets/uploads/banners/'.$bid.'.'.$ext : '';
  ?>
  <div class="card" style="overflow:hidden;">
    <?php if ($src): ?>
    <img src="<?= htmlspecialchars($src) ?>" style="width:100%;height:160px;object-fit:cover;" alt="<?= $name ?>" onerror="this.style.display='none'">
    <?php else: ?>
    <div style="width:100%;height:160px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
    </div>
    <?php endif; ?>
    <div class="card-body">
      <div style="font-weight:600;margin-bottom:4px;"><?= $name ?></div>
      <?php if ($desc): ?><div style="font-size:12px;color:var(--text-muted);margin-bottom:6px;"><?= $desc ?></div><?php endif; ?>
      <div style="font-size:11px;color:var(--text-muted);margin-bottom:10px;">Priority: <?= $prio ?><?= $link ? ' · <a href="'.htmlspecialchars($link).'" target="_blank" style="color:#2563eb;">Link</a>' : '' ?></div>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteBanner') ?>" onsubmit="return confirm('Delete banner <?= addslashes($name) ?>?')">
        <input type="hidden" name="banner_id" value="<?= $bid ?>">
        <button type="submit" class="btn" style="width:100%;padding:6px;background:#fff5f5;color:#dc2626;border:1px solid #fecaca;font-size:12px;">Delete Banner</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add Banner Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <span class="modal-title">Add Banner</span>
      <button class="modal-close" onclick="closeModal('addModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=InsertBanner') ?>" enctype="multipart/form-data" class="form-grid">
        <div class="fg">
          <label class="fc">Banner Name <span class="req">*</span></label>
          <input type="text" name="banner_name" class="form-control" required>
        </div>
        <div class="fg">
          <label class="fc">Banner Image <span class="req">*</span></label>
          <input type="file" name="banner_image" class="form-control" accept="image/*" required>
          <span style="font-size:11px;color:var(--text-muted);">JPG, PNG, WebP. Recommended: 1920×600 px.</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="fg">
            <label class="fc">Priority</label>
            <input type="number" name="priority" class="form-control" value="0" min="0">
          </div>
          <div class="fg">
            <label class="fc">Hyperlink</label>
            <input type="text" name="hyperlink" class="form-control" placeholder="https://...">
          </div>
        </div>
        <div class="fg">
          <label class="fc">Description</label>
          <textarea name="banner_description" class="form-control" rows="2"></textarea>
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Upload Banner</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('addModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
