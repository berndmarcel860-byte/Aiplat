<?php
require_once '../admin_session.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Ticket ID is required']);
    exit();
}

$ticket_id = (int)$_GET['id'];

try {
    // Get ticket and user info
    $stmt = $pdo->prepare("
        SELECT t.*, u.id as user_id, u.first_name, u.last_name, u.email 
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        exit();
    }

    // Get conversation (original message + replies)
    $stmt = $pdo->prepare("
        (SELECT 
            id, NULL as admin_id, user_id, message, NULL as attachments, created_at
        FROM support_tickets 
        WHERE id = ?)
        
        UNION ALL
        
        (SELECT 
            id, admin_id, NULL as user_id, message, attachments, created_at
        FROM ticket_replies 
        WHERE ticket_id = ?)
        
        ORDER BY created_at ASC
    ");
    $stmt->execute([$ticket_id, $ticket_id]);
    $conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process attachments for replies (only ticket_replies has attachments)
    foreach ($conversation as &$message) {
        if ($message['attachments']) {
            $attachments = json_decode($message['attachments'], true);
            $processedAttachments = [];
            
            if (is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    $processedAttachments[] = [
                        'name' => basename($attachment),
                        'url' => $attachment
                    ];
                }
            }
            
            $message['attachments'] = json_encode($processedAttachments);
        }
    }

    echo json_encode([
        'success' => true,
        'ticket' => $ticket,
        'user' => [
            'id' => $ticket['user_id'],
            'first_name' => $ticket['first_name'],
            'last_name' => $ticket['last_name'],
            'email' => $ticket['email']
        ],
        'conversation' => $conversation
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}