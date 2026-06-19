<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? 'list';
$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;

try {
    $nameColumn = 'method_name';
    $codeColumn = 'method_code';
    $statusColumn = 'is_active';
    $createdAtColumn = '';
    $updatedAtColumn = '';

    if ($action === 'get') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            throw new Exception('Invalid payment method ID');
        }

        $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $method = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$method) {
            throw new Exception('Payment method not found');
        }

        $method['method_name'] = $method[$nameColumn] ?? '';
        $method['method_code'] = $method[$codeColumn] ?? '';
        $method['status'] = ((int)($method[$statusColumn] ?? 1) === 1) ? 'active' : 'inactive';

        echo json_encode(['success' => true, 'method' => $method]);
        exit;
    }

    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $methodName = trim((string)($_POST['method_name'] ?? ''));
        $methodCode = trim((string)($_POST['method_code'] ?? ''));
        $status = strtolower(trim((string)($_POST['status'] ?? 'active')));
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }
        $isActive = $status === 'active' ? 1 : 0;

        if ($methodName === '') {
            throw new Exception('Method name is required');
        }

        if ($methodCode === '') {
            $generatedCode = strtolower(preg_replace('/[^a-z0-9]+/', '_', $methodName));
            $generatedCode = trim($generatedCode, '_');
            $methodCode = $generatedCode !== '' ? $generatedCode : ('method_' . time());
        }

        $duplicateParts = ["`$nameColumn` = :dup_name"];
        $duplicateParams = [':dup_name' => $methodName];
        $duplicateParts[] = "`$codeColumn` = :dup_code";
        $duplicateParams[':dup_code'] = $methodCode;
        $duplicateSql = "SELECT id FROM payment_methods WHERE (" . implode(' OR ', $duplicateParts) . ")";
        if ($id > 0) {
            $duplicateSql .= " AND id != :id";
            $duplicateParams[':id'] = $id;
        }
        $dupStmt = $pdo->prepare($duplicateSql);
        $dupStmt->execute($duplicateParams);
        if ($dupStmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Method name or code already exists');
        }

        if ($id > 0) {
            $setParts = ["`$nameColumn` = :method_name"];
            $params = [
                ':method_name' => $methodName,
                ':id' => $id,
                ':method_code' => $methodCode,
                ':is_active' => $isActive
            ];
            $setParts[] = "`$codeColumn` = :method_code";
            $setParts[] = "`$statusColumn` = :is_active";
            if ($updatedAtColumn !== '') {
                $setParts[] = "`$updatedAtColumn` = NOW()";
            }

            $sql = "UPDATE payment_methods SET " . implode(', ', $setParts) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'Payment method updated']);
            exit;
        }

        $insertColumns = ["`$nameColumn`"];
        $insertValues = [':method_name'];
        $insertParams = [':method_name' => $methodName];
        $insertColumns[] = "`$codeColumn`";
        $insertValues[] = ':method_code';
        $insertParams[':method_code'] = $methodCode;
        $insertColumns[] = "`$statusColumn`";
        $insertValues[] = ':is_active';
        $insertParams[':is_active'] = $isActive;
        if ($createdAtColumn !== '') {
            $insertColumns[] = "`$createdAtColumn`";
            $insertValues[] = 'NOW()';
        }
        if ($updatedAtColumn !== '') {
            $insertColumns[] = "`$updatedAtColumn`";
            $insertValues[] = 'NOW()';
        }

        $sql = "INSERT INTO payment_methods (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($insertParams);
        echo json_encode(['success' => true, 'message' => 'Payment method created']);
        exit;
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            throw new Exception('Invalid payment method ID');
        }

        $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Payment method deleted']);
        exit;
    }

    $start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
    $length = isset($_POST['length']) ? max(1, (int)$_POST['length']) : 10;
    $search = trim((string)($_POST['search']['value'] ?? ''));

    $whereSql = '';
    $whereParams = [];
    if ($search !== '') {
        $conditions = [
            "pm.`$nameColumn` LIKE :search",
            "pm.`$codeColumn` LIKE :search",
            "pm.bank_name LIKE :search",
            "pm.account_number LIKE :search",
            "pm.wallet_address LIKE :search"
        ];
        $whereSql = ' WHERE ' . implode(' OR ', $conditions);
        $whereParams[':search'] = '%' . $search . '%';
    }

    $countTotalStmt = $pdo->query("SELECT COUNT(*) FROM payment_methods");
    $recordsTotal = (int)$countTotalStmt->fetchColumn();

    $countFilteredSql = "SELECT COUNT(*) FROM payment_methods pm" . $whereSql;
    $countFilteredStmt = $pdo->prepare($countFilteredSql);
    $countFilteredStmt->execute($whereParams);
    $recordsFiltered = (int)$countFilteredStmt->fetchColumn();

    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 4;
    $orderDirection = strtolower((string)($_POST['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

    $columnMap = [
        0 => 'pm.id',
        1 => "pm.`$codeColumn`",
        2 => "pm.`$nameColumn`",
        3 => "pm.`$statusColumn`",
        4 => $createdAtColumn !== '' ? "pm.`$createdAtColumn`" : "pm.id"
    ];
    $orderBy = $columnMap[$orderColumn] ?? $columnMap[4];

    $dataSql = "
        SELECT
            pm.id,
            pm.`$codeColumn` AS method_code,
            pm.`$nameColumn` AS method_name,
            CASE WHEN pm.`$statusColumn` = 1 THEN 'active' ELSE 'inactive' END AS status,
            " . ($createdAtColumn !== '' ? "pm.`$createdAtColumn`" : 'NULL') . " AS created_at
        FROM payment_methods pm
        $whereSql
        ORDER BY $orderBy $orderDirection
        LIMIT :start, :length
    ";
    $dataStmt = $pdo->prepare($dataSql);
    foreach ($whereParams as $key => $value) {
        $dataStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $dataStmt->bindValue(':start', $start, PDO::PARAM_INT);
    $dataStmt->bindValue(':length', $length, PDO::PARAM_INT);
    $dataStmt->execute();
    $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
} catch (PDOException $e) {
    if ($action === 'list') {
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Failed to load payment methods'
        ]);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($action === 'list') {
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => $e->getMessage()
        ]);
        exit;
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>