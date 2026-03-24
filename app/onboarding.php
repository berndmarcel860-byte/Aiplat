<?php
/**
 * User Onboarding Wizard
 * 
 * UPDATED: 2026-02-19
 * Branch: copilot/sub-pr-1
 * 
 * Features:
 * - Multi-step registration wizard
 * - Case details collection
 * - Address information
 * - Payment method setup (Bank & Crypto support)
 * - Modern responsive card-based design
 * 
 * Security: CSRF protection, input validation, PDO prepared statements
 */
// =============================================================
// 🧠 Scam Recovery - User Onboarding
// =============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';

// === CSRF TOKEN ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// === Redirect if not logged in ===
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// === Check if onboarding already completed ===
try {
    $stmt = $pdo->prepare("SELECT completed FROM user_onboarding WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $onboarding = $stmt->fetch();
    if ($onboarding && $onboarding['completed']) {
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    die("System error: " . $e->getMessage());
}

// === Handle Form Submissions ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $step = (int)($_GET['step'] ?? 1);

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Ungültiges Sicherheitstoken. Bitte versuchen Sie es erneut.";
        header("Location: onboarding.php?step=$step");
        exit();
    }

    try {
        switch ($step) {

            // =========================================================
            // STEP 1: Case details
            // =========================================================
            case 1:
                $lostAmount = filter_input(INPUT_POST, 'lost_amount', FILTER_VALIDATE_FLOAT);
                $yearLost = filter_input(INPUT_POST, 'year_lost', FILTER_VALIDATE_INT);
                $whereLost = trim($_POST['where_lost'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $platforms = isset($_POST['platforms']) ? array_map('intval', $_POST['platforms']) : [];

                if (!$lostAmount || !$yearLost || empty($whereLost) || empty($description) || empty($platforms)) {
                    throw new Exception("Bitte füllen Sie alle erforderlichen Felder aus.");
                }

                $stmt = $pdo->prepare("
                    INSERT INTO user_onboarding (user_id, lost_amount, platforms, year_lost, where_lost, case_description)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        lost_amount=VALUES(lost_amount),
                        platforms=VALUES(platforms),
                        year_lost=VALUES(year_lost),
                        where_lost=VALUES(where_lost),
                        case_description=VALUES(case_description)
                ");
                $stmt->execute([$userId, $lostAmount, json_encode($platforms), $yearLost, $whereLost, $description]);
                break;

            // =========================================================
            // STEP 2: Address Information
            // =========================================================
            case 2:
                $required = ['country','street','postal_code','state'];
                foreach ($required as $f)
                    if (empty($_POST[$f])) throw new Exception("Bitte füllen Sie alle Adressfelder aus.");

                $stmt = $pdo->prepare("UPDATE user_onboarding SET country=?, street=?, postal_code=?, state=? WHERE user_id=?");
                $stmt->execute([
                    htmlspecialchars($_POST['country']),
                    htmlspecialchars($_POST['street']),
                    htmlspecialchars($_POST['postal_code']),
                    htmlspecialchars($_POST['state']),
                    $userId
                ]);
                break;

            // =========================================================
            // STEP 3: Payment Methods (Bank OR Crypto - AT LEAST ONE REQUIRED)
            // =========================================================
            case 3:
                // Check if at least ONE payment method is provided
                $hasBank = !empty($_POST['bank_name']) && !empty($_POST['account_holder']) && 
                           !empty($_POST['iban']) && !empty($_POST['bic']);
                $hasCrypto = !empty($_POST['cryptocurrency']) && !empty($_POST['network']) && 
                             !empty($_POST['wallet_address']);
                
                if (!$hasBank && !$hasCrypto) {
                    throw new Exception("Bitte fügen Sie mindestens eine Zahlungsmethode hinzu (Bankkonto ODER Krypto-Wallet).");
                }
                
                // Validate and save BANK ACCOUNT if provided
                if ($hasBank) {
                    // Validate IBAN format
                    if (!preg_match('/^[A-Z]{2}\d{2}[A-Z\d]{1,30}$/', str_replace(' ', '', $_POST['iban']))) {
                        throw new Exception("Ungültiges IBAN-Format.");
                    }
                    
                    // Save bank details to user_onboarding
                    $stmt = $pdo->prepare("UPDATE user_onboarding SET 
                        bank_name=?, 
                        account_holder=?, 
                        iban=?, 
                        bic=?
                        WHERE user_id=?");
                        
                    $stmt->execute([
                        htmlspecialchars($_POST['bank_name']),
                        htmlspecialchars($_POST['account_holder']),
                        strtoupper(str_replace(' ', '', $_POST['iban'])),
                        strtoupper($_POST['bic']),
                        $userId
                    ]);
                    
                    // Insert bank account into user_payment_methods
                    $bankName = htmlspecialchars($_POST['bank_name']);
                    $accountHolder = htmlspecialchars($_POST['account_holder']);
                    $iban = strtoupper(str_replace(' ', '', $_POST['iban']));
                    $bic = strtoupper($_POST['bic']);
                    
                    $stmt_bank = $pdo->prepare("INSERT INTO user_payment_methods 
                        (user_id, type, payment_method, label, bank_name, account_holder, iban, bic, 
                         is_default, verification_status, created_at) 
                        VALUES (?, 'fiat', 'bank_transfer', ?, ?, ?, ?, ?, 1, 'pending', NOW())");
                    $stmt_bank->execute([$userId, $bankName, $bankName, $accountHolder, $iban, $bic]);
                }
                
                // Validate and save CRYPTOCURRENCY if provided
                if ($hasCrypto) {
                    // Insert crypto wallet into user_payment_methods
                    $cryptocurrency = htmlspecialchars($_POST['cryptocurrency']);
                    $network = htmlspecialchars($_POST['network']);
                    $walletAddress = htmlspecialchars($_POST['wallet_address']);
                    
                    $stmt_crypto = $pdo->prepare("INSERT INTO user_payment_methods 
                        (user_id, type, payment_method, label, cryptocurrency, network, wallet_address, 
                         is_default, verification_status, verification_requested_at, created_at) 
                        VALUES (?, 'crypto', ?, ?, ?, ?, ?, 1, 'pending', NOW(), NOW())");
                    $stmt_crypto->execute([$userId, strtolower($cryptocurrency), $cryptocurrency, 
                        $cryptocurrency, $network, $walletAddress]);
                }
                
                break;

            // =========================================================
            // STEP 4: Complete Onboarding
            // =========================================================
            case 4:
                // Mark onboarding completed
                $pdo->prepare("UPDATE user_onboarding SET completed = 1 WHERE user_id=?")->execute([$userId]);

                // =========================================================
                // Send Onboarding Completion Email with Payment Details
                // =========================================================
                try {
                    // Get user details - using first_name and last_name (not 'name')
                    $stmt_user = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
                    $stmt_user->execute([$userId]);
                    $user = $stmt_user->fetch();
                    
                    // Get platform settings for footer - using system_settings table (not 'settings')
                    $stmt_settings = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
                    $settings = $stmt_settings->fetch();
                    
                    // Get SMTP settings from smtp_settings table
                    $stmt_smtp = $pdo->query("SELECT * FROM smtp_settings WHERE id = 1");
                    $smtp_settings = $stmt_smtp->fetch();
                    
                    // Get onboarding data with payment details
                    $stmt_onboarding = $pdo->prepare("SELECT * FROM user_onboarding WHERE user_id = ?");
                    $stmt_onboarding->execute([$userId]);
                    $onboarding_data = $stmt_onboarding->fetch();
                    
                    // Get crypto payment method data
                    $stmt_crypto = $pdo->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? AND type = 'crypto' ORDER BY created_at DESC LIMIT 1");
                    $stmt_crypto->execute([$userId]);
                    $crypto_data = $stmt_crypto->fetch();
                    
                    // Get email template from database - using template_key column
                    $stmt_template = $pdo->prepare("SELECT * FROM email_templates WHERE template_key = 'onboarding_complete'");
                    $stmt_template->execute();
                    $template = $stmt_template->fetch();
                    
                    if ($template && $user && $onboarding_data && $smtp_settings) {
                        // Prepare email variables with correct column names
                        $variables = [
                            'user_name' => ($user['first_name'] . ' ' . $user['last_name']) ?? 'Valued Customer',
                            'company_name' => $settings['brand_name'] ?? 'Crypto Finanz',
                            'bank_name' => $onboarding_data['bank_name'] ?? 'N/A',
                            'account_holder' => $onboarding_data['account_holder'] ?? 'N/A',
                            'iban' => $onboarding_data['iban'] ?? 'N/A',
                            'bic' => $onboarding_data['bic'] ?? 'N/A',
                            'cryptocurrency' => $crypto_data['cryptocurrency'] ?? 'N/A',
                            'network' => $crypto_data['network'] ?? 'N/A',
                            'wallet_address' => $crypto_data['wallet_address'] ?? 'N/A',
                            'dashboard_url' => ($settings['site_url'] ?? '') . '/index.php',
                            'support_email' => $settings['contact_email'] ?? 'no-reply@cryptofinanze.de',
                            'support_phone' => $settings['contact_phone'] ?? '',
                            'company_address' => $settings['company_address'] ?? 'Bockenheimer Anlage 46\r\n60322 Frankfurt am Main\r\nDeutschland',
                            'company_city' => 'Frankfurt am Main',
                            'company_country' => 'Deutschland',
                            'website_url' => $settings['site_url'] ?? 'https://cryptofinanze.de/app',
                            'terms_url' => ($settings['site_url'] ?? '') . '/terms.php',
                            'privacy_url' => ($settings['site_url'] ?? '') . '/privacy.php',
                            'current_year' => date('Y'),
                            'fca_reference_number' => $settings['fca_reference_number'] ?? '50085600'
                        ];
                        
                        // Replace variables in template - using content column not body
                        $email_subject = $template['subject'];
                        $email_body = $template['content'];
                        
                        foreach ($variables as $key => $value) {
                            $email_subject = str_replace('{{'.$key.'}}', $value, $email_subject);
                            $email_body = str_replace('{{'.$key.'}}', $value, $email_body);
                        }
                        
                        // Use PHPMailer to send email
                        require_once __DIR__ . '/vendor/autoload.php';
                        
                        // Use fully qualified class names to avoid syntax errors
                        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                        
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host       = $smtp_settings['host'] ?? 'localhost';
                        $mail->SMTPAuth   = !empty($smtp_settings['username']);
                        $mail->Username   = $smtp_settings['username'] ?? '';
                        $mail->Password   = $smtp_settings['password'] ?? '';
                        $mail->SMTPSecure = $smtp_settings['encryption'] ?? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = $smtp_settings['port'] ?? 587;
                        
                        // Recipients
                        $mail->setFrom($smtp_settings['from_email'] ?? 'noreply@example.com', 
                                      $smtp_settings['from_name'] ?? ($settings['site_name'] ?? 'Crypto Recovery'));
                        $mail->addAddress($user['email'], $user['name']);
                        $mail->addReplyTo($settings['support_email'] ?? 'support@example.com', 
                                         $settings['site_name'] ?? 'Support');
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->CharSet = 'UTF-8';
                        $mail->Subject = $email_subject;
                        $mail->Body    = $email_body;
                        $mail->AltBody = strip_tags($email_body);
                        
                        // Send email
                        $mail->send();
                        
                        // Log successful email
                        $stmt_log = $pdo->prepare("INSERT INTO email_logs (user_id, email_type, recipient, subject, sent_at, status) VALUES (?, 'onboarding_completed', ?, ?, NOW(), 'sent')");
                        $stmt_log->execute([$userId, $user['email'], $email_subject]);
                        
                        error_log("Onboarding completion email sent successfully to: " . $user['email']);
                        
                    } else {
                        $missing = [];
                        if (!$template) $missing[] = 'email template';
                        if (!$user) $missing[] = 'user data';
                        if (!$onboarding_data) $missing[] = 'onboarding data';
                        if (!$smtp_settings) $missing[] = 'SMTP settings';
                        
                        error_log("Cannot send email - Missing: " . implode(', ', $missing));
                        
                        $stmt_log = $pdo->prepare("INSERT INTO email_logs (user_id, email_type, sent_at, status, error_message) VALUES (?, 'onboarding_completed', NOW(), 'failed', ?)");
                        $stmt_log->execute([$userId, 'Missing required data: ' . implode(', ', $missing)]);
                    }
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                    // Log PHPMailer specific error
                    error_log("PHPMailer Error: " . $e->getMessage());
                    try {
                        $stmt_log = $pdo->prepare("INSERT INTO email_logs (user_id, email_type, recipient, sent_at, status, error_message) VALUES (?, 'onboarding_completed', ?, NOW(), 'error', ?)");
                        $stmt_log->execute([$userId, $user['email'] ?? 'unknown', 'PHPMailer Error: ' . $e->getMessage()]);
                    } catch (Exception $log_error) {
                        error_log("Email logging error: " . $log_error->getMessage());
                    }
                } catch (Exception $e) {
                    // Log error but don't stop onboarding completion
                    error_log("Onboarding email error: " . $e->getMessage());
                    try {
                        $stmt_log = $pdo->prepare("INSERT INTO email_logs (user_id, email_type, sent_at, status, error_message) VALUES (?, 'onboarding_completed', NOW(), 'error', ?)");
                        $stmt_log->execute([$userId, $e->getMessage()]);
                    } catch (Exception $log_error) {
                        error_log("Email logging error: " . $log_error->getMessage());
                    }
                }

                header("Location: onboarding_complete.php");
                exit();
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header("Location: onboarding.php?step=$step");
        exit();
    }

    header("Location: onboarding.php?step=" . ($step + 1));
    exit();
}

// === Load Data for Steps ===
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$maxSteps = 4;

try {
    $platforms = $pdo->query("SELECT id,name FROM scam_platforms WHERE is_active=1")->fetchAll();
    $data = $pdo->prepare("SELECT * FROM user_onboarding WHERE user_id=?");
    $data->execute([$_SESSION['user_id']]);
    $saved = $data->fetch();
} catch (PDOException $e) {
    die("Database error.");
}

// === Package Recommendation Logic (used in Step 4) ===
$recommendedPackage = null;
$allPackages = [];
if ($step == 4) {
    try {
        $pkgStmt = $pdo->query("SELECT * FROM packages ORDER BY price ASC");
        $allPackages = $pkgStmt->fetchAll(PDO::FETCH_ASSOC);

        $yearLostValue = $saved['year_lost'] ?? null;
        if ($yearLostValue) {
            $currentYear = (int)date('Y');
            $yearsSinceLoss = $currentYear - (int)$yearLostValue;

            // Map years-since-loss to package tier
            // ≤1 year → lowest (Basic), ≤3 years → second (Standard),
            // ≤5 years → third (Premium), >5 years → highest/unlimited (VIP)
            if ($yearsSinceLoss <= 1) {
                $recommendedPackage = $allPackages[0] ?? null;
            } elseif ($yearsSinceLoss <= 3) {
                $recommendedPackage = $allPackages[1] ?? $allPackages[0] ?? null;
            } elseif ($yearsSinceLoss <= 5) {
                $recommendedPackage = $allPackages[2] ?? $allPackages[1] ?? null;
            } else {
                // Over 5 years → highest/unlimited package (most expensive non-free)
                $topPackage = null;
                foreach (array_reverse($allPackages) as $pkg) {
                    if ((float)$pkg['price'] > 0) {
                        $topPackage = $pkg;
                        break;
                    }
                }
                $recommendedPackage = $topPackage ?? ($allPackages[count($allPackages) - 1] ?? null);
            }
        } else {
            $recommendedPackage = $allPackages[0] ?? null;
        }
    } catch (PDOException $e) {
        error_log("Package load error: " . $e->getMessage());
    }
}

require_once __DIR__ . '/header.php';
if (!empty($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
        <i class="anticon anticon-close-circle mr-2"></i>' . htmlspecialchars($_SESSION['error']) . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Schließen"><span aria-hidden="true">&times;</span></button>
    </div>';
    unset($_SESSION['error']);
}
?>

<!-- =========================================================
 ONBOARDING - Professionelles Einrichtungsassistenten-Design
========================================================= -->
<style>
/* ── Markenfarben ── */
:root {
    --ob-primary:  #2950a8;
    --ob-accent:   #2da9e3;
    --ob-success:  #28a745;
    --ob-warning:  #ffc107;
    --ob-danger:   #dc3545;
    --ob-text:     #2c3e50;
    --ob-muted:    #6c757d;
    --ob-border:   #e3e8f0;
    --ob-bg:       #f8fafc;
    --ob-shadow:   0 4px 24px rgba(41,80,168,.12);
}

/* ── Layout ── */
.ob-wrap {
    max-width: 760px;
    margin: 0 auto;
    padding: 24px 16px 80px;
}

/* ── Card ── */
.ob-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: var(--ob-shadow);
    border: 1px solid var(--ob-border);
    overflow: hidden;
}

/* ── Card Header ── */
.ob-card-header {
    background: linear-gradient(135deg, var(--ob-primary) 0%, var(--ob-accent) 100%);
    padding: 28px 32px 24px;
    color: #fff;
}

.ob-card-header h1 {
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: 0.2px;
}

.ob-card-header p {
    margin: 6px 0 0;
    font-size: 0.93rem;
    opacity: 0.88;
}

/* ── Step Progress ── */
.ob-stepper {
    display: flex;
    align-items: flex-start;
    padding: 24px 32px 20px;
    background: #fff;
    border-bottom: 1px solid var(--ob-border);
    position: relative;
    gap: 0;
}

.ob-stepper::before {
    content: '';
    position: absolute;
    top: 38px;
    left: calc(32px + 20px);
    right: calc(32px + 20px);
    height: 2px;
    background: var(--ob-border);
    z-index: 0;
}

.ob-step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}

.ob-step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 15px;
    margin: 0 auto 8px;
    border: 2px solid var(--ob-border);
    background: #fff;
    color: var(--ob-muted);
    transition: all .3s ease;
}

.ob-step.is-done .ob-step-circle {
    background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent));
    border-color: var(--ob-primary);
    color: #fff;
}

.ob-step.is-active .ob-step-circle {
    background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent));
    border-color: var(--ob-primary);
    color: #fff;
    box-shadow: 0 0 0 4px rgba(41,80,168,.15);
}

.ob-step-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--ob-muted);
    white-space: nowrap;
}

.ob-step.is-active .ob-step-label,
.ob-step.is-done .ob-step-label {
    color: var(--ob-primary);
}

/* ── Connector line above the circles ── */
.ob-connector {
    flex: 1;
    height: 2px;
    background: var(--ob-border);
    margin: 19px 0 0;
    position: relative;
    z-index: 0;
}

.ob-connector.done {
    background: linear-gradient(90deg, var(--ob-primary), var(--ob-accent));
}

/* ── Form body ── */
.ob-body {
    padding: 32px;
}

/* ── Section heading ── */
.ob-section-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--ob-text);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.ob-section-title .ob-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #fff;
    flex-shrink: 0;
}

.ob-section-desc {
    color: var(--ob-muted);
    font-size: 0.9rem;
    margin-bottom: 24px;
    padding-left: 46px;
}

/* ── Form controls ── */
.ob-form-group {
    margin-bottom: 20px;
}

.ob-form-group label {
    display: block;
    font-weight: 600;
    font-size: 0.88rem;
    color: var(--ob-text);
    margin-bottom: 6px;
}

.ob-form-group label .req {
    color: var(--ob-danger);
    margin-left: 2px;
}

.ob-control {
    width: 100%;
    border: 1.5px solid var(--ob-border);
    border-radius: 10px;
    padding: 11px 16px;
    font-size: 0.95rem;
    color: var(--ob-text);
    background: #fff;
    transition: border-color .2s, box-shadow .2s;
    -webkit-appearance: none;
    appearance: none;
}

.ob-control:focus {
    outline: none;
    border-color: var(--ob-primary);
    box-shadow: 0 0 0 3px rgba(41,80,168,.15);
}

select.ob-control {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 38px;
}

textarea.ob-control {
    resize: vertical;
    min-height: 100px;
}

.ob-hint {
    font-size: 0.8rem;
    color: var(--ob-muted);
    margin-top: 4px;
}

/* ── Info box ── */
.ob-info {
    background: linear-gradient(135deg, rgba(41,80,168,.06), rgba(45,169,227,.04));
    border: 1px solid rgba(41,80,168,.15);
    border-radius: 10px;
    padding: 16px 18px;
    margin-bottom: 20px;
    font-size: 0.88rem;
    color: var(--ob-text);
    line-height: 1.6;
}

.ob-info strong {
    color: var(--ob-primary);
}

.ob-info ul {
    margin: 8px 0 0 0;
    padding-left: 18px;
}

.ob-info ul li {
    margin-bottom: 4px;
}

/* ── Warning box ── */
.ob-warn {
    background: #fff8e6;
    border: 1px solid #ffd166;
    border-radius: 10px;
    padding: 14px 18px;
    font-size: 0.87rem;
    color: #856404;
    margin-bottom: 20px;
}

/* ── Success box ── */
.ob-success {
    background: linear-gradient(135deg, rgba(40,167,69,.07), rgba(32,201,151,.05));
    border: 1px solid rgba(40,167,69,.2);
    border-radius: 10px;
    padding: 20px 24px;
    margin-bottom: 20px;
}

/* ── Payment tabs ── */
.ob-tabs {
    display: flex;
    border-bottom: 2px solid var(--ob-border);
    margin-bottom: 0;
    gap: 4px;
}

.ob-tab {
    padding: 10px 22px;
    cursor: pointer;
    font-size: 0.93rem;
    font-weight: 600;
    color: var(--ob-muted);
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: color .2s, border-color .2s;
    border-radius: 6px 6px 0 0;
}

.ob-tab:hover {
    color: var(--ob-primary);
}

.ob-tab.active {
    color: var(--ob-primary);
    border-bottom-color: var(--ob-primary);
    background: rgba(41,80,168,.05);
}

.ob-tab-pane {
    display: none;
    padding: 24px 0 8px;
}

.ob-tab-pane.active {
    display: block;
}

/* ── Platforms grid ── */
.ob-platforms {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 10px;
}

.ob-platform-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border: 1.5px solid var(--ob-border);
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--ob-text);
    transition: border-color .2s, background .2s;
    background: #fff;
}

.ob-platform-item:hover {
    border-color: var(--ob-accent);
    background: rgba(45,169,227,.05);
}

.ob-platform-item input[type=checkbox] {
    accent-color: var(--ob-primary);
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.ob-platform-item.checked {
    border-color: var(--ob-primary);
    background: rgba(41,80,168,.06);
}

/* ── Footer / CTA ── */
.ob-footer {
    display: flex;
    justify-content: flex-end;
    padding: 20px 32px 28px;
    border-top: 1px solid var(--ob-border);
    background: var(--ob-bg);
}

.ob-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 32px;
    border-radius: 10px;
    font-size: 0.97rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all .25s ease;
}

.ob-btn-primary {
    background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent));
    color: #fff;
    box-shadow: 0 4px 14px rgba(41,80,168,.3);
}

.ob-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(41,80,168,.4);
}

/* ── Row helper ── */
.ob-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

@media (max-width: 575px) {
    .ob-body      { padding: 20px 18px; }
    .ob-footer    { padding: 16px 18px 24px; }
    .ob-stepper   { padding: 18px; }
    .ob-card-header { padding: 20px 18px; }
    .ob-row       { grid-template-columns: 1fr; }
    .ob-platforms { grid-template-columns: 1fr 1fr; }
    .ob-step-label { font-size: 0.7rem; }
}
@keyframes ob-spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
@keyframes ob-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(41,80,168,.3); }
    50%       { box-shadow: 0 0 0 14px rgba(41,80,168,0); }
}
</style>

<div class="main-content">
<div class="ob-wrap">

<!-- ── Card ── -->
<div class="ob-card">

    <!-- Card Header -->
    <div class="ob-card-header">
        <h1><i class="anticon anticon-safety-certificate mr-2"></i> Konto einrichten</h1>
        <p>Schritt <?= $step ?> von <?= $maxSteps ?> &#x2013; Bitte füllen Sie alle Angaben vollständig aus</p>
    </div>

    <!-- Step Progress Indicator -->
    <div class="ob-stepper" role="navigation" aria-label="Fortschritt">
        <?php
        $stepDefs = [
            1 => ['label' => 'Falldetails',   'icon' => 'anticon-file-text'],
            2 => ['label' => 'Adresse',         'icon' => 'anticon-home'],
            3 => ['label' => 'Zahlung',         'icon' => 'anticon-wallet'],
            4 => ['label' => 'Abschluss',       'icon' => 'anticon-check-circle'],
        ];
        foreach ($stepDefs as $n => $def):
            $isDone   = $n < $step;
            $isActive = $n === $step;
            $cls      = $isDone ? 'is-done' : ($isActive ? 'is-active' : '');
        ?>
        <?php if ($n > 1): ?>
        <div class="ob-connector <?= $isDone ? 'done' : '' ?>"></div>
        <?php endif; ?>
        <div class="ob-step <?= $cls ?>" aria-current="<?= $isActive ? 'step' : 'false' ?>">
            <div class="ob-step-circle">
                <?php if ($isDone): ?>
                    <i class="anticon anticon-check" style="font-size:16px;"></i>
                <?php else: ?>
                    <?= $n ?>
                <?php endif; ?>
            </div>
            <div class="ob-step-label"><?= $def['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Form body -->
    <div class="ob-body">

    <?php if ($step == 1): ?>
    <!-- ============================================================
     SCHRITT 1: Falldetails
    ============================================================ -->
    <div class="ob-section-title">
        <span class="ob-icon"><i class="anticon anticon-file-text" style="font-size:18px;"></i></span>
        Erzählen Sie uns von Ihrem Fall
    </div>
    <p class="ob-section-desc">Diese Angaben helfen uns, Ihren Fall zu analysieren und die Wiederherstellung Ihrer Gelder einzuleiten.</p>

    <form method="post" action="onboarding.php?step=<?= $step ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- Verlorener Betrag -->
        <div class="ob-form-group">
            <label>Verlorener Betrag (EUR) <span class="req">*</span></label>
            <select name="lost_amount" class="ob-control" required>
                <option value="">Betrag auswählen …</option>
                <?php
                $amounts = [
                    1000   => 'Weniger als €1.000',
                    5000   => '€1.000 – €5.000',
                    10000  => '€5.000 – €10.000',
                    25000  => '€10.000 – €25.000',
                    50000  => '€25.000 – €50.000',
                    100000 => '€50.000 – €100.000',
                    250000 => '€100.000 – €250.000',
                    500000 => 'Mehr als €250.000',
                ];
                foreach ($amounts as $v => $label):
                    $sel = ($saved['lost_amount'] ?? '') == $v ? 'selected' : '';
                ?>
                <option value="<?= $v ?>" <?= $sel ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Wo verloren -->
        <div class="ob-form-group">
            <label>Wo wurden die Gelder verloren? <span class="req">*</span></label>
            <input type="text" name="where_lost" class="ob-control"
                   value="<?= htmlspecialchars($saved['where_lost'] ?? '') ?>"
                   placeholder="z. B. Binance, Coinbase, Trading-Plattform XYZ …"
                   required>
            <p class="ob-hint">Name der Plattform, Börse oder des Handelshauses</p>
        </div>

        <!-- Plattformen -->
        <div class="ob-form-group">
            <label>Verwendete Plattformen <span class="req">*</span></label>
            <?php
            $chosen = !empty($saved['platforms']) ? json_decode($saved['platforms'], true) : [];
            ?>
            <div class="ob-platforms" role="group" aria-label="Plattformen">
                <?php foreach ($platforms as $p):
                    $checked = in_array($p['id'], $chosen) ? 'checked' : '';
                    $checkedCls = $checked ? 'checked' : '';
                ?>
                <label class="ob-platform-item <?= $checkedCls ?>">
                    <input type="checkbox" name="platforms[]" value="<?= $p['id'] ?>" <?= $checked ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="ob-hint">Wählen Sie alle betroffenen Plattformen aus</p>
        </div>

        <!-- Jahr -->
        <div class="ob-form-group">
            <label>Jahr des Verlusts <span class="req">*</span></label>
            <select name="year_lost" class="ob-control" required>
                <option value="">Jahr auswählen …</option>
                <?php for ($y = date('Y'); $y >= 2000; $y--):
                    $sel = ($saved['year_lost'] ?? '') == $y ? 'selected' : ''; ?>
                <option value="<?= $y ?>" <?= $sel ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- Beschreibung -->
        <div class="ob-form-group">
            <label>Fallbeschreibung <span class="req">*</span></label>
            <textarea name="description" class="ob-control"
                      placeholder="Beschreiben Sie kurz, was passiert ist und wie Sie geschädigt wurden …"
                      required><?= htmlspecialchars($saved['case_description'] ?? '') ?></textarea>
            <p class="ob-hint">Je mehr Details Sie angeben, desto gezielter können wir Ihnen helfen.</p>
        </div>

        <div class="text-right">
            <button type="submit" class="ob-btn ob-btn-primary">
                Weiter <i class="anticon anticon-arrow-right"></i>
            </button>
        </div>
    </form>

    <?php elseif ($step == 2): ?>
    <!-- ============================================================
     SCHRITT 2: Adresse
    ============================================================ -->
    <div class="ob-section-title">
        <span class="ob-icon"><i class="anticon anticon-home" style="font-size:18px;"></i></span>
        Ihre Kontaktadresse
    </div>
    <p class="ob-section-desc">Wir benötigen Ihre Adresse für die offizielle Fallkorrespondenz und Dokumente.</p>

    <form method="post" action="onboarding.php?step=<?= $step ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- Land -->
        <div class="ob-form-group">
            <label>Land <span class="req">*</span></label>
            <select name="country" class="ob-control" required>
                <option value="">Land auswählen …</option>
                <?php
                $countries = [
                    'Deutschland', 'Österreich', 'Schweiz', 'Frankreich', 'Italien',
                    'Spanien', 'Niederlande', 'Belgien', 'Luxemburg', 'Dänemark',
                    'Schweden', 'Norwegen', 'Polen', 'Tschechien', 'Andere',
                ];
                foreach ($countries as $c):
                    $sel = ($saved['country'] ?? '') === $c ? 'selected' : '';
                ?>
                <option value="<?= $c ?>" <?= $sel ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Straße -->
        <div class="ob-form-group">
            <label>Straße und Hausnummer <span class="req">*</span></label>
            <input type="text" name="street" class="ob-control"
                   value="<?= htmlspecialchars($saved['street'] ?? '') ?>"
                   placeholder="Hauptstraße 42"
                   required>
        </div>

        <!-- PLZ + Stadt -->
        <div class="ob-row">
            <div class="ob-form-group">
                <label>Postleitzahl <span class="req">*</span></label>
                <input type="text" name="postal_code" class="ob-control"
                       value="<?= htmlspecialchars($saved['postal_code'] ?? '') ?>"
                       placeholder="60322"
                       required>
            </div>
            <div class="ob-form-group">
                <label>Stadt / Bundesland <span class="req">*</span></label>
                <input type="text" name="state" class="ob-control"
                       value="<?= htmlspecialchars($saved['state'] ?? '') ?>"
                       placeholder="Frankfurt am Main"
                       required>
            </div>
        </div>

        <div class="text-right">
            <button type="submit" class="ob-btn ob-btn-primary">
                Weiter <i class="anticon anticon-arrow-right"></i>
            </button>
        </div>
    </form>

    <?php elseif ($step == 3): ?>
    <!-- ============================================================
     SCHRITT 3: Zahlungsmethoden
    ============================================================ -->
    <div class="ob-section-title">
        <span class="ob-icon"><i class="anticon anticon-wallet" style="font-size:18px;"></i></span>
        Zahlungsmethode hinterlegen
    </div>
    <p class="ob-section-desc">Damit wir zurückgewonnene Gelder auszahlen können, benötigen wir mindestens eine Zahlungsmethode.</p>

    <div class="ob-info">
        <strong>Warum ist das notwendig?</strong>
        <ul>
            <li>Unser KI-System analysiert Blockchain-Transaktionen und ordnet gefundene Gelder Ihrem Konto zu.</li>
            <li>Für die Auszahlung benötigen wir entweder ein Bankkonto <strong>oder</strong> eine Krypto-Wallet.</li>
            <li>Alle Daten werden verschlüsselt gespeichert und ausschließlich für Rückzahlungen verwendet.</li>
        </ul>
    </div>

    <div class="ob-warn">
        <i class="anticon anticon-info-circle mr-1"></i>
        <strong>Hinweis:</strong> Sie können jederzeit weitere Zahlungsmethoden in Ihrem Profil ergänzen.
    </div>

    <form method="post" action="onboarding.php?step=<?= $step ?>" id="paymentForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- Tab Navigation -->
        <div class="ob-tabs" role="tablist">
            <div class="ob-tab active" role="tab" id="tab-bank" onclick="switchTab('bank')" tabindex="0">
                <i class="anticon anticon-bank mr-1"></i> Bankkonto
            </div>
            <div class="ob-tab" role="tab" id="tab-crypto" onclick="switchTab('crypto')" tabindex="0">
                <i class="anticon anticon-block mr-1"></i> Krypto-Wallet
            </div>
        </div>

        <!-- Bank Tab -->
        <div class="ob-tab-pane active" id="pane-bank" role="tabpanel" aria-labelledby="tab-bank">

            <div class="ob-form-group">
                <label>Bankname</label>
                <input type="text" name="bank_name" class="ob-control"
                       value="<?= htmlspecialchars($saved['bank_name'] ?? '') ?>"
                       placeholder="z. B. Sparkasse, Deutsche Bank">
            </div>

            <div class="ob-form-group">
                <label>Kontoinhaber</label>
                <input type="text" name="account_holder" class="ob-control"
                       value="<?= htmlspecialchars($saved['account_holder'] ?? '') ?>"
                       placeholder="Vollständiger Name wie auf dem Bankkonto">
            </div>

            <div class="ob-form-group">
                <label>IBAN</label>
                <input type="text" name="iban" class="ob-control"
                       value="<?= htmlspecialchars($saved['iban'] ?? '') ?>"
                       placeholder="DE89 3704 0044 0532 0130 00"
                       style="font-family: monospace; letter-spacing: 1px;">
                <p class="ob-hint">Internationale Bankkontonummer (wird vor Nutzung verifiziert)</p>
            </div>

            <div class="ob-form-group">
                <label>BIC / SWIFT</label>
                <input type="text" name="bic" class="ob-control"
                       value="<?= htmlspecialchars($saved['bic'] ?? '') ?>"
                       placeholder="COBADEFFXXX"
                       style="font-family: monospace; letter-spacing: 1px;">
                <p class="ob-hint">Bank-Identifikationscode Ihrer Bank</p>
            </div>
        </div>

        <!-- Crypto Tab -->
        <div class="ob-tab-pane" id="pane-crypto" role="tabpanel" aria-labelledby="tab-crypto">

            <div class="ob-form-group">
                <label>Kryptowährung</label>
                <select name="cryptocurrency" class="ob-control">
                    <option value="">Kryptowährung auswählen …</option>
                    <?php
                    $coins = [
                        'BTC' => 'Bitcoin (BTC)',   'ETH' => 'Ethereum (ETH)',
                        'USDT'=> 'Tether (USDT)',    'USDC'=> 'USD Coin (USDC)',
                        'BNB' => 'Binance Coin (BNB)','ADA'=> 'Cardano (ADA)',
                        'LTC' => 'Litecoin (LTC)',   'XRP' => 'XRP (Ripple)',
                        'SOL' => 'Solana (SOL)',      'TRX' => 'TRON (TRX)',
                    ];
                    $savedCoin = $saved['cryptocurrency'] ?? '';
                    foreach ($coins as $val => $lbl):
                        $sel = $savedCoin === $val ? 'selected' : '';
                    ?>
                    <option value="<?= $val ?>" <?= $sel ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ob-form-group">
                <label>Blockchain-Netzwerk</label>
                <select name="network" class="ob-control">
                    <option value="">Netzwerk auswählen …</option>
                    <?php
                    $networks = [
                        'Bitcoin Network'              => 'Bitcoin Network',
                        'Ethereum (ERC-20)'            => 'Ethereum (ERC-20)',
                        'Tron (TRC-20)'                => 'Tron (TRC-20)',
                        'Binance Smart Chain (BEP-20)' => 'Binance Smart Chain (BEP-20)',
                        'Polygon Network'              => 'Polygon Network',
                        'Solana Network'               => 'Solana Network',
                        'XRP Ledger'                   => 'XRP Ledger',
                    ];
                    $savedNet = $saved['network'] ?? '';
                    foreach ($networks as $val => $lbl):
                        $sel = $savedNet === $val ? 'selected' : '';
                    ?>
                    <option value="<?= $val ?>" <?= $sel ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="ob-hint">Wählen Sie das Netzwerk passend zu Ihrem Wallet</p>
            </div>

            <div class="ob-form-group">
                <label>Wallet-Adresse</label>
                <input type="text" name="wallet_address" class="ob-control"
                       value="<?= htmlspecialchars($saved['wallet_address'] ?? '') ?>"
                       placeholder="0xabcd1234efgh5678 …"
                       style="font-family: monospace; font-size: 0.88rem; letter-spacing: 0.5px;">
                <p class="ob-hint">Ihre öffentliche Wallet-Adresse (wird per Satoshi-Test verifiziert)</p>
            </div>

        </div><!-- /crypto-pane -->

        <div class="text-right mt-3">
            <button type="submit" class="ob-btn ob-btn-primary">
                Einrichtung abschließen <i class="anticon anticon-check"></i>
            </button>
        </div>
    </form>

    <script>
    function switchTab(tab) {
        document.querySelectorAll('.ob-tab').forEach(function(t){ t.classList.remove('active'); });
        document.querySelectorAll('.ob-tab-pane').forEach(function(p){ p.classList.remove('active'); });
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('pane-' + tab).classList.add('active');
    }

    // Auto-switch to crypto tab if previously saved
    (function() {
        var hasCrypto = <?= !empty($saved['cryptocurrency']) ? 'true' : 'false' ?>;
        if (hasCrypto) switchTab('crypto');
    })();
    </script>

    <?php elseif ($step == 4): ?>
    <!-- ============================================================
     SCHRITT 4: Bestes Paket finden
    ============================================================ -->
    <div class="ob-section-title">
        <span class="ob-icon"><i class="anticon anticon-gift" style="font-size:18px;"></i></span>
        Wir finden das beste Paket für Sie
    </div>
    <p class="ob-section-desc">Basierend auf Ihrem Verlustjahr ermitteln wir das optimale Recovery-Paket für Ihren Fall.</p>

    <!-- Searching animation (shown for 15 seconds) -->
    <div id="searchingPackage" style="text-align:center; padding: 40px 20px;">
        <div style="margin-bottom: 24px;">
            <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--ob-primary),var(--ob-accent));display:flex;align-items:center;justify-content:center;margin:0 auto 16px;animation:ob-pulse 1.5s infinite;">
                <i class="anticon anticon-loading" style="color:#fff;font-size:32px;animation:ob-spin 1s linear infinite;"></i>
            </div>
            <strong style="font-size:1.1rem;color:var(--ob-text);">Analyse läuft...</strong>
            <p style="color:var(--ob-muted);margin:6px 0 24px;font-size:0.9rem;">Wir analysieren Ihren Fall und suchen das passende Paket.</p>
        </div>
        <div style="background:#e9ecef;border-radius:10px;height:10px;max-width:420px;margin:0 auto 10px;overflow:hidden;">
            <div id="searchProgressBar" style="height:100%;width:0%;border-radius:10px;background:linear-gradient(90deg,var(--ob-primary),var(--ob-accent));transition:width 1s linear;"></div>
        </div>
        <p style="color:var(--ob-muted);font-size:0.85rem;" id="searchCountdown">Noch <strong>15</strong> Sekunden...</p>
    </div>

    <!-- Recommended package (hidden initially, shown after 15 sec) -->
    <div id="packageResult" style="display:none;">
        <?php if ($recommendedPackage): ?>
        <div class="ob-success" style="margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#28a745,#20c997);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="anticon anticon-check" style="color:#fff;font-size:18px;"></i>
                </div>
                <div>
                    <strong style="font-size:1rem;color:#155724;">Empfohlenes Paket gefunden!</strong>
                    <p style="margin:2px 0 0;font-size:0.85rem;color:#1e7e34;">Basierend auf Ihrem Verlustjahr empfehlen wir Ihnen:</p>
                </div>
            </div>
        </div>

        <div style="border:2px solid var(--ob-primary);border-radius:14px;padding:24px;margin-bottom:24px;background:#f7f9ff;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
                <div>
                    <span style="display:inline-block;background:linear-gradient(135deg,var(--ob-primary),var(--ob-accent));color:#fff;font-size:0.75rem;padding:4px 12px;border-radius:20px;margin-bottom:8px;font-weight:600;">
                        ⭐ Empfohlen für Sie
                    </span>
                    <h5 style="margin:0 0 4px;color:var(--ob-text);font-weight:700;"><?= htmlspecialchars($recommendedPackage['name']) ?></h5>
                    <p style="margin:0;color:var(--ob-muted);font-size:0.88rem;"><?= htmlspecialchars($recommendedPackage['description'] ?? '') ?></p>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:1.8rem;font-weight:700;color:var(--ob-primary);">€<?= number_format((float)$recommendedPackage['price'], 0, ',', '.') ?></span>
                </div>
            </div>

            <?php if (!empty($recommendedPackage['features'])): ?>
            <ul style="list-style:none;padding:0;margin:0 0 16px;">
                <?php foreach (json_decode($recommendedPackage['features'], true) ?? [] as $feature): ?>
                <li style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:0.88rem;color:var(--ob-text);">
                    <i class="anticon anticon-check-circle" style="color:#28a745;flex-shrink:0;"></i>
                    <?= htmlspecialchars($feature) ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <?php if (!empty($recommendedPackage['recovery_speed'])): ?>
                <div style="flex:1;min-width:120px;background:#fff;border-radius:8px;padding:10px;text-align:center;border:1px solid var(--ob-border);">
                    <div style="font-size:0.75rem;color:var(--ob-muted);">Bearbeitungszeit</div>
                    <div style="font-weight:600;color:var(--ob-primary);font-size:0.88rem;"><?= htmlspecialchars($recommendedPackage['recovery_speed']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($recommendedPackage['support_level'])): ?>
                <div style="flex:1;min-width:120px;background:#fff;border-radius:8px;padding:10px;text-align:center;border:1px solid var(--ob-border);">
                    <div style="font-size:0.75rem;color:var(--ob-muted);">Support</div>
                    <div style="font-weight:600;color:var(--ob-primary);font-size:0.88rem;"><?= htmlspecialchars($recommendedPackage['support_level']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <a href="packages.php" class="ob-btn" style="background:#fff;border:2px solid var(--ob-primary);color:var(--ob-primary);padding:12px 24px;">
                <i class="anticon anticon-unordered-list mr-1"></i>Alle Pakete ansehen
            </a>
            <form method="post" action="onboarding.php?step=<?= $step ?>" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="ob-btn ob-btn-primary" style="padding:12px 32px;">
                    <i class="anticon anticon-check-circle mr-1"></i> Registrierung abschließen
                </button>
            </form>
        </div>

        <?php else: ?>
        <div style="background:#e8f4fd;border-radius:10px;padding:18px;margin-bottom:20px;border-left:4px solid var(--ob-accent);">
            <p style="margin:0;color:#0c5460;font-size:0.9rem;"><i class="anticon anticon-info-circle mr-2"></i>Bitte besuchen Sie die Paketübersicht für weitere Informationen.</p>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <a href="packages.php" class="ob-btn" style="background:#fff;border:2px solid var(--ob-primary);color:var(--ob-primary);padding:12px 24px;">
                <i class="anticon anticon-unordered-list mr-1"></i>Pakete ansehen
            </a>
            <form method="post" action="onboarding.php?step=<?= $step ?>" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="ob-btn ob-btn-primary" style="padding:12px 32px;">
                    <i class="anticon anticon-check-circle mr-1"></i> Registrierung abschließen
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
    (function () {
        var total = 15, elapsed = 0;
        var bar  = document.getElementById('searchProgressBar');
        var cd   = document.getElementById('searchCountdown');
        var sDiv = document.getElementById('searchingPackage');
        var rDiv = document.getElementById('packageResult');

        var iv = setInterval(function () {
            elapsed++;
            bar.style.width = Math.round((elapsed / total) * 100) + '%';
            var rem = total - elapsed;
            if (rem > 0) {
                cd.innerHTML = 'Noch <strong>' + rem + '</strong> Sekunde' + (rem === 1 ? '' : 'n') + '...';
            } else {
                cd.innerHTML = '<strong>Fertig!</strong>';
                clearInterval(iv);
                sDiv.style.display = 'none';
                rDiv.style.display  = 'block';
            }
        }, 1000);
    }());
    </script>

    <?php endif; ?>

    </div><!-- /.ob-body -->
</div><!-- /.ob-card -->

</div><!-- /.ob-wrap -->
</div><!-- /.main-content -->

<script>
// Initialise checkbox highlight for already-checked platform items (any step)
document.querySelectorAll('.ob-platform-item input[type=checkbox]:checked').forEach(function(cb) {
    cb.closest('.ob-platform-item').classList.add('checked');
});
// Listen for future changes (works on all steps that have the grid)
document.querySelectorAll('.ob-platform-item input[type=checkbox]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        this.closest('.ob-platform-item').classList.toggle('checked', this.checked);
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>