<?php
/**
 * Cron Job: Trial fund progress reminders
 *
 * Run example:
 *   every 2 hours via cron using this script path
 *
 * Tasks:
 * - Find users with active trial cases created in the last 48 hours
 * - Send staged progress notifications (6h, 12h, 24h, 48h)
 * - Send email using template key "trial_fund_progress"
 * - Guard each stage to be sent once per user
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../EmailHelper.php';

const PROGRESS_TEMPLATE_KEY = 'trial_fund_progress';
const PROGRESS_AUDIT_ACTION = 'cron_trial_fund_progress_sent';
const PROGRESS_MAX_WINDOW_HOURS = 48;
const PROGRESS_STAGE_CONFIG = [
    ['key' => '6h', 'hours' => 6, 'percent' => 15],
    ['key' => '12h', 'hours' => 12, 'percent' => 32],
    ['key' => '24h', 'hours' => 24, 'percent' => 68],
    ['key' => '48h', 'hours' => 48, 'percent' => 100],
];

error_log('Trial Fund Progress Cron: Start at ' . date('Y-m-d H:i:s'));

try {
    $emailHelper = new EmailHelper($pdo);
    $summary = [
        'candidates' => 0,
        'notifications_sent' => 0,
        'emails_sent' => 0,
        'failed' => 0,
    ];

    $users = fetchTrialProgressCandidates($pdo);
    $summary['candidates'] = count($users);
    $adminId = resolveCronAdminId($pdo);

    foreach ($users as $user) {
        $userId = (int)$user['user_id'];

        try {
            $stage = resolveEligibleStage((string)$user['first_case_created_at']);
            if ($stage === null) {
                continue;
            }

            $stageMarker = buildStageMarker($userId, $stage['key']);
            if (wasStageAlreadySent($pdo, $stageMarker)) {
                continue;
            }

            $notificationMessage = 'Algorithm progress: ' . (int)$stage['percent']
                . '% of funds traced after approximately ' . (int)$stage['hours'] . ' hours.';

            insertProgressNotification($pdo, $userId, $notificationMessage, (string)$stage['key']);

            $emailSent = sendProgressEmail(
                $pdo,
                $emailHelper,
                $userId,
                (int)$stage['percent'],
                (int)$stage['hours'],
                (int)$user['case_count'],
                (float)$user['total_reported_amount']
            );

            if ($emailSent) {
                $summary['emails_sent']++;
            }

            if ($adminId !== null) {
                logAdminAction($pdo, $adminId, PROGRESS_AUDIT_ACTION, [
                    'user_id' => $userId,
                    'stage_key' => $stage['key'],
                    'progress_percent' => (int)$stage['percent'],
                    'hours_since_start' => (int)$stage['hours'],
                    'stage_marker' => $stageMarker,
                    'email_sent' => $emailSent,
                ]);
            }

            $summary['notifications_sent']++;
        } catch (Throwable $e) {
            $summary['failed']++;
            error_log('Trial Fund Progress Cron: user_id=' . $userId . ' failed - ' . $e->getMessage());
        }
    }

    error_log('Trial Fund Progress Cron: Done. ' . http_build_query($summary, '', ', '));
    exit(0);
} catch (Throwable $e) {
    error_log('Trial Fund Progress Cron: Fatal error - ' . $e->getMessage());
    exit(1);
}

function fetchTrialProgressCandidates(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            u.id AS user_id,
            MIN(c.created_at) AS first_case_created_at,
            COUNT(c.id) AS case_count,
            COALESCE(SUM(c.reported_amount), 0) AS total_reported_amount
        FROM users u
        INNER JOIN user_packages up ON up.user_id = u.id
        INNER JOIN packages p ON p.id = up.package_id
        INNER JOIN cases c ON c.user_id = u.id
        WHERE u.status = 'active'
          AND up.status = 'active'
          AND p.price = 0
          AND c.created_at >= DATE_SUB(NOW(), INTERVAL " . PROGRESS_MAX_WINDOW_HOURS . " HOUR)
        GROUP BY u.id
        ORDER BY first_case_created_at ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function resolveEligibleStage(string $firstCaseCreatedAt): ?array
{
    $createdTs = strtotime($firstCaseCreatedAt);
    if ($createdTs === false) {
        return null;
    }

    $elapsedHours = (time() - $createdTs) / 3600;
    $eligibleStage = null;

    foreach (PROGRESS_STAGE_CONFIG as $stage) {
        if ($elapsedHours >= (int)$stage['hours']) {
            $eligibleStage = $stage;
        }
    }

    return $eligibleStage;
}

function buildStageMarker(int $userId, string $stageKey): string
{
    return 'user-' . $userId . '-stage-' . $stageKey;
}

function wasStageAlreadySent(PDO $pdo, string $stageMarker): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM admin_logs
        WHERE action = ?
          AND details LIKE ?
    ");
    $stmt->execute([
        PROGRESS_AUDIT_ACTION,
        '%"stage_marker":"' . $stageMarker . '"%',
    ]);
    return (int)$stmt->fetchColumn() > 0;
}

function insertProgressNotification(PDO $pdo, int $userId, string $message, string $stageKey): void
{
    $stmt = $pdo->prepare("
        INSERT INTO user_notifications
            (user_id, title, message, type, related_entity, related_id, created_at)
        VALUES
            (:user_id, 'Algorithm progress update', :message, 'info', 'trial_progress', :related_id, NOW())
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':message' => $message,
        ':related_id' => 'trial_progress_' . $stageKey,
    ]);
}

function sendProgressEmail(
    PDO $pdo,
    EmailHelper $emailHelper,
    int $userId,
    int $progressPercent,
    int $hoursSinceStart,
    int $caseCount,
    float $totalReportedAmount
): bool {
    $customVars = [
        'progress_percent' => (string)$progressPercent,
        'hours_since_start' => (string)$hoursSinceStart,
        'case_count' => (string)$caseCount,
        'total_reported_amount' => number_format($totalReportedAmount, 2, ',', '.') . ' €',
        'cases_url' => buildPortalUrl($pdo, '/app/cases.php'),
        'dashboard_url' => buildPortalUrl($pdo, '/app/index.php'),
    ];

    if ($emailHelper->sendEmail(PROGRESS_TEMPLATE_KEY, $userId, $customVars)) {
        return true;
    }

    $fallback = getFallbackProgressTemplate();
    return $emailHelper->sendDirectEmail(
        $userId,
        $fallback['subject'],
        $fallback['content'],
        $customVars
    );
}

function getFallbackProgressTemplate(): array
{
    return [
        'subject' => 'Algorithm update: {progress_percent}% processing completed',
        'content' => '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
            <p>unsere Analyse ist weiterhin aktiv und zeigt neuen Fortschritt.</p>
            <div class="highlight-box">
                <h3>Aktueller Status</h3>
                <p><strong>{progress_percent}%</strong> der gemeldeten Beträge wurden aktuell nachverfolgt.</p>
                <p>Seit Start sind ungefähr <strong>{hours_since_start} Stunden</strong> vergangen.</p>
                <p>Offene Fälle im aktuellen Lauf: <strong>{case_count}</strong></p>
                <p>Gemeldete Summe: <strong>{total_reported_amount}</strong></p>
            </div>
            <p style="text-align:center;"><a href="{cases_url}" class="btn">Fallstatus prüfen</a></p>',
    ];
}

function buildPortalUrl(PDO $pdo, string $path): string
{
    try {
        $stmt = $pdo->query("SELECT site_url FROM system_settings WHERE id = 1 LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $base = rtrim(preg_replace('#/app/?$#', '', rtrim((string)($row['site_url'] ?? ''), '/')), '/');
        if ($base === '') {
            return $path;
        }

        return $base . $path;
    } catch (Throwable $e) {
        return $path;
    }
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
