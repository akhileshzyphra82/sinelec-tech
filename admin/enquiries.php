<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'enquiries';
$pageTitle   = 'Enquiries / RFQ';

$controller = new AdminController();

$filters = [
    'status' => trim($_GET['status'] ?? ''),
    'search' => trim($_GET['search'] ?? ''),
];
$enquiries = $controller->getAllEnquiries($filters);

// Detail view
$detailEnquiry  = null;
$detailProducts = [];
if (!empty($_GET['view'])) {
    $detailEnquiry  = $controller->getEnquiryById((int)$_GET['view']);
    $detailProducts = $controller->getEnquiryProducts((int)$_GET['view']);
}

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Enquiries / RFQ</div>
    <div class="pg-subtitle">Manage quote requests from customers.</div>
  </div>
</div>

<?php if ($detailEnquiry): ?>
<!-- ── Detail View ── -->
<div style="margin-bottom:16px;">
  <a href="enquiries" class="btn btn--outline" style="font-size:12px;padding:5px 12px;">
    ← Back to Enquiries
  </a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;" class="responsive-grid">
  <div class="card">
    <div class="card-header"><span class="card-title">Enquiry Info</span></div>
    <div class="card-body">
      <?php
        $eq = $detailEnquiry;
        $st = (string)($eq->ENQUIRY_STATUS ?? '');
        $bc = match($st) {
          'Quotation Pending' => 'badge--amber',
          'Quotation Sent'    => 'badge--blue',
          'Order Generated'   => 'badge--violet',
          'Order Completed'   => 'badge--green',
          'Cancelled'         => 'badge--red',
          default             => 'badge--gray',
        };
      ?>
      <table style="width:100%;font-size:13px;border-collapse:collapse;">
        <tr><td style="padding:5px 0;color:var(--text-muted);width:120px;">Ref #</td><td><?= htmlspecialchars($eq->ENQUIRY_QUOTE_ID ?? '') ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted);">Name</td><td><?= htmlspecialchars($eq->USER_NAME ?? '') ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted);">Company</td><td><?= htmlspecialchars($eq->COMPANY_NAME ?? '—') ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted);">Email</td><td><?= htmlspecialchars($eq->USER_EMAIL ?? '') ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted);">Phone</td><td><?= htmlspecialchars($eq->USER_MOBILE ?? '—') ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted);">Status</td><td><span class="badge <?= $bc ?>"><?= htmlspecialchars($st) ?></span></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted);">Date</td><td><?= htmlspecialchars(date('d M Y', strtotime($eq->ENQUIRY_DATE ?? ''))) ?></td></tr>
      </table>
      <?php if ($eq->MESSAGE ?? ''): ?>
      <div style="margin-top:12px;padding:10px;background:#f8fafc;border-radius:6px;font-size:12px;color:var(--text-muted);">
        <strong>Message:</strong><br><?= nl2br(htmlspecialchars($eq->MESSAGE ?? '')) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Update Status</span></div>
    <div class="card-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateEnquiryStatus') ?>" class="form-grid">
        <input type="hidden" name="enquiry_quote_id" value="<?= (int)($eq->ENQUIRY_QUOTE_ID ?? 0) ?>">
        <div class="fg">
          <label>New Status</label>
          <select name="enquiry_status" class="form-control">
            <?php foreach (['Quotation Pending','Quotation Sent','Order Generated','Order Completed','Cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $st === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn--primary">Update Status</button>
      </form>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Products Requested</span></div>
  <div class="card-body card-body--flush">
    <?php if (empty($detailProducts)): ?>
    <div class="empty-state" style="padding:24px;"><p>No products listed in this enquiry.</p></div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th style="text-align:right;">Qty Requested</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($detailProducts as $p): ?>
        <tr>
          <td>
            <div style="font-weight:500;"><?= htmlspecialchars($p->PRODUCT_NAME ?? '—') ?></div>
            <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($p->PRODUCT_CODE ?? '') ?></div>
          </td>
          <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($p->PRODUCT_CATEGORY_NAME ?? '—') ?></td>
          <td style="text-align:right;"><?= (int)($p->QUANTITY ?? 0) ?></td>
          <td style="font-size:12px;"><?= htmlspecialchars($p->NOTES ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<!-- ── List View ── -->
<form method="GET" action="enquiries" class="filter-bar">
  <select name="status" class="form-control" style="max-width:220px;">
    <option value="">All Statuses</option>
    <?php foreach (['Quotation Pending','Quotation Sent','Order Generated','Order Completed','Cancelled'] as $s): ?>
    <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="search" class="form-control" style="max-width:240px;" placeholder="Name, email, or company…" value="<?= htmlspecialchars($filters['search']) ?>">
  <button type="submit" class="btn btn--primary">Filter</button>
  <a href="enquiries" class="btn btn--outline">Reset</a>
</form>

<div class="card">
  <div class="card-header">
    <span class="card-title">All Enquiries</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($enquiries) ?> total</span>
  </div>
  <div class="card-body card-body--flush">
    <?php if (empty($enquiries)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
      <p>No enquiries found.</p>
    </div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th>#</th>
          <th>Customer</th>
          <th>Company</th>
          <th style="text-align:center;">Products</th>
          <th>Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($enquiries as $e): ?>
        <?php
          $eid  = (int)($e->ENQUIRY_QUOTE_ID ?? 0);
          $st   = (string)($e->ENQUIRY_STATUS ?? '');
          $bc   = match($st) {
            'Quotation Pending' => 'badge--amber',
            'Quotation Sent'    => 'badge--blue',
            'Order Generated'   => 'badge--violet',
            'Order Completed'   => 'badge--green',
            'Cancelled'         => 'badge--red',
            default             => 'badge--gray',
          };
        ?>
        <tr>
          <td style="color:var(--text-muted);font-size:12px;"><?= $eid ?></td>
          <td>
            <div><?= htmlspecialchars($e->USER_NAME ?? '') ?></div>
            <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($e->USER_EMAIL ?? '') ?></div>
          </td>
          <td style="font-size:12px;"><?= htmlspecialchars($e->COMPANY_NAME ?? '—') ?></td>
          <td style="text-align:center;"><?= (int)($e->PRODUCT_COUNT ?? 0) ?></td>
          <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($st) ?></span></td>
          <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars(date('d M Y', strtotime($e->ENQUIRY_DATE ?? ''))) ?></td>
          <td>
            <a href="enquiries?view=<?= $eid ?>" class="btn btn--outline" style="padding:4px 10px;font-size:12px;">View</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>


<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
