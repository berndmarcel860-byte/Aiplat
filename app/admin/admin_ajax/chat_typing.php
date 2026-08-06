<?php
/**
 * admin_chat_typing.php — Admin is typing.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

$sessionId = (int)(json_decode(file_get_contents('php://input'), true)['session_id'] ?? 0);
if (!$sessionId) { echo json_encode(['success'=>false]); exit; }

try {
    $pdo->prepare("INSERT INTO live_chat_typing (session_id, typer_type, updated_at) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE updated_at=NOW()")->execute([$sessionId, 'admin']);
    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false]);
}
