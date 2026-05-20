<?php
$sinelaKnowledge = require __DIR__ . '/knowledges.php';
$assistant = $sinelaKnowledge['assistant'] ?? [];
$company = $sinelaKnowledge['company'] ?? [];
/* Override bot name from tbl_company if available */
if (!isset($company) || !is_object($company)) {
    if (!class_exists('WebsiteController')) require_once __DIR__ . '/controller/website_controller.php';
    $_cbWc = new WebsiteController();
    $company = $_cbWc->getCompanyInfo();
    unset($_cbWc);
}
$_botName = trim((string)(is_object($company) ? ($company->BOT_NAME ?? '') : ''));
if ($_botName !== '') $assistant['name'] = $_botName;
?>
<div class="sinela-chatbot" id="sinelaChatbot" data-auto-open="true">
  <button
    type="button"
    class="sinela-chatbot-fab"
    id="sinelaChatbotFab"
    aria-label="<?= htmlspecialchars($assistant['open_label'] ?? 'Chat with Sinela AI') ?>"
  >
    <span class="sinela-fab-badge" id="sinelaBadge">1</span>
    <span class="sinela-fab-icon">
      <svg width="22" height="22" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <rect x="4" y="5" width="24" height="17" rx="5" stroke="currentColor" stroke-width="2.2"/>
        <path d="M10.5 26l2.5-4h6l2.5 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="12" cy="13.5" r="1.5" fill="currentColor"/>
        <circle cx="20" cy="13.5" r="1.5" fill="currentColor"/>
        <path d="M12 17.5c1 1.2 2.2 1.8 4 1.8s3-.6 4-1.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
    </span>
    <span class="sinela-fab-label"><?= htmlspecialchars($assistant['name'] ?? 'Sinela AI') ?></span>
    <span class="sinela-fab-pulse"></span>
  </button>

  <section class="sinela-chatbot-window" id="sinelaChatbotWindow" aria-label="<?= htmlspecialchars($assistant['name'] ?? 'Sinela AI') ?>" aria-hidden="true">
    <header class="sinela-chatbot-header">
      <div class="sinela-chatbot-brand">
        <span class="sinela-chatbot-brand-mark" aria-hidden="true">
          <svg width="24" height="24" viewBox="0 0 32 32" fill="none">
            <path d="M16 6.2V4.6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
            <circle cx="16" cy="3.4" r="1.6" fill="currentColor"/>
            <rect x="8" y="8" width="16" height="13.5" rx="5.2" stroke="currentColor" stroke-width="2.2"/>
            <path d="M12.6 23.4h6.8L16 27l-3.4-3.6z" fill="currentColor"/>
            <circle cx="13" cy="14" r="1.35" fill="currentColor"/>
            <circle cx="19" cy="14" r="1.35" fill="currentColor"/>
            <path d="M12.8 18c.9.9 1.9 1.3 3.2 1.3 1.2 0 2.3-.4 3.2-1.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M6.2 11.4c-1.2.8-2 2.2-2 3.9 0 1.7.8 3.1 2 3.9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" opacity=".9"/>
            <path d="M25.8 11.4c1.2.8 2 2.2 2 3.9 0 1.7-.8 3.1-2 3.9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" opacity=".9"/>
          </svg>
        </span>
        <div>
          <div class="sinela-chatbot-title"><?= htmlspecialchars($assistant['name'] ?? 'Sinela AI') ?></div>
          <div class="sinela-chatbot-status">
            <span class="sinela-chatbot-status-dot"></span>
            <?= htmlspecialchars($assistant['online_label'] ?? 'Online') ?>
          </div>
        </div>
      </div>
      <button
        type="button"
        class="sinela-chatbot-close"
        id="sinelaChatbotClose"
        aria-label="<?= htmlspecialchars($assistant['minimize_label'] ?? 'Minimize chat') ?>"
      >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </header>

    <div class="sinela-chatbot-body">
      <div class="sinela-chatbot-messages" id="sinelaChatbotMessages"></div>
    </div>

    <form class="sinela-chatbot-inputbar" id="sinelaChatbotForm">
      <input
        type="text"
        id="sinelaChatbotInput"
        class="sinela-chatbot-input"
        placeholder="<?= htmlspecialchars($assistant['placeholder'] ?? 'Ask a question...') ?>"
        autocomplete="off"
      >
      <button type="submit" class="sinela-chatbot-send" aria-label="<?= htmlspecialchars($assistant['send_label'] ?? 'Send') ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <line x1="22" y1="2" x2="11" y2="13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
          <polygon points="22 2 15 22 11 13 2 9 22 2" fill="currentColor"/>
        </svg>
      </button>
    </form>
  </section>
</div>

<script type="application/json" id="sinelaKnowledgeData"><?= json_encode($sinelaKnowledge, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
