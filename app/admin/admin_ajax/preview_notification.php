<?php
/**
 * Preview Notification Email
 * Show preview of email template with sample data
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
    
    if (empty($templateKey)) {
        echo json_encode(['success' => false, 'message' => 'Keine Vorlage ausgewählt']);
        exit();
    }
    
    // Initialize email helper
    $emailHelper = new EmailTemplateHelper($pdo);
    
    // Sample variables for preview
    $sampleVariables = [
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'email' => 'max.mustermann@example.com',
        'balance' => '250.00',
        'days_inactive' => '15',
        'login_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/login.php',
        'kyc_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/kyc.php',
        'withdrawal_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/withdrawals.php',
        'onboarding_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/onboarding.php',
        'reset_password_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/reset-password.php',
        'dashboard_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/dashboard.php',
        'support_email' => 'support@fundtracerai.com',
        'case_number' => 'CASE-000123',
        'min_withdrawal' => '50',
        'max_withdrawal' => '10000',
        'missing_step_1' => 'Persönliche Daten vervollständigen',
        'missing_step_2' => 'Adresse bestätigen',
        'missing_step_3' => 'Bankdaten hinzufügen',
        'user_id' => '123'
    ];
    
    // Render template
    $rendered = $emailHelper->renderTemplate($templateKey, $sampleVariables);
    
    if (!$rendered) {
        echo json_encode([
            'success' => false,
            'message' => 'Vorlage konnte nicht geladen werden'
        ]);
        exit();
    }
    
    // Return preview HTML
    echo json_encode([
        'success' => true,
        'preview' => '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: white;">' 
                     . '<h6 class="mb-2"><strong>Betreff:</strong> ' . htmlspecialchars($rendered['subject']) . '</h6>'
                     . '<hr>'
                     . $rendered['content'] 
                     . '</div>',
        'subject' => $rendered['subject']
    ]);
    
} catch (Exception $e) {
    error_log("Error in preview_notification.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Laden der Vorschau: ' . $e->getMessage()
    ]);
}