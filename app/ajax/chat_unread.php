<?php
/**
 * chat_unread.php — Returns the number of unread admin/bot messages for the
 * user's active chat session WITHOUT marking them as read.
 * Called by the widget badge poller while the chat window is closed.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'unread' => 0]);
    exit;
}
$uid = (int)$_SESSION['user_id'];

try {
    // Find the user's active session (if any)
    $st = $pdo->prepare(
        "SELECT id FROM live_chat_sessions WHERE user_id=? AND status='active' ORDER BY id DESC LIMIT 1"
    );
    $st->execute([$uid]);
    $row = $st->fetch();

    if (!$row) {
        echo json_encode(['success' => true, 'unread' => 0]);
        exit;
    }

    $sessionId = (int)$row['id'];

    // Count unread messages sent by admin or bot
    $cnt = $pdo->prepare(
        "SELECT COUNT(*) FROM live_chat_messages
         WHERE session_id=? AND sender_type IN ('admin','bot') AND is_read=0"
    );
    $cnt->execute([$sessionId]);
    $unread = (int)$cnt->fetchColumn();

    echo json_encode(['success' => true, 'unread' => $unread, 'session_id' => $sessionId]);
} catch (PDOException $e) {
    error_log('chat_unread: ' . $e->getMessage());
    echo json_encode(['success' => false, 'unread' => 0]);
}
