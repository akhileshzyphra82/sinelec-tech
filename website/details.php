<?php
if (!class_exists('WebsiteController')) require_once '../controller/website_controller.php';

$controller = new WebsiteController();
$company    = $controller->getCompanyInfo();

/* ── Allowed keys and their display titles ─────────────────────── */
$pageMap = [
    'about_us'          => 'About Us',
    'legal_information' => 'Legal Information',
    'disclaimer'        => 'Disclaimer',
    'privacy_policy'    => 'Privacy Policy',
    'terms_of_use'      => 'Terms of Use',
];

$key = trim($_GET['key'] ?? '');

if ($key === '' || !array_key_exists($key, $pageMap)) {
    header('location:index'); exit();
}

$title   = $pageMap[$key];
$content = html_entity_decode((string)($company->{strtoupper($key)} ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

$currentPage = 'details';
$pageTitle   = $title . ' – Sinelec Tech';

require_once 'header.php';
?>

<style>
/* ── Hero ────────────────────────────────────────────────────── */
.dt-hero {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #1a56a0 100%);
  color: #fff;
  padding: 64px 0;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.dt-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse 70% 60% at 50% 0%, rgba(59,130,246,.18) 0%, transparent 70%);
  pointer-events: none;
}
.dt-breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  margin-bottom: 18px;
}
.dt-breadcrumb-link { color: #93c5fd; text-decoration: none; }
.dt-breadcrumb-link:hover { text-decoration: underline; }
.dt-breadcrumb-sep { color: rgba(255,255,255,.35); }
.dt-breadcrumb-cur { color: rgba(255,255,255,.6); }
.dt-hero-title {
  font-size: clamp(22px, 4vw, 36px);
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -.5px;
  margin: 0;
}

/* ── Content ─────────────────────────────────────────────────── */
.dt-content-wrap {
  max-width: 860px;
  margin: 0 auto;
  padding: 48px 0 64px;
}
.dt-content {
  font-size: 15px;
  line-height: 1.8;
  color: #374151;
}
.dt-content h1, .dt-content h2, .dt-content h3 {
  color: #0f172a;
  font-weight: 700;
  margin-top: 1.5em;
  margin-bottom: .5em;
}
.dt-content h1 { font-size: 22px; }
.dt-content h2 { font-size: 18px; }
.dt-content h3 { font-size: 16px; }
.dt-content p  { margin-bottom: 1em; }
.dt-content ul, .dt-content ol { padding-left: 22px; margin-bottom: 1em; }
.dt-content li { margin-bottom: .35em; }
.dt-content a  { color: var(--blue-pri); text-decoration: underline; }
.dt-content strong { color: #0f172a; }
.dt-empty {
  text-align: center;
  padding: 60px 20px;
  color: #94a3b8;
}
.dt-empty svg { margin: 0 auto 16px; display: block; opacity: .35; }
.dt-empty p   { font-size: 15px; }

@media (max-width: 900px) {
  .dt-content-wrap { padding: 32px 16px 48px; }
  .dt-content { padding: 24px 20px; }
}
</style>

<!-- ── Hero ──────────────────────────────────────────────────── -->
<section class="dt-hero">
  <div class="wrap" style="position:relative;">
    <nav class="dt-breadcrumb">
      <a href="index" class="dt-breadcrumb-link">Home</a>
      <span class="dt-breadcrumb-sep">›</span>
      <span class="dt-breadcrumb-cur"><?= htmlspecialchars($title) ?></span>
    </nav>
    <h1 class="dt-hero-title"><?= htmlspecialchars($title) ?></h1>
  </div>
</section>

<!-- ── Content ───────────────────────────────────────────────── -->
<div class="wrap">
  <div class="dt-content-wrap">
    <?php if ($content !== '' && $content !== '<p><br></p>'): ?>
    <div class="dt-content"><?= $content ?></div>
    <?php else: ?>
    <div class="dt-content dt-empty">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <p>This page is currently being updated. Please check back soon.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once 'footer.php'; ?>
