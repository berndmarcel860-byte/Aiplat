

<!-- Footer START -->
<footer class="footer">
    <div class="footer-content">
        <p class="m-b-0" style="color:#6c757d;font-size:14px;">
            <i class="anticon anticon-copyright mr-1"></i>
            <?= date('Y') ?> Crypto Finanze AI. All rights reserved.
        </p>
        <span>
            <a href="terms.php" class="text-gray m-r-15" style="color:#6c757d;text-decoration:none;font-size:14px;">
                <i class="anticon anticon-file-text mr-1"></i>Terms &amp; Conditions
            </a>
            <a href="privacy.php" class="text-gray" style="color:#6c757d;text-decoration:none;font-size:14px;">
                <i class="anticon anticon-lock mr-1"></i>Privacy Policy
            </a>
        </span>
    </div>
</footer>
<!-- Footer END -->

</div>
<!-- Page Container END -->

<!-- ================= CORE JS ================= -->
<script src="assets/js/vendors.min.js"></script>
<script src="https://cdn.datatables.net/v/bs4/dt-1.11.3/datatables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/pages/dashboard-default.js"></script>
<script src="assets/js/app.min.js"></script>

<!-- ================= CUSTOM JS ================= -->
<script src="assets/js/config.js"></script>
<script src="assets/js/sidebar.js"></script>
<script src="assets/js/charts.js"></script>
<script src="assets/js/docu1ments.js"></script>
<script src="assets/js/payment-methods.js"></script>
<script src="assets/js/transactions.js"></script>
<script src="assets/js/ky1c.js"></script>
<script src="assets/js/withdrawals1.js"></script>
<script src="assets/js/deposits.js"></script>

<!-- ================= NOTIFICATIONS ================= -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const notifList  = document.getElementById("notifList");
    const notifCount = document.getElementById("notifCount");

    function loadNotifications() {
        fetch("ajax/get_notifications.php")
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                notifList.innerHTML = "";
                const data = res.data;

                if (!data.length) {
                    notifList.innerHTML = '<div class="notif-empty">No notifications</div>';
                    notifCount.style.display = 'none';
                    return;
                }

                let unread = 0;
                data.forEach(n => {
                    if (n.is_read == 0) unread++;
                    const typeColor =
                        n.type === 'success' ? 'text-success' :
                        n.type === 'warning' ? 'text-warning' :
                        n.type === 'danger'  ? 'text-danger'  :
                        'text-info';
                    notifList.insertAdjacentHTML('beforeend', `
                        <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}" data-id="${n.id}">
                            <div class="${typeColor}">${n.title}</div>
                            <small>${n.message}</small><br>
                            <small>${new Date(n.created_at).toLocaleString()}</small>
                        </div>
                    `);
                });

                notifCount.textContent = unread;
                notifCount.style.display = unread ? 'inline-block' : 'none';
            });
    }

    loadNotifications();
    setInterval(loadNotifications, 15000);

    notifList.addEventListener('click', e => {
        const item = e.target.closest('.notif-item');
        if (!item) return;
        fetch("ajax/mark_notification_read.php", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded"},
            body: "id=" + item.dataset.id
        }).then(loadNotifications);
    });

    document.getElementById('markAllRead').addEventListener('click', () => {
        fetch("ajax/mark_notification_read.php", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded"},
            body: "id=0"
        }).then(loadNotifications);
    });
});
</script>

<!-- ================= AI LIVE CHAT WIDGET ================= -->
<style>
#aiChatWidget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
}
#aiChatToggle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%);
    border: none;
    box-shadow: 0 4px 18px rgba(41,80,168,.45);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform .2s;
    margin-left: auto;
}
#aiChatToggle:hover { transform: scale(1.08); }
#aiChatToggle svg { width: 26px; height: 26px; fill: #fff; }
#aiChatUnread {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #e74c3c;
    color: #fff;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 11px;
    font-weight: 700;
    display: none;
    align-items: center;
    justify-content: center;
}
#aiChatBox {
    display: none;
    flex-direction: column;
    width: 360px;
    max-width: calc(100vw - 48px);
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 40px rgba(0,0,0,.18);
    overflow: hidden;
    margin-bottom: 12px;
}
#aiChatHeader {
    background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%);
    color: #fff;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}
#aiChatHeader .ai-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
#aiChatHeader .ai-info { flex: 1; }
#aiChatHeader .ai-info strong { display: block; font-size: 14px; }
#aiChatHeader .ai-info span { font-size: 11px; opacity: .85; }
#aiChatHeader .ai-status-dot {
    width: 8px; height: 8px; background: #2ecc71;
    border-radius: 50%; display: inline-block; margin-right: 4px;
}
#aiChatCloseBtn {
    background: none; border: none; color: #fff;
    font-size: 20px; cursor: pointer; padding: 0; line-height: 1;
    opacity: .8;
}
#aiChatCloseBtn:hover { opacity: 1; }
#aiChatMessages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 260px;
    max-height: 360px;
    background: #f7fafd;
}
.ai-msg-wrap { display: flex; flex-direction: column; max-width: 82%; }
.ai-msg-wrap.user { align-self: flex-end; align-items: flex-end; }
.ai-msg-wrap.bot  { align-self: flex-start; align-items: flex-start; }
.ai-bubble {
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 13.5px;
    line-height: 1.55;
    word-break: break-word;
}
.ai-msg-wrap.user .ai-bubble {
    background: linear-gradient(135deg, #2950a8, #2da9e3);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.ai-msg-wrap.bot .ai-bubble {
    background: #fff;
    color: #333;
    border: 1px solid #e0e8f0;
    border-bottom-left-radius: 4px;
}
.ai-msg-time {
    font-size: 10.5px;
    color: #999;
    margin-top: 3px;
    padding: 0 2px;
}
.ai-typing {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 10px 14px;
    background: #fff;
    border: 1px solid #e0e8f0;
    border-radius: 16px;
    border-bottom-left-radius: 4px;
    width: fit-content;
}
.ai-typing span {
    width: 7px; height: 7px; background: #2da9e3;
    border-radius: 50%; display: inline-block;
    animation: aiDot 1.2s infinite ease-in-out;
}
.ai-typing span:nth-child(2) { animation-delay: .2s; }
.ai-typing span:nth-child(3) { animation-delay: .4s; }
@keyframes aiDot {
    0%,80%,100% { transform: scale(.7); opacity: .5; }
    40%         { transform: scale(1);  opacity: 1; }
}
#aiChatInputArea {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    border-top: 1px solid #e9ecef;
    background: #fff;
}
#aiChatInput {
    flex: 1;
    border: 1px solid #dde3ec;
    border-radius: 22px;
    padding: 8px 14px;
    font-size: 13.5px;
    outline: none;
    transition: border-color .2s;
    resize: none;
    height: 38px;
    line-height: 1.4;
}
#aiChatInput:focus { border-color: #2950a8; }
#aiChatSendBtn {
    width: 38px; height: 38px; flex-shrink: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #2950a8, #2da9e3);
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: opacity .2s;
}
#aiChatSendBtn:hover { opacity: .88; }
#aiChatSendBtn svg { width: 16px; height: 16px; fill: #fff; }
.ai-live-agent-btn {
    display: inline-block;
    margin-top: 6px;
    padding: 6px 14px;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: #fff !important;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
}
.ai-live-agent-btn:hover { opacity: .9; }
</style>

<div id="aiChatWidget">
    <div id="aiChatBox">
        <div id="aiChatHeader">
            <div class="ai-avatar">🤖</div>
            <div class="ai-info">
                <strong>AI Support Assistant</strong>
                <span><span class="ai-status-dot"></span>Online – ready to help</span>
            </div>
            <button id="aiChatCloseBtn" title="Close chat">×</button>
        </div>
        <div id="aiChatMessages"></div>
        <div id="aiChatInputArea">
            <input type="text" id="aiChatInput" placeholder="Type your question …" autocomplete="off" maxlength="1000">
            <button id="aiChatSendBtn" title="Send">
                <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            </button>
        </div>
    </div>
    <div style="position:relative; width:56px;">
        <button id="aiChatToggle" title="AI Support Chat">
            <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
        </button>
        <span id="aiChatUnread">1</span>
    </div>
</div>

<script>
(function () {
    const AJAX_URL = 'ajax/ai_chat.php';
    const WELCOME  = 'Willkommen! 👋 Ich bin Ihr KI-Support-Assistent. Ich helfe Ihnen gerne bei Fragen zu Ihren Fällen, KYC-Verifizierung, Transaktionen, Dokumenten, Paketen und weiteren Plattformfunktionen.\n\nWie kann ich Ihnen heute helfen?';

    const widget   = document.getElementById('aiChatWidget');
    const box      = document.getElementById('aiChatBox');
    const toggle   = document.getElementById('aiChatToggle');
    const closeBtn = document.getElementById('aiChatCloseBtn');
    const msgs     = document.getElementById('aiChatMessages');
    const input    = document.getElementById('aiChatInput');
    const sendBtn  = document.getElementById('aiChatSendBtn');
    const unread   = document.getElementById('aiChatUnread');

    let isOpen        = false;
    let sending       = false;
    let welcomed      = false;
    let unreadCount   = 0;

    function nowStr() {
        const d = new Date();
        const pad = n => String(n).padStart(2, '0');
        return `${pad(d.getDate())}.${pad(d.getMonth()+1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function scrollBottom() {
        msgs.scrollTop = msgs.scrollHeight;
    }

    function renderMsg(role, text, time) {
        const wrap = document.createElement('div');
        wrap.className = 'ai-msg-wrap ' + role;

        // Convert **bold** markdown and line breaks
        const html = text
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
            .replace(/\n/g,'<br>');

        const bubble = document.createElement('div');
        bubble.className = 'ai-bubble';
        bubble.innerHTML = html;

        const ts = document.createElement('div');
        ts.className = 'ai-msg-time';
        ts.textContent = time || nowStr();

        wrap.appendChild(bubble);
        wrap.appendChild(ts);

        // If bot message mentions "Live Agent", add a quick button
        if (role === 'bot' && /live agent/i.test(text)) {
            const btn = document.createElement('a');
            btn.href = 'support.php';
            btn.className = 'ai-live-agent-btn';
            btn.textContent = '🎧 Open Support Ticket';
            wrap.appendChild(btn);
        }

        msgs.appendChild(wrap);
        scrollBottom();
    }

    function showTyping() {
        const t = document.createElement('div');
        t.className = 'ai-msg-wrap bot';
        t.id = 'aiTypingIndicator';
        t.innerHTML = '<div class="ai-typing"><span></span><span></span><span></span></div>';
        msgs.appendChild(t);
        scrollBottom();
        return t;
    }

    function removeTyping() {
        const t = document.getElementById('aiTypingIndicator');
        if (t) t.remove();
    }

    function loadHistory() {
        fetch(AJAX_URL + '?action=history')
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                msgs.innerHTML = '';
                welcomed = false;
                if (res.messages.length === 0) {
                    showWelcome();
                } else {
                    welcomed = true;
                    res.messages.forEach(m => renderMsg(m.role, m.message, m.time));
                }
            })
            .catch(() => showWelcome());
    }

    function showWelcome() {
        if (welcomed) return;
        welcomed = true;
        renderMsg('bot', WELCOME, nowStr());
    }

    function sendMessage() {
        if (sending) return;
        const text = input.value.trim();
        if (!text) return;

        input.value = '';
        const userTime = nowStr();
        renderMsg('user', text, userTime);

        sending = true;
        sendBtn.disabled = true;
        const typing = showTyping();

        const body = new URLSearchParams({ action: 'send', message: text });
        fetch(AJAX_URL, { method: 'POST', body })
            .then(r => r.json())
            .then(res => {
                removeTyping();
                if (res.success) {
                    renderMsg('bot', res.bot_reply, res.bot_time);
                } else {
                    renderMsg('bot', 'An error occurred. Please try again or type **Live Agent** for live support.', nowStr());
                }
            })
            .catch(() => {
                removeTyping();
                renderMsg('bot', 'Connection error. Please type **Live Agent** for live support.', nowStr());
            })
            .finally(() => {
                sending = false;
                sendBtn.disabled = false;
                input.focus();
            });
    }

    // Toggle open/close
    toggle.addEventListener('click', () => {
        isOpen = !isOpen;
        box.style.display = isOpen ? 'flex' : 'none';
        if (isOpen) {
            unreadCount = 0;
            unread.style.display = 'none';
            loadHistory();
            setTimeout(() => input.focus(), 100);
        }
    });

    closeBtn.addEventListener('click', () => {
        isOpen = false;
        box.style.display = 'none';
    });

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    // Show unread badge after 3 seconds to invite the user
    setTimeout(() => {
        if (!isOpen) {
            unreadCount = 1;
            unread.style.display = 'flex';
        }
    }, 3000);
})();
</script>
<!-- ================= END AI LIVE CHAT WIDGET ================= -->

<!-- ================= GOOGLE TRANSLATE ================= -->
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script>
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'en',
        includedLanguages: 'en,de,no,sv',
        autoDisplay: false
    }, 'google_translate_element');

    setTimeout(() => {
        document.querySelectorAll('.goog-te-gadget-simple span')
            .forEach(el => { if (!el.querySelector('img')) el.style.display = 'none'; });
    }, 1000);
}
</script>


</script>


