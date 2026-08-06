<?php
/**
 * call_start.php — User initiates a WebRTC voice call.
 * Inserts an 'offer' signal and sets voice_call_status to 'ringing'.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$uid   = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = (int)($input['session_id'] ?? 0);
$sdp       = $input['sdp'] ?? null;

if (!$sessionId || !is_array($sdp) || empty($sdp['type']) || empty($sdp['sdp'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid input']); exit;
}

try {
    // Verify ownership and active status
    $st = $pdo->prepare("SELECT id, voice_call_status FROM live_chat_sessions WHERE id=? AND user_id=? AND status='active'");
    $st->execute([$sessionId, $uid]);
    $session = $st->fetch();
    if (!$session) { echo json_encode(['success'=>false,'message'=>'Session not found']); exit; }

    if (in_array($session['voice_call_status'], ['ringing', 'active'], true)) {
        echo json_encode(['success'=>false,'message'=>'Call already in progress']); exit;
    }

    // Purge stale signals for this session
    $pdo->prepare("DELETE FROM voice_call_signals WHERE session_id=? AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->execute([$sessionId]);

    // Insert offer signal
    $pdo->prepare("INSERT INTO voice_call_signals (session_id, from_type, type, payload) VALUES (?, 'user', 'offer', ?)")
        ->execute([$sessionId, json_encode($sdp)]);

    // Update call status
    $pdo->prepare("UPDATE live_chat_sessions SET voice_call_status='ringing', updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    // Create call log entry
    $pdo->prepare("INSERT INTO voice_call_logs (session_id, initiated_by, user_id, status, started_at) VALUES (?, 'user', ?, 'ringing', NOW())")
        ->execute([$sessionId, $uid]);
    $callLogId = (int)$pdo->lastInsertId();

    // System message visible to admin
    $pdo->prepare("INSERT INTO live_chat_messages (session_id, sender_type, message, is_read) VALUES (?, 'bot', '\xF0\x9F\x93\x9E Eingehender Sprachanruf vom Benutzer\xe2\x80\xa6', 0)")
        ->execute([$sessionId]);
    $pdo->prepare("UPDATE live_chat_sessions SET unread_admin=unread_admin+1, updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    // Send WhatsApp notification to admin(s) about the incoming call
    try {
        require_once __DIR__ . '/../../WhatsAppHelper.php';
        // Fetch calling user's name
        $userRow = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id=? LIMIT 1");
        $userRow->execute([$uid]);
        $userRow = $userRow->fetch();
        $callerName = $userRow ? trim($userRow['first_name'] . ' ' . $userRow['last_name']) : 'Ein Benutzer';
        // Notify admins who have a phone number stored (column may not exist yet)
        try {
            $adminStmt = $pdo->query("SELECT phone FROM admins WHERE phone IS NOT NULL AND phone != ''");
            $adminPhones = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
            if ($adminPhones) {
                $chatUrl = rtrim(BASE_URL, '/') . '/admin/admin_live_chat.php';
                $wa = new WhatsAppHelper($pdo);
                foreach ($adminPhones as $adminPhone) {
                    $text = "📞 Eingehender Anruf\n\n$callerName möchte einen Sprachanruf starten.\n\nZum Live-Chat: $chatUrl";
                    $wa->sendTextMessage($adminPhone, $text);
                }
            }
        } catch (PDOException $ignored) {
            // admins table has no phone column yet — skip silently
        }
    } catch (Exception $e) {
        error_log('user_call_start WhatsApp: ' . $e->getMessage());
    }

    echo json_encode(['success'=>true, 'call_log_id'=>$callLogId]);
} catch (PDOException $e) {
    error_log('call_start: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
