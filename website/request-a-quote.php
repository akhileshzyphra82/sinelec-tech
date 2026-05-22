<?php
require_once __DIR__ . '/account-helpers.php';
require_once __DIR__ . '/../controller/website_controller.php';
require_once __DIR__ . '/../common/functions.php';

/* ── Auth gate: must be logged in ───────────────────────────── */
if (!sinelec_is_signed_in()) {
    /* JS sets sessionStorage then redirects so footer auto-redirect works */
    ?><!DOCTYPE html><html><head><title>Redirecting…</title></head><body>
    <script>
    sessionStorage.setItem('sinelec_auth_redirect','request-a-quote');
    window.location.href='index';
    </script>
    </body></html><?php
    exit;
}

//echo "<pre>"; print_r(sinelec_get_signed_in_user()); echo "</pre>";die;
$currentPage = 'request-a-quote';
$pageTitle   = 'Request a Quote — Sinelec Tech';

$controller = new WebsiteController();
$company    = $controller->getCompanyInfo();
$signedUser = sinelec_get_signed_in_user();
$userId     = (int)($signedUser['USER_ID'] ?? 0);

/* ── Country list ───────────────────────────────────────────── */
$countryRows = $controller->getCountries();
$countryOpts = '<option value="" data-id="0">Select country…</option>';
foreach ($countryRows as $cr) {
    $cId   = (int)(float)($cr->COUNTRY_ID   ?? $cr->country_id   ?? 0);
    $cName = htmlspecialchars((string)($cr->COUNTRY ?? $cr->country ?? ''));
    $sel   = ($cName === 'India') ? ' selected' : '';
    $countryOpts .= "<option value=\"{$cName}\" data-id=\"{$cId}\"{$sel}>{$cName}</option>";
}

/* ── Category groups for JS optgroups ───────────────────────── */
$catRows = $controller->getQuoteCategories();
$grouped = [];
foreach ($catRows as $c) {
    $gl = (string)($c->GROUP_LABEL ?? $c->PRODUCT_CATEGORY_NAME ?? '');
    if (!isset($grouped[$gl])) $grouped[$gl] = [];
    $grouped[$gl][] = [
        'id'   => (int)(float)($c->PRODUCT_CATEGORY_ID   ?? 0),
        'name' => (string)($c->PRODUCT_CATEGORY_NAME      ?? ''),
    ];
}
$catGroups = [];
foreach ($grouped as $label => $cats) {
    $catGroups[] = ['group' => $label, 'cats' => $cats];
}

/* ── Saved addresses ────────────────────────────────────────── */
$addrRows = $controller->getUserAddresses($userId);
$addrList = [];
foreach ($addrRows as $r) {
    $addrList[] = [
        'id'               => (int)(float)($r->USER_ADDRESS_ID   ?? 0),
        'label'            => (string)($r->LABEL                 ?? 'Home'),
        'user_name'        => (string)($r->USER_NAME             ?? ''),
        'company'          => (string)($r->COMPANY_NAME          ?? ''),
        'phone'            => (string)($r->DELIVERY_PHONE_NO     ?? ''),
        'isd'              => (string)($r->MOBILE_COUNTRY_CODE   ?? ''),
        'line1'            => (string)($r->ADDRESS_LINE_ONE      ?? ''),
        'line2'            => (string)($r->ADDRESS_LINE_TWO      ?? ''),
        'landmark'         => (string)($r->LANDMARK              ?? ''),
        'city'             => (string)($r->CITY                  ?? ''),
        'state'            => (string)($r->STATE                 ?? ''),
        'zip'              => (string)($r->ZIP                   ?? ''),
        'country'          => (string)($r->COUNTRY               ?? ''),
        'recipient_name'   => (string)($r->RECIPIENT_NAME        ?? ''),
        'recipient_email'  => (string)($r->RECIPIENT_EMAIL       ?? ''),
        'recipient_contact'=> (string)($r->RECIPIENT_CONTACT     ?? ''),
    ];
}

/* ── Company sidebar data ───────────────────────────────────── */
$_cName    = htmlspecialchars((string)($company->NAME             ?? 'Sinelec Technologies'));
$_cPhone   = htmlspecialchars((string)($company->CONTACT_NUMBER   ?? ''));
$_cFax     = htmlspecialchars((string)($company->FAX              ?? ''));
$_cWp      = htmlspecialchars((string)($company->WHATSAPP_NUMBER  ?? ''));
$_cEmail   = htmlspecialchars((string)($company->EMAIL            ?? ''));
$_cSupport = htmlspecialchars((string)($company->SUPPORT_MAIL_ID  ?? ''));
$_cHrs     = htmlspecialchars((string)($company->OFFICE_HRS       ?? ''));
$_cAddress = htmlspecialchars((string)($company->ADDRESS          ?? ''));
$_cBranch  = html_entity_decode((string)($company->BRANCH_OFFICE_ADDRESS ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$_cMapUrl  = (string)($company->MAP_URL ?? '');
$_cFb      = htmlspecialchars((string)($company->FACEBOOK_URL  ?? ''));
$_cLi      = htmlspecialchars((string)($company->LINKEDIN_URL  ?? ''));
$_cTw      = htmlspecialchars((string)($company->TWITTER_URL   ?? ''));
$_cYt      = htmlspecialchars((string)($company->YOUTUBE_URL   ?? ''));
$_cIg      = htmlspecialchars((string)($company->INSTAGRAM_URL ?? ''));
$_cWpLink  = 'https://wa.me/' . preg_replace('/[^0-9]/', '', (string)($company->WHATSAPP_NUMBER ?? ''));

/* ── Turnstile site key ─────────────────────────────────────── */
$cfSiteKey = htmlspecialchars(sinelec_env('SITE_KEY', '') ?? '');

require_once 'header.php';
?>
<style>
/* ── Variables ─────────────────────────────────────────────── */
:root {
  --rq-blue:    #2563eb;
  --rq-dark:    #0f172a;
  --rq-border:  #e2e8f0;
  --rq-radius:  14px;
  --rq-shadow:  0 2px 12px rgba(15,23,42,.07);
}

/* ── Hero ──────────────────────────────────────────────────── */
.rq-hero {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #1a56a0 100%);
  padding: 44px 0 38px;
  position: relative; overflow: hidden;
}
.rq-hero::before {
  content:''; position:absolute; inset:0;
  background: radial-gradient(ellipse 70% 60% at 30% 0%, rgba(59,130,246,.15) 0%, transparent 70%);
  pointer-events:none;
}
.rq-breadcrumb {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; margin-bottom: 12px; color: rgba(255,255,255,.55);
}
.rq-breadcrumb a { color: #93c5fd; text-decoration: none; }
.rq-breadcrumb a:hover { text-decoration: underline; }
.rq-breadcrumb-sep { color: rgba(255,255,255,.3); }
.rq-hero h1 {
  font-size: clamp(22px, 4vw, 32px); font-weight: 800;
  color: #fff; margin: 0 0 6px; letter-spacing: -.4px;
}
.rq-hero p { font-size: 14px; color: rgba(255,255,255,.68); margin: 0; }

/* ── Layout ────────────────────────────────────────────────── */
.rq-wrap   { padding: 40px 0 64px; }
.rq-layout { display: grid; grid-template-columns: 1fr 320px; gap: 28px; align-items: start; }
@media (max-width: 960px) { .rq-layout { grid-template-columns: 1fr; } }

/* ── Card ──────────────────────────────────────────────────── */
.rq-card {
  background: #fff; border: 1.5px solid var(--rq-border);
  border-radius: var(--rq-radius); overflow: hidden;
  margin-bottom: 18px; box-shadow: var(--rq-shadow);
}
.rq-card:last-child { margin-bottom: 0; }
.rq-card-head {
  display: flex; align-items: center; gap: 12px;
  padding: 16px 20px 14px; border-bottom: 1px solid #f1f5f9;
  background: #fafbfc;
}
.rq-card-icon {
  width: 34px; height: 34px; border-radius: 10px;
  display: grid; place-items: center; flex-shrink: 0;
}
.rq-card-title { font-size: 14px; font-weight: 700; color: var(--rq-dark); }
.rq-card-sub   { font-size: 11px; color: #64748b; margin-top: 1px; }
.rq-card-body  { padding: 20px; }
.rq-captcha-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 20px;
  flex-wrap: wrap;
}
.rq-captcha-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 180px;
}
.rq-captcha-right {
  flex-shrink: 0;
}
.rq-captcha-right .cf-turnstile { display: block; }

/* ── Form elements ─────────────────────────────────────────── */
.rq-field { margin-bottom: 14px; }
.rq-field:last-child { margin-bottom: 0; }
.rq-label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
.rq-req   { color: #ef4444; margin-left: 2px; }
.rq-opt   { color: #94a3b8; font-weight: 400; }
.rq-inp, .rq-sel, .rq-ta {
  width: 100%; height: 40px; padding: 0 12px;
  border: 1.5px solid #d1d9e2; border-radius: 9px;
  font-size: 13px; color: #1e293b; background: #fff;
  transition: border-color .15s, box-shadow .15s;
  box-sizing: border-box;
}
.rq-ta { height: auto; padding: 10px 12px; resize: vertical; min-height: 82px; }
.rq-inp:focus, .rq-sel:focus, .rq-ta:focus {
  border-color: var(--rq-blue); outline: none;
  box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.rq-inp[readonly] {
  background: #f8fafc; color: #64748b; cursor: not-allowed;
  border-color: #e2e8f0;
}
.rq-inp::placeholder { color: #b0bac5; }
.rq-phone-wrap { display: flex; gap: 8px; }
.rq-isd { flex: 0 0 auto; width: 148px; }
.rq-phone-num { flex: 1 1 0; min-width: 0; }
.rq-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.rq-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
@media (max-width: 680px) { .rq-grid-2, .rq-grid-3 { grid-template-columns: 1fr; } }

/* ── User badge ────────────────────────────────────────────── */
.rq-user-badge {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 14px; background: #f0fdf4;
  border: 1.5px solid #bbf7d0; border-radius: 10px; margin-bottom: 18px;
}
.rq-user-avatar {
  width: 38px; height: 38px; border-radius: 50%;
  background: #16a34a; color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; font-weight: 800; flex-shrink: 0;
}
.rq-user-name  { font-size: 13px; font-weight: 700; color: #14532d; }
.rq-user-email { font-size: 12px; color: #15803d; }

/* ── Product rows ──────────────────────────────────────────── */
.rq-prod-header {
  display: grid;
  grid-template-columns: 32px 1fr 1fr 80px 110px 110px 36px;
  gap: 8px; padding: 0 10px 8px;
  font-size: 10.5px; font-weight: 700; color: #64748b;
  text-transform: uppercase; letter-spacing: .4px;
}
@media (max-width: 860px) { .rq-prod-header { display: none; } }
.rq-prod-rows { display: flex; flex-direction: column; gap: 10px; }
.rq-prod-row {
  display: grid;
  grid-template-columns: 32px 1fr 1fr 80px 110px 110px 36px;
  gap: 8px; align-items: center;
  background: #f8fafc; border: 1px solid var(--rq-border);
  border-radius: 10px; padding: 10px;
}
@media (max-width: 860px) {
  .rq-prod-row {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto auto auto;
  }
  .rq-prod-row .rq-row-num { display: none; }
  .rq-prod-row .rq-row-del { grid-column: 2; justify-self: end; }
}
@media (max-width: 520px) {
  .rq-prod-row { grid-template-columns: 1fr; }
  .rq-prod-row .rq-row-del { justify-self: start; }
}
.rq-row-num {
  width: 26px; height: 26px; border-radius: 50%;
  background: #e0e7ff; color: #3730a3;
  font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.rq-row-price {
  height: 40px; padding: 0 12px; border-radius: 9px;
  background: #f1f5f9; border: 1.5px solid #e2e8f0;
  display: flex; align-items: center;
  font-size: 13px; font-weight: 600; color: var(--rq-dark);
}
.rq-row-total {
  height: 40px; padding: 0 12px; border-radius: 9px;
  background: #eff6ff; border: 1.5px solid #bfdbfe;
  display: flex; align-items: center;
  font-size: 13px; font-weight: 700; color: #1d4ed8;
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
  display: inline-flex; align-items: center; gap: 7px;
  margin-top: 6px; padding: 8px 16px;
  border: 1.5px dashed #bfdbfe; border-radius: 9px;
  background: transparent; color: var(--rq-blue);
  font-size: 13px; font-weight: 600; cursor: pointer;
  transition: background .15s;
}
.rq-add-row:hover { background: #eff6ff; }
.rq-footer-bar {
  border-top: 1.5px dashed var(--rq-border);
  margin-top: 16px; padding-top: 14px;
  display: flex; flex-direction: column; gap: 12px;
}
.rq-add-row-wrap { display: flex; align-items: flex-start; }
.rq-pricing-box {
  background: linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);
  border: 1.5px solid var(--rq-border); border-radius: 12px;
  padding: 12px 16px; width: 100%;
}
.rq-pricing-rows { display: flex; flex-direction: column; gap: 2px; margin-bottom: 8px; }
.rq-pricing-row {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  padding: 3px 0;
}
.rq-pricing-label { font-size: 12px; color: #64748b; font-weight: 500; }
.rq-pricing-val   { font-size: 12px; font-weight: 700; color: var(--rq-dark); }
.rq-pricing-row--rebate .rq-pricing-label,
.rq-pricing-row--rebate .rq-pricing-val { color: #16a34a; }
.rq-pricing-divider { height: 1px; background: #e2e8f0; margin: 5px 0; }
.rq-pricing-total-row {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.rq-pricing-total-label { font-size: 13px; font-weight: 700; color: var(--rq-dark); }
.rq-pricing-total-val   { font-size: 18px; font-weight: 800; color: var(--rq-blue); }
.rq-vat-row {
  display: flex; align-items: center; gap: 8px;
  padding-top: 8px; border-top: 1px dashed #cbd5e1; margin-top: 8px;
}
.rq-vat-row-label { font-size: 11px; font-weight: 600; color: #475569; white-space: nowrap; flex-shrink: 0; }
.rq-vat-inp { flex: 1 1 0; min-width: 0; font-size: 11.5px; padding: 4px 9px; height: 30px; }
.rq-price-note {
  margin-top: 8px; padding: 6px 10px;
  background: #fffbeb; border-left: 3px solid #f59e0b;
  border-radius: 4px; font-size: 11px; color: #92400e; line-height: 1.5;
}

/* ── Address sections ──────────────────────────────────────── */
.rq-card-head-row {
  display: flex; align-items: center; justify-content: space-between;
  gap: 10px; flex: 1;
}
.rq-addr-add-btn {
  flex-shrink: 0; display: inline-flex; align-items: center; gap: 5px;
  padding: 5px 13px; border: 1.5px solid #bfdbfe; border-radius: 8px;
  background: #eff6ff; color: var(--rq-blue); font-size: 12px; font-weight: 600;
  cursor: pointer; transition: background .15s; white-space: nowrap;
}
.rq-addr-add-btn:hover { background: #dbeafe; }

/* Selected address card */
.rq-selected-addr {
  border: 1.5px solid var(--rq-blue); border-radius: 10px;
  padding: 12px 14px; background: #eff6ff;
}
.rq-selected-addr-row {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
}
.rq-addr-chip {
  display: inline-block; font-size: 10px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .4px; padding: 2px 7px; border-radius: 4px;
  background: #e0e7ff; color: #3730a3; margin-bottom: 5px;
}
.rq-addr-main  { font-size: 13px; color: var(--rq-dark); font-weight: 600; line-height: 1.45; }
.rq-addr-meta  { font-size: 12px; color: #475569; margin-top: 3px; line-height: 1.5; }
.rq-addr-change-btn {
  flex-shrink: 0; padding: 4px 13px;
  border: 1px solid #93c5fd; border-radius: 6px; background: #fff;
  color: var(--rq-blue); font-size: 12px; font-weight: 600; cursor: pointer;
  transition: background .15s;
}
.rq-addr-change-btn:hover { background: #eff6ff; }

/* Address list (shown when changing) */
.rq-addr-list { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
.rq-addr-list-item {
  padding: 11px 14px; border: 1.5px solid var(--rq-border); border-radius: 10px;
  cursor: pointer; transition: border-color .15s, background .15s;
  display: flex; align-items: flex-start; gap: 10px;
}
.rq-addr-list-item:hover  { border-color: var(--rq-blue); background: #f8faff; }
.rq-addr-list-item.picked { border-color: var(--rq-blue); background: #eff6ff; }
.rq-addr-list-radio { margin-top: 3px; flex-shrink: 0; accent-color: var(--rq-blue); }

/* New address form */
.rq-af {
  margin-top: 14px; padding: 18px;
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
}
.rq-af-title {
  font-size: 10px; font-weight: 700; letter-spacing: 1px;
  text-transform: uppercase; color: #94a3b8; margin-bottom: 14px;
}
.rq-af-field { margin-bottom: 12px; }
.rq-af-field:last-child { margin-bottom: 0; }
.rq-af-lbl { font-size: 12px; font-weight: 600; color: #374151; display: block; margin-bottom: 4px; }
.rq-af-chips { display: flex; gap: 8px; flex-wrap: wrap; }
.rq-af-chip {
  padding: 5px 16px; border: 1.5px solid #e2e8f0; border-radius: 20px;
  cursor: pointer; font-size: 12.5px; background: #fff; transition: all .15s;
}
.rq-af-chip.active { border-color: var(--rq-blue); background: #eff6ff; color: var(--rq-blue); font-weight: 600; }
.rq-af-divider { height: 1px; background: #e2e8f0; margin: 14px 0; }
.rq-af-textarea { resize: vertical; min-height: 72px; }
.rq-af-rcpt-toggle {
  display: flex; align-items: center; gap: 7px; cursor: pointer;
  font-size: 12px; font-weight: 600; color: #64748b;
  padding: 8px 0; user-select: none; border: none; background: none; width: 100%;
}
.rq-af-rcpt-toggle svg { transition: transform .2s; flex-shrink: 0; }
.rq-af-rcpt-toggle.open svg { transform: rotate(180deg); }
.rq-af-rcpt-toggle span.rq-opt { font-weight: 400; }
.rq-af-rcpt-body { display: none; padding-top: 4px; }
.rq-af-rcpt-body.open { display: block; }
.rq-af-actions { display: flex; gap: 10px; margin-top: 16px; }
.rq-af-save {
  padding: 8px 22px; border: none; border-radius: 8px;
  background: var(--rq-blue); color: #fff;
  font-size: 13px; font-weight: 600; cursor: pointer;
  display: inline-flex; align-items: center; gap: 6px;
  transition: background .15s;
}
.rq-af-save:hover { background: #1d4ed8; }
.rq-af-cancel {
  padding: 8px 18px; border: 1.5px solid #e2e8f0; border-radius: 8px;
  background: #fff; color: #64748b; font-size: 13px; font-weight: 600; cursor: pointer;
  transition: background .15s;
}
.rq-af-cancel:hover { background: #f1f5f9; }

/* Billing same-as checkbox */
.rq-same-billing {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 14px; background: #f0fdf4;
  border: 1.5px solid #bbf7d0; border-radius: 10px;
  cursor: pointer; user-select: none; margin-bottom: 14px;
}
.rq-same-billing input { accent-color: #16a34a; width: 16px; height: 16px; flex-shrink: 0; cursor: pointer; }
.rq-same-billing span  { font-size: 13px; font-weight: 600; color: #14532d; }
#rqBillingSection { display: none; }

/* ── Submit ────────────────────────────────────────────────── */
.rq-submit-row {
  display: flex; align-items: center; gap: 14px;
  padding: 18px 20px; background: #f8fafc;
  border-top: 1.5px solid var(--rq-border); flex-wrap: wrap;
}
.rq-submit-btn {
  height: 48px; padding: 0 32px; border: none; border-radius: 12px;
  background: linear-gradient(135deg, #1d4ed8, #2563eb);
  color: #fff; font-size: 14px; font-weight: 700; cursor: pointer;
  display: inline-flex; align-items: center; gap: 8px;
  box-shadow: 0 4px 14px rgba(37,99,235,.35);
  transition: transform .15s, box-shadow .15s;
}
.rq-submit-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(37,99,235,.42); }
.rq-submit-btn:disabled { opacity: .6; cursor: default; transform: none; }
.rq-submit-note { font-size: 12px; color: #64748b; }

/* ── Messages ──────────────────────────────────────────────── */
.rq-msg { display: none; padding: 10px 14px; border-radius: 9px; font-size: 13px; margin-top: 10px; }
.rq-msg.show { display: block; }
.rq-err { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
.rq-ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }

/* ── Success state ─────────────────────────────────────────── */
.rq-success {
  display: none; text-align: center; padding: 52px 32px;
}
.rq-success.show { display: block; }
.rq-success-icon {
  width: 72px; height: 72px; border-radius: 50%;
  background: #dcfce7; display: flex; align-items: center; justify-content: center;
  margin: 0 auto 20px;
}
.rq-success-title { font-size: 22px; font-weight: 800; color: #14532d; margin-bottom: 8px; }
.rq-success-sub   { font-size: 14px; color: #374151; margin-bottom: 20px; }
.rq-success-ref   {
  display: inline-block; padding: 8px 20px;
  background: #f0fdf4; border: 1.5px solid #bbf7d0;
  border-radius: 8px; font-size: 13px; font-weight: 700; color: #16a34a;
  margin-bottom: 24px;
}
.rq-success-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
.rq-btn-primary {
  height: 42px; padding: 0 22px; border-radius: 9px;
  background: var(--rq-blue); color: #fff;
  font-size: 13px; font-weight: 700; text-decoration: none;
  display: inline-flex; align-items: center; border: none; cursor: pointer;
}
.rq-btn-outline {
  height: 42px; padding: 0 22px; border-radius: 9px;
  border: 1.5px solid #cbd5e1; color: #374151;
  font-size: 13px; font-weight: 700; text-decoration: none;
  display: inline-flex; align-items: center; background: #fff;
}

/* ── Sidebar cards ─────────────────────────────────────────── */
.rq-side { background: #fff; border: 1.5px solid var(--rq-border); border-radius: var(--rq-radius); margin-bottom: 16px; overflow: hidden; box-shadow: var(--rq-shadow); }
.rq-side:last-child { margin-bottom: 0; }

/* Why card */
.rq-why-head {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #1a56a0 100%);
  padding: 18px 20px 14px; position: relative; overflow: hidden;
}
.rq-why-head::before {
  content:''; position:absolute; inset:0;
  background: radial-gradient(ellipse 80% 70% at 0% 0%, rgba(59,130,246,.22) 0%, transparent 70%);
  pointer-events:none;
}
.rq-why-head-label {
  font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
  color: #93c5fd; margin-bottom: 5px; position: relative;
}
.rq-why-head-title {
  font-size: 16px; font-weight: 800; color: #fff; margin: 0; position: relative; line-height: 1.3;
}
.rq-why-body { padding: 4px 0 6px; }
.rq-why-item {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 12px 20px; border-bottom: 1px solid #f1f5f9;
  transition: background .15s;
}
.rq-why-item:last-child { border-bottom: none; }
.rq-why-item:hover { background: #f8faff; }
.rq-why-icon {
  width: 32px; height: 32px; border-radius: 9px;
  display: grid; place-items: center; flex-shrink: 0; margin-top: 1px;
}
.rq-why-text { flex: 1; min-width: 0; }
.rq-why-text strong { display: block; font-size: 12.5px; font-weight: 700; color: #0f172a; line-height: 1.3; }
.rq-why-text span   { display: block; font-size: 11.5px; color: #64748b; margin-top: 2px; line-height: 1.4; }

/* Contact card */
.rq-contact-head {
  display: flex; align-items: center; gap: 10px;
  padding: 14px 18px; background: #f8fafc;
  border-bottom: 1px solid var(--rq-border);
}
.rq-contact-head-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: #eff6ff; display: grid; place-items: center; flex-shrink: 0;
}
.rq-contact-head-title { font-size: 13px; font-weight: 700; color: var(--rq-dark); }
.rq-contact-head-sub   { font-size: 11px; color: #64748b; margin-top: 1px; }

.rq-contact-block { padding: 12px 18px; border-bottom: 1px solid #f1f5f9; }
.rq-contact-block:last-child { border-bottom: none; }
.rq-contact-block-title {
  font-size: 10px; font-weight: 700; letter-spacing: 1px;
  text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;
  display: flex; align-items: center; gap: 5px;
}
.rq-contact-row {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 6px 0;
}
.rq-contact-row-icon {
  width: 28px; height: 28px; border-radius: 8px;
  display: grid; place-items: center; flex-shrink: 0; margin-top: 1px;
}
.rq-contact-row-lbl  { font-size: 10.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 2px; }
.rq-contact-row-val  { font-size: 13px; color: #1e293b; word-break: break-word; line-height: 1.4; }
.rq-contact-row-val a { color: var(--rq-blue); text-decoration: none; }
.rq-contact-row-val a:hover { text-decoration: underline; }
.rq-address-text { font-size: 13px; color: #374151; line-height: 1.7; white-space: pre-line; margin: 0; }

.rq-socials { display: flex; gap: 8px; padding: 12px 18px; flex-wrap: wrap; }
.rq-soc {
  width: 32px; height: 32px; border-radius: 8px;
  background: #f1f5f9; color: #475569;
  display: grid; place-items: center;
  transition: background .15s, color .15s;
}
.rq-soc:hover { background: #2563eb; color: #fff; }

/* Map card */
.rq-map-head {
  display: flex; align-items: center; gap: 10px;
  padding: 14px 18px; background: #f8fafc;
  border-bottom: 1px solid var(--rq-border);
}
.rq-map-head-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: #eff6ff; display: grid; place-items: center; flex-shrink: 0;
}
.rq-map-head-title { font-size: 13px; font-weight: 700; color: var(--rq-dark); }
.rq-map-head-sub   { font-size: 11px; color: #64748b; margin-top: 1px; }
.rq-map-frame { line-height: 0; }
.rq-map-frame iframe { width: 100%; height: 220px; border: none; display: block; }
.rq-map-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 8px; padding: 36px 20px; color: #94a3b8; text-align: center;
}
.rq-map-empty svg { opacity: .3; }
.rq-map-empty p { font-size: 12px; margin: 0; }

/* ── Spin animation ────────────────────────────────────────── */
@keyframes rqSpin { to { transform: rotate(360deg); } }
</style>

<!-- ── Hero ──────────────────────────────────────────────────── -->
<section class="rq-hero">
  <div class="wrap" style="position:relative;">
    <nav class="rq-breadcrumb">
      <a href="index">Home</a>
      <span class="rq-breadcrumb-sep">›</span>
      <span>Request a Quote</span>
    </nav>
    <h1>Request a Quotation</h1>
    <p>Select products, enter quantities and we'll respond with our best price within 24 hours.</p>
  </div>
</section>

<!-- ── Body ──────────────────────────────────────────────────── -->
<div class="wrap">
<div class="rq-wrap">
<div class="rq-layout">

<!-- ══ LEFT COLUMN ══════════════════════════════════════════ -->
<div>

  <!-- Success state (shown after submit) -->
  <div class="rq-card rq-success" id="rqSuccess">
    <div class="rq-success-icon">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div class="rq-success-title">Quote Submitted Successfully!</div>
    <div class="rq-success-sub">Our team will review your request and get back to you within 24 hours.</div>
    <div class="rq-success-ref" id="rqSuccessRef">Reference: #—</div>
    <div class="rq-success-actions">
      <a href="index" class="rq-btn-primary">Back to Home</a>
      <a href="my-list#quotes" class="rq-btn-outline">View My Quotes</a>
    </div>
  </div>

  <!-- ─ 1. YOUR DETAILS ───────────────────────────────────── -->
  <div class="rq-card" id="rqFormWrap">
    <div class="rq-card-head">
      <div class="rq-card-icon" style="background:#f0fdf4;">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div>
        <div class="rq-card-title">Your Details</div>
        <div class="rq-card-sub">Edit your name, phone and company — email cannot be changed here</div>
      </div>
    </div>
    <div class="rq-card-body">

      <div class="rq-user-badge">
        <div class="rq-user-avatar"><?= strtoupper(substr($signedUser['NAME'] ?? 'U', 0, 1)) ?></div>
        <div>
          <div class="rq-user-name"><?= htmlspecialchars($signedUser['NAME'] ?? '') ?></div>
          <div class="rq-user-email"><?= htmlspecialchars($signedUser['EMAIL'] ?? '') ?></div>
        </div>
      </div>

      <div class="rq-grid-2">
        <div class="rq-field">
          <label class="rq-label">Full Name <span class="rq-req">*</span></label>
          <input type="text" class="rq-inp" id="rqUserName"
                 value="<?= htmlspecialchars($signedUser['NAME'] ?? '') ?>"
                 placeholder="Your full name">
        </div>
        <div class="rq-field">
          <label class="rq-label">Email Address</label>
          <input type="email" class="rq-inp" id="rqUserEmail"
                 value="<?= htmlspecialchars($signedUser['EMAIL'] ?? '') ?>"
                 readonly title="Your email address is your account login ID and cannot be modified here. To update it, please visit your account settings.">
        </div>
      </div>
      <div class="rq-grid-2">
        <div class="rq-field">
          <label class="rq-label">Phone Number <span class="rq-req">*</span></label>
          <div class="rq-phone-wrap">
            <select class="rq-inp rq-isd" id="rqUserPhoneIsd">
              <?php
              $selIsd = trim((string)($signedUser['COMMUNICATION_MOBILE_NUM_ISD'] ?? '91'));
              $isdList = ['91'=>'+91 India','1'=>'+1 USA/Canada','44'=>'+44 UK','49'=>'+49 Germany','33'=>'+33 France','39'=>'+39 Italy','34'=>'+34 Spain','31'=>'+31 Netherlands','61'=>'+61 Australia','81'=>'+81 Japan','86'=>'+86 China','65'=>'+65 Singapore','971'=>'+971 UAE','966'=>'+966 Saudi Arabia','60'=>'+60 Malaysia'];
              foreach ($isdList as $code => $label):
              ?><option value="<?= $code ?>"<?= $selIsd === (string)$code ? ' selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
            </select>
            <input type="tel" class="rq-inp rq-phone-num" id="rqUserPhone"
                   value="<?= htmlspecialchars($signedUser['COMMUNICATION_MOBILE_NUM'] ?? '') ?>"
                   placeholder="98765 43210">
          </div>
        </div>
        <div class="rq-field">
          <label class="rq-label">Company Name <span class="rq-opt">(optional)</span></label>
          <input type="text" class="rq-inp" id="rqUserCompany"
                 value="<?= htmlspecialchars($signedUser['COMPANY_NAME'] ?? '') ?>"
                 placeholder="Your organisation">
        </div>
      </div>

    </div>
  </div>

  <!-- ─ 2. PRODUCTS ───────────────────────────────────────── -->
  <div class="rq-card">
    <div class="rq-card-head">
      <div class="rq-card-icon" style="background:#eff6ff;">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
      </div>
      <div>
        <div class="rq-card-title">Products</div>
        <div class="rq-card-sub">Select category and product, enter quantity to see pricing</div>
      </div>
    </div>
    <div class="rq-card-body">

      <div class="rq-prod-header">
        <div></div><div>Category</div><div>Product</div>
        <div>Qty</div><div>Unit Price</div><div>Line Total</div><div></div>
      </div>

      <div class="rq-prod-rows" id="rqProdRows"></div>

      <div id="rqProdErr" class="rq-msg rq-err"></div>

      <div class="rq-footer-bar">
        <div class="rq-add-row-wrap">
          <button type="button" class="rq-add-row" onclick="rqAddRow()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Another Product
          </button>
        </div>

        <div class="rq-pricing-box">
          <div class="rq-pricing-rows">
            <div class="rq-pricing-row">
              <span class="rq-pricing-label">Subtotal</span>
              <span class="rq-pricing-val" id="rqSubtotal">€0.00</span>
            </div>
            <div class="rq-pricing-row" id="rqVatRow">
              <span class="rq-pricing-label">VAT (18%)</span>
              <span class="rq-pricing-val" id="rqVatAmt">€0.00</span>
            </div>
            <div class="rq-pricing-row rq-pricing-row--rebate" id="rqVatRebateRow" style="display:none;">
              <span class="rq-pricing-label">VAT Rebate (−18%)</span>
              <span class="rq-pricing-val" id="rqVatRebateAmt">−€0.00</span>
            </div>
          </div>
          <div class="rq-vat-row">
            <span class="rq-vat-row-label">VAT / Tax No. <span style="font-weight:400;color:#94a3b8;">(optional)</span></span>
            <input type="text" class="rq-inp rq-vat-inp" id="rqVatNumber"
                   placeholder="e.g. DE123456789 / GSTIN"
                   oninput="rqUpdateSubtotal()">
          </div>
          <div class="rq-pricing-divider" style="margin-top:8px;"></div>
          <div class="rq-pricing-total-row">
            <span class="rq-pricing-total-label">Estimated Total</span>
            <span class="rq-pricing-total-val" id="rqTotal">€0.00</span>
          </div>
          <div class="rq-price-note">
            <strong>Please note:</strong> The above pricing is indicative based on current listed rates. Final pricing, applicable taxes, shipping charges, and any negotiated discounts will be confirmed in your revised quotation issued by our sales team.
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ─ 4. DELIVERY ADDRESS ──────────────────────────────── -->
  <div class="rq-card">
    <div class="rq-card-head">
      <div class="rq-card-icon" style="background:#fff7ed;">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
      </div>
      <div class="rq-card-head-row">
        <div>
          <div class="rq-card-title">Delivery Address <span class="rq-req">*</span></div>
          <div class="rq-card-sub">Where should we ship your order?</div>
        </div>
        <button type="button" class="rq-addr-add-btn" id="rqDelivAddBtn" onclick="rqToggleAddrForm('delivery')">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add New Address
        </button>
      </div>
    </div>
    <div class="rq-card-body">
      <!-- Selected address -->
      <div id="rqDelivSelectedWrap" style="display:none;">
        <div class="rq-selected-addr" id="rqDelivSelectedCard"></div>
      </div>
      <!-- Change address list -->
      <div id="rqDelivListWrap" style="display:none;">
        <div class="rq-addr-list" id="rqDelivAddrList"></div>
      </div>
      <!-- New address form -->
      <div id="rqDelivNewForm" style="display:none;">
        <div class="rq-af">
          <div class="rq-af-title">New Delivery Address</div>
          <div class="rq-af-field">
            <span class="rq-af-lbl">Address Label</span>
            <div class="rq-af-chips" id="rqDALabelChips">
              <button type="button" class="rq-af-chip active" data-val="Home" onclick="rqPickLabel('delivery',this)">🏠 Home</button>
              <button type="button" class="rq-af-chip" data-val="Office" onclick="rqPickLabel('delivery',this)">🏢 Office</button>
              <button type="button" class="rq-af-chip" data-val="Other" onclick="rqPickLabel('delivery',this)">📍 Other</button>
            </div>
            <input type="hidden" id="rqDALabel" value="Home">
          </div>
          <div class="rq-af-divider"></div>
          <div class="rq-grid-2">
            <div class="rq-af-field" style="margin:0;">
              <label class="rq-af-lbl">Country <span class="rq-req">*</span></label>
              <select class="rq-inp" id="rqDACountry">
                <?= $countryOpts ?>
              </select>
            </div>
            <div class="rq-af-field" style="margin:0;">
              <label class="rq-af-lbl">Full Name / Company Name <span class="rq-req">*</span></label>
              <input type="text" class="rq-inp" id="rqDACompany" placeholder="Full name or company name">
            </div>
          </div>
          <div class="rq-grid-2" style="margin-top:12px;">
            <div class="rq-af-field" style="margin:0;">
              <label class="rq-af-lbl">Contact Name <span class="rq-req">*</span></label>
              <input type="text" class="rq-inp" id="rqDAName" placeholder="Contact person name">
            </div>
            <div class="rq-af-field" style="margin:0;">
              <label class="rq-af-lbl">Phone Number <span class="rq-req">*</span></label>
              <div class="rq-phone-wrap">
                <select class="rq-inp rq-isd" id="rqDAIsd">
                  <option value="91">+91 India</option><option value="1">+1 USA/CA</option><option value="44">+44 UK</option><option value="49">+49 Germany</option><option value="33">+33 France</option><option value="39">+39 Italy</option><option value="34">+34 Spain</option><option value="31">+31 Netherlands</option><option value="61">+61 Australia</option><option value="971">+971 UAE</option><option value="65">+65 Singapore</option><option value="60">+60 Malaysia</option>
                </select>
                <input type="tel" class="rq-inp rq-phone-num" id="rqDAPhone" placeholder="Phone number">
              </div>
            </div>
          </div>
          <div class="rq-af-field" style="margin-top:12px;">
            <label class="rq-af-lbl">Address Line 1 <span class="rq-req">*</span></label>
            <input type="text" class="rq-inp" id="rqDALine1" placeholder="Street name and number">
          </div>
          <div class="rq-af-field">
            <label class="rq-af-lbl">Address Line 2 <span class="rq-opt">(optional)</span></label>
            <input type="text" class="rq-inp" id="rqDALine2" placeholder="Apartment, suite, floor, unit">
          </div>
          <div class="rq-af-field">
            <label class="rq-af-lbl">Address Line 3 <span class="rq-opt">(optional)</span></label>
            <input type="text" class="rq-inp" id="rqDALandmark" placeholder="Landmark, area, locality">
          </div>
          <div class="rq-grid-2">
            <div class="rq-af-field" style="margin:0;">
              <label class="rq-af-lbl">Postal / ZIP Code <span class="rq-req">*</span></label>
              <input type="text" class="rq-inp" id="rqDAZip" placeholder="e.g. 400001">
            </div>
            <div class="rq-af-field" style="margin:0;">
              <label class="rq-af-lbl">City <span class="rq-req">*</span></label>
              <input type="text" class="rq-inp" id="rqDACity" placeholder="City">
            </div>
          </div>
          <div class="rq-af-field" style="margin-top:12px;">
            <label class="rq-af-lbl">State / Region <span class="rq-opt">(optional)</span></label>
            <input type="text" class="rq-inp" id="rqDAState" placeholder="State or region">
          </div>
          <div class="rq-af-field">
            <label class="rq-af-lbl">Additional Address Information <span class="rq-opt">(optional)</span></label>
            <textarea class="rq-inp rq-af-textarea" id="rqDAAdditional" placeholder="Special delivery instructions, gate code, access notes…"></textarea>
          </div>
          <div class="rq-af-divider" style="margin-bottom:0;"></div>
          <button type="button" class="rq-af-rcpt-toggle" id="rqDARcptToggle" onclick="rqToggleRcpt('delivery')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            Recipient Details <span class="rq-opt">&nbsp;(Optional)</span>
          </button>
          <div class="rq-af-rcpt-body" id="rqDARcptBody">
            <div class="rq-grid-2">
              <div class="rq-af-field" style="margin:0;">
                <label class="rq-af-lbl">Name</label>
                <input type="text" class="rq-inp" id="rqDARcptName" placeholder="Recipient name">
              </div>
              <div class="rq-af-field" style="margin:0;">
                <label class="rq-af-lbl">Email</label>
                <input type="email" class="rq-inp" id="rqDARcptEmail" placeholder="email@example.com">
              </div>
            </div>
            <div class="rq-af-field" style="margin-top:12px;">
              <label class="rq-af-lbl">Contact Number</label>
              <input type="tel" class="rq-inp" id="rqDARcptPhone" placeholder="Phone number">
            </div>
          </div>
          <div class="rq-af-actions">
            <button type="button" class="rq-af-save" onclick="rqSaveAddrForm('delivery')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Save Address
            </button>
            <button type="button" class="rq-af-cancel" onclick="rqCancelAddrForm('delivery')">Cancel</button>
          </div>
        </div>
      </div>
      <div id="rqDeliveryErr" class="rq-msg rq-err"></div>
    </div>
  </div>

  <!-- ─ 5. BILLING ADDRESS ───────────────────────────────── -->
  <div class="rq-card">
    <div class="rq-card-head">
      <div class="rq-card-icon" style="background:#fdf4ff;">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#9333ea" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
      </div>
      <div>
        <div class="rq-card-title">Billing Address</div>
        <div class="rq-card-sub">Where should we send the invoice?</div>
      </div>
    </div>
    <div class="rq-card-body">
      <label class="rq-same-billing">
        <input type="checkbox" id="rqSameBilling" checked onchange="rqToggleBilling()">
        <span>Same as delivery address</span>
      </label>
      <div id="rqBillingSection">
        <!-- Selected billing address -->
        <div id="rqBillSelectedWrap" style="display:none;">
          <div class="rq-selected-addr" id="rqBillSelectedCard"></div>
        </div>
        <!-- Change billing address list -->
        <div id="rqBillListWrap" style="display:none;">
          <div class="rq-addr-list" id="rqBillAddrList"></div>
        </div>
        <!-- Add new billing address button -->
        <button type="button" class="rq-addr-add-btn" id="rqBillAddBtn" onclick="rqToggleAddrForm('billing')" style="margin-top:10px;width:100%;justify-content:center;">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add New Billing Address
        </button>
        <!-- New billing address form (no recipient details) -->
        <div id="rqBillNewForm" style="display:none;">
          <div class="rq-af">
            <div class="rq-af-title">New Billing Address</div>
            <div class="rq-af-field">
              <span class="rq-af-lbl">Address Label</span>
              <div class="rq-af-chips" id="rqBALabelChips">
                <button type="button" class="rq-af-chip active" data-val="Home" onclick="rqPickLabel('billing',this)">🏠 Home</button>
                <button type="button" class="rq-af-chip" data-val="Office" onclick="rqPickLabel('billing',this)">🏢 Office</button>
                <button type="button" class="rq-af-chip" data-val="Other" onclick="rqPickLabel('billing',this)">📍 Other</button>
              </div>
              <input type="hidden" id="rqBALabel" value="Home">
            </div>
            <div class="rq-af-divider"></div>
            <div class="rq-grid-2">
              <div class="rq-af-field" style="margin:0;">
                <label class="rq-af-lbl">Country <span class="rq-req">*</span></label>
                <select class="rq-inp" id="rqBACountry">
                  <?= $countryOpts ?>
                </select>
              </div>
              <div class="rq-af-field" style="margin:0;">
                <label class="rq-af-lbl">Full Name / Company Name <span class="rq-req">*</span></label>
                <input type="text" class="rq-inp" id="rqBACompany" placeholder="Full name or company name">
              </div>
            </div>
            <div class="rq-grid-2" style="margin-top:12px;">
              <div class="rq-af-field" style="margin:0;">
                <label class="rq-af-lbl">Contact Name <span class="rq-req">*</span></label>
                <input type="text" class="rq-inp" id="rqBAName" placeholder="Contact person name">
              </div>
              <div class="rq-af-field" style="margin:0;">
                <label class="rq-af-lbl">Phone Number <span class="rq-req">*</span></label>
                <div class="rq-phone-wrap">
                  <select class="rq-inp rq-isd" id="rqBAIsd">
                    <option value="91">+91 India</option><option value="1">+1 USA/CA</option><option value="44">+44 UK</option><option value="49">+49 Germany</option><option value="33">+33 France</option><option value="39">+39 Italy</option><option value="34">+34 Spain</option><option value="31">+31 Netherlands</option><option value="61">+61 Australia</option><option value="971">+971 UAE</option><option value="65">+65 Singapore</option><option value="60">+60 Malaysia</option>
                  </select>
                  <input type="tel" class="rq-inp rq-phone-num" id="rqBAPhone" placeholder="Phone number">
                </div>
              </div>
            </div>
            <div class="rq-af-field" style="margin-top:12px;">
              <label class="rq-af-lbl">Address Line 1 <span class="rq-req">*</span></label>
              <input type="text" class="rq-inp" id="rqBALine1" placeholder="Street name and number">
            </div>
            <div class="rq-af-field">
              <label class="rq-af-lbl">Address Line 2 <span class="rq-opt">(optional)</span></label>
              <input type="text" class="rq-inp" id="rqBALine2" placeholder="Apartment, suite, floor, unit">
            </div>
            <div class="rq-af-field">
              <label class="rq-af-lbl">Address Line 3 <span class="rq-opt">(optional)</span></label>
              <input type="text" class="rq-inp" id="rqBALandmark" placeholder="Landmark, area, locality">
            </div>
            <div class="rq-grid-2">
              <div class="rq-af-field" style="margin:0;">
                <label class="rq-af-lbl">Postal / ZIP Code <span class="rq-req">*</span></label>
                <input type="text" class="rq-inp" id="rqBAZip" placeholder="e.g. 400001">
              </div>
              <div class="rq-af-field" style="margin:0;">
                <label class="rq-af-lbl">City <span class="rq-req">*</span></label>
                <input type="text" class="rq-inp" id="rqBACity" placeholder="City">
              </div>
            </div>
            <div class="rq-af-field" style="margin-top:12px;">
              <label class="rq-af-lbl">State / Region <span class="rq-opt">(optional)</span></label>
              <input type="text" class="rq-inp" id="rqBAState" placeholder="State or region">
            </div>
            <div class="rq-af-field">
              <label class="rq-af-lbl">Additional Address Information <span class="rq-opt">(optional)</span></label>
              <textarea class="rq-inp rq-af-textarea" id="rqBAAdditional" placeholder="Special billing instructions, PO number, GST reference…"></textarea>
            </div>
            <div class="rq-af-actions">
              <button type="button" class="rq-af-save" onclick="rqSaveAddrForm('billing')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Save Address
              </button>
              <button type="button" class="rq-af-cancel" onclick="rqCancelAddrForm('billing')">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ─ 6. NOTES ─────────────────────────────────────────── -->
  <div class="rq-card">
    <div class="rq-card-head">
      <div class="rq-card-icon" style="background:#f8fafc;">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4z"/></svg>
      </div>
      <div>
        <div class="rq-card-title">Additional Notes <span class="rq-opt" style="font-size:11px;">(optional)</span></div>
        <div class="rq-card-sub">Special requirements, urgency, certifications, delivery preferences</div>
      </div>
    </div>
    <div class="rq-card-body">
      <textarea class="rq-ta" id="rqNotes" rows="3"
        placeholder="e.g. Need RoHS certified components, delivery within 5 working days…"></textarea>
    </div>
  </div>

  <!-- ─ 7. CAPTCHA ───────────────────────────────────────── -->
  <?php if ($cfSiteKey): ?>
  <div class="rq-card">
    <div class="rq-captcha-row">
      <div class="rq-captcha-left">
        <div class="rq-card-icon" style="background:#fff7ed;flex-shrink:0;">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
          <div class="rq-card-title">Security Verification</div>
          <div class="rq-card-sub">Please complete the check on the right before submitting</div>
        </div>
      </div>
      <div class="rq-captcha-right">
        <div class="cf-turnstile" data-sitekey="<?= $cfSiteKey ?>" data-theme="light"></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ─ SUBMIT ────────────────────────────────────────────── -->
  <div class="rq-card" style="margin-bottom:0;">
    <div class="rq-submit-row">
      <button type="button" class="rq-submit-btn" id="rqSubmitBtn" onclick="rqSubmit()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2" fill="currentColor"/></svg>
        Submit Quote Request
      </button>
      <span class="rq-submit-note">We respond within 24 hours · Your details are kept confidential</span>
    </div>
    <div id="rqSubmitErr" class="rq-msg rq-err" style="margin:0 20px 16px;"></div>
  </div>

</div><!-- /left column -->

<!-- ══ RIGHT: SIDEBAR ════════════════════════════════════════ -->
<div>

  <!-- ─ Why request a quote? ─────────────────────────────── -->
  <div class="rq-side">
    <div class="rq-why-head">
      <div class="rq-why-head-label">Benefits</div>
      <h3 class="rq-why-head-title">Why Request a Quote?</h3>
    </div>
    <div class="rq-why-body">

      <div class="rq-why-item">
        <div class="rq-why-icon" style="background:#fef9c3;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="rq-why-text">
          <strong>Best Price for Bulk &amp; Custom Orders</strong>
          <span>Competitive quotes tailored to your quantity and specification</span>
        </div>
      </div>

      <div class="rq-why-item">
        <div class="rq-why-icon" style="background:#ede9fe;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        </div>
        <div class="rq-why-text">
          <strong>Hard-to-Find Parts Sourced Globally</strong>
          <span>Access our worldwide network of verified distributors and manufacturers</span>
        </div>
      </div>

      <div class="rq-why-item">
        <div class="rq-why-icon" style="background:#dbeafe;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div class="rq-why-text">
          <strong>Dedicated Account Manager</strong>
          <span>A single point of contact who knows your business and requirements</span>
        </div>
      </div>

      <div class="rq-why-item">
        <div class="rq-why-icon" style="background:#dcfce7;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="rq-why-text">
          <strong>100% Genuine Certified Components</strong>
          <span>Every part fully traceable with authenticity certificates available</span>
        </div>
      </div>

      <div class="rq-why-item">
        <div class="rq-why-icon" style="background:#fff7ed;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
        <div class="rq-why-text">
          <strong>Priority Dispatch &amp; Tracking</strong>
          <span>Fast fulfilment with real-time shipment tracking from door to door</span>
        </div>
      </div>

    </div>
  </div>

  <!-- ─ Contact details ──────────────────────────────────── -->
  <div class="rq-side">
    <div class="rq-contact-head">
      <div class="rq-contact-head-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.51 19 19.43 19.43 0 0 1 4.36 12a19.79 19.79 0 0 1-2.29-7.93A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91A16 16 0 0 0 14.09 15.91l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
      </div>
      <div>
        <div class="rq-contact-head-title"><?= $_cName ?></div>
        <div class="rq-contact-head-sub">Get in touch with us</div>
      </div>
    </div>

    <?php if ($_cAddress): ?>
    <div class="rq-contact-block">
      <div class="rq-contact-block-title">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        Address
      </div>
      <p class="rq-address-text"><?= $_cAddress ?></p>
    </div>
    <?php endif; ?>

    <?php if ($_cBranch && $_cBranch !== '<p><br></p>'): ?>
    <div class="rq-contact-block">
      <div class="rq-contact-block-title">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Branch Office
      </div>
      <div style="font-size:13px;line-height:1.7;color:#374151;"><?= $_cBranch ?></div>
    </div>
    <?php endif; ?>

    <?php if ($_cPhone || $_cFax || $_cEmail || $_cSupport || $_cWp || $_cHrs): ?>
    <div class="rq-contact-block">
      <div class="rq-contact-block-title">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.51 19 19.43 19.43 0 0 1 4.36 12a19.79 19.79 0 0 1-2.29-7.93A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91A16 16 0 0 0 14.09 15.91l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        Customer Service &amp; Support
      </div>

      <?php if ($_cPhone): ?>
      <div class="rq-contact-row">
        <div class="rq-contact-row-icon" style="background:#eff6ff;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.51 19 19.43 19.43 0 0 1 4.36 12a19.79 19.79 0 0 1-2.29-7.93A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91A16 16 0 0 0 14.09 15.91l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </div>
        <div>
          <div class="rq-contact-row-lbl">Tel</div>
          <div class="rq-contact-row-val"><a href="tel:<?= preg_replace('/[^+\d]/', '', $company->CONTACT_NUMBER ?? '') ?>"><?= $_cPhone ?></a></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($_cFax): ?>
      <div class="rq-contact-row">
        <div class="rq-contact-row-icon" style="background:#f0fdf4;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M6 9V2h12v20H6v-7"/><polyline points="6 15 2 15 2 9 6 9"/><line x1="10" y1="6" x2="14" y2="6"/><line x1="10" y1="10" x2="14" y2="10"/></svg>
        </div>
        <div>
          <div class="rq-contact-row-lbl">Fax</div>
          <div class="rq-contact-row-val"><?= $_cFax ?></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($_cWp): ?>
      <div class="rq-contact-row">
        <div class="rq-contact-row-icon" style="background:#f0fdf4;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="#16a34a"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
        </div>
        <div>
          <div class="rq-contact-row-lbl">WhatsApp</div>
          <div class="rq-contact-row-val"><a href="<?= htmlspecialchars($_cWpLink) ?>" target="_blank" rel="noopener"><?= $_cWp ?></a></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($_cEmail): ?>
      <div class="rq-contact-row">
        <div class="rq-contact-row-icon" style="background:#fdf4ff;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9333ea" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div>
          <div class="rq-contact-row-lbl">Email</div>
          <div class="rq-contact-row-val"><a href="mailto:<?= $_cEmail ?>"><?= $_cEmail ?></a></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($_cSupport && $_cSupport !== $_cEmail): ?>
      <div class="rq-contact-row">
        <div class="rq-contact-row-icon" style="background:#fff7ed;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div>
          <div class="rq-contact-row-lbl">Support Email</div>
          <div class="rq-contact-row-val"><a href="mailto:<?= $_cSupport ?>"><?= $_cSupport ?></a></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($_cHrs): ?>
      <div class="rq-contact-row">
        <div class="rq-contact-row-icon" style="background:#fff7ed;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
          <div class="rq-contact-row-lbl">Mon – Fri</div>
          <div class="rq-contact-row-val"><?= $_cHrs ?></div>
        </div>
      </div>
      <?php endif; ?>

    </div>
    <?php endif; ?>

    <?php if ($_cFb || $_cLi || $_cTw || $_cYt || $_cIg): ?>
    <div class="rq-socials">
      <?php if ($_cFb): ?><a href="<?= $_cFb ?>" class="rq-soc" target="_blank" rel="noopener" title="Facebook"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a><?php endif; ?>
      <?php if ($_cTw): ?><a href="<?= $_cTw ?>" class="rq-soc" target="_blank" rel="noopener" title="Twitter / X"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.264 5.633 5.9-5.633zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a><?php endif; ?>
      <?php if ($_cLi): ?><a href="<?= $_cLi ?>" class="rq-soc" target="_blank" rel="noopener" title="LinkedIn"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg></a><?php endif; ?>
      <?php if ($_cYt): ?><a href="<?= $_cYt ?>" class="rq-soc" target="_blank" rel="noopener" title="YouTube"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="#fff"/></svg></a><?php endif; ?>
      <?php if ($_cIg): ?><a href="<?= $_cIg ?>" class="rq-soc" target="_blank" rel="noopener" title="Instagram"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ─ Map ──────────────────────────────────────────────── -->
  <div class="rq-side">
    <div class="rq-map-head">
      <div class="rq-map-head-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      </div>
      <div>
        <div class="rq-map-head-title">Our Location</div>
        <div class="rq-map-head-sub"><?= $_cAddress ?: 'Find us on the map' ?></div>
      </div>
    </div>
    <?php if ($_cMapUrl !== ''):
      $rqMapEmbed = preg_replace('/\s*(width|height)=["\'][^"\']*["\']/', '', $_cMapUrl);
    ?>
    <div class="rq-map-frame"><?= $rqMapEmbed ?></div>
    <?php else: ?>
    <div class="rq-map-empty">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      <p>Map not configured</p>
    </div>
    <?php endif; ?>
  </div>

</div>

</div><!-- /layout -->
</div><!-- /wrap -->
</div><!-- /outer -->

<?php if ($cfSiteKey): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>

<script>
/* ── PHP data ────────────────────────────────────────────────── */
var RQ_CAT_GROUPS = <?= json_encode($catGroups,  JSON_UNESCAPED_UNICODE) ?>;
var RQ_ADDRS      = <?= json_encode($addrList,   JSON_UNESCAPED_UNICODE) ?>;
var RQ_AJAX       = 'ajax/quote';
var RQ_HAS_CF     = <?= $cfSiteKey ? 'true' : 'false' ?>;

/* ── State ───────────────────────────────────────────────────── */
var rqRowCount      = 0;
var rqProdCache     = {};
var rqDeliveryId    = 0;   /* selected delivery address id; 0 = new form data */
var rqBillingId     = 0;   /* selected billing address id; 0 = new form data */
var rqDelivTempAddr = null; /* new delivery address data collected from form */
var rqBillTempAddr  = null; /* new billing address data collected from form */
var rqDelivListOpen = false;
var rqBillListOpen  = false;

/* ── Helpers ─────────────────────────────────────────────────── */
function rqFmt(n) {
  return '€' + (parseFloat(n)||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');
}
function rqH(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function rqErr(id,msg) {
  var el=document.getElementById(id);
  if(!el)return;
  el.textContent=msg;
  el.className='rq-msg rq-err'+(msg?' show':'');
}

/* ── Build category optgroup HTML ────────────────────────────── */
function rqCatOpts() {
  var h = '<option value="">Select category…</option>';
  RQ_CAT_GROUPS.forEach(function(g) {
    h += '<optgroup label="'+rqH(g.group)+'">';
    g.cats.forEach(function(c){ h+='<option value="'+c.id+'">'+rqH(c.name)+'</option>'; });
    h += '</optgroup>';
  });
  return h;
}

/* ── Add product row ─────────────────────────────────────────── */
function rqAddRow() {
  rqRowCount++;
  var n = rqRowCount;
  var row = document.createElement('div');
  row.className = 'rq-prod-row';
  row.id = 'rqRow'+n;
  row.innerHTML =
    '<div class="rq-row-num">'+n+'</div>'+
    '<div><select class="rq-sel" id="rqCat'+n+'" onchange="rqOnCat('+n+')">'+rqCatOpts()+'</select></div>'+
    '<div><select class="rq-sel" id="rqProd'+n+'" onchange="rqOnProd('+n+')" disabled><option value="">Select product…</option></select></div>'+
    '<div><input type="number" class="rq-inp" id="rqQty'+n+'" value="1" min="1" oninput="rqCalc('+n+')" style="text-align:center;"></div>'+
    '<div class="rq-row-price" id="rqPrice'+n+'">€0.00</div>'+
    '<div class="rq-row-total" id="rqTotal'+n+'">€0.00</div>'+
    '<button type="button" class="rq-row-del" onclick="rqRemoveRow('+n+')" title="Remove">'+
    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'+
    '</button>';
  document.getElementById('rqProdRows').appendChild(row);
}

function rqRemoveRow(n) {
  var el=document.getElementById('rqRow'+n);
  if(el)el.remove();
  rqUpdateSubtotal();
}

/* ── Category / product loading ──────────────────────────────── */
function rqOnCat(n) {
  var catId = parseInt(document.getElementById('rqCat'+n).value)||0;
  var ps    = document.getElementById('rqProd'+n);
  ps.innerHTML='<option value="">Loading…</option>'; ps.disabled=true;
  document.getElementById('rqPrice'+n).textContent='€0.00';
  document.getElementById('rqTotal'+n).textContent='€0.00';
  rqUpdateSubtotal();
  if(!catId){ps.innerHTML='<option value="">Select product…</option>';ps.disabled=true;return;}
  if(rqProdCache[catId]){rqFillProds(n,rqProdCache[catId]);return;}
  fetch(RQ_AJAX+'?action=get_products&cat_id='+catId)
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.ok){rqProdCache[catId]=d.products;rqFillProds(n,d.products);}
      else{ps.innerHTML='<option value="">Error loading</option>';}
    })
    .catch(function(){ps.innerHTML='<option value="">Error loading</option>';});
}

function rqFillProds(n,prods) {
  var h='<option value="">Select product…</option>';
  prods.forEach(function(p){
    h+='<option value="'+p.ID+'" data-price="'+p.PRICE+'" data-code="'+rqH(p.CODE)+'">'+rqH(p.NAME)+(p.CODE?' ['+rqH(p.CODE)+']':'')+'</option>';
  });
  var el=document.getElementById('rqProd'+n);
  el.innerHTML=h; el.disabled=false;
}

function rqOnProd(n) {
  var sel=document.getElementById('rqProd'+n);
  var opt=sel.options[sel.selectedIndex];
  var price=opt?parseFloat(opt.getAttribute('data-price'))||0:0;
  document.getElementById('rqPrice'+n).textContent=rqFmt(price);
  rqCalc(n);
}

function rqCalc(n) {
  var price=parseFloat((document.getElementById('rqPrice'+n)||{}).textContent.replace('€','').replace(',',''))||0;
  var qty=Math.max(1,parseInt((document.getElementById('rqQty'+n)||{}).value)||1);
  document.getElementById('rqTotal'+n).textContent=rqFmt(price*qty);
  rqUpdateSubtotal();
}

function rqUpdateSubtotal() {
  var subtotal=0;
  document.querySelectorAll('#rqProdRows .rq-prod-row').forEach(function(row){
    var id=row.id.replace('rqRow','');
    var el=document.getElementById('rqTotal'+id);
    if(el)subtotal+=parseFloat(el.textContent.replace('€','').replace(',',''))||0;
  });
  var vat     = subtotal * 0.18;
  var vatNum  = (document.getElementById('rqVatNumber')||{value:''}).value.trim();
  var hasVat  = vatNum.length > 0;
  var total   = hasVat ? subtotal : subtotal + vat;

  document.getElementById('rqSubtotal').textContent   = rqFmt(subtotal);
  document.getElementById('rqVatAmt').textContent      = rqFmt(vat);
  document.getElementById('rqVatRebateAmt').textContent= '−'+rqFmt(vat);
  document.getElementById('rqTotal').textContent       = rqFmt(total);
  document.getElementById('rqVatRebateRow').style.display = hasVat ? 'flex' : 'none';
  document.getElementById('rqVatRow').style.display        = hasVat ? 'none' : 'flex';
}

/* ── Address helpers ─────────────────────────────────────────── */
function rqAddrCardHtml(a) {
  /* Saved address — keys from RQ_ADDRS (PHP $addrList) */
  var lines=[a.line1,a.line2,a.landmark,a.city,a.state,a.zip,a.country].filter(Boolean);
  return '<div>'
    +'<span class="rq-addr-chip">'+rqH(a.label||'Home')+'</span>'
    +(a.company?'<div class="rq-addr-main">'+rqH(a.company)+'</div>':'')
    +(a.user_name?'<div class="rq-addr-meta" style="font-weight:600;color:#374151;">'+rqH(a.user_name)+'</div>':'')
    +'<div class="rq-addr-meta">'+rqH(lines.join(', '))+'</div>'
    +(a.phone?'<div class="rq-addr-meta">📞 '+rqH(a.phone)+'</div>':'')
    +'</div>';
}

function rqTempAddrCardHtml(a) {
  /* New (unsaved) address — keys from rqSaveAddrForm */
  var lines=[a.address_line_one,a.address_line_two,a.landmark,a.city,a.state,a.zip,a.country].filter(Boolean);
  return '<div>'
    +'<span class="rq-addr-chip">'+rqH(a.label||'Home')+'</span>'
    +(a.company_name?'<div class="rq-addr-main">'+rqH(a.company_name)+'</div>':'')
    +(a.user_name?'<div class="rq-addr-meta" style="font-weight:600;color:#374151;">'+rqH(a.user_name)+'</div>':'')
    +'<div class="rq-addr-meta">'+rqH(lines.join(', '))+'</div>'
    +(a.delivery_phone_no?'<div class="rq-addr-meta">📞 '+rqH(a.delivery_phone_no)+'</div>':'')
    +'</div>';
}

/* ── Delivery init ───────────────────────────────────────────── */
function rqInitDelivery() {
  if(!RQ_ADDRS.length){
    /* No saved addresses — show new form directly */
    document.getElementById('rqDelivNewForm').style.display='block';
    rqDeliveryId=0;
    return;
  }
  /* Select first address by default */
  var first=RQ_ADDRS[0];
  rqDeliveryId=first.id;
  rqDelivTempAddr=null;
  var wrap=document.getElementById('rqDelivSelectedWrap');
  var card=document.getElementById('rqDelivSelectedCard');
  card.innerHTML=rqSelectedHtml('delivery',first);
  wrap.style.display='block';
}

function rqSelectedHtml(type, a) {
  var changeBtn='<button type="button" class="rq-addr-change-btn" onclick="rqToggleChangeList(\''+type+'\')">Change</button>';
  return '<div class="rq-selected-addr-row">'+rqAddrCardHtml(a)+changeBtn+'</div>';
}

function rqTempSelectedHtml(type, a) {
  var changeBtn='<button type="button" class="rq-addr-change-btn" onclick="rqToggleAddrForm(\''+type+'\')">Edit</button>';
  return '<div class="rq-selected-addr-row">'+rqTempAddrCardHtml(a)+changeBtn+'</div>';
}

/* ── Toggle the change-address list ─────────────────────────── */
function rqToggleChangeList(type) {
  var isDeliv = type==='delivery';
  var listWrapId = isDeliv?'rqDelivListWrap':'rqBillListWrap';
  var listId     = isDeliv?'rqDelivAddrList':'rqBillAddrList';
  var listOpen   = isDeliv?rqDelivListOpen:rqBillListOpen;

  if(listOpen){
    document.getElementById(listWrapId).style.display='none';
    if(isDeliv) rqDelivListOpen=false; else rqBillListOpen=false;
    return;
  }
  /* Build the list */
  var currentId = isDeliv?rqDeliveryId:rqBillingId;
  var html='';
  RQ_ADDRS.forEach(function(a){
    var isPicked = (a.id===currentId);
    var lines=[a.line1,a.line2,a.landmark,a.city,a.state,a.zip,a.country].filter(Boolean);
    html+='<div class="rq-addr-list-item'+(isPicked?' picked':'')+'" onclick="rqPickSavedAddr(\''+type+'\','+a.id+')">'
      +'<input type="radio" class="rq-addr-list-radio"'+(isPicked?' checked':'')+' readonly>'
      +'<div>'
      +'<span class="rq-addr-chip">'+rqH(a.label||'Home')+'</span>'
      +(a.company?'<div class="rq-addr-main">'+rqH(a.company)+'</div>':'')
      +(a.name?'<div class="rq-addr-meta" style="font-weight:600;color:#374151;">'+rqH(a.name)+'</div>':'')
      +'<div class="rq-addr-meta">'+rqH(lines.join(', '))+'</div>'
      +(a.phone?'<div class="rq-addr-meta">📞 '+rqH(a.phone)+'</div>':'')
      +'</div>'
      +'</div>';
  });
  document.getElementById(listId).innerHTML=html;
  document.getElementById(listWrapId).style.display='block';
  if(isDeliv) rqDelivListOpen=true; else rqBillListOpen=true;
}

function rqPickSavedAddr(type, id) {
  var isDeliv=type==='delivery';
  var addr=null;
  RQ_ADDRS.forEach(function(a){ if(a.id===id)addr=a; });
  if(!addr)return;

  if(isDeliv){ rqDeliveryId=id; rqDelivTempAddr=null; }
  else       { rqBillingId=id;  rqBillTempAddr=null;  }

  /* Update selected card */
  var cardId = isDeliv?'rqDelivSelectedCard':'rqBillSelectedCard';
  var wrapId = isDeliv?'rqDelivSelectedWrap':'rqBillSelectedWrap';
  document.getElementById(cardId).innerHTML=rqSelectedHtml(type,addr);
  document.getElementById(wrapId).style.display='block';

  /* Hide list */
  document.getElementById(isDeliv?'rqDelivListWrap':'rqBillListWrap').style.display='none';
  if(isDeliv) rqDelivListOpen=false; else rqBillListOpen=false;
}

/* ── Toggle new address form (from header button) ────────────── */
function rqToggleAddrForm(type) {
  var isDeliv=type==='delivery';
  var formId = isDeliv?'rqDelivNewForm':'rqBillNewForm';
  var isOpen = document.getElementById(formId).style.display==='block';
  if(isOpen){
    document.getElementById(formId).style.display='none';
  } else {
    document.getElementById(formId).style.display='block';
    document.getElementById(formId).scrollIntoView({behavior:'smooth',block:'nearest'});
  }
}

/* ── Label chip picker ───────────────────────────────────────── */
function rqPickLabel(type, btn) {
  var chipsId=type==='delivery'?'rqDALabelChips':'rqBALabelChips';
  var inputId=type==='delivery'?'rqDALabel':'rqBALabel';
  document.querySelectorAll('#'+chipsId+' .rq-af-chip').forEach(function(c){c.classList.remove('active');});
  btn.classList.add('active');
  document.getElementById(inputId).value=btn.getAttribute('data-val');
}

/* ── Save new address form ───────────────────────────────────── */
/* ── Recipient Details toggle ────────────────────────────────── */
function rqToggleRcpt(type) {
  var btnId  = type==='delivery'?'rqDARcptToggle':'rqBARcptToggle';
  var bodyId = type==='delivery'?'rqDARcptBody':'rqBARcptBody';
  var btn    = document.getElementById(btnId);
  var body   = document.getElementById(bodyId);
  if(!btn||!body)return;
  var open = body.classList.contains('open');
  body.classList.toggle('open',!open);
  btn.classList.toggle('open',!open);
}

function rqSaveAddrForm(type) {
  var isDeliv=type==='delivery';
  var p=isDeliv?'rqDA':'rqBA';
  function v(id){var el=document.getElementById(id);return el?el.value.trim():'';}
  var addr={
    label:               v(p+'Label')||'Home',
    country:             v(p+'Country'),
    company_name:        v(p+'Company'),
    user_name:           v(p+'Name'),
    delivery_phone_no:   v(p+'Phone'),
    address_line_one:    v(p+'Line1'),
    address_line_two:    v(p+'Line2'),
    landmark:            v(p+'Landmark'),
    zip:                 v(p+'Zip'),
    city:                v(p+'City'),
    state:               v(p+'State'),
    additional_info:     v(p+'Additional'),
    mobile_country_code: parseInt(v(p+'Isd'))||0,
    recipient_name:      isDeliv ? v('rqDARcptName')  : '',
    recipient_email:     isDeliv ? v('rqDARcptEmail') : '',
    recipient_contact:   isDeliv ? v('rqDARcptPhone') : '',
  };
  /* Validate */
  var errId=isDeliv?'rqDeliveryErr':'rqSubmitErr';
  if(!addr.country){rqErr(errId,'Please select a country.');return;}
  if(!addr.company_name){rqErr(errId,'Please enter full name / company name.');return;}
  if(!addr.user_name){rqErr(errId,'Please enter contact name.');return;}
  if(!addr.delivery_phone_no){rqErr(errId,'Please enter phone number.');return;}
  if(!addr.address_line_one){rqErr(errId,'Please enter address line 1.');return;}
  if(!addr.zip){rqErr(errId,'Please enter postal / ZIP code.');return;}
  if(!addr.city){rqErr(errId,'Please enter city.');return;}
  rqErr(errId,'');

  /* Store and show as selected */
  if(isDeliv){ rqDelivTempAddr=addr; rqDeliveryId=0; }
  else       { rqBillTempAddr=addr;  rqBillingId=0;  }

  var cardId=isDeliv?'rqDelivSelectedCard':'rqBillSelectedCard';
  var wrapId=isDeliv?'rqDelivSelectedWrap':'rqBillSelectedWrap';
  document.getElementById(cardId).innerHTML=rqTempSelectedHtml(type,addr);
  document.getElementById(wrapId).style.display='block';
  document.getElementById(isDeliv?'rqDelivNewForm':'rqBillNewForm').style.display='none';
}

function rqCancelAddrForm(type) {
  var isDeliv=type==='delivery';
  document.getElementById(isDeliv?'rqDelivNewForm':'rqBillNewForm').style.display='none';
}

/* ── Billing toggle ──────────────────────────────────────────── */
function rqToggleBilling() {
  var same=document.getElementById('rqSameBilling').checked;
  document.getElementById('rqBillingSection').style.display=same?'none':'block';
  if(!same) rqInitBilling();
}

function rqInitBilling() {
  if(!RQ_ADDRS.length){
    document.getElementById('rqBillNewForm').style.display='block';
    rqBillingId=0;
    return;
  }
  if(rqBillingId>0) return; /* already initialised */
  var first=RQ_ADDRS[0];
  rqBillingId=first.id;
  rqBillTempAddr=null;
  var card=document.getElementById('rqBillSelectedCard');
  var wrap=document.getElementById('rqBillSelectedWrap');
  card.innerHTML=rqSelectedHtml('billing',first);
  wrap.style.display='block';
}

/* ── Collect address for submission ─────────────────────────── */
function rqCollectAddr(type) {
  /* Returns the stored temp address or null */
  return type==='delivery' ? rqDelivTempAddr : rqBillTempAddr;
}

/* ── Collect products ────────────────────────────────────────── */
function rqGetProducts() {
  var out=[];
  document.querySelectorAll('#rqProdRows .rq-prod-row').forEach(function(row){
    var id=row.id.replace('rqRow','');
    var catSel  =document.getElementById('rqCat'+id);
    var prodSel =document.getElementById('rqProd'+id);
    var qtyEl   =document.getElementById('rqQty'+id);
    var priceEl =document.getElementById('rqPrice'+id);
    if(!catSel||!prodSel)return;
    var catId  =parseInt(catSel.value)||0;
    var prodId =parseInt(prodSel.value)||0;
    var qty    =parseInt(qtyEl?qtyEl.value:1)||1;
    var price  =parseFloat(priceEl?priceEl.textContent.replace('€','').replace(',',''):0)||0;
    if(catId>0&&prodId>0&&qty>0) out.push({cat_id:catId,prod_id:prodId,qty:qty,price:price});
  });
  return out;
}

/* ── Submit ──────────────────────────────────────────────────── */
function rqSubmit() {
  /* Validate products */
  var products=rqGetProducts();
  if(!products.length){rqErr('rqProdErr','Please add at least one product with category, product and quantity selected.');return;}
  rqErr('rqProdErr','');
  rqErr('rqSubmitErr','');
  rqErr('rqDeliveryErr','');

  /* Block submit if an address form is open but not yet saved */
  var delivFormOpen = document.getElementById('rqDelivNewForm') && document.getElementById('rqDelivNewForm').style.display==='block';
  var billFormOpen  = document.getElementById('rqBillNewForm')  && document.getElementById('rqBillNewForm').style.display==='block'
                      && !document.getElementById('rqSameBilling').checked;
  if(delivFormOpen){
    rqErr('rqDeliveryErr','Please save or cancel the delivery address form before submitting.');
    document.getElementById('rqDelivNewForm').scrollIntoView({behavior:'smooth',block:'center'});
    return;
  }
  if(billFormOpen){
    rqErr('rqSubmitErr','Please save or cancel the billing address form before submitting.');
    document.getElementById('rqBillNewForm').scrollIntoView({behavior:'smooth',block:'center'});
    return;
  }

  /* Validate user details */
  var uName  =(document.getElementById('rqUserName') ||{value:''}).value.trim();
  var uPhoneIsd=(document.getElementById('rqUserPhoneIsd')||{value:''}).value.trim();
  var uPhone =(document.getElementById('rqUserPhone')||{value:''}).value.trim();
  if(!uName){ rqErr('rqSubmitErr','Please enter your full name.'); document.getElementById('rqUserName').focus(); return; }
  if(!uPhone){ rqErr('rqSubmitErr','Please enter your phone number.'); document.getElementById('rqUserPhone').focus(); return; }

  /* Build payload */
  var payload={
    products:    products,
    user_name:        uName,
    user_phone:       uPhone,
    user_phone_isd:   uPhoneIsd,
    user_company:(document.getElementById('rqUserCompany')||{value:''}).value.trim(),
    vat_number:  (document.getElementById('rqVatNumber')||{value:''}).value.trim(),
    vat_pct:     18,
    vat_rebated: (document.getElementById('rqVatNumber')||{value:''}).value.trim().length > 0,
    notes:       (document.getElementById('rqNotes')      ||{value:''}).value.trim(),
  };

  /* Delivery address */
  if(rqDeliveryId>0){
    payload.delivery_address_id=rqDeliveryId;
  } else if(rqDelivTempAddr){
    payload.new_delivery_address=rqDelivTempAddr;
  } else {
    rqErr('rqDeliveryErr','Please save a delivery address before submitting.');
    document.getElementById('rqDelivSelectedWrap').scrollIntoView({behavior:'smooth',block:'center'});
    return;
  }

  /* Billing address */
  var sameBilling=document.getElementById('rqSameBilling').checked;
  if(sameBilling){
    payload.billing_same_as_delivery=true;
  } else if(rqBillingId>0){
    payload.billing_address_id=rqBillingId;
  } else if(rqBillTempAddr){
    payload.new_billing_address=rqBillTempAddr;
  } else {
    rqErr('rqSubmitErr','Please save a billing address before submitting.');
    return;
  }

  /* Cloudflare Turnstile token */
  if(RQ_HAS_CF){
    var cfInput=document.querySelector('[name="cf-turnstile-response"]');
    if(!cfInput||!cfInput.value){ rqErr('rqSubmitErr','Please complete the security verification.'); return; }
    payload.cf_token=cfInput.value;
  } else {
    payload.cf_token='';
  }

  /* Send */
  var btn=document.getElementById('rqSubmitBtn');
  btn.disabled=true;
  btn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:rqSpin 1s linear infinite"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Submitting…';

  fetch(RQ_AJAX+'?action=submit',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(payload)
  })
  .then(function(r){return r.json();})
  .then(function(d){
    btn.disabled=false;
    btn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2" fill="currentColor"/></svg> Submit Quote Request';
    if(d.ok){
      /* Hide all form cards, show success */
      document.querySelectorAll('#rqFormWrap, .rq-card:not(#rqSuccess)').forEach(function(c){c.style.display='none';});
      var s=document.getElementById('rqSuccess');
      s.classList.add('show');
      s.style.display='block';
      document.getElementById('rqSuccessRef').textContent='Reference: #'+d.quote_id;
      window.scrollTo({top:0,behavior:'smooth'});
    } else {
      rqErr('rqSubmitErr', d.msg||'Something went wrong. Please try again.');
    }
  })
  .catch(function(){
    btn.disabled=false;
    btn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2" fill="currentColor"/></svg> Submit Quote Request';
    rqErr('rqSubmitErr','Network error. Please check your connection and try again.');
  });
}

/* ── Init ────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
  rqAddRow();
  rqInitDelivery();
});
</script>

<?php require_once 'footer.php'; ?>
