<?php
// admin_ajax/get_user_package.php
// Get single user package details

require_once '../admin_session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            up.id,
            up.user_id,
            up.package_id,
            up.start_date,
            up.end_date,
            up.status,
            up.created_at,
            up.updated_at,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            u.email as user_email,
            p.name as package_name,
            p.price as package_price,
            p.duration_days,
            p.description as package_description
        FROM user_packages up
        JOIN users u ON up.user_id = u.id
        JOIN packages p ON up.package_id = p.id
        WHERE up.id = ?
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Package assignment not found']);
    }
    
} catch (PDOException $e) {
    error_log("Get user package error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}