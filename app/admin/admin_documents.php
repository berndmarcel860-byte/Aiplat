<?php
require_once 'admin_header.php';

// Get document statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

try {
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM user_documents");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching document stats: " . $e->getMessage());
}
?>

<div class="main-content">
    <div class="page-header">
        <h2>User Documents</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">User Documents</span>
            </nav>
        </div>
    </div>
    
    <!-- Document Statistics -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-blue">
                            <i class="anticon anticon-file"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-muted mb-0">Total Documents</p>
                            <h4 class="mb-0"><?= number_format($stats['total']) ?></h4>
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
                            <i class="anticon anticon-clock-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-muted mb-0">Pending Review</p>
                            <h4 class="mb-0"><?= number_format($stats['pending']) ?></h4>
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
                            <i class="anticon anticon-check-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-muted mb-0">Approved</p>
                            <h4 class="mb-0"><?= number_format($stats['approved']) ?></h4>
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
                            <p class="text-muted mb-0">Rejected</p>
                            <h4 class="mb-0"><?= number_format($stats['rejected']) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <div class="card">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5>Document Management</h5>
                </div>
                <div class="col-md-6 text-right">
                    <div class="btn-group mr-2" role="group">
                        <button type="button" class="btn btn-sm btn-default active" data-status="all">All</button>
                        <button type="button" class="btn btn-sm btn-warning" data-status="pending">Pending</button>
                        <button type="button" class="btn btn-sm btn-success" data-status="approved">Approved</button>
                        <button type="button" class="btn btn-sm btn-danger" data-status="rejected">Rejected</button>
                    </div>
                    <button class="btn btn-info btn-sm" id="refreshDocuments">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Documents Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="documentsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Document Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Document Modal -->
<div class="modal fade" id="viewDocumentModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Document Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body" id="documentDetails">
                <div class="text-center p-3">
                    <i class="anticon anticon-loading anticon-spin"></i> Loading...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <a href="#" id="downloadDocBtn" class="btn btn-primary" download>
                    <i class="anticon anticon-download"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Document Status</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="updateStatusForm">
                <div class="modal-body">
                    <input type="hidden" name="document_id" id="status_document_id">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status" id="document_status" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes (optional)</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    let currentStatus = 'all';
    
    // Initialize DataTable
    const documentsTable = $('#documentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_documents.php',
            type: 'POST',
            data: function(d) {
                d.status_filter = currentStatus;
            }
        },
        order: [[6, 'desc']],
        columns: [
            { data: 'id' },
            { 
                data: null,
                render: function(data) {
                    return data.user_name || `User #${data.user_id}`;
                }
            },
            { data: 'document_name' },
            { data: 'document_type' },
            { 
                data: 'file_size',
                render: function(data) {
                    return formatFileSize(data);
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    const badges = {
                        'pending': 'warning',
                        'approved': 'success',
                        'rejected': 'danger'
                    };
                    return `<span class="badge badge-${badges[data]}">${data.toUpperCase()}</span>`;
                }
            },
            { 
                data: 'uploaded_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString() + ' ' + new Date(data).toLocaleTimeString();
                }
            },
            {
                data: null,
                render: function(data) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info view-doc" 
                                    data-id="${data.id}" 
                                    title="View Details">
                                <i class="anticon anticon-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary update-status" 
                                    data-id="${data.id}"
                                    data-status="${data.status}"
                                    title="Update Status">
                                <i class="anticon anticon-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-doc" 
                                    data-id="${data.id}" 
                                    title="Delete">
                                <i class="anticon anticon-delete"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });
    
    // Status filter buttons
    $('[data-status]').click(function() {
        $('[data-status]').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status');
        documentsTable.ajax.reload();
    });
    
    // Refresh button
    $('#refreshDocuments').click(function() {
        documentsTable.ajax.reload();
        location.reload(); // Reload stats
    });
    
    // View document
    $('#documentsTable').on('click', '.view-doc', function() {
        const docId = $(this).data('id');
        $('#documentDetails').html('<div class="text-center p-3"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div>');
        $('#viewDocumentModal').modal('show');
        
        $.get('admin_ajax/get_document_details.php', { id: docId }, function(response) {
            if (response.success) {
                const doc = response.document;
                $('#documentDetails').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Document Name:</strong> ${doc.document_name}</p>
                            <p><strong>Type:</strong> ${doc.document_type}</p>
                            <p><strong>Size:</strong> ${formatFileSize(doc.file_size)}</p>
                            <p><strong>Status:</strong> <span class="badge badge-${doc.status === 'approved' ? 'success' : doc.status === 'pending' ? 'warning' : 'danger'}">${doc.status.toUpperCase()}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>User:</strong> ${doc.user_name || 'User #' + doc.user_id}</p>
                            <p><strong>Uploaded:</strong> ${new Date(doc.uploaded_at).toLocaleString()}</p>
                            <p><strong>Description:</strong> ${doc.description || 'N/A'}</p>
                        </div>
                    </div>
                `);
                $('#downloadDocBtn').attr('href', doc.file_path);
            } else {
                $('#documentDetails').html('<div class="alert alert-danger">Failed to load document details</div>');
            }
        });
    });
    
    // Update status
    $('#documentsTable').on('click', '.update-status', function() {
        const docId = $(this).data('id');
        const currentStatus = $(this).data('status');
        $('#status_document_id').val(docId);
        $('#document_status').val(currentStatus);
        $('#updateStatusModal').modal('show');
    });
    
    $('#updateStatusForm').submit(function(e) {
        e.preventDefault();
        $.post('admin_ajax/update_document_status.php', $(this).serialize(), function(response) {
            if (response.success) {
                toastr.success('Status updated successfully');
                $('#updateStatusModal').modal('hide');
                documentsTable.ajax.reload();
                location.reload(); // Reload stats
            } else {
                toastr.error(response.message || 'Failed to update status');
            }
        });
    });
    
    // Delete document
    $('#documentsTable').on('click', '.delete-doc', function() {
        if (!confirm('Are you sure you want to delete this document?')) return;
        
        const docId = $(this).data('id');
        $.post('admin_ajax/delete_document.php', { id: docId }, function(response) {
            if (response.success) {
                toastr.success('Document deleted successfully');
                documentsTable.ajax.reload();
                location.reload(); // Reload stats
            } else {
                toastr.error(response.message || 'Failed to delete document');
            }
        });
    });
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
});
</script>
