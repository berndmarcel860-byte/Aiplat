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
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Payment Methods</h5>
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
                            <th>Status</th>
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="addPayment_MethodsModal">
    <div class="modal-dialog">
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
                    <div class="form-group">
                        <label>Method Code</label>
                        <input type="text" class="form-control" name="method_code" placeholder="bank_transfer">
                        <small class="text-muted">Optional. Will be generated automatically if left empty.</small>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" name="method_name" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewPayment_MethodsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Method Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body" id="paymentMethodDetailsContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>


<script>
$(document).ready(function() {
    const endpoint = 'admin_ajax/get_payment_methods.php';

    function escapeHtml(text) {
        return $('<div>').text(text == null ? '' : text).html();
    }

    function formatDate(dateValue) {
        if (!dateValue) return '—';
        const dateObj = new Date(dateValue);
        return isNaN(dateObj.getTime()) ? '—' : dateObj.toLocaleString();
    }

    function resetMethodForm() {
        $('#addPayment_MethodsForm')[0].reset();
        $('#addPayment_MethodsForm input[name="id"]').val('');
        $('#addPayment_MethodsModal .modal-title').text('Add New Payment Method');
    }

    // Initialize DataTable
    const payment_methodsTable = $('#payment_methodsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: endpoint,
            type: 'POST',
            data: function(d) {
                d.action = 'list';
            }
        },
        order: [[4, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'method_code', render: function(data) { return escapeHtml(data || '—'); } },
            { data: 'method_name', render: function(data) { return escapeHtml(data || '—'); } },
            { 
                data: 'status',
                render: function(data) {
                    const normalized = (data || 'inactive').toLowerCase();
                    const statusClass = normalized === 'active' ? 'success' : 'secondary';
                    return `<span class="badge badge-${statusClass}">${escapeHtml(normalized.toUpperCase())}</span>`;
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return formatDate(data);
                }
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info view-item" 
                                    data-id="${data}" 
                                    title="View Details">
                                <i class="anticon anticon-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary edit-item" 
                                    data-id="${data}" 
                                    title="Edit">
                                <i class="anticon anticon-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-item" 
                                    data-id="${data}" 
                                    title="Delete">
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
            } else {
                toastr.error(response && response.message ? response.message : 'Failed to save payment method');
            }
        }, 'json').fail(function() {
            toastr.error('Failed to save payment method');
        });
    });

    $('#payment_methodsTable').on('click', '.view-item', function() {
        const id = $(this).data('id');
        loadMethodDetails(id, function(method) {
            const statusLabel = ((method.status || '').toLowerCase() === 'active' || String(method.is_active) === '1') ? 'ACTIVE' : 'INACTIVE';
            const html = `
                <div class="form-group"><label>ID</label><p>${escapeHtml(method.id)}</p></div>
                <div class="form-group"><label>Code</label><p>${escapeHtml(method.method_code || '—')}</p></div>
                <div class="form-group"><label>Name</label><p>${escapeHtml(method.method_name || '—')}</p></div>
                <div class="form-group"><label>Status</label><p>${escapeHtml(statusLabel)}</p></div>
                <div class="form-group"><label>Bank Name</label><p>${escapeHtml(method.bank_name || '—')}</p></div>
                <div class="form-group"><label>Account Number</label><p>${escapeHtml(method.account_number || '—')}</p></div>
                <div class="form-group"><label>Routing Number</label><p>${escapeHtml(method.routing_number || '—')}</p></div>
                <div class="form-group"><label>Wallet Address</label><p>${escapeHtml(method.wallet_address || '—')}</p></div>
                <div class="form-group"><label>Instructions</label><p>${escapeHtml(method.instructions || '—')}</p></div>
                <div class="form-group"><label>Payment Details</label><p>${escapeHtml(method.payment_details || '—')}</p></div>
                <div class="form-group"><label>Min Amount</label><p>${escapeHtml(method.min_amount || '—')}</p></div>
                <div class="form-group"><label>Max Amount</label><p>${escapeHtml(method.max_amount || '—')}</p></div>
                <div class="form-group"><label>Allows Deposit</label><p>${String(method.allows_deposit) === '1' ? 'YES' : 'NO'}</p></div>
                <div class="form-group"><label>Allows Withdrawal</label><p>${String(method.allows_withdrawal) === '1' ? 'YES' : 'NO'}</p></div>
                <div class="form-group"><label>Crypto Method</label><p>${String(method.is_crypto) === '1' ? 'YES' : 'NO'}</p></div>
                <div class="form-group"><label>Created At</label><p>${escapeHtml(method.created_at || '—')}</p></div>
                <div class="form-group"><label>Updated At</label><p>${escapeHtml(method.updated_at || '—')}</p></div>
            `;
            $('#paymentMethodDetailsContent').html(html);
            $('#viewPayment_MethodsModal').modal('show');
        });
    });

    $('#payment_methodsTable').on('click', '.edit-item', function() {
        const id = $(this).data('id');
        loadMethodDetails(id, function(method) {
            $('#addPayment_MethodsModal .modal-title').text('Edit Payment Method');
            $('#addPayment_MethodsForm input[name="id"]').val(method.id || '');
            $('#addPayment_MethodsForm input[name="method_code"]').val(method.method_code || '');
            $('#addPayment_MethodsForm input[name="method_name"]').val(method.method_name || '');
            $('#addPayment_MethodsForm select[name="status"]').val((method.status || 'active').toLowerCase());
            $('#addPayment_MethodsModal').modal('show');
        });
    });

    $('#payment_methodsTable').on('click', '.delete-item', function() {
        const id = $(this).data('id');
        if (!confirm('Delete this payment method?')) return;

        $.post(endpoint, { action: 'delete', id: id }, function(response) {
            if (response && response.success) {
                toastr.success(response.message || 'Deleted');
                payment_methodsTable.ajax.reload(null, false);
            } else {
                toastr.error(response && response.message ? response.message : 'Failed to delete payment method');
            }
        }, 'json').fail(function() {
            toastr.error('Failed to delete payment method');
        });
    });

    // Refresh button
    $('#refreshPayment_Methods').click(function() {
        payment_methodsTable.ajax.reload(null, false);
    });
});
</script>
