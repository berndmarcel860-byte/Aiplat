<?php 
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Deposit Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Deposits</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Deposit Requests</h5>
                <div class="btn-group">
                    <button class="btn btn-success" data-toggle="modal" data-target="#addDepositModal">
                        <i class="anticon anticon-plus"></i> Add Deposit
                    </button>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#filterDepositsModal">
                        <i class="anticon anticon-filter"></i> Filter
                    </button>
                </div>
            </div>
            
            <div class="m-t-25">
                <table id="depositsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Deposit Modal -->
<div class="modal fade" id="addDepositModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Deposit for User</h5>
                <button type="button" class="close" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
            </div>
            <form id="addDepositForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select User <span class="text-danger">*</span></label>
                        <select class="form-control" name="user_id" required>
                            <option value="">-- Select User --</option>
                        </select>
                        <small class="form-text text-muted">Select the user for this deposit</small>
                    </div>
                    <div class="form-group">
                        <label>Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                        <small class="form-text text-muted">Enter deposit amount</small>
                    </div>
                    <div class="form-group">
                        <label>Payment Method <span class="text-danger">*</span></label>
                        <select class="form-control" name="method_code" required>
                            <option value="">-- Select Method --</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="paypal">PayPal</option>
                            <option value="bitcoin">Bitcoin</option>
                            <option value="ethereum">Ethereum</option>
                            <option value="credit_card">Credit Card</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Proof of Payment <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" required>
                        <small class="form-text text-muted">Upload proof of payment (JPG, PNG, or PDF - max 10MB)</small>
                    </div>
                    <div class="form-group">
                        <label>Transaction ID / Reference</label>
                        <input type="text" class="form-control" name="transaction_id" placeholder="Optional transaction reference">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="pending">Pending</option>
                            <option value="completed" selected>Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                        <small class="form-text text-muted">Set status for this deposit</small>
                    </div>
                    <div class="form-group">
                        <label>Admin Notes</label>
                        <textarea class="form-control" name="admin_notes" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="anticon anticon-plus"></i> Create Deposit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterDepositsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Deposits</h5>
                <button type="button" class="close" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
            </div>
            <form id="filterDepositsForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select class="form-control" name="method_code">
                            <option value="">All Methods</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="paypal">PayPal</option>
                            <option value="bitcoin">Bitcoin</option>
                            <option value="ethereum">Ethereum</option>
                            <option value="credit_card">Credit Card</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date Range</label>
                        <div class="input-daterange input-group" data-provide="datepicker">
                            <input type="text" class="form-control" name="start_date">
                            <span class="input-group-addon">to</span>
                            <input type="text" class="form-control" name="end_date">
                        </div>
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

<!-- Deposit Details Modal -->
<div class="modal fade" id="depositDetailsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deposit Details</h5>
                <button type="button" class="close" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
            </div>
            <div class="modal-body" id="depositDetailsContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success approve-deposit" data-reference="">Approve</button>
                <button type="button" class="btn btn-danger reject-deposit" data-reference="">Reject</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const depositsTable = $('#depositsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: 'admin_ajax/get_deposits.php', type: 'POST' },
        columns: [
            { data: 'id' },
            { data: null, render: data => `${data.user_first_name} ${data.user_last_name}` },
            { data: 'amount', render: data => '€' + parseFloat(data).toFixed(2) },
            { data: 'method_name' },
            { data: 'status', render: function(data) {
                const statusClass = {
                    pending: 'warning',
                    completed: 'success',
                    failed: 'danger'
                }[data] || 'secondary';
                return `<span class="badge badge-${statusClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
            }},
            { data: 'reference' },
            { data: 'created_at', render: data => new Date(data).toLocaleString() },
            {
                data: 'reference',
                render: function(data, type, row) {
                    let buttons = `
                        <button class="btn btn-sm btn-primary view-deposit" data-reference="${data}">
                            <i class="anticon anticon-eye"></i>
                        </button>`;
                    if (row.status === 'pending') {
                        buttons += `
                            <button class="btn btn-sm btn-success approve-deposit" data-reference="${data}">
                                <i class="anticon anticon-check"></i>
                            </button>
                            <button class="btn btn-sm btn-danger reject-deposit" data-reference="${data}">
                                <i class="anticon anticon-close"></i>
                            </button>`;
                    }
                    return `<div class="btn-group">${buttons}</div>`;
                }
            }
        ]
    });

    let currentReference = null;

    // View deposit details
    $('#depositsTable').on('click', '.view-deposit', function() {
        currentReference = $(this).data('reference');
        $.ajax({
            url: 'admin_ajax/get_deposit.php',
            type: 'GET',
            data: { reference: currentReference },
            success: function(response) {
                if (response.success) {
                    const d = response.deposit;
                    $('#depositDetailsContent').html(`
                        <div class="form-group"><label>ID</label><p>${d.id}</p></div>
                        <div class="form-group"><label>User</label><p>${d.user_first_name} ${d.user_last_name}</p></div>
                        <div class="form-group"><label>Amount</label><p>€${parseFloat(d.amount).toFixed(2)}</p></div>
                        <div class="form-group"><label>Method</label><p>${d.method_name}</p></div>
                        <div class="form-group"><label>Status</label><p>${d.status}</p></div>
                        <div class="form-group"><label>Reference</label><p>${d.reference}</p></div>
                        <div class="form-group"><label>Date</label><p>${new Date(d.created_at).toLocaleString()}</p></div>
                    `);
                    $('.approve-deposit').attr('data-reference', d.reference);
                    $('.reject-deposit').attr('data-reference', d.reference);
                    $('#depositDetailsModal').modal('show');
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Approve Deposit
    $(document).on('click', '.approve-deposit', function() {
        const ref = $(this).data('reference');
        if (!ref) return toastr.error('No reference found.');
        if (confirm('Approve this deposit?')) {
            $.post('admin_ajax/approve_deposit.php', { reference: ref }, function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    depositsTable.ajax.reload();
                    $('#depositDetailsModal').modal('hide');
                } else {
                    toastr.error(response.message);
                }
            }, 'json');
        }
    });

    // Reject Deposit
    $(document).on('click', '.reject-deposit', function() {
        const ref = $(this).data('reference');
        if (!ref) return toastr.error('No reference found.');
        if (confirm('Reject this deposit?')) {
            $.post('admin_ajax/reject_deposit.php', { reference: ref }, function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    depositsTable.ajax.reload();
                    $('#depositDetailsModal').modal('hide');
                } else {
                    toastr.error(response.message);
                }
            }, 'json');
        }
    });

    // Filter deposits
    $('#filterDepositsForm').submit(function(e) {
        e.preventDefault();
        const query = $(this).serialize();
        depositsTable.ajax.url('admin_ajax/get_deposits.php?' + query).load();
        $('#filterDepositsModal').modal('hide');
    });

    // Load users when Add Deposit modal opens
    $('#addDepositModal').on('show.bs.modal', function() {
        $.ajax({
            url: 'admin_ajax/get_users_for_select.php',
            type: 'GET',
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    const select = $('#addDepositForm select[name="user_id"]');
                    select.find('option:not(:first)').remove();
                    (resp.users || []).forEach(function(user) {
                        select.append(`<option value="${user.id}">${user.first_name} ${user.last_name} (${user.email})</option>`);
                    });
                }
            }
        });
    });

    // Add Deposit Form Submit
    $('#addDepositForm').submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        $.ajax({
            url: 'admin_ajax/add_deposit.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    toastr.success(resp.message || 'Deposit created successfully');
                    $('#addDepositModal').modal('hide');
                    $('#addDepositForm')[0].reset();
                    depositsTable.ajax.reload();
                } else {
                    toastr.error(resp && resp.message ? resp.message : 'Failed to create deposit');
                }
            },
            error: function() {
                toastr.error('Server error');
            }
        });
    });
});
</script>