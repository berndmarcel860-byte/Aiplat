<?php
/**
 * Delete Notification
 */

require_once '../config.php';
require_once '../session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

if ($notificationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

try {
    // Verify notification belongs to user before deleting
    $stmt = $pdo->prepare("DELETE FROM user_notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Benachrichtigung gelöscht'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Benachrichtigung nicht gefunden'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Delete notification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}