<?php
require_once '../../config.php';
require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid case ID']);
    exit();
}

$caseId = (int)$_GET['id'];

try {
    // Get case details
    $stmt = $pdo->prepare("
        SELECT 
            c.*, 
            u.first_name AS user_first_name, 
            u.last_name AS user_last_name,
            u.email AS user_email,
            p.name AS platform_name,
            (SELECT SUM(amount) FROM case_recovery_transactions WHERE case_id = c.id) AS recovered_amount
        FROM cases c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN scam_platforms p ON c.platform_id = p.id
        WHERE c.id = ?
    ");
    $stmt->execute([$caseId]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$case) {
        echo json_encode(['success' => false, 'message' => 'Case not found']);
        exit();
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
    
    echo json_encode([
        'success' => true,
        'case' => $case,
        'history' => $history,
        'documents' => $documents,
        'recoveries' => $recoveries
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get case details',
        'error' => $e->getMessage()
    ]);
}
?>