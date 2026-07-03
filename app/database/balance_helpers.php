<?php

require_once __DIR__ . '/../EmailHelper.php';

function getUserBalanceSnapshot(PDO $pdo, int $userId, bool $forUpdate = false): array
{
    $sql = "SELECT id, first_name, last_name, email, balance FROM users WHERE id = ? LIMIT 1";
    if ($forUpdate) {
        $sql .= " FOR UPDATE";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Benutzer nicht gefunden.');
    }

    $user['balance'] = (float)($user['balance'] ?? 0);
    return $user;
}

function adjustUserBalance(PDO $pdo, int $userId, float $delta): array
{
    $user = getUserBalanceSnapshot($pdo, $userId, $pdo->inTransaction());
    $newBalance = round(((float)$user['balance']) + $delta, 2);

    if ($newBalance < 0) {
        throw new Exception('Das Nutzerguthaben reicht für diese Gebühr nicht aus.');
    }

    $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt->execute([$newBalance, $userId]);

    return [
        'user' => $user,
        'old_balance' => (float)$user['balance'],
        'new_balance' => $newBalance,
        'delta' => round($delta, 2),
    ];
}

function setUserBalance(PDO $pdo, int $userId, float $newBalance): array
{
    if ($newBalance < 0) {
        throw new Exception('Das Nutzerguthaben darf nicht negativ sein.');
    }

    $user = getUserBalanceSnapshot($pdo, $userId, $pdo->inTransaction());
    $newBalance = round($newBalance, 2);

    $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt->execute([$newBalance, $userId]);

    return [
        'user' => $user,
        'old_balance' => (float)$user['balance'],
        'new_balance' => $newBalance,
        'delta' => round($newBalance - (float)$user['balance'], 2),
    ];
}

function addBalanceUserNotification(PDO $pdo, int $userId, string $title, string $message, string $type = 'info', string $entity = 'balance', ?string $relatedId = null): void
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_notifications (user_id, title, message, type, related_entity, related_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $title, $message, $type, $entity, $relatedId]);
    } catch (Throwable $e) {
        error_log('balance_helpers user notification: ' . $e->getMessage());
    }
}

function addBalanceAdminNotification(PDO $pdo, int $adminId, string $title, string $message, string $type = 'info'): void
{
    if ($adminId <= 0) {
        return;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_notifications (admin_id, title, message, type, is_read, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$adminId, $title, $message, $type]);
    } catch (Throwable $e) {
        error_log('balance_helpers admin notification: ' . $e->getMessage());
    }
}

function sendBalanceEmail(PDO $pdo, int $userId, string $subject, string $htmlBody, array $customVars = []): void
{
    try {
        $emailHelper = new EmailHelper($pdo);
        $emailHelper->sendDirectEmail($userId, $subject, $htmlBody, $customVars);
    } catch (Throwable $e) {
        error_log('balance_helpers email: ' . $e->getMessage());
    }
}

function notifyBalanceCredit(PDO $pdo, int $userId, float $creditAmount, float $newBalance, string $sourceLabel): void
{
    if ($creditAmount <= 0) {
        return;
    }

    $formattedAmount = number_format($creditAmount, 2, ',', '.') . ' €';
    $formattedBalance = number_format($newBalance, 2, ',', '.') . ' €';

    addBalanceUserNotification(
        $pdo,
        $userId,
        'Guthaben aufgeladen',
        'Ihr Kontoguthaben wurde um <strong>' . $formattedAmount . '</strong> erhöht. Neuer Stand: <strong>' . $formattedBalance . '</strong>.',
        'success',
        'balance_credit',
        $sourceLabel
    );

    sendBalanceEmail(
        $pdo,
        $userId,
        'Ihr Guthaben wurde aufgeladen',
        '<p>Ihr Kontoguthaben wurde erfolgreich um <strong>' . $formattedAmount . '</strong> erhöht.</p>'
        . '<p><strong>Neuer Kontostand:</strong> ' . $formattedBalance . '<br>'
        . '<strong>Quelle:</strong> ' . htmlspecialchars($sourceLabel, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p>Sie können Ihre Analyse- und Recovery-Vorgänge nun wie gewohnt fortsetzen.</p>',
        [
            'amount' => $formattedAmount,
            'balance' => $formattedBalance,
        ]
    );
}

function notifyKiFeeCharge(PDO $pdo, int $userId, float $feeAmount, float $newBalance, string $entryTitle): void
{
    if ($feeAmount <= 0) {
        return;
    }

    $formattedFee = number_format($feeAmount, 2, ',', '.') . ' €';
    $formattedBalance = number_format($newBalance, 2, ',', '.') . ' €';

    addBalanceUserNotification(
        $pdo,
        $userId,
        'KI-Gebühr verbucht',
        'Für den Vorgang <strong>' . htmlspecialchars($entryTitle, ENT_QUOTES, 'UTF-8') . '</strong> wurden <strong>'
            . $formattedFee . '</strong> von Ihrem Guthaben abgebucht. Verbleibendes Guthaben: <strong>'
            . $formattedBalance . '</strong>.',
        'info',
        'ki_fee',
        $entryTitle
    );

    sendBalanceEmail(
        $pdo,
        $userId,
        'Neue KI-Transaktionsgebühr verbucht',
        '<p>Für Ihren Vorgang <strong>' . htmlspecialchars($entryTitle, ENT_QUOTES, 'UTF-8') . '</strong> wurde eine Gebühr in Höhe von <strong>'
            . $formattedFee . '</strong> verbucht.</p>'
            . '<p><strong>Verbleibendes Guthaben:</strong> ' . $formattedBalance . '</p>'
            . '<p>Bitte laden Sie Ihr Konto rechtzeitig auf, damit Suche und Rückgewinnung ohne Unterbrechung fortgesetzt werden können.</p>',
        [
            'amount' => $formattedFee,
            'balance' => $formattedBalance,
        ]
    );
}

function notifyBalanceDepleted(PDO $pdo, int $userId, float $newBalance): void
{
    if ($newBalance > 0) {
        return;
    }

    $shouldCreateAlert = true;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM user_notifications
            WHERE user_id = ?
              AND related_entity = 'balance_alert'
              AND created_at >= DATE_SUB(NOW(), INTERVAL 12 HOUR)
        ");
        $stmt->execute([$userId]);
        $shouldCreateAlert = ((int)$stmt->fetchColumn() === 0);
    } catch (Throwable $e) {
        error_log('balance_helpers alert dedupe: ' . $e->getMessage());
    }

    if (!$shouldCreateAlert) {
        return;
    }

    addBalanceUserNotification(
        $pdo,
        $userId,
        'Guthaben aufgebraucht',
        'Ihr Kontoguthaben ist auf <strong>0,00 €</strong> gefallen. Bitte laden Sie Ihr Konto auf, damit Such- und Recovery-Vorgänge weiterlaufen können.',
        'warning',
        'balance_alert',
        'topup_required'
    );

    sendBalanceEmail(
        $pdo,
        $userId,
        'Bitte Guthaben aufladen',
        '<p>Ihr verfügbares Guthaben ist derzeit auf <strong>0,00 €</strong> gefallen.</p>'
        . '<p>Bitte laden Sie Ihr Konto auf, damit unsere Such- und Recovery-Prozesse ohne Unterbrechung fortgesetzt werden können.</p>'
        . '<p>Sie können die Aufladung direkt im Kundenbereich unter <strong>Einzahlungen / Zahlungsmethoden</strong> vornehmen.</p>'
    );
}
