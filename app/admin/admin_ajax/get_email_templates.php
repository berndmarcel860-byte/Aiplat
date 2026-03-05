<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Support both GET and POST requests
    $isDataTable = isset($_POST['draw']);
    
    if (!$isDataTable && $_SERVER['REQUEST_METHOD'] === 'GET') {
        // Simple GET request - return all templates
        $query = "SELECT id, template_key, subject, content, variables FROM email_templates ORDER BY template_key ASC";
        $stmt = $pdo->query($query);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $templates
        ]);
        exit;
    }
    
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    $query = "
        SELECT 
            id,
            template_key,
            subject,
            variables,
            created_at,
            updated_at
        FROM email_templates
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($search) {
        $query .= " AND (template_key LIKE ? OR subject LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm]);
    }
    
    $countQuery = "SELECT COUNT(*) as total FROM email_templates WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (template_key LIKE ? OR subject LIKE ?)";
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($search ? [$searchTerm, $searchTerm] : []);
    $totalRecords = $stmt->fetchColumn();
    
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDirection = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = ['id', 'template_key', 'subject', 'variables', 'created_at', 'updated_at'];
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY {$columns[$orderColumn]} $orderDirection";
    } else {
        $query .= " ORDER BY created_at DESC";
    }
    
    $query .= " LIMIT ?, ?";
    $params[] = $start;
    $params[] = $length;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>