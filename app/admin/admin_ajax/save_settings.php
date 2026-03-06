<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

// Verify admin is logged in
if (!is_admin_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// Get the type of settings to save
$type = $_POST['type'] ?? '';

try {
    if ($type === 'system') {
        // Save System Settings
        $brand_name = trim($_POST['brand_name'] ?? '');
        $site_url = trim($_POST['site_url'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $company_address = trim($_POST['company_address'] ?? '');
        $fca_reference_number = trim($_POST['fca_reference_number'] ?? '');

        // Validate required fields
        if (empty($brand_name) || empty($site_url) || empty($contact_email)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
            exit();
        }

        // Validate email
        if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit();
        }

        // Validate URL
        if (!filter_var($site_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid website URL']);
            exit();
        }

        // Check if record exists
        $stmt = $pdo->query("SELECT id FROM system_settings WHERE id = 1");
        $exists = $stmt->fetch();

        if ($exists) {
            // Update existing record
            $stmt = $pdo->prepare("
                UPDATE system_settings 
                SET brand_name = ?, 
                    site_url = ?, 
                    contact_email = ?, 
                    contact_phone = ?, 
                    company_address = ?, 
                    fca_reference_number = ?,
                    updated_at = NOW()
                WHERE id = 1
            ");
            $stmt->execute([
                $brand_name,
                $site_url,
                $contact_email,
                $contact_phone,
                $company_address,
                $fca_reference_number
            ]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (
                    id, brand_name, site_url, contact_email, contact_phone, 
                    company_address, fca_reference_number, created_at, updated_at
                ) VALUES (
                    1, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )
            ");
            $stmt->execute([
                $brand_name,
                $site_url,
                $contact_email,
                $contact_phone,
                $company_address,
                $fca_reference_number
            ]);
        }

        // Log the action
        $admin_id = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at)
            VALUES (?, 'update', 'system_settings', 1, ?, ?, NOW())
        ");
        $stmt->execute([$admin_id, json_encode($_POST), $ip_address]);

        echo json_encode(['success' => true, 'message' => 'System settings saved successfully!']);

    } elseif ($type === 'smtp') {        // Save SMTP Settings
        $host = trim($_POST['host'] ?? '');
        $port = intval($_POST['port'] ?? 587);
        $encryption = $_POST['encryption'] ?? 'tls';
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $from_email = trim($_POST['from_email'] ?? '');
        $from_name = trim($_POST['from_name'] ?? '');

        // Validate required fields
        if (empty($host) || empty($username) || empty($password) || empty($from_email) || empty($from_name)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
            exit();
        }

        // Validate email
        if (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid from email address']);
            exit();
        }

        // Validate port
        if ($port < 1 || $port > 65535) {
            echo json_encode(['success' => false, 'message' => 'Invalid port number']);
            exit();
        }

        // Validate encryption
        if (!in_array($encryption, ['tls', 'ssl', 'none'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid encryption type']);
            exit();
        }

        // Check if record exists
        $stmt = $pdo->query("SELECT id FROM smtp_settings WHERE id = 1");
        $exists = $stmt->fetch();

        if ($exists) {
            // Update existing record
            $stmt = $pdo->prepare("
                UPDATE smtp_settings 
                SET host = ?, 
                    port = ?, 
                    encryption = ?, 
                    username = ?, 
                    password = ?, 
                    from_email = ?, 
                    from_name = ?,
                    updated_at = NOW()
                WHERE id = 1
            ");
            $stmt->execute([
                $host,
                $port,
                $encryption,
                $username,
                $password,
                $from_email,
                $from_name
            ]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("
                INSERT INTO smtp_settings (
                    id, host, port, encryption, username, password, 
                    from_email, from_name, is_active, created_at, updated_at
                ) VALUES (
                    1, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW()
                )
            ");
            $stmt->execute([
                $host,
                $port,
                $encryption,
                $username,
                $password,
                $from_email,
                $from_name
            ]);
        }

        // Log the action (without password)
        $admin_id = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $log_data = $_POST;
        unset($log_data['password']); // Don't log password
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at)
            VALUES (?, 'update', 'smtp_settings', 1, ?, ?, NOW())
        ");
        $stmt->execute([$admin_id, json_encode($log_data), $ip_address]);

        echo json_encode(['success' => true, 'message' => 'SMTP settings saved successfully!']);

    } elseif ($type === 'telegram') {
        // Save Telegram Bot Settings
        $bot_token  = trim($_POST['bot_token']  ?? '');
        $chat_id    = trim($_POST['chat_id']    ?? '');
        $is_enabled = isset($_POST['is_enabled']) ? 1 : 0;

        if ($is_enabled && (empty($bot_token) || empty($chat_id))) {
            echo json_encode(['success' => false, 'message' => 'Bot token and chat ID are required when Telegram notifications are enabled']);
            exit();
        }

        // Validate basic token format: digits, colon, alphanumeric + underscores + hyphens
        if (!empty($bot_token) && !preg_match('/^\d+:[A-Za-z0-9_\-]+$/', $bot_token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid bot token format']);
            exit();
        }

        try {
            // Atomic upsert — works whether the row exists or not, and does not
            // reference updated_at so it is resilient to manually-created tables
            // that may omit that column.
            $stmt = $pdo->prepare("
                INSERT INTO tg_settings (id, bot_token, chat_id, is_enabled)
                VALUES (1, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    bot_token  = VALUES(bot_token),
                    chat_id    = VALUES(chat_id),
                    is_enabled = VALUES(is_enabled)
            ");
            $stmt->execute([$bot_token, $chat_id, $is_enabled]);
        } catch (PDOException $tgDbError) {
            error_log("save_settings.php (telegram) - DB error: " . $tgDbError->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: tg_settings table may not exist. Run the migration in database/tg_settings.sql first.']);
            exit();
        }

        // Log the action (without the token)
        $admin_id   = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $log_data   = ['chat_id' => $chat_id, 'is_enabled' => $is_enabled];
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at)
            VALUES (?, 'update', 'tg_settings', 1, ?, ?, NOW())
        ");
        $stmt->execute([$admin_id, json_encode($log_data), $ip_address]);

        echo json_encode(['success' => true, 'message' => 'Telegram settings saved successfully!']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid settings type']);
    }

} catch (PDOException $e) {
    error_log("Database error in save_settings.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in save_settings.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}