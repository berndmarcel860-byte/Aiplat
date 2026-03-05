<?php
require_once '../config.php';
require_once '../session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);

    if (empty($paymentMethod)) {
        throw new Exception('Please select a payment method');
    }

    // Check if method exists in user's payment methods
    $stmt = $pdo->prepare("SELECT id FROM user_payment_methods 
                          WHERE user_id = :user_id AND payment_method = :method");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':method' => $paymentMethod
    ]);
    
    if (!$stmt->fetch()) {
        // Add new payment method if not exists
        $pdo->prepare("INSERT INTO user_payment_methods 
                      (user_id, payment_method, is_default) 
                      VALUES (:user_id, :method, 1)")
            ->execute([
                ':user_id' => $_SESSION['user_id'],
                ':method' => $paymentMethod
            ]);
    } else {
        // Update default payment method
        $pdo->beginTransaction();
        
        // Remove default from all methods
        $pdo->prepare("UPDATE user_payment_methods 
                      SET is_default = 0 
                      WHERE user_id = :user_id")
            ->execute([':user_id' => $_SESSION['user_id']]);
        
        // Set selected as default
        $pdo->prepare("UPDATE user_payment_methods 
                      SET is_default = 1 
                      WHERE user_id = :user_id AND payment_method = :method")
            ->execute([
                ':user_id' => $_SESSION['user_id'],
                ':method' => $paymentMethod
            ]);
        
        $pdo->commit();
    }

    // Also update users table for backward compatibility
    $pdo->prepare("UPDATE users SET payment_method = :method WHERE id = :user_id")
        ->execute([
            ':method' => $paymentMethod,
            ':user_id' => $_SESSION['user_id']
        ]);

    echo json_encode([
        'success' => true,
        'message' => 'Payment method updated successfully'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}