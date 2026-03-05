<?php
require_once __DIR__ . '/../session.php';

header('Content-Type: application/json');

try {
    // Enhanced session validation
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity']) || 
        (time() - $_SESSION['last_activity'] > 1800)) {
        throw new Exception('Session expired. Please login again.', 401);
    }

    // Validate input
    if (empty($_GET['id'] || !ctype_digit($_GET['id']))) {
        throw new Exception('Invalid Case ID');
    }

    $caseId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM cases WHERE id = ? AND user_id = ?");
    $stmt->execute([$caseId, $_SESSION['user_id']]);
    
    if (!($case = $stmt->fetch(PDO::FETCH_ASSOC))) {
        throw new Exception('Case not found');
    }

    // Format response
    $response = [
        'success' => true,
        'case' => [
            'id' => $case['id'],
            'case_number' => $case['case_number'],
            'status' => ucwords(str_replace('_', ' ', $case['status'])),
            'reported_amount' => '$' . number_format($case['reported_amount'], 2),
            'created_at' => date('M d, Y', strtotime($case['created_at']))
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log('Error in get-case.php: ' . $e->getMessage());
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'redirect' => $e->getCode() === 401 ? 'login.php' : null
    ]);
}