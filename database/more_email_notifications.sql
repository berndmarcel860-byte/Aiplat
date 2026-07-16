-- ─────────────────────────────────────────────────────────────────────────────
-- Additional German Email Notification Templates
-- Run after email_notifications.sql
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO `email_notifications`
  (`notification_key`, `name`, `subject`, `variables`, `content`, `description`, `category`)
VALUES

-- ─────────────────────────────────────────────────────────────────────────────
-- ENGAGEMENT: never logged in
-- ─────────────────────────────────────────────────────────────────────────────
(
  'never_logged_in',
  'Erster Login-Aufruf',
  'Willkommen bei {platform_name} – Melden Sie sich jetzt an und starten Sie',
  '["first_name","last_name","registration_date","site_url","platform_name","login_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>herzlich willkommen bei <strong>{platform_name}</strong>! Sie haben sich am <strong>{registration_date}</strong> registriert, aber wir haben noch keinen Login von Ihnen festgestellt.</p>
<div style="background:linear-gradient(135deg,#e6f7ff,#bae7ff);border-radius:12px;padding:24px;margin:20px 0;text-align:center;">
  <p style="font-size:20px;font-weight:700;color:#0050b3;margin:0 0 8px;">🚀 Ihr Konto wartet auf Sie!</p>
  <p style="color:#1890ff;margin:0;">Hunderte von Kunden vertrauen bereits auf unsere KI-gestützte Rückgewinnungslösung.</p>
</div>
<p><strong>Was Sie erwartet, sobald Sie sich anmelden:</strong></p>
<ul>
  <li>🤖 <strong>KI-Analyse:</strong> Automatische Auswertung Ihres Schadensfalls</li>
  <li>📊 <strong>Dashboard:</strong> Übersichtliche Darstellung Ihres Rückgewinnungsfortschritts</li>
  <li>🔒 <strong>Sicherheit:</strong> Vollständig verschlüsselte Kommunikation</li>
  <li>💬 <strong>Support:</strong> Direkter Kontakt zu Ihrem persönlichen Betreuer</li>
</ul>
<p>Der erste Login dauert nur wenige Sekunden – danach führt Sie unser System Schritt für Schritt durch alles Weitere.</p>
<p style="text-align:center;margin:28px 0;">
  <a href="{login_url}" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;font-size:16px;box-shadow:0 4px 12px rgba(24,144,255,0.4);">
    Jetzt anmelden &rarr;
  </a>
</p>
<p style="font-size:13px;color:#888;">Falls Sie Ihres Passwort vergessen haben, können Sie es auf der Login-Seite zurücksetzen.</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Team</p>',
  'Erinnerungs-E-Mail für Benutzer, die sich nach der Registrierung noch nie angemeldet haben.',
  'engagement'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- FINANCIAL: payment method reminder
-- ─────────────────────────────────────────────────────────────────────────────
(
  'payment_method_missing',
  'Zahlungsmethode fehlt',
  'Hinterlegen Sie eine Zahlungsmethode für Ihre Auszahlungen bei {platform_name}',
  '["first_name","last_name","site_url","platform_name","payments_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>für zukünftige Auszahlungen Ihrer zurückgewonnenen Gelder benötigen wir eine <strong>verifizierte Zahlungsmethode</strong> von Ihnen.</p>
<div style="background:#fff3cd;border-left:4px solid #ffc107;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;">⚠ Keine Zahlungsmethode hinterlegt</p>
  <p style="margin:8px 0 0;">Ohne hinterlegte und verifizierte Zahlungsmethode können Auszahlungen nicht verarbeitet werden.</p>
</div>
<p><strong>Akzeptierte Zahlungsmethoden:</strong></p>
<ul>
  <li>🏦 <strong>Banküberweisung (SEPA/SWIFT)</strong> – Für schnelle und sichere Überweisungen in ganz Europa</li>
  <li>₿ <strong>Kryptowährung</strong> – Bitcoin, Ethereum und weitere</li>
</ul>
<p>Die Hinterlegung und Verifizierung dauert in der Regel nur <strong>wenige Minuten</strong>.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{payments_url}" style="background:linear-gradient(135deg,#52c41a,#237804);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Zahlungsmethode hinterlegen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Finanzteam</p>',
  'Erinnerung für Benutzer ohne hinterlegte Zahlungsmethode.',
  'financial'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- CASE: case resolved successfully
-- ─────────────────────────────────────────────────────────────────────────────
(
  'case_resolved',
  'Fall erfolgreich abgeschlossen',
  '🎉 Ihr Rückgewinnungsfall #{case_number} wurde erfolgreich abgeschlossen',
  '["first_name","last_name","case_number","recovered_amount","currency","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>wir freuen uns, Ihnen eine <strong>ausgezeichnete Neuigkeit</strong> mitteilen zu können: Ihr Rückgewinnungsfall <strong>#{case_number}</strong> wurde erfolgreich abgeschlossen!</p>
<div style="background:linear-gradient(135deg,#f6ffed,#d9f7be);border:2px solid #52c41a;border-radius:16px;padding:32px;margin:24px 0;text-align:center;">
  <p style="font-size:48px;margin:0 0 8px;">🎉</p>
  <p style="font-size:14px;color:#52c41a;font-weight:600;margin:0 0 12px;text-transform:uppercase;letter-spacing:1.5px;">Erfolgreich zurückgewonnen</p>
  <p style="font-size:42px;font-weight:800;color:#237804;margin:0;">{recovered_amount} {currency}</p>
  <p style="font-size:13px;color:#52c41a;margin:8px 0 0;">Fall #{case_number}</p>
</div>
<p>Dank unserer KI-gestützten Analyse und der harten Arbeit unseres Teams konnten Ihre Gelder erfolgreich zurückgewonnen werden.</p>
<p>Die Auszahlung wird gemäß Ihrer hinterlegten Zahlungsmethode veranlasst. Bitte überprüfen Sie Ihre Kontodaten in Ihrem Dashboard.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/transactions.php" style="background:linear-gradient(135deg,#52c41a,#237804);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Transaktionsdetails ansehen &rarr;
  </a>
</p>
<p>Vielen Dank für Ihr Vertrauen in <strong>{platform_name}</strong>. Es war uns eine Freude, Ihnen zu helfen!</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Rückgewinnungsteam</p>',
  'Benachrichtigung, wenn ein Fall erfolgreich abgeschlossen wurde.',
  'case'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- CASE: case escalated
-- ─────────────────────────────────────────────────────────────────────────────
(
  'case_escalated',
  'Fall eskaliert – Prioritätsstufe erhöht',
  '⚡ Ihr Fall #{case_number} wurde eskaliert – erhöhte Priorität',
  '["first_name","last_name","case_number","escalation_reason","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>wir haben Ihren Rückgewinnungsfall <strong>#{case_number}</strong> auf eine <strong>höhere Prioritätsstufe</strong> angehoben.</p>
<div style="background:linear-gradient(135deg,#fff7e6,#ffe7ba);border-left:4px solid #fa8c16;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;color:#d46b08;">⚡ Eskalationsgrund</p>
  <p style="margin:8px 0 0;">{escalation_reason}</p>
</div>
<p>Was bedeutet das für Sie?</p>
<ul>
  <li>✅ Ihr Fall wird nun von unserem <strong>Senior-Rückgewinnungsteam</strong> bearbeitet</li>
  <li>✅ <strong>Tägliche Updates</strong> zum Bearbeitungsstand</li>
  <li>✅ <strong>Priorisierte KI-Analyse</strong> für schnellere Ergebnisse</li>
</ul>
<p>Sie müssen nichts weiter tun. Unser Team wird sich bei Ihnen melden, sobald weitere Informationen benötigt werden oder neue Entwicklungen vorliegen.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/cases.php" style="background:linear-gradient(135deg,#fa8c16,#d46b08);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Falldetails ansehen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Rückgewinnungsteam</p>',
  'Benachrichtigung, wenn ein Fall auf eine höhere Prioritätsstufe eskaliert wird.',
  'case'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- SUPPORT: ticket reply notification
-- ─────────────────────────────────────────────────────────────────────────────
(
  'support_ticket_new_reply',
  'Neue Antwort auf Ihr Support-Ticket',
  '💬 Neue Antwort auf Ihr Support-Ticket #{ticket_number}',
  '["first_name","last_name","ticket_number","ticket_subject","reply_preview","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>unser Support-Team hat auf Ihr Ticket <strong>#{ticket_number}</strong> geantwortet.</p>
<div style="background:#f9f0ff;border-left:4px solid #722ed1;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;color:#722ed1;">📝 Betreff: {ticket_subject}</p>
  <p style="margin:12px 0 0;font-style:italic;color:#595959;">&ldquo;{reply_preview}&rdquo;</p>
</div>
<p>Melden Sie sich in Ihrem Konto an, um die vollständige Antwort zu lesen und bei Bedarf zu antworten.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/support.php" style="background:linear-gradient(135deg,#722ed1,#531dab);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Ticket öffnen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Support-Team</p>',
  'Benachrichtigung, wenn das Support-Team auf ein Ticket geantwortet hat.',
  'support'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- ACCOUNT: account reactivated
-- ─────────────────────────────────────────────────────────────────────────────
(
  'account_reactivated',
  'Konto reaktiviert',
  '✅ Ihr {platform_name}-Konto wurde erfolgreich reaktiviert',
  '["first_name","last_name","site_url","platform_name","login_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>wir freuen uns, Ihnen mitteilen zu können, dass Ihr <strong>{platform_name}</strong>-Konto erfolgreich <strong>reaktiviert</strong> wurde!</p>
<div style="background:#f6ffed;border-left:4px solid #52c41a;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;color:#237804;">✅ Konto aktiv</p>
  <p style="margin:8px 0 0;">Sie haben ab sofort wieder vollen Zugriff auf alle Funktionen Ihres Kontos.</p>
</div>
<p>Sie können sich nun wieder anmelden und Ihren Rückgewinnungsfortschritt einsehen.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{login_url}" style="background:linear-gradient(135deg,#52c41a,#237804);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Jetzt anmelden &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Team</p>',
  'Wird gesendet, wenn ein zuvor gesperrtes Konto vom Admin reaktiviert wird.',
  'account'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- CASE: new case submitted confirmation
-- ─────────────────────────────────────────────────────────────────────────────
(
  'new_case_submitted',
  'Neuer Fall eingereicht – Bestätigung',
  '📁 Ihr Rückgewinnungsfall #{case_number} wurde eingereicht',
  '["first_name","last_name","case_number","case_title","estimated_amount","currency","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>vielen Dank! Ihr Rückgewinnungsfall wurde erfolgreich eingereicht und wird nun von unserem System bearbeitet.</p>
<div style="background:#f0f7ff;border-radius:12px;padding:24px;margin:20px 0;">
  <p style="font-size:14px;color:#1890ff;font-weight:600;margin:0 0 16px;text-transform:uppercase;letter-spacing:1px;">📋 Fallübersicht</p>
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="padding:6px 0;color:#666;font-size:13px;width:40%;">Fallnummer:</td>
      <td style="padding:6px 0;font-weight:700;font-size:13px;">#{case_number}</td>
    </tr>
    <tr>
      <td style="padding:6px 0;color:#666;font-size:13px;">Betreff:</td>
      <td style="padding:6px 0;font-size:13px;">{case_title}</td>
    </tr>
    <tr>
      <td style="padding:6px 0;color:#666;font-size:13px;">Geschätzter Betrag:</td>
      <td style="padding:6px 0;font-weight:700;color:#237804;font-size:14px;">{estimated_amount} {currency}</td>
    </tr>
    <tr>
      <td style="padding:6px 0;color:#666;font-size:13px;">Status:</td>
      <td style="padding:6px 0;"><span style="background:#e6f7ff;color:#1890ff;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600;">In Bearbeitung</span></td>
    </tr>
  </table>
</div>
<p><strong>Was passiert als nächstes?</strong></p>
<ol>
  <li>Unsere KI analysiert Ihren Fall innerhalb der nächsten <strong>24–48 Stunden</strong></li>
  <li>Sie erhalten eine Benachrichtigung, sobald neue Erkenntnisse vorliegen</li>
  <li>Bei Bedarf fordern wir weitere Dokumente an</li>
</ol>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/cases.php" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Fallstatus verfolgen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Rückgewinnungsteam</p>',
  'Eingangsbestätigung, wenn ein Benutzer einen neuen Rückgewinnungsfall einreicht.',
  'case'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- ENGAGEMENT: annual summary
-- ─────────────────────────────────────────────────────────────────────────────
(
  'annual_summary',
  'Jahresrückblick',
  '📅 Ihr {platform_name}-Jahresrückblick {year}',
  '["first_name","last_name","year","cases_count","recovered_amount","currency","days_active","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>das Jahr <strong>{year}</strong> neigt sich dem Ende entgegen – Zeit für einen persönlichen Rückblick auf Ihre Aktivitäten bei <strong>{platform_name}</strong>.</p>
<div style="background:linear-gradient(135deg,#001529,#0050b3);color:#fff;border-radius:16px;padding:32px;margin:24px 0;text-align:center;">
  <p style="font-size:22px;font-weight:700;margin:0 0 24px;opacity:0.9;">{platform_name} · Jahresrückblick {year}</p>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
    <div style="background:rgba(255,255,255,0.12);border-radius:10px;padding:20px;">
      <p style="font-size:32px;font-weight:800;margin:0;">{cases_count}</p>
      <p style="font-size:11px;opacity:0.75;margin:6px 0 0;text-transform:uppercase;letter-spacing:1px;">Fälle bearbeitet</p>
    </div>
    <div style="background:rgba(255,255,255,0.12);border-radius:10px;padding:20px;">
      <p style="font-size:32px;font-weight:800;margin:0;">{days_active}</p>
      <p style="font-size:11px;opacity:0.75;margin:6px 0 0;text-transform:uppercase;letter-spacing:1px;">Aktive Tage</p>
    </div>
    <div style="background:rgba(82,196,26,0.3);border-radius:10px;padding:20px;">
      <p style="font-size:26px;font-weight:800;margin:0;">{recovered_amount}</p>
      <p style="font-size:11px;opacity:0.9;margin:6px 0 0;text-transform:uppercase;letter-spacing:1px;">{currency} zurückgewonnen</p>
    </div>
  </div>
</div>
<p>Wir bedanken uns herzlich für Ihr Vertrauen und freuen uns darauf, Sie auch im nächsten Jahr auf Ihrem Weg zu unterstützen.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/index.php" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Dashboard öffnen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen und den besten Wünschen,<br>Ihr gesamtes {platform_name}-Team</p>',
  'Jahresrückblick mit persönlicher Zusammenfassung für den Benutzer.',
  'reporting'
)

ON DUPLICATE KEY UPDATE
  `name`        = VALUES(`name`),
  `subject`     = VALUES(`subject`),
  `variables`   = VALUES(`variables`),
  `content`     = VALUES(`content`),
  `description` = VALUES(`description`),
  `category`    = VALUES(`category`);
