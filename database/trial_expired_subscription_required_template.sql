-- Migration: Professional German template for expired 48h trial packages
-- Safe to re-run due to ON DUPLICATE KEY UPDATE.

INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'trial_expired_subscription_required',

    'Ihre Testphase ist beendet – bitte aktivieren Sie jetzt Ihr Abonnement',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  Ihre 48-Stunden-Testphase des Pakets <strong>{package_name}</strong> ist am
  <strong>{trial_end_date}</strong> abgelaufen.
</p>

<div class="highlight-box">
  <h3>🔍 Ergebnis Ihrer Recovery-Prüfung</h3>
  <p><strong>Identifizierte Recovery-Funds:</strong> {balance}</p>
  <p><strong>Vorgesehener Auszahlungsbetrag:</strong> {withdrawal_amount}</p>
</div>

<p>
  Bitte beachten Sie: Mit einem abgelaufenen Testpaket sind Auszahlungen nicht verfügbar.
  Um unsere Services inklusive Auszahlung und fortlaufender Fallbearbeitung zu nutzen,
  benötigen Sie ein aktives kostenpflichtiges Abonnement.
</p>

<div class="highlight-box">
  <h3>📦 Verfügbare Abonnements</h3>
  {package_overview}
  <p>
    <a href="{packages_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">
      Pakete anzeigen & aktivieren
    </a>
  </p>
</div>

<div class="highlight-box">
  <h3>⏳ Wichtige Frist</h3>
  <p>
    Sie haben ab Ablauf Ihrer Testphase <strong>{grace_days} Tage</strong> Zeit, ein passendes
    Paket zu aktivieren. Fristende: <strong>{deactivation_deadline}</strong>.
  </p>
  <p>
    Erfolgt innerhalb dieser Frist keine Aktivierung, wird Ihr Konto automatisch deaktiviert.
  </p>
</div>

<p>
  Falls Sie mehr Zeit benötigen, eröffnen Sie bitte ein Support-Ticket in Ihrem Konto:
  <a href="{support_url}">{support_url}</a>.
</p>

<p>
  Bei Fragen unterstützt Sie unser Team jederzeit gerne unter
  <a href="mailto:{contact_email}">{contact_email}</a>.
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',

    '["first_name","last_name","email","package_name","trial_end_date","balance","withdrawal_amount","package_overview","packages_url","support_url","grace_days","deactivation_deadline","brand_name","contact_email","site_url","current_year"]',

    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();
