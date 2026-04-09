<?php
/**
 * Password Reset Mailer
 *
 * Sends a password-reset link to the user.
 * Uses SmtpClient (pure-PHP, no Composer required) with SMTP settings
 * stored in the smtp_settings database table.
 *
 * @param PDO    $pdo   Database connection
 * @param array  $user  Must contain: id, first_name, last_name, email
 * @param string $token Password-reset token
 * @return bool         True on success
 */

require_once __DIR__ . '/../../mailer/SmtpClient.php';

function sendPasswordResetEmail(PDO $pdo, array $user, string $token): bool
{
    try {
        // SMTP settings
        $stmt = $pdo->query("SELECT * FROM smtp_settings WHERE id = 1");
        $smtp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$smtp) {
            error_log("sendPasswordResetEmail: SMTP settings not found in database");
            return false;
        }

        // Site settings
        $stmt     = $pdo->query("SELECT site_url, brand_name FROM system_settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        $siteUrl   = rtrim($settings['site_url'] ?? '', '/');
        $brandName = $settings['brand_name'] ?? 'Fund Recovery Services';

        $resetLink = $siteUrl . '/app/reset-password.php?token=' . urlencode($token);
        $firstName = htmlspecialchars($user['first_name'] ?? '');
        $fullName  = htmlspecialchars(
            trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
        );

        $subject = 'Passwort zurücksetzen – ' . $brandName;

        $htmlBody = '
<p>Hallo ' . $firstName . ',</p>

<p>Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts gestellt.
Klicken Sie auf den folgenden Link, um ein neues Passwort festzulegen:</p>

<p style="text-align:center;margin:28px 0;">
  <a href="' . $resetLink . '"
     style="display:inline-block;padding:12px 28px;background:#1a3a5c;color:#ffffff;
            border-radius:8px;text-decoration:none;font-weight:600;">
    Passwort jetzt zurücksetzen
  </a>
</p>

<p>Dieser Link ist <strong>24 Stunden</strong> gültig.</p>

<p>Falls Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren –
Ihr Passwort bleibt unverändert.</p>

<p>Mit freundlichen Grüßen,<br>
<strong>' . htmlspecialchars($brandName) . '</strong></p>
';

        $client = new SmtpClient([
            'host'       => $smtp['host'],
            'port'       => (int)($smtp['port'] ?? 587),
            'username'   => $smtp['username'],
            'password'   => $smtp['password'],
            'from_email' => $smtp['from_email'] ?? $smtp['username'],
            'from_name'  => $smtp['from_name']  ?? $brandName,
            'encryption' => $smtp['encryption'] ?? 'tls',
        ]);

        $client->connect();
        $ok = $client->send($user['email'], $fullName, $subject, $htmlBody);
        $client->quit();

        return $ok;

    } catch (\Throwable $e) {
        error_log("sendPasswordResetEmail error: " . $e->getMessage());
        return false;
    }
}
