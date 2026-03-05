<!-- admin_header.php -->
<?php
require_once 'admin_session.php';

// Get admin data
$admin = [];
if (is_admin_logged_in()) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch() ?: [];
    
    $admin = array_merge([
        'first_name' => 'Admin',
        'last_name' => 'User',
        'role' => 'admin'
    ], $admin);
}

$avatarPath = file_exists('../assets/images/avatars/default.jpg') 
    ? '../assets/images/avatars/default.jpg'
    : 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZD0iTTEyLDRBNiw2IDAgMCwxIDE4LDEwVjE3SDE4VjE5QTE5LDE5IDAgMCwxIDYsMTlWMTdINlYxMEE2LDYgMCAwLDEgMTIsNE0xMiw2QTQsNCAwIDAsMCA4LDEwVjE3QTE3LDE3IDAgMCwwIDEyLDE3QTE3LDE3IDAgMCwwIDE2LDE3VjEwQTQsNCAwIDAsMCAxMiw2TTEyLDhBMiwyIDAgMCwxIDE0LDEwQTIsMiAwIDAsMSAxMiwxMkEyLDIgMCAwLDEgMTAsMTBBMiwyIDAgMCwxIDEyLDhNMTIsMTRBNSw1IDAgMCwxIDE3LDE5QTUsNSAwIDAsMSAxMiwyNEE1LDUgMCAwLDEgNywxOUE1LDUgMCAwLDEgMTIsMTRaIiAvPjwvc3ZnPg==';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Admin Dashboard - Scam Recovery</title>
    <link rel="shortcut icon" href="../assets/images/logo/favicon.png">
    
    <!-- Core CSS -->
    <link href="../assets/css/app.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.11.3/datatables.min.css"/>
    
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <!-- Custom Admin CSS -->
    <style>
        /* Mobile Sidebar Styles */
        @media (max-width: 992px) {
            .side-nav {
                transform: translateX(-100%);
                transition: transform 300ms ease;
                position: fixed;
                z-index: 1050;
                height: 100vh;
                width: 280px;
                top: 0;
                left: 0;
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
            }
            
            .side-nav.mobile-open {
                transform: translateX(0);
            }
            
            .page-container {
                margin-left: 0 !important;
            }
            
            .mobile-toggle {
                display: block !important;
            }
            
            .desktop-toggle {
                display: none !important;
            }
            
            .nav-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
                display: none;
            }
            
            .nav-overlay.active {
                display: block;
            }
        }
        
        @media (min-width: 993px) {
            .mobile-toggle {
                display: none !important;
            }
            
            .desktop-toggle {
                display: block !important;
            }
        }
        
        /* Sidebar Styles */
        .side-nav {
            background: #fff;
            border-right: 1px solid #e9e9e9;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
        }
        
        .side-nav-inner {
            padding: 20px 0;
        }
        
        .side-nav-logo {
            padding: 0 24px 20px;
            display: none;
        }
        
        .side-nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-item > a {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: #595959;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-item > a:hover {
            color: #1890ff;
            background: #f0f7ff;
        }
        
        .nav-item.active > a {
            color: #1890ff;
            background: #f0f7ff;
            border-right: 3px solid #1890ff;
        }
        
        .icon-holder {
            margin-right: 10px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .dropdown-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            display: none;
            background: #fafafa;
        }
        
        .dropdown-menu li a {
            display: block;
            padding: 10px 20px 10px 60px;
            color: #595959;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .dropdown-menu li a:hover {
            color: #1890ff;
            background: #f0f7ff;
        }
        
        .dropdown-menu li.active a {
            color: #1890ff;
        }
        
        .nav-item.dropdown.open > .dropdown-menu {
            display: block;
        }
        
        .arrow {
            margin-left: auto;
            transition: transform 0.3s;
        }
        
        .nav-item.dropdown.open > a > .arrow {
            transform: rotate(90deg);
        }
        
        /* Header Styles */
        .header {
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1030;
            height: 64px;
            padding: 0 24px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            height: 64px;
        }
        
        .logo img {
            height: 32px;
        }
        
        .logo-fold {
            display: none;
        }
        
        .nav-wrap {
            display: flex;
            justify-content: space-between;
            height: 100%;
        }
        
        .nav-left, .nav-right {
            display: flex;
            align-items: center;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-left li, .nav-right li {
            margin-right: 15px;
        }
        
        .pop-profile {
            width: 280px;
            padding: 0;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .avatar-image {
            border-radius: 50%;
            overflow: hidden;
        }
        
        .avatar-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
<div class="app">
    <div class="layout">
        <!-- Header START -->
        <div class="header">
            <div class="logo logo-dark">
                <a href="admin_dashboard.php">
                    <img src="../assets/images/logo/logo.png" alt="Logo">
                    <img class="logo-fold" src="../assets/images/logo/logo-fold.png" alt="Logo">
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
                        <a href="javascript:void(0);" id="mobile-toggle">
                            <i class="anticon anticon-menu"></i>
                        </a>
                    </li>
                </ul>
                
                <ul class="nav-right">
                    <li class="dropdown dropdown-animated scale-left">
                        <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown">
                            <div class="avatar avatar-image m-h-10 m-r-15">
                                <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Profile">
                            </div>
                            <span><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></span>
                        </a>
                        <div class="dropdown-menu pop-profile">
                            <div class="p-h-20 p-b-15 m-b-10 border-bottom">
                                <div class="d-flex m-r-50">
                                    <div class="avatar avatar-lg avatar-image">
                                        <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Profile">
                                    </div>
                                    <div class="m-l-10">
                                        <p class="m-b-0 text-dark font-weight-semibold">
                                            <?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?>
                                        </p>
                                        <p class="m-b-0 opacity-07"><?= ucfirst($admin['role']) ?></p>
                                    </div>
                                </div>
                            </div>
                            <a href="admin_profile.php" class="dropdown-item d-block p-h-15 p-v-10">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="anticon opacity-04 font-size-16 anticon-user"></i>
                                        <span class="m-l-10">Profile</span>
                                    </div>
                                    <i class="anticon font-size-10 anticon-right"></i>
                                </div>
                            </a>
                            <a href="admin_logout.php" class="dropdown-item d-block p-h-15 p-v-10">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="anticon opacity-04 font-size-16 anticon-logout"></i>
                                        <span class="m-l-10">Logout</span>
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
        
        <!-- Nav Overlay (Mobile) -->
        <div class="nav-overlay"></div>
        
<?php include_once 'admin_sidebar.php'; ?>

        <!-- Page Container START -->
        <div class="page-container">