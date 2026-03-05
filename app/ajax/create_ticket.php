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
    
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    
    if (empty($subject) || empty($message) || empty($category)) {
        throw new Exception('Subject, message, and category are required');
    }
    
    // Generate ticket number
    $ticket_number = 'TICKET-' . strtoupper(uniqid());
    
    // Insert ticket
    $stmt = $pdo->prepare("
        INSERT INTO support_tickets (user_id, ticket_number, subject, message, category, priority, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $ticket_number, $subject, $message, $category, $priority]);
    
    // Create audit log
    $ticket_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, entity_type, entity_id, new_value, ip_address, user_agent, created_at)
        VALUES (?, 'CREATE', 'support_ticket', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $ticket_id,
        "User created support ticket: $ticket_number",
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Ticket created successfully',
        'ticket_number' => $ticket_number
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in create_ticket.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>