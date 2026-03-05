<?php
/**
 * Toggle Cryptocurrency Active Status (Admin)
 */

session_start();
require_once '../../config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $crypto_id = intval($_POST['crypto_id'] ?? 0);
    $is_active = intval($_POST['is_active'] ?? 0);
    
    if ($crypto_id <= 0) {
        throw new Exception('Invalid cryptocurrency ID');
    }
    
    // Update status
    $sql = "UPDATE cryptocurrencies SET is_active = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$is_active, $crypto_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Cryptocurrency not found');
    }
    
    // Log action
    $action = $is_active ? 'enable' : 'disable';
    $logSql = "INSERT INTO audit_logs (admin_id, action, entity, entity_id, ip_address) 
               VALUES (?, ?, 'cryptocurrency', ?, ?)";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([$_SESSION['admin_id'], $action, $crypto_id, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    $message = $is_active ? 'Cryptocurrency enabled' : 'Cryptocurrency disabled';
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}