<?php
// admin_ajax/get_payout_confirmation_logs.php
require_once '../../config.php';
require_once '../admin_session.php';

ini_set('display_errors', '0');
error_reporting(E_ALL);

ob_start();
header('Content-Type: application/json; charset=UTF-8');

function jexit(array $payload, int $code = 200): void {
    http_response_code($code);
    if (ob_get_level()) { ob_end_clean(); }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$withdrawalId = filter_input(INPUT_GET, 'withdrawal_id', FILTER_VALIDATE_INT);
if (!$withdrawalId) {
    jexit(['success' => false, 'message' => 'Invalid withdrawal_id'], 400);
}

try {
    if (method_exists($pdo, 'setAttribute')) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Matches your schema:
    // payout_confirmation_logs:
    // id, user_id, withdrawal_id, admin_id, email_to, subject, pdf_path,
    // status (queued|sent|failed), tracking_token, error_message, created_at, sent_at, opened_at
    $stmt = $pdo->prepare("
        SELECT
            id,
            email_to,
            subject,
            pdf_path,
            status,
            error_message,
            created_at,
            sent_at,
            tracking_token
        FROM payout_confirmation_logs
        WHERE withdrawal_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$withdrawalId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    jexit(['success' => true, 'logs' => $logs]);

} catch (Throwable $e) {
    jexit([
        'success' => false,
        'message' => 'DB error while loading payout logs',
        'error'   => $e->getMessage(),
    ], 500);
}
