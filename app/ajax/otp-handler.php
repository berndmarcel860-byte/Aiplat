<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access', 401);
    }

    $action = $_POST['action'] ?? '';
    if (!in_array($action, ['send', 'verify'])) {
        throw new Exception('Invalid action', 400);
    }

    // Fetch user email
    $stmt = $pdo->prepare("SELECT id, email, first_name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) throw new Exception('User not found');

    // Load SMTP
    $smtpStmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE is_active = 1 LIMIT 1");
    $smtpStmt->execute();
    $smtp = $smtpStmt->fetch();
    if (!$smtp) throw new Exception('SMTP not configured');

    // ------------------------------------------------------------
    // 🔹 SEND OTP
    // ------------------------------------------------------------
    if ($action === 'send') {
        $otp = rand(100000, 999999);
        $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // Store in DB
        $pdo->prepare("INSERT INTO otp_logs (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'withdrawal', ?)")
            ->execute([$user['id'], $otp, $expires]);

        // Also store in session for fast verification
        $_SESSION['withdraw_otp'] = $otp;
        $_SESSION['otp_expire'] = $expires;
        $_SESSION['otp_verified'] = false;

        // Prepare email
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['username'];
        $mail->Password = $smtp['password'];
        $mail->SMTPSecure = $smtp['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtp['port'];
        $mail->setFrom($smtp['from_email'], $smtp['from_name']);
        $mail->addAddress($user['email'], $user['first_name']);
        $mail->isHTML(true);
        $mail->Subject = "Your Withdrawal OTP Code";
        $mail->Body = "<p>Dear {$user['first_name']},</p>
            <p>Your one-time code is <b>{$otp}</b>. It expires in 5 minutes.</p>
            <p>If you didn’t request this, please ignore this email.</p>";

        $mail->send();

        echo json_encode([
            'success' => true,
            'message' => 'OTP sent successfully to your registered email.'
        ]);
        exit;
    }

    // ------------------------------------------------------------
    // 🔹 VERIFY OTP
    // ------------------------------------------------------------
    if ($action === 'verify') {
        $code = trim($_POST['otp_code'] ?? '');
        if (empty($code)) throw new Exception('Please enter the OTP code.');

        // Check in session first
        if (!isset($_SESSION['withdraw_otp']) || !isset($_SESSION['otp_expire'])) {
            throw new Exception('No OTP request found. Please resend.');
        }

        if (new DateTime() > new DateTime($_SESSION['otp_expire'])) {
            throw new Exception('OTP expired. Please request a new one.');
        }

        if ($code != $_SESSION['withdraw_otp']) {
            throw new Exception('Invalid OTP code.');
        }

        // Mark verified in DB
        $pdo->prepare("UPDATE otp_logs SET is_verified = 1 WHERE user_id = ? AND otp_code = ?")
            ->execute([$user['id'], $code]);

        $_SESSION['otp_verified'] = true;

        echo json_encode([
            'success' => true,
            'message' => 'OTP verified successfully. You can now submit your withdrawal.'
        ]);
        exit;
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

