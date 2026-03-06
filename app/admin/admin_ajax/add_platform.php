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
        require_once dirname(__FILE__, 2) . '/AdminEmailHelper.php';
        $emailHelper = new AdminEmailHelper($pdo);

        // Build custom variables that the alert_platform template uses
        $platformUrlLine = $url
            ? '<li><strong>URL:</strong> ' . htmlspecialchars($url) . '</li>'
            : '';

        $customVars = [
            'platform_name'     => $name,
            'platform_type'     => ucfirst($type),
            'platform_url'      => $url ?? '',
            'platform_url_line' => $platformUrlLine,
        ];

        $usersStmt = $pdo->query("SELECT id FROM users WHERE status = 'active' AND is_verified = 1 LIMIT 500");
        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

        $emailsSent = 0;
        foreach ($users as $user) {
            if ($emailHelper->sendTemplateEmail('alert_platform', $user['id'], $customVars)) {
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