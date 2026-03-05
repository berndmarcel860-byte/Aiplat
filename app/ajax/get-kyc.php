<?php
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

    // Get KYC details
    $stmt = $pdo->prepare("SELECT * FROM kyc_verification_requests 
                          WHERE id = ? AND user_id = ?");
    $stmt->execute([$kycId, $_SESSION['user_id']]);
    $kyc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$kyc) {
        throw new Exception("KYC request not found or unauthorized access");
    }

    // Prepare response
    $response = [
        'success' => true,
        'kyc' => [
            'id' => $kyc['id'],
            'document_type' => $kyc['document_type'],
            'status' => $kyc['status'],
            'rejection_reason' => $kyc['rejection_reason'],
            'created_at' => $kyc['created_at'],
            'verified_at' => $kyc['verified_at'],
            'document_front' => $kyc['document_front'] ?   $kyc['document_front'] : null,
            'document_back' => $kyc['document_back'] ? 'app/' . $kyc['document_back'] : null,
            'selfie_with_id' => $kyc['selfie_with_id'] ? '/../app/' . $kyc['selfie_with_id'] : null,
            'address_proof' => $kyc['address_proof'] ? '/../app/' . $kyc['address_proof'] : null
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}