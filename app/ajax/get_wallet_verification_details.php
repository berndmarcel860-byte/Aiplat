<?php
/**
 * Get Wallet Verification Details - User Endpoint
 * Returns verification instructions for a specific wallet
 */

session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Validate input
    if (!isset($_GET['wallet_id']) || !is_numeric($_GET['wallet_id'])) {
        throw new Exception('Invalid wallet ID');
    }
    
    $wallet_id = intval($_GET['wallet_id']);
    
    // Get wallet verification details
    $stmt = $pdo->prepare("SELECT id, cryptocurrency, network, wallet_address,
                                  verification_status, verification_amount, 
                                  verification_address, verification_txid,
                                  verification_notes
                           FROM user_payment_methods 
                           WHERE id = ? AND user_id = ? AND type = 'crypto'");
    $stmt->execute([$wallet_id, $user_id]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$wallet) {
        throw new Exception('Wallet not found or access denied');
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'wallet_id' => $wallet['id'],
        'cryptocurrency' => $wallet['cryptocurrency'],
        'network' => $wallet['network'],
        'wallet_address' => $wallet['wallet_address'],
        'verification_status' => $wallet['verification_status'],
        'verification_amount' => $wallet['verification_amount'],
        'verification_address' => $wallet['verification_address'],
        'verification_txid' => $wallet['verification_txid'],
        'verification_notes' => $wallet['verification_notes']
    ];
    
    // Add instructions based on status
    if ($wallet['verification_status'] === 'pending') {
        if (empty($wallet['verification_amount']) || empty($wallet['verification_address'])) {
            $response['instructions'] = 'Verification details not yet set by admin. Please wait.';
        } else {
            $response['instructions'] = "Send exactly {$wallet['verification_amount']} {$wallet['cryptocurrency']} to the address below, then submit your transaction hash.";
            $response['ready_for_verification'] = true;
        }
    } elseif ($wallet['verification_status'] === 'verifying') {
        $response['instructions'] = 'Your verification is being reviewed by our admin team. Please wait.';
    } elseif ($wallet['verification_status'] === 'verified') {
        $response['instructions'] = 'Your wallet has been verified successfully!';
    } elseif ($wallet['verification_status'] === 'failed') {
        $response['instructions'] = 'Verification failed. ' . ($wallet['verification_notes'] ?? 'Please contact support.');
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}