-- ============================================================
-- Trust & Professional Features Migration
-- Features: session activity log, live chat, IP login alerts
-- ============================================================

-- 1. Add user_id column to login_logs (for per-user session history)
ALTER TABLE login_logs
    ADD COLUMN IF NOT EXISTS user_id INT NULL DEFAULT NULL AFTER id,
    ADD COLUMN IF NOT EXISTS city VARCHAR(100) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS country VARCHAR(10) NULL DEFAULT NULL;

ALTER TABLE login_logs
    ADD INDEX IF NOT EXISTS idx_login_logs_user_id (user_id);

-- 2. Add live_chat_code column to system_settings
ALTER TABLE system_settings
    ADD COLUMN IF NOT EXISTS live_chat_code TEXT NULL DEFAULT NULL;

-- 3. Email template: IP / new-device login alert
INSERT INTO email_templates (template_key, subject, content, created_at, updated_at)
VALUES (
    'new_ip_login_alert',
    'Sicherheitshinweis: Neues Gerät / neue IP erkannt – {brand_name}',
    '<p>Hallo {first_name},</p>
<p>Wir haben eine Anmeldung in Ihrem Konto von einer <strong>neuen IP-Adresse</strong> oder einem neuen Gerät registriert.</p>
<table style="border-collapse:collapse;width:100%;margin:16px 0;">
  <tr><td style="padding:8px;background:#f8f9fa;font-weight:600;width:40%;">IP-Adresse</td><td style="padding:8px;">{login_ip}</td></tr>
  <tr><td style="padding:8px;background:#f8f9fa;font-weight:600;">Datum &amp; Uhrzeit</td><td style="padding:8px;">{login_time}</td></tr>
  <tr><td style="padding:8px;background:#f8f9fa;font-weight:600;">Browser / Gerät</td><td style="padding:8px;">{login_device}</td></tr>
</table>
<p>Wenn Sie diese Anmeldung nicht durchgeführt haben, sichern Sie bitte sofort Ihr Konto und kontaktieren Sie unseren Support unter <a href="mailto:{contact_email}">{contact_email}</a>.</p>
<p style="margin-top:20px;"><a href="{site_url}/security.php" style="background:#2950a8;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:600;">Aktivitätslog ansehen</a></p>
<p>Falls Sie diese Anmeldung selbst durchgeführt haben, können Sie diese E-Mail ignorieren.</p>',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject  = VALUES(subject),
    content  = VALUES(content),
    updated_at = NOW();
