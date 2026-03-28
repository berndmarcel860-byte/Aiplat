<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Database configuration — use environment variables (never hard-code credentials)
$host     = getenv('DB_HOST')     ?: 'localhost';
$dbname   = getenv('DB_NAME')     ?: 'novalnet-ai';
$username = getenv('DB_USER')     ?: 'novalnet';
$password = getenv('DB_PASSWORD') ?: '';
if ($password === '') {
    error_log('WARNING: DB_PASSWORD environment variable is not set');
}

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

// Set application timezone — adjust APP_TIMEZONE env var or edit this value to match your server location
$appTimezone = getenv('APP_TIMEZONE') ?: 'Europe/Berlin';
date_default_timezone_set($appTimezone);

// Sync MySQL session timezone with PHP timezone (handles DST automatically)
try {
    $tzObj  = new DateTimeZone($appTimezone);
    $offset = $tzObj->getOffset(new DateTime('now', new DateTimeZone('UTC')));
    $hours  = intdiv($offset, 3600);
    $mins   = abs($offset % 3600) / 60;
    $pdo->exec("SET time_zone = " . $pdo->quote(sprintf('%+03d:%02d', $hours, $mins)));
} catch (Exception $e) {
    error_log("Timezone sync error: " . $e->getMessage());
}

// Define base URL
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . str_replace('/login.php', '', $_SERVER['SCRIPT_NAME']));
?>