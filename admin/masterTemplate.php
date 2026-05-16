<?php
/**
 * Admin Master Template
 * Expected vars from calling page:
 *   $pageTitle       string
 *   $currentPage     string  (sidebar key)
 *   $pageMainContent string  (HTML)
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

if (empty($_SESSION['sinelec_admin']['USER_ID'])) {
    sinelec_set_flash('warn', 'Please sign in to access the admin panel.');
    header('location:index'); exit();
}

$pageTitle       = $pageTitle       ?? 'Dashboard';
$currentPage     = $currentPage     ?? 'dashboard';
$pageMainContent = $pageMainContent ?? '';

$adminName  = (string)($_SESSION['sinelec_admin']['NAME']  ?? 'Admin');
$adminEmail = (string)($_SESSION['sinelec_admin']['EMAIL'] ?? '');
$firstName  = explode(' ', trim($adminName))[0] ?: 'Admin';
$initials   = strtoupper(substr($firstName, 0, 1));

$flashToast = sinelec_consume_flash();
$flashMsg   = (string)($flashToast['message'] ?? '');
$flashType  = (string)($flashToast['type']    ?? 'ok');

/* ── Build DB-driven menu ── */
$_sbCtrl = new AdminController();
$_dbMenu = $_sbCtrl->getAdminMenu();

function sbActive(string $key, string $cur): string { return $key === $cur ? ' is-active' : ''; }
function sbGroupOpen(array $grp, string $cur): bool {
    foreach ($grp['items'] as $i) { if ($i['key'] === $cur) return true; } return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — Sinelec Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../admin/css/admin.css">
</head>
<body>

<?php if ($flashMsg !== ''): ?>
<div class="toast-stack" id="toastStack">
  <div class="toast toast--<?= htmlspecialchars(in_array($flashType,['ok','warn','err'])?$flashType:'ok') ?>">
    <span style="flex-shrink:0;margin-top:1px">
      <?php if ($flashType==='ok'): ?>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      <?php elseif ($flashType==='warn'): ?>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <?php else: ?>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?php endif; ?>
    </span>
    <span><?= htmlspecialchars($flashMsg) ?></span>
    <button class="toast-close" onclick="this.closest('.toast').remove()">×</button>
  </div>
</div>
<?php endif; ?>

<div class="shell" id="shell">
  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sb-logo">
      <span class="sb-wordmark">SINELEC</span>
      <span class="sb-wordmark-short">S</span>
    </div>
    <nav class="sb-nav">
      <!-- Dashboard (always visible) -->
      <a href="dashboard" class="sb-link<?= sbActive('dashboard',$currentPage) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><?= sb_icon_svg('dashboard') ?></svg>
        <span class="sb-link-label">Dashboard</span>
      </a>
      <?php foreach ($_dbMenu as $grp): ?>
        <div class="sb-group">
          <div class="sb-group-label"><?= htmlspecialchars($grp['group']) ?></div>
          <?php foreach ($grp['items'] as $item): ?>
            <a href="<?= htmlspecialchars($item['href']) ?>" class="sb-link<?= sbActive($item['key'],$currentPage) ?>">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><?= sb_icon_svg($item['icon']) ?></svg>
              <span class="sb-link-label"><?= htmlspecialchars($item['label']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>
    <div class="sb-bottom">
      <button class="sb-collapse-btn" id="sbColBtn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="sbColIco"><path d="M11 19l-7-7 7-7"/><path d="M21 12H4"/></svg>
        <span class="sb-col-label">Collapse</span>
      </button>
    </div>
  </aside>
  <div class="sb-overlay" id="sbOverlay" onclick="closeMob()"></div>

  <!-- MAIN COLUMN -->
  <div class="main">
    <!-- HEADER -->
    <header class="hd">
      <button class="hd-toggle" id="hdToggle"><span></span><span></span><span></span></button>
      <div class="hd-crumb">
        <a href="dashboard">Dashboard</a>
        <?php if ($currentPage !== 'dashboard'): ?>
          <span class="hd-crumb-sep">›</span>
          <span class="hd-crumb-cur"><?= htmlspecialchars($pageTitle) ?></span>
        <?php endif; ?>
      </div>
      <div class="hd-right">
        <div class="hd-divider"></div>
        <div class="hd-user-wrap">
          <!-- Profile pill button -->
          <button class="hd-user-btn" id="userBtn" aria-haspopup="true" aria-expanded="false">
            <div class="hd-avatar"><?= htmlspecialchars($initials) ?></div>
            <span class="hd-uname"><?= htmlspecialchars($firstName) ?></span>
            <svg class="hd-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="6 9 12 15 18 9"/></svg>
          </button>

          <!-- Dropdown -->
          <div class="hd-drop" id="userDrop">

            <!-- Profile card -->
            <div class="hd-drop-head">
              <div class="hd-drop-avatar"><?= htmlspecialchars($initials) ?></div>
              <div class="hd-drop-info">
                <div class="hd-drop-name"><?= htmlspecialchars($adminName) ?></div>
                <div class="hd-drop-email"><?= htmlspecialchars($adminEmail) ?></div>
                <div class="hd-drop-badge">Admin</div>
              </div>
            </div>

            <!-- Menu items -->
            <div class="hd-drop-items">
              <a href="profile" class="hd-drop-item">
                <span class="hd-di-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                </span>
                Profile
              </a>
              <a href="change-password" class="hd-drop-item">
                <span class="hd-di-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0110 0v3"/></svg>
                </span>
                Change Password
              </a>
              <div class="hd-drop-div"></div>
              <a href="service?urlstring=<?= EncryptURL('action=Logout') ?>" class="hd-drop-item danger">
                <span class="hd-di-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </span>
                Sign Out
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- CONTENT -->
    <main class="page-content"><?= $pageMainContent ?></main>

    <!-- FOOTER -->
    <footer class="site-footer">
      <span>&copy; <?= date('Y') ?> Sinelec Technologies Pvt. Ltd.</span>
      <span class="site-footer-version">Admin Panel v1.0</span>
    </footer>
  </div>
</div>

<script src="../admin/js/admin.js"></script>
</body>
</html>
