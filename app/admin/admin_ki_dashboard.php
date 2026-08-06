<?php
/**
 * Admin – KI Dashboard Entry Manager
 * Allows admins to create/edit/delete per-user AI scan entries,
 * platform checks, and reported platform entries that are shown
 * on the user-facing KI Dashboard (index2.php).
 */
include 'admin_session.php';
include 'admin_header.php';
require_once __DIR__ . '/../database/balance_helpers.php';

// ── Ensure table exists ────────────────────────────────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ki_scan_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            entry_type ENUM('ai_search','platform_check','reported_platform') NOT NULL DEFAULT 'ai_search',
            title VARCHAR(255) NOT NULL,
            description TEXT,
            platform_name VARCHAR(255) DEFAULT NULL,
            platform_url VARCHAR(500) DEFAULT NULL,
            kyc_status ENUM('verified','pending','not_required','failed') NOT NULL DEFAULT 'not_required',
            status ENUM('scanning','found','not_found','verified','flagged','resolved') NOT NULL DEFAULT 'scanning',
            fee_find_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            fee_recover_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            transaction_hash VARCHAR(500) DEFAULT NULL,
            blockchain_network VARCHAR(100) DEFAULT NULL,
            risk_level ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
            is_visible TINYINT(1) NOT NULL DEFAULT 1,
            admin_notes TEXT DEFAULT NULL,
            created_by_admin INT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_entry_type (entry_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Throwable $e) {
    error_log('ki_scan_entries table create: ' . $e->getMessage());
}

$flash = null;

// ── POST handler ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $adminId = (int)($_SESSION['admin_id'] ?? 0);

    try {
        if ($action === 'create' || $action === 'edit') {
            $userId         = (int)($_POST['user_id'] ?? 0);
            $entryType      = in_array($_POST['entry_type'] ?? '', ['ai_search','platform_check','reported_platform']) ? $_POST['entry_type'] : 'ai_search';
            $title          = trim((string)($_POST['title'] ?? ''));
            $description    = trim((string)($_POST['description'] ?? ''));
            $platformName   = trim((string)($_POST['platform_name'] ?? ''));
            $platformUrl    = trim((string)($_POST['platform_url'] ?? ''));
            $kycStatus      = in_array($_POST['kyc_status'] ?? '', ['verified','pending','not_required','failed']) ? $_POST['kyc_status'] : 'not_required';
            $status         = in_array($_POST['status'] ?? '', ['scanning','found','not_found','verified','flagged','resolved']) ? $_POST['status'] : 'scanning';
            $feeFindAmount  = max(0.0, (float)($_POST['fee_find_amount'] ?? 0));
            $feeRecoverAmount = max(0.0, (float)($_POST['fee_recover_amount'] ?? 0));
            $txHash         = trim((string)($_POST['transaction_hash'] ?? ''));
            $network        = trim((string)($_POST['blockchain_network'] ?? ''));
            $riskLevel      = in_array($_POST['risk_level'] ?? '', ['low','medium','high','critical']) ? $_POST['risk_level'] : 'medium';
            $isVisible      = isset($_POST['is_visible']) ? 1 : 0;
            $adminNotes     = trim((string)($_POST['admin_notes'] ?? ''));

            if ($userId <= 0 || $title === '') {
                throw new Exception('User and title are required.');
            }

            $newTotalFee = round($feeFindAmount + $feeRecoverAmount, 2);
            $chargeContext = [];
            $creditContext = [];
            $pdo->beginTransaction();

            if ($action === 'create') {
                if ($newTotalFee > 0) {
                    $chargeContext = adjustUserBalance($pdo, $userId, -$newTotalFee);
                }

                $stmt = $pdo->prepare("
                    INSERT INTO ki_scan_entries
                        (user_id, entry_type, title, description, platform_name, platform_url,
                         kyc_status, status, fee_find_amount, fee_recover_amount,
                         transaction_hash, blockchain_network, risk_level, is_visible,
                         admin_notes, created_by_admin)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                ");
                $stmt->execute([
                    $userId, $entryType, $title, $description, $platformName ?: null, $platformUrl ?: null,
                    $kycStatus, $status, $feeFindAmount, $feeRecoverAmount,
                    $txHash ?: null, $network ?: null, $riskLevel, $isVisible,
                    $adminNotes ?: null, $adminId ?: null
                ]);
                $pdo->commit();

                if (!empty($chargeContext)) {
                    notifyKiFeeCharge($pdo, $userId, $newTotalFee, (float)$chargeContext['new_balance'], $title);
                    notifyBalanceDepleted($pdo, $userId, (float)$chargeContext['new_balance']);
                    if ((float)$chargeContext['new_balance'] <= 0) {
                        addBalanceAdminNotification(
                            $pdo,
                            $adminId,
                            'Nutzerguthaben aufgebraucht',
                            'Durch den KI-Vorgang <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong> ist das Guthaben des Nutzers auf 0,00 € gefallen.',
                            'warning'
                        );
                    }
                }

                $flash = ['type' => 'success', 'text' => 'Entry created successfully.'];
            } else {
                $entryId = (int)($_POST['entry_id'] ?? 0);
                if ($entryId <= 0) throw new Exception('Invalid entry ID.');

                $existingStmt = $pdo->prepare("SELECT * FROM ki_scan_entries WHERE id = ? LIMIT 1 FOR UPDATE");
                $existingStmt->execute([$entryId]);
                $existingEntry = $existingStmt->fetch(PDO::FETCH_ASSOC);

                if (!$existingEntry) {
                    throw new Exception('Entry not found.');
                }

                $oldUserId = (int)$existingEntry['user_id'];
                $oldTotalFee = round((float)$existingEntry['fee_find_amount'] + (float)$existingEntry['fee_recover_amount'], 2);

                if ($oldUserId === $userId) {
                    $balanceDelta = round($oldTotalFee - $newTotalFee, 2);
                    if ($balanceDelta !== 0.0) {
                        $changeContext = adjustUserBalance($pdo, $userId, $balanceDelta);
                        if ($balanceDelta > 0) {
                            $creditContext = ['user_id' => $userId, 'amount' => $balanceDelta, 'new_balance' => (float)$changeContext['new_balance']];
                        } else {
                            $chargeContext = ['user_id' => $userId, 'amount' => abs($balanceDelta), 'new_balance' => (float)$changeContext['new_balance']];
                        }
                    }
                } else {
                    if ($oldTotalFee > 0) {
                        $refundContext = adjustUserBalance($pdo, $oldUserId, $oldTotalFee);
                        $creditContext = ['user_id' => $oldUserId, 'amount' => $oldTotalFee, 'new_balance' => (float)$refundContext['new_balance']];
                    }
                    if ($newTotalFee > 0) {
                        $newChargeContext = adjustUserBalance($pdo, $userId, -$newTotalFee);
                        $chargeContext = ['user_id' => $userId, 'amount' => $newTotalFee, 'new_balance' => (float)$newChargeContext['new_balance']];
                    }
                }

                $stmt = $pdo->prepare("
                    UPDATE ki_scan_entries SET
                        user_id=?, entry_type=?, title=?, description=?, platform_name=?, platform_url=?,
                        kyc_status=?, status=?, fee_find_amount=?, fee_recover_amount=?,
                        transaction_hash=?, blockchain_network=?, risk_level=?, is_visible=?,
                        admin_notes=?
                    WHERE id=?
                ");
                $stmt->execute([
                    $userId, $entryType, $title, $description, $platformName ?: null, $platformUrl ?: null,
                    $kycStatus, $status, $feeFindAmount, $feeRecoverAmount,
                    $txHash ?: null, $network ?: null, $riskLevel, $isVisible,
                    $adminNotes ?: null, $entryId
                ]);
                $pdo->commit();

                if (!empty($creditContext)) {
                    notifyBalanceCredit(
                        $pdo,
                        (int)$creditContext['user_id'],
                        (float)$creditContext['amount'],
                        (float)$creditContext['new_balance'],
                        'Korrektur einer KI-Gebühr'
                    );
                }
                if (!empty($chargeContext)) {
                    notifyKiFeeCharge(
                        $pdo,
                        (int)$chargeContext['user_id'],
                        (float)$chargeContext['amount'],
                        (float)$chargeContext['new_balance'],
                        $title
                    );
                    notifyBalanceDepleted($pdo, (int)$chargeContext['user_id'], (float)$chargeContext['new_balance']);
                }

                $flash = ['type' => 'success', 'text' => 'Entry updated successfully.'];
            }
        } elseif ($action === 'delete') {
            $entryId = (int)($_POST['entry_id'] ?? 0);
            if ($entryId <= 0) throw new Exception('Invalid entry ID.');
            $pdo->beginTransaction();

            $existingStmt = $pdo->prepare("SELECT id, user_id, title, fee_find_amount, fee_recover_amount FROM ki_scan_entries WHERE id = ? LIMIT 1 FOR UPDATE");
            $existingStmt->execute([$entryId]);
            $existingEntry = $existingStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingEntry) {
                throw new Exception('Entry not found.');
            }

            $refundAmount = round((float)$existingEntry['fee_find_amount'] + (float)$existingEntry['fee_recover_amount'], 2);
            $refundContext = null;
            if ($refundAmount > 0) {
                $refundContext = adjustUserBalance($pdo, (int)$existingEntry['user_id'], $refundAmount);
            }

            $pdo->prepare("DELETE FROM ki_scan_entries WHERE id = ?")->execute([$entryId]);
            $pdo->commit();

            if ($refundContext) {
                notifyBalanceCredit(
                    $pdo,
                    (int)$existingEntry['user_id'],
                    $refundAmount,
                    (float)$refundContext['new_balance'],
                    'Löschung des KI-Vorgangs ' . $existingEntry['title']
                );
            }
            $flash = ['type' => 'success', 'text' => 'Entry deleted.'];
        } elseif ($action === 'toggle_visibility') {
            $entryId = (int)($_POST['entry_id'] ?? 0);
            if ($entryId <= 0) throw new Exception('Invalid entry ID.');
            $pdo->prepare("UPDATE ki_scan_entries SET is_visible = 1 - is_visible WHERE id = ?")->execute([$entryId]);
            $flash = ['type' => 'success', 'text' => 'Visibility toggled.'];
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $flash = ['type' => 'danger', 'text' => htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')];
    }
}

// ── Filters ────────────────────────────────────────────────────────────────
$filterUserId   = (int)($_GET['user_id'] ?? 0);
$filterType     = in_array($_GET['type'] ?? '', ['ai_search','platform_check','reported_platform']) ? $_GET['type'] : '';
$filterPage     = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 25;
$offset         = ($filterPage - 1) * $perPage;

$where = ['1=1'];
$params = [];
if ($filterUserId > 0) { $where[] = 'k.user_id = ?'; $params[] = $filterUserId; }
if ($filterType !== '') { $where[] = 'k.entry_type = ?'; $params[] = $filterType; }
$whereSql = implode(' AND ', $where);

$totalRows = 0;
$entries = [];
try {
    $topupBalanceSql = getUserTopupBalanceSql($pdo, 'u');
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM ki_scan_entries k WHERE $whereSql");
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();

    $listStmt = $pdo->prepare("
        SELECT k.*, u.first_name, u.email, {$topupBalanceSql} AS user_balance
        FROM ki_scan_entries k
        LEFT JOIN users u ON u.id = k.user_id
        WHERE $whereSql
        ORDER BY k.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $listStmt->execute($params);
    $entries = $listStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('admin_ki_dashboard list: ' . $e->getMessage());
}

$totalPages = $totalRows > 0 ? (int)ceil($totalRows / $perPage) : 1;

// Fetch users for dropdown (limit to 200 most recent)
$userList = [];
try {
    $userList = $pdo->query("SELECT id, first_name, email FROM users ORDER BY first_name ASC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { /* ignore */ }

// Fetch entry counts by type
$typeCounts = ['ai_search' => 0, 'platform_check' => 0, 'reported_platform' => 0];
try {
    $tcStmt = $pdo->query("SELECT entry_type, COUNT(*) as cnt FROM ki_scan_entries GROUP BY entry_type");
    foreach ($tcStmt->fetchAll(PDO::FETCH_ASSOC) as $tc) {
        if (isset($typeCounts[$tc['entry_type']])) $typeCounts[$tc['entry_type']] = (int)$tc['cnt'];
    }
} catch (Throwable $e) { /* ignore */ }

$statusColors = [
    'scanning'    => 'warning',
    'found'       => 'primary',
    'not_found'   => 'secondary',
    'verified'    => 'success',
    'flagged'     => 'danger',
    'resolved'    => 'info',
];
$riskColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];
$typeLabels = ['ai_search' => 'AI Search', 'platform_check' => 'Platform Check', 'reported_platform' => 'Reported Platform'];
$typeIcons  = ['ai_search' => 'anticon-search', 'platform_check' => 'anticon-security-scan', 'reported_platform' => 'anticon-warning'];
?>

<div class="main-content">
    <div class="page-header">
        <h2><i class="anticon anticon-robot mr-2"></i>KI Dashboard – Entry Manager</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">KI Dashboard Entries</span>
            </nav>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= $flash['text'] ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Stats row -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small font-weight-600 text-uppercase">Total Entries</div>
                    <div style="font-size:28px;font-weight:700;"><?= $totalRows ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small font-weight-600 text-uppercase"><i class="anticon anticon-search mr-1 text-primary"></i>AI Searches</div>
                    <div style="font-size:28px;font-weight:700;"><?= $typeCounts['ai_search'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small font-weight-600 text-uppercase"><i class="anticon anticon-security-scan mr-1 text-info"></i>Platform Checks</div>
                    <div style="font-size:28px;font-weight:700;"><?= $typeCounts['platform_check'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small font-weight-600 text-uppercase"><i class="anticon anticon-warning mr-1 text-danger"></i>Reported Platforms</div>
                    <div style="font-size:28px;font-weight:700;"><?= $typeCounts['reported_platform'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter + Add row -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
                <form method="get" class="d-flex flex-wrap align-items-center" style="gap:8px;">
                    <select name="user_id" class="form-control form-control-sm" style="min-width:200px;">
                        <option value="">All Users</option>
                        <?php foreach ($userList as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= $filterUserId === (int)$u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['first_name'] . ' (' . $u['email'] . ')', ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type" class="form-control form-control-sm">
                        <option value="">All Types</option>
                        <option value="ai_search" <?= $filterType === 'ai_search' ? 'selected' : '' ?>>AI Search</option>
                        <option value="platform_check" <?= $filterType === 'platform_check' ? 'selected' : '' ?>>Platform Check</option>
                        <option value="reported_platform" <?= $filterType === 'reported_platform' ? 'selected' : '' ?>>Reported Platform</option>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                    <a href="admin_ki_dashboard.php" class="btn btn-light btn-sm">Reset</a>
                </form>
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addEntryModal">
                    <i class="anticon anticon-plus mr-1"></i> Add Entry
                </button>
            </div>
        </div>
    </div>

    <!-- Entries Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Balance</th>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Risk</th>
                            <th>Find Fee</th>
                            <th>Recover Fee</th>
                            <th>KYC</th>
                            <th>Visible</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entries)): ?>
                            <tr><td colspan="13" class="text-center text-muted py-4">No entries found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td><?= (int)$entry['id'] ?></td>
                                    <td>
                                        <div class="font-weight-600"><?= htmlspecialchars((string)($entry['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars((string)($entry['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td>€ <?= number_format((float)($entry['user_balance'] ?? 0), 2, ',', '.') ?></td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($typeLabels[$entry['entry_type']] ?? $entry['entry_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td>
                                        <div class="font-weight-600"><?= htmlspecialchars((string)$entry['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if (!empty($entry['platform_name'])): ?>
                                            <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars((string)$entry['platform_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-<?= $statusColors[$entry['status']] ?? 'secondary' ?>"><?= htmlspecialchars((string)$entry['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><span class="badge badge-<?= $riskColors[$entry['risk_level']] ?? 'secondary' ?>"><?= htmlspecialchars((string)$entry['risk_level'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td>€ <?= number_format((float)$entry['fee_find_amount'], 2, ',', '.') ?></td>
                                    <td>€ <?= number_format((float)$entry['fee_recover_amount'], 2, ',', '.') ?></td>
                                    <td><span class="badge badge-<?= $entry['kyc_status'] === 'verified' ? 'success' : ($entry['kyc_status'] === 'pending' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars((string)$entry['kyc_status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_visibility">
                                            <input type="hidden" name="entry_id" value="<?= (int)$entry['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-<?= $entry['is_visible'] ? 'success' : 'secondary' ?>" title="Toggle visibility">
                                                <i class="anticon anticon-<?= $entry['is_visible'] ? 'eye' : 'eye-invisible' ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="font-size:12px;"><?= htmlspecialchars(date('d.m.Y H:i', strtotime((string)$entry['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info edit-btn"
                                            data-id="<?= (int)$entry['id'] ?>"
                                            data-user_id="<?= (int)$entry['user_id'] ?>"
                                            data-entry_type="<?= htmlspecialchars((string)$entry['entry_type'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-title="<?= htmlspecialchars((string)$entry['title'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-description="<?= htmlspecialchars((string)$entry['description'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-platform_name="<?= htmlspecialchars((string)$entry['platform_name'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-platform_url="<?= htmlspecialchars((string)$entry['platform_url'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-kyc_status="<?= htmlspecialchars((string)$entry['kyc_status'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-status="<?= htmlspecialchars((string)$entry['status'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-fee_find_amount="<?= number_format((float)$entry['fee_find_amount'], 2, '.', '') ?>"
                                            data-fee_recover_amount="<?= number_format((float)$entry['fee_recover_amount'], 2, '.', '') ?>"
                                            data-transaction_hash="<?= htmlspecialchars((string)$entry['transaction_hash'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-blockchain_network="<?= htmlspecialchars((string)$entry['blockchain_network'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-risk_level="<?= htmlspecialchars((string)$entry['risk_level'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-is_visible="<?= (int)$entry['is_visible'] ?>"
                                            data-admin_notes="<?= htmlspecialchars((string)$entry['admin_notes'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-toggle="modal" data-target="#editEntryModal">
                                            <i class="anticon anticon-edit"></i>
                                        </button>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this entry?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="entry_id" value="<?= (int)$entry['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="anticon anticon-delete"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= $p === $filterPage ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $p ?>&user_id=<?= $filterUserId ?>&type=<?= urlencode($filterType) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Entry Modal -->
<div class="modal fade" id="addEntryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="anticon anticon-plus mr-2"></i>Add KI Dashboard Entry</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <?= entryFormFields($userList) ?>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="anticon anticon-check mr-1"></i>Create Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Entry Modal -->
<div class="modal fade" id="editEntryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="anticon anticon-edit mr-2"></i>Edit KI Dashboard Entry</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="entry_id" id="edit_entry_id">
                <?= entryFormFields($userList, 'edit_') ?>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info"><i class="anticon anticon-save mr-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var d = btn.dataset;
        document.getElementById('edit_entry_id').value = d.id;
        setVal('edit_user_id', d.user_id);
        setVal('edit_entry_type', d.entry_type);
        setVal('edit_title', d.title);
        setVal('edit_description', d.description);
        setVal('edit_platform_name', d.platform_name);
        setVal('edit_platform_url', d.platform_url);
        setVal('edit_kyc_status', d.kyc_status);
        setVal('edit_status', d.status);
        setVal('edit_fee_find_amount', d.fee_find_amount);
        setVal('edit_fee_recover_amount', d.fee_recover_amount);
        setVal('edit_transaction_hash', d.transaction_hash);
        setVal('edit_blockchain_network', d.blockchain_network);
        setVal('edit_risk_level', d.risk_level);
        setVal('edit_admin_notes', d.admin_notes);
        var cb = document.getElementById('edit_is_visible');
        if (cb) cb.checked = (d.is_visible === '1');
    });
});
function setVal(id, val) {
    var el = document.getElementById(id);
    if (el) el.value = val;
}
</script>

<?php include 'admin_footer.php'; ?>

<?php
// ── Helper: renders form fields for add/edit modal ─────────────────────────
function entryFormFields(array $userList, string $prefix = ''): string {
    $esc = function(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
    $id  = function(string $name) use ($prefix): string { return $prefix . $name; };

    $userOptions = '<option value="">Select user…</option>';
    foreach ($userList as $u) {
        $userOptions .= '<option value="' . (int)$u['id'] . '">' . $esc($u['first_name'] . ' (' . $u['email'] . ')') . '</option>';
    }

    $entryTypes = ['ai_search' => 'AI Search', 'platform_check' => 'Platform Check', 'reported_platform' => 'Reported Platform'];
    $entryTypeOptions = '';
    foreach ($entryTypes as $val => $lbl) {
        $entryTypeOptions .= '<option value="' . $val . '">' . $lbl . '</option>';
    }

    $statuses = ['scanning','found','not_found','verified','flagged','resolved'];
    $statusOptions = '';
    foreach ($statuses as $s) {
        $statusOptions .= '<option value="' . $s . '">' . ucfirst($s) . '</option>';
    }

    $kycStatuses = ['not_required' => 'Not Required', 'verified' => 'Verified', 'pending' => 'Pending', 'failed' => 'Failed'];
    $kycOptions = '';
    foreach ($kycStatuses as $v => $l) {
        $kycOptions .= '<option value="' . $v . '">' . $l . '</option>';
    }

    $riskLevels = ['low','medium','high','critical'];
    $riskOptions = '';
    foreach ($riskLevels as $r) {
        $riskOptions .= '<option value="' . $r . '">' . ucfirst($r) . '</option>';
    }

    return '
    <div class="modal-body">
        <div class="row">
            <div class="col-md-6 form-group">
                <label>User <span class="text-danger">*</span></label>
                <select name="user_id" id="' . $id('user_id') . '" class="form-control" required>' . $userOptions . '</select>
            </div>
            <div class="col-md-6 form-group">
                <label>Entry Type <span class="text-danger">*</span></label>
                <select name="entry_type" id="' . $id('entry_type') . '" class="form-control" required>' . $entryTypeOptions . '</select>
            </div>
            <div class="col-md-12 form-group">
                <label>Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="' . $id('title') . '" class="form-control" placeholder="e.g. Blockchain scan – Bitcoin address" required>
            </div>
            <div class="col-md-12 form-group">
                <label>Description</label>
                <textarea name="description" id="' . $id('description') . '" class="form-control" rows="2" placeholder="Optional description shown to user"></textarea>
            </div>
            <div class="col-md-6 form-group">
                <label>Platform Name</label>
                <input type="text" name="platform_name" id="' . $id('platform_name') . '" class="form-control" placeholder="e.g. CryptoScamX.com">
            </div>
            <div class="col-md-6 form-group">
                <label>Platform URL</label>
                <input type="text" name="platform_url" id="' . $id('platform_url') . '" class="form-control" placeholder="https://...">
            </div>
            <div class="col-md-4 form-group">
                <label>Status</label>
                <select name="status" id="' . $id('status') . '" class="form-control">' . $statusOptions . '</select>
            </div>
            <div class="col-md-4 form-group">
                <label>KYC Status</label>
                <select name="kyc_status" id="' . $id('kyc_status') . '" class="form-control">' . $kycOptions . '</select>
            </div>
            <div class="col-md-4 form-group">
                <label>Risk Level</label>
                <select name="risk_level" id="' . $id('risk_level') . '" class="form-control">' . $riskOptions . '</select>
            </div>
            <div class="col-md-6 form-group">
                <label>Find Fee (€)</label>
                <input type="number" name="fee_find_amount" id="' . $id('fee_find_amount') . '" class="form-control" min="0" step="0.01" value="0.00">
            </div>
            <div class="col-md-6 form-group">
                <label>Recovery Fee (€)</label>
                <input type="number" name="fee_recover_amount" id="' . $id('fee_recover_amount') . '" class="form-control" min="0" step="0.01" value="0.00">
            </div>
            <div class="col-md-12">
                <small class="text-muted d-block mb-3">Gebühren werden direkt vom verfügbaren Nutzerguthaben abgezogen. Reicht das Guthaben nicht aus, kann der Eintrag nicht gespeichert werden.</small>
            </div>
            <div class="col-md-6 form-group">
                <label>Transaction Hash</label>
                <input type="text" name="transaction_hash" id="' . $id('transaction_hash') . '" class="form-control" placeholder="0x...">
            </div>
            <div class="col-md-6 form-group">
                <label>Blockchain Network</label>
                <input type="text" name="blockchain_network" id="' . $id('blockchain_network') . '" class="form-control" placeholder="e.g. Ethereum, Bitcoin, Tron">
            </div>
            <div class="col-md-12 form-group">
                <label>Admin Notes</label>
                <textarea name="admin_notes" id="' . $id('admin_notes') . '" class="form-control" rows="2" placeholder="Internal notes (not visible to user)"></textarea>
            </div>
            <div class="col-md-12 form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" name="is_visible" id="' . $id('is_visible') . '" value="1" checked>
                    <label class="custom-control-label" for="' . $id('is_visible') . '">Visible to user</label>
                </div>
            </div>
        </div>
    </div>';
}
?>
