<?php
// track.php
require_once 'config.php'; // Database connection

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    try {
        // 1️⃣ Update email_logs (mark as opened)
        $stmt = $pdo->prepare("
            UPDATE email_logs 
            SET status = 'opened', opened_at = NOW()
            WHERE tracking_token = ? AND opened_at IS NULL
        ");
        $stmt->execute([$token]);

        // 2️⃣ Collect tracking data
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $referrer  = $_SERVER['HTTP_REFERER'] ?? '';

        // 3️⃣ Log detailed opens into email_tracking
        $trackingStmt = $pdo->prepare("
            INSERT INTO email_tracking (tracking_token, ip_address, user_agent, referrer, opened_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $trackingStmt->execute([$token, $ipAddress, $userAgent, $referrer]);
    } catch (Exception $e) {
        error_log("Email tracking error: " . $e->getMessage());
    }

    // 4️⃣ Return 1x1 transparent GIF
    header('Content-Type: image/gif');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
}

// Default fallback if token missing
header('HTTP/1.1 400 Bad Request');
echo "Missing tracking token.";
exit;
?>

