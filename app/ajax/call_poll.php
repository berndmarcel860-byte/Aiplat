<?php
/**
 * call_poll.php — User polls for WebRTC signals sent by the admin side.
 * Returns unconsumed signals from admin and the current call status.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$uid       = (int)$_SESSION['user_id'];
$sessionId = (int)($_GET['session_id'] ?? 0);
if (!$sessionId) { echo json_encode(['success'=>false,'message'=>'Invalid session']); exit; }

try {
    // Ownership check — no status filter so we can detect call-end on closed sessions too
    $st = $pdo->prepare("SELECT id, voice_call_status FROM live_chat_sessions WHERE id=? AND user_id=?");
    $st->execute([$sessionId, $uid]);
    $session = $st->fetch();
    if (!$session) { echo json_encode(['success'=>false,'message'=>'Session not found']); exit; }

    // Fetch unconsumed signals from admin
    $sigs = $pdo->prepare(
        "SELECT id, type, payload FROM voice_call_signals
         WHERE session_id=? AND from_type='admin' AND is_consumed=0
         ORDER BY id ASC LIMIT 20"
    );
    $sigs->execute([$sessionId]);
    $signals = $sigs->fetchAll();

    // Mark consumed (SELECT has LIMIT 20 so at most 20 IDs)
    if (!empty($signals)) {
        $ids = array_slice(array_column($signals, 'id'), 0, 20);
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE voice_call_signals SET is_consumed=1 WHERE id IN ($in)")->execute($ids);
    }

    // Decode JSON payloads
    foreach ($signals as &$s) {
        $s['payload'] = json_decode($s['payload'], true);
    }
    unset($s);

    // Clean up signals older than 5 minutes
    $pdo->prepare("DELETE FROM voice_call_signals WHERE session_id=? AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->execute([$sessionId]);

    echo json_encode([
        'success'           => true,
        'signals'           => $signals,
        'voice_call_status' => $session['voice_call_status'],
    ]);
} catch (PDOException $e) {
    error_log('call_poll: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
