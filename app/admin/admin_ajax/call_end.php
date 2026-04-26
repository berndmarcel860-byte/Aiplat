<?php
/**
 * call_end.php (admin) — Admin ends an active voice call.
 * Sends an 'end' signal to user and resets the call status.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = (int)($input['session_id'] ?? 0);
if (!$sessionId) { echo json_encode(['success'=>false,'message'=>'Invalid session']); exit; }

try {
    $st = $pdo->prepare("SELECT id FROM live_chat_sessions WHERE id=?");
    $st->execute([$sessionId]);
    if (!$st->fetch()) { echo json_encode(['success'=>false,'message'=>'Session not found']); exit; }

    // Signal to user
    $pdo->prepare("INSERT INTO voice_call_signals (session_id, from_type, type, payload) VALUES (?, 'admin', 'end', '{}')")
        ->execute([$sessionId]);

    $pdo->prepare("UPDATE live_chat_sessions SET voice_call_status='ended', updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    // System message in chat
    $pdo->prepare("INSERT INTO live_chat_messages (session_id, sender_type, message, is_read) VALUES (?, 'bot', '\xF0\x9F\x93\x9E Anruf beendet.', 0)")
        ->execute([$sessionId]);
    $pdo->prepare("UPDATE live_chat_sessions SET unread_user=unread_user+1, updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    error_log('admin_call_end: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
