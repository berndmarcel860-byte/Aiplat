<?php
/**
 * get_user_stats.php
 * Returns live user statistics for the admin_users.php stats banner.
 */
require_once '../../config.php';
require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $currentAdminId   = (int)$_SESSION['admin_id'];
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';

    $adminFilter      = $currentAdminRole === 'superadmin' ? '' : ' AND u.admin_id = ' . $currentAdminId;
    $baseWhere        = "WHERE u.status != 'suspended'" . $adminFilter;

    // Total active users
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM users u $baseWhere");
    $total = (int)$totalStmt->fetchColumn();

    // Never logged in
    $neverStmt = $pdo->query("
        SELECT COUNT(*) FROM users u $baseWhere
          AND (u.last_login IS NULL OR u.last_login = '0000-00-00 00:00:00')
    ");
    $neverLoggedIn = (int)$neverStmt->fetchColumn();

    // KYC pending (no approved KYC)
    $kycStmt = $pdo->query("
        SELECT COUNT(DISTINCT u.id)
        FROM users u
        LEFT JOIN kyc_verification_requests k ON u.id = k.user_id AND k.status = 'approved'
        $baseWhere
          AND k.id IS NULL
    ");
    $kycPending = (int)$kycStmt->fetchColumn();

    // Active today (last_login within last 24h)
    $activeTodayStmt = $pdo->query("
        SELECT COUNT(*) FROM users u $baseWhere
          AND u.last_login >= NOW() - INTERVAL 1 DAY
    ");
    $activeToday = (int)$activeTodayStmt->fetchColumn();

    echo json_encode([
        'success'        => true,
        'total'          => $total,
        'never_logged_in' => $neverLoggedIn,
        'kyc_pending'    => $kycPending,
        'active_today'   => $activeToday,
    ]);

} catch (Exception $e) {
    error_log("get_user_stats error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Fehler beim Laden der Statistiken']);
}
