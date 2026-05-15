<?php
require_once __DIR__ . '/account-helpers.php';
$user = sinelec_require_login();
$currentPage = 'delivery-address';
$pageTitle = 'Delivery Address | Sinelec Technologies';
require_once __DIR__ . '/header.php';

$fullName = trim((string)($user['NAME'] ?? 'Sinelec Customer'));
$mobile = '+' . trim((string)($user['COMMUNICATION_MOBILE_NUM_ISD'] ?? '91')) . ' ' . trim((string)($user['COMMUNICATION_MOBILE_NUM'] ?? ''));
?>

<main class="account-page">
  <div class="wrap">
    <div class="account-shell">
      <?php sinelec_render_account_nav($currentPage); ?>

      <section class="account-main da-main">
        <div class="da-page-head">
          <h1 class="da-page-title">My Addresses</h1>
          <button type="button" class="da-add-btn" id="toggleNewAddressForm" aria-expanded="false" aria-controls="newAddressPanel">
            <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 1v13M1 7.5h13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Add New Address
          </button>
        </div>

        <!-- ── Form panel ── -->
        <div class="da-form-card" id="newAddressPanel" hidden>
          <div class="da-form-card-head">
            <span id="daFormCardTitle">New Address</span>
          </div>

          <form id="accountAddressForm" novalidate>
            <input type="hidden" id="accountAddrId" value="">

            <!-- Address Label tabs -->
            <div class="da-tabs-group">
              <p class="da-tabs-label">Address Label</p>
              <div class="da-tabs" role="group" aria-label="Address type">
                <input type="hidden" id="accountAddrLabel" value="Home">
                <button type="button" class="da-tab is-active" data-tab-val="Home">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 21V12h6v9" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                  Home
                </button>
                <button type="button" class="da-tab" data-tab-val="Office">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="2" y="7" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 12v3M10 13.5h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                  Office
                </button>
                <button type="button" class="da-tab" data-tab-val="Others">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="1.6"/></svg>
                  Others
                </button>
              </div>
            </div>

            <!-- Main fields -->
            <div class="da-grid">

              <div class="da-field">
                <label for="accountAddrCountry">Country <span class="da-ast">*</span></label>
                <input type="text" id="accountAddrCountry" class="da-input" placeholder="e.g. India" autocomplete="country-name">
              </div>

              <div class="da-field">
                <label for="accountAddrFullName">Full Name / Company Name <span class="da-ast">*</span></label>
                <input type="text" id="accountAddrFullName" class="da-input" placeholder="Full name or company name" autocomplete="organization">
              </div>

              <div class="da-field">
                <label for="accountAddrName">Contact Name <span class="da-ast">*</span></label>
                <input type="text" id="accountAddrName" class="da-input" value="<?= htmlspecialchars($fullName) ?>" autocomplete="name">
              </div>

              <div class="da-field">
                <label for="accountAddrPhone">Phone Number <span class="da-ast">*</span></label>
                <input type="tel" id="accountAddrPhone" class="da-input" value="<?= htmlspecialchars($mobile) ?>" autocomplete="tel">
              </div>

              <div class="da-field da-field--full">
                <label for="accountAddrLine1">Address Line 1 <span class="da-ast">*</span></label>
                <input type="text" id="accountAddrLine1" class="da-input" placeholder="Street name and number" autocomplete="address-line1">
              </div>

              <div class="da-field da-field--full">
                <label for="accountAddrLine2">Address Line 2</label>
                <input type="text" id="accountAddrLine2" class="da-input" placeholder="Apartment, suite, floor, unit" autocomplete="address-line2">
              </div>

              <div class="da-field da-field--full">
                <label for="accountAddrLine3">Address Line 3</label>
                <input type="text" id="accountAddrLine3" class="da-input" placeholder="Landmark, area, locality">
              </div>

              <!-- Postal + City + State in one row -->
              <div class="da-field da-field--full">
                <div class="da-row3">
                  <div class="da-field">
                    <label for="accountAddrPin">Postal Code <span class="da-ast">*</span></label>
                    <input type="text" id="accountAddrPin" class="da-input" placeholder="6-digit postal code" maxlength="10" autocomplete="postal-code">
                    <span class="da-hint" id="postalLookupStatus"></span>
                  </div>
                  <div class="da-field">
                    <label for="accountAddrCity">City <span class="da-ast">*</span></label>
                    <input type="text" id="accountAddrCity" class="da-input" placeholder="City" autocomplete="address-level2">
                  </div>
                  <div class="da-field">
                    <label for="accountAddrState">State / Region</label>
                    <input type="text" id="accountAddrState" class="da-input" placeholder="State or region" autocomplete="address-level1">
                  </div>
                </div>
              </div>

              <div class="da-field da-field--full">
                <label for="accountAddrExtra">Additional Address Information</label>
                <textarea id="accountAddrExtra" class="da-input da-textarea" placeholder="Special delivery instructions, gate code, access notes…"></textarea>
              </div>

              <!-- Recipient Details -->
              <div class="da-field da-field--full">
                <div class="da-section-sep">
                  <span class="da-section-name">Recipient Details</span>
                  <span class="da-optional-pill">Optional</span>
                </div>
              </div>

              <div class="da-field da-field--full">
                <div class="da-row3">
                  <div class="da-field">
                    <label for="accountRecipientName">Name</label>
                    <input type="text" id="accountRecipientName" class="da-input" placeholder="Recipient name">
                  </div>
                  <div class="da-field">
                    <label for="accountRecipientEmail">Email</label>
                    <input type="email" id="accountRecipientEmail" class="da-input" placeholder="email@example.com">
                  </div>
                  <div class="da-field">
                    <label for="accountRecipientPhone">Contact Number</label>
                    <input type="tel" id="accountRecipientPhone" class="da-input" placeholder="Phone number">
                  </div>
                </div>
              </div>

            </div><!-- /.da-grid -->

            <div class="da-actions">
              <button type="submit" class="da-btn da-btn--primary" id="addressSubmitBtn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 12l5 5L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Save Address
              </button>
              <button type="button" class="da-btn da-btn--ghost" id="cancelEditAddressBtn" hidden>Cancel</button>
              <button type="reset" class="da-btn da-btn--ghost" id="addressFormReset">Reset</button>
            </div>

          </form>
        </div><!-- /.da-form-card -->

        <!-- ── Address list ── -->
        <div class="da-list" id="accountAddressGrid"></div>

      </section>
    </div>
  </div>
</main>

<style>
/* ─── Page layout ─────────────────────────────────────────── */
.da-main {
  gap: 0;
  padding: 0;
}
.da-page-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}
.da-page-title {
  font-size: clamp(1.05rem, 1.6vw, 1.35rem);
  font-weight: 700;
  color: #1a2332;
  margin: 0;
}
.da-add-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  height: 38px;
  padding: 0 18px;
  background: #2563eb;
  color: #fff;
  border-radius: 9px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: .01em;
  transition: background .15s, transform .1s;
  white-space: nowrap;
}
.da-add-btn:hover  { background: #1d4ed8; }
.da-add-btn:active { transform: scale(.97); }

/* ─── Form card ───────────────────────────────────────────── */
.da-form-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(15,30,55,.06);
  margin-bottom: 24px;
  overflow: hidden;
}
.da-form-card-head {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 22px;
  background: #f8fafc;
  border-bottom: 1px solid #e8edf3;
  font-size: 12px;
  font-weight: 700;
  color: #3a4f66;
  letter-spacing: .04em;
  text-transform: uppercase;
}
.da-form-card form {
  padding: 22px;
}

/* ─── Address label tabs ──────────────────────────────────── */
.da-tabs-group {
  margin-bottom: 22px;
}
.da-tabs-label {
  font-size: 11px;
  font-weight: 600;
  color: #5b6b7c;
  margin-bottom: 8px;
}
.da-tabs {
  display: inline-flex;
  gap: 0;
  background: #f0f4f8;
  border-radius: 11px;
  padding: 4px;
}
.da-tab {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 36px;
  padding: 0 18px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #5b6b7c;
  background: transparent;
  transition: background .18s, color .18s, box-shadow .18s;
  white-space: nowrap;
}
.da-tab svg { flex-shrink: 0; opacity: .7; transition: opacity .18s; }
.da-tab:hover:not(.is-active) { color: #2563eb; background: rgba(37,99,235,.06); }
.da-tab.is-active {
  background: #fff;
  color: #2563eb;
  box-shadow: 0 1px 6px rgba(15,30,55,.12);
}
.da-tab.is-active svg { opacity: 1; }

/* ─── Form grid ───────────────────────────────────────────── */
.da-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px 18px;
  align-items: start;
}
.da-field--full { grid-column: 1 / -1; }

/* ─── Field + input base ──────────────────────────────────── */
.da-field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.da-field label {
  font-size: 11px;
  font-weight: 600;
  color: #5b6b7c;
  line-height: 1;
}
.da-ast { color: #e53935; }
.da-input {
  width: 100%;
  height: 42px;
  padding: 0 12px;
  border: 1.5px solid #d0d7e2;
  border-radius: 8px;
  font-size: 13px;
  color: #1a2332;
  background: #fff;
  transition: border-color .15s, box-shadow .15s;
  outline: none;
  box-sizing: border-box;
}
.da-input::placeholder { color: #a0aec0; }
.da-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}
.da-input:hover:not(:focus) { border-color: #b0bdd0; }
.da-textarea {
  height: auto;
  min-height: 80px;
  padding: 10px 12px;
  resize: vertical;
  line-height: 1.5;
}
select.da-input {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='7' viewBox='0 0 11 7'%3E%3Cpath d='M1 1l4.5 5L10 1' stroke='%23718096' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 11px center;
  padding-right: 30px;
  cursor: pointer;
}

/* postal hint */
.da-hint {
  font-size: 10.5px;
  min-height: 14px;
  padding-left: 1px;
  line-height: 1.3;
}
.da-hint.is-loading { color: #6b8299; }
.da-hint.is-ok   { color: #15803d; }
.da-hint.is-warn { color: #dc2626; }

/* ─── Recipient section separator ────────────────────────── */
.da-section-sep {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 0 2px;
  border-top: 1px solid #edf0f5;
  margin-top: 6px;
}
.da-section-name {
  font-size: 11.5px;
  font-weight: 700;
  color: #2d4160;
  letter-spacing: .02em;
}
.da-optional-pill {
  font-size: 10px;
  font-weight: 600;
  color: #7a92ab;
  background: #eef2f7;
  padding: 2px 9px;
  border-radius: 20px;
}

/* 3-column recipient row */
.da-row3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 14px 18px;
  align-items: start;
}

/* ─── Action buttons ──────────────────────────────────────── */
.da-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 20px;
  flex-wrap: wrap;
}
.da-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 40px;
  padding: 0 22px;
  border-radius: 9px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .01em;
  transition: background .15s, border-color .15s, transform .1s;
  white-space: nowrap;
}
.da-btn:active { transform: scale(.97); }
.da-btn--primary {
  background: #2563eb;
  color: #fff;
  border: 1.5px solid #2563eb;
}
.da-btn--primary:hover { background: #1d4ed8; border-color: #1d4ed8; }
.da-btn--ghost {
  background: #fff;
  color: #3a4f66;
  border: 1.5px solid #d0d7e2;
}
.da-btn--ghost:hover { border-color: #a0aec0; color: #1a2332; }

/* ─── Address list ────────────────────────────────────────── */
.da-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.da-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 1px 6px rgba(15,30,55,.05);
  transition: box-shadow .15s;
  position: relative;
}
.da-card:hover { box-shadow: 0 4px 16px rgba(15,30,55,.1); }

.da-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 12px 18px;
  background: #f8fafc;
  border-bottom: 1px solid #edf0f5;
  border-radius: 12px 12px 0 0;
}
.da-badge-row {
  display: flex;
  align-items: center;
  gap: 7px;
  flex-wrap: wrap;
}
.da-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 26px;
  padding: 0 11px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
}
.da-badge--home   { background: #dcfce7; color: #15803d; }
.da-badge--office { background: #dbeafe; color: #1d4ed8; }
.da-badge--others { background: #fef9c3; color: #854d0e; }
.da-badge--default {
  background: #f0f4ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
}

/* 3-dot action menu */
.da-menu {
  position: relative;
}
.da-menu-trigger {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 7px;
  color: #7a92ab;
  font-size: 18px;
  line-height: 1;
  transition: background .15s, color .15s;
}
.da-menu-trigger:hover { background: #edf0f5; color: #2d4160; }
.da-menu > details summary { list-style: none; }
.da-menu > details summary::-webkit-details-marker { display: none; }
.da-menu-pop {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  min-width: 150px;
  background: #fff;
  border: 1px solid #dde3ec;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(15,30,55,.16);
  padding: 5px;
  z-index: 200;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.da-menu-item {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 34px;
  padding: 0 12px;
  border-radius: 7px;
  font-size: 12px;
  font-weight: 600;
  color: #2d4160;
  text-align: left;
  width: 100%;
  transition: background .12s;
}
.da-menu-item:hover { background: #f0f4f8; }
.da-menu-item--danger { color: #dc2626; }
.da-menu-item--danger:hover { background: #fff5f5; }

/* Card body */
.da-card-body {
  padding: 16px 18px;
}
.da-card-fullname {
  font-size: 15px;
  font-weight: 700;
  color: #1a2332;
  margin-bottom: 4px;
}
.da-card-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 18px;
  font-size: 12px;
  color: #4a6179;
  margin-bottom: 10px;
}
.da-card-meta span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.da-card-address {
  font-size: 13px;
  color: #3a5168;
  line-height: 1.55;
  margin-top: 6px;
}
.da-card-extra {
  margin-top: 8px;
  padding: 7px 10px;
  background: #fffbeb;
  border-left: 3px solid #f59e0b;
  border-radius: 0 6px 6px 0;
  font-size: 11.5px;
  color: #78501a;
  font-style: italic;
}

/* Recipient footer */
.da-card-recipient {
  margin: 12px -18px -16px;
  padding: 10px 18px;
  background: #f8fafc;
  border-top: 1px solid #edf0f5;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px 18px;
}
.da-recipient-label {
  font-size: 10px;
  font-weight: 700;
  color: #7a92ab;
  text-transform: uppercase;
  letter-spacing: .04em;
  flex-basis: 100%;
  margin-bottom: 2px;
}
.da-recipient-item {
  font-size: 12px;
  color: #3a5168;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

/* Empty state */
.da-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 44px 20px;
  background: #fff;
  border: 1px dashed #d0d7e2;
  border-radius: 12px;
  text-align: center;
}
.da-empty svg { color: #c0ccd8; }
.da-empty p { font-size: 13px; color: #7a92ab; margin: 0; }

/* ─── Responsive ──────────────────────────────────────────── */
@media (max-width: 860px) {
  .da-row3 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 680px) {
  .da-grid { grid-template-columns: 1fr; gap: 12px; }
  .da-field--full { grid-column: 1; }
  .da-row3 { grid-template-columns: 1fr; gap: 12px; }
  .da-form-card form { padding: 16px; }
}
@media (max-width: 520px) {
  .da-tabs { width: 100%; display: flex; }
  .da-tab  { flex: 1; padding: 0 8px; justify-content: center; font-size: 11.5px; }
  .da-tab svg { display: none; }
  .da-actions { gap: 8px; }
  .da-btn { height: 38px; padding: 0 14px; font-size: 11.5px; }
  .da-btn--primary { flex: 1; justify-content: center; }
  .da-card-head { padding: 10px 14px; }
  .da-card-body { padding: 13px 14px; }
  .da-card-recipient { margin: 10px -14px -13px; padding: 9px 14px; }
  .da-card-fullname { font-size: 14px; }
  .da-card-address { font-size: 12px; }
}
@media (max-width: 400px) {
  .da-add-btn { height: 34px; padding: 0 13px; font-size: 11px; }
  .da-form-card form { padding: 13px; }
  .da-grid { gap: 10px; }
  .da-input { height: 40px; font-size: 12.5px; }
  .da-btn { height: 36px; font-size: 11px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

  /* ── DOM refs ── */
  var grid               = document.getElementById('accountAddressGrid');
  var form               = document.getElementById('accountAddressForm');
  var toggleBtn          = document.getElementById('toggleNewAddressForm');
  var panel              = document.getElementById('newAddressPanel');
  var addrIdInput        = document.getElementById('accountAddrId');
  var submitBtn          = document.getElementById('addressSubmitBtn');
  var cancelBtn          = document.getElementById('cancelEditAddressBtn');
  var resetBtn           = document.getElementById('addressFormReset');
  var postalInput        = document.getElementById('accountAddrPin');
  var postalHint         = document.getElementById('postalLookupStatus');
  var formTitle          = document.getElementById('daFormCardTitle');

  if (!grid || !form) return;

  var ADDRESS_KEY  = 'sinelec_checkout_addresses';
  var SELECTED_KEY = 'sinelec_checkout_selected_address';

  /* ── Dummy data ── */
  var DUMMY = [
    {
      id: 'addr_demo_1', label: 'Home', isDefault: true,
      country: 'India', fullName: 'Akhilesh Kumar',
      name: 'Akhilesh Kumar', phone: '+91 98456 12345',
      line1: 'Flat 4B, Sunrise Apartments', line2: 'MG Road, Ernakulam',
      line3: 'Near Lulu Mall', zip: '682016', city: 'Ernakulam', state: 'Kerala',
      extra: 'Ring doorbell twice. Security gate passcode: 1234.',
      recipientName: '', recipientEmail: '', recipientPhone: ''
    },
    {
      id: 'addr_demo_2', label: 'Office', isDefault: false,
      country: 'India', fullName: 'Sinelec Technologies Pvt Ltd',
      name: 'Akhilesh Kumar', phone: '+91 80 4567 8901',
      line1: '3rd Floor, Tech Park', line2: 'Outer Ring Road, Bellandur',
      line3: 'Near Wipro Corporate Office', zip: '560103', city: 'Bengaluru', state: 'Karnataka',
      extra: 'Reception on ground floor. Mention PO number on arrival.',
      recipientName: 'Store Manager', recipientEmail: 'store@sinelec-tech.com', recipientPhone: '+91 80 4567 8902'
    },
    {
      id: 'addr_demo_3', label: 'Others', isDefault: false,
      country: 'India', fullName: 'Kumar Family',
      name: 'Rajesh Kumar', phone: '+91 98209 34567',
      line1: 'B-14, Hill View Society', line2: 'Andheri West',
      line3: 'Near Versova Metro Station', zip: '400058', city: 'Mumbai', state: 'Maharashtra',
      extra: '',
      recipientName: 'Priya Kumar', recipientEmail: 'priya.kumar@example.com', recipientPhone: '+91 98765 43210'
    }
  ];

  /* ── Utilities ── */
  function esc(v) {
    return String(v || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }
  function fld(id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; }
  function set(id, v) { var el = document.getElementById(id); if (el) el.value = v || ''; }

  function loadList() {
    try { var s = JSON.parse(localStorage.getItem(ADDRESS_KEY) || '[]'); return Array.isArray(s) ? s : []; }
    catch(e) { return []; }
  }
  function saveList(list, sel) {
    localStorage.setItem(ADDRESS_KEY, JSON.stringify(list));
    if (sel !== undefined) localStorage.setItem(SELECTED_KEY, sel || '');
  }
  function normalize(list, selId) {
    if (!list.length) { localStorage.removeItem(SELECTED_KEY); return []; }
    var def = selId || localStorage.getItem(SELECTED_KEY) || '';
    if (!def || !list.some(function(a){ return a.id === def; })) {
      var e = list.find(function(a){ return a.isDefault; });
      def = e ? e.id : list[0].id;
    }
    list.forEach(function(a){ a.isDefault = (a.id === def); });
    localStorage.setItem(SELECTED_KEY, def);
    return list;
  }

  /* ── Address label tabs ── */
  var tabs = document.querySelectorAll('.da-tab');
  tabs.forEach(function(tab) {
    tab.addEventListener('click', function() {
      tabs.forEach(function(t){ t.classList.remove('is-active'); });
      this.classList.add('is-active');
      document.getElementById('accountAddrLabel').value = this.dataset.tabVal;
    });
  });
  function setActiveTab(val) {
    tabs.forEach(function(t){
      var active = t.dataset.tabVal === val;
      t.classList.toggle('is-active', active);
    });
    document.getElementById('accountAddrLabel').value = val || 'Home';
  }

  /* ── Postal lookup ── */
  var postalTimer = null;
  function setHint(type, msg) {
    postalHint.textContent = msg;
    postalHint.className = 'da-hint' + (type ? ' is-' + type : '');
  }
  function lookupPostal(postal) {
    setHint('loading', 'Looking up location…');
    fetch(
      'https://nominatim.openstreetmap.org/search?postalcode=' + encodeURIComponent(postal) + '&format=json&addressdetails=1&limit=1',
      { headers: { 'Accept-Language': 'en' } }
    )
    .then(function(r){ return r.json(); })
    .then(function(data) {
      if (!data || !data.length) { setHint('warn', 'Postal code not found — fill manually'); return; }
      var a = data[0].address || {};
      var city    = a.city || a.town || a.village || a.county || a.state_district || '';
      var state   = a.state || '';
      var country = a.country || '';
      if (city)    set('accountAddrCity', city);
      if (state)   set('accountAddrState', state);
      if (country) set('accountAddrCountry', country);
      setHint('ok', '✓ City, state and country auto-filled');
    })
    .catch(function(){ setHint('warn', 'Lookup failed — fill manually'); });
  }
  if (postalInput) {
    postalInput.addEventListener('input', function() {
      clearTimeout(postalTimer);
      setHint('', '');
      var v = this.value.trim();
      if (v.length < 4) return;
      postalTimer = setTimeout(function(){ lookupPostal(v); }, 600);
    });
  }

  /* ── Toggle panel ── */
  function openPanel(title) {
    panel.hidden = false;
    toggleBtn.setAttribute('aria-expanded', 'true');
    if (formTitle) formTitle.textContent = title || 'New Address';
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
  function closePanel() {
    panel.hidden = true;
    toggleBtn.setAttribute('aria-expanded', 'false');
  }
  toggleBtn && toggleBtn.addEventListener('click', function() {
    if (!panel.hidden) { closePanel(); return; }
    form.reset(); clearEdit();
    openPanel('New Address');
  });

  /* ── Edit mode ── */
  function clearEdit() {
    addrIdInput.value = '';
    submitBtn.textContent = 'Save Address'; submitBtn.querySelector('svg') && (submitBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 12l5 5L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Save Address');
    cancelBtn.hidden = true;
    setHint('', '');
    setActiveTab('Home');
  }
  function enterEdit(a) {
    if (!a) return;
    setActiveTab(a.label || 'Home');
    set('accountAddrCountry',    a.country);
    set('accountAddrFullName',   a.fullName);
    set('accountAddrName',       a.name);
    set('accountAddrPhone',      a.phone);
    set('accountAddrLine1',      a.line1);
    set('accountAddrLine2',      a.line2);
    set('accountAddrLine3',      a.line3);
    set('accountAddrPin',        a.zip);
    set('accountAddrCity',       a.city);
    set('accountAddrState',      a.state);
    set('accountAddrExtra',      a.extra);
    set('accountRecipientName',  a.recipientName);
    set('accountRecipientEmail', a.recipientEmail);
    set('accountRecipientPhone', a.recipientPhone);
    addrIdInput.value = a.id;
    submitBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 12l5 5L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Update Address';
    cancelBtn.hidden = false;
    setHint('', '');
    openPanel('Edit Address');
  }

  /* ── Render card ── */
  function badgeClass(label) {
    var l = (label || '').toLowerCase();
    if (l === 'home')   return 'da-badge--home';
    if (l === 'office') return 'da-badge--office';
    return 'da-badge--others';
  }
  function badgeIcon(label) {
    var l = (label || '').toLowerCase();
    if (l === 'home')   return '<svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 21V12h6v9" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>';
    if (l === 'office') return '<svg width="11" height="11" viewBox="0 0 24 24" fill="none"><rect x="2" y="7" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" stroke="currentColor" stroke-width="2"/></svg>';
    return '<svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>';
  }
  function renderCard(a) {
    var hasRecipient = a.recipientName || a.recipientEmail || a.recipientPhone;

    return '<article class="da-card">'
      + '<div class="da-card-head">'
      +   '<div class="da-badge-row">'
      +     '<span class="da-badge ' + badgeClass(a.label) + '">' + badgeIcon(a.label) + esc(a.label || 'Others') + '</span>'
      +     (a.isDefault ? '<span class="da-badge da-badge--default"><svg width="10" height="10" viewBox="0 0 24 24" fill="none"><path d="M5 12l5 5L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg> Default</span>' : '')
      +   '</div>'
      +   '<div class="da-menu" style="position:relative">'
      +     '<details>'
      +       '<summary class="da-menu-trigger" aria-label="Actions">&#8942;</summary>'
      +       '<div class="da-menu-pop">'
      +         '<button type="button" class="da-menu-item" data-addr-edit="' + esc(a.id) + '"><svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg> Edit</button>'
      +         (!a.isDefault ? '<button type="button" class="da-menu-item" data-addr-default="' + esc(a.id) + '"><svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg> Set Default</button>' : '')
      +         '<button type="button" class="da-menu-item da-menu-item--danger" data-addr-delete="' + esc(a.id) + '"><svg width="12" height="12" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg> Delete</button>'
      +       '</div>'
      +     '</details>'
      +   '</div>'
      + '</div>'
      + '<div class="da-card-body">'

      /* Name + contact row */
      +   '<div class="da-card-fullname">' + esc(a.fullName || a.name) + '</div>'
      +   '<div class="da-card-meta">'
      +     (a.name && a.name !== a.fullName
              ? '<span><svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>' + esc(a.name) + '</span>' : '')
      +     '<span><svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' + esc(a.phone) + '</span>'
      +   '</div>'

      /* Structured address block */
      +   (function(){
            var parts = [];
            if (a.line1)   parts.push(a.line1);
            if (a.line2)   parts.push(a.line2);
            if (a.line3)   parts.push(a.line3);
            if (a.city)    parts.push(a.city);
            var stateZip = [a.state, a.zip ? a.zip : ''].filter(Boolean).join(' - ');
            if (stateZip)  parts.push(stateZip);
            if (a.country) parts.push(a.country);
            return '<p class="da-card-address">' + esc(parts.join(', ')) + '</p>';
          })()

      /* Delivery note */
      +   (a.extra ? '<div class="da-card-extra">' + esc(a.extra) + '</div>' : '')

      /* Recipient footer */
      +   (hasRecipient
            ? '<div class="da-card-recipient">'
              + '<span class="da-recipient-label">Recipient</span>'
              + (a.recipientName  ? '<span class="da-recipient-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg> ' + esc(a.recipientName)  + '</span>' : '')
              + (a.recipientPhone ? '<span class="da-recipient-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg> ' + esc(a.recipientPhone) + '</span>' : '')
              + (a.recipientEmail ? '<span class="da-recipient-item"><svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="22 6 12 13 2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg> ' + esc(a.recipientEmail) + '</span>' : '')
              + '</div>'
            : '')

      + '</div>'
      + '</article>';
  }

  function render() {
    var list = loadList();
    list = normalize(list);
    if (!list.length) {
      list = normalize(DUMMY.map(function(d){ return Object.assign({}, d); }), DUMMY[0].id);
      saveList(list, DUMMY[0].id);
    } else {
      saveList(list);
    }
    if (!list.length) {
      grid.innerHTML = '<div class="da-empty"><svg width="40" height="40" viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="1.5"/></svg><p>No addresses saved yet. Click <strong>Add New Address</strong> to create one.</p></div>';
      return;
    }
    grid.innerHTML = list.map(renderCard).join('');
  }

  /* ── Form submit ── */
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var label   = (document.getElementById('accountAddrLabel').value || 'Home').trim();
    var country = fld('accountAddrCountry');
    var fullName= fld('accountAddrFullName');
    var name    = fld('accountAddrName');
    var phone   = fld('accountAddrPhone');
    var line1   = fld('accountAddrLine1');
    var zip     = fld('accountAddrPin');
    var city    = fld('accountAddrCity');
    if (!country || !fullName || !name || !phone || !line1 || !zip || !city) {
      if (typeof toast === 'function') toast('Please fill all required fields marked with *.', 'warn');
      return;
    }
    var editId = addrIdInput.value.trim();
    var payload = {
      id: editId || ('addr_' + Date.now()),
      label: label, country: country, fullName: fullName,
      name: name, phone: phone,
      line1: line1, line2: fld('accountAddrLine2'), line3: fld('accountAddrLine3'),
      zip: zip, city: city, state: fld('accountAddrState'),
      extra: fld('accountAddrExtra'),
      recipientName: fld('accountRecipientName'),
      recipientEmail: fld('accountRecipientEmail'),
      recipientPhone: fld('accountRecipientPhone')
    };
    var list = normalize(loadList());
    var sel  = localStorage.getItem(SELECTED_KEY) || '';
    if (editId) {
      list = list.map(function(a){ return a.id === editId ? Object.assign({}, a, payload) : a; });
      list = normalize(list, sel || (list[0] && list[0].id));
      saveList(list, sel);
      if (typeof toast === 'function') toast('Address updated successfully.', 'ok');
    } else {
      payload.isDefault = !list.length;
      list.push(payload);
      list = normalize(list, payload.id);
      saveList(list, payload.id);
      if (typeof toast === 'function') toast('Address saved successfully.', 'ok');
    }
    form.reset(); clearEdit(); closePanel(); render();
  });

  /* ── Reset / Cancel ── */
  resetBtn && resetBtn.addEventListener('click', function(){ setTimeout(function(){ clearEdit(); setActiveTab('Home'); }, 0); });
  cancelBtn && cancelBtn.addEventListener('click', function(){ form.reset(); clearEdit(); closePanel(); });

  /* ── List actions ── */
  grid.addEventListener('click', function(e) {
    var editEl    = e.target.closest('[data-addr-edit]');
    var defaultEl = e.target.closest('[data-addr-default]');
    var deleteEl  = e.target.closest('[data-addr-delete]');
    var list = normalize(loadList());

    if (editEl) {
      var id = editEl.dataset.addrEdit;
      enterEdit(list.find(function(a){ return a.id === id; }) || null);
      return;
    }
    if (defaultEl) {
      var id = defaultEl.dataset.addrDefault;
      list = normalize(list, id);
      saveList(list, id);
      if (typeof toast === 'function') toast('Default address updated.', 'ok');
      render(); return;
    }
    if (deleteEl) {
      var id = deleteEl.dataset.addrDelete;
      list = list.filter(function(a){ return a.id !== id; });
      var nxt = list.length ? list[0].id : '';
      list = normalize(list, nxt);
      saveList(list, nxt);
      if (typeof toast === 'function') toast('Address removed.', 'warn');
      render();
    }
  });

  /* ── Init ── */
  clearEdit();
  render();
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
