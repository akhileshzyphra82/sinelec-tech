<?php
require_once '../data/store_data.php';
$currentPage = 'checkout';
$pageTitle = 'Checkout - Sinelec Tech';
require_once 'header.php';
?>

<main>
  <style>
    .checkout-layout.checkout-layout--summary-only {
      grid-template-columns: minmax(0, 1.45fr) minmax(320px, .9fr);
      gap: 16px;
    }
    .checkout-layout.checkout-layout--summary-only .checkout-main {
      display: block;
    }
    .checkout-layout.checkout-layout--summary-only .checkout-summary {
      max-width: 100%;
      margin: 0;
    }
    .checkout-product-title {
      font-size: 16px;
      font-weight: 800;
      color: #16314e;
      margin-bottom: 2px;
    }
    .checkout-product-sub {
      font-size: 12px;
      color: #6a829e;
      margin: 0 0 10px;
    }
    .checkout-summary-link-row {
      margin-bottom: 10px;
      padding-bottom: 8px;
      border-bottom: 1px solid #e6edf6;
    }
    .checkout-summary-link-row a {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      color: #1363bf;
      font-weight: 700;
      text-decoration: none;
    }
    .checkout-summary-link-row a:hover {
      text-decoration: none;
    }
    .checkout-item-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .checkout-item-left {
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .checkout-item-select {
      accent-color: #145eb4;
      width: 14px;
      height: 14px;
      margin: 0;
      flex-shrink: 0;
    }
    .checkout-item-remove {
      border: 0;
      background: transparent;
      color: #8a9eb7;
      width: 24px;
      height: 24px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      cursor: pointer;
    }
    .checkout-item-remove:hover {
      background: #f1f6fc;
      color: #cf3c56;
    }
    .checkout-item-controls {
      margin-top: 8px;
      display: inline-flex;
      align-items: center;
      gap: 0;
      border: 1px solid #ccd8e8;
      border-radius: 8px;
      overflow: hidden;
      background: #fff;
    }
    .checkout-qty-btn {
      width: 28px;
      height: 26px;
      border: 0;
      background: transparent;
      color: #315272;
      font-weight: 800;
      line-height: 1;
      cursor: pointer;
    }
    .checkout-qty-btn:hover {
      background: #f3f7fd;
    }
    .checkout-qty-num {
      min-width: 28px;
      height: 26px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-size: 12px;
      color: #21415f;
      font-weight: 700;
      border-left: 1px solid #e1e9f3;
      border-right: 1px solid #e1e9f3;
    }
    .checkout-address-change-btn {
      margin-left: 6px;
      padding: 0;
      border: 0;
      background: transparent;
      color: #1363bf;
      font-size: 11px;
      font-weight: 700;
      text-decoration: underline;
      cursor: pointer;
    }
    .checkout-summary-strong {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 2px;
      text-align: right;
    }
    .checkout-payment-switch {
      margin: 8px 0 10px;
      border: 1px solid #d8e4f2;
      border-radius: 10px;
      padding: 10px;
      background: #f8fbff;
    }
    .checkout-payment-switch-label {
      display: block;
      font-size: 11px;
      color: #5f7795;
      margin-bottom: 7px;
      font-weight: 700;
    }
    .checkout-payment-switch-grid {
      display: grid;
      gap: 6px;
      margin-bottom: 8px;
    }
    .checkout-payment-switch-item {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 12px;
      color: #1f3958;
    }
    .checkout-payment-switch-item input {
      accent-color: #145eb4;
    }
    .checkout-vat-input {
      height: 36px;
      border-radius: 8px;
      border: 1px solid #cfdbeb;
      padding: 0 10px;
      font-size: 12px;
      color: #203b5b;
      width: 100%;
      background: #fff;
    }
    .checkout-vat-row {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 8px;
    }
    .checkout-vat-apply-btn {
      min-width: 78px;
      height: 36px;
      border-radius: 8px;
      border: 1px solid #cfe0f4;
      background: #f3f8ff;
      color: #1c4f90;
      font-size: 12px;
      font-weight: 800;
      cursor: pointer;
    }
    .checkout-vat-apply-btn:hover {
      background: #eaf3ff;
    }
    .checkout-vat-state {
      margin-top: 6px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      font-weight: 700;
      color: #1f8d48;
    }
    .checkout-tax-old {
      color: #869ab1;
      text-decoration: line-through;
      margin-right: 6px;
      font-weight: 600;
    }
    .checkout-tax-applied {
      color: #1f8d48;
      font-size: 11px;
      font-weight: 800;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      white-space: nowrap;
    }
    .checkout-layout.checkout-layout--summary-only .checkout-summary-meta {
      margin-top: 0;
      padding-top: 0;
      border-top: 0;
    }
    @media (min-width: 1600px) {
      .checkout-layout.checkout-layout--summary-only {
        grid-template-columns: minmax(0, 1.55fr) minmax(360px, .85fr);
        gap: 20px;
      }
    }
    @media (max-width: 1399px) {
      .checkout-layout.checkout-layout--summary-only {
        grid-template-columns: minmax(0, 1.35fr) minmax(320px, .95fr);
        gap: 14px;
      }
      .checkout-product-title {
        font-size: 15px;
      }
    }
    @media (max-width: 1199px) {
      .checkout-layout.checkout-layout--summary-only {
        grid-template-columns: minmax(0, 1.25fr) minmax(300px, 1fr);
      }
      .checkout-payment-switch-item {
        font-size: 11px;
      }
      .checkout-summary-link-row a {
        font-size: 11px;
      }
    }
    @media (max-width: 1024px) {
      .checkout-layout.checkout-layout--summary-only {
        grid-template-columns: 1fr;
      }
      .checkout-layout.checkout-layout--summary-only .checkout-summary {
        position: static;
        top: auto;
      }
      .checkout-layout.checkout-layout--summary-only .checkout-card {
        border-radius: 18px;
      }
    }
    @media (max-width: 767px) {
      .checkout-layout.checkout-layout--summary-only {
        gap: 12px;
      }
      .checkout-layout.checkout-layout--summary-only .checkout-card-head,
      .checkout-layout.checkout-layout--summary-only .checkout-card-body {
        padding: 14px;
      }
      .checkout-product-title {
        font-size: 14px;
      }
      .checkout-product-sub,
      .checkout-summary-link-row a,
      .checkout-summary-row,
      .checkout-payment-switch-item,
      .checkout-vat-input {
        font-size: 11px;
      }
      .checkout-summary-row strong {
        font-size: 11px;
      }
      .checkout-summary-row--total {
        font-size: 14px;
      }
      .checkout-item {
        grid-template-columns: 56px 1fr;
        gap: 10px;
      }
      .checkout-item img {
        width: 56px;
        height: 56px;
        border-radius: 10px;
      }
      .checkout-item-price {
        grid-column: 2;
        justify-self: end;
      }
      .checkout-item-name {
        font-size: 12px;
      }
      .checkout-item-meta {
        font-size: 11px;
      }
      .checkout-payment-switch {
        padding: 9px;
      }
      .checkout-vat-row {
        grid-template-columns: 1fr;
      }
      .checkout-vat-apply-btn {
        width: 100%;
      }
    }
    @media (max-width: 479px) {
      .checkout-layout.checkout-layout--summary-only {
        gap: 10px;
      }
      .checkout-layout.checkout-layout--summary-only .checkout-card {
        border-radius: 14px;
      }
      .checkout-address-change-btn {
        font-size: 10px;
      }
      .checkout-summary-link-row {
        margin-bottom: 8px;
        padding-bottom: 6px;
      }
      .checkout-item-head {
        gap: 6px;
      }
      .checkout-item-left {
        gap: 6px;
      }
      .checkout-qty-btn,
      .checkout-qty-num {
        height: 24px;
      }
      .checkout-qty-btn {
        width: 26px;
      }
      .checkout-qty-num {
        min-width: 26px;
        font-size: 11px;
      }
      .checkout-secure-line {
        font-size: 10px;
      }
    }
  </style>
  <div class="wrap page-wrap">
    <div class="page-hero checkout-hero">
      <div class="page-eyebrow">Secure Checkout</div>
      <h1 class="page-title">Complete Your Order</h1>
      <p class="page-sub">Review your delivery details, choose your address and payment method, and place your order with confidence.</p>
    </div>

    <div class="checkout-layout checkout-layout--summary-only" id="checkoutPage">
      <div class="checkout-main">
        <section class="checkout-card">
          <div class="checkout-card-body">
            <div class="checkout-product-title">Products</div>
            <p class="checkout-product-sub" id="checkoutProductSub">Items currently added to your cart.</p>
            <div class="checkout-summary-items checkout-summary-items--left" id="checkoutSummaryItems"></div>
          </div>
        </section>
      </div>

      <aside class="checkout-summary">
        <section class="checkout-card checkout-card--sticky">
          <div class="checkout-card-head">
            <div>
              <div class="checkout-card-title">Order Summary</div>
              <div class="checkout-card-sub">A quick review before you place the order.</div>
            </div>
          </div>
          <div class="checkout-card-body">
            <div class="checkout-empty hidden" id="checkoutEmptyState">
              <p>Your cart is empty right now.</p>
              <a href="products" class="btn btn-blue">Browse Products</a>
            </div>

            <div id="checkoutSummaryContent">
              <div class="checkout-summary-meta">
                <div class="checkout-summary-link-row">
                  <a href="shipping-payment-term">
                    <span>Shipping and payment term for your location</span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><polyline points="9 6 15 12 9 18"/></svg>
                  </a>
                </div>
                <div class="checkout-summary-row">
                  <span>Delivering to</span>
                  <strong class="checkout-summary-strong">
                    <span id="checkoutSelectedAddressText">Select an address</span>
                    <button type="button" class="checkout-address-change-btn" id="checkoutChangeAddressBtn">Change address</button>
                  </strong>
                </div>
                <div class="checkout-summary-row">
                  <span>Delivery mode</span>
                  <strong id="checkoutShippingText">Standard Delivery</strong>
                </div>
                <div class="checkout-summary-row">
                  <span>Payment</span>
                  <strong id="checkoutPaymentText">PayPal</strong>
                </div>
              </div>

              <div class="checkout-payment-switch">
                <label class="checkout-payment-switch-label">Change payment mode</label>
                <div class="checkout-payment-switch-grid">
                  <label class="checkout-payment-switch-item">
                    <input type="radio" name="checkoutPayment" value="paypal" checked>
                    <span>Paypal</span>
                  </label>
                  <label class="checkout-payment-switch-item">
                    <input type="radio" name="checkoutPayment" value="card_paypal">
                    <span>Credit Card via Paypal (No Paypal Account needed)</span>
                  </label>
                  <label class="checkout-payment-switch-item">
                    <input type="radio" name="checkoutPayment" value="bank">
                    <span>Bank Transfer</span>
                  </label>
                  <label class="checkout-payment-switch-item">
                    <input type="radio" name="checkoutPayment" value="invoice">
                    <span>Invoice (Corporate customers)</span>
                  </label>
                </div>
                <div class="checkout-vat-row">
                  <input type="text" class="checkout-vat-input" id="checkoutEuVat" placeholder="EU VAT Number (optional)">
                  <button type="button" class="checkout-vat-apply-btn" id="checkoutVatApplyBtn">Apply</button>
                </div>
                <div class="checkout-vat-state hidden" id="checkoutVatState">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                  <span>VAT applied</span>
                </div>
              </div>

              <div class="checkout-totals">
                <div class="checkout-summary-row"><span>Subtotal</span><strong id="checkoutSubtotal">₹0.00</strong></div>
                <div class="checkout-summary-row"><span>Shipping</span><strong id="checkoutShippingCost">₹0.00</strong></div>
                <div class="checkout-summary-row"><span>GST (18%)</span><strong id="checkoutTax">₹0.00</strong></div>
                <div class="checkout-summary-row checkout-summary-row--total"><span>Order Total</span><strong id="checkoutTotal">₹0.00</strong></div>
              </div>

              <button type="button" class="cart-checkout-btn checkout-place-btn" id="checkoutPlaceBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Place Order
              </button>
              <p class="checkout-secure-line">Secure checkout · Tax invoice available · Support Mon-Sat, 9 AM-6 PM</p>
            </div>
          </div>
        </section>
      </aside>
    </div>
  </div>
</main>

<?php require_once 'footer.php'; ?>
