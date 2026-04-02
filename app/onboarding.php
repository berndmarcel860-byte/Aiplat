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
            // STEP 4: Analysis countdown — no server action needed
            // =========================================================
            case 4:
                break;

            // =========================================================
            // STEP 5: Trial package activation (paid packages use AJAX)
            // =========================================================
            case 5:
                $trialPkgId = filter_input(INPUT_POST, 'trial_pkg_id', FILTER_VALIDATE_INT);
                if ($trialPkgId) {
                    // Verify it is a free (trial) package
                    $stmtTrial = $pdo->prepare("SELECT id FROM packages WHERE id = ? AND price = 0");
                    $stmtTrial->execute([$trialPkgId]);
                    if (!$stmtTrial->fetch()) throw new Exception("Ungültiges Trial-Paket.");
                    // Expire existing active packages
                    $pdo->prepare("UPDATE user_packages SET status='expired' WHERE user_id=? AND status='active'")->execute([$userId]);
                    // Activate trial (48 hours)
                    $pdo->prepare("INSERT INTO user_packages (user_id, package_id, start_date, end_date, status)
                                   VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 48 HOUR), 'active')")
                        ->execute([$userId, $trialPkgId]);
                    // Mark onboarding complete
                    $pdo->prepare("UPDATE user_onboarding SET completed = 1 WHERE user_id=?")->execute([$userId]);
                    header("Location: index.php");
                    exit();
                }
                break;

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
$maxSteps = 5;

try {
    $platforms = $pdo->query("SELECT id,name FROM scam_platforms WHERE is_active=1")->fetchAll();
    $data = $pdo->prepare("SELECT * FROM user_onboarding WHERE user_id=?");
    $data->execute([$_SESSION['user_id']]);
    $saved = $data->fetch();
} catch (PDOException $e) {
    die("Database error.");
}

// === Load packages for step 5 ===
$ob_packages = [];
try {
    $ob_packages = $pdo->query("SELECT * FROM packages ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ob_packages = [];
}

// Determine recommended package index based on year_lost
$ob_yearLost   = (int)($saved['year_lost'] ?? 0);
$ob_yearsSince = $ob_yearLost > 0 ? (int)date('Y') - $ob_yearLost : 0;
// Paid packages only (skip trial at price=0 for recommendation)
$ob_paidPkgs   = array_values(array_filter($ob_packages, fn($p) => (float)$p['price'] > 0));
$ob_trialPkgs  = array_values(array_filter($ob_packages, fn($p) => (float)$p['price'] == 0));
if      ($ob_yearsSince <= 1)  $ob_recIdx = 0;
elseif  ($ob_yearsSince <= 3)  $ob_recIdx = 1;
elseif  ($ob_yearsSince <= 5)  $ob_recIdx = 2;
else                           $ob_recIdx = max(0, count($ob_paidPkgs) - 1);

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
            4 => ['label' => 'Analyse',         'icon' => 'anticon-experiment'],
            5 => ['label' => 'Ihr Paket',       'icon' => 'anticon-rocket'],
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
     SCHRITT 4: Algorithmus-Analyse (15s Countdown)
    ============================================================ -->
    <style>
    /* ── Countdown step ── */
    .ob-analysis-wrap {
        text-align: center;
        padding: 10px 0 20px;
    }
    .ob-analysis-ring {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto 28px;
    }
    .ob-analysis-ring svg {
        transform: rotate(-90deg);
    }
    .ob-analysis-ring .ring-bg {
        fill: none;
        stroke: var(--ob-border);
        stroke-width: 7;
    }
    .ob-analysis-ring .ring-fill {
        fill: none;
        stroke: url(#ringGrad);
        stroke-width: 7;
        stroke-linecap: round;
        stroke-dasharray: 345;
        stroke-dashoffset: 0;
        transition: stroke-dashoffset 1s linear;
    }
    .ob-analysis-ring .ring-num {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 800;
        color: var(--ob-primary);
        line-height: 1;
    }
    .ob-analysis-ring .ring-sec {
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--ob-muted);
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .ob-analysis-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--ob-text);
        margin-bottom: 6px;
    }
    .ob-analysis-sub {
        font-size: 0.9rem;
        color: var(--ob-muted);
        margin-bottom: 28px;
        max-width: 480px;
        margin-left: auto;
        margin-right: auto;
    }
    .ob-analysis-steps {
        list-style: none;
        padding: 0;
        margin: 0 auto;
        max-width: 420px;
        text-align: left;
    }
    .ob-analysis-steps li {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 11px 16px;
        border-radius: 10px;
        margin-bottom: 8px;
        background: var(--ob-bg);
        border: 1px solid var(--ob-border);
        font-size: 0.92rem;
        color: var(--ob-muted);
        opacity: 0;
        transform: translateX(-12px);
        transition: opacity .4s ease, transform .4s ease, background .3s, border-color .3s, color .3s;
    }
    .ob-analysis-steps li .step-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--ob-border);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background .3s;
    }
    .ob-analysis-steps li.visible {
        opacity: 1;
        transform: translateX(0);
    }
    .ob-analysis-steps li.done {
        background: rgba(41,80,168,.05);
        border-color: rgba(41,80,168,.2);
        color: var(--ob-text);
    }
    .ob-analysis-steps li.done .step-icon {
        background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent));
    }
    .ob-analysis-steps li.active {
        background: rgba(45,169,227,.07);
        border-color: rgba(45,169,227,.3);
        color: var(--ob-text);
    }
    .ob-analysis-steps li.active .step-icon {
        background: linear-gradient(135deg, var(--ob-accent), #56d4f8);
    }
    </style>

    <div class="ob-analysis-wrap">

        <!-- Circular countdown ring -->
        <div class="ob-analysis-ring">
            <svg width="120" height="120" viewBox="0 0 120 120">
                <defs>
                    <linearGradient id="ringGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%"   stop-color="#2950a8"/>
                        <stop offset="100%" stop-color="#2da9e3"/>
                    </linearGradient>
                </defs>
                <circle class="ring-bg"   cx="60" cy="60" r="54"/>
                <circle class="ring-fill" cx="60" cy="60" r="54" id="obRingFill"/>
            </svg>
            <div class="ring-num">
                <span id="obCountNum">15</span>
                <span class="ring-sec">sek</span>
            </div>
        </div>

        <div class="ob-analysis-title">
            <i class="anticon anticon-experiment mr-2" style="color:var(--ob-accent);"></i>
            Algorithmus analysiert Ihren Fall
        </div>
        <p class="ob-analysis-sub">
            Bitte warten Sie, während unser KI-Algorithmus die optimale Recovery-Strategie
            für Ihren Fall berechnet und das passende Paket auswählt.
        </p>

        <!-- Animated checklist -->
        <ul class="ob-analysis-steps" id="obAnalysisSteps">
            <li id="aStep1">
                <span class="step-icon"><i class="anticon anticon-file-search" style="color:#fff;font-size:14px;"></i></span>
                Fall-Details werden analysiert …
            </li>
            <li id="aStep2">
                <span class="step-icon"><i class="anticon anticon-history" style="color:#fff;font-size:14px;"></i></span>
                Verlusthistorie &amp; Zeitraum wird geprüft …
            </li>
            <li id="aStep3">
                <span class="step-icon"><i class="anticon anticon-line-chart" style="color:#fff;font-size:14px;"></i></span>
                Recovery-Potenzial wird berechnet …
            </li>
            <li id="aStep4">
                <span class="step-icon"><i class="anticon anticon-database" style="color:#fff;font-size:14px;"></i></span>
                Verfügbare Recovery-Pakete werden bewertet …
            </li>
            <li id="aStep5">
                <span class="step-icon"><i class="anticon anticon-star" style="color:#fff;font-size:14px;"></i></span>
                Personalisierte Empfehlung wird erstellt …
            </li>
        </ul>

    </div><!-- /ob-analysis-wrap -->

    <script>
    (function() {
        var total  = 15;
        var remain = total;
        var circumference = 2 * Math.PI * 54; // ≈ 339.3
        var ring   = document.getElementById('obRingFill');
        var numEl  = document.getElementById('obCountNum');

        // Steps: [elementId, visibleAt, doneAt]
        var steps = [
            ['aStep1',  0,  3],
            ['aStep2',  3,  6],
            ['aStep3',  6,  9],
            ['aStep4',  9, 12],
            ['aStep5', 12, 15],
        ];

        // Initialise ring
        ring.style.strokeDasharray  = circumference;
        ring.style.strokeDashoffset = 0;

        var elapsed = 0;
        var interval = setInterval(function() {
            elapsed++;
            remain = total - elapsed;

            // Update number
            numEl.textContent = remain;

            // Update ring (empties as time passes)
            var pct = elapsed / total;
            ring.style.strokeDashoffset = pct * circumference;

            // Update step states
            steps.forEach(function(s) {
                var el = document.getElementById(s[0]);
                if (!el) return;
                if (elapsed >= s[2]) {
                    el.classList.remove('active');
                    el.classList.add('visible', 'done');
                } else if (elapsed >= s[1]) {
                    el.classList.add('visible', 'active');
                    el.classList.remove('done');
                }
            });

            if (elapsed >= total) {
                clearInterval(interval);
                window.location.href = 'onboarding.php?step=5';
            }
        }, 1000);
    })();
    </script>

    <?php elseif ($step == 5): ?>
    <!-- ============================================================
     SCHRITT 5: Paket auswählen
    ============================================================ -->
    <style>
    /* ── Package cards ── */
    .ob-pkg-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 8px;
    }
    @media (max-width: 575px) {
        .ob-pkg-grid { grid-template-columns: 1fr 1fr; }
    }
    .ob-pkg-card {
        border: 2px solid var(--ob-border);
        border-radius: 14px;
        background: #fff;
        overflow: hidden;
        position: relative;
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s;
        display: flex;
        flex-direction: column;
    }
    .ob-pkg-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 28px rgba(41,80,168,.15);
        border-color: var(--ob-accent);
    }
    .ob-pkg-card.ob-pkg-recommended {
        border-color: var(--ob-primary);
        box-shadow: 0 4px 20px rgba(41,80,168,.2);
    }
    .ob-pkg-card.ob-pkg-trial {
        border-color: #ffc107;
    }
    .ob-pkg-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent));
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
        padding: 3px 10px;
        border-radius: 20px;
    }
    .ob-pkg-badge.trial-badge {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    .ob-pkg-header {
        padding: 20px 18px 14px;
        text-align: center;
        border-bottom: 1px solid var(--ob-border);
    }
    .ob-pkg-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent));
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        color: #fff;
        font-size: 22px;
    }
    .ob-pkg-icon.trial-icon {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    .ob-pkg-name {
        font-size: 1rem;
        font-weight: 700;
        color: var(--ob-text);
        margin-bottom: 4px;
    }
    .ob-pkg-price {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--ob-primary);
        line-height: 1.1;
    }
    .ob-pkg-price .ob-pkg-currency {
        font-size: 0.9rem;
        font-weight: 600;
        vertical-align: super;
    }
    .ob-pkg-price.trial-price {
        color: #d97706;
    }
    .ob-pkg-body {
        padding: 14px 16px;
        flex: 1;
    }
    .ob-pkg-features {
        list-style: none;
        padding: 0;
        margin: 0;
        font-size: 0.82rem;
    }
    .ob-pkg-features li {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 5px 0;
        color: #4b5563;
        border-bottom: 1px solid rgba(0,0,0,.04);
    }
    .ob-pkg-features li:last-child { border-bottom: none; }
    .ob-pkg-features li i {
        color: var(--ob-primary);
        font-size: 13px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .ob-pkg-features li i.trial-check { color: #d97706; }
    .ob-pkg-footer {
        padding: 14px 16px 18px;
    }
    .ob-pkg-btn {
        display: block;
        width: 100%;
        padding: 11px 10px;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        text-align: center;
        transition: all .2s ease;
        background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent));
        color: #fff;
        box-shadow: 0 3px 12px rgba(41,80,168,.25);
    }
    .ob-pkg-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(41,80,168,.35);
    }
    .ob-pkg-btn.trial-btn {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 3px 12px rgba(245,158,11,.3);
    }
    .ob-pkg-btn.trial-btn:hover {
        box-shadow: 0 6px 18px rgba(245,158,11,.45);
    }
    .ob-pkg-duration {
        font-size: 0.75rem;
        color: var(--ob-muted);
        text-align: center;
        margin-top: 8px;
    }
    /* Modal overrides for inline style */
    #obPayModal .modal-content {
        border-radius: 16px;
        border: 1px solid var(--ob-border);
    }
    #obPayModal .modal-header {
        background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent));
        border-radius: 14px 14px 0 0;
        color: #fff;
        padding: 18px 24px;
    }
    #obPayModal .modal-header h5,
    #obPayModal .modal-header button,
    #obPayModal .modal-header i { color: #fff !important; }
    .ob-pay-section {
        background: var(--ob-bg);
        border: 1px solid var(--ob-border);
        border-radius: 12px;
        padding: 16px 18px;
        margin-top: 14px;
    }
    .ob-pay-section h6 {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--ob-primary);
        margin-bottom: 10px;
    }
    </style>

    <div class="ob-section-title">
        <span class="ob-icon"><i class="anticon anticon-rocket" style="font-size:18px;"></i></span>
        Ihr empfohlenes Recovery-Paket
    </div>
    <p class="ob-section-desc">
        Basierend auf Ihrem Fall und dem Verlustjahr <strong><?= (int)($saved['year_lost'] ?? 0) ?></strong>
        hat unser Algorithmus das optimale Paket für Ihre Recovery ermittelt.
        Wählen Sie ein Paket, um fortzufahren.
    </p>

    <?php if (empty($ob_packages)): ?>
        <div class="ob-warn">Keine Pakete verfügbar. Bitte kontaktieren Sie den Support.</div>
    <?php else: ?>

    <!-- Package grid: paid packages -->
    <div class="ob-pkg-grid">
    <?php foreach ($ob_paidPkgs as $pIdx => $pkg):
        $isRec = ($pIdx === $ob_recIdx);
        $durDays = (int)($pkg['duration_days'] ?? 30);
        $pkgIdEnc = (int)$pkg['id'];
        $pkgNameEnc = htmlspecialchars($pkg['name'], ENT_QUOTES);
        $pkgPriceEnc = number_format((float)$pkg['price'], 2);
    ?>
    <div class="ob-pkg-card <?= $isRec ? 'ob-pkg-recommended' : '' ?>">
        <?php if ($isRec): ?>
            <span class="ob-pkg-badge">&#9733; Empfohlen</span>
        <?php endif; ?>
        <div class="ob-pkg-header">
            <div class="ob-pkg-icon"><i class="anticon anticon-safety-certificate"></i></div>
            <div class="ob-pkg-name"><?= $pkgNameEnc ?></div>
            <div class="ob-pkg-price">
                <span class="ob-pkg-currency">€</span><?= $pkgPriceEnc ?>
            </div>
        </div>
        <div class="ob-pkg-body">
            <ul class="ob-pkg-features">
                <?php if (!empty($pkg['description'])): ?>
                <li><i class="anticon anticon-info-circle"></i> <?= htmlspecialchars($pkg['description'], ENT_QUOTES) ?></li>
                <?php endif; ?>
                <li><i class="anticon anticon-calendar"></i> <?= $durDays ?> Tage Laufzeit</li>
                <li><i class="anticon anticon-team"></i> <?= htmlspecialchars($pkg['support_level'] ?? 'Standard', ENT_QUOTES) ?> Support</li>
                <li><i class="anticon anticon-file-done"></i> Max. <?= htmlspecialchars($pkg['case_limit'] ?? '1', ENT_QUOTES) ?> <?= ($pkg['case_limit'] ?? 1) == 1 ? 'Fall' : 'Fälle' ?></li>
                <li><i class="anticon anticon-check-circle"></i> Dedizierter Case-Manager</li>
                <li><i class="anticon anticon-lock"></i> Verschlüsselte Datenhaltung</li>
            </ul>
        </div>
        <div class="ob-pkg-footer">
            <button class="ob-pkg-btn ob-select-paid-btn"
                    data-id="<?= $pkgIdEnc ?>"
                    data-name="<?= $pkgNameEnc ?>"
                    data-price="<?= $pkgPriceEnc ?>">
                <?= $isRec ? '&#9733; Jetzt starten' : 'Paket wählen' ?>
            </button>
            <div class="ob-pkg-duration"><?= $durDays ?> Tage · Einmalige Zahlung</div>
        </div>
    </div>
    <?php endforeach; ?>
    </div><!-- /ob-pkg-grid -->

    <!-- Trial package(s) below, separated -->
    <?php if (!empty($ob_trialPkgs)): ?>
    <div style="margin-top:24px; padding-top:20px; border-top: 1px dashed var(--ob-border);">
        <p style="text-align:center; font-size:0.85rem; color:var(--ob-muted); margin-bottom:16px;">
            — oder zunächst kostenlos testen —
        </p>
        <div class="ob-pkg-grid" style="max-width:260px; margin:0 auto;">
        <?php foreach ($ob_trialPkgs as $tPkg):
            $tIdEnc = (int)$tPkg['id'];
            $tNameEnc = htmlspecialchars($tPkg['name'], ENT_QUOTES);
        ?>
        <div class="ob-pkg-card ob-pkg-trial" style="max-width:260px;">
            <span class="ob-pkg-badge trial-badge">Kostenlos</span>
            <div class="ob-pkg-header">
                <div class="ob-pkg-icon trial-icon"><i class="anticon anticon-experiment"></i></div>
                <div class="ob-pkg-name"><?= $tNameEnc ?></div>
                <div class="ob-pkg-price trial-price">48h <span style="font-size:0.9rem;font-weight:600;">kostenlos</span></div>
            </div>
            <div class="ob-pkg-body">
                <ul class="ob-pkg-features">
                    <?php if (!empty($tPkg['description'])): ?>
                    <li><i class="anticon anticon-info-circle trial-check"></i> <?= htmlspecialchars($tPkg['description'], ENT_QUOTES) ?></li>
                    <?php endif; ?>
                    <li><i class="anticon anticon-clock-circle trial-check"></i> 48 Stunden Testzugang</li>
                    <li><i class="anticon anticon-eye trial-check"></i> Dashboard-Vorschau</li>
                    <li><i class="anticon anticon-minus-circle" style="color:#9ca3af;"></i> Keine Auszahlungen</li>
                    <li><i class="anticon anticon-minus-circle" style="color:#9ca3af;"></i> Eingeschränkter Zugriff</li>
                </ul>
            </div>
            <div class="ob-pkg-footer">
                <form method="post" action="onboarding.php?step=5">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="trial_pkg_id" value="<?= $tIdEnc ?>">
                    <button type="submit" class="ob-pkg-btn trial-btn">
                        <i class="anticon anticon-experiment mr-1"></i>Testversion starten
                    </button>
                </form>
                <div class="ob-pkg-duration">48 Stunden · Keine Zahlung</div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?><!-- /packages -->

    <!-- ── Payment Modal (for paid packages) ── -->
    <div class="modal fade" id="obPayModal" tabindex="-1" role="dialog" aria-labelledby="obPayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="obPayModalLabel">
                            <i class="anticon anticon-credit-card mr-2"></i>Paket abonnieren
                        </h5>
                        <p style="margin:4px 0 0; font-size:0.83rem; opacity:.85;" id="obPayModalSub"></p>
                    </div>
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="obPayForm" enctype="multipart/form-data">
                    <input type="hidden" name="package_id" id="obPayPkgId">
                    <div class="modal-body">

                        <!-- Package summary -->
                        <div style="display:flex;align-items:center;gap:14px;padding:14px 16px;background:linear-gradient(135deg,rgba(41,80,168,.06),rgba(45,169,227,.04));border-radius:12px;margin-bottom:18px;border:1px solid rgba(41,80,168,.12);">
                            <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--ob-primary),var(--ob-accent));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="anticon anticon-safety-certificate" style="color:#fff;font-size:20px;"></i>
                            </div>
                            <div>
                                <strong id="obPayPkgName" style="color:var(--ob-text);font-size:1rem;"></strong>
                                <div style="font-size:0.82rem;color:var(--ob-muted);">Einmalige Zahlung · Sichere Abwicklung</div>
                            </div>
                            <div style="margin-left:auto;font-size:1.4rem;font-weight:800;color:var(--ob-primary);">€<span id="obPayPkgPrice"></span></div>
                        </div>

                        <!-- Payment method -->
                        <div class="ob-form-group">
                            <label style="font-weight:700;">Zahlungsmethode <span class="req">*</span></label>
                            <select class="ob-control" name="payment_method" id="obPayMethod" required>
                                <option value="">Zahlungsmethode auswählen …</option>
                                <?php
                                try {
                                    $stmtPm = $pdo->prepare("SELECT * FROM payment_methods WHERE is_active = 1 AND allows_deposit = 1");
                                    $stmtPm->execute();
                                    while ($pm = $stmtPm->fetch(PDO::FETCH_ASSOC)) {
                                        $pmDetails = [
                                            'bank_name'      => $pm['bank_name'] ?? '',
                                            'account_number' => $pm['account_number'] ?? '',
                                            'routing_number' => $pm['routing_number'] ?? '',
                                            'wallet_address' => $pm['wallet_address'] ?? '',
                                            'instructions'   => $pm['instructions'] ?? '',
                                            'is_crypto'      => $pm['is_crypto'] ?? 0,
                                        ];
                                        $pmJson = htmlspecialchars(json_encode($pmDetails, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                                        echo '<option value="'.htmlspecialchars($pm['method_code'], ENT_QUOTES).'" data-details=\''.$pmJson.'\'>'.htmlspecialchars($pm['method_name'], ENT_QUOTES).'</option>';
                                    }
                                } catch (Exception $e) {
                                    echo '<option disabled>Fehler beim Laden</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Payment details (shown after method selected) -->
                        <div id="obPayDetails" style="display:none;">
                            <div class="ob-pay-section">

                                <div id="obPayBankSection" style="display:none;">
                                    <h6><i class="anticon anticon-bank mr-1"></i>Banküberweisung</h6>
                                    <div class="ob-row">
                                        <div>
                                            <div style="font-size:0.8rem;color:var(--ob-muted);margin-bottom:3px;">Bankname</div>
                                            <div style="font-weight:600;" id="obBankName">—</div>
                                        </div>
                                        <div>
                                            <div style="font-size:0.8rem;color:var(--ob-muted);margin-bottom:3px;">Kontonummer</div>
                                            <div style="font-weight:600;" id="obBankAccount">—</div>
                                        </div>
                                    </div>
                                    <div style="margin-top:10px;">
                                        <div style="font-size:0.8rem;color:var(--ob-muted);margin-bottom:3px;">Routing-Nummer</div>
                                        <div style="font-weight:600;" id="obBankRouting">—</div>
                                    </div>
                                </div>

                                <div id="obPayCryptoSection" style="display:none;">
                                    <h6><i class="anticon anticon-block mr-1"></i>Krypto-Wallet</h6>
                                    <div style="font-size:0.8rem;color:var(--ob-muted);margin-bottom:5px;">Wallet-Adresse</div>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="obCryptoAddr" readonly
                                               style="font-family:monospace;font-size:0.85rem;border-radius:8px 0 0 8px;">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="obCopyWallet"
                                                    style="border-radius:0 8px 8px 0;">
                                                <i class="anticon anticon-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div id="obPayInstrSection" style="display:none;">
                                    <h6><i class="anticon anticon-info-circle mr-1"></i>Hinweise</h6>
                                    <p id="obPayInstr" style="font-size:0.88rem;color:var(--ob-text);margin:0;"></p>
                                </div>

                            </div><!-- /ob-pay-section -->

                            <!-- Upload proof -->
                            <div class="ob-form-group" style="margin-top:16px;">
                                <label style="font-weight:700;">Zahlungsnachweis hochladen <span class="req">*</span></label>
                                <div style="border:2px dashed var(--ob-border);border-radius:12px;padding:18px;text-align:center;cursor:pointer;position:relative;background:var(--ob-bg);" id="obDropZone">
                                    <input type="file" name="proof_of_payment" id="obProofFile"
                                           accept=".jpg,.jpeg,.png,.pdf" required
                                           style="position:absolute;inset:0;opacity:0;cursor:pointer;">
                                    <i class="anticon anticon-cloud-upload" style="font-size:28px;color:var(--ob-accent);"></i>
                                    <div style="margin-top:8px;font-size:0.88rem;color:var(--ob-muted);">
                                        Datei hier ablegen oder <span style="color:var(--ob-primary);font-weight:600;">klicken zum Hochladen</span>
                                    </div>
                                    <div style="font-size:0.78rem;color:var(--ob-muted);margin-top:4px;">PDF, JPG oder PNG · Max. 10 MB</div>
                                    <div id="obFileLabel" style="margin-top:8px;font-size:0.82rem;font-weight:600;color:var(--ob-primary);display:none;"></div>
                                </div>
                            </div>
                        </div><!-- /obPayDetails -->

                    </div><!-- /modal-body -->
                    <div class="modal-footer" style="border-top:1px solid var(--ob-border);">
                        <button type="button" class="ob-btn" style="background:var(--ob-bg);color:var(--ob-text);border:1px solid var(--ob-border);" data-dismiss="modal">
                            Abbrechen
                        </button>
                        <button type="submit" class="ob-btn ob-btn-primary" id="obPaySubmitBtn">
                            <i class="anticon anticon-check-circle mr-1"></i>Zahlungsnachweis senden
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div><!-- /#obPayModal -->

    <script>
    // ── Open modal for paid package ──
    document.querySelectorAll('.ob-select-paid-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('obPayPkgId').value    = this.dataset.id;
            document.getElementById('obPayPkgName').textContent  = this.dataset.name;
            document.getElementById('obPayPkgPrice').textContent = this.dataset.price;
            document.getElementById('obPayModalSub').textContent =
                'Sie abonnieren: ' + this.dataset.name + ' für €' + this.dataset.price;
            $('#obPayModal').modal('show');
        });
    });

    // ── Show payment details when method selected ──
    document.getElementById('obPayMethod').addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        var raw = opt.getAttribute('data-details');
        var details = null;
        try { details = raw ? JSON.parse(raw) : null; } catch(e) {}

        var container = document.getElementById('obPayDetails');
        var bankSec   = document.getElementById('obPayBankSection');
        var cryptoSec = document.getElementById('obPayCryptoSection');
        var instrSec  = document.getElementById('obPayInstrSection');

        bankSec.style.display   = 'none';
        cryptoSec.style.display = 'none';
        instrSec.style.display  = 'none';

        if (!details) { container.style.display = 'none'; return; }

        if (details.bank_name) {
            document.getElementById('obBankName').textContent    = details.bank_name;
            document.getElementById('obBankAccount').textContent = details.account_number || '—';
            document.getElementById('obBankRouting').textContent = details.routing_number || '—';
            bankSec.style.display = 'block';
        }
        if (details.wallet_address) {
            document.getElementById('obCryptoAddr').value = details.wallet_address;
            cryptoSec.style.display = 'block';
        }
        if (details.instructions) {
            document.getElementById('obPayInstr').textContent = details.instructions;
            instrSec.style.display = 'block';
        }
        container.style.display = 'block';
    });

    // ── Copy wallet address ──
    document.getElementById('obCopyWallet').addEventListener('click', function() {
        var val = document.getElementById('obCryptoAddr').value;
        if (!val) return;
        navigator.clipboard.writeText(val).then(function() {
            if (typeof toastr !== 'undefined') toastr.success('Wallet-Adresse kopiert');
        });
    });

    // ── Show filename in drop zone ──
    document.getElementById('obProofFile').addEventListener('change', function() {
        var lbl = document.getElementById('obFileLabel');
        if (this.files.length) {
            lbl.textContent = '✓ ' + this.files[0].name;
            lbl.style.display = 'block';
        }
    });

    // ── Submit paid subscription via AJAX ──
    document.getElementById('obPayForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('obPaySubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="anticon anticon-loading mr-1"></i>Wird gesendet …';

        var formData = new FormData(this);
        formData.append('from_onboarding', '1');

        fetch('ajax/subscribe_package.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                if (typeof toastr !== 'undefined') toastr.success(data.message || 'Abonnement aktiviert!');
                $('#obPayModal').modal('hide');
                setTimeout(function() { window.location.href = 'index.php'; }, 1400);
            } else {
                if (typeof toastr !== 'undefined') toastr.error(data.message || 'Fehler beim Abonnieren');
                btn.disabled = false;
                btn.innerHTML = '<i class="anticon anticon-check-circle mr-1"></i>Zahlungsnachweis senden';
            }
        })
        .catch(function() {
            if (typeof toastr !== 'undefined') toastr.error('Serverfehler. Bitte erneut versuchen.');
            btn.disabled = false;
            btn.innerHTML = '<i class="anticon anticon-check-circle mr-1"></i>Zahlungsnachweis senden';
        });
    });
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