<?php
require_once 'config.php';
require_once 'session.php';
require_once __DIR__ . '/EmailHelper.php';

// Redirect already-logged-in users to the dashboard
if (!empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables
$error = '';
$email = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = "Bitte geben Sie Ihre E-Mail-Adresse und Ihr Passwort ein.";
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if account is active
            if ($user['status'] !== 'active') {
                $statusLabels = [
                    'suspended' => 'gesperrt',
                    'banned'    => 'gesperrt',
                    'inactive'  => 'inaktiv',
                    'pending'   => 'ausstehend',
                ];
                $statusLabel = $statusLabels[strtolower($user['status'])] ?? strtolower($user['status']);
                $error = "Ihr Konto ist derzeit " . $statusLabel . ". Bitte kontaktieren Sie den Support.";
            } else {
                // Password verified - Check if OTP verification is still valid
                // Session already initialized by session.php include (line 3)
                
                // Check if user verified OTP within last hour (3600 seconds).
                // Use session value when available; fall back to DB column so the
                // grace period survives browser restarts and session GC expiry.
                $sessionOtpOk = isset($_SESSION['last_otp_verified_at']) && (time() - $_SESSION['last_otp_verified_at']) < 3600;
                $dbOtpOk      = !empty($user['last_otp_verified_at']) && (time() - strtotime($user['last_otp_verified_at'])) < 3600;
                
                if ($sessionOtpOk || $dbOtpOk) {
                    // OTP still valid - skip verification and complete login immediately
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['last_activity'] = time();
                    // Always use the DB timestamp as the authoritative cache value
                    $_SESSION['last_otp_verified_at'] = !empty($user['last_otp_verified_at'])
                        ? strtotime($user['last_otp_verified_at'])
                        : ($_SESSION['last_otp_verified_at'] ?? time());
                    
                    // Update last login
                    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                    
                    // Redirect to dashboard
                    header("Location: index.php");
                    exit();
                }
                
                // OTP expired or not verified - send OTP for two-factor authentication
                // Check rate limiting for OTP requests (max 3 per hour)
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM otp_logs WHERE user_id = ? AND purpose = 'login' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                $stmt->execute([$user['id']]);
                $otpCount = $stmt->fetch()['count'];
                
                if ($otpCount >= 30) {
                    $error = "Zu viele OTP-Anfragen. Bitte versuchen Sie es in 1 Stunde erneut.";
                } else {
                    // Generate 6-digit OTP
                    $otp = sprintf("%06d", rand(0, 999999));
                    $expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes
                    $ip = $_SERVER['REMOTE_ADDR'];
                    
                    // Store OTP in session
                    $_SESSION['otp_user_id'] = $user['id'];
                    $_SESSION['otp_email'] = $user['email'];
                    $_SESSION['login_otp'] = $otp;
                    $_SESSION['otp_expire'] = $expires;
                    $_SESSION['remember_me'] = $remember; // Save for later
                    $_SESSION['last_otp_sent'] = time();
                    
                    // Store OTP in database
                    $stmt = $pdo->prepare("INSERT INTO otp_logs (user_id, otp_code, purpose, expires_at, created_at) VALUES (?, ?, 'login', ?, NOW())");
                    $stmt->execute([$user['id'], $otp, $expires]);
                    
                    // Send OTP via EmailHelper (uses login_otp DB template when available)
                    $emailHelper = new EmailHelper($pdo);
                    $sent = $emailHelper->sendLoginOtpEmail($user['id'], [
                        'otp_code'            => $otp,
                        'otp_expires_minutes' => '5',
                    ]);

                    if ($sent) {
                        // Log successful login attempt
                        $stmt = $pdo->prepare("INSERT INTO login_logs (email, ip_address, user_agent, success) VALUES (?, ?, ?, 1)");
                        $stmt->execute([$email, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);

                        // Redirect to OTP verification page
                        header("Location: verify-otp.php");
                        exit();
                    } else {
                        $error = "Fehler beim Senden des OTP-Codes. Bitte versuchen Sie es später erneut.";
                    }
                }
            }
        } else {
            $error = "Ungültige E-Mail-Adresse oder Passwort.";
            
            // Log failed login attempt
            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO login_logs (email, ip_address, user_agent, success) VALUES (?, ?, ?, 0)");
            $stmt->execute([$email, $ip, $userAgent]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kundenportal Anmeldung | Fund Recovery Services</title>
    <link href="assets/css/app.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0d1b2a 0%, #1b2a3b 60%, #102030 100%);
            min-height: 100vh;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .login-card {
            border-radius: 12px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255,255,255,0.07);
            background: #ffffff;
            max-width: 460px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #1a3a5c 0%, #0d2137 100%);
            border-radius: 12px 12px 0 0;
            padding: 28px 32px 22px;
            text-align: center;
        }
        .login-header img {
            height: 56px;
            margin-bottom: 14px;
        }
        .login-header .header-title {
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .login-header .header-subtitle {
            color: rgba(255,255,255,0.65);
            font-size: 0.82rem;
            margin-top: 4px;
        }
        .login-body {
            padding: 30px 32px 28px;
        }
        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.9rem;
            color: #111827;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #1a3a5c;
            box-shadow: 0 0 0 3px rgba(26, 58, 92, 0.15);
            outline: none;
        }
        .btn-signin {
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
        .btn-signin:hover {
            opacity: 0.88;
        }
        .forgot-link {
            font-size: 0.82rem;
            color: #1a3a5c;
            text-decoration: none;
        }
        .forgot-link:hover {
            text-decoration: underline;
        }
        .login-footer {
            border-top: 1px solid #f0f0f0;
            padding: 16px 32px 20px;
            text-align: center;
            font-size: 0.8rem;
            color: #9ca3af;
        }
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 0.78rem;
            color: #6b7280;
            margin-top: 14px;
        }
        .security-badge svg {
            width: 14px;
            height: 14px;
            fill: #6b7280;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="assets/images/logo/logo.png" alt="Fund Recovery Services">
                <p class="header-title">Kundenportal</p>
                <p class="header-subtitle">Sicherer Zugang zu Ihrem Rückforderungsfall</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="font-size:0.88rem; border-radius:8px; margin-bottom:18px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="form-group mb-3">
                        <label for="email">E-Mail-Adresse</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo htmlspecialchars($email); ?>" required autofocus
                               placeholder="ihre@email.de">
                    </div>

                    <div class="form-group mb-2">
                        <label for="password">Passwort</label>
                        <input type="password" class="form-control" id="password" name="password"
                               required placeholder="••••••••">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3" style="font-size:0.83rem;">
                        <label class="d-flex align-items-center gap-1 mb-0" style="cursor:pointer; color:#374151;">
                            <input type="checkbox" name="remember" id="remember" style="margin-right:5px;">
                            Angemeldet bleiben
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Passwort vergessen?</a>
                    </div>

                    <button type="submit" class="btn-signin">Anmelden</button>
                </form>

                <div class="security-badge">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                    256-Bit SSL-verschlüsselt &amp; sichere Verbindung
                </div>
            </div>
            <div class="login-footer">
                Noch kein Konto? &nbsp;<a href="register.php" style="color:#1a3a5c; font-weight:600; text-decoration:none;">Zugang anfragen</a>
                &nbsp;&middot;&nbsp; <a href="../contact.php" style="color:#9ca3af; text-decoration:none;">Support kontaktieren</a>
            </div>
        </div>
    </div>
</body>
</html>