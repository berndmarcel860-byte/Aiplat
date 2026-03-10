<?php
/**
 * Update Payment Method (Fiat or Crypto)
 * Handles editing existing payment methods for both bank accounts and crypto wallets
 */

session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $id   = intval($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? '';

    if ($id <= 0) {
        throw new Exception('Invalid payment method ID');
    }

    if (empty($type) || !in_array($type, ['fiat', 'crypto'])) {
        throw new Exception('Invalid payment method type');
    }

    // Verify the payment method belongs to this user
    $stmt = $pdo->prepare("SELECT id, type FROM user_payment_methods WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        throw new Exception('Payment method not found');
    }

    if ($type === 'fiat') {
        $payment_method = trim($_POST['payment_method'] ?? '');
        if (empty($payment_method)) {
            throw new Exception('Payment method name is required');
        }

        $bank_name      = trim($_POST['bank_name'] ?? '');
        $account_holder = trim($_POST['account_holder'] ?? '');
        $iban           = preg_replace('/\s+/', '', strtoupper(trim($_POST['iban'] ?? '')));
        $bic            = trim($_POST['bic'] ?? '');
        $label          = trim($_POST['label'] ?? '');

        if (!empty($iban) && !preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', $iban)) {
            throw new Exception('Invalid IBAN format');
        }

        $stmt = $pdo->prepare(
            "UPDATE user_payment_methods
             SET payment_method = ?, bank_name = ?, account_holder = ?,
                 iban = ?, bic = ?, label = ?, updated_at = NOW()
             WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([
            $payment_method,
            $bank_name ?: null,
            $account_holder ?: null,
            $iban ?: null,
            $bic ?: null,
            $label ?: $payment_method,
            $id,
            $user_id
        ]);

    } elseif ($type === 'crypto') {
        $cryptocurrency = trim($_POST['cryptocurrency'] ?? '');
        if (empty($cryptocurrency)) {
            throw new Exception('Cryptocurrency type is required');
        }

        $wallet_address = trim($_POST['wallet_address'] ?? '');
        if (empty($wallet_address)) {
            throw new Exception('Wallet address is required');
        }

        if (strlen($wallet_address) < 26 || strlen($wallet_address) > 100) {
            throw new Exception('Invalid wallet address length');
        }

        if (!preg_match('/^[a-zA-Z0-9:]+$/', $wallet_address)) {
            throw new Exception('Wallet address contains invalid characters');
        }

        $network       = trim($_POST['network'] ?? '');
        $label         = trim($_POST['label'] ?? '');
        $payment_method = strtoupper($cryptocurrency);

        $stmt = $pdo->prepare(
            "UPDATE user_payment_methods
             SET payment_method = ?, cryptocurrency = ?, wallet_address = ?,
                 network = ?, label = ?, updated_at = NOW()
             WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([
            $payment_method,
            $cryptocurrency,
            $wallet_address,
            $network ?: null,
            $label ?: $cryptocurrency,
            $id,
            $user_id
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Payment method updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
