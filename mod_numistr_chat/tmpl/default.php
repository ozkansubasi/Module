<?php
/**
 * @package     NumisTR Chat Module
 * @subpackage  mod_numistr_chat
 * @version     1.0.0
 * @copyright   Copyright (C) 2025 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

// Generate unique module ID
$moduleId = 'numistr-chat-' . $module->id;
$document = Factory::getApplication()->getDocument();
$wa = $document->getWebAssetManager();

?>

<style>
/* NumisTR Chat Widget */
#<?php echo $moduleId; ?>-float {
    position: fixed;
    width: 60px;
    height: 60px;
    <?php if (strpos($widgetPosition, 'bottom') !== false): ?>
    bottom: 40px;
    <?php else: ?>
    top: 40px;
    <?php endif; ?>
    <?php if (strpos($widgetPosition, 'right') !== false): ?>
    right: 40px;
    <?php else: ?>
    left: 40px;
    <?php endif; ?>
    background-color: <?php echo htmlspecialchars($primaryColor); ?>;
    color: #FFF;
    border-radius: 50px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 9999;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
    50% { box-shadow: 0 0 0 10px rgba(37, 211, 102, 0); }
    100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
}

#<?php echo $moduleId; ?>-float:hover {
    transform: scale(1.1);
    animation: none;
}

#<?php echo $moduleId; ?>-widget {
    position: fixed;
    <?php if (strpos($widgetPosition, 'bottom') !== false): ?>
    bottom: 120px;
    <?php else: ?>
    top: 120px;
    <?php endif; ?>
    <?php if (strpos($widgetPosition, 'right') !== false): ?>
    right: 40px;
    <?php else: ?>
    left: 40px;
    <?php endif; ?>
    width: 380px;
    max-height: 600px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    z-index: 9998;
    display: none;
    flex-direction: column;
    overflow: hidden;
}

#<?php echo $moduleId; ?>-widget.active {
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

.numistr-chat-header {
    background: linear-gradient(135deg, #128c7e 0%, <?php echo htmlspecialchars($primaryColor); ?> 100%);
    color: white;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.numistr-chat-header-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.numistr-chat-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #128c7e;
    font-size: 18px;
}

.numistr-chat-info h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.numistr-chat-info p {
    margin: 3px 0 0 0;
    font-size: 13px;
    opacity: 0.9;
}

.numistr-chat-close {
    background: transparent;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    width: 30px;
    height: 30px;
}

.numistr-chat-body {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    background: #ece5dd;
    min-height: 300px;
    max-height: 400px;
}

.numistr-message {
    margin-bottom: 15px;
    display: flex;
}

.numistr-message.user {
    justify-content: flex-end;
}

.numistr-message-bubble {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 14px;
    line-height: 1.5;
    word-wrap: break-word;
}

.numistr-message.bot .numistr-message-bubble {
    background: white;
    border-bottom-left-radius: 2px;
}

.numistr-message.user .numistr-message-bubble {
    background: #dcf8c6;
    border-bottom-right-radius: 2px;
}

.numistr-message-time {
    font-size: 11px;
    color: #667781;
    margin-top: 3px;
}

.numistr-quick-replies {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.numistr-quick-reply {
    background: white;
    border: 1px solid <?php echo htmlspecialchars($primaryColor); ?>;
    color: <?php echo htmlspecialchars($primaryColor); ?>;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.numistr-quick-reply:hover {
    background: <?php echo htmlspecialchars($primaryColor); ?>;
    color: white;
}

.numistr-chat-footer {
    padding: 15px;
    background: #f0f0f0;
    border-top: 1px solid #ddd;
}

.numistr-input-container {
    display: flex;
    gap: 10px;
    align-items: center;
}

.numistr-chat-input {
    flex: 1;
    padding: 10px 15px;
    border: 1px solid #ccc;
    border-radius: 25px;
    font-size: 14px;
    outline: none;
    font-family: inherit;
}

.numistr-send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: <?php echo htmlspecialchars($primaryColor); ?>;
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.numistr-send-btn:hover {
    background: #128c7e;
}

.numistr-typing {
    display: none;
    align-items: center;
    gap: 5px;
    padding: 10px 14px;
    background: white;
    border-radius: 8px;
    width: fit-content;
}

.numistr-typing.active {
    display: flex;
}

.numistr-typing-dot {
    width: 8px;
    height: 8px;
    background: #999;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.numistr-typing-dot:nth-child(2) {
    animation-delay: 0.2s;
}

.numistr-typing-dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
    }
    30% {
        transform: translateY(-10px);
    }
}

@media (max-width: 480px) {
    #<?php echo $moduleId; ?>-widget {
        width: calc(100vw - 20px);
        <?php if (strpos($widgetPosition, 'right') !== false): ?>
        right: 10px;
        <?php else: ?>
        left: 10px;
        <?php endif; ?>
        bottom: 100px;
        max-height: 70vh;
    }

    #<?php echo $moduleId; ?>-float {
        width: 55px;
        height: 55px;
        bottom: 20px;
        <?php if (strpos($widgetPosition, 'right') !== false): ?>
        right: 20px;
        <?php else: ?>
        left: 20px;
        <?php endif; ?>
    }
}
</style>

<!-- Floating Button -->
<div id="<?php echo $moduleId; ?>-float" onclick="numisrToggleChat<?php echo $module->id; ?>()">
    <svg viewBox="0 0 32 32" style="width: 35px; height: 35px; fill: white;">
        <path d="M16 0c-8.837 0-16 7.163-16 16 0 2.825 0.737 5.607 2.137 8.048l-2.137 7.952 7.933-2.127c2.42 1.37 5.173 2.127 8.067 2.127 8.837 0 16-7.163 16-16s-7.163-16-16-16zM16 29.467c-2.482 0-4.908-0.646-7.07-1.87l-0.507-0.292-4.713 1.262 1.262-4.669-0.292-0.508c-1.207-2.100-1.847-4.507-1.847-6.987 0-7.384 6.083-13.467 13.467-13.467s13.467 6.083 13.467 13.467c0 7.384-6.083 13.467-13.467 13.467zM21.713 18.837c-0.397-0.199-2.348-1.158-2.713-1.291s-0.629-0.199-0.893 0.199c-0.265 0.397-1.026 1.291-1.256 1.557-0.232 0.265-0.463 0.298-0.861 0.099s-1.68-0.619-3.199-1.977c-1.182-1.056-1.981-2.359-2.213-2.757s-0.025-0.612 0.174-0.811c0.179-0.178 0.397-0.463 0.596-0.694s0.265-0.397 0.397-0.662c0.132-0.265 0.066-0.496-0.033-0.694s-0.893-2.146-1.224-2.94c-0.322-0.773-0.649-0.668-0.893-0.681-0.231-0.012-0.496-0.015-0.761-0.015s-0.694 0.099-1.058 0.496c-0.364 0.397-1.389 1.357-1.389 3.308s1.421 3.836 1.62 4.102c0.199 0.265 2.799 4.276 6.781 5.996 0.948 0.411 1.688 0.655 2.266 0.839 0.955 0.303 1.824 0.26 2.511 0.158 0.766-0.114 2.348-0.96 2.679-1.887s0.331-1.723 0.232-1.887c-0.099-0.165-0.364-0.265-0.761-0.463z"/>
    </svg>
</div>

<!-- Chat Widget -->
<div id="<?php echo $moduleId; ?>-widget">
    <div class="numistr-chat-header">
        <div class="numistr-chat-header-content">
            <div class="numistr-chat-avatar">NT</div>
            <div class="numistr-chat-info">
                <h3>NumisTR Asistan</h3>
                <p>Çevrimiçi</p>
            </div>
        </div>
        <button class="numistr-chat-close" onclick="numisrToggleChat<?php echo $module->id; ?>()">×</button>
    </div>

    <div class="numistr-chat-body" id="<?php echo $moduleId; ?>-body">
        <div class="numistr-message bot">
            <div>
                <div class="numistr-message-bubble">
                    👋 Merhaba! NumisTR AI Asistanı'na hoş geldiniz.<br><br>
                    Antik Anadolu sikkeleri hakkında size nasıl yardımcı olabilirim?
                </div>
                <div class="numistr-message-time" id="<?php echo $moduleId; ?>-time"></div>
            </div>
        </div>

        <div class="numistr-quick-replies">
            <button class="numistr-quick-reply" onclick="numisrQuickReply<?php echo $module->id; ?>('Sikke nedir?')">Sikke nedir?</button>
            <button class="numistr-quick-reply" onclick="numisrQuickReply<?php echo $module->id; ?>('Lidya sikkeleri hakkında bilgi')">Lidya Sikkeleri</button>
            <button class="numistr-quick-reply" onclick="numisrQuickReply<?php echo $module->id; ?>('İonia bölgesi darphane listesi')">İonia Darphaneleri</button>
        </div>

        <div class="numistr-typing" id="<?php echo $moduleId; ?>-typing">
            <div class="numistr-typing-dot"></div>
            <div class="numistr-typing-dot"></div>
            <div class="numistr-typing-dot"></div>
        </div>
    </div>

    <div class="numistr-chat-footer">
        <div class="numistr-input-container">
            <input
                type="text"
                class="numistr-chat-input"
                id="<?php echo $moduleId; ?>-input"
                placeholder="Mesajınızı yazın..."
            >
            <button class="numistr-send-btn" onclick="numisrSendMessage<?php echo $module->id; ?>()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M2 21L23 12L2 3V10L17 12L2 14V21Z" fill="white"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const moduleId = '<?php echo $moduleId; ?>';
    const config = {
        whatsappNumber: '<?php echo addslashes($whatsappNumber); ?>',
        ragEndpoint: '<?php echo addslashes($ragEndpoint); ?>',
        language: '<?php echo $language; ?>'
    };

    // Set initial time
    document.getElementById(moduleId + '-time').textContent = getCurrentTime();

    // Toggle chat
    window['numisrToggleChat<?php echo $module->id; ?>'] = function() {
        const widget = document.getElementById(moduleId + '-widget');
        widget.classList.toggle('active');
        if (widget.classList.contains('active')) {
            document.getElementById(moduleId + '-input').focus();
            scrollToBottom();
        }
    };

    // Send message
    window['numisrSendMessage<?php echo $module->id; ?>'] = async function() {
        const input = document.getElementById(moduleId + '-input');
        const message = input.value.trim();
        if (!message) return;

        addMessage(message, 'user');
        input.value = '';
        showTyping(true);

        try {
            const response = await fetch(config.ragEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    query: message,
                    language: config.language,
                    session_id: getSessionId()
                })
            });

            const data = await response.json();
            showTyping(false);

            if (data.response) {
                addMessage(data.response, 'bot');
            } else if (data.message) {
                addMessage(data.message, 'bot');
            } else {
                addMessage('Üzgünüm, bir hata oluştu. Lütfen tekrar deneyin.', 'bot');
            }
        } catch (error) {
            console.error('Chat error:', error);
            showTyping(false);
            addMessage('Bağlantı hatası. Lütfen internet bağlantınızı kontrol edin.', 'bot');
        }
    };

    // Quick reply
    window['numisrQuickReply<?php echo $module->id; ?>'] = function(message) {
        document.getElementById(moduleId + '-input').value = message;
        window['numisrSendMessage<?php echo $module->id; ?>']();
    };

    // Add message
    function addMessage(text, sender) {
        const chatBody = document.getElementById(moduleId + '-body');
        const typingIndicator = document.getElementById(moduleId + '-typing');

        const msgDiv = document.createElement('div');
        msgDiv.className = 'numistr-message ' + sender;

        const container = document.createElement('div');

        const bubble = document.createElement('div');
        bubble.className = 'numistr-message-bubble';
        bubble.innerHTML = formatMessage(text);

        const time = document.createElement('div');
        time.className = 'numistr-message-time';
        time.textContent = getCurrentTime();

        container.appendChild(bubble);
        container.appendChild(time);
        msgDiv.appendChild(container);

        chatBody.insertBefore(msgDiv, typingIndicator);
        scrollToBottom();
    }

    // Format message
    function formatMessage(text) {
        text = text.replace(/\n/g, '<br>');
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
        return text;
    }

    // Show typing
    function showTyping(show) {
        const indicator = document.getElementById(moduleId + '-typing');
        if (show) {
            indicator.classList.add('active');
            scrollToBottom();
        } else {
            indicator.classList.remove('active');
        }
    }

    // Get current time
    function getCurrentTime() {
        const now = new Date();
        return now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
    }

    // Scroll to bottom
    function scrollToBottom() {
        const chatBody = document.getElementById(moduleId + '-body');
        setTimeout(() => {
            chatBody.scrollTop = chatBody.scrollHeight;
        }, 100);
    }

    // Get session ID
    function getSessionId() {
        let sessionId = localStorage.getItem('numistr_session_id');
        if (!sessionId) {
            sessionId = 'web_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('numistr_session_id', sessionId);
        }
        return sessionId;
    }

    // Enter key handler
    document.getElementById(moduleId + '-input').addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            window['numisrSendMessage<?php echo $module->id; ?>']();
        }
    });
})();
</script>
