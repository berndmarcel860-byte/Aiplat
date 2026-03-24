<?php
ob_start();

require_once 'session.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include tracking function
require_once 'tracking.php';

// Onboarding check
if (!isset($_SESSION['onboarding_completed'])) {
    $stmt = $pdo->prepare("SELECT completed FROM user_onboarding WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    if (!$result || !$result['completed']) {
        $_SESSION['onboarding_completed'] = false;
        echo '<script>window.location.href = "onboarding.php";</script>';
        exit();
    } else {
        $_SESSION['onboarding_completed'] = true;
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT first_name, last_name, balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$avatar = 'assets/images/avatars/avatar.png';

// Get unread notifications count
$notificationCount = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $notificationCount = $stmt->fetchColumn();
} catch (PDOException $e) {
    // If notifications table doesn't exist yet, default to 0
    $notificationCount = 0;
}

// Get recent notifications
$recentNotifications = [];
try {
    $stmt = $pdo->prepare("SELECT id, title, message, type, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $recentNotifications = $stmt->fetchAll();
} catch (PDOException $e) {
    // If notifications table doesn't exist yet, default to empty array
    $recentNotifications = [];
}

// Track user activity if logged in
if (isset($_SESSION['user_id'])) {
    $currentUrl = $_SERVER['REQUEST_URI'];
    $httpMethod = $_SERVER['REQUEST_METHOD'];
    
    // Exclude tracking for certain pages if needed
    $excludedPages = ['/ajax/', '/user_ajax/'];
    $shouldTrack = true;
    
    foreach ($excludedPages as $excluded) {
        if (strpos($currentUrl, $excluded) !== false) {
            $shouldTrack = false;
            break;
        }
    }
    
    if ($shouldTrack) {
        trackUserActivity($_SESSION['user_id'], $currentUrl, $httpMethod);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Schadenswiederherstellung Dashboard</title>
    <link rel="shortcut icon" href="assets/images/logo/favicon.png">
    
    <!-- Core CSS -->
    <link href="assets/css/app.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.11.3/datatables.min.css"/>
    
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>
    <div class="app">
        <div class="layout">
            <!-- Header START -->
            <div class="header">
                <div class="logo logo-dark">
                    <a href="index.php">
                        <img src="assets/images/logo/logo.png" alt="Logo">
                        <img class="logo-fold" src="assets/images/logo/logo-fold.png" alt="Logo">
                    </a>
                </div>
                
                <div class="nav-wrap">
                    <ul class="nav-left">
                        <li class="desktop-toggle">
                            <a href="javascript:void(0);" id="toggle-sidebar">
                                <i class="anticon anticon-menu"></i>
                            </a>
                        </li>
                        <li class="mobile-toggle">
                            <a href="javascript:void(0);" id="toggle-mobile-sidebar">
                                <i class="anticon anticon-menu"></i>
                            </a>
                        </li>
                    </ul>
                    
                    <ul class="nav-right">
                        <!-- Notification Dropdown -->
                        <li class="dropdown dropdown-animated scale-left">
                            <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="anticon anticon-bell font-size-18"></i>
                                <?php if ($notificationCount > 0): ?>
                                    <span class="badge badge-danger badge-dot"></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu pop-notification">
                                <div class="p-v-15 p-h-25 border-bottom d-flex justify-content-between align-items-center">
                                    <p class="text-dark font-weight-semibold m-b-0">
                                        <i class="anticon anticon-bell"></i>
                                        <span class="m-l-10">Benachrichtigungen</span>
                                    </p>
                                    <?php if ($notificationCount > 0): ?>
                                        <span class="badge badge-primary"><?= $notificationCount ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="relative">
                                    <div class="overflow-y-auto relative scrollable" style="max-height: 300px;">
                                        <?php if (count($recentNotifications) > 0): ?>
                                            <?php foreach ($recentNotifications as $notification): ?>
                                                <a href="notifications.php" class="dropdown-item d-block p-15 border-bottom">
                                                    <div class="d-flex">
                                                        <div class="m-r-15">
                                                            <?php
                                                            $iconClass = 'info-circle';
                                                            $colorClass = 'text-primary';
                                                            if ($notification['type'] == 'success') {
                                                                $iconClass = 'check-circle';
                                                                $colorClass = 'text-success';
                                                            } elseif ($notification['type'] == 'warning') {
                                                                $iconClass = 'warning';
                                                                $colorClass = 'text-warning';
                                                            } elseif ($notification['type'] == 'danger') {
                                                                $iconClass = 'exclamation-circle';
                                                                $colorClass = 'text-danger';
                                                            }
                                                            ?>
                                                            <i class="anticon anticon-<?= $iconClass ?> font-size-20 <?= $colorClass ?>"></i>
                                                        </div>
                                                        <div>
                                                            <p class="m-b-0 text-dark font-weight-semibold"><?= htmlspecialchars($notification['title']) ?></p>
                                                            <p class="m-b-0 opacity-07 font-size-13"><?= htmlspecialchars(substr($notification['message'], 0, 60)) ?><?= strlen($notification['message']) > 60 ? '...' : '' ?></p>
                                                            <small class="opacity-05"><?= date('M d, Y H:i', strtotime($notification['created_at'])) ?></small>
                                                        </div>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="p-20 text-center">
                                                <i class="anticon anticon-inbox font-size-30 opacity-04"></i>
                                                <p class="m-t-10 opacity-07">No notifications</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="p-10 border-top text-center">
                                    <a href="notifications.php" class="text-primary font-weight-semibold">View all notifications</a>
                                </div>
                            </div>
                        </li>
                        
                        <!-- Profile Dropdown -->
                        <li class="dropdown dropdown-animated scale-left">
                            <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown">
                                <div class="avatar avatar-image m-h-10 m-r-15">
                                    <img src="<?= htmlspecialchars($avatar) ?>" alt="Profile">
                                </div>
                            </a>
                            <div class="dropdown-menu pop-profile">
                                <div class="p-h-20 p-b-15 m-b-10 border-bottom">
                                    <div class="d-flex m-r-50">
                                        <div class="avatar avatar-lg avatar-image">
                                            <img src="<?= htmlspecialchars($avatar) ?>" alt="Profile">
                                        </div>
                                        <div class="m-l-10">
                                            <p class="m-b-0 text-dark font-weight-semibold">
                                                <?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?>
                                            </p>
                                            <p class="m-b-0 opacity-07">Member</p>
                                        </div>
                                    </div>
                                </div>
                                <a href="profile.php" class="dropdown-item d-block p-h-15 p-v-10">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="anticon opacity-04 font-size-16 anticon-user"></i>
                                            <span class="m-l-10">Profil</span>
                                        </div>
                                        <i class="anticon font-size-10 anticon-right"></i>
                                    </div>
                                </a>
                                <a href="settings.php" class="dropdown-item d-block p-h-15 p-v-10">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="anticon opacity-04 font-size-16 anticon-setting"></i>
                                            <span class="m-l-10">Einstellungen</span>
                                        </div>
                                        <i class="anticon font-size-10 anticon-right"></i>
                                    </div>
                                </a>
                                <a href="logout.php" class="dropdown-item d-block p-h-15 p-v-10">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="anticon opacity-04 font-size-16 anticon-logout"></i>
                                            <span class="m-l-10">Abmelden</span>
                                        </div>
                                        <i class="anticon font-size-10 anticon-right"></i>
                                    </div>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Header END -->
            
            <!-- Sidebar START -->
            <?php include 'sidebar.php'; ?>
            <!-- Sidebar END -->

<!-- Page Container START -->
<div class="page-container">