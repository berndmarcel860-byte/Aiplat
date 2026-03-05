<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../config.php';
header('Content-Type: application/json');

if (empty($_POST['case_id'])) {
    echo json_encode(['success' => false, 'message' => 'Case ID required']);
    exit();
}

$caseId = (int)$_POST['case_id'];

try {
    // Check if case exists
    $caseStmt = $pdo->prepare("
        SELECT c.*, u.email, u.first_name, u.last_name 
        FROM cases c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.id = ?
    ");
    $caseStmt->execute([$caseId]);
    $case = $caseStmt->fetch(PDO::FETCH_ASSOC);

    if (!$case) {
        echo json_encode(['success' => false, 'message' => 'Case not found']);
        exit();
    }

    $pdo->beginTransaction();

    // Delete related records
    $tables = [
        'case_status_history',
        'case_documents'
    ];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE case_id = ?");
        $stmt->execute([$caseId]);
    }

    // Delete admin logs related to this case
    $stmt = $pdo->prepare("DELETE FROM admin_logs WHERE entity_type = 'case' AND entity_id = ?");
    $stmt->execute([$caseId]);

    // Delete admin notifications mentioning this case number
    $stmt = $pdo->prepare("DELETE FROM admin_notifications WHERE message LIKE ?");
    $stmt->execute(["%SCM-%{$caseId}%"]);

    // Finally delete the case itself
    $stmt = $pdo->prepare("DELETE FROM cases WHERE id = ?");
    $stmt->execute([$caseId]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Case and related logs deleted successfully'
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Database error during deletion',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Unexpected error occurred',
        'error' => $e->getMessage()
    ]);
}
?>
