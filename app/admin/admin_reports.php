<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2>System Reports</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">System Reports</span>
            </nav>
        </div>
    </div>
    
    <!-- Report Filter -->
    <div class="card">
        <div class="card-body">
            <h5>Generate Professional Report</h5>
            <form id="reportFilterForm" class="mt-3">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Report Type</label>
                            <select class="form-control" name="report_type" id="reportType">
                                <option value="users">User Report</option>
                                <option value="login_activity">Login Activity Report</option>
                                <option value="transactions">Transaction Report</option>
                                <option value="cases">Case Report</option>
                                <option value="financial">Financial Summary</option>
                                <option value="activity">Activity Log</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="startDate">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" class="form-control" name="end_date" id="endDate">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" id="generateReport">
                                <i class="anticon anticon-file-pdf"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Report Display Area -->
    <div class="card" id="reportDisplay" style="display: none;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 id="reportTitle">Report Results</h5>
                <button class="btn btn-success btn-sm" id="exportReport">
                    <i class="anticon anticon-download"></i> Export to CSV
                </button>
            </div>
            <div id="reportContent"></div>
        </div>
    </div>
    
    <!-- Quick Reports -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="anticon anticon-team font-size-40 text-primary"></i>
                    <h5 class="mt-3">User Activity Report</h5>
                    <p class="text-muted">Generate comprehensive user activity and engagement report</p>
                    <button class="btn btn-primary btn-sm" onclick="generateQuickReport('users')">
                        <i class="anticon anticon-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="anticon anticon-dollar font-size-40 text-success"></i>
                    <h5 class="mt-3">Financial Report</h5>
                    <p class="text-muted">View all transactions, deposits, and withdrawals</p>
                    <button class="btn btn-success btn-sm" onclick="generateQuickReport('financial')">
                        <i class="anticon anticon-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="anticon anticon-file-protect font-size-40 text-warning"></i>
                    <h5 class="mt-3">Case Management Report</h5>
                    <p class="text-muted">Summary of all cases and their current status</p>
                    <button class="btn btn-warning btn-sm" onclick="generateQuickReport('cases')">
                        <i class="anticon anticon-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Report History -->
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Recent Reports</h5>
            <div class="table-responsive">
                <table class="table table-hover" id="reportsTable">
                    <thead>
                        <tr>
                            <th>Report Type</th>
                            <th>Date Range</th>
                            <th>Generated By</th>
                            <th>Generated On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-primary">User Report</span></td>
                            <td>2024-01-01 to 2024-01-31</td>
                            <td>Admin User</td>
                            <td><?= date('Y-m-d H:i') ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary">
                                    <i class="anticon anticon-download"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Set default dates
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
    $('#endDate').val(today.toISOString().split('T')[0]);
    $('#startDate').val(lastMonth.toISOString().split('T')[0]);
    
    $('#generateReport').click(function() {
        const reportType = $('#reportType').val();
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        if (!startDate || !endDate) {
            toastr.error('Please select start and end dates');
            return;
        }
        
        toastr.info('Generating report...');
        
        // TODO: Replace with actual API endpoint when backend is ready
        // $.ajax({
        //     url: 'admin_ajax/generate_report.php',
        //     type: 'POST',
        //     data: { type: reportType, start_date: startDate, end_date: endDate },
        //     success: function(response) {
        //         if (response.success) {
        //             toastr.success('Report generated successfully');
        //             window.location.href = response.download_url;
        //         }
        //     }
        // });
        setTimeout(() => {
            toastr.success('Report generated successfully (demo mode)');
        }, 1000);
    });
    
    $('#generateReport').click(function() {
        const reportType = $('#reportType').val();
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Generating...');
        
        $.ajax({
            url: 'admin_ajax/generate_report.php',
            type: 'POST',
            data: {
                report_type: reportType,
                start_date: startDate,
                end_date: endDate
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayReport(response);
                    $('#reportDisplay').slideDown();
                    toastr.success('Report generated successfully!');
                } else {
                    toastr.error(response.message || 'Failed to generate report');
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr.responseText);
                toastr.error('Failed to generate report');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    function displayReport(response) {
        const { report_type, data, start_date, end_date, generated_at } = response;
        let html = `
            <div class="alert alert-info">
                <strong>Report Period:</strong> ${start_date} to ${end_date}<br>
                <strong>Generated:</strong> ${generated_at}
            </div>
        `;
        
        if (report_type === 'users') {
            html += `
                <h6>User Statistics</h6>
                <table class="table table-bordered">
                    <tr><td><strong>Total Users</strong></td><td>${data.total_users}</td></tr>
                    <tr><td><strong>Active Users</strong></td><td>${data.status_active}</td></tr>
                    <tr><td><strong>Never Logged In</strong></td><td>${data.never_logged_in}</td></tr>
                    <tr><td><strong>Inactive (7+ days)</strong></td><td>${data.inactive_7_days}</td></tr>
                    <tr><td><strong>Inactive (30+ days)</strong></td><td>${data.inactive_30_days}</td></tr>
                    <tr><td><strong>Verified Users</strong></td><td>${data.verified_users}</td></tr>
                    <tr><td><strong>Average Balance</strong></td><td>$${parseFloat(data.avg_balance || 0).toFixed(2)}</td></tr>
                    <tr><td><strong>Total Balance</strong></td><td>$${parseFloat(data.total_balance || 0).toFixed(2)}</td></tr>
                </table>
            `;
        } else if (report_type === 'login_activity') {
            html += `
                <h6>Login Activity Statistics</h6>
                <table class="table table-bordered">
                    <tr><td><strong>Total Users</strong></td><td>${data.total_users}</td></tr>
                    <tr><td><strong>Never Logged In</strong></td><td class="text-danger">${data.never_logged_in}</td></tr>
                    <tr><td><strong>Active in Last 24h</strong></td><td class="text-success">${data.active_1_day}</td></tr>
                    <tr><td><strong>Active in Last 3 Days</strong></td><td class="text-success">${data.active_3_days}</td></tr>
                    <tr><td><strong>Active in Last 7 Days</strong></td><td class="text-success">${data.active_7_days}</td></tr>
                    <tr><td><strong>Active in Last 30 Days</strong></td><td class="text-info">${data.active_30_days}</td></tr>
                    <tr><td><strong>Inactive 3+ Days</strong></td><td class="text-warning">${data.inactive_3_days}</td></tr>
                    <tr><td><strong>Inactive 7+ Days</strong></td><td class="text-warning">${data.inactive_7_days}</td></tr>
                    <tr><td><strong>Inactive 30+ Days</strong></td><td class="text-danger">${data.inactive_30_days}</td></tr>
                </table>
            `;
        } else if (report_type === 'financial') {
            html += `
                <h6>Financial Summary</h6>
                <table class="table table-bordered">
                    <tr><td><strong>Total User Balance</strong></td><td>$${parseFloat(data.total_user_balance || 0).toFixed(2)}</td></tr>
                    <tr><td><strong>Approved Deposits</strong></td><td class="text-success">$${parseFloat(data.approved_deposits || 0).toFixed(2)}</td></tr>
                    <tr><td><strong>Pending Deposits</strong></td><td class="text-warning">$${parseFloat(data.pending_deposits || 0).toFixed(2)}</td></tr>
                    <tr><td><strong>Approved Withdrawals</strong></td><td class="text-danger">$${parseFloat(data.approved_withdrawals || 0).toFixed(2)}</td></tr>
                    <tr><td><strong>Pending Withdrawals</strong></td><td class="text-warning">$${parseFloat(data.pending_withdrawals || 0).toFixed(2)}</td></tr>
                    <tr><td><strong>Cases Reported Amount</strong></td><td>$${parseFloat(data.cases_reported || 0).toFixed(2)}</td></tr>
                    <tr><td><strong>Cases Recovered Amount</strong></td><td class="text-success">$${parseFloat(data.cases_recovered || 0).toFixed(2)}</td></tr>
                </table>
            `;
        }
        
        $('#reportContent').html(html);
        $('#reportTitle').text(response.report_type.toUpperCase() + ' Report');
    }
});

function generateQuickReport(type) {
    toastr.info('Generating ' + type + ' report...');
    
    // TODO: Replace with actual API endpoint when backend is ready
    // $.ajax({
    //     url: 'admin_ajax/generate_quick_report.php',
    //     type: 'POST',
    //     data: { type: type },
    //     success: function(response) {
    //         if (response.success) {
    //             window.location.href = response.download_url;
    //         }
    //     }
    // });
    setTimeout(() => {
        toastr.success('Report generated successfully (demo mode)');
    }, 1000);
}
</script>


