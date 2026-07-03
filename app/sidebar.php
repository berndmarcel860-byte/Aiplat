<?php
// ── Sidebar: resolve packages toggle + satoshi verification status ──────────
$_sidebar_packagesEnabled = true;
$_sidebar_satoshiVerified = false;
$_sidebar_satoshiRequired = false;
try {
    $_spkgStmt = $pdo->query("SELECT packages_enabled FROM system_settings WHERE id = 1 LIMIT 1");
    $_spkgRow  = $_spkgStmt->fetch(PDO::FETCH_ASSOC);
    if ($_spkgRow !== false && isset($_spkgRow['packages_enabled'])) {
        $_sidebar_packagesEnabled = ((int)$_spkgRow['packages_enabled'] === 1);
    }
} catch (PDOException $e) { /* migration not yet run */ }

if (!$_sidebar_packagesEnabled && !empty($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/database/satoshi_test_helpers.php';
        $_sbBalanceStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? LIMIT 1");
        $_sbBalanceStmt->execute([(int)$_SESSION['user_id']]);
        $_sbBalance = (float)$_sbBalanceStmt->fetchColumn();
        $_sidebar_satoshiRequired = isSatoshiVerificationRequired(false, $_sbBalance, 50000.0);
        $_sidebar_satoshiVerified = $_sidebar_satoshiRequired && userHasVerifiedTest($pdo, (int)$_SESSION['user_id']);
    } catch (Throwable $e) { /* helpers not yet present */ }
}
?>
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

                <!-- Dashboard v2 (AI Recovery) -->
                <li class="nav-item">
                    <a href="index2.php" title="KI-Wiederherstellungs-Dashboard">
                        <span class="icon-holder">
                            <i class="anticon anticon-robot"></i>
                        </span>
                        <span class="title">Analyse-Cockpit</span>
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

                <!-- Recovered Funds -->
                <li class="nav-item">
                    <a href="recovered_funds.php" title="Recovered Funds Overview">
                        <span class="icon-holder">
                            <i class="anticon anticon-dollar"></i>
                        </span>
                        <span class="title">Rückgewonnene Mittel</span>
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

                <li class="nav-item">
                    <a href="history.php" title="Chronologischer Verlauf">
                        <span class="icon-holder">
                            <i class="anticon anticon-history"></i>
                        </span>
                        <span class="title">Historie</span>
                    </a>
                </li>

                <!-- Deposits -->
                <li class="nav-item">
                    <a href="deposit.php" title="Deposits &amp; Escrow">
                        <span class="icon-holder">
                            <i class="anticon anticon-download"></i>
                        </span>
                        <span class="title">Einzahlungen</span>
                        <?php
                        try {
                            $depStmt = $pdo->prepare("SELECT COUNT(*) FROM deposits WHERE user_id = ? AND status = 'pending'");
                            $depStmt->execute([(int)$_SESSION['user_id']]);
                            $pendingDeposits = (int)$depStmt->fetchColumn();
                            if ($pendingDeposits > 0): ?>
                                <span class="badge badge-warning ml-auto" title="Ausstehende Einzahlungen in Treuhand">
                                    🔒 <?= $pendingDeposits ?>
                                </span>
                            <?php endif;
                        } catch (PDOException $e) {
                            error_log('Sidebar deposit count error: ' . $e->getMessage());
                        }
                        ?>
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
                        <span class="title">Zahlung &amp; Verifizierung</span>
                        <?php if ($_sidebar_satoshiRequired): ?>
                            <?php if ($_sidebar_satoshiVerified): ?>
                                <span class="badge ml-auto" style="background:#22c55e;color:#fff;font-size:9px;">✓</span>
                            <?php else: ?>
                                <span class="badge badge-warning ml-auto">!</span>
                            <?php endif; ?>
                        <?php endif; ?>
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

                <?php if ($_sidebar_packagesEnabled): ?>
                <!-- Packages (only visible when admin has enabled packages) -->
                <li class="nav-item">
                    <a href="packages.php" title="Subscription Packages">
                        <span class="icon-holder">
                            <i class="anticon anticon-shopping"></i>
                        </span>
                        <span class="title">Pakete</span>
                    </a>
                </li>
                <?php endif; ?>

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
                            <a href="security.php" title="Security &amp; Activity Log">
                                <i class="anticon anticon-safety m-r-10"></i>
                                Sicherheit
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

                <!-- FAQ -->
                <li class="nav-item">
                    <a href="faq.php" title="Häufig gestellte Fragen">
                        <span class="icon-holder">
                            <i class="anticon anticon-question-circle"></i>
                        </span>
                        <span class="title">FAQ &amp; Hilfe</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>
</div>
<!-- Side Nav END -->