<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'profile';
$pageTitle   = 'My Profile';

$controller = new AdminController();
$userId     = (int)($_SESSION['sinelec_admin']['USER_ID'] ?? 0);
$profile    = $controller->getAdminProfile($userId);

if (!$profile) {
    sinelec_set_flash('err', 'Could not load profile.');
    header('location:welcome'); exit();
}

$name       = (string)($profile->NAME ?? '');
$email      = (string)($profile->COMMUNICATION_EMAIL_ID ?? '');
$isd        = (string)($profile->COMMUNICATION_MOBILE_NUM_ISD ?? '91');
$mobile     = (string)($profile->COMMUNICATION_MOBILE_NUM ?? '');
$company    = (string)($profile->COMPANY_NAME ?? '');
$desig      = (string)($profile->DESIGNATION ?? '');
$initials   = strtoupper(substr(trim($name), 0, 1) ?: 'A');
$userTypeId = (int)($_SESSION['sinelec_admin']['USER_TYPE_ID'] ?? 1);
$roleLabel  = $userTypeId === 1 ? 'Administrator' : 'Employee';

ob_start();
?>

<div class="pg-header" style="justify-content:center;text-align:center;margin-bottom:20px;">
  <div>
    <div class="pg-title">My Profile</div>
    <div class="pg-subtitle">Manage your account details.</div>
  </div>
</div>

<div style="max-width:700px;margin:0 auto;">

  <!-- Profile Card Header -->
  <div style="background:linear-gradient(135deg,#eff6ff 0%,#f5f3ff 100%);border:1px solid #e0e7ff;border-radius:14px 14px 0 0;padding:24px 28px;display:flex;align-items:center;gap:20px;">
    <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#2563eb 0%,#7c3aed 100%);display:grid;place-items:center;font-size:26px;font-weight:700;color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.3);flex-shrink:0;">
      <?= htmlspecialchars($initials) ?>
    </div>
    <div>
      <div style="font-size:18px;font-weight:700;color:#0f172a;"><?= htmlspecialchars($name ?: 'User') ?></div>
      <div style="font-size:13px;color:#6b7280;margin-top:3px;"><?= htmlspecialchars($email) ?></div>
      <div style="display:inline-flex;align-items:center;gap:5px;margin-top:7px;padding:3px 10px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">
        <span style="width:6px;height:6px;border-radius:50%;background:#22c55e;"></span>
        <?= htmlspecialchars($roleLabel) ?>
      </div>
    </div>
  </div>

  <!-- Edit Form -->
  <div class="card" style="border-radius:0 0 14px 14px;border-top:none;">
    <div class="card-header">
      <span class="card-title">Edit Profile Details</span>
      <span style="font-size:12px;color:var(--text-muted);">Click the pencil icon on any field to edit it</span>
    </div>
    <div class="card-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=UpdateAdminProfile') ?>" id="profileForm">

        <div class="prof-grid">

          <!-- Name -->
          <div class="prof-field">
            <label class="prof-label">Full Name <span class="req">*</span></label>
            <div class="prof-input-wrap">
              <input type="text" name="name" id="f_name" class="prof-input" value="<?= htmlspecialchars($name) ?>" readonly required>
              <button type="button" class="prof-edit-btn" data-target="f_name" title="Edit name">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
              </button>
            </div>
          </div>

          <!-- Email (locked) -->
          <div class="prof-field">
            <label class="prof-label">Email Address</label>
            <div class="prof-input-wrap">
              <input type="email" class="prof-input is-locked" value="<?= htmlspecialchars($email) ?>" readonly>
              <span class="prof-lock-icon" title="Email cannot be changed">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0110 0v3"/></svg>
              </span>
            </div>
          </div>

          <!-- Country Code -->
          <div class="prof-field">
            <label class="prof-label">Country Code</label>
            <div class="prof-input-wrap">
              <select name="communication_mobile_num_isd" id="f_isd" class="prof-input prof-select" disabled>
                <?php
                $codes = ['91'=>'+91 (India)','1'=>'+1 (USA/Canada)','44'=>'+44 (UK)','49'=>'+49 (Germany)','33'=>'+33 (France)','39'=>'+39 (Italy)','34'=>'+34 (Spain)','31'=>'+31 (Netherlands)','971'=>'+971 (UAE)','65'=>'+65 (Singapore)','61'=>'+61 (Australia)'];
                foreach ($codes as $code => $label):
                ?>
                <option value="<?= $code ?>" <?= $isd === (string)$code ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="prof-edit-btn" data-target="f_isd" title="Edit country code">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
              </button>
            </div>
          </div>

          <!-- Mobile -->
          <div class="prof-field">
            <label class="prof-label">Mobile Number</label>
            <div class="prof-input-wrap">
              <input type="tel" name="communication_mobile_num" id="f_mobile" class="prof-input" value="<?= htmlspecialchars($mobile) ?>" readonly>
              <button type="button" class="prof-edit-btn" data-target="f_mobile" title="Edit mobile">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
              </button>
            </div>
          </div>

          <!-- Company -->
          <div class="prof-field">
            <label class="prof-label">Company Name</label>
            <div class="prof-input-wrap">
              <input type="text" name="company_name" id="f_company" class="prof-input" value="<?= htmlspecialchars($company) ?>" readonly>
              <button type="button" class="prof-edit-btn" data-target="f_company" title="Edit company">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
              </button>
            </div>
          </div>

          <!-- Designation -->
          <div class="prof-field">
            <label class="prof-label">Designation</label>
            <div class="prof-input-wrap">
              <input type="text" name="designation" id="f_desig" class="prof-input" value="<?= htmlspecialchars($desig) ?>" readonly>
              <button type="button" class="prof-edit-btn" data-target="f_desig" title="Edit designation">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
              </button>
            </div>
          </div>

        </div><!-- /prof-grid -->

        <div class="prof-form-actions">
          <button type="submit" class="btn btn--primary" style="min-width:140px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
            Save Changes
          </button>
          <button type="button" id="resetBtn" class="btn btn--outline">Reset</button>
          <a href="change-password" class="btn btn--outline" style="margin-left:auto;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0110 0v3"/></svg>
            Change Password
          </a>
        </div>

      </form>
    </div>
  </div>

</div>


<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
