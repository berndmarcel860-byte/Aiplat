<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../session.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify the request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['error' => 'This endpoint is only accessible via AJAX']));
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized - Please login']));
}

header('Content-Type: application/json');

try {
    // Get user ID from session
    $userId = $_SESSION['user_id'];
    
    // Initialize response array
    $response = [
        'success' => true,
        'message' => 'Recovery status updated',
        'recoveryPercentage' => 0,
        'totalCases' => 0,
        'activeCases' => 0,
        'totalReported' => 0,
        'totalRecovered' => 0,
        'lastUpdated' => date('Y-m-d H:i:s')
    ];
    
    // Get overall recovery stats from cases table
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_cases,
            COALESCE(SUM(reported_amount), 0) as total_reported,
            COALESCE(SUM(recovered_amount), 0) as total_recovered,
            MAX(updated_at) as last_updated
        FROM cases 
        WHERE user_id = ?
    ");
    $statsStmt->execute([$userId]);
    
    if ($statsStmt->rowCount() > 0) {
        $stats = $statsStmt->fetch();
        $response['totalCases'] = (int)$stats['total_cases'];
        $response['totalReported'] = (float)$stats['total_reported'];
        $response['totalRecovered'] = (float)$stats['total_recovered'];
        $response['lastUpdated'] = $stats['last_updated'] ?: $response['lastUpdated'];
        
        // Calculate recovery percentage based on actual database records
        if ($stats['total_reported'] > 0) {
            $response['recoveryPercentage'] = round(($stats['total_recovered'] / $stats['total_reported']) * 100, 2);
        }
    }
    
    // Get active cases count (excluding closed cases)
    $activeStmt = $pdo->prepare("
        SELECT COUNT(*) as active_cases
        FROM cases
        WHERE user_id = ? AND status NOT IN ('closed')
    ");
    $activeStmt->execute([$userId]);
    $activeCases = $activeStmt->fetch();
    $response['activeCases'] = (int)$activeCases['active_cases'];
    
    // Get individual case progress updates with actual recovery data
    $casesStmt = $pdo->prepare("
        SELECT 
            c.id, 
            c.case_number, 
            c.status, 
            c.recovery_progress, 
            c.reported_amount, 
            c.recovered_amount,
            c.created_at,
            c.updated_at,
            p.name as platform_name,
            p.logo as platform_logo,
            (SELECT COUNT(*) FROM case_documents WHERE case_id = c.id AND verified = 1) as documents_verified,
            (SELECT COUNT(*) FROM case_status_history WHERE case_id = c.id) as status_changes,
            (SELECT SUM(amount) FROM case_recovery_transactions WHERE case_id = c.id) as total_recovered_transactions
        FROM cases c
        JOIN scam_platforms p ON c.platform_id = p.id
        WHERE c.user_id = ? AND c.status NOT IN ('closed', 'refund_rejected')
        ORDER BY c.updated_at DESC
        LIMIT 3
    ");
    $casesStmt->execute([$userId]);
    $response['cases'] = $casesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add additional recovery metrics from related tables
    foreach ($response['cases'] as &$case) {
        // Ensure we're using the most accurate recovered amount (from transactions if available)
        $recoveredAmount = $case['total_recovered_transactions'] ?: $case['recovered_amount'];
        $reportedAmount = $case['reported_amount'];
        
        // Calculate percentages
        if ($reportedAmount > 0) {
            $case['recovered_percentage'] = round(($recoveredAmount / $reportedAmount) * 100, 2);
            $case['remaining_percentage'] = 100 - $case['recovered_percentage'];
        } else {
            $case['recovered_percentage'] = 0;
            $case['remaining_percentage'] = 0;
        }
        
        // Format amounts for display
        $case['formatted_reported_amount'] = number_format($reportedAmount, 2);
        $case['formatted_recovered_amount'] = number_format($recoveredAmount, 2);
        
        // Get latest recovery transaction if exists
        $txnStmt = $pdo->prepare("
            SELECT amount, transaction_date, notes
            FROM case_recovery_transactions
            WHERE case_id = ?
            ORDER BY transaction_date DESC
            LIMIT 1
        ");
        $txnStmt->execute([$case['id']]);
        $case['latest_recovery'] = $txnStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get status history count
        $historyStmt = $pdo->prepare("
            SELECT COUNT(*) as history_count
            FROM case_status_history
            WHERE case_id = ?
        ");
        $historyStmt->execute([$case['id']]);
        $history = $historyStmt->fetch();
        $case['status_history_count'] = (int)$history['history_count'];
    }
    
    // Get system-wide recovery statistics for comparison
    $systemStmt = $pdo->prepare("
        SELECT 
            AVG(recovered_amount/reported_amount)*100 as avg_recovery_rate,
            COUNT(*) as total_cases
        FROM cases
        WHERE reported_amount > 0 AND status = 'closed'
    ");
    $systemStmt->execute();
    $systemStats = $systemStmt->fetch();
    $response['systemStats'] = [
        'avgRecoveryRate' => round($systemStats['avg_recovery_rate'], 2),
        'totalCases' => (int)$systemStats['total_cases']
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error in get_recovery_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => 'Could not fetch recovery status'
    ]);
} catch (Exception $e) {
    error_log("Error in get_recovery_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => 'An unexpected error occurred'
    ]);
}