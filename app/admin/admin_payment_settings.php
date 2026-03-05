<?php
require_once 'admin_header.php';

// Get current settings
$settings = [];
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE ?");
$stmt->execute(["payment_%"]);
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="main-content">
    <div class="page-header">
        <h2>Payment System Settings</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Payment Settings</span>
            </nav>
        </div>
    </div>
    
    <!-- Payment Gateway Settings -->
    <div class="card">
        <div class="card-body">
            <h5>Payment Gateway Configuration</h5>
            <form id="gatewayForm" class="mt-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Primary Payment Gateway</label>
                            <select class="form-control" name="primary_gateway">
                                <option value="stripe" <?= ($settings['primary_gateway'] ?? '') == 'stripe' ? 'selected' : '' ?>>Stripe</option>
                                <option value="paypal" <?= ($settings['primary_gateway'] ?? '') == 'paypal' ? 'selected' : '' ?>>PayPal</option>
                                <option value="square" <?= ($settings['primary_gateway'] ?? '') == 'square' ? 'selected' : '' ?>>Square</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Gateway Status</label>
                            <select class="form-control" name="gateway_status">
                                <option value="enabled" <?= ($settings['gateway_status'] ?? '') == 'enabled' ? 'selected' : '' ?>>Enabled</option>
                                <option value="disabled" <?= ($settings['gateway_status'] ?? '') == 'disabled' ? 'selected' : '' ?>>Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>API Key</label>
                            <input type="password" class="form-control" name="gateway_api_key" 
                                   value="<?= htmlspecialchars($settings['gateway_api_key'] ?? '') ?>" placeholder="Enter API Key">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>API Secret</label>
                            <input type="password" class="form-control" name="gateway_api_secret" 
                                   value="<?= htmlspecialchars($settings['gateway_api_secret'] ?? '') ?>" placeholder="Enter API Secret">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Webhook URL</label>
                    <?php 
                    $webhookUrl = $settings['webhook_url'] ?? '';
                    if (empty($webhookUrl)) {
                        $webhookUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                                     '://' . $_SERVER['HTTP_HOST'] . '/webhook';
                    }
                    ?>
                    <input type="text" class="form-control" name="webhook_url" 
                           value="<?= htmlspecialchars($webhookUrl) ?>" readonly>
                    <small class="text-muted">Configure this URL in your payment gateway settings</small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="anticon anticon-save"></i> Save Gateway Settings
                </button>
            </form>
        </div>
    </div>
    
    <!-- Currency & Limits -->
    <div class="card">
        <div class="card-body">
            <h5>Currency & Transaction Limits</h5>
            <form id="currencyForm" class="mt-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Default Currency</label>
                            <select class="form-control" name="default_currency">
                                <option value="USD" <?= ($settings['default_currency'] ?? '') == 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                                <option value="EUR" <?= ($settings['default_currency'] ?? '') == 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                                <option value="GBP" <?= ($settings['default_currency'] ?? '') == 'GBP' ? 'selected' : '' ?>>GBP - British Pound</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Minimum Transaction</label>
                            <input type="number" class="form-control" name="min_transaction" 
                                   value="<?= htmlspecialchars($settings['min_transaction'] ?? '10') ?>" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Maximum Transaction</label>
                            <input type="number" class="form-control" name="max_transaction" 
                                   value="<?= htmlspecialchars($settings['max_transaction'] ?? '10000') ?>" step="0.01">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Transaction Fee (%)</label>
                            <input type="number" class="form-control" name="transaction_fee" 
                                   value="<?= htmlspecialchars($settings['transaction_fee'] ?? '2.5') ?>" step="0.1">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fixed Fee ($)</label>
                            <input type="number" class="form-control" name="fixed_fee" 
                                   value="<?= htmlspecialchars($settings['fixed_fee'] ?? '0.30') ?>" step="0.01">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="anticon anticon-save"></i> Save Currency Settings
                </button>
            </form>
        </div>
    </div>
    
    <!-- Payout Settings -->
    <div class="card">
        <div class="card-body">
            <h5>Payout & Withdrawal Settings</h5>
            <form id="payoutForm" class="mt-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Enable Automatic Payouts</label>
                            <select class="form-control" name="auto_payout">
                                <option value="yes" <?= ($settings['auto_payout'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="no" <?= ($settings['auto_payout'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Payout Schedule</label>
                            <select class="form-control" name="payout_schedule">
                                <option value="daily" <?= ($settings['payout_schedule'] ?? '') == 'daily' ? 'selected' : '' ?>>Daily</option>
                                <option value="weekly" <?= ($settings['payout_schedule'] ?? '') == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                <option value="monthly" <?= ($settings['payout_schedule'] ?? '') == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Minimum Withdrawal Amount</label>
                            <input type="number" class="form-control" name="min_withdrawal" 
                                   value="<?= htmlspecialchars($settings['min_withdrawal'] ?? '50') ?>" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Withdrawal Processing Time (days)</label>
                            <input type="number" class="form-control" name="withdrawal_processing_days" 
                                   value="<?= htmlspecialchars($settings['withdrawal_processing_days'] ?? '3') ?>">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="anticon anticon-save"></i> Save Payout Settings
                </button>
            </form>
        </div>
    </div>
    
    <!-- Test Mode -->
    <div class="card">
        <div class="card-body">
            <h5>Test Mode</h5>
            <div class="alert alert-warning">
                <i class="anticon anticon-warning"></i> Test mode allows you to test payments without processing real transactions
            </div>
            <form id="testModeForm" class="mt-3">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="testMode" name="test_mode" 
                           <?= ($settings['test_mode'] ?? '') == '1' ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="testMode">Enable Test Mode</label>
                </div>
                <button type="submit" class="btn btn-primary mt-3">
                    <i class="anticon anticon-save"></i> Update Test Mode
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Gateway settings form
    $('#gatewayForm').submit(function(e) {
        e.preventDefault();
        saveSettings($(this), 'payment_gateway');
    });
    
    // Currency settings form
    $('#currencyForm').submit(function(e) {
        e.preventDefault();
        saveSettings($(this), 'payment_currency');
    });
    
    // Payout settings form
    $('#payoutForm').submit(function(e) {
        e.preventDefault();
        saveSettings($(this), 'payment_payout');
    });
    
    // Test mode form
    $('#testModeForm').submit(function(e) {
        e.preventDefault();
        saveSettings($(this), 'payment_test');
    });
    
    function saveSettings(form, type) {
        const formData = form.serialize();
        
        $.post('admin_ajax/save_settings.php', formData + '&type=' + type)
        .done(function(response) {
            if (response.success) {
                toastr.success('Settings saved successfully');
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
