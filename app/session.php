<?php
require_once 'config.php';

// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 1800); // 30 minutes

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-login via remember cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember'])) {
    $token = $_COOKIE['remember'];
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, last_name 
        FROM users 
        WHERE remember_token = ? AND token_expiry > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['last_activity'] = time();

        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

        header("Location: " . BASE_URL . "/index.php");
        exit();
    } else {
        setcookie('remember', '', time() - 3600, '/');
    }
}

// ✅ Real-time online user tracking
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $session_id = session_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    try {
        // Insert or update online record
        $stmt = $pdo->prepare("
            INSERT INTO online_users (user_id, session_id, last_activity, ip_address, user_agent)
            VALUES (?, ?, NOW(), ?, ?)
            ON DUPLICATE KEY UPDATE 
                last_activity = NOW(),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent)
        ");
        $stmt->execute([$user_id, $session_id, $ip, $agent]);
    } catch (PDOException $e) {
        error_log('Online tracking failed: ' . $e->getMessage());
    }
}
?>

