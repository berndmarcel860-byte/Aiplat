<?php
/**
 * chat_create_session.php — Admin creates or resumes a chat session for a specific user.
 *
 * POST { user_id: int }
 * Returns the session data so the admin panel can open it immediately.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$userId = (int)($input['user_id'] ?? 0);

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user_id']); exit;
}

try {
    // Verify user exists
    $uStmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id=? AND status != 'suspended' LIMIT 1");
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch();
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']); exit;
    }

    // Find an existing active session, or create a new one
    $sStmt = $pdo->prepare("SELECT id, status, topic, unread_admin, updated_at FROM live_chat_sessions WHERE user_id=? AND status='active' ORDER BY id DESC LIMIT 1");
    $sStmt->execute([$userId]);
    $session = $sStmt->fetch();

    if (!$session) {
        $pdo->prepare("INSERT INTO live_chat_sessions (user_id, status) VALUES (?, 'active')")->execute([$userId]);
        $sessionId = (int)$pdo->lastInsertId();
        $session = [
            'id'           => $sessionId,
            'status'       => 'active',
            'topic'        => null,
            'unread_admin' => 0,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
    }

    echo json_encode([
        'success' => true,
        'session' => [
            'id'           => (int)$session['id'],
            'status'       => $session['status'],
            'topic'        => $session['topic'],
            'unread_admin' => (int)$session['unread_admin'],
            'updated_at'   => $session['updated_at'],
            'user_name'    => trim($user['first_name'] . ' ' . $user['last_name']),
            'user_email'   => $user['email'],
        ],
    ]);
} catch (PDOException $e) {
    error_log('chat_create_session: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
