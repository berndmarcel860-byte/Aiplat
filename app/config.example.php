<?php
// Copy this file to config.php and fill in your credentials.
// NEVER commit config.php to version control.

// Error reporting — disable in production
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Database configuration — use environment variables or fill in values below
$host     = getenv('DB_HOST')     ?: 'localhost';
$dbname   = getenv('DB_NAME')     ?: 'your_database_name';
$username = getenv('DB_USER')     ?: 'your_db_user';
$password = getenv('DB_PASSWORD') ?: 'your_db_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

define('BASE_URL', getenv('APP_URL') ?: 'https://your-domain.com/app');
?>
