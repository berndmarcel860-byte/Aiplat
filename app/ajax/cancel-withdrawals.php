<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../session.php';

header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }

    // Check user authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access - Please login', 401);
    }

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Security error - Invalid CSRF token', 403);
    }

    // Validate input
    $withdrawalId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$withdrawalId) {
        throw new Exception('Invalid withdrawal ID', 400);
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Get withdrawal details
        $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ? AND user_id = ?");
        $stmt->execute([$withdrawalId, $_SESSION['user_id']]);
        $withdrawal = $stmt->fetch();

        if (!$withdrawal) {
            throw new Exception('Withdrawal not found or not authorized', 404);
        }

        if ($withdrawal['status'] !== 'pending') {
            throw new Exception('Only pending withdrawals can be cancelled', 400);
        }

        // Update withdrawal status
        $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$withdrawalId]);

        // Update transaction status
        $stmt = $pdo->prepare("UPDATE transactions 
                              SET status = 'cancelled', updated_at = NOW() 
                              WHERE reference = ? AND type = 'withdrawal'");
        $stmt->execute([$withdrawal['reference']]);

        // Refund amount to user balance
        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$withdrawal['amount'], $_SESSION['user_id']]);

        // Get new balance
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $newBalance = $stmt->fetchColumn();

        // Commit transaction
        $pdo->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Withdrawal cancelled successfully',
            'new_balance' => $newBalance
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        throw new Exception('Database error: ' . $e->getMessage());
    }

} catch (Exception $e) {
    http_response_code((int)($e->getCode() ?: 400));
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}