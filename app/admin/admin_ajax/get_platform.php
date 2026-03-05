<?php
require_once '../admin_session.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid platform ID']);
    exit();
}

$platform_id = (int)$_GET['id'];

try {
    // Get platform data
    $stmt = $pdo->prepare("
        SELECT 
            sp.*,
            CONCAT(a.first_name, ' ', a.last_name) as created_by_name
        FROM scam_platforms sp
        LEFT JOIN admins a ON sp.created_by = a.id
        WHERE sp.id = ?
    ");
    $stmt->execute([$platform_id]);
    $platform = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$platform) {
        echo json_encode(['success' => false, 'message' => 'Platform not found']);
        exit();
    }
    
    // Get recent cases for this platform
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.case_number,
            c.reported_amount,
            c.status,
            c.created_at,
            u.first_name as user_first_name,
            u.last_name as user_last_name
        FROM cases c
        JOIN users u ON c.user_id = u.id
        WHERE c.platform_id = ?
        ORDER BY c.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$platform_id]);
    $recent_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'platform' => $platform,
        'recent_cases' => $recent_cases
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>