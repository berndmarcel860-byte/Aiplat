<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Get current admin role and ID
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    $query = "
        SELECT 
            n.id,
            n.title,
            n.message,
            n.type,
            n.is_read,
            n.created_at,
            CONCAT(a.first_name, ' ', a.last_name) as admin_name
        FROM admin_notifications n
        LEFT JOIN admins a ON n.admin_id = a.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Role-based filtering: regular admins only see their own notifications
    if ($currentAdminRole !== 'superadmin') {
        $query .= " AND n.admin_id = ?";
        $params[] = $currentAdminId;
    }
    
    if ($search) {
        $query .= " AND (n.title LIKE ? OR n.message LIKE ? OR a.first_name LIKE ? OR a.last_name LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $countQuery = "SELECT COUNT(*) as total FROM admin_notifications n LEFT JOIN admins a ON n.admin_id = a.id WHERE 1=1";
    if ($currentAdminRole !== 'superadmin') {
        $countQuery .= " AND n.admin_id = ?";
    }
    if ($search) {
        $countQuery .= " AND (n.title LIKE ? OR n.message LIKE ? OR a.first_name LIKE ? OR a.last_name LIKE ?)";
    }
    
    $countParams = [];
    if ($currentAdminRole !== 'superadmin') {
        $countParams[] = $currentAdminId;
    }
    if ($search) {
        $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalRecords = $stmt->fetchColumn();
    
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDirection = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = ['n.id', 'admin_name', 'n.title', 'n.type', 'n.is_read', 'n.created_at'];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
    } else {
        $query .= " ORDER BY n.created_at DESC";
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