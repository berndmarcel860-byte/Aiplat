<?php
/**
 * Admin AJAX – save per-case legal milestone visibility flags.
 * POST body (JSON):
 *   { case_id: int, step2: 0|1, step3: 0|1, step4: 0|1 }
 */
require_once '../admin_session.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$caseId = isset($data['case_id']) ? (int)$data['case_id'] : 0;
if ($caseId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Fall-ID']);
    exit;
}

$step2 = empty($data['step2']) ? 0 : 1;
$step3 = empty($data['step3']) ? 0 : 1;
$step4 = empty($data['step4']) ? 0 : 1;

try {
    $stmt = $pdo->prepare("
        INSERT INTO case_milestone_visibility (case_id, step2, step3, step4, updated_by, updated_at)
        VALUES (:case_id, :step2, :step3, :step4, :admin_id, NOW())
        ON DUPLICATE KEY UPDATE
            step2       = VALUES(step2),
            step3       = VALUES(step3),
            step4       = VALUES(step4),
            updated_by  = VALUES(updated_by),
            updated_at  = NOW()
    ");
    $stmt->execute([
        ':case_id'  => $caseId,
        ':step2'    => $step2,
        ':step3'    => $step3,
        ':step4'    => $step4,
        ':admin_id' => (int)$_SESSION['admin_id'],
    ]);

    echo json_encode(['success' => true, 'message' => 'Meilenstein-Sichtbarkeit aktualisiert']);
} catch (PDOException $e) {
    error_log('update_case_milestones.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
}
