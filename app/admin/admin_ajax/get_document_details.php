<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            ud.*,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            u.email as user_email
        FROM user_documents ud
        LEFT JOIN users u ON ud.user_id = u.id
        WHERE ud.id = ?
    ");
    $stmt->execute([$id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($document) {
        echo json_encode([
            'success' => true,
            'document' => $document
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Document not found'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>