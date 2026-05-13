<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'stock';
$pageTitle   = 'Stock Records';

$controller = new AdminController();
$categories = $controller->getParentCategories();

$filters = [
    'cat'  => (int)($_GET['cat'] ?? 0),
    'name' => trim($_GET['name'] ?? ''),
];
$records = $controller->getStockRecords($filters);

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Stock Records</div>
    <div class="pg-subtitle">View current inventory levels across all products.</div>
  </div>
</div>

<!-- Filter bar -->
<form method="GET" action="stock" class="filter-bar">
  <select name="cat" class="form-control" style="max-width:200px;">
    <option value="">All Categories</option>
    <?php foreach ($categories as $c): ?>
    <option value="<?= (int)($c->PRODUCT_CATEGORY_ID ?? 0) ?>" <?= $filters['cat'] == (int)($c->PRODUCT_CATEGORY_ID ?? 0) ? 'selected' : '' ?>>
      <?= htmlspecialchars($c->PRODUCT_CATEGORY_NAME ?? '') ?>
    </option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="name" class="form-control" style="max-width:240px;" placeholder="Search product name…" value="<?= htmlspecialchars($filters['name']) ?>">
  <button type="submit" class="btn btn--primary">Filter</button>
  <a href="stock" class="btn btn--outline">Reset</a>
</form>

<div class="card">
  <div class="card-header">
    <span class="card-title">Inventory Overview</span>
    <span style="font-size:12px;color:var(--text-muted);"><?= count($records) ?> products</span>
  </div>
  <div class="card-body card-body--flush">
    <?php if (empty($records)): ?>
    <div class="empty-state">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      <p>No stock records found.</p>
    </div>
    <?php else: ?>
    <table class="dt">
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th style="text-align:right;">Total In</th>
          <th style="text-align:right;">Sold</th>
          <th style="text-align:right;">Remaining</th>
          <th style="text-align:right;">Threshold</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $r): ?>
        <?php
          $total    = (int)($r->TOTAL_PRODUCT ?? 0);
          $sold     = (int)($r->TOTAL_SOLD ?? 0);
          $rem      = (int)($r->TOTAL_REMAINING ?? 0);
          $thresh   = (int)($r->PRODUCT_THRESHOLD ?? 0);
          $name     = htmlspecialchars($r->PRODUCT_NAME ?? '');
          $code     = htmlspecialchars($r->PRODUCT_CODE ?? '');
          $cat      = htmlspecialchars($r->PRODUCT_CATEGORY_NAME ?? '—');
          if ($rem <= 0)           { $stBadge = 'badge--red';   $stLabel = 'Out of Stock'; }
          elseif ($thresh > 0 && $rem <= $thresh) { $stBadge = 'badge--amber'; $stLabel = 'Low Stock'; }
          else                     { $stBadge = 'badge--green'; $stLabel = 'In Stock'; }
        ?>
        <tr>
          <td>
            <div style="font-weight:500;"><?= $name ?></div>
            <?php if ($code): ?><div style="font-size:11px;color:var(--text-muted);"><?= $code ?></div><?php endif; ?>
          </td>
          <td style="color:var(--text-muted);font-size:12px;"><?= $cat ?></td>
          <td style="text-align:right;"><?= $total ?></td>
          <td style="text-align:right;"><?= $sold ?></td>
          <td style="text-align:right;font-weight:600;<?= $rem <= 0 ? 'color:#dc2626;' : ($thresh > 0 && $rem <= $thresh ? 'color:#d97706;' : '') ?>"><?= $rem ?></td>
          <td style="text-align:right;color:var(--text-muted);"><?= $thresh ?></td>
          <td><span class="badge <?= $stBadge ?>"><?= $stLabel ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
