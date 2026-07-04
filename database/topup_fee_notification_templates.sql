-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: Top-up Balance, Withdrawal Fee & Satoshi Test Notification Templates
-- Inserts into email_notifications (admin-triggered manual send templates).
-- Run after email_notifications.sql and more_email_notifications.sql
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO `email_notifications`
  (`notification_key`, `name`, `subject`, `variables`, `content`, `description`, `category`)
VALUES

-- ─────────────────────────────────────────────────────────────────────────────
-- FINANCIAL: Top-up balance added
-- ─────────────────────────────────────────────────────────────────────────────
(
  'topup_balance_added',
  'Aufladeguthaben aufgeladen',
  'Ihr Aufladeguthaben wurde aufgeladen – {amount} €',
  '["first_name","last_name","amount","new_balance","platform_name","site_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>Ihr <strong>Aufladeguthaben</strong> bei <strong>{platform_name}</strong> wurde erfolgreich aufgeladen.</p>
<div style="background:linear-gradient(135deg,#f6ffed,#d9f7be);border:2px solid #52c41a;border-radius:12px;padding:24px;margin:20px 0;text-align:center;">
  <p style="font-size:13px;color:#52c41a;font-weight:600;margin:0 0 8px;text-transform:uppercase;letter-spacing:1px;">Guthabenbuchung</p>
  <p style="font-size:36px;font-weight:800;color:#237804;margin:0;">+ {amount} €</p>
  <p style="font-size:14px;color:#52c41a;margin:8px 0 0;">Neuer Stand: {new_balance} €</p>
</div>
<p>Das Aufladeguthaben wird für folgende Dienste verwendet:</p>
<ul>
  <li>💻 <strong>KI-Analysegebühren</strong> – Automatische Auswertung Ihrer Rückgewinnungsvorgänge</li>
  <li>💸 <strong>Auszahlungsverwaltungsgebühren</strong> – Bearbeitungsgebühr für Auszahlungsanträge</li>
</ul>
<p>Bitte halten Sie Ihr Aufladeguthaben stets ausreichend gefüllt, damit Ihre Prozesse ununterbrochen fortgesetzt werden können.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/index.php" style="background:linear-gradient(135deg,#52c41a,#237804);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Dashboard aufrufen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Team</p>',
  'Wird gesendet, wenn dem Benutzer manuell Aufladeguthaben gutgeschrieben wird.',
  'financial'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- FINANCIAL: Withdrawal fee reminder / info
-- ─────────────────────────────────────────────────────────────────────────────
(
  'withdrawal_fee_reminder',
  'Erinnerung: Auszahlungsgebühr erforderlich',
  'Wichtig: Auszahlungsgebühr für Ihre Anfrage – Referenz {reference}',
  '["first_name","last_name","reference","fee_amount","fee_percentage","withdrawal_amount","platform_name","site_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>für Ihre ausstehende Auszahlungsanfrage ist eine <strong>Verwaltungsgebühr</strong> erforderlich, bevor die Auszahlung bearbeitet werden kann.</p>
<div style="background:#fff3cd;border-left:4px solid #ffc107;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;">⚠ Gebühr noch ausstehend</p>
  <table style="margin-top:8px;width:100%;">
    <tr><td><strong>Auszahlungsbetrag:</strong></td><td>{withdrawal_amount} €</td></tr>
    <tr><td><strong>Gebührensatz:</strong></td><td>{fee_percentage} %</td></tr>
    <tr><td><strong>Fällige Gebühr:</strong></td><td><strong>{fee_amount} €</strong></td></tr>
    <tr><td><strong>Referenz:</strong></td><td>{reference}</td></tr>
  </table>
</div>
<p>
  Gemäß den gesetzlichen Anforderungen internationaler Finanzbehörden sowie den Compliance-Vorgaben
  unserer Bankpartner ist für jede Auszahlung eine einmalige Verwaltungsgebühr zu entrichten.
  Diese Gebühr dient der Einhaltung der Anti-Geldwäsche-Richtlinien (AML/KYC) und der MiFID-II-Regularien.
</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/index.php" style="background:linear-gradient(135deg,#ffc107,#e0a800);color:#212529;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Gebühr begleichen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Finanzteam</p>',
  'Erinnerung, wenn die Auszahlungsgebühr noch nicht beglichen wurde.',
  'financial'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- FINANCIAL: Topup balance low / depleted warning
-- ─────────────────────────────────────────────────────────────────────────────
(
  'topup_balance_low',
  'Aufladeguthaben niedrig',
  'Ihr Aufladeguthaben bei {platform_name} ist fast aufgebraucht',
  '["first_name","last_name","current_balance","platform_name","site_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>Ihr <strong>Aufladeguthaben</strong> bei <strong>{platform_name}</strong> ist auf <strong>{current_balance} €</strong> gesunken.</p>
<div style="background:#fff3cd;border-left:4px solid #ffc107;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;">⚠ Geringes Aufladeguthaben</p>
  <p style="margin:8px 0 0;">Aktueller Stand: <strong>{current_balance} €</strong></p>
  <p style="margin:4px 0 0;">Bitte laden Sie Ihr Konto auf, damit Ihre Prozesse ohne Unterbrechung fortgesetzt werden können.</p>
</div>
<p><strong>Warum ist das Aufladeguthaben wichtig?</strong></p>
<ul>
  <li>🤖 <strong>KI-Analysen</strong> laufen ohne Unterbrechung</li>
  <li>💸 <strong>Auszahlungsgebühren</strong> können sofort beglichen werden</li>
  <li>⚡ <strong>Schnellere Bearbeitung</strong> Ihrer Rückgewinnungsvorgänge</li>
</ul>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/deposit.php" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Jetzt aufladen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Team</p>',
  'Warnung, wenn das Aufladeguthaben des Benutzers unter einen niedrigen Schwellenwert gefallen ist.',
  'financial'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- VERIFICATION: Satoshi test required
-- ─────────────────────────────────────────────────────────────────────────────
(
  'satoshi_test_required',
  'Satoshi-Verifizierung erforderlich',
  'Aktion erforderlich: Satoshi-Test für Ihr Konto bei {platform_name}',
  '["first_name","last_name","current_balance","threshold_amount","platform_name","site_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>Ihr Kontostand hat die Schwelle von <strong>{threshold_amount} €</strong> erreicht. Um weiterhin vollständigen Zugang zu Ihrem Dashboard und allen Auszahlungsfunktionen zu haben, ist ein <strong>Satoshi-Test</strong> erforderlich.</p>
<div style="background:linear-gradient(135deg,#e6f7ff,#bae7ff);border-radius:12px;padding:24px;margin:20px 0;">
  <p style="font-size:20px;font-weight:700;color:#0050b3;margin:0 0 8px;">🔐 Wallet-Inhaberschaft bestätigen</p>
  <p style="color:#1890ff;margin:0 0 12px;">Aktueller Kontostand: <strong>{current_balance} €</strong></p>
  <p style="color:#555;margin:0;font-size:14px;">
    Der Satoshi-Test ist ein einfaches Verifizierungsverfahren, bei dem Sie eine kleine Krypto-Transaktion
    durchführen, um die Inhaberschaft Ihrer Wallet zu bestätigen.
  </p>
</div>
<p><strong>So funktioniert der Satoshi-Test:</strong></p>
<ol>
  <li>Öffnen Sie die Seite <strong>Zahlungsmethoden / Verifizierung</strong></li>
  <li>Wählen Sie Ihre Kryptowährung aus</li>
  <li>Senden Sie den angezeigten Verifizierungsbetrag</li>
  <li>Geben Sie den Transaktions-Hash ein und reichen Sie ihn ein</li>
</ol>
<p style="text-align:center;margin:28px 0;">
  <a href="{site_url}/app/payment-methods.php#satoshi-verification" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;font-size:16px;box-shadow:0 4px 12px rgba(24,144,255,0.4);">
    Jetzt verifizieren &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Compliance-Team</p>',
  'Aufforderung zur Durchführung des Satoshi-Tests, wenn der Kontostand die Schwelle erreicht hat.',
  'verification'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- VERIFICATION: Satoshi test approved
-- ─────────────────────────────────────────────────────────────────────────────
(
  'satoshi_test_verified',
  'Satoshi-Test bestätigt',
  '✅ Ihre Wallet-Verifizierung bei {platform_name} war erfolgreich',
  '["first_name","last_name","platform_name","site_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>wir freuen uns, Ihnen mitteilen zu können, dass Ihr <strong>Satoshi-Test</strong> erfolgreich abgeschlossen und Ihre Wallet-Inhaberschaft bestätigt wurde.</p>
<div style="background:linear-gradient(135deg,#f6ffed,#d9f7be);border:2px solid #52c41a;border-radius:16px;padding:32px;margin:24px 0;text-align:center;">
  <p style="font-size:48px;margin:0 0 8px;">✅</p>
  <p style="font-size:14px;color:#52c41a;font-weight:600;margin:0 0 12px;text-transform:uppercase;letter-spacing:1.5px;">Verifizierung erfolgreich</p>
  <p style="font-size:22px;font-weight:700;color:#237804;margin:0;">Wallet-Inhaberschaft bestätigt</p>
</div>
<p>Sie können nun Ihr Dashboard ohne Einschränkungen nutzen. Alle Funktionen stehen Ihnen vollständig zur Verfügung:</p>
<ul>
  <li>✅ <strong>Vollständiger Dashboard-Zugriff</strong></li>
  <li>✅ <strong>Auszahlungsanträge</strong> können gestellt werden</li>
  <li>✅ <strong>Alle KI-Analyse-Funktionen</strong> aktiv</li>
</ul>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/index.php" style="background:linear-gradient(135deg,#52c41a,#237804);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Zum Dashboard &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Team</p>',
  'Bestätigung nach erfolgreicher Satoshi-Verifizierung.',
  'verification'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- VERIFICATION: Satoshi test rejected / re-submit reminder
-- ─────────────────────────────────────────────────────────────────────────────
(
  'satoshi_test_rejected',
  'Satoshi-Test abgelehnt – erneut einreichen',
  '⚠ Satoshi-Test abgelehnt – Bitte erneut einreichen bei {platform_name}',
  '["first_name","last_name","rejection_reason","platform_name","site_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>leider konnte Ihr eingereichter <strong>Satoshi-Test</strong> nicht bestätigt werden.</p>
<div style="background:#fff5f5;border-left:4px solid #dc3545;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;color:#dc3545;">❌ Verifizierung abgelehnt</p>
  <p style="margin:8px 0 0;"><strong>Hinweis:</strong> {rejection_reason}</p>
</div>
<p><strong>Bitte überprüfen Sie folgende Punkte beim erneuten Einreichen:</strong></p>
<ul>
  <li>Der Transaktions-Hash muss korrekt und vollständig angegeben werden</li>
  <li>Der gesendete Betrag muss dem geforderten Verifizierungsbetrag entsprechen</li>
  <li>Die Transaktion muss von Ihrer verifizierten Wallet-Adresse stammen</li>
  <li>Die Transaktion muss auf der Blockchain bestätigt sein</li>
</ul>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/payment-methods.php#satoshi-verification" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Test erneut einreichen &rarr;
  </a>
</p>
<p>Bei Fragen kontaktieren Sie uns gerne direkt.</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Compliance-Team</p>',
  'Benachrichtigung nach Ablehnung des Satoshi-Tests mit Aufforderung zur erneuten Einreichung.',
  'verification'
)

ON DUPLICATE KEY UPDATE
    name        = VALUES(name),
    subject     = VALUES(subject),
    variables   = VALUES(variables),
    content     = VALUES(content),
    description = VALUES(description),
    category    = VALUES(category),
    updated_at  = NOW();
