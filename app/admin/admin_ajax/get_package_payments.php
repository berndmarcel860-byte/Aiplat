<?php
// admin_ajax/get_package_payments.php
// Server-side DataTable handler for the Package Payments admin page.

require_once '../admin_session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // DataTables server-side parameters
    $draw   = isset($_POST['draw'])   ? (int)$_POST['draw']   : 1;
    $start  = isset($_POST['start'])  ? (int)$_POST['start']  : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
    $search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

    // Ordering
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDir    = (isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';
    $columns     = ['pp.id', 'u.first_name', 'p.name', 'pp.amount', 'pp.payment_method', 'pp.status', 'pp.created_at'];
    $orderBy     = $columns[$orderColumn] ?? 'pp.id';

    // Filters from POST
    $statusFilter  = isset($_POST['status'])     ? trim($_POST['status'])     : '';
    $packageFilter = isset($_POST['package_id']) ? (int)$_POST['package_id'] : 0;
    $startDate     = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
    $endDate       = isset($_POST['end_date'])   ? trim($_POST['end_date'])   : '';

    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId   = (int)$_SESSION['admin_id'];

    // ------------------------------------------------------------------
    // Check if package_payments table exists; if not, return empty set
    // ------------------------------------------------------------------
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'package_payments'");
    if ($tableCheck->rowCount() === 0) {
        error_log('package_payments table missing — run database/package_payments_table.sql migration.');
        echo json_encode([
            'draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
            'notice' => 'Run database/package_payments_table.sql migration first.'
        ]);
        exit();
    }

    // ------------------------------------------------------------------
    // Build query
    // ------------------------------------------------------------------
    $baseWhere = " WHERE 1=1 ";
    $params    = [];

    // Role-based scoping
    if ($currentAdminRole !== 'superadmin') {
        $baseWhere .= " AND u.admin_id = ? ";
        $params[] = $currentAdminId;
    }

    // Filters
    if ($statusFilter !== '') {
        $baseWhere .= " AND pp.status = ? ";
        $params[] = $statusFilter;
    }
    if ($packageFilter > 0) {
        $baseWhere .= " AND pp.package_id = ? ";
        $params[] = $packageFilter;
    }
    if ($startDate !== '') {
        $baseWhere .= " AND DATE(pp.created_at) >= ? ";
        $params[] = $startDate;
    }
    if ($endDate !== '') {
        $baseWhere .= " AND DATE(pp.created_at) <= ? ";
        $params[] = $endDate;
    }
    if ($search !== '') {
        $baseWhere .= " AND (
            u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?
            OR p.name LIKE ? OR pp.reference LIKE ?
        ) ";
        $s = "%{$search}%";
        $params = array_merge($params, [$s, $s, $s, $s, $s]);
    }

    $fromJoins = "
        FROM package_payments pp
        INNER JOIN users    u  ON pp.user_id    = u.id
        LEFT  JOIN packages p  ON pp.package_id = p.id
        LEFT  JOIN admins   a  ON pp.processed_by = a.id
        LEFT  JOIN user_packages up ON pp.user_package_id = up.id
    ";

    // Count
    $countStmt = $pdo->prepare("SELECT COUNT(*) {$fromJoins} {$baseWhere}");
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();

    // Data
    $dataParams   = array_merge($params, [$start, $length]);
    $dataQuery    = "
        SELECT
            pp.id,
            pp.user_package_id,
            pp.user_id,
            pp.package_id,
            pp.amount,
            pp.currency,
            pp.payment_method,
            pp.reference,
            pp.status,
            pp.admin_notes,
            pp.processed_at,
            pp.created_at,
            pp.updated_at,
            CONCAT(u.first_name, ' ', u.last_name) AS user_name,
            u.email                                 AS user_email,
            p.name                                  AS package_name,
            p.price                                 AS package_price,
            CONCAT(a.first_name, ' ', a.last_name)  AS processed_by_name
        {$fromJoins}
        {$baseWhere}
        ORDER BY {$orderBy} {$orderDir}
        LIMIT ?, ?
    ";

    $dataStmt = $pdo->prepare($dataQuery);
    $dataStmt->execute($dataParams);
    $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data'            => $data,
    ]);

} catch (PDOException $e) {
    error_log('get_package_payments error: ' . $e->getMessage());
    echo json_encode([
        'draw' => $draw ?? 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
        'error' => 'Database error occurred.'
    ]);
}
