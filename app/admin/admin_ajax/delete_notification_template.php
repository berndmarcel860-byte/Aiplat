<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid template ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT notification_key FROM email_notifications WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Template not found']);
        exit;
    }

    $pdo->prepare("DELETE FROM email_notifications WHERE id = ?")->execute([$id]);

    // Log action
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at)
            VALUES (?, 'DELETE', 'email_notification', ?, ?, ?, ?, NOW())
        ");
        $logStmt->execute([
            $_SESSION['admin_id'], $id,
            'Deleted notification template: ' . $row['notification_key'],
            $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $logEx) {}

    echo json_encode(['success' => true, 'message' => 'Template deleted successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
