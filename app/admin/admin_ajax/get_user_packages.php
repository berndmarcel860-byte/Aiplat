<?php
// admin_ajax/get_user_packages.php
// Get user packages list for DataTables

require_once '../admin_session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // DataTables parameters
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Filters
    $statusFilter = isset($_POST['status']) ? $_POST['status'] : '';
    $packageFilter = isset($_POST['package_id']) ? (int)$_POST['package_id'] : 0;
    
    // Ordering
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDir = isset($_POST['order'][0]['dir']) && $_POST['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';
    
    $columns = ['up.id', 'u.first_name', 'p.name', 'p.price', 'up.start_date', 'up.end_date', 'up.status'];
    $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'up.id';
    
    // Get current admin role
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    // Check if tables exist first
    $tablesExist = true;
    try {
        $checkTables = $pdo->query("SHOW TABLES LIKE 'packages'");
        if ($checkTables->rowCount() == 0) {
            $tablesExist = false;
        }
        $checkTables = $pdo->query("SHOW TABLES LIKE 'user_packages'");
        if ($checkTables->rowCount() == 0) {
            $tablesExist = false;
        }
    } catch (PDOException $e) {
        $tablesExist = false;
    }
    
    if (!$tablesExist) {
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Required tables (packages, user_packages) do not exist in database'
        ]);
        exit();
    }
    
    // Base query with role-based filtering
    $baseQuery = "
        FROM user_packages up
        INNER JOIN users u ON up.user_id = u.id
        LEFT JOIN packages p ON up.package_id = p.id
        WHERE u.status != 'suspended'
    ";
    
    $params = [];
    
    // Filter by admin_id for regular admins (superadmin sees all)
    if ($currentAdminRole !== 'superadmin') {
        // Include users with matching admin_id OR NULL admin_id (for backwards compatibility)
        $baseQuery .= " AND (u.admin_id = ? OR u.admin_id IS NULL)";
        $params[] = $currentAdminId;
    }
    
    // Apply filters
    if ($statusFilter) {
        $baseQuery .= " AND up.status = ?";
        $params[] = $statusFilter;
    }
    
    if ($packageFilter > 0) {
        $baseQuery .= " AND up.package_id = ?";
        $params[] = $packageFilter;
    }
    
    // Search
    if ($search) {
        $baseQuery .= " AND (
            u.first_name LIKE ? OR 
            u.last_name LIKE ? OR 
            u.email LIKE ? OR 
            p.name LIKE ?
        )";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) " . $baseQuery;
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    
    // Get data
    $dataQuery = "
        SELECT 
            up.id,
            up.user_id,
            up.package_id,
            up.start_date,
            up.end_date,
            up.status,
            up.created_at,
            up.updated_at,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            u.email as user_email,
            p.name as package_name,
            p.price as package_price,
            p.duration_days
        $baseQuery
        ORDER BY $orderBy $orderDir
        LIMIT ?, ?
    ";
    
    $params[] = $start;
    $params[] = $length;
    
    $stmt = $pdo->prepare($dataQuery);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
    
} catch (PDOException $e) {
    error_log("Get user packages error: " . $e->getMessage());
    echo json_encode([
        'draw' => $draw ?? 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}