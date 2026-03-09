<?php
/**
 * contact.php – AJAX endpoint for the Schnellbewertung (Quick Loss Estimator) contact form.
 *
 * Accepts POST requests with JSON body, stores the lead in contact_leads, and
 * (optionally) sends a confirmation e-mail to the submitter.
 *
 * Response: JSON { success: bool, message: string }
 */

header('Content-Type: application/json; charset=utf-8');

// ------------------------------------------------------------------
// Rate-limit guard: max 5 submissions per IP per hour (soft-limit)
// ------------------------------------------------------------------
/** Seconds in a sliding rate-limit window (1 hour). */
const RATE_LIMIT_WINDOW = 3600;
session_start();
$now = time();
$key = 'contact_submit_times';
$_SESSION[$key] = array_filter($_SESSION[$key] ?? [], fn($t) => $now - $t < RATE_LIMIT_WINDOW);
if (count($_SESSION[$key]) >= 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Zu viele Anfragen. Bitte versuchen Sie es später erneut.']);
    exit;
}

// ------------------------------------------------------------------
// Parse input (supports both JSON body and regular POST)
// ------------------------------------------------------------------
$raw = file_get_contents('php://input');
$data = $raw ? json_decode($raw, true) : $_POST;

$name       = trim($data['name']        ?? '');
$email      = trim($data['email']       ?? '');
$phone      = trim($data['phone']       ?? '');
$lossAmount = trim($data['loss_amount'] ?? '');
$lossType   = trim($data['loss_type']   ?? '');
$message    = trim($data['message']     ?? '');

// ------------------------------------------------------------------
// Validation
// ------------------------------------------------------------------
if ($name === '' || mb_strlen($name) > 255) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Bitte geben Sie Ihren Namen ein.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.']);
    exit;
}

// ------------------------------------------------------------------
// DB connection (reuse app/config.php env vars)
// ------------------------------------------------------------------
$host     = getenv('DB_HOST')     ?: 'localhost';
$dbname   = getenv('DB_NAME')     ?: 'novalnet-ai';
$dbuser   = getenv('DB_USER')     ?: 'novalnet';
$dbpass   = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $dbuser, $dbpass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('contact.php DB connect error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler. Bitte versuchen Sie es später erneut.']);
    exit;
}

// ------------------------------------------------------------------
// Allowed value whitelist for enumerated dropdown fields
// ------------------------------------------------------------------
$allowedAmounts = ['5000', '25000', '50000', '100000', '250000', ''];
$allowedTypes   = ['exchange', 'investment', 'romance', 'rug', 'phishing', 'other', ''];

$lossAmount = in_array($lossAmount, $allowedAmounts, true) ? $lossAmount : '';
$lossType   = in_array($lossType,   $allowedTypes,   true) ? $lossType   : '';

// Sanitise phone – keep only digits, +, -, spaces, parentheses
$phone = preg_replace('/[^\d\+\-\s\(\)]/', '', $phone);
$phone = mb_substr($phone, 0, 50);

// Truncate free-form message
$message = mb_substr($message, 0, 2000);

// ------------------------------------------------------------------
// Persist to DB
// ------------------------------------------------------------------
try {
    $stmt = $pdo->prepare("
        INSERT INTO contact_leads (name, email, phone, loss_amount, loss_type, message, ip_address, created_at)
        VALUES (:name, :email, :phone, :loss_amount, :loss_type, :message, :ip, NOW())
    ");
    $stmt->execute([
        ':name'        => $name,
        ':email'       => $email,
        ':phone'       => $phone !== '' ? $phone : null,
        ':loss_amount' => $lossAmount !== '' ? $lossAmount : null,
        ':loss_type'   => $lossType   !== '' ? $lossType   : null,
        ':message'     => $message    !== '' ? $message    : null,
        ':ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
} catch (PDOException $e) {
    error_log('contact.php INSERT error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Speicherfehler. Bitte versuchen Sie es später erneut.']);
    exit;
}

// ------------------------------------------------------------------
// Track session submission
// ------------------------------------------------------------------
$_SESSION[$key][] = $now;

// ------------------------------------------------------------------
// Optional: send a short confirmation e-mail (best-effort, no fatal on failure)
// ------------------------------------------------------------------
$lossAmountLabels = [
    '5000'   => 'Bis €5.000',
    '25000'  => '€5.000 – €25.000',
    '50000'  => '€25.000 – €50.000',
    '100000' => '€50.000 – €100.000',
    '250000' => 'Über €100.000',
];
$lossTypeLabels = [
    'exchange'   => 'Fake Exchange',
    'investment' => 'Investment-Betrug',
    'romance'    => 'Romance Scam',
    'rug'        => 'Rug Pull / Token-Betrug',
    'phishing'   => 'Phishing / Wallet-Hack',
    'other'      => 'Sonstiges',
];

$amountLabel = $lossAmountLabels[$lossAmount] ?? $lossAmount;
$typeLabel   = $lossTypeLabels[$lossType]     ?? $lossType;

try {
    // Load brand name for the email
    $brandStmt = $pdo->prepare("SELECT brand_name, contact_email FROM system_settings WHERE id = ? LIMIT 1");
    $brandStmt->execute([1]);
    $brandRow = $brandStmt->fetch();
    $brandName    = $brandRow['brand_name']    ?? 'Novalnet AI';
    $contactEmail = $brandRow['contact_email'] ?? 'info@novalnet-ai.de';

    $subject = "Ihre Anfrage wurde erhalten – $brandName";
    $body = "
<p>Guten Tag $name,</p>
<p>vielen Dank für Ihre Anfrage. Wir haben Ihre Kontaktdaten erhalten und werden uns so schnell wie möglich bei Ihnen melden.</p>
<table style='border-collapse:collapse;'>
  <tr><td style='padding:4px 12px 4px 0;font-weight:bold;'>Verlorener Betrag:</td><td>" . htmlspecialchars($amountLabel) . "</td></tr>
  <tr><td style='padding:4px 12px 4px 0;font-weight:bold;'>Art des Verlusts:</td><td>" . htmlspecialchars($typeLabel) . "</td></tr>
</table>
<p>Mit freundlichen Grüßen,<br>Ihr $brandName-Team</p>
";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $brandName <$contactEmail>\r\n";

    if (!mail($email, $subject, $body, $headers)) {
        error_log('contact.php: mail() failed for recipient: ' . $email);
    }
} catch (Throwable $e) {
    // Non-fatal – log and continue
    error_log('contact.php mail error: ' . $e->getMessage());
}

echo json_encode(['success' => true, 'message' => 'Vielen Dank! Wir melden uns schnellstmöglich bei Ihnen.']);
