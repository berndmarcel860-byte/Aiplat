<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $admin_id = $_POST['admin_id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = $_POST['type'] ?? 'info';
    
    if (empty($title) || empty($message)) {
        throw new Exception('Title and message are required');
    }
    
    if ($admin_id === 'all') {
        // Send to all active admins
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE status = 'active'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt = $pdo->prepare("
            INSERT INTO admin_notifications (admin_id, title, message, type, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        foreach ($admins as $admin) {
            $stmt->execute([$admin, $title, $message, $type]);
        }
        
        $message_text = "Notification sent to all admins";
    } else {
        // Send to specific admin
        $admin_id = (int)$admin_id;
        
        // Verify admin exists
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE id = ? AND status = 'active'");
        $stmt->execute([$admin_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Admin not found or inactive');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO admin_notifications (admin_id, title, message, type, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$admin_id, $title, $message, $type]);
        
        $message_text = "Notification sent successfully";
    }
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at) 
        VALUES (?, 'CREATE', 'notification', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['admin_id'], 
        0, 
        "Created notification: $title",
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => $message_text
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in create_notification.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>