<?php
// admin_user_classification.php
// User Classification Dashboard - Classify users by onboarding, package status, etc.

require_once 'admin_header.php';

// Get classification statistics
try {
    // Total users
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // Users with onboarding
    $withOnboarding = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM user_onboarding")->fetchColumn();
    $withCompletedOnboarding = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM user_onboarding WHERE completed = 1")->fetchColumn();
    $withoutOnboarding = $totalUsers - $withOnboarding;
    
    // Users with packages
    $withPackage = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM user_packages")->fetchColumn();
    $withActivePackage = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM user_packages WHERE status = 'active'")->fetchColumn();
    $withExpiredPackage = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM user_packages WHERE status = 'expired'")->fetchColumn();
    $withoutPackage = $totalUsers - $withPackage;
    
    // Users with cases
    $withCases = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM cases")->fetchColumn();
    $withActiveCases = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM cases WHERE status IN ('open', 'documents_required', 'under_review')")->fetchColumn();
    $withoutCases = $totalUsers - $withCases;
    
    // KYC Status
    $withKYC = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM kyc_verification_requests")->fetchColumn();
    $kycApproved = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM kyc_verification_requests WHERE status = 'approved'")->fetchColumn();
    $kycPending = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM kyc_verification_requests WHERE status = 'pending'")->fetchColumn();
    
} catch (PDOException $e) {
    $totalUsers = $withOnboarding = $withoutOnboarding = $withPackage = $withoutPackage = 0;
    $withCases = $withoutCases = $withKYC = $kycApproved = $kycPending = 0;
}
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">User Classification</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <a href="admin_users.php" class="breadcrumb-item">Users</a>
                <span class="breadcrumb-item active">Classification</span>
            </nav>
        </div>
    </div>

    <!-- Quick Service Navigation Bar -->
    <div class="card mb-3" style="border-left:4px solid #2950a8;">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center" style="gap:6px;">
                <small class="text-muted font-weight-semibold mr-2" style="white-space:nowrap;">
                    <i class="anticon anticon-appstore mr-1"></i> Quick Access:
                </small>
                <a href="admin_users.php" class="btn btn-xs btn-outline-primary" style="font-size:12px;">
                    <i class="anticon anticon-team mr-1"></i> All Users
                </a>
                <a href="admin_kyc.php" class="btn btn-xs btn-outline-warning" style="font-size:12px;">
                    <i class="anticon anticon-safety-certificate mr-1"></i> KYC Review
                </a>
                <a href="admin_user_packages.php" class="btn btn-xs btn-outline-success" style="font-size:12px;">
                    <i class="anticon anticon-gift mr-1"></i> Packages
                </a>
                <a href="admin_cases.php" class="btn btn-xs btn-outline-secondary" style="font-size:12px;">
                    <i class="anticon anticon-folder-open mr-1"></i> Cases
                </a>
                <a href="admin_send_notifications.php" class="btn btn-xs btn-outline-secondary" style="font-size:12px;">
                    <i class="anticon anticon-notification mr-1"></i> Send Notifications
                </a>
                <a href="admin_deposits.php" class="btn btn-xs btn-outline-secondary" style="font-size:12px;">
                    <i class="anticon anticon-arrow-down mr-1"></i> Deposits
                </a>
                <a href="admin_withdrawals.php" class="btn btn-xs btn-outline-secondary" style="font-size:12px;">
                    <i class="anticon anticon-arrow-up mr-1"></i> Withdrawals
                </a>
            </div>
        </div>
    </div>
    
    <!-- Summary Stats -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-blue">
                            <i class="anticon anticon-team"></i>
                        </div>
                        <div class="m-l-15">
                            <h2 class="m-b-0"><?= number_format($totalUsers) ?></h2>
                            <p class="m-b-0 text-muted">Total Users</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-green">
                            <i class="anticon anticon-check-circle"></i>
                        </div>
                        <div class="m-l-15">
                            <h2 class="m-b-0"><?= number_format($withCompletedOnboarding) ?></h2>
                            <p class="m-b-0 text-muted">Onboarding Complete</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-cyan">
                            <i class="anticon anticon-gift"></i>
                        </div>
                        <div class="m-l-15">
                            <h2 class="m-b-0"><?= number_format($withActivePackage) ?></h2>
                            <p class="m-b-0 text-muted">Active Packages</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-gold">
                            <i class="anticon anticon-folder-open"></i>
                        </div>
                        <div class="m-l-15">
                            <h2 class="m-b-0"><?= number_format($withActiveCases) ?></h2>
                            <p class="m-b-0 text-muted">Active Cases</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Progress Overview -->
    <?php
    $onboardingRate = $totalUsers > 0 ? round(($withCompletedOnboarding / $totalUsers) * 100, 1) : 0;
    $packageRate    = $totalUsers > 0 ? round(($withActivePackage / $totalUsers) * 100, 1) : 0;
    $kycRate        = $totalUsers > 0 ? round(($kycApproved / $totalUsers) * 100, 1) : 0;
    $caseRate       = $totalUsers > 0 ? round(($withActiveCases / $totalUsers) * 100, 1) : 0;
    ?>
    <div class="card mb-3">
        <div class="card-header py-2">
            <h6 class="m-b-0"><i class="anticon anticon-bar-chart mr-1"></i> Platform Adoption Overview</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="font-weight-semibold">Onboarding Completion</small>
                        <small class="text-muted"><?= $withCompletedOnboarding ?>/<?= $totalUsers ?> (<?= $onboardingRate ?>%)</small>
                    </div>
                    <div class="progress" style="height:8px;" title="<?= $onboardingRate ?>% of users completed onboarding">
                        <div class="progress-bar bg-success" style="width:<?= $onboardingRate ?>%"></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="font-weight-semibold">Active Package Rate</small>
                        <small class="text-muted"><?= $withActivePackage ?>/<?= $totalUsers ?> (<?= $packageRate ?>%)</small>
                    </div>
                    <div class="progress" style="height:8px;" title="<?= $packageRate ?>% of users have an active package">
                        <div class="progress-bar bg-info" style="width:<?= $packageRate ?>%"></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="font-weight-semibold">KYC Approval Rate</small>
                        <small class="text-muted"><?= $kycApproved ?>/<?= $totalUsers ?> (<?= $kycRate ?>%)</small>
                    </div>
                    <div class="progress" style="height:8px;" title="<?= $kycRate ?>% of users have approved KYC">
                        <div class="progress-bar bg-warning" style="width:<?= $kycRate ?>%"></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="font-weight-semibold">Users with Active Cases</small>
                        <small class="text-muted"><?= $withActiveCases ?>/<?= $totalUsers ?> (<?= $caseRate ?>%)</small>
                    </div>
                    <div class="progress" style="height:8px;" title="<?= $caseRate ?>% of users have active cases">
                        <div class="progress-bar bg-primary" style="width:<?= $caseRate ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Classification Cards -->
    <div class="row">
        <!-- Onboarding Classification -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><i class="anticon anticon-form text-primary"></i> Onboarding Status</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-success-light mb-2">
                                <h3 class="text-success"><?= number_format($withCompletedOnboarding) ?></h3>
                                <p class="mb-0">Completed Onboarding</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-success filter-users" data-filter="onboarding_completed">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_users.php?classification=onboarding_completed" class="btn btn-sm btn-outline-success" title="Open in Users page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-warning-light mb-2">
                                <h3 class="text-warning"><?= number_format($withOnboarding - $withCompletedOnboarding) ?></h3>
                                <p class="mb-0">Incomplete Onboarding</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-warning filter-users" data-filter="onboarding_incomplete">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_users.php?classification=onboarding_incomplete" class="btn btn-sm btn-outline-warning" title="Open in Users page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-danger-light">
                                <h3 class="text-danger"><?= number_format($withoutOnboarding) ?></h3>
                                <p class="mb-0">No Onboarding</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-danger filter-users" data-filter="no_onboarding">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_users.php?classification=no_onboarding" class="btn btn-sm btn-outline-danger" title="Open in Users page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-info-light">
                                <h3 class="text-info"><?= number_format($withOnboarding) ?></h3>
                                <p class="mb-0">Has Onboarding (Any)</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-info filter-users" data-filter="has_onboarding">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_users.php?classification=has_onboarding" class="btn btn-sm btn-outline-info" title="Open in Users page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Package Classification -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><i class="anticon anticon-gift text-success"></i> Package Status</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-success-light mb-2">
                                <h3 class="text-success"><?= number_format($withActivePackage) ?></h3>
                                <p class="mb-0">Active Package</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-success filter-users" data-filter="package_active">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_users.php?classification=package_active" class="btn btn-sm btn-outline-success" title="Open in Users page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-danger-light mb-2">
                                <h3 class="text-danger"><?= number_format($withExpiredPackage) ?></h3>
                                <p class="mb-0">Expired Package</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-danger filter-users" data-filter="package_expired">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_users.php?classification=package_expired" class="btn btn-sm btn-outline-danger" title="Open in Users page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-secondary-light">
                                <h3 class="text-secondary"><?= number_format($withoutPackage) ?></h3>
                                <p class="mb-0">No Package</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-secondary filter-users" data-filter="no_package">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_users.php?classification=no_package" class="btn btn-sm btn-outline-secondary" title="Open in Users page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-primary-light">
                                <h3 class="text-primary"><?= number_format($withPackage) ?></h3>
                                <p class="mb-0">Has Package (Any)</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-primary filter-users" data-filter="has_package">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_users.php?classification=has_package" class="btn btn-sm btn-outline-primary" title="Open in Users page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Cases Classification -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><i class="anticon anticon-folder-open text-warning"></i> Cases Status</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-warning-light mb-2">
                                <h3 class="text-warning"><?= number_format($withActiveCases) ?></h3>
                                <p class="mb-0">Active Cases</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-warning filter-users" data-filter="cases_active">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_users.php?classification=cases_active" class="btn btn-sm btn-outline-warning" title="Open in Users page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-success-light mb-2">
                                <h3 class="text-success"><?= number_format($withCases) ?></h3>
                                <p class="mb-0">Has Cases (Any)</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-success filter-users" data-filter="has_cases">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_users.php?classification=has_cases" class="btn btn-sm btn-outline-success" title="Open in Users page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-secondary-light">
                                <h3 class="text-secondary"><?= number_format($withoutCases) ?></h3>
                                <p class="mb-0">No Cases</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-secondary filter-users" data-filter="no_cases">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_users.php?classification=no_cases" class="btn btn-sm btn-outline-secondary" title="Open in Users page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- KYC Classification -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><i class="anticon anticon-safety-certificate text-info"></i> KYC Verification</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-success-light mb-2">
                                <h3 class="text-success"><?= number_format($kycApproved) ?></h3>
                                <p class="mb-0">KYC Approved</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-success filter-users" data-filter="kyc_approved">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_kyc.php" class="btn btn-sm btn-outline-success" title="Go to KYC page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-warning-light mb-2">
                                <h3 class="text-warning"><?= number_format($kycPending) ?></h3>
                                <p class="mb-0">KYC Pending</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-warning filter-users" data-filter="kyc_pending">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_kyc.php" class="btn btn-sm btn-outline-warning" title="Go to KYC Review page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-secondary-light">
                                <h3 class="text-secondary"><?= number_format($totalUsers - $withKYC) ?></h3>
                                <p class="mb-0">No KYC Submitted</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-secondary filter-users" data-filter="no_kyc">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_users.php?classification=no_kyc" class="btn btn-sm btn-outline-secondary" title="Open in Users page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 text-center border rounded bg-info-light">
                                <h3 class="text-info"><?= number_format($withKYC) ?></h3>
                                <p class="mb-0">Has KYC (Any)</p>
                                <div class="mt-2 d-flex justify-content-center" style="gap:4px;">
                                    <a href="#" class="btn btn-sm btn-info filter-users" data-filter="has_kyc">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>
                                    <a href="admin_kyc.php" class="btn btn-sm btn-outline-info" title="Go to KYC page" target="_blank">
                                        <i class="anticon anticon-export"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Users List Section -->
    <div class="card" id="usersListCard" style="display: none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title" id="filterTitle">Users</h4>
            <div>
                <button class="btn btn-sm btn-primary" id="sendEmailToFiltered" disabled>
                    <i class="anticon anticon-mail"></i> Send Email to All
                </button>
                <button class="btn btn-sm btn-secondary" id="clearFilter">
                    <i class="anticon anticon-close"></i> Clear Filter
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="classificationUsersTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Balance</th>
                            <th>Onboarding</th>
                            <th>Package</th>
                            <th>Cases</th>
                            <th>KYC</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- User View Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="viewUserContent">
                <div class="text-center py-5">
                    <i class="anticon anticon-loading anticon-spin font-size-24"></i>
                    <p>Loading user details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editUserFromView">Edit User</button>
                <button type="button" class="btn btn-warning" id="sendNotifFromView"><i class="anticon anticon-notification mr-1"></i> Send Notification</button>
                <button type="button" class="btn btn-success" id="sendEmailFromView">Send Email</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Package Modal -->
<div class="modal fade" id="addPackageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Package to User</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="addPackageForm">
                    <input type="hidden" id="packageUserId" name="user_id">
                    <div class="form-group">
                        <label>User</label>
                        <input type="text" id="packageUserName" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Package</label>
                        <select class="form-control" id="packageSelect" name="package_id" required>
                            <option value="">Select Package...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="datetime-local" class="form-control" id="packageStartDate" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label>End Date (auto-calculated from package duration)</label>
                        <input type="datetime-local" class="form-control" id="packageEndDate" name="end_date">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status" required>
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitPackage">Assign Package</button>
            </div>
        </div>
    </div>
</div>

<!-- Send Email Modal -->
<div class="modal fade" id="sendEmailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Email</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="sendEmailForm">
                    <input type="hidden" id="emailUserId" name="user_id">
                    <div class="form-group">
                        <label>To</label>
                        <input type="text" id="emailUserName" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Template</label>
                        <select class="form-control" id="emailTemplate" name="template_id">
                            <option value="">Custom Email (No Template)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" class="form-control" id="emailSubject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea class="form-control" id="emailContent" name="content" rows="10" required></textarea>
                        <small class="text-muted">Available variables: {first_name}, {last_name}, {email}, {balance}, {site_name}, {site_url}</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitEmail">Send Email</button>
            </div>
        </div>
    </div>
</div>

<!-- Send Notification Modal (for user classification page) -->
<div class="modal fade" id="sendNotifModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(90deg,#007bff,#6f42c1);color:#fff;">
                <h5 class="modal-title">
                    <span style="background:rgba(255,255,255,0.2);border-radius:50%;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;">
                        <i class="anticon anticon-notification"></i>
                    </span>
                    Send Notification
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="sendNotifForm">
                    <input type="hidden" id="notifUserId" name="user_id">
                    <input type="hidden" id="notifUserEmail" name="user_email">
                    <div class="form-group">
                        <label>To</label>
                        <input type="text" id="notifUserName" class="form-control bg-light" readonly>
                    </div>
                    <div class="form-group">
                        <label>Notification Template <span class="text-danger">*</span></label>
                        <select class="form-control" id="notifTemplateSelect" name="template_key" required>
                            <option value="" disabled selected>— Select notification template —</option>
                        </select>
                        <small class="text-muted">Subject and content are automatically taken from the selected notification template.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitNotif"><i class="anticon anticon-notification mr-1"></i> Send Notification</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" class="form-control" id="editLastName" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" id="editEmail" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" class="form-control" id="editPhone" name="phone">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" id="editStatus" name="status">
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="banned">Banned</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Balance (€)</label>
                                <input type="number" class="form-control" id="editBalance" name="balance" step="0.01">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitEditUser">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<style>
.bg-success-light { background-color: rgba(40, 199, 111, 0.1); }
.bg-warning-light { background-color: rgba(255, 159, 67, 0.1); }
.bg-danger-light { background-color: rgba(234, 84, 85, 0.1); }
.bg-info-light { background-color: rgba(0, 188, 212, 0.1); }
.bg-primary-light { background-color: rgba(63, 81, 181, 0.1); }
.bg-secondary-light { background-color: rgba(145, 158, 171, 0.1); }
.user-detail-row { display: flex; border-bottom: 1px solid #eee; padding: 8px 0; }
.user-detail-label { width: 150px; font-weight: 600; color: #666; }
.user-detail-value { flex: 1; }
.user-stats-card { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 10px; }
.user-stats-card h4 { margin-bottom: 10px; font-size: 14px; color: #666; }
.user-stats-card .value { font-size: 24px; font-weight: 600; }
</style>
<?php require_once 'admin_footer.php'; ?>
<script>
$(document).ready(function() {
    var currentFilter = '';
    var table = null;
    var currentUserId = null;
    var filteredUsers = [];
    
    // HTML escape function to prevent XSS
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(text)));
        return div.innerHTML;
    }
    
    // Load email templates for dropdown
    function loadEmailTemplates() {
        $.get('admin_ajax/get_email_templates.php', function(response) {
            if (response.data) {
                var options = '<option value="">Custom Email (No Template)</option>';
                response.data.forEach(function(template) {
                    options += '<option value="' + escapeHtml(template.id) + '" data-subject="' + escapeHtml(template.subject) + '" data-content="' + escapeHtml(template.content) + '">' + escapeHtml(template.name) + '</option>';
                });
                $('#emailTemplate').html(options);
            }
        });
    }
    
    // Load packages for dropdown
    function loadPackages() {
        $.get('admin_ajax/get_packages.php', function(response) {
            if (response.data) {
                var options = '<option value="">Select Package...</option>';
                response.data.forEach(function(pkg) {
                    options += '<option value="' + escapeHtml(pkg.id) + '" data-duration="' + escapeHtml(pkg.duration_days) + '">' + escapeHtml(pkg.name) + ' (€' + parseFloat(pkg.price).toFixed(2) + ')</option>';
                });
                $('#packageSelect').html(options);
            }
        }).fail(function() {
            // Fallback if endpoint doesn't exist
            $('#packageSelect').html('<option value="">No packages available</option>');
        });
    }
    
    loadEmailTemplates();
    loadPackages();
    
    // Fetch notification templates from email_notifications for the dropdown
    $.post('admin_ajax/get_notification_templates.php', {draw: 1, start: 0, length: 200, 'search[value]': ''}, function(response) {
        if (response && response.data && response.data.length) {
            var options = '<option value="" disabled selected>— Select notification template —</option>';
            response.data.forEach(function(tpl) {
                if (tpl.is_active == 1 || tpl.is_active === '1') {
                    options += '<option value="notif:' + escapeHtml(tpl.notification_key) + '">' + escapeHtml(tpl.name) + ' — ' + escapeHtml(tpl.subject) + '</option>';
                }
            });
            $('#notifTemplateSelect').html(options);
        }
    }, 'json').fail(function() {
        // Fallback: keep default empty option
    });
    
    // Set default start date
    var now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    $('#packageStartDate').val(now.toISOString().slice(0,16));
    
    // Auto-calculate end date when package or start date changes
    function calculateEndDate() {
        var selected = $('#packageSelect').find(':selected');
        var duration = selected.data('duration');
        var startDate = $('#packageStartDate').val();
        
        if (duration && startDate) {
            var start = new Date(startDate);
            start.setDate(start.getDate() + parseInt(duration));
            $('#packageEndDate').val(start.toISOString().slice(0,16));
        }
    }
    
    $('#packageSelect').change(calculateEndDate);
    $('#packageStartDate').change(calculateEndDate);
    
    // Filter users click
    $('.filter-users').click(function(e) {
        e.preventDefault();
        currentFilter = $(this).data('filter');
        var filterTitle = $(this).closest('.p-3').find('p').text();
        
        $('#filterTitle').text('Users: ' + filterTitle);
        $('#usersListCard').show();
        $('#sendEmailToFiltered').prop('disabled', false);
        
        // Initialize or reload DataTable
        if (table) {
            table.destroy();
        }
        
        table = $('#classificationUsersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'admin_ajax/get_classified_users.php',
                type: 'POST',
                data: function(d) {
                    d.classification = currentFilter;
                },
                dataSrc: function(json) {
                    filteredUsers = json.data.map(u => ({id: u.id, email: u.email, name: u.first_name + ' ' + u.last_name}));
                    return json.data;
                }
            },
            order: [[0, 'desc']],
            columns: [
                { data: 'id' },
                { data: null, render: function(data) { return escapeHtml(data.first_name + ' ' + data.last_name); } },
                { data: 'email', render: function(d) { return escapeHtml(d); } },
                { 
                    data: 'status',
                    render: function(data) {
                        var cls = {active:'success', suspended:'warning', banned:'danger'}[data] || 'secondary';
                        return '<span class="badge badge-' + cls + '">' + escapeHtml(data) + '</span>';
                    }
                },
                { data: 'balance', render: function(d) { return '€' + parseFloat(d || 0).toFixed(2); } },
                { 
                    data: 'has_onboarding',
                    render: function(d) { return d == '1' ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>'; }
                },
                { 
                    data: 'package_status',
                    render: function(d) {
                        if (!d) return '<span class="badge badge-secondary">None</span>';
                        var cls = {active:'success', pending:'warning', expired:'danger'}[d] || 'secondary';
                        return '<span class="badge badge-' + cls + '">' + escapeHtml(d) + '</span>';
                    }
                },
                { 
                    data: 'cases_count',
                    render: function(d) { return d > 0 ? '<span class="badge badge-info">' + escapeHtml(d) + '</span>' : '<span class="badge badge-secondary">0</span>'; }
                },
                { 
                    data: 'kyc_status',
                    render: function(d) {
                        if (!d) return '<span class="badge badge-secondary">None</span>';
                        var cls = {approved:'success', pending:'warning', rejected:'danger'}[d] || 'secondary';
                        return '<span class="badge badge-' + cls + '">' + escapeHtml(d) + '</span>';
                    }
                },
                { data: 'created_at', render: function(d) { return new Date(d).toLocaleDateString('de-DE'); } },
                {
                    data: null,
                    orderable: false,
                    render: function(data) {
                        return '<div class="d-flex" style="gap:3px;">' +
                            '<a href="admin_view_users.php?id=' + escapeHtml(data.id) + '" class="btn btn-sm btn-primary" title="Full Profile Page" target="_blank"><i class="anticon anticon-profile"></i></a>' +
                            '<button class="btn btn-sm btn-info view-user-btn" data-id="' + escapeHtml(data.id) + '" title="Quick View"><i class="anticon anticon-eye"></i></button>' +
                            '<button class="btn btn-sm btn-warning edit-user-btn" data-id="' + escapeHtml(data.id) + '" title="Edit User"><i class="anticon anticon-edit"></i></button>' +
                            '<button class="btn btn-sm btn-success send-email-btn" data-id="' + escapeHtml(data.id) + '" data-name="' + escapeHtml(data.first_name + ' ' + data.last_name) + '" data-email="' + escapeHtml(data.email) + '" title="Send Email"><i class="anticon anticon-mail"></i></button>' +
                            '<button class="btn btn-sm btn-info send-notif-btn" data-id="' + escapeHtml(data.id) + '" data-name="' + escapeHtml(data.first_name + ' ' + data.last_name) + '" data-email="' + escapeHtml(data.email) + '" title="Send Notification"><i class="anticon anticon-notification"></i></button>' +
                            '<button class="btn btn-sm btn-secondary add-package-btn" data-id="' + escapeHtml(data.id) + '" data-name="' + escapeHtml(data.first_name + ' ' + data.last_name) + '" title="Assign Package"><i class="anticon anticon-gift"></i></button>' +
                        '</div>';
                    }
                }
            ]
        });
        
        // Scroll to table
        $('html, body').animate({
            scrollTop: $('#usersListCard').offset().top - 100
        }, 500);
    });
    
    // Clear filter
    $('#clearFilter').click(function() {
        $('#usersListCard').hide();
        if (table) {
            table.destroy();
        }
        currentFilter = '';
        filteredUsers = [];
        $('#sendEmailToFiltered').prop('disabled', true);
    });
    
    // View user modal
    $(document).on('click', '.view-user-btn', function() {
        currentUserId = $(this).data('id');
        $('#viewUserContent').html('<div class="text-center py-5"><i class="anticon anticon-loading anticon-spin font-size-24"></i><p>Loading user details...</p></div>');
        $('#viewUserModal').modal('show');
        
        $.get('admin_ajax/get_user.php', {id: currentUserId}, function(response) {
            if (response.success && response.user) {
                var user = response.user;
                var statusClass = user.status === 'active' ? 'success' : 'warning';
                var html = '<div class="row">' +
                    '<div class="col-md-6">' +
                        '<div class="user-stats-card">' +
                            '<h4>Balance</h4>' +
                            '<div class="value text-success">€' + parseFloat(user.balance || 0).toFixed(2) + '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-6">' +
                        '<div class="user-stats-card">' +
                            '<h4>Status</h4>' +
                            '<div class="value">' +
                                '<span class="badge badge-' + statusClass + '">' + escapeHtml(user.status) + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="mt-3">' +
                    '<h5>Personal Information</h5>' +
                    '<div class="user-detail-row"><div class="user-detail-label">Name:</div><div class="user-detail-value">' + escapeHtml(user.first_name + ' ' + user.last_name) + '</div></div>' +
                    '<div class="user-detail-row"><div class="user-detail-label">Email:</div><div class="user-detail-value">' + escapeHtml(user.email) + '</div></div>' +
                    '<div class="user-detail-row"><div class="user-detail-label">Phone:</div><div class="user-detail-value">' + escapeHtml(user.phone || 'N/A') + '</div></div>' +
                    '<div class="user-detail-row"><div class="user-detail-label">Registered:</div><div class="user-detail-value">' + new Date(user.created_at).toLocaleDateString('de-DE') + '</div></div>' +
                '</div>';
                
                if (response.package_info) {
                    var pkgStatusClass = response.package_info.status === 'active' ? 'success' : 'danger';
                    html += '<div class="mt-3">' +
                        '<h5>Package Information</h5>' +
                        '<div class="user-detail-row"><div class="user-detail-label">Package:</div><div class="user-detail-value">' + escapeHtml(response.package_info.package_name || 'None') + '</div></div>' +
                        '<div class="user-detail-row"><div class="user-detail-label">Status:</div><div class="user-detail-value"><span class="badge badge-' + pkgStatusClass + '">' + escapeHtml(response.package_info.status) + '</span></div></div>' +
                        '<div class="user-detail-row"><div class="user-detail-label">Expires:</div><div class="user-detail-value">' + (response.package_info.end_date ? new Date(response.package_info.end_date).toLocaleDateString('de-DE') : 'N/A') + '</div></div>' +
                    '</div>';
                }
                
                if (response.case_summary) {
                    html += '<div class="mt-3">' +
                        '<h5>Cases Summary</h5>' +
                        '<div class="row">' +
                            '<div class="col-md-4">' +
                                '<div class="user-stats-card text-center">' +
                                    '<h4>Total Cases</h4>' +
                                    '<div class="value text-primary">' + (response.case_summary.total_cases || 0) + '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="col-md-4">' +
                                '<div class="user-stats-card text-center">' +
                                    '<h4>Total Reported</h4>' +
                                    '<div class="value text-warning">€' + parseFloat(response.case_summary.total_reported || 0).toFixed(2) + '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="col-md-4">' +
                                '<div class="user-stats-card text-center">' +
                                    '<h4>Total Recovered</h4>' +
                                    '<div class="value text-success">€' + parseFloat(response.case_summary.total_recovered || 0).toFixed(2) + '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
                }
                
                $('#viewUserContent').html(html);
            } else {
                $('#viewUserContent').html('<div class="alert alert-danger">Failed to load user details</div>');
            }
        });
    });
    
    // Edit user from view modal
    $('#editUserFromView').click(function() {
        $('#viewUserModal').modal('hide');
        loadUserForEdit(currentUserId);
    });
    
    // Send email from view modal
    $('#sendEmailFromView').click(function() {
        $('#viewUserModal').modal('hide');
        $.get('admin_ajax/get_user.php', {id: currentUserId}, function(response) {
            if (response.success && response.user) {
                $('#emailUserId').val(response.user.id);
                $('#emailUserName').val(response.user.first_name + ' ' + response.user.last_name + ' <' + response.user.email + '>');
                $('#sendEmailModal').modal('show');
            }
        });
    });
    
    // Send notification from view modal
    $('#sendNotifFromView').click(function() {
        $('#viewUserModal').modal('hide');
        $.get('admin_ajax/get_user.php', {id: currentUserId}, function(response) {
            if (response.success && response.user) {
                $('#notifUserId').val(response.user.id);
                $('#notifUserEmail').val(response.user.email);
                $('#notifUserName').val(response.user.first_name + ' ' + response.user.last_name + ' <' + response.user.email + '>');
                $('#notifTemplateSelect').val('');
                $('#sendNotifModal').modal('show');
            }
        });
    });
    
    // Send notification button (from table row)
    $(document).on('click', '.send-notif-btn', function() {
        $('#notifUserId').val($(this).data('id'));
        $('#notifUserEmail').val($(this).data('email'));
        $('#notifUserName').val($(this).data('name') + ' <' + $(this).data('email') + '>');
        $('#notifTemplateSelect').val('');
        $('#sendNotifModal').modal('show');
    });
    
    // Submit notification
    $('#submitNotif').click(function() {
        var templateKey = $('#notifTemplateSelect').val();
        var userId = $('#notifUserId').val();
        var userEmail = $('#notifUserEmail').val();
        
        if (!templateKey) {
            toastr.warning('Please select a notification template');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Sending...');
        
        $.ajax({
            url: 'admin_ajax/send_bulk_notifications.php',
            type: 'POST',
            data: {
                template_key: templateKey,
                users: JSON.stringify([{id: userId, email: userEmail}])
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#sendNotifModal').modal('hide');
                    toastr.success(response.message || 'Notification sent successfully');
                } else {
                    toastr.error(response.message || 'Failed to send notification');
                }
            },
            error: function() {
                toastr.error('Error sending notification');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="anticon anticon-notification mr-1"></i> Send Notification');
            }
        });
    });
    
    // Edit user button
    $(document).on('click', '.edit-user-btn', function() {
        loadUserForEdit($(this).data('id'));
    });
    
    function loadUserForEdit(userId) {
        $.get('admin_ajax/get_user.php', {id: userId}, function(response) {
            if (response.success && response.user) {
                var user = response.user;
                $('#editUserId').val(user.id);
                $('#editFirstName').val(user.first_name);
                $('#editLastName').val(user.last_name);
                $('#editEmail').val(user.email);
                $('#editPhone').val(user.phone);
                $('#editStatus').val(user.status);
                $('#editBalance').val(user.balance);
                $('#editUserModal').modal('show');
            }
        });
    }
    
    // Submit edit user
    $('#submitEditUser').click(function() {
        var formData = $('#editUserForm').serialize();
        $.post('admin_ajax/update_user.php', formData, function(response) {
            if (response.success) {
                $('#editUserModal').modal('hide');
                if (table) table.ajax.reload();
                toastr.success('User updated successfully');
            } else {
                toastr.error(response.message || 'Failed to update user');
            }
        });
    });
    
    // Send email button
    $(document).on('click', '.send-email-btn', function() {
        $('#emailUserId').val($(this).data('id'));
        $('#emailUserName').val($(this).data('name') + ' <' + $(this).data('email') + '>');
        $('#emailSubject').val('');
        $('#emailContent').val('');
        $('#emailTemplate').val('');
        $('#sendEmailModal').modal('show');
    });
    
    // Template selection
    $('#emailTemplate').change(function() {
        var selected = $(this).find(':selected');
        if (selected.val()) {
            $('#emailSubject').val(selected.data('subject') || '');
            $('#emailContent').val(selected.data('content') || '');
        }
    });
    
    // Submit email
    $('#submitEmail').click(function() {
        var formData = {
            user_id: $('#emailUserId').val(),
            subject: $('#emailSubject').val(),
            content: $('#emailContent').val(),
            template_id: $('#emailTemplate').val()
        };
        
        $.post('admin_ajax/send_user_email.php', formData, function(response) {
            if (response.success) {
                $('#sendEmailModal').modal('hide');
                toastr.success('Email sent successfully');
            } else {
                toastr.error(response.message || 'Failed to send email');
            }
        });
    });
    
    // Add package button
    $(document).on('click', '.add-package-btn', function() {
        $('#packageUserId').val($(this).data('id'));
        $('#packageUserName').val($(this).data('name'));
        $('#packageSelect').val('');
        $('#addPackageModal').modal('show');
    });
    
    // Submit package
    $('#submitPackage').click(function() {
        var formData = $('#addPackageForm').serialize();
        $.post('admin_ajax/add_user_package.php', formData, function(response) {
            if (response.success) {
                $('#addPackageModal').modal('hide');
                if (table) table.ajax.reload();
                toastr.success('Package assigned successfully');
            } else {
                toastr.error(response.message || 'Failed to assign package');
            }
        });
    });
    
    // Send email to all filtered users
    $('#sendEmailToFiltered').click(function() {
        if (filteredUsers.length === 0) {
            toastr.warning('No users to email');
            return;
        }
        
        var userList = filteredUsers.map(u => u.name + ' <' + u.email + '>').join(', ');
        $('#emailUserId').val(filteredUsers.map(u => u.id).join(','));
        $('#emailUserName').val(filteredUsers.length + ' users: ' + userList.substring(0, 100) + '...');
        $('#sendEmailModal').modal('show');
    });
});
</script>

