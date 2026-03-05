<?php
/**
 * Get Filtered Users
 * Fetch users based on multiple filter criteria for bulk notifications
 */
require_once '../admin_session.php';

header('Content-Type: application/json');

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

try {
    // Get filters
    $filters = $_POST['filters'] ?? [];
    
    // Base query
    $query = "
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.balance,
            u.status,
            u.last_login,
            u.is_verified as email_verified,
            COALESCE(uo.completed, 0) as onboarding_complete,
            COALESCE((SELECT status FROM kyc_verification_requests WHERE user_id = u.id ORDER BY id DESC LIMIT 1), 'none') as kyc_status,
            DATEDIFF(NOW(), u.last_login) as days_inactive
        FROM users u
        LEFT JOIN user_onboarding uo ON u.id = uo.user_id
        WHERE 1=1
    ";
    
    $params = [];
    $whereConditions = [];
    
    // Apply KYC filter
    if (!empty($filters['kyc'])) {
        switch ($filters['kyc']) {
            case 'no_kyc':
                $whereConditions[] = "NOT EXISTS (SELECT 1 FROM kyc_verification_requests WHERE user_id = u.id)";
                break;
            case 'pending_kyc':
                $whereConditions[] = "EXISTS (SELECT 1 FROM kyc_verification_requests WHERE user_id = u.id AND status = 'pending')";
                break;
            case 'rejected_kyc':
                $whereConditions[] = "EXISTS (SELECT 1 FROM kyc_verification_requests WHERE user_id = u.id AND status = 'rejected')";
                break;
            case 'approved_kyc':
                $whereConditions[] = "EXISTS (SELECT 1 FROM kyc_verification_requests WHERE user_id = u.id AND status = 'approved')";
                break;
        }
    }
    
    // Apply Login filter
    if (!empty($filters['login'])) {
        switch ($filters['login']) {
            case 'never_logged_in':
                $whereConditions[] = "u.last_login IS NULL";
                break;
            case 'inactive_7':
                $whereConditions[] = "u.last_login < DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'inactive_14':
                $whereConditions[] = "u.last_login < DATE_SUB(NOW(), INTERVAL 14 DAY)";
                break;
            case 'inactive_30':
                $whereConditions[] = "u.last_login < DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'inactive_60':
                $whereConditions[] = "u.last_login < DATE_SUB(NOW(), INTERVAL 60 DAY)";
                break;
            case 'inactive_90':
                $whereConditions[] = "u.last_login < DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
        }
    }
    
    // Apply Balance filter
    if (!empty($filters['balance'])) {
        switch ($filters['balance']) {
            case 'has_balance':
                $whereConditions[] = "u.balance > 0";
                break;
            case 'high_balance':
                $whereConditions[] = "u.balance > 100";
                break;
            case 'very_high_balance':
                $whereConditions[] = "u.balance > 500";
                break;
            case 'no_balance':
                $whereConditions[] = "u.balance = 0";
                break;
        }
    }
    
    // Apply Onboarding filter
    if (!empty($filters['onboarding'])) {
        switch ($filters['onboarding']) {
            case 'incomplete_onboarding':
                $whereConditions[] = "COALESCE(uo.completed, 0) = 0";
                break;
            case 'complete_onboarding':
                $whereConditions[] = "uo.completed = 1";
                break;
        }
    }
    
    // Apply Status filter
    if (!empty($filters['status'])) {
        $whereConditions[] = "u.status = ?";
        $params[] = $filters['status'];
    }
    
    // Apply Email Verified filter
    if (!empty($filters['email_verified'])) {
        switch ($filters['email_verified']) {
            case 'verified':
                $whereConditions[] = "u.is_verified = 1";
                break;
            case 'not_verified':
                $whereConditions[] = "u.is_verified = 0";
                break;
        }
    }
    
    // Add where conditions to query
    if (!empty($whereConditions)) {
        $query .= " AND " . implode(" AND ", $whereConditions);
    }
    
    // Get total count before filtering
    $countQuery = "SELECT COUNT(*) as total FROM users u LEFT JOIN user_onboarding uo ON u.id = uo.user_id WHERE 1=1";
    if (!empty($whereConditions)) {
        $countQuery .= " AND " . implode(" AND ", $whereConditions);
    }
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Apply search
    $searchValue = $_POST['search']['value'] ?? '';
    if (!empty($searchValue)) {
        $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
        $searchParam = '%' . $searchValue . '%';
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    }
    
    // Get filtered count
    $filteredCountStmt = $pdo->prepare($countQuery . (empty($searchValue) ? "" : " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)"));
    $filteredCountStmt->execute(empty($searchValue) ? $params : array_merge($params, [$searchParam, $searchParam, $searchParam]));
    $filteredRecords = $filteredCountStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Apply ordering
    $orderColumn = $_POST['order'][0]['column'] ?? 1;
    $orderDir = $_POST['order'][0]['dir'] ?? 'DESC';
    $columns = ['id', 'id', 'first_name', 'email', 'kyc_status', 'last_login', 'balance', 'status', 'onboarding_complete'];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY " . $columns[$orderColumn] . " " . ($orderDir === 'asc' ? 'ASC' : 'DESC');
    } else {
        $query .= " ORDER BY u.id DESC";
    }
    
    // Apply pagination
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 25);
    $query .= " LIMIT ?, ?";
    $params[] = $start;
    $params[] = $length;
    
    // Execute query
    $stmt = $pdo->prepare($query);
    
    // Bind parameters with correct types
    for ($i = 0; $i < count($params); $i++) {
        if ($i >= count($params) - 2) { // Last two are pagination (integers)
            $stmt->bindValue($i + 1, $params[$i], PDO::PARAM_INT);
        } else {
            $stmt->bindValue($i + 1, $params[$i]);
        }
    }
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return DataTables response
    echo json_encode([
        'draw' => intval($_POST['draw'] ?? 0),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $users
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_filtered_users.php: " . $e->getMessage());
    echo json_encode([
        'draw' => intval($_POST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}