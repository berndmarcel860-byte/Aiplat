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

    // ── Algorithm stats (deterministic seed per case) ──────────────────────────
    $seed = hexdec(substr(md5($case['case_number']), 0, 8));
    mt_srand($seed);
    $stats = [
        'txScanned'     => mt_rand(12000, 450000),
        'walletsLinked' => mt_rand(3, 41),
        'exchanges'     => mt_rand(1, 8),
        'hops'          => mt_rand(4, 18),
        'matchScore'    => mt_rand(72, 99),
    ];
    mt_srand();

    // ── Legal milestones – Step 1 = created_at, steps progress forward ─────────
    $seed2   = hexdec(substr(md5('legal_' . $case['case_number']), 0, 8));
    mt_srand($seed2);
    $created = strtotime($case['created_at']);
    $now     = time();

    // Each step is a fixed number of days AFTER case creation, capped at today.
    $cap = function(int $ts) use ($now): int {
        return min($ts, $now);
    };

    $milestones = [];

    // Step 1 – Fallaufnahme (= case created date)
    $milestones[] = [
        'date'  => date('d.m.Y', $created),
        'icon'  => 'anticon-file-text',
        'color' => '#1890ff',
        'title' => 'Fallaufnahme & Dokumentenprüfung',
        'text'  => 'Rechtsabteilung hat alle eingereichten Unterlagen geprüft und den Sachverhalt aufgenommen.',
    ];

    // Step 2 – Forderungsschreiben (7–14 days after creation)
    $step2 = $cap($created + mt_rand(7, 14) * 86400);
    $milestones[] = [
        'date'  => date('d.m.Y', $step2),
        'icon'  => 'anticon-mail',
        'color' => '#fa8c16',
        'title' => 'Forderungsschreiben versandt',
        'text'  => 'Offizielles Forderungsschreiben mit Belegen wurde an die betroffene Plattform / Gegenpartei übermittelt.',
    ];

    // Step 3 – Regulatorische Eskalation (20–35 days after creation)
    $step3 = $cap($created + mt_rand(20, 35) * 86400);
    $milestones[] = [
        'date'  => date('d.m.Y', $step3),
        'icon'  => 'anticon-bank',
        'color' => '#52c41a',
        'title' => 'Regulatorische Eskalation',
        'text'  => 'Fall wurde bei der zuständigen Aufsichtsbehörde (FCA / BaFin) zur weiteren Untersuchung eingereicht.',
    ];

    // Step 4 – Rückerstattung bestätigt / Laufende Verhandlungen (45–90 days)
    $step4 = $cap($created + mt_rand(45, 90) * 86400);
    $recovered = (float)$case['recovered_amount'];
    if ($recovered > 0) {
        $milestones[] = [
            'date'  => date('d.m.Y', $step4),
            'icon'  => 'anticon-check-circle',
            'color' => '#722ed1',
            'title' => 'Rückerstattung bestätigt',
            'text'  => 'Die Gegenpartei hat der Rückerstattung des Betrags von '
                      . number_format($recovered, 2, ',', '.') . ' € zugestimmt.',
        ];
    } else {
        $milestones[] = [
            'date'  => date('d.m.Y', $step4),
            'icon'  => 'anticon-clock-circle',
            'color' => '#faad14',
            'title' => 'Laufende Verhandlungen',
            'text'  => 'Aktive Korrespondenz mit der Gegenpartei. Rechtsabteilung erwartet Rückmeldung innerhalb von 14 Tagen.',
        ];
    }
    mt_srand();

    // ── Status label ───────────────────────────────────────────────────────────
    $statusLabels = [
        'open'               => ['label' => 'Offen',                    'badge' => 'badge-info'],
        'documents_required' => ['label' => 'Dokumente ausstehend',     'badge' => 'badge-warning'],
        'under_review'       => ['label' => 'In Bearbeitung',           'badge' => 'badge-primary'],
        'refund_approved'    => ['label' => 'Rückerstattung genehmigt', 'badge' => 'badge-success'],
        'refund_rejected'    => ['label' => 'Abgelehnt',                'badge' => 'badge-danger'],
        'closed'             => ['label' => 'Geschlossen',              'badge' => 'badge-secondary'],
    ];
    $reported = (float)$case['reported_amount'];
    $pct      = ($reported > 0) ? min(100, round($recovered / $reported * 100, 1)) : 0;
    $sl       = $statusLabels[$case['status']] ?? ['label' => ucfirst($case['status']), 'badge' => 'badge-secondary'];

    echo json_encode([
        'id'            => (int)$case['id'],
        'case_number'   => $case['case_number'],
        'platform'      => $case['platform_name'] ?? 'Unbekannte Plattform',
        'status_label'  => $sl['label'],
        'status_badge'  => $sl['badge'],
        'reported'      => number_format($reported, 2, ',', '.'),
        'recovered'     => number_format($recovered, 2, ',', '.'),
        'recovered_raw' => $recovered,
        'pct'           => $pct,
        'updated_at'    => date('d.m.Y', strtotime($case['updated_at'] ?? $case['created_at'])),
        'stats'         => $stats,
        'milestones'    => $milestones,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

} catch (PDOException $e) {
    error_log('get_case_modal_data.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler']);
}
