/* ============================================================
   SINELEC TECH — Main App (multi-page PHP architecture)
   ============================================================ */

/* ── Data bridge (PHP → JS) ──────────────────────────────────── */
const STORE_DATA    = window.STORE_DATA    || { categories:[], manufacturers:[], products:[], services:[], testimonials:[], banners:[] };
const CURRENT_PAGE  = window.CURRENT_PAGE  || 'home';
const AUTH_STATE    = window.SINELEC_AUTH  || { isSignedIn:false };
const CATALOG_INIT  = window.CATALOG_INIT  || { cat:'', mfr:'', q:'', subcat:'', isNew:false };
const CURRENT_PRODUCT = window.CURRENT_PRODUCT || null;
const EUR_RATE = 0.0093;
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

/* ── State ───────────────────────────────────────────────────── */
const S = {
  catFilter:    CATALOG_INIT.cat    || null,
  subcatFilter: CATALOG_INIT.subcat || null,
  mfrFilter:    CATALOG_INIT.mfr    || null,
  query:        CATALOG_INIT.q      || '',
  expandedCats: CATALOG_INIT.cat ? [CATALOG_INIT.cat] : [],
  filters: {
    mfrs:        CATALOG_INIT.mfr ? [CATALOG_INIT.mfr] : [],
    minP:        0,
    maxP:        999999,
    inStock:     false,
    isNew:       CATALOG_INIT.isNew || false,
    minRating:   0,
    packages:    [],
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
  wishIds:        JSON.parse(localStorage.getItem('sinelec_wish') || '[]'),
  recentlyViewed: JSON.parse(localStorage.getItem('sinelec_rv')   || '[]'),
};

function saveWish() { localStorage.setItem('sinelec_wish', JSON.stringify(S.wishIds)); }
function saveRV()   { localStorage.setItem('sinelec_rv',   JSON.stringify(S.recentlyViewed)); }

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
        <img class="cart-item-img" src="${item.image}" alt="${item.name}" onerror="this.src='https://placehold.co/72x72/EBF3FF/0066CC?text=${encodeURIComponent(item.sku)}'">
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
        <div class="cart-item-price">₹${(item.price * item.qty).toFixed(2)}</div>
      </div>`).join('');
  }
  const sub   = cartSubtotal();
  const ship  = sub >= 5000 ? 0 : 99;
  const gst   = sub * 0.18;
  const total = sub + ship + gst;
  const setT  = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  setT('cartSub',   `₹${sub.toFixed(2)}`);
  setT('cartShip',  ship === 0 ? 'FREE' : `₹${ship.toFixed(2)}`);
  setT('cartGST',   `₹${gst.toFixed(2)}`);
  setT('cartTotal', `₹${total.toFixed(2)}`);
  const freeShip = document.getElementById('cartFreeShip');
  if (freeShip) freeShip.classList.toggle('hidden', sub < 5000);
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
    const sub = selectedSubtotal();
    const ship = shippingCharge(sub);
    const baseGst = sub * 0.18;
    const gst = vatApplied ? 0 : baseGst;
    const total = sub + ship + gst;
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
          <img src="${item.image}" alt="${item.name}" onerror="this.src='https://placehold.co/68x68/EBF3FF/0066CC?text=${encodeURIComponent(item.sku)}'">
          <div>
            <div class="checkout-item-head">
              <div class="checkout-item-left">
                <input type="checkbox" class="checkout-item-select" data-checkout-select="${item.id}" ${selectedCheckoutIds.has(item.id) ? 'checked' : ''}>
                <div class="checkout-item-sku">${item.sku}</div>
              </div>
              <button type="button" class="checkout-item-remove" data-checkout-remove="${item.id}" aria-label="Remove product">${ic('trash', 14, 14)}</button>
            </div>
            <div class="checkout-item-name">${item.name}</div>
            <div class="checkout-item-meta">Unit: ₹${item.price.toFixed(2)}</div>
            <div class="checkout-item-controls">
              <button type="button" class="checkout-qty-btn" data-checkout-qty="${item.id}" data-delta="-1">−</button>
              <span class="checkout-qty-num">${item.qty}</span>
              <button type="button" class="checkout-qty-btn" data-checkout-qty="${item.id}" data-delta="1">+</button>
            </div>
          </div>
          <div class="checkout-item-price">₹${(item.qty * item.price).toFixed(2)}</div>
        </div>
      `).join('');
    }

    if (selectedAddressText) selectedAddressText.textContent = selectedAddress ? `${selectedAddress.label} · ${selectedAddress.line}` : 'Select an address';
    if (shippingText) shippingText.textContent = shippingLabel;
    if (paymentText) paymentText.textContent = paymentMap[currentPaymentMode()] || 'PayPal';
    if (subtotalEl) subtotalEl.textContent = `₹${sub.toFixed(2)}`;
    if (shippingCostEl) shippingCostEl.textContent = ship === 0 ? 'FREE' : `₹${ship.toFixed(2)}`;
    if (taxEl) {
      if (vatApplied && baseGst > 0) {
        taxEl.innerHTML = `<span class="checkout-tax-old">₹${baseGst.toFixed(2)}</span><span class="checkout-tax-applied">${ic('check', 12, 12)}VAT applied</span>`;
      } else {
        taxEl.textContent = `₹${gst.toFixed(2)}`;
      }
    }
    if (totalEl) totalEl.textContent = `₹${total.toFixed(2)}`;
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
function toggleWish(id) {
  const idx = S.wishIds.indexOf(id);
  if (idx > -1) { S.wishIds.splice(idx, 1); toast('Removed from wishlist', 'warn'); }
  else          { S.wishIds.push(id);        toast('Saved to wishlist ♥', 'ok');    }
  saveWish();
  document.querySelectorAll(`.pcard-wish[data-id="${id}"]`).forEach(b => {
    b.classList.toggle('wished', S.wishIds.includes(id));
    b.title = S.wishIds.includes(id) ? 'Remove from wishlist' : 'Add to wishlist';
  });
}

/* ── Compare ─────────────────────────────────────────────────── */
function toggleCompare(id) {
  const p = STORE_DATA.products.find(p => p.id === id);
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
    const p = S.compareIds[i] ? STORE_DATA.products.find(x => x.id === S.compareIds[i]) : null;
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
function pCard(p, listView = false, sponsored = false, variant = 'default') {
  const price = p.priceBreaks ? p.priceBreaks[0].price : (p.price || 0);
  const si    = p.stock > 100 ? 'in-stock' : p.stock > 0 ? 'low-stock' : 'out-stock';
  const siLbl = p.stock > 100 ? 'In Stock' : p.stock > 0 ? `Only ${p.stock} left` : 'Unavailable';
  const wished = S.wishIds.includes(p.id);
  const categoryName = CATEGORY_NAME_MAP[p.category] || p.category || 'Components';
  const primaryBadge = p.isNew ? 'new' : (p.badge || '');
  const badgeTextMap = {
    new: 'New',
    popular: 'Popular',
    bestseller: 'Best Seller',
    featured: 'Featured',
    hot: 'Hot',
    sale: 'Sale',
  };
  const badgeText = primaryBadge ? (badgeTextMap[primaryBadge] || String(primaryBadge)) : '';
  const eurPrice = EUR_FORMAT.format(price * EUR_RATE);
  const eurOrig = p.originalPrice ? EUR_FORMAT.format(p.originalPrice * EUR_RATE) : '';
  const eurLowBreak = p.priceBreaks?.length > 1
    ? EUR_FORMAT.format(p.priceBreaks[p.priceBreaks.length - 1].price * EUR_RATE)
    : '';
  const isDetailOnly = variant === 'detail-link';

  return `
  <div class="pcard${listView ? ' list-v' : ''}${isDetailOnly ? ' pcard-detail-link' : ''}" data-id="${p.id}">
    <div class="pcard-img-wrap" onclick="openPDP(${p.id})" style="cursor:pointer">
      <img class="pcard-img" src="${p.image}" alt="${p.name}" loading="lazy"
           onerror="this.src='https://placehold.co/300x300/EBF3FF/0066CC?text=${encodeURIComponent(p.sku)}'">
      <div class="pcard-badges">
        ${primaryBadge ? `<span class="pbadge pbadge-${primaryBadge}">${badgeText}</span>` : ''}
      </div>
      <button class="pcard-wish ${wished ? 'wished' : ''}" data-id="${p.id}"
              onclick="event.stopPropagation();toggleWish(${p.id})"
              title="${wished ? 'Remove from wishlist' : 'Add to wishlist'}">
        ${ic('heart', 13, 13)}
      </button>
    </div>
    <div class="pcard-body" onclick="openPDP(${p.id})" style="cursor:pointer">
      <h3 class="pcard-name">${p.name}</h3>
      <div class="pcard-cat-rating">
        <div class="pcard-cat">${categoryName}</div>
        <div class="pcard-rating">
          ${stars(p.rating)}
          <span class="pcard-rc">${(p.reviewCount || p.reviews || 0).toLocaleString()}</span>
        </div>
      </div>
      ${listView && p.package ? `<div class="pcard-specs"><span class="spec-tag">${p.package}</span></div>` : ''}
      <div class="pcard-price-row">
        <span class="price-main">${eurPrice}</span>
        ${p.originalPrice ? `<span class="price-orig">${eurOrig}</span>` : ''}
      </div>
      ${p.priceBreaks?.length > 1 ? `<div class="pcard-price-break">As low as <strong>${eurLowBreak}</strong> for ${p.priceBreaks[p.priceBreaks.length - 1].qty}+ units</div>` : ''}
      <div class="pcard-stock ${si}">${siLbl}</div>
      <div class="pcard-delivery">${ic('truck', 11, 11)} FREE delivery by <strong>${delivEst()}</strong></div>
    </div>
    <div class="pcard-footer${isDetailOnly ? ' pcard-footer--single' : ''}">
      ${isDetailOnly ? `
      <button class="btn-view-detail" onclick="event.stopPropagation();openPDP(${p.id})">
        Click Here
      </button>` : `
      <button class="btn-atc" onclick="atcClick(event,${p.id})">
        ${ic('cart', 14, 14)} Add to Cart
      </button>
      <button class="btn-buynow" onclick="event.stopPropagation();atcClick(event,${p.id});openCart()">
        Buy Now
      </button>`}
    </div>
  </div>`;
}

function atcClick(e, id) {
  e.stopPropagation();
  const p = STORE_DATA.products.find(x => x.id === id);
  if (!p) return;
  cartAdd(p, 1);
  const btn = e.currentTarget;
  btn.classList.add('added');
  btn.innerHTML = `${ic('check', 14, 14)} Added!`;
  setTimeout(() => { btn.classList.remove('added'); btn.innerHTML = `${ic('cart', 14, 14)} Add to Cart`; }, 1500);
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
}

function pdpBuyNow() {
  if (!CURRENT_PRODUCT) return;
  const qty = parseInt(document.getElementById('pdpQty')?.value || 1);
  cartAdd(CURRENT_PRODUCT, qty);
  openCart();
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
  const track = document.getElementById('relatedTrack');
  if (!track || !CURRENT_PRODUCT) return;
  const related = STORE_DATA.products
    .filter(p => p.category === CURRENT_PRODUCT.category && p.id !== CURRENT_PRODUCT.id)
    .slice(0, 6);
  track.innerHTML = related.map(p => pCard(p)).join('');
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

/* ── Search suggestions ──────────────────────────────────────── */
let searchDebounce;
function onSearchInput(e) {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => showSearchDrop(e.target.value.trim()), 180);
}
function showSearchDrop(q) {
  const box = document.getElementById('searchDrop');
  if (!q || q.length < 2) { box?.classList.remove('open'); return; }
  const res = STORE_DATA.products.filter(p =>
    p.name.toLowerCase().includes(q.toLowerCase()) ||
    p.sku.toLowerCase().includes(q.toLowerCase()) ||
    (p.manufacturer || p.brand || '').toLowerCase().includes(q.toLowerCase())
  ).slice(0, 7);
  if (!box || !res.length) { box?.classList.remove('open'); return; }
  box.innerHTML = res.map(p => `
    <div class="sdrop-item" onclick="openPDP(${p.id});document.getElementById('searchDrop').classList.remove('open')">
      <img class="sdrop-img" src="${p.image}" alt="${p.name}" onerror="this.src='https://placehold.co/44/EBF3FF/0066CC?text=${encodeURIComponent(p.sku)}'">
      <div class="sdrop-info">
        <div class="sdrop-sku">${p.sku}</div>
        <div class="sdrop-name">${p.name}</div>
      </div>
      <div class="sdrop-price">₹${(p.priceBreaks ? p.priceBreaks[0].price : p.price).toFixed(2)}</div>
    </div>`).join('') +
    `<div class="sdrop-footer" onclick="showPageLoader('Loading search results...');window.location.href='products?q=${encodeURIComponent(q)}'">See all results for "${q}" →</div>`;
  box.classList.add('open');
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
function filteredProducts() {
  let P = [...STORE_DATA.products];
  if (S.catFilter)                  P = P.filter(p => p.category === S.catFilter);
  if (S.subcatFilter)               P = P.filter(p => getProductSubcategory(p).toLowerCase() === S.subcatFilter.toLowerCase());
  const q = S.query || S.filters.filterQuery;
  if (q)                            P = P.filter(p =>
    p.name.toLowerCase().includes(q.toLowerCase()) ||
    p.sku.toLowerCase().includes(q.toLowerCase()) ||
    (p.manufacturer || p.brand || '').toLowerCase().includes(q.toLowerCase()) ||
    (p.description || '').toLowerCase().includes(q.toLowerCase())
  );
  if (S.filters.mfrs.length > 0)   P = P.filter(p => S.filters.mfrs.includes(p.manufacturer || p.brand));
  if (S.filters.minRating > 0)     P = P.filter(p => (p.rating || 0) >= S.filters.minRating);
  if (S.filters.packages.length > 0) P = P.filter(p => S.filters.packages.includes(p.package));
  P = P.filter(p => {
    const price = p.priceBreaks ? p.priceBreaks[0].price : (p.price || 0);
    return price >= S.filters.minP && price <= S.filters.maxP;
  });
  if (S.filters.inStock) P = P.filter(p => p.stock > 0);
  if (S.filters.isNew)   P = P.filter(p => p.isNew);
  switch (S.sort) {
    case 'price-asc':  P.sort((a, b) => (a.priceBreaks?.[0].price||a.price) - (b.priceBreaks?.[0].price||b.price)); break;
    case 'price-desc': P.sort((a, b) => (b.priceBreaks?.[0].price||b.price) - (a.priceBreaks?.[0].price||a.price)); break;
    case 'rating':     P.sort((a, b) => b.rating - a.rating); break;
    case 'name':       P.sort((a, b) => a.name.localeCompare(b.name)); break;
    case 'new':        P.sort((a, b) => (b.isNew ? 1 : 0) - (a.isNew ? 1 : 0)); break;
    default:           P.sort((a, b) => (b.isFeatured ? 1 : 0) - (a.isFeatured ? 1 : 0));
  }
  return P;
}

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

function renderCatalog() {
  const allP = filteredProducts();
  const catName = S.catFilter ? (STORE_DATA.categories.find(c => c.id === S.catFilter)?.name || 'Products') : 'All Products';
  const subcatLabel = S.subcatFilter || '';

  /* clamp page */
  const totalPages = Math.max(1, Math.ceil(allP.length / S.perPage));
  if (S.page > totalPages) S.page = totalPages;
  if (S.page < 1) S.page = 1;
  const P = allP.slice((S.page - 1) * S.perPage, S.page * S.perPage);

  const bc = document.getElementById('catalogBC');
  if (bc) bc.innerHTML = `
    <a href="index">${ic('home', 12, 12)} Home</a>
    <span class="breadcrumb-sep">›</span>
    <a href="products">Products</a>
    ${S.catFilter ? `<span class="breadcrumb-sep">›</span><span class="breadcrumb-cur">${catName}</span>` : ''}
    ${S.subcatFilter ? `<span class="breadcrumb-sep">›</span><span class="breadcrumb-cur">${subcatLabel}</span>` : ''}
    ${S.query ? `<span class="breadcrumb-sep">›</span><span class="breadcrumb-cur">Search: "${S.query}"</span>` : ''}`;

  renderToolbarPagination(allP.length, S.page, S.perPage);

  const grid = document.getElementById('prodGrid');
  if (!grid) return;
  grid.className = S.view === 'list' ? 'prod-list' : 'prod-grid';

  const bottomPg = document.getElementById('catalogPagination');

  if (allP.length === 0) {
    grid.innerHTML = `<div class="catalog-empty"><div class="catalog-empty-icon">🔍</div><p class="catalog-empty-title">No results found</p><p class="catalog-empty-sub">Try different keywords or adjust your filters</p><button class="btn btn-blue" onclick="clearFilters()">Clear All Filters</button></div>`;
    if (bottomPg) bottomPg.style.display = 'none';
  } else {
    grid.innerHTML = P.map((p, i) => pCard(p, S.view === 'list', i % 5 === 0)).join('');
    if (bottomPg) bottomPg.style.display = 'none';
  }

  renderFilterSidebar();
  renderActiveFilterTags(allP.length);
}

function renderActiveFilterTags(count) {
  const row = document.getElementById('activeFiltersRow');
  if (!row) return;
  const tags = [];
  if (S.catFilter) {
    const name = STORE_DATA.categories.find(c => c.id === S.catFilter)?.name || S.catFilter;
    tags.push(`<span class="af-tag">Category: ${name} <button onclick="S.catFilter=null;S.subcatFilter=null;S.expandedCats=[];renderCatalog()">&times;</button></span>`);
  }
  if (S.subcatFilter) tags.push(`<span class="af-tag">Type: ${S.subcatFilter} <button onclick="S.subcatFilter=null;renderCatalog()">&times;</button></span>`);
  S.filters.mfrs.forEach(m => tags.push(`<span class="af-tag">${m} <button onclick="toggleMfr('${m}',false);document.querySelector('.filter-item input[onchange*=\\'${m}\\']').checked=false">&times;</button></span>`));
  if (S.filters.minRating > 0) tags.push(`<span class="af-tag">${S.filters.minRating}★ & Up <button onclick="setRatingFilter(0)">&times;</button></span>`);
  S.filters.packages.forEach(pkg => tags.push(`<span class="af-tag">${pkg} <button onclick="togglePackage('${pkg}',false)">&times;</button></span>`));
  if (S.filters.minP > 0 || S.filters.maxP < 999999) tags.push(`<span class="af-tag">₹${S.filters.minP}–${S.filters.maxP < 999999 ? S.filters.maxP : '∞'} <button onclick="S.filters.minP=0;S.filters.maxP=999999;document.getElementById('priceMin').value='';document.getElementById('priceMax').value='';renderCatalog()">&times;</button></span>`);
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
  renderPackageFilters();
}

function renderCatFilters() {
  const catEl = document.getElementById('catFilters');
  if (!catEl) return;

  /* "All" row */
  let html = `
    <div class="cat-row cat-all-row ${!S.catFilter ? 'active' : ''}"
         onclick="S.catFilter=null;S.subcatFilter=null;S.expandedCats=[];renderCatalog()">
      <span class="cat-row-name">All Categories</span>
      <span class="filter-count">${STORE_DATA.products.length}</span>
    </div>`;

  STORE_DATA.categories.forEach(c => {
    const catProducts = STORE_DATA.products.filter(p => p.category === c.id);
    const catCount = catProducts.length;
    const isSelected = S.catFilter === c.id;
    const isExpanded = S.expandedCats.includes(c.id);

    /* build subcategory counts */
    const subcatMap = {};
    catProducts.forEach(p => {
      const sub = getProductSubcategory(p);
      if (sub) subcatMap[sub] = (subcatMap[sub] || 0) + 1;
    });
    const subcats = Object.entries(subcatMap).sort((a, b) => b[1] - a[1]);
    const hasSubcats = subcats.length > 0;

    html += `
      <div class="cat-accordion ${isExpanded ? 'expanded' : ''}" data-cat-id="${c.id}">
        <div class="cat-row ${isSelected && !S.subcatFilter ? 'active' : ''}"
             onclick="selectCategory('${c.id}')">
          <span class="cat-row-name">${c.name}</span>
          <span class="filter-count">${catCount}</span>
          ${hasSubcats ? `<span class="acc-toggle" onclick="event.stopPropagation();toggleCatAccordion('${c.id}')">
            <svg class="acc-chevron" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </span>` : ''}
        </div>
        ${hasSubcats ? `<div class="cat-subcats">
          ${subcats.map(([sub, cnt]) => `
            <div class="subcat-row ${isSelected && (S.subcatFilter || '').toLowerCase() === sub.toLowerCase() ? 'active' : ''}"
                 onclick="selectSubcat('${c.id}','${sub.replace(/'/g, "\\'")}')">
              <span>${sub}</span>
              <span class="filter-count">${cnt}</span>
            </div>`).join('')}
        </div>` : ''}
      </div>`;
  });

  catEl.innerHTML = html;
}

function selectCategory(catId) {
  S.catFilter = catId; S.subcatFilter = null; S.page = 1;
  if (!S.expandedCats.includes(catId)) S.expandedCats.push(catId);
  renderCatalog();
}

function selectSubcat(catId, subcat) {
  S.catFilter = catId; S.subcatFilter = subcat; S.page = 1;
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
  const mfrs = [...new Set(
    STORE_DATA.products
      .filter(p => !S.catFilter || p.category === S.catFilter)
      .map(p => p.manufacturer || p.brand || '')
      .filter(Boolean)
  )].sort();
  const mfrEl = document.getElementById('mfrFilters');
  if (!mfrEl) return;
  mfrEl.innerHTML = mfrs.map(m => {
    const cnt = STORE_DATA.products.filter(p =>
      (p.manufacturer || p.brand) === m && (!S.catFilter || p.category === S.catFilter)
    ).length;
    return `<label class="filter-item">
      <input type="checkbox" class="filter-check" ${S.filters.mfrs.includes(m) ? 'checked' : ''}
             onchange="toggleMfr('${m}',this.checked)">
      <span>${m}</span>
      <span class="filter-count">${cnt}</span>
    </label>`;
  }).join('');
}

function renderRatingFilters() {
  const ratingEl = document.getElementById('ratingFilters');
  if (!ratingEl) return;
  const ratingOpts = [4, 3, 2];
  ratingEl.innerHTML = `
    <label class="filter-item">
      <input type="radio" class="filter-check" name="ratingFilter" ${S.filters.minRating === 0 ? 'checked' : ''}
             onchange="setRatingFilter(0)">
      <span>Any Rating</span>
    </label>` +
    ratingOpts.map(r => {
      const cnt = STORE_DATA.products.filter(p =>
        (p.rating || 0) >= r && (!S.catFilter || p.category === S.catFilter)
      ).length;
      return `<label class="filter-item rating-filter-item">
        <input type="radio" class="filter-check" name="ratingFilter" ${S.filters.minRating === r ? 'checked' : ''}
               onchange="setRatingFilter(${r})">
        <span class="rf-label"><span class="rf-stars">${'★'.repeat(r)}${'☆'.repeat(5-r)}</span><span class="rf-up"> &amp; Up</span></span>
        <span class="filter-count">${cnt}</span>
      </label>`;
    }).join('');
}

function setRatingFilter(r) {
  S.filters.minRating = r;
  renderCatalog();
}

function renderPackageFilters() {
  const pkgEl = document.getElementById('pkgFilters');
  if (!pkgEl) return;
  const products = S.catFilter
    ? STORE_DATA.products.filter(p => p.category === S.catFilter)
    : STORE_DATA.products;
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
  S.filters.maxP = parseFloat(document.getElementById('priceMax')?.value) || 999999;
  renderCatalog();
}

function toggleMfr(m, checked) {
  if (checked) { if (!S.filters.mfrs.includes(m)) S.filters.mfrs.push(m); }
  else S.filters.mfrs = S.filters.mfrs.filter(x => x !== m);
  renderCatalog();
}

function clearFilters() {
  S.catFilter    = null;
  S.subcatFilter = null;
  S.mfrFilter    = null;
  S.query        = '';
  S.expandedCats = [];
  S.filters      = { mfrs: [], minP: 0, maxP: 999999, inStock: false, isNew: false, minRating: 0, packages: [], filterQuery: '' };
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
  el.innerHTML = STORE_DATA.categories.map(c => `
    <a href="products?cat=${c.id}" class="cat-card">
      <div class="cat-icon">${ic(catIcons[c.id] || 'cpu', 22, 22)}</div>
      <div class="cat-name">${c.name}</div>
      <div class="cat-count">${c.count} products</div>
    </a>`).join('');
}

function renderFeaturedCarousel(elId, products, variant = 'default') {
  const el = document.getElementById(elId);
  if (!el) return;
  el.innerHTML = products.map(p => pCard(p, false, false, variant)).join('');
}

function renderFlashDeals() {
  const el = document.getElementById('flashDeals');
  if (!el) return;
  const deals = STORE_DATA.products.filter(p => p.originalPrice).slice(0, 5);
  el.innerHTML = deals.map(p => {
    return `
    <div class="fdeal-card" onclick="openPDP(${p.id})">
      <div class="fdeal-img">
        <img src="${p.image}" alt="${p.name}" onerror="this.src='https://placehold.co/200/EBF3FF/0066CC?text=${encodeURIComponent(p.sku)}'">
      </div>
      <div class="fdeal-body">
        <div class="fdeal-name">${p.name}</div>
        <div class="fdeal-price">₹${(p.priceBreaks ? p.priceBreaks[0].price : p.price).toFixed(2)}<small>₹${p.originalPrice.toFixed(2)}</small></div>
      </div>
    </div>`;
  }).join('');
}

function renderTopDeals() {
  const el = document.getElementById('topDeals');
  if (!el) return;
  const deals = STORE_DATA.products.filter(p => p.originalPrice).slice(0, 4);
  el.innerHTML = deals.map(p => {
    const price = p.priceBreaks ? p.priceBreaks[0].price : p.price;
    return `
    <div class="deal-card" onclick="openPDP(${p.id})">
      <div class="deal-card-img"><img src="${p.image}" alt="${p.name}" onerror="this.src='https://placehold.co/200/EBF3FF/0066CC?text=${encodeURIComponent(p.sku)}'"></div>
      <div class="deal-card-body">
        <div class="deal-card-name">${p.name}</div>
        <div class="deal-card-stars">${stars(p.rating, 12)}</div>
        <div class="deal-card-price">
          <span class="deal-price-main">₹${price.toFixed(2)}</span>
          <span class="deal-price-orig">₹${p.originalPrice.toFixed(2)}</span>
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
  const q = query.trim().toLowerCase();
  const categoryId = getQuoteSelectedCategoryId(row);
  let products = [...STORE_DATA.products];

  if (categoryId) {
    products = products.filter(p => p.category === categoryId);
  }

  if (!q) return products.slice(0, 8);

  return products.filter(p =>
    p.name.toLowerCase().includes(q) ||
    p.sku.toLowerCase().includes(q) ||
    (p.manufacturer || '').toLowerCase().includes(q)
  ).slice(0, 8);
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
      <img src="${p.image}" alt="${p.name}" onerror="this.src='https://placehold.co/42x42/EBF3FF/0066CC?text=${encodeURIComponent(p.sku)}'">
      <div class="quote-product-item-info">
        <div class="quote-product-item-sku">${p.sku}</div>
        <div class="quote-product-item-name">${p.name}</div>
      </div>
      <div class="quote-product-item-meta">₹${(p.priceBreaks ? p.priceBreaks[0].price : p.price).toFixed(2)}</div>
    </div>
  `).join('');

  drop.querySelectorAll('.quote-product-item').forEach(item => {
    item.addEventListener('click', () => {
      const product = STORE_DATA.products.find(p => p.id === Number(item.dataset.productId));
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
const DELIVERY_KEY = 'sinelec_delivery_location';
const ADDRESS_KEY = 'sinelec_checkout_addresses';
const SELECTED_ADDRESS_KEY = 'sinelec_checkout_selected_address';
const DEFAULT_DELIVERY = 'Delhi 110001';

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
  const locText = document.getElementById('deliveryLocationText');
  const mobLocText = document.getElementById('mobDeliveryLocationText');
  const display = formatDeliveryLabel(text);
  if (locText) locText.textContent = display;
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

function openSignInModalFromDelivery() {
  const modal = document.getElementById('authModal');
  if (!modal) return;

  const signInPanel = document.getElementById('authSignInPanel');
  const signUpPanel = document.getElementById('authSignUpPanel');
  const title = document.getElementById('authModalTitle');
  const desc = document.getElementById('authModalDesc');
  const tabBtns = Array.from(document.querySelectorAll('.auth-switch-tab'));

  signInPanel?.classList.add('is-active');
  signUpPanel?.classList.remove('is-active');
  if (title) title.textContent = 'Sign In';
  if (desc) desc.textContent = 'Sign in to continue.';
  tabBtns.forEach(btn => btn.classList.toggle('is-active', (btn.dataset.authSwitch || 'signin') === 'signin'));

  modal.hidden = false;
  document.body.style.overflow = 'hidden';
}

function loadSavedDeliveryAddresses() {
  try {
    const stored = JSON.parse(localStorage.getItem(ADDRESS_KEY) || '[]');
    return Array.isArray(stored) ? stored : [];
  } catch {
    return [];
  }
}

function getDefaultAddressId(addresses) {
  if (!Array.isArray(addresses) || !addresses.length) return '';
  const selectedId = localStorage.getItem(SELECTED_ADDRESS_KEY) || '';
  if (selectedId && addresses.some(item => item && item.id === selectedId)) {
    return selectedId;
  }
  const explicitDefault = addresses.find(item => item && item.isDefault);
  return explicitDefault?.id || addresses[0].id || '';
}

function formatAddressPreview(address = {}) {
  const label = (address.label || 'HOME').toString().trim().toUpperCase();
  const name = (address.name || '').toString().trim();
  const phone = (address.phone || '').toString().trim();
  const line = (address.line || '').toString().trim();
  return {
    id: (address.id || '').toString(),
    label,
    name,
    phone,
    line
  };
}

function renderDeliveryAddressList(addressList) {
  if (!addressList) return;

  const addresses = loadSavedDeliveryAddresses().map(formatAddressPreview).filter(item => item.id && item.line);
  if (!addresses.length) {
    addressList.innerHTML = '<div class="delivery-empty-row">No saved address found. Add a new address below.</div>';
    return;
  }

  const defaultId = getDefaultAddressId(addresses);
  addressList.innerHTML = addresses.map(item => {
    const isDefault = item.id === defaultId;
    const safeValue = escapeHtml(item.line);
    const safeLabel = escapeHtml(item.label);
    const safeName = escapeHtml(item.name);
    const safePhone = escapeHtml(item.phone);

    return `
      <label class="delivery-address-item">
        <input type="radio" name="deliveryAddress" value="${safeValue}" data-address-id="${escapeHtml(item.id)}" ${isDefault ? 'checked' : ''}>
        <span class="delivery-address-main">
          <strong>${safeLabel}${isDefault ? ' · Default' : ''}</strong>
          <small>${safeName}${safePhone ? ' · ' + safePhone : ''}</small>
          <small>${safeValue}</small>
        </span>
      </label>
    `;
  }).join('');

  const checked = addressList.querySelector('input[name="deliveryAddress"]:checked');
  if (checked instanceof HTMLInputElement) {
    setDeliveryLocation(checked.value);
  }
}

function openDeliveryModal() {
  if (!AUTH_STATE.isSignedIn) {
    openSignInModalFromDelivery();
    return;
  }
  const modal = document.getElementById('deliveryModal');
  if (!modal) return;
  const addressList = document.getElementById('deliveryAddressList');
  renderDeliveryAddressList(addressList);
  modal.hidden = false;
  document.body.style.overflow = 'hidden';
}

function initDeliveryLocationModal() {
  const headerBtn = document.getElementById('headerDeliveryBtn');
  const modal = document.getElementById('deliveryModal');
  const closeEls = Array.from(document.querySelectorAll('[data-delivery-close]'));
  const addressList = document.getElementById('deliveryAddressList');
  if (!headerBtn || !modal) return;

  try {
    const savedLoc = localStorage.getItem(DELIVERY_KEY);
    if (savedLoc) setDeliveryLocation(savedLoc);
  } catch {}

  headerBtn.addEventListener('click', openDeliveryModal);
  headerBtn.addEventListener('keydown', e => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      openDeliveryModal();
    }
  });
  closeEls.forEach(el => el.addEventListener('click', closeDeliveryModal));

  addressList?.addEventListener('change', e => {
    const target = e.target;
    if (!(target instanceof HTMLInputElement)) return;
    if (target.name !== 'deliveryAddress') return;
    const addressId = target.dataset.addressId || '';
    if (addressId) {
      try { localStorage.setItem(SELECTED_ADDRESS_KEY, addressId); } catch {}
    }
    setDeliveryLocation(target.value);
    closeDeliveryModal();
  });

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

  if (window.FLASH_TOAST && window.FLASH_TOAST.message) {
    const message = String(window.FLASH_TOAST.message || '').trim();
    const type = String(window.FLASH_TOAST.type || 'ok').trim().toLowerCase();
    if (message) showToastMessage(message, type);
  }

  /* Search */
  const sf = document.getElementById('searchField');
  sf?.addEventListener('input', onSearchInput);
  sf?.addEventListener('keydown', e => { if (e.key === 'Enter') e.target.form?.submit(); });
  document.addEventListener('click', e => {
    if (!e.target.closest('.header-search')) {
      document.getElementById('searchDrop')?.classList.remove('open');
    }
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
        const basePrice = p => (p.priceBreaks?.[0]?.price || p.price || 0);
        const discountPct = p => disc(basePrice(p), p.originalPrice || 0) || 0;
        const discountedProducts = STORE_DATA.products
          .filter(p => (p.originalPrice || 0) > basePrice(p));

        const flashDealProducts = discountedProducts
          .slice()
          .sort((a, b) => discountPct(b) - discountPct(a))
          .slice(0, 14);
        const featuredProducts = STORE_DATA.products
          .filter(p => p.badge === 'featured' || p.badge === 'popular' || Number(p.rating || 0) >= 4.7)
          .slice(0, 14);
        const bestSellerProducts = STORE_DATA.products
          .filter(p => p.badge === 'bestseller' || Number(p.reviews || 0) >= 600)
          .slice(0, 14);
        const newArrivalProducts = STORE_DATA.products
          .slice()
          .sort((a, b) => {
            if ((b.isNew ? 1 : 0) !== (a.isNew ? 1 : 0)) return (b.isNew ? 1 : 0) - (a.isNew ? 1 : 0);
            return Number(b.id || 0) - Number(a.id || 0);
          })
          .slice(0, 20);

        renderFeaturedCarousel('flashDealsTrack', flashDealProducts.length ? flashDealProducts : STORE_DATA.products.slice(0, 14));
        renderFeaturedCarousel('featuredTrack', featuredProducts.length ? featuredProducts : STORE_DATA.products.slice(0, 14));
        renderFeaturedCarousel('bestsellerTrack', bestSellerProducts.length ? bestSellerProducts : STORE_DATA.products.slice(0, 14));
        renderFeaturedCarousel('newArrivalsTrack', newArrivalProducts.length ? newArrivalProducts : STORE_DATA.products.slice(0, 20), 'detail-link');
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

      document.getElementById('nlForm')?.addEventListener('submit', e => {
        e.preventDefault();
        toast('Subscribed! Thank you.', 'ok');
        e.target.reset();
      });
      break;

    case 'products':
    case 'new-arrivals':
      renderCatalog();
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
