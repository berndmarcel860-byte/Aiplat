<?php
require_once __DIR__ . '/../config.php';
// Check if user is logged in
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if (!$ticket_id || !$message) {
        throw new Exception('Ticket ID and message are required');
    }
    
    // Verify ticket exists and belongs to user
    $stmt = $pdo->prepare("SELECT id, status, ticket_number FROM support_tickets WHERE id = ? AND user_id = ?");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        throw new Exception('Ticket not found or access denied');
    }
    
    if ($ticket['status'] === 'closed') {
        throw new Exception('Cannot reply to a closed ticket');
    }
    
    $pdo->beginTransaction();
    
    // Handle file uploads
    $attachments = [];
    if (isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        $upload_dir = '../uploads/tickets/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        foreach ($_FILES['attachments']['name'] as $key => $name) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $file_size = $_FILES['attachments']['size'][$key];
                
                // Check file size (10MB limit)
                if ($file_size > 10 * 1024 * 1024) {
                    throw new Exception("File $name is too large. Maximum size is 10MB.");
                }
                
                // Generate unique filename
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . time() . '.' . $extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $filepath)) {
                    $attachments[] = $filename;
                } else {
                    throw new Exception("Failed to upload file: $name");
                }
            }
        }
    }
    
    // Insert reply
    $stmt = $pdo->prepare("
        INSERT INTO ticket_replies (ticket_id, user_id, message, attachments, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $ticket_id, 
        $_SESSION['user_id'], 
        $message, 
        !empty($attachments) ? json_encode($attachments) : null
    ]);
    
    // Update ticket last reply time and set status to open if it was resolved
    $new_status = ($ticket['status'] === 'resolved') ? 'open' : $ticket['status'];
    $stmt = $pdo->prepare("
        UPDATE support_tickets 
        SET last_reply_at = NOW(), updated_at = NOW(), status = ?
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $ticket_id]);
    
    // Create audit log
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, entity_type, entity_id, new_value, ip_address, user_agent, created_at)
        VALUES (?, 'USER_REPLY', 'support_ticket', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $ticket_id,
        "User replied to ticket {$ticket['ticket_number']}",
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reply sent successfully'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error in reply_ticket.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>