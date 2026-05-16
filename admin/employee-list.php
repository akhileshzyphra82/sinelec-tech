<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../controller/admin_controller.php';

$currentPage = 'employee-list';
$pageTitle   = 'Employees';

$controller = new AdminController();

/* ── Permission checks (employee only; admin always passes) ── */
$canView   = sinelec_can('view');
$canAdd    = sinelec_can('add');
$canEdit   = sinelec_can('edit');
$canDelete = sinelec_can('delete');

if (!$canView) {
    sinelec_set_flash('err', 'You do not have permission to view this page.');
    header('location:dashboard'); exit();
}

$employees  = $controller->getAllEmployees([
    'search' => trim($_GET['search'] ?? ''),
    'role'   => (int)($_GET['role'] ?? 0),
    'status' => $_GET['status'] ?? '',
]);
$roles = $controller->getAllRoles();

ob_start();
?>

<div class="pg-header">
  <div>
    <div class="pg-title">Employees</div>
    <div class="pg-sub">Manage employee accounts and their assigned roles.</div>
  </div>
  <?php if ($canAdd): ?>
  <button class="btn btn--primary" onclick="openEmpModal(0)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Employee
  </button>
  <?php endif; ?>
</div>

<!-- ── Search Bar ── -->
<div style="display:flex;gap:10px;margin-bottom:18px;align-items:center;flex-wrap:wrap;">
  <div style="position:relative;flex:1;min-width:220px;max-width:340px;">
    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9ca3af;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="empSearch" class="form-control" placeholder="Search name, email, designation…" style="padding-left:32px;height:36px;" oninput="onSearch()">
  </div>
</div>

<!-- ── Employees Table ── -->
<div class="card">
  <?php if (empty($employees)): ?>
  <div class="card-body">
    <div class="empty-state">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      <h3>No employees found</h3>
      <p>Add your first employee to get started.</p>
      <?php if ($canAdd): ?><button class="btn btn--primary" onclick="openEmpModal(0)">Add Employee</button><?php endif; ?>
    </div>
  </div>
  <?php else: ?>

  <!-- ── Pagination Bar ── -->
  <div class="emp-pgbar" id="empPgBar">
    <div class="emp-pgbar-info" id="empPgInfo">Showing 1–10 of <?= count($employees) ?> records</div>
    <div class="emp-pgbar-right">
      <span class="emp-pgbar-rpp-label">Records per page</span>
      <select id="empRpp" class="emp-pgbar-rpp-sel">
        <option value="10" selected>10</option>
        <option value="20">20</option>
        <option value="30">30</option>
        <option value="40">40</option>
        <option value="50">50</option>
        <option value="60">60</option>
        <option value="70">70</option>
        <option value="80">80</option>
        <option value="90">90</option>
        <option value="100">100</option>
      </select>
      <button class="emp-pgbar-apply" onclick="applyRpp()">Apply</button>
      <div class="emp-pgbar-nav" id="empNav"></div>
    </div>
  </div>

  <div class="card-body card-body--flush">
    <table class="dt" id="empTable">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Employee</th>
          <th>Email</th>
          <th>Mobile</th>
          <th>Designation</th>
          <th>Role</th>
          <th style="width:90px;">Status</th>
          <th style="width:60px;text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody id="empTbody">
        <?php foreach ($employees as $i => $emp):
          $uid        = (int)($emp->USER_ID ?? 0);
          $empName    = (string)($emp->NAME ?? '');
          $email      = (string)($emp->COMMUNICATION_EMAIL_ID ?? '');
          $isdCode    = (int)($emp->COMMUNICATION_MOBILE_NUM_ISD ?? 0);
          $mobile     = (string)($emp->COMMUNICATION_MOBILE_NUM ?? '');
          $desig      = (string)($emp->DESIGNATION ?? '');
          $company    = (string)($emp->COMPANY_NAME ?? '');
          $roleName   = (string)($emp->ROLE_NAME ?? '');
          $statusFlag = (string)($emp->ACCOUNT_ACTIVATION_FLAG ?? '0');
          $isActive   = $statusFlag === '1';
          $initial    = strtoupper(substr(trim($empName), 0, 1)) ?: 'E';
          $mobileDisp = ($isdCode > 0 ? '+'.$isdCode.' ' : '').$mobile;
          $searchStr  = strtolower($empName.' '.$email.' '.$desig.' '.$roleName);
        ?>
        <tr data-search="<?= htmlspecialchars($searchStr) ?>" data-seq="<?= $i + 1 ?>">
          <td class="td-sm emp-sno"><?= $i + 1 ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;flex-shrink:0;letter-spacing:-.5px;">
                <?= htmlspecialchars($initial) ?>
              </div>
              <div>
                <div style="font-weight:600;color:var(--text);font-size:13px;"><?= htmlspecialchars($empName) ?></div>
                <?php if ($company): ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($company) ?></div><?php endif; ?>
              </div>
            </div>
          </td>
          <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($email) ?></td>
          <td style="font-size:12px;"><?= $mobileDisp ? htmlspecialchars($mobileDisp) : '<span style="color:var(--text-muted);">—</span>' ?></td>
          <td style="font-size:12px;"><?= $desig ? htmlspecialchars($desig) : '<span style="color:var(--text-muted);">—</span>' ?></td>
          <td>
            <?php if ($roleName): ?>
              <code style="background:#f1f5f9;padding:2px 8px;border-radius:5px;font-size:11px;letter-spacing:.2px;color:#6d28d9;"><?= htmlspecialchars($roleName) ?></code>
            <?php else: ?>
              <span style="color:var(--text-muted);font-size:12px;">—</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge <?= $isActive ? 'badge--green' : 'badge--amber' ?>">
              <?= $isActive ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td>
            <?php if ($canEdit || $canDelete): ?>
            <div class="kbm-wrap">
              <button class="kbm-btn" onclick="toggleKbm(this)" title="Actions">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                  <circle cx="12" cy="5"  r="1.7"/>
                  <circle cx="12" cy="12" r="1.7"/>
                  <circle cx="12" cy="19" r="1.7"/>
                </svg>
              </button>
              <div class="kbm-drop">
                <?php if ($canEdit): ?>
                <button class="kbm-item" onclick="closeKbm(this);openEmpModal(<?= $uid ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </button>
                <button class="kbm-item" onclick="closeKbm(this);openResetPwModal(<?= $uid ?>,<?= htmlspecialchars(json_encode($empName), ENT_QUOTES) ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0110 0v3"/></svg>
                  Reset Password
                </button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <?php if ($canEdit): ?><div class="kbm-divider"></div><?php endif; ?>
                <button class="kbm-item kbm-item--danger" onclick="closeKbm(this);confirmDeleteEmp(<?= $uid ?>,<?= htmlspecialchars(json_encode($empName), ENT_QUOTES) ?>)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                  Delete
                </button>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; /* canEdit || canDelete */ ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div id="empNoResults" style="display:none;padding:40px;text-align:center;color:var(--text-muted);font-size:13px;">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto 10px;opacity:.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      No employees match your search.
    </div>
  </div>
  <?php endif; ?>
</div>


<!-- ════════════════════════════════════════════════════
     ADD / EDIT EMPLOYEE MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="empModal">
  <div class="modal" style="max-width:640px;max-height:92vh;display:flex;flex-direction:column;">

    <div class="modal-hd" style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div>
        <div class="modal-title" id="empModalTitle">Add Employee</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">Fill in the employee details below.</div>
      </div>
      <button class="modal-close" onclick="closeModal('empModal')" style="font-size:22px;line-height:1;">×</button>
    </div>

    <div style="overflow-y:auto;flex:1;padding:22px;">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=SaveEmployee') ?>" id="empForm" autocomplete="off">
        <input type="hidden" name="user_id" id="fEmpId" value="0">

        <!-- Name + Email -->
        <div class="form-row cols-2" style="margin-bottom:14px;">
          <div class="fg">
            <label>Full Name <span class="req">*</span></label>
            <input type="text" name="name" id="fEmpName" class="form-control" placeholder="e.g. Rahul Sharma" required>
          </div>
          <div class="fg">
            <label>Email Address <span class="req" id="emailReqMark">*</span></label>
            <div style="position:relative;">
              <input type="email" name="communication_email_id" id="fEmpEmail" class="form-control" placeholder="employee@sinelec.com" required autocomplete="off">
              <span id="emailCheckIcon" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:13px;display:none;"></span>
            </div>
            <span class="fg-hint" id="emailHint" style="display:none;"></span>
          </div>
        </div>

        <!-- Password row — ONLY shown on Add mode -->
        <div class="form-row cols-2" id="empPwRow" style="margin-bottom:14px;">
          <div class="fg">
            <label>Password <span class="req">*</span></label>
            <div style="position:relative;">
              <input type="password" name="password" id="fEmpPass" class="form-control" placeholder="Set a strong password" style="padding-right:40px;" autocomplete="new-password">
              <button type="button" class="pw-eye" data-target="fEmpPass"
                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;padding:2px;display:flex;align-items:center;">
                <svg class="eye-open" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-shut" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>
          <div class="fg" style="align-self:flex-end;">
            <div style="font-size:12px;color:var(--text-muted);padding-bottom:4px;line-height:1.5;">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Min 6 characters. Employee can change it after first login.
            </div>
          </div>
        </div>

        <!-- Designation + Mobile -->
        <div class="form-row cols-2" style="margin-bottom:14px;">
          <div class="fg">
            <label>Designation</label>
            <input type="text" name="designation" id="fEmpDesig" class="form-control" placeholder="e.g. Sales Executive">
          </div>
          <div class="fg">
            <label>Mobile</label>
            <div style="display:flex;gap:8px;">
              <select name="communication_mobile_num_isd" id="fEmpIsd" class="form-control" style="width:90px;flex-shrink:0;">
                <option value="91">+91</option>
                <option value="1">+1</option>
                <option value="44">+44</option>
                <option value="971">+971</option>
                <option value="65">+65</option>
                <option value="60">+60</option>
                <option value="966">+966</option>
                <option value="974">+974</option>
                <option value="968">+968</option>
                <option value="49">+49</option>
                <option value="33">+33</option>
                <option value="61">+61</option>
              </select>
              <input type="text" name="communication_mobile_num" id="fEmpMobile" class="form-control" placeholder="Mobile number">
            </div>
          </div>
        </div>

        <!-- Company + Role -->
        <div class="form-row cols-2" style="margin-bottom:14px;">
          <div class="fg">
            <label>Company</label>
            <input type="text" name="company_name" id="fEmpCompany" class="form-control" placeholder="e.g. Sinelec Technologies">
          </div>
          <div class="fg">
            <label>Role</label>
            <select name="role_id" id="fEmpRole" class="form-control">
              <option value="0">— No Role Assigned —</option>
              <?php foreach ($roles as $role): ?>
              <?php if ((int)($role->STATUS ?? 1) !== 1) continue; ?>
              <option value="<?= (int)($role->ROLE_ID ?? 0) ?>"><?= htmlspecialchars($role->ROLE_NAME ?? '') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Status -->
        <div class="fg" style="margin-bottom:20px;max-width:300px;">
          <label>Account Status</label>
          <select name="account_activation_flag" id="fEmpStatus" class="form-control">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:14px;border-top:1px solid var(--border);">
          <button type="button" class="btn btn--outline" onclick="closeModal('empModal')">Cancel</button>
          <button type="submit" class="btn btn--primary" id="empSubmitBtn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Employee
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════════════════
     RESET PASSWORD MODAL
════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="resetPwModal">
  <div class="modal modal--sm" style="max-width:420px;">
    <div class="modal-hd" style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <div>
        <div class="modal-title">Reset Password</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="resetPwSubtitle">Set a new password for this employee.</div>
      </div>
      <button class="modal-close" onclick="closeModal('resetPwModal')" style="font-size:22px;line-height:1;">×</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=ResetEmployeePassword') ?>" id="resetPwForm">
        <input type="hidden" name="user_id" id="resetPwUserId">

        <!-- New Password -->
        <div class="fg" style="margin-bottom:14px;">
          <label>New Password <span class="req">*</span></label>
          <div style="position:relative;">
            <input type="password" name="new_password" id="resetPwNew" class="form-control" placeholder="Enter new password" style="padding-right:40px;" required minlength="6" autocomplete="new-password">
            <button type="button" class="pw-eye" data-target="resetPwNew"
              style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;padding:2px;display:flex;align-items:center;">
              <svg class="eye-open" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-shut" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="fg" style="margin-bottom:20px;">
          <label>Confirm Password <span class="req">*</span></label>
          <div style="position:relative;">
            <input type="password" name="confirm_password" id="resetPwConfirm" class="form-control" placeholder="Re-enter new password" style="padding-right:40px;" required autocomplete="new-password">
            <button type="button" class="pw-eye" data-target="resetPwConfirm"
              style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;padding:2px;display:flex;align-items:center;">
              <svg class="eye-open" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-shut" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <span id="resetPwMatch" style="font-size:12px;margin-top:4px;display:none;"></span>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:4px;border-top:1px solid var(--border);">
          <button type="button" class="btn btn--outline" onclick="closeModal('resetPwModal')">Cancel</button>
          <button type="submit" class="btn btn--primary" id="resetPwSubmitBtn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0110 0v3"/></svg>
            Reset Password
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ── Delete Confirm Modal ── -->
<div class="modal-overlay" id="deleteEmpModal">
  <div class="modal modal--sm">
    <div class="modal-hd" style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <span class="modal-title">Delete Employee</span>
      <button class="modal-close" onclick="closeModal('deleteEmpModal')">×</button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:20px;">
        <div style="width:40px;height:40px;border-radius:50%;background:#fff5f5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
        </div>
        <div>
          <div style="font-weight:600;margin-bottom:5px;font-size:14px;">Are you sure?</div>
          <div style="font-size:13px;color:var(--text-muted);">You are about to permanently delete employee <strong id="delEmpName"></strong>. This cannot be undone.</div>
        </div>
      </div>
      <form method="POST" action="service?urlstring=<?= EncryptURL('action=DeleteEmployee') ?>">
        <input type="hidden" name="user_id" id="delEmpId">
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button type="button" class="btn btn--outline" onclick="closeModal('deleteEmpModal')">Cancel</button>
          <button type="submit" class="btn btn--danger-solid">Delete Employee</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ── Scoped CSS ── -->
<style>
.emp-pgbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  padding: 12px 18px;
  border-bottom: 1px solid var(--border);
  background: #fafbfc;
  border-radius: var(--radius) var(--radius) 0 0;
}
.emp-pgbar-info { font-size: 13px; color: var(--text-muted); white-space: nowrap; }
.emp-pgbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.emp-pgbar-rpp-label { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; }
.emp-pgbar-rpp-sel {
  height: 34px;
  padding: 0 28px 0 10px;
  border: 1.5px solid var(--border);
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 10px center;
  -webkit-appearance: none;
  appearance: none;
  cursor: pointer;
  color: var(--text);
  min-width: 70px;
}
.emp-pgbar-rpp-sel:focus { outline: none; border-color: var(--primary); }
.emp-pgbar-apply {
  height: 34px;
  padding: 0 16px;
  border: 1.5px solid var(--border);
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  background: #fff;
  color: var(--text);
  cursor: pointer;
  transition: border-color .15s, background .15s;
}
.emp-pgbar-apply:hover { border-color: var(--primary); background: #f0f4ff; color: var(--primary); }
.emp-pgbar-nav { display: flex; align-items: center; gap: 4px; }
.pg-btn {
  min-width: 34px;
  height: 34px;
  padding: 0 10px;
  border: 1.5px solid var(--border);
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
  background: #fff;
  color: var(--text);
  cursor: pointer;
  transition: border-color .15s, background .15s, color .15s;
  white-space: nowrap;
  display: flex;
  align-items: center;
  justify-content: center;
}
.pg-btn:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); background: #f0f4ff; }
.pg-btn.pg-active { background: var(--primary); border-color: var(--primary); color: #fff; font-weight: 700; pointer-events: none; }
.pg-btn:disabled { opacity: .38; cursor: not-allowed; }
.pg-dots { font-size: 13px; color: var(--text-muted); padding: 0 4px; line-height: 34px; }

/* Email validation states */
.email-ok    { border-color: #16a34a !important; }
.email-err   { border-color: #dc2626 !important; }
</style>


<!-- ── Embedded Data + JS ── -->
<script>
const EMP_DATA = <?= json_encode(array_map(function ($e) {
    return [
        'id'      => (int)($e->USER_ID ?? 0),
        'name'    => (string)($e->NAME ?? ''),
        'email'   => (string)($e->COMMUNICATION_EMAIL_ID ?? ''),
        'mobile'  => (string)($e->COMMUNICATION_MOBILE_NUM ?? ''),
        'isd'     => (int)($e->COMMUNICATION_MOBILE_NUM_ISD ?? 91),
        'company' => (string)($e->COMPANY_NAME ?? ''),
        'desig'   => (string)($e->DESIGNATION ?? ''),
        'role_id' => (int)($e->ROLE_ID ?? 0),
        'status'  => (string)($e->ACCOUNT_ACTIVATION_FLAG ?? '1'),
    ];
}, $employees), JSON_FORCE_OBJECT) ?>;

/* ═══════════════════════════════════════════════════════
   PAGINATION ENGINE
   ═══════════════════════════════════════════════════════ */
(function () {
  var allRows  = [];
  var filtered = [];
  var curPage  = 1;
  var rpp      = 10;

  function init() {
    allRows  = Array.from(document.querySelectorAll('#empTbody tr'));
    filtered = allRows.slice();
    render();
  }

  window.onSearch = function () {
    var q = document.getElementById('empSearch').value.toLowerCase().trim();
    filtered = q
      ? allRows.filter(function (r) { return r.dataset.search.includes(q); })
      : allRows.slice();
    curPage = 1;
    render();
  };

  window.applyRpp = function () {
    rpp     = parseInt(document.getElementById('empRpp').value, 10) || 10;
    curPage = 1;
    render();
  };

  window.goPage = function (p) {
    curPage = p;
    render();
  };

  function render() {
    var total = filtered.length;
    var pages = Math.max(1, Math.ceil(total / rpp));
    curPage   = Math.min(curPage, pages);
    var start = (curPage - 1) * rpp;
    var end   = Math.min(start + rpp, total);

    allRows.forEach(function (r) { r.style.display = 'none'; });
    filtered.slice(start, end).forEach(function (r, idx) {
      r.style.display = '';
      var sno = r.querySelector('.emp-sno');
      if (sno) sno.textContent = start + idx + 1;
    });

    var noRes = document.getElementById('empNoResults');
    if (noRes) noRes.style.display = total === 0 ? 'block' : 'none';

    var info = document.getElementById('empPgInfo');
    if (info) {
      info.textContent = total === 0
        ? 'No records found'
        : 'Showing ' + (start + 1) + '–' + end + ' of ' + total + ' records';
    }

    renderNav(curPage, pages);
  }

  function renderNav(cur, pages) {
    var nav = document.getElementById('empNav');
    if (!nav) return;
    var html = '';
    html += '<button class="pg-btn" onclick="goPage(' + (cur - 1) + ')"' + (cur <= 1 ? ' disabled' : '') + '>Prev</button>';
    buildPageNums(cur, pages).forEach(function (p) {
      if (p === '...') {
        html += '<span class="pg-dots">…</span>';
      } else {
        html += '<button class="pg-btn' + (p === cur ? ' pg-active' : '') + '" onclick="goPage(' + p + ')">' + p + '</button>';
      }
    });
    html += '<button class="pg-btn" onclick="goPage(' + (cur + 1) + ')"' + (cur >= pages ? ' disabled' : '') + '>Next</button>';
    nav.innerHTML = html;
  }

  function buildPageNums(cur, pages) {
    if (pages <= 7) {
      var a = [];
      for (var i = 1; i <= pages; i++) a.push(i);
      return a;
    }
    if (cur <= 4) return [1, 2, 3, 4, 5, '...', pages];
    if (cur >= pages - 3) return [1, '...', pages - 4, pages - 3, pages - 2, pages - 1, pages];
    return [1, '...', cur - 1, cur, cur + 1, '...', pages];
  }

  document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', init)
    : init();
})();


/* ═══════════════════════════════════════════════════════
   EMAIL AJAX VALIDATION
   ═══════════════════════════════════════════════════════ */
var _emailTimer   = null;
var _emailValid   = true;   /* tracks current validity so submit can be blocked */
var _currentEmpId = 0;      /* 0 = add, >0 = edit (excludes self from check)   */

function attachEmailValidation() {
  var emailField = document.getElementById('fEmpEmail');
  if (!emailField) return;

  emailField.addEventListener('input', function () {
    clearTimeout(_emailTimer);
    clearEmailState();
    var val = emailField.value.trim();
    if (!val || !val.includes('@')) return;

    _emailTimer = setTimeout(function () {
      checkEmail(val);
    }, 400);   /* debounce 400 ms */
  });

  emailField.addEventListener('blur', function () {
    clearTimeout(_emailTimer);
    var val = emailField.value.trim();
    if (val && val.includes('@')) checkEmail(val);
  });
}

function checkEmail(email) {
  var icon  = document.getElementById('emailCheckIcon');
  var hint  = document.getElementById('emailHint');
  var field = document.getElementById('fEmpEmail');

  icon.style.display = 'inline';
  icon.textContent   = '…';
  icon.style.color   = '#9ca3af';

  var url = 'ajax/check_emp_email.php?email=' + encodeURIComponent(email)
          + '&exclude_id=' + _currentEmpId;

  fetch(url)
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.exists) {
        /* ── Email already taken ── */
        field.classList.add('email-err');
        field.classList.remove('email-ok');
        icon.textContent   = '✗';
        icon.style.color   = '#dc2626';
        hint.style.display = 'block';
        hint.style.color   = '#dc2626';
        hint.innerHTML     = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:-1px"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> This email is already registered.';
        _emailValid = false;
      } else {
        /* ── Email available ── */
        field.classList.add('email-ok');
        field.classList.remove('email-err');
        icon.textContent   = '✓';
        icon.style.color   = '#16a34a';
        hint.style.display = 'none';
        _emailValid = true;
      }
    })
    .catch(function () {
      clearEmailState();
      _emailValid = true;
    });
}

function clearEmailState() {
  var field = document.getElementById('fEmpEmail');
  var icon  = document.getElementById('emailCheckIcon');
  var hint  = document.getElementById('emailHint');
  if (!field) return;
  field.classList.remove('email-ok', 'email-err');
  icon.style.display = 'none';
  hint.style.display = 'none';
  _emailValid = true;
}

/* Block form submit if email fails validation */
document.getElementById('empForm').addEventListener('submit', function (e) {
  if (!_emailValid) {
    e.preventDefault();
    document.getElementById('fEmpEmail').focus();
  }
});

attachEmailValidation();


/* ═══════════════════════════════════════════════════════
   ADD / EDIT MODAL
   ═══════════════════════════════════════════════════════ */
var saveSvg = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>';

function openEmpModal(userId) {
  _currentEmpId = userId || 0;

  var emailField = document.getElementById('fEmpEmail');
  var pwRow      = document.getElementById('empPwRow');
  var pwField    = document.getElementById('fEmpPass');

  document.getElementById('empForm').reset();
  document.getElementById('fEmpId').value = _currentEmpId;
  clearEmailState();

  if (_currentEmpId > 0) {
    /* ── Edit mode ── */
    document.getElementById('empModalTitle').textContent = 'Edit Employee';
    document.getElementById('empSubmitBtn').innerHTML    = saveSvg + ' Update Employee';

    /* Hide password row entirely */
    pwRow.style.display      = 'none';
    pwField.required         = false;
    pwField.value            = '';

    /* Email: readonly + no validation needed */
    emailField.readOnly         = true;
    emailField.style.background = '#f8fafc';
    emailField.removeAttribute('required');

    /* Show "email locked" hint */
    var hint = document.getElementById('emailHint');
    hint.style.display = 'block';
    hint.style.color   = '#f59e0b';
    hint.innerHTML     = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:-1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Email cannot be changed after creation.';
    _emailValid = true;

    /* Prefill */
    var d = Object.values(EMP_DATA).find(function (e) { return e.id === _currentEmpId; });
    if (d) {
      document.getElementById('fEmpName').value    = d.name;
      emailField.value                             = d.email;
      document.getElementById('fEmpDesig').value   = d.desig;
      document.getElementById('fEmpMobile').value  = d.mobile;
      document.getElementById('fEmpCompany').value = d.company;
      document.getElementById('fEmpStatus').value  = d.status;
      var isdSel = document.getElementById('fEmpIsd');
      for (var i = 0; i < isdSel.options.length; i++) {
        if (parseInt(isdSel.options[i].value) === d.isd) { isdSel.selectedIndex = i; break; }
      }
      var roleSel = document.getElementById('fEmpRole');
      for (var j = 0; j < roleSel.options.length; j++) {
        if (parseInt(roleSel.options[j].value) === d.role_id) { roleSel.selectedIndex = j; break; }
      }
    }

  } else {
    /* ── Add mode ── */
    document.getElementById('empModalTitle').textContent = 'Add Employee';
    document.getElementById('empSubmitBtn').innerHTML    = saveSvg + ' Save Employee';

    pwRow.style.display         = '';
    pwField.required            = true;

    emailField.readOnly         = false;
    emailField.style.background = '';
    emailField.setAttribute('required', 'required');
  }

  openModal('empModal');
}


/* ═══════════════════════════════════════════════════════
   RESET PASSWORD MODAL
   ═══════════════════════════════════════════════════════ */
function openResetPwModal(userId, name) {
  document.getElementById('resetPwUserId').value  = userId;
  document.getElementById('resetPwSubtitle').textContent = 'Set a new password for ' + name + '.';
  document.getElementById('resetPwForm').reset();
  document.getElementById('resetPwMatch').style.display = 'none';
  openModal('resetPwModal');
}

/* Live confirm-match feedback */
document.getElementById('resetPwConfirm').addEventListener('input', function () {
  var newPw  = document.getElementById('resetPwNew').value;
  var conPw  = this.value;
  var msg    = document.getElementById('resetPwMatch');
  if (!conPw) { msg.style.display = 'none'; return; }
  msg.style.display = 'block';
  if (newPw === conPw) {
    msg.textContent = '✓ Passwords match';
    msg.style.color = '#16a34a';
  } else {
    msg.textContent = '✗ Passwords do not match';
    msg.style.color = '#dc2626';
  }
});


/* ═══════════════════════════════════════════════════════
   DELETE CONFIRM
   ═══════════════════════════════════════════════════════ */
function confirmDeleteEmp(userId, name) {
  document.getElementById('delEmpId').value         = userId;
  document.getElementById('delEmpName').textContent = name;
  openModal('deleteEmpModal');
}
</script>

<?php
$pageMainContent = ob_get_clean();
require_once __DIR__ . '/masterTemplate.php';
?>
