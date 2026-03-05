<?php include 'header.php'; ?>

<!-- Main Content START -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Withdrawal Requests</h4>
                        <div class="float-right">
                            <button class="btn btn-primary" data-toggle="modal" data-target="#newWithdrawalModal">
                                <i class="anticon anticon-plus"></i> New Withdrawal
                            </button>
                            <button class="btn btn-success ml-2" id="refreshWithdrawals">
                                <i class="anticon anticon-reload"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger d-none" id="withdrawalError"></div>
                        <div class="alert alert-info">
                            <strong>Current Balance:</strong> 
                            <span id="currentBalance">$<?= number_format($user['balance'], 2) ?></span>
                        </div>
                        <div class="table-responsive">
                            <table id="withdrawalsTable" class="table table-bordered nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Request Date</th>
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
<!-- Main Content END -->

<!-- New Withdrawal Modal -->
<div class="modal fade" id="newWithdrawalModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Withdrawal Request</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="withdrawalForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Amount (USD)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                            </div>
                            <input type="number" class="form-control" name="amount" min="10" step="0.01" required>
                        </div>
                        <small class="form-text text-muted">Minimum withdrawal: $10.00 | Available: <span id="currentBalanceDisplay">$<?= number_format($user['balance'], 2) ?></span></small>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select class="form-control" name="payment_method" required>
                            <option value="">Select Method</option>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE is_active = 1 AND allows_withdrawal = 1");
                            $stmt->execute();
                            while ($method = $stmt->fetch()) {
                                echo '<option value="'.$method['method_code'].'">'.$method['method_name'].'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Details</label>
                        <textarea class="form-control" name="payment_details" rows="3" required 
                                  placeholder="Enter your payment details (e.g., Bitcoin address, Bank account info)"></textarea>
                        <small class="form-text text-muted">Ensure details are accurate for successful processing</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdrawal Details Modal -->
<div class="modal fade" id="withdrawalDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div id="withdrawalDetailsContent"></div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
