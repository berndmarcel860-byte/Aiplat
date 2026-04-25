<?php
/**
 * admin_chat_messages.php — Get messages for a session + mark unread as read.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

$sessionId = (int)($_GET['session_id'] ?? 0);
$sinceId   = (int)($_GET['since_id']   ?? 0);

if (!$sessionId) { echo json_encode(['success'=>false,'message'=>'Invalid session']); exit; }

try {
    if ($sinceId) {
        // Polling: only new messages
        $st = $pdo->prepare("SELECT id, sender_type, message, is_read, created_at FROM live_chat_messages WHERE session_id=? AND id>? ORDER BY id ASC LIMIT 30");
        $st->execute([$sessionId, $sinceId]);
    } else {
        // Initial load: last 60
        $st = $pdo->prepare("SELECT id, sender_type, message, is_read, created_at FROM live_chat_messages WHERE session_id=? ORDER BY id DESC LIMIT 60");
        $st->execute([$sessionId]);
    }
    $messages = $sinceId ? $st->fetchAll() : array_reverse($st->fetchAll());

    // Mark user messages as read
    $pdo->prepare("UPDATE live_chat_messages SET is_read=1 WHERE session_id=? AND sender_type='user' AND is_read=0")->execute([$sessionId]);
    $pdo->prepare("UPDATE live_chat_sessions SET unread_admin=0 WHERE id=?")->execute([$sessionId]);

    // User typing?
    $typRow = $pdo->prepare("SELECT updated_at FROM live_chat_typing WHERE session_id=? AND typer_type='user'");
    $typRow->execute([$sessionId]);
    $typData    = $typRow->fetch();
    $userTyping = false;
    if ($typData) {
        $diff = time() - strtotime($typData['updated_at']);
        $userTyping = $diff < 4;
    }

    // IDs of admin messages now read by user (for live read-receipt ticks in admin panel)
    $readSt = $pdo->prepare("SELECT id FROM live_chat_messages WHERE session_id=? AND sender_type='admin' AND is_read=1");
    $readSt->execute([$sessionId]);
    $readAdminMsgIds = array_map('intval', array_column($readSt->fetchAll(), 'id'));

    // Current session status (admin needs to know if user closed the session)
    $sesSt = $pdo->prepare("SELECT status FROM live_chat_sessions WHERE id=?");
    $sesSt->execute([$sessionId]);
    $sesRow = $sesSt->fetch();
    $sessionStatus = $sesRow ? $sesRow['status'] : 'unknown';

    echo json_encode([
        'success'           => true,
        'messages'          => $messages,
        'user_typing'       => $userTyping,
        'read_admin_msg_ids'=> $readAdminMsgIds,
        'session_status'    => $sessionStatus,
    ]);
} catch (PDOException $e) {
    error_log('admin_chat_messages: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
