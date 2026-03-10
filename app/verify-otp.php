<?php
require_once 'config.php';
require_once __DIR__ . '/EmailHelper.php';
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
            
            // Send email via EmailHelper (uses login_otp DB template when available)
            $emailHelper = new EmailHelper($pdo);
            $sent = $emailHelper->sendLoginOtpEmail($userId, [
                'otp_code'            => $otp,
                'otp_expires_minutes' => '5',
            ]);

            if ($sent) {
                $success = "Ein neuer OTP-Code wurde an Ihre E-Mail gesendet.";
            } else {
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
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0d1b2a 0%, #1b2a3b 60%, #102030 100%);
            min-height: 100vh;
        }
        .otp-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .otp-card {
            border-radius: 12px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255,255,255,0.07);
            background: #ffffff;
            max-width: 460px;
            width: 100%;
        }
        .otp-header {
            background: linear-gradient(135deg, #1a3a5c 0%, #0d2137 100%);
            border-radius: 12px 12px 0 0;
            padding: 28px 32px 22px;
            text-align: center;
        }
        .otp-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
        }
        .otp-icon svg {
            width: 30px;
            height: 30px;
            fill: #ffffff;
        }
        .otp-header .header-title {
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .otp-header .header-subtitle {
            color: rgba(255,255,255,0.65);
            font-size: 0.82rem;
            margin-top: 4px;
        }
        .otp-body {
            padding: 30px 32px 28px;
            background: white;
            border-radius: 0 0 12px 12px;
        }
        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .otp-input {
            font-size: 28px;
            text-align: center;
            letter-spacing: 12px;
            font-weight: bold;
            padding: 14px 20px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            color: #111827;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        .otp-input:focus {
            border-color: #1a3a5c;
            box-shadow: 0 0 0 3px rgba(26, 58, 92, 0.15);
            outline: none;
        }
        .btn-verify {
            background: linear-gradient(135deg, #1a3a5c 0%, #0d2137 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 11px;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            transition: opacity 0.2s;
            letter-spacing: 0.3px;
        }
        .btn-verify:hover {
            opacity: 0.88;
            color: #ffffff;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #1a3a5c;
            padding: 12px 14px;
            border-radius: 6px;
            margin: 18px 0;
        }
        .info-box p {
            margin: 0;
            color: #555;
            font-size: 0.82rem;
        }
        .resend-link {
            color: #1a3a5c;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .resend-link:hover {
            text-decoration: underline;
        }
        .timer-text {
            font-size: 0.78rem;
            color: #9ca3af;
        }
        .otp-footer {
            border-top: 1px solid #f0f0f0;
            padding: 14px 32px 18px;
            text-align: center;
            font-size: 0.8rem;
            color: #9ca3af;
        }
        .otp-footer a {
            font-size: 0.8rem;
        }
        .resend-hint {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="otp-container">
        <div class="otp-card">
            <div class="otp-header">
                <div class="otp-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                </div>
                <p class="header-title">E-Mail-Verifizierung</p>
                <p class="header-subtitle">Geben Sie den 6-stelligen Code ein, den wir an Ihre E-Mail gesendet haben</p>
            </div>

            <div class="otp-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success, ENT_QUOTES) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="otpForm">
                    <div class="form-group">
                        <label for="otp">Einmalcode (OTP)</label>
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
                            Der Code ist 5 Minuten gültig
                        </small>
                    </div>

                    <div class="info-box">
                        <p>Überprüfen Sie Ihren Posteingang und Spam-Ordner auf eine E-Mail von unserem System.</p>
                    </div>

                    <button type="submit" class="btn btn-verify">
                        Code verifizieren
                    </button>
                </form>

                <div class="text-center mt-4">
                    <p class="text-muted mb-2 resend-hint">Code nicht erhalten?</p>
                    <a href="?resend=1" class="resend-link">Neuen Code senden</a>
                    <p class="timer-text mt-2">
                        <?php if (isset($_SESSION['last_otp_sent'])): ?>
                            Letzter Code gesendet vor <?= time() - $_SESSION['last_otp_sent'] ?> Sekunden
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="otp-footer">
                <a href="logout.php" class="text-muted">&#8592; Zurück zur Anmeldung</a>
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