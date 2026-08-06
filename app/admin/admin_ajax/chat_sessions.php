<?php
/**
 * admin_chat_sessions.php — List all chat sessions for the admin panel.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

try {
    $status = $_GET['status'] ?? 'active';
    $allowed = ['active','closed','all'];
    if (!in_array($status, $allowed, true)) $status = 'active';

    // $status is strictly whitelisted above — safe to interpolate
    $where = $status === 'all' ? '' : "AND s.status = '$status'";

    $stmt = $pdo->query("
        SELECT
            s.id, s.status, s.topic, s.unread_admin, s.created_at, s.updated_at,
            CONCAT(u.first_name, ' ', u.last_name) AS user_name,
            u.email AS user_email,
            (SELECT message FROM live_chat_messages WHERE session_id=s.id ORDER BY id DESC LIMIT 1) AS last_message,
            (SELECT sender_type FROM live_chat_messages WHERE session_id=s.id ORDER BY id DESC LIMIT 1) AS last_sender
        FROM live_chat_sessions s
        JOIN users u ON s.user_id = u.id
        WHERE 1=1 $where
        ORDER BY s.updated_at DESC
        LIMIT 100
    ");
    $sessions = $stmt->fetchAll();

    echo json_encode(['success'=>true,'data'=>$sessions]);
} catch (PDOException $e) {
    error_log('admin_chat_sessions: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
