<?php
/**
 * Get Pending Wallets - Admin Endpoint
 * Fetches wallets by verification status for admin review
 */

require_once '../../config.php';
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Get filter parameters
    $status = isset($_GET['status']) ? $_GET['status'] : 'pending';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Validate status
    $valid_statuses = ['pending', 'verifying', 'verified', 'failed'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status filter');
    }
    
    // Build query
    $query = "SELECT upm.id, upm.user_id, upm.cryptocurrency, upm.network, 
                     upm.wallet_address, upm.verification_status, 
                     upm.verification_amount, upm.verification_address,
                     upm.verification_txid, upm.verification_requested_at,
                     upm.verified_at, upm.verification_notes, upm.created_at,
                     u.email as username, u.email
              FROM user_payment_methods upm
              JOIN users u ON upm.user_id = u.id
              WHERE upm.type = 'crypto' AND upm.verification_status = ?";
    
    $params = [$status];
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (u.email LIKE ? OR upm.cryptocurrency LIKE ? OR upm.wallet_address LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $query .= " ORDER BY upm.created_at DESC";
    
    // Execute query using PDO
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $wallets = [];
    foreach ($results as $row) {
        // Mask wallet address for display (show first 6 and last 6 characters)
        $masked_address = strlen($row['wallet_address']) > 12 
            ? substr($row['wallet_address'], 0, 6) . '...' . substr($row['wallet_address'], -6)
            : $row['wallet_address'];
        
        $wallets[] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'email' => $row['email'],
            'cryptocurrency' => $row['cryptocurrency'],
            'network' => $row['network'],
            'wallet_address' => $row['wallet_address'],
            'wallet_address_masked' => $masked_address,
            'verification_status' => $row['verification_status'],
            'verification_amount' => $row['verification_amount'],
            'verification_address' => $row['verification_address'],
            'verification_txid' => $row['verification_txid'],
            'verification_requested_at' => $row['verification_requested_at'],
            'verified_at' => $row['verified_at'],
            'verification_notes' => $row['verification_notes'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'status' => $status,
        'count' => count($wallets),
        'wallets' => $wallets
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}