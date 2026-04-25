<?php
/**
 * chat_init.php — Create or resume the user's active chat session.
 * Returns session info + recent messages + AI greeting if brand-new.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$uid = (int)$_SESSION['user_id'];

try {
    // Find an existing active session
    $st = $pdo->prepare("SELECT id, topic, unread_user FROM live_chat_sessions WHERE user_id=? AND status='active' ORDER BY id DESC LIMIT 1");
    $st->execute([$uid]);
    $session = $st->fetch();

    $isNew = false;
    if (!$session) {
        // Create fresh session
        $pdo->prepare("INSERT INTO live_chat_sessions (user_id) VALUES (?)")->execute([$uid]);
        $sessionId = (int)$pdo->lastInsertId();
        $isNew = true;
        $session = ['id'=>$sessionId,'topic'=>null,'unread_user'=>0];
    }

    $sessionId = (int)$session['id'];

    // Mark all messages as read for user
    $pdo->prepare("UPDATE live_chat_messages SET is_read=1 WHERE session_id=? AND sender_type IN ('bot','admin') AND is_read=0")->execute([$sessionId]);
    $pdo->prepare("UPDATE live_chat_sessions SET unread_user=0 WHERE id=?")->execute([$sessionId]);

    // Fetch messages (last 60)
    $msgs = $pdo->prepare("SELECT id, sender_type, sender_name, message, is_read, created_at FROM live_chat_messages WHERE session_id=? ORDER BY id DESC LIMIT 60");
    $msgs->execute([$sessionId]);
    $messages = array_reverse($msgs->fetchAll());

    // Insert AI greeting if brand-new and no messages yet
    if ($isNew && empty($messages)) {
        $userName = 'there';
        $uRow = $pdo->prepare("SELECT first_name FROM users WHERE id=?");
        $uRow->execute([$uid]);
        $uData = $uRow->fetch();
        if ($uData) $userName = htmlspecialchars($uData['first_name'], ENT_QUOTES);

        $greeting = "Hallo $userName! 👋 Ich bin Ihr KI-Assistent. Wie kann ich Ihnen heute helfen?\n\n" .
                    "Bitte wählen Sie ein Thema:\n" .
                    "• Falldetails\n• KYC-Hilfe\n• Einzahlungshilfe\n• Auszahlungshilfe\n• Finanzhilfe\n• Technische Hilfe\n• Anderes";

        $ins = $pdo->prepare("INSERT INTO live_chat_messages (session_id, sender_type, sender_name, message, is_read) VALUES (?,?,?,?,1)");
        $ins->execute([$sessionId, 'bot', null, $greeting]);

        $msgs2 = $pdo->prepare("SELECT id, sender_type, sender_name, message, is_read, created_at FROM live_chat_messages WHERE session_id=? ORDER BY id ASC");
        $msgs2->execute([$sessionId]);
        $messages = $msgs2->fetchAll();
    }

    echo json_encode([
        'success'    => true,
        'session_id' => $sessionId,
        'topic'      => $session['topic'],
        'messages'   => $messages,
        'is_new'     => $isNew,
    ]);
} catch (PDOException $e) {
    error_log('chat_init: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
