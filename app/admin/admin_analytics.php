<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2>Analytics Dashboard</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Analytics Dashboard</span>
            </nav>
        </div>
    </div>
    
    <!-- Time Range Filter -->
    <div class="card">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5>Analytics Overview</h5>
                </div>
                <div class="col-md-6 text-right">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-default active" data-period="7">7 Days</button>
                        <button type="button" class="btn btn-sm btn-default" data-period="30">30 Days</button>
                        <button type="button" class="btn btn-sm btn-default" data-period="90">90 Days</button>
                        <button type="button" class="btn btn-sm btn-default" data-period="365">1 Year</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Key Metrics -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">Total Users</p>
                            <h3 class="mb-0" id="totalUsers">--</h3>
                            <span class="text-success"><i class="anticon anticon-arrow-up"></i> 12%</span>
                        </div>
                        <div>
                            <div class="avatar avatar-icon avatar-lg avatar-blue">
                                <i class="anticon anticon-team"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">Total Revenue</p>
                            <h3 class="mb-0" id="totalRevenue">--</h3>
                            <span class="text-success"><i class="anticon anticon-arrow-up"></i> 8%</span>
                        </div>
                        <div>
                            <div class="avatar avatar-icon avatar-lg avatar-green">
                                <i class="anticon anticon-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">Active Cases</p>
                            <h3 class="mb-0" id="activeCases">--</h3>
                            <span class="text-danger"><i class="anticon anticon-arrow-down"></i> 3%</span>
                        </div>
                        <div>
                            <div class="avatar avatar-icon avatar-lg avatar-gold">
                                <i class="anticon anticon-file-protect"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">Support Tickets</p>
                            <h3 class="mb-0" id="openTickets">--</h3>
                            <span class="text-success"><i class="anticon anticon-arrow-up"></i> 5%</span>
                        </div>
                        <div>
                            <div class="avatar avatar-icon avatar-lg avatar-cyan">
                                <i class="anticon anticon-customer-service"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5>User Growth Trend</h5>
                    <div class="m-t-30" style="height: 300px;">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5>User Status Distribution</h5>
                    <div class="m-t-30" style="height: 300px;">
                        <canvas id="userStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Revenue by Month</h5>
                    <div class="m-t-30" style="height: 300px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Case Status Distribution</h5>
                    <div class="m-t-30" style="height: 300px;">
                        <canvas id="caseStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Performers -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Top Countries by Users</h5>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Country</th>
                                    <th>Users</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>United States</td>
                                    <td>1,234</td>
                                    <td><span class="badge badge-primary">45%</span></td>
                                </tr>
                                <tr>
                                    <td>United Kingdom</td>
                                    <td>856</td>
                                    <td><span class="badge badge-primary">31%</span></td>
                                </tr>
                                <tr>
                                    <td>Canada</td>
                                    <td>432</td>
                                    <td><span class="badge badge-primary">16%</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Recent Activity</h5>
                    <div class="mt-3">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>New user registrations</span>
                                    <strong>+125</strong>
                                </div>
                            </li>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>Cases opened today</span>
                                    <strong>18</strong>
                                </div>
                            </li>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>Transactions processed</span>
                                    <strong>432</strong>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js" integrity="sha384-C8zj7F9gZdLb8iXJ3GrKDAA6xzHLGFDk2fXVMdGgjfYWNT2pzEqGBJ0bPsaTiN2p" crossorigin="anonymous"></script>
<script>
$(document).ready(function() {
    // Load analytics data
    loadAnalytics();
    
    // Period selection
    $('.btn-group button').click(function() {
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');
        const period = $(this).data('period');
        loadAnalytics(period);
    });
    
    function loadAnalytics(period = 7) {
        // Simulate loading data
        $('#totalUsers').text('2,547');
        $('#totalRevenue').text('$125,430');
        $('#activeCases').text('156');
        $('#openTickets').text('42');
        
        // Initialize charts
        initCharts();
    }
    
    function initCharts() {
        // User Growth Chart
        new Chart(document.getElementById('userGrowthChart'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Users',
                    data: [120, 190, 300, 500, 700, 900],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            }
        });
        
        // User Status Chart
        new Chart(document.getElementById('userStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive', 'Suspended'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                }]
            }
        });
        
        // Revenue Chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue',
                    data: [12000, 19000, 15000, 25000, 22000, 30000],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)'
                }]
            }
        });
        
        // Case Status Chart
        new Chart(document.getElementById('caseStatusChart'), {
            type: 'pie',
            data: {
                labels: ['Open', 'In Progress', 'Resolved', 'Closed'],
                datasets: [{
                    data: [30, 45, 15, 10],
                    backgroundColor: ['#007bff', '#ffc107', '#28a745', '#6c757d']
                }]
            }
        });
    }
});
</script>


