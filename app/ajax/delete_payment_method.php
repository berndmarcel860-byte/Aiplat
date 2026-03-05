<?php
/**
 * Delete Payment Method
 * Removes a payment method from user's account
 */

session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];
$payment_id = $_POST['payment_id'] ?? 0;

try {
    if (empty($payment_id)) {
        throw new Exception('Payment method ID is required');
    }

    // Verify ownership and get payment details
    $stmt = $pdo->prepare("SELECT id, is_default FROM user_payment_methods WHERE id = ? AND user_id = ?");
    $stmt->execute([$payment_id, $user_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        throw new Exception('Payment method not found or access denied');
    }

    // Delete the payment method
    $stmt = $pdo->prepare("DELETE FROM user_payment_methods WHERE id = ? AND user_id = ?");
    $stmt->execute([$payment_id, $user_id]);

    // If this was the default method, set another one as default
    if ($payment['is_default'] == 1) {
        $stmt = $pdo->prepare("
            UPDATE user_payment_methods 
            SET is_default = 1 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Payment method deleted successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}