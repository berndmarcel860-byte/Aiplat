<?php
/**
 * Cron Job: Trial package case setup
 *
 * Run example:
 *   every 5 minutes via cron using this script path
 *
 * Tasks:
 * - Detect newly activated trial packages from the last 5 minutes
 * - Create exactly 3 hard-difficulty cases (total 150,000 EUR) per eligible user
 * - Add a one-time welcome notification for algorithm start
 */

require_once __DIR__ . '/../config.php';

const TRIAL_LOOKBACK_MINUTES = 5;
const TRIAL_CASES_PER_USER = 3;
const TRIAL_TOTAL_AMOUNT = 150000.00;
const TRIAL_CASE_DESCRIPTION = 'KI-gestützte Fallregistrierung erfolgreich abgeschlossen. Erste Rückverfolgung der Transaktionen läuft.';
const TRIAL_WELCOME_TITLE = 'Case setup completed';
const TRIAL_WELCOME_MESSAGE = 'Your case files have been opened. Our algorithm is now analyzing your lost funds.';
const TRIAL_SETUP_ACTION = 'cron_trial_case_setup_completed';

error_log('Trial Case Setup Cron: Start at ' . date('Y-m-d H:i:s'));

try {
    $candidates = fetchTrialActivationCandidates($pdo);
    $summary = [
        'candidates' => count($candidates),
        'created_users' => 0,
        'created_cases' => 0,
        'skipped_existing_cases' => 0,
        'skipped_platforms' => 0,
        'failed' => 0,
    ];

    foreach ($candidates as $candidate) {
        $userId = (int)$candidate['user_id'];
        $userPackageId = (int)$candidate['user_package_id'];

        try {
            if (hasCasesCreatedToday($pdo, $userId)) {
                $summary['skipped_existing_cases']++;
                continue;
            }

            $platformIds = resolveThreePlatforms($pdo, $userId);
            if (count($platformIds) < TRIAL_CASES_PER_USER) {
                $summary['skipped_platforms']++;
                error_log("Trial Case Setup Cron: user_id={$userId} skipped (not enough active platforms)");
                continue;
            }

            $adminId = resolveCronAdminId($pdo);
            if ($adminId === null) {
                throw new RuntimeException('No admin account available for cron logging');
            }

            $amounts = splitFixedAmount(TRIAL_TOTAL_AMOUNT, TRIAL_CASES_PER_USER);
            $createdCaseIds = [];

            $pdo->beginTransaction();

            foreach ($platformIds as $index => $platformId) {
                $createdCaseIds[] = insertCase(
                    $pdo,
                    $userId,
                    (int)$platformId,
                    (float)$amounts[$index],
                    TRIAL_CASE_DESCRIPTION,
                    $adminId
                );
            }

            insertWelcomeNotification($pdo, $userId);
            logAdminAction($pdo, $adminId, TRIAL_SETUP_ACTION, [
                'user_id' => $userId,
                'user_package_id' => $userPackageId,
                'case_ids' => $createdCaseIds,
                'platform_ids' => $platformIds,
                'total_amount' => TRIAL_TOTAL_AMOUNT,
            ]);

            $pdo->commit();

            $summary['created_users']++;
            $summary['created_cases'] += count($createdCaseIds);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $summary['failed']++;
            error_log("Trial Case Setup Cron: user_id={$userId} failed - " . $e->getMessage());
        }
    }

    error_log('Trial Case Setup Cron: Done. ' . http_build_query($summary, '', ', '));
    exit(0);
} catch (Throwable $e) {
    error_log('Trial Case Setup Cron: Fatal error - ' . $e->getMessage());
    exit(1);
}

function fetchTrialActivationCandidates(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT up.id AS user_package_id, up.user_id, up.created_at
        FROM user_packages up
        INNER JOIN packages p ON p.id = up.package_id
        WHERE p.price = 0
          AND up.status = 'active'
          AND up.created_at >= DATE_SUB(NOW(), INTERVAL " . TRIAL_LOOKBACK_MINUTES . " MINUTE)
        ORDER BY up.created_at ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function hasCasesCreatedToday(PDO $pdo, int $userId): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM cases
        WHERE user_id = ?
          AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn() > 0;
}

function resolveThreePlatforms(PDO $pdo, int $userId): array
{
    $activePlatforms = fetchActivePlatformIds($pdo);
    if (count($activePlatforms) < TRIAL_CASES_PER_USER) {
        return [];
    }

    $selected = [];
    $selectedMap = [];

    $onboardingPlatforms = fetchOnboardingPlatformIds($pdo, $userId);
    foreach ($onboardingPlatforms as $platformId) {
        if (!in_array($platformId, $activePlatforms, true)) {
            continue;
        }
        if (isset($selectedMap[$platformId])) {
            continue;
        }
        $selected[] = $platformId;
        $selectedMap[$platformId] = true;
        if (count($selected) >= TRIAL_CASES_PER_USER) {
            return $selected;
        }
    }

    foreach ($activePlatforms as $platformId) {
        if (isset($selectedMap[$platformId])) {
            continue;
        }
        $selected[] = $platformId;
        $selectedMap[$platformId] = true;
        if (count($selected) >= TRIAL_CASES_PER_USER) {
            break;
        }
    }

    return array_slice($selected, 0, TRIAL_CASES_PER_USER);
}

function fetchOnboardingPlatformIds(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT platforms
        FROM user_onboarding
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $platformsJson = $stmt->fetchColumn();

    if (!is_string($platformsJson) || trim($platformsJson) === '') {
        return [];
    }

    $decoded = json_decode($platformsJson, true);
    if (!is_array($decoded)) {
        return [];
    }

    $platforms = [];
    foreach ($decoded as $value) {
        $id = (int)$value;
        if ($id > 0) {
            $platforms[] = $id;
        }
    }
    return $platforms;
}

function fetchActivePlatformIds(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id
        FROM scam_platforms
        WHERE is_active = 1
        ORDER BY id ASC
    ");
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    return array_map('intval', $ids);
}

function splitFixedAmount(float $total, int $parts): array
{
    $base = floor(($total / $parts) * 100) / 100;
    $amounts = array_fill(0, $parts, $base);
    $distributed = $base * $parts;
    $remainder = round($total - $distributed, 2);
    $amounts[$parts - 1] = round($amounts[$parts - 1] + $remainder, 2);
    return $amounts;
}

function insertCase(PDO $pdo, int $userId, int $platformId, float $amount, string $description, int $adminId): int
{
    $caseNumber = generateCaseNumber($pdo);

    $stmt = $pdo->prepare("
        INSERT INTO cases
            (case_number, user_id, platform_id, reported_amount, status, description, admin_id, refund_difficulty, created_at, updated_at)
        VALUES
            (:case_number, :user_id, :platform_id, :reported_amount, 'open', :description, :admin_id, 'hard', NOW(), NOW())
    ");
    $stmt->execute([
        ':case_number' => $caseNumber,
        ':user_id' => $userId,
        ':platform_id' => $platformId,
        ':reported_amount' => $amount,
        ':description' => $description,
        ':admin_id' => $adminId,
    ]);

    $caseId = (int)$pdo->lastInsertId();

    $historyStmt = $pdo->prepare("
        INSERT INTO case_status_history (case_id, new_status, changed_by, notes)
        VALUES (:case_id, 'open', :admin_id, 'Auto-created by trial case setup cron')
    ");
    $historyStmt->execute([
        ':case_id' => $caseId,
        ':admin_id' => $adminId,
    ]);

    return $caseId;
}

function generateCaseNumber(PDO $pdo): string
{
    $year = date('Y');
    for ($i = 0; $i < 10; $i++) {
        $candidate = 'SCM-' . $year . '-' . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $check = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE case_number = ?");
        $check->execute([$candidate]);
        if ((int)$check->fetchColumn() === 0) {
            return $candidate;
        }
    }

    return 'SCM-' . $year . '-' . substr(str_replace('.', '', (string)microtime(true)), -4);
}

function insertWelcomeNotification(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare("
        INSERT INTO user_notifications
            (user_id, title, message, type, related_entity, related_id, created_at)
        VALUES
            (:user_id, :title, :message, 'info', 'trial_case_setup', :related_id, NOW())
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':title' => TRIAL_WELCOME_TITLE,
        ':message' => TRIAL_WELCOME_MESSAGE,
        ':related_id' => 'trial_case_setup_' . date('YmdHis'),
    ]);
}

function resolveCronAdminId(PDO $pdo): ?int
{
    static $adminId = false;
    if ($adminId !== false) {
        return $adminId;
    }

    try {
        $stmt = $pdo->query("
            SELECT id
            FROM admins
            WHERE status = 'active'
            ORDER BY id ASC
            LIMIT 1
        ");
        $resolved = $stmt->fetchColumn();
        if ($resolved === false) {
            $stmt = $pdo->query("SELECT id FROM admins ORDER BY id ASC LIMIT 1");
            $resolved = $stmt->fetchColumn();
        }
        $adminId = $resolved === false ? null : (int)$resolved;
    } catch (Throwable $e) {
        $adminId = null;
    }

    return $adminId;
}

function logAdminAction(PDO $pdo, int $adminId, string $action, array $details): void
{
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, details, ip_address, created_at)
        VALUES (?, ?, ?, '127.0.0.1', NOW())
    ");
    $stmt->execute([
        $adminId,
        $action,
        json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
}
