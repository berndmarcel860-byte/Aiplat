<?php
/**
 * admin_chat_close.php — Admin closes a chat session.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = (int)($input['session_id'] ?? 0);
if (!$sessionId) { echo json_encode(['success'=>false,'message'=>'Invalid session']); exit; }

try {
    $pdo->prepare("UPDATE live_chat_sessions SET status='closed', updated_at=NOW() WHERE id=?")->execute([$sessionId]);
    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
