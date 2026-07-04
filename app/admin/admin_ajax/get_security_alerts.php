<?php
/**
 * Admin AJAX: Payment Security Alerts
 * Supports: list all, get single, mark reviewed, mark all reviewed.
 */
require_once '../admin_session.php';
require_once '../ticket_address_filter.php';   // provides mark_reviewed actions via main page POST too
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// ── Handle POST (review) actions ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_reviewed') {
        $alertId = (int)($_POST['alert_id'] ?? 0);
        if (!$alertId) {
            echo json_encode(['success' => false, 'message' => 'Ungültige Alert-ID']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("
                UPDATE payment_security_alerts
                SET is_reviewed = 1, reviewed_by = ?, reviewed_at = NOW(), review_note = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$_SESSION['admin_id'],
                trim($_POST['review_note'] ?? ''),
                $alertId,
            ]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'mark_all_reviewed') {
        try {
            $pdo->prepare("
                UPDATE payment_security_alerts SET is_reviewed = 1, reviewed_by = ?, reviewed_at = NOW()
                WHERE is_reviewed = 0
            ")->execute([(int)$_SESSION['admin_id']]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unbekannte Aktion']);
    exit;
}

// ── GET: single alert detail ──────────────────────────────────────────────
if (!empty($_GET['id'])) {
    $alertId = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("
            SELECT a.*,
                   adm.name  AS admin_name,
                   adm.email AS admin_email,
                   CONCAT(u.first_name, ' ', u.last_name) AS user_name,
                   u.email AS user_email
            FROM payment_security_alerts a
            LEFT JOIN admins adm ON adm.id = a.admin_id
            LEFT JOIN users  u   ON u.id   = a.user_id
            WHERE a.id = ?
        ");
        $stmt->execute([$alertId]);
        $alert = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$alert) {
            echo json_encode(['success' => false, 'message' => 'Alert nicht gefunden']);
            exit;
        }
        echo json_encode(['success' => true, 'alert' => $alert]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── GET: list all alerts ──────────────────────────────────────────────────
try {
    $stmt = $pdo->query("
        SELECT a.id, a.channel, a.admin_id, a.user_id, a.entity_id,
               a.addresses_found, a.is_reviewed, a.reviewed_at,
               DATE_FORMAT(a.created_at, '%d.%m.%Y %H:%i') AS created_at,
               adm.name  AS admin_name,
               CONCAT(u.first_name, ' ', u.last_name) AS user_name
        FROM payment_security_alerts a
        LEFT JOIN admins adm ON adm.id = a.admin_id
        LEFT JOIN users  u   ON u.id   = a.user_id
        ORDER BY a.is_reviewed ASC, a.created_at DESC
        LIMIT 500
    ");
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'alerts' => $alerts]);
} catch (Exception $e) {
    // Table may not exist yet
    echo json_encode(['success' => true, 'alerts' => [], 'note' => $e->getMessage()]);
}
