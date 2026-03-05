<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid template ID']);
    exit();
}

$templateId = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE id = ?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        echo json_encode(['success' => false, 'message' => 'Template not found']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'template' => $template
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get template details',
        'error' => $e->getMessage()
    ]);
}
?>