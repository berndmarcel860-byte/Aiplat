<?php
/**
 * Payment Address Security Filter
 *
 * Scans admin-to-user messages for unofficial crypto wallet addresses and IBANs,
 * replaces them with official platform addresses, and logs every interception as
 * a security alert so the platform owner can audit and review misuse attempts.
 *
 * Functions provided:
 *   filterPaymentAddresses()               – filter + log + alert (main entry point)
 *   logPaymentSecurityAlert()              – persist a security alert row + send email
 *   sendPaymentSecurityAlertEmail()        – internal: email the superadmin
 */

// ── Shared address patterns (used by both filter and detection) ──────────────

/**
 * Returns the shared crypto / IBAN pattern configuration.
 */
function _getAddressPatterns(): array
{
    return [
        'bitcoin'  => [
            'pattern'  => '/\b(bc1[ac-hj-np-z02-9]{25,87}|[13][a-km-zA-HJ-NP-Z1-9]{25,34})\b/',
            'keywords' => ['bitcoin', 'btc'],
        ],
        'ethereum' => [
            'pattern'  => '/\b(0x[a-fA-F0-9]{40})\b/',
            'keywords' => ['ethereum', 'eth', 'usdt', 'usdc', 'erc20'],
        ],
        'litecoin' => [
            'pattern'  => '/\b(ltc1[a-z0-9]{25,87}|[LM][a-km-zA-HJ-NP-Z1-9]{25,34})\b/',
            'keywords' => ['litecoin', 'ltc'],
        ],
        'tron'     => [
            'pattern'  => '/\b(T[A-Za-z1-9]{33})\b/',
            'keywords' => ['tron', 'trx', 'trc20'],
        ],
        'ripple'   => [
            'pattern'  => '/\b(r[0-9a-zA-Z]{24,33})\b/',
            'keywords' => ['ripple', 'xrp'],
        ],
    ];
}

// ── Log helper ───────────────────────────────────────────────────────────────

/**
 * Persist a payment security alert and send an email to the superadmin.
 *
 * @param PDO    $pdo
 * @param string $channel          'ticket_reply' | 'live_chat' | 'direct_email' | 'bulk_email'
 * @param int    $adminId          Admin who sent the message
 * @param int|null $userId         Affected user (null for bulk)
 * @param int|null $entityId       ticket_id / chat session_id / null
 * @param string $originalMessage  Message before filtering
 * @param string $filteredMessage  Message after filtering
 * @param array  $foundAddresses   List of unauthorized addresses that were detected
 */
function logPaymentSecurityAlert(
    PDO $pdo,
    string $channel,
    int $adminId,
    ?int $userId,
    ?int $entityId,
    string $originalMessage,
    string $filteredMessage,
    array $foundAddresses
): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO payment_security_alerts
                (channel, admin_id, user_id, entity_id, original_message, filtered_message, addresses_found, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $channel,
            $adminId,
            $userId,
            $entityId,
            $originalMessage,
            $filteredMessage,
            json_encode($foundAddresses),
        ]);
    } catch (Exception $e) {
        error_log('logPaymentSecurityAlert DB error: ' . $e->getMessage());
    }

    // Send alert email to superadmin (non-fatal)
    try {
        sendPaymentSecurityAlertEmail($pdo, $channel, $adminId, $userId, $foundAddresses);
    } catch (Exception $e) {
        error_log('sendPaymentSecurityAlertEmail error: ' . $e->getMessage());
    }
}

/**
 * Send an alert email to the platform superadmin when an unauthorized address
 * was intercepted in an admin-to-user message.
 */
function sendPaymentSecurityAlertEmail(
    PDO $pdo,
    string $channel,
    int $adminId,
    ?int $userId,
    array $foundAddresses
): void {
    // Load SMTP / brand settings
    $settings = $pdo->query("SELECT * FROM system_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    if (empty($settings)) {
        return;
    }

    $adminEmail  = $settings['contact_email'] ?? ($settings['smtp_from'] ?? '');
    $brandName   = $settings['brand_name']    ?? 'Fund Recovery Platform';
    $siteUrl     = rtrim($settings['site_url'] ?? '', '/');

    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    // Fetch admin name
    $adminName = 'Unbekannt';
    try {
        $row = $pdo->prepare("SELECT name, email FROM admins WHERE id = ?");
        $row->execute([$adminId]);
        $adminRow  = $row->fetch(PDO::FETCH_ASSOC);
        if ($adminRow) {
            $adminName = htmlspecialchars($adminRow['name'] ?? 'Unbekannt')
                       . ' (' . htmlspecialchars($adminRow['email'] ?? '') . ')';
        }
    } catch (Exception $e) { /* silent */ }

    // Fetch user name
    $userName = 'Unbekannt';
    if ($userId) {
        try {
            $row = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
            $row->execute([$userId]);
            $userRow = $row->fetch(PDO::FETCH_ASSOC);
            if ($userRow) {
                $userName = htmlspecialchars($userRow['first_name'] . ' ' . $userRow['last_name'])
                          . ' (' . htmlspecialchars($userRow['email']) . ')';
            }
        } catch (Exception $e) { /* silent */ }
    }

    $channelLabels = [
        'ticket_reply'  => 'Support-Ticket-Antwort',
        'live_chat'     => 'Live-Chat',
        'direct_email'  => 'Direkte E-Mail an Benutzer',
        'bulk_email'    => 'Massen-E-Mail an alle Benutzer',
    ];
    $channelLabel = $channelLabels[$channel] ?? $channel;

    $addressList = '';
    foreach ($foundAddresses as $addr) {
        $addressList .= '<li style="font-family:monospace;word-break:break-all;">'
                      . htmlspecialchars($addr) . '</li>';
    }

    $subject = "⚠️ Sicherheitswarnung: Unautorisierte Zahlungsadresse abgefangen – $brandName";
    $body    = "
<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>
  <div style='background:#c0392b;color:#fff;padding:20px 24px;border-radius:8px 8px 0 0;'>
    <h2 style='margin:0;font-size:18px;'>⚠️ Sicherheitswarnung: Nicht autorisierte Zahlungsadresse</h2>
    <p style='margin:6px 0 0;font-size:13px;opacity:.9;'>Plattform-Sicherheitsereignis erkannt</p>
  </div>
  <div style='background:#fff;border:1px solid #e8e8e8;padding:24px;border-radius:0 0 8px 8px;'>
    <p>Eine Nachricht eines Administrators enthielt <strong>nicht autorisierte Zahlungsadressen</strong>, die <strong>automatisch abgefangen und durch offizielle Plattformadressen ersetzt</strong> wurden, bevor sie den Benutzer erreichten.</p>
    <table style='width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;'>
      <tr style='background:#fef9e7;'>
        <td style='padding:10px 12px;border:1px solid #f0c040;font-weight:bold;width:35%;'>Kanal</td>
        <td style='padding:10px 12px;border:1px solid #f0c040;'>$channelLabel</td>
      </tr>
      <tr>
        <td style='padding:10px 12px;border:1px solid #e8e8e8;font-weight:bold;'>Administrator</td>
        <td style='padding:10px 12px;border:1px solid #e8e8e8;'>$adminName</td>
      </tr>
      <tr style='background:#fafafa;'>
        <td style='padding:10px 12px;border:1px solid #e8e8e8;font-weight:bold;'>Betroffener Nutzer</td>
        <td style='padding:10px 12px;border:1px solid #e8e8e8;'>$userName</td>
      </tr>
      <tr>
        <td style='padding:10px 12px;border:1px solid #e8e8e8;font-weight:bold;'>Erkannte Adressen</td>
        <td style='padding:10px 12px;border:1px solid #e8e8e8;'><ul style='margin:0;padding-left:18px;'>$addressList</ul></td>
      </tr>
    </table>
    <div style='background:#fdf2f2;border-left:4px solid #c0392b;padding:14px 16px;border-radius:4px;margin:16px 0;'>
      <strong style='color:#c0392b;'>Maßnahmen:</strong>
      <ul style='margin:8px 0 0;padding-left:18px;font-size:13px;'>
        <li>Die Nachricht wurde automatisch gefiltert – der Benutzer hat <strong>keine</strong> unautorisierte Adresse erhalten.</li>
        <li>Bitte überprüfen Sie den Vorfall im Admin-Bereich unter <strong>Sicherheit → Zahlungssicherheit</strong>.</li>
        <li>Wenn dies mutwillig geschah, sollten Sie den Administrator-Zugang sofort einschränken.</li>
      </ul>
    </div>
    <p style='text-align:center;margin-top:20px;'>
      <a href='{$siteUrl}/app/admin/admin_payment_security.php'
         style='background:#c0392b;color:#fff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;display:inline-block;'>
        Sicherheitsalerts überprüfen
      </a>
    </p>
    <hr style='margin:20px 0;border:none;border-top:1px solid #eee;'>
    <p style='font-size:12px;color:#999;text-align:center;'>Diese automatische Sicherheitsbenachrichtigung wurde von $brandName generiert.</p>
  </div>
</div>";

    require_once __DIR__ . '/../../mailer/SmtpClient.php';
    try {
        $smtpHost     = $settings['smtp_host']       ?? '';
        $smtpPort     = (int)($settings['smtp_port'] ?? 587);
        $smtpUser     = $settings['smtp_user']        ?? '';
        $smtpPass     = $settings['smtp_pass']        ?? '';
        $smtpFrom     = $settings['smtp_from']        ?? $adminEmail;
        $smtpFromName = $settings['smtp_from_name']   ?? $brandName;
        $smtpEnc      = $settings['smtp_encryption']  ?? 'tls';

        if (empty($smtpHost) || empty($smtpUser)) {
            return;
        }

        $smtp = new SmtpClient($smtpHost, $smtpPort, $smtpUser, $smtpPass, $smtpEnc);
        $smtp->send($smtpFrom, $smtpFromName, $adminEmail, $subject, $body);
    } catch (Exception $e) {
        error_log('Payment security alert SMTP error: ' . $e->getMessage());
    }
}

// ── Main filter function ──────────────────────────────────────────────────────

/**
 * Scans an admin message for unofficial crypto wallet addresses (or IBANs),
 * replaces them with the official platform address, and – when any replacement
 * occurred – persists a security alert and emails the superadmin.
 *
 * @param  PDO        $pdo       Active database connection
 * @param  string     $message   The raw message to filter
 * @param  string     $channel   Source channel ('ticket_reply'|'live_chat'|'direct_email'|'bulk_email')
 * @param  int        $adminId   ID of the admin sending the message
 * @param  int|null   $userId    Affected user ID (null for bulk)
 * @param  int|null   $entityId  ticket_id / chat session_id / null
 * @return string                The filtered message (unsafe addresses replaced)
 */
function filterPaymentAddresses(
    PDO $pdo,
    string $message,
    string $channel = 'ticket_reply',
    int $adminId = 0,
    ?int $userId = null,
    ?int $entityId = null
): string {
    // Load every active payment method that has a wallet_address or account_number
    $stmt = $pdo->query("
        SELECT method_code, method_name, wallet_address, account_number, iban, is_crypto
        FROM payment_methods
        WHERE is_active = 1
    ");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($methods)) {
        return $message;
    }

    $originalMessage    = $message;
    $interceptedAddrs   = [];          // Collects every unauthorized address found
    $cryptoPatterns     = _getAddressPatterns();

    // Build a quick lookup of all official wallet addresses (lower-cased for comparison)
    $officialWallets = [];
    foreach ($methods as $m) {
        if (!empty($m['wallet_address'])) {
            $officialWallets[] = strtolower(trim($m['wallet_address']));
        }
    }

    // ── Process each crypto pattern ───────────────────────────────────────────
    foreach ($cryptoPatterns as $type => $cfg) {
        if (!preg_match_all($cfg['pattern'], $message, $matches)) {
            continue;
        }

        $foundAddresses = array_unique($matches[1]);

        foreach ($foundAddresses as $found) {
            // Skip if this is already an official address
            if (in_array(strtolower($found), $officialWallets, true)) {
                continue;
            }

            // Find the replacement: the first active payment method whose
            // method_code or method_name contains one of the type keywords
            $replacement = null;
            foreach ($methods as $m) {
                if (empty($m['wallet_address'])) {
                    continue;
                }
                $code = strtolower($m['method_code']);
                $name = strtolower($m['method_name']);
                foreach ($cfg['keywords'] as $kw) {
                    if (strpos($code, $kw) !== false || strpos($name, $kw) !== false) {
                        $replacement = $m['wallet_address'];
                        break 2;
                    }
                }
            }

            if ($replacement !== null) {
                $interceptedAddrs[] = $found;
                $message = str_replace($found, $replacement, $message);
            }
        }
    }

    // ── IBAN pattern ──────────────────────────────────────────────────────────
    // Match IBANs: 2 letters + 2 digits + up to 30 alphanumeric chars,
    // optionally spaced in groups of 4.
    $ibanPattern = '/\b([A-Z]{2}[0-9]{2}(?:\s?[A-Z0-9]{4}){1,7}\s?[A-Z0-9]{0,3})\b/';
    if (preg_match_all($ibanPattern, $message, $ibanMatches)) {
        // Collect official IBANs from payment methods
        $officialIbans = [];
        foreach ($methods as $m) {
            if (!empty($m['iban'])) {
                $officialIbans[] = strtoupper(preg_replace('/\s+/', '', $m['iban']));
            }
            if (!empty($m['account_number'])) {
                $officialIbans[] = strtoupper(preg_replace('/\s+/', '', $m['account_number']));
            }
        }

        foreach (array_unique($ibanMatches[1]) as $foundIban) {
            $normalized = strtoupper(preg_replace('/\s+/', '', $foundIban));
            if (in_array($normalized, $officialIbans, true)) {
                continue;
            }

            // Replace with the first active bank payment method's account_number/iban
            foreach ($methods as $m) {
                if (!$m['is_crypto'] && !empty($m['account_number'])) {
                    $interceptedAddrs[] = $foundIban;
                    $message = str_replace($foundIban, $m['account_number'], $message);
                    break;
                }
            }
        }
    }

    // ── Log security alert when any replacement was made ─────────────────────
    if (!empty($interceptedAddrs) && $adminId > 0) {
        logPaymentSecurityAlert(
            $pdo,
            $channel,
            $adminId,
            $userId,
            $entityId,
            $originalMessage,
            $message,
            array_unique($interceptedAddrs)
        );
    }

    return $message;
}
