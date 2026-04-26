<?php
/**
 * call_whatsapp_fallback.php
 *
 * Called by the admin frontend when an outbound call rings for 20 seconds
 * without the user answering.  It:
 *   1. Ends (or confirms the end of) the WebRTC call.
 *   2. Sends the user a WhatsApp message asking them to return to the chat.
 *
 * POST { session_id: int }
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = (int)($input['session_id'] ?? 0);

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Invalid session_id']); exit;
}

try {
    // Fetch the session and associated user
    $st = $pdo->prepare(
        "SELECT s.id, s.user_id, s.voice_call_status, u.first_name, u.phone
         FROM live_chat_sessions s
         JOIN users u ON s.user_id = u.id
         WHERE s.id = ? LIMIT 1"
    );
    $st->execute([$sessionId]);
    $row = $st->fetch();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Session not found']); exit;
    }

    // Mark call as idle / ended (if still ringing)
    if (in_array($row['voice_call_status'], ['ringing', 'active'], true)) {
        $pdo->prepare("UPDATE live_chat_sessions SET voice_call_status='idle', updated_at=NOW() WHERE id=?")
            ->execute([$sessionId]);
    }

    // Update call log entry
    $pdo->prepare(
        "UPDATE voice_call_logs SET status='no_answer', ended_at=NOW()
         WHERE session_id=? AND status='ringing'
         ORDER BY id DESC LIMIT 1"
    )->execute([$sessionId]);

    // Attempt WhatsApp fallback notification if user has a phone number
    $waSent = false;
    $phone  = trim($row['phone'] ?? '');

    if ($phone) {
        require_once '../../WhatsAppHelper.php';
        $chatUrl = rtrim(BASE_URL, '/') . '/live_chat.php';
        $name    = htmlspecialchars_decode($row['first_name'] ?? 'Benutzer', ENT_QUOTES);
        $text    = "📞 Verpasster Anruf\n\nHallo $name,\n\n"
                 . "Unser Support-Team hat versucht Sie zu erreichen, aber niemand hat abgehoben.\n\n"
                 . "Bitte besuchen Sie den Live-Chat, um zurückgerufen zu werden:\n$chatUrl";

        $wa     = new WhatsAppHelper($pdo);
        $waSent = $wa->sendTextMessage($phone, $text);
    }

    // Also add a system message in the chat so the user sees it next time they open the chat
    $sysMsg = '📞 Verpasster Anruf vom Support-Team. Bitte nehmen Sie Kontakt auf.';
    $pdo->prepare(
        "INSERT INTO live_chat_messages (session_id, sender_type, message, is_read) VALUES (?, 'bot', ?, 0)"
    )->execute([$sessionId, $sysMsg]);
    $pdo->prepare("UPDATE live_chat_sessions SET unread_user=unread_user+1, updated_at=NOW() WHERE id=?")
        ->execute([$sessionId]);

    echo json_encode([
        'success'     => true,
        'wa_sent'     => $waSent,
        'has_phone'   => (bool)$phone,
    ]);
} catch (PDOException $e) {
    error_log('call_whatsapp_fallback: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
