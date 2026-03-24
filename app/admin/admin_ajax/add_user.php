<?php
require_once '../../config.php'; // Contains SITE_URL and other configurations
require_once '../admin_session.php';
require_once '../../EmailHelper.php';
header('Content-Type: application/json');

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Admin not logged in',
        'error' => 'Session admin_id not set'
    ]);
    exit();
}

// Validate input
$required = ['first_name', 'last_name', 'email', 'password', 'status'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// Store plain text password before hashing
$plain_password = $_POST['password'];

// Prepare user data
$data = [
    'first_name' => trim($_POST['first_name']),
    'last_name' => trim($_POST['last_name']),
    'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
    'password_hash' => password_hash($plain_password, PASSWORD_DEFAULT), // Store hashed version
    'status' => in_array($_POST['status'], ['active', 'suspended', 'banned']) ? $_POST['status'] : 'active',
    'phone' => isset($_POST['phone']) ? preg_replace('/[^0-9+]/', '', $_POST['phone']) : null,
    'country' => isset($_POST['country']) ? substr(trim($_POST['country']), 0, 100) : null,
    'uuid' => bin2hex(random_bytes(16)),
    'force_password_change' => 1 // Require password change on first login
];

try {
    // Check if email exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$data['email']]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }

    // Insert new user with admin_id tracking
    $stmt = $pdo->prepare("
        INSERT INTO users 
        (uuid, first_name, last_name, email, password_hash, status, phone, country, force_password_change, admin_id, created_at, updated_at)
        VALUES (:uuid, :first_name, :last_name, :email, :password_hash, :status, :phone, :country, :force_password_change, :admin_id, NOW(), NOW())
    ");
    $data['admin_id'] = $_SESSION['admin_id'];
    $stmt->execute($data);
    $userId = $pdo->lastInsertId();
    
    // Log admin action
    $logStmt = $pdo->prepare("
        INSERT INTO admin_logs 
        (admin_id, action, entity_type, entity_id, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $logStmt->execute([
        $_SESSION['admin_id'],
        'create',
        'user',
        $userId,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    // Send welcome email with plain text password
    $emailSent = false;
    try {
        $emailHelper = new EmailHelper($pdo);
        // Always fetch site_url from system_settings; never fall back to a hardcoded domain
        $siteUrlRow = $pdo->query("SELECT site_url FROM system_settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $siteUrl = rtrim($siteUrlRow['site_url'] ?? '', '/') . '/';

        $customVars = [
            'temp_password' => $plain_password, // Plain text password for email
            'pass' => $plain_password, // Alias for backwards compatibility
            'admin_name' => $_SESSION['admin_name'] ?? 'Administrator',
            'login_link' => $siteUrl . 'login.php',
            'change_password_link' => $siteUrl . 'change-password.php'
        ];
        
        $emailSent = $emailHelper->sendEmail('welcome_email', $userId, $customVars);
    } catch (Exception $e) {
        error_log("Welcome email failed: " . $e->getMessage());
        $emailSent = false;
    }

    // Prepare response
    $response = [
        'success' => true,
        'message' => 'User created successfully',
        'user' => [
            'id' => $userId,
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'email' => $data['email'],
            'status' => $data['status']
        ],
        'email_sent' => $emailSent
    ];

    if (!$emailSent) {
        $response['email_warning'] = 'Welcome email failed to send';
        error_log("Failed to send welcome email to: " . $data['email']);
    }

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}