<?php
/**
 * Mark Notification as Read/Unread
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
$markUnread = isset($_POST['mark_unread']) && $_POST['mark_unread'];

if ($notificationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

try {
    // Verify notification belongs to user
    $stmt = $pdo->prepare("SELECT id FROM user_notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Notification not found']);
        exit();
    }
    
    // Mark as read or unread
    $isRead = $markUnread ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE user_notifications SET is_read = ? WHERE id = ?");
    $stmt->execute([$isRead, $notificationId]);
    
    echo json_encode([
        'success' => true,
        'message' => $markUnread ? 'Als ungelesen markiert' : 'Als gelesen markiert'
    ]);
    
} catch (PDOException $e) {
    error_log("Mark notification read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
