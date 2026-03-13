<?php
// admin_ajax/send_all_users_email.php
// Send a custom email to ALL active verified users, wrapped in the standard
// HTML email template via AdminEmailHelper::sendDirectEmail().

require_once '../admin_session.php';
require_once '../AdminEmailHelper.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
    exit();
}

try {
    // Get all active verified users
    $usersStmt = $pdo->query("SELECT id, email FROM users WHERE status = 'active' AND is_verified = 1");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo json_encode(['success' => false, 'message' => 'No active users found']);
        exit();
    }

    $emailHelper = new AdminEmailHelper($pdo);

    // Convert plain-text newlines in the body to HTML paragraphs so that
    // content typed in the textarea renders correctly inside the template.
    $htmlMessage = '';
    $normalised  = str_replace("\r\n", "\n", $message);
    foreach (explode("\n", $normalised) as $line) {
        $line = trim($line);
        $htmlMessage .= ($line !== '') ? '<p>' . $line . '</p>' : '<br>';
    }

    $sent   = 0;
    $failed = 0;

    foreach ($users as $user) {
        if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            $failed++;
            continue;
        }

        // sendDirectEmail() replaces {variables}, wraps content in the
        // standard HTML template (wrapInTemplate) and sends via SMTP.
        if ($emailHelper->sendDirectEmail((int)$user['id'], $subject, $htmlMessage)) {
            $sent++;
        } else {
            $failed++;
        }
    }

    // Log bulk action in admin_logs
    $adminLogStmt = $pdo->prepare(
        "INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, created_at)
         VALUES (?, 'send_mail_all', 'users', 0, ?, ?, NOW())"
    );
    $adminLogStmt->execute([
        $_SESSION['admin_id'],
        "Bulk email sent: \"{$subject}\" — {$sent} sent, {$failed} failed",
        $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    echo json_encode([
        'success' => true,
        'message' => "Email sent successfully to {$sent} user(s)." . ($failed > 0 ? " {$failed} failed." : ''),
        'sent'    => $sent,
        'failed'  => $failed,
    ]);

} catch (Exception $e) {
    error_log("send_all_users_email error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
