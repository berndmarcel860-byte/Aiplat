<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Total page views today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_page_views
        FROM user_activity_logs
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $totalPageViews = $stmt->fetchColumn();
    
    // Active users today
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT user_id) as active_users_today
        FROM user_activity_logs
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $activeUsersToday = $stmt->fetchColumn();
    
    // Most visited page today
    $stmt = $pdo->prepare("
        SELECT page_url, COUNT(*) as visit_count
        FROM user_activity_logs
        WHERE DATE(created_at) = CURDATE()
        GROUP BY page_url
        ORDER BY visit_count DESC
        LIMIT 1
    ");
    $stmt->execute();
    $topPageResult = $stmt->fetch();
    $topPage = $topPageResult ? basename($topPageResult['page_url']) : '-';
    
    // Average session time (simplified calculation)
    $stmt = $pdo->prepare("
        SELECT AVG(TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at))) as avg_session_time
        FROM user_activity_logs
        WHERE DATE(created_at) = CURDATE() AND user_id IS NOT NULL
        GROUP BY user_id
        HAVING COUNT(*) > 1
    ");
    $stmt->execute();
    $avgSessionTime = $stmt->fetchColumn() ?: 0;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_page_views' => $totalPageViews,
            'active_users_today' => $activeUsersToday,
            'top_page' => $topPage,
            'avg_session_time' => round($avgSessionTime, 1)
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>