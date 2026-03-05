<?php
// admin_user_packages.php
// User Packages Management - View, Add, Edit, Delete user package assignments

require_once 'admin_header.php';

// Get list of packages for dropdown
try {
    $packagesStmt = $pdo->query("SELECT id, name, price, duration_days FROM packages ORDER BY name");
    $packages = $packagesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $packages = [];
}

// Get list of users for dropdown
try {
    $usersStmt = $pdo->query("SELECT id, first_name, last_name, email FROM users ORDER BY first_name, last_name");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">User Packages Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">User Packages</span>
            </nav>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-blue">
                            <i class="anticon anticon-gift"></i>
                        </div>
                        <div class="m-l-15">
                            <h2 class="m-b-0" id="totalPackages">0</h2>
                            <p class="m-b-0 text-muted">Total Assignments</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-green">
                            <i class="anticon anticon-check-circle"></i>
                        </div>
                        <div class="m-l-15">
                            <h2 class="m-b-0" id="activePackages">0</h2>
                            <p class="m-b-0 text-muted">Active</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-gold">
                            <i class="anticon anticon-clock-circle"></i>
                        </div>
                        <div class="m-l-15">
                            <h2 class="m-b-0" id="pendingPackages">0</h2>
                            <p class="m-b-0 text-muted">Pending</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-red">
                            <i class="anticon anticon-close-circle"></i>
                        </div>
                        <div class="m-l-15">
                            <h2 class="m-b-0" id="expiredPackages">0</h2>
                            <p class="m-b-0 text-muted">Expired</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center m-b-20">
                <h5>User Package Assignments</h5>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addPackageModal">
                    <i class="anticon anticon-plus"></i> Add Package Assignment
                </button>
            </div>
            
            <!-- Filters -->
            <div class="row m-b-20">
                <div class="col-md-3">
                    <select id="filterStatus" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="expired">Expired</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterPackage" class="form-control">
                        <option value="">All Packages</option>
                        <?php foreach ($packages as $pkg): ?>
                        <option value="<?= $pkg['id'] ?>"><?= htmlspecialchars($pkg['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="userPackagesTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Package</th>
                            <th>Price</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Package Modal -->
<div class="modal fade" id="addPackageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Package Assignment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addPackageForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>User <span class="text-danger">*</span></label>
                                <select name="user_id" class="form-control select2" required>
                                    <option value="">Select User</option>
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Package <span class="text-danger">*</span></label>
                                <select name="package_id" class="form-control" required id="addPackageSelect">
                                    <option value="">Select Package</option>
                                    <?php foreach ($packages as $pkg): ?>
                                    <option value="<?= $pkg['id'] ?>" data-duration="<?= $pkg['duration_days'] ?>" data-price="<?= $pkg['price'] ?>">
                                        <?= htmlspecialchars($pkg['name']) ?> (€<?= number_format($pkg['price'], 2) ?> - <?= $pkg['duration_days'] ?> days)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="start_date" class="form-control" required id="addStartDate">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="end_date" class="form-control" required id="addEndDate">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-control" required>
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="expired">Expired</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="anticon anticon-plus"></i> Add Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Package Modal -->
<div class="modal fade" id="editPackageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Package Assignment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editPackageForm">
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>User</label>
                                <input type="text" class="form-control" id="editUserDisplay" readonly>
                                <input type="hidden" name="user_id" id="editUserId">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Package <span class="text-danger">*</span></label>
                                <select name="package_id" class="form-control" required id="editPackageSelect">
                                    <?php foreach ($packages as $pkg): ?>
                                    <option value="<?= $pkg['id'] ?>" data-duration="<?= $pkg['duration_days'] ?>">
                                        <?= htmlspecialchars($pkg['name']) ?> (€<?= number_format($pkg['price'], 2) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="start_date" class="form-control" required id="editStartDate">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="end_date" class="form-control" required id="editEndDate">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-control" required id="editStatus">
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="expired">Expired</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="anticon anticon-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewPackageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Package Assignment Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewPackageContent">
                <!-- Content loaded via AJAX -->
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
    var table = $('#userPackagesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_user_packages.php',
            type: 'POST',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.package_id = $('#filterPackage').val();
            }
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'id' },
            { 
                data: null,
                render: function(data) {
                    return `<strong>${data.user_name}</strong><br><small class="text-muted">${data.user_email}</small>`;
                }
            },
            { 
                data: 'package_name',
                render: function(data, type, row) {
                    return `<span class="font-weight-bold">${data}</span><br><small class="text-muted">${row.duration_days} days</small>`;
                }
            },
            { 
                data: 'package_price',
                render: function(data) {
                    return '€' + parseFloat(data).toFixed(2);
                }
            },
            { 
                data: 'start_date',
                render: function(data) {
                    return new Date(data).toLocaleDateString('de-DE', {day:'2-digit', month:'2-digit', year:'numeric'});
                }
            },
            { 
                data: 'end_date',
                render: function(data) {
                    if (!data) return '<span class="text-muted">—</span>';
                    return new Date(data).toLocaleDateString('de-DE', {day:'2-digit', month:'2-digit', year:'numeric'});
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    var badges = {
                        'active': 'success',
                        'pending': 'warning',
                        'expired': 'danger',
                        'cancelled': 'secondary'
                    };
                    return `<span class="badge badge-${badges[data] || 'secondary'}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info view-btn" data-id="${data.id}" title="View Details">
                                <i class="anticon anticon-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${data.id}" title="Edit">
                                <i class="anticon anticon-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${data.id}" title="Delete">
                                <i class="anticon anticon-delete"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });

    // Filter handlers
    $('#filterStatus, #filterPackage').change(function() {
        table.ajax.reload();
    });

    // Load stats
    function loadStats() {
        $.get('admin_ajax/get_user_packages_stats.php', function(response) {
            if (response.success) {
                $('#totalPackages').text(response.data.total);
                $('#activePackages').text(response.data.active);
                $('#pendingPackages').text(response.data.pending);
                $('#expiredPackages').text(response.data.expired);
            }
        });
    }
    loadStats();

    // Auto-calculate end date when package/start date changes
    function calculateEndDate(startDateInput, endDateInput, durationDays) {
        if (startDateInput.val() && durationDays) {
            var startDate = new Date(startDateInput.val());
            startDate.setDate(startDate.getDate() + parseInt(durationDays));
            endDateInput.val(startDate.toISOString().slice(0, 16));
        }
    }

    $('#addPackageSelect').change(function() {
        var duration = $(this).find(':selected').data('duration');
        calculateEndDate($('#addStartDate'), $('#addEndDate'), duration);
    });

    $('#addStartDate').change(function() {
        var duration = $('#addPackageSelect').find(':selected').data('duration');
        calculateEndDate($('#addStartDate'), $('#addEndDate'), duration);
    });

    // Set default start date to now
    $('#addPackageModal').on('show.bs.modal', function() {
        var now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        $('#addStartDate').val(now.toISOString().slice(0, 16));
    });

    // Add Package Form
    $('#addPackageForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'admin_ajax/add_user_package.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addPackageModal').modal('hide');
                    $('#addPackageForm')[0].reset();
                    table.ajax.reload();
                    loadStats();
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('An error occurred. Please try again.');
            }
        });
    });

    // View Details
    $(document).on('click', '.view-btn', function() {
        var id = $(this).data('id');
        $.get('admin_ajax/get_user_package.php', { id: id }, function(response) {
            if (response.success) {
                var d = response.data;
                var html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">User Information</h6>
                            <table class="table table-bordered">
                                <tr><th>Name</th><td>${d.user_name}</td></tr>
                                <tr><th>Email</th><td>${d.user_email}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Package Information</h6>
                            <table class="table table-bordered">
                                <tr><th>Package</th><td>${d.package_name}</td></tr>
                                <tr><th>Price</th><td>€${parseFloat(d.package_price).toFixed(2)}</td></tr>
                                <tr><th>Duration</th><td>${d.duration_days} days</td></tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="text-muted">Assignment Details</h6>
                            <table class="table table-bordered">
                                <tr><th>Start Date</th><td>${new Date(d.start_date).toLocaleString('de-DE')}</td></tr>
                                <tr><th>End Date</th><td>${d.end_date ? new Date(d.end_date).toLocaleString('de-DE') : '—'}</td></tr>
                                <tr><th>Status</th><td><span class="badge badge-${d.status === 'active' ? 'success' : (d.status === 'pending' ? 'warning' : (d.status === 'expired' ? 'danger' : 'secondary'))}">${d.status}</span></td></tr>
                                <tr><th>Created</th><td>${new Date(d.created_at).toLocaleString('de-DE')}</td></tr>
                                <tr><th>Updated</th><td>${new Date(d.updated_at).toLocaleString('de-DE')}</td></tr>
                            </table>
                        </div>
                    </div>
                `;
                $('#viewPackageContent').html(html);
                $('#viewPackageModal').modal('show');
            } else {
                toastr.error(response.message);
            }
        });
    });

    // Edit Package
    $(document).on('click', '.edit-btn', function() {
        var id = $(this).data('id');
        $.get('admin_ajax/get_user_package.php', { id: id }, function(response) {
            if (response.success) {
                var d = response.data;
                $('#editId').val(d.id);
                $('#editUserId').val(d.user_id);
                $('#editUserDisplay').val(d.user_name + ' (' + d.user_email + ')');
                $('#editPackageSelect').val(d.package_id);
                $('#editStartDate').val(d.start_date.replace(' ', 'T').slice(0, 16));
                $('#editEndDate').val(d.end_date ? d.end_date.replace(' ', 'T').slice(0, 16) : '');
                $('#editStatus').val(d.status);
                $('#editPackageModal').modal('show');
            } else {
                toastr.error(response.message);
            }
        });
    });

    // Edit Form Submit
    $('#editPackageForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'admin_ajax/update_user_package.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editPackageModal').modal('hide');
                    table.ajax.reload();
                    loadStats();
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('An error occurred. Please try again.');
            }
        });
    });

    // Delete Package
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        if (confirm('Are you sure you want to delete this package assignment?')) {
            $.ajax({
                url: 'admin_ajax/delete_user_package.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        table.ajax.reload();
                        loadStats();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('An error occurred. Please try again.');
                }
            });
        }
    });
});
</script>

