<?php
/**
 * Get Notification Details
 */

require_once '../config.php';
require_once '../session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$notificationId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($notificationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM user_notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $_SESSION['user_id']]);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($notification) {
        echo json_encode([
            'success' => true,
            'notification' => $notification
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Benachrichtigung nicht gefunden'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Get notification details error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}