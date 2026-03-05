<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Get current admin role and ID
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    // Build query with role-based filtering
    $query = "SELECT id, first_name, last_name, email FROM users WHERE status != 'suspended'";
    $params = [];
    
    // Regular admins only see their own users (including NULL for backwards compatibility)
    if ($currentAdminRole !== 'superadmin') {
        $query .= " AND (admin_id = ? OR admin_id IS NULL)";
        $params[] = $currentAdminId;
    }
    
    $query .= " ORDER BY first_name, last_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}