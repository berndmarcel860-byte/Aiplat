<?php
/**
 * test_whatsapp.php — Send a test WhatsApp message to verify settings.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']); exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($input['csrf_token']) || $input['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']); exit;
}

$toPhone = trim($input['to_phone'] ?? '');
if (!$toPhone) {
    echo json_encode(['success' => false, 'message' => 'Bitte geben Sie eine Telefonnummer an']); exit;
}

require_once '../../WhatsAppHelper.php';
$wa = new WhatsAppHelper($pdo);
$ok = $wa->sendTextMessage($toPhone, '✅ WhatsApp-Test erfolgreich! Ihre Einstellungen sind korrekt konfiguriert.');

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Testnachricht erfolgreich gesendet']);
} else {
    echo json_encode(['success' => false, 'message' => 'Senden fehlgeschlagen – prüfen Sie Ihre Zugangsdaten und die Empfängernummer']);
}
