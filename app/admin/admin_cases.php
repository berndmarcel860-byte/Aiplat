<?php
require_once 'admin_header.php';

// Role-based user loading: superadmin sees all users, admin sees only their own
$currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
$currentAdminId = (int)$_SESSION['admin_id'];

if ($currentAdminRole === 'superadmin') {
    // Superadmin: get ALL users
    $users = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE status != 'suspended'")->fetchAll();
} else {
    // Admin: get only their own users
    $stmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE status != 'suspended' AND admin_id = ?");
    $stmt->execute([$currentAdminId]);
    $users = $stmt->fetchAll();
}

$platforms = $pdo->query("SELECT id, name FROM scam_platforms")->fetchAll();

// Role-based admin loading: superadmin sees all admins, regular admin sees only themselves
if ($currentAdminRole === 'superadmin') {
    $admins = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM admins")->fetchAll();
} else {
    // Regular admin only sees themselves in dropdown
    $stmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM admins WHERE id = ?");
    $stmt->execute([$currentAdminId]);
    $admins = $stmt->fetchAll();
}
?>

<div class="main-content">
    <div class="page-header">
        <h2>Case Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Cases</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Case List</h5>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addCaseModal">
                    <i class="anticon anticon-plus"></i> Add Case
                </button>
            </div>
            
            <div class="m-t-15">
                <table id="casesTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Case #</th>
                            <th>User</th>
                            <th>Platform</th>
                            <th>Reported</th>
                            <th>Recovered</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Case Modal -->
<div class="modal fade" id="addCaseModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Case</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="addCaseForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>User</label>
                                <select class="form-control" name="user_id" required>
                                    <option value="">Select User</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Platform</label>
                                <select class="form-control" name="platform_id" required>
                                    <option value="">Select Platform</option>
                                    <?php foreach ($platforms as $platform): ?>
                                        <option value="<?= $platform['id'] ?>"><?= htmlspecialchars($platform['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reported Amount</label>
                                <input type="number" step="0.01" class="form-control" name="reported_amount" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Assigned To</label>
                                <select class="form-control" name="admin_id">
                                    <option value="">Select Admin</option>
                                    <?php foreach ($admins as $admin): ?>
                                        <option value="<?= $admin['id'] ?>"><?= htmlspecialchars($admin['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="4" required>KI-gestützte Fallregistrierung erfolgreich abgeschlossen. Erste Rückverfolgung der Transaktionen läuft.</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Case</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Case Modal -->
<div class="modal fade" id="editCaseModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Case <span id="editCaseNumber"></span></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="editCaseForm">
                <input type="hidden" name="case_id" id="editCaseId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>User</label>
                                <p class="form-control-static" id="editCaseUser"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Platform</label>
                                <p class="form-control-static" id="editCasePlatform"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Reported Amount</label>
                                <p class="form-control-static" id="editCaseAmount"></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Recovered Amount</label>
                                <p class="form-control-static" id="editCaseRecovered"></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="status" id="editCaseStatus">
                                    <option value="open">Open</option>
                                    <option value="documents_required">Documents Required</option>
                                    <option value="under_review">Under Review</option>
                                    <option value="refund_approved">Refund Approved</option>
                                    <option value="refund_rejected">Refund Rejected</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Assigned To</label>
                                <select class="form-control" name="admin_id" id="editCaseAdmin">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($admins as $admin): ?>
                                        <option value="<?= $admin['id'] ?>"><?= htmlspecialchars($admin['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Recovery Progress</label>
                                <div class="progress">
                                    <div id="editCaseProgress" class="progress-bar" role="progressbar"></div>
                                </div>
                                <small id="editCaseProgressText" class="form-text text-muted"></small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Admin Notes</label>
                        <textarea class="form-control" name="admin_notes" id="editCaseNotes" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status Change Notes</label>
                        <textarea class="form-control" name="status_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Case Details Modal -->
<div class="modal fade" id="caseDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Case Details <span id="caseDetailsNumber"></span></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>User Information</h6>
                        <p id="caseDetailsUser"></p>
                        <p id="caseDetailsUserEmail"></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Case Information</h6>
                        <p><strong>Platform:</strong> <span id="caseDetailsPlatform"></span></p>
                        <p><strong>Reported Amount:</strong> <span id="caseDetailsAmount"></span></p>
                        <p><strong>Recovered Amount:</strong> <span id="caseDetailsRecovered"></span></p>
                        <p><strong>Status:</strong> <span id="caseDetailsStatus"></span></p>
                    </div>
                </div>
                
                <h6>Description</h6>
                <div class="card mb-4">
                    <div class="card-body" id="caseDetailsDescription"></div>
                </div>
                
                <h6>Admin Notes</h6>
                <div class="card mb-4">
                    <div class="card-body" id="caseDetailsAdminNotes"></div>
                </div>
                
                <h6>Recovery Progress</h6>
                <div class="progress mb-4">
                    <div id="caseDetailsProgress" class="progress-bar" role="progressbar"></div>
                </div>
                
                <h6>Recovery Transactions</h6>
                <div class="table-responsive mb-4">
                    <table class="table" id="caseDetailsRecoveries">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Processed By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="table-success">
                                <th>Total Recovered</th>
                                <th id="caseDetailsTotalRecovered">$0.00</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <h6>Status History</h6>
                <div class="table-responsive mb-4">
                    <table class="table" id="caseDetailsHistory">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Changed By</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                
                <h6>Documents</h6>
                <div id="caseDetailsDocuments">
                    <p>No documents uploaded</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="addRecoveryBtn">Add Recovery</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Recovery Modal -->
<div class="modal fade" id="addRecoveryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Recovery Amount</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="addRecoveryForm">
                <input type="hidden" id="recoveryCaseId" name="case_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Amount to Add</label>
                        <input type="number" step="0.01" class="form-control" id="recoveryAmount" required>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" id="recoveryNotes" rows="3"></textarea>
                    </div>
                    <div class="alert alert-info">
                        <strong>Current Status:</strong><br>
                        Total Recovered: <span id="totalRecovered">$0.00</span><br>
                        Remaining Balance: <span id="remainingBalance">$0.00</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Recovery</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const casesTable = $('#casesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_cases.php',
            type: 'GET'
        },
        columns: [
            { data: 'case_number' },
            { 
                data: null,
                render: function(data) {
                    return data.user_first_name + ' ' + data.user_last_name;
                }
            },
            { 
                data: null,
                render: function(data) {
                    return data.platform_name || 'N/A';
                }
            },
            { 
                data: 'reported_amount',
                render: function(data) {
                    return '$' + parseFloat(data).toFixed(2);
                }
            },
            { 
                data: 'recovered_amount',
                render: function(data, type, row) {
                    const recovered = parseFloat(data || 0);
                    const reported = parseFloat(row.reported_amount || 0);
                    const percentage = reported > 0 ? (recovered / reported * 100) : 0;
                    
                    return `
                        <div>
                            <strong>$${recovered.toFixed(2)}</strong>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar" 
                                     role="progressbar" 
                                     style="width: ${percentage}%">
                                </div>
                            </div>
                            <small>${percentage.toFixed(1)}% of $${reported.toFixed(2)}</small>
                        </div>
                    `;
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    const statusClass = {
                        open: 'secondary',
                        documents_required: 'warning',
                        under_review: 'info',
                        refund_approved: 'success',
                        refund_rejected: 'danger',
                        closed: 'dark'
                    }[data];
                    return `<span class="badge badge-${statusClass}">${data.replace(/_/g, ' ')}</span>`;
                }
            },
            { 
                data: null,
                render: function(data) {
                    return data.admin_first_name 
                        ? data.admin_first_name + ' ' + data.admin_last_name 
                        : 'Unassigned';
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
                            <button class="btn btn-sm btn-primary edit-case" 
                                    data-id="${data}" 
                                    title="Edit Case">
                                <i class="anticon anticon-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-success add-recovery" 
                                    data-id="${data}" 
                                    data-case-number="${row.case_number}"
                                    data-reported-amount="${row.reported_amount}"
                                    data-recovered-amount="${row.recovered_amount || 0}"
                                    title="Add Recovery">
                                <i class="anticon anticon-dollar"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-case" 
                                    data-id="${data}" 
                                    title="Delete Case">
                                <i class="anticon anticon-delete"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });

    // Add Case Form Submission
    $('#addCaseForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serializeArray();
        const postData = {};
        $.each(formData, function(i, field) {
            postData[field.name] = field.value;
        });

        $.ajax({
            url: 'admin_ajax/add_case.php',
            type: 'POST',
            data: JSON.stringify(postData),
            contentType: 'application/json',
            beforeSend: function() {
                $('#addCaseForm button[type="submit"]').prop('disabled', true)
                    .html('<i class="anticon anticon-loading anticon-spin"></i> Adding...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#addCaseModal').modal('hide');
                    casesTable.ajax.reload();
                    $('#addCaseForm')[0].reset();
                } else {
                    toastr.error(response.message);
                    if (response.error) {
                        console.error("Error:", response.error);
                    }
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Failed to add case");
                console.error("AJAX Error:", status, error);
            },
            complete: function() {
                $('#addCaseForm button[type="submit"]').prop('disabled', false).html('Add Case');
            }
        });
    });

    // View Case Details
    $('#casesTable').on('click', '.view-case', function() {
        const caseId = $(this).data('id');
        loadCaseDetails(caseId);
    });

    // Add Recovery from Details Modal
    $('#addRecoveryBtn').click(function() {
        const caseId = $('#recoveryCaseId').val();
        const caseNumber = $('#caseDetailsNumber').text();
        const reportedAmount = parseFloat($('#caseDetailsAmount').text().replace('$', ''));
        const recoveredAmount = parseFloat($('#caseDetailsRecovered').text().replace('$', ''));
        
        $('#recoveryCaseId').val(caseId);
        $('#addRecoveryModal .modal-title').text(`Add Recovery - ${caseNumber}`);
        $('#totalRecovered').text('$' + recoveredAmount.toFixed(2));
        $('#remainingBalance').text('$' + (reportedAmount - recoveredAmount).toFixed(2));
        $('#addRecoveryModal').modal('show');
    });

    // Initialize recovery modal with case data
    $(document).on('click', '.add-recovery', function() {
        const caseId = $(this).data('id');
        const caseNumber = $(this).data('case-number');
        const reportedAmount = parseFloat($(this).data('reported-amount'));
        const recoveredAmount = parseFloat($(this).data('recovered-amount')) || 0;
        
        $('#recoveryCaseId').val(caseId);
        $('#addRecoveryModal .modal-title').text(`Add Recovery - ${caseNumber}`);
        $('#totalRecovered').text('$' + recoveredAmount.toFixed(2));
        $('#remainingBalance').text('$' + (reportedAmount - recoveredAmount).toFixed(2));
        $('#addRecoveryModal').modal('show');
    });

    // Add recovery amount form handler
    $('#addRecoveryForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serializeArray();
        const postData = {
            case_id: $('#recoveryCaseId').val(),
            amount: parseFloat($('#recoveryAmount').val()),
            notes: $('#recoveryNotes').val()
        };

        $.ajax({
            url: 'admin_ajax/update_recovery.php',
            type: 'POST',
            data: JSON.stringify(postData),
            contentType: 'application/json',
            beforeSend: function() {
                $('#addRecoveryForm button[type="submit"]').prop('disabled', true)
                    .html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#addRecoveryModal').modal('hide');
                    casesTable.ajax.reload();
                    $('#addRecoveryForm')[0].reset();
                    
                    // If viewing details, reload them
                    if ($('#caseDetailsModal').is(':visible')) {
                        loadCaseDetails($('#recoveryCaseId').val());
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            complete: function() {
                $('#addRecoveryForm button[type="submit"]').prop('disabled', false)
                    .html('Add Recovery');
            }
        });
    });

    // Edit Case - Load Data
    $('#casesTable').on('click', '.edit-case', function() {
        const caseId = $(this).data('id');
        
        $.get('admin_ajax/get_case.php?id=' + caseId, function(response) {
            if (response.success) {
                const caseData = response.case;
                const recovered = parseFloat(caseData.recovered_amount || 0);
                const reported = parseFloat(caseData.reported_amount || 1);
                const percentage = (recovered / reported * 100).toFixed(1);
                
                $('#editCaseId').val(caseData.id);
                $('#editCaseNumber').text(caseData.case_number);
                $('#editCaseUser').text(caseData.user_first_name + ' ' + caseData.user_last_name);
                $('#editCasePlatform').text(caseData.platform_name);
                $('#editCaseAmount').text('$' + reported.toFixed(2));
                $('#editCaseRecovered').text('$' + recovered.toFixed(2));
                $('#editCaseStatus').val(caseData.status);
                $('#editCaseAdmin').val(caseData.admin_id || '');
                $('#editCaseNotes').val(caseData.admin_notes || '');
                
                // Update progress bar
                $('#editCaseProgress').css('width', percentage + '%');
                $('#editCaseProgressText').text(`${percentage}% recovered ($${recovered.toFixed(2)} of $${reported.toFixed(2)})`);
                
                $('#editCaseModal').modal('show');
            } else {
                toastr.error(response.message);
            }
        });
    });

    // Edit Case Form Submission
    $('#editCaseForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serializeArray();
        const postData = {};
        $.each(formData, function(i, field) {
            postData[field.name] = field.value;
        });

        $.ajax({
            url: 'admin_ajax/update_case.php',
            type: 'POST',
            data: JSON.stringify(postData),
            contentType: 'application/json',
            beforeSend: function() {
                $('#editCaseForm button[type="submit"]').prop('disabled', true)
                    .html('<i class="anticon anticon-loading anticon-spin"></i> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#editCaseModal').modal('hide');
                    casesTable.ajax.reload();
                } else {
                    toastr.error(response.message);
                    if (response.error) {
                        console.error("Error:", response.error);
                    }
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Failed to update case");
                console.error("AJAX Error:", status, error);
            },
            complete: function() {
                $('#editCaseForm button[type="submit"]').prop('disabled', false).html('Save Changes');
            }
        });
    });

    // Delete Case
    $('#casesTable').on('click', '.delete-case', function() {
        const caseId = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this case? This action cannot be undone.')) {
            $.ajax({
                url: 'admin_ajax/delete_case.php',
                type: 'POST',
                data: { case_id: caseId },
                beforeSend: function() {
                    $(this).prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        casesTable.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error("Failed to delete case");
                    console.error("AJAX Error:", status, error);
                },
                complete: function() {
                    $(this).prop('disabled', false);
                }
            });
        }
    });

    // Function to load case details
    function loadCaseDetails(caseId) {
        $.get('admin_ajax/get_case.php?id=' + caseId, function(response) {
            if (response.success) {
                const caseData = response.case;
                const recovered = parseFloat(caseData.recovered_amount || 0);
                const reported = parseFloat(caseData.reported_amount || 1);
                const percentage = (recovered / reported * 100).toFixed(1);
                
                // Set basic info
                $('#recoveryCaseId').val(caseData.id);
                $('#caseDetailsNumber').text(caseData.case_number);
                $('#caseDetailsUser').text(caseData.user_first_name + ' ' + caseData.user_last_name);
                $('#caseDetailsUserEmail').text(caseData.user_email);
                $('#caseDetailsPlatform').text(caseData.platform_name);
                $('#caseDetailsAmount').text('$' + reported.toFixed(2));
                $('#caseDetailsRecovered').text('$' + recovered.toFixed(2));
                $('#caseDetailsStatus').html(`
                    <span class="badge badge-${getStatusClass(caseData.status)}">
                        ${caseData.status.replace(/_/g, ' ')}
                    </span>
                `);
                $('#caseDetailsDescription').text(caseData.description);
                $('#caseDetailsAdminNotes').text(caseData.admin_notes || 'No notes available');
                
                // Update progress bar
                $('#caseDetailsProgress').css('width', percentage + '%')
                    .attr('aria-valuenow', percentage)
                    .text(`${percentage}%`);
                
                // Populate recovery transactions
                const recoveriesTable = $('#caseDetailsRecoveries tbody');
                recoveriesTable.empty();
                
                if (response.recoveries && response.recoveries.length > 0) {
                    response.recoveries.forEach(recovery => {
                        recoveriesTable.append(`
                            <tr>
                                <td>${new Date(recovery.transaction_date).toLocaleString()}</td>
                                <td>$${parseFloat(recovery.amount).toFixed(2)}</td>
                                <td>${recovery.admin_first_name} ${recovery.admin_last_name}</td>
                                <td>${recovery.notes || ''}</td>
                            </tr>
                        `);
                    });
                    $('#caseDetailsTotalRecovered').text('$' + recovered.toFixed(2));
                } else {
                    recoveriesTable.append('<tr><td colspan="4">No recovery transactions</td></tr>');
                    $('#caseDetailsTotalRecovered').text('$0.00');
                }
                
                // Populate history
                const historyTable = $('#caseDetailsHistory tbody');
                historyTable.empty();
                
                if (response.history.length > 0) {
                    response.history.forEach(history => {
                        historyTable.append(`
                            <tr>
                                <td>${new Date(history.created_at).toLocaleString()}</td>
                                <td>${history.first_name} ${history.last_name}</td>
                                <td>${history.old_status ? history.old_status.replace(/_/g, ' ') : 'N/A'}</td>
                                <td>${history.new_status.replace(/_/g, ' ')}</td>
                                <td>${history.notes || ''}</td>
                            </tr>
                        `);
                    });
                } else {
                    historyTable.append('<tr><td colspan="5">No history available</td></tr>');
                }
                
                // Populate documents
                const documentsContainer = $('#caseDetailsDocuments');
                documentsContainer.empty();
                
                if (response.documents.length > 0) {
                    const docList = $('<div class="list-group"></div>');
                    response.documents.forEach(doc => {
                        docList.append(`
                            <a href="${doc.file_path}" target="_blank" class="list-group-item list-group-item-action">
                                <i class="anticon anticon-file"></i> ${doc.document_type}
                            </a>
                        `);
                    });
                    documentsContainer.append(docList);
                } else {
                    documentsContainer.append('<p>No documents uploaded</p>');
                }
                
                $('#caseDetailsModal').modal('show');
            } else {
                toastr.error(response.message);
            }
        });
    }

    // Helper function for status badges
    function getStatusClass(status) {
        const statusClasses = {
            open: 'secondary',
            documents_required: 'warning',
            under_review: 'info',
            refund_approved: 'success',
            refund_rejected: 'danger',
            closed: 'dark'
        };
        return statusClasses[status] || 'secondary';
    }
});
</script>