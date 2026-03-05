<?php
// admin_ajax/send_universal_email.php
// Universal email sender - wraps any text in professional HTML template

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

// Validate required fields
if (empty($_POST['user_id']) || empty($_POST['subject']) || empty($_POST['message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: user_id, subject, message']);
    exit();
}

$userId = (int)$_POST['user_id'];
$subject = trim($_POST['subject']);
$message = trim($_POST['message']);

try {
    // Get user details
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, balance, status, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    // Get system settings
    $settingsStmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    
    $siteName = $settings['brand_name'] ?? 'KryptoX';
    $siteUrl = $settings['site_url'] ?? 'https://kryptox.co.uk';
    $contactEmail = $settings['contact_email'] ?? 'info@kryptox.co.uk';
    $contactPhone = $settings['contact_phone'] ?? '';
    
    // Replace variables in message
    $variables = [
        '{first_name}' => htmlspecialchars($user['first_name']),
        '{last_name}' => htmlspecialchars($user['last_name']),
        '{full_name}' => htmlspecialchars($user['first_name'] . ' ' . $user['last_name']),
        '{email}' => htmlspecialchars($user['email']),
        '{balance}' => number_format($user['balance'], 2),
        '{status}' => htmlspecialchars($user['status']),
        '{user_id}' => $user['id'],
        '{site_url}' => htmlspecialchars($siteUrl),
        '{site_name}' => htmlspecialchars($siteName),
        '{contact_email}' => htmlspecialchars($contactEmail),
        '{contact_phone}' => htmlspecialchars($contactPhone)
    ];
    
    $subject = str_replace(array_keys($variables), array_values($variables), $subject);
    $message = str_replace(array_keys($variables), array_values($variables), $message);
    
    // Convert newlines to HTML paragraphs for better formatting
    // Handle both \r\n (Windows) and \n (Unix) line endings
    $message = str_replace("\r\n", "\n", $message);
    $messageParagraphs = '';
    $lines = explode("\n", $message);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $messageParagraphs .= '<p>' . $line . '</p>';
        } else {
            // Empty line - add spacing
            $messageParagraphs .= '<br>';
        }
    }
    if (empty(trim($messageParagraphs))) {
        $messageParagraphs = '<p>' . nl2br(htmlspecialchars($message)) . '</p>';
    }
    
    // Build professional HTML email template (KryptoX Standard)
    $htmlContent = '<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . htmlspecialchars($subject) . '</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      line-height: 1.6;
      color: #333;
      background: #f4f6f8;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 640px;
      margin: 30px auto;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.08);
      overflow: hidden;
    }

    .header {
      background: linear-gradient(90deg, #2950a8 0%, #2da9e3 100%);
      color: #fff;
      text-align: center;
      padding: 30px 20px;
    }
    .header h1 {
      margin: 0;
      font-size: 26px;
      font-weight: 600;
    }
    .header p {
      margin-top: 8px;
      font-size: 15px;
      opacity: 0.9;
    }

    .content {
      padding: 25px;
      background: #f9f9f9;
    }

    .highlight-box {
      background: linear-gradient(90deg, #007bff10 0%, #007bff05 100%);
      border-left: 5px solid #007bff;
      padding: 20px;
      border-radius: 6px;
      margin: 20px 0;
    }
    .highlight-box h3 {
      margin-top: 0;
      color: #007bff;
    }
    .highlight-box p {
      margin: 6px 0;
    }

    .btn {
      display: inline-block;
      background: #007bff;
      color: white;
      padding: 10px 18px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: bold;
      margin-top: 15px;
    }

    .signature {
      margin-top: 40px;
      border-top: 1px solid #e0e0e0;
      padding-top: 25px;
      font-size: 14px;
      color: #555;
      text-align: center;
    }

    .signature img {
      height: 50px;
      margin: 0 auto 12px;
      display: block;
    }

    .signature strong {
      color: #111;
      font-size: 15px;
    }

    .signature a {
      color: #007bff;
      text-decoration: none;
    }

    .signature p {
      font-size: 12px;
      color: #777;
      line-height: 1.5;
      margin-top: 8px;
    }

    .footer {
      text-align: center;
      font-size: 12px;
      color: #777;
      padding: 15px;
      background: #f1f3f5;
    }

    @media only screen and (max-width: 600px) {
      .container {
        width: 94%;
      }
      .header h1 {
        font-size: 22px;
      }
      .signature img {
        height: 45px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>' . htmlspecialchars($subject) . '</h1>
    </div>

    <div class="content">
      <p>Sehr geehrte/r ' . htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']) . ',</p>

      <div class="highlight-box">
        ' . $messageParagraphs . '
      </div>

      <p><a href="' . htmlspecialchars($siteUrl) . '/login.php" class="btn">Zum Kundenportal</a></p>

      <p>Mit freundlichen Grüßen,</p>

      <div class="signature">
        <img src="https://kryptox.co.uk/assets/img/logo.png" alt="KryptoX Logo"><br>
        <strong>' . htmlspecialchars($siteName) . ' Team</strong><br>
        Davidson House Forbury Square, Reading, RG1 3EU, UNITED KINGDOM<br>
        E: <a href="mailto:' . htmlspecialchars($contactEmail) . '">' . htmlspecialchars($contactEmail) . '</a> | 
        W: <a href="' . htmlspecialchars($siteUrl) . '">' . htmlspecialchars($siteUrl) . '</a>
        <p>
          FCA Reference Nr: 910584<br>
          <br>
          <em>Hinweis:</em> Diese E-Mail kann vertrauliche oder rechtlich geschützte Informationen enthalten.  
          Wenn Sie nicht der richtige Adressat sind, informieren Sie uns bitte und löschen Sie diese Nachricht.
        </p>
      </div>
    </div>

    <div class="footer">
      © ' . date('Y') . ' ' . htmlspecialchars($siteName) . '. Alle Rechte vorbehalten.
    </div>
  </div>
</body>
</html>';
    
    // Get SMTP settings
    $smtpStmt = $pdo->query("SELECT * FROM smtp_settings LIMIT 1");
    $smtp = $smtpStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$smtp) {
        throw new Exception('SMTP settings not configured');
    }
    
    // Send email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtp['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtp['username'];
    $mail->Password = $smtp['password'];
    $mail->SMTPSecure = $smtp['encryption'] ?? 'tls';
    $mail->Port = $smtp['port'] ?? 587;
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom($smtp['from_email'] ?? $smtp['username'], $smtp['from_name'] ?? $siteName);
    $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $htmlContent;
    $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $message));
    
    $mail->send();
    
    // Log email
    $logStmt = $pdo->prepare("INSERT INTO email_logs (recipient, subject, content, sent_at, status) VALUES (?, ?, ?, NOW(), 'sent')");
    $logStmt->execute([$user['email'], $subject, $htmlContent]);
    
    // Log admin action
    $adminLogStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, created_at) VALUES (?, 'send_email', 'user', ?, ?, ?, NOW())");
    $adminLogStmt->execute([
        $_SESSION['admin_id'],
        $userId,
        'Sent email: ' . $subject,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Email sent successfully to ' . $user['email']
    ]);
    
} catch (Exception $e) {
    error_log("Universal email error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}