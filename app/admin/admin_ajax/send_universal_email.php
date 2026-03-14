<?php
// admin_ajax/send_universal_email.php
// Universal email sender – uses AdminEmailHelper for all emails (template-key or direct custom content).
// All company information is fetched from the system_settings table by AdminEmailHelper.

require_once '../admin_session.php';
require_once __DIR__ . '/../AdminEmailHelper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate required fields
if (empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required field: user_id']);
    exit();
}

$userId      = (int)$_POST['user_id'];
$templateKey = trim($_POST['template_key'] ?? '');
$subject     = trim($_POST['subject'] ?? '');
$message     = trim($_POST['message'] ?? '');

// A template_key alone is sufficient; otherwise subject+message are required
if (empty($templateKey) && (empty($subject) || empty($message))) {
    echo json_encode(['success' => false, 'message' => 'Bitte eine E-Mail-Vorlage auswählen oder Betreff und Nachricht angeben.']);
    exit();
}

try {
    $emailHelper = new AdminEmailHelper($pdo);

    if (!empty($templateKey)) {
        // Template-based email via AdminEmailHelper::sendTemplateEmail()
        $ok = $emailHelper->sendTemplateEmail($templateKey, $userId);
    } else {
        // Custom direct email via AdminEmailHelper::sendDirectEmail()
        $ok = $emailHelper->sendDirectEmail($userId, $subject, $message);
    }

    if ($ok) {
        // Log admin action
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, created_at)
            VALUES (?, 'send_email', 'user', ?, ?, ?, NOW())
        ");
        $logLabel = !empty($templateKey) ? 'Template: ' . $templateKey : 'Subject: ' . $subject;
        $logStmt->execute([$_SESSION['admin_id'], $userId, $logLabel, $_SERVER['REMOTE_ADDR'] ?? '']);

        echo json_encode(['success' => true, 'message' => 'E-Mail erfolgreich gesendet.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Senden der E-Mail. Bitte SMTP-Einstellungen prüfen.']);
    }

} catch (Exception $e) {
    error_log("send_universal_email error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}