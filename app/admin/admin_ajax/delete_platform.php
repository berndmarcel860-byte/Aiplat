<?php
require_once '../admin_session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['platform_id']) || !is_numeric($_POST['platform_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid platform ID']);
    exit();
}

$platform_id = (int)$_POST['platform_id'];
$admin_id = $_SESSION['admin_id'];

try {
    $pdo->beginTransaction();
    
    // Get platform data for logging
    $stmt = $pdo->prepare("SELECT * FROM scam_platforms WHERE id = ?");
    $stmt->execute([$platform_id]);
    $platform = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$platform) {
        echo json_encode(['success' => false, 'message' => 'Platform not found']);
        exit();
    }
    
    // Check if there are any cases associated with this platform
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE platform_id = ?");
    $stmt->execute([$platform_id]);
    $case_count = $stmt->fetchColumn();
    
    if ($case_count > 0) {
        echo json_encode([
            'success' => false, 
            'message' => "Cannot delete platform. There are {$case_count} case(s) associated with this platform."
        ]);
        exit();
    }
    
    // Delete platform
    $stmt = $pdo->prepare("DELETE FROM scam_platforms WHERE id = ?");
    $stmt->execute([$platform_id]);
    
    // Delete logo file if it exists
    if ($platform['logo']) {
        $app_root = dirname(__FILE__, 2); // Goes up 2 levels: admin_ajax -> admin -> app
        $logo_path = $app_root . '/' . $platform['logo'];
        if (file_exists($logo_path)) {
            unlink($logo_path);
        }
    }
    
    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs 
        (admin_id, action, entity_type, entity_id, old_value, ip_address, user_agent, created_at)
        VALUES (?, 'delete', 'platform', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $admin_id,
        $platform_id,
        json_encode($platform),
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Platform deleted successfully!'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>