<?php
require_once 'config.php';
require_once 'header.php';

// E-Mail-Verifizierung wird per Ajax verarbeitet – siehe JavaScript unten

// Benutzerdaten laden
$user = [];
$onboarding = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM user_onboarding WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $onboarding = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // KYC-Status laden
    $stmt = $pdo->prepare("SELECT * FROM kyc_verification_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $kyc = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Fehler beim Laden der Profildaten: " . $e->getMessage();
}

$kycStatusMap = [
    'pending'  => ['label' => 'Ausstehend',  'cls' => 'warning'],
    'approved' => ['label' => 'Genehmigt',   'cls' => 'success'],
    'rejected' => ['label' => 'Abgelehnt',   'cls' => 'danger'],
    'under_review' => ['label' => 'In Prüfung', 'cls' => 'info'],
];
?>

<style>
/* ── Profil-Komponenten (nutzt ob-* Variablen aus onboarding) ── */
:root {
    --ob-primary: #2950a8;
    --ob-accent:  #2da9e3;
    --ob-text:    #2c3e50;
    --ob-muted:   #6c757d;
    --ob-border:  #e3e8f0;
    --ob-bg:      #f8fafc;
    --ob-shadow:  0 4px 24px rgba(41,80,168,.10);
}

.pf-wrap {
    max-width: 960px;
    margin: 0 auto;
    padding: 24px 16px 80px;
}

/* Page header */
.pf-hero {
    background: linear-gradient(135deg, var(--ob-primary) 0%, var(--ob-accent) 100%);
    border-radius: 16px;
    padding: 28px 32px;
    color: #fff;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: var(--ob-shadow);
}

.pf-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,.5);
    object-fit: cover;
    flex-shrink: 0;
    background: rgba(255,255,255,.2);
}

.pf-hero-name {
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0 0 4px;
}

.pf-hero-meta {
    font-size: 0.88rem;
    opacity: .85;
    margin: 0;
}

.pf-edit-btn {
    margin-left: auto;
    flex-shrink: 0;
    padding: 9px 22px;
    border-radius: 8px;
    border: 2px solid rgba(255,255,255,.7);
    background: transparent;
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: background .2s, border-color .2s;
}

.pf-edit-btn:hover {
    background: rgba(255,255,255,.15);
    border-color: #fff;
    color: #fff;
    text-decoration: none;
}

/* Section cards */
.pf-card {
    background: #fff;
    border: 1px solid var(--ob-border);
    border-radius: 14px;
    box-shadow: var(--ob-shadow);
    margin-bottom: 20px;
    overflow: hidden;
}

.pf-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px 22px;
    border-bottom: 1px solid var(--ob-border);
    font-weight: 700;
    font-size: 0.97rem;
    color: var(--ob-text);
}

.pf-card-header .pf-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 15px;
    flex-shrink: 0;
}

.pf-card-body {
    padding: 20px 22px;
}

/* Info rows */
.pf-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 32px;
}

.pf-info-item {}

.pf-info-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--ob-muted);
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 3px;
}

.pf-info-value {
    font-size: 0.95rem;
    color: var(--ob-text);
    font-weight: 500;
    word-break: break-all;
}

.pf-info-value.mono {
    font-family: monospace;
    letter-spacing: 0.5px;
}

/* Badges */
.pf-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
}

.pf-badge-success { background: rgba(40,167,69,.12); color: #1a7332; }
.pf-badge-warning { background: rgba(255,193,7,.15);  color: #856404; }
.pf-badge-danger  { background: rgba(220,53,69,.1);   color: #b02a37; }
.pf-badge-info    { background: rgba(23,162,184,.12); color: #0c6374; }
.pf-badge-muted   { background: rgba(108,117,125,.1); color: #495057; }

/* KYC action button */
.pf-kyc-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 14px;
    padding: 9px 20px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--ob-primary), var(--ob-accent));
    color: #fff;
    font-weight: 600;
    font-size: 0.88rem;
    text-decoration: none;
    transition: opacity .2s;
}

.pf-kyc-btn:hover { opacity: .88; color: #fff; text-decoration: none; }

/* Email verification inline section */
.pf-verify-section {
    margin-top: 10px;
    padding: 12px 16px;
    background: #fff8e6;
    border: 1px solid #ffd166;
    border-radius: 8px;
    font-size: 0.87rem;
}

/* Responsive */
@media (max-width: 575px) {
    .pf-hero   { flex-wrap: wrap; gap: 14px; padding: 20px 18px; }
    .pf-edit-btn { margin-left: 0; }
    .pf-grid   { grid-template-columns: 1fr; }
    .pf-card-body { padding: 16px 16px; }
}
</style>

<div class="main-content">
<div class="pf-wrap">

    <!-- ── Hero header ── -->
    <div class="pf-hero">
        <img src="<?= htmlspecialchars($avatar ?? 'assets/img/avatar.png') ?>" alt="Profilbild" class="pf-avatar">
        <div>
            <p class="pf-hero-name"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></p>
            <p class="pf-hero-meta">
                <i class="anticon anticon-calendar mr-1"></i>Mitglied seit <?= isset($user['created_at']) ? date('m.Y', strtotime($user['created_at'])) : '–' ?>
            </p>
        </div>
        <a href="settings.php" class="pf-edit-btn">
            <i class="anticon anticon-edit mr-1"></i> Profil bearbeiten
        </a>
    </div>

    <div class="row">
        <!-- Left column -->
        <div class="col-md-4">

            <!-- Kontaktdaten -->
            <div class="pf-card">
                <div class="pf-card-header">
                    <span class="pf-icon"><i class="anticon anticon-mail" style="font-size:15px;"></i></span>
                    Kontaktdaten
                </div>
                <div class="pf-card-body">

                    <!-- E-Mail -->
                    <div class="pf-info-item mb-3">
                        <div class="pf-info-label">E-Mail-Adresse</div>
                        <div class="pf-info-value">
                            <?= htmlspecialchars($user['email'] ?? '') ?>
                            <?php if (!empty($user['is_verified'])): ?>
                                <span class="pf-badge pf-badge-success ml-1">
                                    <i class="anticon anticon-check"></i> Verifiziert
                                </span>
                            <?php else: ?>
                                <span class="pf-badge pf-badge-warning ml-1">
                                    <i class="anticon anticon-warning"></i> Nicht verifiziert
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($user['is_verified'])): ?>
                        <div class="pf-verify-section mt-2">
                            <p class="mb-2" style="color: #856404;">Bitte bestätigen Sie Ihre E-Mail-Adresse.</p>
                            <button type="button" id="resendVerificationBtn" class="btn btn-sm btn-warning">
                                <i class="anticon anticon-mail mr-1"></i> Bestätigungsmail erneut senden
                            </button>
                            <div id="verificationMessage" class="mt-2" style="display: none;"></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Telefon -->
                    <?php if (!empty($user['phone'])): ?>
                    <div class="pf-info-item">
                        <div class="pf-info-label">Telefonnummer</div>
                        <div class="pf-info-value">
                            <?= htmlspecialchars($user['phone']) ?>
                            <?php if (!empty($user['phone_verified'])): ?>
                                <span class="pf-badge pf-badge-success ml-1">
                                    <i class="anticon anticon-check"></i> Verifiziert
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KYC-Verifizierung -->
            <div class="pf-card">
                <div class="pf-card-header">
                    <span class="pf-icon"><i class="anticon anticon-safety-certificate" style="font-size:15px;"></i></span>
                    KYC-Verifizierung
                </div>
                <div class="pf-card-body">
                    <?php if ($kyc): ?>
                        <?php
                        $kycInfo = $kycStatusMap[$kyc['status']] ?? ['label' => ucfirst($kyc['status']), 'cls' => 'muted'];
                        ?>
                        <div class="pf-info-label mb-2">Status</div>
                        <span class="pf-badge pf-badge-<?= $kycInfo['cls'] ?>">
                            <i class="anticon anticon-flag"></i> <?= $kycInfo['label'] ?>
                        </span>
                        <?php if ($kyc['status'] === 'rejected' && !empty($kyc['rejection_reason'])): ?>
                            <div class="mt-2" style="font-size:.87rem; color: #b02a37;">
                                <strong>Ablehnungsgrund:</strong> <?= htmlspecialchars($kyc['rejection_reason']) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <a href="kyc.php" class="pf-kyc-btn">
                                <i class="anticon anticon-<?= $kyc['status'] === 'rejected' ? 'redo' : 'eye' ?>"></i>
                                <?= $kyc['status'] === 'rejected' ? 'KYC erneut einreichen' : 'KYC-Status anzeigen' ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <p style="font-size:.9rem; color: var(--ob-muted); margin-bottom:14px;">
                            Sie haben die KYC-Verifizierung noch nicht abgeschlossen.
                        </p>
                        <a href="kyc.php" class="pf-kyc-btn">
                            <i class="anticon anticon-safety-certificate"></i> KYC jetzt abschließen
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /col-md-4 -->

        <!-- Right column -->
        <div class="col-md-8">

            <!-- Persönliche Informationen -->
            <div class="pf-card">
                <div class="pf-card-header">
                    <span class="pf-icon"><i class="anticon anticon-user" style="font-size:15px;"></i></span>
                    Persönliche Informationen
                </div>
                <div class="pf-card-body">
                    <div class="pf-grid">
                        <div class="pf-info-item">
                            <div class="pf-info-label">Vorname</div>
                            <div class="pf-info-value"><?= htmlspecialchars($user['first_name'] ?? '–') ?></div>
                        </div>
                        <div class="pf-info-item">
                            <div class="pf-info-label">Nachname</div>
                            <div class="pf-info-value"><?= htmlspecialchars($user['last_name'] ?? '–') ?></div>
                        </div>
                        <?php if ($onboarding): ?>
                        <div class="pf-info-item">
                            <div class="pf-info-label">Land</div>
                            <div class="pf-info-value"><?= htmlspecialchars($onboarding['country'] ?? '–') ?></div>
                        </div>
                        <div class="pf-info-item">
                            <div class="pf-info-label">Stadt / Bundesland</div>
                            <div class="pf-info-value"><?= htmlspecialchars($onboarding['state'] ?? '–') ?></div>
                        </div>
                        <div class="pf-info-item">
                            <div class="pf-info-label">Straße</div>
                            <div class="pf-info-value"><?= htmlspecialchars($onboarding['street'] ?? '–') ?></div>
                        </div>
                        <div class="pf-info-item">
                            <div class="pf-info-label">Postleitzahl</div>
                            <div class="pf-info-value"><?= htmlspecialchars($onboarding['postal_code'] ?? '–') ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bankdaten -->
            <?php if ($onboarding && (!empty($onboarding['bank_name']) || !empty($onboarding['account_holder']))): ?>
            <div class="pf-card">
                <div class="pf-card-header">
                    <span class="pf-icon"><i class="anticon anticon-bank" style="font-size:15px;"></i></span>
                    Bankdaten
                </div>
                <div class="pf-card-body">
                    <div class="pf-grid">
                        <div class="pf-info-item">
                            <div class="pf-info-label">Bankname</div>
                            <div class="pf-info-value"><?= htmlspecialchars($onboarding['bank_name'] ?? '–') ?></div>
                        </div>
                        <div class="pf-info-item">
                            <div class="pf-info-label">Kontoinhaber</div>
                            <div class="pf-info-value"><?= htmlspecialchars($onboarding['account_holder'] ?? '–') ?></div>
                        </div>
                        <div class="pf-info-item">
                            <div class="pf-info-label">IBAN</div>
                            <div class="pf-info-value mono"><?= htmlspecialchars($onboarding['iban'] ?? '–') ?></div>
                        </div>
                        <div class="pf-info-item">
                            <div class="pf-info-label">BIC / SWIFT</div>
                            <div class="pf-info-value mono"><?= htmlspecialchars($onboarding['bic'] ?? '–') ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Kontoaktivität -->
            <div class="pf-card">
                <div class="pf-card-header">
                    <span class="pf-icon"><i class="anticon anticon-clock-circle" style="font-size:15px;"></i></span>
                    Kontoaktivität
                </div>
                <div class="pf-card-body">
                    <div class="pf-grid">
                        <div class="pf-info-item">
                            <div class="pf-info-label">Letzter Login</div>
                            <div class="pf-info-value">
                                <?= !empty($user['last_login']) ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nie' ?>
                            </div>
                        </div>
                        <div class="pf-info-item">
                            <div class="pf-info-label">Konto erstellt am</div>
                            <div class="pf-info-value">
                                <?= isset($user['created_at']) ? date('d.m.Y', strtotime($user['created_at'])) : '–' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /col-md-8 -->
    </div><!-- /row -->

</div><!-- /.pf-wrap -->
</div><!-- /.main-content -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resendBtn = document.getElementById('resendVerificationBtn');
    const messageDiv = document.getElementById('verificationMessage');

    if (resendBtn) {
        resendBtn.addEventListener('click', function() {
            resendBtn.disabled = true;
            resendBtn.innerHTML = '<i class="anticon anticon-loading anticon-spin"></i> Wird gesendet …';
            messageDiv.style.display = 'none';

            fetch('ajax/send_verification_email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                messageDiv.style.display = 'block';
                if (data.success) {
                    messageDiv.className = 'alert alert-success mt-2';
                    messageDiv.innerHTML = '<i class="anticon anticon-check-circle mr-1"></i> ' + data.message;
                    setTimeout(function() {
                        resendBtn.disabled = false;
                        resendBtn.innerHTML = '<i class="anticon anticon-mail mr-1"></i> Bestätigungsmail erneut senden';
                    }, 60000);
                } else {
                    messageDiv.className = 'alert alert-danger mt-2';
                    messageDiv.innerHTML = '<i class="anticon anticon-close-circle mr-1"></i> ' + data.message;
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = '<i class="anticon anticon-mail mr-1"></i> Bestätigungsmail erneut senden';
                }
            })
            .catch(error => {
                console.error('Fehler:', error);
                messageDiv.style.display = 'block';
                messageDiv.className = 'alert alert-danger mt-2';
                messageDiv.innerHTML = '<i class="anticon anticon-close-circle mr-1"></i> Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.';
                resendBtn.disabled = false;
                resendBtn.innerHTML = '<i class="anticon anticon-mail mr-1"></i> Bestätigungsmail erneut senden';
            });
        });
    }
});
</script>

<?php require_once 'footer.php'; ?>