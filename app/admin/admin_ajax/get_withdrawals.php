<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Get current admin role and ID
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    $query = "
        SELECT 
            w.*, 
            u.first_name AS user_first_name, 
            u.last_name AS user_last_name,
            pm.method_name
        FROM withdrawals w
        LEFT JOIN users u ON w.user_id = u.id
        LEFT JOIN payment_methods pm ON w.method_code = pm.method_code
        WHERE 1=1
    ";
    
    $params = [];
    
    // Role-based filtering: regular admins only see their own users' withdrawals
    // Include NULL admin_id for backwards compatibility
    if ($currentAdminRole !== 'superadmin') {
        $query .= " AND (u.admin_id = ? OR u.admin_id IS NULL)";
        $params[] = $currentAdminId;
    }
    
    // Apply filters
    if (!empty($_GET['status'])) {
        $query .= " AND w.status = ?";
        $params[] = $_GET['status'];
    }
    
    if (!empty($_GET['method_code'])) {
        $query .= " AND w.method_code = ?";
        $params[] = $_GET['method_code'];
    }
    
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $query .= " AND w.created_at BETWEEN ? AND ?";
        $params[] = $_GET['start_date'];
        $params[] = $_GET['end_date'];
    }
    
    $query .= " ORDER BY w.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $withdrawals
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch withdrawals',
        'error' => $e->getMessage()
    ]);
}
?>