<?php
require_once 'admin_header.php';

// Get admins for filter dropdown
$admins = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM admins ORDER BY first_name")->fetchAll();
?>

<div class="main-content">
    <div class="page-header">
        <h2>Audit Logs</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Audit Logs</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>System Audit Trail</h5>
                <div class="d-flex">
                    <button class="btn btn-info mr-2" id="refreshAuditLogs">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="anticon anticon-filter"></i> Filters
                        </button>
                        <div class="dropdown-menu dropdown-menu-right p-3" style="min-width: 320px;">
                            <div class="form-group">
                                <label>Admin</label>
                                <select class="form-control" id="adminFilter">
                                    <option value="">All Admins</option>
                                    <?php foreach ($admins as $admin): ?>
                                        <option value="<?= $admin['id'] ?>"><?= htmlspecialchars($admin['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Action</label>
                                <select class="form-control" id="actionFilter">
                                    <option value="">All Actions</option>
                                    <option value="create">Create</option>
                                    <option value="update">Update</option>
                                    <option value="delete">Delete</option>
                                    <option value="login">Login</option>
                                    <option value="logout">Logout</option>
                                    <option value="view">View</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Entity Type</label>
                                <select class="form-control" id="entityFilter">
                                    <option value="">All Entities</option>
                                    <option value="user">User</option>
                                    <option value="case">Case</option>
                                    <option value="platform">Platform</option>
                                    <option value="admin">Admin</option>
                                    <option value="transaction">Transaction</option>
                                    <option value="setting">Setting</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Date Range</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="date" class="form-control" id="auditStartDate">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" class="form-control" id="auditEndDate">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-block" id="applyAuditFilters">Apply Filters</button>
                            <button type="button" class="btn btn-secondary btn-block" id="clearAuditFilters">Clear Filters</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="auditLogsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>IP Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Audit Details Modal -->
<div class="modal fade" id="auditDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit Log Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Admin:</strong> <span id="auditDetailAdmin"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Date/Time:</strong> <span id="auditDetailDate"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Action:</strong> <span id="auditDetailAction"></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Entity Type:</strong> <span id="auditDetailEntityType"></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Entity ID:</strong> <span id="auditDetailEntityId"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>IP Address:</strong> <span id="auditDetailIP"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>User Agent:</strong> <span id="auditDetailUA" style="font-size: 0.9em;"></span>
                    </div>
                </div>
                
                <div id="auditOldValue" class="mb-3" style="display: none;">
                    <strong>Old Value:</strong>
                    <pre class="bg-light p-3" style="max-height: 200px; overflow-y: auto;" id="auditOldValueContent"></pre>
                </div>
                
                <div id="auditNewValue" class="mb-3" style="display: none;">
                    <strong>New Value:</strong>
                    <pre class="bg-light p-3" style="max-height: 200px; overflow-y: auto;" id="auditNewValueContent"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const auditLogsTable = $('#auditLogsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_audit_logs.php',
            type: 'POST',
            data: function(d) {
                d.admin_id = $('#adminFilter').val();
                d.action = $('#actionFilter').val();
                d.entity_type = $('#entityFilter').val();
                d.start_date = $('#auditStartDate').val();
                d.end_date = $('#auditEndDate').val();
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
                    return data.admin_name || 'System';
                }
            },
            { 
                data: 'action',
                render: function(data) {
                    const actionClass = {
                        create: 'success',
                        update: 'warning',
                        delete: 'danger',
                        login: 'info',
                        logout: 'secondary',
                        view: 'light'
                    }[data] || 'primary';
                    return `<span class="badge badge-${actionClass}">${data.toUpperCase()}</span>`;
                }
            },
            { 
                data: null,
                render: function(data) {
                    return `${data.entity_type || 'N/A'}${data.entity_id ? ` (#${data.entity_id})` : ''}`;
                }
            },
            { 
                data: 'ip_address'
            },
            {
                data: 'id',
                render: function(data) {
                    return `
                        <button class="btn btn-sm btn-info view-audit" 
                                data-id="${data}" 
                                title="View Details">
                            <i class="anticon anticon-eye"></i>
                        </button>
                    `;
                }
            }
        ]
    });

    // Apply filters
    $('#applyAuditFilters').click(function() {
        auditLogsTable.ajax.reload();
    });

    // Clear filters
    $('#clearAuditFilters').click(function() {
        $('#adminFilter, #actionFilter, #entityFilter, #auditStartDate, #auditEndDate').val('');
        auditLogsTable.ajax.reload();
    });

    // Refresh logs
    $('#refreshAuditLogs').click(function() {
        auditLogsTable.ajax.reload();
    });

    // View audit details
    $('#auditLogsTable').on('click', '.view-audit', function() {
        const auditId = $(this).data('id');
        
        $.get('admin_ajax/get_audit_log.php?id=' + auditId, function(response) {
            if (response.success) {
                const audit = response.audit;
                
                $('#auditDetailAdmin').text(audit.admin_name || 'System');
                $('#auditDetailDate').text(new Date(audit.created_at).toLocaleString());
                $('#auditDetailAction').html(`<span class="badge badge-primary">${audit.action.toUpperCase()}</span>`);
                $('#auditDetailEntityType').text(audit.entity_type || 'N/A');
                $('#auditDetailEntityId').text(audit.entity_id || 'N/A');
                $('#auditDetailIP').text(audit.ip_address);
                $('#auditDetailUA').text(audit.user_agent || 'N/A');
                
                // Handle old value
                if (audit.old_value) {
                    $('#auditOldValue').show();
                    try {
                        const oldValue = JSON.parse(audit.old_value);
                        $('#auditOldValueContent').text(JSON.stringify(oldValue, null, 2));
                    } catch (e) {
                        $('#auditOldValueContent').text(audit.old_value);
                    }
                } else {
                    $('#auditOldValue').hide();
                }
                
                // Handle new value
                if (audit.new_value) {
                    $('#auditNewValue').show();
                    try {
                        const newValue = JSON.parse(audit.new_value);
                        $('#auditNewValueContent').text(JSON.stringify(newValue, null, 2));
                    } catch (e) {
                        $('#auditNewValueContent').text(audit.new_value);
                    }
                } else {
                    $('#auditNewValue').hide();
                }
                
                $('#auditDetailsModal').modal('show');
            } else {
                toastr.error(response.message);
            }
        });
    });
});
</script>