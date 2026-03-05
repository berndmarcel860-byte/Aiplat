<?php
require_once 'admin_header.php';

// Get current settings
$settings = [];
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE ?");
$stmt->execute(["maintenance_%"]);
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="main-content">
    <div class="page-header">
        <h2>Maintenance Mode</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Maintenance Mode</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <h5>Maintenance Mode Configuration</h5>
            
            <form id="settingsForm" class="mt-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Setting Name</label>
                            <input type="text" class="form-control" name="maintenance_setting1" 
                                   value="<?= htmlspecialchars($settings['maintenance_setting1'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" name="maintenance_status">
                                <option value="enabled" <?= ($settings['maintenance_status'] ?? '') == 'enabled' ? 'selected' : '' ?>>Enabled</option>
                                <option value="disabled" <?= ($settings['maintenance_status'] ?? '') == 'disabled' ? 'selected' : '' ?>>Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-control" name="maintenance_description" rows="3"><?= htmlspecialchars($settings['maintenance_description'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="anticon anticon-save"></i> Save Settings
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    $('#settingsForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.post('admin_ajax/save_settings.php', formData + '&type=maintenance')
        .done(function(response) {
            if (response.success) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        })
        .fail(function() {
            toastr.error('Failed to save settings');
        });
    });
});
</script>
