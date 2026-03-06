<?php
/**
 * Email Template Helper Functions
 * Centralized email template management for FundTracer AI
 * 
 * Usage:
 *   require_once 'email_template_helper.php';
 *   $helper = new EmailTemplateHelper($pdo);
 *   $helper->sendTemplateEmail('user@example.com', 'inactive_user_reminder', ['first_name' => 'John']);
 */

class EmailTemplateHelper {
    private $pdo;
    private $defaultFromEmail = 'noreply@fundtracerai.com';
    private $defaultFromName = 'FundTracer AI';
    private $smtpSettings = null;
    private $phpMailerLoaded = false;
    private $systemSettings = null;
    private $siteUrl = '';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadPHPMailer();
        $this->loadSMTPSettings();
        $this->loadSystemSettings();
    }
    
    /**
     * Load PHPMailer library
     */
    private function loadPHPMailer() {
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $this->phpMailerLoaded = true;
            return;
        }
        
        $vendorPaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/app/vendor/autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../../vendor/autoload.php',
            dirname(__DIR__) . '/vendor/autoload.php'
        ];
        
        foreach ($vendorPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $this->phpMailerLoaded = true;
                    break;
                }
            }
        }
        
        if (!$this->phpMailerLoaded) {
            error_log("PHPMailer not found. Emails will use PHP mail() function.");
        }
    }
    
    /**
     * Load SMTP settings from database
     */
    private function loadSMTPSettings() {
        try {
            $stmt = $this->pdo->query("
                SELECT * FROM smtp_settings 
                WHERE is_active = 1 
                ORDER BY id DESC 
                LIMIT 1
            ");
            $this->smtpSettings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($this->smtpSettings) {
                // Update default from email/name from SMTP settings
                $this->defaultFromEmail = $this->smtpSettings['from_email'];
                $this->defaultFromName = $this->smtpSettings['from_name'];
            }
        } catch (PDOException $e) {
            error_log("Error loading SMTP settings: " . $e->getMessage());
            $this->smtpSettings = null;
        }
    }
    
    /**
     * Load system settings from database
     */
    private function loadSystemSettings() {
        try {
            $stmt = $this->pdo->query("
                SELECT * FROM system_settings 
                ORDER BY id DESC 
                LIMIT 1
            ");
            $this->systemSettings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set defaults if not found
            if (!$this->systemSettings) {
                $this->systemSettings = [
                    'brand_name' => 'Novalnet AI',
                    'site_url' => 'https://novalnet-ai.de',
                    'contact_email' => 'support@fundtracerai.com',
                    'contact_phone' => '',
                    'company_address' => 'Davidson House Forbury Square, Reading, RG1 3EU, UNITED KINGDOM',
                    'fca_reference_number' => '910584'
                ];
            }
        } catch (PDOException $e) {
            error_log("Error loading system settings: " . $e->getMessage());
            // Set defaults on error
            $this->systemSettings = [
                'brand_name' => 'FundTracer AI',
                'site_url' => 'https://fundtracerai.com',
                'contact_email' => 'support@fundtracerai.com',
                'contact_phone' => '',
                'company_address' => 'Davidson House Forbury Square, Reading, RG1 3EU, UNITED KINGDOM',
                'fca_reference_number' => '910584'
            ];
        }

        // Cache site URL for tracking pixel generation
        $this->siteUrl = rtrim($this->systemSettings['site_url'] ?? '', '/');
        if (empty($this->siteUrl)) {
            error_log("EmailTemplateHelper: system_settings.site_url is empty — tracking pixel URLs will be malformed.");
        }
    }
    
    /**
     * Get system settings
     */
    public function getSystemSettings() {
        return $this->systemSettings;
    }
    
    /**
     * Get current SMTP settings (useful for debugging)
     * 
     * @return array|null SMTP settings or null if not configured
     */
    public function getSMTPSettings() {
        return $this->smtpSettings;
    }
    
    /**
     * Get a template from the database by template_key
     * 
     * @param string $templateKey The unique template key
     * @return array|null Template data or null if not found
     */
    public function getTemplate($templateKey) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, template_key, subject, content, variables, created_at, updated_at
                FROM email_templates
                WHERE template_key = ?
                LIMIT 1
            ");
            $stmt->execute([$templateKey]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching template '{$templateKey}': " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Render a template with provided variables
     * 
     * @param string $templateKey The unique template key
     * @param array $variables Associative array of variables to replace
     * @return array|null Array with 'subject' and 'content' or null on error
     */
    public function renderTemplate($templateKey, $variables = []) {
        $template = $this->getTemplate($templateKey);
        
        if (!$template) {
            error_log("Template '{$templateKey}' not found");
            return null;
        }
        
        // Replace variables in subject and content
        $subject = $this->replaceVariables($template['subject'], $variables);
        $content = $this->replaceVariables($template['content'], $variables);
        
        // Wrap content in HTML email template
        $htmlContent = $this->wrapInEmailHTML($content, $subject);
        
        return [
            'subject' => $subject,
            'content' => $htmlContent,
            'plain_content' => strip_tags($content)
        ];
    }
    
    /**
     * Replace variables in template text
     * Supports both {{variable}} and {variable} syntax
     * 
     * @param string $text The text with variables
     * @param array $variables Associative array of variables
     * @return string Text with variables replaced
     */
    private function replaceVariables($text, $variables) {
        foreach ($variables as $key => $value) {
            // Support both {{variable}} and {variable} syntax
            $text = str_replace('{{' . $key . '}}', $value, $text);
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }
    
    /**
     * Wrap email content in professional HTML template
     * 
     * @param string $content The email content
     * @param string $title The email title
     * @return string Complete HTML email
     */
    private function wrapInEmailHTML($content, $title = '') {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #2950a8, #2da9e3);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .email-header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .email-body {
            padding: 30px;
            background-color: #ffffff;
        }
        .email-footer {
            background-color: #f9f9f9;
            color: #333;
            padding: 25px;
            text-align: left;
            font-size: 14px;
        }
        .email-footer p {
            margin: 5px 0;
        }
        .email-footer a {
            color: #007bff;
            text-decoration: none;
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
        a {
            color: #2950a8;
            text-decoration: none;
        }
        h2 {
            color: #2950a8;
            margin-top: 0;
        }
        h3 {
            color: #2950a8;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin-bottom: 8px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #2950a8, #2da9e3);
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 10px 0;
        }
        .button:hover {
            opacity: 0.9;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 20px;
            }
            .email-header {
                padding: 20px;
            }
            .signature img {
                height: 45px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🛡️ ' . htmlspecialchars($this->systemSettings['brand_name']) . '</h1>
            <p>AI-Powered Fund Recovery Platform</p>
        </div>
        <div class="email-body">
            ' . $content . '
        </div>
        <div class="email-footer">
            <p>Mit freundlichen Grüßen,</p>
            
            <div class="signature">
                <img src="' . htmlspecialchars($this->systemSettings['logo_url'] ?? 'https://novalnet-ai.de/assets/img/logo.png') . '" alt="' . htmlspecialchars($this->systemSettings['brand_name']) . ' Logo"><br>
                <strong>' . htmlspecialchars($this->systemSettings['brand_name']) . ' Team</strong><br>
                ' . htmlspecialchars($this->systemSettings['company_address'] ?? 'Davidson House Forbury Square, Reading, RG1 3EU, UNITED KINGDOM') . '<br>
                E: <a href="mailto:' . htmlspecialchars($this->systemSettings['contact_email']) . '">' . htmlspecialchars($this->systemSettings['contact_email']) . '</a> | 
                W: <a href="' . htmlspecialchars($this->systemSettings['site_url']) . '">' . htmlspecialchars($this->systemSettings['site_url']) . '</a>
                <p>
                    BaFin Reference Nr: ' . htmlspecialchars($this->systemSettings['fca_reference_number'] ?? '910584') . '<br>
                    <br>
                    <em>Hinweis:</em> Diese E-Mail kann vertrauliche oder rechtlich geschützte Informationen enthalten. 
                    Wenn Sie nicht der richtige Adressat sind, informieren Sie uns bitte und löschen Sie diese Nachricht.
                </p>
            </div>
        </div>
    </div>
    
    <div class="footer">
        &copy; ' . date('Y') . ' ' . htmlspecialchars($this->systemSettings['brand_name']) . '. Alle Rechte vorbehalten.
    </div>
</body>
</html>';
    }
    
    /**
     * Inject a 1×1 tracking pixel into an HTML email body.
     * The pixel calls track_email.php?token=TOKEN so that when the recipient
     * opens the email their mail client loads the pixel and the email_logs row
     * is updated to status='opened' with an opened_at timestamp.
     *
     * @param string $html  Complete HTML email body
     * @param string $token Unique tracking token (also stored in email_logs)
     * @return string HTML with tracking pixel appended before </body> (or at end)
     */
    private function injectTrackingPixel($html, $token) {
        $pixelUrl = $this->siteUrl . '/app/track_email.php?token=' . urlencode($token);
        $pixel = '<img src="' . htmlspecialchars($pixelUrl, ENT_QUOTES, 'UTF-8') . '" width="1" height="1" alt="" style="display:none;border:0;" />';
        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $pixel . '</body>', $html);
        }
        return $html . $pixel;
    }

    /**
     * Send email using a template
     * 
     * @param string $to Recipient email address
     * @param string $templateKey Template key to use
     * @param array $variables Variables for template
     * @param string $fromEmail Optional custom from email
     * @param string $fromName Optional custom from name
     * @return bool True on success, false on failure
     */
    public function sendTemplateEmail($to, $templateKey, $variables = [], $fromEmail = null, $fromName = null) {
        $rendered = $this->renderTemplate($templateKey, $variables);
        
        if (!$rendered) {
            error_log("Failed to render template '{$templateKey}' for {$to}");
            return false;
        }
        
        $fromEmail = $fromEmail ?: $this->defaultFromEmail;
        $fromName = $fromName ?: $this->defaultFromName;

        // Generate a unique tracking token and inject open-tracking pixel
        try {
            $trackingToken = bin2hex(random_bytes(16));
        } catch (\Exception $e) {
            // Fallback if OS cannot provide sufficient entropy
            $trackingToken = md5(uniqid($to, true));
        }
        $htmlWithPixel  = $this->injectTrackingPixel($rendered['content'], $trackingToken);
        
        // Use SMTP if available and configured
        if ($this->phpMailerLoaded && $this->smtpSettings) {
            $success = $this->sendViaSMTP($to, $rendered['subject'], $htmlWithPixel, $rendered['plain_content'], $fromEmail, $fromName);
        } else {
            // Fallback to PHP mail() function
            $success = $this->sendViaMailFunction($to, $rendered['subject'], $htmlWithPixel, $fromEmail, $fromName);
        }
        
        // Log the email with tracking token and rendered content
        $this->logEmail(
            $to,
            $rendered['subject'],
            $templateKey,
            $success ? 'sent' : 'failed',
            $variables['user_id'] ?? null,
            $trackingToken,
            $htmlWithPixel
        );

        if (!$success) {
            error_log("Failed to send email to {$to} using template '{$templateKey}'");
        }
        
        return $success;
    }
    
    /**
     * Send email via SMTP using PHPMailer
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $htmlContent HTML content
     * @param string $plainContent Plain text content
     * @param string $fromEmail From email address
     * @param string $fromName From name
     * @return bool True on success, false on failure
     */
    private function sendViaSMTP($to, $subject, $htmlContent, $plainContent, $fromEmail, $fromName) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = $this->smtpSettings['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpSettings['username'];
            $mail->Password = $this->smtpSettings['password'];
            $mail->SMTPSecure = $this->smtpSettings['encryption'] ?? 'tls';
            $mail->Port = $this->smtpSettings['port'] ?? 587;
            $mail->CharSet = 'UTF-8';
            
            // Email details
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlContent;
            $mail->AltBody = $plainContent;
            
            // Send email
            $mail->send();
            return true;
            
        } catch (\Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email via PHP mail() function (fallback)
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $htmlContent HTML content
     * @param string $fromEmail From email address
     * @param string $fromName From name
     * @return bool True on success, false on failure
     */
    private function sendViaMailFunction($to, $subject, $htmlContent, $fromEmail, $fromName) {
        // Prepare headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "X-Mailer: FundTracer AI\r\n";
        
        // Send email
        return mail($to, $subject, $htmlContent, $headers);
    }
    
    /**
     * Send bulk emails using a template
     * 
     * @param array $recipients Array of recipient data [['email' => '', 'variables' => []]]
     * @param string $templateKey Template key to use
     * @param int $batchSize Number of emails to send per batch (default 50)
     * @return array Statistics about sent emails
     */
    public function sendBulkTemplateEmail($recipients, $templateKey, $batchSize = 50) {
        $sent = 0;
        $failed = 0;
        $errors = [];
        
        // Process in batches
        $batches = array_chunk($recipients, $batchSize);
        
        foreach ($batches as $batch) {
            foreach ($batch as $recipient) {
                if (empty($recipient['email'])) {
                    $failed++;
                    $errors[] = "Missing email address";
                    continue;
                }
                
                $variables = $recipient['variables'] ?? [];
                $success = $this->sendTemplateEmail($recipient['email'], $templateKey, $variables);
                
                if ($success) {
                    $sent++;
                } else {
                    $failed++;
                    $errors[] = "Failed to send to {$recipient['email']}";
                }
            }
            
            // Small delay between batches to avoid rate limiting
            if (count($batches) > 1) {
                usleep(500000); // 0.5 second delay
            }
        }
        
        return [
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($recipients),
            'errors' => $errors
        ];
    }
    
    /**
     * Log email to database
     * 
     * @param string      $recipient      Email address
     * @param string      $subject        Email subject
     * @param string      $templateKey    Template key used
     * @param string      $status         Email status (sent/failed/delivered/opened)
     * @param int|null    $userId         User ID if available
     * @param string|null $trackingToken  Unique token injected as tracking pixel (null when failed before render)
     * @param string|null $content        Full HTML content sent (for audit/preview)
     */
    private function logEmail($recipient, $subject, $templateKey, $status = 'sent', $userId = null, $trackingToken = null, $content = null) {
        try {
            // Truncate content to TEXT column limit (65 535 chars) to avoid oversized inserts
            if ($content !== null && strlen($content) > 65535) {
                $content = substr($content, 0, 65532) . '…';
            }
            $stmt = $this->pdo->prepare("
                INSERT INTO email_logs 
                (recipient, subject, template_key, status, sent_at, user_id, tracking_token, content)
                VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)
            ");
            $stmt->execute([$recipient, $subject, $templateKey, $status, $userId, $trackingToken, $content]);
        } catch (PDOException $e) {
            error_log("Failed to log email: " . $e->getMessage());
        }
    }
    
    /**
     * Get all available templates
     * 
     * @return array List of all email templates
     */
    public function getAllTemplates() {
        try {
            $stmt = $this->pdo->query("
                SELECT template_key, subject, variables, created_at, updated_at
                FROM email_templates
                ORDER BY template_key ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all templates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validate template variables
     * 
     * @param string $templateKey Template key
     * @param array $providedVariables Variables being provided
     * @return array Array with 'valid' boolean and 'missing' array of missing variables
     */
    public function validateTemplateVariables($templateKey, $providedVariables) {
        $template = $this->getTemplate($templateKey);
        
        if (!$template) {
            return ['valid' => false, 'missing' => [], 'error' => 'Template not found'];
        }
        
        // Parse expected variables from template variables field
        $expectedVariables = json_decode($template['variables'], true);
        if (!is_array($expectedVariables)) {
            // Try comma-separated format
            $expectedVariables = array_map('trim', explode(',', $template['variables']));
        }
        
        // Find missing variables
        $missing = array_diff($expectedVariables, array_keys($providedVariables));
        
        return [
            'valid' => empty($missing),
            'missing' => array_values($missing),
            'expected' => $expectedVariables
        ];
    }
}

/**
 * Quick helper function for sending template emails
 * 
 * @param PDO $pdo Database connection
 * @param string $to Recipient email
 * @param string $templateKey Template key
 * @param array $variables Template variables
 * @return bool Success status
 */
function sendTemplateEmail($pdo, $to, $templateKey, $variables = []) {
    $helper = new EmailTemplateHelper($pdo);
    return $helper->sendTemplateEmail($to, $templateKey, $variables);
}