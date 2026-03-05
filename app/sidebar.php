<!-- Side Nav START -->
<div class="side-nav">
    <div class="side-nav-inner">
        <div class="side-nav-scroll-container">
            <ul class="side-nav-menu">

                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="index.php" title="Dashboard Overview">
                        <span class="icon-holder">
                            <i class="anticon anticon-dashboard"></i>
                        </span>
                        <span class="title">Dashboard</span>
                    </a>
                </li>

                <!-- My Cases -->
                <li class="nav-item">
                    <a href="cases.php" title="Manage Your Cases">
                        <span class="icon-holder">
                            <i class="anticon anticon-folder-open"></i>
                        </span>
                        <span class="title">My Cases</span>
                    </a>
                </li>

                <!-- Transactions -->
                <li class="nav-item">
                    <a href="transactions.php" title="View Transaction History">
                        <span class="icon-holder">
                            <i class="anticon anticon-wallet"></i>
                        </span>
                        <span class="title">Transactions</span>
                    </a>
                </li>

                <!-- Notifications -->
                <li class="nav-item">
                    <a href="notifications.php" title="Benachrichtigungen">
                        <span class="icon-holder">
                            <i class="anticon anticon-bell"></i>
                        </span>
                        <span class="title">Benachrichtigungen</span>
                        <?php 
                        try {
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                            $stmt->execute([$_SESSION['user_id']]);
                            $unreadNotifications = $stmt->fetchColumn();
                            if ($unreadNotifications > 0): ?>
                                <span class="badge badge-primary ml-auto"><?= $unreadNotifications ?></span>
                            <?php endif;
                        } catch (PDOException $e) {
                            // Table might not exist yet
                        }
                        ?>
                    </a>
                </li>

                <!-- Payment Methods -->
                <li class="nav-item">
                    <a href="payment-methods.php" title="Manage Payment Methods">
                        <span class="icon-holder">
                            <i class="anticon anticon-credit-card"></i>
                        </span>
                        <span class="title">Payment Methods</span>
                    </a>
                </li>

                <!-- KYC Verification -->
                <li class="nav-item">
                    <a href="kyc.php" title="Identity Verification">
                        <span class="icon-holder">
                            <i class="anticon anticon-safety-certificate"></i>
                        </span>
                        <span class="title">KYC Verification</span>
                        <?php 
                        $stmt = $pdo->prepare("SELECT status FROM kyc_verification_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
                        $stmt->execute([$_SESSION['user_id']]);
                        $kycStatus = $stmt->fetch();
                        if ($kycStatus && $kycStatus['status'] === 'pending'): ?>
                            <span class="badge badge-warning ml-auto">Pending</span>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- Account -->
                <li class="nav-item dropdown">
                    <a class="dropdown-toggle" href="javascript:void(0);" title="Account Settings">
                        <span class="icon-holder">
                            <i class="anticon anticon-user"></i>
                        </span>
                        <span class="title">Account</span>
                        <span class="arrow">
                            <i class="arrow-icon"></i>
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="profile.php" title="View and Edit Profile">
                                <i class="anticon anticon-user m-r-10"></i>
                                My Profile
                            </a>
                        </li>
                        <li>
                            <a href="settings.php" title="Account Settings">
                                <i class="anticon anticon-setting m-r-10"></i>
                                Settings
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" title="Sign Out">
                                <i class="anticon anticon-logout m-r-10"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Support -->
                <li class="nav-item">
                    <a href="support.php" title="Get Help & Support">
                        <span class="icon-holder">
                            <i class="anticon anticon-customer-service"></i>
                        </span>
                        <span class="title">Support</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>
</div>
<!-- Side Nav END -->