<?php
require_once __DIR__ . '/account-helpers.php';
$user = sinelec_require_login();
$currentPage = 'delivery-address';
$pageTitle = 'Delivery Address | Sinelec Technologies';
require_once __DIR__ . '/header.php';

$fullName = trim((string)($user['NAME'] ?? 'Sinelec Customer'));
$mobile = '+' . trim((string)($user['COMMUNICATION_MOBILE_NUM_ISD'] ?? '49')) . ' ' . trim((string)($user['COMMUNICATION_MOBILE_NUM'] ?? ''));
?>

<main class="account-page">
  <div class="wrap">
    <div class="account-shell">
      <?php sinelec_render_account_nav($currentPage); ?>

      <section class="account-main delivery-flat-main">
        <div class="delivery-flat-surface">
          <h1>Manage Addresses</h1>

          <button type="button" class="delivery-add-toggle" id="toggleNewAddressForm" aria-expanded="false" aria-controls="newAddressPanel">
            <span>+</span>
            <strong>ADD A NEW ADDRESS</strong>
          </button>

          <section class="delivery-new-address-panel" id="newAddressPanel" hidden>
            <form id="accountAddressForm" data-loader-text="Saving address...">
              <input type="hidden" id="accountAddrId" value="">
              <div class="delivery-form-grid">
                <div class="account-field">
                  <label for="accountAddrLabel">Address Label</label>
                  <input type="text" id="accountAddrLabel" placeholder="Home, Office, Warehouse" required>
                </div>

                <div class="account-field">
                  <label for="accountAddrName">Contact Name</label>
                  <input type="text" id="accountAddrName" value="<?= htmlspecialchars($fullName) ?>" required>
                </div>

                <div class="account-field">
                  <label for="accountAddrPhone">Contact Number</label>
                  <input type="text" id="accountAddrPhone" value="<?= htmlspecialchars($mobile) ?>" required>
                </div>

                <div class="account-field">
                  <label for="accountAddrPin">Postal Code</label>
                  <input type="text" id="accountAddrPin" placeholder="400701" required>
                </div>

                <div class="account-field account-field--full">
                  <label for="accountAddrLine">Delivery Address</label>
                  <textarea id="accountAddrLine" placeholder="Street, city, state, country" required></textarea>
                </div>

                <div class="account-field account-field--full delivery-billing-toggle">
                  <label class="delivery-check-wrap">
                    <input type="checkbox" id="billingSameCheckbox" checked>
                    <span>Billing address is same as delivery address</span>
                  </label>
                </div>

                <div class="account-field account-field--full" id="billingAddressField" hidden>
                  <label for="accountBillingLine">Billing Address</label>
                  <textarea id="accountBillingLine" placeholder="Enter billing address"></textarea>
                </div>
              </div>

              <div class="account-form-actions">
                <button type="submit" class="account-btn" id="addressSubmitBtn">Save Address</button>
                <button type="button" class="account-btn-secondary" id="cancelEditAddressBtn" hidden>Cancel Edit</button>
                <button type="reset" class="account-btn-secondary" id="addressFormReset">Reset</button>
              </div>
            </form>
          </section>

          <section class="delivery-list-box" id="accountAddressGrid"></section>
        </div>
      </section>
    </div>
  </div>
</main>

<style>
.delivery-flat-main {
  gap: 0;
}
.delivery-flat-surface {
  border: 1px solid #d7dce3;
  background: #f3f4f6;
  border-radius: 0;
  padding: 24px;
}
.delivery-flat-surface h1 {
  font-size: clamp(1.1rem, 1.6vw, 1.45rem);
  color: #242933;
  font-weight: 700;
  margin-bottom: 18px;
}
.delivery-add-toggle {
  width: 100%;
  min-height: 54px;
  border: 1px solid #d6d9de;
  border-radius: 3px;
  background: #fff;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 22px;
  color: #2b67d3;
  text-align: left;
}
.delivery-add-toggle span {
  font-size: 24px;
  line-height: 1;
  font-weight: 300;
}
.delivery-add-toggle strong {
  font-size: 12px;
  letter-spacing: .01em;
}
.delivery-new-address-panel {
  margin-top: 12px;
  border: 1px solid #d6d9de;
  border-radius: 3px;
  background: #fff;
  padding: 14px;
}
.delivery-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}
.delivery-form-grid .account-field label {
  font-size: 10px;
  color: #4f6786;
}
.delivery-form-grid .account-field input,
.delivery-form-grid .account-field textarea {
  min-height: 38px;
  border-radius: 9px;
  font-size: 11px;
}
.delivery-form-grid .account-field textarea {
  min-height: 80px;
  padding: 10px 12px;
}
.delivery-billing-toggle {
  margin-top: -1px;
}
.delivery-check-wrap {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: #26486e;
  font-weight: 600;
}
.delivery-check-wrap input {
  width: 14px;
  height: 14px;
}
.account-form-actions .account-btn,
.account-form-actions .account-btn-secondary {
  min-height: 33px;
  font-size: 11px;
}
.delivery-list-box {
  margin-top: 26px;
  border: 1px solid #d6d9de;
  border-radius: 3px;
  background: #fff;
}
.delivery-row {
  padding: 18px 24px;
  border-bottom: 1px solid #dfe3e8;
  position: relative;
}
.delivery-row:last-child {
  border-bottom: none;
}
.delivery-row-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
}
.delivery-type-wrap {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.delivery-type-badge,
.delivery-default-badge {
  min-height: 30px;
  padding: 0 12px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .02em;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
}
.delivery-type-badge {
  color: #7b7f85;
  background: #f0f0f1;
}
.delivery-default-badge {
  color: #1f5dbf;
  background: #eaf2ff;
}
.delivery-menu {
  position: relative;
}
.delivery-menu summary {
  list-style: none;
  cursor: pointer;
  color: #868b92;
  font-size: 24px;
  line-height: 1;
  width: 24px;
  text-align: center;
}
.delivery-menu summary::-webkit-details-marker {
  display: none;
}
.delivery-menu-pop {
  position: absolute;
  top: 30px;
  right: 0;
  min-width: 130px;
  border: 1px solid #d4dae2;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 12px 26px rgba(12, 31, 58, .12);
  padding: 6px;
  z-index: 8;
  display: grid;
  gap: 4px;
}
.delivery-menu-pop button {
  min-height: 30px;
  border: 1px solid #d9e2ee;
  background: #fff;
  border-radius: 7px;
  font-size: 11px;
  font-weight: 700;
  color: #23496e;
}
.delivery-menu-pop button:hover {
  border-color: #2c67c6;
  color: #2c67c6;
}
.delivery-name-line {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px 18px;
  font-size: 15px;
  color: #252a33;
  font-weight: 700;
}
.delivery-line {
  margin-top: 10px;
  font-size: 13px;
  color: #2d323c;
  line-height: 1.5;
  max-width: 92%;
}
.delivery-empty {
  padding: 22px;
  font-size: 12px;
  color: #5f748f;
}
@media (max-width: 1100px) {
  .delivery-form-grid {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 768px) {
  .delivery-flat-surface {
    padding: 14px;
  }
  .delivery-flat-surface h1 {
    font-size: 1.1rem;
    margin-bottom: 10px;
  }
  .delivery-add-toggle {
    min-height: 46px;
    padding: 0 14px;
  }
  .delivery-add-toggle span {
    font-size: 20px;
  }
  .delivery-add-toggle strong {
    font-size: 11px;
  }
  .delivery-row {
    padding: 14px;
  }
  .delivery-name-line {
    font-size: 13px;
  }
  .delivery-line {
    font-size: 12px;
    max-width: 100%;
  }
  .delivery-type-badge,
  .delivery-default-badge {
    min-height: 24px;
    font-size: 9px;
  }
  .delivery-menu summary {
    font-size: 20px;
  }
}
@media (max-width: 575px) {
  .delivery-flat-surface {
    padding: 12px;
  }
  .delivery-new-address-panel {
    padding: 12px;
  }
  .delivery-add-toggle {
    min-height: 44px;
    padding: 0 12px;
    gap: 10px;
  }
  .delivery-add-toggle strong {
    font-size: 10.5px;
  }
  .delivery-check-wrap {
    align-items: flex-start;
  }
  .account-form-actions {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
  }
  .account-form-actions .account-btn,
  .account-form-actions .account-btn-secondary {
    width: auto;
    flex: 1 1 0;
    min-height: 34px;
    font-size: 10px;
    padding: 0 8px;
  }
  .delivery-row-head {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }
  .delivery-type-wrap {
    flex-direction: row;
    align-items: center;
  }
  .delivery-menu-pop {
    right: 0;
    left: auto;
    min-width: 140px;
  }
}

@media (max-width: 480px) {
  .delivery-flat-surface {
    padding: 10px;
  }
  .delivery-flat-surface h1 {
    font-size: 1rem;
    margin-bottom: 8px;
  }
  .delivery-add-toggle {
    min-height: 40px;
    padding: 0 10px;
    gap: 8px;
  }
  .delivery-add-toggle span {
    font-size: 18px;
  }
  .delivery-add-toggle strong {
    font-size: 10px;
    letter-spacing: 0;
  }
  .delivery-new-address-panel {
    margin-top: 10px;
    padding: 10px;
  }
  .delivery-form-grid {
    gap: 8px;
  }
  .delivery-form-grid .account-field label {
    font-size: 9.5px;
  }
  .delivery-form-grid .account-field input,
  .delivery-form-grid .account-field textarea {
    min-height: 36px;
    font-size: 10.5px;
  }
  .delivery-form-grid .account-field textarea {
    min-height: 72px;
    padding: 9px 10px;
  }
  .delivery-check-wrap {
    font-size: 10px;
    gap: 6px;
  }
  .delivery-check-wrap input {
    width: 13px;
    height: 13px;
  }
  .account-form-actions .account-btn,
  .account-form-actions .account-btn-secondary {
    min-height: 32px;
    font-size: 9.5px;
    padding: 0 6px;
  }
  .delivery-list-box {
    margin-top: 14px;
  }
  .delivery-row {
    padding: 12px 10px;
  }
  .delivery-row-head {
    gap: 6px;
    justify-content: space-between;
    align-items: center;
  }
  .delivery-type-wrap {
    gap: 6px;
  }
  .delivery-type-badge,
  .delivery-default-badge {
    min-height: 22px;
    padding: 0 8px;
    font-size: 8.5px;
  }
  .delivery-menu summary {
    font-size: 18px;
    width: 20px;
  }
  .delivery-menu-pop {
    right: 0;
    left: auto;
    min-width: 118px;
    padding: 5px;
  }
  .delivery-menu-pop button {
    min-height: 28px;
    font-size: 10px;
  }
  .delivery-name-line {
    margin-top: 8px;
    gap: 6px 10px;
    font-size: 12px;
  }
  .delivery-line {
    margin-top: 7px;
    font-size: 11px;
    line-height: 1.4;
  }
  .delivery-empty {
    padding: 14px;
    font-size: 11px;
  }
}

@media (max-width: 360px) {
  .delivery-add-toggle strong {
    font-size: 9.5px;
  }
  .delivery-menu-pop {
    min-width: 108px;
  }
  .delivery-name-line {
    font-size: 11px;
  }
  .delivery-line {
    font-size: 10.5px;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const grid = document.getElementById('accountAddressGrid');
  const form = document.getElementById('accountAddressForm');
  const toggleNewAddressForm = document.getElementById('toggleNewAddressForm');
  const newAddressPanel = document.getElementById('newAddressPanel');
  const billingSameCheckbox = document.getElementById('billingSameCheckbox');
  const billingAddressField = document.getElementById('billingAddressField');
  const billingAddressInput = document.getElementById('accountBillingLine');
  const addressIdInput = document.getElementById('accountAddrId');
  const addressSubmitBtn = document.getElementById('addressSubmitBtn');
  const cancelEditAddressBtn = document.getElementById('cancelEditAddressBtn');
  const addressFormReset = document.getElementById('addressFormReset');

  if (!grid || !form || !toggleNewAddressForm || !newAddressPanel || !billingSameCheckbox || !billingAddressField || !billingAddressInput || !addressIdInput || !addressSubmitBtn || !cancelEditAddressBtn) {
    return;
  }

  const ADDRESS_KEY = 'sinelec_checkout_addresses';
  const SELECTED_KEY = 'sinelec_checkout_selected_address';
  const DUMMY_ADDRESSES = [
    {
      id: 'addr_demo_1',
      label: 'HOME',
      name: 'Demo Customer One',
      phone: '9000000001',
      line: 'Flat 12A, Sunrise Residency, Sector 10, Example City, State - 100001',
      billingSame: true,
      billingLine: 'Flat 12A, Sunrise Residency, Sector 10, Example City, State - 100001',
      isDefault: true
    },
    {
      id: 'addr_demo_2',
      label: 'OFFICE',
      name: 'Demo Customer Two',
      phone: '9000000002',
      line: 'Unit 204, Business Plaza, Ring Road, Demo Town, State - 200002',
      billingSame: true,
      billingLine: 'Unit 204, Business Plaza, Ring Road, Demo Town, State - 200002',
      isDefault: false
    },
    {
      id: 'addr_demo_3',
      label: 'WAREHOUSE',
      name: 'Demo Customer Three',
      phone: '9000000003',
      line: 'Gate 3, Storage Park, Industrial Belt, Sample Nagar, State - 300003',
      billingSame: false,
      billingLine: 'Block B, Finance Center, Main Avenue, Sample Nagar, State - 300010',
      isDefault: false
    }
  ];

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function loadAddresses() {
    try {
      const stored = JSON.parse(localStorage.getItem(ADDRESS_KEY) || '[]');
      return Array.isArray(stored) ? stored : [];
    } catch {
      return [];
    }
  }

  function saveAddresses(addresses, selected = '') {
    localStorage.setItem(ADDRESS_KEY, JSON.stringify(addresses));
    if (selected !== '') {
      localStorage.setItem(SELECTED_KEY, selected);
    }
  }

  function normalizeAddresses(addresses, selectedId = '') {
    const next = Array.isArray(addresses) ? addresses.slice() : [];
    if (!next.length) {
      localStorage.removeItem(SELECTED_KEY);
      return [];
    }

    let defaultId = selectedId || localStorage.getItem(SELECTED_KEY) || '';
    if (!defaultId || !next.some(function (a) { return a.id === defaultId; })) {
      const existingDefault = next.find(function (a) { return !!a.isDefault; });
      defaultId = existingDefault ? existingDefault.id : next[0].id;
    }

    next.forEach(function (address) {
      address.isDefault = address.id === defaultId;
    });

    localStorage.setItem(SELECTED_KEY, defaultId);
    return next;
  }

  function migrateDemoAddresses(addresses) {
    if (!Array.isArray(addresses) || !addresses.length) return addresses;

    const demoMap = {};
    DUMMY_ADDRESSES.forEach(function (item) {
      demoMap[item.id] = item;
    });

    let changed = false;
    const migrated = addresses.map(function (address) {
      if (!address || !address.id || !demoMap[address.id]) {
        return address;
      }
      changed = true;
      return Object.assign({}, address, demoMap[address.id]);
    });

    return changed ? migrated : addresses;
  }

  function setBillingFieldState() {
    const same = billingSameCheckbox.checked;
    billingAddressField.hidden = same;
    billingAddressInput.required = !same;
    if (same) {
      billingAddressInput.value = '';
    }
  }

  function clearEditMode() {
    addressIdInput.value = '';
    addressSubmitBtn.textContent = 'Save Address';
    cancelEditAddressBtn.hidden = true;
    form.setAttribute('data-loader-text', 'Saving address...');
  }

  function enterEditMode(address) {
    if (!address) return;

    document.getElementById('accountAddrLabel').value = address.label || '';
    document.getElementById('accountAddrName').value = address.name || '';
    document.getElementById('accountAddrPhone').value = address.phone || '';

    const rawLine = (address.line || '').trim();
    let pin = '';
    let deliveryOnly = rawLine;
    const match = rawLine.match(/,\s*([0-9]{4,10})$/);
    if (match) {
      pin = match[1];
      deliveryOnly = rawLine.slice(0, match.index).trim();
    }
    document.getElementById('accountAddrPin').value = pin;
    document.getElementById('accountAddrLine').value = deliveryOnly;

    const isBillingSame = address.billingSame !== false;
    billingSameCheckbox.checked = isBillingSame;
    billingAddressInput.value = isBillingSame ? '' : (address.billingLine || '');
    setBillingFieldState();

    addressIdInput.value = address.id || '';
    addressSubmitBtn.textContent = 'Update Address';
    cancelEditAddressBtn.hidden = false;
    form.setAttribute('data-loader-text', 'Updating address...');

    newAddressPanel.hidden = false;
    toggleNewAddressForm.setAttribute('aria-expanded', 'true');
    document.getElementById('accountAddrLabel').focus();
  }

  function render() {
    let current = loadAddresses();
    current = migrateDemoAddresses(current);
    let normalized = normalizeAddresses(current);
    if (!normalized.length) {
      normalized = normalizeAddresses(DUMMY_ADDRESSES.slice(), DUMMY_ADDRESSES[0].id);
      saveAddresses(normalized, DUMMY_ADDRESSES[0].id);
    } else {
      saveAddresses(normalized);
    }

    if (!normalized.length) {
      grid.innerHTML = '<div class="delivery-empty">No addresses saved yet. Use <strong>ADD A NEW ADDRESS</strong> to create one.</div>';
      return;
    }

    grid.innerHTML = normalized.map(function (address) {
      const label = escapeHtml((address.label || 'Home').toUpperCase());
      const name = escapeHtml(address.name || '');
      const phone = escapeHtml(address.phone || '');
      const line = escapeHtml(address.line || '');
      const isDefault = !!address.isDefault;

      return '\n        <article class="delivery-row">\n          <div class="delivery-row-head">\n            <div class="delivery-type-wrap">\n              <span class="delivery-type-badge">' + label + '</span>\n              ' + (isDefault ? '<span class="delivery-default-badge">Default</span>' : '') + '\n            </div>\n            <details class="delivery-menu">\n              <summary aria-label="Address actions">&#8942;</summary>\n              <div class="delivery-menu-pop">\n                <button type="button" data-address-edit="' + escapeHtml(address.id) + '">Edit</button>\n                ' + (!isDefault ? '<button type="button" data-address-default="' + escapeHtml(address.id) + '">Set Default</button>' : '') + '\n                <button type="button" data-address-delete="' + escapeHtml(address.id) + '">Delete</button>\n              </div>\n            </details>\n          </div>\n          <div class="delivery-name-line"><span>' + name + '</span><span>' + phone + '</span></div>\n          <p class="delivery-line">' + line + '</p>\n        </article>\n      ';
    }).join('');
  }

  toggleNewAddressForm.addEventListener('click', function () {
    const shouldOpen = newAddressPanel.hidden;
    newAddressPanel.hidden = !shouldOpen;
    toggleNewAddressForm.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
    if (shouldOpen && !addressIdInput.value) {
      form.reset();
      billingSameCheckbox.checked = true;
      setBillingFieldState();
      clearEditMode();
    }
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    const label = (document.getElementById('accountAddrLabel')?.value || '').trim();
    const name = (document.getElementById('accountAddrName')?.value || '').trim();
    const phone = (document.getElementById('accountAddrPhone')?.value || '').trim();
    const pin = (document.getElementById('accountAddrPin')?.value || '').trim();
    const deliveryLine = (document.getElementById('accountAddrLine')?.value || '').trim();
    const billingSame = billingSameCheckbox.checked;
    const billingLine = (billingAddressInput.value || '').trim();
    const editingId = (addressIdInput.value || '').trim();

    if (!label || !name || !phone || !pin || !deliveryLine) {
      toast('Please fill all required address fields.', 'warn');
      return;
    }

    if (!billingSame && billingLine === '') {
      toast('Please enter billing address or select same as delivery.', 'warn');
      return;
    }

    let addresses = normalizeAddresses(loadAddresses());
    const fullDeliveryLine = deliveryLine + ', ' + pin;
    const payload = {
      id: editingId || ('addr_' + Date.now()),
      label: label,
      name: name,
      phone: phone,
      line: fullDeliveryLine,
      billingSame: billingSame,
      billingLine: billingSame ? fullDeliveryLine : billingLine
    };

    let selectedId = localStorage.getItem(SELECTED_KEY) || '';
    if (editingId) {
      addresses = addresses.map(function (item) {
        if (item.id !== editingId) return item;
        return Object.assign({}, item, payload);
      });
      if (!selectedId && addresses.length) {
        selectedId = addresses[0].id;
      }
      const normalizedEdited = normalizeAddresses(addresses, selectedId);
      saveAddresses(normalizedEdited, selectedId);
      toast('Address updated successfully.', 'ok');
    } else {
      payload.isDefault = addresses.length === 0;
      addresses.push(payload);
      const normalizedNew = normalizeAddresses(addresses, payload.id);
      saveAddresses(normalizedNew, payload.id);
      toast('Address saved successfully.', 'ok');
    }

    form.reset();
    billingSameCheckbox.checked = true;
    setBillingFieldState();
    clearEditMode();
    newAddressPanel.hidden = true;
    toggleNewAddressForm.setAttribute('aria-expanded', 'false');
    render();
  });

  addressFormReset?.addEventListener('click', function () {
    setTimeout(function () {
      billingSameCheckbox.checked = true;
      setBillingFieldState();
      clearEditMode();
    }, 0);
  });

  cancelEditAddressBtn.addEventListener('click', function () {
    form.reset();
    billingSameCheckbox.checked = true;
    setBillingFieldState();
    clearEditMode();
    newAddressPanel.hidden = true;
    toggleNewAddressForm.setAttribute('aria-expanded', 'false');
  });

  grid.addEventListener('click', function (event) {
    const editBtn = event.target.closest('[data-address-edit]');
    const setDefaultBtn = event.target.closest('[data-address-default]');
    const deleteBtn = event.target.closest('[data-address-delete]');
    let addresses = normalizeAddresses(loadAddresses());

    if (editBtn) {
      const id = editBtn.getAttribute('data-address-edit') || '';
      if (!id) return;
      const targetAddress = addresses.find(function (item) { return item.id === id; });
      enterEditMode(targetAddress || null);
      return;
    }

    if (setDefaultBtn) {
      const id = setDefaultBtn.getAttribute('data-address-default') || '';
      if (!id) return;
      addresses = normalizeAddresses(addresses, id);
      saveAddresses(addresses, id);
      toast('Default address updated.', 'ok');
      render();
      return;
    }

    if (deleteBtn) {
      const id = deleteBtn.getAttribute('data-address-delete') || '';
      if (!id) return;
      addresses = addresses.filter(function (address) { return address.id !== id; });
      const nextDefault = addresses[0]?.id || '';
      addresses = normalizeAddresses(addresses, nextDefault);
      saveAddresses(addresses, nextDefault);
      toast('Address removed.', 'warn');
      render();
    }
  });

  billingSameCheckbox.addEventListener('change', setBillingFieldState);
  setBillingFieldState();
  clearEditMode();
  render();
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
