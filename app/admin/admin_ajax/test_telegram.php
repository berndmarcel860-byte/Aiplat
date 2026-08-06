<?php
require_once '../admin_session.php';
require_once '../../TelegramHelper.php';

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

$bot_token = trim($_POST['bot_token'] ?? '');
$chat_id   = trim($_POST['chat_id']   ?? '');

if (empty($bot_token) || empty($chat_id)) {
    echo json_encode(['success' => false, 'message' => 'Bot token and chat ID are required']);
    exit();
}

// Validate basic token format: digits + colon + alphanumeric/underscore/hyphen
if (!preg_match('/^\d+:[A-Za-z0-9_\-]+$/', $bot_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid bot token format']);
    exit();
}

// Build a safe URL: the validated token contains only digits, colon, alphanum, underscores and hyphens
$url     = 'https://api.telegram.org/bot' . $bot_token . '/sendMessage';
$payload = http_build_query([
    'chat_id'    => $chat_id,
    'text'       => "✅ <b>Test Message</b>\n\nTelegram notifications for support tickets are configured correctly.",
    'parse_mode' => 'HTML',
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response  = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    error_log("test_telegram.php - cURL error: $curlError");
    echo json_encode(['success' => false, 'message' => 'Connection error: ' . $curlError]);
    exit();
}

$result = json_decode($response, true);
if (!($result['ok'] ?? false)) {
    $desc = $result['description'] ?? 'Unknown Telegram API error';
    error_log("test_telegram.php - API error: $desc");
    echo json_encode(['success' => false, 'message' => 'Telegram API error: ' . $desc]);
    exit();
}

// Test succeeded — automatically save the verified credentials to tg_settings so that
// real ticket notifications use them. On first setup (INSERT), notifications are enabled
// by default. If the row already exists, only the credentials are updated and the current
// is_enabled flag is preserved (so an admin who deliberately disabled notifications won't
// have that decision silently overridden).
$successMessage = 'Test message sent successfully! Credentials saved — Telegram notifications are active.';
try {
    $stmt = $pdo->prepare("
        INSERT INTO tg_settings (id, bot_token, chat_id, is_enabled)
        VALUES (1, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            bot_token = VALUES(bot_token),
            chat_id   = VALUES(chat_id)
    ");
    $stmt->execute([$bot_token, $chat_id]);

    // Check whether notifications are actually enabled after the save
    $checkStmt = $pdo->query("SELECT is_enabled FROM tg_settings WHERE id = 1 LIMIT 1");
    $savedRow  = $checkStmt ? $checkStmt->fetch(PDO::FETCH_ASSOC) : false;
    if ($savedRow && !$savedRow['is_enabled']) {
        $successMessage = 'Test message sent successfully! Credentials saved. ⚠️ However, notifications are currently disabled — check "Enable Telegram Notifications" and click "Save Telegram Settings" to activate real ticket notifications.';
    }
} catch (Exception $dbSaveEx) {
    error_log("test_telegram.php - failed to auto-save settings after successful test: " . $dbSaveEx->getMessage());
    $successMessage = 'Test message sent successfully! ⚠️ Settings could not be saved automatically — please click "Save Telegram Settings" to activate real ticket notifications.';
}

echo json_encode(['success' => true, 'message' => $successMessage]);
