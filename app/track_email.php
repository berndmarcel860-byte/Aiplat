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
    // Database connection
    require_once __DIR__ . '/config.php';

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referrer   = $_SERVER['HTTP_REFERER']   ?? '';

    // Step 1: Update email_logs.
    // Try the full UPDATE first (requires opened_at column).
    // If the column does not exist yet (migration not applied), fall back to
    // updating only the status so the open event is still recorded.
    try {
        $stmt = $pdo->prepare("
            UPDATE email_logs
            SET status = 'opened', opened_at = NOW()
            WHERE tracking_token = ?
              AND status != 'opened'
        ");
        $stmt->execute([$token]);
    } catch (Exception $e) {
        error_log("Email tracking (full update) error: " . $e->getMessage());
        // Fallback: update only status (opened_at column may not exist yet)
        try {
            $stmt = $pdo->prepare("
                UPDATE email_logs
                SET status = 'opened'
                WHERE tracking_token = ?
                  AND status != 'opened'
            ");
            $stmt->execute([$token]);
        } catch (Exception $e2) {
            error_log("Email tracking (fallback update) error: " . $e2->getMessage());
        }
    }

    // Step 2: Insert a row into the open-event log table (independent of step 1).
    try {
        $stmt = $pdo->prepare("
            INSERT INTO email_tracking (tracking_token, ip_address, user_agent, referrer, opened_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$token, $ip_address, $user_agent, $referrer]);
    } catch (Exception $e) {
        // Table may not exist yet — non-fatal, email_logs is the primary record.
        error_log("Email tracking (insert) error: " . $e->getMessage());
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