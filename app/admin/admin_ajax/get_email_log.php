<?php
require_once '../admin_session.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email log ID']);
    exit();
}

$email_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            el.*,
            et.template_key
        FROM email_logs el
        LEFT JOIN email_templates et ON el.template_id = et.id
        WHERE el.id = ?
    ");
    $stmt->execute([$email_id]);
    $email = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Email log not found']);
        exit();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'email' => $email
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>