<?php
include 'admin_session.php';
require_once __DIR__ . '/AdminEmailHelper.php';
include 'admin_header.php';

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $testId = isset($_POST['test_id']) ? (int)$_POST['test_id'] : 0;
    $adminNotes = trim((string)($_POST['admin_notes'] ?? ''));

    if ($testId > 0 && in_array($action, ['approve', 'reject'], true)) {
        try {
            $stmt = $pdo->prepare(
                "SELECT st.id, st.user_id, st.status, st.amount, st.currency, st.crypto_coin, st.tx_reference,
                        u.first_name, u.email
                 FROM satoshi_tests st
                 JOIN users u ON u.id = st.user_id
                 WHERE st.id = ?
                 LIMIT 1"
            );
            $stmt->execute([$testId]);
            $test = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$test) {
                throw new Exception('Satoshi-Test nicht gefunden.');
            }

            if (!in_array($test['status'], ['pending', 'under_review'], true)) {
                throw new Exception('Nur Anfragen mit Status "pending/under_review" können bearbeitet werden.');
            }

            $newStatus = ($action === 'approve') ? 'verified' : 'rejected';
            $update = $pdo->prepare(
                "UPDATE satoshi_tests
                 SET status = ?, admin_notes = ?, verified_at = CASE WHEN ? = 'verified' THEN NOW() ELSE verified_at END
                 WHERE id = ?"
            );
            $update->execute([$newStatus, $adminNotes !== '' ? $adminNotes : null, $newStatus, $testId]);

            try {
                $emailHelper = new AdminEmailHelper($pdo);
                if ($newStatus === 'verified') {
                    $emailHelper->sendDirectEmail(
                        (int)$test['user_id'],
                        'Ihr Satoshi-Test wurde bestätigt',
                        '<p>Guten Tag {first_name},</p>
                         <p>Ihr Satoshi-Test wurde erfolgreich bestätigt.</p>
                         <p>Sie können Ihr Dashboard nun ohne Verifizierungsblockaden nutzen.</p>
                         <p>Viele Grüße<br>{brand_name}</p>'
                    );
                } else {
                    $emailHelper->sendDirectEmail(
                        (int)$test['user_id'],
                        'Ihr Satoshi-Test wurde abgelehnt',
                        '<p>Guten Tag {first_name},</p>
                         <p>Ihr eingereichter Satoshi-Test konnte nicht bestätigt werden.</p>
                         <p><strong>Hinweis des Teams:</strong> ' . htmlspecialchars($adminNotes !== '' ? $adminNotes : 'Bitte prüfen Sie die Transaktionsdaten und reichen Sie den Test erneut ein.', ENT_QUOTES, 'UTF-8') . '</p>
                         <p>Viele Grüße<br>{brand_name}</p>'
                    );
                }
            } catch (Throwable $mailEx) {
                error_log('Satoshi-Test E-Mail fehlgeschlagen: ' . $mailEx->getMessage());
            }

            $flash = ['type' => 'success', 'text' => $newStatus === 'verified'
                ? 'Satoshi-Test wurde erfolgreich bestätigt.'
                : 'Satoshi-Test wurde abgelehnt.'];
        } catch (Throwable $e) {
            $flash = ['type' => 'danger', 'text' => $e->getMessage()];
        }
    }
}

$counts = [
    'pending' => 0,
    'under_review' => 0,
    'verified' => 0,
    'rejected' => 0,
];
try {
    $countStmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM satoshi_tests GROUP BY status");
    while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
        $counts[$row['status']] = (int)$row['cnt'];
    }
} catch (Throwable $e) {
    // Tabelle evtl. nicht vorhanden
}

$tests = [];
try {
    $listStmt = $pdo->query(
        "SELECT st.id, st.user_id, st.amount, st.currency, st.crypto_coin, st.tx_reference, st.status, st.admin_notes, st.verified_at, st.created_at,
                u.first_name, u.last_name, u.email, u.balance
         FROM satoshi_tests st
         JOIN users u ON u.id = st.user_id
         ORDER BY st.created_at DESC
         LIMIT 300"
    );
    $tests = $listStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Tabelle evtl. nicht vorhanden
}
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Satoshi-Test Verwaltung</h2>
        <p class="header-sub-title">Anfragen prüfen, freigeben oder ablehnen</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($flash['text'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Ausstehend:</strong> <?= (int)$counts['pending'] ?></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>In Prüfung:</strong> <?= (int)$counts['under_review'] ?></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Bestätigt:</strong> <?= (int)$counts['verified'] ?></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Abgelehnt:</strong> <?= (int)$counts['rejected'] ?></div></div></div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover table-bordered">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nutzer</th>
                    <th>Kontostand</th>
                    <th>Coin</th>
                    <th>Betrag</th>
                    <th>Transaktions-Ref</th>
                    <th>Status</th>
                    <th>Eingang</th>
                    <th>Aktionen</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($tests as $row): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td>
                            <?= htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?><br>
                            <small class="text-muted"><?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>€<?= number_format((float)($row['balance'] ?? 0), 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($row['crypto_coin'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>€<?= number_format((float)$row['amount'], 2, ',', '.') ?></td>
                        <td style="max-width:260px;word-break:break-all;"><code><?= htmlspecialchars((string)$row['tx_reference'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="min-width:250px;">
                            <?php if (in_array($row['status'], ['pending', 'under_review'], true)): ?>
                                <form method="post" class="d-inline-block mr-1" onsubmit="return withAdminNote(this, 'Freigabe-Hinweis (optional):');">
                                    <input type="hidden" name="test_id" value="<?= (int)$row['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="admin_notes" value="">
                                    <button type="submit" class="btn btn-sm btn-success">Freigeben</button>
                                </form>
                                <form method="post" class="d-inline-block" onsubmit="return withAdminNote(this, 'Ablehnungsgrund (erforderlich):', true);">
                                    <input type="hidden" name="test_id" value="<?= (int)$row['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="admin_notes" value="">
                                    <button type="submit" class="btn btn-sm btn-danger">Ablehnen</button>
                                </form>
                            <?php else: ?>
                                <small class="text-muted">Abgeschlossen</small>
                            <?php endif; ?>
                            <?php if (!empty($row['admin_notes'])): ?>
                                <div><small class="text-muted"><?= htmlspecialchars($row['admin_notes'], ENT_QUOTES, 'UTF-8') ?></small></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function withAdminNote(form, text, required = false) {
    const note = window.prompt(text, '') || '';
    if (required && !note.trim()) {
        alert('Bitte einen Grund eingeben.');
        return false;
    }
    form.querySelector('input[name="admin_notes"]').value = note.trim();
    return true;
}
</script>

<?php include 'admin_footer.php'; ?>
