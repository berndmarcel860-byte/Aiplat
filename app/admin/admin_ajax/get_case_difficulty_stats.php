<?php
/**
 * Returns case counts grouped by refund_difficulty for the donut chart.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

try {
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId   = (int)$_SESSION['admin_id'];

    if ($currentAdminRole === 'superadmin') {
        $rows = $pdo->query("
            SELECT COALESCE(refund_difficulty, 'medium') AS difficulty, COUNT(*) AS cnt
            FROM cases
            GROUP BY difficulty
        ")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("
            SELECT COALESCE(refund_difficulty, 'medium') AS difficulty, COUNT(*) AS cnt
            FROM cases
            WHERE admin_id = ?
            GROUP BY difficulty
        ");
        $stmt->execute([$currentAdminId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $result = ['easy' => 0, 'medium' => 0, 'hard' => 0];
    foreach ($rows as $row) {
        if (isset($result[$row['difficulty']])) {
            $result[$row['difficulty']] = (int)$row['cnt'];
        }
    }

    echo json_encode($result);

} catch (PDOException $e) {
    error_log("get_case_difficulty_stats error: " . $e->getMessage());
    echo json_encode(['easy' => 0, 'medium' => 0, 'hard' => 0]);
}
