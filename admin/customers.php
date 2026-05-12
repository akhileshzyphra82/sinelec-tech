<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'customers';
$pageTitle   = 'Customers';

$controller = new AdminController();

$filters = ['search' => trim($_GET['search'] ?? '')];
$customers = $controller->getAllCustomers($filters);

// Detail view
$detailCustomer   = null;
$detailAddresses  = [];
if (!empty($_GET['view'])) {
    $detailCustomer  = $controller->getCustomerById((int)$_GET['view']);
    $detailAddresses = $controller->getCustomerAddresses((int)$_GET['view']);
}

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Customers</div>
    <div class="pg-subtitle">View and manage registered customer accounts.</div>
  </div>
</div>

<?php if ($detailCustomer): ?>
<!-- ── Detail View ── -->
<div style="margin-bottom:16px;">
  <a href="customers" class="btn btn--outline" style="font-size:12px;padding:5px 12px;">← Back to Customers</a>
</div>

<?php $c = $detailCustomer; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;" class="responsive-grid">
  <div class="card">
    <div class="card-header"><span class="card-title">Customer Info</span></div>
    <div class="card-body">
      <table style="width:100%;font-size:13px;border-collapse:collapse;">
        <tr><td style="padding:5px 0;color:var(--text-muted);width:130px;">Name</td><td><strong><?= htmlspecialchars($c->NAME ?? '') ?></strong></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted);">Email</td><td><?= htmlspecialchars($c->COMMUNICATION_EMAIL_ID ?? '') ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted);">Mobile</td><td><?= htmlspecialchars(($c->COMMUNICATION_MOBILE_NUM_ISD ?? '').($c->COMMUNICATION_MOBILE_NUM ?? '')) ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted);">Company</td><td><?= htmlspecialchars($c->COMPANY_NAME ?? '—') ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted);">Designation</td><td><?= htmlspecialchars($c->DESIGNATION ?? '—') ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted);">Verified</td>
          <td><span class="badge <?= ($c->VERIFIED_FLAG ?? '') === 'Yes' ? 'badge--green' : 'badge--amber' ?>"><?= htmlspecialchars($c->VERIFIED_FLAG ?? 'No') ?></span></td>
        </tr>
        <tr><td style="padding:5px 0;color:var(--text-muted);">Active</td>
          <td><span class="badge <?= ($c->ACCOUNT_ACTIVATION_FLAG ?? '') === 'Yes' ? 'badge--green' : 'badge--red' ?>"><?= htmlspecialchars($c->ACCOUNT_ACTIVATION_FLAG ?? 'No') ?></span></td>
        </tr>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Saved Addresses</span></div>
    <div class="card-body" style="padding:0;">
      <?php if (empty($detailAddresses)): ?>
      <div class="empty-state" style="padding:20px;"><p style="font-size:13px;">No addresses saved.</p></div>
      <?php else: ?>
      <?php foreach ($detailAddresses as $addr): ?>
      <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;">
        <div style="font-weight:500;"><?= htmlspecialchars($addr->ADDRESS_LINE_1 ?? '') ?><?= ($addr->ADDRESS_LINE_2 ?? '') ? ', '.htmlspecialchars($addr->ADDRESS_LINE_2) : '' ?></div>
        <div style="color:var(--text-muted);"><?= htmlspecialchars(implode(', ', array_filter([$addr->CITY ?? '', $addr->STATE ?? '', $addr->PIN_CODE ?? '', $addr->COUNTRY_NAME ?? '']))) ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ── List View ── -->
<form method="GET" action="customers" class="filter-bar">
  <input type="text" name="search" class="form-control" style="max-width:280px;" placeholder="Name or email…" value="<?= htmlspecialchars($filters['search']) ?>">
  <button type="submit" class="btn btn--primary">Search</button>
  <a href="customers" class="btn btn--outline">Reset</a>
</form>

<div class="card">
  <div class="card-header">
    <span class="card-title">All Customers</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($customers) ?> registered</span>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($customers)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      <p>No customers found.</p>
    </div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Company</th>
          <th style="text-align:center;">Orders</th>
          <th>Verified</th>
          <th>Active</th>
          <th>Detail</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $cust): ?>
        <?php
          $uid = (int)($cust->USER_ID ?? 0);
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($cust->NAME ?? '') ?></strong></td>
          <td style="font-size:12px;"><?= htmlspecialchars($cust->COMMUNICATION_EMAIL_ID ?? '') ?></td>
          <td style="font-size:12px;"><?= htmlspecialchars($cust->COMPANY_NAME ?? '—') ?></td>
          <td style="text-align:center;"><?= (int)($cust->ORDER_COUNT ?? 0) ?></td>
          <td><span class="badge <?= ($cust->VERIFIED_FLAG ?? '') === 'Yes' ? 'badge--green' : 'badge--amber' ?>"><?= htmlspecialchars($cust->VERIFIED_FLAG ?? 'No') ?></span></td>
          <td><span class="badge <?= ($cust->ACCOUNT_ACTIVATION_FLAG ?? '') === 'Yes' ? 'badge--green' : 'badge--red' ?>"><?= htmlspecialchars($cust->ACCOUNT_ACTIVATION_FLAG ?? 'No') ?></span></td>
          <td>
            <a href="customers?view=<?= $uid ?>" class="btn btn--outline" style="padding:4px 10px;font-size:12px;">View</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<style>
@media(max-width:640px){.responsive-grid{grid-template-columns:1fr!important;}}
</style>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
