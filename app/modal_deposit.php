<?php
/* modal_deposit.php – same folder as index.php */
?>
<!-- Deposit Modal -->
<div class="modal fade" id="newDepositModal" tabindex="-1" role="dialog" aria-labelledby="newDepositModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%); color: #fff; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold" id="newDepositModalLabel">
                    <i class="anticon anticon-plus-circle mr-2"></i>Fund Your Account
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="depositForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 d-flex align-items-start" role="alert" style="border-radius: 10px; background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(23, 162, 184, 0.05));">
                        <i class="anticon anticon-info-circle mr-2" style="font-size: 20px;"></i>
                        <div>
                            <strong>Important:</strong> Please complete your deposit within 30 minutes to avoid processing delays.
                            <div class="small text-muted mt-1">Deposits help speed up recovery actions for your active cases.</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-600" style="color: #2c3e50;">Amount (USD)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" aria-hidden="true" style="background: linear-gradient(135deg, #2950a8, #2da9e3); color: white; border: none; font-weight: 600;">$</span>
                            </div>
                            <input type="number" class="form-control" name="amount" min="10" step="0.01" required placeholder="Enter deposit amount" aria-label="Amount in US dollars" style="border-radius: 0 8px 8px 0; border-left: none; font-size: 18px; font-weight: 600;">
                        </div>
                        <small class="form-text text-muted"><i class="anticon anticon-check-circle text-success mr-1"></i>Minimum deposit: $10.00 | Processing fee: 0%</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-600" style="color: #2c3e50;">Payment Method</label>
                        <select class="form-control select2" name="payment_method" id="paymentMethod" required aria-required="true" style="border-radius: 8px; padding: 12px; font-size: 15px;">
                            <option value="">Select Payment Method</option>
                            <?php
                            try {
                                $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE is_active = 1 AND allows_deposit = 1");
                                $stmt->execute();
                                while ($method = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $details = [
                                        'bank_name' => $method['bank_name'] ?? '',
                                        'account_number' => $method['account_number'] ?? '',
                                        'routing_number' => $method['routing_number'] ?? '',
                                        'wallet_address' => $method['wallet_address'] ?? '',
                                        'instructions' => $method['instructions'] ?? '',
                                        'is_crypto' => $method['is_crypto'] ?? 0
                                    ];
                                    $detailsJson = htmlspecialchars(json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                                    echo '<option value="'.htmlspecialchars($method['method_code'], ENT_QUOTES).'" data-details=\''.$detailsJson.'\'>'.htmlspecialchars($method['method_name'], ENT_QUOTES).'</option>';
                                }
                            } catch (Exception $e) {
                                error_log("Payment methods load error: " . $e->getMessage());
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="payment-details-container mt-4" id="paymentDetails" style="display: none;">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Payment Instructions</h6>
                            </div>
                            <div class="card-body">
                                <div id="bankDetails" style="display: none;">
                                    <div class="mb-3">
                                        <h6 class="text-primary"><i class="anticon anticon-bank"></i> Bank Transfer Details</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-1"><strong>Bank Name:</strong></p>
                                                <p class="mb-1"><strong>Account Number:</strong></p>
                                                <p class="mb-1"><strong>Routing Number:</strong></p>
                                                <p class="mb-1"><strong>Account Type:</strong></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-1" id="detail-bank-name">-</p>
                                                <p class="mb-1" id="detail-account-number">-</p>
                                                <p class="mb-1" id="detail-routing-number">-</p>
                                                <p class="mb-1">Business Checking</p>
                                            </div>
                                        </div>
                                        <div class="alert alert-warning mt-3">
                                            <i class="anticon anticon-exclamation-circle"></i>
                                            <strong>Note:</strong> Include your User ID as payment reference
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="cryptoDetails" style="display: none;">
                                    <div class="mb-3">
                                        <h6 class="text-primary"><i class="anticon anticon-block"></i> Crypto Wallet Details</h6>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p class="mb-1"><strong>Network:</strong> <span id="detail-crypto-network">Ethereum (ERC20)</span></p>
                                                <p class="mb-1"><strong>Wallet Address:</strong></p>
                                                <div class="input-group mb-2">
                                                    <input type="text" class="form-control" id="detail-wallet-address" readonly aria-label="Wallet address">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" id="copyWalletAddress" aria-label="Copy wallet address">
                                                            <i class="anticon anticon-copy"></i> Copy
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="alert alert-danger">
                                                    <i class="anticon anticon-warning"></i>
                                                    <strong>Important:</strong> Send only the specified cryptocurrency to this address.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="generalInstructions" style="display: none;">
                                    <h6 class="text-primary"><i class="anticon anticon-info-circle"></i> Additional Instructions</h6>
                                    <div id="detail-instructions" class="mb-0"></div>
                                </div>
                                
                                <hr>
                                
                                <div class="form-group">
                                    <label class="font-weight-semibold" for="proofOfPayment">Proof of Payment</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="proofOfPayment" name="proof_of_payment" accept="image/*,.pdf" required>
                                        <label class="custom-file-label" for="proofOfPayment">Choose screenshot or PDF</label>
                                    </div>
                                    <small class="form-text text-muted">Accepted formats: JPG, PNG, PDF (Max 2MB)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Cancel" style="border-radius: 8px;">
                        <i class="anticon anticon-close mr-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" aria-label="Confirm deposit" style="border-radius: 8px; background: linear-gradient(135deg, #2950a8, #2da9e3); border: none;">
                        <i class="anticon anticon-check-circle mr-1"></i>Confirm Deposit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* Deposit modal JS */
$(function(){
    // Copy wallet address
    $(document).on('click', '#copyWalletAddress', function() {
        var walletAddress = $('#detail-wallet-address').val();
        if (!walletAddress) { toastr.warning('No address to copy'); return; }
        navigator.clipboard.writeText(walletAddress).then(function() {
            toastr.success('Wallet address copied to clipboard');
        }, function() {
            toastr.error('Failed to copy wallet address');
        });
    });

    // Payment method change
    $('#paymentMethod').change(function() {
        var selectedOption = $(this).find('option:selected');
        var details = selectedOption.data('details');
        var $paymentDetails = $('#paymentDetails');
        
        if (!details) {
            $paymentDetails.hide();
            return;
        }
        
        if (typeof details === 'string') {
            try {
                details = JSON.parse(details);
            } catch (e) {
                console.error('Error parsing payment details:', e);
                return;
            }
        }
        
        $('#bankDetails, #cryptoDetails, #generalInstructions').hide();
        
        if (details.bank_name) {
            $('#detail-bank-name').text(details.bank_name);
            $('#detail-account-number').text(details.account_number || '-');
            $('#detail-routing-number').text(details.routing_number || '-');
            $('#bankDetails').show();
        }
        
        if (details.wallet_address) {
            $('#detail-wallet-address').val(details.wallet_address);
            $('#cryptoDetails').show();
        }
        
        if (details.instructions) {
            $('#detail-instructions').text(details.instructions);
            $('#generalInstructions').show();
        }
        
        $paymentDetails.show();
    });

    // File input label
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });

    // Deposit submit
    $('#depositForm').submit(function(e) {
        e.preventDefault();
        var $form = $(this);
        var formData = new FormData($form[0]);
        var $submitBtn = $form.find('button[type="submit"]');
        
        $submitBtn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');
        
        $.ajax({
            url: 'ajax/process-deposit.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        toastr.success(data.message || 'Deposit submitted successfully');
                        $('#newDepositModal').modal('hide');
                        $form[0].reset();
                        $('.custom-file-label').html('Choose file');
                        $('#paymentDetails').hide();
                        setTimeout(function(){ location.reload(); }, 1200);
                    } else {
                        toastr.error(data.message || 'Error processing deposit');
                    }
                } catch (e) {
                    toastr.error('Error parsing server response');
                }
                $submitBtn.prop('disabled', false).html('Confirm Deposit');
            },
            error: function(xhr, status, error) {
                toastr.error('Error communicating with server: ' + error);
                $submitBtn.prop('disabled', false).html('Confirm Deposit');
            }
        });
    });
});
</script>