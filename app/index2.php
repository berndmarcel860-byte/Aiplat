<?php
/**
 * KI Dashboard – AI-powered recovery intelligence for users.
 * Shows admin-managed entries: AI search scans, platform checks, reported platforms.
 * Transaction fees (find + recover) and KYC details are shown from admin-entered data.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/satoshi_test_helpers.php';
require_once __DIR__ . '/header.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

// ── Ensure ki_scan_entries table exists ────────────────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ki_scan_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            entry_type ENUM('ai_search','platform_check','reported_platform') NOT NULL DEFAULT 'ai_search',
            title VARCHAR(255) NOT NULL,
            description TEXT,
            platform_name VARCHAR(255) DEFAULT NULL,
            platform_url VARCHAR(500) DEFAULT NULL,
            kyc_status ENUM('verified','pending','not_required','failed') NOT NULL DEFAULT 'not_required',
            status ENUM('scanning','found','not_found','verified','flagged','resolved') NOT NULL DEFAULT 'scanning',
            fee_find_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            fee_recover_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            transaction_hash VARCHAR(500) DEFAULT NULL,
            blockchain_network VARCHAR(100) DEFAULT NULL,
            risk_level ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
            is_visible TINYINT(1) NOT NULL DEFAULT 1,
            admin_notes TEXT DEFAULT NULL,
            created_by_admin INT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_entry_type (entry_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Throwable $e) { /* table already exists or DB limitation */ }

// ── User & account data ────────────────────────────────────────────────────
$currentUser = ['first_name' => 'Benutzer', 'balance' => 0.0];
$kycStatus = 'pending';
$packagesFeatureEnabled = true;
$satoshiThreshold = 50000.0;
$requiresSatoshiVerification = false;
$satoshiVerified = false;

// KI scan entries (admin-managed)
$aiSearchEntries       = [];
$platformCheckEntries  = [];
$reportedPlatformEntries = [];
$kiStats = ['total_entries' => 0, 'total_fee_find' => 0.0, 'total_fee_recover' => 0.0, 'flagged_count' => 0];

// Case summary
$caseSummary = ['active_cases' => 0, 'platforms_checked' => 0, 'total_recovered' => 0.0];
$feeSummary  = ['total_fee_amount' => 0.0, 'pending_fee_amount' => 0.0, 'pending_fee_cases' => 0];

try {
    $userStmt = $pdo->prepare("SELECT first_name, balance FROM users WHERE id = ? LIMIT 1");
    $userStmt->execute([$userId]);
    $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
    if ($userRow) $currentUser = $userRow;

    $settingsStmt = $pdo->query("SELECT packages_enabled FROM system_settings WHERE id = 1 LIMIT 1");
    $settingsRow = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    if ($settingsRow && isset($settingsRow['packages_enabled'])) {
        $packagesFeatureEnabled = ((int)$settingsRow['packages_enabled'] === 1);
    }

    $requiresSatoshiVerification = isSatoshiVerificationRequired(
        $packagesFeatureEnabled, (float)($currentUser['balance'] ?? 0), $satoshiThreshold
    );
    $satoshiVerified = $requiresSatoshiVerification ? userHasVerifiedTest($pdo, $userId) : false;

    $kycStmt = $pdo->prepare("SELECT status FROM kyc_verification_requests WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $kycStmt->execute([$userId]);
    $kycStatus = (string)($kycStmt->fetchColumn() ?: 'pending');

    // Case & fee summaries (from real case data)
    $summaryStmt = $pdo->prepare("SELECT COUNT(*) AS active_cases, COUNT(DISTINCT platform_id) AS platforms_checked, COALESCE(SUM(recovered_amount), 0) AS total_recovered FROM cases WHERE user_id = ?");
    $summaryStmt->execute([$userId]);
    $caseSummary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: $caseSummary;

    $feeStmt = $pdo->prepare("SELECT COALESCE(SUM(fee_amount), 0) AS total_fee_amount, COALESCE(SUM(CASE WHEN status IN ('pending','processing') THEN fee_amount ELSE 0 END), 0) AS pending_fee_amount, SUM(CASE WHEN status IN ('pending','processing') AND fee_amount > 0 THEN 1 ELSE 0 END) AS pending_fee_cases FROM withdrawals WHERE user_id = ?");
    $feeStmt->execute([$userId]);
    $feeSummary = $feeStmt->fetch(PDO::FETCH_ASSOC) ?: $feeSummary;

    // KI scan entries (admin-added, visible only)
    $kiEntriesStmt = $pdo->prepare("SELECT * FROM ki_scan_entries WHERE user_id = ? AND is_visible = 1 ORDER BY created_at DESC");
    $kiEntriesStmt->execute([$userId]);
    $allKiEntries = $kiEntriesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($allKiEntries as $entry) {
        $kiStats['total_entries']++;
        $kiStats['total_fee_find']    += (float)$entry['fee_find_amount'];
        $kiStats['total_fee_recover'] += (float)$entry['fee_recover_amount'];
        if ($entry['status'] === 'flagged') $kiStats['flagged_count']++;

        if ($entry['entry_type'] === 'ai_search')          $aiSearchEntries[]        = $entry;
        elseif ($entry['entry_type'] === 'platform_check') $platformCheckEntries[]   = $entry;
        else                                               $reportedPlatformEntries[] = $entry;
    }
} catch (Throwable $e) {
    error_log('index2.php: ' . $e->getMessage());
}

$kycLabels = [
    'approved'  => ['label' => 'KYC Confirmed',  'class' => 'badge-success'],
    'verified'  => ['label' => 'KYC Confirmed',  'class' => 'badge-success'],
    'pending'   => ['label' => 'KYC Pending',    'class' => 'badge-warning'],
    'rejected'  => ['label' => 'KYC Rejected',   'class' => 'badge-danger'],
];
$kycBadge = $kycLabels[$kycStatus] ?? ['label' => ucfirst($kycStatus), 'class' => 'badge-secondary'];

$statusColors = [
    'scanning'  => 'warning',
    'found'     => 'primary',
    'not_found' => 'secondary',
    'verified'  => 'success',
    'flagged'   => 'danger',
    'resolved'  => 'info',
];
$riskColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];
?>
<style>
/* ── KI Dashboard Styles ──────────────────────────────────────────────── */
.ki-hero{background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 55%,#2563eb 100%);border-radius:18px;color:#fff;padding:28px;box-shadow:0 20px 45px rgba(15,23,42,.2);position:relative;overflow:hidden}
.ki-hero::before{content:'';position:absolute;top:-60px;right:-60px;width:240px;height:240px;background:rgba(255,255,255,.07);border-radius:50%}
.ki-hero::after{content:'';position:absolute;bottom:-80px;left:-40px;width:180px;height:180px;background:rgba(37,99,235,.15);border-radius:50%}
.ki-hero>*{position:relative;z-index:1}
.ki-card{border:0;border-radius:16px;box-shadow:0 8px 24px rgba(15,23,42,.07);height:100%;background:#fff}
.ki-stat{font-size:28px;font-weight:700;color:#0f172a;line-height:1}
.ki-label{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b;margin-bottom:8px}
.ki-note{font-size:13px;color:#64748b;line-height:1.5;margin-top:6px}

/* Scan Entry Cards */
.scan-entry{border:1px solid #e5e7eb;border-radius:14px;padding:16px 18px;background:#fff;transition:box-shadow .2s,transform .2s}
.scan-entry:hover{box-shadow:0 8px 20px rgba(15,23,42,.1);transform:translateY(-2px)}
.scan-entry-header{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:10px}
.scan-entry-title{font-weight:700;color:#0f172a;font-size:14px}
.scan-entry-meta{font-size:12px;color:#64748b;margin-top:2px}
.scan-entry-desc{font-size:13px;color:#475569;line-height:1.6;margin-bottom:8px}
.scan-entry-footer{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9}
.fee-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700}
.fee-chip.find{background:#eff6ff;color:#1d4ed8}
.fee-chip.recover{background:#ecfdf5;color:#166534}
.tx-hash{font-family:monospace;font-size:11px;color:#64748b;background:#f8fafc;padding:3px 8px;border-radius:6px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* AI scanning animation */
@keyframes pulse-ring{0%{transform:scale(.95);box-shadow:0 0 0 0 rgba(37,99,235,.5)}70%{transform:scale(1);box-shadow:0 0 0 10px rgba(37,99,235,0)}100%{transform:scale(.95);box-shadow:0 0 0 0 rgba(37,99,235,0)}}
@keyframes scan-line{0%{top:0}100%{top:100%}}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}
.scan-icon{width:36px;height:36px;border-radius:50%;background:rgba(37,99,235,.1);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
.scan-icon.active{animation:pulse-ring 1.8s infinite}
.scanning-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#f59e0b;animation:blink 1.2s infinite}
.section-title{font-size:16px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px;margin-bottom:4px}
.section-subtitle{font-size:13px;color:#64748b;margin-bottom:16px}
.empty-ki{text-align:center;padding:32px 20px;color:#94a3b8}
.empty-ki i{font-size:36px;margin-bottom:10px;display:block}

/* Cost breakdown */
.cost-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px}
.cost-box{border-radius:14px;background:#f8fafc;border:1px solid #e5e7eb;padding:16px 18px}
.cost-box .cost-val{font-size:22px;font-weight:700;color:#0f172a;line-height:1;margin:6px 0 4px}

/* Badge overrides */
.badge-scanning{background:#f59e0b;color:#fff}
</style>

<div class="main-content">
<div class="container-fluid">

    <!-- Hero -->
    <div class="ki-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:20px;">
            <div>
                <div class="ki-label text-white-50 mb-2"><i class="anticon anticon-robot mr-1"></i> KI INTELLIGENCE DASHBOARD</div>
                <h2 class="text-white mb-2">Willkommen, <?= htmlspecialchars((string)($currentUser['first_name'] ?? 'Benutzer'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="mb-0" style="max-width:680px;color:rgba(255,255,255,.88);font-size:15px;line-height:1.7;">
                    Ihr persönliches KI-Analyse-Cockpit: Alle KI-Scans, Plattformprüfungen und gemeldeten Plattformen
                    werden hier exklusiv für Ihren Fall aufgeführt – inklusive anfallender Kosten für Suche und Rückgewinnung.
                </p>
            </div>
            <div class="text-right" style="min-width:160px;">
                <span class="badge <?= $kycBadge['class'] ?> mb-2 d-inline-block"><?= htmlspecialchars($kycBadge['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <div style="font-size:28px;font-weight:700;color:#fff;">€ <?= number_format((float)($currentUser['balance'] ?? 0), 2, ',', '.') ?></div>
                <div style="font-size:12px;color:rgba(255,255,255,.7);">Kontostand</div>
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

    <!-- KI Stats row -->
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="card ki-card"><div class="card-body">
                <div class="ki-label">KI-Scan-Einträge</div>
                <div class="ki-stat"><?= (int)$kiStats['total_entries'] ?></div>
                <div class="ki-note">Vom Team eingepflegte AI-Scans und Prüfeinträge.</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="card ki-card"><div class="card-body">
                <div class="ki-label">Suchkosten (Find Fee)</div>
                <div class="ki-stat">€ <?= number_format($kiStats['total_fee_find'], 2, ',', '.') ?></div>
                <div class="ki-note">Gesamtkosten für KI-gestützte Transaktionssuche.</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="card ki-card"><div class="card-body">
                <div class="ki-label">Rückgewinnungskosten</div>
                <div class="ki-stat">€ <?= number_format($kiStats['total_fee_recover'], 2, ',', '.') ?></div>
                <div class="ki-note">Gesamtkosten für die Rückholung der gefundenen Transaktionen.</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="card ki-card"><div class="card-body">
                <div class="ki-label">Aktive Fälle / Geprüfte Plattformen</div>
                <div class="ki-stat"><?= (int)$caseSummary['active_cases'] ?> / <?= (int)$caseSummary['platforms_checked'] ?></div>
                <div class="ki-note">Laufende Fälle und unterschiedliche geprüfte Plattformen.</div>
            </div></div>
        </div>
    </div>

    <!-- Main 3-column grid -->
    <div class="row mb-4">

        <!-- ── AI Search History ─────────────────────────────────────────── -->
        <div class="col-xl-4 mb-4">
            <div class="card ki-card">
                <div class="card-body" style="padding:20px;">
                    <div class="section-title">
                        <span class="scan-icon active" style="background:rgba(37,99,235,.1);">
                            <i class="anticon anticon-search" style="color:#2563eb;font-size:16px;"></i>
                        </span>
                        KI-Transaktionssuche
                    </div>
                    <div class="section-subtitle">Vom Team gestartete KI-Blockchain-Scans für Ihren Fall.</div>

                    <?php if (empty($aiSearchEntries)): ?>
                        <div class="empty-ki">
                            <i class="anticon anticon-search"></i>
                            <div>Noch keine KI-Suchläufe für Ihr Konto eingetragen.</div>
                            <small>Das Team wird diese Sektion befüllen, sobald Scans gestartet werden.</small>
                        </div>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <?php foreach ($aiSearchEntries as $i => $entry):
                                $isScanning = $entry['status'] === 'scanning';
                                $sc = $statusColors[$entry['status']] ?? 'secondary';
                            ?>
                                <div class="scan-entry" style="animation-delay:<?= $i * 0.07 ?>s">
                                    <div class="scan-entry-header">
                                        <div>
                                            <div class="scan-entry-title">
                                                <?php if ($isScanning): ?>
                                                    <span class="scanning-dot mr-1"></span>
                                                <?php endif; ?>
                                                <?= htmlspecialchars((string)$entry['title'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div class="scan-entry-meta">
                                                <?php if (!empty($entry['blockchain_network'])): ?>
                                                    <i class="anticon anticon-link mr-1"></i><?= htmlspecialchars((string)$entry['blockchain_network'], ENT_QUOTES, 'UTF-8') ?>
                                                <?php endif; ?>
                                                · <?= htmlspecialchars(date('d.m.Y H:i', strtotime((string)$entry['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                        <span class="badge badge-<?= $sc ?>"><?= htmlspecialchars(ucfirst((string)$entry['status']), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <?php if (!empty($entry['description'])): ?>
                                        <div class="scan-entry-desc"><?= htmlspecialchars((string)$entry['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['transaction_hash'])): ?>
                                        <div class="tx-hash" title="<?= htmlspecialchars((string)$entry['transaction_hash'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="anticon anticon-block mr-1"></i><?= htmlspecialchars((string)$entry['transaction_hash'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="scan-entry-footer">
                                        <?php if ($entry['fee_find_amount'] > 0): ?>
                                            <span class="fee-chip find"><i class="anticon anticon-search"></i> Suche: € <?= number_format((float)$entry['fee_find_amount'], 2, ',', '.') ?></span>
                                        <?php endif; ?>
                                        <?php if ($entry['fee_recover_amount'] > 0): ?>
                                            <span class="fee-chip recover"><i class="anticon anticon-rise"></i> Rückholung: € <?= number_format((float)$entry['fee_recover_amount'], 2, ',', '.') ?></span>
                                        <?php endif; ?>
                                        <span class="badge badge-<?= $riskColors[$entry['risk_level']] ?? 'secondary' ?>" style="font-size:10px;"><?= htmlspecialchars(ucfirst((string)$entry['risk_level']), ENT_QUOTES, 'UTF-8') ?> Risk</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Platform Checks with KYC ─────────────────────────────────── -->
        <div class="col-xl-4 mb-4">
            <div class="card ki-card">
                <div class="card-body" style="padding:20px;">
                    <div class="section-title">
                        <span class="scan-icon" style="background:rgba(16,185,129,.1);">
                            <i class="anticon anticon-security-scan" style="color:#10b981;font-size:16px;"></i>
                        </span>
                        Plattformprüfungen & KYC
                    </div>
                    <div class="section-subtitle">Geprüfte Plattformen mit KYC-Verifizierungsstatus und Falldetails.</div>

                    <?php if (empty($platformCheckEntries)): ?>
                        <div class="empty-ki">
                            <i class="anticon anticon-security-scan"></i>
                            <div>Noch keine Plattformprüfungen für Ihr Konto hinterlegt.</div>
                        </div>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <?php foreach ($platformCheckEntries as $i => $entry):
                                $sc = $statusColors[$entry['status']] ?? 'secondary';
                                $kyc = $entry['kyc_status'];
                                $kycChipClass = $kyc === 'verified' ? 'success' : ($kyc === 'pending' ? 'warning' : ($kyc === 'failed' ? 'danger' : 'secondary'));
                            ?>
                                <div class="scan-entry">
                                    <div class="scan-entry-header">
                                        <div>
                                            <div class="scan-entry-title"><?= htmlspecialchars((string)$entry['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if (!empty($entry['platform_name'])): ?>
                                                <div class="scan-entry-meta">
                                                    <i class="anticon anticon-global mr-1"></i>
                                                    <?php if (!empty($entry['platform_url'])): ?>
                                                        <a href="<?= htmlspecialchars((string)$entry['platform_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="text-muted">
                                                            <?= htmlspecialchars((string)$entry['platform_name'], ENT_QUOTES, 'UTF-8') ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars((string)$entry['platform_name'], ENT_QUOTES, 'UTF-8') ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge badge-<?= $sc ?>"><?= htmlspecialchars(ucfirst((string)$entry['status']), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <?php if (!empty($entry['description'])): ?>
                                        <div class="scan-entry-desc"><?= htmlspecialchars((string)$entry['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <div class="scan-entry-footer">
                                        <span class="badge badge-<?= $kycChipClass ?>">KYC: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$kyc)), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge badge-<?= $riskColors[$entry['risk_level']] ?? 'secondary' ?>" style="font-size:10px;"><?= htmlspecialchars(ucfirst((string)$entry['risk_level']), ENT_QUOTES, 'UTF-8') ?> Risk</span>
                                        <?php if ($entry['fee_find_amount'] > 0): ?>
                                            <span class="fee-chip find">Suche: € <?= number_format((float)$entry['fee_find_amount'], 2, ',', '.') ?></span>
                                        <?php endif; ?>
                                        <?php if ($entry['fee_recover_amount'] > 0): ?>
                                            <span class="fee-chip recover">Rückholung: € <?= number_format((float)$entry['fee_recover_amount'], 2, ',', '.') ?></span>
                                        <?php endif; ?>
                                        <span class="text-muted" style="font-size:11px;margin-left:auto;"><?= htmlspecialchars(date('d.m.Y', strtotime((string)$entry['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Reported Platforms ────────────────────────────────────────── -->
        <div class="col-xl-4 mb-4">
            <div class="card ki-card">
                <div class="card-body" style="padding:20px;">
                    <div class="section-title">
                        <span class="scan-icon" style="background:rgba(239,68,68,.1);">
                            <i class="anticon anticon-warning" style="color:#ef4444;font-size:16px;"></i>
                        </span>
                        Gemeldete Plattformen
                    </div>
                    <div class="section-subtitle">Als betrügerisch eingestufte Plattformen, die Ihren Fall betreffen.</div>

                    <?php if (empty($reportedPlatformEntries)): ?>
                        <div class="empty-ki">
                            <i class="anticon anticon-warning"></i>
                            <div>Noch keine gemeldeten Plattformen für Ihr Konto hinterlegt.</div>
                        </div>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <?php foreach ($reportedPlatformEntries as $entry):
                                $sc = $statusColors[$entry['status']] ?? 'secondary';
                                $rc = $riskColors[$entry['risk_level']] ?? 'secondary';
                            ?>
                                <div class="scan-entry" style="border-left:3px solid <?= $entry['risk_level'] === 'critical' ? '#ef4444' : ($entry['risk_level'] === 'high' ? '#f97316' : '#f59e0b') ?>;">
                                    <div class="scan-entry-header">
                                        <div>
                                            <div class="scan-entry-title"><?= htmlspecialchars((string)$entry['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if (!empty($entry['platform_name'])): ?>
                                                <div class="scan-entry-meta">
                                                    <?php if (!empty($entry['platform_url'])): ?>
                                                        <a href="<?= htmlspecialchars((string)$entry['platform_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="text-danger">
                                                            <i class="anticon anticon-link mr-1"></i><?= htmlspecialchars((string)$entry['platform_name'], ENT_QUOTES, 'UTF-8') ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <i class="anticon anticon-global mr-1"></i><?= htmlspecialchars((string)$entry['platform_name'], ENT_QUOTES, 'UTF-8') ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge badge-<?= $rc ?>"><?= htmlspecialchars(ucfirst((string)$entry['risk_level']), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <?php if (!empty($entry['description'])): ?>
                                        <div class="scan-entry-desc"><?= htmlspecialchars((string)$entry['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <div class="scan-entry-footer">
                                        <span class="badge badge-<?= $sc ?>"><?= htmlspecialchars(ucfirst((string)$entry['status']), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge badge-<?= $entry['kyc_status'] === 'verified' ? 'success' : 'warning' ?>">KYC: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$entry['kyc_status'])), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($entry['fee_find_amount'] > 0 || $entry['fee_recover_amount'] > 0): ?>
                                            <span class="fee-chip find">Suche: € <?= number_format((float)$entry['fee_find_amount'], 2, ',', '.') ?></span>
                                            <span class="fee-chip recover">Rückholung: € <?= number_format((float)$entry['fee_recover_amount'], 2, ',', '.') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost & Fee Breakdown -->
    <div class="row mb-4">
        <div class="col-xl-8 mb-4">
            <div class="card ki-card">
                <div class="card-body" style="padding:20px;">
                    <div class="section-title mb-1">
                        <i class="anticon anticon-dollar" style="color:#2563eb;"></i>
                        Kostenübersicht – Suche & Rückgewinnung
                    </div>
                    <div class="section-subtitle">Aufschlüsselung aller anfallenden Gebühren aus KI-Scans und Falldaten.</div>
                    <div class="cost-grid">
                        <div class="cost-box">
                            <div class="ki-label"><i class="anticon anticon-search mr-1 text-primary"></i>KI-Suchkosten gesamt</div>
                            <div class="cost-val">€ <?= number_format($kiStats['total_fee_find'], 2, ',', '.') ?></div>
                            <div class="ki-note">Kosten für KI-Transaktionssuche auf der Blockchain.</div>
                        </div>
                        <div class="cost-box">
                            <div class="ki-label"><i class="anticon anticon-rise mr-1 text-success"></i>Rückgewinnungskosten gesamt</div>
                            <div class="cost-val">€ <?= number_format($kiStats['total_fee_recover'], 2, ',', '.') ?></div>
                            <div class="ki-note">Kosten für die aktive Rückholung identifizierter Transaktionen.</div>
                        </div>
                        <div class="cost-box">
                            <div class="ki-label"><i class="anticon anticon-arrow-up mr-1 text-warning"></i>Offene Auszahlungsgebühren</div>
                            <div class="cost-val">€ <?= number_format((float)($feeSummary['pending_fee_amount'] ?? 0), 2, ',', '.') ?></div>
                            <div class="ki-note"><?= (int)($feeSummary['pending_fee_cases'] ?? 0) ?> Auszahlungsfall/-fälle noch offen.</div>
                        </div>
                        <div class="cost-box">
                            <div class="ki-label"><i class="anticon anticon-check-circle mr-1 text-info"></i>Bereits rückgewonnen</div>
                            <div class="cost-val">€ <?= number_format((float)($caseSummary['total_recovered'] ?? 0), 2, ',', '.') ?></div>
                            <div class="ki-note">Summe der bestätigten Recovery-Buchungen aus Ihren Fällen.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick links -->
        <div class="col-xl-4 mb-4">
            <div class="card ki-card">
                <div class="card-body" style="padding:20px;">
                    <div class="section-title mb-1">
                        <i class="anticon anticon-compass" style="color:#7c3aed;"></i>
                        Weitere Ansichten
                    </div>
                    <div class="section-subtitle">Navigation zu verwandten Bereichen Ihres Dashboards.</div>
                    <div style="display:flex;flex-direction:column;gap:10px;margin-top:16px;">
                        <a href="history.php" class="btn btn-light btn-block text-left" style="border-radius:10px;">
                            <i class="anticon anticon-history mr-2 text-primary"></i>Transaktionsverlauf &amp; Kosten
                        </a>
                        <a href="cases.php" class="btn btn-light btn-block text-left" style="border-radius:10px;">
                            <i class="anticon anticon-folder-open mr-2 text-info"></i>Meine Fälle
                        </a>
                        <a href="payment-methods.php#satoshi-verification" class="btn btn-light btn-block text-left" style="border-radius:10px;">
                            <i class="anticon anticon-safety-certificate mr-2 text-success"></i>Zahlung &amp; Verifizierung
                        </a>
                        <a href="recovered_funds.php" class="btn btn-light btn-block text-left" style="border-radius:10px;">
                            <i class="anticon anticon-dollar mr-2 text-warning"></i>Rückgewonnene Mittel
                        </a>
                        <a href="index.php" class="btn btn-light btn-block text-left" style="border-radius:10px;">
                            <i class="anticon anticon-dashboard mr-2 text-secondary"></i>Hauptdashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
