<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'purchase';
$pageTitle   = 'Purchase Records';

$controller = new AdminController();

// Load all products for the add-form dropdown
$allProducts = $controller->getAllProducts();
$records     = $controller->getAllPurchaseRecords();

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Purchase Records</div>
    <div class="pg-subtitle">Log incoming stock purchases and track supplier receipts.</div>
  </div>
  <button class="btn btn--primary" onclick="openModal('addModal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Purchase
  </button>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">All Purchase Records</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($records) ?> records</span>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($records)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/></svg>
      <p>No purchase records yet.</p>
    </div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>Category</th>
          <th style="text-align:right;">Qty</th>
          <th style="text-align:right;">Amount</th>
          <th>Supplier</th>
          <th>Receipt No.</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $r): ?>
        <?php
          $ppId = (int)($r->PRODUCT_PURCHASE_ID ?? 0);
          $prod = htmlspecialchars($r->PRODUCT_NAME ?? '');
          $code = htmlspecialchars($r->PRODUCT_CODE ?? '');
          $cat  = htmlspecialchars($r->PRODUCT_CATEGORY_NAME ?? '—');
          $qty  = (int)($r->QUANTITY_PURCHASED ?? 0);
          $amt  = (float)($r->PURCHASE_AMT ?? 0);
          $from = htmlspecialchars($r->PURCHASED_FROM ?? '—');
          $rcpt = htmlspecialchars($r->RECEIPT_NO ?? '—');
          $date = htmlspecialchars(date('d M Y', strtotime($r->DATE_OF_PURCHASE ?? '')));
        ?>
        <tr>
          <td style="color:var(--text-muted);font-size:12px;"><?= $ppId ?></td>
          <td>
            <div style="font-weight:500;"><?= $prod ?></div>
            <?php if ($code): ?><div style="font-size:11px;color:var(--text-muted);"><?= $code ?></div><?php endif; ?>
          </td>
          <td style="color:var(--text-muted);font-size:12px;"><?= $cat ?></td>
          <td style="text-align:right;font-weight:600;"><?= $qty ?></td>
          <td style="text-align:right;">₹<?= number_format($amt, 2) ?></td>
          <td><?= $from ?></td>
          <td style="font-size:12px;"><?= $rcpt ?></td>
          <td style="font-size:12px;color:var(--text-muted);"><?= $date ?></td>
          <td>
            <button class="btn" style="padding:4px 10px;font-size:12px;background:#fff5f5;color:#dc2626;border:1px solid #fecaca;"
              onclick="confirmDeletePurchase(<?= $ppId ?>)">Delete</button>
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
      <span class="modal-title">Add Purchase Record</span>
      <button class="modal-close" onclick="closeModal('addModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=InsertPurchase') ?>" class="form-grid">
        <div class="fg">
          <label class="fc">Product <span class="req">*</span></label>
          <select name="product_id" class="form-control" required>
            <option value="">— Select Product —</option>
            <?php foreach ($allProducts as $p): ?>
            <option value="<?= (int)($p->PRODUCT_ID ?? 0) ?>">
              <?= htmlspecialchars($p->PRODUCT_NAME ?? '') ?><?= ($p->PRODUCT_CODE ?? '') ? ' ('.$p->PRODUCT_CODE.')' : '' ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="fg">
            <label class="fc">Quantity <span class="req">*</span></label>
            <input type="number" name="quantity_purchased" class="form-control" min="1" required>
          </div>
          <div class="fg">
            <label class="fc">Purchase Amount (₹)</label>
            <input type="number" name="purchase_amt" class="form-control" step="0.01" min="0" value="0">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="fg">
            <label class="fc">Date of Purchase <span class="req">*</span></label>
            <input type="date" name="date_of_purchase" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="fg">
            <label class="fc">Low-stock Threshold</label>
            <input type="number" name="product_threshold" class="form-control" min="0" value="0">
          </div>
        </div>
        <div class="fg">
          <label class="fc">Purchased From (Supplier)</label>
          <input type="text" name="purchased_from" class="form-control" placeholder="Supplier name">
        </div>
        <div class="fg">
          <label class="fc">Receipt / Invoice No.</label>
          <input type="text" name="receipt_no" class="form-control" placeholder="Receipt number">
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Add Record</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('addModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <span class="modal-title">Delete Purchase Record</span>
      <button class="modal-close" onclick="closeModal('deleteModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-muted);margin-bottom:16px;">Deleting this record will reverse the stock count. Are you sure?</p>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeletePurchase') ?>">
        <input type="hidden" name="product_purchase_id" id="del_pp_id">
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn" style="background:#dc2626;color:#fff;border:none;">Yes, Delete</button>
          <button type="button" class="btn btn--outline" onclick="closeModal('deleteModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function confirmDeletePurchase(id) {
  document.getElementById('del_pp_id').value = id;
  openModal('deleteModal');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
