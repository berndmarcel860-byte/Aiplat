<!-- admin_sidebar.php -->
<div class="side-nav">
    <div class="side-nav-inner">
        <div class="side-nav-logo">
            <a href="admin_dashboard.php">
                <img src="../assets/images/logo/logo-fold.png" alt="Logo" class="collapsed-logo">
            </a>
        </div>
        <div class="side-nav-scroll-container">
            <ul class="side-nav-menu">
                <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : '' ?>">
                    <a href="admin_dashboard.php" data-page="dashboard">
                        <span class="icon-holder"><i class="anticon anticon-dashboard"></i></span>
                        <span class="title">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item dropdown <?= in_array(basename($_SERVER['PHP_SELF']), ['admin_users.php', 'admin_kyc.php', 'admin_online_users.php', 'admin_user_activity.php', 'admin_user_classification.php']) ? 'open' : '' ?>">
                    <a class="dropdown-toggle" href="javascript:void(0);" data-toggle="users">
                        <span class="icon-holder"><i class="anticon anticon-team"></i></span>
                        <span class="title">User Management</span>
                        <span class="arrow"><i class="arrow-icon"></i></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : '' ?>">
                            <a href="admin_users.php" data-page="users">
                                <i class="anticon anticon-user"></i> Manage Users
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_user_classification.php' ? 'active' : '' ?>">
                            <a href="admin_user_classification.php" data-page="user-classification">
                                <i class="anticon anticon-filter"></i> User Classification
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_kyc.php' ? 'active' : '' ?>">
                            <a href="admin_kyc.php" data-page="kyc">
                                <i class="anticon anticon-safety-certificate"></i> KYC Verification
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_online_users.php' ? 'active' : '' ?>">
                            <a href="admin_online_users.php" data-page="online-users">
                                <i class="anticon anticon-global"></i> Online Users
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_user_activity.php' ? 'active' : '' ?>">
                            <a href="admin_user_activity.php" data-page="user-activity">
                                <i class="anticon anticon-eye"></i> User Activity Logs
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item dropdown <?= in_array(basename($_SERVER['PHP_SELF']), ['admin_cases.php', 'admin_case_assignments.php', 'admin_platforms.php']) ? 'open' : '' ?>">
                    <a class="dropdown-toggle" href="javascript:void(0);" data-toggle="cases">
                        <span class="icon-holder"><i class="anticon anticon-file-protect"></i></span>
                        <span class="title">Case Management</span>
                        <span class="arrow"><i class="arrow-icon"></i></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_cases.php' ? 'active' : '' ?>">
                            <a href="admin_cases.php" data-page="cases">
                                <i class="anticon anticon-file"></i> All Cases
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_case_assignments.php' ? 'active' : '' ?>">
                            <a href="admin_case_assignments.php" data-page="assignments">
                                <i class="anticon anticon-user-switch"></i> Case Assignments
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_platforms.php' ? 'active' : '' ?>">
                            <a href="admin_platforms.php" data-page="platforms">
                                <i class="anticon anticon-security-scan"></i> Scam Platforms
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item dropdown <?= in_array(basename($_SERVER['PHP_SELF']), ['admin_transactions.php', 'admin_deposits.php', 'admin_withdrawals.php']) ? 'open' : '' ?>">
                    <a class="dropdown-toggle" href="javascript:void(0);" data-toggle="financials">
                        <span class="icon-holder"><i class="anticon anticon-dollar"></i></span>
                        <span class="title">Financial Management</span>
                        <span class="arrow"><i class="arrow-icon"></i></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_transactions.php' ? 'active' : '' ?>">
                            <a href="admin_transactions.php" data-page="transactions">
                                <i class="anticon anticon-transaction"></i> All Transactions
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_deposits.php' ? 'active' : '' ?>">
                            <a href="admin_deposits.php" data-page="deposits">
                                <i class="anticon anticon-arrow-down"></i> Deposits
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_withdrawals.php' ? 'active' : '' ?>">
                            <a href="admin_withdrawals.php" data-page="withdrawals">
                                <i class="anticon anticon-arrow-up"></i> Withdrawals
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item dropdown <?= in_array(basename($_SERVER['PHP_SELF']), ['admin_payment_methods.php', 'admin_payment_settings.php', 'admin_crypto_management.php', 'admin_wallet_verifications.php']) ? 'open' : '' ?>">
                    <a class="dropdown-toggle" href="javascript:void(0);" data-toggle="payments">
                        <span class="icon-holder"><i class="anticon anticon-credit-card"></i></span>
                        <span class="title">Payment System</span>
                        <span class="arrow"><i class="arrow-icon"></i></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_payment_methods.php' ? 'active' : '' ?>">
                            <a href="admin_payment_methods.php" data-page="payment-methods">
                                <i class="anticon anticon-wallet"></i> Payment Methods
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_crypto_management.php' ? 'active' : '' ?>">
                            <a href="admin_crypto_management.php" data-page="crypto-management">
                                <i class="fab fa-bitcoin"></i> Cryptocurrency Management
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_wallet_verifications.php' ? 'active' : '' ?>">
                            <a href="admin_wallet_verifications.php" data-page="wallet-verifications">
                                <i class="anticon anticon-safety-certificate"></i> Wallet Verifications
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_payment_settings.php' ? 'active' : '' ?>">
                            <a href="admin_payment_settings.php" data-page="payment-settings">
                                <i class="anticon anticon-setting"></i> Payment Settings
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_crypto_settings.php' ? 'active' : '' ?>">
                            <a href="admin_crypto_settings.php" data-page="crypto-settings">
                                <i class="anticon anticon-global"></i> Crypto Settings
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item dropdown <?= in_array(basename($_SERVER['PHP_SELF']), ['admin_email_logs.php', 'admin_email.php', 'admin_notifications.php', 'admin_send_notifications.php', 'admin_notification_templates.php']) ? 'open' : '' ?>">
                    <a class="dropdown-toggle" href="javascript:void(0);" data-toggle="communications">
                        <span class="icon-holder"><i class="anticon anticon-mail"></i></span>
                        <span class="title">Communications</span>
                        <span class="arrow"><i class="arrow-icon"></i></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_send_notifications.php' ? 'active' : '' ?>">
                            <a href="admin_send_notifications.php" data-page="send-notifications">
                                <i class="anticon anticon-send"></i> Send Notifications
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_email_logs.php' ? 'active' : '' ?>">
                            <a href="admin_email_logs.php" data-page="email-logs">
                                <i class="anticon anticon-audit"></i> Email Logs
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_email_templates.php' ? 'active' : '' ?>">
                            <a href="admin_email.php" data-page="email-templates">
                                <i class="anticon anticon-file-text"></i> Email Templates
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_notification_templates.php' ? 'active' : '' ?>">
                            <a href="admin_notification_templates.php" data-page="notification-templates">
                                <i class="anticon anticon-notification"></i> Email Notificasion TMP
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_notifications.php' ? 'active' : '' ?>">
                            <a href="admin_notifications.php" data-page="notifications">
                                <i class="anticon anticon-bell"></i> Notifications
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_smtp_settings.php' ? 'active' : '' ?>">
                            <a href="admin_smtp_settings.php" data-page="smtp-settings">
                                <i class="anticon anticon-setting"></i> SMTP Settings
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin_mailer.php' ? 'active' : '' ?>">
                    <a href="admin_mailer.php" data-page="campaign-mailer">
                        <span class="icon-holder"><i class="anticon anticon-thunderbolt"></i></span>
                        <span class="title">Campaign Mailer</span>
                    </a>
                </li>

                <li class="nav-item dropdown <?= in_array(basename($_SERVER['PHP_SELF']), ['admin_support_tickets.php', 'admin_faq.php']) ? 'open' : '' ?>">
                    <a class="dropdown-toggle" href="javascript:void(0);" data-toggle="support">
                        <span class="icon-holder"><i class="anticon anticon-question-circle"></i></span>
                        <span class="title">Support System</span>
                        <span class="arrow"><i class="arrow-icon"></i></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_support_tickets.php' ? 'active' : '' ?>">
                            <a href="admin_support_tickets.php" data-page="support-tickets">
                                <i class="anticon anticon-customer-service"></i> Support Tickets
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_faq.php' ? 'active' : '' ?>">
                            <a href="admin_faq.php" data-page="faq">
                                <i class="anticon anticon-question"></i> FAQ Management
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_help_articles.php' ? 'active' : '' ?>">
                            <a href="admin_help_articles.php" data-page="help-articles">
                                <i class="anticon anticon-book"></i> Help Articles
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item dropdown <?= in_array(basename($_SERVER['PHP_SELF']), ['admin_documents.php', 'admin_file_manager.php']) ? 'open' : '' ?>">
                    <a class="dropdown-toggle" href="javascript:void(0);" data-toggle="files">
                        <span class="icon-holder"><i class="anticon anticon-folder"></i></span>
                        <span class="title">File Management</span>
                        <span class="arrow"><i class="arrow-icon"></i></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_documents.php' ? 'active' : '' ?>">
                            <a href="admin_documents.php" data-page="documents">
                                <i class="anticon anticon-file"></i> User Documents
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_file_manager.php' ? 'active' : '' ?>">
                            <a href="admin_file_manager.php" data-page="file-manager">
                                <i class="anticon anticon-folder-open"></i> File Manager
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_media_library.php' ? 'active' : '' ?>">
                            <a href="admin_media_library.php" data-page="media-library">
                                <i class="anticon anticon-picture"></i> Media Library
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item dropdown <?= in_array(basename($_SERVER['PHP_SELF']), ['admin_reports.php', 'admin_analytics.php', 'admin_statistics.php']) ? 'open' : '' ?>">
                    <a class="dropdown-toggle" href="javascript:void(0);" data-toggle="reports">
                        <span class="icon-holder"><i class="anticon anticon-bar-chart"></i></span>
                        <span class="title">Reports & Analytics</span>
                        <span class="arrow"><i class="arrow-icon"></i></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_reports.php' ? 'active' : '' ?>">
                            <a href="admin_reports.php" data-page="reports">
                                <i class="anticon anticon-file-pdf"></i> System Reports
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_analytics.php' ? 'active' : '' ?>">
                            <a href="admin_analytics.php" data-page="analytics">
                                <i class="anticon anticon-line-chart"></i> Analytics Dashboard
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_statistics.php' ? 'active' : '' ?>">
                            <a href="admin_statistics.php" data-page="statistics">
                                <i class="anticon anticon-pie-chart"></i> Statistics
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_export.php' ? 'active' : '' ?>">
                            <a href="admin_export.php" data-page="export">
                                <i class="anticon anticon-download"></i> Data Export
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item dropdown <?= in_array(basename($_SERVER['PHP_SELF']), ['admin_admins.php', 'admin_roles.php', 'admin_permissions.php']) ? 'open' : '' ?>">
                    <a class="dropdown-toggle" href="javascript:void(0);" data-toggle="admin-management">
                        <span class="icon-holder"><i class="anticon anticon-crown"></i></span>
                        <span class="title">Admin Management</span>
                        <span class="arrow"><i class="arrow-icon"></i></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_admins.php' ? 'active' : '' ?>">
                            <a href="admin_admins.php" data-page="admins">
                                <i class="anticon anticon-user-add"></i> Manage Admins
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_roles.php' ? 'active' : '' ?>">
                            <a href="admin_roles.php" data-page="roles">
                                <i class="anticon anticon-key"></i> Roles & Permissions
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_login_logs.php' ? 'active' : '' ?>">
                            <a href="admin_login_logs.php" data-page="login-logs">
                                <i class="anticon anticon-login"></i> Login Logs
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item dropdown <?= in_array(basename($_SERVER['PHP_SELF']), ['admin_settings.php', 'admin_audit_logs.php', 'admin_system_info.php', 'admin_backup.php']) ? 'open' : '' ?>">
                    <a class="dropdown-toggle" href="javascript:void(0);" data-toggle="system">
                        <span class="icon-holder"><i class="anticon anticon-setting"></i></span>
                        <span class="title">System Settings</span>
                        <span class="arrow"><i class="arrow-icon"></i></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_settings.php' ? 'active' : '' ?>">
                            <a href="admin_settings.php" data-page="settings">
                                <i class="anticon anticon-control"></i> General Settings
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_audit_logs.php' ? 'active' : '' ?>">
                            <a href="admin_audit_logs.php" data-page="audit">
                                <i class="anticon anticon-audit"></i> Audit Logs
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_system_info.php' ? 'active' : '' ?>">
                            <a href="admin_system_info.php" data-page="system-info">
                                <i class="anticon anticon-info-circle"></i> System Information
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_backup.php' ? 'active' : '' ?>">
                            <a href="admin_backup.php" data-page="backup">
                                <i class="anticon anticon-cloud-upload"></i> Backup & Restore
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_maintenance.php' ? 'active' : '' ?>">
                            <a href="admin_maintenance.php" data-page="maintenance">
                                <i class="anticon anticon-tool"></i> Maintenance Mode
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item dropdown <?= in_array(basename($_SERVER['PHP_SELF']), ['admin_security.php', 'admin_ip_whitelist.php', 'admin_blocked_ips.php']) ? 'open' : '' ?>">
                    <a class="dropdown-toggle" href="javascript:void(0);" data-toggle="security">
                        <span class="icon-holder"><i class="anticon anticon-safety"></i></span>
                        <span class="title">Security Center</span>
                        <span class="arrow"><i class="arrow-icon"></i></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_security.php' ? 'active' : '' ?>">
                            <a href="admin_security.php" data-page="security">
                                <i class="anticon anticon-shield"></i> Security Settings
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_ip_whitelist.php' ? 'active' : '' ?>">
                            <a href="admin_ip_whitelist.php" data-page="ip-whitelist">
                                <i class="anticon anticon-check-circle"></i> IP Whitelist
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_blocked_ips.php' ? 'active' : '' ?>">
                            <a href="admin_blocked_ips.php" data-page="blocked-ips">
                                <i class="anticon anticon-stop"></i> Blocked IPs
                            </a>
                        </li>
                        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_2fa_settings.php' ? 'active' : '' ?>">
                            <a href="admin_2fa_settings.php" data-page="2fa-settings">
                                <i class="anticon anticon-mobile"></i> 2FA Settings
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Mobile overlay -->
<div class="side-nav-overlay"></div>