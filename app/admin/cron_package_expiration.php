<?php
/**
 * Cron Job: 48h Trial Expiration & Subscription Enforcement
 *
 * Run example:
 *   0 * * * * /usr/bin/php /path/to/app/admin/cron_package_expiration.php
 *
 * Tasks:
 * 1) Find expired 48h trial packages (price = 0, end_date < NOW(), status active|pending)
 * 2) Mark those trial packages as expired
 * 3) Send professional German email notification with:
 *    - trial expiration notice
 *    - balance / withdrawal amount information
 *    - paid package overview
 *    - 3-day deactivation warning + support ticket hint
 * 4) Deactivate users (users.status = inactive) if 3 days passed after trial expiry
 *    and no active paid package exists
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../EmailHelper.php';

const TRIAL_DEACTIVATION_GRACE_DAYS = 3;
const TRIAL_EMAIL_TEMPLATE_KEY = 'trial_expired_subscription_required';
const TRIAL_EMAIL_FALLBACK_TEMPLATE_KEY = 'trial_end';

error_log('Trial Expiration Cron: Start at ' . date('Y-m-d H:i:s'));

try {
    $emailHelper = new EmailHelper($pdo);
    $packageOverviewHtml = buildPaidPackageOverview($pdo);

    $expiredTrials = fetchExpiredActiveTrials($pdo);
    $expiredCount = count($expiredTrials);
    error_log("Trial Expiration Cron: Found {$expiredCount} expired active/pending trial package(s)");

    $updatedCount = 0;
    $emailSentCount = 0;

    if ($expiredCount > 0) {
        $updateStmt = $pdo->prepare("
            UPDATE user_packages
            SET status = 'expired', updated_at = NOW()
            WHERE id = ?
        ");

        foreach ($expiredTrials as $trial) {
            try {
                $updateStmt->execute([(int)$trial['user_package_id']]);
                $updatedCount++;

                $balanceAmount = (float)($trial['balance'] ?? 0);
                $formattedAmount = number_format($balanceAmount, 2, ',', '.') . ' €';
                $deactivationDeadline = date(
                    'd.m.Y H:i',
                    strtotime((string)$trial['end_date'] . ' +' . TRIAL_DEACTIVATION_GRACE_DAYS . ' days')
                );

                $emailSent = $emailHelper->sendEmail(
                    TRIAL_EMAIL_TEMPLATE_KEY,
                    (int)$trial['user_id'],
                    [
                        'package_name' => (string)$trial['package_name'],
                        'trial_end_date' => date('d.m.Y H:i', strtotime((string)$trial['end_date'])),
                        'balance' => $formattedAmount,
                        'withdrawal_amount' => $formattedAmount,
                        'grace_days' => (string)TRIAL_DEACTIVATION_GRACE_DAYS,
                        'deactivation_deadline' => $deactivationDeadline,
                        'package_overview' => $packageOverviewHtml,
                        'packages_url' => buildPortalUrl($pdo, '/app/packages.php'),
                        'support_url' => buildPortalUrl($pdo, '/app/support.php'),
                    ]
                );

                if (!$emailSent) {
                    $emailSent = $emailHelper->sendEmail(
                        TRIAL_EMAIL_FALLBACK_TEMPLATE_KEY,
                        (int)$trial['user_id'],
                        [
                            'package_name' => (string)$trial['package_name'],
                            'trial_end_date' => date('d.m.Y H:i', strtotime((string)$trial['end_date'])),
                            'balance' => $formattedAmount,
                            'withdrawal_amount' => $formattedAmount,
                            'grace_days' => (string)TRIAL_DEACTIVATION_GRACE_DAYS,
                            'deactivation_deadline' => $deactivationDeadline,
                            'package_overview' => $packageOverviewHtml,
                            'packages_url' => buildPortalUrl($pdo, '/app/packages.php'),
                            'support_url' => buildPortalUrl($pdo, '/app/support.php'),
                        ]
                    );
                }

                if ($emailSent) {
                    $emailSentCount++;
                } else {
                    error_log(
                        "Trial Expiration Cron: Email send failed for user_id={$trial['user_id']} ({$trial['email']})"
                    );
                }

                logAdminAction($pdo, 'trial_package_expired', [
                    'user_package_id' => (int)$trial['user_package_id'],
                    'user_id' => (int)$trial['user_id'],
                    'user_email' => (string)$trial['email'],
                    'package_name' => (string)$trial['package_name'],
                    'previous_status' => (string)$trial['status'],
                    'new_status' => 'expired',
                    'trial_end_date' => (string)$trial['end_date'],
                    'cron' => 'cron_package_expiration',
                ]);
            } catch (Throwable $e) {
                error_log(
                    "Trial Expiration Cron: Failed processing trial user_package_id={$trial['user_package_id']} - "
                    . $e->getMessage()
                );
            }
        }
    }

    $deactivateCandidates = fetchUsersForDeactivation($pdo);
    $deactivateCount = 0;
    if (!empty($deactivateCandidates)) {
        $deactivateStmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ? AND status = 'active'");
        foreach ($deactivateCandidates as $candidate) {
            try {
                $deactivateStmt->execute([(int)$candidate['user_id']]);
                if ($deactivateStmt->rowCount() > 0) {
                    $deactivateCount++;
                    logAdminAction($pdo, 'user_auto_deactivated_trial_expired', [
                        'user_id' => (int)$candidate['user_id'],
                        'user_email' => (string)$candidate['email'],
                        'last_trial_end_date' => (string)$candidate['last_trial_end_date'],
                        'grace_days' => TRIAL_DEACTIVATION_GRACE_DAYS,
                        'new_status' => 'suspended',
                        'cron' => 'cron_package_expiration',
                    ]);
                }
            } catch (Throwable $e) {
                error_log(
                    "Trial Expiration Cron: Failed to deactivate user_id={$candidate['user_id']} - " . $e->getMessage()
                );
            }
        }
    }

    error_log(
        "Trial Expiration Cron: Done. expired_updated={$updatedCount}, emails_sent={$emailSentCount}, "
        . "users_deactivated={$deactivateCount}"
    );
    exit(0);
} catch (Throwable $e) {
    error_log('Trial Expiration Cron: Fatal error - ' . $e->getMessage());
    exit(1);
}

/**
 * Get expired trial packages that are still active/pending.
 */
function fetchExpiredActiveTrials(PDO $pdo): array
{
    $sql = "
        SELECT
            up.id AS user_package_id,
            up.user_id,
            up.status,
            up.start_date,
            up.end_date,
            u.email,
            u.first_name,
            u.last_name,
            u.balance,
            p.id AS package_id,
            p.name AS package_name,
            p.price
        FROM user_packages up
        INNER JOIN users u ON u.id = up.user_id
        INNER JOIN packages p ON p.id = up.package_id
        WHERE up.status IN ('active', 'pending')
          AND p.price = 0
          AND up.end_date IS NOT NULL
          AND up.end_date < NOW()
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Build paid package list HTML to be inserted in the email template.
 */
function buildPaidPackageOverview(PDO $pdo): string
{
    try {
        $stmt = $pdo->query("
            SELECT name, price, duration_days
            FROM packages
            WHERE price > 0
            ORDER BY price ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (!$rows) {
            return '<p>Aktuell sind keine kostenpflichtigen Pakete hinterlegt. Bitte kontaktieren Sie den Support.</p>';
        }

        $listItems = [];
        foreach ($rows as $row) {
            $name = htmlspecialchars((string)($row['name'] ?? 'Paket'), ENT_QUOTES, 'UTF-8');
            $price = number_format((float)($row['price'] ?? 0), 2, ',', '.');
            $days = (int)($row['duration_days'] ?? 0);
            $durationText = $days > 0 ? " ({$days} Tage)" : '';
            $listItems[] = "<li><strong>{$name}</strong> – {$price} €{$durationText}</li>";
        }

        return '<ul>' . implode('', $listItems) . '</ul>';
    } catch (Throwable $e) {
        error_log('Trial Expiration Cron: Failed to load paid package overview - ' . $e->getMessage());
        return '<p>Paketübersicht konnte derzeit nicht geladen werden. Bitte besuchen Sie den Paketbereich im Portal.</p>';
    }
}

/**
 * Users to deactivate:
 * - currently active
 * - trial expired for at least grace period
 * - no active paid package
 */
function fetchUsersForDeactivation(PDO $pdo): array
{
    $sql = "
        SELECT
            u.id AS user_id,
            u.email,
            MAX(up.end_date) AS last_trial_end_date
        FROM users u
        INNER JOIN user_packages up ON up.user_id = u.id
        INNER JOIN packages p ON p.id = up.package_id
        WHERE u.status = 'active'
          AND up.status = 'expired'
          AND p.price = 0
          AND up.end_date IS NOT NULL
          AND DATE_ADD(up.end_date, INTERVAL " . TRIAL_DEACTIVATION_GRACE_DAYS . " DAY) <= NOW()
          AND NOT EXISTS (
              SELECT 1
              FROM user_packages up2
              INNER JOIN packages p2 ON p2.id = up2.package_id
              WHERE up2.user_id = u.id
                AND up2.status = 'active'
                AND p2.price > 0
                AND (up2.end_date IS NULL OR up2.end_date >= NOW())
          )
        GROUP BY u.id, u.email
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Build full portal URL from system_settings.site_url.
 */
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

/**
 * Best-effort admin log entry.
 */
function logAdminAction(PDO $pdo, string $action, array $details): void
{
    try {
        $adminId = resolveCronAdminId($pdo);
        if ($adminId === null) {
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, details, ip_address, created_at)
            VALUES (?, ?, ?, '127.0.0.1', NOW())
        ");
        $stmt->execute([$adminId, $action, json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    } catch (Throwable $e) {
        error_log("Trial Expiration Cron: admin_logs insert failed for action {$action} - " . $e->getMessage());
    }
}

/**
 * Resolve an existing admin ID for cron-created admin_logs rows.
 */
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
        $resolvedAdminId = $stmt->fetchColumn();
        if ($resolvedAdminId === false) {
            $stmt = $pdo->query("SELECT id FROM admins ORDER BY id ASC LIMIT 1");
            $resolvedAdminId = $stmt->fetchColumn();
        }

        $adminId = $resolvedAdminId === false ? null : (int)$resolvedAdminId;
    } catch (Throwable $e) {
        $adminId = null;
    }

    return $adminId;
}
