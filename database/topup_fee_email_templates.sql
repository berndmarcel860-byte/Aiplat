-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: Top-up Balance & Withdrawal Fee Email Templates
-- Run after transaction_email_templates.sql
-- Uses ON DUPLICATE KEY UPDATE so it is safe to re-run.
-- ─────────────────────────────────────────────────────────────────────────────

-- ---------------------------------------------------------------
-- topup_balance_credited: sent when admin adds top-up balance
-- Variables: amount, new_balance, source_label (reason/source)
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'topup_balance_credited',

    'Ihr Aufladeguthaben wurde aufgeladen – {amount} €',

    '<p>Sehr geehrte/r {first_name},</p>

<p>
  wir freuen uns, Ihnen mitteilen zu können, dass Ihr Aufladeguthaben erfolgreich
  aufgeladen wurde.
</p>

<div class="highlight-box">
  <h3>💳 Guthabennachricht</h3>
  <p><strong>Aufgeladener Betrag:</strong> {amount} €</p>
  <p><strong>Neuer Kontostand:</strong> {new_balance} €</p>
  <p><strong>Quelle:</strong> {source_label}</p>
  <p><strong>Datum:</strong> {transaction_date}</p>
</div>

<p>
  Das Aufladeguthaben wird für KI-Analyse-Gebühren und Auszahlungsverwaltungsgebühren
  verwendet. Bitte stellen Sie sicher, dass immer ausreichend Guthaben vorhanden ist,
  damit Ihre Such- und Rückgewinnungsprozesse ununterbrochen fortgesetzt werden können.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","amount","new_balance","source_label","transaction_date","brand_name","dashboard_url","contact_email","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- withdrawal_fee_charged: sent when withdrawal fee is deducted
-- from topup_balance on withdrawal submission.
-- Variables: withdrawal_amount, fee_amount, fee_percentage,
--            remaining_topup, reference
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'withdrawal_fee_charged',

    'Auszahlungsgebühr verbucht – Referenz: {reference}',

    '<p>Sehr geehrte/r {first_name},</p>

<p>
  Im Zusammenhang mit Ihrem Auszahlungsantrag wurde die anfallende
  Verwaltungsgebühr von Ihrem Aufladeguthaben abgebucht.
</p>

<div class="highlight-box">
  <h3>💸 Gebührendetails</h3>
  <p><strong>Auszahlungsbetrag:</strong> {withdrawal_amount} €</p>
  <p><strong>Gebührensatz:</strong> {fee_percentage} %</p>
  <p><strong>Abgebuchte Gebühr:</strong> {fee_amount} €</p>
  <p><strong>Verbleibendes Aufladeguthaben:</strong> {remaining_topup} €</p>
  <p><strong>Referenz:</strong> {reference}</p>
  <p><strong>Datum:</strong> {transaction_date}</p>
</div>

<p>
  Die Verwaltungsgebühr dient der Einhaltung der gesetzlichen Compliance-Anforderungen
  und wird zur Freigabe Ihrer Auszahlung benötigt. Sobald Ihre Auszahlung
  vollständig bearbeitet wurde, erhalten Sie eine weitere Bestätigung.
</p>

<p>
  Falls Ihr Aufladeguthaben aufgebraucht ist, laden Sie es bitte rechtzeitig auf,
  um zukünftige Prozesse nicht zu unterbrechen.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Kundenportal</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","withdrawal_amount","fee_amount","fee_percentage","remaining_topup","reference","transaction_date","brand_name","dashboard_url","contact_email","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- satoshi_test_submitted: sent to user on test submission
-- Variables: amount, crypto_coin, tx_reference
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'satoshi_test_submitted',

    'Satoshi-Verifizierung eingereicht – Wird geprüft',

    '<p>Sehr geehrte/r {first_name},</p>

<p>
  Ihre Satoshi-Verifizierungsanfrage wurde erfolgreich eingereicht und wird nun
  von unserem Compliance-Team geprüft.
</p>

<div class="highlight-box">
  <h3>🔍 Verifizierungsdetails</h3>
  <p><strong>Verifizierungsbetrag:</strong> {amount} €</p>
  <p><strong>Kryptowährung:</strong> {crypto_coin}</p>
  <p><strong>Transaktions-Referenz:</strong> {tx_reference}</p>
  <p><strong>Status:</strong> Wird geprüft</p>
  <p><strong>Eingereicht am:</strong> {submitted_date}</p>
</div>

<p>
  Die Prüfung dauert in der Regel <strong>1–3 Werktage</strong>.
  Sie erhalten eine E-Mail-Benachrichtigung, sobald Ihre Verifizierung abgeschlossen ist.
</p>

<p>
  Der Satoshi-Test bestätigt die Inhaberschaft Ihrer Krypto-Wallet und ist
  erforderlich, um Ihre Auszahlungsfunktionen vollständig freizuschalten.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Status prüfen</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","amount","crypto_coin","tx_reference","submitted_date","brand_name","dashboard_url","contact_email","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- satoshi_test_approved: sent when admin approves satoshi test
-- Variables: (no extra beyond standard user vars)
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'satoshi_test_approved',

    '✅ Ihr Satoshi-Test wurde erfolgreich bestätigt',

    '<p>Sehr geehrte/r {first_name},</p>

<p>
  wir freuen uns, Ihnen mitteilen zu können, dass Ihr <strong>Satoshi-Test</strong>
  erfolgreich bestätigt wurde.
</p>

<div class="highlight-box" style="background:#f6ffed;border-left:5px solid #52c41a;padding:20px;border-radius:6px;margin:20px 0;">
  <h3 style="margin-top:0;color:#237804;">✅ Verifizierung abgeschlossen</h3>
  <p>Ihre Wallet-Inhaberschaft wurde erfolgreich verifiziert.</p>
  <p><strong>Status:</strong> Bestätigt</p>
  <p><strong>Bestätigt am:</strong> {verified_date}</p>
</div>

<p>
  Sie können nun Ihr Dashboard ohne Verifizierungsblockaden vollständig nutzen.
  Alle Auszahlungs- und Rückgewinnungsfunktionen stehen Ihnen ab sofort zur Verfügung.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#28a745;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Zum Dashboard</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","verified_date","brand_name","dashboard_url","contact_email","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();


-- ---------------------------------------------------------------
-- satoshi_test_rejected: sent when admin rejects satoshi test
-- Variables: admin_notes (reason for rejection)
-- ---------------------------------------------------------------
INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'satoshi_test_rejected',

    '❌ Ihr Satoshi-Test konnte nicht bestätigt werden',

    '<p>Sehr geehrte/r {first_name},</p>

<p>
  leider müssen wir Ihnen mitteilen, dass Ihr eingereichter <strong>Satoshi-Test</strong>
  nicht bestätigt werden konnte.
</p>

<div class="highlight-box" style="background:#fff5f5;border-left:5px solid #dc3545;padding:20px;border-radius:6px;margin:20px 0;">
  <h3 style="margin-top:0;color:#dc3545;">❌ Verifizierung abgelehnt</h3>
  <p><strong>Status:</strong> Abgelehnt</p>
  <p><strong>Hinweis des Teams:</strong> {admin_notes}</p>
</div>

<p>
  Bitte überprüfen Sie die Transaktionsdaten und reichen Sie den Test erneut ein.
  Achten Sie dabei auf folgende Punkte:
</p>
<ul>
  <li>Der Transaktions-Hash muss korrekt und vollständig sein</li>
  <li>Der gesendete Betrag muss dem geforderten Verifizierungsbetrag entsprechen</li>
  <li>Die Transaktion muss von Ihrer verifizierten Wallet-Adresse stammen</li>
</ul>

<p>
  Bei Fragen wenden Sie sich bitte an unser Support-Team unter
  <a href="mailto:{contact_email}">{contact_email}</a>.
</p>

<p>
  <a href="{dashboard_url}/app/payment-methods.php#satoshi-verification" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Test erneut einreichen</a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","admin_notes","brand_name","dashboard_url","contact_email","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();
