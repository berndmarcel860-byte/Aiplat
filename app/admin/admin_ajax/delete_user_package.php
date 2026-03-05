<?php
// admin_ajax/delete_user_package.php
// Delete user package assignment

require_once '../admin_session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$id = (int)$_POST['id'];

try {
    // Verify assignment exists and get details for logging
    $stmt = $pdo->prepare("
        SELECT up.*, u.email as user_email, p.name as package_name
        FROM user_packages up
        JOIN users u ON up.user_id = u.id
        JOIN packages p ON up.package_id = p.id
        WHERE up.id = ?
    ");
    $stmt->execute([$id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'Package assignment not found']);
        exit();
    }
    
    // Delete the assignment
    $stmt = $pdo->prepare("DELETE FROM user_packages WHERE id = ?");
    $stmt->execute([$id]);
    
    // Log admin action
    $logStmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at)
        VALUES (?, 'delete', 'user_package', ?, ?, ?, ?, NOW())
    ");
    $logStmt->execute([
        $_SESSION['admin_id'],
        $id,
        json_encode([
            'user_email' => $assignment['user_email'],
            'package_name' => $assignment['package_name'],
            'status' => $assignment['status']
        ]),
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Package assignment deleted successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Delete user package error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}