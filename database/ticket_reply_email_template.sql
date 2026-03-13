-- Migration: ticket_reply email template
-- Inserts the German "Ticket Antwort" notification template that is sent to
-- users when an admin replies to their support ticket.
-- EmailHelper::sendTicketReplyEmail() fetches this template, substitutes the
-- listed variables, and wraps the content in wrapInTemplate() before sending.
-- Run once per environment.  Uses ON DUPLICATE KEY UPDATE so it is safe to
-- re-run (it will refresh the content if it already exists).

INSERT INTO email_templates
    (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'ticket_reply',
    'Neue Antwort zu Ihrem Ticket {ticket_number}',
    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  unser Support-Team hat eine neue Antwort zu Ihrem Support-Ticket hinzugefügt.
</p>

<div class="highlight-box">
  <h3>&#127931; Ticket-Details</h3>
  <p><strong>Ticket-Nummer:</strong> {ticket_number}</p>
  <p><strong>Betreff:</strong> {ticket_subject}</p>
  <p><strong>Status:</strong> {ticket_status}</p>
</div>

<div class="highlight-box">
  <h3>&#128172; Antwort des Support-Teams</h3>
  <p>{reply_message}</p>
</div>

<p>
  Sie können die vollständige Konversation einsehen und antworten, indem Sie
  Ihr <strong>Kundenportal</strong> besuchen.
</p>

<p><a href="{site_url}/app/support.php" class="btn">Ticket ansehen</a></p>',
    '["first_name","last_name","ticket_number","ticket_subject","ticket_status","reply_message","site_url"]',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    subject    = VALUES(subject),
    content    = VALUES(content),
    variables  = VALUES(variables),
    updated_at = NOW();
