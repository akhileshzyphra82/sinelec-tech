<?php
if (!class_exists('WebsiteController')) require_once '../controller/website_controller.php';

$controller = new WebsiteController();
$company    = $controller->getCompanyInfo();

$co_name    = htmlspecialchars((string)($company->NAME             ?? 'Sinelec Technologies'));
$co_phone   = htmlspecialchars((string)($company->CONTACT_NUMBER   ?? ''));
$co_email   = htmlspecialchars((string)($company->EMAIL            ?? ''));
$co_address = htmlspecialchars((string)($company->ADDRESS          ?? ''));
$co_fax     = htmlspecialchars((string)($company->FAX              ?? ''));
$co_wp      = htmlspecialchars((string)($company->WHATSAPP_NUMBER  ?? ''));
$co_support = htmlspecialchars((string)($company->SUPPORT_MAIL_ID  ?? ''));
$co_hrs     = htmlspecialchars((string)($company->OFFICE_HRS       ?? ''));
$co_mapUrl  = (string)($company->MAP_URL ?? '');
$co_branch  = html_entity_decode((string)($company->BRANCH_OFFICE_ADDRESS ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$co_fb      = htmlspecialchars((string)($company->FACEBOOK_URL  ?? ''));
$co_li      = htmlspecialchars((string)($company->LINKEDIN_URL  ?? ''));
$co_tw      = htmlspecialchars((string)($company->TWITTER_URL   ?? ''));
$co_yt      = htmlspecialchars((string)($company->YOUTUBE_URL   ?? ''));
$co_ig      = htmlspecialchars((string)($company->INSTAGRAM_URL ?? ''));

$showHelp    = isset($_GET['help']) && $_GET['help'] === '1';
$currentPage = 'contact-us';
$pageTitle   = $showHelp ? 'Technical Help & Support – Sinelec Tech' : 'Contact Us – Sinelec Tech';

require_once 'header.php';
?>

<style>
/* ── Hero ────────────────────────────────────────────────────── */
.cu-hero {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #1a56a0 100%);
  color: #fff;
  padding: 64px 0;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.cu-hero::before {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(ellipse 70% 60% at 50% 0%, rgba(59,130,246,.18) 0%, transparent 70%);
  pointer-events: none;
}
.cu-breadcrumb {
  display: flex; align-items: center; gap: 6px;
  font-size: 13px; margin-bottom: 18px;
}
.cu-breadcrumb-link { color: #93c5fd; text-decoration: none; }
.cu-breadcrumb-link:hover { text-decoration: underline; }
.cu-breadcrumb-sep { color: rgba(255,255,255,.35); }
.cu-breadcrumb-cur { color: rgba(255,255,255,.6); }
.cu-hero-title {
  font-size: clamp(22px, 4vw, 36px);
  font-weight: 800; line-height: 1.2;
  letter-spacing: -.5px; margin: 0;
}

/* ── Layout ──────────────────────────────────────────────────── */
.cu-wrap {
  padding: 52px 0 64px;
}
.cu-grid {
  display: grid;
  grid-template-columns: 340px 1fr;
  gap: 32px;
  align-items: start;
}
@media (max-width: 860px) {
  .cu-grid { grid-template-columns: 1fr; }
}

/* ── Contact card ────────────────────────────────────────────── */
.cu-card {
  background: #fff;
  border: 1.5px solid #e8edf4;
  border-radius: 16px;
  overflow: hidden;
}
.cu-card-head {
  background: linear-gradient(135deg, #0f172a, #1e3a5f);
  color: #fff;
  padding: 20px 24px;
}
.cu-card-head-title {
  font-size: 15px; font-weight: 700; margin-bottom: 2px;
}
.cu-card-head-sub {
  font-size: 12px; color: #93c5fd;
}
.cu-block {
  padding: 18px 24px;
  border-bottom: 1px solid #f1f5f9;
}
.cu-block:last-child { border-bottom: none; }
.cu-block-title {
  font-size: 12px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .6px;
  color: var(--blue-pri); margin-bottom: 10px;
}
.cu-row {
  display: flex; align-items: flex-start;
  gap: 10px; margin-bottom: 8px;
}
.cu-row:last-child { margin-bottom: 0; }
.cu-row-icon {
  width: 28px; height: 28px;
  border-radius: 8px;
  display: grid; place-items: center;
  flex-shrink: 0; margin-top: 1px;
}
.cu-row-label {
  font-size: 11px; font-weight: 600;
  color: #94a3b8; text-transform: uppercase;
  letter-spacing: .4px; margin-bottom: 2px;
}
.cu-row-val {
  font-size: 13px; color: #1e293b; line-height: 1.5;
  word-break: break-word;
}
.cu-row-val a { color: var(--blue-pri); text-decoration: none; }
.cu-row-val a:hover { text-decoration: underline; }
.cu-address-text { font-size: 13px; color: #374151; line-height: 1.7; white-space: pre-line; }

/* ── Social icons ────────────────────────────────────────────── */
.cu-socials {
  display: flex; gap: 8px; flex-wrap: wrap;
  padding: 16px 24px;
}
.cu-soc {
  width: 34px; height: 34px;
  border-radius: 10px;
  background: #f1f5f9;
  display: grid; place-items: center;
  color: #475569;
  transition: background .15s, color .15s;
  text-decoration: none;
}
.cu-soc:hover { background: #2563eb; color: #fff; }

/* ── Map ─────────────────────────────────────────────────────── */
.cu-map-wrap {
  border-radius: 16px;
  overflow: hidden;
  border: 1.5px solid #e8edf4;
  background: #f8fafc;
  min-height: 420px;
  display: flex; flex-direction: column;
}
.cu-map-head {
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
  background: #fff;
  display: flex; align-items: center; gap: 10px;
}
.cu-map-head-title { font-size: 14px; font-weight: 700; color: #0f172a; }
.cu-map-head-sub   { font-size: 12px; color: #64748b; margin-top: 1px; }
.cu-map-frame {
  flex: 1;
  min-height: 380px;
  display: block;
}
.cu-map-frame iframe {
  width: 100% !important;
  height: 100% !important;
  min-height: 380px;
  border: none !important;
  display: block;
}
.cu-map-empty {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  padding: 40px; text-align: center; color: #94a3b8; gap: 12px;
}
.cu-map-empty svg { opacity: .3; }
.cu-map-empty p { font-size: 13px; }

/* ── Help section ───────────────────────────────────────────── */
.cu-help {
  margin-top: 40px;
  border-top: 1.5px solid #e8edf4;
  padding-top: 36px;
}
.cu-help-hd { margin-bottom: 24px; }
.cu-help-title {
  font-size: 20px; font-weight: 800; color: #0f172a;
}
.cu-help-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px 48px;
}
@media (max-width: 640px) {
  .cu-help-grid { grid-template-columns: 1fr; }
}
.cu-help-group-title {
  font-size: 14px; font-weight: 700; color: #0f172a;
  margin-bottom: 10px;
}
.cu-help-links {
  list-style: none; padding: 0; margin: 0;
}
.cu-help-links li { margin-bottom: 8px; }
.cu-help-links a {
  font-size: 14px; color: #2563eb;
  text-decoration: none;
  transition: color .15s;
}
.cu-help-links a:hover { color: #1d4ed8; text-decoration: underline; }

/* ── Branch section ──────────────────────────────────────────── */
.cu-branch {
  margin-top: 32px;
  background: #fff;
  border: 1.5px solid #e8edf4;
  border-radius: 16px;
  overflow: hidden;
}
.cu-branch-head {
  display: flex; align-items: center; gap: 10px;
  padding: 16px 24px;
  border-bottom: 1px solid #f1f5f9;
  background: linear-gradient(135deg, #f8faff, #f3f4f6);
}
.cu-branch-icon {
  width: 36px; height: 36px;
  border-radius: 10px; background: #eff6ff;
  display: grid; place-items: center; flex-shrink: 0;
}
.cu-branch-title { font-size: 14px; font-weight: 700; color: #0f172a; }
.cu-branch-body {
  padding: 20px 24px;
  font-size: 14px; line-height: 1.8; color: #374151;
}
.cu-branch-body h1,.cu-branch-body h2,.cu-branch-body h3 {
  color: #0f172a; font-weight: 700; margin: 1em 0 .4em;
}
.cu-branch-body p  { margin-bottom: .8em; }
.cu-branch-body ul,.cu-branch-body ol { padding-left: 20px; margin-bottom: .8em; }
</style>

<!-- ── Hero ──────────────────────────────────────────────────── -->
<section class="cu-hero">
  <div class="wrap" style="position:relative;">
    <nav class="cu-breadcrumb">
      <a href="index" class="cu-breadcrumb-link">Home</a>
      <span class="cu-breadcrumb-sep">›</span>
      <?php if ($showHelp): ?>
      <a href="contact-us" class="cu-breadcrumb-link">Contact Us</a>
      <span class="cu-breadcrumb-sep">›</span>
      <span class="cu-breadcrumb-cur">Technical Help &amp; Support</span>
      <?php else: ?>
      <span class="cu-breadcrumb-cur">Contact Us</span>
      <?php endif; ?>
    </nav>
    <h1 class="cu-hero-title"><?= $showHelp ? 'Technical Help &amp; Support' : 'Contact Us' ?></h1>
  </div>
</section>

<!-- ── Body ──────────────────────────────────────────────────── -->
<div class="wrap">
  <div class="cu-wrap">
    <div class="cu-grid">

      <!-- ── Left: Contact Details ─────────────────────────── -->
      <div>
        <div class="cu-card">
          <div class="cu-card-head">
            <div class="cu-card-head-title"><?= $co_name ?></div>
            <div class="cu-card-head-sub">Get in touch with us</div>
          </div>

          <?php if ($co_address): ?>
          <div class="cu-block">
            <div class="cu-block-title">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              Local Address
            </div>
            <p class="cu-address-text"><?= $co_address ?></p>
          </div>
          <?php endif; ?>

          <?php if ($co_branch && $co_branch !== '<p><br></p>'): ?>
          <div class="cu-block">
            <div class="cu-block-title">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:4px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              Branch Office
            </div>
            <div style="font-size:13px;line-height:1.7;color:#374151;"><?= $co_branch ?></div>
          </div>
          <?php endif; ?>

          <?php if ($co_hrs): ?>
          <div class="cu-block">
            <div class="cu-block-title">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Office Hours
            </div>
            <div class="cu-row">
              <div class="cu-row-icon" style="background:#fff7ed;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              </div>
              <div>
                <div class="cu-row-label">Mon – Fri</div>
                <div class="cu-row-val"><?= $co_hrs ?></div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($co_phone || $co_fax || $co_email || $co_support || $co_wp): ?>
          <div class="cu-block">
            <div class="cu-block-title">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:4px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.51 19 19.43 19.43 0 0 1 4.36 12a19.79 19.79 0 0 1-2.29-7.93A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91A16 16 0 0 0 14.09 15.91l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              Customer Service &amp; Support
            </div>
            <?php if ($co_phone): ?>
            <div class="cu-row">
              <div class="cu-row-icon" style="background:#eff6ff;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.51 19 19.43 19.43 0 0 1 4.36 12a19.79 19.79 0 0 1-2.29-7.93A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91A16 16 0 0 0 14.09 15.91l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              </div>
              <div>
                <div class="cu-row-label">Tel</div>
                <div class="cu-row-val"><a href="tel:<?= preg_replace('/[^+\d]/', '', $co_phone) ?>"><?= $co_phone ?></a></div>
              </div>
            </div>
            <?php endif; ?>
            <?php if ($co_fax): ?>
            <div class="cu-row">
              <div class="cu-row-icon" style="background:#f0fdf4;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M6 9V2h12v20H6v-7"/><polyline points="6 15 2 15 2 9 6 9"/><line x1="10" y1="6" x2="14" y2="6"/><line x1="10" y1="10" x2="14" y2="10"/></svg>
              </div>
              <div>
                <div class="cu-row-label">Fax</div>
                <div class="cu-row-val"><?= $co_fax ?></div>
              </div>
            </div>
            <?php endif; ?>
            <?php if ($co_email): ?>
            <div class="cu-row">
              <div class="cu-row-icon" style="background:#fdf4ff;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9333ea" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              </div>
              <div>
                <div class="cu-row-label">Email</div>
                <div class="cu-row-val"><a href="mailto:<?= $co_email ?>"><?= $co_email ?></a></div>
              </div>
            </div>
            <?php endif; ?>
            <?php if ($co_support && $co_support !== $co_email): ?>
            <div class="cu-row">
              <div class="cu-row-icon" style="background:#fff7ed;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              </div>
              <div>
                <div class="cu-row-label">Support Email</div>
                <div class="cu-row-val"><?= $co_support ?></div>
              </div>
            </div>
            <?php endif; ?>
            <?php if ($co_wp): ?>
            <div class="cu-row">
              <div class="cu-row-icon" style="background:#f0fdf4;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="#16a34a"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
              </div>
              <div>
                <div class="cu-row-label">WhatsApp</div>
                <div class="cu-row-val"><a href="https://wa.me/<?= preg_replace('/[^+\d]/', '', $co_wp) ?>" target="_blank" rel="noopener"><?= $co_wp ?></a></div>
              </div>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <?php if ($co_fb || $co_li || $co_tw || $co_yt || $co_ig): ?>
          <div class="cu-socials">
            <?php if ($co_fb): ?><a href="<?= $co_fb ?>" class="cu-soc" target="_blank" rel="noopener" title="Facebook"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a><?php endif; ?>
            <?php if ($co_tw): ?><a href="<?= $co_tw ?>" class="cu-soc" target="_blank" rel="noopener" title="Twitter / X"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.264 5.633 5.9-5.633zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a><?php endif; ?>
            <?php if ($co_li): ?><a href="<?= $co_li ?>" class="cu-soc" target="_blank" rel="noopener" title="LinkedIn"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg></a><?php endif; ?>
            <?php if ($co_yt): ?><a href="<?= $co_yt ?>" class="cu-soc" target="_blank" rel="noopener" title="YouTube"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="#fff"/></svg></a><?php endif; ?>
            <?php if ($co_ig): ?><a href="<?= $co_ig ?>" class="cu-soc" target="_blank" rel="noopener" title="Instagram"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a><?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── Right: Map + Help ─────────────────────────────── -->
      <div>
        <div class="cu-map-wrap">
          <div class="cu-map-head">
            <div class="cu-row-icon" style="background:#eff6ff;width:36px;height:36px;border-radius:10px;display:grid;place-items:center;flex-shrink:0;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
              <div class="cu-map-head-title">Our Location</div>
              <div class="cu-map-head-sub"><?= $co_address ?: 'Find us on the map' ?></div>
            </div>
          </div>
          <?php if ($co_mapUrl !== ''):
            $mapEmbed = preg_replace('/\s*(width|height)=["\'][^"\']*["\']/', '', $co_mapUrl);
          ?>
          <div class="cu-map-frame"><?= $mapEmbed ?></div>
          <?php else: ?>
          <div class="cu-map-empty">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <p>Map not configured yet.<br>Add a Google Maps embed URL in Admin → Company Settings.</p>
          </div>
          <?php endif; ?>
        </div>

        <?php if ($showHelp): ?>
        <!-- ── How can we help you? ────────────────────────── -->
        <div class="cu-help">
          <div class="cu-help-hd">
            <h2 class="cu-help-title">How can we help you?</h2>
          </div>
          <div class="cu-help-grid">

            <div class="cu-help-col">
              <div class="cu-help-group-title">Pricing &amp; Availability</div>
              <ul class="cu-help-links">
                <li><a href="request-a-quote">Quote Request</a></li>
                <li><a href="quotation">View and Order Existing Quotes</a></li>
              </ul>
              <div class="cu-help-group-title" style="margin-top:20px;">Technical Support</div>
              <ul class="cu-help-links">
                <li><a href="contact-us?help=1#contact">Request a Datasheet</a></li>
                <li><a href="contact-us?help=1#contact">Request Technical Information about a Product</a></li>
              </ul>
            </div>

            <div class="cu-help-col">
              <div class="cu-help-group-title">Placing Orders &amp; After Service</div>
              <ul class="cu-help-links">
                <li><a href="quotation">View and Track Open Orders</a></li>
                <li><a href="contact-us?help=1#contact">Change my Order</a></li>
                <li><a href="contact-us?help=1#contact">Resolve an Issue on My Order</a></li>
                <li><a href="contact-us?help=1#contact">Request a Return Authorisation</a></li>
                <li><a href="contact-us?help=1#contact">Obtain an Invoice Copy</a></li>
              </ul>
            </div>

          </div>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /.cu-grid -->

  </div><!-- /.cu-wrap -->
</div>

<?php require_once 'footer.php'; ?>
