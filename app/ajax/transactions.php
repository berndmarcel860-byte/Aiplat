<?php
require_once '../config.php';
require_once '../session.php';

header('Content-Type: application/json');

try {
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access', 401);
    }

    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    $start = $input['start'] ?? 0;
    $length = $input['length'] ?? 10;
    $search = $input['search']['value'] ?? '';
    $orderColumn = $input['order'][0]['column'] ?? 4;
    $orderDir = $input['order'][0]['dir'] ?? 'desc';

    // Column mapping for ordering
    $columns = [
        0 => 'type',
        1 => 'amount',
        2 => 'method_display',
        3 => 'status',
        4 => 'reference',
        5 => 'created_at'
    ];
    $orderBy = $columns[$orderColumn] ?? 'created_at';

    // Query directly from deposits and withdrawals tables using UNION
    // This gives us complete data from source tables
    // All string columns must have explicit COLLATE to avoid collation mismatch errors
    $query = "
        SELECT 
            d.id,
            'deposit' as type,
            d.amount,
            d.status COLLATE utf8mb4_unicode_ci as status,
            d.reference COLLATE utf8mb4_unicode_ci as reference,
            d.created_at,
            d.method_code COLLATE utf8mb4_unicode_ci as method_display,
            d.proof_path COLLATE utf8mb4_unicode_ci as details,
            NULL as withdrawal_id,
            d.id as deposit_id,
            d.method_code COLLATE utf8mb4_unicode_ci as method_code,
            NULL as otp_verified,
            d.admin_notes COLLATE utf8mb4_unicode_ci as admin_notes,
            d.processed_at,
            d.updated_at,
            NULL as transaction_id,
            d.processed_by as confirmed_by,
            NULL as ip_address
        FROM deposits d
        WHERE d.user_id = :user_id1
        
        UNION ALL
        
        SELECT 
            w.id,
            'withdrawal' as type,
            w.amount,
            w.status COLLATE utf8mb4_unicode_ci as status,
            w.reference COLLATE utf8mb4_unicode_ci as reference,
            w.created_at,
            COALESCE(upm.label, upm.cryptocurrency, upm.bank_name, w.method_code) COLLATE utf8mb4_unicode_ci as method_display,
            w.payment_details COLLATE utf8mb4_unicode_ci as details,
            w.id as withdrawal_id,
            NULL as deposit_id,
            w.method_code COLLATE utf8mb4_unicode_ci as method_code,
            NULL as otp_verified,
            w.admin_notes COLLATE utf8mb4_unicode_ci as admin_notes,
            w.processed_at,
            w.updated_at,
            NULL as transaction_id,
            w.processed_by as confirmed_by,
            NULL as ip_address
        FROM withdrawals w
        LEFT JOIN user_payment_methods upm ON w.user_id = upm.user_id 
            AND w.method_code COLLATE utf8mb4_unicode_ci = upm.payment_method COLLATE utf8mb4_unicode_ci
        WHERE w.user_id = :user_id2
    ";

    // Add search filter if provided
    $searchWhere = "";
    if (!empty($search)) {
        $searchWhere = " AND (type LIKE :search OR status LIKE :search OR reference LIKE :search OR method_display LIKE :search)";
    }

    // Wrap in subquery for filtering and ordering
    $finalQuery = "SELECT * FROM ($query) AS combined WHERE 1=1 $searchWhere ORDER BY $orderBy $orderDir LIMIT :start, :length";

    $stmt = $pdo->prepare($finalQuery);
    $stmt->bindValue(':user_id1', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':user_id2', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);

    if (!empty($search)) {
        $searchTerm = "%$search%";
        $stmt->bindValue(':search', $searchTerm);
    }

    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data for DataTables
    $formattedTransactions = array_map(function($transaction) {
        return [
            'id' => $transaction['id'],
            'type' => $transaction['type'],
            'amount' => $transaction['amount'],
            'method' => $transaction['method_display'],
            'status' => $transaction['status'],
            'reference' => $transaction['reference'],
            'created_at' => $transaction['created_at'],
            'details' => $transaction['details'],
            'withdrawal_id' => $transaction['withdrawal_id'],
            'deposit_id' => $transaction['deposit_id'],
            'method_code' => $transaction['method_code'],
            'otp_verified' => $transaction['otp_verified'],
            'admin_notes' => $transaction['admin_notes'],
            'processed_at' => $transaction['processed_at'],
            'updated_at' => $transaction['updated_at'],
            'transaction_id' => $transaction['transaction_id'],
            'confirmed_by' => $transaction['confirmed_by'],
            'ip_address' => $transaction['ip_address']
        ];
    }, $transactions);

    // Get total records count from both tables
    $totalQuery = "SELECT 
                    (SELECT COUNT(*) FROM deposits WHERE user_id = :user_id1) + 
                    (SELECT COUNT(*) FROM withdrawals WHERE user_id = :user_id2) as total";
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->bindValue(':user_id1', $_SESSION['user_id'], PDO::PARAM_INT);
    $totalStmt->bindValue(':user_id2', $_SESSION['user_id'], PDO::PARAM_INT);
    $totalStmt->execute();
    $totalRecords = $totalStmt->fetchColumn();

    // Get filtered count if searching
    $filteredRecords = $totalRecords;
    if (!empty($search)) {
        $filteredQuery = "SELECT COUNT(*) FROM ($query) AS combined WHERE 1=1 $searchWhere";
        $filteredStmt = $pdo->prepare($filteredQuery);
        $filteredStmt->bindValue(':user_id1', $_SESSION['user_id'], PDO::PARAM_INT);
        $filteredStmt->bindValue(':user_id2', $_SESSION['user_id'], PDO::PARAM_INT);
        $filteredStmt->bindValue(':search', "%$search%");
        $filteredStmt->execute();
        $filteredRecords = $filteredStmt->fetchColumn();
    }

    // Return JSON response
    echo json_encode([
        'draw' => intval($input['draw'] ?? 1),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $formattedTransactions
    ]);

} catch (Exception $e) {
    // Log error details for debugging
    error_log("Ajax transactions error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    http_response_code((int)($e->getCode() ?: 500));
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}