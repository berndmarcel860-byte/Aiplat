<?php
/**
 * Generate Professional Reports
 * Provides various report types with professional formatting
 */
require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$reportType = $_POST['report_type'] ?? '';
$startDate = $_POST['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_POST['end_date'] ?? date('Y-m-d'); // Today

try {
    $reportData = [];
    
    switch ($reportType) {
        case 'users':
            // User Activity Report
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN last_login >= ? THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN last_login IS NULL THEN 1 ELSE 0 END) as never_logged_in,
                    SUM(CASE WHEN last_login < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as inactive_7_days,
                    SUM(CASE WHEN last_login < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as inactive_30_days,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as status_active,
                    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as status_suspended,
                    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified_users,
                    AVG(balance) as avg_balance,
                    SUM(balance) as total_balance
                FROM users
                WHERE created_at BETWEEN ? AND ?
            ");
            $stmt->execute([$startDate, $startDate, $endDate]);
            $reportData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get KYC stats
            $kycStmt = $pdo->prepare("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM kyc_verification_requests
                WHERE created_at BETWEEN ? AND ?
                GROUP BY status
            ");
            $kycStmt->execute([$startDate, $endDate]);
            $reportData['kyc_stats'] = $kycStmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'transactions':
            // Transaction Report
            $stmt = $pdo->prepare("
                SELECT 
                    type,
                    COUNT(*) as count,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount,
                    status
                FROM transactions
                WHERE created_at BETWEEN ? AND ?
                GROUP BY type, status
            ");
            $stmt->execute([$startDate, $endDate]);
            $reportData['transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get deposits
            $depositStmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as count,
                    SUM(amount) as total,
                    AVG(amount) as average
                FROM deposits
                WHERE created_at BETWEEN ? AND ?
            ");
            $depositStmt->execute([$startDate, $endDate]);
            $reportData['deposits'] = $depositStmt->fetch(PDO::FETCH_ASSOC);
            
            // Get withdrawals
            $withdrawalStmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as count,
                    SUM(amount) as total,
                    AVG(amount) as average
                FROM withdrawals
                WHERE created_at BETWEEN ? AND ?
            ");
            $withdrawalStmt->execute([$startDate, $endDate]);
            $reportData['withdrawals'] = $withdrawalStmt->fetch(PDO::FETCH_ASSOC);
            break;
            
        case 'cases':
            // Case Report
            $stmt = $pdo->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(reported_amount) as total_reported,
                    SUM(recovered_amount) as total_recovered,
                    AVG(reported_amount) as avg_reported
                FROM cases
                WHERE created_at BETWEEN ? AND ?
                GROUP BY status
            ");
            $stmt->execute([$startDate, $endDate]);
            $reportData['cases_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Platform statistics
            $platformStmt = $pdo->prepare("
                SELECT 
                    sp.name as platform_name,
                    COUNT(c.id) as case_count,
                    SUM(c.reported_amount) as total_reported,
                    SUM(c.recovered_amount) as total_recovered
                FROM cases c
                JOIN scam_platforms sp ON c.platform_id = sp.id
                WHERE c.created_at BETWEEN ? AND ?
                GROUP BY sp.id
                ORDER BY case_count DESC
                LIMIT 10
            ");
            $platformStmt->execute([$startDate, $endDate]);
            $reportData['top_platforms'] = $platformStmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'financial':
            // Financial Summary
            $stmt = $pdo->prepare("
                SELECT 
                    (SELECT COALESCE(SUM(balance), 0) FROM users) as total_user_balance,
                    (SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'approved' AND created_at BETWEEN ? AND ?) as approved_deposits,
                    (SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'pending' AND created_at BETWEEN ? AND ?) as pending_deposits,
                    (SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'approved' AND created_at BETWEEN ? AND ?) as approved_withdrawals,
                    (SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'pending' AND created_at BETWEEN ? AND ?) as pending_withdrawals,
                    (SELECT COALESCE(SUM(reported_amount), 0) FROM cases WHERE created_at BETWEEN ? AND ?) as cases_reported,
                    (SELECT COALESCE(SUM(recovered_amount), 0) FROM cases WHERE created_at BETWEEN ? AND ?) as cases_recovered
            ");
            $stmt->execute([
                $startDate, $endDate,
                $startDate, $endDate,
                $startDate, $endDate,
                $startDate, $endDate,
                $startDate, $endDate,
                $startDate, $endDate
            ]);
            $reportData = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
            
        case 'activity':
            // Activity Log Report
            $stmt = $pdo->prepare("
                SELECT 
                    al.action,
                    al.entity_type,
                    COUNT(*) as count,
                    a.first_name,
                    a.last_name
                FROM audit_logs al
                LEFT JOIN admins a ON al.admin_id = a.id
                WHERE al.created_at BETWEEN ? AND ?
                GROUP BY al.action, al.entity_type, al.admin_id
                ORDER BY count DESC
                LIMIT 50
            ");
            $stmt->execute([$startDate, $endDate]);
            $reportData['audit_logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'login_activity':
            // Login Activity Report - NEW
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN last_login IS NULL THEN 1 ELSE 0 END) as never_logged_in,
                    SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as active_1_day,
                    SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 3 DAY) THEN 1 ELSE 0 END) as active_3_days,
                    SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as active_7_days,
                    SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as active_30_days,
                    SUM(CASE WHEN last_login < DATE_SUB(NOW(), INTERVAL 3 DAY) AND last_login IS NOT NULL THEN 1 ELSE 0 END) as inactive_3_days,
                    SUM(CASE WHEN last_login < DATE_SUB(NOW(), INTERVAL 7 DAY) AND last_login IS NOT NULL THEN 1 ELSE 0 END) as inactive_7_days,
                    SUM(CASE WHEN last_login < DATE_SUB(NOW(), INTERVAL 30 DAY) AND last_login IS NOT NULL THEN 1 ELSE 0 END) as inactive_30_days
                FROM users
            ");
            $stmt->execute();
            $reportData = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
            
        default:
            throw new Exception('Invalid report type');
    }
    
    echo json_encode([
        'success' => true,
        'report_type' => $reportType,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'generated_at' => date('Y-m-d H:i:s'),
        'data' => $reportData
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error generating report: ' . $e->getMessage()
    ]);
}
