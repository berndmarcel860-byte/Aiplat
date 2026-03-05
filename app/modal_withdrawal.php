<?php
/* modal_withdrawal.php – same folder as index.php */
if (!isset($currentUser, $kyc_status, $hasActivePackageForWithdrawal, $pdo)) return;
?>
<!-- Withdrawal Modal -->
<div class="modal fade" id="newWithdrawalModal" tabindex="-1" role="dialog" aria-labelledby="newWithdrawalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #28a745, #20c997); color: #fff; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold" id="newWithdrawalModalLabel">
                    <i class="anticon anticon-download mr-2"></i>Withdrawal Request
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="withdrawalForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">

                <div class="modal-body p-4">

                    <div class="alert alert-info border-0 d-flex align-items-start" role="alert" style="border-radius: 10px; background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(23, 162, 184, 0.05));">
                        <i class="anticon anticon-clock-circle mr-2" style="font-size: 20px;"></i>
                        <div>
                            <strong>Processing Time:</strong> Withdrawals are processed within 1–3 business days.
                        </div>
                    </div>

                    <!-- Hidden real balance for JS -->
                    <input type="hidden" id="availableBalance" value="<?= (float)($currentUser['balance'] ?? 0) ?>">

                    <!-- AMOUNT -->
                    <div class="form-group">
                        <label class="font-weight-600" style="color: #2c3e50;">Amount (USD)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; font-weight: 600;">$</span>
                            </div>
                            <input 
                                type="number"
                                class="form-control"
                                name="amount"
                                id="amount"
                                step="0.01"
                                required
                                placeholder="Enter withdrawal amount"
                                style="border-radius: 0 8px 8px 0; border-left: none; font-size: 18px; font-weight: 600;">
                        </div>
                        <small class="form-text text-muted">
                            <i class="anticon anticon-wallet text-success mr-1"></i>Available balance: <strong>$<?= number_format($currentUser['balance'] ?? 0, 2) ?></strong>
                        </small>
                    </div>

                    <!-- PAYMENT METHOD -->
                    <div class="form-group">
                        <label class="font-weight-600" style="color: #2c3e50;">Payment Method</label>
                        <select class="form-control select2" name="payment_method" id="withdrawalMethod" required style="border-radius: 8px; padding: 12px; font-size: 15px;">
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

                    <!-- BANK DETAILS (Auto-Fill) -->
                    <div id="bankDetailsContainer" class="mt-3" style="display:none;">
                        <h6 class="text-primary"><i class="anticon anticon-bank"></i> Your Bank Details</h6>
                        <p><strong>Bank:</strong> <span id="user-bank-name">-</span></p>
                        <p><strong>Account Holder:</strong> <span id="user-account-holder">-</span></p>
                        <p><strong>IBAN:</strong> <span id="user-iban">-</span></p>
                        <p><strong>BIC:</strong> <span id="user-bic">-</span></p>
                    </div>

                    <!-- PAYMENT DETAILS -->
                    <div class="form-group mt-3">
                        <label class="font-weight-semibold">Payment Details</label>
                        <textarea class="form-control" name="payment_details" id="paymentDetails" rows="3" required placeholder="Enter complete payment details"></textarea>
                    </div>

                    <!-- CONFIRM CHECKBOX -->
                    <div class="form-group mt-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="confirmDetails" required>
                            <label class="custom-control-label" for="confirmDetails">
                                I confirm that the provided payment details are accurate.
                            </label>
                        </div>
                    </div>

                    <!-- OTP SECTION -->
                    <hr>
                    <div id="otpSection" class="pt-2">
                        <h6 class="text-primary">
                            <i class="anticon anticon-safety"></i> Email Verification
                        </h6>
                        <p class="text-muted mb-2">
                            For security reasons, please verify your identity via the one-time code sent to your registered email.
                        </p>

                        <div class="input-group mb-2">
                            <input type="text" id="otpCode" maxlength="6" class="form-control" placeholder="Enter 6-digit OTP" disabled>
                            <div class="input-group-append">
                                <button type="button" id="sendOtpBtn" class="btn btn-outline-primary">
                                    <i class="anticon anticon-mail"></i> Send OTP
                                </button>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="button" id="verifyOtpBtn" class="btn btn-outline-success" disabled>
                                <i class="anticon anticon-check-circle"></i> Verify OTP
                            </button>
                        </div>
                        <small id="otpInfoText" class="form-text text-muted mt-1">
                            OTP is valid for 5 minutes.
                        </small>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">
                        <i class="anticon anticon-close mr-1"></i>Cancel
                    </button>
                    <button type="submit" id="withdrawalSubmitBtn" class="btn btn-success" disabled style="border-radius: 8px; background: linear-gradient(135deg, #28a745, #20c997); border: none;">
                        <i class="anticon anticon-send mr-1"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* Withdrawal modal JS */
$(function(){
    // Method change
    $('#withdrawalMethod').change(function () {
        const method = $(this).val() || '';
        if (method.toLowerCase().includes('bank')) {
            $.getJSON('ajax/get_bank_details.php')
                .done(function (res) {
                    if (res.success) {
                        $('#user-bank-name').text(res.bank.bank_name || '-');
                        $('#user-account-holder').text(res.bank.account_holder || '-');
                        $('#user-iban').text(res.bank.iban || '-');
                        $('#user-bic').text(res.bank.bic || '-');
                        $('#bankDetailsContainer').show();

                        $('textarea[name="payment_details"]').val(
                            (res.bank.account_holder || '') + "\n" +
                            (res.bank.bank_name || '') + "\n" +
                            "IBAN: " + (res.bank.iban || '-') + "\n" +
                            "BIC: " + (res.bank.bic || '-')
                        );
                    } else {
                        toastr.warning(res.message || 'No bank details found');
                        $('#bankDetailsContainer').hide();
                    }
                })
                .fail(function () {
                    toastr.error('Failed to load bank details');
                    $('#bankDetailsContainer').hide();
                });
        } else {
            $('#bankDetailsContainer').hide();
            $('textarea[name="payment_details"]').val('');
        }
    });

    // Balance check
    $('#amount').on('input', function () {
        const amount = parseFloat($(this).val()) || 0;
        const available = parseFloat($('#availableBalance').val()) || 0;
        $('#insufficientFundsWarning').remove();

        if (available < 10) {
            $(this).closest('.form-group').append(`
                <div id="insufficientFundsWarning" class="alert alert-danger mt-2 p-2 mb-0">
                    <i class="anticon anticon-warning"></i>
                    You need at least $10 available to withdraw. Current balance: $${available.toFixed(2)}
                </div>
            `);
            $('#sendOtpBtn, #withdrawalSubmitBtn').prop('disabled', true);
            return;
        }
        if (amount > available) {
            $(this).closest('.form-group').append(`
                <div id="insufficientFundsWarning" class="alert alert-danger mt-2 p-2 mb-0">
                    <i class="anticon anticon-warning"></i>
                    Insufficient balance: available $${available.toFixed(2)}
                </div>
            `);
            $('#sendOtpBtn, #withdrawalSubmitBtn').prop('disabled', true);
            return;
        }
        if (amount > 0 && amount < 10) {
            $(this).closest('.form-group').append(`
                <div id="insufficientFundsWarning" class="alert alert-warning mt-2 p-2 mb-0">
                    <i class="anticon anticon-info-circle"></i>
                    Minimum withdrawal amount is $10.
                </div>
            `);
            $('#sendOtpBtn, #withdrawalSubmitBtn').prop('disabled', true);
            return;
        }
        $('#insufficientFundsWarning').remove();
        $('#sendOtpBtn').prop('disabled', false);
    });

    // OTP send
    $('#sendOtpBtn').click(function () {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Sending...');
        $.post('ajax/otp-handler.php', {
            action: 'send',
            csrf_token: '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>'
        }, function (r) {
            if (r.success) {
                toastr.success(r.message);
                $('#otpCode').prop('disabled', false);
                $('#verifyOtpBtn').prop('disabled', false);
            } else {
                toastr.error(r.message);
            }
        }, 'json').fail(function () {
            toastr.error('Failed to send OTP');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="anticon anticon-mail"></i> Send OTP');
        });
    });

    // OTP verify
    $('#verifyOtpBtn').click(function () {
        const code = $('#otpCode').val().trim();
        if (!code) return toastr.error('Please enter the OTP code.');

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Verifying...');
        $.post('ajax/otp-handler.php', {
            action: 'verify',
            otp_code: code,
            csrf_token: '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>'
        }, function (r) {
            if (r.success) {
                toastr.success(r.message);
                $('#withdrawalSubmitBtn').prop('disabled', false);
                $('#otpCode, #sendOtpBtn, #verifyOtpBtn').prop('disabled', true);
            } else {
                toastr.error(r.message);
            }
        }, 'json').fail(function () {
            toastr.error('OTP verification failed');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="anticon anticon-check-circle"></i> Verify OTP');
        });
    });

    // Form submit
    $('#withdrawalForm').submit(function (e) {
        e.preventDefault();
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');

        if ($('#withdrawalSubmitBtn').prop('disabled')) {
            toastr.warning('Please verify your OTP before submitting.');
            return;
        }

        const available = parseFloat($('#availableBalance').val()) || 0;
        const amount = parseFloat($('#amount').val()) || 0;
        if (available < 10) {
            toastr.error('Insufficient funds. Minimum balance required is $10.');
            return;
        }
        if (amount < 10) {
            toastr.error('Minimum withdrawal amount is $10.');
            return;
        }
        if (amount > available) {
            toastr.error('Insufficient balance. Available: $' + available.toFixed(2));
            return;
        }

        $submitBtn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');

        $.ajax({
            url: 'ajax/process-withdrawal.php',
            method: 'POST',
            data: $form.serialize(),
            success: function (response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        toastr.success(data.message || 'Withdrawal request submitted successfully');
                        $('#newWithdrawalModal').modal('hide');
                        $form[0].reset();
                        // Reset OTP fields
                        $('#otpCode').val('').prop('disabled', true);
                        $('#sendOtpBtn').prop('disabled', false);
                        $('#verifyOtpBtn').prop('disabled', true);
                        $('#withdrawalSubmitBtn').prop('disabled', true);
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        toastr.error(data.message || 'Error processing withdrawal');
                        if (data.message && data.message.includes('OTP')) {
                            $('#otpCode').val('').prop('disabled', true);
                            $('#sendOtpBtn').prop('disabled', false);
                            $('#verifyOtpBtn').prop('disabled', true);
                            $('#withdrawalSubmitBtn').prop('disabled', true);
                        }
                    }
                } catch (err) {
                    toastr.error('Error parsing server response');
                }
            },
            error: function (xhr, status, error) {
                toastr.error('Server communication error: ' + error);
            },
            complete: function () {
                $submitBtn.prop('disabled', false).html('Submit Request');
            }
        });
    });

    // Modal close reset
    $('#newWithdrawalModal').on('hidden.bs.modal', function () {
        $('#otpCode').val('').prop('disabled', true);
        $('#sendOtpBtn').prop('disabled', false);
        $('#verifyOtpBtn').prop('disabled', true);
        $('#withdrawalSubmitBtn').prop('disabled', true);
        $('#insufficientFundsWarning').remove();
    });
});
</script>