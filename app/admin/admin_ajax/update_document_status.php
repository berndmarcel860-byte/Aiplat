<?php
require_once '../../config.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $documentId = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Validate
    if ($documentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
        exit;
    }
    
    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    // Update document status
    $stmt = $pdo->prepare("UPDATE user_documents SET status = ? WHERE id = ?");
    $stmt->execute([$status, $documentId]);
    
    // Log the action
    if (isset($_SESSION['admin_id'])) {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs 
            (admin_id, action, entity_type, entity_id, ip_address, user_agent, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $logStmt->execute([
            $_SESSION['admin_id'],
            'update_document_status',
            'user_document',
            $documentId,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $notes
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Document status updated successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>