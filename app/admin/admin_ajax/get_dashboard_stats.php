<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Get total users count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users");
    $stmt->execute();
    $totalUsers = $stmt->fetchColumn();

    // Get active cases count
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_cases FROM cases WHERE status NOT IN ('closed', 'refund_rejected')");
    $stmt->execute();
    $activeCases = $stmt->fetchColumn();

    // Get pending withdrawals count
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_withdrawals FROM withdrawals WHERE status = 'pending'");
    $stmt->execute();
    $pendingWithdrawals = $stmt->fetchColumn();

    // Get financial stats
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 'refund_approved' THEN recovered_amount ELSE 0 END) as total_recovered,
            SUM(CASE WHEN status = 'under_review' THEN reported_amount ELSE 0 END) as in_progress_amount,
            SUM(CASE WHEN status = 'open' THEN reported_amount ELSE 0 END) as pending_amount
        FROM cases
    ");
    $stmt->execute();
    $financialStats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_users' => (int)$totalUsers,
            'active_cases' => (int)$activeCases,
            'pending_withdrawals' => (int)$pendingWithdrawals,
            'total_recovered' => (float)$financialStats['total_recovered'],
            'in_progress_amount' => (float)$financialStats['in_progress_amount'],
            'pending_amount' => (float)$financialStats['pending_amount']
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>