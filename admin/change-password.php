<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'change-password';
$pageTitle   = 'Change Password';

// Handle form submission
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

<div class="pg-header">
  <div>
    <div class="pg-title">Change Password</div>
    <div class="pg-subtitle">Update your admin account password.</div>
  </div>
</div>

<div style="max-width:480px;">
  <div class="card">
    <div class="card-header">
      <span class="card-title">New Password</span>
    </div>
    <div class="card-body">
      <form method="POST" action="change-password" class="form-grid">

        <div class="form-group">
          <label class="form-label">Current Password <span class="req">*</span></label>
          <input type="password" name="current_password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
        </div>

        <div class="form-group">
          <label class="form-label">New Password <span class="req">*</span></label>
          <input type="password" name="new_password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
          <span style="font-size:11px;color:var(--text-muted);">Min 8 characters, include letters, numbers, and a special character.</span>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm New Password <span class="req">*</span></label>
          <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
        </div>

        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn--primary">Update Password</button>
          <a href="welcome" class="btn btn--outline">Cancel</a>
        </div>

      </form>
    </div>
  </div>
</div>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
