<?php
/**
 * Delete Cryptocurrency (Admin)
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
    
    if ($crypto_id <= 0) {
        throw new Exception('Invalid cryptocurrency ID');
    }
    
    // Check if cryptocurrency is used in payment methods
    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_payment_methods WHERE cryptocurrency = (SELECT symbol FROM cryptocurrencies WHERE id = ?)");
    $checkStmt->execute([$crypto_id]);
    $result = $checkStmt->fetch();
    
    if ($result['count'] > 0) {
        throw new Exception('Cannot delete: This cryptocurrency is currently used in ' . $result['count'] . ' payment method(s)');
    }
    
    // Delete cryptocurrency (CASCADE will delete networks)
    $sql = "DELETE FROM cryptocurrencies WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$crypto_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Cryptocurrency not found');
    }
    
    // Log action
    $logSql = "INSERT INTO audit_logs (admin_id, action, entity, entity_id, ip_address) 
               VALUES (?, 'delete', 'cryptocurrency', ?, ?)";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([$_SESSION['admin_id'], $crypto_id, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cryptocurrency deleted successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}