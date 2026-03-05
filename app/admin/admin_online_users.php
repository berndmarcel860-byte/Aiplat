<?php
require_once 'admin_header.php';
?>
<div class="main-content">
    <div class="page-header">
        <h2>Online Users</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Online Users</span>
            </nav>
        </div>
    </div>

    <!-- ===== Stats Cards ===== -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar avatar-icon avatar-lg avatar-green mb-2">
                        <i class="anticon anticon-user"></i>
                    </div>
                    <h2 id="totalOnlineUsers">0</h2>
                    <p class="m-b-0 text-muted">Total Online</p>
                    <small class="text-success">Last 5 minutes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar avatar-icon avatar-lg avatar-blue mb-2">
                        <i class="anticon anticon-clock-circle"></i>
                    </div>
                    <h2 id="activeUsers">0</h2>
                    <p class="m-b-0 text-muted">Active Now</p>
                    <small class="text-info">Last 1 minute</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar avatar-icon avatar-lg avatar-orange mb-2">
                        <i class="anticon anticon-mobile"></i>
                    </div>
                    <h2 id="mobileUsers">0</h2>
                    <p class="m-b-0 text-muted">Mobile Users</p>
                    <small class="text-warning">Currently online</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar avatar-icon avatar-lg avatar-red mb-2">
                        <i class="anticon anticon-desktop"></i>
                    </div>
                    <h2 id="desktopUsers">0</h2>
                    <p class="m-b-0 text-muted">Desktop Users</p>
                    <small class="text-danger">Currently online</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Online Users Table ===== -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Currently Online Users</h5>
                <div>
                    <button class="btn btn-info mr-2" id="refreshOnlineUsers">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                    <button class="btn btn-secondary" id="autoRefreshToggle">
                        <i class="anticon anticon-play-circle"></i> Auto Refresh
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="onlineUsersTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Device</th>
                            <th>IP Address</th>
                            <th>Last Activity</th>
                            <th>Status</th>
                            <th>Session Duration</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(function(){
    let autoRefreshInterval = null;
    let isAutoRefresh = false;

    // === DataTable ===
    const table = $('#onlineUsersTable').DataTable({
        ajax: { url: 'admin_ajax/get_online_users.php', dataSrc: 'data' },
        order: [[3,'desc']],
        columns: [
            {
                data: null,
                render: d => `
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm bg-primary text-white mr-2">
                            ${d.user_email ? d.user_email.charAt(0).toUpperCase() : 'U'}
                        </div>
                        <div>
                            <strong>${d.user_first_name} ${d.user_last_name}</strong><br>
                            <small class="text-muted">${d.user_email}</small>
                        </div>
                    </div>`
            },
            {
                data: 'user_agent',
                render: d => {
                    const mobile = /Mobile|Android|iPhone|iPad/.test(d||'');
                    const browser = getBrowser(d);
                    return `<i class="anticon anticon-${mobile?'mobile':'desktop'}"></i> ${browser}`;
                }
            },
            { data: 'ip_address', render: d=> `<code>${d}</code>` },
            { data: 'last_activity', render: d=> formatTimeAgo(d) },
            { data: 'status', render: d=> renderStatusBadge(d) },
            {
                data: 'last_activity',
                render: d=>{
                    const mins = Math.floor((Date.now()-new Date(d))/60000);
                    return mins>0?`${mins} min`:'Just now';
                }
            },
            {
                data: null,
                render: d=>`
                    <div class="btn-group">
                        <button class="btn btn-sm btn-info view-session" data-id="${d.user_id}" data-session-id="${d.session_id}">
                            <i class="anticon anticon-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning force-logout" data-id="${d.user_id}" data-session-id="${d.session_id}" title="Force Logout">
                            <i class="anticon anticon-logout"></i>
                        </button>
                    </div>`
            }
        ]
    });

    // === Helper functions ===
    function formatTimeAgo(dt){
        const diff = Math.floor((Date.now()-new Date(dt))/1000);
        if(diff<60) return diff+" sec ago";
        if(diff<3600) return Math.floor(diff/60)+" min ago";
        if(diff<86400) return Math.floor(diff/3600)+" hr ago";
        return Math.floor(diff/86400)+" day(s) ago";
    }
    function renderStatusBadge(status){
        if(status==='active') return '<span class="badge bg-success">Active</span>';
        if(status==='idle')   return '<span class="badge bg-warning text-dark">Idle</span>';
        return '<span class="badge bg-secondary">Offline</span>';
    }
    function getBrowser(ua){
        if(!ua) return 'Unknown';
        if(ua.includes('Chrome')) return 'Chrome';
        if(ua.includes('Firefox')) return 'Firefox';
        if(ua.includes('Safari')) return 'Safari';
        if(ua.includes('Edge')) return 'Edge';
        if(ua.includes('Opera')) return 'Opera';
        return 'Unknown';
    }

    // === Load stats cards ===
    function loadStats(){
        $.get('admin_ajax/get_online_stats.php', r=>{
            if(r.success){
                $('#totalOnlineUsers').text(r.stats.total_online);
                $('#activeUsers').text(r.stats.active_now);
                $('#mobileUsers').text(r.stats.mobile_users);
                $('#desktopUsers').text(r.stats.desktop_users);
            }
        });
    }

    function refresh(){
        table.ajax.reload(null,false);
        loadStats();
    }

    $('#refreshOnlineUsers').click(refresh);

    $('#autoRefreshToggle').click(function(){
        if(isAutoRefresh){
            clearInterval(autoRefreshInterval);
            $(this).html('<i class="anticon anticon-play-circle"></i> Auto Refresh');
            isAutoRefresh=false;
        }else{
            autoRefreshInterval=setInterval(refresh,10000);
            $(this).html('<i class="anticon anticon-pause-circle"></i> Stop Auto');
            isAutoRefresh=true;
        }
    });

    // === Force logout ===
    $('#onlineUsersTable').on('click','.force-logout',function(){
        const uid=$(this).data('id'), sid=$(this).data('session-id');
        if(confirm('Force logout this user?')){
            $.post('admin_ajax/force_logout_user.php',{user_id:uid,session_id:sid},r=>{
                r.success?toastr.success(r.message):toastr.error(r.message);
                refresh();
            });
        }
    });

    loadStats();
});
</script>

