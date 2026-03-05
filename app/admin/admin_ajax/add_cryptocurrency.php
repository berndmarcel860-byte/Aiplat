<?php
/**
 * Add New Cryptocurrency (Admin)
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
    $symbol = strtoupper(trim($_POST['symbol'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate required fields
    if (empty($symbol) || empty($name)) {
        throw new Exception('Symbol and name are required');
    }
    
    // Check if symbol already exists
    $checkStmt = $pdo->prepare("SELECT id FROM cryptocurrencies WHERE symbol = ?");
    $checkStmt->execute([$symbol]);
    if ($checkStmt->fetch()) {
        throw new Exception('A cryptocurrency with this symbol already exists');
    }
    
    // Insert new cryptocurrency
    $sql = "INSERT INTO cryptocurrencies (symbol, name, icon, description, is_active, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$symbol, $name, $icon, $description, $is_active, $sort_order]);
    
    $crypto_id = $pdo->lastInsertId();
    
    // Log action
    $logSql = "INSERT INTO audit_logs (admin_id, action, entity, entity_id, ip_address) 
               VALUES (?, 'create', 'cryptocurrency', ?, ?)";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([$_SESSION['admin_id'], $crypto_id, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cryptocurrency added successfully',
        'crypto_id' => $crypto_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}