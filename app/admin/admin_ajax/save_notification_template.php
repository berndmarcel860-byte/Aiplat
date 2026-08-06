<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id               = intval($_POST['id'] ?? 0);
$notificationKey  = trim($_POST['notification_key'] ?? '');
$name             = trim($_POST['name'] ?? '');
$subject          = trim($_POST['subject'] ?? '');
$content          = trim($_POST['content'] ?? '');
$description      = trim($_POST['description'] ?? '');
$category         = trim($_POST['category'] ?? 'general');
$variables        = trim($_POST['variables'] ?? '[]');
$isActive         = intval($_POST['is_active'] ?? 1);

if (empty($notificationKey) || empty($name) || empty($subject) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Key, name, subject and content are required']);
    exit;
}

// Ensure notification_key is safe (alphanumeric + underscores)
if (!preg_match('/^[a-z0-9_]+$/i', $notificationKey)) {
    echo json_encode(['success' => false, 'message' => 'Notification key may only contain letters, digits and underscores']);
    exit;
}

// Validate JSON variables
if (!json_decode($variables)) {
    $variables = '[]';
}

try {
    if ($id > 0) {
        // UPDATE existing
        $stmt = $pdo->prepare("
            UPDATE email_notifications SET
                notification_key = ?,
                name             = ?,
                subject          = ?,
                content          = ?,
                description      = ?,
                category         = ?,
                variables        = ?,
                is_active        = ?,
                updated_at       = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $notificationKey, $name, $subject, $content,
            $description, $category, $variables, $isActive, $id
        ]);

        $logAction = 'UPDATE';
        $entityId  = $id;
        $logMsg    = "Updated notification template: $notificationKey";
    } else {
        // INSERT new
        $stmt = $pdo->prepare("
            INSERT INTO email_notifications
                (notification_key, name, subject, content, description, category, variables, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $notificationKey, $name, $subject, $content,
            $description, $category, $variables, $isActive
        ]);

        $logAction = 'CREATE';
        $entityId  = (int)$pdo->lastInsertId();
        $logMsg    = "Created notification template: $notificationKey";
    }

    // Log admin action
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at)
            VALUES (?, ?, 'email_notification', ?, ?, ?, ?, NOW())
        ");
        $logStmt->execute([
            $_SESSION['admin_id'], $logAction, $entityId, $logMsg,
            $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $logEx) {
        // Logging failure is non-critical
    }

    echo json_encode([
        'success' => true,
        'message' => $id > 0 ? 'Template updated successfully' : 'Template created successfully',
        'id'      => $entityId,
    ]);

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Notification key already exists']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
