-- Email templates for wallet verification approve and reject notifications.
-- Safe to run multiple times (INSERT IGNORE).

INSERT IGNORE INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'wallet_verification_approved',
    'Your {cryptocurrency} Wallet Has Been Verified ✅',
    '<p>Dear {first_name},</p>

<p>Great news! Your <strong>{cryptocurrency}</strong> ({network}) wallet has been successfully verified.</p>

<div class="highlight-box" style="background:#f0fff4;border-left:4px solid #28a745;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0 0 8px 0;"><strong>✅ Verification Details</strong></p>
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="padding:4px 0;color:#555;width:40%;">Cryptocurrency:</td>
      <td style="padding:4px 0;"><strong>{cryptocurrency}</strong></td>
    </tr>
    <tr>
      <td style="padding:4px 0;color:#555;">Network:</td>
      <td style="padding:4px 0;"><strong>{network}</strong></td>
    </tr>
    <tr>
      <td style="padding:4px 0;color:#555;">Wallet Address:</td>
      <td style="padding:4px 0;font-family:monospace;word-break:break-all;">{wallet_address}</td>
    </tr>
    <tr>
      <td style="padding:4px 0;color:#555;">Transaction ID:</td>
      <td style="padding:4px 0;font-family:monospace;word-break:break-all;">{verification_txid}</td>
    </tr>
  </table>
</div>

<p>Your wallet is now active and you can use it for transactions on our platform.</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Go to Dashboard</a>
</p>

<p>If you have any questions, please <a href="mailto:{contact_email}">{contact_email}</a>.</p>

<p>Best regards,<br>The {brand_name} Team</p>',
    '["first_name","last_name","email","cryptocurrency","network","wallet_address","verification_txid","brand_name","dashboard_url","contact_email","current_year"]',
    NOW(),
    NOW()
);

INSERT IGNORE INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'wallet_verification_rejected',
    'Action Required: Your {cryptocurrency} Wallet Verification Was Not Approved',
    '<p>Dear {first_name},</p>

<p>We were unable to verify your <strong>{cryptocurrency}</strong> ({network}) wallet at this time.</p>

<div class="highlight-box" style="background:#fff5f5;border-left:4px solid #dc3545;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0 0 8px 0;"><strong>❌ Verification Details</strong></p>
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="padding:4px 0;color:#555;width:40%;">Cryptocurrency:</td>
      <td style="padding:4px 0;"><strong>{cryptocurrency}</strong></td>
    </tr>
    <tr>
      <td style="padding:4px 0;color:#555;">Network:</td>
      <td style="padding:4px 0;"><strong>{network}</strong></td>
    </tr>
    <tr>
      <td style="padding:4px 0;color:#555;">Wallet Address:</td>
      <td style="padding:4px 0;font-family:monospace;word-break:break-all;">{wallet_address}</td>
    </tr>
    <tr>
      <td style="padding:4px 0;color:#555;">Reason:</td>
      <td style="padding:4px 0;"><strong>{rejection_reason}</strong></td>
    </tr>
  </table>
</div>

<p>To complete your wallet verification, please:</p>
<ol>
  <li>Log in to your dashboard</li>
  <li>Go to <strong>Wallet &amp; Payments</strong></li>
  <li>Re-submit your wallet with the correct transaction details</li>
</ol>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Go to Dashboard</a>
</p>

<p>If you need assistance, please contact us at <a href="mailto:{contact_email}">{contact_email}</a>.</p>

<p>Best regards,<br>The {brand_name} Team</p>',
    '["first_name","last_name","email","cryptocurrency","network","wallet_address","rejection_reason","brand_name","dashboard_url","contact_email","current_year"]',
    NOW(),
    NOW()
);
