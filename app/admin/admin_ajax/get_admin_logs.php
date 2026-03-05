<?php
require_once '../../config.php';
require_once '../admin_session.php';

header('Content-Type: application/json');

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'draw' => intval($_POST['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Unauthorized'
    ]);
    exit();
}

$currentAdminId = (int)$_SESSION['admin_id'];

try {
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    $dateRange = isset($_POST['date_range']) ? $_POST['date_range'] : '7days';
    $logType = isset($_POST['log_type']) ? $_POST['log_type'] : 'all';
    $adminId = isset($_POST['admin_id']) ? (int)$_POST['admin_id'] : 0;
    
    // Base query - FILTER BY CURRENT ADMIN to show only their own logs
    $query = "
        SELECT 
            al.*,
            CONCAT(a.first_name, ' ', a.last_name) as admin_name
        FROM admin_logs al
        LEFT JOIN admins a ON al.admin_id = a.id
        WHERE al.admin_id = ?
    ";
    
    $params = [$currentAdminId];
    $countParams = [$currentAdminId];
    
    // Date range filter
    switch ($dateRange) {
        case 'today':
            $query .= " AND DATE(al.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $query .= " AND DATE(al.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case '7days':
            $query .= " AND al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case '30days':
            $query .= " AND al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
    
    // Log type filter
    if ($logType !== 'all') {
        $query .= " AND al.action LIKE ?";
        $params[] = "%$logType%";
        $countParams[] = "%$logType%";
    }
    
    // Search filter
    if ($search) {
        $query .= " AND (al.action LIKE ? OR al.entity_type LIKE ? OR al.ip_address LIKE ? OR CONCAT(a.first_name, ' ', a.last_name) LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Get total count - FILTERED BY CURRENT ADMIN
    $countQuery = "SELECT COUNT(*) FROM admin_logs al LEFT JOIN admins a ON al.admin_id = a.id WHERE al.admin_id = ?";
    
    // Apply same filters to count query
    switch ($dateRange) {
        case 'today':
            $countQuery .= " AND DATE(al.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $countQuery .= " AND DATE(al.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case '7days':
            $countQuery .= " AND al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case '30days':
            $countQuery .= " AND al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
    
    if ($logType !== 'all') {
        $countQuery .= " AND al.action LIKE ?";
    }
    
    if ($search) {
        $countQuery .= " AND (al.action LIKE ? OR al.entity_type LIKE ? OR al.ip_address LIKE ? OR CONCAT(a.first_name, ' ', a.last_name) LIKE ?)";
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalRecords = $stmt->fetchColumn();
    
    // Add sorting
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 1;
    $orderDirection = isset($_POST['order'][0]['dir']) && strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
    
    $columns = ['al.id', 'al.created_at', 'admin_name', 'al.action', 'al.entity_type', 'al.ip_address'];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
    } else {
        $query .= " ORDER BY al.created_at DESC";
    }
    
    // Pagination
    $query .= " LIMIT ?, ?";
    $params[] = $start;
    $params[] = $length;
    
    // Execute query
    $stmt = $pdo->prepare($query);
    
    foreach ($params as $i => $param) {
        if ($i >= count($params) - 2) {
            $stmt->bindValue($i + 1, $param, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($i + 1, $param, PDO::PARAM_STR);
        }
    }
    
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>