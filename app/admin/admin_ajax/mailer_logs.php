<?php
// admin_ajax/mailer_logs.php — query send logs
require_once '../admin_session.php';
header('Content-Type: application/json');
if (!is_admin_logged_in()) { echo json_encode(['ok' => false, 'message' => 'Unauthorized']); exit; }

$campaignId = (int)($_GET['campaign_id'] ?? 0);
$status     = $_GET['status']     ?? '';
$limit      = min((int)($_GET['limit'] ?? 200), 500);

$allowedStatus = ['sent', 'failed', 'skipped'];

$where  = [];
$params = [];

if ($campaignId) {
    $where[]  = 'l.campaign_id = ?';
    $params[] = $campaignId;
}
if ($status && in_array($status, $allowedStatus, true)) {
    $where[]  = 'l.status = ?';
    $params[] = $status;
}

$sql = "SELECT l.id, l.campaign_id, c.name AS campaign_name,
               l.to_email, l.to_name, s.from_email AS smtp_from,
               l.status, l.error_msg, l.sent_at
          FROM mailer_campaign_logs l
          LEFT JOIN mailer_campaigns c ON c.id = l.campaign_id
          LEFT JOIN mailer_smtp_accounts s ON s.id = l.smtp_id
        " . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . "
         ORDER BY l.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    // Limit applied in PHP to avoid interpolating $limit into SQL string
    $logs = array_slice($stmt->fetchAll(PDO::FETCH_ASSOC), 0, $limit);
    echo json_encode(['ok' => true, 'logs' => $logs]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
