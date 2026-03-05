<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid template ID']);
    exit();
}

$templateId = (int)$_POST['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM email_templates WHERE id = ?");
    $stmt->execute([$templateId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Template not found');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Email template deleted successfully'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error while deleting template',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete template',
        'error' => $e->getMessage()
    ]);
}
?>