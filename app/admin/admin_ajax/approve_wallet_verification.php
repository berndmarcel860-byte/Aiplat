<?php
/**
 * Approve Wallet Verification - Admin Endpoint
 * Admin approves a wallet after verifying the satoshi test transaction
 */

require_once '../../config.php';
require_once '../admin_session.php';
require_once __DIR__ . '/../AdminEmailHelper.php';

header('Content-Type: application/json');

try {
    $admin_id = $_SESSION['admin_id'];
    
    // Validate input
    if (!isset($_POST['wallet_id']) || !is_numeric($_POST['wallet_id'])) {
        throw new Exception('Invalid wallet ID');
    }
    
    $wallet_id = intval($_POST['wallet_id']);
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Get wallet details
    $stmt = $pdo->prepare("SELECT upm.id, upm.user_id, upm.cryptocurrency, upm.network, upm.wallet_address, upm.verification_status, upm.verification_txid
                           FROM user_payment_methods upm
                           WHERE upm.id = ? AND upm.type = 'crypto'");
    $stmt->execute([$wallet_id]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$wallet) {
        throw new Exception('Wallet not found');
    }
    
    // Check if wallet is in correct status
    if ($wallet['verification_status'] !== 'verifying') {
        throw new Exception('Wallet must be in verifying status to approve');
    }
    
    if (empty($wallet['verification_txid'])) {
        throw new Exception('No transaction hash submitted for this wallet');
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Update wallet status to verified
        $update_stmt = $pdo->prepare("UPDATE user_payment_methods 
                                       SET verification_status = 'verified',
                                           verified_by = ?,
                                           verified_at = CURRENT_TIMESTAMP,
                                           verification_notes = ?,
                                           updated_at = CURRENT_TIMESTAMP
                                       WHERE id = ?");
        $update_stmt->execute([$admin_id, $notes, $wallet_id]);
        
        // Log admin action
        $action = "approve_wallet_verification";
        $log_stmt = $pdo->prepare("INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, ip_address) 
                                   VALUES (?, ?, 'payment_method', ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_stmt->execute([$admin_id, $action, $wallet_id, $ip]);
        
        // Commit transaction
        $pdo->commit();
        
        // Send approval email to user
        try {
            $emailHelper = new AdminEmailHelper($pdo);
            $emailHelper->sendTemplateEmail('wallet_verification_approved', $wallet['user_id'], [
                'cryptocurrency' => $wallet['cryptocurrency'],
                'network' => $wallet['network'],
                'wallet_address' => $wallet['wallet_address'],
                'verification_txid' => $wallet['verification_txid'],
            ]);
        } catch (Exception $emailEx) {
            error_log("Wallet approval email failed for user {$wallet['user_id']}: " . $emailEx->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Wallet verification approved successfully',
            'wallet_id' => $wallet_id,
            'status' => 'verified'
        ]);
        
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