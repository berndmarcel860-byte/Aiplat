<?php
/**
 * Email Verification Handler
 * 
 * Verifies user email address using the token from the email link
 */

session_start();
require_once 'config.php';

$pageTitle = "Email Verification";
$success = false;
$error = false;
$message = '';

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $error = true;
    $message = 'Invalid verification link. No token provided.';
} else {
    $token = $_GET['token'];
    
    try {
        // Find user with this token
        $stmt = $pdo->prepare("
            SELECT id, email, is_verified, verification_token, created_at
            FROM users 
            WHERE verification_token = ? 
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $error = true;
            $message = 'Invalid verification link. Token not found.';
        } elseif ($user['is_verified']) {
            $success = true;
            $message = 'Your email has already been verified!';
        } else {
            // Check token expiration (stored in session or default to 1 hour from now as fallback)
            // Tokens are valid for 1 hour from when they were sent
            $tokenExpiresAt = isset($_SESSION['verification_token_expires_' . $user['id']]) 
                ? $_SESSION['verification_token_expires_' . $user['id']]
                : date('Y-m-d H:i:s', strtotime('+1 hour')); // Default: tokens valid for 1 hour
            
            if (strtotime($tokenExpiresAt) < time()) {
                $error = true;
                $message = 'Verification link has expired. Please request a new one from your profile.';
            } else {
                // Verify the email
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET is_verified = 1,
                        verification_token = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$user['id']]);
                
                $success = true;
                $message = 'Email verified successfully! You can now access all features.';
                
                // If user is logged in and it's their email being verified, update session
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']) {
                    $_SESSION['email_verified'] = true;
                }
                
                // Clear expiration from session
                unset($_SESSION['verification_token_expires_' . $user['id']]);
            }
        }
        
    } catch (PDOException $e) {
        error_log("Email verification error: " . $e->getMessage());
        $error = true;
        $message = 'An error occurred during verification. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .verification-container {
            max-width: 500px;
            width: 100%;
            padding: 20px;
        }
        .verification-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            text-align: center;
        }
        .icon-container {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
        }
        .icon-success {
            background: linear-gradient(135deg, #6dd5ed 0%, #2193b0 100%);
            color: white;
        }
        .icon-error {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .verification-card h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .verification-card p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn-custom {
            padding: 12px 40px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        .btn-success-custom {
            background: linear-gradient(135deg, #6dd5ed 0%, #2193b0 100%);
            border: none;
            color: white;
        }
        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-card">
            <?php if ($success): ?>
                <div class="icon-container icon-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Email Verified!</h2>
                <p><?= htmlspecialchars($message) ?></p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php" class="btn btn-custom btn-success-custom">
                        <i class="fas fa-home me-2"></i>Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-custom btn-primary-custom">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Continue
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <div class="icon-container icon-error">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h2>Verification Failed</h2>
                <p><?= htmlspecialchars($message) ?></p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="btn btn-custom btn-primary-custom">
                        <i class="fas fa-redo me-2"></i>Request New Link
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-custom btn-primary-custom">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Continue
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>