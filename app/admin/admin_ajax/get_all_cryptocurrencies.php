<?php
/**
 * Get All Cryptocurrencies with Networks (Admin)
 */

require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Fetch all cryptocurrencies (including inactive)
    $sql = "SELECT * FROM cryptocurrencies";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $cryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch networks for each cryptocurrency
    foreach ($cryptos as &$crypto) {
        $networkSql = "SELECT * FROM crypto_networks WHERE crypto_id = ?";
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
        'message' => 'Error: ' . $e->getMessage()
    ]);
}