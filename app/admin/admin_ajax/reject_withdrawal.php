<?php 
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid withdrawal ID']);
    exit();
}

if (empty($_POST['reason'])) {
    echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
    exit();
}

$withdrawalId = (int)$_POST['id'];
$reason = trim($_POST['reason']);

try {
    $pdo->beginTransaction();

    // === FETCH WITHDRAWAL ===
    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
    $stmt->execute([$withdrawalId]);
    $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$withdrawal) {
        throw new Exception('Withdrawal not found');
    }

    if (!in_array($withdrawal['status'], ['pending', 'processing'])) {
        throw new Exception('Withdrawal cannot be rejected in its current state');
    }

    // === UPDATE WITHDRAWAL ===
    $stmt = $pdo->prepare("
        UPDATE withdrawals 
        SET 
            status = 'failed',
            admin_notes = ?,
            processed_by = ?,
            processed_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$reason, $_SESSION['admin_id'], $withdrawalId]);

    // === GET USER ===
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, balance FROM users WHERE id = ?");
    $stmt->execute([$withdrawal['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // === GET PAYMENT METHOD NAME ===
    // Query user_payment_methods to get proper display name
    $paymentMethodName = $withdrawal['method_code'] ?? 'N/A';
    if ($withdrawal['method_code']) {
        $stmt = $pdo->prepare("SELECT label, cryptocurrency, bank_name, type 
                               FROM user_payment_methods 
                               WHERE user_id = ? AND payment_method = ? 
                               LIMIT 1");
        $stmt->execute([$withdrawal['user_id'], $withdrawal['method_code']]);
        $paymentMethodData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($paymentMethodData) {
            // Use label first, then cryptocurrency or bank_name
            $paymentMethodName = $paymentMethodData['label'] ?: 
                                ($paymentMethodData['type'] === 'crypto' ? 
                                    $paymentMethodData['cryptocurrency'] : 
                                    $paymentMethodData['bank_name']) ?: 
                                $withdrawal['method_code'];
        }
    }

    if ($user) {
        // --- SEND EMAIL NOTIFICATION ---
        try {
            require_once '../AdminEmailHelper.php';
            $emailHelper = new AdminEmailHelper($pdo);
            
            $customVars = [
                'amount' => number_format($withdrawal['amount'], 2) . ' €',
                'reason' => $reason,
                'reference' => $withdrawal['reference'] ?? 'WD-' . $withdrawal['id'],
                'transaction_date' => $withdrawal['created_at'] ?? date('Y-m-d H:i:s'),
                'payment_method' => $paymentMethodName,
                'payment_details' => $withdrawal['payment_details'] ?? 'N/A',
                'transaction_id' => $withdrawal['id'],
                'rejected_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $reason
            ];
            
            $emailHelper->sendTemplateEmail('withdrawal_rejected', $user['id'], $customVars);
        } catch (Exception $e) {
            error_log("Withdrawal rejection email failed: " . $e->getMessage());
        }

        // --- USER NOTIFICATION ---
        $notifUser = $pdo->prepare("
            INSERT INTO user_notifications
            (user_id, title, message, type, related_entity, related_id, created_at)
            VALUES (:user_id, :title, :message, 'warning', 'withdrawal', :rel_id, NOW())
        ");

        $notifUser->execute([
            ':user_id' => (int)$withdrawal['user_id'],
            ':title' => 'Auszahlung abgelehnt',
            ':message' => 'Ihre Auszahlung über <strong>' 
                . number_format($withdrawal['amount'], 2) . ' €</strong> wurde abgelehnt. Grund: '
                . htmlspecialchars($reason),
            ':rel_id' => $withdrawalId
        ]);

        // --- ADMIN NOTIFICATION ---
        $notifAdmin = $pdo->prepare("
            INSERT INTO admin_notifications 
            (admin_id, title, message, type, is_read, created_at)
            VALUES (:admin_id, :title, :message, 'warning', 0, NOW())
        ");

        $notifAdmin->execute([
            ':admin_id' => (int)$_SESSION['admin_id'],
            ':title' => 'Auszahlung abgelehnt',
            ':message' => 'Sie haben eine Auszahlung von Benutzer-ID <strong>'
                . (int)$withdrawal['user_id'] . '</strong> über <strong>'
                . number_format($withdrawal['amount'], 2) . ' €</strong> abgelehnt.'
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Withdrawal rejected successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Failed to reject withdrawal',
        'error' => $e->getMessage()
    ]);
}
