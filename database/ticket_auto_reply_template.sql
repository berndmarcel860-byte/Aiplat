-- Migration: support ticket first auto-reply email template

INSERT INTO email_templates
    (template_key, subject, content, variables, is_active, created_at, updated_at)
VALUES
(
    'ticket_auto_reply',
    'Update zu Ihrem Support-Ticket {ticket_number}',
    '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>

<p>vielen Dank für Ihre Nachricht. Ihr Ticket wurde erfolgreich erfasst und wird aktuell bearbeitet.</p>

<div class="highlight-box">
  <h3>Aktueller Hinweis vom Support</h3>
  <p>{reply_message}</p>
</div>

<div class="highlight-box">
  <h3>Ticket-Informationen</h3>
  <p><strong>Ticket-Nummer:</strong> {ticket_number}</p>
  <p><strong>Betreff:</strong> {ticket_subject}</p>
  <p><strong>Status:</strong> {ticket_status}</p>
</div>

<p>Sie erhalten automatisch eine weitere Benachrichtigung, sobald eine individuelle Rückmeldung vorliegt.</p>

<p style="text-align:center;margin:26px 0;">
  <a href="{support_url}" class="btn">Ticket im Portal öffnen</a>
</p>',
    '["first_name","last_name","ticket_number","ticket_subject","ticket_status","reply_message","support_url","site_url"]',
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

