<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/header.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

$stats = [
    'entries' => 0,
    'recovered_total' => 0.0,
    'fee_total' => 0.0,
    'movement_total' => 0.0,
];
$historyRows = [];

try {
    $statsStmt = $pdo->prepare("
        SELECT
            (SELECT COUNT(*) FROM case_status_history h INNER JOIN cases c ON c.id = h.case_id WHERE c.user_id = :user_id_a) +
            (SELECT COUNT(*) FROM case_recovery_transactions rt INNER JOIN cases c2 ON c2.id = rt.case_id WHERE c2.user_id = :user_id_b) +
            (SELECT COUNT(*) FROM deposits d WHERE d.user_id = :user_id_c) +
            (SELECT COUNT(*) FROM withdrawals w WHERE w.user_id = :user_id_d) AS entries,
            (SELECT COALESCE(SUM(rt.amount), 0) FROM case_recovery_transactions rt INNER JOIN cases c3 ON c3.id = rt.case_id WHERE c3.user_id = :user_id_e) AS recovered_total,
            (SELECT COALESCE(SUM(w2.fee_amount), 0) FROM withdrawals w2 WHERE w2.user_id = :user_id_f) AS fee_total,
            (SELECT COALESCE(SUM(d2.amount), 0) FROM deposits d2 WHERE d2.user_id = :user_id_g) +
            (SELECT COALESCE(SUM(w3.amount), 0) FROM withdrawals w3 WHERE w3.user_id = :user_id_h) AS movement_total
    ");
    $statsStmt->execute([
        ':user_id_a' => $userId,
        ':user_id_b' => $userId,
        ':user_id_c' => $userId,
        ':user_id_d' => $userId,
        ':user_id_e' => $userId,
        ':user_id_f' => $userId,
        ':user_id_g' => $userId,
        ':user_id_h' => $userId,
    ]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: $stats;

    $timelineStmt = $pdo->prepare("
        SELECT entry_type, reference_label, context_label, status_label, details, amount, fee_amount, created_at
        FROM (
            SELECT
                'Status' AS entry_type,
                c.case_number AS reference_label,
                COALESCE(p.name, 'Unbekannte Plattform') AS context_label,
                h.new_status AS status_label,
                COALESCE(NULLIF(h.notes, ''), 'Statusänderung protokolliert.') AS details,
                NULL AS amount,
                NULL AS fee_amount,
                h.created_at
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
                COALESCE(NULLIF(rt.notes, ''), 'Recovery-Buchung hinterlegt.') AS details,
                rt.amount AS amount,
                NULL AS fee_amount,
                rt.transaction_date AS created_at
            FROM case_recovery_transactions rt
            INNER JOIN cases c ON c.id = rt.case_id
            LEFT JOIN scam_platforms p ON p.id = c.platform_id
            WHERE c.user_id = :user_id_recovery

            UNION ALL

            SELECT
                'Einzahlung' AS entry_type,
                COALESCE(d.reference, CONCAT('DEP-', d.id)) AS reference_label,
                COALESCE(d.method_code, 'Zahlungsmethode') AS context_label,
                d.status AS status_label,
                COALESCE(NULLIF(d.admin_notes, ''), 'Einzahlungseintrag erstellt.') AS details,
                d.amount AS amount,
                NULL AS fee_amount,
                d.created_at
            FROM deposits d
            WHERE d.user_id = :user_id_deposit

            UNION ALL

            SELECT
                'Auszahlung' AS entry_type,
                COALESCE(w.reference, CONCAT('WDR-', w.id)) AS reference_label,
                COALESCE(w.method_code, 'Zahlungsmethode') AS context_label,
                w.status AS status_label,
                COALESCE(NULLIF(w.admin_notes, ''), 'Auszahlungseintrag erstellt.') AS details,
                w.amount AS amount,
                w.fee_amount AS fee_amount,
                w.created_at
            FROM withdrawals w
            WHERE w.user_id = :user_id_withdrawal
        ) timeline
        ORDER BY created_at DESC
        LIMIT 60
    ");
    $timelineStmt->execute([
        ':user_id_status' => $userId,
        ':user_id_recovery' => $userId,
        ':user_id_deposit' => $userId,
        ':user_id_withdrawal' => $userId,
    ]);
    $historyRows = $timelineStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('history.php: ' . $e->getMessage());
}

$statusMap = [
    'open' => 'Offen',
    'documents_required' => 'Dokumente erforderlich',
    'under_review' => 'In Prüfung',
    'refund_approved' => 'Rückerstattung genehmigt',
    'refund_rejected' => 'Abgelehnt',
    'closed' => 'Abgeschlossen',
    'pending' => 'Ausstehend',
    'processing' => 'In Bearbeitung',
    'approved' => 'Genehmigt',
    'completed' => 'Abgeschlossen',
    'failed' => 'Fehlgeschlagen',
    'rejected' => 'Abgelehnt',
    'recovery' => 'Recovery',
];
?>
<style>
.history-hero{background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 60%,#2563eb 100%);border-radius:18px;color:#fff;padding:28px;box-shadow:0 20px 45px rgba(15,23,42,.18)}
.history-card{border:0;border-radius:16px;box-shadow:0 12px 30px rgba(15,23,42,.06);height:100%}
.history-stat{font-size:30px;font-weight:700;color:#111827}
.history-label{font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b}
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="history-hero mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:20px;">
                <div>
                    <div class="history-label text-white-50">Historie</div>
                    <h2 class="text-white mb-2">Transaktions- & Kostenverlauf</h2>
                    <p class="mb-0" style="color:rgba(255,255,255,.86);max-width:760px;">
                        Alle relevanten Fall-, Zahlungs- und Recovery-Ereignisse an einem Ort – inklusive Summen für Bewegungen,
                        Gebühren und Rückgewinnungsbuchungen.
                    </p>
                </div>
                <div>
                    <a href="payment-methods.php#satoshi-verification" class="btn btn-light mr-2">Zahlung & Verifizierung</a>
                    <a href="index2.php" class="btn btn-outline-light">Analyse-Cockpit</a>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card history-card"><div class="card-body">
                    <div class="history-label">Ereignisse</div>
                    <div class="history-stat"><?= (int)($stats['entries'] ?? 0) ?></div>
                </div></div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card history-card"><div class="card-body">
                    <div class="history-label">Recovery gesamt</div>
                    <div class="history-stat">€ <?= number_format((float)($stats['recovered_total'] ?? 0), 2, ',', '.') ?></div>
                </div></div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card history-card"><div class="card-body">
                    <div class="history-label">Gebühren gesamt</div>
                    <div class="history-stat">€ <?= number_format((float)($stats['fee_total'] ?? 0), 2, ',', '.') ?></div>
                </div></div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card history-card"><div class="card-body">
                    <div class="history-label">Bewegungsvolumen</div>
                    <div class="history-stat">€ <?= number_format((float)($stats['movement_total'] ?? 0), 2, ',', '.') ?></div>
                </div></div>
            </div>
        </div>

        <div class="card history-card">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h4 class="mb-1">Chronologische Historie</h4>
                <p class="text-muted mb-0">Einträge aus Fallstatus, Recovery-Buchungen, Einzahlungen und Auszahlungen.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <?php if (empty($historyRows)): ?>
                    <div class="alert alert-light border mb-0">Noch keine Historieneinträge vorhanden.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Typ</th>
                                    <th>Referenz</th>
                                    <th>Kontext</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                    <th>Betrag</th>
                                    <th>Gebühr</th>
                                    <th>Datum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historyRows as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)$row['entry_type'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)$row['reference_label'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)$row['context_label'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($statusMap[$row['status_label']] ?? $row['status_label']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)$row['details'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= $row['amount'] !== null ? '€ ' . number_format((float)$row['amount'], 2, ',', '.') : '—' ?></td>
                                        <td><?= $row['fee_amount'] !== null && (float)$row['fee_amount'] > 0 ? '€ ' . number_format((float)$row['fee_amount'], 2, ',', '.') : '—' ?></td>
                                        <td><?= htmlspecialchars(date('d.m.Y H:i', strtotime((string)$row['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
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

<?php require_once __DIR__ . '/footer.php'; ?>
