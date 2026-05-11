<?php
require_once '../config.php';
require_once '../session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $start = $input['start'] ?? 0;
    $length = $input['length'] ?? 10;
    $search = $input['search']['value'] ?? '';
    $orderColumn = $input['order'][0]['column'] ?? 4;
    $orderDir = $input['order'][0]['dir'] ?? 'desc';

    $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

    $columns = [
        0 => 'd.type',
        1 => 'd.amount',
        2 => 'd.method_code',
        3 => 'd.status',
        4 => 'd.status',   // escrow column (non-sortable, fallback)
        5 => 'd.created_at'
    ];
    $orderBy = $columns[$orderColumn] ?? 'd.created_at';

    $query = "SELECT 
                'Deposit' as type,
                d.amount,
                d.method_code as method,
                d.status,
                d.created_at,
                d.reference,
                e.status AS escrow_status,
                e.reference AS escrow_reference
              FROM deposits d
              LEFT JOIN escrow_accounts e ON e.deposit_id = d.id
              WHERE d.user_id = :user_id";

    if (!empty($search)) {
        $query .= " AND (d.method_code LIKE :search OR d.status LIKE :search OR d.reference LIKE :search)";
    }

    $query .= " ORDER BY $orderBy $orderDir LIMIT :start, :length";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);

    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%");
    }

    $stmt->execute();
    $deposits = $stmt->fetchAll();

    $totalQuery = "SELECT COUNT(*) FROM deposits d WHERE d.user_id = :user_id";
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $totalStmt->execute();
    $totalRecords = $totalStmt->fetchColumn();

    $filteredRecords = $totalRecords;
    if (!empty($search)) {
        $filteredQuery = "SELECT COUNT(*) FROM deposits d WHERE d.user_id = :user_id 
                         AND (d.method_code LIKE :search OR d.status LIKE :search OR d.reference LIKE :search)";
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
        'data' => $deposits
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}