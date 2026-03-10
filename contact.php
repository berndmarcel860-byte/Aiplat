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
// DB connection via app/config.php
// ------------------------------------------------------------------
require_once __DIR__ . '/app/config.php';
// $pdo is now available from config.php

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
// Send confirmation email via EmailHelper (best-effort, non-fatal)
// ------------------------------------------------------------------
try {
    require_once __DIR__ . '/app/EmailHelper.php';
    $emailHelper = new EmailHelper($pdo);
    $emailHelper->sendRegisterRequestEmail([
        'first_name' => $firstName,
        'last_name'  => $lastName,
        'email'      => $email,
        'phone'      => $phone,
        'amount'     => $amountLabel,
        'year'       => $year,
        'platforms'  => $platforms,
        'details'    => $details,
    ]);
} catch (Throwable $e) {
    error_log('contact.php mail error: ' . $e->getMessage());
}

echo json_encode(['success' => true, 'message' => 'Vielen Dank! Wir melden uns schnellstmöglich bei Ihnen.']);
