<?php
// ajax/kyc_submit.php
require_once __DIR__ . '/../session.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

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

    // Get user information
    $userStmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();

    if (!$user) {
        throw new Exception('User not found', 404);
    }

    // Check if there's a pending KYC request
    $stmt = $pdo->prepare("SELECT id FROM kyc_verification_requests WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id']]);
    $pending = $stmt->fetch();
    
    if ($pending) {
        throw new Exception('You already have a pending KYC verification request.', 400);
    }

    // Validate document type
    $documentType = filter_input(INPUT_POST, 'document_type', FILTER_SANITIZE_STRING);
    $allowedTypes = ['passport', 'id_card', 'driving_license', 'other'];
    if (!in_array($documentType, $allowedTypes)) {
        throw new Exception('Invalid document type selected.', 400);
    }

    // Create upload directory with proper path
    $uploadDir = __DIR__ . '/../uploads/kyc/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory', 500);
        }
    }

    // Make sure directory is writable
    if (!is_writable($uploadDir)) {
        chmod($uploadDir, 0755);
    }

    // Enhanced function to safely handle file uploads
    function handleFileUpload($fileInput, $prefix, $required = true) {
        global $uploadDir;
        
        if (!isset($fileInput)) {
            if ($required) {
                throw new Exception("No file uploaded for " . $prefix);
            }
            return null;
        }
        
        // Check for upload errors
        if ($fileInput['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File is too large (exceeds server limit)',
                UPLOAD_ERR_FORM_SIZE => 'File is too large (exceeds form limit)', 
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            
            $errorMsg = $errorMessages[$fileInput['error']] ?? 'Unknown upload error';
            if ($required) {
                throw new Exception("Upload error for " . $prefix . ": " . $errorMsg);
            }
            return null;
        }
        
        // Enhanced file validation
        $maxSize = 10 * 1024 * 1024; // 10MB
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg', 
            'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf'
        ];
        
        // Validate file size
        if ($fileInput['size'] > $maxSize) {
            throw new Exception("File size exceeds 10MB limit for " . $prefix);
        }

        if ($fileInput['size'] < 1024) {
            throw new Exception("File is too small to be valid for " . $prefix);
        }
        
        // Check MIME type
        if (function_exists('finfo_file')) {
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $fileInfo->file($fileInput['tmp_name']);
        } else {
            // Fallback to file extension
            $pathInfo = pathinfo($fileInput['name']);
            $ext = strtolower($pathInfo['extension'] ?? '');
            switch($ext) {
                case 'jpg':
                case 'jpeg':
                    $mime = 'image/jpeg';
                    break;
                case 'png':
                    $mime = 'image/png';
                    break;
                case 'pdf':
                    $mime = 'application/pdf';
                    break;
                default:
                    throw new Exception("Unsupported file type for " . $prefix);
            }
        }
        
        if (!array_key_exists($mime, $allowedMimes)) {
            throw new Exception("Invalid file type for " . $prefix . ". Only JPG, PNG, and PDF are allowed. Detected: " . $mime);
        }
        
        // Additional security checks for file extension
        $originalName = $fileInput['name'];
        $pathInfo = pathinfo($originalName);
        $fileExtension = strtolower($pathInfo['extension'] ?? '');
        
        if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'pdf'])) {
            throw new Exception("Invalid file extension for " . $prefix . ". Only JPG, PNG, and PDF are allowed.");
        }
        
        // Generate secure filename with timestamp
        $ext = $allowedMimes[$mime];
        $timestamp = time();
        $randomString = bin2hex(random_bytes(8));
        $filename = $prefix . '_' . $_SESSION['user_id'] . '_' . $timestamp . '_' . $randomString . '.' . $ext;
        $destination = $uploadDir . $filename;
        
        // Final security check - scan for potential threats
        if ($mime !== 'application/pdf') {
            // For images, do additional validation
            $imageInfo = @getimagesize($fileInput['tmp_name']);
            if ($imageInfo === false) {
                throw new Exception("Invalid or corrupted image file for " . $prefix);
            }
        }
        
        // Move uploaded file
        if (!move_uploaded_file($fileInput['tmp_name'], $destination)) {
            throw new Exception("Failed to save uploaded file for " . $prefix . ". Check directory permissions.");
        }
        
        // Set proper file permissions
        chmod($destination, 0644);
        
        // Verify file was actually saved
        if (!file_exists($destination)) {
            throw new Exception("File was not saved properly for " . $prefix);
        }
        
        // Return relative path for database storage
        return 'uploads/kyc/' . $filename;
    }
    
    // Process all file uploads
    $frontPath = handleFileUpload($_FILES['document_front'] ?? null, 'front', true);
    $backPath = ($documentType !== 'passport') ? handleFileUpload($_FILES['document_back'] ?? null, 'back', true) : null;
    $selfiePath = handleFileUpload($_FILES['selfie_with_id'] ?? null, 'selfie', true);
    $addressProofPath = handleFileUpload($_FILES['address_proof'] ?? null, 'address', true);
    
    // Begin database transaction
    $pdo->beginTransaction();
    
    try {
        // Create KYC request
        $stmt = $pdo->prepare("INSERT INTO kyc_verification_requests 
                              (user_id, document_type, document_front, document_back, 
                               selfie_with_id, address_proof, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $_SESSION['user_id'],
            $documentType,
            $frontPath,
            $backPath,
            $selfiePath,
            $addressProofPath
        ]);
        
        $kycId = $pdo->lastInsertId();
        
        // Send KYC pending email via EmailHelper
        try {
            require_once __DIR__ . '/../EmailHelper.php';
            $emailHelper = new EmailHelper($pdo);
            $emailHelper->sendKycPendingEmail($_SESSION['user_id'], [
                'document_type' => $documentType,
                'kyc_id'        => (string)$kycId,
            ]);
        } catch (Throwable $emailError) {
            error_log("KYC email sending failed: " . $emailError->getMessage());
            // Continue processing even if email fails
        }

        // Commit transaction
        $pdo->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'KYC documents submitted successfully! Verification may take 1-3 business days.',
            'kyc_id' => $kycId,
            'next_steps' => 'Your KYC verification is now pending review'
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        // Delete uploaded files if transaction failed
        if ($frontPath && file_exists(__DIR__ . '/../' . $frontPath)) {
            @unlink(__DIR__ . '/../' . $frontPath);
        }
        if ($backPath && file_exists(__DIR__ . '/../' . $backPath)) {
            @unlink(__DIR__ . '/../' . $backPath);
        }
        if ($selfiePath && file_exists(__DIR__ . '/../' . $selfiePath)) {
            @unlink(__DIR__ . '/../' . $selfiePath);
        }
        if ($addressProofPath && file_exists(__DIR__ . '/../' . $addressProofPath)) {
            @unlink(__DIR__ . '/../' . $addressProofPath);
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

