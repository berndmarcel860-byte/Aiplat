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

// Test succeeded — now verify that the settings are also saved and enabled in the DB,
// because the real ticket notification reads from tg_settings rather than form values.
$successMessage = 'Test message sent successfully!';
try {
    $stmt = $pdo->query("SELECT bot_token, chat_id, is_enabled FROM tg_settings WHERE id = 1 LIMIT 1");
    $dbRow = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;

    if (!$dbRow || empty($dbRow['bot_token']) || empty($dbRow['chat_id'])) {
        $successMessage .= ' ⚠️ However, settings are not yet saved in the database — click "Save Telegram Settings" to activate real ticket notifications.';
    } elseif (!$dbRow['is_enabled']) {
        $successMessage .= ' ⚠️ However, notifications are currently disabled in the database — check "Enable Telegram Notifications" and save to activate real ticket notifications.';
    } elseif (trim($dbRow['bot_token']) !== $bot_token || trim($dbRow['chat_id']) !== $chat_id) {
        $successMessage .= ' ⚠️ Note: The tested credentials differ from the saved settings — save the form to apply them to real ticket notifications.';
    }
} catch (Exception $dbCheckEx) {
    error_log("test_telegram.php - DB state check error: " . $dbCheckEx->getMessage());
}

echo json_encode(['success' => true, 'message' => $successMessage]);
