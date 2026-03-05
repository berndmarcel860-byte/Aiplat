<?php
// === ENABLE PHP ERRORS (TEMPORARILY) ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../admin_session.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'html' => []];



try {
    if (empty($_GET['user_id'])) {
        throw new Exception('User ID missing.');
    }

    $user_id = (int) $_GET['user_id'];

    // BASIC INFO
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) throw new Exception('User not found.');

    ob_start(); ?>
    <div class="table-responsive">
        <table class="table table-bordered">
            <tr><th>ID</th><td><?= htmlspecialchars($user['id']); ?></td></tr>
            <tr><th>Name</th><td><?= htmlspecialchars($user['first_name'].' '.$user['last_name']); ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($user['email']); ?></td></tr>
            <tr><th>Status</th><td><?= htmlspecialchars($user['status']); ?></td></tr>
            <tr><th>Balance</th><td>$<?= number_format($user['balance'], 2); ?></td></tr>
            <tr><th>Created</th><td><?= htmlspecialchars($user['created_at']); ?></td></tr>
        </table>
    </div>
    <?php
    $response['html']['basic'] = ob_get_clean();

    // Helper to render any table
    function renderTable($rows, $title) {
        if (empty($rows)) return "<p class='text-muted'>No data for {$title}.</p>";
        $html = "<div class='table-responsive'><table class='table table-bordered table-sm'>";
        $html .= "<thead><tr>";
        foreach (array_keys($rows[0]) as $c) $html .= "<th>".htmlspecialchars($c)."</th>";
        $html .= "</tr></thead><tbody>";
        foreach ($rows as $r) {
            $html .= "<tr>";
            foreach ($r as $v) {
                $html .= "<td>".htmlspecialchars((string)$v)."</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</tbody></table></div>";
        return $html;
    }

    // === Onboarding
    $stmt = $pdo->prepare("SELECT * FROM user_onboarding WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $response['html']['onboarding'] = renderTable($stmt->fetchAll(PDO::FETCH_ASSOC), 'Onboarding');

    // === KYC
    $stmt = $pdo->prepare("SELECT * FROM kyc_verification_requests WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $response['html']['kyc'] = renderTable($stmt->fetchAll(PDO::FETCH_ASSOC), 'KYC');

    // === Payments
    $stmt = $pdo->prepare("SELECT * FROM user_payment_methods WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $response['html']['payments'] = renderTable($stmt->fetchAll(PDO::FETCH_ASSOC), 'Payments');

    // === Transactions
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $response['html']['transactions'] = renderTable($stmt->fetchAll(PDO::FETCH_ASSOC), 'Transactions');

    // === Cases (fix: platform instead of platform_id)
    $stmt = $pdo->prepare("SELECT case_number, platform_id, reported_amount, recovered_amount, status, description, created_at FROM cases WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $response['html']['cases'] = renderTable($stmt->fetchAll(PDO::FETCH_ASSOC), 'Cases');

    // === Tickets (fix: id instead of ticket_id)
    $stmt = $pdo->prepare("SELECT id, subject, status, created_at, updated_at FROM support_tickets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $response['html']['tickets'] = renderTable($stmt->fetchAll(PDO::FETCH_ASSOC), 'Support Tickets');

    $response['success'] = true;

} catch (Exception $e) {
    $response['html']['basic'] = "<div class='alert alert-danger'>Error: ".htmlspecialchars($e->getMessage())."</div>";
}

echo json_encode($response);
?>

