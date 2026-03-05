<?php
require_once '../config.php';
require_once '../session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    $documentType = filter_input(INPUT_POST, 'document_type', FILTER_SANITIZE_STRING);
    
    if (empty($documentType)) {
        throw new Exception('Please select a document type');
    }

    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Please select a valid document file');
    }

    $file = $_FILES['document'];
    
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    $maxSize = 5 * 1024 * 1024;
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Only PDF, JPG, and PNG files are allowed');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File size must be less than 5MB');
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'doc_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
    $uploadPath = '../uploads/documents/' . $filename;

    if (!file_exists('../uploads/documents')) {
        mkdir('../uploads/documents', 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to save document');
    }

    $stmt = $pdo->prepare("INSERT INTO user_documents 
                          (user_id, document_name, document_type, file_path, uploaded_at) 
                          VALUES (:user_id, :name, :type, :path, NOW())");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':name' => $file['name'],
        ':type' => $documentType,
        ':path' => 'documents/' . $filename
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Document uploaded successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}