class AdminDashboard {
    constructor() {
        this.charts = {
            recovery: null,
            activity: null
        };
        
        this.dataTables = {
            recentCases: null,
            recentUsers: null
        };
        
        this.init();
    }
    
    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initComponents();
            this.loadInitialData();
            this.registerEventHandlers();
        });
    }
    
    initComponents() {
        this.initCharts();
        this.initDataTables();
        this.initNotifications();
        this.initUIComponents();
    }
    
    initCharts() {
        // Recovery Progress Chart
        const recoveryCtx = document.getElementById('recoveryChart')?.getContext('2d');
        if (recoveryCtx) {
            this.charts.recovery = new Chart(recoveryCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Recovered', 'In Progress', 'Pending'],
                    datasets: [{
                        data: [0, 0, 0],
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
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: $${value.toLocaleString()}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Activity Timeline Chart
        const activityCtx = document.getElementById('activityChart')?.getContext('2d');
        if (activityCtx) {
            this.charts.activity = new Chart(activityCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Admin Activities',
                        data: [],
                        backgroundColor: 'rgba(92, 107, 192, 0.2)',
                        borderColor: 'rgba(92, 107, 192, 1)',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
    
    initDataTables() {
        // Recent Cases Table
        const recentCasesTable = document.getElementById('recentCasesTable');
        if (recentCasesTable) {
            this.dataTables.recentCases = new DataTable(recentCasesTable, {
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/app/admin/admin_ajax/get_recent_cases.php',
                    type: 'POST'
                },
                columns: [
                    { data: 'case_number' },
                    { data: 'user_name' },
                    { 
                        data: 'reported_amount',
                        render: (data) => {
                            return `$${parseFloat(data).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                        }
                    },
                    { 
                        data: 'status',
                        render: (data) => {
                            const statusClass = {
                                'open': 'secondary',
                                'documents_required': 'info',
                                'under_review': 'warning',
                                'refund_approved': 'success',
                                'refund_rejected': 'danger',
                                'closed': 'dark'
                            }[data] || 'secondary';
                            return `<span class="badge bg-${statusClass}">${data.replace(/_/g, ' ')}</span>`;
                        }
                    },
                    {
                        data: 'id',
                        render: (data) => {
                            return `<a href="case_details.php?id=${data}" class="btn btn-sm btn-primary">View</a>`;
                        },
                        orderable: false
                    }
                ],
                order: [[0, 'desc']],
                searching: false,
                lengthChange: false,
                pageLength: 5,
                dom: 't<"table-footer"p>'
            });
        }

        // Recent Users Table
        const recentUsersTable = document.getElementById('recentUsersTable');
        if (recentUsersTable) {
            this.dataTables.recentUsers = new DataTable(recentUsersTable, {
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/app/admin/admin_ajax/get_recent_users.php',
                    type: 'POST'
                },
                columns: [
                    { data: 'id' },
                    { 
                        render: (data) => {
                            return `${data.first_name} ${data.last_name}`;
                        }
                    },
                    { data: 'email' },
                    { 
                        data: 'created_at',
                        render: (data) => {
                            return moment(data).format('MMM D, YYYY');
                        }
                    },
                    {
                        data: 'id',
                        render: (data) => {
                            return `<a href="user_details.php?id=${data}" class="btn btn-sm btn-primary">View</a>`;
                        },
                        orderable: false
                    }
                ],
                order: [[0, 'desc']],
                searching: false,
                lengthChange: false,
                pageLength: 5,
                dom: 't<"table-footer"p>'
            });
        }
    }
    
    initNotifications() {
        this.checkNotificationPermission();
        
        this.notificationInterval = setInterval(() => this.fetchNewNotifications(), 120000);
        this.fetchNewNotifications();

        document.getElementById('notificationDropdown')?.addEventListener('click', (e) => {
            const notificationItem = e.target.closest('.notification-item');
            if (notificationItem) {
                const notificationId = notificationItem.dataset.id;
                this.markNotificationAsRead(notificationId);
            }
        });
    }
    
    initUIComponents() {
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map((el) => new bootstrap.Tooltip(el));
        
        // Initialize Bootstrap popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map((el) => new bootstrap.Popover(el));
        
        // Sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
        });
        
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
        
        // Dark mode toggle
        document.getElementById('darkModeToggle')?.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkMode);
            this.updateChartsTheme(isDarkMode);
        });
        
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    }
    
    registerEventHandlers() {
        document.getElementById('refreshDashboard')?.addEventListener('click', () => {
            this.loadDashboardStats();
            this.loadRecentActivities();
            this.dataTables.recentCases?.ajax.reload();
            this.dataTables.recentUsers?.ajax.reload();
            this.showToast('Dashboard refreshed', 'success');
        });
        
        document.getElementById('caseStatusFilter')?.addEventListener('change', (e) => {
            const status = e.target.value;
            this.dataTables.recentCases?.column(3).search(status === 'all' ? '' : status).draw();
        });
    }
    
    loadInitialData() {
        this.loadDashboardStats();
        this.loadRecentActivities();
    }
    
    async loadDashboardStats() {
        try {
            const response = await fetch('/app/admin/admin_ajax/get_dashboard_stats.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateElementText('#totalUsers', data.data.total_users.toLocaleString());
                this.updateElementText('#activeCases', data.data.active_cases.toLocaleString());
                this.updateElementText('#pendingWithdrawals', data.data.pending_withdrawals.toLocaleString());
                this.updateElementText('#totalRecovered', `$${data.data.total_recovered.toLocaleString()}`);
                
                if (this.charts.recovery) {
                    this.charts.recovery.data.datasets[0].data = [
                        data.data.total_recovered,
                        data.data.in_progress_amount,
                        data.data.pending_amount
                    ];
                    this.charts.recovery.update();
                }
            } else {
                throw new Error(data.message || 'Failed to load dashboard statistics');
            }
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
            this.showToast('Failed to load dashboard statistics', 'error');
        }
    }
    
    async loadRecentActivities() {
        try {
            const response = await fetch('/app/admin/admin_ajax/get_recent_activities.php');
            const data = await response.json();
            
            if (data.success) {
                const activityList = document.getElementById('activityList');
                if (activityList) {
                    activityList.innerHTML = data.data.map(activity => `
                        <div class="activity-item">
                            <div class="d-flex justify-content-between">
                                <p class="mb-1">
                                    <strong>${activity.admin_name}</strong>
                                    ${activity.action}
                                    ${activity.entity_type ? 'on ' + activity.entity_type : ''}
                                </p>
                                <span class="activity-time">
                                    ${moment(activity.created_at).fromNow()}
                                </span>
                            </div>
                            ${activity.details ? `<p class="text-muted mb-0">${activity.details}</p>` : ''}
                        </div>
                    `).join('');
                }
                
                this.updateActivityChart(data.data);
            } else {
                throw new Error(data.message || 'Failed to load recent activities');
            }
        } catch (error) {
            console.error('Error loading recent activities:', error);
            this.showToast('Failed to load recent activities', 'error');
        }
    }
    
    updateActivityChart(activities) {
        if (!this.charts.activity) return;
        
        const activityCounts = {};
        const now = moment();
        
        activities.forEach(activity => {
            const hour = moment(activity.created_at).startOf('hour').format('HH:mm');
            activityCounts[hour] = (activityCounts[hour] || 0) + 1;
        });
        
        const labels = [];
        const data = [];
        
        for (let i = 11; i >= 0; i--) {
            const time = now.clone().subtract(i, 'hours').startOf('hour').format('HH:mm');
            labels.push(time);
            data.push(activityCounts[time] || 0);
        }
        
        this.charts.activity.data.labels = labels;
        this.charts.activity.data.datasets[0].data = data;
        this.charts.activity.update();
    }
    
    async fetchNewNotifications() {
        try {
            const response = await fetch('/app/admin/admin_ajax/get_notifications.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateNotificationBadge(data.data.length);
                this.updateNotificationDropdown(data.data);
                
                if (!document.hasFocus() && data.data.length > 0) {
                    this.showDesktopNotification('New notifications', `You have ${data.data.length} new notifications`);
                }
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }
    
    updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (!badge) return;
        
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
    
    updateNotificationDropdown(notifications) {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;
        
        const notificationList = dropdown.querySelector('.notification-list');
        if (!notificationList) return;
        
        notificationList.innerHTML = '';
        
        if (notifications.length === 0) {
            notificationList.innerHTML = '<div class="dropdown-item text-center">No new notifications</div>';
            return;
        }
        
        notifications.forEach(notification => {
            const item = document.createElement('div');
            item.className = `dropdown-item notification-item d-flex align-items-center ${notification.is_read ? '' : 'bg-light'}`;
            item.dataset.id = notification.id;
            item.innerHTML = `
                <div class="me-3">
                    <div class="icon-circle bg-${notification.type}">
                        <i class="fas fa-bell text-white"></i>
                    </div>
                </div>
                <div>
                    <div class="small text-gray-500">${moment(notification.created_at).format('MMM D, h:mm A')}</div>
                    <span class="${notification.is_read ? '' : 'fw-bold'}">${notification.title}</span>
                </div>
            `;
            notificationList.appendChild(item);
        });
        
        const showAll = document.createElement('a');
        showAll.className = 'dropdown-item text-center small text-gray-500';
        showAll.href = 'notifications.php';
        showAll.textContent = 'Show All Notifications';
        notificationList.appendChild(showAll);
    }
    
    async markNotificationAsRead(notificationId) {
        try {
            const response = await fetch('/app/admin/admin_ajax/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `notification_id=${notificationId}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (notificationItem) {
                    notificationItem.classList.remove('bg-light', 'fw-bold');
                    this.fetchNewNotifications();
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    checkNotificationPermission() {
        if (!('Notification' in window)) return;
        
        if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    console.log('Notification permission granted');
                }
            });
        }
    }
    
    showDesktopNotification(title, message) {
        if (!('Notification' in window)) return;
        
        if (Notification.permission === 'granted') {
            new Notification(title, { body: message });
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    new Notification(title, { body: message });
                }
            });
        }
    }
    
    showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer') || this.createToastContainer();
        const toast = document.createElement('div');
        
        toast.className = `toast show align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '11';
        document.body.appendChild(container);
        return container;
    }
    
    updateElementText(selector, text) {
        const element = document.querySelector(selector);
        if (element) element.textContent = text;
    }
    
    updateChartsTheme(isDarkMode) {
        const textColor = isDarkMode ? '#f8f9fa' : '#212529';
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
        
        Object.values(this.charts).forEach(chart => {
            if (chart) {
                chart.options.scales = {
                    ...chart.options.scales,
                    x: {
                        ...chart.options.scales?.x,
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    },
                    y: {
                        ...chart.options.scales?.y,
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    }
                };
                
                chart.options.plugins = {
                    ...chart.options.plugins,
                    legend: {
                        ...chart.options.plugins?.legend,
                        labels: { color: textColor }
                    }
                };
                
                chart.update();
            }
        });
    }
}

// Initialize the dashboard
new AdminDashboard();

// Export for testing if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminDashboard;
}