<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2>User Activity Logs</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">User Activity</span>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="avatar avatar-icon avatar-lg avatar-blue">
                            <i class="anticon anticon-eye"></i>
                        </div>
                    </div>
                    <h2 class="m-t-10" id="totalPageViews">0</h2>
                    <p class="m-b-0 text-muted">Page Views</p>
                    <small class="text-info">Today</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="avatar avatar-icon avatar-lg avatar-green">
                            <i class="anticon anticon-user"></i>
                        </div>
                    </div>
                    <h2 class="m-t-10" id="activeUsersToday">0</h2>
                    <p class="m-b-0 text-muted">Active Users</p>
                    <small class="text-success">Today</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="avatar avatar-icon avatar-lg avatar-orange">
                            <i class="anticon anticon-file"></i>
                        </div>
                    </div>
                    <h2 class="m-t-10" id="topPage">-</h2>
                    <p class="m-b-0 text-muted">Most Visited</p>
                    <small class="text-warning">Today</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="avatar avatar-icon avatar-lg avatar-purple">
                            <i class="anticon anticon-clock-circle"></i>
                        </div>
                    </div>
                    <h2 class="m-t-10" id="avgSessionTime">0</h2>
                    <p class="m-b-0 text-muted">Avg Session</p>
                    <small class="text-info">Minutes</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Inactive Users Section -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">🤖 AI-Powered Inactive User Management</h5>
                    <p class="text-muted mb-0">Automatically notify users who haven't logged in for a specified period</p>
                </div>
                <button class="btn btn-primary" data-toggle="modal" data-target="#notifyInactiveUsersModal">
                    <i class="anticon anticon-mail"></i> Notify Inactive Users
                </button>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3 id="inactiveUsers30Days" class="text-warning">...</h3>
                            <p class="mb-0">Inactive 30+ Days</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3 id="inactiveUsers60Days" class="text-danger">...</h3>
                            <p class="mb-0">Inactive 60+ Days</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3 id="emailsSentToday" class="text-success">...</h3>
                            <p class="mb-0">Emails Sent Today</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>User Activity Logs</h5>
                <div class="d-flex">
                    <button class="btn btn-info mr-2" id="refreshActivityLogs">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="anticon anticon-filter"></i> Filters
                        </button>
                        <div class="dropdown-menu dropdown-menu-right p-3" style="min-width: 320px;">
                            <div class="form-group">
                                <label>User Email</label>
                                <input type="text" class="form-control" id="userEmailFilter" placeholder="Enter user email">
                            </div>
                            <div class="form-group">
                                <label>Page URL</label>
                                <input type="text" class="form-control" id="pageUrlFilter" placeholder="Enter page URL">
                            </div>
                            <div class="form-group">
                                <label>HTTP Method</label>
                                <select class="form-control" id="httpMethodFilter">
                                    <option value="">All Methods</option>
                                    <option value="GET">GET</option>
                                    <option value="POST">POST</option>
                                    <option value="PUT">PUT</option>
                                    <option value="DELETE">DELETE</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>IP Address</label>
                                <input type="text" class="form-control" id="ipAddressFilter" placeholder="Enter IP address">
                            </div>
                            <div class="form-group">
                                <label>Date Range</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="date" class="form-control" id="activityStartDate">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" class="form-control" id="activityEndDate">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-block" id="applyActivityFilters">Apply Filters</button>
                            <button type="button" class="btn btn-secondary btn-block" id="clearActivityFilters">Clear Filters</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="userActivityTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>User</th>
                            <th>Page</th>
                            <th>Method</th>
                            <th>IP Address</th>
                            <th>Browser</th>
                            <th>Referrer</th>
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
$(document).ready(function() {
    // Initialize DataTable
    const userActivityTable = $('#userActivityTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_user_activity.php',
            type: 'POST',
            data: function(d) {
                d.user_email = $('#userEmailFilter').val();
                d.page_url = $('#pageUrlFilter').val();
                d.http_method = $('#httpMethodFilter').val();
                d.ip_address = $('#ipAddressFilter').val();
                d.start_date = $('#activityStartDate').val();
                d.end_date = $('#activityEndDate').val();
            }
        },
        order: [[0, 'desc']],
        columns: [
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            },
            { 
                data: null,
                render: function(data) {
                    if (!data.user_first_name) return 'Anonymous';
                    
                    const avatar = data.user_email ? data.user_email.charAt(0).toUpperCase() : 'A';
                    return `
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm bg-info text-white mr-2">
                                ${avatar}
                            </div>
                            <div>
                                <strong>${data.user_first_name} ${data.user_last_name}</strong><br>
                                <small class="text-muted">${data.user_email}</small>
                            </div>
                        </div>
                    `;
                }
            },
            { 
                data: 'page_url',
                render: function(data) {
                    const shortUrl = data.length > 40 ? data.substring(0, 40) + '...' : data;
                    return `<code title="${data}">${shortUrl}</code>`;
                }
            },
            { 
                data: 'http_method',
                render: function(data) {
                    const methodClass = {
                        GET: 'success',
                        POST: 'primary',
                        PUT: 'warning',
                        DELETE: 'danger'
                    }[data] || 'secondary';
                    return `<span class="badge badge-${methodClass}">${data}</span>`;
                }
            },
            { 
                data: 'ip_address',
                render: function(data) {
                    return `<code>${data}</code>`;
                }
            },
            { 
                data: 'user_agent',
                render: function(data) {
                    if (!data) return 'Unknown';
                    
                    const browser = getBrowserInfo(data);
                    const isMobile = /Mobile|Android|iPhone|iPad/.test(data);
                    
                    return `
                        <div>
                            <i class="anticon anticon-${isMobile ? 'mobile' : 'desktop'}"></i>
                            ${browser}
                        </div>
                    `;
                }
            },
            { 
                data: 'referrer',
                render: function(data) {
                    if (!data) return '<span class="text-muted">Direct</span>';
                    
                    const shortReferrer = data.length > 30 ? data.substring(0, 30) + '...' : data;
                    return `<small title="${data}">${shortReferrer}</small>`;
                }
            }
        ]
    });

    // Load statistics
    function loadActivityStats() {
        $.get('admin_ajax/get_activity_stats.php', function(response) {
            if (response.success) {
                $('#totalPageViews').text(response.stats.total_page_views);
                $('#activeUsersToday').text(response.stats.active_users_today);
                $('#topPage').text(response.stats.top_page || '-');
                $('#avgSessionTime').text(response.stats.avg_session_time || '0');
            }
        });
    }

    // Apply filters
    $('#applyActivityFilters').click(function() {
        userActivityTable.ajax.reload();
    });

    // Clear filters
    $('#clearActivityFilters').click(function() {
        $('#userEmailFilter, #pageUrlFilter, #httpMethodFilter, #ipAddressFilter, #activityStartDate, #activityEndDate').val('');
        userActivityTable.ajax.reload();
    });

    // Refresh logs
    $('#refreshActivityLogs').click(function() {
        userActivityTable.ajax.reload();
        loadActivityStats();
        loadInactiveUsersStats();
    });
    
    // Load inactive users statistics
    function loadInactiveUsersStats() {
        $.get('admin_ajax/get_activity_stats.php?type=inactive', function(data) {
            if (data.success) {
                $('#inactiveUsers30Days').text(data.inactive_30_days || 0);
                $('#inactiveUsers60Days').text(data.inactive_60_days || 0);
                $('#emailsSentToday').text(data.emails_sent_today || 0);
            }
        });
    }
    
    // Handle notify inactive users form submission
    $('#notifyInactiveUsersForm').submit(function(e) {
        e.preventDefault();
        
        const inactiveDays = $('#inactiveDays').val();
        const emailTemplate = $('#emailTemplate').val();
        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.html();
        
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Sending...');
        
        $.post('admin_ajax/notify_inactive_users.php', {
            inactive_days: inactiveDays,
            email_template: emailTemplate
        })
        .done(function(response) {
            if (response.success) {
                toastr.success(`${response.sent} emails sent successfully!`, 'Success');
                if (response.failed > 0) {
                    toastr.warning(`${response.failed} emails failed to send`, 'Warning');
                }
                $('#notifyInactiveUsersModal').modal('hide');
                loadInactiveUsersStats();
            } else {
                toastr.error(response.message, 'Error');
            }
        })
        .fail(function() {
            toastr.error('Failed to send notifications', 'Error');
        })
        .always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });

    // Helper function to get browser info
    function getBrowserInfo(userAgent) {
        if (userAgent.includes('Chrome')) return 'Chrome';
        if (userAgent.includes('Firefox')) return 'Firefox';
        if (userAgent.includes('Safari')) return 'Safari';
        if (userAgent.includes('Edge')) return 'Edge';
        if (userAgent.includes('Opera')) return 'Opera';
        return 'Unknown';
    }

    // Initial load
    loadActivityStats();
    loadInactiveUsersStats();
});
</script>

<!-- Notify Inactive Users Modal -->
<div class="modal fade" id="notifyInactiveUsersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="anticon anticon-mail"></i> Notify Inactive Users
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="notifyInactiveUsersForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="anticon anticon-info-circle"></i>
                        <strong>AI-Powered Engagement</strong><br>
                        This feature uses AI to identify inactive users and send personalized reminder emails to bring them back to their fund recovery cases.
                    </div>
                    
                    <div class="form-group">
                        <label>Inactive Period (Days)</label>
                        <select class="form-control" id="inactiveDays" required>
                            <option value="7">7 days</option>
                            <option value="14">14 days</option>
                            <option value="30" selected>30 days</option>
                            <option value="60">60 days</option>
                            <option value="90">90 days</option>
                        </select>
                        <small class="text-muted">Users who haven't logged in for this period will receive notifications</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Template</label>
                        <select class="form-control" id="emailTemplate" required>
                            <option value="inactive_user_reminder" selected>Inactive User Reminder</option>
                            <option value="case_update_notification">Case Update Notification</option>
                            <option value="ai_recovery_update">AI Recovery Progress Update</option>
                        </select>
                        <small class="text-muted">Choose the email template to use for notifications</small>
                    </div>
                    
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>Preview:</h6>
                            <p class="mb-0 small">Inactive users will receive an AI-personalized email encouraging them to return, highlighting:</p>
                            <ul class="small mb-0">
                                <li>Their current case status</li>
                                <li>AI analysis updates</li>
                                <li>New recovery strategies</li>
                                <li>Direct login link</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="anticon anticon-send"></i> Send Notifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>