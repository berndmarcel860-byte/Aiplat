<?php
require_once 'admin_header.php';

// Get list of admins for assignment dropdown
$admins = [];
try {
    $stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM admins WHERE status = 'active' ORDER BY first_name");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error
}
?>

<div class="main-content">
    <div class="page-header">
        <h2>Case Assignments</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Case Assignments</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Case Assignments</h5>
                <div class="d-flex">
                    <button class="btn btn-info mr-2" id="refreshCaseAssignments">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="case_assignmentsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Case Number</th>
                            <th>User</th>
                            <th>Amount (Reported/Recovered)</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div class="modal fade" id="assignCaseModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Case</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="assignCaseForm">
                <div class="modal-body">
                    <input type="hidden" name="case_id" id="assign_case_id">
                    <div class="form-group">
                        <label>Assign to Admin</label>
                        <select class="form-control" name="admin_id" required>
                            <option value="">Select Admin</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?= $admin['id'] ?>"><?= htmlspecialchars($admin['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign Case</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const case_assignmentsTable = $('#case_assignmentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_case_assignments.php',
            type: 'POST'
        },
        order: [[6, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'user_info' },
            { data: 'amount_info' },
            { 
                data: 'status',
                render: function(data) {
                    const statusClasses = {
                        'open': 'primary',
                        'documents_required': 'warning',
                        'under_review': 'info',
                        'refund_approved': 'success',
                        'refund_rejected': 'danger',
                        'closed': 'secondary'
                    };
                    return `<span class="badge badge-${statusClasses[data] || 'secondary'}">${data.replace('_', ' ').toUpperCase()}</span>`;
                }
            },
            { 
                data: 'assigned_admin',
                render: function(data) {
                    return data || '<span class="text-muted">Unassigned</span>';
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info view-case" 
                                    data-id="${data}" 
                                    title="View Details">
                                <i class="anticon anticon-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary assign-case" 
                                    data-id="${data}" 
                                    title="Assign Case">
                                <i class="anticon anticon-user-add"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });
    
    // Refresh button
    $('#refreshCaseAssignments').click(function() {
        case_assignmentsTable.ajax.reload();
    });
    
    // Assign case button
    $(document).on('click', '.assign-case', function() {
        const caseId = $(this).data('id');
        $('#assign_case_id').val(caseId);
        $('#assignCaseModal').modal('show');
    });
    
    // Assign case form submission
    $('#assignCaseForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.post('admin_ajax/assign_case.php', formData)
        .done(function(response) {
            if (response.success) {
                $('#assignCaseModal').modal('hide');
                case_assignmentsTable.ajax.reload();
                toastr.success(response.message || 'Case assigned successfully');
                $('#assignCaseForm')[0].reset();
            } else {
                toastr.error(response.message || 'Failed to assign case');
            }
        })
        .fail(function() {
            toastr.error('Failed to assign case');
        });
    });
});
</script>