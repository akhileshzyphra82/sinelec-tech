<?php
require_once '../common/functions.php';
require_once '../controller/website_controller.php';

$ctrl      = new WebsiteController();
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row       = $ctrl->getProductById($productId);

/* ── 404 if not found ─────────────────────────────────────────── */
if (!$row) {
    http_response_code(404);
    $currentPage = 'products';
    $pageTitle   = 'Product Not Found — Sinelec Tech';
    require_once 'header.php';
    ?>
    <main>
      <div class="wrap page-wrap">
        <div class="page-hero">
          <h1 class="page-title">Product Not Found</h1>
          <p class="page-sub">The product you're looking for doesn't exist or may have been removed.</p>
          <a href="products" class="btn btn-blue">Browse All Products</a>
        </div>
      </div>
    </main>
    <?php
    require_once 'footer.php';
    exit;
}

/* ── Normalise DB row → template variables ────────────────────── */
$BASE_URL    = rtrim((string)sinelec_env('PUBLIC_BASE_URL', ''), '/');

/*
 * Price logic:
 *   product_amt  = the FINAL price shown to the customer (already discounted)
 *   If offer_percentage > 0: original (crossed-out) = product_amt * (1 + offer_percentage/100)
 *   If offer_percentage is 0/null: no crossed-out price shown
 */
$baseEUR  = (float)($row->PRODUCT_AMT       ?? 0);
$offerPct = (float)($row->OFFER_PERCENTAGE  ?? 0);
$origEUR  = $offerPct > 0 ? round($baseEUR * (1 + $offerPct / 100), 2) : 0;
$savePct  = $offerPct > 0 ? (int)round($offerPct) : 0;

$stock     = (int)(float)($row->TOTAL_REMAINING ?? 0);
$label     = strtolower(trim((string)($row->LABEL ?? '')));
$sku       = (string)($row->PRODUCT_CODE ?? '');
$name      = (string)($row->PRODUCT_NAME ?? '');
$catId     = (int)(float)($row->PRODUCT_CATEGORY_ID   ?? 0);
$catName   = (string)($row->PRODUCT_CATEGORY_NAME     ?? '');
$parentCat = (string)($row->PARENT_CATEGORY_NAME      ?? '');
$rating    = (float)($row->RATING    ?? 0);
$totalSold = (int)(float)($row->TOTAL_SOLD ?? 0);

/* Short description shown under heading (product_description) */
$shortDesc = trim((string)($row->PRODUCT_DESCRIPTION ?? ''));

/* Tab content */
$tabDesc    = trim((string)($row->PRODUCT_DETAILS       ?? ''));   /* Description tab */
$tabSpec    = trim((string)($row->PRODUCT_SPECIFICATION ?? ''));   /* Specifications tab */

/* ── Product images ───────────────────────────────────────────── */
$imgRows  = $ctrl->getProductImages($productId);
$images   = array_map(fn($r) => $BASE_URL . '/' . ltrim((string)($r->PRODUCT_IMAGE_PATH ?? ''), '/'), $imgRows);
$hasImgs  = count($images) > 0;
$mainImg  = $hasImgs ? $images[0] : '';

/* ── Downloads (Product Manuals) ──────────────────────────────── */
$manualRows = $ctrl->getProductManuals($productId);

/* ── Sample Code ──────────────────────────────────────────────── */
$sampleRows = $ctrl->getProductSampleCode($productId);

$currentPage = 'product';
$pageTitle   = htmlspecialchars($name) . ' — Sinelec Tech';

/* Build a product object for CURRENT_PRODUCT JS bridge */
$jsProduct = [
    'id'            => $productId,
    'sku'           => $sku,
    'name'          => $name,
    'category'      => (string)$catId,
    'categoryName'  => $catName,
    'image'         => $mainImg,
    'price'         => $baseEUR,
    'originalPrice' => $origEUR > 0 ? $origEUR : null,
    'stock'         => $stock,
    'rating'        => $rating,
    'reviews'       => $totalSold,
    'label'         => $label,
    'badge'         => $label,
    'isNew'         => $label === 'new',
    'isFeatured'    => in_array($label, ['featured','bestseller','hot']),
    'description'   => $shortDesc,
];

require_once 'header.php';
?>

<main>
<div class="wrap page-wrap pdp-wrap">

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="index">Home</a>
    <span class="bc-sep">›</span>
    <a href="products">Products</a>
    <?php if ($parentCat && $parentCat !== $catName): ?>
    <span class="bc-sep">›</span>
    <a href="products?cat_id=<?= $catId ?>"><?= htmlspecialchars($parentCat) ?></a>
    <?php endif; ?>
    <?php if ($catName): ?>
    <span class="bc-sep">›</span>
    <a href="products?cat_id=<?= $catId ?>"><?= htmlspecialchars($catName) ?></a>
    <?php endif; ?>
    <span class="bc-sep">›</span>
    <span><?= htmlspecialchars($name) ?></span>
  </nav>

  <!-- ── Top 2-col grid ───────────────────────────────────────── -->
  <div class="pdp-top">

    <!-- Left: Image panel -->
    <div class="pdp-img-card">

      <?php if ($hasImgs): ?>
      <!-- Main image -->
      <div class="pdp-main-image-wrap">
        <img src="<?= htmlspecialchars($mainImg) ?>"
             alt="<?= htmlspecialchars($name) ?>"
             class="pdp-main-image"
             id="pdpMainImg"
             onerror="this.style.display='none';document.getElementById('pdpImgPlaceholder').style.display='flex'">
        <div class="pdp-img-placeholder" id="pdpImgPlaceholder" style="display:none;">
          <svg width="72" height="72" viewBox="0 0 38 38" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="9" y="9" width="20" height="20" rx="2"/>
            <line x1="9" y1="14.5" x2="5" y2="14.5"/><line x1="9" y1="19" x2="3" y2="19"/><line x1="9" y1="23.5" x2="5" y2="23.5"/>
            <line x1="29" y1="14.5" x2="33" y2="14.5"/><line x1="29" y1="19" x2="35" y2="19"/><line x1="29" y1="23.5" x2="33" y2="23.5"/>
            <line x1="14.5" y1="9" x2="14.5" y2="5"/><line x1="19" y1="9" x2="19" y2="3"/><line x1="23.5" y1="9" x2="23.5" y2="5"/>
            <line x1="14.5" y1="29" x2="14.5" y2="33"/><line x1="19" y1="29" x2="19" y2="35"/><line x1="23.5" y1="29" x2="23.5" y2="33"/>
            <rect x="15" y="15" width="8" height="8" rx="1" fill="currentColor" fill-opacity=".15"/>
          </svg>
        </div>
        <?php if (count($images) > 1): ?>
        <button type="button" class="pdp-image-nav pdp-image-nav--prev" onclick="pdpStepThumb(-1)" aria-label="Previous image">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button type="button" class="pdp-image-nav pdp-image-nav--next" onclick="pdpStepThumb(1)" aria-label="Next image">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
        <?php endif; ?>
      </div>

      <!-- Thumbnail strip -->
      <?php if (count($images) > 1): ?>
      <div class="pdp-thumbs-strip">
        <?php foreach ($images as $ti => $imgUrl): ?>
        <img src="<?= htmlspecialchars($imgUrl) ?>"
             alt="<?= htmlspecialchars($name) ?> view <?= $ti + 1 ?>"
             class="pdp-thumb-img<?= $ti === 0 ? ' active' : '' ?>"
             data-full="<?= htmlspecialchars($imgUrl) ?>"
             onclick="pdpSetThumb(this, '<?= htmlspecialchars($imgUrl, ENT_QUOTES) ?>', <?= $ti ?>)"
             onerror="this.style.display='none'">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <!-- No images — placeholder -->
      <div class="pdp-main-image pdp-img-placeholder" id="pdpMainImg">
        <svg width="80" height="80" viewBox="0 0 38 38" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <rect x="9" y="9" width="20" height="20" rx="2"/>
          <line x1="9" y1="14.5" x2="5" y2="14.5"/><line x1="9" y1="19" x2="3" y2="19"/><line x1="9" y1="23.5" x2="5" y2="23.5"/>
          <line x1="29" y1="14.5" x2="33" y2="14.5"/><line x1="29" y1="19" x2="35" y2="19"/><line x1="29" y1="23.5" x2="33" y2="23.5"/>
          <line x1="14.5" y1="9" x2="14.5" y2="5"/><line x1="19" y1="9" x2="19" y2="3"/><line x1="23.5" y1="9" x2="23.5" y2="5"/>
          <line x1="14.5" y1="29" x2="14.5" y2="33"/><line x1="19" y1="29" x2="19" y2="35"/><line x1="23.5" y1="29" x2="23.5" y2="33"/>
          <rect x="15" y="15" width="8" height="8" rx="1" fill="currentColor" fill-opacity=".15"/>
        </svg>
        <?php if ($sku): ?>
        <span><?= htmlspecialchars($sku) ?></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>

    <!-- Right: Purchase card -->
    <div class="pdp-info-card">

      <!-- Share button — top right corner -->
      <button class="pdp2-share-corner" id="copyLinkBtn" onclick="copyLink()" title="Copy link">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
          <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
        </svg>
        <span>Share</span>
      </button>

      <!-- ① Meta row: badge + SKU + category -->
      <div class="pdp2-meta">
        <?php if ($label): ?>
        <span class="pdp2-badge pdp2-badge--<?= htmlspecialchars($label) ?>"><?= strtoupper(htmlspecialchars($label)) ?></span>
        <?php endif; ?>
        <?php if ($catName): ?>
        <a class="pdp2-cat-link" href="products?cat_id=<?= $catId ?>">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="7" height="7"/><rect x="15" y="3" width="7" height="7"/><rect x="15" y="14" width="7" height="7"/><rect x="2" y="14" width="7" height="7"/></svg>
          <?= htmlspecialchars($catName) ?>
        </a>
        <?php endif; ?>
        <?php if ($sku): ?>
        <span class="pdp2-sku">SKU: <strong><?= htmlspecialchars($sku) ?></strong></span>
        <?php endif; ?>
      </div>

      <!-- ② Product name -->
      <h1 class="pdp2-name"><?= htmlspecialchars($name) ?></h1>

      <!-- ③ Short description with Read More -->
      <?php if ($shortDesc): ?>
      <div class="pdp2-short-desc-wrap">
        <div class="pdp2-short-desc" id="pdpShortDesc"><?= $shortDesc ?></div>
        <button class="pdp2-read-more-btn" id="pdpReadMoreBtn" onclick="pdpToggleDesc()">Read more</button>
      </div>
      <?php endif; ?>

      <!-- ④ Rating -->
      <div class="pdp2-rating-row">
        <div class="pdp2-stars" id="pdpStars"></div>
        <?php if ($rating > 0): ?>
        <span class="pdp2-rating-val"><?= number_format($rating, 1) ?></span>
        <?php endif; ?>
      </div>

      <!-- ⑤ Price block (price left, qty right) -->
      <div class="pdp2-price-block">
        <div class="pdp2-price-qty-row">
          <!-- Left: price + strikethrough + delivery -->
          <div class="pdp2-price-left">
            <div class="pdp2-price-row">
              <span class="pdp2-price">€<?= number_format($baseEUR, 2) ?></span>
              <?php if ($origEUR > 0): ?>
              <span class="pdp2-orig">€<?= number_format($origEUR, 2) ?></span>
              <span class="pdp2-save-badge">
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Save <?= $savePct ?>%
              </span>
              <?php endif; ?>
            </div>
            <div class="pdp2-delivery-row">
              <span class="pdp2-delivery-chip">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Expected delivery in <strong>7 days</strong>
              </span>
            </div>
          </div>
          <!-- Right: qty control -->
          <div class="pdp2-qty-right">
            <span class="pdp2-qty-label">Qty</span>
            <div class="pdp2-qty">
              <button class="pdp2-qty-btn" onclick="pdpQtyChange(-1)" aria-label="Decrease">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </button>
              <input type="number" class="pdp2-qty-inp" id="pdpQty" value="1" min="1" max="9999" aria-label="Quantity">
              <button class="pdp2-qty-btn" onclick="pdpQtyChange(1)" aria-label="Increase">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ⑥ Action buttons -->
      <div class="pdp2-action-block">
        <div class="pdp2-btn-row">
          <button class="pdp2-cart-btn" id="pdpAtcBtn" onclick="pdpAddToCart()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
              <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
            </svg>
            Add to Cart
          </button>
          <button class="pdp2-buynow-btn" onclick="pdpBuyNow()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Buy Now
          </button>
          <button class="pdp2-wish-btn" id="pdpWishBtn" onclick="pdpToggleWish()" title="Wishlist" aria-label="Add to Wishlist">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
          </button>
        </div>
      </div>

      <!-- ⑦ Trust badges -->
      <div class="pdp2-trust">
        <div class="pdp2-trust-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
          <span>Secure Checkout</span>
        </div>
        <div class="pdp2-trust-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
          <span>Fast Delivery</span>
        </div>
        <div class="pdp2-trust-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
          <span>Easy Returns</span>
        </div>
      </div>

    </div><!-- /pdp-info-card -->
  </div><!-- /pdp-top -->

  <!-- ── Tab section ──────────────────────────────────────────── -->
  <div class="pdp-tab-section">
    <div class="pdp-tab-bar" role="tablist">
      <button class="pdp-tab-btn active" data-tab="description"    onclick="pdpTab('description')"    role="tab">Description</button>
      <button class="pdp-tab-btn"        data-tab="specifications" onclick="pdpTab('specifications')" role="tab">Specifications</button>
      <button class="pdp-tab-btn"        data-tab="downloads"      onclick="pdpTab('downloads')"      role="tab">Downloads</button>
      <button class="pdp-tab-btn"        data-tab="samplecode"     onclick="pdpTab('samplecode')"     role="tab">Sample Code</button>
    </div>

    <!-- Description tab — product_details -->
    <div class="pdp-tab-panel active" data-tab="description">
      <?php if ($tabDesc): ?>
      <div class="pdp-desc-body"><?= $tabDesc ?></div>
      <?php else: ?>
      <p class="pdp-desc-empty">No description available for this product.</p>
      <?php endif; ?>
    </div>

    <!-- Specifications tab — product_specification -->
    <div class="pdp-tab-panel" data-tab="specifications">
      <?php if ($tabSpec): ?>
      <div class="pdp-desc-body"><?= $tabSpec ?></div>
      <?php else: ?>
      <p class="pdp-desc-empty">No specifications available for this product.</p>
      <?php endif; ?>
    </div>

    <!-- Downloads tab — Product Manuals from tbl_product_img -->
    <div class="pdp-tab-panel" data-tab="downloads">
      <?php if (!empty($manualRows)): ?>
      <div class="pdp-downloads-list">
        <?php foreach ($manualRows as $doc):
          $docPath = (string)($doc->PRODUCT_IMAGE_PATH ?? '');
          $docUrl  = $docPath !== '' ? $BASE_URL . '/' . ltrim($docPath, '/') : '#';
          $docName = (string)($doc->IMAGE_NAME ?? '');
          if (!$docName) $docName = basename($docPath) ?: 'Document';
          /* Detect file extension for icon hint */
          $ext = strtolower(pathinfo($docPath, PATHINFO_EXTENSION));
        ?>
        <div class="pdp-download-row">
          <div class="pdp-dl-icon">
            <?php if ($ext === 'pdf'): ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e53e3e" stroke-width="1.75">
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="12" y2="17"/>
            </svg>
            <?php else: ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
            </svg>
            <?php endif; ?>
          </div>
          <div class="pdp-dl-info">
            <div class="pdp-dl-name"><?= htmlspecialchars($docName) ?></div>
            <?php if ($ext): ?>
            <div class="pdp-dl-desc"><?= strtoupper($ext) ?> file</div>
            <?php endif; ?>
          </div>
          <a href="<?= htmlspecialchars($docUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline pdp-dl-btn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="pdp-desc-empty">No downloads available for this product.</p>
      <?php endif; ?>
    </div>

    <!-- Sample Code tab — tbl_product_sample_code -->
    <div class="pdp-tab-panel" data-tab="samplecode">
      <?php if (!empty($sampleRows)): ?>
      <div class="pdp-sample-list">
        <?php foreach ($sampleRows as $sc):
          $scUrl  = trim((string)($sc->EXT ?? ''));
          $scLang = trim((string)($sc->LANGUAGE_TECHNOLOGY ?? ''));
          $scIde  = trim((string)($sc->IDE_COMPILER ?? ''));
          $scType = trim((string)($sc->TYPE ?? ''));
          $scOs   = trim((string)($sc->OS ?? ''));
          $scDate = trim((string)($sc->DATE ?? ''));
          $label  = $scLang ?: ($scType ?: 'Sample Code');
        ?>
        <div class="pdp-sample-row">
          <div class="pdp-sample-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
              <polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>
            </svg>
          </div>
          <div class="pdp-sample-info">
            <div class="pdp-sample-title"><?= htmlspecialchars($label) ?></div>
            <div class="pdp-sample-meta">
              <?php if ($scIde):  ?><span class="pdp-sample-tag"><?= htmlspecialchars($scIde) ?></span><?php endif; ?>
              <?php if ($scType): ?><span class="pdp-sample-tag"><?= htmlspecialchars($scType) ?></span><?php endif; ?>
              <?php if ($scOs):   ?><span class="pdp-sample-tag"><?= htmlspecialchars($scOs) ?></span><?php endif; ?>
            </div>
          </div>
          <?php if ($scUrl): ?>
          <a href="<?= htmlspecialchars($scUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline pdp-dl-btn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Open
          </a>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="pdp-desc-empty">No sample code available for this product.</p>
      <?php endif; ?>
    </div>

  </div><!-- /pdp-tab-section -->

  <!-- ── Related Products ──────────────────────────────────────── -->
  <div class="home-section-wrap" id="relatedSection">
    <div class="sec-head">
      <div>
        <div class="sec-title">Related Products</div>
        <div class="sec-subtitle">From the same category</div>
      </div>
      <div class="carousel-nav-btns">
        <button class="car-btn car-btn-inline" onclick="carouselScroll('relatedTrack', 1)"  aria-label="Previous">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button class="car-btn car-btn-inline" onclick="carouselScroll('relatedTrack', -1)" aria-label="Next">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
    <div class="prod-carousel">
      <div class="prod-carousel-track-wrap">
        <div class="prod-carousel-track" id="relatedTrack"></div>
      </div>
    </div>
  </div>

</div><!-- /wrap -->
</main>

<script>
window.CURRENT_PRODUCT = <?= json_encode($jsProduct, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

/* ── Image gallery ─────────────────────────────────────────────── */
var _pdpImgs = <?= json_encode(array_values($images)) ?>;
var _pdpIdx  = 0;

function pdpSetThumb(el, src, index) {
  document.querySelectorAll('.pdp-thumb-img').forEach(function(t) { t.classList.remove('active'); });
  if (el) el.classList.add('active');
  var main = document.getElementById('pdpMainImg');
  if (main && src) {
    main.style.display = '';
    main.src = src;
    var ph = document.getElementById('pdpImgPlaceholder');
    if (ph) ph.style.display = 'none';
  }
  _pdpIdx = typeof index === 'number' ? index : 0;
}

function pdpStepThumb(step) {
  if (!_pdpImgs.length) return;
  _pdpIdx = (_pdpIdx + step + _pdpImgs.length) % _pdpImgs.length;
  var thumbs = document.querySelectorAll('.pdp-thumb-img');
  var thumb  = thumbs[_pdpIdx] || null;
  pdpSetThumb(thumb, _pdpImgs[_pdpIdx], _pdpIdx);
}

function pdpTab(name) {
  document.querySelectorAll('.pdp-tab-btn').forEach(function(b) {
    b.classList.toggle('active', b.dataset.tab === name);
  });
  document.querySelectorAll('.pdp-tab-panel').forEach(function(p) {
    p.classList.toggle('active', p.dataset.tab === name);
  });
}

/* ── Read More toggle ──────────────────────────────────────────── */
function pdpToggleDesc() {
  var wrap = document.getElementById('pdpShortDesc');
  var btn  = document.getElementById('pdpReadMoreBtn');
  if (!wrap || !btn) return;
  var expanded = wrap.classList.toggle('pdp2-short-desc--expanded');
  btn.textContent = expanded ? 'Read less' : 'Read more';
}

function copyLink() {
  navigator.clipboard.writeText(window.location.href).then(function() {
    var btn = document.getElementById('copyLinkBtn');
    if (btn) { btn.textContent = 'Copied!'; setTimeout(function() { btn.textContent = 'Copy Link'; }, 2000); }
  });
}
</script>

<?php require_once 'footer.php'; ?>
