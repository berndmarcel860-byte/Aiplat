<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $template_key = trim($_POST['template_key'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $variables = trim($_POST['variables'] ?? '');
    
    if (empty($template_key) || empty($subject) || empty($content)) {
        throw new Exception('Template key, subject and content are required');
    }
    
    // Validate JSON if variables provided
    if ($variables && !json_decode($variables)) {
        throw new Exception('Variables must be valid JSON format');
    }
    
    // Check if template key exists
    $stmt = $pdo->prepare("SELECT id FROM email_templates WHERE template_key = ?");
    $stmt->execute([$template_key]);
    if ($stmt->fetch()) {
        throw new Exception('Template key already exists');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at) 
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$template_key, $subject, $content, $variables]);
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at) 
        VALUES (?, 'CREATE', 'email_template', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['admin_id'], 
        $pdo->lastInsertId(), 
        "Created email template: $template_key",
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Email template created successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in add_email_template.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>