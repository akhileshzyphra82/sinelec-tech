<?php
require_once __DIR__ . '/account-helpers.php';
$flashToast = sinelec_consume_flash();
$msg = (string)($flashToast['message'] ?? '');
$toastType = (string)($flashToast['type'] ?? 'ok');

$currentPage = $currentPage ?? 'home';
$pageTitle   ='Sinelec Technologies : Electronic Module and Component Distributor & Expert chip programming services';
$signedInUser = sinelec_get_signed_in_user();
$isSignedIn = sinelec_is_signed_in();
$userDisplayName = trim((string)($signedInUser['NAME'] ?? ''));
$userFirstName = sinelec_account_first_name($signedInUser);
$turnstileSiteKey = sinelec_env('SITE_KEY', '');

function navClass(string $page, string $current): string {
    return $page === $current ? 'nav-link active' : 'nav-link';
}

$productMegaMenu = [
    [
        'id' => 'mcu',
        'name' => 'Microcontrollers',
        'subcategories' => ['ARM Cortex', 'AVR', 'PIC', 'ESP32'],
    ],
    [
        'id' => 'logic',
        'name' => 'Logic ICs',
        'subcategories' => ['Shift Registers', 'Gates', 'Flip-Flops', 'Counters'],
    ],
    [
        'id' => 'opamp',
        'name' => 'Op-Amps & Comparators',
        'subcategories' => ['General Purpose', 'Dual Op-Amp', 'Comparators', 'Low Noise'],
    ],
    [
        'id' => 'power',
        'name' => 'Power Management',
        'subcategories' => ['Linear Regulators', 'LDO Regulators', 'Buck Converters', 'Converters'],
    ],
    [
        'id' => 'transistor',
        'name' => 'Transistors & MOSFETs',
        'subcategories' => ['NPN Transistors', 'Power MOSFETs', 'IGBT', 'Switching'],
    ],
    [
        'id' => 'sensor',
        'name' => 'Sensors & Modules',
        'subcategories' => ['Temperature & Humidity', 'IMU', 'Ultrasonic', 'Motion'],
    ],
    [
        'id' => 'comm',
        'name' => 'Communication ICs',
        'subcategories' => ['RS-232', 'RF 2.4GHz', 'UART/SPI/I2C', 'Wireless Modules'],
    ],
    [
        'id' => 'memory',
        'name' => 'Memory',
        'subcategories' => ['EEPROM', 'Flash', 'SRAM', 'Non-Volatile'],
    ],
    [
        'id' => 'passive',
        'name' => 'Passive Components',
        'subcategories' => ['Resistors', 'Capacitors', 'Inductors', 'Through-Hole Packs'],
    ],
    [
        'id' => 'display',
        'name' => 'Display & LED',
        'subcategories' => ['OLED', 'Character LCD', 'Display Modules', 'LED Drivers'],
    ],
];
?>
<!DOCTYPE html> 
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Sinelec Tech — largest online semiconductor &amp; electronic components store. Genuine ICs, MCUs, sensors, power ICs. Expert chip programming services.">
<meta name="theme-color" content="#131A2E">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../assets/css/chatbot.css">
<?php if ($turnstileSiteKey !== ''): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<script>
window.STORE_DATA   = <?= json_encode($storeData ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
window.CURRENT_PAGE = '<?= htmlspecialchars($currentPage) ?>';
window.SINELEC_AUTH = {
  isSignedIn: <?= $isSignedIn ? 'true' : 'false' ?>
};
window.FLASH_TOAST  = {
  message: <?= json_encode($msg ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
  type: <?= json_encode($toastType ?? 'ok', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>
};
</script>
</head>
<body data-page="<?= htmlspecialchars($currentPage) ?>">

<!-- ══════════ HEADER ════════════════════════════════════════ -->
<header class="site-header">
  <div class="wrap">
    <div class="header-main">

      <!-- Mobile Hamburger -->
      <button class="h-hamburger" onclick="openMobMenu()">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        <span>Menu</span>
      </button>

      <!-- Logo -->
      <a href="index" class="logo" aria-label="Sinelec Tech — Home">
        <img src="../assets/logo.png" alt="Sinelec Tech" class="logo-img">
      </a>

      <!-- Search -->
      <div class="header-search">
        <form action="products" method="GET" autocomplete="off" class="search-form-contents">
          <select class="search-cat-btn" name="cat" id="searchCat">
            <option value="">All</option>
            <option value="mcu">Microcontrollers</option>
            <option value="logic">Logic ICs</option>
            <option value="opamp">Op-Amps</option>
            <option value="power">Power ICs</option>
            <option value="transistor">Transistors</option>
            <option value="sensor">Sensors</option>
            <option value="comm">Comm ICs</option>
            <option value="memory">Memory</option>
            <option value="display">Display &amp; LED</option>
            <option value="passive">Passives</option>
          </select>
          <input class="search-field" id="searchField" type="text" name="q"
                 placeholder="Search part number, description or manufacturer…"
                 oninput="onSearchInput(event)"
                 value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
          <button class="search-go" type="submit" aria-label="Search">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </button>
        </form>
        <div class="search-drop" id="searchDrop"></div>
      </div>

      <!-- Delivery Location -->
      <div class="header-delivery" id="headerDeliveryBtn" title="Change delivery location" role="button" tabindex="0" aria-haspopup="dialog" aria-controls="deliveryModal">
        <span class="h-label">Deliver to</span>
        <strong class="h-value">
          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <span id="deliveryLocationText" class="delivery-loc-text">Delhi 110001</span>
        </strong>
      </div>

      <!-- Account -->
      <div class="header-account-wrap">
        <div class="h-act" id="headerAccountBtn" title="Account" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" <?= $isSignedIn ? 'aria-controls="accountMenu"' : 'aria-controls="authModal"' ?>>
          <span class="h-label"><?= $isSignedIn ? 'Hello, ' . htmlspecialchars($userFirstName) : 'Hello, Sign in' ?></span>
          <strong class="h-value">
            <?= $isSignedIn ? 'Account &amp; Lists' : 'Account &amp; Lists' ?>
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </strong>
        </div>
        <?php if ($isSignedIn): ?>
        <div class="account-menu" id="accountMenu" hidden>
          <div class="account-menu-head">
            <div class="account-menu-title">Signed in as</div>
            <strong><?= htmlspecialchars($signedInUser['NAME'] ?? $userFirstName) ?></strong>
            <span><?= htmlspecialchars($signedInUser['EMAIL'] ?? '') ?></span>
          </div>
          <div class="account-menu-links">
            <?php foreach (sinelec_account_nav_items() as $key => $item): ?>
            <a href="<?= htmlspecialchars($item['href']) ?>" class="account-menu-link<?= $key === 'logout' ? ' is-logout' : '' ?>">
              <span class="account-menu-link-icon"><?= sinelec_account_icon($item['icon']) ?></span>
              <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Cart -->
      <button class="h-cart" onclick="openCart()" aria-label="Shopping cart">
        <span class="h-cart-icon-wrap">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
          <span class="cart-count cart-count-initial">0</span>
        </span>
        <span class="h-cart-label">Cart</span>
      </button>

    </div>
  </div>

  <!-- Nav Bar -->
  <nav class="nav-bar" aria-label="Main navigation">
    <div class="wrap">
      <div class="nav-inner">

        <!-- Products mega menu -->
        <div class="nav-item" id="productsNavItem">
          <a href="products" class="nav-link nav-link-drop <?= in_array($currentPage, ['products', 'product', 'new-arrivals']) ? 'active' : '' ?>"
             onclick="toggleProductsMenu(event)">
            Products
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </a>
          <div class="mega-menu mega-products">

            <!-- LEFT: Category list -->
            <div class="mega-cats-col">
              <a href="new-arrivals" class="mega-cat mega-cat-new active" data-cat-id="newest">
                Newest Products
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
              </a>
              <div class="mega-cats-divider"></div>
              <?php foreach ($productMegaMenu as $menuCategory): ?>
              <a
                href="products?cat=<?= urlencode($menuCategory['id']) ?>"
                class="mega-cat"
                data-cat-id="<?= htmlspecialchars($menuCategory['id']) ?>"
              >
                <?= htmlspecialchars($menuCategory['name']) ?>
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
              </a>
              <?php endforeach; ?>
              <a href="products" class="mega-see-all">SEE ALL</a>
            </div>

            <!-- RIGHT: Dynamic content panels -->
            <div class="mega-content-col">

              <div class="mega-panel active" data-panel-id="newest">
                <div class="mega-panel-head">
                  <span>Shop Newest Products <strong>By Category</strong></span>
                  <a href="new-arrivals" class="mega-panel-viewall">View All Newest →</a>
                </div>
                <div class="mega-cat-imgrid">
                  <?php foreach (array_slice($storeData['categories'] ?? [], 0, 6) as $idx => $categoryCard): ?>
                  <a href="products?cat=<?= urlencode($categoryCard['id']) ?>" class="mega-cat-card">
                    <div class="mega-cat-card-img <?= $idx % 3 === 0 ? 'mega-cat-card-img--blue' : ($idx % 3 === 1 ? 'mega-cat-card-img--green' : 'mega-cat-card-img--orange') ?>">
                      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                        <rect x="3" y="3" width="18" height="18" rx="3"/>
                        <rect x="8" y="8" width="8" height="8" rx="1.5"/>
                      </svg>
                    </div>
                    <div class="mega-cat-card-name"><?= htmlspecialchars($categoryCard['name']) ?></div>
                  </a>
                  <?php endforeach; ?>
                </div>
              </div>

              <?php foreach ($productMegaMenu as $menuCategory): ?>
              <div class="mega-panel" data-panel-id="<?= htmlspecialchars($menuCategory['id']) ?>">
                <div class="mega-panel-head">
                  <strong>Types of <?= htmlspecialchars($menuCategory['name']) ?></strong>
                  <a href="products?cat=<?= urlencode($menuCategory['id']) ?>" class="mega-panel-viewall">View all <?= htmlspecialchars($menuCategory['name']) ?> →</a>
                </div>
                <div class="mega-sub-2col">
                  <?php foreach ($menuCategory['subcategories'] as $subCategory): ?>
                  <a href="products?cat=<?= urlencode($menuCategory['id']) ?>&subcat=<?= urlencode($subCategory) ?>" class="mega-sub-link"><?= htmlspecialchars($subCategory) ?></a>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endforeach; ?>

            </div><!-- end mega-content-col -->
          </div>
        </div>

        <!-- Manufacturers -->
        <div class="nav-item">
          <a href="manufacturers" class="nav-link nav-link-drop <?= navClass('manufacturers', $currentPage) === 'nav-link active' ? 'active' : '' ?>">
            Manufacturers
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </a>
          <div class="mega-menu mega-simple">
            <div class="mega-simple-title">Our Manufacturers</div>
            <?php foreach ($storeData['manufacturers'] as $mfr): ?>
            <a href="products?mfr=<?= urlencode($mfr['name']) ?>" class="mega-simple-link"><?= htmlspecialchars($mfr['name']) ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Resources -->
        <div class="nav-item">
          <a href="resources" class="nav-link nav-link-drop <?= $currentPage === 'resources' ? 'active' : '' ?>">
            Resources
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </a>
          <div class="mega-menu mega-simple">
            <div class="mega-simple-title">Resources</div>

            <a href="chip-programming" class="mega-simple-link">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
               Chip Programming
               <span class="nav-badge">New</span>
            </a>


            <a href="resources#learning" class="mega-simple-link">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
              Learning Material
            </a>
            <a href="resources#datasheets" class="mega-simple-link">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              Datasheets
            </a>
            <a href="resources#manuals" class="mega-simple-link">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/></svg>
              Manuals
            </a>
            <a href="resources#appnotes" class="mega-simple-link">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Application Notes
            </a>
          </div>
        </div>

        <!-- Request a Quote -->
        <a href="request-a-quote" class="<?= navClass('request-a-quote', $currentPage) ?>">Request a Quote</a>

        <!-- About Sinelec -->
        <a href="about" class="<?= navClass('about', $currentPage) ?>">E-Shop</a>

      </div>
    </div>
  </nav>
</header>

<!-- ══════════ MOBILE MENU ════════════════════════════════════ -->
<div class="mobile-menu" id="mobMenu" aria-hidden="true">
  <div class="mob-overlay" onclick="closeMobMenu()"></div>
	<div class="mob-panel">
		    <div class="mob-hd">
		      <div class="mob-hd-title">
		        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
		        Hello, <?= htmlspecialchars($userFirstName) ?>
		      </div>
	      <button class="mob-close" onclick="closeMobMenu()" aria-label="Close menu">
	        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
	      </button>
	    </div>
		    <nav class="mob-nav">
		      <div class="mob-quick-grid">
		        <button type="button" class="mob-quick-card" onclick="closeMobMenu(); document.getElementById('headerDeliveryBtn')?.click();">
		          <span class="mob-quick-icon">
		            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
		          </span>
		          <span class="mob-quick-copy">
		            <strong>Delivery Location</strong>
		            <small id="mobDeliveryLocationText">Delhi 110001</small>
		          </span>
		        </button>
		        <button type="button" class="mob-quick-card" onclick="closeMobMenu(); document.getElementById('headerAccountBtn')?.click();">
		          <span class="mob-quick-icon">
		            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
		          </span>
		          <span class="mob-quick-copy">
		            <strong><?= $isSignedIn ? 'My Account' : 'Sign In / Register' ?></strong>
		            <small><?= $isSignedIn ? htmlspecialchars($userDisplayName ?: $userFirstName) : 'Access account and orders' ?></small>
		          </span>
		        </button>
		      </div>
		      <div class="mob-divider"></div>
		      <?php if ($isSignedIn): ?>
		      <div class="mob-section-title">My Account</div>
		      <a href="profile" class="mob-link <?= $currentPage === 'profile' ? 'on' : '' ?>">Profile</a>
		      <a href="my-orders" class="mob-link <?= $currentPage === 'my-orders' ? 'on' : '' ?>">My Order</a>
		      <a href="delivery-address" class="mob-link <?= $currentPage === 'delivery-address' ? 'on' : '' ?>">Delivery Address</a>
		      <a href="change-password" class="mob-link <?= $currentPage === 'change-password' ? 'on' : '' ?>">Change Password</a>
		      <a href="service?urlstring=<?= EncryptURL('action=Logout') ?>" class="mob-link">Logout</a>
		      <div class="mob-divider"></div>
		      <?php endif; ?>
		      <div class="mob-section-title">Browse</div>
		      <a href="index" class="mob-link <?= $currentPage === 'home' ? 'on' : '' ?>">
	        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
	        Home
	      </a>
	      <details class="mob-accordion" <?= in_array($currentPage, ['products', 'product', 'new-arrivals']) ? 'open' : '' ?>>
	        <summary class="mob-link mob-link--accordion <?= in_array($currentPage, ['products', 'product', 'new-arrivals']) ? 'on' : '' ?>">
	          <span class="mob-link-main">
	            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
	            Products
	          </span>
	          <span class="mob-accordion-arrow" aria-hidden="true">
	            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
	          </span>
	        </summary>
	        <div class="mob-accordion-body">
	          <a href="products" class="mob-sub-link">All Products</a>
	          <a href="new-arrivals" class="mob-sub-link">Newest Products</a>
	          <?php foreach ($productMegaMenu as $menuCategory): ?>
	          <a href="products?cat=<?= urlencode($menuCategory['id']) ?>" class="mob-sub-link"><?= htmlspecialchars($menuCategory['name']) ?></a>
	          <?php endforeach; ?>
	        </div>
	      </details>
	      <details class="mob-accordion" <?= $currentPage === 'manufacturers' ? 'open' : '' ?>>
	        <summary class="mob-link mob-link--accordion <?= $currentPage === 'manufacturers' ? 'on' : '' ?>">
	          <span class="mob-link-main">
	            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
	            Manufacturers
	          </span>
	          <span class="mob-accordion-arrow" aria-hidden="true">
	            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
	          </span>
	        </summary>
	        <div class="mob-accordion-body mob-accordion-body--chips">
	          <a href="manufacturers" class="mob-sub-link">All Manufacturers</a>
	          <?php foreach ($storeData['manufacturers'] as $mfr): ?>
	          <a href="products?mfr=<?= urlencode($mfr['name']) ?>" class="mob-sub-link"><?= htmlspecialchars($mfr['name']) ?></a>
	          <?php endforeach; ?>
	        </div>
	      </details>
	      <details class="mob-accordion" <?= $currentPage === 'resources' || $currentPage === 'chip-programming' ? 'open' : '' ?>>
	        <summary class="mob-link mob-link--accordion <?= $currentPage === 'resources' || $currentPage === 'chip-programming' ? 'on' : '' ?>">
	          <span class="mob-link-main">
	            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
	            Resources
	          </span>
	          <span class="mob-accordion-arrow" aria-hidden="true">
	            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
	          </span>
	        </summary>
	        <div class="mob-accordion-body">
	          <a href="chip-programming" class="mob-sub-link">Chip Programming</a>
	          <a href="resources#learning" class="mob-sub-link">Learning Material</a>
	          <a href="resources#datasheets" class="mob-sub-link">Datasheets</a>
	          <a href="resources#manuals" class="mob-sub-link">Manuals</a>
	          <a href="resources#appnotes" class="mob-sub-link">Application Notes</a>
	        </div>
	      </details>
	      <a href="request-a-quote" class="mob-link <?= $currentPage === 'request-a-quote' ? 'on' : '' ?>">
	        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
	        Request a Quote
	      </a>
	      <a href="about" class="mob-link <?= $currentPage === 'about' ? 'on' : '' ?>">
	        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
	        E-Shop
	      </a>
	    </nav>
	  </div>
	</div>

<!-- ══════════ DELIVERY LOCATION MODAL ═══════════════════════ -->
<div class="delivery-modal" id="deliveryModal" hidden>
  <div class="delivery-modal-backdrop" data-delivery-close></div>
  <div class="delivery-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="deliveryModalTitle">
    <button type="button" class="delivery-modal-close" data-delivery-close aria-label="Close">×</button>
    <h3 class="delivery-modal-title" id="deliveryModalTitle">Choose Delivery Location</h3>
    <p class="delivery-modal-subtitle">Set where you want orders to be delivered for accurate stock and shipping estimates.</p>

    <div class="delivery-modal-block">
      <div class="delivery-modal-label">Select existing address</div>
      <div class="delivery-address-list" id="deliveryAddressList"></div>
    </div>

    <div class="delivery-modal-block">
      <a href="delivery-address" class="delivery-add-address-link">+ Add new address</a>
    </div>

    <div class="delivery-modal-block delivery-modal-block--info">
      <div class="delivery-modal-label">Shipping and payment term for your location</div>
      <a href="shipping-payment-term" class="delivery-info-link">
        <span class="delivery-info-link-copy">
          <strong>Click here</strong>
          <small>Review delivery timelines, VAT guidance, accepted payment methods, and region-wise shipping costs.</small>
        </span>
        <span class="delivery-info-link-icon" aria-hidden="true">→</span>
      </a>
    </div>
  </div>
</div>

<!-- ══════════ AUTH MODAL ═════════════════════════════════════ -->
<div class="auth-modal" id="authModal" hidden>
  <div class="auth-modal-backdrop" data-auth-close></div>
  <div class="auth-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">
    <button type="button" class="auth-modal-close" data-auth-close aria-label="Close">×</button>

    <h3 class="auth-title" id="authModalTitle">Sign In</h3>
    <p class="auth-subtitle" id="authModalDesc">Sign in to continue.</p>

    <div class="auth-panel auth-panel-signin is-active" id="authSignInPanel">
      <form id="authSignInForm" class="auth-form" method="POST" action="service?urlstring=<?= EncryptURL('action=Login') ?>" novalidate>
        <label class="auth-field">
          <span>Email ID</span>
          <div class="auth-input-wrap">
            <span class="auth-input-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z" opacity=".0"/><path d="M4 6l8 6 8-6"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
            </span>
            <input type="email" id="authUserId" name="authUserId" required>
          </div>
        </label>

        <label class="auth-field">
          <span>Password</span>
          <div class="auth-input-wrap">
            <span class="auth-input-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0 1 10 0v3"/></svg>
            </span>
            <input type="password" id="authPassword" name="authPassword" required>
            <button type="button" class="auth-pass-eye" data-toggle-pass data-pass-target="authPassword" aria-label="Show password" title="Show password">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </label>

        <div class="auth-captcha auth-captcha-cloud">
          <?php if ($turnstileSiteKey !== ''): ?>
          <div class="turnstile-wrap">
            <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>" data-theme="light" data-size="flexible" data-action="login"></div>
          </div>
          <?php else: ?>
          <div class="auth-captcha-left">
            <span class="auth-captcha-ok">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8"><polyline points="20 6 9 17 4 12"/></svg>
            </span>
            <span>Captcha configuration missing</span>
          </div>
          <?php endif; ?>
        </div>

        <button type="submit" class="auth-primary-btn">Sign In</button>
      </form>

      <div class="auth-sep"><span>or</span></div>
      <a href="service?urlstring=<?= EncryptURL('action=GoogleLogin') ?>" class="auth-google-btn">
        <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
          <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.656 32.657 29.205 36 24 36c-6.627 0-12-5.373-12-12S17.373 12 24 12c3.06 0 5.849 1.154 7.971 3.029l5.657-5.657C34.053 6.053 29.277 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
          <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 16.108 19.013 12 24 12c3.06 0 5.849 1.154 7.971 3.029l5.657-5.657C34.053 6.053 29.277 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
          <path fill="#4CAF50" d="M24 44c5.176 0 9.86-1.977 13.409-5.197l-6.19-5.238C29.141 35.091 26.715 36 24 36c-5.184 0-9.623-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
          <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-.792 2.237-2.231 4.166-4.094 5.565l.003-.002 6.19 5.238C37.005 39.163 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
        </svg>
        Continue with Google
      </a>

      <div class="auth-links-row">
        <button type="button" class="auth-link-btn" data-auth-switch="signup">Create new account</button>
        <a href="forgot-password" class="auth-link-btn" id="authForgotBtn" data-loader="off">Forgot password</a>
      </div>

      <?php if ($isSignedIn): ?>
      <div class="auth-links-row auth-links-row-center">
        <a href="service?urlstring=<?= EncryptURL('action=Logout') ?>" class="auth-link-btn">Sign out</a>
      </div>
      <?php endif; ?>

      <p class="auth-terms">By continuing, you agree to our <a href="#">Terms of Use</a> &amp; <a href="#">Privacy Policy</a>.</p>
    </div>

    <div class="auth-panel auth-panel-signup" id="authSignUpPanel">
      <form id="authSignUpForm" class="auth-form" method="POST" action="service?urlstring=<?= EncryptURL('action=Insert') ?>">
          <label class="auth-field">
            <span>Full Name</span>
            <div class="auth-input-wrap">
              <span class="auth-input-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </span>
              <input type="text" id="authFullName" name="authFullName" required>
            </div>
          </label>
 
          <label class="auth-field">
            <span>Email ID</span>
            <div class="auth-input-wrap">
              <span class="auth-input-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6l8 6 8-6"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
              </span>
              <input type="email" id="authEmail" name="authEmail" required>
            </div>
          </label>


          <label class="auth-field">
            <span>Mobile Number</span>
            <div class="auth-input-wrap auth-phone-combo">
              <select name="phone_code" id="phone_code" class="auth-phone-code" required>
                <option value="49" selected>+49</option>
                <option value="91">+91</option>
                <option value="1">+1</option>
                <option value="44">+44</option>
                <option value="33">+33</option>
                <option value="39">+39</option>
                <option value="34">+34</option>
                <option value="31">+31</option>
              </select>
              <span class="auth-input-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7c.1.8.3 1.5.6 2.3a2 2 0 0 1-.5 2.1L8 9a16 16 0 0 0 7 7l.9-.9a2 2 0 0 1 2.1-.5c.8.3 1.5.5 2.3.6A2 2 0 0 1 22 16.9z"/></svg>
              </span>
              <input type="tel" id="authPhone" name="authPhone" class="auth-phone-number" inputmode="numeric" required>
            </div>
          </label>

          <label class="auth-field">
            <span>Password</span>
            <div class="auth-input-wrap">
              <span class="auth-input-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0 1 10 0v3"/></svg>
              </span>
              <input type="password" id="authPassCreate" name="authPassCreate" pattern="^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$" title="Minimum 8 characters with letters, numbers, and special character." required>
              <button type="button" class="auth-pass-eye" data-toggle-pass data-pass-target="authPassCreate" aria-label="Show password" title="Show password">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </label>

          <label class="auth-field">
            <span>Confirm Password</span>
            <div class="auth-input-wrap">
              <span class="auth-input-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0 1 10 0v3"/></svg>
              </span>
              <input type="password" id="authPassConfirm" name="authPassConfirm" minlength="8" required>
              <button type="button" class="auth-pass-eye" data-toggle-pass data-pass-target="authPassConfirm" aria-label="Show password" title="Show password">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </label>

          <div class="auth-captcha auth-captcha-cloud">
            <?php if ($turnstileSiteKey !== ''): ?>
            <div class="turnstile-wrap">
              <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>" data-theme="light" data-size="flexible" data-action="register"></div>
            </div>
            <?php else: ?>
            <div class="auth-captcha-left">
              <span class="auth-captcha-ok">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8"><polyline points="20 6 9 17 4 12"/></svg>
              </span>
              <span>Captcha configuration missing</span>
            </div>
            <?php endif; ?>
          </div>

        <button type="submit" class="auth-primary-btn">Create Account</button>
      </form>
      <div class="auth-sep"><span>or</span></div>
      <a href="service?urlstring=<?= EncryptURL('action=GoogleLogin') ?>" class="auth-google-btn">
        <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
          <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.656 32.657 29.205 36 24 36c-6.627 0-12-5.373-12-12S17.373 12 24 12c3.06 0 5.849 1.154 7.971 3.029l5.657-5.657C34.053 6.053 29.277 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
          <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 16.108 19.013 12 24 12c3.06 0 5.849 1.154 7.971 3.029l5.657-5.657C34.053 6.053 29.277 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
          <path fill="#4CAF50" d="M24 44c5.176 0 9.86-1.977 13.409-5.197l-6.19-5.238C29.141 35.091 26.715 36 24 36c-5.184 0-9.623-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
          <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-.792 2.237-2.231 4.166-4.094 5.565l.003-.002 6.19 5.238C37.005 39.163 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
        </svg>
        Continue with Google
      </a>

      <div class="auth-links-row auth-links-row-center">
        <span>Already have account?</span>
        <button type="button" class="auth-link-btn" data-auth-switch="signin">Sign in</button>
      </div>

      <p class="auth-terms">By continuing, you agree to our <a href="#">Terms of Use</a> &amp; <a href="#">Privacy Policy</a>.</p>
    </div>

  </div>
</div>
