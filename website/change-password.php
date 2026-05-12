<?php
require_once __DIR__ . '/account-helpers.php';
$user = sinelec_require_login();
$currentPage = 'change-password';
$pageTitle = 'Change Password | Sinelec Technologies';
require_once __DIR__ . '/header.php';
?>

<main class="account-page">
  <div class="wrap">
    <div class="account-shell">
      <?php sinelec_render_account_nav($currentPage); ?>

      <section class="account-main">
        <article class="account-panel password-panel">
          <div class="password-head">
            <span class="account-eyebrow">Security</span>
            <h1>Change Password</h1>
            <p>Use at least 8 characters with letters, numbers, and a special character.</p>
          </div>

          <form id="changePasswordForm" method="POST" action="service?urlstring=<?= EncryptURL('action=ChangePassword') ?>" data-loader-text="Updating password...">
            <div class="password-form-grid">
              <div class="account-field account-field--full">
                <label for="current_password">Current Password</label>
                <div class="password-input-wrap">
                  <input type="password" id="current_password" name="current_password" minlength="8" required>
                  <button type="button" class="password-eye" data-pass-target="current_password" aria-label="Show password" title="Show password">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
                <p class="password-error" id="currentPasswordError" aria-live="polite"></p>
              </div>

              <div class="account-field">
                <label for="new_password">New Password</label>
                <div class="password-input-wrap">
                  <input type="password" id="new_password" name="new_password" pattern="^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$" title="Minimum 8 characters with letters, numbers, and one special character." minlength="8" required>
                  <button type="button" class="password-eye" data-pass-target="new_password" aria-label="Show password" title="Show password">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
                <p class="password-error" id="newPasswordError" aria-live="polite"></p>
              </div>

              <div class="account-field">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-input-wrap">
                  <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
                  <button type="button" class="password-eye" data-pass-target="confirm_password" aria-label="Show password" title="Show password">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
                <p class="password-error" id="confirmPasswordError" aria-live="polite"></p>
              </div>

              <div class="password-strength-wrap account-field--full" id="passwordStrengthWrap" hidden>
                <div class="password-strength-head">
                  <span>Password strength</span>
                  <strong id="passwordStrengthLabel" class="is-weak">Weak</strong>
                </div>
                <div class="password-strength-bar">
                  <span id="passwordStrengthFill" class="is-weak"></span>
                </div>
                <p class="password-instruction">Use 8+ chars, letters, numbers, and special characters. Mix upper and lower case for stronger security.</p>
              </div>
            </div>

            <p class="password-helper">Tip: do not reuse old passwords. After successful update, you will be logged out and asked to login again.</p>

            <div class="account-form-actions">
              <button type="submit" class="account-btn">Update Password</button>
              <a href="profile" class="account-btn-secondary">Cancel</a>
            </div>
          </form>
        </article>
      </section>
    </div>
  </div>
</main>

<style>
.password-panel {
  max-width: 760px;
}
.password-head h1 {
  margin-top: 12px;
  font-size: clamp(1.2rem, 1.9vw, 1.55rem);
  color: #12304f;
}
.password-head p {
  margin-top: 8px;
  color: #627c98;
  font-size: 12px;
}
.password-panel .account-field label {
  font-size: 11px;
}
.password-form-grid {
  margin-top: 16px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}
.password-input-wrap {
  position: relative;
}
.password-input-wrap input {
  padding-right: 42px;
  min-height: 40px;
  font-size: 12px;
}
.password-input-wrap input.is-invalid {
  border-color: #d93240;
  box-shadow: 0 0 0 3px rgba(217, 50, 64, .12);
}
.password-error {
  min-height: 16px;
  margin-top: 4px;
  color: #d93240;
  font-size: 11px;
  line-height: 1.35;
}
.password-strength-wrap {
  margin-top: -2px;
  border: 1px solid #d6e2f0;
  border-radius: 12px;
  padding: 10px;
  background: #f9fcff;
}
.password-strength-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.password-strength-head span {
  color: #3b5878;
  font-size: 11px;
  font-weight: 700;
}
.password-strength-head strong {
  font-size: 11px;
  font-weight: 800;
}
.password-strength-head strong.is-weak { color: #b85c5c; }
.password-strength-head strong.is-medium { color: #c67c00; }
.password-strength-head strong.is-strong { color: #2d8a63; }
.password-strength-bar {
  margin-top: 7px;
  height: 7px;
  border-radius: 999px;
  background: #e2ebf6;
  overflow: hidden;
}
.password-strength-bar span {
  display: block;
  height: 100%;
  width: 20%;
  border-radius: 999px;
  transition: width .18s ease, background .18s ease;
}
.password-strength-bar span.is-weak {
  width: 34%;
  background: #f2bcbc;
}
.password-strength-bar span.is-medium {
  width: 66%;
  background: #f3d89f;
}
.password-strength-bar span.is-strong {
  width: 100%;
  background: #b8e9d2;
}
.password-instruction {
  margin-top: 8px;
  color: #6a839f;
  font-size: 11px;
  line-height: 1.45;
}
.password-eye {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  width: 30px;
  height: 30px;
  border: 1px solid #cfe0f3;
  border-radius: 9px;
  background: #fff;
  color: #4f6f92;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.password-eye:hover {
  border-color: #0f5ebf;
  color: #0f5ebf;
}
.password-helper {
  margin-top: 12px;
  color: #6e86a0;
  font-size: 11px;
}
.password-panel .account-form-actions {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 8px;
}
.password-panel .account-form-actions .account-btn,
.password-panel .account-form-actions .account-btn-secondary {
  flex: 1 1 0;
  min-width: 0;
}
@media (max-width: 760px) {
  .password-form-grid {
    grid-template-columns: 1fr;
  }
  .password-panel .account-form-actions {
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 6px;
  }
  .password-panel .account-form-actions .account-btn,
  .password-panel .account-form-actions .account-btn-secondary {
    width: auto;
    min-height: 36px;
    font-size: 11px;
    padding-inline: 8px;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('changePasswordForm');
  const currentPassword = document.getElementById('current_password');
  const newPassword = document.getElementById('new_password');
  const confirmPassword = document.getElementById('confirm_password');
  const currentPasswordError = document.getElementById('currentPasswordError');
  const newPasswordError = document.getElementById('newPasswordError');
  const confirmPasswordError = document.getElementById('confirmPasswordError');
  const strengthWrap = document.getElementById('passwordStrengthWrap');
  const strengthLabel = document.getElementById('passwordStrengthLabel');
  const strengthFill = document.getElementById('passwordStrengthFill');

  document.querySelectorAll('.password-eye').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const targetId = btn.getAttribute('data-pass-target');
      const target = targetId ? document.getElementById(targetId) : null;
      if (!target) return;
      target.type = target.type === 'password' ? 'text' : 'password';
    });
  });

  if (!form || !currentPassword || !newPassword || !confirmPassword || !strengthLabel || !strengthFill) return;

  function setFieldError(input, errorEl, message) {
    if (!input || !errorEl) return;
    errorEl.textContent = message || '';
    input.classList.toggle('is-invalid', !!message);
  }

  function evaluateStrength(passwordValue) {
    const value = String(passwordValue || '');
    const hasLower = /[a-z]/.test(value);
    const hasUpper = /[A-Z]/.test(value);
    const hasNumber = /[0-9]/.test(value);
    const hasSpecial = /[^A-Za-z0-9]/.test(value);

    let score = 0;
    if (value.length >= 8) score += 1;
    if (value.length >= 12) score += 1;
    if (hasLower && hasUpper) score += 1;
    if (hasNumber) score += 1;
    if (hasSpecial) score += 1;

    if (score >= 5) return 'strong';
    if (score >= 3) return 'medium';
    return 'weak';
  }

  function renderStrength(passwordValue) {
    const hasValue = String(passwordValue || '').length > 0;
    if (strengthWrap) {
      strengthWrap.hidden = !hasValue;
    }
    if (!hasValue) {
      return;
    }
    const level = evaluateStrength(passwordValue);
    strengthLabel.classList.remove('is-weak', 'is-medium', 'is-strong');
    strengthFill.classList.remove('is-weak', 'is-medium', 'is-strong');
    strengthLabel.classList.add('is-' + level);
    strengthFill.classList.add('is-' + level);
    strengthLabel.textContent = level === 'weak' ? 'Weak' : (level === 'medium' ? 'Medium' : 'Strong');
  }

  function validateCurrent() {
    const value = currentPassword.value.trim();
    if (value === '') {
      setFieldError(currentPassword, currentPasswordError, 'Current password is required.');
      return false;
    }
    setFieldError(currentPassword, currentPasswordError, '');
    return true;
  }

  function validateNew() {
    const value = newPassword.value;
    const rule = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/;
    if (value === '') {
      setFieldError(newPassword, newPasswordError, 'New password is required.');
      return false;
    }
    if (!rule.test(value)) {
      setFieldError(newPassword, newPasswordError, 'Use at least 8 characters with letters, numbers, and special characters.');
      return false;
    }
    if (currentPassword.value !== '' && value === currentPassword.value) {
      setFieldError(newPassword, newPasswordError, 'New password must be different from current password.');
      return false;
    }
    setFieldError(newPassword, newPasswordError, '');
    return true;
  }

  function validateConfirm() {
    const confirmValue = confirmPassword.value;
    if (confirmValue === '') {
      setFieldError(confirmPassword, confirmPasswordError, 'Please confirm your new password.');
      return false;
    }
    if (newPassword.value !== confirmValue) {
      setFieldError(confirmPassword, confirmPasswordError, 'Confirm password must match new password.');
      return false;
    }
    setFieldError(confirmPassword, confirmPasswordError, '');
    return true;
  }

  renderStrength('');

  currentPassword.addEventListener('input', function () {
    validateCurrent();
    if (newPassword.value !== '') {
      validateNew();
    }
  });

  newPassword.addEventListener('input', function () {
    renderStrength(newPassword.value);
    validateNew();
    if (confirmPassword.value !== '') {
      validateConfirm();
    }
  });

  confirmPassword.addEventListener('input', validateConfirm);
  currentPassword.addEventListener('blur', validateCurrent);
  newPassword.addEventListener('blur', validateNew);
  confirmPassword.addEventListener('blur', validateConfirm);

  form.addEventListener('submit', function (event) {
    const currentOk = validateCurrent();
    const newOk = validateNew();
    const confirmOk = validateConfirm();
    if (!currentOk || !newOk || !confirmOk) {
      event.preventDefault();
    }
  });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
