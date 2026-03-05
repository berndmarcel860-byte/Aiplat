<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            first_name,
            last_name,
            email,
            created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'data' => $users
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>