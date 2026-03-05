<?php
require_once '../admin_session.php';

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : null;
$priority = isset($_GET['priority']) ? $_GET['priority'] : null;
$category = isset($_GET['category']) ? $_GET['category'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

try {
    // Base query
    $query = "
        SELECT 
            t.id, t.ticket_number, t.subject, t.status, t.priority, t.category,
            t.created_at, t.last_reply_at,
            u.id as user_id, u.first_name as user_first_name, u.last_name as user_last_name
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add filters
    if ($status) {
        $query .= " AND t.status = ?";
        $params[] = $status;
    }
    
    if ($priority) {
        $query .= " AND t.priority = ?";
        $params[] = $priority;
    }
    
    if ($category) {
        $query .= " AND t.category = ?";
        $params[] = $category;
    }
    
    if ($start_date) {
        $query .= " AND DATE(t.created_at) >= ?";
        $params[] = $start_date;
    }
    
    if ($end_date) {
        $query .= " AND DATE(t.created_at) <= ?";
        $params[] = $end_date;
    }
    
    // For DataTables server-side processing
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Add search filter
    if ($search) {
        $query .= " AND (
            t.ticket_number LIKE ? OR 
            t.subject LIKE ? OR 
            u.first_name LIKE ? OR 
            u.last_name LIKE ?
        )";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Get total records count
    $countQuery = "SELECT COUNT(*) as total FROM ($query) as subquery";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    
    // Add sorting
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 5; // Default to created_at
    $orderDirection = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = [
        't.ticket_number',
        'u.first_name',
        't.subject',
        't.status',
        't.priority',
        't.created_at',
        't.last_reply_at',
        't.id' // actions column
    ];
    
    $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
    $query .= " LIMIT ?, ?";
    $params[] = $start;
    $params[] = $length;
    
    // Get filtered data
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare response for DataTables
    $response = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $tickets
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}