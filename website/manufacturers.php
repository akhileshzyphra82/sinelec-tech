<?php
require_once '../data/store_data.php';
$currentPage = 'manufacturers';
$pageTitle   = 'Manufacturers — Sinelec Tech';
require_once 'header.php';

$categoriesById = [];
foreach ($storeData['categories'] as $category) {
    $categoriesById[$category['id']] = $category;
}

$manufacturerTypeMap = [];
foreach ($storeData['products'] as $product) {
    $manufacturerName = $product['manufacturer'] ?? '';
    $categoryId = $product['category'] ?? '';
    if ($manufacturerName === '' || $categoryId === '') {
        continue;
    }
    if (!isset($manufacturerTypeMap[$manufacturerName])) {
        $manufacturerTypeMap[$manufacturerName] = [];
    }
    $manufacturerTypeMap[$manufacturerName][$categoryId] = true;
}

$typeManufacturerCounts = [];
foreach ($manufacturerTypeMap as $typeSet) {
    foreach (array_keys($typeSet) as $typeId) {
        if (!isset($typeManufacturerCounts[$typeId])) {
            $typeManufacturerCounts[$typeId] = 0;
        }
        $typeManufacturerCounts[$typeId]++;
    }
}

$manufacturers = $storeData['manufacturers'];
usort($manufacturers, static function ($a, $b) {
    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
});

$alphabet = range('A', 'Z');

$makeInitials = static function (string $name): string {
    $name = trim($name);
    if ($name === '') {
        return '--';
    }
    $parts = preg_split('/\s+/', $name);
    if (!$parts) {
        return strtoupper(substr($name, 0, 2));
    }
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr($parts[1], 0, 1));
    } else {
        $initials .= strtoupper(substr($parts[0], 1, 1));
    }
    return preg_replace('/[^A-Z0-9]/', '', $initials) ?: '--';
};
?>

<main>
<div class="wrap page-wrap">

  <!-- Page Header -->
  <div class="page-hero">
    <div class="page-eyebrow">Authorised Distributor</div>
    <h1 class="page-title">Our Manufacturer Partners</h1>
    <p class="page-sub">We source directly from <?= count($storeData['manufacturers']) ?>+ leading semiconductor brands — every part is genuine and fully traceable.</p>
  </div>

  <section class="mfr-directory">
    <div class="mfr-layout">
      <aside class="mfr-sidebar">
        <div class="mfr-refine-block">
          <label for="mfrSearchInp" class="mfr-control-label">Search Box</label>
          <div class="mfr-search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="7"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input id="mfrSearchInp" type="text" placeholder="Search For A Manufacturer" autocomplete="off">
          </div>
        </div>

        <div class="mfr-refine-block">
          <div class="mfr-control-label">Status (New Manufacturer)</div>
          <div class="mfr-chip-col">
            <button type="button" class="mfr-chip is-active" data-status-filter="all">All</button>
            <button type="button" class="mfr-chip" data-status-filter="new">New Manufacturer</button>
          </div>
        </div>

        <div class="mfr-refine-block">
          <div class="mfr-control-label">MFG By Product Type</div>
          <div class="mfr-chip-col">
            <button type="button" class="mfr-chip is-active" data-type-filter="all">All Types</button>
            <?php foreach ($storeData['categories'] as $category): ?>
              <?php
                $catId = $category['id'];
                $catCount = (int)($typeManufacturerCounts[$catId] ?? 0);
                if ($catCount === 0) {
                    continue;
                }
              ?>
              <button type="button" class="mfr-chip" data-type-filter="<?= htmlspecialchars($catId) ?>">
                <?= htmlspecialchars($category['name']) ?> (<?= $catCount ?>)
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>

      <div class="mfr-main">
        <div class="mfr-alpha-wrap">
          <button type="button" class="mfr-alpha is-active" data-alpha-filter="ALL">All</button>
          <?php foreach ($alphabet as $letter): ?>
            <button type="button" class="mfr-alpha" data-alpha-filter="<?= $letter ?>"><?= $letter ?></button>
          <?php endforeach; ?>
        </div>

        <div class="mfr-results-head">
          <div id="mfrResultCount" class="mfr-results-count"><?= count($manufacturers) ?> manufacturers found</div>
          <button type="button" id="mfrClearFilters" class="mfr-clear-btn">Clear Filters</button>
        </div>

        <div class="mfr-grid" id="mfrGrid">
          <?php foreach ($manufacturers as $mfr): ?>
            <?php
              $manufacturerName = (string)($mfr['name'] ?? '');
              $manufacturerCountry = (string)($mfr['country'] ?? '');
              $productCount = (int)($mfr['products'] ?? 0);
              $typeIds = array_keys($manufacturerTypeMap[$manufacturerName] ?? []);
              sort($typeIds);
              $typeNames = [];
              foreach ($typeIds as $typeId) {
                  if (isset($categoriesById[$typeId]['name'])) {
                      $typeNames[] = $categoriesById[$typeId]['name'];
                  }
              }
              $isNewManufacturer = $productCount <= 1;
              $nameForAlpha = strtoupper(preg_replace('/[^A-Z]/', '', strtoupper($manufacturerName)));
              $alpha = $nameForAlpha !== '' ? substr($nameForAlpha, 0, 1) : '#';
              $previewTypes = array_slice($typeNames, 0, 2);
              $allTypeNames = !empty($typeNames) ? implode(', ', $typeNames) : 'Category mapping in progress';
            ?>
            <article
              class="mfr-card"
              data-name="<?= htmlspecialchars(strtolower($manufacturerName)) ?>"
              data-alpha="<?= htmlspecialchars($alpha) ?>"
              data-is-new="<?= $isNewManufacturer ? '1' : '0' ?>"
              data-types="<?= htmlspecialchars(implode(',', $typeIds)) ?>"
            >
              <div class="mfr-card-top">
                <div class="mfr-logo-placeholder"><?= htmlspecialchars($makeInitials($manufacturerName)) ?></div>
                <?php if ($isNewManufacturer): ?>
                  <span class="mfr-status-badge">New</span>
                <?php endif; ?>
              </div>
              <div class="mfr-card-name"><?= htmlspecialchars($manufacturerName) ?></div>
              <div class="mfr-card-country"><?= htmlspecialchars($manufacturerCountry) ?></div>
              <a href="products?mfr=<?= urlencode($manufacturerName) ?>" class="mfr-card-products mfr-card-products-link"><?= $productCount ?> products</a>
              <div class="mfr-type-preview">
                <?php if (!empty($previewTypes)): ?>
                  <?= htmlspecialchars(implode(' • ', $previewTypes)) ?>
                <?php else: ?>
                  Category mapping in progress
                <?php endif; ?>
              </div>
              <div class="mfr-card-actions">
                <button
                  type="button"
                  class="mfr-detail-btn"
                  data-mfr-title="<?= htmlspecialchars($manufacturerName) ?>"
                  data-mfr-country="<?= htmlspecialchars($manufacturerCountry) ?>"
                  data-mfr-products="<?= $productCount ?>"
                  data-mfr-status="<?= $isNewManufacturer ? 'New Manufacturer' : 'Established Manufacturer' ?>"
                  data-mfr-types="<?= htmlspecialchars($allTypeNames) ?>"
                >
                  View Details
                </button>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <div id="mfrEmptyState" class="mfr-empty-state" hidden>
          <div class="mfr-empty-title">No manufacturers found</div>
          <div class="mfr-empty-sub">Try another name, alphabet, status, or product type filter.</div>
        </div>
      </div>
    </div>
  </section>

  <div id="mfrDetailModal" class="mfr-modal" hidden>
    <div class="mfr-modal-backdrop" data-close-modal></div>
    <div class="mfr-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="mfrModalTitle">
      <button type="button" class="mfr-modal-close" data-close-modal aria-label="Close">×</button>
      <div class="mfr-modal-eyebrow">Manufacturer Details</div>
      <h3 id="mfrModalTitle" class="mfr-modal-title"></h3>
      <div class="mfr-modal-grid">
        <div class="mfr-modal-item">
          <div class="mfr-modal-label">Country</div>
          <div id="mfrModalCountry" class="mfr-modal-value"></div>
        </div>
        <div class="mfr-modal-item">
          <div class="mfr-modal-label">Status</div>
          <div id="mfrModalStatus" class="mfr-modal-value"></div>
        </div>
        <div class="mfr-modal-item">
          <div class="mfr-modal-label">Listed Products</div>
          <div id="mfrModalProducts" class="mfr-modal-value"></div>
        </div>
        <div class="mfr-modal-item mfr-modal-item--full">
          <div class="mfr-modal-label">Product Types</div>
          <div id="mfrModalTypes" class="mfr-modal-value"></div>
        </div>
        <div class="mfr-modal-item mfr-modal-item--full">
          <div class="mfr-modal-label">Description</div>
          <div id="mfrModalDescription" class="mfr-modal-value"></div>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const grid = document.getElementById('mfrGrid');
      if (!grid) return;

      const state = {
        search: '',
        alpha: 'ALL',
        status: 'all',
        type: 'all'
      };

      const cards = Array.from(grid.querySelectorAll('.mfr-card'));
      const searchInput = document.getElementById('mfrSearchInp');
      const alphaButtons = Array.from(document.querySelectorAll('[data-alpha-filter]'));
      const statusButtons = Array.from(document.querySelectorAll('[data-status-filter]'));
      const typeButtons = Array.from(document.querySelectorAll('[data-type-filter]'));
      const countNode = document.getElementById('mfrResultCount');
      const emptyNode = document.getElementById('mfrEmptyState');
      const clearBtn = document.getElementById('mfrClearFilters');
      const detailButtons = Array.from(document.querySelectorAll('.mfr-detail-btn'));
      const modal = document.getElementById('mfrDetailModal');
      const modalTitle = document.getElementById('mfrModalTitle');
      const modalCountry = document.getElementById('mfrModalCountry');
      const modalStatus = document.getElementById('mfrModalStatus');
      const modalProducts = document.getElementById('mfrModalProducts');
      const modalTypes = document.getElementById('mfrModalTypes');
      const modalDescription = document.getElementById('mfrModalDescription');
      const closeModalButtons = Array.from(document.querySelectorAll('[data-close-modal]'));

      const normalize = (value) => (value || '').toLowerCase().trim();

      function setActive(buttons, activeValue, key) {
        buttons.forEach((button) => {
          const value = button.dataset[key];
          button.classList.toggle('is-active', value === activeValue);
        });
      }

      function isMatch(card) {
        const name = card.dataset.name || '';
        const alpha = card.dataset.alpha || '';
        const isNew = card.dataset.isNew === '1';
        const types = (card.dataset.types || '').split(',').filter(Boolean);

        if (state.search && !name.includes(state.search)) return false;
        if (state.alpha !== 'ALL' && alpha !== state.alpha) return false;
        if (state.status === 'new' && !isNew) return false;
        if (state.type !== 'all' && !types.includes(state.type)) return false;
        return true;
      }

      function updateClearButton() {
        const hasFilters =
          state.search !== '' ||
          state.alpha !== 'ALL' ||
          state.status !== 'all' ||
          state.type !== 'all';
        clearBtn.hidden = !hasFilters;
      }

      function render() {
        let visible = 0;
        cards.forEach((card) => {
          const match = isMatch(card);
          card.style.display = match ? '' : 'none';
          if (match) visible++;
        });

        countNode.textContent = `${visible} manufacturer${visible === 1 ? '' : 's'} found`;
        emptyNode.hidden = visible !== 0;
        updateClearButton();
      }

      searchInput?.addEventListener('input', function (event) {
        state.search = normalize(event.target.value);
        render();
      });

      alphaButtons.forEach((button) => {
        button.addEventListener('click', function () {
          state.alpha = button.dataset.alphaFilter || 'ALL';
          setActive(alphaButtons, state.alpha, 'alphaFilter');
          render();
        });
      });

      statusButtons.forEach((button) => {
        button.addEventListener('click', function () {
          state.status = button.dataset.statusFilter || 'all';
          setActive(statusButtons, state.status, 'statusFilter');
          render();
        });
      });

      typeButtons.forEach((button) => {
        button.addEventListener('click', function () {
          state.type = button.dataset.typeFilter || 'all';
          setActive(typeButtons, state.type, 'typeFilter');
          render();
        });
      });

      clearBtn?.addEventListener('click', function () {
        state.search = '';
        state.alpha = 'ALL';
        state.status = 'all';
        state.type = 'all';

        if (searchInput) searchInput.value = '';
        setActive(alphaButtons, state.alpha, 'alphaFilter');
        setActive(statusButtons, state.status, 'statusFilter');
        setActive(typeButtons, state.type, 'typeFilter');
        render();
      });

      function openModal(button) {
        const mfrName = button.dataset.mfrTitle || '';
        const mfrCountry = button.dataset.mfrCountry || 'N/A';
        const mfrStatus = button.dataset.mfrStatus || 'N/A';
        const mfrProducts = Number(button.dataset.mfrProducts || 0);
        const mfrTypes = button.dataset.mfrTypes || 'N/A';

        modalTitle.textContent = button.dataset.mfrTitle || '';
        modalCountry.textContent = mfrCountry;
        modalStatus.textContent = mfrStatus;
        modalProducts.textContent = `${mfrProducts} products`;
        modalTypes.textContent = mfrTypes;
        modalDescription.textContent = `${mfrName} is a ${mfrStatus.toLowerCase()} based in ${mfrCountry}. Currently ${mfrProducts} products are listed with focus on ${mfrTypes}.`;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
      }

      function closeModal() {
        if (!modal) return;
        modal.hidden = true;
        document.body.style.overflow = '';
      }

      detailButtons.forEach((button) => {
        button.addEventListener('click', function () {
          openModal(button);
        });
      });

      closeModalButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
      });

      render();
    })();
  </script>

  <!-- Why Genuine Section -->
  <div class="trust-badges mt-32">
    <div class="trust-badges-grid">
      <div>
        <div class="trust-badge-icon">🔍</div>
        <div class="trust-badge-title">Authenticity Verified</div>
        <div class="trust-badge-sub">Every part traced to source</div>
      </div>
      <div>
        <div class="trust-badge-icon">📄</div>
        <div class="trust-badge-title">Full Documentation</div>
        <div class="trust-badge-sub">Datasheets &amp; CoC on request</div>
      </div>
      <div>
        <div class="trust-badge-icon">🤝</div>
        <div class="trust-badge-title">Authorised Partner</div>
        <div class="trust-badge-sub">Direct manufacturer relationships</div>
      </div>
      <div>
        <div class="trust-badge-icon">🚫</div>
        <div class="trust-badge-title">Zero Counterfeits</div>
        <div class="trust-badge-sub">100% grey-market free</div>
      </div>
    </div>
  </div>

</div>
</main>

<?php require_once 'footer.php'; ?>
