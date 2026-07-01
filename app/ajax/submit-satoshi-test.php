<?php
session_start();

require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

try {
    $userId = (int)$_SESSION['user_id'];

    $currency = isset($_POST['currency']) ? strtoupper(trim((string)$_POST['currency'])) : '';
    $amountEur = isset($_POST['amount_eur']) ? (float)$_POST['amount_eur'] : 0.0;
    $transactionHash = isset($_POST['transaction_hash']) ? trim((string)$_POST['transaction_hash']) : '';
    $notes = isset($_POST['notes']) ? trim((string)$_POST['notes']) : null;

    if ($currency === '' || strlen($currency) > 10) {
        throw new Exception('Invalid currency selection');
    }

    if ($amountEur <= 0) {
        throw new Exception('Invalid verification amount');
    }

    if ($transactionHash === '') {
        throw new Exception('Transaction hash / reference is required');
    }

    if (strlen($transactionHash) > 255) {
        throw new Exception('Transaction hash / reference is too long');
    }

    if ($notes !== null && $notes !== '' && strlen($notes) > 65535) {
        throw new Exception('Notes are too long');
    }

    $existingStmt = $pdo->prepare("
        SELECT id
        FROM satoshi_tests
        WHERE user_id = ?
          AND status IN ('pending', 'under_review')
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $existingStmt->execute([$userId]);
    $existingPending = $existingStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingPending) {
        echo json_encode([
            'success' => true,
            'message' => 'Verification already submitted and currently under review.'
        ]);
        exit;
    }

    $insertStmt = $pdo->prepare("
        INSERT INTO satoshi_tests (
            user_id,
            amount,
            currency,
            payment_method,
            crypto_coin,
            tx_reference,
            status,
            admin_notes
        ) VALUES (?, ?, 'EUR', 'crypto', ?, ?, 'pending', ?)
    ");

    $insertStmt->execute([
        $userId,
        $amountEur,
        $currency,
        $transactionHash,
        ($notes === '') ? null : $notes
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Verification submitted successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
