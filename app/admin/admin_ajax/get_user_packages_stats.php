<?php
// admin_ajax/get_user_packages_stats.php
// Get statistics for user packages dashboard

require_once '../admin_session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Get current admin role
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    // Build query with role-based filtering
    $query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN up.status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN up.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN up.status = 'expired' THEN 1 ELSE 0 END) as expired,
            SUM(CASE WHEN up.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM user_packages up
        JOIN users u ON up.user_id = u.id
        WHERE 1=1
    ";
    
    // Filter by admin_id for regular admins (superadmin sees all)
    if ($currentAdminRole !== 'superadmin') {
        $query .= " AND u.admin_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$currentAdminId]);
    } else {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
    }
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total' => (int)$stats['total'],
            'active' => (int)$stats['active'],
            'pending' => (int)$stats['pending'],
            'expired' => (int)$stats['expired'],
            'cancelled' => (int)$stats['cancelled']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Get user packages stats error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}