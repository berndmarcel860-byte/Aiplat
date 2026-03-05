<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
    exit();
}

$transactionId = (int)$_POST['id'];

try {
    $pdo->beginTransaction();
    
    // Get transaction details
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        throw new Exception('Transaction not found');
    }
    
    if ($transaction['status'] !== 'pending') {
        throw new Exception('Transaction is not pending');
    }
    
    // Update transaction status
    $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed', processed_by = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id'], $transactionId]);
    
    // If this is a withdrawal, return funds to user balance
    if ($transaction['type'] === 'withdrawal') {
        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$transaction['amount'], $transaction['user_id']]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction rejected successfully'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to reject transaction',
        'error' => $e->getMessage()
    ]);
}
?>