<?php
require_once '../config.php';
require_once '../session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Ungültige Anfragemethode', 405);
    }
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Nicht autorisiert', 401);
    }

    // Validate CSRF
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $csrfToken = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
    if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        throw new Exception('Ungültiges Sicherheits-Token', 403);
    }

    $reference = trim($input['reference'] ?? '');
    if (empty($reference)) {
        throw new Exception('Fehlende Einzahlungsreferenz', 400);
    }

    // Fetch the deposit + escrow account (must belong to this user)
    $stmt = $pdo->prepare("
        SELECT d.id AS deposit_id, d.status AS dep_status,
               e.id AS escrow_id, e.status AS escrow_status
        FROM deposits d
        LEFT JOIN escrow_accounts e ON e.deposit_id = d.id
        WHERE d.reference = :ref AND d.user_id = :uid
        LIMIT 1
    ");
    $stmt->execute([':ref' => $reference, ':uid' => (int)$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('Einzahlung nicht gefunden', 404);
    }
    if (!$row['escrow_id']) {
        throw new Exception('Kein Treuhandkonto für diese Einzahlung gefunden', 400);
    }
    if (!in_array($row['escrow_status'], ['holding', 'verified'], true)) {
        throw new Exception('Die Treuhandfreigabe ist für diesen Status nicht möglich: ' . $row['escrow_status'], 400);
    }

    // Mark escrow as released (user confirmed withdrawal received)
    $now = date('Y-m-d H:i:s');
    $upd = $pdo->prepare("
        UPDATE escrow_accounts
        SET status = 'released', released_at = :now, release_reason = 'user_confirmed_withdrawal'
        WHERE id = :eid AND status IN ('holding','verified')
    ");
    $upd->execute([':now' => $now, ':eid' => (int)$row['escrow_id']]);

    if ($upd->rowCount() === 0) {
        throw new Exception('Freigabe fehlgeschlagen oder bereits freigegeben', 400);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Vielen Dank! Die Treuhandmittel wurden erfolgreich freigegeben.'
    ]);

} catch (Exception $e) {
    $code = in_array($e->getCode(), [400, 401, 403, 404, 405]) ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
