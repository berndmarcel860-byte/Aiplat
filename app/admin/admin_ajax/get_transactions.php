<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Get current admin role
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    $query = "
        SELECT 
            t.*, 
            u.first_name AS user_first_name, 
            u.last_name AS user_last_name,
            pm.method_name
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN payment_methods pm ON t.payment_method_id = pm.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Filter by admin_id for regular admins (superadmin sees all)
    if ($currentAdminRole !== 'superadmin') {
        $query .= " AND u.admin_id = ?";
        $params[] = $currentAdminId;
    }
    
    // Apply filters
    if (!empty($_GET['type'])) {
        $query .= " AND t.type = ?";
        $params[] = $_GET['type'];
    }
    
    if (!empty($_GET['status'])) {
        $query .= " AND t.status = ?";
        $params[] = $_GET['status'];
    }
    
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $query .= " AND t.created_at BETWEEN ? AND ?";
        $params[] = $_GET['start_date'];
        $params[] = $_GET['end_date'];
    }
    
    $query .= " ORDER BY t.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $transactions
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch transactions',
        'error' => $e->getMessage()
    ]);
}
?>