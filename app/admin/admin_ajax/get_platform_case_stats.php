<?php
/**
 * Returns per-platform case counts and total reported amounts for the chart.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

try {
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId   = (int)$_SESSION['admin_id'];

    if ($currentAdminRole === 'superadmin') {
        $rows = $pdo->query("
            SELECT p.name AS platform, COUNT(c.id) AS cnt,
                   COALESCE(SUM(c.reported_amount), 0) AS total_amount
            FROM scam_platforms p
            LEFT JOIN cases c ON c.platform_id = p.id
            GROUP BY p.id, p.name
            ORDER BY cnt DESC
            LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("
            SELECT p.name AS platform, COUNT(c.id) AS cnt,
                   COALESCE(SUM(c.reported_amount), 0) AS total_amount
            FROM scam_platforms p
            LEFT JOIN cases c ON c.platform_id = p.id AND c.admin_id = ?
            GROUP BY p.id, p.name
            ORDER BY cnt DESC
            LIMIT 20
        ");
        $stmt->execute([$currentAdminId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $labels  = [];
    $values  = [];
    $amounts = [];
    foreach ($rows as $row) {
        $labels[]  = $row['platform'];
        $values[]  = (int)$row['cnt'];
        $amounts[] = (float)$row['total_amount'];
    }

    echo json_encode(['labels' => $labels, 'values' => $values, 'amounts' => $amounts]);

} catch (PDOException $e) {
    error_log("get_platform_case_stats error: " . $e->getMessage());
    echo json_encode(['labels' => [], 'values' => [], 'amounts' => []]);
}
