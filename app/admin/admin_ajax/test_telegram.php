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

echo json_encode(['success' => true, 'message' => 'Test message sent successfully!']);
