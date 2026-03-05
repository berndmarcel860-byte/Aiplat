<?php
// tracking.php
function trackUserActivity($userId, $pageUrl, $httpMethod = 'GET') {
    global $pdo;

    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $referrer  = $_SERVER['HTTP_REFERER'] ?? null;

        $stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, page_url, http_method, ip_address, user_agent, referrer)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $pageUrl, $httpMethod, $ipAddress, $userAgent, $referrer]);

        return true;
    } catch (PDOException $e) {
        error_log('Tracking error: ' . $e->getMessage());
        return false;
    }
}
?>

