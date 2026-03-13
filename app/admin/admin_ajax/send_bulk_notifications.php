<?php
/**
 * Send Bulk Notifications
 * Send emails to multiple users using templates.
 * Supports both:
 *   - "notif:<notification_key>" → email_notifications table
 *   - "tpl:<template_key>"       → email_templates table (legacy)
 *   - bare key (legacy compat)   → email_templates table
 */
require_once '../admin_session.php';
require_once '../email_template_helper.php';

// AdminEmailHelper is used for wrapping + sending via SMTP
$adminEmailHelperPath = dirname(__DIR__) . '/AdminEmailHelper.php';
if (file_exists($adminEmailHelperPath)) {
    require_once $adminEmailHelperPath;
}

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $rawKey    = $_POST['template_key'] ?? '';
    $usersJson = $_POST['users'] ?? '[]';

    if (empty($rawKey)) {
        echo json_encode(['success' => false, 'message' => 'Keine Vorlage ausgewählt']);
        exit();
    }

    $users = json_decode($usersJson, true);

    if (empty($users) || !is_array($users)) {
        echo json_encode(['success' => false, 'message' => 'Keine Benutzer ausgewählt']);
        exit();
    }

    if (count($users) > 500) {
        echo json_encode(['success' => false, 'message' => 'Maximum 500 Benutzer pro Batch erlaubt']);
        exit();
    }

    // Detect which table to use
    $useNotifTable   = str_starts_with($rawKey, 'notif:');
    $notificationKey = null;
    $templateKey     = null;

    if ($useNotifTable) {
        $notificationKey = substr($rawKey, 6);
    } elseif (str_starts_with($rawKey, 'tpl:')) {
        $templateKey = substr($rawKey, 4);
    } else {
        // Legacy bare key → email_templates
        $templateKey = $rawKey;
    }

    // Pre-fetch notification template (if from email_notifications)
    $notifTemplate = null;
    if ($useNotifTable) {
        $notifStmt = $pdo->prepare("
            SELECT notification_key, name, subject, content
            FROM email_notifications
            WHERE notification_key = ? AND is_active = 1
        ");
        $notifStmt->execute([$notificationKey]);
        $notifTemplate = $notifStmt->fetch(PDO::FETCH_ASSOC);

        if (!$notifTemplate) {
            echo json_encode(['success' => false, 'message' => 'Benachrichtigungsvorlage nicht gefunden']);
            exit();
        }
    }

    // Initialize helpers
    $emailHelper = new EmailTemplateHelper($pdo);
    $adminEmailHelper = class_exists('AdminEmailHelper') ? new AdminEmailHelper($pdo) : null;

    $baseUrl    = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
    $sentCount  = 0;
    $failedCount = 0;
    $errors     = [];

    foreach ($users as $user) {
        try {
            // Fetch full user data
            $stmt = $pdo->prepare("
                SELECT
                    u.*,
                    DATEDIFF(NOW(), u.last_login) AS days_inactive
                FROM users u
                WHERE u.id = ?
            ");
            $stmt->execute([$user['id']]);
            $fullUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$fullUser) {
                $failedCount++;
                $errors[] = "Benutzer nicht gefunden: {$user['email']}";
                continue;
            }

            $lastLogin = $fullUser['last_login'] ? date('d.m.Y', strtotime($fullUser['last_login'])) : 'nie';

            // Build variables
            $variables = [
                'first_name'         => $fullUser['first_name'] ?? '',
                'last_name'          => $fullUser['last_name'] ?? '',
                'email'              => $fullUser['email'],
                'balance'            => number_format((float)($fullUser['balance'] ?? 0), 2, ',', '.'),
                'currency'           => '€',
                'days_inactive'      => $fullUser['days_inactive'] ?? 0,
                'last_login_date'    => $lastLogin,
                'platform_name'      => 'FundTracer AI',
                'site_url'           => $baseUrl,
                'kyc_url'            => $baseUrl . '/app/kyc.php',
                'onboarding_url'     => $baseUrl . '/app/onboarding.php',
                'verification_link'  => $baseUrl . '/app/verify-email.php',
                'case_number'        => 'CASE-' . str_pad($fullUser['id'], 6, '0', STR_PAD_LEFT),
                'case_status'        => 'In Bearbeitung',
                'update_message'     => '',
                'amount'             => '',
                'reference'          => '',
                'withdrawal_method'  => '',
                'rejection_reason'   => '',
                'required_documents' => '',
                'deadline'           => '',
                'recovery_rate'      => '0',
                'cases_count'        => '0',
                'recovered_amount'   => '0,00',
                'quarter'            => 'Q' . ceil(date('n') / 3),
                'year'               => date('Y'),
                'login_time'         => '',
                'login_location'     => '',
                'ip_address'         => '',
            ];

            $success = false;

            if ($useNotifTable && $notifTemplate) {
                // Replace variables in subject and content
                $subject = $notifTemplate['subject'];
                $content = $notifTemplate['content'];
                foreach ($variables as $k => $v) {
                    $subject = str_replace('{' . $k . '}', $v, $subject);
                    $content = str_replace('{' . $k . '}', $v, $content);
                }

                // Send via AdminEmailHelper (wraps in branded template) or plain PHPMailer fallback
                if ($adminEmailHelper) {
                    $success = $adminEmailHelper->sendDirectEmail(
                        $fullUser['email'],
                        $subject,
                        $content
                    );
                } else {
                    // Use EmailTemplateHelper's built-in SMTP send with inline HTML
                    $success = $emailHelper->sendRawHtmlEmail(
                        $fullUser['email'],
                        $subject,
                        $content
                    );
                    // If sendRawHtmlEmail doesn't exist, fall back silently and log
                    if ($success === null) {
                        error_log("AdminEmailHelper not available and sendRawHtmlEmail not found – email NOT sent to {$fullUser['email']}");
                        $success = false;
                    }
                }

                $logSubject  = $subject;
                $logTemplate = 'notif:' . $notificationKey;

            } else {
                // Legacy email_templates path
                $success = $emailHelper->sendTemplateEmail(
                    $fullUser['email'],
                    $templateKey,
                    $variables
                );
                $logSubject  = 'Bulk Notification';
                $logTemplate = $templateKey;
            }

            if ($success) {
                $sentCount++;

                // Log to email_logs
                try {
                    $logStmt = $pdo->prepare("
                        INSERT INTO email_logs
                            (recipient, subject, template_key, status, sent_at, user_id, admin_id)
                        VALUES (?, ?, ?, 'sent', NOW(), ?, ?)
                    ");
                    $logStmt->execute([
                        $fullUser['email'],
                        $logSubject,
                        $logTemplate,
                        $fullUser['id'],
                        $_SESSION['admin_id'],
                    ]);
                } catch (Exception $e) {
                    error_log("Email log insert failed: " . $e->getMessage());
                }
            } else {
                $failedCount++;
                $errors[] = "Fehler beim Senden an: {$fullUser['email']}";
            }

        } catch (Exception $e) {
            $failedCount++;
            $errors[] = "Fehler bei {$user['email']}: " . $e->getMessage();
            error_log("Error sending notification to {$user['email']}: " . $e->getMessage());
        }

        // Throttle: 0.1s pause every 10 messages
        if ($sentCount > 0 && $sentCount % 10 === 0) {
            usleep(100000);
        }
    }

    // Audit log
    try {
        $auditStmt = $pdo->prepare("
            INSERT INTO audit_logs
                (admin_id, action, entity_type, entity_id, new_value, ip_address, user_agent, created_at)
            VALUES (?, 'bulk_notification', 'email_notification', NULL, ?, ?, ?, NOW())
        ");
        $auditStmt->execute([
            $_SESSION['admin_id'],
            json_encode([
                'template' => $rawKey,
                'sent'     => $sentCount,
                'failed'   => $failedCount,
                'total'    => count($users),
            ]),
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        ]);
    } catch (Exception $e) {
        error_log("Failed to log audit: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => "Erfolgreich {$sentCount} von " . count($users) . " E-Mails gesendet",
        'sent'    => $sentCount,
        'failed'  => $failedCount,
        'total'   => count($users),
        'errors'  => $errors,
    ]);

} catch (Exception $e) {
    error_log("Error in send_bulk_notifications.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Fehler: ' . $e->getMessage(),
    ]);
}
