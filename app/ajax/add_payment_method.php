<?php
/**
 * Add Payment Method (Fiat or Crypto)
 * Allows users to add new payment methods including bank accounts and crypto wallets
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

// Get POST data
$type = $_POST['type'] ?? ''; // 'fiat' or 'crypto'
$payment_method = $_POST['payment_method'] ?? '';
$label = $_POST['label'] ?? '';
$is_default = isset($_POST['is_default']) ? 1 : 0;

try {
    // Validate required fields
    if (empty($type) || !in_array($type, ['fiat', 'crypto'])) {
        throw new Exception('Invalid payment method type');
    }

    if (empty($payment_method)) {
        throw new Exception('Payment method name is required');
    }

    // Prepare data array
    $data = [
        'user_id' => $user_id,
        'type' => $type,
        'payment_method' => $payment_method,
        'label' => !empty($label) ? $label : $payment_method,
        'is_default' => $is_default,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ];

    if ($type === 'fiat') {
        // Fiat payment method details
        $data['account_holder'] = $_POST['account_holder'] ?? null;
        $data['bank_name'] = $_POST['bank_name'] ?? null;
        $data['iban'] = $_POST['iban'] ?? null;
        $data['bic'] = $_POST['bic'] ?? null;
        $data['account_number'] = $_POST['account_number'] ?? null;
        $data['routing_number'] = $_POST['routing_number'] ?? null;
        $data['sort_code'] = $_POST['sort_code'] ?? null;

        // Validate IBAN if provided
        if (!empty($data['iban'])) {
            $iban = preg_replace('/\s+/', '', strtoupper($data['iban']));
            if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', $iban)) {
                throw new Exception('Invalid IBAN format');
            }
            $data['iban'] = $iban;
        }

        // Validate at least one account identifier is provided
        if (empty($data['iban']) && empty($data['account_number'])) {
            throw new Exception('Please provide either IBAN or account number');
        }

    } elseif ($type === 'crypto') {
        // Cryptocurrency wallet details
        $data['wallet_address'] = $_POST['wallet_address'] ?? null;
        $data['cryptocurrency'] = $_POST['cryptocurrency'] ?? null;
        $data['network'] = $_POST['network'] ?? null;

        // Validate required crypto fields
        if (empty($data['wallet_address'])) {
            throw new Exception('Wallet address is required');
        }

        if (empty($data['cryptocurrency'])) {
            throw new Exception('Cryptocurrency type is required');
        }

        // Basic wallet address validation (alphanumeric, 26-42 chars for most cryptos)
        $wallet = trim($data['wallet_address']);
        if (strlen($wallet) < 26 || strlen($wallet) > 100) {
            throw new Exception('Invalid wallet address length');
        }

        if (!preg_match('/^[a-zA-Z0-9]+$/', $wallet)) {
            throw new Exception('Wallet address contains invalid characters');
        }

        $data['wallet_address'] = $wallet;
        $data['cryptocurrency'] = strtoupper($data['cryptocurrency']);
    }

    // If setting as default, unset other defaults for this user
    if ($is_default == 1) {
        $stmt = $pdo->prepare("UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }

    // Insert new payment method
    $fields = array_keys($data);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO user_payment_methods (" . implode(', ', $fields) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));

    $payment_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Payment method added successfully',
        'payment_id' => $payment_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}