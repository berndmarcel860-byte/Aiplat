<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    // Get current admin role and ID
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    // Validate required fields
    if (empty($_POST['user_id']) || empty($_POST['document_type'])) {
        echo json_encode([
            'success' => false,
            'message' => 'User and document type are required'
        ]);
        exit;
    }
    
    $userId = intval($_POST['user_id']);
    $documentType = $_POST['document_type'];
    $status = $_POST['status'] ?? 'pending';
    $adminNotes = $_POST['admin_notes'] ?? '';
    
    // Verify user belongs to this admin (unless superadmin)
    if ($currentAdminRole !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND (admin_id = ? OR admin_id IS NULL)");
        $stmt->execute([$userId, $currentAdminId]);
        
        if (!$stmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'User not found or you do not have permission to add KYC for this user'
            ]);
            exit;
        }
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = '../../uploads/kyc';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0700, true); // Restricted permissions for sensitive KYC documents
    }
    
    // Handle file uploads
    $documentFrontPath = null;
    $documentBackPath = null;
    $selfieWithIdPath = null;
    $addressProofPath = null;
    
    // Allowed file types - no file size limit
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    
    // Function to handle file upload with security validation
    function handleFileUpload($fileKey, $uploadsDir, $userId, $allowedMimeTypes, $allowedExtensions) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $file = $_FILES[$fileKey];
        
        // File size validation removed - allow any size up to server limit
        
        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception("Invalid file type for {$fileKey}. Only JPG, PNG, and PDF files are allowed.");
        }
        
        // Validate MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new Exception("Invalid file content for {$fileKey}. File does not match its extension.");
        }
        
        // Additional validation for images
        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                throw new Exception("Invalid image file for {$fileKey}.");
            }
        }
        
        // Generate secure filename
        $filename = $fileKey . '_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $uploadsDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'uploads/kyc/' . $filename;
        }
        
        throw new Exception("Failed to upload file {$fileKey}.");
    }
    
    $documentFrontPath = handleFileUpload('document_front', $uploadsDir, $userId, $allowedMimeTypes, $allowedExtensions);
    $documentBackPath = handleFileUpload('document_back', $uploadsDir, $userId, $allowedMimeTypes, $allowedExtensions);
    $selfieWithIdPath = handleFileUpload('selfie_with_id', $uploadsDir, $userId, $allowedMimeTypes, $allowedExtensions);
    $addressProofPath = handleFileUpload('address_proof', $uploadsDir, $userId, $allowedMimeTypes, $allowedExtensions);
    
    // At least document front is required
    if (!$documentFrontPath) {
        echo json_encode([
            'success' => false,
            'message' => 'Document front is required'
        ]);
        exit;
    }
    
    // Insert KYC request
    $stmt = $pdo->prepare("
        INSERT INTO kyc_verification_requests 
        (user_id, document_type, document_front, document_back, selfie_with_id, address_proof, status, admin_notes, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $userId,
        $documentType,
        $documentFrontPath,
        $documentBackPath,
        $selfieWithIdPath,
        $addressProofPath,
        $status,
        $adminNotes
    ]);
    
    $kycId = $pdo->lastInsertId();
    
    // Update user's KYC status if approved
    if ($status === 'approved') {
        $updateStmt = $pdo->prepare("UPDATE users SET kyc_verified = 1 WHERE id = ?");
        $updateStmt->execute([$userId]);
    }
    
    // Log admin action
    $logStmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, details, created_at)
        VALUES (?, 'add_kyc', ?, NOW())
    ");
    $logDetails = json_encode([
        'user_id' => $userId,
        'kyc_id' => $kycId,
        'document_type' => $documentType,
        'status' => $status
    ]);
    $logStmt->execute([$currentAdminId, $logDetails]);
    
    // Create in-app notification for user
    try {
        $statusText = ucfirst($status);
        $documentTypeText = ucfirst(str_replace('_', ' ', $documentType));
        
        $notificationTitle = "KYC Documents {$statusText}";
        $notificationMessage = "KYC documents have been submitted for your account. Document type: {$documentTypeText}. Status: {$statusText}.";
        
        if ($status === 'approved') {
            $notificationMessage .= " Your account has been verified!";
            $notificationType = 'success';
        } elseif ($status === 'rejected') {
            $notificationMessage .= " Please check the rejection reason and resubmit if necessary.";
            $notificationType = 'warning';
        } else {
            $notificationMessage .= " Your documents are being reviewed.";
            $notificationType = 'info';
        }
        
        $notifStmt = $pdo->prepare("
            INSERT INTO user_notifications (user_id, type, title, message, is_read, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $notifStmt->execute([$userId, $notificationType, $notificationTitle, $notificationMessage]);
    } catch (Exception $e) {
        // Log notification error but don't fail the KYC creation
        error_log('In-app notification failed: ' . $e->getMessage());
    }
    
    // Send email notification to user using EmailHelper
    try {
        require_once '../../EmailHelper.php';
        $emailHelper = new EmailHelper($pdo);
        
        $userStmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $statusText = ucfirst($status);
            $documentTypeText = ucfirst(str_replace('_', ' ', $documentType));
            
            // Prepare custom variables for email
            $customVars = [
                'kyc_id' => $kycId,
                'document_type' => $documentTypeText,
                'kyc_status' => $statusText,
                'status_text' => $statusText,
                'date' => date('d.m.Y H:i')
            ];
            
            // Build email subject and body with variable placeholders
            $subject = "KYC Verification {$statusText}";
            
            $emailContent = "
                <h2>KYC Verification {$statusText}</h2>
                <p>Dear {first_name} {last_name},</p>
                <p>KYC documents have been submitted for your account:</p>
                <ul>
                    <li><strong>Document Type:</strong> {document_type}</li>
                    <li><strong>Status:</strong> {status_text}</li>
                    <li><strong>Date:</strong> {date}</li>
                </ul>
            ";
            
            if ($status === 'approved') {
                $emailContent .= "<p><strong>✓ Your account has been verified!</strong></p>";
                $emailContent .= "<p>You can now access all features of your account.</p>";
            } elseif ($status === 'pending') {
                $emailContent .= "<p>Your documents are being reviewed. You will be notified once the verification is complete.</p>";
                $emailContent .= "<p>This usually takes 1-2 business days.</p>";
            } elseif ($status === 'rejected') {
                $emailContent .= "<p>Unfortunately, your documents were not approved. Please check the rejection reason and resubmit if necessary.</p>";
            }
            
            $emailContent .= "<p>If you have any questions, please contact our support team at {contact_email}.</p>";
            $emailContent .= "<p><a href=\"{dashboard_url}\" style=\"display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px;\">Go to Dashboard</a></p>";
            
            // Send email using EmailHelper with all variables automatically available
            $success = $emailHelper->sendDirectEmail($user['id'], $subject, $emailContent, $customVars);
            
            if ($success) {
                error_log("KYC email sent successfully to: " . $user['email'] . " for KYC ID: " . $kycId);
            } else {
                error_log("Failed to send KYC email to: " . $user['email'] . " for KYC ID: " . $kycId);
            }
        }
    } catch (Exception $e) {
        // Log email error but don't fail the KYC creation
        error_log('Email notification failed: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'KYC request created successfully',
        'kyc_id' => $kycId
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