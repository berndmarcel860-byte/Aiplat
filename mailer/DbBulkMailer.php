<?php
/**
 * DbBulkMailer — database-backed version of BulkMailer.
 *
 * Reads SMTP accounts and configuration from the `mailer_smtp_accounts` table
 * instead of a static php file.  Logs every send attempt to
 * `mailer_campaign_logs` and updates campaign stats in real-time.
 *
 * Rotation algorithm (same as BulkMailer):
 *   Send $emailsPerAccount emails → rotate to next account → sleep $pauseSeconds.
 */

require_once __DIR__ . '/SmtpClient.php';

class DbBulkMailer
{
    private PDO    $pdo;
    private int    $campaignId;
    private int    $emailsPerAccount;
    private int    $pauseSeconds;
    private string $replyTo;
    private array  $accounts = [];

    public function __construct(PDO $pdo, int $campaignId, array $options = [])
    {
        $this->pdo              = $pdo;
        $this->campaignId       = $campaignId;
        $this->emailsPerAccount = $options['emails_per_account'] ?? 3;
        $this->pauseSeconds     = $options['pause_seconds']      ?? 60;
        $this->replyTo          = $options['reply_to']           ?? '';

        $this->accounts = $this->loadAccounts();
        if (empty($this->accounts)) {
            throw new RuntimeException('No active SMTP accounts found in the database.');
        }
    }

    /**
     * Run the campaign against a list of recipients.
     *
     * @param array  $recipients  [['email'=>..,'name'=>..,'lead_id'=>..], ...]
     * @param string $subject
     * @param string $htmlBody    Full HTML (with {first_name}/{name}/{email} placeholders)
     * @return array ['sent'=>int,'failed'=>int,'errors'=>[]]
     */
    public function sendCampaign(array $recipients, string $subject, string $htmlBody): array
    {
        $stats = ['sent' => 0, 'failed' => 0, 'errors' => []];

        $this->setCampaignStatus('running');

        $accountIndex = 0;
        $sentThisSlot = 0;
        $client       = null;
        $currentSmtpId = null;

        foreach ($recipients as $i => $recipient) {
            // Re-check campaign status (allows pause/stop from admin UI)
            $currentStatus = $this->getCampaignStatus();
            if ($currentStatus === 'paused' || $currentStatus === 'failed') {
                $this->log("Campaign #{$this->campaignId} stopped by admin (status: $currentStatus).");
                break;
            }

            $toEmail = trim($recipient['email'] ?? '');
            $toName  = trim($recipient['name']  ?? '');
            $leadId  = (int)($recipient['lead_id'] ?? 0) ?: null;

            if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $this->writeLog($leadId, null, $toEmail, $toName, 'skipped', 'Invalid email address');
                continue;
            }

            // ── Account rotation ──────────────────────────────────────────────
            if ($client === null || $sentThisSlot >= $this->emailsPerAccount) {
                if ($client !== null) {
                    $client->quit();
                    $client       = null;
                    $currentSmtpId = null;

                    $accountIndex = ($accountIndex + 1) % count($this->accounts);

                    $this->log("Account switch → #{$accountIndex}. Sleeping {$this->pauseSeconds}s …");
                    sleep($this->pauseSeconds);
                }

                $account = $this->accounts[$accountIndex];
                $currentSmtpId = $account['id'];
                $client  = new SmtpClient($account);

                try {
                    $client->connect();
                    $this->log("Connected: {$account['username']} via {$account['host']}:{$account['port']}");
                } catch (RuntimeException $e) {
                    $msg = "CONNECT ERROR [{$account['username']}]: " . $e->getMessage();
                    $this->log($msg);
                    $stats['failed']++;
                    $stats['errors'][] = $msg;
                    $this->writeLog($leadId, $currentSmtpId, $toEmail, $toName, 'failed', $e->getMessage());
                    $client        = null;
                    $currentSmtpId = null;
                    $accountIndex  = ($accountIndex + 1) % count($this->accounts);
                    $sentThisSlot  = 0;
                    continue;
                }

                $sentThisSlot = 0;
            }

            // ── Personalise & send ────────────────────────────────────────────
            $personalHtml    = $this->personalise($htmlBody, $toEmail, $toName);
            $personalSubject = $this->personalise($subject,  $toEmail, $toName);

            try {
                $ok = $client->send($toEmail, $toName, $personalSubject, $personalHtml, '', $this->replyTo);

                if ($ok) {
                    $stats['sent']++;
                    $sentThisSlot++;
                    $this->writeLog($leadId, $currentSmtpId, $toEmail, $toName, 'sent', '');
                    $this->updateSmtpStats($currentSmtpId);
                    $this->updateCampaignCounts($stats['sent'], $stats['failed']);
                    $this->log("SENT  [$i] → $toEmail");
                } else {
                    throw new RuntimeException('Server rejected the message.');
                }
            } catch (RuntimeException $e) {
                $stats['failed']++;
                $stats['errors'][] = "$toEmail: " . $e->getMessage();
                $this->writeLog($leadId, $currentSmtpId, $toEmail, $toName, 'failed', $e->getMessage());
                $this->updateCampaignCounts($stats['sent'], $stats['failed']);
                $this->log("FAIL  [$i] → $toEmail : " . $e->getMessage());

                $client->quit();
                $client       = null;
                $currentSmtpId = null;
                $sentThisSlot  = 0;
            }
        }

        if ($client !== null) {
            $client->quit();
        }

        $finalStatus = in_array($this->getCampaignStatus(), ['paused', 'failed']) ? $this->getCampaignStatus() : 'completed';
        $this->setCampaignStatus($finalStatus, true);

        $this->log(sprintf(
            "Campaign #%d finished. Sent: %d  Failed: %d",
            $this->campaignId,
            $stats['sent'],
            $stats['failed']
        ));

        return $stats;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function loadAccounts(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, host, port, encryption, username, password, from_email, from_name
               FROM mailer_smtp_accounts
              WHERE is_active = 1
              ORDER BY id ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function personalise(string $content, string $email, string $name): string
    {
        $firstName = explode(' ', $name)[0];
        return str_replace(
            ['{email}', '{name}', '{first_name}'],
            [htmlspecialchars($email, ENT_QUOTES), htmlspecialchars($name, ENT_QUOTES), htmlspecialchars($firstName, ENT_QUOTES)],
            $content
        );
    }

    private function writeLog(?int $leadId, ?int $smtpId, string $email, string $name, string $status, string $error): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO mailer_campaign_logs (campaign_id, lead_id, smtp_id, to_email, to_name, status, error_msg)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$this->campaignId, $leadId, $smtpId, $email, $name, $status, substr($error, 0, 499)]);
    }

    private function updateSmtpStats(int $smtpId): void
    {
        $this->pdo->prepare(
            "UPDATE mailer_smtp_accounts SET emails_sent = emails_sent + 1, last_used_at = NOW() WHERE id = ?"
        )->execute([$smtpId]);
    }

    private function updateCampaignCounts(int $sent, int $failed): void
    {
        $this->pdo->prepare(
            "UPDATE mailer_campaigns SET sent_count = ?, failed_count = ? WHERE id = ?"
        )->execute([$sent, $failed, $this->campaignId]);
    }

    private function setCampaignStatus(string $status, bool $markCompleted = false): void
    {
        if ($markCompleted) {
            $this->pdo->prepare(
                "UPDATE mailer_campaigns SET status = ?, completed_at = NOW() WHERE id = ?"
            )->execute([$status, $this->campaignId]);
        } else {
            if ($status === 'running') {
                $this->pdo->prepare(
                    "UPDATE mailer_campaigns SET status = ?, started_at = NOW() WHERE id = ?"
                )->execute([$status, $this->campaignId]);
            } else {
                $this->pdo->prepare(
                    "UPDATE mailer_campaigns SET status = ? WHERE id = ?"
                )->execute([$status, $this->campaignId]);
            }
        }
    }

    private function getCampaignStatus(): string
    {
        $stmt = $this->pdo->prepare("SELECT status FROM mailer_campaigns WHERE id = ?");
        $stmt->execute([$this->campaignId]);
        return (string)($stmt->fetchColumn() ?: 'draft');
    }

    private function log(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] [Campaign #' . $this->campaignId . '] ' . $message . PHP_EOL;
        $logFile = __DIR__ . '/mailer.log';
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        if (PHP_SAPI === 'cli') {
            echo $line;
        }
    }
}
