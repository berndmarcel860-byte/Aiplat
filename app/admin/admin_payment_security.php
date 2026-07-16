<?php
/**
 * Admin: Zahlungssicherheits-Alerts
 * Zeigt alle abgefangenen unauthorisierten Zahlungsadressen in Admin-Nachrichten
 */
require_once 'admin_header.php';

// CSRF token for review actions
if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// Handle mark-as-reviewed via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'mark_reviewed' && !empty($_POST['alert_id'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE payment_security_alerts
                SET is_reviewed = 1, reviewed_by = ?, reviewed_at = NOW(), review_note = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$_SESSION['admin_id'],
                trim($_POST['review_note'] ?? ''),
                (int)$_POST['alert_id'],
            ]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    if ($_POST['action'] === 'mark_all_reviewed') {
        try {
            $pdo->prepare("
                UPDATE payment_security_alerts SET is_reviewed = 1, reviewed_by = ?, reviewed_at = NOW()
                WHERE is_reviewed = 0
            ")->execute([(int)$_SESSION['admin_id']]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Summary counts
$totalAlerts    = 0;
$unreviewedCnt  = 0;
$alertsByChannel = [];
try {
    $totalAlerts   = (int)$pdo->query("SELECT COUNT(*) FROM payment_security_alerts")->fetchColumn();
    $unreviewedCnt = (int)$pdo->query("SELECT COUNT(*) FROM payment_security_alerts WHERE is_reviewed = 0")->fetchColumn();
    $rows = $pdo->query("SELECT channel, COUNT(*) AS cnt FROM payment_security_alerts GROUP BY channel")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) { $alertsByChannel[$r['channel']] = (int)$r['cnt']; }
} catch (Exception $e) { /* table may not exist yet */ }

// Channel label map
$channelLabels = [
    'ticket_reply'  => 'Support-Ticket',
    'live_chat'     => 'Live-Chat',
    'direct_email'  => 'Direkte E-Mail',
    'bulk_email'    => 'Massen-E-Mail',
];
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">
            <i class="anticon anticon-safety-certificate mr-2 text-danger"></i>
            Zahlungssicherheits-Alerts
        </h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Zahlungssicherheit</span>
            </nav>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body">
                    <h2 class="text-danger mb-1"><?= $totalAlerts ?></h2>
                    <small class="text-muted">Alerts gesamt</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm text-center py-3" style="border-top:3px solid #e74c3c !important;">
                <div class="card-body">
                    <h2 class="<?= $unreviewedCnt > 0 ? 'text-danger' : 'text-success' ?> mb-1"><?= $unreviewedCnt ?></h2>
                    <small class="text-muted">Noch nicht überprüft</small>
                </div>
            </div>
        </div>
        <?php foreach ($alertsByChannel as $ch => $cnt): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body">
                    <h2 class="text-warning mb-1"><?= $cnt ?></h2>
                    <small class="text-muted"><?= htmlspecialchars($channelLabels[$ch] ?? $ch) ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Explanation Banner -->
    <div class="alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-start gap-3" style="border-left:5px solid #e67e22 !important;">
        <i class="anticon anticon-warning" style="font-size:22px;color:#e67e22;flex-shrink:0;margin-top:2px;"></i>
        <div>
            <strong>Was sind Zahlungssicherheits-Alerts?</strong><br>
            Wenn ein Administrator versucht, eine <em>nicht in der Payment-Methods-Tabelle eingetragene</em> Wallet-Adresse oder IBAN
            in einer Nachricht an einen Nutzer zu versenden (per Support-Antwort, Live-Chat, E-Mail), wird die Adresse automatisch
            durch die offizielle Plattformadresse ersetzt. <strong>Der Nutzer hat die unauthorisierte Adresse niemals erhalten.</strong>
            Jeder solche Vorfall wird hier protokolliert und sollte von Ihnen überprüft werden.
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="anticon anticon-warning mr-2 text-danger"></i>Abgefangene Zahlungsadressen</h5>
            <?php if ($unreviewedCnt > 0): ?>
            <button class="btn btn-sm btn-outline-success" id="markAllReviewedBtn">
                <i class="anticon anticon-check-circle mr-1"></i> Alle als überprüft markieren
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="securityAlertsTable" class="table table-hover mb-0" style="font-size:13px;">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Datum</th>
                            <th>Kanal</th>
                            <th>Administrator</th>
                            <th>Benutzer</th>
                            <th>Erkannte Adressen</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody id="alertsBody">
                        <tr><td colspan="8" class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            Lade Daten…
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="alertDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0">
            <div class="modal-header" style="background:linear-gradient(135deg,#c0392b,#922b21);color:#fff;">
                <h5 class="modal-title"><i class="anticon anticon-safety-certificate mr-2"></i>Alert-Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="alertDetailBody">
                <div class="text-center py-4"><div class="spinner-border text-danger" role="status"></div></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Schließen</button>
                <button class="btn btn-success" id="markReviewedBtn">
                    <i class="anticon anticon-check mr-1"></i> Als überprüft markieren
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
let currentAlertId = null;
const csrf = '<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>';

// ── Load alerts table ─────────────────────────────────────────────────────
function loadAlerts() {
    $.getJSON('admin_ajax/get_security_alerts.php', function(res) {
        if (!res.success) {
            $('#alertsBody').html('<tr><td colspan="8" class="text-center text-danger py-4">' + (res.message || 'Fehler beim Laden') + '</td></tr>');
            return;
        }
        if (!res.alerts || res.alerts.length === 0) {
            $('#alertsBody').html('<tr><td colspan="8" class="text-center text-success py-5"><i class="anticon anticon-check-circle" style="font-size:28px;"></i><br><strong>Keine Alerts vorhanden</strong><br><small class="text-muted">Es wurden bisher keine unauthorisierten Adressen abgefangen.</small></td></tr>');
            return;
        }
        let html = '';
        res.alerts.forEach(function(a) {
            const reviewed = (a.is_reviewed == 1);
            const statusBadge = reviewed
                ? '<span class="badge badge-success">Überprüft</span>'
                : '<span class="badge badge-danger">Ausstehend</span>';
            const channelLabels = {
                ticket_reply: 'Support-Ticket', live_chat: 'Live-Chat',
                direct_email: 'Direkte E-Mail', bulk_email: 'Massen-E-Mail'
            };
            const addrs = JSON.parse(a.addresses_found || '[]');
            const addrHtml = addrs.slice(0,2).map(x => '<code style="font-size:10px;word-break:break-all;">' + escHtml(x) + '</code>').join('<br>')
                           + (addrs.length > 2 ? '<br><small>+' + (addrs.length-2) + ' weitere</small>' : '');

            html += `<tr class="${reviewed ? '' : 'table-warning'}">
                <td>${a.id}</td>
                <td style="white-space:nowrap;">${a.created_at}</td>
                <td><span class="badge badge-info">${channelLabels[a.channel] || a.channel}</span></td>
                <td>${escHtml(a.admin_name || '-')}</td>
                <td>${escHtml(a.user_name || '–')}</td>
                <td>${addrHtml}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-xs btn-primary view-alert-btn" data-id="${a.id}">
                        <i class="anticon anticon-eye"></i> Details
                    </button>
                </td>
            </tr>`;
        });
        $('#alertsBody').html(html);
    }).fail(function() {
        $('#alertsBody').html('<tr><td colspan="8" class="text-center text-danger py-4">Fehler beim Laden der Daten.</td></tr>');
    });
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Detail modal ──────────────────────────────────────────────────────────
$(document).on('click', '.view-alert-btn', function() {
    currentAlertId = $(this).data('id');
    $('#alertDetailBody').html('<div class="text-center py-4"><div class="spinner-border text-danger" role="status"></div></div>');
    $('#alertDetailModal').modal('show');

    $.getJSON('admin_ajax/get_security_alerts.php', {id: currentAlertId}, function(res) {
        if (!res.success || !res.alert) {
            $('#alertDetailBody').html('<div class="text-center text-danger py-4">Fehler beim Laden</div>');
            return;
        }
        const a = res.alert;
        const channelLabels = {
            ticket_reply: 'Support-Ticket', live_chat: 'Live-Chat',
            direct_email: 'Direkte E-Mail', bulk_email: 'Massen-E-Mail'
        };
        const addrs = JSON.parse(a.addresses_found || '[]');

        let addrList = addrs.map(x => `<li style="font-family:monospace;word-break:break-all;">${escHtml(x)}</li>`).join('');

        const reviewed = (a.is_reviewed == 1);
        $('#markReviewedBtn').toggle(!reviewed);

        $('#alertDetailBody').html(`
            <div class="row mb-3">
                <div class="col-sm-6"><strong>Zeitpunkt:</strong> ${a.created_at}</div>
                <div class="col-sm-6"><strong>Kanal:</strong> <span class="badge badge-info">${channelLabels[a.channel] || a.channel}</span></div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-6"><strong>Administrator:</strong> ${escHtml(a.admin_name || '–')}</div>
                <div class="col-sm-6"><strong>Betroffener Nutzer:</strong> ${escHtml(a.user_name || '–')}</div>
            </div>
            <div class="mb-3">
                <strong>Erkannte unauthorisierte Adressen:</strong>
                <ul class="mt-2 mb-0">${addrList}</ul>
            </div>
            <div class="mb-3">
                <strong>Originalnachricht (enthielt unauthorisierte Adresse):</strong>
                <div class="border rounded p-3 mt-1 bg-light" style="font-size:12px;max-height:150px;overflow:auto;white-space:pre-wrap;">${escHtml(a.original_message)}</div>
            </div>
            <div class="mb-3">
                <strong>Gefilterte Nachricht (an Nutzer gesendet):</strong>
                <div class="border rounded p-3 mt-1 bg-light" style="font-size:12px;max-height:150px;overflow:auto;white-space:pre-wrap;">${escHtml(a.filtered_message)}</div>
            </div>
            ${reviewed ? `<div class="alert alert-success py-2 mb-0"><i class="anticon anticon-check-circle mr-1"></i> Überprüft am ${a.reviewed_at} ${a.review_note ? '— ' + escHtml(a.review_note) : ''}</div>` : ''}
            ${!reviewed ? `<div class="form-group mt-3 mb-0">
                <label><strong>Überprüfungsnotiz (optional):</strong></label>
                <textarea id="reviewNoteInput" class="form-control" rows="2" placeholder="Interne Notiz zum Vorfall…"></textarea>
            </div>` : ''}
        `);
    });
});

// ── Mark single as reviewed ───────────────────────────────────────────────
$('#markReviewedBtn').on('click', function() {
    if (!currentAlertId) return;
    const note = $('#reviewNoteInput').val() || '';
    $.post('admin_ajax/get_security_alerts.php', {
        action: 'mark_reviewed',
        alert_id: currentAlertId,
        review_note: note,
        csrf_token: csrf
    }, function(res) {
        if (res.success) {
            toastr.success('Als überprüft markiert.');
            $('#alertDetailModal').modal('hide');
            loadAlerts();
        } else {
            toastr.error(res.message || 'Fehler');
        }
    }, 'json');
});

// ── Mark all reviewed ─────────────────────────────────────────────────────
$('#markAllReviewedBtn').on('click', function() {
    if (!confirm('Alle ausstehenden Alerts als überprüft markieren?')) return;
    $.post('admin_ajax/get_security_alerts.php', {action: 'mark_all_reviewed', csrf_token: csrf}, function(res) {
        if (res.success) {
            toastr.success('Alle Alerts überprüft.');
            loadAlerts();
            $('#markAllReviewedBtn').hide();
        } else {
            toastr.error(res.message || 'Fehler');
        }
    }, 'json');
});

$(document).ready(loadAlerts);
</script>
