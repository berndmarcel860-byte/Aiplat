<?php
require_once '../admin_session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['admin_csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

if (!isset($_POST['file_id']) || !is_numeric($_POST['file_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid file ID']);
    exit();
}

$file_id = (int)$_POST['file_id'];

try {
    // Get file info
    $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
    $stmt->execute([$file_id]);
    $file = $stmt->fetch();

    if (!$file) {
        echo json_encode(['success' => false, 'message' => 'File not found']);
        exit();
    }

    // Delete file from server
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $file['file_path'];
    if (file_exists($full_path)) {
        unlink($full_path);
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
    $stmt->execute([$file_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}