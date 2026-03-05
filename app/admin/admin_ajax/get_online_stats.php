<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Get current admin role
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    // Build JOIN clause for role-based filtering
    $joinClause = "";
    $whereClause = "";
    $params = [];
    
    if ($currentAdminRole !== 'superadmin') {
        $joinClause = "JOIN users u ON ou.user_id = u.id";
        $whereClause = "AND u.admin_id = ?";
        $params = [$currentAdminId];
    }
    
    // Total online (last 5 minutes)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ou.user_id) as total_online
        FROM online_users ou
        $joinClause
        WHERE ou.last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        $whereClause
    ");
    $stmt->execute($params);
    $totalOnline = $stmt->fetchColumn();
    
    // Active now (last 1 minute)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ou.user_id) as active_now
        FROM online_users ou
        $joinClause
        WHERE ou.last_activity >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        $whereClause
    ");
    $stmt->execute($params);
    $activeNow = $stmt->fetchColumn();
    
    // Mobile users
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ou.user_id) as mobile_users
        FROM online_users ou
        $joinClause
        WHERE ou.last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        AND ou.user_agent LIKE '%Mobile%'
        $whereClause
    ");
    $stmt->execute($params);
    $mobileUsers = $stmt->fetchColumn();
    
    // Desktop users
    $desktopUsers = $totalOnline - $mobileUsers;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_online' => $totalOnline,
            'active_now' => $activeNow,
            'mobile_users' => $mobileUsers,
            'desktop_users' => max(0, $desktopUsers)
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>