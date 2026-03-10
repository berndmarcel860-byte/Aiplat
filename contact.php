<?php
/**
 * contact.php – AJAX endpoint for the Terminvereinbarung (appointment request) contact form.
 *
 * Accepts POST requests with JSON body or FormData, stores the lead in register_request, and
 * (optionally) sends a confirmation e-mail to the submitter.
 *
 * Expected fields: first_name, last_name, email, phone, amount, year, platforms, details
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
// Parse input (supports both JSON body and regular POST / FormData)
// ------------------------------------------------------------------
$raw  = file_get_contents('php://input');
$data = ($raw && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json'))
    ? (json_decode($raw, true) ?? [])
    : $_POST;

$firstName = trim($data['first_name'] ?? '');
$lastName  = trim($data['last_name']  ?? '');
$email     = trim($data['email']      ?? '');
$phone     = trim($data['phone']      ?? '');
$amount    = trim($data['amount']     ?? '');
$year      = (int)($data['year']      ?? 0);
$platforms = trim($data['platforms']  ?? '');
$details   = trim($data['details']    ?? '');

// ------------------------------------------------------------------
// Validation
// ------------------------------------------------------------------
if ($firstName === '' || mb_strlen($firstName) > 100) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Bitte geben Sie Ihren Vornamen ein.']);
    exit;
}
if ($lastName === '' || mb_strlen($lastName) > 100) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Bitte geben Sie Ihren Nachnamen ein.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.']);
    exit;
}
if ($phone === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Bitte geben Sie Ihre Telefonnummer ein.']);
    exit;
}
if ($details === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Bitte beschreiben Sie Ihren Fall kurz.']);
    exit;
}

// ------------------------------------------------------------------
// Whitelist / sanitise enumerated fields
// ------------------------------------------------------------------
$allowedAmounts = ['5000-20000', '20000-50000', '50000-100000', '100000-250000', '250000-500000', '500000+'];
if (!in_array($amount, $allowedAmounts, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Bitte wählen Sie einen gültigen Verlustbetrag.']);
    exit;
}

if ($year < 2000 || $year > 2026) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Bitte wählen Sie ein gültiges Jahr.']);
    exit;
}

// Sanitise phone – keep only digits, +, -, spaces, parentheses
$phone = mb_substr(preg_replace('/[^\d\+\-\s\(\)]/', '', $phone), 0, 50);

// Truncate free-form fields
$platforms = mb_substr($platforms, 0, 500);
$details   = mb_substr($details, 0, 2000);

// ------------------------------------------------------------------
// DB connection (reuse app/config.php env vars)
// ------------------------------------------------------------------
$host   = getenv('DB_HOST')     ?: 'localhost';
$dbname = getenv('DB_NAME')     ?: 'novalnet-ai';
$dbuser = getenv('DB_USER')     ?: 'novalnet';
$dbpass = getenv('DB_PASSWORD') ?: '';

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
// Persist to register_request
// ------------------------------------------------------------------
try {
    $stmt = $pdo->prepare("
        INSERT INTO register_request
            (first_name, last_name, email, phone, amount, year, platforms, details, ip_address)
        VALUES
            (:first_name, :last_name, :email, :phone, :amount, :year, :platforms, :details, :ip)
    ");
    $stmt->execute([
        ':first_name' => $firstName,
        ':last_name'  => $lastName,
        ':email'      => $email,
        ':phone'      => $phone,
        ':amount'     => $amount,
        ':year'       => $year,
        ':platforms'  => $platforms !== '' ? $platforms : null,
        ':details'    => $details,
        ':ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
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
// Amount label map for confirmation e-mail
// ------------------------------------------------------------------
$amountLabels = [
    '5000-20000'    => '5.000 € – 20.000 €',
    '20000-50000'   => '20.000 € – 50.000 €',
    '50000-100000'  => '50.000 € – 100.000 €',
    '100000-250000' => '100.000 € – 250.000 €',
    '250000-500000' => '250.000 € – 500.000 €',
    '500000+'       => '500.000 € und mehr',
];
$amountLabel = $amountLabels[$amount] ?? $amount;

// ------------------------------------------------------------------
// Optional: send a short confirmation e-mail (best-effort, non-fatal)
// ------------------------------------------------------------------
try {
    $brandStmt = $pdo->prepare("SELECT brand_name, contact_email FROM system_settings WHERE id = ? LIMIT 1");
    $brandStmt->execute([1]);
    $brandRow     = $brandStmt->fetch();
    $brandName    = $brandRow['brand_name']    ?? 'Novalnet AI';
    $contactEmail = $brandRow['contact_email'] ?? 'info@novalnet-ai.de';

    $fullName = htmlspecialchars($firstName . ' ' . $lastName);
    $subject  = "Ihre Anfrage wurde erhalten – $brandName";
    $body     = "
<p>Guten Tag $fullName,</p>
<p>vielen Dank für Ihre Anfrage. Wir haben Ihre Kontaktdaten erhalten und werden uns so schnell wie möglich bei Ihnen melden.</p>
<table style='border-collapse:collapse;'>
  <tr><td style='padding:4px 12px 4px 0;font-weight:bold;'>Verlustbetrag:</td><td>" . htmlspecialchars($amountLabel) . "</td></tr>
  <tr><td style='padding:4px 12px 4px 0;font-weight:bold;'>Jahr des Verlusts:</td><td>" . htmlspecialchars((string)$year) . "</td></tr>
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
    error_log('contact.php mail error: ' . $e->getMessage());
}

echo json_encode(['success' => true, 'message' => 'Vielen Dank! Wir melden uns schnellstmöglich bei Ihnen.']);
