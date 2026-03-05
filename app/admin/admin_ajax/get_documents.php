<?php
require_once '../../config.php';


header('Content-Type: application/json');

try {
    // For DataTables server-side processing
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    $statusFilter = isset($_POST['status_filter']) ? $_POST['status_filter'] : 'all';
    
    // Base query with user join
    $query = "
        SELECT 
            ud.id,
            ud.user_id,
            ud.document_name,
            ud.document_type,
            ud.file_path,
            ud.file_size,
            ud.status,
            ud.description,
            ud.uploaded_at,
            CONCAT(u.first_name, ' ', u.last_name) as user_name
        FROM user_documents ud
        LEFT JOIN users u ON ud.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    $countParams = [];
    
    // Add status filter
    if ($statusFilter !== 'all') {
        $query .= " AND ud.status = ?";
        $params[] = $statusFilter;
        $countParams[] = $statusFilter;
    }
    
    // Add search filter
    if ($search) {
        $query .= " AND (ud.document_name LIKE ? OR ud.document_type LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Get total records count
    $countQuery = "SELECT COUNT(*) as total FROM user_documents ud LEFT JOIN users u ON ud.user_id = u.id WHERE 1=1";
    if ($statusFilter !== 'all') {
        $countQuery .= " AND ud.status = ?";
    }
    if ($search) {
        $countQuery .= " AND (ud.document_name LIKE ? OR ud.document_type LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalRecords = $stmt->fetchColumn();
    
    // Add sorting
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDirection = isset($_POST['order'][0]['dir']) && strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
    
    $columns = ['ud.id', 'user_name', 'ud.document_name', 'ud.document_type', 'ud.file_size', 'ud.status', 'ud.uploaded_at'];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
    } else {
        $query .= " ORDER BY ud.uploaded_at DESC";
    }
    
    // Add pagination
    $query .= " LIMIT ?, ?";
    $params[] = $start;
    $params[] = $length;
    
    // Get filtered data
    $stmt = $pdo->prepare($query);
    
    // Bind parameters properly
    foreach ($params as $i => $param) {
        if ($i >= count($params) - 2) {
            // Last two params are pagination (integers)
            $stmt->bindValue($i + 1, $param, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($i + 1, $param, PDO::PARAM_STR);
        }
    }
    
    $stmt->execute();
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