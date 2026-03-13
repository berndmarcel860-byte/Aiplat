<?php
// admin_ajax/send_all_users_email.php
// Send a custom email to ALL active verified users

require_once '../admin_session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Load PHPMailer
$vendorPaths = [
    $_SERVER['DOCUMENT_ROOT'] . '/app/vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php'
];
$autoloadFound = false;
foreach ($vendorPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    echo json_encode(['success' => false, 'message' => 'PHPMailer not found']);
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;

$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
    exit();
}

try {
    // Get SMTP settings
    $smtpStmt = $pdo->query("SELECT * FROM smtp_settings LIMIT 1");
    $smtp = $smtpStmt->fetch(PDO::FETCH_ASSOC);

    if (!$smtp) {
        throw new Exception('SMTP settings not configured');
    }

    // Get system settings
    $settingsStmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $siteName     = $settings['brand_name']    ?? 'Platform';
    $siteUrl      = $settings['site_url']      ?? 'https://example.com';
    $contactEmail = $settings['contact_email'] ?? '';

    // Get all active verified users
    $usersStmt = $pdo->query("SELECT id, email, first_name, last_name FROM users WHERE status = 'active' AND is_verified = 1");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo json_encode(['success' => false, 'message' => 'No active users found']);
        exit();
    }

    $sent   = 0;
    $failed = 0;

    foreach ($users as $user) {
        if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            $failed++;
            continue;
        }

        // Replace variables in subject and message for each user
        $replacements = [
            '{first_name}'    => htmlspecialchars($user['first_name']),
            '{last_name}'     => htmlspecialchars($user['last_name']),
            '{full_name}'     => htmlspecialchars($user['first_name'] . ' ' . $user['last_name']),
            '{email}'         => htmlspecialchars($user['email']),
            '{user_id}'       => $user['id'],
            '{site_url}'      => htmlspecialchars($siteUrl),
            '{site_name}'     => htmlspecialchars($siteName),
            '{contact_email}' => htmlspecialchars($contactEmail),
        ];

        $userSubject = str_replace(array_keys($replacements), array_values($replacements), $subject);
        $userMessage = str_replace(array_keys($replacements), array_values($replacements), $message);

        // Convert newlines to HTML paragraphs
        $userMessage = str_replace("\r\n", "\n", $userMessage);
        $messageParagraphs = '';
        foreach (explode("\n", $userMessage) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $messageParagraphs .= '<p>' . $line . '</p>';
            } else {
                $messageParagraphs .= '<br>';
            }
        }

        // Build HTML email
        $htmlContent = '<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . htmlspecialchars($userSubject) . '</title>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f6f8; margin: 0; padding: 0; }
    .container { max-width: 640px; margin: 30px auto; background: #fff; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); overflow: hidden; }
    .header { background: linear-gradient(90deg, #2950a8 0%, #2da9e3 100%); color: #fff; text-align: center; padding: 30px 20px; }
    .header h1 { margin: 0; font-size: 26px; font-weight: 600; }
    .content { padding: 25px; background: #f9f9f9; }
    .highlight-box { background: linear-gradient(90deg, #007bff10 0%, #007bff05 100%); border-left: 5px solid #007bff; padding: 20px; border-radius: 6px; margin: 20px 0; }
    .btn { display: inline-block; background: #007bff; color: white; padding: 10px 18px; border-radius: 5px; text-decoration: none; font-weight: bold; margin-top: 15px; }
    .signature { margin-top: 40px; border-top: 1px solid #e0e0e0; padding-top: 25px; font-size: 14px; color: #555; text-align: center; }
    .signature strong { color: #111; font-size: 15px; }
    .signature a { color: #007bff; text-decoration: none; }
    .footer { text-align: center; font-size: 12px; color: #777; padding: 15px; background: #f1f3f5; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>' . htmlspecialchars($userSubject) . '</h1>
    </div>
    <div class="content">
      <p>Sehr geehrte/r ' . htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']) . ',</p>
      <div class="highlight-box">' . $messageParagraphs . '</div>
      <p><a href="' . htmlspecialchars($siteUrl) . '/login.php" class="btn">Zum Kundenportal</a></p>
      <p>Mit freundlichen Grüßen,</p>
      <div class="signature">
        <strong>' . htmlspecialchars($siteName) . ' Team</strong><br>
        E: <a href="mailto:' . htmlspecialchars($contactEmail) . '">' . htmlspecialchars($contactEmail) . '</a> |
        W: <a href="' . htmlspecialchars($siteUrl) . '">' . htmlspecialchars($siteUrl) . '</a>
      </div>
    </div>
    <div class="footer">© ' . date('Y') . ' ' . htmlspecialchars($siteName) . '. Alle Rechte vorbehalten.</div>
  </div>
</body>
</html>';

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['username'];
            $mail->Password   = $smtp['password'];
            $mail->SMTPSecure = $smtp['encryption'] ?? 'tls';
            $mail->Port       = $smtp['port'] ?? 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($smtp['from_email'] ?? $smtp['username'], $smtp['from_name'] ?? $siteName);
            $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
            $mail->isHTML(true);
            $mail->Subject = $userSubject;
            $mail->Body    = $htmlContent;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $userMessage));

            $mail->send();

            // Log sent email
            $logStmt = $pdo->prepare("INSERT INTO email_logs (recipient, subject, content, sent_at, status) VALUES (?, ?, ?, NOW(), 'sent')");
            $logStmt->execute([$user['email'], $userSubject, $htmlContent]);

            $sent++;
        } catch (Exception $mailEx) {
            error_log("send_all_users_email: Failed to send to {$user['email']}: " . $mailEx->getMessage());
            $failed++;
        }
    }

    // Log admin action
    $adminLogStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, created_at) VALUES (?, 'send_mail_all', 'users', 0, ?, ?, NOW())");
    $adminLogStmt->execute([
        $_SESSION['admin_id'],
        "Bulk email sent: \"{$subject}\" — {$sent} sent, {$failed} failed",
        $_SERVER['REMOTE_ADDR'] ?? ''
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
