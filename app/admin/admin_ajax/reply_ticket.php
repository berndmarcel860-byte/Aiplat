<?php
require_once '../admin_session.php';
header('Content-Type: application/json');

try {
    // === 1️⃣ Ensure admin is logged in ===
    if (!isset($_SESSION['admin_id'])) {
        throw new Exception('Unauthorized: Admin not logged in');
    }

    // === 2️⃣ Validate request method ===
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // === 3️⃣ Validate CSRF token ===
    if (!isset($_POST['csrf_token']) || empty($_SESSION['admin_csrf_token']) || $_POST['csrf_token'] !== $_SESSION['admin_csrf_token']) {
        throw new Exception('Invalid CSRF token');
    }

    // === 4️⃣ Validate fields ===
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $new_status = $_POST['new_status'] ?? '';

    if (!$ticket_id || !$message) {
        throw new Exception('Ticket ID and message are required');
    }

    // === 5️⃣ Verify ticket exists ===
    $stmt = $pdo->prepare("
        SELECT t.id, t.status, t.ticket_number, t.subject, u.id AS user_id, u.email, u.first_name, u.last_name
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ticket) throw new Exception('Ticket not found');

    $pdo->beginTransaction();

    // === 6️⃣ Insert reply ===
    $stmt = $pdo->prepare("
        INSERT INTO ticket_replies (ticket_id, admin_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$ticket_id, $_SESSION['admin_id'], $message]);

    // === 7️⃣ Update ticket status if needed ===
    if ($new_status && in_array($new_status, ['in_progress', 'resolved', 'closed'])) {
        $stmt = $pdo->prepare("
            UPDATE support_tickets 
            SET status = ?, last_reply_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $ticket_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE support_tickets 
            SET last_reply_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$ticket_id]);
    }

    // === 8️⃣ Log activity ===
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at) 
        VALUES (?, 'REPLY', 'support_ticket', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['admin_id'],
        $ticket_id,
        "Admin replied to ticket" . ($new_status ? " and set status to $new_status" : ""),
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    $pdo->commit();

    // === 9️⃣ 🔔 Notify user ===
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_notifications 
                (user_id, title, message, type, related_entity, related_id, created_at)
            VALUES 
                (:user_id, :title, :message, :type, 'support_ticket', :related_id, NOW())
        ");
        $stmt->execute([
            ':user_id' => $ticket['user_id'],
            ':title' => 'Neue Antwort vom Support-Team',
            ':message' => 'Ihr Support-Ticket <strong>#' . htmlspecialchars($ticket['ticket_number']) . '</strong> hat eine neue Antwort erhalten.',
            ':type' => 'info',
            ':related_id' => $ticket_id
        ]);
    } catch (Exception $e) {
        error_log("User notification failed: " . $e->getMessage());
    }

    // === 🔔 Notify Admins ===
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_notifications 
                (admin_id, title, message, type, is_read, created_at)
            VALUES 
                (:admin_id, :title, :message, 'info', 0, NOW())
        ");
        $stmt->execute([
            ':admin_id' => $_SESSION['admin_id'],
            ':title' => 'Ticket-Antwort erstellt',
            ':message' => 'Eine Antwort wurde zu Ticket <strong>#' . htmlspecialchars($ticket['ticket_number']) . '</strong> hinzugefügt.'
        ]);
    } catch (Exception $e) {
        error_log("Admin notification failed: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Reply sent successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Reply Ticket Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Database error in reply_ticket.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>

