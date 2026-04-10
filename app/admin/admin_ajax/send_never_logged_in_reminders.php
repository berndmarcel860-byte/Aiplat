<?php
/**
 * Send "Never Logged In" Reminder Emails
 * Sends the notif:never_logged_in notification template to all users who
 * registered but have never logged in.
 */
require_once '../admin_session.php';
require_once __DIR__ . '/../AdminEmailHelper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $currentAdminId   = (int)$_SESSION['admin_id'];
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';

    // Fetch users who have never logged in and are active
    if ($currentAdminRole === 'superadmin') {
        $stmt = $pdo->prepare("
            SELECT id, email, first_name, last_name
            FROM users
            WHERE status = 'active'
              AND (last_login IS NULL OR last_login = '0000-00-00 00:00:00')
            ORDER BY created_at DESC
            LIMIT 200
        ");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT id, email, first_name, last_name
            FROM users
            WHERE status = 'active'
              AND admin_id = ?
              AND (last_login IS NULL OR last_login = '0000-00-00 00:00:00')
            ORDER BY created_at DESC
            LIMIT 200
        ");
        $stmt->execute([$currentAdminId]);
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo json_encode([
            'success' => true,
            'message' => 'Keine Benutzer gefunden, die sich noch nie angemeldet haben.',
            'sent'    => 0,
            'failed'  => 0,
        ]);
        exit();
    }

    $emailHelper = new AdminEmailHelper($pdo);
    $sent        = 0;
    $failed      = 0;

    foreach ($users as $user) {
        try {
            $result = $emailHelper->sendTemplateEmail('notif:never_logged_in', (int)$user['id']);
            if ($result) {
                $sent++;
            } else {
                $failed++;
            }
        } catch (Exception $e) {
            $failed++;
            error_log("never_logged_in reminder error for user {$user['id']}: " . $e->getMessage());
        }
    }

    // Audit log
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $pdo->prepare(
        "INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at)
         VALUES (?, 'bulk_email', 'users', 0, ?, ?, NOW())"
    )->execute([
        $currentAdminId,
        json_encode(['template' => 'never_logged_in', 'sent' => $sent, 'failed' => $failed]),
        $ip,
    ]);

    echo json_encode([
        'success' => true,
        'message' => "{$sent} Erinnerungs-E-Mail(s) erfolgreich versendet." . ($failed > 0 ? " {$failed} fehlgeschlagen." : ''),
        'sent'    => $sent,
        'failed'  => $failed,
        'total'   => count($users),
    ]);

} catch (PDOException $e) {
    error_log("send_never_logged_in_reminders DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("send_never_logged_in_reminders error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Fehler: ' . $e->getMessage()]);
}
