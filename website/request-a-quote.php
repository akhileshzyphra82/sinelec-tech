<?php
require_once __DIR__ . '/account-helpers.php';
require_once __DIR__ . '/../controller/website_controller.php';

$currentPage = 'request-a-quote';
$pageTitle   = 'Request a Quote — Sinelec Tech';

$controller  = new WebsiteController();
$company     = $controller->getCompanyInfo();
$categories  = $controller->getQuoteCategories();
$isSignedIn  = sinelec_is_signed_in();
$signedUser  = sinelec_get_signed_in_user();

require_once 'header.php';

/* ── Contact sidebar data from tbl_company ───────── */
$_q_phone  = htmlspecialchars((string)($company->CONTACT_NUMBER  ?? ''));
$_q_wp     = htmlspecialchars((string)($company->WHATSAPP_NUMBER ?? ''));
$_q_email  = htmlspecialchars((string)($company->EMAIL           ?? ''));
$_q_sup    = htmlspecialchars((string)($company->SUPPORT_MAIL_ID ?? ''));
$_q_fax    = htmlspecialchars((string)($company->FAX             ?? ''));
$_q_hrs    = htmlspecialchars((string)($company->OFFICE_HRS      ?? ''));
$_q_addr   = htmlspecialchars((string)($company->ADDRESS         ?? ''));
$_q_wpLink = 'https://wa.me/'.preg_replace('/[^0-9]/', '', (string)($company->WHATSAPP_NUMBER ?? ''));

/* Category map for JS */
$catList = [];
foreach ($categories as $c) {
    $catList[] = [
        'id'   => (int)(float)($c->PRODUCT_CATEGORY_ID ?? 0),
        'name' => (string)($c->PRODUCT_CATEGORY_NAME   ?? ''),
    ];
}
?>

<style>
/* ── Hero ──────────────────────────────────────────────────── */
.rq-hero {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #1a56a0 100%);
  color: #fff;
  padding: 52px 0;
  position: relative;
  overflow: hidden;
  text-align: left;
}
.rq-hero::before {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(ellipse 70% 60% at 50% 0%, rgba(59,130,246,.18) 0%, transparent 70%);
  pointer-events: none;
}
.rq-breadcrumb {
  display: flex; align-items: center; gap: 6px;
  font-size: 13px; margin-bottom: 14px; justify-content: flex-start;
}
.rq-breadcrumb a { color: #93c5fd; text-decoration: none; }
.rq-breadcrumb a:hover { text-decoration: underline; }
.rq-breadcrumb-sep { color: rgba(255,255,255,.35); }
.rq-breadcrumb-cur { color: rgba(255,255,255,.6); }
.rq-hero-title {
  font-size: clamp(22px, 4vw, 34px);
  font-weight: 800; line-height: 1.2; letter-spacing: -.5px; margin: 0 0 8px;
}
.rq-hero-sub {
  font-size: 14px; color: rgba(255,255,255,.72); margin: 0;
}

/* ── Layout ────────────────────────────────────────────────── */
.rq-wrap    { padding: 48px 0 64px; }
.rq-layout  { display: grid; grid-template-columns: 1fr 360px; gap: 32px; align-items: start; }
@media (max-width: 960px) { .rq-layout { grid-template-columns: 1fr; } }

/* ── Section cards ─────────────────────────────────────────── */
.rq-card {
  background: #fff;
  border: 1.5px solid #e2e8f0;
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 20px;
}
.rq-card:last-child { margin-bottom: 0; }
.rq-card-head {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px; border-bottom: 1px solid #f1f5f9;
  background: #fafbfc;
}
.rq-card-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: grid; place-items: center; flex-shrink: 0;
}
.rq-card-title  { font-size: 14px; font-weight: 700; color: #0f172a; }
.rq-card-sub    { font-size: 12px; color: #64748b; margin-top: 1px; }
.rq-card-body   { padding: 20px; }

/* ── Form fields ───────────────────────────────────────────── */
.rq-field       { margin-bottom: 14px; }
.rq-field:last-child { margin-bottom: 0; }
.rq-label       { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
.rq-req         { color: #ef4444; margin-left: 2px; }
.rq-opt         { color: #94a3b8; font-weight: 400; }
.rq-inp, .rq-sel, .rq-ta {
  width: 100%; height: 40px; padding: 0 12px;
  border: 1.5px solid #d1d9e2; border-radius: 9px;
  font-size: 13px; color: #1e293b; background: #fff;
  transition: border-color .15s, box-shadow .15s;
  box-sizing: border-box;
}
.rq-ta  { height: auto; padding: 10px 12px; resize: vertical; min-height: 80px; }
.rq-inp:focus, .rq-sel:focus, .rq-ta:focus {
  border-color: #2563eb; outline: none;
  box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.rq-inp::placeholder { color: #b0bac5; }
.rq-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.rq-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
@media (max-width: 680px) { .rq-grid-2, .rq-grid-3 { grid-template-columns: 1fr; } }
.rq-phone-wrap { display: flex; gap: 6px; }
.rq-phone-code { width: 80px; flex-shrink: 0; }
.rq-phone-num  { flex: 1; }

/* ── Product rows ──────────────────────────────────────────── */
.rq-prod-rows   { display: flex; flex-direction: column; gap: 12px; }
.rq-prod-row {
  display: grid;
  grid-template-columns: 40px 1fr 1fr 80px 110px 110px 36px;
  gap: 8px; align-items: center;
  background: #f8fafc; border: 1px solid #e2e8f0;
  border-radius: 10px; padding: 10px 12px;
}
@media (max-width: 900px) {
  .rq-prod-row {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto auto auto;
  }
  .rq-prod-row .rq-row-num  { display: none; }
  .rq-prod-row .rq-row-del  { grid-column: 2; justify-self: end; }
}
@media (max-width: 560px) {
  .rq-prod-row { grid-template-columns: 1fr; }
  .rq-prod-row .rq-row-del { justify-self: start; }
}
.rq-row-num {
  width: 28px; height: 28px; border-radius: 50%;
  background: #e0e7ff; color: #3730a3;
  font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.rq-row-price-disp {
  height: 40px; padding: 0 12px; border-radius: 9px;
  background: #f1f5f9; border: 1.5px solid #e2e8f0;
  display: flex; align-items: center;
  font-size: 13px; font-weight: 600; color: #0f172a;
  white-space: nowrap;
}
.rq-row-total-disp {
  height: 40px; padding: 0 12px; border-radius: 9px;
  background: #eff6ff; border: 1.5px solid #bfdbfe;
  display: flex; align-items: center;
  font-size: 13px; font-weight: 700; color: #1d4ed8;
  white-space: nowrap;
}
.rq-row-del {
  width: 34px; height: 34px; border-radius: 8px;
  border: 1px solid #fecaca; background: #fff5f5;
  color: #ef4444; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background .15s;
}
.rq-row-del:hover { background: #fee2e2; }
.rq-add-row {
  display: inline-flex; align-items: center; gap: 6px;
  margin-top: 4px; padding: 8px 14px;
  border: 1.5px dashed #bfdbfe; border-radius: 9px;
  background: transparent; color: #2563eb;
  font-size: 12px; font-weight: 700; cursor: pointer;
  transition: background .15s;
}
.rq-add-row:hover { background: #eff6ff; }
.rq-subtotal-bar {
  display: flex; align-items: center; justify-content: flex-end;
  gap: 10px; margin-top: 12px; padding-top: 12px;
  border-top: 1.5px dashed #e2e8f0;
}
.rq-subtotal-label { font-size: 13px; color: #64748b; font-weight: 600; }
.rq-subtotal-val {
  font-size: 18px; font-weight: 800; color: #0f172a;
  min-width: 120px; text-align: right;
}

/* ── Auth section ──────────────────────────────────────────── */
.rq-auth-gate {
  display: flex; gap: 16px; padding: 16px;
  background: #f0f9ff; border: 1.5px solid #bae6fd;
  border-radius: 12px; align-items: center; margin-bottom: 18px;
}
.rq-auth-gate-copy { flex: 1; }
.rq-auth-gate-title { font-size: 13px; font-weight: 700; color: #0c4a6e; margin-bottom: 2px; }
.rq-auth-gate-sub   { font-size: 12px; color: #0369a1; }
.rq-auth-gate-btn {
  height: 36px; padding: 0 16px; border-radius: 9px;
  border: none; background: #0369a1; color: #fff;
  font-size: 12px; font-weight: 700; cursor: pointer;
  white-space: nowrap; flex-shrink: 0;
}
.rq-auth-gate-btn:hover { background: #075985; }

.rq-user-badge {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 14px; background: #f0fdf4;
  border: 1.5px solid #bbf7d0; border-radius: 10px;
  margin-bottom: 16px;
}
.rq-user-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: #16a34a; color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 800; flex-shrink: 0;
}
.rq-user-name  { font-size: 13px; font-weight: 700; color: #14532d; }
.rq-user-email { font-size: 12px; color: #15803d; }

/* ── Address selector ──────────────────────────────────────── */
.rq-addr-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 14px; }
.rq-addr-card {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 12px 14px; border: 1.5px solid #e2e8f0;
  border-radius: 10px; cursor: pointer;
  transition: border-color .15s, background .15s;
}
.rq-addr-card.selected, .rq-addr-card:has(input:checked) {
  border-color: #2563eb; background: #eff6ff;
}
.rq-addr-card input[type=radio] { margin-top: 2px; flex-shrink: 0; accent-color: #2563eb; }
.rq-addr-card-body { min-width: 0; }
.rq-addr-label {
  display: inline-block; font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .4px;
  padding: 2px 7px; border-radius: 4px;
  background: #e0e7ff; color: #3730a3; margin-bottom: 4px;
}
.rq-addr-main  { font-size: 13px; color: #0f172a; font-weight: 600; line-height: 1.4; }
.rq-addr-meta  { font-size: 12px; color: #64748b; margin-top: 2px; }
.rq-new-addr-toggle {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 14px; border: 1.5px dashed #bfdbfe;
  border-radius: 10px; cursor: pointer;
  font-size: 13px; font-weight: 600; color: #2563eb;
  background: transparent;
  transition: background .15s;
}
.rq-new-addr-toggle:hover { background: #eff6ff; }
#rqNewAddrForm { margin-top: 12px; display: none; }

/* ── Submit row ────────────────────────────────────────────── */
.rq-submit-row {
  display: flex; gap: 12px; align-items: center;
  padding: 20px; background: #f8fafc;
  border-top: 1.5px solid #e2e8f0; border-radius: 0 0 16px 16px;
  flex-wrap: wrap;
}
.rq-submit-btn {
  height: 48px; padding: 0 32px;
  border-radius: 12px; border: none;
  background: linear-gradient(135deg, #1d4ed8, #2563eb);
  color: #fff; font-size: 14px; font-weight: 700;
  cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
  box-shadow: 0 4px 12px rgba(37,99,235,.3);
  transition: box-shadow .15s, transform .15s;
}
.rq-submit-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(37,99,235,.38); }
.rq-submit-btn:disabled { opacity: .6; cursor: default; transform: none; }
.rq-submit-note { font-size: 12px; color: #64748b; }

/* ── Sidebar ───────────────────────────────────────────────── */
.rq-side-card {
  background: #fff; border: 1.5px solid #e2e8f0;
  border-radius: 14px; padding: 20px;
  margin-bottom: 18px;
}
.rq-side-card:last-child { margin-bottom: 0; }
.rq-side-title { font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 14px; }
.rq-why-list   { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; }
.rq-why-list li {
  display: flex; align-items: center; gap: 8px;
  font-size: 13px; color: #374151;
}
.rq-ci-item {
  display: flex; align-items: flex-start; gap: 10px;
  margin-bottom: 12px;
}
.rq-ci-item:last-child { margin-bottom: 0; }
.rq-ci-icon {
  width: 28px; height: 28px; border-radius: 8px;
  background: #eff6ff; display: grid; place-items: center; flex-shrink: 0;
}
.rq-ci-label  { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 2px; }
.rq-ci-val    { font-size: 13px; color: #1e293b; word-break: break-word; }
.rq-ci-val a  { color: #2563eb; text-decoration: none; }
.rq-ci-val a:hover { text-decoration: underline; }

/* ── Success state ─────────────────────────────────────────── */
.rq-success {
  display: none; text-align: center; padding: 48px 32px;
}
.rq-success-icon {
  width: 72px; height: 72px; border-radius: 50%;
  background: #dcfce7; display: flex; align-items: center; justify-content: center;
  margin: 0 auto 20px;
}
.rq-success-title  { font-size: 22px; font-weight: 800; color: #14532d; margin-bottom: 8px; }
.rq-success-sub    { font-size: 14px; color: #374151; margin-bottom: 24px; }
.rq-success-ref    { display: inline-block; padding: 8px 20px; background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 8px; font-size: 13px; font-weight: 700; color: #16a34a; margin-bottom: 24px; }
.rq-success-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

/* ── Error / info messages ─────────────────────────────────── */
.rq-msg { display: none; padding: 10px 14px; border-radius: 9px; font-size: 13px; margin-top: 12px; }
.rq-msg-err { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
.rq-msg-ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
</style>

<!-- ── Hero ──────────────────────────────────────────────────── -->
<section class="rq-hero">
  <div class="wrap" style="position:relative;">
    <nav class="rq-breadcrumb">
      <a href="index">Home</a>
      <span class="rq-breadcrumb-sep">›</span>
      <span class="rq-breadcrumb-cur">Request a Quote</span>
    </nav>
    <h1 class="rq-hero-title">Request a Quotation</h1>
    <p class="rq-hero-sub">Select your products, enter quantities and we'll get back with the best price.</p>
  </div>
</section>

<!-- ── Body ──────────────────────────────────────────────────── -->
<div class="wrap">
<div class="rq-wrap">
<div class="rq-layout">

  <!-- ══ LEFT: FORM ════════════════════════════════════════════ -->
  <div>

    <!-- Success state (hidden until submission) -->
    <div class="rq-card rq-success" id="rqSuccess">
      <div class="rq-success-icon">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div class="rq-success-title">Quote Request Submitted!</div>
      <div class="rq-success-sub">Your quotation has been received. Our team will review and respond within 24 hours.</div>
      <div class="rq-success-ref" id="rqSuccessRef">Reference: #—</div>
      <div class="rq-success-actions">
        <a href="index" class="qsubmit-btn" style="text-decoration:none;">Back to Home</a>
        <?php if ($isSignedIn): ?>
        <a href="quotation" class="qclear-btn" style="text-decoration:none;">View My Quotes</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── 1. PRODUCTS ─────────────────────────────────────── -->
    <div class="rq-card" id="rqFormCard">
      <div class="rq-card-head">
        <div class="rq-card-icon" style="background:#eff6ff;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
        </div>
        <div>
          <div class="rq-card-title">Products</div>
          <div class="rq-card-sub">Select category, product and quantity for each item</div>
        </div>
      </div>
      <div class="rq-card-body">

        <!-- Column headers (desktop) -->
        <div class="rq-prod-row" style="background:transparent;border:none;padding:0 12px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">
          <div></div>
          <div>Category</div>
          <div>Product</div>
          <div>Qty</div>
          <div>Unit Price</div>
          <div>Line Total</div>
          <div></div>
        </div>

        <div class="rq-prod-rows" id="rqProdRows"></div>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:12px;">
          <button type="button" class="rq-add-row" onclick="rqAddRow()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Another Product
          </button>
          <div class="rq-subtotal-bar" style="margin-top:0;border:none;padding:0;">
            <span class="rq-subtotal-label">Subtotal:</span>
            <span class="rq-subtotal-val" id="rqSubtotal">€0.00</span>
          </div>
        </div>
        <div id="rqProdErr" class="rq-msg rq-msg-err"></div>
      </div>
    </div>

    <!-- ── 2. YOUR DETAILS ──────────────────────────────────── -->
    <div class="rq-card">
      <div class="rq-card-head">
        <div class="rq-card-icon" style="background:#f0fdf4;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
          <div class="rq-card-title">Your Details</div>
          <div class="rq-card-sub">Contact and delivery address</div>
        </div>
      </div>
      <div class="rq-card-body">

        <?php if ($isSignedIn): ?>
        <!-- ── LOGGED IN ── -->
        <div class="rq-user-badge">
          <div class="rq-user-avatar"><?= htmlspecialchars(strtoupper(substr($signedUser['name'] ?? 'U', 0, 1))) ?></div>
          <div>
            <div class="rq-user-name"><?= htmlspecialchars($signedUser['name'] ?? '') ?></div>
            <div class="rq-user-email"><?= htmlspecialchars($signedUser['email'] ?? '') ?></div>
          </div>
        </div>

        <div class="rq-field">
          <label class="rq-label">Company Name <span class="rq-opt">(optional)</span></label>
          <input type="text" class="rq-inp" id="rqCompany" placeholder="Your company / organisation"
                 value="<?= htmlspecialchars($signedUser['company_name'] ?? '') ?>">
        </div>

        <!-- Address selector -->
        <div class="rq-field">
          <label class="rq-label">Delivery Address <span class="rq-req">*</span></label>
          <div class="rq-addr-list" id="rqAddrList">
            <div style="font-size:13px;color:#94a3b8;">Loading saved addresses…</div>
          </div>
          <button type="button" class="rq-new-addr-toggle" onclick="rqToggleNewAddr()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add a new delivery address
          </button>
          <div id="rqNewAddrForm">
            <?php include_once __DIR__ . '/../partials/address_fields.php'; ?>
          </div>
        </div>

        <?php else: ?>
        <!-- ── NOT LOGGED IN ── -->
        <div class="rq-auth-gate">
          <div class="rq-auth-gate-copy">
            <div class="rq-auth-gate-title">Already have an account?</div>
            <div class="rq-auth-gate-sub">Sign in to use saved addresses and track this quote.</div>
          </div>
          <button type="button" class="rq-auth-gate-btn" onclick="rqOpenLogin()">Sign In</button>
        </div>

        <div class="rq-grid-2">
          <div class="rq-field">
            <label class="rq-label">Full Name <span class="rq-req">*</span></label>
            <input type="text" class="rq-inp" id="rqName" placeholder="Your full name" required>
          </div>
          <div class="rq-field">
            <label class="rq-label">Company Name <span class="rq-opt">(optional)</span></label>
            <input type="text" class="rq-inp" id="rqCompany" placeholder="Your company / organisation">
          </div>
        </div>
        <div class="rq-grid-2" style="margin-top:0;">
          <div class="rq-field">
            <label class="rq-label">Email Address <span class="rq-req">*</span></label>
            <input type="email" class="rq-inp" id="rqEmail" placeholder="you@company.com" required>
          </div>
          <div class="rq-field">
            <label class="rq-label">Phone Number <span class="rq-req">*</span></label>
            <div class="rq-phone-wrap">
              <select class="rq-sel rq-phone-code" id="rqPhoneCode">
                <option value="49">+49</option><option value="91">+91</option>
                <option value="1">+1</option><option value="44">+44</option>
                <option value="33">+33</option><option value="65">+65</option>
                <option value="971">+971</option>
              </select>
              <input type="tel" class="rq-inp rq-phone-num" id="rqPhone" placeholder="Phone number" required>
            </div>
          </div>
        </div>
        <div class="rq-field">
          <label class="rq-label">Create a Password <span class="rq-req">*</span></label>
          <input type="password" class="rq-inp" id="rqPassword" placeholder="Min 6 characters — to track your quote" required minlength="6">
          <div style="font-size:11px;color:#64748b;margin-top:4px;">An account will be created so you can track and view your quotes.</div>
        </div>
        <div id="rqGuestErrEmail" class="rq-msg rq-msg-err"></div>

        <!-- Delivery address for guests -->
        <div style="margin-top:16px;padding-top:16px;border-top:1.5px dashed #e2e8f0;">
          <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:12px;text-transform:uppercase;letter-spacing:.4px;">Delivery Address</div>
          <div class="rq-field">
            <label class="rq-label">Address Line 1 <span class="rq-req">*</span></label>
            <input type="text" class="rq-inp" id="rqLine1" placeholder="House / flat, street, area…" required>
          </div>
          <div class="rq-field">
            <label class="rq-label">Address Line 2 <span class="rq-opt">(optional)</span></label>
            <input type="text" class="rq-inp" id="rqLine2" placeholder="Building, floor, landmark…">
          </div>
          <div class="rq-grid-3">
            <div class="rq-field">
              <label class="rq-label">City <span class="rq-req">*</span></label>
              <input type="text" class="rq-inp" id="rqCity" placeholder="City" required>
            </div>
            <div class="rq-field">
              <label class="rq-label">State / Region</label>
              <input type="text" class="rq-inp" id="rqState" placeholder="State">
            </div>
            <div class="rq-field">
              <label class="rq-label">ZIP / Postal Code <span class="rq-req">*</span></label>
              <input type="text" class="rq-inp" id="rqZip" placeholder="ZIP" required>
            </div>
          </div>
          <div class="rq-field">
            <label class="rq-label">Country <span class="rq-req">*</span></label>
            <select class="rq-sel" id="rqCountry" required>
              <option value="">Select country…</option>
              <option value="Germany">Germany</option>
              <option value="India">India</option>
              <option value="United States">United States</option>
              <option value="United Kingdom">United Kingdom</option>
              <option value="France">France</option>
              <option value="Netherlands">Netherlands</option>
              <option value="Singapore">Singapore</option>
              <option value="UAE">UAE</option>
              <option value="Australia">Australia</option>
              <option value="Canada">Canada</option>
              <option value="China">China</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- ── 3. NOTES ─────────────────────────────────────────── -->
    <div class="rq-card">
      <div class="rq-card-head">
        <div class="rq-card-icon" style="background:#fdf4ff;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9333ea" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
        <div>
          <div class="rq-card-title">Additional Notes <span class="rq-opt" style="font-size:11px;">(optional)</span></div>
          <div class="rq-card-sub">Special requirements, timeline, specs, urgent needs</div>
        </div>
      </div>
      <div class="rq-card-body">
        <textarea class="rq-ta" id="rqNotes" placeholder="e.g. Need delivery within 5 days, RoHS compliance required, firmware ready for chip programming…"></textarea>
      </div>
    </div>

    <!-- ── SUBMIT ────────────────────────────────────────────── -->
    <div class="rq-card" style="margin-bottom:0;">
      <div class="rq-submit-row">
        <button type="button" class="rq-submit-btn" id="rqSubmitBtn" onclick="rqSubmit()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2" fill="currentColor"/></svg>
          Submit Quote Request
        </button>
        <span class="rq-submit-note">We respond within 24 hours</span>
      </div>
      <div id="rqSubmitErr" class="rq-msg rq-msg-err" style="margin:0 20px 16px;"></div>
    </div>

  </div><!-- /left col -->

  <!-- ══ RIGHT: SIDEBAR ════════════════════════════════════════ -->
  <div>

    <!-- Why request a quote -->
    <div class="rq-side-card">
      <div class="rq-side-title">Why request a quote?</div>
      <ul class="rq-why-list">
        <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Best price for bulk &amp; custom orders</li>
        <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Hard-to-find parts sourced for you</li>
        <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Dedicated account manager</li>
        <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Priority processing &amp; dispatch</li>
        <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>100% genuine certified components</li>
      </ul>
    </div>

    <!-- Contact details (dynamic) -->
    <div class="rq-side-card">
      <div class="rq-side-title">Prefer to talk directly?</div>
      <?php if ($_q_phone): ?>
      <div class="rq-ci-item">
        <div class="rq-ci-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 8.81 19.79 19.79 0 01.1 2.27 2 2 0 012.07.1h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.16 6.16l.91-.91a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg></div>
        <div><div class="rq-ci-label">Phone</div><div class="rq-ci-val"><a href="tel:<?= preg_replace('/[^+\d]/', '', $company->CONTACT_NUMBER ?? '') ?>"><?= $_q_phone ?></a></div></div>
      </div>
      <?php endif; ?>
      <?php if ($_q_wp): ?>
      <div class="rq-ci-item">
        <div class="rq-ci-icon" style="background:#f0fdf4;"><svg width="14" height="14" viewBox="0 0 24 24" fill="#16a34a"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.52-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></div>
        <div><div class="rq-ci-label">WhatsApp</div><div class="rq-ci-val"><a href="<?= htmlspecialchars($_q_wpLink) ?>" target="_blank" rel="noopener"><?= $_q_wp ?></a></div></div>
      </div>
      <?php endif; ?>
      <?php if ($_q_email): ?>
      <div class="rq-ci-item">
        <div class="rq-ci-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
        <div><div class="rq-ci-label">Email</div><div class="rq-ci-val"><a href="mailto:<?= $_q_email ?>"><?= $_q_email ?></a></div></div>
      </div>
      <?php endif; ?>
      <?php if ($_q_hrs): ?>
      <div class="rq-ci-item">
        <div class="rq-ci-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div><div class="rq-ci-label">Office Hours</div><div class="rq-ci-val"><?= $_q_hrs ?></div></div>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /sidebar -->

</div><!-- /layout -->
</div><!-- /wrap -->
</div>

<script>
/* ── Data from PHP ─────────────────────────────────────────── */
var RQ_CATS      = <?= json_encode($catList, JSON_UNESCAPED_UNICODE) ?>;
var RQ_LOGGED_IN = <?= $isSignedIn ? 'true' : 'false' ?>;
var RQ_AJAX_BASE = 'ajax/quote.php';
var rqRowCount   = 0;
var rqProdCache  = {}; /* catId → products array */
var rqSelectedAddr = 0; /* user_address_id, 0 = new */

/* ── Formatting ─────────────────────────────────────────────── */
function rqFmt(n) {
  return '€' + (parseFloat(n) || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/* ── Add product row ─────────────────────────────────────────── */
function rqAddRow() {
  rqRowCount++;
  var n = rqRowCount;
  var catOpts = '<option value="">Select category…</option>';
  RQ_CATS.forEach(function(c) {
    catOpts += '<option value="' + c.id + '">' + c.name + '</option>';
  });

  var row = document.createElement('div');
  row.className = 'rq-prod-row';
  row.id = 'rqRow' + n;
  row.innerHTML = [
    '<div class="rq-row-num">' + n + '</div>',
    '<div>',
    '  <select class="rq-sel" id="rqCat' + n + '" onchange="rqOnCatChange(' + n + ')">' + catOpts + '</select>',
    '</div>',
    '<div>',
    '  <select class="rq-sel" id="rqProd' + n + '" onchange="rqOnProdChange(' + n + ')" disabled>',
    '    <option value="">Select product…</option>',
    '  </select>',
    '</div>',
    '<div>',
    '  <input type="number" class="rq-inp" id="rqQty' + n + '" value="1" min="1" oninput="rqCalcRow(' + n + ')" style="text-align:center;">',
    '</div>',
    '<div class="rq-row-price-disp" id="rqPrice' + n + '">€0.00</div>',
    '<div class="rq-row-total-disp" id="rqTotal' + n + '">€0.00</div>',
    '<button type="button" class="rq-row-del" onclick="rqRemoveRow(' + n + ')" title="Remove">',
    '  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    '</button>',
  ].join('');
  document.getElementById('rqProdRows').appendChild(row);
}

function rqRemoveRow(n) {
  var el = document.getElementById('rqRow' + n);
  if (el) el.remove();
  rqUpdateSubtotal();
}

/* ── Load products for a category ─────────────────────────── */
function rqOnCatChange(n) {
  var catId   = parseInt(document.getElementById('rqCat' + n).value) || 0;
  var prodSel = document.getElementById('rqProd' + n);
  prodSel.innerHTML = '<option value="">Loading…</option>';
  prodSel.disabled  = true;
  document.getElementById('rqPrice' + n).textContent = '€0.00';
  document.getElementById('rqTotal' + n).textContent = '€0.00';
  rqUpdateSubtotal();

  if (!catId) {
    prodSel.innerHTML = '<option value="">Select product…</option>';
    return;
  }

  if (rqProdCache[catId]) {
    rqFillProds(n, rqProdCache[catId]);
    return;
  }

  fetch(RQ_AJAX_BASE + '?action=get_products&cat_id=' + catId)
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.ok) {
        rqProdCache[catId] = d.products;
        rqFillProds(n, d.products);
      } else {
        prodSel.innerHTML = '<option value="">Error loading products</option>';
      }
    })
    .catch(function() {
      prodSel.innerHTML = '<option value="">Error loading products</option>';
    });
}

function rqFillProds(n, products) {
  var sel  = document.getElementById('rqProd' + n);
  var html = '<option value="">Select product…</option>';
  products.forEach(function(p) {
    html += '<option value="' + p.id + '" data-price="' + p.price + '" data-code="' + p.code + '">'
          + p.name + (p.code ? ' [' + p.code + ']' : '') + '</option>';
  });
  sel.innerHTML = html;
  sel.disabled  = false;
}

function rqOnProdChange(n) {
  var sel    = document.getElementById('rqProd' + n);
  var opt    = sel.options[sel.selectedIndex];
  var price  = opt ? parseFloat(opt.getAttribute('data-price')) || 0 : 0;
  document.getElementById('rqPrice' + n).textContent = rqFmt(price);
  rqCalcRow(n);
}

function rqCalcRow(n) {
  var priceEl = document.getElementById('rqPrice' + n);
  var qtyEl   = document.getElementById('rqQty' + n);
  var totalEl = document.getElementById('rqTotal' + n);
  var price   = parseFloat(priceEl.textContent.replace('€','').replace(',','')) || 0;
  var qty     = Math.max(1, parseInt(qtyEl.value) || 1);
  totalEl.textContent = rqFmt(price * qty);
  rqUpdateSubtotal();
}

function rqUpdateSubtotal() {
  var total = 0;
  document.querySelectorAll('#rqProdRows .rq-prod-row').forEach(function(row) {
    var id  = row.id.replace('rqRow','');
    var el  = document.getElementById('rqTotal' + id);
    if (el) total += parseFloat(el.textContent.replace('€','').replace(',','')) || 0;
  });
  document.getElementById('rqSubtotal').textContent = rqFmt(total);
}

/* ── Address (logged-in) ────────────────────────────────────── */
function rqLoadAddresses() {
  if (!RQ_LOGGED_IN) return;
  fetch(RQ_AJAX_BASE + '?action=get_addresses')
    .then(function(r) { return r.json(); })
    .then(function(d) {
      var list = document.getElementById('rqAddrList');
      if (!list) return;
      if (!d.ok || !d.addresses.length) {
        list.innerHTML = '<div style="font-size:13px;color:#94a3b8;">No saved addresses. Add one below.</div>';
        rqShowNewAddr();
        return;
      }
      var html = '';
      d.addresses.forEach(function(a, i) {
        var checked = i === 0 ? 'checked' : '';
        if (i === 0) rqSelectedAddr = a.id;
        html += '<label class="rq-addr-card' + (i===0?' selected':'') + '" onclick="rqSelectAddr(' + a.id + ', this)">'
              + '<input type="radio" name="rqAddrRadio" value="' + a.id + '" ' + checked + ' style="display:none;">'
              + '<div class="rq-addr-card-body">'
              + '<span class="rq-addr-label">' + (a.label||'Home') + '</span>'
              + '<div class="rq-addr-main">' + (a.line1 || '') + (a.line2 ? ', ' + a.line2 : '') + '</div>'
              + '<div class="rq-addr-meta">' + [a.city, a.state, a.zip, a.country].filter(Boolean).join(', ')
              + (a.phone ? ' · ' + a.phone : '') + '</div>'
              + '</div></label>';
      });
      list.innerHTML = html;
    })
    .catch(function() {});
}

function rqSelectAddr(id, el) {
  rqSelectedAddr = id;
  document.querySelectorAll('#rqAddrList .rq-addr-card').forEach(function(c) {
    c.classList.remove('selected');
  });
  el.classList.add('selected');
  /* hide new addr form */
  var f = document.getElementById('rqNewAddrForm');
  if (f) f.style.display = 'none';
}

function rqToggleNewAddr() {
  rqSelectedAddr = 0;
  document.querySelectorAll('#rqAddrList .rq-addr-card').forEach(function(c) {
    c.classList.remove('selected');
  });
  rqShowNewAddr();
}
function rqShowNewAddr() {
  var f = document.getElementById('rqNewAddrForm');
  if (f) f.style.display = 'block';
}

/* ── Auth modal ──────────────────────────────────────────────── */
function rqOpenLogin() {
  sessionStorage.setItem('sinelec_auth_redirect', 'request-a-quote');
  var m = document.getElementById('authModal');
  if (m) { m.hidden = false; document.body.style.overflow = 'hidden'; }
  var d = document.getElementById('authModalDesc');
  if (d) d.textContent = 'Sign in to use your saved addresses and track quotes.';
}

/* ── Collect products ─────────────────────────────────────────── */
function rqGetProducts() {
  var rows = document.querySelectorAll('#rqProdRows .rq-prod-row');
  var out  = [];
  rows.forEach(function(row) {
    var id = row.id.replace('rqRow', '');
    var catSel  = document.getElementById('rqCat'  + id);
    var prodSel = document.getElementById('rqProd' + id);
    var qtyEl   = document.getElementById('rqQty'  + id);
    var priceEl = document.getElementById('rqPrice'+ id);
    if (!catSel || !prodSel) return;
    var catId  = parseInt(catSel.value)  || 0;
    var prodId = parseInt(prodSel.value) || 0;
    var qty    = parseInt(qtyEl ? qtyEl.value : 1) || 1;
    var price  = parseFloat(priceEl ? priceEl.textContent.replace('€','').replace(',','') : 0) || 0;
    if (catId > 0 && prodId > 0 && qty > 0) {
      out.push({ cat_id: catId, prod_id: prodId, qty: qty, price: price });
    }
  });
  return out;
}

/* ── Submit ──────────────────────────────────────────────────── */
function rqShowErr(elId, msg) {
  var el = document.getElementById(elId);
  if (!el) return;
  el.textContent = msg;
  el.style.display = msg ? 'block' : 'none';
}

function rqSubmit() {
  /* Validate products */
  var products = rqGetProducts();
  if (!products.length) {
    rqShowErr('rqProdErr', 'Please add at least one product with a valid selection and quantity.');
    return;
  }
  rqShowErr('rqProdErr', '');
  rqShowErr('rqSubmitErr', '');

  var payload = { products: products, notes: (document.getElementById('rqNotes')||{}).value || '' };

  if (RQ_LOGGED_IN) {
    /* Logged in: use selected existing address OR new address form */
    var comp = (document.getElementById('rqCompany')||{}).value || '';
    payload.company_name = comp;

    if (rqSelectedAddr > 0) {
      payload.existing_address_id = rqSelectedAddr;
    } else {
      /* read new address form */
      payload.addr_name       = (document.getElementById('rqAddrName')    ||{}).value || '';
      payload.address_line_one= (document.getElementById('rqAddrLine1')  ||{}).value || '';
      payload.address_line_two= (document.getElementById('rqAddrLine2')  ||{}).value || '';
      payload.landmark        = (document.getElementById('rqAddrLandmark')||{}).value || '';
      payload.city            = (document.getElementById('rqAddrCity')   ||{}).value || '';
      payload.state           = (document.getElementById('rqAddrState')  ||{}).value || '';
      payload.zip             = (document.getElementById('rqAddrZip')    ||{}).value || '';
      payload.country         = (document.getElementById('rqAddrCountry')||{}).value || '';
      payload.phone           = (document.getElementById('rqAddrPhone')  ||{}).value || '';
      payload.label           = (document.getElementById('rqAddrLabel')  ||{}).value || 'Home';
      if (!payload.address_line_one || !payload.city || !payload.zip || !payload.country) {
        rqShowErr('rqSubmitErr', 'Please fill in the required address fields.');
        return;
      }
    }
  } else {
    /* Guest: collect personal + address fields */
    var name     = (document.getElementById('rqName')    ||{}).value || '';
    var email    = (document.getElementById('rqEmail')   ||{}).value || '';
    var phone    = (document.getElementById('rqPhone')   ||{}).value || '';
    var pcode    = (document.getElementById('rqPhoneCode')||{}).value || '49';
    var password = (document.getElementById('rqPassword')||{}).value || '';
    var line1    = (document.getElementById('rqLine1')   ||{}).value || '';
    var city     = (document.getElementById('rqCity')    ||{}).value || '';
    var zip      = (document.getElementById('rqZip')     ||{}).value || '';
    var country  = (document.getElementById('rqCountry') ||{}).value || '';

    if (!name || !email || !phone || !password || !line1 || !city || !zip || !country) {
      rqShowErr('rqSubmitErr', 'Please fill in all required fields.');
      return;
    }
    if (password.length < 6) {
      rqShowErr('rqSubmitErr', 'Password must be at least 6 characters.');
      return;
    }

    payload.name             = name;
    payload.email            = email;
    payload.phone            = phone;
    payload.phone_code       = pcode;
    payload.password         = password;
    payload.company_name     = (document.getElementById('rqCompany') ||{}).value || '';
    payload.address_line_one = line1;
    payload.address_line_two = (document.getElementById('rqLine2')   ||{}).value || '';
    payload.landmark         = '';
    payload.city             = city;
    payload.state            = (document.getElementById('rqState')   ||{}).value || '';
    payload.zip              = zip;
    payload.country          = country;
    payload.label            = 'Home';
  }

  /* Disable submit */
  var btn = document.getElementById('rqSubmitBtn');
  btn.disabled = true;
  btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:rqSpin 1s linear infinite"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Submitting…';

  fetch(RQ_AJAX_BASE + '?action=submit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    btn.disabled = false;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2" fill="currentColor"/></svg> Submit Quote Request';

    if (d.ok) {
      document.getElementById('rqFormCard').style.display = 'none';
      var successEl = document.getElementById('rqSuccess');
      successEl.style.display = 'block';
      document.getElementById('rqSuccessRef').textContent = 'Reference: #' + d.quote_id;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (d.error === 'email_exists') {
      rqShowErr('rqGuestErrEmail', d.msg || 'Email already registered. Please sign in.');
      rqShowErr('rqSubmitErr', '');
    } else {
      rqShowErr('rqSubmitErr', d.msg || 'Something went wrong. Please try again.');
    }
  })
  .catch(function() {
    btn.disabled = false;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2" fill="currentColor"/></svg> Submit Quote Request';
    rqShowErr('rqSubmitErr', 'Network error. Please check your connection and try again.');
  });
}

/* ── Init ────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
  rqAddRow(); /* start with one empty row */
  if (RQ_LOGGED_IN) rqLoadAddresses();
});
</script>

<style>
@keyframes rqSpin { to { transform: rotate(360deg); } }
</style>

<?php require_once 'footer.php'; ?>
