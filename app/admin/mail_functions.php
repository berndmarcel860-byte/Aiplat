<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $pdo;
    private $mail;
    private $systemSettings;
    private $smtpSettings;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->mail = new PHPMailer(true);
        $this->loadSettings();
        $this->configureMailer();
    }

    private function loadSettings() {
        // Load SMTP settings
        $stmt = $this->pdo->query("SELECT * FROM smtp_settings LIMIT 1");
        $this->smtpSettings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$this->smtpSettings) {
            throw new Exception('SMTP settings not configured');
        }

        // Load system settings
        $stmt = $this->pdo->query("SELECT * FROM system_settings LIMIT 1");
        $this->systemSettings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function configureMailer() {
        $this->mail->isSMTP();
        $this->mail->Host = $this->smtpSettings['host'];
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->smtpSettings['username'];
        $this->mail->Password = $this->smtpSettings['password'];
        $this->mail->SMTPSecure = $this->smtpSettings['encryption'] ?? 'tls';
        $this->mail->Port = $this->smtpSettings['port'] ?? 587;
        $this->mail->CharSet = 'UTF-8';
        
        $fromEmail = $this->smtpSettings['from_email'] 
                   ?? $this->smtpSettings['username']
                   ?? $this->systemSettings['contact_email']
                   ?? 'noreply@blockchainfahndung.com';
                   
        $fromName = $this->smtpSettings['from_name']
                  ?? $this->systemSettings['brand_name']
                  ?? 'Kryptosuchmaschine';
                  
        $this->mail->setFrom($fromEmail, $fromName);
    }

    private function replaceVariables(string $content, array $variables): string {
        // Default system variables
        $defaults = [
            'surl' => $this->systemSettings['site_url'] ?? 'https://blockchainfahndung.com/app',
            'sbrand' => $this->systemSettings['brand_name'] ?? 'Kryptosuchmaschine',
            'sphone' => $this->systemSettings['contact_phone'] ?? '41415041387',
            'semail' => $this->systemSettings['contact_email'] ?? 'info@blockchainfahndung.com'
        ];

        // Merge with provided variables
        $allVars = array_merge($defaults, $variables);

        // Replace all variable formats
        foreach ($allVars as $key => $value) {
            $content = str_replace(
                ["{{$key}}", "{{$key}}", "{$key}"], 
                $value, 
                $content
            );
        }

        return $content;
    }

    public function sendTemplateEmail(string $templateKey, string $recipientEmail, array $variables = []): bool {
        try {
            // Validate email
            if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address: $recipientEmail");
            }

            // Get template
            $stmt = $this->pdo->prepare("SELECT subject, content FROM email_templates WHERE template_key = ?");
            $stmt->execute([$templateKey]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                throw new Exception("Template '$templateKey' not found");
            }

            // Process content
            $template['subject'] = $this->replaceVariables($template['subject'], $variables);
            $template['content'] = $this->replaceVariables($template['content'], $variables);

            // Configure email
            $this->mail->clearAddresses();
            $this->mail->clearReplyTos();
            $this->mail->addAddress($recipientEmail);
            $this->mail->isHTML(true);
            $this->mail->Subject = $template['subject'];
            $this->mail->Body = $template['content'];
            
            // Create text version
            $textContent = strip_tags(str_replace(
                ['<br>', '<br/>', '<br />', '</p>', '</div>'], 
                "\n", 
                $template['content']
            ));
            $this->mail->AltBody = $textContent;

            // Send and log
            error_log("Sending email to $recipientEmail using template $templateKey");
            
            if (!$this->mail->send()) {
                throw new Exception("Mailer Error: " . $this->mail->ErrorInfo);
            }

            return true;
        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            return false;
        }
    }
}