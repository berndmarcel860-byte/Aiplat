<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Get current admin role and ID
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    // DataTables parameters
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDirection = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';
    
    // Column mapping for ordering
    $columns = [
        0 => 'c.case_number',
        1 => 'u.first_name',
        2 => 'p.name',
        3 => 'c.reported_amount',
        4 => 'recovered_amount',
        5 => 'c.status',
        6 => 'c.created_at'
    ];
    
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'c.created_at';
    
    // Base query
    $baseQuery = "
        SELECT 
            c.*, 
            u.first_name AS user_first_name, 
            u.last_name AS user_last_name,
            u.email AS user_email,
            a.first_name AS admin_first_name,
            a.last_name AS admin_last_name,
            p.name AS platform_name,
            (SELECT SUM(amount) FROM case_recovery_transactions WHERE case_id = c.id) AS recovered_amount
        FROM cases c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN admins a ON c.admin_id = a.id
        LEFT JOIN scam_platforms p ON c.platform_id = p.id
    ";
    
    $whereConditions = [];
    $params = [];
    
    // Filter by admin_id if not superadmin
    if ($currentAdminRole !== 'superadmin') {
        $whereConditions[] = "c.admin_id = ?";
        $params[] = $currentAdminId;
    }
    
    // Search functionality
    if (!empty($searchValue)) {
        $searchConditions = [
            "c.case_number LIKE ?",
            "u.first_name LIKE ?",
            "u.last_name LIKE ?",
            "u.email LIKE ?",
            "p.name LIKE ?",
            "c.status LIKE ?",
            "c.title LIKE ?"
        ];
        $whereConditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        $searchParam = "%{$searchValue}%";
        for ($i = 0; $i < count($searchConditions); $i++) {
            $params[] = $searchParam;
        }
    }
    
    // Build WHERE clause
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Count total records (without filtering)
    $totalQuery = "SELECT COUNT(*) as total FROM cases c";
    if ($currentAdminRole !== 'superadmin') {
        $totalQuery .= " WHERE c.admin_id = ?";
        $stmtTotal = $pdo->prepare($totalQuery);
        $stmtTotal->execute([$currentAdminId]);
    } else {
        $stmtTotal = $pdo->query($totalQuery);
    }
    $totalRecords = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Count filtered records
    $filteredQuery = "
        SELECT COUNT(*) as total 
        FROM cases c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN scam_platforms p ON c.platform_id = p.id
        {$whereClause}
    ";
    $stmtFiltered = $pdo->prepare($filteredQuery);
    $stmtFiltered->execute($params);
    $filteredRecords = $stmtFiltered->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get paginated data
    $dataQuery = $baseQuery . $whereClause . " ORDER BY {$orderColumn} {$orderDirection} LIMIT ? OFFSET ?";
    $params[] = $length;
    $params[] = $start;
    
    $stmt = $pdo->prepare($dataQuery);
    $stmt->execute($params);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return DataTables response
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => intval($totalRecords),
        'recordsFiltered' => intval($filteredRecords),
        'data' => $cases
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Failed to fetch cases: ' . $e->getMessage()
    ]);
}
?>