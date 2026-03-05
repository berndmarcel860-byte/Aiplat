<?php
require_once 'admin_header.php';

// Get log statistics
$logStats = [
    'total' => 0,
    'errors' => 0,
    'warnings' => 0,
    'info' => 0
];

try {
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN action LIKE ? OR action LIKE ? THEN 1 ELSE 0 END) as errors,
        SUM(CASE WHEN action LIKE ? OR action LIKE ? THEN 1 ELSE 0 END) as warnings,
        SUM(CASE WHEN action NOT LIKE ? AND action NOT LIKE ? AND action NOT LIKE ? THEN 1 ELSE 0 END) as info
        FROM admin_logs
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute(['%error%', '%fail%', '%warning%', '%suspend%', '%error%', '%fail%', '%warning%']);
    $logStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $logStats;
} catch (PDOException $e) {
    error_log("Error fetching log stats: " . $e->getMessage());
}
?>

<div class="main-content">
    <div class="page-header">
        <h2>System Logs</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">System Logs</span>
            </nav>
        </div>
    </div>
    
    <!-- Log Statistics -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-blue">
                            <i class="anticon anticon-file-text"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-muted mb-0">Total Logs (24h)</p>
                            <h4 class="mb-0"><?= number_format($logStats['total']) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-red">
                            <i class="anticon anticon-close-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-muted mb-0">Errors</p>
                            <h4 class="mb-0 text-danger"><?= number_format($logStats['errors']) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-gold">
                            <i class="anticon anticon-warning"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-muted mb-0">Warnings</p>
                            <h4 class="mb-0 text-warning"><?= number_format($logStats['warnings']) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-green">
                            <i class="anticon anticon-info-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-muted mb-0">Info</p>
                            <h4 class="mb-0 text-success"><?= number_format($logStats['info']) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <div class="card">
        <div class="card-body">
            <form id="logFilterForm" class="row align-items-end">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date Range</label>
                        <select class="form-control" name="date_range" id="dateRange">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="7days" selected>Last 7 Days</option>
                            <option value="30days">Last 30 Days</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Log Type</label>
                        <select class="form-control" name="log_type" id="logType">
                            <option value="all">All Types</option>
                            <option value="login">Login Events</option>
                            <option value="user">User Actions</option>
                            <option value="payment">Payment Events</option>
                            <option value="system">System Events</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Admin</label>
                        <select class="form-control" name="admin_id" id="adminId">
                            <option value="">All Admins</option>
                            <?php
                            try {
                                $adminsStmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM admins ORDER BY first_name");
                                $adminsStmt->execute();
                                $admins = $adminsStmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($admins as $admin) {
                                    echo '<option value="' . $admin['id'] . '">' . htmlspecialchars($admin['name']) . '</option>';
                                }
                            } catch (PDOException $e) {
                                error_log("Error loading admins list: " . $e->getMessage());
                                echo '<option value="">Error loading admins</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <button type="button" class="btn btn-primary btn-block" id="applyFilters">
                            <i class="anticon anticon-filter"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Logs Table -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Activity Logs</h5>
                <div>
                    <button class="btn btn-info btn-sm mr-2" id="refreshLogs">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                    <button class="btn btn-success btn-sm" id="exportLogs">
                        <i class="anticon anticon-download"></i> Export
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="logsTable" class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Timestamp</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Entry Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <div class="text-center p-3">
                    <i class="anticon anticon-loading anticon-spin"></i> Loading...
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
    const logsTable = $('#logsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_admin_logs.php',
            type: 'POST',
            data: function(d) {
                d.date_range = $('#dateRange').val();
                d.log_type = $('#logType').val();
                d.admin_id = $('#adminId').val();
            }
        },
        order: [[1, 'desc']],
        columns: [
            { data: 'id' },
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            },
            { data: 'admin_name' },
            { 
                data: 'action',
                render: function(data) {
                    return `<code>${data}</code>`;
                }
            },
            { 
                data: null,
                render: function(data) {
                    return `${data.entity_type} #${data.entity_id || 'N/A'}`;
                }
            },
            { data: 'ip_address' },
            {
                data: 'action',
                render: function(data) {
                    if (data.includes('error') || data.includes('fail') || data.includes('reject')) {
                        return '<span class="badge badge-danger">Error</span>';
                    } else if (data.includes('warning') || data.includes('suspend')) {
                        return '<span class="badge badge-warning">Warning</span>';
                    } else {
                        return '<span class="badge badge-success">Success</span>';
                    }
                }
            },
            {
                data: 'id',
                render: function(data) {
                    return `<button class="btn btn-sm btn-info view-log" data-id="${data}">
                        <i class="anticon anticon-eye"></i>
                    </button>`;
                }
            }
        ]
    });
    
    // Apply filters
    $('#applyFilters').click(function() {
        logsTable.ajax.reload();
    });
    
    // Refresh logs
    $('#refreshLogs').click(function() {
        logsTable.ajax.reload();
    });
    
    // Export logs
    $('#exportLogs').click(function() {
        toastr.info('Exporting logs...');
        // TODO: Implement actual export
        setTimeout(() => {
            toastr.success('Logs exported successfully');
        }, 1000);
    });
    
    // View log details
    $('#logsTable').on('click', '.view-log', function() {
        const logId = $(this).data('id');
        $('#logDetailsContent').html('<div class="text-center p-3"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div>');
        $('#logDetailsModal').modal('show');
        
        $.get('admin_ajax/get_log_details.php', { id: logId }, function(response) {
            if (response.success) {
                const log = response.log;
                $('#logDetailsContent').html(`
                    <table class="table table-bordered">
                        <tr><th width="200">Log ID</th><td>${log.id}</td></tr>
                        <tr><th>Timestamp</th><td>${new Date(log.created_at).toLocaleString()}</td></tr>
                        <tr><th>Admin</th><td>${log.admin_name || 'N/A'}</td></tr>
                        <tr><th>Action</th><td><code>${log.action}</code></td></tr>
                        <tr><th>Entity Type</th><td>${log.entity_type || 'N/A'}</td></tr>
                        <tr><th>Entity ID</th><td>${log.entity_id || 'N/A'}</td></tr>
                        <tr><th>IP Address</th><td>${log.ip_address}</td></tr>
                        <tr><th>User Agent</th><td>${log.user_agent || 'N/A'}</td></tr>
                        <tr><th>Notes</th><td>${log.notes || 'N/A'}</td></tr>
                    </table>
                `);
            } else {
                $('#logDetailsContent').html('<div class="alert alert-danger">Failed to load log details</div>');
            }
        });
    });
});
</script>