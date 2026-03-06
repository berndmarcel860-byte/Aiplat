<?php
require_once 'config.php';
session_start();

// Check if user has OTP session
if (!isset($_SESSION['otp_user_id']) || !isset($_SESSION['login_otp'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Process OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp'] ?? '');
    
    if (empty($entered_otp)) {
        $error = "Bitte geben Sie den OTP-Code ein";
    } else {
        // Check if OTP has expired
        if (time() > strtotime($_SESSION['otp_expire'])) {
            $error = "Der OTP-Code ist abgelaufen. Bitte fordern Sie einen neuen an.";
        } 
        // Verify OTP
        elseif ($entered_otp === $_SESSION['login_otp']) {
            // OTP is correct, complete login
            $userId = $_SESSION['otp_user_id'];
            
            // Get user details
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Clear OTP session variables
                unset($_SESSION['otp_user_id']);
                unset($_SESSION['login_otp']);
                unset($_SESSION['otp_expire']);
                
                // Track successful OTP verification for 1-hour window (session + DB for persistence)
                $_SESSION['last_otp_verified_at'] = time();
                
                // Set user session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['last_activity'] = time();
                
                // Update last login and persist OTP grace period to DB
                $pdo->prepare("UPDATE users SET last_login = NOW(), last_otp_verified_at = NOW() WHERE id = ?")->execute([$user['id']]);
                
                // Log successful OTP verification
                $pdo->prepare("UPDATE otp_logs SET is_verified = 1 WHERE user_id = ? AND otp_code = ? AND purpose = 'login' ORDER BY created_at DESC LIMIT 1")
                    ->execute([$userId, $entered_otp]);
                
                // Redirect to dashboard
                $_SESSION['success_message'] = "Erfolgreich angemeldet!";
                header("Location: index.php");
                exit();
            }
        } else {
            $error = "Ungültiger OTP-Code. Bitte versuchen Sie es erneut.";
            
            // Log failed attempt
            $ip = $_SERVER['REMOTE_ADDR'];
            $pdo->prepare("INSERT INTO login_logs (email, ip_address, success, reason) VALUES (?, ?, 0, 'Invalid OTP')")
                ->execute([$_SESSION['otp_email'] ?? '', $ip]);
        }
    }
}

// Handle resend OTP request
if (isset($_GET['resend']) && $_GET['resend'] === '1') {
    // Check if user can resend (cooldown)
    if (!isset($_SESSION['last_otp_sent']) || (time() - $_SESSION['last_otp_sent']) > 60) {
        $userId = $_SESSION['otp_user_id'];
        
        // Get user email
        $stmt = $pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate new OTP
            $otp = sprintf("%06d", rand(0, 999999));
            $expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes
            
            // Update session
            $_SESSION['login_otp'] = $otp;
            $_SESSION['otp_expire'] = $expires;
            $_SESSION['last_otp_sent'] = time();
            
            // Store in database
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $pdo->prepare("INSERT INTO otp_logs (user_id, otp_code, purpose, ip_address, expires_at) VALUES (?, ?, 'login', ?, ?)");
            $stmt->execute([$userId, $otp, $ip, $expires]);
            
            // Send email
            try {
                require_once __DIR__ . '/vendor/autoload.php';
                
                // Get SMTP settings
                $stmt_smtp = $pdo->query("SELECT * FROM smtp_settings WHERE id = 1");
                $smtp = $stmt_smtp->fetch();
                
                if ($smtp) {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    
                    $mail->isSMTP();
                    $mail->Host = $smtp['host'];
                    $mail->SMTPAuth = !empty($smtp['username']);
                    $mail->Username = $smtp['username'];
                    $mail->Password = $smtp['password'];
                    $mail->SMTPSecure = $smtp['encryption'];
                    $mail->Port = $smtp['port'];
                    
                    $mail->setFrom($smtp['from_email'], $smtp['from_name']);
                    $mail->addAddress($user['email'], $user['first_name']);
                    
                    $mail->isHTML(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->Subject = "Ihr Anmeldecode für Crypto Finanz";
                    
                    $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa; padding: 20px;'>
                        <div style='background: linear-gradient(135deg, #2950a8, #2da9e3); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                            <h1 style='color: white; margin: 0;'>Crypto Finanz</h1>
                        </div>
                        
                        <div style='background: white; padding: 40px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                            <h2 style='color: #2c3e50; margin-top: 0;'>Ihr Einmalcode</h2>
                            
                            <p style='color: #555; font-size: 16px; line-height: 1.6;'>
                                Hallo {$user['first_name']},
                            </p>
                            
                            <p style='color: #555; font-size: 16px; line-height: 1.6;'>
                                Verwenden Sie diesen Code, um sich bei Ihrem Konto anzumelden:
                            </p>
                            
                            <div style='background: #f8f9fa; padding: 25px; border-radius: 8px; text-align: center; margin: 30px 0; border: 2px dashed #2950a8;'>
                                <div style='font-size: 36px; font-weight: bold; letter-spacing: 10px; color: #2950a8;'>
                                    {$otp}
                                </div>
                            </div>
                            
                            <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                                <p style='margin: 0; color: #856404; font-size: 14px;'>
                                    <strong>⏱️ Gültigkeit:</strong> Dieser Code ist 5 Minuten gültig.
                                </p>
                            </div>
                            
                            <div style='background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                                <p style='margin: 0; color: #0c5460; font-size: 14px;'>
                                    <strong>🔒 Sicherheit:</strong> Teilen Sie diesen Code niemals mit anderen.
                                </p>
                            </div>
                            
                            <hr style='margin: 30px 0; border: none; border-top: 1px solid #dee2e6;'>
                            
                            <p style='font-size: 12px; color: #999; margin-bottom: 0;'>
                                Wenn Sie sich nicht angemeldet haben, ignorieren Sie diese E-Mail bitte.
                            </p>
                            
                            <p style='font-size: 12px; color: #999; margin-top: 10px;'>
                                Mit freundlichen Grüßen,<br>
                                Ihr Crypto Finanz Team
                            </p>
                        </div>
                    </div>
                    ";
                    
                    $mail->send();
                    $success = "Ein neuer OTP-Code wurde an Ihre E-Mail gesendet.";
                }
            } catch (Exception $e) {
                error_log("Failed to resend OTP: " . $e->getMessage());
                $error = "Fehler beim Senden des OTP-Codes. Bitte versuchen Sie es später erneut.";
            }
        }
    } else {
        $remaining = 60 - (time() - $_SESSION['last_otp_sent']);
        $error = "Bitte warten Sie {$remaining} Sekunden, bevor Sie einen neuen Code anfordern.";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP-Verifizierung | Crypto Finanz</title>
    <link href="assets/css/app.min.css" rel="stylesheet">
    <style>
        .otp-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .otp-card {
            max-width: 500px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }
        .otp-header {
            background: linear-gradient(135deg, #2950a8, #2da9e3);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .otp-header h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 600;
        }
        .otp-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .otp-body {
            padding: 40px 30px;
            background: white;
        }
        .otp-input {
            font-size: 32px;
            text-align: center;
            letter-spacing: 15px;
            font-weight: bold;
            padding: 20px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .otp-input:focus {
            border-color: #2950a8;
            box-shadow: 0 0 0 0.2rem rgba(41, 80, 168, 0.25);
        }
        .btn-verify {
            background: linear-gradient(135deg, #2950a8, #2da9e3);
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 80, 168, 0.3);
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-box p {
            margin: 0;
            color: #555;
            font-size: 14px;
        }
        .resend-link {
            color: #2950a8;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        .resend-link:hover {
            color: #1e3a7a;
            text-decoration: underline;
        }
        .timer-text {
            font-size: 13px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="otp-container">
        <div class="otp-card">
            <div class="otp-header">
                <i class="anticon anticon-mail" style="font-size: 48px; margin-bottom: 15px;"></i>
                <h2>E-Mail-Verifizierung</h2>
                <p>Geben Sie den 6-stelligen Code ein, den wir an Ihre E-Mail gesendet haben</p>
            </div>
            
            <div class="otp-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="anticon anticon-close-circle mr-2"></i>
                    <?= htmlspecialchars($error, ENT_QUOTES) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="anticon anticon-check-circle mr-2"></i>
                    <?= htmlspecialchars($success, ENT_QUOTES) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="otpForm">
                    <div class="form-group">
                        <label for="otp" class="font-weight-bold" style="color: #2c3e50;">
                            <i class="anticon anticon-lock mr-2"></i>Einmalcode (OTP)
                        </label>
                        <input type="text" 
                               class="form-control otp-input" 
                               id="otp" 
                               name="otp" 
                               maxlength="6" 
                               pattern="[0-9]{6}" 
                               placeholder="000000"
                               required 
                               autofocus
                               autocomplete="off">
                        <small class="form-text text-muted text-center mt-2">
                            <i class="anticon anticon-clock-circle mr-1"></i>
                            Der Code ist 5 Minuten gültig
                        </small>
                    </div>
                    
                    <div class="info-box">
                        <p>
                            <i class="anticon anticon-info-circle mr-2" style="color: #17a2b8;"></i>
                            Überprüfen Sie Ihren Posteingang und Spam-Ordner auf eine E-Mail von <strong>Crypto Finanz</strong>.
                        </p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-verify btn-block">
                        <i class="anticon anticon-check-circle mr-2"></i>Code verifizieren
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <p class="text-muted mb-2">Code nicht erhalten?</p>
                    <a href="?resend=1" class="resend-link">
                        <i class="anticon anticon-reload mr-1"></i>Neuen Code senden
                    </a>
                    <p class="timer-text mt-2">
                        <?php if (isset($_SESSION['last_otp_sent'])): ?>
                            Letzter Code gesendet vor <?= time() - $_SESSION['last_otp_sent'] ?> Sekunden
                        <?php endif; ?>
                    </p>
                </div>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <a href="logout.php" class="text-muted" style="font-size: 14px;">
                        <i class="anticon anticon-arrow-left mr-1"></i>Zurück zur Anmeldung
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/vendors.min.js"></script>
    <script>
        // Auto-submit when 6 digits entered
        document.getElementById('otp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) {
                // Auto-submit after brief delay
                setTimeout(function() {
                    document.getElementById('otpForm').submit();
                }, 300);
            }
        });
        
        // Prevent paste of non-numeric characters
        document.getElementById('otp').addEventListener('paste', function(e) {
            let paste = (e.clipboardData || window.clipboardData).getData('text');
            paste = paste.replace(/[^0-9]/g, '').substring(0, 6);
            this.value = paste;
            e.preventDefault();
            
            if (paste.length === 6) {
                setTimeout(function() {
                    document.getElementById('otpForm').submit();
                }, 300);
            }
        });
    </script>
</body>
</html>