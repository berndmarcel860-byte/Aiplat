<?php
// admin_users.php
// === ENABLE PHP ERRORS (TEMPORARILY) ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">User Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Users</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>User List</h5>
                <div class="d-flex">
                    <button class="btn btn-warning mr-2" id="sendKycRemindersBtn">
                        <i class="anticon anticon-mail"></i> Send KYC Reminders
                    </button>
                    <button class="btn btn-info mr-2" data-toggle="modal" data-target="#sendMailAllModal">
                        <i class="anticon anticon-mail"></i> Send Mail to All
                    </button>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
                        <i class="anticon anticon-plus"></i> Add User
                    </button>
                </div>
            </div>
            
            <!-- Login Activity Filters -->
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h6 class="mb-3"><i class="anticon anticon-filter"></i> Filter by Last Login Activity</h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary filter-login" data-days="all">All Users</button>
                        <button type="button" class="btn btn-outline-danger filter-login" data-days="never">Never Logged In</button>
                        <button type="button" class="btn btn-outline-warning filter-login" data-days="3">3+ Days</button>
                        <button type="button" class="btn btn-outline-warning filter-login" data-days="5">5+ Days</button>
                        <button type="button" class="btn btn-outline-warning filter-login" data-days="7">7+ Days</button>
                        <button type="button" class="btn btn-outline-warning filter-login" data-days="10">10+ Days</button>
                        <button type="button" class="btn btn-outline-warning filter-login" data-days="15">15+ Days</button>
                        <button type="button" class="btn btn-outline-danger filter-login" data-days="21">21+ Days</button>
                        <button type="button" class="btn btn-outline-danger filter-login" data-days="30">1 Month+</button>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Filter users based on their last login activity. Click a button to filter.</small>
                    </div>
                </div>
            </div>
            
            <div class="m-t-25" style="overflow-x:auto;">
                <table id="usersTable" class="table table-hover nowrap" style="width:100%;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Country</th>
                            <th>Status</th>
                            <th>KYC</th>
                            <th>Wallet</th>
                            <th>Onboarding</th>
                            <th>Cases</th>
                            <th>Tickets</th>
                            <th>Last Login</th>
                            <th>Balance</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- User Details Modal (Tabs) -->
<div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(90deg,#2950a8,#2da9e3);color:#fff;">
        <h5 class="modal-title">
          <span style="background:rgba(255,255,255,0.2);border-radius:50%;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;">
            <i class="anticon anticon-user"></i>
          </span>
          User Details
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-0">
        <ul class="nav nav-tabs nav-tabs-line px-3 pt-2" id="userDetailsTabs" role="tablist" style="flex-wrap:nowrap;overflow-x:auto;">
          <li class="nav-item"><a class="nav-link active" id="tab-basic" data-toggle="tab" href="#basicInfo" role="tab"><i class="anticon anticon-idcard mr-1"></i>Overview</a></li>
          <li class="nav-item"><a class="nav-link" id="tab-onboarding" data-toggle="tab" href="#onboarding" role="tab"><i class="anticon anticon-solution mr-1"></i>Onboarding</a></li>
          <li class="nav-item"><a class="nav-link" id="tab-kyc" data-toggle="tab" href="#kyc" role="tab"><i class="anticon anticon-safety mr-1"></i>KYC</a></li>
          <li class="nav-item"><a class="nav-link" id="tab-payments" data-toggle="tab" href="#payments" role="tab"><i class="anticon anticon-credit-card mr-1"></i>Payments</a></li>
          <li class="nav-item"><a class="nav-link" id="tab-transactions" data-toggle="tab" href="#transactions" role="tab"><i class="anticon anticon-swap mr-1"></i>Transactions</a></li>
          <li class="nav-item"><a class="nav-link" id="tab-cases" data-toggle="tab" href="#cases" role="tab"><i class="anticon anticon-folder mr-1"></i>Cases</a></li>
          <li class="nav-item"><a class="nav-link" id="tab-tickets" data-toggle="tab" href="#tickets" role="tab"><i class="anticon anticon-customer-service mr-1"></i>Tickets</a></li>
          <li class="nav-item"><a class="nav-link" id="tab-emaillogs" data-toggle="tab" href="#emailLogs" role="tab"><i class="anticon anticon-mail mr-1"></i>Email Logs</a></li>
          <li class="nav-item"><a class="nav-link" id="tab-sendemail" data-toggle="tab" href="#sendEmailTab" role="tab"><i class="anticon anticon-send mr-1"></i>Send Email</a></li>
          <li class="nav-item"><a class="nav-link" id="tab-sendnotif" data-toggle="tab" href="#sendNotifTab" role="tab"><i class="anticon anticon-notification mr-1"></i>Send Notification</a></li>
        </ul>
        <div class="tab-content p-3" id="userDetailsContent">
          <div class="tab-pane fade show active" id="basicInfo" role="tabpanel"><div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div></div>
          <div class="tab-pane fade" id="onboarding" role="tabpanel"><div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div></div>
          <div class="tab-pane fade" id="kyc" role="tabpanel"><div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div></div>
          <div class="tab-pane fade" id="payments" role="tabpanel"><div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div></div>
          <div class="tab-pane fade" id="transactions" role="tabpanel"><div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div></div>
          <div class="tab-pane fade" id="cases" role="tabpanel"><div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div></div>
          <div class="tab-pane fade" id="tickets" role="tabpanel"><div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div></div>
          <div class="tab-pane fade" id="emailLogs" role="tabpanel"><div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div></div>
          <div class="tab-pane fade" id="sendEmailTab" role="tabpanel"><div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div></div>
          <div class="tab-pane fade" id="sendNotifTab" role="tabpanel"><div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(90deg,#2950a8,#2da9e3);color:#fff;">
        <h5 class="modal-title">
          <span style="background:rgba(255,255,255,0.2);border-radius:50%;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;">
            <i class="anticon anticon-user-add"></i>
          </span>
          Add New User
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
      </div>
      <form id="addUserForm">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label><i class="anticon anticon-user text-muted mr-1"></i> First Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="first_name" placeholder="First name" required>
            </div>
            <div class="form-group col-md-6">
              <label><i class="anticon anticon-user text-muted mr-1"></i> Last Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="last_name" placeholder="Last name" required>
            </div>
          </div>
          <div class="form-group">
            <label><i class="anticon anticon-mail text-muted mr-1"></i> Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" placeholder="user@example.com" required>
          </div>
          <div class="form-group">
            <label><i class="anticon anticon-phone text-muted mr-1"></i> Phone Number</label>
            <input type="tel" class="form-control" name="phone" placeholder="+1234567890">
            <small class="form-text text-muted">Optional. International format preferred (e.g., +1234567890)</small>
          </div>
          <div class="form-group">
            <label><i class="anticon anticon-lock text-muted mr-1"></i> Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" class="form-control" name="password" id="add_password" value="ceM8fFXV" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary" id="toggleAddPwd" tabindex="-1" title="Show/Hide password">
                  <i class="anticon anticon-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label><i class="anticon anticon-check-circle text-muted mr-1"></i> Status</label>
            <select class="form-control" name="status">
              <option value="active">Active</option>
              <option value="suspended">Suspended</option>
              <option value="banned">Banned</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><i class="anticon anticon-close mr-1"></i>Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="anticon anticon-user-add mr-1"></i>Add User</button>
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
            <input type="text" class="form-control" name="subject" id="send_mail_subject" placeholder="Enter email subject" required>
          </div>
          <div class="form-group">
            <label><i class="anticon anticon-align-left text-muted mr-1"></i> Message <span class="text-danger">*</span></label>
            <textarea class="form-control" name="message" id="send_mail_content" rows="8" placeholder="Enter your message here. HTML is supported." required></textarea>
            <small class="form-text text-muted">
              <strong>Variables:</strong> {first_name}, {last_name}, {email}, {user_id}, {balance}, {status}, {site_url}, {site_name}, {contact_email}
            </small>
          </div>
          <div class="alert alert-info mb-0">
            <i class="anticon anticon-info-circle"></i> Your message will be automatically wrapped in the professional HTML email template with header, signature, and footer.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><i class="anticon anticon-close mr-1"></i>Cancel</button>
          <button type="submit" class="btn btn-success">
            <i class="anticon anticon-send mr-1"></i> Send Email
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Send Mail to All Users Modal -->
<div class="modal fade" id="sendMailAllModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(90deg,#17a2b8,#138496);color:#fff;">
        <h5 class="modal-title">
          <span style="background:rgba(255,255,255,0.2);border-radius:50%;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;">
            <i class="anticon anticon-mail"></i>
          </span>
          Send Email to All Active Users
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
      </div>
      <form id="sendMailAllForm">
        <div class="modal-body">
          <div class="alert alert-warning">
            <i class="anticon anticon-warning"></i> <strong>Warning:</strong> This will send an email to <strong>all active verified users</strong>. Please double-check your subject and message before sending.
          </div>
          <div class="form-group">
            <label><i class="anticon anticon-tag text-muted mr-1"></i> Subject <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="subject" id="send_mail_all_subject" placeholder="Enter email subject" required>
          </div>
          <div class="form-group">
            <label><i class="anticon anticon-align-left text-muted mr-1"></i> Message <span class="text-danger">*</span></label>
            <textarea class="form-control" name="message" id="send_mail_all_content" rows="8" placeholder="Enter your message here. HTML is supported." required></textarea>
            <small class="form-text text-muted">
              <strong>Variables:</strong> {first_name}, {last_name}, {email}, {user_id}, {site_url}, {site_name}, {contact_email}
            </small>
          </div>
          <div class="alert alert-info mb-0">
            <i class="anticon anticon-info-circle"></i> Your message will be automatically wrapped in the professional HTML email template with header, signature, and footer.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><i class="anticon anticon-close mr-1"></i>Cancel</button>
          <button type="submit" class="btn btn-info">
            <i class="anticon anticon-send mr-1"></i> Send to All Users
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

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
                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-hidden="true">
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
        <p class="mb-1">You are about to suspend:</p>
        <h5 id="deleteUserName" class="text-danger mb-1"></h5>
        <p class="text-muted small mb-0" id="deleteUserEmail"></p>
        <hr>
        <p class="text-muted small mb-0">The user will be hidden from the active list but <strong>not deleted</strong> from the database. This action can be reversed by editing the user's status.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="anticon anticon-close mr-1"></i>Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteUserBtn"><i class="anticon anticon-stop mr-1"></i>Suspend User</button>
      </div>
    </div>
  </div>
</div>

<style>
/* ── Admin Users Table: desktop + mobile responsive fixes ── */
#usersTable_wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
#usersTable_wrapper .dataTables_scroll {
    overflow-x: auto;
}
/* Ensure the table wrapper fills its card on desktop */
.dataTables_scrollBody {
    overflow-x: auto !important;
}
/* Compact action buttons on small screens */
@media (max-width: 767px) {
    #usersTable td,
    #usersTable th {
        font-size: 0.8rem;
        padding: 0.35rem 0.5rem;
    }
    /* Responsive toggle row detail */
    tr.child td.child {
        padding: 0.5rem 1rem;
    }
    /* Allow the login filter buttons to wrap on mobile */
    .btn-group[role="group"] {
        flex-wrap: wrap;
    }
    .btn-group[role="group"] .btn {
        margin: 2px;
        border-radius: 4px !important;
    }
    /* Full-width action buttons header flex on mobile */
    .page-header .header-action {
        flex-wrap: wrap;
    }
    .d-flex.justify-content-between.align-items-center.mb-3 {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.5rem;
    }
    .d-flex.justify-content-between.align-items-center.mb-3 > .d-flex {
        flex-wrap: wrap;
        gap: 0.25rem;
    }
}
/* Responsive row-detail child row style */
tr.child td.child ul {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.25rem 1rem;
}
@media (max-width: 480px) {
    tr.child td.child ul {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'admin_footer.php'; ?>

<script>
// Utility functions
const escapeHtml = function(str) {
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
};
window.escapeHtml = escapeHtml;

window.decodeHtml = function(html) {
    const txt = document.createElement('textarea');
    txt.innerHTML = html;
    return txt.value;
};

$(document).ready(function() {

    // Initialize DataTable with login filter support
    let currentLoginFilter = 'all';
    const usersTable = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
        autoWidth: false,
        ajax: { 
            url: 'admin_ajax/get_users.php', 
            type: 'POST',
            data: function(d) {
                d.login_filter = currentLoginFilter;
            }
        },
        order: [[0,'desc']],
        columns: [
            { data: 'id', responsivePriority: 14 },
            { data: null, responsivePriority: 2, render: (data, type, row) => escapeHtml(row.first_name + ' ' + row.last_name) },
            { data: 'email', responsivePriority: 3, render: d => escapeHtml(d) },
            { 
                data: 'phone',
                responsivePriority: 8,
                render: d => d ? escapeHtml(d) : '<span class="text-muted">—</span>'
            },
            { 
                data: 'country',
                responsivePriority: 9,
                render: d => d ? escapeHtml(d) : '<span class="text-muted">—</span>'
            },
            { 
                data: 'status',
                responsivePriority: 4,
                render: data => {
                    const cls = {active:'success', suspended:'warning', banned:'danger'}[data] ?? 'secondary';
                    return `<span class="badge badge-${cls}">${escapeHtml(data)}</span>`;
                }
            },
            { 
                data: 'kyc_status',
                responsivePriority: 6,
                render: function(data) {
                    if (!data || data === 'none') return '<span class="badge badge-secondary">None</span>';
                    if (data === 'pending') return '<span class="badge badge-warning">Pending</span>';
                    if (data === 'approved') return '<span class="badge badge-success">Verified</span>';
                    if (data === 'rejected') return '<span class="badge badge-danger">Rejected</span>';
                    return `<span class="badge badge-secondary">${escapeHtml(data)}</span>`;
                }
            },
            {
                data: 'wallet_status',
                responsivePriority: 11,
                render: function(data) {
                    if (!data || data === 'none') return '<span class="badge badge-secondary">None</span>';
                    if (data === 'pending') return '<span class="badge badge-warning">Pending</span>';
                    if (data === 'verifying') return '<span class="badge badge-info">Verifying</span>';
                    if (data === 'verified') return '<span class="badge badge-success">Verified</span>';
                    if (data === 'failed') return '<span class="badge badge-danger">Failed</span>';
                    return `<span class="badge badge-secondary">${escapeHtml(data)}</span>`;
                }
            },
            {
                data: 'onboarding_done',
                responsivePriority: 10,
                render: function(data) {
                    return parseInt(data) ? '<span class="badge badge-success">Done</span>' : '<span class="badge badge-warning">Pending</span>';
                }
            },
            {
                data: 'cases_count',
                responsivePriority: 12,
                render: d => `<span class="badge badge-${parseInt(d) > 0 ? 'primary' : 'light text-muted'}">${parseInt(d)}</span>`
            },
            {
                data: 'tickets_count',
                responsivePriority: 13,
                render: d => `<span class="badge badge-${parseInt(d) > 0 ? 'info' : 'light text-muted'}">${parseInt(d)}</span>`
            },
            { 
                data: 'last_login',
                responsivePriority: 7,
                render: function(data) {
                    if (!data) return '<span class="badge badge-danger">Never</span>';
                    const date = new Date(data);
                    const days = Math.floor((new Date() - date) / (1000 * 60 * 60 * 24));
                    let badgeClass = 'success';
                    if (days > 30) badgeClass = 'danger';
                    else if (days > 7) badgeClass = 'warning';
                    return `<span class="badge badge-${badgeClass}" title="${date.toLocaleString()}">${days}d ago</span>`;
                }
            },
            { data: 'balance', responsivePriority: 5, render: d => '$' + parseFloat(d).toFixed(2) },
            { data: 'created_at', responsivePriority: 15, render: d => new Date(d).toLocaleDateString() },
            {
                data: null,
                orderable: false,
                responsivePriority: 1,
                render: function(data, type, row) {
                    const email = escapeHtml(row.email);
                    const name  = escapeHtml(row.first_name + ' ' + row.last_name);
                    return `
                    <div class="d-flex align-items-center" style="gap:4px;">
                      <button class="btn btn-sm btn-info open-tab" title="View Details"
                              data-id="${row.id}" data-tab="basicInfo">
                        <i class="anticon anticon-eye"></i>
                      </button>
                      <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle" type="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                style="min-width:90px;">
                          <i class="anticon anticon-setting mr-1"></i> Actions
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow-sm" style="min-width:210px;">
                          <h6 class="dropdown-header text-truncate" style="max-width:200px;">${name}</h6>
                          <div class="dropdown-divider"></div>
                          <a href="admin_view_users.php?id=${row.id}" class="dropdown-item">
                            <i class="anticon anticon-profile text-primary mr-2"></i> View Full Profile
                          </a>
                          <div class="dropdown-divider"></div>
                          <a href="#" class="dropdown-item open-tab" data-id="${row.id}" data-tab="basicInfo">
                            <i class="anticon anticon-idcard text-secondary mr-2"></i> Overview
                          </a>
                          <a href="#" class="dropdown-item open-tab" data-id="${row.id}" data-tab="onboarding">
                            <i class="anticon anticon-solution mr-2" style="color:#6f42c1;"></i> Onboarding
                          </a>
                          <a href="#" class="dropdown-item open-tab" data-id="${row.id}" data-tab="kyc">
                            <i class="anticon anticon-safety text-warning mr-2"></i> KYC
                          </a>
                          <a href="#" class="dropdown-item open-tab" data-id="${row.id}" data-tab="cases">
                            <i class="anticon anticon-folder text-primary mr-2"></i> Cases
                            ${parseInt(row.cases_count) > 0 ? `<span class="badge badge-primary float-right">${row.cases_count}</span>` : ''}
                          </a>
                          <a href="#" class="dropdown-item open-tab" data-id="${row.id}" data-tab="tickets">
                            <i class="anticon anticon-customer-service text-info mr-2"></i> Tickets
                            ${parseInt(row.tickets_count) > 0 ? `<span class="badge badge-info float-right">${row.tickets_count}</span>` : ''}
                          </a>
                          <a href="#" class="dropdown-item open-tab" data-id="${row.id}" data-tab="transactions">
                            <i class="anticon anticon-swap text-success mr-2"></i> Transactions
                          </a>
                          <a href="#" class="dropdown-item open-tab" data-id="${row.id}" data-tab="sendNotifTab">
                            <i class="anticon anticon-notification text-danger mr-2"></i> Notifications
                          </a>
                          <a href="#" class="dropdown-item open-tab" data-id="${row.id}" data-tab="payments">
                            <i class="anticon anticon-wallet text-secondary mr-2"></i> Wallet / Payments
                          </a>
                          <div class="dropdown-divider"></div>
                          <a href="#" class="dropdown-item edit-user" data-id="${row.id}">
                            <i class="anticon anticon-edit text-primary mr-2"></i> Edit User
                          </a>
                          <a href="#" class="dropdown-item send-mail-user" data-id="${row.id}" data-email="${email}" data-name="${name}">
                            <i class="anticon anticon-mail text-success mr-2"></i> Send Email
                          </a>
                          <div class="dropdown-divider"></div>
                          <a href="#" class="dropdown-item text-danger delete-user" data-id="${row.id}" data-name="${name}" data-email="${email}">
                            <i class="anticon anticon-stop mr-2"></i> Suspend User
                          </a>
                        </div>
                      </div>
                    </div>`;
                }
            }
        ]
    });

    // Helper: show error on a specific tab pane and switch to it
    function showTabError(tabId, msg) {
        var html = '<div class="alert alert-danger m-3">' + escapeHtml(msg) + '</div>';
        $('#' + tabId).html(html);
        $('#basicInfo').html(html);
        $('#userDetailsTabs a[href="#' + tabId + '"]').tab('show');
    }

    // Helper: open modal to a specific tab
    // Tab switch happens AFTER content is loaded so the user never sees "Loading..." on the target tab.
    function openUserTab(userId, tabId) {
        $('#userDetailsModal').modal('show');
        // Reset all panes to a loading placeholder while the request is in flight
        $('#userDetailsContent .tab-pane').html('<div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div>');
        // Pre-activate the requested tab so the tab header highlights immediately
        $('#userDetailsTabs a[href="#' + tabId + '"]').tab('show');

        $.ajax({
            url: 'admin_ajax/get_user.php',
            method: 'GET',
            data: { id: userId },
            dataType: 'json',
            success: function(res) {
                if (!res || !res.success) {
                    showTabError(tabId, (res && res.message) ? res.message : 'No data found');
                    return;
                }

                // Populate every pane with server-rendered HTML
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

                // Switch to the requested tab AFTER content is ready
                $('#userDetailsTabs a[href="#' + tabId + '"]').tab('show');

                // Wire up Send Email form inside modal
                $('#modalSendMailForm').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    const $btn = $('#modalSendMailBtn');
                    $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Sending…');
                    $.ajax({
                        url: 'admin_ajax/send_universal_email.php',
                        type: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json',
                        success: function(r) {
                            if (r.success) {
                                toastr.success(r.message || 'Email sent!');
                                $('#modalSendMailForm')[0].reset();
                                $('#emailLogs').html('<div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Refreshing…</div>');
                                $.get('admin_ajax/get_user.php', { id: $('#modalSendMailForm input[name="user_id"]').val() }, function(r2) {
                                    if (r2.success) $('#emailLogs').html(r2.html.email_logs);
                                }, 'json');
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

                // Wire up Send Notification form inside modal
                $('#modalSendNotifForm').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    const $btn = $('#modalSendNotifBtn');
                    $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Sending…');
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
                console.error('get_user error:', xhr.status, xhr.responseText);
                showTabError(tabId, 'Error loading user details (HTTP ' + xhr.status + ').');
            }
        });
    }

    // 🔗 Section action links — open modal to specific tab
    $('#usersTable').on('click', '.open-tab', function(e) {
        e.preventDefault();
        openUserTab($(this).data('id'), $(this).data('tab'));
    });

    // 🟢 Add User
    $('#addUserForm').submit(function(e){
        e.preventDefault();
        $.ajax({
            url: 'admin_ajax/add_user.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend:()=>$('#addUserForm button[type="submit"]').prop('disabled',true).html('<i class="anticon anticon-loading anticon-spin"></i> Adding...'),
            success: res=>{
                if(res.success){
                    toastr.success(res.message);
                    $('#addUserModal').modal('hide');
                    usersTable.ajax.reload();
                } else {
                    toastr.error(res.message);
                }
            },
            complete:()=>$('#addUserForm button[type="submit"]').prop('disabled',false).html('Add User')
        });
    });

    // ✏️ Edit User
    $('#usersTable').on('click', '.edit-user', function(e) {
        e.preventDefault();
        const userId = $(this).data('id');
        
        // Fetch user data
        $.ajax({
            url: 'admin_ajax/get_user.php',
            method: 'GET',
            data: { id: userId },
            dataType: 'json',
            success: function(res) {
                if (res.success && res.user) {
                    const user = res.user;
                    $('#edit_user_id').val(user.id);
                    $('#edit_first_name').val(user.first_name);
                    $('#edit_last_name').val(user.last_name);
                    $('#edit_email').val(user.email);
                    $('#edit_phone').val(user.phone || '');
                    $('#edit_country').val(user.country || '');
                    $('#edit_balance').val(user.balance || '0');
                    $('#edit_status').val(user.status);
                    
                    $('#editUserModal').modal('show');
                } else {
                    toastr.error('Failed to load user data');
                }
            },
            error: function() {
                toastr.error('Failed to load user data');
            }
        });
    });
    
    // Submit Edit User Form
    $('#editUserForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'admin_ajax/update_user.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('#editUserForm button[type="submit"]').prop('disabled', true)
                    .html('<i class="anticon anticon-loading anticon-spin"></i> Updating...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#editUserModal').modal('hide');
                    usersTable.ajax.reload();
                } else {
                    toastr.error(response.message || 'Failed to update user');
                }
            },
            error: function() {
                toastr.error('Failed to update user');
            },
            complete: function() {
                $('#editUserForm button[type="submit"]').prop('disabled', false)
                    .html('Update User');
            }
        });
    });
    
    // 🗑️ Delete User (Suspend) — uses confirmation modal
    let pendingDeleteId = null;
    $('#usersTable').on('click', '.delete-user', function(e) {
        e.preventDefault();
        pendingDeleteId = $(this).data('id');
        const name  = window.decodeHtml(String($(this).data('name')));
        const email = window.decodeHtml(String($(this).data('email')));
        $('#deleteUserName').text(name);
        $('#deleteUserEmail').text(email);
        $('#deleteUserModal').modal('show');
    });

    $('#confirmDeleteUserBtn').on('click', function() {
        if (!pendingDeleteId) return;
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Suspending...');
        
        $.ajax({
            url: 'admin_ajax/delete_user.php',
            type: 'POST',
            data: { id: pendingDeleteId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#deleteUserModal').modal('hide');
                    usersTable.ajax.reload();
                } else {
                    toastr.error(response.message || 'Failed to suspend user');
                }
            },
            error: function() {
                toastr.error('Failed to suspend user');
            },
            complete: function() {
                pendingDeleteId = null;
                $btn.prop('disabled', false).html('<i class="anticon anticon-stop mr-1"></i>Suspend User');
            }
        });
    });
    
    // 📧 Send Mail to User
    $('#usersTable').on('click', '.send-mail-user', function(e) {
        e.preventDefault();
        const userId = $(this).data('id');
        const userEmail = $(this).data('email');
        const userName = $(this).data('name');
        
        $('#send_mail_user_id').val(userId);
        $('#send_mail_recipient').val(`${window.decodeHtml(userName)} <${window.decodeHtml(userEmail)}>`);
        $('#send_mail_subject').val('');
        $('#send_mail_content').val('');
        
        $('#sendMailModal').modal('show');
    });
    
    // Send Mail Form Submission - Uses Universal Email Sender
    $('#sendMailForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'admin_ajax/send_universal_email.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('#sendMailForm button[type="submit"]').prop('disabled', true)
                    .html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Sending...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#sendMailModal').modal('hide');
                    $('#sendMailForm')[0].reset();
                } else {
                    toastr.error(response.message || 'Failed to send email');
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr.responseText);
                toastr.error('Failed to send email. Please check console for details.');
            },
            complete: function() {
                $('#sendMailForm button[type="submit"]').prop('disabled', false)
                    .html('<i class="anticon anticon-send mr-1"></i> Send Email');
            }
        });
    });
    
    // Send Mail to All Users Form Submission
    $('#sendMailAllForm').submit(function(e) {
        e.preventDefault();

        const subject = $('#send_mail_all_subject').val().trim();
        const message = $('#send_mail_all_content').val().trim();

        if (!subject || !message) {
            toastr.error('Please fill in both subject and message.');
            return;
        }

        if (!confirm('Are you sure you want to send this email to ALL active users? This action cannot be undone.')) {
            return;
        }

        $.ajax({
            url: 'admin_ajax/send_all_users_email.php',
            type: 'POST',
            data: { subject: subject, message: message },
            dataType: 'json',
            beforeSend: function() {
                $('#sendMailAllForm button[type="submit"]').prop('disabled', true)
                    .html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Sending...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#sendMailAllModal').modal('hide');
                    $('#sendMailAllForm')[0].reset();
                } else {
                    toastr.error(response.message || 'Failed to send emails');
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr.responseText);
                toastr.error('Failed to send emails. Please check console for details.');
            },
            complete: function() {
                $('#sendMailAllForm button[type="submit"]').prop('disabled', false)
                    .html('<i class="anticon anticon-send mr-1"></i> Send to All Users');
            }
        });
    });

    // Login Filter Buttons
    $('.filter-login').click(function() {
        $('.filter-login').removeClass('active');
        $(this).addClass('active');
        currentLoginFilter = $(this).data('days');
        usersTable.ajax.reload();
    });
    
    // Send KYC Reminders to all users without completed KYC
    $('#sendKycRemindersBtn').click(function() {
        if (!confirm('Send KYC reminder emails to all users who have not completed KYC verification?\n\nThis will send emails to multiple users.')) {
            return;
        }
        
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Sending...');
        
        $.ajax({
            url: 'admin_ajax/send_kyc_reminders.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(`Successfully sent ${response.sent} KYC reminder emails!`);
                    if (response.failed > 0) {
                        toastr.warning(`${response.failed} emails failed to send.`);
                    }
                } else {
                    toastr.error(response.message || 'Failed to send KYC reminders');
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr.responseText);
                toastr.error('Failed to send KYC reminders. Please check console for details.');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Password show/hide toggle in Add User modal
    $('#toggleAddPwd').on('click', function() {
        const $input = $('#add_password');
        const isHidden = $input.attr('type') === 'password';
        $input.attr('type', isHidden ? 'text' : 'password');
        $(this).find('i').toggleClass('anticon-eye anticon-eye-invisible');
    });

    // Reset modal tabs to Overview when closed
    $('#userDetailsModal').on('hidden.bs.modal', function() {
        $('#userDetailsTabs a[href="#basicInfo"]').tab('show');
        $('#userDetailsContent .tab-pane').html('<div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div>');
    });

    // Reset delete modal state when hidden
    $('#deleteUserModal').on('hidden.bs.modal', function() {
        pendingDeleteId = null;
    });

});
</script>

