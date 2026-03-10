-- Seed the register_request email template.
-- Sent automatically to every visitor who submits the contact/registration
-- modal on the public website.  The email is written in professional German
-- and thanks the applicant for their inquiry, confirming that we have
-- received their data and will be in touch shortly.
-- Safe to run multiple times (INSERT IGNORE).

INSERT IGNORE INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
VALUES (
    'register_request',

    'Vielen Dank für Ihre Anfrage – {brand_name}',

    '<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
    vielen Dank für Ihre Kontaktaufnahme und das entgegengebrachte Vertrauen.
    Wir haben Ihre Anfrage erfolgreich erhalten und bestätigen den Eingang
    Ihrer Informationen.
</p>

<div class="highlight-box">
    <h3>&#10003; Ihre Anfrage ist bei uns eingegangen</h3>
    <p>
        Unser Expertenteam wird Ihre Angaben sorgfältig prüfen und sich
        <strong>so schnell wie möglich</strong> bei Ihnen melden, um die
        nächsten Schritte zu besprechen.
    </p>
</div>

<p><strong>Zusammenfassung Ihrer übermittelten Angaben:</strong></p>
<ul>
    <li><strong>Name:</strong> {first_name} {last_name}</li>
    <li><strong>E-Mail:</strong> {request_email}</li>
    <li><strong>Telefon:</strong> {phone}</li>
    <li><strong>Geschätzter Verlustbetrag:</strong> {amount} €</li>
    <li><strong>Jahr des Verlusts:</strong> {year}</li>
    <li><strong>Betroffene Plattformen:</strong> {platforms}</li>
</ul>

<div class="highlight-box">
    <h3>&#128221; Ihre Schilderung</h3>
    <p>{details}</p>
</div>

<p>
    Falls Sie in der Zwischenzeit Fragen haben oder weitere Informationen
    bereitstellen möchten, stehen wir Ihnen jederzeit unter
    <a href="mailto:{contact_email}">{contact_email}</a> zur Verfügung.
</p>

<p>
    Wir danken Ihnen für Ihr Vertrauen und freuen uns darauf, Ihnen bei der
    Rückforderung Ihrer verlorenen Gelder behilflich zu sein.
</p>

<p>
    Mit freundlichen Grüßen,<br>
    <strong>Das Beratungsteam von {brand_name}</strong>
</p>',

    '["first_name","last_name","request_email","phone","amount","year","platforms","details","brand_name","contact_email","site_url","current_date","current_year"]',

    NOW(),
    NOW()
);
