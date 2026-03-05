<?php
require_once '../admin_session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['admin_csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

if (!isset($_POST['ticket_id']) || !is_numeric($_POST['ticket_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
    exit();
}

if (empty($_POST['message'])) {
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit();
}

$ticket_id = (int)$_POST['ticket_id'];
$admin_id = $_SESSION['admin_id'];
$message = trim($_POST['message']);
$priority = isset($_POST['priority']) ? $_POST['priority'] : null;
$internal_notes = isset($_POST['internal_notes']) ? trim($_POST['internal_notes']) : null;
$change_status = isset($_POST['change_status']) ? (bool)$_POST['change_status'] : false;
$attachment_ids = isset($_POST['attachment_ids']) ? explode(',', $_POST['attachment_ids']) : [];

try {
    $pdo->beginTransaction();

    // Process attachments
    $attachments = [];
    if (!empty($attachment_ids)) {
        $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
        
        foreach ($attachment_ids as $file_id) {
            $stmt->execute([$file_id]);
            $file = $stmt->fetch();
            
            if ($file) {
                $attachments[] = $file['file_path'];
            }
        }
    }

    // Insert the reply
    $stmt = $pdo->prepare("
        INSERT INTO ticket_replies (ticket_id, admin_id, message, attachments, created_at)
        VALUES (:ticket_id, :admin_id, :message, :attachments, NOW())
    ");
    $stmt->execute([
        ':ticket_id' => $ticket_id,
        ':admin_id' => $admin_id,
        ':message' => $message,
        ':attachments' => !empty($attachments) ? json_encode($attachments) : null
    ]);

    // Update ticket status/priority if changed
    $update_fields = [];
    $update_params = [':id' => $ticket_id];

    if ($change_status) {
        $update_fields[] = "status = 'in_progress'";
    }

    if (!empty($priority)) {
        $update_fields[] = "priority = :priority";
        $update_params[':priority'] = $priority;
    }

    if (!empty($update_fields)) {
        $stmt = $pdo->prepare("
            UPDATE support_tickets 
            SET " . implode(', ', $update_fields) . ", 
                last_reply_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute($update_params);
    }

    // Add internal notes if provided
    if (!empty($internal_notes)) {
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at)
            VALUES (:admin_id, 'ticket_note', 'ticket', :ticket_id, :notes, :ip, :ua, NOW())
        ");
        $stmt->execute([
            ':admin_id' => $admin_id,
            ':ticket_id' => $ticket_id,
            ':notes' => $internal_notes,
            ':ip' => $_SERVER['REMOTE_ADDR'],
            ':ua' => $_SERVER['HTTP_USER_AGENT']
        ]);
    }

    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at)
        VALUES (:admin_id, 'ticket_reply', 'ticket', :ticket_id, 'Replied to ticket', :ip, :ua, NOW())
    ");
    $stmt->execute([
        ':admin_id' => $admin_id,
        ':ticket_id' => $ticket_id,
        ':ip' => $_SERVER['REMOTE_ADDR'],
        ':ua' => $_SERVER['HTTP_USER_AGENT']
    ]);

    // Get user info for notification
    $stmt = $pdo->prepare("
        SELECT u.email, u.first_name, u.last_name, t.ticket_number, t.subject
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket_info = $stmt->fetch();

    $pdo->commit();

    // Send email notification to user
    if ($ticket_info) {
        $to = $ticket_info['email'];
        $subject = "Re: Your Support Ticket #{$ticket_info['ticket_number']} - {$ticket_info['subject']}";
        
        $body = "
            <p>Dear {$ticket_info['first_name']},</p>
            
            <p>We have received your reply regarding ticket <strong>#{$ticket_info['ticket_number']}</strong> with subject <strong>{$ticket_info['subject']}</strong>.</p>
            
            <div style='background:#f5f5f5; padding:15px; border-left:4px solid #1890ff; margin:15px 0;'>
                " . nl2br(htmlspecialchars($message)) . "
            </div>
            
            <p>You can view the full conversation and reply by visiting your support ticket:</p>
            
            <p><a href='".SITE_URL."/support_ticket.php?id={$ticket_id}' style='background:#1890ff; color:#fff; padding:10px 15px; text-decoration:none; border-radius:4px; display:inline-block;'>View Ticket</a></p>
            
            <p>If you have any further questions, please don't hesitate to reply to this email or through the ticket system.</p>
            
            <p>Best regards,<br>
            {$_SESSION['admin_name']}<br>
            Support Team<br>
            ".SITE_NAME."</p>
        ";
        
        // In a real implementation, you would use your email sending function here
        // sendEmail($to, $subject, $body);
    }

    echo json_encode(['success' => true, 'message' => 'Reply sent successfully!']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
