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
