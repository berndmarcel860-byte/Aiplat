<?php
require_once '../admin_session.php';
require_once __DIR__ . '/../AdminEmailHelper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Validate required fields
    $missingFields = [];
    foreach (['user_id', 'subject', 'content'] as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }

    if (!empty($missingFields)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missingFields));
    }

    $userId  = (int)$_POST['user_id'];
    $subject = trim($_POST['subject']);
    $content = trim($_POST['content']);

    // Send via AdminEmailHelper – pulls all company info from system_settings
    $emailHelper = new AdminEmailHelper($pdo);
    $ok = $emailHelper->sendDirectEmail($userId, $subject, $content);

    if (!$ok) {
        throw new Exception('Fehler beim Senden der E-Mail. Bitte SMTP-Einstellungen prüfen.');
    }

    // Log admin action
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, created_at)
        VALUES (?, 'send_email', 'user', ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['admin_id'],
        $userId,
        'Sent email: ' . $subject,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    echo json_encode(['success' => true, 'message' => 'E-Mail erfolgreich gesendet.']);

} catch (Exception $e) {
    error_log("send_user_email error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>