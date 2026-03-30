<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../EmailHelper.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access - Please login', 401);
    }

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Security error - Invalid CSRF token', 403);
    }

    // Check OTP verification
    if (empty($_SESSION['otp_verified'])) {
        throw new Exception('OTP verification required before submitting withdrawal', 400);
    }

    // Validate and sanitize inputs
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $paymentMethodId = filter_input(INPUT_POST, 'payment_method_id', FILTER_VALIDATE_INT);
    $paymentDetails  = trim($_POST['payment_details'] ?? '');

    if (!$amount || $amount <= 0) {
        throw new Exception('Please enter a valid withdrawal amount', 400);
    }

    if (!$paymentMethodId) {
        throw new Exception('Please select a payment method', 400);
    }

    if (empty($paymentDetails)) {
        throw new Exception('Please enter payment details', 400);
    }

    // Get user with balance from users table
    $userStmt = $pdo->prepare("SELECT id, email, first_name, last_name, balance FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found', 404);
    }

    // Use amount from users table (balance) as validation source; actual withdrawal amount comes from POST
    $userBalance = (float)($user['balance'] ?? 0);

    if ($amount < 1000) {
        throw new Exception('Minimum withdrawal amount is €1,000', 400);
    }

    if ($amount > $userBalance) {
        throw new Exception('Insufficient balance. Available: €' . number_format($userBalance, 2, ',', '.'), 400);
    }

    // Get the user's payment method
    $methodStmt = $pdo->prepare("
        SELECT id, type, payment_method, cryptocurrency, wallet_address, iban, account_number, bank_name, label
        FROM user_payment_methods
        WHERE id = ? AND user_id = ? AND verification_status = 'verified'
    ");
    $methodStmt->execute([$paymentMethodId, $_SESSION['user_id']]);
    $paymentMethod = $methodStmt->fetch(PDO::FETCH_ASSOC);

    if (!$paymentMethod) {
        throw new Exception('Invalid or unverified payment method', 400);
    }

    // Determine display name for payment method
    if (!empty($paymentMethod['label'])) {
        $methodName = $paymentMethod['label'];
    } elseif ($paymentMethod['type'] === 'crypto') {
        $methodName = ucfirst($paymentMethod['cryptocurrency'] ?? 'Crypto');
    } else {
        $methodName = $paymentMethod['bank_name'] ?? 'Bank Transfer';
    }

    $methodCode = $paymentMethod['payment_method'] ?? $paymentMethod['type'] ?? 'bank';

    // ── Load withdrawal fee settings ─────────────────────────────────────
    $feeEnabled    = false;
    $feePercentage = 0.0;
    try {
        $feeStmt = $pdo->query(
            "SELECT withdrawal_fee_enabled, withdrawal_fee_percentage
             FROM system_settings WHERE id = 1 LIMIT 1"
        );
        $feeRow = $feeStmt->fetch(PDO::FETCH_ASSOC);
        if ($feeRow) {
            $feeEnabled    = (bool)(int)$feeRow['withdrawal_fee_enabled'];
            $feePercentage = (float)$feeRow['withdrawal_fee_percentage'];
        }
    } catch (PDOException $e) {
        // Columns not yet added – migration pending; proceed without fee
    }

    $feeAmount = $feeEnabled ? round($amount * $feePercentage / 100, 2) : 0.0;

    // Generate unique reference
    $reference = 'WD-' . time() . '-' . strtoupper(substr(uniqid(), -6));

    // Begin database transaction
    $pdo->beginTransaction();

    try {
        // Deduct balance from user (amount comes from POST, validated against users.balance above)
        $deductStmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
        $deductStmt->execute([$amount, $_SESSION['user_id'], $amount]);

        if ($deductStmt->rowCount() === 0) {
            throw new Exception('Insufficient balance or concurrent update conflict', 400);
        }

        // Insert withdrawal record
        $insertStmt = $pdo->prepare("
            INSERT INTO withdrawals (user_id, amount, method_code, payment_details, reference, status, fee_percentage, fee_amount, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NOW())
        ");
        $insertStmt->execute([
            $_SESSION['user_id'],
            $amount,
            $methodCode,
            $paymentDetails,
            $reference,
            $feeEnabled ? $feePercentage : null,
            $feeEnabled ? $feeAmount     : null,
        ]);

        // Get updated balance from users table
        $balStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $balStmt->execute([$_SESSION['user_id']]);
        $newBalance = (float)$balStmt->fetchColumn();

        $pdo->commit();

    } catch (Exception $dbEx) {
        $pdo->rollBack();
        throw $dbEx;
    }

    // Clear OTP session after successful submission
    unset($_SESSION['otp_verified'], $_SESSION['withdraw_otp'], $_SESSION['otp_expire']);

    // Send withdrawal_pending email notification
    // amount is explicitly passed; falls back to users.balance if not provided (AdminEmailHelper default)
    try {
        $emailHelper = new EmailHelper($pdo);
        $customVars = [
            'amount'          => number_format($amount, 2, ',', '.') . ' €',
            'reference'       => $reference,
            'transaction_id'  => $reference,
            'payment_method'  => $methodName,
            'payment_details' => $paymentDetails,
            'transaction_date' => date('d.m.Y H:i'),
            'transaction_status' => 'Ausstehend',
        ];
        $emailHelper->sendEmail('withdrawal_pending', (int)$_SESSION['user_id'], $customVars);
    } catch (Exception $emailEx) {
        error_log('Withdrawal pending email failed: ' . $emailEx->getMessage());
        // Email failure does not roll back the withdrawal
    }

    echo json_encode([
        'success'      => true,
        'message'      => 'Ihr Auszahlungsantrag wurde erfolgreich eingereicht. Sie erhalten eine Bestätigung per E-Mail.',
        'reference'    => $reference,
        'amount'       => number_format($amount, 2, ',', '.'),
        'new_balance'  => number_format($newBalance, 2, ',', '.'),
        'fee_enabled'  => $feeEnabled,
        'fee_amount'   => $feeEnabled ? number_format($feeAmount, 2, ',', '.') : null,
        'fee_percentage' => $feeEnabled ? $feePercentage : null,
    ]);

} catch (Exception $e) {
    $code = (int)($e->getCode() ?: 400);
    http_response_code($code > 0 ? $code : 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
