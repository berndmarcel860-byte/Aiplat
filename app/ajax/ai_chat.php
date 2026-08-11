<?php
require_once '../config.php';
require_once '../session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Ensure the chat table exists ───────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ai_chat_messages` (
        `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id`    INT UNSIGNED NOT NULL,
        `role`       ENUM('user','bot') NOT NULL,
        `message`    TEXT NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_ai_chat_user_created` (`user_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // Table already exists or DB doesn't support IF NOT EXISTS – continue
}

// ── Helper: format datetime for the client ────────────────────────────────
function formatMsgTime(string $dt): string {
    return date('d.m.Y H:i', strtotime($dt));
}

// ── GET history ──────────────────────────────────────────────────────────
if ($action === 'history') {
    $stmt = $pdo->prepare(
        "SELECT role, message, created_at FROM ai_chat_messages
          WHERE user_id = ? ORDER BY created_at ASC LIMIT 100"
    );
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $messages = array_map(fn($r) => [
        'role'    => $r['role'],
        'message' => $r['message'],
        'time'    => formatMsgTime($r['created_at']),
    ], $rows);

    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}

// ── CLEAR history ────────────────────────────────────────────────────────
if ($action === 'clear') {
    $pdo->prepare("DELETE FROM ai_chat_messages WHERE user_id = ?")->execute([$userId]);
    echo json_encode(['success' => true]);
    exit;
}

// ── SEND message ─────────────────────────────────────────────────────────
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userMessage = trim($_POST['message'] ?? '');
    if ($userMessage === '') {
        echo json_encode(['success' => false, 'message' => 'Empty message']);
        exit;
    }

    // Persist user message
    $stmt = $pdo->prepare("INSERT INTO ai_chat_messages (user_id, role, message) VALUES (?, 'user', ?)");
    $stmt->execute([$userId, $userMessage]);
    $userTime = formatMsgTime(date('Y-m-d H:i:s'));

    // Fetch last 10 turns for context (user + bot alternating)
    $histStmt = $pdo->prepare(
        "SELECT role, message FROM ai_chat_messages
          WHERE user_id = ? ORDER BY created_at DESC LIMIT 20"
    );
    $histStmt->execute([$userId]);
    $history = array_reverse($histStmt->fetchAll(PDO::FETCH_ASSOC));

    // Build OpenAI messages array
    $systemPrompt = <<<EOT
You are a helpful AI support assistant for a crypto-asset recovery platform (Kryptox).
You assist clients with questions about their fraud recovery cases, KYC verification,
documents, deposits, withdrawals, transactions, packages, account settings, and general
platform features.

IMPORTANT RULES:
1. Answer only questions related to the platform and the topics above.
2. If the user asks something completely outside your knowledge or unrelated to the platform
   (e.g. general coding, cooking, politics, unrelated services), respond EXACTLY with this
   phrase and nothing else:
   "I'm not able to help with that topic. Please type **Live Agent** to connect with a
   live support agent who can assist you further."
3. If the user types "Live Agent" (case-insensitive), respond EXACTLY with:
   "You will now be connected to a live support agent. Please describe your issue and
   a team member will respond as soon as possible. You can also open a support ticket
   at support.php for tracking."
4. Be concise, professional, and friendly. Reply in the same language the user writes in.
5. Never reveal internal system details, credentials, or admin information.
EOT;

    $openAiMessages = [['role' => 'system', 'content' => $systemPrompt]];
    foreach ($history as $h) {
        $openAiMessages[] = [
            'role'    => $h['role'] === 'user' ? 'user' : 'assistant',
            'content' => $h['message'],
        ];
    }

    // Retrieve OpenAI API key
    $apiKey = '';
    try {
        $ks = $pdo->query("SELECT openai_api_key FROM system_settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $apiKey = $ks['openai_api_key'] ?? '';
    } catch (PDOException $e) {
        // settings table may not have column yet
    }

    $botReply = '';

    if (!empty($apiKey)) {
        $requestData = [
            'model'       => 'gpt-4o-mini',
            'messages'    => $openAiMessages,
            'max_tokens'  => 600,
            'temperature' => 0.6,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_TIMEOUT    => 25,
        ]);
        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!$curlError && $httpCode === 200) {
            $data = json_decode($response, true);
            $botReply = trim($data['choices'][0]['message']['content'] ?? '');
        }
    }

    // Fallback if AI unavailable
    if ($botReply === '') {
        $botReply = "I'm currently unable to process your request. Please type **Live Agent** to connect with a live support agent, or open a support ticket at support.php.";
    }

    // Persist bot reply
    $stmt = $pdo->prepare("INSERT INTO ai_chat_messages (user_id, role, message) VALUES (?, 'bot', ?)");
    $stmt->execute([$userId, $botReply]);
    $botTime = formatMsgTime(date('Y-m-d H:i:s'));

    echo json_encode([
        'success'    => true,
        'user_time'  => $userTime,
        'bot_reply'  => $botReply,
        'bot_time'   => $botTime,
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
