<?php
// admin_ajax/update_package_payment.php
// Approve or reject a package payment record.

require_once '../admin_session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id     = isset($_POST['id'])     ? (int)$_POST['id']            : 0;
$action = isset($_POST['action']) ? trim($_POST['action'])        : '';
$notes  = isset($_POST['notes'])  ? trim($_POST['notes'])         : '';

if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

try {
    // Verify record exists and is pending
    $stmt = $pdo->prepare("SELECT pp.*, up.status AS pkg_status FROM package_payments pp
                           LEFT JOIN user_packages up ON pp.user_package_id = up.id
                           WHERE pp.id = ? LIMIT 1");
    $stmt->execute([$id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment record not found.']);
        exit();
    }
    if ($payment['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Only pending payments can be updated.']);
        exit();
    }

    $newStatus  = ($action === 'approve') ? 'completed' : 'failed';
    $adminId    = (int)$_SESSION['admin_id'];

    $pdo->beginTransaction();

    // Update payment status
    $upd = $pdo->prepare("UPDATE package_payments
                          SET status = ?, admin_notes = ?, processed_by = ?, processed_at = NOW(), updated_at = NOW()
                          WHERE id = ?");
    $upd->execute([$newStatus, $notes ?: null, $adminId, $id]);

    // If approved, also activate the linked user_package
    if ($action === 'approve' && !empty($payment['user_package_id'])) {
        $activateStmt = $pdo->prepare("UPDATE user_packages SET status = 'active', updated_at = NOW()
                                       WHERE id = ? AND status = 'pending'");
        $activateStmt->execute([$payment['user_package_id']]);
        if ($activateStmt->rowCount() === 0) {
            error_log("update_package_payment: user_package id={$payment['user_package_id']} was not pending or not found.");
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully.',
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('update_package_payment error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}
