<?php
// =======================================================
// 🔍 get_user.php — fetch full user details for admin modal
// =======================================================
ob_start(); // buffer output so display_errors cannot corrupt JSON
require_once '../admin_session.php';
header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
        throw new Exception('Invalid user ID');
    }

    $userId = (int) $_GET['id'];

    // --------------------------------
    // 🧑 Basic Info (full user row)
    // --------------------------------
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception('User not found');
    }

    // --------------------------------
    // 📊 Case Recovery Stats
    // --------------------------------
    $stmtCaseStats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_cases,
            COALESCE(SUM(reported_amount), 0) as total_reported,
            COALESCE(SUM(recovered_amount), 0) as total_recovered,
            SUM(CASE WHEN status IN ('open', 'documents_required', 'under_review') THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'refund_approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
        FROM cases WHERE user_id = ?
    ");
    $stmtCaseStats->execute([$userId]);
    $caseStats = $stmtCaseStats->fetch(PDO::FETCH_ASSOC);

    // --------------------------------
    // 💳 Bank account
    // --------------------------------
    $stmtBank = $pdo->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? AND type = 'fiat' LIMIT 1");
    $stmtBank->execute([$userId]);
    $bankAccount = $stmtBank->fetch(PDO::FETCH_ASSOC);

    // --------------------------------
    // 🪙 Crypto wallet
    // --------------------------------
    $stmtCrypto = $pdo->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? AND type = 'crypto' LIMIT 1");
    $stmtCrypto->execute([$userId]);
    $cryptoWallet = $stmtCrypto->fetch(PDO::FETCH_ASSOC);

    // --------------------------------
    // 🟢 KYC status (latest)
    // --------------------------------
    $stmtKycStatus = $pdo->prepare("SELECT status FROM kyc_verification_requests WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmtKycStatus->execute([$userId]);
    $kycStatusRow = $stmtKycStatus->fetch(PDO::FETCH_ASSOC);
    $kycStatusVal = $kycStatusRow['status'] ?? 'none';

    // -- Helper: status badges
    $userStatusBadge  = ['active' => 'success', 'suspended' => 'warning', 'banned' => 'danger'][$user['status'] ?? ''] ?? 'secondary';
    $kycBadge         = ['approved' => 'success', 'rejected' => 'danger', 'pending' => 'warning', 'none' => 'secondary'][$kycStatusVal] ?? 'secondary';
    $kycLabel         = ucfirst($kycStatusVal);
    $verifiedBadge    = ($user['is_verified'] ?? 0) ? 'success' : 'warning';
    $verifiedLabel    = ($user['is_verified'] ?? 0) ? 'Verified' : 'Unverified';
    $lastLogin        = !empty($user['last_login']) ? date('d.m.Y H:i', strtotime($user['last_login'])) : '<em class="text-muted">Never</em>';
    $registered       = !empty($user['created_at']) ? date('d.m.Y H:i', strtotime($user['created_at'])) : '—';

    // Address fields (flexible column names)
    $addressParts = array_filter([
        trim($user['address'] ?? ''),
        trim($user['address_line1'] ?? ''),
        trim($user['city'] ?? ''),
        trim($user['country'] ?? ''),
    ]);
    $phone    = $user['phone'] ?? '';
    $addressLine = $addressParts;

    // -- Bank account row
    if ($bankAccount) {
        $bankHTML = "
        <div class='row'>
          <div class='col-sm-6'><small class='text-muted'>Bank</small><div class='font-weight-bold'>" . htmlspecialchars($bankAccount['bank_name'] ?? '—') . "</div></div>
          <div class='col-sm-6'><small class='text-muted'>Account Holder</small><div class='font-weight-bold'>" . htmlspecialchars($bankAccount['account_holder'] ?? '—') . "</div></div>
          <div class='col-sm-6 mt-2'><small class='text-muted'>IBAN</small><div class='font-weight-bold font-monospace'>" . htmlspecialchars($bankAccount['iban'] ?? '—') . "</div></div>
          <div class='col-sm-6 mt-2'><small class='text-muted'>BIC</small><div class='font-weight-bold font-monospace'>" . htmlspecialchars($bankAccount['bic'] ?? '—') . "</div></div>
        </div>";
    } else {
        $bankHTML = "<p class='text-muted mb-0'><i class='anticon anticon-info-circle mr-1'></i>No bank account linked.</p>";
    }

    // -- Crypto wallet row
    if ($cryptoWallet) {
        $cryptoHTML = "
        <div class='row'>
          <div class='col-sm-6'><small class='text-muted'>Cryptocurrency</small><div class='font-weight-bold'>" . htmlspecialchars($cryptoWallet['cryptocurrency'] ?? '—') . "</div></div>
          <div class='col-sm-6'><small class='text-muted'>Network</small><div class='font-weight-bold'>" . htmlspecialchars($cryptoWallet['network'] ?? '—') . "</div></div>
          <div class='col-12 mt-2'><small class='text-muted'>Wallet Address</small><div class='font-weight-bold font-monospace text-break'>" . htmlspecialchars($cryptoWallet['wallet_address'] ?? '—') . "</div></div>
        </div>";
    } else {
        $cryptoHTML = "<p class='text-muted mb-0'><i class='anticon anticon-info-circle mr-1'></i>No crypto wallet linked.</p>";
    }

    $basicHTML = "
    <div class='row no-gutters'>
      <!-- Left column: identity -->
      <div class='col-lg-6 border-right p-3'>
        <h6 class='text-uppercase text-muted mb-3' style='font-size:11px;letter-spacing:1px;'><i class='anticon anticon-idcard mr-1'></i> Identity</h6>
        <table class='table table-sm mb-0'>
          <tr><th style='width:38%'>ID</th><td><code>#{$user['id']}</code></td></tr>
          <tr><th>Full Name</th><td><strong>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</strong></td></tr>
          <tr><th>Email</th><td><a href='mailto:" . htmlspecialchars($user['email']) . "'>" . htmlspecialchars($user['email']) . "</a></td></tr>
          <tr><th>Phone</th><td>" . ($phone ? htmlspecialchars($phone) : '<em class=\"text-muted\">—</em>') . "</td></tr>
          <tr><th>Address</th><td>" . (count($addressLine) ? htmlspecialchars(implode(', ', $addressLine)) : '<em class=\"text-muted\">—</em>') . "</td></tr>
          <tr><th>Account Status</th><td><span class='badge badge-{$userStatusBadge}'>" . htmlspecialchars(ucfirst($user['status'] ?? '')) . "</span></td></tr>
          <tr><th>Email Verified</th><td><span class='badge badge-{$verifiedBadge}'>{$verifiedLabel}</span></td></tr>
          <tr><th>KYC Status</th><td><span class='badge badge-{$kycBadge}'>{$kycLabel}</span></td></tr>
          <tr><th>Last Login</th><td>{$lastLogin}</td></tr>
          <tr><th>Registered</th><td>{$registered}</td></tr>
          <tr><th>Balance</th><td><strong class='text-primary'>€" . number_format($user['balance'] ?? 0, 2, ',', '.') . "</strong></td></tr>
        </table>
      </div>
      <!-- Right column: case summary + quick links -->
      <div class='col-lg-6 p-3'>
        <h6 class='text-uppercase text-muted mb-3' style='font-size:11px;letter-spacing:1px;'><i class='anticon anticon-bar-chart mr-1'></i> Case Recovery Summary</h6>
        <div class='row text-center mb-3'>
          <div class='col-4'>
            <div style='font-size:22px;font-weight:700;color:#2950a8;'>{$caseStats['total_cases']}</div>
            <small class='text-muted'>Total</small>
          </div>
          <div class='col-4'>
            <div style='font-size:22px;font-weight:700;color:#ffc107;'>{$caseStats['processing']}</div>
            <small class='text-muted'>Processing</small>
          </div>
          <div class='col-4'>
            <div style='font-size:22px;font-weight:700;color:#28a745;'>{$caseStats['approved']}</div>
            <small class='text-muted'>Approved</small>
          </div>
        </div>
        <table class='table table-sm mb-3'>
          <tr><th>Total Reported</th><td>€" . number_format($caseStats['total_reported'], 2, ',', '.') . "</td></tr>
          <tr><th>Total Recovered</th><td><strong class='text-success'>€" . number_format($caseStats['total_recovered'], 2, ',', '.') . "</strong></td></tr>
          <tr><th>Closed Cases</th><td><span class='badge badge-secondary'>{$caseStats['closed']}</span></td></tr>
        </table>
        <h6 class='text-uppercase text-muted mb-2' style='font-size:11px;letter-spacing:1px;'><i class='anticon anticon-link mr-1'></i> Quick Links</h6>
        <div class='d-flex flex-wrap' style='gap:6px;'>
          <a href='admin_cases.php?user_id={$user['id']}' target='_blank' class='btn btn-sm btn-outline-primary'><i class='anticon anticon-folder mr-1'></i>Cases</a>
          <a href='admin_transactions.php?user_id={$user['id']}' target='_blank' class='btn btn-sm btn-outline-info'><i class='anticon anticon-swap mr-1'></i>Transactions</a>
          <a href='admin_support.php?user_id={$user['id']}' target='_blank' class='btn btn-sm btn-outline-warning'><i class='anticon anticon-customer-service mr-1'></i>Support Tickets</a>
        </div>
      </div>
    </div>
    ";

    // --------------------------------
    // 🧾 Onboarding Info
    // --------------------------------
    $stmt = $pdo->prepare("SELECT * FROM user_onboarding WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $onboarding = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($onboarding) {
        $platforms = json_decode($onboarding['platforms'], true);
        if (is_array($platforms) && count($platforms)) {
            $in = implode(',', array_fill(0, count($platforms), '?'));
            $stmt = $pdo->prepare("SELECT name FROM scam_platforms WHERE id IN ($in)");
            $stmt->execute($platforms);
            $platformNames = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
        } else {
            $platformNames = ['Keine Plattformen angegeben'];
        }

        $onboardingHTML = "
            <table class='table'>
                <tr><th>Lost Amount</th><td>€" . number_format($onboarding['lost_amount'], 2) . "</td></tr>
                <tr><th>Year Lost</th><td>{$onboarding['year_lost']}</td></tr>
                <tr><th>Platforms</th><td>" . implode(', ', $platformNames) . "</td></tr>
                <tr><th>Country</th><td>{$onboarding['country']}</td></tr>
                <tr><th>Completed</th><td>{$onboarding['completed']}</td></tr>
                <tr><th>Description</th><td>{$onboarding['case_description']}</td></tr>
            </table>";
    } else {
        $onboardingHTML = "
        <div class='alert alert-warning d-flex align-items-start' role='alert'>
          <i class='anticon anticon-exclamation-circle mr-2 mt-1' style='font-size:18px;'></i>
          <div>
            <strong>No onboarding data found.</strong><br>
            <span class='text-muted small'>This user has not completed the onboarding questionnaire yet.</span><br>
            <a href='#sendEmailTab' data-toggle='tab' class='btn btn-sm btn-warning mt-2'>
              <i class='anticon anticon-mail mr-1'></i> Send Onboarding Reminder Email
            </a>
          </div>
        </div>";
    }

    // --------------------------------
    // 🪪 KYC Verification
    // --------------------------------
    $stmt = $pdo->prepare("SELECT * FROM kyc_verification_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $kyc = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($kyc) {
        $kycHTML = "
        <table class='table'>
            <tr><th>Status</th><td>{$kyc['status']}</td></tr>
            <tr><th>Document Type</th><td>{$kyc['document_type']}</td></tr>
            <tr><th>Front</th><td><a href='../{$kyc['document_front']}' target='_blank'>View</a></td></tr>
            <tr><th>Back</th><td><a href='../{$kyc['document_back']}' target='_blank'>View</a></td></tr>
            <tr><th>Selfie</th><td><a href='../{$kyc['selfie_with_id']}' target='_blank'>View</a></td></tr>
            <tr><th>Address Proof</th><td><a href='../{$kyc['address_proof']}' target='_blank'>View</a></td></tr>
            <tr><th>Created</th><td>{$kyc['created_at']}</td></tr>
        </table>";
    } else {
        $kycHTML = "
        <div class='alert alert-warning d-flex align-items-start' role='alert'>
          <i class='anticon anticon-safety-certificate mr-2 mt-1' style='font-size:18px;'></i>
          <div>
            <strong>No KYC documents submitted yet.</strong><br>
            <span class='text-muted small'>This user has not uploaded identity verification documents.</span><br>
            <a href='#sendEmailTab' data-toggle='tab' class='btn btn-sm btn-warning mt-2'>
              <i class='anticon anticon-mail mr-1'></i> Send KYC Reminder Email
            </a>
          </div>
        </div>";
    }

    // --------------------------------
    // 💳 Payments tab (bank + wallet + transactions)
    // --------------------------------
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? AND type IN ('deposit', 'withdrawal', 'refund') ORDER BY created_at DESC LIMIT 25");
    $stmt->execute([$userId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $payRows = '';
    foreach ($payments as $p) {
        $typeBadge = ['deposit' => 'success', 'withdrawal' => 'info', 'refund' => 'warning'][$p['type']] ?? 'secondary';
        $stBadge   = ['completed' => 'success', 'pending' => 'warning', 'failed' => 'danger'][$p['status']] ?? 'secondary';
        $payRows .= "<tr>
            <td><span class='badge badge-{$typeBadge}'>{$p['type']}</span></td>
            <td><strong>€" . number_format($p['amount'], 2, ',', '.') . "</strong></td>
            <td><span class='badge badge-{$stBadge}'>{$p['status']}</span></td>
            <td>" . date('d.m.Y H:i', strtotime($p['created_at'])) . "</td>
        </tr>";
    }
    $txTableHTML = $payRows
        ? "<div class='table-responsive mt-3'><table class='table table-sm table-hover'>
             <thead class='thead-light'><tr><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
             <tbody>{$payRows}</tbody></table></div>"
        : "<p class='text-muted mt-2'>No payment transactions found.</p>";

    $paymentReminderHTML = '';
    if (!$bankAccount && !$cryptoWallet) {
        $paymentReminderHTML = "
    <div class='alert alert-warning d-flex align-items-start mb-3' role='alert'>
      <i class='anticon anticon-credit-card mr-2 mt-1' style='font-size:18px;'></i>
      <div>
        <strong>No payment method on file.</strong><br>
        <span class='text-muted small'>This user has not linked a bank account or crypto wallet yet.</span><br>
        <a href='#sendEmailTab' data-toggle='tab' class='btn btn-sm btn-warning mt-2'>
          <i class='anticon anticon-mail mr-1'></i> Send Payment Setup Reminder Email
        </a>
      </div>
    </div>";
    }

    $paymentsHTML = "
    {$paymentReminderHTML}
    <h6 class='text-uppercase text-muted mb-2' style='font-size:11px;letter-spacing:1px;'><i class='anticon anticon-bank mr-1'></i> Bank Account</h6>
    <div class='border rounded p-3 mb-3 bg-light'>{$bankHTML}</div>
    <h6 class='text-uppercase text-muted mb-2' style='font-size:11px;letter-spacing:1px;'><i class='anticon anticon-bitcoin mr-1'></i> Crypto Wallet</h6>
    <div class='border rounded p-3 mb-3 bg-light'>{$cryptoHTML}</div>
    <h6 class='text-uppercase text-muted mb-2' style='font-size:11px;letter-spacing:1px;'><i class='anticon anticon-swap mr-1'></i> Recent Payment Transactions</h6>
    {$txTableHTML}
    ";

    // --------------------------------
    // 🔄 Transactions (all)
    // --------------------------------
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($transactions) {
        $rows = '';
        foreach ($transactions as $t) {
            $rows .= "<tr>
                <td>{$t['id']}</td>
                <td>{$t['type']}</td>
                <td>€" . number_format($t['amount'], 2) . "</td>
                <td>{$t['status']}</td>
                <td>{$t['reference']}</td>
                <td>{$t['created_at']}</td>
            </tr>";
        }
        $transactionsHTML = "<table class='table'><thead>
            <tr><th>ID</th><th>Type</th><th>Amount</th><th>Status</th><th>Reference</th><th>Date</th></tr>
            </thead><tbody>$rows</tbody></table>";
    } else {
        $transactionsHTML = "<div class='text-muted'>No transactions found.</div>";
    }

    // --------------------------------
    // 📂 Cases (with platform + docs)
    // --------------------------------
    $sqlCases = "
        SELECT c.id, c.case_number, c.reported_amount, c.recovered_amount, c.status, c.description,
               c.created_at, sp.name AS platform_name,
               (SELECT COUNT(*) FROM case_documents cd WHERE cd.case_id = c.id) AS docs
        FROM cases c
        LEFT JOIN scam_platforms sp ON sp.id = c.platform_id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT 20";
    $stmt = $pdo->prepare($sqlCases);
    $stmt->execute([$userId]);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($cases) {
        $rows = '';
        foreach ($cases as $c) {
            $rows .= "<tr>
                <td>{$c['case_number']}</td>
                <td>{$c['platform_name']}</td>
                <td>€" . number_format($c['reported_amount'], 2) . "</td>
                <td>€" . number_format($c['recovered_amount'], 2) . "</td>
                <td>{$c['status']}</td>
                <td>{$c['docs']}</td>
                <td>{$c['created_at']}</td>
            </tr>";
        }
        $casesHTML = "<table class='table'>
            <thead><tr><th>Case #</th><th>Platform</th><th>Reported</th><th>Recovered</th><th>Status</th><th>Docs</th><th>Date</th></tr></thead>
            <tbody>$rows</tbody></table>";
    } else {
        $casesHTML = "<div class='text-muted'>No cases found for this user.</div>";
    }

    // --------------------------------
    // 🎫 Support Tickets
    // --------------------------------
    $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$userId]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($tickets) {
        $rows = '';
        foreach ($tickets as $t) {
            $rows .= "<tr>
                <td>{$t['ticket_number']}</td>
                <td>{$t['subject']}</td>
                <td>{$t['status']}</td>
                <td>{$t['priority']}</td>
                <td>{$t['created_at']}</td>
            </tr>";
        }
        $ticketsHTML = "<table class='table'>
            <thead><tr><th>Ticket #</th><th>Subject</th><th>Status</th><th>Priority</th><th>Date</th></tr></thead>
            <tbody>$rows</tbody></table>";
    } else {
        $ticketsHTML = "<div class='text-muted'>No support tickets found.</div>";
    }

    // --------------------------------
    // 📧 Email Logs (last 20 for this user)
    // --------------------------------
    $emailLogs = [];
    try {
        $stmtLogs = $pdo->prepare("
            SELECT el.subject, el.status,
                   COALESCE(t.template_key, el.template_key) AS resolved_template_key,
                   el.sent_at, el.error_message
            FROM email_logs el
            LEFT JOIN email_templates t ON el.template_id = t.id
            WHERE el.recipient = ?
            ORDER BY el.sent_at DESC
            LIMIT 20
        ");
        $stmtLogs->execute([$user['email']]);
        $emailLogs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback: template_key column may not yet be migrated — use simpler query
        try {
            $stmtLogs = $pdo->prepare("
                SELECT el.subject, el.status, NULL AS resolved_template_key,
                       el.sent_at, el.error_message
                FROM email_logs el
                WHERE el.recipient = ?
                ORDER BY el.sent_at DESC
                LIMIT 20
            ");
            $stmtLogs->execute([$user['email']]);
            $emailLogs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {
            $emailLogs = [];
        }
    }

    if ($emailLogs) {
        $logRows = '';
        foreach ($emailLogs as $log) {
            $stBadge = $log['status'] === 'sent' ? 'success' : 'danger';
            $sentAt  = $log['sent_at'] ? date('d.m.Y H:i', strtotime($log['sent_at'])) : '—';
            $logRows .= "<tr>
                <td class='text-truncate' style='max-width:220px;'>" . htmlspecialchars($log['subject'] ?? '—') . "</td>
                <td><code class='small'>" . htmlspecialchars($log['resolved_template_key'] ?? '—') . "</code></td>
                <td><span class='badge badge-{$stBadge}'>" . htmlspecialchars($log['status'] ?? '—') . "</span></td>
                <td>{$sentAt}</td>
            </tr>";
        }
        $emailLogsHTML = "<div class='table-responsive'>
            <table class='table table-sm table-hover'>
              <thead class='thead-light'><tr><th>Subject</th><th>Template</th><th>Status</th><th>Sent At</th></tr></thead>
              <tbody>{$logRows}</tbody>
            </table></div>";
    } else {
        $emailLogsHTML = "<p class='text-muted p-3'>No emails sent to this user yet.</p>";
    }

    // --------------------------------
    // 📤 Send Email (inline form)
    // --------------------------------
    $userNameEsc  = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
    $userEmailEsc = htmlspecialchars($user['email']);
    $sendEmailHTML = "
    <div class='p-1'>
      <div class='alert alert-info py-2 mb-3'>
        <i class='anticon anticon-mail mr-1'></i>
        Sending to: <strong>{$userNameEsc}</strong> &lt;{$userEmailEsc}&gt;
      </div>
      <form id='modalSendMailForm'>
        <input type='hidden' name='user_id' value='{$user['id']}'>
        <div class='form-group'>
          <label class='small font-weight-bold'>Subject <span class='text-danger'>*</span></label>
          <input type='text' class='form-control' name='subject' placeholder='Enter subject…' required>
        </div>
        <div class='form-group'>
          <label class='small font-weight-bold'>Message <span class='text-danger'>*</span></label>
          <textarea class='form-control' name='message' rows='7' placeholder='Your message… HTML is supported. Variables: {first_name}, {last_name}, {balance}' required></textarea>
          <small class='text-muted'>The message is automatically wrapped in the professional branded email template.</small>
        </div>
        <button type='submit' class='btn btn-success btn-block' id='modalSendMailBtn'>
          <i class='anticon anticon-send mr-1'></i> Send Email
        </button>
      </form>
    </div>";

    // --------------------------------
    // 🔔 Send Notification (inline form)
    // --------------------------------

    // Fetch notification templates from email_notifications table (notif: prefix for send_bulk_notifications.php)
    $notifTemplateOptions = "<option value='' disabled selected>— Select notification template —</option>";
    try {
        $stmtNotif = $pdo->query("
            SELECT notification_key, name, subject
            FROM email_notifications
            WHERE is_active = 1
            ORDER BY name ASC
        ");
        $notifTemplates = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);
        foreach ($notifTemplates as $tpl) {
            $key  = htmlspecialchars('notif:' . $tpl['notification_key'], ENT_QUOTES);
            $name = htmlspecialchars($tpl['name'], ENT_QUOTES);
            $subj = htmlspecialchars($tpl['subject'] ?? $tpl['notification_key'], ENT_QUOTES);
            $notifTemplateOptions .= "<option value='{$key}'>{$name} — {$subj}</option>";
        }
    } catch (Exception $e) {
        // email_notifications table unavailable — leave the select empty
    }

    $sendNotifHTML = "
    <div class='p-1'>
      <div class='alert alert-info py-2 mb-3'>
        <i class='anticon anticon-notification mr-1'></i>
        Send notification to: <strong>{$userNameEsc}</strong>
      </div>
      <form id='modalSendNotifForm'>
        <input type='hidden' name='user_id' value='{$user['id']}'>
        <input type='hidden' name='user_email' value='" . htmlspecialchars($user['email'], ENT_QUOTES) . "'>
        <div class='form-group'>
          <label class='small font-weight-bold'>Notification Template <span class='text-danger'>*</span></label>
          <select class='form-control' name='template_key' id='modalNotifTemplate' required>
            {$notifTemplateOptions}
          </select>
          <small class='text-muted'>Subject and content are automatically taken from the selected notification template.</small>
        </div>
        <button type='submit' class='btn btn-primary btn-block' id='modalSendNotifBtn'>
          <i class='anticon anticon-notification mr-1'></i> Send Notification
        </button>
      </form>
    </div>";

    // --------------------------------
    // ✅ Final Response
    // --------------------------------
    
    // Build case_summary for classification page modal
    $caseSummary = [
        'total_cases' => $caseStats['total_cases'],
        'processing' => $caseStats['processing'],
        'approved' => $caseStats['approved'],
        'closed' => $caseStats['closed'],
        'total_reported' => $caseStats['total_reported'],
        'total_recovered' => $caseStats['total_recovered']
    ];
    
    ob_clean(); // discard any PHP warning/notice output so JSON is not corrupted
    echo json_encode([
        'success' => true,
        // Raw data for classification page modals
        'user' => $user,
        'package_info' => null,
        'case_summary' => $caseSummary,
        // HTML for admin_users.php modal
        'html' => [
            'basic'            => $basicHTML,
            'onboarding'       => $onboardingHTML,
            'kyc'              => $kycHTML,
            'payments'         => $paymentsHTML,
            'transactions'     => $transactionsHTML,
            'cases'            => $casesHTML,
            'tickets'          => $ticketsHTML,
            'email_logs'       => $emailLogsHTML,
            'send_email'       => $sendEmailHTML,
            'send_notification'=> $sendNotifHTML,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
