<?php
// Admin configuration - use relative path to main config
require_once __DIR__ . '/../config.php';

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Enhanced admin session security settings
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // Use only if HTTPS is enabled
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes

    // Use separate session name for admin
    session_name('ADMINSESSID');
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Check if admin is logged in
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

// Redirect to login if not authenticated
if (!is_admin_logged_in() && basename($_SERVER['PHP_SELF']) !== 'admin_login.php') {
    header("Location: admin_login.php");
    exit();
}

// Check session expiration
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Session expired
    session_unset();
    session_destroy();
    header("Location: admin_login.php?expired=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Auto-login from remember me cookie (admin-specific)
if (!is_admin_logged_in() && isset($_COOKIE['admin_remember'])) {
    $token = $_COOKIE['admin_remember'];
    
    $stmt = $pdo->prepare("SELECT a.id, a.email, a.first_name, a.last_name, a.role 
                          FROM admins a
                          INNER JOIN admin_remember_tokens art ON a.id = art.admin_id
                          WHERE art.token = ? AND art.expires > NOW()");
    $stmt->execute([$token]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['last_activity'] = time();
        
        // Update last login
        $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
    } else {
        // Invalid token - clear cookie
        setcookie('admin_remember', '', time() - 3600, '/admin', '', true, true);
    }
}

// Generate and store CSRF token if not exists
if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}
?>