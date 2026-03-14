<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

try {
    $draw   = intval($_POST['draw'] ?? 1);
    $start  = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $search = trim($_POST['search']['value'] ?? '');
    $category = trim($_POST['category'] ?? '');

    $where  = '1=1';
    $params = [];

    if ($search !== '') {
        $where .= " AND (notification_key LIKE ? OR name LIKE ? OR subject LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($category !== '') {
        $where .= " AND category = ?";
        $params[] = $category;
    }

    $totalStmt = $pdo->query("SELECT COUNT(*) FROM email_notifications");
    $totalRecords = (int)$totalStmt->fetchColumn();

    $filteredStmt = $pdo->prepare("SELECT COUNT(*) FROM email_notifications WHERE $where");
    $filteredStmt->execute($params);
    $filteredRecords = (int)$filteredStmt->fetchColumn();

    // LIMIT and OFFSET cannot use named PDO parameters; using already-sanitized int casts.
    $limitSafe  = (int)$length;
    $offsetSafe = (int)$start;
    $stmt = $pdo->prepare("
        SELECT id, notification_key, name, subject, category, is_active, updated_at
        FROM email_notifications
        WHERE $where
        ORDER BY category ASC, name ASC
        LIMIT $limitSafe OFFSET $offsetSafe
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data'            => $rows,
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
