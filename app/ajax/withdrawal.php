<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    // Verify database connection
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $start = $input['start'] ?? 0;
    $length = $input['length'] ?? 10;
    $search = $input['search']['value'] ?? '';
    $orderColumn = $input['order'][0]['column'] ?? 4;
    $orderDir = $input['order'][0]['dir'] ?? 'desc';

    $columns = [
        0 => 'id',
        1 => 'amount',
        2 => 'method_code',
        3 => 'status',
        4 => 'created_at'
    ];
    $orderBy = $columns[$orderColumn] ?? 'created_at';

    $query = "SELECT 
                id,
                amount,
                method_code,
                status,
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                reference
              FROM withdrawals 
              WHERE user_id = :user_id";

    if (!empty($search)) {
        $query .= " AND (method_code LIKE :search OR status LIKE :search OR reference LIKE :search)";
    }

    $query .= " ORDER BY $orderBy $orderDir LIMIT :start, :length";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);

    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%");
    }

    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . implode(' ', $stmt->errorInfo()));
    }

    $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalQuery = "SELECT COUNT(*) FROM withdrawals WHERE user_id = :user_id";
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $totalStmt->execute();
    $totalRecords = $totalStmt->fetchColumn();

    $filteredRecords = $totalRecords;
    if (!empty($search)) {
        $filteredQuery = "SELECT COUNT(*) FROM withdrawals WHERE user_id = :user_id 
                         AND (method_code LIKE :search OR status LIKE :search OR reference LIKE :search)";
        $filteredStmt = $pdo->prepare($filteredQuery);
        $filteredStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $filteredStmt->bindValue(':search', "%$search%");
        $filteredStmt->execute();
        $filteredRecords = $filteredStmt->fetchColumn();
    }

    echo json_encode([
        'draw' => intval($input['draw'] ?? 1),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $withdrawals
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'database_error' => $pdo ? $pdo->errorInfo() : null
    ]);
}