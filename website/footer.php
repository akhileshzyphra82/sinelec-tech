
<!-- ══════════ FOOTER ════════════════════════════════════════ -->
<footer class="site-footer">
  <div class="footer-back-top">
    <button onclick="window.scrollTo({top:0,behavior:'smooth'})">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
      Back to top
    </button>
  </div>
  <div class="wrap">
    <div class="footer-main">

      <!-- Brand -->
      <div>
        <div class="footer-logo-wrap">
          <?php $_footerLogo = trim((string)($company->LOGO ?? '')); if ($_footerLogo === '') $_footerLogo = '../assets/logo.png'; ?>
          <a href="index" class="footer-logo-link" aria-label="Sinelec Tech — Home">
            <img src="<?= htmlspecialchars($_footerLogo) ?>" alt="Sinelec Tech" class="footer-logo-img">
          </a>
        </div>
<?php
$_co_phone   = htmlspecialchars((string)($company->CONTACT_NUMBER  ?? '+49 (0)8165-9906178'));
$_co_email   = htmlspecialchars((string)($company->EMAIL           ?? 'contact@sinelec-tech.com'));
$_co_addr    = htmlspecialchars((string)($company->ADDRESS         ?? 'Brachvogelweg 9, 85375 Neufahrn, Germany'));
$_co_fax     = htmlspecialchars((string)($company->FAX             ?? '+49 (0)8165-9039998'));
$_co_fb      = htmlspecialchars((string)($company->FACEBOOK_URL    ?? ''));
$_co_tw      = htmlspecialchars((string)($company->TWITTER_URL     ?? ''));
$_co_li      = htmlspecialchars((string)($company->LINKEDIN_URL    ?? ''));
$_co_yt      = htmlspecialchars((string)($company->YOUTUBE_URL     ?? ''));
$_co_ig      = htmlspecialchars((string)($company->INSTAGRAM_URL   ?? ''));
?>
        <div class="footer-contact-list">
          <?php if ($_co_phone): ?>
          <div class="footer-ci">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.79 19.79 0 013.07 8.81a2 2 0 011.95-2.18h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L9.91 14a16 16 0 006.09 6.09l.72-.72a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 21.5v3z"/></svg>
            <span><strong>Phone:</strong> <?= $_co_phone ?></span>
          </div>
          <?php endif; ?>
          <?php if ($_co_email): ?>
          <div class="footer-ci">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <span><strong>Email:</strong> <?= $_co_email ?></span>
          </div>
          <?php endif; ?>
          <?php if ($_co_addr): ?>
          <div class="footer-ci">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span><strong>Address:</strong> <?= $_co_addr ?></span>
          </div>
          <?php endif; ?>
          <?php if ($_co_fax): ?>
          <div class="footer-ci">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M6 9V2h12v20H6v-7"/><polyline points="6 15 2 15 2 9 6 9"/><line x1="10" y1="6" x2="14" y2="6"/><line x1="10" y1="10" x2="14" y2="10"/></svg>
            <span><strong>Fax:</strong> <?= $_co_fax ?></span>
          </div>
          <?php endif; ?>
        </div>
        <div class="footer-socials">
          <?php if ($_co_fb): ?><a href="<?= $_co_fb ?>" class="fsoc" title="Facebook" target="_blank" rel="noopener"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg></a><?php endif; ?>
          <?php if ($_co_tw): ?><a href="<?= $_co_tw ?>" class="fsoc" title="Twitter / X" target="_blank" rel="noopener"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.264 5.633 5.9-5.633zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a><?php endif; ?>
          <?php if ($_co_li): ?><a href="<?= $_co_li ?>" class="fsoc" title="LinkedIn" target="_blank" rel="noopener"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg></a><?php endif; ?>
          <?php if ($_co_yt): ?><a href="<?= $_co_yt ?>" class="fsoc" title="YouTube" target="_blank" rel="noopener"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 00-1.95 1.96A29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 001.95-1.95A29 29 0 0023 12a29 29 0 00-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="#131A2E"/></svg></a><?php endif; ?>
          <?php if ($_co_ig): ?><a href="<?= $_co_ig ?>" class="fsoc" title="Instagram" target="_blank" rel="noopener"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a><?php endif; ?>
        </div>
      </div>

      <!-- Company -->
      <div>
        <div class="footer-col-title">Company</div>
        <ul class="footer-links">
          <li><a href="details?key=about_us" class="footer-link">About Us</a></li>
          <li><a href="career" class="footer-link">Careers</a></li>
          <li><a href="details?key=legal_information" class="footer-link">Legal Information</a></li>
          <li><a href="details?key=disclaimer" class="footer-link">Disclaimer</a></li>
          <li><a href="details?key=privacy_policy" class="footer-link">Privacy Policy</a></li>
          <li><a href="details?key=terms_of_use" class="footer-link">Terms of Use</a></li>
        </ul>
      </div>

      <!-- Customer Support -->
      <div>
        <div class="footer-col-title">Customer Support</div>
        <ul class="footer-links">
          <li><a href="contact-us" class="footer-link">Contact Us</a></li>
          <li><a href="request-a-quote" class="footer-link">Request Quote</a></li>
          <li><a href="shipping-payment-term" class="footer-link">Shipping &amp; Payment</a></li>
          <li><a href="contact-us?help=1" class="footer-link">Technical Help &amp; Support</a></li>
          <li><a href="#" onclick="sinelaAuthGate('my-orders'); return false;" class="footer-link">Order Status</a></li>
          <li><a href="#" onclick="sinelaAuthGate('my-orders?tab=returns'); return false;" class="footer-link">RMA Returns</a></li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      <span>Copyright &copy; <?= date('Y') ?> Sinelec Technologies. All rights reserved.</span>
    </div>
  </div>
</footer>

<!-- ══════════ CART PANEL ═════════════════════════════════════ -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>
<div class="cart-panel" id="cartPanel">
  <div class="cart-hd">
    <div class="cart-hd-title">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
      Shopping Cart
    </div>
    <button class="cart-close-btn" onclick="closeCart()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div id="cartFreeShip" class="cart-free-ship hidden">
    ✅ Your order qualifies for FREE shipping!
  </div>
  <div class="cart-items-wrap" id="cartItemsWrap"></div>
  <div class="cart-foot">
    <div class="cart-subtotal-row"><span>Subtotal</span><span id="cartSub">₹0.00</span></div>
    <div class="cart-subtotal-row"><span>Shipping</span><span id="cartShip">₹99.00</span></div>
    <div class="cart-subtotal-row"><span>GST (18%)</span><span id="cartGST">₹0.00</span></div>
    <div class="cart-subtotal-row total-row"><span>Order Total</span><span id="cartTotal">₹0.00</span></div>
    <button class="cart-checkout-btn" onclick="showPageLoader('Redirecting to checkout...');window.location.href='checkout'">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Proceed to Checkout
    </button>
    <p class="cart-secure-note">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Secure checkout · Free returns · 100% genuine
    </p>
  </div>
</div>

<!-- ══════════ COMPARE BAR ════════════════════════════════════ -->
<div class="compare-bar" id="compareBar">
  <div class="wrap">
    <div class="compare-inner">
      <span class="compare-title">Compare Products</span>
      <div class="compare-slots" id="compareSlots"></div>
      <button class="btn btn-yellow btn-sm" onclick="toast('Full compare page coming soon!','warn')">Compare Now</button>
      <button class="btn btn-ghost-white btn-sm" onclick="clearCompare()">Clear</button>
    </div>
  </div>
</div>

<!-- ══════════ FLOATING BUTTONS ══════════════════════════════ -->
<?php require_once __DIR__ . '/../chatbot.php'; ?>
<button class="scroll-top-fab" id="scrollTopFab" title="Back to top">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
</button>

<!-- Toast Container -->
<div class="toast-wrap" id="toastWrap"></div>

<!-- Global Loader -->
<div class="page-loader-overlay" id="globalPageLoader" aria-hidden="true">
  <div class="page-loader-card">
    <span class="page-loader-spinner" aria-hidden="true"></span>
  </div>
</div>

<!-- ══════════ COOKIE CONSENT ══════════════════════════════════ -->
<div class="ck-bar" id="ckBar" aria-live="polite" role="region" aria-label="Cookie consent" hidden>
  <div class="ck-inner">

    <p class="ck-text">
      Sinelec Technologies uses cookies and similar technology to improve site performance, enhance security, personalise content and ads, and understand how you interact with our services. This information is shared with third-party service providers. Click &ldquo;Reject All&rdquo; to disable all non-essential cookies. By continuing on our site, you agree to our website
      <a href="terms-conditions" class="ck-link">Terms &amp; Conditions</a> and <a href="privacy-policy" class="ck-link">Privacy Notice</a>.
    </p>

    <div class="ck-actions">
      <button type="button" class="ck-btn ck-btn--outline" id="ckSettingsBtn">Cookie Settings</button>
      <button type="button" class="ck-btn ck-btn--reject"  id="ckRejectBtn">Reject All</button>
      <button type="button" class="ck-btn ck-btn--accept"  id="ckAcceptBtn">Accept All Cookies</button>
    </div>

    <button type="button" class="ck-close" id="ckCloseBtn" aria-label="Close cookie banner">&times;</button>
  </div>
</div>


<style>
/* ── Cookie banner ──────────────────────────────────── */
.ck-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 9999;
  background: #ffffff;
  border-top: 1px solid #c8cfd8;
  box-shadow: 0 -2px 16px rgba(0,0,0,.08);
  animation: ckSlideUp .3s ease;
}
@keyframes ckSlideUp {
  from { transform: translateY(100%); }
  to   { transform: translateY(0); }
}
.ck-inner {
  max-width: 1400px;
  margin: 0 auto;
  padding: 18px 32px;
  display: flex;
  align-items: center;
  gap: 24px;
}
.ck-text {
  flex: 1 1 0;
  font-size: 13.5px;
  color: #1a1a1a;
  line-height: 1.6;
  margin: 0;
  min-width: 0;
}
.ck-link {
  color: #1a5dc8;
  text-decoration: underline;
  font-weight: 600;
}
.ck-link:hover { color: #0f44a3; }
.ck-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}
.ck-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 44px;
  padding: 0 22px;
  border-radius: 4px;
  font-size: 13.5px;
  font-weight: 700;
  white-space: nowrap;
  cursor: pointer;
  transition: background .15s, border-color .15s;
  line-height: 1;
}
.ck-btn--outline {
  background: #ffffff;
  color: #1a1a1a;
  border: 2px solid #1a1a1a;
}
.ck-btn--outline:hover { background: #f5f5f5; }
.ck-btn--reject {
  background: #d0021b;
  color: #ffffff;
  border: 2px solid #d0021b;
}
.ck-btn--reject:hover { background: #b00218; border-color: #b00218; }
.ck-btn--accept {
  background: #d0021b;
  color: #ffffff;
  border: 2px solid #d0021b;
}
.ck-btn--accept:hover { background: #b00218; border-color: #b00218; }
.ck-close {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  font-size: 24px;
  line-height: 1;
  color: #444;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: color .15s;
  padding: 0;
}
.ck-close:hover { color: #000; }

/* ── Responsive ─────────────────────────────────────── */
@media (max-width: 960px) {
  .ck-inner {
    flex-wrap: wrap;
    gap: 14px;
    padding: 16px 20px;
  }
  .ck-text    { flex-basis: 100%; font-size: 13px; }
  .ck-actions { order: 2; flex-wrap: wrap; gap: 8px; }
  .ck-close   { order: 3; margin-left: auto; }
}
@media (max-width: 600px) {
  .ck-inner   { padding: 14px 16px; }
  .ck-actions { width: 100%; }
  .ck-btn     { flex: 1 1 calc(50% - 4px); height: 42px; font-size: 12.5px; padding: 0 10px; }
  .ck-btn--accept { flex-basis: 100%; flex-grow: 0; width: 100%; }
  .ck-text    { font-size: 12.5px; }
}
@media (max-width: 400px) {
  .ck-btn  { height: 40px; font-size: 12px; }
  .ck-text { font-size: 12px; }
}
</style>

<script>
(function () {
  var STORAGE_KEY = 'sinelec_cookie_consent';
  var bar = document.getElementById('ckBar');

  if (!bar) return;
  if (!localStorage.getItem(STORAGE_KEY)) bar.hidden = false;

  function done(val) {
    localStorage.setItem(STORAGE_KEY, val);
    bar.hidden = true;
  }

  document.getElementById('ckAcceptBtn')  && document.getElementById('ckAcceptBtn').addEventListener('click',   function () { done('accepted'); });
  document.getElementById('ckRejectBtn')  && document.getElementById('ckRejectBtn').addEventListener('click',   function () { done('rejected'); });
  document.getElementById('ckSettingsBtn')&& document.getElementById('ckSettingsBtn').addEventListener('click', function () { done('dismissed'); });
  document.getElementById('ckCloseBtn')   && document.getElementById('ckCloseBtn').addEventListener('click',    function () { done('dismissed'); });
})();
</script>

<!-- ══════════ AUTH GATE ══════════════════════════════════════ -->
<script>
function sinelaAuthGate(url) {
  if (window.SINELEC_AUTH && window.SINELEC_AUTH.isSignedIn) {
    window.location.href = url;
  } else {
    sessionStorage.setItem('sinelec_auth_redirect', url);
    var modal = document.getElementById('authModal');
    if (modal) {
      modal.hidden = false;
      document.body.style.overflow = 'hidden';
      var desc = document.getElementById('authModalDesc');
      if (desc) desc.textContent = 'Sign in to access your orders.';
    }
  }
}
(function () {
  if (window.SINELEC_AUTH && window.SINELEC_AUTH.isSignedIn) {
    var redir = sessionStorage.getItem('sinelec_auth_redirect');
    if (redir) {
      sessionStorage.removeItem('sinelec_auth_redirect');
      window.location.href = redir;
    }
  }
})();
</script>

<!-- ══════════ SCRIPTS ════════════════════════════════════════ -->
<script src="../js/cart.js"></script>
<script src="../js/app.js"></script>
<script src="../assets/js/chatbot.js"></script>
</body>
</html>
