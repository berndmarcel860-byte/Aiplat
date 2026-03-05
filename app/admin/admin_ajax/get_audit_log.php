<?php
require_once '../admin_session.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid audit log ID']);
    exit();
}

$audit_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            al.*,
            CONCAT(a.first_name, ' ', a.last_name) as admin_name
        FROM audit_logs al
        LEFT JOIN admins a ON al.admin_id = a.id
        WHERE al.id = ?
    ");
    $stmt->execute([$audit_id]);
    $audit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$audit) {
        echo json_encode(['success' => false, 'message' => 'Audit log not found']);
        exit();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'audit' => $audit
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>