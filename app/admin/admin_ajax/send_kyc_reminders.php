<?php
/**
 * Send KYC Reminder Emails
 * Uses the notif:kyc_required notification template via AdminEmailHelper.
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

    // Fetch active, verified users without completed KYC
    if ($currentAdminRole === 'superadmin') {
        $stmt = $pdo->prepare("
            SELECT u.id, u.email, u.first_name, u.last_name
            FROM users u
            LEFT JOIN kyc_verification_requests k ON u.id = k.user_id
            WHERE u.status = 'active'
              AND u.is_verified = 1
              AND (k.id IS NULL OR k.status IN ('pending', 'rejected', 'none'))
            GROUP BY u.id
            ORDER BY u.id DESC
            LIMIT 200
        ");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT u.id, u.email, u.first_name, u.last_name
            FROM users u
            LEFT JOIN kyc_verification_requests k ON u.id = k.user_id
            WHERE u.status = 'active'
              AND u.is_verified = 1
              AND u.admin_id = ?
              AND (k.id IS NULL OR k.status IN ('pending', 'rejected', 'none'))
            GROUP BY u.id
            ORDER BY u.id DESC
            LIMIT 200
        ");
        $stmt->execute([$currentAdminId]);
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo json_encode([
            'success' => true,
            'message' => 'Keine Benutzer ohne abgeschlossene KYC-Verifizierung gefunden.',
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
            $result = $emailHelper->sendTemplateEmail('notif:kyc_required', (int)$user['id']);
            if ($result) {
                $sent++;
            } else {
                $failed++;
            }
        } catch (Exception $e) {
            $failed++;
            error_log("KYC reminder error for user {$user['id']}: " . $e->getMessage());
        }
    }

    // Audit log
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $pdo->prepare(
        "INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at)
         VALUES (?, 'bulk_email', 'users', 0, ?, ?, NOW())"
    )->execute([
        $currentAdminId,
        json_encode(['template' => 'kyc_required', 'sent' => $sent, 'failed' => $failed]),
        $ip,
    ]);

    echo json_encode([
        'success' => true,
        'message' => "{$sent} KYC-Erinnerung(en) erfolgreich versendet." . ($failed > 0 ? " {$failed} fehlgeschlagen." : ''),
        'sent'    => $sent,
        'failed'  => $failed,
        'total'   => count($users),
    ]);

} catch (PDOException $e) {
    error_log("send_kyc_reminders DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("send_kyc_reminders error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Fehler: ' . $e->getMessage()]);
}
