<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../admin_session.php';
require_once '../../EmailHelper.php';

header('Content-Type: application/json');

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
    exit();
}

$transactionId = (int)$_POST['id'];
$reason = trim($_POST['reason'] ?? '');

try {
    $pdo->beginTransaction();

    // Fetch transaction with payment method name
    $stmt = $pdo->prepare("
        SELECT t.*, pm.method_name
        FROM transactions t
        LEFT JOIN payment_methods pm ON t.payment_method_id = pm.id
        WHERE t.id = ?
    ");
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

    // Get user
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$transaction['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // =======================================================
    // If withdrawal → restore balance + update withdrawals table
    // =======================================================
    if ($transaction['type'] === 'withdrawal') {
        // Restore user balance
        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$transaction['amount'], $transaction['user_id']]);

        // Update withdrawals table
        $stmt = $pdo->prepare("
            UPDATE withdrawals
            SET status = 'failed',
                admin_notes = ?,
                processed_by = ?,
                processed_at = NOW(),
                updated_at = NOW()
            WHERE reference = ? AND user_id = ?
        ");
        $stmt->execute([$reason, $_SESSION['admin_id'], $transaction['reference'], $transaction['user_id']]);

        if ($user) {
            // Fetch payment method / details from withdrawals table
            $methodName    = $transaction['method_name'] ?? 'N/A';
            $paymentDetails = '';
            $stmt2 = $pdo->prepare("SELECT method_code, payment_details FROM withdrawals WHERE reference = ? AND user_id = ? LIMIT 1");
            $stmt2->execute([$transaction['reference'], $transaction['user_id']]);
            $wd = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($wd) {
                $paymentDetails = $wd['payment_details'] ?? '';
                if (empty($methodName) || $methodName === 'N/A') {
                    $methodName = $wd['method_code'] ?? 'N/A';
                }
            }

            // Send withdrawal_rejected email via EmailHelper
            try {
                $emailHelper = new EmailHelper($pdo);
                $customVars = [
                    'amount'           => number_format($transaction['amount'], 2) . ' €',
                    'payment_method'   => $methodName,
                    'payment_details'  => $paymentDetails,
                    'transaction_id'   => $transaction['reference'] ?? (string)$transactionId,
                    'reference'        => $transaction['reference'] ?? (string)$transactionId,
                    'transaction_date' => isset($transaction['created_at'])
                                         ? date('d.m.Y H:i', strtotime($transaction['created_at']))
                                         : date('d.m.Y H:i'),
                    'rejection_reason' => $reason ?: 'Keine Angabe',
                    'reason'           => $reason ?: 'Keine Angabe',
                    'rejected_at'      => date('d.m.Y H:i'),
                ];
                $emailHelper->sendEmail('withdrawal_rejected', $user['id'], $customVars);
            } catch (Exception $e) {
                error_log("Withdrawal rejection email failed: " . $e->getMessage());
            }

            // User notification
            try {
                $notifUser = $pdo->prepare("
                    INSERT INTO user_notifications (user_id, title, message, type, related_entity, related_id, created_at)
                    VALUES (:user_id, :title, :message, :type, :entity, :rel_id, NOW())
                ");
                $notifUser->execute([
                    ':user_id' => (int)$transaction['user_id'],
                    ':title'   => 'Auszahlung abgelehnt',
                    ':message' => 'Ihre Auszahlung über <strong>'
                        . number_format($transaction['amount'], 2) . ' €</strong> wurde abgelehnt.'
                        . ($reason ? ' Grund: ' . htmlspecialchars($reason) : ''),
                    ':type'    => 'warning',
                    ':entity'  => 'transaction',
                    ':rel_id'  => $transactionId,
                ]);
            } catch (Exception $e) {
                error_log("User notification failed: " . $e->getMessage());
            }

            // Admin notification
            try {
                $notifAdmin = $pdo->prepare("
                    INSERT INTO admin_notifications (admin_id, title, message, type, is_read, created_at)
                    VALUES (:admin_id, :title, :message, :type, 0, NOW())
                ");
                $notifAdmin->execute([
                    ':admin_id' => (int)$_SESSION['admin_id'],
                    ':title'    => 'Auszahlung abgelehnt',
                    ':message'  => 'Sie haben eine Auszahlung von Benutzer-ID <strong>'
                        . (int)$transaction['user_id'] . '</strong> über <strong>'
                        . number_format($transaction['amount'], 2) . ' €</strong> abgelehnt.',
                    ':type'     => 'warning',
                ]);
            } catch (Exception $e) {
                error_log("Admin notification failed: " . $e->getMessage());
            }
        }
    }

    // =======================================================
    // If deposit → send deposit rejection notification
    // =======================================================
    if ($transaction['type'] === 'deposit' && $user) {
        // User notification
        try {
            $notifUser = $pdo->prepare("
                INSERT INTO user_notifications (user_id, title, message, type, related_entity, related_id, created_at)
                VALUES (:user_id, :title, :message, :type, :entity, :rel_id, NOW())
            ");
            $notifUser->execute([
                ':user_id' => (int)$transaction['user_id'],
                ':title'   => 'Einzahlung abgelehnt',
                ':message' => 'Ihre Einzahlung über <strong>'
                    . number_format($transaction['amount'], 2) . ' €</strong> wurde abgelehnt.'
                    . ($reason ? ' Grund: ' . htmlspecialchars($reason) : ''),
                ':type'    => 'warning',
                ':entity'  => 'transaction',
                ':rel_id'  => $transactionId,
            ]);
        } catch (Exception $e) {
            error_log("User notification failed: " . $e->getMessage());
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaction rejected successfully'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Failed to reject transaction',
        'error'   => $e->getMessage()
    ]);
}
?>