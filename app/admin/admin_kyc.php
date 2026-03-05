<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">KYC Verification</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">KYC</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5>KYC Requests</h5>
                <div class="btn-group">
                    <button class="btn btn-success" data-toggle="modal" data-target="#addKYCModal">
                        <i class="anticon anticon-plus"></i> Add KYC
                    </button>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#filterKYCModal">
                        <i class="anticon anticon-filter"></i> Filter
                    </button>
                </div>
            </div>
            
            <div class="m-t-25">
                <table id="kycTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Document Type</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- AJAX will populate this -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add KYC Modal -->
<div class="modal fade" id="addKYCModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add KYC Documents for User</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="addKYCForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select User <span class="text-danger">*</span></label>
                        <select class="form-control" name="user_id" required>
                            <option value="">-- Select User --</option>
                        </select>
                        <small class="form-text text-muted">Select the user for KYC verification</small>
                    </div>
                    <div class="form-group">
                        <label>Document Type <span class="text-danger">*</span></label>
                        <select class="form-control" name="document_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="passport">Passport</option>
                            <option value="id_card">ID Card</option>
                            <option value="driving_license">Driving License</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Document Front <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="document_front" accept="image/*,application/pdf" required>
                                <small class="form-text text-muted">Upload front side of document</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Document Back</label>
                                <input type="file" class="form-control" name="document_back" accept="image/*,application/pdf">
                                <small class="form-text text-muted">Upload back side (if applicable)</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Selfie with ID</label>
                                <input type="file" class="form-control" name="selfie_with_id" accept="image/*">
                                <small class="form-text text-muted">Upload selfie holding ID</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Address Proof</label>
                                <input type="file" class="form-control" name="address_proof" accept="image/*,application/pdf">
                                <small class="form-text text-muted">Utility bill, bank statement, etc.</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Admin Notes</label>
                        <textarea class="form-control" name="admin_notes" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="anticon anticon-plus"></i> Create KYC Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterKYCModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter KYC Requests</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="filterKYCForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Document Type</label>
                        <select class="form-control" name="document_type">
                            <option value="">All Types</option>
                            <option value="passport">Passport</option>
                            <option value="id_card">ID Card</option>
                            <option value="driving_license">Driving License</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- KYC Details Modal -->
<div class="modal fade" id="kycDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">KYC Verification Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body" id="kycDetailsContent">
                <!-- AJAX will populate this -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success approve-kyc">Approve</button>
                <button type="button" class="btn btn-danger reject-kyc">Reject</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const kycTable = $('#kycTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_kyc_requests.php',
            type: 'POST'
        },
        columns: [
            { data: 'id' },
            { 
                data: null,
                render: function(data) {
                    return data.user_first_name + ' ' + data.user_last_name;
                }
            },
            { 
                data: 'document_type',
                render: function(data) {
                    return data.charAt(0).toUpperCase() + data.slice(1).replace('_', ' ');
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    const statusClass = {
                        pending: 'warning',
                        approved: 'success',
                        rejected: 'danger'
                    }[data];
                    return `<span class="badge badge-${statusClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
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
                            <button class="btn btn-sm btn-primary view-kyc" data-id="${data}">
                                <i class="anticon anticon-eye"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });

    // View KYC Details
    let currentKycId = null;
    $('#kycTable').on('click', '.view-kyc', function() {
        currentKycId = $(this).data('id');
        
        $.ajax({
            url: 'admin_ajax/get_kyc_request.php',
            type: 'GET',
            data: { id: currentKycId },
            success: function(response) {
                if (response.success) {
                    const kyc = response.kyc;
                    
                    let documentFront = kyc.document_front ? 
                        `<a href="${kyc.document_front}" target="_blank"><img src="../${kyc.document_front}" class="img-fluid mb-2" style="max-height: 200px;"></a>` : 
                        'N/A';
                    
                    let documentBack = kyc.document_back ? 
                        `<a href="${kyc.document_back}" target="_blank"><img src="../${kyc.document_back}" class="img-fluid mb-2" style="max-height: 200px;"></a>` : 
                        'N/A';
                    
                    let selfie = kyc.selfie_with_id ? 
                        `<a href="${kyc.selfie_with_id}" target="_blank"><img src="../${kyc.selfie_with_id}" class="img-fluid mb-2" style="max-height: 200px;"></a>` : 
                        'N/A';
                    
                    let addressProof = kyc.address_proof ? 
                        `<a href="${kyc.address_proof}" target="_blank"><img src="../${kyc.address_proof}" class="img-fluid mb-2" style="max-height: 200px;"></a>` : 
                        'N/A';
                    
                    $('#kycDetailsContent').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>User</label>
                                    <p>${kyc.user_first_name} ${kyc.user_last_name}</p>
                                </div>
                                <div class="form-group">
                                    <label>Document Type</label>
                                    <p>${kyc.document_type.charAt(0).toUpperCase() + kyc.document_type.slice(1).replace('_', ' ')}</p>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <p>${kyc.status.charAt(0).toUpperCase() + kyc.status.slice(1)}</p>
                                </div>
                                <div class="form-group">
                                    <label>Submitted Date</label>
                                    <p>${new Date(kyc.created_at).toLocaleString()}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Document Front</label>
                                    <div>${documentFront}</div>
                                </div>
                                <div class="form-group">
                                    <label>Document Back</label>
                                    <div>${documentBack}</div>
                                </div>
                                <div class="form-group">
                                    <label>Selfie with ID</label>
                                    <div>${selfie}</div>
                                </div>
                                <div class="form-group">
                                    <label>Address Proof</label>
                                    <div>${addressProof}</div>
                                </div>
                            </div>
                        </div>
                    `);
                    
                    // Show/hide buttons based on status
                    if (kyc.status === 'pending') {
                        $('.approve-kyc, .reject-kyc').show();
                    } else {
                        $('.approve-kyc, .reject-kyc').hide();
                    }
                    
                    $('#kycDetailsModal').modal('show');
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Approve KYC
    $('.approve-kyc').click(function() {
        if (confirm('Are you sure you want to approve this KYC verification?')) {
            $.ajax({
                url: 'admin_ajax/approve_kyc.php',
                type: 'POST',
                data: { id: currentKycId },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        kycTable.ajax.reload();
                        $('#kycDetailsModal').modal('hide');
                    } else {
                        toastr.error(response.message);
                    }
                }
            });
        }
    });

    // Reject KYC
    $('.reject-kyc').click(function() {
        const reason = prompt('Please enter the rejection reason:');
        if (reason !== null) {
            $.ajax({
                url: 'admin_ajax/reject_kyc.php',
                type: 'POST',
                data: { 
                    id: currentKycId,
                    reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        kycTable.ajax.reload();
                        $('#kycDetailsModal').modal('hide');
                    } else {
                        toastr.error(response.message);
                    }
                }
            });
        }
    });

    // Apply Filters
    $('#filterKYCForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        kycTable.ajax.url('admin_ajax/get_kyc_requests.php?' + formData).load();
        $('#filterKYCModal').modal('hide');
    });

    // Load users when Add KYC modal opens
    $('#addKYCModal').on('show.bs.modal', function() {
        $.ajax({
            url: 'admin_ajax/get_users_for_select.php',
            type: 'GET',
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    const select = $('#addKYCForm select[name="user_id"]');
                    select.find('option:not(:first)').remove();
                    (resp.users || []).forEach(function(user) {
                        select.append(`<option value="${user.id}">${user.first_name} ${user.last_name} (${user.email})</option>`);
                    });
                }
            }
        });
    });

    // Add KYC Form Submit with file upload
    $('#addKYCForm').submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        $.ajax({
            url: 'admin_ajax/add_kyc.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    toastr.success(resp.message || 'KYC request created successfully');
                    $('#addKYCModal').modal('hide');
                    $('#addKYCForm')[0].reset();
                    kycTable.ajax.reload();
                } else {
                    toastr.error(resp && resp.message ? resp.message : 'Failed to create KYC request');
                }
            },
            error: function() {
                toastr.error('Server error');
            }
        });
    });
});
</script>