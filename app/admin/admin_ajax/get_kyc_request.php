<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid KYC request ID']);
    exit();
}

$kycId = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            k.*, 
            u.first_name AS user_first_name, 
            u.last_name AS user_last_name,
            a.first_name AS admin_first_name,
            a.last_name AS admin_last_name
        FROM kyc_verification_requests k
        LEFT JOIN users u ON k.user_id = u.id
        LEFT JOIN admins a ON k.verified_by = a.id
        WHERE k.id = ?
    ");
    $stmt->execute([$kycId]);
    $kyc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kyc) {
        echo json_encode(['success' => false, 'message' => 'KYC request not found']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'kyc' => $kyc
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get KYC request details',
        'error' => $e->getMessage()
    ]);
}
?>