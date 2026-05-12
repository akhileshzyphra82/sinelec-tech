<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'dashboard';
$pageTitle   = 'Dashboard';

$controller   = new AdminController();
$stats        = $controller->getDashboardStats();
$recentOrders = $controller->getRecentOrders(5);

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Dashboard</div>
    <div class="pg-subtitle">Welcome back — here's a quick summary of activity.</div>
  </div>
</div>

<!-- Stat cards -->
<div class="stats-grid">

  <a href="orders" class="stat-card" style="text-decoration:none;color:inherit;">
    <div class="stat-icon stat-icon--blue">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16Z"/>
        <path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
      </svg>
    </div>
    <div class="stat-label">Active Orders</div>
    <div class="stat-value"><?= number_format((int)($stats['active_orders'] ?? 0)) ?></div>
    <div class="stat-note">Awaiting dispatch or payment</div>
  </a>

  <a href="products" class="stat-card" style="text-decoration:none;color:inherit;">
    <div class="stat-icon stat-icon--green">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <rect x="3" y="3" width="18" height="18" rx="3"/><rect x="8" y="8" width="8" height="8" rx="1.5"/>
      </svg>
    </div>
    <div class="stat-label">Products</div>
    <div class="stat-value"><?= number_format((int)($stats['products'] ?? 0)) ?></div>
    <div class="stat-note">In catalogue</div>
  </a>

  <a href="customers" class="stat-card" style="text-decoration:none;color:inherit;">
    <div class="stat-icon stat-icon--amber">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
      </svg>
    </div>
    <div class="stat-label">Customers</div>
    <div class="stat-value"><?= number_format((int)($stats['customers'] ?? 0)) ?></div>
    <div class="stat-note">Registered accounts</div>
  </a>

  <a href="enquiries" class="stat-card" style="text-decoration:none;color:inherit;">
    <div class="stat-icon stat-icon--violet">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
      </svg>
    </div>
    <div class="stat-label">Open Enquiries</div>
    <div class="stat-value"><?= number_format((int)($stats['enquiries'] ?? 0)) ?></div>
    <div class="stat-note">Pending quotation or action</div>
  </a>

</div>

<!-- Recent Orders -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Recent Orders</span>
    <a href="orders" class="btn btn--outline" style="font-size:12px;padding:5px 12px;">View All</a>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($recentOrders)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16Z"/></svg>
      <p>No recent orders</p>
    </div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Status</th>
          <th style="text-align:right;">Amount</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentOrders as $o): ?>
        <?php
          $st = (string)($o->ORDER_CURRENT_STATUS ?? '');
          $badgeClass = match($st) {
            'Payment Successful'        => 'badge--blue',
            'Dispatched'                => 'badge--violet',
            'Delivered'                 => 'badge--green',
            'Cancelled'                 => 'badge--red',
            'Invoice Payment Pending'   => 'badge--amber',
            default                     => 'badge--gray',
          };
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($o->ORDER_NUMBER ?? '') ?></strong></td>
          <td><?= htmlspecialchars($o->NAME ?? 'Guest') ?></td>
          <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($st) ?></span></td>
          <td style="text-align:right;">₹<?= number_format((float)($o->ORDER_TOTAL_AMT ?? 0), 2) ?></td>
          <td style="color:var(--text-muted);font-size:12px;"><?= htmlspecialchars(date('d M Y', strtotime($o->ORDER_DATE ?? ''))) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Quick Access -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Quick Access</span>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;">
      <?php
      $shortcuts = [
        ['href'=>'categories','label'=>'Categories',      'color'=>'#eff6ff','tc'=>'#1d4ed8','icon'=>'<path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>'],
        ['href'=>'products',  'label'=>'Products',        'color'=>'#f0fdf4','tc'=>'#15803d','icon'=>'<rect x="3" y="3" width="18" height="18" rx="3"/><rect x="8" y="8" width="8" height="8" rx="1.5"/>'],
        ['href'=>'purchase',  'label'=>'Purchase',        'color'=>'#fdf4ff','tc'=>'#7e22ce','icon'=>'<path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/>'],
        ['href'=>'stock',     'label'=>'Stock',           'color'=>'#f0fdf4','tc'=>'#065f46','icon'=>'<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
        ['href'=>'orders',    'label'=>'Active Orders',   'color'=>'#faf5ff','tc'=>'#6d28d9','icon'=>'<path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16Z"/>'],
        ['href'=>'enquiries', 'label'=>'Enquiries',       'color'=>'#fff7ed','tc'=>'#c2410c','icon'=>'<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>'],
        ['href'=>'banners',   'label'=>'Banners',         'color'=>'#fefce8','tc'=>'#854d0e','icon'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'],
        ['href'=>'faq',       'label'=>'FAQ',             'color'=>'#f0f9ff','tc'=>'#0369a1','icon'=>'<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/>'],
      ];
      foreach ($shortcuts as $s): ?>
      <a href="<?= htmlspecialchars($s['href']) ?>"
         style="display:flex;flex-direction:column;align-items:flex-start;gap:10px;padding:14px;border-radius:10px;border:1px solid #e2e8f0;text-decoration:none;transition:box-shadow .15s,border-color .15s;"
         onmouseover="this.style.boxShadow='0 4px 14px rgba(0,0,0,.08)';this.style.borderColor='#c7d2e0';"
         onmouseout="this.style.boxShadow='';this.style.borderColor='#e2e8f0';">
        <span style="width:34px;height:34px;border-radius:8px;background:<?= $s['color'] ?>;color:<?= $s['tc'] ?>;display:grid;place-items:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><?= $s['icon'] ?></svg>
        </span>
        <span style="font-size:12px;font-weight:500;color:#0f172a;line-height:1.3;"><?= htmlspecialchars($s['label']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
