<?php
/**
 * Dashboard - User Main Page
 * 
 * UPDATED: 2026-02-19
 * Branch: copilot/sub-pr-1
 * 
 * Features:
 * - Dual withdrawal restrictions (KYC + verified payment methods)
 * - Onboarding notification container (shows incomplete steps)
 * - Smart progress tracking
 * - Real-time status updates
 * 
 * Security: PDO prepared statements, CSRF protection, session validation
 */

// Ensure config.php exists
if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    echo "<h1>Server configuration error</h1><p>Missing config.php</p>";
    exit;
}
require_once __DIR__ . '/config.php';

// Include header.php
if (file_exists(__DIR__ . '/header.php')) {
    require_once __DIR__ . '/header.php';
}

// Validate PDO instance
if (empty($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo "<h1>Database connection error</h1><p>Can't find valid PDO instance.</p>";
    exit;
}

// CSRF token init
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Current date/time UTC
$currentDateTime = new DateTime('now', new DateTimeZone('UTC'));
$currentDateTimeFormatted = $currentDateTime->format('Y-m-d H:i:s');

// Branding - Already loaded from header.php but ensure defaults if not set
if (!isset($appName)) {
    $appName = "Fundtracer AI";
}
if (!isset($appTagline)) {
    $appTagline = "Next-Generation Scam Recovery & Fund Tracing";
}

$brandColor = "#2950a8";
$brandGradient = "linear-gradient(90deg,#2950a8 0,#2da9e3 100%)";
$aiStatus = "Online";

// Safe defaults
$passwordChangeRequired = false;
$currentUser = null;
$currentUserLogin = null;
$cases = [];
$ongoingRecoveries = [];
$transactions = [];
$statusCounts = [];
$userId = $_SESSION['user_id'] ?? null;
$kyc_status = 'pending';
$loginLogs = [];

// Load current user if logged in
if (!empty($userId)) {
    try {
        $userStmt = $pdo->prepare("SELECT id, first_name, force_password_change, balance, last_login, is_verified FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($currentUser) {
            $passwordChangeRequired = ((int)$currentUser['force_password_change'] === 1);
            $currentUserLogin = $currentUser['first_name'] ?: 'Unknown User';
        }
    } catch (PDOException $e) {
        error_log("Database error (user fetch): " . $e->getMessage());
    }
}

if (empty($currentUserLogin)) {
    $currentUserLogin = 'Unknown User';
}

// Additional data for logged-in user
if (!empty($userId)) {
    try {
        // KYC
        $kyc = $pdo->prepare("SELECT status FROM kyc_verification_requests WHERE user_id=? ORDER BY id DESC LIMIT 1");
        $kyc->execute([$userId]);
        $kyc_status = ($row = $kyc->fetch(PDO::FETCH_ASSOC)) ? $row['status'] : 'pending';

        // Check for verified payment methods
        $stmt_verified = $pdo->prepare("SELECT COUNT(*) as count FROM user_payment_methods 
                                        WHERE user_id = ? AND type = 'crypto' 
                                        AND verification_status = 'verified'");
        $stmt_verified->execute([$userId]);
        $verified_count = $stmt_verified->fetch(PDO::FETCH_ASSOC);
        $hasVerifiedPaymentMethod = $verified_count['count'] > 0;

        // Login logs
        $loginLogsStmt = $pdo->prepare("SELECT ip_address, attempted_at, success FROM login_logs WHERE user_id=? ORDER BY attempted_at DESC LIMIT 3");
        $loginLogsStmt->execute([$userId]);
        $loginLogs = $loginLogsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats
        $statsStmt = $pdo->prepare("SELECT COUNT(*) as total_cases, COALESCE(SUM(reported_amount), 0) as total_reported, COALESCE(SUM(recovered_amount), 0) as total_recovered, MAX(created_at) as last_case_date FROM cases WHERE user_id = ?");
        $statsStmt->execute([$userId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_cases' => 0,
            'total_reported' => 0.00,
            'total_recovered' => 0.00,
            'last_case_date' => null
        ];

        // Recent cases
        $casesStmt = $pdo->prepare("SELECT c.*, p.name as platform_name, p.logo as platform_logo FROM cases c JOIN scam_platforms p ON c.platform_id = p.id WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 5");
        $casesStmt->execute([$userId]);
        $cases = $casesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Ongoing recoveries
        $ongoingStmt = $pdo->prepare("SELECT c.*, p.name as platform_name FROM cases c JOIN scam_platforms p ON c.platform_id = p.id WHERE c.user_id = ? AND c.status NOT IN ('closed', 'refund_rejected') ORDER BY c.created_at DESC LIMIT 5");
        $ongoingStmt->execute([$userId]);
        $ongoingRecoveries = $ongoingStmt->fetchAll(PDO::FETCH_ASSOC);

        // Transactions
        $transactionsStmt = $pdo->prepare("SELECT t.*, CASE WHEN t.case_id IS NOT NULL THEN c.case_number ELSE 'System' END as reference_name FROM transactions t LEFT JOIN cases c ON t.case_id = c.id WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT 5");
        $transactionsStmt->execute([$userId]);
        $transactions = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Status counts
        $statusStmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM cases WHERE user_id = ? GROUP BY status ORDER BY count DESC");
        $statusStmt->execute([$userId]);
        $statusCounts = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Database error (data fetch): " . $e->getMessage());
        $cases = $cases ?? [];
        $ongoingRecoveries = $ongoingRecoveries ?? [];
        $transactions = $transactions ?? [];
        $statusCounts = $statusCounts ?? [];
        $stats = $stats ?? [
            'total_cases' => 0,
            'total_reported' => 0.00,
            'total_recovered' => 0.00,
            'last_case_date' => null
        ];
    }
} else {
    // not logged in: safe defaults
    $stats = [
        'total_cases' => 0,
        'total_reported' => 0.00,
        'total_recovered' => 0.00,
        'last_case_date' => null
    ];
}

// Last AI scan
$lastAIScan = date('M d, Y H:i', strtotime($stats['last_case_date'] ?? 'now'));

// Recovery calculations
$reportedTotal = (float)($stats['total_reported'] ?? 0.0);
$recoveredTotal = (float)($stats['total_recovered'] ?? 0.0);
$recoveryPercentage = ($reportedTotal > 0) ? round(($recoveredTotal / $reportedTotal) * 100, 2) : 0;
$outstandingAmount = max(0, $reportedTotal - $recoveredTotal);
?>
<?php if ($passwordChangeRequired): ?>

<div class="modal fade show" id="passwordChangeModal" tabindex="-1" role="dialog"
     aria-labelledby="passwordChangeModalLabel" style="display:block; padding-right:15px;" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="passwordChangeModalLabel">
                    <i class="anticon anticon-lock m-r-5"></i> Passwortänderung erforderlich
                </h5>
            </div>

            <div class="modal-body">
                <div class="alert alert-warning mb-4" role="alert">
                    <i class="anticon anticon-info-circle"></i>
                    Bitte ändern Sie Ihr Passwort aus Sicherheitsgründen, bevor Sie fortfahren.
                </div>

                <form id="passwordChangeForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">

                    <!-- Current Password -->
                    <div class="form-group">
                        <label for="currentPassword">Aktuelles Passwort</label>
                        <input type="password" class="form-control" id="currentPassword" required aria-required="true" autocomplete="current-password">
                    </div>

                    <!-- New Password -->
                    <div class="form-group">
                        <label for="newPassword">Neues Passwort</label>
                        <input type="password" class="form-control" id="newPassword" required minlength="8" aria-describedby="passwordHelp" autocomplete="new-password">
                        <small id="passwordHelp" class="form-text text-muted">
                            Verwenden Sie ein eindeutiges Passwort (mindestens 8 Zeichen).
                        </small>

                        <!-- Strength Bar -->
                        <div class="progress mt-2" style="height:8px;">
                            <div id="passwordStrengthBar" class="progress-bar bg-danger" style="width:0%;" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small id="passwordStrengthText" class="text-muted small d-block mb-1" aria-live="polite">Stärke: Schwach</small>

                        <!-- Requirements Checklist -->
                        <ul class="list-unstyled small" id="passwordChecklist" aria-hidden="false">
                            <li id="req-length" class="text-danger"><i class="anticon anticon-close"></i> Mindestens 8 Zeichen</li>
                            <li id="req-upper" class="text-danger"><i class="anticon anticon-close"></i> Mindestens ein Großbuchstabe</li>
                            <li id="req-number" class="text-danger"><i class="anticon anticon-close"></i> Mindestens eine Zahl</li>
                            <li id="req-special" class="text-danger"><i class="anticon anticon-close"></i> Mindestens ein Sonderzeichen</li>
                        </ul>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirmPassword">Neues Passwort bestätigen</label>
                        <input type="password" class="form-control" id="confirmPassword" required autocomplete="new-password">
                        <small id="passwordMatchText" class="small text-muted" aria-live="polite">Warte auf Eingabe...</small>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="submitPasswordChange" aria-label="Passwort ändern">
                    <i class="anticon anticon-save"></i> Passwort ändern
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>
<?php endif; ?>


<div class="modal fade" id="newDepositModal" tabindex="-1" role="dialog" aria-labelledby="newDepositModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%); color: #fff; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold" id="newDepositModalLabel">
                    <i class="anticon anticon-plus-circle mr-2"></i>Konto aufladen
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
                            <strong>Wichtig:</strong> Bitte schließen Sie Ihre Einzahlung innerhalb von 30 Minuten ab, um Verzögerungen zu vermeiden.
                            <div class="small text-muted mt-1">Einzahlungen beschleunigen die Wiederherstellung Ihrer aktiven Fälle.</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-600" style="color: #2c3e50;">Betrag (EUR)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" aria-hidden="true" style="background: linear-gradient(135deg, #2950a8, #2da9e3); color: white; border: none; font-weight: 600;">€</span>
                            </div>
                            <input type="number" class="form-control" name="amount" min="10" step="0.01" required placeholder="Einzahlungsbetrag eingeben" aria-label="Betrag in Euro" style="border-radius: 0 8px 8px 0; border-left: none; font-size: 18px; font-weight: 600;">
                        </div>
                        <small class="form-text text-muted"><i class="anticon anticon-check-circle text-success mr-1"></i>Mindesteinzahlung: €10,00 | Bearbeitungsgebühr: 0%</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-600" style="color: #2c3e50;">Zahlungsmethode</label>
                        <select class="form-control select2" name="payment_method" id="paymentMethod" required aria-required="true" style="border-radius: 8px; padding: 12px; font-size: 15px;">
                            <option value="">Zahlungsmethode auswählen</option>
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
                                <h6 class="mb-0">Zahlungsanweisungen</h6>
                            </div>
                            <div class="card-body">
                                <div id="bankDetails" style="display: none;">
                                    <div class="mb-3">
                                        <h6 class="text-primary"><i class="anticon anticon-bank"></i> Banküberweisung – Details</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-1"><strong>Kontoinhaber:</strong></p>
                                                <p class="mb-1"><strong>IBAN:</strong></p>
                                                <p class="mb-1"><strong>BIC / SWIFT:</strong></p>
                                                <p class="mb-1"><strong>Kontotyp:</strong></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-1" id="detail-bank-name">-</p>
                                                <p class="mb-1" id="detail-account-number">-</p>
                                                <p class="mb-1" id="detail-routing-number">-</p>
                                                <p class="mb-1">Geschäftskonto</p>
                                            </div>
                                        </div>
                                        <div class="alert alert-warning mt-3">
                                            <i class="anticon anticon-exclamation-circle"></i>
                                            <strong>Hinweis:</strong> Geben Sie als Verwendungszweck <strong>RF3K8M1ZPW-<?= htmlspecialchars($currentUser['id'],ENT_QUOTES) ?></strong> an.
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="cryptoDetails" style="display: none;">
                                    <div class="mb-3">
                                        <h6 class="text-primary"><i class="anticon anticon-block"></i> Krypto-Wallet – Details</h6>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p class="mb-1"><strong>Netzwerk:</strong> <span id="detail-crypto-network">Ethereum (ERC20)</span></p>
                                                <p class="mb-1"><strong>Wallet-Adresse:</strong></p>
                                                <div class="input-group mb-2">
                                                    <input type="text" class="form-control" id="detail-wallet-address" readonly aria-label="Wallet-Adresse">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" id="copyWalletAddress" aria-label="Adresse kopieren">
                                                            <i class="anticon anticon-copy"></i> Kopieren
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="alert alert-danger">
                                                    <i class="anticon anticon-warning"></i>
                                                    <strong>Wichtig:</strong> Senden Sie nur die angegebene Kryptowährung an diese Adresse.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="generalInstructions" style="display: none;">
                                    <h6 class="text-primary"><i class="anticon anticon-info-circle"></i> Weitere Hinweise</h6>
                                    <div id="detail-instructions" class="mb-0"></div>
                                </div>
                                
                                <hr>
                                
                                <div class="form-group">
                                    <label class="font-weight-semibold" for="proofOfPayment">Zahlungsnachweis</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="proofOfPayment" name="proof_of_payment" accept="image/*,.pdf" required>
                                        <label class="custom-file-label" for="proofOfPayment">Screenshot oder PDF auswählen</label>
                                    </div>
                                    <small class="form-text text-muted">Akzeptierte Formate: JPG, PNG, PDF (Max. 2 MB)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Abbrechen" style="border-radius: 8px;">
                        <i class="anticon anticon-close mr-1"></i>Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary" aria-label="Einzahlung bestätigen" style="border-radius: 8px; background: linear-gradient(135deg, #2950a8, #2da9e3); border: none;">
                        <i class="anticon anticon-check-circle mr-1"></i>Einzahlung bestätigen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Withdrawal Modal -->
<!-- 🔒 Withdrawal Modal -->
<div class="modal fade" id="newWithdrawalModal" tabindex="-1" role="dialog" aria-labelledby="newWithdrawalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #28a745, #20c997); color: #fff; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold" id="newWithdrawalModalLabel">
                    <i class="anticon anticon-download mr-2"></i>Auszahlungsantrag
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
                            <strong>Bearbeitungszeit:</strong> Auszahlungen werden innerhalb von 1–3 Werktagen bearbeitet.
                        </div>
                    </div>

                    <!-- Hidden real balance for JS -->
                    <input type="hidden" id="availableBalance" value="<?= (float)($currentUser['balance'] ?? 0) ?>">

                    <!-- AMOUNT -->
                    <div class="form-group">
                        <label class="font-weight-600" style="color: #2c3e50;">Betrag (EUR €)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; font-weight: 600;">€</span>
                            </div>
                            <input 
                                type="number"
                                class="form-control"
                                name="amount"
                                id="amount"
                                step="0.01"
                                min="1000"
                                required
                                placeholder="Minimum: €1.000"
                                style="border-radius: 0 8px 8px 0; border-left: none; font-size: 18px; font-weight: 600;">
                        </div>
                        <small class="form-text text-muted">
                            <i class="anticon anticon-wallet text-success mr-1"></i>Verfügbares Guthaben: <strong>€<?= number_format($currentUser['balance'] ?? 0, 2) ?></strong> | Mindestbetrag: <strong>€1.000</strong>
                        </small>
                    </div>

                    <!-- PAYMENT METHOD -->
                    <div class="form-group">
                        <label class="font-weight-600" style="color: #2c3e50;">Auszahlungsmethode</label>
                        <select class="form-control select2" name="payment_method_id" id="withdrawalMethod" required style="border-radius: 8px; padding: 12px; font-size: 15px;">
                            <option value="">Auszahlungsmethode auswählen</option>
                            <?php
                            try {
                                // Load only user's verified payment methods (no JOIN with payment_methods)
                                $stmt = $pdo->prepare("SELECT id, type, payment_method, cryptocurrency, 
                                    wallet_address, iban, account_number, bank_name, label 
                                    FROM user_payment_methods 
                                    WHERE user_id = ? AND verification_status = 'verified'
                                    ORDER BY created_at DESC");
                                $stmt->execute([$_SESSION['user_id']]);
                                while ($userMethod = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    // Determine display name (only from user_payment_methods)
                                    if ($userMethod['label']) {
                                        $displayName = $userMethod['label'];
                                    } elseif ($userMethod['type'] === 'crypto') {
                                        $displayName = ucfirst($userMethod['cryptocurrency']);
                                    } else {
                                        $displayName = $userMethod['bank_name'] ?? 'Bank Transfer';
                                    }
                                    
                                    // Get account details based on type
                                    if ($userMethod['type'] === 'crypto') {
                                        $details = $userMethod['wallet_address'] ?? '';
                                        // Show masked version for crypto
                                        if (strlen($details) > 10) {
                                            $displayName .= ' (...' . substr($details, -6) . ')';
                                        }
                                    } else {
                                        // For bank: prefer IBAN, fallback to account_number
                                        $details = $userMethod['iban'] ?? $userMethod['account_number'] ?? '';
                                        if (strlen($details) > 10) {
                                            $displayName .= ' (...' . substr($details, -4) . ')';
                                        }
                                    }
                                    
                                    echo '<option value="' . htmlspecialchars($userMethod['id'], ENT_QUOTES) 
                                         . '" data-details="' . htmlspecialchars($details, ENT_QUOTES) 
                                         . '" data-type="' . htmlspecialchars($userMethod['type'], ENT_QUOTES) . '">' 
                                         . htmlspecialchars($displayName, ENT_QUOTES) . '</option>';
                                }
                            } catch (Exception $e) {
                                error_log("Withdrawal methods load error: " . $e->getMessage());
                            }
                            ?>
                        </select>
                        <small class="form-text text-muted">
                            <i class="anticon anticon-safety mr-1"></i>Nur Ihre verifizierten Zahlungsmethoden werden angezeigt
                        </small>
                    </div>

                    <!-- BANK DETAILS (Auto-Fill) -->
                    <div id="bankDetailsContainer" class="mt-3" style="display:none;">
                        <h6 class="text-primary"><i class="anticon anticon-bank"></i> Ihre Bankdaten</h6>
                        <p><strong>Bank:</strong> <span id="user-bank-name">-</span></p>
                        <p><strong>Kontoinhaber:</strong> <span id="user-account-holder">-</span></p>
                        <p><strong>IBAN:</strong> <span id="user-iban">-</span></p>
                        <p><strong>BIC:</strong> <span id="user-bic">-</span></p>
                    </div>

                    <!-- PAYMENT DETAILS -->
                    <div class="form-group mt-3">
                        <label class="font-weight-semibold">Zahlungsdetails</label>
                        <textarea class="form-control" name="payment_details" id="paymentDetails" rows="3" required placeholder="Vollständige Zahlungsdetails eingeben"></textarea>
                    </div>

                    <!-- CONFIRM CHECKBOX -->
                    <div class="form-group mt-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="confirmDetails" required>
                            <label class="custom-control-label" for="confirmDetails">
                                Ich bestätige, dass die angegebenen Zahlungsdetails korrekt sind.
                            </label>
                        </div>
                    </div>

                    <!-- OTP SECTION -->
                    <hr>
                    <div id="otpSection" class="pt-2">
                        <h6 class="text-primary">
                            <i class="anticon anticon-safety"></i> E-Mail-Verifizierung
                        </h6>
                        <p class="text-muted mb-2">
                            Aus Sicherheitsgründen senden wir einen Einmalcode an Ihre E-Mail. Klicken Sie auf die Schaltfläche, um den Code zu erhalten und zu verifizieren.
                        </p>

                        <div class="form-group">
                            <label class="font-weight-600">Einmalpasswort (OTP)</label>
                            <div class="input-group mb-2">
                                <input type="text" id="otpCode" maxlength="6" class="form-control" placeholder="6-stelligen OTP eingeben" disabled style="font-size: 16px; letter-spacing: 3px; text-align: center; font-weight: 600;">
                                <div class="input-group-append">
                                    <button type="button" id="sendVerifyOtpBtn" class="btn btn-primary" style="min-width: 160px;">
                                        <i class="anticon anticon-mail"></i> OTP senden & prüfen
                                    </button>
                                </div>
                            </div>
                            <small id="otpInfoText" class="form-text text-muted">
                                <i class="anticon anticon-info-circle"></i> OTP ist 5 Minuten gültig. Klicken Sie auf die Schaltfläche, um den Code an Ihre E-Mail zu senden.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">
                        <i class="anticon anticon-close mr-1"></i>Abbrechen
                    </button>
                    <button type="submit" id="withdrawalSubmitBtn" class="btn btn-success" disabled style="border-radius: 8px; background: linear-gradient(135deg, #28a745, #20c997); border: none;">
                        <i class="anticon anticon-send mr-1"></i>Antrag einreichen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionDetailsModal" tabindex="-1" role="dialog" aria-labelledby="transactionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content shadow-sm">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="transactionDetailsModalLabel">Transaktionsdetails</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Schließen">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-semibold">Transaktions-ID:</label>
                            <p id="txn-id" class="form-control-static">-</p>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">Datum & Uhrzeit:</label>
                            <p id="txn-date" class="form-control-static">-</p>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">Typ:</label>
                            <p id="txn-type" class="form-control-static">-</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-semibold">Betrag:</label>
                            <p id="txn-amount" class="form-control-static">-</p>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">Status:</label>
                            <p id="txn-status" class="form-control-static">-</p>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">Referenz:</label>
                            <p id="txn-reference" class="form-control-static">-</p>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Zahlungsdetails</h6>
                    </div>
                    <div class="card-body">
                        <div id="txn-payment-details"></div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Transaktions-Zeitleiste</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush" id="txn-timeline" role="list">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Antrag eingereicht</span>
                                <small class="text-muted">-</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Schließen</button>
                <button type="button" class="btn btn-info" id="printReceiptBtn">Quittung drucken</button>
            </div>
        </div>
    </div>
</div>

<!-- Current Date and Time Display -->
<div class="fixed-bottom text-right p-2" style="z-index: 1000;">
    <small class="bg-dark text-light px-2 py-1 rounded" role="status" aria-live="polite">
        Datum &amp; Uhrzeit (UTC): <?= htmlspecialchars($currentDateTimeFormatted, ENT_QUOTES) ?> | Angemeldeter Benutzer: <?= htmlspecialchars($currentUserLogin, ENT_QUOTES) ?>
    </small>
</div>

<style>
:root {
    --brand: #2950a8;
    --brand-light: #2da9e3;
    --brand-dark: #1e3a7a;
    --bg: #f7fafd;
    --muted: #6c757d;
    --card-radius: 12px;
    --success: #28a745;
    --danger: #dc3545;
    --warning: #ffc107;
    --info: #17a2b8;
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
    --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);
}

/* Body & Typography */
body {
    background: linear-gradient(135deg, #f7fafd 0%, #e8f2f7 100%);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-size: 15px;
    line-height: 1.6;
    color: #333;
}

/* Card Improvements */
.main-content .card {
    border-radius: var(--card-radius);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: var(--shadow-sm);
    background: #fff;
    position: relative;
    overflow: hidden;
}

.main-content .card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, var(--brand) 0%, var(--brand-light) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.main-content .card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.main-content .card:hover::before {
    opacity: 1;
}

.card-body {
    padding: 1.5rem;
}

.card-header {
    background: linear-gradient(180deg, #fff 0%, #f8f9fa 100%);
    border-bottom: 2px solid #f0f0f0;
    padding: 1rem 1.5rem;
    font-weight: 600;
}

/* Avatar Icons */
.avatar-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    padding: 14px;
    color: #fff;
    font-size: 24px;
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: hidden;
}

.avatar-icon::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: rgba(255, 255, 255, 0.1);
    transform: rotate(45deg);
    transition: all 0.6s ease;
}

.avatar-icon:hover::before {
    top: -60%;
    right: -60%;
}

.avatar-blue {
    background: linear-gradient(135deg, #2950a8, #2da9e3);
    box-shadow: 0 4px 15px rgba(41, 80, 168, 0.3);
}

.avatar-cyan {
    background: linear-gradient(135deg, #17a2b8, #5bd0e6);
    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
}

.avatar-gold {
    background: linear-gradient(135deg, #f39c12, #f6c36d);
    box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
}

.avatar-purple {
    background: linear-gradient(135deg, #6f42c1, #b28bff);
    box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);
}

/* Typography */
.lead {
    font-size: 1.1rem;
    font-weight: 500;
    line-height: 1.5;
}

h5, .h5 {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1rem;
}

/* Algorithm Animation */
.algorithm-animation {
    padding: 12px 0;
}

.algorithm-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 8px;
}

.step {
    flex: 1;
    background: #fff;
    border-radius: 10px;
    padding: 12px 10px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.step.active {
    background: linear-gradient(135deg, #e8f3ff, #f4fbff);
    transform: translateY(-4px);
    border-color: var(--brand-light);
    box-shadow: 0 4px 12px rgba(41, 80, 168, 0.2);
}

.step-icon {
    font-size: 22px;
    margin-bottom: 6px;
    color: var(--brand);
}

.step-label {
    font-size: 12px;
    font-weight: 500;
    color: #555;
}

.algorithm-progress {
    height: 10px;
    background: #e9eef7;
    border-radius: 12px;
    margin-top: 12px;
    overflow: hidden;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.algorithm-progress .progress-bar {
    height: 100%;
    background: linear-gradient(90deg, var(--brand), var(--brand-light));
    transition: width 1s cubic-bezier(0.2, 0.9, 0.3, 1);
    box-shadow: 0 2px 4px rgba(41, 80, 168, 0.3);
}

/* Table Improvements */
.table-hover tbody tr {
    transition: background-color 0.2s ease;
}

.table-hover tbody tr:hover {
    background: rgba(41, 80, 168, 0.04);
}

.table th {
    font-weight: 600;
    color: #555;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

/* Progress Bar */
.live-progress {
    transition: width 0.8s cubic-bezier(0.2, 0.9, 0.3, 1);
}

.progress {
    height: 22px;
    border-radius: 8px;
    background: #e9ecef;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

.progress-bar {
    font-size: 12px;
    font-weight: 600;
    line-height: 22px;
}

/* Scrollable */
.scrollable {
    overflow: auto;
    padding-right: 8px;
}

.scrollable::-webkit-scrollbar {
    width: 6px;
}

.scrollable::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.scrollable::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.scrollable::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Skeleton Loading */
.kv-skeleton {
    background: linear-gradient(90deg, #f3f6fb, #eef6ff);
    border-radius: 8px;
    height: 18px;
    display: inline-block;
    width: 100%;
    animation: skeleton 1.5s linear infinite;
}

@keyframes skeleton {
    0% { opacity: 1; }
    50% { opacity: 0.6; }
    100% { opacity: 1; }
}

/* Badges */
.badge-pill {
    border-radius: 50px;
    padding: 0.35em 0.75em;
    font-weight: 500;
    transition: all 0.2s ease;
}

.badge {
    font-size: 85%;
    font-weight: 500;
    padding: 0.4em 0.6em;
    transition: all 0.2s ease;
}

.badge:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Animated Badges */
.badge-success {
    background: linear-gradient(135deg, #28a745, #20c997);
    animation: pulse-success 2s infinite;
}

@keyframes pulse-success {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
    }
}

/* Buttons */
.btn {
    font-weight: 500;
    border-radius: 8px;
    padding: 0.5rem 1.2rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn:hover::before {
    width: 300px;
    height: 300px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.btn-primary {
    background: linear-gradient(135deg, var(--brand), var(--brand-light));
    border: none;
    box-shadow: 0 4px 12px rgba(41, 80, 168, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--brand-dark), var(--brand));
    box-shadow: 0 6px 20px rgba(41, 80, 168, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, #28a745, #20c997);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.btn-success:hover {
    background: linear-gradient(135deg, #218838, #1ea77e);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

.btn-info {
    background: linear-gradient(135deg, #17a2b8, #5bd0e6);
    box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
}

.btn-info:hover {
    background: linear-gradient(135deg, #138496, #4abfd1);
    box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
}

.btn-sm {
    padding: 0.4rem 0.9rem;
    font-size: 13px;
}

/* Alerts */
.alert {
    border-radius: 10px;
    border: none;
    box-shadow: var(--shadow-sm);
}

/* Utilities */
.small-muted {
    color: var(--muted);
}

.tooltip-inner {
    max-width: 280px;
    border-radius: 6px;
}

/* Header Brand Card */
.brand-header-card {
    background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%);
    border: none;
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
}

.brand-header-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

.brand-header-card .card-body {
    position: relative;
    z-index: 1;
}

/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e0e0e0;
}

.timeline-item-active .timeline-marker {
    width: 14px;
    height: 14px;
    box-shadow: 0 0 0 3px rgba(41, 80, 168, 0.2);
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 17px;
    bottom: -5px;
    width: 2px;
    background: #e0e0e0;
}

.timeline-content {
    background: rgba(41, 80, 168, 0.03);
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid rgba(41, 80, 168, 0.2);
}

.timeline-item-active .timeline-content {
    background: rgba(41, 80, 168, 0.08);
    border-left-color: var(--brand);
}

/* Responsive */
@media (max-width: 767.98px) {
    .algorithm-steps {
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .step {
        min-width: 80px;
    }
    
    .card-body {
        padding: 1rem;
    }
}

/* Table Enhancements */
.table {
    border-collapse: separate;
    border-spacing: 0;
}

.table thead th {
    background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    color: #6c757d;
    padding: 1rem 0.75rem;
}

.table tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f0f0f0;
}

.table tbody tr:hover {
    background: linear-gradient(90deg, rgba(41, 80, 168, 0.03) 0%, rgba(45, 169, 227, 0.03) 100%);
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.table tbody td {
    vertical-align: middle;
    padding: 1rem 0.75rem;
    border-top: none;
}

/* KPI Cards with Trend Indicators */
.trend-indicator {
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 12px;
    margin-left: 8px;
}

.trend-up {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.trend-down {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

/* Animated Progress Bars */
.progress {
    border-radius: 50px;
    overflow: visible;
    background: #e9ecef;
}

.progress-bar {
    border-radius: 50px;
    transition: width 1s ease;
    position: relative;
    overflow: visible;
}

.progress-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

/* Pulse Animation for Active Elements */
.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(41, 80, 168, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(41, 80, 168, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(41, 80, 168, 0);
    }
}
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- PROFESSIONAL STATUS ALERTS & ACTION PROMPTS -->
        <?php
        // Calculate completion percentage
        $completion_steps = 0;
        $completed_steps = 0;
        
        // Check KYC
        $completion_steps++;
        if ($kyc_status === 'approved') $completed_steps++;
        
        // Check crypto verification
        $completion_steps++;
        if (isset($hasVerifiedPaymentMethod) && $hasVerifiedPaymentMethod) $completed_steps++;
        
        // Check if profile is complete (has email verified)
        $completion_steps++;
        if ($currentUser['is_verified'] ?? false) $completed_steps++;
        
        $completion_percentage = round(($completed_steps / $completion_steps) * 100);
        ?>

        <!-- STATUS ALERTS: KYC, Crypto Verification, Email Verification -->
        <?php if ($kyc_status !== 'approved' || !(isset($hasVerifiedPaymentMethod) && $hasVerifiedPaymentMethod) || !($currentUser['is_verified'] ?? false)): ?>
        <div class="row mb-4">
            
            <!-- KYC Verification Alert -->
            <?php if ($kyc_status !== 'approved'): ?>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ffc107;">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="avatar-icon avatar-lg mr-3" style="background: linear-gradient(135deg, #ffc107, #ffdb4d); font-size: 28px;">
                                <i class="anticon anticon-idcard"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h5 class="mb-0" style="font-weight: 600; color: #2c3e50;">KYC-Verifizierung erforderlich</h5>
                                    <button class="btn btn-link p-0 text-info" data-toggle="modal" data-target="#kycInfoModal" 
                                            title="Warum ist KYC wichtig?" style="font-size: 20px;">
                                        <i class="anticon anticon-info-circle"></i>
                                    </button>
                                </div>
                                <p class="text-muted mb-3" style="font-size: 14px; line-height: 1.6;">
                                    Schließen Sie die KYC-Verifizierung ab, um Auszahlungen freizuschalten und auf erweiterte Wiederherstellungsfunktionen zuzugreifen.
                                </p>
                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <?php if ($kyc_status === 'pending'): ?>
                                        <span class="badge badge-warning px-3 py-2 mb-2">
                                            <i class="anticon anticon-clock-circle mr-1"></i>Verifizierung ausstehend
                                        </span>
                                    <?php elseif ($kyc_status === 'rejected'): ?>
                                        <span class="badge badge-danger px-3 py-2 mb-2">
                                            <i class="anticon anticon-close-circle mr-1"></i>Verifizierung abgelehnt
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary px-3 py-2 mb-2">
                                            <i class="anticon anticon-question-circle mr-1"></i>Nicht begonnen
                                        </span>
                                    <?php endif; ?>
                                    <a href="kyc.php" class="btn btn-warning btn-sm mb-2" style="font-weight: 500;">
                                        <i class="anticon anticon-arrow-right mr-1"></i>
                                        <?= $kyc_status === 'rejected' ? 'KYC erneut einreichen' : ($kyc_status === 'pending' ? 'Status prüfen' : 'Verifizierung starten') ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Crypto Verification Alert -->
            <?php if (!(isset($hasVerifiedPaymentMethod) && $hasVerifiedPaymentMethod)): ?>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #17a2b8;">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="avatar-icon avatar-lg mr-3" style="background: linear-gradient(135deg, #17a2b8, #5bd0e6); font-size: 28px;">
                                <i class="anticon anticon-wallet"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h5 class="mb-0" style="font-weight: 600; color: #2c3e50;">Krypto-Adresse verifizieren</h5>
                                    <button class="btn btn-link p-0 text-info" data-toggle="modal" data-target="#cryptoInfoModal" 
                                            title="Warum Krypto-Adresse verifizieren?" style="font-size: 20px;">
                                        <i class="anticon anticon-info-circle"></i>
                                    </button>
                                </div>
                                <p class="text-muted mb-3" style="font-size: 14px; line-height: 1.6;">
                                    Verifizieren Sie Ihre Kryptowährungs-Wallet-Adresse für sichere Auszahlungen und zum Schutz Ihrer wiederhergestellten Gelder.
                                </p>
                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <span class="badge badge-info px-3 py-2 mb-2">
                                        <i class="anticon anticon-exclamation-circle mr-1"></i>Verifizierung erforderlich
                                    </span>
                                    <a href="payment-methods.php" class="btn btn-info btn-sm mb-2" style="font-weight: 500;">
                                        <i class="anticon anticon-arrow-right mr-1"></i>Jetzt verifizieren
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Email Verification Alert (Step 3) -->
            <?php if (!($currentUser['is_verified'] ?? false)): ?>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #dc3545;">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="avatar-icon avatar-lg mr-3" style="background: linear-gradient(135deg, #dc3545, #e74c5d); font-size: 28px;">
                                <i class="anticon anticon-mail"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h5 class="mb-0" style="font-weight: 600; color: #2c3e50;">E-Mail-Verifizierung</h5>
                                    <button class="btn btn-link p-0 text-info" data-toggle="modal" data-target="#emailVerifyInfoModal" 
                                            title="Warum E-Mail verifizieren?" style="font-size: 20px;">
                                        <i class="anticon anticon-info-circle"></i>
                                    </button>
                                </div>
                                <p class="text-muted mb-3" style="font-size: 14px; line-height: 1.6;">
                                    Verifizieren Sie Ihre E-Mail-Adresse, um die Kontoeinrichtung abzuschließen und alle Plattformfunktionen zu aktivieren.
                                </p>
                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <span class="badge badge-danger px-3 py-2 mb-2">
                                        <i class="anticon anticon-exclamation-circle mr-1"></i>Nicht verifiziert
                                    </span>
                                    <button id="sendVerificationEmailBtn" class="btn btn-danger btn-sm mb-2" style="font-weight: 500;">
                                        <i class="anticon anticon-mail mr-1"></i>Verifizierungs-E-Mail senden
                                    </button>
                                </div>
                                <div id="verificationEmailStatus" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        <?php endif; ?>

        <!-- INFO MODALS -->
        <!-- KYC Info Modal -->
   <div class="modal fade" id="kycInfoModal" tabindex="-1" role="dialog" aria-labelledby="kycInfoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="modal-header border-0" style="background: linear-gradient(135deg, #ffc107, #ffdb4d); color: #fff; border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title font-weight-bold" id="kycInfoModalLabel">
                            <i class="anticon anticon-idcard mr-2"></i>Warum ist die KYC-Verifizierung wichtig?
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <h6 class="text-primary mb-3" style="font-weight: 600;">
                                <i class="anticon anticon-safety-certificate mr-2"></i>Sicherheit & Compliance
                            </h6>
                            <p style="line-height: 1.8; color: #555;">
                                Die KYC (Know Your Customer)-Verifizierung ist eine wichtige Sicherheitsmaßnahme, die sowohl Sie als auch unsere Plattform schützt. 
                                Sie hilft, Betrug und Geldwäsche zu verhindern und stellt sicher, dass Ihre wiederhergestellten Gelder an den rechtmäßigen Eigentümer zurückgegeben werden.
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="text-success mb-3" style="font-weight: 600;">
                                <i class="anticon anticon-check-circle mr-2"></i>Vorteile der KYC-Verifizierung
                            </h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="anticon anticon-lock text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Erhöhte Sicherheit:</strong> Schützt Ihr Konto vor unbefugtem Zugriff und betrügerischen Aktivitäten.
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="anticon anticon-dollar text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Auszahlungen ermöglichen:</strong> Erforderlich, um wiederhergestellte Gelder auf Ihr Bank- oder Krypto-Wallet abzuheben.
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="anticon anticon-thunderbolt text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Zugriff auf erweiterte Tools:</strong> Schalten Sie KI-gestützte Wiederherstellungstools und Premium-Support-Services frei.
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="anticon anticon-global text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Einhaltung von Vorschriften:</strong> Entspricht internationalen AML (Anti-Geldwäsche)- und CTF (Terrorismusfinanzierung)-Vorschriften.
                                    </div>
                                </li>
                                <li class="d-flex align-items-start">
                                    <i class="anticon anticon-shield text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Identitätsschutz:</strong> Verhindert Identitätsdiebstahl und stellt sicher, dass Gelder nur in Ihrem Namen wiederhergestellt werden.
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-info border-0" style="background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(23, 162, 184, 0.05)); border-radius: 10px;">
                            <div class="d-flex align-items-start">
                                <i class="anticon anticon-info-circle mr-3" style="font-size: 24px; color: #17a2b8;"></i>
                                <div>
                                    <strong style="color: #17a2b8;">Schneller & einfacher Prozess</strong>
                                    <p class="mb-0 mt-2" style="color: #555;">
                                        Unsere KYC-Verifizierung dauert normalerweise nur 5-10 Minuten. Sie benötigen einen amtlichen Ausweis 
                                        und ein Selfie zur Identitätsbestätigung. Die meisten Verifizierungen werden innerhalb von 24-48 Stunden bearbeitet.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 15px 15px;">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">
                            <i class="anticon anticon-close mr-1"></i>Schließen
                        </button>
                        <a href="kyc.php" class="btn btn-warning" style="border-radius: 8px; font-weight: 500;">
                            <i class="anticon anticon-arrow-right mr-1"></i>KYC-Verifizierung starten
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Crypto Verification Info Modal -->
       <div class="modal fade" id="cryptoInfoModal" tabindex="-1" role="dialog" aria-labelledby="cryptoInfoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="modal-header border-0" style="background: linear-gradient(135deg, #17a2b8, #5bd0e6); color: #fff; border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title font-weight-bold" id="cryptoInfoModalLabel">
                            <i class="anticon anticon-wallet mr-2"></i>Warum Ihre Krypto-Adresse verifizieren?
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <h6 class="text-primary mb-3" style="font-weight: 600;">
                                <i class="anticon anticon-safety mr-2"></i>Schützen Sie Ihre wiederhergestellten Gelder
                            </h6>
                            <p style="line-height: 1.8; color: #555;">
                                Die Verifizierung der Krypto-Wallet ist für eine sichere Wiederherstellung von Geldern unerlässlich. Durch die Verifizierung des Eigentums Ihrer Wallet-Adresse 
                                stellen wir sicher, dass Ihre wiederhergestellten Gelder an das richtige Ziel gesendet werden und unbefugte Auszahlungen verhindert werden.
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="text-success mb-3" style="font-weight: 600;">
                                <i class="anticon anticon-check-circle mr-2"></i>Wichtige Sicherheitsvorteile
                            </h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="anticon anticon-shield text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Unbefugten Zugriff verhindern:</strong> Stellt sicher, dass nur Sie Gelder auf Ihre verifizierte Wallet-Adresse erhalten können.
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="anticon anticon-check-square text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Eigentumsnachweis:</strong> Bestätigt, dass Sie die privaten Schlüssel kontrollieren und die Gelder empfangen können.
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="anticon anticon-warning text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Betrugsprävention:</strong> Schützt vor Wallet-Adressen-Ersetzungsangriffen und Phishing-Versuchen.
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="anticon anticon-clock-circle text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Schnellere Auszahlungen:</strong> Vorab verifizierte Adressen ermöglichen eine schnellere Bearbeitung von Auszahlungsanträgen.
                                    </div>
                                </li>
                                <li class="d-flex align-items-start">
                                    <i class="anticon anticon-file-protect text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Compliance & Prüfpfad:</strong> Erstellt eine sichere Aufzeichnung des Eigentums für Regulierungs- und Prüfungszwecke.
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="card border-warning mb-3">
                            <div class="card-body bg-light">
                                <h6 class="text-warning mb-2" style="font-weight: 600;">
                                    <i class="anticon anticon-exclamation-circle mr-2"></i>Verifizierungsprozess
                                </h6>
                                <p class="mb-2" style="color: #555; font-size: 14px;">
                                    Um Ihre Krypto-Wallet zu verifizieren, müssen Sie:
                                </p>
                                <ol class="mb-0" style="color: #555; font-size: 14px; line-height: 2;">
                                    <li>Ihre Wallet-Adresse zu Ihrem Profil hinzufügen</li>
                                    <li>Eine kleine "Satoshi-Test"-Transaktion durchführen (einen winzigen Betrag senden)</li>
                                    <li>Auf die Admin-Genehmigung warten (normalerweise innerhalb von 24 Stunden)</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="alert alert-danger border-0" style="background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05)); border-radius: 10px;">
                            <div class="d-flex align-items-start">
                                <i class="anticon anticon-warning mr-3" style="font-size: 24px; color: #dc3545;"></i>
                                <div>
                                    <strong style="color: #dc3545;">Wichtiger Sicherheitshinweis</strong>
                                    <p class="mb-0 mt-2" style="color: #555;">
                                        Ohne Wallet-Verifizierung können Auszahlungsanträge nicht bearbeitet werden. Diese Sicherheitsmaßnahme verhindert Gelddiebstahl 
                                        und stellt sicher, dass wiederhergestellte Vermögenswerte den rechtmäßigen Eigentümer erreichen. Die Verifizierung ist ein einmaliger Prozess.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 15px 15px;">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">
                            <i class="anticon anticon-close mr-1"></i>Schließen
                        </button>
                        <a href="payment-methods.php" class="btn btn-info" style="border-radius: 8px; font-weight: 500;">
                            <i class="anticon anticon-arrow-right mr-1"></i>Krypto-Adresse verifizieren
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Verification Info Modal -->
     <div class="modal fade" id="emailVerifyInfoModal" tabindex="-1" role="dialog" aria-labelledby="emailVerifyInfoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="modal-header border-0" style="background: linear-gradient(135deg, #dc3545, #e74c5d); color: #fff; border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title font-weight-bold" id="emailVerifyInfoModalLabel">
                            <i class="anticon anticon-mail mr-2"></i>Warum Ihre E-Mail-Adresse verifizieren?
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <h6 class="text-primary mb-3" style="font-weight: 600;">
                                <i class="anticon anticon-safety-certificate mr-2"></i>Kontosicherheit & Kommunikation
                            </h6>
                            <p style="line-height: 1.8; color: #555;">
                                Die E-Mail-Verifizierung bestätigt, dass Sie Zugriff auf die mit Ihrem Konto verknüpfte E-Mail-Adresse haben. 
                                Dies ist für sichere Kommunikation, Passwortwiederherstellung und den Empfang wichtiger Benachrichtigungen über Ihre Wiederherstellungsfälle unerlässlich.
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="text-success mb-3" style="font-weight: 600;">
                                <i class="anticon anticon-check-circle mr-2"></i>Vorteile der E-Mail-Verifizierung
                            </h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="anticon anticon-mail text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Wichtige Benachrichtigungen:</strong> Erhalten Sie sofortige Updates über Ihren Fallstatus, Auszahlungen und Wiederherstellungen.
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="anticon anticon-lock text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Kontowiederherstellung:</strong> Aktivieren Sie Optionen zum Zurücksetzen des Passworts und zur Wiederherstellung des Kontos, wenn Sie den Zugriff verlieren.
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="anticon anticon-check text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Vollständiges Profil:</strong> Letzter Schritt, um alle Plattformfunktionen und volle Funktionalität freizuschalten.
                                    </div>
                                </li>
                                <li class="d-flex align-items-start">
                                    <i class="anticon anticon-shield text-success mr-3 mt-1" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>Sicherheitswarnungen:</strong> Werden Sie über verdächtige Aktivitäten oder Anmeldeversuche auf Ihrem Konto benachrichtigt.
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-info border-0" style="background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(23, 162, 184, 0.05)); border-radius: 10px;">
                            <div class="d-flex align-items-start">
                                <i class="anticon anticon-info-circle mr-3" style="font-size: 24px; color: #17a2b8;"></i>
                                <div>
                                    <strong style="color: #17a2b8;">Schneller Verifizierungsprozess</strong>
                                    <p class="mb-0 mt-2" style="color: #555;">
                                        Klicken Sie oben auf die Schaltfläche "Verifizierungs-E-Mail senden", überprüfen Sie Ihren Posteingang auf unsere E-Mail 
                                        und klicken Sie auf den Verifizierungslink. Der Vorgang dauert weniger als 1 Minute.
                                        Der Verifizierungslink läuft aus Sicherheitsgründen nach 1 Stunde ab.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 15px 15px;">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">
                            <i class="anticon anticon-close mr-1"></i>Schließen
                        </button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal" style="border-radius: 8px; font-weight: 500;" onclick="$('#sendVerificationEmailBtn').click();">
                            <i class="anticon anticon-mail mr-1"></i>Verifizierungs-E-Mail senden
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- HEADER & BRAND -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card brand-header-card" style="background: <?= htmlspecialchars($brandGradient, ENT_QUOTES) ?>; color: #fff; border: none; overflow: hidden;">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between py-4">
                        <div class="brand-content">
                            <div class="h4 mb-2 text-white" style="font-weight: 700; letter-spacing: 0.3px;">
                                <i class="anticon anticon-safety-certificate mr-2"></i>
                                <?= htmlspecialchars($appName, ENT_QUOTES) ?>
                            </div>
                            <div class="lead mb-3" style="color: rgba(255,255,255,0.95); font-size: 1.05rem;">
                                <?= htmlspecialchars($appTagline, ENT_QUOTES) ?>
                            </div>
                            <div class="mt-3 d-flex flex-wrap">
                                <span class="badge badge-light px-3 py-2 mr-2 mb-2" style="color: var(--brand); background: rgba(255,255,255,0.95); font-weight: 500;">
                                    <i class="anticon anticon-lock mr-1"></i> Verschlüsselt & Sicher
                                </span>
                                <span class="badge badge-success px-3 py-2 mr-2 mb-2" id="ai-status-badge" role="status" aria-live="polite" style="font-weight: 500;">
                                    <i class="anticon anticon-check-circle mr-1"></i> KI-Status: <span id="aiStatusText"><?= htmlspecialchars($aiStatus, ENT_QUOTES) ?></span>
                                </span>
                                <span class="badge badge-info px-3 py-2 mb-2" style="font-weight: 500;">
                                    <i class="anticon anticon-clock-circle mr-1"></i> Letzter Scan: <span id="lastScanText"><?= htmlspecialchars($lastAIScan, ENT_QUOTES) ?></span>
                                </span>
                            </div>
                        </div>
                        <div class="text-right mt-3 mt-md-0">
                            <div class="mb-3">
                                <div class="badge badge-pill px-4 py-2" style="font-size: 1.05em; background: rgba(255,255,255,0.2); color: #fff; font-weight: 500;">
                                    <i class="anticon anticon-user mr-1"></i> Willkommen, <?= htmlspecialchars($currentUser['first_name'] ?? $currentUserLogin, ENT_QUOTES) ?>!
                                </div>
                            </div>
                            <div class="mt-2 p-3 rounded" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                                <div class="text-white mb-1" style="font-size: 0.9em; opacity: 0.9; font-weight: 500;">
                                    <i class="anticon anticon-wallet mr-1"></i> Kontostand
                                </div>
                                <div class="h2 font-weight-bold text-white mb-0" id="balanceCounter" data-value="<?= number_format($currentUser['balance'] ?? 0,2, '.', '') ?>">
                                    €<?= number_format($currentUser['balance'] ?? 0,2) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- AI INSIGHT CARD -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card shadow-sm border-0 h-100" aria-labelledby="aiInsightsHeading">
                    <div class="card-body">
                        <h5 id="aiInsightsHeading" class="mb-3" style="color: #2c3e50; font-weight: 600;">
                            <i class="anticon anticon-robot text-primary mr-2"></i> KI-Einblicke
                        </h5>
                        <ul class="list-unstyled mb-0" style="line-height: 2;">
                            <li class="d-flex align-items-start mb-2">
                                <i class="anticon anticon-check-circle text-success mr-2 mt-1"></i>
                                <span style="font-size: 14px;">Kontinuierliche Überwachung auf verdächtige Aktivitäten</span>
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="anticon anticon-clock-circle text-info mr-2 mt-1"></i>
                                <span style="font-size: 14px;">Nächster Scan: <strong id="nextScan"><?= date('d.m., H:i', strtotime('+1 hour')) ?></strong></span>
                            </li>
                            <li class="d-flex align-items-start">
                                <i class="anticon anticon-<?= $passwordChangeRequired ? 'exclamation-circle text-warning' : 'shield text-success' ?> mr-2 mt-1"></i>
                                <?php if ($passwordChangeRequired): ?>
                                    <span class="text-danger" style="font-size: 14px; font-weight: 500;">Aktion erforderlich: Passwort ändern</span>
                                <?php else: ?>
                                    <span class="text-success" style="font-size: 14px;">Sicherheitsstatus: Ausgezeichnet</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- KYC/AML -->
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card shadow-sm border-0 h-100" aria-labelledby="complianceHeading">
                    <div class="card-body">
                        <h5 id="complianceHeading" class="mb-3" style="color: #2c3e50; font-weight: 600;">
                            <i class="anticon anticon-safety-certificate text-success mr-2"></i> Compliance
                        </h5>
                        <div class="mb-3">
                            <label class="text-muted mb-1" style="font-size: 13px;">KYC-Verifizierungsstatus</label>
                            <div>
                            <?php
                            $kycStatus = $kyc_status;
                            $kycBadge = "secondary";
                            $kycIcon = "question-circle";
                            if ($kycStatus === 'approved') {
                                $kycBadge = "success";
                                $kycStatus = "verified";
                                $kycIcon = "check-circle";
                            } elseif ($kycStatus === 'rejected') {
                                $kycBadge = "danger";
                                $kycIcon = "close-circle";
                            } elseif ($kycStatus === 'pending') {
                                $kycBadge = "warning";
                                $kycIcon = "clock-circle";
                            }
                            $kycStatusDe = [
                                'verified' => 'Verifiziert',
                                'approved' => 'Genehmigt',
                                'rejected' => 'Abgelehnt',
                                'pending'  => 'Ausstehend',
                            ][$kycStatus] ?? ucfirst($kycStatus);
                            ?>
                                <span class="badge badge-<?= htmlspecialchars($kycBadge, ENT_QUOTES) ?> px-3 py-2">
                                    <i class="anticon anticon-<?= htmlspecialchars($kycIcon, ENT_QUOTES) ?> mr-1"></i>
                                    <?= htmlspecialchars($kycStatusDe, ENT_QUOTES) ?>
                                </span>
                            </div>
                        </div>
                        <p class="text-muted mb-3" style="font-size: 13px; line-height: 1.6;">
                            Die KYC-Verifizierung ist für Auszahlungen und erweiterte Wiederherstellungstools erforderlich.
                        </p>
                        <?php if ($kycStatus == "pending"): ?>
                            <a href="kyc.php" class="btn btn-primary btn-sm btn-block" role="button">
                                <i class="anticon anticon-safety-certificate mr-1"></i> Verifizierung abschließen
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Security -->
            <div class="col-md-12 col-lg-4 mb-3">
                <div class="card shadow-sm border-0 h-100" aria-labelledby="securityHeading">
                    <div class="card-body">
                        <h5 id="securityHeading" class="mb-3" style="color: #2c3e50; font-weight: 600;">
                            <i class="anticon anticon-lock text-primary mr-2"></i> Sicherheit
                        </h5>
                        <div class="mb-3">
                            <label class="text-muted mb-1" style="font-size: 13px;">Letzte Anmeldung</label>
                            <p class="mb-0 font-weight-500" style="font-size: 14px;">
                                <i class="anticon anticon-calendar mr-1"></i>
                                <?= htmlspecialchars($currentUser['last_login'] ?? $currentDateTimeFormatted, ENT_QUOTES) ?>
                            </p>
                        </div>

                        <div class="alert alert-warning mb-0 py-2 px-3" style="font-size: 13px; border-radius: 8px;">
                            <i class="anticon anticon-info-circle mr-1"></i>
                            Verdächtige Aktivität? <a href="support.php" class="alert-link font-weight-600">Support kontaktieren</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center py-3">
                        <div class="mb-2 mb-md-0">
                            <h5 class="card-title mb-1" style="color: #2c3e50; font-weight: 600;">
                                <i class="anticon anticon-thunderbolt text-warning mr-2"></i>Schnellaktionen
                            </h5>
                            <p class="card-text small text-muted mb-0" style="font-size: 13px;">Häufige Transaktionen schnell und sicher ausführen</p>
                        </div>
                        <div class="d-flex flex-wrap" role="group" aria-label="Schnellaktionen">
                            <button class="btn btn-primary mr-2 mb-2" data-toggle="modal" data-target="#newDepositModal" title="Guthaben aufladen">
                                <i class="anticon anticon-plus-circle mr-1"></i> Neue Einzahlung
                            </button>
                            <button class="btn btn-success mr-2 mb-2" onclick="checkWithdrawalEligibility(event)" title="Auszahlung beantragen">
                                <i class="anticon anticon-download mr-1"></i> Neue Auszahlung
                            </button>
                            <a href="transactions.php" class="btn btn-info mb-2" title="Alle Transaktionen anzeigen">
                                <i class="anticon anticon-history mr-1"></i> Transaktionen
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Row -->
        <div class="row mb-3">
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-icon avatar-lg avatar-blue mr-3" aria-hidden="true">
                                <i class="anticon anticon-file-text"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h2 class="mb-1 font-weight-bold count" data-value="<?= htmlspecialchars($stats['total_cases'], ENT_QUOTES) ?>" style="color: #2c3e50;">
                                    <?= htmlspecialchars($stats['total_cases'], ENT_QUOTES) ?>
                                </h2>
                                <p class="mb-1 text-muted font-weight-500" style="font-size: 14px;">Gesamte Fälle</p>
                                <?php if ($stats['last_case_date']): ?>
                                <small class="text-muted" style="font-size: 12px;">
                                    <i class="anticon anticon-calendar mr-1"></i><?= htmlspecialchars(date('M d, Y', strtotime($stats['last_case_date'])), ENT_QUOTES) ?>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-icon avatar-lg avatar-cyan mr-3" aria-hidden="true">
                                <i class="anticon anticon-line-chart"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h2 class="mb-1 font-weight-bold count percent" data-value="<?= htmlspecialchars($recoveryPercentage, ENT_QUOTES) ?>" style="color: #2c3e50;">
                                    <?= htmlspecialchars($recoveryPercentage, ENT_QUOTES) ?>%
                                </h2>
                                <p class="mb-1 text-muted font-weight-500" style="font-size: 14px;">Wiederherstellungsquote</p>
                                <small class="badge badge-<?= $recoveryPercentage >= 50 ? 'success' : 'warning' ?>" style="font-size: 11px;">
                                    <i class="anticon anticon-<?= $recoveryPercentage >= 50 ? 'arrow-up' : 'arrow-down' ?> mr-1"></i>
                                    <?= $recoveryPercentage >= 50 ? 'Überdurchschnittlich' : 'Unterdurchschnittlich' ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-icon avatar-lg avatar-gold mr-3" aria-hidden="true">
                                <i class="anticon anticon-exclamation-circle"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h2 class="mb-1 font-weight-bold count money" data-value="<?= htmlspecialchars($stats['total_reported'], ENT_QUOTES) ?>" style="color: #2c3e50;">
                                    €<?= number_format($stats['total_reported'], 2) ?>
                                </h2>
                                <p class="mb-1 text-muted font-weight-500" style="font-size: 14px;">Gemeldeter Verlust</p>
                                <?php if ($outstandingAmount > 0): ?>
                                <small class="badge badge-danger" style="font-size: 11px;">
                                    <i class="anticon anticon-warning mr-1"></i>€<?= number_format($outstandingAmount, 2) ?> ausstehend
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-icon avatar-lg avatar-purple mr-3" aria-hidden="true">
                                <i class="anticon anticon-check-circle"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h2 class="mb-1 font-weight-bold count money" data-value="<?= htmlspecialchars($stats['total_recovered'], ENT_QUOTES) ?>" style="color: #2c3e50;">
                                    €<?= number_format($stats['total_recovered'], 2) ?>
                                </h2>
                                <p class="mb-1 text-muted font-weight-500" style="font-size: 14px;">Wiederbeschaffter Betrag</p>
                                <?php if ($stats['total_recovered'] > 0): ?>
                                <small class="badge badge-success pulse" style="font-size: 11px;">
                                    <i class="anticon anticon-rise mr-1"></i><?= htmlspecialchars($recoveryPercentage, ENT_QUOTES) ?>% zurückgewonnen
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recovery / Workflow -->
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                            <h5 class="mb-2 mb-md-0" style="color: #2c3e50; font-weight: 600;">
                                <i class="anticon anticon-sync mr-2" style="color: var(--brand);"></i>Wiederherstellungsstatus
                            </h5>
                            <div>
                                <span class="badge badge-pill px-3 py-2 badge-<?= $recoveryPercentage > 70 ? 'success' : ($recoveryPercentage > 30 ? 'warning' : 'danger') ?>" style="font-size: 13px;">
                                    <i class="anticon anticon-<?= $recoveryPercentage > 70 ? 'check-circle' : ($recoveryPercentage > 30 ? 'clock-circle' : 'exclamation-circle') ?> mr-1"></i>
                                    <?= $recoveryPercentage > 70 ? 'Ausgezeichneter Fortschritt' : ($recoveryPercentage > 30 ? 'Guter Fortschritt' : 'Aufmerksamkeit erforderlich') ?>
                                </span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="algorithm-animation">
                                <div class="algorithm-steps" aria-hidden="true">
                                    <div class="step <?= $recoveryPercentage > 0 ? 'active' : '' ?>">
                                        <div class="step-icon">
                                            <i class="anticon anticon-search"></i>
                                        </div>
                                        <div class="step-label">Gelder verfolgen</div>
                                    </div>
                                    <div class="step <?= $recoveryPercentage > 20 ? 'active' : '' ?>">
                                        <div class="step-icon">
                                            <i class="anticon anticon-lock"></i>
                                        </div>
                                        <div class="step-label">Vermögen einfrieren</div>
                                    </div>
                                    <div class="step <?= $recoveryPercentage > 40 ? 'active' : '' ?>">
                                        <div class="step-icon">
                                            <i class="anticon anticon-solution"></i>
                                        </div>
                                        <div class="step-label">Rechtsverfahren</div>
                                    </div>
                                    <div class="step <?= $recoveryPercentage > 60 ? 'active' : '' ?>">
                                        <div class="step-icon">
                                            <i class="anticon anticon-sync"></i>
                                        </div>
                                        <div class="step-label">Wiederherstellung</div>
                                    </div>
                                    <div class="step <?= $recoveryPercentage > 80 ? 'active' : '' ?>">
                                        <div class="step-icon">
                                            <i class="anticon anticon-check-circle"></i>
                                        </div>
                                        <div class="step-label">Abgeschlossen</div>
                                    </div>
                                </div>
                                <div class="algorithm-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= htmlspecialchars($recoveryPercentage, ENT_QUOTES) ?>">
                                    <div class="progress-bar" style="width: <?= $recoveryPercentage ?>%"></div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-center">
                                    <div class="text-left">
                                        <p class="m-b-5"><strong>Gesamte Fälle:</strong> <?= htmlspecialchars($stats['total_cases'], ENT_QUOTES) ?></p>
                                        <p class="m-b-5"><strong>Aktive Fälle:</strong> <?= array_sum($statusCounts) ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="m-b-5"><strong>Zurückgewonnen:</strong> €<?= number_format($stats['total_recovered'], 2) ?></p>
                                        <p class="m-b-5"><strong>Ausstehend:</strong> €<?= number_format($outstandingAmount, 2) ?></p>
                                    </div>
                                </div>
                                <button class="btn btn-outline-primary btn-sm" id="refresh-algorithm" aria-live="polite">
                                    <i class="anticon anticon-sync"></i> Status aktualisieren
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Cases + Side column -->
        <div class="row mt-3">
            <div class="col-md-12 col-lg-8">
                <!-- Recent Cases -->
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                            <h5 class="mb-2 mb-md-0" style="color: #2c3e50; font-weight: 600;">
                                <i class="anticon anticon-folder-open mr-2" style="color: var(--brand);"></i>Aktuelle Fälle
                            </h5>
                            <div class="d-flex">
                                <a href="cases.php" class="btn btn-sm btn-outline-primary mr-2">
                                    <i class="anticon anticon-eye mr-1"></i>Alle ansehen
                                </a>
                                <a href="new-case.php" class="btn btn-sm btn-primary">
                                    <i class="anticon anticon-plus-circle mr-1"></i>Neuer Fall
                                </a>
                            </div>
                        </div>
                        
                        <?php if (empty($cases)): ?>
                            <div class="alert alert-info mt-3 d-flex align-items-center" style="border-radius: 10px;">
                                <i class="anticon anticon-info-circle mr-2" style="font-size: 20px;"></i>
                                <div>Keine Fälle gefunden. <a href="new-case.php" class="alert-link font-weight-600">Ersten Fall einreichen</a></div>
                            </div>
                        <?php else: ?>
                            <div class="mt-3">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Fall-Nr.</th>
                                                <th>Plattform</th>
                                                <th>Gemeldet</th>
                                                <th>Zurückgewonnen</th>
                                                <th>Status</th>
                                                <th>Aktionen</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cases as $case): 
                                                $reported = (float)($case['reported_amount'] ?? 0);
                                                $recovered = (float)($case['recovered_amount'] ?? 0);
                                                $status = $case['status'] ?? 'open';
                                                
                                                $progress = ($reported > 0) ? round(($recovered / $reported) * 100, 2) : 0;
                                                
                                                $statusClass = [
                                                    'open' => 'warning',
                                                    'documents_required' => 'secondary',
                                                    'under_review' => 'info',
                                                    'refund_approved' => 'success',
                                                    'refund_rejected' => 'danger',
                                                    'closed' => 'dark'
                                                ][$status] ?? 'light';
                                            ?>
                                            <tr>
                                                <td>
                                                    <a href="case-details.php?id=<?= htmlspecialchars($case['id'], ENT_QUOTES) ?>">
                                                        <?= htmlspecialchars($case['case_number'], ENT_QUOTES) ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <div class="media align-items-center">
                                                        <?php if (!empty($case['platform_logo'])): ?>
                                                        <div class="avatar avatar-image" style="width: 34px; height: 34px">
                                                            <img src="<?= htmlspecialchars($case['platform_logo'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($case['platform_name'], ENT_QUOTES) ?>">
                                                        </div>
                                                        <?php endif; ?>
                                                        <div class="m-l-10">
                                                            <?= htmlspecialchars($case['platform_name'], ENT_QUOTES) ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>$<?= number_format($reported, 2) ?></td>
                                                <td style="min-width:180px">
                                                    <div>
                                                        <strong style="font-size:14px;color:#2c3e50;">$<?= number_format($recovered, 2) ?></strong>
                                                    </div>
                                                    <div class="mt-1">
                                                        <div class="progress" style="height:6px;border-radius:3px;">
                                                            <div class="progress-bar" 
                                                                 style="width:<?= htmlspecialchars($progress, ENT_QUOTES) ?>%;background:linear-gradient(90deg,#2950a8,#2da9e3);"
                                                                 role="progressbar" 
                                                                 aria-valuenow="<?= htmlspecialchars($progress, ENT_QUOTES) ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-1">
                                                        <small class="text-muted" style="font-size:11px;"><?= htmlspecialchars($progress, ENT_QUOTES) ?>% von €<?= number_format($reported, 2) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-pill badge-<?= htmlspecialchars($statusClass, ENT_QUOTES) ?>">
                                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $status)), ENT_QUOTES) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary view-case-btn" 
                                                            data-case-id="<?= htmlspecialchars($case['id'], ENT_QUOTES) ?>" 
                                                            title="Falldetails anzeigen">
                                                        <i class="anticon anticon-eye"></i> Anzeigen
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Active Recovery Operations -->
                <div class="card mt-3 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                            <h5 class="mb-2 mb-md-0" style="color: #2c3e50; font-weight: 600;">
                                <i class="anticon anticon-sync mr-2" style="color: var(--brand);"></i>Aktive Wiederherstellungsoperationen
                            </h5>
                            <span class="badge badge-info px-3 py-2" style="font-size: 13px;">
                                <i class="anticon anticon-file-text mr-1"></i><?= count($ongoingRecoveries) ?> aktive Fälle
                            </span>
                        </div>
                        <div class="mt-3">
                            <?php if (empty($ongoingRecoveries)): ?>
                                <div class="alert alert-info d-flex align-items-center" style="border-radius: 10px;">
                                    <i class="anticon anticon-info-circle mr-2" style="font-size: 20px;"></i>
                                    <span>Keine aktiven Wiederherstellungsoperationen</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($ongoingRecoveries as $recovery): 
                                    $reported = (float)($recovery['reported_amount'] ?? 0);
                                    $recovered = (float)($recovery['recovered_amount'] ?? 0);
                                    $status = $recovery['status'] ?? 'open';
                                    
                                    $progress = ($reported > 0) ? round(($recovered / $reported) * 100, 2) : 0;
                                    
                                    $statusClass = 'info';
                                    $statusText = 'In Bearbeitung';
                                    
                                    if ($status === 'documents_required') {
                                        $statusClass = 'danger';
                                        $statusText = 'Aufmerksamkeit erforderlich';
                                    } elseif ($progress > 70) {
                                        $statusClass = 'success';
                                        $statusText = 'Auf Kurs';
                                    } elseif ($progress > 30) {
                                        $statusClass = 'warning';
                                        $statusText = 'In Bearbeitung';
                                    }
                                ?>
                                <div class="m-b-25">
                                    <div class="d-flex justify-content-between m-b-5">
                                        <div>
                                            <button class="btn btn-link p-0 view-case-btn" 
                                                    data-case-id="<?= htmlspecialchars($recovery['id'], ENT_QUOTES) ?>" 
                                                    style="color: var(--brand); text-decoration: none; font-weight: 600;">
                                                <?= htmlspecialchars($recovery['case_number'], ENT_QUOTES) ?>
                                            </button>
                                        </div>
                                        <div class="text-right">
                                            <span><?= htmlspecialchars($progress, ENT_QUOTES) ?>%</span>
                                            <div class="text-<?= htmlspecialchars($statusClass, ENT_QUOTES) ?>">
                                                <?= htmlspecialchars($statusText, ENT_QUOTES) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-<?= htmlspecialchars($statusClass, ENT_QUOTES) ?>" 
                                             style="width: <?= htmlspecialchars($progress, ENT_QUOTES) ?>%" 
                                             aria-valuenow="<?= htmlspecialchars($progress, ENT_QUOTES) ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between m-t-10">
                                        <small class="text-muted">
                                            Gemeldet: €<?= number_format($reported, 2) ?>
                                        </small>
                                        <small class="text-muted">
                                            Zurückgewonnen: €<?= number_format($recovered, 2) ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="text-center m-t-20">
                                    <a href="cases.php" class="btn btn-sm btn-outline-primary">
                                        <i class="anticon anticon-eye"></i> Alle Fälle ansehen
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right column -->
            <div class="col-md-12 col-lg-4">

                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-body">
                        <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                            <i class="anticon anticon-transaction mr-2" style="color: var(--brand);"></i>Aktuelle Transaktionen
                        </h5>
                        <div style="min-height: 300px">
                            <?php if (empty($transactions)): ?>
                                <div class="alert alert-info d-flex align-items-center mt-3" style="border-radius: 10px;">
                                    <i class="anticon anticon-info-circle mr-2" style="font-size: 20px;"></i>
                                    <span>Noch keine Transaktionen</span>
                                </div>
                            <?php else: ?>
                                <div class="scrollable" style="height: 280px">
                                    <?php foreach ($transactions as $transaction): ?>
                                    <div class="m-b-20">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <?php
                                                $iconConfig = [
                                                    'refund'     => ['icon' => 'arrow-up',   'color' => 'success', 'label' => 'Rückerstattung'],
                                                    'deposit'    => ['icon' => 'arrow-down',  'color' => 'primary', 'label' => 'Einzahlung'],
                                                    'withdrawal' => ['icon' => 'arrow-up',   'color' => 'danger',  'label' => 'Auszahlung'],
                                                    'fee'        => ['icon' => 'minus',       'color' => 'warning', 'label' => 'Gebühr']
                                                ];
                                                $config = $iconConfig[$transaction['type']] ?? ['icon' => 'swap', 'color' => 'info', 'label' => 'Transaktion'];
                                                ?>
                                                <div class="avatar avatar-icon avatar-<?= htmlspecialchars($config['color'], ENT_QUOTES) ?>" aria-hidden="true">
                                                    <i class="anticon anticon-<?= htmlspecialchars($config['icon'], ENT_QUOTES) ?>"></i>
                                                </div>
                                                <div class="m-l-15">
                                                    <h6 class="m-b-0"><?= htmlspecialchars($config['label'], ENT_QUOTES) ?></h6>
                                                    <p class="m-b-0 text-muted">
                                                        <?= htmlspecialchars($transaction['reference_name'], ENT_QUOTES) ?>
                                                        <br>
                                                        <small><?= date('M d, Y', strtotime($transaction['created_at'])) ?></small>
                                                    </p>
                                                </div>
                                            </div>
                                            <span class="text-<?= in_array($transaction['type'], ['refund', 'deposit']) ? 'success' : 'danger' ?> font-weight-semibold">
                                                $<?= number_format($transaction['amount'], 2) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <h5 class="m-b-20">Fallstatuszusammenfassung</h5>
                        <div class="m-t-20">
                            <?php if (empty($statusCounts)): ?>
                                <div class="alert alert-info">Keine Fälle gefunden</div>
                            <?php else: ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <canvas id="statusChart" height="200" aria-label="Case status chart"></canvas>
                                    </div>
                                </div>
                                <div class="m-t-20">
                                    <ul class="list-group list-group-flush">
                                        <?php 
                                        $statusDe = [
                                            'open'               => 'Offen',
                                            'documents_required' => 'Dokumente erforderlich',
                                            'under_review'       => 'In Prüfung',
                                            'refund_approved'    => 'Rückerstattung genehmigt',
                                            'refund_rejected'    => 'Rückerstattung abgelehnt',
                                            'closed'             => 'Abgeschlossen',
                                        ];
                                        foreach ($statusCounts as $status => $count): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center p-l-0 p-r-0">
                                            <?= htmlspecialchars($statusDe[$status] ?? ucwords(str_replace('_', ' ', $status)), ENT_QUOTES) ?>
                                            <span class="badge badge-pill badge-<?= htmlspecialchars([
                                                'open' => 'warning',
                                                'documents_required' => 'secondary',
                                                'under_review' => 'info',
                                                'refund_approved' => 'success',
                                                'refund_rejected' => 'danger',
                                                'closed' => 'dark'
                                            ][$status] ?? 'light', ENT_QUOTES) ?>"><?= htmlspecialchars($count, ENT_QUOTES) ?></span>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Recovery Progress -->
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 style="color: #2c3e50; font-weight: 600;">
                                <i class="anticon anticon-line-chart mr-2" style="color: var(--brand);"></i>Wiederherstellungsfortschritt
                            </h5>
                            <div>
                                <span class="badge badge-<?= $recoveryPercentage >= 50 ? 'success' : 'warning' ?> px-3 py-2">
                                    <i class="anticon anticon-<?= $recoveryPercentage >= 50 ? 'check-circle' : 'clock-circle' ?> mr-1"></i>
                                    <?= $recoveryPercentage >= 50 ? 'Guter Fortschritt' : 'Aufmerksamkeit erforderlich' ?>
                                </span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="font-weight-semibold">
                                    Gesamte Wiederherstellung: <span class="count" data-value="<?= htmlspecialchars($recoveryPercentage, ENT_QUOTES) ?>"><?= htmlspecialchars($recoveryPercentage, ENT_QUOTES) ?>%</span>
                                    (<?= htmlspecialchars($stats['total_cases'], ENT_QUOTES) ?> Fälle)
                                </span>
                                <span>
                                    €<?= number_format($stats['total_recovered'], 2) ?> von €<?= number_format($stats['total_reported'], 2) ?>
                                </span>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 10px;" aria-hidden="false" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= htmlspecialchars($recoveryPercentage, ENT_QUOTES) ?>">
                                <div class="progress-bar bg-success" style="width: <?= $recoveryPercentage ?>%; background: linear-gradient(90deg, #28a745, #20c997);"></div>
                            </div>
                            <div class="mt-2 d-flex justify-content-between">
                                <small class="text-muted">0%</small>
                                <small class="text-muted">100%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Professional Case Details Modal -->
<div class="modal fade" id="caseDetailsModal" tabindex="-1" role="dialog" aria-labelledby="caseDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%); color: #fff; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold" id="caseDetailsModalLabel">
                    <i class="anticon anticon-file-text mr-2"></i>Falldetails
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4" id="caseModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Laden...</span>
                    </div>
                    <p class="mt-3 text-muted">Falldetails werden geladen...</p>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="anticon anticon-close mr-1"></i>Schließen
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// include footer safely
if (file_exists(__DIR__ . '/footer.php')) {
    include __DIR__ . '/footer.php';
} else {
    echo "<!-- footer.php missing; page ended -->\n";
}
?>

<script>
$(function(){
    // Tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // animate counts
    function animateCount(el, start, end, decimals, duration) {
        decimals = decimals || 0;
        var current = start;
        var range = end - start;
        var increment = range / (duration / 30);
        var timer = setInterval(function() {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            $(el).text((decimals ? current.toFixed(decimals) : Math.round(current)).toString() + (el.dataset.suffix || ''));
        }, 30);
    }

    $('.count').each(function(){
        var $el = $(this);
        var end = parseFloat($el.data('value')) || 0;
        var start = 0;
        var decimals = (String(end).indexOf('.') !== -1) ? 2 : 0;
        animateCount(this, start, end, decimals, 700);
    });

    var $balance = $('#balanceCounter');
    if ($balance.length) {
        var bval = parseFloat($balance.data('value')) || 0;
        var bstart = 0;
        var btimer = setInterval(function() {
            bstart += Math.max(0.01, (bval - bstart) / 12);
            if (bstart >= bval) { bstart = bval; clearInterval(btimer); }
            $balance.text('€' + bstart.toFixed(2));
        }, 40);
    }

    function animateLiveProgress(el) {
        var $bar = $(el);
        var finalVal = parseFloat($bar.data('final')) || 0;
        var progressLabel = $bar.parent().find('[data-progress-label]');
        var live = 0;
        $bar.css('width', live + '%');
        
        var step = function() {
            live += Math.max(0.5, (finalVal-live)/6);
            if (live >= finalVal) {
                live = finalVal;
                $bar.css('width', finalVal + '%');
                progressLabel.text(finalVal + '%');
            } else {
                $bar.css('width', live + '%');
                progressLabel.text(Math.round(live * 100) / 100 + '%');
                setTimeout(step, 60 + Math.random()*60);
            }
        };
        setTimeout(step, 200 + Math.random()*200);
    }
    $('.live-progress').each(function() { animateLiveProgress(this); });

    // Copy wallet address
    $(document).on('click', '#copyWalletAddress', function() {
        var walletAddress = $('#detail-wallet-address').val();
        if (!walletAddress) { toastr.warning('Keine Adresse zum Kopieren'); return; }
        navigator.clipboard.writeText(walletAddress).then(function() {
            toastr.success('Wallet-Adresse in die Zwischenablage kopiert');
        }, function() {
            toastr.error('Kopieren der Wallet-Adresse fehlgeschlagen');
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
        
        $submitBtn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Wird verarbeitet...');
        
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
                        toastr.success(data.message || 'Einzahlung erfolgreich eingereicht');
                        $('#newDepositModal').modal('hide');
                        $form[0].reset();
                        $('.custom-file-label').html('Datei auswählen');
                        $('#paymentDetails').hide();
                        setTimeout(function(){ location.reload(); }, 1200);
                    } else {
                        toastr.error(data.message || 'Fehler bei der Einzahlung');
                    }
                } catch (e) {
                    toastr.error('Fehler beim Verarbeiten der Serverantwort');
                }
                $submitBtn.prop('disabled', false).html('Einzahlung bestätigen');
            },
            error: function(xhr, status, error) {
                toastr.error('Kommunikationsfehler mit dem Server: ' + error);
                $submitBtn.prop('disabled', false).html('Einzahlung bestätigen');
            }
        });
    });

// =====================================================
// 💸 WITHDRAWAL FORM SUBMIT (WITH OTP + BALANCE CHECK)
// =====================================================
$('#withdrawalForm').submit(function (e) {
    e.preventDefault();
    const $form = $(this);
    const $submitBtn = $form.find('button[type="submit"]');

    // Ensure OTP verified (button enabled only after verification)
    if ($('#withdrawalSubmitBtn').prop('disabled')) {
        toastr.warning('Bitte verifizieren Sie Ihren OTP, bevor Sie den Antrag einreichen.');
        return;
    }

    // Validate balance and amount before sending
    const available = parseFloat($('#availableBalance').val()) || 0;
    const amount = parseFloat($('#amount').val()) || 0;
    if (available < 1000) {
        toastr.error('Unzureichendes Guthaben. Mindestguthaben für Auszahlungen: €1.000.');
        return;
    }
    if (amount < 1000) {
        toastr.error('Mindestbetrag für Auszahlungen: €1.000.');
        return;
    }
    if (amount > available) {
        toastr.error('Unzureichendes Guthaben. Verfügbar: €' + available.toFixed(2));
        return;
    }

    // Send request
    $submitBtn.prop('disabled', true)
        .html('<i class="anticon anticon-loading anticon-spin"></i> Wird verarbeitet...');

    $.ajax({
        url: 'ajax/process-withdrawal.php',
        method: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                toastr.success(response.message || 'Auszahlungsantrag erfolgreich eingereicht');
                $('#newWithdrawalModal').modal('hide');
                $form[0].reset();
                resetOtpFields();
                setTimeout(() => location.reload(), 1200);
            } else {
                toastr.error(response.message || 'Fehler bei der Bearbeitung des Auszahlungsantrags');
                if (response.message && response.message.includes('OTP')) resetOtpFields();
            }
        },
        error: function (xhr, status, error) {
            console.error('Withdrawal error:', xhr.status, xhr.responseText);
            let errorMsg = 'Serverkommunikationsfehler: ' + error;
            
            try {
                const errorData = JSON.parse(xhr.responseText);
                if (errorData.message) {
                    errorMsg = errorData.message;
                }
            } catch (e) {
                if (xhr.status === 400) {
                    errorMsg = 'Ungültige Anfrage – Bitte überprüfen Sie Ihre Eingaben';
                } else if (xhr.status === 403) {
                    errorMsg = 'Sicherheitsfehler – Bitte laden Sie die Seite neu';
                } else if (xhr.status === 401) {
                    errorMsg = 'Sitzung abgelaufen – Bitte erneut anmelden';
                }
            }
            
            toastr.error(errorMsg);
        },
        complete: function () {
            $submitBtn.prop('disabled', false).html('Antrag einreichen');
        }
    });
});


// =====================================================
// 🏦 WITHDRAWAL METHOD AUTO-FILL (USER'S VERIFIED ADDRESSES)
// =====================================================
$('#withdrawalMethod').change(function () {
    const $selected = $(this).find('option:selected');
    const details = $selected.data('details') || '';
    const type = $selected.data('type') || '';
    
    // Auto-fill payment details textarea with user's verified address/account
    if (details) {
        $('textarea[name="payment_details"]').val(details);
        toastr.success('Zahlungsdetails wurden automatisch ausgefüllt (' + (type === 'crypto' ? 'Wallet-Adresse' : 'Bankkonto') + ')');
    } else {
        $('textarea[name="payment_details"]').val('');
    }
    
    // Hide bank details container (no longer needed with direct auto-fill)
    $('#bankDetailsContainer').hide();
});


// =====================================================
// 💵 LIVE BALANCE CHECK (REAL DB VALUE)
// =====================================================
$('#amount').on('input', function () {
    const amount = parseFloat($(this).val()) || 0;
    const available = parseFloat($('#availableBalance').val()) || 0;

    $('#insufficientFundsWarning').remove();

    // Case 1: Balance too low to withdraw
    if (available < 1000) {
        $(this).closest('.form-group').append(`
            <div id="insufficientFundsWarning" class="alert alert-danger mt-2 p-2 mb-0">
                <i class="anticon anticon-warning"></i>
                Mindestguthaben für Auszahlungen: €1.000. Ihr aktuelles Guthaben: €${available.toFixed(2)}
            </div>
        `);
        $('#sendVerifyOtpBtn, #withdrawalSubmitBtn').prop('disabled', true);
        return;
    }

    // Case 2: Amount greater than available
    if (amount > available) {
        $(this).closest('.form-group').append(`
            <div id="insufficientFundsWarning" class="alert alert-danger mt-2 p-2 mb-0">
                <i class="anticon anticon-warning"></i>
                Unzureichendes Guthaben: Verfügbar €${available.toFixed(2)}
            </div>
        `);
        $('#sendVerifyOtpBtn, #withdrawalSubmitBtn').prop('disabled', true);
        return;
    }

    // Case 3: Amount below minimum
    if (amount > 0 && amount < 1000) {
        $(this).closest('.form-group').append(`
            <div id="insufficientFundsWarning" class="alert alert-warning mt-2 p-2 mb-0">
                <i class="anticon anticon-info-circle"></i>
                Mindestbetrag für Auszahlungen: €1.000.
            </div>
        `);
        $('#sendVerifyOtpBtn, #withdrawalSubmitBtn').prop('disabled', true);
        return;
    }

    // ✅ All good
    $('#insufficientFundsWarning').remove();
    $('#sendVerifyOtpBtn').prop('disabled', false);
});


// =====================================================
// 🔐 COMBINED OTP SEND & VERIFY
// =====================================================
let otpSent = false;

$('#sendVerifyOtpBtn').click(function () {
    const $btn = $(this);
    const $otpInput = $('#otpCode');
    
    // Step 1: Send OTP if not sent yet
    if (!otpSent) {
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> OTP wird gesendet...');
        $.ajax({
            url: 'ajax/otp-handler.php',
            method: 'POST',
            data: {
                action: 'send',
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function (r) {
                if (r.success) {
                    toastr.success(r.message || 'OTP wurde an Ihre E-Mail gesendet');
                    $otpInput.prop('disabled', false).focus();
                    otpSent = true;
                    $btn.prop('disabled', false).html('<i class="anticon anticon-check-circle"></i> OTP prüfen');
                    $('#otpInfoText').html('<i class="anticon anticon-clock-circle"></i> OTP gesendet! Geben Sie den Code ein und klicken Sie auf "OTP prüfen".');
                } else {
                    toastr.error(r.message || 'OTP konnte nicht gesendet werden');
                    $btn.prop('disabled', false).html('<i class="anticon anticon-mail"></i> OTP senden & prüfen');
                }
            },
            error: function (xhr, status, error) {
                console.error('OTP send error:', xhr.status, xhr.responseText);
                toastr.error('OTP konnte nicht gesendet werden. Bitte versuchen Sie es erneut.');
                $btn.prop('disabled', false).html('<i class="anticon anticon-mail"></i> OTP senden & prüfen');
            }
        });
    } 
    // Step 2: Verify OTP
    else {
        const code = $otpInput.val().trim();
        if (!code || code.length !== 6) {
            toastr.error('Bitte geben Sie den 6-stelligen OTP-Code ein.');
            return;
        }
        
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Wird geprüft...');
        $.ajax({
            url: 'ajax/otp-handler.php',
            method: 'POST',
            data: {
                action: 'verify',
                otp_code: code,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function (r) {
                if (r.success) {
                    toastr.success(r.message || 'OTP erfolgreich verifiziert');
                    $('#withdrawalSubmitBtn').prop('disabled', false);
                    $otpInput.prop('disabled', true);
                    $btn.prop('disabled', true).html('<i class="anticon anticon-check"></i> Verifiziert').removeClass('btn-primary').addClass('btn-success');
                    $('#otpInfoText').html('<i class="anticon anticon-check-circle text-success"></i> E-Mail verifiziert! Sie können jetzt Ihren Auszahlungsantrag einreichen.');
                } else {
                    toastr.error(r.message || 'Ungültiger OTP-Code');
                    $btn.prop('disabled', false).html('<i class="anticon anticon-check-circle"></i> OTP prüfen');
                }
            },
            error: function (xhr, status, error) {
                console.error('OTP verify error:', xhr.status, xhr.responseText);
                toastr.error('OTP-Verifizierung fehlgeschlagen. Bitte versuchen Sie es erneut.');
                $btn.prop('disabled', false).html('<i class="anticon anticon-check-circle"></i> OTP prüfen');
            }
        });
    }
});


// =====================================================
// 🧹 RESET OTP FIELDS ON MODAL CLOSE
// =====================================================
$('#newWithdrawalModal').on('hidden.bs.modal', function () {
    resetOtpFields();
});

function resetOtpFields() {
    $('#otpCode').val('').prop('disabled', true);
    $('#sendVerifyOtpBtn').prop('disabled', false).html('<i class="anticon anticon-mail"></i> OTP senden & prüfen').removeClass('btn-success').addClass('btn-primary');
    $('#withdrawalSubmitBtn').prop('disabled', true);
    $('#otpInfoText').html('<i class="anticon anticon-info-circle"></i> OTP ist 5 Minuten gültig. Klicken Sie auf die Schaltfläche, um den Code an Ihre E-Mail zu senden.');
    otpSent = false;
}

    // Refresh algorithm
    $('#refresh-algorithm').click(function() {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Aktualisiere...');
        
        setTimeout(function() {
            $.ajax({
                url: 'ajax/get_recovery_status.php',
                method: 'GET',
                success: function(response) {
                    try {
                        var data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (data.success) {
                            if (data.recoveryPercentage !== undefined) {
                                $('.algorithm-progress .progress-bar').css('width', data.recoveryPercentage + '%');
                                $('.count[data-value="<?= htmlspecialchars($recoveryPercentage, ENT_QUOTES) ?>"]').text(data.recoveryPercentage + '%');
                            }
                            toastr.success('Status erfolgreich aktualisiert');
                        } else {
                            toastr.error(data.message || 'Fehler beim Aktualisieren des Status');
                        }
                    } catch (e) {
                        toastr.error('Fehler beim Verarbeiten der Serverantwort');
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('Kommunikationsfehler mit dem Server: ' + error);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="anticon anticon-sync"></i> Status aktualisieren');
                }
            });
        }, 400);
    });

    // Background refresh for AI status and balance (optional)
    function bgRefresh() {
        $.ajax({
            url: 'ajax/bg_status.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    if (data.aiStatus) {
                        $('#aiStatusText').text(data.aiStatus);
                    }
                    if (data.lastScan) {
                        $('#lastScanText').text(data.lastScan);
                    }
                    if (data.balance !== undefined) {
                        var b = parseFloat(data.balance) || 0;
                        animateCount($('#balanceCounter')[0], parseFloat($('#balanceCounter').text().replace(/[^\d.-]/g,'')) || 0, b, 2, 600);
                    }
                }
            }
        }).always(function(){
            setTimeout(bgRefresh, 30000);
        });
    }
    // Start bgRefresh only if endpoint exists in your system; safe to comment out if not present.
    bgRefresh();

    // Password modal interactions (if present)
    <?php if ($passwordChangeRequired): ?>
    $('#newPassword').on('input', function() {
        const val = $(this).val();
        const $bar = $('#passwordStrengthBar');
        const $text = $('#passwordStrengthText');

        let score = 0;
        const req = {
            length: val.length >= 8,
            upper: /[A-Z]/.test(val),
            number: /[0-9]/.test(val),
            special: /[^A-Za-z0-9]/.test(val)
        };

        for (let key in req) {
            const $item = $('#req-' + key);
            if (req[key]) {
                $item.removeClass('text-danger').addClass('text-success')
                     .html('<i class="anticon anticon-check"></i> ' + $item.text().replace(/^[✓✗]\s*/, ''));
                score++;
            } else {
                $item.removeClass('text-success').addClass('text-danger')
                     .html('<i class="anticon anticon-close"></i> ' + $item.text().replace(/^[✓✗]\s*/, ''));
            }
        }

        const width = (score / 4) * 100;
        let colorClass, label;
        switch (score) {
            case 0:
            case 1: colorClass = 'bg-danger'; label = 'Schwach'; break;
            case 2: colorClass = 'bg-warning'; label = 'Mittel'; break;
            case 3: colorClass = 'bg-info'; label = 'Gut'; break;
            case 4: colorClass = 'bg-success'; label = 'Stark'; break;
        }

        $bar.removeClass('bg-danger bg-warning bg-info bg-success')
            .addClass(colorClass)
            .css('width', width + '%');
        $text.text('Stärke: ' + label);

        $('#confirmPassword').trigger('input');
    });

    $('#confirmPassword, #newPassword').on('input', function() {
        const newPass = $('#newPassword').val();
        const confirm = $('#confirmPassword').val();
        const $match = $('#passwordMatchText');

        if (!confirm) {
            $match.text('Warte auf Eingabe...').removeClass('text-success text-danger').addClass('text-muted');
            return;
        }

        if (confirm === newPass) {
            $match.text('Passwörter stimmen überein ✅').removeClass('text-danger text-muted').addClass('text-success');
        } else {
            $match.text('Passwörter stimmen nicht überein ❌').removeClass('text-success text-muted').addClass('text-danger');
        }
    });

    $('#submitPasswordChange').click(function() {
        const currentPassword = $('#currentPassword').val();
        const newPassword = $('#newPassword').val();
        const confirmPassword = $('#confirmPassword').val();

        if (!currentPassword || !newPassword || !confirmPassword) {
            toastr.error('Alle Felder sind erforderlich');
            return;
        }
        if (newPassword !== confirmPassword) {
            toastr.error('Die neuen Passwörter stimmen nicht überein');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Wird verarbeitet...');

        $.ajax({
            url: 'change_password.php',
            method: 'POST',
            dataType: 'json',
            data: {
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword,
                force_change: 1,
                csrf_token: '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>'
            },
            success: function(data) {
                if (data.success) {
                    toastr.success(data.message || 'Passwort erfolgreich geändert');
                    $('#passwordChangeModal').modal('hide');
                    $('.modal-backdrop').remove();
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    toastr.error(data.message || 'Fehler beim Ändern des Passworts');
                }
                $btn.prop('disabled', false).html('<i class="anticon anticon-save"></i> Passwort ändern');
            },
            error: function(xhr, status, error) {
                toastr.error('Serverfehler: ' + error);
                $btn.prop('disabled', false).html('<i class="anticon anticon-save"></i> Passwort ändern');
            }
        });
    });
    <?php endif; ?>

    // Print receipt
    $('#printReceiptBtn').click(function(){ window.print(); });

    // =====================================================
    // 📋 VIEW CASE DETAILS MODAL
    // =====================================================
    $('.view-case-btn').click(function() {
        const caseId = $(this).data('case-id');
        $('#caseDetailsModal').modal('show');
        
        // Reset modal body
        $('#caseModalBody').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Laden...</span>
                </div>
                <p class="mt-3 text-muted">Falldetails werden geladen...</p>
            </div>
        `);
        
        // Fetch case details via AJAX
        $.ajax({
            url: 'ajax/get-case.php',
            method: 'GET',
            data: { id: caseId },
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success && data.case) {
                        const c = data.case;
                        const progress = c.reported_amount > 0 ? Math.round((c.recovered_amount / c.reported_amount) * 100) : 0;
                        
                        const statusClass = {
                            'open': 'warning',
                            'documents_required': 'secondary',
                            'under_review': 'info',
                            'refund_approved': 'success',
                            'refund_rejected': 'danger',
                            'closed': 'dark'
                        }[c.status] || 'light';
                        
                        const html = `
                            <div class="case-details-content">
                                <!-- Header Info -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card border-0" style="background: rgba(41, 80, 168, 0.05);">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2" style="font-size: 12px; text-transform: uppercase;">Fall-Nummer</h6>
                                                <h4 class="mb-0 font-weight-bold" style="color: var(--brand);">${c.case_number || 'N/A'}</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0" style="background: rgba(41, 80, 168, 0.05);">
                                            <div class="card-body">
                                               <h6 class="text-muted mb-2" style="font-size: 12px; text-transform: uppercase;">Status</h6>
                                                <span class="badge badge-${statusClass} px-3 py-2" style="font-size: 14px;">
                                                    <i class="anticon anticon-flag mr-1"></i>${c.status ? c.status.replace(/_/g, ' ').toUpperCase() : 'N/A'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Financial Overview -->
                                <div class="card border-0 mb-4" style="background: linear-gradient(135deg, rgba(41, 80, 168, 0.05), rgba(45, 169, 227, 0.05));">
                                    <div class="card-body">
                                        <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-dollar mr-2" style="color: var(--brand);"></i>Finanzübersicht
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="text-muted mb-1" style="font-size: 13px;">Gemeldeter Betrag</div>
                                                <h4 class="mb-0 font-weight-bold text-danger">€${parseFloat(c.reported_amount || 0).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h4>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="text-muted mb-1" style="font-size: 13px;">Zurückgewonnener Betrag</div>
                                                <h3 class="mb-2 font-weight-bold" style="color: #2c3e50;">€${parseFloat(c.recovered_amount || 0).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h3>
                                                <div class="progress mb-2" style="height: 8px; border-radius: 10px; background: #e9ecef;">
                                                    <div class="progress-bar" style="width: ${progress}%; background: linear-gradient(90deg, #2950a8 0%, #2da9e3 100%);"></div>
                                                </div>
                                                <small class="text-muted">${progress}% von €${parseFloat(c.reported_amount || 0).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Platform Info -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card border-0 h-100">
                                            <div class="card-body">
                                                <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                                    <i class="anticon anticon-global mr-2" style="color: var(--brand);"></i>Plattform-Information
                                                </h6>
                                                <p class="mb-2"><strong>Plattform:</strong> ${c.platform_name || 'N/A'}</p>
                                                <p class="mb-0"><strong>Erstellt:</strong> ${c.created_at ? new Date(c.created_at).toLocaleDateString('de-DE', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : 'N/A'}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 h-100">
                                            <div class="card-body">
                                                <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                                    <i class="anticon anticon-clock-circle mr-2" style="color: var(--brand);"></i>Zeitleiste
                                                </h6>
                                                <p class="mb-2"><strong>Zuletzt aktualisiert:</strong> ${c.updated_at ? new Date(c.updated_at).toLocaleDateString('de-DE', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : 'N/A'}</p>
                                                <p class="mb-0"><strong>Tage aktiv:</strong> ${c.created_at ? Math.floor((new Date() - new Date(c.created_at)) / (1000 * 60 * 60 * 24)) : 0} Tage</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                ${c.description ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-file-text mr-2" style="color: var(--brand);"></i>Fallbeschreibung
                                        </h6>
                                        <p class="mb-0" style="line-height: 1.6;">${c.description}</p>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Recovery Transactions -->
                                ${data.recoveries && data.recoveries.length > 0 ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-transaction mr-2" style="color: var(--brand);"></i>Wiederherstellungstransaktionen
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead style="background: rgba(41, 80, 168, 0.05);">
                                                    <tr>
                                                        <th>Datum</th>
                                                        <th>Betrag</th>
                                                        <th>Methode</th>
                                                        <th>Referenz</th>
                                                        <th>Bearbeitet von</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.recoveries.map(r => `
                                                        <tr>
                                                            <td>${r.transaction_date ? new Date(r.transaction_date).toLocaleDateString('de-DE', {year: 'numeric', month: 'short', day: 'numeric'}) : 'N/A'}</td>
                                                            <td><strong class="text-success">€${parseFloat(r.amount || 0).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                                                            <td>${r.method || 'N/A'}</td>
                                                            <td><small class="text-muted">${r.transaction_reference || 'N/A'}</small></td>
                                                            <td>${r.admin_first_name && r.admin_last_name ? `${r.admin_first_name} ${r.admin_last_name}` : 'System'}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Documents -->
                                ${data.documents && data.documents.length > 0 ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-paper-clip mr-2" style="color: var(--brand);"></i>Falldokumente
                                        </h6>
                                        <div class="list-group">
                                            ${data.documents.map(d => `
                                                <div class="list-group-item border-0 px-0">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="anticon anticon-file mr-2" style="color: var(--brand);"></i>
                                                            <strong>${d.document_type || 'Dokument'}</strong>
                                                            ${d.verified ? '<span class="badge badge-success badge-sm ml-2"><i class="anticon anticon-check"></i> Verifiziert</span>' : ''}
                                                        </div>
                                                        <small class="text-muted">${d.uploaded_at ? new Date(d.uploaded_at).toLocaleDateString('de-DE', {year: 'numeric', month: 'short', day: 'numeric'}) : ''}</small>
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Status History -->
                                ${data.history && data.history.length > 0 ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-history mr-2" style="color: var(--brand);"></i>Statusverlauf
                                        </h6>
                                        <div class="timeline">
                                            ${data.history.map((h, idx) => `
                                                <div class="timeline-item ${idx === 0 ? 'timeline-item-active' : ''}">
                                                    <div class="timeline-marker ${idx === 0 ? 'bg-primary' : 'bg-secondary'}"></div>
                                                    <div class="timeline-content">
                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                            <strong>${h.new_status ? h.new_status.replace(/_/g, ' ').toUpperCase() : 'Statusänderung'}</strong>
                                                            <small class="text-muted">${h.created_at ? new Date(h.created_at).toLocaleDateString('de-DE', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : ''}</small>
                                                        </div>
                                                        ${h.comments ? `<p class="mb-1 text-muted small">${h.comments}</p>` : ''}
                                                        ${h.first_name && h.last_name ? `<small class="text-muted">Von: ${h.first_name} ${h.last_name}</small>` : ''}
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Actions -->
                                <div class="text-center mt-4">
                                    <a href="cases.php" class="btn btn-primary">
                                        <i class="anticon anticon-folder-open mr-1"></i>Alle Fälle ansehen
                                    </a>
                                </div>
                            </div>
                        `;
                        
                        $('#caseModalBody').html(html);
                        $('#caseDetailsModalLabel').html(`<i class="anticon anticon-file-text mr-2"></i>Fall #${c.case_number || 'Details'}`);
                    } else {
                        $('#caseModalBody').html(`
                            <div class="alert alert-danger">
                                <i class="anticon anticon-close-circle mr-2"></i>${data.message || 'Falldetails konnten nicht geladen werden'}
                            </div>
                        `);
                    }
                } catch (e) {
                    $('#caseModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="anticon anticon-close-circle mr-2"></i>Fehler beim Verarbeiten der Falldaten
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                $('#caseModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="anticon anticon-close-circle mr-2"></i>Fehler beim Laden der Falldetails: ${error}
                    </div>
                `);
            }
        });
    });

    // Charts removed per user request

    // Animated Counter Function
    function animateCounter(element) {
        const target = parseFloat(element.getAttribute('data-value')) || 0;
        const duration = 1500; // 1.5 seconds
        const start = 0;
        const startTime = performance.now();
        
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function (easeOutQuart)
            const easeOut = 1 - Math.pow(1 - progress, 4);
            const current = start + (target - start) * easeOut;
            
            // Format based on whether it's a decimal or integer
            if (element.classList.contains('money')) {
                element.textContent = '€' + current.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            } else if (element.classList.contains('percent')) {
                element.textContent = current.toFixed(1) + '%';
            } else {
                element.textContent = Math.floor(current).toLocaleString();
            }
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        requestAnimationFrame(update);
    }

    // Initialize counters with Intersection Observer for better performance
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                    entry.target.classList.add('counted');
                    animateCounter(entry.target);
                }
            });
        }, { threshold: 0.5 });

        // Observe all counter elements
        document.querySelectorAll('.count').forEach(el => observer.observe(el));
    } else {
        // Fallback for older browsers
        document.querySelectorAll('.count').forEach(el => animateCounter(el));
    }

    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add loading animation to buttons on click
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!this.classList.contains('no-loading')) {
                this.style.pointerEvents = 'none';
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="anticon anticon-loading anticon-spin mr-1"></i>' + this.textContent;
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.style.pointerEvents = '';
                }, 2000);
            }
        });
    });

    // =====================================================
    // 📧 EMAIL VERIFICATION - AJAX HANDLER
    // =====================================================
    let emailVerificationCooldown = false;
    
    $('#sendVerificationEmailBtn').on('click', function(e) {
        e.preventDefault();
        
        if (emailVerificationCooldown) {
            return;
        }
        
        const $btn = $(this);
        const $statusDiv = $('#verificationEmailStatus');
        const originalBtnText = $btn.html();
        
        // Disable button and show loading
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i>Wird gesendet...');
        $statusDiv.empty();
        
        $.ajax({
            url: 'ajax/send_verification_email.php',
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $statusDiv.html(`
                        <div class="alert alert-success alert-sm border-0 mt-2" style="font-size: 13px;">
                            <i class="anticon anticon-check-circle mr-1"></i>${response.message}
                        </div>
                    `);
                    
                    // Set cooldown for 60 seconds
                    emailVerificationCooldown = true;
                    let countdown = 60;
                    $btn.html(`<i class="anticon anticon-clock-circle mr-1"></i>Erneut senden in ${countdown}s`);
                    
                    const countdownInterval = setInterval(() => {
                        countdown--;
                        if (countdown <= 0) {
                            clearInterval(countdownInterval);
                            emailVerificationCooldown = false;
                            $btn.prop('disabled', false).html(originalBtnText);
                        } else {
                            $btn.html(`<i class="anticon anticon-clock-circle mr-1"></i>Erneut senden in ${countdown}s`);
                        }
                    }, 1000);
                } else {
                    $statusDiv.html(`
                        <div class="alert alert-danger alert-sm border-0 mt-2" style="font-size: 13px;">
                            <i class="anticon anticon-close-circle mr-1"></i>${response.message}
                        </div>
                    `);
                    $btn.prop('disabled', false).html(originalBtnText);
                }
            },
            error: function(xhr, status, error) {
                $statusDiv.html(`
                    <div class="alert alert-danger alert-sm border-0 mt-2" style="font-size: 13px;">
                        <i class="anticon anticon-close-circle mr-1"></i>Fehler beim Senden der E-Mail. Bitte versuchen Sie es später erneut.
                    </div>
                `);
                $btn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
});

// =====================================================
// 💳 WITHDRAWAL ELIGIBILITY CHECK
// =====================================================
function checkWithdrawalEligibility(event) {
    event.preventDefault();
    
    // Check KYC status (escaped for security)
    const kycStatus = <?php echo json_encode($kyc_status); ?>;
    if (kycStatus !== 'verified' && kycStatus !== 'approved') {
        toastr.warning('Bitte verifizieren Sie Ihre KYC-Identifikation, bevor Sie Auszahlungen vornehmen.', 'KYC-Verifizierung erforderlich', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            onclick: function() {
                window.location.href = 'kyc.php';
            }
        });
        return;
    }
    
    // Check for verified payment method
    const hasVerifiedPayment = <?php echo json_encode($hasVerifiedPaymentMethod ?? false); ?>;
    if (!hasVerifiedPayment) {
        toastr.warning('Bitte fügen Sie eine Kryptowährungs-Wallet hinzu und verifizieren Sie diese, bevor Sie Auszahlungen vornehmen.', 'Zahlungsmethode verifizieren', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            onclick: function() {
                window.location.href = 'payment-methods.php';
            }
        });
        return;
    }
    
    // All checks passed - open withdrawal modal
    $('#newWithdrawalModal').modal('show');
}
</script>
</body>
</html>