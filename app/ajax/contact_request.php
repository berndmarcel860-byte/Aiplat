<?php
/**
 * contact_request.php
 *
 * Handles the POST submission from the register-page contact/request modal.
 * Saves the submitted data to the `register_request` table.
 *
 * Expected POST fields:
 *   first_name, last_name, email, phone, amount, year, platforms, details
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name']  ?? '');
$email     = trim($_POST['email']      ?? '');
$phone     = trim($_POST['phone']      ?? '');
$amount    = trim($_POST['amount']     ?? '');
$year      = (int)($_POST['year']      ?? 0);
$platforms = trim($_POST['platforms']  ?? '');
$details   = trim($_POST['details']    ?? '');

// Basic validation
if (!$firstName || !$lastName || !$email || !$phone || !$amount || !$year || !$details) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Alle Pflichtfelder müssen ausgefüllt werden.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige E-Mail-Adresse.']);
    exit;
}

if ($year < 2000 || $year > 2026) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültiges Jahr.']);
    exit;
}

$allowedAmounts = ['5000-20000', '20000-50000', '50000-100000', '100000-250000', '250000-500000', '500000+'];
if (!in_array($amount, $allowedAmounts, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültiger Verlustbetrag.']);
    exit;
}

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

try {
    $stmt = $pdo->prepare(
        "INSERT INTO register_request
             (first_name, last_name, email, phone, amount, year, platforms, details, ip_address)
         VALUES
             (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $firstName,
        $lastName,
        $email,
        $phone,
        $amount,
        $year,
        $platforms ?: null,
        $details,
        $ipAddress,
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('contact_request.php DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler. Bitte versuchen Sie es später erneut.']);
}
