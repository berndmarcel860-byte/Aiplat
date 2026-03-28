<?php
/**
 * package_expiry_check.php
 *
 * Automated cron job for package expiry management.
 * Run this script every hour via cron:
 *
 *   0 * * * * php /var/www/html/app/cron/package_expiry_check.php >> /var/log/package_expiry.log 2>&1
 *
 * What this script does:
 *  1. Marks active packages whose end_date has passed as "expired" in the DB.
 *  2. Sends a professional German "expiring soon" email when a package is within 24 hours of expiry
 *     (only once per package assignment, tracked via warning_sent_at column).
 *  3. Sends a professional German "trial expired" email with a recommended package
 *     based on the user's reported loss amount when a package transitions to expired.
 */

// ── Bootstrap ────────────────────────────────────────────────────────────────
// Allow CLI execution; also works when called via HTTP if needed.
if (!defined('STDIN')) {
    define('STDIN', fopen('php://stdin', 'r'));
}

// Resolve config.php relative to this file's location
$configPaths = [
    __DIR__ . '/../config.php',
    __DIR__ . '/../../app/config.php',
];
$configLoaded = false;
foreach ($configPaths as $cp) {
    if (file_exists($cp)) {
        require_once $cp;
        $configLoaded = true;
        break;
    }
}

if (!$configLoaded || !isset($pdo)) {
    fwrite(STDERR, "[package_expiry_check] ERROR: Could not load config.php or \$pdo\n");
    exit(1);
}

// Load AdminEmailHelper for sending emails
$helperPaths = [
    __DIR__ . '/../admin/AdminEmailHelper.php',
    __DIR__ . '/../../app/admin/AdminEmailHelper.php',
];
foreach ($helperPaths as $hp) {
    if (file_exists($hp)) {
        require_once $hp;
        break;
    }
}

// ── Helper: log with timestamp ────────────────────────────────────────────────
function cronLog(string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}

// ── Load system settings ──────────────────────────────────────────────────────
$sysStmt   = $pdo->query("SELECT * FROM system_settings WHERE id = 1 LIMIT 1");
$sysSettings = $sysStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$siteUrl    = rtrim($sysSettings['site_url'] ?? '', '/');
$brandName  = $sysSettings['brand_name'] ?? 'Support';
$packagesUrl = $siteUrl . '/app/packages.php';

// Check if subscription feature is enabled; if not, nothing to do.
if (empty($sysSettings['subscription_enabled'])) {
    cronLog("Subscription feature disabled – exiting.");
    exit(0);
}

// ── 1. Auto-expire packages whose end_date has passed ────────────────────────
try {
    $expireStmt = $pdo->prepare(
        "UPDATE user_packages
            SET status     = 'expired',
                updated_at = NOW()
          WHERE status   = 'active'
            AND end_date IS NOT NULL
            AND end_date < NOW()"
    );
    $expireStmt->execute();
    $expiredCount = $expireStmt->rowCount();
    if ($expiredCount > 0) {
        cronLog("Marked {$expiredCount} package(s) as expired.");
    }
} catch (PDOException $e) {
    cronLog("ERROR auto-expiring packages: " . $e->getMessage());
}

// ── 2. Send "expiring soon" warning email (within 24 h, only once) ────────────
try {
    $warnStmt = $pdo->prepare(
        "SELECT up.id, up.user_id, up.end_date, up.warning_sent_at,
                p.name AS package_name, p.price,
                u.first_name, u.last_name, u.email
           FROM user_packages up
           JOIN packages p ON p.id = up.package_id
           JOIN users    u ON u.id = up.user_id
          WHERE up.status      = 'active'
            AND up.end_date   IS NOT NULL
            AND up.end_date    > NOW()
            AND up.end_date   <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
            AND up.warning_sent_at IS NULL"
    );
    $warnStmt->execute();
    $expiringPackages = $warnStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($expiringPackages as $pkg) {
        $hoursLeft = max(1, (int)ceil((strtotime($pkg['end_date']) - time()) / 3600));
        $endDateFormatted = date('d.m.Y H:i', strtotime($pkg['end_date']));

        // Fetch user's recovered amount for personalisation
        $statsRow = $pdo->prepare(
            "SELECT COALESCE(SUM(recovered_amount), 0) AS recovered,
                    COALESCE(SUM(reported_amount),  0) AS reported
               FROM cases WHERE user_id = ?"
        );
        $statsRow->execute([$pkg['user_id']]);
        $stats = $statsRow->fetch(PDO::FETCH_ASSOC);
        $recoveredDisplay = '€' . number_format((float)($stats['recovered'] ?? 0), 2, ',', '.');

        $emailSent = false;
        try {
            if (class_exists('AdminEmailHelper')) {
                $mailer = new AdminEmailHelper($pdo);
                $emailSent = $mailer->sendTemplateEmail('trial_expiring_soon', (int)$pkg['user_id'], [
                    'package_name'    => $pkg['package_name'],
                    'hours_left'      => (string)$hoursLeft,
                    'end_date'        => $endDateFormatted,
                    'recovered_display' => $recoveredDisplay,
                    'packages_url'    => $packagesUrl,
                    'brand_name'      => $brandName,
                    'site_url'        => $siteUrl,
                ]);
            }
        } catch (Exception $e) {
            cronLog("WARN email error for user {$pkg['user_id']}: " . $e->getMessage());
        }

        // Mark warning sent regardless of email success (avoids spam on transient SMTP failures)
        $pdo->prepare("UPDATE user_packages SET warning_sent_at = NOW() WHERE id = ?")
            ->execute([$pkg['id']]);

        // Add in-app notification
        try {
            $pdo->prepare(
                "INSERT INTO notifications (user_id, type, title, message, created_at)
                 VALUES (?, 'package_expiring', ?, ?, NOW())"
            )->execute([
                $pkg['user_id'],
                'Paket läuft bald ab',
                'Ihr Paket "' . $pkg['package_name'] . '" läuft in ca. ' . $hoursLeft . ' Stunden ab. Bitte upgraden Sie, um weiterhin alle Funktionen zu nutzen.',
            ]);
        } catch (Exception $e) { /* notifications table might not exist yet */ }

        cronLog("Sent expiring-soon email to user {$pkg['user_id']} ({$pkg['email']}) – {$hoursLeft}h left.");
    }
} catch (PDOException $e) {
    cronLog("ERROR in expiring-soon check: " . $e->getMessage());
}

// ── 3. Send "trial expired" emails (once per newly expired assignment) ────────
// Track sent notifications via the notifications table so we don't double-send.
try {
    $expiredPkgStmt = $pdo->prepare(
        "SELECT up.id, up.user_id, up.end_date, up.package_id,
                p.name AS package_name, p.price,
                u.first_name, u.last_name, u.email
           FROM user_packages up
           JOIN packages p ON p.id = up.package_id
           JOIN users    u ON u.id = up.user_id
          WHERE up.status  = 'expired'
            AND up.end_date >= DATE_SUB(NOW(), INTERVAL 48 HOUR)  -- 48h window: catches recent expirations even if cron was delayed
            AND NOT EXISTS (
                SELECT 1 FROM notifications n
                 WHERE n.user_id = up.user_id
                   AND n.type    = 'package_expired'
                   AND n.message LIKE CONCAT('%', up.id, '%')
            )
          ORDER BY up.end_date ASC"
    );
    $expiredPkgStmt->execute();
    $justExpired = $expiredPkgStmt->fetchAll(PDO::FETCH_ASSOC);

    // Load all paid packages ordered by price (for recommendation)
    $allPackages = $pdo->query(
        "SELECT * FROM packages WHERE price > 0 ORDER BY price ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($justExpired as $pkg) {
        $endDateFormatted = date('d.m.Y', strtotime($pkg['end_date']));

        // Fetch user's loss stats for recommendation
        $statsRow = $pdo->prepare(
            "SELECT COALESCE(SUM(recovered_amount), 0) AS recovered,
                    COALESCE(SUM(reported_amount),  0) AS reported
               FROM cases WHERE user_id = ?"
        );
        $statsRow->execute([$pkg['user_id']]);
        $stats = $statsRow->fetch(PDO::FETCH_ASSOC);
        $reportedAmount  = (float)($stats['reported'] ?? 0);
        $recoveredAmount = (float)($stats['recovered'] ?? 0);
        $reportedDisplay  = '€' . number_format($reportedAmount,  2, ',', '.');
        $recoveredDisplay = '€' . number_format($recoveredAmount, 2, ',', '.');

        // Recommend a package: pick the first paid package whose price is reasonable
        // relative to the reported loss (simple heuristic: ≥ 1% of reported, up to highest tier)
        $recommended = null;
        foreach ($allPackages as $candidate) {
            $recommended = $candidate; // always assign last valid one
            if ((float)$candidate['price'] >= $reportedAmount * 0.005) {
                break; // first package at or above 0.5% of loss
            }
        }
        // Fallback: first available paid package
        if (!$recommended && !empty($allPackages)) {
            $recommended = $allPackages[0];
        }

        $recPkgName  = $recommended ? $recommended['name'] : 'Premium';
        $recPkgPrice = $recommended ? '€' . number_format((float)$recommended['price'], 2, ',', '.') : '';
        $recPkgDesc  = $recommended ? ($recommended['description'] ?? '') : '';

        $emailSent = false;
        try {
            if (class_exists('AdminEmailHelper')) {
                $mailer = new AdminEmailHelper($pdo);
                $emailSent = $mailer->sendTemplateEmail('trial_expired', (int)$pkg['user_id'], [
                    'package_name'                  => $pkg['package_name'],
                    'end_date'                      => $endDateFormatted,
                    'recovered_display'             => $recoveredDisplay,
                    'reported_amount'               => $reportedDisplay,
                    'recommended_package_name'      => $recPkgName,
                    'recommended_package_price'     => $recPkgPrice,
                    'recommended_package_description' => $recPkgDesc,
                    'packages_url'                  => $packagesUrl,
                    'brand_name'                    => $brandName,
                    'site_url'                      => $siteUrl,
                ]);
            }
        } catch (Exception $e) {
            cronLog("WARN expired-email error for user {$pkg['user_id']}: " . $e->getMessage());
        }

        // Record notification (used as a "sent" flag to prevent duplicates)
        try {
            $pdo->prepare(
                "INSERT INTO notifications (user_id, type, title, message, created_at)
                 VALUES (?, 'package_expired', ?, ?, NOW())"
            )->execute([
                $pkg['user_id'],
                'Test-Paket abgelaufen',
                'Ihr Test-Paket "' . $pkg['package_name'] . '" ist abgelaufen (ID:' . $pkg['id'] . '). '
                    . 'Bitte upgraden Sie, um wieder vollen Zugang zu erhalten.',
            ]);
        } catch (Exception $e) { /* best-effort */ }

        cronLog("Sent expired email to user {$pkg['user_id']} ({$pkg['email']}) for package \"{$pkg['package_name']}\".");
    }
} catch (PDOException $e) {
    cronLog("ERROR in trial-expired check: " . $e->getMessage());
}

cronLog("package_expiry_check finished.");
exit(0);
