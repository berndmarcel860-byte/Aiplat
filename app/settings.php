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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!--</div>-->

<?php require_once 'footer.php'; ?>