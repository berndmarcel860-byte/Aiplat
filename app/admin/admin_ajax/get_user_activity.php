<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Get current admin role
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    // For DataTables server-side processing
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Filters
    $user_email = isset($_POST['user_email']) ? $_POST['user_email'] : '';
    $page_url = isset($_POST['page_url']) ? $_POST['page_url'] : '';
    $http_method = isset($_POST['http_method']) ? $_POST['http_method'] : '';
    $ip_address = isset($_POST['ip_address']) ? $_POST['ip_address'] : '';
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    
    // Base query with role-based filtering
    $query = "
        SELECT 
            ual.id,
            ual.page_url,
            ual.http_method,
            ual.ip_address,
            ual.user_agent,
            ual.referrer,
            ual.created_at,
            u.first_name as user_first_name,
            u.last_name as user_last_name,
            u.email as user_email
        FROM user_activity_logs ual
        LEFT JOIN users u ON ual.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Filter by admin_id for regular admins (superadmin sees all)
    if ($currentAdminRole !== 'superadmin') {
        $query .= " AND u.admin_id = ?";
        $params[] = $currentAdminId;
    }
    
    // Add filters
    if ($user_email) {
        $query .= " AND u.email LIKE ?";
        $params[] = "%$user_email%";
    }
    
    if ($page_url) {
        $query .= " AND ual.page_url LIKE ?";
        $params[] = "%$page_url%";
    }
    
    if ($http_method) {
        $query .= " AND ual.http_method = ?";
        $params[] = $http_method;
    }
    
    if ($ip_address) {
        $query .= " AND ual.ip_address LIKE ?";
        $params[] = "%$ip_address%";
    }
    
    if ($start_date) {
        $query .= " AND DATE(ual.created_at) >= ?";
        $params[] = $start_date;
    }
    
    if ($end_date) {
        $query .= " AND DATE(ual.created_at) <= ?";
        $params[] = $end_date;
    }
    
    // Add search filter
    if ($search) {
        $query .= " AND (
            ual.page_url LIKE ? OR 
            u.email LIKE ? OR
            ual.ip_address LIKE ? OR
            ual.user_agent LIKE ?
        )";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Get total records count (simplified for performance)
    $countQuery = "SELECT COUNT(*) as total FROM user_activity_logs ual LEFT JOIN users u ON ual.user_id = u.id WHERE 1=1";
    $countParams = [];
    
    // Filter by admin_id for regular admins (superadmin sees all)
    if ($currentAdminRole !== 'superadmin') {
        $countQuery .= " AND u.admin_id = ?";
        $countParams[] = $currentAdminId;
    }
    
    // Apply same filters to count query
    if ($user_email) {
        $countQuery .= " AND u.email LIKE ?";
        $countParams[] = "%$user_email%";
    }
    
    if ($page_url) {
        $countQuery .= " AND ual.page_url LIKE ?";
        $countParams[] = "%$page_url%";
    }
    
    if ($http_method) {
        $countQuery .= " AND ual.http_method = ?";
        $countParams[] = $http_method;
    }
    
    if ($ip_address) {
        $countQuery .= " AND ual.ip_address LIKE ?";
        $countParams[] = "%$ip_address%";
    }
    
    if ($start_date) {
        $countQuery .= " AND DATE(ual.created_at) >= ?";
        $countParams[] = $start_date;
    }
    
    if ($end_date) {
        $countQuery .= " AND DATE(ual.created_at) <= ?";
        $countParams[] = $end_date;
    }
    
    if ($search) {
        $countQuery .= " AND (
            ual.page_url LIKE ? OR 
            u.email LIKE ? OR
            ual.ip_address LIKE ? OR
            ual.user_agent LIKE ?
        )";
        $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalRecords = $stmt->fetchColumn();
    
    // Add sorting
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDirection = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = [
        'ual.created_at',
        'user_email',
        'ual.page_url',
        'ual.http_method',
        'ual.ip_address',
        'ual.user_agent',
        'ual.referrer'
    ];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
    } else {
        $query .= " ORDER BY ual.created_at DESC";
    }
    
    $query .= " LIMIT ?, ?";
    $params[] = $start;
    $params[] = $length;
    
    // Get filtered data
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare response for DataTables
    $response = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $activities
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>