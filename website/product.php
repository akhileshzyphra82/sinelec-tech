<?php
require_once '../data/store_data.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$product = null;
foreach ($storeData['products'] as $p) {
    if ((int)$p['id'] === $productId) { $product = $p; break; }
}

if (!$product) {
    header('HTTP/1.1 404 Not Found');
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

$currentPage = 'product';
$pageTitle   = htmlspecialchars($product['name']) . ' — Sinelec Tech';

$basePrice       = $product['priceBreaks'][0]['price'] ?? $product['price'] ?? 0;
$originalPrice   = $product['originalPrice'] ?? $product['oldPrice'] ?? null;
$inStock         = ($product['stock'] ?? 0) > 0;
$manufacturer    = $product['manufacturer'] ?? $product['brand'] ?? '';
$description     = $product['description'] ?? $product['desc'] ?? '';
$reviews         = $product['reviews'] ?? $product['reviewCount'] ?? 0;
$features        = $product['features'] ?? [];
$priceBreaks     = $product['priceBreaks'] ?? [];
$specs           = $product['specs'] ?? [];
$savePct         = ($originalPrice && $originalPrice > $basePrice)
                   ? round((($originalPrice - $basePrice) / $originalPrice) * 100)
                   : 0;

require_once 'header.php';
?>

<main>
<div class="wrap page-wrap pdp-wrap">

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="index">Home</a>
    <span class="bc-sep">›</span>
    <a href="products">Products</a>
    <?php if (!empty($product['category'])): ?>
    <span class="bc-sep">›</span>
    <a href="products?cat=<?= urlencode($product['category']) ?>">
      <?php
      foreach ($storeData['categories'] as $c) {
          if ($c['id'] === $product['category']) { echo htmlspecialchars($c['name']); break; }
      }
      ?>
    </a>
    <?php endif; ?>
    <span class="bc-sep">›</span>
    <span><?= htmlspecialchars($product['name']) ?></span>
  </nav>

  <!-- ── Top 2-col grid ───────────────────────────────────────── -->
  <div class="pdp-top">

    <!-- Left: Image panel -->
    <div class="pdp-img-card">
      <?php if (!empty($product['image'])): ?>
      <div class="pdp-main-image-wrap">
        <img src="<?= htmlspecialchars($product['image']) ?>"
             alt="<?= htmlspecialchars($product['name']) ?>"
             class="pdp-main-image pdp-view-0"
             id="pdpMainImg"
             data-view-index="0">
        <button type="button" class="pdp-image-nav pdp-image-nav--prev" onclick="pdpStepThumb(-1)" aria-label="Previous image">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button type="button" class="pdp-image-nav pdp-image-nav--next" onclick="pdpStepThumb(1)" aria-label="Next image">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
      <?php else: ?>
      <div class="pdp-main-image pdp-img-placeholder">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
          <rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/>
        </svg>
      </div>
      <?php endif; ?>

      <!-- Thumbnail strip -->
      <div class="pdp-thumbs-strip">
        <?php
        $thumbSrc = !empty($product['image']) ? htmlspecialchars($product['image']) : '';
        for ($ti = 0; $ti < 4; $ti++):
        ?>
        <img src="<?= $thumbSrc ?>"
             alt="<?= htmlspecialchars($product['name']) ?> view <?= $ti + 1 ?>"
             class="pdp-thumb-img<?= $ti === 0 ? ' active' : '' ?>"
             data-view-index="<?= $ti ?>"
             onclick="pdpSetThumb(this, '<?= $thumbSrc ?>', <?= $ti ?>)">
        <?php endfor; ?>
      </div>

      <?php if (!empty($product['datasheet'])): ?>
      <a href="<?= htmlspecialchars($product['datasheet']) ?>" target="_blank" rel="noopener" class="pdp-datasheet-btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Download Datasheet
      </a>
      <?php endif; ?>
    </div>

    <!-- Right: Purchase card -->
    <div class="pdp-info-card">

      <!-- Badge + SKU -->
      <div class="pdp-badge-sku-row">
        <?php if (!empty($product['badge'])): ?>
        <span class="pbadge pbadge-<?= htmlspecialchars($product['badge']) ?>"><?= strtoupper(htmlspecialchars($product['badge'])) ?></span>
        <?php endif; ?>
        <?php if (!empty($product['sku'])): ?>
        <span class="pdp-sku-tag">SKU: <?= htmlspecialchars($product['sku']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Product name -->
      <h1 class="pdp-name"><?= htmlspecialchars($product['name']) ?></h1>

      <!-- Manufacturer row -->
      <div class="pdp-mfr-row">
        <?php if ($manufacturer): ?>
        <span>by <strong><?= htmlspecialchars($manufacturer) ?></strong></span>
        <?php endif; ?>
        <?php if (!empty($product['sku'])): ?>
        <span class="pdp-mfr-sep">|</span>
        <span>Part: <strong><?= htmlspecialchars($product['sku']) ?></strong></span>
        <?php endif; ?>
      </div>

      <!-- Rating bar -->
      <div class="pdp-rating-bar">
        <div class="star-row" id="pdpStars"></div>
        <span class="pdp-review-link">
          <?= (int)$reviews ?> review<?= (int)$reviews !== 1 ? 's' : '' ?>
        </span>
      </div>

      <!-- Price block -->
      <div class="pdp-price-block">
        <div class="pdp-price-row">
          <span class="pdp-price-current">₹<?= number_format($basePrice, 2) ?></span>
          <?php if ($originalPrice && $originalPrice > $basePrice): ?>
          <span class="pdp-orig-price">₹<?= number_format($originalPrice, 2) ?></span>
          <span class="pdp-save-pct">Save <?= $savePct ?>%</span>
          <?php endif; ?>
        </div>
        <div class="pdp-gst-note">+ GST applicable</div>
      </div>

      <!-- Volume pricing table -->
      <?php if (count($priceBreaks) > 1): ?>
      <div class="pdp-vol-wrap">
        <div class="pdp-vol-label">Volume Pricing</div>
        <table class="pdp-vol-table">
          <thead>
            <tr><th>Qty</th><th>Unit Price</th><th>You Save</th></tr>
          </thead>
          <tbody>
          <?php
          $bestIdx = count($priceBreaks) - 1;
          foreach ($priceBreaks as $idx => $pb):
            $isBest = ($idx === $bestIdx);
            $savePb = ($pb['price'] < $basePrice)
                      ? round((($basePrice - $pb['price']) / $basePrice) * 100)
                      : 0;
          ?>
          <tr<?= $isBest ? ' class="pdp-vol-best"' : '' ?>>
            <td><?= (int)$pb['qty'] ?>+</td>
            <td>₹<?= number_format($pb['price'], 2) ?></td>
            <td><?= $savePb > 0 ? '<span class="pdp-pb-save">-' . $savePb . '%</span>' : '—' ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <!-- Stock status -->
      <div class="pdp-stock-row">
        <?php if ($inStock): ?>
        <span class="pdp-stock-chip pdp-stock-in">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          In Stock — <?= number_format((int)$product['stock']) ?> units
        </span>
        <?php else: ?>
        <span class="pdp-stock-chip pdp-stock-out">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
          Out of Stock
        </span>
        <?php endif; ?>
      </div>

      <!-- Quick spec chips -->
      <?php
      $chips = [];
      if (!empty($product['package']))   $chips[] = $product['package'];
      if (!empty($product['voltage']))   $chips[] = $product['voltage'];
      if (!empty($product['frequency'])) $chips[] = $product['frequency'];
      ?>
      <?php if ($chips): ?>
      <div class="pdp-spec-chips">
        <?php foreach ($chips as $chip): ?>
        <span class="pdp-spec-chip"><?= htmlspecialchars($chip) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Key features -->
      <?php if (!empty($features)): ?>
      <ul class="pdp-features-list">
        <?php foreach ($features as $feat): ?>
        <li><?= htmlspecialchars($feat) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>

      <!-- Actions: qty + buttons -->
      <div class="pdp-actions">
        <div class="pdp-qty">
          <button class="pdp-qty-btn" onclick="pdpQtyChange(-1)" aria-label="Decrease quantity">−</button>
          <input type="number" class="pdp-qty-inp" id="pdpQty" value="1" min="1" max="<?= (int)($product['stock'] ?? 999) ?>" aria-label="Quantity">
          <button class="pdp-qty-btn" onclick="pdpQtyChange(1)" aria-label="Increase quantity">+</button>
        </div>
        <button class="btn btn-yellow pdp-cart-btn" onclick="pdpAddToCart()" <?= $inStock ? '' : 'disabled' ?>>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
          </svg>
          <?= $inStock ? 'Add to Cart' : 'Out of Stock' ?>
        </button>
        <button class="btn pdp-buynow-btn" onclick="pdpAddToCart()" <?= $inStock ? '' : 'disabled' ?>>Buy Now</button>
        <button class="pdp-wish-btn" id="pdpWishBtn" onclick="pdpToggleWish()" title="Add to Wishlist" aria-label="Add to wishlist">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
          </svg>
        </button>
      </div>

      <!-- Share row -->
      <div class="pdp-share-row">
        <span class="pdp-share-label">Share:</span>
        <button class="pdp-share-btn" id="copyLinkBtn" onclick="copyLink()">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
            <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
          </svg>
          Copy Link
        </button>
      </div>

    </div><!-- /pdp-info-card -->
  </div><!-- /pdp-top -->

  <!-- ── Tab section ──────────────────────────────────────────── -->
  <div class="pdp-tab-section" id="tab-reviews">
    <div class="pdp-tab-bar" role="tablist">
      <button class="pdp-tab-btn active" data-tab="description" onclick="pdpTab('description')" role="tab">Description</button>
      <button class="pdp-tab-btn" data-tab="specifications" onclick="pdpTab('specifications')" role="tab">Specifications</button>
      <button class="pdp-tab-btn" data-tab="downloads" onclick="pdpTab('downloads')" role="tab">Downloads</button>
      <button class="pdp-tab-btn" data-tab="samplecode" onclick="pdpTab('samplecode')" role="tab">Sample Code</button>
    </div>

    <!-- Description tab -->
    <div class="pdp-tab-panel active" data-tab="description">
      <?php if ($description): ?>
      <div class="pdp-desc-body"><?= nl2br(htmlspecialchars($description)) ?></div>
      <?php else: ?>
      <p class="pdp-desc-empty">No description available for this product.</p>
      <?php endif; ?>
    </div>

    <!-- Specifications tab -->
    <div class="pdp-tab-panel" data-tab="specifications">
      <table class="pdp-specs-tbl">
        <tbody>
        <?php if (!empty($specs)): ?>
          <?php foreach ($specs as $k => $v): ?>
          <tr>
            <td class="pdp-spec-key"><?= htmlspecialchars($k) ?></td>
            <td class="pdp-spec-val"><?= htmlspecialchars($v) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($product['package'])): ?>
        <tr><td class="pdp-spec-key">Package</td><td class="pdp-spec-val"><?= htmlspecialchars($product['package']) ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($product['voltage'])): ?>
        <tr><td class="pdp-spec-key">Voltage</td><td class="pdp-spec-val"><?= htmlspecialchars($product['voltage']) ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($product['frequency'])): ?>
        <tr><td class="pdp-spec-key">Frequency</td><td class="pdp-spec-val"><?= htmlspecialchars($product['frequency']) ?></td></tr>
        <?php endif; ?>
        <?php if ($manufacturer): ?>
        <tr><td class="pdp-spec-key">Manufacturer</td><td class="pdp-spec-val"><?= htmlspecialchars($manufacturer) ?></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Downloads tab -->
    <div class="pdp-tab-panel" data-tab="downloads">
      <div class="pdp-downloads-list">
        <?php if (!empty($product['datasheet'])): ?>
        <div class="pdp-download-row">
          <div class="pdp-dl-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
              <polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/>
              <polyline points="9 15 12 18 15 15"/>
            </svg>
          </div>
          <div class="pdp-dl-info">
            <div class="pdp-dl-name">Datasheet PDF</div>
            <div class="pdp-dl-desc">Official technical datasheet for <?= htmlspecialchars($product['name']) ?></div>
          </div>
          <a href="<?= htmlspecialchars($product['datasheet']) ?>" target="_blank" rel="noopener" class="btn btn-blue pdp-dl-btn">Download</a>
        </div>
        <?php endif; ?>
        <?php
        $extraDocs = [
          ['Application Notes',  'Usage examples and application guidance'],
          ['Reference Manual',   'Complete reference documentation'],
          ['Errata Sheet',       'Known issues and corrections'],
        ];
        foreach ($extraDocs as $doc):
        ?>
        <div class="pdp-download-row">
          <div class="pdp-dl-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
            </svg>
          </div>
          <div class="pdp-dl-info">
            <div class="pdp-dl-name"><?= $doc[0] ?></div>
            <div class="pdp-dl-desc"><?= $doc[1] ?></div>
          </div>
          <a href="#" class="btn btn-outline pdp-dl-btn">Download</a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Sample Code tab -->
    <div class="pdp-tab-panel" data-tab="samplecode">
      <div class="pdp-code-header">
        <span class="pdp-code-lang">Arduino / C</span>
        <button class="pdp-copy-btn" id="copyCodeBtn" onclick="copyCode()">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
          </svg>
          Copy
        </button>
      </div>
      <pre class="pdp-code-block"><code>/*
 * <?= htmlspecialchars($product['name']) ?> — Basic Arduino Example
 * Manufacturer: <?= htmlspecialchars($manufacturer ?: 'N/A') ?>

 * SKU: <?= htmlspecialchars($product['sku'] ?? 'N/A') ?>

 */

#include &lt;Arduino.h&gt;

// Pin definitions
const int DEVICE_PIN = 2;

void setup() {
  Serial.begin(9600);
  pinMode(DEVICE_PIN, OUTPUT);

  Serial.println("<?= htmlspecialchars($product['name']) ?> initialized");
}

void loop() {
  // Main control loop
  digitalWrite(DEVICE_PIN, HIGH);
  delay(1000);
  digitalWrite(DEVICE_PIN, LOW);
  delay(1000);

  Serial.println("Cycle complete");
}</code></pre>
    </div>

    <!-- Reviews tab -->
    <div class="pdp-tab-panel" data-tab="reviews">
      <div class="pdp-review-summary">
        <div class="pdp-review-score">
          <div class="pdp-review-big-num"><?= number_format($product['rating'] ?? 4.5, 1) ?></div>
          <div class="pdp-review-big-stars">
            <?php
            $rating = $product['rating'] ?? 4.5;
            for ($s = 1; $s <= 5; $s++) {
                echo $s <= round($rating) ? '★' : '☆';
            }
            ?>
          </div>
          <div class="pdp-review-total"><?= (int)$reviews ?> reviews</div>
        </div>
        <div class="pdp-review-bars">
          <?php
          $bars = [5 => 60, 4 => 25, 3 => 10, 2 => 3, 1 => 2];
          foreach ($bars as $star => $pct):
          ?>
          <div class="pdp-review-bar-row">
            <span class="pdp-review-bar-label"><?= $star ?>★</span>
            <div class="pdp-review-bar-track">
              <div class="pdp-review-bar-fill" style="width:<?= $pct ?>%"></div>
            </div>
            <span class="pdp-review-bar-pct"><?= $pct ?>%</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="pdp-review-cards">
        <?php
        $placeholderReviews = [
          ['name' => 'Rahul S.', 'date' => 'March 2025',   'stars' => 5, 'text' => 'Excellent component. Works perfectly with our design. Fast shipping and well-packaged. Highly recommend for production use.'],
          ['name' => 'Priya M.', 'date' => 'February 2025','stars' => 4, 'text' => 'Good quality part, matches the datasheet specs. Delivery was prompt. Dropped one star only because the packaging could be improved.'],
          ['name' => 'Arjun K.', 'date' => 'January 2025', 'stars' => 5, 'text' => 'Used this in an industrial control project. Performs reliably under continuous operation. Will order again in larger quantities.'],
        ];
        foreach ($placeholderReviews as $rev):
        ?>
        <div class="pdp-review-card">
          <div class="pdp-rv-header">
            <div class="pdp-rv-avatar"><?= $rev['name'][0] ?></div>
            <div class="pdp-rv-meta">
              <div class="pdp-rv-name"><?= $rev['name'] ?></div>
              <div class="pdp-rv-date"><?= $rev['date'] ?></div>
            </div>
            <div class="pdp-rv-stars">
              <?php for ($s = 1; $s <= 5; $s++) echo $s <= $rev['stars'] ? '★' : '☆'; ?>
            </div>
          </div>
          <p class="pdp-rv-text"><?= $rev['text'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>

      <button class="btn btn-outline pdp-write-review-btn">Write a Review</button>
    </div>

  </div><!-- /pdp-tab-section -->

  <!-- ── Related Products ──────────────────────────────────────── -->
  <div class="home-section-wrap">
    <div class="sec-head">
      <div>
        <div class="sec-title">Related Products</div>
        <div class="sec-subtitle">Customers also viewed</div>
      </div>
      <div class="carousel-nav-btns">
        <button class="car-btn car-btn-inline" onclick="carouselScroll('relatedTrack', 1)" aria-label="Previous related products">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button class="car-btn car-btn-inline" onclick="carouselScroll('relatedTrack', -1)" aria-label="Next related products">
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
window.CURRENT_PRODUCT = <?= json_encode($product, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

function pdpTab(name) {
  document.querySelectorAll('.pdp-tab-btn').forEach(function(b) {
    b.classList.toggle('active', b.dataset.tab === name);
  });
  document.querySelectorAll('.pdp-tab-panel').forEach(function(p) {
    p.classList.toggle('active', p.dataset.tab === name);
  });
}

function pdpSetThumb(el, src, index) {
  document.querySelectorAll('.pdp-thumb-img').forEach(function(t) { t.classList.remove('active'); });
  el.classList.add('active');
  var main = document.getElementById('pdpMainImg');
  if (main) {
    if (src) main.src = src;
    var nextIndex = typeof index === 'number' ? index : Number(el.dataset.viewIndex || 0);
    main.dataset.viewIndex = String(nextIndex);
    main.classList.remove('pdp-view-0', 'pdp-view-1', 'pdp-view-2', 'pdp-view-3');
    main.classList.add('pdp-view-' + nextIndex);
  }
}

function pdpStepThumb(step) {
  var thumbs = Array.from(document.querySelectorAll('.pdp-thumb-img'));
  if (!thumbs.length) return;
  var current = thumbs.findIndex(function(t) { return t.classList.contains('active'); });
  if (current < 0) current = 0;
  var next = (current + step + thumbs.length) % thumbs.length;
  var thumb = thumbs[next];
  pdpSetThumb(thumb, thumb.getAttribute('src') || '', Number(thumb.dataset.viewIndex || next));
}

function copyCode() {
  var code = document.querySelector('.pdp-code-block code');
  if (code) navigator.clipboard.writeText(code.textContent).then(function() {
    var btn = document.getElementById('copyCodeBtn');
    if (btn) { btn.textContent = 'Copied!'; setTimeout(function() { btn.textContent = 'Copy'; }, 2000); }
  });
}

function copyLink() {
  navigator.clipboard.writeText(window.location.href).then(function() {
    var btn = document.getElementById('copyLinkBtn');
    if (btn) { btn.textContent = 'Copied!'; setTimeout(function() { btn.textContent = 'Copy Link'; }, 2000); }
  });
}
</script>

<?php require_once 'footer.php'; ?>
