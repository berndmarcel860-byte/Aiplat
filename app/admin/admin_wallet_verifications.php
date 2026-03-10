<?php
/**
 * Admin Wallet Verifications Dashboard
 * Manage cryptocurrency wallet verifications with satoshi test
 */

include 'admin_session.php';
include 'admin_header.php';

$page_title = "Wallet Verifications";
$current_page = "wallet-verifications";

// Get counts for each status
try {
    $stmt = $pdo->prepare("
        SELECT 
            verification_status,
            COUNT(*) as count
        FROM user_payment_methods
        WHERE type = 'crypto'
        GROUP BY verification_status
    ");
    $stmt->execute();
    $status_counts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_counts[$row['verification_status']] = $row['count'];
    }
} catch (PDOException $e) {
    $status_counts = [];
}

$pending_count = $status_counts['pending'] ?? 0;
$verifying_count = $status_counts['verifying'] ?? 0;
$verified_count = $status_counts['verified'] ?? 0;
$failed_count = $status_counts['failed'] ?? 0;
?>


    <div class="main-content">
        <div class="page-header">
            <h2 class="page-title">
                <i class="anticon anticon-safety-certificate"></i>
                Wallet Verifications
            </h2>
            <div class="breadcrumb">
                <a href="admin_dashboard.php">Dashboard</a>
                <span class="divider">/</span>
                <span>Wallet Verifications</span>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary text-white">
                            <div class="stat-icon">
                                <i class="anticon anticon-clock-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?= $pending_count ?></div>
                                <div class="stat-label">Pending</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning text-white">
                            <div class="stat-icon">
                                <i class="anticon anticon-loading"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?= $verifying_count ?></div>
                                <div class="stat-label">Verifying</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success text-white">
                            <div class="stat-icon">
                                <i class="anticon anticon-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?= $verified_count ?></div>
                                <div class="stat-label">Verified</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-danger text-white">
                            <div class="stat-icon">
                                <i class="anticon anticon-close-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?= $failed_count ?></div>
                                <div class="stat-label">Failed</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#pending-tab" role="tab">
                            <i class="anticon anticon-clock-circle"></i> Pending (<?= $pending_count ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#verifying-tab" role="tab">
                            <i class="anticon anticon-loading"></i> Verifying (<?= $verifying_count ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#verified-tab" role="tab">
                            <i class="anticon anticon-check-circle"></i> Verified (<?= $verified_count ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#failed-tab" role="tab">
                            <i class="anticon anticon-close-circle"></i> Failed (<?= $failed_count ?>)
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content mt-3">
                    <!-- Pending Tab -->
                    <div class="tab-pane fade show active" id="pending-tab" role="tabpanel">
                        <div class="table-responsive">
                            <table id="pending-table" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Cryptocurrency</th>
                                        <th>Network</th>
                                        <th>Wallet Address</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Verifying Tab -->
                    <div class="tab-pane fade" id="verifying-tab" role="tabpanel">
                        <div class="table-responsive">
                            <table id="verifying-table" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Cryptocurrency</th>
                                        <th>Network</th>
                                        <th>Wallet Address</th>
                                        <th>Test Amount</th>
                                        <th>TX Hash</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Verified Tab -->
                    <div class="tab-pane fade" id="verified-tab" role="tabpanel">
                        <div class="table-responsive">
                            <table id="verified-table" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Cryptocurrency</th>
                                        <th>Network</th>
                                        <th>Wallet Address</th>
                                        <th>Verified By</th>
                                        <th>Verified At</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Failed Tab -->
                    <div class="tab-pane fade" id="failed-tab" role="tabpanel">
                        <div class="table-responsive">
                            <table id="failed-table" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Cryptocurrency</th>
                                        <th>Network</th>
                                        <th>Wallet Address</th>
                                        <th>Reason</th>
                                        <th>Failed At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Wallet Details Modal -->
<div class="modal fade" id="walletDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(90deg,#2950a8,#2da9e3);color:#fff;">
                <h5 class="modal-title">
                    <i class="anticon anticon-eye mr-2"></i> Wallet Verification Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="walletDetailsBody">
                <div class="text-center p-3 text-muted">
                    <i class="anticon anticon-loading anticon-spin"></i> Loading...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Set Verification Details Modal -->
<div class="modal fade" id="setVerificationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="anticon anticon-setting"></i> Set Verification Details
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="setVerificationForm">
                    <input type="hidden" id="wallet_id" name="wallet_id">
                    
                    <div class="form-group">
                        <label>User</label>
                        <input type="text" class="form-control" id="modal_user_info" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Cryptocurrency</label>
                        <input type="text" class="form-control" id="modal_crypto_info" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Wallet Address</label>
                        <input type="text" class="form-control" id="modal_wallet_address" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="verification_amount">Test Amount *</label>
                        <input type="text" class="form-control" id="verification_amount" name="verification_amount" 
                               placeholder="0.00001" required>
                        <small class="form-text text-muted">
                            Enter the test amount in smallest unit (e.g., 0.00001 BTC, 0.001 ETH)
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="verification_address">Platform Wallet Address *</label>
                        <input type="text" class="form-control" id="verification_address" name="verification_address" 
                               placeholder="bc1q..." required>
                        <small class="form-text text-muted">
                            Enter the platform wallet address to receive the test deposit
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveVerificationDetails()">
                    <i class="anticon anticon-save"></i> Save Details
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Verification Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="anticon anticon-check-circle"></i> Approve Wallet Verification
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="approveForm">
                    <input type="hidden" id="approve_wallet_id" name="wallet_id">
                    
                    <div class="alert alert-info">
                        <i class="anticon anticon-info-circle"></i>
                        Please verify the transaction on the blockchain before approving.
                    </div>
                    
                    <div class="form-group">
                        <label>User</label>
                        <input type="text" class="form-control" id="approve_user_info" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Cryptocurrency</label>
                        <input type="text" class="form-control" id="approve_crypto_info" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Transaction Hash</label>
                        <input type="text" class="form-control" id="approve_txid" readonly>
                        <small class="form-text">
                            <a href="#" id="blockchain_explorer_link" target="_blank">
                                <i class="anticon anticon-link"></i> View on Blockchain Explorer
                            </a>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="approve_notes">Notes (Optional)</label>
                        <textarea class="form-control" id="approve_notes" name="notes" rows="3" 
                                  placeholder="Add any notes about this verification..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="approveVerification()">
                    <i class="anticon anticon-check"></i> Approve
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Verification Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="anticon anticon-close-circle"></i> Reject Wallet Verification
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <input type="hidden" id="reject_wallet_id" name="wallet_id">
                    
                    <div class="alert alert-warning">
                        <i class="anticon anticon-warning"></i>
                        This will reject the verification. The user can resubmit after fixing the issue.
                    </div>
                    
                    <div class="form-group">
                        <label>User</label>
                        <input type="text" class="form-control" id="reject_user_info" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Cryptocurrency</label>
                        <input type="text" class="form-control" id="reject_crypto_info" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="reject_reason">Rejection Reason *</label>
                        <select class="form-control" id="reject_reason" required>
                            <option value="">Select reason...</option>
                            <option value="incorrect_amount">Incorrect Amount Sent</option>
                            <option value="wrong_address">Wrong Wallet Address</option>
                            <option value="transaction_not_found">Transaction Not Found</option>
                            <option value="insufficient_confirmations">Insufficient Confirmations</option>
                            <option value="invalid_transaction">Invalid Transaction</option>
                            <option value="other">Other (specify in notes)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reject_notes">Notes *</label>
                        <textarea class="form-control" id="reject_notes" name="notes" rows="3" 
                                  placeholder="Explain the reason for rejection..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="rejectVerification()">
                    <i class="anticon anticon-close"></i> Reject
                </button>
            </div>
        </div>
    </div>


<style>
.stat-card {
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-icon {
    font-size: 36px;
    opacity: 0.8;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    line-height: 1;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
    margin-top: 5px;
}

.wallet-address {
    font-family: monospace;
    font-size: 12px;
    word-break: break-all;
}

.badge-pending {
    background-color: #17a2b8;
    color: white;
}

.badge-verifying {
    background-color: #ffc107;
    color: #000;
}

.badge-verified {
    background-color: #28a745;
    color: white;
}

.badge-failed {
    background-color: #dc3545;
    color: white;
}
</style>
<?php include 'admin_footer.php'; ?>
<script>
const escapeHtml = function(str) {
    if (str == null) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
};

$(document).ready(function() {
    // Initialize DataTables for each tab
    const pendingTable = $('#pending-table').DataTable({
        ajax: {
            url: 'admin_ajax/get_pending_wallets.php',
            data: { status: 'pending' },
            dataSrc: 'wallets'
        },
        columns: [
            { data: 'id' },
            { data: 'username' },
            { data: 'cryptocurrency' },
            { data: 'network' },
            { 
                data: 'wallet_address',
                render: function(data) {
                    return '<span class="wallet-address">' + data + '</span>';
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
                    return `<div class="btn-group">
                        <button class="btn btn-sm btn-info" onclick="openWalletDetailsModal(${row.id})" title="View Details">
                            <i class="anticon anticon-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="openSetVerificationModal(${row.id}, '${escapeHtml(row.username)}', '${escapeHtml(row.cryptocurrency)}', '${escapeHtml(row.network)}', '${escapeHtml(row.wallet_address)}')">
                        <i class="anticon anticon-setting"></i> Set Details
                    </button>
                    </div>`;
                }
            }
        ],
        order: [[0, 'desc']]
    });

    const verifyingTable = $('#verifying-table').DataTable({
        ajax: {
            url: 'admin_ajax/get_pending_wallets.php',
            data: { status: 'verifying' },
            dataSrc: 'wallets'
        },
        columns: [
            { data: 'id' },
            { data: 'username' },
            { data: 'cryptocurrency' },
            { data: 'network' },
            { 
                data: 'wallet_address',
                render: function(data) {
                    return '<span class="wallet-address">' + data + '</span>';
                }
            },
            { data: 'verification_amount' },
            { 
                data: 'verification_txid',
                render: function(data) {
                    return '<span class="wallet-address">' + (data || 'N/A') + '</span>';
                }
            },
            { 
                data: 'verification_requested_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleString() : 'N/A';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `<div class="btn-group">
                        <button class="btn btn-sm btn-info" onclick="openWalletDetailsModal(${row.id})" title="View Details">
                            <i class="anticon anticon-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-success" onclick="openApproveModal(${row.id}, '${escapeHtml(row.username)}', '${escapeHtml(row.cryptocurrency)}', '${escapeHtml(row.network)}', '${escapeHtml(row.verification_txid || '')}')">
                            <i class="anticon anticon-check"></i> Approve
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="openRejectModal(${row.id}, '${escapeHtml(row.username)}', '${escapeHtml(row.cryptocurrency)}', '${escapeHtml(row.network)}')">
                            <i class="anticon anticon-close"></i> Reject
                        </button>
                    </div>`;
                }
            }
        ],
        order: [[0, 'desc']]
    });

    const verifiedTable = $('#verified-table').DataTable({
        ajax: {
            url: 'admin_ajax/get_pending_wallets.php',
            data: { status: 'verified' },
            dataSrc: 'wallets'
        },
        columns: [
            { data: 'id' },
            { data: 'username' },
            { data: 'cryptocurrency' },
            { data: 'network' },
            { 
                data: 'wallet_address',
                render: function(data) {
                    return '<span class="wallet-address">' + data + '</span>';
                }
            },
            { data: 'verified_by_name' },
            { 
                data: 'verified_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleString() : 'N/A';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `<button class="btn btn-sm btn-info" onclick="openWalletDetailsModal(${row.id})" title="View Details">
                        <i class="anticon anticon-eye"></i> Details
                    </button>`;
                }
            }
        ],
        order: [[0, 'desc']]
    });

    const failedTable = $('#failed-table').DataTable({
        ajax: {
            url: 'admin_ajax/get_pending_wallets.php',
            data: { status: 'failed' },
            dataSrc: 'wallets'
        },
        columns: [
            { data: 'id' },
            { data: 'username' },
            { data: 'cryptocurrency' },
            { data: 'network' },
            { 
                data: 'wallet_address',
                render: function(data) {
                    return '<span class="wallet-address">' + data + '</span>';
                }
            },
            { data: 'verification_notes' },
            { 
                data: 'updated_at',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `<div class="btn-group">
                        <button class="btn btn-sm btn-info" onclick="openWalletDetailsModal(${row.id})" title="View Details">
                            <i class="anticon anticon-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="openSetVerificationModal(${row.id}, '${escapeHtml(row.username)}', '${escapeHtml(row.cryptocurrency)}', '${escapeHtml(row.network)}', '${escapeHtml(row.wallet_address)}')">
                            <i class="anticon anticon-reload"></i> Reset
                        </button>
                    </div>`;
                }
            }
        ],
        order: [[0, 'desc']]
    });

    // Reload table when tab is changed
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        if (target === '#pending-tab') pendingTable.ajax.reload();
        if (target === '#verifying-tab') verifyingTable.ajax.reload();
        if (target === '#verified-tab') verifiedTable.ajax.reload();
        if (target === '#failed-tab') failedTable.ajax.reload();
    });
});

function openSetVerificationModal(walletId, userName, crypto, network, address) {
    $('#wallet_id').val(walletId);
    $('#modal_user_info').val(userName);
    $('#modal_crypto_info').val(crypto + ' (' + network + ')');
    $('#modal_wallet_address').val(address);
    $('#verification_amount').val('');
    $('#verification_address').val('');
    $('#setVerificationModal').modal('show');
}

function saveVerificationDetails() {
    const formData = {
        wallet_id: $('#wallet_id').val(),
        verification_amount: $('#verification_amount').val(),
        verification_address: $('#verification_address').val()
    };

    $.ajax({
        url: 'admin_ajax/set_verification_details.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                toastr.success('Verification details set successfully!');
                $('#setVerificationModal').modal('hide');
                $('#pending-table').DataTable().ajax.reload();
            } else {
                toastr.error('Error: ' + response.message);
            }
        },
        error: function() {
            toastr.error('Failed to set verification details. Please try again.');
        }
    });
}

function openApproveModal(walletId, userName, crypto, network, txid) {
    $('#approve_wallet_id').val(walletId);
    $('#approve_user_info').val(userName);
    $('#approve_crypto_info').val(crypto + ' (' + network + ')');
    $('#approve_txid').val(txid);
    $('#approve_notes').val('');
    
    // Set blockchain explorer link based on cryptocurrency
    const explorerUrl = getBlockchainExplorerUrl(crypto, txid);
    $('#blockchain_explorer_link').attr('href', explorerUrl);
    
    $('#approveModal').modal('show');
}

function approveVerification() {
    const formData = {
        wallet_id: $('#approve_wallet_id').val(),
        notes: $('#approve_notes').val()
    };

    $.ajax({
        url: 'admin_ajax/approve_wallet_verification.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                toastr.success('Wallet verification approved successfully!');
                $('#approveModal').modal('hide');
                $('#verifying-table').DataTable().ajax.reload();
                $('#verified-table').DataTable().ajax.reload();
            } else {
                toastr.error('Error: ' + response.message);
            }
        },
        error: function() {
            toastr.error('Failed to approve verification. Please try again.');
        }
    });
}

function openRejectModal(walletId, userName, crypto, network) {
    $('#reject_wallet_id').val(walletId);
    $('#reject_user_info').val(userName);
    $('#reject_crypto_info').val(crypto + ' (' + network + ')');
    $('#reject_reason').val('');
    $('#reject_notes').val('');
    $('#rejectModal').modal('show');
}

function rejectVerification() {
    const reason = $('#reject_reason').val();
    const notes = $('#reject_notes').val();
    
    if (!reason || !notes) {
        toastr.warning('Please provide both a reason and notes for rejection.');
        return;
    }

    const formData = {
        wallet_id: $('#reject_wallet_id').val(),
        reason: reason,
        notes: notes
    };

    $.ajax({
        url: 'admin_ajax/reject_wallet_verification.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                toastr.success('Wallet verification rejected.');
                $('#rejectModal').modal('hide');
                $('#verifying-table').DataTable().ajax.reload();
                $('#failed-table').DataTable().ajax.reload();
            } else {
                toastr.error('Error: ' + response.message);
            }
        },
        error: function() {
            toastr.error('Failed to reject verification. Please try again.');
        }
    });
}

function openWalletDetailsModal(walletId) {
    $('#walletDetailsBody').html('<div class="text-center p-3 text-muted"><i class="anticon anticon-loading anticon-spin"></i> Loading...</div>');
    $('#walletDetailsModal').modal('show');

    $.ajax({
        url: 'admin_ajax/get_pending_wallets.php',
        method: 'GET',
        data: { wallet_id: walletId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.wallet) {
                const w = response.wallet;
                const statusBadge = {
                    pending: '<span class="badge badge-info">Pending</span>',
                    verifying: '<span class="badge badge-warning">Verifying</span>',
                    verified: '<span class="badge badge-success">Verified</span>',
                    failed: '<span class="badge badge-danger">Failed</span>'
                }[w.verification_status] || '<span class="badge badge-secondary">' + escapeHtml(w.verification_status) + '</span>';

                const txid = w.verification_txid || '';
                const explorerLink = txid
                    ? '<a href="' + escapeHtml(getBlockchainExplorerUrl(w.cryptocurrency, txid)) + '" target="_blank" rel="noopener" class="text-primary"><i class="anticon anticon-link"></i> View on Explorer</a>'
                    : 'N/A';

                $('#walletDetailsBody').html(
                    '<div class="row">' +
                        '<div class="col-md-6">' +
                            '<table class="table table-sm table-bordered">' +
                                '<tr><th class="bg-light" style="width:40%">User</th><td>' + escapeHtml(w.user_full_name || w.username) + '</td></tr>' +
                                '<tr><th class="bg-light">Email</th><td>' + escapeHtml(w.email) + '</td></tr>' +
                                '<tr><th class="bg-light">Status</th><td>' + statusBadge + '</td></tr>' +
                                '<tr><th class="bg-light">Cryptocurrency</th><td><strong>' + escapeHtml(w.cryptocurrency) + '</strong></td></tr>' +
                                '<tr><th class="bg-light">Network</th><td>' + escapeHtml(w.network) + '</td></tr>' +
                                '<tr><th class="bg-light">Submitted</th><td>' + (w.created_at ? new Date(w.created_at).toLocaleString() : 'N/A') + '</td></tr>' +
                            '</table>' +
                        '</div>' +
                        '<div class="col-md-6">' +
                            '<table class="table table-sm table-bordered">' +
                                '<tr><th class="bg-light" style="width:40%">Wallet Address</th><td><span style="font-family:monospace;word-break:break-all;">' + escapeHtml(w.wallet_address) + '</span></td></tr>' +
                                '<tr><th class="bg-light">Test Amount</th><td>' + escapeHtml(w.verification_amount || 'N/A') + '</td></tr>' +
                                '<tr><th class="bg-light">Platform Address</th><td><span style="font-family:monospace;word-break:break-all;">' + escapeHtml(w.verification_address || 'N/A') + '</span></td></tr>' +
                                '<tr><th class="bg-light">TX Hash</th><td><span style="font-family:monospace;word-break:break-all;">' + escapeHtml(txid || 'N/A') + '</span></td></tr>' +
                                '<tr><th class="bg-light">Explorer</th><td>' + explorerLink + '</td></tr>' +
                                '<tr><th class="bg-light">Verified By</th><td>' + escapeHtml(w.verified_by_name || 'N/A') + '</td></tr>' +
                                '<tr><th class="bg-light">Verified At</th><td>' + (w.verified_at ? new Date(w.verified_at).toLocaleString() : 'N/A') + '</td></tr>' +
                                '<tr><th class="bg-light">Notes</th><td>' + escapeHtml(w.verification_notes || '—') + '</td></tr>' +
                            '</table>' +
                        '</div>' +
                    '</div>'
                );
            } else {
                $('#walletDetailsBody').html('<div class="alert alert-danger m-3">Could not load wallet details.</div>');
            }
        },
        error: function() {
            $('#walletDetailsBody').html('<div class="alert alert-danger m-3">Error loading wallet details.</div>');
        }
    });
}

function getBlockchainExplorerUrl(crypto, txid) {
    const explorers = {
        'BTC': 'https://blockchain.com/btc/tx/',
        'ETH': 'https://etherscan.io/tx/',
        'USDT': 'https://etherscan.io/tx/',
        'USDC': 'https://etherscan.io/tx/',
        'BNB': 'https://bscscan.com/tx/',
        'XRP': 'https://xrpscan.com/tx/',
        'ADA': 'https://cardanoscan.io/transaction/',
        'SOL': 'https://solscan.io/tx/',
        'DOT': 'https://polkascan.io/polkadot/transaction/',
        'DOGE': 'https://dogechain.info/tx/'
    };
    
    return (explorers[crypto] || 'https://blockchain.com/') + txid;
}
</script>


