<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../common/functions.php';

if (!empty($_SESSION['sinelec_admin']['USER_ID'])) {
    header('location:welcome');
    exit();
}

$flashToast       = sinelec_consume_flash();
$flashMsg         = (string)($flashToast['message'] ?? '');
$flashType        = (string)($flashToast['type']    ?? 'ok');
$turnstileSiteKey = sinelec_env('SITE_KEY', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Sinelec Technologies</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php if ($turnstileSiteKey !== ''): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:        #f0f2f5;
    --surface:   #ffffff;
    --border:    #d9dee7;
    --border-hi: #1a56db;
    --text:      #111827;
    --text-muted:#6b7280;
    --accent:    #1a56db;
    --accent-h:  #1648c0;
    --radius:    10px;
    --input-h:   44px;
  }

  html, body {
    height: 100%;
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--text);
    font-size: 14px;
    line-height: 1.5;
  }

  body {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 24px 16px;
  }

  /* ── Card ── */
  .card {
    width: 100%;
    max-width: 400px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 36px 32px 28px;
    box-shadow: 0 4px 24px rgba(0,0,0,.07);
  }

  .card-title {
    font-size: 20px; font-weight: 700;
    margin-bottom: 4px;
    color: var(--text);
  }
  .card-sub {
    color: var(--text-muted);
    margin-bottom: 28px;
    font-size: 13px;
  }

  /* ── Toast ── */
  .toast {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 11px 14px;
    border-radius: var(--radius);
    margin-bottom: 20px;
    font-size: 13px;
    line-height: 1.45;
    border: 1px solid transparent;
  }
  .toast-icon { flex-shrink: 0; margin-top: 1px; }
  .toast--err  { background: #fff5f5; border-color: #fecaca; color: #b91c1c; }
  .toast--warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
  .toast--ok   { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }

  /* ── Form ── */
  .field { margin-bottom: 18px; }
  .field label {
    display: block;
    font-size: 13px; font-weight: 500;
    margin-bottom: 6px;
    color: var(--text);
  }
  .input-wrap { position: relative; }
  .input-icon {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); pointer-events: none;
    display: flex; align-items: center;
  }
  .field input {
    width: 100%;
    height: var(--input-h);
    background: #f9fafb;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    font-family: inherit;
    font-size: 14px;
    padding: 0 40px 0 38px;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
  }
  .field input:focus {
    border-color: var(--border-hi);
    box-shadow: 0 0 0 3px rgba(26,86,219,.1);
    background: #fff;
  }
  .field input::placeholder { color: #9ca3af; }

  .pass-eye {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); padding: 4px;
    display: flex; align-items: center;
    transition: color .15s;
  }
  .pass-eye:hover { color: var(--text); }

  /* ── Captcha ── */
  .captcha-wrap { margin-bottom: 20px; }
  .captcha-missing {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 12px;
    border: 1px dashed var(--border);
    border-radius: var(--radius);
    color: var(--text-muted);
    font-size: 12px;
  }

  /* ── Submit ── */
  .btn-submit {
    width: 100%;
    height: 44px;
    background: var(--accent);
    border: none;
    border-radius: var(--radius);
    color: #fff;
    font-family: inherit;
    font-size: 14px; font-weight: 600;
    cursor: pointer;
    transition: background .15s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
  }
  .btn-submit:hover { background: var(--accent-h); }
  .btn-submit:active { opacity: .9; }

  /* ── Footer links ── */
  .card-footer {
    margin-top: 20px;
    text-align: center;
    font-size: 13px;
    color: var(--text-muted);
  }
  .card-footer a {
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;
    transition: opacity .15s;
  }
  .card-footer a:hover { opacity: .75; }

  /* ── Page footer ── */
  .page-footer {
    margin-top: 24px;
    color: var(--text-muted);
    font-size: 12px;
    text-align: center;
  }
</style>
</head>
<body>

  <!-- Card -->
  <div class="card">
    <div class="card-title">Admin Sign In</div>
    <div class="card-sub">Enter your credentials to access the admin panel.</div>

    <?php if ($flashMsg !== ''): ?>
    <div class="toast toast--<?= htmlspecialchars($flashType === 'ok' ? 'ok' : ($flashType === 'warn' ? 'warn' : 'err')) ?>">
      <span class="toast-icon">
        <?php if ($flashType === 'ok'): ?>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <?php elseif ($flashType === 'warn'): ?>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <?php else: ?>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?php endif; ?>
      </span>
      <span><?= htmlspecialchars($flashMsg) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" action="service?urlstring=<?= EncryptURL('action=Login') ?>" novalidate>

      <div class="field">
        <label for="adminUserId">Email Address</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6l8 6 8-6"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
          </span>
          <input type="email" id="adminUserId" name="adminUserId"
                 placeholder="admin@sinelec-tech.com"
                 value="<?= htmlspecialchars($_GET['prefill'] ?? '') ?>"
                 autocomplete="username" required>
        </div>
      </div>

      <div class="field">
        <label for="adminPassword">Password</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V8a5 5 0 0 1 10 0v3"/></svg>
          </span>
          <input type="password" id="adminPassword" name="adminPassword"
                 placeholder="••••••••"
                 autocomplete="current-password" required>
          <button type="button" class="pass-eye" id="passToggle" aria-label="Show password">
            <svg id="eyeOpen" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg id="eyeClosed" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
      </div>

      <div class="captcha-wrap">
        <?php if ($turnstileSiteKey !== ''): ?>
          <div class="cf-turnstile"
               data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>"
               data-theme="light"
               data-size="flexible"
               data-action="admin_login">
          </div>
        <?php else: ?>
          <div class="captcha-missing">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Captcha configuration missing (SITE_KEY not set in .env)
          </div>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn-submit">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Sign In
      </button>

    </form>

    <div class="card-footer">
      <a href="../website/forgot-password">Forgot your password?</a>
    </div>
  </div>

  <div class="page-footer">&copy; <?= date('Y') ?> Sinelec Technologies. Admin Panel.</div>

<script>
  const passInput  = document.getElementById('adminPassword');
  const passToggle = document.getElementById('passToggle');
  const eyeOpen    = document.getElementById('eyeOpen');
  const eyeClosed  = document.getElementById('eyeClosed');

  passToggle.addEventListener('click', function () {
    const isPass = passInput.type === 'password';
    passInput.type          = isPass ? 'text' : 'password';
    eyeOpen.style.display   = isPass ? 'none' : '';
    eyeClosed.style.display = isPass ? ''     : 'none';
  });
</script>
</body>
</html>
