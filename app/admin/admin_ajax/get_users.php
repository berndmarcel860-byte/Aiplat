<?php
// admin_ajax/get_users.php
require_once '../../config.php';
require_once '../admin_session.php';
require_once '../../database/balance_helpers.php';

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

$loginFilter = $_POST['login_filter'] ?? 'all';
$statusScope = strtolower(trim($_POST['status_scope'] ?? 'active'));
$allowedScopes = ['all', 'active', 'suspended', 'banned'];
if (!in_array($statusScope, $allowedScopes, true)) {
    $statusScope = 'active';
}

$baseWhere = [];
$baseParams = [];

if ($currentAdminRole !== 'superadmin') {
    $baseWhere[] = "u.admin_id = :admin_id";
    $baseParams['admin_id'] = $currentAdminId;
}

if ($statusScope !== 'all') {
    $baseWhere[] = "u.status = :status_scope";
    $baseParams['status_scope'] = $statusScope;
}

if ($loginFilter !== 'all') {
    if ($loginFilter === 'never') {
        $baseWhere[] = "u.last_login IS NULL";
    } else {
        $days = max(0, (int)$loginFilter);
        $baseWhere[] = "(u.last_login IS NULL OR u.last_login < DATE_SUB(NOW(), INTERVAL :filter_days DAY))";
        $baseParams['filter_days'] = $days;
    }
}

$dataWhere = $baseWhere;
$dataParams = $baseParams;

$searchValue = trim((string)($_POST['search']['value'] ?? ''));
if ($searchValue !== '') {
    $dataWhere[] = "(u.first_name LIKE :search1
                OR u.last_name LIKE :search2
                OR u.email LIKE :search3
                OR u.phone LIKE :search4
                OR u.country LIKE :search5)";
    $searchLike = '%' . $searchValue . '%';
    $dataParams['search1'] = $searchLike;
    $dataParams['search2'] = $searchLike;
    $dataParams['search3'] = $searchLike;
    $dataParams['search4'] = $searchLike;
    $dataParams['search5'] = $searchLike;
}

$topupBalanceSql = getUserTopupBalanceSql($pdo, 'u');

$selectQuery = "SELECT u.id, u.first_name, u.last_name, u.email, u.status, {$topupBalanceSql} AS topup_balance, u.created_at, u.last_login,
              u.phone, u.country,
              COALESCE((SELECT status FROM kyc_verification_requests WHERE user_id = u.id ORDER BY id DESC LIMIT 1), 'none') as kyc_status,
              COALESCE((SELECT verification_status FROM user_payment_methods WHERE user_id = u.id AND type = 'crypto' ORDER BY id DESC LIMIT 1), 'none') as wallet_status,
              (SELECT COUNT(*) FROM cases WHERE user_id = u.id) as cases_count,
              (SELECT COUNT(*) FROM support_tickets WHERE user_id = u.id) as tickets_count,
              COALESCE((SELECT completed FROM user_onboarding WHERE user_id = u.id ORDER BY id DESC LIMIT 1), 0) as onboarding_done
              FROM users u";

$query = $selectQuery;
if (!empty($dataWhere)) {
    $query .= " WHERE " . implode(" AND ", $dataWhere);
}

$orderableColumns = [
    0  => 'u.id',
    1  => 'u.first_name',
    2  => 'u.email',
    3  => 'u.phone',
    4  => 'u.country',
    5  => 'u.status',
    6  => 'kyc_status',
    7  => 'wallet_status',
    8  => 'onboarding_done',
    9  => 'cases_count',
    10 => 'tickets_count',
    11 => 'u.last_login',
    12 => $topupBalanceSql,
    13 => 'u.created_at'
];
$columnIndex = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
$orderColumn = $orderableColumns[$columnIndex] ?? 'u.id';
$orderDirection = strtoupper($_POST['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$query .= " ORDER BY {$orderColumn} {$orderDirection}";

$start = max(0, (int)($_POST['start'] ?? 0));
$length = (int)($_POST['length'] ?? 25);
if ($length !== -1) {
    $query .= " LIMIT :start, :length";
    $dataParams['start'] = $start;
    $dataParams['length'] = max(1, $length);
}

$stmt = $pdo->prepare($query);
foreach ($dataParams as $key => $value) {
    $paramType = in_array($key, ['admin_id', 'filter_days', 'start', 'length'], true) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue(':' . $key, $value, $paramType);
}
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countQueryBase = "SELECT COUNT(*) FROM users u";

$totalRecordsQuery = $countQueryBase;
if (!empty($baseWhere)) {
    $totalRecordsQuery .= " WHERE " . implode(" AND ", $baseWhere);
}
$totalRecordsStmt = $pdo->prepare($totalRecordsQuery);
foreach ($baseParams as $key => $value) {
    $paramType = in_array($key, ['admin_id', 'filter_days'], true) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $totalRecordsStmt->bindValue(':' . $key, $value, $paramType);
}
$totalRecordsStmt->execute();
$totalRecords = $totalRecordsStmt->fetchColumn();

$filteredRecordsQuery = $countQueryBase;
if (!empty($dataWhere)) {
    $filteredRecordsQuery .= " WHERE " . implode(" AND ", $dataWhere);
}
$filteredRecordsStmt = $pdo->prepare($filteredRecordsQuery);
foreach ($dataParams as $key => $value) {
    if (in_array($key, ['start', 'length'], true)) {
        continue;
    }
    $paramType = in_array($key, ['admin_id', 'filter_days'], true) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $filteredRecordsStmt->bindValue(':' . $key, $value, $paramType);
}
$filteredRecordsStmt->execute();
$totalFiltered = $filteredRecordsStmt->fetchColumn();

echo json_encode([
    'draw' => intval($_POST['draw'] ?? 0),
    'recordsTotal' => intval($totalRecords),
    'recordsFiltered' => intval($totalFiltered),
    'data' => $data
]);