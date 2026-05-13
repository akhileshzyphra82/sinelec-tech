<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'change-password';
$pageTitle   = 'Change Password';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId          = (int)($_SESSION['sinelec_admin']['USER_ID'] ?? 0);
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword     = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $passwordRule    = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/';

    if ($userId <= 0) {
        sinelec_set_flash('warn', 'Session expired. Please sign in again.');
        header('location:index'); exit();
    }
    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        sinelec_set_flash('warn', 'Please fill in all password fields.');
    } elseif (!preg_match($passwordRule, $newPassword)) {
        sinelec_set_flash('warn', 'New password must be at least 8 characters with letters, numbers, and a special character.');
    } elseif ($newPassword !== $confirmPassword) {
        sinelec_set_flash('warn', 'New password and confirm password do not match.');
    } elseif ($currentPassword === $newPassword) {
        sinelec_set_flash('warn', 'New password must be different from your current password.');
    } else {
        require_once __DIR__ . '/../controller/website_controller.php';
        $wc = new WebsiteController();
        $errCode = '';
        if ($wc->changeUserPassword($userId, $currentPassword, $newPassword, $errCode)) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
            session_start();
            sinelec_set_flash('ok', 'Password updated. Please sign in with your new password.');
            header('location:index'); exit();
        } else {
            $msg = match($errCode) {
                'current_password_invalid' => 'Current password is incorrect.',
                'same_as_current'          => 'New password must be different from current password.',
                default                    => 'Unable to update password right now. Please try again.',
            };
            sinelec_set_flash('err', $msg);
        }
    }
    header('location:change-password'); exit();
}

ob_start();
?>

<div class="pg-header" style="justify-content:center;text-align:center;margin-bottom:20px;">
  <div>
    <div class="pg-title">Change Password</div>
    <div class="pg-subtitle">Update your admin account password securely.</div>
  </div>
</div>

<div style="max-width:460px;margin:0 auto;">
  <div class="card">
    <div class="card-header">
      <span class="card-title">Update Password</span>
    </div>
    <div class="card-body">
      <form method="POST" action="change-password" id="cpForm" class="form-grid" novalidate>

        <!-- Current Password -->
        <div class="fg">
          <label>Current Password <span class="req">*</span></label>
          <div class="pw-wrap">
            <input type="password" name="current_password" id="cp_current" class="form-control pw-input"
                   placeholder="Enter current password" required autocomplete="current-password">
            <button type="button" class="pw-eye" data-target="cp_current" tabindex="-1" title="Show / Hide">
              <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-shut" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>

        <!-- New Password -->
        <div class="fg">
          <label>New Password <span class="req">*</span></label>
          <div class="pw-wrap">
            <input type="password" name="new_password" id="cp_new" class="form-control pw-input"
                   placeholder="Enter new password" required autocomplete="new-password"
                   oninput="checkStrength(this.value)">
            <button type="button" class="pw-eye" data-target="cp_new" tabindex="-1" title="Show / Hide">
              <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-shut" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>

          <!-- Strength meter -->
          <div id="strengthMeter" class="str-meter">
            <div class="str-bars">
              <div class="str-bar" id="sb1"></div>
              <div class="str-bar" id="sb2"></div>
              <div class="str-bar" id="sb3"></div>
              <div class="str-bar" id="sb4"></div>
            </div>
            <div class="str-info">
              <span id="strLabel" class="str-label"></span>
              <span id="strTip"   class="str-tip"></span>
            </div>
          </div>

          <!-- Rules checklist -->
          <div class="pw-rules" id="pwRules">
            <div class="pw-rule" id="r_len">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/></svg>
              At least 8 characters
            </div>
            <div class="pw-rule" id="r_let">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/></svg>
              Contains letters (a–z / A–Z)
            </div>
            <div class="pw-rule" id="r_num">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/></svg>
              Contains a number (0–9)
            </div>
            <div class="pw-rule" id="r_sym">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/></svg>
              Contains a special character (!@#$…)
            </div>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="fg">
          <label>Confirm New Password <span class="req">*</span></label>
          <div class="pw-wrap">
            <input type="password" name="confirm_password" id="cp_confirm" class="form-control pw-input"
                   placeholder="Re-enter new password" required autocomplete="new-password"
                   oninput="checkMatch()">
            <button type="button" class="pw-eye" data-target="cp_confirm" tabindex="-1" title="Show / Hide">
              <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-shut" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <span id="matchMsg" class="match-msg"></span>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn--primary btn--full">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0110 0v3"/></svg>
            Update Password
          </button>
          <a href="profile" class="btn btn--outline">Cancel</a>
        </div>

      </form>
    </div>
  </div>
</div>


<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
