<?php
$currentPage = 'manufacturers';
$pageTitle   = 'Manufacturers — Sinelec Tech';

require_once '../common/functions.php';
require_once '../controller/website_controller.php';

$_wc   = new WebsiteController();
$_mfrs = $_wc->getAllManufacturers();
$_cats = $_wc->getAllCategoriesFlat();

/* Build category name lookup */
$_catNames = [];
foreach ($_cats as $_c)
    $_catNames[(int)(float)$_c->PRODUCT_CATEGORY_ID] = (string)($_c->PRODUCT_CATEGORY_NAME ?? '');

/* Resolve each manufacturer's categories */
$_BASE_URL  = rtrim((string)sinelec_env('PUBLIC_BASE_URL', ''), '/');
$_mfrData   = [];
foreach ($_mfrs as $_m) {
    $_rawCatIds  = trim((string)($_m->PRODUCT_CATEGORY_IDS ?? ''));
    $_catIds     = $_rawCatIds !== '' ? array_values(array_filter(array_map('intval', explode(',', $_rawCatIds)))) : [];
    $_catLabels  = array_values(array_filter(array_map(fn($id) => $_catNames[$id] ?? null, $_catIds)));
    $_logoRaw    = trim((string)($_m->LOGO ?? ''));
    $_logoUrl    = $_logoRaw !== '' ? $_BASE_URL . '/' . ltrim($_logoRaw, '/') : '';
    $_name       = (string)($_m->NAME ?? '');
    $_firstAlpha = strtoupper(preg_replace('/[^A-Za-z]/', '', $_name));
    $_letter     = $_firstAlpha !== '' ? substr($_firstAlpha, 0, 1) : '#';
    $_catParam   = count($_catIds) > 0 ? implode(',', $_catIds) : 'none';
    $_mfrData[]  = [
        'id'        => (int)$_m->MANUFACTURER_ID,
        'name'      => $_name,
        'letter'    => $_letter,
        'logo'      => $_logoUrl,
        'desc'      => trim((string)($_m->DESCRIPTION ?? '')),
        'cat_ids'   => $_catIds,
        'cat_names' => $_catLabels,
        'cat_param' => $_catParam,
        'is_home'   => (string)($_m->SHOULD_DISPLAY_IN_HOME ?? 'No') === 'Yes',
    ];
}

$_totalMfrs = count($_mfrData);

/* All categories for sidebar — show every category from DB */
$_filterCats = [];
foreach ($_cats as $_c) {
    $_cid = (int)(float)$_c->PRODUCT_CATEGORY_ID;
    $_filterCats[$_cid] = (string)($_c->PRODUCT_CATEGORY_NAME ?? '');
}
asort($_filterCats);

/* Avatar gradient map */
$_gradients = [
    'A'=>'135deg,#2563eb,#1d4ed8','B'=>'135deg,#7c3aed,#6d28d9','C'=>'135deg,#0891b2,#0e7490',
    'D'=>'135deg,#059669,#047857','E'=>'135deg,#d97706,#b45309','F'=>'135deg,#dc2626,#b91c1c',
    'G'=>'135deg,#2563eb,#7c3aed','H'=>'135deg,#0891b2,#059669','I'=>'135deg,#7c3aed,#db2777',
    'J'=>'135deg,#f59e0b,#ef4444','K'=>'135deg,#10b981,#0891b2','L'=>'135deg,#6366f1,#8b5cf6',
    'M'=>'135deg,#0f172a,#1e3a5f','N'=>'135deg,#1e40af,#3b82f6','O'=>'135deg,#dc2626,#f97316',
    'P'=>'135deg,#7c3aed,#2563eb','Q'=>'135deg,#059669,#10b981','R'=>'135deg,#ef4444,#dc2626',
    'S'=>'135deg,#0284c7,#0369a1','T'=>'135deg,#f59e0b,#d97706','U'=>'135deg,#8b5cf6,#7c3aed',
    'V'=>'135deg,#10b981,#059669','W'=>'135deg,#6366f1,#4f46e5','X'=>'135deg,#db2777,#be185d',
    'Y'=>'135deg,#f97316,#ea580c','Z'=>'135deg,#14b8a6,#0d9488',
];
$_getGrad = fn(string $l): string => $_gradients[$l] ?? '135deg,#1e40af,#2563eb';
$_initials = function(string $n): string {
    $words = preg_split('/[\s\-_&]+/', trim($n));
    $words = array_filter($words);
    if (count($words) >= 2)
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    return strtoupper(substr($n, 0, 2)) ?: '??';
};

require_once 'header.php';
?>

<main>

<!-- ── Hero Banner ──────────────────────────────────────────────── -->
<div class="mfr-page-hero">
  <div class="mfr-page-hero-inner wrap">
    <nav class="mfr-breadcrumb">
      <a href="index">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Home
      </a>
      <svg class="mfr-bc-sep" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      <span>Manufacturers</span>
    </nav>
    <h1 class="mfr-hero-title">Our Manufacturer Partners</h1>
    <p class="mfr-hero-sub">We source genuine components directly from <?= $_totalMfrs ?>+ leading global semiconductor and electronics brands — every part is authentic and fully traceable.</p>
  </div>
</div>

<!-- ── Filter Bar (search + alphabet) ──────────────────────────── -->
<div class="mfr-filterbar-wrap">
  <div class="wrap mfr-filterbar">
    <div class="mfr-fb-search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input id="mfrSearchInp" type="text" placeholder="Search manufacturers…" autocomplete="off">
    </div>

    <div class="mfr-alpha-strip">
      <button type="button" class="mfr-a-btn is-active" data-a="ALL">All</button>
      <?php foreach (range('A','Z') as $_l): ?>
      <button type="button" class="mfr-a-btn" data-a="<?= $_l ?>"><?= $_l ?></button>
      <?php endforeach; ?>
    </div>

    <div class="mfr-fb-meta">
      <span id="mfrCount" class="mfr-fb-count"><?= $_totalMfrs ?> manufacturers</span>
      <button type="button" id="mfrClearBtn" class="mfr-fb-clear" hidden>
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Clear All
      </button>
    </div>
  </div>
</div>

<!-- ── Body: Sidebar + Grid ─────────────────────────────────────── -->
<div class="wrap mfr-page-body">

  <!-- Category Sidebar -->
  <aside class="mfr-cat-sidebar" id="mfrCatSidebar">
    <div class="mfr-cat-sidebar-head">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Category
      <span id="mfrCatSelCount" class="mfr-cat-sel-count" hidden>0</span>
    </div>

    <div class="mfr-cat-list" id="mfrCatList">
      <?php foreach ($_filterCats as $_cid => $_cname): ?>
      <label class="mfr-cat-item">
        <input type="checkbox" class="mfr-cat-chk" value="<?= (int)$_cid ?>">
        <span class="mfr-cat-checkmark"></span>
        <span class="mfr-cat-label"><?= htmlspecialchars($_cname) ?></span>
      </label>
      <?php endforeach; ?>
    </div>

    <?php if (!empty($_filterCats)): ?>
    <button type="button" id="mfrCatClear" class="mfr-cat-clear-btn" hidden>
      Clear categories
    </button>
    <?php endif; ?>
  </aside>

  <!-- Manufacturer Grid -->
  <div class="mfr-main-col">
    <div class="mfr-pg-grid" id="mfrGrid">
      <?php foreach ($_mfrData as $_md):
        $_name      = $_md['name'];
        $_letter    = $_md['letter'];
        $_logo      = $_md['logo'];
        $_desc      = strip_tags($_md['desc']);   /* strip any HTML tags from description */
        $_catLabels = $_md['cat_names'];
        $_catParam  = $_md['cat_param'];
        $_isHome    = $_md['is_home'];
        $_init      = $_initials($_name);
        $_grad      = $_getGrad($_letter);
        $_catIdsStr = implode(',', $_md['cat_ids']);
        $_mfrUrl    = 'products?mfr=' . urlencode($_name) . '&cat_ids=' . urlencode($_catParam);
        $_catLabelStr = implode(', ', $_catLabels) ?: 'N/A';
      ?>
      <article class="mfr-pg-card"
        data-name="<?= htmlspecialchars(strtolower($_name)) ?>"
        data-letter="<?= htmlspecialchars($_letter) ?>"
        data-cat-ids="<?= htmlspecialchars($_catIdsStr) ?>"
        data-mfr-url="<?= htmlspecialchars($_mfrUrl) ?>"
        data-detail-name="<?= htmlspecialchars($_name) ?>"
        data-detail-desc="<?= htmlspecialchars($_desc) ?>"
        data-detail-cats="<?= htmlspecialchars($_catLabelStr) ?>"
        data-detail-logo="<?= htmlspecialchars($_logo) ?>"
        data-detail-grad="linear-gradient(<?= $_grad ?>)"
        data-detail-init="<?= htmlspecialchars($_init) ?>"
        data-detail-home="<?= $_isHome ? 'Yes' : 'No' ?>"
        style="cursor:pointer;"
        onclick="mfrCardClick(event, this)"
      >
        <div class="mfr-pg-card-top">
          <?php if ($_isHome): ?>
          <span class="mfr-pg-partner-badge">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Partner
          </span>
          <?php endif; ?>
          <div class="mfr-pg-logo-box">
            <?php if ($_logo !== ''): ?>
              <img src="<?= htmlspecialchars($_logo) ?>" alt="<?= htmlspecialchars($_name) ?>" class="mfr-pg-logo-img" loading="lazy">
            <?php else: ?>
              <div class="mfr-pg-avatar" style="background:linear-gradient(<?= $_grad ?>);">
                <?= htmlspecialchars($_init) ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="mfr-pg-card-body">
          <h3 class="mfr-pg-name"><?= htmlspecialchars($_name) ?></h3>
        </div>

        <div class="mfr-pg-card-foot">
          <button type="button" class="mfr-pg-detail-btn" onclick="mfrOpenDetail(event, this.closest('.mfr-pg-card'))">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            View Details
          </button>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <div id="mfrEmpty" class="mfr-pg-empty" hidden>
      <div class="mfr-pg-empty-icon">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </div>
      <p class="mfr-pg-empty-title">No manufacturers found</p>
      <p class="mfr-pg-empty-sub">Try adjusting your search, alphabet, or category filter.</p>
      <button type="button" id="mfrEmptyClear" class="mfr-fb-clear" style="margin-top:14px;display:inline-flex;">Clear All Filters</button>
    </div>
  </div>

</div><!-- /wrap -->
</main>

<!-- Manufacturer Detail Modal -->
<div id="mfrDetailModal" class="mfrd-modal" hidden>
  <div class="mfrd-backdrop" onclick="mfrCloseDetail()"></div>
  <div class="mfrd-dialog" role="dialog" aria-modal="true">
    <button type="button" class="mfrd-close" onclick="mfrCloseDetail()" aria-label="Close">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <!-- Logo / Avatar -->
    <div class="mfrd-logo-wrap">
      <img id="mfrdLogoImg" src="" alt="" class="mfrd-logo-img" hidden>
      <div id="mfrdAvatar" class="mfrd-avatar"></div>
    </div>

    <!-- Partner badge -->
    <div id="mfrdPartnerBadge" class="mfrd-partner-badge" hidden>
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Official Partner
    </div>

    <h3 id="mfrdName" class="mfrd-name"></h3>

    <div id="mfrdDescWrap" class="mfrd-desc-wrap">
      <p id="mfrdDesc" class="mfrd-desc"></p>
    </div>

    <div class="mfrd-info-grid">
      <div class="mfrd-info-item">
        <span class="mfrd-info-label">Product Categories</span>
        <span id="mfrdCats" class="mfrd-info-val"></span>
      </div>
    </div>

    <div class="mfrd-actions">
      <a id="mfrdViewProducts" href="#" class="mfrd-btn-primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="3"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>
        View Products
      </a>
      <button type="button" class="mfrd-btn-secondary" onclick="mfrCloseDetail()">Close</button>
    </div>
  </div>
</div>

<script>
/* ── Manufacturer card click: whole card → products page, button → detail modal ── */
function mfrCardClick(e, card) {
  /* Button click is handled separately by mfrOpenDetail */
  if (e.target.closest('.mfr-pg-detail-btn')) return;
  window.location.href = card.dataset.mfrUrl || '#';
}

function mfrOpenDetail(e, card) {
  e.stopPropagation();
  const d = card.dataset;
  const modal = document.getElementById('mfrDetailModal');

  const logoImg  = document.getElementById('mfrdLogoImg');
  const avatar   = document.getElementById('mfrdAvatar');
  if (d.detailLogo) {
    logoImg.src = d.detailLogo;
    logoImg.alt = d.detailName;
    logoImg.hidden = false;
    avatar.hidden  = true;
  } else {
    logoImg.hidden = true;
    avatar.hidden  = false;
    avatar.textContent  = d.detailInit || '';
    avatar.style.background = d.detailGrad || '#1d4ed8';
  }

  const badge = document.getElementById('mfrdPartnerBadge');
  badge.hidden = d.detailHome !== 'Yes';

  document.getElementById('mfrdName').textContent = d.detailName || '';

  const descWrap = document.getElementById('mfrdDescWrap');
  const desc     = (d.detailDesc || '').trim();
  document.getElementById('mfrdDesc').textContent = desc;
  descWrap.hidden = !desc;

  const cats = (d.detailCats || '').trim();
  document.getElementById('mfrdCats').textContent = cats !== 'N/A' && cats !== '' ? cats : 'Not specified';

  document.getElementById('mfrdViewProducts').href = d.mfrUrl || '#';

  modal.hidden = false;
  document.body.style.overflow = 'hidden';
}

function mfrCloseDetail() {
  document.getElementById('mfrDetailModal').hidden = true;
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') mfrCloseDetail(); });

/* ── Filter logic ── */
(function(){
  const grid       = document.getElementById('mfrGrid');
  const cards      = Array.from(grid ? grid.querySelectorAll('.mfr-pg-card') : []);
  const searchInp  = document.getElementById('mfrSearchInp');
  const alphaBtns  = Array.from(document.querySelectorAll('.mfr-a-btn'));
  const catChks    = Array.from(document.querySelectorAll('.mfr-cat-chk'));
  const countEl    = document.getElementById('mfrCount');
  const clearBtn   = document.getElementById('mfrClearBtn');
  const emptyEl    = document.getElementById('mfrEmpty');
  const emptyClear = document.getElementById('mfrEmptyClear');
  const catClear   = document.getElementById('mfrCatClear');
  const catSelCount= document.getElementById('mfrCatSelCount');

  const state = { q: '', letter: 'ALL', cats: new Set() };

  function applyFilters() {
    let n = 0;
    cards.forEach(card => {
      const name       = card.dataset.name || '';
      const letter     = card.dataset.letter || '';
      const cardCatIds = (card.dataset.catIds || '').split(',').filter(Boolean);

      const matchQ   = !state.q || name.includes(state.q.toLowerCase());
      const matchA   = state.letter === 'ALL' || letter === state.letter;
      const matchCat = state.cats.size === 0 || [...state.cats].some(id => cardCatIds.includes(id));

      const show = matchQ && matchA && matchCat;
      card.style.display = show ? '' : 'none';
      if (show) n++;
    });

    countEl.textContent = n + ' manufacturer' + (n === 1 ? '' : 's');
    emptyEl.hidden = n > 0;

    const hasCat = state.cats.size > 0;
    if (catSelCount) { catSelCount.hidden = !hasCat; catSelCount.textContent = state.cats.size; }
    if (catClear) catClear.hidden = !hasCat;
    clearBtn.hidden = !(state.q || state.letter !== 'ALL' || hasCat);
  }

  function clearAll() {
    state.q = ''; state.letter = 'ALL'; state.cats.clear();
    if (searchInp) searchInp.value = '';
    alphaBtns.forEach(b => b.classList.toggle('is-active', b.dataset.a === 'ALL'));
    catChks.forEach(c => { c.checked = false; });
    applyFilters();
  }

  searchInp?.addEventListener('input', e => { state.q = e.target.value.trim(); applyFilters(); });
  alphaBtns.forEach(b => b.addEventListener('click', () => {
    state.letter = b.dataset.a;
    alphaBtns.forEach(x => x.classList.toggle('is-active', x.dataset.a === state.letter));
    applyFilters();
  }));
  catChks.forEach(chk => chk.addEventListener('change', () => {
    if (chk.checked) state.cats.add(chk.value);
    else state.cats.delete(chk.value);
    applyFilters();
  }));
  clearBtn?.addEventListener('click', clearAll);
  emptyClear?.addEventListener('click', clearAll);
  catClear?.addEventListener('click', () => {
    state.cats.clear();
    catChks.forEach(c => { c.checked = false; });
    applyFilters();
  });

  applyFilters(); /* run once on load to set correct count and hide empty state */
})();
</script>

<?php require_once 'footer.php'; ?>
