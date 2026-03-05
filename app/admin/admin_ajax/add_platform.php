<?php
require_once '../admin_session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Validate required fields
if (empty($_POST['name']) || empty($_POST['type'])) {
    echo json_encode(['success' => false, 'message' => 'Platform name and type are required']);
    exit();
}

$name = trim($_POST['name']);
$url = trim($_POST['url']) ?: null;
$type = $_POST['type'];
$description = trim($_POST['description']) ?: null;
$admin_id = $_SESSION['admin_id'];

// Validate type
$valid_types = ['crypto', 'forex', 'investment', 'dating', 'tax', 'other'];
if (!in_array($type, $valid_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid platform type']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Handle logo upload
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['logo']['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
        }
        
        if ($_FILES['logo']['size'] > $max_size) {
            throw new Exception('File size too large. Maximum size is 2MB.');
        }
        
        // Correct path: from admin/admin_ajax/ go up one level to admin/, then up to app/
        $app_root = dirname(__FILE__, 3); // Goes up 2 levels: admin_ajax -> admin -> app
        $upload_dir = $app_root . '/uploads/platforms/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception('Failed to create upload directory. Please check permissions on: ' . $upload_dir);
            }
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            throw new Exception('Upload directory is not writable: ' . $upload_dir);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = 'platform_' . uniqid() . '.' . strtolower($file_extension);
        $logo_full_path = $upload_dir . $filename;
        
        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $logo_full_path)) {
            throw new Exception('Failed to upload logo to: ' . $logo_full_path);
        }
        
        // Store relative path for database (from app root)
        $logo_path = 'uploads/platforms/' . $filename;
    }
    
    // Insert platform
    $stmt = $pdo->prepare("
        INSERT INTO scam_platforms 
        (name, url, type, description, logo, created_by, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $name, $url, $type, $description, $logo_path, $admin_id
    ]);
    
    $platform_id = $pdo->lastInsertId();
    
    // Send notification emails to all active users about new scam platform
    try {
        $usersStmt = $pdo->query("SELECT id, email, first_name FROM users WHERE status = 'active' AND is_verified = 1 LIMIT 500");
        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $emailsSent = 0;
        foreach ($users as $user) {
            $emailMessage = "
                <h2>⚠️ New Scam Platform Alert</h2>
                <p>Hello {$user['first_name']},</p>
                
                <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                    <h3 style='color: #856404; margin-top: 0;'>⚠️ SCAM ALERT</h3>
                    <p style='margin: 0;'>
                        <strong>{$name}</strong> has been identified as a scam platform.
                    </p>
                </div>
                
                <p><strong>Platform Details:</strong></p>
                <ul>
                    <li><strong>Name:</strong> {$name}</li>
                    " . ($url ? "<li><strong>URL:</strong> {$url}</li>" : "") . "
                    <li><strong>Type:</strong> " . ucfirst($type) . "</li>
                </ul>
                
                <div style='background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0;'>
                    <h3 style='color: #0c5460; margin-top: 0;'>🛡️ We're Here to Help</h3>
                    <p>If you invested with <strong>{$name}</strong>, we can help recover your funds.</p>
                </div>
                
                <p style='text-align: center;'>
                    <a href='https://{$_SERVER['HTTP_HOST']}/cases.php' 
                       style='background: linear-gradient(135deg, #2950a8, #2da9e3); 
                              color: white; padding: 15px 30px; text-decoration: none; 
                              border-radius: 5px; display: inline-block; font-weight: bold;'>
                        Report Your Case
                    </a>
                </p>
            ";
            
            $emailHTML = '<!DOCTYPE html>
<html><head><meta charset="UTF-8"><style>
body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
.container { max-width: 600px; margin: 0 auto; padding: 20px; }
.header { background: linear-gradient(135deg, #dc3545, #ff6b6b); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
.content { background: #f9f9f9; padding: 30px; }
.footer { background: #333; color: #fff; padding: 20px; text-align: center; font-size: 12px; border-radius: 0 0 10px 10px; }
</style></head><body><div class="container">
<div class="header"><h1 style="margin: 0;">⚠️ SCAM ALERT</h1><p style="margin: 5px 0 0 0;">New Scam Platform Detected</p></div>
<div class="content">' . $emailMessage . '</div>
<div class="footer"><p>&copy; ' . date('Y') . ' FundTracer AI. All rights reserved.</p></div>
</div></body></html>';
            
            $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: FundTracer AI Security <security@fundtracerai.com>\r\n";
            
            if (mail($user['email'], "⚠️ SCAM ALERT: {$name} Detected", $emailHTML, $headers)) {
                $logStmt = $pdo->prepare("INSERT INTO email_logs (recipient, subject, template_key, status, sent_at, user_id) VALUES (?, ?, 'scam_platform_alert', 'sent', NOW(), ?)");
                $logStmt->execute([$user['email'], "⚠️ SCAM ALERT: {$name} Detected", $user['id']]);
                $emailsSent++;
            }
        }
    } catch (Exception $emailEx) {
        error_log("Failed to send scam platform notifications: " . $emailEx->getMessage());
    }
    
    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs 
        (admin_id, action, entity_type, entity_id, new_value, ip_address, user_agent, created_at)
        VALUES (?, 'create', 'platform', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $admin_id,
        $platform_id,
        json_encode(['name' => $name, 'type' => $type]),
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "Platform added successfully! Sent {$emailsSent} notification emails to users.",
        'platform_id' => $platform_id,
        'emails_sent' => $emailsSent
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    
    // Clean up uploaded file if there was an error and file exists
    if (isset($logo_full_path) && file_exists($logo_full_path)) {
        unlink($logo_full_path);
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>