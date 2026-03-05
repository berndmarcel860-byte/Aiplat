<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Get current admin role and ID
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    $query = "
        SELECT 
            k.*, 
            u.first_name AS user_first_name, 
            u.last_name AS user_last_name
        FROM kyc_verification_requests k
        LEFT JOIN users u ON k.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Role-based filtering: regular admins only see their own users' KYC requests
    // Include NULL admin_id for backwards compatibility
    if ($currentAdminRole !== 'superadmin') {
        $query .= " AND (u.admin_id = ? OR u.admin_id IS NULL)";
        $params[] = $currentAdminId;
    }
    
    // Apply filters
    if (!empty($_GET['status'])) {
        $query .= " AND k.status = ?";
        $params[] = $_GET['status'];
    }
    
    if (!empty($_GET['document_type'])) {
        $query .= " AND k.document_type = ?";
        $params[] = $_GET['document_type'];
    }
    
    $query .= " ORDER BY k.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    $kycRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $kycRequests
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch KYC requests',
        'error' => $e->getMessage()
    ]);
}
?>