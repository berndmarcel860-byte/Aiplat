<?php
// admin_ajax/mailer_stats.php — summary stats for the mailer dashboard
require_once '../admin_session.php';
header('Content-Type: application/json');
if (!is_admin_logged_in()) { echo json_encode(['ok' => false]); exit; }

try {
    $smtp      = (int)$pdo->query("SELECT COUNT(*) FROM mailer_smtp_accounts WHERE is_active=1")->fetchColumn();
    $leads     = (int)$pdo->query("SELECT COUNT(*) FROM mailer_leads WHERE status='active'")->fetchColumn();
    $campaigns = (int)$pdo->query("SELECT COUNT(*) FROM mailer_campaigns")->fetchColumn();
    $sent      = (int)$pdo->query("SELECT COALESCE(SUM(sent_count),0) FROM mailer_campaigns")->fetchColumn();

    echo json_encode(['ok' => true, 'smtp' => $smtp, 'leads' => $leads, 'campaigns' => $campaigns, 'sent' => $sent]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
