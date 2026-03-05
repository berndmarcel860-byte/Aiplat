<?php
/**
 * Get Available Cryptocurrencies and Networks
 * Returns active cryptocurrencies with their networks for user dropdown
 */

require_once '../config.php';
require_once '../session.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Fetch all active cryptocurrencies with their networks
    $sql = "SELECT 
                c.id, 
                c.symbol, 
                c.name, 
                c.icon,
                c.description
            FROM cryptocurrencies c
            WHERE c.is_active = 1
            ORDER BY c.sort_order ASC, c.name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $cryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch networks for each cryptocurrency
    foreach ($cryptos as &$crypto) {
        $networkSql = "SELECT 
                        id,
                        network_name,
                        network_type,
                        chain_id,
                        explorer_url
                    FROM crypto_networks
                    WHERE crypto_id = ? AND is_active = 1
                    ORDER BY sort_order ASC, network_name ASC";
        
        $networkStmt = $pdo->prepare($networkSql);
        $networkStmt->execute([$crypto['id']]);
        $crypto['networks'] = $networkStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'cryptocurrencies' => $cryptos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching cryptocurrencies: ' . $e->getMessage()
    ]);
}