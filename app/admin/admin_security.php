<?php
require_once 'admin_header.php';

// Get current settings
$settings = [];
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE ?");
$stmt->execute(["security_%"]);
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="main-content">
    <div class="page-header">
        <h2>Security Center</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Security Center</span>
            </nav>
        </div>
    </div>
    
    <!-- Security Status -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-green">
                            <i class="anticon anticon-safety-certificate"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-muted mb-0">Security Score</p>
                            <h4 class="mb-0 text-success">95/100</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-red">
                            <i class="anticon anticon-warning"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-muted mb-0">Threats Blocked</p>
                            <h4 class="mb-0">24</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-blue">
                            <i class="anticon anticon-lock"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-muted mb-0">Failed Logins</p>
                            <h4 class="mb-0">8</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-gold">
                            <i class="anticon anticon-audit"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-muted mb-0">Audit Logs</p>
                            <h4 class="mb-0">1,547</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Authentication Settings -->
    <div class="card">
        <div class="card-body">
            <h5>Authentication & Access Control</h5>
            <form id="authForm" class="mt-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Two-Factor Authentication</label>
                            <select class="form-control" name="two_factor_auth">
                                <option value="enabled" <?= ($settings['two_factor_auth'] ?? '') == 'enabled' ? 'selected' : '' ?>>Enabled</option>
                                <option value="disabled" <?= ($settings['two_factor_auth'] ?? '') == 'disabled' ? 'selected' : '' ?>>Disabled</option>
                                <option value="optional" <?= ($settings['two_factor_auth'] ?? '') == 'optional' ? 'selected' : '' ?>>Optional</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Session Timeout (minutes)</label>
                            <input type="number" class="form-control" name="session_timeout" 
                                   value="<?= htmlspecialchars($settings['session_timeout'] ?? '30') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Max Login Attempts</label>
                            <input type="number" class="form-control" name="max_login_attempts" 
                                   value="<?= htmlspecialchars($settings['max_login_attempts'] ?? '5') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Lockout Duration (minutes)</label>
                            <input type="number" class="form-control" name="lockout_duration" 
                                   value="<?= htmlspecialchars($settings['lockout_duration'] ?? '15') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Password Minimum Length</label>
                            <input type="number" class="form-control" name="password_min_length" 
                                   value="<?= htmlspecialchars($settings['password_min_length'] ?? '8') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Password Expiry (days)</label>
                            <input type="number" class="form-control" name="password_expiry_days" 
                                   value="<?= htmlspecialchars($settings['password_expiry_days'] ?? '90') ?>">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="anticon anticon-save"></i> Save Authentication Settings
                </button>
            </form>
        </div>
    </div>
    
    <!-- IP Access Control -->
    <div class="card">
        <div class="card-body">
            <h5>IP Access Control</h5>
            <form id="ipControlForm" class="mt-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>IP Whitelist Enabled</label>
                            <select class="form-control" name="ip_whitelist_enabled">
                                <option value="yes" <?= ($settings['ip_whitelist_enabled'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="no" <?= ($settings['ip_whitelist_enabled'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>IP Blacklist Enabled</label>
                            <select class="form-control" name="ip_blacklist_enabled">
                                <option value="yes" <?= ($settings['ip_blacklist_enabled'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="no" <?= ($settings['ip_blacklist_enabled'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between">
                            <?php if (file_exists('admin_ip_whitelist.php')): ?>
                            <a href="admin_ip_whitelist.php" class="btn btn-outline-primary">
                                <i class="anticon anticon-check-circle"></i> Manage IP Whitelist
                            </a>
                            <?php else: ?>
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="anticon anticon-check-circle"></i> IP Whitelist (Not Available)
                            </button>
                            <?php endif; ?>
                            
                            <?php if (file_exists('admin_blocked_ips.php')): ?>
                            <a href="admin_blocked_ips.php" class="btn btn-outline-danger">
                                <i class="anticon anticon-close-circle"></i> Manage Blocked IPs
                            </a>
                            <?php else: ?>
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="anticon anticon-close-circle"></i> Blocked IPs (Not Available)
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary mt-3">
                    <i class="anticon anticon-save"></i> Save IP Control Settings
                </button>
            </form>
        </div>
    </div>
    
    <!-- Encryption & Data Protection -->
    <div class="card">
        <div class="card-body">
            <h5>Encryption & Data Protection</h5>
            <form id="encryptionForm" class="mt-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>SSL/TLS Enforcement</label>
                            <select class="form-control" name="ssl_enforcement">
                                <option value="enabled" <?= ($settings['ssl_enforcement'] ?? '') == 'enabled' ? 'selected' : '' ?>>Enabled</option>
                                <option value="disabled" <?= ($settings['ssl_enforcement'] ?? '') == 'disabled' ? 'selected' : '' ?>>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Data Encryption</label>
                            <select class="form-control" name="data_encryption">
                                <option value="aes256" <?= ($settings['data_encryption'] ?? '') == 'aes256' ? 'selected' : '' ?>>AES-256</option>
                                <option value="aes128" <?= ($settings['data_encryption'] ?? '') == 'aes128' ? 'selected' : '' ?>>AES-128</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="autoBackup" name="auto_backup" 
                           <?= ($settings['auto_backup'] ?? '') == '1' ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="autoBackup">Enable Automatic Backups</label>
                </div>
                
                <div class="custom-control custom-switch mt-2">
                    <input type="checkbox" class="custom-control-input" id="auditLogging" name="audit_logging" 
                           <?= ($settings['audit_logging'] ?? '') == '1' ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="auditLogging">Enable Comprehensive Audit Logging</label>
                </div>
                
                <button type="submit" class="btn btn-primary mt-3">
                    <i class="anticon anticon-save"></i> Save Encryption Settings
                </button>
            </form>
        </div>
    </div>
    
    <!-- Security Alerts -->
    <div class="card">
        <div class="card-body">
            <h5>Recent Security Events</h5>
            <div class="table-responsive mt-3">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-danger">Failed Login</span></td>
                            <td>Multiple failed login attempts detected</td>
                            <td>192.168.1.100</td>
                            <td><?= date('Y-m-d H:i:s') ?></td>
                            <td><span class="badge badge-warning">Blocked</span></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-success">Admin Login</span></td>
                            <td>Admin user logged in successfully</td>
                            <td>10.0.0.1</td>
                            <td><?= date('Y-m-d H:i:s', strtotime('-1 hour')) ?></td>
                            <td><span class="badge badge-success">Allowed</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Authentication form
    $('#authForm').submit(function(e) {
        e.preventDefault();
        saveSettings($(this), 'security_auth');
    });
    
    // IP Control form
    $('#ipControlForm').submit(function(e) {
        e.preventDefault();
        saveSettings($(this), 'security_ip');
    });
    
    // Encryption form
    $('#encryptionForm').submit(function(e) {
        e.preventDefault();
        saveSettings($(this), 'security_encryption');
    });
    
    function saveSettings(form, type) {
        const formData = form.serialize();
        
        $.post('admin_ajax/save_settings.php', formData + '&type=' + type)
        .done(function(response) {
            if (response.success) {
                toastr.success('Security settings saved successfully');
            } else {
                toastr.error(response.message || 'Failed to save settings');
            }
        })
        .fail(function() {
            toastr.error('Failed to save settings');
        });
    }
});
</script>
