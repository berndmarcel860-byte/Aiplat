-- Fix email_templates: remove full-HTML wrappers and correct variables JSON.
--
-- Problem 1 – Double-wrapping:
--   EmailHelper::sendEmail() ALWAYS calls wrapInTemplate() unconditionally.
--   Any template whose `content` column contains a complete HTML document
--   (with <!DOCTYPE … <html …>) will be injected *inside* the wrapper,
--   producing broken double-wrapped email HTML.
--   Fix: replace those templates with partial HTML content only
--        (using the <!-- CUSTOM_HEADER --> marker where a branded header is needed).
--
-- Problem 2 – Malformed variables JSON:
--   Several templates have an invalid `variables` value that mixes a JSON array
--   with inline key:value object notation, e.g.:
--     ["first_name", "last_name", "brand_name": "", "contact_email": ""]
--   That string is not valid JSON.  Replace every affected row with a proper
--   JSON array that lists only the variable names actually used in the template.
--
-- All statements use ON DUPLICATE KEY UPDATE so the file is safe to run
-- multiple times without data loss.
--

-- ---------------------------------------------------------------
-- 1. documents_required
--    Was: full <!DOCTYPE html> document with {#each required_documents}
--         Handlebars syntax (unsupported by PHP str_replace).
--    Fix: partial HTML; use {required_documents} which update_case.php
--         already pre-renders as an <ul> HTML string.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'documents_required',

    'Dokumente erforderlich für Fallnummer: {case_number}',

    '<!-- CUSTOM_HEADER -->
<div class="header">
  <h1>📋 Dokumente erforderlich – {case_number}</h1>
  <p>Bitte laden Sie die angeforderten Unterlagen hoch</p>
</div>

<div class="content">
  <p>Sehr geehrte/r {first_name} {last_name},</p>

  <p>
    Um Ihren Fall <strong>{case_number}</strong> weiter bearbeiten zu können,
    benötigen wir die folgenden Unterlagen von Ihnen:
  </p>

  <div class="highlight-box">
    <h3>📎 Erforderliche Dokumente</h3>
    {required_documents}
    <p><strong>Hinweise:</strong> {additional_notes}</p>
  </div>

  <p>
    Bitte laden Sie die Unterlagen so bald wie möglich hoch, um Verzögerungen
    bei der Bearbeitung Ihres Falls zu vermeiden.
  </p>

  <p>
    <a href="{upload_link}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Dokumente hochladen</a>
  </p>

  <p>Bei Fragen stehen wir Ihnen unter <a href="mailto:{contact_email}">{contact_email}</a> zur Verfügung.</p>

  <p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>
</div>',

    '["first_name","last_name","email","case_number","required_documents","additional_notes","upload_link","brand_name","contact_email","dashboard_url","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- 2. welcome_email
--    Was: full <!DOCTYPE html> document with wrong variable names
--         (sbrand, surl, semail) and malformed variables JSON.
--    Fix: partial HTML with correct variable names from getAllVariables()
--         and the custom vars passed by add_user.php:
--         pass = plain-text password, login_link, change_password_link.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'welcome_email',

    'Herzlich willkommen bei {brand_name}!',

    '<!-- CUSTOM_HEADER -->
<div class="header">
  <h1>Willkommen bei {brand_name}</h1>
  <p>Ihr Zugang wurde erfolgreich eingerichtet</p>
</div>

<div class="content">
  <p>Sehr geehrte/r {first_name} {last_name},</p>

  <p>
    Herzlich willkommen bei <strong>{brand_name}</strong>!
    Wir freuen uns, dass Sie sich für uns entschieden haben.
  </p>

  <div class="highlight-box">
    <h3>🔐 Ihre Zugangsdaten</h3>
    <p><strong>E-Mail:</strong> {email}</p>
    <p><strong>Passwort:</strong> {pass}</p>
  </div>

  <div style="background:#fff3cd;border:1px solid #ffc107;padding:15px;border-radius:6px;margin:20px 0;">
    <p style="margin:0;">
      <strong>⚠️ Sicherheitshinweis:</strong> Bitte ändern Sie Ihr Passwort
      nach der ersten Anmeldung unter <em>„Profil bearbeiten → Passwort ändern"</em>.
    </p>
  </div>

  <p>
    <a href="{login_link}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Login</a>
  </p>

  <p>
    Bei Fragen stehen wir Ihnen unter
    <a href="mailto:{contact_email}">{contact_email}</a> gerne zur Verfügung.
  </p>

  <p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>
</div>',

    '["first_name","last_name","email","pass","login_link","change_password_link","brand_name","contact_email","site_url","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- 3. user_registration
--    Was: full <!DOCTYPE html> document.
--    Fix: partial HTML.  Variables: first_name, last_name, email,
--         verification_link (passed by the registration flow).
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'user_registration',

    'Willkommen bei {brand_name} – Bitte bestätigen Sie Ihre E-Mail',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  vielen Dank für Ihre Registrierung bei <strong>{brand_name}</strong>.
  Ihr Konto wurde erfolgreich erstellt.
</p>

<div class="highlight-box">
  <h3>✉️ E-Mail-Bestätigung erforderlich</h3>
  <p>
    Bitte bestätigen Sie Ihre E-Mail-Adresse, um Ihr Konto zu aktivieren und
    alle Funktionen der Plattform nutzen zu können.
  </p>
  <p><strong>Ihre E-Mail-Adresse:</strong> {email}</p>
</div>

<p style="text-align:center;margin:30px 0;">
  <a href="{verification_link}" style="display:inline-block;background:#2950a8;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none;font-weight:bold;font-size:16px;">E-Mail bestätigen</a>
</p>

<p>
  Falls Sie sich nicht registriert haben, ignorieren Sie bitte diese E-Mail.
  Der Link ist 24 Stunden gültig.
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","verification_link","brand_name","contact_email","site_url","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- 4. case_status_updated
--    Content is already correct partial HTML with <!-- CUSTOM_HEADER -->.
--    Fix: replace malformed variables JSON with a valid JSON array.
--    Variables: from getAllVariables() + customVars passed by update_case.php
--    (case_number, old_status, new_status, status_notes, update_date).
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'case_status_updated',

    'Fallstatus aktualisiert - Fallnummer: {case_number}',

    '<!-- CUSTOM_HEADER -->
<div class="header">
  <h1>Fallstatus aktualisiert – {case_number}</h1>
  <p>Aktuelle Informationen zu Ihrem Fall</p>
</div>

<div class="content">
  <p>Sehr geehrte/r {first_name} {last_name},</p>

  <p>
    Der Status Ihres Falls wurde erfolgreich aktualisiert.
    Nachfolgend finden Sie die neuesten Informationen:
  </p>

  <div class="highlight-box">
    <h3>📄 Aktualisierte Falldetails</h3>
    <p><strong>Fallnummer:</strong> {case_number}</p>
    <p><strong>Vorheriger Status:</strong> {old_status}</p>
    <p><strong>Neuer Status:</strong> {new_status}</p>
    <p><strong>Grund / Notizen:</strong> {status_notes}</p>
    <p><strong>Datum der Änderung:</strong> {update_date}</p>
  </div>

  <p>
    Sie können den aktuellen Stand Ihres Falls jederzeit in Ihrem
    <strong>Kundenportal</strong> einsehen und relevante Unterlagen hochladen.
  </p>

  <p>
    <a href="{site_url}/login.php" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
  </p>

  <p>Bei Fragen kontaktieren Sie uns unter <a href="mailto:{contact_email}">{contact_email}</a>.</p>

  <p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>
</div>',

    '["first_name","last_name","email","case_number","old_status","new_status","status_notes","update_date","brand_name","site_url","contact_email","company_address","fca_reference_number","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- 5. kyc_approved
--    Content is already correct partial HTML with <!-- CUSTOM_HEADER -->.
--    Fix:
--    a) Replace {kyc_date} with {current_date} (auto-populated by
--       getAllVariables(); approve_kyc.php does not pass kyc_date).
--    b) Replace malformed variables JSON with a valid JSON array.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'kyc_approved',

    'Ihre KYC-Verifizierung wurde genehmigt',

    '<!-- CUSTOM_HEADER -->
<div class="header">
  <h1>KYC-Verifizierung erfolgreich ✅</h1>
  <p>Ihr Konto ist jetzt vollständig verifiziert</p>
</div>

<div class="content">
  <p>Sehr geehrte/r {first_name} {last_name},</p>

  <p>
    Wir freuen uns, Ihnen mitteilen zu können, dass Ihre
    <strong>KYC-Verifizierung (Know Your Customer)</strong> erfolgreich abgeschlossen wurde.
  </p>

  <div class="highlight-box">
    <h3>✅ Verifizierungsdetails</h3>
    <p><strong>Verifiziertes Konto:</strong> {email}</p>
    <p><strong>Datum der Verifizierung:</strong> {current_date}</p>
    <p><strong>Status:</strong> Erfolgreich abgeschlossen</p>
  </div>

  <p>
    Ihr Konto ist nun vollständig freigeschaltet und Sie können alle Funktionen
    unserer Plattform uneingeschränkt nutzen – inklusive Auszahlungen,
    Fallmanagement und Transaktionsverfolgung in Echtzeit.
  </p>

  <p>
    <a href="{site_url}/login.php" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
  </p>

  <p>Bei Fragen stehen wir Ihnen unter <a href="mailto:{contact_email}">{contact_email}</a> zur Verfügung.</p>

  <p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>
</div>',

    '["first_name","last_name","email","current_date","brand_name","site_url","contact_email","company_address","fca_reference_number","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- 6. kyc_rejected
--    Content is already correct partial HTML with <!-- CUSTOM_HEADER -->.
--    Fix: replace malformed variables JSON.
--    Variables: from getAllVariables() + reject_kyc.php custom vars
--    (rejection_reason, resubmit_link).
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'kyc_rejected',

    'Ihre KYC-Verifizierung erfordert weitere Schritte',

    '<!-- CUSTOM_HEADER -->
<div class="header">
  <h1>KYC-Verifizierung nicht erfolgreich ❗</h1>
  <p>Überprüfung Ihrer Unterlagen fehlgeschlagen</p>
</div>

<div class="content">
  <p>Sehr geehrte/r {first_name} {last_name},</p>

  <p>
    Leider konnten wir Ihre <strong>KYC-Verifizierung (Know Your Customer)</strong>
    nicht erfolgreich abschließen. Bitte beachten Sie die unten aufgeführten Hinweise.
  </p>

  <div class="highlight-box" style="background:#fff5f5;border-left:5px solid #dc3545;">
    <h3>❗ Grund der Ablehnung</h3>
    <p>{rejection_reason}</p>
  </div>

  <p>
    Um den Prozess fortzusetzen, reichen Sie bitte die fehlenden oder korrigierten
    Dokumente über unser sicheres <strong>Kundenportal</strong> erneut ein.
  </p>

  <p style="text-align:center;">
    <a href="{resubmit_link}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Dokumente erneut einreichen</a>
  </p>

  <p>
    Nach erfolgreicher Überprüfung werden Sie automatisch per E-Mail informiert.
    Sollten Sie Fragen haben, steht Ihnen unser Support-Team unter
    <a href="mailto:{contact_email}">{contact_email}</a> gerne zur Verfügung.
  </p>

  <p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>
</div>',

    '["first_name","last_name","email","rejection_reason","resubmit_link","brand_name","site_url","contact_email","company_address","fca_reference_number","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- 7. recovery_amount_updated
--    Content is already correct partial HTML with <!-- CUSTOM_HEADER -->.
--    Fix: replace malformed variables JSON.
--    Variables: from getAllVariables() + update_recovery.php custom vars
--    (recovered_amount, total_recovered, reported_amount, recovery_id,
--     recovery_date, recovery_notes, case_number).
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'recovery_amount_updated',

    'Erstattungsbetrag aktualisiert - Fallnummer: {case_number}',

    '<!-- CUSTOM_HEADER -->
<div class="header">
  <h1>Erstattungsbetrag aktualisiert 💰</h1>
  <p>Neue Rückerstattungsinformationen für Fall {case_number}</p>
</div>

<div class="content">
  <p>Sehr geehrte/r {first_name} {last_name},</p>

  <p>
    Wir freuen uns, Ihnen mitteilen zu können, dass für Ihren Fall
    <strong>{case_number}</strong> ein neuer Rückerstattungsbetrag verbucht wurde.
  </p>

  <div class="highlight-box">
    <h3>💰 Erstattungsdetails</h3>
    <p><strong>Fallnummer:</strong> {case_number}</p>
    <p><strong>Gemeldeter Verlustbetrag:</strong> {reported_amount}</p>
    <p><strong>Neuer Rückerstattungsbetrag:</strong> {recovered_amount}</p>
    <p><strong>Gesamtrückerstattung bisher:</strong> {total_recovered}</p>
    <p><strong>Datum:</strong> {recovery_date}</p>
    <p><strong>Notizen:</strong> {recovery_notes}</p>
  </div>

  <p>
    Sie können die vollständigen Details Ihres Falls jederzeit in Ihrem
    Kundenportal einsehen.
  </p>

  <p>
    <a href="{site_url}/login.php" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
  </p>

  <p>Bei Fragen kontaktieren Sie uns unter <a href="mailto:{contact_email}">{contact_email}</a>.</p>

  <p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>
</div>',

    '["first_name","last_name","email","case_number","reported_amount","recovered_amount","total_recovered","recovery_date","recovery_notes","brand_name","site_url","contact_email","company_address","fca_reference_number","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- 8. withdrawal_requested
--    Content is already correct partial HTML with <!-- CUSTOM_HEADER -->.
--    Fix: replace malformed variables JSON.
--    Variables: standard transaction vars available via getAllVariables()
--    plus any custom vars passed with the withdrawal request.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'withdrawal_requested',

    'Auszahlungsanfrage erhalten - Betrag: {amount} €',

    '<!-- CUSTOM_HEADER -->
<div class="header">
  <h1>Auszahlungsanfrage erhalten 💸</h1>
  <p>Ihre Anfrage wird bearbeitet</p>
</div>

<div class="content">
  <p>Sehr geehrte/r {first_name} {last_name},</p>

  <p>
    Wir haben Ihre Auszahlungsanfrage erhalten und werden diese
    schnellstmöglich bearbeiten.
  </p>

  <div class="highlight-box">
    <h3>📋 Anfragedetails</h3>
    <p><strong>Betrag:</strong> {amount} €</p>
    <p><strong>Zahlungsmethode:</strong> {payment_method}</p>
    <p><strong>Zahlungsdetails:</strong> {payment_details}</p>
    <p><strong>Transaktions-ID:</strong> {transaction_id}</p>
    <p><strong>Datum:</strong> {transaction_date}</p>
    <p><strong>Status:</strong> {transaction_status}</p>
  </div>

  <p>
    Sie erhalten eine weitere E-Mail, sobald Ihre Anfrage bearbeitet wurde.
    Den aktuellen Status können Sie auch jederzeit in Ihrem Kundenportal einsehen.
  </p>

  <p>
    <a href="{site_url}/login.php" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
  </p>

  <p>Bei Fragen kontaktieren Sie uns unter <a href="mailto:{contact_email}">{contact_email}</a>.</p>

  <p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>
</div>',

    '["first_name","last_name","email","amount","payment_method","payment_details","transaction_id","transaction_date","transaction_status","brand_name","site_url","contact_email","company_address","fca_reference_number","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- 9. password_reset
--    Content is already correct partial HTML with <!-- CUSTOM_HEADER -->.
--    Fix: replace malformed variables JSON.
--    Variables: first_name, last_name, reset_link, site_url + standard.
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'password_reset',

    'Passwort zurücksetzen – {brand_name}',

    '<!-- CUSTOM_HEADER -->
<div class="header">
  <h1>Passwort zurücksetzen 🔑</h1>
  <p>Anfrage zum Zurücksetzen Ihres Passworts</p>
</div>

<div class="content">
  <p>Sehr geehrte/r {first_name} {last_name},</p>

  <p>
    Wir haben eine Anfrage zum Zurücksetzen des Passworts für Ihr Konto erhalten.
    Klicken Sie auf den folgenden Button, um Ihr Passwort zurückzusetzen:
  </p>

  <p style="text-align:center;margin:30px 0;">
    <a href="{reset_link}" style="display:inline-block;background:#2950a8;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none;font-weight:bold;font-size:16px;">Passwort zurücksetzen</a>
  </p>

  <div class="highlight-box" style="background:#fff3cd;border-left:5px solid #ffc107;">
    <p style="margin:0;">
      <strong>⚠️ Sicherheitshinweis:</strong> Dieser Link ist nur 24 Stunden gültig.
      Falls Sie kein Passwort-Reset angefordert haben, ignorieren Sie diese E-Mail bitte –
      Ihr Passwort bleibt dann unverändert.
    </p>
  </div>

  <p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>
</div>',

    '["first_name","last_name","email","reset_link","brand_name","site_url","contact_email","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();
