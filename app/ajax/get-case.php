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
    if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
        throw new Exception('Invalid Case ID');
    }

    $caseId = (int)$_GET['id'];
    $userId = $_SESSION['user_id'];
    
    // Get comprehensive case details
    $stmt = $pdo->prepare("
        SELECT 
            c.*, 
            p.name AS platform_name,
            p.logo AS platform_logo,
            (SELECT SUM(amount) FROM case_recovery_transactions WHERE case_id = c.id) AS recovered_amount
        FROM cases c
        LEFT JOIN scam_platforms p ON c.platform_id = p.id
        WHERE c.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$caseId, $userId]);
    
    if (!($case = $stmt->fetch(PDO::FETCH_ASSOC))) {
        throw new Exception('Case not found');
    }
    
    // Get status history
    $stmt = $pdo->prepare("
        SELECT h.*, a.first_name, a.last_name 
        FROM case_status_history h
        LEFT JOIN admins a ON h.changed_by = a.id
        WHERE h.case_id = ?
        ORDER BY h.created_at DESC
    ");
    $stmt->execute([$caseId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get documents
    $stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ?");
    $stmt->execute([$caseId]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recovery transactions
    $stmt = $pdo->prepare("
        SELECT rt.*, a.first_name AS admin_first_name, a.last_name AS admin_last_name
        FROM case_recovery_transactions rt
        LEFT JOIN admins a ON rt.processed_by = a.id
        WHERE rt.case_id = ?
        ORDER BY rt.transaction_date DESC
    ");
    $stmt->execute([$caseId]);
    $recoveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response
    $response = [
        'success' => true,
        'case' => $case,
        'history' => $history,
        'documents' => $documents,
        'recoveries' => $recoveries
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