<?php
require_once '../config.php';
require_once 'admin_session.php'; // Include admin session at the top

// Check if admin is already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_index.php");
    exit();
}

// Initialize variables
$error = '';
$email = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['admin_csrf_token']) {
        $error = "Invalid form submission";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validate inputs
        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password";
        } else {
            // Check if admin exists
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Check if account is active
                if ($admin['status'] !== 'active') {
                    $error = "Your account is currently " . ucfirst($admin['status']);
                } else {
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['last_activity'] = time();
                    
                    // Set remember me cookie if requested
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expiry = time() + 60 * 60 * 24 * 30; // 30 days
                        
                        // Store token in database
                        $stmt = $pdo->prepare("INSERT INTO admin_remember_tokens (admin_id, token, expires) VALUES (?, ?, ?)");
                        $stmt->execute([
                            $admin['id'],
                            $token,
                            date('Y-m-d H:i:s', $expiry)
                        ]);
                        
                        // Set secure cookie
                        setcookie('admin_remember', $token, [
                            'expires' => $expiry,
                            'path' => '/admin',
                            'domain' => '',
                            'secure' => true,
                            'httponly' => true,
                            'samesite' => 'Strict'
                        ]);
                    }
                    
                    // Update last login
                    $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
                    
                    // Log login
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $stmt = $pdo->prepare("INSERT INTO admin_login_logs (admin_id, ip_address, user_agent) VALUES (?, ?, ?)");
                    $stmt->execute([$admin['id'], $ip, $userAgent]);
                    
                    // Redirect to admin dashboard
                    header("Location: admin_index.php");
                    exit();
                }
            } else {
                $error = "Invalid email or password";
                
                // Log failed login attempt
                $ip = $_SERVER['REMOTE_ADDR'];
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $stmt = $pdo->prepare("INSERT INTO admin_login_logs (email, ip_address, user_agent, success) VALUES (?, ?, ?, 0)");
                $stmt->execute([$email, $ip, $userAgent]);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Fund Recovery Services</title>
    <link href="../assets/css/app.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, #1e3a5c 0%, #0d2137 100%);
            border-radius: 12px 12px 0 0;
            padding: 28px 32px 22px;
            text-align: center;
        }
        .login-header img {
            height: 52px;
            margin-bottom: 14px;
        }
        .login-header .header-title {
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .login-header .header-subtitle {
            color: rgba(255,255,255,0.6);
            font-size: 0.82rem;
            margin-top: 4px;
        }
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 0.75rem;
            font-weight: 700;
            color: rgba(255,255,255,0.9);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }
        .login-body {
            padding: 30px 32px 28px;
        }
        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            display: block;
        }
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.9rem;
            color: #111827;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        .form-control:focus {
            border-color: #1e3a5c;
            box-shadow: 0 0 0 3px rgba(30, 58, 92, 0.15);
            outline: none;
        }
        .btn-signin {
            background: linear-gradient(135deg, #1e3a5c 0%, #0d2137 100%);
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
            color: #1e3a5c;
            text-decoration: none;
        }
        .forgot-link:hover {
            text-decoration: underline;
        }
        .login-footer {
            border-top: 1px solid #f0f0f0;
            padding: 14px 32px 18px;
            text-align: center;
            font-size: 0.78rem;
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
        .alert {
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../assets/images/logo/logo.png" alt="Fund Recovery Services">
                <p class="header-title">Admin Portal</p>
                <p class="header-subtitle">Restricted access — authorised personnel only</p>
                <div class="admin-badge">
                    <svg viewBox="0 0 24 24" width="11" height="11" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                    Secured Admin Access
                </div>
            </div>

            <div class="login-body">
                <?php if (isset($_GET['expired'])): ?>
                    <div class="alert alert-warning">Your session has expired. Please sign in again.</div>
                <?php endif; ?>

                <?php if (isset($_GET['logout'])): ?>
                    <div class="alert alert-success">You have been successfully signed out.</div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="admin_login.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">

                    <div class="form-group mb-3">
                        <label for="email">Admin Email Address</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo htmlspecialchars($email); ?>"
                               required autofocus placeholder="admin@example.com">
                    </div>

                    <div class="form-group mb-2">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password"
                               required placeholder="••••••••">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3" style="font-size:0.83rem;">
                        <label class="d-flex align-items-center mb-0" style="cursor:pointer;color:#374151;gap:5px;">
                            <input type="checkbox" name="remember" id="remember" style="margin-right:4px;">
                            Remember me
                        </label>
                        <a href="admin_forgot_password.php" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-signin">Sign In to Admin Portal</button>
                </form>

                <div class="security-badge">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                    256-bit SSL encrypted &amp; secure connection
                </div>
            </div>

            <div class="login-footer">
                User portal &nbsp;&middot;&nbsp;
                <a href="../login.php" style="color:#1e3a5c;font-weight:600;text-decoration:none;">Client Login</a>
                &nbsp;&middot;&nbsp;
                <a href="../contact.php" style="color:#9ca3af;text-decoration:none;">Support</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/vendors.min.js"></script>
    <script>
    document.getElementById('email').focus();
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</body>
</html>