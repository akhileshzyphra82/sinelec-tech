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

$menu = [
    ['key'=>'dashboard','label'=>'Dashboard','href'=>'dashboard',
     'icon'=>'<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
    ['group'=>'Catalog','items'=>[
        ['key'=>'categories','label'=>'Product Categories','href'=>'categories',
         'icon'=>'<path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>'],
        ['key'=>'products','label'=>'Products','href'=>'products',
         'icon'=>'<rect x="3" y="3" width="18" height="18" rx="3"/><rect x="8" y="8" width="8" height="8" rx="1.5"/>'],
    ]],
    ['group'=>'Inventory','items'=>[
        ['key'=>'purchase','label'=>'Purchase Records','href'=>'purchase',
         'icon'=>'<path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>'],
        ['key'=>'stock','label'=>'Stock Records','href'=>'stock',
         'icon'=>'<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
    ]],
    ['group'=>'Orders & Sales','items'=>[
        ['key'=>'orders','label'=>'Active Orders','href'=>'orders',
         'icon'=>'<path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>'],
        ['key'=>'orders-history','label'=>'Order History','href'=>'orders-history',
         'icon'=>'<path d="M3 12a9 9 0 105.195-8.195"/><polyline points="3 3 3 9 9 9"/><path d="M12 7v5l3 3"/>'],
        ['key'=>'enquiries','label'=>'Enquiries / RFQ','href'=>'enquiries',
         'icon'=>'<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>'],
    ]],
    ['group'=>'Customers','items'=>[
        ['key'=>'customers','label'=>'Customer Details','href'=>'customers',
         'icon'=>'<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>'],
    ]],
    ['group'=>'Content','items'=>[
        ['key'=>'banners','label'=>'Banners','href'=>'banners',
         'icon'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'],
        ['key'=>'news','label'=>'News & Events','href'=>'news',
         'icon'=>'<path d="M4 22h16a2 2 0 002-2V4a2 2 0 00-2-2H8a2 2 0 00-2 2v16a2 2 0 01-2 2zm0 0a2 2 0 01-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/>'],
        ['key'=>'faq','label'=>'FAQ','href'=>'faq',
         'icon'=>'<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>'],
    ]],
    ['group'=>'Careers','items'=>[
        ['key'=>'jobs','label'=>'Job Posts','href'=>'jobs',
         'icon'=>'<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>'],
        ['key'=>'applicants','label'=>'Applications','href'=>'applicants',
         'icon'=>'<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'],
    ]],
];

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
      <?php foreach ($menu as $entry): ?>
        <?php if (isset($entry['key'])): ?>
          <a href="<?= $entry['href'] ?>" class="sb-link<?= sbActive($entry['key'],$currentPage) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><?= $entry['icon'] ?></svg>
            <span class="sb-link-label"><?= htmlspecialchars($entry['label']) ?></span>
          </a>
        <?php else: ?>
          <div class="sb-group">
            <div class="sb-group-label"><?= htmlspecialchars($entry['group']) ?></div>
            <?php foreach ($entry['items'] as $item): ?>
              <a href="<?= $item['href'] ?>" class="sb-link<?= sbActive($item['key'],$currentPage) ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><?= $item['icon'] ?></svg>
                <span class="sb-link-label"><?= htmlspecialchars($item['label']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
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
