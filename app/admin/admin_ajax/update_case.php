<?php
// Error reporting - consider setting to 0 in production
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../admin_session.php';
require_once '../AdminEmailHelper.php';

header('Content-Type: application/json');

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Admin not logged in',
        'error' => 'Session admin_id not set'
    ]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['case_id'])) {
    echo json_encode(['success' => false, 'message' => 'Case ID required']);
    exit();
}

// Define complete status translations including all possible statuses
$statusTranslations = [
    'open' => 'Offen',
    'document_required' => 'Nachweisdokumente erforderlich',
    'documents_required' => 'Nachweisdokumente erforderlich', // Alternative spelling
    'under_review' => 'Fall wird überprüft',
    'in_progress' => 'In Bearbeitung',
    'completed' => 'Abgeschlossen',
    'rejected' => 'Abgelehnt',
    'pending' => 'Ausstehend', // Add any other statuses you might use
];

try {
    $pdo->beginTransaction();
    
    // Get current case details including user info
    $stmt = $pdo->prepare("
        SELECT c.status, c.user_id, u.id, u.email, u.first_name, u.last_name, c.case_number
        FROM cases c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$data['case_id']]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$case) {
        throw new Exception('Case not found');
    }
    
    $currentStatus = $case['status'];
    $newStatus = $data['status'];
    
    // Update case
    $stmt = $pdo->prepare("
        UPDATE cases SET
            status = :status,
            admin_notes = :admin_notes,
            admin_id = :admin_id,
            updated_at = NOW()
        WHERE id = :case_id
    ");
    
    $stmt->execute([
        ':status' => $newStatus,
        ':admin_notes' => $data['admin_notes'] ?? null,
        ':admin_id' => $data['admin_id'] ?? $_SESSION['admin_id'],
        ':case_id' => $data['case_id']
    ]);
    
    // Record status change if different
    if ($currentStatus != $newStatus) {
        if (empty($_SESSION['admin_id'])) {
            throw new Exception('Cannot record status change - no admin ID in session');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO case_status_history (
                case_id, old_status, new_status, changed_by, notes
            ) VALUES (
                :case_id, :old_status, :new_status, :admin_id, :notes
            )
        ");
        
        $stmt->execute([
            ':case_id' => $data['case_id'],
            ':old_status' => $currentStatus,
            ':new_status' => $newStatus,
            ':admin_id' => $_SESSION['admin_id'],
            ':notes' => $data['status_notes'] ?? null
        ]);
        
        // Send status update email
        sendCaseStatusUpdateEmail($pdo, $case, $data['case_id'], $currentStatus, $newStatus, $data);
        
        // Special handling for document_required status
        if (in_array(strtolower($newStatus), ['document_required', 'documents_required']) && 
            !empty($data['required_documents'])) {
            
            // Send documents required email
            sendDocumentsRequiredEmail($pdo, $case, $data['case_id'], $data);
        }
    }
    
    $pdo->commit();
    
    $response = [
        'success' => true,
        'message' => 'Case updated successfully',
        'data' => [
            'case_id' => $data['case_id'],
            'old_status' => $currentStatus,
            'new_status' => $newStatus,
            'status_translated' => $statusTranslations[strtolower($newStatus)] ?? $newStatus
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Case update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update case',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

/**
 * Send case status update email notification
 */
function sendCaseStatusUpdateEmail($pdo, $userData, $caseId, $oldStatus, $newStatus, $updateData) {
    // Get status translations
    global $statusTranslations;
    
    try {
        $emailHelper = new AdminEmailHelper($pdo);
        
        $customVars = [
            'case_number' => $userData['case_number'] ?? 'N/A',
            'case_id' => $caseId,
            'old_status' => $statusTranslations[strtolower($oldStatus)] ?? $oldStatus,
            'new_status' => $statusTranslations[strtolower($newStatus)] ?? $newStatus,
            'status_notes' => $updateData['status_notes'] ?? '',
            'update_date' => date('Y-m-d H:i:s')
        ];
        
        $emailHelper->sendTemplateEmail('case_status_updated', $userData['id'], $customVars);
        error_log("Case status update email sent to: " . $userData['email'] . " for case ID: " . $caseId);
        
    } catch (Exception $e) {
        error_log("Case status update email sending failed: " . $e->getMessage());
        // Don't throw exception - email failure shouldn't break the case update
    }
}

/**
 * Send documents required email notification
 */
function sendDocumentsRequiredEmail($pdo, $userData, $caseId, $updateData) {
    try {
        $emailHelper = new AdminEmailHelper($pdo);
        
        // Prepare required documents list
        $requiredDocs = is_array($updateData['required_documents']) 
            ? $updateData['required_documents'] 
            : explode(',', $updateData['required_documents']);
        
        $documentsList = '<ul>';
        foreach ($requiredDocs as $doc) {
            $documentsList .= '<li>' . htmlspecialchars(trim($doc)) . '</li>';
        }
        $documentsList .= '</ul>';
        
        // Get system settings for upload link
        $systemStmt = $pdo->prepare("SELECT * FROM system_settings WHERE id = 1");
        $systemStmt->execute();
        $systemSettings = $systemStmt->fetch(PDO::FETCH_ASSOC);
        
        $customVars = [
            'case_number' => $userData['case_number'] ?? 'N/A',
            'case_id' => $caseId,
            'required_documents' => $documentsList,
            'additional_notes' => $updateData['status_notes'] ?? '',
            'upload_link' => ($systemSettings['site_url'] ?? 'https://your-site.com') . '/documents.php?case=' . $caseId,
            'deadline' => $updateData['deadline'] ?? 'ASAP'
        ];
        
        $emailHelper->sendTemplateEmail('documents_required', $userData['id'], $customVars);
        error_log("Documents required email sent to: " . $userData['email'] . " for case ID: " . $caseId);
        
    } catch (Exception $e) {
        error_log("Documents required email sending failed: " . $e->getMessage());
        // Don't throw exception - email failure shouldn't break the case update
    }
}
?>