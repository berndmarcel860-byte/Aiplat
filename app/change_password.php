<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$response = ['success' => false];

try {
    // Validate input
    if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
        throw new Exception('All fields are required');
    }
    
    if ($_POST['new_password'] !== $_POST['confirm_password']) {
        throw new Exception('New passwords do not match');
    }
    
    if (strlen($_POST['new_password']) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($_POST['current_password'], $user['password_hash'])) {
        throw new Exception('Current password is incorrect');
    }
    
    // Update password
    $newHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, force_password_change = 0 WHERE id = ?");
    $updateStmt->execute([$newHash, $_SESSION['user_id']]);
    
    $response = [
        'success' => true,
        'message' => 'Password changed successfully'
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
