<?php
require_once '../admin_session.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['admin_id'])) {
        throw new Exception('Unauthorized: Admin not logged in');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if (!$ticket_id || !$status) {
        throw new Exception('Ticket ID and status are required');
    }

    $allowed_statuses = ['open', 'in_progress', 'resolved', 'closed'];
    if (!in_array($status, $allowed_statuses)) {
        throw new Exception('Invalid status');
    }

    // === 1️⃣ Get ticket + user info ===
    $stmt = $pdo->prepare("
        SELECT id, user_id, subject, status 
        FROM support_tickets 
        WHERE id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        throw new Exception('Ticket not found');
    }

    // === 2️⃣ Update status ===
    $stmt = $pdo->prepare("
        UPDATE support_tickets 
        SET status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$status, $ticket_id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('No changes made or invalid ticket');
    }

    // === 3️⃣ Insert admin log ===
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at) 
        VALUES (?, 'STATUS_UPDATE', 'support_ticket', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['admin_id'],
        $ticket_id,
        "Updated ticket status to $status",
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    // === 4️⃣ 🔔 Create user notification ===
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_notifications 
                (user_id, title, message, type, related_entity, related_id, created_at)
            VALUES 
                (:user_id, :title, :message, :type, :entity, :entity_id, NOW())
        ");
        $stmt->execute([
            ':user_id' => (int)$ticket['user_id'],
            ':title' => 'Support-Ticket-Update',
            ':message' => 'Der Status Ihres Support-Tickets <strong>#' . htmlspecialchars($ticket_id) . '</strong> wurde auf <strong>' 
                . ucfirst($status) . '</strong> geändert.',
            ':type' => in_array($status, ['resolved', 'closed']) ? 'success' : 'info',
            ':entity' => 'support_ticket',
            ':entity_id' => $ticket_id
        ]);
    } catch (Exception $e) {
        error_log("User notification failed: " . $e->getMessage());
    }

    // === 5️⃣ 🧭 Create admin notification ===
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_notifications 
                (admin_id, title, message, type, is_read, created_at)
            VALUES 
                (:admin_id, :title, :message, :type, 0, NOW())
        ");
        $stmt->execute([
            ':admin_id' => $_SESSION['admin_id'],
            ':title' => 'Ticketstatus geändert',
            ':message' => 'Ticket <strong>#' . htmlspecialchars($ticket_id) . '</strong> wurde auf <strong>' 
                . ucfirst($status) . '</strong> gesetzt.',
            ':type' => in_array($status, ['resolved', 'closed']) ? 'success' : 'info'
        ]);
    } catch (Exception $e) {
        error_log("Admin notification failed: " . $e->getMessage());
    }

    // === 6️⃣ Success response ===
    echo json_encode([
        'success' => true,
        'message' => 'Ticket status updated successfully',
        'data' => [
            'ticket_id' => $ticket_id,
            'status' => $status,
            'subject' => $ticket['subject']
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in update_ticket_status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in update_ticket_status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
