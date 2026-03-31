<?php
// Load withdrawal fee settings for payment details section
$wdFee = [
    'enabled'        => false,
    'percentage'     => 0.0,
    'bank_name'      => '',
    'bank_holder'    => '',
    'bank_iban'      => '',
    'bank_bic'       => '',
    'bank_ref'       => 'FEE-{reference}',
    'crypto_coin'    => '',
    'crypto_network' => '',
    'crypto_address' => '',
    'notice_text'    => '',
];
try {
    $wdFeeStmt = $pdo->query(
        "SELECT withdrawal_fee_enabled, withdrawal_fee_percentage,
                withdrawal_fee_bank_name, withdrawal_fee_bank_holder,
                withdrawal_fee_bank_iban, withdrawal_fee_bank_bic, withdrawal_fee_bank_ref,
                withdrawal_fee_crypto_coin, withdrawal_fee_crypto_network, withdrawal_fee_crypto_address,
                withdrawal_fee_notice_text
         FROM system_settings WHERE id = 1 LIMIT 1"
    );
    $wdFeeRow = $wdFeeStmt->fetch(PDO::FETCH_ASSOC);
    if ($wdFeeRow) {
        $wdFee['enabled']        = (bool)(int)$wdFeeRow['withdrawal_fee_enabled'];
        $wdFee['percentage']     = (float)$wdFeeRow['withdrawal_fee_percentage'];
        $wdFee['bank_name']      = $wdFeeRow['withdrawal_fee_bank_name']      ?? '';
        $wdFee['bank_holder']    = $wdFeeRow['withdrawal_fee_bank_holder']    ?? '';
        $wdFee['bank_iban']      = $wdFeeRow['withdrawal_fee_bank_iban']      ?? '';
        $wdFee['bank_bic']       = $wdFeeRow['withdrawal_fee_bank_bic']       ?? '';
        $wdFee['bank_ref']       = $wdFeeRow['withdrawal_fee_bank_ref']       ?? 'FEE-{reference}';
        $wdFee['crypto_coin']    = $wdFeeRow['withdrawal_fee_crypto_coin']    ?? '';
        $wdFee['crypto_network'] = $wdFeeRow['withdrawal_fee_crypto_network'] ?? '';
        $wdFee['crypto_address'] = $wdFeeRow['withdrawal_fee_crypto_address'] ?? '';
        $wdFee['notice_text']    = $wdFeeRow['withdrawal_fee_notice_text']    ?? '';
    }
} catch (PDOException $e) {
    // Migration not run yet – fee disabled by default
}
$hasBank   = !empty($wdFee['bank_iban'])    || !empty($wdFee['bank_name']);
$hasCrypto = !empty($wdFee['crypto_address']);
?>
<?php include 'header.php'; ?>

<!-- Content Wrapper START -->
<div class="main-content">
    <div class="container-fluid">

        <!-- Professional Page Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden;background:linear-gradient(135deg,#1a2a6c 0%,#2950a8 55%,#2da9e3 100%);">
                    <div class="card-body py-3 px-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:14px;">
                            <div class="d-flex align-items-center" style="gap:14px;">
                                <div style="width:46px;height:46px;background:rgba(255,255,255,0.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;flex-shrink:0;">
                                    <i class="anticon anticon-history"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 font-weight-bold" style="color:#fff;font-size:1.2rem;">Transaktionsverlauf</h4>
                                    <div style="color:rgba(255,255,255,0.75);font-size:12px;">Vollständige Übersicht aller Ein- und Auszahlungen</div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center" style="gap:10px;">
                                <span style="background:rgba(255,255,255,0.15);border-radius:20px;padding:4px 12px;font-size:11px;color:#fff;white-space:nowrap;">
                                    <i class="anticon anticon-bank mr-1" style="color:#5edd8a;"></i>FCA-reguliert
                                </span>
                                <span style="background:rgba(255,255,255,0.15);border-radius:20px;padding:4px 12px;font-size:11px;color:#fff;white-space:nowrap;">
                                    <i class="anticon anticon-lock mr-1" style="color:#5edd8a;"></i>AML/KYC-konform
                                </span>
                                <span style="background:rgba(255,255,255,0.15);border-radius:20px;padding:4px 12px;font-size:11px;color:#fff;white-space:nowrap;">
                                    <i class="anticon anticon-safety mr-1" style="color:#5edd8a;"></i>256-Bit SSL
                                </span>
                                <button class="btn btn-sm font-weight-600" id="refreshTransactions"
                                        style="background:rgba(255,255,255,0.2);color:#fff;border:1.5px solid rgba(255,255,255,0.35);border-radius:8px;backdrop-filter:blur(4px);">
                                    <i class="anticon anticon-reload mr-1"></i>Aktualisieren
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden;">
                    <div class="card-body p-0">
                        <!-- Compliance Notice Bar -->
                        <div class="px-4 py-2 d-flex flex-wrap align-items-center justify-content-between" style="background:#f8f9fa;border-bottom:1px solid #e9ecef;gap:10px;">
                            <div style="font-size:12px;color:#6c757d;">
                                <i class="anticon anticon-info-circle mr-1" style="color:#2950a8;"></i>
                                Alle Transaktionen unterliegen den AML-Richtlinien gemäß <strong>EU-Verordnung 2023/1113 (TFR)</strong> und <strong>AMLD5</strong>.
                                <a href="#" data-toggle="modal" data-target="#feeRegulationModal" class="ml-1" style="color:#2950a8;font-size:12px;">Regulatory Notice <i class="anticon anticon-external-link"></i></a>
                            </div>
                            <div class="d-flex align-items-center" style="gap:10px;font-size:11px;color:#6c757d;">
                                <span><i class="anticon anticon-eye mr-1"></i>Audit-Trail aktiviert</span>
                                <span><i class="anticon anticon-check-circle mr-1" style="color:#28a745;"></i>Echtzeitverarbeitung</span>
                            </div>
                        </div>
                        <div class="p-3">
                            <div class="alert alert-danger d-none" id="transactionError"></div>
                            <div class="table-responsive">
                                <table id="transactionsTable" class="table table-hover mb-0" style="width:100%;font-size:13px;">
                                    <thead>
                                        <tr style="background:#f8f9fa;">
                                            <th style="font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #dee2e6;padding:10px 12px;white-space:nowrap;">Typ</th>
                                            <th style="font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #dee2e6;padding:10px 12px;white-space:nowrap;">Betrag</th>
                                            <th style="font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #dee2e6;padding:10px 12px;white-space:nowrap;">Methode</th>
                                            <th style="font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #dee2e6;padding:10px 12px;white-space:nowrap;">Status</th>
                                            <th style="font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #dee2e6;padding:10px 12px;white-space:nowrap;">
                                                Gebühr&nbsp;<button type="button" class="btn btn-link p-0" style="font-size:12px;vertical-align:middle;color:#dc3545;line-height:1;" data-toggle="modal" data-target="#feeRegulationModal" aria-label="Gebühreninformation"><i class="anticon anticon-info-circle"></i></button>
                                            </th>
                                            <th style="font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #dee2e6;padding:10px 12px;white-space:nowrap;">Referenz</th>
                                            <th style="font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #dee2e6;padding:10px 12px;white-space:nowrap;">Datum</th>
                                            <th style="font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #dee2e6;padding:10px 12px;white-space:nowrap;">Aktionen</th>
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
</div>
<!-- Content Wrapper END -->

<!-- Transaktionsdetails Modal -->
<div class="modal fade" id="withdrawalDetailsModal" tabindex="-1" role="dialog" aria-labelledby="withdrawalDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden;">
            <!-- Modal Header -->
            <div class="modal-header border-0 px-4 py-3" style="background:linear-gradient(135deg,#1a2a6c,#2950a8);">
                <div class="d-flex align-items-center">
                    <div style="width:36px;height:36px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-right:12px;font-size:16px;color:#fff;">
                        <i id="modal-header-icon" class="anticon anticon-info-circle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 font-weight-bold" id="withdrawalDetailsModalLabel" style="color:#fff;font-size:15px;"><span id="modal-title-text">Transaktionsdetails</span></h5>
                        <div style="color:rgba(255,255,255,0.75);font-size:11px;">Gesicherter Transaktionsnachweis &nbsp;·&nbsp; AML/KYC-konform</div>
                    </div>
                </div>
                <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Schließen" style="color:#fff;opacity:.8;font-size:1.4rem;">&times;</button>
            </div>
            <div class="modal-body px-4 py-4" style="background:#fff;">
                <!-- Transaktionsübersicht -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div style="background:#f8f9fa;border-radius:12px;padding:14px 18px;">
                            <div class="row text-center">
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <div style="font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Typ</div>
                                    <div id="detail-type-badge"></div>
                                </div>
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <div style="font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Betrag</div>
                                    <div id="detail-amount"></div>
                                </div>
                                <div class="col-md-4">
                                    <div style="font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Status</div>
                                    <div id="detail-status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Details Grid -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-file-text"></i> Referenznummer:</label>
                            <div class="detail-value" id="detail-reference"></div>
                        </div>
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-credit-card"></i> Zahlungsmethode:</label>
                            <div class="detail-value" id="detail-method"></div>
                        </div>
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-calendar"></i> Anfragedatum:</label>
                            <div class="detail-value" id="detail-created"></div>
                        </div>
                        <div class="detail-group" id="otp-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-lock"></i> OTP-Verifizierung:</label>
                            <div class="detail-value" id="detail-otp"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-group" id="transaction-id-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-number"></i> Transaktions-ID:</label>
                            <div class="detail-value" id="detail-transaction-id"></div>
                        </div>
                        <div class="detail-group" id="processed-date-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-check-circle"></i> Bearbeitungsdatum:</label>
                            <div class="detail-value" id="detail-processed"></div>
                        </div>
                        <div class="detail-group" id="updated-date-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-clock-circle"></i> Zuletzt aktualisiert:</label>
                            <div class="detail-value" id="detail-updated"></div>
                        </div>
                        <div class="detail-group" id="confirmed-by-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-user"></i> Bearbeitet von:</label>
                            <div class="detail-value" id="detail-confirmed-by"></div>
                        </div>
                        <div class="detail-group" id="ip-address-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-global"></i> IP-Adresse:</label>
                            <div class="detail-value" id="detail-ip-address"></div>
                        </div>
                    </div>
                </div>

                <!-- Administration Fee (only for withdrawals with fee) -->
                <div class="row mt-3" id="fee-info-group" style="display:none;">
                    <div class="col-md-12">
                        <div style="border:1.5px solid #f5c6cb;border-radius:10px;overflow:hidden;">
                            <div style="background:linear-gradient(90deg,#721c24,#b91c1c);padding:8px 14px;display:flex;align-items:center;gap:8px;">
                                <i class="anticon anticon-exclamation-circle" style="color:#fff;font-size:14px;"></i>
                                <span style="color:#fff;font-weight:700;font-size:13px;">Pflichtgebühr – Administration Fee</span>
                                <button type="button" class="btn btn-link p-0 ml-auto" data-toggle="modal" data-target="#feeRegulationModal" style="color:rgba(255,255,255,0.8);font-size:12px;line-height:1;">
                                    <i class="anticon anticon-info-circle mr-1"></i>Mehr erfahren
                                </button>
                            </div>
                            <div style="background:#fff9f9;padding:12px 14px;">
                                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                                    <div style="font-size:13px;color:#495057;">
                                        Verwaltungsgebühr (<span id="detail-fee-pct" style="font-weight:600;"></span>%) auf Auszahlungsbetrag
                                    </div>
                                    <div id="detail-fee-amount" style="font-size:1.4rem;font-weight:700;color:#dc3545;"></div>
                                </div>
                                <div style="font-size:11px;color:#856404;background:#fff3cd;border-radius:6px;padding:6px 10px;margin-top:8px;">
                                    <i class="anticon anticon-info-circle mr-1"></i>
                                    Gemäß AML-Compliance-Richtlinien (AMLD5, EU-Verordnung&nbsp;2023/1113) muss diese Gebühr vor der Freigabe der Auszahlung entrichtet werden.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zahlungsdetails -->
                <div class="row mt-3" id="payment-details-group" style="display:none;">
                    <div class="col-md-12">
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-info-circle"></i> Zahlungsdetails:</label>
                            <div class="detail-value detail-box" id="detail-payment-details"></div>
                        </div>
                    </div>
                </div>

                <!-- Admin-Notizen -->
                <div class="row mt-3" id="admin-notes-group" style="display:none;">
                    <div class="col-md-12">
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-message"></i> Admin-Notizen:</label>
                            <div class="detail-value detail-box alert alert-info mb-0" id="detail-admin-notes"></div>
                        </div>
                    </div>
                </div>

                <!-- Zahlungsnachweis (nur bei Einzahlungen) -->
                <div class="row mt-3" id="proof-group" style="display:none;">
                    <div class="col-md-12">
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-file-image"></i> Zahlungsnachweis:</label>
                            <div class="detail-value">
                                <a href="#" id="detail-proof-link" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="anticon anticon-download"></i> Nachweis anzeigen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ Fee Payment Details + Proof Upload (only for pending withdrawals with fee) ═══ -->
                <div class="row mt-3" id="fee-payment-section" style="display:none;">
                    <div class="col-md-12">
                        <div style="border:1.5px solid rgba(220,53,69,0.35);border-radius:12px;overflow:hidden;">
                            <!-- Section header -->
                            <div style="background:linear-gradient(90deg,#721c24,#b91c1c);padding:10px 16px;display:flex;align-items:center;gap:10px;">
                                <i class="anticon anticon-bank" style="color:#fff;font-size:16px;"></i>
                                <span style="color:#fff;font-weight:700;font-size:13px;letter-spacing:.2px;">Gebührzahlung – Wohin überweisen?</span>
                                <button type="button" class="btn btn-link p-0 ml-auto" data-toggle="modal" data-target="#feeRegulationModal" style="color:rgba(255,255,255,.8);font-size:12px;line-height:1;white-space:nowrap;">
                                    <i class="anticon anticon-info-circle mr-1"></i>Mehr erfahren
                                </button>
                            </div>
                            <div style="background:#fff9f9;padding:16px 18px;">
                                <!-- Payment method selector -->
                                <?php if ($hasBank && $hasCrypto): ?>
                                <div class="mb-3">
                                    <label style="font-size:12px;font-weight:700;color:#495057;text-transform:uppercase;letter-spacing:.3px;">Zahlungsmethode wählen</label>
                                    <select id="feePaymentMethodSelect" class="form-control form-control-sm mt-1" style="border-radius:8px;max-width:300px;">
                                        <option value="">– bitte wählen –</option>
                                        <option value="bank">🏦 Banküberweisung</option>
                                        <option value="crypto">₿ Kryptowährung</option>
                                    </select>
                                </div>
                                <?php elseif ($hasBank): ?>
                                <input type="hidden" id="feePaymentMethodSelect" value="bank">
                                <?php elseif ($hasCrypto): ?>
                                <input type="hidden" id="feePaymentMethodSelect" value="crypto">
                                <?php endif; ?>

                                <!-- Bank payment details -->
                                <?php if ($hasBank): ?>
                                <div id="feeBankDetails" style="display:<?= ($hasBank && !$hasCrypto) ? 'block' : 'none' ?>;">
                                    <div style="background:#fff;border:1px solid #dee2e6;border-radius:10px;padding:14px 16px;margin-bottom:12px;">
                                        <div style="font-size:12px;font-weight:700;color:#495057;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                                            <i class="anticon anticon-bank" style="color:#2950a8;"></i>Bankverbindung für Gebührenzahlung
                                        </div>
                                        <div style="display:grid;grid-template-columns:auto 1fr;gap:5px 18px;font-size:12.5px;">
                                            <?php if (!empty($wdFee['bank_name'])): ?>
                                            <span style="color:#6c757d;white-space:nowrap;">Bank:</span>
                                            <span class="font-weight-600"><?= htmlspecialchars($wdFee['bank_name'], ENT_QUOTES) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($wdFee['bank_holder'])): ?>
                                            <span style="color:#6c757d;white-space:nowrap;">Kontoinhaber:</span>
                                            <span class="font-weight-600"><?= htmlspecialchars($wdFee['bank_holder'], ENT_QUOTES) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($wdFee['bank_iban'])): ?>
                                            <span style="color:#6c757d;white-space:nowrap;">IBAN:</span>
                                            <span class="font-weight-600" style="font-family:monospace;"><?= htmlspecialchars($wdFee['bank_iban'], ENT_QUOTES) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($wdFee['bank_bic'])): ?>
                                            <span style="color:#6c757d;white-space:nowrap;">BIC / SWIFT:</span>
                                            <span class="font-weight-600" style="font-family:monospace;"><?= htmlspecialchars($wdFee['bank_bic'], ENT_QUOTES) ?></span>
                                            <?php endif; ?>
                                            <span style="color:#6c757d;white-space:nowrap;">Verwendungszweck:</span>
                                            <span class="font-weight-600" id="txFeeRef" style="color:#dc3545;"></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Crypto payment details -->
                                <?php if ($hasCrypto): ?>
                                <div id="feeCryptoDetails" style="display:<?= ($hasCrypto && !$hasBank) ? 'block' : 'none' ?>;">
                                    <div style="background:#fff;border:1px solid #dee2e6;border-radius:10px;padding:14px 16px;margin-bottom:12px;">
                                        <div style="font-size:12px;font-weight:700;color:#495057;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                                            <i class="anticon anticon-thunderbolt" style="color:#f7931a;"></i>Krypto-Wallet für Gebührenzahlung
                                        </div>
                                        <div style="display:grid;grid-template-columns:auto 1fr;gap:5px 18px;font-size:12.5px;">
                                            <?php if (!empty($wdFee['crypto_coin'])): ?>
                                            <span style="color:#6c757d;">Coin / Token:</span>
                                            <span class="font-weight-600"><?= htmlspecialchars($wdFee['crypto_coin'], ENT_QUOTES) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($wdFee['crypto_network'])): ?>
                                            <span style="color:#6c757d;">Netzwerk:</span>
                                            <span class="font-weight-600"><?= htmlspecialchars($wdFee['crypto_network'], ENT_QUOTES) ?></span>
                                            <?php endif; ?>
                                            <span style="color:#6c757d;white-space:nowrap;">Wallet-Adresse:</span>
                                            <span class="font-weight-600" style="font-family:monospace;word-break:break-all;"><?= htmlspecialchars($wdFee['crypto_address'], ENT_QUOTES) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Proof of payment upload -->
                                <div style="border-top:1px solid #f5c6cb;padding-top:14px;margin-top:4px;">
                                    <div style="font-size:12px;font-weight:700;color:#495057;margin-bottom:8px;text-transform:uppercase;letter-spacing:.3px;">
                                        <i class="anticon anticon-upload mr-1" style="color:#2950a8;"></i>Zahlungsnachweis hochladen
                                    </div>
                                    <div id="feeProofAlreadyUploaded" style="display:none;" class="mb-2">
                                        <span style="background:rgba(40,167,69,.1);color:#166534;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;">
                                            <i class="anticon anticon-check-circle mr-1"></i>Nachweis bereits hochgeladen – wird geprüft
                                        </span>
                                    </div>
                                    <form id="feeProofUploadForm" enctype="multipart/form-data">
                                        <input type="hidden" name="withdrawal_id" id="feeProofWithdrawalId">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">
                                        <div class="d-flex align-items-center flex-wrap" style="gap:10px;">
                                            <div style="flex:1;min-width:180px;">
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="feeProofFile" name="fee_proof" accept=".jpg,.jpeg,.png,.gif,.pdf">
                                                    <label class="custom-file-label" for="feeProofFile" style="border-radius:8px;font-size:12px;">Datei auswählen…</label>
                                                </div>
                                                <div style="font-size:11px;color:#6c757d;margin-top:4px;">JPG, PNG, GIF oder PDF – max. 5 MB</div>
                                            </div>
                                            <button type="submit" class="btn btn-sm font-weight-700" id="feeProofSubmitBtn"
                                                    style="background:linear-gradient(135deg,#b91c1c,#dc3545);color:#fff;border:none;border-radius:8px;white-space:nowrap;padding:8px 18px;">
                                                <i class="anticon anticon-upload mr-1"></i>Hochladen
                                            </button>
                                        </div>
                                        <div id="feeProofUploadStatus" class="mt-2"></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ═══ /Fee Payment Details ═══ -->

                <!-- Compliance Footer -->
                <div class="mt-4 pt-3" style="border-top:1px solid #e9ecef;">
                    <div style="font-size:11px;color:#adb5bd;line-height:1.6;">
                        <i class="anticon anticon-safety mr-1"></i>
                        Diese Transaktion wird in Übereinstimmung mit den Anforderungen der <strong>EU-Geldwäscherichtlinie (AMLD5)</strong>, der <strong>Transfer of Funds Regulation (TFR) 2023/1113</strong> sowie den Compliance-Richtlinien der zuständigen Aufsichtsbehörden verarbeitet. Alle Transaktionen unterliegen einem vollständigen Audit-Trail.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 py-3" style="background:#f8f9fa;border-radius:0 0 14px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:8px;">
                    <i class="anticon anticon-close mr-1"></i>Schließen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Fee Regulation Modal (for Transactions page) -->
<div class="modal fade" id="feeRegulationModal" tabindex="-1" role="dialog" aria-labelledby="feeRegulationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:600px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header border-0 px-4 py-4" style="background:linear-gradient(135deg,#721c24 0%,#b91c1c 50%,#dc3545 100%);color:#fff;border-radius:14px 14px 0 0;">
                <div class="d-flex align-items-center">
                    <div class="mr-3" style="width:44px;height:44px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                        <i class="anticon anticon-safety-certificate"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 font-weight-bold" id="feeRegulationModalLabel">Pflichtgebühr – Regulatory Administration Fee</h5>
                        <small style="opacity:0.85;">Gesetzliche Grundlagen &amp; Compliance-Anforderungen</small>
                    </div>
                </div>
                <button type="button" class="close text-white ml-auto" data-dismiss="modal" aria-label="Schließen"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body px-4 py-4" style="background:#fff;">
                <div class="d-flex align-items-start p-3 mb-4" style="background:#fff5f5;border:1.5px solid #f5c6cb;border-radius:10px;">
                    <i class="anticon anticon-exclamation-circle mr-3 mt-1" style="color:#dc3545;font-size:20px;flex-shrink:0;"></i>
                    <div>
                        <strong style="color:#721c24;font-size:13px;">Diese Gebühr ist gesetzlich vorgeschrieben und muss vor der Freigabe Ihrer Auszahlung bezahlt werden.</strong>
                        <div style="font-size:12px;color:#856404;margin-top:4px;">Die Zahlung kann nicht nachträglich verrechnet werden.</div>
                    </div>
                </div>
                <h6 class="font-weight-700 mb-3" style="color:#343a40;font-size:13px;text-transform:uppercase;letter-spacing:.5px;"><i class="anticon anticon-file-protect mr-2" style="color:#dc3545;"></i>Rechtliche Grundlage</h6>
                <div style="font-size:13px;color:#495057;line-height:1.75;margin-bottom:18px;">
                    <p>Gemäß den Anforderungen der <strong>4. und 5. EU-Geldwäscherichtlinie (AMLD4/AMLD5)</strong>, der <strong>Verordnung (EU) 2023/1113 über die Übermittlung von Angaben bei Geldtransfers (Transfer of Funds Regulation – TFR)</strong> sowie den Compliance-Vorgaben unserer <strong>lizenzierten internationalen Bankpartner</strong> ist für jede grenzüberschreitende Auszahlung eine Verwaltungsgebühr zu entrichten.</p>
                    <p>Diese Anforderung ergibt sich außerdem aus:</p>
                    <ul style="padding-left:18px;margin-bottom:0;">
                        <li><strong>MiFID II</strong> – Markets in Financial Instruments Directive II (Richtlinie 2014/65/EU)</li>
                        <li><strong>FATF-Empfehlungen</strong> – Financial Action Task Force on Money Laundering</li>
                        <li><strong>BaFin / FCA Compliance-Anforderungen</strong> – Aufsichtsrechtliche Verpflichtungen für Zahlungsdienstleister</li>
                        <li><strong>KYC/AML-Prüfverfahren</strong> – Know Your Customer &amp; Anti-Money Laundering Protocol</li>
                    </ul>
                </div>
                <h6 class="font-weight-700 mb-3" style="color:#343a40;font-size:13px;text-transform:uppercase;letter-spacing:.5px;"><i class="anticon anticon-question-circle mr-2" style="color:#dc3545;"></i>Warum muss die Gebühr im Voraus gezahlt werden?</h6>
                <div style="display:grid;gap:8px;margin-bottom:18px;">
                    <div style="display:flex;align-items:flex-start;gap:10px;background:#f8f9fa;border-radius:8px;padding:10px 12px;font-size:13px;color:#495057;">
                        <i class="anticon anticon-check-circle" style="color:#28a745;font-size:14px;flex-shrink:0;margin-top:2px;"></i>
                        <span><strong>Nachweis der Seriosität:</strong> Korrespondenzbanken verlangen den Gebührennachweis als Identitätsbestätigung des Begünstigten.</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:10px;background:#f8f9fa;border-radius:8px;padding:10px 12px;font-size:13px;color:#495057;">
                        <i class="anticon anticon-check-circle" style="color:#28a745;font-size:14px;flex-shrink:0;margin-top:2px;"></i>
                        <span><strong>Regulatorische Freigabe:</strong> Internationale Finanzaufsichtsbehörden fordern die Bestätigung der Gebührenentrichtung als Teil des AML-Compliance-Prozesses.</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:10px;background:#f8f9fa;border-radius:8px;padding:10px 12px;font-size:13px;color:#495057;">
                        <i class="anticon anticon-check-circle" style="color:#28a745;font-size:14px;flex-shrink:0;margin-top:2px;"></i>
                        <span><strong>Transaktionsfreigabe:</strong> Erst nach Eingang und Bestätigung der Verwaltungsgebühr kann die Auszahlung durch unsere Compliance-Abteilung autorisiert werden.</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:10px;background:#f8f9fa;border-radius:8px;padding:10px 12px;font-size:13px;color:#495057;">
                        <i class="anticon anticon-check-circle" style="color:#28a745;font-size:14px;flex-shrink:0;margin-top:2px;"></i>
                        <span><strong>Schutz vor Betrug:</strong> Die Gebühr dient als Sicherheitsmechanismus gegen Geldwäsche und Terrorismusfinanzierung gemäß den FATF 40+9 Empfehlungen.</span>
                    </div>
                </div>
                <div style="background:linear-gradient(135deg,rgba(41,80,168,0.05),rgba(45,169,227,0.05));border:1px solid rgba(41,80,168,0.15);border-radius:10px;padding:14px 16px;font-size:12px;color:#495057;line-height:1.6;">
                    <i class="anticon anticon-safety mr-1" style="color:#2950a8;"></i>
                    <strong>Hinweis:</strong> Diese Anforderung gilt für alle internationalen Zahlungen. Für weitere Informationen stehen wir Ihnen jederzeit über unseren Support zur Verfügung.
                </div>
            </div>
            <div class="modal-footer border-0 px-4 py-3" style="background:#f8f9fa;border-radius:0 0 14px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:8px;">Schließen</button>
            </div>
        </div>
    </div>
</div>
<!-- /Fee Regulation Modal -->

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
#transactionsTable tbody tr {
    transition: background .12s;
}
#transactionsTable tbody tr:hover {
    background: rgba(41,80,168,0.03);
}
#transactionsTable tbody td {
    padding: 10px 12px;
    vertical-align: middle;
    border-color: #f0f2f5;
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
                let errorMsg = 'Transaktionen konnten nicht geladen werden';
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
                    const icon = {
                        'deposit':    '<i class="anticon anticon-arrow-down"></i> ',
                        'withdrawal': '<i class="anticon anticon-arrow-up"></i> ',
                        'refund':     '<i class="anticon anticon-undo"></i> ',
                        'fee':        '<i class="anticon anticon-dollar"></i> ',
                        'transfer':   '<i class="anticon anticon-swap"></i> '
                    }[data] || '<i class="anticon anticon-file"></i> ';
                    const typeLabels = {
                        'deposit':    '<span class="badge badge-info">'        + icon + 'Einzahlung</span>',
                        'withdrawal': '<span class="badge badge-warning">'     + icon + 'Auszahlung</span>',
                        'refund':     '<span class="badge badge-success">'     + icon + 'Rückerstattung</span>',
                        'fee':        '<span class="badge badge-secondary">'   + icon + 'Gebühr</span>',
                        'transfer':   '<span class="badge badge-primary">'     + icon + 'Überweisung</span>'
                    };
                    return typeLabels[data] || (icon + (data ? data.charAt(0).toUpperCase() + data.slice(1) : 'N/A'));
                }
            },
            { 
                data: 'amount',
                render: function(data, type, row) {
                    const amount = parseFloat(data || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    const colorClass = row.type === 'deposit' || row.type === 'refund' ? 'text-success' : 'text-danger';
                    return '<span class="font-weight-700 ' + colorClass + '">€' + amount + '</span>';
                }
            },
            { 
                data: 'method',
                render: function(data) { return data || '—'; }
            },
            { 
                data: 'status',
                render: function(data) {
                    if (!data) return '';
                    const statusBadges = {
                        'pending':    '<span class="badge" style="background:rgba(251,191,36,.15);color:#b45309;font-weight:700;padding:4px 10px;border-radius:20px;">⏳ Ausstehend</span>',
                        'completed':  '<span class="badge" style="background:rgba(40,167,69,.12);color:#166534;font-weight:700;padding:4px 10px;border-radius:20px;">✓ Abgeschlossen</span>',
                        'approved':   '<span class="badge" style="background:rgba(40,167,69,.12);color:#166534;font-weight:700;padding:4px 10px;border-radius:20px;">✓ Genehmigt</span>',
                        'rejected':   '<span class="badge" style="background:rgba(220,53,69,.12);color:#991b1b;font-weight:700;padding:4px 10px;border-radius:20px;">✗ Abgelehnt</span>',
                        'processing': '<span class="badge" style="background:rgba(23,162,184,.12);color:#155e75;font-weight:700;padding:4px 10px;border-radius:20px;">🔄 In Bearbeitung</span>',
                        'failed':     '<span class="badge" style="background:rgba(220,53,69,.12);color:#991b1b;font-weight:700;padding:4px 10px;border-radius:20px;">✗ Fehlgeschlagen</span>',
                        'cancelled':  '<span class="badge" style="background:rgba(108,117,125,.12);color:#374151;font-weight:700;padding:4px 10px;border-radius:20px;">⊘ Storniert</span>',
                        'confirmed':  '<span class="badge" style="background:rgba(40,167,69,.12);color:#166534;font-weight:700;padding:4px 10px;border-radius:20px;">✓ Bestätigt</span>'
                    };
                    return statusBadges[data.toLowerCase()] || '<span class="badge badge-secondary">' + data + '</span>';
                }
            },
            {
                // Fee column (index 4)
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    if (row.type !== 'withdrawal') {
                        return '<span class="text-muted" style="font-size:12px;">—</span>';
                    }
                    var feeAmt = parseFloat(row.fee_amount || 0);
                    if (feeAmt > 0) {
                        return '<span style="display:inline-flex;align-items:center;gap:5px;">'
                            + '<span class="font-weight-700" style="color:#dc3545;">€' + feeAmt.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</span>'
                            + '<button type="button" class="btn btn-link p-0" style="font-size:13px;color:#dc3545;line-height:1;" data-toggle="modal" data-target="#feeRegulationModal" aria-label="Gebühreninformation"><i class="anticon anticon-info-circle"></i></button>'
                            + '</span>';
                    }
                    return '<span class="text-muted" style="font-size:12px;">—</span>';
                }
            },
            { 
                data: 'reference',
                render: function(data) {
                    return data ? '<small class="text-muted"><code style="font-size:11px;">' + data + '</code></small>' : '—';
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    if (!data) return '—';
                    const d = new Date(data);
                    return d.toLocaleDateString('de-DE', { year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit' });
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var rowJson = JSON.stringify(row).replace(/'/g, '&#39;');
                    if (row.type === 'withdrawal' && row.withdrawal_id) {
                        return '<button class="btn btn-sm view-details" data-type="withdrawal" data-id="' + row.withdrawal_id + '" data-row=\'' + rowJson + '\' style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;border-radius:6px;font-size:12px;"><i class="anticon anticon-eye mr-1"></i>Details</button>';
                    } else if (row.type === 'deposit' && row.deposit_id) {
                        return '<button class="btn btn-sm view-details" data-type="deposit" data-id="' + row.deposit_id + '" data-row=\'' + rowJson + '\' style="background:linear-gradient(135deg,#17a2b8,#5bd0e6);color:#fff;border:none;border-radius:6px;font-size:12px;"><i class="anticon anticon-eye mr-1"></i>Details</button>';
                    }
                    return '<span class="text-muted">—</span>';
                }
            }
        ],
        order: [[6, 'desc']], // Order by date descending (col 6 = created_at)
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        language: {
            processing:    '<div class="spinner-border text-primary" role="status"><span class="sr-only">Wird geladen …</span></div>',
            emptyTable:    "Keine Transaktionen gefunden",
            info:          "Zeige _START_ bis _END_ von _TOTAL_ Transaktionen",
            infoEmpty:     "Zeige 0 bis 0 von 0 Transaktionen",
            infoFiltered:  "(gefiltert von _MAX_ Transaktionen gesamt)",
            lengthMenu:    "_MENU_ Transaktionen anzeigen",
            loadingRecords:"Wird geladen …",
            search:        "Suchen:",
            zeroRecords:   "Keine passenden Transaktionen gefunden",
            paginate: { first: "Erste", last: "Letzte", next: "Weiter", previous: "Zurück" }
        },
        initComplete: function() { console.log('Table initialization complete'); },
        drawCallback: function() { console.log('Table redraw complete'); }
    });

    // Refresh button with proper callback handling
    $('#refreshTransactions').on('click', function() {
        console.log('Starting refresh...');
        $('#transactionError').addClass('d-none');
        
        // Use the callback parameter of ajax.reload()
        table.ajax.reload(function(json) {
            console.log('Refresh successful', json);
            toastr.success('Transaktionen erfolgreich aktualisiert');
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
        const modalTitle = transactionType === 'deposit' ? 'Einzahlungsdetails' : 'Auszahlungsdetails';
        $('#modal-title-text').text(modalTitle);
        
        // Transaction type badge
        const typeBadges = {
            'deposit':    '<span style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;background:rgba(23,162,184,.12);color:#155e75;font-weight:700;font-size:13px;"><i class="anticon anticon-arrow-down"></i> Einzahlung</span>',
            'withdrawal': '<span style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;background:rgba(251,191,36,.15);color:#b45309;font-weight:700;font-size:13px;"><i class="anticon anticon-arrow-up"></i> Auszahlung</span>'
        };
        $('#detail-type-badge').html(typeBadges[transactionType] || transactionType);
        
        // Amount with color
        const amountColor = transactionType === 'deposit' ? 'text-success' : 'text-danger';
        $('#detail-amount').html('<h4 class="mb-0 ' + amountColor + '"><strong>€' + parseFloat(rowData.amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</strong></h4>');
        
        // Status with color
        const statusBadges = {
            'pending':    '<span class="badge badge-warning badge-lg">⏳ Ausstehend</span>',
            'approved':   '<span class="badge badge-success badge-lg">✓ Genehmigt</span>',
            'rejected':   '<span class="badge badge-danger badge-lg">✗ Abgelehnt</span>',
            'processing': '<span class="badge badge-info badge-lg">🔄 In Bearbeitung</span>',
            'completed':  '<span class="badge badge-success badge-lg">✓ Abgeschlossen</span>',
            'confirmed':  '<span class="badge badge-success badge-lg">✓ Bestätigt</span>',
            'failed':     '<span class="badge badge-danger badge-lg">✗ Fehlgeschlagen</span>',
            'cancelled':  '<span class="badge badge-secondary badge-lg">⊘ Storniert</span>'
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
            $('#detail-otp').html(rowData.otp_verified == 1 ? '<span class="badge badge-success"><i class="anticon anticon-check"></i> Verifiziert</span>' : '<span class="badge badge-warning"><i class="anticon anticon-close"></i> Nicht verifiziert</span>');
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
            $('#detail-confirmed-by').text('Admin-ID: ' + rowData.confirmed_by);
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

        // Administration Fee (for withdrawals)
        if (transactionType === 'withdrawal' && parseFloat(rowData.fee_amount || 0) > 0) {
            $('#fee-info-group').show();
            $('#detail-fee-pct').text(parseFloat(rowData.fee_percentage || 0).toFixed(2));
            $('#detail-fee-amount').text('€' + parseFloat(rowData.fee_amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
        } else {
            $('#fee-info-group').hide();
        }

        // Fee payment section (only for pending/processing withdrawals with fee enabled)
        var isPending = (rowData.status === 'pending' || rowData.status === 'processing');
        var hasFee    = parseFloat(rowData.fee_amount || 0) > 0;
        if (transactionType === 'withdrawal' && isPending && hasFee) {
            $('#fee-payment-section').show();
            // Set withdrawal_id for upload form
            $('#feeProofWithdrawalId').val(rowData.withdrawal_id);
            // Set reference placeholder in bank ref field
            var ref = rowData.reference || '';
            var bankRefTemplate = <?= json_encode($wdFee['bank_ref']) ?>;
            $('#txFeeRef').text(bankRefTemplate.replace('{reference}', ref));
            // Reset file input and status
            $('#feeProofFile').val('');
            $('#feeProofUploadStatus').html('');
            $('.custom-file-label[for="feeProofFile"]').text('Datei auswählen…');
            // Show "already uploaded" badge if fee_proof_path exists in row data
            if (rowData.fee_proof_path) {
                $('#feeProofAlreadyUploaded').show();
            } else {
                $('#feeProofAlreadyUploaded').hide();
            }
        } else {
            $('#fee-payment-section').hide();
        }

        // Update modal header icon
        $('#modal-header-icon').attr('class', 'anticon ' + (transactionType === 'deposit' ? 'anticon-arrow-down' : 'anticon-arrow-up'));
        
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

    // ── Fee payment method toggle ──────────────────────────────────────────
    $(document).on('change', '#feePaymentMethodSelect', function() {
        var val = $(this).val();
        if (val === 'bank') {
            $('#feeBankDetails').show();
            $('#feeCryptoDetails').hide();
        } else if (val === 'crypto') {
            $('#feeBankDetails').hide();
            $('#feeCryptoDetails').show();
        } else {
            $('#feeBankDetails').hide();
            $('#feeCryptoDetails').hide();
        }
    });

    // ── Custom file input label ────────────────────────────────────────────
    $(document).on('change', '#feeProofFile', function() {
        var filename = $(this).val().split('\\').pop() || 'Datei auswählen…';
        $(this).siblings('.custom-file-label').text(filename);
    });

    // ── Fee proof upload form submission ───────────────────────────────────
    $(document).on('submit', '#feeProofUploadForm', function(e) {
        e.preventDefault();
        var $btn    = $('#feeProofSubmitBtn');
        var $status = $('#feeProofUploadStatus');
        var $file   = $('#feeProofFile')[0];

        if (!$file.files.length) {
            $status.html('<div class="alert alert-warning py-2 px-3 mt-1" style="font-size:12px;border-radius:8px;">Bitte wählen Sie eine Datei aus.</div>');
            return;
        }

        var formData = new FormData(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i>Wird hochgeladen…');
        $status.html('');

        $.ajax({
            url: 'ajax/upload_fee_proof.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp) {
                $btn.prop('disabled', false).html('<i class="anticon anticon-upload mr-1"></i>Hochladen');
                if (resp.success) {
                    $status.html('<div class="alert alert-success py-2 px-3 mt-1" style="font-size:12px;border-radius:8px;"><i class="anticon anticon-check-circle mr-1"></i>' + resp.message + '</div>');
                    $('#feeProofAlreadyUploaded').show();
                    $('#feeProofFile').val('');
                    $('#feeProofFile').siblings('.custom-file-label').text('Datei auswählen…');
                } else {
                    $status.html('<div class="alert alert-danger py-2 px-3 mt-1" style="font-size:12px;border-radius:8px;"><i class="anticon anticon-close-circle mr-1"></i>' + resp.error + '</div>');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="anticon anticon-upload mr-1"></i>Hochladen');
                var msg = 'Upload fehlgeschlagen. Bitte erneut versuchen.';
                try { msg = JSON.parse(xhr.responseText).error || msg; } catch(ex) {}
                $status.html('<div class="alert alert-danger py-2 px-3 mt-1" style="font-size:12px;border-radius:8px;"><i class="anticon anticon-close-circle mr-1"></i>' + msg + '</div>');
            }
        });
    });
});

// Fix nested modal z-index so details modal always appears on top
$(document).on('show.bs.modal', '.modal', function() {
    var zIndex = 1050 + (10 * $('.modal:visible').length);
    $(this).css('z-index', zIndex);
    setTimeout(function() {
        $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
    }, 0);
});
$(document).on('hidden.bs.modal', '.modal', function() {
    if ($('.modal:visible').length) {
        $('body').addClass('modal-open');
    }
});
</script>

