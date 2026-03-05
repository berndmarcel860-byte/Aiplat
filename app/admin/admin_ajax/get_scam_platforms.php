<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    $query = "
        SELECT 
            sp.id,
            sp.name,
            sp.url,
            sp.type,
            sp.total_reported_loss,
            sp.total_recovered,
            sp.is_active,
            sp.created_at,
            CONCAT(a.first_name, ' ', a.last_name) as created_by_name,
            COUNT(c.id) as case_count
        FROM scam_platforms sp
        LEFT JOIN admins a ON sp.created_by = a.id
        LEFT JOIN cases c ON sp.id = c.platform_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($search) {
        $query .= " AND (sp.name LIKE ? OR sp.url LIKE ? OR sp.type LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    $query .= " GROUP BY sp.id";
    
    $countQuery = "SELECT COUNT(DISTINCT sp.id) as total FROM scam_platforms sp WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (sp.name LIKE ? OR sp.url LIKE ? OR sp.type LIKE ?)";
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($search ? [$searchTerm, $searchTerm, $searchTerm] : []);
    $totalRecords = $stmt->fetchColumn();
    
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDirection = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = ['sp.id', 'sp.name', 'sp.type', 'sp.total_reported_loss', 'sp.total_recovered', 'sp.created_at'];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
    } else {
        $query .= " ORDER BY sp.created_at DESC";
    }
    
    $query .= " LIMIT ?, ?";
    $params[] = $start;
    $params[] = $length;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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