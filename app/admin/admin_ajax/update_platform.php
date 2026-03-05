<?php
require_once '../admin_session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['platform_id']) || !is_numeric($_POST['platform_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid platform ID']);
    exit();
}

// Validate required fields
if (empty($_POST['name']) || empty($_POST['type'])) {
    echo json_encode(['success' => false, 'message' => 'Platform name and type are required']);
    exit();
}

$platform_id = (int)$_POST['platform_id'];
$name = trim($_POST['name']);
$url = trim($_POST['url']) ?: null;
$type = $_POST['type'];
$description = trim($_POST['description']) ?: null;
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
$admin_id = $_SESSION['admin_id'];

// Validate type
$valid_types = ['crypto', 'forex', 'investment', 'dating', 'tax', 'other'];
if (!in_array($type, $valid_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid platform type']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Get current platform data for logging
    $stmt = $pdo->prepare("SELECT * FROM scam_platforms WHERE id = ?");
    $stmt->execute([$platform_id]);
    $old_platform = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$old_platform) {
        throw new Exception('Platform not found');
    }
    
    // Handle logo upload
    $logo_path = $old_platform['logo']; // Keep existing logo by default
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
        $app_root = dirname(__FILE__, 2); // Goes up 2 levels: admin_ajax -> admin -> app
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
        $new_logo_full_path = $upload_dir . $filename;
        
        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $new_logo_full_path)) {
            throw new Exception('Failed to upload logo to: ' . $new_logo_full_path);
        }
        
        // Delete old logo if it exists
        if ($old_platform['logo']) {
            $old_logo_path = $app_root . '/' . $old_platform['logo'];
            if (file_exists($old_logo_path)) {
                unlink($old_logo_path);
            }
        }
        
        // Store relative path for database (from app root)
        $logo_path = 'uploads/platforms/' . $filename;
    }
    
    // Update platform
    $stmt = $pdo->prepare("
        UPDATE scam_platforms 
        SET name = ?, url = ?, type = ?, description = ?, logo = ?, is_active = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $name, $url, $type, $description, $logo_path, $is_active, $platform_id
    ]);
    
    // Log the action
    $changes = [];
    if ($old_platform['name'] !== $name) $changes['name'] = ['old' => $old_platform['name'], 'new' => $name];
    if ($old_platform['url'] !== $url) $changes['url'] = ['old' => $old_platform['url'], 'new' => $url];
    if ($old_platform['type'] !== $type) $changes['type'] = ['old' => $old_platform['type'], 'new' => $type];
    if ($old_platform['description'] !== $description) $changes['description'] = ['old' => $old_platform['description'], 'new' => $description];
    if ($old_platform['is_active'] != $is_active) $changes['is_active'] = ['old' => $old_platform['is_active'], 'new' => $is_active];
    if ($old_platform['logo'] !== $logo_path) $changes['logo'] = ['old' => $old_platform['logo'], 'new' => $logo_path];
    
    if (!empty($changes)) {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs 
            (admin_id, action, entity_type, entity_id, old_value, new_value, ip_address, user_agent, created_at)
            VALUES (?, 'update', 'platform', ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $admin_id,
            $platform_id,
            json_encode($old_platform),
            json_encode($changes),
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Platform updated successfully!'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    
    // Clean up uploaded file if there was an error and file exists
    if (isset($new_logo_full_path) && file_exists($new_logo_full_path)) {
        unlink($new_logo_full_path);
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>