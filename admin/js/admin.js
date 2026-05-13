/* ================================================================
   Sinelec Admin Panel — Main JavaScript
   ================================================================ */

/* ── Core shell: sidebar, dropdown, toast, modals ── */
(function () {
  var shell = document.getElementById('shell');
  var KEY   = 'sn_sb_col';

  if (localStorage.getItem(KEY) === '1') shell.classList.add('sb-col');

  function toggleCollapse() {
    var c = shell.classList.toggle('sb-col');
    localStorage.setItem(KEY, c ? '1' : '0');
  }

  var hdToggle = document.getElementById('hdToggle');
  if (hdToggle) {
    hdToggle.addEventListener('click', function () {
      if (window.innerWidth <= 768) { shell.classList.toggle('mob-open'); }
      else { toggleCollapse(); }
    });
  }

  var sbBtn = document.getElementById('sbColBtn');
  if (sbBtn) sbBtn.addEventListener('click', toggleCollapse);

  window.closeMob = function () { shell.classList.remove('mob-open'); };

  /* user dropdown */
  var uBtn  = document.getElementById('userBtn');
  var uDrop = document.getElementById('userDrop');
  if (uBtn && uDrop) {
    uBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var o = uDrop.classList.toggle('open');
      uBtn.setAttribute('aria-expanded', o);
    });
    document.addEventListener('click', function () {
      uDrop.classList.remove('open');
      uBtn.setAttribute('aria-expanded', 'false');
    });
    uDrop.addEventListener('click', function (e) { e.stopPropagation(); });
  }

  /* auto-dismiss toast */
  var ts = document.getElementById('toastStack');
  if (ts) {
    setTimeout(function () {
      ts.style.transition = 'opacity .35s';
      ts.style.opacity    = '0';
      setTimeout(function () { ts.remove(); }, 360);
    }, 4200);
  }

  /* generic modal helpers */
  window.openModal  = function (id) { var el = document.getElementById(id); if (el) el.classList.add('open'); };
  window.closeModal = function (id) { var el = document.getElementById(id); if (el) el.classList.remove('open'); };
  document.querySelectorAll('.modal-overlay').forEach(function (o) {
    o.addEventListener('click', function (e) { if (e.target === o) o.classList.remove('open'); });
  });
})();


/* ── Eye toggle (password inputs with .pw-eye) ── */
document.querySelectorAll('.pw-eye').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var id    = btn.getAttribute('data-target');
    var input = document.getElementById(id);
    if (!input) return;
    var isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.querySelector('.eye-open').style.display = isPass ? 'none' : '';
    btn.querySelector('.eye-shut').style.display = isPass ? ''     : 'none';
  });
});


/* ── Password strength checker (change-password page) ── */
var strengthMeter = document.getElementById('strengthMeter');
var strBars       = ['sb1','sb2','sb3','sb4'].map(function(id){ return document.getElementById(id); });
var strLabel      = document.getElementById('strLabel');
var strTip        = document.getElementById('strTip');
var rLen = document.getElementById('r_len');
var rLet = document.getElementById('r_let');
var rNum = document.getElementById('r_num');
var rSym = document.getElementById('r_sym');

function checkStrength(val) {
  if (!strengthMeter) return;
  if (val.length === 0) { strengthMeter.style.display = 'none'; resetRules(); return; }
  strengthMeter.style.display = 'block';

  var hasLen = val.length >= 8;
  var hasLet = /[A-Za-z]/.test(val);
  var hasNum = /[0-9]/.test(val);
  var hasSym = /[^A-Za-z0-9]/.test(val);
  var hasUpp = /[A-Z]/.test(val);
  var hasLow = /[a-z]/.test(val);

  setRule(rLen, hasLen);
  setRule(rLet, hasLet);
  setRule(rNum, hasNum);
  setRule(rSym, hasSym);

  var score = 0;
  if (hasLen) score++;
  if (hasLet) score++;
  if (hasNum) score++;
  if (hasSym) score++;
  if (val.length >= 12) score++;
  if (hasUpp && hasLow) score++;

  strBars.forEach(function (b) { if (b) b.style.background = '#e2e8f0'; });

  if (score <= 2) {
    if (strBars[0]) strBars[0].style.background = '#ef4444';
    if (strLabel) { strLabel.textContent = 'Weak';   strLabel.style.color = '#ef4444'; }
    if (strTip)   strTip.textContent = '— Add numbers and special characters.';
  } else if (score <= 4) {
    [0,1,2].forEach(function(i){ if (strBars[i]) strBars[i].style.background = '#f59e0b'; });
    if (strLabel) { strLabel.textContent = 'Medium'; strLabel.style.color = '#f59e0b'; }
    if (strTip)   strTip.textContent = '— Try mixing uppercase & symbols.';
  } else {
    strBars.forEach(function (b) { if (b) b.style.background = '#22c55e'; });
    if (strLabel) { strLabel.textContent = 'Strong'; strLabel.style.color = '#22c55e'; }
    if (strTip)   strTip.textContent = '— Great password!';
  }
}

function setRule(el, pass) {
  if (!el) return;
  el.classList.toggle('ok', pass);
  var ico = el.querySelector('svg');
  if (!ico) return;
  ico.innerHTML = pass
    ? '<polyline points="20 6 9 17 4 12" stroke-width="2.5"/>'
    : '<circle cx="12" cy="12" r="10"/>';
}

function resetRules() {
  [rLen, rLet, rNum, rSym].forEach(function (r) {
    if (!r) return;
    r.classList.remove('ok');
    var ico = r.querySelector('svg');
    if (ico) ico.innerHTML = '<circle cx="12" cy="12" r="10"/>';
  });
}

/* match check */
var matchMsg = document.getElementById('matchMsg');
function checkMatch() {
  var newEl  = document.getElementById('cp_new');
  var confEl = document.getElementById('cp_confirm');
  if (!matchMsg || !newEl || !confEl || confEl.value.length === 0) {
    if (matchMsg) matchMsg.style.display = 'none';
    return;
  }
  matchMsg.style.display = 'block';
  if (newEl.value === confEl.value) {
    matchMsg.textContent  = '✓ Passwords match';
    matchMsg.style.color  = '#16a34a';
  } else {
    matchMsg.textContent  = '✗ Passwords do not match';
    matchMsg.style.color  = '#ef4444';
  }
}


/* ── Profile edit form ── */
(function () {
  var form = document.getElementById('profileForm');
  if (!form) return;

  var editables = Array.from(form.querySelectorAll('.prof-input:not(.is-locked)'));
  editables.forEach(function (el) { el.dataset.init = el.value; });

  form.querySelectorAll('.prof-edit-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-target');
      var el = id ? document.getElementById(id) : null;
      if (!el) return;
      if (el.tagName === 'SELECT') {
        el.disabled = false; el.classList.add('is-editing'); el.focus(); return;
      }
      el.readOnly = false; el.classList.add('is-editing'); el.focus();
      el.setSelectionRange(el.value.length, el.value.length);
    });
  });

  editables.forEach(function (el) {
    el.addEventListener('blur', function () {
      if (el.tagName === 'SELECT') el.disabled = true;
      else el.readOnly = true;
      el.classList.remove('is-editing');
    });
  });

  var resetBtn = document.getElementById('resetBtn');
  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      editables.forEach(function (el) {
        el.value = el.dataset.init || '';
        if (el.tagName === 'SELECT') el.disabled = true;
        else el.readOnly = true;
        el.classList.remove('is-editing');
      });
    });
  }

  form.addEventListener('submit', function () {
    form.querySelectorAll('select.prof-input').forEach(function (s) { s.disabled = false; });
  });
})();


/* ── Categories page ── */
function openEditModal(id, name, parentId, prio, desc, ext) {
  document.getElementById('edit_cat_id').value          = id;
  document.getElementById('edit_cat_name').value        = name;
  document.getElementById('edit_cat_prio').value        = prio;
  document.getElementById('edit_cat_desc').value        = desc;
  document.getElementById('edit_existing_ext').value    = ext;
  var sel = document.getElementById('edit_cat_parent');
  if (sel) {
    for (var i = 0; i < sel.options.length; i++) {
      sel.options[i].selected = (parseInt(sel.options[i].value) === parentId);
    }
  }
  var prev = document.getElementById('edit_img_preview');
  if (prev) {
    if (ext) {
      prev.style.display = 'block';
      prev.innerHTML = '<img src="../assets/uploads/categories/' + id + '.' + ext + '" style="height:60px;border-radius:6px;border:1px solid #e2e8f0;" onerror="this.parentNode.style.display=\'none\'">';
    } else { prev.style.display = 'none'; }
  }
  openModal('editModal');
}

function confirmDelete(id, name) {
  document.getElementById('del_cat_id').value             = id;
  document.getElementById('del_cat_name').textContent     = name;
  openModal('deleteModal');
}


/* ── Products page ── */
function confirmDeleteProduct(id, name) {
  var idEl   = document.getElementById('del_prod_id');
  var nameEl = document.getElementById('del_prod_name');
  if (idEl)   idEl.value             = id;
  if (nameEl) nameEl.textContent     = name;
  openModal('delProdModal');
}


/* ── Orders page ── */
function openOrderModal(id, num, currentStatus) {
  var idEl  = document.getElementById('modal_order_id');
  var numEl = document.getElementById('modal_onum');
  var sel   = document.getElementById('modal_status');
  if (idEl)  idEl.value         = id;
  if (numEl) numEl.textContent  = num;
  if (sel)   sel.value          = '';
  toggleDispatch('');
  openModal('statusModal');
}

function toggleDispatch(val) {
  var el = document.getElementById('dispatchFields');
  if (el) el.style.display = (val === 'Dispatched') ? 'block' : 'none';
}


/* ── Orders History page ── */
function viewOrderDetail(orderId) {
  var body = document.getElementById('detailBody');
  if (body) body.innerHTML = '<div class="dt-empty">Loading…</div>';
  openModal('detailModal');
  if (!body) return;
  fetch('ajax/order_detail.php?order_id=' + orderId)
    .then(function (r) { return r.text(); })
    .then(function (html) { body.innerHTML = html; })
    .catch(function () { body.innerHTML = '<p style="color:#dc2626;padding:16px;">Failed to load order details.</p>'; });
}


/* ── FAQ page ── */
function openEditFAQ(id, q, a, ord) {
  var set = function(elId, val){ var el = document.getElementById(elId); if(el) el.value = val; };
  set('edit_faq_id',  id);
  set('edit_faq_q',   q);
  set('edit_faq_a',   a);
  set('edit_faq_ord', ord);
  openModal('editModal');
}

function confirmDelFAQ(id) {
  var el = document.getElementById('del_faq_id');
  if (el) el.value = id;
  openModal('deleteModal');
}


/* ── News page ── */
function openEditNews(id, title, flag, date, desc, imgExt, docExt) {
  var set = function(elId, val){ var el = document.getElementById(elId); if(el) el.value = val; };
  set('edit_news_id',      id);
  set('edit_news_title',   title);
  set('edit_news_date',    date);
  set('edit_news_desc',    desc);
  set('edit_news_img_ext', imgExt);
  set('edit_news_doc_ext', docExt);
  var sel = document.getElementById('edit_news_flag');
  if (sel) {
    for (var i = 0; i < sel.options.length; i++) {
      sel.options[i].selected = (sel.options[i].value === flag);
    }
  }
  openModal('editModal');
}

function confirmDelNews(id) {
  var el = document.getElementById('del_news_id');
  if (el) el.value = id;
  openModal('deleteModal');
}


/* ── Jobs page ── */
function openEditJob(id, pos, prio, loc, desc, status) {
  var set = function(elId, val){ var el = document.getElementById(elId); if(el) el.value = val; };
  set('edit_job_id',   id);
  set('edit_job_pos',  pos);
  set('edit_job_prio', prio);
  set('edit_job_loc',  loc);
  set('edit_job_desc', desc);
  var sel = document.getElementById('edit_job_status');
  if (sel) {
    for (var i = 0; i < sel.options.length; i++) {
      sel.options[i].selected = (sel.options[i].value === status);
    }
  }
  openModal('editModal');
}

function confirmDelJob(id, appCount) {
  var el  = document.getElementById('del_job_id');
  var msg = document.getElementById('del_job_msg');
  var btn = document.getElementById('del_job_btn');
  if (el) el.value = id;
  if (appCount > 0) {
    if (msg) msg.innerHTML = '<span style="color:#dc2626;">This post has <strong>' + appCount + '</strong> applicant(s). You must delete them first before deleting this post.</span>';
    if (btn) { btn.disabled = true; btn.style.opacity = '0.5'; }
  } else {
    if (msg) msg.textContent = 'Are you sure you want to delete this job post?';
    if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
  }
  openModal('deleteModal');
}


/* ── Applicants page ── */
function confirmDelApp(id, name) {
  var idEl   = document.getElementById('del_app_id');
  var nameEl = document.getElementById('del_app_name');
  if (idEl)   idEl.value        = id;
  if (nameEl) nameEl.textContent = name;
  openModal('deleteModal');
}


/* ── Purchase page ── */
function confirmDeletePurchase(id) {
  var el = document.getElementById('del_pp_id');
  if (el) el.value = id;
  openModal('deleteModal');
}
