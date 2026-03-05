<?php
// admin_ajax/get_classified_users.php
// Get users filtered by classification (onboarding, package, cases, kyc status)

require_once '../admin_session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Get current admin role
    $currentAdminRole = $_SESSION['admin_role'] ?? 'admin';
    $currentAdminId = $_SESSION['admin_id'];
    
    // DataTables parameters
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    $classification = isset($_POST['classification']) ? $_POST['classification'] : '';
    
    // Build WHERE clause based on classification
    $where = "u.status != 'suspended'";  // Changed from "1=1" to meaningful default
    $joins = "";
    $whereExtra = "";
    
    // Filter by admin_id for regular admins (superadmin sees all)
    if ($currentAdminRole !== 'superadmin') {
        // Include users with matching admin_id OR NULL admin_id (for backwards compatibility)
        $where .= " AND (u.admin_id = :admin_id OR u.admin_id IS NULL)";
    }
    
    switch ($classification) {
        // Onboarding filters
        case 'onboarding_completed':
            $joins = "INNER JOIN user_onboarding uo ON u.id = uo.user_id AND uo.completed = 1";
            break;
        case 'onboarding_incomplete':
            $joins = "INNER JOIN user_onboarding uo ON u.id = uo.user_id AND (uo.completed = 0 OR uo.completed IS NULL)";
            break;
        case 'has_onboarding':
            $joins = "INNER JOIN user_onboarding uo ON u.id = uo.user_id";
            break;
        case 'no_onboarding':
            $whereExtra = " AND u.id NOT IN (SELECT DISTINCT user_id FROM user_onboarding)";
            break;
            
        // Package filters
        case 'package_active':
            $joins = "INNER JOIN user_packages up ON u.id = up.user_id AND up.status = 'active'";
            break;
        case 'package_expired':
            $joins = "INNER JOIN user_packages up ON u.id = up.user_id AND up.status = 'expired'";
            break;
        case 'has_package':
            $joins = "INNER JOIN user_packages up ON u.id = up.user_id";
            break;
        case 'no_package':
            $whereExtra = " AND u.id NOT IN (SELECT DISTINCT user_id FROM user_packages)";
            break;
            
        // Cases filters
        case 'cases_active':
            $joins = "INNER JOIN cases c ON u.id = c.user_id AND c.status IN ('open', 'documents_required', 'under_review')";
            break;
        case 'has_cases':
            $joins = "INNER JOIN cases c ON u.id = c.user_id";
            break;
        case 'no_cases':
            $whereExtra = " AND u.id NOT IN (SELECT DISTINCT user_id FROM cases)";
            break;
            
        // KYC filters
        case 'kyc_approved':
            $joins = "INNER JOIN kyc_verification_requests k ON u.id = k.user_id AND k.status = 'approved'";
            break;
        case 'kyc_pending':
            $joins = "INNER JOIN kyc_verification_requests k ON u.id = k.user_id AND k.status = 'pending'";
            break;
        case 'has_kyc':
            $joins = "INNER JOIN kyc_verification_requests k ON u.id = k.user_id";
            break;
        case 'no_kyc':
            $whereExtra = " AND u.id NOT IN (SELECT DISTINCT user_id FROM kyc_verification_requests)";
            break;
    }
    
    // Append extra WHERE conditions
    $where .= $whereExtra;
    
    // Add search
    if ($search) {
        $where .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
    }
    
    // Count total records
    $countSql = "SELECT COUNT(DISTINCT u.id) FROM users u $joins WHERE $where";
    $countStmt = $pdo->prepare($countSql);
    if ($currentAdminRole !== 'superadmin') {
        $countStmt->bindValue(':admin_id', $currentAdminId, PDO::PARAM_INT);
    }
    if ($search) {
        $countStmt->bindValue(':search', "%$search%");
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
    
    // Get data with additional info
    $sql = "
        SELECT DISTINCT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.status,
            u.balance,
            u.created_at,
            (SELECT 1 FROM user_onboarding WHERE user_id = u.id LIMIT 1) as has_onboarding,
            (SELECT status FROM user_packages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as package_status,
            (SELECT COUNT(*) FROM cases WHERE user_id = u.id) as cases_count,
            (SELECT status FROM kyc_verification_requests WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as kyc_status
        FROM users u 
        $joins
        WHERE $where
        ORDER BY u.id DESC
        LIMIT :start, :length
    ";
    
    $stmt = $pdo->prepare($sql);
    if ($currentAdminRole !== 'superadmin') {
        $stmt->bindValue(':admin_id', $currentAdminId, PDO::PARAM_INT);
    }
    if ($search) {
        $stmt->bindValue(':search', "%$search%");
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
    
} catch (PDOException $e) {
    error_log("Get classified users error: " . $e->getMessage());
    echo json_encode([
        'draw' => $draw ?? 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}