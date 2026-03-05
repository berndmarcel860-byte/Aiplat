<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.case_number,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            c.reported_amount,
            c.status,
            c.created_at
        FROM cases c
        JOIN users u ON c.user_id = u.id
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'data' => $cases
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>