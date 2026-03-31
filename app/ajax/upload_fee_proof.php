<?php
/**
 * upload_fee_proof.php
 * Accepts a user's proof-of-fee-payment file for a withdrawal.
 * POST fields:
 *   withdrawal_id  – integer, withdrawal that belongs to the logged-in user
 *   fee_proof      – uploaded file (image/pdf, max 5 MB)
 *   csrf_token     – CSRF token
 */
require_once __DIR__ . '/../session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Ungültige Anfragemethode', 405);
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Nicht autorisiert – bitte einloggen', 401);
    }

    // CSRF check
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Sicherheitsfehler – ungültiges CSRF-Token', 403);
    }

    $withdrawalId = isset($_POST['withdrawal_id']) ? (int)$_POST['withdrawal_id'] : 0;
    if ($withdrawalId <= 0) {
        throw new Exception('Ungültige Auszahlungs-ID', 400);
    }

    // Verify the withdrawal belongs to this user and is still pending/processing
    $stmt = $pdo->prepare(
        "SELECT id, reference, status FROM withdrawals WHERE id = ? AND user_id = ? LIMIT 1"
    );
    $stmt->execute([$withdrawalId, $_SESSION['user_id']]);
    $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$withdrawal) {
        throw new Exception('Auszahlung nicht gefunden', 404);
    }
    if (!in_array($withdrawal['status'], ['pending', 'processing'], true)) {
        throw new Exception('Nachweis kann nur für ausstehende Auszahlungen hochgeladen werden', 400);
    }

    // File validation
    if (empty($_FILES['fee_proof']) || $_FILES['fee_proof']['error'] === UPLOAD_ERR_NO_FILE) {
        throw new Exception('Bitte wählen Sie eine Datei aus', 400);
    }
    $file = $_FILES['fee_proof'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Fehler beim Datei-Upload (Code: ' . $file['error'] . ')', 400);
    }

    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $mimeType    = mime_content_type($file['tmp_name']);
    if (!in_array($mimeType, $allowedMime, true)) {
        throw new Exception('Nur JPG, PNG, GIF und PDF-Dateien sind erlaubt', 400);
    }

    $maxSize = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxSize) {
        throw new Exception('Die Datei darf maximal 5 MB groß sein', 400);
    }

    // Build upload path
    $uploadDir = __DIR__ . '/../../uploads/fee_proofs/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new Exception('Upload-Verzeichnis konnte nicht erstellt werden', 500);
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'fee_' . $withdrawalId . '_' . $_SESSION['user_id'] . '_' . uniqid() . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new Exception('Datei konnte nicht gespeichert werden', 500);
    }

    $dbPath = 'uploads/fee_proofs/' . $filename;

    // Persist to DB
    $upd = $pdo->prepare(
        "UPDATE withdrawals SET fee_proof_path = ? WHERE id = ? AND user_id = ?"
    );
    $upd->execute([$dbPath, $withdrawalId, $_SESSION['user_id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Nachweis erfolgreich hochgeladen. Unser Compliance-Team wird die Zahlung prüfen und Ihre Auszahlung freigeben.',
        'path'    => $dbPath,
    ]);

} catch (Exception $e) {
    http_response_code((int)($e->getCode() ?: 500));
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}

