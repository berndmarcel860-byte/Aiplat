<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get form data
    $brand_name = $_POST['brand_name'] ?? '';
    $site_url = $_POST['site_url'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    $company_address = $_POST['company_address'] ?? '';
    $fca_reference_number = $_POST['fca_reference_number'] ?? '';
    $logo_url = $_POST['logo_url'] ?? '';
    
    // Basic validation
    if (empty($brand_name)) {
        throw new Exception('Brand name is required');
    }
    
    if (empty($site_url)) {
        throw new Exception('Site URL is required');
    }
    
    if (!empty($contact_email) && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    $pdo->beginTransaction();
    
    // Check if settings exist
    $stmt = $pdo->prepare("SELECT id FROM system_settings LIMIT 1");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Update existing settings
        $stmt = $pdo->prepare("
            UPDATE system_settings 
            SET brand_name = ?,
                site_url = ?,
                contact_email = ?,
                contact_phone = ?,
                company_address = ?,
                fca_reference_number = ?,
                logo_url = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $brand_name,
            $site_url,
            $contact_email,
            $contact_phone,
            $company_address,
            $fca_reference_number,
            $logo_url,
            $exists['id']
        ]);
    } else {
        // Insert new settings
        $stmt = $pdo->prepare("
            INSERT INTO system_settings 
            (brand_name, site_url, contact_email, contact_phone, company_address, fca_reference_number, logo_url, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $brand_name,
            $site_url,
            $contact_email,
            $contact_phone,
            $company_address,
            $fca_reference_number,
            $logo_url
        ]);
    }
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at) 
        VALUES (?, 'UPDATE', 'system_settings', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['admin_id'], 
        0, 
        "Updated system settings: brand_name, site_url, contact details, company_address, fca_reference_number",
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'System settings saved successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Database error in update_system_settings.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
