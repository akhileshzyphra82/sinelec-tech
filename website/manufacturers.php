<?php
require_once '../data/store_data.php';
$currentPage = 'manufacturers';
$pageTitle   = 'Manufacturers — Sinelec Tech';
require_once 'header.php';

/* ── Build type map ─────────────────────────────────────────── */
$categoriesById = [];
foreach ($storeData['categories'] as $cat) {
    $categoriesById[$cat['id']] = $cat;
}

$mfrTypeMap = [];
foreach ($storeData['products'] as $p) {
    $mName = $p['manufacturer'] ?? '';
    $catId = $p['category'] ?? '';
    if ($mName === '' || $catId === '') continue;
    $mfrTypeMap[$mName][$catId] = true;
}

$typeCount = [];
foreach ($mfrTypeMap as $ts) {
    foreach (array_keys($ts) as $tid) {
        $typeCount[$tid] = ($typeCount[$tid] ?? 0) + 1;
    }
}

$manufacturers = $storeData['manufacturers'];
usort($manufacturers, fn($a,$b) => strcasecmp($a['name'], $b['name']));

$alphabet = range('A','Z');

/* ── Helpers ────────────────────────────────────────────────── */
$initials = function(string $n): string {
    $parts = preg_split('/\s+/', trim($n));
    $i = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $i .= strtoupper(substr($parts[1], 0, 1));
    else $i .= strtoupper(substr($parts[0], 1, 1));
    return preg_replace('/[^A-Z0-9]/', '', $i) ?: '--';
};

$countryFlag = function(string $c): string {
    $map = [
        'USA' => '🇺🇸', 'Germany' => '🇩🇪', 'Switzerland' => '🇨🇭',
        'Netherlands' => '🇳🇱', 'Japan' => '🇯🇵', 'Norway' => '🇳🇴',
        'China' => '🇨🇳', 'UK' => '🇬🇧', 'France' => '🇫🇷',
        'South Korea' => '🇰🇷', 'Taiwan' => '🇹🇼', 'India' => '🇮🇳',
    ];
    return $map[$c] ?? '🌐';
};

$avatarGradients = [
    'A'=>'#2563eb,#1d4ed8','B'=>'#7c3aed,#6d28d9','C'=>'#0891b2,#0e7490',
    'D'=>'#059669,#047857','E'=>'#d97706,#b45309','F'=>'#dc2626,#b91c1c',
    'G'=>'#2563eb,#7c3aed','H'=>'#0891b2,#059669','I'=>'#7c3aed,#db2777',
    'J'=>'#f59e0b,#ef4444','K'=>'#10b981,#0891b2','L'=>'#6366f1,#8b5cf6',
    'M'=>'#0f172a,#1e3a5f','N'=>'#1e40af,#3b82f6','O'=>'#dc2626,#f97316',
    'P'=>'#7c3aed,#2563eb','Q'=>'#059669,#10b981','R'=>'#ef4444,#dc2626',
    'S'=>'#0284c7,#0369a1','T'=>'#f59e0b,#d97706','U'=>'#8b5cf6,#7c3aed',
    'V'=>'#10b981,#059669','W'=>'#6366f1,#4f46e5','X'=>'#db2777,#be185d',
    'Y'=>'#f97316,#ea580c','Z'=>'#14b8a6,#0d9488',
];

$getGradient = function(string $name) use ($avatarGradients): string {
    $l = strtoupper(substr(preg_replace('/[^A-Z]/i','',strtoupper($name)),0,1));
    return $avatarGradients[$l] ?? '#1e40af,#2563eb';
};
?>

<main>
<div class="wrap page-wrap">

  <!-- Hero -->
  <div class="mfr-hero">
    <div class="mfr-hero-text">
      <div class="mfr-hero-eyebrow">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Authorised Distributor
      </div>
      <h1 class="mfr-hero-title">Our Manufacturer Partners</h1>
      <p class="mfr-hero-sub">We source genuine parts directly from <?= count($storeData['manufacturers']) ?>+ leading global semiconductor brands — every component is authentic and fully traceable.</p>
    </div>
    <div class="mfr-hero-stats">
      <div class="mfr-stat">
        <span class="mfr-stat-val"><?= count($storeData['manufacturers']) ?>+</span>
        <span class="mfr-stat-lbl">Brands</span>
      </div>
      <div class="mfr-stat-divider"></div>
      <div class="mfr-stat">
        <span class="mfr-stat-val"><?= count($storeData['products']) ?>+</span>
        <span class="mfr-stat-lbl">Products</span>
      </div>
      <div class="mfr-stat-divider"></div>
      <div class="mfr-stat">
        <span class="mfr-stat-val">100%</span>
        <span class="mfr-stat-lbl">Genuine</span>
      </div>
    </div>
  </div>

  <!-- Directory -->
  <section class="mfr-directory">
    <div class="mfr-layout">

      <!-- Sidebar -->
      <aside class="mfr-sidebar">

        <div class="mfr-sidebar-block">
          <div class="mfr-sidebar-label">Search Manufacturer</div>
          <div class="mfr-search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input id="mfrSearchInp" type="text" placeholder="e.g. Texas Instruments" autocomplete="off">
          </div>
        </div>

        <div class="mfr-sidebar-block">
          <div class="mfr-sidebar-label">Status</div>
          <div class="mfr-filter-list">
            <button type="button" class="mfr-filter-item is-active" data-status-filter="all">
              <span class="mfr-filter-dot" style="background:#6b7280;"></span>All Manufacturers
              <span class="mfr-filter-count"><?= count($manufacturers) ?></span>
            </button>
            <button type="button" class="mfr-filter-item" data-status-filter="new">
              <span class="mfr-filter-dot" style="background:#10b981;"></span>New Manufacturer
            </button>
          </div>
        </div>

        <div class="mfr-sidebar-block">
          <div class="mfr-sidebar-label">By Product Type</div>
          <div class="mfr-filter-list">
            <button type="button" class="mfr-filter-item is-active" data-type-filter="all">
              <span class="mfr-filter-dot" style="background:#6b7280;"></span>All Types
            </button>
            <?php foreach ($storeData['categories'] as $cat):
              $cnt = (int)($typeCount[$cat['id']] ?? 0);
              if ($cnt === 0) continue;
            ?>
            <button type="button" class="mfr-filter-item" data-type-filter="<?= htmlspecialchars($cat['id']) ?>">
              <span class="mfr-filter-dot" style="background:#3b82f6;"></span>
              <?= htmlspecialchars($cat['name']) ?>
              <span class="mfr-filter-count"><?= $cnt ?></span>
            </button>
            <?php endforeach; ?>
          </div>
        </div>

      </aside>

      <!-- Main -->
      <div class="mfr-main">

        <!-- Alphabet bar -->
        <div class="mfr-alpha-bar">
          <button type="button" class="mfr-alpha-btn is-active" data-alpha-filter="ALL">All</button>
          <?php foreach ($alphabet as $l): ?>
          <button type="button" class="mfr-alpha-btn" data-alpha-filter="<?= $l ?>"><?= $l ?></button>
          <?php endforeach; ?>
        </div>

        <!-- Results row -->
        <div class="mfr-results-bar">
          <span id="mfrResultCount" class="mfr-result-count">
            <?= count($manufacturers) ?> manufacturers found
          </span>
          <button type="button" id="mfrClearFilters" class="mfr-clear-btn" hidden>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Clear Filters
          </button>
        </div>

        <!-- Grid -->
        <div class="mfr-grid" id="mfrGrid">
          <?php foreach ($manufacturers as $mfr):
            $name       = (string)($mfr['name'] ?? '');
            $country    = (string)($mfr['country'] ?? '');
            $products   = (int)($mfr['products'] ?? 0);
            $typeIds    = array_keys($mfrTypeMap[$name] ?? []);
            sort($typeIds);
            $typeNames  = array_filter(array_map(fn($id) => $categoriesById[$id]['name'] ?? null, $typeIds));
            $isNew      = $products <= 1;
            $firstLetter = strtoupper(preg_replace('/[^A-Z]/i','',strtoupper($name)));
            $alpha      = $firstLetter !== '' ? substr($firstLetter,0,1) : '#';
            $grad       = $getGradient($name);
            $allTypes   = implode(', ', $typeNames) ?: 'General Electronics';
            $flag       = $countryFlag($country);
          ?>
          <article class="mfr-card"
            data-name="<?= htmlspecialchars(strtolower($name)) ?>"
            data-alpha="<?= htmlspecialchars($alpha) ?>"
            data-is-new="<?= $isNew ? '1' : '0' ?>"
            data-types="<?= htmlspecialchars(implode(',', $typeIds)) ?>"
            data-mfr-title="<?= htmlspecialchars($name) ?>"
            data-mfr-country="<?= htmlspecialchars($country) ?>"
            data-mfr-products="<?= $products ?>"
            data-mfr-status="<?= $isNew ? 'New Manufacturer' : 'Established Manufacturer' ?>"
            data-mfr-types="<?= htmlspecialchars($allTypes) ?>"
          >
            <div class="mfr-card-head">
              <div class="mfr-avatar" style="background:linear-gradient(135deg,<?= $grad ?>);">
                <?= htmlspecialchars($initials($name)) ?>
              </div>
              <?php if ($isNew): ?>
              <span class="mfr-new-badge">New</span>
              <?php endif; ?>
            </div>

            <div class="mfr-card-body">
              <div class="mfr-card-name"><?= htmlspecialchars($name) ?></div>
              <div class="mfr-card-meta">
                <span class="mfr-flag"><?= $flag ?></span>
                <span class="mfr-country"><?= htmlspecialchars($country) ?></span>
              </div>
              <?php if (!empty($typeNames)): ?>
              <div class="mfr-type-tags">
                <?php foreach (array_slice($typeNames, 0, 2) as $tn): ?>
                <span class="mfr-type-tag"><?= htmlspecialchars($tn) ?></span>
                <?php endforeach; ?>
                <?php if (count($typeNames) > 2): ?>
                <span class="mfr-type-tag mfr-type-more">+<?= count($typeNames)-2 ?></span>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>

            <div class="mfr-card-foot">
              <a href="products?mfr=<?= urlencode($name) ?>" class="mfr-btn-products">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="3"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>
                <?= $products ?> Product<?= $products !== 1 ? 's' : '' ?>
              </a>
              <button type="button" class="mfr-btn-detail mfr-detail-btn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Info
              </button>
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <div id="mfrEmptyState" class="mfr-empty" hidden>
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <div class="mfr-empty-title">No manufacturers found</div>
          <div class="mfr-empty-sub">Try adjusting your search or filters.</div>
          <button type="button" id="mfrEmptyClear" class="mfr-clear-btn" style="margin-top:12px;">Clear All Filters</button>
        </div>

      </div>
    </div>
  </section>

</div>
</main>

<!-- Detail Modal -->
<div id="mfrDetailModal" class="mfr-modal" hidden>
  <div class="mfr-modal-backdrop" data-close-modal></div>
  <div class="mfr-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="mfrModalTitle">
    <button type="button" class="mfr-modal-close" data-close-modal aria-label="Close">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="mfr-modal-avatar" id="mfrModalAvatar"></div>
    <h3 id="mfrModalTitle" class="mfr-modal-title"></h3>
    <div id="mfrModalStatusBadge" class="mfr-modal-status"></div>
    <div class="mfr-modal-grid">
      <div class="mfr-modal-item">
        <div class="mfr-modal-label">Country</div>
        <div id="mfrModalCountry" class="mfr-modal-value"></div>
      </div>
      <div class="mfr-modal-item">
        <div class="mfr-modal-label">Listed Products</div>
        <div id="mfrModalProducts" class="mfr-modal-value"></div>
      </div>
      <div class="mfr-modal-item mfr-modal-full">
        <div class="mfr-modal-label">Product Categories</div>
        <div id="mfrModalTypes" class="mfr-modal-value"></div>
      </div>
    </div>
    <a id="mfrModalLink" href="#" class="mfr-modal-cta">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>
      View All Products
    </a>
  </div>
</div>

<script>
(function () {
  const grid       = document.getElementById('mfrGrid');
  if (!grid) return;
  const cards      = Array.from(grid.querySelectorAll('.mfr-card'));
  const searchInp  = document.getElementById('mfrSearchInp');
  const alphaBtns  = Array.from(document.querySelectorAll('[data-alpha-filter]'));
  const statusBtns = Array.from(document.querySelectorAll('[data-status-filter]'));
  const typeBtns   = Array.from(document.querySelectorAll('[data-type-filter]'));
  const countEl    = document.getElementById('mfrResultCount');
  const clearBtn   = document.getElementById('mfrClearFilters');
  const emptyEl    = document.getElementById('mfrEmptyState');
  const emptyClear = document.getElementById('mfrEmptyClear');
  const modal      = document.getElementById('mfrDetailModal');
  const detailBtns = Array.from(document.querySelectorAll('.mfr-detail-btn'));

  const state = { search:'', alpha:'ALL', status:'all', type:'all' };

  const norm = v => (v||'').toLowerCase().trim();

  function setActive(btns, val, key) {
    btns.forEach(b => b.classList.toggle('is-active', b.dataset[key] === val));
  }

  function match(card) {
    if (state.search && !card.dataset.name.includes(state.search)) return false;
    if (state.alpha !== 'ALL' && card.dataset.alpha !== state.alpha) return false;
    if (state.status === 'new' && card.dataset.isNew !== '1') return false;
    if (state.type !== 'all' && !(card.dataset.types||'').split(',').includes(state.type)) return false;
    return true;
  }

  function render() {
    let n = 0;
    cards.forEach(c => { const v = match(c); c.style.display = v ? '' : 'none'; if(v) n++; });
    countEl.textContent = n + ' manufacturer' + (n===1?'':'s') + ' found';
    emptyEl.hidden = n !== 0;
    const hasFilter = state.search||state.alpha!=='ALL'||state.status!=='all'||state.type!=='all';
    clearBtn.hidden = !hasFilter;
  }

  function clearAll() {
    state.search = ''; state.alpha = 'ALL'; state.status = 'all'; state.type = 'all';
    if (searchInp) searchInp.value = '';
    setActive(alphaBtns, 'ALL', 'alphaFilter');
    setActive(statusBtns, 'all', 'statusFilter');
    setActive(typeBtns, 'all', 'typeFilter');
    render();
  }

  searchInp?.addEventListener('input', e => { state.search = norm(e.target.value); render(); });
  alphaBtns.forEach(b => b.addEventListener('click', () => { state.alpha = b.dataset.alphaFilter; setActive(alphaBtns, state.alpha, 'alphaFilter'); render(); }));
  statusBtns.forEach(b => b.addEventListener('click', () => { state.status = b.dataset.statusFilter; setActive(statusBtns, state.status, 'statusFilter'); render(); }));
  typeBtns.forEach(b => b.addEventListener('click', () => { state.type = b.dataset.typeFilter; setActive(typeBtns, state.type, 'typeFilter'); render(); }));
  clearBtn?.addEventListener('click', clearAll);
  emptyClear?.addEventListener('click', clearAll);

  /* Modal */
  function openModal(card) {
    const name     = card.dataset.mfrTitle || '';
    const country  = card.dataset.mfrCountry || '';
    const products = card.dataset.mfrProducts || '0';
    const status   = card.dataset.mfrStatus || '';
    const types    = card.dataset.mfrTypes || '';
    const grad     = card.querySelector('.mfr-avatar')?.style.background || 'linear-gradient(135deg,#1e40af,#2563eb)';

    document.getElementById('mfrModalAvatar').style.background = grad;
    document.getElementById('mfrModalAvatar').textContent = name.replace(/[^A-Z]/gi,'').slice(0,2).toUpperCase() || name.slice(0,2).toUpperCase();
    document.getElementById('mfrModalTitle').textContent = name;
    document.getElementById('mfrModalStatusBadge').textContent = status;
    document.getElementById('mfrModalStatusBadge').className = 'mfr-modal-status ' + (status.includes('New') ? 'is-new' : 'is-estab');
    document.getElementById('mfrModalCountry').textContent = country;
    document.getElementById('mfrModalProducts').textContent = products + ' products';
    document.getElementById('mfrModalTypes').textContent = types;
    document.getElementById('mfrModalLink').href = 'products?mfr=' + encodeURIComponent(name);
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeModal() { modal.hidden = true; document.body.style.overflow = ''; }

  detailBtns.forEach(b => b.addEventListener('click', e => { e.stopPropagation(); openModal(b.closest('.mfr-card')); }));
  document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', closeModal));
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

  render();
})();
</script>

<?php require_once 'footer.php'; ?>
