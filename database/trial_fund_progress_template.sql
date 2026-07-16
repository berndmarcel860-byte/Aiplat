-- Migration: trial fund progress email template
-- Sends staged 48-hour algorithm progress updates.

INSERT INTO email_templates
    (template_key, subject, content, variables, is_active, created_at, updated_at)
VALUES
(
    'trial_fund_progress',
    'Algorithm update: {progress_percent}% processing completed',
    '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>

<p>unsere Analyse Ihres Vorgangs läuft aktiv weiter und wir informieren Sie über den aktuellen Zwischenstand.</p>

<div class="highlight-box">
  <h3>Fortschritt Ihrer Analyse</h3>
  <p><strong>{progress_percent}%</strong> der gemeldeten Beträge wurden aktuell nachverfolgt.</p>
  <p>Seit Start sind etwa <strong>{hours_since_start} Stunden</strong> vergangen.</p>
  <p>Aktive Fälle in diesem Lauf: <strong>{case_count}</strong></p>
  <p>Gemeldete Gesamtsumme: <strong>{total_reported_amount}</strong></p>
</div>

<p>Unser System arbeitet kontinuierlich weiter. Neue Ergebnisse werden automatisch in Ihrem Portal angezeigt.</p>

<p style="text-align:center;margin:26px 0;">
  <a href="{cases_url}" class="btn">Fortschritt im Portal ansehen</a>
</p>',
    '["first_name","last_name","progress_percent","hours_since_start","case_count","total_reported_amount","cases_url","dashboard_url"]',
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject = VALUES(subject),
    content = VALUES(content),
    variables = VALUES(variables),
    is_active = VALUES(is_active),
    updated_at = VALUES(updated_at);

