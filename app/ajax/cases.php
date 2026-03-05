<?php
require_once __DIR__ . '/../config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode([
        'error' => 'Unauthorized Please login to access this resource',
        'message' => 'Please login to access this resource',
        'redirect' => 'login.php'
    ]));
}

header('Content-Type: application/json');

try {
    // Debug: Log incoming request
    error_log('Cases AJAX Request: ' . print_r($_POST, true));

    // Define columns that exist in database
    $dbColumns = [
        'id',
        'case_number', 
        'reported_amount',
        'recovered_amount',
        'status',
        'created_at',
        'updated_at'
    ];

    // Get DataTables parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? min(intval($_POST['length']), 100) : 10;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    
    // Order parameters with validation
    $orderColumnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 4; // Default to created_at
    $orderDir = isset($_POST['order'][0]['dir']) && in_array(strtolower($_POST['order'][0]['dir']), ['asc', 'desc']) 
                ? $_POST['order'][0]['dir'] 
                : 'desc';

    // Validate order column exists
    $orderBy = isset($dbColumns[$orderColumnIndex]) ? $dbColumns[$orderColumnIndex] : 'created_at';

    // Build base query
    $query = "SELECT SQL_CALC_FOUND_ROWS " . implode(', ', $dbColumns) . " 
              FROM cases 
              WHERE user_id = :user_id";

    $params = [':user_id' => $_SESSION['user_id']];
    $searchParams = [];

    // Add search conditions if search value exists
    if (!empty($searchValue)) {
        $query .= " AND (";
        $searchFields = ['case_number', 'status'];
        $searchConditions = [];
        
        foreach ($searchFields as $field) {
            $param = ":search_$field";
            $searchConditions[] = "$field LIKE $param";
            $searchParams[$param] = "%$searchValue%";
        }
        
        $query .= implode(" OR ", $searchConditions) . ")";
    }

    // Add sorting
    $query .= " ORDER BY `$orderBy` $orderDir";
    $query .= " LIMIT :limit OFFSET :offset";

    // Prepare and execute query
    $stmt = $pdo->prepare($query);
    
    // Debug: Show final query
    error_log("Final Query: $query");
    
    // Bind parameters
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    
    foreach ($searchParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    if (!$stmt->execute()) {
        throw new Exception('Failed to execute database query');
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Show raw data before formatting
    error_log('Raw Data: ' . print_r($data, true));

    // Format data for frontend
    foreach ($data as &$row) {
        // Ensure status exists
        if (!isset($row['status'])) {
            $row['status'] = 'unknown';
        }
        
        // Format amounts
        $row['reported_amount'] = is_numeric($row['reported_amount']) 
            ? floatval($row['reported_amount']) // Return raw number for frontend formatting
            : 0;
            
        $row['recovered_amount'] = is_numeric($row['recovered_amount']) 
            ? floatval($row['recovered_amount'])
            : null; // Null for "-" display

        // Format dates
        $row['created_at'] = $row['created_at'] ?: null;
        $row['updated_at'] = $row['updated_at'] ?: null;
    }

    // Get total records
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE user_id = :user_id");
    $totalStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $totalStmt->execute();
    $totalRecords = $totalStmt->fetchColumn();

    // Get filtered count (same conditions as main query)
    if (!empty($searchValue)) {
        $filteredQuery = "SELECT COUNT(*) FROM cases WHERE user_id = :user_id AND (";
        $filteredQuery .= implode(" OR ", $searchConditions) . ")";
        
        $filteredStmt = $pdo->prepare($filteredQuery);
        $filteredStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        foreach ($searchParams as $key => $value) {
            $filteredStmt->bindValue($key, $value);
        }
        $filteredStmt->execute();
        $filteredRecords = $filteredStmt->fetchColumn();
    } else {
        $filteredRecords = $totalRecords;
    }

    // Prepare final response
    $response = [
        "draw" => $draw,
        "recordsTotal" => (int)$totalRecords,
        "recordsFiltered" => (int)$filteredRecords,
        "data" => $data
    ];

    // Debug: Show final response
    error_log('Final Response: ' . print_r($response, true));

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database error in cases.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => 'A database error occurred',
        'debug' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in cases.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'error' => 'Application error',
        'message' => $e->getMessage()
    ]);
}