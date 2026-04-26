<?php
/**
 * call_answer.php (admin) — Admin answers a WebRTC voice call initiated by user.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = (int)($input['session_id'] ?? 0);
$sdp       = $input['sdp'] ?? null;

if (!$sessionId || !is_array($sdp) || empty($sdp['type']) || empty($sdp['sdp'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid input']); exit;
}

try {
    $st = $pdo->prepare("SELECT id FROM live_chat_sessions WHERE id=? AND status='active'");
    $st->execute([$sessionId]);
    if (!$st->fetch()) { echo json_encode(['success'=>false,'message'=>'Session not found']); exit; }

    $pdo->prepare("INSERT INTO voice_call_signals (session_id, from_type, type, payload) VALUES (?, 'admin', 'answer', ?)")
        ->execute([$sessionId, json_encode($sdp)]);

    $pdo->prepare("UPDATE live_chat_sessions SET voice_call_status='active', updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    error_log('admin_call_answer: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
