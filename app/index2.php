<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/satoshi_test_helpers.php';
require_once __DIR__ . '/header.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

$currentUser = [
    'first_name' => 'Benutzer',
    'balance' => 0.0,
];
$kycStatus = 'pending';
$packagesFeatureEnabled = true;
$satoshiThreshold = 50000.0;
$requiresSatoshiVerification = false;
$satoshiVerified = false;
$verifiedWallets = 0;
$caseSummary = [
    'active_cases' => 0,
    'platforms_checked' => 0,
    'status_updates' => 0,
    'total_recovered' => 0.0,
];
$feeSummary = [
    'total_fee_amount' => 0.0,
    'pending_fee_amount' => 0.0,
    'pending_fee_cases' => 0,
];
$recentCases = [];
$recentHistory = [];
$recentRecoveries = [];

try {
    $userStmt = $pdo->prepare("SELECT first_name, balance FROM users WHERE id = ? LIMIT 1");
    $userStmt->execute([$userId]);
    $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
    if ($userRow) {
        $currentUser = $userRow;
    }

    $settingsStmt = $pdo->query("SELECT packages_enabled FROM system_settings WHERE id = 1 LIMIT 1");
    $settingsRow = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    if ($settingsRow && isset($settingsRow['packages_enabled'])) {
        $packagesFeatureEnabled = ((int)$settingsRow['packages_enabled'] === 1);
    }

    $requiresSatoshiVerification = isSatoshiVerificationRequired(
        $packagesFeatureEnabled,
        (float)($currentUser['balance'] ?? 0),
        $satoshiThreshold
    );
    $satoshiVerified = $requiresSatoshiVerification ? userHasVerifiedTest($pdo, $userId) : false;

    $kycStmt = $pdo->prepare("
        SELECT status
        FROM kyc_verification_requests
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $kycStmt->execute([$userId]);
    $kycStatus = (string)($kycStmt->fetchColumn() ?: 'pending');

    $summaryStmt = $pdo->prepare("
        SELECT
            COUNT(*) AS active_cases,
            COUNT(DISTINCT platform_id) AS platforms_checked,
            COALESCE(SUM(recovered_amount), 0) AS total_recovered
        FROM cases
        WHERE user_id = ?
    ");
    $summaryStmt->execute([$userId]);
    $summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $caseSummary['active_cases'] = (int)($summaryRow['active_cases'] ?? 0);
    $caseSummary['platforms_checked'] = (int)($summaryRow['platforms_checked'] ?? 0);
    $caseSummary['total_recovered'] = (float)($summaryRow['total_recovered'] ?? 0);

    $statusCountStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM case_status_history h
        INNER JOIN cases c ON c.id = h.case_id
        WHERE c.user_id = ?
    ");
    $statusCountStmt->execute([$userId]);
    $caseSummary['status_updates'] = (int)$statusCountStmt->fetchColumn();

    $walletStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM user_payment_methods
        WHERE user_id = ? AND type = 'crypto' AND verification_status = 'verified'
    ");
    $walletStmt->execute([$userId]);
    $verifiedWallets = (int)$walletStmt->fetchColumn();

    $feeStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(fee_amount), 0) AS total_fee_amount,
            COALESCE(SUM(CASE WHEN status IN ('pending', 'processing') THEN fee_amount ELSE 0 END), 0) AS pending_fee_amount,
            SUM(CASE WHEN status IN ('pending', 'processing') AND fee_amount > 0 THEN 1 ELSE 0 END) AS pending_fee_cases
        FROM withdrawals
        WHERE user_id = ?
    ");
    $feeStmt->execute([$userId]);
    $feeSummary = $feeStmt->fetch(PDO::FETCH_ASSOC) ?: $feeSummary;

    $recentCasesStmt = $pdo->prepare("
        SELECT
            c.id,
            c.case_number,
            c.reported_amount,
            c.recovered_amount,
            c.status,
            c.recovery_stage,
            c.recovery_progress,
            c.admin_notes,
            c.updated_at,
            p.name AS platform_name
        FROM cases c
        LEFT JOIN scam_platforms p ON p.id = c.platform_id
        WHERE c.user_id = ?
        ORDER BY c.updated_at DESC, c.id DESC
        LIMIT 8
    ");
    $recentCasesStmt->execute([$userId]);
    $recentCases = $recentCasesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $historyStmt = $pdo->prepare("
        SELECT
            c.case_number,
            p.name AS platform_name,
            h.new_status,
            h.notes,
            h.created_at
        FROM case_status_history h
        INNER JOIN cases c ON c.id = h.case_id
        LEFT JOIN scam_platforms p ON p.id = c.platform_id
        WHERE c.user_id = ?
        ORDER BY h.created_at DESC, h.id DESC
        LIMIT 12
    ");
    $historyStmt->execute([$userId]);
    $recentHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $recoveryStmt = $pdo->prepare("
        SELECT
            c.case_number,
            p.name AS platform_name,
            rt.amount,
            rt.notes,
            rt.transaction_date
        FROM case_recovery_transactions rt
        INNER JOIN cases c ON c.id = rt.case_id
        LEFT JOIN scam_platforms p ON p.id = c.platform_id
        WHERE c.user_id = ?
        ORDER BY rt.transaction_date DESC, rt.id DESC
        LIMIT 10
    ");
    $recoveryStmt->execute([$userId]);
    $recentRecoveries = $recoveryStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('index2.php: ' . $e->getMessage());
}

$statusLabels = [
    'open' => ['label' => 'Offen', 'class' => 'badge badge-warning'],
    'documents_required' => ['label' => 'Dokumente erforderlich', 'class' => 'badge badge-info'],
    'under_review' => ['label' => 'In Prüfung', 'class' => 'badge badge-primary'],
    'refund_approved' => ['label' => 'Rückerstattung genehmigt', 'class' => 'badge badge-success'],
    'refund_rejected' => ['label' => 'Abgelehnt', 'class' => 'badge badge-danger'],
    'closed' => ['label' => 'Abgeschlossen', 'class' => 'badge badge-secondary'],
];
$kycLabels = [
    'approved' => ['label' => 'KYC bestätigt', 'class' => 'badge badge-success'],
    'verified' => ['label' => 'KYC bestätigt', 'class' => 'badge badge-success'],
    'pending' => ['label' => 'KYC ausstehend', 'class' => 'badge badge-warning'],
    'rejected' => ['label' => 'KYC abgelehnt', 'class' => 'badge badge-danger'],
];
$kycBadge = $kycLabels[$kycStatus] ?? ['label' => ucfirst($kycStatus), 'class' => 'badge badge-secondary'];
?>
<style>
.analysis-hero{background:linear-gradient(135deg,#1f3c88 0%,#2950a8 55%,#2da9e3 100%);border-radius:18px;color:#fff;padding:28px;box-shadow:0 20px 45px rgba(41,80,168,.18)}
.analysis-card{border:0;border-radius:16px;box-shadow:0 12px 30px rgba(15,23,42,.06);height:100%}
.analysis-stat{font-size:30px;font-weight:700;color:#1f2937}
.analysis-label{font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b}
.analysis-note{color:#64748b;font-size:13px;line-height:1.6}
.timeline-list{display:flex;flex-direction:column;gap:14px}
.timeline-item{border:1px solid #e5e7eb;border-radius:14px;padding:14px 16px;background:#fff}
.timeline-meta{font-size:12px;color:#64748b}
.platform-item{border:1px solid #e5e7eb;border-radius:14px;padding:16px;background:#fff}
.progress.slim{height:8px;border-radius:999px;background:#e5e7eb}
.progress.slim .progress-bar{border-radius:999px}
.cost-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}
.cost-box{border-radius:14px;background:#f8fafc;border:1px solid #e5e7eb;padding:16px}
.recovery-table td,.recovery-table th{vertical-align:middle}
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="analysis-hero mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:20px;">
                <div>
                    <div class="analysis-label text-white-50">KI-Analyse & Historie</div>
                    <h2 class="text-white mb-2">Willkommen zurück, <?= htmlspecialchars((string)($currentUser['first_name'] ?? 'Benutzer'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="mb-0" style="max-width:720px;color:rgba(255,255,255,.88);">
                        Dieses Cockpit zeigt ausschließlich echte Fallaktivitäten aus Ihrer Akte:
                        Statusänderungen, Plattformprüfungen, Rückgewinnungsbuchungen und aktuell hinterlegte Gebühren.
                    </p>
                </div>
                <div class="text-md-right">
                    <div class="mb-2"><span class="<?= $kycBadge['class'] ?>"><?= htmlspecialchars($kycBadge['label'], ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div style="font-size:28px;font-weight:700;">€ <?= number_format((float)($currentUser['balance'] ?? 0), 2, ',', '.') ?></div>
                    <small style="color:rgba(255,255,255,.78);">Kontostand</small>
                </div>
            </div>
        </div>

        <?php if ($requiresSatoshiVerification && !$satoshiVerified): ?>
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <i class="anticon anticon-warning mr-2"></i>
                Für Konten ab 50.000 € ist der Satoshi-Test noch offen.
                <a href="payment-methods.php#satoshi-verification" class="alert-link">Zur kombinierten Zahlungs- & Verifizierungsseite</a>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card analysis-card">
                    <div class="card-body">
                        <div class="analysis-label">Fälle in Betreuung</div>
                        <div class="analysis-stat"><?= (int)$caseSummary['active_cases'] ?></div>
                        <div class="analysis-note">Aktive und abgeschlossene Fälle, die im Analyseverlauf berücksichtigt werden.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card analysis-card">
                    <div class="card-body">
                        <div class="analysis-label">Plattformen geprüft</div>
                        <div class="analysis-stat"><?= (int)$caseSummary['platforms_checked'] ?></div>
                        <div class="analysis-note">Unterschiedliche gemeldete Plattformen mit dokumentiertem Prüfpfad.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card analysis-card">
                    <div class="card-body">
                        <div class="analysis-label">Statusupdates</div>
                        <div class="analysis-stat"><?= (int)$caseSummary['status_updates'] ?></div>
                        <div class="analysis-note">Vom Team eingetragene Änderungen und Hinweise aus Ihrer Fallhistorie.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card analysis-card">
                    <div class="card-body">
                        <div class="analysis-label">Gesamtgebühren</div>
                        <div class="analysis-stat">€ <?= number_format((float)($feeSummary['total_fee_amount'] ?? 0), 2, ',', '.') ?></div>
                        <div class="analysis-note">Bisher in Auszahlungsfällen hinterlegte Gebühren- und Bearbeitungskosten.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-xl-7 mb-4">
                <div class="card analysis-card">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h4 class="mb-1">Analyselauf-Verlauf</h4>
                        <p class="text-muted mb-0">Echte Statusprotokolle aus den vom Team gepflegten Fallakten.</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <?php if (empty($recentHistory)): ?>
                            <div class="alert alert-light border mb-0">Noch keine Analyseeinträge vorhanden.</div>
                        <?php else: ?>
                            <div class="timeline-list">
                                <?php foreach ($recentHistory as $entry):
                                    $status = $statusLabels[$entry['new_status']] ?? ['label' => ucfirst((string)$entry['new_status']), 'class' => 'badge badge-secondary'];
                                ?>
                                    <div class="timeline-item">
                                        <div class="d-flex justify-content-between align-items-start mb-2" style="gap:12px;">
                                            <div>
                                                <strong><?= htmlspecialchars((string)$entry['case_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <div class="timeline-meta"><?= htmlspecialchars((string)($entry['platform_name'] ?? 'Unbekannte Plattform'), ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>
                                            <span class="<?= $status['class'] ?>"><?= htmlspecialchars($status['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div class="analysis-note text-dark">
                                            <?= htmlspecialchars(trim((string)($entry['notes'] ?? 'Statusänderung protokolliert.')), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="timeline-meta mt-2"><?= htmlspecialchars(date('d.m.Y H:i', strtotime((string)$entry['created_at'])), ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-5 mb-4">
                <div class="card analysis-card">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h4 class="mb-1">Plattformen & Prüfstatus</h4>
                        <p class="text-muted mb-0">Aktuelle Fallstände mit KYC-Kontext und vom Team gepflegten Notizen.</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <?php if (empty($recentCases)): ?>
                            <div class="alert alert-light border mb-0">Es liegen noch keine Plattformprüfungen vor.</div>
                        <?php else: ?>
                            <div class="timeline-list">
                                <?php foreach ($recentCases as $case):
                                    $status = $statusLabels[$case['status']] ?? ['label' => ucfirst((string)$case['status']), 'class' => 'badge badge-secondary'];
                                    $progress = max(0, min(100, (int)($case['recovery_progress'] ?? 0)));
                                ?>
                                    <div class="platform-item">
                                        <div class="d-flex justify-content-between align-items-start mb-2" style="gap:12px;">
                                            <div>
                                                <strong><?= htmlspecialchars((string)$case['case_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <div class="timeline-meta"><?= htmlspecialchars((string)($case['platform_name'] ?? 'Unbekannte Plattform'), ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>
                                            <span class="<?= $status['class'] ?>"><?= htmlspecialchars($status['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div class="d-flex flex-wrap mb-2" style="gap:8px;">
                                            <span class="<?= $kycBadge['class'] ?>"><?= htmlspecialchars($kycBadge['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="badge badge-light border"><?= htmlspecialchars((string)($case['recovery_stage'] ?? 'initial'), ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between timeline-meta mb-1">
                                            <span>Fortschritt</span>
                                            <span><?= $progress ?>%</span>
                                        </div>
                                        <div class="progress slim mb-2">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width:<?= $progress ?>%"></div>
                                        </div>
                                        <div class="analysis-note">
                                            <?= htmlspecialchars(trim((string)($case['admin_notes'] ?: 'Keine zusätzliche Teamnotiz hinterlegt.')), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="timeline-meta mt-2">Zuletzt aktualisiert: <?= htmlspecialchars(date('d.m.Y H:i', strtotime((string)$case['updated_at'])), ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-xl-8 mb-4">
                <div class="card analysis-card">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h4 class="mb-1">Rückgewinnungsbuchungen</h4>
                        <p class="text-muted mb-0">Vom Team eingetragene Recovery-Transfers mit Notizen aus Ihrer Akte.</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <?php if (empty($recentRecoveries)): ?>
                            <div class="alert alert-light border mb-0">Noch keine Rückgewinnungsbuchungen vorhanden.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover recovery-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Fall</th>
                                            <th>Plattform</th>
                                            <th>Betrag</th>
                                            <th>Notiz</th>
                                            <th>Datum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentRecoveries as $recovery): ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string)$recovery['case_number'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars((string)($recovery['platform_name'] ?? 'Unbekannt'), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="font-weight-bold text-success">€ <?= number_format((float)$recovery['amount'], 2, ',', '.') ?></td>
                                                <td><?= htmlspecialchars(trim((string)($recovery['notes'] ?: 'Keine Zusatznotiz.')), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars(date('d.m.Y H:i', strtotime((string)$recovery['transaction_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 mb-4">
                <div class="card analysis-card">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h4 class="mb-1">Kosten & Freigaben</h4>
                        <p class="text-muted mb-0">Sofortüberblick zu Gebühren, Wallet-Freigaben und Kontoverifizierung.</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="cost-grid">
                            <div class="cost-box">
                                <div class="analysis-label">Offene Gebühren</div>
                                <div class="h4 mb-1">€ <?= number_format((float)($feeSummary['pending_fee_amount'] ?? 0), 2, ',', '.') ?></div>
                                <div class="analysis-note"><?= (int)($feeSummary['pending_fee_cases'] ?? 0) ?> Auszahlungsfall/-fälle mit offener Gebühr.</div>
                            </div>
                            <div class="cost-box">
                                <div class="analysis-label">Verifizierte Wallets</div>
                                <div class="h4 mb-1"><?= $verifiedWallets ?></div>
                                <div class="analysis-note">Freigegebene Krypto-Zahlungswege für Auszahlungen.</div>
                            </div>
                            <div class="cost-box">
                                <div class="analysis-label">Satoshi-Test</div>
                                <div class="h4 mb-1"><?= $requiresSatoshiVerification ? ($satoshiVerified ? 'Aktiv' : 'Offen') : 'Nicht nötig' ?></div>
                                <div class="analysis-note">
                                    <?php if ($requiresSatoshiVerification): ?>
                                        <a href="payment-methods.php#satoshi-verification">Jetzt prüfen</a>
                                    <?php else: ?>
                                        Verifizierung wird erst ab 50.000 € Kontostand eingeblendet.
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="cost-box">
                                <div class="analysis-label">Weitere Ansichten</div>
                                <div class="h4 mb-1">Historie</div>
                                <div class="analysis-note">
                                    <a href="history.php">Transaktions- & Kostenverlauf öffnen</a><br>
                                    <a href="recovered_funds.php">Recovery-Übersicht öffnen</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
