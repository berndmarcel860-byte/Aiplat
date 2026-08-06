<?php
/**
 * call_reject.php (admin) — Admin rejects a user-initiated incoming call.
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
    $pdo->prepare("INSERT INTO voice_call_signals (session_id, from_type, type, payload) VALUES (?, 'admin', 'reject', '{}')")
        ->execute([$sessionId]);

    $pdo->prepare("UPDATE live_chat_sessions SET voice_call_status=NULL, updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    // Update call log: rejected
    $pdo->prepare("UPDATE voice_call_logs SET status='rejected', ended_at=NOW() WHERE session_id=? AND status='ringing' ORDER BY id DESC LIMIT 1")
        ->execute([$sessionId]);

    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    error_log('admin_call_reject: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
