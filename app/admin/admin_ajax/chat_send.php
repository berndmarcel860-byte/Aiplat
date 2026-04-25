<?php
/**
 * admin_chat_send.php — Admin sends a message in a chat session.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = (int)($input['session_id'] ?? 0);
$message   = trim($input['message'] ?? '');

if (!$sessionId || $message === '') {
    echo json_encode(['success'=>false,'message'=>'Invalid input']);
    exit;
}

// Resolve display name for this admin
$agentName = $_SESSION['admin_name'] ?? 'Support';
if (empty(trim($agentName))) $agentName = 'Support';

try {
    $st = $pdo->prepare("SELECT id FROM live_chat_sessions WHERE id=? AND status='active'");
    $st->execute([$sessionId]);
    if (!$st->fetch()) { echo json_encode(['success'=>false,'message'=>'Session not found or closed']); exit; }

    $ins = $pdo->prepare(
        "INSERT INTO live_chat_messages (session_id, sender_type, sender_name, message, is_read)
         VALUES (?,?,?,?,0)"
    );
    $ins->execute([$sessionId, 'admin', $agentName, $message]);
    $msgId = (int)$pdo->lastInsertId();

    $pdo->prepare("UPDATE live_chat_sessions SET unread_user=unread_user+1, updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    echo json_encode([
        'success' => true,
        'message' => [
            'id'          => $msgId,
            'sender_type' => 'admin',
            'sender_name' => $agentName,
            'message'     => $message,
            'is_read'     => 0,
            'created_at'  => date('Y-m-d H:i:s'),
        ],
    ]);
} catch (PDOException $e) {
    error_log('admin_chat_send: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
