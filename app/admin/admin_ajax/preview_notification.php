<?php
/**
 * Preview Notification Email
 * Show preview of email template with sample data.
 * Supports both:
 *   - "notif:<notification_key>" → email_notifications table
 *   - "tpl:<template_key>"       → email_templates table (legacy)
 *   - bare key (legacy compat)   → email_templates table
 */
require_once '../admin_session.php';
require_once '../email_template_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $rawKey = $_POST['template_key'] ?? '';

    if (empty($rawKey)) {
        echo json_encode(['success' => false, 'message' => 'Keine Vorlage ausgewählt']);
        exit();
    }

    // Sample variables shared by all templates
    $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'example.com');
    $sampleVariables = [
        'first_name'          => 'Max',
        'last_name'           => 'Mustermann',
        'email'               => 'max.mustermann@example.com',
        'balance'             => '1.250,00',
        'currency'            => '€',
        'amount'              => '500,00',
        'days_inactive'       => '30',
        'last_login_date'     => date('d.m.Y', strtotime('-30 days')),
        'login_url'           => $baseUrl . '/login.php',
        'kyc_url'             => $baseUrl . '/app/kyc.php',
        'onboarding_url'      => $baseUrl . '/app/onboarding.php',
        'withdrawal_url'      => $baseUrl . '/app/transactions.php',
        'verification_link'   => $baseUrl . '/verify-email.php?token=SAMPLE',
        'site_url'            => $baseUrl,
        'platform_name'       => 'FundTracer AI',
        'case_number'         => 'CASE-000123',
        'case_status'         => 'In Bearbeitung',
        'update_message'      => 'Neue KI-Erkenntnisse wurden zu Ihrem Fall hinzugefügt.',
        'recovery_rate'       => '64',
        'cases_count'         => '3',
        'recovered_amount'    => '28.800,00',
        'quarter'             => 'Q1',
        'year'                => date('Y'),
        'withdrawal_method'   => 'Banküberweisung',
        'reference'           => 'REF-20240310-001',
        'deadline'            => date('d.m.Y', strtotime('+3 days')),
        'rejection_reason'    => 'Das eingereichte Dokument ist abgelaufen.',
        'required_documents'  => "- Aktueller Personalausweis\n- Kontoauszug (max. 3 Monate alt)",
        'login_time'          => date('d.m.Y H:i') . ' Uhr',
        'login_location'      => 'Berlin, Deutschland',
        'ip_address'          => '192.168.1.1',
        'support_email'       => 'support@fundtracerai.com',
    ];

    // Detect source table
    if (str_starts_with($rawKey, 'notif:')) {
        // ── email_notifications table ─────────────────────────────────────
        $notificationKey = substr($rawKey, 6);

        $stmt = $pdo->prepare("
            SELECT notification_key, name, subject, content, variables
            FROM email_notifications
            WHERE notification_key = ? AND is_active = 1
        ");
        $stmt->execute([$notificationKey]);
        $notif = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$notif) {
            echo json_encode(['success' => false, 'message' => 'Benachrichtigungsvorlage nicht gefunden']);
            exit();
        }

        // Replace variables in subject and content
        $subject = $notif['subject'];
        $content = $notif['content'];
        foreach ($sampleVariables as $k => $v) {
            $subject = str_replace('{' . $k . '}', $v, $subject);
            $content = str_replace('{' . $k . '}', $v, $content);
        }

        echo json_encode([
            'success' => true,
            'preview' => '<div style="max-height:500px;overflow-y:auto;border:1px solid #ddd;padding:15px;background:#fff;">'
                       . '<p><strong>Betreff:</strong> ' . htmlspecialchars($subject) . '</p>'
                       . '<p><strong>Vorlage:</strong> ' . htmlspecialchars($notif['name']) . '</p>'
                       . '<hr>'
                       . $content
                       . '</div>',
            'subject' => $subject,
        ]);

    } else {
        // ── email_templates table (legacy) ────────────────────────────────
        $templateKey = str_starts_with($rawKey, 'tpl:') ? substr($rawKey, 4) : $rawKey;

        $emailHelper = new EmailTemplateHelper($pdo);
        $rendered    = $emailHelper->renderTemplate($templateKey, $sampleVariables);

        if (!$rendered) {
            echo json_encode(['success' => false, 'message' => 'Vorlage konnte nicht geladen werden']);
            exit();
        }

        echo json_encode([
            'success' => true,
            'preview' => '<div style="max-height:500px;overflow-y:auto;border:1px solid #ddd;padding:15px;background:#fff;">'
                       . '<p><strong>Betreff:</strong> ' . htmlspecialchars($rendered['subject']) . '</p>'
                       . '<hr>'
                       . $rendered['content']
                       . '</div>',
            'subject' => $rendered['subject'],
        ]);
    }

} catch (Exception $e) {
    error_log("Error in preview_notification.php: " . $e->getMessage());
    echo json_encode([
        'success'  => false,
        'message'  => 'Fehler beim Laden der Vorschau: ' . $e->getMessage(),
    ]);
}
