<?php
require_once '../admin_session.php';

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

$user_id = (int)$_GET['user_id'];
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : '';

try {
    // Get session details
    $stmt = $pdo->prepare("
        SELECT 
            ou.*,
            u.first_name as user_first_name,
            u.last_name as user_last_name,
            u.email as user_email
        FROM online_users ou
        JOIN users u ON ou.user_id = u.id
        WHERE ou.user_id = ? AND ou.session_id = ?
    ");
    $stmt->execute([$user_id, $session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit();
    }
    
    // Get recent activity
    $stmt = $pdo->prepare("
        SELECT page_url, http_method, created_at
        FROM user_activity_logs
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recentPages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'session' => $session,
        'recent_pages' => $recentPages
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>