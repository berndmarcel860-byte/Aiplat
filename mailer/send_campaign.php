#!/usr/bin/env php
<?php
/**
 * send_campaign.php — CLI & web-callable campaign runner
 *
 * ─── CLI Usage ───────────────────────────────────────────────────────────────
 *
 *   php send_campaign.php \
 *       --recipients=recipients.csv \
 *       --subject="Ihre Anfrage zur Krypto-Analyse" \
 *       --emails-per-account=3 \
 *       --pause=60 \
 *       --reply-to=contact@novalnet-ai.de \
 *       --cta-url=https://novalnet-ai.de/kontakt.php
 *
 * ─── Web Usage (restrict access!) ───────────────────────────────────────────
 *
 *   GET /mailer/send_campaign.php
 *       ?token=YOUR_SECRET_TOKEN
 *       &recipients=recipients.csv
 *       &subject=...
 *
 * ─── Security ────────────────────────────────────────────────────────────────
 *   Set WEB_ACCESS_TOKEN below (or via env var) to prevent unauthorised access.
 *   Better: block this file with .htaccess and run from CLI only.
 *
 * ─── Rate-limiting behaviour ─────────────────────────────────────────────────
 *   emails_per_account  → N emails sent per SMTP account before rotating
 *   pause_seconds       → seconds to sleep after each account rotation
 *   This keeps each sending address well below typical ISP hourly limits.
 */

define('WEB_ACCESS_TOKEN', getenv('MAILER_TOKEN') ?: '');
define('MAILER_DIR', __DIR__);

// ── Initialise ────────────────────────────────────────────────────────────────

require_once MAILER_DIR . '/BulkMailer.php';
require_once MAILER_DIR . '/email_template.php';

$isCli = PHP_SAPI === 'cli';

// ── Argument parsing ──────────────────────────────────────────────────────────

if ($isCli) {
    $opts = getopt('', [
        'recipients:',
        'subject:',
        'emails-per-account:',
        'pause:',
        'reply-to:',
        'cta-url:',
        'unsubscribe-url:',
    ]);

    $recipientFile   = $opts['recipients']          ?? MAILER_DIR . '/recipients.csv';
    $subject         = $opts['subject']             ?? 'Ihre Krypto-Analyse – Vertrauliche Ersteinschätzung';
    $emailsPerAcct   = (int)($opts['emails-per-account'] ?? 3);
    $pause           = (int)($opts['pause']          ?? 60);
    $replyTo         = $opts['reply-to']             ?? '';
    $ctaUrl          = $opts['cta-url']              ?? 'https://novalnet-ai.de/kontakt.php';
    $unsubscribeUrl  = $opts['unsubscribe-url']      ?? 'https://novalnet-ai.de/unsubscribe.php?email={email}';
} else {
    // Web: validate token first
    $token = $_GET['token'] ?? $_POST['token'] ?? '';
    $configuredToken = WEB_ACCESS_TOKEN;
    if ($configuredToken === '' || !hash_equals($configuredToken, $token)) {
        http_response_code(403);
        exit('Forbidden');
    }

    $recipientFile   = MAILER_DIR . '/' . basename($_GET['recipients'] ?? 'recipients.csv');
    $subject         = $_GET['subject']             ?? 'Ihre Krypto-Analyse – Vertrauliche Ersteinschätzung';
    $emailsPerAcct   = max(1, (int)($_GET['emails_per_account'] ?? 3));
    $pause           = max(0, (int)($_GET['pause']               ?? 60));
    $replyTo         = $_GET['reply_to']             ?? '';
    $ctaUrl          = $_GET['cta_url']              ?? 'https://novalnet-ai.de/kontakt.php';
    $unsubscribeUrl  = $_GET['unsubscribe_url']      ?? 'https://novalnet-ai.de/unsubscribe.php?email={email}';

    header('Content-Type: text/plain; charset=UTF-8');
    // Increase time limit for large campaigns
    set_time_limit(0);
}

// ── Build HTML template ───────────────────────────────────────────────────────

$htmlBody = EmailTemplate::build([
    'cta_url'         => $ctaUrl,
    'unsubscribe_url' => $unsubscribeUrl,
]);

// ── Run campaign ──────────────────────────────────────────────────────────────

echo "=== Novalnet AI Bulk Mailer ===\n";
echo "Recipients : $recipientFile\n";
echo "Subject    : $subject\n";
echo "Acct limit : $emailsPerAcct emails/account\n";
echo "Pause      : {$pause}s between account switches\n\n";

try {
    $mailer = new BulkMailer([
        'emails_per_account' => $emailsPerAcct,
        'pause_seconds'      => $pause,
        'reply_to'           => $replyTo,
        'log_file'           => MAILER_DIR . '/mailer.log',
    ]);

    $stats = $mailer->sendCampaign($recipientFile, $subject, $htmlBody);

    echo "\n=== Campaign Complete ===\n";
    echo "Sent   : {$stats['sent']}\n";
    echo "Failed : {$stats['failed']}\n";

    if (!empty($stats['errors'])) {
        echo "\nErrors:\n";
        foreach ($stats['errors'] as $err) {
            echo "  - $err\n";
        }
    }
} catch (RuntimeException $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
