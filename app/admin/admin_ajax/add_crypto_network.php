<?php
/**
 * Add New Network to Cryptocurrency (Admin)
 */

session_start();
require_once '../../config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $crypto_id = intval($_POST['crypto_id'] ?? 0);
    $network_name = trim($_POST['network_name'] ?? '');
    $network_type = trim($_POST['network_type'] ?? '');
    $chain_id = trim($_POST['chain_id'] ?? '');
    $explorer_url = trim($_POST['explorer_url'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate required fields
    if ($crypto_id <= 0) {
        throw new Exception('Invalid cryptocurrency');
    }
    
    if (empty($network_name) || empty($network_type)) {
        throw new Exception('Network name and type are required');
    }
    
    // Verify cryptocurrency exists
    $checkStmt = $pdo->prepare("SELECT id FROM cryptocurrencies WHERE id = ?");
    $checkStmt->execute([$crypto_id]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Cryptocurrency not found');
    }
    
    // Insert new network
    $sql = "INSERT INTO crypto_networks (crypto_id, network_name, network_type, chain_id, explorer_url, is_active, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$crypto_id, $network_name, $network_type, $chain_id, $explorer_url, $is_active, $sort_order]);
    
    $network_id = $pdo->lastInsertId();
    
    // Log action
    $logSql = "INSERT INTO audit_logs (admin_id, action, entity, entity_id, ip_address) 
               VALUES (?, 'create', 'crypto_network', ?, ?)";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([$_SESSION['admin_id'], $network_id, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Network added successfully',
        'network_id' => $network_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}