<?php
require_once 'admin_header.php';

// Get platform types for dropdown
$platform_types = [
    'crypto' => 'Cryptocurrency',
    'forex' => 'Forex Trading',
    'investment' => 'Investment',
    'dating' => 'Dating Scam',
    'tax' => 'Tax Scam',
    'other' => 'Other'
];
?>

<div class="main-content">
    <div class="page-header">
        <h2>Platform Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Scam Platforms</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Platform List</h5>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addPlatformModal">
                    <i class="anticon anticon-plus"></i> Add Platform
                </button>
            </div>
            
            <div class="m-t-15">
                <table id="platformsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>URL</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Total Reported</th>
                            <th>Total Recovered</th>
                            <th>Status</th>
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

<!-- Add Platform Modal -->
<div class="modal fade" id="addPlatformModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Platform</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="addPlatformForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Platform Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Type</label>
                                <select class="form-control" name="type" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($platform_types as $key => $label): ?>
                                        <option value="<?= $key ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>URL</label>
                        <input type="url" class="form-control" name="url" placeholder="https://example.com">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="4" placeholder="Enter platform description..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Logo</label>
                        <input type="file" class="form-control" name="logo" accept="image/*">
                        <small class="form-text text-muted">Upload logo image (JPG, PNG, GIF - Max 2MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Platform</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Platform Modal -->
<div class="modal fade" id="editPlatformModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Platform</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="editPlatformForm" enctype="multipart/form-data">
                <input type="hidden" name="platform_id" id="editPlatformId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Platform Name</label>
                                <input type="text" class="form-control" name="name" id="editPlatformName" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Type</label>
                                <select class="form-control" name="type" id="editPlatformType" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($platform_types as $key => $label): ?>
                                        <option value="<?= $key ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>URL</label>
                        <input type="url" class="form-control" name="url" id="editPlatformUrl" placeholder="https://example.com">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" id="editPlatformDescription" rows="4" placeholder="Enter platform description..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="is_active" id="editPlatformStatus">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Logo</label>
                                <input type="file" class="form-control" name="logo" accept="image/*">
                                <small class="form-text text-muted">Upload new logo (JPG, PNG, GIF - Max 2MB)</small>
                            </div>
                        </div>
                    </div>
                    <div id="currentLogo" class="form-group" style="display: none;">
                        <label>Current Logo</label>
                        <div>
                            <img id="currentLogoImg" src="" alt="Current Logo" style="max-width: 100px; max-height: 100px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Platform</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Platform Details Modal -->
<div class="modal fade" id="platformDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Platform Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h4 id="detailsPlatformName"></h4>
                        <p><strong>URL:</strong> <a id="detailsPlatformUrl" href="#" target="_blank"></a></p>
                        <p><strong>Type:</strong> <span id="detailsPlatformType"></span></p>
                        <p><strong>Status:</strong> <span id="detailsPlatformStatus"></span></p>
                    </div>
                    <div class="col-md-4">
                        <div id="detailsPlatformLogo" style="display: none;">
                            <img id="detailsPlatformLogoImg" src="" alt="Platform Logo" style="max-width: 100%; height: auto;">
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h5>Total Reported Loss</h5>
                                <h3 id="detailsReportedLoss">$0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h5>Total Recovered</h5>
                                <h3 id="detailsRecovered">$0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h6>Recovery Rate</h6>
                    <div class="progress">
                        <div id="detailsRecoveryProgress" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small id="detailsRecoveryText" class="form-text text-muted">0% recovery rate</small>
                </div>
                
                <h6>Description</h6>
                <div class="card mb-4">
                    <div class="card-body" id="detailsPlatformDescription"></div>
                </div>
                
                <h6>Recent Cases</h6>
                <div class="table-responsive mb-4">
                    <table class="table" id="detailsCasesTable">
                        <thead>
                            <tr>
                                <th>Case #</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editPlatformFromDetails">Edit Platform</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const platformsTable = $('#platformsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_platforms.php',
            type: 'POST'
        },
        columns: [
            { 
                data: 'name',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex align-items-center">
                            ${row.logo ? `<img src="${row.logo}" alt="Logo" style="width: 32px; height: 32px; margin-right: 10px; border-radius: 4px;">` : ''}
                            <strong>${data}</strong>
                        </div>
                    `;
                }
            },
            { 
                data: 'url',
                render: function(data) {
                    return data ? `<a href="${data}" target="_blank" title="Visit Platform">${data}</a>` : 'N/A';
                }
            },
            { 
                data: 'type',
                render: function(data) {
                    const typeLabels = {
                        'crypto': 'Cryptocurrency',
                        'forex': 'Forex Trading',
                        'investment': 'Investment',
                        'dating': 'Dating Scam',
                        'tax': 'Tax Scam',
                        'other': 'Other'
                    };
                    const typeClass = {
                        'crypto': 'warning',
                        'forex': 'info',
                        'investment': 'primary',
                        'dating': 'danger',
                        'tax': 'dark',
                        'other': 'secondary'
                    }[data] || 'secondary';
                    return `<span class="badge badge-${typeClass}">${typeLabels[data] || data}</span>`;
                }
            },
            { 
                data: 'description',
                render: function(data) {
                    if (!data) return 'N/A';
                    return data.length > 50 ? data.substring(0, 50) + '...' : data;
                }
            },
            { 
                data: 'total_reported_loss',
                render: function(data) {
                    return '$' + parseFloat(data || 0).toFixed(2);
                }
            },
            { 
                data: 'total_recovered',
                render: function(data, type, row) {
                    const recovered = parseFloat(data || 0);
                    const reported = parseFloat(row.total_reported_loss || 0);
                    const percentage = reported > 0 ? (recovered / reported * 100) : 0;
                    
                    return `
                        <div>
                            <strong>$${recovered.toFixed(2)}</strong>
                            ${reported > 0 ? `
                                <div class="progress" style="height: 5px; margin: 5px 0;">
                                    <div class="progress-bar bg-success" 
                                         role="progressbar" 
                                         style="width: ${percentage}%">
                                    </div>
                                </div>
                                <small>${percentage.toFixed(1)}%</small>
                            ` : ''}
                        </div>
                    `;
                }
            },
            { 
                data: 'is_active',
                render: function(data) {
                    return data == 1 
                        ? '<span class="badge badge-success">Active</span>' 
                        : '<span class="badge badge-secondary">Inactive</span>';
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
                            <button class="btn btn-sm btn-info view-platform" 
                                    data-id="${data}" 
                                    title="View Details">
                                <i class="anticon anticon-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary edit-platform" 
                                    data-id="${data}" 
                                    title="Edit Platform">
                                <i class="anticon anticon-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-platform" 
                                    data-id="${data}" 
                                    data-name="${row.name}"
                                    title="Delete Platform">
                                <i class="anticon anticon-delete"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });

    // Add Platform Form Submission
    $('#addPlatformForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'admin_ajax/add_platform.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#addPlatformForm button[type="submit"]').prop('disabled', true)
                    .html('<i class="anticon anticon-loading anticon-spin"></i> Adding...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#addPlatformModal').modal('hide');
                    platformsTable.ajax.reload();
                    $('#addPlatformForm')[0].reset();
                } else {
                    toastr.error(response.message);
                    if (response.error) {
                        console.error("Error:", response.error);
                    }
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Failed to add platform");
                console.error("AJAX Error:", status, error);
            },
            complete: function() {
                $('#addPlatformForm button[type="submit"]').prop('disabled', false).html('Add Platform');
            }
        });
    });

    // View Platform Details
    $('#platformsTable').on('click', '.view-platform', function() {
        const platformId = $(this).data('id');
        loadPlatformDetails(platformId);
    });

    // Edit Platform - Load Data
    $('#platformsTable').on('click', '.edit-platform', function() {
        const platformId = $(this).data('id');
        loadPlatformForEdit(platformId);
    });

    // Edit from details modal
    $('#editPlatformFromDetails').click(function() {
        const platformId = $(this).data('id');
        $('#platformDetailsModal').modal('hide');
        setTimeout(() => {
            loadPlatformForEdit(platformId);
        }, 300);
    });

    // Edit Platform Form Submission
    $('#editPlatformForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'admin_ajax/update_platform.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#editPlatformForm button[type="submit"]').prop('disabled', true)
                    .html('<i class="anticon anticon-loading anticon-spin"></i> Updating...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#editPlatformModal').modal('hide');
                    platformsTable.ajax.reload();
                } else {
                    toastr.error(response.message);
                    if (response.error) {
                        console.error("Error:", response.error);
                    }
                }
            },
            error: function(xhr, status, error) {
                toastr.error("Failed to update platform");
                console.error("AJAX Error:", status, error);
            },
            complete: function() {
                $('#editPlatformForm button[type="submit"]').prop('disabled', false).html('Update Platform');
            }
        });
    });

    // Delete Platform
    $('#platformsTable').on('click', '.delete-platform', function() {
        const platformId = $(this).data('id');
        const platformName = $(this).data('name');
        
        if (confirm(`Are you sure you want to delete "${platformName}"? This action cannot be undone.`)) {
            $.ajax({
                url: 'admin_ajax/delete_platform.php',
                type: 'POST',
                data: { platform_id: platformId },
                beforeSend: function() {
                    $(this).prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        platformsTable.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error("Failed to delete platform");
                    console.error("AJAX Error:", status, error);
                },
                complete: function() {
                    $(this).prop('disabled', false);
                }
            });
        }
    });

    // Load platform details function
    function loadPlatformDetails(platformId) {
        $.get('admin_ajax/get_platform.php?id=' + platformId, function(response) {
            if (response.success) {
                const platform = response.platform;
                const reported = parseFloat(platform.total_reported_loss || 0);
                const recovered = parseFloat(platform.total_recovered || 0);
                const recoveryRate = reported > 0 ? (recovered / reported * 100) : 0;
                
                const typeLabels = {
                    'crypto': 'Cryptocurrency',
                    'forex': 'Forex Trading',
                    'investment': 'Investment',
                    'dating': 'Dating Scam',
                    'tax': 'Tax Scam',
                    'other': 'Other'
                };
                
                $('#detailsPlatformName').text(platform.name);
                $('#detailsPlatformUrl').attr('href', platform.url || '#').text(platform.url || 'N/A');
                $('#detailsPlatformType').text(typeLabels[platform.type] || platform.type);
                $('#detailsPlatformStatus').html(
                    platform.is_active == 1 
                        ? '<span class="badge badge-success">Active</span>' 
                        : '<span class="badge badge-secondary">Inactive</span>'
                );
                $('#detailsReportedLoss').text('$' + reported.toFixed(2));
                $('#detailsRecovered').text('$' + recovered.toFixed(2));
                $('#detailsRecoveryProgress').css('width', recoveryRate + '%').text(recoveryRate.toFixed(1) + '%');
                $('#detailsRecoveryText').text(`${recoveryRate.toFixed(1)}% recovery rate`);
                $('#detailsPlatformDescription').text(platform.description || 'No description available');
                
                // Handle logo
                if (platform.logo) {
                    $('#detailsPlatformLogo').show();
                    $('#detailsPlatformLogoImg').attr('src', platform.logo);
                } else {
                    $('#detailsPlatformLogo').hide();
                }
                
                // Set platform ID for edit button
                $('#editPlatformFromDetails').data('id', platformId);
                
                // Load recent cases
                const casesTable = $('#detailsCasesTable tbody');
                casesTable.empty();
                
                if (response.recent_cases && response.recent_cases.length > 0) {
                    response.recent_cases.forEach(caseData => {
                        const statusClass = getStatusClass(caseData.status);
                        casesTable.append(`
                            <tr>
                                <td>${caseData.case_number}</td>
                                <td>${caseData.user_first_name} ${caseData.user_last_name}</td>
                                <td>$${parseFloat(caseData.reported_amount).toFixed(2)}</td>
                                <td><span class="badge badge-${statusClass}">${caseData.status.replace(/_/g, ' ')}</span></td>
                                <td>${new Date(caseData.created_at).toLocaleDateString()}</td>
                            </tr>
                        `);
                    });
                } else {
                    casesTable.append('<tr><td colspan="5">No cases found for this platform</td></tr>');
                }
                
                $('#platformDetailsModal').modal('show');
            } else {
                toastr.error(response.message);
            }
        });
    }

    // Load platform for editing function
    function loadPlatformForEdit(platformId) {
        $.get('admin_ajax/get_platform.php?id=' + platformId, function(response) {
            if (response.success) {
                const platform = response.platform;
                
                $('#editPlatformId').val(platform.id);
                $('#editPlatformName').val(platform.name);
                $('#editPlatformType').val(platform.type);
                $('#editPlatformUrl').val(platform.url);
                $('#editPlatformDescription').val(platform.description);
                $('#editPlatformStatus').val(platform.is_active);
                
                // Handle current logo
                if (platform.logo) {
                    $('#currentLogo').show();
                    $('#currentLogoImg').attr('src', platform.logo);
                } else {
                    $('#currentLogo').hide();
                }
                
                $('#editPlatformModal').modal('show');
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