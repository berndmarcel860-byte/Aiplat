<?php
require_once '../config.php';
require_once '../session.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized', 401);
    }

    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $balance = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'balance' => number_format($balance, 2)
    ]);
} catch (Exception $e) {
    http_response_code((int)($e->getCode() ?: 400));
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}