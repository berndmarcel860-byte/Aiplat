<?php
require_once '../../config.php';
require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
    exit();
}

$transactionId = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            t.*, 
            u.first_name AS user_first_name, 
            u.last_name AS user_last_name,
            pm.method_name
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN payment_methods pm ON t.payment_method_id = pm.id
        WHERE t.id = ?
    ");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'transaction' => $transaction
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get transaction details',
        'error' => $e->getMessage()
    ]);
}
?>