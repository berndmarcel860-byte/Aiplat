-- Seed the deposit_rejected email template.
-- Sent automatically when an admin rejects a pending deposit.
-- Content is in German. Uses partial HTML (no DOCTYPE) so EmailHelper.sendEmail()
-- wraps it in the standard brand template automatically.
-- Safe to run multiple times: INSERT updates if template_key already exists.

INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'deposit_rejected',

    'Ihre Einzahlung wurde abgelehnt – Referenz: {reference}',

    '<p>Sehr geehrte/r {first_name},</p>

<p>leider müssen wir Ihnen mitteilen, dass Ihre Einzahlung nicht bestätigt werden konnte und abgelehnt wurde.</p>

<div class="highlight-box" style="background:#fff5f5;border-left:5px solid #dc3545;padding:20px;border-radius:6px;margin:20px 0;">
  <h3 style="margin-top:0;color:#dc3545;">❌ Einzahlungsdetails</h3>
  <p><strong>Betrag:</strong> {amount}</p>
  <p><strong>Transaktions-ID:</strong> {transaction_id}</p>
  <p><strong>Referenz:</strong> {reference}</p>
  <p><strong>Datum:</strong> {transaction_date}</p>
  <p><strong>Ablehnungsgrund:</strong> {reason}</p>
</div>

<p>
  Falls Sie Fragen haben oder eine erneute Einzahlung vornehmen möchten,
  wenden Sie sich bitte an unser Support-Team unter
  <a href="mailto:{contact_email}">{contact_email}</a>.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","amount","reference","transaction_id","transaction_date","reason","brand_name","dashboard_url","contact_email","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();
