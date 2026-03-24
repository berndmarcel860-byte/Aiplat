<?php
// admin_ajax/mailer_templates.php — CRUD for email templates
require_once '../admin_session.php';
require_once '../mailer_db.php';
header('Content-Type: application/json');
if (!is_admin_logged_in()) { echo json_encode(['ok' => false, 'message' => 'Unauthorized']); exit; }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/**
 * Basic spam-word filter — returns an array of detected trigger words.
 * The list targets common English & German spam triggers.
 */
function detectSpamWords(string $text): array
{
    $triggers = [
        // English
        'click here','act now','limited time','100% free','you won','congratulations',
        'free gift','guaranteed','no obligation','winner','claim your prize',
        'special promotion','earn money fast','work from home','extra income',
        'lose weight','miracle','risk-free','no risk','order now','buy now',
        'discount','cash bonus','credit card required','free access','free membership',
        'be your own boss','double your','earn per week','million dollars',
        'unsecured debt','pre-approved','dear friend','this is not spam','bulk email',
        // German equivalents
        'kostenlos klicken','jetzt handeln','begrenzte zeit','100% kostenlos','sie haben gewonnen',
        'glückwunsch','gratis geschenk','garantiert','keine verpflichtung','gewinner',
        'sonderaktion','schnell geld verdienen','heimarbeit','extra einkommen',
        'abnehmen','wundermittel','risikolos','jetzt bestellen','jetzt kaufen',
        'rabatt','cashbonus','kreditkarte erforderlich','gratiszugang',
    ];

    $lc      = strtolower($text);
    $found   = [];
    foreach ($triggers as $word) {
        if (str_contains($lc, $word)) {
            $found[] = $word;
        }
    }
    return array_unique($found);
}

try {
    switch ($action) {

        case 'list':
            $rows = $mailerPdo->query(
                "SELECT id, name, subject, is_default, updated_at FROM mailer_templates ORDER BY id DESC"
            )->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'templates' => $rows]);
            break;

        case 'list_short':
            $rows = $mailerPdo->query("SELECT id, name FROM mailer_templates ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'templates' => $rows]);
            break;

        case 'get':
            $id   = (int)($_GET['id'] ?? 0);
            $stmt = $mailerPdo->prepare("SELECT id,name,subject,html_body,is_default FROM mailer_templates WHERE id=?");
            $stmt->execute([$id]);
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { echo json_encode(['ok' => false, 'message' => 'Not found']); break; }
            echo json_encode(['ok' => true, 'template' => $row]);
            break;

        case 'create':
        case 'update':
            $id        = (int)($_POST['id'] ?? 0);
            $name      = trim($_POST['name']      ?? '');
            $subject   = trim($_POST['subject']   ?? '');
            $htmlBody  = trim($_POST['html_body'] ?? '');
            $isDefault = (int)($_POST['is_default'] ?? 0);

            if (!$name || !$subject || !$htmlBody) {
                echo json_encode(['ok' => false, 'message' => 'Name, subject and HTML body are required.']);
                break;
            }

            // Spam-word check
            $spamWords = detectSpamWords($subject . ' ' . $htmlBody);
            if (!empty($spamWords)) {
                echo json_encode([
                    'ok'      => false,
                    'message' => 'Spam trigger words detected — please remove them before saving: ' . implode(', ', $spamWords),
                    'spam'    => $spamWords,
                ]);
                break;
            }

            if ($isDefault) {
                $mailerPdo->exec("UPDATE mailer_templates SET is_default=0");
            }

            if ($action === 'create') {
                $mailerPdo->prepare(
                    "INSERT INTO mailer_templates (name,subject,html_body,is_default) VALUES (?,?,?,?)"
                )->execute([$name, $subject, $htmlBody, $isDefault]);
                echo json_encode(['ok' => true, 'message' => 'Template saved.']);
            } else {
                $mailerPdo->prepare(
                    "UPDATE mailer_templates SET name=?,subject=?,html_body=?,is_default=? WHERE id=?"
                )->execute([$name, $subject, $htmlBody, $isDefault, $id]);
                echo json_encode(['ok' => true, 'message' => 'Template updated.']);
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $mailerPdo->prepare("DELETE FROM mailer_templates WHERE id=?")->execute([$id]);
            echo json_encode(['ok' => true, 'message' => 'Template deleted.']);
            break;

        default:
            echo json_encode(['ok' => false, 'message' => 'Unknown action.']);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
