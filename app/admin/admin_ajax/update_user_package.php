<?php
// admin_ajax/update_user_package.php
// Update existing user package assignment

require_once '../admin_session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate required fields
$required = ['id', 'package_id', 'start_date', 'end_date', 'status'];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$id = (int)$_POST['id'];
$packageId = (int)$_POST['package_id'];
$startDate = $_POST['start_date'];
$endDate = $_POST['end_date'];
$status = $_POST['status'];

// Validate status
$validStatuses = ['pending', 'active', 'expired', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Verify assignment exists
    $stmt = $pdo->prepare("SELECT id, status as old_status FROM user_packages WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Package assignment not found']);
        exit();
    }
    
    // Verify package exists
    $stmt = $pdo->prepare("SELECT id FROM packages WHERE id = ?");
    $stmt->execute([$packageId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit();
    }
    
    // Update assignment
    $stmt = $pdo->prepare("
        UPDATE user_packages 
        SET package_id = ?, start_date = ?, end_date = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$packageId, $startDate, $endDate, $status, $id]);
    
    // Log admin action
    $logStmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at)
        VALUES (?, 'update', 'user_package', ?, ?, ?, ?, NOW())
    ");
    $logStmt->execute([
        $_SESSION['admin_id'],
        $id,
        json_encode(['old_status' => $existing['old_status'], 'new_status' => $status]),
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Package assignment updated successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Update user package error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}