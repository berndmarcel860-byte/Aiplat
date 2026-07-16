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
require_once __DIR__ . '/../AdminEmailHelper.php';
require_once __DIR__ . '/../../database/balance_helpers.php';
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
    $creditedAmount = 0.0;
    $newBalance = null;

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
    $alreadyCompleted = in_array($status, ['completed', 'approved'], true);
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
    // 5️⃣ Update escrow status to 'released' on approval
    // =======================================================
    try {
        $escrowStmt = $pdo->prepare("
            UPDATE escrow_accounts e
            INNER JOIN deposits d ON d.id = e.deposit_id
            SET e.status = 'released', e.released_at = NOW(), e.released_by = ?
            WHERE d.reference = ?
        ");
        $escrowStmt->execute([$_SESSION['admin_id'], $reference]);
    } catch (Exception $e) {
        error_log("Escrow update failed (non-fatal): " . $e->getMessage());
    }

    // =======================================================
    // 6️⃣ Update user balance (only for deposits)
    // =======================================================
    if ($transaction['type'] === 'deposit' && !$alreadyCompleted) {
        $balanceResult = adjustUserBalance($pdo, (int)$transaction['user_id'], (float)$transaction['amount']);
        $creditedAmount = (float)$transaction['amount'];
        $newBalance = (float)$balanceResult['new_balance'];
    }
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
    // 7️⃣ Create admin notification
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

    if ($creditedAmount > 0 && $newBalance !== null) {
        notifyBalanceCredit(
            $pdo,
            (int)$transaction['user_id'],
            $creditedAmount,
            $newBalance,
            'Bestätigte Einzahlung ' . $reference
        );
    }

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
