<?php
require_once __DIR__ . '/account-helpers.php';
$user = sinelec_require_login();
$currentPage = 'my-list';
$pageTitle = 'My List | Sinelec Technologies';
require_once __DIR__ . '/header.php';

/* ── Dummy quotes data ── */
$quotes = [
  [
    'id'       => 'QT-2024-00183',
    'date'     => '12 May 2025',
    'product'  => 'STM32F407VGT6 — ARM Cortex-M4 Microcontroller',
    'part_no'  => 'STM32F407VGT6',
    'qty'      => 500,
    'status'   => 'responded',
    'history'  => [
      ['date' => '12 May 2025, 09:14', 'event' => 'Quote requested',           'note' => '500 units of STM32F407VGT6'],
      ['date' => '12 May 2025, 11:30', 'event' => 'Under review by sales team', 'note' => ''],
      ['date' => '13 May 2025, 14:22', 'event' => 'Quote sent to your email',  'note' => 'Unit price: €4.85 | Lead time: 4–6 weeks'],
    ],
  ],
  [
    'id'       => 'QT-2024-00179',
    'date'     => '08 May 2025',
    'product'  => 'ATMEGA328P-PU — 8-bit AVR Microcontroller, DIP-28',
    'part_no'  => 'ATMEGA328P-PU',
    'qty'      => 1000,
    'status'   => 'pending',
    'history'  => [
      ['date' => '08 May 2025, 16:45', 'event' => 'Quote requested', 'note' => '1000 units of ATMEGA328P-PU'],
    ],
  ],
  [
    'id'       => 'QT-2024-00162',
    'date'     => '01 Apr 2025',
    'product'  => 'LM358DR — Dual Op-Amp, SOIC-8',
    'part_no'  => 'LM358DR',
    'qty'      => 2000,
    'status'   => 'closed',
    'history'  => [
      ['date' => '01 Apr 2025, 10:00', 'event' => 'Quote requested',           'note' => '2000 units'],
      ['date' => '02 Apr 2025, 09:15', 'event' => 'Under review by sales team', 'note' => ''],
      ['date' => '03 Apr 2025, 13:00', 'event' => 'Quote sent to your email',  'note' => 'Unit price: €0.28 | Lead time: 1–2 weeks'],
      ['date' => '05 Apr 2025, 11:22', 'event' => 'Order placed by customer',  'note' => 'Order #ORD-2025-00441 created'],
      ['date' => '06 Apr 2025, 08:50', 'event' => 'Quote closed',              'note' => 'Converted to order'],
    ],
  ],
  [
    'id'       => 'QT-2024-00155',
    'date'     => '18 Mar 2025',
    'product'  => 'IRF540NPBF — N-Channel Power MOSFET, TO-220',
    'part_no'  => 'IRF540NPBF',
    'qty'      => 300,
    'status'   => 'in_review',
    'history'  => [
      ['date' => '18 Mar 2025, 14:30', 'event' => 'Quote requested',           'note' => '300 units of IRF540NPBF'],
      ['date' => '19 Mar 2025, 10:05', 'event' => 'Under review by sales team', 'note' => 'Checking stock availability'],
    ],
  ],
];

$statusMeta = [
  'pending'   => ['label' => 'Pending',    'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fcd34d'],
  'in_review' => ['label' => 'In Review',  'color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
  'responded' => ['label' => 'Responded',  'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#86efac'],
  'closed'    => ['label' => 'Closed',     'color' => '#6b7280', 'bg' => '#f9fafb', 'border' => '#d1d5db'],
];
?>

<main class="account-page">
  <div class="wrap">
    <div class="account-shell">
      <?php sinelec_render_account_nav($currentPage); ?>

      <section class="account-main">
        <article class="account-panel ml-shell">

          <div class="ml-page-head">
            <div>
              <h1>My List</h1>
              <p>Manage your saved products and track quote requests.</p>
            </div>
          </div>

          <!-- ── Top-level tabs: Wishlist / Quotes ── -->
          <div class="ml-tab-row" role="tablist" aria-label="My List tabs">
            <button type="button" class="ml-tab-btn is-active" data-ml-tab="wishlist" role="tab" aria-selected="true">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              My Wishlist
              <span class="ml-tab-count" id="mlWishlistCount">0</span>
            </button>
            <button type="button" class="ml-tab-btn" data-ml-tab="quotes" role="tab" aria-selected="false">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              Quotes
              <span class="ml-tab-count"><?= count($quotes) ?></span>
            </button>
          </div>

          <!-- ── Wishlist Panel ── -->
          <div class="ml-panel is-active" id="mlWishlistPanel" data-ml-panel="wishlist">
            <div id="mlWishlistGrid" class="ml-wl-grid"></div>
            <div id="mlWishlistEmpty" class="ml-empty" hidden>
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              <p>Your wishlist is empty.</p>
              <a href="products" class="ml-empty-btn">Browse Products</a>
            </div>
          </div>

          <!-- ── Quotes Panel ── -->
          <div class="ml-panel" id="mlQuotesPanel" data-ml-panel="quotes" hidden>
            <div class="ml-qt-grid">
              <?php foreach ($quotes as $qt):
                $sm = $statusMeta[$qt['status']] ?? $statusMeta['pending'];
              ?>
              <article class="ml-qt-card">

                <!-- Quote header -->
                <div class="ml-qt-head">
                  <div class="ml-qt-head-left">
                    <span class="ml-qt-id"><?= htmlspecialchars($qt['id']) ?></span>
                    <span class="ml-qt-date">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                      <?= htmlspecialchars($qt['date']) ?>
                    </span>
                  </div>
                  <span class="ml-qt-badge" style="color:<?= $sm['color'] ?>;background:<?= $sm['bg'] ?>;border-color:<?= $sm['border'] ?>">
                    <?php if ($qt['status'] === 'pending'):   ?><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><?php endif; ?>
                    <?php if ($qt['status'] === 'in_review'): ?><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><?php endif; ?>
                    <?php if ($qt['status'] === 'responded'): ?><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><?php endif; ?>
                    <?php if ($qt['status'] === 'closed'):    ?><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg><?php endif; ?>
                    <?= $sm['label'] ?>
                  </span>
                </div>

                <!-- Product info -->
                <div class="ml-qt-product">
                  <div class="ml-qt-product-name"><?= htmlspecialchars($qt['product']) ?></div>
                  <div class="ml-qt-product-meta">
                    <span>Part No: <strong><?= htmlspecialchars($qt['part_no']) ?></strong></span>
                    <span>Qty Requested: <strong><?= number_format($qt['qty']) ?></strong></span>
                  </div>
                </div>

                <!-- History timeline -->
                <div class="ml-qt-history">
                  <div class="ml-qt-history-toggle" data-qt-toggle="<?= htmlspecialchars($qt['id']) ?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                    Quote History
                    <svg class="ml-qt-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                  </div>
                  <ul class="ml-qt-timeline" id="qt-history-<?= htmlspecialchars($qt['id']) ?>" hidden>
                    <?php foreach ($qt['history'] as $i => $h): ?>
                    <li class="ml-qt-tl-item<?= $i === count($qt['history']) - 1 ? ' is-last' : '' ?>">
                      <span class="ml-qt-tl-dot"></span>
                      <div class="ml-qt-tl-body">
                        <span class="ml-qt-tl-event"><?= htmlspecialchars($h['event']) ?></span>
                        <span class="ml-qt-tl-date"><?= htmlspecialchars($h['date']) ?></span>
                        <?php if ($h['note']): ?>
                        <span class="ml-qt-tl-note"><?= htmlspecialchars($h['note']) ?></span>
                        <?php endif; ?>
                      </div>
                    </li>
                    <?php endforeach; ?>
                  </ul>
                </div>

              </article>
              <?php endforeach; ?>
            </div>
          </div>

        </article>
      </section>
    </div>
  </div>
</main>

<style>
/* ── Shell ──────────────────────────────────────────── */
.ml-shell {
  padding: 18px;
  background: #f3f5f7;
  border-radius: 20px;
}
.ml-page-head { margin-bottom: 14px; }
.ml-page-head h1 {
  font-size: clamp(1.2rem, 2vw, 1.6rem);
  color: #182a43;
  font-weight: 700;
}
.ml-page-head p { margin-top: 4px; color: #5f728b; font-size: 12px; }

/* ── Top tabs ────────────────────────────────────────── */
.ml-tab-row {
  display: inline-flex;
  gap: 6px;
  padding: 5px;
  border-radius: 14px;
  background: #e8edf3;
  border: 1px solid #d6dde6;
  margin-bottom: 16px;
}
.ml-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  min-height: 38px;
  padding: 0 18px;
  border-radius: 10px;
  border: 1px solid transparent;
  background: transparent;
  color: #324b69;
  font-size: 12px;
  font-weight: 700;
  transition: all .18s;
  cursor: pointer;
}
.ml-tab-btn.is-active {
  background: #fff;
  color: #112d4b;
  border-color: #d3dbe5;
  box-shadow: 0 4px 10px rgba(20,33,56,.06);
}
.ml-tab-btn svg { flex-shrink: 0; opacity: .65; }
.ml-tab-btn.is-active svg { opacity: 1; }
.ml-tab-count {
  min-width: 20px;
  height: 18px;
  padding: 0 6px;
  border-radius: 20px;
  background: #dde4ef;
  color: #2d4b6e;
  font-size: 10px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.ml-tab-btn.is-active .ml-tab-count { background: #2563eb; color: #fff; }

.ml-panel { display: none; }
.ml-panel.is-active { display: block; }

/* ── Wishlist grid ───────────────────────────────────── */
.ml-wl-grid {
  display: grid;
  gap: 10px;
}
.ml-wl-card {
  display: grid;
  grid-template-columns: 90px minmax(0,1fr) minmax(130px,.4fr);
  gap: 14px;
  align-items: start;
  padding: 14px 16px;
  background: #fff;
  border: 1px solid #d3d9e0;
  border-radius: 10px;
  transition: box-shadow .15s;
}
.ml-wl-card:hover { box-shadow: 0 4px 16px rgba(15,30,55,.08); }
.ml-wl-img-wrap {
  width: 80px;
  height: 80px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #dde4ec;
  background: #f4f6f9;
  flex-shrink: 0;
}
.ml-wl-img-wrap img { width: 100%; height: 100%; object-fit: contain; padding: 6px; }
.ml-wl-info { min-width: 0; }
.ml-wl-name {
  font-size: 14px;
  font-weight: 700;
  color: #1a2d42;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 5px;
}
.ml-wl-meta {
  font-size: 11.5px;
  color: #5f728b;
  line-height: 1.5;
}
.ml-wl-meta span { display: inline-block; margin-right: 12px; }
.ml-wl-actions { display: flex; flex-direction: column; gap: 8px; align-items: flex-end; padding-top: 4px; }
.ml-wl-price { font-size: 17px; font-weight: 700; color: #1a2d42; }
.ml-wl-price small { font-size: 11px; color: #7a92ab; font-weight: 500; display: block; }
.ml-wl-btn-cart {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 34px;
  padding: 0 14px;
  border-radius: 8px;
  background: #2563eb;
  color: #fff;
  font-size: 11.5px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: background .15s;
  white-space: nowrap;
}
.ml-wl-btn-cart:hover { background: #1d4ed8; }
.ml-wl-btn-remove {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 30px;
  padding: 0 12px;
  border-radius: 7px;
  border: 1.5px solid #e0e6ee;
  background: #fff;
  color: #dc2626;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color .15s, background .15s;
  white-space: nowrap;
}
.ml-wl-btn-remove:hover { border-color: #dc2626; background: #fff5f5; }

.ml-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 52px 20px;
  background: #fff;
  border: 1px dashed #d0d9e4;
  border-radius: 12px;
  text-align: center;
  color: #b0bdcb;
}
.ml-empty svg { opacity: .4; }
.ml-empty p { font-size: 13px; color: #7a92ab; margin: 0; }
.ml-empty-btn {
  display: inline-flex;
  align-items: center;
  height: 34px;
  padding: 0 20px;
  border-radius: 8px;
  background: #2563eb;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  transition: background .15s;
}
.ml-empty-btn:hover { background: #1d4ed8; }

/* ── Quotes grid ─────────────────────────────────────── */
.ml-qt-grid { display: grid; gap: 12px; }
.ml-qt-card {
  background: #fff;
  border: 1px solid #d3d9e0;
  border-radius: 10px;
  overflow: hidden;
}
.ml-qt-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 16px;
  background: #f8fafc;
  border-bottom: 1px solid #edf0f5;
  flex-wrap: wrap;
}
.ml-qt-head-left {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}
.ml-qt-id {
  font-size: 13px;
  font-weight: 700;
  color: #1a2d42;
  letter-spacing: .01em;
}
.ml-qt-date {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11.5px;
  color: #6b82a0;
}
.ml-qt-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 26px;
  padding: 0 11px;
  border-radius: 6px;
  border: 1px solid;
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: .02em;
  white-space: nowrap;
}
.ml-qt-product { padding: 13px 16px 0; }
.ml-qt-product-name {
  font-size: 14px;
  font-weight: 700;
  color: #1a2d42;
  line-height: 1.35;
  margin-bottom: 6px;
}
.ml-qt-product-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 18px;
  font-size: 12px;
  color: #5f728b;
}
.ml-qt-product-meta strong { color: #1a2d42; }

/* Quote history timeline */
.ml-qt-history { padding: 10px 16px 14px; margin-top: 6px; }
.ml-qt-history-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  font-weight: 700;
  color: #2563eb;
  cursor: pointer;
  user-select: none;
  padding: 4px 0;
}
.ml-qt-history-toggle:hover { color: #1d4ed8; }
.ml-qt-chevron { transition: transform .2s; flex-shrink: 0; }
.ml-qt-history-toggle.is-open .ml-qt-chevron { transform: rotate(180deg); }
.ml-qt-timeline {
  margin-top: 12px;
  padding-left: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.ml-qt-tl-item {
  display: flex;
  gap: 12px;
  position: relative;
  padding-bottom: 14px;
}
.ml-qt-tl-item:last-child { padding-bottom: 0; }
.ml-qt-tl-dot {
  flex-shrink: 0;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #fff;
  border: 3px solid #2563eb;
  margin-top: 3px;
  position: relative;
  z-index: 1;
}
.ml-qt-tl-item.is-last .ml-qt-tl-dot { background: #2563eb; }
.ml-qt-tl-item::before {
  content: '';
  position: absolute;
  left: 5px;
  top: 14px;
  bottom: 0;
  width: 2px;
  background: #dde4ef;
}
.ml-qt-tl-item.is-last::before { display: none; }
.ml-qt-tl-body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.ml-qt-tl-event { font-size: 12.5px; font-weight: 700; color: #1a2d42; line-height: 1.3; }
.ml-qt-tl-date  { font-size: 11px;   color: #7a92ab; }
.ml-qt-tl-note  { font-size: 11.5px; color: #3a5168; margin-top: 2px; background: #f0f4f8; padding: 4px 8px; border-radius: 5px; }

/* ── Responsive ─────────────────────────────────────── */
@media (max-width: 900px) {
  .ml-tab-row { width: 100%; display: grid; grid-template-columns: 1fr 1fr; }
  .ml-tab-btn { width: 100%; justify-content: center; }
  .ml-wl-card { grid-template-columns: 80px minmax(0,1fr); gap: 12px; padding: 12px; }
  .ml-wl-actions { flex-direction: row; align-items: center; grid-column: 1 / -1; border-top: 1px dashed #d6dde6; padding-top: 10px; margin-top: 2px; justify-content: space-between; }
  .ml-wl-price small { display: inline; margin-left: 4px; }
}
@media (max-width: 640px) {
  .ml-shell { padding: 12px; }
  .ml-page-head h1 { font-size: 1rem; }
  .ml-tab-btn { min-height: 34px; font-size: 11px; padding: 0 10px; gap: 5px; }
  .ml-tab-btn svg { display: none; }
  .ml-wl-name { font-size: 13px; }
  .ml-wl-meta { font-size: 11px; }
  .ml-qt-id { font-size: 12px; }
  .ml-qt-product-name { font-size: 13px; }
}
@media (max-width: 480px) {
  .ml-wl-card { grid-template-columns: 70px minmax(0,1fr); gap: 10px; padding: 10px; }
  .ml-wl-img-wrap { width: 64px; height: 64px; }
  .ml-wl-btn-cart, .ml-wl-btn-remove { height: 30px; font-size: 11px; padding: 0 10px; }
  .ml-qt-head { padding: 10px 12px; }
  .ml-qt-product { padding: 10px 12px 0; }
  .ml-qt-history { padding: 8px 12px 12px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

  /* ── Tab switching ── */
  var tabBtns  = document.querySelectorAll('.ml-tab-btn');
  var panels   = document.querySelectorAll('.ml-panel');
  tabBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = btn.dataset.mlTab;
      tabBtns.forEach(function (b) {
        b.classList.toggle('is-active', b === btn);
        b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
      });
      panels.forEach(function (p) {
        var active = p.dataset.mlPanel === target;
        p.classList.toggle('is-active', active);
        p.hidden = !active;
      });
    });
  });

  /* ── Quote history toggles ── */
  document.querySelectorAll('[data-qt-toggle]').forEach(function (tog) {
    tog.addEventListener('click', function () {
      var id      = tog.dataset.qtToggle;
      var ul      = document.getElementById('qt-history-' + id);
      var isOpen  = !ul.hidden;
      ul.hidden   = isOpen;
      tog.classList.toggle('is-open', !isOpen);
    });
  });

  /* ── Wishlist (localStorage) ── */
  var WISHLIST_KEY  = 'sinelec_wishlist';
  var grid          = document.getElementById('mlWishlistGrid');
  var emptyMsg      = document.getElementById('mlWishlistEmpty');
  var countBadge    = document.getElementById('mlWishlistCount');

  var DEMO_WISHLIST = [
    {
      id: 'wl_001',
      name: 'STM32F103C8T6 — ARM Cortex-M3 MCU, LQFP-48',
      partNo: 'STM32F103C8T6',
      brand: 'STMicroelectronics',
      price: '€2.45',
      priceNote: 'per unit, MOQ 10',
      image: 'https://via.placeholder.com/120x120/f0f4f9/2563eb?text=STM32'
    },
    {
      id: 'wl_002',
      name: 'ESP32-WROOM-32E — Wi-Fi + BT Module, SMD',
      partNo: 'ESP32-WROOM-32E',
      brand: 'Espressif Systems',
      price: '€3.90',
      priceNote: 'per unit, MOQ 5',
      image: 'https://via.placeholder.com/120x120/f0f4f9/2563eb?text=ESP32'
    },
    {
      id: 'wl_003',
      name: 'LM317T — Adjustable Voltage Regulator, TO-220',
      partNo: 'LM317T',
      brand: 'Texas Instruments',
      price: '€0.68',
      priceNote: 'per unit, MOQ 25',
      image: 'https://via.placeholder.com/120x120/f0f4f9/2563eb?text=LM317'
    }
  ];

  function esc(v) {
    return String(v || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function loadWishlist() {
    try {
      var s = JSON.parse(localStorage.getItem(WISHLIST_KEY) || '[]');
      return Array.isArray(s) ? s : [];
    } catch(e) { return []; }
  }
  function saveWishlist(list) {
    localStorage.setItem(WISHLIST_KEY, JSON.stringify(list));
  }
  function removeItem(id) {
    var list = loadWishlist().filter(function(i){ return i.id !== id; });
    saveWishlist(list);
    renderWishlist();
  }

  function renderWishlist() {
    var list = loadWishlist();
    if (!list.length) {
      list = DEMO_WISHLIST.map(function(d){ return Object.assign({}, d); });
      saveWishlist(list);
    }
    if (countBadge) countBadge.textContent = list.length;
    if (!list.length) {
      grid.innerHTML = '';
      emptyMsg.hidden = false;
      return;
    }
    emptyMsg.hidden = true;
    grid.innerHTML = list.map(function(item) {
      return '<article class="ml-wl-card">'
        + '<div class="ml-wl-img-wrap"><img src="' + esc(item.image || '') + '" alt="' + esc(item.name) + '" loading="lazy" onerror="this.src=\'../assets/no-image.png\'"></div>'
        + '<div class="ml-wl-info">'
        +   '<div class="ml-wl-name">' + esc(item.name) + '</div>'
        +   '<div class="ml-wl-meta">'
        +     '<span>Part No: <strong>' + esc(item.partNo || '') + '</strong></span>'
        +     (item.brand ? '<span>Brand: <strong>' + esc(item.brand) + '</strong></span>' : '')
        +   '</div>'
        + '</div>'
        + '<div class="ml-wl-actions">'
        +   '<div><div class="ml-wl-price">' + esc(item.price || '—') + (item.priceNote ? '<small>' + esc(item.priceNote) + '</small>' : '') + '</div></div>'
        +   '<button type="button" class="ml-wl-btn-cart" onclick="if(typeof addToCart===\'function\')addToCart(' + JSON.stringify(item.id) + ')else alert(\'Added to cart!\')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg> Add to Cart</button>'
        +   '<button type="button" class="ml-wl-btn-remove" data-remove-id="' + esc(item.id) + '"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg> Remove</button>'
        + '</div>'
        + '</article>';
    }).join('');

    grid.querySelectorAll('[data-remove-id]').forEach(function(btn) {
      btn.addEventListener('click', function(){ removeItem(btn.dataset.removeId); });
    });
  }

  renderWishlist();
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
