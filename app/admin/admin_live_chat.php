<?php require_once 'admin_header.php'; ?>

<style>
/* ── Layout ───────────────────────────────────────────────────────────────── */
.chat-wrap{display:flex;gap:0;height:calc(100vh - 140px);min-height:400px;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.1);}
.chat-sidebar{width:300px;min-width:260px;background:#fff;border-right:1px solid #e9ecef;display:flex;flex-direction:column;flex-shrink:0;}
.chat-main{flex:1;display:flex;flex-direction:column;background:#f8f9fa;min-width:0;overflow:hidden;}

/* Sidebar */
.cs-header{padding:16px;background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;}
.cs-header h6{margin:0;font-size:14px;font-weight:700;}
.cs-search{padding:10px 12px;border-bottom:1px solid #e9ecef;}
.cs-search input{border-radius:20px;font-size:13px;padding:6px 14px;}
.session-list{flex:1;overflow-y:auto;-webkit-overflow-scrolling:touch;}
.session-item{padding:12px 16px;border-bottom:1px solid #f0f2f5;cursor:pointer;transition:background .15s;}
.session-item:hover{background:#f0f7ff;}
.session-item.active{background:#e8f0fe;border-left:3px solid #2950a8;}
.si-name{font-size:13px;font-weight:600;color:#2c3e50;}
.si-preview{font-size:11px;color:#6c757d;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;}
.si-time{font-size:10px;color:#adb5bd;}
.si-badge{background:#dc3545;color:#fff;border-radius:10px;font-size:10px;padding:1px 6px;font-weight:700;}
.si-status{width:8px;height:8px;border-radius:50%;background:#28a745;display:inline-block;margin-right:4px;}
.si-status.closed{background:#adb5bd;}

/* Chat pane */
.chat-pane-empty{flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;color:#adb5bd;}
.chat-pane{display:none;flex:1;flex-direction:column;min-height:0;overflow:hidden;}
.chat-pane.active{display:flex;}
.chat-pane-header{padding:14px 20px;background:#fff;border-bottom:1px solid #e9ecef;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.chat-pane-header .user-info .name{font-size:14px;font-weight:700;color:#2c3e50;}
.chat-pane-header .user-info .email{font-size:11px;color:#6c757d;}
/* Back button — hidden on desktop */
.btn-back-sidebar{display:none;background:none;border:none;color:#2950a8;font-size:18px;padding:0 8px 0 0;cursor:pointer;flex-shrink:0;}
.chat-messages{flex:1;overflow-y:auto;-webkit-overflow-scrolling:touch;padding:16px;display:flex;flex-direction:column;gap:10px;min-height:0;}
.msg-row{display:flex;gap:8px;}
.msg-row.user{justify-content:flex-start;}
.msg-row.admin{justify-content:flex-end;}
.msg-row.bot{justify-content:flex-start;}
.msg-content{max-width:75%;min-width:0;}
.msg-bubble{padding:10px 14px;border-radius:14px;font-size:13px;line-height:1.55;word-break:break-word;white-space:pre-wrap;}
.msg-bubble strong{font-weight:700;}
.msg-row.user  .msg-bubble{background:#fff;border:1px solid #dee2e6;border-radius:14px 14px 14px 2px;}
.msg-row.bot   .msg-bubble{background:linear-gradient(135deg,#e8f0fe,#dbeafe);border:1px solid rgba(41,80,168,.15);border-radius:14px 14px 14px 2px;}
.msg-row.admin .msg-bubble{background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border-radius:14px 14px 2px 14px;}
.msg-meta{font-size:10px;color:#adb5bd;margin-top:3px;text-align:right;}
.msg-row.user  .msg-meta{text-align:left;}
.msg-row.bot   .msg-meta{text-align:left;}
.msg-sender-name{font-size:10px;font-weight:700;color:#2950a8;margin-bottom:2px;text-align:right;}
.read-tick{color:rgba(255,255,255,.7);font-size:11px;}
.read-tick.seen{color:#7ee8a2;}
.sender-avatar{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;margin-top:4px;}
.av-user{background:#e8f4fd;color:#2950a8;}
.av-bot{background:#fff3e0;color:#e65100;}
.av-admin{background:#2950a8;color:#fff;}
.typing-indicator{display:none;font-size:12px;color:#6c757d;padding:4px 0 0 36px;height:22px;flex-shrink:0;}
.typing-dots span{display:inline-block;width:6px;height:6px;border-radius:50%;background:#adb5bd;margin:0 2px;animation:typingBounce 1.2s infinite;}
.typing-dots span:nth-child(2){animation-delay:.2s;}
.typing-dots span:nth-child(3){animation-delay:.4s;}
@keyframes typingBounce{0%,80%,100%{transform:translateY(0)}40%{transform:translateY(-6px)}}
.chat-input-bar{padding:10px 12px;background:#fff;border-top:1px solid #e9ecef;display:flex;gap:8px;align-items:flex-end;flex-shrink:0;}
.chat-input-bar textarea{resize:none;border-radius:10px;font-size:13px;padding:10px 14px;flex:1;max-height:100px;overflow-y:auto;-webkit-overflow-scrolling:touch;}
.btn-send{background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;border-radius:10px;width:42px;height:42px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;}
.btn-send:hover{opacity:.88;}

/* Voice call */
.btn-call{border-radius:8px;font-size:12px;transition:background .15s;}
.btn-call.ringing{animation:adminCallPulse 1s infinite;}
@keyframes adminCallPulse{0%,100%{opacity:1}50%{opacity:.45}}
.call-inc-bar{padding:10px 14px;background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;display:none;flex-direction:column;gap:6px;flex-shrink:0;}
.call-inc-title{font-size:13px;font-weight:700;}
.call-inc-sub{font-size:11px;opacity:.85;}
.call-inc-btns{display:flex;gap:8px;}
.call-ans-btn{background:#28a745;border:none;color:#fff;border-radius:20px;padding:5px 14px;font-size:12px;cursor:pointer;font-weight:600;}
.call-rej-btn{background:#dc3545;border:none;color:#fff;border-radius:20px;padding:5px 14px;font-size:12px;cursor:pointer;font-weight:600;}
.call-active-bar{padding:8px 14px;background:#f0f7ff;border-top:2px solid #2950a8;display:none;flex-direction:row;align-items:center;gap:10px;flex-shrink:0;}
.call-active-timer{font-size:13px;font-weight:700;color:#2950a8;min-width:46px;}
.call-active-status{font-size:11px;color:#6c757d;flex:1;}
.call-ctrl-mute,.call-ctrl-end{border:none;border-radius:50%;width:34px;height:34px;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;}
.call-ctrl-mute{background:#e9ecef;color:#495057;}
.call-ctrl-mute.muted{background:#ffc107;}
.call-ctrl-end{background:#dc3545;color:#fff;}

/* ── Mobile (≤ 767 px) ─────────────────────────────────────────────────────── */
@media (max-width:767px){
    /* Remove extra vertical space so the chat fills the screen properly */
    .chat-wrap{
        height:calc(100vh - 110px);
        min-height:0;
        border-radius:0;
        box-shadow:none;
        position:relative;
    }
    /* Sidebar: full-width overlay, slides to left when chat is open */
    .chat-sidebar{
        position:absolute;
        top:0;left:0;
        width:100%;height:100%;
        z-index:10;
        transition:transform .25s ease;
    }
    .chat-sidebar.hidden-mobile{
        transform:translateX(-100%);
        pointer-events:none;
    }
    /* Main chat pane fills the full wrap */
    .chat-main{
        position:absolute;
        top:0;left:0;
        width:100%;height:100%;
    }
    /* Show back-arrow button */
    .btn-back-sidebar{display:inline-flex;}
    /* Message bubbles can use more width on narrow screens */
    .msg-content{max-width:88%;}
    /* Tighten header padding */
    .chat-pane-header{padding:10px 12px;}
    /* Page header: shrink */
    .page-header h2{font-size:16px;}
}
</style>

<div class="main-content">
    <div class="page-header">
        <h2>Live Chat</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Live Chat</span>
            </nav>
        </div>
    </div>

    <div class="chat-wrap">
        <!-- ── Sidebar ── -->
        <div class="chat-sidebar">
            <div class="cs-header">
                <h6><i class="anticon anticon-message mr-2"></i>Live Chat Sessions</h6>
                <div class="d-flex align-items-center mt-2" style="gap:6px;">
                    <button class="btn btn-sm btn-light filter-btn active" data-status="active" style="border-radius:14px;font-size:11px;padding:3px 10px;">Aktiv</button>
                    <button class="btn btn-sm btn-light filter-btn" data-status="closed" style="border-radius:14px;font-size:11px;padding:3px 10px;">Beendet</button>
                    <button class="btn btn-sm btn-light filter-btn" data-status="all" style="border-radius:14px;font-size:11px;padding:3px 10px;">Alle</button>
                    <span id="totalBadge" class="si-badge ml-auto">0</span>
                </div>
            </div>
            <div class="cs-search">
                <input type="text" id="sessionSearch" class="form-control form-control-sm" placeholder="Benutzer suchen…">
            </div>
            <div class="session-list" id="sessionList">
                <div class="text-center text-muted py-4" style="font-size:12px;">Lade Sitzungen…</div>
            </div>
        </div>

        <!-- ── Main Chat Pane ── -->
        <div class="chat-main">
            <!-- Empty state -->
            <div class="chat-pane-empty" id="chatEmpty">
                <i class="anticon anticon-message" style="font-size:48px;color:#dee2e6;margin-bottom:12px;"></i>
                <p style="font-size:14px;">Wählen Sie eine Chat-Sitzung aus der Liste</p>
            </div>

            <!-- Active chat -->
            <div class="chat-pane" id="chatPane">
                <div class="chat-pane-header">
                    <button class="btn-back-sidebar" id="btnBackSidebar" title="Zurück zur Liste">&#x2190;</button>
                    <div class="user-info">
                        <div class="name" id="chatUserName">—</div>
                        <div class="email" id="chatUserEmail">—</div>
                    </div>
                    <div class="d-flex align-items-center" style="gap:8px;">
                        <span id="chatStatusBadge" class="badge badge-success" style="font-size:11px;">Aktiv</span>
                        <button class="btn btn-sm btn-outline-success btn-call" id="btnCallUser" style="display:none;" title="Sprachanruf starten">
                            &#x1F4DE; Anruf
                        </button>
                        <button class="btn btn-sm btn-outline-danger" id="btnCloseSession" style="border-radius:8px;font-size:12px;">
                            <i class="anticon anticon-close-circle mr-1"></i>Schlie&szlig;en
                        </button>
                    </div>
                </div>

                <!-- Incoming call notification -->
                <div class="call-inc-bar" id="adminCallIncoming">
                    <div class="call-inc-title">&#x1F4DE; Eingehender Sprachanruf</div>
                    <div class="call-inc-sub" id="adminCallIncSub">Benutzer m&ouml;chte einen Anruf starten</div>
                    <div class="call-inc-btns">
                        <button class="call-ans-btn" id="adminCallAnsBtn" type="button">&#x2714; Annehmen</button>
                        <button class="call-rej-btn" id="adminCallRejBtn" type="button">&#x2715; Ablehnen</button>
                    </div>
                </div>

                <!-- Active call bar -->
                <div class="call-active-bar" id="adminCallActive">
                    <div class="call-active-timer" id="adminCallTimer">00:00</div>
                    <div class="call-active-status" id="adminCallStatusTxt">Verbinde&#x2026;</div>
                    <button class="call-ctrl-mute" id="adminCallMuteBtn" type="button" title="Stummschalten">&#x1F399;</button>
                    <button class="call-ctrl-end" id="adminCallEndBtn" type="button" title="Anruf beenden">&#x1F6AB;</button>
                </div>

                <div class="chat-messages" id="chatMessages"></div>

                <div class="typing-indicator" id="adminTypingIndicator">
                    Benutzer schreibt<span class="typing-dots ml-1"><span></span><span></span><span></span></span>
                </div>

                <div class="chat-input-bar" id="chatInputBar">
                    <textarea id="adminMsgInput" class="form-control" rows="1" placeholder="Nachricht eingeben… (Enter = Senden, Shift+Enter = neue Zeile)"></textarea>
                    <button class="btn-send" id="btnAdminSend" title="Senden"><i class="anticon anticon-send"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    'use strict';

    let activeSessionId   = null;
    let lastMsgId         = 0;
    let currentStatus     = 'active';
    let pollTimer         = null;
    let sessionTimer      = null;
    let typingTimer       = null;
    const allSessions     = {};

    // ── Helpers ─────────────────────────────────────────────────────────────
    function fmtTime(dt){
        const d = new Date(dt);
        return d.toLocaleTimeString('de-DE',{hour:'2-digit',minute:'2-digit'});
    }
    function escHtml(s){ const d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML; }
    function mdToHtml(s){
        if(s && s.substring(0,10)==='__ATTACH__'){
            const payload=s.substring(11);
            const parts=payload.split('|');
            const url=parts[0]||'';
            const name=parts[1]||'Datei';
            const isImg=/\.(jpe?g|png|gif|webp)$/i.test(url);
            const absUrl='../'+url;
            if(isImg){
                return `<img src="${escHtml(absUrl)}" style="max-width:200px;max-height:180px;border-radius:8px;display:block;cursor:pointer;margin-top:4px;" alt="${escHtml(name)}" onclick="window.open(this.src)">`;
            }
            return `<a href="${escHtml(absUrl)}" style="display:inline-flex;align-items:center;gap:4px;font-size:12px;padding:5px 8px;background:#f0f7ff;border:1px solid #cfe2ff;border-radius:8px;color:#2950a8;text-decoration:none;margin-top:4px;" target="_blank" rel="noopener">&#x1F4CE; ${escHtml(name)}</a>`;
        }
        return escHtml(s)
            .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
            .replace(/• /g,'• ');
    }
    function scrollBottom(){
        const el=document.getElementById('chatMessages');
        if(el) el.scrollTop=el.scrollHeight;
    }

    // ── Mobile sidebar toggle ────────────────────────────────────────────────
    const sidebar = document.getElementById ? document.querySelector('.chat-sidebar') : null;
    function showChatPane(){
        if(window.innerWidth<=767 && sidebar){
            sidebar.classList.add('hidden-mobile');
        }
    }
    function showSidebarPane(){
        if(sidebar) sidebar.classList.remove('hidden-mobile');
    }
    const btnBack = document.getElementById('btnBackSidebar');
    if(btnBack) btnBack.addEventListener('click', showSidebarPane);

    // ── Load session list ────────────────────────────────────────────────────
    function loadSessions(){
        fetch('admin_ajax/chat_sessions.php?status='+currentStatus)
            .then(r=>r.json())
            .then(res=>{
                if(!res.success) return;
                const list=document.getElementById('sessionList');
                const search=(document.getElementById('sessionSearch').value||'').toLowerCase();
                const data=res.data.filter(s=>!search||s.user_name.toLowerCase().includes(search)||s.user_email.toLowerCase().includes(search));
                document.getElementById('totalBadge').textContent=data.length;
                list.innerHTML='';
                if(!data.length){
                    list.innerHTML='<div class="text-center text-muted py-4" style="font-size:12px;">Keine Sitzungen</div>';
                    return;
                }
                data.forEach(s=>{
                    allSessions[s.id]=s;
                    const div=document.createElement('div');
                    div.className='session-item'+(s.id==activeSessionId?' active':'');
                    div.dataset.id=s.id;
                    const preview=(s.last_message||'Keine Nachrichten').substring(0,50);
                    div.innerHTML=`
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="si-name"><span class="si-status${s.status=='closed'?' closed':''}"></span>${escHtml(s.user_name)}</div>
                                <div class="si-preview">${escHtml(preview)}</div>
                            </div>
                            <div class="text-right">
                                <div class="si-time">${fmtTime(s.updated_at)}</div>
                                ${parseInt(s.unread_admin)>0?`<span class="si-badge">${s.unread_admin}</span>`:''}
                            </div>
                        </div>
                    `;
                    div.addEventListener('click',()=>openSession(s.id,s));
                    list.appendChild(div);
                });
            })
            .catch(()=>{});
    }

    // ── Open a session ───────────────────────────────────────────────────────
    function openSession(id, meta){
        activeSessionId=parseInt(id);
        lastMsgId=0;

        document.querySelectorAll('.session-item').forEach(el=>el.classList.toggle('active',parseInt(el.dataset.id)===activeSessionId));
        document.getElementById('chatEmpty').style.display='none';
        const pane=document.getElementById('chatPane');
        pane.classList.add('active');
        document.getElementById('chatUserName').textContent=meta.user_name;
        document.getElementById('chatUserEmail').textContent=meta.user_email;

        const isActive=meta.status==='active';
        document.getElementById('chatStatusBadge').className='badge badge-'+(isActive?'success':'secondary');
        document.getElementById('chatStatusBadge').textContent=isActive?'Aktiv':'Beendet';
        document.getElementById('chatInputBar').style.display=isActive?'flex':'none';
        document.getElementById('btnCloseSession').style.display=isActive?'':'none';

        document.getElementById('chatMessages').innerHTML='';
        fetchMessages(false);
        showChatPane(); /* slide sidebar away on mobile */

        clearInterval(pollTimer);
        if(isActive){
            pollTimer=setInterval(()=>fetchMessages(true),2000);
        }
    }

    // ── Fetch messages ───────────────────────────────────────────────────────
    function fetchMessages(poll){
        const url='admin_ajax/chat_messages.php?session_id='+activeSessionId+(poll?'&since_id='+lastMsgId:'');
        fetch(url)
            .then(r=>r.json())
            .then(res=>{
                if(!res.success) return;
                res.messages.forEach(appendMsg);
                if(res.messages.length) scrollBottom();

                // Typing
                const ti=document.getElementById('adminTypingIndicator');
                ti.style.display=res.user_typing?'block':'none';
                if(res.user_typing) scrollBottom();

                // Live read-receipt ticks: update ✓ → ✓✓ for admin messages user has now read
                if(res.read_admin_msg_ids && res.read_admin_msg_ids.length){
                    res.read_admin_msg_ids.forEach(id=>{
                        const tick=document.querySelector('[data-msg-id="'+id+'"] .read-tick');
                        if(tick && !tick.classList.contains('seen')){
                            tick.classList.add('seen');
                            tick.textContent=' \u2713\u2713';
                        }
                    });
                }

                // Detect session closed by user during active poll
                if(poll && res.session_status==='closed'){
                    const sb=document.getElementById('chatStatusBadge');
                    if(sb && sb.textContent!=='Beendet'){
                        clearInterval(pollTimer);
                        sb.className='badge badge-secondary';
                        sb.textContent='Beendet';
                        document.getElementById('chatInputBar').style.display='none';
                        document.getElementById('btnCloseSession').style.display='none';
                        // Append system notice
                        const sysRow=document.createElement('div');
                        sysRow.style.cssText='display:flex;justify-content:center;padding:4px 0;';
                        sysRow.innerHTML='<div style="font-size:11px;color:#adb5bd;padding:4px 12px;background:#f0f2f5;border-radius:10px;">Sitzung vom Benutzer beendet</div>';
                        document.getElementById('chatMessages').appendChild(sysRow);
                        scrollBottom();
                        loadSessions();
                    }
                }
            })
            .catch(()=>{});
    }

    // ── Append message ───────────────────────────────────────────────────────
    function appendMsg(msg){
        if(msg.id>lastMsgId) lastMsgId=parseInt(msg.id);
        const existing=document.querySelector('[data-msg-id="'+msg.id+'"]');
        if(existing) return;

        const stype=msg.sender_type;
        const row=document.createElement('div');
        row.className='msg-row '+stype;
        row.dataset.msgId=msg.id;

        // Avatar label: initials for admin, U for user, 🤖 for bot
        const agentName = (msg.sender_name||'').trim();
        let avLabel;
        if(stype==='admin'){
            if(agentName){
                const parts=agentName.split(' ');
                avLabel=parts.length>=2?(parts[0][0]+parts[1][0]).toUpperCase():agentName.substring(0,2).toUpperCase();
            } else {
                avLabel='A';
            }
        } else if(stype==='bot'){
            avLabel='🤖';
        } else {
            avLabel='U';
        }
        const avClass=stype==='user'?'av-user':stype==='bot'?'av-bot':'av-admin';
        const isRead=parseInt(msg.is_read)===1;
        const readMark=stype==='admin'?`<span class="read-tick${isRead?' seen':''}"> ${isRead?'✓✓':'✓'}</span>`:'';
        const nameLabel=stype==='admin'&&agentName?`<div class="msg-sender-name">${escHtml(agentName)}</div>`:'';

        row.innerHTML=`
            ${stype!=='admin'?`<div class="sender-avatar ${avClass}">${avLabel}</div>`:''}
            <div class="msg-content">
                ${nameLabel}
                <div class="msg-bubble">${mdToHtml(msg.message)}</div>
                <div class="msg-meta">${fmtTime(msg.created_at)}${readMark}</div>
            </div>
            ${stype==='admin'?`<div class="sender-avatar av-admin">${avLabel}</div>`:''}
        `;
        document.getElementById('chatMessages').appendChild(row);
    }

    // ── Send admin message ───────────────────────────────────────────────────
    function sendAdminMsg(){
        const input=document.getElementById('adminMsgInput');
        const text=input.value.trim();
        if(!text||!activeSessionId) return;
        input.value='';
        input.style.height='auto';

        fetch('admin_ajax/chat_send.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({session_id:activeSessionId,message:text})
        })
        .then(r=>r.json())
        .then(res=>{
            if(res.success) { appendMsg(res.message); scrollBottom(); }
        });
    }

    // ── Close session ────────────────────────────────────────────────────────
    document.getElementById('btnCloseSession').addEventListener('click',()=>{
        if(!activeSessionId) return;
        if(!confirm('Chat-Sitzung wirklich schließen?')) return;
        fetch('admin_ajax/chat_close.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({session_id:activeSessionId})
        })
        .then(r=>r.json())
        .then(res=>{
            if(res.success){
                clearInterval(pollTimer);
                document.getElementById('chatInputBar').style.display='none';
                document.getElementById('btnCloseSession').style.display='none';
                document.getElementById('chatStatusBadge').className='badge badge-secondary';
                document.getElementById('chatStatusBadge').textContent='Beendet';
                loadSessions();
            }
        });
    });

    // ── Keyboard ─────────────────────────────────────────────────────────────
    const input=document.getElementById('adminMsgInput');
    input.addEventListener('keydown',e=>{
        if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendAdminMsg();return;}
        // Typing indicator
        clearTimeout(typingTimer);
        if(activeSessionId){
            fetch('admin_ajax/chat_typing.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:activeSessionId})});
        }
    });
    input.addEventListener('input',()=>{ input.style.height='auto'; input.style.height=Math.min(input.scrollHeight,120)+'px'; });
    document.getElementById('btnAdminSend').addEventListener('click',sendAdminMsg);

    // ── Filter buttons ───────────────────────────────────────────────────────
    document.querySelectorAll('.filter-btn').forEach(btn=>{
        btn.addEventListener('click',function(){
            document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active','btn-primary'));
            this.classList.add('active');
            currentStatus=this.dataset.status;
            loadSessions();
        });
    });

    // ── Search ───────────────────────────────────────────────────────────────
    document.getElementById('sessionSearch').addEventListener('input',loadSessions);

    // ── Session refresh every 5 s ─────────────────────────────────────────────
    loadSessions();
    sessionTimer=setInterval(loadSessions,5000);

    // ── Voice Call (WebRTC) ─────────────────────────────────────────────────
    var iceServers=[
        {urls:'stun:stun.l.google.com:19302'},
        {urls:'stun:stun1.l.google.com:19302'}
        /* For production behind strict firewalls add a TURN server:
           {urls:'turn:your-turn-server:3478',username:'user',credential:'pass'} */
    ];
    var vc={pc:null,stream:null,timer:null,sigPoll:null,seconds:0,incomingOffer:null};

    var adminCallIncoming  = document.getElementById('adminCallIncoming');
    var adminCallAnsBtn    = document.getElementById('adminCallAnsBtn');
    var adminCallRejBtn    = document.getElementById('adminCallRejBtn');
    var adminCallActive    = document.getElementById('adminCallActive');
    var adminCallTimer     = document.getElementById('adminCallTimer');
    var adminCallStatusTxt = document.getElementById('adminCallStatusTxt');
    var adminCallMuteBtn   = document.getElementById('adminCallMuteBtn');
    var adminCallEndBtn    = document.getElementById('adminCallEndBtn');
    var btnCallUser        = document.getElementById('btnCallUser');

    function vcResetPc(){
        if(vc.pc){try{vc.pc.close();}catch(e){}}
        vc.pc=new RTCPeerConnection({iceServers:iceServers});
        vc.pc.onicecandidate=function(e){
            if(e.candidate&&activeSessionId){
                fetch('admin_ajax/call_ice.php',{method:'POST',headers:{'Content-Type':'application/json'},
                    body:JSON.stringify({session_id:activeSessionId,candidate:e.candidate.toJSON()})}).catch(function(){});
            }
        };
        vc.pc.ontrack=function(e){
            var a=document.getElementById('admin-remote-audio');
            if(!a){a=document.createElement('audio');a.id='admin-remote-audio';a.autoplay=true;document.body.appendChild(a);}
            a.srcObject=e.streams[0];
        };
        vc.pc.onconnectionstatechange=function(){
            var s=vc.pc.connectionState;
            if(s==='connected'){vcShowActive();}
            if(s==='disconnected'||s==='failed'){vcHangup(false);}
        };
    }

    function vcShowActive(){
        adminCallIncoming.style.display='none';
        adminCallActive.style.display='flex';
        if(adminCallStatusTxt)adminCallStatusTxt.textContent='Verbunden';
        if(btnCallUser)btnCallUser.classList.add('ringing');
        clearInterval(vc.timer);
        vc.seconds=0;
        vc.timer=setInterval(function(){
            vc.seconds++;
            var m=Math.floor(vc.seconds/60),s=vc.seconds%60;
            if(adminCallTimer)adminCallTimer.textContent=(m<10?'0'+m:m)+':'+(s<10?'0'+s:s);
        },1000);
    }

    function vcHangup(sendSignal){
        clearInterval(vc.timer);
        clearInterval(vc.sigPoll);
        vc.timer=null;vc.sigPoll=null;
        if(vc.pc){try{vc.pc.close();}catch(e){}vc.pc=null;}
        if(vc.stream){vc.stream.getTracks().forEach(function(t){t.stop();});vc.stream=null;}
        var a=document.getElementById('admin-remote-audio');if(a)a.srcObject=null;
        adminCallIncoming.style.display='none';
        adminCallActive.style.display='none';
        if(btnCallUser){btnCallUser.classList.remove('ringing');btnCallUser.innerHTML='&#x1F4DE; Anruf';}
        vc.incomingOffer=null;
        if(sendSignal&&activeSessionId){
            fetch('admin_ajax/call_end.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({session_id:activeSessionId})}).catch(function(){});
        }
    }

    /* Admin initiates call */
    if(btnCallUser){
        btnCallUser.addEventListener('click',function(){
            if(!activeSessionId)return;
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
                return fetch('admin_ajax/call_start.php',{method:'POST',headers:{'Content-Type':'application/json'},
                    body:JSON.stringify({session_id:activeSessionId,sdp:{type:offer.type,sdp:offer.sdp}})
                }).then(function(r){return r.json();});
            })
            .then(function(res){
                if(!res.success){vcHangup(false);alert(res.message||'Anruf fehlgeschlagen');return;}
                btnCallUser.classList.add('ringing');
                adminCallActive.style.display='flex';
                if(adminCallStatusTxt)adminCallStatusTxt.textContent='Klingelt\u2026';
                vcStartSigPoll();
            })
            .catch(function(err){
                vcHangup(false);
                if(err.name==='NotAllowedError'){alert('Mikrofonzugriff verweigert.');}
                else{alert('Anruf konnte nicht gestartet werden.');}
            });
        });
    }

    /* Admin answers user-initiated call */
    if(adminCallAnsBtn){
        adminCallAnsBtn.addEventListener('click',function(){
            if(!vc.incomingOffer)return;
            var offer=vc.incomingOffer;
            vc.incomingOffer=null;
            adminCallIncoming.style.display='none';
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
                return fetch('admin_ajax/call_answer.php',{method:'POST',headers:{'Content-Type':'application/json'},
                    body:JSON.stringify({session_id:activeSessionId,sdp:{type:ans.type,sdp:ans.sdp}})
                }).then(function(r){return r.json();});
            })
            .then(function(res){
                if(!res.success){vcHangup(false);return;}
                vcShowActive();
                vcStartSigPoll();
            })
            .catch(function(err){console.error('Answer error:',err);vcHangup(false);});
        });
    }

    /* Admin rejects user-initiated call */
    if(adminCallRejBtn){
        adminCallRejBtn.addEventListener('click',function(){
            adminCallIncoming.style.display='none';
            vc.incomingOffer=null;
            clearInterval(vc.sigPoll);vc.sigPoll=null;
            if(activeSessionId){
                fetch('admin_ajax/call_reject.php',{method:'POST',headers:{'Content-Type':'application/json'},
                    body:JSON.stringify({session_id:activeSessionId})}).catch(function(){});
            }
        });
    }

    /* Mute toggle */
    if(adminCallMuteBtn){
        adminCallMuteBtn.addEventListener('click',function(){
            if(!vc.stream)return;
            var tracks=vc.stream.getAudioTracks();
            if(!tracks.length)return;
            var enabled=tracks[0].enabled;
            tracks.forEach(function(t){t.enabled=!enabled;});
            adminCallMuteBtn.classList.toggle('muted');
            adminCallMuteBtn.innerHTML=adminCallMuteBtn.classList.contains('muted')?'&#x1F507;':'&#x1F399;';
        });
    }

    /* End call */
    if(adminCallEndBtn){
        adminCallEndBtn.addEventListener('click',function(){vcHangup(true);});
    }

    /* Signal polling */
    function vcStartSigPoll(){
        clearInterval(vc.sigPoll);
        vc.sigPoll=setInterval(vcPollSignals,1500);
    }
    function vcPollSignals(){
        if(!activeSessionId)return;
        fetch('admin_ajax/call_poll.php?session_id='+activeSessionId)
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
            var si=allSessions[activeSessionId];
            if(adminCallIncoming){
                var sub=document.getElementById('adminCallIncSub');
                if(sub&&si)sub.textContent=(si.user_name||'Benutzer')+' m\u00f6chte einen Anruf starten';
                adminCallIncoming.style.display='flex';
            }
            vcStartSigPoll();
        } else if(type==='answer'){
            if(vc.pc&&!vc.pc.remoteDescription){
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
        } else if(type==='end'){
            vcHangup(false);
        }
    }

    /* Hook into openSession to start/stop call polling per session */
    var _origOpenSession=openSession;
    openSession=function(id,meta){
        vcHangup(false); /* clean up any previous call */
        _origOpenSession(id,meta);
        if(meta.status==='active'){
            if(btnCallUser)btnCallUser.style.display='';
            vcStartSigPoll();
        } else {
            if(btnCallUser)btnCallUser.style.display='none';
        }
    };
})();
</script>

<?php require_once 'admin_footer.php'; ?>
