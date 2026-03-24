-- Email templates for wallet verification approve and reject notifications.
-- Safe to run multiple times (INSERT IGNORE).
-- Content is in German.

INSERT IGNORE INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'wallet_verification_approved',
    'Ihre {cryptocurrency}-Wallet wurde verifiziert ✅',
    '<p>Sehr geehrte/r {first_name},</p>

<p>Großartige Neuigkeiten! Ihre <strong>{cryptocurrency}</strong>-Wallet ({network}) wurde erfolgreich verifiziert.</p>

<div class="highlight-box" style="background:#f0fff4;border-left:4px solid #28a745;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0 0 8px 0;"><strong>✅ Verifizierungsdetails</strong></p>
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="padding:4px 0;color:#555;width:40%;">Kryptowährung:</td>
      <td style="padding:4px 0;"><strong>{cryptocurrency}</strong></td>
    </tr>
    <tr>
      <td style="padding:4px 0;color:#555;">Netzwerk:</td>
      <td style="padding:4px 0;"><strong>{network}</strong></td>
    </tr>
    <tr>
      <td style="padding:4px 0;color:#555;">Wallet-Adresse:</td>
      <td style="padding:4px 0;font-family:monospace;word-break:break-all;">{wallet_address}</td>
    </tr>
    <tr>
      <td style="padding:4px 0;color:#555;">Transaktions-ID:</td>
      <td style="padding:4px 0;font-family:monospace;word-break:break-all;">{verification_txid}</td>
    </tr>
  </table>
</div>

<p>Ihre Wallet ist nun aktiv und kann für Transaktionen auf unserer Plattform verwendet werden.</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Dashboard</a>
</p>

<p>Bei Fragen wenden Sie sich bitte an <a href="mailto:{contact_email}">{contact_email}</a>.</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',
    '["first_name","last_name","email","cryptocurrency","network","wallet_address","verification_txid","brand_name","dashboard_url","contact_email","current_year"]',
    NOW(),
    NOW()
);

INSERT IGNORE INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'wallet_verification_rejected',
    'Handlungsbedarf: Ihre {cryptocurrency}-Wallet-Verifizierung wurde nicht genehmigt',
    '<p>Sehr geehrte/r {first_name},</p>

<p>Leider konnten wir Ihre <strong>{cryptocurrency}</strong>-Wallet ({network}) derzeit nicht verifizieren.</p>

<div class="highlight-box" style="background:#fff5f5;border-left:4px solid #dc3545;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0 0 8px 0;"><strong>❌ Verifizierungsdetails</strong></p>
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="padding:4px 0;color:#555;width:40%;">Kryptowährung:</td>
      <td style="padding:4px 0;"><strong>{cryptocurrency}</strong></td>
    </tr>
    <tr>
      <td style="padding:4px 0;color:#555;">Netzwerk:</td>
      <td style="padding:4px 0;"><strong>{network}</strong></td>
    </tr>
    <tr>
      <td style="padding:4px 0;color:#555;">Wallet-Adresse:</td>
      <td style="padding:4px 0;font-family:monospace;word-break:break-all;">{wallet_address}</td>
    </tr>
    <tr>
      <td style="padding:4px 0;color:#555;">Ablehnungsgrund:</td>
      <td style="padding:4px 0;"><strong>{rejection_reason}</strong></td>
    </tr>
  </table>
</div>

<p>Um Ihre Wallet-Verifizierung abzuschließen, gehen Sie bitte wie folgt vor:</p>
<ol>
  <li>Melden Sie sich in Ihrem Dashboard an</li>
  <li>Navigieren Sie zu <strong>Wallet &amp; Zahlungen</strong></li>
  <li>Reichen Sie Ihre Wallet erneut mit den korrekten Transaktionsdaten ein</li>
</ol>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Dashboard</a>
</p>

<p>Wenn Sie Hilfe benötigen, kontaktieren Sie uns bitte unter <a href="mailto:{contact_email}">{contact_email}</a>.</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',
    '["first_name","last_name","email","cryptocurrency","network","wallet_address","rejection_reason","brand_name","dashboard_url","contact_email","current_year"]',
    NOW(),
    NOW()
);
