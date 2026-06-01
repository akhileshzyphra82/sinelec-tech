<?php
require_once __DIR__ . '/account-helpers.php';
$user = sinelec_require_login();
$currentPage = 'my-list';
$pageTitle = 'My List | Sinelec Technologies';
require_once __DIR__ . '/header.php';

require_once __DIR__ . '/../controller/website_controller.php';
$_wc    = new WebsiteController();
$userId = (int)($user['USER_ID'] ?? 0);

/* ── Real quotes from DB ── */
$quotes = $_wc->getCustomerQuotes($userId);

/* ── Wishlist products from DB ── */
$_pubBase        = rtrim((string)sinelec_env('PUBLIC_BASE_URL', ''), '/');
$wishlistProducts = $_wc->getWishlistProducts($userId);

/* Status display map (DB value → display meta) */
$statusMeta = [
  'Quotation Pending' => ['key' => 'pending',   'label' => 'Pending',    'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fcd34d'],
  'Quotation Sent'    => ['key' => 'sent',       'label' => 'Quote Sent', 'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#86efac'],
  'Order Completed'   => ['key' => 'completed',  'label' => 'Completed',  'color' => '#0369a1', 'bg' => '#f0f9ff', 'border' => '#7dd3fc'],
  'Quotation Cancel'  => ['key' => 'cancelled',  'label' => 'Cancelled',  'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fca5a5'],
];
$statusDefault = ['key' => 'pending', 'label' => 'In Review', 'color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe'];
?>

<main class="account-page">
  <div class="wrap">
    <div class="account-shell">
      <?php sinelec_render_account_nav($currentPage); ?>

      <section class="account-main">
        <article class="account-panel ml-shell">

          <div class="ml-page-head">
            <div>
              <h1>My List</h1>
              <p>Manage your saved products and track quote requests.</p>
            </div>
          </div>

          <!-- ── Top-level tabs: Wishlist / Quotes ── -->
          <div class="ml-tab-row" role="tablist" aria-label="My List tabs">
            <button type="button" class="ml-tab-btn is-active" data-ml-tab="wishlist" role="tab" aria-selected="true">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              My Wishlist
              <span class="ml-tab-count" id="mlWishlistCount"><?= count($wishlistProducts) ?></span>
            </button>
            <button type="button" class="ml-tab-btn" data-ml-tab="quotes" role="tab" aria-selected="false">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              Quotes
              <span class="ml-tab-count"><?= count($quotes) ?></span>
            </button>

          </div>

          <!-- ── Wishlist Panel ── -->
          <div class="ml-panel is-active" id="mlWishlistPanel" data-ml-panel="wishlist">
            <?php if (empty($wishlistProducts)): ?>
            <div class="ml-empty">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              <p>Your wishlist is empty.</p>
              <a href="products" class="ml-empty-btn">Browse Products</a>
            </div>
            <?php else: ?>
            <div class="ml-wl-grid" id="mlWishlistGrid">
              <?php foreach ($wishlistProducts as $_wp):
                $_wpId      = (int)(float)($_wp->PRODUCT_ID ?? 0);
                $_wpName    = (string)($_wp->PRODUCT_NAME ?? '');
                $_wpCode    = (string)($_wp->PRODUCT_CODE ?? '');
                $_wpCat     = (string)($_wp->PRODUCT_CATEGORY_NAME ?? '');
                $_wpAmt     = (float)($_wp->PRODUCT_AMT ?? 0);
                $_wpOffer   = (float)($_wp->OFFER_PERCENTAGE ?? 0);
                $_wpOrigAmt = $_wpOffer > 0 ? round($_wpAmt * (1 + $_wpOffer / 100), 2) : 0;
                $_wpStock   = (int)(float)($_wp->TOTAL_REMAINING ?? 0);
                $_wpRating  = (float)($_wp->RATING ?? 0);
                $_wpThumb   = trim((string)($_wp->THUMB_PATH ?? ''));
                $_wpImgUrl  = $_wpThumb !== '' ? $_pubBase . '/' . ltrim($_wpThumb, '/') : '';
                $_wpDate    = (string)($_wp->WISHLISTED_AT ?? '');
              ?>
              <article class="ml-wl-card" id="ml-wl-card-<?= $_wpId ?>" data-product-id="<?= $_wpId ?>">
                <!-- Image -->
                <div class="ml-wl-img-wrap" onclick="openPDP(<?= $_wpId ?>)" style="cursor:pointer;">
                  <img src="<?= htmlspecialchars($_wpImgUrl ?: 'https://placehold.co/120x120/f0f4f9/2563eb?text='.urlencode($_wpCode)) ?>"
                       alt="<?= htmlspecialchars($_wpName) ?>" loading="lazy"
                       onerror="this.src='https://placehold.co/120x120/f0f4f9/2563eb?text=<?= urlencode($_wpCode) ?>'">
                </div>
                <!-- Info -->
                <div class="ml-wl-info" onclick="openPDP(<?= $_wpId ?>)" style="cursor:pointer;">
                  <?php if ($_wpCat): ?>
                  <div style="font-size:11px;color:#5f728b;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">
                    <?= htmlspecialchars($_wpCat) ?>
                  </div>
                  <?php endif; ?>
                  <div class="ml-wl-name"><?= htmlspecialchars($_wpName) ?></div>
                  <div class="ml-wl-meta">
                    <?php if ($_wpCode): ?>
                    <span>SKU: <strong><?= htmlspecialchars($_wpCode) ?></strong></span>
                    <?php endif; ?>
                    <?php if ($_wpRating > 0): ?>
                    <span>Rating: <strong><?= number_format($_wpRating, 1) ?> ★</strong></span>
                    <?php endif; ?>
                    <span><?= $_wpStock > 0 ? '<strong style="color:#16a34a;">In Stock</strong>' : '<strong style="color:#dc2626;">Out of Stock</strong>' ?></span>
                  </div>
                  <?php if ($_wpDate): ?>
                  <div style="font-size:10.5px;color:#9baab8;margin-top:5px;">
                    Saved on <?= date('d M Y', strtotime($_wpDate)) ?>
                  </div>
                  <?php endif; ?>
                </div>
                <!-- Actions -->
                <div class="ml-wl-actions">
                  <div>
                    <div class="ml-wl-price">
                      <?= '€' . number_format($_wpAmt, 2) ?>
                      <?php if ($_wpOrigAmt > 0): ?>
                      <small style="text-decoration:line-through;color:#9baab8;">€<?= number_format($_wpOrigAmt, 2) ?></small>
                      <?php endif; ?>
                    </div>
                  </div>
                  <button type="button" class="ml-wl-btn-cart"
                          onclick="mlWlAddToCart(<?= $_wpId ?>, this)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    Add to Cart
                  </button>
                  <button type="button" class="ml-wl-btn-remove"
                          onclick="mlWlRemove(<?= $_wpId ?>, this)">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    Remove
                  </button>
                </div>
              </article>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- ── Quotes Panel ── -->
          <div class="ml-panel" id="mlQuotesPanel" data-ml-panel="quotes" hidden>
            <?php if (empty($quotes)): ?>
            <div class="ml-empty">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              <p>You haven't submitted any quote requests yet.</p>
              <a href="request-a-quote" class="ml-empty-btn">Request a Quote</a>
            </div>
            <?php else: ?>
            <div class="ml-qt-grid">
              <?php foreach ($quotes as $qt):
                $qid       = (int)(float)($qt->ENQUIRY_QUOTE_ID  ?? 0);
                $dbStatus  = (string)($qt->ENQUIRY_STATUS        ?? 'Quotation Pending');
                $rawDate   = (string)($qt->ENQUIRY_DATE          ?? '');
                $total     = (float)($qt->ENQUIRY_TOTAL_AMT      ?? 0);
                $vatNum    = trim((string)($qt->VAT_NUMBER        ?? ''));
                $remark    = trim((string)($qt->REMARK           ?? ''));
                $sm        = $statusMeta[$dbStatus] ?? $statusDefault;
                $fmtDate   = $rawDate ? date('d M Y', strtotime($rawDate)) : '—';
                $products  = $qt->products ?? [];
                $itemCount = count($products);
              ?>
              <article class="ml-qt-card">

                <!-- Quote header -->
                <div class="ml-qt-head">
                  <div class="ml-qt-head-left">
                    <span class="ml-qt-id">Quote #<?= $qid ?></span>
                    <span class="ml-qt-date">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                      <?= htmlspecialchars($fmtDate) ?>
                    </span>
                    <span class="ml-qt-items"><?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?></span>
                  </div>
                  <div class="ml-qt-head-right">
                    <span class="ml-qt-badge" style="color:<?= $sm['color'] ?>;background:<?= $sm['bg'] ?>;border-color:<?= $sm['border'] ?>">
                      <?php if ($sm['key'] === 'pending'):   ?><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><?php endif; ?>
                      <?php if ($sm['key'] === 'sent'):      ?><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><?php endif; ?>
                      <?php if ($sm['key'] === 'completed'): ?><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><?php endif; ?>
                      <?php if ($sm['key'] === 'cancelled'): ?><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg><?php endif; ?>
                      <?php if (!in_array($sm['key'],['pending','sent','completed','cancelled'])): ?><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><?php endif; ?>
                      <?= htmlspecialchars($sm['label']) ?>
                    </span>
                    <a href="../admin/quotation-pdf?id=<?= $qid ?>&uid=<?= $userId ?>"
                       target="_blank" rel="noopener"
                       class="ml-qt-pdf-btn"
                       title="View PDF Quotation">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                      PDF
                    </a>
                  </div>
                </div>

                <!-- Products list -->
                <div class="ml-qt-products">
                  <?php foreach ($products as $pi => $p):
                    $pName  = trim((string)($p->PRODUCT_NAME ?? ''));
                    $pCode  = trim((string)($p->PRODUCT_CODE ?? ''));
                    $pQty   = (int)(float)($p->PRODUCT_QUANTITY ?? 0);
                    $pPrice = (float)($p->PRODUCT_AMT ?? 0);
                    $pLine  = round($pPrice * $pQty, 2);
                    if ($pi >= 3 && $itemCount > 3): ?>
                  <div class="ml-qt-prod-more">+<?= $itemCount - 3 ?> more item<?= ($itemCount - 3) !== 1 ? 's' : '' ?></div>
                    <?php break; endif; ?>
                  <div class="ml-qt-prod-row">
                    <div class="ml-qt-prod-info">
                      <span class="ml-qt-prod-name"><?= htmlspecialchars($pName ?: 'Product #' . (int)(float)($p->PRODUCT_ID ?? 0)) ?></span>
                      <?php if ($pCode !== ''): ?>
                      <span class="ml-qt-prod-code"><?= htmlspecialchars($pCode) ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="ml-qt-prod-right">
                      <span class="ml-qt-prod-qty">×<?= $pQty ?></span>
                      <?php if ($pPrice > 0): ?>
                      <span class="ml-qt-prod-price">€<?= number_format($pLine, 2) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>

                <!-- Footer row: total + notes -->
                <div class="ml-qt-foot">
                  <div class="ml-qt-foot-left">
                    <?php if ($vatNum !== ''): ?>
                    <span class="ml-qt-vat">VAT No: <?= htmlspecialchars($vatNum) ?></span>
                    <?php endif; ?>
                    <?php if ($remark !== ''): ?>
                    <span class="ml-qt-note" title="<?= htmlspecialchars($remark) ?>">
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                      <?= htmlspecialchars(mb_strimwidth($remark, 0, 60, '…')) ?>
                    </span>
                    <?php endif; ?>
                  </div>
                  <?php if ($total > 0): ?>
                  <div class="ml-qt-total">
                    <span class="ml-qt-total-label">Est. Total</span>
                    <span class="ml-qt-total-val">€<?= number_format($total, 2) ?></span>
                  </div>
                  <?php endif; ?>
                </div>

              </article>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

        </article>
      </section>
    </div>
  </div>
</main>

<style>
/* ── Shell ──────────────────────────────────────────── */
.ml-shell {
  padding: 18px;
  background: #f3f5f7;
  border-radius: 20px;
}
.ml-page-head { margin-bottom: 14px; }
.ml-page-head h1 {
  font-size: clamp(1.2rem, 2vw, 1.6rem);
  color: #182a43;
  font-weight: 700;
}
.ml-page-head p { margin-top: 4px; color: #5f728b; font-size: 12px; }

/* ── Top tabs ────────────────────────────────────────── */
.ml-tab-row {
  display: inline-flex;
  gap: 6px;
  padding: 5px;
  border-radius: 14px;
  background: #e8edf3;
  border: 1px solid #d6dde6;
  margin-bottom: 16px;
}
.ml-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  min-height: 38px;
  padding: 0 18px;
  border-radius: 10px;
  border: 1px solid transparent;
  background: transparent;
  color: #324b69;
  font-size: 12px;
  font-weight: 700;
  transition: all .18s;
  cursor: pointer;
}
.ml-tab-btn.is-active {
  background: #fff;
  color: #112d4b;
  border-color: #d3dbe5;
  box-shadow: 0 4px 10px rgba(20,33,56,.06);
}
.ml-tab-btn svg { flex-shrink: 0; opacity: .65; }
.ml-tab-btn.is-active svg { opacity: 1; }
.ml-tab-count {
  min-width: 20px;
  height: 18px;
  padding: 0 6px;
  border-radius: 20px;
  background: #dde4ef;
  color: #2d4b6e;
  font-size: 10px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.ml-tab-btn.is-active .ml-tab-count { background: #2563eb; color: #fff; }

.ml-panel { display: none; }
.ml-panel.is-active { display: block; }

/* ── Wishlist grid ───────────────────────────────────── */
.ml-wl-grid {
  display: grid;
  gap: 10px;
}
.ml-wl-card {
  display: grid;
  grid-template-columns: 90px minmax(0,1fr) minmax(130px,.4fr);
  gap: 14px;
  align-items: start;
  padding: 14px 16px;
  background: #fff;
  border: 1px solid #d3d9e0;
  border-radius: 10px;
  transition: box-shadow .15s;
}
.ml-wl-card:hover { box-shadow: 0 4px 16px rgba(15,30,55,.08); }
.ml-wl-img-wrap {
  width: 80px;
  height: 80px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #dde4ec;
  background: #f4f6f9;
  flex-shrink: 0;
}
.ml-wl-img-wrap img { width: 100%; height: 100%; object-fit: contain; padding: 6px; }
.ml-wl-info { min-width: 0; }
.ml-wl-name {
  font-size: 14px;
  font-weight: 700;
  color: #1a2d42;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 5px;
}
.ml-wl-meta {
  font-size: 11.5px;
  color: #5f728b;
  line-height: 1.5;
}
.ml-wl-meta span { display: inline-block; margin-right: 12px; }
.ml-wl-actions { display: flex; flex-direction: column; gap: 8px; align-items: flex-end; padding-top: 4px; }
.ml-wl-price { font-size: 17px; font-weight: 700; color: #1a2d42; }
.ml-wl-price small { font-size: 11px; color: #7a92ab; font-weight: 500; display: block; }
.ml-wl-btn-cart {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 34px;
  padding: 0 14px;
  border-radius: 8px;
  background: #2563eb;
  color: #fff;
  font-size: 11.5px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: background .15s;
  white-space: nowrap;
}
.ml-wl-btn-cart:hover { background: #1d4ed8; }
.ml-wl-btn-remove {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 30px;
  padding: 0 12px;
  border-radius: 7px;
  border: 1.5px solid #e0e6ee;
  background: #fff;
  color: #dc2626;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color .15s, background .15s;
  white-space: nowrap;
}
.ml-wl-btn-remove:hover { border-color: #dc2626; background: #fff5f5; }

.ml-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 52px 20px;
  background: #fff;
  border: 1px dashed #d0d9e4;
  border-radius: 12px;
  text-align: center;
  color: #b0bdcb;
}
.ml-empty svg { opacity: .4; }
.ml-empty p { font-size: 13px; color: #7a92ab; margin: 0; }
.ml-empty-btn {
  display: inline-flex;
  align-items: center;
  height: 34px;
  padding: 0 20px;
  border-radius: 8px;
  background: #2563eb;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  transition: background .15s;
}
.ml-empty-btn:hover { background: #1d4ed8; }

/* ── Quotes grid ─────────────────────────────────────── */
.ml-qt-grid { display: grid; gap: 12px; }
.ml-qt-card {
  background: #fff;
  border: 1px solid #d3d9e0;
  border-radius: 10px;
  overflow: hidden;
  transition: box-shadow .15s;
}
.ml-qt-card:hover { box-shadow: 0 4px 18px rgba(15,30,55,.08); }

/* Header */
.ml-qt-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 16px;
  background: #f8fafc;
  border-bottom: 1px solid #edf0f5;
  flex-wrap: wrap;
}
.ml-qt-head-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.ml-qt-head-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
.ml-qt-id {
  font-size: 13px;
  font-weight: 700;
  color: #1a2d42;
  letter-spacing: .01em;
}
.ml-qt-date {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11.5px;
  color: #6b82a0;
}
.ml-qt-items {
  font-size: 11px;
  color: #94a3b8;
  background: #f1f5f9;
  padding: 2px 8px;
  border-radius: 20px;
  font-weight: 600;
}
.ml-qt-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 26px;
  padding: 0 11px;
  border-radius: 6px;
  border: 1px solid;
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: .02em;
  white-space: nowrap;
}
.ml-qt-pdf-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 28px;
  padding: 0 12px;
  border-radius: 6px;
  background: #1e293b;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
  transition: background .15s;
}
.ml-qt-pdf-btn:hover { background: #0f172a; }

/* Products list */
.ml-qt-products {
  padding: 12px 16px 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.ml-qt-prod-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 7px 10px;
  background: #f8fafc;
  border-radius: 6px;
  border: 1px solid #edf0f5;
}
.ml-qt-prod-info { min-width: 0; flex: 1; }
.ml-qt-prod-name {
  font-size: 12.5px;
  font-weight: 600;
  color: #1a2d42;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ml-qt-prod-code {
  font-size: 10.5px;
  color: #94a3b8;
  font-family: monospace;
  display: block;
  margin-top: 1px;
}
.ml-qt-prod-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}
.ml-qt-prod-qty {
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
  min-width: 28px;
  text-align: center;
}
.ml-qt-prod-price {
  font-size: 12.5px;
  font-weight: 700;
  color: #1d4ed8;
  min-width: 64px;
  text-align: right;
}
.ml-qt-prod-more {
  font-size: 11.5px;
  color: #94a3b8;
  padding: 4px 10px;
  font-style: italic;
}

/* Footer row */
.ml-qt-foot {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 16px 14px;
  flex-wrap: wrap;
}
.ml-qt-foot-left {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  flex: 1;
}
.ml-qt-vat {
  font-size: 11px;
  color: #64748b;
  font-weight: 600;
}
.ml-qt-note {
  display: inline-flex;
  align-items: flex-start;
  gap: 5px;
  font-size: 11.5px;
  color: #64748b;
  line-height: 1.45;
}
.ml-qt-total {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 1px;
  flex-shrink: 0;
}
.ml-qt-total-label { font-size: 10.5px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.ml-qt-total-val   { font-size: 16px; font-weight: 800; color: #1d4ed8; }

/* ── Responsive ─────────────────────────────────────── */
@media (max-width: 900px) {
  .ml-tab-row { width: 100%; display: grid; grid-template-columns: 1fr 1fr; }
  .ml-tab-btn { width: 100%; justify-content: center; }
  .ml-wl-card { grid-template-columns: 80px minmax(0,1fr); gap: 12px; padding: 12px; }
  .ml-wl-actions { flex-direction: row; align-items: center; grid-column: 1 / -1; border-top: 1px dashed #d6dde6; padding-top: 10px; margin-top: 2px; justify-content: space-between; }
  .ml-wl-price small { display: inline; margin-left: 4px; }
}
@media (max-width: 640px) {
  .ml-shell { padding: 12px; }
  .ml-page-head h1 { font-size: 1rem; }
  .ml-tab-btn { min-height: 34px; font-size: 11px; padding: 0 10px; gap: 5px; }
  .ml-tab-btn svg { display: none; }
  .ml-wl-name { font-size: 13px; }
  .ml-wl-meta { font-size: 11px; }
  .ml-qt-id { font-size: 12px; }
  .ml-qt-product-name { font-size: 13px; }
}
@media (max-width: 480px) {
  .ml-wl-card { grid-template-columns: 70px minmax(0,1fr); gap: 10px; padding: 10px; }
  .ml-wl-img-wrap { width: 64px; height: 64px; }
  .ml-wl-btn-cart, .ml-wl-btn-remove { height: 30px; font-size: 11px; padding: 0 10px; }
  .ml-qt-head { padding: 10px 12px; }
  .ml-qt-product { padding: 10px 12px 0; }
  .ml-qt-history { padding: 8px 12px 12px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

  /* ── Tab switching ── */
  var tabBtns  = document.querySelectorAll('.ml-tab-btn');
  var panels   = document.querySelectorAll('.ml-panel');

  function mlActivateTab(target) {
    tabBtns.forEach(function (b) {
      var match = b.dataset.mlTab === target;
      b.classList.toggle('is-active', match);
      b.setAttribute('aria-selected', match ? 'true' : 'false');
    });
    panels.forEach(function (p) {
      var active = p.dataset.mlPanel === target;
      p.classList.toggle('is-active', active);
      p.hidden = !active;
    });
  }

  tabBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      mlActivateTab(btn.dataset.mlTab);
      history.replaceState(null, '', '#' + btn.dataset.mlTab);
    });
  });

  /* Activate tab from URL hash on load */
  var hashTab = (location.hash || '').replace('#', '').toLowerCase();
  if (hashTab && document.querySelector('[data-ml-tab="' + hashTab + '"]')) {
    mlActivateTab(hashTab);
  }


  /* ── Wishlist: AJAX remove ── */
  window.mlWlRemove = function (productId, btn) {
    btn.disabled = true;
    btn.textContent = 'Removing…';
    var fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('product_id', productId);
    fetch('ajax/wishlist', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(data) {
        if (!data.ok) { btn.disabled = false; btn.textContent = 'Remove'; return; }
        /* Animate card out */
        var card = document.getElementById('ml-wl-card-' + productId);
        if (card) {
          card.style.transition = 'opacity .25s, transform .25s';
          card.style.opacity = '0';
          card.style.transform = 'translateX(20px)';
          setTimeout(function () {
            card.remove();
            /* Update count badge */
            var badge = document.getElementById('mlWishlistCount');
            if (badge) badge.textContent = Math.max(0, parseInt(badge.textContent, 10) - 1);
            /* Show empty state if no cards left */
            var grid = document.getElementById('mlWishlistGrid');
            if (grid && !grid.querySelector('.ml-wl-card')) {
              grid.innerHTML = '<div class="ml-empty"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg><p>Your wishlist is empty.</p><a href="products" class="ml-empty-btn">Browse Products</a></div>';
            }
          }, 260);
        }
        /* Sync global wishIds in app.js if available */
        if (window.S && Array.isArray(window.S.wishIds)) {
          var idx = window.S.wishIds.indexOf(productId);
          if (idx > -1) window.S.wishIds.splice(idx, 1);
        }
        if (typeof toast === 'function') toast('Removed from wishlist', 'warn');
      })
      .catch(function() { btn.disabled = false; btn.textContent = 'Remove'; });
  };

  /* ── Wishlist: Add to cart from my-list ── */
  window.mlWlAddToCart = function (productId, btn) {
    /* Delegate to app.js atcClick via product lookup, or direct API call */
    if (typeof openPDP === 'function') {
      openPDP(productId);
    } else {
      btn.textContent = 'Opening…';
      window.location.href = 'product?id=' + productId;
    }
  };
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
