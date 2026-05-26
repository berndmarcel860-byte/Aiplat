<?php
/**
 * Cron Job: Trial package case setup
 *
 * Run example:
 *   every 5 minutes via cron using this script path
 *
 * Tasks:
 * - Process active 48h trial packages (price=0)
 * - Create at most 1 case per cron run (throttled interval)
 * - Gradually reach total 150,000 EUR within the 48h trial window
 * - Start case generation 1 hour after trial activation
 * - Add a one-time welcome notification for algorithm start
 * - Send "case_created" email like manual case creation
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../EmailHelper.php';

const DEFAULT_TRIAL_ACTIVE_WINDOW_HOURS = 48;
const DEFAULT_TRIAL_CASE_INTERVAL_MINUTES = 5;
const DEFAULT_TRIAL_INITIAL_DELAY_MINUTES = 5;
const DEFAULT_TRIAL_MAX_CASES_PER_RUN = 2;
const DEFAULT_TRIAL_CASES_PER_USER = 3;
const DEFAULT_TRIAL_TOTAL_AMOUNT = 150000.00;
const DEFAULT_TRIAL_AMOUNT_VARIATION_PERCENT = 20.00;
const DEFAULT_TRIAL_INTERVAL_VARIATION_PERCENT = 35.00;
const TRIAL_CASE_DESCRIPTION = 'KI-gestützte Fallregistrierung erfolgreich abgeschlossen. Erste Rückverfolgung der Transaktionen läuft.';
const TRIAL_WELCOME_TITLE = 'Case setup completed';
const TRIAL_WELCOME_MESSAGE = 'Your case files have been opened. Our algorithm is now analyzing your lost funds.';
const TRIAL_SETUP_ACTION = 'cron_trial_case_setup_completed';
const TRIAL_HISTORY_NOTE = 'Auto-created by trial case setup cron';
const TRIAL_WELCOME_ENTITY = 'trial_case_setup';

error_log('Trial Case Setup Cron: Start at ' . date('Y-m-d H:i:s'));

try {
    $trialSettings = loadTrialCaseSetupSettings($pdo);
    $candidates = fetchTrialActivationCandidates($pdo, $trialSettings['active_window_hours']);
    $emailHelper = new EmailHelper($pdo);
    $summary = [
        'candidates' => count($candidates),
        'created_users' => 0,
        'created_cases' => 0,
        'emails_sent' => 0,
        'skipped_interval' => 0,
        'skipped_initial_delay' => 0,
        'skipped_completed' => 0,
        'skipped_platforms' => 0,
        'stopped_run_limit' => 0,
        'failed' => 0,
        'settings' => json_encode($trialSettings, JSON_UNESCAPED_SLASHES),
    ];

    foreach ($candidates as $candidate) {
        if ($summary['created_cases'] >= $trialSettings['max_cases_per_run']) {
            $summary['stopped_run_limit']++;
            break;
        }

        $userId = (int)$candidate['user_id'];
        $userPackageId = (int)$candidate['user_package_id'];

        try {
            if (!hasPassedInitialDelay($candidate, $trialSettings['initial_delay_minutes'])) {
                $summary['skipped_initial_delay']++;
                continue;
            }

            if (hasRecentTrialCaseCreation(
                $pdo,
                $userId,
                $trialSettings['case_interval_minutes'],
                $trialSettings['active_window_hours'],
                (float)$trialSettings['interval_variation_percent']
            )) {
                $summary['skipped_interval']++;
                continue;
            }

            $progress = fetchTrialProgress($pdo, $userId, $trialSettings['active_window_hours']);
            $remainingAmount = round($trialSettings['total_amount'] - (float)$progress['total_amount'], 2);
            if ($remainingAmount <= 0) {
                $summary['skipped_completed']++;
                continue;
            }

            $platformIds = resolvePlatforms($pdo, $userId, $trialSettings['cases_per_user']);
            if (count($platformIds) < $trialSettings['cases_per_user']) {
                $summary['skipped_platforms']++;
                error_log("Trial Case Setup Cron: user_id={$userId} skipped (not enough active platforms)");
                continue;
            }

            $adminId = resolveCronAdminId($pdo);
            if ($adminId === null) {
                throw new RuntimeException('No admin account available for cron logging');
            }

            $trialEndAt = resolveTrialEndAt($candidate, $trialSettings['active_window_hours']);
            $caseAmount = calculateNextCaseAmount(
                $remainingAmount,
                $trialEndAt,
                $trialSettings['case_interval_minutes'],
                (float)$trialSettings['amount_variation_percent']
            );
            $caseAmount = ensureNonRepeatingTrialAmount(
                $pdo,
                $userId,
                $caseAmount,
                $remainingAmount,
                $trialSettings['active_window_hours']
            );
            $platformId = resolveNextPlatformId($pdo, $userId, $platformIds, $trialSettings['active_window_hours']);

            $pdo->beginTransaction();

            $case = insertCase(
                $pdo,
                $userId,
                $platformId,
                $caseAmount,
                TRIAL_CASE_DESCRIPTION,
                $adminId
            );

            insertWelcomeNotificationOnce($pdo, $userId);
            $emailSent = sendTrialCaseCreatedEmail(
                $pdo,
                $emailHelper,
                $userId,
                $case['id'],
                $case['case_number'],
                $platformId,
                $caseAmount
            );

            logAdminAction($pdo, $adminId, TRIAL_SETUP_ACTION, [
                'user_id' => $userId,
                'user_package_id' => $userPackageId,
                'case_id' => $case['id'],
                'case_number' => $case['case_number'],
                'platform_id' => $platformId,
                'case_amount' => $caseAmount,
                'remaining_before' => $remainingAmount,
                'remaining_after' => round($remainingAmount - $caseAmount, 2),
                'interval_minutes' => $trialSettings['case_interval_minutes'],
                'trial_end_at' => $trialEndAt,
                'email_sent' => $emailSent,
            ]);

            $pdo->commit();

            $summary['created_users']++;
            $summary['created_cases']++;
            if ($emailSent) {
                $summary['emails_sent']++;
            }
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

function loadTrialCaseSetupSettings(PDO $pdo): array
{
    $defaults = [
        'active_window_hours' => DEFAULT_TRIAL_ACTIVE_WINDOW_HOURS,
        'case_interval_minutes' => DEFAULT_TRIAL_CASE_INTERVAL_MINUTES,
        'initial_delay_minutes' => DEFAULT_TRIAL_INITIAL_DELAY_MINUTES,
        'max_cases_per_run' => DEFAULT_TRIAL_MAX_CASES_PER_RUN,
        'cases_per_user' => DEFAULT_TRIAL_CASES_PER_USER,
        'total_amount' => DEFAULT_TRIAL_TOTAL_AMOUNT,
        'amount_variation_percent' => DEFAULT_TRIAL_AMOUNT_VARIATION_PERCENT,
        'interval_variation_percent' => DEFAULT_TRIAL_INTERVAL_VARIATION_PERCENT,
    ];

    try {
        $stmt = $pdo->query("
            SELECT trial_active_window_hours, trial_case_interval_minutes, trial_initial_delay_minutes,
                   trial_max_cases_per_run, trial_cases_per_user, trial_total_amount,
                   trial_amount_variation_percent, trial_interval_variation_percent
            FROM system_settings
            WHERE id = 1
            LIMIT 1
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return $defaults;
    }

    return [
        'active_window_hours' => max(1, (int)($row['trial_active_window_hours'] ?? $defaults['active_window_hours'])),
        'case_interval_minutes' => max(1, (int)($row['trial_case_interval_minutes'] ?? $defaults['case_interval_minutes'])),
        'initial_delay_minutes' => max(0, (int)($row['trial_initial_delay_minutes'] ?? $defaults['initial_delay_minutes'])),
        'max_cases_per_run' => max(1, (int)($row['trial_max_cases_per_run'] ?? $defaults['max_cases_per_run'])),
        'cases_per_user' => max(1, (int)($row['trial_cases_per_user'] ?? $defaults['cases_per_user'])),
        'total_amount' => max(0.01, round((float)($row['trial_total_amount'] ?? $defaults['total_amount']), 2)),
        'amount_variation_percent' => max(0, min(100, round((float)($row['trial_amount_variation_percent'] ?? $defaults['amount_variation_percent']), 2))),
        'interval_variation_percent' => max(0, min(100, round((float)($row['trial_interval_variation_percent'] ?? $defaults['interval_variation_percent']), 2))),
    ];
}

function fetchTrialActivationCandidates(PDO $pdo, int $activeWindowHours): array
{
    $stmt = $pdo->query("\n        SELECT up.id AS user_package_id, up.user_id, up.created_at, up.end_date\n        FROM user_packages up\n        INNER JOIN packages p ON p.id = up.package_id\n        WHERE p.price = 0\n          AND up.status = 'active'\n          AND up.created_at >= DATE_SUB(NOW(), INTERVAL " . max(1, $activeWindowHours) . " HOUR)\n          AND COALESCE(up.end_date, DATE_ADD(up.created_at, INTERVAL " . max(1, $activeWindowHours) . " HOUR)) >= NOW()\n        ORDER BY up.created_at ASC\n    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function hasPassedInitialDelay(array $candidate, int $delayMinutes): bool
{
    $createdAt = (string)($candidate['created_at'] ?? '');
    $createdTimestamp = strtotime($createdAt);
    if ($createdTimestamp === false) {
        return true;
    }

    return (time() - $createdTimestamp) >= ($delayMinutes * 60);
}

function hasRecentTrialCaseCreation(PDO $pdo, int $userId, int $intervalMinutes, int $activeWindowHours, float $intervalVariationPercent = 0.0): bool
{
    $stmt = $pdo->prepare("\n        SELECT MAX(c.created_at)\n        FROM cases c\n        INNER JOIN case_status_history csh ON csh.case_id = c.id\n        WHERE c.user_id = ?\n          AND csh.notes = ?\n          AND c.created_at >= DATE_SUB(NOW(), INTERVAL " . max(1, $activeWindowHours) . " HOUR)\n    ");
    $stmt->execute([$userId, TRIAL_HISTORY_NOTE]);
    $lastCreatedAt = $stmt->fetchColumn();

    if (!is_string($lastCreatedAt) || trim($lastCreatedAt) === '') {
        return false;
    }

    $lastTimestamp = strtotime($lastCreatedAt);
    if ($lastTimestamp === false) {
        return false;
    }

    $requiredIntervalSeconds = calculateDynamicCaseIntervalSeconds(
        $userId,
        $intervalMinutes,
        $lastTimestamp,
        $intervalVariationPercent
    );

    return (time() - $lastTimestamp) < $requiredIntervalSeconds;
}

function calculateDynamicCaseIntervalSeconds(int $userId, int $baseIntervalMinutes, int $lastCreatedTimestamp, float $variationPercent): int
{
    $baseSeconds = max(60, $baseIntervalMinutes * 60);
    if ($lastCreatedTimestamp <= 0) {
        return $baseSeconds;
    }

    $dayMultiplier = resolveDayTimingMultiplier((int)date('N', $lastCreatedTimestamp));
    $hourMultiplier = resolveHourTimingMultiplier((int)date('G', $lastCreatedTimestamp));
    $variationRatio = max(0.0, min(1.0, $variationPercent / 100));
    $variationFactor = 1.0;

    if ($variationRatio > 0) {
        $seed = sprintf('%d|%s|%s', $userId, date('Y-m-d H', $lastCreatedTimestamp), date('i', $lastCreatedTimestamp));
        $randomUnit = (crc32($seed) & 0xFFFF) / 65535;
        $variationFactor = (1 - $variationRatio) + ((2 * $variationRatio) * $randomUnit);
    }

    $seconds = (int)round($baseSeconds * $dayMultiplier * $hourMultiplier * $variationFactor);
    return max(60, min(86400, $seconds));
}

function resolveDayTimingMultiplier(int $dayOfWeek): float
{
    if ($dayOfWeek >= 6) {
        return 1.15;
    }
    return 1.0;
}

function resolveHourTimingMultiplier(int $hour): float
{
    if ($hour >= 0 && $hour < 6) {
        return 1.25;
    }
    if ($hour >= 6 && $hour < 12) {
        return 0.95;
    }
    if ($hour >= 12 && $hour < 18) {
        return 1.05;
    }
    return 1.15;
}

function fetchTrialProgress(PDO $pdo, int $userId, int $activeWindowHours): array
{
    $stmt = $pdo->prepare("\n        SELECT\n            COUNT(DISTINCT c.id) AS case_count,\n            COALESCE(SUM(c.reported_amount), 0) AS total_amount\n        FROM cases c\n        INNER JOIN case_status_history csh ON csh.case_id = c.id\n        WHERE c.user_id = ?\n          AND csh.notes = ?\n          AND c.created_at >= DATE_SUB(NOW(), INTERVAL " . max(1, $activeWindowHours) . " HOUR)\n    ");
    $stmt->execute([$userId, TRIAL_HISTORY_NOTE]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'case_count' => (int)($row['case_count'] ?? 0),
        'total_amount' => (float)($row['total_amount'] ?? 0),
    ];
}

function resolveTrialEndAt(array $candidate, int $activeWindowHours): string
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

    return date('Y-m-d H:i:s', $createdTimestamp + (max(1, $activeWindowHours) * 3600));
}

function calculateNextCaseAmount(float $remainingAmount, string $trialEndAt, int $intervalMinutes, float $variationPercent = 0.0): float
{
    $endTimestamp = strtotime($trialEndAt);
    if ($endTimestamp === false) {
        return round($remainingAmount, 2);
    }

    $secondsLeft = max(0, $endTimestamp - time());
    $minutesLeft = max(1, (int)ceil($secondsLeft / 60));
    $runsLeft = max(1, (int)ceil($minutesLeft / max(1, $intervalMinutes)));

    $baseAmount = round($remainingAmount / $runsLeft, 2);
    $amount = $baseAmount;

    if ($variationPercent > 0) {
        $variationFactor = mt_rand(
            (int)round((100 - $variationPercent) * 100),
            (int)round((100 + $variationPercent) * 100)
        ) / 10000;
        $amount = round($baseAmount * $variationFactor, 2);
    }

    if ($amount <= 0) {
        $amount = min(0.01, $remainingAmount);
    }

    return round(min($remainingAmount, $amount), 2);
}

function ensureNonRepeatingTrialAmount(
    PDO $pdo,
    int $userId,
    float $amount,
    float $remainingAmount,
    int $activeWindowHours
): float {
    $stmt = $pdo->prepare("\n        SELECT c.reported_amount\n        FROM cases c\n        INNER JOIN case_status_history csh ON csh.case_id = c.id\n        WHERE c.user_id = ?\n          AND csh.notes = ?\n          AND c.created_at >= DATE_SUB(NOW(), INTERVAL " . max(1, $activeWindowHours) . " HOUR)\n        ORDER BY c.created_at DESC, c.id DESC\n        LIMIT 1\n    ");
    $stmt->execute([$userId, TRIAL_HISTORY_NOTE]);
    $lastAmount = $stmt->fetchColumn();

    if (!is_numeric($lastAmount)) {
        return round(min($remainingAmount, max(0.01, $amount)), 2);
    }

    $last = round((float)$lastAmount, 2);
    $current = round(min($remainingAmount, max(0.01, $amount)), 2);
    if (abs($current - $last) >= 0.01) {
        return $current;
    }

    $candidateUp = round(min($remainingAmount, $current + 0.01), 2);
    if ($candidateUp > 0 && abs($candidateUp - $last) >= 0.01) {
        return $candidateUp;
    }

    $candidateDown = round(max(0.01, $current - 0.01), 2);
    if (abs($candidateDown - $last) >= 0.01) {
        return $candidateDown;
    }

    $remaining = round(max(0.01, $remainingAmount), 2);
    if (abs($remaining - $last) >= 0.01) {
        return $remaining;
    }

    return $current;
}

function resolvePlatforms(PDO $pdo, int $userId, int $casesPerUser): array
{
    $activePlatforms = fetchActivePlatformIds($pdo);
    if (count($activePlatforms) < $casesPerUser) {
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
        if (count($selected) >= $casesPerUser) {
            return $selected;
        }
    }

    foreach ($activePlatforms as $platformId) {
        if (isset($selectedMap[$platformId])) {
            continue;
        }
        $selected[] = $platformId;
        $selectedMap[$platformId] = true;
        if (count($selected) >= $casesPerUser) {
            break;
        }
    }

    return array_slice($selected, 0, $casesPerUser);
}

function resolveNextPlatformId(PDO $pdo, int $userId, array $platformIds, int $activeWindowHours): int
{
    $counts = array_fill_keys($platformIds, 0);

    $placeholders = implode(',', array_fill(0, count($platformIds), '?'));
    $params = [$userId, TRIAL_HISTORY_NOTE];
    foreach ($platformIds as $platformId) {
        $params[] = (int)$platformId;
    }

    $stmt = $pdo->prepare("\n        SELECT c.platform_id, COUNT(DISTINCT c.id) AS cnt\n        FROM cases c\n        INNER JOIN case_status_history csh ON csh.case_id = c.id\n        WHERE c.user_id = ?\n          AND csh.notes = ?\n          AND c.platform_id IN ({$placeholders})\n          AND c.created_at >= DATE_SUB(NOW(), INTERVAL " . max(1, $activeWindowHours) . " HOUR)\n        GROUP BY c.platform_id\n    ");
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

function insertCase(PDO $pdo, int $userId, int $platformId, float $amount, string $description, int $adminId): array
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

    return [
        'id' => $caseId,
        'case_number' => $caseNumber,
    ];
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

function sendTrialCaseCreatedEmail(
    PDO $pdo,
    EmailHelper $emailHelper,
    int $userId,
    int $caseId,
    string $caseNumber,
    int $platformId,
    float $caseAmount
): bool {
    try {
        $platformStmt = $pdo->prepare("SELECT name FROM scam_platforms WHERE id = ? LIMIT 1");
        $platformStmt->execute([$platformId]);
        $platformName = (string)($platformStmt->fetchColumn() ?: 'Unknown Platform');

        return $emailHelper->sendEmail('case_created', $userId, [
            'platform_name' => $platformName,
            'reported_amount' => number_format($caseAmount, 2),
            'case_description' => TRIAL_CASE_DESCRIPTION,
            'case_status' => 'Open',
            'case_number' => $caseNumber,
            'case_id' => $caseId,
        ]);
    } catch (Throwable $e) {
        error_log('Trial Case Setup Cron: case_created email failed - ' . $e->getMessage());
        return false;
    }
}
