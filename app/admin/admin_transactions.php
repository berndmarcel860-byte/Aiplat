<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Transaction Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Transactions</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Transaction List</h5>
                <div class="btn-group">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#filterTransactionsModal">
                        <i class="anticon anticon-filter"></i> Filter
                    </button>
                </div>
            </div>
            
            <div class="m-t-25">
                <table id="transactionsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
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

<!-- Filter Modal -->
<div class="modal fade" id="filterTransactionsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Transactions</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="filterTransactionsForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Type</label>
                        <select class="form-control" name="type">
                            <option value="">All Types</option>
                            <option value="deposit">Deposit</option>
                            <option value="withdrawal">Withdrawal</option>
                            <option value="refund">Refund</option>
                            <option value="fee">Fee</option>
                        </select>
                    </div>
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

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionDetailsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body" id="transactionDetailsContent">
                <!-- AJAX will populate this -->
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
    const transactionsTable = $('#transactionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_transactions.php',
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
                data: 'type',
                render: function(data) {
                    const typeClass = {
                        deposit: 'primary',
                        withdrawal: 'warning',
                        refund: 'success',
                        fee: 'danger'
                    }[data];
                    return `<span class="badge badge-${typeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            { 
                data: 'amount',
                render: function(data) {
                    return '$' + parseFloat(data).toFixed(2);
                }
            },
            { data: 'method_name' },
            { 
                data: 'status',
                render: function(data) {
                    const statusClass = {
                        pending: 'warning',
                        completed: 'success',
                        failed: 'danger',
                        cancelled: 'secondary'
                    }[data];
                    return `<span class="badge badge-${statusClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    let buttons = `
                        <button class="btn btn-sm btn-primary view-transaction" data-id="${data}">
                            <i class="anticon anticon-eye"></i>
                        </button>`;
                    
                    if (row.status === 'pending') {
                        buttons += `
                            <button class="btn btn-sm btn-success approve-transaction" data-id="${data}">
                                <i class="anticon anticon-check"></i>
                            </button>
                            <button class="btn btn-sm btn-danger reject-transaction" data-id="${data}">
                                <i class="anticon anticon-close"></i>
                            </button>`;
                    }
                    
                    return `<div class="btn-group">${buttons}</div>`;
                }
            }
        ]
    });

    // View Transaction Details
    $('#transactionsTable').on('click', '.view-transaction', function() {
        const transactionId = $(this).data('id');
        
        $.ajax({
            url: 'admin_ajax/get_transaction.php',
            type: 'GET',
            data: { id: transactionId },
            success: function(response) {
                if (response.success) {
                    $('#transactionDetailsContent').html(`
                        <div class="form-group">
                            <label>Transaction ID</label>
                            <p>${response.transaction.id}</p>
                        </div>
                        <div class="form-group">
                            <label>User</label>
                            <p>${response.transaction.user_first_name} ${response.transaction.user_last_name}</p>
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <p>${response.transaction.type}</p>
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <p>$${parseFloat(response.transaction.amount).toFixed(2)}</p>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <p>${response.transaction.status}</p>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <p>${new Date(response.transaction.created_at).toLocaleString()}</p>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <p>${response.transaction.method_name || 'N/A'}</p>
                        </div>
                        <div class="form-group">
                            <label>Reference</label>
                            <p>${response.transaction.reference || 'N/A'}</p>
                        </div>
                        <div class="form-group">
                            <label>Admin Notes</label>
                            <p>${response.transaction.admin_notes || 'N/A'}</p>
                        </div>
                    `);
                    $('#transactionDetailsModal').modal('show');
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Approve Transaction
    $('#transactionsTable').on('click', '.approve-transaction', function() {
        const transactionId = $(this).data('id');
        
        if (confirm('Are you sure you want to approve this transaction?')) {
            $.ajax({
                url: 'admin_ajax/approve_transaction.php',
                type: 'POST',
                data: { id: transactionId },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        transactionsTable.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                }
            });
        }
    });

    // Reject Transaction
    $('#transactionsTable').on('click', '.reject-transaction', function() {
        const transactionId = $(this).data('id');
        
        if (confirm('Are you sure you want to reject this transaction?')) {
            $.ajax({
                url: 'admin_ajax/reject_transaction.php',
                type: 'POST',
                data: { id: transactionId },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        transactionsTable.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                }
            });
        }
    });

    // Apply Filters
    $('#filterTransactionsForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        transactionsTable.ajax.url('admin_ajax/get_transactions.php?' + formData).load();
        $('#filterTransactionsModal').modal('hide');
    });
});
</script>