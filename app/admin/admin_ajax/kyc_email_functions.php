<?php
/**
 * File: admin_ajax/kyc_email_functions.php
 * KYC Email Helper Functions - Simplified wrapper around AdminEmailHelper
 */

require_once __DIR__ . '/../AdminEmailHelper.php';

/**
 * Send KYC-related email using AdminEmailHelper
 * 
 * @param PDO $pdo Database connection
 * @param array $user User data (must have 'id' key)
 * @param string $templateKey Template key (e.g., 'kyc_approved', 'kyc_rejected')
 * @param int $kycId KYC record ID
 * @param string|null $rejectionReason Rejection reason if applicable
 * @return bool Success status
 */
function sendKYCEmail($pdo, $user, $templateKey, $kycId, $rejectionReason = null) {
    try {
        $emailHelper = new AdminEmailHelper($pdo);
        
        $customVars = [
            'kyc_id' => $kycId,
            'verification_date' => date('Y-m-d H:i:s'),
            'date' => date('Y-m-d H:i:s')
        ];
        
        // Add rejection reason if provided
        if ($rejectionReason !== null) {
            $customVars['rejection_reason'] = $rejectionReason;
            $customVars['reason'] = $rejectionReason;
        }
        
        return $emailHelper->sendTemplateEmail($templateKey, $user['id'], $customVars);
    } catch (Exception $e) {
        error_log("KYC email failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send KYC approval email
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $kycId KYC record ID
 * @return bool Success status
 */
function sendKYCApprovalEmail($pdo, $userId, $kycId) {
    try {
        $userStmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("User not found: " . $userId);
            return false;
        }
        
        return sendKYCEmail($pdo, $user, 'kyc_approved', $kycId);
    } catch (Exception $e) {
        error_log("KYC approval email failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send KYC rejection email
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $kycId KYC record ID
 * @param string $reason Rejection reason
 * @return bool Success status
 */
function sendKYCRejectionEmail($pdo, $userId, $kycId, $reason) {
    try {
        $userStmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("User not found: " . $userId);
            return false;
        }
        
        return sendKYCEmail($pdo, $user, 'kyc_rejected', $kycId, $reason);
    } catch (Exception $e) {
        error_log("KYC rejection email failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send KYC reminder email
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return bool Success status
 */
function sendKYCReminderEmail($pdo, $userId) {
    try {
        $emailHelper = new AdminEmailHelper($pdo);
        return $emailHelper->sendTemplateEmail('kyc_reminder', $userId);
    } catch (Exception $e) {
        error_log("KYC reminder email failed: " . $e->getMessage());
        return false;
    }
}