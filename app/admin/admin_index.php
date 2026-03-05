<?php
require_once 'admin_session.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get admin data
$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

// Get stats for dashboard
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active_cases' => $pdo->query("SELECT COUNT(*) FROM cases WHERE status NOT IN ('closed', 'refund_rejected')")->fetchColumn(),
    'pending_withdrawals' => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn(),
    'total_recovered' => $pdo->query("SELECT SUM(recovered_amount) FROM cases")->fetchColumn()
];

// Get recent activities
$activities = $pdo->query("
    SELECT al.*, a.first_name, a.last_name 
    FROM admin_logs al
    LEFT JOIN admins a ON al.admin_id = a.id
    ORDER BY al.created_at DESC
    LIMIT 5
")->fetchAll();

// Get recent cases
$recentCases = $pdo->query("
    SELECT c.*, u.first_name, u.last_name, p.name as platform_name
    FROM cases c
    JOIN users u ON c.user_id = u.id
    JOIN scam_platforms p ON c.platform_id = p.id
    ORDER BY c.created_at DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Dashboard | Scam Recovery</title>
    <link href="../assets/css/app.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.css">
    <style>
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .card-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
        .activity-item {
            border-left: 3px solid #5c6bc0;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .case-status-badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="layout">
            <?php require_once 'admin_header.php'; ?>
            <?php require_once 'admin_sidebar.php'; ?>

            <!-- Page Container START -->
            <div class="page-container">
                <!-- Content Wrapper START -->
                <div class="main-content">
                    <!-- Welcome Message -->
                    <div class="page-header">
                        <h2 class="header-title">Welcome back, <?= htmlspecialchars($admin['first_name']) ?></h2>
                        <p class="header-sub-title">Here's what's happening with your platform today</p>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row">
                        <div class="col-md-6 col-lg-3">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-icon avatar-lg avatar-blue">
                                            <i class="anticon anticon-user card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($stats['total_users']) ?></h2>
                                            <p class="m-b-0 text-muted">Total Users</p>
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
                                            <i class="anticon anticon-file card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($stats['active_cases']) ?></h2>
                                            <p class="m-b-0 text-muted">Active Cases</p>
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
                                            <i class="anticon anticon-transaction card-icon"></i>
                                        </div>
                                        <div class="m-l-15">
                                            <h2 class="m-b-0"><?= number_format($stats['pending_withdrawals']) ?></h2>
                                            <p class="m-b-0 text-muted">Pending Withdrawals</p>
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
                                            <h2 class="m-b-0">$<?= number_format($stats['total_recovered'], 2) ?></h2>
                                            <p class="m-b-0 text-muted">Total Recovered</p>
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
                                                    <strong><?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?></strong>
                                                    <?= htmlspecialchars($activity['action']) ?>
                                                </p>
                                                <span class="activity-time">
                                                    <?= date('M j, H:i', strtotime($activity['created_at'])) ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($activity['details'])): ?>
                                            <p class="text-muted m-b-0"><?= htmlspecialchars($activity['details']) ?></p>
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
                                        <a href="admin_new_case.php" class="btn btn-block btn-primary m-b-10">
                                            <i class="anticon anticon-plus"></i> Create New Case
                                        </a>
                                        <a href="admin_users.php" class="btn btn-block btn-default m-b-10">
                                            <i class="anticon anticon-user"></i> Manage Users
                                        </a>
                                        <a href="admin_withdrawals.php" class="btn btn-block btn-default m-b-10">
                                            <i class="anticon anticon-transaction"></i> Process Withdrawals
                                        </a>
                                        <a href="admin_settings.php" class="btn btn-block btn-default">
                                            <i class="anticon anticon-setting"></i> System Settings
                                        </a>
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
        </div>
    </div>

    <!-- Core Vendors JS -->
    <script src="../assets/js/vendors.min.js"></script>

    <!-- Chart JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

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