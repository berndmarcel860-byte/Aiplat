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
    $status_filter = isset($_POST['status_filter']) ? $_POST['status_filter'] : '';
    
    $query = "
        SELECT 
            t.id,
            t.ticket_number,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            u.email as user_email,
            t.subject,
            t.status,
            t.priority,
            t.category,
            t.last_reply_at,
            t.created_at,
            COUNT(tr.id) as reply_count
        FROM support_tickets t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN ticket_replies tr ON t.id = tr.ticket_id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Role-based filtering: regular admins only see tickets from their own users
    if ($currentAdminRole !== 'superadmin') {
        $query .= " AND u.admin_id = ?";
        $params[] = $currentAdminId;
    }
    
    if ($search) {
        $query .= " AND (t.ticket_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR t.subject LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($status_filter) {
        $query .= " AND t.status = ?";
        $params[] = $status_filter;
    }
    
    $query .= " GROUP BY t.id";
    
    $countQuery = "SELECT COUNT(DISTINCT t.id) as total FROM support_tickets t LEFT JOIN users u ON t.user_id = u.id WHERE 1=1";
    if ($currentAdminRole !== 'superadmin') {
        $countQuery .= " AND u.admin_id = ?";
    }
    if ($search) {
        $countQuery .= " AND (t.ticket_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR t.subject LIKE ?)";
    }
    if ($status_filter) {
        $countQuery .= " AND t.status = ?";
    }
    
    $countParams = [];
    if ($currentAdminRole !== 'superadmin') {
        $countParams[] = $currentAdminId;
    }
    if ($search) {
        $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    if ($status_filter) {
        $countParams[] = $status_filter;
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalRecords = $stmt->fetchColumn();
    
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDirection = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = ['t.id', 't.ticket_number', 'user_name', 't.subject', 't.priority', 't.status', 't.category', 't.last_reply_at', 't.created_at'];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
    } else {
        $query .= " ORDER BY t.created_at DESC";
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