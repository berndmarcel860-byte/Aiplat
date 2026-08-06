<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$prompt = trim($_POST['prompt'] ?? '');
if (empty($prompt)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a description for the email content']);
    exit;
}

try {
    // Retrieve OpenAI API key from system_settings
    $stmt = $pdo->query("SELECT openai_api_key FROM system_settings WHERE id = 1 LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    $apiKey = $settings['openai_api_key'] ?? '';

    if (empty($apiKey)) {
        echo json_encode([
            'success' => false,
            'message' => 'OpenAI API key not configured. Please add openai_api_key to System Settings.',
        ]);
        exit;
    }

    $systemPrompt = <<<EOT
You are an expert email copywriter for a financial services platform. 
Generate professional HTML email content (partial HTML only - no DOCTYPE, no <html>, no <body> tags) in German.
The content should use available template variables in {variable_name} format.
Common variables: {first_name}, {last_name}, {brand_name}, {contact_email}, {dashboard_url}, {site_url}, {amount}, {reference}, {current_date}.
Include proper HTML formatting with paragraphs, maybe a highlighted box for key information.
Keep the tone professional and trustworthy.
Return ONLY the HTML content, no explanations.
EOT;

    $requestData = [
        'model'      => 'gpt-4o-mini',
        'messages'   => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $prompt],
        ],
        'max_tokens' => 1200,
        'temperature' => 0.7,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS     => json_encode($requestData),
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('Connection error: ' . $curlError);
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200 || empty($data['choices'][0]['message']['content'])) {
        $errMsg = $data['error']['message'] ?? 'Unknown OpenAI error';
        throw new Exception('OpenAI API error: ' . $errMsg);
    }

    $generatedContent = trim($data['choices'][0]['message']['content']);

    echo json_encode([
        'success' => true,
        'content' => $generatedContent,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
