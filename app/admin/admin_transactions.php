<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">User Transaction Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">User Transactions</span>
            </nav>
        </div>
    </div>

    <!-- Platform Distribution Map -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Cases per Platform</h5>
                    <small class="text-muted">Number of open cases by scam platform</small>
                </div>
                <div class="card-body p-2">
                    <div id="txnPlatformChart" style="width:100%;height:280px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Refund Difficulty Mix</h5>
                </div>
                <div class="card-body p-2">
                    <div id="txnDifficultyChart" style="width:100%;height:280px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation tabs across all financial views -->
    <ul class="nav nav-tabs mb-0" style="border-bottom:none;">
        <li class="nav-item">
            <a class="nav-link active" href="admin_transactions.php">
                <i class="anticon anticon-transaction mr-1"></i> User Transactions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admin_package_payments.php">
                <i class="anticon anticon-gift mr-1"></i> Package Payments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admin_deposits.php">
                <i class="anticon anticon-arrow-down mr-1"></i> Deposits
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admin_withdrawals.php">
                <i class="anticon anticon-arrow-up mr-1"></i> Withdrawals
            </a>
        </li>
    </ul>

    <div class="card" style="border-top-left-radius:0;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5>User Transaction List</h5>
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
<div class="modal fade" id="transactionDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title font-weight-bold">
                    <i class="anticon anticon-file-text mr-2 text-primary"></i>
                    Transaction Details
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body p-0" id="transactionDetailsContent">
                <div class="text-center py-5">
                    <i class="anticon anticon-loading anticon-spin font-size-24 text-primary"></i>
                    <p class="mt-2 text-muted">Loading details…</p>
                </div>
            </div>
            <div class="modal-footer" id="transactionDetailsFooter">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectTransactionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="anticon anticon-close-circle mr-1 text-danger"></i>
                    Reject Transaction
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Please provide a reason for rejecting this transaction. The user will be notified.</p>
                <input type="hidden" id="rejectTransactionId">
                <div class="form-group">
                    <label for="rejectReason">Rejection Reason <span class="text-muted">(optional)</span></label>
                    <textarea id="rejectReason" class="form-control" rows="3" placeholder="e.g. Insufficient documentation, suspicious activity..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRejectTransaction">
                    <i class="anticon anticon-stop mr-1"></i> Reject Transaction
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<!-- ApexCharts for transaction maps -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>

<script>
$(document).ready(function() {

    /* ------------------------------------------------------------------ */
    /*  Platform distribution bar chart                                    */
    /* ------------------------------------------------------------------ */
    $.getJSON('admin_ajax/get_platform_case_stats.php', function(stats) {
        if (!stats || !stats.labels || !stats.labels.length) return;
        new ApexCharts(document.querySelector('#txnPlatformChart'), {
            chart: { type: 'bar', height: 260, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 3 } },
            colors: ['#4e73df'],
            dataLabels: { enabled: false },
            series: [{ name: 'Cases', data: stats.values }],
            xaxis: { categories: stats.labels, labels: { style: { fontSize: '11px' } } },
            tooltip: {
                y: { formatter: function(val, opts) {
                    return val + ' case(s) — €' + (stats.amounts[opts.dataPointIndex] || 0).toLocaleString('de-DE');
                }}
            }
        }).render();
    });

    /* ------------------------------------------------------------------ */
    /*  Refund difficulty donut chart                                      */
    /* ------------------------------------------------------------------ */
    $.getJSON('admin_ajax/get_case_difficulty_stats.php', function(ds) {
        if (!ds) return;
        new ApexCharts(document.querySelector('#txnDifficultyChart'), {
            chart: { type: 'donut', height: 260 },
            labels: ['Einfach', 'Mittel', 'Schwierig'],
            series: [ds.easy || 0, ds.medium || 0, ds.hard || 0],
            colors: ['#1cc88a', '#f6c23e', '#e74a3b'],
            legend: { position: 'bottom' },
            plotOptions: { pie: { donut: { size: '60%' } } }
        }).render();
    });

    /* ------------------------------------------------------------------ */
    /*  Helper: badge HTML for type and status                             */
    /* ------------------------------------------------------------------ */
    function typeBadge(type) {
        var map = { deposit: 'primary', withdrawal: 'warning', refund: 'success', fee: 'danger' };
        var cls = map[type] || 'secondary';
        return '<span class="badge badge-' + cls + '">' + (type.charAt(0).toUpperCase() + type.slice(1)) + '</span>';
    }

    function statusBadge(status) {
        var map = { pending: 'warning', completed: 'success', failed: 'danger', cancelled: 'secondary' };
        var cls = map[status] || 'secondary';
        return '<span class="badge badge-' + cls + '">' + (status.charAt(0).toUpperCase() + status.slice(1)) + '</span>';
    }

    /* ------------------------------------------------------------------ */
    /*  Initialize DataTable                                               */
    /* ------------------------------------------------------------------ */
    var transactionsTable = $('#transactionsTable').DataTable({
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
                render: function(data, type, row) {
                    return row.user_first_name + ' ' + row.user_last_name;
                }
            },
            {
                data: 'type',
                render: function(data, type, row) {
                    return typeBadge(data);
                }
            },
            {
                data: 'amount',
                render: function(data) {
                    return '€' + parseFloat(data).toFixed(2);
                }
            },
            { data: 'method_name' },
            {
                data: 'status',
                render: function(data) {
                    return statusBadge(data);
                }
            },
            {
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    var id = parseInt(row.id, 10);
                    var buttons = '<button class="btn btn-sm btn-primary view-transaction" data-id="' + id + '" title="View Details">'
                                + '<i class="anticon anticon-eye"></i></button>';

                    if (row.status === 'pending') {
                        buttons += ' <button class="btn btn-sm btn-success approve-transaction" data-id="' + id + '" title="Approve">'
                                 + '<i class="anticon anticon-check"></i></button>';
                        buttons += ' <button class="btn btn-sm btn-danger reject-transaction" data-id="' + id + '" title="Reject">'
                                 + '<i class="anticon anticon-close"></i></button>';
                    }

                    return '<div class="btn-group">' + buttons + '</div>';
                }
            }
        ]
    });

    /* ------------------------------------------------------------------ */
    /*  View Transaction Details                                           */
    /* ------------------------------------------------------------------ */
    $('#transactionsTable').on('click', '.view-transaction', function() {
        var transactionId = $(this).data('id');

        // Show loading
        $('#transactionDetailsContent').html(
            '<div class="text-center py-5">'
            + '<i class="anticon anticon-loading anticon-spin font-size-24 text-primary"></i>'
            + '<p class="mt-2 text-muted">Loading details…</p>'
            + '</div>'
        );
        $('#transactionDetailsFooter').html('<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>');
        $('#transactionDetailsModal').modal('show');

        $.ajax({
            url: 'admin_ajax/get_transaction.php',
            type: 'GET',
            data: { id: transactionId },
            dataType: 'json',
            success: function(response) {
                if (!response.success) {
                    $('#transactionDetailsContent').html(
                        '<div class="alert alert-danger m-3">' + (response.message || 'Failed to load transaction.') + '</div>'
                    );
                    return;
                }

                var t  = response.transaction;
                var isPending = (t.status === 'pending');

                var typeHtml   = typeBadge(t.type);
                var statusHtml = statusBadge(t.status);
                var amount     = '€' + parseFloat(t.amount || 0).toFixed(2);
                var createdDate = t.created_at ? new Date(t.created_at).toLocaleString('de-DE') : '—';
                var processedDate = t.processed_at ? new Date(t.processed_at).toLocaleString('de-DE') : '—';
                var userFullName = ((t.user_first_name || '') + ' ' + (t.user_last_name || '')).trim() || '—';

                // Determine status colour for summary strip
                var stripColor = { pending: '#fff3cd', completed: '#d1e7dd', failed: '#f8d7da', cancelled: '#e2e3e5' }[t.status] || '#f0f4ff';
                var stripBorder = { pending: '#ffc107', completed: '#198754', failed: '#dc3545', cancelled: '#6c757d' }[t.status] || '#4e73df';

                var html = '<div class="p-4">'

                    // ── Summary strip
                    + '<div class="d-flex align-items-center mb-4 p-3 rounded" style="background:' + stripColor + ';border-left:4px solid ' + stripBorder + '">'
                    +   '<div class="flex-grow-1">'
                    +     '<div class="text-muted small mb-1">Transaction #' + parseInt(t.id, 10) + ' &nbsp;·&nbsp; ' + $('<span>').text(t.reference || '—').html() + '</div>'
                    +     '<div class="font-weight-bold" style="font-size:1.5rem;">' + amount + '</div>'
                    +   '</div>'
                    +   '<div class="text-right">'
                    +     '<div class="mb-1">' + typeHtml + '</div>'
                    +     '<div>' + statusHtml + '</div>'
                    +   '</div>'
                    + '</div>'

                    // ── Two-column grid
                    + '<div class="row no-gutters">'

                    // Left column
                    + '<div class="col-md-6 pr-md-3">'

                    +   '<div class="mb-3">'
                    +     '<small class="text-muted text-uppercase font-weight-semibold d-block" style="font-size:0.72rem;letter-spacing:.05em;">Customer</small>'
                    +     '<div class="font-weight-medium">' + $('<span>').text(userFullName).html() + '</div>'
                    +     (t.user_email ? '<div class="text-muted small">' + $('<span>').text(t.user_email).html() + '</div>' : '')
                    +     '<div class="text-muted small">User ID: ' + (t.user_id || '—') + '</div>'
                    +   '</div>'

                    +   '<div class="mb-3">'
                    +     '<small class="text-muted text-uppercase font-weight-semibold d-block" style="font-size:0.72rem;letter-spacing:.05em;">Reference</small>'
                    +     '<div class="font-weight-medium font-monospace">' + $('<span>').text(t.reference || '—').html() + '</div>'
                    +   '</div>'

                    +   '<div class="mb-3">'
                    +     '<small class="text-muted text-uppercase font-weight-semibold d-block" style="font-size:0.72rem;letter-spacing:.05em;">Payment Method</small>'
                    +     '<div class="font-weight-medium">' + $('<span>').text(t.method_name || '—').html() + '</div>'
                    +   '</div>'

                    + '</div>'

                    // Right column
                    + '<div class="col-md-6 pl-md-3">'

                    +   '<div class="mb-3">'
                    +     '<small class="text-muted text-uppercase font-weight-semibold d-block" style="font-size:0.72rem;letter-spacing:.05em;">Date Created</small>'
                    +     '<div class="font-weight-medium">' + createdDate + '</div>'
                    +   '</div>'

                    +   '<div class="mb-3">'
                    +     '<small class="text-muted text-uppercase font-weight-semibold d-block" style="font-size:0.72rem;letter-spacing:.05em;">Processed</small>'
                    +     '<div class="font-weight-medium">' + processedDate + '</div>'
                    +   '</div>'

                    +   '<div class="mb-3">'
                    +     '<small class="text-muted text-uppercase font-weight-semibold d-block" style="font-size:0.72rem;letter-spacing:.05em;">Type / Status</small>'
                    +     '<div>' + typeHtml + ' &nbsp; ' + statusHtml + '</div>'
                    +   '</div>'

                    + '</div>'

                    + '</div>'; // row

                if (t.description) {
                    html += '<div class="border-top pt-3 mt-1 mb-2">'
                          + '<small class="text-muted text-uppercase font-weight-semibold d-block mb-1" style="font-size:0.72rem;letter-spacing:.05em;">Description</small>'
                          + '<div class="text-dark">' + $('<span>').text(t.description).html() + '</div>'
                          + '</div>';
                }

                if (t.admin_notes) {
                    html += '<div class="alert alert-secondary border mt-2 mb-0 py-2">'
                          + '<small class="text-muted text-uppercase font-weight-semibold d-block mb-1" style="font-size:0.72rem;letter-spacing:.05em;"><i class="anticon anticon-info-circle mr-1"></i>Admin Notes</small>'
                          + '<span class="text-dark">' + $('<span>').text(t.admin_notes).html() + '</span>'
                          + '</div>';
                }

                html += '</div>'; // p-4

                $('#transactionDetailsContent').html(html);

                // Footer buttons
                var footer = '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
                if (isPending) {
                    footer += ' <button type="button" class="btn btn-success modal-approve-transaction" data-id="' + parseInt(t.id, 10) + '">'
                            + '<i class="anticon anticon-check mr-1"></i>Approve</button>';
                    footer += ' <button type="button" class="btn btn-danger modal-reject-transaction" data-id="' + parseInt(t.id, 10) + '">'
                            + '<i class="anticon anticon-close mr-1"></i>Reject</button>';
                }
                $('#transactionDetailsFooter').html(footer);
            },
            error: function() {
                $('#transactionDetailsContent').html('<div class="alert alert-danger m-3">Network error. Please try again.</div>');
            }
        });
    });

    /* ------------------------------------------------------------------ */
    /*  Approve Transaction (table row button)                             */
    /* ------------------------------------------------------------------ */
    $('#transactionsTable').on('click', '.approve-transaction', function() {
        var transactionId = $(this).data('id');
        if (!confirm('Are you sure you want to approve this transaction?')) return;
        doApprove(transactionId);
    });

    /* ------------------------------------------------------------------ */
    /*  Approve Transaction (modal footer button)                          */
    /* ------------------------------------------------------------------ */
    $('#transactionDetailsModal').on('click', '.modal-approve-transaction', function() {
        var transactionId = $(this).data('id');
        if (!confirm('Are you sure you want to approve this transaction?')) return;
        $('#transactionDetailsModal').modal('hide');
        doApprove(transactionId);
    });

    function doApprove(transactionId) {
        $.ajax({
            url: 'admin_ajax/approve_transaction.php',
            type: 'POST',
            data: { id: transactionId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    transactionsTable.ajax.reload();
                } else {
                    toastr.error(response.message || 'Failed to approve transaction.');
                }
            },
            error: function() {
                toastr.error('Network error. Please try again.');
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Open Rejection Modal (table row button)                            */
    /* ------------------------------------------------------------------ */
    $('#transactionsTable').on('click', '.reject-transaction', function() {
        var transactionId = $(this).data('id');
        openRejectModal(transactionId);
    });

    /* ------------------------------------------------------------------ */
    /*  Open Rejection Modal (modal footer button)                         */
    /* ------------------------------------------------------------------ */
    $('#transactionDetailsModal').on('click', '.modal-reject-transaction', function() {
        var transactionId = $(this).data('id');
        $('#transactionDetailsModal').modal('hide');
        openRejectModal(transactionId);
    });

    function openRejectModal(transactionId) {
        $('#rejectTransactionId').val(transactionId);
        $('#rejectReason').val('');
        $('#rejectTransactionModal').modal('show');
    }

    /* ------------------------------------------------------------------ */
    /*  Confirm Rejection                                                  */
    /* ------------------------------------------------------------------ */
    $('#confirmRejectTransaction').on('click', function() {
        var transactionId = $('#rejectTransactionId').val();
        var reason = $('#rejectReason').val().trim();

        $(this).prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i>Rejecting…');

        $.ajax({
            url: 'admin_ajax/reject_transaction.php',
            type: 'POST',
            data: { id: transactionId, reason: reason },
            dataType: 'json',
            success: function(response) {
                $('#confirmRejectTransaction').prop('disabled', false).html('<i class="anticon anticon-stop mr-1"></i> Reject Transaction');
                $('#rejectTransactionModal').modal('hide');
                if (response.success) {
                    toastr.success(response.message);
                    transactionsTable.ajax.reload();
                } else {
                    toastr.error(response.message || 'Failed to reject transaction.');
                }
            },
            error: function() {
                $('#confirmRejectTransaction').prop('disabled', false).html('<i class="anticon anticon-stop mr-1"></i> Reject Transaction');
                toastr.error('Network error. Please try again.');
            }
        });
    });

    /* ------------------------------------------------------------------ */
    /*  Apply Filters                                                      */
    /* ------------------------------------------------------------------ */
    $('#filterTransactionsForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        transactionsTable.ajax.url('admin_ajax/get_transactions.php?' + formData).load();
        $('#filterTransactionsModal').modal('hide');
    });
});
</script>