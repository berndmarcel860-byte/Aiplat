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
}

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
});
</script>

