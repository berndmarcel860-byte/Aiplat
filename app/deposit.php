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
                            <!-- Escrow Trust Info Bar -->
                        <div class="escrow-info-bar mb-3 p-3" style="border-radius:10px;background:linear-gradient(90deg,#0f4c81 0%,#1a6b3a 100%);color:#fff;display:flex;align-items:center;gap:12px;">
                            <span style="font-size:24px;">🔒</span>
                            <div>
                                <strong style="font-size:14px;">Treuhand-Zahlungsschutz</strong>
                                <div style="font-size:12px;opacity:.9;">Alle ausstehenden Einzahlungen werden sicher auf Treuhandkonten gehalten, bis sie verifiziert und freigegeben werden.</div>
                            </div>
                            <div class="ml-auto d-none d-md-flex align-items-center" style="gap:8px;white-space:nowrap;">
                                <span class="badge" style="background:rgba(255,255,255,0.2);color:#fff;font-size:11px;padding:5px 10px;">🏦 Holding</span>
                                <span style="opacity:.7;">→</span>
                                <span class="badge" style="background:rgba(255,255,255,0.2);color:#fff;font-size:11px;padding:5px 10px;">✅ Verifiziert</span>
                                <span style="opacity:.7;">→</span>
                                <span class="badge" style="background:rgba(255,255,255,0.2);color:#fff;font-size:11px;padding:5px 10px;">🎯 Freigegeben</span>
                            </div>
                        </div>
                        <table id="depositsTable" class="table table-bordered nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Escrow</th>
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

<!-- Deposit Detail Modal -->
<div class="modal fade" id="depositDetailModal" tabindex="-1" role="dialog" aria-labelledby="depositDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px;">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border-radius:12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold" id="depositDetailLabel">
                    <i class="anticon anticon-file-text mr-2"></i>Einzahlungs-Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4" id="depositDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading…</span></div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light" style="border-radius:0 0 12px 12px;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<script>
// Deposit detail row-click handler
document.addEventListener('DOMContentLoaded', function () {
    var escrowLabels = {
        'holding':   { cls: 'info',      icon: '🏦', text: 'In Treuhand',   desc: 'Ihre Zahlung wird sicher gehalten.' },
        'verified':  { cls: 'primary',   icon: '✅', text: 'Verifiziert',   desc: 'Zahlung wurde verifiziert.' },
        'released':  { cls: 'success',   icon: '🎯', text: 'Freigegeben',   desc: 'Treuhandmittel wurden auf Ihr Konto freigegeben.' },
        'refunded':  { cls: 'warning',   icon: '↩️', text: 'Erstattet',     desc: 'Zahlung wurde erstattet.' },
        'cancelled': { cls: 'secondary', icon: '❌', text: 'Storniert',     desc: 'Treuhandauftrag wurde storniert.' }
    };

    $(document).on('click', '#depositsTable tbody tr', function () {
        var data = window._depositsTable ? window._depositsTable.row(this).data() : null;
        if (!data || !data.reference) return;
        openDepositDetail(data.reference);
    });

    window.openDepositDetail = function (reference) {
        $('#depositDetailBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading…</span></div></div>');
        $('#depositDetailModal').modal('show');

        $.ajax({
            url: 'ajax/get_deposit_detail.php',
            type: 'GET',
            data: { reference: reference },
            success: function (resp) {
                if (!resp.success) {
                    $('#depositDetailBody').html('<div class="alert alert-danger">' + (resp.message || 'Fehler beim Laden') + '</div>');
                    return;
                }
                var d = resp.deposit;
                var escrow = escrowLabels[d.escrow_status] || null;

                // Deposit status badge
                var depCls = { pending: 'warning', completed: 'success', failed: 'danger', approved: 'success', rejected: 'danger' }[d.status] || 'secondary';
                var depStatus = '<span class="badge badge-' + depCls + '">' + (d.status ? d.status.charAt(0).toUpperCase() + d.status.slice(1) : '–') + '</span>';

                // Escrow block
                var escrowBlock = '';
                if (d.escrow_reference) {
                    var eInfo = escrow || { cls: 'secondary', icon: '❓', text: d.escrow_status || '–', desc: '' };
                    var escrowFlow = [
                        { icon: '💳', label: 'Gezahlt',    active: true },
                        { icon: '🏦', label: 'Treuhand',   active: ['holding','verified','released','refunded'].includes(d.escrow_status) },
                        { icon: '✅', label: 'Verifiziert', active: ['verified','released'].includes(d.escrow_status) },
                        { icon: '🎯', label: 'Freigegeben', active: d.escrow_status === 'released' }
                    ];
                    var stepHtml = escrowFlow.map(function (s) {
                        return '<div class="text-center" style="flex:1;">'
                            + '<div style="width:36px;height:36px;border-radius:50%;background:' + (s.active ? 'linear-gradient(135deg,#0f4c81,#1a6b3a)' : '#dee2e6') + ';display:flex;align-items:center;justify-content:center;margin:0 auto 4px;font-size:16px;">' + s.icon + '</div>'
                            + '<div style="font-size:10px;color:' + (s.active ? '#0f4c81' : '#6c757d') + ';font-weight:' + (s.active ? '700' : '400') + ';">' + s.label + '</div>'
                            + '</div>';
                    }).join('<div style="flex:0 0 14px;text-align:center;opacity:.5;margin-top:-12px;">→</div>');

                    escrowBlock = '<div class="mt-4 p-3" style="background:linear-gradient(135deg,#0f4c81,#1a6b3a);border-radius:12px;color:#fff;">'
                        + '<div class="d-flex align-items-center mb-2">'
                        + '  <span style="font-size:20px;margin-right:8px;">🔒</span>'
                        + '  <strong>Treuhand-Zahlungsschutz</strong>'
                        + '  <span class="badge badge-light ml-2" style="color:#0f4c81;font-size:10px;">' + eInfo.icon + ' ' + eInfo.text + '</span>'
                        + '</div>'
                        + '<p style="font-size:12px;opacity:.9;margin-bottom:10px;">' + eInfo.desc + '</p>'
                        + '<div class="d-flex align-items-center" style="gap:4px;">' + stepHtml + '</div>'
                        + '<div class="mt-2 d-flex justify-content-between" style="font-size:11px;opacity:.85;">'
                        + '  <span><strong>Treuhand-Ref:</strong> <code style="color:#fff;">' + d.escrow_reference + '</code></span>'
                        + (d.escrow_held_at ? '<span><strong>Gehalten seit:</strong> ' + new Date(d.escrow_held_at).toLocaleString('de-DE') + '</span>' : '')
                        + (d.escrow_released_at ? '<span><strong>Freigegeben:</strong> ' + new Date(d.escrow_released_at).toLocaleString('de-DE') + '</span>' : '')
                        + '</div>'
                        + '</div>';
                }

                var html = '<div class="row">'
                    + '<div class="col-md-6"><div class="form-group"><label class="font-weight-semibold text-muted" style="font-size:11px;text-transform:uppercase;">Referenz</label><p class="font-weight-bold">' + d.reference + '</p></div></div>'
                    + '<div class="col-md-6"><div class="form-group"><label class="font-weight-semibold text-muted" style="font-size:11px;text-transform:uppercase;">Betrag</label><p class="font-weight-bold" style="font-size:22px;">$' + parseFloat(d.amount).toFixed(2) + '</p></div></div>'
                    + '<div class="col-md-6"><div class="form-group"><label class="font-weight-semibold text-muted" style="font-size:11px;text-transform:uppercase;">Zahlungsmethode</label><p>' + (d.method_name || d.method_code || '–') + '</p></div></div>'
                    + '<div class="col-md-6"><div class="form-group"><label class="font-weight-semibold text-muted" style="font-size:11px;text-transform:uppercase;">Status</label><p>' + depStatus + '</p></div></div>'
                    + '<div class="col-md-6"><div class="form-group"><label class="font-weight-semibold text-muted" style="font-size:11px;text-transform:uppercase;">Datum</label><p>' + new Date(d.created_at).toLocaleString('de-DE') + '</p></div></div>'
                    + (d.processed_at ? '<div class="col-md-6"><div class="form-group"><label class="font-weight-semibold text-muted" style="font-size:11px;text-transform:uppercase;">Verarbeitet am</label><p>' + new Date(d.processed_at).toLocaleString('de-DE') + '</p></div></div>' : '')
                    + (d.admin_notes ? '<div class="col-12"><div class="form-group"><label class="font-weight-semibold text-muted" style="font-size:11px;text-transform:uppercase;">Admin-Notizen</label><p>' + d.admin_notes + '</p></div></div>' : '')
                    + '</div>'
                    + escrowBlock;

                $('#depositDetailBody').html(html);
            },
            error: function () {
                $('#depositDetailBody').html('<div class="alert alert-danger">Serverfehler beim Laden der Details.</div>');
            }
        });
    };
});
</script>



<!-- New Deposit Modal (3-step wizard) -->
<div class="modal fade" id="newDepositModal" tabindex="-1" role="dialog" aria-labelledby="newDepositModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px;">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border-radius:12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold" id="newDepositModalLabel">
                    <i class="anticon anticon-plus-circle mr-2"></i>New Deposit
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <!-- Step Indicator -->
            <div class="px-4 pt-3 pb-0" style="background:#fff;border-bottom:1px solid #f0f0f0;">
                <div class="d-flex align-items-center" style="padding-bottom:14px;">
                    <div class="d-flex flex-column align-items-center" style="flex:0 0 auto;">
                        <div id="dep-circle-1" style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;box-shadow:0 2px 6px rgba(41,80,168,0.3);">1</div>
                        <div id="dep-label-1" style="font-size:10px;color:#2950a8;font-weight:700;margin-top:4px;white-space:nowrap;">Amount</div>
                    </div>
                    <div id="dep-bar-1" style="flex:1;height:2px;background:#dee2e6;margin:0 6px 14px;"></div>
                    <div class="d-flex flex-column align-items-center" style="flex:0 0 auto;">
                        <div id="dep-circle-2" style="width:30px;height:30px;border-radius:50%;background:#dee2e6;color:#6c757d;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">2</div>
                        <div id="dep-label-2" style="font-size:10px;color:#6c757d;font-weight:600;margin-top:4px;white-space:nowrap;">Method</div>
                    </div>
                    <div id="dep-bar-2" style="flex:1;height:2px;background:#dee2e6;margin:0 6px 14px;"></div>
                    <div class="d-flex flex-column align-items-center" style="flex:0 0 auto;">
                        <div id="dep-circle-3" style="width:30px;height:30px;border-radius:50%;background:#dee2e6;color:#6c757d;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">3</div>
                        <div id="dep-label-3" style="font-size:10px;color:#6c757d;font-weight:600;margin-top:4px;white-space:nowrap;">Proof</div>
                    </div>
                </div>
            </div>
            <form id="depositForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">
                <div class="modal-body p-4">
                    <!-- STEP 1: Amount -->
                    <div id="depositStep1">
                        <!-- Escrow Protection Notice -->
                        <div class="mb-4 p-3" style="background:linear-gradient(135deg,#0f4c81,#1a6b3a);border-radius:12px;color:#fff;">
                            <div class="d-flex align-items-center mb-2">
                                <span style="font-size:22px;margin-right:10px;">🔒</span>
                                <strong style="font-size:15px;">Your Deposit is Protected by Escrow</strong>
                            </div>
                            <p style="font-size:13px;opacity:.95;margin-bottom:10px;">
                                We use a <strong>secure escrow system</strong> to guarantee your funds are always safe. Here is how it works:
                            </p>
                            <div class="d-flex flex-column" style="gap:8px;font-size:13px;">
                                <div class="d-flex align-items-start" style="gap:8px;">
                                    <span style="flex:0 0 22px;font-size:16px;">🏦</span>
                                    <span>Your deposit is <strong>held securely in escrow</strong> — it is never released to us until you confirm everything is in order.</span>
                                </div>
                                <div class="d-flex align-items-start" style="gap:8px;">
                                    <span style="flex:0 0 22px;font-size:16px;">✅</span>
                                    <span>Once you confirm that your <strong>withdrawal has been received</strong>, the funds are released from escrow to us.</span>
                                </div>
                                <div class="d-flex align-items-start" style="gap:8px;">
                                    <span style="flex:0 0 22px;font-size:16px;">↩️</span>
                                    <span>If your withdrawal is <strong>not received</strong> or there is any issue, your deposit is <strong>immediately refunded back to you</strong> from escrow — no questions asked.</span>
                                </div>
                            </div>
                            <div class="mt-3 pt-2" style="border-top:1px solid rgba(255,255,255,0.25);font-size:12px;opacity:.85;">
                                <i class="anticon anticon-safety-certificate mr-1"></i>
                                You are fully protected at every step. Your money only moves when <strong>you are satisfied</strong>.
                            </div>
                        </div>
                        <!-- /Escrow Protection Notice -->
                        <div class="form-group">
                            <label class="font-weight-600">Amount (USD)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;font-weight:600;">$</span>
                                </div>
                                <input type="number" class="form-control" id="depositAmount" name="amount" min="10" step="0.01" required placeholder="Enter deposit amount" style="border-radius:0 8px 8px 0;font-size:18px;font-weight:600;">
                            </div>
                            <small class="form-text text-muted">Minimum deposit: $10.00 | Processing fee: 0%</small>
                        </div>
                    </div><!-- /depositStep1 -->

                    <!-- STEP 2: Payment Method + Details -->
                    <div id="depositStep2" style="display:none;">
                        <div class="form-group">
                            <label class="font-weight-600">Payment Method</label>
                            <select class="form-control" name="payment_method" id="paymentMethod" required style="border-radius:8px;padding:10px;font-size:15px;">
                                <option value="">Select Payment Method</option>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE is_active = 1 AND allows_deposit = 1");
                                    $stmt->execute();
                                    while ($method = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $details = [
                                            'bank_name'      => $method['bank_name'] ?? '',
                                            'account_number' => $method['account_number'] ?? '',
                                            'routing_number' => $method['routing_number'] ?? '',
                                            'wallet_address' => $method['wallet_address'] ?? '',
                                            'instructions'   => $method['instructions'] ?? '',
                                            'is_crypto'      => $method['is_crypto'] ?? 0,
                                        ];
                                        $detailsJson = htmlspecialchars(json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                                        echo '<option value="' . htmlspecialchars($method['method_code'], ENT_QUOTES)
                                             . '" data-details=\'' . $detailsJson . '\'>'
                                             . htmlspecialchars($method['method_name'], ENT_QUOTES) . '</option>';
                                    }
                                } catch (Exception $e) {
                                    error_log("Payment methods load error: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                        <!-- Payment details (shown after method selected) -->
                        <div id="depPaymentDetails" class="mt-3" style="display:none;">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white"><h6 class="mb-0">Payment Instructions</h6></div>
                                <div class="card-body">
                                    <div id="depBankDetails" style="display:none;">
                                        <h6 class="text-primary"><i class="anticon anticon-bank"></i> Bank Transfer Details</h6>
                                        <p><strong>Bank Name:</strong> <span id="dep-bank-name">-</span></p>
                                        <p><strong>Account Number:</strong> <span id="dep-account-number">-</span></p>
                                        <p><strong>Routing Number:</strong> <span id="dep-routing-number">-</span></p>
                                        <div class="alert alert-warning mt-2"><strong>Note:</strong> Include your User ID as reference</div>
                                    </div>
                                    <div id="depCryptoDetails" style="display:none;">
                                        <h6 class="text-primary"><i class="anticon anticon-block"></i> Crypto Wallet</h6>
                                        <p><strong>Wallet Address:</strong></p>
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" id="dep-wallet-address" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" id="depCopyWallet">
                                                    <i class="anticon anticon-copy"></i> Copy
                                                </button>
                                            </div>
                                        </div>
                                        <div class="alert alert-danger"><strong>Important:</strong> Send only the specified cryptocurrency.</div>
                                    </div>
                                    <div id="depGeneralInstructions" style="display:none;">
                                        <h6 class="text-primary">Additional Instructions</h6>
                                        <div id="dep-instructions"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /depositStep2 -->

                    <!-- STEP 3: Proof of Payment + Submit -->
                    <div id="depositStep3" style="display:none;">
                        <div class="form-group">
                            <label class="font-weight-semibold">Proof of Payment</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="proofOfPayment" name="proof_of_payment" accept="image/*,.pdf" required>
                                <label class="custom-file-label" for="proofOfPayment">Choose screenshot or PDF</label>
                            </div>
                            <small class="form-text text-muted">Accepted: JPG, PNG, PDF (Max 2MB)</small>
                        </div>
                    </div><!-- /depositStep3 -->
                </div><!-- /modal-body -->

                <div class="modal-footer border-0 bg-light" style="border-radius:0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id="depositBackBtn" class="btn btn-outline-secondary" style="display:none;">
                        <i class="anticon anticon-left mr-1"></i>Back
                    </button>
                    <button type="button" id="depositNextBtn" class="btn btn-primary" style="background:linear-gradient(135deg,#2950a8,#2da9e3);border:none;">
                        Next <i class="anticon anticon-right ml-1"></i>
                    </button>
                    <button type="submit" id="depositSubmitBtn" class="btn btn-primary" style="background:linear-gradient(135deg,#2950a8,#2da9e3);border:none;display:none;">
                        <i class="anticon anticon-check-circle mr-1"></i>Confirm Deposit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var depCurrentStep = 1;

    function goToDepStep(step) {
        depCurrentStep = step;
        $('#depositStep1, #depositStep2, #depositStep3').hide();
        $('#depositStep' + step).show();

        // Buttons
        if (step === 1) {
            $('#depositBackBtn').hide();
            $('#depositNextBtn').show();
            $('#depositSubmitBtn').hide();
        } else if (step === 2) {
            $('#depositBackBtn').show();
            $('#depositNextBtn').show();
            $('#depositSubmitBtn').hide();
        } else if (step === 3) {
            $('#depositBackBtn').show();
            $('#depositNextBtn').hide();
            $('#depositSubmitBtn').show();
        }

        // Step indicator
        var activeColor = 'linear-gradient(135deg,#2950a8,#2da9e3)';
        var inactiveColor = '#dee2e6';
        for (var i = 1; i <= 3; i++) {
            if (i < step) {
                $('#dep-circle-' + i).css('background', activeColor).css('color', '#fff').html('<i class="anticon anticon-check" style="font-size:13px;"></i>');
                $('#dep-label-' + i).css('color', '#2950a8');
                if (i < 3) $('#dep-bar-' + i).css('background', activeColor);
            } else if (i === step) {
                $('#dep-circle-' + i).css('background', activeColor).css('color', '#fff').text(i);
                $('#dep-label-' + i).css('color', '#2950a8');
            } else {
                $('#dep-circle-' + i).css('background', inactiveColor).css('color', '#6c757d').text(i);
                $('#dep-label-' + i).css('color', '#6c757d');
                if (i < 3) $('#dep-bar-' + i).css('background', inactiveColor);
            }
        }
    }

    $('#depositNextBtn').click(function () {
        if (depCurrentStep === 1) {
            var amount = parseFloat($('#depositAmount').val()) || 0;
            if (!amount || amount < 10) {
                toastr.error('Minimum deposit: $10.00');
                return;
            }
            goToDepStep(2);
        } else if (depCurrentStep === 2) {
            var method = $('#paymentMethod').val();
            if (!method) {
                toastr.error('Please select a payment method.');
                return;
            }
            goToDepStep(3);
        }
    });

    $('#depositBackBtn').click(function () {
        if (depCurrentStep > 1) goToDepStep(depCurrentStep - 1);
    });

    // Payment method change → show details
    $('#paymentMethod').on('change', function () {
        var selectedOption = $(this).find('option:selected');
        var details = selectedOption.data('details');
        if (!details) {
            $('#depPaymentDetails').hide();
            return;
        }
        if (typeof details === 'string') {
            try { details = JSON.parse(details); } catch (e) { return; }
        }
        $('#depBankDetails, #depCryptoDetails, #depGeneralInstructions').hide();
        if (details.bank_name) {
            $('#dep-bank-name').text(details.bank_name);
            $('#dep-account-number').text(details.account_number || '-');
            $('#dep-routing-number').text(details.routing_number || '-');
            $('#depBankDetails').show();
        }
        if (details.wallet_address) {
            $('#dep-wallet-address').val(details.wallet_address);
            $('#depCryptoDetails').show();
        }
        if (details.instructions) {
            $('#dep-instructions').text(details.instructions);
            $('#depGeneralInstructions').show();
        }
        $('#depPaymentDetails').show();
    });

    // Copy wallet address
    $('#depCopyWallet').click(function () {
        var addr = $('#dep-wallet-address').val();
        if (!addr) { toastr.warning('No address to copy'); return; }
        navigator.clipboard.writeText(addr).then(function () {
            toastr.success('Wallet address copied');
        });
    });

    // File input label
    $('#proofOfPayment').on('change', function () {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Choose file');
    });

    // Reset on modal close
    $('#newDepositModal').on('hidden.bs.modal', function () {
        goToDepStep(1);
        $('#depositForm')[0].reset();
        $('#depPaymentDetails').hide();
        $('.custom-file-label').html('Choose screenshot or PDF');
    });

    // Note: form submission is handled by deposits.js on this page
    // Just ensure the submit button works with the step wizard
    $('#depositForm').on('submit', function (e) {
        // Validate that file is selected (step 3)
        var fileInput = document.getElementById('proofOfPayment');
        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            e.preventDefault();
            toastr.error('Please upload proof of payment');
            return false;
        }
    });
});
</script>

<?php include 'footer.php'; ?>
