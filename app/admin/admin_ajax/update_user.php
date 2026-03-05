<?php
require_once '../../config.php';

header('Content-Type: application/json');

// Validate input
$required = ['id', 'first_name', 'last_name', 'email', 'status'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$userId = (int)$_POST['id'];
$data = [
    'first_name' => trim($_POST['first_name']),
    'last_name' => trim($_POST['last_name']),
    'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
    'status' => in_array($_POST['status'], ['active', 'suspended', 'banned']) ? $_POST['status'] : 'active',
    'balance' => isset($_POST['balance']) ? (float)$_POST['balance'] : 0,
    'phone' => isset($_POST['phone']) ? preg_replace('/[^0-9+]/', '', $_POST['phone']) : null,
    'country' => isset($_POST['country']) ? substr(trim($_POST['country']), 0, 100) : null
];

try {
    // Check if email exists for another user
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkStmt->execute([$data['email'], $userId]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }

    // Update user
    $stmt = $pdo->prepare("
        UPDATE users SET 
        first_name = :first_name,
        last_name = :last_name,
        email = :email,
        status = :status,
        balance = :balance,
        phone = :phone,
        country = :country,
        updated_at = NOW()
        WHERE id = :id
    ");
    $data['id'] = $userId;
    $stmt->execute($data);
    
    // Log this action
    $logStmt = $pdo->prepare("
        INSERT INTO admin_logs 
        (admin_id, action, entity_type, entity_id, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $logStmt->execute([
        $_SESSION['admin_id'],
        'update',
        'user',
        $userId,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully',
        'user' => [
            'id' => $userId,
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'email' => $data['email'],
            'status' => $data['status']
        ]
    ]);
} catch (PDOException $e) {
    error_log("Update User Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update user',
        'error' => $e->getMessage()
    ]);
}
?>