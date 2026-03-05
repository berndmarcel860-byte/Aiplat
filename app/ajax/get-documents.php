<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ? AND user_id = ?");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$document = $stmt->fetch();

if ($document) {
    echo json_encode([
        'success' => true,
        'document' => $document
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Document not found or access denied'
    ]);
}