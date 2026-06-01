<?php
require_once '../common/functions.php';
require_once __DIR__ . '/account-helpers.php';
require_once '../controller/website_controller.php';

$user   = sinelec_get_signed_in_user();
$userId = (int)($user['USER_ID'] ?? 0);
if ($userId <= 0) { header('location:index'); exit; }

$orderNumber = htmlspecialchars(trim($_GET['order'] ?? ''));
$payType     = trim($_GET['pt'] ?? '');
if (!$orderNumber) { header('location:products'); exit; }

/* Bank details (for Bank Transfer orders) — fetched from DB */
$bankDetails = [];
if ($payType === 'Bank Transfer') {
    $ctrl = new WebsiteController();
    $bankDetails = $ctrl->getBankDetails();
}

$currentPage = 'order-success';
$pageTitle   = 'Order Confirmed — Sinelec Tech';
require_once 'header.php';
?>
<main>
<div class="wrap page-wrap" style="max-width:660px;margin:0 auto;padding:32px 16px 60px;">

  <div class="os-card">
    <div class="os-icon">
      <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        <polyline points="9 12 11 14 15 10"/>
      </svg>
    </div>
    <h1 class="os-title">Order Placed Successfully!</h1>
    <p class="os-sub">Thank you! A confirmation email has been sent to your registered email address.</p>

    <div class="os-order-chip">
      <span class="os-order-label">Order Number</span>
      <span class="os-order-val"><?= $orderNumber ?></span>
    </div>

    <?php if (strcasecmp($payType, 'Paypal') === 0): ?>
    <div class="os-notice os-notice--paypal">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <div>
        <strong>Payment Confirmed via PayPal</strong>
        <p>Your payment was captured successfully. A confirmation email has been sent to you.</p>
      </div>
    </div>
    <?php elseif ($payType === 'Bank Transfer'): ?>
    <!-- Bank Transfer Instructions -->
    <div class="os-bank-wrap">
      <div class="os-bank-head">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        Bank Transfer Instructions
      </div>
      <p class="os-bank-note">Please transfer the exact order amount to the bank account below. Include your order number <strong><?= $orderNumber ?></strong> as the payment reference.</p>

      <?php if (!empty($bankDetails)): ?>
        <?php foreach ($bankDetails as $b):
          $holder  = htmlspecialchars(trim((string)($b->ACCOUNT_HOLDER_NAME ?? '')));
          $bName   = htmlspecialchars(trim((string)($b->BANK_NAME           ?? '')));
          $branch  = htmlspecialchars(trim((string)($b->BRANCH_NAME         ?? '')));
          $acct    = htmlspecialchars(trim((string)($b->ACCOUNT_NUMBER      ?? '')));
          $swift   = htmlspecialchars(trim((string)($b->SWIFT_CODE          ?? '')));
          $iban    = htmlspecialchars(trim((string)($b->IBAN_NUMBER         ?? '')));
          $cur     = htmlspecialchars(trim((string)($b->CURRENCY            ?? 'EURO')));
          $bAddr   = htmlspecialchars(trim((string)($b->BANK_ADDRESS        ?? '')));
        ?>
        <div class="os-bank-card">
          <?php if ($holder): ?><div class="os-bank-row"><span>Account Holder</span><strong><?= $holder ?></strong></div><?php endif; ?>
          <?php if ($bName):  ?><div class="os-bank-row"><span>Bank</span><strong><?= $bName ?><?= $branch ? ' — ' . $branch : '' ?></strong></div><?php endif; ?>
          <?php if ($acct):   ?><div class="os-bank-row"><span>Account Number</span><strong class="os-mono"><?= $acct ?></strong></div><?php endif; ?>
          <?php if ($iban):   ?><div class="os-bank-row"><span>IBAN</span><strong class="os-mono"><?= $iban ?></strong></div><?php endif; ?>
          <?php if ($swift):  ?><div class="os-bank-row"><span>SWIFT / BIC</span><strong class="os-mono"><?= $swift ?></strong></div><?php endif; ?>
          <?php if ($cur):    ?><div class="os-bank-row"><span>Currency</span><strong><?= $cur ?></strong></div><?php endif; ?>
          <?php if ($bAddr):  ?><div class="os-bank-row"><span>Bank Address</span><strong><?= $bAddr ?></strong></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
      <p style="font-size:13px;color:#92400e;margin:8px 0 0;">Our team will email you the bank details shortly.</p>
      <?php endif; ?>
    </div>

    <?php elseif ($payType === 'Invoice'): ?>
    <div class="os-notice os-notice--invoice">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <div>
        <strong>Invoice on the Way</strong>
        <p>An invoice will be sent to you separately. Please process payment as per your agreed terms.</p>
      </div>
    </div>
    <?php endif; ?>

    <div class="os-actions">
      <a href="my-orders" class="btn btn-blue os-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
        View My Orders
      </a>
      <a href="products" class="btn btn-outline os-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Continue Shopping
      </a>
    </div>
  </div>

</div>
</main>

<style>
.os-card{background:#fff;border-radius:20px;border:1px solid #e2eaf6;padding:36px 28px;text-align:center;}
.os-icon{width:72px;height:72px;background:linear-gradient(135deg,#1363bf,#1b7dd4);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;color:#fff;}
.os-title{font-size:22px;font-weight:800;color:#1a3352;margin:0 0 8px;}
.os-sub{font-size:13px;color:#5a748e;margin:0 0 20px;line-height:1.6;}
.os-order-chip{display:inline-flex;flex-direction:column;align-items:center;gap:3px;background:#f4f9ff;border:1px solid #c8dff8;border-radius:12px;padding:12px 28px;margin-bottom:22px;}
.os-order-label{font-size:10px;font-weight:700;color:#7a93b0;text-transform:uppercase;letter-spacing:.5px;}
.os-order-val{font-size:19px;font-weight:800;color:#1363bf;letter-spacing:.5px;}
/* Bank card */
.os-bank-wrap{text-align:left;background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:18px 20px;margin-bottom:22px;}
.os-bank-head{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:700;color:#92400e;margin-bottom:8px;}
.os-bank-head svg{color:#f59e0b;}
.os-bank-note{font-size:12px;color:#92400e;margin:0 0 14px;line-height:1.6;}
.os-bank-card{background:#fff;border:1px solid #fde68a;border-radius:10px;padding:14px 16px;display:flex;flex-direction:column;gap:8px;}
.os-bank-row{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;font-size:13px;border-bottom:1px solid #fef3c7;padding-bottom:7px;}
.os-bank-row:last-child{border-bottom:0;padding-bottom:0;}
.os-bank-row span{color:#92400e;min-width:130px;font-size:12px;}
.os-bank-row strong{color:#1a3352;text-align:right;word-break:break-all;}
.os-mono{font-family:monospace;letter-spacing:.5px;}
/* Invoice notice */
.os-notice{display:flex;align-items:flex-start;gap:10px;padding:14px 16px;border-radius:12px;text-align:left;margin-bottom:22px;font-size:13px;line-height:1.6;}
.os-notice--invoice{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;}
.os-notice--invoice svg{color:#3b82f6;flex-shrink:0;margin-top:2px;}
.os-notice--paypal{background:#f0f4ff;border:1px solid #bfdbfe;color:#003087;}
.os-notice--paypal svg{color:#003087;flex-shrink:0;margin-top:2px;}
.os-notice strong{display:block;margin-bottom:3px;font-size:14px;}
.os-notice p{margin:0;}
/* Actions */
.os-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;}
.os-btn{display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none;}
</style>

<?php require_once 'footer.php'; ?>
