<?php
// admin_ajax/get_users.php
require_once '../../config.php';
require_once '../admin_session.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'draw' => intval($_POST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Unauthorized'
    ]);
    exit();
}

$currentAdminId = (int)$_SESSION['admin_id'];
$currentAdminRole = $_SESSION['admin_role'] ?? 'admin';

$columns = ['id', 'first_name', 'last_name', 'email', 'status', 'balance', 'created_at', 'last_login'];

// Login filter support
$loginFilter = $_POST['login_filter'] ?? 'all';

// Role-based filtering: superadmin sees all users, admin sees only their own
if ($currentAdminRole === 'superadmin') {
    // Superadmin: see ALL users (no admin_id filter)
    $query = "SELECT u.id, u.first_name, u.last_name, u.email, u.status, u.balance, u.created_at, u.last_login, 
              COALESCE((SELECT status FROM kyc_verification_requests WHERE user_id = u.id ORDER BY id DESC LIMIT 1), 'none') as kyc_status 
              FROM users u WHERE u.status != :excluded_status";
    $params = [
        'excluded_status' => 'suspended'
    ];
} else {
    // Admin: see only their own users (filtered by admin_id)
    $query = "SELECT u.id, u.first_name, u.last_name, u.email, u.status, u.balance, u.created_at, u.last_login,
              COALESCE((SELECT status FROM kyc_verification_requests WHERE user_id = u.id ORDER BY id DESC LIMIT 1), 'none') as kyc_status
              FROM users u WHERE u.status != :excluded_status AND u.admin_id = :admin_id";
    $params = [
        'excluded_status' => 'suspended',
        'admin_id' => $currentAdminId
    ];
}

// Apply login filter
if ($loginFilter !== 'all') {
    if ($loginFilter === 'never') {
        $query .= " AND u.last_login IS NULL";
    } else {
        $days = intval($loginFilter);
        $query .= " AND (u.last_login IS NULL OR u.last_login < DATE_SUB(NOW(), INTERVAL :filter_days DAY))";
        $params['filter_days'] = $days;
    }
}

// Search filter
$searchValue = '';
if (isset($_POST['search']['value']) && !empty($_POST['search']['value'])) {
    $searchValue = $_POST['search']['value'];
    $query .= " AND (u.first_name LIKE :search1 
                OR u.last_name LIKE :search2 
                OR u.email LIKE :search3)";
    $params['search1'] = '%' . $searchValue . '%';
    $params['search2'] = '%' . $searchValue . '%';
    $params['search3'] = '%' . $searchValue . '%';
}

// Ordering - whitelist approach with strict validation
$allowedColumns = ['id', 'first_name', 'last_name', 'email', 'status', 'balance', 'created_at', 'last_login'];
if (isset($_POST['order'])) {
    $columnIndex = intval($_POST['order'][0]['column']);
    if (isset($columns[$columnIndex]) && in_array($columns[$columnIndex], $allowedColumns, true)) {
        $column = $columns[$columnIndex];
        $dir = strtoupper($_POST['order'][0]['dir']) === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY u." . $column . " " . $dir;
    } else {
        $query .= " ORDER BY u.id DESC";
    }
} else {
    $query .= " ORDER BY u.id DESC";
}

// Pagination - sanitize integers
if (isset($_POST['length']) && $_POST['length'] != -1) {
    $start = intval($_POST['start']);
    $length = intval($_POST['length']);
    $query .= " LIMIT :start, :length";
    $params['start'] = $start;
    $params['length'] = $length;
}

$stmt = $pdo->prepare($query);

// Bind pagination parameters separately as integers
if (isset($params['start']) && isset($params['length'])) {
    $stmt->bindValue(':start', $params['start'], PDO::PARAM_INT);
    $stmt->bindValue(':length', $params['length'], PDO::PARAM_INT);
    unset($params['start'], $params['length']);
}

// Bind other parameters
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}

$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total records - Role-based: superadmin sees all, admin sees only their own
if ($currentAdminRole === 'superadmin') {
    $totalRecordsStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status != 'suspended'");
    $totalRecordsStmt->execute();
} else {
    $totalRecordsStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status != 'suspended' AND admin_id = ?");
    $totalRecordsStmt->execute([$currentAdminId]);
}
$totalRecords = $totalRecordsStmt->fetchColumn();

// Calculate filtered total if search is applied
if (!empty($searchValue)) {
    if ($currentAdminRole === 'superadmin') {
        $countQuery = "SELECT COUNT(*) FROM users WHERE status != :excluded_status
                       AND (first_name LIKE :search1 OR last_name LIKE :search2 OR email LIKE :search3)";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute([
            'excluded_status' => 'suspended',
            'search1' => '%' . $searchValue . '%',
            'search2' => '%' . $searchValue . '%',
            'search3' => '%' . $searchValue . '%'
        ]);
    } else {
        $countQuery = "SELECT COUNT(*) FROM users WHERE status != :excluded_status AND admin_id = :admin_id
                       AND (first_name LIKE :search1 OR last_name LIKE :search2 OR email LIKE :search3)";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute([
            'excluded_status' => 'suspended',
            'admin_id' => $currentAdminId,
            'search1' => '%' . $searchValue . '%',
            'search2' => '%' . $searchValue . '%',
            'search3' => '%' . $searchValue . '%'
        ]);
    }
    $totalFiltered = $countStmt->fetchColumn();
} else {
    $totalFiltered = $totalRecords;
}

$data = [];
foreach ($result as $row) {
    $data[] = $row;
}

echo json_encode([
    'draw' => intval($_POST['draw']),
    'recordsTotal' => intval($totalRecords),
    'recordsFiltered' => intval($totalFiltered),
    'data' => $data
]);