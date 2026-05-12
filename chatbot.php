<?php
$sinelaKnowledge = require __DIR__ . '/knowledges.php';
$assistant = $sinelaKnowledge['assistant'] ?? [];
$company = $sinelaKnowledge['company'] ?? [];
?>
<div class="sinela-chatbot" id="sinelaChatbot" data-auto-open="true">
  <button
    type="button"
    class="sinela-chatbot-fab"
    id="sinelaChatbotFab"
    aria-label="<?= htmlspecialchars($assistant['open_label'] ?? 'Open chatbot') ?>"
  >
    <span class="sinela-chatbot-fab-ring"></span>
    <span class="sinela-chatbot-fab-core">
      <svg width="28" height="28" viewBox="0 0 32 32" fill="none" aria-hidden="true">
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
