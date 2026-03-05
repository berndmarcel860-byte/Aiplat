<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get form data
    $host = $_POST['host'] ?? '';
    $port = $_POST['port'] ?? 587;
    $encryption = $_POST['encryption'] ?? 'tls';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $from_email = $_POST['from_email'] ?? '';
    $from_name = $_POST['from_name'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Basic validation
    if (empty($host)) {
        throw new Exception('SMTP host is required');
    }
    
    if (empty($port) || !is_numeric($port)) {
        throw new Exception('Valid SMTP port is required');
    }
    
    if (!empty($from_email) && !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid from email address');
    }
    
    if (!in_array($encryption, ['tls', 'ssl', 'none', ''])) {
        throw new Exception('Invalid encryption type');
    }
    
    $pdo->beginTransaction();
    
    // Check if settings exist
    $stmt = $pdo->prepare("SELECT id FROM smtp_settings LIMIT 1");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Update existing settings
        $stmt = $pdo->prepare("
            UPDATE smtp_settings 
            SET host = ?,
                port = ?,
                encryption = ?,
                username = ?,
                password = ?,
                from_email = ?,
                from_name = ?,
                is_active = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $host,
            $port,
            $encryption,
            $username,
            $password,
            $from_email,
            $from_name,
            $is_active,
            $exists['id']
        ]);
    } else {
        // Insert new settings
        $stmt = $pdo->prepare("
            INSERT INTO smtp_settings 
            (host, port, encryption, username, password, from_email, from_name, is_active, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $host,
            $port,
            $encryption,
            $username,
            $password,
            $from_email,
            $from_name,
            $is_active
        ]);
    }
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at) 
        VALUES (?, 'UPDATE', 'smtp_settings', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['admin_id'], 
        0, 
        "Updated SMTP settings: host=$host, port=$port, encryption=$encryption, is_active=$is_active",
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'SMTP settings saved successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Database error in update_smtp_settings.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
