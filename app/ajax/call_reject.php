<?php
/**
 * call_reject.php — User rejects an admin-initiated incoming call.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$uid   = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = (int)($input['session_id'] ?? 0);
if (!$sessionId) { echo json_encode(['success'=>false,'message'=>'Invalid session']); exit; }

try {
    $st = $pdo->prepare("SELECT id FROM live_chat_sessions WHERE id=? AND user_id=?");
    $st->execute([$sessionId, $uid]);
    if (!$st->fetch()) { echo json_encode(['success'=>false,'message'=>'Session not found']); exit; }

    // Signal to admin
    $pdo->prepare("INSERT INTO voice_call_signals (session_id, from_type, type, payload) VALUES (?, 'user', 'reject', '{}')")
        ->execute([$sessionId]);

    $pdo->prepare("UPDATE live_chat_sessions SET voice_call_status=NULL, updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    error_log('call_reject: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
