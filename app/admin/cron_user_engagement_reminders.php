<?php
/**
 * Cron Job: Automated user engagement reminders
 *
 * Run example:
 *   0 * * * * /usr/bin/php /path/to/app/admin/cron_user_engagement_reminders.php
 *
 * Tasks:
 * 1) Remind active users after 3 days if they registered but never logged in
 * 2) Suspend users after 8 days if they still never logged in
 * 3) Remind active users after 5 days of inactivity and include reported case amount when available
 * 4) Remind active verified users without approved KYC that withdrawals stay unavailable until verification
 * 5) Remind active package users to complete onboarding and check current algorithm progress
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../EmailHelper.php';

const NEVER_LOGGED_IN_REMINDER_DAYS = 3;
const NEVER_LOGGED_IN_SUSPEND_DAYS = 8;
const NEVER_LOGGED_IN_GRACE_DAYS = 5;
const INACTIVE_REMINDER_DAYS = 5;
const KYC_REMINDER_DAYS = 3;
const ONBOARDING_REMINDER_DAYS = 3;

const TEMPLATE_NEVER_LOGGED_IN = 'user_never_logged_in_reminder_3_days';
const TEMPLATE_NEVER_LOGGED_IN_SUSPENDED = 'user_never_logged_in_suspended';
const TEMPLATE_INACTIVE_CASE = 'user_inactive_case_reminder_5_days';
const TEMPLATE_KYC_REQUIRED = 'user_kyc_required_withdrawal_notice';
const TEMPLATE_ONBOARDING_PACKAGE = 'user_onboarding_package_reminder';

const AUDIT_NEVER_LOGGED_IN_REMINDER = 'cron_never_logged_in_3_day_reminder_sent';
const AUDIT_NEVER_LOGGED_IN_SUSPENDED = 'cron_never_logged_in_auto_suspended';
const AUDIT_INACTIVE_REMINDER = 'cron_inactive_5_day_reminder_sent';
const AUDIT_KYC_REMINDER = 'cron_kyc_required_reminder_sent';
const AUDIT_ONBOARDING_REMINDER = 'cron_onboarding_package_reminder_sent';

error_log('User Engagement Cron: Start at ' . date('Y-m-d H:i:s'));

try {
    $emailHelper = new EmailHelper($pdo);

    $summary = [
        'never_logged_in_reminders' => 0,
        'never_logged_in_suspended' => 0,
        'inactive_reminders' => 0,
        'kyc_reminders' => 0,
        'onboarding_reminders' => 0,
        'emails_failed' => 0,
    ];

    processNeverLoggedInReminders($pdo, $emailHelper, $summary);
    processNeverLoggedInSuspensions($pdo, $emailHelper, $summary);
    processInactiveCaseReminders($pdo, $emailHelper, $summary);
    processKycReminders($pdo, $emailHelper, $summary);
    processOnboardingPackageReminders($pdo, $emailHelper, $summary);

    error_log(
        'User Engagement Cron: Done. '
        . http_build_query($summary, '', ', ')
    );
    exit(0);
} catch (Throwable $e) {
    error_log('User Engagement Cron: Fatal error - ' . $e->getMessage());
    exit(1);
}

function processNeverLoggedInReminders(PDO $pdo, EmailHelper $emailHelper, array &$summary): void
{
    $users = fetchNeverLoggedInReminderCandidates($pdo);
    error_log('User Engagement Cron: Never-login reminder candidates=' . count($users));

    foreach ($users as $user) {
        $deactivationDate = date(
            'd.m.Y H:i',
            strtotime((string)$user['created_at'] . ' +' . NEVER_LOGGED_IN_SUSPEND_DAYS . ' days')
        );

        $sent = sendReminderEmail(
            $emailHelper,
            TEMPLATE_NEVER_LOGGED_IN,
            (int)$user['id'],
            [
                'registration_date' => formatDateTime((string)$user['created_at']),
                'deactivation_date' => $deactivationDate,
                'grace_days' => (string)NEVER_LOGGED_IN_GRACE_DAYS,
                'login_url' => buildPortalUrl($pdo, '/login.php'),
                'dashboard_url' => buildPortalUrl($pdo, '/app/index.php'),
            ]
        );

        if ($sent) {
            $summary['never_logged_in_reminders']++;
            logAuditEvent($pdo, (int)$user['id'], AUDIT_NEVER_LOGGED_IN_REMINDER, [
                'template_key' => TEMPLATE_NEVER_LOGGED_IN,
                'created_at' => (string)$user['created_at'],
                'deactivation_date' => $deactivationDate,
            ]);
            continue;
        }

        $summary['emails_failed']++;
        error_log('User Engagement Cron: Never-login reminder failed for user_id=' . (int)$user['id']);
    }
}

function processNeverLoggedInSuspensions(PDO $pdo, EmailHelper $emailHelper, array &$summary): void
{
    $users = fetchNeverLoggedInSuspensionCandidates($pdo);
    error_log('User Engagement Cron: Never-login suspension candidates=' . count($users));

    $stmt = $pdo->prepare("UPDATE users SET status = 'suspended', updated_at = NOW() WHERE id = ? AND status = 'active'");

    foreach ($users as $user) {
        try {
            $stmt->execute([(int)$user['id']]);
            if ($stmt->rowCount() < 1) {
                continue;
            }

            $emailSent = sendReminderEmail(
                $emailHelper,
                TEMPLATE_NEVER_LOGGED_IN_SUSPENDED,
                (int)$user['id'],
                [
                    'registration_date' => formatDateTime((string)$user['created_at']),
                    'login_url' => buildPortalUrl($pdo, '/login.php'),
                    'support_url' => buildPortalUrl($pdo, '/app/support.php'),
                ]
            );

            if (!$emailSent) {
                $summary['emails_failed']++;
                error_log('User Engagement Cron: Suspension email failed for user_id=' . (int)$user['id']);
            }

            $summary['never_logged_in_suspended']++;
            logAuditEvent($pdo, (int)$user['id'], AUDIT_NEVER_LOGGED_IN_SUSPENDED, [
                'template_key' => TEMPLATE_NEVER_LOGGED_IN_SUSPENDED,
                'registration_date' => formatDateTime((string)$user['created_at']),
                'email_sent' => $emailSent,
                'new_status' => 'suspended',
            ]);
        } catch (Throwable $e) {
            error_log('User Engagement Cron: Failed to suspend user_id=' . (int)$user['id'] . ' - ' . $e->getMessage());
        }
    }
}

function processInactiveCaseReminders(PDO $pdo, EmailHelper $emailHelper, array &$summary): void
{
    $users = fetchInactiveReminderCandidates($pdo);
    error_log('User Engagement Cron: Inactive reminder candidates=' . count($users));

    foreach ($users as $user) {
        $reportedAmount = isset($user['reported_amount']) ? (float)$user['reported_amount'] : null;
        $sent = sendReminderEmail(
            $emailHelper,
            TEMPLATE_INACTIVE_CASE,
            (int)$user['id'],
            [
                'days_inactive' => (string)((int)$user['days_inactive']),
                'last_login_date' => formatDateTime((string)$user['last_login']),
                'reported_case_amount' => $reportedAmount !== null ? formatEuro($reportedAmount) : '',
                'has_reported_case_amount' => ($reportedAmount !== null && $reportedAmount > 0) ? 'yes' : '',
                'case_number' => (string)($user['case_number'] ?? ''),
                'case_status' => (string)($user['case_status'] ?? ''),
                'dashboard_url' => buildPortalUrl($pdo, '/app/index.php'),
                'cases_url' => buildPortalUrl($pdo, '/app/cases.php'),
            ]
        );

        if ($sent) {
            $summary['inactive_reminders']++;
            logAuditEvent($pdo, (int)$user['id'], AUDIT_INACTIVE_REMINDER, [
                'template_key' => TEMPLATE_INACTIVE_CASE,
                'last_login' => (string)$user['last_login'],
                'days_inactive' => (int)$user['days_inactive'],
                'reported_amount' => $reportedAmount,
            ]);
            continue;
        }

        $summary['emails_failed']++;
        error_log('User Engagement Cron: Inactive reminder failed for user_id=' . (int)$user['id']);
    }
}

function processKycReminders(PDO $pdo, EmailHelper $emailHelper, array &$summary): void
{
    $users = fetchKycReminderCandidates($pdo);
    error_log('User Engagement Cron: KYC reminder candidates=' . count($users));

    foreach ($users as $user) {
        $sent = sendReminderEmail(
            $emailHelper,
            TEMPLATE_KYC_REQUIRED,
            (int)$user['id'],
            [
                'kyc_status_detail' => formatKycStatusLabel((string)($user['latest_kyc_status'] ?? '')),
                'kyc_url' => buildPortalUrl($pdo, '/app/kyc.php'),
                'withdrawal_url' => buildPortalUrl($pdo, '/app/transactions.php'),
                'support_url' => buildPortalUrl($pdo, '/app/support.php'),
            ]
        );

        if ($sent) {
            $summary['kyc_reminders']++;
            logAuditEvent($pdo, (int)$user['id'], AUDIT_KYC_REMINDER, [
                'template_key' => TEMPLATE_KYC_REQUIRED,
                'latest_kyc_status' => (string)($user['latest_kyc_status'] ?? ''),
            ]);
            continue;
        }

        $summary['emails_failed']++;
        error_log('User Engagement Cron: KYC reminder failed for user_id=' . (int)$user['id']);
    }
}

function processOnboardingPackageReminders(PDO $pdo, EmailHelper $emailHelper, array &$summary): void
{
    $users = fetchOnboardingPackageReminderCandidates($pdo);
    error_log('User Engagement Cron: Onboarding reminder candidates=' . count($users));

    foreach ($users as $user) {
        $sent = sendReminderEmail(
            $emailHelper,
            TEMPLATE_ONBOARDING_PACKAGE,
            (int)$user['id'],
            [
                'package_name' => (string)($user['package_name'] ?? ''),
                'algorithm_status' => 'Unsere KI-gestützte Analyse läuft bereits und identifiziert fortlaufend neue Hinweise für Ihren Vorgang.',
                'onboarding_url' => buildPortalUrl($pdo, '/app/onboarding.php'),
                'packages_url' => buildPortalUrl($pdo, '/app/packages.php'),
                'dashboard_url' => buildPortalUrl($pdo, '/app/index.php'),
                'kyc_url' => buildPortalUrl($pdo, '/app/kyc.php'),
                'reported_case_amount' => isset($user['reported_amount']) ? formatEuro((float)$user['reported_amount']) : '',
                'has_reported_case_amount' => (isset($user['reported_amount']) && (float)$user['reported_amount'] > 0) ? 'yes' : '',
            ]
        );

        if ($sent) {
            $summary['onboarding_reminders']++;
            logAuditEvent($pdo, (int)$user['id'], AUDIT_ONBOARDING_REMINDER, [
                'template_key' => TEMPLATE_ONBOARDING_PACKAGE,
                'package_name' => (string)($user['package_name'] ?? ''),
            ]);
            continue;
        }

        $summary['emails_failed']++;
        error_log('User Engagement Cron: Onboarding reminder failed for user_id=' . (int)$user['id']);
    }
}

function fetchNeverLoggedInReminderCandidates(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT u.id, u.email, u.first_name, u.last_name, u.created_at
        FROM users u
        WHERE u.status = 'active'
          AND (u.last_login IS NULL OR u.last_login = '0000-00-00 00:00:00')
          AND u.created_at <= DATE_SUB(NOW(), INTERVAL " . NEVER_LOGGED_IN_REMINDER_DAYS . " DAY)
          AND u.created_at > DATE_SUB(NOW(), INTERVAL " . NEVER_LOGGED_IN_SUSPEND_DAYS . " DAY)
          AND NOT EXISTS (
              SELECT 1
              FROM audit_logs al
              WHERE al.user_id = u.id
                AND al.action = '" . AUDIT_NEVER_LOGGED_IN_REMINDER . "'
          )
        ORDER BY u.created_at ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fetchNeverLoggedInSuspensionCandidates(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT u.id, u.email, u.first_name, u.last_name, u.created_at
        FROM users u
        WHERE u.status = 'active'
          AND (u.last_login IS NULL OR u.last_login = '0000-00-00 00:00:00')
          AND u.created_at <= DATE_SUB(NOW(), INTERVAL " . NEVER_LOGGED_IN_SUSPEND_DAYS . " DAY)
          AND NOT EXISTS (
              SELECT 1
              FROM audit_logs al
              WHERE al.user_id = u.id
                AND al.action = '" . AUDIT_NEVER_LOGGED_IN_SUSPENDED . "'
          )
        ORDER BY u.created_at ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fetchInactiveReminderCandidates(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            u.id,
            u.email,
            u.first_name,
            u.last_name,
            u.last_login,
            DATEDIFF(NOW(), u.last_login) AS days_inactive,
            (
                SELECT c.case_number
                FROM cases c
                WHERE c.user_id = u.id
                ORDER BY c.created_at DESC, c.id DESC
                LIMIT 1
            ) AS case_number,
            (
                SELECT c.status
                FROM cases c
                WHERE c.user_id = u.id
                ORDER BY c.created_at DESC, c.id DESC
                LIMIT 1
            ) AS case_status,
            (
                SELECT c.reported_amount
                FROM cases c
                WHERE c.user_id = u.id
                ORDER BY c.created_at DESC, c.id DESC
                LIMIT 1
            ) AS reported_amount
        FROM users u
        WHERE u.status = 'active'
          AND u.last_login IS NOT NULL
          AND u.last_login <> '0000-00-00 00:00:00'
          AND u.last_login <= DATE_SUB(NOW(), INTERVAL " . INACTIVE_REMINDER_DAYS . " DAY)
          AND NOT EXISTS (
              SELECT 1
              FROM audit_logs al
              WHERE al.user_id = u.id
                AND al.action = '" . AUDIT_INACTIVE_REMINDER . "'
                AND al.created_at >= u.last_login
          )
        ORDER BY u.last_login ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fetchKycReminderCandidates(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            u.id,
            u.email,
            u.first_name,
            u.last_name,
            (
                SELECT k.status
                FROM kyc_verification_requests k
                WHERE k.user_id = u.id
                ORDER BY k.created_at DESC, k.id DESC
                LIMIT 1
            ) AS latest_kyc_status
        FROM users u
        WHERE u.status = 'active'
          AND u.is_verified = 1
          AND u.created_at <= DATE_SUB(NOW(), INTERVAL " . KYC_REMINDER_DAYS . " DAY)
          AND NOT EXISTS (
              SELECT 1
              FROM kyc_verification_requests k2
              WHERE k2.user_id = u.id
                AND k2.status = 'approved'
          )
          AND NOT EXISTS (
              SELECT 1
              FROM audit_logs al
              WHERE al.user_id = u.id
                AND al.action = '" . AUDIT_KYC_REMINDER . "'
          )
        ORDER BY u.created_at ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fetchOnboardingPackageReminderCandidates(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            u.id,
            u.email,
            u.first_name,
            u.last_name,
            (
                SELECT p.name
                FROM user_packages up
                INNER JOIN packages p ON p.id = up.package_id
                WHERE up.user_id = u.id
                  AND up.status = 'active'
                ORDER BY up.start_date DESC, up.id DESC
                LIMIT 1
            ) AS package_name,
            (
                SELECT c.reported_amount
                FROM cases c
                WHERE c.user_id = u.id
                ORDER BY c.created_at DESC, c.id DESC
                LIMIT 1
            ) AS reported_amount
        FROM users u
        LEFT JOIN user_onboarding uo ON uo.user_id = u.id
        WHERE u.status = 'active'
          AND u.created_at <= DATE_SUB(NOW(), INTERVAL " . ONBOARDING_REMINDER_DAYS . " DAY)
          AND (uo.id IS NULL OR COALESCE(uo.completed, 0) = 0)
          AND EXISTS (
              SELECT 1
              FROM user_packages up2
              WHERE up2.user_id = u.id
                AND up2.status = 'active'
          )
          AND NOT EXISTS (
              SELECT 1
              FROM audit_logs al
              WHERE al.user_id = u.id
                AND al.action = '" . AUDIT_ONBOARDING_REMINDER . "'
          )
        ORDER BY u.created_at ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function sendReminderEmail(EmailHelper $emailHelper, string $templateKey, int $userId, array $customVars): bool
{
    if ($emailHelper->sendEmail($templateKey, $userId, $customVars)) {
        return true;
    }

    $fallback = getFallbackTemplate($templateKey);
    if ($fallback === null) {
        return false;
    }

    return $emailHelper->sendDirectEmail(
        $userId,
        $fallback['subject'],
        $fallback['content'],
        $customVars
    );
}

function getFallbackTemplate(string $templateKey): ?array
{
    $templates = [
        TEMPLATE_NEVER_LOGGED_IN => [
            'subject' => 'Willkommen bei {brand_name} – bitte loggen Sie sich jetzt ein',
            'content' => '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
                <p>Sie haben Ihr Konto am <strong>{registration_date}</strong> erstellt, aber bisher noch keinen ersten Login durchgeführt.</p>
                <div class="highlight-box">
                    <h3>Ihr nächster Schritt</h3>
                    <p>Bitte melden Sie sich jetzt im Kundenportal an, damit wir Ihren Fall strukturiert weiterverarbeiten können.</p>
                    <p>Ohne ersten Login wird Ihr Konto am <strong>{deactivation_date}</strong> vorsorglich eingeschränkt.</p>
                </div>
                <p style="text-align:center;"><a href="{login_url}" class="btn">Jetzt anmelden</a></p>',
        ],
        TEMPLATE_NEVER_LOGGED_IN_SUSPENDED => [
            'subject' => 'Ihr Konto wurde vorübergehend eingeschränkt',
            'content' => '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
                <p>da seit Ihrer Registrierung am <strong>{registration_date}</strong> kein Login erfolgt ist, wurde Ihr Konto vorübergehend auf <strong>suspendiert</strong> gesetzt.</p>
                <p>Bitte kontaktieren Sie bei Rückfragen unser Support-Team oder melden Sie sich erneut an, sobald Ihr Zugang reaktiviert wurde.</p>
                <p style="text-align:center;"><a href="{support_url}" class="btn">Support kontaktieren</a></p>',
        ],
        TEMPLATE_INACTIVE_CASE => [
            'subject' => 'Bitte prüfen Sie Ihr Kundenportal – neue Aktivität empfohlen',
            'content' => '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
                <p>Sie waren seit <strong>{last_login_date}</strong> nicht mehr im Portal aktiv.</p>
                {#if has_reported_case_amount}<div class="highlight-box"><h3>Gemeldeter Fallbetrag</h3><p>Für Ihren letzten Fall ist derzeit ein gemeldeter Betrag von <strong>{reported_case_amount}</strong> hinterlegt.</p></div>{/if}
                <p>Bitte loggen Sie sich ein und prüfen Sie den aktuellen Bearbeitungsstand.</p>
                <p style="text-align:center;"><a href="{cases_url}" class="btn">Zum Fallbereich</a></p>',
        ],
        TEMPLATE_KYC_REQUIRED => [
            'subject' => 'KYC-Verifizierung erforderlich – Auszahlungen derzeit nicht verfügbar',
            'content' => '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
                <p>für Ihr Konto liegt derzeit keine abgeschlossene KYC-Verifizierung vor.</p>
                <div class="highlight-box">
                    <h3>Wichtiger Hinweis</h3>
                    <p>Ohne erfolgreiche KYC-Prüfung bleiben Auszahlungen und bestimmte Kontofunktionen gesperrt.</p>
                </div>
                <p style="text-align:center;"><a href="{kyc_url}" class="btn">KYC jetzt abschließen</a></p>',
        ],
        TEMPLATE_ONBOARDING_PACKAGE => [
            'subject' => 'Bitte vervollständigen Sie Ihr Onboarding',
            'content' => '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
                <p>Ihr aktives Paket <strong>{package_name}</strong> ist bereits hinterlegt, Ihr Onboarding wurde jedoch noch nicht abgeschlossen.</p>
                <p>{algorithm_status}</p>
                <p style="text-align:center;"><a href="{onboarding_url}" class="btn">Onboarding fortsetzen</a></p>',
        ],
    ];

    return $templates[$templateKey] ?? null;
}

function logAuditEvent(PDO $pdo, int $userId, string $action, array $details): void
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (
                user_id, admin_id, action, entity_type, entity_id, new_value, ip_address, user_agent, created_at
            ) VALUES (?, NULL, ?, 'user_reminder_cron', ?, ?, '127.0.0.1', 'cron_user_engagement_reminders', NOW())
        ");
        $stmt->execute([
            $userId,
            $action,
            $userId,
            json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    } catch (Throwable $e) {
        error_log('User Engagement Cron: audit log failed for action ' . $action . ' - ' . $e->getMessage());
    }
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

function formatEuro(float $amount): string
{
    return number_format($amount, 2, ',', '.') . ' €';
}

function formatDateTime(string $value): string
{
    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return 'noch kein Login';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d.m.Y H:i', $timestamp);
}

function formatKycStatusLabel(string $status): string
{
    return match ($status) {
        'pending' => 'Ihre Unterlagen sind eingereicht und befinden sich aktuell in Prüfung.',
        'rejected' => 'Ihre letzte KYC-Einreichung wurde zurückgewiesen und benötigt eine erneute Übermittlung.',
        default => 'Für Ihr Konto wurde noch keine vollständige KYC-Verifizierung eingereicht.',
    };
}
