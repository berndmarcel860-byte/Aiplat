<?php
/**
 * Email Helper Class
 * Handles email sending with tracking and dynamic variable replacement
 * 
 * Usage:
 * $emailHelper = new EmailHelper($pdo);
 * $emailHelper->sendEmail('onboarding_complete', $userId, $customVariables);
 */

// Load PHPMailer
$vendorPaths = [
    $_SERVER['DOCUMENT_ROOT'] . '/app/vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php'
];

foreach ($vendorPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    private $pdo;
    private $siteUrl;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        // Get site URL from system_settings
        $stmt = $pdo->query("SELECT site_url FROM system_settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->siteUrl = $settings['site_url'] ?? '';
    }
    
    /**
     * Send email using template
     * 
     * @param string $templateKey Template identifier
     * @param int $userId User ID
     * @param array $customVariables Additional variables to replace
     * @return bool Success status
     */
    public function sendEmail($templateKey, $userId, $customVariables = []) {
        try {
            // Get template
            $stmt = $this->pdo->prepare("SELECT * FROM email_templates WHERE template_key = ?");
            $stmt->execute([$templateKey]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                throw new Exception("Template not found: $templateKey");
            }
            
            // Get user data
            $stmt = $this->pdo->prepare("
                SELECT first_name, last_name, email, created_at 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception("User not found: $userId");
            }
            
            // Get system settings
            $stmt = $this->pdo->query("SELECT * FROM system_settings WHERE id = 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get payment methods (if exist)
            $stmt = $this->pdo->prepare("
                SELECT * FROM user_payment_methods 
                WHERE user_id = ? AND type = 'fiat' 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $bankAccount = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM user_payment_methods 
                WHERE user_id = ? AND type = 'crypto' 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $cryptoWallet = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Generate tracking token
            $trackingToken = md5(uniqid($user['email'], true));
            
            // Build variables array
            $variables = array_merge([
                // User data
                'user_first_name' => $user['first_name'],
                'user_last_name' => $user['last_name'],
                'first_name' => $user['first_name'], // Alias for template compatibility
                'last_name' => $user['last_name'], // Alias for template compatibility
                'user_email' => $user['email'],
                'user_created_at' => date('d.m.Y', strtotime($user['created_at'])),
                
                // System settings
                'brand_name' => $settings['brand_name'] ?? 'CryptoFinanz',
                'company_address' => $settings['company_address'] ?? '',
                'contact_email' => $settings['contact_email'] ?? '',
                'contact_phone' => $settings['contact_phone'] ?? '',
                'fca_reference_number' => $settings['fca_reference_number'] ?? '',
                'site_url' => $settings['site_url'] ?? $this->siteUrl,
                
                // Bank account (if exists)
                'has_bank_account' => $bankAccount ? 'yes' : 'no',
                'bank_name' => $bankAccount['bank_name'] ?? '',
                'account_holder' => $bankAccount['account_holder'] ?? '',
                'iban' => $bankAccount['iban'] ?? '',
                'bic' => $bankAccount['bic'] ?? '',
                
                // Crypto wallet (if exists)
                'has_crypto_wallet' => $cryptoWallet ? 'yes' : 'no',
                'cryptocurrency' => $cryptoWallet['cryptocurrency'] ?? '',
                'network' => $cryptoWallet['network'] ?? '',
                'wallet_address' => $cryptoWallet['wallet_address'] ?? '',
                
                // Tracking and date
                'tracking_token' => $trackingToken,
                'current_year' => date('Y'),
            ], $customVariables);
            
            // Replace variables in subject and content
            $subject = $this->replaceVariables($template['subject'], $variables);
            $content = $this->replaceVariables($template['content'], $variables);
            
            // Handle conditional blocks ({{#if}})
            $content = $this->handleConditionals($content, $variables);
            
            // Send email using PHPMailer
            $sent = $this->sendWithPHPMailer($user['email'], $subject, $content);
            
            // Log email
            $stmt = $this->pdo->prepare("
                INSERT INTO email_logs (
                    template_id, recipient, subject, content, 
                    tracking_token, status, sent_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $template['id'],
                $user['email'],
                $subject,
                $content,
                $trackingToken,
                $sent ? 'sent' : 'failed'
            ]);
            
            return $sent;
            
        } catch (Exception $e) {
            error_log("Email send error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Replace {variable} placeholders (single braces to match template format)
     */
    private function replaceVariables($text, $variables) {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }
    
    /**
     * Handle conditional blocks {#if variable}...{/if} (single braces to match template format)
     */
    private function handleConditionals($content, $variables) {
        // Simple if condition handler
        $pattern = '/\{#if\s+(\w+)\}(.*?)\{\/if\}/s';
        
        $content = preg_replace_callback($pattern, function($matches) use ($variables) {
            $varName = $matches[1];
            $block = $matches[2];
            
            // Check if variable exists and is truthy
            if (isset($variables[$varName]) && $variables[$varName] && $variables[$varName] !== 'no') {
                return $block;
            }
            return '';
        }, $content);
        
        return $content;
    }
    
    /**
     * Send email using PHPMailer
     */
    private function sendWithPHPMailer($to, $subject, $htmlContent) {
        // Get SMTP settings
        $stmt = $this->pdo->query("SELECT * FROM smtp_settings WHERE id = 1");
        $smtp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$smtp) {
            throw new Exception("SMTP settings not found");
        }
        
        $mail = new PHPMailer(true);
        
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->SMTPAuth = !empty($smtp['username']);
            $mail->Username = $smtp['username'];
            $mail->Password = $smtp['password'];
            $mail->SMTPSecure = $smtp['encryption'];
            $mail->Port = $smtp['port'];
            
            // Email configuration
            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $htmlContent;
            $mail->AltBody = strip_tags($htmlContent);
            
            // Send
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
}