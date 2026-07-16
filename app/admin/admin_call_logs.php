<?php
/**
 * admin_call_logs.php — Voice call log viewer in the admin panel.
 * Shows all call log entries with filters and DataTables pagination.
 */
require_once 'admin_header.php';

// Ensure the table exists (safe idempotent DDL)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `voice_call_logs` (
            `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            `session_id`    INT UNSIGNED    NOT NULL,
            `initiated_by`  ENUM('user','admin') NOT NULL DEFAULT 'user',
            `user_id`       INT UNSIGNED    NULL DEFAULT NULL,
            `status`        ENUM('ringing','answered','rejected','missed','ended') NOT NULL DEFAULT 'ringing',
            `started_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `answered_at`   DATETIME        NULL DEFAULT NULL,
            `ended_at`      DATETIME        NULL DEFAULT NULL,
            `duration_sec`  INT UNSIGNED    NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_vcl_session`  (`session_id`),
            KEY `idx_vcl_user`     (`user_id`),
            KEY `idx_vcl_status`   (`status`),
            KEY `idx_vcl_started`  (`started_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) { /* already exists */ }

// Stats summary
try {
    $stats = $pdo->query("
        SELECT
            COUNT(*) AS total,
            SUM(status='answered' OR status='ended') AS answered,
            SUM(status='missed')  AS missed,
            SUM(status='rejected') AS rejected,
            COALESCE(SUM(duration_sec),0) AS total_seconds
        FROM voice_call_logs
    ")->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['total'=>0,'answered'=>0,'missed'=>0,'rejected'=>0,'total_seconds'=>0];
}
?>

<div class="main-content">
    <div class="page-header">
        <h2>Voice Call Logs</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Call Logs</span>
            </nav>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-primary"><?= (int)$stats['total'] ?></h4>
                    <small class="text-muted">Total Calls</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-success"><?= (int)$stats['answered'] ?></h4>
                    <small class="text-muted">Answered</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-danger"><?= (int)$stats['missed'] ?></h4>
                    <small class="text-muted">Missed</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-warning"><?= (int)$stats['rejected'] ?></h4>
                    <small class="text-muted">Rejected</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="mb-0">All Call Records</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <!-- Status filter -->
                    <select class="form-control form-control-sm" id="statusFilter" style="width:auto;">
                        <option value="">All Statuses</option>
                        <option value="answered">Answered</option>
                        <option value="ended">Ended</option>
                        <option value="missed">Missed</option>
                        <option value="rejected">Rejected</option>
                        <option value="ringing">Ringing</option>
                    </select>
                    <!-- Initiated by filter -->
                    <select class="form-control form-control-sm" id="initiatedFilter" style="width:auto;">
                        <option value="">Both Sides</option>
                        <option value="user">User-initiated</option>
                        <option value="admin">Admin-initiated</option>
                    </select>
                    <button class="btn btn-sm btn-secondary" id="resetFilters">
                        <i class="anticon anticon-reload"></i> Reset
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="callLogsTable" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Session</th>
                            <th>User</th>
                            <th>Initiated By</th>
                            <th>Status</th>
                            <th>Started At</th>
                            <th>Answered At</th>
                            <th>Ended At</th>
                            <th>Duration</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.badge-answered { background:#28a745; color:#fff; }
.badge-ended    { background:#17a2b8; color:#fff; }
.badge-missed   { background:#dc3545; color:#fff; }
.badge-rejected { background:#fd7e14; color:#fff; }
.badge-ringing  { background:#ffc107; color:#212529; }
.gap-2 { gap:.5rem; }
</style>

<script>
$(function(){
    var table = $('#callLogsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/call_logs_data.php',
            type: 'POST',
            data: function(d){
                d.status_filter    = $('#statusFilter').val();
                d.initiated_filter = $('#initiatedFilter').val();
            }
        },
        columns: [
            { data: 'id',           orderable: true  },
            { data: 'session_id',   orderable: true  },
            { data: 'user_name',    orderable: false },
            { data: 'initiated_by', orderable: true  },
            { data: 'status_badge', orderable: true  },
            { data: 'started_at',   orderable: true  },
            { data: 'answered_at',  orderable: true  },
            { data: 'ended_at',     orderable: true  },
            { data: 'duration',     orderable: true  },
            { data: 'actions',      orderable: false }
        ],
        order: [[0,'desc']],
        pageLength: 25,
        language: { processing:'<div class="spinner-border spinner-border-sm text-primary" role="status"></div>' }
    });

    // Filter change → reload
    $('#statusFilter, #initiatedFilter').on('change', function(){ table.ajax.reload(); });
    $('#resetFilters').on('click', function(){
        $('#statusFilter').val('');
        $('#initiatedFilter').val('');
        table.ajax.reload();
    });

    // Open chat session on row action
    $(document).on('click', '.btn-open-session', function(){
        var sid = $(this).data('session');
        window.open('admin_live_chat.php?session='+sid, '_blank');
    });
});
</script>
<?php require_once 'admin_footer.php'; ?>
