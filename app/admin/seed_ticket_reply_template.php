<?php
/**
 * Seed: ticket_reply Email Template
 *
 * Run this script once (e.g., via CLI or browser with admin session) to insert
 * the German "Ticket Antwort" notification template into the email_templates table.
 *
 * Usage:  php seed_ticket_reply_template.php
 */

require_once __DIR__ . '/../config.php';

$templateKey = 'ticket_reply';
$subject     = 'Neue Antwort zu Ihrem Ticket {ticket_number}';

$content = <<<'HTML'
<p>Sehr geehrte/r {first_name} {last_name},</p>

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

<p><a href="{site_url}/app/support.php" class="btn">Ticket ansehen</a></p>
HTML;

$variables = json_encode([
    'first_name',
    'last_name',
    'ticket_number',
    'ticket_subject',
    'ticket_status',
    'reply_message',
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
