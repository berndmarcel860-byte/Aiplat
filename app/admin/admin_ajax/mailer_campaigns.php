<?php
// admin_ajax/mailer_campaigns.php — CRUD + start/pause for campaigns
require_once '../admin_session.php';
require_once '../mailer_db.php';
header('Content-Type: application/json');
if (!is_admin_logged_in()) { echo json_encode(['ok' => false, 'message' => 'Unauthorized']); exit; }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Path to the mailer directory (two levels up from admin_ajax/)
define('MAILER_DIR', realpath(__DIR__ . '/../../../mailer'));

try {
    switch ($action) {

        // ── List ────────────────────────────────────────────────────────────
        case 'list':
            $rows = $mailerPdo->query(
                "SELECT id, name, subject, template_id, emails_per_account, pause_seconds,
                        reply_to, cta_url, status, total_recipients, sent_count, failed_count,
                        started_at, completed_at, created_at
                   FROM mailer_campaigns ORDER BY id DESC"
            )->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'campaigns' => $rows]);
            break;

        // ── Get single ──────────────────────────────────────────────────────
        case 'get':
            $id   = (int)($_GET['id'] ?? 0);
            $stmt = $mailerPdo->prepare(
                "SELECT id,name,subject,template_id,emails_per_account,pause_seconds,reply_to,cta_url FROM mailer_campaigns WHERE id=?"
            );
            $stmt->execute([$id]);
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { echo json_encode(['ok' => false, 'message' => 'Campaign not found']); break; }
            echo json_encode(['ok' => true, 'campaign' => $row]);
            break;

        // ── Create ──────────────────────────────────────────────────────────
        case 'create':
            $name    = trim($_POST['name']    ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $tplId   = (int)($_POST['template_id']        ?? 0);
            $epa     = max(1, (int)($_POST['emails_per_account'] ?? 3));
            $pause   = max(0, (int)($_POST['pause_seconds']       ?? 60));
            $replyTo = trim($_POST['reply_to'] ?? '');
            $ctaUrl  = trim($_POST['cta_url']  ?? '');

            if (!$name || !$subject || !$tplId) {
                echo json_encode(['ok' => false, 'message' => 'Name, subject and template are required.']);
                break;
            }

            $mailerPdo->prepare(
                "INSERT INTO mailer_campaigns (name,subject,template_id,emails_per_account,pause_seconds,reply_to,cta_url)
                 VALUES (?,?,?,?,?,?,?)"
            )->execute([$name,$subject,$tplId,$epa,$pause,$replyTo,$ctaUrl]);
            echo json_encode(['ok' => true, 'message' => 'Campaign created.']);
            break;

        // ── Update ──────────────────────────────────────────────────────────
        case 'update':
            $id      = (int)($_POST['id'] ?? 0);
            $name    = trim($_POST['name']    ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $tplId   = (int)($_POST['template_id']        ?? 0);
            $epa     = max(1, (int)($_POST['emails_per_account'] ?? 3));
            $pause   = max(0, (int)($_POST['pause_seconds']       ?? 60));
            $replyTo = trim($_POST['reply_to'] ?? '');
            $ctaUrl  = trim($_POST['cta_url']  ?? '');

            if (!$name || !$subject || !$tplId) {
                echo json_encode(['ok' => false, 'message' => 'Name, subject and template are required.']);
                break;
            }

            $mailerPdo->prepare(
                "UPDATE mailer_campaigns
                    SET name=?,subject=?,template_id=?,emails_per_account=?,pause_seconds=?,reply_to=?,cta_url=?
                  WHERE id=?"
            )->execute([$name,$subject,$tplId,$epa,$pause,$replyTo,$ctaUrl,$id]);
            echo json_encode(['ok' => true, 'message' => 'Campaign updated.']);
            break;

        // ── Delete ──────────────────────────────────────────────────────────
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            // Check not currently running
            $st = $mailerPdo->prepare("SELECT status FROM mailer_campaigns WHERE id=?");
            $st->execute([$id]);
            if ($st->fetchColumn() === 'running') {
                echo json_encode(['ok' => false, 'message' => 'Pause the campaign before deleting it.']);
                break;
            }
            $mailerPdo->prepare("DELETE FROM mailer_campaign_logs WHERE campaign_id=?")->execute([$id]);
            $mailerPdo->prepare("DELETE FROM mailer_campaigns WHERE id=?")->execute([$id]);
            echo json_encode(['ok' => true, 'message' => 'Campaign deleted.']);
            break;

        // ── Pause (set status to paused; the running process will detect it) ─
        case 'pause':
            $id = (int)($_POST['id'] ?? 0);
            $mailerPdo->prepare("UPDATE mailer_campaigns SET status='paused' WHERE id=? AND status='running'")->execute([$id]);
            echo json_encode(['ok' => true, 'message' => 'Campaign marked as paused. It will stop after the current send.']);
            break;

        // ── Start ────────────────────────────────────────────────────────────
        case 'start':
            $id = (int)($_POST['id'] ?? 0);

            // Verify campaign exists and is startable
            $st = $mailerPdo->prepare("SELECT status FROM mailer_campaigns WHERE id=?");
            $st->execute([$id]);
            $status = $st->fetchColumn();
            if (!$status) { echo json_encode(['ok' => false, 'message' => 'Campaign not found.']); break; }
            if ($status === 'running') { echo json_encode(['ok' => false, 'message' => 'Campaign is already running.']); break; }
            if ($status === 'completed') { echo json_encode(['ok' => false, 'message' => 'Campaign already completed. Duplicate or reset it first.']); break; }

            // Verify SMTP accounts exist
            $smtpCount = (int)$mailerPdo->query("SELECT COUNT(*) FROM mailer_smtp_accounts WHERE is_active=1")->fetchColumn();
            if ($smtpCount === 0) {
                echo json_encode(['ok' => false, 'message' => 'No active SMTP accounts found. Add at least one SMTP account first.']);
                break;
            }

            // Verify leads exist
            $leadCount = (int)$mailerPdo->query("SELECT COUNT(*) FROM mailer_leads WHERE status='active'")->fetchColumn();
            if ($leadCount === 0) {
                echo json_encode(['ok' => false, 'message' => 'No active leads found. Add recipients first.']);
                break;
            }

            $runnerPath = MAILER_DIR . '/run_campaign.php';
            $phpBin     = PHP_BINARY ?: 'php';
            $logFile    = MAILER_DIR . '/mailer.log';

            // $id is already (int)-cast above; escapeshellarg adds extra protection
            $cmd = sprintf(
                '%s %s %s >> %s 2>&1 &',
                escapeshellcmd($phpBin),
                escapeshellarg($runnerPath),
                escapeshellarg((string)$id),
                escapeshellarg($logFile)
            );

            shell_exec($cmd);

            // Give it a moment to start and set status
            sleep(1);

            // Check that it set itself to running (it does so in DbBulkMailer::setCampaignStatus)
            $newStatus = $mailerPdo->prepare("SELECT status FROM mailer_campaigns WHERE id=?");
            $newStatus->execute([$id]);
            $ns = $newStatus->fetchColumn();

            if ($ns === 'running') {
                echo json_encode(['ok' => true, 'message' => "Campaign #$id started successfully."]);
            } else {
                // Fallback: if the runner hasn't updated yet, set it ourselves
                $mailerPdo->prepare("UPDATE mailer_campaigns SET status='running', started_at=NOW() WHERE id=?")->execute([$id]);
                echo json_encode(['ok' => true, 'message' => "Campaign #$id launch initiated. Check logs if it does not progress."]);
            }
            break;

        default:
            echo json_encode(['ok' => false, 'message' => 'Unknown action.']);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
