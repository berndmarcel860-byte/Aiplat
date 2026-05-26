<?php
/**
 * Cron Job: Trial package case setup
 *
 * Run example:
 *   every 5 minutes via cron using this script path
 *
 * Tasks:
 * - Process active 48h trial packages (price=0)
 * - Create at most 1 case per user per run (throttled interval)
 * - Gradually reach total 150,000 EUR within the 48h trial window
 * - Add a one-time welcome notification for algorithm start
 */

require_once __DIR__ . '/../config.php';

const TRIAL_ACTIVE_WINDOW_HOURS = 48;
const TRIAL_CASE_INTERVAL_MINUTES = 30;
const TRIAL_CASES_PER_USER = 3;
const TRIAL_TOTAL_AMOUNT = 150000.00;
const TRIAL_CASE_DESCRIPTION = 'KI-gestützte Fallregistrierung erfolgreich abgeschlossen. Erste Rückverfolgung der Transaktionen läuft.';
const TRIAL_WELCOME_TITLE = 'Case setup completed';
const TRIAL_WELCOME_MESSAGE = 'Your case files have been opened. Our algorithm is now analyzing your lost funds.';
const TRIAL_SETUP_ACTION = 'cron_trial_case_setup_completed';
const TRIAL_HISTORY_NOTE = 'Auto-created by trial case setup cron';
const TRIAL_WELCOME_ENTITY = 'trial_case_setup';

error_log('Trial Case Setup Cron: Start at ' . date('Y-m-d H:i:s'));

try {
    $candidates = fetchTrialActivationCandidates($pdo);
    $summary = [
        'candidates' => count($candidates),
        'created_users' => 0,
        'created_cases' => 0,
        'skipped_interval' => 0,
        'skipped_completed' => 0,
        'skipped_platforms' => 0,
        'failed' => 0,
    ];

    foreach ($candidates as $candidate) {
        $userId = (int)$candidate['user_id'];
        $userPackageId = (int)$candidate['user_package_id'];

        try {
            if (hasRecentTrialCaseCreation($pdo, $userId, TRIAL_CASE_INTERVAL_MINUTES)) {
                $summary['skipped_interval']++;
                continue;
            }

            $progress = fetchTrialProgress($pdo, $userId);
            $remainingAmount = round(TRIAL_TOTAL_AMOUNT - (float)$progress['total_amount'], 2);
            if ($remainingAmount <= 0) {
                $summary['skipped_completed']++;
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

            $trialEndAt = resolveTrialEndAt($candidate);
            $caseAmount = calculateNextCaseAmount($remainingAmount, $trialEndAt, TRIAL_CASE_INTERVAL_MINUTES);
            $platformId = resolveNextPlatformId($pdo, $userId, $platformIds);

            $pdo->beginTransaction();

            $caseId = insertCase(
                $pdo,
                $userId,
                $platformId,
                $caseAmount,
                TRIAL_CASE_DESCRIPTION,
                $adminId
            );

            insertWelcomeNotificationOnce($pdo, $userId);

            logAdminAction($pdo, $adminId, TRIAL_SETUP_ACTION, [
                'user_id' => $userId,
                'user_package_id' => $userPackageId,
                'case_id' => $caseId,
                'platform_id' => $platformId,
                'case_amount' => $caseAmount,
                'remaining_before' => $remainingAmount,
                'remaining_after' => round($remainingAmount - $caseAmount, 2),
                'interval_minutes' => TRIAL_CASE_INTERVAL_MINUTES,
                'trial_end_at' => $trialEndAt,
            ]);

            $pdo->commit();

            $summary['created_users']++;
            $summary['created_cases']++;
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
    $stmt = $pdo->query("\n        SELECT up.id AS user_package_id, up.user_id, up.created_at, up.end_date\n        FROM user_packages up\n        INNER JOIN packages p ON p.id = up.package_id\n        WHERE p.price = 0\n          AND up.status = 'active'\n          AND up.created_at >= DATE_SUB(NOW(), INTERVAL " . TRIAL_ACTIVE_WINDOW_HOURS . " HOUR)\n          AND COALESCE(up.end_date, DATE_ADD(up.created_at, INTERVAL " . TRIAL_ACTIVE_WINDOW_HOURS . " HOUR)) >= NOW()\n        ORDER BY up.created_at ASC\n    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function hasRecentTrialCaseCreation(PDO $pdo, int $userId, int $intervalMinutes): bool
{
    $stmt = $pdo->prepare("\n        SELECT MAX(c.created_at)\n        FROM cases c\n        INNER JOIN case_status_history csh ON csh.case_id = c.id\n        WHERE c.user_id = ?\n          AND csh.notes = ?\n          AND c.created_at >= DATE_SUB(NOW(), INTERVAL " . TRIAL_ACTIVE_WINDOW_HOURS . " HOUR)\n    ");
    $stmt->execute([$userId, TRIAL_HISTORY_NOTE]);
    $lastCreatedAt = $stmt->fetchColumn();

    if (!is_string($lastCreatedAt) || trim($lastCreatedAt) === '') {
        return false;
    }

    $lastTimestamp = strtotime($lastCreatedAt);
    if ($lastTimestamp === false) {
        return false;
    }

    return (time() - $lastTimestamp) < ($intervalMinutes * 60);
}

function fetchTrialProgress(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("\n        SELECT\n            COUNT(DISTINCT c.id) AS case_count,\n            COALESCE(SUM(c.reported_amount), 0) AS total_amount\n        FROM cases c\n        INNER JOIN case_status_history csh ON csh.case_id = c.id\n        WHERE c.user_id = ?\n          AND csh.notes = ?\n          AND c.created_at >= DATE_SUB(NOW(), INTERVAL " . TRIAL_ACTIVE_WINDOW_HOURS . " HOUR)\n    ");
    $stmt->execute([$userId, TRIAL_HISTORY_NOTE]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'case_count' => (int)($row['case_count'] ?? 0),
        'total_amount' => (float)($row['total_amount'] ?? 0),
    ];
}

function resolveTrialEndAt(array $candidate): string
{
    $endDate = (string)($candidate['end_date'] ?? '');
    $endTimestamp = strtotime($endDate);
    if ($endDate !== '' && $endTimestamp !== false) {
        return date('Y-m-d H:i:s', $endTimestamp);
    }

    $createdAt = (string)($candidate['created_at'] ?? '');
    $createdTimestamp = strtotime($createdAt);
    if ($createdTimestamp === false) {
        $createdTimestamp = time();
    }

    return date('Y-m-d H:i:s', $createdTimestamp + (TRIAL_ACTIVE_WINDOW_HOURS * 3600));
}

function calculateNextCaseAmount(float $remainingAmount, string $trialEndAt, int $intervalMinutes): float
{
    $endTimestamp = strtotime($trialEndAt);
    if ($endTimestamp === false) {
        return round($remainingAmount, 2);
    }

    $secondsLeft = max(0, $endTimestamp - time());
    $minutesLeft = max(1, (int)ceil($secondsLeft / 60));
    $runsLeft = max(1, (int)ceil($minutesLeft / max(1, $intervalMinutes)));

    $amount = round($remainingAmount / $runsLeft, 2);
    if ($amount <= 0) {
        $amount = min(0.01, $remainingAmount);
    }

    return round(min($remainingAmount, $amount), 2);
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

function resolveNextPlatformId(PDO $pdo, int $userId, array $platformIds): int
{
    $counts = array_fill_keys($platformIds, 0);

    $placeholders = implode(',', array_fill(0, count($platformIds), '?'));
    $params = [$userId, TRIAL_HISTORY_NOTE];
    foreach ($platformIds as $platformId) {
        $params[] = (int)$platformId;
    }

    $stmt = $pdo->prepare("\n        SELECT c.platform_id, COUNT(DISTINCT c.id) AS cnt\n        FROM cases c\n        INNER JOIN case_status_history csh ON csh.case_id = c.id\n        WHERE c.user_id = ?\n          AND csh.notes = ?\n          AND c.platform_id IN ({$placeholders})\n          AND c.created_at >= DATE_SUB(NOW(), INTERVAL " . TRIAL_ACTIVE_WINDOW_HOURS . " HOUR)\n        GROUP BY c.platform_id\n    ");
    $stmt->execute($params);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $platformId = (int)($row['platform_id'] ?? 0);
        if (isset($counts[$platformId])) {
            $counts[$platformId] = (int)$row['cnt'];
        }
    }

    $selected = (int)$platformIds[0];
    $minCount = PHP_INT_MAX;
    foreach ($platformIds as $platformId) {
        $count = (int)($counts[$platformId] ?? 0);
        if ($count < $minCount) {
            $minCount = $count;
            $selected = (int)$platformId;
        }
    }

    return $selected;
}

function fetchOnboardingPlatformIds(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("\n        SELECT platforms\n        FROM user_onboarding\n        WHERE user_id = ?\n        ORDER BY id DESC\n        LIMIT 1\n    ");
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
    $stmt = $pdo->query("\n        SELECT id\n        FROM scam_platforms\n        WHERE is_active = 1\n        ORDER BY id ASC\n    ");
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    return array_map('intval', $ids);
}

function insertCase(PDO $pdo, int $userId, int $platformId, float $amount, string $description, int $adminId): int
{
    $caseNumber = generateCaseNumber($pdo);

    $stmt = $pdo->prepare("\n        INSERT INTO cases\n            (case_number, user_id, platform_id, reported_amount, status, description, admin_id, refund_difficulty, created_at, updated_at)\n        VALUES\n            (:case_number, :user_id, :platform_id, :reported_amount, 'open', :description, :admin_id, 'hard', NOW(), NOW())\n    ");
    $stmt->execute([
        ':case_number' => $caseNumber,
        ':user_id' => $userId,
        ':platform_id' => $platformId,
        ':reported_amount' => $amount,
        ':description' => $description,
        ':admin_id' => $adminId,
    ]);

    $caseId = (int)$pdo->lastInsertId();

    $historyStmt = $pdo->prepare("\n        INSERT INTO case_status_history (case_id, new_status, changed_by, notes)\n        VALUES (:case_id, 'open', :admin_id, :note)\n    ");
    $historyStmt->execute([
        ':case_id' => $caseId,
        ':admin_id' => $adminId,
        ':note' => TRIAL_HISTORY_NOTE,
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

function insertWelcomeNotificationOnce(PDO $pdo, int $userId): void
{
    $checkStmt = $pdo->prepare("\n        SELECT COUNT(*)\n        FROM user_notifications\n        WHERE user_id = ?\n          AND related_entity = ?\n    ");
    $checkStmt->execute([$userId, TRIAL_WELCOME_ENTITY]);
    if ((int)$checkStmt->fetchColumn() > 0) {
        return;
    }

    $stmt = $pdo->prepare("\n        INSERT INTO user_notifications\n            (user_id, title, message, type, related_entity, related_id, created_at)\n        VALUES\n            (:user_id, :title, :message, 'info', :related_entity, :related_id, NOW())\n    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':title' => TRIAL_WELCOME_TITLE,
        ':message' => TRIAL_WELCOME_MESSAGE,
        ':related_entity' => TRIAL_WELCOME_ENTITY,
        ':related_id' => 'trial_case_setup',
    ]);
}

function resolveCronAdminId(PDO $pdo): ?int
{
    static $adminId = false;
    if ($adminId !== false) {
        return $adminId;
    }

    try {
        $stmt = $pdo->query("\n            SELECT id\n            FROM admins\n            WHERE status = 'active'\n            ORDER BY id ASC\n            LIMIT 1\n        ");
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
    $stmt = $pdo->prepare("\n        INSERT INTO admin_logs (admin_id, action, details, ip_address, created_at)\n        VALUES (?, ?, ?, '127.0.0.1', NOW())\n    ");
    $stmt->execute([
        $adminId,
        $action,
        json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
}
