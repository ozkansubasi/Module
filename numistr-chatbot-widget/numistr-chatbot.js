/**
 * NumisTR AI Chatbot Widget
 * Antik Anadolu sikkeleri hakkında sorularınızı yanıtlar
 *
 * Kullanım: Sayfanın sonuna bu scripti ekleyin
 * <script src="path/to/numistr-chatbot.js"></script>
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        apiUrl: 'https://n8n.aetelekom.com/webhook/numistr-kb-query',
        widgetTitle: 'NumisTR Asistan',
        placeholder: 'Antik sikkeler hakkında soru sorun...',
        welcomeMessage: 'Merhaba! Ben NumisTR AI asistanıyım. Antik Anadolu sikkeleri, darphaneler ve tarih hakkında sorularınızı yanıtlayabilirim.',
        errorMessage: 'Üzgünüm, bir hata oluştu. Lütfen tekrar deneyin.',
        thinkingMessage: 'Düşünüyorum...',
        primaryColor: '#8B4513', // Saddle brown - antik tema
        secondaryColor: '#D4AF37', // Gold
        position: 'bottom-right' // bottom-right, bottom-left
    };

    // Generate unique session ID
    const sessionId = 'web_' + Math.random().toString(36).substring(2, 15);

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
                        <div class="title">${CONFIG.widgetTitle}</div>
                        <div class="subtitle">Antik Anadolu Sikkeleri Uzmanı</div>
                    </div>
                </div>

                <div id="numistr-chat-messages"></div>

                <div id="numistr-chat-input-container">
                    <input type="text" id="numistr-chat-input" placeholder="${CONFIG.placeholder}" />
                    <button id="numistr-chat-send" aria-label="Gönder">
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
        const messages = document.getElementById('numistr-chat-messages');

        // Toggle chat panel
        button.addEventListener('click', function() {
            const isOpen = panel.classList.toggle('open');
            button.classList.toggle('open', isOpen);

            if (isOpen && messages.children.length === 0) {
                addMessage(CONFIG.welcomeMessage, 'bot');
            }

            if (isOpen) {
                input.focus();
            }
        });

        // Send message on button click
        sendBtn.addEventListener('click', sendMessage);

        // Send message on Enter key
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        async function sendMessage() {
            const query = input.value.trim();
            if (!query) return;

            // Add user message
            addMessage(query, 'user');
            input.value = '';
            sendBtn.disabled = true;

            // Show thinking indicator
            const thinkingId = addThinkingIndicator();

            try {
                const response = await fetch(CONFIG.apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        query: query,
                        session_id: sessionId
                    })
                });

                if (!response.ok) {
                    throw new Error('API error');
                }

                const data = await response.json();

                // Remove thinking indicator
                removeThinkingIndicator(thinkingId);

                // Add bot response
                addMessage(data.answer || CONFIG.errorMessage, 'bot');

            } catch (error) {
                console.error('NumisTR Chatbot Error:', error);
                removeThinkingIndicator(thinkingId);
                addMessage(CONFIG.errorMessage, 'bot');
            }

            sendBtn.disabled = false;
            input.focus();
        }

        function addMessage(text, sender) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `numistr-message ${sender}`;

            const time = new Date().toLocaleTimeString('tr-TR', {
                hour: '2-digit',
                minute: '2-digit'
            });

            messageDiv.innerHTML = `
                <div class="bubble">${escapeHtml(text)}</div>
                <div class="time">${time}</div>
            `;

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
                    <span>${CONFIG.thinkingMessage}</span>
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
            div.textContent = text;
            return div.innerHTML.replace(/\n/g, '<br>');
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createWidget);
    } else {
        createWidget();
    }
})();
