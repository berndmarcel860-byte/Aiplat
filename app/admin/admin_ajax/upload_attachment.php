<?php
require_once '../admin_session.php';

if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['admin_csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

if (empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/app/uploads/ticket_attachments/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$file = $_FILES['file'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$file_name = 'ticket_attachment_' . uniqid() . '.' . $file_ext;
$file_path = $upload_dir . $file_name;

// Validate file
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
if (!in_array($file_ext, $allowed_extensions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit();
}

if ($file['size'] > 5 * 1024 * 1024) { // 5MB
    echo json_encode(['success' => false, 'message' => 'File too large (max 5MB)']);
    exit();
}

if (move_uploaded_file($file['tmp_name'], $file_path)) {
    // Save to database
    $stmt = $pdo->prepare("INSERT INTO documents (document_name, document_type, file_path, file_size, status) VALUES (?, ?, ?, ?, 'approved')");
    $stmt->execute([$file['name'], $file_ext, '/app/uploads/ticket_attachments/' . $file_name, $file['size']]);
    
    $file_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'file_id' => $file_id,
        'file_name' => $file['name'],
        'file_path' => '/app/uploads/ticket_attachments/' . $file_name
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error uploading file']);
}