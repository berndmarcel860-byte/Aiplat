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
    $orderColumn = $input['order'][0]['column'] ?? 3; // Default sort by upload date
    $orderDir = $input['order'][0]['dir'] ?? 'desc';

    $columns = [
        0 => 'document_name',
        1 => 'document_type',
        2 => 'status',
        3 => 'uploaded_at'
    ];
    $orderBy = $columns[$orderColumn] ?? 'uploaded_at';

    $query = "SELECT 
                id,
                document_name,
                document_type,
                file_path,
                file_size,
                status,
                description,
                DATE_FORMAT(uploaded_at, '%Y-%m-%d %H:%i:%s') as uploaded_at,
                CASE 
                    WHEN status = 'approved' THEN 'success'
                    WHEN status = 'pending' THEN 'warning'
                    WHEN status = 'rejected' THEN 'danger'
                    ELSE 'info'
                END as status_class
              FROM user_documents 
              WHERE user_id = :user_id";

    if (!empty($search)) {
        $query .= " AND (document_name LIKE :search OR document_type LIKE :search OR status LIKE :search)";
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
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalQuery = "SELECT COUNT(*) FROM user_documents WHERE user_id = :user_id";
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $totalStmt->execute();
    $totalRecords = $totalStmt->fetchColumn();

    $filteredRecords = $totalRecords;
    if (!empty($search)) {
        $filteredQuery = "SELECT COUNT(*) FROM user_documents 
                         WHERE user_id = :user_id 
                         AND (document_name LIKE :search OR document_type LIKE :search OR status LIKE :search)";
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
        'data' => $documents
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTrace()
    ]);
}