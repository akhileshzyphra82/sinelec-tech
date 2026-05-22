<?php
require_once __DIR__ . '/account-helpers.php';
$user        = sinelec_require_login();
$currentPage = 'delivery-address';
$pageTitle   = 'My Addresses | Sinelec Technologies';
$pageCSS     = ['../css/delivery-address.css'];
$pageJS      = ['../js/delivery-address.js'];

require_once __DIR__ . '/../controller/website_controller.php';
$ctrl      = new WebsiteController();
$countries = $ctrl->getCountries();
$addresses = $ctrl->getUserAddresses((int)($user['USER_ID'] ?? 0));

$fullName = trim((string)($user['NAME'] ?? ''));
$mobile   = trim((string)($user['COMMUNICATION_MOBILE_NUM'] ?? ''));

require_once __DIR__ . '/header.php';
?>

<main class="account-page">
  <div class="wrap">
    <div class="account-shell">
      <?php sinelec_render_account_nav($currentPage); ?>

      <section class="account-main da-main">

        <div class="da-page-head">
          <h1 class="da-page-title">My Addresses</h1>
          <button type="button" class="da-add-btn" id="toggleNewAddressForm"
                  aria-expanded="false" aria-controls="newAddressPanel">
            <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
              <path d="M7.5 1v13M1 7.5h13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Add New Address
          </button>
        </div>

        <!-- ── Add / Edit form ── -->
        <div class="da-form-card" id="newAddressPanel" hidden>
          <div class="da-form-card-head">
            <span id="daFormCardTitle">New Address</span>
          </div>

          <form id="accountAddressForm" novalidate>
            <input type="hidden" id="accountAddrId" value="">

            <!-- Label tabs -->
            <div class="da-tabs-group">
              <p class="da-tabs-label">Address Label</p>
              <div class="da-tabs" role="group" aria-label="Address type">
                <input type="hidden" id="accountAddrLabel" value="Home">
                <button type="button" class="da-tab is-active" data-tab-val="Home">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                    <path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    <path d="M9 21V12h6v9" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                  </svg>
                  Home
                </button>
                <button type="button" class="da-tab" data-tab-val="Office">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                    <rect x="2" y="7" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    <path d="M12 12v3M10 13.5h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  </svg>
                  Office
                </button>
                <button type="button" class="da-tab" data-tab-val="Other">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" stroke="currentColor" stroke-width="1.8"/>
                    <circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="1.6"/>
                  </svg>
                  Other
                </button>
              </div>
            </div>

            <!-- Fields -->
            <div class="da-grid">

              <div class="da-field">
                <label for="accountAddrCountry">Country <span class="da-ast">*</span></label>
                <select id="accountAddrCountry" class="da-input" autocomplete="country-name">
                  <option value="" data-cid="0">— Select Country —</option>
                  <?php foreach ($countries as $c): ?>
                  <option value="<?= htmlspecialchars((string)($c->COUNTRY ?? '')) ?>"
                          data-cid="<?= (int)($c->COUNTRY_ID ?? 0) ?>">
                    <?= htmlspecialchars((string)($c->COUNTRY ?? '')) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
                <input type="hidden" id="accountAddrCountryId" value="0">
              </div>

              <div class="da-field">
                <label for="accountAddrFullName">Company / Full Name <span class="da-ast">*</span></label>
                <input type="text" id="accountAddrFullName" class="da-input"
                       placeholder="Full name or company name" autocomplete="organization">
              </div>

              <div class="da-field">
                <label for="accountAddrName">Contact Name <span class="da-ast">*</span></label>
                <input type="text" id="accountAddrName" class="da-input"
                       value="<?= htmlspecialchars($fullName) ?>" autocomplete="name">
              </div>

              <div class="da-field">
                <label for="accountAddrPhone">Phone Number</label>
                <input type="tel" id="accountAddrPhone" class="da-input"
                       value="<?= htmlspecialchars($mobile) ?>" autocomplete="tel">
              </div>

              <div class="da-field da-field--full">
                <label for="accountAddrLine1">Address Line 1 <span class="da-ast">*</span></label>
                <input type="text" id="accountAddrLine1" class="da-input"
                       placeholder="Street name and number" autocomplete="address-line1">
              </div>

              <div class="da-field da-field--full">
                <label for="accountAddrLine2">Address Line 2</label>
                <input type="text" id="accountAddrLine2" class="da-input"
                       placeholder="Apartment, suite, floor" autocomplete="address-line2">
              </div>

              <div class="da-field da-field--full">
                <label for="accountAddrLine3">Landmark / Area</label>
                <input type="text" id="accountAddrLine3" class="da-input"
                       placeholder="Landmark or locality">
              </div>

              <div class="da-field da-field--full">
                <div class="da-row3">
                  <div class="da-field">
                    <label for="accountAddrPin">Postal Code <span class="da-ast">*</span></label>
                    <input type="text" id="accountAddrPin" class="da-input"
                           placeholder="Postal code" maxlength="10" autocomplete="postal-code">
                    <span class="da-hint" id="postalLookupStatus"></span>
                  </div>
                  <div class="da-field">
                    <label for="accountAddrCity">City <span class="da-ast">*</span></label>
                    <input type="text" id="accountAddrCity" class="da-input"
                           placeholder="City" autocomplete="address-level2">
                  </div>
                  <div class="da-field">
                    <label for="accountAddrState">State / Region</label>
                    <input type="text" id="accountAddrState" class="da-input"
                           placeholder="State or region" autocomplete="address-level1">
                  </div>
                </div>
              </div>

              <div class="da-field da-field--full">
                <label for="accountAddrExtra">Delivery Notes</label>
                <textarea id="accountAddrExtra" class="da-input da-textarea"
                          placeholder="Gate code, special instructions…"></textarea>
              </div>

              <!-- Recipient -->
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
                    <label for="accountRecipientPhone">Contact</label>
                    <input type="tel" id="accountRecipientPhone" class="da-input" placeholder="Phone number">
                  </div>
                </div>
              </div>

            </div><!-- /.da-grid -->

            <div class="da-form-err" id="daFormErr" hidden></div>

            <div class="da-actions">
              <button type="submit" class="da-btn da-btn--primary" id="addressSubmitBtn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                  <path d="M5 12l5 5L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Save Address
              </button>
              <button type="button" class="da-btn da-btn--ghost" id="cancelEditAddressBtn" hidden>Cancel</button>
              <button type="reset"  class="da-btn da-btn--ghost" id="addressFormReset">Reset</button>
            </div>

          </form>
        </div><!-- /.da-form-card -->

        <!-- ── Address list ── -->
        <div class="da-list" id="accountAddressGrid">

          <?php if (empty($addresses)): ?>
          <div class="da-empty">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
              <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5"/>
              <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            <p>No addresses saved yet. Click <strong>Add New Address</strong> to create one.</p>
          </div>
          <?php else: ?>

          <?php foreach ($addresses as $a):
            $addrId    = (int)($a->USER_ADDRESS_ID      ?? 0);
            $label     = (string)($a->LABEL              ?? 'Home');
            $company   = (string)($a->COMPANY_NAME       ?? '');
            $userName  = (string)($a->USER_NAME          ?? '');
            $phone     = (string)($a->DELIVERY_PHONE_NO  ?? '');
            $mcc       = (int)($a->MOBILE_COUNTRY_CODE   ?? 0);
            $line1     = (string)($a->ADDRESS_LINE_ONE   ?? '');
            $line2     = (string)($a->ADDRESS_LINE_TWO   ?? '');
            $landmark  = (string)($a->LANDMARK           ?? '');
            $city      = (string)($a->CITY               ?? '');
            $state     = (string)($a->STATE              ?? '');
            $zip       = (string)($a->ZIP                ?? '');
            $country   = (string)($a->COUNTRY            ?? '');
            $notes     = (string)($a->ADDRESS_NOTES      ?? '');
            $recipName = (string)($a->RECIPIENT_NAME     ?? '');
            $recipMail = (string)($a->RECIPIENT_EMAIL    ?? '');
            $recipTel  = (string)($a->RECIPIENT_CONTACT  ?? '');

            $labelLower  = strtolower($label);
            $badgeClass  = $labelLower === 'home' ? 'da-badge--home'
                         : ($labelLower === 'office' ? 'da-badge--office' : 'da-badge--other');

            $displayPhone = $mcc > 0 ? '+' . $mcc . ' ' . $phone : $phone;

            $addrParts = array_filter([$line1, $line2]);
            if ($landmark) $addrParts[] = 'Near: ' . $landmark;
            $cityLine = trim($city . ($state ? ', ' . $state : '') . ($zip ? ' - ' . $zip : ''));
            if ($cityLine)  $addrParts[] = $cityLine;
            if ($country)   $addrParts[] = $country;
            $addrString = implode(', ', $addrParts);

            $hasRecipient = $recipName || $recipMail || $recipTel;
          ?>
          <article class="da-card"
                   data-addr-id="<?= $addrId ?>"
                   data-addr-json="<?= htmlspecialchars(json_encode([
                     'id'                  => $addrId,
                     'label'               => $label,
                     'company_name'        => $company,
                     'user_name'           => $userName,
                     'delivery_phone_no'   => $phone,
                     'mobile_country_code' => $mcc,
                     'address_line_one'    => $line1,
                     'address_line_two'    => $line2,
                     'landmark'            => $landmark,
                     'city'                => $city,
                     'state'               => $state,
                     'zip'                 => $zip,
                     'country'             => $country,
                     'country_id'          => (float)($a->COUNTRY_ID ?? 0),
                     'address'             => $notes,
                     'recipient_name'      => $recipName,
                     'recipient_email'     => $recipMail,
                     'recipient_contact'   => $recipTel,
                   ]), ENT_QUOTES) ?>">

            <div class="da-card-head">
              <div class="da-badge-row">
                <span class="da-badge <?= $badgeClass ?>">
                  <?php if ($labelLower === 'home'): ?>
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 21V12h6v9" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                  <?php elseif ($labelLower === 'office'): ?>
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><rect x="2" y="7" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" stroke="currentColor" stroke-width="2"/></svg>
                  <?php else: ?>
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                  <?php endif; ?>
                  <?= htmlspecialchars($label) ?>
                </span>
              </div>
              <div class="da-menu">
                <details>
                  <summary class="da-menu-trigger" aria-label="Actions">&#8942;</summary>
                  <div class="da-menu-pop">
                    <button type="button" class="da-menu-item" data-action="edit" data-id="<?= $addrId ?>">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                      Edit
                    </button>
                    <button type="button" class="da-menu-item da-menu-item--danger" data-action="delete" data-id="<?= $addrId ?>">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                      Delete
                    </button>
                  </div>
                </details>
              </div>
            </div>

            <div class="da-card-body">
              <div class="da-card-company"><?= htmlspecialchars($company ?: $userName ?: '—') ?></div>
              <div class="da-card-meta">
                <?php if ($userName): ?>
                <span>
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
                  <?= htmlspecialchars($userName) ?>
                </span>
                <?php endif; ?>
                <?php if ($phone): ?>
                <span>
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  <?= htmlspecialchars($displayPhone) ?>
                </span>
                <?php endif; ?>
              </div>
              <?php if ($addrString): ?>
              <p class="da-card-address"><?= htmlspecialchars($addrString) ?></p>
              <?php endif; ?>
              <?php if ($notes): ?>
              <div class="da-card-extra"><?= htmlspecialchars($notes) ?></div>
              <?php endif; ?>
              <?php if ($hasRecipient): ?>
              <div class="da-card-recipient">
                <span class="da-recipient-label">Recipient</span>
                <?php if ($recipName): ?>
                <span class="da-recipient-item">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
                  <?= htmlspecialchars($recipName) ?>
                </span>
                <?php endif; ?>
                <?php if ($recipTel): ?>
                <span class="da-recipient-item">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  <?= htmlspecialchars($recipTel) ?>
                </span>
                <?php endif; ?>
                <?php if ($recipMail): ?>
                <span class="da-recipient-item">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="22 6 12 13 2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  <?= htmlspecialchars($recipMail) ?>
                </span>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>

          </article>
          <?php endforeach; ?>

          <?php endif; ?>

        </div><!-- /.da-list -->

      </section>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
