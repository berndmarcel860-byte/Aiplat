<?php
require_once '../admin_session.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? 'list';
$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;

try {
    // ------------------------------------------------------------------ stats
    if ($action === 'stats') {
        $total    = (int)$pdo->query("SELECT COUNT(*) FROM payment_methods")->fetchColumn();
        $active   = (int)$pdo->query("SELECT COUNT(*) FROM payment_methods WHERE is_active = 1")->fetchColumn();
        $inactive = $total - $active;
        $crypto   = (int)$pdo->query("SELECT COUNT(*) FROM payment_methods WHERE is_crypto = 1")->fetchColumn();
        echo json_encode(['success' => true, 'total' => $total, 'active' => $active, 'inactive' => $inactive, 'crypto' => $crypto]);
        exit;
    }

    // ------------------------------------------------------------------- get
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

        $method['status'] = ((int)($method['is_active'] ?? 1) === 1) ? 'active' : 'inactive';

        echo json_encode(['success' => true, 'method' => $method]);
        exit;
    }

    // ------------------------------------------------------------------ save
    if ($action === 'save') {
        $id              = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $methodName      = trim((string)($_POST['method_name'] ?? ''));
        $methodCode      = trim((string)($_POST['method_code'] ?? ''));
        $status          = strtolower(trim((string)($_POST['status'] ?? 'active')));
        $bankName        = trim((string)($_POST['bank_name'] ?? ''));
        $accountNumber   = trim((string)($_POST['account_number'] ?? ''));
        $routingNumber   = trim((string)($_POST['routing_number'] ?? ''));
        $walletAddress   = trim((string)($_POST['wallet_address'] ?? ''));
        $paymentDetails  = trim((string)($_POST['payment_details'] ?? ''));
        $instructions    = trim((string)($_POST['instructions'] ?? ''));
        $minAmount       = $_POST['min_amount'] !== '' && $_POST['min_amount'] !== null ? (float)$_POST['min_amount'] : null;
        $maxAmount       = $_POST['max_amount'] !== '' && $_POST['max_amount'] !== null ? (float)$_POST['max_amount'] : null;
        $allowsDeposit   = isset($_POST['allows_deposit']) && $_POST['allows_deposit'] == '1' ? 1 : 0;
        $allowsWithdraw  = isset($_POST['allows_withdrawal']) && $_POST['allows_withdrawal'] == '1' ? 1 : 0;
        $isCrypto        = isset($_POST['is_crypto']) && $_POST['is_crypto'] == '1' ? 1 : 0;

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

        // Duplicate check
        $dupSql = "SELECT id FROM payment_methods WHERE (method_name = :dup_name OR method_code = :dup_code)";
        $dupParams = [':dup_name' => $methodName, ':dup_code' => $methodCode];
        if ($id > 0) {
            $dupSql .= " AND id != :id";
            $dupParams[':id'] = $id;
        }
        $dupStmt = $pdo->prepare($dupSql);
        $dupStmt->execute($dupParams);
        if ($dupStmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Method name or code already exists');
        }

        $fields = [
            'method_name'       => $methodName,
            'method_code'       => $methodCode,
            'is_active'         => $isActive,
            'bank_name'         => $bankName !== '' ? $bankName : null,
            'account_number'    => $accountNumber !== '' ? $accountNumber : null,
            'routing_number'    => $routingNumber !== '' ? $routingNumber : null,
            'wallet_address'    => $walletAddress !== '' ? $walletAddress : null,
            'payment_details'   => $paymentDetails !== '' ? $paymentDetails : null,
            'instructions'      => $instructions !== '' ? $instructions : null,
            'min_amount'        => $minAmount,
            'max_amount'        => $maxAmount,
            'allows_deposit'    => $allowsDeposit,
            'allows_withdrawal' => $allowsWithdraw,
            'is_crypto'         => $isCrypto,
        ];

        if ($id > 0) {
            $setParts = [];
            $params = [':id' => $id];
            foreach ($fields as $col => $val) {
                $setParts[] = "`$col` = :$col";
                $params[":$col"] = $val;
            }
            $stmt = $pdo->prepare("UPDATE payment_methods SET " . implode(', ', $setParts) . " WHERE id = :id");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'Payment method updated']);
            exit;
        }

        $cols   = array_map(fn($c) => "`$c`", array_keys($fields));
        $vals   = array_map(fn($c) => ":$c", array_keys($fields));
        $params = [];
        foreach ($fields as $col => $val) {
            $params[":$col"] = $val;
        }
        $stmt = $pdo->prepare("INSERT INTO payment_methods (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")");
        $stmt->execute($params);
        echo json_encode(['success' => true, 'message' => 'Payment method created']);
        exit;
    }

    // ---------------------------------------------------------------- toggle
    if ($action === 'toggle') {
        $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $status = strtolower(trim((string)($_POST['status'] ?? 'inactive')));
        if ($id <= 0) {
            throw new Exception('Invalid payment method ID');
        }
        $isActive = $status === 'active' ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE payment_methods SET is_active = ? WHERE id = ?");
        $stmt->execute([$isActive, $id]);
        $label = $isActive ? 'activated' : 'deactivated';
        echo json_encode(['success' => true, 'message' => "Payment method $label"]);
        exit;
    }

    // ---------------------------------------------------------------- delete
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

    // ------------------------------------------------------------------ list
    $start  = isset($_POST['start'])  ? max(0, (int)$_POST['start'])  : 0;
    $length = isset($_POST['length']) ? max(1, (int)$_POST['length']) : 10;
    $search = trim((string)($_POST['search']['value'] ?? ''));

    $whereSql   = '';
    $whereParams = [];
    if ($search !== '') {
        $conditions = [
            "pm.method_name LIKE :search",
            "pm.method_code LIKE :search",
            "pm.bank_name LIKE :search",
            "pm.account_number LIKE :search",
            "pm.wallet_address LIKE :search",
        ];
        $whereSql = ' WHERE ' . implode(' OR ', $conditions);
        $whereParams[':search'] = '%' . $search . '%';
    }

    $recordsTotal    = (int)$pdo->query("SELECT COUNT(*) FROM payment_methods")->fetchColumn();
    $countFilteredStmt = $pdo->prepare("SELECT COUNT(*) FROM payment_methods pm" . $whereSql);
    $countFilteredStmt->execute($whereParams);
    $recordsFiltered = (int)$countFilteredStmt->fetchColumn();

    $orderColIdx  = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDir     = strtolower((string)($_POST['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
    $columnMap    = [0 => 'pm.id', 1 => 'pm.method_code', 2 => 'pm.method_name', 5 => 'pm.is_active'];
    $orderBy      = $columnMap[$orderColIdx] ?? 'pm.id';

    $dataSql = "
        SELECT
            pm.id,
            pm.method_code,
            pm.method_name,
            pm.is_active,
            CASE WHEN pm.is_active = 1 THEN 'active' ELSE 'inactive' END AS status,
            pm.allows_deposit,
            pm.allows_withdrawal,
            pm.is_crypto
        FROM payment_methods pm
        $whereSql
        ORDER BY $orderBy $orderDir
        LIMIT :start, :length
    ";
    $dataStmt = $pdo->prepare($dataSql);
    foreach ($whereParams as $key => $value) {
        $dataStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $dataStmt->bindValue(':start',  $start,  PDO::PARAM_INT);
    $dataStmt->bindValue(':length', $length, PDO::PARAM_INT);
    $dataStmt->execute();
    $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data'            => $data,
    ]);
} catch (PDOException $e) {
    if ($action === 'list') {
        echo json_encode(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Failed to load payment methods']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($action === 'list') {
        echo json_encode(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage()]);
        exit;
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>