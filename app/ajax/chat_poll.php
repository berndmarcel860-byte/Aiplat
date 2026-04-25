<?php
/**
 * chat_poll.php — Returns new messages since a given message ID + typing status.
 * Called every ~2 s by the widget.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$uid       = (int)$_SESSION['user_id'];
$sessionId = (int)($_GET['session_id'] ?? 0);
$sinceId   = (int)($_GET['since_id']   ?? 0);

if (!$sessionId) { echo json_encode(['success'=>false,'message'=>'Invalid session']); exit; }

try {
    // Verify ownership
    $st = $pdo->prepare("SELECT id, status FROM live_chat_sessions WHERE id=? AND user_id=?");
    $st->execute([$sessionId, $uid]);
    $session = $st->fetch();
    if (!$session) { echo json_encode(['success'=>false,'message'=>'Session not found']); exit; }

    // New messages
    $msgs = $pdo->prepare("SELECT id, sender_type, message, is_read, created_at FROM live_chat_messages WHERE session_id=? AND id>? ORDER BY id ASC LIMIT 30");
    $msgs->execute([$sessionId, $sinceId]);
    $newMessages = $msgs->fetchAll();

    // Mark incoming (bot/admin) messages as read
    if (!empty($newMessages)) {
        $pdo->prepare("UPDATE live_chat_messages SET is_read=1 WHERE session_id=? AND sender_type IN ('bot','admin') AND is_read=0")->execute([$sessionId]);
        $pdo->prepare("UPDATE live_chat_sessions SET unread_user=0 WHERE id=?")->execute([$sessionId]);
    }

    // Admin typing?
    $typRow = $pdo->prepare("SELECT updated_at FROM live_chat_typing WHERE session_id=? AND typer_type='admin'");
    $typRow->execute([$sessionId]);
    $typData  = $typRow->fetch();
    $adminTyping = false;
    if ($typData) {
        // Consider typing active if updated within the last 4 seconds
        $diff = time() - strtotime($typData['updated_at']);
        $adminTyping = $diff < 4;
    }

    echo json_encode([
        'success'      => true,
        'messages'     => $newMessages,
        'admin_typing' => $adminTyping,
        'session_status' => $session['status'],
    ]);
} catch (PDOException $e) {
    error_log('chat_poll: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
