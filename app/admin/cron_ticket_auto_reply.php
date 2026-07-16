<?php
/**
 * Cron Job: Support ticket first auto-reply
 *
 * Run example:
 *   * * * * * /usr/bin/php /path/to/app/admin/cron_ticket_auto_reply.php
 *
 * Tasks:
 * - Find open tickets older than 5 minutes with no replies
 * - Send one auto-reply only once
 * - Mark ticket with auto_reply_sent = 1
 * - Send user email via template key "ticket_auto_reply"
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../EmailHelper.php';

const TICKET_AUTO_REPLY_ACTION = 'cron_ticket_auto_reply_sent';
const TICKET_AUTO_REPLY_TEMPLATE = 'ticket_auto_reply';
const TICKET_AUTO_REPLY_MESSAGE = 'We are currently reviewing your case. Our support team has been notified and will respond as soon as possible. Thank you for your patience.';

error_log('Ticket Auto Reply Cron: Start at ' . date('Y-m-d H:i:s'));

try {
    $emailHelper = new EmailHelper($pdo);
    $adminId = resolveCronAdminId($pdo);

    if ($adminId === null) {
        throw new RuntimeException('No admin account available for system auto replies');
    }

    $tickets = fetchTicketCandidates($pdo);
    $summary = [
        'candidates' => count($tickets),
        'auto_replies_sent' => 0,
        'emails_sent' => 0,
        'failed' => 0,
    ];

    foreach ($tickets as $ticket) {
        $ticketId = (int)$ticket['id'];
        $userId = (int)$ticket['user_id'];

        try {
            $pdo->beginTransaction();

            if (hasAnyReply($pdo, $ticketId)) {
                $pdo->rollBack();
                continue;
            }

            insertAutoReply($pdo, $ticketId, $adminId);
            markTicketAsAutoReplied($pdo, $ticketId);

            logAdminAction($pdo, $adminId, TICKET_AUTO_REPLY_ACTION, [
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'ticket_number' => (string)$ticket['ticket_number'],
            ]);

            $pdo->commit();

            $summary['auto_replies_sent']++;

            $emailSent = sendTicketAutoReplyEmail($pdo, $emailHelper, $userId, $ticket);
            if ($emailSent) {
                $summary['emails_sent']++;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $summary['failed']++;
            error_log("Ticket Auto Reply Cron: ticket_id={$ticketId} failed - " . $e->getMessage());
        }
    }

    error_log('Ticket Auto Reply Cron: Done. ' . http_build_query($summary, '', ', '));
    exit(0);
} catch (Throwable $e) {
    error_log('Ticket Auto Reply Cron: Fatal error - ' . $e->getMessage());
    exit(1);
}

function fetchTicketCandidates(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT st.id, st.user_id, st.ticket_number, st.subject, st.status
        FROM support_tickets st
        WHERE st.status = 'open'
          AND st.created_at <= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
          AND COALESCE(st.auto_reply_sent, 0) = 0
          AND NOT EXISTS (
              SELECT 1
              FROM ticket_replies tr
              WHERE tr.ticket_id = st.id
          )
        ORDER BY st.created_at ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function hasAnyReply(PDO $pdo, int $ticketId): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    return (int)$stmt->fetchColumn() > 0;
}

function insertAutoReply(PDO $pdo, int $ticketId, int $adminId): void
{
    $stmt = $pdo->prepare("
        INSERT INTO ticket_replies (ticket_id, admin_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$ticketId, $adminId, TICKET_AUTO_REPLY_MESSAGE]);
}

function markTicketAsAutoReplied(PDO $pdo, int $ticketId): void
{
    $stmt = $pdo->prepare("
        UPDATE support_tickets
        SET auto_reply_sent = 1,
            last_reply_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
          AND COALESCE(auto_reply_sent, 0) = 0
    ");
    $stmt->execute([$ticketId]);
}

function sendTicketAutoReplyEmail(PDO $pdo, EmailHelper $emailHelper, int $userId, array $ticket): bool
{
    $customVars = [
        'ticket_number' => (string)($ticket['ticket_number'] ?? ''),
        'ticket_subject' => (string)($ticket['subject'] ?? ''),
        'ticket_status' => (string)($ticket['status'] ?? 'open'),
        'reply_message' => TICKET_AUTO_REPLY_MESSAGE,
        'support_url' => buildPortalUrl($pdo, '/app/support.php'),
    ];

    if ($emailHelper->sendEmail(TICKET_AUTO_REPLY_TEMPLATE, $userId, $customVars)) {
        return true;
    }

    return $emailHelper->sendDirectEmail(
        $userId,
        'Update zu Ihrem Support-Ticket {ticket_number}',
        '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
         <p>wir bestätigen den Eingang Ihres Support-Tickets und möchten Sie kurz informieren:</p>
         <div class="highlight-box"><p>{reply_message}</p></div>
         <p>Sie können Ihr Ticket jederzeit im Portal einsehen.</p>
         <p style="text-align:center;"><a href="{support_url}" class="btn">Ticket ansehen</a></p>',
        $customVars
    );
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

