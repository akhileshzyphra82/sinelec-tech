(function () {
  const root = document.getElementById('sinelaChatbot');
  const knowledgeNode = document.getElementById('sinelaKnowledgeData');
  if (!root || !knowledgeNode) return;

  let knowledge = null;
  try {
    knowledge = JSON.parse(knowledgeNode.textContent || '{}');
  } catch (error) {
    return;
  }
  if (!knowledge) return;

  const fab = document.getElementById('sinelaChatbotFab');
  const windowEl = document.getElementById('sinelaChatbotWindow');
  const closeBtn = document.getElementById('sinelaChatbotClose');
  const messagesEl = document.getElementById('sinelaChatbotMessages');
  const form = document.getElementById('sinelaChatbotForm');
  const input = document.getElementById('sinelaChatbotInput');

  if (!fab || !windowEl || !closeBtn || !messagesEl || !form || !input) return;

  const assistant = knowledge.assistant || {};
  const support = knowledge.support || {};
  const company = knowledge.company || {};
  const intents = Array.isArray(knowledge.intents) ? knowledge.intents : [];
  const suggestedQuestions = Array.isArray(knowledge.suggested_questions) ? knowledge.suggested_questions : [];
  const AUTO_OPEN_KEY = 'sinela_chatbot_auto_open';

  let hasAutoOpened = false;
  let suggestionsExpanded = false;
  let thinkingTimer = null;
  let isTouchTriggering = false;

  function setOpenState(isOpen) {
    root.classList.toggle('is-open', isOpen);
    root.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    windowEl.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    document.body.classList.toggle('sinela-chat-open', isOpen);
    if (isOpen) {
      window.setTimeout(() => input.focus(), 180);
    }
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function normalise(text) {
    return String(text || '')
      .toLowerCase()
      .replace(/[^a-z0-9+\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function stemToken(token) {
    return token
      .replace(/(ing|ers|ies|ied|ed|es|s)$/i, '')
      .trim();
  }

  function tokenize(text) {
    return normalise(text).split(' ').filter(Boolean).map(stemToken).filter(Boolean);
  }

  function buildBubbleHtml(message) {
    const paragraphs = [];
    if (message.text) {
      paragraphs.push(`<p>${escapeHtml(message.text)}</p>`);
    }
    if (Array.isArray(message.bullets) && message.bullets.length) {
      paragraphs.push(`<ul>${message.bullets.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`);
    }
    if (Array.isArray(message.links) && message.links.length) {
      paragraphs.push(
        `<div class="sinela-chatbot-links">${message.links
          .map(link => `<a class="sinela-chatbot-link" href="${escapeHtml(link.href || '#')}">${escapeHtml(link.label || 'Learn more')}</a>`)
          .join('')}</div>`
      );
    }
    return paragraphs.join('');
  }

  function appendMessage(role, message) {
    const item = document.createElement('div');
    item.className = `sinela-chatbot-message sinela-chatbot-message--${role}`;

    const bubble = document.createElement('div');
    bubble.className = 'sinela-chatbot-bubble';
    bubble.innerHTML = buildBubbleHtml(typeof message === 'string' ? { text: message } : message);

    item.appendChild(bubble);
    messagesEl.appendChild(item);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function appendThinkingMessage() {
    const item = document.createElement('div');
    item.className = 'sinela-chatbot-message sinela-chatbot-message--bot';
    item.id = 'sinelaChatbotThinking';

    const bubble = document.createElement('div');
    bubble.className = 'sinela-chatbot-bubble sinela-chatbot-bubble--thinking';
    bubble.innerHTML = `
      <span class="sinela-chatbot-thinking">
        <span></span>
        <span></span>
        <span></span>
      </span>
    `;

    item.appendChild(bubble);
    messagesEl.appendChild(item);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function removeThinkingMessage() {
    const thinking = document.getElementById('sinelaChatbotThinking');
    if (thinking && thinking.parentNode) {
      thinking.parentNode.removeChild(thinking);
    }
  }

  function addGreeting() {
    if (messagesEl.dataset.initialized === 'true') return;
    messagesEl.dataset.initialized = 'true';
    appendMessage('bot', {
      text: assistant.greeting || "Hi, I'm Sinela AI 👋 How can I help you today?",
    });
  }

  function renderSuggestionsInline() {
    messagesEl.querySelectorAll('.sinela-chatbot-suggestion-block').forEach(node => node.remove());

    const wrap = document.createElement('div');
    wrap.className = 'sinela-chatbot-suggestion-block';

    const quoteLink = document.createElement('a');
    quoteLink.className = 'sinela-chatbot-chip sinela-chatbot-chip--whatsapp';
    quoteLink.href = company.whatsapp_link || '#';
    quoteLink.target = '_blank';
    quoteLink.rel = 'noopener';
    quoteLink.innerHTML = `
      <span class="sinela-chatbot-chip-icon" aria-hidden="true">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
          <path d="M20.52 3.48A11.8 11.8 0 0012.12 0C5.55 0 .2 5.34.2 11.92c0 2.1.55 4.15 1.6 5.95L0 24l6.33-1.75a11.9 11.9 0 005.79 1.49h.01c6.57 0 11.92-5.35 11.92-11.92 0-3.18-1.24-6.17-3.53-8.34zm-8.4 18.24h-.01a9.9 9.9 0 01-5.03-1.37l-.36-.21-3.76 1.04 1-3.66-.24-.38a9.87 9.87 0 01-1.52-5.22c0-5.45 4.44-9.89 9.91-9.89 2.64 0 5.12 1.02 6.98 2.88a9.8 9.8 0 012.9 7c0 5.46-4.45 9.9-9.87 9.9zm5.43-7.42c-.3-.15-1.76-.86-2.03-.96-.27-.1-.47-.15-.67.15s-.77.96-.94 1.16c-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.46a8.97 8.97 0 01-1.67-2.08c-.18-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.23-.24-.57-.49-.5-.67-.51h-.57c-.2 0-.52.08-.8.38-.27.3-1.03 1.01-1.03 2.46 0 1.44 1.05 2.84 1.2 3.03.15.2 2.06 3.15 5 4.41.7.3 1.24.47 1.67.6.7.22 1.34.19 1.85.12.56-.08 1.76-.72 2-1.42.25-.7.25-1.3.17-1.42-.07-.12-.27-.2-.57-.35z"/>
        </svg>
      </span>
      <span>Get instant quotes on WhatsApp</span>
    `;
    wrap.appendChild(quoteLink);

    const visibleQuestions = suggestionsExpanded ? suggestedQuestions : suggestedQuestions.slice(0, 4);
    visibleQuestions.forEach(question => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'sinela-chatbot-chip';
      chip.textContent = question;
      chip.addEventListener('click', event => {
        event.stopPropagation();
        handleQuestion(question);
      });
      wrap.appendChild(chip);
    });

    if (suggestedQuestions.length > 4) {
      const moreBtn = document.createElement('button');
      moreBtn.type = 'button';
      moreBtn.className = 'sinela-chatbot-chip sinela-chatbot-chip--more';
      moreBtn.innerHTML = suggestionsExpanded ? 'Less <span aria-hidden="true">−</span>' : 'More <span aria-hidden="true">+</span>';
      moreBtn.addEventListener('click', event => {
        event.stopPropagation();
        suggestionsExpanded = !suggestionsExpanded;
        renderSuggestionsInline();
      });
      wrap.appendChild(moreBtn);
    }

    messagesEl.appendChild(wrap);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function getFallbackMessage() {
    const fallback = support.fallback || {};
    return {
      text: fallback.message || 'Please contact support for more details.',
      links: [
        { label: fallback.whatsapp_label || 'Chat on WhatsApp', href: company.whatsapp_link || '#' },
        { label: `${fallback.phone_label || 'Call'}: ${support.contact?.phone || company.phone || ''}`, href: `tel:${(support.contact?.phone || company.phone || '').replace(/[^+\d]/g, '')}` },
        { label: `${fallback.email_label || 'Email'}: ${support.contact?.email || company.email || ''}`, href: `mailto:${support.contact?.email || company.email || ''}` },
      ],
    };
  }

  function scoreIntent(intent, query) {
    const normalizedQuery = normalise(query);
    const queryTokens = tokenize(query);
    if (!normalizedQuery || !queryTokens.length) return 0;

    let score = 0;
    const keywords = Array.isArray(intent.keywords) ? intent.keywords : [];
    keywords.forEach(keyword => {
      const normalizedKeyword = normalise(keyword);
      if (!normalizedKeyword) return;
      if (normalizedQuery.includes(normalizedKeyword)) {
        score += normalizedKeyword.split(' ').length > 1 ? 6 : 4;
      }
    });

    const questionTokens = tokenize(intent.question || '');
    questionTokens.forEach(token => {
      if (queryTokens.includes(token)) score += 1.3;
    });

    const keywordTokens = keywords.flatMap(keyword => tokenize(keyword));
    queryTokens.forEach(token => {
      if (keywordTokens.includes(token)) {
        score += 1.6;
        return;
      }

      if (keywordTokens.some(keywordToken => keywordToken.startsWith(token) || token.startsWith(keywordToken))) {
        score += 0.9;
      }
    });

    queryTokens.forEach(token => {
      if (questionTokens.some(questionToken => questionToken.startsWith(token) || token.startsWith(questionToken))) {
        score += 0.6;
      }
    });

    return score;
  }

  function resolveAnswer(query) {
    let bestIntent = null;
    let bestScore = 0;

    intents.forEach(intent => {
      const score = scoreIntent(intent, query);
      if (score > bestScore) {
        bestIntent = intent;
        bestScore = score;
      }
    });

    if (!bestIntent || bestScore < 3) {
      return getFallbackMessage();
    }
    return bestIntent.answer || getFallbackMessage();
  }

  function handleQuestion(question) {
    const trimmed = String(question || '').trim();
    if (!trimmed) return;
    if (thinkingTimer) {
      clearTimeout(thinkingTimer);
      thinkingTimer = null;
    }
    removeThinkingMessage();
    appendMessage('user', { text: trimmed });
    appendThinkingMessage();
    thinkingTimer = window.setTimeout(() => {
      removeThinkingMessage();
      appendMessage('bot', resolveAnswer(trimmed));
      renderSuggestionsInline();
      thinkingTimer = null;
    }, 1000);
    input.value = '';
  }

  function openChat(event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    addGreeting();
    setOpenState(true);
  }

  fab.addEventListener('click', event => {
    if (isTouchTriggering) return;
    openChat(event);
  });

  fab.addEventListener('touchend', event => {
    isTouchTriggering = true;
    openChat(event);
    window.setTimeout(() => {
      isTouchTriggering = false;
    }, 250);
  }, { passive: false });

  closeBtn.addEventListener('click', () => {
    setOpenState(false);
    try {
      localStorage.setItem(AUTO_OPEN_KEY, 'dismissed');
    } catch {}
  });

  form.addEventListener('submit', event => {
    event.preventDefault();
    handleQuestion(input.value);
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && root.classList.contains('is-open')) {
      setOpenState(false);
    }
  });

  addGreeting();
  renderSuggestionsInline();

  let shouldAutoOpen = root.dataset.autoOpen === 'true';
  try {
    shouldAutoOpen = shouldAutoOpen && localStorage.getItem(AUTO_OPEN_KEY) !== 'dismissed';
  } catch {}

  if (shouldAutoOpen && !hasAutoOpened) {
    hasAutoOpened = true;
    window.setTimeout(() => setOpenState(true), 700);
  }
})();
