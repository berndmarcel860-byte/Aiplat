<?php
// Error reporting - consider setting to 0 in production
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../admin_session.php';
require_once __DIR__ . '/../AdminEmailHelper.php';
header('Content-Type: application/json');

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid KYC request ID']);
    exit();
}

$kycId = (int)$_POST['id'];

try {
    $pdo->beginTransaction();
    
    // Get KYC request details
    $stmt = $pdo->prepare("SELECT * FROM kyc_verification_requests WHERE id = ?");
    $stmt->execute([$kycId]);
    $kyc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kyc) {
        throw new Exception('KYC request not found');
    }
    
    if ($kyc['status'] !== 'pending') {
        throw new Exception('KYC request is not pending');
    }
    
    // Get user details for email
    $userStmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
    $userStmt->execute([$kyc['user_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Update KYC status
    $stmt = $pdo->prepare("
        UPDATE kyc_verification_requests 
        SET 
            status = 'approved',
            verified_by = ?,
            verified_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['admin_id'], $kycId]);
    
    // Update user verification status
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->execute([$kyc['user_id']]);
    
    // Create in-app notification for user
    try {
        $notifStmt = $pdo->prepare("
            INSERT INTO user_notifications (user_id, type, title, message, is_read, created_at)
            VALUES (?, 'success', 'KYC Documents Approved', 'Your KYC verification has been approved! Your account is now verified.', 0, NOW())
        ");
        $notifStmt->execute([$kyc['user_id']]);
    } catch (Exception $e) {
        error_log('In-app notification failed: ' . $e->getMessage());
    }
    
    // Send approval email
    sendKYCEmail($pdo, $user, 'kyc_approved', $kycId, null);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'KYC approved successfully'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to approve KYC',
        'error' => $e->getMessage()
    ]);
}

/**
 * Send KYC status email using AdminEmailHelper
 */
function sendKYCEmail($pdo, $user, $templateKey, $kycId, $rejectionReason = null) {
    try {
        $emailHelper = new AdminEmailHelper($pdo);

        // Prepare custom variables
        $customVars = [
            'kyc_id' => $kycId,
            'date'   => date('Y-m-d H:i:s')
        ];

        // Add rejection reason if provided
        if ($rejectionReason) {
            $customVars['rejection_reason'] = $rejectionReason;
            $stmt = $pdo->query("SELECT site_url FROM system_settings WHERE id = 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            $customVars['resubmit_link'] = ($settings['site_url'] ?? 'https://cryptofinanze.de') . '/kyc.php';
        }

        // Send template email (uses email_templates table)
        $success = $emailHelper->sendTemplateEmail($templateKey, $user['id'], $customVars);

        if (!$success) {
            throw new Exception("Failed to send KYC email via AdminEmailHelper");
        }

        error_log("KYC email sent to: " . $user['email'] . " for KYC ID: " . $kycId);

    } catch (Exception $e) {
        error_log("KYC email sending failed: " . $e->getMessage());
        throw new Exception("Failed to send KYC email: " . $e->getMessage());
    }
}
?>