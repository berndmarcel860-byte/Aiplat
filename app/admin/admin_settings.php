<?php
require_once 'admin_header.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get current system settings
$stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
$systemSettings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current SMTP settings
$stmt = $pdo->query("SELECT * FROM smtp_settings WHERE id = 1");
$smtpSettings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current Telegram settings
$tgSettings = ['bot_token' => '', 'chat_id' => '', 'is_enabled' => 0];
try {
    $stmt = $pdo->query("SELECT bot_token, chat_id, is_enabled FROM tg_settings WHERE id = 1 LIMIT 1");
    $tgRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($tgRow) {
        $tgSettings = $tgRow;
    }
} catch (PDOException $e) {
    // tg_settings table may not exist yet; use defaults
}

// Get current WhatsApp settings
$waSettings = ['phone_number_id' => '', 'access_token' => '', 'is_enabled' => 0];
try {
    $stmt = $pdo->query("SELECT phone_number_id, access_token, is_enabled FROM wa_settings WHERE id = 1 LIMIT 1");
    $waRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($waRow) {
        $waSettings = $waRow;
    }
} catch (PDOException $e) {
    // wa_settings table may not exist yet; use defaults
}

// Get withdrawal fee settings (columns may not exist yet if migration has not been run)
$withdrawalFeeSettings = [
    'withdrawal_fee_enabled'      => 0,
    'withdrawal_fee_percentage'   => '0.00',
    'withdrawal_fee_bank_name'    => '',
    'withdrawal_fee_bank_holder'  => '',
    'withdrawal_fee_bank_iban'    => '',
    'withdrawal_fee_bank_bic'     => '',
    'withdrawal_fee_bank_ref'     => 'FEE-{reference}',
    'withdrawal_fee_crypto_coin'  => '',
    'withdrawal_fee_crypto_network' => '',
    'withdrawal_fee_crypto_address' => '',
    'withdrawal_fee_notice_text'  => '',
];
try {
    $feeStmt = $pdo->query("SELECT withdrawal_fee_enabled, withdrawal_fee_percentage,
        withdrawal_fee_bank_name, withdrawal_fee_bank_holder,
        withdrawal_fee_bank_iban, withdrawal_fee_bank_bic, withdrawal_fee_bank_ref,
        withdrawal_fee_crypto_coin, withdrawal_fee_crypto_network, withdrawal_fee_crypto_address,
        withdrawal_fee_notice_text
        FROM system_settings WHERE id = 1 LIMIT 1");
    $feeRow = $feeStmt->fetch(PDO::FETCH_ASSOC);
    if ($feeRow) {
        // Merge only non-null values to preserve defaults for missing/null columns
        foreach ($feeRow as $k => $v) {
            if ($v !== null) {
                $withdrawalFeeSettings[$k] = $v;
            }
        }
    }
} catch (PDOException $e) {
    // Columns not yet added – migration pending; use defaults
}

// Get login OTP setting (column may not exist yet if migration not run)
$loginOtpEnabled = 1;
try {
    $otpStmt = $pdo->query("SELECT login_otp_enabled FROM system_settings WHERE id = 1 LIMIT 1");
    $otpRow  = $otpStmt->fetch(PDO::FETCH_ASSOC);
    if ($otpRow !== false && isset($otpRow['login_otp_enabled'])) {
        $loginOtpEnabled = (int)$otpRow['login_otp_enabled'];
    }
} catch (PDOException $e) { /* migration not yet run */ }

// Set defaults if no settings exist
if (!$systemSettings) {
    $systemSettings = [
        'brand_name' => '',
        'site_url' => '',
        'contact_email' => '',
        'contact_phone' => '',
        'company_address' => '',
        'fca_reference_number' => '',
        'logo_url' => '',
        'openai_api_key' => '',
    ];
} else {
    // Ensure openai_api_key key exists (column may not yet be migrated)
    if (!isset($systemSettings['openai_api_key'])) {
        $systemSettings['openai_api_key'] = '';
    }
    if (!isset($systemSettings['dashboard_theme'])) {
        $systemSettings['dashboard_theme'] = 'theme-1';
    }
    if (!isset($systemSettings['live_chat_code'])) {
        $systemSettings['live_chat_code'] = '';
    }
}

// Get chat AI auto-reply setting (column may not exist yet if migration not run)
$chatAiEnabled = 1;
try {
    $aiStmt = $pdo->query("SELECT chat_ai_enabled FROM system_settings WHERE id = 1 LIMIT 1");
    $aiRow  = $aiStmt->fetch(PDO::FETCH_ASSOC);
    if ($aiRow !== false && isset($aiRow['chat_ai_enabled'])) {
        $chatAiEnabled = (int)$aiRow['chat_ai_enabled'];
    }
} catch (PDOException $e) { /* migration not yet run */ }

// Get trial case setup cron settings (columns may not exist yet if migration not run)
$trialCaseSetupSettings = [
    'trial_active_window_hours' => 48,
    'trial_case_interval_minutes' => 5,
    'trial_initial_delay_minutes' => 5,
    'trial_max_cases_per_run' => 2,
    'trial_cases_per_user' => 3,
    'trial_total_amount' => '150000.00',
    'trial_amount_variation_percent' => '20.00',
    'trial_interval_variation_percent' => '35.00',
];
try {
    $trialStmt = $pdo->query("SELECT trial_active_window_hours, trial_case_interval_minutes, trial_initial_delay_minutes, trial_max_cases_per_run, trial_cases_per_user, trial_total_amount, trial_amount_variation_percent, trial_interval_variation_percent FROM system_settings WHERE id = 1 LIMIT 1");
    $trialRow = $trialStmt->fetch(PDO::FETCH_ASSOC);
    if ($trialRow) {
        foreach ($trialRow as $k => $v) {
            if ($v !== null && array_key_exists($k, $trialCaseSetupSettings)) {
                $trialCaseSetupSettings[$k] = $v;
            }
        }
    }
} catch (PDOException $e) { /* migration not yet run */ }

if (!$smtpSettings) {
    $smtpSettings = [
        'host' => '',
        'port' => 587,
        'encryption' => 'tls',
        'username' => '',
        'password' => '',
        'from_email' => '',
        'from_name' => ''
    ];
}
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">User Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Users</span>
            </nav>
        </div>
    </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#system-settings" role="tab">
                                <i class="fe fe-settings"></i> System Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#smtp-settings" role="tab">
                                <i class="fe fe-mail"></i> SMTP Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#telegram-settings" role="tab">
                                <i class="fe fe-send"></i> Telegram Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#whatsapp-settings" role="tab">
                                <i class="fe fe-message-circle"></i> WhatsApp Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#dashboard-design" role="tab">
                                <i class="fe fe-layout"></i> Dashboard Design
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#withdrawal-fee" role="tab">
                                <i class="fe fe-percent"></i> Withdrawal Fee
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#live-chat" role="tab">
                                <i class="fe fe-message-circle"></i> Live Chat
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#login-security" role="tab">
                                <i class="fe fe-shield"></i> Login Security
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#trial-case-setup" role="tab">
                                <i class="fe fe-clock"></i> Trial Case Setup
                            </a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content">
                        <!-- System Settings Tab -->
                        <div class="tab-pane fade show active" id="system-settings" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-header-title">Company & Brand Information</h4>
                                </div>
                                <div class="card-body">
                                    <form id="systemSettingsForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="brand_name">Brand Name *</label>
                                                    <input type="text" class="form-control" id="brand_name" name="brand_name" 
                                                           value="<?php echo htmlspecialchars($systemSettings['brand_name']); ?>" required>
                                                    <small class="form-text text-muted">Your company/brand name</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="site_url">Website URL *</label>
                                                    <input type="url" class="form-control" id="site_url" name="site_url" 
                                                           value="<?php echo htmlspecialchars($systemSettings['site_url']); ?>" required>
                                                    <small class="form-text text-muted">Your website URL (e.g., https://example.com)</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="contact_email">Contact Email *</label>
                                                    <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                                           value="<?php echo htmlspecialchars($systemSettings['contact_email']); ?>" required>
                                                    <small class="form-text text-muted">Primary contact email address</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="contact_phone">Contact Phone</label>
                                                    <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                                                           value="<?php echo htmlspecialchars($systemSettings['contact_phone']); ?>">
                                                    <small class="form-text text-muted">Phone number for customer support</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="company_address">Company Address</label>
                                            <textarea class="form-control" id="company_address" name="company_address" rows="3"><?php echo htmlspecialchars($systemSettings['company_address']); ?></textarea>
                                            <small class="form-text text-muted">Full company address</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="fca_reference_number">BaFin-Registernummer</label>
                                            <input type="text" class="form-control" id="fca_reference_number" name="fca_reference_number" 
                                                   value="<?php echo htmlspecialchars($systemSettings['fca_reference_number']); ?>">
                                            <small class="form-text text-muted">Registernummer der Bundesanstalt für Finanzdienstleistungsaufsicht (BaFin)</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="licens_url">BaFin-Datenbank URL</label>
                                            <input type="url" class="form-control" id="licens_url" name="licens_url"
                                                   value="<?php echo htmlspecialchars($systemSettings['licens_url'] ?? ''); ?>">
                                            <small class="form-text text-muted">Überprüfungslink zur BaFin-Unternehmensdatenbank (wird im Impressum angezeigt)</small>
                                        </div>

                                        <!-- OpenAI API Key -->
                                        <div class="form-group">
                                            <label for="openai_api_key">OpenAI API Key <span class="badge badge-info">AI Features</span></label>
                                            <input type="password" class="form-control" id="openai_api_key" name="openai_api_key"
                                                   autocomplete="new-password"
                                                   placeholder="<?php echo !empty($systemSettings['openai_api_key']) ? '••••••••• (key saved – enter new key to replace)' : 'sk-…'; ?>">
                                            <small class="form-text text-muted">
                                                Required for AI-assisted email content generation in the
                                                <a href="admin_notification_templates.php">Email Notification Templates</a> page.
                                                Get your key at <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">platform.openai.com</a>.
                                                Leave blank to keep the current key.
                                            </small>
                                        </div>

                                        <!-- Logo URL (hidden, updated by upload or manual entry) -->
                                        <input type="hidden" id="logo_url" name="logo_url" value="<?php echo htmlspecialchars($systemSettings['logo_url'] ?? ''); ?>">

                                        <hr class="my-4">

                                        <!-- Logo Upload Section -->
                                        <div class="form-group">
                                            <label>Site Logo</label>
                                            <div class="d-flex align-items-center mb-2">
                                                <?php if (!empty($systemSettings['logo_url'])): ?>
                                                    <img id="logoPreview" src="<?php echo htmlspecialchars($systemSettings['logo_url']); ?>"
                                                         alt="Current Logo" style="max-height:60px;max-width:200px;margin-right:16px;border:1px solid #e9ecef;border-radius:4px;padding:4px;background:#fff;">
                                                <?php else: ?>
                                                    <img id="logoPreview" src="" alt="" style="max-height:60px;max-width:200px;margin-right:16px;border:1px solid #e9ecef;border-radius:4px;padding:4px;background:#fff;display:none;">
                                                <?php endif; ?>
                                                <div>
                                                    <div class="custom-file" style="max-width:320px;">
                                                        <input type="file" class="custom-file-input" id="logoFileInput" accept="image/png,image/jpeg,image/gif,image/webp">
                                                        <label class="custom-file-label" for="logoFileInput">Choose logo file…</label>
                                                    </div>
                                                    <small class="form-text text-muted">PNG, JPG, GIF or WEBP · max 2 MB. The file will be saved as <code>/assets/img/logo.{ext}</code> and the URL will be stored automatically.</small>
                                                    <?php if (!empty($systemSettings['logo_url'])): ?>
                                                        <small class="form-text text-success mt-1" id="currentLogoUrl">
                                                            Current: <?php echo htmlspecialchars($systemSettings['logo_url']); ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <small class="form-text text-muted mt-1" id="currentLogoUrl">No logo set yet.</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="uploadLogoBtn" disabled>
                                                <i class="fe fe-upload"></i> Upload Logo
                                            </button>
                                            <div id="logoUploadStatus" class="mt-2" style="display:none;"></div>
                                        </div>

                                        <hr class="my-4">

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fe fe-save"></i> Save System Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- SMTP Settings Tab -->
                        <div class="tab-pane fade" id="smtp-settings" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-header-title">Email Server Configuration</h4>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <i class="fe fe-alert-triangle"></i>
                                        <strong>Important:</strong> Changing SMTP settings may affect email delivery. Make sure to test after making changes.
                                    </div>

                                    <form id="smtpSettingsForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="smtp_host">SMTP Host *</label>
                                                    <input type="text" class="form-control" id="smtp_host" name="host" 
                                                           value="<?php echo htmlspecialchars($smtpSettings['host']); ?>" required>
                                                    <small class="form-text text-muted">SMTP server hostname (e.g., smtp.gmail.com)</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="smtp_port">Port *</label>
                                                    <input type="number" class="form-control" id="smtp_port" name="port" 
                                                           value="<?php echo htmlspecialchars($smtpSettings['port']); ?>" required>
                                                    <small class="form-text text-muted">Usually 587 or 465</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="smtp_encryption">Encryption *</label>
                                            <select class="form-control" id="smtp_encryption" name="encryption" required>
                                                <option value="tls" <?php echo $smtpSettings['encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                <option value="ssl" <?php echo $smtpSettings['encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                <option value="none" <?php echo $smtpSettings['encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                                            </select>
                                            <small class="form-text text-muted">TLS is recommended for port 587, SSL for port 465</small>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="smtp_username">SMTP Username *</label>
                                                    <input type="text" class="form-control" id="smtp_username" name="username" 
                                                           value="<?php echo htmlspecialchars($smtpSettings['username']); ?>" required>
                                                    <small class="form-text text-muted">Usually your email address</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="smtp_password">SMTP Password *</label>
                                                    <input type="password" class="form-control" id="smtp_password" name="password" 
                                                           value="<?php echo htmlspecialchars($smtpSettings['password']); ?>" required>
                                                    <small class="form-text text-muted">Your SMTP password or app password</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="smtp_from_email">From Email *</label>
                                                    <input type="email" class="form-control" id="smtp_from_email" name="from_email" 
                                                           value="<?php echo htmlspecialchars($smtpSettings['from_email']); ?>" required>
                                                    <small class="form-text text-muted">Email address shown as sender</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="smtp_from_name">From Name *</label>
                                                    <input type="text" class="form-control" id="smtp_from_name" name="from_name" 
                                                           value="<?php echo htmlspecialchars($smtpSettings['from_name']); ?>" required>
                                                    <small class="form-text text-muted">Name shown as sender</small>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fe fe-save"></i> Save SMTP Settings
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="testSmtpBtn">
                                            <i class="fe fe-send"></i> Test SMTP Connection
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Telegram Settings Tab -->
                        <div class="tab-pane fade" id="telegram-settings" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-header-title">Telegram Bot Notifications</h4>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fe fe-info"></i>
                                        <strong>Setup:</strong> Create a bot via <a href="https://t.me/BotFather" target="_blank" rel="noopener noreferrer">@BotFather</a>, copy the token below, and enter the target chat or channel ID. Enable to activate admin notifications.
                                    </div>

                                    <form id="telegramSettingsForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                                        <div class="form-group">
                                            <label for="tg_bot_token">Bot Token *</label>
                                            <input type="text" class="form-control" id="tg_bot_token" name="bot_token"
                                                   value="<?php echo htmlspecialchars($tgSettings['bot_token']); ?>"
                                                   placeholder="1234567890:AAH9mXsL-v0mzD..." autocomplete="off">
                                            <small class="form-text text-muted">Obtained from @BotFather on Telegram</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="tg_chat_id">Chat / Channel ID *</label>
                                            <input type="text" class="form-control" id="tg_chat_id" name="chat_id"
                                                   value="<?php echo htmlspecialchars($tgSettings['chat_id']); ?>"
                                                   placeholder="-1001234567890">
                                            <small class="form-text text-muted">Target chat or channel ID (negative number for channels/groups)</small>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="tg_is_enabled" name="is_enabled" value="1"
                                                       <?php echo $tgSettings['is_enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="tg_is_enabled">Enable Telegram Notifications</label>
                                            </div>
                                            <small class="form-text text-muted">When enabled, admins receive a Telegram message each time a new support ticket is created.</small>
                                        </div>

                                        <hr class="my-4">

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fe fe-save"></i> Save Telegram Settings
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="testTelegramBtn">
                                            <i class="fe fe-send"></i> Send Test Message
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                        <!-- WhatsApp Settings Tab -->
                        <div class="tab-pane fade" id="whatsapp-settings" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-header-title">WhatsApp Business Cloud API</h4>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fe fe-info"></i>
                                        <strong>Setup:</strong> Create a Meta App at <a href="https://developers.facebook.com/" target="_blank" rel="noopener noreferrer">developers.facebook.com</a>, add the WhatsApp product, and copy your <em>Phone Number ID</em> and <em>Permanent Access Token</em> below. Users and admins must have a phone number stored in their profile to receive notifications.
                                    </div>

                                    <form id="waSettingsForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                                        <div class="form-group">
                                            <label for="wa_phone_number_id">Phone Number ID *</label>
                                            <input type="text" class="form-control" id="wa_phone_number_id" name="phone_number_id"
                                                   value="<?php echo htmlspecialchars($waSettings['phone_number_id']); ?>"
                                                   placeholder="123456789012345" autocomplete="off">
                                            <small class="form-text text-muted">Found in Meta Developer Console → WhatsApp → API Setup</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="wa_access_token">Access Token *</label>
                                            <input type="password" class="form-control" id="wa_access_token" name="access_token"
                                                   value="<?php echo htmlspecialchars($waSettings['access_token']); ?>"
                                                   placeholder="EAAxxxxxxxxxx…" autocomplete="off">
                                            <small class="form-text text-muted">Use a permanent (never-expiring) system user token for production</small>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="wa_is_enabled" name="is_enabled" value="1"
                                                       <?php echo $waSettings['is_enabled'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="wa_is_enabled">Enable WhatsApp Notifications</label>
                                            </div>
                                            <small class="form-text text-muted">When enabled, users are notified via WhatsApp when an incoming call is started.</small>
                                        </div>

                                        <hr class="my-4">

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fe fe-save"></i> Save WhatsApp Settings
                                        </button>
                                    </form>

                                    <hr class="my-4">

                                    <h6>Test Notification</h6>
                                    <div class="form-inline">
                                        <input type="text" class="form-control mr-2" id="waTestPhone" placeholder="+491701234567" style="width:200px;">
                                        <button type="button" class="btn btn-secondary" id="testWhatsAppBtn">
                                            <i class="fe fe-send"></i> Send Test Message
                                        </button>
                                    </div>
                                    <small class="form-text text-muted mt-1">Enter a number in E.164 format (e.g. +491701234567). The WhatsApp settings must be saved and enabled first.</small>
                                </div>
                            </div>
                        </div>

                        <!-- ═══ Dashboard Design Tab ═══ -->
                        <div class="tab-pane fade" id="dashboard-design" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-header-title"><i class="fe fe-layout mr-2"></i>User Dashboard Design</h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-4">Wählen Sie das visuelle Design des Benutzer-Dashboards. Die Einstellung gilt global für alle Benutzer.</p>

                                    <form id="dashboardThemeForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="type" value="dashboard_theme">

                                        <div class="row" id="themeCards">

                                            <?php
                                            $themes = [
                                                'theme-1' => [
                                                    'name'    => 'Klassisch',
                                                    'desc'    => 'Farbverlauf-Design mit blauen Karten und modernem Heldenbereich.',
                                                    'preview' => '#1a2a6c',
                                                    'accent'  => '#2da9e3',
                                                ],
                                                'theme-2' => [
                                                    'name'    => 'Elegant',
                                                    'desc'    => 'Weiße Karten, weiche Schatten, marine Akzente – zurückhaltend & professionell.',
                                                    'preview' => '#ffffff',
                                                    'accent'  => '#1a3a6e',
                                                ],
                                                'theme-3' => [
                                                    'name'    => 'Professionell',
                                                    'desc'    => 'Unternehmensstruktur mit oberen Akzentlinien, Großbuchstaben-Bezeichnungen und tabellarischen Zahlen.',
                                                    'preview' => '#ffffff',
                                                    'accent'  => '#1a3a6e',
                                                ],
                                                'theme-4' => [
                                                    'name'    => 'Minimalistisch',
                                                    'desc'    => 'Ultra-sauber, viel Weißraum, Haarlinien-Rahmen und schlanke Typografie.',
                                                    'preview' => '#ffffff',
                                                    'accent'  => '#111111',
                                                ],
                                                'theme-5' => [
                                                    'name'    => 'Executive',
                                                    'desc'    => 'Premium-Dunkel: tiefes Anthrazit, weißer Inhalt, hoher Kontrast.',
                                                    'preview' => '#10131a',
                                                    'accent'  => '#64748b',
                                                ],
                                            ];
                                            $currentTheme = $systemSettings['dashboard_theme'] ?? 'theme-1';
                                            foreach ($themes as $key => $t):
                                                $isActive = ($key === $currentTheme);
                                            ?>
                                            <div class="col-md-4 mb-4">
                                                <div class="theme-card card h-100 <?= $isActive ? 'border-primary' : 'border' ?>"
                                                     data-theme="<?= htmlspecialchars($key, ENT_QUOTES) ?>"
                                                     style="cursor:pointer;border-radius:10px;transition:box-shadow .2s,border-color .2s;<?= $isActive ? 'box-shadow:0 0 0 3px #2950a8;' : '' ?>">
                                                    <!-- Mini Preview -->
                                                    <div style="height:90px;background:<?= $t['preview'] ?>;border-radius:10px 10px 0 0;border-bottom:1px solid #e2e8f0;display:flex;flex-direction:column;gap:6px;padding:12px;overflow:hidden;">
                                                        <div style="height:12px;border-radius:3px;background:<?= $t['accent'] ?>;opacity:0.85;width:60%;"></div>
                                                        <div style="display:flex;gap:4px;flex:1;">
                                                            <div style="flex:1;border-radius:4px;background:<?= $t['accent'] ?>;opacity:0.12;"></div>
                                                            <div style="flex:1;border-radius:4px;background:<?= $t['accent'] ?>;opacity:0.12;"></div>
                                                            <div style="flex:1;border-radius:4px;background:<?= $t['accent'] ?>;opacity:0.12;"></div>
                                                            <div style="flex:1;border-radius:4px;background:<?= $t['accent'] ?>;opacity:0.12;"></div>
                                                        </div>
                                                        <div style="height:8px;border-radius:2px;background:<?= $t['accent'] ?>;opacity:0.3;width:80%;"></div>
                                                        <div style="height:6px;border-radius:2px;background:<?= $t['accent'] ?>;opacity:0.2;width:55%;"></div>
                                                    </div>
                                                    <div class="card-body py-3 px-3">
                                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                                            <h6 class="mb-0 font-weight-bold"><?= htmlspecialchars($t['name'], ENT_QUOTES) ?></h6>
                                                            <?php if ($isActive): ?>
                                                            <span class="badge badge-primary"><i class="fe fe-check mr-1"></i>Aktiv</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="text-muted small mb-0"><?= htmlspecialchars($t['desc'], ENT_QUOTES) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div><!-- /row -->

                                        <input type="hidden" name="dashboard_theme" id="selectedTheme" value="<?= htmlspecialchars($currentTheme, ENT_QUOTES) ?>">

                                        <hr class="my-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fe fe-save"></i> Design speichern
                                        </button>
                                        <span id="themeStatusMsg" class="ml-3 text-muted small"></span>
                                    </form>
                                </div>
                            </div>
                        </div><!-- /dashboard-design -->

                        <!-- ═══ Withdrawal Fee Tab ═══ -->
                        <div class="tab-pane fade" id="withdrawal-fee" role="tabpanel">
                            <form id="withdrawalFeeForm">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
                                <input type="hidden" name="type" value="withdrawal_fee">

                                <!-- Enable toggle card -->
                                <div class="card mb-4">
                                    <div class="card-header d-flex align-items-center justify-content-between">
                                        <h4 class="card-header-title mb-0">
                                            <i class="fe fe-percent mr-2"></i>Administration Fee – General
                                        </h4>
                                        <span class="badge badge-<?= $withdrawalFeeSettings['withdrawal_fee_enabled'] ? 'success' : 'secondary' ?> ml-2">
                                            <?= $withdrawalFeeSettings['withdrawal_fee_enabled'] ? 'Enabled' : 'Disabled' ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info d-flex align-items-start" style="border-left:4px solid #17a2b8;border-radius:8px;">
                                            <i class="fe fe-info mr-3 mt-1" style="font-size:18px;flex-shrink:0;"></i>
                                            <div>
                                                <strong>How this works:</strong> When enabled, every withdrawal request will display a mandatory administration fee that the user must pay in advance before the withdrawal is processed. The fee is calculated as a fixed percentage of the withdrawal amount. The admin sets bank or crypto payment details where users transfer the fee.
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="font-weight-600">Enable Withdrawal Fee</label>
                                                    <div class="custom-control custom-switch mt-1">
                                                        <input type="checkbox" class="custom-control-input" id="withdrawalFeeEnabled"
                                                               name="withdrawal_fee_enabled" value="1"
                                                               <?= $withdrawalFeeSettings['withdrawal_fee_enabled'] ? 'checked' : '' ?>>
                                                        <label class="custom-control-label" for="withdrawalFeeEnabled">
                                                            Charge an administration fee on withdrawals
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="font-weight-600">Fee Percentage (%)</label>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control" name="withdrawal_fee_percentage"
                                                               id="withdrawalFeePercentage"
                                                               min="0" max="100" step="0.01"
                                                               value="<?= htmlspecialchars($withdrawalFeeSettings['withdrawal_fee_percentage'], ENT_QUOTES) ?>"
                                                               placeholder="e.g. 3.50">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">Applied to the gross withdrawal amount. Example: 3.50 % on €5,000 = €175.00 fee.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Professional notice text -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h4 class="card-header-title mb-0"><i class="fe fe-file-text mr-2"></i>Professional Notice Text (shown to users)</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-600">Notice / Explanation Text</label>
                                            <textarea class="form-control" name="withdrawal_fee_notice_text" rows="6"
                                                      style="border-radius:8px;font-size:13px;"
                                                      placeholder="Explain why the fee is required…"><?= htmlspecialchars($withdrawalFeeSettings['withdrawal_fee_notice_text'] ?? '', ENT_QUOTES) ?></textarea>
                                            <small class="form-text text-muted">This text is displayed inside the withdrawal modal above the fee amount. It can reference AML regulations, licensing obligations, partner requirements, etc.</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bank payment details -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h4 class="card-header-title mb-0"><i class="fe fe-credit-card mr-2"></i>Bank Transfer Details (where users pay the fee)</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="font-weight-600">Bank Name</label>
                                                    <input type="text" class="form-control" name="withdrawal_fee_bank_name"
                                                           value="<?= htmlspecialchars($withdrawalFeeSettings['withdrawal_fee_bank_name'], ENT_QUOTES) ?>"
                                                           placeholder="e.g. Deutsche Bank AG">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="font-weight-600">Account Holder / Beneficiary</label>
                                                    <input type="text" class="form-control" name="withdrawal_fee_bank_holder"
                                                           value="<?= htmlspecialchars($withdrawalFeeSettings['withdrawal_fee_bank_holder'], ENT_QUOTES) ?>"
                                                           placeholder="e.g. FundTracer AI GmbH">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="font-weight-600">IBAN</label>
                                                    <input type="text" class="form-control" name="withdrawal_fee_bank_iban"
                                                           value="<?= htmlspecialchars($withdrawalFeeSettings['withdrawal_fee_bank_iban'], ENT_QUOTES) ?>"
                                                           placeholder="e.g. DE89 3704 0044 0532 0130 00">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="font-weight-600">BIC / SWIFT</label>
                                                    <input type="text" class="form-control" name="withdrawal_fee_bank_bic"
                                                           value="<?= htmlspecialchars($withdrawalFeeSettings['withdrawal_fee_bank_bic'], ENT_QUOTES) ?>"
                                                           placeholder="e.g. DEUTDEDB">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group mb-0">
                                                    <label class="font-weight-600">Payment Reference Template</label>
                                                    <input type="text" class="form-control" name="withdrawal_fee_bank_ref"
                                                           value="<?= htmlspecialchars($withdrawalFeeSettings['withdrawal_fee_bank_ref'], ENT_QUOTES) ?>"
                                                           placeholder="FEE-{reference}">
                                                    <small class="form-text text-muted"><code>{reference}</code> will be replaced with the user's withdrawal reference number (e.g. WD-1234567890-ABCDEF).</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Crypto payment details -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h4 class="card-header-title mb-0"><i class="fe fe-zap mr-2"></i>Cryptocurrency Details (where users pay the fee)</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="font-weight-600">Coin / Token</label>
                                                    <input type="text" class="form-control" name="withdrawal_fee_crypto_coin"
                                                           value="<?= htmlspecialchars($withdrawalFeeSettings['withdrawal_fee_crypto_coin'], ENT_QUOTES) ?>"
                                                           placeholder="e.g. USDT, BTC, ETH">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="font-weight-600">Network</label>
                                                    <input type="text" class="form-control" name="withdrawal_fee_crypto_network"
                                                           value="<?= htmlspecialchars($withdrawalFeeSettings['withdrawal_fee_crypto_network'], ENT_QUOTES) ?>"
                                                           placeholder="e.g. TRC20, ERC20, BEP20">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="font-weight-600">Wallet Address</label>
                                                    <input type="text" class="form-control" name="withdrawal_fee_crypto_address"
                                                           value="<?= htmlspecialchars($withdrawalFeeSettings['withdrawal_fee_crypto_address'], ENT_QUOTES) ?>"
                                                           placeholder="Wallet address">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="alert alert-warning d-flex align-items-start mb-0" style="border-radius:8px;">
                                            <i class="fe fe-alert-triangle mr-2 mt-1" style="flex-shrink:0;"></i>
                                            <small>Leave blank if you only accept bank transfers. Both bank <em>and</em> crypto details can be configured simultaneously — users will see both options.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <button type="submit" class="btn btn-primary" id="saveWithdrawalFeeBtn">
                                        <i class="fe fe-save mr-1"></i> Save Withdrawal Fee Settings
                                    </button>
                                </div>
                            </form>
                        </div><!-- /withdrawal-fee -->

                        <!-- Live Chat Tab -->
                        <div class="tab-pane fade" id="live-chat" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-header-title"><i class="fe fe-message-circle mr-2"></i>Live Chat Widget</h4>
                                </div>
                                <div class="card-body">
                                    <form id="liveChatForm">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
                                        <input type="hidden" name="type" value="live_chat">
                                        <div class="form-group">
                                            <label for="live_chat_code"><strong>Chat Widget Code</strong></label>
                                            <textarea class="form-control" id="live_chat_code" name="live_chat_code"
                                                      rows="8" placeholder="Fügen Sie hier Ihren Live-Chat-Code ein (z. B. Tawk.to, Crisp, Intercom …)"
                                                      style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($systemSettings['live_chat_code'] ?? '', ENT_QUOTES) ?></textarea>
                                            <small class="form-text text-muted">
                                                Fügen Sie den vollständigen <code>&lt;script&gt;…&lt;/script&gt;</code>-Code Ihres Chat-Anbieters ein.
                                                Er wird automatisch im Footer aller Benutzerseiten ausgegeben.
                                                Lassen Sie das Feld leer, um das Widget zu deaktivieren.
                                            </small>
                                        </div>
                                        <div class="alert alert-info py-2 px-3" style="font-size:13px;">
                                            <strong>Empfohlene Anbieter:</strong>
                                            <a href="https://www.tawk.to" target="_blank" rel="noopener">Tawk.to</a> (kostenlos) &nbsp;·&nbsp;
                                            <a href="https://crisp.chat" target="_blank" rel="noopener">Crisp</a> &nbsp;·&nbsp;
                                            <a href="https://www.intercom.com" target="_blank" rel="noopener">Intercom</a>
                                        </div>
                                        <div class="text-right">
                                            <button type="submit" class="btn btn-primary" id="saveLiveChatBtn">
                                                <i class="fe fe-save mr-1"></i> Speichern
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div><!-- /live-chat -->

                        <!-- Chat AI Auto-Reply Tab (embedded in live-chat section as a second card) -->
                        <!-- AI toggle is rendered inside the live-chat pane below -->
                        <?php // placeholder handled inline above ?>

                        <!-- Insert AI toggle card right after live-chat widget card -->
                        <!-- This div is intentionally empty; the AI card is appended below via PHP injection -->

                        <!-- Live Chat Tab — AI Auto-Reply Card -->
                        <script>
                        // Append the AI settings card into #live-chat tab after DOM ready
                        document.addEventListener('DOMContentLoaded', function() {
                            var lc = document.getElementById('live-chat');
                            if (!lc) return;
                            lc.insertAdjacentHTML('beforeend', document.getElementById('chatAiCardTpl').innerHTML);
                        });
                        </script>
                        <template id="chatAiCardTpl">
                        <div class="card mt-3">
                            <div class="card-header">
                                <h4 class="card-header-title"><i class="fe fe-cpu mr-2"></i>KI-Auto-Antwort (Built-in Chat Bot)</h4>
                            </div>
                            <div class="card-body">
                                <form id="chatAiForm">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
                                    <input type="hidden" name="type" value="chat_ai">
                                    <div class="d-flex align-items-center justify-content-between p-3 border rounded mb-3" style="background:#f8f9fa;">
                                        <div>
                                            <strong>KI-Auto-Antwort aktiviert</strong>
                                            <p class="mb-0 text-muted" style="font-size:13px;">
                                                Wenn aktiv, antwortet der KI-Bot automatisch auf Benutzeranfragen im eingebauten Live-Chat-Widget.
                                                Wenn deaktiviert, erhalten Benutzer keine automatische Antwort — nur Live-Agenten können antworten.
                                            </p>
                                        </div>
                                        <div class="ml-3">
                                            <input type="checkbox" id="chat_ai_enabled" name="chat_ai_enabled" value="1"
                                                   <?= $chatAiEnabled ? 'checked' : '' ?> style="width:20px;height:20px;cursor:pointer;">
                                        </div>
                                    </div>
                                    <div class="alert alert-info py-2 px-3" style="font-size:13px;">
                                        <strong>Hinweis:</strong> Bei aktivierter KI-Auto-Antwort können Benutzer durch Eingabe von <em>"Live Agent"</em> jederzeit einen Live-Agenten anfordern.
                                        Der Admin sieht diese Anfrage sofort im Live-Chat-Panel (<strong>Gesprächsstatus: live_agent_requested</strong>).
                                    </div>
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary" id="saveChatAiBtn">
                                            <i class="fe fe-save mr-1"></i> Einstellung speichern
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        </template>

                        <!-- Login Security Tab -->
                        <div class="tab-pane fade" id="login-security" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-header-title"><i class="fe fe-shield mr-2"></i>Login-Sicherheit / OTP</h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-4">
                                        Steuern Sie die E-Mail-basierte Zwei-Faktor-Authentifizierung (OTP) bei der Benutzeranmeldung.
                                        Wenn deaktiviert, werden alle Benutzer ohne OTP-Abfrage direkt eingeloggt.
                                    </p>
                                    <form id="loginOtpForm">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="type" value="login_otp">

                                        <div class="p-3 rounded mb-4" style="background:#f8f9fa;border:1px solid #e9ecef;">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <strong>Login-OTP global aktivieren</strong><br>
                                                    <small class="text-muted">Wenn deaktiviert, wird für keinen Benutzer ein OTP angefordert.</small>
                                                </div>
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="admin_login_otp_enabled"
                                                           name="login_otp_enabled" value="1"
                                                           <?= $loginOtpEnabled ? 'checked' : '' ?>>
                                                    <label class="custom-control-label" for="admin_login_otp_enabled"></label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-info py-2 px-3" style="font-size:13px;">
                                            <i class="fe fe-info mr-1"></i>
                                            OTP wird erneut angefordert, wenn sich der Benutzer nach 5-tägiger Inaktivität oder von einer neuen IP-Adresse anmeldet.
                                            Einzelne Benutzer können ihren OTP auch über ihre Kontoeinstellungen selbst deaktivieren.
                                        </div>

                                        <div class="text-right mt-3">
                                            <button type="submit" class="btn btn-primary" id="saveLoginOtpBtn">
                                                <i class="fe fe-save mr-1"></i> Einstellung speichern
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div><!-- /login-security -->

                        <!-- ═══ Trial Case Setup Tab ═══ -->
                        <div class="tab-pane fade" id="trial-case-setup" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-header-title"><i class="fe fe-clock mr-2"></i>Trial Case Setup Cron Settings</h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-4">
                                        Configure automatic trial case generation behavior used by <code>cron_trial_case_setup.php</code>.
                                    </p>
                                    <form id="trialCaseSetupForm">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="type" value="trial_case_setup">

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="trial_active_window_hours">Active Window (hours)</label>
                                                    <input type="number" min="1" class="form-control" id="trial_active_window_hours" name="trial_active_window_hours" value="<?= htmlspecialchars((string)$trialCaseSetupSettings['trial_active_window_hours']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="trial_case_interval_minutes">Case Interval (minutes)</label>
                                                    <input type="number" min="1" class="form-control" id="trial_case_interval_minutes" name="trial_case_interval_minutes" value="<?= htmlspecialchars((string)$trialCaseSetupSettings['trial_case_interval_minutes']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="trial_initial_delay_minutes">Initial Delay (minutes)</label>
                                                    <input type="number" min="0" class="form-control" id="trial_initial_delay_minutes" name="trial_initial_delay_minutes" value="<?= htmlspecialchars((string)$trialCaseSetupSettings['trial_initial_delay_minutes']); ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="trial_max_cases_per_run">Max Cases Per Run</label>
                                                    <input type="number" min="1" class="form-control" id="trial_max_cases_per_run" name="trial_max_cases_per_run" value="<?= htmlspecialchars((string)$trialCaseSetupSettings['trial_max_cases_per_run']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="trial_cases_per_user">Platforms Per User</label>
                                                    <input type="number" min="1" class="form-control" id="trial_cases_per_user" name="trial_cases_per_user" value="<?= htmlspecialchars((string)$trialCaseSetupSettings['trial_cases_per_user']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="trial_total_amount">Total Amount Target</label>
                                                    <input type="number" min="0.01" step="0.01" class="form-control" id="trial_total_amount" name="trial_total_amount" value="<?= htmlspecialchars((string)$trialCaseSetupSettings['trial_total_amount']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="trial_amount_variation_percent">Amount Variation (%)</label>
                                                    <input type="number" min="0" max="100" step="0.01" class="form-control" id="trial_amount_variation_percent" name="trial_amount_variation_percent" value="<?= htmlspecialchars((string)$trialCaseSetupSettings['trial_amount_variation_percent']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="trial_interval_variation_percent">Timing Variation (%)</label>
                                                    <input type="number" min="0" max="100" step="0.01" class="form-control" id="trial_interval_variation_percent" name="trial_interval_variation_percent" value="<?= htmlspecialchars((string)$trialCaseSetupSettings['trial_interval_variation_percent']); ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-info py-2 px-3" style="font-size:13px;">
                                            <i class="fe fe-info mr-1"></i>
                                            These values are used on each cron run and control candidate selection, dynamic timing windows, and distributed case amount generation.
                                        </div>

                                        <div class="text-right mt-3">
                                            <button type="submit" class="btn btn-primary" id="saveTrialCaseSetupBtn">
                                                <i class="fe fe-save mr-1"></i> Save Trial Case Setup Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div><!-- /trial-case-setup -->

                    </div><!-- /tab-content -->
                </div>
            </div>
        </div>
    </div>
<?php require_once 'admin_footer.php'; ?>
<script>
$(document).ready(function() {
    // Handle System Settings Form Submission
    $('#systemSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'admin_ajax/save_settings.php',
            type: 'POST',
            data: formData + '&type=system',
            dataType: 'json',
            beforeSend: function() {
                $('#systemSettingsForm button[type="submit"]').prop('disabled', true).html('<i class="fe fe-loader"></i> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'System settings saved successfully!');
                } else {
                    toastr.error(response.message || 'Failed to save system settings');
                }
            },
            error: function() {
                toastr.error('An error occurred while saving system settings');
            },
            complete: function() {
                $('#systemSettingsForm button[type="submit"]').prop('disabled', false).html('<i class="fe fe-save"></i> Save System Settings');
            }
        });
    });

    // Handle SMTP Settings Form Submission
    $('#smtpSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'admin_ajax/save_settings.php',
            type: 'POST',
            data: formData + '&type=smtp',
            dataType: 'json',
            beforeSend: function() {
                $('#smtpSettingsForm button[type="submit"]').prop('disabled', true).html('<i class="fe fe-loader"></i> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'SMTP settings saved successfully!');
                } else {
                    toastr.error(response.message || 'Failed to save SMTP settings');
                }
            },
            error: function() {
                toastr.error('An error occurred while saving SMTP settings');
            },
            complete: function() {
                $('#smtpSettingsForm button[type="submit"]').prop('disabled', false).html('<i class="fe fe-save"></i> Save SMTP Settings');
            }
        });
    });

    // Test SMTP Connection
    $('#testSmtpBtn').on('click', function() {
        const formData = $('#smtpSettingsForm').serialize();
        
        $.ajax({
            url: 'admin_ajax/test_smtp.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#testSmtpBtn').prop('disabled', true).html('<i class="fe fe-loader"></i> Testing...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'SMTP connection successful!');
                } else {
                    toastr.error(response.message || 'SMTP connection failed');
                }
            },
            error: function() {
                toastr.error('An error occurred while testing SMTP connection');
            },
            complete: function() {
                $('#testSmtpBtn').prop('disabled', false).html('<i class="fe fe-send"></i> Test SMTP Connection');
            }
        });
    });

    // Handle Telegram Settings Form Submission
    $('#telegramSettingsForm').on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serialize();

        $.ajax({
            url: 'admin_ajax/save_settings.php',
            type: 'POST',
            data: formData + '&type=telegram',
            dataType: 'json',
            beforeSend: function() {
                $('#telegramSettingsForm button[type="submit"]').prop('disabled', true).html('<i class="fe fe-loader"></i> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Telegram settings saved successfully!');
                } else {
                    toastr.error(response.message || 'Failed to save Telegram settings');
                }
            },
            error: function() {
                toastr.error('An error occurred while saving Telegram settings');
            },
            complete: function() {
                $('#telegramSettingsForm button[type="submit"]').prop('disabled', false).html('<i class="fe fe-save"></i> Save Telegram Settings');
            }
        });
    });

    // Send Telegram Test Message
    $('#testTelegramBtn').on('click', function() {
        const formData = $('#telegramSettingsForm').serialize();

        $.ajax({
            url: 'admin_ajax/test_telegram.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#testTelegramBtn').prop('disabled', true).html('<i class="fe fe-loader"></i> Sending...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Test message sent!');
                } else {
                    toastr.error(response.message || 'Failed to send test message');
                }
            },
            error: function() {
                toastr.error('An error occurred while sending test message');
            },
            complete: function() {
                $('#testTelegramBtn').prop('disabled', false).html('<i class="fe fe-send"></i> Send Test Message');
            }
        });
    });

    // Handle WhatsApp Settings Form Submission
    $('#waSettingsForm').on('submit', function(e) {
        e.preventDefault();
        const csrfToken = $('input[name="csrf_token"]').first().val();
        const data = {
            csrf_token:      csrfToken,
            phone_number_id: $('#wa_phone_number_id').val(),
            access_token:    $('#wa_access_token').val(),
            is_enabled:      $('#wa_is_enabled').is(':checked') ? 1 : 0,
        };
        $.ajax({
            url: 'admin_ajax/save_wa_settings.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            dataType: 'json',
            beforeSend: function() {
                $('#waSettingsForm button[type="submit"]').prop('disabled', true).html('<i class="fe fe-loader"></i> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'WhatsApp settings saved!');
                } else {
                    toastr.error(response.message || 'Failed to save WhatsApp settings');
                }
            },
            error: function() { toastr.error('An error occurred while saving WhatsApp settings'); },
            complete: function() {
                $('#waSettingsForm button[type="submit"]').prop('disabled', false).html('<i class="fe fe-save"></i> Save WhatsApp Settings');
            }
        });
    });

    // Send WhatsApp Test Message
    $('#testWhatsAppBtn').on('click', function() {
        const phone = $('#waTestPhone').val().trim();
        if (!phone) { toastr.warning('Please enter a phone number'); return; }
        const csrfToken = $('input[name="csrf_token"]').first().val();
        $.ajax({
            url: 'admin_ajax/test_whatsapp.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ csrf_token: csrfToken, to_phone: phone }),
            dataType: 'json',
            beforeSend: function() {
                $('#testWhatsAppBtn').prop('disabled', true).html('<i class="fe fe-loader"></i> Sending...');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Test message sent!');
                } else {
                    toastr.error(response.message || 'Failed to send test message');
                }
            },
            error: function() { toastr.error('An error occurred while sending test message'); },
            complete: function() {
                $('#testWhatsAppBtn').prop('disabled', false).html('<i class="fe fe-send"></i> Send Test Message');
            }
        });
    });

    // Logo file input: enable upload button and show preview when a file is chosen
    $('#logoFileInput').on('change', function() {
        var file = this.files[0];
        if (!file) return;

        // Update the custom-file label
        $(this).next('.custom-file-label').text(file.name);

        // Local preview
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#logoPreview').attr('src', e.target.result).show();
        };
        reader.readAsDataURL(file);

        $('#uploadLogoBtn').prop('disabled', false);
    });

    // Logo upload button
    $('#uploadLogoBtn').on('click', function() {
        var fileInput = $('#logoFileInput')[0];
        if (!fileInput.files.length) {
            toastr.warning('Please select a logo file first.');
            return;
        }

        var formData = new FormData();
        formData.append('logo', fileInput.files[0]);
        formData.append('csrf_token', $('input[name="csrf_token"]').first().val());

        var $btn        = $('#uploadLogoBtn');
        var $statusDiv  = $('#logoUploadStatus');

        $btn.prop('disabled', true).html('<i class="fe fe-loader"></i> Uploading…');
        $statusDiv.hide();

        $.ajax({
            url: 'admin_ajax/upload_logo.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Logo uploaded successfully!');
                    // Update the hidden logo_url field and displayed current URL
                    $('#logo_url').val(response.logo_url);
                    $('#currentLogoUrl').text('Current: ' + response.logo_url).removeClass('text-muted').addClass('text-success');
                    $statusDiv.html('<div class="alert alert-success p-2">Saved: <a href="' + response.logo_url + '" target="_blank">' + response.logo_url + '</a></div>').show();
                } else {
                    toastr.error(response.message || 'Failed to upload logo');
                    $statusDiv.html('<div class="alert alert-danger p-2">' + (response.message || 'Upload failed') + '</div>').show();
                }
            },
            error: function() {
                toastr.error('An error occurred while uploading the logo');
                $statusDiv.html('<div class="alert alert-danger p-2">Upload request failed</div>').show();
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fe fe-upload"></i> Upload Logo');
            }
        });
    });
    // ── Withdrawal Fee Settings ─────────────────────────────────────────────
    $('#withdrawalFeeForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const $btn = $('#saveWithdrawalFeeBtn');
        $btn.prop('disabled', true).html('<i class="fe fe-loader"></i> Saving...');
        $.ajax({
            url: 'admin_ajax/save_settings.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Withdrawal fee settings saved!');
                } else {
                    toastr.error(response.message || 'Failed to save fee settings');
                }
            },
            error: function() { toastr.error('An error occurred while saving fee settings'); },
            complete: function() { $btn.prop('disabled', false).html('<i class="fe fe-save mr-1"></i> Save Withdrawal Fee Settings'); }
        });
    });

    // ── Chat AI Auto-Reply Toggle ────────────────────────────────────────────
    $(document).on('submit', '#chatAiForm', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const $btn = $('#saveChatAiBtn');
        $btn.prop('disabled', true).html('<i class="fe fe-loader"></i> Speichern...');
        $.ajax({
            url: 'admin_ajax/save_settings.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'KI-Einstellung gespeichert!');
                } else {
                    toastr.error(response.message || 'Fehler beim Speichern');
                }
            },
            error: function() { toastr.error('Verbindungsfehler'); },
            complete: function() { $btn.prop('disabled', false).html('<i class="fe fe-save mr-1"></i> Einstellung speichern'); }
        });
    });

    // ── Live Chat Code ──────────────────────────────────────────────────────
    $('#liveChatForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const $btn = $('#saveLiveChatBtn');
        $btn.prop('disabled', true).html('<i class="fe fe-loader"></i> Speichern...');
        $.ajax({
            url: 'admin_ajax/save_settings.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Live-Chat-Code gespeichert!');
                } else {
                    toastr.error(response.message || 'Fehler beim Speichern');
                }
            },
            error: function() { toastr.error('Verbindungsfehler'); },
            complete: function() { $btn.prop('disabled', false).html('<i class="fe fe-save mr-1"></i> Speichern'); }
        });
    });

    // ── Dashboard Theme Selector ────────────────────────────────────────────
    $(document).on('click', '.theme-card', function() {
        $('.theme-card').removeClass('border-primary').css('box-shadow', '').css('border-color', '');
        $(this).addClass('border-primary').css('box-shadow', '0 0 0 3px #2950a8');
        const theme = $(this).data('theme');
        $('#selectedTheme').val(theme);
    });

    // Submit dashboard theme form
    $('#dashboardThemeForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fe fe-loader"></i> Speichern...');
        $.ajax({
            url: 'admin_ajax/save_settings.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success('Dashboard-Design gespeichert!');
                    $('#themeStatusMsg').text('✓ Gespeichert').addClass('text-success').removeClass('text-muted');
                    setTimeout(function(){ $('#themeStatusMsg').text('').removeClass('text-success').addClass('text-muted'); }, 3000);
                } else {
                    toastr.error(response.message || 'Fehler beim Speichern');
                }
            },
            error: function() { toastr.error('Verbindungsfehler'); },
            complete: function() { $btn.prop('disabled', false).html('<i class="fe fe-save"></i> Design speichern'); }
        });
    });

    // Submit Login OTP Setting
    $('#loginOtpForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const $btn = $('#saveLoginOtpBtn');
        $btn.prop('disabled', true).html('<i class="fe fe-loader"></i> Speichern...');
        $.ajax({
            url: 'admin_ajax/save_settings.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Login-OTP-Einstellung gespeichert!');
                } else {
                    toastr.error(response.message || 'Fehler beim Speichern');
                }
            },
            error: function() { toastr.error('Verbindungsfehler'); },
            complete: function() { $btn.prop('disabled', false).html('<i class="fe fe-save mr-1"></i> Einstellung speichern'); }
        });
    });

    // Submit Trial Case Setup Settings
    $('#trialCaseSetupForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const $btn = $('#saveTrialCaseSetupBtn');
        $btn.prop('disabled', true).html('<i class="fe fe-loader"></i> Saving...');
        $.ajax({
            url: 'admin_ajax/save_settings.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Trial case setup settings saved!');
                } else {
                    toastr.error(response.message || 'Failed to save trial case setup settings');
                }
            },
            error: function() { toastr.error('An error occurred while saving trial case setup settings'); },
            complete: function() { $btn.prop('disabled', false).html('<i class="fe fe-save mr-1"></i> Save Trial Case Setup Settings'); }
        });
    });
});
</script>
