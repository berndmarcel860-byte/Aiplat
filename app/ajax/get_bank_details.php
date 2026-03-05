<?php
require_once '../config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT bank_name, account_holder, iban, bic
        FROM user_onboarding
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $bank = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bank) {
        echo json_encode(['success' => true, 'bank' => $bank]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No bank details found']);
    }
} catch (PDOException $e) {
    error_log("Bank fetch error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
