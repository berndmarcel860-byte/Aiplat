<?php
require_once '../config.php';
require_once 'admin_session.php';

// Delete remember token from database
if (isset($_COOKIE['admin_remember'])) {
    $token = $_COOKIE['admin_remember'];
    $pdo->prepare("DELETE FROM admin_remember_tokens WHERE token = ?")->execute([$token]);
    setcookie('admin_remember', '', [
        'expires' => time() - 3600,
        'path' => '/admin',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header("Location: admin_login.php?logout=1");
exit();
?>