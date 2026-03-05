<?php
// app/notify_user.php
require_once __DIR__ . '/config.php';

function addUserNotification(PDO $pdo, $userId, $title, $message, $type = 'info', $entity = null, $entityId = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_notifications (user_id, title, message, type, related_entity, related_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $title, $message, $type, $entity, $entityId]);
        return true;
    } catch (Exception $e) {
        error_log('[UserNotification] ' . $e->getMessage());
        return false;
    }
}

function addAdminNotification(PDO $pdo, $adminId, $title, $message, $type = 'info') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_notifications (admin_id, title, message, type)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, $title, $message, $type]);
        return true;
    } catch (Exception $e) {
        error_log('[AdminNotification] ' . $e->getMessage());
        return false;
    }
}
?>
