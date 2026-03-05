<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'novalnet-ai';
$username = 'novalnet';
$password = 'BXTrpD5Dk5@@';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Set timezone
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Define base URL
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . str_replace('/login.php', '', $_SERVER['SCRIPT_NAME']));
?>