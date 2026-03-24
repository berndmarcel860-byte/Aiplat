-- Seed the login_otp email template used by EmailHelper::sendLoginOtpEmail().
-- This is a safe INSERT IGNORE so it can be run multiple times without error.

INSERT IGNORE INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'login_otp',
    'Ihr Anmeldecode für {brand_name}',
    '<p>Hallo {first_name},</p>\n\n<p>\n  Verwenden Sie diesen Code, um sich bei Ihrem Konto anzumelden:\n</p>\n\n<div class="highlight-box" style="text-align:center;">\n  <h2 style="font-size:36px;font-weight:bold;letter-spacing:10px;color:#2950a8;">{otp_code}</h2>\n</div>\n\n<div class="highlight-box">\n  <p>\n    <strong>⏱️ Gültigkeit:</strong> Dieser Code ist {otp_expires_minutes} Minuten gültig.\n  </p>\n  <p>\n    <strong>🔒 Sicherheit:</strong> Teilen Sie diesen Code niemals mit anderen.\n  </p>\n</div>\n\n<p>\n  Wenn Sie sich nicht angemeldet haben, ignorieren Sie diese E-Mail bitte.\n</p>',
    '["first_name","last_name","email","brand_name","otp_code","otp_expires_minutes","site_url"]',
    NOW(),
    NOW()
);
