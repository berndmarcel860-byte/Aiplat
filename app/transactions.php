<?php include 'header.php'; ?>

<!-- Content Wrapper START -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Transaction History</h4>
                        <div class="float-right">
                            <button class="btn btn-primary" id="refreshTransactions">
                                <i class="anticon anticon-reload"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger d-none" id="transactionError"></div>
                        <div class="table-responsive">
                            <table id="transactionsTable" class="table table-bordered nowrap" style="width:100%">
                                <!-- Update the table headers to match what the DataTable expects -->
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Reference</th>
                                        <th>Date</th>
                                        <th>Actions</th>
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

<!-- Transaction Details Modal -->
<div class="modal fade" id="withdrawalDetailsModal" tabindex="-1" role="dialog" aria-labelledby="withdrawalDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="withdrawalDetailsModalLabel">
                    <i class="anticon anticon-info-circle"></i> <span id="modal-title-text">Transaction Details</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Transaction Overview -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 bg-light">
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <div class="detail-label">Type</div>
                                        <div class="detail-value" id="detail-type-badge"></div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="detail-label">Amount</div>
                                        <div class="detail-value" id="detail-amount"></div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="detail-label">Status</div>
                                        <div class="detail-value" id="detail-status"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Details -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-file-text"></i> Reference Number:</label>
                            <div class="detail-value" id="detail-reference"></div>
                        </div>
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-credit-card"></i> Payment Method:</label>
                            <div class="detail-value" id="detail-method"></div>
                        </div>
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-calendar"></i> Request Date:</label>
                            <div class="detail-value" id="detail-created"></div>
                        </div>
                        <div class="detail-group" id="otp-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-lock"></i> OTP Verification:</label>
                            <div class="detail-value" id="detail-otp"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-group" id="transaction-id-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-number"></i> Transaction ID:</label>
                            <div class="detail-value" id="detail-transaction-id"></div>
                        </div>
                        <div class="detail-group" id="processed-date-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-check-circle"></i> Processed Date:</label>
                            <div class="detail-value" id="detail-processed"></div>
                        </div>
                        <div class="detail-group" id="updated-date-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-clock-circle"></i> Last Updated:</label>
                            <div class="detail-value" id="detail-updated"></div>
                        </div>
                        <div class="detail-group" id="confirmed-by-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-user"></i> Processed By:</label>
                            <div class="detail-value" id="detail-confirmed-by"></div>
                        </div>
                        <div class="detail-group" id="ip-address-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-global"></i> IP Address:</label>
                            <div class="detail-value" id="detail-ip-address"></div>
                        </div>
                    </div>
                </div>

                <!-- Payment Details Section -->
                <div class="row mt-3" id="payment-details-group" style="display:none;">
                    <div class="col-md-12">
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-info-circle"></i> Payment Details:</label>
                            <div class="detail-value detail-box" id="detail-payment-details"></div>
                        </div>
                    </div>
                </div>

                <!-- Admin Notes Section -->
                <div class="row mt-3" id="admin-notes-group" style="display:none;">
                    <div class="col-md-12">
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-message"></i> Admin Notes:</label>
                            <div class="detail-value detail-box alert alert-info mb-0" id="detail-admin-notes"></div>
                        </div>
                    </div>
                </div>

                <!-- Deposit Proof Section (for deposits only) -->
                <div class="row mt-3" id="proof-group" style="display:none;">
                    <div class="col-md-12">
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-file-image"></i> Payment Proof:</label>
                            <div class="detail-value">
                                <a href="#" id="detail-proof-link" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="anticon anticon-download"></i> View Proof Document
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="anticon anticon-close"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.detail-group {
    margin-bottom: 15px;
}
.detail-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 5px;
    display: block;
}
.detail-value {
    font-size: 14px;
    color: #333;
    font-weight: 500;
}
.detail-box {
    background-color: #f8f9fa;
    padding: 12px;
    border-radius: 4px;
    border-left: 3px solid #007bff;
    word-break: break-all;
}
.badge-lg {
    font-size: 14px;
    padding: 8px 12px;
}
code {
    font-size: 13px;
    font-weight: 600;
}
</style>
<?php include 'footer.php'; ?>
<script>
// Global variable to prevent multiple initializations
var transactionsTableInitialized = false;

$(document).ready(function() {
    // Check if table element exists
    if (!$('#transactionsTable').length) {
        console.log('Transaction table not found');
        return;
    }

    // Toastr initialization
    toastr.options = {
        positionClass: "toast-top-right",
        timeOut: 5000,
        closeButton: true,
        progressBar: true
    };

    // Prevent multiple initializations
    if (transactionsTableInitialized) {
        console.log('Table already initialized, skipping');
        return;
    }
    
    // Check if DataTable already exists and destroy it
    if ($.fn.DataTable.isDataTable('#transactionsTable')) {
        console.log('Destroying existing DataTable instance');
        $('#transactionsTable').DataTable().destroy();
    }
    
    // Mark as initialized
    transactionsTableInitialized = true;
    
    // Initialize DataTable
    var table = $('#transactionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'ajax/transactions.php',
            type: 'POST',
            data: function(d) {
                // Add CSRF token to request
                d.csrf_token = $('meta[name="csrf-token"]').attr('content');
                return JSON.stringify(d);
            },
            contentType: 'application/json',
            dataSrc: function(json) {
                // Validate response data
                if (!json || !json.data) {
                    console.error('Invalid data format:', json);
                    toastr.error('Invalid data received from server');
                    return [];
                }
                console.log('Received data:', json.data.length, 'records');
                return json.data;
            },
            error: function(xhr, error, thrown) {
                console.error('AJAX Error:', xhr.responseText);
                let errorMsg = 'Failed to load transactions';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) errorMsg = response.error;
                } catch (e) {
                    console.error('Could not parse error response:', e);
                }
                
                $('#transactionError').text(errorMsg).removeClass('d-none');
                toastr.error(errorMsg);
            }
        },
        columns: [
            { 
                data: 'type',
                render: function(data, type, row) {
                    // Add icons to transaction types
                    const icon = {
                        'deposit': '<i class="anticon anticon-arrow-down"></i> ',
                        'withdrawal': '<i class="anticon anticon-arrow-up"></i> ',
                        'refund': '<i class="anticon anticon-undo"></i> ',
                        'fee': '<i class="anticon anticon-dollar"></i> ',
                        'transfer': '<i class="anticon anticon-swap"></i> '
                    }[data] || '<i class="anticon anticon-file"></i> ';
                    
                    const typeLabels = {
                        'deposit': '<span class="badge badge-info">' + icon + 'Deposit</span>',
                        'withdrawal': '<span class="badge badge-warning">' + icon + 'Withdrawal</span>',
                        'refund': '<span class="badge badge-success">' + icon + 'Refund</span>',
                        'fee': '<span class="badge badge-secondary">' + icon + 'Fee</span>',
                        'transfer': '<span class="badge badge-primary">' + icon + 'Transfer</span>'
                    };
                    return typeLabels[data] || (icon + (data ? data.charAt(0).toUpperCase() + data.slice(1) : 'N/A'));
                }
            },
            { 
                data: 'amount',
                render: function(data, type, row) {
                    const amount = parseFloat(data || 0).toFixed(2);
                    const colorClass = row.type === 'deposit' || row.type === 'refund' ? 'text-success' : 'text-danger';
                    return '<span class="' + colorClass + '">€' + amount.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</span>';
                }
            },
            { 
                data: 'method',
                render: function(data, type, row) {
                    return data || 'N/A';
                }
            },
            { 
                data: 'status',
                render: function(data, type, row) {
                    if (!data) return '';
                    const statusBadges = {
                        'pending': '<span class="badge badge-warning">Pending</span>',
                        'completed': '<span class="badge badge-success">Completed</span>',
                        'approved': '<span class="badge badge-success">Approved</span>',
                        'rejected': '<span class="badge badge-danger">Rejected</span>',
                        'processing': '<span class="badge badge-info">Processing</span>',
                        'failed': '<span class="badge badge-danger">Failed</span>',
                        'cancelled': '<span class="badge badge-secondary">Cancelled</span>',
                        'confirmed': '<span class="badge badge-success">Confirmed</span>'
                    };
                    return statusBadges[data.toLowerCase()] || '<span class="badge badge-secondary">' + data + '</span>';
                }
            },
            { 
                data: 'reference',
                render: function(data, type, row) {
                    return data ? '<small class="text-muted"><code style="font-size: 11px;">' + data + '</code></small>' : 'N/A';
                }
            },
            { 
                data: 'created_at',
                render: function(data, type, row) {
                    if (!data) return 'N/A';
                    const date = new Date(data);
                    return date.toLocaleDateString('de-DE', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    // Show details button for both withdrawals and deposits
                    if (row.type === 'withdrawal' && row.withdrawal_id) {
                        return '<button class="btn btn-sm btn-primary view-details" data-type="withdrawal" data-id="' + row.withdrawal_id + '" data-row=\'' + JSON.stringify(row) + '\'><i class="anticon anticon-eye"></i> Details</button>';
                    } else if (row.type === 'deposit' && row.deposit_id) {
                        return '<button class="btn btn-sm btn-info view-details" data-type="deposit" data-id="' + row.deposit_id + '" data-row=\'' + JSON.stringify(row) + '\'><i class="anticon anticon-eye"></i> Details</button>';
                    }
                    return '<span class="text-muted">N/A</span>';
                }
            }
        ],
        order: [[5, 'desc']], // Order by date descending
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
            emptyTable: "No transactions found",
            info: "Showing _START_ to _END_ of _TOTAL_ transactions",
            infoEmpty: "Showing 0 to 0 of 0 transactions",
            infoFiltered: "(filtered from _MAX_ total transactions)",
            lengthMenu: "Show _MENU_ transactions",
            loadingRecords: "Loading...",
            search: "Search:",
            zeroRecords: "No matching transactions found"
        },
        initComplete: function() {
            console.log('Table initialization complete');
        },
        drawCallback: function() {
            console.log('Table redraw complete');
        }
    });

    // Refresh button with proper callback handling
    $('#refreshTransactions').on('click', function() {
        console.log('Starting refresh...');
        $('#transactionError').addClass('d-none');
        
        // Use the callback parameter of ajax.reload()
        table.ajax.reload(function(json) {
            console.log('Refresh successful', json);
            toastr.success('Transactions updated successfully');
        }, false);
    });

    // Debug processing events
    $('#transactionsTable').on('processing.dt', function(e, settings, processing) {
        console.log('Processing state:', processing);
    });

    // View details button click handler
    $('#transactionsTable').on('click', '.view-details', function() {
        const rowData = JSON.parse($(this).attr('data-row'));
        const transactionType = $(this).attr('data-type');
        
        // Update modal title based on transaction type
        const modalIcon = transactionType === 'deposit' ? 'anticon-arrow-down' : 'anticon-arrow-up';
        const modalTitle = transactionType === 'deposit' ? 'Deposit Details' : 'Withdrawal Details';
        $('#modal-title-text').html('<i class="anticon ' + modalIcon + '"></i> ' + modalTitle);
        
        // Transaction type badge
        const typeBadges = {
            'deposit': '<span class="badge badge-info badge-lg"><i class="anticon anticon-arrow-down"></i> Deposit</span>',
            'withdrawal': '<span class="badge badge-warning badge-lg"><i class="anticon anticon-arrow-up"></i> Withdrawal</span>'
        };
        $('#detail-type-badge').html(typeBadges[transactionType] || transactionType);
        
        // Amount with color
        const amountColor = transactionType === 'deposit' ? 'text-success' : 'text-danger';
        $('#detail-amount').html('<h4 class="mb-0 ' + amountColor + '"><strong>€' + parseFloat(rowData.amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</strong></h4>');
        
        // Status with color
        const statusBadges = {
            'pending': '<span class="badge badge-warning badge-lg">⏳ Pending</span>',
            'approved': '<span class="badge badge-success badge-lg">✓ Approved</span>',
            'rejected': '<span class="badge badge-danger badge-lg">✗ Rejected</span>',
            'processing': '<span class="badge badge-info badge-lg">🔄 Processing</span>',
            'completed': '<span class="badge badge-success badge-lg">✓ Completed</span>',
            'confirmed': '<span class="badge badge-success badge-lg">✓ Confirmed</span>',
            'failed': '<span class="badge badge-danger badge-lg">✗ Failed</span>',
            'cancelled': '<span class="badge badge-secondary badge-lg">⊘ Cancelled</span>'
        };
        $('#detail-status').html(statusBadges[rowData.status.toLowerCase()] || '<span class="badge badge-secondary">' + rowData.status + '</span>');
        
        // Reference
        $('#detail-reference').html('<code class="bg-light p-2 rounded">' + (rowData.reference || 'N/A') + '</code>');
        
        // Payment method
        $('#detail-method').text(rowData.method || 'N/A');
        
        // Request date
        $('#detail-created').text(formatDate(rowData.created_at));
        
        // OTP verification (only for withdrawals)
        if (transactionType === 'withdrawal') {
            $('#otp-group').show();
            $('#detail-otp').html(rowData.otp_verified == 1 ? '<span class="badge badge-success"><i class="anticon anticon-check"></i> Verified</span>' : '<span class="badge badge-warning"><i class="anticon anticon-close"></i> Not Verified</span>');
        } else {
            $('#otp-group').hide();
        }
        
        // Transaction ID
        if (rowData.transaction_id) {
            $('#transaction-id-group').show();
            $('#detail-transaction-id').html('<code class="bg-light p-2 rounded">' + rowData.transaction_id + '</code>');
        } else {
            $('#transaction-id-group').hide();
        }
        
        // Processed date
        if (rowData.processed_at) {
            $('#processed-date-group').show();
            $('#detail-processed').text(formatDate(rowData.processed_at));
        } else {
            $('#processed-date-group').hide();
        }
        
        // Updated date
        if (rowData.updated_at) {
            $('#updated-date-group').show();
            $('#detail-updated').text(formatDate(rowData.updated_at));
        } else {
            $('#updated-date-group').hide();
        }
        
        // Confirmed/Processed by
        if (rowData.confirmed_by) {
            $('#confirmed-by-group').show();
            $('#detail-confirmed-by').text('Admin ID: ' + rowData.confirmed_by);
        } else {
            $('#confirmed-by-group').hide();
        }
        
        // IP Address
        if (rowData.ip_address) {
            $('#ip-address-group').show();
            $('#detail-ip-address').html('<code class="bg-light p-2 rounded">' + rowData.ip_address + '</code>');
        } else {
            $('#ip-address-group').hide();
        }
        
        // Payment details (for withdrawals) or proof path (for deposits)
        if (transactionType === 'withdrawal' && rowData.details) {
            $('#payment-details-group').show();
            $('#detail-payment-details').text(rowData.details);
        } else if (transactionType === 'deposit' && rowData.details) {
            // For deposits, details contains proof_path
            $('#payment-details-group').hide();
            $('#proof-group').show();
            $('#detail-proof-link').attr('href', '../app/' + rowData.details);
        } else {
            $('#payment-details-group').hide();
            $('#proof-group').hide();
        }
        
        // Admin notes
        if (rowData.admin_notes) {
            $('#admin-notes-group').show();
            $('#detail-admin-notes').text(rowData.admin_notes);
        } else {
            $('#admin-notes-group').hide();
        }
        
        // Show modal
        $('#withdrawalDetailsModal').modal('show');
    });
    
    // Helper function to format dates
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('de-DE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
});
</script>

