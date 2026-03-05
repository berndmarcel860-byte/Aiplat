<?php
// ajax/kyc_submit.php
require_once __DIR__ . '/../session.php';

// Use statements must be at the very top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 0);
error_reporting(E_ALL);

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
        
        // Send KYC pending email
        try {
            sendKYCPendingEmail($pdo, $user, $documentType, $kycId);
        } catch (Exception $emailError) {
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

/**
 * Send KYC pending email in German
 */
function sendKYCPendingEmail($pdo, $user, $documentType, $kycId) {
    global $phpMailerAvailable;
    
    try {
        // Get SMTP settings
        $smtpStmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE is_active = 1 LIMIT 1");
        $smtpStmt->execute();
        $smtpSettings = $smtpStmt->fetch();
        
        if (!$smtpSettings) {
            throw new Exception("No active SMTP configuration found");
        }

        // Get email template from database
        $templateStmt = $pdo->prepare("SELECT * FROM email_templates WHERE template_key = 'kyc_pending' LIMIT 1");
        $templateStmt->execute();
        $template = $templateStmt->fetch();

        // Get system settings
        $systemStmt = $pdo->prepare("SELECT * FROM system_settings WHERE id = 1");
        $systemStmt->execute();
        $systemSettings = $systemStmt->fetch();

        // Prepare template variables for replacement
        $variables = [
            '{first_name}' => $user['first_name'] ?? '',
            '{last_name}' => $user['last_name'] ?? '',
            '{user_name}' => $user['first_name'] . ' ' . $user['last_name'],
            '{email}' => $user['email'],
            '{document_type}' => $documentType,
            '{kyc_id}' => $kycId,
            '{date}' => date('Y-m-d H:i:s'),
            '{current_year}' => date('Y'),
            '{site_name}' => 'Fundtracer AI',
            '{site_url}' => $systemSettings['site_url'] ?? 'https://your-site.com',
            '{support_email}' => $systemSettings['contact_email'] ?? 'support@your-site.com',
            '{brand_name}' => $systemSettings['brand_name'] ?? 'Fundtracer AI',
            '{contact_phone}' => $systemSettings['contact_phone'] ?? '',
'{fca_reference_number}' => $systemSettings['fca_reference_number'] ?? '',
'{company_address}' => $systemSettings['company_address'] ?? '',
            '{contact_email}' => $systemSettings['contact_email'] ?? ''
        ];

        // Use template from database or fallback to default German template
        if ($template) {
            $subject = $template['subject'] ?? 'KYC-Verifizierung ausstehend - ' . $kycId;
            $htmlBody = $template['content'] ?? getDefaultKYCPendingTemplate();
            
            // Replace variables in template
            foreach ($variables as $key => $value) {
                $subject = str_replace($key, $value, $subject);
                $htmlBody = str_replace($key, $value, $htmlBody);
            }
        } else {
            // Use default German template if no database template found
            $subject = 'KYC-Verifizierung ausstehend - ' . $kycId;
            $htmlBody = getDefaultKYCPendingTemplate();
            
            // Replace variables in default template
            foreach ($variables as $key => $value) {
                $subject = str_replace($key, $value, $subject);
                $htmlBody = str_replace($key, $value, $htmlBody);
            }
        }

        $textBody = strip_tags($htmlBody);

        // Send email using PHPMailer if available
        if ($phpMailerAvailable) {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $smtpSettings['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpSettings['username'];
            $mail->Password   = $smtpSettings['password'];
            $mail->SMTPSecure = $smtpSettings['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtpSettings['port'];

            // Recipients
            $mail->setFrom($smtpSettings['from_email'], $smtpSettings['from_name']);
            $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;

            $mail->send();
        } else {
            // Fallback to PHP mail() function
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: ' . $smtpSettings['from_name'] . ' <' . $smtpSettings['from_email'] . '>' . "\r\n";
            
            if (!mail($user['email'], $subject, $htmlBody, $headers)) {
                throw new Exception("Failed to send email using mail() function");
            }
        }
        
        // Log successful email in database
        try {
            $logStmt = $pdo->prepare("INSERT INTO email_logs (template_id, recipient, subject, content, sent_at, status) VALUES (?, ?, ?, ?, NOW(), 'sent')");
            $logStmt->execute([
                $template['id'] ?? null,
                $user['email'],
                $subject,
                $htmlBody
            ]);
        } catch (Exception $logError) {
            error_log("Failed to log email: " . $logError->getMessage());
        }
        
        error_log("KYC pending email sent to: " . $user['email'] . " for KYC ID: " . $kycId);
        
    } catch (Exception $e) {
        // Log failed email attempt
        try {
            $logStmt = $pdo->prepare("INSERT INTO email_logs (template_id, recipient, subject, content, sent_at, status, error_message) VALUES (?, ?, ?, ?, NOW(), 'failed', ?)");
            $logStmt->execute([
                isset($template) ? $template['id'] ?? null : null,
                $user['email'] ?? 'unknown',
                $subject ?? 'KYC Pending',
                $htmlBody ?? '',
                $e->getMessage()
            ]);
        } catch (Exception $logError) {
            error_log("Failed to log email error: " . $logError->getMessage());
        }
        
        error_log("KYC email sending failed: " . $e->getMessage());
        throw new Exception("Failed to send KYC email: " . $e->getMessage());
    }
}

/**
 * Default German email template for KYC pending
 */
function getDefaultKYCPendingTemplate() {
    return '
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <style>
    @media only screen and (max-width: 600px) {
    .inner-body { width: 100% !important; }
    .footer { width: 100% !important; }
    }
    @media only screen and (max-width: 500px) {
    .button { width: 100% !important; }
    }
    </style>
    </head>
    <body style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; background-color: #ffffff; color: #718096; height: 100%; line-height: 1.4; margin: 0; padding: 0; width: 100% !important;">
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; background-color: #edf2f7; margin: 0; padding: 0; width: 100%;">
    <tr>
    <td align="center" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; position: relative;">
    <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; width: 100%;">
    <tr>
    <td class="header" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; position: relative; padding: 25px 0; text-align: center; background: linear-gradient(90deg,#2950a8 0,#2da9e3 100%); color: white;">
    <h1>KYC-Verifizierung ausstehend</h1>
    </td>
    </tr>
    <tr>
    <td class="body" width="100%" cellpadding="0" cellspacing="0" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; background-color: #edf2f7; border-bottom: 1px solid #edf2f7; border-top: 1px solid #edf2f7; margin: 0; padding: 0; width: 100%;">
    <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; background-color: #ffffff; border-color: #e8e5ef; border-radius: 2px; border-width: 1px; box-shadow: 0 2px 0 rgba(0, 0, 150, 0.025), 2px 4px 0 rgba(0, 0, 150, 0.015); margin: 0 auto; padding: 0; width: 570px;">
    <tr>
    <td class="content-cell" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; position: relative; max-width: 100vw; padding: 32px;">
    <p>Sehr geehrte/r {first_name} {last_name},</p>
    <p>vielen Dank für die Einreichung Ihrer KYC-Dokumente (Know Your Customer) bei Fundtracer AI.</p>
    
    <div style="background: #e8f4f8; border: 1px solid #2950a8; border-radius: 8px; padding: 15px; margin: 15px 0;">
    <h4 style="color: #2950a8; margin-top: 0;">📋 Transaktionsdetails:</h4>
    <table style="width: 100%; border-collapse: collapse;">
    <tr><td style="padding: 5px; font-weight: bold;">KYC-ID:</td><td style="padding: 5px;">{kyc_id}</td></tr>
    <tr><td style="padding: 5px; font-weight: bold;">Dokumenttyp:</td><td style="padding: 5px;">{document_type}</td></tr>
    <tr><td style="padding: 5px; font-weight: bold;">Datum & Uhrzeit:</td><td style="padding: 5px;">{date}</td></tr>
    <tr><td style="padding: 5px; font-weight: bold;">Status:</td><td style="padding: 5px;"><span style="background: #ffc107; color: #000; padding: 2px 8px; border-radius: 10px;">⏳ In Bearbeitung</span></td></tr>
    </table>
    </div>
    
    <div style="background: #d4edda; border: 1px solid #28a745; border-radius: 8px; padding: 15px; margin: 15px 0;">
    <h4 style="color: #155724; margin-top: 0;">✅ Dokumente erfolgreich erhalten:</h4>
    <ul style="margin: 0; padding-left: 20px;">
    <li>Vorderseite des Ausweisdokuments</li>
    <li>Rückseite des Ausweisdokuments (falls zutreffend)</li>
    <li>Selfie mit Ausweisdokument</li>
    <li>Adressnachweis</li>
    </ul>
    </div>
    
    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 10px; margin: 10px 0;">
    <h4 style="color: #856404; margin-top: 0;">🔄 Nächste Schritte:</h4>
    <ul style="margin: 0; padding-left: 20px;">
    <li><strong>Überprüfungsprozess:</strong> Unser Team wird Ihre Dokumente innerhalb von 1-3 Werktagen überprüfen</li>
    <li><strong>Verifizierung:</strong> Wir werden die Echtheit und Lesbarkeit Ihrer Dokumente prüfen</li>
    <li><strong>Kontofreischaltung:</strong> Nach erfolgreicher Verifizierung wird Ihr Konto vollständig freigeschaltet</li>
    <li><strong>Benachrichtigung:</strong> Sie erhalten eine E-Mail, sobald die Überprüfung abgeschlossen ist</li>
    </ul>
    </div>
    
    <p>Sie können den Status Ihrer KYC-Verifizierung jederzeit in Ihrem Dashboard einsehen.</p>
    
    <div style="background: #f8d7da; border: 1px solid #dc3545; border-radius: 5px; padding: 10px; margin: 10px 0;">
    <p style="margin: 0;"><strong>⚠️ Wichtiger Sicherheitshinweis:</strong> Falls Sie diese KYC-Einreichung nicht autorisiert haben, kontaktieren Sie bitte umgehend unser Support-Team unter {support_email} mit der KYC-ID <strong>{kyc_id}</strong>.</p>
    </div>
    
    <p>Benötigen Sie Hilfe? Unser Support-Team ist 24/7 erreichbar, um Ihnen bei Fragen zu Ihrer KYC-Verifizierung oder Ihrem Konto zu helfen.</p>
    
    <p style="margin-bottom: 0;">Mit freundlichen Grüßen,<br><strong>Das Fundtracer AI Team</strong><br>Next-Generation Scam Recovery & Fund Tracing</p>
    </td>
    </tr>
    </table>
    </td>
    </tr>
    <tr>
    <td style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; position: relative;">
    <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; margin: 0 auto; padding: 0; text-align: center; width: 570px;">
    <tr>
    <td class="content-cell" align="center" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; position: relative; max-width: 100vw; padding: 32px;">
    <p style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; line-height: 1.5em; margin-top: 0; color: #b0adc5; font-size: 12px; text-align: center;">© {current_year} {site_name}. Alle Rechte vorbehalten.</p>
    <p style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; line-height: 1.5em; margin-top: 0; color: #b0adc5; font-size: 12px; text-align: center;">🔒 Dies ist eine automatisierte sichere Nachricht. Bitte antworten Sie nicht direkt auf diese E-Mail.</p>
    <p style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; line-height: 1.5em; margin-top: 0; color: #b0adc5; font-size: 12px; text-align: center;">Support: {support_email} | Website: {site_url}</p>
    </td>
    </tr>
    </table>
    </td>
    </tr>
    </table>
    </td>
    </tr>
    </table>
    </body>
    </html>';
}
?>