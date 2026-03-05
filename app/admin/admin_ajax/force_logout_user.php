<?php
require_once '../admin_session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

$user_id = (int)$_POST['user_id'];
$session_id = isset($_POST['session_id']) ? $_POST['session_id'] : '';
$admin_id = $_SESSION['admin_id'];

try {
    $pdo->beginTransaction();
    
    // Remove from online users
    $stmt = $pdo->prepare("DELETE FROM online_users WHERE user_id = ? AND session_id = ?");
    $stmt->execute([$user_id, $session_id]);
    
    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs 
        (admin_id, action, entity_type, entity_id, new_value, ip_address, user_agent, created_at)
        VALUES (?, 'force_logout', 'user', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $admin_id,
        $user_id,
        json_encode(['session_id' => $session_id]),
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'User has been forced to logout successfully'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>