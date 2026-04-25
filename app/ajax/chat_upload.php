<?php
/**
 * chat_upload.php — User uploads an image or file attachment in a live-chat session.
 * Stores the file under uploads/chat/ and records it as a message.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    error_log('chat_upload: Unauthorized access attempt - no user session');
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}
$uid       = (int)$_SESSION['user_id'];
$sessionId = (int)($_POST['session_id'] ?? 0);

if (!$sessionId) {
    error_log('chat_upload: Invalid or missing session_id for user ' . $uid);
    echo json_encode(['success'=>false,'message'=>'Invalid session']);
    exit;
}

if (!isset($_FILES['chat_file']) || $_FILES['chat_file']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['chat_file']['error'] ?? -1;
    error_log('chat_upload: File upload error code ' . $errCode . ' for user ' . $uid . ' session ' . $sessionId);
    echo json_encode(['success'=>false,'message'=>'No file received (code '.$errCode.')']);
    exit;
}

$file    = $_FILES['chat_file'];
$maxSize = 10 * 1024 * 1024; // 10 MB

$allowedMime = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

if (!in_array($file['type'], $allowedMime, true)) {
    error_log('chat_upload: Rejected file type "' . $file['type'] . '" (name: ' . $file['name'] . ') for user ' . $uid);
    echo json_encode(['success'=>false,'message'=>'File type not allowed. Accepted: images, PDF, DOC/DOCX.']);
    exit;
}

if ($file['size'] > $maxSize) {
    error_log('chat_upload: File too large (' . $file['size'] . ' bytes) for user ' . $uid . ' (name: ' . $file['name'] . ')');
    echo json_encode(['success'=>false,'message'=>'File too large. Maximum 10 MB.']);
    exit;
}

try {
    // Verify session belongs to user and is active
    $st = $pdo->prepare("SELECT id FROM live_chat_sessions WHERE id=? AND user_id=? AND status='active'");
    $st->execute([$sessionId, $uid]);
    if (!$st->fetch()) {
        error_log('chat_upload: Session ' . $sessionId . ' not found or not active for user ' . $uid);
        echo json_encode(['success'=>false,'message'=>'Session not found']);
        exit;
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeName = 'chat_' . $uid . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dir      = __DIR__ . '/../uploads/chat/';

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $destPath = $dir . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        error_log('chat_upload: move_uploaded_file failed for user ' . $uid . ' — src: ' . $file['tmp_name'] . ' dest: ' . $destPath);
        echo json_encode(['success'=>false,'message'=>'Failed to save file']);
        exit;
    }

    error_log('chat_upload: File saved successfully — ' . $destPath . ' for user ' . $uid . ' session ' . $sessionId);

    // Relative URL served by the web server
    $fileUrl  = 'uploads/chat/' . $safeName;
    $origName = basename($file['name']);

    // Store as a special attachment message: __ATTACH__:url|original_name
    $attachMsg = '__ATTACH__:' . $fileUrl . '|' . $origName;

    $ins = $pdo->prepare("INSERT INTO live_chat_messages (session_id, sender_type, message, is_read) VALUES (?,?,?,0)");
    $ins->execute([$sessionId, 'user', $attachMsg]);
    $msgId = (int)$pdo->lastInsertId();

    $pdo->prepare("UPDATE live_chat_sessions SET unread_admin=unread_admin+1, updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    $row = $pdo->prepare("SELECT id, sender_type, sender_name, message, is_read, created_at FROM live_chat_messages WHERE id=?");
    $row->execute([$msgId]);
    $msgRow = $row->fetch();

    echo json_encode(['success'=>true, 'user_msg'=>$msgRow]);

} catch (PDOException $e) {
    error_log('chat_upload: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
