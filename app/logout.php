<?php
require_once 'config.php'; // includes $pdo

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Remove from online_users table ---
if (!empty($_SESSION['user_id']) && !empty(session_id())) {
    try {
        $stmt = $pdo->prepare("DELETE FROM online_users WHERE user_id = ? AND session_id = ?");
        $stmt->execute([$_SESSION['user_id'], session_id()]);
    } catch (Exception $e) {
        // Optional: log the error to a file
        error_log("Failed to remove online user: " . $e->getMessage());
    }
}

// --- Clear all session data ---
$_SESSION = [];

// --- Delete session cookie ---
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// --- Destroy session ---
session_destroy();

// --- Optional: clear any output buffers ---
while (ob_get_level()) {
    ob_end_clean();
}

// --- Redirect to login ---
header("Location: login.php");
exit;
?>
