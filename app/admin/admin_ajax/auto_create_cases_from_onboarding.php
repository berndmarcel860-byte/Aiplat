<?php
/**
 * Auto-Create Cases From Onboarding
 *
 * For a given user_id this endpoint:
 *  1. Reads the platforms stored in user_onboarding and creates one case per
 *     platform (if a case on that platform does not already exist).
 *  2. When the user has no completed onboarding record, it generates up to
 *     30 000 EUR in "recovery" cases split randomly across active platforms
 *     (no more than one run per calendar day per user).
 *  3. Assigns refund_difficulty automatically based on the case amount:
 *     easy  (< 5 000 EUR)
 *     medium (5 000 – 50 000 EUR)
 *     hard  (> 50 000 EUR)
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../admin_session.php';
require_once '../../EmailHelper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'user_id required']);
    exit();
}

/**
 * Assign refund_difficulty based on EUR amount.
 */
function difficultyForAmount(float $amount): string
{
    if ($amount < 5000) return 'easy';
    if ($amount <= 50000) return 'medium';
    return 'hard';
}

/**
 * Generate a unique case number, retrying on collision.
 */
function generateCaseNumber(PDO $pdo): string
{
    $year = date('Y');
    for ($i = 0; $i < 10; $i++) {
        $candidate = 'SCM-' . $year . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $check = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE case_number = ?");
        $check->execute([$candidate]);
        if ((int)$check->fetchColumn() === 0) {
            return $candidate;
        }
    }
    // Final fallback: use microsecond timestamp
    return 'SCM-' . $year . '-' . substr(str_replace('.', '', microtime(true)), -4);
}

/**
 * Insert a single case record and all associated rows.
 * Returns the new case id or throws on failure.
 */
function insertCase(
    PDO $pdo,
    int $userId,
    int $platformId,
    float $amount,
    string $description,
    int $adminId
): int {
    $caseNumber = generateCaseNumber($pdo);
    $difficulty = difficultyForAmount($amount);

    $stmt = $pdo->prepare("
        INSERT INTO cases
            (case_number, user_id, platform_id, reported_amount, status,
             description, admin_id, refund_difficulty, created_at, updated_at)
        VALUES
            (:case_number, :user_id, :platform_id, :reported_amount, 'open',
             :description, :admin_id, :difficulty, NOW(), NOW())
    ");
    $stmt->execute([
        ':case_number'    => $caseNumber,
        ':user_id'        => $userId,
        ':platform_id'    => $platformId,
        ':reported_amount'=> $amount,
        ':description'    => $description,
        ':admin_id'       => $adminId,
        ':difficulty'     => $difficulty,
    ]);
    $caseId = (int)$pdo->lastInsertId();

    // Status history
    $pdo->prepare("
        INSERT INTO case_status_history (case_id, new_status, changed_by, notes)
        VALUES (:case_id, 'open', :admin_id, 'Auto-created from onboarding')
    ")->execute([':case_id' => $caseId, ':admin_id' => $adminId]);

    // User notification
    try {
        $pdo->prepare("
            INSERT INTO user_notifications
                (user_id, title, message, type, related_entity, related_id, created_at)
            VALUES (:user_id, :title, :message, 'info', 'case', :rel_id, NOW())
        ")->execute([
            ':user_id' => $userId,
            ':title'   => 'Neuer Fall eröffnet',
            ':message' => 'Ein neuer Fall <strong>' . htmlspecialchars($caseNumber)
                . '</strong> wurde automatisch für Sie erstellt.',
            ':rel_id'  => $caseNumber,
        ]);
    } catch (Exception $e) {
        error_log("auto_create_cases notification error: " . $e->getMessage());
    }

    return $caseId;
}

try {
    $adminId = (int)$_SESSION['admin_id'];

    /* ------------------------------------------------------------------ */
    /*  Check for existing onboarding data                                 */
    /* ------------------------------------------------------------------ */
    $onbStmt = $pdo->prepare("
        SELECT lost_amount, platforms, completed
        FROM user_onboarding
        WHERE user_id = ?
        LIMIT 1
    ");
    $onbStmt->execute([$userId]);
    $onboarding = $onbStmt->fetch(PDO::FETCH_ASSOC);

    $created = [];

    /* ------------------------------------------------------------------ */
    /*  CASE A – onboarding exists and is completed                        */
    /* ------------------------------------------------------------------ */
    if ($onboarding && $onboarding['completed']) {

        $platformIds = json_decode($onboarding['platforms'] ?? '[]', true) ?: [];
        $totalAmount = (float)$onboarding['lost_amount'];
        $platformCount = count($platformIds);

        if ($platformCount === 0) {
            echo json_encode(['success' => false, 'message' => 'No platforms in onboarding data']);
            exit();
        }

        // Distribute amount across platforms (at least 1 EUR per platform)
        $baseAmount = max(1, floor($totalAmount / $platformCount));

        $pdo->beginTransaction();

        // Small amounts (< 100 EUR total): create one case with the full amount
        if ($totalAmount < 100) {
            $firstPlatformId = (int)$platformIds[0];
            // Check if case already exists
            $existsStmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE user_id = ? AND platform_id = ?");
            $existsStmt->execute([$userId, $firstPlatformId]);
            if ($existsStmt->fetchColumn() == 0) {
                $description = 'KI-gestützte Fallregistrierung erfolgreich abgeschlossen. '
                    . 'Erste Rückverfolgung der Transaktionen läuft.';
                $caseId = insertCase($pdo, $userId, $firstPlatformId, $totalAmount, $description, $adminId);
                $created[] = $caseId;
            }
        } else {
            foreach ($platformIds as $platformId) {
                $platformId = (int)$platformId;

                // Skip if a case already exists for this user+platform
                $existsStmt = $pdo->prepare("
                    SELECT COUNT(*) FROM cases WHERE user_id = ? AND platform_id = ?
                ");
                $existsStmt->execute([$userId, $platformId]);
                if ($existsStmt->fetchColumn() > 0) {
                    continue;
                }

                $description = 'KI-gestützte Fallregistrierung erfolgreich abgeschlossen. '
                    . 'Erste Rückverfolgung der Transaktionen läuft.';

                $caseId = insertCase($pdo, $userId, $platformId, $baseAmount, $description, $adminId);
                $created[] = $caseId;
            }
        }

        $pdo->commit();

        echo json_encode([
            'success'       => true,
            'message'       => count($created) . ' case(s) created from onboarding data',
            'cases_created' => count($created),
            'case_ids'      => $created,
        ]);
        exit();
    }

    /* ------------------------------------------------------------------ */
    /*  CASE B – no completed onboarding: generate up to 30 000 EUR/day   */
    /* ------------------------------------------------------------------ */

    // Only run once per calendar day per user
    $todayCheck = $pdo->prepare("
        SELECT COUNT(*) FROM cases
        WHERE user_id = ?
          AND DATE(created_at) = CURDATE()
    ");
    $todayCheck->execute([$userId]);
    if ($todayCheck->fetchColumn() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cases already generated today for this user (no-onboarding fallback)',
        ]);
        exit();
    }

    // Fetch all active platforms
    $platforms = $pdo->query("SELECT id FROM scam_platforms WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($platforms)) {
        echo json_encode(['success' => false, 'message' => 'No active platforms available']);
        exit();
    }

    // Pick 3-6 random platforms
    shuffle($platforms);
    $selectedPlatforms = array_slice($platforms, 0, min(count($platforms), mt_rand(3, 6)));
    $platformCount = count($selectedPlatforms);

    // Total budget max 30 000 EUR; randomise a bit
    $totalBudget = mt_rand(15000, 30000);

    // Split budget across platforms (random but summing to totalBudget)
    $shares = [];
    $remaining = $totalBudget;
    for ($i = 0; $i < $platformCount - 1; $i++) {
        $platformsLeft = $platformCount - $i;
        $minShare = min(1000, (int)floor($remaining / $platformsLeft));
        $maxShare = (int)floor($remaining / $platformsLeft);
        $share = ($minShare <= $maxShare) ? mt_rand($minShare, $maxShare) : $minShare;
        $shares[] = max(1, $share);
        $remaining -= $shares[$i];
    }
    $shares[] = max(1, $remaining); // last platform gets the remainder

    $pdo->beginTransaction();

    foreach ($selectedPlatforms as $idx => $platformId) {
        $amount = (float)$shares[$idx];
        $description = 'KI-gestützte Fallregistrierung erfolgreich abgeschlossen. '
            . 'Erste Rückverfolgung der Transaktionen läuft.';

        $caseId = insertCase($pdo, $userId, (int)$platformId, $amount, $description, $adminId);
        $created[] = $caseId;
    }

    $pdo->commit();

    echo json_encode([
        'success'       => true,
        'message'       => count($created) . ' case(s) created (no-onboarding fallback, max 30k EUR/day)',
        'cases_created' => count($created),
        'total_amount'  => $totalBudget,
        'case_ids'      => $created,
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("auto_create_cases DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("auto_create_cases error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
