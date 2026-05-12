<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../common/functions.php';

// Read forgot-password session state
$step         = (int)($_SESSION['fp_step'] ?? 1);
$fpEmail      = (string)($_SESSION['fp_email'] ?? '');
$fpExpires    = (int)($_SESSION['fp_otp_expires'] ?? 0);
$fpVerified   = (bool)($_SESSION['fp_otp_verified'] ?? false);
$remainingSec = 0;

if ($step === 2) {
    if ($fpExpires > 0 && time() > $fpExpires) {
        unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_otp'], $_SESSION['fp_otp_expires']);
        sinelec_set_flash('warn', 'Your OTP has expired. Please request a new one.');
        $step    = 1;
        $fpEmail = '';
    } else {
        $remainingSec = max(0, $fpExpires - time());
    }
}

if ($step === 3 && !$fpVerified) {
    $step = 1;
}

require_once '../data/store_data.php';
$currentPage = 'forgot-password';
$pageTitle   = 'Forgot Password | Sinelec Technologies';
require_once 'header.php';

function fpStepClass(int $current, int $n): string {
    if ($n < $current)  return 'fp-step is-done';
    if ($n === $current) return 'fp-step is-active';
    return 'fp-step';
}
function fpLineClass(int $current, int $n): string {
    return $current > $n ? 'fp-step-connector is-done' : 'fp-step-connector';
}
?>

<main class="fp-main">
  <div class="fp-card">

    <!-- Brand -->
    <div class="fp-brand">
      <a href="index" data-loader="off" aria-label="Sinelec Tech — Home">
        <img src="../assets/logo.png" alt="Sinelec Tech" class="fp-brand-logo">
      </a>
    </div>

    <!-- Step indicator -->
    <div class="fp-steps" role="list" aria-label="Progress steps">
      <div class="<?= fpStepClass($step, 1) ?>" role="listitem">
        <span aria-hidden="true"><?= $step > 1 ? '✓' : '1' ?></span>
        <small>Email</small>
      </div>
      <div class="<?= fpLineClass($step, 1) ?>" aria-hidden="true"></div>
      <div class="<?= fpStepClass($step, 2) ?>" role="listitem">
        <span aria-hidden="true"><?= $step > 2 ? '✓' : '2' ?></span>
        <small>Verify OTP</small>
      </div>
      <div class="<?= fpLineClass($step, 2) ?>" aria-hidden="true"></div>
      <div class="<?= fpStepClass($step, 3) ?>" role="listitem">
        <span aria-hidden="true">3</span>
        <small>New Password</small>
      </div>
    </div>

    <?php if ($step === 1): ?>
    <!-- ═══════ STEP 1 — EMAIL ═══════════════════════════════ -->
    <h1 class="fp-title">Forgot Password?</h1>
    <p class="fp-subtitle">Enter your registered email address and we'll send you a 6-digit OTP to reset your password.</p>

    <form class="fp-form" method="POST"
          action="service?urlstring=<?= EncryptURL('action=ForgotPassword') ?>"
          data-loader-text="Sending OTP…" novalidate>

      <div class="fp-field">
        <label for="fpEmailInput">Email Address</label>
        <div class="fp-input-wrap">
          <span class="fp-input-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6l8 6 8-6"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
          </span>
          <input class="fp-input" type="email" id="fpEmailInput" name="fp_email"
                 placeholder="you@example.com" required autocomplete="email" autofocus>
        </div>
      </div>

      <?php if ($turnstileSiteKey !== ''): ?>
      <div class="fp-captcha">
        <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>"
             data-theme="light" data-size="flexible" data-action="forgot_password"></div>
      </div>
      <?php endif; ?>

      <button type="submit" class="fp-submit-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Send OTP
      </button>
    </form>

    <?php elseif ($step === 2): ?>
    <!-- ═══════ STEP 2 — OTP VERIFICATION ════════════════════ -->
    <h1 class="fp-title">Enter OTP</h1>
    <p class="fp-subtitle">
      We've sent a 6-digit code to<br>
      <strong><?= htmlspecialchars($fpEmail) ?></strong>
    </p>

    <form class="fp-form" id="fpOtpForm" method="POST"
          action="service?urlstring=<?= EncryptURL('action=VerifyForgotOTP') ?>"
          data-loader-text="Verifying OTP…" data-loader="off">

      <!-- Hidden input collects all 6 digits -->
      <input type="hidden" name="fp_otp" id="fpOtpHidden">

      <div class="fp-otp-group" role="group" aria-label="One-time password digits">
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <input class="fp-otp-digit" type="text" inputmode="numeric" maxlength="1"
               pattern="[0-9]" autocomplete="one-time-code"
               aria-label="OTP digit <?= $i ?>"
               data-otp-idx="<?= $i - 1 ?>">
        <?php endfor; ?>
      </div>

      <!-- Countdown -->
      <div class="fp-otp-timer" id="fpTimer">
        <span id="fpTimerText">
          Code expires in <span class="fp-timer-val" id="fpTimerVal"><?= gmdate('i:s', $remainingSec) ?></span>
        </span>
        <span class="fp-timer-expired" id="fpTimerExpired" hidden>
          OTP expired. <a href="service?urlstring=<?= EncryptURL('action=ResendForgotOTP') ?>" data-loader="off">Resend now →</a>
        </span>
      </div>

      <button type="submit" class="fp-submit-btn" id="fpOtpSubmitBtn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Verify OTP
      </button>
    </form>

    <div class="fp-resend-row">
      Didn't receive it?&nbsp;
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=ResendForgotOTP') ?>" style="display:inline" data-loader="off">
        <button type="submit" class="fp-resend-btn" id="fpResendBtn" <?= $remainingSec > 0 ? 'disabled' : '' ?>>
          Resend OTP<?= $remainingSec > 0 ? ' (wait <span id="fpResendCountdown">' . $remainingSec . '</span>s)' : '' ?>
        </button>
      </form>
    </div>

    <?php else: ?>
    <!-- ═══════ STEP 3 — NEW PASSWORD ════════════════════════ -->
    <h1 class="fp-title">Set New Password</h1>
    <p class="fp-subtitle">Choose a strong password for your account.</p>

    <!-- Password requirements info -->
    <div class="fp-pass-requirements">
      <p class="fp-req-label">Password must have:</p>
      <ul class="fp-req-list">
        <li class="fp-req-item" id="req-length">
          <span class="fp-req-icon" aria-hidden="true">○</span> At least 8 characters
        </li>
        <li class="fp-req-item" id="req-upper">
          <span class="fp-req-icon" aria-hidden="true">○</span> One uppercase letter
        </li>
        <li class="fp-req-item" id="req-lower">
          <span class="fp-req-icon" aria-hidden="true">○</span> One lowercase letter
        </li>
        <li class="fp-req-item" id="req-number">
          <span class="fp-req-icon" aria-hidden="true">○</span> One number
        </li>
        <li class="fp-req-item" id="req-special">
          <span class="fp-req-icon" aria-hidden="true">○</span> One special character
        </li>
      </ul>
    </div>

    <form class="fp-form" method="POST"
          action="service?urlstring=<?= EncryptURL('action=ResetForgotPassword') ?>"
          data-loader-text="Updating password…" novalidate
          id="fpResetForm">

      <div class="fp-field">
        <label for="fpNewPass">New Password</label>
        <div class="fp-input-wrap">
          <span class="fp-input-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0 1 10 0v3"/></svg>
          </span>
          <input class="fp-input" type="password" id="fpNewPass" name="fp_new_password"
                 placeholder="Create a strong password" required autocomplete="new-password" autofocus>
          <button type="button" class="fp-pass-eye" data-fp-eye="fpNewPass" aria-label="Show password">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <!-- Strength bar -->
        <div class="fp-strength-bar" aria-hidden="true">
          <div class="fp-strength-fill" id="fpStrengthFill"></div>
        </div>
        <span class="fp-strength-label" id="fpStrengthLabel"></span>
      </div>

      <div class="fp-field">
        <label for="fpConfirmPass">Confirm Password</label>
        <div class="fp-input-wrap">
          <span class="fp-input-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0 1 10 0v3"/></svg>
          </span>
          <input class="fp-input" type="password" id="fpConfirmPass" name="fp_confirm_password"
                 placeholder="Repeat your password" required autocomplete="new-password">
          <button type="button" class="fp-pass-eye" data-fp-eye="fpConfirmPass" aria-label="Show confirm password">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <span class="fp-match-hint" id="fpMatchHint"></span>
      </div>

      <button type="submit" class="fp-submit-btn" id="fpResetBtn" disabled>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
        Update Password
      </button>
    </form>

    <?php endif; ?>

    <a href="index" class="fp-back-link" data-loader="off">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Back to Login
    </a>

  </div><!-- /.fp-card -->
</main>

<?php if ($step === 2): ?>
<script>
(function () {
  /* ── OTP digit inputs ── */
  const digits  = Array.from(document.querySelectorAll('.fp-otp-digit'));
  const hidden  = document.getElementById('fpOtpHidden');
  const form    = document.getElementById('fpOtpForm');
  const submitBtn = document.getElementById('fpOtpSubmitBtn');

  function syncHidden() {
    const val = digits.map(d => d.value).join('');
    hidden.value = val;
    digits.forEach(d => d.classList.toggle('is-filled', d.value !== ''));
    submitBtn.disabled = val.length < 6;
  }

  digits.forEach((input, idx) => {
    input.addEventListener('input', e => {
      const val = e.target.value.replace(/\D/g, '');
      input.value = val.slice(-1);
      syncHidden();
      if (val && idx < 5) digits[idx + 1].focus();
    });

    input.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !input.value && idx > 0) {
        digits[idx - 1].focus();
        digits[idx - 1].value = '';
        syncHidden();
      }
      if (e.key === 'ArrowLeft' && idx > 0) digits[idx - 1].focus();
      if (e.key === 'ArrowRight' && idx < 5) digits[idx + 1].focus();
    });

    input.addEventListener('paste', e => {
      e.preventDefault();
      const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      pasted.split('').forEach((ch, i) => {
        if (digits[i]) digits[i].value = ch;
      });
      syncHidden();
      const next = Math.min(pasted.length, 5);
      digits[next].focus();
    });
  });

  form.addEventListener('submit', e => {
    if (hidden.value.length < 6) {
      e.preventDefault();
      if (window.toast) toast('Please enter all 6 digits of the OTP.', 'warn');
      return;
    }
    submitBtn.disabled = true;
    document.getElementById('globalPageLoader')?.classList.add('is-visible');
  });

  digits[0]?.focus();
  syncHidden();

  /* ── Countdown timer ── */
  let remaining = <?= (int)$remainingSec ?>;
  const timerVal  = document.getElementById('fpTimerVal');
  const timerText = document.getElementById('fpTimerText');
  const timerExp  = document.getElementById('fpTimerExpired');
  const resendBtn = document.getElementById('fpResendBtn');
  const resendCd  = document.getElementById('fpResendCountdown');

  function pad(n) { return String(n).padStart(2, '0'); }
  function formatTime(s) { return pad(Math.floor(s / 60)) + ':' + pad(s % 60); }

  function tickTimer() {
    if (remaining <= 0) {
      if (timerText) timerText.hidden = true;
      if (timerExp)  timerExp.hidden = false;
      if (resendBtn) { resendBtn.disabled = false; resendBtn.innerHTML = 'Resend OTP'; }
      submitBtn.disabled = true;
      return;
    }
    remaining--;
    if (timerVal) timerVal.textContent = formatTime(remaining);
    if (resendCd) resendCd.textContent = remaining;
    setTimeout(tickTimer, 1000);
  }

  if (remaining > 0) setTimeout(tickTimer, 1000);
  else {
    if (timerText) timerText.hidden = true;
    if (timerExp)  timerExp.hidden = false;
    if (resendBtn) { resendBtn.disabled = false; resendBtn.innerHTML = 'Resend OTP'; }
  }
})();
</script>
<?php endif; ?>

<?php if ($step === 3): ?>
<script>
(function () {
  const newPassInput  = document.getElementById('fpNewPass');
  const confPassInput = document.getElementById('fpConfirmPass');
  const strengthFill  = document.getElementById('fpStrengthFill');
  const strengthLabel = document.getElementById('fpStrengthLabel');
  const matchHint     = document.getElementById('fpMatchHint');
  const resetBtn      = document.getElementById('fpResetBtn');
  const form          = document.getElementById('fpResetForm');

  const reqs = {
    length:  { el: document.getElementById('req-length'),  test: v => v.length >= 8 },
    upper:   { el: document.getElementById('req-upper'),   test: v => /[A-Z]/.test(v) },
    lower:   { el: document.getElementById('req-lower'),   test: v => /[a-z]/.test(v) },
    number:  { el: document.getElementById('req-number'),  test: v => /[0-9]/.test(v) },
    special: { el: document.getElementById('req-special'), test: v => /[^A-Za-z0-9]/.test(v) },
  };

  const CHECK_ICON = '<svg class="fp-req-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8"><polyline points="20 6 9 17 4 12"/></svg>';
  const RING_ICON  = '<svg class="fp-req-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>';

  function calcScore(v) {
    return Object.values(reqs).filter(r => r.test(v)).length;
  }

  function updateStrength(v) {
    const score = calcScore(v);

    Object.values(reqs).forEach(r => {
      if (!r.el) return;
      const met = r.test(v);
      r.el.classList.toggle('is-met', met);
      const icon = r.el.querySelector('.fp-req-icon');
      if (icon) icon.outerHTML; // replace icon
      r.el.innerHTML = (met ? CHECK_ICON : RING_ICON) + ' ' + r.el.textContent.trim();
    });

    if (v === '') {
      strengthFill.style.width = '0%';
      strengthFill.style.background = 'transparent';
      strengthLabel.textContent = '';
      return;
    }

    const pct = (score / 5) * 100;
    let color, label;
    if (score <= 2) { color = '#EF4444'; label = 'Weak'; }
    else if (score <= 3) { color = '#F97316'; label = 'Medium'; }
    else if (score === 4) { color = '#EAB308'; label = 'Good'; }
    else { color = '#22C55E'; label = 'Strong'; }

    strengthFill.style.width = pct + '%';
    strengthFill.style.background = color;
    strengthLabel.textContent = label;
    strengthLabel.style.color = color;
  }

  function updateMatch() {
    const np = newPassInput.value;
    const cp = confPassInput.value;
    if (cp === '') { matchHint.textContent = ''; matchHint.className = 'fp-match-hint'; return; }
    if (np === cp) {
      matchHint.textContent = '✓ Passwords match';
      matchHint.className = 'fp-match-hint is-match';
    } else {
      matchHint.textContent = '✗ Passwords do not match';
      matchHint.className = 'fp-match-hint is-mismatch';
    }
  }

  function syncSubmitBtn() {
    const np    = newPassInput.value;
    const cp    = confPassInput.value;
    const score = calcScore(np);
    resetBtn.disabled = !(score >= 4 && np === cp && np !== '');
  }

  newPassInput?.addEventListener('input', () => {
    updateStrength(newPassInput.value);
    updateMatch();
    syncSubmitBtn();
  });

  confPassInput?.addEventListener('input', () => {
    updateMatch();
    syncSubmitBtn();
  });

  // Eye toggle buttons
  document.querySelectorAll('[data-fp-eye]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.fpEye);
      if (!target) return;
      target.type = target.type === 'password' ? 'text' : 'password';
    });
  });

  form?.addEventListener('submit', e => {
    const np = newPassInput.value;
    const cp = confPassInput.value;
    const passwordRule = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/;
    if (!passwordRule.test(np)) {
      e.preventDefault();
      if (window.toast) toast('Password does not meet the requirements.', 'warn');
      return;
    }
    if (np !== cp) {
      e.preventDefault();
      if (window.toast) toast('Passwords do not match.', 'warn');
      return;
    }
    resetBtn.disabled = true;
    document.getElementById('globalPageLoader')?.classList.add('is-visible');
  });
})();
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
