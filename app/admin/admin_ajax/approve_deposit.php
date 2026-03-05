<?php
// =======================================================
// Error reporting (disable in production)
// =======================================================
ini_set('display_errors', 0);
error_reporting(E_ALL);

// =======================================================
// Include admin session and email helper
// =======================================================
require_once '../admin_session.php';
require_once '../AdminEmailHelper.php';
header('Content-Type: application/json');

// =======================================================
// Validate reference input
// =======================================================
if (empty($_POST['reference'])) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid reference']);
    exit();
}

$reference = trim($_POST['reference']);

try {
    $pdo->beginTransaction();

    // =======================================================
    // 1️⃣ Fetch transaction by reference
    // =======================================================
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE reference = ? LIMIT 1");
    $stmt->execute([$reference]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception("No transaction found for reference: $reference");
    }

    // =======================================================
    // 2️⃣ Allow flexible status (process even if not lowercase 'pending')
    // =======================================================
    $status = strtolower(trim($transaction['status']));
    if (!in_array($status, ['pending', 'processing', 'awaiting'])) {
        error_log("⚠️ Transaction with reference '{$reference}' has non-pending status '{$transaction['status']}', continuing anyway.");
    }

    // =======================================================
    // 3️⃣ Update transaction to completed
    // =======================================================
    $stmt = $pdo->prepare("
        UPDATE transactions 
        SET status = 'completed', processed_by = ?, updated_at = NOW()
        WHERE reference = ?
    ");
    $stmt->execute([$_SESSION['admin_id'], $reference]);

    // =======================================================
    // 4️⃣ Update deposit using reference
    // =======================================================
    $stmt = $pdo->prepare("
        UPDATE deposits 
        SET status = 'completed', processed_by = ?, processed_at = NOW(), updated_at = NOW()
        WHERE reference = ?
    ");
    $stmt->execute([$_SESSION['admin_id'], $reference]);

    if ($stmt->rowCount() === 0) {
        error_log("⚠️ No deposit updated for reference $reference");
    }

    // =======================================================
    // 5️⃣ Update user balance (only for deposits)
    // =======================================================
/*
    if ($transaction['type'] === 'deposit') {
        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$transaction['amount'], $transaction['user_id']]);
    }
*/
    // =======================================================
    // 6️⃣ Fetch user details
    // =======================================================
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$transaction['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found for transaction reference: ' . $reference);
    }

    // =======================================================
    // 7️⃣ Send deposit confirmation email
    // =======================================================
    try {
        $emailHelper = new AdminEmailHelper($pdo);
        
        // Lookup payment method name
        $methodName = 'Unknown';
        if (!empty($transaction['payment_method_id'])) {
            $methodStmt = $pdo->prepare("SELECT method_name FROM payment_methods WHERE id = ? LIMIT 1");
            $methodStmt->execute([$transaction['payment_method_id']]);
            $method = $methodStmt->fetch(PDO::FETCH_ASSOC);
            if ($method && !empty($method['method_name'])) {
                $methodName = $method['method_name'];
            }
        }
        
        $customVars = [
            'amount' => number_format($transaction['amount'], 2) . ' €',
            'payment_method' => $methodName,
            'transaction_id' => $transaction['reference'] ?? $transaction['id'],
            'transaction_date' => date('Y-m-d H:i:s'),
            'transaction_status' => 'Completed',
            'deposit_id' => $transaction['id']
        ];
        
        $emailHelper->sendTemplateEmail('deposit_received', $user['id'], $customVars);
    } catch (Exception $e) {
        error_log("Deposit confirmation email failed: " . $e->getMessage());
    }

    // =======================================================
    // 8️⃣ Create user notification
    // =======================================================
    try {
        $notifUser = $pdo->prepare("
            INSERT INTO user_notifications (user_id, title, message, type, related_entity, related_id, created_at)
            VALUES (:user_id, :title, :message, :type, :entity, :rel_id, NOW())
        ");
        $notifUser->execute([
            ':user_id' => (int)$transaction['user_id'],
            ':title' => 'Einzahlung bestätigt',
            ':message' => 'Ihre Einzahlung über <strong>' 
                . number_format($transaction['amount'], 2) . ' €</strong> mit Referenz <strong>'
                . htmlspecialchars($reference) . '</strong> wurde erfolgreich bestätigt.',
            ':type' => 'success',
            ':entity' => 'transaction',
            ':rel_id' => $reference
        ]);
    } catch (Exception $e) {
        error_log("User notification failed: " . $e->getMessage());
    }

    // =======================================================
    // 9️⃣ Create admin notification
    // =======================================================
    try {
        $notifAdmin = $pdo->prepare("
            INSERT INTO admin_notifications (admin_id, title, message, type, is_read, created_at)
            VALUES (:admin_id, :title, :message, :type, 0, NOW())
        ");
        $notifAdmin->execute([
            ':admin_id' => (int)$_SESSION['admin_id'],
            ':title' => 'Einzahlung genehmigt',
            ':message' => 'Sie haben eine Einzahlung von Benutzer-ID <strong>'
                . (int)$transaction['user_id'] . '</strong> über <strong>'
                . number_format($transaction['amount'], 2) . ' €</strong> (Referenz: <strong>'
                . htmlspecialchars($reference) . '</strong>) bestätigt.',
            ':type' => 'info'
        ]);
    } catch (Exception $e) {
        error_log("Admin notification failed: " . $e->getMessage());
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Deposit approved successfully using reference.'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Failed to approve deposit',
        'error' => $e->getMessage()
    ]);
}
?>

