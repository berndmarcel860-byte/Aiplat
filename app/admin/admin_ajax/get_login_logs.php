<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // For DataTables server-side processing
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Base query - Updated to match actual schema
    $query = "
        SELECT 
            l.id,
            l.email,
            CONCAT(a.first_name, ' ', a.last_name) as admin_name,
            l.ip_address,
            l.success,
            l.attempted_at as created_at,
            CASE WHEN l.success = 1 THEN 'success' ELSE 'failed' END as status
        FROM admin_login_logs l
        LEFT JOIN admins a ON l.admin_id = a.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add search filter
    if ($search) {
        $query .= " AND (l.email LIKE ? OR a.first_name LIKE ? OR a.last_name LIKE ? OR l.ip_address LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Get total records count
    $countQuery = "SELECT COUNT(*) as total FROM admin_login_logs l LEFT JOIN admins a ON l.admin_id = a.id WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (l.email LIKE ? OR a.first_name LIKE ? OR a.last_name LIKE ? OR l.ip_address LIKE ?)";
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($search ? [$searchTerm, $searchTerm, $searchTerm, $searchTerm] : []);
    $totalRecords = $stmt->fetchColumn();
    
    // Add sorting
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDirection = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = ['l.id', 'l.email', 'l.ip_address', 'l.success', 'l.attempted_at'];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
    } else {
        $query .= " ORDER BY l.attempted_at DESC";
    }
    
    $query .= " LIMIT ?, ?";
    $params[] = $start;
    $params[] = $length;
    
    // Get filtered data
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data for display
    foreach ($data as &$row) {
        $row['name'] = $row['admin_name'] ?: $row['email'];
    }
    
    // Prepare response for DataTables
    $response = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>