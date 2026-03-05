<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // For DataTables server-side processing
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Base query - MODIFY TABLE NAME AS NEEDED
    $query = "
        SELECT 
            id,
            name,
            status,
            created_at
        FROM payment_methods
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add search filter
    if ($search) {
        $query .= " AND (name LIKE ? OR status LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm]);
    }
    
    // Get total records count
    $countQuery = "SELECT COUNT(*) as total FROM payment_methods WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (name LIKE ? OR status LIKE ?)";
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($search ? [$searchTerm, $searchTerm] : []);
    $totalRecords = $stmt->fetchColumn();
    
    // Add sorting
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDirection = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = ['id', 'name', 'status', 'created_at', 'id'];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
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