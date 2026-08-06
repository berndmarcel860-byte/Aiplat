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
?>

<!-- =========================================================
 ONBOARDING (SATOSHI) — Professional Design
========================================================= -->
<style>
/* ── Brand colours ── */
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

.ob-wrap        { max-width: 760px; margin: 0 auto; padding: 24px 16px 80px; }
.ob-card        { background: #fff; border-radius: 16px; box-shadow: var(--ob-shadow); border: 1px solid var(--ob-border); overflow: hidden; }
.ob-card-header { background: linear-gradient(135deg, var(--ob-primary) 0%, var(--ob-accent) 100%); padding: 28px 32px 24px; color: #fff; }
.ob-card-header h1 { font-size: 1.35rem; font-weight: 700; margin: 0; letter-spacing: .2px; }
.ob-card-header p  { margin: 6px 0 0; font-size: .93rem; opacity: .88; }

/* Step progress */
.ob-stepper { display: flex; align-items: flex-start; padding: 24px 32px 20px; background: #fff; border-bottom: 1px solid var(--ob-border); position: relative; gap: 0; }
.ob-stepper::before { content: ''; position: absolute; top: 38px; left: calc(32px + 20px); right: calc(32px + 20px); height: 2px; background: var(--ob-border); z-index: 0; }
.ob-step { flex: 1; text-align: center; position: relative; z-index: 1; }
.ob-step-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; margin: 0 auto 8px; border: 2px solid var(--ob-border); background: #fff; color: var(--ob-muted); transition: all .3s ease; }
.ob-step.is-done   .ob-step-circle { background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent)); border-color: var(--ob-primary); color: #fff; }
.ob-step.is-active .ob-step-circle { background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent)); border-color: var(--ob-primary); color: #fff; box-shadow: 0 0 0 4px rgba(41,80,168,.15); }
.ob-step-label { font-size: .78rem; font-weight: 600; color: var(--ob-muted); white-space: nowrap; }
.ob-step.is-active .ob-step-label,
.ob-step.is-done   .ob-step-label { color: var(--ob-primary); }
.ob-connector { flex: 1; height: 2px; background: var(--ob-border); margin: 19px 0 0; position: relative; z-index: 0; }
.ob-connector.done { background: linear-gradient(90deg, var(--ob-primary), var(--ob-accent)); }

/* Form body */
.ob-body { padding: 32px; }
.ob-section-title { font-size: 1.15rem; font-weight: 700; color: var(--ob-text); margin-bottom: 6px; display: flex; align-items: center; gap: 10px; }
.ob-section-title .ob-icon { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent)); display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; flex-shrink: 0; }
.ob-section-desc { color: var(--ob-muted); font-size: .9rem; margin-bottom: 24px; padding-left: 46px; }
.ob-form-group { margin-bottom: 20px; }
.ob-form-group label { display: block; font-weight: 600; font-size: .88rem; color: var(--ob-text); margin-bottom: 6px; }
.ob-form-group label .req { color: var(--ob-danger); margin-left: 2px; }
.ob-control { width: 100%; border: 1.5px solid var(--ob-border); border-radius: 10px; padding: 11px 16px; font-size: .95rem; color: var(--ob-text); background: #fff; transition: border-color .2s, box-shadow .2s; -webkit-appearance: none; appearance: none; }
.ob-control:focus { outline: none; border-color: var(--ob-primary); box-shadow: 0 0 0 3px rgba(41,80,168,.15); }
select.ob-control { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 38px; }
textarea.ob-control { resize: vertical; min-height: 100px; }
.ob-hint { font-size: .8rem; color: var(--ob-muted); margin-top: 4px; }
.ob-info { background: linear-gradient(135deg,rgba(41,80,168,.06),rgba(45,169,227,.04)); border: 1px solid rgba(41,80,168,.15); border-radius: 10px; padding: 16px 18px; margin-bottom: 20px; font-size: .88rem; color: var(--ob-text); line-height: 1.6; }
.ob-info strong { color: var(--ob-primary); }
.ob-info ul { margin: 8px 0 0; padding-left: 18px; }
.ob-info ul li { margin-bottom: 4px; }
.ob-warn { background: #fff8e6; border: 1px solid #ffd166; border-radius: 10px; padding: 14px 18px; font-size: .87rem; color: #856404; margin-bottom: 20px; }

/* Footer / CTA */
.ob-footer { display: flex; justify-content: flex-end; padding: 20px 32px 28px; border-top: 1px solid var(--ob-border); background: var(--ob-bg); }
.ob-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 32px; border-radius: 10px; font-size: .97rem; font-weight: 600; border: none; cursor: pointer; transition: all .25s ease; }
.ob-btn-primary { background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent)); color: #fff; box-shadow: 0 4px 14px rgba(41,80,168,.3); }
.ob-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(41,80,168,.4); }

/* Row helper */
.ob-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

/* Platform grid */
.ob-platforms { display: grid; grid-template-columns: repeat(auto-fill,minmax(180px,1fr)); gap: 10px; }
.ob-platform-item { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border: 1.5px solid var(--ob-border); border-radius: 8px; cursor: pointer; font-size: .88rem; font-weight: 500; color: var(--ob-text); transition: border-color .2s,background .2s; background: #fff; }
.ob-platform-item:hover { border-color: var(--ob-accent); background: rgba(45,169,227,.05); }
.ob-platform-item input[type=checkbox] { accent-color: var(--ob-primary); width: 16px; height: 16px; flex-shrink: 0; }
.ob-platform-item.checked { border-color: var(--ob-primary); background: rgba(41,80,168,.06); }

/* Satoshi step 4 specifics */
.ob-process-steps { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin: 20px 0; }
.ob-process-step  { text-align: center; padding: 20px 14px; background: var(--ob-bg); border: 1px solid var(--ob-border); border-radius: 12px; }
.ob-process-num   { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; color: #fff; margin: 0 auto 12px; }
.ob-process-step h6 { font-weight: 700; font-size: .9rem; color: var(--ob-text); margin-bottom: 6px; }
.ob-process-step p  { font-size: .82rem; color: var(--ob-muted); margin: 0; line-height: 1.5; }
.ob-stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 16px 0 0; }
.ob-stat-box  { background: var(--ob-bg); border: 1px solid var(--ob-border); border-radius: 10px; padding: 16px; text-align: center; }
.ob-stat-label { font-size: .78rem; color: var(--ob-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px; }
.ob-stat-value { font-size: 1.4rem; font-weight: 800; color: var(--ob-primary); line-height: 1; }
.ob-stat-value.green { color: var(--ob-success); }
.ob-stat-sub   { font-size: .78rem; color: var(--ob-muted); margin-top: 4px; }

@media (max-width: 575px) {
    .ob-body    { padding: 20px 18px; }
    .ob-footer  { padding: 16px 18px 24px; }
    .ob-stepper { padding: 18px; }
    .ob-card-header { padding: 20px 18px; }
    .ob-row     { grid-template-columns: 1fr; }
    .ob-platforms { grid-template-columns: 1fr 1fr; }
    .ob-step-label { font-size: .7rem; }
    .ob-process-steps { grid-template-columns: 1fr; }
    .ob-stat-grid { grid-template-columns: 1fr; }
}
</style>

<div class="main-content">
<div class="ob-wrap">
<div class="ob-card">

    <!-- Card Header -->
    <div class="ob-card-header">
        <div class="d-flex align-items-center mb-2" style="gap:12px;">
            <div style="width:44px;height:44px;background:rgba(255,255,255,.18);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
                <i class="anticon anticon-safety-certificate"></i>
            </div>
            <div>
                <h1 style="font-size:1.3rem;font-weight:700;margin:0;">Konto einrichten</h1>
                <p style="margin:4px 0 0;font-size:.88rem;opacity:.88;">Schritt <?= $step ?> von <?= $maxSteps ?> &ndash; Bitte füllen Sie alle Angaben vollständig aus</p>
            </div>
        </div>
        <div class="d-flex flex-wrap mt-3" style="gap:8px;">
            <span style="background:rgba(255,255,255,.15);border-radius:20px;padding:4px 12px;font-size:11px;font-weight:600;color:#fff;display:flex;align-items:center;gap:5px;"><i class="anticon anticon-lock"></i> 256-Bit SSL</span>
            <span style="background:rgba(255,255,255,.15);border-radius:20px;padding:4px 12px;font-size:11px;font-weight:600;color:#fff;display:flex;align-items:center;gap:5px;"><i class="anticon anticon-bank"></i> FCA-reguliert</span>
            <span style="background:rgba(255,255,255,.15);border-radius:20px;padding:4px 12px;font-size:11px;font-weight:600;color:#fff;display:flex;align-items:center;gap:5px;"><i class="anticon anticon-eye-invisible"></i> DSGVO-konform</span>
            <span style="background:rgba(255,255,255,.15);border-radius:20px;padding:4px 12px;font-size:11px;font-weight:600;color:#fff;display:flex;align-items:center;gap:5px;"><i class="anticon anticon-robot"></i> KI-gestützt</span>
        </div>
    </div>

    <!-- Step Progress Indicator -->
    <div class="ob-stepper" role="navigation" aria-label="Fortschritt">
        <?php
        $stepDefs = [
            1 => 'Falldetails',
            2 => 'Adresse',
            3 => 'Bankdaten',
            4 => 'Verifizierung',
        ];
        foreach ($stepDefs as $n => $label):
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
            <div class="ob-step-label"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
    <div class="mx-3 mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:10px;font-size:.9rem;">
            <i class="anticon anticon-close-circle mr-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Schließen"><span aria-hidden="true">&times;</span></button>
        </div>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <!-- Form body -->
    <div class="ob-body">

    <?php if ($step == 1): ?>
    <!-- ============================================================
     SCHRITT 1: Falldetails
    ============================================================ -->
    <div class="ob-info d-flex" style="align-items:flex-start;gap:14px;margin-bottom:24px;">
        <div style="width:38px;height:38px;background:linear-gradient(135deg,#2950a8,#2da9e3);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="anticon anticon-bulb" style="color:#fff;font-size:17px;"></i>
        </div>
        <div>
            <strong>Willkommen! So funktioniert's:</strong>
            In <strong>4 kurzen Schritten</strong> richten wir Ihren Wiederherstellungsfall ein.
            Unser KI-Algorithmus analysiert Ihren Fall und leitet automatisch Maßnahmen ein.
            Alle Angaben sind vertraulich und DSGVO-konform geschützt.
        </div>
    </div>

    <div class="ob-section-title">
        <span class="ob-icon"><i class="anticon anticon-file-text" style="font-size:18px;"></i></span>
        Erzählen Sie uns von Ihrem Fall
    </div>
    <p class="ob-section-desc">Diese Angaben helfen uns, Ihren Fall zu analysieren und die Wiederherstellung Ihrer Gelder einzuleiten.</p>

    <form method="post" action="onboarding_satoshi.php?step=<?= $step ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

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

        <div class="ob-form-group">
            <label>Verwendete Plattformen <span class="req">*</span></label>
            <?php $chosen = !empty($saved['platforms']) ? json_decode($saved['platforms'], true) : []; ?>
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

        <div class="ob-form-group">
            <label>Fallbeschreibung <span class="req">*</span></label>
            <textarea name="description" class="ob-control"
                      placeholder="Beschreiben Sie kurz, was passiert ist und wie Sie geschädigt wurden …"
                      required><?= htmlspecialchars($saved['case_description'] ?? '') ?></textarea>
            <p class="ob-hint">Je mehr Details Sie angeben, desto gezielter können wir Ihnen helfen.</p>
        </div>

        <div class="ob-footer">
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

    <form method="post" action="onboarding_satoshi.php?step=<?= $step ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="ob-form-group">
            <label>Land <span class="req">*</span></label>
            <select name="country" class="ob-control" required>
                <option value="">Land auswählen …</option>
                <?php
                $countries = [
                    'Deutschland','Österreich','Schweiz','Frankreich','Italien',
                    'Spanien','Niederlande','Belgien','Luxemburg','Dänemark',
                    'Schweden','Norwegen','Polen','Tschechien','Andere',
                ];
                foreach ($countries as $c):
                    $sel = ($saved['country'] ?? '') === $c ? 'selected' : '';
                ?>
                <option value="<?= $c ?>" <?= $sel ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ob-form-group">
            <label>Straße und Hausnummer <span class="req">*</span></label>
            <input type="text" name="street" class="ob-control"
                   value="<?= htmlspecialchars($saved['street'] ?? '') ?>"
                   placeholder="Hauptstraße 42" required>
        </div>

        <div class="ob-row">
            <div class="ob-form-group">
                <label>Postleitzahl <span class="req">*</span></label>
                <input type="text" name="postal_code" class="ob-control"
                       value="<?= htmlspecialchars($saved['postal_code'] ?? '') ?>"
                       placeholder="60322" required>
            </div>
            <div class="ob-form-group">
                <label>Stadt / Bundesland <span class="req">*</span></label>
                <input type="text" name="state" class="ob-control"
                       value="<?= htmlspecialchars($saved['state'] ?? '') ?>"
                       placeholder="Frankfurt am Main" required>
            </div>
        </div>

        <div class="ob-footer">
            <button type="submit" class="ob-btn ob-btn-primary">
                Weiter <i class="anticon anticon-arrow-right"></i>
            </button>
        </div>
    </form>

    <?php elseif ($step == 3): ?>
    <!-- ============================================================
     SCHRITT 3: Bankverbindung
    ============================================================ -->
    <div class="ob-section-title">
        <span class="ob-icon"><i class="anticon anticon-bank" style="font-size:18px;"></i></span>
        Bankverbindung hinterlegen
    </div>
    <p class="ob-section-desc">Damit wir zurückgewonnene Gelder auszahlen können, benötigen wir Ihre Bankdaten.</p>

    <div class="ob-info">
        <strong>Warum ist das notwendig?</strong>
        <ul>
            <li>Unser System ordnet gefundene Gelder sicher Ihrem verifizierten Bankkonto zu.</li>
            <li>Die Bankverbindung wird im nächsten Schritt durch einen Satoshi-Test verifiziert.</li>
            <li>Alle Daten werden verschlüsselt gespeichert und ausschließlich für Rückzahlungen verwendet.</li>
        </ul>
    </div>

    <form method="post" action="onboarding_satoshi.php?step=<?= $step ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="ob-form-group">
            <label>Bankname <span class="req">*</span></label>
            <input type="text" name="bank_name" class="ob-control"
                   value="<?= htmlspecialchars($saved['bank_name'] ?? '') ?>"
                   placeholder="z. B. Sparkasse, Deutsche Bank" required>
        </div>

        <div class="ob-form-group">
            <label>Kontoinhaber <span class="req">*</span></label>
            <input type="text" name="account_holder" class="ob-control"
                   value="<?= htmlspecialchars($saved['account_holder'] ?? '') ?>"
                   placeholder="Vollständiger Name wie auf dem Bankkonto" required>
        </div>

        <div class="ob-form-group">
            <label>IBAN <span class="req">*</span></label>
            <input type="text" name="iban" class="ob-control"
                   value="<?= htmlspecialchars($saved['iban'] ?? '') ?>"
                   placeholder="DE89 3704 0044 0532 0130 00"
                   style="font-family:monospace;letter-spacing:1px;" required>
            <p class="ob-hint">Internationale Bankkontonummer (wird vor Nutzung verifiziert)</p>
        </div>

        <div class="ob-form-group">
            <label>BIC / SWIFT <span class="req">*</span></label>
            <input type="text" name="bic" class="ob-control"
                   value="<?= htmlspecialchars($saved['bic'] ?? '') ?>"
                   placeholder="COBADEFFXXX"
                   style="font-family:monospace;letter-spacing:1px;" required>
            <p class="ob-hint">Bank-Identifikationscode Ihrer Bank</p>
        </div>

        <div class="ob-footer">
            <button type="submit" class="ob-btn ob-btn-primary">
                Weiter <i class="anticon anticon-arrow-right"></i>
            </button>
        </div>
    </form>

    <?php elseif ($step == 4): ?>
    <!-- ============================================================
     SCHRITT 4: Satoshi-Test Informationen
    ============================================================ -->
    <div class="ob-section-title">
        <span class="ob-icon"><i class="anticon anticon-experiment" style="font-size:18px;"></i></span>
        Satoshi-Test Verifizierung
    </div>
    <p class="ob-section-desc">Eine kurze Erklärung zum Ablauf der Bankverifizierung für sichere Auszahlungen.</p>

    <div class="ob-info">
        <strong>Was ist ein Satoshi-Test?</strong><br>
        Ein Satoshi-Test ist eine geringe Testeinzahlung (maximal €10), die dazu dient, Ihre Bankverbindung
        mit Ihrem Krypto-Konto zu verifizieren. Dies stellt sicher, dass zukünftige Auszahlungen korrekt
        und sicher durchgeführt werden können. Der überwiesene Betrag wird vollständig Ihrem Depot gutgeschrieben.
    </div>

    <!-- Process steps -->
    <div class="ob-process-steps">
        <div class="ob-process-step">
            <div class="ob-process-num" style="background:linear-gradient(135deg,#2950a8,#2da9e3);">1</div>
            <h6>Testeinzahlung</h6>
            <p>Überweisen Sie einen kleinen Betrag (max. €10) zur Verifizierung Ihrer Bankverbindung.</p>
        </div>
        <div class="ob-process-step">
            <div class="ob-process-num" style="background:linear-gradient(135deg,#28a745,#20c997);">2</div>
            <h6>Automatische Prüfung</h6>
            <p>Unser System prüft die Bankverbindung und verifiziert Ihre Identität automatisch.</p>
        </div>
        <div class="ob-process-step">
            <div class="ob-process-num" style="background:linear-gradient(135deg,#17a2b8,#2da9e3);">3</div>
            <h6>Freischaltung</h6>
            <p>Nach erfolgreicher Verifizierung erhalten Sie eine Bestätigung und können Auszahlungen vornehmen.</p>
        </div>
    </div>

    <!-- KI verification info -->
    <div style="background:linear-gradient(135deg,rgba(41,80,168,.05),rgba(45,169,227,.04));border:1px solid rgba(41,80,168,.15);border-radius:12px;padding:20px 22px;margin-bottom:20px;">
        <div class="ob-section-title" style="margin-bottom:10px;">
            <span class="ob-icon"><i class="anticon anticon-robot" style="font-size:18px;"></i></span>
            Erweiterte KI-Verifizierung
        </div>
        <p style="font-size:.88rem;color:var(--ob-muted);margin-bottom:14px;padding-left:46px;">
            Sollte Ihr Konto von Drittanbietern erstellt worden sein oder mehrere fehlgeschlagene
            Auszahlungsversuche aufgetreten sein, kann unsere Blockchain-KI einen zusätzlichen
            Verifizierungsbetrag anfordern.
        </p>
        <div class="ob-stat-grid">
            <div class="ob-stat-box">
                <div class="ob-stat-label">Verifizierungsbetrag</div>
                <div class="ob-stat-value">0,3 % – 4 %</div>
                <div class="ob-stat-sub">des gesamten Depotwerts</div>
                <?php if ($depotValue > 0): ?>
                <div class="ob-stat-sub mt-1"><strong>Ihr Bereich:</strong> €<?= number_format($verificationMin, 2) ?> – €<?= number_format($verificationMax, 2) ?></div>
                <?php endif; ?>
            </div>
            <div class="ob-stat-box">
                <div class="ob-stat-label">Gutschrift</div>
                <div class="ob-stat-value green">100 %</div>
                <div class="ob-stat-sub">wird Ihrem Depot gutgeschrieben</div>
            </div>
        </div>
    </div>

    <div class="ob-warn">
        <i class="anticon anticon-warning mr-2"></i>
        <strong>Wichtiger Hinweis:</strong> Führen Sie den Satoshi-Test nur durch, wenn Sie den Prozess vollständig
        verstanden haben. Bei Fragen kontaktieren Sie bitte unseren Support vor der Überweisung.
    </div>

    <form method="post" action="onboarding_satoshi.php?step=<?= $step ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="ob-footer">
            <button type="submit" class="ob-btn ob-btn-primary">
                <i class="anticon anticon-check mr-1"></i>Verstanden – Weiter zum Dashboard
            </button>
        </div>
    </form>
    <?php endif; ?>

    </div><!-- /ob-body -->

</div><!-- /ob-card -->
</div><!-- /ob-wrap -->
</div><!-- /main-content -->

<?php require_once __DIR__ . '/footer.php'; ?>
