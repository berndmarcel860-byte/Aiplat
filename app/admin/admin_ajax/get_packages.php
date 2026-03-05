<?php
/**
 * Get Packages List
 * Returns all available packages for dropdown selections
 */

require_once '../admin_session.php';

header('Content-Type: application/json');

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT id, name, description, price, duration_days, features, status
        FROM packages
        WHERE status = 'active'
        ORDER BY price ASC
    ");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $packages
    ]);
    
} catch (PDOException $e) {
    error_log("Get packages error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'data' => []
    ]);
}