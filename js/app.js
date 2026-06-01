/* ============================================================
   SINELEC TECH — Main App (multi-page PHP architecture)
   ============================================================ */

/* ── Data bridge (PHP → JS) ──────────────────────────────────── */
const STORE_DATA    = window.STORE_DATA    || { categories:[], manufacturers:[], products:[], services:[], testimonials:[], banners:[] };
const CURRENT_PAGE  = window.CURRENT_PAGE  || 'home';
const AUTH_STATE    = window.SINELEC_AUTH  || { isSignedIn:false };
const CATALOG_INIT  = window.CATALOG_INIT  || { cat:'', mfr:'', q:'', subcat:'', isNew:false };
const CURRENT_PRODUCT = window.CURRENT_PRODUCT || null;
const EUR_FORMAT = new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' });
const CATEGORY_NAME_MAP = Object.fromEntries((STORE_DATA.categories || []).map(c => [c.id, c.name]));

/* ── Page routing map ────────────────────────────────────────── */
const PAGE_MAP = {
  home:            'index',
  products:        'products',
  catalog:         'products',
  manufacturers:   'manufacturers',
  resources:       'resources',
  'chip-programming': 'chip-programming',
  'new-arrivals':  'new-arrivals',
  'request-a-quote': 'request-a-quote',
  checkout:        'checkout',
  about:           'about',
  profile:         'profile',
  'my-orders':     'my-orders',
  'delivery-address': 'delivery-address',
  'change-password': 'change-password',
  contact:         'about#contact',
  quote:           'request-a-quote',
  services:        'chip-programming',
};

function goTo(page, opts = {}) {
  const base = PAGE_MAP[page] || 'index';
  const params = new URLSearchParams();
  if (opts.cat) params.set('cat', opts.cat);
  if (opts.subcat) params.set('subcat', opts.subcat);
  if (opts.mfr) params.set('mfr', opts.mfr);
  if (opts.q)   params.set('q',   opts.q);
  const qs = params.toString();
  showPageLoader('Loading...');
  window.location.href = base + (qs ? '?' + qs : '');
}

function openPDP(id) {
  addRecentlyViewed(id);
  showPageLoader('Loading product...');
  window.location.href = 'product?id=' + id;
}

/* ── SVG icon helper ─────────────────────────────────────────── */
const SVG = {
  cart:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>`,
  heart:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>`,
  eye:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`,
  check:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>`,
  x:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,
  chevR:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>`,
  chevL:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>`,
  chevD:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>`,
  star:`<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`,
  shield:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>`,
  truck:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>`,
  file:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>`,
  compare:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>`,
  tag:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>`,
  trash:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>`,
  home:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>`,
  grid:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>`,
  list:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>`,
  filter:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>`,
  menu:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>`,
  user:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
  code:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 7l5 5-5 5M7 7l-5 5 5 5M14 3l-4 18"/></svg>`,
  cpu:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg>`,
  zap:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>`,
  radio:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 010 8.49m-8.48-.01a6 6 0 010-8.49m11.31-2.82a10 10 0 010 14.14m-14.14 0a10 10 0 010-14.14"/></svg>`,
  wifi:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M5 12.55a11 11 0 0114.08 0M1.42 9a16 16 0 0121.16 0M8.53 16.11a6 6 0 016.95 0M12 20h.01"/></svg>`,
  db:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>`,
  monitor:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>`,
  tri:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`,
  minus_c:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>`,
  send:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>`,
  info:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
  pin:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>`,
};

function ic(name, w = 18, h = 18) {
  const s = SVG[name] || SVG.info;
  return s.replace('<svg ', `<svg width="${w}" height="${h}" `);
}

/* ── Common Toast ────────────────────────────────────────────── */
function showToastMessage(msg, status = 'pass') {
  const c = document.getElementById('toastWrap');
  if (!c || !msg) return;
  const normalizedStatus = String(status || 'pass').trim().toLowerCase();
  const statusMap = {
    ok: 'pass',
    pass: 'pass',
    success: 'pass',
    err: 'fail',
    error: 'fail',
    fail: 'fail',
    warning: 'warn',
    warn: 'warn',
  };
  const tone = statusMap[normalizedStatus] || 'pass';
  const t = document.createElement('div');
  t.className = `toast toast--${tone}`;
  t.setAttribute('role', 'status');
  t.setAttribute('aria-live', 'polite');

  const icon = tone === 'pass'
    ? SVG.check
    : tone === 'fail'
      ? SVG.x
      : ic('info', 16, 16);

  t.innerHTML = `
    <span class="toast-icon" aria-hidden="true">${icon}</span>
    <span class="toast-text">${msg}</span>
    <button type="button" class="toast-close" aria-label="Close notification">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
  `;

  let hideTimer = null;
  const removeToast = () => {
    if (!t.parentNode) return;
    t.classList.add('is-leaving');
    window.setTimeout(() => t.remove(), 220);
  };

  t.querySelector('.toast-close')?.addEventListener('click', removeToast);
  c.appendChild(t);
  hideTimer = window.setTimeout(removeToast, 5000);
  t.addEventListener('mouseenter', () => window.clearTimeout(hideTimer));
  t.addEventListener('mouseleave', () => {
    hideTimer = window.setTimeout(removeToast, 1800);
  });
}
window.showToastMessage = showToastMessage;
window.showAppToast = showToastMessage;
window.toast = showToastMessage;
const toast = showToastMessage;

/* ── Global Loader ──────────────────────────────────────────── */
function showPageLoader(message = 'Please wait...') {
  const loader = document.getElementById('globalPageLoader');
  if (!loader) return;
  loader.classList.add('is-visible');
  loader.setAttribute('aria-hidden', 'false');
}

function hidePageLoader() {
  const loader = document.getElementById('globalPageLoader');
  if (!loader) return;
  loader.classList.remove('is-visible');
  loader.setAttribute('aria-hidden', 'true');
}

window.showPageLoader = showPageLoader;
window.hidePageLoader = hidePageLoader;

function initGlobalLoader() {
  hidePageLoader();

  document.addEventListener('submit', e => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.loader === 'off') return;
    if (e.defaultPrevented) return;
    if (typeof form.checkValidity === 'function' && !form.checkValidity()) return;

    showPageLoader(form.dataset.loaderText || 'Submitting...');
  });

  document.addEventListener('click', e => {
    const anchor = e.target.closest('a[href]');
    if (!(anchor instanceof HTMLAnchorElement)) return;
    if (anchor.dataset.loader === 'off') return;
    if (e.defaultPrevented) return;
    if (e.button !== 0) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (anchor.target && anchor.target !== '_self') return;
    if (anchor.hasAttribute('download')) return;

    const href = (anchor.getAttribute('href') || '').trim();
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
    if (href.startsWith('mailto:') || href.startsWith('tel:')) return;

    showPageLoader(anchor.dataset.loaderText || 'Loading...');
  });
}

/* ── Stars ───────────────────────────────────────────────────── */
function stars(r, size = 13) {
  let h = '<div class="pcard-stars">';
  for (let i = 1; i <= 5; i++) {
    const cls = i <= Math.floor(r) ? 'star-filled' : (i - 0.5 <= r ? 'star-half' : 'star-empty');
    h += `<svg class="${cls}" width="${size}" height="${size}" viewBox="0 0 24 24" fill="${cls === 'star-empty' ? 'none' : 'currentColor'}" stroke="${cls === 'star-empty' ? 'currentColor' : 'none'}" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`;
  }
  return h + '</div>';
}

/* ── Stock helpers ───────────────────────────────────────────── */
function stockLabel(s) {
  if (s > 100) return `<span class="pcard-stock in-stock">In Stock</span>`;
  if (s > 0)   return `<span class="pcard-stock low-stock">Only ${s} left — order soon</span>`;
  return `<span class="pcard-stock out-stock">Currently unavailable</span>`;
}
function stockClass(s) { return s > 100 ? 'in-stock' : s > 0 ? 'low-stock' : 'out-stock'; }

/* ── Product image placeholders ──────────────────────────────────
 *  No external dependency. Two variants:
 *   _pImgFail(img)  — pcard / deal cards: beautiful overlay panel
 *   _IMG_PH         — small contexts (cart, checkout, quote…)
 * ─────────────────────────────────────────────────────────────── */
const _IMG_PH = (() => {
  const s = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
  <rect width="80" height="80" rx="8" fill="#ffffff"/>
  <rect width="80" height="80" rx="8" fill="none" stroke="#e5e7eb" stroke-width="1"/>
  <g transform="translate(21,21)" fill="none" stroke="#d1d5db" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <rect x="6" y="6" width="26" height="26" rx="2"/>
    <line x1="6" y1="12" x2="2" y2="12"/><line x1="6" y1="19" x2="0" y2="19"/><line x1="6" y1="26" x2="2" y2="26"/>
    <line x1="32" y1="12" x2="36" y2="12"/><line x1="32" y1="19" x2="38" y2="19"/><line x1="32" y1="26" x2="36" y2="26"/>
    <line x1="12" y1="6" x2="12" y2="2"/><line x1="19" y1="6" x2="19" y2="0"/><line x1="26" y1="6" x2="26" y2="2"/>
    <line x1="12" y1="32" x2="12" y2="36"/><line x1="19" y1="32" x2="19" y2="38"/><line x1="26" y1="32" x2="26" y2="36"/>
    <rect x="13" y="13" width="12" height="12" rx="1" fill="#f3f4f6"/>
  </g>
</svg>`;
  return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(s);
})();

function _pImgFail(img) {
  const wrap = img.parentElement;
  if (!wrap) { img.style.display = 'none'; return; }

  const isLarge = wrap.classList.contains('pcard-img-wrap')
               || wrap.classList.contains('deal-card-img')
               || wrap.classList.contains('fdeal-img');

  img.style.display = 'none';

  if (!isLarge) {
    /* Small inline context — just swap to embedded SVG */
    img.onerror = null;
    img.style.display = '';
    img.src = _IMG_PH;
    return;
  }

  /* Large card — build a rich overlay */
  const sku = (img.dataset.sku || '').trim();
  const ph  = document.createElement('div');
  ph.className = 'pcard-img-ph';
  ph.innerHTML =
    `<div class="pcard-img-ph-inner">`
  + `<svg viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg" class="pcard-img-ph-icon">`
  + `<rect x="9" y="9" width="20" height="20" rx="2" stroke="currentColor" stroke-width="1.6"/>`
  + `<line x1="9" y1="14.5" x2="5" y2="14.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`
  + `<line x1="9" y1="19" x2="3" y2="19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`
  + `<line x1="9" y1="23.5" x2="5" y2="23.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`
  + `<line x1="29" y1="14.5" x2="33" y2="14.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`
  + `<line x1="29" y1="19" x2="35" y2="19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`
  + `<line x1="29" y1="23.5" x2="33" y2="23.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`
  + `<line x1="14.5" y1="9" x2="14.5" y2="5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`
  + `<line x1="19" y1="9" x2="19" y2="3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`
  + `<line x1="23.5" y1="9" x2="23.5" y2="5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`
  + `<line x1="14.5" y1="29" x2="14.5" y2="33" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`
  + `<line x1="19" y1="29" x2="19" y2="35" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`
  + `<line x1="23.5" y1="29" x2="23.5" y2="33" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`
  + `<rect x="15" y="15" width="8" height="8" rx="1" fill="currentColor" fill-opacity=".15"/>`
  + `</svg>`
  + (sku ? `<span class="pcard-img-ph-sku">${sku}</span>` : '')
  + `</div>`;
  wrap.insertBefore(ph, img);
}

/* ── Discount ────────────────────────────────────────────────── */
function disc(price, orig) {
  if (!orig || orig <= price) return null;
  return Math.round((1 - price / orig) * 100);
}

/* ── Delivery estimate ───────────────────────────────────────── */
function delivEst() {
  const d = new Date();
  d.setDate(d.getDate() + 2);
  return d.toLocaleDateString('en-IN', { weekday: 'short', month: 'short', day: 'numeric' });
}

/* ── Catalog DB data (loaded async from server) ──────────────── */
const CATALOG_DB = { categories: [], manufacturers: [], loaded: false };

/* ── Catalog AJAX base path ───────────────────────────────────── */
function _catAjaxBase() {
  const p = window.location.pathname;
  if (p.indexOf('/website/') !== -1) return 'ajax/catalog';
  if (p.indexOf('/admin/')   !== -1) return '../website/ajax/catalog';
  return 'website/ajax/catalog';
}

/* ── Load categories + manufacturers from server ─────────────── */
async function loadCatalogInit() {
  if (CATALOG_DB.loaded) return;
  try {
    const r    = await fetch(`${_catAjaxBase()}?action=init`);
    const data = await r.json();
    if (!data.ok) return;
    CATALOG_DB.categories    = data.categories    || [];
    CATALOG_DB.manufacturers = data.manufacturers || [];
    CATALOG_DB.loaded        = true;
  } catch(e) { console.error('loadCatalogInit:', e); }
}

/* ── State ───────────────────────────────────────────────────── */
const S = {
  catId:        CATALOG_INIT.catId  || 0,           /* DB integer category id  */
  catIds:       CATALOG_INIT.catIds || '',           /* comma-separated IDs from manufacturer link */
  catFilter:    CATALOG_INIT.cat    || null,         /* legacy slug (kept for breadcrumb) */
  subcatFilter: CATALOG_INIT.subcat || null,
  mfrFilter:    CATALOG_INIT.mfr    || null,
  query:        CATALOG_INIT.q      || '',
  expandedCats: [],
  filters: {
    mfrs:        CATALOG_INIT.mfr ? [CATALOG_INIT.mfr] : [],
    minP:        0,
    maxP:        0,
    inStock:     false,
    isNew:       CATALOG_INIT.isNew || false,
    minRating:   0,
    filterQuery: '',
  },
  sort:    'featured',
  view:    'grid',
  page:    1,
  perPage: 16,
  pdpId:   CURRENT_PRODUCT ? CURRENT_PRODUCT.id : null,
  heroIdx: 0,
  heroTimer: null,
  compareIds: [],
  wishIds:        [],   /* populated from server for logged-in users */
  recentlyViewed: JSON.parse(localStorage.getItem('sinelec_rv')   || '[]'),
};

function saveRV()   { localStorage.setItem('sinelec_rv',   JSON.stringify(S.recentlyViewed)); }

/* ── Product registry: populated as products are fetched/loaded ── */
const _productRegistry = {};
function _registerProducts(list) { list.forEach(p => { if (p?.id) _productRegistry[p.id] = p; }); }
function _getProduct(id) { return _productRegistry[id] || null; }
if (CURRENT_PRODUCT) _registerProducts([CURRENT_PRODUCT]);

/* ── Cart state ──────────────────────────────────────────────── */
const cartItems = JSON.parse(localStorage.getItem('sinelec_cart') || '[]');

function saveCart() {
  localStorage.setItem('sinelec_cart', JSON.stringify(cartItems));
  updateCartUI();
}
function cartAdd(product, qty = 1) {
  const price = product.priceBreaks ? product.priceBreaks[0].price : product.price;
  const ex = cartItems.find(i => i.id === product.id);
  if (ex) { ex.qty += qty; } else { cartItems.push({ id: product.id, name: product.name, sku: product.sku, image: product.image, price, qty }); }
  saveCart();
  toast(`"${product.name.substring(0, 35)}…" added to cart`, 'ok');
}
function cartRemove(id) {
  const idx = cartItems.findIndex(i => i.id === id);
  if (idx > -1) cartItems.splice(idx, 1);
  saveCart();
}
function cartQty(id, qty) {
  if (qty <= 0) { cartRemove(id); return; }
  const item = cartItems.find(i => i.id === id);
  if (item) item.qty = qty;
  saveCart();
}
function cartCount()    { return cartItems.reduce((s, i) => s + i.qty, 0); }
function cartSubtotal() { return cartItems.reduce((s, i) => s + i.price * i.qty, 0); }

function updateCartUI() {
  const cnt = cartCount();
  document.querySelectorAll('.cart-count').forEach(el => {
    el.textContent = cnt;
    el.classList.toggle('cart-count-initial', cnt <= 0);
    el.style.display = cnt > 0 ? '' : 'none';
  });
  const wrap = document.getElementById('cartItemsWrap');
  if (!wrap) return;
  if (cartItems.length === 0) {
    wrap.innerHTML = `<div class="cart-empty-state">${ic('cart', 56, 56)}<p>Your cart is empty</p><small>Browse products and add items here</small><a class="btn btn-blue" href="products" style="margin-top:14px">Shop Now</a></div>`;
  } else {
    wrap.innerHTML = cartItems.map(item => `
      <div class="cart-item">
        <img class="cart-item-img" src="${item.image}" alt="${item.name}" onerror="_pImgFail(this)">
        <div class="cart-item-info">
          <div class="cart-item-sku">${item.sku}</div>
          <div class="cart-item-name">${item.name}</div>
          <div class="cart-item-actions">
            <div class="ci-qty-ctrl">
              <button class="ci-qty-btn" onclick="cartQty(${item.id},${item.qty - 1})">−</button>
              <span class="ci-qty-num">${item.qty}</span>
              <button class="ci-qty-btn" onclick="cartQty(${item.id},${item.qty + 1})">+</button>
            </div>
            <span class="ci-del" onclick="cartRemove(${item.id})">Delete</span>
          </div>
        </div>
        <div class="cart-item-price">€${(item.price * item.qty).toFixed(2)}</div>
      </div>`).join('');
  }
  const sub  = cartSubtotal();
  const setT = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  setT('cartSub',   `€${sub.toFixed(2)}`);
  setT('cartTotal', `€${sub.toFixed(2)}`);
}

function openCart() {
  document.getElementById('cartPanel').classList.add('on');
  document.getElementById('cartOverlay').classList.add('on');
  document.body.style.overflow = 'hidden';
}
function closeCart() {
  document.getElementById('cartPanel').classList.remove('on');
  document.getElementById('cartOverlay').classList.remove('on');
  document.body.style.overflow = '';
}

/* ── Auth-guarded checkout ───────────────────────────────────── */
function cartCheckout() {
  if (cartItems.length === 0) {
    toast('Your cart is empty. Add products before checking out.', 'warn');
    return;
  }
  if (window.SINELEC_AUTH?.isSignedIn) {
    closeCart();
    showPageLoader('Redirecting to checkout…');
    window.location.href = 'checkout';
  } else {
    /* Set redirect target so login bounces to checkout */
    const redir = document.getElementById('authRedirect');
    if (redir) redir.value = 'checkout';
    /* Update auth modal messaging */
    const title = document.getElementById('authModalTitle');
    const desc  = document.getElementById('authModalDesc');
    if (title) title.textContent = 'Sign in to Checkout';
    if (desc)  desc.textContent  = 'Please sign in or create an account to complete your purchase.';
    closeCart();
    if (window.sinelecOpenAuth) window.sinelecOpenAuth('signin');
  }
}

function initCheckoutPage() {
  const page = document.getElementById('checkoutPage');
  if (!page) return;

  const addressGrid = document.getElementById('checkoutAddressGrid');
  const addressFormWrap = document.getElementById('checkoutAddressFormWrap');
  const toggleAddressBtn = document.getElementById('checkoutToggleAddressBtn');
  const addressCancelBtn = document.getElementById('checkoutAddressCancelBtn');
  const addressForm = document.getElementById('checkoutAddressForm');
  const emptyState = document.getElementById('checkoutEmptyState');
  const summaryContent = document.getElementById('checkoutSummaryContent');
  const summaryItems = document.getElementById('checkoutSummaryItems');
  const productSubText = document.getElementById('checkoutProductSub');
  const selectedAddressText = document.getElementById('checkoutSelectedAddressText');
  const changeAddressBtn = document.getElementById('checkoutChangeAddressBtn');
  const shippingText = document.getElementById('checkoutShippingText');
  const paymentText = document.getElementById('checkoutPaymentText');
  const subtotalEl = document.getElementById('checkoutSubtotal');
  const shippingCostEl = document.getElementById('checkoutShippingCost');
  const taxEl = document.getElementById('checkoutTax');
  const totalEl = document.getElementById('checkoutTotal');
  const placeBtn = document.getElementById('checkoutPlaceBtn');
  const invoiceEmail = document.getElementById('checkoutInvoiceEmail');
  const euVatInput = document.getElementById('checkoutEuVat');
  const vatApplyBtn = document.getElementById('checkoutVatApplyBtn');
  const vatState = document.getElementById('checkoutVatState');

  const ADDRESS_KEY = 'sinelec_checkout_addresses';
  const SELECTED_KEY = 'sinelec_checkout_selected_address';
  const savedDelivery = localStorage.getItem(DELIVERY_KEY) || 'Delhi 110001';
  const defaultAddresses = [
    { id: 'office', label: 'Office', name: 'Purchase Desk', phone: '+91 98765 43210', line: `${savedDelivery}, India`, badge: 'Default' },
    { id: 'warehouse', label: 'Warehouse', name: 'Stores Team', phone: '+91 91234 56789', line: 'Noida Sector 62, Uttar Pradesh 201309, India', badge: 'Business' },
  ];

  let addresses = [];
  try {
    addresses = JSON.parse(localStorage.getItem(ADDRESS_KEY) || '[]');
  } catch {}
  if (!Array.isArray(addresses) || addresses.length === 0) {
    addresses = defaultAddresses;
    localStorage.setItem(ADDRESS_KEY, JSON.stringify(addresses));
  }

  let selectedAddressId = localStorage.getItem(SELECTED_KEY) || addresses[0]?.id || '';
  let selectedCheckoutIds = new Set(cartItems.map(item => item.id));
  let vatApplied = false;

  function normalizeSelectedCheckoutIds() {
    const currentIds = new Set(cartItems.map(item => item.id));
    selectedCheckoutIds = new Set([...selectedCheckoutIds].filter(id => currentIds.has(id)));
  }

  function getSelectedCheckoutItems() {
    return cartItems.filter(item => selectedCheckoutIds.has(item.id));
  }

  function selectedSubtotal() {
    return getSelectedCheckoutItems().reduce((sum, item) => sum + (item.price * item.qty), 0);
  }

  function syncSelectedAddressFromStorage() {
    const stored = localStorage.getItem(SELECTED_KEY) || '';
    if (stored && addresses.some(address => address.id === stored)) {
      selectedAddressId = stored;
    }
  }

  function saveAddresses() {
    localStorage.setItem(ADDRESS_KEY, JSON.stringify(addresses));
    localStorage.setItem(SELECTED_KEY, selectedAddressId);
  }

  function getSelectedAddress() {
    return addresses.find(address => address.id === selectedAddressId) || addresses[0] || null;
  }

  function currentShippingMode() {
    return document.querySelector('input[name="checkoutShipping"]:checked')?.value || 'standard';
  }

  function currentPaymentMode() {
    return document.querySelector('input[name="checkoutPayment"]:checked')?.value || 'paypal';
  }

  function shippingCharge(subtotal) {
    return currentShippingMode() === 'priority'
      ? 199
      : (subtotal >= 5000 ? 0 : 99);
  }

  function renderAddresses() {
    syncSelectedAddressFromStorage();
    if (!addressGrid) return;
    addressGrid.innerHTML = addresses.map(address => `
      <article class="checkout-address-card ${address.id === selectedAddressId ? 'is-selected' : ''}" data-address-id="${address.id}">
        <div class="checkout-address-top">
          <div class="checkout-address-label">${address.label}</div>
          <div class="checkout-address-badge">${address.badge || 'Saved'}</div>
        </div>
        <div class="checkout-address-name">${address.name}</div>
        <div class="checkout-address-phone">${address.phone}</div>
        <div class="checkout-address-line">${address.line}</div>
      </article>
    `).join('');

    addressGrid.querySelectorAll('.checkout-address-card').forEach(card => {
      card.addEventListener('click', () => {
        selectedAddressId = card.dataset.addressId || selectedAddressId;
        saveAddresses();
        renderAddresses();
        renderSummary();
      });
    });
  }

  function renderSummary() {
    syncSelectedAddressFromStorage();
    normalizeSelectedCheckoutIds();
    const selectedItems = getSelectedCheckoutItems();
    const sub   = selectedSubtotal();
    const ship  = 0;
    const gst   = 0;
    const total = sub;
    const selectedAddress = getSelectedAddress();
    const shippingLabel = currentShippingMode() === 'priority' ? 'Priority Dispatch' : 'Standard Delivery';
    const paymentMap = {
      paypal: 'PayPal',
      card_paypal: 'Credit Card via Paypal (No Paypal Account needed)',
      bank: 'Bank Transfer',
      invoice: 'Invoice (Corporate customers)',
    };

    if (cartItems.length === 0) {
      emptyState?.classList.remove('hidden');
      summaryContent?.classList.add('hidden');
      placeBtn && (placeBtn.disabled = true);
      if (productSubText) {
        productSubText.textContent = 'No items in cart.';
      }
      return;
    }

    emptyState?.classList.add('hidden');
    summaryContent?.classList.remove('hidden');
    if (placeBtn) placeBtn.disabled = selectedItems.length === 0;
    if (productSubText) {
      productSubText.textContent = `${selectedItems.length} of ${cartItems.length} item(s) selected for checkout.`;
    }

    if (summaryItems) {
      summaryItems.innerHTML = cartItems.map(item => `
        <div class="checkout-item">
          <img src="${item.image}" alt="${item.name}" onerror="_pImgFail(this)">
          <div>
            <div class="checkout-item-head">
              <div class="checkout-item-left">
                <input type="checkbox" class="checkout-item-select" data-checkout-select="${item.id}" ${selectedCheckoutIds.has(item.id) ? 'checked' : ''}>
                <div class="checkout-item-sku">${item.sku}</div>
              </div>
              <button type="button" class="checkout-item-remove" data-checkout-remove="${item.id}" aria-label="Remove product">${ic('trash', 14, 14)}</button>
            </div>
            <div class="checkout-item-name">${item.name}</div>
            <div class="checkout-item-meta">Unit: €${item.price.toFixed(2)}</div>
            <div class="checkout-item-controls">
              <button type="button" class="checkout-qty-btn" data-checkout-qty="${item.id}" data-delta="-1">−</button>
              <span class="checkout-qty-num">${item.qty}</span>
              <button type="button" class="checkout-qty-btn" data-checkout-qty="${item.id}" data-delta="1">+</button>
            </div>
          </div>
          <div class="checkout-item-price">€${(item.qty * item.price).toFixed(2)}</div>
        </div>
      `).join('');
    }

    if (selectedAddressText) selectedAddressText.textContent = selectedAddress ? `${selectedAddress.label} · ${selectedAddress.line}` : 'Select an address';
    if (shippingText) shippingText.textContent = shippingLabel;
    if (paymentText) paymentText.textContent = paymentMap[currentPaymentMode()] || 'PayPal';
    if (subtotalEl) subtotalEl.textContent = `€${sub.toFixed(2)}`;
    if (shippingCostEl) shippingCostEl.textContent = '—';
    if (taxEl) taxEl.textContent = '—';
    if (totalEl) totalEl.textContent = `€${sub.toFixed(2)}`;
    if (vatState) {
      vatState.classList.toggle('hidden', !vatApplied);
    }
  }

  toggleAddressBtn?.addEventListener('click', () => {
    addressFormWrap?.classList.toggle('hidden');
  });

  addressCancelBtn?.addEventListener('click', () => {
    addressFormWrap?.classList.add('hidden');
    addressForm?.reset();
  });

  addressForm?.addEventListener('submit', e => {
    e.preventDefault();
    const label = document.getElementById('checkoutAddrLabel')?.value.trim();
    const name = document.getElementById('checkoutAddrName')?.value.trim();
    const phone = document.getElementById('checkoutAddrPhone')?.value.trim();
    const pin = document.getElementById('checkoutAddrPin')?.value.trim();
    const line = document.getElementById('checkoutAddrLine')?.value.trim();
    if (!label || !name || !phone || !pin || !line) {
      toast('Please fill all address fields.', 'warn');
      return;
    }
    const address = {
      id: `addr_${Date.now()}`,
      label,
      name,
      phone,
      line: `${line}${pin ? `, ${pin}` : ''}`,
      badge: 'New',
    };
    addresses.unshift(address);
    selectedAddressId = address.id;
    setDeliveryLocation(address.line);
    saveAddresses();
    renderAddresses();
    renderSummary();
    addressForm.reset();
    addressFormWrap?.classList.add('hidden');
    toast('Address saved for this checkout.', 'ok');
  });

  document.querySelectorAll('input[name="checkoutShipping"], input[name="checkoutPayment"]').forEach(input => {
    input.addEventListener('change', renderSummary);
  });

  summaryItems?.addEventListener('click', e => {
    const qtyBtn = e.target.closest('[data-checkout-qty]');
    if (qtyBtn) {
      const id = Number(qtyBtn.getAttribute('data-checkout-qty') || 0);
      const delta = Number(qtyBtn.getAttribute('data-delta') || 0);
      const item = cartItems.find(entry => entry.id === id);
      if (item && delta !== 0) {
        const nextQty = item.qty + delta;
        cartQty(id, nextQty);
        if (nextQty <= 0) {
          selectedCheckoutIds.delete(id);
        }
        renderSummary();
      }
      return;
    }

    const removeBtn = e.target.closest('[data-checkout-remove]');
    if (removeBtn) {
      const id = Number(removeBtn.getAttribute('data-checkout-remove') || 0);
      selectedCheckoutIds.delete(id);
      cartRemove(id);
      renderSummary();
      return;
    }
  });

  summaryItems?.addEventListener('change', e => {
    const check = e.target;
    if (!(check instanceof HTMLInputElement)) return;
    if (!check.matches('[data-checkout-select]')) return;
    const id = Number(check.getAttribute('data-checkout-select') || 0);
    if (!id) return;
    if (check.checked) {
      selectedCheckoutIds.add(id);
    } else {
      selectedCheckoutIds.delete(id);
    }
    renderSummary();
  });

  changeAddressBtn?.addEventListener('click', () => {
    const headerDeliveryBtn = document.getElementById('headerDeliveryBtn');
    if (headerDeliveryBtn) {
      headerDeliveryBtn.click();
      setTimeout(() => {
        syncSelectedAddressFromStorage();
        renderSummary();
      }, 140);
    }
  });

  vatApplyBtn?.addEventListener('click', () => {
    const vatText = euVatInput?.value.trim() || '';
    if (!vatText) {
      vatApplied = false;
      vatState?.classList.add('hidden');
      toast('Please enter EU VAT number.', 'warn');
      renderSummary();
      return;
    }
    vatApplied = true;
    vatState?.classList.remove('hidden');
    toast('EU VAT applied successfully.', 'ok');
    renderSummary();
  });

  placeBtn?.addEventListener('click', () => {
    const selectedItems = getSelectedCheckoutItems();
    if (selectedItems.length === 0) {
      toast('Please select at least one product to place the order.', 'warn');
      return;
    }
    if (cartItems.length === 0) {
      toast('Your cart is empty.', 'warn');
      return;
    }
    if (!getSelectedAddress()) {
      toast('Please select a delivery address.', 'warn');
      return;
    }
    if (invoiceEmail && !invoiceEmail.value.trim()) {
      invoiceEmail.value = 'contact@sinelec-tech.com';
    }
    const selectedIds = new Set(selectedItems.map(item => item.id));
    for (let i = cartItems.length - 1; i >= 0; i -= 1) {
      if (selectedIds.has(cartItems[i].id)) {
        cartItems.splice(i, 1);
      }
    }
    selectedCheckoutIds.clear();
    saveCart();
    renderSummary();
    toast('Order placed successfully! Our team will contact you with confirmation details.', 'ok');
  });

  renderAddresses();
  renderSummary();
}

/* ── Wishlist ────────────────────────────────────────────────── */
function _wishAjaxBase() {
  const p = window.location.pathname;
  if (p.indexOf('/website/') !== -1) return 'ajax/wishlist';
  if (p.indexOf('/admin/')   !== -1) return '../website/ajax/wishlist';
  return 'website/ajax/wishlist';
}

function _updateWishUI(id) {
  const wished = S.wishIds.includes(id);
  document.querySelectorAll(`.pcard-wish[data-id="${id}"], #pdpWishBtn[data-id="${id}"], [data-wish-id="${id}"]`)
    .forEach(b => {
      b.classList.toggle('wished', wished);
      b.title = wished ? 'Remove from wishlist' : 'Add to wishlist';
      const svg = b.querySelector('svg');
      if (svg) svg.setAttribute('fill', wished ? 'currentColor' : 'none');
    });
}

function toggleWish(id) {
  /* Not logged in → save pending, open auth modal */
  if (!window.SINELEC_AUTH?.isSignedIn) {
    sessionStorage.setItem('sinelec_pending_wish', String(id));
    const authModal = document.getElementById('authModal');
    if (authModal) {
      authModal.removeAttribute('hidden');
      document.getElementById('authSignInPanel')?.classList.add('is-active');
      document.getElementById('authSignUpPanel')?.classList.remove('is-active');
    }
    toast('Please sign in to save to your wishlist', 'warn');
    return;
  }

  /* Optimistic UI update */
  const idx = S.wishIds.indexOf(id);
  if (idx > -1) S.wishIds.splice(idx, 1); else S.wishIds.push(id);
  _updateWishUI(id);

  /* AJAX persist */
  const fd = new FormData();
  fd.append('action', 'toggle');
  fd.append('product_id', id);
  fetch(_wishAjaxBase(), { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.ok) {
        /* Rollback optimistic update */
        const ri = S.wishIds.indexOf(id);
        if (ri > -1) S.wishIds.splice(ri, 1); else S.wishIds.push(id);
        _updateWishUI(id);
        toast('Could not update wishlist', 'err');
        return;
      }
      const nowWished = data.state === 'added';
      if (nowWished && !S.wishIds.includes(id)) S.wishIds.push(id);
      if (!nowWished) { const ri = S.wishIds.indexOf(id); if (ri > -1) S.wishIds.splice(ri, 1); }
      _updateWishUI(id);
      toast(nowWished ? 'Saved to wishlist ♥' : 'Removed from wishlist', nowWished ? 'ok' : 'warn');
    })
    .catch(() => toast('Could not update wishlist', 'err'));
}

/* Load wishlist IDs from server on page load (logged-in users only) */
function _loadWishlistFromServer() {
  if (!window.SINELEC_AUTH?.isSignedIn) return;
  fetch(_wishAjaxBase() + '?action=get')
    .then(r => r.json())
    .then(data => {
      if (data.ok && Array.isArray(data.ids)) {
        S.wishIds = data.ids;
        /* Refresh all heart icons on the page */
        document.querySelectorAll('.pcard-wish[data-id]').forEach(b => {
          const id = parseInt(b.dataset.id, 10);
          if (id) _updateWishUI(id);
        });
        if (CURRENT_PRODUCT?.id) _updateWishUI(CURRENT_PRODUCT.id);
      }
    })
    .catch(() => {});
}

/* After login: process any pending wish stored before auth */
function _processPendingWish() {
  if (!window.SINELEC_AUTH?.isSignedIn) return;
  const pending = sessionStorage.getItem('sinelec_pending_wish');
  if (!pending) return;
  sessionStorage.removeItem('sinelec_pending_wish');
  const id = parseInt(pending, 10);
  if (id > 0 && !S.wishIds.includes(id)) toggleWish(id);
}

/* ── Compare ─────────────────────────────────────────────────── */
function toggleCompare(id) {
  const p = _getProduct(id);
  if (!p) return;
  const idx = S.compareIds.indexOf(id);
  if (idx > -1) { S.compareIds.splice(idx, 1); }
  else {
    if (S.compareIds.length >= 3) { toast('Max 3 products can be compared', 'warn'); return; }
    S.compareIds.push(id);
  }
  renderCompareBar();
}
function clearCompare() { S.compareIds = []; renderCompareBar(); }

function renderCompareBar() {
  const bar = document.getElementById('compareBar');
  if (!bar) return;
  bar.classList.toggle('show', S.compareIds.length > 0);
  const slots = document.getElementById('compareSlots');
  if (!slots) return;
  slots.innerHTML = [0, 1, 2].map(i => {
    const p = S.compareIds[i] ? _getProduct(S.compareIds[i]) : null;
    return p
      ? `<div class="compare-slot"><img src="${p.image}" alt="${p.name}"><button class="compare-remove" onclick="toggleCompare(${p.id})">${ic('x', 9, 9)}</button></div>`
      : `<div class="compare-slot">＋ Add</div>`;
  }).join('');
}

/* ── Recently Viewed ─────────────────────────────────────────── */
function addRecentlyViewed(id) {
  S.recentlyViewed = [id, ...S.recentlyViewed.filter(x => x !== id)].slice(0, 8);
  saveRV();
}

/* ── Product card renderer ───────────────────────────────────── */
function pCard(p, variant = 'default') {
  const price        = p.priceBreaks ? p.priceBreaks[0].price : (p.price || 0);
  const categoryName = p.categoryName || CATEGORY_NAME_MAP[p.category] || '';
  const primaryBadge = p.isNew ? 'new' : (p.badge || '');
  const badgeTextMap = {
    new: 'New', popular: 'Popular', bestseller: 'Best Seller',
    featured: 'Featured', hot: 'Hot', sale: 'Sale',
  };
  const badgeText = primaryBadge ? (badgeTextMap[primaryBadge] || String(primaryBadge)) : '';
  const wished    = S.wishIds.includes(p.id);

  /* Minimal variant — image + category + name only, no price/buttons */
  if (variant === 'minimal') {
    return `
  <div class="pcard pcard-minimal" data-id="${p.id}" onclick="openPDP(${p.id})" style="cursor:pointer;">
    <div class="pcard-img-wrap">
      <img class="pcard-img" src="${p.image}" alt="${p.name}" loading="lazy"
           data-sku="${p.sku}" onerror="_pImgFail(this)">
      <div class="pcard-badges">
        ${primaryBadge ? `<span class="pbadge pbadge-${primaryBadge}">${badgeText}</span>` : ''}
      </div>
    </div>
    <div class="pcard-body">
      ${categoryName ? `<div class="pcard-cat">${categoryName}</div>` : ''}
      <h3 class="pcard-name">${p.name}</h3>
    </div>
  </div>`;
  }

  const eurPrice   = EUR_FORMAT.format(price);
  const eurOrig    = p.originalPrice ? EUR_FORMAT.format(p.originalPrice) : '';
  const reviewsCnt = p.reviewCount || p.reviews || 0;

  return `
  <div class="pcard" data-id="${p.id}">
    <!-- Image area -->
    <div class="pcard-img-wrap" onclick="openPDP(${p.id})">
      <img class="pcard-img" src="${p.image}" alt="${p.name}" loading="lazy"
           data-sku="${p.sku}" onerror="_pImgFail(this)">
      <div class="pcard-badges">
        ${primaryBadge ? `<span class="pbadge pbadge-${primaryBadge}">${badgeText}</span>` : ''}
      </div>
      <button class="pcard-wish ${wished ? 'wished' : ''}" data-id="${p.id}"
              onclick="event.stopPropagation();toggleWish(${p.id})"
              title="${wished ? 'Remove from wishlist' : 'Add to wishlist'}">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="${wished ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="1.75"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
      </button>
    </div>

    <!-- Body -->
    <div class="pcard-body" onclick="openPDP(${p.id})">
      ${categoryName ? `<div class="pcard-cat">${categoryName}</div>` : ''}
      <h3 class="pcard-name">${p.name}</h3>
      ${p.sku ? `<div class="pcard-sku">${p.sku}</div>` : ''}
      <div class="pcard-meta-row">
        ${p.rating > 0 ? `
        <div class="pcard-rating">
          ${stars(p.rating)}
          <span class="pcard-rating-val">${p.rating.toFixed(1)}</span>
          ${reviewsCnt > 0 ? `<span class="pcard-rc">(${reviewsCnt.toLocaleString()})</span>` : ''}
        </div>` : ''}
      </div>
      <div class="pcard-price-row">
        <span class="price-main">${eurPrice}</span>
        ${eurOrig ? `<span class="price-orig">${eurOrig}</span>` : ''}
      </div>
      <div class="pcard-delivery">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        Delivery in <strong>7 days</strong>
      </div>
    </div>

    <!-- Footer buttons -->
    <div class="pcard-footer">
      <button class="btn-atc" onclick="atcClick(event,${p.id})">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
        Add to Cart
      </button>
      <button class="btn-buynow" onclick="buyNowClick(event,${p.id})">Buy Now</button>
    </div>
  </div>`;
}

function atcClick(e, id) {
  e.stopPropagation();
  const p = _getProduct(id);
  if (!p) { toast('Product not found', 'warn'); return; }
  cartAdd(p, 1);
  const btn = e.currentTarget;
  const orig = btn.innerHTML;
  btn.classList.add('added');
  btn.disabled = true;
  btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Added!`;
  setTimeout(() => { btn.classList.remove('added'); btn.disabled = false; btn.innerHTML = orig; }, 1800);
}

/* Add to cart + open cart panel (used on product cards) */
function buyNowClick(e, id) {
  e.stopPropagation();
  const p = _getProduct(id);
  if (!p) { toast('Product not found', 'warn'); return; }
  cartAdd(p, 1);
  cartCheckout();
}

/* ── PDP page interactions ───────────────────────────────────── */
function pdpQtyChange(delta) {
  const inp = document.getElementById('pdpQty');
  if (!inp) return;
  const max = parseInt(inp.max) || 9999;
  inp.value = Math.max(1, Math.min(max, parseInt(inp.value || 1) + delta));
}

function pdpAddToCart() {
  if (!CURRENT_PRODUCT) return;
  const qty = parseInt(document.getElementById('pdpQty')?.value || 1);
  cartAdd(CURRENT_PRODUCT, qty);
  const btn = document.getElementById('pdpAtcBtn');
  if (btn) {
    const orig = btn.innerHTML;
    btn.classList.add('added'); btn.disabled = true;
    btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Added to Cart`;
    setTimeout(() => { btn.classList.remove('added'); btn.disabled = false; btn.innerHTML = orig; }, 2000);
  }
}

function pdpBuyNow() {
  if (!CURRENT_PRODUCT) return;
  const qty = parseInt(document.getElementById('pdpQty')?.value || 1);
  cartAdd(CURRENT_PRODUCT, qty);
  cartCheckout();
}

function pdpToggleWish() {
  if (!CURRENT_PRODUCT) return;
  toggleWish(CURRENT_PRODUCT.id);
  document.getElementById('pdpWishBtn')?.classList.toggle('wished', S.wishIds.includes(CURRENT_PRODUCT.id));
}

function initPDPStars() {
  const el = document.getElementById('pdpStars');
  if (!el || !CURRENT_PRODUCT) return;
  el.innerHTML = stars(CURRENT_PRODUCT.rating, 16);
}

function initRelatedProducts() {
  const track   = document.getElementById('relatedTrack');
  const section = document.getElementById('relatedSection');
  if (!track || !CURRENT_PRODUCT) { if (section) section.style.display = 'none'; return; }

  const catId = parseInt(CURRENT_PRODUCT.category, 10) || 0;
  if (!catId) { if (section) section.style.display = 'none'; return; }

  const base = _catAjaxBase();
  const url  = `${base}?action=products&cat_id=${catId}&per_page=8&sort=featured`;

  track.innerHTML = Array(4).fill(0).map(() =>
    `<div class="pcard pcard--skeleton"><div class="pcard-img-wrap pcard-skel-img"></div>
     <div class="pcard-body"><div class="pcard-skel-line w70"></div>
     <div class="pcard-skel-line w50"></div></div></div>`
  ).join('');

  fetch(url)
    .then(r => r.json())
    .then(data => {
      if (!data.ok) { track.innerHTML = ''; if (section) section.style.display = 'none'; return; }
      const related = (data.products || [])
        .filter(p => p.id !== CURRENT_PRODUCT.id)
        .slice(0, 6)
        .map(_normProduct);
      if (related.length) {
        _registerProducts(related);
        track.innerHTML = related.map(p => pCard(p)).join('');
      } else {
        track.innerHTML = '';
        if (section) section.style.display = 'none';
      }
    })
    .catch(() => { track.innerHTML = ''; if (section) section.style.display = 'none'; });
}

/* ── Hero Carousel ───────────────────────────────────────────── */
function startHero() {
  const slides = document.querySelectorAll('.hero-slide');
  const track  = document.getElementById('heroTrack');
  const dots   = document.querySelectorAll('.hero-dot');
  if (!track || slides.length === 0) return;

  function goSlide(n) {
    S.heroIdx = (n + slides.length) % slides.length;
    track.style.transform = `translateX(-${S.heroIdx * 100}%)`;
    dots.forEach((d, i) => d.classList.toggle('on', i === S.heroIdx));
  }
  window.heroGo   = n => { goSlide(n); resetT(); };
  window.heroNext = () => { goSlide(S.heroIdx + 1); resetT(); };
  window.heroPrev = () => { goSlide(S.heroIdx - 1); resetT(); };

  function resetT() {
    clearInterval(S.heroTimer);
    S.heroTimer = setInterval(() => goSlide(S.heroIdx + 1), 5000);
  }
  goSlide(0); resetT();
}

/* ── Carousel scroll ─────────────────────────────────────────── */
function carouselScroll(id, dir) {
  const track = document.getElementById(id);
  if (!track) return;
  const cardW = 212;
  track.style.transform = `translateX(${parseFloat(track.style.transform.replace(/[^-\d.]/g, '') || 0) + dir * cardW * 3}px)`;
}

/* ══════════════════════════════════════════════════════════════
   ELASTIC SEARCH SUGGESTIONS — Professional e-commerce style
   - AJAX to /website/ajax/search.php
   - Dynamic category filter
   - Keyboard navigation (↑ ↓ Enter Esc)
   - Match highlighting
   - Loading / empty / error states
══════════════════════════════════════════════════════════════ */
let _srDebounce   = null;
let _srController = null;   /* AbortController for in-flight fetch */
let _srActiveIdx  = -1;
let _srItems      = [];

/* ── Helpers ── */
function _srEsc(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function _srHighlight(text, q) {
  if (!q) return _srEsc(text);
  const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
  return _srEsc(text).replace(re, '<mark class="sdrop-hl">$1</mark>');
}
function _srClose() {
  const box = document.getElementById('searchDrop');
  if (box) { box.classList.remove('open'); box.innerHTML = ''; }
  _srActiveIdx = -1;
  _srItems     = [];
}
function _srGetDrop() { return document.getElementById('searchDrop'); }
function _srGetField() { return document.getElementById('searchField'); }
function _srGetCat()   { return document.getElementById('searchCat'); }
function _srGetCatId() { return parseInt(_srGetCat()?.value || '0', 10) || 0; }

/* ── Navigate items with keyboard ── */
function _srMoveFocus(dir) {
  const box = _srGetDrop();
  if (!box || !box.classList.contains('open')) return;
  const items = box.querySelectorAll('.sdrop-item[data-idx]');
  if (!items.length) return;
  _srActiveIdx = Math.max(-1, Math.min(items.length - 1, _srActiveIdx + dir));
  items.forEach((el, i) => el.classList.toggle('is-active', i === _srActiveIdx));
  if (_srActiveIdx >= 0) {
    const active = items[_srActiveIdx];
    const code = active.dataset.code || '';
    const name = active.dataset.name || '';
    const fld  = _srGetField();
    if (fld) fld.value = code || name;
  }
}

/* ── Loading state ── */
function _srShowLoading() {
  const box = _srGetDrop();
  if (!box) return;
  box.innerHTML = `
    <div class="sdrop-loading">
      <svg class="sdrop-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
      </svg>
      Searching…
    </div>`;
  box.classList.add('open');
}

/* ── Render suggestions ── */
function _srRender(q, data) {
  const box = _srGetDrop();
  if (!box) return;

  const items = data.items || [];
  const total = data.total || 0;
  _srItems     = items;
  _srActiveIdx = -1;

  if (!items.length) {
    box.innerHTML = `
      <div class="sdrop-empty">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          <line x1="8" y1="11" x2="14" y2="11"/>
        </svg>
        <p>No products found for <strong>"${_srEsc(q)}"</strong></p>
        <small>Try a different part number or keyword</small>
      </div>`;
    box.classList.add('open');
    return;
  }

  const catId = _srGetCatId();
  const allUrl = `products?q=${encodeURIComponent(q)}${catId > 0 ? '&cat_id=' + catId : ''}`;

  box.innerHTML =
    `<div class="sdrop-header">
       <span class="sdrop-header-txt">
         <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
           <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
         </svg>
         Results for <strong>${_srEsc(q)}</strong>
       </span>
       <a class="sdrop-header-all" href="${allUrl}">View all ${total > 0 ? total : ''}</a>
     </div>` +
    items.map((p, i) => {
      const inStock   = p.REMAINING > 0;
      const stockCls  = inStock ? 'sdrop-stock--in' : 'sdrop-stock--out';
      const stockTxt  = inStock ? `In Stock (${p.REMAINING})` : 'Out of Stock';
      const labelHtml = p.LABEL ? `<span class="sdrop-badge sdrop-badge--${p.LABEL.toLowerCase().replace(/\s+/g,'-')}">${_srEsc(p.LABEL)}</span>` : '';
      const priceHtml = p.PRICE > 0
        ? `<div class="sdrop-price">
             €${p.PRICE.toFixed(2)}
             ${p.OFFER > 0 ? `<span class="sdrop-price-was">€${p.ORG_PRICE.toFixed(2)}</span>` : ''}
           </div>`
        : '';
      const ratingHtml = p.RATING > 0
        ? `<span class="sdrop-rating">★ ${p.RATING.toFixed(1)}</span>` : '';

      const imgHtml = p.IMAGE
        ? `<img class="sdrop-thumb" src="${_srEsc(p.IMAGE)}" alt="${_srEsc(p.NAME)}"
                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
           ><div class="sdrop-icon" style="display:none">`
        : `<div class="sdrop-icon">`;

      return `
        <div class="sdrop-item" role="option" tabindex="-1"
             data-idx="${i}" data-id="${p.ID}"
             data-code="${_srEsc(p.CODE)}" data-name="${_srEsc(p.NAME)}"
             onclick="_srSelectItem(${p.ID},'${encodeURIComponent(p.CODE)}')">
          ${imgHtml}
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
              <path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
              <path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
            </svg>
          </div>
          <div class="sdrop-info">
            <div class="sdrop-sku">${_srHighlight(p.CODE, q)}</div>
            <div class="sdrop-name">${_srHighlight(p.NAME, q)}</div>
            <div class="sdrop-meta">
              ${p.CATEGORY ? `<span class="sdrop-cat">${_srEsc(p.CATEGORY)}</span>` : ''}
              <span class="sdrop-stock ${stockCls}">${stockTxt}</span>
              ${labelHtml}
              ${ratingHtml}
            </div>
          </div>
          ${priceHtml}
        </div>`;
    }).join('') +
    `<div class="sdrop-footer" onclick="showPageLoader('Loading results…');location.href='${allUrl}'">
       <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
         <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
       </svg>
       See all <strong>${total > 0 ? total + ' ' : ''}results</strong> for "${_srEsc(q)}"
       <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-left:auto">
         <path d="M5 12h14M12 5l7 7-7 7"/>
       </svg>
     </div>`;

  box.classList.add('open');
}

/* ── Select a suggestion ── */
function _srSelectItem(productId, encodedCode) {
  _srClose();
  const code = decodeURIComponent(encodedCode);
  const fld  = _srGetField();
  if (fld) fld.value = code;
  showPageLoader('Loading product…');
  location.href = `product?id=${productId}`;
}

/* ── Resolve AJAX base (same pattern as deliver-to) ── */
function _srAjaxBase() {
  const p = window.location.pathname;
  if (p.indexOf('/website/') !== -1) return 'ajax/search';
  if (p.indexOf('/admin/')   !== -1) return '../website/ajax/search';
  return 'website/ajax/search';
}

/* ── Fetch from server ── */
function _srFetch(q) {
  if (_srController) _srController.abort();
  _srController = typeof AbortController !== 'undefined' ? new AbortController() : null;

  const catId  = _srGetCatId();
  const url    = `${_srAjaxBase()}?action=suggest&q=${encodeURIComponent(q)}&cat_id=${catId}`;

  fetch(url, _srController ? { signal: _srController.signal } : {})
    .then(r => r.json())
    .then(data => {
      if (data.ok) _srRender(q, data);
      else _srClose();
    })
    .catch(err => {
      if (err.name !== 'AbortError') _srClose();
    });
}

/* ── Main input handler ── */
function onSearchInput(e) {
  clearTimeout(_srDebounce);
  const q = e.target.value.trim();
  if (!q || q.length < 2) { _srClose(); return; }
  _srShowLoading();
  _srDebounce = setTimeout(() => _srFetch(q), 220);
}

/* ── Category change: re-run search if field has value ── */
function onSearchCatChange() {
  const q = _srGetField()?.value.trim() || '';
  if (q.length >= 2) {
    _srShowLoading();
    clearTimeout(_srDebounce);
    _srDebounce = setTimeout(() => _srFetch(q), 100);
  }
}

function getProductSubcategory(product) {
  if (product.subcategory) return product.subcategory;

  const name = (product.name || '').toLowerCase();
  const category = product.category || '';

  if (category === 'mcu') {
    if (name.includes('esp32')) return 'ESP32';
    if (name.includes('atmega')) return 'AVR';
    if (name.includes('pic')) return 'PIC';
    return 'ARM Cortex';
  }
  if (category === 'logic') {
    if (name.includes('shift register')) return 'Shift Registers';
    if (name.includes('nand') || name.includes('gate')) return 'Gates';
    return 'Flip-Flops';
  }
  if (category === 'opamp') {
    if (name.includes('lm358') || name.includes('dual')) return 'Dual Op-Amp';
    return 'General Purpose';
  }
  if (category === 'power') {
    if (name.includes('ams1117') || name.includes('ldo')) return 'LDO Regulators';
    if (name.includes('buck') || name.includes('converter') || name.includes('mp2307')) return 'Buck Converters';
    return 'Linear Regulators';
  }
  if (category === 'transistor') {
    if (name.includes('mosfet')) return 'Power MOSFETs';
    return 'NPN Transistors';
  }
  if (category === 'sensor') {
    if (name.includes('dht')) return 'Temperature & Humidity';
    if (name.includes('mpu')) return 'IMU';
    if (name.includes('ultrasonic') || name.includes('hc-sr04')) return 'Ultrasonic';
    return 'Motion';
  }
  if (category === 'comm') {
    if (name.includes('max232')) return 'RS-232';
    if (name.includes('rf') || name.includes('nrf24')) return 'RF 2.4GHz';
    return 'Wireless Modules';
  }
  if (category === 'memory') return 'EEPROM';
  if (category === 'passive') {
    if (name.includes('resistor')) return 'Resistors';
    if (name.includes('capacitor')) return 'Capacitors';
    return 'Inductors';
  }
  if (category === 'display') {
    if (name.includes('oled')) return 'OLED';
    if (name.includes('lcd')) return 'Character LCD';
    return 'Display Modules';
  }

  return '';
}

/* ── Catalog ─────────────────────────────────────────────────── */
/* ── Build AJAX params from S state ─────────────────────────── */
function _catParams() {
  const p = new URLSearchParams();
  const q = S.query || S.filters.filterQuery;
  if (q)                           p.set('q',          q);
  if (S.catIds)                    p.set('cat_ids',    S.catIds);  /* multi-cat from mfr link */
  else if (S.catId > 0)            p.set('cat_id',     S.catId);
  if (S.filters.mfrs.length > 0)  p.set('mfr',        S.filters.mfrs[0]); /* first selected mfr */
  if (S.filters.minRating > 0)    p.set('min_rating',  S.filters.minRating);
  if (S.filters.minP > 0)         p.set('min_price',   S.filters.minP);
  if (S.filters.maxP > 0)         p.set('max_price',   S.filters.maxP);
  if (S.filters.inStock)          p.set('in_stock',    1);
  if (S.filters.isNew)            p.set('is_new',      1);
  p.set('sort',     S.sort);
  p.set('page',     S.page);
  p.set('per_page', S.perPage);
  return p;
}

/* ── Fetch catalog page from server ─────────────────────────── */
async function _fetchCatalogProducts() {
  const url = `${_catAjaxBase()}?action=products&${_catParams()}`;
  const r   = await fetch(url);
  return await r.json();
}

/* ── Normalize DB product to pCard-compatible shape ─────────── */
function _normProduct(p) {
  return {
    id:            p.id,
    sku:           p.sku,
    name:          p.name,
    category:      String(p.category),
    categoryName:  p.categoryName,
    image:         p.image || '',
    price:         p.price,
    priceBreaks:   null,
    originalPrice: p.originalPrice > 0 ? p.originalPrice : null,
    stock:         p.stock,
    rating:        p.rating || 0,
    reviews:       p.reviews || 0,
    label:         p.label || '',
    badge:         p.badge || '',
    isNew:         p.isNew,
    isFeatured:    p.isFeatured,
    description:   p.description || '',
    package:       '',
    manufacturer:  '',
  };
}

/* kept for backward compat (home page etc) */
function filteredProducts() { return []; }

function goToPage(n) { S.page = n; renderCatalog(); window.scrollTo({ top: 0, behavior: 'smooth' }); }

function renderToolbarPagination(total, page, perPage) {
  const el = document.getElementById('toolbarPagination');
  if (!el) return;
  const totalPages = Math.ceil(total / perPage);
  if (total === 0) { el.innerHTML = '<span class="tpg-total">0 results</span>'; return; }

  const pageNums = [];
  const delta = 1;
  for (let i = 1; i <= totalPages; i++) {
    if (i === 1 || i === totalPages || (i >= page - delta && i <= page + delta)) pageNums.push(i);
    else if (pageNums[pageNums.length - 1] !== '…') pageNums.push('…');
  }

  const btns = pageNums.map(n =>
    n === '…'
      ? `<span class="tpg-ellipsis">…</span>`
      : `<button class="tpg-btn ${n === page ? 'active' : ''}" onclick="goToPage(${n})">${n}</button>`
  ).join('');

  el.innerHTML = `
    <span class="tpg-total">${total} result${total !== 1 ? 's' : ''}</span>
    <button class="tpg-nav" onclick="goToPage(${page - 1})" ${page <= 1 ? 'disabled' : ''}>&#8249;</button>
    ${btns}
    <button class="tpg-nav" onclick="goToPage(${page + 1})" ${page >= totalPages ? 'disabled' : ''}>&#8250;</button>`;
}

/* ── renderCatalog: fire-and-forget wrapper (keeps all callers sync) */
function renderCatalog() { _renderCatalogAsync().catch(console.error); }

async function _renderCatalogAsync() {
  const grid = document.getElementById('prodGrid');
  if (!grid) return;

  /* Show loading skeleton */
  grid.className = S.view === 'list' ? 'prod-list' : 'prod-grid';
  grid.innerHTML = Array(S.perPage).fill(0).map(() =>
    `<div class="pcard pcard--skeleton"><div class="pcard-img-wrap pcard-skel-img"></div>
     <div class="pcard-body"><div class="pcard-skel-line w70"></div>
     <div class="pcard-skel-line w50"></div><div class="pcard-skel-line w40"></div></div></div>`
  ).join('');

  let data;
  try { data = await _fetchCatalogProducts(); }
  catch(e) {
    grid.innerHTML = `<div class="catalog-empty"><div class="catalog-empty-icon">⚠</div>
      <p class="catalog-empty-title">Failed to load products</p>
      <p class="catalog-empty-sub">Check your connection and try again</p>
      <button class="btn btn-blue" onclick="renderCatalog()">Retry</button></div>`;
    return;
  }

  if (!data.ok) { grid.innerHTML = `<div class="catalog-empty"><p class="catalog-empty-title">Error loading products</p></div>`; return; }

  const products = (data.products || []).map(_normProduct);
  _registerProducts(products);   /* keep registry fresh for atcClick */
  const total    = data.total    || 0;
  const pages    = data.pages    || 1;

  /* Clamp page */
  if (S.page > pages) S.page = pages;
  if (S.page < 1)     S.page = 1;

  /* Breadcrumb */
  const catName = S.catId > 0
    ? (CATALOG_DB.categories.find(c => c.id === S.catId)?.name || '')
    : (S.mfrFilter ? S.mfrFilter : (S.query ? `Search: "${S.query}"` : ''));
  const bc = document.getElementById('catalogBC');
  if (bc) bc.innerHTML = `
    <a href="index">${ic('home', 12, 12)} Home</a>
    <span class="breadcrumb-sep">›</span><a href="products">Products</a>
    ${catName ? `<span class="breadcrumb-sep">›</span><span class="breadcrumb-cur">${catName}</span>` : ''}`;

  renderToolbarPagination(total, S.page, S.perPage);

  if (!products.length) {
    grid.innerHTML = `<div class="catalog-empty">
      <div class="catalog-empty-icon">🔍</div>
      <p class="catalog-empty-title">No results found</p>
      <p class="catalog-empty-sub">Try different keywords or adjust your filters</p>
      <button class="btn btn-blue" onclick="clearFilters()">Clear All Filters</button>
    </div>`;
  } else {
    grid.innerHTML = products.map((p, i) => pCard(p, S.view === 'list', i % 5 === 0)).join('');
  }

  /* Pagination */
  const bottomPg = document.getElementById('catalogPagination');
  if (bottomPg) bottomPg.style.display = pages > 1 ? '' : 'none';

  renderFilterSidebar();
  renderActiveFilterTags(total);
}

function renderActiveFilterTags(count) {
  const row = document.getElementById('activeFiltersRow');
  if (!row) return;
  const tags = [];
  if (S.catId > 0) {
    const cat  = CATALOG_DB.categories.find(c => c.id === S.catId);
    const name = cat ? cat.name : `#${S.catId}`;
    tags.push(`<span class="af-tag">Category: ${name} <button onclick="S.catId=0;S.page=1;renderCatalog()">&times;</button></span>`);
  }
  if (S.query) tags.push(`<span class="af-tag">Query: "${S.query}" <button onclick="S.query='';S.page=1;renderCatalog()">&times;</button></span>`);
  S.filters.mfrs.forEach(m => tags.push(`<span class="af-tag">${m} <button onclick="toggleMfr('${m.replace(/'/g,"\\'")}',false)">&times;</button></span>`));
  if (S.filters.minRating > 0) tags.push(`<span class="af-tag">${S.filters.minRating}★ & Up <button onclick="setRatingFilter(0)">&times;</button></span>`);
  if (S.filters.minP > 0 || S.filters.maxP > 0) tags.push(`<span class="af-tag">€${S.filters.minP}–${S.filters.maxP > 0 ? S.filters.maxP : '∞'} <button onclick="S.filters.minP=0;S.filters.maxP=0;document.getElementById('priceMin').value='';document.getElementById('priceMax').value='';renderCatalog()">&times;</button></span>`);
  if (S.filters.inStock) tags.push(`<span class="af-tag">In Stock <button onclick="S.filters.inStock=false;document.getElementById('filterStock').checked=false;renderCatalog()">&times;</button></span>`);
  if (S.filters.isNew) tags.push(`<span class="af-tag">New Arrivals <button onclick="S.filters.isNew=false;document.getElementById('filterNew').checked=false;renderCatalog()">&times;</button></span>`);
  if (S.filters.filterQuery) tags.push(`<span class="af-tag">Search: "${S.filters.filterQuery}" <button onclick="S.filters.filterQuery='';document.getElementById('filterSearchInp').value='';renderCatalog()">&times;</button></span>`);
  row.innerHTML = tags.join('');
}

/* ── Filter Sidebar ────────────────────────────────────────────── */
function renderFilterSidebar() {
  renderCatFilters();
  renderMfrFilters();
  renderRatingFilters();
}

function renderCatFilters() {
  const catEl = document.getElementById('catFilters');
  if (!catEl) return;

  const cats = CATALOG_DB.categories;

  /* "All" row — total is sum of all child-category counts only
     (parent counts are already included in their children to avoid double-counting) */
  const totalCount = cats.filter(c => c.parent_id !== 0).reduce((s, c) => s + c.count, 0)
                  || cats.reduce((s, c) => s + c.count, 0); /* fallback if no children exist */
  let html = `
    <div class="cat-row cat-all-row ${S.catId === 0 ? 'active' : ''}"
         onclick="selectCategory(0)">
      <span class="cat-row-name">All Categories</span>
      <span class="filter-count">${totalCount}</span>
    </div>`;

  /* Group: top-level (parent_id === 0) and their children */
  const roots    = cats.filter(c => c.parent_id === 0);
  const children = cats.filter(c => c.parent_id !== 0);

  roots.forEach(root => {
    const kids       = children.filter(c => c.parent_id === root.id);
    const isSelected = S.catId === root.id;
    const isExpanded = S.expandedCats.includes(root.id);
    const hasKids    = kids.length > 0;
    /* Combined count = parent's own direct products + all children's products */
    const rootCount  = root.count + kids.reduce((s, k) => s + k.count, 0);

    html += `
      <div class="cat-accordion ${isExpanded ? 'expanded' : ''}" data-cat-id="${root.id}">
        <div class="cat-row ${isSelected ? 'active' : ''}"
             onclick="selectCategory(${root.id})">
          <span class="cat-row-name">${root.name}</span>
          <span class="filter-count">${rootCount}</span>
          ${hasKids ? `<span class="acc-toggle" onclick="event.stopPropagation();toggleCatAccordion(${root.id})">
            <svg class="acc-chevron" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </span>` : ''}
        </div>
        ${hasKids ? `<div class="cat-subcats">
          ${kids.map(kid => `
            <div class="subcat-row ${S.catId === kid.id ? 'active' : ''}"
                 onclick="selectCategory(${kid.id})">
              <span>${kid.name}</span>
              <span class="filter-count">${kid.count}</span>
            </div>`).join('')}
        </div>` : ''}
      </div>`;
  });

  /* Orphan children whose parent wasn't in roots (rare) */
  const rootIds = new Set(roots.map(r => r.id));
  children.filter(c => !rootIds.has(c.parent_id)).forEach(c => {
    html += `
      <div class="cat-row ${S.catId === c.id ? 'active' : ''}"
           onclick="selectCategory(${c.id})">
        <span class="cat-row-name">${c.name}</span>
        <span class="filter-count">${c.count}</span>
      </div>`;
  });

  catEl.innerHTML = html;
}

function selectCategory(catId) {
  S.catId  = catId;
  S.catIds = '';   /* clear multi-cat lock when user picks a specific category */
  S.page   = 1;
  if (catId && !S.expandedCats.includes(catId)) S.expandedCats.push(catId);
  renderCatalog();
}

function toggleCatAccordion(catId) {
  const idx = S.expandedCats.indexOf(catId);
  if (idx > -1) S.expandedCats.splice(idx, 1);
  else S.expandedCats.push(catId);
  const acc = document.querySelector(`.cat-accordion[data-cat-id="${catId}"]`);
  if (acc) acc.classList.toggle('expanded', S.expandedCats.includes(catId));
}

function renderMfrFilters() {
  const mfrEl = document.getElementById('mfrFilters');
  if (!mfrEl) return;
  const mfrs = CATALOG_DB.manufacturers;
  if (!mfrs.length) { mfrEl.innerHTML = '<p class="filter-empty-note">No manufacturers</p>'; return; }
  mfrEl.innerHTML = mfrs.map(m => {
    const safeName = m.name.replace(/'/g, "\\'");
    return `<label class="filter-item">
      <input type="checkbox" class="filter-check" ${S.filters.mfrs.includes(m.name) ? 'checked' : ''}
             onchange="toggleMfr('${safeName}',this.checked)">
      <span>${m.name}</span>
    </label>`;
  }).join('');
}

function renderRatingFilters() {
  const ratingEl = document.getElementById('ratingFilters');
  if (!ratingEl) return;
  const ratingOpts = [4, 3, 2, 1];
  ratingEl.innerHTML = `
    <label class="filter-item">
      <input type="radio" class="filter-check" name="ratingFilter" ${S.filters.minRating === 0 ? 'checked' : ''}
             onchange="setRatingFilter(0)">
      <span>Any Rating</span>
    </label>` +
    ratingOpts.map(r => `
      <label class="filter-item rating-filter-item">
        <input type="radio" class="filter-check" name="ratingFilter" ${S.filters.minRating === r ? 'checked' : ''}
               onchange="setRatingFilter(${r})">
        <span class="rf-label"><span class="rf-stars">${'★'.repeat(r)}${'☆'.repeat(5-r)}</span><span class="rf-up"> &amp; Up</span></span>
      </label>`).join('');
}

function setRatingFilter(r) {
  S.filters.minRating = r;
  renderCatalog();
}

function renderPackageFilters() {
  const pkgEl = document.getElementById('pkgFilters');
  if (!pkgEl) return;
  const products = [];
  const pkgMap = {};
  products.forEach(p => { if (p.package) pkgMap[p.package] = (pkgMap[p.package] || 0) + 1; });
  const pkgs = Object.entries(pkgMap).sort((a, b) => b[1] - a[1]);
  if (!pkgs.length) { pkgEl.innerHTML = '<p class="filter-empty-note">No package data</p>'; return; }
  pkgEl.innerHTML = pkgs.map(([pkg, cnt]) => `
    <label class="filter-item">
      <input type="checkbox" class="filter-check" ${S.filters.packages.includes(pkg) ? 'checked' : ''}
             onchange="togglePackage('${pkg.replace(/'/g, "\\'")}',this.checked)">
      <span>${pkg}</span>
      <span class="filter-count">${cnt}</span>
    </label>`).join('');
}

function togglePackage(pkg, checked) {
  if (checked) S.filters.packages.push(pkg);
  else S.filters.packages = S.filters.packages.filter(x => x !== pkg);
  renderCatalog();
}

/* collapse/expand a whole filter group */
function toggleFilterGroup(id) {
  const el = document.getElementById(id);
  if (!el) return;
  const isCollapsed = el.classList.toggle('fg-collapsed');
  const chevron = el.querySelector('.fg-chevron');
  if (chevron) chevron.style.transform = isCollapsed ? 'rotate(-90deg)' : '';
}

function onFilterSearch(q) {
  S.filters.filterQuery = q.trim(); S.page = 1;
  renderCatalog();
}

function applyPriceFilter() {
  S.filters.minP = parseFloat(document.getElementById('priceMin')?.value) || 0;
  S.filters.maxP = parseFloat(document.getElementById('priceMax')?.value) || 0;
  S.page = 1;
  renderCatalog();
}

function toggleMfr(m, checked) {
  if (checked) { if (!S.filters.mfrs.includes(m)) S.filters.mfrs.push(m); }
  else S.filters.mfrs = S.filters.mfrs.filter(x => x !== m);

  /* Rebuild S.catIds from all selected manufacturers' category IDs.
     If a selected mfr has no catIds use sentinel 'none' so it yields 0 results
     (matching the manufacturer link behaviour). */
  if (S.filters.mfrs.length > 0) {
    const allIds = [];
    let hasEmpty = false;
    S.filters.mfrs.forEach(name => {
      const mfrData = CATALOG_DB.manufacturers.find(x => x.name === name);
      const ids = mfrData?.catIds ? mfrData.catIds.split(',').map(s => s.trim()).filter(Boolean) : [];
      if (ids.length) allIds.push(...ids);
      else hasEmpty = true;
    });
    if (allIds.length) {
      /* deduplicate and join */
      S.catIds = [...new Set(allIds)].join(',');
    } else if (hasEmpty) {
      S.catIds = 'none';
    } else {
      S.catIds = '';
    }
  } else {
    S.catIds = '';   /* no mfr selected → remove category restriction */
  }

  renderCatalog();
}

function clearFilters() {
  S.catId        = 0;
  S.catIds       = '';
  S.catFilter    = null;
  S.subcatFilter = null;
  S.mfrFilter    = null;
  S.query        = '';
  S.expandedCats = [];
  S.filters      = { mfrs: [], minP: 0, maxP: 0, inStock: false, isNew: false, minRating: 0, filterQuery: '' };
  S.sort         = 'featured'; S.page = 1;
  const si = document.getElementById('filterSearchInp'); if (si) si.value = '';
  const pm = document.getElementById('priceMin');        if (pm) pm.value = '';
  const px = document.getElementById('priceMax');        if (px) px.value = '';
  const fs = document.getElementById('filterStock');     if (fs) fs.checked = false;
  const fn = document.getElementById('filterNew');       if (fn) fn.checked = false;
  const ss = document.getElementById('sortSel');         if (ss) ss.value = 'featured';
  renderCatalog();
}

function renderHomeCategories() {
  const el = document.getElementById('homeCats');
  if (!el) return;
  const catIcons = { mcu: 'cpu', logic: 'grid', opamp: 'zap', power: 'zap', transistor: 'tri', sensor: 'radio', comm: 'wifi', passive: 'minus_c', memory: 'db', display: 'monitor' };
  if (!STORE_DATA.categories.length) { el.innerHTML = ''; return; }
  el.innerHTML = STORE_DATA.categories.map(c => `
    <a href="products?cat_id=${c.id}" class="cat-card">
      <div class="cat-icon">${ic('cpu', 22, 22)}</div>
      <div class="cat-name">${c.name}</div>
    </a>`).join('');
}

function renderFeaturedCarousel(elId, products, variant = 'default') {
  const el = document.getElementById(elId);
  if (!el) return;
  el.innerHTML = products.map(p => pCard(p, variant)).join('');
}

/* ── Featured Manufacturers logos ────────────────────────────── */
const MFR_LOGO_COLORS = {
  'STMicroelectronics':  { bg: '#f0f6ff', tc: '#003d82' },
  'Texas Instruments':   { bg: '#fff4f0', tc: '#c0392b' },
  'Microchip Technology':{ bg: '#fff8f0', tc: '#d4700a' },
  'NXP Semiconductors':  { bg: '#f5f0ff', tc: '#6228c8' },
  'Infineon':            { bg: '#f0faff', tc: '#00629b' },
  'ON Semiconductor':    { bg: '#f5fff5', tc: '#1a7a1a' },
  'Analog Devices':      { bg: '#f0f0ff', tc: '#1c2e7e' },
  'Renesas':             { bg: '#fff0f5', tc: '#b5003c' },
  'Vishay':              { bg: '#f8f8f0', tc: '#4a4a00' },
  'ROHM':                { bg: '#fff5f0', tc: '#b84000' },
  'Murata':              { bg: '#f0f9f0', tc: '#006633' },
  'Wurth Elektronik':    { bg: '#fff0f0', tc: '#cc0000' },
};
function renderMfrLogos() {
  const el = document.getElementById('mfrLogosGrid');
  if (!el) return;
  const mfrs = STORE_DATA.manufacturers.slice(0, 12);
  el.innerHTML = mfrs.map(name => {
    const c = MFR_LOGO_COLORS[name] || { bg: '#f4f6f8', tc: '#374151' };
    const short = name.replace(' Semiconductors','').replace(' Technology','').replace(' Integrated','').replace(' Electronics','');
    return `<a href="products?mfr=${encodeURIComponent(name)}" class="mfr-logo-card" style="background:${c.bg};">
      <span class="mfr-logo-text" style="color:${c.tc};">${short}</span>
    </a>`;
  }).join('');
}

/* ── Service & Tools cards ────────────────────────────────────── */
const SRV_IMAGES = [
  'https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=300&fit=crop',
  'https://images.unsplash.com/photo-1531746790731-6c087fecd65a?w=300&h=300&fit=crop',
  'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=300&h=300&fit=crop',
  'https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?w=300&h=300&fit=crop',
  'https://images.unsplash.com/photo-1555664424-778a1e5e1b48?w=300&h=300&fit=crop',
];
function renderSrvTools() {
  const el = document.getElementById('srvToolsGrid');
  if (!el) return;
  if (!STORE_DATA.services.length) { el.innerHTML = ''; return; }
  el.innerHTML = STORE_DATA.services.map((s, i) => `
    <div class="srv-tool-card">
      <div class="srv-tool-img-wrap">
        <img src="${SRV_IMAGES[i % SRV_IMAGES.length]}" alt="${s.title}" loading="lazy"
             onerror="this.style.display='none'">
      </div>
      <h3 class="srv-tool-title">${s.title}</h3>
      <p class="srv-tool-desc">${s.description}</p>
    </div>`).join('');
}

function renderFlashDeals() {
  const el = document.getElementById('flashDeals');
  if (!el) return;
  const deals = [];
  if (!deals.length) { el.innerHTML = '<p style="color:#94a3b8;font-size:13px;padding:16px;">No deals available.</p>'; return; }
  el.innerHTML = deals.map(p => {
    return `
    <div class="fdeal-card" onclick="openPDP(${p.id})">
      <div class="fdeal-img">
        <img src="${p.image}" alt="${p.name}" data-sku="${p.sku}" onerror="_pImgFail(this)">
      </div>
      <div class="fdeal-body">
        <div class="fdeal-name">${p.name}</div>
        <div class="fdeal-price">€${(p.priceBreaks ? p.priceBreaks[0].price : p.price).toFixed(2)}<small>€${p.originalPrice.toFixed(2)}</small></div>
      </div>
    </div>`;
  }).join('');
}

function renderTopDeals() {
  const el = document.getElementById('topDeals');
  if (!el) return;
  const deals = [];
  if (!deals.length) { el.innerHTML = '<p style="color:#94a3b8;font-size:13px;padding:16px;">No deals available.</p>'; return; }
  el.innerHTML = deals.map(p => {
    const price = p.priceBreaks ? p.priceBreaks[0].price : p.price;
    return `
    <div class="deal-card" onclick="openPDP(${p.id})">
      <div class="deal-card-img"><img src="${p.image}" alt="${p.name}" data-sku="${p.sku}" onerror="_pImgFail(this)"></div>
      <div class="deal-card-body">
        <div class="deal-card-name">${p.name}</div>
        <div class="deal-card-stars">${stars(p.rating, 12)}</div>
        <div class="deal-card-price">
          <span class="deal-price-main">€${price.toFixed(2)}</span>
          <span class="deal-price-orig">€${p.originalPrice.toFixed(2)}</span>
        </div>
        <button class="btn-atc" onclick="event.stopPropagation();atcClick(event,${p.id})">${ic('cart', 13, 13)} Add to Cart</button>
      </div>
    </div>`;
  }).join('');
}

/* ── Services render ─────────────────────────────────────────── */
function renderServices() {
  const el = document.getElementById('srvGrid');
  if (!el) return;
  el.innerHTML = STORE_DATA.services.map(s => `
    <div class="srv-card">
      <div class="srv-icon">${ic(s.icon || 'code', 22, 22)}</div>
      <h3 class="srv-title">${s.title}</h3>
      <p class="srv-desc">${s.description}</p>
      <div class="srv-features">${s.features.map(f => `<div class="srv-feat">${ic('check', 13, 13)}<span>${f}</span></div>`).join('')}</div>
      <span class="srv-price">${ic('tag', 13, 13)} ${s.price}</span>
    </div>`).join('');
}

function initAboutTeamCarousel() {
  const track = document.getElementById('aboutTeamTrack');
  const prev = document.getElementById('aboutTeamPrev');
  const next = document.getElementById('aboutTeamNext');
  if (!track || !prev || !next) return;

  const slides = Array.from(track.children);
  if (!slides.length) return;

  let index = 0;

  const getVisible = () => {
    if (window.innerWidth <= 760) return 1;
    if (window.innerWidth <= 1100) return 2;
    return 3;
  };

  const update = () => {
    const visible = getVisible();
    const maxIndex = Math.max(0, slides.length - visible);
    index = Math.min(index, maxIndex);

    const slideWidth = slides[0].getBoundingClientRect().width;
    const gap = parseFloat(getComputedStyle(track).gap || '0') || 0;
    track.style.transform = `translateX(-${index * (slideWidth + gap)}px)`;

    prev.disabled = index === 0;
    next.disabled = index >= maxIndex;
  };

  prev.addEventListener('click', () => {
    index = Math.max(0, index - 1);
    update();
  });

  next.addEventListener('click', () => {
    const maxIndex = Math.max(0, slides.length - getVisible());
    index = Math.min(maxIndex, index + 1);
    update();
  });

  window.addEventListener('resize', update);
  update();
}

/* ── Mobile menu ─────────────────────────────────────────────── */
function openMobMenu()  { document.getElementById('mobMenu').classList.add('on'); document.body.style.overflow = 'hidden'; }
function closeMobMenu() { document.getElementById('mobMenu').classList.remove('on'); document.body.style.overflow = ''; }

/* ── Filter panel (mobile) ───────────────────────────────────── */
function openFilterPanel()  { document.querySelector('.filter-col')?.classList.add('open'); document.getElementById('filterOverlay')?.classList.add('show'); }
function closeFilterPanel() { document.querySelector('.filter-col')?.classList.remove('open'); document.getElementById('filterOverlay')?.classList.remove('show'); }

/* ── Mega menu ───────────────────────────────────────────────── */
function toggleProductsMenu(e) {
  e.preventDefault(); e.stopPropagation();
  document.getElementById('productsNavItem')?.classList.toggle('open');
}
function closeProductsMenu() {
  document.getElementById('productsNavItem')?.classList.remove('open');
  resetMegaMenu();
}
function resetMegaMenu() {
  const menu = document.querySelector('.mega-products');
  if (!menu) return;
  menu.querySelectorAll('.mega-cat').forEach(c => c.classList.remove('active'));
  menu.querySelectorAll('.mega-panel').forEach(p => p.classList.remove('active'));
  const defCat   = menu.querySelector('.mega-cat[data-cat-id="newest"]');
  const defPanel = menu.querySelector('.mega-panel[data-panel-id="newest"]');
  if (defCat) defCat.classList.add('active');
  if (defPanel) defPanel.classList.add('active');
}

function activateMegaCategory(cat) {
  if (!cat) return;
  const id   = cat.dataset.catId;
  const menu = cat.closest('.mega-products');
  if (!menu || !id) return;
  menu.querySelectorAll('.mega-cat').forEach(c => c.classList.remove('active'));
  menu.querySelectorAll('.mega-panel').forEach(p => p.classList.remove('active'));
  cat.classList.add('active');
  menu.querySelector(`.mega-panel[data-panel-id="${id}"]`)?.classList.add('active');
}

/* ── Quote form helpers ──────────────────────────────────────── */
function getQuoteSelectedCategoryId(row) {
  const categorySelect = row?.querySelector('select.qinp');
  const selectedName = categorySelect?.value?.trim();
  if (!selectedName) return '';
  return STORE_DATA.categories.find(c => c.name === selectedName)?.id || '';
}

function getQuoteSearchMatches(query, row) {
  /* Products now served via AJAX — this sync fallback returns empty;
     live search uses the async searchQuoteProducts() flow instead. */
  return [];
}

function closeQuoteSearchDrops(exceptWrap = null) {
  document.querySelectorAll('.quote-product-search').forEach(wrap => {
    if (exceptWrap && wrap === exceptWrap) return;
    wrap.querySelector('.quote-product-drop')?.classList.remove('open');
  });
}

function selectQuoteProduct(input, product) {
  input.value = `${product.sku} - ${product.name}`;
  input.dataset.selectedProductId = String(product.id);

  const row = input.closest('.qprow');
  const categorySelect = row?.querySelector('select.qinp');
  if (categorySelect && product.category) {
    const category = STORE_DATA.categories.find(c => c.id === product.category);
    if (category) categorySelect.value = category.name;
  }

  input.closest('.quote-product-search')?.querySelector('.quote-product-drop')?.classList.remove('open');
}

function renderQuoteSearchDrop(input) {
  const wrap = input.closest('.quote-product-search');
  const drop = wrap?.querySelector('.quote-product-drop');
  const row = input.closest('.qprow');
  if (!wrap || !drop || !row) return;

  const matches = getQuoteSearchMatches(input.value, row);
  if (!matches.length) {
    drop.innerHTML = `<div class="quote-product-empty">No matching products found for this category.</div>`;
    drop.classList.add('open');
    return;
  }

  drop.innerHTML = matches.map(p => `
    <div class="quote-product-item" data-product-id="${p.id}">
      <img src="${p.image}" alt="${p.name}" onerror="_pImgFail(this)">
      <div class="quote-product-item-info">
        <div class="quote-product-item-sku">${p.sku}</div>
        <div class="quote-product-item-name">${p.name}</div>
      </div>
      <div class="quote-product-item-meta">€${(p.priceBreaks ? p.priceBreaks[0].price : p.price).toFixed(2)}</div>
    </div>
  `).join('');

  drop.querySelectorAll('.quote-product-item').forEach(item => {
    item.addEventListener('click', () => {
      const product = _getProduct(Number(item.dataset.productId));
      if (product) selectQuoteProduct(input, product);
    });
  });

  drop.classList.add('open');
}

function initQuoteProductSearch(scope = document) {
  const roots = scope.matches?.('.qprow') ? [scope] : Array.from(scope.querySelectorAll('.qprow'));

  roots.forEach(row => {
    const input = row.querySelector('[data-product-search="true"]');
    const categorySelect = row.querySelector('select.qinp');
    if (!input) return;
    if (input.dataset.quoteSearchBound === 'true') return;
    input.dataset.quoteSearchBound = 'true';

    input.addEventListener('input', () => {
      input.dataset.selectedProductId = '';
      closeQuoteSearchDrops(input.closest('.quote-product-search'));
      renderQuoteSearchDrop(input);
    });

    input.addEventListener('focus', () => {
      closeQuoteSearchDrops(input.closest('.quote-product-search'));
      renderQuoteSearchDrop(input);
    });

    input.addEventListener('click', () => {
      closeQuoteSearchDrops(input.closest('.quote-product-search'));
      renderQuoteSearchDrop(input);
    });

    categorySelect?.addEventListener('change', () => {
      input.value = '';
      input.dataset.selectedProductId = '';
      closeQuoteSearchDrops(input.closest('.quote-product-search'));
      renderQuoteSearchDrop(input);
      input.focus();
    });
  });
}

function addQuoteRow() {
  const rows = document.getElementById('quoteProductRows');
  if (!rows) return;
  const first = rows.querySelector('.qprow');
  if (!first) return;
  const clone = first.cloneNode(true);
  const num = rows.querySelectorAll('.qprow').length + 1;
  clone.querySelector('.qprow-num').textContent = num;
  clone.querySelectorAll('input, select').forEach(el => el.value = '');
  clone.querySelectorAll('[data-quote-search-bound]').forEach(el => delete el.dataset.quoteSearchBound);
  clone.querySelectorAll('.quote-product-drop').forEach(el => {
    el.innerHTML = '';
    el.classList.remove('open');
  });
  rows.appendChild(clone);
  initQuoteProductSearch(clone);
}
function toggleBilling() {
  const cb  = document.getElementById('diffBilling');
  const sec = document.getElementById('billingSection');
  if (sec) sec.classList.toggle('hidden', !cb?.checked);
}
function resetQuoteForm() {
  document.getElementById('quoteForm')?.reset();
  document.getElementById('billingSection')?.classList.add('hidden');
  closeQuoteSearchDrops();
}

/* ── Scroll top ──────────────────────────────────────────────── */
function initScrollTop() {
  const btn = document.getElementById('scrollTopFab');
  if (!btn) return;
  window.addEventListener('scroll', () => btn.classList.toggle('visible', window.scrollY > 500));
  btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

/* ── Mobile filter button visibility ────────────────────────── */
function checkMobile() {
  const btn = document.getElementById('mobileFilterBtn');
  if (btn) btn.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
}

/* ── Delivery location modal ─────────────────────────────────── */
const DELIVERY_KEY          = 'sinelec_delivery_location';
const ADDRESS_KEY           = 'sinelec_checkout_addresses';
const SELECTED_ADDRESS_KEY  = 'sinelec_checkout_selected_address';
const DEFAULT_DELIVERY      = 'Delhi 110001';
const DELIVER_TO_AJAX = (function() {
  const p = window.location.pathname;
  if (p.indexOf('/website/') !== -1) return 'ajax/deliver-to';
  if (p.indexOf('/admin/')   !== -1) return '../website/ajax/deliver-to';
  return 'website/ajax/deliver-to';
})();

function escapeHtml(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatDeliveryLabel(fullAddress) {
  const text = (fullAddress || '').trim();
  if (!text) return DEFAULT_DELIVERY;
  if (text.length <= 22) return text;

  const pinMatch = text.match(/\b\d{5,6}\b/);
  const parts = text.split(',').map(s => s.trim()).filter(Boolean);
  const city = parts.length >= 2 ? parts[parts.length - 2] : parts[0];
  if (pinMatch) return `${city} ${pinMatch[0]}`;
  return `${text.slice(0, 20)}…`;
}

function setDeliveryLocation(value) {
  const text = (value || '').trim();
  if (!text) return;
  const locText    = document.getElementById('deliveryLocationText');
  const mobLocText = document.getElementById('mobDeliveryLocationText');
  const display    = formatDeliveryLabel(text);
  if (locText)    locText.textContent    = display;
  if (mobLocText) mobLocText.textContent = display;
  const btn = document.getElementById('headerDeliveryBtn');
  if (btn) btn.setAttribute('title', text);
  try { localStorage.setItem(DELIVERY_KEY, text); } catch {}
}

function closeDeliveryModal() {
  const modal = document.getElementById('deliveryModal');
  if (!modal) return;
  modal.hidden = true;
  document.body.style.overflow = '';
}

/* ── helpers ── */
function dlocFeedback(el, msg, type) {
  if (!el) return;
  el.textContent = msg;
  el.className   = 'dloc-feedback' + (type ? ' is-' + type : '');
}

function dlocAjax(action, body) {
  const fd = new FormData();
  fd.append('action', action);
  Object.entries(body || {}).forEach(([k, v]) => fd.append(k, v));
  return fetch(DELIVER_TO_AJAX, { method: 'POST', body: fd })
    .then(r => r.json());
}

/* ── Tab switching ── */
function dlocSwitchTab(tabId) {
  document.querySelectorAll('.dloc-tab').forEach(btn => {
    const active = btn.dataset.dlocTab === tabId;
    btn.classList.toggle('is-active', active);
    btn.setAttribute('aria-selected', active ? 'true' : 'false');
  });
  document.querySelectorAll('.dloc-panel').forEach(panel => {
    const active = panel.id === 'dloc' + tabId.charAt(0).toUpperCase() + tabId.slice(1) + 'Panel';
    panel.classList.toggle('is-active', active);
    panel.hidden = !active;
  });
  if (tabId === 'saved') dlocLoadSavedAddresses();
}

/* ── Geolocation tab ── */
const GEO_BTN_HTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/><circle cx="12" cy="12" r="9" stroke-width="1.5"/></svg> Use Current Location';

function dlocReverseGeocode(lat, lng) {
  const url = 'https://nominatim.openstreetmap.org/reverse?lat=' + lat + '&lon=' + lng + '&format=json&addressdetails=1';
  return fetch(url, { headers: { 'Accept-Language': 'en' } })
    .then(r => r.json())
    .then(data => {
      const a = data.address || {};
      const suburb   = a.suburb || a.neighbourhood || a.city_district || '';
      const city     = a.city || a.town || a.village || a.county || '';
      const postcode = a.postcode || '';
      const parts = [suburb, city, postcode].filter(Boolean);
      return parts.length ? parts.join(', ') : (data.display_name || '').split(',').slice(0, 2).join(',').trim();
    });
}

function dlocInitGeoTab() {
  const btn      = document.getElementById('dlocGeoBtn');
  const feedback = document.getElementById('dlocGeoFeedback');
  if (!btn) return;

  btn.addEventListener('click', () => {
    if (!navigator.geolocation) {
      dlocFeedback(feedback, 'Geolocation is not supported by your browser.', 'warn');
      return;
    }
    btn.disabled    = true;
    btn.textContent = 'Detecting location…';
    dlocFeedback(feedback, '', '');

    navigator.geolocation.getCurrentPosition(
      pos => {
        const lat = pos.coords.latitude.toFixed(6);
        const lng = pos.coords.longitude.toFixed(6);
        dlocFeedback(feedback, 'GPS detected. Fetching location name…', 'info');

        dlocReverseGeocode(lat, lng)
          .then(locationName => {
            const display = locationName || 'Current Location';
            dlocFeedback(feedback, '📍 ' + display, 'ok');

            return dlocAjax('set_location', { lat, lng, display });
          })
          .then(res => {
            if (res && res.ok) {
              setDeliveryLocation(res.display);
              setTimeout(closeDeliveryModal, 1400);
            }
          })
          .catch(() => {
            dlocFeedback(feedback, 'Location detected. Could not resolve name — using coordinates.', 'info');
            dlocAjax('set_location', { lat, lng, display: 'Current Location' })
              .then(res => { if (res && res.ok) setDeliveryLocation(res.display); })
              .catch(() => {});
            setTimeout(closeDeliveryModal, 1400);
          })
          .finally(() => {
            btn.disabled  = false;
            btn.innerHTML = GEO_BTN_HTML;
          });
      },
      err => {
        const msgs = {
          1: 'Location permission denied. Please allow access in your browser settings.',
          2: 'Location unavailable. Please try again or use postal code.',
          3: 'Location request timed out. Please try again.',
        };
        dlocFeedback(feedback, msgs[err.code] || 'Could not get location. Please try postal code.', 'warn');
        btn.disabled  = false;
        btn.innerHTML = GEO_BTN_HTML;
      },
      { timeout: 12000, maximumAge: 60000 }
    );
  });
}

/* ── Postal code tab ── */
function dlocLookupPostal(postalCode) {
  const url = 'https://nominatim.openstreetmap.org/search?postalcode='
    + encodeURIComponent(postalCode)
    + '&format=json&addressdetails=1&limit=1';
  return fetch(url, { headers: { 'Accept-Language': 'en' } })
    .then(r => r.json())
    .then(data => {
      if (!data || !data.length) return null;
      const a        = data[0].address || {};
      const suburb   = a.suburb || a.neighbourhood || a.city_district || '';
      const city     = a.city || a.town || a.village || a.county || a.state_district || '';
      const postcode = a.postcode || postalCode;
      const parts    = [suburb, city, postcode].filter(Boolean);
      return parts.length ? parts.join(', ') : postcode;
    });
}

function dlocInitPostalTab() {
  const form      = document.getElementById('dlocPostalForm');
  const input     = document.getElementById('dlocPostalInput');
  const feedback  = document.getElementById('dlocPostalFeedback');
  const submitBtn = form?.querySelector('.dloc-postal-btn');
  if (!form) return;

  form.addEventListener('submit', e => {
    e.preventDefault();
    const postal = (input?.value || '').trim();
    if (!postal) {
      dlocFeedback(feedback, 'Please enter a postal code.', 'warn');
      input?.focus();
      return;
    }
    if (submitBtn) submitBtn.disabled = true;
    dlocFeedback(feedback, 'Looking up location…', 'info');

    dlocLookupPostal(postal)
      .then(display => {
        if (!display) {
          dlocFeedback(feedback, 'Postal code not found. Please check and try again.', 'warn');
          return;
        }
        dlocFeedback(feedback, '📍 ' + display, 'ok');
        dlocAjax('set_location', { postal_code: postal, display: display });
        setDeliveryLocation(display);
        setTimeout(closeDeliveryModal, 1400);
      })
      .catch(() => dlocFeedback(feedback, 'Network error. Please try again.', 'warn'))
      .finally(() => { if (submitBtn) submitBtn.disabled = false; });
  });
}

/* ── Saved addresses tab ── */
function dlocLoadSavedAddresses() {
  const loading  = document.getElementById('dlocSavedLoading');
  const list     = document.getElementById('dlocSavedList');
  const feedback = document.getElementById('dlocSavedFeedback');
  if (!list) return;

  if (loading) loading.style.display = 'flex';
  list.hidden = true;

  dlocAjax('get_addresses', {})
    .then(res => {
      if (loading) loading.style.display = 'none';
      if (!res.ok) {
        dlocFeedback(feedback, res.message || 'Could not load addresses.', 'warn');
        return;
      }
      const addrs = res.addresses || [];
      if (!addrs.length) {
        list.innerHTML = '<p class="dloc-panel-desc">No saved addresses found. Click "Add New Address" below.</p>';
        list.hidden = false;
        return;
      }
      list.innerHTML = addrs.map((a, i) => `
        <label class="dloc-addr-item">
          <input class="dloc-addr-radio" type="radio" name="dlocAddress"
                 value="${escapeHtml(a.line)}" data-addr-id="${escapeHtml(String(a.id))}" ${i === 0 ? 'checked' : ''}>
          <span class="dloc-addr-copy">
            <strong class="dloc-addr-name">${escapeHtml(a.name || 'Address ' + (i + 1))}</strong>
            <span class="dloc-addr-line">${escapeHtml(a.line)}</span>
          </span>
        </label>`).join('');
      list.hidden = false;
    })
    .catch(() => {
      if (loading) loading.style.display = 'none';
      dlocFeedback(feedback, 'Network error. Please try again.', 'warn');
    });
}

function dlocInitSavedTab() {
  const list = document.getElementById('dlocSavedList');
  if (!list) return;

  list.addEventListener('change', e => {
    const radio = e.target;
    if (!(radio instanceof HTMLInputElement) || radio.name !== 'dlocAddress') return;
    const addressId = radio.dataset.addrId || '';
    const line      = radio.value;

    document.querySelectorAll('.dloc-addr-item').forEach(item => {
      item.classList.toggle('is-selected', item.querySelector('input') === radio);
    });

    dlocAjax('set_address', { address_id: addressId })
      .then(res => {
        if (res.ok) {
          setDeliveryLocation(res.display || line);
        } else {
          setDeliveryLocation(line);
        }
        setTimeout(closeDeliveryModal, 600);
      })
      .catch(() => {
        setDeliveryLocation(line);
        setTimeout(closeDeliveryModal, 600);
      });
  });
}

/* ── Open / Init ── */
function openDeliveryModal() {
  const modal = document.getElementById('deliveryModal');
  if (!modal) return;
  dlocSwitchTab('geo');
  modal.hidden = false;
  document.body.style.overflow = 'hidden';
}

function openSignInModalFromDelivery() {
  closeDeliveryModal();
  const modal       = document.getElementById('authModal');
  const signInPanel = document.getElementById('authSignInPanel');
  const signUpPanel = document.getElementById('authSignUpPanel');
  const title       = document.getElementById('authModalTitle');
  const desc        = document.getElementById('authModalDesc');
  if (!modal) return;
  signInPanel?.classList.add('is-active');
  signUpPanel?.classList.remove('is-active');
  if (title) title.textContent = 'Sign In';
  if (desc)  desc.textContent  = 'Sign in to continue.';
  modal.hidden = false;
  document.body.style.overflow = 'hidden';
}

function initDeliveryLocationModal() {
  const headerBtn = document.getElementById('headerDeliveryBtn');
  const modal     = document.getElementById('deliveryModal');
  if (!headerBtn || !modal) return;

  try {
    const savedLoc = localStorage.getItem(DELIVERY_KEY);
    if (savedLoc) setDeliveryLocation(savedLoc);
  } catch {}

  headerBtn.addEventListener('click', openDeliveryModal);
  headerBtn.addEventListener('keydown', e => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDeliveryModal(); }
  });

  Array.from(document.querySelectorAll('[data-delivery-close]'))
    .forEach(el => el.addEventListener('click', closeDeliveryModal));

  document.querySelectorAll('.dloc-tab').forEach(btn => {
    btn.addEventListener('click', () => dlocSwitchTab(btn.dataset.dlocTab || 'geo'));
  });

  const loginBtn = document.getElementById('dlocLoginBtn');
  if (loginBtn) loginBtn.addEventListener('click', openSignInModalFromDelivery);

  dlocInitGeoTab();
  dlocInitPostalTab();
  dlocInitSavedTab();

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && modal && !modal.hidden) closeDeliveryModal();
  });
}

/* ── Auth modal ─────────────────────────────────────────────── */
function initAuthModal() {
  const trigger = document.getElementById('headerAccountBtn');
  const modal = document.getElementById('authModal');
  if (!trigger || !modal) return;

  const closeEls = Array.from(document.querySelectorAll('[data-auth-close]'));
  const signInPanel = document.getElementById('authSignInPanel');
  const signUpPanel = document.getElementById('authSignUpPanel');
  const title = document.getElementById('authModalTitle');
  const desc = document.getElementById('authModalDesc');
  const switchBtns = Array.from(document.querySelectorAll('[data-auth-switch]'));
  const tabBtns = Array.from(document.querySelectorAll('.auth-switch-tab'));
  const signInForm = document.getElementById('authSignInForm');
  const forgotBtn = document.getElementById('authForgotBtn');
  const passToggles = Array.from(document.querySelectorAll('[data-toggle-pass]'));

  function switchAuth(mode) {
    const isSignIn = mode !== 'signup';
    signInPanel?.classList.toggle('is-active', isSignIn);
    signUpPanel?.classList.toggle('is-active', !isSignIn);
    if (title) title.textContent = isSignIn ? 'Sign In' : 'Register';
    if (desc) {
      desc.textContent = isSignIn
        ? 'Sign in to continue.'
        : 'Create an account in seconds.';
    }
    tabBtns.forEach(btn => btn.classList.toggle('is-active', (btn.dataset.authSwitch || 'signin') === (isSignIn ? 'signin' : 'signup')));
  }

  function openAuth(mode = 'signin') {
    switchAuth(mode);
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeAuth() {
    modal.hidden = true;
    document.body.style.overflow = '';
  }

  if (!AUTH_STATE.isSignedIn) {
    trigger.addEventListener('click', () => openAuth('signin'));
    trigger.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openAuth('signin');
      }
    });
  }
  closeEls.forEach(el => el.addEventListener('click', closeAuth));
  switchBtns.forEach(btn => btn.addEventListener('click', () => switchAuth(btn.dataset.authSwitch || 'signin')));

  const externalAuthTriggers = Array.from(document.querySelectorAll('[data-auth-open]'));
  externalAuthTriggers.forEach(triggerEl => {
    triggerEl.addEventListener('click', e => {
      e.preventDefault();
      const requestedMode = (triggerEl.dataset.authOpen || 'signin').toLowerCase();
      openAuth(requestedMode === 'signup' ? 'signup' : 'signin');
    });
  });

  forgotBtn?.addEventListener('click', e => {
    e.preventDefault();
    window.location.href = 'forgot-password';
  });

  passToggles.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.passTarget || '';
      const input = document.getElementById(targetId);
      if (!(input instanceof HTMLInputElement)) return;
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  });

  signInForm?.addEventListener('submit', e => {
    const user = document.getElementById('authUserId')?.value.trim();
    const pass = document.getElementById('authPassword')?.value;
    if (!user || !pass) {
      e.preventDefault();
      toast('Please enter your email and password.', 'warn');
    }
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && modal && !modal.hidden) closeAuth();
  });

  window.sinelecOpenAuth = openAuth;
}

function initQuoteAuthGuard() {
  if (AUTH_STATE.isSignedIn) return;

  document.addEventListener('click', e => {
    const link = e.target.closest('a[href="request-a-quote"]');
    if (!link) return;
    e.preventDefault();
    const redirectInput = document.getElementById('authRedirect');
    if (redirectInput) redirectInput.value = 'request-a-quote';
    if (window.sinelecOpenAuth) window.sinelecOpenAuth('signin');
  }, true);
}

function initAccountMenu() {
  if (!AUTH_STATE.isSignedIn) return;

  const trigger = document.getElementById('headerAccountBtn');
  const menu = document.getElementById('accountMenu');
  if (!trigger || !menu) return;

  function openMenu() {
    menu.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
  }

  function closeMenu() {
    menu.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
  }

  function toggleMenu() {
    if (menu.hidden) openMenu();
    else closeMenu();
  }

  trigger.addEventListener('click', e => {
    e.preventDefault();
    e.stopPropagation();
    toggleMenu();
  });

  trigger.addEventListener('keydown', e => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggleMenu();
    } else if (e.key === 'Escape') {
      closeMenu();
    }
  });

  menu.addEventListener('click', e => e.stopPropagation());
  document.addEventListener('click', e => {
    if (!menu.hidden && !e.target.closest('.header-account-wrap')) {
      closeMenu();
    }
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !menu.hidden) closeMenu();
  });
}

/* ═══════════════════════════════════════════════════════════════
   DOMContentLoaded — page-aware init
═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {

  /* ── Common init (every page) ─────────────────────────────── */
  updateCartUI();
  initGlobalLoader();
  initScrollTop();
  renderCompareBar();
  initDeliveryLocationModal();
  initAuthModal();
  initAccountMenu();
  initQuoteAuthGuard();
  _loadWishlistFromServer();
  _processPendingWish();

  if (window.FLASH_TOAST && window.FLASH_TOAST.message) {
    const message = String(window.FLASH_TOAST.message || '').trim();
    const type = String(window.FLASH_TOAST.type || 'ok').trim().toLowerCase();
    if (message) showToastMessage(message, type);
  }

  /* ── Search field wiring ── */
  const sf = document.getElementById('searchField');
  if (sf) {
    sf.addEventListener('input', onSearchInput);
    sf.addEventListener('focus', () => {
      /* Re-open if already has query */
      const q = sf.value.trim();
      if (q.length >= 2 && _srItems.length) {
        document.getElementById('searchDrop')?.classList.add('open');
      }
    });
    sf.addEventListener('keydown', e => {
      const box = document.getElementById('searchDrop');
      const isOpen = box?.classList.contains('open');
      if (e.key === 'ArrowDown')  { e.preventDefault(); _srMoveFocus(1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); _srMoveFocus(-1); }
      else if (e.key === 'Escape') { _srClose(); sf.blur(); }
      else if (e.key === 'Enter') {
        if (isOpen && _srActiveIdx >= 0 && _srItems[_srActiveIdx]) {
          e.preventDefault();
          const it = _srItems[_srActiveIdx];
          _srSelectItem(it.ID, encodeURIComponent(it.CODE));
        } else {
          _srClose();
          /* let form submit naturally */
        }
      }
    });
  }
  /* Close dropdown on outside click */
  document.addEventListener('click', e => {
    if (!e.target.closest('#headerSearch')) _srClose();
  });

  /* Mega menu */
  const productsNavItem = document.getElementById('productsNavItem');
  productsNavItem?.addEventListener('click', e => e.stopPropagation());
  productsNavItem?.addEventListener('mouseenter', () => productsNavItem.classList.add('open'));
  productsNavItem?.addEventListener('mouseleave', () => closeProductsMenu());
  document.addEventListener('click', e => {
    if (!productsNavItem?.contains(e.target)) closeProductsMenu();
  });
  document.querySelectorAll('.mega-cat').forEach(cat => {
    cat.addEventListener('mouseenter', () => activateMegaCategory(cat));
    cat.addEventListener('focus', () => activateMegaCategory(cat));
    cat.addEventListener('click', e => {
      const isAlreadyActive = cat.classList.contains('active');
      if (!isAlreadyActive) {
        e.preventDefault();
        activateMegaCategory(cat);
      }
    });
  });

  /* ── Page-specific init ───────────────────────────────────── */
  switch (CURRENT_PAGE) {

    case 'home':
      startHero();

      {
        /* Fetch real New Products (label='New') from DB via catalog AJAX */
        const _newUrl = `${_catAjaxBase()}?action=products&is_new=1&per_page=20&sort=new`;
        fetch(_newUrl)
          .then(r => r.json())
          .then(data => {
            if (data.ok && data.products && data.products.length) {
              const newProds = data.products.map(_normProduct);
              _registerProducts(newProds);
              renderFeaturedCarousel('newArrivalsTrack', newProds, 'minimal');
            }
          })
          .catch(() => {});
      }

      {
        const dealTabs = Array.from(document.querySelectorAll('.deal-tab[href^="#"]'));
        dealTabs.forEach(tab => {
          tab.addEventListener('click', () => {
            dealTabs.forEach(x => x.classList.remove('active'));
            tab.classList.add('active');
          });
        });
      }

      document.getElementById('nlForm')?.addEventListener('submit', function(e) {
        var input = this.querySelector('input[type="email"]');
        if (!input || !input.value.trim()) { e.preventDefault(); return; }
        var btn = this.querySelector('button[type="submit"]');
        if (btn) { btn.disabled = true; btn.textContent = 'Subscribing…'; }
      });
      break;

    case 'products':
    case 'new-arrivals':
      loadCatalogInit().then(() => renderCatalog());
      window.addEventListener('resize', checkMobile);
      checkMobile();

      document.getElementById('sortSel')?.addEventListener('change', e => { S.sort = e.target.value; renderCatalog(); });
      document.querySelectorAll('.vbtn').forEach(b => {
        b.addEventListener('click', () => {
          document.querySelectorAll('.vbtn').forEach(x => x.classList.remove('on'));
          b.classList.add('on');
          S.view = b.dataset.view;
          renderCatalog();
        });
      });
      document.getElementById('filterStock')?.addEventListener('change', e => { S.filters.inStock = e.target.checked; renderCatalog(); });
      document.getElementById('filterNew')?.addEventListener('change',   e => { S.filters.isNew   = e.target.checked; renderCatalog(); });
      break;

    case 'product':
      initPDPStars();
      initRelatedProducts();
      document.getElementById('pdpWishBtn')?.classList.toggle('wished', CURRENT_PRODUCT && S.wishIds.includes(CURRENT_PRODUCT.id));
      break;

    case 'chip-programming':
    case 'services':
      renderServices();
      break;

    case 'request-a-quote':
    case 'quote':
      initQuoteProductSearch();
      document.addEventListener('click', e => {
        if (!e.target.closest('.quote-product-search')) closeQuoteSearchDrops();
      });
      document.getElementById('quoteForm')?.addEventListener('submit', e => {
        e.preventDefault();
        toast("Quote request submitted! We'll contact you within 24 hours.", 'ok');
        e.target.reset();
        document.getElementById('billingSection')?.classList.add('hidden');
      });
      break;

    case 'checkout':
      initCheckoutPage();
      break;

    case 'about':
    case 'contact':
      initAboutTeamCarousel();
      document.getElementById('contactForm')?.addEventListener('submit', e => {
        e.preventDefault();
        toast("Message sent! We'll reply within 24h.", 'ok');
        e.target.reset();
      });
      break;
  }
});
