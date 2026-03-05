<?php
require_once 'config.php';
require_once 'header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_personal'])) {
        // Update personal information
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $country = trim($_POST['country']);
        $street = trim($_POST['street']);
        $state = trim($_POST['state']);
        $postalCode = trim($_POST['postal_code']);
        
        try {
            $pdo->beginTransaction();
            
            // Update users table (only name and phone)
            $stmt = $pdo->prepare("UPDATE users SET 
                                  first_name = ?, last_name = ?, phone = ?
                                  WHERE id = ?");
            $stmt->execute([$firstName, $lastName, $phone, $_SESSION['user_id']]);
            
            // Update or insert onboarding record
            if ($onboarding) {
                // Update existing onboarding
                $stmt = $pdo->prepare("UPDATE user_onboarding SET 
                                      country = ?, street = ?, postal_code = ?, state = ?
                                      WHERE user_id = ?");
                $stmt->execute([$country, $street, $postalCode, $state, $_SESSION['user_id']]);
            } else {
                // Create new onboarding record
                $stmt = $pdo->prepare("INSERT INTO user_onboarding 
                                      (user_id, country, street, postal_code, state)
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $country, $street, $postalCode, $state]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Personal information updated successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error updating personal information: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_bank'])) {
        // Update bank details
        $bankName = trim($_POST['bank_name']);
        $accountHolder = trim($_POST['account_holder']);
        $iban = trim($_POST['iban']);
        $bic = trim($_POST['bic']);
        
        try {
            if ($onboarding) {
                // Update existing onboarding
                $stmt = $pdo->prepare("UPDATE user_onboarding SET 
                                      bank_name = ?, account_holder = ?, iban = ?, bic = ?
                                      WHERE user_id = ?");
                $stmt->execute([$bankName, $accountHolder, $iban, $bic, $_SESSION['user_id']]);
            } else {
                // Create new onboarding record with just bank details
                $stmt = $pdo->prepare("INSERT INTO user_onboarding 
                                      (user_id, bank_name, account_holder, iban, bic)
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $bankName, $accountHolder, $iban, $bic]);
            }
            
            $_SESSION['success'] = "Bank details updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating bank details: " . $e->getMessage();
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "New passwords do not match!";
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                    $_SESSION['error'] = "Current password is incorrect!";
                } else {
                    // Update password
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$newHash, $_SESSION['user_id']]);
                    
                    $_SESSION['success'] = "Password changed successfully!";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error changing password: " . $e->getMessage();
            }
        }
    }
    
    header("Location: settings.php");
    exit();
}

// Get user data
$user = [];
$onboarding = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM user_onboarding WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $onboarding = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching user data: " . $e->getMessage();
}
?>

<!--<div class="page-container">-->
    <div class="main-content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Account Settings</h4>
                            <a href="payment-methods.php" class="btn btn-primary">
                                <i class="anticon anticon-credit-card"></i> Manage Payment Methods
                            </a>
                        </div>
                        
                        <div class="m-t-30">
                            <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="personal-tab" data-toggle="tab" href="#personal" role="tab">Personal Info</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="bank-tab" data-toggle="tab" href="#bank" role="tab">Bank Details</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="password-tab" data-toggle="tab" href="#password" role="tab">Password</a>
                                </li>
                            </ul>
                            
                            <div class="tab-content m-t-20" id="settingsTabsContent">
                                <!-- Personal Information Tab -->
                                <div class="tab-pane fade show active" id="personal" role="tabpanel">
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>First Name</label>
                                                    <input type="text" class="form-control" name="first_name" 
                                                           value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Last Name</label>
                                                    <input type="text" class="form-control" name="last_name" 
                                                           value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Phone Number</label>
                                                    <input type="tel" class="form-control" name="phone" 
                                                           value="<?= htmlspecialchars($user['phone']) ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Country</label>
                                                    <input type="text" class="form-control" name="country" 
                                                           value="<?= htmlspecialchars($onboarding['country'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Street Address</label>
                                            <input type="text" class="form-control" name="street" 
                                                   value="<?= htmlspecialchars($onboarding['street'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>City/State</label>
                                                    <input type="text" class="form-control" name="state" 
                                                           value="<?= htmlspecialchars($onboarding['state'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Postal Code</label>
                                                    <input type="text" class="form-control" name="postal_code" 
                                                           value="<?= htmlspecialchars($onboarding['postal_code'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" name="update_personal" class="btn btn-primary">Save Changes</button>
                                    </form>
                                </div>
                                
                                <!-- Bank Details Tab -->
                                <div class="tab-pane fade" id="bank" role="tabpanel">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>Bank Name</label>
                                            <input type="text" class="form-control" name="bank_name" 
                                                   value="<?= htmlspecialchars($onboarding['bank_name'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Account Holder Name</label>
                                            <input type="text" class="form-control" name="account_holder" 
                                                   value="<?= htmlspecialchars($onboarding['account_holder'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>IBAN</label>
                                                    <input type="text" class="form-control" name="iban" 
                                                           value="<?= htmlspecialchars($onboarding['iban'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>BIC/SWIFT</label>
                                                    <input type="text" class="form-control" name="bic" 
                                                           value="<?= htmlspecialchars($onboarding['bic'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" name="update_bank" class="btn btn-primary">Save Bank Details</button>
                                    </form>
                                </div>
                                
                                <!-- Password Tab -->
                                <div class="tab-pane fade" id="password" role="tabpanel">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>Current Password</label>
                                            <input type="password" class="form-control" name="current_password" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>New Password</label>
                                            <input type="password" class="form-control" name="new_password" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                        
                                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!--</div>-->

<?php require_once 'footer.php'; ?>