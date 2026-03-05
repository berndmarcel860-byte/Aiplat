<?php
require_once 'config.php';
require_once 'header.php';

// Email verification is now handled via Ajax - see JavaScript at bottom of page

// Get user data with onboarding info
$user = [];
$onboarding = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM user_onboarding WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $onboarding = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get KYC status
    $stmt = $pdo->prepare("SELECT * FROM kyc_verification_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $kyc = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching profile data: " . $e->getMessage();
}
?>

<!--<div class="page-container">-->
    <div class="main-content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4>My Profile</h4>
                            <a href="settings.php" class="btn btn-primary">Edit Profile</a>
                        </div>
                        
                        <div class="row m-t-30">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="avatar avatar-image" style="width: 150px; height: 150px; margin: 0 auto;">
                                            <img src="<?= htmlspecialchars($avatar) ?>" alt="Profile">
                                        </div>
                                        <h4 class="m-t-20"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                                        <p class="text-muted">Member Since: <?= date('M Y', strtotime($user['created_at'])) ?></p>
                                        
                                        <div class="m-t-20">
                                            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?>
                                                <?php if ($user['is_verified']): ?>
                                                    <span class="badge badge-success">Verified</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Unverified</span>
                                                    <div class="m-t-10">
                                                        <button type="button" id="resendVerificationBtn" class="btn btn-sm btn-primary">
                                                            <i class="fa fa-envelope"></i> Resend Verification Email
                                                        </button>
                                                        <div id="verificationMessage" class="m-t-10" style="display: none;"></div>
                                                    </div>
                                                <?php endif; ?>
                                            </p>
                                            
                                            <?php if ($user['phone']): ?>
                                            <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?>
                                                <?= $user['phone_verified'] ? '<span class="badge badge-success">Verified</span>' : '' ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card m-t-20">
                                    <div class="card-body">
                                        <h5>KYC Verification</h5>
                                        <?php if ($kyc): ?>
                                            <p>Status: 
                                                <span class="badge badge-<?= 
                                                    $kyc['status'] == 'approved' ? 'success' : 
                                                    ($kyc['status'] == 'rejected' ? 'danger' : 'warning') 
                                                ?>">
                                                    <?= ucfirst($kyc['status']) ?>
                                                </span>
                                            </p>
                                            <?php if ($kyc['status'] == 'rejected' && $kyc['rejection_reason']): ?>
                                                <p class="text-danger">Reason: <?= htmlspecialchars($kyc['rejection_reason']) ?></p>
                                            <?php endif; ?>
                                            <a href="kyc.php" class="btn btn-sm btn-primary">
                                                <?= $kyc['status'] == 'rejected' ? 'Resubmit KYC' : 'View KYC Status' ?>
                                            </a>
                                        <?php else: ?>
                                            <p>You haven't completed KYC verification yet.</p>
                                            <a href="kyc.php" class="btn btn-sm btn-primary">Complete KYC</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Personal Information</h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>First Name:</strong> <?= htmlspecialchars($user['first_name']) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Last Name:</strong> <?= htmlspecialchars($user['last_name']) ?></p>
                                            </div>
                                        </div>
                                        
                                        <?php if ($onboarding): ?>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Country:</strong> <?= htmlspecialchars($onboarding['country']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>State:</strong> <?= htmlspecialchars($onboarding['state']) ?></p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Street:</strong> <?= htmlspecialchars($onboarding['street']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Postal Code:</strong> <?= htmlspecialchars($onboarding['postal_code']) ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($onboarding && ($onboarding['bank_name'] || $onboarding['account_holder'])): ?>
                                <div class="card m-t-20">
                                    <div class="card-body">
                                        <h5>Bank Details</h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Bank Name:</strong> <?= htmlspecialchars($onboarding['bank_name']) ?></p>
                                                <p><strong>Account Holder:</strong> <?= htmlspecialchars($onboarding['account_holder']) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>IBAN:</strong> <?= htmlspecialchars($onboarding['iban']) ?></p>
                                                <p><strong>BIC/SWIFT:</strong> <?= htmlspecialchars($onboarding['bic']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="card m-t-20">
                                    <div class="card-body">
                                        <h5>Account Activity</h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Last Login:</strong> 
                                                    <?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never' ?>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Account Created:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!--</div>-->

<script>
// Email Verification Ajax Handler
document.addEventListener('DOMContentLoaded', function() {
    const resendBtn = document.getElementById('resendVerificationBtn');
    const messageDiv = document.getElementById('verificationMessage');
    
    if (resendBtn) {
        resendBtn.addEventListener('click', function() {
            // Disable button to prevent double-clicking
            resendBtn.disabled = true;
            resendBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';
            
            // Hide previous message
            messageDiv.style.display = 'none';
            
            // Send Ajax request
            fetch('ajax/send_verification_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                // Show message
                messageDiv.style.display = 'block';
                
                if (data.success) {
                    messageDiv.className = 'alert alert-success m-t-10';
                    messageDiv.innerHTML = '<i class="fa fa-check-circle"></i> ' + data.message;
                    
                    // Keep button disabled for 60 seconds
                    setTimeout(function() {
                        resendBtn.disabled = false;
                        resendBtn.innerHTML = '<i class="fa fa-envelope"></i> Resend Verification Email';
                    }, 60000);
                } else {
                    messageDiv.className = 'alert alert-danger m-t-10';
                    messageDiv.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + data.message;
                    
                    // Re-enable button after error
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = '<i class="fa fa-envelope"></i> Resend Verification Email';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.style.display = 'block';
                messageDiv.className = 'alert alert-danger m-t-10';
                messageDiv.innerHTML = '<i class="fa fa-exclamation-circle"></i> An error occurred. Please try again.';
                
                // Re-enable button after error
                resendBtn.disabled = false;
                resendBtn.innerHTML = '<i class="fa fa-envelope"></i> Resend Verification Email';
            });
        });
    }
});
</script>

<?php require_once 'footer.php'; ?>