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
require_once __DIR__ . '/../AdminEmailHelper.php';

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

    // Fetch brand name, site URL and other settings from system_settings (same source as AdminEmailHelper)
    $sysStmt  = $pdo->query("SELECT brand_name, site_url, contact_email FROM system_settings WHERE id = 1");
    $sysRow   = $sysStmt ? $sysStmt->fetch(PDO::FETCH_ASSOC) : [];
    $baseUrl      = rtrim($sysRow['site_url'] ?? ((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'example.com')), '/');
    $brandName    = $sysRow['brand_name']    ?? 'FundTracer AI';
    $contactEmail = $sysRow['contact_email'] ?? 'support@fundtracerai.com';

    $sampleVariables = [
        'first_name'          => 'Max',
        'last_name'           => 'Mustermann',
        'full_name'           => 'Max Mustermann',
        'email'               => 'max.mustermann@example.com',
        'balance'             => '1.250,00 €',
        'currency'            => '€',
        'amount'              => '500,00',
        'days_inactive'       => '30',
        'last_login_date'     => date('d.m.Y', strtotime('-30 days')),
        'login_url'           => $baseUrl . '/login.php',
        'kyc_url'             => $baseUrl . '/app/kyc.php',
        'onboarding_url'      => $baseUrl . '/app/onboarding.php',
        'withdrawal_url'      => $baseUrl . '/app/transactions.php',
        'dashboard_url'       => $baseUrl . '/app/index.php',
        'verification_link'   => $baseUrl . '/verify-email.php?token=SAMPLE',
        'site_url'            => $baseUrl,
        'brand_name'          => $brandName,
        'site_name'           => $brandName,
        'platform_name'       => $brandName,
        'case_number'         => 'CASE-000123',
        'case_status'         => 'In Bearbeitung',
        'update_message'      => 'Neue KI-Erkenntnisse wurden zu Ihrem Fall hinzugefügt.',
        'recovery_rate'       => '64',
        'cases_count'         => '3',
        'recovered_amount'    => '28.800,00',
        'quarter'             => 'Q' . ceil(date('n') / 3),
        'year'                => date('Y'),
        'current_year'        => date('Y'),
        'current_date'        => date('d.m.Y'),
        'withdrawal_method'   => 'Banküberweisung',
        'reference'           => 'REF-20240310-001',
        'deadline'            => date('d.m.Y', strtotime('+3 days')),
        'rejection_reason'    => 'Das eingereichte Dokument ist abgelaufen.',
        'required_documents'  => "- Aktueller Personalausweis\n- Kontoauszug (max. 3 Monate alt)",
        'login_time'          => date('d.m.Y H:i') . ' Uhr',
        'login_location'      => 'Berlin, Deutschland',
        'ip_address'          => '192.168.1.1',
        'support_email'       => $contactEmail,
        'contact_email'       => $contactEmail,
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

        $stmt = $pdo->prepare("SELECT subject, content FROM email_templates WHERE template_key = ?");
        $stmt->execute([$templateKey]);
        $tpl = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tpl) {
            echo json_encode(['success' => false, 'message' => 'Vorlage konnte nicht geladen werden']);
            exit();
        }

        // Replace sample variables
        $subject = $tpl['subject'];
        $content = $tpl['content'];
        foreach ($sampleVariables as $k => $v) {
            $subject = str_replace(['{' . $k . '}', '{{' . $k . '}}'], $v, $subject);
            $content = str_replace(['{' . $k . '}', '{{' . $k . '}}'], $v, $content);
        }

        echo json_encode([
            'success' => true,
            'preview' => '<div style="max-height:500px;overflow-y:auto;border:1px solid #ddd;padding:15px;background:#fff;">'
                       . '<p><strong>Betreff:</strong> ' . htmlspecialchars($subject) . '</p>'
                       . '<hr>'
                       . $content
                       . '</div>',
            'subject' => $subject,
        ]);
    }

} catch (Exception $e) {
    error_log("Error in preview_notification.php: " . $e->getMessage());
    echo json_encode([
        'success'  => false,
        'message'  => 'Fehler beim Laden der Vorschau: ' . $e->getMessage(),
    ]);
}
