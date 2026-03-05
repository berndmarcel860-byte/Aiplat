<?php
/**
 * Set Default Payment Method
 * Sets a payment method as the default for the user
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

    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM user_payment_methods WHERE id = ? AND user_id = ?");
    $stmt->execute([$payment_id, $user_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Payment method not found or access denied');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Unset all defaults for this user
    $stmt = $pdo->prepare("UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Set the selected payment method as default
    $stmt = $pdo->prepare("UPDATE user_payment_methods SET is_default = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$payment_id, $user_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Default payment method updated successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}