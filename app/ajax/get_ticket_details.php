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
    $ticket_id = (int)($_GET['id'] ?? 0);
    
    if (!$ticket_id) {
        throw new Exception('Ticket ID is required');
    }
    
    // Get ticket details - ensure user owns this ticket
    $stmt = $pdo->prepare("
        SELECT * FROM support_tickets 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        throw new Exception('Ticket not found or access denied');
    }
    
    // Get replies
    $stmt = $pdo->prepare("
        SELECT 
            tr.*,
            CONCAT(a.first_name, ' ', a.last_name) as admin_name
        FROM ticket_replies tr
        LEFT JOIN admins a ON tr.admin_id = a.id
        WHERE tr.ticket_id = ?
        ORDER BY tr.created_at ASC
    ");
    $stmt->execute([$ticket_id]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'ticket' => $ticket,
            'replies' => $replies
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in get_ticket_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>