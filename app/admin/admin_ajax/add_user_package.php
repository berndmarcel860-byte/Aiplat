<?php
// admin_ajax/add_user_package.php
// Add new user package assignment with email notification

require_once '../admin_session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate required fields - end_date is optional (will be calculated from package duration)
$required = ['user_id', 'package_id', 'start_date', 'status'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$userId = (int)$_POST['user_id'];
$packageId = (int)$_POST['package_id'];
$startDate = $_POST['start_date'];
$endDate = isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : null;
$status = $_POST['status'];

// Validate status
$validStatuses = ['pending', 'active', 'expired', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Verify user exists and get details for email
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Verify package exists and get details
    $stmt = $pdo->prepare("SELECT id, name, duration_days, price FROM packages WHERE id = ?");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$package) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit();
    }
    
    // Calculate end_date if not provided
    if (empty($endDate) && $package['duration_days'] > 0) {
        try {
            $startDateTime = new DateTime($startDate);
            $startDateTime->modify('+' . (int)$package['duration_days'] . ' days');
            $endDate = $startDateTime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Invalid start date format']);
            exit();
        }
    }
    
    // Insert new assignment
    $stmt = $pdo->prepare("
        INSERT INTO user_packages (user_id, package_id, start_date, end_date, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$userId, $packageId, $startDate, $endDate, $status]);
    $newId = $pdo->lastInsertId();
    
    // Log admin action
    $logStmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, ip_address, user_agent, created_at)
        VALUES (?, 'create', 'user_package', ?, ?, ?, NOW())
    ");
    $logStmt->execute([
        $_SESSION['admin_id'],
        $newId,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    // Send email notification using package_assigned template
    $emailSent = false;
    try {
        // Get system settings
        $settingsStmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
        $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        
        // Get email template - try package_assigned first, then use universal template
        $templateStmt = $pdo->prepare("SELECT * FROM email_templates WHERE template_key = ? LIMIT 1");
        $templateStmt->execute(['package_assigned']);
        $template = $templateStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($template) {
            // Load PHPMailer
            $vendorPaths = [
                $_SERVER['DOCUMENT_ROOT'] . '/app/vendor/autoload.php',
                __DIR__ . '/../../vendor/autoload.php',
                __DIR__ . '/../vendor/autoload.php'
            ];
            foreach ($vendorPaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    break;
                }
            }
            
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $smtpStmt = $pdo->query("SELECT * FROM smtp_settings LIMIT 1");
                $smtp = $smtpStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($smtp) {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $smtp['host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtp['username'];
                    $mail->Password = $smtp['password'];
                    $mail->SMTPSecure = $smtp['encryption'] ?? 'tls';
                    $mail->Port = $smtp['port'] ?? 587;
                    $mail->CharSet = 'UTF-8';
                    
                    $mail->setFrom($smtp['from_email'] ?? $smtp['username'], $smtp['from_name'] ?? ($settings['brand_name'] ?? 'System'));
                    $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
                    $mail->isHTML(true);
                    
                    // Replace variables in template
                    $variables = [
                        '{first_name}' => htmlspecialchars($user['first_name']),
                        '{last_name}' => htmlspecialchars($user['last_name']),
                        '{email}' => htmlspecialchars($user['email']),
                        '{balance}' => number_format($user['balance'], 2),
                        '{package_name}' => htmlspecialchars($package['name']),
                        '{package_price}' => number_format($package['price'], 2),
                        '{start_date}' => date('d.m.Y', strtotime($startDate)),
                        '{end_date}' => $endDate ? date('d.m.Y', strtotime($endDate)) : 'N/A',
                        '{site_url}' => $settings['site_url'] ?? 'https://kryptox.co.uk',
                        '{site_name}' => $settings['brand_name'] ?? 'KryptoX',
                        '{contact_email}' => $settings['contact_email'] ?? 'info@kryptox.co.uk'
                    ];
                    
                    $subject = str_replace(array_keys($variables), array_values($variables), $template['subject']);
                    $content = str_replace(array_keys($variables), array_values($variables), $template['content']);
                    
                    $mail->Subject = $subject;
                    $mail->Body = $content;
                    $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $content));
                    
                    $mail->send();
                    $emailSent = true;
                    
                    // Log email
                    $emailLogStmt = $pdo->prepare("INSERT INTO email_logs (template_id, recipient, subject, content, sent_at, status) VALUES (?, ?, ?, ?, NOW(), 'sent')");
                    $emailLogStmt->execute([$template['id'], $user['email'], $subject, $content]);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Package assignment email error: " . $e->getMessage());
    }
    
    // Create user notification
    try {
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'package', ?, ?, NOW())");
        $notifStmt->execute([
            $userId, 
            'Package Assigned', 
            'Your package "' . $package['name'] . '" has been activated. Valid until: ' . ($endDate ? date('d.m.Y', strtotime($endDate)) : 'N/A')
        ]);
    } catch (Exception $e) {
        // Notification table might not exist
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Package assignment created successfully' . ($emailSent ? ' and email notification sent' : ''),
        'id' => $newId,
        'email_sent' => $emailSent
    ]);
    
} catch (PDOException $e) {
    error_log("Add user package error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}