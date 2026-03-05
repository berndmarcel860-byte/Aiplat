<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2>Admin Notifications</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Notifications</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Notifications</h5>
                <div class="d-flex">
                    <button class="btn btn-info mr-2" id="refreshNotifications">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addNotificationModal">
                        <i class="anticon anticon-plus"></i> Create Notification
                    </button>
                    <button class="btn btn-success ml-2" id="markAllRead">
                        <i class="anticon anticon-check"></i> Mark All Read
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="notificationsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Admin</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Notification Modal -->
<div class="modal fade" id="addNotificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Notification</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="notificationForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Send To</label>
                        <select class="form-control" name="admin_id" required>
                            <option value="">Select Admin</option>
                            <option value="all">All Admins</option>
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM admins WHERE status = 'active' ORDER BY first_name");
                                while ($admin = $stmt->fetch()) {
                                    echo "<option value='{$admin['id']}'>" . htmlspecialchars($admin['name']) . "</option>";
                                }
                            } catch (PDOException $e) {}
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea class="form-control" name="message" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select class="form-control" name="type" required>
                            <option value="info">Info</option>
                            <option value="warning">Warning</option>
                            <option value="danger">Danger</option>
                            <option value="success">Success</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Send Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    const notificationsTable = $('#notificationsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_notifications.php',
            type: 'POST'
        },
        order: [[5, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'admin_name' },
            { data: 'title' },
            { 
                data: 'type',
                render: function(data) {
                    const typeClass = {
                        'info': 'info',
                        'warning': 'warning',
                        'danger': 'danger',
                        'success': 'success'
                    };
                    return `<span class="badge badge-${typeClass[data]}">${data.toUpperCase()}</span>`;
                }
            },
            { 
                data: 'is_read',
                render: function(data) {
                    return data == 1 ? 
                        '<span class="badge badge-success">Read</span>' : 
                        '<span class="badge badge-warning">Unread</span>';
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    let actions = `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info view-notification" 
                                    data-id="${data}" 
                                    title="View">
                                <i class="anticon anticon-eye"></i>
                            </button>
                    `;
                    
                    if (row.is_read == 0) {
                        actions += `
                            <button class="btn btn-sm btn-success mark-read" 
                                    data-id="${data}" 
                                    title="Mark as Read">
                                <i class="anticon anticon-check"></i>
                            </button>
                        `;
                    }
                    
                    actions += `
                            <button class="btn btn-sm btn-danger delete-notification" 
                                    data-id="${data}" 
                                    title="Delete">
                                <i class="anticon anticon-delete"></i>
                            </button>
                        </div>
                    `;
                    
                    return actions;
                }
            }
        ]
    });
    
    $('#refreshNotifications').click(function() {
        notificationsTable.ajax.reload();
    });
    
    $('#notificationForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.post('admin_ajax/create_notification.php', formData)
        .done(function(response) {
            if (response.success) {
                $('#addNotificationModal').modal('hide');
                notificationsTable.ajax.reload();
                toastr.success(response.message);
                $('#notificationForm')[0].reset();
            } else {
                toastr.error(response.message);
            }
        })
        .fail(function() {
            toastr.error('Failed to send notification');
        });
    });
});
</script>