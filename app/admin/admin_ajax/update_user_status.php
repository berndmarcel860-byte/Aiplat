<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $user_id = (int)($_POST['user_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    if (!$user_id || !$status) {
        throw new Exception('User ID and status are required');
    }
    
    $allowed_statuses = ['active', 'suspended', 'banned'];
    if (!in_array($status, $allowed_statuses)) {
        throw new Exception('Invalid status');
    }
    
    // Get current user info
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Update user status
    $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $user_id]);
    
    // Log audit trail
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, old_value, new_value, ip_address, user_agent, created_at)
        VALUES (?, 'STATUS_CHANGE', 'user', ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['admin_id'],
        $user_id,
        $user['status'],
        $status,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    // Log admin activity
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at) 
        VALUES (?, 'UPDATE_STATUS', 'user', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['admin_id'], 
        $user_id, 
        "Changed user status from {$user['status']} to $status for {$user['first_name']} {$user['last_name']} ({$user['email']}). Notes: $notes",
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'User status updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in update_user_status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>