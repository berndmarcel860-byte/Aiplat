<?php
// admin_view_users.php — Full-page user profile / detail view
require_once 'admin_header.php';

$userId = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$userId) {
    header('Location: admin_users.php');
    exit;
}

// Fetch the user's email for passing to send_bulk_notifications.php
$userEmail = '';
try {
    $stmtUser = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $userEmail = $userRow['email'] ?? '';
} catch (Exception $e) {
    // ignore — email will be empty, used only for error reporting
}

// Build notification dropdown options from email_notifications table (notif: prefix)
$notifModalTemplateOptions = "<option value='' disabled selected>— Select notification template —</option>";
try {
    $stmtTplModal = $pdo->prepare("
        SELECT notification_key, name, subject
        FROM email_notifications
        WHERE is_active = 1
        ORDER BY name ASC
    ");
    $stmtTplModal->execute();
    foreach ($stmtTplModal->fetchAll(PDO::FETCH_ASSOC) as $tpl) {
        $k = htmlspecialchars('notif:' . $tpl['notification_key'], ENT_QUOTES);
        $n = htmlspecialchars($tpl['name'], ENT_QUOTES);
        $s = htmlspecialchars($tpl['subject'] ?? $tpl['notification_key'], ENT_QUOTES);
        $notifModalTemplateOptions .= "<option value='{$k}'>{$n} — {$s}</option>";
    }
} catch (Exception $e) {
    // email_notifications unavailable — dropdown will be empty
}
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">
            <i class="anticon anticon-user mr-2"></i>
            User Profile
            <span id="pageUserName" class="text-muted font-weight-normal ml-2" style="font-size:16px;"></span>
        </h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <a href="admin_users.php" class="breadcrumb-item">Users</a>
                <span class="breadcrumb-item active">User Profile</span>
            </nav>
        </div>
    </div>

    <!-- Top action bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="admin_users.php" class="btn btn-default">
            <i class="anticon anticon-arrow-left mr-1"></i> Back to Users
        </a>
        <div id="profileActionBar" style="display:none;">
            <button class="btn btn-primary mr-1" id="btnEditUser">
                <i class="anticon anticon-edit mr-1"></i> Edit User
            </button>
            <button class="btn btn-success mr-1" id="btnSendEmail">
                <i class="anticon anticon-mail mr-1"></i> Send Email
            </button>
            <button class="btn btn-info mr-1" id="btnSendNotif">
                <i class="anticon anticon-notification mr-1"></i> Send Notification
            </button>
            <button class="btn btn-danger" id="btnSuspend">
                <i class="anticon anticon-stop mr-1"></i> Suspend
            </button>
        </div>
    </div>

    <!-- Loading placeholder (replaced when AJAX completes) -->
    <div id="profileLoading" class="text-center py-5">
        <i class="anticon anticon-loading anticon-spin" style="font-size:32px;color:#2950a8;"></i>
        <p class="mt-3 text-muted">Loading user profile...</p>
    </div>

    <!-- Error placeholder (shown on failure) -->
    <div id="profileError" class="alert alert-danger" style="display:none;"></div>

    <!-- Profile content card (hidden until data loads) -->
    <div id="profileCard" class="card" style="display:none;">
        <div class="card-body p-0">
            <ul class="nav nav-tabs nav-tabs-line px-3 pt-2" id="userDetailsTabs" role="tablist" style="flex-wrap:nowrap;overflow-x:auto;">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#basicInfo" role="tab"><i class="anticon anticon-idcard mr-1"></i>Overview</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#onboarding" role="tab"><i class="anticon anticon-solution mr-1"></i>Onboarding</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#kyc" role="tab"><i class="anticon anticon-safety mr-1"></i>KYC</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#payments" role="tab"><i class="anticon anticon-credit-card mr-1"></i>Payments</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#transactions" role="tab"><i class="anticon anticon-swap mr-1"></i>Transactions</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#cases" role="tab"><i class="anticon anticon-folder mr-1"></i>Cases</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tickets" role="tab"><i class="anticon anticon-customer-service mr-1"></i>Tickets</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#emailLogs" role="tab"><i class="anticon anticon-mail mr-1"></i>Email Logs</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#sendEmailTab" role="tab"><i class="anticon anticon-send mr-1"></i>Send Email</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#sendNotifTab" role="tab"><i class="anticon anticon-notification mr-1"></i>Send Notification</a></li>
            </ul>
            <div class="tab-content p-3" id="userDetailsContent">
                <div class="tab-pane fade show active" id="basicInfo" role="tabpanel"></div>
                <div class="tab-pane fade" id="onboarding" role="tabpanel"></div>
                <div class="tab-pane fade" id="kyc" role="tabpanel"></div>
                <div class="tab-pane fade" id="payments" role="tabpanel"></div>
                <div class="tab-pane fade" id="transactions" role="tabpanel"></div>
                <div class="tab-pane fade" id="cases" role="tabpanel"></div>
                <div class="tab-pane fade" id="tickets" role="tabpanel"></div>
                <div class="tab-pane fade" id="emailLogs" role="tabpanel"></div>
                <div class="tab-pane fade" id="sendEmailTab" role="tabpanel"></div>
                <div class="tab-pane fade" id="sendNotifTab" role="tabpanel"></div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODALS
═══════════════════════════════════════════════════════════ -->

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(90deg,#6f42c1,#e83e8c);color:#fff;">
        <h5 class="modal-title">
          <span style="background:rgba(255,255,255,0.2);border-radius:50%;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;">
            <i class="anticon anticon-edit"></i>
          </span>
          Edit User
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
      </div>
      <form id="editUserForm">
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_user_id">
          <p class="text-muted small mb-3"><i class="anticon anticon-info-circle mr-1"></i> Fields marked <span class="text-danger">*</span> are required.</p>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label><i class="anticon anticon-user text-muted mr-1"></i> First Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
            </div>
            <div class="form-group col-md-6">
              <label><i class="anticon anticon-user text-muted mr-1"></i> Last Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
            </div>
          </div>
          <div class="form-group">
            <label><i class="anticon anticon-mail text-muted mr-1"></i> Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" id="edit_email" required>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label><i class="anticon anticon-phone text-muted mr-1"></i> Phone</label>
              <input type="text" class="form-control" name="phone" id="edit_phone">
            </div>
            <div class="form-group col-md-6">
              <label><i class="anticon anticon-global text-muted mr-1"></i> Country</label>
              <input type="text" class="form-control" name="country" id="edit_country">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label><i class="anticon anticon-dollar text-muted mr-1"></i> Balance</label>
              <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text">€</span></div>
                <input type="number" class="form-control" name="balance" id="edit_balance" step="0.01" min="0">
              </div>
            </div>
            <div class="form-group col-md-6">
              <label><i class="anticon anticon-check-circle text-muted mr-1"></i> Status</label>
              <select class="form-control" name="status" id="edit_status">
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="banned">Banned</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><i class="anticon anticon-close mr-1"></i>Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="anticon anticon-save mr-1"></i>Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Send Email Modal -->
<div class="modal fade" id="sendMailModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(90deg,#28a745,#20c997);color:#fff;">
        <h5 class="modal-title">
          <span style="background:rgba(255,255,255,0.2);border-radius:50%;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;">
            <i class="anticon anticon-mail"></i>
          </span>
          Send Email to User
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
      </div>
      <form id="sendMailForm">
        <div class="modal-body">
          <input type="hidden" name="user_id" id="send_mail_user_id">
          <div class="form-group">
            <label><i class="anticon anticon-user text-muted mr-1"></i> Recipient</label>
            <input type="text" class="form-control bg-light" id="send_mail_recipient" readonly>
          </div>
          <div class="form-group">
            <label><i class="anticon anticon-tag text-muted mr-1"></i> Subject <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="subject" placeholder="Enter email subject" required>
          </div>
          <div class="form-group">
            <label><i class="anticon anticon-align-left text-muted mr-1"></i> Message <span class="text-danger">*</span></label>
            <textarea class="form-control" name="message" rows="8" placeholder="Enter your message here. HTML is supported." required></textarea>
            <small class="form-text text-muted">
              <strong>Variables:</strong> {first_name}, {last_name}, {email}, {user_id}, {balance}, {status}, {site_url}, {site_name}, {contact_email}
            </small>
          </div>
          <div class="alert alert-info mb-0">
            <i class="anticon anticon-info-circle"></i> Your message will be automatically wrapped in the professional HTML email template.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><i class="anticon anticon-close mr-1"></i>Cancel</button>
          <button type="submit" class="btn btn-success"><i class="anticon anticon-send mr-1"></i> Send Email</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Send Notification Modal -->
<div class="modal fade" id="sendNotifModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(90deg,#007bff,#6f42c1);color:#fff;">
        <h5 class="modal-title">
          <span style="background:rgba(255,255,255,0.2);border-radius:50%;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;">
            <i class="anticon anticon-notification"></i>
          </span>
          Send Notification
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
      </div>
      <form id="sendNotifForm">
        <div class="modal-body">
          <input type="hidden" name="type" value="notification">
          <div class="form-group">
            <label><i class="anticon anticon-mail text-muted mr-1"></i> Notification Template <span class="text-danger">*</span></label>
            <select class="form-control" name="template_key" required>
              <?= $notifModalTemplateOptions ?>
            </select>
            <small class="text-muted">Subject and content are automatically taken from the selected notification template.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><i class="anticon anticon-close mr-1"></i>Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="anticon anticon-notification mr-1"></i> Send Notification</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Suspend User Confirmation Modal -->
<div class="modal fade" id="suspendUserModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
    <div class="modal-content">
      <div class="modal-header" style="background:#dc3545;color:#fff;border-bottom:none;">
        <h5 class="modal-title">
          <span style="background:rgba(255,255,255,0.2);border-radius:50%;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;">
            <i class="anticon anticon-warning"></i>
          </span>
          Confirm Suspension
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body text-center py-4">
        <div style="font-size:48px;color:#dc3545;margin-bottom:12px;"><i class="anticon anticon-exclamation-circle"></i></div>
        <p class="mb-1">You are about to suspend this user.</p>
        <p class="text-muted small mb-0">The user will be hidden from the active list but <strong>not deleted</strong>. This can be reversed by editing the user's status.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="anticon anticon-close mr-1"></i>Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmSuspendBtn"><i class="anticon anticon-stop mr-1"></i>Suspend User</button>
      </div>
    </div>
  </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
(function () {
    'use strict';

    var userId    = <?= $userId ?>;
    var userEmail = <?= json_encode($userEmail) ?>;

    var escapeHtml = function(str) {
        return String(str).replace(/[&<>"']/g, function(c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    };

    // -------------------------------------------------------
    // Load all user data via the existing get_user.php endpoint
    // -------------------------------------------------------
    function loadProfile(targetTab) {
        $('#profileLoading').show();
        $('#profileError').hide();
        $('#profileCard').hide();
        $('#profileActionBar').hide();

        $.ajax({
            url: 'admin_ajax/get_user.php',
            method: 'GET',
            data: { id: userId },
            dataType: 'json',
            success: function(res) {
                $('#profileLoading').hide();

                if (!res || !res.success) {
                    $('#profileError').text((res && res.message) ? res.message : 'Failed to load user data.').show();
                    return;
                }

                // Populate all tab panes
                $('#basicInfo').html(res.html.basic);
                $('#onboarding').html(res.html.onboarding);
                $('#kyc').html(res.html.kyc);
                $('#payments').html(res.html.payments);
                $('#transactions').html(res.html.transactions);
                $('#cases').html(res.html.cases);
                $('#tickets').html(res.html.tickets);
                $('#emailLogs').html(res.html.email_logs);
                $('#sendEmailTab').html(res.html.send_email);
                $('#sendNotifTab').html(res.html.send_notification);

                // Show card and action bar
                $('#profileCard').show();
                $('#profileActionBar').show();

                // Update page title with user name
                if (res.user) {
                    var name = escapeHtml(res.user.first_name + ' ' + res.user.last_name);
                    $('#pageUserName').html('&mdash; ' + name);
                    document.title = 'User: ' + res.user.first_name + ' ' + res.user.last_name + ' | Admin';
                }

                // Switch to requested tab if specified
                if (targetTab) {
                    $('#userDetailsTabs a[href="#' + targetTab + '"]').tab('show');
                }

                // Wire up inline Send Email form (rendered inside the tab by get_user.php)
                $('#modalSendMailForm').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    var $btn = $('#modalSendMailBtn');
                    $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Sending...');
                    $.ajax({
                        url: 'admin_ajax/send_universal_email.php',
                        type: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json',
                        success: function(r) {
                            if (r.success) {
                                toastr.success(r.message || 'Email sent!');
                                $('#modalSendMailForm')[0].reset();
                                refreshEmailLogs();
                            } else {
                                toastr.error(r.message || 'Failed to send email');
                            }
                        },
                        error: function() { toastr.error('Error sending email'); },
                        complete: function() {
                            $btn.prop('disabled', false).html('<i class="anticon anticon-send mr-1"></i> Send Email');
                        }
                    });
                });

                // Wire up inline Send Notification form
                $('#modalSendNotifForm').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    var $btn = $('#modalSendNotifBtn');
                    $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Sending...');
                    $.ajax({
                        url: 'admin_ajax/send_bulk_notifications.php',
                        type: 'POST',
                        data: {
                            template_key: $(this).find('[name=template_key]').val(),
                            users: JSON.stringify([{
                                id: $(this).find('[name=user_id]').val(),
                                email: $(this).find('[name=user_email]').val() || ''
                            }])
                        },
                        dataType: 'json',
                        success: function(r) {
                            if (r.success) {
                                toastr.success(r.message || 'Notification sent!');
                                $('#modalSendNotifForm')[0].reset();
                            } else {
                                toastr.error(r.message || 'Failed to send notification');
                            }
                        },
                        error: function() { toastr.error('Error sending notification'); },
                        complete: function() {
                            $btn.prop('disabled', false).html('<i class="anticon anticon-notification mr-1"></i> Send Notification');
                        }
                    });
                });
            },
            error: function(xhr) {
                $('#profileLoading').hide();
                $('#profileError').text('Error loading user profile (HTTP ' + xhr.status + ').').show();
            }
        });
    }

    function refreshEmailLogs() {
        $('#emailLogs').html('<div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Refreshing...</div>');
        $.get('admin_ajax/get_user.php', { id: userId }, function(r2) {
            if (r2.success) $('#emailLogs').html(r2.html.email_logs);
        }, 'json');
    }

    // -------------------------------------------------------
    // Action bar — Edit User button
    // -------------------------------------------------------
    $('#btnEditUser').on('click', function() {
        $.ajax({
            url: 'admin_ajax/get_user.php',
            method: 'GET',
            data: { id: userId },
            dataType: 'json',
            success: function(res) {
                if (res.success && res.user) {
                    var u = res.user;
                    $('#edit_user_id').val(u.id);
                    $('#edit_first_name').val(u.first_name);
                    $('#edit_last_name').val(u.last_name);
                    $('#edit_email').val(u.email);
                    $('#edit_phone').val(u.phone || '');
                    $('#edit_country').val(u.country || '');
                    $('#edit_balance').val(u.balance || '0');
                    $('#edit_status').val(u.status);
                    $('#editUserModal').modal('show');
                } else {
                    toastr.error('Failed to load user data');
                }
            },
            error: function() { toastr.error('Failed to load user data'); }
        });
    });

    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Saving...');
        $.ajax({
            url: 'admin_ajax/update_user.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    toastr.success(r.message || 'User updated');
                    $('#editUserModal').modal('hide');
                    loadProfile(); // Reload all tabs to reflect changes
                } else {
                    toastr.error(r.message || 'Failed to update user');
                }
            },
            error: function() { toastr.error('Failed to update user'); },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="anticon anticon-save mr-1"></i>Save Changes');
            }
        });
    });

    // -------------------------------------------------------
    // Action bar — Send Email button (standalone modal)
    // -------------------------------------------------------
    $('#btnSendEmail').on('click', function() {
        $.ajax({
            url: 'admin_ajax/get_user.php',
            method: 'GET',
            data: { id: userId },
            dataType: 'json',
            success: function(res) {
                if (res.success && res.user) {
                    var u = res.user;
                    $('#send_mail_user_id').val(u.id);
                    $('#send_mail_recipient').val(u.first_name + ' ' + u.last_name + ' <' + u.email + '>');
                    $('#sendMailModal').modal('show');
                } else {
                    toastr.error('Failed to load user data');
                }
            },
            error: function() { toastr.error('Failed to load user data'); }
        });
    });

    $('#sendMailForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Sending...');
        $.ajax({
            url: 'admin_ajax/send_universal_email.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    toastr.success(r.message || 'Email sent!');
                    $('#sendMailModal').modal('hide');
                    $('#sendMailForm')[0].reset();
                    refreshEmailLogs();
                } else {
                    toastr.error(r.message || 'Failed to send email');
                }
            },
            error: function() { toastr.error('Error sending email'); },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="anticon anticon-send mr-1"></i> Send Email');
            }
        });
    });

    // -------------------------------------------------------
    // Action bar — Send Notification button (standalone modal)
    // -------------------------------------------------------
    $('#btnSendNotif').on('click', function() {
        $('#sendNotifModal').modal('show');
    });

    $('#sendNotifForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Sending...');
        $.ajax({
            url: 'admin_ajax/send_bulk_notifications.php',
            type: 'POST',
            data: {
                template_key: $(this).find('[name=template_key]').val(),
                users: JSON.stringify([{id: userId, email: userEmail}])
            },
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    toastr.success(r.message || 'Notification sent!');
                    $('#sendNotifModal').modal('hide');
                    $('#sendNotifForm')[0].reset();
                } else {
                    toastr.error(r.message || 'Failed to send notification');
                }
            },
            error: function() { toastr.error('Error sending notification'); },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="anticon anticon-notification mr-1"></i> Send Notification');
            }
        });
    });

    // -------------------------------------------------------
    // Action bar — Suspend button
    // -------------------------------------------------------
    $('#btnSuspend').on('click', function() {
        $('#suspendUserModal').modal('show');
    });

    $('#confirmSuspendBtn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Suspending...');
        $.ajax({
            url: 'admin_ajax/delete_user.php',
            type: 'POST',
            data: { id: userId },
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    toastr.success(r.message || 'User suspended');
                    $('#suspendUserModal').modal('hide');
                    loadProfile(); // Reload to show updated status
                } else {
                    toastr.error(r.message || 'Failed to suspend user');
                }
            },
            error: function() { toastr.error('Error suspending user'); },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="anticon anticon-stop mr-1"></i>Suspend User');
            }
        });
    });

    // -------------------------------------------------------
    // Read optional ?tab= param from URL to open a specific tab
    // -------------------------------------------------------
    var urlParams = new URLSearchParams(window.location.search);
    var initialTab = urlParams.get('tab') || null;

    // Kick off initial load
    loadProfile(initialTab);

}());
</script>
