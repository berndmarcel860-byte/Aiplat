-- email_templates_remaining_fix.sql
--
-- Fixes the remaining email_templates rows that were not addressed by
-- fix_email_templates.sql or transaction_email_templates.sql.
--
-- Problems fixed here:
--  1. Full-HTML templates (<!DOCTYPE ... <html ...>) → converted to partial HTML
--     so that EmailHelper::sendEmail() and AdminEmailHelper::sendTemplateEmail()
--     can wrap them in the standard brand template without producing broken
--     double-wrapped HTML documents.
--  2. Malformed `variables` JSON (object-notation mixed into a JSON array,
--     or wrong variable names like {sbrand}/{surl} that don't match the
--     variable names provided by getAllVariables()).
--
-- All statements use ON DUPLICATE KEY UPDATE so this file is safe to run
-- multiple times without data loss.
-- ---------------------------------------------------------------


-- ---------------------------------------------------------------
-- welcome_email_text (id=16)
-- Was: full <!DOCTYPE html> with wrong variable names
--      ({sbrand}, {surl}, {sphone}, {semail}, {name}, {pass})
-- Fix: partial HTML with correct getAllVariables() variable names.
--      Custom vars that callers must pass: pass, login_link.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'welcome_email_text',

    'Herzlich willkommen bei {brand_name}!',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  Herzlich willkommen bei <strong>{brand_name}</strong>! Ihr Konto wurde
  erfolgreich eingerichtet.
</p>

<div class="highlight-box">
  <h3>🔑 Ihre Zugangsdaten</h3>
  <p><strong>E-Mail:</strong> {email}</p>
  <p><strong>Passwort:</strong> {pass}</p>
</div>

<p>
  Bitte loggen Sie sich ein und ändern Sie Ihr Passwort nach dem ersten
  Login aus Sicherheitsgründen.
</p>

<p>
  <a href="{login_link}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Jetzt anmelden</a>
</p>

<p>
  Bei Fragen wenden Sie sich bitte an uns unter
  <a href="mailto:{contact_email}">{contact_email}</a>.
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","pass","login_link","brand_name","contact_email","site_url","dashboard_url","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- deposit_confirmation (id=17)
-- Was: malformed variables JSON (mixing array entries with object pairs).
-- Fix: correct JSON array of variable names actually used in the template.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'deposit_confirmation',

    'Einzahlungsbestätigung – Referenz: {reference}',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  Wir bestätigen den Eingang Ihrer Einzahlung.
</p>

<div class="highlight-box">
  <h3>💳 Einzahlungsdetails</h3>
  <p><strong>Betrag:</strong> {amount} €</p>
  <p><strong>Referenz:</strong> {reference}</p>
  <p><strong>Zahlungsmethode:</strong> {payment_method}</p>
  <p><strong>Datum:</strong> {current_date}</p>
</div>

<p>
  Ihr Guthaben wird nach Prüfung schnellstmöglich gutgeschrieben.
</p>

<p>
  Bei Fragen wenden Sie sich bitte an uns unter
  <a href="mailto:{contact_email}">{contact_email}</a>.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","amount","reference","payment_method","brand_name","contact_email","dashboard_url","current_date","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- kyc_pending (id=18)
-- Was: object-notation JSON instead of a JSON array.
-- Fix: partial HTML with correct JSON array.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'kyc_pending',

    'KYC-Verifizierung ausstehend – {brand_name}',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  vielen Dank für die Einreichung Ihrer KYC-Dokumente. Wir haben Ihre
  Unterlagen erhalten und werden diese in Kürze prüfen.
</p>

<div class="highlight-box">
  <h3>📋 Status Ihrer Verifizierung</h3>
  <p><strong>Status:</strong> Ausstehend</p>
  <p><strong>Eingereicht am:</strong> {current_date}</p>
</div>

<p>
  Sie erhalten eine weitere Benachrichtigung, sobald die Prüfung
  abgeschlossen ist. Dieser Vorgang dauert in der Regel 1–3 Werktage.
</p>

<p>
  Bei Fragen wenden Sie sich bitte an uns unter
  <a href="mailto:{contact_email}">{contact_email}</a>.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","brand_name","contact_email","dashboard_url","current_date","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- payout_confirmation_document_send (id=20)
-- Was: malformed variables JSON (mixing array with object notation).
-- Fix: correct JSON array matching the strtr() vars in send_payout_confirmation.php:
--      {full_name}, {invoice_no}, {invoice_date}, {lost_amount}, {service_fee}, {brand_name}
-- NOTE: content is NOT changed – send_payout_confirmation.php manages its own
--       HTML wrapping.  Only the variables column is corrected here.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
SELECT
    'payout_confirmation_document_send',
    subject,
    content,
    '["full_name","invoice_no","invoice_date","lost_amount","service_fee","brand_name"]',
    created_at,
    NOW()
FROM email_templates
WHERE template_key = 'payout_confirmation_document_send'
ON DUPLICATE KEY UPDATE
    variables  = '["full_name","invoice_no","invoice_date","lost_amount","service_fee","brand_name"]',
    updated_at = NOW();


-- ---------------------------------------------------------------
-- withdrawal_pending (id=33)
-- Was: full <!DOCTYPE html> document.
-- Fix: partial HTML so the template wrapper is applied correctly.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'withdrawal_pending',

    'Ihre Auszahlungsanfrage wurde erhalten – {reference}',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  Wir bestätigen den Eingang Ihrer Auszahlungsanfrage. Diese wird aktuell
  von unserem Team geprüft.
</p>

<div class="highlight-box">
  <h3>💸 Auszahlungsdetails</h3>
  <p><strong>Betrag:</strong> {amount} €</p>
  <p><strong>Referenz:</strong> {reference}</p>
  <p><strong>Zahlungsmethode:</strong> {payment_method}</p>
  <p><strong>Eingereicht am:</strong> {current_date}</p>
  <p><strong>Status:</strong> Ausstehend</p>
</div>

<p>
  Sie werden per E-Mail benachrichtigt, sobald Ihre Auszahlung
  genehmigt oder abgelehnt wurde.
</p>

<p>
  Bei Fragen wenden Sie sich bitte an uns unter
  <a href="mailto:{contact_email}">{contact_email}</a>.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","amount","reference","payment_method","brand_name","contact_email","dashboard_url","current_date","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- onboarding_complete (id=30)
-- Was: full <!DOCTYPE html> document.
-- Fix: partial HTML.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'onboarding_complete',

    'Willkommen bei {brand_name} – Registrierung abgeschlossen',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  Herzlichen Glückwunsch! Ihre Registrierung bei <strong>{brand_name}</strong>
  wurde erfolgreich abgeschlossen.
</p>

<div class="highlight-box">
  <h3>✅ Nächste Schritte</h3>
  <p>1. Melden Sie sich in Ihrem Kundenportal an.</p>
  <p>2. Vervollständigen Sie Ihre KYC-Verifizierung.</p>
  <p>3. Fügen Sie eine Zahlungsmethode hinzu.</p>
</div>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
</p>

<p>
  Bei Fragen stehen wir Ihnen unter
  <a href="mailto:{contact_email}">{contact_email}</a> zur Verfügung.
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","brand_name","contact_email","dashboard_url","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- onboarding_completed (id=29)
-- Was: full <!DOCTYPE html> document.
-- Fix: partial HTML (same content as onboarding_complete – these are duplicates).
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'onboarding_completed',

    'Willkommen bei {brand_name} – Registrierung abgeschlossen',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  Herzlichen Glückwunsch! Ihre Registrierung bei <strong>{brand_name}</strong>
  wurde erfolgreich abgeschlossen.
</p>

<div class="highlight-box">
  <h3>✅ Nächste Schritte</h3>
  <p>1. Melden Sie sich in Ihrem Kundenportal an.</p>
  <p>2. Vervollständigen Sie Ihre KYC-Verifizierung.</p>
  <p>3. Fügen Sie eine Zahlungsmethode hinzu.</p>
</div>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
</p>

<p>
  Bei Fragen stehen wir Ihnen unter
  <a href="mailto:{contact_email}">{contact_email}</a> zur Verfügung.
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","brand_name","contact_email","dashboard_url","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- email_verification (id=31)
-- Was: full <!DOCTYPE html> document.
-- Fix: partial HTML with correct variables.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'email_verification',

    'Bitte bestätigen Sie Ihre E-Mail-Adresse – {brand_name}',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  Bitte bestätigen Sie Ihre E-Mail-Adresse, um Ihr Konto zu aktivieren.
</p>

<p style="text-align:center;margin:30px 0;">
  <a href="{verification_link}" style="display:inline-block;background:#2950a8;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none;font-weight:bold;font-size:16px;">E-Mail bestätigen</a>
</p>

<p>
  Falls Sie sich nicht registriert haben, ignorieren Sie bitte diese E-Mail.
  Der Link ist 24 Stunden gültig.
</p>

<p>
  Bei Fragen wenden Sie sich bitte an uns unter
  <a href="mailto:{contact_email}">{contact_email}</a>.
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","verification_link","brand_name","contact_email","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- support_ticket_created (id=12)
-- Was: full <!DOCTYPE html> document.
-- Fix: partial HTML.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'support_ticket_created',

    'Ihr Support-Ticket wurde erstellt – {ticket_number}',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  Ihr Support-Ticket wurde erfolgreich erstellt und wird von unserem Team
  so schnell wie möglich bearbeitet.
</p>

<div class="highlight-box">
  <h3>🎫 Ticket-Details</h3>
  <p><strong>Ticket-Nummer:</strong> {ticket_number}</p>
  <p><strong>Betreff:</strong> {ticket_subject}</p>
  <p><strong>Kategorie:</strong> {ticket_category}</p>
  <p><strong>Priorität:</strong> {ticket_priority}</p>
  <p><strong>Status:</strong> {ticket_status}</p>
</div>

<p>
  Sie werden per E-Mail benachrichtigt, sobald eine Antwort vorliegt.
</p>

<p>
  Bei dringenden Fragen wenden Sie sich bitte an uns unter
  <a href="mailto:{contact_email}">{contact_email}</a>.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Ticket ansehen</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","ticket_number","ticket_subject","ticket_category","ticket_priority","ticket_status","brand_name","contact_email","dashboard_url","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- support_ticket_updated (id=13)
-- Was: full <!DOCTYPE html> document.
-- Fix: partial HTML.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'support_ticket_updated',

    'Ihr Support-Ticket wurde aktualisiert – {ticket_number}',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  Es gibt eine Aktualisierung zu Ihrem Support-Ticket.
</p>

<div class="highlight-box">
  <h3>🔄 Ticket-Aktualisierung</h3>
  <p><strong>Ticket-Nummer:</strong> {ticket_number}</p>
  <p><strong>Neuer Status:</strong> {ticket_status}</p>
  <p><strong>Aktualisiert am:</strong> {update_date}</p>
  <p><strong>Antwort:</strong> {ticket_response}</p>
</div>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Ticket ansehen</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","ticket_number","ticket_status","update_date","ticket_response","brand_name","contact_email","dashboard_url","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- admin_created_user (id=14)
-- Was: full <!DOCTYPE html> document.
-- Fix: partial HTML with correct variables.
--      Custom vars that callers must pass: admin_name, login_link.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'admin_created_user',

    'Ihr Konto bei {brand_name} wurde erstellt',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  Ihr Konto bei <strong>{brand_name}</strong> wurde von unserem Administrator
  eingerichtet.
</p>

<div class="highlight-box">
  <h3>🔑 Ihre Zugangsdaten</h3>
  <p><strong>E-Mail:</strong> {email}</p>
  <p><strong>Eingerichtet von:</strong> {admin_name}</p>
</div>

<p>
  Bitte loggen Sie sich ein und ändern Sie Ihr Passwort beim ersten Login.
</p>

<p>
  <a href="{login_link}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Jetzt anmelden</a>
</p>

<p>
  Bei Fragen wenden Sie sich bitte an uns unter
  <a href="mailto:{contact_email}">{contact_email}</a>.
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","admin_name","login_link","brand_name","contact_email","dashboard_url","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();
