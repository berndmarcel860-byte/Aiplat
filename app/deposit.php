<?php include 'header.php'; ?>

<!-- Content Wrapper START -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Deposit Funds</h4>
                        <div class="float-right">
                            <button class="btn btn-primary" data-toggle="modal" data-target="#newDepositModal">
                                <i class="anticon anticon-plus"></i> New Deposit
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="depositsTable" class="table table-bordered nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
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

<!-- New Deposit Modal -->
<div class="modal fade" id="newDepositModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Deposit</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="depositForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Amount (USD)</label>
                        <input type="number" class="form-control" name="amount" min="10" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select class="form-control" name="payment_method" id="paymentMethod" required>
                            <option value="">Select Method</option>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE is_active = 1");
                            $stmt->execute();
                            while ($method = $stmt->fetch()) {
                                echo '<option value="'.$method['method_code'].'" data-details="'.htmlspecialchars($method['payment_details']).'">'.$method['method_name'].'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group" id="paymentDetails">
                        <!-- Dynamic payment details will appear here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Deposit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>