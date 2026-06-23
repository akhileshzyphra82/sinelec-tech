<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['sinelec_admin']['USER_ID'])) {
    http_response_code(403); echo 'Unauthorized'; exit();
}
require_once __DIR__ . '/../../common/functions.php';
require_once __DIR__ . '/../../controller/admin_controller.php';

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) { echo '<p style="color:#dc2626;padding:16px;">Invalid order ID.</p>'; exit(); }

$controller = new AdminController();
$items      = $controller->getOrderItems($orderId);
$history    = $controller->getOrderHistory_byId($orderId);
?>

<div style="font-size:13px;">
  <h4 style="font-size:13px;font-weight:600;margin-bottom:10px;color:#0f172a;">Order Items</h4>
  <?php if (empty($items)): ?>
  <p style="color:#6b7280;font-size:12px;">No items found.</p>
  <?php else: ?>
  <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:16px;">
    <thead>
      <tr style="background:#f8fafc;">
        <th style="text-align:left;padding:7px 10px;font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;border-bottom:1px solid #e2e8f0;">Product</th>
        <th style="text-align:right;padding:7px 10px;font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;border-bottom:1px solid #e2e8f0;">Qty</th>
        <th style="text-align:right;padding:7px 10px;font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;border-bottom:1px solid #e2e8f0;">Unit Price</th>
        <th style="text-align:right;padding:7px 10px;font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;border-bottom:1px solid #e2e8f0;">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;">
          <div style="font-weight:500;"><?= htmlspecialchars($item->PRODUCT_NAME ?? '') ?></div>
          <?php if ($item->PRODUCT_CODE ?? ''): ?><div style="font-size:11px;color:#94a3b8;"><?= htmlspecialchars($item->PRODUCT_CODE) ?></div><?php endif; ?>
        </td>
        <td style="text-align:right;padding:8px 10px;border-bottom:1px solid #f1f5f9;"><?= (int)($item->QUANTITY ?? 0) ?></td>
        <td style="text-align:right;padding:8px 10px;border-bottom:1px solid #f1f5f9;">€<?= number_format((float)($item->PRODUCT_AMT ?? 0), 2) ?></td>
        <td style="text-align:right;padding:8px 10px;border-bottom:1px solid #f1f5f9;font-weight:600;">€<?= number_format((float)($item->FINAL_AMT ?? 0), 2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php if (!empty($history)): ?>
  <h4 style="font-size:13px;font-weight:600;margin-bottom:8px;color:#0f172a;">Status History</h4>
  <div style="display:flex;flex-direction:column;gap:6px;">
    <?php foreach ($history as $h): ?>
    <div style="display:flex;align-items:center;gap:10px;font-size:12px;">
      <span style="width:8px;height:8px;border-radius:50%;background:#2563eb;flex-shrink:0;"></span>
      <span style="font-weight:500;"><?= htmlspecialchars($h->HISTORY_ORDER_STATUS ?? $h->HISTORY_PAYMENT_STATUS ?? '') ?></span>
      <?php if (!empty($h->HISTORY_REMARKS)): ?>
      <span style="color:#94a3b8;font-size:11px;margin-left:6px;">(<?= htmlspecialchars($h->HISTORY_REMARKS) ?>)</span>
      <?php endif; ?>
      <span style="color:#6b7280;margin-left:auto;"><?= htmlspecialchars(date('d M Y H:i', strtotime($h->CREATED_AT ?? ''))) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
