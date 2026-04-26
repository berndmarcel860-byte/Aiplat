<?php
/**
 * save_wa_settings.php — Save WhatsApp Business Cloud API settings.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']); exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

// CSRF check
if (empty($input['csrf_token']) || $input['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']); exit;
}

$phoneNumberId = trim($input['phone_number_id'] ?? '');
$accessToken   = trim($input['access_token'] ?? '');
$isEnabled     = isset($input['is_enabled']) ? (int)(bool)$input['is_enabled'] : 0;

try {
    // Ensure the table/row exists (graceful bootstrap)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `wa_settings` (
        `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `phone_number_id` VARCHAR(64)  NOT NULL DEFAULT '',
        `access_token`    TEXT         NOT NULL,
        `is_enabled`      TINYINT(1)   NOT NULL DEFAULT 0,
        `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("INSERT IGNORE INTO `wa_settings` (`id`) VALUES (1)");

    $stmt = $pdo->prepare(
        "UPDATE wa_settings SET phone_number_id=?, access_token=?, is_enabled=?, updated_at=NOW() WHERE id=1"
    );
    $stmt->execute([$phoneNumberId, $accessToken, $isEnabled]);

    echo json_encode(['success' => true, 'message' => 'WhatsApp-Einstellungen gespeichert']);
} catch (PDOException $e) {
    error_log('save_wa_settings: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
}
