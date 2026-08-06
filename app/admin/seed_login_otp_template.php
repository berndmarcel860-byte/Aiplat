<?php
/**
 * Seed: login_otp Email Template
 *
 * Run this script once (e.g., via CLI or browser with admin session) to insert
 * the German login-OTP notification template into the email_templates table.
 *
 * Usage:  php seed_login_otp_template.php
 */

require_once __DIR__ . '/../config.php';

$templateKey = 'login_otp';
$subject     = 'Ihr Anmeldecode für {brand_name}';

$content = <<<'HTML'
<p>Hallo {first_name},</p>

<p>
  Verwenden Sie diesen Code, um sich bei Ihrem Konto anzumelden:
</p>

<div class="highlight-box" style="text-align:center;">
  <h2 style="font-size:36px;font-weight:bold;letter-spacing:10px;color:#2950a8;">{otp_code}</h2>
</div>

<div class="highlight-box">
  <p>
    <strong>⏱️ Gültigkeit:</strong> Dieser Code ist {otp_expires_minutes} Minuten gültig.
  </p>
  <p>
    <strong>🔒 Sicherheit:</strong> Teilen Sie diesen Code niemals mit anderen.
  </p>
</div>

<p>
  Wenn Sie sich nicht angemeldet haben, ignorieren Sie diese E-Mail bitte.
</p>
HTML;

$variables = json_encode([
    'first_name',
    'last_name',
    'email',
    'brand_name',
    'otp_code',
    'otp_expires_minutes',
    'site_url',
]);

try {
    $stmt = $pdo->prepare("SELECT id FROM email_templates WHERE template_key = ?");
    $stmt->execute([$templateKey]);

    if ($stmt->fetch()) {
        echo "Template '$templateKey' already exists – skipping insert.\n";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$templateKey, $subject, $content, $variables]);
        echo "Template '$templateKey' inserted successfully (ID: " . $pdo->lastInsertId() . ").\n";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
