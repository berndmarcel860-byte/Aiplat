<?php
/**
 * call_logs_data.php — Server-side DataTables handler for voice_call_logs.
 */
require_once '../admin_session.php';
header('Content-Type: application/json');

// Ensure table exists
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
            KEY `idx_vcl_session` (`session_id`),
            KEY `idx_vcl_user`    (`user_id`),
            KEY `idx_vcl_status`  (`status`),
            KEY `idx_vcl_started` (`started_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) { /* already exists */ }

// DataTables parameters
$draw   = (int)($_POST['draw']   ?? 1);
$start  = (int)($_POST['start']  ?? 0);
$length = (int)($_POST['length'] ?? 25);
if ($length < 1 || $length > 100) $length = 25;

$statusFilter    = $_POST['status_filter']    ?? '';
$initiatedFilter = $_POST['initiated_filter'] ?? '';
$search = trim($_POST['search']['value'] ?? '');

// Whitelist for order column
$allowedCols = ['vcl.id','vcl.session_id','vcl.initiated_by','vcl.status','vcl.started_at','vcl.answered_at','vcl.ended_at','vcl.duration_sec'];
$orderColIdx = (int)($_POST['order'][0]['column'] ?? 0);
$colMap = [0=>'vcl.id',1=>'vcl.session_id',2=>'vcl.id',3=>'vcl.initiated_by',4=>'vcl.status',5=>'vcl.started_at',6=>'vcl.answered_at',7=>'vcl.ended_at',8=>'vcl.duration_sec',9=>'vcl.id'];
$orderCol = $colMap[$orderColIdx] ?? 'vcl.id';
$orderDir = (($_POST['order'][0]['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';

// Build WHERE
$where = ['1=1'];
$params = [];

$allowedStatuses = ['answered','ended','missed','rejected','ringing'];
if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
    $where[] = 'vcl.status = ?';
    $params[] = $statusFilter;
}
$allowedInit = ['user','admin'];
if ($initiatedFilter !== '' && in_array($initiatedFilter, $allowedInit, true)) {
    $where[] = 'vcl.initiated_by = ?';
    $params[] = $initiatedFilter;
}
if ($search !== '') {
    $where[] = "(CONCAT(u.first_name,' ',u.last_name) LIKE ? OR u.email LIKE ? OR vcl.session_id LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSQL = implode(' AND ', $where);

try {
    // Total records (no filter)
    $total = (int)$pdo->query("SELECT COUNT(*) FROM voice_call_logs")->fetchColumn();

    // Filtered count
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM voice_call_logs vcl
        LEFT JOIN users u ON u.id = vcl.user_id
        WHERE $whereSQL
    ");
    $countStmt->execute($countParams);
    $filteredCount = (int)$countStmt->fetchColumn();

    // Data
    $dataParams = array_merge($params, [$length, $start]);
    $dataStmt = $pdo->prepare("
        SELECT
            vcl.id,
            vcl.session_id,
            vcl.initiated_by,
            vcl.status,
            vcl.started_at,
            vcl.answered_at,
            vcl.ended_at,
            vcl.duration_sec,
            vcl.user_id,
            CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS user_name,
            u.email AS user_email
        FROM voice_call_logs vcl
        LEFT JOIN users u ON u.id = vcl.user_id
        WHERE $whereSQL
        ORDER BY $orderCol $orderDir
        LIMIT ? OFFSET ?
    ");
    $dataStmt->execute($dataParams);
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format rows
    $data = [];
    foreach ($rows as $r) {
        // Status badge
        $statusBadge = '<span class="badge badge-' . htmlspecialchars($r['status']) . '">' . ucfirst(htmlspecialchars($r['status'])) . '</span>';

        // Initiated-by badge
        $initBadge = $r['initiated_by'] === 'admin'
            ? '<span class="badge badge-primary">Admin</span>'
            : '<span class="badge badge-secondary">User</span>';

        // Duration
        $dur = '—';
        if ($r['duration_sec'] !== null && $r['duration_sec'] !== '') {
            $sec = (int)$r['duration_sec'];
            $m   = intdiv($sec, 60);
            $s   = $sec % 60;
            $dur = sprintf('%02d:%02d', $m, $s);
        }

        // User name
        $userName = trim($r['user_name']) ?: '—';
        if ($r['user_email']) {
            $userName .= '<br><small class="text-muted">' . htmlspecialchars($r['user_email']) . '</small>';
        }
        if ($r['user_id']) {
            $userName .= '<br><small><a href="admin_view_users.php?id=' . (int)$r['user_id'] . '" target="_blank">View profile</a></small>';
        }

        // Dates
        $fmt = fn($dt) => $dt ? date('d.m.Y H:i:s', strtotime($dt)) : '—';

        $data[] = [
            'id'           => (int)$r['id'],
            'session_id'   => '<a href="admin_live_chat.php?session=' . (int)$r['session_id'] . '" class="btn-open-session" data-session="' . (int)$r['session_id'] . '" target="_blank">#' . (int)$r['session_id'] . '</a>',
            'user_name'    => $userName,
            'initiated_by' => $initBadge,
            'status_badge' => $statusBadge,
            'started_at'   => $fmt($r['started_at']),
            'answered_at'  => $fmt($r['answered_at']),
            'ended_at'     => $fmt($r['ended_at']),
            'duration'     => $dur,
            'actions'      => '<button class="btn btn-xs btn-outline-primary btn-open-session" data-session="' . (int)$r['session_id'] . '" title="Open session"><i class="anticon anticon-message"></i></button>',
        ];
    }

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $total,
        'recordsFiltered' => $filteredCount,
        'data'            => $data,
    ]);
} catch (PDOException $e) {
    error_log('call_logs_data: ' . $e->getMessage());
    echo json_encode(['draw'=>$draw,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[],'error'=>'Database error']);
}
