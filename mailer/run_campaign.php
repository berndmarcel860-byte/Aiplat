<?php
/**
 * run_campaign.php — executes a single campaign in the background.
 *
 * Called via CLI by the admin AJAX handler (mailer_campaigns.php).
 * Never expose this file to the public web.
 *
 * Usage:
 *   php run_campaign.php <campaign_id>
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden — CLI only.');
}

$campaignId = (int)($argv[1] ?? 0);
if ($campaignId <= 0) {
    fwrite(STDERR, "Usage: php run_campaign.php <campaign_id>\n");
    exit(1);
}

// Load DB connection (identical credentials to the main app)
$configFile = __DIR__ . '/../app/config.php';
if (!file_exists($configFile)) {
    fwrite(STDERR, "config.php not found at: $configFile\n");
    exit(1);
}
require_once $configFile;

require_once __DIR__ . '/SmtpClient.php';
require_once __DIR__ . '/DbBulkMailer.php';

// ── Load campaign ─────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM mailer_campaigns WHERE id = ?");
$stmt->execute([$campaignId]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    fwrite(STDERR, "Campaign #$campaignId not found.\n");
    exit(1);
}

if ($campaign['status'] === 'running') {
    fwrite(STDERR, "Campaign #$campaignId is already running.\n");
    exit(1);
}

// ── Load HTML template ────────────────────────────────────────────────────────
$htmlBody = '';
if ($campaign['template_id']) {
    $tStmt = $pdo->prepare("SELECT html_body FROM mailer_templates WHERE id = ?");
    $tStmt->execute([$campaign['template_id']]);
    $tRow = $tStmt->fetch(PDO::FETCH_ASSOC);
    if ($tRow) {
        $htmlBody = $tRow['html_body'];
    }
}

if (empty($htmlBody)) {
    fwrite(STDERR, "No HTML template body found for campaign #$campaignId.\n");
    exit(1);
}

// Wrap partial template in full HTML envelope (inbox-rendering)
$htmlBody = wrapEmailBody($htmlBody, $campaign);

// ── Load recipients (active leads not already sent to in this campaign) ────────
$leadsStmt = $pdo->prepare(
    "SELECT l.id AS lead_id, l.email, l.name
       FROM mailer_leads l
      WHERE l.status = 'active'
        AND l.id NOT IN (
              SELECT COALESCE(lead_id, 0)
                FROM mailer_campaign_logs
               WHERE campaign_id = ? AND status = 'sent'
            )
      ORDER BY l.id ASC"
);
$leadsStmt->execute([$campaignId]);
$recipients = $leadsStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($recipients)) {
    fwrite(STDOUT, "No eligible recipients for campaign #$campaignId.\n");
    // Mark completed if nothing to send
    $pdo->prepare("UPDATE mailer_campaigns SET status='completed', completed_at=NOW() WHERE id=?")->execute([$campaignId]);
    exit(0);
}

// Update total_recipients count
$pdo->prepare("UPDATE mailer_campaigns SET total_recipients = ? WHERE id = ?")->execute([count($recipients), $campaignId]);

// ── Run ───────────────────────────────────────────────────────────────────────
try {
    $mailer = new DbBulkMailer($pdo, $campaignId, [
        'emails_per_account' => (int)$campaign['emails_per_account'],
        'pause_seconds'      => (int)$campaign['pause_seconds'],
        'reply_to'           => $campaign['reply_to'],
    ]);

    $stats = $mailer->sendCampaign($recipients, $campaign['subject'], $htmlBody);

    echo "=== Campaign #{$campaignId} Complete ===\n";
    echo "Sent  : {$stats['sent']}\n";
    echo "Failed: {$stats['failed']}\n";
} catch (RuntimeException $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    $pdo->prepare("UPDATE mailer_campaigns SET status='failed' WHERE id=?")->execute([$campaignId]);
    exit(1);
}

// ── Helper: wrap partial HTML in a full inbox-safe envelope ──────────────────

function wrapEmailBody(string $partial, array $campaign): string
{
    // Resolve brand settings from DB if possible
    global $pdo;
    $settings = [];
    try {
        $s = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $settings = $s;
    } catch (Exception $e) { }

    $brand   = $settings['brand_name']      ?? 'Novalnet AI';
    $siteUrl = $settings['site_url']        ?? 'https://novalnet-ai.de';
    $addr    = $settings['company_address'] ?? 'Novalnet AI GmbH · BaFin-reg. · Deutschland';
    $ctaUrl  = !empty($campaign['cta_url']) ? $campaign['cta_url'] : $siteUrl . '/kontakt.php';
    $unsubUrl = $siteUrl . '/unsubscribe.php?email={email}';

    // Replace any {cta_url} placeholder in the partial
    $partial = str_replace('{cta_url}', htmlspecialchars($ctaUrl, ENT_QUOTES), $partial);

    $blue = '#0d6efd';

    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>$brand</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#f4f6f9;">
<tr><td align="center" style="padding:32px 16px;">
  <table role="presentation" width="600" border="0" cellspacing="0" cellpadding="0"
         style="max-width:600px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    <!-- Header -->
    <tr>
      <td style="background:linear-gradient(135deg,$blue 0%,#0b5ed7 100%);padding:28px 40px;text-align:center;">
        <p style="margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.02em;">$brand</p>
        <p style="margin:6px 0 0;font-size:12px;color:rgba(255,255,255,0.75);letter-spacing:0.08em;text-transform:uppercase;">
          KI-gestützte Blockchain-Forensik · BaFin-lizenziert
        </p>
      </td>
    </tr>
    <!-- Body -->
    <tr>
      <td style="padding:40px 40px 32px;">
        $partial
        <!-- CTA button -->
        <table role="presentation" border="0" cellspacing="0" cellpadding="0" style="margin:24px 0 32px;">
          <tr>
            <td style="border-radius:8px;background:$blue;">
              <a href="$ctaUrl"
                 style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">
                Jetzt unverbindlich anfragen →
              </a>
            </td>
          </tr>
        </table>
        <!-- Trust badges -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0"
               style="border-top:1px solid #e9ecef;padding-top:20px;">
          <tr>
            <td align="center">
              <p style="margin:0;font-size:12px;color:#6c757d;line-height:1.8;">
                🔒 BaFin-lizenziert &nbsp;|&nbsp; 🏅 ISO 27001 &nbsp;|&nbsp; ⚖️ DSGVO-konform
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <!-- Footer -->
    <tr>
      <td style="background:#f8f9fa;padding:24px 40px;border-top:1px solid #e9ecef;">
        <p style="margin:0 0 8px;font-size:12px;color:#6c757d;line-height:1.6;text-align:center;">
          Diese Nachricht wurde an <a href="mailto:{email}" style="color:#6c757d;">{email}</a> gesendet.
        </p>
        <p style="margin:0 0 8px;font-size:12px;color:#6c757d;text-align:center;">$addr</p>
        <p style="margin:0;font-size:12px;text-align:center;">
          <a href="$unsubUrl" style="color:#6c757d;text-decoration:underline;">Abmelden / Unsubscribe</a>
          &nbsp;|&nbsp;
          <a href="$siteUrl/datenschutz.php" style="color:#6c757d;text-decoration:underline;">Datenschutz</a>
          &nbsp;|&nbsp;
          <a href="$siteUrl/impressum.php" style="color:#6c757d;text-decoration:underline;">Impressum</a>
        </p>
      </td>
    </tr>
  </table>
</td></tr>
</table>
</body>
</html>
HTML;
}
