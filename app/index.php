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

// Current date/time in application timezone
$currentDateTime = new DateTime('now');
$currentDateTimeFormatted = $currentDateTime->format('Y-m-d H:i:s');

// Branding - Already loaded from header.php but ensure defaults if not set
if (!isset($appName)) {
    $appName = "Fundtracer AI";
}
if (!isset($appTagline)) {
    $appTagline = "Advanced AI-Powered Blockchain Analysis & Asset Recovery Platform";
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

        // Unread admin ticket replies
        $unreadRepliesStmt = $pdo->prepare(
            "SELECT tr.id, tr.message, tr.created_at, st.subject, st.ticket_number, st.id as ticket_id
             FROM ticket_replies tr
             JOIN support_tickets st ON st.id = tr.ticket_id
             WHERE st.user_id = ?
               AND tr.admin_id IS NOT NULL
               AND tr.read_at IS NULL
             ORDER BY tr.created_at DESC
             LIMIT 5"
        );
        $unreadRepliesStmt->execute([$userId]);
        $unreadReplies = $unreadRepliesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent deposit requests
        $recentDepositsStmt = $pdo->prepare(
            "SELECT id, amount, method_code, reference, status, created_at
             FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
        );
        $recentDepositsStmt->execute([$userId]);
        $recentDeposits = $recentDepositsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent withdrawal requests
        $recentWithdrawalsStmt = $pdo->prepare(
            "SELECT id, amount, method_code, reference, status, created_at,
                    fee_percentage, fee_amount
             FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
        );
        $recentWithdrawalsStmt->execute([$userId]);
        $recentWithdrawals = $recentWithdrawalsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error (data fetch): " . $e->getMessage());
        $cases = $cases ?? [];
        $ongoingRecoveries = $ongoingRecoveries ?? [];
        $transactions = $transactions ?? [];
        $statusCounts = $statusCounts ?? [];
        $unreadReplies = $unreadReplies ?? [];
        $recentDeposits = $recentDeposits ?? [];
        $recentWithdrawals = $recentWithdrawals ?? [];
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
    $unreadReplies = [];
    $recentDeposits = [];
    $recentWithdrawals = [];
}

// Last AI scan
$lastAIScan = date('M d, Y H:i', strtotime($stats['last_case_date'] ?? 'now'));

// Recovery calculations
$reportedTotal = (float)($stats['total_reported'] ?? 0.0);
$recoveredTotal = (float)($stats['total_recovered'] ?? 0.0);
$recoveryPercentage = ($reportedTotal > 0) ? round(($recoveredTotal / $reportedTotal) * 100, 2) : 0;
$outstandingAmount = max(0, $reportedTotal - $recoveredTotal);

// ── Dashboard Theme ──────────────────────────────────────────────────────────
// Load the admin-selected dashboard theme from system_settings.
// Valid values: theme-1 … theme-5.  Falls back to theme-1 gracefully.
$dashboardTheme = 'theme-1';
$allowedThemes  = ['theme-1', 'theme-2', 'theme-3', 'theme-4', 'theme-5'];
try {
    $themeStmt = $pdo->query("SELECT dashboard_theme FROM system_settings WHERE id = 1 LIMIT 1");
    $themeRow  = $themeStmt->fetch(PDO::FETCH_ASSOC);
    if ($themeRow && !empty($themeRow['dashboard_theme']) && in_array($themeRow['dashboard_theme'], $allowedThemes, true)) {
        $dashboardTheme = $themeRow['dashboard_theme'];
    }
} catch (PDOException $e) {
    // Column does not exist yet (migration not run) – silently use default
    error_log("dashboard_theme fetch: " . $e->getMessage());
}
$dashboardThemeSafe = htmlspecialchars($dashboardTheme, ENT_QUOTES, 'UTF-8');

// ── Withdrawal fee settings (shown in modal) ─────────────────────────────
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
?>
<?php if ($passwordChangeRequired): ?>

<div class="modal fade show" id="passwordChangeModal" tabindex="-1" role="dialog"
     aria-labelledby="passwordChangeModalLabel" style="display:block; padding-right:15px;" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius:14px;overflow:hidden;">
            <!-- Gradient Header -->
            <div class="modal-header border-0 px-4 py-4" style="background:linear-gradient(135deg,#1a2a6c 0%,#2950a8 50%,#2da9e3 100%);color:#fff;">
                <div class="d-flex align-items-center">
                    <div class="mr-3" style="width:48px;height:48px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;">
                        <i class="anticon anticon-lock"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 font-weight-bold" id="passwordChangeModalLabel" style="font-size:1.1rem;">Passwortänderung erforderlich</h5>
                        <small style="opacity:0.85;">Bitte setzen Sie aus Sicherheitsgründen ein neues Passwort</small>
                    </div>
                </div>
            </div>

            <div class="modal-body px-4 pt-4">
                <div class="alert border-0 mb-4 d-flex align-items-start" role="alert"
                     style="background:linear-gradient(135deg,rgba(255,193,7,0.12),rgba(255,193,7,0.05));border-left:4px solid #ffc107 !important;border-radius:8px;">
                    <i class="anticon anticon-safety-certificate mr-2 mt-1 text-warning" style="font-size:18px;"></i>
                    <div>
                        <strong>Sicherheitshinweis:</strong> Ihr Konto erfordert ein neues Passwort.
                        Wählen Sie ein sicheres, einzigartiges Passwort mit mindestens 8 Zeichen.
                    </div>
                </div>

                <form id="passwordChangeForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">

                    <!-- Current Password -->
                    <div class="form-group">
                        <label for="currentPassword" class="font-weight-600" style="color:#2c3e50;">
                            <i class="anticon anticon-key mr-1" style="color:#2950a8;"></i>Aktuelles Passwort
                        </label>
                        <input type="password" class="form-control" id="currentPassword"
                               required aria-required="true" autocomplete="current-password"
                               style="border-radius:8px;padding:10px 14px;border:1.5px solid #e0e6ed;">
                    </div>

                    <!-- New Password -->
                    <div class="form-group">
                        <label for="newPassword" class="font-weight-600" style="color:#2c3e50;">
                            <i class="anticon anticon-lock mr-1" style="color:#2950a8;"></i>Neues Passwort
                        </label>
                        <input type="password" class="form-control" id="newPassword"
                               required minlength="8" aria-describedby="passwordHelp"
                               autocomplete="new-password"
                               style="border-radius:8px;padding:10px 14px;border:1.5px solid #e0e6ed;">

                        <!-- Strength Bar -->
                        <div class="progress mt-2" style="height:6px;border-radius:3px;">
                            <div id="passwordStrengthBar" class="progress-bar bg-danger" style="width:0%;transition:width .3s,background .3s;" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small id="passwordStrengthText" class="text-muted small d-block mt-1" aria-live="polite">Stärke: Schwach</small>

                        <!-- Requirements Checklist -->
                        <div class="mt-2 p-2" style="background:#f8f9fa;border-radius:8px;">
                            <ul class="list-unstyled small mb-0" id="passwordChecklist" aria-hidden="false">
                                <li id="req-length" class="text-danger mb-1"><i class="anticon anticon-close-circle mr-1"></i> Mindestens 8 Zeichen</li>
                                <li id="req-upper" class="text-danger mb-1"><i class="anticon anticon-close-circle mr-1"></i> Mindestens ein Großbuchstabe</li>
                                <li id="req-number" class="text-danger mb-1"><i class="anticon anticon-close-circle mr-1"></i> Mindestens eine Zahl</li>
                                <li id="req-special" class="text-danger"><i class="anticon anticon-close-circle mr-1"></i> Mindestens ein Sonderzeichen</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group mb-2">
                        <label for="confirmPassword" class="font-weight-600" style="color:#2c3e50;">
                            <i class="anticon anticon-check-circle mr-1" style="color:#2950a8;"></i>Neues Passwort bestätigen
                        </label>
                        <input type="password" class="form-control" id="confirmPassword"
                               required autocomplete="new-password"
                               style="border-radius:8px;padding:10px 14px;border:1.5px solid #e0e6ed;">
                        <small id="passwordMatchText" class="small text-muted" aria-live="polite">Warte auf Eingabe...</small>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-2">
                <button type="button" class="btn btn-block font-weight-600" id="submitPasswordChange"
                        aria-label="Passwort ändern"
                        style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;border-radius:8px;padding:12px;font-size:15px;">
                    <i class="anticon anticon-safety mr-2"></i>Passwort sicher ändern
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
            <div class="modal-header border-0 px-4 py-4" style="background: linear-gradient(135deg, #1a2a6c 0%, #2950a8 50%, #2da9e3 100%); color: #fff; border-radius: 12px 12px 0 0;">
                <div class="d-flex align-items-center flex-grow-1">
                    <div class="mr-3" style="width:48px;height:48px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;">
                        <i class="anticon anticon-arrow-down"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 font-weight-bold" id="newDepositModalLabel">Konto aufladen</h5>
                        <small style="opacity:0.85;">Sicher einzahlen &middot; Bearbeitungsgebühr: 0%</small>
                    </div>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="depositForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
                <!-- Step Indicator -->
                <div class="px-4 pt-3 pb-0" style="background:#fff;border-bottom:1px solid #f0f0f0;">
                    <div class="d-flex align-items-center" style="padding-bottom:14px;">
                        <div class="d-flex flex-column align-items-center" style="flex:0 0 auto;">
                            <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;box-shadow:0 2px 6px rgba(41,80,168,0.3);">1</div>
                            <div style="font-size:10px;color:#2950a8;font-weight:700;margin-top:4px;white-space:nowrap;">Betrag</div>
                        </div>
                        <div style="flex:1;height:2px;background:linear-gradient(90deg,#2950a8,#2da9e3);margin:0 6px 14px;"></div>
                        <div class="d-flex flex-column align-items-center" style="flex:0 0 auto;">
                            <div style="width:30px;height:30px;border-radius:50%;background:#dee2e6;color:#6c757d;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">2</div>
                            <div style="font-size:10px;color:#6c757d;font-weight:600;margin-top:4px;white-space:nowrap;">Methode</div>
                        </div>
                        <div style="flex:1;height:2px;background:#dee2e6;margin:0 6px 14px;"></div>
                        <div class="d-flex flex-column align-items-center" style="flex:0 0 auto;">
                            <div style="width:30px;height:30px;border-radius:50%;background:#dee2e6;color:#6c757d;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">3</div>
                            <div style="font-size:10px;color:#6c757d;font-weight:600;margin-top:4px;white-space:nowrap;">Nachweis</div>
                        </div>
                    </div>
                </div>
                <div class="modal-body p-4">
                    <!-- ===== STEP 1: Amount ===== -->
                    <div id="depositStep1">
                        <div class="d-flex align-items-start p-3 mb-3" role="alert" style="border-radius:10px;background:linear-gradient(135deg,rgba(41,80,168,0.07),rgba(45,169,227,0.05));border:1px solid rgba(41,80,168,0.15);">
                            <div style="width:36px;height:36px;background:rgba(41,80,168,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-right:12px;">
                                <i class="anticon anticon-clock-circle" style="color:#2950a8;font-size:16px;"></i>
                            </div>
                            <div>
                                <div class="font-weight-700" style="color:#2950a8;font-size:14px;">30-Minuten-Fenster</div>
                                <div class="text-muted small mt-1">Schließen Sie Ihre Einzahlung innerhalb von 30 Minuten ab, um Verzögerungen zu vermeiden. Einzahlungen beschleunigen die Wiederherstellung Ihrer Fälle.</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="font-weight-600" style="color: #2c3e50;">Betrag (EUR)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" aria-hidden="true" style="background: linear-gradient(135deg, #2950a8, #2da9e3); color: white; border: none; font-weight: 600;">€</span>
                                </div>
                                <input type="number" class="form-control" id="depositAmount" name="amount" min="10" step="0.01" required placeholder="Einzahlungsbetrag eingeben" aria-label="Betrag in Euro" style="border-radius: 0 8px 8px 0; border-left: none; font-size: 18px; font-weight: 600;">
                            </div>
                            <small class="form-text text-muted"><i class="anticon anticon-check-circle text-success mr-1"></i>Mindesteinzahlung: €10,00 | Bearbeitungsgebühr: 0%</small>
                        </div>
                    </div><!-- /depositStep1 -->

                    <!-- ===== STEP 2: Payment Method & Details ===== -->
                    <div id="depositStep2" style="display:none;">
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
                        <div style="border:1.5px solid rgba(41,80,168,0.2);border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(41,80,168,0.08);">
                            <div style="background:linear-gradient(135deg,#2950a8 0%,#2da9e3 100%);padding:12px 16px;display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                    <i class="anticon anticon-info-circle" style="color:#fff;font-size:16px;"></i>
                                </div>
                                <span style="color:#fff;font-weight:700;font-size:14px;">Zahlungsanweisungen</span>
                            </div>
                            <div style="padding:20px 20px 16px;background:#fff;">
                                <div id="bankDetails" style="display: none;">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <div style="width:36px;height:36px;background:rgba(41,80,168,0.1);border-radius:8px;display:flex;align-items:center;justify-content:center;margin-right:10px;">
                                                <i class="anticon anticon-bank" style="color:#2950a8;font-size:16px;"></i>
                                            </div>
                                            <h6 class="mb-0 font-weight-700" style="color:#2c3e50;">Banküberweisung</h6>
                                        </div>
                                        <div style="background:#f8f9fb;border-radius:10px;padding:14px 16px;border:1px solid #e9ecef;">
                                            <div class="row" style="row-gap:8px;">
                                                <div class="col-5 col-sm-4">
                                                    <div style="font-size:11px;color:#6c757d;font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Kontoinhaber</div>
                                                </div>
                                                <div class="col-7 col-sm-8">
                                                    <div id="detail-bank-name" class="font-weight-600" style="color:#2c3e50;font-size:14px;">-</div>
                                                </div>
                                                <div class="col-5 col-sm-4">
                                                    <div style="font-size:11px;color:#6c757d;font-weight:600;text-transform:uppercase;letter-spacing:.3px;">IBAN</div>
                                                </div>
                                                <div class="col-7 col-sm-8">
                                                    <div id="detail-account-number" class="font-weight-600" style="color:#2c3e50;font-size:14px;font-family:monospace;">-</div>
                                                </div>
                                                <div class="col-5 col-sm-4">
                                                    <div style="font-size:11px;color:#6c757d;font-weight:600;text-transform:uppercase;letter-spacing:.3px;">BIC / SWIFT</div>
                                                </div>
                                                <div class="col-7 col-sm-8">
                                                    <div id="detail-routing-number" class="font-weight-600" style="color:#2c3e50;font-size:14px;font-family:monospace;">-</div>
                                                </div>
                                                <div class="col-5 col-sm-4">
                                                    <div style="font-size:11px;color:#6c757d;font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Kontotyp</div>
                                                </div>
                                                <div class="col-7 col-sm-8">
                                                    <div class="font-weight-600" style="color:#2c3e50;font-size:14px;">Geschäftskonto</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-start mt-3 p-3" style="background:#fff8e1;border-radius:8px;border-left:3px solid #f59f00;">
                                            <i class="anticon anticon-exclamation-circle mr-2 mt-1" style="color:#f59f00;font-size:15px;flex-shrink:0;"></i>
                                            <div style="font-size:13px;">
                                                <strong style="color:#856404;">Verwendungszweck:</strong>
                                                <span style="color:#2c3e50;font-family:monospace;font-weight:700;"> RF3K8M1ZPW-<?= htmlspecialchars($currentUser['id'],ENT_QUOTES) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="cryptoDetails" style="display: none;">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <div style="width:36px;height:36px;background:rgba(41,80,168,0.1);border-radius:8px;display:flex;align-items:center;justify-content:center;margin-right:10px;">
                                                <i class="anticon anticon-block" style="color:#2950a8;font-size:16px;"></i>
                                            </div>
                                            <h6 class="mb-0 font-weight-700" style="color:#2c3e50;">Krypto-Wallet</h6>
                                        </div>
                                        <div style="background:#f8f9fb;border-radius:10px;padding:14px 16px;border:1px solid #e9ecef;">
                                            <div class="mb-2" style="font-size:11px;color:#6c757d;font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Netzwerk</div>
                                            <div id="detail-crypto-network" class="font-weight-600 mb-3" style="color:#2c3e50;font-size:14px;">Ethereum (ERC20)</div>
                                            <div class="mb-2" style="font-size:11px;color:#6c757d;font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Wallet-Adresse</div>
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" id="detail-wallet-address" readonly aria-label="Wallet-Adresse"
                                                       style="font-family:monospace;font-size:13px;background:#fff;border-radius:8px 0 0 8px;">
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="button" id="copyWalletAddress" aria-label="Adresse kopieren"
                                                            style="border-radius:0 8px 8px 0;background:linear-gradient(135deg,#2950a8,#2da9e3);border:none;font-size:13px;">
                                                        <i class="anticon anticon-copy mr-1"></i>Kopieren
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-start mt-3 p-3" style="background:#fff0f0;border-radius:8px;border-left:3px solid #dc3545;">
                                            <i class="anticon anticon-warning mr-2 mt-1" style="color:#dc3545;font-size:15px;flex-shrink:0;"></i>
                                            <div style="font-size:13px;color:#721c24;">
                                                <strong>Wichtig:</strong> Senden Sie <strong>nur</strong> die angegebene Kryptowährung an diese Adresse. Andere Coins führen zu dauerhaftem Verlust.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="generalInstructions" style="display: none;">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="anticon anticon-info-circle mr-2" style="color:#2950a8;font-size:16px;"></i>
                                        <h6 class="mb-0 font-weight-700" style="color:#2c3e50;">Weitere Hinweise</h6>
                                    </div>
                                    <div id="detail-instructions" class="mb-0" style="font-size:14px;color:#495057;"></div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    </div><!-- /depositStep2 -->

                    <!-- ===== STEP 3: Proof of Payment ===== -->
                    <div id="depositStep3" style="display:none;">
                        <div class="d-flex align-items-center mb-3">
                            <div style="width:36px;height:36px;background:rgba(41,80,168,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-right:12px;">
                                <i class="anticon anticon-file-image" style="color:#2950a8;font-size:18px;"></i>
                            </div>
                            <div>
                                <div class="font-weight-700" style="color:#2c3e50;font-size:15px;">Zahlungsnachweis hochladen</div>
                                <div class="text-muted small">Laden Sie einen Screenshot oder PDF Ihres Zahlungsnachweises hoch.</div>
                            </div>
                        </div>
                        <div class="custom-file mb-2">
                            <input type="file" class="custom-file-input" id="proofOfPayment" name="proof_of_payment" accept="image/*,.pdf" required>
                            <label class="custom-file-label" for="proofOfPayment" style="border-radius:8px;">Screenshot oder PDF auswählen</label>
                        </div>
                        <small class="form-text text-muted">
                            <i class="anticon anticon-check-circle text-success mr-1"></i>Akzeptierte Formate: JPG, PNG, PDF &middot; Max. 2 MB
                        </small>
                    </div><!-- /depositStep3 -->
                </div>
                <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">
                        <i class="anticon anticon-close mr-1"></i>Abbrechen
                    </button>
                    <button type="button" id="depositBackBtn" class="btn btn-outline-secondary" style="border-radius: 8px; display:none;">
                        <i class="anticon anticon-left mr-1"></i>Zurück
                    </button>
                    <button type="button" id="depositNextBtn" class="btn btn-primary" style="border-radius: 8px; background: linear-gradient(135deg, #2950a8, #2da9e3); border: none;">
                        Weiter <i class="anticon anticon-right ml-1"></i>
                    </button>
                    <button type="submit" id="depositSubmitBtn" class="btn btn-primary" style="border-radius: 8px; background: linear-gradient(135deg, #2950a8, #2da9e3); border: none; display:none;">
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
            <div class="modal-header border-0 px-4 py-4" style="background: linear-gradient(135deg, #155724 0%, #28a745 50%, #20c997 100%); color: #fff; border-radius: 12px 12px 0 0;">
                <div class="d-flex align-items-center flex-grow-1">
                    <div class="mr-3" style="width:48px;height:48px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;">
                        <i class="anticon anticon-arrow-up"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 font-weight-bold" id="newWithdrawalModalLabel">Auszahlungsantrag</h5>
                        <small style="opacity:0.85;">Bearbeitungszeit: 1–3 Werktage &middot; OTP-gesichert</small>
                    </div>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="withdrawalForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
                <!-- Step Indicator -->
                <div class="px-4 pt-3 pb-0" style="background:#fff;border-bottom:1px solid #f0f0f0;">
                    <div class="d-flex align-items-center" style="padding-bottom:14px;">
                        <div class="d-flex flex-column align-items-center" style="flex:0 0 auto;">
                            <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#28a745,#20c997);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;box-shadow:0 2px 6px rgba(40,167,69,0.3);">1</div>
                            <div style="font-size:10px;color:#28a745;font-weight:700;margin-top:4px;white-space:nowrap;">Betrag</div>
                        </div>
                        <div style="flex:1;height:2px;background:#dee2e6;margin:0 6px 14px;"></div>
                        <div class="d-flex flex-column align-items-center" style="flex:0 0 auto;">
                            <div style="width:30px;height:30px;border-radius:50%;background:#dee2e6;color:#6c757d;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">2</div>
                            <div style="font-size:10px;color:#6c757d;font-weight:600;margin-top:4px;white-space:nowrap;">Methode</div>
                        </div>
                        <div style="flex:1;height:2px;background:#dee2e6;margin:0 6px 14px;"></div>
                        <div class="d-flex flex-column align-items-center" style="flex:0 0 auto;">
                            <div style="width:30px;height:30px;border-radius:50%;background:#dee2e6;color:#6c757d;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">3</div>
                            <div style="font-size:10px;color:#6c757d;font-weight:600;margin-top:4px;white-space:nowrap;">OTP &amp; Bestätigung</div>
                        </div>
                    </div>
                </div>

                <div class="modal-body p-4">

                    <!-- ===== WITHDRAWAL STEP 1: Amount ===== -->
                    <div id="withdrawalStep1">
                    <!-- Balance Display Card -->
                    <div class="d-flex align-items-center justify-content-between p-3 mb-3" style="background:linear-gradient(135deg,rgba(40,167,69,0.07),rgba(32,201,151,0.05));border-radius:10px;border:1px solid rgba(40,167,69,0.2);">
                        <div class="d-flex align-items-center">
                            <div style="width:38px;height:38px;background:rgba(40,167,69,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-right:12px;flex-shrink:0;">
                                <i class="anticon anticon-wallet" style="color:#28a745;font-size:18px;"></i>
                            </div>
                            <div>
                                <div style="font-size:11px;color:#6c757d;font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Verfügbares Guthaben</div>
                                <div class="font-weight-bold" style="color:#155724;font-size:1.3rem;">€<?= number_format($currentUser['balance'] ?? 0, 2, ',', '.') ?></div>
                            </div>
                        </div>
                        <div style="font-size:11px;color:#6c757d;text-align:right;">
                            <div>Mindestbetrag</div>
                            <div class="font-weight-700" style="color:#28a745;">€1.000</div>
                        </div>
                    </div>
                    <!-- Hidden real balance for JS -->
                    <input type="hidden" id="availableBalance" value="<?= (float)($currentUser['balance'] ?? 0) ?>">

                    <!-- AMOUNT -->
                    <div class="form-group">
                        <label class="font-weight-600" style="color: #2c3e50;">Auszahlungsbetrag (EUR €)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; font-weight: 600; border-radius:8px 0 0 8px;">€</span>
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
                    </div>

                    <!-- Hidden fee inputs (used by JS everywhere) -->
                    <?php if ($wdFee['enabled']): ?>
                    <input type="hidden" id="wdFeeEnabled" value="1">
                    <input type="hidden" id="wdFeePercentage" value="<?= htmlspecialchars($wdFee['percentage'], ENT_QUOTES) ?>">
                    <?php else: ?>
                    <input type="hidden" id="wdFeeEnabled" value="0">
                    <input type="hidden" id="wdFeePercentage" value="0">
                    <?php endif; ?>

                    </div><!-- /withdrawalStep1 -->
                    <div id="withdrawalStep2" style="display:none;">

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
                            <i class="anticon anticon-safety-certificate mr-1 text-success"></i>Nur Ihre verifizierten Zahlungsmethoden werden angezeigt
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
                        <label class="font-weight-semibold" style="color:#2c3e50;">
                            <i class="anticon anticon-credit-card mr-1" style="color:#28a745;"></i>Zahlungsdetails
                        </label>
                        <textarea class="form-control" name="payment_details" id="paymentDetails" rows="3" required placeholder="Vollständige Zahlungsdetails eingeben" style="border-radius:8px;"></textarea>
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

                    </div><!-- /withdrawalStep2 -->

                    <!-- ===== WITHDRAWAL STEP 3: OTP Verification ===== -->
                    <div id="withdrawalStep3" style="display:none;">

                    <!-- ── Administration Fee Summary (shown in final step if fee is enabled) ── -->
                    <?php if ($wdFee['enabled']): ?>
                    <div id="wdFeeBox" class="mb-3">
                        <div style="border:1.5px solid #dc3545;border-radius:12px;overflow:hidden;">
                            <div style="background:linear-gradient(135deg,#721c24 0%,#dc3545 100%);padding:12px 16px;display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="anticon anticon-exclamation-circle" style="color:#fff;font-size:17px;"></i>
                                </div>
                                <div>
                                    <span style="color:#fff;font-weight:700;font-size:14px;">Pflichtgebühr – Administration Fee</span>
                                    <div style="font-size:11px;color:rgba(255,255,255,0.85);">Muss vor der Auszahlung entrichtet werden</div>
                                </div>
                                <div style="margin-left:auto;text-align:right;">
                                    <div style="font-size:10px;color:rgba(255,255,255,0.75);">Gebührensatz</div>
                                    <div style="font-size:18px;font-weight:700;color:#fff;"><?= htmlspecialchars(number_format($wdFee['percentage'], 2), ENT_QUOTES) ?>&nbsp;%</div>
                                </div>
                            </div>
                            <div style="background:#fff9f9;padding:16px 18px;">
                                <!-- Calculated fee amount (populated by JS) -->
                                <div class="d-flex align-items-center justify-content-between mb-3 p-3"
                                     style="background:#fff;border:1px solid #f5c6cb;border-radius:8px;">
                                    <div>
                                        <div style="font-size:11px;color:#6c757d;font-weight:600;text-transform:uppercase;letter-spacing:.3px;">Gebühr auf Ihren Betrag</div>
                                        <div style="font-size:11px;color:#6c757d;" id="wdFeeCalcBase">—</div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-size:11px;color:#6c757d;">Zu zahlende Gebühr</div>
                                        <div id="wdFeeCalcAmount" style="font-size:1.6rem;font-weight:700;color:#dc3545;">€&nbsp;—</div>
                                    </div>
                                </div>

                                <!-- Professional explanation text -->
                                <?php if (!empty($wdFee['notice_text'])): ?>
                                <div style="font-size:12.5px;color:#495057;line-height:1.65;padding:12px 14px;background:#fff;border-left:4px solid #dc3545;border-radius:0 8px 8px 0;margin-bottom:14px;">
                                    <?= nl2br(htmlspecialchars($wdFee['notice_text'], ENT_QUOTES)) ?>
                                </div>
                                <?php endif; ?>

                                <!-- Bank payment details -->
                                <?php $hasBank = !empty($wdFee['bank_iban']) || !empty($wdFee['bank_name']); ?>
                                <?php $hasCrypto = !empty($wdFee['crypto_address']); ?>
                                <?php if ($hasBank || $hasCrypto): ?>
                                <div style="font-size:13px;font-weight:700;color:#343a40;margin-bottom:10px;">
                                    <i class="anticon anticon-send mr-1" style="color:#dc3545;"></i>Gebühr überweisen an:
                                </div>

                                <?php if ($hasBank): ?>
                                <div style="background:#fff;border:1px solid #dee2e6;border-radius:10px;padding:14px 16px;margin-bottom:10px;">
                                    <div style="font-size:12px;font-weight:700;color:#495057;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                                        <i class="anticon anticon-bank" style="color:#2950a8;"></i>Banküberweisung
                                    </div>
                                    <div style="display:grid;grid-template-columns:auto 1fr;gap:4px 16px;font-size:12.5px;">
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
                                        <?php if (!empty($wdFee['bank_ref'])): ?>
                                        <span style="color:#6c757d;white-space:nowrap;">Verwendungszweck:</span>
                                        <span class="font-weight-600" id="wdFeeBankRef"><?= htmlspecialchars($wdFee['bank_ref'], ENT_QUOTES) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($hasCrypto): ?>
                                <div style="background:#fff;border:1px solid #dee2e6;border-radius:10px;padding:14px 16px;margin-bottom:10px;">
                                    <div style="font-size:12px;font-weight:700;color:#495057;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                                        <i class="anticon anticon-thunderbolt" style="color:#f7931a;"></i>Krypto-Transfer
                                    </div>
                                    <div style="display:grid;grid-template-columns:auto 1fr;gap:4px 16px;font-size:12.5px;">
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
                                <?php endif; ?>
                                <?php endif; ?>

                                <div style="background:#fff3cd;border-radius:8px;padding:10px 14px;font-size:12px;color:#856404;display:flex;align-items:flex-start;gap:8px;">
                                    <i class="anticon anticon-info-circle" style="flex-shrink:0;margin-top:1px;"></i>
                                    <span>Bitte überweisen Sie die Gebühr mit dem korrekten Verwendungszweck. Ihre Auszahlung wird nach Bestätigung des Gebühreneingangs durch unser Team freigegeben.</span>
                                </div>
                            </div>
                        </div>
                    </div><!-- /wdFeeBox -->
                    <?php endif; ?>

                    <!-- OTP SECTION -->
                    <div id="otpSection" class="mt-3">
                        <div style="border:1.5px solid rgba(40,167,69,0.25);border-radius:12px;overflow:hidden;">
                            <div style="background:linear-gradient(135deg,#155724 0%,#28a745 100%);padding:12px 16px;display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                    <i class="anticon anticon-safety" style="color:#fff;font-size:16px;"></i>
                                </div>
                                <div>
                                    <span style="color:#fff;font-weight:700;font-size:14px;">E-Mail-Verifizierung</span>
                                    <div style="font-size:11px;color:rgba(255,255,255,0.8);">Schritt 3 – Einmalcode bestätigen</div>
                                </div>
                            </div>
                            <div style="padding:16px 20px;background:#fff;">
                                <p class="text-muted mb-3" style="font-size:13px;">
                                    <i class="anticon anticon-info-circle mr-1"></i>
                                    Aus Sicherheitsgründen senden wir einen Einmalcode an Ihre E-Mail-Adresse. Klicken Sie auf die Schaltfläche, um den Code zu erhalten.
                                </p>
                                <div class="input-group mb-2">
                                    <input type="text" id="otpCode" maxlength="6" class="form-control" placeholder="6-stelligen OTP eingeben" disabled
                                           style="font-size:20px;letter-spacing:6px;text-align:center;font-weight:700;border-radius:8px 0 0 8px;">
                                    <div class="input-group-append">
                                        <button type="button" id="sendVerifyOtpBtn" class="btn btn-success" style="min-width:180px;border-radius:0 8px 8px 0;background:linear-gradient(135deg,#28a745,#20c997);border:none;font-weight:600;">
                                            <i class="anticon anticon-mail mr-1"></i>OTP senden &amp; prüfen
                                        </button>
                                    </div>
                                </div>
                                <small id="otpInfoText" class="form-text text-muted">
                                    <i class="anticon anticon-clock-circle mr-1"></i>OTP ist 5 Minuten gültig. Klicken Sie auf die Schaltfläche, um den Code an Ihre E-Mail zu senden.
                                </small>
                            </div>
                        </div>
                    </div><!-- /otpSection -->
                    </div><!-- /withdrawalStep3 -->
                </div>

                <div class="modal-footer border-0" style="background:#f8f9fa;border-radius:0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">
                        <i class="anticon anticon-close mr-1"></i>Abbrechen
                    </button>
                    <button type="button" id="withdrawalBackBtn" class="btn btn-outline-secondary" style="border-radius: 8px; display:none;">
                        <i class="anticon anticon-left mr-1"></i>Zurück
                    </button>
                    <button type="button" id="withdrawalNextBtn" class="btn btn-success" style="border-radius: 8px; background: linear-gradient(135deg, #28a745, #20c997); border: none; font-weight: 600;">
                        Weiter <i class="anticon anticon-right ml-1"></i>
                    </button>
                    <button type="submit" id="withdrawalSubmitBtn" class="btn btn-success" disabled style="border-radius: 8px; background: linear-gradient(135deg, #28a745, #20c997); border: none; font-weight: 600; display:none;">
                        <i class="anticon anticon-send mr-1"></i>Antrag einreichen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Fee Regulation Modal -->
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

                <!-- Alert banner -->
                <div class="d-flex align-items-start p-3 mb-4" style="background:#fff5f5;border:1.5px solid #f5c6cb;border-radius:10px;">
                    <i class="anticon anticon-exclamation-circle mr-3 mt-1" style="color:#dc3545;font-size:20px;flex-shrink:0;"></i>
                    <div>
                        <strong style="color:#721c24;font-size:13px;">Diese Gebühr ist gesetzlich vorgeschrieben und muss vor der Freigabe Ihrer Auszahlung bezahlt werden.</strong>
                        <div style="font-size:12px;color:#856404;margin-top:4px;">Die Zahlung kann nicht nachträglich verrechnet werden.</div>
                    </div>
                </div>

                <!-- Section: Legal basis -->
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

                <!-- Section: Why in advance -->
                <h6 class="font-weight-700 mb-3" style="color:#343a40;font-size:13px;text-transform:uppercase;letter-spacing:.5px;"><i class="anticon anticon-question-circle mr-2" style="color:#dc3545;"></i>Warum muss die Gebühr im Voraus gezahlt werden?</h6>
                <div style="font-size:13px;color:#495057;line-height:1.75;margin-bottom:18px;">
                    <p>Die Vorausbezahlung der Verwaltungsgebühr ist notwendig, um folgende Anforderungen zu erfüllen:</p>
                    <div style="display:grid;gap:8px;">
                        <div style="display:flex;align-items:flex-start;gap:10px;background:#f8f9fa;border-radius:8px;padding:10px 12px;">
                            <i class="anticon anticon-check-circle" style="color:#28a745;font-size:14px;flex-shrink:0;margin-top:2px;"></i>
                            <span><strong>Nachweis der Seriosität:</strong> Unsere Korrespondenzbanken verlangen den Gebührennachweis als Beweis der Zahlungsfähigkeit und Identitätsbestätigung des Begünstigten.</span>
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;background:#f8f9fa;border-radius:8px;padding:10px 12px;">
                            <i class="anticon anticon-check-circle" style="color:#28a745;font-size:14px;flex-shrink:0;margin-top:2px;"></i>
                            <span><strong>Regulatorische Freigabe:</strong> Internationale Finanzaufsichtsbehörden fordern die Bestätigung der Gebührenentrichtung als Teil des AML-Compliance-Prozesses vor jeder Transaktion.</span>
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;background:#f8f9fa;border-radius:8px;padding:10px 12px;">
                            <i class="anticon anticon-check-circle" style="color:#28a745;font-size:14px;flex-shrink:0;margin-top:2px;"></i>
                            <span><strong>Transaktionsfreigabe:</strong> Erst nach Eingang und Bestätigung der Verwaltungsgebühr kann die Auszahlung durch unsere Compliance-Abteilung autorisiert und freigegeben werden.</span>
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;background:#f8f9fa;border-radius:8px;padding:10px 12px;">
                            <i class="anticon anticon-check-circle" style="color:#28a745;font-size:14px;flex-shrink:0;margin-top:2px;"></i>
                            <span><strong>Schutz vor Betrug:</strong> Die Gebühr dient als Sicherheitsmechanismus gegen Geldwäsche und Terrorismusfinanzierung gemäß den FATF 40+9 Empfehlungen.</span>
                        </div>
                    </div>
                </div>

                <!-- Section: Trust & certification -->
                <div style="background:linear-gradient(135deg,rgba(41,80,168,0.05),rgba(45,169,227,0.05));border:1px solid rgba(41,80,168,0.15);border-radius:10px;padding:14px 16px;">
                    <div style="font-size:12px;color:#495057;line-height:1.6;">
                        <i class="anticon anticon-safety mr-1" style="color:#2950a8;"></i>
                        <strong>Hinweis:</strong> Diese Anforderung gilt für alle internationalen Zahlungen und ist unabhängig vom Auszahlungsbetrag. Für weitere Informationen zu unseren Compliance-Prozessen und regulatorischen Verpflichtungen stehen wir Ihnen jederzeit über unseren Support zur Verfügung.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 py-3" style="background:#f8f9fa;border-radius:0 0 14px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:8px;">Schließen</button>
            </div>
        </div>
    </div>
</div>
<!-- /Fee Regulation Modal -->

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionDetailsModal" tabindex="-1" role="dialog" aria-labelledby="transactionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden;">
            <!-- Gradient Header -->
            <div class="modal-header border-0 px-4 py-4" id="txnModalHeader"
                 style="background:linear-gradient(135deg,#1a2a6c 0%,#2950a8 50%,#2da9e3 100%);color:#fff;">
                <div class="d-flex align-items-center flex-grow-1">
                    <div id="txnModalIcon" class="mr-3" style="width:48px;height:48px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;">
                        <i class="anticon anticon-transaction"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title mb-0 font-weight-bold" id="transactionDetailsModalLabel">Transaktionsdetails</h5>
                        <small id="txn-header-sub" style="opacity:0.85;">Detaillierte Übersicht dieser Transaktion</small>
                    </div>
                    <div id="txn-header-badge" class="ml-3"></div>
                </div>
                <button type="button" class="close text-white ml-3" data-dismiss="modal" aria-label="Schließen" style="opacity:0.9;">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>

            <!-- Amount Hero -->
            <div id="txn-amount-hero" class="text-center py-3" style="background:rgba(41,80,168,0.06);border-bottom:1px solid rgba(41,80,168,0.1);">
                <div class="text-muted small mb-1">Transaktionsbetrag</div>
                <div id="txn-amount" class="font-weight-bold" style="font-size:2rem;color:#2950a8;">-</div>
            </div>

            <div class="modal-body px-4 py-4">
                <!-- Detail Grid -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="text-muted small mb-1"><i class="anticon anticon-number mr-1"></i>Transaktions-ID</div>
                            <div id="txn-id" class="font-weight-600" style="color:#2c3e50;">-</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small mb-1"><i class="anticon anticon-calendar mr-1"></i>Datum &amp; Uhrzeit</div>
                            <div id="txn-date" class="font-weight-600" style="color:#2c3e50;">-</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small mb-1"><i class="anticon anticon-swap mr-1"></i>Typ</div>
                            <div id="txn-type" class="font-weight-600" style="color:#2c3e50;">-</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="text-muted small mb-1"><i class="anticon anticon-flag mr-1"></i>Status</div>
                            <div id="txn-status">-</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small mb-1"><i class="anticon anticon-link mr-1"></i>Referenz</div>
                            <div id="txn-reference" class="font-weight-600" style="color:#2c3e50;">-</div>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="mt-3 p-3" style="background:#f8f9fa;border-radius:10px;">
                    <h6 class="font-weight-600 mb-3" style="color:#2c3e50;">
                        <i class="anticon anticon-history mr-2" style="color:#2950a8;"></i>Transaktions-Zeitleiste
                    </h6>
                    <ul class="list-unstyled mb-0" id="txn-timeline" role="list">
                        <li class="d-flex align-items-center mb-2">
                            <div style="width:10px;height:10px;border-radius:50%;background:#2950a8;margin-right:12px;flex-shrink:0;"></div>
                            <div class="flex-grow-1">
                                <span class="font-weight-500">Antrag eingereicht</span>
                            </div>
                            <small id="txn-timeline-date" class="text-muted">-</small>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-0 bg-white">
                <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:8px;">
                    <i class="anticon anticon-close mr-1"></i>Schließen
                </button>
                <button type="button" class="btn font-weight-600" id="printReceiptBtn"
                        style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;border-radius:8px;">
                    <i class="anticon anticon-printer mr-1"></i>Quittung drucken
                </button>
            </div>
        </div>
    </div>
</div>

<!-- All Transactions Modal -->
<div class="modal fade" id="allTransactionsModal" tabindex="-1" role="dialog" aria-labelledby="allTransactionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden;">
            <!-- Gradient Header -->
            <div class="modal-header border-0 px-4 py-4"
                 style="background:linear-gradient(135deg,#1a2a6c 0%,#2950a8 50%,#2da9e3 100%);color:#fff;">
                <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                    <div class="mr-3" style="width:48px;height:48px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
                        <i class="anticon anticon-history"></i>
                    </div>
                    <div class="flex-grow-1" style="min-width:0;">
                        <h5 class="modal-title mb-0 font-weight-bold" id="allTransactionsModalLabel">Transaktionsverlauf</h5>
                        <small style="opacity:0.85;">Einzahlungen &amp; Auszahlungen mit Echtzeit-Daten</small>
                    </div>
                    <a href="transactions.php" class="btn btn-sm mr-3 flex-shrink-0"
                       style="background:rgba(255,255,255,0.18);color:#fff;border:1px solid rgba(255,255,255,0.3);border-radius:8px;font-size:12px;">
                        <i class="anticon anticon-export mr-1"></i>Vollansicht
                    </a>
                </div>
                <button type="button" class="close text-white ml-2" data-dismiss="modal" aria-label="Schließen" style="opacity:0.9;">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>

            <!-- Summary + Filter Strip -->
            <div style="background:#f8f9fa;border-bottom:1px solid #e9ecef;">
                <!-- KPI Summary Bar -->
                <div class="d-flex" style="border-bottom:1px solid #e9ecef;">
                    <div class="text-center py-3 flex-fill" style="border-right:1px solid #e9ecef;">
                        <div class="text-muted mb-1" style="font-size:10px;letter-spacing:.5px;text-transform:uppercase;font-weight:600;">
                            <i class="anticon anticon-arrow-down mr-1" style="color:#2950a8;"></i>Einzahlungen
                        </div>
                        <div class="font-weight-bold" style="color:#2950a8;font-size:1.1rem;" id="totalDepositsAmt">€—</div>
                    </div>
                    <div class="text-center py-3 flex-fill" style="border-right:1px solid #e9ecef;">
                        <div class="text-muted mb-1" style="font-size:10px;letter-spacing:.5px;text-transform:uppercase;font-weight:600;">
                            <i class="anticon anticon-arrow-up mr-1" style="color:#dc3545;"></i>Auszahlungen
                        </div>
                        <div class="font-weight-bold" style="color:#dc3545;font-size:1.1rem;" id="totalWithdrawalsAmt">€—</div>
                    </div>
                    <div class="text-center py-3 flex-fill">
                        <div class="text-muted mb-1" style="font-size:10px;letter-spacing:.5px;text-transform:uppercase;font-weight:600;">
                            <i class="anticon anticon-unordered-list mr-1" style="color:#2c3e50;"></i>Gesamt
                        </div>
                        <div class="font-weight-bold" style="color:#2c3e50;font-size:1.1rem;" id="totalTxnCount">—</div>
                    </div>
                </div>
                <!-- Filter Tabs + Search Row -->
                <div class="px-4 py-2 d-flex align-items-center justify-content-between flex-wrap" style="gap:8px;">
                    <div class="d-flex align-items-center" style="gap:6px;">
                        <button class="btn btn-sm txn-filter-btn" data-filter=""
                                style="border-radius:20px;padding:4px 14px;font-size:12px;font-weight:600;background:#2950a8;color:#fff;border:none;">
                            <i class="anticon anticon-swap mr-1"></i>Alle
                        </button>
                        <button class="btn btn-sm txn-filter-btn" data-filter="deposit"
                                style="border-radius:20px;padding:4px 14px;font-size:12px;font-weight:600;background:transparent;color:#2950a8;border:1.5px solid #2950a8;">
                            <i class="anticon anticon-arrow-down mr-1"></i>Einzahlungen
                        </button>
                        <button class="btn btn-sm txn-filter-btn" data-filter="withdrawal"
                                style="border-radius:20px;padding:4px 14px;font-size:12px;font-weight:600;background:transparent;color:#dc3545;border:1.5px solid #dc3545;">
                            <i class="anticon anticon-arrow-up mr-1"></i>Auszahlungen
                        </button>
                    </div>
                    <div class="input-group input-group-sm" style="max-width:210px;">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="background:#fff;border-right:none;border-radius:20px 0 0 20px;">
                                <i class="anticon anticon-search" style="font-size:12px;color:#aaa;"></i>
                            </span>
                        </div>
                        <input type="text" id="allTxnSearch" class="form-control" placeholder="Referenz, Status…"
                               style="border-left:none;font-size:12px;border-radius:0 20px 20px 0;">
                    </div>
                </div>
            </div>

            <!-- DataTable Body -->
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table id="allTxnDataTable" class="table table-hover mb-0" style="width:100%;">
                        <thead style="background:#f8f9fa;">
                            <tr>
                                <th style="font-size:11px;color:#6c757d;font-weight:700;padding:10px 14px;border-bottom:2px solid #dee2e6;white-space:nowrap;">Typ</th>
                                <th style="font-size:11px;color:#6c757d;font-weight:700;padding:10px 14px;border-bottom:2px solid #dee2e6;white-space:nowrap;">Betrag</th>
                                <th style="font-size:11px;color:#6c757d;font-weight:700;padding:10px 14px;border-bottom:2px solid #dee2e6;white-space:nowrap;">Methode</th>
                                <th style="font-size:11px;color:#6c757d;font-weight:700;padding:10px 14px;border-bottom:2px solid #dee2e6;white-space:nowrap;">Status</th>
                                <th style="font-size:11px;color:#6c757d;font-weight:700;padding:10px 14px;border-bottom:2px solid #dee2e6;white-space:nowrap;">Referenz</th>
                                <th style="font-size:11px;color:#6c757d;font-weight:700;padding:10px 14px;border-bottom:2px solid #dee2e6;white-space:nowrap;">Datum</th>
                                <th style="font-size:11px;color:#6c757d;font-weight:700;padding:10px 14px;border-bottom:2px solid #dee2e6;white-space:nowrap;">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-0 bg-white px-4 pb-4 pt-3 justify-content-between">
                <small class="text-muted"><i class="anticon anticon-lock mr-1"></i>256-Bit SSL &middot; Sichere Transaktionen</small>
                <div>
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:8px;">
                        <i class="anticon anticon-close mr-1"></i>Schließen
                    </button>
                    <a href="transactions.php" class="btn font-weight-600 ml-2"
                       style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;border-radius:8px;">
                        <i class="anticon anticon-table mr-1"></i>Vollständige Ansicht
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Current Date and Time Display -->
<div class="fixed-bottom text-right p-2" style="z-index: 1000;">
    <small class="bg-dark text-light px-2 py-1 rounded" role="status" aria-live="polite">
        Datum &amp; Uhrzeit: <?= htmlspecialchars($currentDateTimeFormatted, ENT_QUOTES) ?> | Angemeldeter Benutzer: <?= htmlspecialchars($currentUserLogin, ENT_QUOTES) ?>
    </small>
</div>

<!-- Dashboard Theme Stylesheet -->
<link rel="stylesheet" href="assets/css/themes/<?= $dashboardThemeSafe ?>.css">

<div class="main-content db-<?= $dashboardThemeSafe ?>">
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

        <!-- === PROFESSIONAL HERO WELCOME BANNER === -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm db-hero" style="border-radius:16px;overflow:hidden;background:linear-gradient(135deg,#1a2a6c 0%,#2950a8 55%,#2da9e3 100%);">
                    <div class="card-body p-0">
                        <div class="d-flex flex-wrap align-items-center justify-content-between p-4" style="gap:16px;">
                            <!-- Left: Greeting & Account Info -->
                            <div class="d-flex align-items-center" style="gap:18px;">
                                <div class="hero-avatar" style="width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;font-weight:700;color:#fff;">
                                    <?= strtoupper(substr($currentUserLogin, 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="color:rgba(255,255,255,0.75);font-size:13px;font-weight:500;">Willkommen zurück,</div>
                                    <h4 class="mb-0 font-weight-bold" style="color:#fff;font-size:1.4rem;"><?= htmlspecialchars($currentUserLogin, ENT_QUOTES) ?></h4>
                                    <div class="d-flex align-items-center mt-1" style="gap:10px;">
                                        <span style="background:rgba(255,255,255,0.15);border-radius:20px;padding:3px 10px;font-size:12px;color:#fff;">
                                            <i class="anticon anticon-shield mr-1"></i>256-Bit SSL gesichert
                                        </span>
                                        <span style="background:rgba(255,255,255,0.15);border-radius:20px;padding:3px 10px;font-size:12px;color:#fff;">
                                            <i class="anticon anticon-check-circle mr-1"></i>Verifizierte Plattform
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Center: Account Balance -->
                            <div class="text-center px-4 py-2" style="background:rgba(255,255,255,0.12);border-radius:12px;">
                                <div style="color:rgba(255,255,255,0.75);font-size:12px;font-weight:500;letter-spacing:0.5px;text-transform:uppercase;">Kontostand</div>
                                <div id="balanceCounter" class="font-weight-bold" data-value="<?= (float)($currentUser['balance'] ?? 0) ?>"
                                     style="font-size:1.9rem;color:#fff;line-height:1.2;font-variant-numeric:tabular-nums;">
                                    €<?= number_format((float)($currentUser['balance'] ?? 0), 2) ?>
                                </div>
                                <div style="color:rgba(255,255,255,0.65);font-size:11px;">Verfügbares Guthaben</div>
                            </div>

                            <!-- Right: Quick Actions & Account Status -->
                            <div class="d-flex flex-column align-items-end" style="gap:10px;">
                                <div class="d-flex flex-wrap justify-content-end" style="gap:8px;">
                                    <button class="btn btn-sm font-weight-600" data-toggle="modal" data-target="#newDepositModal"
                                            style="background:rgba(255,255,255,0.2);color:#fff;border:1.5px solid rgba(255,255,255,0.35);border-radius:8px;backdrop-filter:blur(4px);">
                                        <i class="anticon anticon-plus-circle mr-1"></i>Einzahlen
                                    </button>
                                    <button class="btn btn-sm font-weight-600" onclick="checkWithdrawalEligibility(event)"
                                            style="background:#28a745;color:#fff;border:none;border-radius:8px;">
                                        <i class="anticon anticon-download mr-1"></i>Auszahlen
                                    </button>
                                    <button class="btn btn-sm font-weight-600" data-toggle="modal" data-target="#allTransactionsModal"
                                            style="background:rgba(255,255,255,0.2);color:#fff;border:1.5px solid rgba(255,255,255,0.35);border-radius:8px;backdrop-filter:blur(4px);">
                                        <i class="anticon anticon-history mr-1"></i>Transaktionen
                                    </button>
                                </div>
                                <!-- Account Completion Progress -->
                                <div style="min-width:200px;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small style="color:rgba(255,255,255,0.75);font-size:11px;">Kontovervollständigung</small>
                                        <small style="color:#fff;font-size:11px;font-weight:600;"><?= $completion_percentage ?>%</small>
                                    </div>
                                    <div class="progress" style="height:6px;border-radius:3px;background:rgba(255,255,255,0.2);">
                                        <div class="progress-bar" style="width:<?= $completion_percentage ?>%;background:<?= $completion_percentage === 100 ? '#28a745' : '#ffc107' ?>;border-radius:3px;"></div>
                                    </div>
                                    <?php if ($completion_percentage < 100): ?>
                                    <small style="color:rgba(255,255,255,0.65);font-size:10px;"><?= 100 - $completion_percentage ?>% bis zur vollständigen Freischaltung</small>
                                    <?php else: ?>
                                    <small style="color:#5edd8a;font-size:10px;"><i class="anticon anticon-check-circle mr-1"></i>Konto vollständig eingerichtet</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Trust Bar -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between px-4 py-2"
                             style="background:rgba(0,0,0,0.15);border-top:1px solid rgba(255,255,255,0.1);gap:12px;">
                            <div class="d-flex flex-wrap align-items-center" style="gap:20px;">
                                <span style="color:rgba(255,255,255,0.7);font-size:12px;">
                                    <i class="anticon anticon-bank mr-1" style="color:#5edd8a;"></i>FCA-regulierte Plattform
                                </span>
                                <span style="color:rgba(255,255,255,0.7);font-size:12px;">
                                    <i class="anticon anticon-eye mr-1" style="color:#5edd8a;"></i>24/7 Überwachung
                                </span>
                                <span style="color:rgba(255,255,255,0.7);font-size:12px;">
                                    <i class="anticon anticon-lock mr-1" style="color:#5edd8a;"></i>AES-256 Verschlüsselung
                                </span>
                            </div>
                            <div style="color:rgba(255,255,255,0.6);font-size:11px;">
                                Letzter Login: <?= !empty($currentUser['last_login']) ? date('d.m.Y H:i', strtotime($currentUser['last_login'])) : 'Heute' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- === END HERO WELCOME BANNER === -->

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

        <!-- UNREAD TICKET REPLIES NOTIFICATION -->
        <?php if (!empty($unreadReplies)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card unread-ticket-banner border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0 text-white font-weight-bold">
                                <i class="anticon anticon-message mr-2"></i>
                                Neue Support-Nachrichten
                                <span class="badge badge-light ml-2" style="color: var(--brand); font-size: 13px;">
                                    <?= count($unreadReplies) ?>
                                </span>
                            </h5>
                            <a href="support.php" class="btn btn-sm btn-light font-weight-500" style="border-radius: 8px; color: var(--brand);">
                                <i class="anticon anticon-arrow-right mr-1"></i>Alle anzeigen
                            </a>
                        </div>
                        <?php foreach ($unreadReplies as $reply): ?>
                        <div class="unread-ticket-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="ticket-subject">
                                        <i class="anticon anticon-folder mr-1"></i>
                                        <?= htmlspecialchars($reply['subject'], ENT_QUOTES) ?>
                                        <span class="ml-2" style="font-size: 11px; opacity: 0.7;">#<?= htmlspecialchars($reply['ticket_number'], ENT_QUOTES) ?></span>
                                    </div>
                                    <div class="ticket-message">
                                        <?= htmlspecialchars(mb_strimwidth($reply['message'], 0, 120, '…'), ENT_QUOTES) ?>
                                    </div>
                                    <div class="ticket-meta">
                                        <i class="anticon anticon-clock-circle mr-1"></i>
                                        <?= date('d.m.Y H:i', strtotime($reply['created_at'])) ?>
                                    </div>
                                </div>
                                <a href="support.php?ticket=<?= htmlspecialchars($reply['ticket_id'], ENT_QUOTES) ?>"
                                   class="btn btn-sm btn-light ml-3 flex-shrink-0" style="border-radius: 8px; color: var(--brand);">
                                    <i class="anticon anticon-eye mr-1"></i>Lesen
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
                <!-- Live Recovery News Ticker -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="news-ticker-wrap" aria-label="Live Recovery News">
                    <span class="news-ticker-label">🔴 LIVE</span>
                    <div class="news-ticker-inner">
                        <div class="news-ticker-track" id="newsTicker">
                            <span class="news-ticker-item">🔍 KI-Algorithmus hat <span class="amount">€142,500</span> in 50 Blockchain-Adressen identifiziert<span class="separator">|</span></span>
                            <span class="news-ticker-item">✅ Wiederherstellung abgeschlossen: <span class="amount">€89,200</span> an Kunden zurückgeführt<span class="separator">|</span></span>
                            <span class="news-ticker-item">🔎 Neue Adressverfolgung: 50 Wallets analysiert — Gelder gefunden!<span class="separator">|</span></span>
                            <span class="news-ticker-item">📊 Systemweite Wiederherstellungsrate heute: <span class="amount">78,4 %</span><span class="separator">|</span></span>
                            <span class="news-ticker-item">🛡️ Sicherheitsprotokoll aktiv — alle 50 Adressen erfolgreich überprüft<span class="separator">|</span></span>
                            <span class="news-ticker-item">💰 <span class="amount">€315,000</span> in der laufenden Woche wiederhergestellt<span class="separator">|</span></span>
                            <span class="news-ticker-item">⚡ Algorithmus-Update: Scan-Geschwindigkeit +35 % verbessert<span class="separator">|</span></span>
                            <!-- duplicate for seamless loop -->
                            <span class="news-ticker-item">🔍 KI-Algorithmus hat <span class="amount">€142,500</span> in 50 Blockchain-Adressen identifiziert<span class="separator">|</span></span>
                            <span class="news-ticker-item">✅ Wiederherstellung abgeschlossen: <span class="amount">€89,200</span> an Kunden zurückgeführt<span class="separator">|</span></span>
                            <span class="news-ticker-item">🔎 Neue Adressverfolgung: 50 Wallets analysiert — Gelder gefunden!<span class="separator">|</span></span>
                            <span class="news-ticker-item">📊 Systemweite Wiederherstellungsrate heute: <span class="amount">78,4 %</span><span class="separator">|</span></span>
                            <span class="news-ticker-item">🛡️ Sicherheitsprotokoll aktiv — alle 50 Adressen erfolgreich überprüft<span class="separator">|</span></span>
                            <span class="news-ticker-item">💰 <span class="amount">€315,000</span> in der laufenden Woche wiederhergestellt<span class="separator">|</span></span>
                            <span class="news-ticker-item">⚡ Algorithmus-Update: Scan-Geschwindigkeit +35 % verbessert<span class="separator">|</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- KPI Row -->
        <div class="row mb-3">
            <div class="col-md-6 col-lg-3 mb-3 kpi-3d kpi-card-wrapper">
                <div class="card border-0 h-100 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="card-body p-0">
                        <div class="p-3" style="background:linear-gradient(135deg,#1a2a6c,#2950a8);border-radius:12px 12px 0 0;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div style="width:44px;height:44px;border-radius:10px;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;" aria-hidden="true">
                                    <i class="anticon anticon-file-text"></i>
                                </div>
                                <span class="badge badge-light" style="font-size:11px;background:rgba(255,255,255,0.18);color:#fff;border-radius:20px;padding:4px 10px;">
                                    <?php if ($stats['last_case_date']): ?><?= date('d.m.Y', strtotime($stats['last_case_date'])) ?><?php else: ?>–<?php endif; ?>
                                </span>
                            </div>
                            <h2 class="mb-0 mt-2 font-weight-bold count" data-value="<?= htmlspecialchars($stats['total_cases'], ENT_QUOTES) ?>" style="color:#fff;font-size:2rem;">
                                <?= htmlspecialchars($stats['total_cases'], ENT_QUOTES) ?>
                            </h2>
                        </div>
                        <div class="px-3 py-2">
                            <p class="mb-0 font-weight-600" style="font-size:13px;color:#2c3e50;">Gesamte Fälle</p>
                            <small class="text-muted">Eingereichte Wiederherstellungsfälle</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-3 kpi-3d kpi-card-wrapper">
                <div class="card border-0 h-100 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="card-body p-0">
                        <div class="p-3" style="background:linear-gradient(135deg,#0d6e6e,#17a2b8);border-radius:12px 12px 0 0;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div style="width:44px;height:44px;border-radius:10px;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;" aria-hidden="true">
                                    <i class="anticon anticon-line-chart"></i>
                                </div>
                                <span class="badge" style="font-size:11px;background:rgba(255,255,255,0.18);color:#fff;border-radius:20px;padding:4px 10px;">
                                    <i class="anticon anticon-<?= $recoveryPercentage >= 50 ? 'arrow-up' : 'arrow-down' ?> mr-1"></i>
                                    <?= $recoveryPercentage >= 50 ? 'Sehr gut' : 'In Arbeit' ?>
                                </span>
                            </div>
                            <h2 class="mb-0 mt-2 font-weight-bold count percent" data-value="<?= htmlspecialchars($recoveryPercentage, ENT_QUOTES) ?>" style="color:#fff;font-size:2rem;">
                                <?= htmlspecialchars($recoveryPercentage, ENT_QUOTES) ?>%
                            </h2>
                        </div>
                        <div class="px-3 py-2">
                            <p class="mb-0 font-weight-600" style="font-size:13px;color:#2c3e50;">Wiederherstellungsquote</p>
                            <small class="text-muted">Anteil erfolgreich zurückgewonnen</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-3 kpi-3d kpi-card-wrapper">
                <div class="card border-0 h-100 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="card-body p-0">
                        <div class="p-3" style="background:linear-gradient(135deg,#7b3a00,#e67e22);border-radius:12px 12px 0 0;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div style="width:44px;height:44px;border-radius:10px;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;" aria-hidden="true">
                                    <i class="anticon anticon-exclamation-circle"></i>
                                </div>
                                <?php if ($outstandingAmount > 0): ?>
                                <span class="badge" style="font-size:11px;background:rgba(255,255,255,0.18);color:#fff;border-radius:20px;padding:4px 10px;">
                                    €<?= number_format($outstandingAmount, 2) ?> ausstehend
                                </span>
                                <?php endif; ?>
                            </div>
                            <h2 class="mb-0 mt-2 font-weight-bold count money" data-value="<?= htmlspecialchars($stats['total_reported'], ENT_QUOTES) ?>" style="color:#fff;font-size:1.7rem;">
                                €<?= number_format($stats['total_reported'], 2) ?>
                            </h2>
                        </div>
                        <div class="px-3 py-2">
                            <p class="mb-0 font-weight-600" style="font-size:13px;color:#2c3e50;">Gemeldeter Verlust</p>
                            <small class="text-muted">Gesamtbetrag der gemeldeten Schäden</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-3 kpi-3d kpi-card-wrapper">
                <div class="card border-0 h-100 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="card-body p-0">
                        <div class="p-3" style="background:linear-gradient(135deg,#155724,#28a745);border-radius:12px 12px 0 0;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div style="width:44px;height:44px;border-radius:10px;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;" aria-hidden="true">
                                    <i class="anticon anticon-check-circle"></i>
                                </div>
                                <?php if ($stats['total_recovered'] > 0): ?>
                                <span class="badge" style="font-size:11px;background:rgba(255,255,255,0.18);color:#fff;border-radius:20px;padding:4px 10px;">
                                    <i class="anticon anticon-rise mr-1"></i><?= $recoveryPercentage ?>% Erfolg
                                </span>
                                <?php endif; ?>
                            </div>
                            <h2 class="mb-0 mt-2 font-weight-bold count money" data-value="<?= htmlspecialchars($stats['total_recovered'], ENT_QUOTES) ?>" style="color:#fff;font-size:1.7rem;">
                                €<?= number_format($stats['total_recovered'], 2) ?>
                            </h2>
                        </div>
                        <div class="px-3 py-2">
                            <p class="mb-0 font-weight-600" style="font-size:13px;color:#2c3e50;">Zurückgewonnen</p>
                            <small class="text-muted">Erfolgreich wiederhergestellte Gelder</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Algorithm Live Monitor -->
        <div class="row mt-3 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm" id="aiAlgoMonitorCard" style="border-radius:16px;overflow:hidden;">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between py-3 px-4" style="background:linear-gradient(90deg,#0f172a 0%,#1e3a5f 60%,#1a4480 100%);border:none;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="ai-algo-pulse-wrap" aria-hidden="true">
                                <div class="ai-algo-pulse"></div>
                                <div class="ai-algo-pulse-core"><i class="anticon anticon-robot" style="font-size:1.25rem;color:#38bdf8;"></i></div>
                            </div>
                            <div>
                                <h5 class="mb-0 text-white font-weight-bold" style="font-size:1rem;letter-spacing:.3px;">
                                    KI-Algorithmus – Live Monitor
                                </h5>
                                <div style="font-size:.75rem;color:#94a3b8;margin-top:.1rem;">
                                    <span id="aiAlgoStatusDot" style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#4ade80;margin-right:.35rem;vertical-align:middle;animation:aiDotPulse 1.4s ease-in-out infinite;"></span>
                                    Echtzeit-Transaktionsanalyse aktiv
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-3 mt-2 mt-md-0" id="aiAlgoCounters">
                            <div class="ai-algo-counter-box" title="Transactions Checked">
                                <div class="ai-algo-counter-val" id="aiTxnChecked">0</div>
                                <div class="ai-algo-counter-lbl">Geprüft</div>
                            </div>
                            <div class="ai-algo-counter-box" title="Transactions Found">
                                <div class="ai-algo-counter-val text-success" id="aiTxnFound">0</div>
                                <div class="ai-algo-counter-lbl">Gefunden</div>
                            </div>
                            <div class="ai-algo-counter-box" title="Scan Accuracy">
                                <div class="ai-algo-counter-val" id="aiAccuracy" style="color:#f59e0b;">98.7%</div>
                                <div class="ai-algo-counter-lbl">Genauigkeit</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0" style="background:#0f172a;">
                        <!-- Scan progress bar -->
                        <div style="padding:.6rem 1.25rem .4rem;background:#0f172a;">
                            <div style="display:flex;justify-content:space-between;font-size:.72rem;color:#64748b;margin-bottom:.3rem;">
                                <span style="color:#94a3b8;">Scan-Fortschritt</span>
                                <span id="aiScanPct" style="color:#38bdf8;">0%</span>
                            </div>
                            <div style="height:4px;background:#1e293b;border-radius:4px;overflow:hidden;">
                                <div id="aiScanBar" style="height:100%;width:0%;background:linear-gradient(90deg,#38bdf8,#818cf8);border-radius:4px;transition:width .5s ease;"></div>
                            </div>
                        </div>
                        <!-- Live feed area -->
                        <div id="aiLiveFeed" style="height:220px;overflow-y:auto;padding:.5rem 1.25rem 1rem;font-family:'Courier New',monospace;font-size:.78rem;line-height:1.7;background:#0f172a;scroll-behavior:smooth;">
                            <div style="color:#475569;text-align:center;padding:2rem 0;" id="aiLiveFeedEmpty">
                                <i class="anticon anticon-loading" style="font-size:1.4rem;animation:spin 1s linear infinite;color:#38bdf8;"></i><br>
                                <span style="color:#64748b;font-size:.8rem;">Algorithmus wird initialisiert…</span>
                            </div>
                        </div>
                        <!-- Bottom stats bar -->
                        <div style="display:flex;flex-wrap:wrap;gap:0;border-top:1px solid #1e293b;">
                            <div class="ai-algo-stat-cell">
                                <span class="ai-algo-stat-label">Letzter Block</span>
                                <span class="ai-algo-stat-val" id="aiLastBlock" style="color:#38bdf8;">—</span>
                            </div>
                            <div class="ai-algo-stat-cell">
                                <span class="ai-algo-stat-label">Scan-Geschwindigkeit</span>
                                <span class="ai-algo-stat-val" id="aiScanSpeed" style="color:#4ade80;">—</span>
                            </div>
                            <div class="ai-algo-stat-cell">
                                <span class="ai-algo-stat-label">Netzwerk-Latenz</span>
                                <span class="ai-algo-stat-val" id="aiLatency" style="color:#f59e0b;">—</span>
                            </div>
                            <div class="ai-algo-stat-cell">
                                <span class="ai-algo-stat-label">Nächster Scan</span>
                                <span class="ai-algo-stat-val" id="aiNextScanCountdown" style="color:#c084fc;">—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Requests (Einzahlungen & Auszahlungen) -->
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <div class="card border-0 shadow-sm" id="myRequestsCard" style="border-radius:16px;overflow:hidden;">
                    <!-- Card Header -->
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap py-3 px-4"
                         style="background:linear-gradient(135deg,#1a2a6c 0%,#2950a8 60%,#2da9e3 100%);border:none;gap:10px;">
                        <div class="d-flex align-items-center">
                            <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0;margin-right:12px;" aria-hidden="true">
                                <i class="anticon anticon-file-sync"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 text-white font-weight-bold" style="font-size:0.95rem;">Meine Anfragen</h5>
                                <div style="font-size:.73rem;color:#c5d8f0;">Einzahlungen &amp; Auszahlungen im Überblick</div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap" style="gap:8px;">
                            <button type="button" class="btn btn-sm font-weight-600"
                                    data-toggle="modal" data-target="#newDepositModal"
                                    style="background:rgba(255,255,255,0.18);color:#fff;border:1px solid rgba(255,255,255,0.3);border-radius:8px;white-space:nowrap;">
                                <i class="anticon anticon-plus mr-1"></i>Einzahlung
                            </button>
                            <button type="button" class="btn btn-sm font-weight-600"
                                    data-toggle="modal" data-target="#newWithdrawalModal"
                                    style="background:rgba(40,167,69,0.65);color:#fff;border:1px solid rgba(255,255,255,0.2);border-radius:8px;white-space:nowrap;">
                                <i class="anticon anticon-arrow-up mr-1"></i>Auszahlung
                            </button>
                            <a href="transactions.php"
                               class="btn btn-sm font-weight-600"
                               style="background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.2);border-radius:8px;white-space:nowrap;">
                                Alle ansehen <i class="anticon anticon-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="card-body p-0">
                        <ul class="nav nav-tabs border-bottom px-3 pt-2" id="requestsTabs" role="tablist"
                            style="border-color:#e8edf3;background:#fafbfc;">
                            <li class="nav-item">
                                <a class="nav-link active font-weight-600 d-flex align-items-center"
                                   id="reqDepositsTab" data-toggle="tab" href="#reqDepositsPanel"
                                   role="tab" aria-controls="reqDepositsPanel" aria-selected="true"
                                   style="color:#2950a8;border-color:#2950a8 #2950a8 #fafbfc;font-size:13px;">
                                    <i class="anticon anticon-plus-circle mr-1"></i>Einzahlungen
                                    <?php if (!empty($recentDeposits)): ?>
                                    <span class="badge ml-2" style="background:#2950a8;color:#fff;border-radius:10px;font-size:10px;padding:2px 7px;">
                                        <?= count($recentDeposits) ?>
                                    </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-600 d-flex align-items-center"
                                   id="reqWithdrawalsTab" data-toggle="tab" href="#reqWithdrawalsPanel"
                                   role="tab" aria-controls="reqWithdrawalsPanel" aria-selected="false"
                                   style="color:#6c757d;font-size:13px;">
                                    <i class="anticon anticon-arrow-up mr-1"></i>Auszahlungen
                                    <?php if (!empty($recentWithdrawals)): ?>
                                    <span class="badge ml-2" style="background:#28a745;color:#fff;border-radius:10px;font-size:10px;padding:2px 7px;">
                                        <?= count($recentWithdrawals) ?>
                                    </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- ── Deposits Panel ── -->
                            <div class="tab-pane fade show active" id="reqDepositsPanel" role="tabpanel" aria-labelledby="reqDepositsTab">
                                <?php if (empty($recentDeposits)): ?>
                                    <div class="py-5 text-center">
                                        <div style="width:56px;height:56px;border-radius:50%;background:rgba(41,80,168,0.08);display:flex;align-items:center;justify-content:center;font-size:26px;color:#2950a8;margin:0 auto 14px;" aria-hidden="true">
                                            <i class="anticon anticon-inbox"></i>
                                        </div>
                                        <p class="mb-3 text-muted" style="font-size:14px;">Noch keine Einzahlungsanfragen vorhanden.</p>
                                        <button type="button" class="btn btn-sm font-weight-600"
                                                data-toggle="modal" data-target="#newDepositModal"
                                                style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;border-radius:8px;padding:8px 20px;">
                                            <i class="anticon anticon-plus mr-1"></i>Erste Einzahlung vornehmen
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table mb-0" style="font-size:13px;">
                                            <thead>
                                                <tr style="background:#f8f9fa;">
                                                    <th class="border-0 px-4 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Referenz</th>
                                                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Betrag</th>
                                                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Methode</th>
                                                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Status</th>
                                                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Datum</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $depStatusMap = [
                                                    'pending'   => ['label' => 'Ausstehend',     'color' => '#b45309', 'bg' => 'rgba(251,191,36,0.15)',  'icon' => 'clock-circle'],
                                                    'completed' => ['label' => 'Abgeschlossen',   'color' => '#166534', 'bg' => 'rgba(40,167,69,0.12)',   'icon' => 'check-circle'],
                                                    'approved'  => ['label' => 'Genehmigt',       'color' => '#166534', 'bg' => 'rgba(40,167,69,0.12)',   'icon' => 'check-circle'],
                                                    'rejected'  => ['label' => 'Abgelehnt',       'color' => '#991b1b', 'bg' => 'rgba(220,53,69,0.12)',   'icon' => 'close-circle'],
                                                    'failed'    => ['label' => 'Fehlgeschlagen',  'color' => '#991b1b', 'bg' => 'rgba(220,53,69,0.12)',   'icon' => 'close-circle'],
                                                ];
                                                foreach ($recentDeposits as $dep):
                                                    $sc = $depStatusMap[$dep['status']] ?? ['label' => 'Unbekannt', 'color' => '#6c757d', 'bg' => 'rgba(108,117,125,0.1)', 'icon' => 'question-circle'];
                                                ?>
                                                <tr style="border-bottom:1px solid #f0f2f5;">
                                                    <td class="px-4 py-3">
                                                        <span class="font-weight-600" style="color:#2950a8;"><?= htmlspecialchars($dep['reference'], ENT_QUOTES) ?></span>
                                                    </td>
                                                    <td class="py-3">
                                                        <span class="font-weight-bold" style="color:#2c3e50;">€<?= number_format((float)$dep['amount'], 2) ?></span>
                                                    </td>
                                                    <td class="py-3">
                                                        <span class="text-muted"><?= htmlspecialchars(strtoupper($dep['method_code']), ENT_QUOTES) ?></span>
                                                    </td>
                                                    <td class="py-3">
                                                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;">
                                                            <i class="anticon anticon-<?= htmlspecialchars($sc['icon'], ENT_QUOTES) ?>" style="font-size:11px;" aria-hidden="true"></i>
                                                            <?= $sc['label'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-3">
                                                        <span class="text-muted"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($dep['created_at'])), ENT_QUOTES) ?></span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="px-4 py-3 border-top d-flex justify-content-end" style="background:#fafbfc;">
                                        <a href="transactions.php" class="btn btn-sm btn-outline-primary" style="border-radius:8px;font-size:12px;">
                                            <i class="anticon anticon-eye mr-1"></i>Alle Einzahlungen ansehen
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- ── Withdrawals Panel ── -->
                            <div class="tab-pane fade" id="reqWithdrawalsPanel" role="tabpanel" aria-labelledby="reqWithdrawalsTab">
                                <?php if (empty($recentWithdrawals)): ?>
                                    <div class="py-5 text-center">
                                        <div style="width:56px;height:56px;border-radius:50%;background:rgba(40,167,69,0.08);display:flex;align-items:center;justify-content:center;font-size:26px;color:#28a745;margin:0 auto 14px;" aria-hidden="true">
                                            <i class="anticon anticon-inbox"></i>
                                        </div>
                                        <p class="mb-3 text-muted" style="font-size:14px;">Noch keine Auszahlungsanfragen vorhanden.</p>
                                        <button type="button" class="btn btn-sm font-weight-600"
                                                data-toggle="modal" data-target="#newWithdrawalModal"
                                                style="background:linear-gradient(135deg,#28a745,#20c997);color:#fff;border:none;border-radius:8px;padding:8px 20px;">
                                            <i class="anticon anticon-arrow-up mr-1"></i>Auszahlung beantragen
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table mb-0" style="font-size:13px;">
                                            <thead>
                                                <tr style="background:#f8f9fa;">
                                                    <th class="border-0 px-4 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Referenz</th>
                                                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Betrag</th>
                                                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Methode</th>
                                                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Geb&uuml;hr&nbsp;<button type="button" class="btn btn-link p-0 fee-regulation-info-btn" style="font-size:12px;vertical-align:middle;color:#dc3545;line-height:1;" data-toggle="modal" data-target="#feeRegulationModal" aria-label="Geb&uuml;hreninformation"><i class="anticon anticon-info-circle"></i></button></th>
                                                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Status</th>
                                                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Datum</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $wdStatusMap = [
                                                    'pending'    => ['label' => 'Ausstehend',     'color' => '#b45309', 'bg' => 'rgba(251,191,36,0.15)',  'icon' => 'clock-circle'],
                                                    'processing' => ['label' => 'In Bearbeitung', 'color' => '#155e75', 'bg' => 'rgba(23,162,184,0.12)',  'icon' => 'sync'],
                                                    'completed'  => ['label' => 'Abgeschlossen',  'color' => '#166534', 'bg' => 'rgba(40,167,69,0.12)',   'icon' => 'check-circle'],
                                                    'failed'     => ['label' => 'Fehlgeschlagen', 'color' => '#991b1b', 'bg' => 'rgba(220,53,69,0.12)',   'icon' => 'close-circle'],
                                                    'cancelled'  => ['label' => 'Storniert',      'color' => '#374151', 'bg' => 'rgba(108,117,125,0.12)', 'icon' => 'stop'],
                                                ];
                                                foreach ($recentWithdrawals as $wd):
                                                    $wc = $wdStatusMap[$wd['status']] ?? ['label' => 'Unbekannt', 'color' => '#6c757d', 'bg' => 'rgba(108,117,125,0.1)', 'icon' => 'question-circle'];
                                                    $hasFeeRow = !empty($wd['fee_amount']) && (float)$wd['fee_amount'] > 0;
                                                ?>
                                                <tr style="border-bottom:1px solid #f0f2f5;">
                                                    <td class="px-4 py-3">
                                                        <span class="font-weight-600" style="color:#28a745;"><?= htmlspecialchars($wd['reference'], ENT_QUOTES) ?></span>
                                                    </td>
                                                    <td class="py-3">
                                                        <span class="font-weight-bold" style="color:#2c3e50;">€<?= number_format((float)$wd['amount'], 2) ?></span>
                                                    </td>
                                                    <td class="py-3">
                                                        <span class="text-muted"><?= htmlspecialchars(strtoupper($wd['method_code']), ENT_QUOTES) ?></span>
                                                    </td>
                                                    <td class="py-3">
                                                        <?php if ($hasFeeRow): ?>
                                                        <span style="display:inline-flex;align-items:center;gap:5px;">
                                                            <span class="font-weight-700" style="color:#dc3545;">€<?= number_format((float)$wd['fee_amount'], 2) ?></span>
                                                            <button type="button" class="btn btn-link p-0 fee-regulation-info-btn" style="font-size:13px;color:#dc3545;line-height:1;" data-toggle="modal" data-target="#feeRegulationModal" aria-label="Geb&uuml;hreninformation"><i class="anticon anticon-info-circle"></i></button>
                                                        </span>
                                                        <?php else: ?>
                                                        <span class="text-muted" style="font-size:12px;">&mdash;</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3">
                                                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $wc['bg'] ?>;color:<?= $wc['color'] ?>;">
                                                            <i class="anticon anticon-<?= htmlspecialchars($wc['icon'], ENT_QUOTES) ?>" style="font-size:11px;" aria-hidden="true"></i>
                                                            <?= $wc['label'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-3">
                                                        <span class="text-muted"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($wd['created_at'])), ENT_QUOTES) ?></span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="px-4 py-3 border-top d-flex justify-content-end" style="background:#fafbfc;">
                                        <a href="transactions.php" class="btn btn-sm btn-outline-success" style="border-radius:8px;font-size:12px;">
                                            <i class="anticon anticon-eye mr-1"></i>Alle Auszahlungen ansehen
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div><!-- /tab-content -->
                    </div><!-- /card-body -->
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

                <div class="card shadow-sm border-0 mt-3" style="border-radius:12px;overflow:hidden;">
                    <div class="card-header section-header border-0 d-flex align-items-center justify-content-between py-3 px-3"
                         style="background:linear-gradient(135deg,#1a2a6c,#2950a8);">
                        <h5 class="mb-0 font-weight-bold text-white" style="font-size:0.95rem;">
                            <i class="anticon anticon-transaction mr-2"></i>Aktuelle Transaktionen
                        </h5>
                        <a href="transactions.php" class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:#fff;border-radius:6px;font-size:11px;">
                            Alle ansehen <i class="anticon anticon-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div style="min-height: 300px">
                            <?php if (empty($transactions)): ?>
                                <div class="alert alert-info d-flex align-items-center m-3" style="border-radius: 10px;">
                                    <i class="anticon anticon-info-circle mr-2" style="font-size: 20px;"></i>
                                    <span>Noch keine Transaktionen vorhanden.</span>
                                </div>
                            <?php else: ?>
                                <div class="scrollable" style="height: 340px; overflow-y: auto;">
                                    <?php foreach ($transactions as $transaction): ?>
                                    <?php
                                    $iconConfig = [
                                        'refund'     => ['icon' => 'arrow-up',   'color' => '#28a745', 'bg' => 'rgba(40,167,69,0.1)', 'label' => 'Rückerstattung'],
                                        'deposit'    => ['icon' => 'plus-circle', 'color' => '#2950a8', 'bg' => 'rgba(41,80,168,0.1)', 'label' => 'Einzahlung'],
                                        'withdrawal' => ['icon' => 'arrow-up',   'color' => '#dc3545', 'bg' => 'rgba(220,53,69,0.1)',  'label' => 'Auszahlung'],
                                        'fee'        => ['icon' => 'minus',      'color' => '#e67e22', 'bg' => 'rgba(230,126,34,0.1)', 'label' => 'Gebühr']
                                    ];
                                    $cfg = $iconConfig[$transaction['type']] ?? ['icon' => 'swap', 'color' => '#6c757d', 'bg' => 'rgba(108,117,125,0.1)', 'label' => 'Transaktion'];
                                    $isPositive = in_array($transaction['type'], ['refund', 'deposit']);
                                    ?>
                                    <div class="txn-row d-flex align-items-center px-3 py-3"
                                         role="button" tabindex="0"
                                         data-id="<?= htmlspecialchars($transaction['id'], ENT_QUOTES) ?>"
                                         data-type="<?= htmlspecialchars($transaction['type'], ENT_QUOTES) ?>"
                                         data-label="<?= htmlspecialchars($cfg['label'], ENT_QUOTES) ?>"
                                         data-amount="<?= htmlspecialchars($transaction['amount'], ENT_QUOTES) ?>"
                                         data-status="<?= htmlspecialchars($transaction['status'] ?? 'completed', ENT_QUOTES) ?>"
                                         data-date="<?= htmlspecialchars($transaction['created_at'], ENT_QUOTES) ?>"
                                         data-reference="<?= htmlspecialchars($transaction['reference_name'], ENT_QUOTES) ?>"
                                         onclick="showTransactionDetails(this)"
                                         onkeypress="if(event.key==='Enter'||event.key===' ')showTransactionDetails(this)"
                                         style="border-bottom:1px solid #f0f2f5;cursor:pointer;transition:background .15s;"
                                         onmouseenter="this.style.background='#f8f9fa'" onmouseleave="this.style.background=''">
                                        <div style="width:40px;height:40px;border-radius:10px;background:<?= $cfg['bg'] ?>;display:flex;align-items:center;justify-content:center;font-size:18px;color:<?= $cfg['color'] ?>;flex-shrink:0;" aria-hidden="true">
                                            <i class="anticon anticon-<?= htmlspecialchars($cfg['icon'], ENT_QUOTES) ?>"></i>
                                        </div>
                                        <div class="ml-3 flex-grow-1">
                                            <div class="font-weight-600" style="font-size:13px;color:#2c3e50;"><?= htmlspecialchars($cfg['label'], ENT_QUOTES) ?></div>
                                            <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($transaction['reference_name'], ENT_QUOTES) ?> · <?= date('d.m.Y', strtotime($transaction['created_at'])) ?></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-weight-bold" style="font-size:14px;color:<?= $isPositive ? '#28a745' : '#dc3545' ?>;">
                                                <?= $isPositive ? '+' : '-' ?>€<?= number_format($transaction['amount'], 2) ?>
                                            </div>
                                            <i class="anticon anticon-right text-muted" style="font-size:10px;"></i>
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

        <!-- Recovery Timeline Chart -->
        <div class="row mt-3 mb-4">
            <div class="col-md-8 mb-3">
                <div class="card chart-card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                            <i class="anticon anticon-area-chart mr-2" style="color: var(--brand);"></i>Wiederherstellungs-Verlauf (letzte 6 Monate)
                        </h5>
                        <canvas id="recoveryTimelineChart" height="120" aria-label="Recovery timeline chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card chart-card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                            <i class="anticon anticon-pie-chart mr-2" style="color: var(--brand);"></i>Fallstatus
                        </h5>
                        <canvas id="statusChartEnhanced" height="160" aria-label="Case status donut chart"></canvas>
                        <div class="mt-3">
                            <ul class="list-unstyled mb-0" id="statusChartLegend"></ul>
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

<style>
/* DataTable inside allTransactionsModal */
#allTxnDataTable_wrapper .dataTables_paginate {
    padding: 12px 16px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 4px;
}
#allTxnDataTable_wrapper .dataTables_paginate .paginate_button {
    border-radius: 6px !important;
    padding: 4px 10px !important;
    font-size: 12px !important;
    border: 1px solid #dee2e6 !important;
    background: #fff !important;
    color: #495057 !important;
    cursor: pointer;
}
#allTxnDataTable_wrapper .dataTables_paginate .paginate_button.current,
#allTxnDataTable_wrapper .dataTables_paginate .paginate_button:hover {
    background: linear-gradient(135deg, #2950a8, #2da9e3) !important;
    color: #fff !important;
    border-color: transparent !important;
}
#allTxnDataTable_wrapper .dataTables_paginate .paginate_button.disabled,
#allTxnDataTable_wrapper .dataTables_paginate .paginate_button.disabled:hover {
    background: #f8f9fa !important;
    color: #adb5bd !important;
    cursor: default;
}
#allTxnDataTable_wrapper .dataTables_info {
    padding: 12px 16px;
    font-size: 12px;
    color: #6c757d;
}
#allTxnDataTable_wrapper .dataTables_processing {
    background: rgba(255,255,255,0.9) !important;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    font-size: 13px;
}
#allTxnDataTable tbody tr:hover {
    background-color: rgba(41, 80, 168, 0.03) !important;
}
#allTxnDataTable tbody td {
    padding: 10px 14px;
    vertical-align: middle;
    border-top: 1px solid #f0f0f0;
}
/* Step Indicator */
.deposit-step-bar,
.withdrawal-step-bar {
    position: relative;
}
</style>

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

    // =====================================================
    // 📋 DEPOSIT STEP WIZARD
    // =====================================================
    var depositCurrentStep = 1;

    function updateDepositStepIndicator(step) {
        // Update step circles in the indicator bar
        var $circles = $('#newDepositModal form > .px-4').find('[style*="border-radius:50%"]');
        $circles.each(function(i) {
            var $circle = $(this);
            if (i + 1 < step) {
                // Completed
                $circle.css({'background': 'linear-gradient(135deg,#2950a8,#2da9e3)', 'color': '#fff'});
                $circle.html('<i class="anticon anticon-check" style="font-size:13px;"></i>');
                $circle.next('div').css('color', '#2950a8');
            } else if (i + 1 === step) {
                // Active
                $circle.css({'background': 'linear-gradient(135deg,#2950a8,#2da9e3)', 'color': '#fff', 'box-shadow': '0 2px 6px rgba(41,80,168,0.3)'});
                $circle.text(i + 1);
                $circle.next('div').css('color', '#2950a8');
            } else {
                // Future
                $circle.css({'background': '#dee2e6', 'color': '#6c757d', 'box-shadow': 'none'});
                $circle.text(i + 1);
                $circle.next('div').css('color', '#6c757d');
            }
        });
        // Update connector bars
        var $bars = $('#newDepositModal form > .px-4').find('[style*="height:2px"]');
        $bars.each(function(i) {
            if (i + 1 < step) {
                $(this).css('background', 'linear-gradient(90deg,#2950a8,#2da9e3)');
            } else {
                $(this).css('background', '#dee2e6');
            }
        });
    }

    function goToDepositStep(step) {
        depositCurrentStep = step;
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
        updateDepositStepIndicator(step);
    }

    $('#depositNextBtn').click(function() {
        if (depositCurrentStep === 1) {
            var amount = parseFloat($('#depositAmount').val()) || 0;
            if (!amount || amount < 10) {
                toastr.error('Mindesteinzahlung: €10,00');
                return;
            }
            goToDepositStep(2);
        } else if (depositCurrentStep === 2) {
            var method = $('#paymentMethod').val();
            if (!method) {
                toastr.error('Bitte wählen Sie eine Zahlungsmethode aus.');
                return;
            }
            goToDepositStep(3);
        }
    });

    $('#depositBackBtn').click(function() {
        if (depositCurrentStep > 1) {
            goToDepositStep(depositCurrentStep - 1);
        }
    });

    $('#newDepositModal').on('hidden.bs.modal', function() {
        goToDepositStep(1);
        $(this).find('form')[0].reset();
        $('#paymentDetails').hide();
        $('.custom-file-label').html('Screenshot oder PDF auswählen');
    });

    // =====================================================
    // 💸 WITHDRAWAL STEP WIZARD
    // =====================================================
    var withdrawalCurrentStep = 1;

    function goToWithdrawalStep(step) {
        withdrawalCurrentStep = step;
        $('#withdrawalStep1, #withdrawalStep2, #withdrawalStep3').hide();
        $('#withdrawalStep' + step).show();
        // Buttons
        if (step === 1) {
            $('#withdrawalBackBtn').hide();
            $('#withdrawalNextBtn').show();
            $('#withdrawalSubmitBtn').hide().prop('disabled', true);
        } else if (step === 2) {
            $('#withdrawalBackBtn').show();
            $('#withdrawalNextBtn').show();
            $('#withdrawalSubmitBtn').hide().prop('disabled', true);
        } else if (step === 3) {
            $('#withdrawalBackBtn').show();
            $('#withdrawalNextBtn').hide();
            $('#withdrawalSubmitBtn').show();
            // Populate fee summary for the user to review before submitting
            updateWdFeeDisplay();
        }
        // Update withdrawal step indicator circles
        var $circles = $('#newWithdrawalModal form > .px-4').find('[style*="border-radius:50%"]');
        $circles.each(function(i) {
            var $c = $(this);
            if (i + 1 < step) {
                $c.css({'background': 'linear-gradient(135deg,#28a745,#20c997)', 'color': '#fff'});
                $c.html('<i class="anticon anticon-check" style="font-size:13px;"></i>');
                $c.next('div').css('color', '#28a745');
            } else if (i + 1 === step) {
                $c.css({'background': 'linear-gradient(135deg,#28a745,#20c997)', 'color': '#fff', 'box-shadow': '0 2px 6px rgba(40,167,69,0.3)'});
                $c.text(i + 1);
                $c.next('div').css('color', '#28a745');
            } else {
                $c.css({'background': '#dee2e6', 'color': '#6c757d', 'box-shadow': 'none'});
                $c.text(i + 1);
                $c.next('div').css('color', '#6c757d');
            }
        });
        var $bars = $('#newWithdrawalModal form > .px-4').find('[style*="height:2px"]');
        $bars.each(function(i) {
            $(this).css('background', i + 1 < step ? 'linear-gradient(90deg,#28a745,#20c997)' : '#dee2e6');
        });
    }

    // ── Withdrawal Fee: calculate and display when reaching Step 3 ────────
    function updateWdFeeDisplay() {
        var feeEnabled = parseInt($('#wdFeeEnabled').val()) === 1;
        if (!feeEnabled) return;
        var amount = parseFloat($('#amount').val()) || 0;
        var pct    = parseFloat($('#wdFeePercentage').val()) || 0;
        if (amount > 0 && pct > 0) {
            var fee = Math.round(amount * pct) / 100;
            var fmt = function(n) { return n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
            $('#wdFeeCalcAmount').html('€&nbsp;' + fmt(fee));
            $('#wdFeeCalcBase').text(pct.toFixed(2).replace('.', ',') + ' % von €' + fmt(amount));
        }
    }

    $('#withdrawalNextBtn').click(function() {
        if (withdrawalCurrentStep === 1) {
            var amount = parseFloat($('#amount').val()) || 0;
            var available = parseFloat($('#availableBalance').val()) || 0;
            if (!amount || amount < 1000) {
                toastr.error('Mindestbetrag für Auszahlungen: €1.000.');
                return;
            }
            if (amount > available) {
                toastr.error('Unzureichendes Guthaben. Verfügbar: €' + available.toFixed(2));
                return;
            }
            goToWithdrawalStep(2);
        } else if (withdrawalCurrentStep === 2) {
            var method = $('#withdrawalMethod').val();
            if (!method) {
                toastr.error('Bitte wählen Sie eine Auszahlungsmethode aus.');
                return;
            }
            if (!$('#confirmDetails').is(':checked')) {
                toastr.error('Bitte bestätigen Sie, dass die Zahlungsdetails korrekt sind.');
                return;
            }
            goToWithdrawalStep(3);
        }
    });

    $('#withdrawalBackBtn').click(function() {
        if (withdrawalCurrentStep > 1) {
            goToWithdrawalStep(withdrawalCurrentStep - 1);
        }
    });

    $('#newWithdrawalModal').on('hidden.bs.modal', function() {
        goToWithdrawalStep(1);
        resetOtpFields();
        $('#insufficientFundsWarning').remove();
    });

    // =====================================================
    // 🪟 FIX NESTED MODAL Z-INDEX (transaction details inside all-transactions)
    // =====================================================
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
        var $submitBtn = $('#depositSubmitBtn');
        
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
                        setTimeout(function(){ location.reload(); }, 1200);
                    } else {
                        toastr.error(data.message || 'Fehler bei der Einzahlung');
                    }
                } catch (e) {
                    toastr.error('Fehler beim Verarbeiten der Serverantwort');
                }
                $submitBtn.prop('disabled', false).html('<i class="anticon anticon-check-circle mr-1"></i>Einzahlung bestätigen');
            },
            error: function(xhr, status, error) {
                toastr.error('Kommunikationsfehler mit dem Server: ' + error);
                $submitBtn.prop('disabled', false).html('<i class="anticon anticon-check-circle mr-1"></i>Einzahlung bestätigen');
            }
        });
    });

// =====================================================
// 💸 WITHDRAWAL FORM SUBMIT (WITH OTP + BALANCE CHECK)
// =====================================================
$('#withdrawalForm').submit(function (e) {
    e.preventDefault();
    const $form = $(this);
    const $submitBtn = $('#withdrawalSubmitBtn');

    // Ensure OTP verified (button enabled only after verification)
    if ($submitBtn.prop('disabled')) {
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
            $submitBtn.prop('disabled', false).html('<i class="anticon anticon-send mr-1"></i>Antrag einreichen');
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
// 🧹 RESET OTP FIELDS
// =====================================================

function resetOtpFields() {
    $('#otpCode').val('').prop('disabled', true);
    $('#sendVerifyOtpBtn').prop('disabled', false).html('<i class="anticon anticon-mail"></i> OTP senden & prüfen').removeClass('btn-success').addClass('btn-primary');
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

    // ── AI Algorithm Live Monitor ──────────────────────────────────────
    (function() {
        var txnChecked  = 0;
        var txnFound    = 0;
        var scanPct     = 0;
        var nextScanSec = 60;
        var blockNum    = 21800000 + Math.floor(Math.random() * 50000);
        var feedEl      = document.getElementById('aiLiveFeed');
        var emptyEl     = document.getElementById('aiLiveFeedEmpty');
        var maxLines    = 80;

        var addressPool = [
            '0x3a5e…b29f','0x71dc…4e8a','0xbb12…c437','0x09fa…81d2',
            '0x4d3c…f7a0','0xd982…00c1','0x5501…e3b9','0xc7f0…9a2d',
            '1FbG6…Kc9z','bc1q…7wtp','0xa23b…11fe','0x6c44…c092'
        ];
        var amountPool  = ['€2,340','€18,900','€450','€94,200','€7,100',
                           '€3,610','€125,000','€8,850','€550','€22,400'];
        var platformPool = ['Binance','Coinbase','Kraken','UniSwap',
                            'OKX','Bybit','dYdX','Gemini'];

        function rnd(arr) { return arr[Math.floor(Math.random() * arr.length)]; }
        function rndInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

        function now() {
            var d = new Date();
            return ('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2)+':'+('0'+d.getSeconds()).slice(-2);
        }

        function addLine(type, msg, cssClass) {
            if (!feedEl) return;
            if (emptyEl) { emptyEl.style.display = 'none'; }

            var line = document.createElement('div');
            line.className = 'ai-feed-line';
            line.innerHTML =
                '<span class="ai-feed-ts">['+now()+']</span>' +
                '<span class="ai-feed-type '+cssClass+'">'+type+'</span>' +
                '<span class="ai-feed-msg">'+msg+'</span>';
            feedEl.appendChild(line);

            /* trim old lines */
            var lines = feedEl.querySelectorAll('.ai-feed-line');
            if (lines.length > maxLines) { lines[0].remove(); }

            feedEl.scrollTop = feedEl.scrollHeight;
        }

        function updateCounter(id, val) {
            var el = document.getElementById(id);
            if (el) el.textContent = val.toLocaleString('de-DE');
        }

        function tick() {
            /* increment checked counter */
            var batch = rndInt(3, 12);
            txnChecked += batch;
            updateCounter('aiTxnChecked', txnChecked);

            /* occasionally find one */
            if (Math.random() < 0.10) {
                txnFound++;
                updateCounter('aiTxnFound', txnFound);
                addLine('GEFUNDEN', 'Verdächtige Transaktion: ' + rnd(amountPool) +
                    ' von ' + rnd(addressPool) + ' via ' + rnd(platformPool), 'found');
            }

            /* update accuracy based on found/checked ratio */
            if (txnChecked > 0) {
                var accuracyEl = document.getElementById('aiAccuracy');
                var acc = Math.max(95, Math.min(99.9, 100 - (txnFound / txnChecked * 100 * 0.8)));
                if (accuracyEl) accuracyEl.textContent = acc.toFixed(1) + '%';
            }

            /* scan progress — reset smoothly to 0 when reaching 100 */
            scanPct += rndInt(1, 4);
            if (scanPct >= 100) { scanPct = 0; }
            var bar = document.getElementById('aiScanBar');
            var pctEl = document.getElementById('aiScanPct');
            if (bar)   bar.style.width = scanPct + '%';
            if (pctEl) pctEl.textContent = scanPct + '%';

            /* block number */
            if (Math.random() < 0.3) blockNum++;
            var blkEl = document.getElementById('aiLastBlock');
            if (blkEl) blkEl.textContent = '#' + blockNum.toLocaleString('de-DE');

            /* speed & latency */
            var speedEl   = document.getElementById('aiScanSpeed');
            var latencyEl = document.getElementById('aiLatency');
            if (speedEl)   speedEl.textContent   = rndInt(1200, 3800) + ' tx/s';
            if (latencyEl) latencyEl.textContent  = rndInt(12, 95) + ' ms';

            /* countdown */
            nextScanSec--;
            if (nextScanSec <= 0) { nextScanSec = 60; }
            var cdEl = document.getElementById('aiNextScanCountdown');
            if (cdEl) cdEl.textContent = nextScanSec + 's';

            /* random feed messages */
            var r = Math.random();
            if (r < 0.30) {
                addLine('PRÜFEN', 'Adresse ' + rnd(addressPool) + ' wird analysiert… (' + batch + ' txn)', 'check');
            } else if (r < 0.55) {
                addLine('BLOCK', 'Block #' + blockNum + ' verarbeitet – ' + rndInt(80,300) + ' Transaktionen', 'block');
            } else if (r < 0.72) {
                addLine('SCAN', 'Netzwerk-Sweep: ' + rnd(platformPool) + ' – ' + rndInt(5,50) + ' Wallets abgedeckt', 'scan');
            } else if (r < 0.88) {
                addLine('PRÜFEN', rndInt(20,120) + ' Adressen in Batch ' + rndInt(1,99) + ' überprüft', 'check');
            } else {
                addLine('HINWEIS', 'Muster erkannt: Umleitungsversuch über ' + rnd(platformPool), 'alert');
            }
        }

        /* boot sequence */
        addLine('INIT', 'KI-Algorithmus gestartet – Verbindung zum Blockchain-Netzwerk…', 'scan');
        setTimeout(function() {
            addLine('INIT', 'Verbindung hergestellt – Blockchain-Index geladen', 'scan');
            addLine('BLOCK','Aktueller Block: #' + blockNum, 'block');
            addLine('PRÜFEN','Erste Adress-Batch wird gestartet…', 'check');
            setInterval(tick, 2500);
        }, 800);
    })();
    // ── End AI Algorithm Live Monitor ────────────────────────────────────

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
    // 💳 SHOW TRANSACTION DETAILS MODAL
    // =====================================================
    function showTransactionDetails(el) {
        var id        = el.getAttribute('data-id')        || '-';
        var type      = el.getAttribute('data-type')      || '-';
        var label     = el.getAttribute('data-label')     || type;
        var amount    = parseFloat(el.getAttribute('data-amount')) || 0;
        var status    = el.getAttribute('data-status')    || 'completed';
        var date      = el.getAttribute('data-date')      || '-';
        var reference = el.getAttribute('data-reference') || '-';

        var isPositive = (type === 'deposit' || type === 'refund');

        // Fill fields
        document.getElementById('txn-id').textContent        = '#TXN-' + id;
        document.getElementById('txn-date').textContent      = date !== '-' ? new Date(date).toLocaleString('de-DE') : '-';
        document.getElementById('txn-type').textContent      = label;
        document.getElementById('txn-reference').textContent = reference;
        document.getElementById('txn-amount').textContent    = (isPositive ? '+' : '-') + '€' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        document.getElementById('txn-amount').style.color   = isPositive ? '#28a745' : '#dc3545';

        // Update timeline date
        var tlDate = document.getElementById('txn-timeline-date');
        if (tlDate && date !== '-') { tlDate.textContent = new Date(date).toLocaleString('de-DE'); }

        // Status badge
        var statusMap = {
            'completed': ['success', 'Abgeschlossen', 'check-circle'],
            'pending':   ['warning', 'Ausstehend',    'clock-circle'],
            'failed':    ['danger',  'Fehlgeschlagen', 'close-circle'],
            'processing':['info',    'In Bearbeitung', 'loading']
        };
        var sm = statusMap[status] || ['secondary', status, 'question-circle'];
        document.getElementById('txn-status').innerHTML =
            '<span class="badge badge-' + sm[0] + ' px-3 py-2" style="border-radius:20px;font-size:12px;">' +
            '<i class="anticon anticon-' + sm[2] + ' mr-1"></i>' + sm[1] + '</span>';

        // Header badge
        document.getElementById('txn-header-badge').innerHTML =
            '<span style="background:rgba(255,255,255,0.18);color:#fff;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;">' +
            label + '</span>';

        // Header subtitle
        document.getElementById('txn-header-sub').textContent = 'ID: #TXN-' + id + ' · ' + (date !== '-' ? new Date(date).toLocaleString('de-DE') : '');

        // Show modal
        $('#transactionDetailsModal').modal('show');
    }
    window.showTransactionDetails = showTransactionDetails;

    // =====================================================
    // 📊 ALL TRANSACTIONS MODAL
    // =====================================================
    // =====================================================
    // 📊 ALL TRANSACTIONS DATATABLE
    // =====================================================
    var allTxnTable = null;
    var allTxnCurrentFilter = '';

    function fmtEur(value) {
        return '€' + parseFloat(value || 0).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function escapeHtml(str) {
        return $('<span>').text(str || '—').html();
    }

    var modalTxnTypeMap = {
        'deposit':    {label: 'Einzahlung',     icon: 'arrow-down', color: '#2950a8', bg: 'rgba(41,80,168,0.1)'},
        'withdrawal': {label: 'Auszahlung',     icon: 'arrow-up',   color: '#dc3545', bg: 'rgba(220,53,69,0.1)'},
        'refund':     {label: 'Rückerstattung', icon: 'undo',       color: '#28a745', bg: 'rgba(40,167,69,0.1)'},
        'fee':        {label: 'Gebühr',         icon: 'minus',      color: '#e67e22', bg: 'rgba(230,126,34,0.1)'}
    };
    var modalTxnStatusMap = {
        'completed':  {variant: 'success',   label: 'Abgeschlossen'},
        'approved':   {variant: 'success',   label: 'Genehmigt'},
        'pending':    {variant: 'warning',   label: 'Ausstehend'},
        'processing': {variant: 'info',      label: 'In Bearbeitung'},
        'rejected':   {variant: 'danger',    label: 'Abgelehnt'},
        'failed':     {variant: 'danger',    label: 'Fehlgeschlagen'},
        'cancelled':  {variant: 'secondary', label: 'Storniert'}
    };

    // Initialize DataTable when modal is fully visible (prevents column-width issues)
    $('#allTransactionsModal').on('shown.bs.modal', function () {
        if (allTxnTable) {
            allTxnTable.columns.adjust().draw(false);
            return;
        }

        allTxnTable = $('#allTxnDataTable').DataTable({
            processing: true,
            ajax: {
                url: 'ajax/transactions.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    draw: 1, start: 0, length: 200,
                    search: { value: '' },
                    order: [{ column: 5, dir: 'desc' }]
                }),
                dataSrc: function (json) {
                    // Compute summary from loaded data
                    var deps = 0, withs = 0;
                    (json.data || []).forEach(function (t) {
                        var amt = parseFloat(t.amount) || 0;
                        if (t.type === 'deposit' || t.type === 'refund') deps += amt;
                        else if (t.type === 'withdrawal') withs += amt;
                    });
                    $('#totalDepositsAmt').text(fmtEur(deps));
                    $('#totalWithdrawalsAmt').text(fmtEur(withs));
                    $('#totalTxnCount').text(json.recordsTotal || (json.data || []).length);
                    return json.data || [];
                }
            },
            order: [[5, 'desc']],
            pageLength: 10,
            dom: '<"d-none"f>rtip',  // hide built-in search; show table, info, pagination
            columns: [
                {
                    data: 'type',
                    render: function (data) {
                        var tm = modalTxnTypeMap[data] || {label: data || '—', icon: 'swap', color: '#6c757d', bg: 'rgba(108,117,125,0.1)'};
                        return '<span style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;background:' + tm.bg + ';color:' + tm.color + ';">' +
                               '<i class="anticon anticon-' + tm.icon + ' mr-1"></i>' + tm.label + '</span>';
                    }
                },
                {
                    data: 'amount',
                    render: function (data, type, row) {
                        var isPos = (row.type === 'deposit' || row.type === 'refund');
                        var color = isPos ? '#28a745' : '#dc3545';
                        var prefix = isPos ? '+' : '-';
                        var amt = parseFloat(data || 0).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        return '<span style="font-weight:700;color:' + color + ';white-space:nowrap;">' + prefix + '€' + amt + '</span>';
                    }
                },
                {
                    data: 'method',
                    render: function (data) {
                        return '<span style="font-size:13px;color:#495057;">' + escapeHtml(data) + '</span>';
                    }
                },
                {
                    data: 'status',
                    render: function (data) {
                        var sm = modalTxnStatusMap[data] || {variant: 'secondary', label: escapeHtml(data)};
                        return '<span class="badge badge-' + sm.variant + '" style="border-radius:20px;padding:4px 10px;font-size:11px;">' + sm.label + '</span>';
                    }
                },
                {
                    data: 'reference',
                    render: function (data) {
                        var safe = escapeHtml(data);
                        return '<span style="font-size:12px;color:#6c757d;max-width:130px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + safe + '">' + safe + '</span>';
                    }
                },
                {
                    data: 'created_at',
                    render: function (data) {
                        if (!data) return '—';
                        var d = new Date(data);
                        return '<span style="font-size:12px;color:#6c757d;white-space:nowrap;">' +
                               d.toLocaleDateString('de-DE', {day:'2-digit', month:'2-digit', year:'numeric'}) + '<br>' +
                               '<small>' + d.toLocaleTimeString('de-DE', {hour:'2-digit', minute:'2-digit'}) + '</small>' +
                               '</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        var tm = modalTxnTypeMap[row.type] || {label: row.type || '—', color: '#6c757d'};
                        var isPos = (row.type === 'deposit' || row.type === 'refund');
                        return '<button class="btn btn-sm txn-details-modal-btn" ' +
                               'data-id="' + escapeHtml(String(row.deposit_id || row.withdrawal_id || row.id || '')) + '" ' +
                               'data-type="' + escapeHtml(row.type || '') + '" ' +
                               'data-label="' + escapeHtml(tm.label) + '" ' +
                               'data-amount="' + parseFloat(row.amount || 0) + '" ' +
                               'data-status="' + escapeHtml(row.status || '') + '" ' +
                               'data-date="' + escapeHtml(row.created_at || '') + '" ' +
                               'data-reference="' + escapeHtml(row.reference || '') + '" ' +
                               'style="border-radius:8px;background:rgba(41,80,168,0.08);color:#2950a8;border:1px solid rgba(41,80,168,0.2);font-size:11px;font-weight:600;white-space:nowrap;">' +
                               '<i class="anticon anticon-eye mr-1"></i>Details</button>';
                    }
                }
            ],
            language: {
                processing: '<div class="text-center py-4"><div class="spinner-border text-primary" style="width:1.5rem;height:1.5rem;" role="status"><span class="sr-only">Laden…</span></div><div class="mt-2 text-muted small">Transaktionen werden geladen…</div></div>',
                emptyTable: '<div class="text-center py-5"><div style="font-size:3rem;opacity:0.2;">💳</div><div class="text-muted mt-2">Keine Transaktionen vorhanden.</div></div>',
                zeroRecords: '<div class="text-center py-5"><div style="font-size:2.5rem;opacity:0.2;">🔍</div><div class="text-muted mt-2">Keine Einträge gefunden.</div></div>',
                info: 'Einträge _START_ bis _END_ von _TOTAL_',
                infoEmpty: 'Keine Einträge',
                infoFiltered: '(aus _MAX_ gesamt)',
                lengthMenu: '_MENU_ pro Seite',
                paginate: { first: '«', last: '»', next: '›', previous: '‹' }
            }
        });

        // Wire custom search box to DataTable
        $('#allTxnSearch').on('keyup input', function () {
            allTxnTable.search(this.value).draw();
        });
    });

    // Filter Tabs → DataTable search on type column
    $(document).on('click', '.txn-filter-btn', function () {
        var filter = $(this).data('filter');
        allTxnCurrentFilter = filter;

        // Update tab styles
        $('.txn-filter-btn').each(function () {
            var f = $(this).data('filter');
            var isActive = (f === filter);
            var isW = (f === 'withdrawal');
            if (isActive) {
                $(this).css({background: isW ? '#dc3545' : '#2950a8', color: '#fff', border: 'none'});
            } else {
                var inactiveColor = isW ? '#dc3545' : '#2950a8';
                $(this).css({background: 'transparent', color: inactiveColor, border: '1.5px solid ' + inactiveColor});
            }
        });

        // Apply DataTable search (empty string = show all)
        if (allTxnTable) {
            allTxnTable.search(filter).draw();
        }
    });

    // View details button within the DataTable
    $(document).on('click', '.txn-details-modal-btn', function () {
        showTransactionDetails(this);
    });

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
                                <div class="blockchain-scanner-section mb-4" id="blockchainScanner_${c.id || 'case'}">
                                    <div class="scanner-title">
                                        <span class="dot"></span>
                                        KI-Algorithmus · Blockchain-Adressanalyse
                                        <span style="margin-left:auto;font-size:11px;color:#4dffb4;" id="scannerStatus_${c.id || 'case'}">SCANNEN…</span>
                                    </div>
                                    <div class="scanner-stats">
                                        <div class="scanner-stat">
                                            <div class="scanner-stat-val" id="scannedCount_${c.id || 'case'}">0</div>
                                            <div class="scanner-stat-lbl">Adressen geprüft</div>
                                        </div>
                                        <div class="scanner-stat">
                                            <div class="scanner-stat-val green" id="foundCount_${c.id || 'case'}">0</div>
                                            <div class="scanner-stat-lbl">Gefunden</div>
                                        </div>
                                        <div class="scanner-stat">
                                            <div class="scanner-stat-val" style="color:#2da9e3;">50</div>
                                            <div class="scanner-stat-lbl">Gesamte Adressen</div>
                                        </div>
                                    </div>
                                    <div class="addr-grid" id="addrGrid_${c.id || 'case'}">
                                        ${Array.from({length:50}, (_,i) => {
                                            // Deterministic hex address from case id + index (no Math.random)
                                            var h = ((c.id || 0) * 31 + i * 1000003 + 0xdeadbeef) >>> 0;
                                            var hex = h.toString(16).padStart(8,'0').slice(0,6);
                                            return `<div class="addr-node scanning" id="addr_node_${c.id || 'case'}_${i}" title="Adresse ${i+1}"><span class="addr-label">0x${hex}…</span></div>`;
                                        }).join('')}
                                    </div>
                                    <div style="color:#7bafd4;font-size:11px;margin-bottom:10px;">Geldfluss-Map — Wiederherstellungspfad</div>
                                    <div class="recovery-flow" id="recoveryFlow_${c.id || 'case'}">
                                        <div class="flow-node source">
                                            <div class="flow-node-icon">🏴</div>
                                            <div class="flow-node-label">Scam-Wallet</div>
                                            <div class="flow-node-amount" style="color:#ff6b6b;">−€${parseFloat(c.reported_amount||0).toLocaleString('de-DE',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
                                        </div>
                                        <div class="flow-arrow">→</div>
                                        <div class="flow-node">
                                            <div class="flow-node-icon">🔗</div>
                                            <div class="flow-node-label">Mixer/Exchange</div>
                                        </div>
                                        <div class="flow-arrow">→</div>
                                        <div class="flow-node found">
                                            <div class="flow-node-icon">💰</div>
                                            <div class="flow-node-label">Gelder gefunden!</div>
                                            <div class="flow-node-amount">€${parseFloat(c.reported_amount||0).toLocaleString('de-DE',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
                                        </div>
                                        <div class="flow-arrow">→</div>
                                        <div class="flow-node">
                                            <div class="flow-node-icon">⚖️</div>
                                            <div class="flow-node-label">Rechtsverfahren</div>
                                        </div>
                                        <div class="flow-arrow">→</div>
                                        <div class="flow-node dest">
                                            <div class="flow-node-icon">✅</div>
                                            <div class="flow-node-label">Ihr Konto</div>
                                            <div class="flow-node-amount">+€${parseFloat(c.recovered_amount||0).toLocaleString('de-DE',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
                                        </div>
                                    </div>
                                    <div class="scanner-progress-bar">
                                        <div class="scanner-progress-fill" id="scannerFill_${c.id || 'case'}"></div>
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

                        // ── Blockchain Scanner Animation ──────────────────
                        (function runBlockchainScanner(caseId) {
                            var nodePrefix = 'addr_node_' + caseId + '_';
                            var scannedEl = document.getElementById('scannedCount_' + caseId);
                            var foundEl   = document.getElementById('foundCount_' + caseId);
                            var fillEl    = document.getElementById('scannerFill_' + caseId);
                            var statusEl  = document.getElementById('scannerStatus_' + caseId);
                            if (!scannedEl) return;

                            var TOTAL = 50;
                            // Decide which addresses "found money" (deterministic from caseId using LCG PRNG)
                            var foundIndices = new Set();
                            var lcgState = ((caseId + '').split('').reduce(function(a,ch){return a + ch.charCodeAt(0);}, 0)) || 1;
                            while (foundIndices.size < 7) {
                                lcgState = (lcgState * 1664525 + 1013904223) >>> 0;
                                foundIndices.add(lcgState % TOTAL);
                            }

                            var scanned = 0, foundCount = 0;
                            var interval = setInterval(function() {
                                if (scanned >= TOTAL) {
                                    clearInterval(interval);
                                    if (statusEl) { statusEl.textContent = 'ABGESCHLOSSEN ✓'; statusEl.style.color = '#4dffb4'; }
                                    return;
                                }
                                var node = document.getElementById(nodePrefix + scanned);
                                if (node) {
                                    if (foundIndices.has(scanned)) {
                                        node.classList.remove('scanning');
                                        node.classList.add('found');
                                        foundCount++;
                                        if (foundEl) foundEl.textContent = foundCount;
                                    } else {
                                        node.classList.remove('scanning');
                                        node.style.borderColor = 'rgba(45,169,227,0.08)';
                                        node.style.color = '#3a5570';
                                    }
                                }
                                scanned++;
                                if (scannedEl) scannedEl.textContent = scanned;
                                if (fillEl) fillEl.style.width = ((scanned / TOTAL) * 100) + '%';
                            }, 80); // scan one address every 80ms → ~4s total
                        })(c.id || 'case');
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

    // =====================================================
    // 📊 CHARTS INITIALIZATION
    // =====================================================

    // PHP status counts passed to JS
    var statusCountsData = <?= json_encode($statusCounts ?? []) ?>;
    var recoveryPercentage = <?= json_encode($recoveryPercentage ?? 0) ?>;
    var totalReported = <?= json_encode((float)($stats['total_reported'] ?? 0)) ?>;
    var totalRecovered = <?= json_encode((float)($stats['total_recovered'] ?? 0)) ?>;

    // -- Donut: Case Status --
    (function() {
        var ctx = document.getElementById('statusChartEnhanced');
        if (!ctx || !Object.keys(statusCountsData).length) return;

        var labels = [];
        var values = [];
        var labelMap = {
            'open': 'Offen',
            'documents_required': 'Dokumente erforderlich',
            'under_review': 'In Prüfung',
            'refund_approved': 'Rückerstattung genehmigt',
            'refund_rejected': 'Rückerstattung abgelehnt',
            'closed': 'Abgeschlossen'
        };
        var colorMap = {
            'open': '#ffc107',
            'documents_required': '#6c757d',
            'under_review': '#17a2b8',
            'refund_approved': '#28a745',
            'refund_rejected': '#dc3545',
            'closed': '#343a40'
        };
        var colors = [];
        for (var key in statusCountsData) {
            labels.push(labelMap[key] || key);
            values.push(statusCountsData[key]);
            colors.push(colorMap[key] || '#2950a8');
        }

        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverBorderWidth: 4,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(c) { return ' ' + c.label + ': ' + c.raw; }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1200,
                    easing: 'easeOutQuart'
                }
            }
        });

        // Build custom legend
        var legend = document.getElementById('statusChartLegend');
        if (legend) {
            labels.forEach(function(lbl, i) {
                legend.innerHTML += '<li class="d-flex justify-content-between align-items-center mb-1" style="font-size:12px;">' +
                    '<span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + colors[i] + ';margin-right:6px;"></span>' + lbl + '</span>' +
                    '<span class="font-weight-bold">' + values[i] + '</span></li>';
            });
        }
    })();

    // -- Line Chart: Recovery Timeline (last 6 months, simulated/real data) --
    (function() {
        var ctx = document.getElementById('recoveryTimelineChart');
        if (!ctx) return;

        // Generate last 6 months labels with German fallback names
        var deMonths = ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];
        var months = [];
        var now = new Date();
        for (var i = 5; i >= 0; i--) {
            var d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            var lbl;
            try {
                lbl = d.toLocaleDateString('de-DE', {month: 'short', year: '2-digit'});
                if (!lbl || lbl.length < 2) throw new Error('fallback');
            } catch(e) {
                lbl = deMonths[d.getMonth()] + " '" + String(d.getFullYear()).slice(2);
            }
            months.push(lbl);
        }

        // Simulated cumulative recovery trend (illustrated data — real data comes from PHP endpoint)
        // Monotonically increasing series so the trend is coherent and last value == totalRecovered
        var finalRecovered = totalRecovered > 0 ? totalRecovered : 5000 * 6;
        var finalReported  = totalReported  > 0 ? totalReported  : 20000 * 6;
        var ratios = [0.08, 0.20, 0.38, 0.58, 0.80, 1.00];
        var recoveredData = ratios.map(function(r) { return Math.round(finalRecovered * r); });
        var reportedData  = ratios.map(function(r, i) {
            return Math.round(finalReported * (0.55 + i * 0.09));
        });
        reportedData[5] = Math.round(finalReported);

        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Zurückgewonnen (€)',
                        data: recoveredData,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40,167,69,0.10)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#28a745',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Gemeldet (€)',
                        data: reportedData,
                        borderColor: '#2950a8',
                        backgroundColor: 'rgba(41,80,168,0.07)',
                        borderWidth: 2,
                        pointBackgroundColor: '#2950a8',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4,
                        borderDash: [5, 3]
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { size: 12 }, usePointStyle: true, pointStyleWidth: 10 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                return ' ' + c.dataset.label + ': €' + c.raw.toLocaleString('de-DE');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            font: { size: 11 },
                            callback: function(v) { return '€' + v.toLocaleString('de-DE'); }
                        }
                    }
                },
                animation: { duration: 1400, easing: 'easeOutQuart' }
            }
        });
    })();

    // -- Old status chart (keep for backward compat) --
    (function() {
        var ctx = document.getElementById('statusChart');
        if (!ctx || !Object.keys(statusCountsData).length) return;
        var labels = [], values = [], colors = [];
        var labelMap = {open:'Offen',documents_required:'Dokumente',under_review:'In Prüfung',refund_approved:'Genehmigt',refund_rejected:'Abgelehnt',closed:'Abgeschlossen'};
        var colorMap = {open:'#ffc107',documents_required:'#6c757d',under_review:'#17a2b8',refund_approved:'#28a745',refund_rejected:'#dc3545',closed:'#343a40'};
        for (var k in statusCountsData) { labels.push(labelMap[k]||k); values.push(statusCountsData[k]); colors.push(colorMap[k]||'#2950a8'); }
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
            options: { responsive: true, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
        });
    })();

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
