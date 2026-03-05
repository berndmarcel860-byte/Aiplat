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
            c.id,
            c.case_number as name,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            u.email as user_email,
            c.status,
            c.reported_amount,
            c.recovered_amount,
            CONCAT(a.first_name, ' ', a.last_name) as assigned_admin,
            c.created_at
        FROM cases c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN admins a ON c.assigned_to = a.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add search filter
    if ($search) {
        $query .= " AND (c.case_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR c.status LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Get total records count
    $countQuery = "SELECT COUNT(*) as total FROM cases c LEFT JOIN users u ON c.user_id = u.id WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (c.case_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR c.status LIKE ?)";
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($search ? [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm] : []);
    $totalRecords = $stmt->fetchColumn();
    
    // Add sorting
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDirection = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = ['c.id', 'c.case_number', 'c.status', 'c.created_at', 'c.id'];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
    } else {
        $query .= " ORDER BY c.created_at DESC";
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
        $row['amount_info'] = '$' . number_format($row['reported_amount'], 2) . ' / $' . number_format($row['recovered_amount'], 2);
        $row['user_info'] = $row['user_name'] . ' (' . $row['user_email'] . ')';
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