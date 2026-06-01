<?php
$currentPage = 'products';
$pageTitle   = 'Products — Sinelec Tech';

/* Support both old slug-based ?cat= and new DB-id ?cat_id= from search */
$catIdFilter  = (int)($_GET['cat_id'] ?? 0);
$catFilter    = htmlspecialchars($_GET['cat']    ?? '');
$mfrFilter    = htmlspecialchars($_GET['mfr']    ?? '');
$query        = htmlspecialchars($_GET['q']      ?? '');
$subcatFilter = htmlspecialchars($_GET['subcat'] ?? '');
/* Comma-separated category IDs passed when coming from a manufacturer link.
   'none' sentinel means manufacturer has no categories → shows 0 products. */
$_rawCatIds   = $_GET['cat_ids'] ?? '';
$catIdsFilter = ($_rawCatIds === 'none') ? 'none' : preg_replace('/[^0-9,]/', '', $_rawCatIds);

if ($query)    $pageTitle = 'Search: "' . $query . '" — Sinelec Tech';
if ($mfrFilter) $pageTitle = $mfrFilter . ' Products — Sinelec Tech';

require_once 'header.php';
?>

<main>
<div class="wrap catalog-wrap page-wrap">

  <!-- ── Toolbar box: breadcrumb + controls in one card ────────── -->
  <div class="catalog-toolbar">
    <!-- Row 1: Breadcrumb -->
    <nav class="breadcrumb" id="catalogBC"></nav>

    <!-- Separator -->
    <div class="toolbar-bc-sep"></div>

    <!-- Row 2: Search + results + sort + view -->
    <div class="toolbar-controls">
      <div class="toolbar-search-wrap">
        <svg class="toolbar-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="toolbar-search-inp" id="filterSearchInp"
               placeholder="Search products…"
               oninput="onFilterSearch(this.value)">
      </div>

      <span class="toolbar-divider"></span>
      <div class="toolbar-pagination" id="toolbarPagination"></div>
      <span class="toolbar-divider"></span>

      <select class="sort-sel" id="sortSel">
        <option value="featured">Sort: Featured</option>
        <option value="price-asc">Price: Low → High</option>
        <option value="price-desc">Price: High → Low</option>
        <option value="rating">Avg. Customer Review</option>
        <option value="new">Newest Arrivals</option>
        <option value="name">Name A–Z</option>
      </select>

      <div class="view-toggle">
        <button class="vbtn on" data-view="grid" title="Grid view">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </button>
        <button class="vbtn" data-view="list" title="List view">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
      </div>

      <button class="btn btn-outline btn-sm" id="mobileFilterBtn" onclick="openFilterPanel()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        Filters
      </button>
    </div>
  </div>

  <!-- ── Active filter tags ──────────────────────────────────────── -->
  <div id="activeFiltersRow" class="active-filters"></div>

  <!-- ── Catalog layout: sidebar + products ────────────────────── -->
  <div class="catalog-layout">

    <!-- Filter Sidebar -->
    <aside class="filter-col" id="filterSidebar">
      <div class="filter-header">
        <span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
          Filters
        </span>
        <button onclick="clearFilters()">Clear all</button>
      </div>

      <div class="filter-body">

        <!-- Category (accordion rendered by JS) -->
        <div class="filter-group" id="fgCategory">
          <div class="filter-group-title" onclick="toggleFilterGroup('fgCategory')">
            <span>Category</span>
            <svg class="fg-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
          <div class="filter-group-body" id="catFilters"></div>
        </div>

        <!-- Manufacturer -->
        <div class="filter-group" id="fgManufacturer">
          <div class="filter-group-title" onclick="toggleFilterGroup('fgManufacturer')">
            <span>Manufacturer</span>
            <svg class="fg-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
          <div class="filter-group-body" id="mfrFilters"></div>
        </div>

        <!-- Customer Rating -->
        <div class="filter-group" id="fgRating">
          <div class="filter-group-title" onclick="toggleFilterGroup('fgRating')">
            <span>Customer Rating</span>
            <svg class="fg-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
          <div class="filter-group-body" id="ratingFilters"></div>
        </div>

        <!-- Price Range -->
        <div class="filter-group" id="fgPrice">
          <div class="filter-group-title" onclick="toggleFilterGroup('fgPrice')">
            <span>Price (€)</span>
            <svg class="fg-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
          <div class="filter-group-body">
            <div class="price-row">
              <input type="number" class="price-inp" id="priceMin" placeholder="Min" min="0">
              <input type="number" class="price-inp" id="priceMax" placeholder="Max" min="0">
            </div>
            <button class="filter-apply-btn" onclick="applyPriceFilter()">Apply Price Filter</button>
          </div>
        </div>

        <!-- Availability (hidden) -->
        <!--
        <div class="filter-group" id="fgAvail">
          <div class="filter-group-title" onclick="toggleFilterGroup('fgAvail')">
            <span>Availability</span>
            <svg class="fg-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
          <div class="filter-group-body">
            <label class="filter-item">
              <input type="checkbox" class="filter-check" id="filterStock">
              <span>In Stock Only</span>
            </label>
            <label class="filter-item">
              <input type="checkbox" class="filter-check" id="filterNew">
              <span>New Arrivals Only</span>
            </label>
          </div>
        </div>
        -->

      </div><!-- /filter-body -->
    </aside>

    <!-- Main catalog area -->
    <div class="catalog-main">
      <div class="prod-grid" id="prodGrid"></div>
      <div class="pagination" id="catalogPagination">
        <button class="pg-btn" id="pgPrev">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <div id="pgButtons"></div>
        <button class="pg-btn" id="pgNext">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>

  </div><!-- /catalog-layout -->
</div><!-- /catalog-wrap -->

<!-- Filter overlay (mobile) -->
<div class="filter-overlay" id="filterOverlay" onclick="closeFilterPanel()"></div>

<script>
window.CATALOG_INIT = {
  cat:    '<?= $catFilter ?>',
  catId:  <?= $catIdFilter ?>,           /* DB integer category id (from search dropdown) */
  catIds: '<?= $catIdsFilter ?>',        /* comma-separated IDs from manufacturer link */
  mfr:    '<?= addslashes($mfrFilter) ?>',
  q:      '<?= addslashes($query) ?>',
  subcat: '<?= addslashes($subcatFilter) ?>',
  isNew:  false
};
</script>
</main>

<?php require_once 'footer.php'; ?>
