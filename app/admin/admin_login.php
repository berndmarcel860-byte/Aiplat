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
    <title>Admin Login | Scam Recovery</title>
    <link href="../assets/css/app.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .login-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: none;
            max-width: 450px;
            margin: 0 auto;
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
        .alert {
            border-radius: 8px;
        }
        .admin-login-title {
            color: #3f51b5;
            font-weight: 600;
            margin-bottom: 1.5rem;
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
                                <img src="../assets/images/logo/logo.png" alt="Scam Recovery">
                            </div>
                            <h4 class="text-center admin-login-title">Admin Portal Login</h4>
                            
                            <?php if (isset($_GET['expired'])): ?>
                                <div class="alert alert-warning">Your session has expired. Please login again.</div>
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['logout'])): ?>
                                <div class="alert alert-success">You have been successfully logged out.</div>
                            <?php endif; ?>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            
                            <form method="POST" action="admin_login.php">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">
                                
                                <div class="form-group">
                                    <label for="email">Admin Email</label>
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
                                        <a href="admin_forgot_password.php">Forgot password?</a>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Core Vendors JS -->
    <script src="../assets/js/vendors.min.js"></script>
    
    <!-- Page JS -->
    <script>
    // Focus on email field by default
    document.getElementById('email').focus();
    
    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</body>
</html>