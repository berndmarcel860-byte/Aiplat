-- Migration: package expiry cron support
-- Adds warning_sent_at to user_packages so the cron job tracks whether
-- the "expiring soon" email has already been sent for each package assignment.

ALTER TABLE `user_packages`
    ADD COLUMN IF NOT EXISTS `warning_sent_at` TIMESTAMP NULL DEFAULT NULL
        COMMENT 'Timestamp when the "expiring soon" email was dispatched';

-- ─────────────────────────────────────────────────────────────────────────────
-- Email templates used by the cron job (German)
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO `email_templates` (`template_key`, `subject`, `content`, `variables`, `created_at`, `updated_at`)
VALUES
(
  'trial_expiring_soon',
  'Ihr {package_name} läuft bald ab – {hours_left} Stunden verbleiben',
  '<p>Hallo {first_name},</p>

<p>Ihr aktuelles Paket <strong>{package_name}</strong> läuft in ungefähr <strong>{hours_left} Stunden</strong> ab
(am <strong>{end_date}</strong>).</p>

<div class="highlight-box">
  <h3>⚠️ Wichtiger Hinweis</h3>
  <p>Nach Ablauf Ihres Test-Paketes werden folgende Funktionen eingeschränkt:</p>
  <ul>
    <li>🔒 Keine Auszahlungen möglich</li>
    <li>🔒 Nur begrenzte Fallübersicht (bis zu 2 Fälle sichtbar)</li>
    <li>🔒 Rückgewonnene Beträge über €100.000 werden nicht angezeigt</li>
  </ul>
</div>

<p>Unser Algorithmus hat bereits <strong>{recovered_display}</strong> für Sie identifiziert.
Upgraden Sie jetzt, um vollen Zugang zu erhalten und Ihre Gelder auszahlen zu lassen.</p>

<div style="text-align:center;margin:30px 0;">
  <a href="{packages_url}" class="btn" style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;padding:14px 32px;border-radius:8px;font-weight:700;text-decoration:none;font-size:16px;">
    🚀 Jetzt Paket upgraden
  </a>
</div>

<p>Bei Fragen stehen wir Ihnen jederzeit unter <a href="mailto:{contact_email}">{contact_email}</a> zur Verfügung.</p>

<p>Mit freundlichen Grüßen,<br>
Ihr {brand_name}-Team</p>',
  '["first_name","last_name","package_name","hours_left","end_date","recovered_display","packages_url","brand_name","contact_email","site_url"]',
  NOW(),
  NOW()
),
(
  'trial_expired',
  'Ihr Test-Paket ist abgelaufen – Weitere Gelder warten auf Sie',
  '<p>Hallo {first_name},</p>

<p>Ihr <strong>{package_name}</strong> ist am <strong>{end_date}</strong> abgelaufen.</p>

<div class="highlight-box">
  <h3>📊 Ihr Wiederherstellungsstatus</h3>
  <p>Unser leistungsstarker Algorithmus hat <strong>{recovered_display}</strong> für Sie zurückgewonnen –
  doch um Ihre Gelder auszahlen zu lassen und alle Ergebnisse zu sehen,
  benötigen Sie ein aktives Abonnement.</p>
</div>

<p><strong>Empfohlenes Paket basierend auf Ihrem Verlust von {reported_amount}:</strong></p>

<div class="highlight-box" style="border-left-color:#28a745;">
  <h3 style="color:#28a745;">⭐ {recommended_package_name}</h3>
  <p><strong>Preis:</strong> {recommended_package_price}</p>
  <p>{recommended_package_description}</p>
</div>

<div style="text-align:center;margin:30px 0;">
  <a href="{packages_url}" class="btn" style="background:linear-gradient(135deg,#28a745,#20c997);color:#fff;padding:14px 32px;border-radius:8px;font-weight:700;text-decoration:none;font-size:16px;">
    💎 Paket jetzt abonnieren
  </a>
</div>

<p>Handeln Sie jetzt – Ihre zurückgewonnenen Gelder warten auf die Auszahlung.</p>

<p>Mit freundlichen Grüßen,<br>
Ihr {brand_name}-Team</p>',
  '["first_name","last_name","package_name","end_date","recovered_display","reported_amount","recommended_package_name","recommended_package_price","recommended_package_description","packages_url","brand_name","contact_email","site_url"]',
  NOW(),
  NOW()
)
ON DUPLICATE KEY UPDATE
  `subject`   = VALUES(`subject`),
  `content`   = VALUES(`content`),
  `variables` = VALUES(`variables`),
  `updated_at` = NOW();
