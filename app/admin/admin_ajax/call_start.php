<?php
/**
 * call_start.php (admin) — Admin initiates a WebRTC voice call with a user.
 * Inserts an 'offer' signal and sets voice_call_status to 'ringing'.
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
    $st = $pdo->prepare("SELECT id, voice_call_status FROM live_chat_sessions WHERE id=? AND status='active'");
    $st->execute([$sessionId]);
    $session = $st->fetch();
    if (!$session) { echo json_encode(['success'=>false,'message'=>'Session not found']); exit; }

    if (in_array($session['voice_call_status'], ['ringing', 'active'], true)) {
        echo json_encode(['success'=>false,'message'=>'Call already in progress']); exit;
    }

    // Purge stale signals for this session
    $pdo->prepare("DELETE FROM voice_call_signals WHERE session_id=? AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->execute([$sessionId]);

    // Insert offer signal
    $pdo->prepare("INSERT INTO voice_call_signals (session_id, from_type, type, payload) VALUES (?, 'admin', 'offer', ?)")
        ->execute([$sessionId, json_encode($sdp)]);

    $pdo->prepare("UPDATE live_chat_sessions SET voice_call_status='ringing', updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    // Create call log entry (admin-initiated)
    $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    // Look up the user_id for this session
    $sessionRow = $pdo->prepare("SELECT user_id FROM live_chat_sessions WHERE id=?");
    $sessionRow->execute([$sessionId]);
    $sessionRow = $sessionRow->fetch();
    $callUserId = $sessionRow ? (int)$sessionRow['user_id'] : null;
    $pdo->prepare("INSERT INTO voice_call_logs (session_id, initiated_by, user_id, status, started_at) VALUES (?, 'admin', ?, 'ringing', NOW())")
        ->execute([$sessionId, $callUserId]);

    // System message visible to user
    $pdo->prepare("INSERT INTO live_chat_messages (session_id, sender_type, message, is_read) VALUES (?, 'bot', '\xF0\x9F\x93\x9E Eingehender Sprachanruf vom Support\xe2\x80\xa6', 0)")
        ->execute([$sessionId]);
    $pdo->prepare("UPDATE live_chat_sessions SET unread_user=unread_user+1, updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    // Send WhatsApp notification to the user (non-blocking, errors silently logged)
    if ($callUserId) {
        try {
            require_once __DIR__ . '/../../WhatsAppHelper.php';
            $userRow = $pdo->prepare("SELECT first_name, phone FROM users WHERE id=? LIMIT 1");
            $userRow->execute([$callUserId]);
            $userRow = $userRow->fetch();
            if ($userRow && !empty($userRow['phone'])) {
                $chatUrl = rtrim(BASE_URL, '/') . '/live_chat.php';
                $wa = new WhatsAppHelper($pdo);
                $wa->sendCallNotification($userRow['phone'], $userRow['first_name'], $chatUrl);
            }
        } catch (Exception $e) {
            error_log('admin_call_start WhatsApp: ' . $e->getMessage());
        }
    }

    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    error_log('admin_call_start: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
