<?php
require_once '../../config.php';

require_once '../admin_session.php';

header('Content-Type: application/json');


// Validate input
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

$userId = (int)$_POST['id'];

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Instead of deleting, set user status to 'suspended'
    $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Log this action
    $logStmt = $pdo->prepare("
        INSERT INTO admin_logs 
        (admin_id, action, entity_type, entity_id, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $logStmt->execute([
        $_SESSION['admin_id'],
        'suspend',
        'user',
        $userId,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'User suspended successfully',
        'id' => $userId
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Suspend User Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to suspend user',
        'error' => $e->getMessage()
    ]);
}
?>