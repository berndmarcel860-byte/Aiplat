<?php
require_once 'admin_header.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get admin data
$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

$currentAdminId = (int)$_SESSION['admin_id'];
$currentAdminRole = $admin['role'] ?? 'admin';

// Role-based filtering: superadmin sees all, admin sees only their own
if ($currentAdminRole === 'superadmin') {
    // Superadmin: see ALL data (no admin_id filtering)
    $stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'active_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
        'new_users_today' => $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
        'new_users_week' => $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn(),
        'active_cases' => $pdo->query("SELECT COUNT(*) FROM cases WHERE status NOT IN ('closed', 'refund_rejected')")->fetchColumn(),
        'total_cases' => $pdo->query("SELECT COUNT(*) FROM cases")->fetchColumn(),
        'pending_withdrawals' => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn(),
        'pending_deposits' => $pdo->query("SELECT COUNT(*) FROM deposits WHERE status = 'pending'")->fetchColumn(),
        'total_recovered' => $pdo->query("SELECT COALESCE(SUM(recovered_amount), 0) FROM cases")->fetchColumn(),
        'total_reported' => $pdo->query("SELECT COALESCE(SUM(reported_amount), 0) FROM cases")->fetchColumn(),
        'pending_kyc' => $pdo->query("SELECT COUNT(*) FROM kyc_verification_requests WHERE status = 'pending'")->fetchColumn(),
        'active_packages' => $pdo->query("SELECT COUNT(*) FROM user_packages WHERE status = 'active'")->fetchColumn(),
        'expired_packages' => $pdo->query("SELECT COUNT(*) FROM user_packages WHERE status = 'expired'")->fetchColumn(),
        'total_balance' => $pdo->query("SELECT COALESCE(SUM(balance), 0) FROM users")->fetchColumn(),
        'emails_sent_today' => $pdo->query("SELECT COUNT(*) FROM email_logs WHERE DATE(sent_at) = CURDATE()")->fetchColumn(),
        'withdrawals_approved_today' => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'approved' AND DATE(updated_at) = CURDATE()")->fetchColumn(),
    ];
    
    // Get recent activities from audit_logs - all admins
    $activities = $pdo->query("
        SELECT 
            al.id,
            al.action,
            al.entity_type,
            al.entity_id,
            al.old_value,
            al.new_value,
            al.created_at,
            al.ip_address,
            CONCAT(a.first_name, ' ', a.last_name) as admin_name
        FROM audit_logs al
        LEFT JOIN admins a ON al.admin_id = a.id
        ORDER BY al.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // Get recent cases - all cases
    $recentCases = $pdo->query("
        SELECT c.*, u.first_name, u.last_name, p.name as platform_name
        FROM cases c
        JOIN users u ON c.user_id = u.id
        JOIN scam_platforms p ON c.platform_id = p.id
        ORDER BY c.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // Get recent users - all users
    $recentUsers = $pdo->query("
        SELECT u.*, 
               (SELECT COUNT(*) FROM cases WHERE user_id = u.id) as cases_count,
               (SELECT status FROM user_packages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as package_status
        FROM users u
        ORDER BY u.created_at DESC
        LIMIT 5
    ")->fetchAll();
} else {
    // Admin: see only their own data (filtered by admin_id)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE admin_id = ?");
    $stmt->execute([$currentAdminId]);
    $total_users = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'active' AND admin_id = ?");
    $stmt->execute([$currentAdminId]);
    $active_users = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE() AND admin_id = ?");
    $stmt->execute([$currentAdminId]);
    $new_users_today = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND admin_id = ?");
    $stmt->execute([$currentAdminId]);
    $new_users_week = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE status NOT IN ('closed', 'refund_rejected') AND admin_id = ?");
    $stmt->execute([$currentAdminId]);
    $active_cases = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE admin_id = ?");
    $stmt->execute([$currentAdminId]);
    $total_cases = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(recovered_amount), 0) FROM cases WHERE admin_id = ?");
    $stmt->execute([$currentAdminId]);
    $total_recovered = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(reported_amount), 0) FROM cases WHERE admin_id = ?");
    $stmt->execute([$currentAdminId]);
    $total_reported = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) FROM users WHERE admin_id = ?");
    $stmt->execute([$currentAdminId]);
    $total_balance = $stmt->fetchColumn();
    
    $stats = [
        'total_users' => $total_users,
        'active_users' => $active_users,
        'new_users_today' => $new_users_today,
        'new_users_week' => $new_users_week,
        'active_cases' => $active_cases,
        'total_cases' => $total_cases,
        'pending_withdrawals' => 0, // Withdrawals not admin-specific
        'pending_deposits' => 0, // Deposits not admin-specific
        'total_recovered' => $total_recovered,
        'total_reported' => $total_reported,
        'pending_kyc' => 0, // KYC not admin-specific
        'active_packages' => 0, // Packages not admin-specific
        'expired_packages' => 0, // Packages not admin-specific
        'total_balance' => $total_balance,
        'emails_sent_today' => 0, // Email logs not admin-specific in current schema
        'withdrawals_approved_today' => 0, // Withdrawals not admin-specific
    ];
    
    // Get recent activities - only this admin's actions
    $stmt = $pdo->prepare("
        SELECT 
            al.id,
            al.action,
            al.entity_type,
            al.entity_id,
            al.old_value,
            al.new_value,
            al.created_at,
            al.ip_address,
            CONCAT(a.first_name, ' ', a.last_name) as admin_name
        FROM audit_logs al
        LEFT JOIN admins a ON al.admin_id = a.id
        WHERE al.admin_id = ?
        ORDER BY al.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$currentAdminId]);
    $activities = $stmt->fetchAll();
    
    // Get recent cases - only this admin's cases
    $stmt = $pdo->prepare("
        SELECT c.*, u.first_name, u.last_name, p.name as platform_name
        FROM cases c
        JOIN users u ON c.user_id = u.id
        JOIN scam_platforms p ON c.platform_id = p.id
        WHERE c.admin_id = ?
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$currentAdminId]);
    $recentCases = $stmt->fetchAll();
    
    // Get recent users - only this admin's users
    $stmt = $pdo->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM cases WHERE user_id = u.id) as cases_count,
               (SELECT status FROM user_packages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as package_status
        FROM users u
        WHERE u.admin_id = ?
        ORDER BY u.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$currentAdminId]);
    $recentUsers = $stmt->fetchAll();
}

// Calculate recovery rate
$recoveryRate = $stats['total_reported'] > 0 ? ($stats['total_recovered'] / $stats['total_reported']) * 100 : 0;

// Get pending items that need attention
$pendingItems = [
    'withdrawals' => $stats['pending_withdrawals'],
    'deposits' => $stats['pending_deposits'],
    'kyc' => $stats['pending_kyc'],
];
$totalPending = array_sum($pendingItems);
?>

                <!-- Content Wrapper START -->
                <div class="main-content">
                    <!-- Welcome Message -->
                    <div class="page-header">
                        <h2 class="header-title">Welcome back, <?= htmlspecialchars($admin['first_name']) ?></h2>
                        <p class="header-sub-title">Here's what's happening with your platform today</p>
                    </div>

                    <!-- Alert for Pending Items -->
                    <?php if ($totalPending > 0): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="anticon anticon-exclamation-circle"></i>
                        <strong>Attention Required:</strong> You have <?= $totalPending ?> pending item(s): 
                        <?php if ($pendingItems['withdrawals'] > 0): ?>
                            <a href="admin_withdrawals.php?status=pending" class="alert-link"><?= $pendingItems['withdrawals'] ?> withdrawal(s)</a>
                        <?php endif; ?>
                        <?php if ($pendingItems['deposits'] > 0): ?>
                            <a href="admin_deposits.php?status=pending" class="alert-link"><?= $pendingItems['deposits'] ?> deposit(s)</a>
                        <?php endif; ?>
                        <?php if ($pendingItems['kyc'] > 0): ?>
                            <a href="admin_kyc.php" class="alert-link"><?= $pendingItems['kyc'] ?> KYC request(s)</a>
                        <?php endif; ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                    <?php endif; ?>

                    <!-- Main Stats Cards Row 1 -->
                    <div class="row">
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-blue">
                                            <i class="anticon anticon-team card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($stats['total_users']) ?></h2>
                                            <p class="m-b-0 text-muted">Total Users</p>
                                            <small class="text-success">+<?= $stats['new_users_today'] ?> today</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-cyan">
                                            <i class="anticon anticon-folder-open card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($stats['active_cases']) ?></h2>
                                            <p class="m-b-0 text-muted">Active Cases</p>
                                            <small class="text-muted"><?= $stats['total_cases'] ?> total</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-gold">
                                            <i class="anticon anticon-arrow-up card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($stats['pending_withdrawals']) ?></h2>
                                            <p class="m-b-0 text-muted">Pending Withdrawals</p>
                                            <small class="text-success"><?= $stats['withdrawals_approved_today'] ?> approved today</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-purple">
                                            <i class="anticon anticon-dollar card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0">€<?= number_format($stats['total_recovered'], 2) ?></h2>
                                            <p class="m-b-0 text-muted">Total Recovered</p>
                                            <small class="text-info"><?= number_format($recoveryRate, 1) ?>% rate</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards Row 2 -->
                    <div class="row">
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-green">
                                            <i class="anticon anticon-gift card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($stats['active_packages']) ?></h2>
                                            <p class="m-b-0 text-muted">Active Packages</p>
                                            <small class="text-danger"><?= $stats['expired_packages'] ?> expired</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-orange">
                                            <i class="anticon anticon-arrow-down card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($stats['pending_deposits']) ?></h2>
                                            <p class="m-b-0 text-muted">Pending Deposits</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-teal">
                                            <i class="anticon anticon-safety-certificate card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($stats['pending_kyc']) ?></h2>
                                            <p class="m-b-0 text-muted">Pending KYC</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-pink">
                                            <i class="anticon anticon-mail card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($stats['emails_sent_today']) ?></h2>
                                            <p class="m-b-0 text-muted">Emails Sent Today</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cryptocurrency Stats Row -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="m-t-20 m-b-15">Cryptocurrency Overview</h5>
                        </div>
                        <?php
                        // Get crypto statistics
                        try {
                            $cryptoStats = [
                                'crypto_cases' => $pdo->query("SELECT COUNT(*) FROM cases WHERE currency_type = 'crypto'")->fetchColumn(),
                                'crypto_withdrawals' => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE currency_type = 'crypto' AND status = 'pending'")->fetchColumn(),
                                'active_cryptos' => $pdo->query("SELECT COUNT(*) FROM cryptocurrencies WHERE is_active = 1")->fetchColumn(),
                                'top_crypto' => $pdo->query("SELECT c.symbol, c.name, COUNT(cs.id) as case_count FROM cases cs LEFT JOIN cryptocurrencies c ON cs.crypto_currency_id = c.id WHERE cs.currency_type = 'crypto' GROUP BY cs.crypto_currency_id ORDER BY case_count DESC LIMIT 1")->fetch()
                            ];
                        } catch (PDOException $e) {
                            $cryptoStats = ['crypto_cases' => 0, 'crypto_withdrawals' => 0, 'active_cryptos' => 0, 'top_crypto' => false];
                        }
                        ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-yellow">
                                            <i class="anticon anticon-bitcoin card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($cryptoStats['crypto_cases']) ?></h2>
                                            <p class="m-b-0 text-muted">Crypto Cases</p>
                                            <small class="text-muted">Active cryptocurrency cases</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-lime">
                                            <i class="anticon anticon-wallet card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($cryptoStats['crypto_withdrawals']) ?></h2>
                                            <p class="m-b-0 text-muted">Crypto Withdrawals</p>
                                            <small class="text-warning">Pending approval</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-indigo">
                                            <i class="anticon anticon-stock card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($cryptoStats['active_cryptos']) ?></h2>
                                            <p class="m-b-0 text-muted">Active Cryptos</p>
                                            <small class="text-muted">Supported cryptocurrencies</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-cyan">
                                            <i class="anticon anticon-trophy card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= $cryptoStats['top_crypto'] ? htmlspecialchars($cryptoStats['top_crypto']['symbol']) : 'N/A' ?></h2>
                                            <p class="m-b-0 text-muted">Top Crypto</p>
                                            <small class="text-muted"><?= $cryptoStats['top_crypto'] ? htmlspecialchars($cryptoStats['top_crypto']['case_count']) . ' cases' : 'No data' ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary Card -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="m-b-0">Financial Summary</h5>
                                    <div class="btn-group btn-group-sm">
                                        <a href="admin_reports.php" class="btn btn-default">View Reports</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 text-center border-right">
                                            <h4 class="text-primary">€<?= number_format($stats['total_reported'], 2) ?></h4>
                                            <p class="text-muted m-b-0">Total Reported</p>
                                        </div>
                                        <div class="col-md-3 text-center border-right">
                                            <h4 class="text-success">€<?= number_format($stats['total_recovered'], 2) ?></h4>
                                            <p class="text-muted m-b-0">Total Recovered</p>
                                        </div>
                                        <div class="col-md-3 text-center border-right">
                                            <h4 class="text-info">€<?= number_format($stats['total_balance'], 2) ?></h4>
                                            <p class="text-muted m-b-0">Total User Balances</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h4 class="text-warning"><?= number_format($recoveryRate, 1) ?>%</h4>
                                            <p class="text-muted m-b-0">Recovery Rate</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Row -->
                    <div class="row">
                        <!-- Recent Activities and Cases -->
                        <div class="col-md-12 col-lg-8">
                            <!-- Recent Cases -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5>Recent Cases</h5>
                                        <a href="admin_cases.php" class="btn btn-sm btn-default">View All</a>
                                    </div>
                                    <div class="m-t-25">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Case #</th>
                                                        <th>User</th>
                                                        <th>Platform</th>
                                                        <th>Amount</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recentCases as $case): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($case['case_number']) ?></td>
                                                        <td><?= htmlspecialchars($case['first_name'] . ' ' . $case['last_name']) ?></td>
                                                        <td><?= htmlspecialchars($case['platform_name']) ?></td>
                                                        <td>$<?= number_format($case['reported_amount'], 2) ?></td>
                                                        <td>
                                                            <?php
                                                            $statusClass = [
                                                                'open' => 'warning',
                                                                'documents_required' => 'info',
                                                                'under_review' => 'primary',
                                                                'refund_approved' => 'success',
                                                                'refund_rejected' => 'danger',
                                                                'closed' => 'secondary'
                                                            ][$case['status']] ?? 'light';
                                                            ?>
                                                            <span class="badge case-status-badge badge-<?= $statusClass ?>">
                                                                <?= ucwords(str_replace('_', ' ', $case['status'])) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activities -->
                            <div class="card m-t-20">
                                <div class="card-body">
                                    <h5>Recent Activities</h5>
                                    <div class="m-t-20">
                                        <?php foreach ($activities as $activity): ?>
                                        <div class="activity-item">
                                            <div class="d-flex justify-content-between">
                                                <p class="m-b-5">
                                                    <?php if ($activity['admin_name']): ?>
                                                    <strong><?= htmlspecialchars($activity['admin_name']) ?></strong>
                                                    <?php else: ?>
                                                    <strong>System</strong>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($activity['action']) ?>
                                                    <?php if ($activity['entity_type']): ?>
                                                    on <?= htmlspecialchars($activity['entity_type']) ?>
                                                    <?php endif; ?>
                                                    <?php if ($activity['entity_id']): ?>
                                                    #<?= htmlspecialchars($activity['entity_id']) ?>
                                                    <?php endif; ?>
                                                </p>
                                                <span class="activity-time">
                                                    <?= date('M j, H:i', strtotime($activity['created_at'])) ?>
                                                </span>
                                            </div>
                                            <?php if ($activity['old_value'] || $activity['new_value']): ?>
                                            <p class="text-muted m-b-0">
                                                <?php if ($activity['old_value']): ?>
                                                <span class="old-value"><?= htmlspecialchars($activity['old_value']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($activity['old_value'] && $activity['new_value']): ?>
                                                <i class="anticon anticon-arrow-right"></i>
                                                <?php endif; ?>
                                                <?php if ($activity['new_value']): ?>
                                                <span class="new-value"><?= htmlspecialchars($activity['new_value']) ?></span>
                                                <?php endif; ?>
                                            </p>
                                            <?php endif; ?>
                                            <?php if ($activity['ip_address']): ?>
                                            <small class="ip-address">IP: <?= htmlspecialchars($activity['ip_address']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recovery Stats and Quick Actions -->
                        <div class="col-md-12 col-lg-4">
                            <!-- Recovery Progress -->
                            <div class="card">
                                <div class="card-body">
                                    <h5>Recovery Progress</h5>
                                    <div class="m-t-20">
                                        <canvas id="recoveryChart" height="200"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="card m-t-20">
                                <div class="card-body">
                                    <h5>Quick Actions</h5>
                                    <div class="m-t-20">
                                        <a href="admin_cases.php?action=new" class="btn btn-block btn-primary m-b-10">
                                            <i class="anticon anticon-plus"></i> Create New Case
                                        </a>
                                        <a href="admin_users.php" class="btn btn-block btn-default m-b-10">
                                            <i class="anticon anticon-team"></i> Manage Users
                                        </a>
                                        <a href="admin_user_packages.php" class="btn btn-block btn-default m-b-10">
                                            <i class="anticon anticon-gift"></i> User Packages
                                        </a>
                                        <a href="admin_user_classification.php" class="btn btn-block btn-default m-b-10">
                                            <i class="anticon anticon-filter"></i> User Classification
                                        </a>
                                        <a href="admin_withdrawals.php?status=pending" class="btn btn-block btn-warning m-b-10">
                                            <i class="anticon anticon-arrow-up"></i> Process Withdrawals
                                            <?php if ($stats['pending_withdrawals'] > 0): ?>
                                            <span class="badge badge-light"><?= $stats['pending_withdrawals'] ?></span>
                                            <?php endif; ?>
                                        </a>
                                        <a href="admin_deposits.php?status=pending" class="btn btn-block btn-info m-b-10">
                                            <i class="anticon anticon-arrow-down"></i> Process Deposits
                                            <?php if ($stats['pending_deposits'] > 0): ?>
                                            <span class="badge badge-light"><?= $stats['pending_deposits'] ?></span>
                                            <?php endif; ?>
                                        </a>
                                        <a href="admin_kyc.php" class="btn btn-block btn-success m-b-10">
                                            <i class="anticon anticon-safety-certificate"></i> Review KYC
                                            <?php if ($stats['pending_kyc'] > 0): ?>
                                            <span class="badge badge-light"><?= $stats['pending_kyc'] ?></span>
                                            <?php endif; ?>
                                        </a>
                                        <a href="admin_email_templates.php" class="btn btn-block btn-default m-b-10">
                                            <i class="anticon anticon-mail"></i> Email Templates
                                        </a>
                                        <a href="admin_settings.php" class="btn btn-block btn-default">
                                            <i class="anticon anticon-setting"></i> System Settings
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Users Section -->
                    <div class="row m-t-20">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5>Recent Users</h5>
                                        <a href="admin_users.php" class="btn btn-sm btn-default">View All</a>
                                    </div>
                                    <div class="m-t-25">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>User</th>
                                                        <th>Email</th>
                                                        <th>Status</th>
                                                        <th>Balance</th>
                                                        <th>Package</th>
                                                        <th>Cases</th>
                                                        <th>Registered</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recentUsers as $user): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                                        <td>
                                                            <span class="badge badge-<?= $user['status'] === 'active' ? 'success' : 'warning' ?>">
                                                                <?= ucfirst($user['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td>€<?= number_format($user['balance'] ?? 0, 2) ?></td>
                                                        <td>
                                                            <?php if ($user['package_status']): ?>
                                                            <span class="badge badge-<?= $user['package_status'] === 'active' ? 'success' : 'danger' ?>">
                                                                <?= ucfirst($user['package_status']) ?>
                                                            </span>
                                                            <?php else: ?>
                                                            <span class="badge badge-secondary">None</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= $user['cases_count'] ?></td>
                                                        <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Content Wrapper END -->

                <?php require_once 'admin_footer.php'; ?>
            </div>
            <!-- Page Container END -->

    
    <!-- Page JS -->
    <script>
    $(document).ready(function() {
        // Initialize recovery chart
        const ctx = document.getElementById('recoveryChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Recovered', 'Pending', 'Lost'],
                datasets: [{
                    data: [<?= $stats['total_recovered'] ?>, 50000, 25000], // Example data
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom'
                },
                cutoutPercentage: 70
            }
        });

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
    </script>
</body>
</html>