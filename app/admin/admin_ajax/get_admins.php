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
            id,
            CONCAT(first_name, ' ', last_name) as name,
            email,
            role,
            status,
            created_at
        FROM admins
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add search filter
    if ($search) {
        $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR role LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Get total records count
    $countQuery = "SELECT COUNT(*) as total FROM admins WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR role LIKE ?)";
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($search ? [$searchTerm, $searchTerm, $searchTerm, $searchTerm] : []);
    $totalRecords = $stmt->fetchColumn();
    
    // Add sorting
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDirection = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = ['id', 'name', 'email', 'role', 'status', 'created_at'];
    
    if (isset($columns[$orderColumn])) {
        if ($orderColumn == 1) { // Name column
            $query .= " ORDER BY first_name $orderDirection, last_name $orderDirection";
        } else {
            $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
        }
    } else {
        $query .= " ORDER BY created_at DESC";
    }
    
    $query .= " LIMIT ?, ?";
    $params[] = $start;
    $params[] = $length;
    
    // Get filtered data
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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