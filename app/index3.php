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

const DASHBOARD_ITEMS_LIMIT = 6;
const TICKET_MESSAGE_PREVIEW_LENGTH = 90;
const PAYMENT_METHOD_TYPE_CRYPTO = 'crypto';
const PAYMENT_METHOD_STATUS_VERIFIED = 'verified';
const SYSTEM_SETTINGS_PRIMARY_ID = 1;

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatCurrency(float $amount): string
{
    return '€' . number_format($amount, 2, ',', '.');
}

function getTransactionColorClass(string $type): string
{
    return in_array($type, ['refund', 'deposit'], true) ? 'text-success' : 'text-danger';
}

function extractDomainFromUrl(string $url): string
{
    $trimmedUrl = trim($url);
    if ($trimmedUrl === '') {
        return '';
    }

    $candidate = $trimmedUrl;
    if (!preg_match('#^https?://#i', $candidate)) {
        $candidate = 'https://' . ltrim($candidate, '/');
    }

    if (!filter_var($candidate, FILTER_VALIDATE_URL)) {
        return '';
    }

    return (string)(parse_url($candidate, PHP_URL_HOST) ?? '');
}

function getSafeHttpUrl(string $url): string
{
    $trimmedUrl = trim($url);
    if ($trimmedUrl === '') {
        return '';
    }

    $candidate = $trimmedUrl;
    if (!preg_match('#^https?://#i', $candidate)) {
        $candidate = 'https://' . ltrim($candidate, '/');
    }

    if (!filter_var($candidate, FILTER_VALIDATE_URL)) {
        return '';
    }

    $scheme = strtolower((string)parse_url($candidate, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return '';
    }

    return $candidate;
}

function truncatePreviewText(string $text, int $maxChars): string
{
    $cleanText = trim($text);
    if (mb_strlen($cleanText) <= $maxChars) {
        return $cleanText;
    }

    return mb_substr($cleanText, 0, $maxChars) . '…';
}

$userId = $_SESSION['user_id'] ?? null;
$currentUserName = 'Nutzer';
$userBalance = 0.0;
$stats = ['total_cases' => 0, 'total_reported' => 0.0, 'total_recovered' => 0.0];
$recentCases = [];
$recentTransactions = [];
$unreadReplies = [];
$kycStatus = 'pending';
$hasVerifiedPaymentMethod = false;
$officialSiteUrl = '';
$officialDomain = '';
$safeOfficialSiteUrl = '';
$hostMatchesOfficialDomain = false;
$itemLimit = DASHBOARD_ITEMS_LIMIT;

if (!empty($userId)) {
    try {
        $userStmt = $pdo->prepare('SELECT first_name, last_name, balance FROM users WHERE id = ?');
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $currentUserName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Nutzer';
            $userBalance = (float)($user['balance'] ?? 0);
        }

        $statsStmt = $pdo->prepare(
            'SELECT COUNT(*) AS total_cases,
                    COALESCE(SUM(reported_amount),0) AS total_reported,
                    COALESCE(SUM(recovered_amount),0) AS total_recovered
             FROM cases
             WHERE user_id = ?'
        );
        $statsStmt->execute([$userId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: $stats;

        $kycStmt = $pdo->prepare('SELECT status FROM kyc_verification_requests WHERE user_id = ? ORDER BY id DESC LIMIT 1');
        $kycStmt->execute([$userId]);
        $kycRow = $kycStmt->fetch(PDO::FETCH_ASSOC);
        $kycStatus = $kycRow ? (string)$kycRow['status'] : 'pending';

        $verifiedPmStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM user_payment_methods
             WHERE user_id = ?
               AND type = ?
               AND verification_status = ?'
        );
        $verifiedPmStmt->execute([$userId, PAYMENT_METHOD_TYPE_CRYPTO, PAYMENT_METHOD_STATUS_VERIFIED]);
        $hasVerifiedPaymentMethod = ((int)$verifiedPmStmt->fetchColumn() > 0);

        $casesStmt = $pdo->prepare(
            'SELECT c.case_number,
                    c.status,
                    c.reported_amount,
                    c.recovered_amount,
                    c.updated_at,
                    p.name AS platform_name
             FROM cases c
             JOIN scam_platforms p ON p.id = c.platform_id
             WHERE c.user_id = :userId
             ORDER BY c.updated_at DESC
             LIMIT :itemLimit'
        );
        $casesStmt->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
        $casesStmt->bindValue(':itemLimit', $itemLimit, PDO::PARAM_INT);
        $casesStmt->execute();
        $recentCases = $casesStmt->fetchAll(PDO::FETCH_ASSOC);

        $txStmt = $pdo->prepare(
            'SELECT transaction_type, amount, created_at
             FROM transactions
             WHERE user_id = :userId
             ORDER BY created_at DESC
             LIMIT :itemLimit'
        );
        $txStmt->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
        $txStmt->bindValue(':itemLimit', $itemLimit, PDO::PARAM_INT);
        $txStmt->execute();
        $recentTransactions = $txStmt->fetchAll(PDO::FETCH_ASSOC);

        $replyStmt = $pdo->prepare(
            "SELECT tr.id, tr.message, tr.created_at, st.subject, st.ticket_number, st.id AS ticket_id
             FROM ticket_replies tr
             JOIN support_tickets st ON st.id = tr.ticket_id
             WHERE st.user_id = ?
               AND tr.admin_id IS NOT NULL
               AND tr.read_at IS NULL
             ORDER BY tr.created_at DESC
             LIMIT 5"
        );
        $replyStmt->execute([$userId]);
        $unreadReplies = $replyStmt->fetchAll(PDO::FETCH_ASSOC);

        $settingsStmt = $pdo->prepare('SELECT site_url FROM system_settings WHERE id = ? LIMIT 1');
        $settingsStmt->execute([SYSTEM_SETTINGS_PRIMARY_ID]);
        $settingsRow = $settingsStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$settingsRow) {
            $fallbackSettingsStmt = $pdo->prepare('SELECT site_url FROM system_settings ORDER BY id ASC LIMIT 1');
            $fallbackSettingsStmt->execute();
            $settingsRow = $fallbackSettingsStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if (!empty($settingsRow['site_url'])) {
            $officialSiteUrl = trim((string)$settingsRow['site_url']);
            $safeOfficialSiteUrl = getSafeHttpUrl($officialSiteUrl);
            $officialDomain = extractDomainFromUrl($officialSiteUrl);

            $currentHost = strtolower((string)($_SERVER['SERVER_NAME'] ?? ''));
            $normalizedCurrentHost = preg_replace('/:\d+$/', '', $currentHost);
            $normalizedOfficialHost = strtolower($officialDomain);
            if ($normalizedOfficialHost !== '' && $normalizedCurrentHost !== '') {
                $hostMatchesOfficialDomain = ($normalizedCurrentHost === $normalizedOfficialHost);
            }
        }
    } catch (PDOException $e) {
        error_log('index3.php DB error: ' . $e->getMessage());
    }
}

$reportedTotal = (float)($stats['total_reported'] ?? 0.0);
$recoveredTotal = (float)($stats['total_recovered'] ?? 0.0);
$openExposure = max(0, $reportedTotal - $recoveredTotal);
$recoveryRate = ($reportedTotal > 0) ? round(($recoveredTotal / $reportedTotal) * 100, 1) : 0;
$totalCases = (int)($stats['total_cases'] ?? 0);
$recentTransactionCount = count($recentTransactions);

$todoItems = [];
if ($kycStatus !== 'approved') {
    $todoItems[] = [
        'text' => 'Bitte schließen Sie Ihre KYC-Verifizierung ab, um volle Sicherheits- und Auszahlungsfunktionen zu nutzen.',
        'link' => 'kyc.php',
        'linkLabel' => 'KYC abschließen',
    ];
}
if (!$hasVerifiedPaymentMethod) {
    $todoItems[] = [
        'text' => 'Hinterlegen und verifizieren Sie eine Zahlungsmethode für sichere Auszahlungen über Escrow.',
        'link' => 'payment-methods.php',
        'linkLabel' => 'Zahlungsmethode einrichten',
    ];
}
if ($totalCases === 0) {
    $todoItems[] = [
        'text' => 'Legen Sie Ihren ersten Fall an, damit unsere KI mit der Analyse starten kann.',
        'link' => 'cases.php',
        'linkLabel' => 'Ersten Fall anlegen',
    ];
}

$statusLabelMap = [
    'open' => 'Offen',
    'documents_required' => 'Dokumente erforderlich',
    'under_review' => 'In Prüfung',
    'refund_approved' => 'Erstattung genehmigt',
    'refund_rejected' => 'Erstattung abgelehnt',
    'closed' => 'Abgeschlossen',
];
$statusBadgeMap = [
    'open' => 'badge-warning',
    'documents_required' => 'badge-info',
    'under_review' => 'badge-primary',
    'refund_approved' => 'badge-success',
    'refund_rejected' => 'badge-danger',
    'closed' => 'badge-secondary',
];
?>

<style>
    .ai-monitor-card {
        border: 0;
        border-radius: 16px;
        color: #fff;
        background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 45%, #2950a8 100%);
        overflow: hidden;
        position: relative;
    }
    .ai-monitor-card::after {
        content: '';
        position: absolute;
        top: -70px;
        right: -70px;
        width: 220px;
        height: 220px;
        background: radial-gradient(circle, rgba(45, 169, 227, .35) 0%, transparent 70%);
        pointer-events: none;
    }
    .ai-radar-wrap {
        width: 132px;
        height: 132px;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,.28);
        position: relative;
        margin: 0 auto;
        box-shadow: inset 0 0 25px rgba(45,169,227,.25);
    }
    .ai-radar-wrap::before,
    .ai-radar-wrap::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        border: 1px solid rgba(255,255,255,.22);
    }
    .ai-radar-wrap::before { inset: 18px; }
    .ai-radar-wrap::after { inset: 38px; }
    .ai-radar-beam {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 58px;
        height: 2px;
        background: linear-gradient(90deg, rgba(77,226,255,.1), #4de2ff 80%);
        transform-origin: left center;
        animation: aiRadarSpin 2.8s linear infinite;
    }
    .ai-radar-dot {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 11px;
        height: 11px;
        margin: -5.5px 0 0 -5.5px;
        border-radius: 50%;
        background: #4de2ff;
        box-shadow: 0 0 0 rgba(77,226,255,.4);
        animation: aiPulseDot 1.6s ease-in-out infinite;
    }
    .ai-monitor-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255,255,255,.1);
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
    }
    .ai-monitor-chip i {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #2ee76d;
        box-shadow: 0 0 10px rgba(46,231,109,.8);
        animation: aiBlink 1.2s infinite;
    }
    .ai-scan-track {
        height: 8px;
        border-radius: 999px;
        background: rgba(255,255,255,.2);
        overflow: hidden;
    }
    .ai-scan-bar {
        height: 100%;
        width: 18%;
        border-radius: 999px;
        background: linear-gradient(90deg, #4de2ff, #4f8dff);
        transition: width .7s ease;
    }
    .ai-feed-line {
        color: rgba(255,255,255,.78);
        font-size: 12px;
        margin-bottom: 4px;
    }
    @keyframes aiRadarSpin { to { transform: rotate(360deg); } }
    @keyframes aiPulseDot {
        0% { box-shadow: 0 0 0 0 rgba(77,226,255,.45); }
        100% { box-shadow: 0 0 0 15px rgba(77,226,255,0); }
    }
    @keyframes aiBlink { 50% { opacity: .45; } }
</style>

<div class="main-content db-theme-1">
    <div class="container-fluid" style="max-width: 1320px; padding-top: 24px;">
        <div class="card mb-4" style="border:0; border-radius:16px; background:linear-gradient(135deg,#1f3f91 0%,#2da9e3 100%); color:#fff;">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h2 class="mb-2 text-white">KI-Fondsrückgewinnungs-Portfolio</h2>
                        <p class="mb-0" style="opacity:.92;">Willkommen zurück, <?= escapeHtml($currentUserName) ?>. Ihr Portfolio-Überblick in Echtzeit.</p>
                    </div>
                    <a href="cases.php" class="btn btn-light font-weight-semibold">Fälle verwalten</a>
                </div>
            </div>
        </div>

        <?php if (!$hostMatchesOfficialDomain && $officialDomain !== ''): ?>
            <div class="alert alert-danger mb-3" role="alert" aria-live="assertive">
                <strong>Warnung:</strong> Die aktuelle Domain stimmt nicht mit der offiziellen Domain <strong><?= escapeHtml($officialDomain) ?></strong> überein.
            </div>
        <?php endif; ?>

        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between mb-3" role="alert" aria-live="polite">
            <div>
                <strong>Sicherheitshinweis:</strong>
                <?php if ($officialDomain !== ''): ?>
                    Bitte prüfen Sie, dass Sie sich auf <strong><?= escapeHtml($officialDomain) ?></strong> befinden.
                <?php else: ?>
                    Die offizielle Domain konnte nicht automatisch ermittelt werden. Bitte öffnen Sie den offiziellen Link nur über das Kundenportal.
                <?php endif; ?>
            </div>
            <?php if ($safeOfficialSiteUrl !== ''): ?>
                <a href="<?= escapeHtml($safeOfficialSiteUrl) ?>" class="btn btn-sm btn-outline-warning mt-2 mt-md-0" target="_blank" rel="noopener noreferrer">Offizielle Domain öffnen</a>
            <?php endif; ?>
        </div>

        <div class="alert alert-danger mb-3" role="alert" aria-live="assertive">
            <strong>Wichtiger Schutz:</strong> Wir fordern niemals Zahlungen ohne Escrow-Verfahren an. Leisten Sie keine Direktzahlung außerhalb des Portals.
        </div>

        <?php if (!empty($todoItems)): ?>
            <div class="card mb-3" style="border-left:4px solid #ffc107;">
                <div class="card-body">
                    <h6 class="mb-3">Sicherheitsrelevante Aufgaben</h6>
                    <?php foreach ($todoItems as $todoItem): ?>
                        <div class="d-flex flex-wrap align-items-center justify-content-between border rounded px-3 py-2 mb-2">
                            <span class="text-muted mr-3"><?= escapeHtml($todoItem['text']) ?></span>
                            <a href="<?= escapeHtml($todoItem['link']) ?>" class="btn btn-sm btn-warning mt-2 mt-md-0"><?= escapeHtml($todoItem['linkLabel']) ?></a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($unreadReplies)): ?>
            <div class="card mb-4" style="border-left:4px solid #17a2b8;">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="mb-0">Ungelesene Support-Tickets</h6>
                        <span class="badge badge-info"><?= count($unreadReplies) ?> neu</span>
                    </div>
                    <?php foreach ($unreadReplies as $reply): ?>
                        <div class="d-flex flex-wrap align-items-center justify-content-between border rounded px-3 py-2 mb-2">
                            <div class="mr-3">
                                <div class="font-weight-semibold"><?= escapeHtml((string)$reply['subject']) ?> <small class="text-muted">#<?= escapeHtml((string)$reply['ticket_number']) ?></small></div>
                                <small class="text-muted"><?= escapeHtml(truncatePreviewText((string)$reply['message'], TICKET_MESSAGE_PREVIEW_LENGTH)) ?></small>
                            </div>
                            <a href="support.php?ticket=<?= (int)$reply['ticket_id'] ?>" class="btn btn-sm btn-info mt-2 mt-md-0">Antwort lesen</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row mb-3">
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="mb-1">Vertrauensmerkmal</h6>
                        <small class="text-muted">Transparente Escrow-Abwicklung ohne Direktforderungen.</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="mb-1">Datensicherheit</h6>
                        <small class="text-muted">Verschlüsselte Verarbeitung sensibler Fall- und Transaktionsdaten.</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="mb-1">Professioneller Support</h6>
                        <small class="text-muted">Verbindliche Kommunikation über Tickets für nachvollziehbare Entscheidungen.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card ai-monitor-card mb-4">
            <div class="card-body p-4" style="position:relative;z-index:1;">
                <div class="row align-items-center">
                    <div class="col-lg-3 mb-3 mb-lg-0 text-center">
                        <div class="ai-radar-wrap" aria-hidden="true">
                            <div class="ai-radar-beam"></div>
                            <div class="ai-radar-dot"></div>
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                            <h5 class="mb-2 mb-md-0 text-white">KI-Transaktionsprüfung in Echtzeit</h5>
                            <span class="ai-monitor-chip"><i></i> KI-Engine aktiv</span>
                        </div>
                        <p id="aiStatusText" class="mb-2" style="opacity:.86;">Prüfe Transaktionsmuster und erkenne Auffälligkeiten …</p>
                        <div class="ai-scan-track mb-2">
                            <div id="aiScanBar" class="ai-scan-bar"></div>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center" style="font-size:12px;color:rgba(255,255,255,.82);">
                            <span id="aiProgressLabel">18% geprüft</span>
                            <span><strong id="aiTxCount"><?= escapeHtml((string)$recentTransactionCount) ?></strong> Transaktionen in der aktuellen Analyse</span>
                        </div>
                        <div class="mt-3">
                            <div class="ai-feed-line">• Verhaltensbasierte Prüfung von Auszahlungs- und Einzahlungsströmen</div>
                            <div class="ai-feed-line">• Risiko-Scoring je Vorgang mit Priorisierung für schnellere Bearbeitung</div>
                            <div class="ai-feed-line">• Kontinuierlicher Abgleich mit bekannten Betrugsmustern</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted d-block mb-1">Kontostand</small>
                        <h4 class="mb-0"><?= escapeHtml(formatCurrency($userBalance)) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted d-block mb-1">Gemeldetes Volumen</small>
                        <h4 class="mb-0"><?= escapeHtml(formatCurrency($reportedTotal)) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted d-block mb-1">Wiederhergestellt</small>
                        <h4 class="mb-0 text-success"><?= escapeHtml(formatCurrency($recoveredTotal)) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted d-block mb-1">Aktive Fälle</small>
                        <h4 class="mb-0"><?= escapeHtml((string)$totalCases) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Portfolio-Rückgewinnungsquote</h5>
                    <strong><?= escapeHtml((string)$recoveryRate) ?>%</strong>
                </div>
                <div class="progress" style="height:10px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= escapeHtml((string)max(0, min(100, $recoveryRate))) ?>%"></div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6"><small class="text-muted">Offene Exposition: <?= escapeHtml(formatCurrency($openExposure)) ?></small></div>
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
                                                <td><?= escapeHtml((string)($case['case_number'] ?? '-')) ?></td>
                                                <td><?= escapeHtml((string)($case['platform_name'] ?? '-')) ?></td>
                                                <td><span class="badge badge-pill <?= $statusBadgeMap[$case['status']] ?? 'badge-light' ?>"><?= escapeHtml($statusLabelMap[$case['status']] ?? (string)$case['status']) ?></span></td>
                                                <td><?= escapeHtml(formatCurrency((float)($case['reported_amount'] ?? 0))) ?></td>
                                                <td class="text-success"><?= escapeHtml(formatCurrency((float)($case['recovered_amount'] ?? 0))) ?></td>
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
                                        <div class="font-weight-semibold text-capitalize"><?= escapeHtml($type) ?></div>
                                        <small class="text-muted"><?= escapeHtml(date('d.m.Y H:i', strtotime((string)($tx['created_at'] ?? 'now')))) ?></small>
                                    </div>
                                    <div class="<?= getTransactionColorClass($type) ?>">
                                        <?= escapeHtml(formatCurrency((float)($tx['amount'] ?? 0))) ?>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var statusEl = document.getElementById('aiStatusText');
    var barEl = document.getElementById('aiScanBar');
    var progressEl = document.getElementById('aiProgressLabel');
    var txCountEl = document.getElementById('aiTxCount');
    if (!statusEl || !barEl || !progressEl || !txCountEl) return;

    var statusMessages = [
        'Prüfe Transaktionsmuster und erkenne Auffälligkeiten …',
        'KI gleicht Vorgänge mit bekannten Risikomustern ab …',
        'Ermittle Priorität für verdächtige Bewegungen …',
        'Validiere Zahlungsketten und Herkunftsbezüge …'
    ];
    var progress = 18;
    var statusIndex = 0;
    var txCount = parseInt(txCountEl.textContent, 10) || 0;

    setInterval(function () {
        statusIndex = (statusIndex + 1) % statusMessages.length;
        statusEl.textContent = statusMessages[statusIndex];

        progress += 11;
        if (progress > 96) {
            progress = 24;
        }
        barEl.style.width = progress + '%';
        progressEl.textContent = progress + '% geprüft';

        txCount += Math.floor(Math.random() * 3);
        txCountEl.textContent = String(txCount);
    }, 2200);
});
</script>

<?php
if (file_exists(__DIR__ . '/footer.php')) {
    require_once __DIR__ . '/footer.php';
}
?>
