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
<div class="modal fade" id="newWithdrawalModal" tabindex="-1" role="dialog" aria-labelledby="newWithdrawalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px;">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#28a745,#20c997);color:#fff;border-radius:12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold" id="newWithdrawalModalLabel">
                    <i class="anticon anticon-download mr-2"></i>Withdrawal Request
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <!-- Step Indicator -->
            <div class="px-4 pt-3 pb-0" style="background:#fff;border-bottom:1px solid #f0f0f0;">
                <div class="d-flex align-items-center" style="padding-bottom:14px;">
                    <div class="d-flex flex-column align-items-center" style="flex:0 0 auto;">
                        <div id="wd-circle-1" style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#28a745,#20c997);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;box-shadow:0 2px 6px rgba(40,167,69,0.3);">1</div>
                        <div id="wd-label-1" style="font-size:10px;color:#28a745;font-weight:700;margin-top:4px;white-space:nowrap;">Amount</div>
                    </div>
                    <div id="wd-bar-1" style="flex:1;height:2px;background:#dee2e6;margin:0 6px 14px;"></div>
                    <div class="d-flex flex-column align-items-center" style="flex:0 0 auto;">
                        <div id="wd-circle-2" style="width:30px;height:30px;border-radius:50%;background:#dee2e6;color:#6c757d;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">2</div>
                        <div id="wd-label-2" style="font-size:10px;color:#6c757d;font-weight:600;margin-top:4px;white-space:nowrap;">Method</div>
                    </div>
                    <div id="wd-bar-2" style="flex:1;height:2px;background:#dee2e6;margin:0 6px 14px;"></div>
                    <div class="d-flex flex-column align-items-center" style="flex:0 0 auto;">
                        <div id="wd-circle-3" style="width:30px;height:30px;border-radius:50%;background:#dee2e6;color:#6c757d;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">3</div>
                        <div id="wd-label-3" style="font-size:10px;color:#6c757d;font-weight:600;margin-top:4px;white-space:nowrap;">OTP</div>
                    </div>
                </div>
            </div>
            <form id="withdrawalForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">
                <div class="modal-body p-4">
                    <!-- STEP 1: Amount -->
                    <div id="wdStep1">
                        <input type="hidden" id="wdAvailableBalance" value="<?= (float)($user['balance'] ?? 0) ?>">
                        <div class="form-group">
                            <label class="font-weight-600">Amount (USD)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" style="background:linear-gradient(135deg,#28a745,#20c997);color:#fff;border:none;font-weight:600;">$</span>
                                </div>
                                <input type="number" class="form-control" id="wdAmount" name="amount" min="10" step="0.01" required placeholder="Enter withdrawal amount" style="border-radius:0 8px 8px 0;font-size:18px;font-weight:600;">
                            </div>
                            <small class="form-text text-muted">
                                Available balance: <strong>$<?= number_format($user['balance'] ?? 0, 2) ?></strong>
                                &nbsp;| Minimum: $10.00
                            </small>
                        </div>
                    </div><!-- /wdStep1 -->

                    <!-- STEP 2: Payment Method + Details -->
                    <div id="wdStep2" style="display:none;">
                        <div class="form-group">
                            <label class="font-weight-600">Payment Method</label>
                            <select class="form-control" name="payment_method" id="wdMethod" required style="border-radius:8px;padding:10px;font-size:15px;">
                                <option value="">Select Withdrawal Method</option>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE is_active = 1 AND allows_withdrawal = 1");
                                    $stmt->execute();
                                    while ($method = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . htmlspecialchars($method['method_code'], ENT_QUOTES) . '">'
                                             . htmlspecialchars($method['method_name'], ENT_QUOTES) . '</option>';
                                    }
                                } catch (Exception $e) {
                                    error_log("Withdrawal methods load error: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">Payment Details</label>
                            <textarea class="form-control" name="payment_details" id="wdPaymentDetails" rows="3" required placeholder="Enter your payment details (wallet address, bank account, etc.)"></textarea>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="wdConfirmDetails">
                                <label class="custom-control-label" for="wdConfirmDetails">
                                    I confirm that the payment details provided are accurate.
                                </label>
                            </div>
                        </div>
                    </div><!-- /wdStep2 -->

                    <!-- STEP 3: OTP Verification -->
                    <div id="wdStep3" style="display:none;">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="anticon anticon-safety mr-1"></i> Email Verification</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">For security, please verify your identity via a one-time code sent to your registered email.</p>
                                <div class="input-group mb-2">
                                    <input type="text" id="wdOtpCode" maxlength="6" class="form-control" placeholder="Enter 6-digit OTP" disabled style="font-size:20px;letter-spacing:6px;text-align:center;font-weight:700;">
                                    <div class="input-group-append">
                                        <button type="button" id="wdSendOtpBtn" class="btn btn-success" style="min-width:130px;">
                                            <i class="anticon anticon-mail mr-1"></i>Send OTP
                                        </button>
                                    </div>
                                </div>
                                <div class="text-right mt-2">
                                    <button type="button" id="wdVerifyOtpBtn" class="btn btn-outline-success" disabled>
                                        <i class="anticon anticon-check-circle mr-1"></i>Verify OTP
                                    </button>
                                </div>
                                <small class="form-text text-muted mt-1">OTP is valid for 5 minutes.</small>
                            </div>
                        </div>
                    </div><!-- /wdStep3 -->
                </div><!-- /modal-body -->

                <div class="modal-footer border-0 bg-light" style="border-radius:0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id="wdBackBtn" class="btn btn-outline-secondary" style="display:none;">
                        <i class="anticon anticon-left mr-1"></i>Back
                    </button>
                    <button type="button" id="wdNextBtn" class="btn btn-success" style="background:linear-gradient(135deg,#28a745,#20c997);border:none;font-weight:600;">
                        Next <i class="anticon anticon-right ml-1"></i>
                    </button>
                    <button type="submit" id="wdSubmitBtn" class="btn btn-success" disabled style="background:linear-gradient(135deg,#28a745,#20c997);border:none;font-weight:600;display:none;">
                        <i class="anticon anticon-send mr-1"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function () {
    var wdCurrentStep = 1;

    function goToWdStep(step) {
        wdCurrentStep = step;
        $('#wdStep1, #wdStep2, #wdStep3').hide();
        $('#wdStep' + step).show();

        if (step === 1) {
            $('#wdBackBtn').hide();
            $('#wdNextBtn').show();
            $('#wdSubmitBtn').hide().prop('disabled', true);
        } else if (step === 2) {
            $('#wdBackBtn').show();
            $('#wdNextBtn').show();
            $('#wdSubmitBtn').hide().prop('disabled', true);
        } else if (step === 3) {
            $('#wdBackBtn').show();
            $('#wdNextBtn').hide();
            $('#wdSubmitBtn').show();
        }

        // Step indicator
        var activeColor = 'linear-gradient(135deg,#28a745,#20c997)';
        var inactiveColor = '#dee2e6';
        for (var i = 1; i <= 3; i++) {
            if (i < step) {
                $('#wd-circle-' + i).css('background', activeColor).css('color', '#fff').html('<i class="anticon anticon-check" style="font-size:13px;"></i>');
                $('#wd-label-' + i).css('color', '#28a745');
                if (i < 3) $('#wd-bar-' + i).css('background', activeColor);
            } else if (i === step) {
                $('#wd-circle-' + i).css('background', activeColor).css('color', '#fff').text(i);
                $('#wd-label-' + i).css('color', '#28a745');
            } else {
                $('#wd-circle-' + i).css('background', inactiveColor).css('color', '#6c757d').text(i);
                $('#wd-label-' + i).css('color', '#6c757d');
                if (i < 3) $('#wd-bar-' + i).css('background', inactiveColor);
            }
        }
    }

    $('#wdNextBtn').click(function () {
        if (wdCurrentStep === 1) {
            var amount = parseFloat($('#wdAmount').val()) || 0;
            var available = parseFloat($('#wdAvailableBalance').val()) || 0;
            if (!amount || amount < 10) {
                toastr.error('Minimum withdrawal: $10.00');
                return;
            }
            if (amount > available) {
                toastr.error('Insufficient balance. Available: $' + available.toFixed(2));
                return;
            }
            goToWdStep(2);
        } else if (wdCurrentStep === 2) {
            if (!$('#wdMethod').val()) {
                toastr.error('Please select a withdrawal method.');
                return;
            }
            if (!$('#wdPaymentDetails').val().trim()) {
                toastr.error('Please enter your payment details.');
                return;
            }
            if (!$('#wdConfirmDetails').is(':checked')) {
                toastr.error('Please confirm your payment details are accurate.');
                return;
            }
            goToWdStep(3);
        }
    });

    $('#wdBackBtn').click(function () {
        if (wdCurrentStep > 1) goToWdStep(wdCurrentStep - 1);
    });

    // OTP Send
    $('#wdSendOtpBtn').click(function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i>');
        $.post('ajax/otp-handler.php', {
            action: 'send',
            csrf_token: $('input[name="csrf_token"]').val() || $('meta[name="csrf-token"]').attr('content')
                $('#wdOtpCode').prop('disabled', false).focus();
                $('#wdVerifyOtpBtn').prop('disabled', false);
            } else {
                toastr.error(r.message || 'Failed to send OTP');
            }
        }, 'json').fail(function () {
            toastr.error('Failed to send OTP');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="anticon anticon-mail mr-1"></i>Send OTP');
        });
    });

    // OTP Verify
    $('#wdVerifyOtpBtn').click(function () {
        var code = $('#wdOtpCode').val().trim();
        if (!code) { toastr.error('Please enter the OTP code.'); return; }
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i>');
        $.post('ajax/otp-handler.php', {
            action: 'verify',
            otp_code: code,
            csrf_token: $('input[name="csrf_token"]').val() || $('meta[name="csrf-token"]').attr('content')
                $('#wdSubmitBtn').prop('disabled', false);
                $('#wdOtpCode, #wdSendOtpBtn, #wdVerifyOtpBtn').prop('disabled', true);
            } else {
                toastr.error(r.message || 'Invalid OTP');
            }
        }, 'json').fail(function () {
            toastr.error('OTP verification failed');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="anticon anticon-check-circle mr-1"></i>Verify OTP');
        });
    });

    // Reset on modal close
    $('#newWithdrawalModal').on('hidden.bs.modal', function () {
        goToWdStep(1);
        $('#withdrawalForm')[0].reset();
        $('#wdOtpCode').val('').prop('disabled', true);
        $('#wdSendOtpBtn').prop('disabled', false);
        $('#wdVerifyOtpBtn').prop('disabled', true);
        $('#wdSubmitBtn').prop('disabled', true);
    });
});
</script>

<!-- Withdrawal Details Modal -->
<div class="modal fade" id="withdrawalDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div id="withdrawalDetailsContent"></div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
