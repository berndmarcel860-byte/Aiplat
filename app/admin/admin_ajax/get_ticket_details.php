<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    $ticket_id = (int)($_GET['id'] ?? 0);
    
    if (!$ticket_id) {
        throw new Exception('Ticket ID is required');
    }
    
    // Get ticket details
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            u.email as user_email
        FROM support_tickets t
        LEFT JOIN users u ON t.user_id = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        throw new Exception('Ticket not found');
    }
    
    // Get replies
    $stmt = $pdo->prepare("
        SELECT 
            tr.*,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            CONCAT(a.first_name, ' ', a.last_name) as admin_name
        FROM ticket_replies tr
        LEFT JOIN users u ON tr.user_id = u.id
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