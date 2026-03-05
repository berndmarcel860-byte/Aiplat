<?php
/**
 * Get Payment Methods
 * Retrieves all payment methods for the logged-in user
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
    // Get all payment methods for this user
    $stmt = $pdo->prepare("
        SELECT 
            id,
            type,
            payment_method,
            label,
            account_holder,
            bank_name,
            iban,
            bic,
            account_number,
            routing_number,
            sort_code,
            wallet_address,
            cryptocurrency,
            network,
            is_default,
            is_verified,
            verification_date,
            last_used_at,
            status,
            verification_status,
            verification_amount,
            verification_address,
            verification_txid,
            verification_requested_at,
            verified_by,
            verified_at,
            verification_notes,
            created_at,
            updated_at
        FROM user_payment_methods
        WHERE user_id = ?
        ORDER BY is_default DESC, created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mask sensitive information for security
    foreach ($methods as &$method) {
        if (!empty($method['iban'])) {
            // Show only last 4 characters of IBAN
            $method['iban_masked'] = str_repeat('*', strlen($method['iban']) - 4) . substr($method['iban'], -4);
        }
        
        if (!empty($method['account_number'])) {
            // Show only last 4 digits of account number
            $method['account_number_masked'] = str_repeat('*', strlen($method['account_number']) - 4) . substr($method['account_number'], -4);
        }
        
        if (!empty($method['wallet_address'])) {
            // Show first 6 and last 6 characters of wallet address
            $addr = $method['wallet_address'];
            if (strlen($addr) > 12) {
                $method['wallet_address_masked'] = substr($addr, 0, 6) . '...' . substr($addr, -6);
            } else {
                $method['wallet_address_masked'] = $addr;
            }
        }
    }

    // Separate by type
    $fiat_methods = array_filter($methods, function($m) { return $m['type'] === 'fiat'; });
    $crypto_methods = array_filter($methods, function($m) { return $m['type'] === 'crypto'; });

    echo json_encode([
        'success' => true,
        'methods' => [
            'all' => array_values($methods),
            'fiat' => array_values($fiat_methods),
            'crypto' => array_values($crypto_methods)
        ],
        'counts' => [
            'total' => count($methods),
            'fiat' => count($fiat_methods),
            'crypto' => count($crypto_methods)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching payment methods: ' . $e->getMessage()
    ]);
}