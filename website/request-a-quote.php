<?php
require_once '../data/store_data.php';
$currentPage = 'request-a-quote';
$pageTitle   = 'Request a Quote — Sinelec Tech';
require_once 'header.php';
?>

<main>

  <!-- Hero Banner -->
  <div class="qhero">
    <div class="wrap">
      <div class="qhero-inner">
        <div class="qhero-left">
          <span class="qhero-tag">Fast · Reliable · Competitive</span>
          <h1 class="qhero-title">Request a Quotation</h1>
          <p class="qhero-desc">Share your requirements and we'll get back with the best price within 24 hours.</p>
	          <div class="qhero-badges">
	            <span class="qhero-badge">
	              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
	              24h Response
            </span>
            <span class="qhero-badge">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Genuine Products
            </span>
	            <span class="qhero-badge">
	              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
	              Pan-India Delivery
	            </span>
	          </div>
	          <a href="https://wa.me/919876543210" class="qhero-whatsapp" target="_blank" rel="noopener" aria-label="Get instant quotes on WhatsApp">
	            <span class="qhero-whatsapp-icon" aria-hidden="true">
	              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
	                <path d="M20.52 3.48A11.8 11.8 0 0012.12 0C5.55 0 .2 5.34.2 11.92c0 2.1.55 4.15 1.6 5.95L0 24l6.33-1.75a11.9 11.9 0 005.79 1.49h.01c6.57 0 11.92-5.35 11.92-11.92 0-3.18-1.24-6.17-3.53-8.34zm-8.4 18.24h-.01a9.9 9.9 0 01-5.03-1.37l-.36-.21-3.76 1.04 1-3.66-.24-.38a9.87 9.87 0 01-1.52-5.22c0-5.45 4.44-9.89 9.91-9.89 2.64 0 5.12 1.02 6.98 2.88a9.8 9.8 0 012.9 7c0 5.46-4.45 9.9-9.87 9.9zm5.43-7.42c-.3-.15-1.76-.86-2.03-.96-.27-.1-.47-.15-.67.15s-.77.96-.94 1.16c-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.46a8.97 8.97 0 01-1.67-2.08c-.18-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.23-.24-.57-.49-.5-.67-.51h-.57c-.2 0-.52.08-.8.38-.27.3-1.03 1.01-1.03 2.46 0 1.44 1.05 2.84 1.2 3.03.15.2 2.06 3.15 5 4.41.7.3 1.24.47 1.67.6.7.22 1.34.19 1.85.12.56-.08 1.76-.72 2-1.42.25-.7.25-1.3.17-1.42-.07-.12-.27-.2-.57-.35z"/>
	              </svg>
	            </span>
	            <span class="qhero-whatsapp-copy">
	              <strong>Get instant quotes on WhatsApp</strong>
	              <small>Fast reply for urgent and bulk enquiries</small>
	            </span>
	            <span class="qhero-whatsapp-badge">Live</span>
	          </a>
	        </div>
	        <div class="qhero-steps">
          <div class="qstep"><div class="qstep-num">1</div><div class="qstep-label">Fill Form</div></div>
          <div class="qstep-arrow">›</div>
          <div class="qstep"><div class="qstep-num">2</div><div class="qstep-label">We Review</div></div>
          <div class="qstep-arrow">›</div>
          <div class="qstep"><div class="qstep-num">3</div><div class="qstep-label">Get Quote</div></div>
          <div class="qstep-arrow">›</div>
          <div class="qstep"><div class="qstep-num">4</div><div class="qstep-label">Confirm &amp; Ship</div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Form + Sidebar -->
  <div class="wrap page-wrap--lg">
    <div class="qlayout">

      <!-- Form Column -->
      <div class="qform-col">
        <form id="quoteForm" class="quote-form" novalidate>

          <!-- Products Section -->
          <div class="qcard">
            <div class="qcard-head">
              <div class="qcard-head-icon qcard-head-icon--blue">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0066CC" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
              </div>
              <div>
                <div class="qcard-title">Products</div>
                <div class="qcard-sub">Add all products you need a quote for</div>
              </div>
            </div>
            <div class="qcard-body">
              <div id="quoteProductRows">
                <div class="qprow">
                  <div class="qprow-num">1</div>
                  <div class="qprow-fields">
                    <div class="qfield">
                      <label class="qlabel">Product Category <span class="qreq">*</span></label>
                      <select class="qinp" required>
                        <option value="">Select Category</option>
                        <?php foreach ($storeData['categories'] as $cat): ?>
                        <option><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="qfield">
                      <label class="qlabel">Product / Part Number <span class="qreq">*</span></label>
                      <div class="quote-product-search">
                        <input
                          type="text"
                          class="qinp quote-product-input"
                          placeholder="Select or search product"
                          autocomplete="off"
                          data-product-search="true"
                          required
                        >
                        <div class="quote-product-drop"></div>
                      </div>
                    </div>
                    <div class="qfield qfield-qty">
                      <label class="qlabel">Qty <span class="qreq">*</span></label>
                      <input type="number" class="qinp" placeholder="1" min="1" required>
                    </div>
                  </div>
                </div>
              </div>
              <div class="qprow-actions">
                <button type="button" class="qbtn-add" onclick="addQuoteRow()">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  Add Another Product
                </button>
              </div>
            </div>
          </div>

          <!-- Contact Info Section -->
          <div class="qcard">
            <div class="qcard-head">
              <div class="qcard-head-icon qcard-head-icon--green">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
              <div>
                <div class="qcard-title">Contact Information</div>
                <div class="qcard-sub">Who should we reach out to?</div>
              </div>
            </div>
            <div class="qcard-body">
              <div class="qgrid-2">
                <div class="qfield">
                  <label class="qlabel">Full Name <span class="qreq">*</span></label>
                  <input type="text" class="qinp" placeholder="Rajesh Kumar" required>
                </div>
                <div class="qfield">
                  <label class="qlabel">Company Name <span class="qreq">*</span></label>
                  <input type="text" class="qinp" placeholder="Your company / organisation" required>
                </div>
              </div>
              <div class="qgrid-2 qgrid-mt">
                <div class="qfield">
                  <label class="qlabel">Email Address <span class="qreq">*</span></label>
                  <input type="email" class="qinp" placeholder="you@company.com" required>
                </div>
                <div class="qfield">
                  <label class="qlabel">Phone Number <span class="qreq">*</span></label>
                  <div class="qphone-wrap">
                    <select class="qinp qinp-code">
                      <option>+91</option><option>+1</option><option>+44</option>
                      <option>+49</option><option>+86</option><option>+65</option><option>+971</option>
                    </select>
                    <input type="tel" class="qinp" placeholder="98765 43210" required>
                  </div>
                </div>
              </div>
              <div class="qgrid-2 qgrid-mt">
                <div class="qfield">
                  <label class="qlabel">Enquiry Type</label>
                  <select class="qinp">
                    <option>Product Quotation</option>
                    <option>Bulk Order</option>
                    <option>Chip Programming</option>
                    <option>Custom Requirement</option>
                    <option>Other</option>
                  </select>
                </div>
                <div class="qfield">
                  <label class="qlabel">Customer Order # <span class="qopt">(optional)</span></label>
                  <input type="text" class="qinp" placeholder="Your PO / reference number">
                </div>
              </div>
            </div>
          </div>

          <!-- Delivery Section -->
          <div class="qcard">
            <div class="qcard-head">
              <div class="qcard-head-icon qcard-head-icon--orange">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ea7c1e" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
              </div>
              <div>
                <div class="qcard-title">Delivery Details</div>
                <div class="qcard-sub">Where should we deliver?</div>
              </div>
            </div>
            <div class="qcard-body">
              <div class="qfield">
                <label class="qlabel">Delivery Address <span class="qreq">*</span></label>
                <textarea class="qinp qtextarea" placeholder="House/Flat No., Street, Area, Landmark…" required></textarea>
              </div>
              <div class="qgrid-4 qgrid-mt">
                <div class="qfield">
                  <label class="qlabel">City <span class="qreq">*</span></label>
                  <input type="text" class="qinp" placeholder="City" required>
                </div>
                <div class="qfield">
                  <label class="qlabel">State <span class="qreq">*</span></label>
                  <input type="text" class="qinp" placeholder="State" required>
                </div>
                <div class="qfield">
                  <label class="qlabel">PIN / ZIP <span class="qreq">*</span></label>
                  <input type="text" class="qinp" placeholder="110001" required>
                </div>
                <div class="qfield">
                  <label class="qlabel">Country <span class="qreq">*</span></label>
                  <select class="qinp" required>
                    <option value="">Select</option>
                    <option selected>India</option>
                    <option>United States</option><option>United Kingdom</option>
                    <option>Germany</option><option>China</option>
                    <option>Singapore</option><option>UAE</option>
                    <option>Australia</option><option>Canada</option>
                  </select>
                </div>
              </div>
              <div class="qgrid-mt">
                <label class="qcheck-label">
                  <input type="checkbox" id="diffBilling" onchange="toggleBilling()">
                  <span>Billing address is different from delivery address</span>
                </label>
              </div>
              <div id="billingSection" class="hidden">
                <div class="qbilling-divider">Billing Address</div>
                <div class="qfield">
                  <textarea class="qinp qtextarea" placeholder="Billing address…"></textarea>
                </div>
                <div class="qgrid-4 qgrid-mt">
                  <div class="qfield"><input type="text" class="qinp" placeholder="City"></div>
                  <div class="qfield"><input type="text" class="qinp" placeholder="State"></div>
                  <div class="qfield"><input type="text" class="qinp" placeholder="PIN / ZIP"></div>
                  <div class="qfield">
                    <select class="qinp">
                      <option value="">Country</option>
                      <option>India</option><option>United States</option>
                      <option>United Kingdom</option><option>Germany</option>
                      <option>Singapore</option><option>UAE</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes Section -->
          <div class="qcard">
            <div class="qcard-head">
              <div class="qcard-head-icon qcard-head-icon--purple">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </div>
              <div>
                <div class="qcard-title">Additional Notes <span class="qopt">(optional)</span></div>
                <div class="qcard-sub">Special requirements, timeline, or specs</div>
              </div>
            </div>
            <div class="qcard-body">
              <textarea class="qinp qtextarea qnotes" placeholder="e.g. Need delivery within 5 days, require RoHS compliance, have firmware for chip programming…"></textarea>
            </div>
          </div>

          <!-- Submit -->
          <div class="qsubmit-row">
            <button type="submit" class="qsubmit-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              Submit Quote Request
            </button>
            <button type="button" class="qclear-btn" onclick="resetQuoteForm()">Clear Form</button>
          </div>

        </form>
      </div>

      <!-- Sidebar -->
      <div class="qsidebar">

        <div class="qside-card qside-why">
          <div class="qside-title">Why request a quote?</div>
          <ul class="qside-list">
            <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Best price for bulk orders</li>
            <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Custom part sourcing</li>
            <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Dedicated account manager</li>
            <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Priority processing &amp; dispatch</li>
            <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>100% genuine certified products</li>
          </ul>
        </div>

        <div class="qside-card qside-contact">
          <div class="qside-title">Prefer to talk directly?</div>
          <div class="qside-contact-item">
            <div class="qside-contact-icon qside-contact-icon--blue">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0066CC" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 8.81 19.79 19.79 0 01.1 2.27 2 2 0 012.07.1h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.16 6.16l.91-.91a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
            </div>
            <div>
              <div class="qside-contact-label">Call / WhatsApp</div>
              <div class="qside-contact-val">+91-98765 43210</div>
            </div>
          </div>
          <div class="qside-contact-item">
            <div class="qside-contact-icon qside-contact-icon--blue">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0066CC" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div>
              <div class="qside-contact-label">Email us</div>
              <div class="qside-contact-val">info@sinelec-tech.com</div>
            </div>
          </div>
          <div class="qside-contact-item">
            <div class="qside-contact-icon qside-contact-icon--blue">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0066CC" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
              <div class="qside-contact-label">Working hours</div>
              <div class="qside-contact-val">Mon–Sat, 9AM–6PM IST</div>
            </div>
          </div>
        </div>

        <div class="qside-card qside-trust">
          <div class="qside-title">Trusted by 50,000+ customers</div>
          <div class="qside-trust-grid">
            <div class="qside-trust-item"><div class="qside-trust-num">24h</div><div class="qside-trust-lbl">Response</div></div>
            <div class="qside-trust-item"><div class="qside-trust-num">100%</div><div class="qside-trust-lbl">Genuine</div></div>
            <div class="qside-trust-item"><div class="qside-trust-num">28</div><div class="qside-trust-lbl">States</div></div>
            <div class="qside-trust-item"><div class="qside-trust-num">10yr</div><div class="qside-trust-lbl">Experience</div></div>
          </div>
        </div>

      </div>
    </div>
  </div>

</main>

<?php require_once 'footer.php'; ?>
