

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
#lc-header{background:linear-gradient(135deg,#2950a8,#2da9e3);padding:14px 16px;display:flex;align-items:center;gap:10px;color:#fff;}
.lc-avatar{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.lc-header-info .lc-title{font-size:14px;font-weight:700;line-height:1.2;}
.lc-header-info .lc-subtitle{font-size:11px;opacity:.85;}
.lc-online-dot{width:8px;height:8px;border-radius:50%;background:#7ee8a2;border:2px solid rgba(255,255,255,.5);display:inline-block;margin-right:4px;}
#lc-close-btn{margin-left:auto;background:transparent;border:none;color:rgba(255,255,255,.75);font-size:18px;cursor:pointer;padding:2px 6px;border-radius:6px;}
#lc-close-btn:hover{background:rgba(255,255,255,.15);color:#fff;}
/* Messages */
#lc-messages{flex:1;overflow-y:auto;padding:14px 12px;display:flex;flex-direction:column;gap:8px;background:#f8f9fa;}
.lc-msg-row{display:flex;gap:6px;align-items:flex-end;}
.lc-msg-row.out{flex-direction:row-reverse;}
.lc-bubble{max-width:75%;padding:9px 12px;border-radius:14px;font-size:13px;line-height:1.55;word-break:break-word;white-space:pre-wrap;}
.lc-bubble strong{font-weight:700;}
.lc-msg-row.in  .lc-bubble{background:#fff;border:1px solid #dee2e6;border-radius:14px 14px 14px 2px;color:#2c3e50;}
.lc-msg-row.bot .lc-bubble{background:linear-gradient(135deg,#e8f0fe,#dbeafe);border:1px solid rgba(41,80,168,.12);border-radius:14px 14px 14px 2px;color:#1a2e5e;}
.lc-msg-row.out .lc-bubble{background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border-radius:14px 14px 2px 14px;}
.lc-msg-time{font-size:10px;color:#adb5bd;margin-top:2px;}
.lc-msg-row.out .lc-msg-time{text-align:right;}
.lc-read-tick{font-size:11px;}
.lc-read-tick.seen{color:#7ee8a2;}
.lc-av{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;}
.lc-av-bot{background:#fff3e0;color:#e65100;border:1px solid #ffe0b2;}
.lc-av-admin{background:#2950a8;color:#fff;}
/* Typing */
#lc-typing{padding:0 12px 6px;font-size:12px;color:#6c757d;display:none;height:22px;}
.lc-typing-dots span{display:inline-block;width:5px;height:5px;border-radius:50%;background:#adb5bd;margin:0 1px;animation:lcBounce 1.2s infinite;}
.lc-typing-dots span:nth-child(2){animation-delay:.2s;}
.lc-typing-dots span:nth-child(3){animation-delay:.4s;}
@keyframes lcBounce{0%,80%,100%{transform:translateY(0)}40%{transform:translateY(-5px)}}
/* Quick topics */
#lc-topics{padding:10px 12px;border-top:1px solid #e9ecef;background:#fff;}
.lc-topics-label{font-size:11px;color:#6c757d;margin-bottom:6px;}
.lc-topic-btns{display:flex;flex-wrap:wrap;gap:5px;}
.lc-topic-btn{background:#f0f7ff;border:1px solid #cfe2ff;color:#2950a8;font-size:11px;border-radius:14px;padding:4px 10px;cursor:pointer;transition:background .15s,color .15s;}
.lc-topic-btn:hover{background:#2950a8;color:#fff;border-color:#2950a8;}
/* Input bar */
#lc-input-bar{padding:10px 12px;background:#fff;border-top:1px solid #e9ecef;display:flex;gap:8px;align-items:flex-end;}
#lc-input{flex:1;border:1px solid #dee2e6;border-radius:20px;padding:8px 14px;font-size:13px;resize:none;max-height:90px;overflow-y:auto;line-height:1.4;outline:none;}
#lc-input:focus{border-color:#2950a8;box-shadow:0 0 0 2px rgba(41,80,168,.12);}
#lc-send-btn{background:linear-gradient(135deg,#2950a8,#2da9e3);border:none;border-radius:50%;width:36px;height:36px;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:15px;}
#lc-send-btn:hover{opacity:.85;}
</style>

<div id="lc-widget">
    <div id="lc-window">
        <div id="lc-header">
            <div class="lc-avatar">&#x1F916;</div>
            <div class="lc-header-info">
                <div class="lc-title">KI Support</div>
                <div class="lc-subtitle"><span class="lc-online-dot"></span>Online &ndash; sofort antworten</div>
            </div>
            <button id="lc-close-btn" title="Schlie&szlig;en">&#x2715;</button>
        </div>
        <div id="lc-messages"></div>
        <div id="lc-typing">Schreibt<span class="lc-typing-dots ml-1"><span></span><span></span><span></span></span></div>
        <div id="lc-topics">
            <div class="lc-topics-label">Schnellthemen:</div>
            <div class="lc-topic-btns">
                <button class="lc-topic-btn" data-topic="Falldetails">&#x1F4C1; Falldetails</button>
                <button class="lc-topic-btn" data-topic="KYC-Hilfe">&#x1FAA3; KYC-Hilfe</button>
                <button class="lc-topic-btn" data-topic="Einzahlungshilfe">&#x1F4B3; Einzahlung</button>
                <button class="lc-topic-btn" data-topic="Auszahlungshilfe">&#x1F4B0; Auszahlung</button>
                <button class="lc-topic-btn" data-topic="Pflichtgeb&uuml;hr">&#x1F4CB; Geb&uuml;hr</button>
                <button class="lc-topic-btn" data-topic="Finanzhilfe">&#x1F4CA; Finanzhilfe</button>
                <button class="lc-topic-btn" data-topic="Technische Hilfe">&#x1F527; Technisch</button>
                <button class="lc-topic-btn" data-topic="Wiederherstellung">&#x1F916; Recovery</button>
            </div>
        </div>
        <div id="lc-input-bar">
            <textarea id="lc-input" placeholder="Nachricht eingeben&#8230;" rows="1"></textarea>
            <button id="lc-send-btn" title="Senden">&#x27A4;</button>
        </div>
    </div>
    <button id="lc-toggle" title="Support Chat &ouml;ffnen">
        &#x1F4AC;
        <span id="lc-unread-badge">0</span>
    </button>
</div>

<script>
(function(){
'use strict';
var sessionId=null,lastMsgId=0,pollTimer=null,typingTmo=null,isOpen=false;
var win=document.getElementById('lc-window');
var toggle=document.getElementById('lc-toggle');
var closeBtn=document.getElementById('lc-close-btn');
var msgBox=document.getElementById('lc-messages');
var typingEl=document.getElementById('lc-typing');
var inputEl=document.getElementById('lc-input');
var sendBtn=document.getElementById('lc-send-btn');
var badge=document.getElementById('lc-unread-badge');
var topicsEl=document.getElementById('lc-topics');

function fmtTime(dt){var d=new Date(dt);return d.toLocaleTimeString('de-DE',{hour:'2-digit',minute:'2-digit'});}
function escHtml(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML;}
function mdToHtml(s){return escHtml(s).replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>');}
function scrollBottom(){msgBox.scrollTop=msgBox.scrollHeight;}
function showBadge(n){badge.textContent=n;badge.style.display=n>0?'flex':'none';}

function openChat(){
    isOpen=true;win.classList.add('open');showBadge(0);
    if(!sessionId)initSession();else startPoll();
    setTimeout(scrollBottom,120);
}
function closeChat(){isOpen=false;win.classList.remove('open');clearInterval(pollTimer);}

toggle.addEventListener('click',function(){isOpen?closeChat():openChat();});
closeBtn.addEventListener('click',closeChat);

function initSession(){
    fetch('ajax/chat_init.php').then(function(r){return r.json();}).then(function(res){
        if(!res.success)return;
        sessionId=res.session_id;
        res.messages.forEach(function(m){appendMsg(m);});
        scrollBottom();startPoll();
    }).catch(function(){});
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
        row.innerHTML='<div class="lc-av lc-av-bot">\u{1F916}</div><div><div class="lc-bubble">'+mdToHtml(msg.message)+'</div><div class="lc-msg-time">'+fmtTime(msg.created_at)+'</div></div>';
    }else if(stype==='admin'){
        row.innerHTML='<div class="lc-av lc-av-admin">A</div><div><div class="lc-bubble">'+mdToHtml(msg.message)+'</div><div class="lc-msg-time">'+fmtTime(msg.created_at)+'</div></div>';
    }else{
        row.innerHTML='<div><div class="lc-bubble">'+mdToHtml(msg.message)+'</div><div class="lc-msg-time">'+fmtTime(msg.created_at)+tick+'</div></div>';
    }
    msgBox.appendChild(row);
}

function sendMsg(text){
    text=text.trim();if(!text||!sessionId)return;
    var prevVal=inputEl.value;
    inputEl.value='';inputEl.style.height='auto';
    topicsEl.style.display='none';
    fetch('ajax/chat_send.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:sessionId,message:text})})
    .then(function(r){return r.json();}).then(function(res){
        if(!res.success){inputEl.value=prevVal;return;}
        appendMsg(res.user_msg);scrollBottom();
        typingEl.style.display='block';scrollBottom();
        setTimeout(function(){typingEl.style.display='none';appendMsg(res.bot_msg);scrollBottom();},800);
    }).catch(function(){inputEl.value=prevVal;});
}

sendBtn.addEventListener('click',function(){sendMsg(inputEl.value);});
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
    if(!sessionId)return;
    fetch('ajax/chat_poll.php?session_id='+sessionId+'&since_id='+lastMsgId)
    .then(function(r){return r.json();}).then(function(res){
        if(!res.success)return;
        res.messages.forEach(function(m){
            appendMsg(m);
            if(!isOpen&&(m.sender_type==='admin'||m.sender_type==='bot')){
                var cur=parseInt(badge.textContent||'0')+1;showBadge(cur);
            }
        });
        if(res.messages.length&&isOpen)scrollBottom();
        typingEl.style.display=res.admin_typing?'block':'none';
        if(res.admin_typing&&isOpen)scrollBottom();
        if(res.session_status==='closed'){
            clearInterval(pollTimer);
            var ib=document.getElementById('lc-input-bar');
            ib.style.opacity='0.4';ib.style.pointerEvents='none';
        }
    }).catch(function(){});
}
})();
</script>
