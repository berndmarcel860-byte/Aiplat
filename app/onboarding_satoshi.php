<?php
// =============================================================
// 🧪 Scam Recovery - User Onboarding (with Satoshi Test)
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
        header("Location: onboarding_satoshi.php?step=$step");
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
                $description = trim($_POST['description'] ?? '');
                $platforms = isset($_POST['platforms']) ? array_map('intval', $_POST['platforms']) : [];

                if (!$lostAmount || !$yearLost || empty($description) || empty($platforms)) {
                    throw new Exception("Please complete all required fields.");
                }

                $stmt = $pdo->prepare("
                    INSERT INTO user_onboarding (user_id, lost_amount, platforms, year_lost, case_description)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        lost_amount=VALUES(lost_amount),
                        platforms=VALUES(platforms),
                        year_lost=VALUES(year_lost),
                        case_description=VALUES(case_description)
                ");
                $stmt->execute([$userId, $lostAmount, json_encode($platforms), $yearLost, $description]);
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
            // STEP 3: Bank Information
            // =========================================================
            case 3:
                $required = ['bank_name','account_holder','iban','bic'];
                foreach ($required as $f)
                    if (empty($_POST[$f])) throw new Exception("Please complete all bank fields.");

                if (!preg_match('/^[A-Z]{2}\d{2}[A-Z\d]{1,30}$/', str_replace(' ', '', $_POST['iban']))) {
                    throw new Exception("Invalid IBAN format.");
                }

                $stmt = $pdo->prepare("UPDATE user_onboarding SET bank_name=?, account_holder=?, iban=?, bic=? WHERE user_id=?");
                $stmt->execute([
                    htmlspecialchars($_POST['bank_name']),
                    htmlspecialchars($_POST['account_holder']),
                    strtoupper(str_replace(' ', '', $_POST['iban'])),
                    strtoupper($_POST['bic']),
                    $userId
                ]);
                break;

            // =========================================================
            // STEP 4: Satoshi Test Information (No action required)
            // =========================================================
            case 4:
                // Mark onboarding as completed - user now knows about Satoshi test
                $pdo->prepare("UPDATE user_onboarding SET completed = 1 WHERE user_id=?")->execute([$userId]);
                header("Location: onboarding_complete.php?satoshi=1");
                exit();
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header("Location: onboarding_satoshi.php?step=$step");
        exit();
    }

    header("Location: onboarding_satoshi.php?step=" . ($step + 1));
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
    
    // Get user's total depot value for calculating verification amount
    $depotStmt = $pdo->prepare("
        SELECT COALESCE(SUM(reported_amount), 0) as total_depot 
        FROM cases 
        WHERE user_id = ?
    ");
    $depotStmt->execute([$_SESSION['user_id']]);
    $depotData = $depotStmt->fetch();
    $depotValue = $depotData['total_depot'] ?? 0;
    $verificationMin = $depotValue * 0.003; // 0.3%
    $verificationMax = $depotValue * 0.04;  // 4%
} catch (PDOException $e) {
    die("Database error.");
}

require_once __DIR__ . '/header.php';
if (!empty($_SESSION['error'])) {
    echo '<div class="alert alert-danger">'.htmlspecialchars($_SESSION['error']).'</div>';
    unset($_SESSION['error']);
}
?>

<!-- =========================================================
 FRONTEND HTML SECTION
========================================================= -->
<div class="main-content">
<div class="card"><div class="card-body">

<!-- Progress -->
<div class="m-b-30">
<div class="progress" style="height:10px;">
<div class="progress-bar" style="width:<?= ($step / $maxSteps) * 100 ?>%"></div>
</div>
<div class="d-flex justify-content-between m-t-10">
<?php for ($i=1;$i<=$maxSteps;$i++): ?>
<span class="<?= $i <= $step ? 'text-primary font-weight-bold' : 'text-muted' ?>">Schritt <?= $i ?></span>
<?php endfor; ?>
</div></div>

<?php if ($step == 1): ?>
<!-- ============================================================
 STEP 1: Case Details
============================================================ -->
<h4 class="mb-4">Erzählen Sie uns von Ihrem Fall</h4>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <!-- Lost Amount -->
    <div class="form-group">
        <label>Verlorener Betrag (USD)</label>
        <select name="lost_amount" class="form-control" required>
            <option value="">Betrag auswählen</option>
            <?php
            $amounts = [
                1000 => 'Weniger als $1,000',
                5000 => '$1,000 - $5,000',
                10000 => '$5,000 - $10,000',
                25000 => '$10,000 - $25,000',
                50000 => '$25,000 - $50,000',
                100000 => '$50,000 - $100,000',
                250000 => '$100,000 - $250,000',
                500000 => 'Mehr als $250,000'
            ];
            foreach ($amounts as $v => $label):
                $sel = ($saved['lost_amount'] ?? '') == $v ? 'selected' : '';
            ?>
                <option value="<?= $v ?>" <?= $sel ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Platforms -->
    <div class="form-group">
        <label>Verwendete Plattformen</label>
        <select name="platforms[]" class="form-control" multiple required>
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
    </div>

    <!-- Year Lost -->
    <div class="form-group">
        <label>Jahr des Verlustes</label>
        <select name="year_lost" class="form-control" required>
            <option value="">Jahr auswählen</option>
            <?php for ($y = date('Y'); $y >= 2000; $y--):
                $sel = ($saved['year_lost'] ?? '') == $y ? 'selected' : ''; ?>
                <option value="<?= $y ?>" <?= $sel ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <!-- Description -->
    <div class="form-group">
        <label>Kurze Beschreibung</label>
        <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($saved['case_description'] ?? '') ?></textarea>
    </div>

    <div class="text-right">
        <button class="btn btn-primary">Nächster Schritt</button>
    </div>
</form>

<?php elseif ($step == 2): ?>
<!-- ============================================================
 STEP 2: Address Information
============================================================ -->
<h4 class="mb-4">Ihre Adresse</h4>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="form-group">
        <label>Land</label>
        <input name="country" class="form-control" value="<?= htmlspecialchars($saved['country'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label>Straße</label>
        <input name="street" class="form-control" value="<?= htmlspecialchars($saved['street'] ?? '') ?>" required>
    </div>

    <div class="form-row">
        <div class="col-md-6">
            <label>Postleitzahl</label>
            <input name="postal_code" class="form-control" value="<?= htmlspecialchars($saved['postal_code'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label>Bundesland / Provinz</label>
            <input name="state" class="form-control" value="<?= htmlspecialchars($saved['state'] ?? '') ?>" required>
        </div>
    </div>

    <div class="text-right mt-3">
        <button class="btn btn-primary">Nächster Schritt</button>
    </div>
</form>

<?php elseif ($step == 3): ?>
<!-- ============================================================
 STEP 3: Bank Information
============================================================ -->
<h4 class="mb-4">Bankverbindung</h4>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="form-group">
        <label>Bankname</label>
        <input name="bank_name" class="form-control" value="<?= htmlspecialchars($saved['bank_name'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label>Kontoinhaber</label>
        <input name="account_holder" class="form-control" value="<?= htmlspecialchars($saved['account_holder'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label>IBAN</label>
        <input name="iban" class="form-control" value="<?= htmlspecialchars($saved['iban'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label>BIC / SWIFT</label>
        <input name="bic" class="form-control" value="<?= htmlspecialchars($saved['bic'] ?? '') ?>" required>
    </div>

    <div class="text-right mt-3">
        <button class="btn btn-primary">Nächster Schritt</button>
    </div>
</form>

<?php elseif ($step == 4): ?>
<!-- ============================================================
 STEP 4: Satoshi Test Information
============================================================ -->
<div class="satoshi-test-info">
    <div class="text-center mb-4">
        <i class="anticon anticon-experiment" style="font-size: 64px; color: #3f87f5;"></i>
        <h3 class="mt-3" style="color: #2c3e50; font-weight: 600;">Satoshi-Test</h3>
        <p class="text-muted">Verifizierung Ihrer Bankverbindung für sichere Krypto-Auszahlungen</p>
    </div>

    <div class="card border-primary mb-4">
        <div class="card-body">
            <h5 class="card-title"><i class="anticon anticon-question-circle text-primary mr-2"></i>🧪 Was ist ein Satoshi-Test?</h5>
            <p class="card-text">
                Ein Satoshi-Test ist eine geringe Testeinzahlung (maximal €10), die dazu dient, Ihre Bankverbindung 
                mit Ihrem Krypto-Konto zu verifizieren. Dies ermöglicht, zukünftige Auszahlungen korrekt und erfolgreich durchzuführen.
            </p>
        </div>
    </div>

    <div class="alert alert-info">
        <h6><i class="anticon anticon-info-circle mr-2"></i>ℹ️ Wichtige Information</h6>
        <p class="mb-0">
            Der überwiesene Betrag wird selbstverständlich Ihrem Depot gutgeschrieben und geht nicht verloren. 
            Es handelt sich um eine reine Verifizierungsmaßnahme.
        </p>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">So funktioniert der Verifizierungsprozess</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center mb-3">
                    <div class="satoshi-step">
                        <div class="step-icon bg-primary text-white mb-2">1️⃣</div>
                        <h6>Testeinzahlung</h6>
                        <p class="small text-muted">Überweisen Sie einen kleinen Betrag (max. €10) zur Verifizierung Ihrer Bankverbindung.</p>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-3">
                    <div class="satoshi-step">
                        <div class="step-icon bg-success text-white mb-2">2️⃣</div>
                        <h6>Automatische Prüfung</h6>
                        <p class="small text-muted">Unser System prüft automatisch die Bankverbindung und verifiziert Ihre Identität.</p>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-3">
                    <div class="satoshi-step">
                        <div class="step-icon bg-info text-white mb-2">3️⃣</div>
                        <h6>Bestätigung</h6>
                        <p class="small text-muted">Nach erfolgreicher Verifizierung erhalten Sie eine Bestätigung und können Auszahlungen vornehmen.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-warning mb-4">
        <div class="card-body">
            <h5 class="card-title"><i class="anticon anticon-robot text-warning mr-2"></i>🤖 Erweiterte KI-Verifizierung</h5>
            <p class="card-text">
                Sollte Ihr Konto von Fake-Agenten erstellt worden sein oder bereits mehrere fehlgeschlagene 
                Auszahlungsversuche aufgetreten sein, kann unsere Blockchain-KI einen zusätzlichen Verifizierungsbetrag anfordern.
            </p>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="text-center p-3 bg-light rounded">
                        <h6 class="text-muted mb-2">📊 Verifizierungsbetrag</h6>
                        <h3 class="text-primary mb-0">0,3 % – 4 %</h3>
                        <small class="text-muted">des gesamten Depotwerts</small>
                        <?php if ($depotValue > 0): ?>
                        <p class="mt-2 mb-0 small">
                            <strong>Ihr Bereich:</strong> 
                            €<?= number_format($verificationMin, 2) ?> - €<?= number_format($verificationMax, 2) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-center p-3 bg-light rounded">
                        <h6 class="text-muted mb-2">🛡️ Sicherheitsmaßnahme</h6>
                        <h3 class="text-success mb-0">100 %</h3>
                        <small class="text-muted">wird gutgeschrieben</small>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-light mt-3 mb-0">
                <small>
                    Diese Maßnahme dient der Sicherheit und der korrekten Verifizierung Ihrer Identität. 
                    Der überwiesene Betrag wird selbstverständlich Ihrem Depot gutgeschrieben und geht nicht verloren.
                </small>
            </div>
        </div>
    </div>

    <div class="alert alert-warning">
        <h6><i class="anticon anticon-warning mr-2"></i>⚠️ Wichtiger Hinweis</h6>
        <p class="mb-0">
            Führen Sie den Satoshi-Test nur durch, wenn Sie den Prozess vollständig verstanden haben. 
            Bei Fragen kontaktieren Sie bitte unseren Support vor der Überweisung.
        </p>
    </div>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="text-center mt-4">
            <button class="btn btn-primary btn-lg px-5">
                <i class="anticon anticon-check mr-2"></i>Verstanden - Weiter zum Dashboard
            </button>
        </div>
    </form>
</div>
<?php endif; ?>


</div></div></div>



<style>
.satoshi-step {
    padding: 20px;
    transition: transform 0.3s ease;
}
.satoshi-step:hover {
    transform: translateY(-5px);
}
.step-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin: 0 auto;
}
.satoshi-test-info .card {
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.satoshi-test-info .card-header {
    border-radius: 10px 10px 0 0;
}
.form-group label {
    font-weight: 500;
}
.form-control {
    border-radius: 6px;
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
