<?php
/**
 * chat_close.php — User closes the chat session.
 */
require_once '../I.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$uid       = (int)$_SESSION['user_id'];
$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = (int)($input['session_id'] ?? 0);

if (!$sessionId) { echo json_encode(['success'=>false,'message'=>'Invalid session']); exit; }

try {
    $st = $pdo->prepare("UPDATE live_chat_sessions SET status='closed', updated_at=NOW() WHERE id=? AND user_id=?");
    $st->execute([$sessionId, $uid]);
    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
