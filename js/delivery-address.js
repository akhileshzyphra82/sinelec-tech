(function () {
  'use strict';

  var AJAX_URL = 'ajax/address';

  /* ── DOM refs ── */
  var grid         = document.getElementById('accountAddressGrid');
  var form         = document.getElementById('accountAddressForm');
  var toggleBtn    = document.getElementById('toggleNewAddressForm');
  var panel        = document.getElementById('newAddressPanel');
  var addrIdInp    = document.getElementById('accountAddrId');
  var submitBtn    = document.getElementById('addressSubmitBtn');
  var cancelBtn    = document.getElementById('cancelEditAddressBtn');
  var resetBtn     = document.getElementById('addressFormReset');
  var postalInp    = document.getElementById('accountAddrPin');
  var postalHint   = document.getElementById('postalLookupStatus');
  var formTitle    = document.getElementById('daFormCardTitle');
  var formErr      = document.getElementById('daFormErr');
  var countrySel   = document.getElementById('accountAddrCountry');
  var countryIdInp = document.getElementById('accountAddrCountryId');

  if (!grid || !form) return;

  /* ── Utilities ── */
  function fld(id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; }
  function set(id, v) { var el = document.getElementById(id); if (el) el.value = (v == null ? '' : v); }
  function showErr(msg) { if (formErr) { formErr.textContent = msg; formErr.hidden = !msg; } }

  /* ── Country select ── */
  if (countrySel) {
    countrySel.addEventListener('change', function () {
      var opt = this.options[this.selectedIndex];
      if (countryIdInp) countryIdInp.value = opt ? (opt.dataset.cid || '0') : '0';
    });
  }
  function setCountry(name, cid) {
    if (!countrySel) return;
    var opts   = countrySel.options;
    var cidNum = parseFloat(cid) || 0;
    var cidIdx  = -1;
    var nameIdx = -1;

    for (var i = 1; i < opts.length; i++) { /* skip index 0 = placeholder */
      if (cidNum > 0 && parseFloat(opts[i].dataset.cid) === cidNum) {
        cidIdx = i;
        break; /* exact ID match — stop here */
      }
      if (nameIdx === -1 && opts[i].value === name) {
        nameIdx = i; /* remember first name match, keep scanning for cid */
      }
    }

    var idx = cidIdx >= 0 ? cidIdx : nameIdx; /* cid takes priority */
    if (idx >= 0) {
      countrySel.selectedIndex = idx;
      if (countryIdInp) countryIdInp.value = opts[idx].dataset.cid || '0';
    } else {
      countrySel.selectedIndex = 0;
      if (countryIdInp) countryIdInp.value = '0';
    }
  }

  /* ── Label tabs ── */
  var tabs = document.querySelectorAll('.da-tab');
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('is-active'); });
      this.classList.add('is-active');
      document.getElementById('accountAddrLabel').value = this.dataset.tabVal;
    });
  });
  function setActiveTab(val) {
    tabs.forEach(function (t) { t.classList.toggle('is-active', t.dataset.tabVal === val); });
    document.getElementById('accountAddrLabel').value = val || 'Home';
  }

  /* ── Postal code auto-fill ── */
  var postalTimer = null;
  function setHint(type, msg) {
    if (!postalHint) return;
    postalHint.textContent = msg;
    postalHint.className = 'da-hint' + (type ? ' is-' + type : '');
  }
  if (postalInp) {
    postalInp.addEventListener('input', function () {
      clearTimeout(postalTimer);
      setHint('', '');
      var v = this.value.trim();
      if (v.length < 4) return;
      postalTimer = setTimeout(function () {
        setHint('loading', 'Looking up location…');
        fetch(
          'https://nominatim.openstreetmap.org/search?postalcode=' + encodeURIComponent(v) +
          '&format=json&addressdetails=1&limit=1',
          { headers: { 'Accept-Language': 'en' } }
        )
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data || !data.length) { setHint('warn', 'Postal code not found — fill manually'); return; }
          var a = data[0].address || {};
          var city    = a.city || a.town || a.village || a.county || '';
          var state   = a.state || '';
          var country = a.country || '';
          if (city)    set('accountAddrCity', city);
          if (state)   set('accountAddrState', state);
          if (country) setCountry(country, 0);
          setHint('ok', '✓ City, state and country auto-filled');
        })
        .catch(function () { setHint('warn', 'Lookup failed — fill manually'); });
      }, 600);
    });
  }

  /* ── Panel open / close ── */
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

  toggleBtn && toggleBtn.addEventListener('click', function () {
    if (!panel.hidden) { closePanel(); return; }
    form.reset(); clearEdit(); showErr(''); openPanel('New Address');
  });

  /* ── Edit mode ── */
  function clearEdit() {
    addrIdInp.value = '';
    submitBtn.innerHTML =
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="none">' +
      '<path d="M5 12l5 5L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>' +
      '</svg> Save Address';
    cancelBtn.hidden = true;
    setHint('', '');
    setActiveTab('Home');
    if (countrySel)   countrySel.selectedIndex = 0;
    if (countryIdInp) countryIdInp.value = '0';
  }

  function enterEdit(a) {
    setActiveTab(a.label || 'Home');
    setCountry(a.country, a.country_id);
    set('accountAddrFullName',   a.company_name);
    set('accountAddrName',       a.user_name);
    set('accountAddrPhone',      a.delivery_phone_no);
    set('accountAddrLine1',      a.address_line_one);
    set('accountAddrLine2',      a.address_line_two);
    set('accountAddrLine3',      a.landmark);
    set('accountAddrPin',        a.zip);
    set('accountAddrCity',       a.city);
    set('accountAddrState',      a.state);
    set('accountAddrExtra',      a.address);
    set('accountRecipientName',  a.recipient_name);
    set('accountRecipientEmail', a.recipient_email);
    set('accountRecipientPhone', a.recipient_contact);
    addrIdInp.value = a.id;
    submitBtn.innerHTML =
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="none">' +
      '<path d="M5 12l5 5L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>' +
      '</svg> Update Address';
    cancelBtn.hidden = false;
    setHint('', ''); showErr('');
    openPanel('Edit Address');
  }

  /* ── AJAX ── */
  function ajaxPost(data, cb) {
    var fd = new FormData();
    Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    fetch(AJAX_URL, { method: 'POST', body: fd })
      .then(function (r) {
        return r.text().then(function (txt) {
          try { return JSON.parse(txt); }
          catch (e) {
            console.error('address.php non-JSON response:', txt);
            return { ok: false, msg: 'Server error. Check console for details.' };
          }
        });
      })
      .then(cb)
      .catch(function (err) {
        console.error('address AJAX network error:', err);
        if (typeof toast === 'function') toast('Network error. Please try again.', 'warn');
      });
  }

  /* ── Form submit (save / update) ── */
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    showErr('');
    var country = fld('accountAddrCountry');
    var line1   = fld('accountAddrLine1');
    var zip     = fld('accountAddrPin');
    var city    = fld('accountAddrCity');
    if (!country || !fld('accountAddrFullName') || !line1 || !zip || !city) {
      showErr('Please fill all required fields marked with *.'); return;
    }
    var editId  = addrIdInp.value.trim();
    var payload = {
      action:               editId ? 'update' : 'save',
      user_address_id:      editId || '',
      label:                document.getElementById('accountAddrLabel').value || 'Home',
      company_name:         fld('accountAddrFullName'),
      user_name:            fld('accountAddrName'),
      delivery_phone_no:    fld('accountAddrPhone'),
      mobile_country_code:  0,
      address_line_one:     line1,
      address_line_two:     fld('accountAddrLine2'),
      landmark:             fld('accountAddrLine3'),
      zip:                  zip,
      city:                 city,
      state:                fld('accountAddrState'),
      country:              country,
      country_id:           countryIdInp ? countryIdInp.value : '0',
      address:              fld('accountAddrExtra'),
      recipient_name:       fld('accountRecipientName'),
      recipient_email:      fld('accountRecipientEmail'),
      recipient_contact:    fld('accountRecipientPhone')
    };
    submitBtn.disabled = true;
    ajaxPost(payload, function (res) {
      submitBtn.disabled = false;
      if (!res.ok) { showErr(res.msg || 'Save failed.'); return; }
      window.location.reload();
    });
  });

  /* ── Reset / Cancel ── */
  resetBtn  && resetBtn.addEventListener('click',  function () { setTimeout(function () { clearEdit(); showErr(''); }, 0); });
  cancelBtn && cancelBtn.addEventListener('click', function () { form.reset(); clearEdit(); closePanel(); showErr(''); });

  /* ── Card actions (edit / delete) via delegation ── */
  grid.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-action]');
    if (!btn) return;

    var action = btn.dataset.action;
    var id     = parseInt(btn.dataset.id, 10);
    var card   = btn.closest('[data-addr-json]');

    if (action === 'edit' && card) {
      try {
        var a = JSON.parse(card.dataset.addrJson);
        enterEdit(a);
      } catch (err) { console.error('Could not parse address data', err); }
      return;
    }

    if (action === 'delete') {
      if (!confirm('Delete this address?')) return;
      ajaxPost({ action: 'delete', user_address_id: id }, function (res) {
        if (!res.ok) {
          if (typeof toast === 'function') toast(res.msg || 'Delete failed.', 'warn');
          return;
        }
        window.location.reload();
      });
    }
  });

  /* ── Init ── */
  clearEdit();

}());
