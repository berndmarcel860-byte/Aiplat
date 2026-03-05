<?php
// =======================================================
// 🔍 get_user.php — fetch full user details for admin modal
// =======================================================
require_once '../../config.php';
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
        throw new Exception('Invalid user ID');
    }

    $userId = (int) $_GET['id'];

    // --------------------------------
    // 🧑 Basic Info
    // --------------------------------
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, status, balance, created_at 
                           FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception('User not found');
    }

    $basicHTML = "
        <table class='table'>
            <tr><th>ID</th><td>{$user['id']}</td></tr>
            <tr><th>Name</th><td>{$user['first_name']} {$user['last_name']}</td></tr>
            <tr><th>Email</th><td>{$user['email']}</td></tr>
            <tr><th>Status</th><td>{$user['status']}</td></tr>
            <tr><th>Balance</th><td>$" . number_format($user['balance'], 2) . "</td></tr>
            <tr><th>Registered</th><td>{$user['created_at']}</td></tr>
        </table>
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
        $onboardingHTML = "<div class='text-muted'>No onboarding record found.</div>";
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
        $kycHTML = "<div class='text-muted'>No KYC submitted yet.</div>";
    }

    // --------------------------------
    // 💳 Payments (from transactions)
    // --------------------------------
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? AND type IN ('deposit', 'withdrawal', 'refund') ORDER BY created_at DESC LIMIT 25");
    $stmt->execute([$userId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($payments) {
        $rows = '';
        foreach ($payments as $p) {
            $rows .= "<tr>
                <td>{$p['type']}</td>
                <td>€" . number_format($p['amount'], 2) . "</td>
                <td>{$p['status']}</td>
                <td>{$p['created_at']}</td>
            </tr>";
        }
        $paymentsHTML = "<table class='table'><thead><tr><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>$rows</tbody></table>";
    } else {
        $paymentsHTML = "<div class='text-muted'>No payments found.</div>";
    }

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
    // ✅ Final Response
    // --------------------------------
    echo json_encode([
        'success' => true,
        'html' => [
            'basic' => $basicHTML,
            'onboarding' => $onboardingHTML,
            'kyc' => $kycHTML,
            'payments' => $paymentsHTML,
            'transactions' => $transactionsHTML,
            'cases' => $casesHTML,
            'tickets' => $ticketsHTML
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

