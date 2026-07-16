<?php
/**
 * Admin AJAX – return milestone visibility flags for a case.
 * GET ?case_id=<int>
 */
require_once '../admin_session.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

$caseId = isset($_GET['case_id']) ? (int)$_GET['case_id'] : 0;
if ($caseId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Fall-ID']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT step2, step3, step4 FROM case_milestone_visibility WHERE case_id = ? LIMIT 1"
    );
    $stmt->execute([$caseId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'step2'   => $row ? (int)$row['step2'] : 0,
        'step3'   => $row ? (int)$row['step3'] : 0,
        'step4'   => $row ? (int)$row['step4'] : 0,
    ]);
} catch (PDOException $e) {
    error_log('get_case_milestones.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
}
