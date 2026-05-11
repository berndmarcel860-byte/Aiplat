<?php
require_once '../config.php';
require_once '../session.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access', 401);
    }

    $reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';
    if (empty($reference)) {
        throw new Exception('Missing deposit reference', 400);
    }

    $stmt = $pdo->prepare("
        SELECT
            d.id,
            d.amount,
            d.method_code,
            d.reference,
            d.status,
            d.proof_path,
            d.admin_notes,
            d.created_at,
            d.processed_at,
            pm.method_name,
            e.status        AS escrow_status,
            e.reference     AS escrow_reference,
            e.held_at       AS escrow_held_at,
            e.released_at   AS escrow_released_at
        FROM deposits d
        LEFT JOIN payment_methods pm ON pm.method_code = d.method_code
        LEFT JOIN escrow_accounts  e  ON e.deposit_id  = d.id
        WHERE d.reference = :ref
          AND d.user_id   = :uid
        LIMIT 1
    ");
    $stmt->execute([':ref' => $reference, ':uid' => $_SESSION['user_id']]);
    $deposit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$deposit) {
        throw new Exception('Deposit not found', 404);
    }

    echo json_encode(['success' => true, 'deposit' => $deposit]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
