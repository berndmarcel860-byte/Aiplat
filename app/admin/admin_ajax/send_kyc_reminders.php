<?php
/**
 * Send KYC Reminder Emails
 * Sends reminder emails to all users who haven't completed KYC verification
 */
require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Find users without completed KYC
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.email,
            u.first_name,
            u.last_name
        FROM users u
        LEFT JOIN kyc_verification_requests k ON u.id = k.user_id
        WHERE u.status = 'active'
            AND u.is_verified = 1
            AND (k.id IS NULL OR k.status IN ('pending', 'rejected', 'none'))
        GROUP BY u.id
        LIMIT 100
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo json_encode([
            'success' => true,
            'message' => 'No users need KYC reminders',
            'sent' => 0,
            'failed' => 0
        ]);
        exit;
    }
    
    $sentCount = 0;
    $failedCount = 0;
    $errors = [];
    
    // Email template
    $subject = 'Complete Your KYC Verification - FundTracer AI';
    
    foreach ($users as $user) {
        try {
            $message = "
                <h2>Hello {$user['first_name']},</h2>
                <p>We noticed that you haven't completed your <strong>KYC (Know Your Customer) verification</strong> yet.</p>
                
                <p><strong>Why is KYC Important?</strong></p>
                <ul>
                    <li>✅ Required for fund recovery processing</li>
                    <li>✅ Ensures secure transactions</li>
                    <li>✅ Protects your account</li>
                    <li>✅ Unlocks full platform features</li>
                </ul>
                
                <div style='background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #2950a8;'>📋 Complete Your KYC in 3 Easy Steps:</h3>
                    <ol>
                        <li>Upload a valid government-issued ID</li>
                        <li>Provide a recent utility bill or bank statement</li>
                        <li>Take a selfie holding your ID</li>
                    </ol>
                </div>
                
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='https://{$_SERVER['HTTP_HOST']}/kyc.php' 
                       style='background: linear-gradient(135deg, #2950a8, #2da9e3); 
                              color: white; 
                              padding: 15px 30px; 
                              text-decoration: none; 
                              border-radius: 5px; 
                              display: inline-block;
                              font-weight: bold;'>
                        Complete KYC Verification Now
                    </a>
                </p>
                
                <p><strong>Need Help?</strong> Our support team is available 24/7 to assist you with the verification process.</p>
                
                <p>Best regards,<br>
                <strong>FundTracer AI Team</strong></p>
            ";
            
            // Prepare email HTML
            $emailHTML = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #2950a8, #2da9e3); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; }
        .footer { background: #333; color: #fff; padding: 20px; text-align: center; font-size: 12px; border-radius: 0 0 10px 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">FundTracer AI</h1>
            <p style="margin: 5px 0 0 0;">Complete Your KYC Verification</p>
        </div>
        <div class="content">
            ' . $message . '
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' FundTracer AI. All rights reserved.</p>
            <p>You received this email because you have an active account with us.</p>
        </div>
    </div>
</body>
</html>';
            
            // Send email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: FundTracer AI <noreply@fundtracerai.com>" . "\r\n";
            
            if (mail($user['email'], $subject, $emailHTML, $headers)) {
                // Log email
                $logStmt = $pdo->prepare("
                    INSERT INTO email_logs 
                    (recipient, subject, template_key, status, sent_at, user_id) 
                    VALUES (?, ?, 'kyc_reminder', 'sent', NOW(), ?)
                ");
                $logStmt->execute([$user['email'], $subject, $user['id']]);
                $sentCount++;
            } else {
                $failedCount++;
                $errors[] = "Failed to send to: {$user['email']}";
            }
            
        } catch (Exception $e) {
            $failedCount++;
            $errors[] = "Error sending to {$user['email']}: " . $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Sent {$sentCount} KYC reminder emails. {$failedCount} failed.",
        'sent' => $sentCount,
        'failed' => $failedCount,
        'total_users' => count($users),
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
