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
            e.id,
            e.recipient,
            e.subject,
            e.status,
            e.sent_at,
            e.opened_at,
            t.template_key
        FROM email_logs e
        LEFT JOIN email_templates t ON e.template_id = t.id
        LEFT JOIN users u ON e.recipient = u.email
        WHERE 1=1
    ";
    
    $params = [];
    
    // Role-based filtering: regular admins only see emails sent to their own users
    if ($currentAdminRole !== 'superadmin') {
        $query .= " AND u.admin_id = ?";
        $params[] = $currentAdminId;
    }
    
    if ($search) {
        $query .= " AND (e.recipient LIKE ? OR e.subject LIKE ? OR e.status LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    $countQuery = "SELECT COUNT(*) as total FROM email_logs e LEFT JOIN email_templates t ON e.template_id = t.id LEFT JOIN users u ON e.recipient = u.email WHERE 1=1";
    if ($currentAdminRole !== 'superadmin') {
        $countQuery .= " AND u.admin_id = ?";
    }
    if ($search) {
        $countQuery .= " AND (e.recipient LIKE ? OR e.subject LIKE ? OR e.status LIKE ?)";
    }
    
    $countParams = [];
    if ($currentAdminRole !== 'superadmin') {
        $countParams[] = $currentAdminId;
    }
    if ($search) {
        $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalRecords = $stmt->fetchColumn();
    
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDirection = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = ['e.id', 'e.recipient', 'e.subject', 't.template_key', 'e.status', 'e.sent_at', 'e.opened_at'];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
    } else {
        $query .= " ORDER BY e.sent_at DESC";
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