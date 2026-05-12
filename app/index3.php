<?php
/**
 * index3.php - Simple professional AI fund recovery portfolio dashboard.
 */
if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    echo '<h1>Server configuration error</h1><p>Missing config.php</p>';
    exit;
}
require_once __DIR__ . '/config.php';

if (file_exists(__DIR__ . '/header.php')) {
    require_once __DIR__ . '/header.php';
}

if (empty($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo '<h1>Database connection error</h1>';
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e3(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function money3(float $amount): string
{
    return '€' . number_format($amount, 2, ',', '.');
}

function txColorClass3(string $type): string
{
    return in_array($type, ['refund', 'deposit'], true) ? 'text-success' : 'text-danger';
}

$userId = $_SESSION['user_id'] ?? null;
$currentUserName = 'Nutzer';
$userBalance = 0.0;
$stats = ['total_cases' => 0, 'total_reported' => 0.0, 'total_recovered' => 0.0];
$recentCases = [];
$recentTransactions = [];

if (!empty($userId)) {
    try {
        $userStmt = $pdo->prepare('SELECT first_name, last_name, balance FROM users WHERE id = ?');
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $currentUserName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Nutzer';
            $userBalance = (float)($user['balance'] ?? 0);
        }

        $statsStmt = $pdo->prepare('SELECT COUNT(*) AS total_cases, COALESCE(SUM(reported_amount),0) AS total_reported, COALESCE(SUM(recovered_amount),0) AS total_recovered FROM cases WHERE user_id = ?');
        $statsStmt->execute([$userId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: $stats;

        $casesStmt = $pdo->prepare("SELECT c.case_number, c.status, c.reported_amount, c.recovered_amount, c.updated_at, p.name AS platform_name FROM cases c JOIN scam_platforms p ON p.id = c.platform_id WHERE c.user_id = ? ORDER BY c.updated_at DESC LIMIT 6");
        $casesStmt->execute([$userId]);
        $recentCases = $casesStmt->fetchAll(PDO::FETCH_ASSOC);

        $txStmt = $pdo->prepare("SELECT transaction_type, amount, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
        $txStmt->execute([$userId]);
        $recentTransactions = $txStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('index3.php DB error: ' . $e->getMessage());
    }
}

$reportedTotal = (float)($stats['total_reported'] ?? 0.0);
$recoveredTotal = (float)($stats['total_recovered'] ?? 0.0);
$openExposure = max(0, $reportedTotal - $recoveredTotal);
$recoveryRate = ($reportedTotal > 0) ? round(($recoveredTotal / $reportedTotal) * 100, 1) : 0;
$totalCases = (int)($stats['total_cases'] ?? 0);

$statusLabelMap = [
    'open' => 'Offen',
    'documents_required' => 'Dokumente erforderlich',
    'under_review' => 'In Prüfung',
    'refund_approved' => 'Erstattung genehmigt',
    'refund_rejected' => 'Erstattung abgelehnt',
    'closed' => 'Abgeschlossen',
];
?>

<div class="main-content db-theme-1">
    <div class="container-fluid" style="max-width: 1320px; padding-top: 24px;">
        <div class="card mb-4" style="border:0; border-radius:16px; background:linear-gradient(135deg,#1f3f91 0%,#2da9e3 100%); color:#fff;">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h2 class="mb-2 text-white">AI Fund Recovery Portfolio</h2>
                        <p class="mb-0" style="opacity:.92;">Willkommen zurück, <?= e3($currentUserName) ?>. Ihr Portfolio-Überblick in Echtzeit.</p>
                    </div>
                    <a href="case_submit.php" class="btn btn-light font-weight-semibold">Neuen Fall melden</a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted d-block mb-1">Kontostand</small>
                        <h4 class="mb-0"><?= e3(money3($userBalance)) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted d-block mb-1">Gemeldetes Volumen</small>
                        <h4 class="mb-0"><?= e3(money3($reportedTotal)) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted d-block mb-1">Wiederhergestellt</small>
                        <h4 class="mb-0 text-success"><?= e3(money3($recoveredTotal)) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted d-block mb-1">Aktive Fälle</small>
                        <h4 class="mb-0"><?= e3((string)$totalCases) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Portfolio Recovery Score</h5>
                    <strong><?= e3((string)$recoveryRate) ?>%</strong>
                </div>
                <div class="progress" style="height:10px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= e3((string)max(0, min(100, $recoveryRate))) ?>%"></div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6"><small class="text-muted">Offene Exposition: <?= e3(money3($openExposure)) ?></small></div>
                    <div class="col-md-6 text-md-right"><small class="text-muted">KI-basierte Fall-Priorisierung aktiv</small></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white border-0 pt-3">
                        <h5 class="mb-0">Aktuelle Recovery-Fälle</h5>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Fall</th>
                                        <th>Plattform</th>
                                        <th>Status</th>
                                        <th>Gemeldet</th>
                                        <th>Wiederhergestellt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recentCases) === 0): ?>
                                        <tr><td colspan="5" class="text-center text-muted py-4">Noch keine Fälle vorhanden.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentCases as $case): ?>
                                            <tr>
                                                <td><?= e3((string)($case['case_number'] ?? '-')) ?></td>
                                                <td><?= e3((string)($case['platform_name'] ?? '-')) ?></td>
                                                <td><span class="badge badge-pill badge-light"><?= e3($statusLabelMap[$case['status']] ?? (string)$case['status']) ?></span></td>
                                                <td><?= e3(money3((float)($case['reported_amount'] ?? 0))) ?></td>
                                                <td class="text-success"><?= e3(money3((float)($case['recovered_amount'] ?? 0))) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white border-0 pt-3">
                        <h5 class="mb-0">Letzte Transaktionen</h5>
                    </div>
                    <div class="card-body pt-2">
                        <?php if (count($recentTransactions) === 0): ?>
                            <p class="text-muted mb-0">Keine Transaktionen vorhanden.</p>
                        <?php else: ?>
                            <?php foreach ($recentTransactions as $tx): ?>
                                <?php $type = strtolower((string)($tx['transaction_type'] ?? 'transaction')); ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="font-weight-semibold text-capitalize"><?= e3($type) ?></div>
                                        <small class="text-muted"><?= e3(date('d.m.Y H:i', strtotime((string)($tx['created_at'] ?? 'now')))) ?></small>
                                    </div>
                                    <div class="<?= txColorClass3($type) ?>">
                                        <?= e3(money3((float)($tx['amount'] ?? 0))) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (file_exists(__DIR__ . '/footer.php')) {
    require_once __DIR__ . '/footer.php';
}
?>
