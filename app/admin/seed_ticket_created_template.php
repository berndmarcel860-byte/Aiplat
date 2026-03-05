<?php
/**
 * Seed: ticket_created Email Template
 *
 * Run this script once (e.g., via CLI or browser with admin session) to insert
 * the German "Ticket erstellt" notification template into the email_templates table.
 *
 * Usage:  php seed_ticket_created_template.php
 */

require_once __DIR__ . '/../config.php';

$templateKey = 'ticket_created';
$subject     = 'Ihr Support-Ticket wurde erstellt – {ticket_number}';

$content = <<<'HTML'
<p>Sehr geehrte/r {first_name} {last_name},</p>

<p>
  vielen Dank für Ihre Kontaktaufnahme. Ihr Support-Ticket wurde erfolgreich
  in unserem System registriert.
</p>

<p>
  Unser <strong>KI-Algorithmus</strong> sowie unser Support-Team werden Ihr
  Anliegen schnellstmöglich analysieren und sich bei Ihnen melden.
</p>

<div class="highlight-box">
  <h3>🎫 Ticket-Details</h3>
  <p><strong>Ticket-Nummer:</strong> {ticket_number}</p>
  <p><strong>Betreff:</strong> {ticket_subject}</p>
  <p><strong>Kategorie:</strong> {ticket_category}</p>
  <p><strong>Priorität:</strong> {ticket_priority}</p>
  <p><strong>Status:</strong> Offen</p>
</div>

<p>
  Sie werden über jeden weiteren Bearbeitungsschritt automatisch informiert,
  sobald neue Ergebnisse oder Statusänderungen vorliegen.
</p>

<p>
  Den aktuellen Status Ihres Tickets können Sie jederzeit in Ihrem
  <strong>Kundenportal</strong> einsehen und dort weitere Nachrichten oder
  Dokumente hinzufügen.
</p>

<p><a href="{site_url}/app/support.php" class="btn">Zum Kundenportal</a></p>
HTML;

$variables = json_encode([
    'first_name',
    'last_name',
    'ticket_number',
    'ticket_subject',
    'ticket_category',
    'ticket_priority',
    'site_url',
]);

try {
    // Check if template already exists
    $stmt = $pdo->prepare("SELECT id FROM email_templates WHERE template_key = ?");
    $stmt->execute([$templateKey]);

    if ($stmt->fetch()) {
        echo "Template '$templateKey' already exists – skipping insert.\n";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO email_templates (template_key, subject, content, variables, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$templateKey, $subject, $content, $variables]);
        echo "Template '$templateKey' inserted successfully (ID: " . $pdo->lastInsertId() . ").\n";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
