<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Get current admin role and ID
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    // Validate required fields
    if (empty($_POST['user_id']) || empty($_POST['amount']) || empty($_POST['method_code'])) {
        echo json_encode([
            'success' => false,
            'message' => 'User, amount, and payment method are required'
        ]);
        exit;
    }
    
    // Validate proof file
    if (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'message' => 'Proof of payment file is required'
        ]);
        exit;
    }
    
    $userId = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $methodCode = $_POST['method_code'];
    $transactionId = $_POST['transaction_id'] ?? '';
    $status = $_POST['status'] ?? 'completed';
    $adminNotes = $_POST['admin_notes'] ?? '';
    
    // Validate amount
    if ($amount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Amount must be greater than 0'
        ]);
        exit;
    }
    
    // Verify user belongs to this admin (unless superadmin)
    if ($currentAdminRole !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND (admin_id = ? OR admin_id IS NULL)");
        $stmt->execute([$userId, $currentAdminId]);
        
        if (!$stmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'User not found or you do not have permission to add deposit for this user'
            ]);
            exit;
        }
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = '../../uploads/deposits';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    // Handle file upload
    $file = $_FILES['proof_file'];
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    
    // Validate file size
    if ($file['size'] > $maxFileSize) {
        echo json_encode([
            'success' => false,
            'message' => 'File size exceeds 10MB limit'
        ]);
        exit;
    }
    
    // Validate file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Only JPG, PNG, and PDF files are allowed.'
        ]);
        exit;
    }
    
    // Validate MIME type using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimeTypes)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file content. File does not match its extension.'
        ]);
        exit;
    }
    
    // Additional validation for images
    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid image file.'
            ]);
            exit;
        }
    }
    
    // Generate secure filename
    $filename = 'deposit_proof_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $uploadsDir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to upload proof file'
        ]);
        exit;
    }
    
    $proofPath = 'uploads/deposits/' . $filename;
    
    // Generate unique reference
    $reference = 'DEP' . time() . rand(1000, 9999);
    
    // Insert deposit
    $stmt = $pdo->prepare("
        INSERT INTO deposits (user_id, amount, method_code, reference, proof_path, status, admin_notes, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$userId, $amount, $methodCode, $reference, $proofPath, $status, $adminNotes]);
    
    // If status is completed, update user balance
    if ($status === 'completed') {
        $updateStmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $updateStmt->execute([$amount, $userId]);
    }
    
    // Log admin action
    $logStmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, details, created_at)
        VALUES (?, 'add_deposit', ?, NOW())
    ");
    $logDetails = json_encode([
        'user_id' => $userId,
        'amount' => $amount,
        'reference' => $reference,
        'method_code' => $methodCode,
        'status' => $status,
        'proof_path' => $proofPath
    ]);
    $logStmt->execute([$currentAdminId, $logDetails]);
    
    // Send email notification to user using AdminEmailHelper with templates
    try {
        require_once __DIR__ . '/../AdminEmailHelper.php';
        $emailHelper = new AdminEmailHelper($pdo);
        
        // Prepare custom variables specific to this deposit
        $customVars = [
            'deposit_amount' => number_format($amount, 2),
            'deposit_reference' => $reference,
            'deposit_status' => ucfirst($status),
            'payment_method' => $methodCode,
            'date' => date('d.m.Y H:i')
        ];
        
        // Determine which template to use based on deposit status
        $templateKey = ($status === 'completed') ? 'deposit_completed_de' : 'deposit_pending_de';
        
        // Send email using template from email_templates table
        $success = $emailHelper->sendTemplateEmail($templateKey, $userId, $customVars);
        
        if ($success) {
            error_log("Deposit email sent successfully to user ID: {$userId} for reference: {$reference} using template: {$templateKey}");
        } else {
            error_log("Failed to send deposit email to user ID: {$userId} for reference: {$reference} using template: {$templateKey}");
        }
    } catch (Exception $e) {
        // Log email error but don't fail the deposit creation
        error_log('Email notification failed: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Deposit created successfully',
        'reference' => $reference
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}