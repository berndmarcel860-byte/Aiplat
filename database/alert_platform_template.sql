-- Seed the alert_platform email template.
-- This professional German template is sent to all active users when an admin
-- adds a new platform to the scam list.
-- Safe to run multiple times (INSERT IGNORE).

INSERT IGNORE INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'alert_platform',

    '⚠️ Wichtige Sicherheitswarnung: {platform_name}',

    '<p>Hallo {first_name},</p>

<p>
    wir möchten Sie über eine wichtige Sicherheitswarnung informieren,
    die Ihren Schutz als Kunde betrifft.
</p>

<div class="highlight-box" style="background:#fff3cd;border-left:5px solid #ffc107;padding:20px;border-radius:6px;margin:20px 0;">
    <h3 style="margin-top:0;color:#856404;">⚠️ BETRUGSMELDUNG</h3>
    <p style="margin:0;">
        Die Plattform <strong>{platform_name}</strong> wurde offiziell als
        betrügerische Handelsplattform eingestuft und abgeschaltet.
        Bitte seien Sie äußerst vorsichtig und nehmen Sie <strong>keinen weiteren
        Kontakt</strong> mit dieser Plattform auf.
    </p>
</div>

<p><strong>Details zur gemeldeten Plattform:</strong></p>
<ul>
    <li><strong>Name:</strong> {platform_name}</li>
    <li><strong>Kategorie:</strong> {platform_type}</li>
    {platform_url_line}
</ul>

<p>
    Wenn Sie in der Vergangenheit Gelder an <strong>{platform_name}</strong>
    überwiesen haben oder dort investiert waren, empfehlen wir Ihnen dringend,
    umgehend zu handeln, um Ihre Verluste zu minimieren.
</p>

<div class="highlight-box" style="background:#d1ecf1;border-left:5px solid #0c5460;padding:20px;border-radius:6px;margin:20px 0;">
    <h3 style="margin-top:0;color:#0c5460;">🛡️ Wir helfen Ihnen</h3>
    <p>
        Unser Team steht Ihnen zur Seite, um Ihre verlorenen Gelder
        zurückzufordern. Öffnen Sie noch heute einen Fall bei uns –
        vertraulich, professionell und ohne Verpflichtungen.
    </p>
    <p style="margin-bottom:0;">
        <a class="btn" href="{dashboard_url}"
           style="display:inline-block;background:#007bff;color:#fff;
                  padding:10px 20px;border-radius:5px;text-decoration:none;
                  font-weight:bold;margin-top:10px;">
            Fall eröffnen
        </a>
    </p>
</div>

<p>
    Bitte leiten Sie diese Warnung auch an Freunde oder Bekannte weiter,
    die möglicherweise betroffen sein könnten.
</p>

<p>
    Bei Fragen stehen wir Ihnen jederzeit unter
    <a href="mailto:{contact_email}">{contact_email}</a> zur Verfügung.
</p>

<p>
    Mit freundlichen Grüßen,<br>
    <strong>Das Sicherheitsteam von {brand_name}</strong>
</p>',

    '["first_name","last_name","email","brand_name","contact_email","dashboard_url","platform_name","platform_type","platform_url","platform_url_line","current_date","current_year"]',

    NOW(),
    NOW()
);
