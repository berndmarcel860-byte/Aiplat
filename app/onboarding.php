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
        $_SESSION['error'] = "Invalid security token.";
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
                    if (empty($_POST[$f])) throw new Exception("Please complete all address fields.");

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
            // ≤5 years → third (Premium), >5 years → highest (VIP/Unlimited)
            if ($yearsSinceLoss <= 1) {
                $recommendedPackage = $allPackages[0] ?? null;
            } elseif ($yearsSinceLoss <= 3) {
                $recommendedPackage = $allPackages[1] ?? $allPackages[0] ?? null;
            } elseif ($yearsSinceLoss <= 5) {
                $recommendedPackage = $allPackages[2] ?? $allPackages[1] ?? null;
            } else {
                // Over 5 years → highest/unlimited package (last non-trial in list)
                // Find the most expensive non-free package
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
            // Default: recommend first package
            $recommendedPackage = $allPackages[0] ?? null;
        }
    } catch (PDOException $e) {
        error_log("Package load error: " . $e->getMessage());
    }
}

require_once __DIR__ . '/header.php';
if (!empty($_SESSION['error'])) {
    echo '<div class="alert alert-danger">'.htmlspecialchars($_SESSION['error']).'</div>';
    unset($_SESSION['error']);
}
?>

<!-- =========================================================
 FRONTEND HTML SECTION - Modern Responsive Design
========================================================= -->
<div class="main-content">
<div class="container" style="max-width: 800px;">
<div class="card shadow-lg" style="border-radius: 15px; border: none;">
<div class="card-body p-4">

<!-- Modern Progress Indicator -->
<div class="mb-5">
<div class="progress" style="height: 8px; border-radius: 10px;">
<div class="progress-bar bg-gradient-primary" style="width:<?= ($step / $maxSteps) * 100 ?>%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
</div>
<div class="d-flex justify-content-between mt-3">
<?php 
$stepIcons = ['📋', '🏠', '💳', '✅'];
$stepLabels = ['Falldetails', 'Adresse', 'Zahlung', 'Abschluss'];
for ($i=1;$i<=$maxSteps;$i++): 
    $active = $i <= $step;
?>
<div class="text-center">
    <div class="step-icon mb-2" style="font-size: 2rem;"><?= $stepIcons[$i-1] ?></div>
    <span class="<?= $active ? 'text-primary font-weight-bold' : 'text-muted' ?>" style="font-size: 0.9rem;">
        <?= $stepLabels[$i-1] ?>
    </span>
</div>
<?php endfor; ?>
</div></div>

<?php if ($step == 1): ?>
<!-- ============================================================
 STEP 1: Case Details
============================================================ -->
<h4 class="mb-4">📋 Erzählen Sie uns von Ihrem Fall</h4>

<form method="post" action="onboarding.php?step=<?= $step ?>">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <!-- Lost Amount -->
    <div class="form-group">
        <label class="font-weight-bold">💰 Verlorener Betrag (EUR) <span class="text-danger">*</span></label>
        <select name="lost_amount" class="form-control form-control-lg" required>
            <option value="">Betrag auswählen...</option>
            <?php
            $amounts = [
                1000 => 'Weniger als €1.000',
                5000 => '€1.000 - €5.000',
                10000 => '€5.000 - €10.000',
                25000 => '€10.000 - €25.000',
                50000 => '€25.000 - €50.000',
                100000 => '€50.000 - €100.000',
                250000 => '€100.000 - €250.000',
                500000 => 'Mehr als €250.000'
            ];
            foreach ($amounts as $v => $label):
                $sel = ($saved['lost_amount'] ?? '') == $v ? 'selected' : '';
            ?>
                <option value="<?= $v ?>" <?= $sel ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Where Lost -->
    <div class="form-group">
        <label class="font-weight-bold">📍 Wo wurden die Gelder verloren? <span class="text-danger">*</span></label>
        <input type="text" name="where_lost" class="form-control form-control-lg" 
               value="<?= htmlspecialchars($saved['where_lost'] ?? '') ?>" 
               placeholder="z.B. Binance, Coinbase, Trading-Plattform XYZ..." 
               required>
        <small class="text-muted">Name der Plattform, Börse oder Ort des Verlusts</small>
    </div>

    <!-- Platforms -->
    <div class="form-group">
        <label class="font-weight-bold">🏢 Verwendete Plattformen <span class="text-danger">*</span></label>
        <select name="platforms[]" class="form-control form-control-lg" multiple required>
            <?php
            $chosen = !empty($saved['platforms']) ? json_decode($saved['platforms'], true) : [];
            foreach ($platforms as $p):
                $sel = in_array($p['id'], $chosen) ? 'selected' : '';
            ?>
                <option value="<?= $p['id'] ?>" <?= $sel ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted">Halten Sie Strg/Cmd gedrückt, um mehrere auszuwählen</small>
    </div>

    <!-- Year Lost -->
    <div class="form-group">
        <label class="font-weight-bold">📅 Jahr des Verlusts <span class="text-danger">*</span></label>
        <select name="year_lost" class="form-control form-control-lg" required>
            <option value="">Jahr auswählen...</option>
            <?php for ($y = date('Y'); $y >= 2000; $y--):
                $sel = ($saved['year_lost'] ?? '') == $y ? 'selected' : ''; ?>
                <option value="<?= $y ?>" <?= $sel ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <!-- Description -->
    <div class="form-group">
        <label class="font-weight-bold">📝 Kurze Beschreibung <span class="text-danger">*</span></label>
        <textarea name="description" class="form-control form-control-lg" rows="4" 
                  placeholder="Beschreiben Sie, was passiert ist und wie Sie betrogen wurden..." 
                  required><?= htmlspecialchars($saved['case_description'] ?? '') ?></textarea>
        <small class="text-muted">Je mehr Details, desto besser können wir Ihnen helfen</small>
    </div>

    <div class="text-right">
        <button class="btn btn-primary btn-lg px-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 25px;">
            Weiter <i class="fas fa-arrow-right ml-2"></i>
        </button>
    </div>
</form>

<?php elseif ($step == 2): ?>
<!-- ============================================================
 STEP 2: Address Information
============================================================ -->
<h4 class="mb-4">🏠 Ihre Adresse</h4>

<form method="post" action="onboarding.php?step=<?= $step ?>">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="form-group">
        <label class="font-weight-bold">🌍 Land <span class="text-danger">*</span></label>
        <select name="country" class="form-control form-control-lg" required>
            <option value="">Land auswählen...</option>
            <option value="Deutschland" <?= ($saved['country'] ?? '') == 'Deutschland' ? 'selected' : '' ?>>Deutschland</option>
            <option value="Österreich" <?= ($saved['country'] ?? '') == 'Österreich' ? 'selected' : '' ?>>Österreich</option>
            <option value="Schweiz" <?= ($saved['country'] ?? '') == 'Schweiz' ? 'selected' : '' ?>>Schweiz</option>
            <option value="Frankreich" <?= ($saved['country'] ?? '') == 'Frankreich' ? 'selected' : '' ?>>Frankreich</option>
            <option value="Italien" <?= ($saved['country'] ?? '') == 'Italien' ? 'selected' : '' ?>>Italien</option>
            <option value="Spanien" <?= ($saved['country'] ?? '') == 'Spanien' ? 'selected' : '' ?>>Spanien</option>
            <option value="Niederlande" <?= ($saved['country'] ?? '') == 'Niederlande' ? 'selected' : '' ?>>Niederlande</option>
            <option value="Belgien" <?= ($saved['country'] ?? '') == 'Belgien' ? 'selected' : '' ?>>Belgien</option>
            <option value="Luxemburg" <?= ($saved['country'] ?? '') == 'Luxemburg' ? 'selected' : '' ?>>Luxemburg</option>
            <option value="Dänemark" <?= ($saved['country'] ?? '') == 'Dänemark' ? 'selected' : '' ?>>Dänemark</option>
            <option value="Andere" <?= ($saved['country'] ?? '') == 'Andere' ? 'selected' : '' ?>>Andere</option>
        </select>
    </div>

    <div class="form-group">
        <label class="font-weight-bold">🏘️ Straße und Hausnummer <span class="text-danger">*</span></label>
        <input name="street" class="form-control form-control-lg" 
               value="<?= htmlspecialchars($saved['street'] ?? '') ?>" 
               placeholder="z.B. Hauptstraße 123" 
               required>
    </div>

    <div class="form-row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="font-weight-bold">📮 Postleitzahl <span class="text-danger">*</span></label>
                <input name="postal_code" class="form-control form-control-lg" 
                       value="<?= htmlspecialchars($saved['postal_code'] ?? '') ?>" 
                       placeholder="60322" 
                       required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="font-weight-bold">🏙️ Stadt / Bundesland <span class="text-danger">*</span></label>
                <input name="state" class="form-control form-control-lg" 
                       value="<?= htmlspecialchars($saved['state'] ?? '') ?>" 
                       placeholder="Frankfurt am Main" 
                       required>
            </div>
        </div>
    </div>

    <div class="text-right mt-3">
        <button class="btn btn-primary btn-lg px-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 25px;">
            Weiter <i class="fas fa-arrow-right ml-2"></i>
        </button>
    </div>
</form>

<?php elseif ($step == 3): ?>
<!-- ============================================================
 STEP 3: Payment Methods (Bank OR Crypto - AT LEAST ONE REQUIRED)
============================================================ -->
<h4 class="mb-4" style="color: #667eea; font-weight: 600;">💳 Zahlungsmethoden hinzufügen</h4>

<!-- Algorithm Explanation Alert -->
<div class="alert alert-info mb-4" style="border-left: 4px solid #17a2b8; background: linear-gradient(135deg, #d1ecf1 0%, #e7f9fc 100%);">
    <h5 class="mb-3"><i class="fas fa-robot"></i> <strong>Warum benötigen wir Ihre Zahlungsdaten?</strong></h5>
    <p class="mb-2">
        <i class="fas fa-brain"></i> Unser <strong>KI-Algorithmus</strong> durchsucht die Blockchain nach Spuren Ihrer verlorenen Gelder.
    </p>
    <p class="mb-2">
        <i class="fas fa-search"></i> Für eine erfolgreiche Suche benötigen wir <strong>ENTWEDER</strong> ein Bankkonto <strong>ODER</strong> eine Krypto-Wallet (oder beides für mehr Flexibilität).
    </p>
    <p class="mb-2">
        <i class="fas fa-shield-alt"></i> Diese Daten ermöglichen es uns, gefundene Gelder sicher Ihrem Konto zuzuordnen und Auszahlungen zu verarbeiten.
    </p>
    <p class="mb-0">
        <i class="fas fa-check-circle"></i> <strong>Sie müssen NICHT beide hinzufügen</strong> - eine Zahlungsmethode reicht aus!
    </p>
</div>

<div class="alert alert-warning mb-4" style="border-left: 4px solid #ffc107;">
    <i class="fas fa-info-circle"></i>
    <strong>Hinweis:</strong> Sie können später jederzeit weitere Zahlungsmethoden in Ihrem Profil hinzufügen.
</div>

<form method="post" action="onboarding.php?step=<?= $step ?>" id="paymentForm">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <!-- Bank Account Section (OPTIONAL) -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0;">
            <h5 class="mb-0">
                <i class="fas fa-university mr-2"></i>
                🏦 Bankkonto (Optional)
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="form-group">
                <label class="font-weight-bold">Bankname</label>
                <input type="text" name="bank_name" class="form-control form-control-lg" 
                       value="<?= htmlspecialchars($saved['bank_name'] ?? '') ?>" 
                       placeholder="z.B. Sparkasse, Deutsche Bank">
            </div>

            <div class="form-group">
                <label class="font-weight-bold">Kontoinhaber</label>
                <input type="text" name="account_holder" class="form-control form-control-lg" 
                       value="<?= htmlspecialchars($saved['account_holder'] ?? '') ?>" 
                       placeholder="Vollständiger Name wie auf dem Bankkonto">
            </div>

            <div class="form-group">
                <label class="font-weight-bold">IBAN</label>
                <input type="text" name="iban" class="form-control form-control-lg" 
                       value="<?= htmlspecialchars($saved['iban'] ?? '') ?>" 
                       placeholder="DE89 3704 0044 0532 0130 00"
                       pattern="[A-Z]{2}\d{2}[A-Z\d]{1,30}">
                <small class="text-muted">Internationale Bankkontonummer</small>
            </div>

            <div class="form-group">
                <label class="font-weight-bold">BIC / SWIFT</label>
                <input type="text" name="bic" class="form-control form-control-lg" 
                       value="<?= htmlspecialchars($saved['bic'] ?? '') ?>" 
                       placeholder="COBADEFFXXX">
                <small class="text-muted">Bank-Identifikationscode</small>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Info:</strong> Ihr Bankkonto wird vor dem Empfang von Geldern verifiziert.
            </div>
        </div>
    </div>

    <!-- Cryptocurrency Section (OPTIONAL) -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0;">
            <h5 class="mb-0">
                <i class="fab fa-bitcoin mr-2"></i>
                💰 Krypto-Wallet (Optional)
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="form-group">
                <label class="font-weight-bold">Kryptowährung</label>
                <select name="cryptocurrency" class="form-control form-control-lg">
                    <option value="">Kryptowährung auswählen...</option>
                    <option value="BTC" <?= ($saved['cryptocurrency'] ?? '') == 'BTC' ? 'selected' : '' ?>>Bitcoin (BTC)</option>
                    <option value="ETH" <?= ($saved['cryptocurrency'] ?? '') == 'ETH' ? 'selected' : '' ?>>Ethereum (ETH)</option>
                    <option value="USDT" <?= ($saved['cryptocurrency'] ?? '') == 'USDT' ? 'selected' : '' ?>>Tether (USDT)</option>
                    <option value="USDC" <?= ($saved['cryptocurrency'] ?? '') == 'USDC' ? 'selected' : '' ?>>USD Coin (USDC)</option>
                    <option value="BNB" <?= ($saved['cryptocurrency'] ?? '') == 'BNB' ? 'selected' : '' ?>>Binance Coin (BNB)</option>
                    <option value="ADA" <?= ($saved['cryptocurrency'] ?? '') == 'ADA' ? 'selected' : '' ?>>Cardano (ADA)</option>
                    <option value="LTC" <?= ($saved['cryptocurrency'] ?? '') == 'LTC' ? 'selected' : '' ?>>Litecoin (LTC)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="font-weight-bold">Netzwerk</label>
                <select name="network" class="form-control form-control-lg">
                    <option value="">Netzwerk auswählen...</option>
                    <option value="Bitcoin Network" <?= ($saved['network'] ?? '') == 'Bitcoin Network' ? 'selected' : '' ?>>Bitcoin Network</option>
                    <option value="Ethereum (ERC-20)" <?= ($saved['network'] ?? '') == 'Ethereum (ERC-20)' ? 'selected' : '' ?>>Ethereum (ERC-20)</option>
                    <option value="Tron (TRC-20)" <?= ($saved['network'] ?? '') == 'Tron (TRC-20)' ? 'selected' : '' ?>>Tron (TRC-20)</option>
                    <option value="Binance Smart Chain (BEP-20)" <?= ($saved['network'] ?? '') == 'Binance Smart Chain (BEP-20)' ? 'selected' : '' ?>>Binance Smart Chain (BEP-20)</option>
                    <option value="Polygon Network" <?= ($saved['network'] ?? '') == 'Polygon Network' ? 'selected' : '' ?>>Polygon Network</option>
                    <option value="Solana Network" <?= ($saved['network'] ?? '') == 'Solana Network' ? 'selected' : '' ?>>Solana Network</option>
                </select>
                <small class="text-muted">Wählen Sie das Blockchain-Netzwerk für Ihr Wallet</small>
            </div>

            <div class="form-group">
                <label class="font-weight-bold">Wallet-Adresse</label>
                <input type="text" name="wallet_address" class="form-control form-control-lg" 
                       value="<?= htmlspecialchars($saved['wallet_address'] ?? '') ?>" 
                       placeholder="0xabcd1234..." 
                       style="font-family: monospace;">
                <small class="text-muted">Ihre Kryptowährungs-Wallet-Adresse</small>
            </div>

            <div class="alert alert-warning">
                <i class="fas fa-shield-alt"></i>
                <strong>Info:</strong> Ihr Wallet wird durch einen Satoshi-Test (kleine Test-Transaktion) verifiziert.
            </div>
        </div>
    </div>

    <div class="text-right mt-4">
        <button type="submit" class="btn btn-primary btn-lg px-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 25px;">
            Einrichtung abschließen <i class="fas fa-check ml-2"></i>
        </button>
    </div>
</form>

<?php elseif ($step == 4): ?>
<!-- ============================================================
 STEP 4: Package Recommendation - Finding Best Package
============================================================ -->
<h4 class="mb-4">🎯 Wir finden das beste Paket für Sie</h4>

<!-- Searching animation (shown for 15 seconds) -->
<div id="searchingPackage" class="text-center py-5">
    <div class="mb-4">
        <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
            <span class="sr-only">Suche läuft...</span>
        </div>
    </div>
    <h5 class="mb-2">Wir analysieren Ihren Fall...</h5>
    <p class="text-muted mb-4">Bitte warten Sie, während wir das optimale Paket für Sie ermitteln.</p>
    <div class="progress" style="height: 10px; border-radius: 10px; max-width: 400px; margin: 0 auto;">
        <div id="searchProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
             role="progressbar" style="width: 0%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        </div>
    </div>
    <p class="text-muted mt-2 small" id="searchCountdown">Noch <strong>15</strong> Sekunden...</p>
</div>

<!-- Recommended package (hidden initially, shown after 15 sec) -->
<div id="packageResult" style="display:none;">
    <?php if ($recommendedPackage): ?>
    <div class="alert alert-success mb-4" style="border-left: 4px solid #52c41a; border-radius: 10px;">
        <i class="fas fa-check-circle mr-2"></i>
        <strong>Empfohlenes Paket gefunden!</strong> Basierend auf Ihrem Verlustjahr empfehlen wir Ihnen:
    </div>

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px; border-left: 4px solid #667eea !important;">
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
                <div>
                    <span class="badge badge-primary mb-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-size: 0.8rem; padding: 6px 12px; border-radius: 20px;">
                        ⭐ Empfohlen für Sie
                    </span>
                    <h4 class="mb-1" style="font-weight: 700; color: #2c3e50;">
                        <?= htmlspecialchars($recommendedPackage['name']) ?>
                    </h4>
                    <p class="text-muted mb-0"><?= htmlspecialchars($recommendedPackage['description'] ?? '') ?></p>
                </div>
                <div class="text-right mt-2">
                    <span class="h3 font-weight-bold" style="color: #667eea;">
                        €<?= number_format((float)$recommendedPackage['price'], 0, ',', '.') ?>
                    </span>
                </div>
            </div>

            <?php if (!empty($recommendedPackage['features'])): ?>
            <ul class="list-unstyled mb-3">
                <?php foreach (json_decode($recommendedPackage['features'], true) ?? [] as $feature): ?>
                <li class="mb-1">
                    <i class="fas fa-check text-success mr-2"></i>
                    <?= htmlspecialchars($feature) ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <div class="row text-center">
                <?php if (!empty($recommendedPackage['recovery_speed'])): ?>
                <div class="col-6 col-md-6">
                    <div class="p-2 rounded" style="background: rgba(102, 126, 234, 0.08);">
                        <div class="small text-muted">Bearbeitungszeit</div>
                        <div class="font-weight-bold" style="color: #667eea;"><?= htmlspecialchars($recommendedPackage['recovery_speed']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($recommendedPackage['support_level'])): ?>
                <div class="col-6 col-md-6">
                    <div class="p-2 rounded" style="background: rgba(102, 126, 234, 0.08);">
                        <div class="small text-muted">Support</div>
                        <div class="font-weight-bold" style="color: #667eea;"><?= htmlspecialchars($recommendedPackage['support_level']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
        <a href="packages.php" class="btn btn-outline-primary btn-lg px-4" style="border-radius: 25px;">
            <i class="fas fa-th-list mr-2"></i>Alle Pakete ansehen
        </a>
        <form method="post" action="onboarding.php?step=<?= $step ?>" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button class="btn btn-primary btn-lg px-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 25px;">
                Registrierung abschließen <i class="fas fa-check-circle ml-2"></i>
            </button>
        </form>
    </div>

    <?php else: ?>
    <div class="alert alert-info mb-4" style="border-radius: 10px;">
        <i class="fas fa-info-circle mr-2"></i>
        Für weitere Informationen zu unseren Paketen besuchen Sie bitte die Paketübersicht.
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <a href="packages.php" class="btn btn-outline-primary btn-lg px-4" style="border-radius: 25px;">
            <i class="fas fa-th-list mr-2"></i>Pakete ansehen
        </a>
        <form method="post" action="onboarding.php?step=<?= $step ?>" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button class="btn btn-primary btn-lg px-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 25px;">
                Registrierung abschließen <i class="fas fa-check-circle ml-2"></i>
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    var totalSeconds = 15;
    var elapsed = 0;
    var progressBar = document.getElementById('searchProgressBar');
    var countdown = document.getElementById('searchCountdown');
    var searchDiv = document.getElementById('searchingPackage');
    var resultDiv = document.getElementById('packageResult');

    var interval = setInterval(function() {
        elapsed++;
        var pct = Math.round((elapsed / totalSeconds) * 100);
        progressBar.style.width = pct + '%';
        var remaining = totalSeconds - elapsed;
        if (remaining > 0) {
            countdown.innerHTML = 'Noch <strong>' + remaining + '</strong> Sekunde' + (remaining === 1 ? '' : 'n') + '...';
        } else {
            countdown.innerHTML = '<strong>Fertig!</strong>';
        }
        if (elapsed >= totalSeconds) {
            clearInterval(interval);
            searchDiv.style.display = 'none';
            resultDiv.style.display = 'block';
        }
    }, 1000);
})();
</script>
<?php endif; ?>


</div></div></div></div>


<style>
/* Modern Onboarding Styles */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 10px 40px rgba(0,0,0,0.15) !important;
}

.form-control-lg {
    border-radius: 10px;
    border: 2px solid #e1e8ed;
    padding: 12px 20px;
    font-size: 1rem;
}

.form-control-lg:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.nav-tabs {
    border: none;
}

.nav-tabs .nav-link {
    transition: all 0.3s ease;
    font-size: 1.1rem;
    padding: 12px 24px;
}

.nav-tabs .nav-link:hover {
    border-bottom: 3px solid #764ba2 !important;
    color: #764ba2 !important;
}

.step-icon {
    transition: transform 0.3s ease;
}

.step-icon:hover {
    transform: scale(1.2);
}

.btn-primary {
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.alert {
    border-radius: 10px;
    border-left: 4px solid;
}

.alert-info {
    border-left-color: #17a2b8;
    background-color: #d1ecf1;
}

.alert-warning {
    border-left-color: #ffc107;
    background-color: #fff3cd;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.text-muted {
    font-size: 0.875rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-body {
        padding: 1.5rem !important;
    }
    
    .step-icon {
        font-size: 1.5rem !important;
    }
    
    .nav-tabs .nav-link {
        font-size: 0.9rem;
        padding: 10px 16px;
    }
}
</style>


<?php require_once __DIR__ . '/footer.php'; ?>