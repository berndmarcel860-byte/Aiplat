<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

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
        $query = "SELECT id, email, first_name, last_name FROM users WHERE ";
        
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
    
    if (empty($users)) {
        throw new Exception('No recipients found matching your criteria');
    }
    
    // Prepare email content
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    // For each user, personalize and send email
    $sentCount = 0;
    foreach ($users as $user) {
        // Personalize message (replace variables)
        $personalizedMessage = $message;
        $personalizedMessage = str_replace('{{user_id}}', $user['id'], $personalizedMessage);
        $personalizedMessage = str_replace('{{email}}', $user['email'], $personalizedMessage);
        $personalizedMessage = str_replace('{{first_name}}', $user['first_name'], $personalizedMessage);
        $personalizedMessage = str_replace('{{last_name}}', $user['last_name'], $personalizedMessage);
        $personalizedMessage = str_replace('{{full_name}}', $user['first_name'] . ' ' . $user['last_name'], $personalizedMessage);
        
        // In a real application, you would send the email here
        // For this example, we'll just log it to the email_logs table
        
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (
                template_id,
                recipient,
                subject,
                content,
                sent_at,
                status
            ) VALUES (
                :template_id,
                :recipient,
                :subject,
                :content,
                NOW(),
                'sent'
            )
        ");
        
        $stmt->execute([
            ':template_id' => !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null,
            ':recipient' => $user['email'],
            ':subject' => $subject,
            ':content' => $personalizedMessage
        ]);
        
        $sentCount++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Email sent successfully to $sentCount recipients"
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send email',
        'error' => $e->getMessage()
    ]);
}
?>