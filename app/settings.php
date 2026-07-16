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
            $_SESSION['success'] = "Persönliche Daten erfolgreich aktualisiert!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Fehler beim Aktualisieren der persönlichen Daten: " . $e->getMessage();
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
            
            $_SESSION['success'] = "Bankdaten erfolgreich aktualisiert!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Fehler beim Aktualisieren der Bankdaten: " . $e->getMessage();
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "Passwörter stimmen nicht überein!";
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                    $_SESSION['error'] = "Aktuelles Passwort ist falsch!";
                } else {
                    // Update password
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$newHash, $_SESSION['user_id']]);
                    
                    $_SESSION['success'] = "Passwort erfolgreich geändert!";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Fehler beim Ändern des Passworts: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_otp_setting'])) {
        // Toggle login OTP for this user
        $otpEnabled = isset($_POST['login_otp_enabled']) ? 1 : 0;
        try {
            $pdo->prepare("UPDATE users SET login_otp_enabled = ? WHERE id = ?")
                ->execute([$otpEnabled, $_SESSION['user_id']]);
            $_SESSION['success'] = $otpEnabled
                ? "Zwei-Faktor-Authentifizierung aktiviert."
                : "Zwei-Faktor-Authentifizierung deaktiviert.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Fehler beim Speichern der Sicherheitseinstellung.";
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
    $_SESSION['error'] = "Fehler beim Laden der Benutzerdaten: " . $e->getMessage();
}
?>

<!--<div class="page-container">-->
    <div class="main-content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Kontoeinstellungen</h4>
                            <a href="payment-methods.php" class="btn btn-primary">
                                <i class="anticon anticon-credit-card"></i> Zahlungsmethoden verwalten
                            </a>
                        </div>
                        
                        <div class="m-t-30">
                            <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="personal-tab" data-toggle="tab" href="#personal" role="tab">Persönliche Daten</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="bank-tab" data-toggle="tab" href="#bank" role="tab">Bankdaten</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="password-tab" data-toggle="tab" href="#password" role="tab">Passwort</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="security-tab" data-toggle="tab" href="#security-otp" role="tab">Sicherheit</a>
                                </li>
                            </ul>
                            
                            <div class="tab-content m-t-20" id="settingsTabsContent">
                                <!-- Persönliche Daten -->
                                <div class="tab-pane fade show active" id="personal" role="tabpanel">
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Vorname</label>
                                                    <input type="text" class="form-control" name="first_name" 
                                                           value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Nachname</label>
                                                    <input type="text" class="form-control" name="last_name" 
                                                           value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Telefonnummer</label>
                                                    <input type="tel" class="form-control" name="phone" 
                                                           value="<?= htmlspecialchars($user['phone']) ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Land</label>
                                                    <input type="text" class="form-control" name="country" 
                                                           value="<?= htmlspecialchars($onboarding['country'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Straße</label>
                                            <input type="text" class="form-control" name="street" 
                                                   value="<?= htmlspecialchars($onboarding['street'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Stadt / Bundesland</label>
                                                    <input type="text" class="form-control" name="state" 
                                                           value="<?= htmlspecialchars($onboarding['state'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Postleitzahl</label>
                                                    <input type="text" class="form-control" name="postal_code" 
                                                           value="<?= htmlspecialchars($onboarding['postal_code'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" name="update_personal" class="btn btn-primary">Änderungen speichern</button>
                                    </form>
                                </div>
                                
                                <!-- Bankdaten -->
                                <div class="tab-pane fade" id="bank" role="tabpanel">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>Bankname</label>
                                            <input type="text" class="form-control" name="bank_name" 
                                                   value="<?= htmlspecialchars($onboarding['bank_name'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Kontoinhaber</label>
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
                                                    <label>BIC / SWIFT</label>
                                                    <input type="text" class="form-control" name="bic" 
                                                           value="<?= htmlspecialchars($onboarding['bic'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" name="update_bank" class="btn btn-primary">Bankdaten speichern</button>
                                    </form>
                                </div>
                                
                                <!-- Passwort -->
                                <div class="tab-pane fade" id="password" role="tabpanel">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>Aktuelles Passwort</label>
                                            <input type="password" class="form-control" name="current_password" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Neues Passwort</label>
                                            <input type="password" class="form-control" name="new_password" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Neues Passwort bestätigen</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                        
                                        <button type="submit" name="change_password" class="btn btn-primary">Passwort ändern</button>
                                    </form>
                                </div>

                                <!-- Sicherheit / OTP -->
                                <div class="tab-pane fade" id="security-otp" role="tabpanel">
                                    <div class="row justify-content-center">
                                        <div class="col-md-8">
                                            <div class="card border-0 shadow-sm mb-4">
                                                <div class="card-header" style="background:linear-gradient(135deg,#1a3a5c,#0d2137);color:#fff;border-radius:6px 6px 0 0;">
                                                    <h5 class="mb-0"><i class="anticon anticon-safety mr-2"></i>Zwei-Faktor-Authentifizierung (2FA)</h5>
                                                </div>
                                                <div class="card-body">
                                                    <p class="text-muted" style="font-size:14px;">
                                                        Wenn aktiviert, erhalten Sie bei jeder Anmeldung von einem neuen Gerät oder nach 5 Tagen Inaktivität einen einmaligen Sicherheitscode per E-Mail.
                                                    </p>
                                                    <div class="alert alert-info py-2 px-3" style="font-size:13px;">
                                                        <i class="anticon anticon-info-circle mr-1"></i>
                                                        Der Code wird nicht erneut angefordert, wenn Sie sich innerhalb von 5 Tagen vom gleichen Gerät/IP anmelden.
                                                    </div>
                                                    <form method="POST" class="mt-3">
                                                        <div class="d-flex align-items-center justify-content-between p-3 rounded" style="background:#f8f9fa;border:1px solid #e9ecef;">
                                                            <div>
                                                                <strong style="font-size:15px;">Login-OTP aktivieren</strong><br>
                                                                <small class="text-muted">E-Mail-Verifizierung bei der Anmeldung</small>
                                                            </div>
                                                            <div class="custom-control custom-switch">
                                                                <input type="checkbox" class="custom-control-input" id="login_otp_enabled"
                                                                       name="login_otp_enabled" value="1"
                                                                       <?= (!isset($user['login_otp_enabled']) || $user['login_otp_enabled']) ? 'checked' : '' ?>>
                                                                <label class="custom-control-label" for="login_otp_enabled"></label>
                                                            </div>
                                                        </div>
                                                        <div class="mt-3">
                                                            <button type="submit" name="update_otp_setting" class="btn btn-primary">
                                                                <i class="anticon anticon-save mr-1"></i> Einstellung speichern
                                                            </button>
                                                        </div>
                                                    </form>
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
    </div>
<!--</div>-->

<?php require_once 'footer.php'; ?>