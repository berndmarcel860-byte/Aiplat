<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2>Email Logs</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Email Logs</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Email Logs</h5>
                <div class="d-flex">
                    <button class="btn btn-info mr-2" id="refreshEmailLogs">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                    <button class="btn btn-danger" id="clearLogs">
                        <i class="anticon anticon-delete"></i> Clear Old Logs
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="emailLogsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Template</th>
                            <th>Status</th>
                            <th>Sent At</th>
                            <th>Opened At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Email Modal -->
<div class="modal fade" id="viewEmailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Email Content</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="emailContent"></div>
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
    const emailLogsTable = $('#emailLogsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_email_logs.php',
            type: 'POST'
        },
        order: [[5, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'recipient' },
            { 
                data: 'subject',
                render: function(data) {
                    return data.length > 50 ? data.substring(0, 50) + '...' : data;
                }
            },
            { data: 'template_key' },
            { 
                data: 'status',
                render: function(data) {
                    const statusClass = {
                        'sent': 'success',
                        'delivered': 'info',
                        'opened': 'primary',
                        'failed': 'danger'
                    };
                    return `<span class="badge badge-${statusClass[data] || 'secondary'}">${data.toUpperCase()}</span>`;
                }
            },
            { 
                data: 'sent_at',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            },
            { 
                data: 'opened_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleString() : '-';
                }
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info view-email" 
                                    data-id="${data}" 
                                    title="View Email">
                                <i class="anticon anticon-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-log" 
                                    data-id="${data}" 
                                    title="Delete Log">
                                <i class="anticon anticon-delete"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });
    
    $('#refreshEmailLogs').click(function() {
        emailLogsTable.ajax.reload();
    });
    
    $(document).on('click', '.view-email', function() {
        const id = $(this).data('id');
        $.get('admin_ajax/get_email_content.php', {id: id})
        .done(function(response) {
            if (response.success) {
                $('#emailContent').html(`
                    <div class="mb-3">
                        <strong>To:</strong> ${response.data.recipient}<br>
                        <strong>Subject:</strong> ${response.data.subject}<br>
                        <strong>Sent:</strong> ${new Date(response.data.sent_at).toLocaleString()}
                    </div>
                    <div class="border p-3">
                        ${response.data.content}
                    </div>
                `);
                $('#viewEmailModal').modal('show');
            }
        });
    });
});
</script>