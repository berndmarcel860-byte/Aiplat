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
        $error = "Please enter both email and password";
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if account is active
            if ($user['status'] !== 'active') {
                $error = "Your account is currently " . ucfirst($user['status']);
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
            $error = "Invalid email or password";
            
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Scam Recovery Dashboard</title>
    <link href="assets/css/app.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .login-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo img {
            height: 60px;
        }
        .form-control:focus {
            border-color: #5c6bc0;
            box-shadow: 0 0 0 0.2rem rgba(92, 107, 192, 0.25);
        }
        .btn-primary {
            background-color: #5c6bc0;
            border-color: #5c6bc0;
        }
        .btn-primary:hover {
            background-color: #3f51b5;
            border-color: #3f51b5;
        }
        .forgot-password {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card login-card">
                        <div class="card-body p-4">
                            <div class="login-logo">
                                <img src="assets/images/logo/logo.png" alt="Scam Recovery">
                            </div>
                            <h4 class="text-center mb-4">Sign in to your account</h4>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            
                            <form method="POST" action="login.php">
                                <div class="form-group">
                                    <label for="email">Email address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                
                                <div class="form-group form-check d-flex justify-content-between">
                                    <div>
                                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>
                                    <div class="forgot-password">
                                        <a href="forgot-password.php">Forgot password?</a>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block">Sign in</button>
                                
                                <div class="text-center mt-3">
                                    <p>Don't have an account? <a href="register.php">Sign up</a></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>