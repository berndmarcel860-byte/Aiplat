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

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
    exit();
}

$transactionId = (int)$_POST['id'];

try {
    $pdo->beginTransaction();

    // =======================================================
    // Fetch transaction
    // =======================================================
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception('Transaction not found');
    }

    if ($transaction['status'] !== 'pending') {
        throw new Exception('Transaction is not pending');
    }

    // =======================================================
    // Update transaction
    // =======================================================
    $stmt = $pdo->prepare("
        UPDATE transactions 
        SET status = 'completed', processed_by = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['admin_id'], $transactionId]);

    // =======================================================
    // If deposit → update user balance, deposits table, email & notifications
    // =======================================================
    if ($transaction['type'] === 'deposit') {
        // Update user balance
        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$transaction['amount'], $transaction['user_id']]);

        // Update deposit record (if found)
        $stmt = $pdo->prepare("
            UPDATE deposits 
            SET status = 'completed', processed_by = ?, processed_at = NOW(), updated_at = NOW() 
            WHERE reference = ?
        ");
        $stmt->execute([$_SESSION['admin_id'], $transaction['reference']]);

        // Get user
        $stmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$transaction['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // === 1️⃣ Send transaction approval email
            try {
                $emailHelper = new AdminEmailHelper($pdo);
                
                $customVars = [
                    'amount' => number_format($transaction['amount'], 2) . ' €',
                    'transaction_type' => $transaction['type'] ?? 'Transaction',
                    'transaction_id' => $transaction['reference'] ?? $transaction['id'],
                    'transaction_date' => date('Y-m-d H:i:s'),
                    'transaction_status' => 'Completed'
                ];
                
                $emailHelper->sendTemplateEmail('deposit_received', $user['id'], $customVars);
            } catch (Exception $e) {
                error_log("Transaction approval email failed: " . $e->getMessage());
            }

            // === 2️⃣ Create user notification
            try {
                $notifUser = $pdo->prepare("
                    INSERT INTO user_notifications (user_id, title, message, type, related_entity, related_id, created_at)
                    VALUES (:user_id, :title, :message, :type, :entity, :rel_id, NOW())
                ");
                $notifUser->execute([
                    ':user_id' => (int)$transaction['user_id'],
                    ':title' => 'Einzahlung bestätigt',
                    ':message' => 'Ihre Einzahlung über <strong>' 
                        . number_format($transaction['amount'], 2) . ' €</strong> wurde erfolgreich bestätigt. '
                        . 'Referenz: <strong>' . htmlspecialchars($transaction['reference']) . '</strong>.',
                    ':type' => 'success',
                    ':entity' => 'transaction',
                    ':rel_id' => $transactionId
                ]);
            } catch (Exception $e) {
                error_log("User notification failed: " . $e->getMessage());
            }

            // === 3️⃣ Create admin notification
            try {
                $assignedAdmin = (int)$_SESSION['admin_id'];
                $notifAdmin = $pdo->prepare("
                    INSERT INTO admin_notifications (admin_id, title, message, type, is_read, created_at)
                    VALUES (:admin_id, :title, :message, :type, 0, NOW())
                ");
                $notifAdmin->execute([
                    ':admin_id' => $assignedAdmin,
                    ':title' => 'Einzahlung genehmigt',
                    ':message' => 'Sie haben eine Einzahlung von Benutzer-ID <strong>'
                        . (int)$transaction['user_id'] . '</strong> über <strong>'
                        . number_format($transaction['amount'], 2) . ' €</strong> bestätigt.',
                    ':type' => 'info'
                ]);
            } catch (Exception $e) {
                error_log("Admin notification failed: " . $e->getMessage());
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaction approved successfully and notifications sent.'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to approve transaction',
        'error' => $e->getMessage()
    ]);
}
?>
