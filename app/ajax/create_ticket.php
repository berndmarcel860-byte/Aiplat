<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../EmailHelper.php';
require_once __DIR__ . '/../admin/AdminEmailHelper.php';

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
    
    $subject  = trim($_POST['subject']  ?? '');
    $message  = trim($_POST['message']  ?? '');
    $category = trim($_POST['category'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';

    // Validate priority against allowed values; fall back to 'medium' for unknown input
    $allowedPriorities = ['low', 'medium', 'high', 'critical'];
    if (!in_array($priority, $allowedPriorities, true)) {
        $priority = 'medium';
    }
    
    if (empty($subject) || empty($message) || empty($category)) {
        throw new Exception('Subject, message, and category are required');
    }
    
    // Translate priority to German for the email
    $priorityLabels = [
        'low'      => 'Niedrig',
        'medium'   => 'Mittel',
        'high'     => 'Hoch',
        'critical' => 'Kritisch',
    ];
    $priorityLabel = $priorityLabels[$priority];

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
    
    // Send ticket-created notification email to user (non-fatal if it fails)
    try {
        $emailHelper = new EmailHelper($pdo);
        $emailHelper->sendEmail('ticket_created', $_SESSION['user_id'], [
            'ticket_number'   => $ticket_number,
            'ticket_subject'  => $subject,
            'ticket_category' => $category,
            'ticket_priority' => $priorityLabel,
        ]);
    } catch (Exception $emailError) {
        error_log("Ticket creation email failed (ticket $ticket_number): " . $emailError->getMessage());
    }

    // Notify admin of new ticket via AdminEmailHelper (non-fatal)
    try {
        $adminEmailHelper = new AdminEmailHelper($pdo);
        $siteUrlStmt = $pdo->query("SELECT site_url FROM system_settings WHERE id = 1");
        $siteUrl = $siteUrlStmt ? ($siteUrlStmt->fetchColumn() ?: '') : '';
        $adminSubject = "Neues Support-Ticket: $ticket_number";
        $adminBody = "
            <p>Ein neues Support-Ticket wurde erstellt.</p>
            <div class='highlight-box'>
                <h3>&#127931; Ticket-Details</h3>
                <p><strong>Ticket-Nr.:</strong> " . htmlspecialchars($ticket_number) . "</p>
                <p><strong>Betreff:</strong> " . htmlspecialchars($subject) . "</p>
                <p><strong>Kategorie:</strong> " . htmlspecialchars($category) . "</p>
                <p><strong>Priorität:</strong> " . htmlspecialchars($priorityLabel) . "</p>
            </div>
            <p><a href='" . htmlspecialchars($siteUrl) . "/app/admin/admin_support_tickets.php' class='btn'>Ticket ansehen</a></p>
        ";
        $adminEmailHelper->sendAdminNotification($adminSubject, $adminBody);
    } catch (Exception $adminEmailError) {
        error_log("Admin ticket notification failed (ticket $ticket_number): " . $adminEmailError->getMessage());
    }
    
    echo json_encode([
        'success'       => true,
        'message'       => 'Ticket created successfully',
        'ticket_number' => $ticket_number,
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success'  => false,
        'message'  => $e->getMessage(),
    ]);
} catch (PDOException $e) {
    error_log("Database error in create_ticket.php: " . $e->getMessage());
    echo json_encode([
        'success'  => false,
        'message'  => 'Database error occurred',
    ]);
}
?>