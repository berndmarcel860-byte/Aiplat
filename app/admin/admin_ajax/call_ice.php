<?php
/**
 * call_ice.php (admin) — Admin sends a WebRTC ICE candidate to be relayed to user.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = (int)($input['session_id'] ?? 0);
$candidate = $input['candidate'] ?? null;

if (!$sessionId || !is_array($candidate) || !isset($candidate['candidate'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid input']); exit;
}

try {
    $st = $pdo->prepare("SELECT id FROM live_chat_sessions WHERE id=?");
    $st->execute([$sessionId]);
    if (!$st->fetch()) { echo json_encode(['success'=>false,'message'=>'Session not found']); exit; }

    $pdo->prepare("INSERT INTO voice_call_signals (session_id, from_type, type, payload) VALUES (?, 'admin', 'ice-candidate', ?)")
        ->execute([$sessionId, json_encode($candidate)]);

    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    error_log('admin_call_ice: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
