<?php
/**
 * History – Comprehensive transaction, cost & recovery timeline.
 * Shows: total transactions, cost breakdown, recovery stats, KI scan entries,
 * chronological event log with filter & export.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/header.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

// ── Stats ──────────────────────────────────────────────────────────────────
$stats = [
    'entries'            => 0,
    'deposit_count'      => 0,
    'withdrawal_count'   => 0,
    'recovery_count'     => 0,
    'status_count'       => 0,
    'deposit_total'      => 0.0,
    'withdrawal_total'   => 0.0,
    'recovered_total'    => 0.0,
    'fee_total'          => 0.0,
    'ki_find_fee'        => 0.0,
    'ki_recover_fee'     => 0.0,
    'ki_entries'         => 0,
];
$historyRows   = [];
$depositsByMonth = [];

// ── Active filter ─────────────────────────────────────────────────────────
$filterType = in_array($_GET['type'] ?? '', ['Status','Recovery','Einzahlung','Auszahlung','KI-Scan']) ? $_GET['type'] : '';

try {
    // Individual counts
    $cntStmt = $pdo->prepare("
        SELECT
            (SELECT COUNT(*) FROM case_status_history h INNER JOIN cases c ON c.id = h.case_id WHERE c.user_id = :a)     AS status_count,
            (SELECT COUNT(*) FROM case_recovery_transactions rt INNER JOIN cases c2 ON c2.id = rt.case_id WHERE c2.user_id = :b) AS recovery_count,
            (SELECT COUNT(*) FROM deposits d WHERE d.user_id = :c)    AS deposit_count,
            (SELECT COUNT(*) FROM withdrawals w WHERE w.user_id = :d) AS withdrawal_count,
            (SELECT COALESCE(SUM(rt2.amount),0) FROM case_recovery_transactions rt2 INNER JOIN cases c3 ON c3.id = rt2.case_id WHERE c3.user_id = :e) AS recovered_total,
            (SELECT COALESCE(SUM(w2.fee_amount),0) FROM withdrawals w2 WHERE w2.user_id = :f) AS fee_total,
            (SELECT COALESCE(SUM(d2.amount),0) FROM deposits d2 WHERE d2.user_id = :g)  AS deposit_total,
            (SELECT COALESCE(SUM(w3.amount),0) FROM withdrawals w3 WHERE w3.user_id = :h) AS withdrawal_total
    ");
    $cntStmt->execute([':a'=>$userId,':b'=>$userId,':c'=>$userId,':d'=>$userId,':e'=>$userId,':f'=>$userId,':g'=>$userId,':h'=>$userId]);
    $row = $cntStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    foreach ($row as $k => $v) {
        if (isset($stats[$k])) $stats[$k] = (strpos($k, 'count') !== false) ? (int)$v : (float)$v;
    }
    $stats['entries'] = $stats['status_count'] + $stats['recovery_count'] + $stats['deposit_count'] + $stats['withdrawal_count'];

    // KI scan fee totals
    try {
        $kiStmt = $pdo->prepare("SELECT COUNT(*) AS ki_entries, COALESCE(SUM(fee_find_amount),0) AS ki_find_fee, COALESCE(SUM(fee_recover_amount),0) AS ki_recover_fee FROM ki_scan_entries WHERE user_id = ? AND is_visible = 1");
        $kiStmt->execute([$userId]);
        $kiRow = $kiStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stats['ki_entries']     = (int)($kiRow['ki_entries'] ?? 0);
        $stats['ki_find_fee']    = (float)($kiRow['ki_find_fee'] ?? 0);
        $stats['ki_recover_fee'] = (float)($kiRow['ki_recover_fee'] ?? 0);
        $stats['entries'] += $stats['ki_entries'];
    } catch (Throwable $e) { /* table may not exist */ }

    // Timeline (with optional type filter)
    $typeFilter = '';
    $timelineParams = [
        ':user_id_status'     => $userId,
        ':user_id_recovery'   => $userId,
        ':user_id_deposit'    => $userId,
        ':user_id_withdrawal' => $userId,
    ];
    // Build combined timeline query
    $timelineStmt = $pdo->prepare("
        SELECT entry_type, reference_label, context_label, status_label, details, amount, fee_amount, created_at
        FROM (
            SELECT
                'Status' AS entry_type,
                c.case_number AS reference_label,
                COALESCE(p.name, 'Unbekannte Plattform') AS context_label,
                h.new_status AS status_label,
                COALESCE(NULLIF(h.notes,''), 'Statusänderung protokolliert.') AS details,
                NULL AS amount, NULL AS fee_amount, h.created_at
            FROM case_status_history h
            INNER JOIN cases c ON c.id = h.case_id
            LEFT JOIN scam_platforms p ON p.id = c.platform_id
            WHERE c.user_id = :user_id_status

            UNION ALL

            SELECT
                'Recovery' AS entry_type,
                c.case_number AS reference_label,
                COALESCE(p.name, 'Unbekannte Plattform') AS context_label,
                'recovery' AS status_label,
                COALESCE(NULLIF(rt.notes,''), 'Recovery-Buchung hinterlegt.') AS details,
                rt.amount AS amount, NULL AS fee_amount, rt.transaction_date AS created_at
            FROM case_recovery_transactions rt
            INNER JOIN cases c ON c.id = rt.case_id
            LEFT JOIN scam_platforms p ON p.id = c.platform_id
            WHERE c.user_id = :user_id_recovery

            UNION ALL

            SELECT
                'Einzahlung' AS entry_type,
                COALESCE(d.reference, CONCAT('DEP-', d.id)) AS reference_label,
                COALESCE(d.method_code,'Zahlungsmethode') AS context_label,
                d.status AS status_label,
                COALESCE(NULLIF(d.admin_notes,''), 'Einzahlungseintrag erstellt.') AS details,
                d.amount AS amount, NULL AS fee_amount, d.created_at
            FROM deposits d
            WHERE d.user_id = :user_id_deposit

            UNION ALL

            SELECT
                'Auszahlung' AS entry_type,
                COALESCE(w.reference, CONCAT('WDR-', w.id)) AS reference_label,
                COALESCE(w.method_code,'Zahlungsmethode') AS context_label,
                w.status AS status_label,
                COALESCE(NULLIF(w.admin_notes,''), 'Auszahlungseintrag erstellt.') AS details,
                w.amount AS amount, w.fee_amount AS fee_amount, w.created_at
            FROM withdrawals w
            WHERE w.user_id = :user_id_withdrawal
        ) timeline
        ORDER BY created_at DESC
        LIMIT 100
    ");
    $timelineStmt->execute($timelineParams);
    $allRows = $timelineStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // KI scan entries in timeline
    try {
        $kiTimelineStmt = $pdo->prepare("SELECT id, entry_type, title, description, status, fee_find_amount, fee_recover_amount, platform_name, blockchain_network, transaction_hash, risk_level, created_at FROM ki_scan_entries WHERE user_id = ? AND is_visible = 1 ORDER BY created_at DESC LIMIT 30");
        $kiTimelineStmt->execute([$userId]);
        $kiTimelineRows = $kiTimelineStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($kiTimelineRows as $k) {
            $totalFee = (float)$k['fee_find_amount'] + (float)$k['fee_recover_amount'];
            $allRows[] = [
                'entry_type'      => 'KI-Scan',
                'reference_label' => $k['title'],
                'context_label'   => $k['platform_name'] ?: ($k['blockchain_network'] ?: 'AI Scan'),
                'status_label'    => $k['status'],
                'details'         => $k['description'] ?: ('KI-Scan: ' . ucfirst($k['entry_type'])),
                'amount'          => $totalFee > 0 ? $totalFee : null,
                'fee_amount'      => (float)$k['fee_find_amount'] > 0 ? $k['fee_find_amount'] : null,
                'created_at'      => $k['created_at'],
            ];
        }
    } catch (Throwable $e) { /* table may not exist */ }

    // Sort merged rows by created_at desc
    usort($allRows, function($a, $b) {
        return strtotime((string)$b['created_at']) - strtotime((string)$a['created_at']);
    });

    // Apply type filter
    if ($filterType !== '') {
        $historyRows = array_values(array_filter($allRows, function($r) use ($filterType) {
            return $r['entry_type'] === $filterType;
        }));
    } else {
        $historyRows = array_slice($allRows, 0, 100);
    }

} catch (Throwable $e) {
    error_log('history.php: ' . $e->getMessage());
}

$totalCosts = $stats['fee_total'] + $stats['ki_find_fee'] + $stats['ki_recover_fee'];
$recoveryRate = ($stats['withdrawal_total'] > 0 && $stats['recovered_total'] > 0)
    ? min(100, round(($stats['recovered_total'] / max($stats['withdrawal_total'], 1)) * 100, 1))
    : 0;

$statusMap = [
    'open'                 => 'Offen',
    'documents_required'   => 'Dokumente erforderlich',
    'under_review'         => 'In Prüfung',
    'refund_approved'      => 'Rückerstattung genehmigt',
    'refund_rejected'      => 'Abgelehnt',
    'closed'               => 'Abgeschlossen',
    'pending'              => 'Ausstehend',
    'processing'           => 'In Bearbeitung',
    'approved'             => 'Genehmigt',
    'completed'            => 'Abgeschlossen',
    'failed'               => 'Fehlgeschlagen',
    'rejected'             => 'Abgelehnt',
    'recovery'             => 'Recovery',
    'scanning'             => 'Scanning',
    'found'                => 'Gefunden',
    'not_found'            => 'Nicht gefunden',
    'verified'             => 'Verifiziert',
    'flagged'              => 'Markiert',
    'resolved'             => 'Gelöst',
];

$typeColors = [
    'Status'     => 'primary',
    'Recovery'   => 'success',
    'Einzahlung' => 'info',
    'Auszahlung' => 'warning',
    'KI-Scan'    => 'dark',
];
$typeIcons = [
    'Status'     => 'anticon-file-protect',
    'Recovery'   => 'anticon-rise',
    'Einzahlung' => 'anticon-arrow-down',
    'Auszahlung' => 'anticon-arrow-up',
    'KI-Scan'    => 'anticon-robot',
];
?>
<style>
.hist-hero{background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 60%,#2563eb 100%);border-radius:18px;color:#fff;padding:28px;box-shadow:0 20px 45px rgba(15,23,42,.18)}
.hist-card{border:0;border-radius:16px;box-shadow:0 8px 24px rgba(15,23,42,.06);height:100%}
.hist-stat{font-size:28px;font-weight:700;color:#0f172a;line-height:1}
.hist-label{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b;margin-bottom:8px}
.hist-note{font-size:13px;color:#64748b;line-height:1.5;margin-top:5px}
.cost-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-top:16px}
.cost-box{border-radius:14px;background:#f8fafc;border:1px solid #e5e7eb;padding:16px 18px}
.cost-val{font-size:22px;font-weight:700;color:#0f172a;line-height:1;margin:6px 0 4px}
.timeline-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}
.filter-bar{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}
.filter-btn{padding:5px 14px;border-radius:999px;font-size:12px;font-weight:600;border:1.5px solid #e2e8f0;background:#fff;color:#4a5568;cursor:pointer;transition:all .15s}
.filter-btn:hover,.filter-btn.active{border-color:#2563eb;background:#2563eb;color:#fff}
</style>

<div class="main-content">
<div class="container-fluid">

    <!-- Hero -->
    <div class="hist-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:20px;">
            <div>
                <div class="hist-label text-white-50">VERLAUFS-ARCHIV</div>
                <h2 class="text-white mb-2">Transaktions- & Kostenverlauf</h2>
                <p class="mb-0" style="color:rgba(255,255,255,.86);max-width:700px;font-size:15px;line-height:1.7;">
                    Chronologischer Überblick aller Fall-, Zahlungs- und KI-Scan-Ereignisse –
                    inklusive vollständiger Kostenaufschlüsselung für Suche, Auszahlungsgebühren und Rückgewinnungskosten.
                </p>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="index2.php" class="btn btn-outline-light btn-sm">KI Dashboard</a>
                <a href="payment-methods.php#satoshi-verification" class="btn btn-light btn-sm">Zahlung &amp; Verifizierung</a>
            </div>
        </div>
    </div>

    <!-- Stats row: 8 metrics -->
    <div class="row mb-4">
        <div class="col-6 col-md-3 mb-3"><div class="card hist-card"><div class="card-body">
            <div class="hist-label">Gesamtereignisse</div>
            <div class="hist-stat"><?= number_format((int)$stats['entries']) ?></div>
            <div class="hist-note">Alle Status-, Zahlungs-, Recovery- und KI-Einträge.</div>
        </div></div></div>

        <div class="col-6 col-md-3 mb-3"><div class="card hist-card"><div class="card-body">
            <div class="hist-label">Einzahlungen</div>
            <div class="hist-stat"><?= (int)$stats['deposit_count'] ?></div>
            <div class="hist-note">Total: € <?= number_format($stats['deposit_total'], 2, ',', '.') ?></div>
        </div></div></div>

        <div class="col-6 col-md-3 mb-3"><div class="card hist-card"><div class="card-body">
            <div class="hist-label">Auszahlungen</div>
            <div class="hist-stat"><?= (int)$stats['withdrawal_count'] ?></div>
            <div class="hist-note">Total: € <?= number_format($stats['withdrawal_total'], 2, ',', '.') ?></div>
        </div></div></div>

        <div class="col-6 col-md-3 mb-3"><div class="card hist-card"><div class="card-body">
            <div class="hist-label">Recovery-Buchungen</div>
            <div class="hist-stat"><?= (int)$stats['recovery_count'] ?></div>
            <div class="hist-note">Rückgewonnen: € <?= number_format($stats['recovered_total'], 2, ',', '.') ?></div>
        </div></div></div>
    </div>

    <!-- Cost Breakdown -->
    <div class="card hist-card mb-4">
        <div class="card-body">
            <h5 class="mb-1" style="font-weight:700;"><i class="anticon anticon-dollar mr-2 text-primary"></i>Vollständige Kostenaufschlüsselung</h5>
            <p class="text-muted mb-0" style="font-size:13px;">Alle anfallenden Kosten aus Auszahlungsgebühren, KI-Suchläufen und Rückgewinnungsoperationen.</p>
            <div class="cost-grid">
                <div class="cost-box">
                    <div class="hist-label">Auszahlungsgebühren</div>
                    <div class="cost-val">€ <?= number_format($stats['fee_total'], 2, ',', '.') ?></div>
                    <div class="hist-note">Gebühren auf abgeschlossene Auszahlungsanträge.</div>
                </div>
                <div class="cost-box">
                    <div class="hist-label">KI-Suchkosten</div>
                    <div class="cost-val">€ <?= number_format($stats['ki_find_fee'], 2, ',', '.') ?></div>
                    <div class="hist-note">Kosten für KI-Transaktionssuche auf der Blockchain.</div>
                </div>
                <div class="cost-box">
                    <div class="hist-label">Rückgewinnungskosten</div>
                    <div class="cost-val">€ <?= number_format($stats['ki_recover_fee'], 2, ',', '.') ?></div>
                    <div class="hist-note">Kosten für aktive Rückholung identifizierter Transaktionen.</div>
                </div>
                <div class="cost-box" style="border-color:#2563eb;background:#eff6ff;">
                    <div class="hist-label" style="color:#1d4ed8;">Gesamtkosten</div>
                    <div class="cost-val" style="color:#1d4ed8;">€ <?= number_format($totalCosts, 2, ',', '.') ?></div>
                    <div class="hist-note" style="color:#3b82f6;">Summe aller im System erfassten Kosten.</div>
                </div>
                <div class="cost-box">
                    <div class="hist-label">Rückgewonnen</div>
                    <div class="cost-val text-success">€ <?= number_format($stats['recovered_total'], 2, ',', '.') ?></div>
                    <div class="hist-note">Bestätigte Recovery-Buchungen aus allen Fällen.</div>
                </div>
                <div class="cost-box">
                    <div class="hist-label">KI-Scan-Einträge</div>
                    <div class="cost-val"><?= (int)$stats['ki_entries'] ?></div>
                    <div class="hist-note">Vom Team eingetragene AI-Scans und Prüfungen.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <div class="card hist-card">
        <div class="card-body">
            <h5 class="mb-1" style="font-weight:700;"><i class="anticon anticon-history mr-2 text-primary"></i>Chronologische Ereignishistorie</h5>
            <p class="text-muted mb-3" style="font-size:13px;">Fallstatus, Recovery-Buchungen, Einzahlungen, Auszahlungen und KI-Scan-Einträge in zeitlicher Reihenfolge.</p>

            <!-- Filter buttons -->
            <div class="filter-bar">
                <a href="history.php" class="filter-btn <?= $filterType === '' ? 'active' : '' ?>">Alle</a>
                <a href="?type=Einzahlung" class="filter-btn <?= $filterType === 'Einzahlung' ? 'active' : '' ?>"><i class="anticon anticon-arrow-down"></i> Einzahlungen</a>
                <a href="?type=Auszahlung" class="filter-btn <?= $filterType === 'Auszahlung' ? 'active' : '' ?>"><i class="anticon anticon-arrow-up"></i> Auszahlungen</a>
                <a href="?type=Recovery" class="filter-btn <?= $filterType === 'Recovery' ? 'active' : '' ?>"><i class="anticon anticon-rise"></i> Recovery</a>
                <a href="?type=Status" class="filter-btn <?= $filterType === 'Status' ? 'active' : '' ?>"><i class="anticon anticon-file-protect"></i> Fallstatus</a>
                <a href="?type=KI-Scan" class="filter-btn <?= $filterType === 'KI-Scan' ? 'active' : '' ?>"><i class="anticon anticon-robot"></i> KI-Scans</a>
            </div>

            <?php if (empty($historyRows)): ?>
                <div class="alert alert-light border mb-0">Keine Historieneinträge für diesen Filter vorhanden.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:13px;">
                        <thead class="bg-light">
                            <tr>
                                <th style="white-space:nowrap;">Typ</th>
                                <th>Referenz</th>
                                <th>Kontext</th>
                                <th>Status</th>
                                <th>Details</th>
                                <th>Betrag</th>
                                <th>Gebühr</th>
                                <th style="white-space:nowrap;">Datum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historyRows as $row):
                                $tc = $typeColors[$row['entry_type']] ?? 'secondary';
                                $ti = $typeIcons[$row['entry_type']] ?? 'anticon-info-circle';
                            ?>
                                <tr>
                                    <td>
                                        <span class="timeline-badge badge-<?= $tc ?>" style="background:var(--badge-<?= $tc ?>,#6b7280);color:#fff;">
                                            <i class="<?= $ti ?>"></i>
                                            <?= htmlspecialchars((string)$row['entry_type'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="font-weight-600"><?= htmlspecialchars((string)$row['reference_label'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$row['context_label'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($statusMap[$row['status_label']] ?? ucfirst((string)$row['status_label'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars((string)$row['details'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string)$row['details'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td><?= $row['amount'] !== null ? '<strong>€ ' . number_format((float)$row['amount'], 2, ',', '.') . '</strong>' : '—' ?></td>
                                    <td><?= $row['fee_amount'] !== null && (float)$row['fee_amount'] > 0 ? '€ ' . number_format((float)$row['fee_amount'], 2, ',', '.') : '—' ?></td>
                                    <td style="white-space:nowrap;"><?= htmlspecialchars(date('d.m.Y H:i', strtotime((string)$row['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

<style>
/* Badge color vars for timeline */
.badge-primary{background:#2563eb;color:#fff}
.badge-success{background:#10b981;color:#fff}
.badge-info{background:#0ea5e9;color:#fff}
.badge-warning{background:#f59e0b;color:#fff}
.badge-dark{background:#1f2937;color:#fff}
.badge-secondary{background:#6b7280;color:#fff}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
