<?php
$currentPage = 'home';
$pageTitle   = 'Sinelec Tech — India\'s #1 Online Semiconductor Store';
require_once 'header.php';

/* ── Active homepage sections from tbl_section ─────────── */
$_secCtrl = new WebsiteController();
$_sections = $_secCtrl->getActiveSections();
unset($_secCtrl);
$_showNew  = isset($_sections['New Products']);
$_showMfr  = isset($_sections['Featured Manufacture']);
$_showSrv  = isset($_sections['Service & Tools']);
?>

<main>

<?php
/* ── Load banners from DB ─────────────────────────────── */
$_heroCtrl   = new WebsiteController();
$_banners    = $_heroCtrl->getBanners();
$_BASE_URL   = rtrim((string)sinelec_env('PUBLIC_BASE_URL', ''), '/');
$_slideCount = count($_banners);
?>
<!-- ═══════════════════════════════════════════════════════════
     HERO CAROUSEL — dynamic from tbl_banner
═══════════════════════════════════════════════════════════ -->
<?php if ($_slideCount > 0): ?>
<div class="hero">
  <div id="heroTrack" class="hero-track">

    <?php foreach ($_banners as $_i => $_b):
        $_imgRaw = trim((string)($_b->BANNER_IMG_EXT ?? ''));
        $_color  = trim((string)($_b->COLOR          ?? ''));
        $_title  = htmlspecialchars((string)($_b->BANNER_NAME        ?? ''), ENT_QUOTES);
        $_desc   = htmlspecialchars((string)($_b->BANNER_DESCRIPTION ?? ''), ENT_QUOTES);
        $_tag    = htmlspecialchars((string)($_b->TAGS               ?? ''), ENT_QUOTES);
        $_link   = htmlspecialchars((string)($_b->HYPERLINK          ?? ''), ENT_QUOTES);
        $_b1     = htmlspecialchars((string)($_b->BTN_ONE            ?? ''), ENT_QUOTES);
        $_b1l    = htmlspecialchars((string)($_b->BTN_ONE_LINK       ?? ''), ENT_QUOTES);
        $_b2     = htmlspecialchars((string)($_b->BTN_TWO            ?? ''), ENT_QUOTES);
        $_b2l    = htmlspecialchars((string)($_b->BTN_TWO_LINK       ?? ''), ENT_QUOTES);

        /* Build background style */
        if ($_imgRaw !== '') {
            $_imgUrl   = $_BASE_URL . '/' . ltrim($_imgRaw, '/');
            $_bgStyle  = "background: linear-gradient(to right, rgba(8,20,45,.88) 40%, rgba(8,20,45,.50) 70%, rgba(8,20,45,.20) 100%), url('" . $_imgUrl . "') center/cover no-repeat;";
        } elseif ($_color !== '') {
            $_bgStyle  = "background: " . $_color . ";";
        } else {
            /* fallback gradient */
            $_gradients = [
                "linear-gradient(120deg, #0d1b3e 0%, #1a3a7c 50%, #0a5a9c 100%)",
                "linear-gradient(120deg, #1a0d3e 0%, #2d1b7c 50%, #4a0a9c 100%)",
                "linear-gradient(120deg, #0d2e1b 0%, #1b5a3a 50%, #0a7c3a 100%)",
                "linear-gradient(120deg, #3e1a0d 0%, #7c3a1b 50%, #9c5a0a 100%)",
            ];
            $_bgStyle   = "background: " . $_gradients[$_i % count($_gradients)] . ";";
        }
    ?>
    <!-- Slide <?= $_i + 1 ?> -->
    <div class="hero-slide" style="<?= $_bgStyle ?><?= $_link ? ' cursor:pointer;' : '' ?>"<?= $_link ? ' onclick="location.href=\'' . $_link . '\'"' : '' ?>>
      <div class="wrap hero-slide-wrap">
        <div class="hero-content">
          <?php if ($_tag !== ''): ?>
          <div class="hero-eyebrow"><?= $_tag ?></div>
          <?php endif; ?>
          <?php if ($_title !== ''): ?>
          <h1 class="hero-title"><?= $_title ?></h1>
          <?php endif; ?>
          <?php if ($_desc !== ''): ?>
          <p class="hero-subtitle"><?= $_desc ?></p>
          <?php endif; ?>
          <?php if ($_b1 !== '' && $_b1l !== '' || $_b2 !== '' && $_b2l !== ''): ?>
          <div class="hero-ctas">
            <?php if ($_b1 !== '' && $_b1l !== ''): ?>
            <a href="<?= $_b1l ?>" class="btn btn-yellow btn-lg"><?= $_b1 ?></a>
            <?php endif; ?>
            <?php if ($_b2 !== '' && $_b2l !== ''): ?>
            <a href="<?= $_b2l ?>" class="btn btn-ghost-white btn-lg"><?= $_b2 ?></a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

  </div><!-- /hero-track -->

  <?php if ($_slideCount > 1): ?>
  <!-- Hero Controls -->
  <button class="hero-prev" onclick="heroPrev()">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
  </button>
  <button class="hero-next" onclick="heroNext()">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
  </button>
  <div class="hero-dots">
    <?php for ($_d = 0; $_d < $_slideCount; $_d++): ?>
    <button class="hero-dot <?= $_d === 0 ? 'on' : '' ?>" onclick="heroGo(<?= $_d ?>)"></button>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

</div><!-- /hero -->
<?php endif; unset($_heroCtrl, $_banners, $_BASE_URL, $_slideCount, $_b, $_i, $_imgRaw, $_color, $_title, $_desc, $_tag, $_link, $_b1, $_b1l, $_b2, $_b2l, $_bgStyle, $_imgUrl, $_gradients, $_d); ?>


<?php if ($_showNew || $_showMfr || $_showSrv): ?>
<!-- Deal Tabs -->
<div class="deals-bar">
  <div class="wrap">
    <div class="deals-bar-inner">
      <?php if ($_showNew): ?><a class="deal-tab" href="#new-arrival-section"><?= htmlspecialchars($_sections['New Products']['name']) ?></a><?php endif; ?>
      <?php if ($_showMfr): ?><a class="deal-tab" href="#best-seller-section"><?= htmlspecialchars($_sections['Featured Manufacture']['name']) ?></a><?php endif; ?>
      <?php if ($_showSrv): ?><a class="deal-tab" href="#popular-section"><?= htmlspecialchars($_sections['Service & Tools']['name']) ?></a><?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>


<!-- ── Main Content ────────────────────────────────────────── -->
<div class="wrap page-wrap">

 
<?php if ($_showNew): ?>
  <!-- New arrival -->
  <div class="home-section-wrap" id="new-arrival-section">
    <div class="sec-head">
      <div>
        <div class="sec-title"><?= htmlspecialchars($_sections['New Products']['name']) ?></div>
        <div class="sec-subtitle">Explore the latest additions across semiconductors, modules, and production-ready components.</div>
      </div>
      <div class="carousel-nav-btns">
        <button class="car-btn car-btn-inline" onclick="carouselScroll('newArrivalsTrack', 1)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button class="car-btn car-btn-inline" onclick="carouselScroll('newArrivalsTrack', -1)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
    <div class="prod-carousel">
      <div class="prod-carousel-track-wrap">
        <div class="prod-carousel-track" id="newArrivalsTrack"></div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($_showMfr):
  $_featMfrCtrl = new WebsiteController();
  $_featMfrs    = $_featMfrCtrl->getFeaturedManufacturers();
  unset($_featMfrCtrl);
?>
  <!-- Featured Manufacturers -->
  <div class="home-section-wrap" id="best-seller-section">
    <div class="sec-head">
      <div>
        <div class="sec-title"><?= htmlspecialchars($_sections['Featured Manufacture']['name']) ?></div>
        <div class="sec-subtitle">Genuine components from the world's leading semiconductor brands.</div>
      </div>
      <a href="manufacturers" class="sec-viewall">View All Manufacturers</a>
    </div>

    <?php if (!empty($_featMfrs)): ?>
    <div class="mfr-logos-grid">
      <?php foreach ($_featMfrs as $_mfr):
        $_mfrName    = (string)($_mfr->NAME ?? '');
        $_mfrLogoRaw = trim((string)($_mfr->LOGO ?? ''));
        $_mfrLogoUrl = $_mfrLogoRaw !== '' ? rtrim((string)sinelec_env('PUBLIC_BASE_URL',''),'/') . '/' . ltrim($_mfrLogoRaw,'/') : '';
        $_mfrInitial = strtoupper(substr(trim($_mfrName), 0, 1)) ?: '?';
        $_mfrCatIds  = trim((string)($_mfr->PRODUCT_CATEGORY_IDS ?? ''));
        $_mfrCatParam = $_mfrCatIds !== '' ? $_mfrCatIds : 'none';
        $_mfrLink    = 'products?mfr=' . urlencode($_mfrName) . '&cat_ids=' . urlencode($_mfrCatParam);
      ?>
      <a href="<?= htmlspecialchars($_mfrLink) ?>" class="mfr-logo-card">
        <?php if ($_mfrLogoUrl !== ''): ?>
          <img src="<?= htmlspecialchars($_mfrLogoUrl) ?>" alt="<?= htmlspecialchars($_mfrName) ?>" class="mfr-home-img">
        <?php else: ?>
          <span class="mfr-home-name"><?= htmlspecialchars($_mfrName) ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
<?php unset($_featMfrs, $_mfr, $_mfrName, $_mfrLogoRaw, $_mfrLogoUrl, $_mfrInitial, $_mfrCatIds, $_mfrCatParam, $_mfrLink); endif; ?>

<?php if ($_showSrv): ?>
  <!-- Services & Tools -->
  <div class="home-section-wrap" id="popular-section">
    <div class="sec-head">
      <div>
        <div class="sec-title"><?= htmlspecialchars($_sections['Service & Tools']['name']) ?></div>
        <div class="sec-subtitle">End-to-end engineering support — from component sourcing to production-ready firmware.</div>
      </div>
      <a href="chip-programming" class="sec-viewall">View All Services &amp; Tools</a>
    </div>
    <div class="srv-tools-grid">

      <a href="chip-programming" class="srv-tool-card">
        <div class="srv-tool-img-wrap">
          <img src="https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&h=400&fit=crop&q=80" alt="Chip Programming" loading="lazy">
        </div>
        <h3 class="srv-tool-title">Chip Programming</h3>
        <p class="srv-tool-desc">Flash your MCU with custom firmware — Arduino, STM32, PIC, AVR, ESP32 and more.</p>
      </a>

      <a href="request-a-quote" class="srv-tool-card">
        <div class="srv-tool-img-wrap">
          <img src="https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=400&h=400&fit=crop&q=80" alt="Request Quote" loading="lazy">
        </div>
        <h3 class="srv-tool-title">Request a Quote</h3>
        <p class="srv-tool-desc">Need a bulk price or custom order? Tell us what you're after and we'll get back to you fast.</p>
      </a>

      <a href="products" class="srv-tool-card">
        <div class="srv-tool-img-wrap">
          <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&h=400&fit=crop&q=80" alt="Price and Availability" loading="lazy">
        </div>
        <h3 class="srv-tool-title">Price &amp; Availability</h3>
        <p class="srv-tool-desc">Quickly check live stock levels and competitive pricing across 250,000+ components.</p>
      </a>

      <a href="about" class="srv-tool-card">
        <div class="srv-tool-img-wrap">
          <img src="https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?w=400&h=400&fit=crop&q=80" alt="PCB Design" loading="lazy">
        </div>
        <h3 class="srv-tool-title">PCB Design &amp; Assembly</h3>
        <p class="srv-tool-desc">Professional schematic capture, PCB layout and full SMT assembly from prototype to production.</p>
      </a>

      <a href="about" class="srv-tool-card">
        <div class="srv-tool-img-wrap">
          <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=400&h=400&fit=crop&q=80" alt="Express Delivery" loading="lazy">
        </div>
        <h3 class="srv-tool-title">Express Delivery</h3>
        <p class="srv-tool-desc">Same-day dispatch on orders placed before 2 PM. Nationwide coverage with tracking.</p>
      </a>

    </div>
  </div>
<?php endif; ?>

  <!-- Trust Badges -->
  <!-- <div class="trust-badges">
    <div class="trust-badges-grid">
     
      <div>
        <div class="trust-badge-icon">✅</div>
        <div class="trust-badge-title">100% Genuine</div>
        <div class="trust-badge-sub">Authorised distributor</div>
      </div>
      <div>
        <div class="trust-badge-icon">↩️</div>
        <div class="trust-badge-title">Easy Returns</div>
        <div class="trust-badge-sub">7-day return policy</div>
      </div>
      <div class="trust-badge-payments">
        <img
          src="../assets/payment-methods.svg"
          alt="Accepted payments: PayPal, Bank Transfer, Visa, Mastercard, American Express"
          class="trust-badge-payment-img"
        >
        <div class="trust-badge-title">Accepted Payments</div>
        <div class="trust-badge-sub">PayPal · Bank Transfer · Visa · Mastercard · American Express</div>
      </div>
      <div>
        <div class="trust-badge-icon">📞</div>
        <div class="trust-badge-title">Expert Support</div>
        <div class="trust-badge-sub">Mon–Sat 9AM–6PM</div>
      </div>
      <div>
        <div class="trust-badge-icon">⚡</div>
        <div class="trust-badge-title">Same-Day Dispatch</div>
        <div class="trust-badge-sub">Orders before 2 PM</div>
      </div>
    </div>
  </div> -->

 
  <!-- Newsletter -->
  <div class="newsletter-section">
    <div>
      <div class="newsletter-label">Newsletter</div>
      <div class="newsletter-title">Get Deals, New Products &amp; Tech Tips</div>
      <div class="newsletter-sub">Subscribe for exclusive offers, datasheets and application notes.</div>
    </div>
    <form id="nlForm" class="newsletter-form" method="POST" action="service?action=Subscribe">
      <input type="email" name="email" required placeholder="Enter your email address" class="newsletter-input" autocomplete="email">
      <button type="submit" class="btn btn-blue">Subscribe</button>
    </form>
  </div>

</div><!-- /wrap.page-wrap -->

</main>

<?php require_once 'footer.php'; ?>
