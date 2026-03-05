<?php
/**
 * Reject Wallet Verification - Admin Endpoint
 * Admin rejects a wallet verification (e.g., wrong amount, fake transaction)
 */

require_once '../../config.php';
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    $admin_id = $_SESSION['admin_id'];
    
    // Validate input
    if (!isset($_POST['wallet_id']) || !is_numeric($_POST['wallet_id'])) {
        throw new Exception('Invalid wallet ID');
    }
    
    $wallet_id = intval($_POST['wallet_id']);
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    
    if (empty($reason)) {
        throw new Exception('Rejection reason is required');
    }
    
    // Get wallet details
    $stmt = $pdo->prepare("SELECT id, user_id, cryptocurrency, verification_status
                           FROM user_payment_methods 
                           WHERE id = ? AND type = 'crypto'");
    $stmt->execute([$wallet_id]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$wallet) {
        throw new Exception('Wallet not found');
    }
    
    // Check if wallet is in correct status
    if ($wallet['verification_status'] !== 'verifying') {
        throw new Exception('Wallet must be in verifying status to reject');
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update wallet status to failed and clear verification data
        $update_stmt = $pdo->prepare("UPDATE user_payment_methods 
                                       SET verification_status = 'failed',
                                           verification_txid = NULL,
                                           verification_notes = ?,
                                           updated_at = CURRENT_TIMESTAMP
                                       WHERE id = ?");
        $update_stmt->execute([$reason, $wallet_id]);
        
        // Log admin action
        $action = "reject_wallet_verification";
        $log_stmt = $pdo->prepare("INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, ip_address) 
                                   VALUES (?, ?, 'payment_method', ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_stmt->execute([$admin_id, $action, $wallet_id, $ip]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Wallet verification rejected',
            'wallet_id' => $wallet_id,
            'status' => 'failed',
            'reason' => $reason
        ]);
        
        // TODO: Send notification to user (email/SMS)
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}