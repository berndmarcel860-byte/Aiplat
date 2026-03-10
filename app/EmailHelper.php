<?php
/**
 * Email Helper Class
 * Handles email sending with tracking and dynamic variable replacement
 * Supports template-based and direct HTML emails with comprehensive variable fetching.
 *
 * Usage:
 * $emailHelper = new EmailHelper($pdo);
 * // Send using a template from email_templates table:
 * $emailHelper->sendEmail('onboarding_complete', $userId, $customVariables);
 * // Send a direct HTML email:
 * $emailHelper->sendDirectEmail($userId, 'Subject', '<p>Hello {first_name}</p>', $customVariables);
 */

// Load PHPMailer
$vendorPaths = [
    $_SERVER['DOCUMENT_ROOT'] . '/app/vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php'
];

foreach ($vendorPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    private $pdo;
    private $siteUrl;
    private $brandName;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->siteUrl = $settings['site_url'] ?? '';
        $this->brandName = $settings['brand_name'] ?? 'CryptoFinanz';
    }

    /**
     * Send email using a template from the email_templates table.
     *
     * @param string $templateKey  Template identifier (e.g. 'kyc_approved')
     * @param int    $userId       User ID
     * @param array  $customVars   Additional variables to replace
     * @return bool Success status
     */
    public function sendEmail($templateKey, $userId, $customVars = []) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM email_templates WHERE template_key = ?");
            $stmt->execute([$templateKey]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                throw new Exception("Template not found: $templateKey");
            }

            $variables = $this->getAllVariables($userId, $customVars);

            $subject = $this->replaceVariables($template['subject'], $variables);
            $content = $this->replaceVariables($template['content'], $variables);
            $content = $this->handleConditionals($content, $variables);

            // Always wrap DB-fetched template content in the standard HTML email template
            $content = $this->wrapInTemplate($subject, $content, $variables);

            $user = $this->getUser($userId);
            $trackingToken = bin2hex(random_bytes(16));
            $content = $this->injectTrackingPixel($content, $trackingToken);
            $sent = $this->sendWithPHPMailer($user['email'], $subject, $content);

            $stmt = $this->pdo->prepare("
                INSERT INTO email_logs (
                    template_id, recipient, subject, content,
                    tracking_token, status, sent_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $template['id'],
                $user['email'],
                $subject,
                $content,
                $trackingToken,
                $sent ? 'sent' : 'failed'
            ]);

            return $sent;

        } catch (Exception $e) {
            error_log("EmailHelper - sendEmail error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a direct HTML email without using a stored template.
     * Variables in $subject and $htmlBody are replaced automatically.
     *
     * @param int    $userId     User ID
     * @param string $subject    Email subject (may contain {variable} placeholders)
     * @param string $htmlBody   Email body HTML (may contain {variable} placeholders)
     * @param array  $customVars Additional variables to replace
     * @return bool Success status
     */
    public function sendDirectEmail($userId, $subject, $htmlBody, $customVars = []) {
        try {
            $variables = $this->getAllVariables($userId, $customVars);

            $subject  = $this->replaceVariables($subject, $variables);
            $htmlBody = $this->replaceVariables($htmlBody, $variables);

            // Wrap in professional HTML template if not already a full document
            if (stripos($htmlBody, '<!DOCTYPE') === false && stripos($htmlBody, '<html') === false) {
                $htmlBody = $this->wrapInTemplate($subject, $htmlBody, $variables);
            }

            $user = $this->getUser($userId);
            $trackingToken = bin2hex(random_bytes(16));
            $htmlBody = $this->injectTrackingPixel($htmlBody, $trackingToken);
            $sent = $this->sendWithPHPMailer($user['email'], $subject, $htmlBody);

            $stmt = $this->pdo->prepare("
                INSERT INTO email_logs (recipient, subject, content, tracking_token, sent_at, status)
                VALUES (?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([$user['email'], $subject, $htmlBody, $trackingToken, $sent ? 'sent' : 'failed']);

            if (isset($_SESSION['admin_id'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, created_at)
                    VALUES (?, 'send_email', 'user', ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $_SESSION['admin_id'],
                    $userId,
                    'Sent email: ' . $subject,
                    $_SERVER['REMOTE_ADDR'] ?? ''
                ]);
            }

            return $sent;

        } catch (Exception $e) {
            error_log("EmailHelper - sendDirectEmail error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch all available template variables for a user.
     * Pulls data from users, system_settings, user_payment_methods,
     * user_onboarding, and cases tables.
     *
     * @param int   $userId     User ID
     * @param array $customVars Additional variables to merge (override built-ins)
     * @return array Associative array of all variables
     */
    public function getAllVariables($userId, $customVars = []) {
        try {
            $user = $this->getUser($userId);

            $stmt = $this->pdo->query("SELECT * FROM system_settings WHERE id = 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $stmt = $this->pdo->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? AND type = 'fiat' LIMIT 1");
            $stmt->execute([$userId]);
            $bankAccount = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $this->pdo->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? AND type = 'crypto' LIMIT 1");
            $stmt->execute([$userId]);
            $cryptoWallet = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $this->pdo->prepare("SELECT * FROM user_onboarding WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $onboarding = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $this->pdo->prepare("SELECT * FROM cases WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$userId]);
            $latestCase = $stmt->fetch(PDO::FETCH_ASSOC);

            $siteUrl     = htmlspecialchars($settings['site_url'] ?? $this->siteUrl);
            $brandName   = htmlspecialchars($settings['brand_name'] ?? $this->brandName);

            $variables = [
                // User data
                'user_id'          => $user['id'],
                'first_name'       => htmlspecialchars($user['first_name'] ?? ''),
                'last_name'        => htmlspecialchars($user['last_name'] ?? ''),
                'full_name'        => htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
                'email'            => htmlspecialchars($user['email'] ?? ''),
                'user_email'       => htmlspecialchars($user['email'] ?? ''),
                'user_first_name'  => htmlspecialchars($user['first_name'] ?? ''),
                'user_last_name'   => htmlspecialchars($user['last_name'] ?? ''),
                'balance'          => number_format($user['balance'] ?? 0, 2, ',', '.') . ' €',
                'status'           => htmlspecialchars($user['status'] ?? ''),
                'created_at'       => isset($user['created_at']) ? date('d.m.Y', strtotime($user['created_at'])) : '',
                'member_since'     => isset($user['created_at']) ? date('d.m.Y', strtotime($user['created_at'])) : '',
                'user_created_at'  => isset($user['created_at']) ? date('d.m.Y H:i', strtotime($user['created_at'])) : '',
                'is_verified'      => ($user['is_verified'] ?? 0) ? 'Ja' : 'Nein',
                'kyc_status'       => htmlspecialchars($user['kyc_status'] ?? 'pending'),

                // System / company settings
                'brand_name'           => $brandName,
                'site_name'            => $brandName,
                'site_url'             => $siteUrl,
                'contact_email'        => htmlspecialchars($settings['contact_email'] ?? ''),
                'contact_phone'        => htmlspecialchars($settings['contact_phone'] ?? ''),
                'company_address'      => htmlspecialchars($settings['company_address'] ?? ''),
                'fca_reference_number' => htmlspecialchars($settings['fca_reference_number'] ?? ''),
                'fca_reference'        => htmlspecialchars($settings['fca_reference_number'] ?? ''),
                'logo_url'             => htmlspecialchars($settings['logo_url'] ?? ''),

                // Bank account
                'has_bank_account' => $bankAccount ? 'yes' : 'no',
                'bank_name'        => htmlspecialchars($bankAccount['bank_name'] ?? ''),
                'account_holder'   => htmlspecialchars($bankAccount['account_holder'] ?? ''),
                'iban'             => htmlspecialchars($bankAccount['iban'] ?? ''),
                'bic'              => htmlspecialchars($bankAccount['bic'] ?? ''),
                'bank_country'     => htmlspecialchars($bankAccount['country'] ?? ''),

                // Crypto wallet
                'has_crypto_wallet' => $cryptoWallet ? 'yes' : 'no',
                'cryptocurrency'    => htmlspecialchars($cryptoWallet['cryptocurrency'] ?? ''),
                'network'           => htmlspecialchars($cryptoWallet['network'] ?? ''),
                'wallet_address'    => htmlspecialchars($cryptoWallet['wallet_address'] ?? ''),

                // Onboarding
                'onboarding_completed' => ($onboarding && ($onboarding['completed'] ?? 0)) ? 'Ja' : 'Nein',
                'onboarding_step'      => htmlspecialchars($onboarding['current_step'] ?? ''),

                // Latest case
                'case_number' => htmlspecialchars($latestCase['case_number'] ?? ''),
                'case_status' => htmlspecialchars($latestCase['status'] ?? ''),
                'case_title'  => htmlspecialchars($latestCase['title'] ?? ''),
                'case_amount' => isset($latestCase['amount']) ? number_format($latestCase['amount'], 2, ',', '.') . ' €' : '',

                // System / dynamic
                'current_year'  => date('Y'),
                'current_date'  => date('d.m.Y'),
                'current_time'  => date('H:i'),
                'dashboard_url' => $siteUrl . '/dashboard',
                'login_url'     => $siteUrl . '/login.php',
                'tracking_token' => md5(uniqid($user['email'] ?? '', true)),
            ];

            // Merge custom variables (allow callers to override built-ins)
            // Note: custom variable values are accepted as-is to avoid double-encoding
            // values that callers have already formatted (e.g. number_format, date).
            foreach ($customVars as $key => $value) {
                $variables[$key] = (string)$value;
            }

            return $variables;

        } catch (Exception $e) {
            error_log("EmailHelper - getAllVariables error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Replace {variable} and {{variable}} placeholders in text.
     */
    public function replaceVariables($text, $variables) {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        return $text;
    }

    /**
     * Handle conditional blocks {#if variable}...{/if}
     */
    private function handleConditionals($content, $variables) {
        $pattern = '/\{#if\s+(\w+)\}(.*?)\{\/if\}/s';

        $content = preg_replace_callback($pattern, function($matches) use ($variables) {
            $varName = $matches[1];
            $block   = $matches[2];
            if (isset($variables[$varName]) && $variables[$varName] && $variables[$varName] !== 'no') {
                return $block;
            }
            return '';
        }, $content);

        return $content;
    }

    /**
     * Wrap HTML body content in the standard professional email template.
     *
     * @param string $subject   Email subject (used in title and header)
     * @param string $body      Partial HTML body content
     * @param array  $variables Template variables for substitution
     * @return string Complete HTML email document
     */
    private function wrapInTemplate($subject, $body, $variables) {
        $firstName      = $variables['first_name'] ?? '';
        $lastName       = $variables['last_name'] ?? '';
        $brandName      = $variables['brand_name'] ?? $this->brandName;
        $siteUrl        = $variables['site_url'] ?? $this->siteUrl;
        $contactEmail   = $variables['contact_email'] ?? '';
        $companyAddress = $variables['company_address'] ?? '';
        $fcaReference   = $variables['fca_reference_number'] ?? '';
        $logoUrl        = $variables['logo_url'] ?? '';

        $hasCustomHeader = strpos($body, '<!-- CUSTOM_HEADER -->') !== false;
        $body = str_replace('<!-- CUSTOM_HEADER -->', '', $body);

        return '<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . htmlspecialchars($subject) . '</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      line-height: 1.6;
      color: #333;
      background: #f4f6f8;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 640px;
      margin: 30px auto;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.08);
      overflow: hidden;
    }
    .header {
      background: linear-gradient(90deg, #2950a8 0%, #2da9e3 100%);
      color: #fff;
      text-align: center;
      padding: 30px 20px;
    }
    .header h1 {
      margin: 0;
      font-size: 26px;
      font-weight: 600;
    }
    .header p {
      margin-top: 8px;
      font-size: 15px;
      opacity: 0.9;
    }
    .content {
      padding: 25px;
      background: #f9f9f9;
    }
    .highlight-box {
      background: linear-gradient(90deg, #007bff10 0%, #007bff05 100%);
      border-left: 5px solid #007bff;
      padding: 20px;
      border-radius: 6px;
      margin: 20px 0;
    }
    .highlight-box h3 {
      margin-top: 0;
      color: #007bff;
    }
    .highlight-box p {
      margin: 6px 0;
    }
    .btn {
      display: inline-block;
      background: #007bff;
      color: white;
      padding: 10px 18px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: bold;
      margin-top: 15px;
    }
    .signature {
      margin-top: 40px;
      border-top: 1px solid #e0e0e0;
      padding-top: 25px;
      font-size: 14px;
      color: #555;
      text-align: center;
    }
    .signature img {
      height: 50px;
      margin: 0 auto 12px;
      display: block;
    }
    .signature strong {
      color: #111;
      font-size: 15px;
    }
    .signature a {
      color: #007bff;
      text-decoration: none;
    }
    .signature p {
      font-size: 12px;
      color: #777;
      line-height: 1.5;
      margin-top: 8px;
    }
    .footer {
      text-align: center;
      font-size: 12px;
      color: #777;
      padding: 15px;
      background: #f1f3f5;
    }
    @media only screen and (max-width: 600px) {
      .container { width: 94%; }
      .header h1 { font-size: 22px; }
      .signature img { height: 45px; }
    }
  </style>
</head>
<body>
  <div class="container">'
        . (!$hasCustomHeader ? '
    <div class="header">
      <h1>&#128737; ' . htmlspecialchars($brandName) . '</h1>
      <p>AI-Powered Fund Recovery Platform</p>
    </div>' : '')
        . '
    <div class="content">
      ' . $body . '
    </div>
    <div class="footer">
      <div class="signature">'
        . ($logoUrl ? '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($brandName) . ' Logo"><br>' : '')
        . '
        <strong>' . htmlspecialchars($brandName) . ' Team</strong><br>'
        . ($companyAddress ? htmlspecialchars($companyAddress) . '<br>' : '')
        . ($contactEmail ? 'E: <a href="mailto:' . htmlspecialchars($contactEmail) . '">' . htmlspecialchars($contactEmail) . '</a>' : '')
        . ($siteUrl ? ' | W: <a href="' . htmlspecialchars($siteUrl) . '">' . htmlspecialchars($siteUrl) . '</a>' : '')
        . ($fcaReference ? '
        <p>FCA Reference Nr: ' . htmlspecialchars($fcaReference) . '<br><br>
          <em>Hinweis:</em> Diese E-Mail kann vertrauliche oder rechtlich gesch&uuml;tzte Informationen enthalten.
          Wenn Sie nicht der richtige Adressat sind, informieren Sie uns bitte und l&ouml;schen Sie diese Nachricht.
        </p>' : '')
        . '
      </div>
    </div>
  </div>
  <div class="footer">
    &copy; ' . date('Y') . ' ' . htmlspecialchars($brandName) . '. Alle Rechte vorbehalten.
  </div>
</body>
</html>';
    }

    /**
     * Fetch a single user row from the database.
     *
     * @param int $userId
     * @return array
     * @throws Exception if user is not found
     */
    private function getUser($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new Exception("User not found: $userId");
        }
        return $user;
    }

    /**
     * Check whether an email template key exists in the email_templates table.
     * Returns false (instead of throwing) when the table does not exist or any
     * other database error occurs, so callers can fall back to inline templates.
     *
     * @param string $templateKey
     * @return bool
     */
    private function templateExistsInDb($templateKey) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM email_templates WHERE template_key = ? LIMIT 1");
            $stmt->execute([$templateKey]);
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            error_log("EmailHelper - templateExistsInDb error for '$templateKey': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inject a 1×1 tracking pixel into HTML email body.
     * The pixel calls track_email.php?token=TOKEN so that when the recipient
     * opens the email their mail client loads the pixel and the email_logs row
     * is updated to status='opened' with opened_at timestamp.
     *
     * @param string $html  Email HTML body
     * @param string $token Unique tracking token (also stored in email_logs)
     * @return string HTML with pixel appended
     */
    private function injectTrackingPixel($html, $token) {
        $pixelUrl = rtrim($this->siteUrl, '/') . '/app/track_email.php?token=' . urlencode($token);
        $pixel = '<img src="' . htmlspecialchars($pixelUrl, ENT_QUOTES, 'UTF-8') . '" width="1" height="1" alt="" style="display:none;border:0;" />';
        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $pixel . '</body>', $html);
        }
        return $html . $pixel;
    }

    /**
     * Send an email via SMTP using PHPMailer.
     *
     * @param string $to          Recipient email address
     * @param string $subject     Email subject
     * @param string $htmlContent Full HTML email body
     * @return bool True on success
     * @throws Exception on configuration or send error
     */
    private function sendWithPHPMailer($to, $subject, $htmlContent) {
        $stmt = $this->pdo->query("SELECT * FROM smtp_settings WHERE id = 1");
        $smtp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$smtp) {
            throw new Exception("SMTP settings not found");
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $smtp['host'];
            $mail->SMTPAuth   = !empty($smtp['username']);
            $mail->Username   = $smtp['username'];
            $mail->Password   = $smtp['password'];
            $mail->SMTPSecure = $smtp['encryption'];
            $mail->Port       = $smtp['port'];

            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $htmlContent;
            $mail->AltBody = strip_tags(str_replace(
                ['<br>', '<br/>', '<br />', '</p>', '</div>', '</h1>', '</h2>', '</h3>', '</li>', '</tr>'],
                "\n",
                $htmlContent
            ));

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Send "Ticket erstellt" notification email to a user.
     * Fetches the 'ticket_created' template from the email_templates table and
     * wraps it in the standard HTML email template.  Falls back to an inline
     * template when no database record exists.
     *
     * @param int   $userId     User ID
     * @param array $customVars Must include: ticket_number, ticket_subject,
     *                          ticket_category, ticket_priority
     * @return bool
     */
    public function sendTicketCreatedEmail($userId, $customVars = []) {
        // Use the DB template when available — sendEmail() fetches the record,
        // substitutes variables, and wraps the content in wrapInTemplate().
        if ($this->templateExistsInDb('ticket_created')) {
            return $this->sendEmail('ticket_created', $userId, $customVars);
        }

        // Fallback: inline template (used when the seed script has not been run)
        $subject = 'Ihr Support-Ticket wurde erstellt – {ticket_number}';

        $body = '
<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  vielen Dank für Ihre Kontaktaufnahme. Ihr Support-Ticket wurde erfolgreich
  in unserem System registriert.
</p>

<p>
  Unser <strong>KI-Algorithmus</strong> sowie unser Support-Team werden Ihr
  Anliegen schnellstmöglich analysieren und sich bei Ihnen melden.
</p>

<div class="highlight-box">
  <h3>&#127931; Ticket-Details</h3>
  <p><strong>Ticket-Nummer:</strong> {ticket_number}</p>
  <p><strong>Betreff:</strong> {ticket_subject}</p>
  <p><strong>Kategorie:</strong> {ticket_category}</p>
  <p><strong>Priorität:</strong> {ticket_priority}</p>
  <p><strong>Status:</strong> Offen</p>
</div>

<p>
  Sie werden über jeden weiteren Bearbeitungsschritt automatisch informiert,
  sobald neue Ergebnisse oder Statusänderungen vorliegen.
</p>

<p>
  Den aktuellen Status Ihres Tickets können Sie jederzeit in Ihrem
  <strong>Kundenportal</strong> einsehen und dort weitere Nachrichten oder
  Dokumente hinzufügen.
</p>

<p><a href="{site_url}/app/support.php" class="btn">Zum Kundenportal</a></p>
';

        return $this->sendDirectEmail($userId, $subject, $body, $customVars);
    }

    /**
     * Send "Ticket Antwort" notification email to a user when an admin replies.
     * Fetches the 'ticket_reply' template from the email_templates table and
     * wraps it in the standard HTML email template.  Falls back to an inline
     * template when no database record exists.
     *
     * @param int   $userId     User ID
     * @param array $customVars Must include: ticket_number, ticket_subject,
     *                          ticket_status, reply_message
     * @return bool
     */
    public function sendTicketReplyEmail($userId, $customVars = []) {
        // Use the DB template when available — sendEmail() fetches the record,
        // substitutes variables, and wraps the content in wrapInTemplate().
        if ($this->templateExistsInDb('ticket_reply')) {
            return $this->sendEmail('ticket_reply', $userId, $customVars);
        }

        // Fallback: inline template (used when the seed script has not been run)
        $subject = 'Neue Antwort zu Ihrem Ticket {ticket_number}';

        $body = '
<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  unser Support-Team hat eine neue Antwort zu Ihrem Support-Ticket hinzugefügt.
</p>

<div class="highlight-box">
  <h3>&#127931; Ticket-Details</h3>
  <p><strong>Ticket-Nummer:</strong> {ticket_number}</p>
  <p><strong>Betreff:</strong> {ticket_subject}</p>
  <p><strong>Status:</strong> {ticket_status}</p>
</div>

<div class="highlight-box">
  <h3>&#128172; Antwort des Support-Teams</h3>
  <p>{reply_message}</p>
</div>

<p>
  Sie können die vollständige Konversation einsehen und antworten, indem Sie
  Ihr <strong>Kundenportal</strong> besuchen.
</p>

<p><a href="{site_url}/app/support.php" class="btn">Ticket ansehen</a></p>
';

        return $this->sendDirectEmail($userId, $subject, $body, $customVars);
    }

    /**
     * Send a login OTP email to a user.
     * Uses the 'login_otp' template from the email_templates table when it
     * exists; otherwise falls back to an inline German template.
     *
     * @param int    $userId     User ID
     * @param array  $customVars Must include: otp_code, otp_expires_minutes
     * @return bool
     */
    public function sendLoginOtpEmail($userId, $customVars = []) {
        if ($this->templateExistsInDb('login_otp')) {
            return $this->sendEmail('login_otp', $userId, $customVars);
        }

        // Fallback inline template
        $subject = 'Ihr Anmeldecode für {brand_name}';

        $body = '
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
';

        return $this->sendDirectEmail($userId, $subject, $body, $customVars);
    }

    /**
     * Send a confirmation email to someone who submitted the public
     * register-request form (no user account required).
     *
     * The email is written in professional German and confirms receipt of the
     * request.  When the 'register_request' template exists in email_templates
     * it is used; otherwise a built-in inline template is used as fallback.
     *
     * @param array $requestData Must include: first_name, last_name, email,
     *                           phone, amount, year.
     *                           Optional: platforms, details.
     * @return bool
     */
    public function sendRegisterRequestEmail(array $requestData) {
        try {
            $stmt = $this->pdo->query("SELECT * FROM system_settings WHERE id = 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $brandName      = htmlspecialchars($settings['brand_name']      ?? $this->brandName);
            $contactEmail   = htmlspecialchars($settings['contact_email']   ?? '');
            $siteUrl        = htmlspecialchars($settings['site_url']        ?? $this->siteUrl);
            $companyAddress = htmlspecialchars($settings['company_address'] ?? '');
            $fcaReference   = htmlspecialchars($settings['fca_reference_number'] ?? '');
            $logoUrl        = htmlspecialchars($settings['logo_url']        ?? '');

            $firstName = htmlspecialchars($requestData['first_name'] ?? '');
            $lastName  = htmlspecialchars($requestData['last_name']  ?? '');
            $email     = trim($requestData['email'] ?? '');
            $phone     = htmlspecialchars($requestData['phone']     ?? '');
            $amount    = htmlspecialchars($requestData['amount']    ?? '');
            $year      = htmlspecialchars((string)($requestData['year'] ?? ''));
            $platforms = htmlspecialchars($requestData['platforms'] ?? '');
            $details   = htmlspecialchars($requestData['details']   ?? '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("EmailHelper::sendRegisterRequestEmail — invalid email address: $email");
                return false;
            }

            $variables = [
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'request_email'  => htmlspecialchars($email),
                'phone'          => $phone,
                'amount'         => $amount,
                'year'           => $year,
                'platforms'      => $platforms,
                'details'        => $details,
                'brand_name'     => $brandName,
                'contact_email'  => $contactEmail,
                'site_url'       => $siteUrl,
                'company_address' => $companyAddress,
                'fca_reference_number' => $fcaReference,
                'logo_url'       => $logoUrl,
                'current_date'   => date('d.m.Y'),
                'current_year'   => date('Y'),
            ];

            // Try to fetch a DB template first
            $dbTemplate = null;
            $stmt2 = $this->pdo->prepare("SELECT * FROM email_templates WHERE template_key = 'register_request' LIMIT 1");
            $stmt2->execute();
            $dbTemplate = $stmt2->fetch(PDO::FETCH_ASSOC);

            if ($dbTemplate) {
                $subject = $this->replaceVariables($dbTemplate['subject'], $variables);
                $body    = $this->replaceVariables($dbTemplate['content'], $variables);
            } else {
                // Inline fallback template (professional German)
                $subject = "Vielen Dank für Ihre Anfrage – {brand_name}";
                $subject = $this->replaceVariables($subject, $variables);

                $body = '
<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  vielen Dank für Ihre Kontaktaufnahme und das entgegengebrachte Vertrauen.
  Wir haben Ihre Anfrage erfolgreich erhalten und bestätigen den Eingang
  Ihrer Informationen.
</p>

<div class="highlight-box">
  <h3>&#10003; Ihre Anfrage ist bei uns eingegangen</h3>
  <p>
    Unser Expertenteam wird Ihre Angaben sorgfältig prüfen und sich
    <strong>so schnell wie möglich</strong> bei Ihnen melden, um die
    nächsten Schritte zu besprechen.
  </p>
</div>

<p><strong>Zusammenfassung Ihrer übermittelten Angaben:</strong></p>
<ul>
  <li><strong>Name:</strong> {first_name} {last_name}</li>
  <li><strong>E-Mail:</strong> {request_email}</li>
  <li><strong>Telefon:</strong> {phone}</li>
  <li><strong>Geschätzter Verlustbetrag:</strong> {amount} €</li>
  <li><strong>Jahr des Verlusts:</strong> {year}</li>
  <li><strong>Betroffene Plattformen:</strong> {platforms}</li>
</ul>

<div class="highlight-box">
  <h3>&#128221; Ihre Schilderung</h3>
  <p>{details}</p>
</div>

<p>
  Falls Sie in der Zwischenzeit Fragen haben oder weitere Informationen
  bereitstellen möchten, stehen wir Ihnen jederzeit unter
  <a href="mailto:{contact_email}">{contact_email}</a> zur Verfügung.
</p>

<p>
  Wir danken Ihnen für Ihr Vertrauen und freuen uns darauf, Ihnen bei der
  Rückforderung Ihrer verlorenen Gelder behilflich zu sein.
</p>

<p>
  Mit freundlichen Grüßen,<br>
  <strong>Das Beratungsteam von {brand_name}</strong>
</p>';
                $body = $this->replaceVariables($body, $variables);
            }

            // Wrap in the standard professional HTML email template
            $htmlBody = $this->wrapInTemplate($subject, $body, $variables);

            $trackingToken = bin2hex(random_bytes(16));
            $htmlBody = $this->injectTrackingPixel($htmlBody, $trackingToken);
            $sent = $this->sendWithPHPMailer($email, $subject, $htmlBody);

            $logStmt = $this->pdo->prepare(
                "INSERT INTO email_logs (recipient, subject, content, tracking_token, sent_at, status)
                 VALUES (?, ?, ?, ?, NOW(), ?)"
            );
            $logStmt->execute([$email, $subject, $htmlBody, $trackingToken, $sent ? 'sent' : 'failed']);

            return $sent;

        } catch (Exception $e) {
            error_log("EmailHelper::sendRegisterRequestEmail error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a KYC pending notification email to a user.
     * Uses the 'kyc_pending' template from the email_templates table when it
     * exists; otherwise falls back to an inline German template.
     *
     * @param int   $userId     User ID
     * @param array $customVars Must include: document_type, kyc_id
     * @return bool
     */
    public function sendKycPendingEmail($userId, $customVars = []) {
        if ($this->templateExistsInDb('kyc_pending')) {
            return $this->sendEmail('kyc_pending', $userId, $customVars);
        }

        $documentType = htmlspecialchars($customVars['document_type'] ?? '');
        $kycId        = htmlspecialchars((string)($customVars['kyc_id'] ?? ''));

        $subject = 'KYC-Verifizierung ausstehend – ' . $kycId;

        $body = '
<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>vielen Dank für die Einreichung Ihrer KYC-Dokumente. Wir haben Ihre Unterlagen erfolgreich erhalten.</p>

<div class="highlight-box">
  <h3>&#128203; Einreichungsdetails</h3>
  <p><strong>KYC-ID:</strong> ' . $kycId . '</p>
  <p><strong>Dokumenttyp:</strong> ' . $documentType . '</p>
  <p><strong>Status:</strong> In Bearbeitung</p>
</div>

<p>Unser Team wird Ihre Dokumente innerhalb von 1–3 Werktagen überprüfen. Nach erfolgreicher Verifizierung wird Ihr Konto vollständig freigeschaltet.</p>

<p>Sie können den Status Ihrer KYC-Verifizierung jederzeit in Ihrem <strong>Kundenportal</strong> einsehen.</p>

<p><a href="{site_url}/app/profile.php" class="btn">Zum Kundenportal</a></p>
';

        return $this->sendDirectEmail($userId, $subject, $body, $customVars);
    }

    /**
     * Send an admin notification email about a newly created support ticket.
     * Reads the admin contact address from system_settings.contact_email.
     *
     * @param array $customVars Must include: ticket_number, ticket_subject,
     *                          ticket_category, ticket_priority
     * @return bool
     */
    public function sendAdminNewTicketEmail($customVars = []) {
        try {
            $stmt = $this->pdo->query("SELECT contact_email, brand_name FROM system_settings WHERE id = 1");
            $settings   = $stmt->fetch(PDO::FETCH_ASSOC);
            $adminEmail = $settings['contact_email'] ?? '';
            $brandName  = $settings['brand_name'] ?? $this->brandName;

            if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                error_log("EmailHelper::sendAdminNewTicketEmail — admin contact_email not configured");
                return false;
            }

            $ticketNumber  = htmlspecialchars($customVars['ticket_number']  ?? '');
            $ticketSubject = htmlspecialchars($customVars['ticket_subject'] ?? '');
            $ticketCat     = htmlspecialchars($customVars['ticket_category'] ?? '');
            $ticketPrio    = htmlspecialchars($customVars['ticket_priority'] ?? '');

            $subject = "Neues Support-Ticket: $ticketNumber";

            $bodyContent = '
<p>Ein neues Support-Ticket wurde erstellt.</p>

<div class="highlight-box">
  <h3>&#127931; Ticket-Details</h3>
  <p><strong>Ticket-Nr.:</strong> ' . $ticketNumber . '</p>
  <p><strong>Betreff:</strong> ' . $ticketSubject . '</p>
  <p><strong>Kategorie:</strong> ' . $ticketCat . '</p>
  <p><strong>Priorität:</strong> ' . $ticketPrio . '</p>
</div>

<p><a href="' . htmlspecialchars($this->siteUrl) . '/app/admin/admin_support_tickets.php" class="btn">Ticket ansehen</a></p>
';

            $variables = [
                'brand_name'           => htmlspecialchars($brandName),
                'site_url'             => htmlspecialchars($this->siteUrl),
                'contact_email'        => '',
                'company_address'      => '',
                'fca_reference_number' => '',
                'logo_url'             => '',
                'first_name'           => 'Admin',
                'last_name'            => '',
            ];

            $htmlBody = $this->wrapInTemplate($subject, $bodyContent, $variables);

            $trackingToken = bin2hex(random_bytes(16));
            $htmlBody = $this->injectTrackingPixel($htmlBody, $trackingToken);
            $sent = $this->sendWithPHPMailer($adminEmail, $subject, $htmlBody);

            $logStmt = $this->pdo->prepare("INSERT INTO email_logs (recipient, subject, content, tracking_token, sent_at, status) VALUES (?, ?, ?, ?, NOW(), ?)");
            $logStmt->execute([$adminEmail, $subject, $htmlBody, $trackingToken, $sent ? 'sent' : 'failed']);

            return $sent;

        } catch (Exception $e) {
            error_log("EmailHelper::sendAdminNewTicketEmail error: " . $e->getMessage());
            return false;
        }
    }
}