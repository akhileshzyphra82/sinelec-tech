<?php
require_once '../common/functions.php';
require_once __DIR__ . '/account-helpers.php';
require_once '../controller/website_controller.php';

$user   = sinelec_get_signed_in_user();
$userId = (int)($user['USER_ID'] ?? 0);
if ($userId <= 0) {
    sinelec_set_flash('warn', 'Please sign in to checkout.');
    header('location:index'); exit;
}

$ctrl      = new WebsiteController();
$addresses = $ctrl->getUserAddresses($userId);
$payModes  = $ctrl->getPaymentModes();

/* PayPal SDK config — exposed to JS (client ID is public, secret stays server-side) */
$_ppRawMode   = (string)sinelec_env('PAYPAL_MODE', 'sandbox');
$_ppMode      = strtolower(trim((string)preg_replace('/#.*$/', '', $_ppRawMode)));
$_ppClientId  = (string)sinelec_env('PAYPAL_CLIENT_ID', '');
$_ppCurrency  = strtoupper(trim((string)sinelec_env('CURRENCY', 'EUR')));
/* Detect if any active payment mode is PayPal */
$_hasPaypal   = false;
foreach ($payModes as $_pm) {
    if (strtolower(trim((string)($_pm->PAYMENT_TYPE ?? ''))) === 'paypal') {
        $_hasPaypal = true; break;
    }
}
unset($_pm);

/* Encode addresses as JSON for JS modal */
$jsAddresses = array_map(fn($a) => [
    'id'      => (int)(float)($a->USER_ADDRESS_ID   ?? 0),
    'label'   => (string)($a->LABEL                 ?? 'Other'),
    'name'    => (string)($a->USER_NAME              ?? $a->RECIPIENT_NAME ?? ''),
    'company' => (string)($a->COMPANY_NAME           ?? ''),
    'line1'   => (string)($a->ADDRESS_LINE_ONE       ?? ''),
    'line2'   => (string)($a->ADDRESS_LINE_TWO       ?? ''),
    'city'    => (string)($a->CITY                   ?? ''),
    'state'   => (string)($a->STATE                  ?? ''),
    'zip'     => (string)($a->ZIP                    ?? ''),
    'country' => (string)($a->COUNTRY                ?? ''),
    'phone'   => (string)($a->DELIVERY_PHONE_NO      ?? ''),
], $addresses);

$firstAddrId = !empty($addresses) ? (int)(float)($addresses[0]->USER_ADDRESS_ID ?? 0) : 0;

$currentPage = 'checkout';
$pageTitle   = 'Checkout — Sinelec Tech';
require_once 'header.php';
?>
<main>
<div class="wrap page-wrap">
  <div class="page-hero checkout-hero" style="padding-bottom:20px;">
    <div class="page-eyebrow">Secure Checkout</div>
    <h1 class="page-title" style="font-size:clamp(20px,3.5vw,30px);margin-bottom:0;">Complete Your Order</h1>
  </div>

  <div class="co-layout" id="coPage">

    <!-- ══════════ LEFT — Cart Items ══════════ -->
    <div class="co-main">
      <section class="co-card">
        <div class="co-card-head">
          <div class="co-card-title">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
            Order Items
          </div>
          <a href="products" class="co-back-link">← Continue Shopping</a>
        </div>
        <div class="co-card-body" id="coItemsList">
          <div class="co-empty" id="coEmptyCart" hidden>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" opacity=".3"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
            <p>Your cart is empty.</p>
            <a href="products" class="btn btn-blue" style="font-size:13px;padding:8px 20px;">Browse Products</a>
          </div>
        </div>
      </section>
    </div>

    <!-- ══════════ RIGHT — Address + Payment + Summary ══════════ -->
    <aside class="co-aside">
      <div class="co-sticky-wrap">

        <!-- Shipping terms link -->
        <a href="shipping-payment-term" class="co-terms-link" target="_blank" rel="noopener">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Shipping &amp; Payment Terms for Your Location
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 6 15 12 9 18"/></svg>
        </a>

        <!-- ── Delivery Address ── -->
        <section class="co-card">
          <div class="co-card-head">
            <div class="co-card-title">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
              Delivery Address <span class="co-required">*</span>
            </div>
            <button type="button" class="co-change-btn" onclick="coOpenAddrModal('delivery')">
              <?= empty($addresses) ? '+ Add Address' : 'Change' ?>
            </button>
          </div>
          <div class="co-card-body" style="padding:12px 16px;">
            <?php if (empty($addresses)): ?>
            <p class="co-addr-empty">No saved addresses. Click "Add Address" to add one.</p>
            <?php else:
              $a      = $addresses[0];
              $label  = htmlspecialchars((string)($a->LABEL    ?? 'Other'));
              $name   = htmlspecialchars((string)($a->USER_NAME ?? $a->RECIPIENT_NAME ?? ''));
              $line1  = htmlspecialchars((string)($a->ADDRESS_LINE_ONE ?? ''));
              $line2  = htmlspecialchars((string)($a->ADDRESS_LINE_TWO ?? ''));
              $city   = htmlspecialchars((string)($a->CITY    ?? ''));
              $state  = htmlspecialchars((string)($a->STATE   ?? ''));
              $zip    = htmlspecialchars((string)($a->ZIP     ?? ''));
              $cty    = htmlspecialchars((string)($a->COUNTRY ?? ''));
              $phone  = htmlspecialchars((string)($a->DELIVERY_PHONE_NO ?? ''));
              $addrFull = implode(', ', array_filter([$line1, $line2, $city, $state, $zip, $cty]));
            ?>
            <div id="coDelivSelected" class="co-addr-display">
              <div class="co-addr-display-top">
                <span class="co-addr-label co-addr-label--<?= strtolower($label) ?>"><?= $label ?></span>
                <strong class="co-addr-display-name" id="coDelivName"><?= $name ?></strong>
              </div>
              <div class="co-addr-display-line" id="coDelivLine"><?= $addrFull ?></div>
              <?php if ($phone): ?><div class="co-addr-display-phone" id="coDelivPhone"><?= $phone ?></div><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="co-shipping-status" id="coShippingStatus">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
              <span id="coShippingStatusText"><?= empty($addresses) ? 'Add a delivery address to calculate shipping' : 'Calculating shipping…' ?></span>
            </div>
          </div>
        </section>

        <!-- ── Billing Address ── -->
        <section class="co-card">
          <div class="co-card-head">
            <div class="co-card-title">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
              Billing Address
            </div>
            <div class="co-billing-head-right">
              <label class="co-same-chk-label" title="Use delivery address as billing">
                <input type="checkbox" id="billingSameChk" checked onchange="coToggleBilling()">
                <span>Same as delivery</span>
              </label>
              <button type="button" class="co-change-btn" id="coBillingChangeBtn" style="display:none;" onclick="coOpenAddrModal('billing')">Change</button>
            </div>
          </div>
          <div id="coBillingDisplay" hidden>
            <div class="co-card-body" style="padding:12px 16px;">
              <div class="co-addr-display" id="coBillSelectedDisplay">
                <?php if (!empty($addresses)):
                  $a      = $addresses[0];
                  $label  = htmlspecialchars((string)($a->LABEL    ?? 'Other'));
                  $name   = htmlspecialchars((string)($a->USER_NAME ?? $a->RECIPIENT_NAME ?? ''));
                  $line1  = htmlspecialchars((string)($a->ADDRESS_LINE_ONE ?? ''));
                  $line2  = htmlspecialchars((string)($a->ADDRESS_LINE_TWO ?? ''));
                  $city   = htmlspecialchars((string)($a->CITY    ?? ''));
                  $state  = htmlspecialchars((string)($a->STATE   ?? ''));
                  $zip    = htmlspecialchars((string)($a->ZIP     ?? ''));
                  $cty    = htmlspecialchars((string)($a->COUNTRY ?? ''));
                  $phone  = htmlspecialchars((string)($a->DELIVERY_PHONE_NO ?? ''));
                  $addrFull = implode(', ', array_filter([$line1, $line2, $city, $state, $zip, $cty]));
                ?>
                <div class="co-addr-display-top">
                  <span class="co-addr-label co-addr-label--<?= strtolower($label) ?>"><?= $label ?></span>
                  <strong class="co-addr-display-name" id="coBillName"><?= $name ?></strong>
                </div>
                <div class="co-addr-display-line" id="coBillLine"><?= $addrFull ?></div>
                <?php if ($phone): ?><div class="co-addr-display-phone" id="coBillPhone"><?= $phone ?></div><?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </section>

        <!-- ── Payment Method ── -->
        <section class="co-card">
          <div class="co-card-head">
            <div class="co-card-title">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
              Payment Method
            </div>
          </div>
          <div class="co-card-body">
            <?php if (empty($payModes)): ?>
            <p style="font-size:13px;color:#6b7280;">No payment methods available.</p>
            <?php else: ?>
            <div class="co-pm-tabs">
              <?php foreach ($payModes as $i => $pm):
                $pmId   = (int)($pm->PAYMENT_MODE_ID ?? 0);
                $pmName = htmlspecialchars((string)($pm->NAME         ?? ''));
                $pmType = (string)($pm->PAYMENT_TYPE ?? '');
              ?>
              <button type="button"
                      class="co-pm-tab<?= $i === 0 ? ' active' : '' ?>"
                      data-pm-id="<?= $pmId ?>" data-pm-type="<?= htmlspecialchars($pmType) ?>"
                      onclick="coSelectPayment(this)">
                <?php if ($pmType === 'Bank Transfer'): ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                <?php elseif ($pmType === 'Invoice'): ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <?php else: ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                <?php endif; ?>
                <?= $pmName ?>
              </button>
              <?php endforeach; ?>
            </div>

            <?php foreach ($payModes as $i => $pm):
              $pmId   = (int)($pm->PAYMENT_MODE_ID ?? 0);
              $pmType = (string)($pm->PAYMENT_TYPE ?? '');
              $pmDesc = trim(strip_tags((string)($pm->DESCRIPTION ?? '')));
            ?>
            <div class="co-pm-panel<?= $i === 0 ? ' active' : '' ?>" data-pm-panel="<?= $pmId ?>">
              <?php if ($pmType === 'Bank Transfer'): ?>
              <div class="co-pm-info co-pm-info--bank">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                  <strong>Bank Transfer / SEPA</strong>
                  <p>Our bank details will be shown after placing your order and emailed to you. Please transfer the exact amount and use your order number as reference.</p>
                  <?php if ($pmDesc): ?><p><?= htmlspecialchars($pmDesc) ?></p><?php endif; ?>
                </div>
              </div>
              <?php elseif ($pmType === 'Invoice'): ?>
              <div class="co-pm-info co-pm-info--invoice">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                  <strong>Invoice Payment</strong>
                  <p>An invoice will be issued and sent to you. Payment due as per your corporate terms.</p>
                  <?php if ($pmDesc): ?><p><?= htmlspecialchars($pmDesc) ?></p><?php endif; ?>
                </div>
              </div>
              <?php elseif ($pmType === 'Paypal'): ?>
              <div class="co-pm-info co-pm-info--paypal">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px;color:#003087;"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                <div>
                  <strong>Pay with PayPal</strong>
                  <p>Your order total is shown below. Click the PayPal button to complete payment securely.</p>
                  <?php if ($pmDesc): ?><p><?= htmlspecialchars($pmDesc) ?></p><?php endif; ?>
                </div>
              </div>
              <?php else: ?>
              <div class="co-pm-info">
                <?php if ($pmDesc): ?><p><?= htmlspecialchars($pmDesc) ?></p><?php endif; ?>
                <p style="color:#94a3b8;font-size:12px;">Payment gateway integration coming soon.</p>
              </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <input type="hidden" id="coSelectedPmId" value="<?= !empty($payModes) ? (int)($payModes[0]->PAYMENT_MODE_ID ?? 0) : 0 ?>">
            <?php endif; ?>
          </div>
        </section>

        <!-- ── Order Summary ── -->
        <section class="co-card">
          <div class="co-card-head">
            <div class="co-card-title">Order Summary</div>
          </div>
          <div class="co-card-body">

            <!-- VAT -->
            <div class="co-vat-section">
              <div class="co-vat-label">EU VAT Number <span class="co-vat-hint">(enter to exempt VAT)</span></div>
              <div class="co-vat-row">
                <input type="text" id="coVatInput" class="co-vat-input"
                       placeholder="e.g. DE123456789" maxlength="20" oninput="coVatReset()">
                <button type="button" class="co-vat-btn" onclick="coApplyVat()">Apply</button>
              </div>
              <div class="co-vat-msg" id="coVatMsg"></div>
            </div>

            <!-- Totals -->
            <div class="co-totals">
              <div class="co-total-row"><span>Subtotal</span><strong id="coSubtotal">€0.00</strong></div>
              <div class="co-total-row"><span>Shipping</span><strong id="coShipping"><em class="co-dim">Select address</em></strong></div>
              <div class="co-total-row"><span id="coVatLabel">VAT (19%)</span><strong id="coVatAmt">—</strong></div>
              <div class="co-total-row co-total-row--grand"><span>Order Total</span><strong id="coGrandTotal">—</strong></div>
            </div>

            <!-- Validation warning -->
            <div class="co-block-reason" id="coBlockReason" hidden></div>

            <!-- Place Order (hidden when PayPal is selected) -->
            <button type="button" class="co-place-btn" id="coPlaceBtn" onclick="coPlaceOrder()" disabled>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
              Place Order
            </button>

            <!-- PayPal button (shown when PayPal is selected) -->
            <div id="paypal-btn-container" style="display:none;margin-top:2px;min-height:45px;"></div>
            <div id="paypal-btn-msg" style="display:none;margin-top:6px;font-size:12px;font-weight:600;border-radius:8px;padding:8px 12px;"></div>

            <p class="co-secure-note">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Secure checkout · Free returns · 100% genuine
            </p>
          </div>
        </section>

      </div>
    </aside>
  </div>

  <!-- ════ Address Change Modal ════ -->
  <div class="co-modal-overlay" id="coAddrModalOverlay" onclick="coCloseAddrModal(event)">
    <div class="co-modal" id="coAddrModal">
      <div class="co-modal-head">
        <div class="co-modal-title" id="coAddrModalTitle">Select Delivery Address</div>
        <button type="button" class="co-modal-close" onclick="coCloseAddrModal(null)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div class="co-modal-body">
        <div class="co-addr-list" id="coModalAddrList"></div>
        <a href="delivery-address" class="co-add-new-link">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add New Address
        </a>
      </div>
      <div class="co-modal-foot">
        <button type="button" class="co-modal-cancel" onclick="coCloseAddrModal(null)">Cancel</button>
        <button type="button" class="co-modal-confirm" id="coModalConfirmBtn" onclick="coConfirmAddr()">Confirm Selection</button>
      </div>
    </div>
  </div>

</div><!-- /wrap -->
</main>

<style>
/* ── Layout ──────────────────────────────────────────────── */
.co-layout{display:grid;grid-template-columns:1fr 400px;gap:20px;align-items:start;margin-bottom:48px;}
.co-main{display:flex;flex-direction:column;gap:14px;}
.co-aside{position:sticky;top:76px;}
.co-sticky-wrap{display:flex;flex-direction:column;gap:12px;}
/* Card */
.co-card{background:#fff;border-radius:16px;border:1px solid #e2eaf6;overflow:hidden;}
.co-card-head{display:flex;align-items:center;justify-content:space-between;padding:13px 18px;border-bottom:1px solid #edf2fb;gap:8px;}
.co-card-title{display:flex;align-items:center;gap:7px;font-size:13px;font-weight:700;color:#1a3352;}
.co-card-body{padding:14px 18px;}
/* Back link */
.co-back-link{font-size:12px;color:#1363bf;font-weight:600;text-decoration:none;white-space:nowrap;}
.co-back-link:hover{text-decoration:underline;}
/* Terms link */
.co-terms-link{display:flex;align-items:center;gap:7px;padding:9px 14px;background:linear-gradient(90deg,#fff8e6,#fffcf0);border:1px solid #f3c960;border-radius:10px;font-size:12px;font-weight:700;color:#92400e;text-decoration:none;transition:background .15s;}
.co-terms-link:hover{background:linear-gradient(90deg,#fff3cc,#fff9e0);text-decoration:none;}
.co-terms-link svg:last-child{margin-left:auto;color:#c08800;}
/* Address display */
.co-addr-display{padding:10px 12px;background:#f7fbff;border:1px solid #d8eaf8;border-radius:10px;margin-bottom:8px;}
.co-addr-display-top{display:flex;align-items:center;gap:7px;margin-bottom:4px;}
.co-addr-label{font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;text-transform:uppercase;letter-spacing:.3px;}
.co-addr-label--home{background:#dbeafe;color:#1d4ed8;}
.co-addr-label--office{background:#d1fae5;color:#065f46;}
.co-addr-label--other{background:#f3f4f6;color:#374151;}
.co-addr-display-name{font-size:13px;color:#1a3352;}
.co-addr-display-line{font-size:12px;color:#5a748e;line-height:1.5;}
.co-addr-display-phone{font-size:11px;color:#7a93b0;margin-top:2px;}
.co-addr-empty{font-size:12px;color:#8399b5;margin:0;}
/* Shipping status */
.co-shipping-status{display:flex;align-items:center;gap:6px;font-size:12px;color:#5a748e;margin-top:4px;}
.co-shipping-status.loaded{color:#16a34a;}
.co-shipping-status.err{color:#e0415a;}
/* Change button */
.co-change-btn{font-size:11px;font-weight:700;color:#1363bf;background:#eef5ff;border:1px solid #c8dff8;border-radius:7px;padding:4px 10px;cursor:pointer;white-space:nowrap;}
.co-change-btn:hover{background:#dceeff;}
/* Required star */
.co-required{color:#e0415a;margin-left:2px;}
/* Billing same checkbox */
.co-billing-head-right{display:flex;align-items:center;gap:8px;}
.co-same-chk-label{display:flex;align-items:center;gap:5px;font-size:11px;color:#5a748e;cursor:pointer;white-space:nowrap;}
.co-same-chk-label input{accent-color:#1363bf;width:13px;height:13px;}
/* Payment tabs */
.co-pm-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;}
.co-pm-tab{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;font-size:12px;font-weight:700;border:2px solid #d0ddf0;border-radius:9px;background:#f7fafe;color:#4a6a8a;cursor:pointer;transition:all .15s;}
.co-pm-tab:hover,.co-pm-tab.active{border-color:#1363bf;background:#eef5ff;color:#1363bf;}
.co-pm-panel{display:none;}
.co-pm-panel.active{display:block;}
.co-pm-info{display:flex;gap:9px;padding:9px 11px;border-radius:9px;font-size:12px;line-height:1.55;color:#374151;}
.co-pm-info strong{display:block;margin-bottom:3px;font-size:12px;font-weight:700;}
.co-pm-info p{margin:0 0 3px;}
.co-pm-info--bank{background:#fffbeb;color:#92400e;}
.co-pm-info--invoice{background:#eff6ff;color:#1e40af;}
/* VAT */
.co-vat-section{padding-bottom:12px;margin-bottom:12px;border-bottom:1px solid #edf2fb;}
.co-vat-label{font-size:11px;font-weight:700;color:#4a6a8a;margin-bottom:5px;}
.co-vat-hint{font-weight:400;color:#9fb3cc;}
.co-vat-row{display:grid;grid-template-columns:1fr auto;gap:6px;}
.co-vat-input{height:34px;border:1px solid #cdd8ec;border-radius:8px;padding:0 9px;font-size:12px;color:#1a3352;background:#fff;outline:none;width:100%;}
.co-vat-input:focus{border-color:#1363bf;}
.co-vat-btn{min-width:62px;height:34px;border:1px solid #c8dff8;background:#eef5ff;color:#1363bf;font-size:11px;font-weight:700;border-radius:8px;cursor:pointer;}
.co-vat-btn:hover{background:#dceeff;}
.co-vat-msg{margin-top:5px;font-size:11px;font-weight:600;min-height:14px;}
.co-vat-msg.ok{color:#16a34a;}
.co-vat-msg.err{color:#e0415a;}
/* Totals */
.co-totals{display:flex;flex-direction:column;gap:6px;padding-bottom:12px;margin-bottom:12px;border-bottom:1px solid #edf2fb;}
.co-total-row{display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#4a6a8a;}
.co-total-row strong{color:#1a3352;font-size:12px;}
.co-total-row--grand{font-size:15px;font-weight:700;color:#1a3352;padding-top:8px;border-top:2px solid #e2eaf6;margin-top:4px;}
.co-total-row--grand strong{color:#1363bf;font-size:16px;}
.co-dim{font-style:normal;color:#9fb3cc;font-size:11px;font-weight:500;}
/* Validation block reason */
.co-block-reason{padding:8px 12px;background:#fff1f2;border:1px solid #fecdd3;border-radius:9px;font-size:12px;font-weight:600;color:#be123c;margin-bottom:10px;display:flex;align-items:center;gap:6px;}
/* Place Order btn */
.co-place-btn{width:100%;height:48px;background:linear-gradient(135deg,#1363bf,#1b7dd4);border:0;border-radius:12px;color:#fff;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:opacity .15s;}
.co-place-btn:hover:not(:disabled){opacity:.9;}
.co-place-btn:disabled{background:#94a3b8;cursor:not-allowed;opacity:1;}
.co-place-btn.loading{opacity:.7;pointer-events:none;}
.co-secure-note{text-align:center;font-size:10px;color:#aab8cc;margin:7px 0 0;display:flex;align-items:center;justify-content:center;gap:4px;}
/* Cart items */
.co-item{display:grid;grid-template-columns:86px 1fr auto;gap:12px;align-items:start;padding:12px 0;border-bottom:1px solid #f0f5fc;}
.co-item:last-child{border-bottom:0;padding-bottom:0;}
.co-item:first-child{padding-top:0;}
.co-item-img-wrap{position:relative;width:86px;height:86px;border-radius:10px;overflow:hidden;border:1px solid #e2eaf6;background:#f5f8fc;flex-shrink:0;}
.co-item-img-wrap a{display:block;width:100%;height:100%;}
.co-item-img{width:100%;height:100%;object-fit:cover;display:block;}
.co-item-img-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#9fb3cc;font-size:10px;font-weight:600;}
.co-item-name{font-size:13px;font-weight:700;color:#1a3352;margin-bottom:3px;line-height:1.3;}
.co-item-name a{color:inherit;text-decoration:none;}
.co-item-name a:hover{color:#1363bf;text-decoration:underline;}
.co-item-sku{font-size:11px;color:#7a93b0;margin-bottom:6px;}
.co-item-qty{display:inline-flex;align-items:center;border:1px solid #d0ddf0;border-radius:7px;overflow:hidden;}
.co-item-qty-btn{width:26px;height:24px;border:0;background:0;color:#3366aa;font-size:13px;font-weight:700;cursor:pointer;line-height:1;}
.co-item-qty-btn:hover{background:#f3f8ff;}
.co-item-qty-val{min-width:28px;height:24px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#1a3352;border-left:1px solid #d0ddf0;border-right:1px solid #d0ddf0;}
.co-item-right{text-align:right;display:flex;flex-direction:column;align-items:flex-end;gap:4px;}
.co-item-price{font-size:14px;font-weight:700;color:#1a3352;}
.co-item-remove{font-size:11px;color:#e05569;background:0;border:0;cursor:pointer;padding:0;}
.co-empty{text-align:center;padding:32px;color:#7a93b0;display:flex;flex-direction:column;align-items:center;gap:12px;}
/* ── Address Modal ── */
.co-modal-overlay{position:fixed;inset:0;background:rgba(10,25,47,.55);z-index:2000;display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s;}
.co-modal-overlay.open{opacity:1;pointer-events:all;}
.co-modal{background:#fff;border-radius:20px;width:100%;max-width:520px;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.18);transform:translateY(20px);transition:transform .2s;}
.co-modal-overlay.open .co-modal{transform:translateY(0);}
.co-modal-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #edf2fb;flex-shrink:0;}
.co-modal-title{font-size:15px;font-weight:700;color:#1a3352;}
.co-modal-close{background:0;border:0;cursor:pointer;color:#8399b5;padding:4px;border-radius:6px;}
.co-modal-close:hover{background:#f3f7fd;color:#1a3352;}
.co-modal-body{overflow-y:auto;padding:16px 20px;flex:1;}
.co-modal-foot{padding:14px 20px;border-top:1px solid #edf2fb;display:flex;gap:10px;justify-content:flex-end;flex-shrink:0;}
.co-modal-cancel{padding:9px 20px;border:1px solid #d0ddf0;border-radius:10px;background:#fff;color:#5a748e;font-size:13px;font-weight:700;cursor:pointer;}
.co-modal-confirm{padding:9px 22px;border:0;border-radius:10px;background:#1363bf;color:#fff;font-size:13px;font-weight:700;cursor:pointer;}
.co-modal-confirm:hover{background:#0e56aa;}
/* Modal address list */
.co-modal-addr-item{display:flex;align-items:flex-start;gap:10px;padding:11px 13px;border:2px solid #e2eaf6;border-radius:11px;cursor:pointer;margin-bottom:8px;transition:border-color .15s,background .15s;}
.co-modal-addr-item.selected,.co-modal-addr-item:has(input:checked){border-color:#1363bf;background:#f4f9ff;}
.co-modal-addr-item input[type=radio]{margin-top:3px;accent-color:#1363bf;flex-shrink:0;}
.co-modal-addr-content{flex:1;min-width:0;}
.co-modal-addr-top{display:flex;align-items:center;gap:6px;margin-bottom:3px;flex-wrap:wrap;}
.co-modal-addr-name{font-size:13px;font-weight:700;color:#1a3352;}
.co-modal-addr-line{font-size:12px;color:#5a748e;line-height:1.4;}
.co-modal-addr-phone{font-size:11px;color:#7a93b0;margin-top:1px;}
.co-add-new-link{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#f4f9ff;border:1px dashed #c8dff8;border-radius:9px;font-size:12px;font-weight:700;color:#1363bf;text-decoration:none;margin-top:4px;width:100%;justify-content:center;}
.co-add-new-link:hover{background:#dceeff;text-decoration:none;}
/* Responsive */
@media(max-width:960px){.co-layout{grid-template-columns:1fr;}.co-aside{position:static;}}
@media(max-width:520px){
  .co-card-head,.co-card-body{padding:11px 13px;}
  .co-item{grid-template-columns:70px 1fr auto;}
  .co-item-img-wrap{width:70px;height:70px;}
}
</style>

<script>
const _CO_ADDRS  = <?= json_encode(array_values($jsAddresses), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const VAT_PCT    = 19;
let _coItems     = [];
let _coShipping  = null;  /* null = not loaded; number = amount */
let _coVatExempt = false;
let _coDelivId   = <?= $firstAddrId ?>;
let _coBillId    = <?= $firstAddrId ?>;
let _coBillSame  = true;
let _coPmId      = <?= !empty($payModes) ? (int)($payModes[0]->PAYMENT_MODE_ID ?? 0) : 0 ?>;
let _coModalFor  = 'delivery'; /* 'delivery' | 'billing' */
let _coModalTmp  = 0;          /* temp selection while modal is open */

/* ── Boot from localStorage ──────────────────────────────── */
(function boot() {
  try {
    const raw = localStorage.getItem('sinelec_cart');
    if (raw) {
      const arr = JSON.parse(raw);
      if (Array.isArray(arr)) {
        _coItems = arr.map(it => ({
          id:    parseInt(it.id || it.product_id || 0),
          name:  String(it.name || it.product_name || ''),
          sku:   String(it.sku  || it.product_code || ''),
          image: String(it.image || ''),
          price: parseFloat(it.price) || 0,
          qty:   Math.max(1, parseInt(it.qty) || 1),
          catId: it.category || it.catId || 0,
        }));
      }
    }
  } catch(e) {}
  coRenderItems();
  coRecalc();
  /* Fetch shipping for preselected address regardless of cart state */
  if (_coDelivId) coFetchShipping(_coDelivId);
})();

/* ── Render cart items ───────────────────────────────────── */
function coRenderItems() {
  const wrap  = document.getElementById('coItemsList');
  const empty = document.getElementById('coEmptyCart');
  if (!wrap) return;
  if (!_coItems.length) {
    if (empty) empty.hidden = false;
    wrap.innerHTML = '';
    wrap.appendChild(empty);
    return;
  }
  if (empty) empty.hidden = true;
  const html = _coItems.map((it, idx) => {
    const prodUrl = `product?id=${it.id}`;
    const imgHtml = it.image
      ? `<img class="co-item-img" src="${_e(it.image)}" alt="${_e(it.name)}" onerror="this.style.display='none'">`
      : `<div class="co-item-img-ph">${_e(it.sku||'IMG')}</div>`;
    return `<div class="co-item" data-idx="${idx}">
      <div class="co-item-img-wrap"><a href="${prodUrl}" title="View product">${imgHtml}</a></div>
      <div>
        <div class="co-item-name"><a href="${prodUrl}">${_e(it.name)}</a></div>
        ${it.sku ? `<div class="co-item-sku">SKU: ${_e(it.sku)}</div>` : ''}
        <div class="co-item-qty">
          <button class="co-item-qty-btn" onclick="coQty(${idx},-1)" type="button">−</button>
          <div class="co-item-qty-val" id="coQty_${idx}">${it.qty}</div>
          <button class="co-item-qty-btn" onclick="coQty(${idx},1)"  type="button">+</button>
        </div>
      </div>
      <div class="co-item-right">
        <div class="co-item-price" id="coPrice_${idx}">€${(it.price*it.qty).toFixed(2)}</div>
        <button class="co-item-remove" onclick="coRemoveItem(${idx})" type="button">Remove</button>
      </div>
    </div>`;
  }).join('');
  wrap.innerHTML = html;
}

function coQty(idx, d) {
  _coItems[idx].qty = Math.max(1, (_coItems[idx].qty||1) + d);
  const qEl = document.getElementById('coQty_'  + idx);
  const pEl = document.getElementById('coPrice_' + idx);
  if (qEl) qEl.textContent = _coItems[idx].qty;
  if (pEl) pEl.textContent = '€' + (_coItems[idx].price * _coItems[idx].qty).toFixed(2);
  _coSyncCart(); coRecalc();
}

function coRemoveItem(idx) {
  _coItems.splice(idx, 1);
  _coSyncCart(); coRenderItems(); coRecalc();
}

function _coSyncCart() {
  localStorage.setItem('sinelec_cart', JSON.stringify(
    _coItems.map(it => ({ id:it.id, name:it.name, sku:it.sku, image:it.image, price:it.price, qty:it.qty, category:it.catId }))
  ));
  const cnt = _coItems.reduce((s,it) => s + it.qty, 0);
  document.querySelectorAll('#cartCount,.cart-count-badge').forEach(el => {
    el.textContent = cnt; el.hidden = cnt === 0;
  });
}

/* ── Recalculate & update UI ─────────────────────────────── */
function coRecalc() {
  const sub  = _coItems.reduce((s, it) => s + it.price * it.qty, 0);
  _setText('coSubtotal', '€' + sub.toFixed(2));

  if (_coShipping !== null) {
    _setText('coShipping', '€' + _coShipping.toFixed(2));
    const vatBase = sub + _coShipping;
    const vatAmt  = _coVatExempt ? 0 : +(vatBase * VAT_PCT / 100).toFixed(2);
    _setText('coVatAmt',     _coVatExempt ? '€0.00 (exempt)' : '€' + vatAmt.toFixed(2));
    _setText('coVatLabel',   _coVatExempt ? 'VAT (exempt)' : 'VAT (19%)');
    _setText('coGrandTotal', '€' + (vatBase + vatAmt).toFixed(2));
  } else {
    const el = document.getElementById('coShipping');
    if (el) el.innerHTML = '<em class="co-dim">Select address</em>';
    _setText('coVatAmt',     '—');
    _setText('coGrandTotal', '—');
  }
  coCheckReady();
}

function coCheckReady() {
  const reasons = [];
  if (!_coItems.length)       reasons.push('Your cart is empty.');
  if (!_coDelivId)             reasons.push('Select a delivery address.');
  if (_coShipping === null)    reasons.push('Shipping cost is loading…');
  if (!_coPmId)                reasons.push('Select a payment method.');

  const btn    = document.getElementById('coPlaceBtn');
  const block  = document.getElementById('coBlockReason');
  const ready  = reasons.length === 0;
  if (btn)   btn.disabled = !ready;
  if (block) {
    block.hidden = ready;
    if (!ready) block.innerHTML =
      `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> `
      + _e(reasons[0]);
  }
}

function _setText(id, v) {
  const el = document.getElementById(id);
  if (el) el.textContent = v;
}

/* ── Shipping fetch ──────────────────────────────────────── */
function _coFetchShippingBase(addrId) {
  if (!addrId) return;
  const stEl   = document.getElementById('coShippingStatus');
  const stText = document.getElementById('coShippingStatusText');
  if (stEl)   stEl.className   = 'co-shipping-status';
  if (stText) stText.textContent = 'Calculating shipping…';

  const shipEl = document.getElementById('coShipping');
  if (shipEl) shipEl.innerHTML = '<em class="co-dim">Loading…</em>';
  _coShipping = null;
  coCheckReady();

  fetch(`ajax/order?action=get_shipping&address_id=${addrId}`)
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        _coShipping = parseFloat(d.shipping_amt) || 0;
        if (stEl)   stEl.className   = 'co-shipping-status loaded';
        if (stText) stText.textContent = `Shipping to ${d.country || 'your country'}: €${_coShipping.toFixed(2)}`;
        coRecalc();
      } else {
        _coShipping = null;
        if (stEl)   stEl.className   = 'co-shipping-status err';
        if (stText) stText.textContent = d.msg || 'Could not calculate shipping.';
        coRecalc();
      }
    })
    .catch(() => {
      _coShipping = null;
      if (stEl)   stEl.className   = 'co-shipping-status err';
      if (stText) stText.textContent = 'Network error loading shipping.';
      coRecalc();
    });
}

/* Public wrapper — may be overridden by PayPal block below if SDK is present */
function coFetchShipping(addrId) { _coFetchShippingBase(addrId); }

/* ── Address modal ───────────────────────────────────────── */
function coOpenAddrModal(type) {
  _coModalFor = type;
  _coModalTmp = type === 'delivery' ? _coDelivId : _coBillId;
  document.getElementById('coAddrModalTitle').textContent =
    type === 'delivery' ? 'Select Delivery Address' : 'Select Billing Address';

  /* Render address list in modal */
  const list = document.getElementById('coModalAddrList');
  list.innerHTML = _CO_ADDRS.map(a => {
    const addrLine = [a.line1, a.line2, a.city, a.state, a.zip, a.country].filter(Boolean).join(', ');
    const sel = a.id === _coModalTmp;
    return `<label class="co-modal-addr-item${sel ? ' selected' : ''}" onclick="coModalSelect(${a.id},this)">
      <input type="radio" name="coModalAddr" value="${a.id}" ${sel ? 'checked' : ''}>
      <div class="co-modal-addr-content">
        <div class="co-modal-addr-top">
          <span class="co-addr-label co-addr-label--${a.label.toLowerCase()}">${_e(a.label)}</span>
          <span class="co-modal-addr-name">${_e(a.name)}${a.company ? ' · ' + _e(a.company) : ''}</span>
        </div>
        <div class="co-modal-addr-line">${_e(addrLine)}</div>
        ${a.phone ? `<div class="co-modal-addr-phone">${_e(a.phone)}</div>` : ''}
      </div>
    </label>`;
  }).join('');

  document.getElementById('coAddrModalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function coModalSelect(id, el) {
  _coModalTmp = id;
  document.querySelectorAll('.co-modal-addr-item').forEach(x => x.classList.remove('selected'));
  if (el) el.classList.add('selected');
}

function coCloseAddrModal(e) {
  if (e && e.target !== document.getElementById('coAddrModalOverlay')) return;
  document.getElementById('coAddrModalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

function coConfirmAddr() {
  if (!_coModalTmp) { alert('Please select an address.'); return; }
  const addr = _CO_ADDRS.find(a => a.id === _coModalTmp);
  if (!addr) return;
  const addrLine = [addr.line1, addr.line2, addr.city, addr.state, addr.zip, addr.country].filter(Boolean).join(', ');
  const labelHtml = `<span class="co-addr-label co-addr-label--${addr.label.toLowerCase()}">${_e(addr.label)}</span>`;

  if (_coModalFor === 'delivery') {
    _coDelivId = addr.id;
    /* Update delivery display */
    const disp = document.getElementById('coDelivSelected');
    if (disp) disp.innerHTML = `
      <div class="co-addr-display-top">${labelHtml}<strong class="co-addr-display-name">${_e(addr.name)}${addr.company ? ' · '+_e(addr.company) : ''}</strong></div>
      <div class="co-addr-display-line">${_e(addrLine)}</div>
      ${addr.phone ? `<div class="co-addr-display-phone">${_e(addr.phone)}</div>` : ''}`;
    /* If billing is same, also update billing */
    if (_coBillSame) {
      _coBillId = addr.id;
      _coUpdateBillDisplay(addr, addrLine, labelHtml);
    }
    coFetchShipping(_coDelivId);
  } else {
    _coBillId = addr.id;
    _coUpdateBillDisplay(addr, addrLine, labelHtml);
  }
  coCloseAddrModal(null);
  coCheckReady();
}

function _coUpdateBillDisplay(addr, addrLine, labelHtml) {
  const disp = document.getElementById('coBillSelectedDisplay');
  if (disp) disp.innerHTML = `
    <div class="co-addr-display-top">${labelHtml}<strong class="co-addr-display-name">${_e(addr.name)}${addr.company ? ' · '+_e(addr.company) : ''}</strong></div>
    <div class="co-addr-display-line">${_e(addrLine)}</div>
    ${addr.phone ? `<div class="co-addr-display-phone">${_e(addr.phone)}</div>` : ''}`;
}

/* ── Billing toggle ──────────────────────────────────────── */
function coToggleBilling() {
  const chk  = document.getElementById('billingSameChk');
  const wrap = document.getElementById('coBillingDisplay');
  const btn  = document.getElementById('coBillingChangeBtn');
  _coBillSame = !!chk?.checked;
  if (wrap) wrap.hidden = _coBillSame;
  if (btn)  btn.style.display = _coBillSame ? 'none' : '';
  if (_coBillSame) _coBillId = _coDelivId;
}

/* ── Payment method ──────────────────────────────────────── */
function coSelectPayment(btn) {
  document.querySelectorAll('.co-pm-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.co-pm-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  _coPmId = parseInt(btn.dataset.pmId);
  const panel = document.querySelector(`.co-pm-panel[data-pm-panel="${_coPmId}"]`);
  if (panel) panel.classList.add('active');
  const hid = document.getElementById('coSelectedPmId');
  if (hid) hid.value = _coPmId;
  coCheckReady();
  /* PayPal toggle */
  _coIsPaypal = (btn.dataset.pmType || '').toLowerCase() === 'paypal';
  _coTogglePaypalBtn();
}

/* ── VAT ─────────────────────────────────────────────────── */
function coVatReset() {
  _coVatExempt = false;
  const msg = document.getElementById('coVatMsg');
  if (msg) { msg.textContent = ''; msg.className = 'co-vat-msg'; }
  coRecalc();
}

function coApplyVat() {
  const inp = document.getElementById('coVatInput');
  const msg = document.getElementById('coVatMsg');
  if (!inp || !msg) return;
  const val = inp.value.trim().toUpperCase().replace(/\s+/g, '');
  if (!val) { msg.textContent = 'Please enter a VAT number.'; msg.className = 'co-vat-msg err'; return; }
  if (!/^[A-Z]{2}[0-9A-Z]{2,13}$/.test(val)) {
    msg.textContent = 'Invalid format. Use country code + number (e.g. DE123456789).';
    msg.className = 'co-vat-msg err';
    _coVatExempt = false;
  } else {
    inp.value = val;
    _coVatExempt = true;
    msg.textContent = '✓ Valid EU VAT — tax exempted.';
    msg.className = 'co-vat-msg ok';
  }
  coRecalc();
}

/* ── Place Order ─────────────────────────────────────────── */
function coPlaceOrder() {
  if (!_coItems.length || !_coDelivId || _coShipping === null || !_coPmId) return;
  const vatNum    = (document.getElementById('coVatInput')?.value.trim().toUpperCase()||'').replace(/\s+/g,'');
  const billSame  = _coBillSame ? 1 : 0;
  const billId    = _coBillSame ? _coDelivId : (_coBillId || _coDelivId);

  const payload = {
    delivery_address_id: _coDelivId,
    billing_same:        billSame,
    billing_address_id:  billId,
    payment_mode_id:     _coPmId,
    vat_number:          vatNum,
    items: _coItems.map(it => ({ product_id: it.id, qty: it.qty })),
  };

  const btn = document.getElementById('coPlaceBtn');
  if (btn) { btn.disabled = true; btn.classList.add('loading');
    btn.innerHTML = '<span style="display:flex;align-items:center;gap:6px;"><svg width="15" height="15" class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>Processing…</span>'; }

  fetch('ajax/order?action=place', {
    method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload),
  })
  .then(r => r.json())
  .then(d => {
    if (d.ok) {
      localStorage.removeItem('sinelec_cart');
      document.querySelectorAll('#cartCount,.cart-count-badge').forEach(el => { el.textContent='0'; el.hidden=true; });
      /* Pass bank details via sessionStorage for success page */
      if (d.bank_details && d.bank_details.length) {
        sessionStorage.setItem('_co_bank', JSON.stringify(d.bank_details));
        sessionStorage.setItem('_co_total', d.final_total || '');
      }
      window.location.href = 'order-success?order=' + encodeURIComponent(d.order_number) + '&pt=' + encodeURIComponent(d.payment_type||'');
    } else {
      if (btn) { btn.disabled = false; btn.classList.remove('loading');
        btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg> Place Order'; }
      if (window.toast) toast(d.msg || 'Something went wrong.', 'error');
      else alert(d.msg || 'Something went wrong.');
    }
  })
  .catch(() => {
    if (btn) { btn.disabled = false; btn.classList.remove('loading');
      btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg> Place Order'; }
    if (window.toast) toast('Network error. Please try again.', 'error');
    else alert('Network error. Please try again.');
  });
}

function _e(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── PayPal toggle helpers ───────────────────────────────── */
let _coIsPaypal = false;

function _coTogglePaypalBtn() {
  const placeBtn = document.getElementById('coPlaceBtn');
  const ppWrap   = document.getElementById('paypal-btn-container');
  if (_coIsPaypal) {
    if (placeBtn) placeBtn.style.display = 'none';
    if (ppWrap)   ppWrap.style.display   = 'block';
    _ppMaybeRender();
  } else {
    if (placeBtn) placeBtn.style.display = '';
    if (ppWrap)   ppWrap.style.display   = 'none';
    /* Clear any PayPal messages when switching away */
    const msg = document.getElementById('paypal-btn-msg');
    if (msg) { msg.style.display = 'none'; msg.textContent = ''; }
  }
}

/* Detect initial PayPal tab on page load */
document.addEventListener('DOMContentLoaded', function() {
  const activePmTab = document.querySelector('.co-pm-tab.active');
  if (activePmTab) {
    const t = (activePmTab.dataset.pmType || '').toLowerCase();
    if (t === 'paypal') { _coIsPaypal = true; _coTogglePaypalBtn(); }
  }
});
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin .7s linear infinite; display:inline-block; }
.co-pm-info--paypal{background:#f0f4ff;color:#003087;}
.co-pm-info--paypal strong{color:#003087;}
#paypal-btn-container .paypal-buttons{border-radius:10px!important;overflow:hidden;}
</style>

<?php if ($_hasPaypal && $_ppClientId): ?>
<!-- PayPal JS SDK — loaded only when PayPal payment mode is active -->
<script
  src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars($_ppClientId) ?>&currency=<?= htmlspecialchars($_ppCurrency) ?>&intent=capture"
  onload="_ppSdkReady()"
  onerror="_ppSdkFailed()">
</script>
<script>
const _PP_CURRENCY = <?= json_encode($_ppCurrency) ?>;
const _PP_MODE     = <?= json_encode($_ppMode) ?>;
let   _ppRendered        = false;
let   _ppButtons         = null;
let   _ppHadError        = false; /* prevents onError overwriting our specific messages */
let   _ppCurrentOrderId  = null;  /* PayPal order ID of the in-progress payment */

/* Called by SDK <script onload> */
function _ppSdkReady() {
  if (_coIsPaypal) _ppMaybeRender();
}
function _ppSdkFailed() {
  const c = document.getElementById('paypal-btn-container');
  if (c) c.innerHTML = '<p style="font-size:12px;color:#e0415a;padding:8px 0;">PayPal failed to load. Please refresh the page.</p>';
}

/* Render (or re-render) the PayPal button */
function _ppMaybeRender() {
  const container = document.getElementById('paypal-btn-container');
  if (!container) return;

  /* SDK not loaded yet — will be called again from _ppSdkReady */
  if (typeof paypal === 'undefined') return;

  /* Destroy previous instance before re-rendering */
  if (_ppButtons) {
    try { _ppButtons.close(); } catch(e) {}
    container.innerHTML = '';
    _ppRendered = false;
    _ppButtons  = null;
  }

  _ppHadError       = false;
  _ppCurrentOrderId = null;
  _ppRendered       = true;

  _ppButtons = paypal.Buttons({
    style: { layout:'vertical', color:'blue', shape:'rect', label:'pay', height:45 },

    /* ── Step 1: Validate → save order in DB (Payment Pending) → create PayPal order ── */
    createOrder: async function() {
      _ppHadError       = false;
      _ppCurrentOrderId = null;
      _ppSetMsg('', '');

      /* Client-side guard */
      if (!_coItems.length) {
        _ppHadError = true;
        _ppSetMsg('Your cart is empty.', 'err');
        throw new Error('empty cart');
      }
      if (!_coDelivId) {
        _ppHadError = true;
        _ppSetMsg('Please select a delivery address first.', 'err');
        throw new Error('no address');
      }
      if (_coShipping === null) {
        _ppHadError = true;
        _ppSetMsg('Shipping cost is still loading — please wait a moment.', 'warn');
        throw new Error('shipping loading');
      }

      const vatNum  = (document.getElementById('coVatInput')?.value.trim().toUpperCase()||'').replace(/\s+/g,'');
      const payload = {
        delivery_address_id: _coDelivId,
        billing_same:        _coBillSame ? 1 : 0,
        billing_address_id:  _coBillSame ? _coDelivId : (_coBillId || _coDelivId),
        payment_mode_id:     _coPmId,
        vat_number:          vatNum,
        items: _coItems.map(it => ({ product_id: it.id, qty: it.qty })),
      };

      let data;
      try {
        const res = await fetch('ajax/paypal?action=create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        data = await res.json();
      } catch(e) {
        _ppHadError = true;
        _ppSetMsg('Network error. Please check your connection and try again.', 'err');
        throw e;
      }

      if (!data.ok) {
        _ppHadError = true;
        _ppSetMsg(data.msg || 'Could not start PayPal payment. Please try again.', 'err');
        throw new Error(data.msg || 'create failed');
      }

      /* Order saved in DB — track PayPal order ID for cancel/error cleanup */
      _ppCurrentOrderId = data.paypal_order_id;
      return data.paypal_order_id;
    },

    /* ── Step 2: Buyer approved → capture payment + redirect ── */
    onApprove: async function(ppData) {
      _ppHadError = true; /* suppress generic onError if capture later fails */
      _ppCurrentOrderId = null;
      _ppSetMsg('Processing payment… please wait.', 'info');

      let data;
      try {
        const res = await fetch('ajax/paypal?action=capture', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ paypal_order_id: ppData.orderID }),
        });
        data = await res.json();
      } catch(e) {
        /* Network error — redirect to orders so user can see saved order */
        window.location.href = 'my-orders?payment=failed';
        return;
      }

      /* Success or failure — both go to My Orders (server supplies the redirect URL) */
      if (data.ok) {
        localStorage.removeItem('sinelec_cart');
        document.querySelectorAll('#cartCount,.cart-count-badge').forEach(el => {
          el.textContent = '0'; el.hidden = true;
        });
      }
      window.location.href = data.redirect || (data.ok ? 'my-orders?payment=success' : 'my-orders?payment=failed');
    },

    onCancel: function(data) {
      /* User closed the PayPal popup — mark the saved order as Payment Failed then go to My Orders */
      const ppOid = (data && data.orderID) ? data.orderID : _ppCurrentOrderId;
      _ppCurrentOrderId = null;
      if (ppOid) {
        /* Fire-and-forget cancel; navigate immediately */
        fetch('ajax/paypal?action=cancel', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ paypal_order_id: ppOid }),
        }).catch(() => {}).finally(() => {
          window.location.href = 'my-orders?payment=cancelled';
        });
      } else {
        window.location.href = 'my-orders?payment=cancelled';
      }
    },

    /* Only show generic error if WE didn't already set a specific message */
    onError: function(err) {
      console.error('PayPal SDK error:', err);
      /* If an order was saved in DB but PayPal SDK errored, mark it failed then redirect */
      const ppOid = _ppCurrentOrderId;
      _ppCurrentOrderId = null;
      if (ppOid && !_ppHadError) {
        fetch('ajax/paypal?action=cancel', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ paypal_order_id: ppOid }),
        }).catch(() => {}).finally(() => {
          window.location.href = 'my-orders?payment=failed';
        });
      } else if (!_ppHadError) {
        _ppSetMsg('An unexpected PayPal error occurred. Please try again or choose a different payment method.', 'err');
      }
      _ppHadError = false;
    },
  });

  _ppButtons.render('#paypal-btn-container').catch(function(err) {
    console.error('PayPal render error:', err);
    container.innerHTML = '<p style="font-size:12px;color:#e0415a;padding:8px 4px;">PayPal button failed to render. Please refresh the page.</p>';
  });
}

/* ── Msg helper ───────────────────────────────────────────── */
function _ppSetMsg(msg, type) {
  const el = document.getElementById('paypal-btn-msg');
  if (!el) return;
  el.textContent     = msg;
  el.style.display   = msg ? 'block' : 'none';
  el.style.background = type === 'err'  ? '#fff1f2' :
                        type === 'warn' ? '#fffbeb' : '#f0f9ff';
  el.style.color      = type === 'err'  ? '#be123c' :
                        type === 'warn' ? '#92400e' : '#0369a1';
  el.style.border     = '1px solid ' + (
                        type === 'err'  ? '#fecdd3' :
                        type === 'warn' ? '#fde68a' : '#bae6fd');
}

/* Re-render PayPal button after shipping recalculates (amount changed) */
function coFetchShipping(addrId) {
  /* Call original — defined earlier in this page */
  _coFetchShippingBase(addrId);
  if (_coIsPaypal && _ppRendered) {
    setTimeout(() => { if (_coIsPaypal) _ppMaybeRender(); }, 1600);
  }
}
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
