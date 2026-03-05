<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method', 405);
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access', 401);
    }

    $withdrawalId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    if (!$withdrawalId) {
        throw new Exception('Withdrawal ID is required', 400);
    }

    // Fetch withdrawal with payment method details
    $stmt = $pdo->prepare("
        SELECT 
            w.*,
            pm.method_code as pm_method_code,
            pm.method_name as pm_method_name,
            pm.is_crypto as pm_is_crypto
        FROM withdrawals w
        LEFT JOIN payment_methods pm ON w.method_code COLLATE utf8mb4_unicode_ci = pm.method_code COLLATE utf8mb4_unicode_ci
        WHERE w.id = :id AND w.user_id = :user_id
    ");
    $stmt->execute([
        ':id' => $withdrawalId,
        ':user_id' => $_SESSION['user_id']
    ]);
    $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$withdrawal) {
        throw new Exception('Withdrawal not found', 404);
    }

    // Format response with nested payment method
    $response = [
        'success' => true,
        'data' => [
            'id' => $withdrawal['id'],
            'amount' => $withdrawal['amount'],
            'method_code' => $withdrawal['method_code'],
            'payment_method' => [
                'method_code' => $withdrawal['pm_method_code'],
                'method_name' => $withdrawal['pm_method_name'],
                'is_crypto' => $withdrawal['pm_is_crypto']
            ],
            'status' => $withdrawal['status'],
            'reference' => $withdrawal['reference'],
            'payment_details' => $withdrawal['payment_details'],
            'admin_notes' => $withdrawal['admin_notes'],
            'created_at' => $withdrawal['created_at'],
            'updated_at' => $withdrawal['updated_at']
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code((int)($e->getCode() ?: 500));
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}