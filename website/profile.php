<?php
require_once __DIR__ . '/account-helpers.php';
$user = sinelec_require_login();
$currentPage = 'profile';
$pageTitle = 'My Profile | Sinelec Technologies';
require_once __DIR__ . '/header.php';

$fullName = trim((string)($user['NAME'] ?? ''));
$emailId = trim((string)($user['EMAIL'] ?? ''));
$phoneCode = trim((string)($user['COMMUNICATION_MOBILE_NUM_ISD'] ?? '49'));
$mobileNumber = trim((string)($user['COMMUNICATION_MOBILE_NUM'] ?? ''));
$companyName = trim((string)($user['COMPANY_NAME'] ?? ''));
$designation = trim((string)($user['DESIGNATION'] ?? ''));
$avatarChar = strtoupper(substr(sinelec_account_first_name($user), 0, 1));
?>

<main class="account-page">
  <div class="wrap">
    <div class="account-shell">
      <?php sinelec_render_account_nav($currentPage); ?>

      <section class="account-main">
        <article class="account-panel account-hero profile-hero">
          <div>
            <span class="account-eyebrow">Profile</span>
            <h1><?= htmlspecialchars($fullName !== '' ? $fullName : 'Sinelec Customer') ?></h1>
            <p>Manage your account profile details for orders, quotes, and delivery communication.</p>
          </div>
          <div class="account-avatar"><?= htmlspecialchars($avatarChar !== '' ? $avatarChar : 'U') ?></div>
        </article>

        <article class="account-panel profile-editor-panel">
          <div class="account-section-head">
            <div>
              <h2>Profile Details</h2>
              <p>Click the edit icon to update your details. Email ID is locked and cannot be changed.</p>
            </div>
          </div>

          <form class="profile-editor-form" method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateProfile') ?>" data-loader-text="Updating profile...">
            <div class="profile-editor-grid">
              <div class="profile-field">
                <label for="profileNameInput">Name</label>
                <div class="profile-input-wrap">
                  <input type="text" id="profileNameInput" name="profile_name" class="profile-input" value="<?= htmlspecialchars($fullName) ?>" readonly required>
                  <button type="button" class="profile-edit-btn" data-target="profileNameInput" aria-label="Edit name" title="Edit name">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                  </button>
                </div>
              </div>

              <div class="profile-field">
                <label for="profileEmailInput">Email ID</label>
                <div class="profile-input-wrap">
                  <input type="email" id="profileEmailInput" class="profile-input is-locked" value="<?= htmlspecialchars($emailId) ?>" readonly>
                  <span class="profile-lock-badge" aria-label="Email locked" title="Email cannot be edited">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0 1 10 0v3"/></svg>
                  </span>
                </div>
              </div>

              <div class="profile-field">
                <label for="profilePhoneCodeInput">County Code</label>
                <div class="profile-input-wrap">
                  <select id="profilePhoneCodeInput" name="profile_phone_code" class="profile-input profile-select" disabled required>
                    <option value="49" <?= $phoneCode === '49' ? 'selected' : '' ?>>+49</option>
                    <option value="91" <?= $phoneCode === '91' ? 'selected' : '' ?>>+91</option>
                    <option value="1" <?= $phoneCode === '1' ? 'selected' : '' ?>>+1</option>
                    <option value="44" <?= $phoneCode === '44' ? 'selected' : '' ?>>+44</option>
                    <option value="33" <?= $phoneCode === '33' ? 'selected' : '' ?>>+33</option>
                    <option value="39" <?= $phoneCode === '39' ? 'selected' : '' ?>>+39</option>
                    <option value="34" <?= $phoneCode === '34' ? 'selected' : '' ?>>+34</option>
                    <option value="31" <?= $phoneCode === '31' ? 'selected' : '' ?>>+31</option>
                  </select>
                  <button type="button" class="profile-edit-btn" data-target="profilePhoneCodeInput" aria-label="Edit county code" title="Edit county code">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                  </button>
                </div>
              </div>

              <div class="profile-field">
                <label for="profileNumberInput">Mobile Number</label>
                <div class="profile-input-wrap">
                  <input type="text" id="profileNumberInput" name="profile_number" class="profile-input" value="<?= htmlspecialchars($mobileNumber) ?>" readonly required>
                  <button type="button" class="profile-edit-btn" data-target="profileNumberInput" aria-label="Edit number" title="Edit number">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                  </button>
                </div>
              </div>

              <div class="profile-field">
                <label for="profileCompanyInput">Company</label>
                <div class="profile-input-wrap">
                  <input type="text" id="profileCompanyInput" name="profile_company" class="profile-input" value="<?= htmlspecialchars($companyName) ?>" readonly>
                  <button type="button" class="profile-edit-btn" data-target="profileCompanyInput" aria-label="Edit company" title="Edit company">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                  </button>
                </div>
              </div>

              <div class="profile-field">
                <label for="profileDesignationInput">Designation</label>
                <div class="profile-input-wrap">
                  <input type="text" id="profileDesignationInput" name="profile_designation" class="profile-input" value="<?= htmlspecialchars($designation) ?>" readonly>
                  <button type="button" class="profile-edit-btn" data-target="profileDesignationInput" aria-label="Edit designation" title="Edit designation">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                  </button>
                </div>
              </div>
            </div>

            <div class="account-form-actions profile-form-actions">
              <button type="submit" class="account-btn">Update Profile</button>
              <button type="button" id="profileResetBtn" class="account-btn-secondary">Reset</button>
            </div>
          </form>
        </article>
      </section>
    </div>
  </div>
</main>

<style>
.account-main {
  gap: 0;
}
.profile-hero {
  padding: 16px 18px;
  min-height: auto;
  gap: 12px;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}
.profile-editor-panel {
  border-top-left-radius: 0;
  border-top-right-radius: 0;
  padding: 0 24px 24px;
}
.profile-editor-panel .account-section-head {
  margin: 0 0 12px;
  padding-top: 10px;
}
.profile-hero .account-eyebrow {
  min-height: 22px;
  padding: 0 10px;
  font-size: 10px;
}
.profile-hero h1 {
  margin-top: 8px;
  font-size: clamp(1.2rem, 1.7vw, 1.55rem);
  line-height: 1.2;
}
.profile-hero p {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.5;
  max-width: 520px;
}
.profile-hero .account-avatar {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  font-size: 22px;
}
.profile-form-actions {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 8px;
}
.profile-form-actions .account-btn,
.profile-form-actions .account-btn-secondary {
  flex: 1 1 0;
  min-width: 0;
}
@media (max-width: 768px) {
  .account-main {
    gap: 0;
  }
  .profile-hero {
    padding: 14px;
  }
  .profile-editor-panel {
    padding: 0 16px 16px;
  }
  .profile-editor-panel .account-section-head {
    padding-top: 8px;
  }
  .profile-hero h1 {
    font-size: 1.2rem;
  }
  .profile-hero p {
    font-size: 11px;
  }
  .profile-form-actions {
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    gap: 6px;
  }
  .profile-form-actions .account-btn,
  .profile-form-actions .account-btn-secondary {
    width: auto;
    min-height: 36px;
    font-size: 11px;
    padding-inline: 8px;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('.profile-editor-form');
  if (!form) return;

  const editableControls = Array.from(form.querySelectorAll('.profile-input:not(.is-locked)'));
  editableControls.forEach(function (control) {
    control.dataset.initialValue = control.value;
  });

  form.querySelectorAll('.profile-edit-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const targetId = btn.getAttribute('data-target');
      const control = targetId ? document.getElementById(targetId) : null;
      if (!control) return;

      if (control instanceof HTMLSelectElement) {
        control.disabled = false;
        control.classList.add('is-editing');
        control.focus();
        return;
      }

      if (control instanceof HTMLInputElement) {
        control.readOnly = false;
        control.classList.add('is-editing');
        control.focus();
        const len = control.value.length;
        control.setSelectionRange(len, len);
      }
    });
  });

  editableControls.forEach(function (control) {
    control.addEventListener('blur', function () {
      if (control instanceof HTMLSelectElement) {
        control.disabled = true;
      }
      if (control instanceof HTMLInputElement) {
        control.readOnly = true;
      }
      control.classList.remove('is-editing');
    });
  });

  form.addEventListener('submit', function () {
    form.querySelectorAll('.profile-select').forEach(function (selectEl) {
      if (selectEl instanceof HTMLSelectElement) {
        selectEl.disabled = false;
      }
    });
  });

  const resetBtn = document.getElementById('profileResetBtn');
  resetBtn?.addEventListener('click', function () {
    editableControls.forEach(function (control) {
      control.value = control.dataset.initialValue || '';
      if (control instanceof HTMLSelectElement) {
        control.disabled = true;
      }
      if (control instanceof HTMLInputElement) {
        control.readOnly = true;
      }
      control.classList.remove('is-editing');
    });
  });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
