<?php
require_once '../admin_session.php';
require_once __DIR__ . '/../AdminEmailHelper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$requiredFields = ['recipients', 'subject', 'message'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: ' . implode(', ', $missingFields)
    ]);
    exit();
}

try {
    // Determine recipient users based on selection
    $recipientTypes = is_array($_POST['recipients']) ? $_POST['recipients'] : [$_POST['recipients']];
    $users = [];

    foreach ($recipientTypes as $type) {
        $query = "SELECT id, email FROM users WHERE ";

        switch ($type) {
            case 'all':
                $query .= "1=1";
                break;
            case 'verified':
                $query .= "is_verified = 1";
                break;
            case 'unverified':
                $query .= "is_verified = 0";
                break;
            case 'with_cases':
                $query .= "id IN (SELECT DISTINCT user_id FROM cases)";
                break;
            case 'without_cases':
                $query .= "id NOT IN (SELECT DISTINCT user_id FROM cases)";
                break;
            case 'active':
                $query .= "status = 'active'";
                break;
            case 'suspended':
                $query .= "status = 'suspended'";
                break;
            default:
                continue 2; // Skip unknown types
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $users = array_merge($users, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Deduplicate by user id
    $seen  = [];
    $users = array_filter($users, function ($u) use (&$seen) {
        if (isset($seen[$u['id']])) return false;
        $seen[$u['id']] = true;
        return true;
    });

    if (empty($users)) {
        throw new Exception('No recipients found matching your criteria');
    }

    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Convert plain-text newlines to HTML paragraphs
    $htmlMessage = '';
    foreach (explode("\n", str_replace("\r\n", "\n", $message)) as $line) {
        $line = trim($line);
        $htmlMessage .= ($line !== '') ? '<p>' . $line . '</p>' : '<br>';
    }

    // Use AdminEmailHelper – company info is fetched from system_settings automatically.
    $emailHelper = new AdminEmailHelper($pdo);

    $sentCount  = 0;
    $failedCount = 0;

    foreach ($users as $user) {
        if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            $failedCount++;
            continue;
        }

        if ($emailHelper->sendDirectEmail((int)$user['id'], $subject, $htmlMessage)) {
            $sentCount++;
        } else {
            $failedCount++;
        }
    }

    // Log admin action
    $logStmt = $pdo->prepare(
        "INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address, created_at)
         VALUES (?, 'send_email_bulk', 'users', 0, ?, ?, NOW())"
    );
    $logStmt->execute([
        $_SESSION['admin_id'],
        "Bulk email: \"{$subject}\" — {$sentCount} sent, {$failedCount} failed",
        $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    echo json_encode([
        'success' => true,
        'message' => "Email sent successfully to {$sentCount} recipient(s)." . ($failedCount > 0 ? " {$failedCount} failed." : ''),
        'sent'    => $sentCount,
        'failed'  => $failedCount,
    ]);
} catch (Exception $e) {
    error_log("send_email.php error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send email: ' . $e->getMessage(),
    ]);
}
?>