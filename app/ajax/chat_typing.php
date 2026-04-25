<?php
/**
 * chat_typing.php — User is typing; upsert typing record.
 */
require_once '../I.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false]); exit; }
$uid       = (int)$_SESSION['user_id'];
$sessionId = (int)(json_decode(file_get_contents('php://input'), true)['session_id'] ?? 0);

if (!$sessionId) { echo json_encode(['success'=>false]); exit; }

try {
    // Verify ownership
    $st = $pdo->prepare("SELECT id FROM live_chat_sessions WHERE id=? AND user_id=? AND status='active'");
    $st->execute([$sessionId, $uid]);
    if (!$st->fetch()) { echo json_encode(['success'=>false]); exit; }

    $pdo->prepare("INSERT INTO live_chat_typing (session_id, typer_type, updated_at) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE updated_at=NOW()")->execute([$sessionId, 'user']);

    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false]);
}
