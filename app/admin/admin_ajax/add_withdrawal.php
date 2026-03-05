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
    
    $userId = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $methodCode = $_POST['method_code'];
    $paymentDetails = $_POST['payment_details'] ?? '';
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
                'message' => 'User not found or you do not have permission to add withdrawal for this user'
            ]);
            exit;
        }
    }
    
    // Generate unique reference
    $reference = 'WD' . time() . rand(1000, 9999);
    
    // Insert withdrawal
    $stmt = $pdo->prepare("
        INSERT INTO withdrawals (user_id, amount, method_code, payment_details, reference, status, admin_notes, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())
    ");
    
    $stmt->execute([$userId, $amount, $methodCode, $paymentDetails, $reference, $adminNotes]);
    
    // Log admin action with IP address
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $logStmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent, created_at)
        VALUES (?, 'add_withdrawal', ?, ?, ?, NOW())
    ");
    $logDetails = json_encode([
        'user_id' => $userId,
        'amount' => $amount,
        'reference' => $reference,
        'method_code' => $methodCode
    ]);
    $logStmt->execute([$currentAdminId, $logDetails, $ipAddress, $userAgent]);
    
    // Send email notification to user
    try {
        require_once '../mail_functions.php';
        
        $userStmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $emailContent = "
                <h2>Withdrawal Request Created</h2>
                <p>Dear {$user['first_name']} {$user['last_name']},</p>
                <p>A withdrawal request has been created for your account:</p>
                <ul>
                    <li><strong>Amount:</strong> €" . number_format($amount, 2) . "</li>
                    <li><strong>Reference:</strong> {$reference}</li>
                    <li><strong>Status:</strong> Pending</li>
                </ul>
                <p>You will receive another notification when your withdrawal is processed.</p>
            ";
            
            sendEmail($user['email'], 'Withdrawal Request Created', $emailContent);
        }
    } catch (Exception $e) {
        // Log email error but don't fail the withdrawal creation
        error_log('Email notification failed: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Withdrawal created successfully',
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