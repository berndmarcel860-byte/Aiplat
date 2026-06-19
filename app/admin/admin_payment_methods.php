<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2>Payment Methods</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Payment Methods</span>
            </nav>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row" id="pm-stats-row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-icon avatar-lg avatar-blue rounded mr-3">
                        <i class="anticon anticon-credit-card font-size-24"></i>
                    </div>
                    <div>
                        <p class="m-b-0 text-muted">Total Methods</p>
                        <h4 class="m-b-0" id="stat-total">—</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-icon avatar-lg avatar-green rounded mr-3">
                        <i class="anticon anticon-check-circle font-size-24"></i>
                    </div>
                    <div>
                        <p class="m-b-0 text-muted">Active</p>
                        <h4 class="m-b-0" id="stat-active">—</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-icon avatar-lg avatar-red rounded mr-3">
                        <i class="anticon anticon-close-circle font-size-24"></i>
                    </div>
                    <div>
                        <p class="m-b-0 text-muted">Inactive</p>
                        <h4 class="m-b-0" id="stat-inactive">—</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-icon avatar-lg avatar-gold rounded mr-3">
                        <i class="anticon anticon-bitcoin font-size-24"></i>
                    </div>
                    <div>
                        <p class="m-b-0 text-muted">Crypto Methods</p>
                        <h4 class="m-b-0" id="stat-crypto">—</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="m-b-0">Payment Methods</h5>
                <div class="d-flex">
                    <button class="btn btn-info mr-2" id="refreshPayment_Methods">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addPayment_MethodsModal">
                        <i class="anticon anticon-plus"></i> Add New
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="payment_methodsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Capabilities</th>
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="addPayment_MethodsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Payment Method</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="addPayment_MethodsForm">
                <input type="hidden" name="id" value="">
                <div class="modal-body">

                    <!-- Basic Info -->
                    <h6 class="font-weight-semibold text-muted mb-3"><i class="anticon anticon-info-circle mr-1"></i> Basic Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Method Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="method_name" placeholder="e.g. Bank Transfer" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Method Code</label>
                                <input type="text" class="form-control" name="method_code" placeholder="bank_transfer">
                                <small class="text-muted">Auto-generated if left empty.</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Type</label>
                                <div class="d-flex align-items-center mt-1">
                                    <div class="custom-control custom-checkbox mr-4">
                                        <input type="checkbox" class="custom-control-input" id="chk_is_crypto" name="is_crypto" value="1">
                                        <label class="custom-control-label" for="chk_is_crypto">Cryptocurrency</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Capabilities -->
                    <h6 class="font-weight-semibold text-muted mb-3 mt-2"><i class="anticon anticon-setting mr-1"></i> Capabilities</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="chk_allows_deposit" name="allows_deposit" value="1">
                                    <label class="custom-control-label" for="chk_allows_deposit">Allows Deposits</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="chk_allows_withdrawal" name="allows_withdrawal" value="1">
                                    <label class="custom-control-label" for="chk_allows_withdrawal">Allows Withdrawals</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Amount Limits -->
                    <h6 class="font-weight-semibold text-muted mb-3 mt-2"><i class="anticon anticon-dollar mr-1"></i> Amount Limits</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Minimum Amount</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="min_amount" placeholder="10.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Maximum Amount</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="max_amount" placeholder="Leave empty for no limit">
                            </div>
                        </div>
                    </div>

                    <!-- Bank Details -->
                    <h6 class="font-weight-semibold text-muted mb-3 mt-2"><i class="anticon anticon-bank mr-1"></i> Bank Details</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bank Name</label>
                                <input type="text" class="form-control" name="bank_name" placeholder="e.g. Deutsche Bank">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Account Number / IBAN</label>
                                <input type="text" class="form-control" name="account_number" placeholder="e.g. DE12 3456 7890">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Routing / BIC / SWIFT</label>
                                <input type="text" class="form-control" name="routing_number" placeholder="e.g. DEUTDEDB">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Wallet Address (Crypto)</label>
                                <input type="text" class="form-control" name="wallet_address" placeholder="e.g. bc1q...">
                            </div>
                        </div>
                    </div>

                    <!-- Instructions & Details -->
                    <h6 class="font-weight-semibold text-muted mb-3 mt-2"><i class="anticon anticon-file-text mr-1"></i> Instructions &amp; Details</h6>
                    <div class="form-group">
                        <label>Payment Details (shown to users)</label>
                        <textarea class="form-control" name="payment_details" rows="3" placeholder="Bank Name: ...\nIBAN: ...\nBIC: ..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Internal Instructions</label>
                        <textarea class="form-control" name="instructions" rows="2" placeholder="Notes for staff or users about this payment method"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"><i class="anticon anticon-save mr-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewPayment_MethodsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="anticon anticon-credit-card mr-1"></i> Payment Method Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body" id="paymentMethodDetailsContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editFromViewBtn"><i class="anticon anticon-edit mr-1"></i> Edit</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePayment_MethodModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="anticon anticon-delete mr-1"></i> Delete Payment Method</h5>
                <button type="button" class="close" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteMethodName"></strong>? This action cannot be undone.</p>
                <input type="hidden" id="deleteMethodId" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn"><i class="anticon anticon-delete mr-1"></i> Delete</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const endpoint = 'admin_ajax/get_payment_methods.php';
    let currentViewId = null;

    function escapeHtml(text) {
        return $('<div>').text(text == null ? '' : String(text)).html();
    }

    function yesNo(val) {
        return String(val) === '1'
            ? '<span class="badge badge-success">YES</span>'
            : '<span class="badge badge-secondary">NO</span>';
    }

    function resetMethodForm() {
        $('#addPayment_MethodsForm')[0].reset();
        $('#addPayment_MethodsForm input[name="id"]').val('');
        $('#chk_allows_deposit').prop('checked', false);
        $('#chk_allows_withdrawal').prop('checked', false);
        $('#chk_is_crypto').prop('checked', false);
        $('#addPayment_MethodsModal .modal-title').text('Add New Payment Method');
    }

    function loadStats() {
        $.post(endpoint, { action: 'stats' }, function(r) {
            if (r && r.success) {
                $('#stat-total').text(r.total);
                $('#stat-active').text(r.active);
                $('#stat-inactive').text(r.inactive);
                $('#stat-crypto').text(r.crypto);
            }
        }, 'json');
    }

    loadStats();

    // Initialize DataTable
    const payment_methodsTable = $('#payment_methodsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: endpoint,
            type: 'POST',
            data: function(d) { d.action = 'list'; }
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'id', width: '50px' },
            { data: 'method_code', render: function(data) { return '<code>' + escapeHtml(data || '—') + '</code>'; } },
            { data: 'method_name', render: function(data) { return escapeHtml(data || '—'); } },
            {
                data: 'is_crypto',
                orderable: false,
                render: function(data) {
                    return String(data) === '1'
                        ? '<span class="badge badge-warning"><i class="anticon anticon-bitcoin"></i> Crypto</span>'
                        : '<span class="badge badge-blue"><i class="anticon anticon-bank"></i> Fiat</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    const dep = String(row.allows_deposit) === '1'
                        ? '<span class="badge badge-success mr-1" title="Deposits enabled">↓ Dep</span>'
                        : '<span class="badge badge-light mr-1" title="Deposits disabled">↓ Dep</span>';
                    const wth = String(row.allows_withdrawal) === '1'
                        ? '<span class="badge badge-info" title="Withdrawals enabled">↑ Wth</span>'
                        : '<span class="badge badge-light" title="Withdrawals disabled">↑ Wth</span>';
                    return dep + wth;
                }
            },
            {
                data: 'status',
                render: function(data, type, row) {
                    const isActive = (data || '').toLowerCase() === 'active';
                    const statusClass = isActive ? 'success' : 'secondary';
                    const label = isActive ? 'ACTIVE' : 'INACTIVE';
                    return `<span class="badge badge-${statusClass} toggle-status" data-id="${escapeHtml(row.id)}" data-active="${isActive ? 1 : 0}" style="cursor:pointer" title="Click to toggle">${label}</span>`;
                }
            },
            {
                data: 'id',
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info view-item" data-id="${data}" title="View Details">
                                <i class="anticon anticon-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary edit-item" data-id="${data}" title="Edit">
                                <i class="anticon anticon-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-item" data-id="${data}" data-name="${escapeHtml(row.method_name)}" title="Delete">
                                <i class="anticon anticon-delete"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });

    function loadMethodDetails(id, onSuccess) {
        $.post(endpoint, { action: 'get', id: id }, function(response) {
            if (response && response.success && response.method) {
                onSuccess(response.method);
            } else {
                toastr.error(response && response.message ? response.message : 'Failed to load payment method');
            }
        }, 'json').fail(function() {
            toastr.error('Failed to load payment method');
        });
    }

    $('#addPayment_MethodsModal').on('hidden.bs.modal', function() {
        resetMethodForm();
    });

    $('#addPayment_MethodsForm').on('submit', function(e) {
        e.preventDefault();
        const payload = $(this).serialize() + '&action=save';

        $.post(endpoint, payload, function(response) {
            if (response && response.success) {
                toastr.success(response.message || 'Saved');
                $('#addPayment_MethodsModal').modal('hide');
                payment_methodsTable.ajax.reload(null, false);
                loadStats();
            } else {
                toastr.error(response && response.message ? response.message : 'Failed to save payment method');
            }
        }, 'json').fail(function() {
            toastr.error('Failed to save payment method');
        });
    });

    // View
    $('#payment_methodsTable').on('click', '.view-item', function() {
        const id = $(this).data('id');
        currentViewId = id;
        loadMethodDetails(id, function(m) {
            const statusLabel = ((m.status || '').toLowerCase() === 'active' || String(m.is_active) === '1') ? 'ACTIVE' : 'INACTIVE';
            const statusBadge = statusLabel === 'ACTIVE'
                ? '<span class="badge badge-success">ACTIVE</span>'
                : '<span class="badge badge-secondary">INACTIVE</span>';
            const html = `
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr><th class="text-muted" style="width:40%">ID</th><td>${escapeHtml(m.id)}</td></tr>
                            <tr><th class="text-muted">Code</th><td><code>${escapeHtml(m.method_code || '—')}</code></td></tr>
                            <tr><th class="text-muted">Name</th><td><strong>${escapeHtml(m.method_name || '—')}</strong></td></tr>
                            <tr><th class="text-muted">Status</th><td>${statusBadge}</td></tr>
                            <tr><th class="text-muted">Crypto</th><td>${yesNo(m.is_crypto)}</td></tr>
                            <tr><th class="text-muted">Allows Deposit</th><td>${yesNo(m.allows_deposit)}</td></tr>
                            <tr><th class="text-muted">Allows Withdrawal</th><td>${yesNo(m.allows_withdrawal)}</td></tr>
                            <tr><th class="text-muted">Min Amount</th><td>${escapeHtml(m.min_amount || '—')}</td></tr>
                            <tr><th class="text-muted">Max Amount</th><td>${escapeHtml(m.max_amount || '—')}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr><th class="text-muted" style="width:40%">Bank Name</th><td>${escapeHtml(m.bank_name || '—')}</td></tr>
                            <tr><th class="text-muted">Account / IBAN</th><td>${escapeHtml(m.account_number || '—')}</td></tr>
                            <tr><th class="text-muted">Routing / BIC</th><td>${escapeHtml(m.routing_number || '—')}</td></tr>
                            <tr><th class="text-muted">Wallet Address</th><td style="word-break:break-all">${escapeHtml(m.wallet_address || '—')}</td></tr>
                            <tr><th class="text-muted">Created At</th><td>${escapeHtml(m.created_at || '—')}</td></tr>
                            <tr><th class="text-muted">Updated At</th><td>${escapeHtml(m.updated_at || '—')}</td></tr>
                        </table>
                    </div>
                </div>
                ${m.payment_details ? `<div class="mt-2"><strong class="text-muted">Payment Details:</strong><pre class="bg-light p-2 mt-1 rounded" style="white-space:pre-wrap">${escapeHtml(m.payment_details)}</pre></div>` : ''}
                ${m.instructions ? `<div class="mt-2"><strong class="text-muted">Instructions:</strong><p class="mt-1">${escapeHtml(m.instructions)}</p></div>` : ''}
            `;
            $('#paymentMethodDetailsContent').html(html);
            $('#viewPayment_MethodsModal').modal('show');
        });
    });

    // Edit from view modal
    $('#editFromViewBtn').on('click', function() {
        if (!currentViewId) return;
        $('#viewPayment_MethodsModal').modal('hide');
        loadMethodDetails(currentViewId, function(method) {
            populateEditForm(method);
            $('#addPayment_MethodsModal').modal('show');
        });
    });

    // Edit
    $('#payment_methodsTable').on('click', '.edit-item', function() {
        const id = $(this).data('id');
        loadMethodDetails(id, function(method) {
            populateEditForm(method);
            $('#addPayment_MethodsModal').modal('show');
        });
    });

    function populateEditForm(method) {
        $('#addPayment_MethodsModal .modal-title').text('Edit Payment Method');
        $('#addPayment_MethodsForm input[name="id"]').val(method.id || '');
        $('#addPayment_MethodsForm input[name="method_code"]').val(method.method_code || '');
        $('#addPayment_MethodsForm input[name="method_name"]').val(method.method_name || '');
        $('#addPayment_MethodsForm select[name="status"]').val((method.status || 'active').toLowerCase());
        $('#addPayment_MethodsForm input[name="bank_name"]').val(method.bank_name || '');
        $('#addPayment_MethodsForm input[name="account_number"]').val(method.account_number || '');
        $('#addPayment_MethodsForm input[name="routing_number"]').val(method.routing_number || '');
        $('#addPayment_MethodsForm input[name="wallet_address"]').val(method.wallet_address || '');
        $('#addPayment_MethodsForm textarea[name="payment_details"]').val(method.payment_details || '');
        $('#addPayment_MethodsForm textarea[name="instructions"]').val(method.instructions || '');
        $('#addPayment_MethodsForm input[name="min_amount"]').val(method.min_amount || '');
        $('#addPayment_MethodsForm input[name="max_amount"]').val(method.max_amount || '');
        $('#chk_allows_deposit').prop('checked', String(method.allows_deposit) === '1');
        $('#chk_allows_withdrawal').prop('checked', String(method.allows_withdrawal) === '1');
        $('#chk_is_crypto').prop('checked', String(method.is_crypto) === '1');
    }

    // Delete — open confirm modal
    $('#payment_methodsTable').on('click', '.delete-item', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        $('#deleteMethodId').val(id);
        $('#deleteMethodName').text(name || 'this method');
        $('#deletePayment_MethodModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        const id = $('#deleteMethodId').val();
        if (!id) return;
        $(this).prop('disabled', true).html('<i class="anticon anticon-loading"></i> Deleting...');

        $.post(endpoint, { action: 'delete', id: id }, function(response) {
            $('#deletePayment_MethodModal').modal('hide');
            $('#confirmDeleteBtn').prop('disabled', false).html('<i class="anticon anticon-delete mr-1"></i> Delete');
            if (response && response.success) {
                toastr.success(response.message || 'Deleted');
                payment_methodsTable.ajax.reload(null, false);
                loadStats();
            } else {
                toastr.error(response && response.message ? response.message : 'Failed to delete payment method');
            }
        }, 'json').fail(function() {
            $('#deletePayment_MethodModal').modal('hide');
            $('#confirmDeleteBtn').prop('disabled', false).html('<i class="anticon anticon-delete mr-1"></i> Delete');
            toastr.error('Failed to delete payment method');
        });
    });

    // Quick status toggle
    $('#payment_methodsTable').on('click', '.toggle-status', function() {
        const id = $(this).data('id');
        const currentActive = $(this).data('active');
        const newStatus = currentActive == 1 ? 'inactive' : 'active';

        $.post(endpoint, { action: 'toggle', id: id, status: newStatus }, function(response) {
            if (response && response.success) {
                toastr.success(response.message || 'Status updated');
                payment_methodsTable.ajax.reload(null, false);
                loadStats();
            } else {
                toastr.error(response && response.message ? response.message : 'Failed to update status');
            }
        }, 'json').fail(function() {
            toastr.error('Failed to update status');
        });
    });

    // Refresh button
    $('#refreshPayment_Methods').click(function() {
        payment_methodsTable.ajax.reload(null, false);
        loadStats();
    });
});
</script>
