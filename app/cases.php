<?php include 'header.php'; ?>

<!-- Content Wrapper START -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%); color: #fff;">
                    <div class="card-body py-4">
                        <h2 class="mb-2 text-white" style="font-weight: 700;">
                            <i class="anticon anticon-folder-open mr-2"></i>My Cases
                        </h2>
                        <p class="mb-0" style="color: rgba(255,255,255,0.9); font-size: 15px;">
                            View and manage all your reported cases
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cases Table Card -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0" style="color: #2c3e50; font-weight: 600;">
                                <i class="anticon anticon-file-text mr-2" style="color: var(--brand);"></i>My Reported Cases
                            </h5>
                            <button class="btn btn-outline-primary btn-sm" id="refreshCases" title="Refresh Table">
                                <i class="anticon anticon-reload mr-1"></i>Refresh
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table id="casesTable" class="table table-hover mb-0" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Case ID</th>
                                        <th>Platform</th>
                                        <th>Reported Amount</th>
                                        <th>Recovered Amount</th>
                                        <th>Status</th>
                                        <th>Date Created</th>
                                        <th>Last Updated</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Content Wrapper END -->

<!-- Case Details Modal -->
<div class="modal fade" id="caseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%); color: #fff; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold">
                    <i class="anticon anticon-file-text mr-2"></i>Case #<span id="caseNumber"></span> Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4" id="caseModalContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading case details...</p>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="anticon anticon-close mr-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline Styles for Cases Modal */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e0e0e0;
}

.timeline-item-active .timeline-marker {
    width: 14px;
    height: 14px;
    box-shadow: 0 0 0 3px rgba(41, 80, 168, 0.2);
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 17px;
    bottom: -5px;
    width: 2px;
    background: #e0e0e0;
}

.timeline-content {
    background: rgba(41, 80, 168, 0.03);
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid rgba(41, 80, 168, 0.2);
}

.timeline-item-active .timeline-content {
    background: rgba(41, 80, 168, 0.08);
    border-left-color: #2950a8;
}
</style>



<!-- Document Upload Modal -->
<div class="modal fade" id="documentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="anticon anticon-upload me-2"></i>
                    Upload Documents for Case #<span id="documentCaseNumber"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="documentUploadForm" enctype="multipart/form-data">
                <input type="hidden" id="documentCaseId" name="case_id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="anticon anticon-info-circle"></i>
                        Please upload all required documents to proceed with your case.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Document Type</label>
                        <select class="form-control" name="document_type" required>
                            <option value="">Select Document Type</option>
                            <option value="proof_of_payment">Proof of Payment</option>
                            <option value="identity_verification">Identity Verification</option>
                            <option value="communication_records">Communication Records</option>
                            <option value="bank_statement">Bank Statement</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Document File</label>
                        <input type="file" class="form-control" id="documentFile" name="document_file" required>
                        <small class="form-text text-muted">Max file size: 5MB (PDF, JPG, PNG)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" name="document_notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="anticon anticon-upload me-1"></i> Upload Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- DataTables already loaded in footer.php -->

<script>
$(document).ready(function() {
    const casesTable = $('#casesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 10,
        order: [[5, 'desc']],
        ajax: {
            url: 'user_ajax/get_my_cases.php',
            type: 'POST'
        },
        columns: [
            { data: 'case_number' },
            { data: null, render: d => d.platform_name || 'N/A' },
            { data: 'reported_amount', render: d => '$' + parseFloat(d).toFixed(2) },
            { 
                data: null, 
                render: function(data, type, row) {
                    const recovered = parseFloat(row.recovered_amount || 0);
                    const reported = parseFloat(row.reported_amount || 0);
                    const progress = reported > 0 ? ((recovered / reported) * 100).toFixed(1) : 0;
                    
                    return `
                        <div style="min-width:180px;">
                            <div>
                                <strong style="font-size:14px;color:#2c3e50;">$${recovered.toFixed(2)}</strong>
                            </div>
                            <div class="mt-1">
                                <div class="progress" style="height:6px;border-radius:3px;">
                                    <div class="progress-bar" 
                                         style="width:${progress}%;background:linear-gradient(90deg,#2950a8,#2da9e3);"
                                         role="progressbar" 
                                         aria-valuenow="${progress}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-1">
                                <small class="text-muted" style="font-size:11px;">${progress}% of $${reported.toFixed(2)}</small>
                            </div>
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
                    return `<span class="badge bg-${statusClass}">${data.replace(/_/g, ' ')}</span>`;
                }
            },
            { data: 'created_at', render: d => new Date(d).toLocaleDateString() },
            { data: 'updated_at', render: d => new Date(d).toLocaleDateString() },
            {
                data: 'id',
                render: function(data, type, row) {
                    let buttons = `
                        <button class="btn btn-sm btn-info view-case" data-id="${data}" title="View Details">
                            <i class="anticon anticon-eye"></i>
                        </button>`;
                    if (row.status === 'documents_required') {
                        buttons += `
                            <button class="btn btn-sm btn-warning upload-docs" 
                                    data-id="${data}" data-case-number="${row.case_number}" 
                                    title="Upload Documents">
                                <i class="anticon anticon-upload"></i>
                            </button>`;
                    }
                    return `<div class="btn-group">${buttons}</div>`;
                }
            }
        ]
    });

    // View Case Details
    $('#casesTable').on('click', '.view-case', function() {
        const caseId = $(this).data('id');
        loadCaseDetails(caseId);
    });

    // Open document upload modal
    $('#casesTable').on('click', '.upload-docs', function() {
        $('#documentCaseId').val($(this).data('id'));
        $('#documentCaseNumber').text($(this).data('case-number'));
        new bootstrap.Modal(document.getElementById('documentModal')).show();
    });

    // File input label update
    $('#documentFile').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('label').html(fileName || 'Choose file');
    });

    // Document upload form
    $('#documentUploadForm').submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        $.ajax({
            url: 'user_ajax/upload_document.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#documentUploadForm button[type="submit"]').prop('disabled', true)
                    .html('<i class="anticon anticon-loading anticon-spin"></i> Uploading...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    bootstrap.Modal.getInstance(document.getElementById('documentModal')).hide();
                    $('#documentUploadForm')[0].reset();
                    $('#documentFile').next('label').html('Choose file');

                    if ($('#caseModal').is(':visible')) {
                        loadCaseDetails($('#documentCaseId').val());
                    }
                    casesTable.ajax.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error("Failed to upload document");
            },
            complete: function() {
                $('#documentUploadForm button[type="submit"]').prop('disabled', false)
                    .html('<i class="anticon anticon-upload me-1"></i> Upload Document');
            }
        });
    });

    function loadCaseDetails(caseId) {
        // Show modal with loading state
        $('#caseModal').modal('show');
        $('#caseModalContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading case details...</p>
            </div>
        `);
        
        // Fetch case details via AJAX
        $.ajax({
            url: 'ajax/get-case.php',
            method: 'GET',
            data: { id: caseId },
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success && data.case) {
                        const c = data.case;
                        const progress = c.reported_amount > 0 ? Math.round((c.recovered_amount / c.reported_amount) * 100) : 0;
                        
                        const statusClass = getStatusClass(c.status);
                        
                        const html = `
                            <div class="case-details-content">
                                <!-- Header Info -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card border-0" style="background: rgba(41, 80, 168, 0.05);">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2" style="font-size: 12px; text-transform: uppercase;">Case Number</h6>
                                                <h4 class="mb-0 font-weight-bold" style="color: #2950a8;">${c.case_number || 'N/A'}</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0" style="background: rgba(41, 80, 168, 0.05);">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2" style="font-size: 12px; text-transform: uppercase;">Status</h6>
                                                <span class="badge badge-${statusClass} px-3 py-2" style="font-size: 14px;">
                                                    <i class="anticon anticon-flag mr-1"></i>${c.status ? c.status.replace(/_/g, ' ').toUpperCase() : 'N/A'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Financial Overview -->
                                <div class="card border-0 mb-4" style="background: linear-gradient(135deg, rgba(41, 80, 168, 0.05), rgba(45, 169, 227, 0.05));">
                                    <div class="card-body">
                                        <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-dollar mr-2" style="color: #2950a8;"></i>Financial Overview
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="text-muted mb-1" style="font-size: 13px;">Reported Amount</div>
                                                <h4 class="mb-0 font-weight-bold text-danger">$${parseFloat(c.reported_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h4>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="text-muted mb-1" style="font-size: 13px;">Recovered Amount</div>
                                                <h3 class="mb-2 font-weight-bold" style="color: #2c3e50;">$${parseFloat(c.recovered_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h3>
                                                <div class="progress mb-2" style="height: 8px; border-radius: 10px; background: #e9ecef;">
                                                    <div class="progress-bar" style="width: ${progress}%; background: linear-gradient(90deg, #2950a8 0%, #2da9e3 100%);"></div>
                                                </div>
                                                <small class="text-muted">${progress}% of $${parseFloat(c.reported_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Platform Info -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card border-0 h-100">
                                            <div class="card-body">
                                                <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                                    <i class="anticon anticon-global mr-2" style="color: #2950a8;"></i>Platform Information
                                                </h6>
                                                <p class="mb-2"><strong>Platform:</strong> ${c.platform_name || 'N/A'}</p>
                                                <p class="mb-0"><strong>Created:</strong> ${c.created_at ? new Date(c.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : 'N/A'}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 h-100">
                                            <div class="card-body">
                                                <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                                    <i class="anticon anticon-clock-circle mr-2" style="color: #2950a8;"></i>Timeline
                                                </h6>
                                                <p class="mb-2"><strong>Last Updated:</strong> ${c.updated_at ? new Date(c.updated_at).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : 'N/A'}</p>
                                                <p class="mb-0"><strong>Days Active:</strong> ${c.created_at ? Math.floor((new Date() - new Date(c.created_at)) / (1000 * 60 * 60 * 24)) : 0} days</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                ${c.description ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-file-text mr-2" style="color: #2950a8;"></i>Case Description
                                        </h6>
                                        <p class="mb-0" style="line-height: 1.6;">${c.description}</p>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Recovery Transactions -->
                                ${data.recoveries && data.recoveries.length > 0 ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-transaction mr-2" style="color: #2950a8;"></i>Recovery Transactions
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead style="background: rgba(41, 80, 168, 0.05);">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Amount</th>
                                                        <th>Method</th>
                                                        <th>Reference</th>
                                                        <th>Processed By</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.recoveries.map(r => `
                                                        <tr>
                                                            <td>${r.transaction_date ? new Date(r.transaction_date).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}) : 'N/A'}</td>
                                                            <td><strong class="text-success">$${parseFloat(r.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                                                            <td>${r.method || 'N/A'}</td>
                                                            <td><small class="text-muted">${r.transaction_reference || 'N/A'}</small></td>
                                                            <td>${r.admin_first_name && r.admin_last_name ? `${r.admin_first_name} ${r.admin_last_name}` : 'System'}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Documents -->
                                ${data.documents && data.documents.length > 0 ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-paper-clip mr-2" style="color: #2950a8;"></i>Case Documents
                                        </h6>
                                        <div class="list-group">
                                            ${data.documents.map(d => `
                                                <div class="list-group-item border-0 px-0">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="anticon anticon-file mr-2" style="color: #2950a8;"></i>
                                                            <strong>${d.document_type || 'Document'}</strong>
                                                            ${d.verified ? '<span class="badge badge-success badge-sm ml-2"><i class="anticon anticon-check"></i> Verified</span>' : ''}
                                                        </div>
                                                        <small class="text-muted">${d.uploaded_at ? new Date(d.uploaded_at).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}) : ''}</small>
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Status History -->
                                ${data.history && data.history.length > 0 ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-history mr-2" style="color: #2950a8;"></i>Status History
                                        </h6>
                                        <div class="timeline">
                                            ${data.history.map((h, idx) => `
                                                <div class="timeline-item ${idx === 0 ? 'timeline-item-active' : ''}">
                                                    <div class="timeline-marker ${idx === 0 ? 'bg-primary' : 'bg-secondary'}"></div>
                                                    <div class="timeline-content">
                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                            <strong>${h.new_status ? h.new_status.replace(/_/g, ' ').toUpperCase() : 'Status Change'}</strong>
                                                            <small class="text-muted">${h.created_at ? new Date(h.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : ''}</small>
                                                        </div>
                                                        ${h.comments ? `<p class="mb-1 text-muted small">${h.comments}</p>` : ''}
                                                        ${h.first_name && h.last_name ? `<small class="text-muted">By: ${h.first_name} ${h.last_name}</small>` : ''}
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        `;
                        
                        $('#caseModalContent').html(html);
                        $('#caseNumber').text(c.case_number || 'N/A');
                    } else {
                        $('#caseModalContent').html(`
                            <div class="alert alert-danger">
                                <i class="anticon anticon-close-circle mr-2"></i>${data.message || 'Unable to load case details'}
                            </div>
                        `);
                    }
                } catch (e) {
                    $('#caseModalContent').html(`
                        <div class="alert alert-danger">
                            <i class="anticon anticon-close-circle mr-2"></i>Error parsing case data
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                $('#caseModalContent').html(`
                    <div class="alert alert-danger">
                        <i class="anticon anticon-close-circle mr-2"></i>Error loading case details: ${error}
                    </div>
                `);
            }
        });
    }

    function getStatusClass(status) {
        return {
            open: 'secondary',
            documents_required: 'warning',
            under_review: 'info',
            refund_approved: 'success',
            refund_rejected: 'danger',
            closed: 'dark'
        }[status] || 'secondary';
    }

    // Refresh button
    $('.refresh-btn').click(function() {
        casesTable.ajax.reload();
        toastr.success('Cases refreshed');
    });
});
</script>
