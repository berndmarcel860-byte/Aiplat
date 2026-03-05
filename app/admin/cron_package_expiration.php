<?php
/**
 * Cron Job: User Package Expiration Checker
 * 
 * This script should be run daily via cron to:
 * 1. Check all user packages for expiration
 * 2. Update status to 'expired' for packages past end_date
 * 3. Send email notification using 'trial_end' template for trial packages
 * 4. Log all status changes
 * 
 * Setup cron: Run every hour or daily
 * 0 * * * * /usr/bin/php /path/to/cron_package_expiration.php
 * OR
 * 0 0 * * * /usr/bin/php /path/to/cron_package_expiration.php
 */

// Use absolute path for config based on server structure
$rootPath = $_SERVER['DOCUMENT_ROOT'] . '/app';
if (file_exists($rootPath . '/config.php')) {
    require_once $rootPath . '/config.php';
} else {
    require_once __DIR__ . '/../config.php';
}

// Try multiple paths for vendor autoload (needed for PHPMailer)
$vendorPaths = [
    $_SERVER['DOCUMENT_ROOT'] . '/app/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php'
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
    error_log('Package Expiration Cron: PHPMailer not found - emails will not be sent');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Log start
error_log("Package Expiration Cron: Starting at " . date('Y-m-d H:i:s'));

try {
    // Get current date
    $now = date('Y-m-d');
    
    // Find all user packages with 'active' or 'pending' status where end_date has passed
    $stmt = $pdo->prepare("
        SELECT 
            up.id as user_package_id,
            up.user_id,
            up.package_id,
            up.status,
            up.end_date,
            u.email,
            u.first_name,
            u.last_name,
            p.name as package_name
        FROM user_packages up
        JOIN users u ON up.user_id = u.id
        JOIN packages p ON up.package_id = p.id
        WHERE up.status IN ('active', 'pending')
        AND DATE(up.end_date) < DATE(:now)
    ");
    $stmt->execute([':now' => $now]);
    $expiredPackages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = count($expiredPackages);
    error_log("Package Expiration Cron: Found {$count} expired packages to update");
    
    if ($count === 0) {
        error_log("Package Expiration Cron: No expired packages found. Exiting.");
        exit(0);
    }
    
    // Update each expired package
    $updatedCount = 0;
    $updateStmt = $pdo->prepare("
        UPDATE user_packages 
        SET status = 'expired', updated_at = NOW() 
        WHERE id = ?
    ");
    
    foreach ($expiredPackages as $package) {
        try {
            $updateStmt->execute([$package['user_package_id']]);
            $updatedCount++;
            
            error_log("Package Expiration Cron: Updated package ID {$package['user_package_id']} for user {$package['email']} (Package: {$package['package_name']}, End Date: {$package['end_date']})");
            
            // Send trial_end email notification
            if ($autoloadFound) {
                sendTrialEndEmail($pdo, $package);
            }
            
            // Log to admin_logs if table exists
            try {
                $logStmt = $pdo->prepare("
                    INSERT INTO admin_logs (admin_id, action, details, ip_address, created_at)
                    VALUES (0, 'package_expired', ?, '127.0.0.1', NOW())
                ");
                $logDetails = json_encode([
                    'user_package_id' => $package['user_package_id'],
                    'user_id' => $package['user_id'],
                    'user_email' => $package['email'],
                    'package_name' => $package['package_name'],
                    'end_date' => $package['end_date'],
                    'previous_status' => $package['status'],
                    'new_status' => 'expired',
                    'updated_by' => 'cron_package_expiration'
                ]);
                $logStmt->execute([$logDetails]);
            } catch (PDOException $logError) {
                // Log table might have different structure, continue anyway
                error_log("Package Expiration Cron: Could not log to admin_logs - " . $logError->getMessage());
            }
            
        } catch (PDOException $e) {
            error_log("Package Expiration Cron: Failed to update package ID {$package['user_package_id']} - " . $e->getMessage());
        }
    }
    
    error_log("Package Expiration Cron: Successfully updated {$updatedCount} of {$count} expired packages");
    error_log("Package Expiration Cron: Completed at " . date('Y-m-d H:i:s'));
    
    exit(0);
    
} catch (PDOException $e) {
    error_log("Package Expiration Cron Error: Database error - " . $e->getMessage());
    exit(1);
} catch (Exception $e) {
    error_log("Package Expiration Cron Error: " . $e->getMessage());
    exit(1);
}

/**
 * Send email notification when trial/package expires using the 'trial_end' template
 * 
 * @param PDO $pdo Database connection
 * @param array $package Package data with user info
 * @return bool Success or failure
 */
function sendTrialEndEmail($pdo, $package) {
    try {
        // Get the 'trial_end' template from database
        $templateStmt = $pdo->prepare("SELECT * FROM email_templates WHERE template_key = ? LIMIT 1");
        $templateStmt->execute(['trial_end']);
        $template = $templateStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            error_log("Package Expiration Cron: Email template 'trial_end' not found in database");
            return false;
        }
        
        // Get SMTP settings
        $stmt = $pdo->query("SELECT * FROM smtp_settings LIMIT 1");
        $smtpSettings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$smtpSettings) {
            error_log("Package Expiration Cron: SMTP settings not configured");
            return false;
        }
        
        // Get system settings for site info
        $stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $siteUrl = $settings['site_url'] ?? 'https://kryptox.co.uk';
        $siteName = $settings['brand_name'] ?? 'KryptoX';
        $contactEmail = $settings['contact_email'] ?? 'info@kryptox.co.uk';
        $contactPhone = $settings['contact_phone'] ?? '';
        
        // Get user balance
        $balanceStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $balanceStmt->execute([$package['user_id']]);
        $userData = $balanceStmt->fetch(PDO::FETCH_ASSOC);
        $balance = $userData['balance'] ?? 0;
        
        // Prepare variables for replacement
        $variables = [
            // User variables
            'first_name' => $package['first_name'],
            'last_name' => $package['last_name'],
            'email' => $package['email'],
            'user_id' => $package['user_id'],
            'full_name' => $package['first_name'] . ' ' . $package['last_name'],
            'balance' => number_format($balance, 2),
            
            // Package variables
            'package_name' => $package['package_name'],
            'end_date' => date('d.m.Y', strtotime($package['end_date'])),
            
            // Site variables
            'site_url' => $siteUrl,
            'surl' => $siteUrl,
            'site_name' => $siteName,
            'sbrand' => $siteName,
            'contact_email' => $contactEmail,
            'semail' => $contactEmail,
            'contact_phone' => $contactPhone,
            'sphone' => $contactPhone
        ];
        
        // Replace variables in subject and content
        $subject = $template['subject'];
        $content = $template['content'];
        
        foreach ($variables as $key => $value) {
            $subject = str_replace(['{' . $key . '}', '{{' . $key . '}}'], $value, $subject);
            $content = str_replace(['{' . $key . '}', '{{' . $key . '}}'], $value, $content);
        }
        
        // Configure PHPMailer
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtpSettings['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtpSettings['username'];
        $mail->Password = $smtpSettings['password'];
        $mail->SMTPSecure = $smtpSettings['encryption'] ?? 'tls';
        $mail->Port = $smtpSettings['port'] ?? 587;
        $mail->CharSet = 'UTF-8';
        
        $fromEmail = $smtpSettings['from_email'] ?? $smtpSettings['username'];
        $fromName = $smtpSettings['from_name'] ?? $siteName;
        
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($package['email'], $package['first_name'] . ' ' . $package['last_name']);
        $mail->isHTML(true);
        
        $mail->Subject = $subject;
        $mail->Body = $content;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $content));
        
        if ($mail->send()) {
            // Log email to email_logs table
            try {
                $logStmt = $pdo->prepare("
                    INSERT INTO email_logs (user_id, recipient, subject, content, template_id, sent_at, status)
                    VALUES (?, ?, ?, ?, ?, NOW(), 'sent')
                ");
                $logStmt->execute([
                    $package['user_id'],
                    $package['email'],
                    $subject,
                    $content,
                    $template['id']
                ]);
            } catch (PDOException $logError) {
                // Try simpler insert if columns differ
                try {
                    $logStmt = $pdo->prepare("
                        INSERT INTO email_logs (recipient, subject, content, sent_at, status)
                        VALUES (?, ?, ?, NOW(), 'sent')
                    ");
                    $logStmt->execute([$package['email'], $subject, $content]);
                } catch (PDOException $e) {
                    error_log("Package Expiration Cron: Could not log email - " . $e->getMessage());
                }
            }
            
            error_log("Package Expiration Cron: Trial end email sent to " . $package['email']);
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Package Expiration Cron: Failed to send trial end email to " . $package['email'] . " - " . $e->getMessage());
        return false;
    }
}