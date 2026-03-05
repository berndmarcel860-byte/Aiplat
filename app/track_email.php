<?php
/**
 * Email Tracking Pixel Handler
 * Tracks when emails are opened by recipients
 * 
 * Usage: Include as 1x1 pixel in email HTML:
 * <img src="https://yourdomain.com/track_email.php?token=TRACKING_TOKEN" width="1" height="1" alt="" />
 */

// Suppress any output
ini_set('display_errors', 0);
error_reporting(0);

// Get tracking token from URL
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!empty($token)) {
    try {
        // Database connection
        require_once __DIR__ . '/config.php';
        
        // Get client information
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Update email_logs to mark as opened
        $stmt = $pdo->prepare("
            UPDATE email_logs 
            SET status = 'opened', 
                opened_at = NOW() 
            WHERE tracking_token = ? 
            AND status != 'opened'
        ");
        $stmt->execute([$token]);
        
        // Insert tracking record
        $stmt = $pdo->prepare("
            INSERT INTO email_tracking (tracking_token, ip_address, user_agent, referrer, opened_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$token, $ip_address, $user_agent, $referrer]);
        
    } catch (Exception $e) {
        // Silent fail - don't break email display
        error_log("Email tracking error: " . $e->getMessage());
    }
}

// Output 1x1 transparent GIF
header('Content-Type: image/gif');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 1x1 transparent GIF (43 bytes)
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
exit;