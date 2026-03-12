-- Transaction email templates for admin-triggered notifications.
-- Uses partial HTML (no DOCTYPE/html/head) so EmailHelper.sendEmail()
-- wraps content in the standard brand template automatically.
-- Uses ON DUPLICATE KEY UPDATE to fix any existing full-HTML templates
-- that would be incompatible with EmailHelper's automatic wrapping.

-- ---------------------------------------------------------------
-- deposit_received: sent when admin approves a deposit
-- Variables passed from approve_transaction.php:
--   amount (plain number, template adds €), payment_method,
--   transaction_id, reference, transaction_date, transaction_status
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'deposit_received',

    'Einzahlung bestätigt – Betrag: {amount} €',

    '<p>Sehr geehrte/r {first_name},</p>

<p>
  wir freuen uns, Ihnen mitteilen zu dürfen, dass Ihre Einzahlung erfolgreich
  verbucht wurde. Der Betrag wurde Ihrem Kontoguthaben gutgeschrieben.
</p>

<div class="highlight-box">
  <h3>💳 Transaktionsdetails</h3>
  <p><strong>Betrag:</strong> {amount} €</p>
  <p><strong>Zahlungsmethode:</strong> {payment_method}</p>
  <p><strong>Transaktions-ID:</strong> {transaction_id}</p>
  <p><strong>Referenz:</strong> {reference}</p>
  <p><strong>Datum:</strong> {transaction_date}</p>
  <p><strong>Status:</strong> {transaction_status}</p>
</div>

<p>
  Sie können Ihre vollständige Transaktionshistorie sowie Ihr aktuelles
  Guthaben jederzeit in Ihrem Kundenportal einsehen.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","amount","payment_method","transaction_id","reference","transaction_date","transaction_status","brand_name","dashboard_url","contact_email","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- withdrawal_completed: sent when admin approves a withdrawal
-- Variables passed from approve_transaction.php:
--   amount (plain number, template adds €), payment_method,
--   payment_details, transaction_id, reference,
--   transaction_date, transaction_status
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'withdrawal_completed',

    'Auszahlung abgeschlossen – Betrag: {amount} €',

    '<p>Sehr geehrte/r {first_name},</p>

<p>
  Ihre Auszahlungsanfrage wurde erfolgreich bearbeitet und der Betrag
  wurde an Ihre hinterlegte Zahlungsmethode überwiesen.
</p>

<div class="highlight-box">
  <h3>💸 Auszahlungsdetails</h3>
  <p><strong>Betrag:</strong> {amount} €</p>
  <p><strong>Zahlungsmethode:</strong> {payment_method}</p>
  <p><strong>Zahlungsdetails:</strong> {payment_details}</p>
  <p><strong>Transaktions-ID:</strong> {transaction_id}</p>
  <p><strong>Referenz:</strong> {reference}</p>
  <p><strong>Datum:</strong> {transaction_date}</p>
  <p><strong>Status:</strong> {transaction_status}</p>
</div>

<p>
  Bitte beachten Sie, dass es je nach Zahlungsmethode einige Werktage
  dauern kann, bis der Betrag auf Ihrem Konto erscheint.
</p>

<p>
  Bei Fragen stehen wir Ihnen unter
  <a href="mailto:{contact_email}">{contact_email}</a> zur Verfügung.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","amount","payment_method","payment_details","transaction_id","reference","transaction_date","transaction_status","brand_name","dashboard_url","contact_email","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- withdrawal_rejected: sent when admin rejects a withdrawal
-- Variables passed from reject_transaction.php:
--   amount (with €), payment_method, payment_details,
--   transaction_id, reference, transaction_date,
--   rejection_reason, reason (alias), rejected_at
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'withdrawal_rejected',

    'Ihre Auszahlung wurde abgelehnt – Referenz: {reference}',

    '<p>Sehr geehrte/r {first_name},</p>

<p>
  leider müssen wir Ihnen mitteilen, dass Ihre Auszahlungsanfrage nicht
  genehmigt werden konnte.
</p>

<div class="highlight-box" style="background:#fff5f5;border-left:5px solid #dc3545;padding:20px;border-radius:6px;margin:20px 0;">
  <h3 style="margin-top:0;color:#dc3545;">❌ Auszahlungsdetails</h3>
  <p><strong>Betrag:</strong> {amount}</p>
  <p><strong>Zahlungsmethode:</strong> {payment_method}</p>
  <p><strong>Transaktions-ID:</strong> {transaction_id}</p>
  <p><strong>Referenz:</strong> {reference}</p>
  <p><strong>Eingangsdatum:</strong> {transaction_date}</p>
  <p><strong>Abgelehnt am:</strong> {rejected_at}</p>
  <p><strong>Ablehnungsgrund:</strong> {rejection_reason}</p>
</div>

<p>
  Der zurückgehaltene Betrag wurde Ihrem Guthaben wieder gutgeschrieben.
  Falls Sie Fragen haben oder eine erneute Auszahlung beantragen möchten,
  wenden Sie sich bitte an unser Support-Team unter
  <a href="mailto:{contact_email}">{contact_email}</a>.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","amount","payment_method","payment_details","transaction_id","reference","transaction_date","rejected_at","rejection_reason","brand_name","dashboard_url","contact_email","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();
