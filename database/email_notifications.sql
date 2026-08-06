-- =============================================================================
-- Email Notifications Table
-- Dedicated notification templates for admin-triggered user emails
-- =============================================================================

CREATE TABLE IF NOT EXISTS `email_notifications` (
    `id`               INT(11)      NOT NULL AUTO_INCREMENT,
    `notification_key` VARCHAR(100) NOT NULL UNIQUE,
    `name`             VARCHAR(200) NOT NULL,
    `subject`          VARCHAR(500) NOT NULL,
    `variables`        JSON         NOT NULL DEFAULT ('[]'),
    `content`          TEXT         NOT NULL,
    `description`      TEXT         DEFAULT NULL,
    `category`         VARCHAR(100) NOT NULL DEFAULT 'general',
    `is_active`        TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Seed: Professional German notification templates
-- =============================================================================

INSERT INTO `email_notifications`
  (`notification_key`, `name`, `subject`, `variables`, `content`, `description`, `category`)
VALUES

-- ─────────────────────────────────────────────────────────────────────────────
-- ENGAGEMENT / INACTIVITY
-- ─────────────────────────────────────────────────────────────────────────────
(
  'inactive_7_days',
  'Inaktivitätserinnerung (7 Tage)',
  'Wir vermissen Sie, {first_name} – Ihr Konto wartet auf Sie',
  '["first_name","last_name","last_login_date","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>wir haben bemerkt, dass Sie sich seit <strong>{last_login_date}</strong> nicht mehr in Ihrem {platform_name}-Konto angemeldet haben.</p>
<p>Ihr Konto ist aktiv und Ihr Rückgewinnungsprozess läuft weiter. Damit wir Ihnen optimal helfen können, möchten wir Sie einladen, einen kurzen Blick auf Ihre aktuellen Fallstatus zu werfen.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/index.php" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Jetzt anmelden &rarr;
  </a>
</p>
<p>Ihre Daten sind sicher und Ihr Rückgewinnungsteam arbeitet weiterhin für Sie. Bei Fragen stehen wir Ihnen jederzeit zur Verfügung.</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Team</p>',
  'Wird gesendet, wenn ein Benutzer seit 7 Tagen nicht aktiv war.',
  'engagement'
),

(
  'inactive_30_days',
  'Inaktivitätserinnerung (30 Tage)',
  'Ihr {platform_name}-Konto: Wichtige Statusaktualisierungen warten auf Sie',
  '["first_name","last_name","last_login_date","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>es ist nun über einen Monat her, seit wir Sie zuletzt in Ihrem Konto gesehen haben (letzter Login: <strong>{last_login_date}</strong>).</p>
<p>Wir möchten Sie daran erinnern, dass Ihr Rückgewinnungsfall weiterhin aktiv betreut wird. Möglicherweise liegen wichtige Updates oder Aktionen vor, die Ihre Aufmerksamkeit erfordern.</p>
<p><strong>Was könnte Sie erwarten:</strong></p>
<ul>
  <li>Neue Erkenntnisse aus der KI-Analyse Ihres Falls</li>
  <li>Ausstehende Dokumentenanforderungen</li>
  <li>Statusaktualisierungen Ihrer Rückgewinnungsmaßnahmen</li>
  <li>Wichtige Nachrichten von Ihrem Bearbeiter</li>
</ul>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/index.php" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Fallstatus prüfen &rarr;
  </a>
</p>
<p>Ihr Konto bleibt aktiv und geschützt. Wir sind jederzeit für Sie da.</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Team</p>',
  'Wird gesendet, wenn ein Benutzer seit 30 Tagen nicht aktiv war.',
  'engagement'
),

(
  'inactive_90_days',
  'Langzeitinaktivität (90 Tage)',
  'Dringende Erinnerung: Ihr Rückgewinnungsfall benötigt Ihre Aufmerksamkeit',
  '["first_name","last_name","last_login_date","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>wir machen uns Sorgen, da Sie sich seit über <strong>90 Tagen</strong> (letzter Login: <strong>{last_login_date}</strong>) nicht mehr in Ihrem Konto angemeldet haben.</p>
<p>Ihr aktiver Rückgewinnungsfall läuft weiterhin, jedoch sind möglicherweise <strong>wichtige Entscheidungen oder Informationen von Ihnen erforderlich</strong>, um den Prozess voranzubringen.</p>
<div style="background:#fff3cd;border-left:4px solid #ffc107;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;">⚠ Handlungsbedarf möglich</p>
  <p style="margin:8px 0 0;">Bitte melden Sie sich an, um zu prüfen, ob ausstehende Aufgaben Ihren Fortschritt beeinflussen könnten.</p>
</div>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/index.php" style="background:linear-gradient(135deg,#ff4d4f,#cf1322);color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:700;display:inline-block;font-size:16px;">
    Jetzt anmelden &rarr;
  </a>
</p>
<p>Falls Sie Fragen haben oder Unterstützung benötigen, kontaktieren Sie uns bitte direkt. Wir sind hier, um Ihnen zu helfen.</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Team</p>',
  'Wird gesendet, wenn ein Benutzer seit 90 Tagen nicht aktiv war.',
  'engagement'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- ONBOARDING
-- ─────────────────────────────────────────────────────────────────────────────
(
  'onboarding_required',
  'Onboarding unvollständig',
  'Vervollständigen Sie Ihr Profil – wichtiger Schritt für Ihren Rückgewinnungserfolg',
  '["first_name","last_name","site_url","platform_name","onboarding_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>herzlich willkommen bei <strong>{platform_name}</strong>! Wir freuen uns, Sie als Kunden begrüßen zu dürfen.</p>
<p>Wir haben festgestellt, dass Ihr Konto-Setup noch nicht abgeschlossen ist. Um mit der KI-gestützten Analyse Ihres Falls zu beginnen, bitten wir Sie, noch einige wenige Schritte zu vervollständigen:</p>
<div style="background:#f0f7ff;border-radius:8px;padding:20px;margin:16px 0;">
  <p style="margin:0 0 12px;font-weight:600;color:#1890ff;">📋 Ihr Onboarding-Fortschritt</p>
  <ul style="margin:0;padding-left:20px;">
    <li>✅ Konto erstellt</li>
    <li>⏳ Persönliche Daten vervollständigen</li>
    <li>⏳ Schadensinformationen eingeben</li>
    <li>⏳ Dokumentenupload</li>
    <li>⏳ KYC-Verifizierung starten</li>
  </ul>
</div>
<p>Je vollständiger Ihr Profil ist, desto schneller und präziser kann unsere KI eine Rückgewinnungsstrategie für Sie entwickeln.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{onboarding_url}" style="background:linear-gradient(135deg,#52c41a,#237804);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Onboarding jetzt abschließen &rarr;
  </a>
</p>
<p>Das Onboarding dauert in der Regel nur <strong>5–10 Minuten</strong>. Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Onboarding-Team</p>',
  'Erinnerung für Benutzer, die das Onboarding noch nicht abgeschlossen haben.',
  'onboarding'
),

(
  'onboarding_reminder_3_days',
  'Onboarding-Erinnerung (nach 3 Tagen)',
  'Nur noch wenige Schritte – schließen Sie Ihr {platform_name}-Onboarding ab',
  '["first_name","last_name","site_url","platform_name","onboarding_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>vor 3 Tagen haben Sie Ihr Konto bei <strong>{platform_name}</strong> eröffnet – herzlichen Glückwunsch zu diesem wichtigen Schritt!</p>
<p>Allerdings ist Ihr Profil noch nicht vollständig eingerichtet. Ohne vollständige Informationen können wir die KI-Analyse Ihres Falls nicht optimal durchführen.</p>
<p><strong>Warum ist das Onboarding so wichtig?</strong></p>
<ul>
  <li>🤖 Unsere KI benötigt vollständige Fallinformationen für präzise Analysen</li>
  <li>⚡ Schnellere Bearbeitung Ihres Falls durch unser Team</li>
  <li>🔒 Höhere Erfolgswahrscheinlichkeit bei der Rückgewinnung</li>
  <li>📊 Zugang zu personalisierten Statistiken und Updates</li>
</ul>
<p style="text-align:center;margin:24px 0;">
  <a href="{onboarding_url}" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Profil vervollständigen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Team</p>',
  'Wird 3 Tage nach Registrierung gesendet, wenn das Onboarding noch nicht abgeschlossen ist.',
  'onboarding'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- KYC
-- ─────────────────────────────────────────────────────────────────────────────
(
  'kyc_required',
  'KYC-Verifizierung erforderlich',
  'Wichtig: Identitätsverifizierung für Ihr {platform_name}-Konto erforderlich',
  '["first_name","last_name","site_url","platform_name","kyc_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>um Ihr Konto bei <strong>{platform_name}</strong> vollständig freizuschalten und alle Dienste nutzen zu können, ist eine <strong>Identitätsverifizierung (KYC)</strong> erforderlich.</p>
<div style="background:#fff1f0;border-left:4px solid #ff4d4f;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;color:#cf1322;">🔒 KYC-Verifizierung ausstehend</p>
  <p style="margin:8px 0 0;">Ohne abgeschlossene Verifizierung sind Auszahlungen und bestimmte Kontofunktionen eingeschränkt.</p>
</div>
<p><strong>Was benötigen Sie für die KYC-Verifizierung?</strong></p>
<ul>
  <li>📄 Gültiger Personalausweis oder Reisepass</li>
  <li>📸 Aktuelles Selfie (zur Identitätsbestätigung)</li>
  <li>🏠 Adressnachweis (Rechnung oder Kontoauszug, max. 3 Monate alt)</li>
</ul>
<p>Der Verifizierungsprozess dauert in der Regel nur <strong>2–3 Minuten</strong> und wird von unserem Team innerhalb von 24 Stunden überprüft.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{kyc_url}" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    KYC-Verifizierung starten &rarr;
  </a>
</p>
<p>Bei Fragen zu diesem Prozess stehen wir Ihnen gerne zur Verfügung.</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Compliance-Team</p>',
  'Für Benutzer, die noch keine KYC-Verifizierung abgeschlossen haben.',
  'kyc'
),

(
  'kyc_rejected_resubmit',
  'KYC abgelehnt – Erneute Einreichung erforderlich',
  'Ihre KYC-Verifizierung wurde abgelehnt – Bitte reichen Sie Ihre Dokumente erneut ein',
  '["first_name","last_name","rejection_reason","site_url","platform_name","kyc_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>leider müssen wir Sie informieren, dass Ihre eingereichten KYC-Dokumente <strong>nicht akzeptiert</strong> werden konnten.</p>
<div style="background:#fff3cd;border-left:4px solid #ffc107;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;">📋 Ablehnungsgrund</p>
  <p style="margin:8px 0 0;">{rejection_reason}</p>
</div>
<p><strong>Häufige Gründe für eine Ablehnung:</strong></p>
<ul>
  <li>Dokument abgelaufen oder unleserlich</li>
  <li>Selfie stimmt nicht mit dem Ausweisfoto überein</li>
  <li>Adressnachweis älter als 3 Monate</li>
  <li>Dokument wurde beschnitten oder ist unvollständig sichtbar</li>
</ul>
<p>Bitte laden Sie gültige Dokumente erneut hoch. Stellen Sie sicher, dass alle Informationen gut lesbar und vollständig sichtbar sind.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{kyc_url}" style="background:linear-gradient(135deg,#fa8c16,#d46b08);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Dokumente erneut einreichen &rarr;
  </a>
</p>
<p>Bei Fragen helfen wir Ihnen gerne weiter.</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Compliance-Team</p>',
  'Für Benutzer, deren KYC-Verifizierung abgelehnt wurde.',
  'kyc'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- FINANCIAL
-- ─────────────────────────────────────────────────────────────────────────────
(
  'withdrawal_ready',
  'Auszahlung bereit',
  '✅ Ihre Auszahlung ist genehmigt und bereit zur Überweisung',
  '["first_name","last_name","amount","currency","withdrawal_method","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>wir freuen uns, Ihnen mitteilen zu können, dass Ihre Auszahlungsanfrage genehmigt wurde!</p>
<div style="background:linear-gradient(135deg,#f6ffed,#d9f7be);border:1px solid #b7eb8f;border-radius:12px;padding:24px;margin:20px 0;text-align:center;">
  <p style="font-size:14px;color:#52c41a;font-weight:600;margin:0 0 8px;text-transform:uppercase;letter-spacing:1px;">Genehmigte Auszahlung</p>
  <p style="font-size:36px;font-weight:700;color:#237804;margin:0;">{amount} {currency}</p>
  <p style="font-size:13px;color:#52c41a;margin:8px 0 0;">via {withdrawal_method}</p>
</div>
<p>Die Überweisung wird innerhalb der nächsten <strong>1–3 Werktage</strong> auf Ihrem angegebenen Konto gutgeschrieben.</p>
<p>Sie können den Status Ihrer Auszahlung jederzeit in Ihrem Konto einsehen:</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/transactions.php" style="background:linear-gradient(135deg,#52c41a,#237804);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Transaktionsstatus ansehen &rarr;
  </a>
</p>
<p>Vielen Dank für Ihr Vertrauen in <strong>{platform_name}</strong>. Wir arbeiten weiterhin daran, Ihnen den bestmöglichen Service zu bieten.</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Finanzteam</p>',
  'Wird gesendet, wenn eine Auszahlung genehmigt und bereit zur Überweisung ist.',
  'financial'
),

(
  'deposit_confirmation',
  'Einzahlungsbestätigung',
  '✅ Ihre Einzahlung wurde erfolgreich verarbeitet',
  '["first_name","last_name","amount","currency","reference","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>Ihre Einzahlung wurde erfolgreich auf Ihrem <strong>{platform_name}</strong>-Konto verbucht.</p>
<div style="background:linear-gradient(135deg,#e6f7ff,#bae7ff);border:1px solid #91d5ff;border-radius:12px;padding:24px;margin:20px 0;text-align:center;">
  <p style="font-size:14px;color:#1890ff;font-weight:600;margin:0 0 8px;text-transform:uppercase;letter-spacing:1px;">Einzahlung verbucht</p>
  <p style="font-size:36px;font-weight:700;color:#0050b3;margin:0;">+{amount} {currency}</p>
  <p style="font-size:13px;color:#1890ff;margin:8px 0 0;">Referenz: {reference}</p>
</div>
<p>Ihr Kontostand wurde aktualisiert. Sie können Ihren aktuellen Saldo jederzeit in Ihrem Dashboard einsehen.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/index.php" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Dashboard öffnen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Finanzteam</p>',
  'Bestätigungs-E-Mail nach erfolgreicher Einzahlung.',
  'financial'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- CASE / RECOVERY
-- ─────────────────────────────────────────────────────────────────────────────
(
  'case_update',
  'Fallstatusaktualisierung',
  'Neuigkeiten zu Ihrem Rückgewinnungsfall #{case_number}',
  '["first_name","last_name","case_number","case_status","update_message","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>es gibt neue Entwicklungen zu Ihrem Rückgewinnungsfall <strong>#{case_number}</strong>, über die wir Sie informieren möchten.</p>
<div style="background:#f9f0ff;border-left:4px solid #722ed1;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;color:#722ed1;">📊 Fallstatus: {case_status}</p>
  <p style="margin:12px 0 0;">{update_message}</p>
</div>
<p>Unser KI-System analysiert kontinuierlich Ihren Fall, um die beste Strategie für eine erfolgreiche Rückgewinnung zu entwickeln. Wir halten Sie über alle wichtigen Entwicklungen auf dem Laufenden.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/cases.php" style="background:linear-gradient(135deg,#722ed1,#531dab);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Falldetails ansehen &rarr;
  </a>
</p>
<p>Bei Fragen oder wenn Sie zusätzliche Informationen bereitstellen möchten, stehen wir Ihnen jederzeit zur Verfügung.</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Rückgewinnungsteam</p>',
  'Benachrichtigung über Aktualisierungen am Rückgewinnungsfall.',
  'case'
),

(
  'documents_required',
  'Dokumente angefordert',
  'Bitte reichen Sie fehlende Dokumente für Ihren Fall #{case_number} ein',
  '["first_name","last_name","case_number","required_documents","deadline","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>für die weitere Bearbeitung Ihres Rückgewinnungsfalls <strong>#{case_number}</strong> benötigen wir noch einige Dokumente von Ihnen.</p>
<div style="background:#fff3cd;border-left:4px solid #ffc107;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;">📁 Benötigte Dokumente</p>
  <p style="margin:8px 0 0;white-space:pre-line;">{required_documents}</p>
</div>
<p>Bitte laden Sie die angeforderten Dokumente bis zum <strong>{deadline}</strong> in Ihrem Konto hoch, um Verzögerungen in der Fallbearbeitung zu vermeiden.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/documents.php" style="background:linear-gradient(135deg,#fa8c16,#d46b08);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Dokumente hochladen &rarr;
  </a>
</p>
<p>Je schneller Sie die Dokumente einreichen, desto schneller können wir für Sie tätig werden.</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Rückgewinnungsteam</p>',
  'Anforderung fehlender Dokumente für einen Fall.',
  'case'
),

-- ─────────────────────────────────────────────────────────────────────────────
-- ACCOUNT / SECURITY
-- ─────────────────────────────────────────────────────────────────────────────
(
  'account_security_alert',
  'Sicherheitswarnung',
  '⚠ Sicherheitshinweis für Ihr {platform_name}-Konto',
  '["first_name","last_name","login_time","login_location","ip_address","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>wir haben eine Anmeldung an Ihrem <strong>{platform_name}</strong>-Konto festgestellt, die von einem ungewohnten Standort zu stammen scheint.</p>
<div style="background:#fff1f0;border-left:4px solid #ff4d4f;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;color:#cf1322;">🔐 Anmeldedetails</p>
  <ul style="margin:8px 0 0;padding-left:20px;">
    <li>Zeitpunkt: {login_time}</li>
    <li>Standort: {login_location}</li>
    <li>IP-Adresse: {ip_address}</li>
  </ul>
</div>
<p>Falls Sie diese Anmeldung selbst vorgenommen haben, müssen Sie nichts weiter tun.</p>
<p><strong>Falls Sie diese Anmeldung nicht kennen,</strong> empfehlen wir Ihnen dringend:</p>
<ol>
  <li>Ändern Sie sofort Ihr Passwort</li>
  <li>Aktivieren Sie die Zwei-Faktor-Authentifizierung</li>
  <li>Kontaktieren Sie unseren Support</li>
</ol>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/security.php" style="background:linear-gradient(135deg,#ff4d4f,#cf1322);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Sicherheitseinstellungen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Sicherheitsteam</p>',
  'Sicherheitswarnung bei ungewöhnlichen Anmeldeaktivitäten.',
  'security'
),

(
  'email_verification_reminder',
  'E-Mail-Verifizierungserinnerung',
  'Bitte verifizieren Sie Ihre E-Mail-Adresse für {platform_name}',
  '["first_name","last_name","verification_link","platform_name","site_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>Ihre E-Mail-Adresse für Ihr <strong>{platform_name}</strong>-Konto wurde noch nicht verifiziert.</p>
<p>Die E-Mail-Verifizierung ist wichtig für:</p>
<ul>
  <li>🔒 Die Sicherheit Ihres Kontos</li>
  <li>📬 Den Empfang wichtiger Benachrichtigungen</li>
  <li>💰 Die Durchführung von Auszahlungen</li>
  <li>📊 Zugang zu allen Kontofunktionen</li>
</ul>
<p style="text-align:center;margin:24px 0;">
  <a href="{verification_link}" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    E-Mail jetzt verifizieren &rarr;
  </a>
</p>
<p><em>Dieser Link ist 48 Stunden gültig.</em></p>
<p>Falls Sie sich nicht bei {platform_name} registriert haben, können Sie diese E-Mail ignorieren.</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Team</p>',
  'Erinnerung zur E-Mail-Verifizierung für neue Benutzer.',
  'account'
),

(
  'welcome_first_steps',
  'Willkommen – Erste Schritte',
  '🎉 Willkommen bei {platform_name} – Ihre Rückgewinnung beginnt jetzt',
  '["first_name","last_name","site_url","platform_name","onboarding_url"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>herzlich willkommen bei <strong>{platform_name}</strong>! Wir freuen uns, Sie als Teil unserer Gemeinschaft begrüßen zu dürfen.</p>
<p>Unser KI-gestütztes System steht Ihnen zur Seite, um verlorene Gelder wiederzugewinnen. Hier sind Ihre ersten Schritte:</p>
<div style="background:#f0f7ff;border-radius:8px;padding:20px;margin:16px 0;">
  <div style="display:flex;align-items:flex-start;margin-bottom:16px;">
    <span style="background:#1890ff;color:#fff;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;font-weight:700;margin-right:12px;flex-shrink:0;">1</span>
    <div><strong>Profil vervollständigen</strong><br><span style="color:#666;">Geben Sie Ihre persönlichen Daten ein, um Ihr Konto einzurichten.</span></div>
  </div>
  <div style="display:flex;align-items:flex-start;margin-bottom:16px;">
    <span style="background:#1890ff;color:#fff;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;font-weight:700;margin-right:12px;flex-shrink:0;">2</span>
    <div><strong>Schadensfall einreichen</strong><br><span style="color:#666;">Schildern Sie Ihren Schadensfall und laden Sie relevante Dokumente hoch.</span></div>
  </div>
  <div style="display:flex;align-items:flex-start;margin-bottom:16px;">
    <span style="background:#1890ff;color:#fff;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;font-weight:700;margin-right:12px;flex-shrink:0;">3</span>
    <div><strong>KYC abschließen</strong><br><span style="color:#666;">Verifizieren Sie Ihre Identität für eine vollständige Kontofreischaltung.</span></div>
  </div>
  <div style="display:flex;align-items:flex-start;">
    <span style="background:#52c41a;color:#fff;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;font-weight:700;margin-right:12px;flex-shrink:0;">4</span>
    <div><strong>KI-Analyse starten</strong><br><span style="color:#666;">Unsere KI beginnt sofort mit der Analyse Ihres Falls und entwickelt eine Strategie.</span></div>
  </div>
</div>
<p style="text-align:center;margin:24px 0;">
  <a href="{onboarding_url}" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:700;display:inline-block;font-size:16px;">
    Jetzt loslegen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Team</p>',
  'Willkommensnachricht für neue Benutzer mit Anleitung zu den ersten Schritten.',
  'onboarding'
),

(
  'quarterly_case_report',
  'Vierteljährlicher Fallbericht',
  '📊 Ihr vierteljährlicher Rückgewinnungsbericht – {quarter} {year}',
  '["first_name","last_name","quarter","year","cases_count","recovered_amount","currency","recovery_rate","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>hier ist Ihr persönlicher Rückgewinnungsbericht für <strong>{quarter} {year}</strong>.</p>
<div style="background:linear-gradient(135deg,#001529,#003a8c);color:#fff;border-radius:12px;padding:24px;margin:20px 0;">
  <p style="text-align:center;font-size:18px;font-weight:600;margin:0 0 20px;">{platform_name} Quartalsbericht</p>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;text-align:center;">
    <div style="background:rgba(255,255,255,0.1);border-radius:8px;padding:16px;">
      <p style="font-size:28px;font-weight:700;margin:0;">{cases_count}</p>
      <p style="font-size:12px;opacity:0.8;margin:4px 0 0;text-transform:uppercase;">Aktive Fälle</p>
    </div>
    <div style="background:rgba(255,255,255,0.1);border-radius:8px;padding:16px;">
      <p style="font-size:28px;font-weight:700;margin:0;">{recovery_rate}%</p>
      <p style="font-size:12px;opacity:0.8;margin:4px 0 0;text-transform:uppercase;">Erfolgsquote</p>
    </div>
    <div style="background:rgba(82,196,26,0.3);border-radius:8px;padding:16px;grid-column:1/-1;">
      <p style="font-size:32px;font-weight:700;margin:0;">{recovered_amount} {currency}</p>
      <p style="font-size:13px;opacity:0.9;margin:4px 0 0;text-transform:uppercase;">Zurückgewonnene Gelder</p>
    </div>
  </div>
</div>
<p>Unser Team und unsere KI arbeiten weiterhin intensiv daran, Ihre Rückgewinnung voranzutreiben. Wir werden Sie bei allen wichtigen Entwicklungen informieren.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/index.php" style="background:linear-gradient(135deg,#1890ff,#0050b3);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Vollständigen Bericht ansehen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Analyseteam</p>',
  'Vierteljährlicher Statusbericht für Benutzer mit aktiven Fällen.',
  'reporting'
),

(
  'withdrawal_pending_action',
  'Auszahlung: Aktion erforderlich',
  '⏳ Ihre Auszahlung benötigt Ihre Bestätigung',
  '["first_name","last_name","amount","currency","deadline","site_url","platform_name"]',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>Ihre Auszahlungsanfrage über <strong>{amount} {currency}</strong> wurde bearbeitet und wartet jetzt auf Ihre Bestätigung.</p>
<div style="background:#fff3cd;border-left:4px solid #ffc107;padding:16px;margin:16px 0;border-radius:4px;">
  <p style="margin:0;font-weight:600;">⚠ Bestätigung erforderlich bis: {deadline}</p>
  <p style="margin:8px 0 0;">Bitte melden Sie sich an und bestätigen Sie Ihre Auszahlung, um eine Stornierung zu vermeiden.</p>
</div>
<p>Aus Sicherheitsgründen werden nicht bestätigte Auszahlungsanfragen nach Ablauf der Frist automatisch storniert.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{site_url}/app/transactions.php" style="background:linear-gradient(135deg,#fa8c16,#d46b08);color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Auszahlung bestätigen &rarr;
  </a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {platform_name}-Finanzteam</p>',
  'Wird gesendet, wenn eine Auszahlung die Benutzerbestätigung benötigt.',
  'financial'
)

ON DUPLICATE KEY UPDATE
  `name`        = VALUES(`name`),
  `subject`     = VALUES(`subject`),
  `variables`   = VALUES(`variables`),
  `content`     = VALUES(`content`),
  `description` = VALUES(`description`),
  `category`    = VALUES(`category`),
  `updated_at`  = CURRENT_TIMESTAMP;
