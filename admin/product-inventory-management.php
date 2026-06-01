<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'product-inventory-management';
$pageTitle   = 'Inventory Management';

$controller = new AdminController();

$canView   = sinelec_can('view');
$canEdit   = sinelec_can('edit');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

/* ── Filters ── */
$fSearch  = trim($_GET['search']       ?? '');
$fCat     = (int)($_GET['category_id'] ?? 0);
$fStatus  = trim($_GET['status']       ?? 'Active');
$fHealth  = trim($_GET['stock_health'] ?? '');

/* ── Load data ── */
$inventoryRows = $controller->getInventoryReport([
    'search'       => $fSearch,
    'category_id'  => $fCat ?: null,
    'status'       => $fStatus ?: null,
    'stock_health' => $fHealth ?: null,
]);
$stats       = $controller->getInventoryStats();
$allCats     = $controller->getAllCategories();
$pubBase     = rtrim(sinelec_env('PUBLIC_BASE_URL'), '/');

/* ── Build category tree for filter dropdown ── */
$parentCats = [];
$childCats  = [];
foreach ($allCats as $cat) {
    $cid  = (int)(float)($cat->PRODUCT_CATEGORY_ID  ?? 0);
    $pid  = (int)(float)($cat->PARENT_CATEGORY_ID   ?? 0);
    $nm   = (string)($cat->PRODUCT_CATEGORY_NAME    ?? '');
    if ($pid === 0) $parentCats[$cid] = $nm;
    else            $childCats[$pid][] = ['id' => $cid, 'name' => $nm];
}

/* ── Stock-health helper ── */
function invHealthInfo(float $remaining, float $threshold): array {
    if ($remaining <= 0)                              return ['out',      'Out of Stock', '#dc2626', '#fee2e2', '#fca5a5'];
    if ($remaining <= $threshold)                     return ['critical', 'Critical',     '#ea580c', '#fff7ed', '#fed7aa'];
    if ($remaining <= $threshold * 2)                 return ['warning',  'Warning',      '#d97706', '#fefce8', '#fde68a'];
    return                                                   ['healthy',  'Healthy',      '#16a34a', '#f0fdf4', '#86efac'];
}

/* ── Aging helper ── */
function invAgingInfo(string $lastPurchase, string $lastSale): array {
    $dates = array_filter([$lastPurchase, $lastSale]);
    if (empty($dates)) return [null, 'No Data', '#94a3b8', '#f8fafc'];
    $latestTs = max(array_map('strtotime', $dates));
    $days = (int)floor((time() - $latestTs) / 86400);
    if ($days <= 30)  return [$days, 'Fresh',    '#16a34a', '#f0fdf4'];
    if ($days <= 90)  return [$days, 'Normal',   '#0284c7', '#f0f9ff'];
    if ($days <= 180) return [$days, 'Aging',    '#d97706', '#fefce8'];
    if ($days <= 365) return [$days, 'Old',      '#ea580c', '#fff7ed'];
    return                   [$days, 'Very Old', '#dc2626', '#fee2e2'];
}

/* ── Stats ── */
$sTot    = (int)(float)($stats->TOTAL_PRODUCTS     ?? 0);
$sActive = (int)(float)($stats->ACTIVE_PRODUCTS    ?? 0);
$sOut    = (int)(float)($stats->OUT_OF_STOCK       ?? 0);
$sCrit   = (int)(float)($stats->CRITICAL_STOCK     ?? 0);
$sWarn   = (int)(float)($stats->WARNING_STOCK      ?? 0);
$sHealth = (int)(float)($stats->HEALTHY_STOCK      ?? 0);
$sUnits  = (float)($stats->TOTAL_UNITS_IN_STOCK    ?? 0);
$sSold   = (float)($stats->TOTAL_UNITS_SOLD        ?? 0);
$sValue  = (float)($stats->TOTAL_STOCK_VALUE       ?? 0);
$sAlert  = $sOut + $sCrit;   /* needs attention */

ob_start();
?>

<!-- ══════════════════ PAGE HEADER ══════════════════ -->
<div class="pg-header">
  <div>
    <h1 class="pg-title">Inventory Management</h1>
    <p class="pg-sub">Stock levels, health, aging analysis and full product movement ledger.</p>
  </div>
  <button class="btn btn--outline" style="height:36px;padding:0 14px;font-size:12px;" onclick="invExportCsv()">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Export CSV
  </button>
</div>

<!-- ══════════════════ KPI TILES ══════════════════ -->
<div style="display:grid;grid-template-columns:repeat(9,1fr);gap:8px;margin-bottom:14px;">
  <?php
  $kpiTiles = [
    ['Total Products',  $sTot,                       '#4f46e5','#ede9fe','📦'],
    ['Active',          $sActive,                    '#0284c7','#dbeafe','✅'],
    ['Out of Stock',    $sOut,                       '#dc2626','#fee2e2','❌'],
    ['Critical / Low',  $sCrit,                      '#ea580c','#fff7ed','⚠️'],
    ['Warning',         $sWarn,                      '#d97706','#fefce8','🔶'],
    ['Healthy',         $sHealth,                    '#16a34a','#dcfce7','🟢'],
    ['Units in Stock',  number_format($sUnits),      '#0891b2','#e0f2fe','🏭'],
    ['Total Sold',      number_format($sSold),       '#7c3aed','#f3e8ff','📤'],
    ['Stock Value',     '€'.number_format($sValue,2),'#059669','#ecfdf5','💰'],
  ];
  foreach ($kpiTiles as [$lbl,$val,$clr,$bg,$icon]):
  ?>
  <div style="background:<?= $bg ?>;border-radius:8px;padding:8px 10px;border:1px solid <?= $clr ?>33;">
    <div style="font-size:10px;font-weight:600;color:<?= $clr ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:flex;align-items:center;gap:3px;margin-bottom:4px;">
      <span style="font-size:11px;"><?= $icon ?></span><?= $lbl ?>
    </div>
    <div style="font-size:15px;font-weight:800;color:<?= $clr ?>;line-height:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $val ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══════════════════ STOCK HEALTH DISTRIBUTION BAR ══════════════════ -->
<?php if ($sTot > 0): ?>
<div class="card" style="padding:16px 20px;margin-bottom:18px;">
  <div style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Stock Health Distribution</div>
  <div style="display:flex;height:20px;border-radius:10px;overflow:hidden;gap:1px;">
    <?php
    $distSegments = [
        ['Out of Stock', $sOut,    '#dc2626'],
        ['Critical',     $sCrit,   '#ea580c'],
        ['Warning',      $sWarn,   '#d97706'],
        ['Healthy',      $sHealth, '#16a34a'],
    ];
    foreach ($distSegments as [$dlbl,$dval,$dclr]):
        $pct = $sTot > 0 ? round($dval / $sTot * 100, 1) : 0;
        if ($pct <= 0) continue;
    ?>
    <div title="<?= $dlbl ?>: <?= $dval ?> (<?= $pct ?>%)"
         style="flex:0 0 <?= $pct ?>%;background:<?= $dclr ?>;min-width:2px;"></div>
    <?php endforeach; ?>
  </div>
  <div style="display:flex;gap:16px;margin-top:8px;flex-wrap:wrap;">
    <?php foreach($distSegments as [$dlbl,$dval,$dclr]):
      $pct = $sTot > 0 ? round($dval / $sTot * 100, 1) : 0;
    ?>
    <div style="display:flex;align-items:center;gap:5px;font-size:12px;">
      <span style="width:10px;height:10px;border-radius:50%;background:<?= $dclr ?>;display:inline-block;flex-shrink:0;"></span>
      <span style="color:var(--text-muted);"><?= $dlbl ?>:</span>
      <strong style="color:#1e293b;"><?= $dval ?></strong>
      <span style="color:var(--text-muted);">(<?= $pct ?>%)</span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ══════════════════ FILTER BAR ══════════════════ -->
<form method="GET" id="invFilterForm" style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;align-items:flex-end;">
  <!-- Search -->
  <div style="position:relative;flex:1;min-width:200px;max-width:280px;">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af;" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="search" class="form-control" placeholder="Product name or code…"
           value="<?= htmlspecialchars($fSearch) ?>" style="padding-left:30px;height:36px;">
  </div>
  <!-- Category -->
  <select name="category_id" class="form-control" style="height:36px;width:auto;min-width:170px;">
    <option value="">All Categories</option>
    <?php foreach ($parentCats as $pid => $pname): ?>
    <?php if (!empty($childCats[$pid])): ?>
      <optgroup label="<?= htmlspecialchars($pname) ?>">
      <?php foreach ($childCats[$pid] as $sub): ?>
        <option value="<?= $sub['id'] ?>" <?= $fCat===$sub['id']?'selected':'' ?>><?= htmlspecialchars($sub['name']) ?></option>
      <?php endforeach; ?>
      </optgroup>
    <?php else: ?>
      <option value="<?= $pid ?>" <?= $fCat===$pid?'selected':'' ?>><?= htmlspecialchars($pname) ?></option>
    <?php endif; ?>
    <?php endforeach; ?>
  </select>
  <!-- Stock Health -->
  <select name="stock_health" class="form-control" style="height:36px;width:auto;min-width:155px;">
    <option value="">All Health</option>
    <option value="healthy"  <?= $fHealth==='healthy' ?'selected':'' ?>>Healthy</option>
    <option value="warning"  <?= $fHealth==='warning' ?'selected':'' ?>>Warning</option>
    <option value="critical" <?= $fHealth==='critical'?'selected':'' ?>>Critical</option>
    <option value="out"      <?= $fHealth==='out'     ?'selected':'' ?>>Out of Stock</option>
  </select>
  <!-- Product Status -->
  <select name="status" class="form-control" style="height:36px;width:auto;min-width:130px;">
    <option value="">All Status</option>
    <option value="Active"   <?= $fStatus==='Active'  ?'selected':'' ?>>Active</option>
    <option value="In-Active"<?= $fStatus==='In-Active'?'selected':'' ?>>In-Active</option>
  </select>
  <button type="submit" class="btn btn--primary" style="height:36px;padding:0 16px;">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="16" y2="12"/><line x1="4" y1="18" x2="12" y2="18"/></svg>
    Apply
  </button>
  <?php if ($fSearch||$fCat||$fHealth||($fStatus&&$fStatus!=='Active')): ?>
  <a href="product-inventory-management" class="btn btn--ghost" style="height:36px;padding:0 14px;font-size:12px;">Clear</a>
  <?php endif; ?>
</form>

<!-- ══════════════════ TABLE CARD ══════════════════ -->
<div class="card" style="overflow:hidden;">

  <!-- ── Top Pagination Bar ── -->
  <div class="inv-pgbar">
    <div class="inv-pgbar__info">
      Showing <strong id="invRangeStart">1</strong>–<strong id="invRangeEnd">20</strong>
      of <strong id="invCount"><?= count($inventoryRows) ?></strong> product<?= count($inventoryRows) !== 1 ? 's' : '' ?>
    </div>
    <div class="inv-pgbar__perpage">
      <span class="inv-pgbar__perpage-label">Per page</span>
      <div class="inv-pgbar__sel-wrap">
        <select id="invPerPage" class="inv-pgbar__sel">
          <option value="10">10</option>
          <option value="20" selected>20</option>
          <option value="30">30</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <svg class="inv-pgbar__sel-arrow" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <button type="button" class="inv-pgbar__apply" onclick="invApplyPerPage()">Apply</button>
    </div>
    <div id="invPager" class="inv-pgbar__pager"></div>
  </div>

  <!-- ── Table ── -->
  <div style="overflow-x:auto;">
    <table class="dt" id="invTable">
      <thead>
        <tr>
          <th style="width:36px;">#</th>
          <th style="min-width:230px;">Product</th>
          <th style="width:130px;">Category</th>
          <th style="width:110px;text-align:center;">Health</th>
          <th style="width:120px;text-align:right;">In Stock</th>
          <th style="width:80px;text-align:center;">Threshold</th>
          <th style="width:90px;text-align:right;">Total In</th>
          <th style="width:90px;text-align:right;">Total Sold</th>
          <th style="width:100px;text-align:right;">Unit Price</th>
          <th style="width:110px;text-align:right;">Stock Value</th>
          <th style="width:105px;text-align:center;">Aging</th>
          <th style="width:55px;text-align:center;">Act.</th>
        </tr>
      </thead>
      <tbody id="invTbody">
        <?php foreach ($inventoryRows as $i => $p):
          $pid        = (int)(float)($p->PRODUCT_ID          ?? 0);
          $pName      = (string)($p->PRODUCT_NAME            ?? '');
          $pCode      = (string)($p->PRODUCT_CODE            ?? '');
          $pStatus    = (string)($p->PRODUCT_STATUS          ?? '');
          $pAmt       = (float)($p->PRODUCT_AMT              ?? 0);
          $pThresh    = (float)($p->PRODUCT_THRESHOLD        ?? 1);
          $pTotIn     = (float)($p->TOTAL_PRODUCT            ?? 0);
          $pSold      = (float)($p->TOTAL_SOLD               ?? 0);
          $pRemain    = (float)($p->TOTAL_REMAINING          ?? 0);
          $pCatName   = (string)($p->PRODUCT_CATEGORY_NAME   ?? '');
          $pParCat    = (string)($p->PARENT_CATEGORY_NAME    ?? '');
          $pLastPurch = (string)($p->LAST_PURCHASE_DATE      ?? '');
          $pLastSale  = (string)($p->LAST_SALE_DATE          ?? '');
          $pOrders    = (int)(float)($p->ORDER_COUNT         ?? 0);
          $pPurchCnt  = (int)(float)($p->PURCHASE_COUNT      ?? 0);
          $pThumbExt  = (string)($p->THUMB_EXT               ?? '');
          $pThumbUrl  = ($pThumbExt !== '' && strpos($pThumbExt, '/') !== false) ? $pubBase.'/'.$pThumbExt : '';
          $pStockVal  = $pRemain * $pAmt;

          [$hKey, $hLabel, $hClr, $hBg] = invHealthInfo($pRemain, $pThresh);
          [$ageDays, $ageLabel, $ageClr, $ageBg] = invAgingInfo($pLastPurch, $pLastSale);

          $pLastPurchFmt = $pLastPurch ? date('d M Y', strtotime($pLastPurch)) : '—';
          $pLastSaleFmt  = $pLastSale  ? date('d M Y', strtotime($pLastSale))  : '—';

          /* stock fill % for mini bar */
          $fillPct = $pThresh > 0 ? min(100, round($pRemain / max($pThresh * 3, 1) * 100)) : ($pRemain > 0 ? 100 : 0);

          $catDisplay = $pParCat ? $pParCat . ' / ' . $pCatName : $pCatName;
        ?>
        <tr class="inv-row" data-seq="<?= $i+1 ?>">
          <td class="td-sm inv-sno" style="font-size:12px;color:var(--text-muted);font-weight:600;"><?= $i+1 ?></td>

          <!-- Product -->
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <?php if ($pThumbUrl): ?>
              <img src="<?= htmlspecialchars($pThumbUrl) ?>" alt=""
                   style="width:38px;height:38px;border-radius:6px;object-fit:cover;border:1px solid var(--border);flex-shrink:0;">
              <?php else: ?>
              <div style="width:38px;height:38px;border-radius:6px;background:#f1f5f9;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
              </div>
              <?php endif; ?>
              <div style="min-width:0;">
                <div style="font-weight:700;font-size:13px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:190px;" title="<?= htmlspecialchars($pName) ?>"><?= htmlspecialchars($pName) ?></div>
                <div style="font-size:10px;color:var(--text-muted);font-family:monospace;margin-top:1px;"><?= htmlspecialchars($pCode) ?></div>
                <div style="margin-top:3px;">
                  <span style="font-size:10px;padding:1px 7px;border-radius:10px;font-weight:600;<?= $pStatus==='Active' ? 'background:#dcfce7;color:#16a34a;' : 'background:#fee2e2;color:#dc2626;' ?>"><?= $pStatus ?></span>
                </div>
              </div>
            </div>
          </td>

          <!-- Category -->
          <td>
            <div style="font-size:12px;color:var(--text);font-weight:500;line-height:1.4;">
              <?= htmlspecialchars($catDisplay ?: '—') ?>
            </div>
            <?php if ($pOrders > 0 || $pPurchCnt > 0): ?>
            <div style="font-size:10px;color:var(--text-muted);margin-top:3px;">
              <?= $pPurchCnt ?> purchase<?= $pPurchCnt!=1?'s':'' ?> · <?= $pOrders ?> order<?= $pOrders!=1?'s':'' ?>
            </div>
            <?php endif; ?>
          </td>

          <!-- Health -->
          <td style="text-align:center;">
            <div style="display:inline-flex;flex-direction:column;align-items:center;gap:4px;">
              <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;white-space:nowrap;background:<?= $hBg ?>;color:<?= $hClr ?>;border:1px solid <?= $hClr ?>44;">
                <?= $hLabel ?>
              </span>
            </div>
          </td>

          <!-- In Stock -->
          <td style="text-align:right;">
            <div style="font-size:15px;font-weight:800;color:<?= $hClr ?>;"><?= number_format($pRemain) ?></div>
            <div style="margin-top:5px;height:4px;background:#e2e8f0;border-radius:2px;min-width:60px;">
              <div style="height:4px;border-radius:2px;background:<?= $hClr ?>;width:<?= $fillPct ?>%;transition:width .3s;"></div>
            </div>
            <div style="font-size:10px;color:var(--text-muted);margin-top:2px;"><?= $fillPct ?>% of safe level</div>
          </td>

          <!-- Threshold -->
          <td style="text-align:center;font-size:13px;font-weight:600;color:var(--text-muted);"><?= number_format($pThresh) ?></td>

          <!-- Total In -->
          <td style="text-align:right;">
            <div style="font-size:13px;font-weight:700;color:#0284c7;"><?= number_format($pTotIn) ?></div>
            <div style="font-size:10px;color:var(--text-muted);margin-top:1px;"><?= $pLastPurchFmt ?></div>
          </td>

          <!-- Total Sold -->
          <td style="text-align:right;">
            <div style="font-size:13px;font-weight:700;color:#7c3aed;"><?= number_format($pSold) ?></div>
            <div style="font-size:10px;color:var(--text-muted);margin-top:1px;"><?= $pLastSaleFmt ?></div>
          </td>

          <!-- Unit Price -->
          <td style="text-align:right;">
            <div style="font-size:13px;font-weight:700;color:var(--text);">€<?= number_format($pAmt, 2) ?></div>
          </td>

          <!-- Stock Value -->
          <td style="text-align:right;">
            <div style="font-size:13px;font-weight:800;color:#059669;">€<?= number_format($pStockVal, 2) ?></div>
          </td>

          <!-- Aging -->
          <td style="text-align:center;">
            <span style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;background:<?= $ageBg ?>;color:<?= $ageClr ?>;white-space:nowrap;">
              <?= $ageLabel ?>
            </span>
            <?php if ($ageDays !== null): ?>
            <div style="font-size:10px;color:var(--text-muted);margin-top:3px;"><?= $ageDays ?>d</div>
            <?php endif; ?>
          </td>

          <!-- Actions -->
          <td style="text-align:center;">
            <button class="btn btn--outline" title="View Movement Ledger"
                    style="height:28px;padding:0 10px;font-size:11px;"
                    onclick="invOpenLedger(<?= $pid ?>,<?= htmlspecialchars(json_encode($pName),ENT_QUOTES) ?>)">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="11" y2="17"/></svg>
              Ledger
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Empty state -->
  <div id="invEmpty" style="display:none;padding:50px 20px;text-align:center;">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#c0ccd8" stroke-width="1.2" style="margin:0 auto 12px;display:block;"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
    <div style="font-size:14px;font-weight:600;color:var(--text-muted);">No products match your filters.</div>
    <div style="font-size:12px;color:#94a3b8;margin-top:4px;">Try adjusting the search or health filter.</div>
  </div>

</div><!-- /card -->

<!-- ═══════════════════════════════════════════════════
     MOVEMENT LEDGER MODAL
═══════════════════════════════════════════════════ -->
<div id="ledgerModal" class="modal-overlay">
  <div class="modal" style="max-width:860px;width:96%;max-height:92vh;display:flex;flex-direction:column;">

    <div class="modal-hd" style="display:flex;align-items:flex-start;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div>
        <div style="font-size:16px;font-weight:800;color:#1e293b;" id="ledgerTitle">Movement Ledger</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="ledgerSubtitle">Full IN/OUT stock movement history</div>
      </div>
      <button onclick="closeModal('ledgerModal')" style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:26px;line-height:1;margin-top:-4px;">×</button>
    </div>

    <!-- Ledger filter row -->
    <div style="padding:12px 22px;border-bottom:1px solid var(--border);background:#f8fafc;flex-shrink:0;display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
      <div class="fg" style="margin:0;">
        <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px;">From</label>
        <input type="date" id="ledgerFrom" class="form-control" style="height:32px;width:140px;font-size:12px;">
      </div>
      <div class="fg" style="margin:0;">
        <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px;">To</label>
        <input type="date" id="ledgerTo" class="form-control" style="height:32px;width:140px;font-size:12px;">
      </div>
      <button type="button" class="btn btn--primary" style="height:32px;padding:0 14px;font-size:12px;" onclick="invLoadLedger()">Filter</button>
      <button type="button" class="btn btn--ghost" style="height:32px;padding:0 12px;font-size:12px;" onclick="document.getElementById('ledgerFrom').value='';document.getElementById('ledgerTo').value='';invLoadLedger();">Reset</button>
      <div style="margin-left:auto;display:flex;gap:10px;align-items:center;">
        <!-- Summary pills updated by JS -->
        <span id="ledgerSummaryIn"  style="font-size:12px;background:#dcfce7;color:#16a34a;border:1px solid #86efac;padding:3px 10px;border-radius:20px;font-weight:700;">IN: 0</span>
        <span id="ledgerSummaryOut" style="font-size:12px;background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;padding:3px 10px;border-radius:20px;font-weight:700;">OUT: 0</span>
        <span id="ledgerSummaryBal" style="font-size:12px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;padding:3px 10px;border-radius:20px;font-weight:700;">Balance: 0</span>
      </div>
    </div>

    <div id="ledgerBody" style="overflow-y:auto;flex:1;min-height:0;padding:0;">
      <div style="text-align:center;padding:40px;color:var(--text-muted);">Loading…</div>
    </div>

  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════ -->
<style>
/* ══ Pagination bar (mirrors ol-pgbar from order-list) ══ */
.inv-pgbar {
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;flex-wrap:wrap;
  padding:14px 20px;
  border-bottom:1px solid var(--border);
  background:#fff;
}
.inv-pgbar__info { font-size:13px;color:#64748b;white-space:nowrap; }
.inv-pgbar__info strong { color:#1e293b;font-weight:700; }
.inv-pgbar__perpage { display:flex;align-items:center;gap:10px;flex-shrink:0; }
.inv-pgbar__perpage-label { font-size:13px;font-weight:600;color:#374151;white-space:nowrap; }
.inv-pgbar__sel-wrap { position:relative;display:inline-flex;align-items:center; }
.inv-pgbar__sel {
  -webkit-appearance:none;appearance:none;
  height:36px;padding:0 32px 0 14px;
  border:1.5px solid #e2e8f0;border-radius:20px;
  font-size:13px;font-weight:600;color:#1e293b;
  background:#fff;cursor:pointer;outline:none;transition:border-color .15s;
}
.inv-pgbar__sel:hover,.inv-pgbar__sel:focus { border-color:#6366f1; }
.inv-pgbar__sel-arrow { position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#64748b; }
.inv-pgbar__apply {
  height:36px;padding:0 20px;background:#1e293b;color:#fff;
  border:none;border-radius:20px;font-size:13px;font-weight:600;
  cursor:pointer;white-space:nowrap;transition:background .15s;
}
.inv-pgbar__apply:hover { background:#0f172a; }
.inv-pgbar__pager { display:flex;align-items:center;gap:5px;flex-wrap:wrap; }
.inv-pg-nav {
  height:36px;padding:0 16px;
  border:1.5px solid #e2e8f0;border-radius:20px;
  background:#fff;font-size:13px;font-weight:600;color:#374151;
  cursor:pointer;white-space:nowrap;transition:border-color .15s,color .15s;
}
.inv-pg-nav:hover:not(:disabled) { border-color:#6366f1;color:#6366f1; }
.inv-pg-nav:disabled,.inv-pg-nav--disabled { color:#cbd5e1;border-color:#f1f5f9;cursor:default; }
.inv-pg-num {
  width:36px;height:36px;
  border:1.5px solid #e2e8f0;border-radius:50%;
  background:#fff;font-size:13px;font-weight:600;color:#374151;
  cursor:pointer;display:inline-flex;align-items:center;justify-content:center;
  transition:border-color .15s,color .15s,background .15s;flex-shrink:0;
}
.inv-pg-num:hover { border-color:#6366f1;color:#6366f1; }
.inv-pg-dots { font-size:13px;color:#94a3b8;padding:0 2px;display:inline-flex;align-items:center; }
@media(max-width:640px){
  .inv-pgbar { flex-direction:column;align-items:flex-start;gap:10px; }
}
/* ══ Ledger type badges ══ */
.ldg-in  { background:#dcfce7;color:#16a34a;border:1px solid #86efac; }
.ldg-out { background:#fee2e2;color:#dc2626;border:1px solid #fca5a5; }
</style>

<!-- ═══════════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════════ -->
<script>
/* ══════════════════════════════════════════
   TABLE PAGINATION
══════════════════════════════════════════ */
var _invPage    = 1;
var _invPerPage = 20;
var _invRows    = [];

function invInit() {
  _invRows = Array.from(document.querySelectorAll('#invTbody .inv-row'));
  invRender();
}

function invApplyPerPage() {
  _invPerPage = parseInt(document.getElementById('invPerPage').value, 10) || 20;
  _invPage = 1;
  invRender();
}

function invRender() {
  var pp = _invPerPage, total = _invRows.length;
  var pages = Math.max(1, Math.ceil(total / pp));
  if (_invPage > pages) _invPage = pages;
  if (_invPage < 1)     _invPage = 1;
  var start = (_invPage - 1) * pp;
  var end   = Math.min(start + pp, total);

  _invRows.forEach(function(r, i) {
    var vis = (i >= start && i < end);
    r.style.display = vis ? '' : 'none';
    if (vis) r.querySelector('.inv-sno').textContent = i + 1;
  });

  document.getElementById('invCount').textContent      = total;
  document.getElementById('invRangeStart').textContent = total > 0 ? start + 1 : 0;
  document.getElementById('invRangeEnd').textContent   = end;
  document.getElementById('invEmpty').style.display    = total === 0 ? 'block' : 'none';
  _invBuildPager(pages);
}

function _invBuildPager(pages) {
  var pager = document.getElementById('invPager');
  pager.innerHTML = '';
  pager.appendChild(_invNavBtn('Prev', _invPage - 1, _invPage <= 1));
  if (pages > 1) {
    _invPageNums(_invPage, pages).forEach(function(n) {
      if (n === -1) {
        var dots = document.createElement('span');
        dots.className = 'inv-pg-dots'; dots.textContent = '...';
        pager.appendChild(dots);
      } else {
        pager.appendChild(_invNumBtn(n));
      }
    });
  }
  pager.appendChild(_invNavBtn('Next', _invPage + 1, _invPage >= pages));
}

function _invPageNums(cur, total) {
  if (total <= 1) return [];
  var set = new Set();
  if (cur !== 1)     set.add(1);
  if (cur !== total) set.add(total);
  var before = Math.min(2, cur - 1);
  var after  = Math.min(2, total - cur);
  if (before + after < 4) {
    if (cur <= 3)              after  = Math.min(4 - before, total - cur);
    else if (cur >= total - 2) before = Math.min(4 - after,  cur - 1);
  }
  for (var p = cur - before; p <= cur + after; p++) {
    if (p >= 1 && p <= total && p !== cur) set.add(p);
  }
  var arr = Array.from(set).sort(function(a,b){return a-b;});
  var result = [];
  for (var i = 0; i < arr.length; i++) {
    if (i > 0 && arr[i] - arr[i-1] > 1) result.push(-1);
    result.push(arr[i]);
  }
  return result;
}

function _invNavBtn(label, pg, disabled) {
  var b = document.createElement('button');
  b.textContent = label;
  b.className   = 'inv-pg-nav' + (disabled ? ' inv-pg-nav--disabled' : '');
  b.disabled    = disabled;
  if (!disabled) b.onclick = function() { _invPage = pg; invRender(); };
  return b;
}
function _invNumBtn(pg) {
  var b = document.createElement('button');
  b.textContent = String(pg); b.className = 'inv-pg-num';
  b.onclick = function() { _invPage = pg; invRender(); };
  return b;
}

document.addEventListener('DOMContentLoaded', invInit);

/* ══════════════════════════════════════════
   CSV EXPORT
══════════════════════════════════════════ */
function invExportCsv() {
  var headers = ['#','Product','Code','Category','Status','Health','In Stock','Threshold','Total In','Total Sold','Unit Price (€)','Stock Value (€)','Last Purchase','Last Sale','Aging'];
  var rows = [headers];
  document.querySelectorAll('#invTbody .inv-row').forEach(function(r, i) {
    var cells = r.querySelectorAll('td');
    rows.push([
      i + 1,
      cells[1]?.querySelector('div[style*="font-weight:700"]')?.textContent?.trim() || '',
      cells[1]?.querySelector('div[style*="monospace"]')?.textContent?.trim()       || '',
      cells[2]?.querySelector('div')?.textContent?.trim()                           || '',
      cells[1]?.querySelector('span')?.textContent?.trim()                          || '',
      cells[3]?.querySelector('span')?.textContent?.trim()                          || '',
      cells[4]?.querySelector('div:first-child')?.textContent?.trim()               || '',
      cells[5]?.textContent?.trim()                                                 || '',
      cells[6]?.querySelector('div:first-child')?.textContent?.trim()               || '',
      cells[7]?.querySelector('div:first-child')?.textContent?.trim()               || '',
      cells[8]?.querySelector('div')?.textContent?.trim()                           || '',
      cells[9]?.querySelector('div')?.textContent?.trim()                           || '',
      cells[6]?.querySelector('div:last-child')?.textContent?.trim()                || '',
      cells[7]?.querySelector('div:last-child')?.textContent?.trim()                || '',
      cells[10]?.querySelector('span')?.textContent?.trim()                         || '',
    ]);
  });
  var csv = rows.map(function(r) {
    return r.map(function(c) { return '"' + String(c).replace(/"/g,'""') + '"'; }).join(',');
  }).join('\n');
  var a = document.createElement('a');
  a.href = 'data:text/csv;charset=utf-8,﻿' + encodeURIComponent(csv);
  a.download = 'inventory-' + new Date().toISOString().slice(0,10) + '.csv';
  document.body.appendChild(a); a.click(); a.remove();
}

/* ══════════════════════════════════════════
   MOVEMENT LEDGER MODAL
══════════════════════════════════════════ */
var _ledgerProductId   = 0;
var _ledgerProductName = '';

function invOpenLedger(pid, pname) {
  _ledgerProductId   = pid;
  _ledgerProductName = pname;
  document.getElementById('ledgerTitle').textContent    = pname;
  document.getElementById('ledgerSubtitle').textContent = 'Product #' + pid + ' — Stock movement ledger (IN / OUT)';
  document.getElementById('ledgerFrom').value = '';
  document.getElementById('ledgerTo').value   = '';
  document.getElementById('ledgerBody').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted);">Loading…</div>';
  openModal('ledgerModal');
  invLoadLedger();
}

function invLoadLedger() {
  if (!_ledgerProductId) return;
  var from = document.getElementById('ledgerFrom').value || '';
  var to   = document.getElementById('ledgerTo').value   || '';
  var url  = 'service?urlstring=<?= EncryptURL('action=GetProductMovementLedger') ?>'
           + '&product_id=' + _ledgerProductId
           + (from ? '&date_from=' + encodeURIComponent(from) : '')
           + (to   ? '&date_to='   + encodeURIComponent(to)   : '');

  document.getElementById('ledgerBody').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted);">Loading…</div>';

  fetch(url)
    .then(function(r) { return r.json(); })
    .catch(function() { return null; })
    .then(function(data) {
      if (!data || !data.ok) {
        document.getElementById('ledgerBody').innerHTML = '<div style="padding:20px;color:#dc2626;">Failed to load ledger data.</div>';
        return;
      }
      _invRenderLedger(data.movements || []);
    });
}

function _invRenderLedger(movements) {
  /* Summary stats */
  var totalIn = 0, totalOut = 0, lastBal = 0;
  movements.forEach(function(m) {
    if (m.type === 'IN')  totalIn  += m.qty;
    else                  totalOut += m.qty;
    lastBal = m.running_balance;
  });
  document.getElementById('ledgerSummaryIn').textContent  = 'IN: '      + _fmt(totalIn);
  document.getElementById('ledgerSummaryOut').textContent = 'OUT: '     + _fmt(totalOut);
  document.getElementById('ledgerSummaryBal').textContent = 'Balance: ' + _fmt(lastBal);

  if (!movements.length) {
    document.getElementById('ledgerBody').innerHTML =
      '<div style="text-align:center;padding:50px 20px;">'
      + '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#c0ccd8" stroke-width="1.3" style="margin:0 auto 10px;display:block;">'
      + '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'
      + '<div style="font-size:14px;color:var(--text-muted);font-weight:600;">No movements found</div>'
      + '<div style="font-size:12px;color:#94a3b8;margin-top:4px;">No stock IN or OUT for this date range.</div></div>';
    return;
  }

  var html = '<table style="width:100%;border-collapse:collapse;font-size:12px;">'
    + '<thead><tr style="background:#f8fafc;border-bottom:2px solid var(--border);">'
    + '<th style="padding:10px 14px;text-align:left;font-weight:700;color:#64748b;white-space:nowrap;">Date & Time</th>'
    + '<th style="padding:10px 8px;text-align:center;font-weight:700;color:#64748b;">Type</th>'
    + '<th style="padding:10px 8px;text-align:right;font-weight:700;color:#64748b;">Qty</th>'
    + '<th style="padding:10px 14px;text-align:left;font-weight:700;color:#64748b;">Reference / Source</th>'
    + '<th style="padding:10px 8px;text-align:right;font-weight:700;color:#64748b;">Ref #</th>'
    + '<th style="padding:10px 8px;text-align:right;font-weight:700;color:#64748b;">Unit Cost</th>'
    + '<th style="padding:10px 14px;text-align:right;font-weight:700;color:#64748b;">Running Balance</th>'
    + '</tr></thead><tbody>';

  movements.forEach(function(m, idx) {
    var isIn    = m.type === 'IN';
    var tBadge  = isIn
      ? '<span style="font-size:10px;font-weight:800;padding:2px 9px;border-radius:20px;" class="ldg-in">▲ IN</span>'
      : '<span style="font-size:10px;font-weight:800;padding:2px 9px;border-radius:20px;" class="ldg-out">▼ OUT</span>';
    var qtyClr  = isIn ? '#16a34a' : '#dc2626';
    var balClr  = m.running_balance > 0 ? '#2563eb' : (m.running_balance < 0 ? '#dc2626' : '#94a3b8');

    /* Format date */
    var dt = new Date(m.date);
    var dStr = isNaN(dt) ? m.date : dt.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
    var tStr = isNaN(dt) ? '' : dt.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});

    /* Reference link */
    var refStr = '';
    if (m.order_id > 0) {
      refStr = '<a href="order-list" style="color:#4f46e5;font-weight:700;text-decoration:none;font-size:11px;">'
             + (m.order_number || 'ORD-'+m.order_id) + '</a>';
    } else {
      refStr = '<span style="color:#1e293b;font-weight:600;">' + _esc(m.reference_no || '—') + '</span>';
    }

    html += '<tr style="border-bottom:1px solid #f1f5f9;' + (idx % 2 === 0 ? '' : 'background:#fafbfc;') + '">'
      + '<td style="padding:10px 14px;white-space:nowrap;">'
      +   '<div style="font-weight:600;color:#1e293b;">' + dStr + '</div>'
      +   (tStr ? '<div style="font-size:10px;color:#94a3b8;">' + tStr + '</div>' : '')
      + '</td>'
      + '<td style="padding:10px 8px;text-align:center;">' + tBadge + '</td>'
      + '<td style="padding:10px 8px;text-align:right;font-size:14px;font-weight:800;color:' + qtyClr + ';">'
      +   (isIn ? '+' : '−') + _fmt(m.qty) + '</td>'
      + '<td style="padding:10px 14px;">'
      +   '<div style="font-size:12px;font-weight:600;color:#1e293b;">' + _esc(m.reference_name || '—') + '</div>'
      +   (m.order_id > 0 ? '<div style="font-size:10px;color:#94a3b8;margin-top:1px;">Order: ' + refStr + '</div>' : '')
      + '</td>'
      + '<td style="padding:10px 8px;text-align:right;font-family:monospace;color:#64748b;">'
      +   (m.reference_no && !m.order_id ? _esc(m.reference_no) : (m.order_number ? m.order_number : '—'))
      + '</td>'
      + '<td style="padding:10px 8px;text-align:right;font-weight:600;color:#475569;">'
      +   (m.unit_cost > 0 ? '€' + m.unit_cost.toFixed(2) : '—') + '</td>'
      + '<td style="padding:10px 14px;text-align:right;font-size:14px;font-weight:800;color:' + balClr + ';">'
      +   _fmt(m.running_balance) + '</td>'
      + '</tr>';
  });

  html += '</tbody></table>';
  document.getElementById('ledgerBody').innerHTML = html;
}

function _fmt(n) { return parseFloat(n || 0).toLocaleString('en-GB', {maximumFractionDigits:0}); }
function _esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
