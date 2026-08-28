/**
 * NumisTR AI Asistan Widget v2 (ADR-003 Faz 1)
 * Backend: /api/index.php/v1/assistant/chat (same-origin, anonymous cookie identity)
 *
 * Kullanım: Sayfanın sonuna bu scripti ekleyin (yalnız numistr.org üzerinde çalışır)
 * <script src="/media/numistr-chatbot/numistr-chatbot.js" defer></script>
 */

(function() {
    'use strict';

    // ------------------------------------------------------------------
    // Configuration (v2 — NumisTR AI Asistan, ADR-003 Faz 1)
    // Backend: same-origin Joomla plugin /v1/assistant/* (anonymous identity via
    // the nt_aid cookie, SameSite=Lax -> the widget MUST run on numistr.org).
    // ------------------------------------------------------------------
    const CONFIG = Object.assign({
        // Faz 2b: varsayilan uc SITE uygulamasindaki com_ajax koprusudur.
        // Neden: /api/index.php (Joomla API uygulamasi) site oturum cerezini
        // kimlik saymaz -> giris yapmis kullanici asistanda anonim gorunuyordu.
        // group=webservices sart: bu eklenti grubu site uygulamasinda otomatik
        // yuklenmez. Eski davranis icin apiMode: 'rest' + apiBase: '/api/...'.
        apiBase: '/index.php?option=com_ajax&group=webservices&plugin=numistr&format=json',
        apiMode: 'bridge',
        primaryColor: '#8B4513', // Saddle brown - antik tema
        secondaryColor: '#D4AF37', // Gold
        position: 'bottom-right', // bottom-right, bottom-left
        storageKey: 'numistr_assistant_conv', // localStorage: { tr: id, en: id }
        restoreHistory: true
    }, window.NumisTRAssistantConfig || {}); // mod_numistr_chat passes its params here

    if (document.getElementById('numistr-chatbot-widget')) { return; } // load once

    // Uc adresi: kopru modunda query-string, rest modunda yol tabanli.
    function endpoint(kind, id) {
        if (CONFIG.apiMode === 'rest') {
            return kind === 'chat'
                ? CONFIG.apiBase + '/chat'
                : CONFIG.apiBase + '/conversations/' + encodeURIComponent(id);
        }

        const sep = CONFIG.apiBase.indexOf('?') === -1 ? '?' : '&';

        return kind === 'chat'
            ? CONFIG.apiBase + sep + 'task=assistant.chat'
            : CONFIG.apiBase + sep + 'task=assistant.conversation&id=' + encodeURIComponent(id);
    }

    // Giris/kayit baglantisina donus adresi ekle -> kullanici ayni sayfaya doner
    // ve localStorage'daki conversation_id korundugu icin sohbet kaldigi yerden
    // devam eder (sunucu anonim konusmayi uyeye devralir).
    function withReturn(url) {
        if (!url) { return ''; }

        const sep = url.indexOf('?') === -1 ? '?' : '&';

        return url + sep + 'return=' + encodeURIComponent(window.location.pathname + window.location.search);
    }

    const I18N = {
        tr: {
            title: 'NumisTR Asistan',
            subtitle: 'Antik Anadolu Sikkeleri',
            placeholder: 'Sikkeler, darphaneler, yerleşimler...',
            welcome: 'Merhaba! Antik Anadolu sikkeleri, darphaneler, antik yerleşimler ve numizmatik terimler hakkında soru sorabilirsiniz.',
            error: 'Üzgünüm, bir hata oluştu. Lütfen tekrar deneyin.',
            thinking: 'Düşünüyorum...',
            sources: 'Kaynaklar',
            cta: 'Ücretsiz üye ol',
            ctaLogin: 'Giriş yap',
            badgeUser: 'Üye',
            badgePro: 'PRO',
            remaining: 'Bugün kalan: {n}',
            newChat: 'Yeni sohbet',
            send: 'Gönder',
            open: 'Asistanı aç'
        },
        en: {
            title: 'NumisTR Assistant',
            subtitle: 'Ancient Anatolian Coins',
            placeholder: 'Coins, mints, settlements...',
            welcome: 'Hello! Ask me about ancient Anatolian coins, mints, ancient settlements and numismatic terms.',
            error: 'Sorry, something went wrong. Please try again.',
            thinking: 'Thinking...',
            sources: 'Sources',
            cta: 'Register for free',
            ctaLogin: 'Sign in',
            badgeUser: 'Member',
            badgePro: 'PRO',
            remaining: 'Left today: {n}',
            newChat: 'New chat',
            send: 'Send',
            open: 'Open assistant'
        }
    };

    // Language: <html lang> first, then URL prefix, default tr
    const LANG = (function () {
        const htmlLang = (document.documentElement.getAttribute('lang') || '').toLowerCase();
        if (htmlLang.indexOf('en') === 0) return 'en';
        if (htmlLang.indexOf('tr') === 0) return 'tr';
        return /^\/en(\/|$)/.test(window.location.pathname) ? 'en' : 'tr';
    })();
    const T = I18N[LANG];

    // Conversation id persistence (per language)
    function loadConvId() {
        try {
            const raw = window.localStorage.getItem(CONFIG.storageKey);
            const obj = raw ? JSON.parse(raw) : {};
            return obj && obj[LANG] ? parseInt(obj[LANG], 10) || null : null;
        } catch (e) { return null; }
    }
    function saveConvId(id) {
        try {
            const raw = window.localStorage.getItem(CONFIG.storageKey);
            const obj = raw ? JSON.parse(raw) : {};
            if (id) { obj[LANG] = id; } else { delete obj[LANG]; }
            window.localStorage.setItem(CONFIG.storageKey, JSON.stringify(obj));
        } catch (e) { /* storage disabled */ }
    }
    let conversationId = loadConvId();
    let historyRestored = false;

    // Create widget HTML
    function createWidget() {
        const widget = document.createElement('div');
        widget.id = 'numistr-chatbot-widget';
        widget.innerHTML = `
            <style>
                #numistr-chatbot-widget {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
                    font-size: 14px;
                    line-height: 1.5;
                }

                #numistr-chat-button {
                    position: fixed;
                    ${CONFIG.position === 'bottom-right' ? 'right: 20px;' : 'left: 20px;'}
                    bottom: 20px;
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, ${CONFIG.primaryColor} 0%, ${CONFIG.secondaryColor} 100%);
                    border: none;
                    cursor: pointer;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: transform 0.3s ease, box-shadow 0.3s ease;
                }

                #numistr-chat-button:hover {
                    transform: scale(1.1);
                    box-shadow: 0 6px 20px rgba(0,0,0,0.4);
                }

                #numistr-chat-button svg {
                    width: 28px;
                    height: 28px;
                    fill: white;
                }

                #numistr-chat-button.open svg.chat-icon {
                    display: none;
                }

                #numistr-chat-button.open svg.close-icon {
                    display: block;
                }

                #numistr-chat-button svg.close-icon {
                    display: none;
                }

                #numistr-chat-panel {
                    position: fixed;
                    ${CONFIG.position === 'bottom-right' ? 'right: 20px;' : 'left: 20px;'}
                    bottom: 90px;
                    width: 380px;
                    max-width: calc(100vw - 40px);
                    height: 500px;
                    max-height: calc(100vh - 120px);
                    background: #fff;
                    border-radius: 16px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                    z-index: 9998;
                    display: none;
                    flex-direction: column;
                    overflow: hidden;
                }

                #numistr-chat-panel.open {
                    display: flex;
                    animation: slideUp 0.3s ease;
                }

                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                #numistr-chat-header {
                    background: linear-gradient(135deg, ${CONFIG.primaryColor} 0%, ${CONFIG.secondaryColor} 100%);
                    color: white;
                    padding: 16px 20px;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                #numistr-chat-header .avatar {
                    width: 40px;
                    height: 40px;
                    background: rgba(255,255,255,0.2);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                #numistr-chat-header .avatar svg {
                    width: 24px;
                    height: 24px;
                    fill: white;
                }

                #numistr-chat-header .title {
                    font-weight: 600;
                    font-size: 16px;
                }

                #numistr-chat-header .numistr-badge {
                    margin-left: auto;
                    margin-right: 8px;
                    padding: 2px 8px;
                    border-radius: 10px;
                    font-size: 11px;
                    font-weight: 600;
                    line-height: 1.6;
                    background: rgba(255, 255, 255, .18);
                    color: #fff;
                    white-space: nowrap;
                }
                #numistr-chat-header .numistr-badge.pro {
                    background: ${CONFIG.secondaryColor};
                    color: #3a2a08;
                }
                #numistr-chat-header .subtitle {
                    font-size: 12px;
                    opacity: 0.9;
                }

                #numistr-chat-messages {
                    flex: 1;
                    overflow-y: auto;
                    padding: 16px;
                    background: #f8f9fa;
                }

                .numistr-message {
                    margin-bottom: 12px;
                    display: flex;
                    flex-direction: column;
                }

                .numistr-message.user {
                    align-items: flex-end;
                }

                .numistr-message.bot {
                    align-items: flex-start;
                }

                .numistr-message .bubble {
                    max-width: 85%;
                    padding: 12px 16px;
                    border-radius: 16px;
                    word-wrap: break-word;
                }

                .numistr-message.user .bubble {
                    background: ${CONFIG.primaryColor};
                    color: white;
                    border-bottom-right-radius: 4px;
                }

                .numistr-message.bot .bubble {
                    background: white;
                    color: #333;
                    border-bottom-left-radius: 4px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }

                .numistr-message .time {
                    font-size: 11px;
                    color: #999;
                    margin-top: 4px;
                    padding: 0 8px;
                }

                .numistr-message.bot .bubble.thinking {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .thinking-dots {
                    display: flex;
                    gap: 4px;
                }

                .thinking-dots span {
                    width: 8px;
                    height: 8px;
                    background: ${CONFIG.primaryColor};
                    border-radius: 50%;
                    animation: bounce 1.4s infinite ease-in-out both;
                }

                .thinking-dots span:nth-child(1) { animation-delay: -0.32s; }
                .thinking-dots span:nth-child(2) { animation-delay: -0.16s; }

                @keyframes bounce {
                    0%, 80%, 100% { transform: scale(0); }
                    40% { transform: scale(1); }
                }

                #numistr-chat-input-container {
                    padding: 12px 16px;
                    background: white;
                    border-top: 1px solid #eee;
                    display: flex;
                    gap: 8px;
                }

                #numistr-chat-input {
                    flex: 1;
                    padding: 12px 16px;
                    border: 1px solid #ddd;
                    border-radius: 24px;
                    outline: none;
                    font-size: 14px;
                    transition: border-color 0.2s;
                }

                #numistr-chat-input:focus {
                    border-color: ${CONFIG.primaryColor};
                }

                #numistr-chat-send {
                    width: 44px;
                    height: 44px;
                    border: none;
                    background: ${CONFIG.primaryColor};
                    border-radius: 50%;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: background 0.2s;
                }

                #numistr-chat-send:hover {
                    background: ${CONFIG.secondaryColor};
                }

                #numistr-chat-send:disabled {
                    background: #ccc;
                    cursor: not-allowed;
                }

                #numistr-chat-send svg {
                    width: 20px;
                    height: 20px;
                    fill: white;
                }

                /* Mobile responsive */
                @media (max-width: 480px) {
                    #numistr-chat-panel {
                        width: calc(100vw - 20px);
                        height: calc(100vh - 100px);
                        ${CONFIG.position === 'bottom-right' ? 'right: 10px;' : 'left: 10px;'}
                        bottom: 80px;
                        border-radius: 12px;
                    }

                    #numistr-chat-button {
                        ${CONFIG.position === 'bottom-right' ? 'right: 10px;' : 'left: 10px;'}
                        bottom: 10px;
                        width: 56px;
                        height: 56px;
                    }
                }

                #numistr-chat-new {
                    margin-left: auto;
                    background: rgba(255,255,255,0.18);
                    color: #fff;
                    border: none;
                    border-radius: 50%;
                    width: 28px;
                    height: 28px;
                    font-size: 18px;
                    line-height: 1;
                    cursor: pointer;
                }
                #numistr-chat-new:hover { background: rgba(255,255,255,0.32); }
                #numistr-chat-footer {
                    font-size: 11px;
                    color: #777;
                    padding: 2px 14px 6px;
                    min-height: 14px;
                    text-align: right;
                }
                .numistr-message .bubble a { color: ${CONFIG.primaryColor}; text-decoration: underline; word-break: break-word; }
                .numistr-message.user .bubble a { color: #fff; }
                .numistr-message .bubble p { margin: 0 0 6px; }
                .numistr-message .bubble p:last-child { margin-bottom: 0; }
                .numistr-sources {
                    margin-top: 6px;
                    padding-top: 6px;
                    border-top: 1px solid rgba(0,0,0,0.08);
                    font-size: 12px;
                }
                .numistr-sources .label { font-weight: 600; color: #555; margin-bottom: 2px; }
                .numistr-sources a { display: block; color: ${CONFIG.primaryColor}; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                .numistr-sources a:hover { text-decoration: underline; }
                .numistr-cta {
                    display: inline-block;
                    margin-top: 8px;
                    padding: 6px 12px;
                    border-radius: 16px;
                    background: ${CONFIG.secondaryColor};
                    color: #222 !important;
                    font-weight: 600;
                    text-decoration: none !important;
                    font-size: 12px;
                }
                .numistr-cta:hover { filter: brightness(1.05); }
            </style>

            <button id="numistr-chat-button" aria-label="Sohbet aç">
                <svg class="chat-icon" viewBox="0 0 24 24">
                    <path d="M12 3c5.5 0 10 3.58 10 8s-4.5 8-10 8c-1.24 0-2.43-.18-3.53-.5C5.55 21 2 21 2 21c2.33-2.33 2.7-3.9 2.75-4.5C3.05 15.07 2 13.13 2 11c0-4.42 4.5-8 10-8z"/>
                </svg>
                <svg class="close-icon" viewBox="0 0 24 24">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>

            <div id="numistr-chat-panel">
                <div id="numistr-chat-header">
                    <div class="avatar">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="title">${T.title}</div>
                        <div class="subtitle">${T.subtitle}</div>
                    </div>
                    <span id="numistr-chat-badge" class="numistr-badge" hidden></span>
                    <button id="numistr-chat-new" type="button" title="${T.newChat}" aria-label="${T.newChat}">+</button>
                </div>

                <div id="numistr-chat-messages" aria-live="polite"></div>
                <div id="numistr-chat-footer"></div>

                <div id="numistr-chat-input-container">
                    <input type="text" id="numistr-chat-input" placeholder="${T.placeholder}" maxlength="1500" autocomplete="off" />
                    <button id="numistr-chat-send" aria-label="${T.send}">
                        <svg viewBox="0 0 24 24">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(widget);
        initializeWidget();
    }

    // Initialize widget interactions
    function initializeWidget() {
        const button = document.getElementById('numistr-chat-button');
        const panel = document.getElementById('numistr-chat-panel');
        const input = document.getElementById('numistr-chat-input');
        const sendBtn = document.getElementById('numistr-chat-send');
        const newBtn = document.getElementById('numistr-chat-new');
        const messages = document.getElementById('numistr-chat-messages');
        const footer = document.getElementById('numistr-chat-footer');
        const badge = document.getElementById('numistr-chat-badge');

        // Kimlik rozeti: anonimde gizli, uyede "Uye", Pro'da altin "PRO".
        function setIdentity(type) {
            if (!badge) { return; }

            if (type === 'pro') {
                badge.textContent = T.badgePro;
                badge.classList.add('pro');
                badge.hidden = false;
            } else if (type === 'user') {
                badge.textContent = T.badgeUser;
                badge.classList.remove('pro');
                badge.hidden = false;
            } else {
                badge.hidden = true;
            }
        }

        button.setAttribute('aria-label', T.open);

        // Toggle chat panel
        button.addEventListener('click', function() {
            const isOpen = panel.classList.toggle('open');
            button.classList.toggle('open', isOpen);

            if (isOpen && !historyRestored) {
                historyRestored = true;
                restoreHistory();
            }

            if (isOpen) {
                input.focus();
            }
        });

        newBtn.addEventListener('click', function() {
            conversationId = null;
            saveConvId(null);
            messages.innerHTML = '';
            footer.textContent = '';
            addMessage(T.welcome, 'bot');
            input.focus();
        });

        sendBtn.addEventListener('click', sendMessage);

        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        async function restoreHistory() {
            if (CONFIG.restoreHistory && conversationId) {
                try {
                    const r = await fetch(endpoint('conversation', conversationId), {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });

                    if (r.ok) {
                        const data = await r.json();
                        const list = Array.isArray(data.messages) ? data.messages : [];

                        if (list.length) {
                            addMessage(T.welcome, 'bot');
                            list.forEach(function(m) {
                                if (m.role === 'user' || m.role === 'assistant') {
                                    addMessage(m.content || '', m.role === 'user' ? 'user' : 'bot', null, m.created);
                                }
                            });
                            return;
                        }
                    }
                } catch (e) { /* fall through: start fresh */ }

                // 404 (cookie changed / expired) or error -> forget the id
                conversationId = null;
                saveConvId(null);
            }

            if (messages.children.length === 0) {
                addMessage(T.welcome, 'bot');
            }
        }

        async function sendMessage() {
            const query = input.value.trim();
            if (!query || sendBtn.disabled) return;

            addMessage(query, 'user');
            input.value = '';
            sendBtn.disabled = true;

            const thinkingId = addThinkingIndicator();

            try {
                const response = await fetch(endpoint('chat'), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        message: query,
                        lang: LANG,
                        conversation_id: conversationId
                    })
                });

                let data = null;
                try { data = await response.json(); } catch (e) { data = null; }

                removeThinkingIndicator(thinkingId);

                if (!response.ok || !data) {
                    const detail = data && data.errors && data.errors[0] && (data.errors[0].detail || data.errors[0].title);
                    addMessage(detail || T.error, 'bot');
                } else {
                    if (data.conversation_id) {
                        conversationId = parseInt(data.conversation_id, 10) || conversationId;
                        saveConvId(conversationId);
                    }

                    addMessage(data.answer || T.error, 'bot', data);

                    setIdentity(data.identity);

                    // Pro'da gunluk sayaci gostermenin anlami yok (1000/gun sistem
                    // sigortasi, kullanici limiti degil).
                    if (data.identity === 'pro') {
                        footer.textContent = '';
                    } else if (data.quota && typeof data.quota.remaining_today === 'number') {
                        footer.textContent = T.remaining.replace('{n}', data.quota.remaining_today);
                    }
                }
            } catch (error) {
                console.error('NumisTR Assistant Error:', error);
                removeThinkingIndicator(thinkingId);
                addMessage(T.error, 'bot');
            }

            sendBtn.disabled = false;
            input.focus();
        }

        function addMessage(text, sender, data, createdAt) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `numistr-message ${sender}`;

            const when = createdAt ? new Date(String(createdAt).replace(' ', 'T')) : new Date();
            const time = (isNaN(when.getTime()) ? new Date() : when).toLocaleTimeString(LANG === 'en' ? 'en-GB' : 'tr-TR', {
                hour: '2-digit',
                minute: '2-digit'
            });

            let extra = '';

            if (data && Array.isArray(data.sources) && data.sources.length) {
                extra += '<div class="numistr-sources"><div class="label">' + escapeHtml(T.sources) + '</div>';
                data.sources.slice(0, 5).forEach(function(s) {
                    if (s && s.url) {
                        extra += '<a href="' + escapeAttr(s.url) + '" target="_blank" rel="noopener">' + escapeHtml(s.title || s.url) + '</a>';
                    }
                });
                extra += '</div>';
            }

            if (data && data.cta) {
                if (data.cta.login_url) {
                    extra += '<a class="numistr-cta" href="' + escapeAttr(withReturn(data.cta.login_url)) + '">' + escapeHtml(T.ctaLogin) + '</a>';
                }

                if (data.cta.register_url) {
                    extra += '<a class="numistr-cta" href="' + escapeAttr(withReturn(data.cta.register_url)) + '">' + escapeHtml(T.cta) + '</a>';
                } else if (data.cta.url) {
                    // eski sunucu surumu: yalnizca planlar sayfasi linki
                    extra += '<a class="numistr-cta" href="' + escapeAttr(data.cta.url) + '">' + escapeHtml(T.cta) + '</a>';
                }
            }

            const body = sender === 'bot' ? renderMarkdown(text) : escapeHtml(text).replace(/\n/g, '<br>');
            messageDiv.innerHTML = '<div class="bubble">' + body + extra + '</div><div class="time">' + time + '</div>';

            messages.appendChild(messageDiv);
            messages.scrollTop = messages.scrollHeight;
        }

        function addThinkingIndicator() {
            const id = 'thinking-' + Date.now();
            const messageDiv = document.createElement('div');
            messageDiv.id = id;
            messageDiv.className = 'numistr-message bot';
            messageDiv.innerHTML = `
                <div class="bubble thinking">
                    <div class="thinking-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <span>${T.thinking}</span>
                </div>
            `;
            messages.appendChild(messageDiv);
            messages.scrollTop = messages.scrollHeight;
            return id;
        }

        function removeThinkingIndicator(id) {
            const element = document.getElementById(id);
            if (element) {
                element.remove();
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        function escapeAttr(text) {
            return escapeHtml(text).replace(/"/g, '&quot;');
        }

        // Minimal, safe markdown: escape first, then [text](url), bare URLs, **bold**, lists, paragraphs.
        function renderMarkdown(text) {
            let html = escapeHtml(text);

            html = html.replace(/\[([^\]]{1,200})\]\((https?:\/\/[^\s)]+)\)/g, function(m, label, url) {
                return '<a href="' + url.replace(/"/g, '&quot;') + '" target="_blank" rel="noopener">' + label + '</a>';
            });
            html = html.replace(/(^|[\s(])(https?:\/\/[^\s<)]*[^\s<).,;:!?])/g, function(m, pre, url) {
                return pre + '<a href="' + url.replace(/"/g, '&quot;') + '" target="_blank" rel="noopener">' + url + '</a>';
            });
            html = html.replace(/\*\*([^*\n]{1,200})\*\*/g, '<strong>$1</strong>');

            const paras = html.split(/\n{2,}/).map(function(p) {
                const lines = p.split('\n');
                const isList = lines.length > 0 && lines.every(function(l) { return /^\s*([-*\u2022]|\d+[.)])\s+/.test(l); });

                if (isList) {
                    return '<ul style="margin:0 0 6px 18px;padding:0">' + lines.map(function(l) {
                        return '<li>' + l.replace(/^\s*([-*\u2022]|\d+[.)])\s+/, '') + '</li>';
                    }).join('') + '</ul>';
                }

                return '<p>' + lines.join('<br>') + '</p>';
            });

            return paras.join('');
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createWidget);
    } else {
        createWidget();
    }
})();
