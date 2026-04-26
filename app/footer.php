

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

<?php
// ── Live Chat Widget (3rd-party embed code) ─────────────────────────
// The admin can paste any live-chat embed code (Tawk.to, Crisp, Intercom …)
// via Admin → Settings → Live-Chat Code. It is output raw here.
try {
    $lcStmt = $pdo->query("SELECT live_chat_code FROM system_settings WHERE id = 1 LIMIT 1");
    $lcRow  = $lcStmt ? $lcStmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($lcRow && !empty(trim($lcRow['live_chat_code']))) {
        echo $lcRow['live_chat_code'];
    }
} catch (Exception $lcEx) {
    // Column does not exist yet (migration not applied) – silently skip
}
?>




<!-- ═══════════════════════════════════════════════════════════════════════
     BUILT-IN LIVE CHAT WIDGET
     ═══════════════════════════════════════════════════════════════════════ -->
<style>
/* Widget container */
#lc-widget{position:fixed;bottom:24px;right:24px;z-index:9999;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;}
/* Toggle bubble */
#lc-toggle{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:22px;box-shadow:0 4px 16px rgba(41,80,168,.45);transition:transform .2s;position:relative;}
#lc-toggle:hover{transform:scale(1.08);}
#lc-unread-badge{position:absolute;top:-3px;right:-3px;background:#dc3545;color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:700;display:none;align-items:center;justify-content:center;}
/* Chat window */
#lc-window{width:360px;height:520px;background:#fff;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.18);display:none;flex-direction:column;overflow:hidden;margin-bottom:10px;}
#lc-window.open{display:flex;}
/* Header */
#lc-header{background:linear-gradient(135deg,#2950a8,#2da9e3);padding:12px 14px;display:flex;align-items:center;gap:10px;color:#fff;flex-shrink:0;}
.lc-avatar{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;}
.lc-header-info{flex:1;min-width:0;}
.lc-header-info .lc-title{font-size:13px;font-weight:700;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.lc-header-info .lc-subtitle{font-size:11px;opacity:.85;}
.lc-online-dot{width:7px;height:7px;border-radius:50%;background:#7ee8a2;border:2px solid rgba(255,255,255,.5);display:inline-block;margin-right:3px;}
/* Header action buttons */
.lc-header-actions{display:flex;align-items:center;gap:4px;flex-shrink:0;margin-left:auto;}
#lc-end-btn{background:rgba(220,53,69,.15);border:1px solid rgba(220,53,69,.6);color:#ffc0c0;border-radius:12px;font-size:11px;padding:4px 9px;cursor:pointer;white-space:nowrap;transition:background .15s,color .15s;}
#lc-end-btn:hover{background:#dc3545;color:#fff;border-color:#dc3545;}
#lc-close-btn{background:transparent;border:none;color:rgba(255,255,255,.75);font-size:17px;cursor:pointer;padding:2px 5px;border-radius:6px;line-height:1;}
#lc-close-btn:hover{background:rgba(255,255,255,.15);color:#fff;}
/* Messages */
#lc-messages{flex:1;overflow-y:auto;padding:14px 12px;display:flex;flex-direction:column;gap:8px;background:#f8f9fa;}
.lc-msg-row{display:flex;gap:6px;align-items:flex-end;}
.lc-msg-row.out{flex-direction:row-reverse;}
.lc-msg-content{max-width:75%;min-width:0;}
.lc-bubble{padding:9px 12px;border-radius:14px;font-size:13px;line-height:1.55;word-break:break-word;white-space:pre-wrap;}
.lc-bubble strong{font-weight:700;}
.lc-msg-row.in  .lc-bubble{background:#fff;border:1px solid #dee2e6;border-radius:14px 14px 14px 2px;color:#2c3e50;}
.lc-msg-row.bot .lc-bubble{background:linear-gradient(135deg,#e8f0fe,#dbeafe);border:1px solid rgba(41,80,168,.12);border-radius:14px 14px 14px 2px;color:#1a2e5e;}
.lc-msg-row.out .lc-bubble{background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border-radius:14px 14px 2px 14px;}
.lc-msg-meta{font-size:10px;color:#adb5bd;margin-top:2px;}
.lc-msg-row.out .lc-msg-meta{text-align:right;}
.lc-sender-name{font-size:10px;font-weight:700;color:#2950a8;margin-bottom:2px;}
.lc-read-tick{font-size:11px;}
.lc-read-tick.seen{color:#7ee8a2;}
.lc-av{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;}
.lc-av-bot{background:#fff3e0;color:#e65100;border:1px solid #ffe0b2;font-size:13px;}
.lc-av-admin{background:#2950a8;color:#fff;}
/* Typing */
#lc-typing{padding:0 12px 6px;font-size:12px;color:#6c757d;display:none;height:22px;flex-shrink:0;}
.lc-typing-dots span{display:inline-block;width:5px;height:5px;border-radius:50%;background:#adb5bd;margin:0 1px;animation:lcBounce 1.2s infinite;}
.lc-typing-dots span:nth-child(2){animation-delay:.2s;}
.lc-typing-dots span:nth-child(3){animation-delay:.4s;}
@keyframes lcBounce{0%,80%,100%{transform:translateY(0)}40%{transform:translateY(-5px)}}
/* Quick topics */
#lc-topics{padding:10px 12px;border-top:1px solid #e9ecef;background:#fff;flex-shrink:0;}
.lc-topics-label{font-size:11px;color:#6c757d;margin-bottom:6px;}
.lc-topic-btns{display:flex;flex-wrap:wrap;gap:5px;}
.lc-topic-btn{background:#f0f7ff;border:1px solid #cfe2ff;color:#2950a8;font-size:11px;border-radius:14px;padding:4px 10px;cursor:pointer;transition:background .15s,color .15s;}
.lc-topic-btn:hover{background:#2950a8;color:#fff;border-color:#2950a8;}
/* Ticket suggestion banner */
#lc-ticket-banner{padding:9px 12px;background:#fff8e1;border-top:1px solid #ffecb3;font-size:12px;color:#856404;display:none;flex-direction:column;gap:6px;flex-shrink:0;}
.lc-ticket-banner-row{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.lc-ticket-banner-btns{display:flex;gap:6px;flex-wrap:wrap;}
.lc-banner-wait-btn{background:#fff3cd;border:1px solid #ffc107;color:#856404;border-radius:12px;font-size:11px;padding:4px 10px;cursor:pointer;}
.lc-banner-wait-btn:hover{background:#ffc107;color:#fff;}
#lc-ticket-banner a.lc-banner-ticket-link{background:#2950a8;color:#fff;border-radius:12px;font-size:11px;padding:4px 10px;text-decoration:none;font-weight:600;}
#lc-ticket-banner a.lc-banner-ticket-link:hover{opacity:.85;}
#lc-ticket-dismiss{background:none;border:none;color:#856404;cursor:pointer;font-size:14px;line-height:1;padding:0 2px;flex-shrink:0;margin-left:auto;}
/* Closed bar */
#lc-closed-bar{padding:12px;background:#f8f9fa;border-top:1px solid #e9ecef;text-align:center;display:none;flex-shrink:0;}
.lc-closed-msg{font-size:12px;color:#6c757d;margin-bottom:8px;}
.lc-new-chat-btn{background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;border-radius:20px;font-size:12px;padding:7px 18px;cursor:pointer;}
.lc-new-chat-btn:hover{opacity:.85;}
/* System notice message */
.lc-msg-row.system{justify-content:center;}
.lc-msg-row.system .lc-bubble{background:#e9ecef;color:#6c757d;font-size:11px;border-radius:10px;font-style:italic;text-align:center;max-width:90%;}
/* Input bar */
#lc-input-bar{padding:10px 12px;background:#fff;border-top:1px solid #e9ecef;display:flex;gap:8px;align-items:flex-end;flex-shrink:0;}
#lc-attach-btn{background:transparent;border:none;color:#6c757d;cursor:pointer;padding:4px;font-size:18px;line-height:1;flex-shrink:0;border-radius:6px;}
#lc-attach-btn:hover{color:#2950a8;background:#f0f7ff;}
#lc-file-input{display:none;}
/* Voice call button in header */
#lc-call-btn{background:transparent;border:none;color:rgba(255,255,255,.8);font-size:16px;cursor:pointer;padding:2px 6px;border-radius:6px;line-height:1;transition:background .15s,color .15s;}
#lc-call-btn:hover{background:rgba(255,255,255,.15);color:#fff;}
#lc-call-btn.lc-in-call{color:#7ee8a2;animation:lcCallPulse 1.5s infinite;}
@keyframes lcCallPulse{0%,100%{opacity:1}50%{opacity:.55}}
/* Incoming call banner (sits above input bar) */
#lc-call-incoming{padding:12px 14px;background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;display:none;flex-direction:column;gap:7px;flex-shrink:0;animation:lcSlideUp .25s ease;}
@keyframes lcSlideUp{from{transform:translateY(30px);opacity:0}to{transform:none;opacity:1}}
.lc-call-inc-title{font-size:13px;font-weight:700;}
.lc-call-inc-sub{font-size:11px;opacity:.85;}
.lc-call-inc-btns{display:flex;gap:8px;}
.lc-call-ans-btn{background:#28a745;border:none;color:#fff;border-radius:20px;padding:6px 16px;font-size:12px;cursor:pointer;font-weight:600;}
.lc-call-rej-btn{background:#dc3545;border:none;color:#fff;border-radius:20px;padding:6px 16px;font-size:12px;cursor:pointer;font-weight:600;}
/* Active call overlay (replaces input bar) */
#lc-call-overlay{padding:10px 12px;background:#f0f7ff;border-top:2px solid #2950a8;display:none;flex-direction:column;align-items:center;gap:6px;flex-shrink:0;}
.lc-call-timer{font-size:13px;font-weight:700;color:#2950a8;letter-spacing:.05em;}
.lc-call-status-txt{font-size:11px;color:#6c757d;}
.lc-call-controls{display:flex;gap:10px;}
.lc-call-mute-btn,.lc-call-end-btn{border:none;border-radius:50%;width:40px;height:40px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;}
.lc-call-mute-btn{background:#e9ecef;color:#495057;}
.lc-call-mute-btn.muted{background:#ffc107;color:#fff;}
.lc-call-end-btn{background:#dc3545;color:#fff;}
/* Attachment preview inside bubble */
.lc-attach-img{max-width:180px;max-height:160px;border-radius:8px;display:block;cursor:pointer;margin-top:4px;}
.lc-attach-link{display:inline-flex;align-items:center;gap:4px;font-size:12px;padding:5px 8px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);border-radius:8px;color:inherit;text-decoration:none;margin-top:4px;}
.lc-msg-row.in .lc-attach-link,.lc-msg-row.bot .lc-attach-link{background:#f0f7ff;border-color:#cfe2ff;color:#2950a8;}
#lc-input{flex:1;border:1px solid #dee2e6;border-radius:20px;padding:8px 14px;font-size:13px;resize:none;max-height:90px;overflow-y:auto;line-height:1.4;outline:none;}
#lc-input:focus{border-color:#2950a8;box-shadow:0 0 0 2px rgba(41,80,168,.12);}
#lc-send-btn{background:linear-gradient(135deg,#2950a8,#2da9e3);border:none;border-radius:50%;width:36px;height:36px;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:15px;}
#lc-send-btn:hover{opacity:.85;}
/* Mobile */
@media(max-width:480px){
    #lc-window{width:calc(100vw - 20px);height:calc(100vh - 90px);bottom:0;right:0;border-radius:16px 16px 0 0;}
    #lc-widget{bottom:10px;right:10px;}
}
</style>

<div id="lc-widget">
    <div id="lc-window">
        <div id="lc-header">
            <div class="lc-avatar">&#x1F916;</div>
            <div class="lc-header-info">
                <div class="lc-title">KI Support</div>
                <div class="lc-subtitle"><span class="lc-online-dot"></span>Online &ndash; sofort antworten</div>
            </div>
            <div class="lc-header-actions">
                <button id="lc-call-btn" type="button" title="Sprachanruf starten">&#x1F4DE;</button>
                <button id="lc-end-btn" type="button" title="Chat-Sitzung beenden">&#x2715; Beenden</button>
                <button id="lc-close-btn" type="button" title="Chat minimieren">&#x2212;</button>
            </div>
        </div>
        <div id="lc-messages"></div>
        <div id="lc-typing">Schreibt<span class="lc-typing-dots ml-1"><span></span><span></span><span></span></span></div>
        <div id="lc-ticket-banner">
            <div class="lc-ticket-banner-row">
                <span>&#x23F3; Noch keine Antwort vom Support-Team.</span>
                <button type="button" id="lc-ticket-dismiss" title="Schlie&szlig;en">&#x2715;</button>
            </div>
            <div class="lc-ticket-banner-btns">
                <button type="button" class="lc-banner-wait-btn" id="lc-banner-wait-btn">&#x1F64F; Ich warte weiter</button>
                <a href="support.php" class="lc-banner-ticket-link">&#x1F4DD; Support-Ticket erstellen</a>
            </div>
        </div>
        <div id="lc-topics">
            <div class="lc-topics-label">Schnellthemen:</div>
            <div class="lc-topic-btns">
                <button class="lc-topic-btn" type="button" data-topic="Falldetails">&#x1F4C1; Falldetails</button>
                <button class="lc-topic-btn" type="button" data-topic="KYC-Hilfe">&#x1FAA3; KYC-Hilfe</button>
                <button class="lc-topic-btn" type="button" data-topic="Einzahlungshilfe">&#x1F4B3; Einzahlung</button>
                <button class="lc-topic-btn" type="button" data-topic="Auszahlungshilfe">&#x1F4B0; Auszahlung</button>
                <button class="lc-topic-btn" type="button" data-topic="Pflichtgeb&uuml;hr">&#x1F4CB; Geb&uuml;hr</button>
                <button class="lc-topic-btn" type="button" data-topic="Finanzhilfe">&#x1F4CA; Finanzhilfe</button>
                <button class="lc-topic-btn" type="button" data-topic="Technische Hilfe">&#x1F527; Technisch</button>
                <button class="lc-topic-btn" type="button" data-topic="Wiederherstellung">&#x1F916; Recovery</button>
            </div>
        </div>
        <div id="lc-call-incoming">
            <div class="lc-call-inc-title">&#x1F4DE; Eingehender Sprachanruf</div>
            <div class="lc-call-inc-sub">Support-Team m&ouml;chte Sie anrufen</div>
            <div class="lc-call-inc-btns">
                <button class="lc-call-ans-btn" id="lc-call-ans-btn" type="button">&#x2714; Annehmen</button>
                <button class="lc-call-rej-btn" id="lc-call-rej-btn" type="button">&#x2715; Ablehnen</button>
            </div>
        </div>
        <div id="lc-call-overlay">
            <div class="lc-call-status-txt" id="lc-call-status-txt">Verbinde&#x2026;</div>
            <div class="lc-call-timer" id="lc-call-timer">00:00</div>
            <div class="lc-call-controls">
                <button class="lc-call-mute-btn" id="lc-call-mute-btn" type="button" title="Stummschalten">&#x1F399;</button>
                <button class="lc-call-end-btn" id="lc-call-end-btn" type="button" title="Anruf beenden">&#x1F4DE;</button>
            </div>
        </div>
        <div id="lc-input-bar">
            <button id="lc-attach-btn" type="button" title="Datei oder Bild anhängen">&#x1F4CE;</button>
            <input type="file" id="lc-file-input" accept="image/*,.pdf,.doc,.docx">
            <textarea id="lc-input" placeholder="Nachricht eingeben&#8230;" rows="1"></textarea>
            <button id="lc-send-btn" type="button" title="Senden">&#x27A4;</button>
        </div>
        <div id="lc-closed-bar">
            <div class="lc-closed-msg">&#x1F512; Diese Chat-Sitzung wurde beendet.</div>
            <button class="lc-new-chat-btn" id="lc-new-chat-btn" type="button">&#x1F4AC; Neuen Chat starten</button>
        </div>
    </div>
    <button id="lc-toggle" type="button" title="Support Chat &ouml;ffnen">
        &#x1F4AC;
        <span id="lc-unread-badge">0</span>
    </button>
</div>

<script>
(function(){
'use strict';
var sessionId=null,lastMsgId=0,pollTimer=null,typingTmo=null,isOpen=false,_initProm=null,sessionClosed=false;
var win=document.getElementById('lc-window');
var toggle=document.getElementById('lc-toggle');
var closeBtn=document.getElementById('lc-close-btn');
var msgBox=document.getElementById('lc-messages');
var typingEl=document.getElementById('lc-typing');
var inputEl=document.getElementById('lc-input');
var sendBtn=document.getElementById('lc-send-btn');
var badge=document.getElementById('lc-unread-badge');
var topicsEl=document.getElementById('lc-topics');
var endBtn=document.getElementById('lc-end-btn');
var closedBar=document.getElementById('lc-closed-bar');
var newChatBtn=document.getElementById('lc-new-chat-btn');
var ticketBanner=document.getElementById('lc-ticket-banner');
var ticketDismiss=document.getElementById('lc-ticket-dismiss');
var inputBar=document.getElementById('lc-input-bar');
var attachBtn=document.getElementById('lc-attach-btn');
var fileInput=document.getElementById('lc-file-input');
var bannerWaitBtn=document.getElementById('lc-banner-wait-btn');

/* ── Inactivity tracking ── */
var lastUserSentTime=0,lastReplyTime=0,inactivityTimer=null,ticketBannerShown=false;

/* ── Background unread-badge poller (runs while chat is closed) ── */
var unreadPollTimer=null;
function startUnreadPoll(){
    stopUnreadPoll();
    _checkUnread();
    unreadPollTimer=setInterval(_checkUnread,6000);
}
function stopUnreadPoll(){clearInterval(unreadPollTimer);unreadPollTimer=null;}
function _checkUnread(){
    if(isOpen||sessionClosed)return;
    fetch('ajax/chat_unread.php')
    .then(function(r){return r.json();})
    .then(function(res){
        if(!res.success)return;
        if(res.unread>0){showBadge(res.unread);}
        // Hydrate sessionId so polling starts immediately when user opens chat
        if(res.session_id&&!sessionId)sessionId=res.session_id;
    })
    .catch(function(){});
}

function fmtTime(dt){var d=new Date(dt);return d.toLocaleTimeString('de-DE',{hour:'2-digit',minute:'2-digit'});}
function escHtml(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML;}
function mdToHtml(s){
    if(s&&s.substring(0,10)==='__ATTACH__'){
        var payload=s.substring(11); // after "__ATTACH__:"
        var parts=payload.split('|');
        var url=parts[0]||'';
        var name=parts[1]||'Datei';
        var isImg=/\.(jpe?g|png|gif|webp)$/i.test(url);
        var absUrl=url; // relative – works as-is from same origin
        if(isImg){
            return '<img src="'+escHtml(absUrl)+'" class="lc-attach-img" alt="'+escHtml(name)+'" onclick="window.open(this.src)">';
        }
        return '<a href="'+escHtml(absUrl)+'" class="lc-attach-link" target="_blank" rel="noopener">&#x1F4CE; '+escHtml(name)+'</a>';
    }
    return escHtml(s).replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>');
}
function scrollBottom(){msgBox.scrollTop=msgBox.scrollHeight;}
function showBadge(n){badge.textContent=n;badge.style.display=n>0?'flex':'none';}

/* ── Notification sound (Web Audio API – no external file needed) ── */
var _audioCtx=null;
function playNotifSound(){
    try{
        if(!_audioCtx)_audioCtx=new(window.AudioContext||window.webkitAudioContext)();
        var ctx=_audioCtx;
        var osc=ctx.createOscillator();
        var gain=ctx.createGain();
        osc.connect(gain);gain.connect(ctx.destination);
        osc.type='sine';
        osc.frequency.setValueAtTime(880,ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(660,ctx.currentTime+0.12);
        gain.gain.setValueAtTime(0.28,ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001,ctx.currentTime+0.35);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime+0.35);
    }catch(e){}
}

function openChat(){
    isOpen=true;win.classList.add('open');showBadge(0);
    stopUnreadPoll();
    if(!sessionId)initSession();else startPoll();
    setTimeout(scrollBottom,120);
    startInactivityTimer();
}
function closeChat(){isOpen=false;win.classList.remove('open');clearInterval(pollTimer);startUnreadPoll();}

toggle.addEventListener('click',function(){isOpen?closeChat():openChat();});
closeBtn.addEventListener('click',closeChat);

/* ── End session ── */
endBtn.addEventListener('click',function(){
    if(!sessionId)return;
    if(!confirm('Chat-Sitzung wirklich beenden?\n\nSie können danach eine neue Sitzung starten.'))return;
    _endSession();
});
function _endSession(){
    fetch('ajax/chat_close.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:sessionId})})
    .then(function(r){return r.json();}).then(function(res){
        if(res.success)showClosedState('Sie haben diese Chat-Sitzung beendet.');
    }).catch(function(){});
}
function showClosedState(msg){
    sessionClosed=true;
    clearInterval(pollTimer);
    clearInterval(inactivityTimer);
    stopUnreadPoll();
    inputBar.style.display='none';
    if(ticketBanner)ticketBanner.style.display='none';
    closedBar.style.display='block';
    appendSystemMsg(msg||'\uD83D\uDD12 Diese Chat-Sitzung wurde beendet.');
}
function appendSystemMsg(text){
    var row=document.createElement('div');
    row.className='lc-msg-row system';
    row.innerHTML='<div style="width:100%;text-align:center;"><div class="lc-bubble">'+escHtml(text)+'</div></div>';
    msgBox.appendChild(row);
    scrollBottom();
}

newChatBtn.addEventListener('click',function(){
    sessionId=null;lastMsgId=0;_initProm=null;sessionClosed=false;
    lastUserSentTime=0;lastReplyTime=0;ticketBannerShown=false;
    closedBar.style.display='none';
    inputBar.style.display='flex';
    if(topicsEl)topicsEl.style.display='block';
    msgBox.innerHTML='';
    initSession();
    startInactivityTimer();
    stopUnreadPoll(); /* will be restarted when chat is closed */
});

/* ── Inactivity → suggest ticket ── */
ticketDismiss.addEventListener('click',function(){ticketBanner.style.display='none';ticketBannerShown=true;});
if(bannerWaitBtn){
    bannerWaitBtn.addEventListener('click',function(){
        ticketBanner.style.display='none';
        ticketBannerShown=true;
        appendSystemMsg('Danke für Ihre Geduld! Unser Team wird sich bald bei Ihnen melden. ⏳');
    });
}
function startInactivityTimer(){
    clearInterval(inactivityTimer);
    inactivityTimer=setInterval(function(){
        if(sessionClosed||ticketBannerShown||!sessionId)return;
        if(!lastUserSentTime)return;
        /* Show banner if user sent a message and has waited >5 min without any bot/admin reply */
        if(lastUserSentTime>lastReplyTime&&Date.now()-lastUserSentTime>5*60*1000){
            ticketBanner.style.display='flex';
            ticketBannerShown=true;
        }
    },30000);
}

function initSession(){
    if(_initProm)return _initProm;
    _initProm=fetch('ajax/chat_init.php').then(function(r){return r.json();}).then(function(res){
        if(!res.success){_initProm=null;return;}
        sessionId=res.session_id;
        if(res.session_status==='closed'){
            showClosedState();
            res.messages.forEach(function(m){appendMsg(m);});
            return;
        }
        res.messages.forEach(function(m){appendMsg(m);});
        scrollBottom();startPoll();
    }).catch(function(){_initProm=null;});
    return _initProm;
}

function appendMsg(msg){
    if(msg.id>lastMsgId)lastMsgId=parseInt(msg.id);
    if(document.querySelector('[data-lc-id="'+msg.id+'"]'))return;
    var stype=msg.sender_type;
    var rowClass=stype==='user'?'out':stype==='bot'?'bot':'in';
    var row=document.createElement('div');
    row.className='lc-msg-row '+rowClass;
    row.dataset.lcId=msg.id;
    var isRead=parseInt(msg.is_read)===1;
    var tick=stype==='user'?('<span class="lc-read-tick'+(isRead?' seen':'')+'">'+( isRead?' \u2713\u2713':' \u2713')+'</span>'):'';

    if(stype==='bot'){
        row.innerHTML='<div class="lc-av lc-av-bot">\uD83E\uDD16</div>'
            +'<div class="lc-msg-content"><div class="lc-bubble">'+mdToHtml(msg.message)+'</div>'
            +'<div class="lc-msg-meta">'+fmtTime(msg.created_at)+'</div></div>';
    }else if(stype==='admin'){
        // Build avatar initials from sender_name
        var aname=(msg.sender_name||'').trim();
        var initials='S';
        if(aname){
            var parts=aname.split(' ');
            initials=parts.length>=2?(parts[0][0]+parts[1][0]).toUpperCase():aname.substring(0,2).toUpperCase();
        }
        var nameHtml=aname?'<div class="lc-sender-name">'+escHtml(aname)+'</div>':'';
        row.innerHTML='<div class="lc-av lc-av-admin">'+escHtml(initials)+'</div>'
            +'<div class="lc-msg-content">'+nameHtml+'<div class="lc-bubble">'+mdToHtml(msg.message)+'</div>'
            +'<div class="lc-msg-meta">'+fmtTime(msg.created_at)+'</div></div>';
    }else{
        row.innerHTML='<div class="lc-msg-content"><div class="lc-bubble">'+mdToHtml(msg.message)+'</div>'
            +'<div class="lc-msg-meta">'+fmtTime(msg.created_at)+tick+'</div></div>';
    }
    msgBox.appendChild(row);
}

function sendMsg(text){
    text=text.trim();
    if(!text)return;
    if(!sessionId){initSession().then(function(){if(sessionId)_doSend(text);});return;}
    _doSend(text);
}
function _doSend(text){
    lastUserSentTime=Date.now();
    var prevVal=inputEl.value;
    inputEl.value='';inputEl.style.height='auto';
    if(topicsEl)topicsEl.style.display='none';
    fetch('ajax/chat_send.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:sessionId,message:text})})
    .then(function(r){return r.json();}).then(function(res){
        if(!res.success){inputEl.value=prevVal;return;}
        appendMsg(res.user_msg);scrollBottom();
        if(res.bot_msg){
            typingEl.style.display='block';scrollBottom();
            setTimeout(function(){
                typingEl.style.display='none';
                appendMsg(res.bot_msg);
                scrollBottom();
                lastReplyTime=Date.now(); /* bot replied — reset inactivity */
                if(ticketBanner&&!ticketBannerShown)ticketBanner.style.display='none';
            },1200);
        }
    }).catch(function(){inputEl.value=prevVal;});
}

sendBtn.addEventListener('click',function(){sendMsg(inputEl.value);});

/* ── File attach button ── */
attachBtn.addEventListener('click',function(){
    if(sessionClosed)return;
    if(!sessionId){initSession().then(function(){if(sessionId)fileInput.click();});return;}
    fileInput.click();
});
fileInput.addEventListener('change',function(){
    var f=fileInput.files[0];
    if(!f)return;
    fileInput.value='';
    if(!sessionId){initSession().then(function(){if(sessionId)_doUpload(f);});return;}
    _doUpload(f);
});
function _doUpload(f){
    if(topicsEl)topicsEl.style.display='none';
    var fd=new FormData();
    fd.append('session_id',sessionId);
    fd.append('chat_file',f);
    lastUserSentTime=Date.now();
    fetch('ajax/chat_upload.php',{method:'POST',body:fd})
    .then(function(r){return r.json();}).then(function(res){
        if(!res.success){alert(res.message||'Upload fehlgeschlagen');return;}
        appendMsg(res.user_msg);scrollBottom();
    }).catch(function(){alert('Upload fehlgeschlagen');});
}
inputEl.addEventListener('keydown',function(e){
    if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendMsg(inputEl.value);return;}
    clearTimeout(typingTmo);
    if(sessionId)fetch('ajax/chat_typing.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:sessionId})});
    typingTmo=setTimeout(function(){},3000);
});
inputEl.addEventListener('input',function(){inputEl.style.height='auto';inputEl.style.height=Math.min(inputEl.scrollHeight,90)+'px';});

document.querySelectorAll('.lc-topic-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
        var t=btn.dataset.topic;openChat();setTimeout(function(){sendMsg(t);},100);
    });
});

function startPoll(){clearInterval(pollTimer);pollTimer=setInterval(pollMessages,2500);}
function pollMessages(){
    if(!sessionId||sessionClosed)return;
    fetch('ajax/chat_poll.php?session_id='+sessionId+'&since_id='+lastMsgId)
    .then(function(r){return r.json();}).then(function(res){
        if(!res.success)return;
        var gotReply=false;
        res.messages.forEach(function(m){
            appendMsg(m);
            if(m.sender_type!=='user'){gotReply=true;}
            if(m.sender_type==='admin'||m.sender_type==='bot'){
                playNotifSound();
                if(!isOpen){var cur=parseInt(badge.textContent||'0')+1;showBadge(cur);}
            }
        });
        if(gotReply){
            lastReplyTime=Date.now();
            ticketBanner.style.display='none'; /* hide ticket banner if reply arrived */
            ticketBannerShown=false;
        }
        if(res.messages.length&&isOpen)scrollBottom();
        typingEl.style.display=res.admin_typing?'block':'none';
        if(res.admin_typing&&isOpen)scrollBottom();
        /* Live read-receipt tick updates: ✓ → ✓✓ */
        if(res.read_user_msg_ids&&res.read_user_msg_ids.length){
            res.read_user_msg_ids.forEach(function(id){
                var el=document.querySelector('[data-lc-id="'+id+'"] .lc-read-tick');
                if(el&&!el.classList.contains('seen')){el.classList.add('seen');el.textContent='\u00a0\u2713\u2713';}
            });
        }
        if(res.session_status==='closed'&&!sessionClosed){
            showClosedState('Diese Chat-Sitzung wurde vom Support-Team beendet. Starten Sie eine neue Sitzung für weitere Hilfe.');
        }
    }).catch(function(){});
}

/* ── Start background unread poller immediately on page load ── */
startUnreadPoll();

/* ── Voice Call (WebRTC) ── */
(function(){
'use strict';
var lcCallBtn    = document.getElementById('lc-call-btn');
var lcCallInc    = document.getElementById('lc-call-incoming');
var lcCallAnsBtn = document.getElementById('lc-call-ans-btn');
var lcCallRejBtn = document.getElementById('lc-call-rej-btn');
var lcCallOvr    = document.getElementById('lc-call-overlay');
var lcCallTimer  = document.getElementById('lc-call-timer');
var lcCallStatus = document.getElementById('lc-call-status-txt');
var lcMuteBtn    = document.getElementById('lc-call-mute-btn');
var lcEndCallBtn = document.getElementById('lc-call-end-btn');

var iceServers = [{urls:'stun:stun.l.google.com:19302'},{urls:'stun:stun1.l.google.com:19302'}];
var vc = {pc:null, stream:null, timer:null, sigPoll:null, seconds:0, incomingOffer:null};

function vcResetPc(){
    if(vc.pc){try{vc.pc.close();}catch(e){}}
    vc.pc = new RTCPeerConnection({iceServers:iceServers});
    vc.pc.onicecandidate = function(e){
        if(e.candidate && sessionId){
            fetch('ajax/call_ice.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({session_id:sessionId,candidate:e.candidate.toJSON()})}).catch(function(){});
        }
    };
    vc.pc.ontrack = function(e){
        var a=document.getElementById('lc-remote-audio');
        if(!a){a=document.createElement('audio');a.id='lc-remote-audio';a.autoplay=true;document.body.appendChild(a);}
        a.srcObject=e.streams[0];
    };
    vc.pc.onconnectionstatechange = function(){
        var s=vc.pc.connectionState;
        if(s==='connected'){vcShowActive();}
        if(s==='disconnected'||s==='failed'){vcHangup(false);}
    };
}

function vcShowActive(){
    lcCallInc.style.display='none';
    lcCallOvr.style.display='flex';
    inputBar.style.display='none';
    lcCallBtn.classList.add('lc-in-call');
    if(lcCallStatus)lcCallStatus.textContent='Verbunden';
    clearInterval(vc.timer);
    vc.seconds=0;
    vc.timer=setInterval(function(){
        vc.seconds++;
        var m=Math.floor(vc.seconds/60),s=vc.seconds%60;
        if(lcCallTimer)lcCallTimer.textContent=(m<10?'0'+m:m)+':'+(s<10?'0'+s:s);
    },1000);
}

function vcHangup(sendSignal){
    clearInterval(vc.timer);
    clearInterval(vc.sigPoll);
    vc.timer=null;vc.sigPoll=null;
    if(vc.pc){try{vc.pc.close();}catch(e){}vc.pc=null;}
    if(vc.stream){vc.stream.getTracks().forEach(function(t){t.stop();});vc.stream=null;}
    var a=document.getElementById('lc-remote-audio');if(a)a.srcObject=null;
    lcCallInc.style.display='none';
    lcCallOvr.style.display='none';
    inputBar.style.display=sessionClosed?'none':'flex';
    lcCallBtn.classList.remove('lc-in-call');
    vc.incomingOffer=null;
    if(sendSignal&&sessionId){
        fetch('ajax/call_end.php',{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({session_id:sessionId})}).catch(function(){});
    }
}

/* User initiates call */
lcCallBtn.addEventListener('click',function(){
    if(!sessionId){openChat();return;}
    if(sessionClosed)return;
    if(vc.pc){vcHangup(true);return;}
    navigator.mediaDevices.getUserMedia({audio:true,video:false})
    .then(function(stream){
        vc.stream=stream;
        vcResetPc();
        stream.getTracks().forEach(function(t){vc.pc.addTrack(t,stream);});
        return vc.pc.createOffer();
    })
    .then(function(offer){return vc.pc.setLocalDescription(offer).then(function(){return offer;});})
    .then(function(offer){
        return fetch('ajax/call_start.php',{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({session_id:sessionId,sdp:{type:offer.type,sdp:offer.sdp}})
        }).then(function(r){return r.json();});
    })
    .then(function(res){
        if(!res.success){vcHangup(false);alert(res.message||'Anruf fehlgeschlagen');return;}
        lcCallBtn.classList.add('lc-in-call');
        lcCallOvr.style.display='flex';
        inputBar.style.display='none';
        if(lcCallStatus)lcCallStatus.textContent='Klingelt\u2026';
        vcStartSigPoll();
    })
    .catch(function(err){
        vcHangup(false);
        if(err.name==='NotAllowedError'){alert('Mikrofonzugriff verweigert. Bitte erlauben Sie den Mikrofonzugriff.');}
        else{alert('Anruf konnte nicht gestartet werden.');}
    });
});

/* User answers admin-initiated call */
lcCallAnsBtn.addEventListener('click',function(){
    if(!vc.incomingOffer)return;
    var offer=vc.incomingOffer;
    vc.incomingOffer=null;
    lcCallInc.style.display='none';
    navigator.mediaDevices.getUserMedia({audio:true,video:false})
    .then(function(stream){
        vc.stream=stream;
        vcResetPc();
        stream.getTracks().forEach(function(t){vc.pc.addTrack(t,stream);});
        return vc.pc.setRemoteDescription(new RTCSessionDescription(offer));
    })
    .then(function(){return vc.pc.createAnswer();})
    .then(function(ans){return vc.pc.setLocalDescription(ans).then(function(){return ans;});})
    .then(function(ans){
        return fetch('ajax/call_answer.php',{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({session_id:sessionId,sdp:{type:ans.type,sdp:ans.sdp}})
        }).then(function(r){return r.json();});
    })
    .then(function(res){
        if(!res.success){vcHangup(false);return;}
        vcShowActive();
        vcStartSigPoll();
    })
    .catch(function(err){console.error('Answer error:',err);vcHangup(false);});
});

/* User rejects admin-initiated call */
lcCallRejBtn.addEventListener('click',function(){
    lcCallInc.style.display='none';
    vc.incomingOffer=null;
    clearInterval(vc.sigPoll);vc.sigPoll=null;
    if(sessionId){
        fetch('ajax/call_reject.php',{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({session_id:sessionId})}).catch(function(){});
    }
});

/* Mute toggle */
lcMuteBtn.addEventListener('click',function(){
    if(!vc.stream)return;
    var enabled=vc.stream.getAudioTracks()[0].enabled;
    vc.stream.getAudioTracks().forEach(function(t){t.enabled=!enabled;});
    lcMuteBtn.classList.toggle('muted');
    lcMuteBtn.innerHTML=lcMuteBtn.classList.contains('muted')?'&#x1F507;':'&#x1F399;';
    lcMuteBtn.title=lcMuteBtn.classList.contains('muted')?'Stummschaltung aufheben':'Stummschalten';
});

/* End call button */
lcEndCallBtn.addEventListener('click',function(){vcHangup(true);});

/* Signal polling */
function vcStartSigPoll(){
    clearInterval(vc.sigPoll);
    vc.sigPoll=setInterval(vcPollSignals,1500);
}
function vcPollSignals(){
    if(!sessionId)return;
    fetch('ajax/call_poll.php?session_id='+sessionId)
    .then(function(r){return r.json();})
    .then(function(res){
        if(!res.success)return;
        res.signals.forEach(function(sig){vcHandleSignal(sig);});
    }).catch(function(){});
}
function vcHandleSignal(sig){
    var type=sig.type, payload=sig.payload;
    if(type==='offer'){
        vc.incomingOffer=payload;
        lcCallInc.style.display='flex';
        playNotifSound();
        vcStartSigPoll();
    } else if(type==='answer'){
        if(vc.pc&&vc.pc.signalingState!=='stable'){
            vc.pc.setRemoteDescription(new RTCSessionDescription(payload))
            .then(function(){vcShowActive();})
            .catch(function(e){console.error('setRemoteDescription:',e);});
        }
    } else if(type==='ice-candidate'){
        if(vc.pc&&payload){
            vc.pc.addIceCandidate(new RTCIceCandidate(payload)).catch(function(e){console.error('addIceCandidate:',e);});
        }
    } else if(type==='reject'){
        vcHangup(false);
        appendSystemMsg('\u{1F4DE} Anruf abgelehnt.');
    } else if(type==='end'){
        vcHangup(false);
        appendSystemMsg('\u{1F4DE} Anruf beendet.');
    }
}

/* Start signal polling when chat widget is opened and a session exists */
var _origOpenChat=openChat;
openChat=function(){
    _origOpenChat();
    if(sessionId&&!sessionClosed&&!vc.pc)vcStartSigPoll();
};
})();
})();
</script>
