<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_POST['notification_id']) || !is_numeric($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

$notificationId = (int)$_POST['notification_id'];

try {
    $stmt = $pdo->prepare("
        UPDATE admin_notifications 
        SET is_read = 1 
        WHERE id = :id AND admin_id = :admin_id
    ");
    $stmt->execute([
        'id' => $notificationId,
        'admin_id' => $_SESSION['admin_id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Notification marked as read'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>