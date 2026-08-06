<?php
/**
 * BulkMailer — rotating SMTP account manager
 *
 * Algorithm
 * ---------
 *  1. Load the SMTP account pool from smtp_accounts.php.
 *  2. Iterate through recipients (array or CSV file path).
 *  3. Every $emailsPerAccount emails, rotate to the next account and
 *     sleep $pauseSeconds seconds to avoid rate-limit flags.
 *  4. Log every send attempt (success / failure) with timestamp.
 *
 * Usage (CLI or web)
 * ------------------
 *   require_once __DIR__ . '/BulkMailer.php';
 *
 *   $mailer = new BulkMailer([
 *       'emails_per_account' => 3,          // switch after this many sends
 *       'pause_seconds'      => 60,         // sleep between account switches
 *       'log_file'           => __DIR__ . '/mailer.log',
 *       'reply_to'           => 'contact@novalnet-ai.de',
 *   ]);
 *
 *   $mailer->sendCampaign(
 *       __DIR__ . '/recipients.csv',         // or an array of ['email','name']
 *       'Krypto-Wiederherstellung – Kostenlose Ersteinschätzung',
 *       EmailTemplate::build(),              // HTML from email_template.php
 *   );
 */

require_once __DIR__ . '/SmtpClient.php';

class BulkMailer
{
    private array  $accounts;
    private int    $emailsPerAccount;
    private int    $pauseSeconds;
    private string $logFile;
    private string $replyTo;

    public function __construct(array $options = [])
    {
        $this->emailsPerAccount = $options['emails_per_account'] ?? 3;
        $this->pauseSeconds     = $options['pause_seconds']      ?? 60;
        $this->logFile          = $options['log_file']           ?? __DIR__ . '/mailer.log';
        $this->replyTo          = $options['reply_to']           ?? '';

        $accountsFile = $options['accounts_file'] ?? __DIR__ . '/smtp_accounts.php';
        $this->accounts = require $accountsFile;

        if (empty($this->accounts)) {
            throw new RuntimeException('No SMTP accounts configured in smtp_accounts.php');
        }
    }

    /**
     * Run the campaign.
     *
     * @param string|array $recipients  CSV file path OR array of ['email'=>..,'name'=>..]
     * @param string       $subject     Email subject line
     * @param string       $htmlBody    Full HTML email body (use EmailTemplate::build())
     * @param string       $textBody    Plain-text fallback (auto-generated if empty)
     * @return array Statistics: ['sent'=>int, 'failed'=>int, 'errors'=>[]]
     */
    public function sendCampaign(
        $recipients,
        string $subject,
        string $htmlBody,
        string $textBody = ''
    ): array {
        $recipientList = is_array($recipients)
            ? $recipients
            : $this->loadCsv($recipients);

        $stats = ['sent' => 0, 'failed' => 0, 'errors' => []];

        $accountIndex  = 0;
        $sentThisSlot  = 0;
        $client        = null;

        foreach ($recipientList as $i => $recipient) {
            $toEmail = trim($recipient['email'] ?? $recipient[0] ?? '');
            $toName  = trim($recipient['name']  ?? $recipient[1] ?? '');

            if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $this->log("SKIP invalid email: $toEmail");
                continue;
            }

            // --- account rotation ---
            if ($client === null || $sentThisSlot >= $this->emailsPerAccount) {
                // Close previous connection
                if ($client !== null) {
                    $client->quit();
                    $client = null;

                    // Advance to next account
                    $accountIndex = ($accountIndex + 1) % count($this->accounts);

                    // Pause between account switches
                    $this->log(sprintf(
                        "Account switch → account #%d. Sleeping %d s …",
                        $accountIndex + 1,
                        $this->pauseSeconds
                    ));
                    sleep($this->pauseSeconds);
                }

                $account = $this->accounts[$accountIndex];
                $client  = new SmtpClient($account);

                try {
                    $client->connect();
                    $this->log("Connected: {$account['username']} via {$account['host']}:{$account['port']}");
                } catch (RuntimeException $e) {
                    $this->log("CONNECT ERROR [{$account['username']}]: " . $e->getMessage());
                    $stats['failed']++;
                    $stats['errors'][] = "connect:{$account['username']}:" . $e->getMessage();
                    $client = null;
                    // Move to next account immediately
                    $accountIndex = ($accountIndex + 1) % count($this->accounts);
                    $sentThisSlot = 0;
                    continue;
                }

                $sentThisSlot = 0;
            }

            // --- personalise HTML & subject ---
            $personalHtml    = $this->personalise($htmlBody,    $toEmail, $toName);
            $personalText    = $this->personalise($textBody,    $toEmail, $toName);
            $personalSubject = $this->personalise($subject,     $toEmail, $toName);

            // --- send ---
            try {
                $ok = $client->send(
                    $toEmail,
                    $toName,
                    $personalSubject,
                    $personalHtml,
                    $personalText,
                    $this->replyTo
                );

                if ($ok) {
                    $stats['sent']++;
                    $sentThisSlot++;
                    $this->log("SENT  [$i] → $toEmail");
                } else {
                    throw new RuntimeException('Server rejected the message.');
                }
            } catch (RuntimeException $e) {
                $stats['failed']++;
                $stats['errors'][] = "$toEmail:" . $e->getMessage();
                $this->log("FAIL  [$i] → $toEmail : " . $e->getMessage());

                // Reconnect on error
                $client->quit();
                $client = null;
                $sentThisSlot = 0;
            }
        }

        if ($client !== null) {
            $client->quit();
        }

        $this->log(sprintf(
            "Campaign finished. Sent: %d  Failed: %d",
            $stats['sent'],
            $stats['failed']
        ));

        return $stats;
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Replace {email} and {name} placeholders in the template.
     */
    private function personalise(string $content, string $email, string $name): string
    {
        $firstName = explode(' ', $name)[0];
        return str_replace(
            ['{email}', '{name}', '{first_name}'],
            [htmlspecialchars($email, ENT_QUOTES), htmlspecialchars($name, ENT_QUOTES), htmlspecialchars($firstName, ENT_QUOTES)],
            $content
        );
    }

    /**
     * Load recipients from a CSV file.
     * Accepted formats:
     *   email,name          (header row optional)
     *   email               (name defaults to email prefix)
     */
    private function loadCsv(string $filePath): array
    {
        if (!is_readable($filePath)) {
            throw new RuntimeException("Recipients CSV not readable: $filePath");
        }

        $rows   = [];
        $handle = fopen($filePath, 'r');
        $first  = true;

        while (($cols = fgetcsv($handle)) !== false) {
            if ($first) {
                $first = false;
                // Skip header row if first cell looks like a label
                if (strtolower(trim($cols[0] ?? '')) === 'email') {
                    continue;
                }
            }

            $email = trim($cols[0] ?? '');
            $name  = trim($cols[1] ?? '');

            if ($name === '') {
                $atPos = strpos($email, '@');
                $name  = $atPos !== false ? ucfirst(substr($email, 0, $atPos)) : 'Kunde';
            }

            if ($email !== '') {
                $rows[] = ['email' => $email, 'name' => $name];
            }
        }

        fclose($handle);
        return $rows;
    }

    private function log(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);

        // Echo to STDOUT when running from CLI
        if (PHP_SAPI === 'cli') {
            echo $line;
        }
    }
}
