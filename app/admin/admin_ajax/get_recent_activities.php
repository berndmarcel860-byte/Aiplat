<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.action,
            a.entity_type,
            a.entity_id,
            a.old_value,
            a.new_value,
            a.created_at,
            CONCAT(ad.first_name, ' ', ad.last_name) as admin_name,
            a.ip_address,
            a.user_agent
        FROM audit_logs a
        LEFT JOIN admins ad ON a.admin_id = ad.id
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $activities
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>