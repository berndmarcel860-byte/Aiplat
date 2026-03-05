<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_POST['template_id']) || !is_numeric($_POST['template_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid template ID']);
    exit();
}

$requiredFields = ['template_key', 'subject', 'content'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: ' . implode(', ', $missingFields)
    ]);
    exit();
}

$templateId = (int)$_POST['template_id'];

try {
    $stmt = $pdo->prepare("
        UPDATE email_templates SET
            template_key = :template_key,
            subject = :subject,
            content = :content,
            variables = :variables,
            updated_at = NOW()
        WHERE id = :template_id
    ");
    
    $success = $stmt->execute([
        ':template_key' => $_POST['template_key'],
        ':subject' => $_POST['subject'],
        ':content' => $_POST['content'],
        ':variables' => $_POST['variables'] ?? null,
        ':template_id' => $templateId
    ]);
    
    if (!$success) {
        throw new Exception('Failed to update template in database');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Email template updated successfully'
    ]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate key error
        echo json_encode([
            'success' => false,
            'message' => 'Template key already exists'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error while updating template',
            'error' => $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update template',
        'error' => $e->getMessage()
    ]);
}
?>