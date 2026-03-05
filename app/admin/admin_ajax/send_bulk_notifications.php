<?php
/**
 * Send Bulk Notifications
 * Send emails to multiple users using templates
 */
require_once '../admin_session.php';
require_once '../email_template_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $templateKey = $_POST['template_key'] ?? '';
    $usersJson = $_POST['users'] ?? '[]';
    
    if (empty($templateKey)) {
        echo json_encode(['success' => false, 'message' => 'Keine Vorlage ausgewählt']);
        exit();
    }
    
    $users = json_decode($usersJson, true);
    
    if (empty($users) || !is_array($users)) {
        echo json_encode(['success' => false, 'message' => 'Keine Benutzer ausgewählt']);
        exit();
    }
    
    // Limit to prevent abuse
    if (count($users) > 500) {
        echo json_encode(['success' => false, 'message' => 'Maximum 500 Benutzer pro Batch erlaubt']);
        exit();
    }
    
    // Initialize email helper
    $emailHelper = new EmailTemplateHelper($pdo);
    
    $sentCount = 0;
    $failedCount = 0;
    $errors = [];
    
    foreach ($users as $user) {
        try {
            // Fetch full user data
            $stmt = $pdo->prepare("
                SELECT 
                    u.*,
                    DATEDIFF(NOW(), u.last_login) as days_inactive,
                    COALESCE((SELECT status FROM kyc_verification_requests WHERE user_id = u.id ORDER BY id DESC LIMIT 1), 'none') as kyc_status
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
            
            // Prepare template variables
            $variables = [
                'user_id' => $fullUser['id'],
                'first_name' => $fullUser['first_name'],
                'last_name' => $fullUser['last_name'],
                'email' => $fullUser['email'],
                'balance' => number_format($fullUser['balance'], 2),
                'days_inactive' => $fullUser['days_inactive'] ?? 0,
                'login_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/app/login.php',
                'kyc_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/app/kyc.php',
                'withdrawal_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/app/withdrawals.php',
                'onboarding_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/app/onboarding.php',
                'reset_password_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/app/reset-password.php',
                'dashboard_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/dashboard.php',
                'support_email' => 'support@fundtracerai.com',
                'case_number' => 'CASE-' . str_pad($fullUser['id'], 6, '0', STR_PAD_LEFT),
                'min_withdrawal' => '50',
                'max_withdrawal' => '10000',
                // Onboarding missing steps (sample data)
                'missing_step_1' => 'Persönliche Daten vervollständigen',
                'missing_step_2' => 'Adresse bestätigen',
                'missing_step_3' => 'Bankdaten hinzufügen'
            ];
            
            // Send email
            $success = $emailHelper->sendTemplateEmail(
                $fullUser['email'],
                $templateKey,
                $variables
            );
            
            if ($success) {
                $sentCount++;
                
                // Log notification
                $logStmt = $pdo->prepare("
                    INSERT INTO email_logs 
                    (recipient, subject, template_key, status, sent_at, user_id, admin_id)
                    VALUES (?, ?, ?, 'sent', NOW(), ?, ?)
                ");
                $logStmt->execute([
                    $fullUser['email'],
                    'Bulk Notification',
                    $templateKey,
                    $fullUser['id'],
                    $_SESSION['admin_id']
                ]);
            } else {
                $failedCount++;
                $errors[] = "Fehler beim Senden an: {$fullUser['email']}";
            }
            
        } catch (Exception $e) {
            $failedCount++;
            $errors[] = "Fehler bei {$user['email']}: " . $e->getMessage();
            error_log("Error sending to {$user['email']}: " . $e->getMessage());
        }
        
        // Small delay to prevent rate limiting
        if ($sentCount % 10 == 0) {
            usleep(100000); // 0.1 second
        }
    }
    
    // Log admin action
    try {
        $auditStmt = $pdo->prepare("
            INSERT INTO audit_logs 
            (admin_id, action, entity_type, entity_id, new_value, ip_address, user_agent, created_at)
            VALUES (?, 'bulk_email', 'notification', NULL, ?, ?, ?, NOW())
        ");
        $auditStmt->execute([
            $_SESSION['admin_id'],
            json_encode([
                'template' => $templateKey,
                'sent' => $sentCount,
                'failed' => $failedCount,
                'total' => count($users)
            ]),
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        error_log("Failed to log audit: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Erfolgreich {$sentCount} von " . count($users) . " E-Mails gesendet",
        'sent' => $sentCount,
        'failed' => $failedCount,
        'total' => count($users),
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    error_log("Error in send_bulk_notifications.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Fehler: ' . $e->getMessage()
    ]);
}