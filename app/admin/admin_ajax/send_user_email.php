<?php
require_once '../admin_session.php';

// Try multiple paths for vendor autoload to support different deployment structures
$vendorPaths = [
    $_SERVER['DOCUMENT_ROOT'] . '/app/vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    dirname(dirname(__DIR__)) . '/vendor/autoload.php'
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PHPMailer not found. Please ensure Composer dependencies are installed.'
    ]);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

try {
    // Validate required fields
    $requiredFields = ['user_id', 'subject', 'content'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missingFields));
    }
    
    $userId = (int)$_POST['user_id'];
    $subject = trim($_POST['subject']);
    $content = $_POST['content'];
    $templateId = !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null;
    
    // Get user details
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, last_name, uuid, balance, status, created_at
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Validate email
    if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address for user');
    }
    
    // Prepare variables for replacement
    $variables = [
        'user_id' => $user['id'],
        'uuid' => $user['uuid'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'full_name' => $user['first_name'] . ' ' . $user['last_name'],
        'balance' => number_format($user['balance'], 2),
        'status' => $user['status'],
        'registration_date' => date('Y-m-d', strtotime($user['created_at']))
    ];
    
    // Define fallback constants
    define('DEFAULT_SITE_URL', 'https://kryptox.co.uk');
    define('DEFAULT_SITE_NAME', 'KryptoX');
    define('DEFAULT_CONTACT_EMAIL', 'info@kryptox.co.uk');
    define('DEFAULT_FROM_NAME', 'System Admin');
    
    // Get site settings for additional variables
    $stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($settings) {
        $variables['site_url'] = $settings['site_url'] ?? DEFAULT_SITE_URL;
        $variables['site_name'] = $settings['brand_name'] ?? DEFAULT_SITE_NAME;
        $variables['contact_email'] = $settings['contact_email'] ?? DEFAULT_CONTACT_EMAIL;
        $variables['contact_phone'] = $settings['contact_phone'] ?? '';
    } else {
        $variables['site_url'] = DEFAULT_SITE_URL;
        $variables['site_name'] = DEFAULT_SITE_NAME;
        $variables['contact_email'] = DEFAULT_CONTACT_EMAIL;
        $variables['contact_phone'] = '';
    }
    
    // Replace variables in subject and content
    foreach ($variables as $key => $value) {
        $subject = str_replace(['{' . $key . '}', '{{' . $key . '}}'], $value, $subject);
        $content = str_replace(['{' . $key . '}', '{{' . $key . '}}'], $value, $content);
    }
    
    // Check if HTML wrapper should be applied for custom emails
    $useHtmlWrapper = isset($_POST['use_html_wrapper']) && $_POST['use_html_wrapper'] == '1';
    
    if ($useHtmlWrapper) {
        // Wrap content in professional HTML template (KryptoX Standard)
        $htmlTemplate = '<!DOCTYPE html>
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
        ' . $content . '
      </div>

      <p><a href="' . htmlspecialchars($variables['site_url']) . '/login.php" class="btn">Zum Kundenportal</a></p>

      <p>Mit freundlichen Grüßen,</p>

      <div class="signature">
        <img src="https://kryptox.co.uk/assets/img/logo.png" alt="KryptoX Logo"><br>
        <strong>' . htmlspecialchars($variables['site_name']) . ' Team</strong><br>
        Davidson House Forbury Square, Reading, RG1 3EU, UNITED KINGDOM<br>
        E: <a href="mailto:' . htmlspecialchars($variables['contact_email']) . '">' . htmlspecialchars($variables['contact_email']) . '</a> | 
        W: <a href="' . htmlspecialchars($variables['site_url']) . '">' . htmlspecialchars($variables['site_url']) . '</a>
        <p>
          FCA Reference Nr: 910584<br>
          <br>
          <em>Hinweis:</em> Diese E-Mail kann vertrauliche oder rechtlich geschützte Informationen enthalten.  
          Wenn Sie nicht der richtige Adressat sind, informieren Sie uns bitte und löschen Sie diese Nachricht.
        </p>
      </div>
    </div>

    <div class="footer">
      © ' . date('Y') . ' ' . htmlspecialchars($variables['site_name']) . '. Alle Rechte vorbehalten.
    </div>
  </div>
</body>
</html>';
        
        $content = $htmlTemplate;
    }
    
    // Initialize PHPMailer for custom content
    try {
        $mail = new PHPMailer(true);
        
        // Load SMTP settings
        $stmt = $pdo->query("SELECT * FROM smtp_settings LIMIT 1");
        $smtpSettings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$smtpSettings) {
            throw new Exception('SMTP settings not configured');
        }
        
        // Configure mailer
        $mail->isSMTP();
        $mail->Host = $smtpSettings['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtpSettings['username'];
        $mail->Password = $smtpSettings['password'];
        $mail->SMTPSecure = $smtpSettings['encryption'] ?? 'tls';
        $mail->Port = $smtpSettings['port'] ?? 587;
        $mail->CharSet = 'UTF-8';
        
        $fromEmail = $smtpSettings['from_email'] ?? $smtpSettings['username'];
        $fromName = $smtpSettings['from_name'] ?? ($settings['brand_name'] ?? DEFAULT_FROM_NAME);
        
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $content;
        
        // Create text version
        $textContent = strip_tags(str_replace(
            ['<br>', '<br/>', '<br />', '</p>', '</div>'], 
            "\n", 
            $content
        ));
        $mail->AltBody = $textContent;
        
        // Send email
        if (!$mail->send()) {
            throw new Exception('Failed to send email: ' . $mail->ErrorInfo);
        }
        
        // Log email to database
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (
                template_id,
                recipient,
                subject,
                content,
                sent_at,
                status
            ) VALUES (?, ?, ?, ?, NOW(), 'sent')
        ");
        
        $stmt->execute([
            $templateId,
            $user['email'],
            $subject,
            $content
        ]);
        
        // Log admin action
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (
                admin_id,
                action,
                entity_type,
                entity_id,
                details,
                ip_address,
                user_agent
            ) VALUES (?, 'send_email', 'user', ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['admin_id'],
            $userId,
            'Sent email: ' . $subject,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully to ' . $user['email']
        ]);
        
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        throw new Exception('Failed to send email: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>