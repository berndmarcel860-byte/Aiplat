<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../admin_session.php';
require_once '../AdminEmailHelper.php';
header('Content-Type: application/json');

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid withdrawal ID']);
    exit();
}

$withdrawalId = (int)$_POST['id'];

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
        throw new Exception('Withdrawal cannot be approved in its current state');
    }

    // === UPDATE WITHDRAWAL ===
    $stmt = $pdo->prepare("
        UPDATE withdrawals 
        SET 
            status = 'completed',
            processed_by = ?,
            processed_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['admin_id'], $withdrawalId]);

    // === UPDATE EXISTING TRANSACTION ===
    $stmt = $pdo->prepare("
        UPDATE transactions
        SET 
            status = 'completed',
            updated_at = NOW()
        WHERE reference = ?
          AND type = 'withdrawal'
          AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([
        $withdrawal['reference'],
        $withdrawal['user_id']
    ]);

    // === FETCH USER ===
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, last_name, balance 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$withdrawal['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {

        // --- SEND EMAIL ---
        try {
            $emailHelper = new AdminEmailHelper($pdo);
            
            // Lookup payment method name
            $methodName = 'Banküberweisung';
            if (!empty($withdrawal['method_code'])) {
                $stmt = $pdo->prepare("SELECT method_name FROM payment_methods WHERE method_code = ? LIMIT 1");
                $stmt->execute([$withdrawal['method_code']]);
                $method = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($method && !empty($method['method_name'])) {
                    $methodName = $method['method_name'];
                }
            }
            
            $customVars = [
                'amount' => number_format($withdrawal['amount'], 2) . ' €',
                'reference' => $withdrawal['reference'] ?? 'WD-' . $withdrawal['id'],
                'payment_method' => $methodName,
                'payment_details' => $withdrawal['payment_details'] ?? '',
                'transaction_date' => date('Y-m-d H:i:s')
            ];
            
            $emailHelper->sendTemplateEmail('withdrawal_completed', $user['id'], $customVars);
        } catch (Exception $e) {
            error_log("Approval email failed: " . $e->getMessage());
        }

        // --- USER NOTIFICATION ---
        $notifUser = $pdo->prepare("
            INSERT INTO user_notifications
            (user_id, title, message, type, related_entity, related_id, created_at)
            VALUES (:user_id, :title, :message, 'success', 'withdrawal', :rel_id, NOW())
        ");

        $notifUser->execute([
            ':user_id' => (int)$withdrawal['user_id'],
            ':title' => 'Auszahlung abgeschlossen',
            ':message' => 'Ihre Auszahlung über <strong>'
                . number_format($withdrawal['amount'], 2) . ' €</strong> wurde erfolgreich ausgeführt.',
            ':rel_id' => $withdrawalId
        ]);

        // --- ADMIN NOTIFICATION ---
        $notifAdmin = $pdo->prepare("
            INSERT INTO admin_notifications 
            (admin_id, title, message, type, is_read, created_at)
            VALUES (:admin_id, :title, :message, 'success', 0, NOW())
        ");

        $notifAdmin->execute([
            ':admin_id' => (int)$_SESSION['admin_id'],
            ':title' => 'Auszahlung genehmigt',
            ':message' => 'Sie haben eine Auszahlung von Benutzer-ID <strong>'
                . (int)$withdrawal['user_id'] . '</strong> über <strong>'
                . number_format($withdrawal['amount'], 2) . ' €</strong> genehmigt.'
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Withdrawal approved successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Failed to approve withdrawal',
        'error' => $e->getMessage()
    ]);
}

