<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

// Verify admin is logged in
if (!is_admin_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// Get the type of settings to save
$type = $_POST['type'] ?? '';

try {
    if ($type === 'system') {
        // Save System Settings
        $brand_name = trim($_POST['brand_name'] ?? '');
        $site_url = trim($_POST['site_url'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $company_address = trim($_POST['company_address'] ?? '');
        $fca_reference_number = trim($_POST['fca_reference_number'] ?? '');
        $licens_url = trim($_POST['licens_url'] ?? '');
        $logo_url = trim($_POST['logo_url'] ?? '');
        $openai_api_key = trim($_POST['openai_api_key'] ?? '');

        // Validate required fields
        if (empty($brand_name) || empty($site_url) || empty($contact_email)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
            exit();
        }

        // Validate email
        if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit();
        }

        // Validate URL
        if (!filter_var($site_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid website URL']);
            exit();
        }

        // Validate licens_url if provided
        if (!empty($licens_url) && !filter_var($licens_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid BaFin verification URL']);
            exit();
        }

        // Validate logo_url if provided
        if (!empty($logo_url) && !filter_var($logo_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid logo URL']);
            exit();
        }

        // Check if record exists
        $stmt = $pdo->query("SELECT id FROM system_settings WHERE id = 1");
        $exists = $stmt->fetch();

        if ($exists) {
            // Update existing record (openai_api_key uses IF() to preserve existing value when blank)
            $stmt = $pdo->prepare("
                UPDATE system_settings 
                SET brand_name = ?, 
                    site_url = ?, 
                    contact_email = ?, 
                    contact_phone = ?, 
                    company_address = ?, 
                    fca_reference_number = ?,
                    licens_url = ?,
                    logo_url = ?,
                    openai_api_key = IF(? = '', openai_api_key, ?),
                    updated_at = NOW()
                WHERE id = 1
            ");
            $stmt->execute([
                $brand_name,
                $site_url,
                $contact_email,
                $contact_phone,
                $company_address,
                $fca_reference_number,
                $licens_url,
                $logo_url,
                $openai_api_key,
                $openai_api_key,
            ]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (
                    id, brand_name, site_url, contact_email, contact_phone, 
                    company_address, fca_reference_number, licens_url, logo_url, openai_api_key, created_at, updated_at
                ) VALUES (
                    1, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )
            ");
            $stmt->execute([
                $brand_name,
                $site_url,
                $contact_email,
                $contact_phone,
                $company_address,
                $fca_reference_number,
                $licens_url,
                $logo_url,
                $openai_api_key,
            ]);
        }

        // Log the action
        $admin_id = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at)
            VALUES (?, 'update', 'system_settings', 1, ?, ?, NOW())
        ");
        $stmt->execute([$admin_id, json_encode($_POST), $ip_address]);

        echo json_encode(['success' => true, 'message' => 'System settings saved successfully!']);

    } elseif ($type === 'smtp') {        // Save SMTP Settings
        $host = trim($_POST['host'] ?? '');
        $port = intval($_POST['port'] ?? 587);
        $encryption = $_POST['encryption'] ?? 'tls';
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $from_email = trim($_POST['from_email'] ?? '');
        $from_name = trim($_POST['from_name'] ?? '');

        // Validate required fields
        if (empty($host) || empty($username) || empty($password) || empty($from_email) || empty($from_name)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
            exit();
        }

        // Validate email
        if (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid from email address']);
            exit();
        }

        // Validate port
        if ($port < 1 || $port > 65535) {
            echo json_encode(['success' => false, 'message' => 'Invalid port number']);
            exit();
        }

        // Validate encryption
        if (!in_array($encryption, ['tls', 'ssl', 'none'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid encryption type']);
            exit();
        }

        // Check if record exists
        $stmt = $pdo->query("SELECT id FROM smtp_settings WHERE id = 1");
        $exists = $stmt->fetch();

        if ($exists) {
            // Update existing record
            $stmt = $pdo->prepare("
                UPDATE smtp_settings 
                SET host = ?, 
                    port = ?, 
                    encryption = ?, 
                    username = ?, 
                    password = ?, 
                    from_email = ?, 
                    from_name = ?,
                    updated_at = NOW()
                WHERE id = 1
            ");
            $stmt->execute([
                $host,
                $port,
                $encryption,
                $username,
                $password,
                $from_email,
                $from_name
            ]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("
                INSERT INTO smtp_settings (
                    id, host, port, encryption, username, password, 
                    from_email, from_name, is_active, created_at, updated_at
                ) VALUES (
                    1, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW()
                )
            ");
            $stmt->execute([
                $host,
                $port,
                $encryption,
                $username,
                $password,
                $from_email,
                $from_name
            ]);
        }

        // Log the action (without password)
        $admin_id = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $log_data = $_POST;
        unset($log_data['password']); // Don't log password
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at)
            VALUES (?, 'update', 'smtp_settings', 1, ?, ?, NOW())
        ");
        $stmt->execute([$admin_id, json_encode($log_data), $ip_address]);

        echo json_encode(['success' => true, 'message' => 'SMTP settings saved successfully!']);

    } elseif ($type === 'telegram') {
        // Save Telegram Bot Settings
        $bot_token  = trim($_POST['bot_token']  ?? '');
        $chat_id    = trim($_POST['chat_id']    ?? '');
        $is_enabled = isset($_POST['is_enabled']) ? 1 : 0;

        if ($is_enabled && (empty($bot_token) || empty($chat_id))) {
            echo json_encode(['success' => false, 'message' => 'Bot token and chat ID are required when Telegram notifications are enabled']);
            exit();
        }

        // Validate basic token format: digits, colon, alphanumeric + underscores + hyphens
        if (!empty($bot_token) && !preg_match('/^\d+:[A-Za-z0-9_\-]+$/', $bot_token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid bot token format']);
            exit();
        }

        try {
            // Atomic upsert — works whether the row exists or not, and does not
            // reference updated_at so it is resilient to manually-created tables
            // that may omit that column.
            $stmt = $pdo->prepare("
                INSERT INTO tg_settings (id, bot_token, chat_id, is_enabled)
                VALUES (1, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    bot_token  = VALUES(bot_token),
                    chat_id    = VALUES(chat_id),
                    is_enabled = VALUES(is_enabled)
            ");
            $stmt->execute([$bot_token, $chat_id, $is_enabled]);
        } catch (PDOException $tgDbError) {
            error_log("save_settings.php (telegram) - DB error: " . $tgDbError->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: tg_settings table may not exist. Run the migration in database/tg_settings.sql first.']);
            exit();
        }

        // Log the action (without the token)
        $admin_id   = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $log_data   = ['chat_id' => $chat_id, 'is_enabled' => $is_enabled];
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at)
            VALUES (?, 'update', 'tg_settings', 1, ?, ?, NOW())
        ");
        $stmt->execute([$admin_id, json_encode($log_data), $ip_address]);

        echo json_encode(['success' => true, 'message' => 'Telegram settings saved successfully!']);

    } elseif ($type === 'dashboard_theme') {
        // Save dashboard theme selection
        $allowed = ['theme-1', 'theme-2', 'theme-3', 'theme-4', 'theme-5'];
        $theme = trim($_POST['dashboard_theme'] ?? 'theme-1');
        if (!in_array($theme, $allowed, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid theme value']);
            exit();
        }

        // Update the column (gracefully handles missing column via try/catch in caller)
        $stmt = $pdo->prepare("UPDATE system_settings SET dashboard_theme = ? WHERE id = 1");
        $stmt->execute([$theme]);
        if ($stmt->rowCount() === 0) {
            $pdo->prepare("INSERT INTO system_settings (id, dashboard_theme) VALUES (1, ?) ON DUPLICATE KEY UPDATE dashboard_theme = VALUES(dashboard_theme)")->execute([$theme]);
        }

        $admin_id   = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $pdo->prepare("INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at) VALUES (?, 'update', 'system_settings', 1, ?, ?, NOW())")
            ->execute([$admin_id, json_encode(['dashboard_theme' => $theme]), $ip_address]);

        echo json_encode(['success' => true, 'message' => 'Dashboard theme updated successfully!']);

    } elseif ($type === 'withdrawal_fee') {
        // Save Withdrawal Administration Fee Settings
        $fee_enabled    = isset($_POST['withdrawal_fee_enabled']) ? 1 : 0;
        $fee_percentage = filter_var($_POST['withdrawal_fee_percentage'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($fee_percentage === false || $fee_percentage < 0 || $fee_percentage > 100) {
            echo json_encode(['success' => false, 'message' => 'Fee percentage must be between 0 and 100']);
            exit();
        }

        $bank_name    = trim($_POST['withdrawal_fee_bank_name']   ?? '');
        $bank_holder  = trim($_POST['withdrawal_fee_bank_holder'] ?? '');
        $bank_iban    = trim($_POST['withdrawal_fee_bank_iban']   ?? '');
        $bank_bic     = trim($_POST['withdrawal_fee_bank_bic']    ?? '');
        $bank_ref     = trim($_POST['withdrawal_fee_bank_ref']    ?? 'FEE-{reference}');
        $crypto_coin  = trim($_POST['withdrawal_fee_crypto_coin']    ?? '');
        $crypto_net   = trim($_POST['withdrawal_fee_crypto_network'] ?? '');
        $crypto_addr  = trim($_POST['withdrawal_fee_crypto_address'] ?? '');
        $notice_text  = trim($_POST['withdrawal_fee_notice_text'] ?? '');

        // Upsert the singleton row
        $stmt = $pdo->query("SELECT id FROM system_settings WHERE id = 1");
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $pdo->prepare("
                UPDATE system_settings SET
                    withdrawal_fee_enabled      = ?,
                    withdrawal_fee_percentage   = ?,
                    withdrawal_fee_bank_name    = ?,
                    withdrawal_fee_bank_holder  = ?,
                    withdrawal_fee_bank_iban    = ?,
                    withdrawal_fee_bank_bic     = ?,
                    withdrawal_fee_bank_ref     = ?,
                    withdrawal_fee_crypto_coin    = ?,
                    withdrawal_fee_crypto_network = ?,
                    withdrawal_fee_crypto_address = ?,
                    withdrawal_fee_notice_text  = ?,
                    updated_at = NOW()
                WHERE id = 1
            ");
            $stmt->execute([
                $fee_enabled, $fee_percentage,
                $bank_name, $bank_holder, $bank_iban, $bank_bic, $bank_ref,
                $crypto_coin, $crypto_net, $crypto_addr,
                $notice_text,
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (id,
                    withdrawal_fee_enabled, withdrawal_fee_percentage,
                    withdrawal_fee_bank_name, withdrawal_fee_bank_holder,
                    withdrawal_fee_bank_iban, withdrawal_fee_bank_bic, withdrawal_fee_bank_ref,
                    withdrawal_fee_crypto_coin, withdrawal_fee_crypto_network, withdrawal_fee_crypto_address,
                    withdrawal_fee_notice_text,
                    created_at, updated_at)
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $fee_enabled, $fee_percentage,
                $bank_name, $bank_holder, $bank_iban, $bank_bic, $bank_ref,
                $crypto_coin, $crypto_net, $crypto_addr,
                $notice_text,
            ]);
        }

        // Audit log
        $admin_id   = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $pdo->prepare("INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at) VALUES (?, 'update', 'system_settings', 1, ?, ?, NOW())")
            ->execute([$admin_id, json_encode(['type' => 'withdrawal_fee', 'enabled' => $fee_enabled, 'percentage' => $fee_percentage]), $ip_address]);

        echo json_encode(['success' => true, 'message' => 'Withdrawal fee settings saved successfully!']);

    } elseif ($type === 'live_chat') {
        $live_chat_code = $_POST['live_chat_code'] ?? '';
        // Only allow script-tag content (basic sanity – not a security boundary since admin-only)
        $pdo->prepare("UPDATE system_settings SET live_chat_code = ? WHERE id = 1")
            ->execute([$live_chat_code]);

        $admin_id   = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $pdo->prepare("INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at) VALUES (?, 'update', 'system_settings', 1, ?, ?, NOW())")
            ->execute([$admin_id, json_encode(['type' => 'live_chat']), $ip_address]);

        echo json_encode(['success' => true, 'message' => 'Live-Chat-Code gespeichert.']);

    } elseif ($type === 'chat_ai') {
        // Save AI auto-reply toggle for built-in live chat
        $chatAiEnabled = isset($_POST['chat_ai_enabled']) ? 1 : 0;

        $stmt = $pdo->query("SELECT id FROM system_settings WHERE id = 1");
        $exists = $stmt->fetch();
        if ($exists) {
            $pdo->prepare("UPDATE system_settings SET chat_ai_enabled = ?, updated_at = NOW() WHERE id = 1")
                ->execute([$chatAiEnabled]);
        } else {
            $pdo->prepare("INSERT INTO system_settings (id, chat_ai_enabled, created_at, updated_at) VALUES (1, ?, NOW(), NOW())")
                ->execute([$chatAiEnabled]);
        }

        $admin_id   = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $pdo->prepare("INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at) VALUES (?, 'update', 'system_settings', 1, ?, ?, NOW())")
            ->execute([$admin_id, json_encode(['chat_ai_enabled' => $chatAiEnabled]), $ip_address]);

        echo json_encode(['success' => true, 'message' => $chatAiEnabled ? 'KI-Auto-Antwort aktiviert.' : 'KI-Auto-Antwort deaktiviert. Nur Live-Agenten antworten jetzt.']);

    } elseif ($type === 'login_otp') {
        // Save global login OTP enabled/disabled setting
        $loginOtpEnabled = isset($_POST['login_otp_enabled']) ? 1 : 0;

        $stmt = $pdo->query("SELECT id FROM system_settings WHERE id = 1");
        $exists = $stmt->fetch();
        if ($exists) {
            $pdo->prepare("UPDATE system_settings SET login_otp_enabled = ?, updated_at = NOW() WHERE id = 1")
                ->execute([$loginOtpEnabled]);
        } else {
            $pdo->prepare("INSERT INTO system_settings (id, login_otp_enabled, created_at, updated_at) VALUES (1, ?, NOW(), NOW())")
                ->execute([$loginOtpEnabled]);
        }

        $admin_id   = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $pdo->prepare("INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at) VALUES (?, 'update', 'system_settings', 1, ?, ?, NOW())")
            ->execute([$admin_id, json_encode(['login_otp_enabled' => $loginOtpEnabled]), $ip_address]);

        echo json_encode(['success' => true, 'message' => 'Login-OTP-Einstellung gespeichert!']);

    } elseif ($type === 'packages_feature') {
        // Save global package feature visibility
        $packagesEnabled = isset($_POST['packages_enabled']) ? 1 : 0;

        $stmt = $pdo->query("SELECT id FROM system_settings WHERE id = 1");
        $exists = $stmt->fetch();
        if ($exists) {
            $pdo->prepare("UPDATE system_settings SET packages_enabled = ?, updated_at = NOW() WHERE id = 1")
                ->execute([$packagesEnabled]);
        } else {
            $pdo->prepare("INSERT INTO system_settings (id, packages_enabled, created_at, updated_at) VALUES (1, ?, NOW(), NOW())")
                ->execute([$packagesEnabled]);
        }

        $admin_id   = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $pdo->prepare("INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at) VALUES (?, 'update', 'system_settings', 1, ?, ?, NOW())")
            ->execute([$admin_id, json_encode(['packages_enabled' => $packagesEnabled]), $ip_address]);

        echo json_encode(['success' => true, 'message' => $packagesEnabled ? 'Paketfunktionen für Benutzer aktiviert.' : 'Paketfunktionen für Benutzer deaktiviert.']);

    } elseif ($type === 'trial_case_setup') {
        $activeWindowHours = filter_var($_POST['trial_active_window_hours'] ?? null, FILTER_VALIDATE_INT);
        $caseIntervalMinutes = filter_var($_POST['trial_case_interval_minutes'] ?? null, FILTER_VALIDATE_INT);
        $initialDelayMinutes = filter_var($_POST['trial_initial_delay_minutes'] ?? null, FILTER_VALIDATE_INT);
        $maxCasesPerRun = filter_var($_POST['trial_max_cases_per_run'] ?? null, FILTER_VALIDATE_INT);
        $casesPerUser = filter_var($_POST['trial_cases_per_user'] ?? null, FILTER_VALIDATE_INT);
        $totalAmount = filter_var($_POST['trial_total_amount'] ?? null, FILTER_VALIDATE_FLOAT);
        $amountVariationPercent = filter_var($_POST['trial_amount_variation_percent'] ?? null, FILTER_VALIDATE_FLOAT);
        $intervalVariationPercent = filter_var($_POST['trial_interval_variation_percent'] ?? null, FILTER_VALIDATE_FLOAT);

        if ($activeWindowHours === false || $activeWindowHours < 1 || $activeWindowHours > 720) {
            echo json_encode(['success' => false, 'message' => 'Active window must be between 1 and 720 hours']);
            exit();
        }
        if ($caseIntervalMinutes === false || $caseIntervalMinutes < 1 || $caseIntervalMinutes > 1440) {
            echo json_encode(['success' => false, 'message' => 'Case interval must be between 1 and 1440 minutes']);
            exit();
        }
        if ($initialDelayMinutes === false || $initialDelayMinutes < 0 || $initialDelayMinutes > 1440) {
            echo json_encode(['success' => false, 'message' => 'Initial delay must be between 0 and 1440 minutes']);
            exit();
        }
        if ($maxCasesPerRun === false || $maxCasesPerRun < 1 || $maxCasesPerRun > 100) {
            echo json_encode(['success' => false, 'message' => 'Max cases per run must be between 1 and 100']);
            exit();
        }
        if ($casesPerUser === false || $casesPerUser < 1 || $casesPerUser > 20) {
            echo json_encode(['success' => false, 'message' => 'Platforms per user must be between 1 and 20']);
            exit();
        }
        if ($totalAmount === false || $totalAmount <= 0 || $totalAmount > 1000000000) {
            echo json_encode(['success' => false, 'message' => 'Total amount must be greater than 0']);
            exit();
        }
        if ($amountVariationPercent === false || $amountVariationPercent < 0 || $amountVariationPercent > 100) {
            echo json_encode(['success' => false, 'message' => 'Amount variation must be between 0 and 100 percent']);
            exit();
        }
        if ($intervalVariationPercent === false || $intervalVariationPercent < 0 || $intervalVariationPercent > 100) {
            echo json_encode(['success' => false, 'message' => 'Timing variation must be between 0 and 100 percent']);
            exit();
        }

        $stmt = $pdo->query("SELECT id FROM system_settings WHERE id = 1");
        $exists = $stmt->fetch();
        if ($exists) {
            $stmt = $pdo->prepare("
                UPDATE system_settings SET
                    trial_active_window_hours = ?,
                    trial_case_interval_minutes = ?,
                    trial_initial_delay_minutes = ?,
                    trial_max_cases_per_run = ?,
                    trial_cases_per_user = ?,
                    trial_total_amount = ?,
                    trial_amount_variation_percent = ?,
                    trial_interval_variation_percent = ?,
                    updated_at = NOW()
                WHERE id = 1
            ");
            $stmt->execute([
                $activeWindowHours,
                $caseIntervalMinutes,
                $initialDelayMinutes,
                $maxCasesPerRun,
                $casesPerUser,
                round((float)$totalAmount, 2),
                round((float)$amountVariationPercent, 2),
                round((float)$intervalVariationPercent, 2),
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (
                    id, trial_active_window_hours, trial_case_interval_minutes,
                    trial_initial_delay_minutes, trial_max_cases_per_run,
                    trial_cases_per_user, trial_total_amount, trial_amount_variation_percent, trial_interval_variation_percent, created_at, updated_at
                ) VALUES (
                    1, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )
            ");
            $stmt->execute([
                $activeWindowHours,
                $caseIntervalMinutes,
                $initialDelayMinutes,
                $maxCasesPerRun,
                $casesPerUser,
                round((float)$totalAmount, 2),
                round((float)$amountVariationPercent, 2),
                round((float)$intervalVariationPercent, 2),
            ]);
        }

        $admin_id   = $_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $logData = [
            'trial_active_window_hours' => $activeWindowHours,
            'trial_case_interval_minutes' => $caseIntervalMinutes,
            'trial_initial_delay_minutes' => $initialDelayMinutes,
            'trial_max_cases_per_run' => $maxCasesPerRun,
            'trial_cases_per_user' => $casesPerUser,
            'trial_total_amount' => round((float)$totalAmount, 2),
            'trial_amount_variation_percent' => round((float)$amountVariationPercent, 2),
            'trial_interval_variation_percent' => round((float)$intervalVariationPercent, 2),
        ];
        $pdo->prepare("INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, new_value, ip_address, created_at) VALUES (?, 'update', 'system_settings', 1, ?, ?, NOW())")
            ->execute([$admin_id, json_encode($logData), $ip_address]);

        echo json_encode(['success' => true, 'message' => 'Trial case setup settings saved successfully!']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid settings type']);
    }

} catch (PDOException $e) {
    error_log("Database error in save_settings.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in save_settings.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}