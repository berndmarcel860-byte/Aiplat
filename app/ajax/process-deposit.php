<?php
// Use statements must be at the very top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../EmailHelper.php';

// Check if PHPMailer is available
$phpMailerAvailable = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $phpMailerAvailable = true;
}


header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }

    // Check user authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access - Please login', 401);
    }

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Security error - Invalid CSRF token', 403);
    }

    // Validate and sanitize inputs
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $methodCode = isset($_POST['payment_method']) ? htmlspecialchars($_POST['payment_method'], ENT_QUOTES, 'UTF-8') : null;

    if (!$amount || $amount <= 0) {
        throw new Exception('Please enter a valid deposit amount (minimum $10)', 400);
    }

    if ($amount < 10) {
        throw new Exception('Minimum deposit amount is $10', 400);
    }

    if (empty($methodCode)) {
        throw new Exception('Please select a payment method', 400);
    }

    // Get user information - Fixed to use correct column names from your schema
    $userStmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();

    if (!$user) {
        throw new Exception('User not found', 404);
    }

    // Get payment method ID and name
    $stmt = $pdo->prepare("SELECT id, method_name FROM payment_methods WHERE method_code = ? AND is_active = 1");
    $stmt->execute([$methodCode]);
    $paymentMethod = $stmt->fetch();

    if (!$paymentMethod) {
        throw new Exception('Invalid payment method selected', 400);
    }
    $paymentMethodId = $paymentMethod['id'];

    // Handle file upload
    $proofPath = null;
    if (isset($_FILES['proof_of_payment'])) {
        $file = $_FILES['proof_of_payment'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error'], 400);
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Only JPG, PNG, and PDF files are allowed', 400);
        }

        // Validate file size (max 2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            throw new Exception('File size must be less than 2MB', 400);
        }

        // Create upload directory if it doesn't exist
        $uploadDir = '../uploads/proofs/';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Failed to create upload directory', 500);
            }
        }

        // Generate unique filename
        $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
        $proofPath = $uploadDir . uniqid('deposit_') . '.' . $fileExt;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $proofPath)) {
            throw new Exception('Failed to save proof of payment', 500);
        }
    } else {
        throw new Exception('Proof of payment is required', 400);
    }

    // Generate unique reference number
    $reference = 'DEP-' . time() . '-' . strtoupper(substr(uniqid(), -6));

    // Start database transaction
    $pdo->beginTransaction();

    try {
        // Insert deposit record
        $stmt = $pdo->prepare("INSERT INTO deposits 
                              (user_id, amount, method_code, reference, proof_path, status) 
                              VALUES (:user_id, :amount, :method, :reference, :proof_path, 'pending')");
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':amount' => $amount,
            ':method' => $methodCode,
            ':reference' => $reference,
            ':proof_path' => $proofPath
        ]);
        
        $depositId = $pdo->lastInsertId();

        // Insert transaction record
        $stmt = $pdo->prepare("INSERT INTO transactions 
                              (user_id, type, amount, payment_method_id, reference, status, proof_path) 
                              VALUES (:user_id, 'deposit', :amount, :method_id, :reference, 'pending', :proof_path)");
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':amount' => $amount,
            ':method_id' => $paymentMethodId,
            ':reference' => $reference,
            ':proof_path' => $proofPath
        ]);
        
        $transactionId = $pdo->lastInsertId();

        // Insert transaction attachment
        $stmt = $pdo->prepare("INSERT INTO transaction_attachments 
                              (transaction_id, file_path, file_type) 
                              VALUES (:transaction_id, :file_path, :file_type)");
        $stmt->execute([
            ':transaction_id' => $transactionId,
            ':file_path' => $proofPath,
            ':file_type' => mime_content_type($proofPath)
        ]);

        // Update user balance (commented out for pending status)
        // $stmt = $pdo->prepare("UPDATE users SET balance = balance + :amount WHERE id = :user_id");
        // $stmt->execute([
        //     ':amount' => $amount,
        //     ':user_id' => $_SESSION['user_id']
        // ]);

        // Get current balance
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $currentBalance = $stmt->fetchColumn();

        // Send email notification using EmailHelper with deposit_pending template
        try {
            $emailHelper = new EmailHelper($pdo);
            
            // Prepare custom variables for email template
            $customVars = [
                'amount' => $amount,
                'reference' => $reference,
                'payment_method' => $paymentMethod['method_name'],
                'payment_details' => $paymentMethod['method_name'],
                'transaction_id' => $transactionId ? 'TXN-' . $transactionId : $reference,
                'transaction_date' => date('Y-m-d H:i:s')
            ];
            
            // Send email using EmailHelper with deposit_pending template key
            $emailHelper->sendEmail('deposit_pending', $user['id'], $customVars);
        } catch (Exception $emailError) {
            error_log("Email sending failed: " . $emailError->getMessage());
            // Continue processing even if email fails
        }

        // Commit transaction
        $pdo->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Your deposit is pending. Please wait while we process your request. A confirmation email has been sent.',
            'reference' => $reference,
            'amount' => number_format($amount, 2),
            'current_balance' => $currentBalance,
            'next_steps' => 'Your deposit will be reviewed and processed within 1-2 business days. You will be notified once approved.'
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        // Delete uploaded file if transaction failed
        if ($proofPath && file_exists($proofPath)) {
            unlink($proofPath);
        }
        throw new Exception('Database error: ' . $e->getMessage(), 500);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => get_class($e)
    ]);
}