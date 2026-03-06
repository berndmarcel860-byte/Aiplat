<?php
/**
 * AdminEmailHelper Class
 * Centralized email handling for admin backend with comprehensive variable support
 * 
 * This class provides a unified interface for sending emails from admin panels,
 * automatically fetching and replacing 42+ variables from multiple database tables.
 * 
 * FEATURES:
 * - Template-based emails (uses email_templates table)
 * - Direct HTML emails (for admin-customized content)
 * - Automatic variable fetching from 6 database tables
 * - Email tracking support
 * - Professional HTML wrapping (matching email_template_helper.php)
 * - Error handling and logging
 * - Logo support in email headers
 * - Responsive email design
 * 
 * USAGE:
 * require_once 'AdminEmailHelper.php';
 * $emailHelper = new AdminEmailHelper($pdo);
 * 
 * // Send template email
 * $emailHelper->sendTemplateEmail('kyc_approved', $userId);
 * 
 * // Send direct HTML email
 * $subject = "Welcome {first_name}!";
 * $body = "<p>Hello {first_name} {last_name}, your balance is {balance}.</p>";
 * $emailHelper->sendDirectEmail($userId, $subject, $body);
 * 
 * AVAILABLE VARIABLES (42+):
 * User Data: {user_id}, {first_name}, {last_name}, {full_name}, {email}, {balance}, {status}, etc.
 * Company: {brand_name}, {company_address}, {contact_email}, {contact_phone}, {fca_reference_number}, {logo_url}, etc.
 * Bank Account: {has_bank_account}, {bank_name}, {account_holder}, {iban}, {bic}, {bank_country}
 * Crypto Wallet: {has_crypto_wallet}, {cryptocurrency}, {network}, {wallet_address}
 * Onboarding: {onboarding_completed}, {onboarding_step}
 * Cases: {case_number}, {case_status}, {case_title}, {case_amount}
 * System: {current_year}, {current_date}, {current_time}, {dashboard_url}, {login_url}
 */

// Load PHPMailer
$vendorPaths = [
    $_SERVER['DOCUMENT_ROOT'] . '/app/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php'
];

foreach ($vendorPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AdminEmailHelper {
    private $pdo;
    private $siteUrl;
    private $brandName;
    
    /**
     * Constructor
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Load system settings
        $stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->siteUrl = $settings['site_url'] ?? 'https://cryptofinanze.de';
        $this->brandName = $settings['brand_name'] ?? 'CryptoFinanz';
    }
    
    /**
     * Send email using template from email_templates table
     * 
     * @param string $templateKey Template identifier (e.g., 'kyc_approved')
     * @param int $userId User ID to send email to
     * @param array $customVars Additional custom variables
     * @return bool True on success, false on failure
     */
    public function sendTemplateEmail($templateKey, $userId, $customVars = []) {
        try {
            // Get template
            $stmt = $this->pdo->prepare("SELECT * FROM email_templates WHERE template_key = ?");
            $stmt->execute([$templateKey]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                throw new Exception("Template not found: $templateKey");
            }
            
            // Get all variables
            $variables = $this->getAllVariables($userId, $customVars);
            
            // Replace variables in template
            $subject = $this->replaceVariables($template['subject'], $variables);
            $htmlBody = $this->replaceVariables($template['content'], $variables);
            
            // Wrap in professional template if not already a complete HTML document
            // Templates from database contain only content HTML, so they need wrapping
            if (strpos($htmlBody, '<!DOCTYPE') === false && strpos($htmlBody, '<html') === false) {
                $htmlBody = $this->wrapInTemplate($subject, $htmlBody, $variables);
            }
            
            // Send email
            return $this->sendEmail($userId, $subject, $htmlBody, $variables);
            
        } catch (Exception $e) {
            error_log("AdminEmailHelper - Template email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send direct HTML email (not using template)
     * Perfect for admin-customized emails
     * 
     * @param int $userId User ID to send email to
     * @param string $subject Email subject (can contain {variables})
     * @param string $htmlBody Email body HTML (can contain {variables})
     * @param array $customVars Additional custom variables
     * @return bool True on success, false on failure
     */
    public function sendDirectEmail($userId, $subject, $htmlBody, $customVars = []) {
        try {
            // Get all variables
            $variables = $this->getAllVariables($userId, $customVars);
            
            // Replace variables
            $subject = $this->replaceVariables($subject, $variables);
            $htmlBody = $this->replaceVariables($htmlBody, $variables);
            
            // Wrap in professional template if not already wrapped
            if (strpos($htmlBody, '<!DOCTYPE') === false && strpos($htmlBody, '<html') === false) {
                $htmlBody = $this->wrapInTemplate($subject, $htmlBody, $variables);
            }
            
            // Send email
            return $this->sendEmail($userId, $subject, $htmlBody, $variables);
            
        } catch (Exception $e) {
            error_log("AdminEmailHelper - Direct email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all available variables for a user
     * Fetches data from all relevant database tables
     * 
     * @param int $userId User ID
     * @param array $customVars Additional custom variables to merge
     * @return array Associative array of all variables
     */
    public function getAllVariables($userId, $customVars = []) {
        try {
            // 1. Get user data
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception("User not found: $userId");
            }
            
            // 2. Get system settings
            $stmt = $this->pdo->query("SELECT * FROM system_settings WHERE id = 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            
            // 3. Get bank account
            $stmt = $this->pdo->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? AND type = 'fiat' LIMIT 1");
            $stmt->execute([$userId]);
            $bankAccount = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // 4. Get crypto wallet
            $stmt = $this->pdo->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? AND type = 'crypto' LIMIT 1");
            $stmt->execute([$userId]);
            $cryptoWallet = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // 5. Get onboarding data
            $stmt = $this->pdo->prepare("SELECT * FROM user_onboarding WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $onboarding = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // 6. Get latest case
            $stmt = $this->pdo->prepare("SELECT * FROM cases WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$userId]);
            $latestCase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Build comprehensive variables array
            $variables = [
                // User data (12 variables)
                'user_id' => $user['id'],
                'first_name' => htmlspecialchars($user['first_name'] ?? ''),
                'last_name' => htmlspecialchars($user['last_name'] ?? ''),
                'full_name' => htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
                'email' => htmlspecialchars($user['email'] ?? ''),
                'balance' => number_format($user['balance'] ?? 0, 2, ',', '.') . ' €',
                'status' => htmlspecialchars($user['status'] ?? ''),
                'created_at' => isset($user['created_at']) ? date('d.m.Y', strtotime($user['created_at'])) : '',
                'member_since' => isset($user['created_at']) ? date('d.m.Y', strtotime($user['created_at'])) : '',
                'user_created_at' => isset($user['created_at']) ? date('d.m.Y H:i', strtotime($user['created_at'])) : '',
                'is_verified' => ($user['is_verified'] ?? 0) ? 'Ja' : 'Nein',
                'kyc_status' => htmlspecialchars($user['kyc_status'] ?? 'pending'),
                
                // Company/System settings (9 variables)
                'site_name' => htmlspecialchars($settings['brand_name'] ?? $this->brandName),
                'brand_name' => htmlspecialchars($settings['brand_name'] ?? $this->brandName),
                'site_url' => htmlspecialchars($settings['site_url'] ?? $this->siteUrl),
                'contact_email' => htmlspecialchars($settings['contact_email'] ?? 'info@cryptofinanze.de'),
                'contact_phone' => htmlspecialchars($settings['contact_phone'] ?? ''),
                'company_address' => htmlspecialchars($settings['company_address'] ?? 'Davidson House Forbury Square, Reading, RG1 3EU, UNITED KINGDOM'),
                'fca_reference_number' => htmlspecialchars($settings['fca_reference_number'] ?? '910584'),
                'fca_reference' => htmlspecialchars($settings['fca_reference_number'] ?? '910584'),
                'logo_url' => htmlspecialchars($settings['logo_url'] ?? 'https://novalnet-ai.de/assets/img/logo.png'),
                
                // Bank account (6 variables)
                'has_bank_account' => $bankAccount ? 'yes' : 'no',
                'bank_name' => htmlspecialchars($bankAccount['bank_name'] ?? ''),
                'account_holder' => htmlspecialchars($bankAccount['account_holder'] ?? ''),
                'iban' => htmlspecialchars($bankAccount['iban'] ?? ''),
                'bic' => htmlspecialchars($bankAccount['bic'] ?? ''),
                'bank_country' => htmlspecialchars($bankAccount['country'] ?? ''),
                
                // Crypto wallet (4 variables)
                'has_crypto_wallet' => $cryptoWallet ? 'yes' : 'no',
                'cryptocurrency' => htmlspecialchars($cryptoWallet['cryptocurrency'] ?? ''),
                'network' => htmlspecialchars($cryptoWallet['network'] ?? ''),
                'wallet_address' => htmlspecialchars($cryptoWallet['wallet_address'] ?? ''),
                
                // Onboarding (2 variables)
                'onboarding_completed' => ($onboarding && ($onboarding['completed'] ?? 0)) ? 'Ja' : 'Nein',
                'onboarding_step' => htmlspecialchars($onboarding['current_step'] ?? ''),
                
                // Cases (4 variables)
                'case_number' => htmlspecialchars($latestCase['case_number'] ?? ''),
                'case_status' => htmlspecialchars($latestCase['status'] ?? ''),
                'case_title' => htmlspecialchars($latestCase['title'] ?? ''),
                'case_amount' => isset($latestCase['amount']) ? number_format($latestCase['amount'], 2, ',', '.') . ' €' : '',
                
                // System/Dynamic (5 variables)
                'current_year' => date('Y'),
                'current_date' => date('d.m.Y'),
                'current_time' => date('H:i'),
                'dashboard_url' => htmlspecialchars($settings['site_url'] ?? $this->siteUrl) . '/dashboard',
                'login_url' => htmlspecialchars($settings['site_url'] ?? $this->siteUrl) . '/login.php',
            ];
            
            // Merge custom variables
            foreach ($customVars as $key => $value) {
                $variables[$key] = htmlspecialchars($value);
            }
            
            return $variables;
            
        } catch (Exception $e) {
            error_log("AdminEmailHelper - Get variables error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Replace variables in content
     * Handles both {variable} and {{variable}} formats
     * 
     * @param string $content Content with variables
     * @param array $variables Variables to replace
     * @return string Content with replaced variables
     */
    public function replaceVariables($content, $variables) {
        foreach ($variables as $key => $value) {
            // Replace {variable} format
            $content = str_replace('{' . $key . '}', $value, $content);
            // Replace {{variable}} format
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }
    
    /**
     * Wrap HTML content in professional email template
     * Updated to match email_template_helper.php structure
     * Supports custom headers from templates using <!-- CUSTOM_HEADER --> marker
     * 
     * @param string $subject Email subject
     * @param string $body Email body content
     * @param array $variables Variables for template
     * @return string Complete HTML email
     */
    private function wrapInTemplate($subject, $body, $variables) {
        $firstName = $variables['first_name'] ?? '';
        $lastName = $variables['last_name'] ?? '';
        $brandName = $variables['brand_name'] ?? $this->brandName;
        $siteUrl = $variables['site_url'] ?? $this->siteUrl;
        $contactEmail = $variables['contact_email'] ?? 'info@cryptofinanze.de';
        $companyAddress = $variables['company_address'] ?? 'Davidson House Forbury Square, Reading, RG1 3EU, UNITED KINGDOM';
        $fcaReference = $variables['fca_reference_number'] ?? '910584';
        $logoUrl = $variables['logo_url'] ?? 'https://kryptox.co.uk/assets/img/logo.png';
        
        // Check if template has custom header (marked with <!-- CUSTOM_HEADER -->)
        $hasCustomHeader = (strpos($body, '<!-- CUSTOM_HEADER -->') !== false);
        
        // Remove the marker if present
        $body = str_replace('<!-- CUSTOM_HEADER -->', '', $body);
        
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
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
    <div class="container">' . 
        // Only add default header if template doesn't have custom header
        (!$hasCustomHeader ? '
        <div class="header">
            <h1>🛡️ ' . htmlspecialchars($brandName) . '</h1>
            <p>AI-Powered Fund Recovery Platform</p>
        </div>' : '') . '
        <div class="content">
            ' . $body . '
        </div>
        <div class="footer">
            
            <div class="signature">
                <img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($brandName) . ' Logo"><br>
                <strong>' . htmlspecialchars($brandName) . ' Team</strong><br>
                ' . htmlspecialchars($companyAddress) . '<br>
                E: <a href="mailto:' . htmlspecialchars($contactEmail) . '">' . htmlspecialchars($contactEmail) . '</a> | 
                W: <a href="' . htmlspecialchars($siteUrl) . '">' . htmlspecialchars($siteUrl) . '</a>
                <p>
                    FCA Reference Nr: ' . htmlspecialchars($fcaReference) . '<br>
                    <br>
                    <em>Hinweis:</em> Diese E-Mail kann vertrauliche oder rechtlich geschützte Informationen enthalten. 
                    Wenn Sie nicht der richtige Adressat sind, informieren Sie uns bitte und löschen Sie diese Nachricht.
                </p>
            </div>
        </div>
    </div>
    
    <div class="footer">
        &copy; ' . date('Y') . ' ' . htmlspecialchars($brandName) . '. Alle Rechte vorbehalten.
    </div>
</body>
</html>';
    }
    
    /**
     * Internal method to send email via SMTP
     * 
     * @param int $userId User ID
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @param array $variables Variables (for logging)
     * @return bool Success status
     */
    private function sendEmail($userId, $subject, $htmlBody, $variables) {
        try {
            // Get user email
            $stmt = $this->pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid user or email");
            }
            
            // Get SMTP settings
            $stmt = $this->pdo->query("SELECT * FROM smtp_settings WHERE id = 1");
            $smtp = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$smtp) {
                throw new Exception("SMTP settings not configured");
            }
            
            // Configure PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['username'];
            $mail->Password = $smtp['password'];
            $mail->SMTPSecure = $smtp['encryption'] ?? 'tls';
            $mail->Port = $smtp['port'] ?? 587;
            $mail->CharSet = 'UTF-8';
            
            $mail->setFrom(
                $smtp['from_email'] ?? $smtp['username'], 
                $smtp['from_name'] ?? $this->brandName
            );
            $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));
            
            // Send email
            $mail->send();
            
            // Log email
            $logStmt = $this->pdo->prepare("INSERT INTO email_logs (recipient, subject, content, sent_at, status) VALUES (?, ?, ?, NOW(), 'sent')");
            $logStmt->execute([$user['email'], $subject, $htmlBody]);
            
            // Log admin action if admin session exists
            if (isset($_SESSION['admin_id'])) {
                $adminLogStmt = $this->pdo->prepare("INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, created_at) VALUES (?, 'send_email', 'user', ?, ?, ?, NOW())");
                $adminLogStmt->execute([
                    $_SESSION['admin_id'],
                    $userId,
                    'Sent email: ' . $subject,
                    $_SERVER['REMOTE_ADDR'] ?? ''
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("AdminEmailHelper - Send error: " . $e->getMessage());
            
            // Log failed email
            try {
                $logStmt = $this->pdo->prepare("INSERT INTO email_logs (recipient, subject, content, sent_at, status, error_message) VALUES (?, ?, ?, NOW(), 'failed', ?)");
                $logStmt->execute([
                    $user['email'] ?? 'unknown',
                    $subject,
                    $htmlBody,
                    $e->getMessage()
                ]);
            } catch (Exception $logError) {
                error_log("AdminEmailHelper - Log error: " . $logError->getMessage());
            }
            
            return false;
        }
    }

    /**
     * Send an email notification directly to the admin contact address
     * configured in system_settings (contact_email).
     *
     * @param string $subject   Email subject
     * @param string $htmlBody  Full HTML email body
     * @return bool True on success
     */
    public function sendAdminNotification($subject, $htmlBody) {
        $adminEmail = '';
        try {
            $stmt = $this->pdo->query("SELECT contact_email, brand_name FROM system_settings WHERE id = 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            $adminEmail = $settings['contact_email'] ?? '';
            $brandName  = $settings['brand_name'] ?? $this->brandName;

            if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Admin contact email not configured or invalid");
            }

            $stmt = $this->pdo->query("SELECT * FROM smtp_settings WHERE id = 1");
            $smtp = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$smtp) {
                throw new Exception("SMTP settings not configured");
            }

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['username'];
            $mail->Password   = $smtp['password'];
            $mail->SMTPSecure = $smtp['encryption'] ?? 'tls';
            $mail->Port       = $smtp['port'] ?? 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(
                $smtp['from_email'] ?? $smtp['username'],
                $smtp['from_name'] ?? $brandName
            );
            $mail->addAddress($adminEmail, $brandName . ' Admin');
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

            $mail->send();

            $logStmt = $this->pdo->prepare("INSERT INTO email_logs (recipient, subject, content, sent_at, status) VALUES (?, ?, ?, NOW(), 'sent')");
            $logStmt->execute([$adminEmail, $subject, $htmlBody]);

            return true;

        } catch (Exception $e) {
            error_log("AdminEmailHelper - sendAdminNotification error: " . $e->getMessage());
            try {
                $logStmt = $this->pdo->prepare("INSERT INTO email_logs (recipient, subject, content, sent_at, status, error_message) VALUES (?, ?, ?, NOW(), 'failed', ?)");
                $logStmt->execute([$adminEmail ?? 'unknown', $subject, $htmlBody, $e->getMessage()]);
            } catch (Exception $logError) {
                error_log("AdminEmailHelper - Log error: " . $logError->getMessage());
            }
            return false;
        }
    }
}