<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../EmailHelper.php';
require_once __DIR__ . '/../database/satoshi_test_helpers.php';
require_once __DIR__ . '/../database/balance_helpers.php';

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

    // Get user with balances from users table
    $topupBalanceSql = getUserTopupBalanceSql($pdo);
    $userStmt = $pdo->prepare("SELECT id, email, first_name, last_name, balance, {$topupBalanceSql} AS topup_balance FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found', 404);
    }

    // Block withdrawal if user only has a trial (free) package – require active paid subscription
    $pkgStmt = $pdo->prepare(
        "SELECT up.status, p.price
         FROM user_packages up
         JOIN packages p ON up.package_id = p.id
         WHERE up.user_id = ?
         ORDER BY up.end_date DESC LIMIT 1"
    );
    $pkgStmt->execute([$_SESSION['user_id']]);
    $userPkg = $pkgStmt->fetch(PDO::FETCH_ASSOC);
    $hasActivePaidPkg = $userPkg && $userPkg['status'] === 'active' && (float)$userPkg['price'] > 0;
    if (!$hasActivePaidPkg) {
        throw new Exception('Withdrawals require an active paid subscription. Please upgrade your account.', 403);
    }

    // Use amount from users table (balance) as validation source; actual withdrawal amount comes from POST
    $userBalance = (float)($user['balance'] ?? 0);
    $userTopupBalance = (float)($user['topup_balance'] ?? 0);

    if ($amount < 1000) {
        throw new Exception('Minimum withdrawal amount is €1,000', 400);
    }

    if ($amount > $userBalance) {
        throw new Exception('Insufficient balance. Available: €' . number_format($userBalance, 2, ',', '.'), 400);
    }

    $packagesEnabled = true;
    try {
        $pkgSwitchStmt = $pdo->query("SELECT packages_enabled FROM system_settings WHERE id = 1 LIMIT 1");
        $pkgSwitchRow = $pkgSwitchStmt->fetch(PDO::FETCH_ASSOC);
        if ($pkgSwitchRow && isset($pkgSwitchRow['packages_enabled'])) {
            $packagesEnabled = ((int)$pkgSwitchRow['packages_enabled'] === 1);
        }
    } catch (Throwable $e) { /* optional */ }

    $satoshiRequired = isSatoshiVerificationRequired($packagesEnabled, $userBalance, 50000.0);
    $allowBySatoshi = $satoshiRequired && userHasVerifiedTest($pdo, (int)$_SESSION['user_id']);

    // Get the user's payment method (wallet verification OR account-level Satoshi verification)
    $methodSql = $allowBySatoshi
        ? "SELECT id, type, payment_method, cryptocurrency, wallet_address, iban, account_number, bank_name, label
           FROM user_payment_methods
           WHERE id = ? AND user_id = ?"
        : "SELECT id, type, payment_method, cryptocurrency, wallet_address, iban, account_number, bank_name, label
           FROM user_payment_methods
           WHERE id = ? AND user_id = ? AND verification_status = 'verified'";
    $methodStmt = $pdo->prepare($methodSql);
    $methodStmt->execute([$paymentMethodId, $_SESSION['user_id']]);
    $paymentMethod = $methodStmt->fetch(PDO::FETCH_ASSOC);

    if (!$paymentMethod) {
        throw new Exception('Ungültige oder nicht verifizierte Zahlungsmethode', 400);
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
    $defaultWithdrawalFeePercentage = 3.0;
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

    $effectiveFeePercentage = ($feePercentage > 0) ? $feePercentage : $defaultWithdrawalFeePercentage;
    $feeEnabled = true;
    $feeAmount = round($amount * $effectiveFeePercentage / 100, 2);

    if ($feeAmount > 0 && $userTopupBalance < $feeAmount) {
        throw new Exception(
            'Ihr Aufladeguthaben reicht nicht aus, um die Auszahlungsgebühr von '
            . number_format($feeAmount, 2, ',', '.') . ' € zu decken. '
            . 'Bitte laden Sie zuerst Ihr Top-up Guthaben auf.',
            400
        );
    }

    // Generate unique reference
    $reference = 'WD-' . time() . '-' . strtoupper(substr(uniqid(), -6));

    // Begin database transaction
    $pdo->beginTransaction();

    try {
        // Deduct withdrawal amount from user balance
        $deductStmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
        $deductStmt->execute([$amount, $_SESSION['user_id'], $amount]);

        if ($deductStmt->rowCount() === 0) {
            throw new Exception('Insufficient balance or concurrent update conflict', 400);
        }

        // Deduct withdrawal fee from top-up balance
        $topupBalanceColumn = getUserTopupBalanceSql($pdo);
        $feeDeductStmt = $pdo->prepare("UPDATE users SET {$topupBalanceColumn} = {$topupBalanceColumn} - ? WHERE id = ? AND {$topupBalanceColumn} >= ?");
        $feeDeductStmt->execute([$feeAmount, $_SESSION['user_id'], $feeAmount]);

        if ($feeDeductStmt->rowCount() === 0) {
            throw new Exception('Ihr Aufladeguthaben reicht für die Auszahlungsgebühr nicht aus.', 400);
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
            $feeEnabled ? $effectiveFeePercentage : null,
            $feeEnabled ? $feeAmount     : null,
        ]);
        $withdrawalId = (int)$pdo->lastInsertId();

        // Mark fee as already paid via top-up balance (if column exists)
        try {
            $pdo->prepare("UPDATE withdrawals SET fee_status = 'approved' WHERE id = ? LIMIT 1")
                ->execute([$withdrawalId]);
        } catch (PDOException $e) {
            // Migration not run yet; ignore
        }

        // Get updated balances from users table
        $balStmt = $pdo->prepare("SELECT balance, {$topupBalanceSql} AS topup_balance FROM users WHERE id = ?");
        $balStmt->execute([$_SESSION['user_id']]);
        $updatedBalances = $balStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $newBalance = (float)($updatedBalances['balance'] ?? 0);
        $newTopupBalance = (float)($updatedBalances['topup_balance'] ?? 0);

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
        'new_topup_balance'  => number_format($newTopupBalance, 2, ',', '.'),
        'fee_enabled'  => $feeEnabled,
        'fee_amount'   => $feeEnabled ? number_format($feeAmount, 2, ',', '.') : null,
        'fee_percentage' => $feeEnabled ? $effectiveFeePercentage : null,
    ]);

} catch (Exception $e) {
    $code = (int)($e->getCode() ?: 400);
    http_response_code($code > 0 ? $code : 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
