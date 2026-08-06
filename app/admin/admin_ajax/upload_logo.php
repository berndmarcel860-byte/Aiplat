<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

// Verify admin is logged in
if (!is_admin_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Verify CSRF token (sent as POST field)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// Validate file was provided
if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    $uploadError = $_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE;
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error (code ' . $uploadError . ')']);
    exit();
}

$file     = $_FILES['logo'];
$fileExt  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Only allow raster image formats (SVG excluded due to XSS risk)
$allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
if (!in_array($fileExt, $allowedExtensions, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PNG, JPG, JPEG, GIF, WEBP']);
    exit();
}

// Validate MIME type
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
$allowedMimes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
if (!in_array($mimeType, $allowedMimes, true)) {
    echo json_encode(['success' => false, 'message' => 'File content does not match an allowed image type']);
    exit();
}

// Size limit: 2 MB
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large (max 2 MB)']);
    exit();
}

// Save to /assets/img/logo.<ext> at the document root
$docRoot   = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$uploadDir = $docRoot . '/assets/img/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Could not create upload directory']);
        exit();
    }
}

// Use a fixed filename so the URL is stable; keep the original extension
$fileName    = 'logo.' . $fileExt;
$fileDest    = $uploadDir . $fileName;

if (!move_uploaded_file($file['tmp_name'], $fileDest)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
    exit();
}

// Build the absolute URL from the actual request host (no hardcoded domain).
// SERVER_NAME comes from the web-server config and is not user-controllable,
// making it safer than raw HTTP_HOST.  Fall back to HTTP_HOST only when
// SERVER_NAME is absent (e.g. CLI), and strip any port number appended by
// the client.
$scheme     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
if (empty($serverName)) {
    // Strip port from HTTP_HOST (e.g. "example.com:8080" -> "example.com")
    $serverName = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
}
$logoUrl = $scheme . '://' . $serverName . '/assets/img/' . $fileName;

// Update system_settings
try {
    // Check if row exists
    $stmt   = $pdo->query("SELECT id FROM system_settings WHERE id = 1");
    $exists = $stmt->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE system_settings SET logo_url = ?, updated_at = NOW() WHERE id = 1");
        $stmt->execute([$logoUrl]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO system_settings (id, logo_url, created_at, updated_at) VALUES (1, ?, NOW(), NOW())");
        $stmt->execute([$logoUrl]);
    }

    // Audit log
    $adminId   = $_SESSION['admin_id'];
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at)
        VALUES (?, 'update', 'system_settings', 1, ?, ?, NOW())
    ");
    $stmt->execute([$adminId, json_encode(['logo_url' => $logoUrl]), $ipAddress]);

    echo json_encode([
        'success'  => true,
        'message'  => 'Logo uploaded successfully',
        'logo_url' => $logoUrl,
    ]);
} catch (PDOException $e) {
    error_log("upload_logo.php DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error while saving logo URL']);
}
