<?php
/**
 * Notifications Page
 * 
 * Displays all user notifications with filtering, search, and management features
 * German interface with professional design
 */

require_once 'header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT * FROM user_notifications WHERE user_id = ?";
$params = [$_SESSION['user_id']];

// Apply filters
if ($filter == 'unread') {
    $query .= " AND is_read = 0";
} elseif ($filter == 'read') {
    $query .= " AND is_read = 1";
}

if ($type != 'all') {
    $query .= " AND type = ?";
    $params[] = $type;
}

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR message LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY created_at DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unreadCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 1");
$stmt->execute([$_SESSION['user_id']]);
$readCount = $stmt->fetchColumn();
?>

<style>
.notification-card {
    border-left: 4px solid #e0e0e0;
    transition: all 0.3s ease;
    cursor: pointer;
}

.notification-card:hover {
    background-color: #f8f9fa;
    border-left-color: #1890ff;
    transform: translateX(5px);
}

.notification-card.unread {
    background-color: #f0f7ff;
    border-left-color: #1890ff;
}

.notification-card.success {
    border-left-color: #52c41a;
}

.notification-card.warning {
    border-left-color: #faad14;
}

.notification-card.danger {
    border-left-color: #f5222d;
}

.notification-card.info {
    border-left-color: #1890ff;
}

.notification-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}

.notification-icon.success {
    background: linear-gradient(135deg, #52c41a, #73d13d);
}

.notification-icon.warning {
    background: linear-gradient(135deg, #faad14, #ffc53d);
}

.notification-icon.danger {
    background: linear-gradient(135deg, #f5222d, #ff4d4f);
}

.notification-icon.info {
    background: linear-gradient(135deg, #1890ff, #40a9ff);
}

.filter-btn {
    border-radius: 20px;
    padding: 8px 20px;
    margin-right: 10px;
    transition: all 0.3s ease;
}

.filter-btn.active {
    background: linear-gradient(135deg, #1890ff, #40a9ff);
    color: white;
    border-color: transparent;
}

.badge-pill {
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 600;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state-icon {
    font-size: 64px;
    color: #d9d9d9;
    margin-bottom: 20px;
}
</style>


    
    <div class="main-content">
        <div class="container-fluid">
            
            <!-- Page Header -->
            <div class="page-header">
                <h2 class="header-title">
                    <i class="anticon anticon-bell"></i> Benachrichtigungen
                </h2>
                <div class="header-sub-title">
                    <p class="m-b-0">Alle Ihre Benachrichtigungen und Updates</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row m-b-30">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="m-b-5 text-muted">Gesamt</p>
                                    <h2 class="m-b-0"><?= $totalCount ?></h2>
                                </div>
                                <div class="notification-icon info">
                                    <i class="anticon anticon-bell"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="m-b-5 text-muted">Ungelesen</p>
                                    <h2 class="m-b-0 text-primary"><?= $unreadCount ?></h2>
                                </div>
                                <div class="notification-icon info">
                                    <i class="anticon anticon-mail"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="m-b-5 text-muted">Gelesen</p>
                                    <h2 class="m-b-0 text-success"><?= $readCount ?></h2>
                                </div>
                                <div class="notification-icon success">
                                    <i class="anticon anticon-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="btn-group" role="group">
                                <a href="?filter=all" class="btn btn-default filter-btn <?= $filter == 'all' ? 'active' : '' ?>">
                                    Alle (<?= $totalCount ?>)
                                </a>
                                <a href="?filter=unread" class="btn btn-default filter-btn <?= $filter == 'unread' ? 'active' : '' ?>">
                                    Ungelesen (<?= $unreadCount ?>)
                                </a>
                                <a href="?filter=read" class="btn btn-default filter-btn <?= $filter == 'read' ? 'active' : '' ?>">
                                    Gelesen (<?= $readCount ?>)
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <?php if ($unreadCount > 0): ?>
                                <button class="btn btn-primary" onclick="markAllAsRead()">
                                    <i class="anticon anticon-check-circle"></i> Alle als gelesen markieren
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row m-t-20">
                        <div class="col-md-6">
                            <form method="GET" action="notifications.php" class="form-inline">
                                <div class="input-group" style="width: 100%;">
                                    <input type="text" name="search" class="form-control" placeholder="Benachrichtigungen durchsuchen..." value="<?= htmlspecialchars($search) ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="anticon anticon-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <select class="form-control" onchange="filterByType(this.value)">
                                <option value="all" <?= $type == 'all' ? 'selected' : '' ?>>Alle Typen</option>
                                <option value="success" <?= $type == 'success' ? 'selected' : '' ?>>Erfolg</option>
                                <option value="warning" <?= $type == 'warning' ? 'selected' : '' ?>>Warnung</option>
                                <option value="danger" <?= $type == 'danger' ? 'selected' : '' ?>>Fehler</option>
                                <option value="info" <?= $type == 'info' ? 'selected' : '' ?>>Info</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="card m-t-20">
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="anticon anticon-inbox"></i>
                            </div>
                            <h4>Keine Benachrichtigungen gefunden</h4>
                            <p class="text-muted">
                                <?php if (!empty($search)): ?>
                                    Keine Benachrichtigungen entsprechen Ihrer Suche.
                                <?php elseif ($filter == 'unread'): ?>
                                    Sie haben keine ungelesenen Benachrichtigungen.
                                <?php else: ?>
                                    Sie haben noch keine Benachrichtigungen erhalten.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item notification-card <?= $notification['is_read'] ? 'read' : 'unread' ?> <?= $notification['type'] ?>" 
                                     onclick="showNotificationDetails(<?= $notification['id'] ?>)">
                                    <div class="media align-items-center">
                                        <div class="notification-icon <?= $notification['type'] ?> m-r-20">
                                            <?php
                                            $icon = 'bell';
                                            if ($notification['type'] == 'success') $icon = 'check-circle';
                                            elseif ($notification['type'] == 'warning') $icon = 'exclamation-circle';
                                            elseif ($notification['type'] == 'danger') $icon = 'close-circle';
                                            elseif ($notification['type'] == 'info') $icon = 'info-circle';
                                            ?>
                                            <i class="anticon anticon-<?= $icon ?>"></i>
                                        </div>
                                        <div class="media-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h5 class="m-b-5">
                                                        <?= htmlspecialchars($notification['title']) ?>
                                                        <?php if (!$notification['is_read']): ?>
                                                            <span class="badge badge-pill badge-primary ml-2">Neu</span>
                                                        <?php endif; ?>
                                                    </h5>
                                                    <p class="m-b-5 text-muted">
                                                        <?= htmlspecialchars(strlen($notification['message']) > 150 ? substr($notification['message'], 0, 150) . '...' : $notification['message']) ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        <i class="anticon anticon-clock-circle"></i>
                                                        <?= formatNotificationDate($notification['created_at']) ?>
                                                    </small>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-icon btn-hover" data-toggle="dropdown" onclick="event.stopPropagation()">
                                                        <i class="anticon anticon-more"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <?php if (!$notification['is_read']): ?>
                                                            <a class="dropdown-item" href="#" onclick="event.stopPropagation(); markAsRead(<?= $notification['id'] ?>)">
                                                                <i class="anticon anticon-check"></i> Als gelesen markieren
                                                            </a>
                                                        <?php else: ?>
                                                            <a class="dropdown-item" href="#" onclick="event.stopPropagation(); markAsUnread(<?= $notification['id'] ?>)">
                                                                <i class="anticon anticon-mail"></i> Als ungelesen markieren
                                                            </a>
                                                        <?php endif; ?>
                                                        <a class="dropdown-item text-danger" href="#" onclick="event.stopPropagation(); deleteNotification(<?= $notification['id'] ?>)">
                                                            <i class="anticon anticon-delete"></i> Löschen
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>


<!-- Notification Details Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="anticon anticon-bell"></i> <span id="modalTitle"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="modalContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to format notification dates
function formatNotificationDate($datetime) {
    $date = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->y > 0) {
        return $diff->y . ' Jahr' . ($diff->y > 1 ? 'e' : '') . ' her';
    } elseif ($diff->m > 0) {
        return $diff->m . ' Monat' . ($diff->m > 1 ? 'e' : '') . ' her';
    } elseif ($diff->d > 0) {
        if ($diff->d == 1) return 'Gestern';
        return $diff->d . ' Tag' . ($diff->d > 1 ? 'e' : '') . ' her';
    } elseif ($diff->h > 0) {
        return $diff->h . ' Stunde' . ($diff->h > 1 ? 'n' : '') . ' her';
    } elseif ($diff->i > 0) {
        return $diff->i . ' Minute' . ($diff->i > 1 ? 'n' : '') . ' her';
    } else {
        return 'Gerade eben';
    }
}
?>
<?php require_once 'footer.php'; ?>
<script>
function filterByType(type) {
    const url = new URL(window.location.href);
    url.searchParams.set('type', type);
    window.location.href = url.toString();
}

function showNotificationDetails(id) {
    // Mark as read and show details
    $.ajax({
        url: 'ajax/mark_notification_read.php',
        method: 'POST',
        data: { notification_id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Get notification details
                $.ajax({
                    url: 'ajax/get_notification_details.php',
                    method: 'GET',
                    data: { id: id },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            $('#modalTitle').text(data.notification.title);
                            let content = `
                                <div class="notification-detail">
                                    <div class="alert alert-${data.notification.type}">
                                        <strong>${getTypeLabel(data.notification.type)}</strong>
                                    </div>
                                    <div class="m-t-20">
                                        <p>${data.notification.message}</p>
                                    </div>
                                    <hr>
                                    <small class="text-muted">
                                        <i class="anticon anticon-clock-circle"></i>
                                        ${formatDate(data.notification.created_at)}
                                    </small>
                                </div>
                            `;
                            $('#modalContent').html(content);
                            $('#notificationModal').modal('show');
                            
                            // Reload page after modal closes to update counts
                            $('#notificationModal').on('hidden.bs.modal', function() {
                                location.reload();
                            });
                        }
                    }
                });
            }
        }
    });
}

function markAsRead(id) {
    $.ajax({
        url: 'ajax/mark_notification_read.php',
        method: 'POST',
        data: { notification_id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            }
        }
    });
}

function markAsUnread(id) {
    $.ajax({
        url: 'ajax/mark_notification_read.php',
        method: 'POST',
        data: { notification_id: id, mark_unread: true },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            }
        }
    });
}

function markAllAsRead() {
    if (confirm('Möchten Sie wirklich alle Benachrichtigungen als gelesen markieren?')) {
        $.ajax({
            url: 'ajax/mark_all_notifications_read.php',
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    }
}

function deleteNotification(id) {
    if (confirm('Möchten Sie diese Benachrichtigung wirklich löschen?')) {
        $.ajax({
            url: 'ajax/delete_notification.php',
            method: 'POST',
            data: { notification_id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    }
}

function getTypeLabel(type) {
    const labels = {
        'success': 'Erfolg',
        'warning': 'Warnung',
        'danger': 'Fehler',
        'info': 'Information'
    };
    return labels[type] || 'Benachrichtigung';
}

function formatDate(datetime) {
    const date = new Date(datetime);
    return date.toLocaleString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>

