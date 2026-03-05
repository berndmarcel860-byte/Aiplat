<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode([
        'success' => false,
        'message' => 'Please login to access this resource',
        'redirect' => 'login.php'
    ]));
}

header('Content-Type: application/json');

try {
    // Get KYC request ID
    $kycId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($kycId <= 0) {
        throw new Exception("Invalid KYC request ID");
    }

    // Get KYC details with additional verification
    $stmt = $pdo->prepare("SELECT *, 
                          CASE 
                              WHEN status = 'approved' THEN 'success'
                              WHEN status = 'rejected' THEN 'danger'
                              ELSE 'warning'
                          END as status_class
                          FROM kyc_verification_requests 
                          WHERE id = ? AND user_id = ?");
    $stmt->execute([$kycId, $_SESSION['user_id']]);
    $kyc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$kyc) {
        throw new Exception("KYC request not found or unauthorized access");
    }

    // Verify files exist before returning paths
    $basePath = realpath(__DIR__ . '/../uploads/kyc/') . '/';
    
    $documentFront = null;
    if ($kyc['document_front'] && file_exists($basePath . $kyc['document_front'])) {
        $documentFront = 'uploads/kyc/' . $kyc['document_front'];
    }
    
    $documentBack = null;
    if ($kyc['document_back'] && file_exists($basePath . $kyc['document_back'])) {
        $documentBack = 'uploads/kyc/' . $kyc['document_back'];
    }
    
    $selfieWithId = null;
    if ($kyc['selfie_with_id'] && file_exists($basePath . $kyc['selfie_with_id'])) {
        $selfieWithId = 'uploads/kyc/' . $kyc['selfie_with_id'];
    }
    
    $addressProof = null;
    if ($kyc['address_proof'] && file_exists($basePath . $kyc['address_proof'])) {
        $addressProof = 'uploads/kyc/' . $kyc['address_proof'];
    }

    // Prepare response with verified file paths
    $response = [
        'success' => true,
        'kyc' => [
            'id' => $kyc['id'],
            'document_type' => $kyc['document_type'],
            'status' => $kyc['status'],
            'status_class' => $kyc['status_class'],
            'rejection_reason' => $kyc['rejection_reason'],
            'created_at' => $kyc['created_at'],
            'verified_at' => $kyc['verified_at'],
            'document_front' => $documentFront,
            'document_back' => $documentBack,
            'selfie_with_id' => $selfieWithId,
            'address_proof' => $addressProof
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("get-kyc.php Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}