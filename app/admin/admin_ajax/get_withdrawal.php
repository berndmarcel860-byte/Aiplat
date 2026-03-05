<?php
// admin_ajax/get_withdrawal.php
require_once '../../config.php';
require_once '../admin_session.php';

ini_set('display_errors', '0');
error_reporting(E_ALL);

// Buffer to avoid stray output corrupting JSON
ob_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

function jexit(array $payload, int $code = 200): void {
    http_response_code($code);
    // Drop any accidental output captured so far
    if (ob_get_level()) { ob_end_clean(); }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// id can arrive via GET or POST
$withdrawalId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($withdrawalId === null) {
    $withdrawalId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}
if (!$withdrawalId) {
    jexit(['success' => false, 'message' => 'Invalid withdrawal ID'], 400);
}

try {
    // Ensure PDO throws exceptions so we see real SQL errors
    if (method_exists($pdo, 'setAttribute')) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // --- Withdrawal + user + (optional) payment method name ---
    $sql = "
        SELECT 
            w.*,
            u.first_name AS user_first_name,
            u.last_name  AS user_last_name,
            pm.method_name
        FROM withdrawals w
        LEFT JOIN users u
               ON u.id = w.user_id
        LEFT JOIN payment_methods pm
               ON pm.method_code = w.method_code
        WHERE w.id = ?
        LIMIT 1
    ";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$withdrawalId]);
    } catch (Throwable $e) {
        // Fallback without payment_methods join (in case table/column not present)
        $stmt = $pdo->prepare("
            SELECT 
                w.*,
                u.first_name AS user_first_name,
                u.last_name  AS user_last_name,
                w.method_code AS method_name
            FROM withdrawals w
            LEFT JOIN users u ON u.id = w.user_id
            WHERE w.id = ?
            LIMIT 1
        ");
        $stmt->execute([$withdrawalId]);
    }

    $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$withdrawal) {
        jexit(['success' => false, 'message' => 'Withdrawal not found'], 404);
    }

    // --- Logs for this withdrawal from payout_confirmation_logs (schema-matched) ---
    $logs = [];
    try {
        $logStmt = $pdo->prepare("
            SELECT
                pcl.id,
                pcl.email_to,
                pcl.subject,
                pcl.pdf_path,
                pcl.status,
                pcl.error_message,
                pcl.created_at,
                pcl.sent_at,
                pcl.tracking_token
            FROM payout_confirmation_logs pcl
            WHERE pcl.withdrawal_id = ?
            ORDER BY pcl.created_at DESC
        ");
        $logStmt->execute([$withdrawalId]);
        $logs = $logStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        // If logs table isn’t present yet, just return empty logs
        $logs = [];
    }

    jexit([
        'success'    => true,
        'withdrawal' => $withdrawal,
        'logs'       => $logs,
    ]);

} catch (Throwable $e) {
    jexit([
        'success' => false,
        'message' => 'Failed to get withdrawal details',
        'error'   => $e->getMessage(),
    ], 500);
}
