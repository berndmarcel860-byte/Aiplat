<?php
// =======================================================
// Error reporting (disable in production)
// =======================================================
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid deposit ID']);
    exit();
}

$depositId = (int)$_POST['id'];
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : 'Keine Begründung angegeben';

try {
    $pdo->beginTransaction();
    
    // Get deposit details
    $stmt = $pdo->prepare("SELECT * FROM deposits WHERE id = ?");
    $stmt->execute([$depositId]);
    $deposit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$deposit) {
        throw new Exception('Deposit not found');
    }
    
    if ($deposit['status'] !== 'pending') {
        throw new Exception('Deposit is not pending');
    }
    
    // Update deposit status
    $stmt = $pdo->prepare("UPDATE deposits SET status = 'failed', admin_notes = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$reason, $depositId]);
    
    // Also update the transaction if exists
    if (!empty($deposit['reference'])) {
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE reference = ?");
        $stmt->execute([$deposit['reference']]);
    }
    
    // Get user details
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$deposit['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Send rejection email using AdminEmailHelper
        try {
            require_once '../AdminEmailHelper.php';
            $emailHelper = new AdminEmailHelper($pdo);
            
            $customVars = [
                'amount' => number_format($deposit['amount'], 2) . ' €',
                'reason' => $reason,
                'reference' => $deposit['reference'] ?? $deposit['id'],
                'transaction_id' => $deposit['reference'] ?? $deposit['id'],
                'transaction_date' => date('Y-m-d H:i:s')
            ];
            
            $emailHelper->sendTemplateEmail('deposit_rejected', $user['id'], $customVars);
        } catch (Exception $e) {
            error_log("Deposit rejection email failed: " . $e->getMessage());
        }
        
        // Create user notification
        try {
            $notifUser = $pdo->prepare("
                INSERT INTO user_notifications (user_id, title, message, type, related_entity, related_id, created_at)
                VALUES (:user_id, :title, :message, :type, :entity, :rel_id, NOW())
            ");
            $notifUser->execute([
                ':user_id' => (int)$deposit['user_id'],
                ':title' => 'Einzahlung abgelehnt',
                ':message' => 'Ihre Einzahlung über <strong>' 
                    . number_format($deposit['amount'], 2) . ' €</strong> wurde leider abgelehnt. Grund: '
                    . htmlspecialchars($reason),
                ':type' => 'warning',
                ':entity' => 'deposit',
                ':rel_id' => $depositId
            ]);
        } catch (Exception $e) {
            error_log("User notification failed: " . $e->getMessage());
        }
    }
    
    // Log admin action
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, description, entity_type, entity_id, created_at)
            VALUES (?, 'reject_deposit', ?, 'deposit', ?, NOW())
        ");
        $logStmt->execute([
            $_SESSION['admin_id'],
            'Rejected deposit of ' . number_format($deposit['amount'], 2) . ' € for user ID ' . $deposit['user_id'],
            $depositId
        ]);
    } catch (Exception $e) {
        error_log("Admin log failed: " . $e->getMessage());
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Deposit rejected successfully'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Failed to reject deposit',
        'error' => $e->getMessage()
    ]);
}
?>