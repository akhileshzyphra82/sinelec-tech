<?php
require_once '../data/store_data.php';
$currentPage = 'home';
$pageTitle   = 'Sinelec Tech — India\'s #1 Online Semiconductor Store';
require_once 'header.php';
?>

<main>

<!-- ═══════════════════════════════════════════════════════════
     HERO CAROUSEL
═══════════════════════════════════════════════════════════ -->
<div class="hero">
  <div id="heroTrack" class="hero-track">

    <!-- Slide 1 -->
    <div class="hero-slide hero-slide-1">
      <div class="wrap hero-slide-wrap">
        <div class="hero-content">
          <div class="hero-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            250,000+ Genuine Components
          </div>
          <h1 class="hero-title">India's #1 Online <span class="hl">Semiconductor</span> Store</h1>
          <p class="hero-subtitle">Genuine ICs · Microcontrollers · Sensors · Power Modules<br>Expert Chip Programming · Pan-India Fast Delivery</p>
          <div class="hero-ctas">
            <a href="products" class="btn btn-yellow btn-lg">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
              Shop Now
            </a>
            <a href="chip-programming" class="btn btn-ghost-white btn-lg">Chip Programming →</a>
          </div>
        </div>
      </div>
      <div class="hero-visual">
        <svg class="hero-chip-graphic" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="80" y="80" width="240" height="240" rx="20" stroke="white" stroke-width="4"/>
          <rect x="120" y="120" width="160" height="160" rx="10" stroke="white" stroke-width="2.5"/>
          <rect x="150" y="150" width="100" height="100" rx="5" fill="white" opacity="0.15"/>
          <line x1="80" y1="160" x2="0" y2="160" stroke="white" stroke-width="2.5"/><circle cx="0" cy="160" r="6" fill="white"/>
          <line x1="80" y1="200" x2="0" y2="200" stroke="white" stroke-width="2.5"/><circle cx="0" cy="200" r="6" fill="white"/>
          <line x1="80" y1="240" x2="0" y2="240" stroke="white" stroke-width="2.5"/><circle cx="0" cy="240" r="6" fill="white"/>
          <line x1="320" y1="160" x2="400" y2="160" stroke="white" stroke-width="2.5"/><circle cx="400" cy="160" r="6" fill="white"/>
          <line x1="320" y1="200" x2="400" y2="200" stroke="white" stroke-width="2.5"/><circle cx="400" cy="200" r="6" fill="white"/>
          <line x1="320" y1="240" x2="400" y2="240" stroke="white" stroke-width="2.5"/><circle cx="400" cy="240" r="6" fill="white"/>
          <line x1="160" y1="80" x2="160" y2="0" stroke="white" stroke-width="2.5"/><circle cx="160" cy="0" r="6" fill="white"/>
          <line x1="200" y1="80" x2="200" y2="0" stroke="white" stroke-width="2.5"/><circle cx="200" cy="0" r="6" fill="white"/>
          <line x1="240" y1="80" x2="240" y2="0" stroke="white" stroke-width="2.5"/><circle cx="240" cy="0" r="6" fill="white"/>
          <line x1="160" y1="320" x2="160" y2="400" stroke="white" stroke-width="2.5"/><circle cx="160" cy="400" r="6" fill="white"/>
          <line x1="200" y1="320" x2="200" y2="400" stroke="white" stroke-width="2.5"/><circle cx="200" cy="400" r="6" fill="white"/>
          <line x1="240" y1="320" x2="240" y2="400" stroke="white" stroke-width="2.5"/><circle cx="240" cy="400" r="6" fill="white"/>
          <text x="175" y="210" fill="white" font-size="22" font-weight="bold" font-family="monospace">MCU</text>
        </svg>
      </div>
    </div>

    <!-- Slide 2 -->
    <div class="hero-slide hero-slide-2">
      <div class="wrap hero-slide-wrap">
        <div class="hero-content">
          <div class="hero-eyebrow">Starting ₹499 / chip</div>
          <h1 class="hero-title">Custom <span class="hl">Chip Programming</span> Service</h1>
          <p class="hero-subtitle">Flash custom firmware to any MCU — Arduino, STM32, PIC, AVR, ESP32. Send us your chip or we supply it. Fast 48-hour turnaround.</p>
          <div class="hero-ctas">
            <a href="chip-programming" class="btn btn-yellow btn-lg">Get Started</a>
            <a href="request-a-quote" class="btn btn-ghost-white btn-lg">Get a Quote</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Slide 3 -->
    <div class="hero-slide hero-slide-3">
      <div class="wrap hero-slide-wrap">
        <div class="hero-content">
          <div class="hero-eyebrow">🎉 Limited Time Offer</div>
          <h1 class="hero-title">Upto <span class="hl">40% Off</span> on Bulk Orders</h1>
          <p class="hero-subtitle">Volume pricing on 100+ units. Competitive rates for your production runs. Genuine parts from authorised distributors. Nationwide delivery.</p>
          <div class="hero-ctas">
            <a href="request-a-quote" class="btn btn-yellow btn-lg">Request Quote</a>
            <a href="products" class="btn btn-ghost-white btn-lg">Browse Products</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Slide 4 -->
    <div class="hero-slide hero-slide-4">
      <div class="wrap hero-slide-wrap">
        <div class="hero-content">
          <div class="hero-eyebrow">⚡ New Arrivals</div>
          <h1 class="hero-title">Latest <span class="hl">STM32G0</span> &amp; ESP32-S3 Now In Stock</h1>
          <p class="hero-subtitle">Shop the newest microcontrollers and development modules. STM32G0B1, ESP32-S3-WROOM, nRF5340 and more.</p>
          <div class="hero-ctas">
            <a href="new-arrivals" class="btn btn-yellow btn-lg">Shop New Arrivals</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Slide 5 -->
    <div class="hero-slide hero-slide-5">
      <div class="wrap hero-slide-wrap">
        <div class="hero-content">
          <div class="hero-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
            ARM · AVR · PIC · ESP32
          </div>
          <h1 class="hero-title">250,000+ Genuine <span class="hl">IC Components</span></h1>
          <p class="hero-subtitle">From basic logic gates to advanced ARM Cortex-M7 MCUs — every part sourced from authorised distributors and verified for authenticity.</p>
          <div class="hero-ctas">
            <a href="products?cat=mcu" class="btn btn-yellow btn-lg">Browse Microcontrollers</a>
            <a href="products" class="btn btn-ghost-white btn-lg">All Categories</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Slide 6 -->
    <div class="hero-slide hero-slide-6">
      <div class="wrap hero-slide-wrap">
        <div class="hero-content">
          <div class="hero-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Expert Engineering Services
          </div>
          <h1 class="hero-title">PCB Design, Assembly &amp; <span class="hl">Chip Programming</span></h1>
          <p class="hero-subtitle">End-to-end electronics manufacturing support. Custom firmware flashing · PCB layout · Component sourcing. 48-hour turnaround guaranteed.</p>
          <div class="hero-ctas">
            <a href="chip-programming" class="btn btn-yellow btn-lg">Our Services</a>
            <a href="request-a-quote" class="btn btn-ghost-white btn-lg">Get a Quote</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Slide 7 -->
    <div class="hero-slide hero-slide-7">
      <div class="wrap hero-slide-wrap">
        <div class="hero-content">
          <div class="hero-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 010 8.49m-8.48-.01a6 6 0 010-8.49"/></svg>
            IoT · Embedded · Automation
          </div>
          <h1 class="hero-title">Top <span class="hl">Sensors</span> &amp; Communication ICs In Stock</h1>
          <p class="hero-subtitle">DHT22, MPU-6050, HC-SR04, nRF24L01+, ESP32 modules and 150+ more IoT components. Ready to ship same day.</p>
          <div class="hero-ctas">
            <a href="products?cat=sensor" class="btn btn-yellow btn-lg">Shop Sensors</a>
            <a href="products?cat=comm" class="btn btn-ghost-white btn-lg">Comm ICs</a>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /hero-track -->

  <!-- Hero Controls -->
  <button class="hero-prev" onclick="heroPrev()">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
  </button>
  <button class="hero-next" onclick="heroNext()">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
  </button>
  <div class="hero-dots">
    <button class="hero-dot on" onclick="heroGo(0)"></button>
    <button class="hero-dot" onclick="heroGo(1)"></button>
    <button class="hero-dot" onclick="heroGo(2)"></button>
    <button class="hero-dot" onclick="heroGo(3)"></button>
    <button class="hero-dot" onclick="heroGo(4)"></button>
    <button class="hero-dot" onclick="heroGo(5)"></button>
    <button class="hero-dot" onclick="heroGo(6)"></button>
  </div>
</div><!-- /hero -->


<!-- Deal Tabs -->
<div class="deals-bar">
  <div class="wrap">
    <div class="deals-bar-inner">
      <a class="deal-tab" href="#new-arrival-section">New Products</a>
      <a class="deal-tab" href="#best-seller-section">Featured Manufacture</a>
      <a class="deal-tab" href="#popular-section">Service & Tools</a>
      
    </div>
  </div>
</div>


<!-- ── Main Content ────────────────────────────────────────── -->
<div class="wrap page-wrap">

 
 <!-- New arrival -->
  <div class="home-section-wrap" id="new-arrival-section">
    <div class="sec-head">
      <div>
        <div class="sec-title">New Products</div>
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

  <!-- Featured Manufacturers -->
  <div class="home-section-wrap" id="best-seller-section">
    <div class="sec-head">
      <div>
        <div class="sec-title">Featured Manufacturers</div>
        <div class="sec-subtitle">Genuine components from the world's leading semiconductor brands.</div>
      </div>
      <a href="manufacturers" class="sec-viewall">View All Manufacturers</a>
    </div>
    <div class="mfr-logos-grid">

      <!-- Analog Devices -->
      <a href="products?mfr=Analog+Devices" class="mfr-logo-card">
        <svg viewBox="0 0 160 60" xmlns="http://www.w3.org/2000/svg" class="mfr-svg">
          <polygon points="8,48 28,12 48,48" fill="none" stroke="#003d82" stroke-width="3.5" stroke-linejoin="round"/>
          <line x1="28" y1="12" x2="28" y2="48" stroke="#003d82" stroke-width="3.5"/>
          <text x="56" y="28" font-family="Arial,sans-serif" font-size="11" font-weight="700" fill="#003d82" letter-spacing="1">ANALOG</text>
          <text x="56" y="43" font-family="Arial,sans-serif" font-size="11" font-weight="700" fill="#003d82" letter-spacing="1">DEVICES</text>
        </svg>
      </a>

      <!-- Vishay -->
      <a href="products?mfr=Vishay" class="mfr-logo-card">
        <svg viewBox="0 0 160 60" xmlns="http://www.w3.org/2000/svg" class="mfr-svg">
          <text x="80" y="30" font-family="Arial Black,sans-serif" font-size="22" font-weight="900" fill="#1a1a1a" text-anchor="middle" letter-spacing="2">VISHAY</text>
          <text x="80" y="47" font-family="Arial,sans-serif" font-size="9" fill="#666" text-anchor="middle" letter-spacing="1.5">THE DNA OF TECH</text>
        </svg>
      </a>

      <!-- Microchip -->
      <a href="products?mfr=Microchip+Technology" class="mfr-logo-card">
        <svg viewBox="0 0 160 60" xmlns="http://www.w3.org/2000/svg" class="mfr-svg">
          <rect x="8" y="16" width="28" height="28" rx="3" fill="none" stroke="#d4470a" stroke-width="2.5"/>
          <rect x="14" y="22" width="16" height="16" rx="2" fill="#d4470a"/>
          <line x1="14" y1="10" x2="14" y2="16" stroke="#d4470a" stroke-width="2"/>
          <line x1="22" y1="10" x2="22" y2="16" stroke="#d4470a" stroke-width="2"/>
          <line x1="30" y1="10" x2="30" y2="16" stroke="#d4470a" stroke-width="2"/>
          <line x1="14" y1="44" x2="14" y2="50" stroke="#d4470a" stroke-width="2"/>
          <line x1="22" y1="44" x2="22" y2="50" stroke="#d4470a" stroke-width="2"/>
          <line x1="30" y1="44" x2="30" y2="50" stroke="#d4470a" stroke-width="2"/>
          <line x1="36" y1="22" x2="42" y2="22" stroke="#d4470a" stroke-width="2"/>
          <line x1="36" y1="30" x2="42" y2="30" stroke="#d4470a" stroke-width="2"/>
          <line x1="36" y1="38" x2="42" y2="38" stroke="#d4470a" stroke-width="2"/>
          <text x="50" y="34" font-family="Arial,sans-serif" font-size="13.5" font-weight="700" fill="#d4470a">microchip</text>
        </svg>
      </a>

      <!-- Amphenol -->
      <a href="products?mfr=Amphenol" class="mfr-logo-card">
        <svg viewBox="0 0 160 60" xmlns="http://www.w3.org/2000/svg" class="mfr-svg">
          <circle cx="22" cy="30" r="14" fill="#005baa"/>
          <text x="22" y="35" font-family="Georgia,serif" font-size="16" font-weight="700" fill="#fff" text-anchor="middle">A</text>
          <text x="44" y="27" font-family="Arial,sans-serif" font-size="13" font-weight="700" fill="#005baa">Amphenol</text>
          <text x="44" y="43" font-family="Arial,sans-serif" font-size="8.5" fill="#888" letter-spacing="0.5">CONNECTORS &amp; SENSORS</text>
        </svg>
      </a>

      <!-- NXP -->
      <a href="products?mfr=NXP+Semiconductors" class="mfr-logo-card">
        <svg viewBox="0 0 160 60" xmlns="http://www.w3.org/2000/svg" class="mfr-svg">
          <rect x="8" y="12" width="36" height="36" rx="6" fill="#f60"/>
          <text x="26" y="36" font-family="Arial Black,sans-serif" font-size="18" font-weight="900" fill="#fff" text-anchor="middle">NXP</text>
          <text x="54" y="27" font-family="Arial,sans-serif" font-size="14" font-weight="700" fill="#1a1a1a">Semiconductors</text>
          <text x="54" y="43" font-family="Arial,sans-serif" font-size="8.5" fill="#888" letter-spacing="0.5">SECURE CONNECTIONS</text>
        </svg>
      </a>

      <!-- Murata -->
      <a href="products?mfr=Murata" class="mfr-logo-card">
        <svg viewBox="0 0 160 60" xmlns="http://www.w3.org/2000/svg" class="mfr-svg">
          <text x="80" y="30" font-family="Arial,sans-serif" font-size="22" font-weight="400" fill="#1a1a1a" text-anchor="middle">mu<tspan font-weight="700" fill="#e00020">R</tspan>ata</text>
          <text x="80" y="47" font-family="Arial,sans-serif" font-size="8.5" fill="#888" text-anchor="middle" letter-spacing="1">INNOVATOR IN ELECTRONICS</text>
        </svg>
      </a>

    </div>
  </div>

  <!-- Services & Tools -->
  <div class="home-section-wrap" id="popular-section">
    <div class="sec-head">
      <div>
        <div class="sec-title">Services &amp; Tools</div>
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
