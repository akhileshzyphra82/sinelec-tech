<?php
require_once __DIR__ . '/account-helpers.php';
if (!isset($company)) {
    if (!class_exists('WebsiteController')) require_once __DIR__ . '/../controller/website_controller.php';
    $_wc = new WebsiteController();
    $company = $_wc->getCompanyInfo();
    /* ── Load search categories for dropdown ── */
    $_searchCatRows = $_wc->getSearchCategories();
    /* Group by parent_name */
    $_searchCatGroups = [];
    foreach ($_searchCatRows as $_scr) {
        $_grp  = (string)($_scr->PARENT_NAME ?? '');
        $_name = (string)($_scr->PRODUCT_CATEGORY_NAME ?? '');
        $_id   = (int)(float)($_scr->PRODUCT_CATEGORY_ID ?? 0);
        $_pid  = (int)(float)($_scr->PARENT_CATEGORY_ID ?? 0);
        /* If no parent, the category itself is a top-level group entry */
        $key = $_grp !== '' ? $_grp : $_name;
        $_searchCatGroups[$key][] = ['id' => $_id, 'name' => $_name, 'is_parent' => $_pid === 0];
    }
    unset($_wc, $_searchCatRows, $_scr, $_grp, $_name, $_id, $_pid, $key);
}
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

/* ── Build category tree for mega menu ── */
$_wc2 = new WebsiteController();
$_catRows2 = $_wc2->getCatalogCategories();
$_megaParents  = [];   // keyed by parent cat id
$_megaChildren = [];   // keyed by parent cat id => [child, ...]
foreach ($_catRows2 as $_cr) {
    $_cid  = (int)(float)($_cr->PRODUCT_CATEGORY_ID ?? 0);
    $_cname = (string)($_cr->PRODUCT_CATEGORY_NAME ?? '');
    $_pid  = (int)(float)($_cr->PARENT_CATEGORY_ID ?? 0);
    $_pname = (string)($_cr->PARENT_NAME ?? '');
    if ($_pid === 0) {
        if (!isset($_megaParents[$_cid]))
            $_megaParents[$_cid] = ['id' => $_cid, 'name' => $_cname, 'children' => []];
    } else {
        $_megaChildren[$_pid][] = ['id' => $_cid, 'name' => $_cname];
        if (!isset($_megaParents[$_pid]))
            $_megaParents[$_pid] = ['id' => $_pid, 'name' => $_pname, 'children' => []];
    }
}
foreach ($_megaChildren as $_pid => $_kids) {
    if (isset($_megaParents[$_pid]))
        $_megaParents[$_pid]['children'] = $_kids;
}
$_megaParents = array_values($_megaParents);

/* ── Manufacturers for mega menu ── */
$_navMfrs = [];
foreach ($_wc2->getCatalogManufacturers() as $_mr) {
    $_mfrCatIds = trim((string)($_mr->PRODUCT_CATEGORY_IDS ?? ''));
    $_navMfrs[] = [
        'id'      => (int)($_mr->MANUFACTURER_ID ?? 0),
        'name'    => (string)($_mr->NAME ?? ''),
        'cat_ids' => $_mfrCatIds,
    ];
}
unset($_wc2, $_catRows2, $_cr, $_cid, $_cname, $_pid, $_pname, $_kids, $_mr, $_mfrCatIds);
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
<?php if (!empty($pageCSS)): foreach ((array)$pageCSS as $_css): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($_css) ?>">
<?php endforeach; endif; ?>
<?php if ($turnstileSiteKey !== ''): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<?php
/* ── Build STORE_DATA from DB (replaces removed data/store_data.php) ── */
if (!class_exists('WebsiteController')) require_once __DIR__ . '/../controller/website_controller.php';
$_sdCtrl = new WebsiteController();
$_sdCats = $_sdCtrl->getAllCategoriesFlat();
$_sdMfrs = $_sdCtrl->getAllManufacturers();
$_dynCategories = array_map(fn($c) => [
    'id'   => (int)(float)($c->PRODUCT_CATEGORY_ID ?? 0),
    'name' => (string)($c->PRODUCT_CATEGORY_NAME ?? ''),
], $_sdCats);
$_dynManufacturers = array_map(fn($m) => (string)($m->NAME ?? ''), $_sdMfrs);
unset($_sdCtrl, $_sdCats, $_sdMfrs);
?>
<script>
window.STORE_DATA = <?= json_encode([
    'categories'   => $_dynCategories,
    'manufacturers'=> $_dynManufacturers,
    'products'     => [],
    'services'     => [],
    'testimonials' => [],
    'banners'      => [],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
window.CURRENT_PAGE = '<?= htmlspecialchars($currentPage) ?>';
window.SINELEC_AUTH = {
  isSignedIn: <?= $isSignedIn ? 'true' : 'false' ?>,
  userId: <?= (int)($signedInUser['USER_ID'] ?? 0) ?>
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
      <?php
      $logoSrc = trim((string)($company->LOGO ?? ''));
      if ($logoSrc === '') $logoSrc = '../assets/logo.png';
      ?>
      <a href="index" class="logo" aria-label="Sinelec Tech — Home">
        <img src="<?= htmlspecialchars($logoSrc) ?>" alt="Sinelec Tech" class="logo-img">
      </a>

      <!-- Search -->
      <!-- Outer wrapper: position:relative, NO overflow:hidden — dropdown must escape -->
      <div class="header-search-wrap" id="headerSearch">
        <div class="header-search">
        <form action="products" method="GET" autocomplete="off" class="search-form-contents" id="searchForm">
          <?php
          /* ── Dynamic category select ── */
          $selCatId = (int)($_GET['cat_id'] ?? 0);
          ?>
          <select class="search-cat-btn" name="cat_id" id="searchCat" onchange="onSearchCatChange()">
            <option value="0">All</option>
            <?php if (!empty($_searchCatGroups)): ?>
              <?php foreach ($_searchCatGroups as $_groupLabel => $_groupItems): ?>
                <?php
                /* If only one item and it IS the parent, render flat option */
                if (count($_groupItems) === 1 && $_groupItems[0]['is_parent']) {
                    $opt = $_groupItems[0];
                    $sel = $opt['id'] === $selCatId ? ' selected' : '';
                    echo '<option value="' . $opt['id'] . '"' . $sel . '>' . htmlspecialchars($opt['name']) . '</option>';
                } else {
                    echo '<optgroup label="' . htmlspecialchars($_groupLabel) . '">';
                    foreach ($_groupItems as $opt) {
                        $sel = $opt['id'] === $selCatId ? ' selected' : '';
                        echo '<option value="' . $opt['id'] . '"' . $sel . '>' . htmlspecialchars($opt['name']) . '</option>';
                    }
                    echo '</optgroup>';
                }
                ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
          <input class="search-field" id="searchField" type="text" name="q"
                 placeholder="Search part number, description or manufacturer…"
                 autocomplete="off" spellcheck="false"
                 value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
          <button class="search-go" type="submit" aria-label="Search">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </button>
        </form>
        </div><!-- /.header-search -->
        <!-- Suggestion dropdown — OUTSIDE the overflow:hidden container -->
        <div class="search-drop" id="searchDrop" role="listbox" aria-label="Search suggestions"></div>
      </div><!-- /.header-search-wrap -->

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
          <?php if ($isSignedIn): ?>
          <span class="h-avatar h-avatar--initial"><?= htmlspecialchars(strtoupper(substr($userFirstName, 0, 1))) ?></span>
          <?php else: ?>
          <span class="h-avatar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </span>
          <?php endif; ?>
          <strong class="h-value h-value--center">
            <?= $isSignedIn ? 'Hello, ' . htmlspecialchars($userFirstName) : 'Login or Register' ?>
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
          <a href="products" class="nav-link nav-link-drop <?= in_array($currentPage, ['products', 'product', 'new-arrivals']) ? 'active' : '' ?>">
            Products
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </a>
          <div class="mega-menu mega-products">

            <!-- LEFT: Category list -->
            <div class="mega-cats-col">
              <a href="products?is_new=1" class="mega-cat mega-cat-new active" data-cat-id="newest">
                New Products
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
              </a>
              <div class="mega-cats-divider"></div>
              <?php foreach ($_megaParents as $_mp): ?>
              <a href="products?cat_id=<?= $_mp['id'] ?>"
                 class="mega-cat"
                 data-cat-id="<?= $_mp['id'] ?>">
                <?= htmlspecialchars($_mp['name']) ?>
                <?php if (!empty($_mp['children'])): ?>
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                <?php endif; ?>
              </a>
              <?php endforeach; ?>
              <a href="products" class="mega-see-all">SEE ALL</a>
            </div>

            <!-- RIGHT: Dynamic content panels -->
            <div class="mega-content-col">

              <!-- New Products panel: show parent categories as cards -->
              <div class="mega-panel active" data-panel-id="newest">
                <div class="mega-panel-head">
                  <span>New Products <strong>By Category</strong></span>
                  <a href="products?is_new=1" class="mega-panel-viewall">View All New →</a>
                </div>
                <div class="mega-cat-imgrid">
                  <?php foreach (array_slice($_megaParents, 0, 6) as $_idx => $_pc): ?>
                  <a href="products?cat_id=<?= $_pc['id'] ?>&is_new=1" class="mega-cat-card">
                    <div class="mega-cat-card-img <?= $_idx % 3 === 0 ? 'mega-cat-card-img--blue' : ($_idx % 3 === 1 ? 'mega-cat-card-img--green' : 'mega-cat-card-img--orange') ?>">
                      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                        <rect x="3" y="3" width="18" height="18" rx="3"/><rect x="8" y="8" width="8" height="8" rx="1.5"/>
                      </svg>
                    </div>
                    <div class="mega-cat-card-name"><?= htmlspecialchars($_pc['name']) ?></div>
                  </a>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- Panel per parent category: show child categories -->
              <?php foreach ($_megaParents as $_mp): ?>
              <div class="mega-panel" data-panel-id="<?= $_mp['id'] ?>">
                <div class="mega-panel-head">
                  <strong><?= htmlspecialchars($_mp['name']) ?></strong>
                  <a href="products?cat_id=<?= $_mp['id'] ?>" class="mega-panel-viewall">View all →</a>
                </div>
                <?php if (!empty($_mp['children'])): ?>
                <div class="mega-sub-2col">
                  <?php foreach ($_mp['children'] as $_ch): ?>
                  <a href="products?cat_id=<?= $_ch['id'] ?>" class="mega-sub-link"><?= htmlspecialchars($_ch['name']) ?></a>
                  <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="mega-panel-empty">Browse all <?= htmlspecialchars($_mp['name']) ?> products</p>
                <?php endif; ?>
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
            <?php if (!empty($_navMfrs)): ?>
              <?php foreach ($_navMfrs as $_mfr):
                /* Always pass cat_ids; use 'none' sentinel when no categories set so products page shows 0 results */
                $_mfrCatParam = $_mfr['cat_ids'] !== '' ? $_mfr['cat_ids'] : 'none';
                $_mfrUrl = 'products?mfr=' . urlencode($_mfr['name']) . '&cat_ids=' . urlencode($_mfrCatParam);
              ?>
              <a href="<?= htmlspecialchars($_mfrUrl) ?>" class="mega-simple-link">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
                <?= htmlspecialchars($_mfr['name']) ?>
              </a>
              <?php endforeach; ?>
            <?php else: ?>
              <span class="mega-panel-empty">No manufacturers listed.</span>
            <?php endif; ?>
            <a href="manufacturers" class="mega-see-all" style="margin-top:10px;">View All →</a>
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
        <a href="products" class="<?= navClass('products', $currentPage) ?>">E-Shop</a>

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
	          <a href="products?is_new=1" class="mob-sub-link">New Products</a>
	          <?php foreach ($_megaParents as $_mp): ?>
	          <a href="products?cat_id=<?= $_mp['id'] ?>" class="mob-sub-link"><?= htmlspecialchars($_mp['name']) ?></a>
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
	          <?php foreach ($_navMfrs as $_mfr):
	            $_mobCatParam = $_mfr['cat_ids'] !== '' ? $_mfr['cat_ids'] : 'none';
	            $_mobMfrUrl = 'products?mfr=' . urlencode($_mfr['name']) . '&cat_ids=' . urlencode($_mobCatParam);
	          ?>
	          <a href="<?= htmlspecialchars($_mobMfrUrl) ?>" class="mob-sub-link"><?= htmlspecialchars($_mfr['name']) ?></a>
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
	      <a href="products" class="mob-link <?= $currentPage === 'products' ? 'on' : '' ?>">
	        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
	        E-Shop
	      </a>
	    </nav>
	  </div>
	</div>

<!-- ══════════ DELIVERY LOCATION MODAL ═══════════════════════ -->
<div class="delivery-modal" id="deliveryModal" hidden>
  <div class="delivery-modal-backdrop" data-delivery-close></div>
  <div class="delivery-modal-dialog dloc-dialog" role="dialog" aria-modal="true" aria-labelledby="deliveryModalTitle">
    <button type="button" class="delivery-modal-close" data-delivery-close aria-label="Close">×</button>
    <h3 class="delivery-modal-title" id="deliveryModalTitle">Choose Delivery Location</h3>
    <p class="delivery-modal-subtitle">Set where you want orders delivered for accurate stock &amp; shipping estimates.</p>

    <!-- ── Tabs ── -->
    <div class="dloc-tabs" role="tablist" aria-label="Location method">
      <button class="dloc-tab is-active" role="tab" aria-selected="true"  data-dloc-tab="geo"    type="button">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/><circle cx="12" cy="12" r="9" stroke-width="1.5"/></svg>
        Current Location
      </button>
      <button class="dloc-tab" role="tab" aria-selected="false" data-dloc-tab="postal" type="button">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        Enter Postal Code
      </button>
      <button class="dloc-tab" role="tab" aria-selected="false" data-dloc-tab="saved"  type="button">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Saved Addresses
      </button>
    </div>

    <!-- ── Panel: Geolocation ── -->
    <div class="dloc-panel is-active" id="dlocGeoPanel" role="tabpanel">
      <p class="dloc-panel-desc">Use your device GPS to automatically detect your location for accurate delivery estimates.</p>
      <button type="button" class="dloc-geo-btn" id="dlocGeoBtn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/><circle cx="12" cy="12" r="9" stroke-width="1.5"/></svg>
        Use Current Location
      </button>
      <div class="dloc-feedback" id="dlocGeoFeedback" aria-live="polite"></div>
    </div>

    <!-- ── Panel: Postal code ── -->
    <div class="dloc-panel" id="dlocPostalPanel" role="tabpanel" hidden>
      <p class="dloc-panel-desc">Enter your postal or zip code to check if we deliver to your area.</p>
      <form class="dloc-postal-form" id="dlocPostalForm" novalidate>
        <div class="dloc-postal-row">
          <input class="dloc-postal-input" type="text" id="dlocPostalInput"
                 placeholder="e.g. 110001 or SW1A 1AA"
                 autocomplete="postal-code" maxlength="12" inputmode="text">
          <button type="submit" class="dloc-postal-btn">Check</button>
        </div>
      </form>
      <div class="dloc-feedback" id="dlocPostalFeedback" aria-live="polite"></div>
    </div>

    <!-- ── Panel: Saved addresses ── -->
    <div class="dloc-panel" id="dlocSavedPanel" role="tabpanel" hidden>
      <?php if (!$isSignedIn): ?>
      <div class="dloc-login-prompt">
        <span class="dloc-login-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0 1 10 0v3"/></svg>
        </span>
        <p class="dloc-login-copy">Sign in to select from your saved addresses</p>
        <button type="button" class="dloc-login-btn" id="dlocLoginBtn">Sign In / Register</button>
      </div>
      <?php else: ?>
      <div class="dloc-saved-inner">
        <div class="dloc-saved-loading" id="dlocSavedLoading">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="dloc-spin" aria-hidden="true"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
          Loading addresses…
        </div>
        <div class="dloc-saved-list" id="dlocSavedList" hidden></div>
        <div class="dloc-feedback" id="dlocSavedFeedback" aria-live="polite"></div>
        <a href="delivery-address" class="dloc-add-link">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add New Address
        </a>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── Shipping info footer ── -->
    <div class="dloc-info-footer">
      <a href="shipping-payment-term" class="dloc-info-card">
        <span class="dloc-info-icon" aria-hidden="true">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2.5" stroke-linecap="round"/>
          </svg>
        </span>
        <span class="dloc-info-body">
          <span class="dloc-info-heading">Shipping &amp; Payment Terms for Your Location</span>
          <span class="dloc-info-cta">
            Click here
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </span>
          <span class="dloc-info-desc">Review delivery timelines, VAT guidance, accepted payment methods, and region-wise shipping costs.</span>
        </span>
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
        <input type="hidden" id="authRedirect" name="auth_redirect" value="">
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
