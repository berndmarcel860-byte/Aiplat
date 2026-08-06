<?php
/**
 * AJAX endpoint – returns KI stats + legal milestones for a single case.
 * Called lazily when the user opens the detail modal in recovered_funds.php.
 */
require_once __DIR__ . '/../session.php';
header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Nicht autorisiert']);
        exit;
    }

    $caseId = filter_input(INPUT_GET, 'case_id', FILTER_VALIDATE_INT);
    if (!$caseId) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültige Fall-ID']);
        exit;
    }

    $userId = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT c.*, p.name AS platform_name
        FROM cases c
        LEFT JOIN scam_platforms p ON p.id = c.platform_id
        WHERE c.id = ? AND c.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$caseId, $userId]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$case) {
        http_response_code(404);
        echo json_encode(['error' => 'Fall nicht gefunden']);
        exit;
    }

    // ── Algorithm stats – derived from real case attributes ────────────────────
    // Scale log-linearly from €1 000 → min values  to  €2 000 000 → max values.
    // A small per-case deterministic jitter keeps each case unique.
    $amount    = max(1000.0, (float)$case['reported_amount']);
    $ratio     = min(1.0, log10($amount / 1000.0) / log10(2000.0)); // log10(2 000 000/1 000)
    $recovProg = max(0, min(100, (int)($case['recovery_progress'] ?? 0)));

    $jSeed = hexdec(substr(md5($case['case_number']), 0, 8));
    mt_srand($jSeed);
    $jT = mt_rand(-8000, 8000);
    $jW = mt_rand(-2,    2);
    $jE = mt_rand(-1,    1);
    $jH = mt_rand(-2,    2);
    $jS = mt_rand(-3,    3);
    mt_srand();

    $stats = [
        'txScanned'     => max(12000, (int)round(12000 + $ratio * 428000) + $jT),
        'walletsLinked' => max(3,     (int)round(3     + $ratio * 36)     + $jW),
        'exchanges'     => max(1,     (int)round(1     + $ratio * 6)      + $jE),
        'hops'          => max(4,     (int)round(4     + $ratio * 13)     + $jH),
        'matchScore'    => min(99, max(72,
                            (int)round(72 + $ratio * 22 + $recovProg * 0.05) + $jS)),
    ];

    // ── Legal milestones – Step 1 = created_at, steps progress forward ─────────
    $seed2   = hexdec(substr(md5('legal_' . $case['case_number']), 0, 8));
    mt_srand($seed2);
    $created = strtotime($case['created_at']);
    $now     = time();

    // Each step is a fixed number of days AFTER case creation, capped at today.
    $cap = function(int $ts) use ($now): int {
        return min($ts, $now);
    };

    // ── Per-case milestone visibility (admin-controlled) ─────────────────────
    $visStmt = $pdo->prepare(
        "SELECT step2, step3, step4 FROM case_milestone_visibility WHERE case_id = ? LIMIT 1"
    );
    $visStmt->execute([$caseId]);
    $vis = $visStmt->fetch(PDO::FETCH_ASSOC);
    $showStep2 = $vis ? (bool)$vis['step2'] : false;
    $showStep3 = $vis ? (bool)$vis['step3'] : false;
    $showStep4 = $vis ? (bool)$vis['step4'] : false;

    $milestones = [];

    // Step 1 – Fallaufnahme (= case created date, always visible)
    $milestones[] = [
        'date'  => date('d.m.Y', $created),
        'icon'  => 'anticon-file-text',
        'color' => '#1890ff',
        'title' => 'Fallaufnahme & Dokumentenprüfung',
        'text'  => 'Rechtsabteilung hat alle eingereichten Unterlagen geprüft und den Sachverhalt aufgenommen.',
    ];

    // Step 2 – Forderungsschreiben (7–14 days after creation, hidden until admin enables)
    $step2Timestamp = $cap($created + mt_rand(7, 14) * 86400);
    if ($showStep2) {
        $milestones[] = [
            'date'  => date('d.m.Y', $step2Timestamp),
            'icon'  => 'anticon-mail',
            'color' => '#fa8c16',
            'title' => 'Forderungsschreiben versandt',
            'text'  => 'Offizielles Forderungsschreiben mit Belegen wurde an die betroffene Plattform / Gegenpartei übermittelt.',
        ];
    }

    // Step 3 – Regulatorische Eskalation (20–35 days after creation, hidden until admin enables)
    $step3Timestamp = $cap($created + mt_rand(20, 35) * 86400);
    if ($showStep3) {
        $milestones[] = [
            'date'  => date('d.m.Y', $step3Timestamp),
            'icon'  => 'anticon-bank',
            'color' => '#52c41a',
            'title' => 'Regulatorische Eskalation',
            'text'  => 'Fall wurde bei der zuständigen Aufsichtsbehörde (FCA / BaFin) zur weiteren Untersuchung eingereicht.',
        ];
    }

    // Step 4 – Rückerstattung bestätigt / Laufende Verhandlungen (45–90 days, hidden until admin enables)
    $step4Timestamp = $cap($created + mt_rand(45, 90) * 86400);
    $recovered = (float)$case['recovered_amount'];
    if ($showStep4) {
        if ($recovered > 0) {
            $milestones[] = [
                'date'  => date('d.m.Y', $step4Timestamp),
                'icon'  => 'anticon-check-circle',
                'color' => '#722ed1',
                'title' => 'Rückerstattung bestätigt',
                'text'  => 'Die Gegenpartei hat der Rückerstattung des Betrags von '
                          . number_format($recovered, 2, ',', '.') . ' € zugestimmt.',
            ];
        } else {
            $milestones[] = [
                'date'  => date('d.m.Y', $step4Timestamp),
                'icon'  => 'anticon-clock-circle',
                'color' => '#faad14',
                'title' => 'Laufende Verhandlungen',
                'text'  => 'Aktive Korrespondenz mit der Gegenpartei. Rechtsabteilung erwartet Rückmeldung innerhalb von 14 Tagen.',
            ];
        }
    }
    mt_srand();

    // ── Real recovery transactions ──────────────────────────────────────────────
    $rtStmt = $pdo->prepare("
        SELECT amount, transaction_date, notes
        FROM case_recovery_transactions
        WHERE case_id = ?
        ORDER BY transaction_date DESC
        LIMIT 10
    ");
    $rtStmt->execute([$caseId]);
    $recoveryTransactions = array_map(function ($t) {
        return [
            'amount' => number_format((float)$t['amount'], 2, ',', '.'),
            'date'   => date('d.m.Y', strtotime($t['transaction_date'])),
            'notes'  => $t['notes'] ?? '',
        ];
    }, $rtStmt->fetchAll(PDO::FETCH_ASSOC));

    // ── Status history (last 5, newest-first) ────────────────────────────────────
    $shStmt = $pdo->prepare("
        SELECT new_status, notes, created_at
        FROM case_status_history
        WHERE case_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $shStmt->execute([$caseId]);

    // ── Status label (needed for both status history and main badge) ────────────
    $statusLabels = [
        'open'               => ['label' => 'Offen',                    'badge' => 'badge-info'],
        'documents_required' => ['label' => 'Dokumente ausstehend',     'badge' => 'badge-warning'],
        'under_review'       => ['label' => 'In Bearbeitung',           'badge' => 'badge-primary'],
        'refund_approved'    => ['label' => 'Rückerstattung genehmigt', 'badge' => 'badge-success'],
        'refund_rejected'    => ['label' => 'Abgelehnt',                'badge' => 'badge-danger'],
        'closed'             => ['label' => 'Geschlossen',              'badge' => 'badge-secondary'],
    ];

    $statusHistory = array_map(function ($h) use ($statusLabels) {
        $sl = $statusLabels[$h['new_status']]
            ?? ['label' => ucfirst($h['new_status']), 'badge' => 'badge-secondary'];
        return [
            'status_label' => $sl['label'],
            'status_badge' => $sl['badge'],
            'notes'        => $h['notes'] ?? '',
            'date'         => date('d.m.Y', strtotime($h['created_at'])),
        ];
    }, $shStmt->fetchAll(PDO::FETCH_ASSOC));
    $reported = (float)$case['reported_amount'];
    $pct      = ($reported > 0) ? min(100, round($recovered / $reported * 100, 1)) : 0;
    $sl       = $statusLabels[$case['status']] ?? ['label' => ucfirst($case['status']), 'badge' => 'badge-secondary'];

    echo json_encode([
        'id'                   => (int)$case['id'],
        'case_number'          => $case['case_number'],
        'platform'             => $case['platform_name'] ?? 'Unbekannte Plattform',
        'status_label'         => $sl['label'],
        'status_badge'         => $sl['badge'],
        'reported'             => number_format($reported, 2, ',', '.'),
        'recovered'            => number_format($recovered, 2, ',', '.'),
        'recovered_raw'        => $recovered,
        'pct'                  => $pct,
        'created_at'           => date('d.m.Y', $created),
        'updated_at'           => date('d.m.Y', strtotime($case['updated_at'] ?? $case['created_at'])),
        'recovery_stage'       => $case['recovery_stage']       ?? 'initial',
        'recovery_progress'    => $recovProg,
        'refund_difficulty'    => $case['refund_difficulty']     ?? 'medium',
        'stats'                => $stats,
        'milestones'           => $milestones,
        'recovery_transactions'=> $recoveryTransactions,
        'status_history'       => $statusHistory,
        'milestone_visibility' => [
            'step2' => $showStep2,
            'step3' => $showStep3,
            'step4' => $showStep4,
        ],
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

} catch (PDOException $e) {
    error_log('get_case_modal_data.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler']);
}
