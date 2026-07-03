<?php
require_once '../../config.php';
require_once '../admin_session.php';
require_once '../../database/balance_helpers.php';

header('Content-Type: application/json');

// Validate input
$required = ['id', 'first_name', 'last_name', 'email', 'status'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$userId = (int)$_POST['id'];
$data = [
    'first_name' => trim($_POST['first_name']),
    'last_name' => trim($_POST['last_name']),
    'email' => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
    'status' => in_array($_POST['status'], ['active', 'suspended', 'banned']) ? $_POST['status'] : 'active',
    'balance' => isset($_POST['balance']) ? (float)$_POST['balance'] : 0,
    'phone' => isset($_POST['phone']) ? preg_replace('/[^0-9+]/', '', $_POST['phone']) : null,
    'country' => isset($_POST['country']) ? substr(trim($_POST['country']), 0, 100) : null
];

try {
    // Check if email exists for another user
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkStmt->execute([$data['email'], $userId]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }

    $pdo->beginTransaction();

    $balanceChange = setUserBalance($pdo, $userId, (float)$data['balance']);

    // Update user
    $stmt = $pdo->prepare("
        UPDATE users SET 
        first_name = :first_name,
        last_name = :last_name,
        email = :email,
        status = :status,
        phone = :phone,
        country = :country,
        updated_at = NOW()
        WHERE id = :id
    ");
    $data['id'] = $userId;
    $stmt->execute($data);
    
    // Log this action
    $logStmt = $pdo->prepare("
        INSERT INTO admin_logs 
        (admin_id, action, entity_type, entity_id, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $logStmt->execute([
        $_SESSION['admin_id'],
        'update',
        'user',
        $userId,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    $pdo->commit();

    $delta = (float)$balanceChange['delta'];
    if ($delta > 0) {
        notifyBalanceCredit($pdo, $userId, $delta, (float)$balanceChange['new_balance'], 'Manuelle Guthabenanpassung durch den Support');
    } elseif ($delta < 0) {
        $formattedDelta = number_format(abs($delta), 2, ',', '.') . ' €';
        $formattedBalance = number_format((float)$balanceChange['new_balance'], 2, ',', '.') . ' €';

        addBalanceUserNotification(
            $pdo,
            $userId,
            'Guthaben angepasst',
            'Ihr Guthaben wurde manuell um <strong>' . $formattedDelta . '</strong> reduziert. Neuer Kontostand: <strong>' . $formattedBalance . '</strong>.',
            'warning',
            'balance_adjustment',
            'admin_manual'
        );

        sendBalanceEmail(
            $pdo,
            $userId,
            'Ihr Guthaben wurde angepasst',
            '<p>Ihr Guthaben wurde manuell um <strong>' . $formattedDelta . '</strong> reduziert.</p>'
            . '<p><strong>Neuer Kontostand:</strong> ' . $formattedBalance . '</p>'
        );
        notifyBalanceDepleted($pdo, $userId, (float)$balanceChange['new_balance']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully',
        'user' => [
            'id' => $userId,
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'email' => $data['email'],
            'status' => $data['status'],
            'balance' => $data['balance']
        ]
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Update User Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update user',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Update User Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>