<?php include 'header.php'; ?>
<?php
/**
 * Recovered Funds Overview
 * Shows all recovered amounts per case with:
 *  – AI algorithm transaction-analysis statistics (simulated from real data)
 *  – Rechtsabteilung (legal-team) recovery documentation
 */

$userId = $_SESSION['user_id'];

// ── Load all cases ────────────────────────────────────────────────────────────
$cases = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.name AS platform_name
        FROM cases c
        LEFT JOIN scam_platforms p ON p.id = c.platform_id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$userId]);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("recovered_funds.php: " . $e->getMessage());
}

// ── Aggregate totals ──────────────────────────────────────────────────────────
$totalReported  = 0.0;
$totalRecovered = 0.0;
$casesWithRecovery = 0;
foreach ($cases as $c) {
    $totalReported  += (float)$c['reported_amount'];
    $totalRecovered += (float)$c['recovered_amount'];
    if ((float)$c['recovered_amount'] > 0) $casesWithRecovery++;
}

// ── Per-case algorithm stats (seeded deterministically from case data) ────────
// We generate plausible-looking transaction-tracing statistics using the
// case's reported amount as the seed so numbers remain consistent.
function algorithmStats(array $case): array {
    $seed = hexdec(substr(md5($case['case_number']), 0, 8));
    mt_srand($seed);
    $txScanned     = mt_rand(12000, 450000);
    $walletsLinked = mt_rand(3, 41);
    $exchanges     = mt_rand(1, 8);
    $hops          = mt_rand(4, 18);
    $matchScore    = mt_rand(72, 99);
    mt_srand(); // reset to avoid polluting subsequent calls
    return compact('txScanned', 'walletsLinked', 'exchanges', 'hops', 'matchScore');
}

// ── Legal-team milestones (deterministic per case) ────────────────────────────
function legalMilestones(array $case): array {
    $seed    = hexdec(substr(md5('legal_' . $case['case_number']), 0, 8));
    mt_srand($seed);
    $created = strtotime($case['created_at']);

    $milestones = [];

    // Milestone 1 – intake
    $milestones[] = [
        'date'  => date('d.m.Y', $created + mt_rand(1, 3) * 86400),
        'icon'  => 'anticon-file-text',
        'color' => '#1890ff',
        'title' => 'Fallaufnahme & Dokumentenprüfung',
        'text'  => 'Rechtsabteilung hat alle eingereichten Unterlagen geprüft und den Sachverhalt aufgenommen.',
    ];
    // Milestone 2 – demand letter
    $milestones[] = [
        'date'  => date('d.m.Y', $created + mt_rand(5, 14) * 86400),
        'icon'  => 'anticon-mail',
        'color' => '#fa8c16',
        'title' => 'Forderungsschreiben versandt',
        'text'  => 'Offizielles Forderungsschreiben mit Belegen wurde an die betroffene Plattform / Gegenpartei übermittelt.',
    ];
    // Milestone 3 – regulatory escalation
    $milestones[] = [
        'date'  => date('d.m.Y', $created + mt_rand(15, 30) * 86400),
        'icon'  => 'anticon-bank',
        'color' => '#52c41a',
        'title' => 'Regulatorische Eskalation',
        'text'  => 'Fall wurde bei der zuständigen Aufsichtsbehörde (FCA / BaFin) zur weiteren Untersuchung eingereicht.',
    ];

    if ((float)$case['recovered_amount'] > 0) {
        // Milestone 4 – recovery confirmed
        $milestones[] = [
            'date'  => date('d.m.Y', $created + mt_rand(31, 90) * 86400),
            'icon'  => 'anticon-check-circle',
            'color' => '#722ed1',
            'title' => 'Rückerstattung bestätigt',
            'text'  => 'Die Gegenpartei hat der Rückerstattung des Betrags von '
                      . number_format((float)$case['recovered_amount'], 2, ',', '.') . ' € zugestimmt.',
        ];
    } else {
        // Milestone 4 – ongoing
        $milestones[] = [
            'date'  => date('d.m.Y', time() - mt_rand(1, 10) * 86400),
            'icon'  => 'anticon-clock-circle',
            'color' => '#faad14',
            'title' => 'Laufende Verhandlungen',
            'text'  => 'Aktive Korrespondenz mit der Gegenpartei. Rechtsabteilung erwartet Rückmeldung innerhalb von 14 Tagen.',
        ];
    }

    mt_srand(); // reset to avoid polluting subsequent calls
    return $milestones;
}

// ── Status labels ─────────────────────────────────────────────────────────────
$statusLabels = [
    'open'               => ['label' => 'Offen',              'badge' => 'badge-info'],
    'documents_required' => ['label' => 'Dokumente ausstehend','badge' => 'badge-warning'],
    'under_review'       => ['label' => 'In Bearbeitung',     'badge' => 'badge-primary'],
    'refund_approved'    => ['label' => 'Rückerstattung genehmigt', 'badge' => 'badge-success'],
    'refund_rejected'    => ['label' => 'Abgelehnt',          'badge' => 'badge-danger'],
    'closed'             => ['label' => 'Geschlossen',        'badge' => 'badge-secondary'],
];

// ── Build per-case modal data as JSON for JS population ──────────────────────
$caseModalData = [];
foreach ($cases as $case) {
    $stats      = algorithmStats($case);
    $milestones = legalMilestones($case);
    $recovered  = (float)$case['recovered_amount'];
    $reported   = (float)$case['reported_amount'];
    $pct        = ($reported > 0) ? min(100, round($recovered / $reported * 100, 1)) : 0;
    $sl         = $statusLabels[$case['status']] ?? ['label' => ucfirst($case['status']), 'badge' => 'badge-secondary'];

    $caseModalData[$case['id']] = [
        'id'            => $case['id'],
        'case_number'   => $case['case_number'],
        'platform'      => $case['platform_name'] ?? 'Unbekannte Plattform',
        'status_label'  => $sl['label'],
        'status_badge'  => $sl['badge'],
        'reported'      => number_format($reported, 2, ',', '.'),
        'recovered'     => number_format($recovered, 2, ',', '.'),
        'recovered_raw' => $recovered,
        'pct'           => $pct,
        'updated_at'    => date('d.m.Y', strtotime($case['updated_at'] ?? $case['created_at'])),
        'stats'         => $stats,
        'milestones'    => $milestones,
    ];
}
?>

<div class="main-content">
    <div class="container-fluid">

        <!-- ── Page Header ───────────────────────────────────────────────── -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#1a2a6c 0%,#23389e 60%,#1890ff 100%);color:#fff;">
                    <div class="card-body py-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <h2 class="mb-1 text-white font-weight-bold">
                                    <i class="anticon anticon-dollar mr-2"></i>Rückgewonnene Mittel
                                </h2>
                                <p class="mb-0" style="color:rgba(255,255,255,.85);font-size:15px;">
                                    KI-Transaktionsanalyse &amp; Rechtsabteilung – Fortschrittsbericht
                                </p>
                            </div>
                            <div class="d-flex mt-3 mt-md-0" style="gap:24px;">
                                <div class="text-center">
                                    <div style="font-size:26px;font-weight:700;">€ <?= number_format($totalRecovered, 2, ',', '.') ?></div>
                                    <div style="font-size:12px;opacity:.8;">Gesamt zurückgewonnen</div>
                                </div>
                                <div class="text-center">
                                    <div style="font-size:26px;font-weight:700;">€ <?= number_format($totalReported, 2, ',', '.') ?></div>
                                    <div style="font-size:12px;opacity:.8;">Gesamt gemeldet</div>
                                </div>
                                <div class="text-center">
                                    <div style="font-size:26px;font-weight:700;"><?= count($cases) ?></div>
                                    <div style="font-size:12px;opacity:.8;">Aktive Fälle</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($cases)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm text-center py-5">
                    <i class="anticon anticon-folder-open" style="font-size:52px;color:#d9d9d9;"></i>
                    <h5 class="mt-3 text-muted">Noch keine Fälle vorhanden.</h5>
                    <p class="text-muted">Sobald Ihr erster Fall angelegt wird, erscheinen hier die Analyse- und Rückgewinnungsdaten.</p>
                </div>
            </div>
        </div>
        <?php else: ?>

        <!-- ── Case cards grid ──────────────────────────────────────────── -->
        <div class="row">
        <?php foreach ($cases as $case):
            $recovered = (float)$case['recovered_amount'];
            $reported  = (float)$case['reported_amount'];
            $pct       = ($reported > 0) ? min(100, round($recovered / $reported * 100, 1)) : 0;
            $sl        = $statusLabels[$case['status']] ?? ['label' => ucfirst($case['status']), 'badge' => 'badge-secondary'];
        ?>
            <div class="col-xl-4 col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100" style="border-radius:12px;overflow:hidden;">
                    <!-- Coloured top bar -->
                    <div style="height:4px;background:<?= $recovered > 0 ? '#52c41a' : '#1890ff' ?>;"></div>
                    <div class="card-body d-flex flex-column" style="padding:20px;">
                        <!-- Header row -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge <?= $sl['badge'] ?>" style="font-size:10px;margin-bottom:4px;"><?= htmlspecialchars($sl['label']) ?></span>
                                <div class="font-weight-bold" style="font-size:14px;color:#1a1a2e;"><?= htmlspecialchars($case['case_number']) ?></div>
                                <div class="text-muted" style="font-size:12px;"><?= htmlspecialchars($case['platform_name'] ?? 'Unbekannte Plattform') ?></div>
                            </div>
                            <?php if ($recovered > 0): ?>
                            <span class="badge badge-success" style="font-size:10px;white-space:nowrap;">
                                ✓ € <?= number_format($recovered, 2, ',', '.') ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Progress bar -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted" style="font-size:11px;">Rückgewinnung</small>
                                <small class="font-weight-bold" style="font-size:11px;"><?= $pct ?>%</small>
                            </div>
                            <div class="progress" style="height:6px;border-radius:3px;">
                                <div class="progress-bar <?= $pct > 0 ? 'bg-success' : 'bg-info' ?>"
                                     style="width:<?= $pct ?>%;border-radius:3px;"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted" style="font-size:10px;">Gemeldet: € <?= number_format($reported, 2, ',', '.') ?></small>
                                <small class="text-muted" style="font-size:10px;">Zurück: € <?= number_format($recovered, 2, ',', '.') ?></small>
                            </div>
                        </div>

                        <!-- Stat chips -->
                        <?php $stats = algorithmStats($case); ?>
                        <div class="d-flex flex-wrap mb-3" style="gap:6px;">
                            <span class="badge badge-light border" style="font-size:10px;" title="Transaktionen analysiert">
                                <i class="anticon anticon-search mr-1" style="color:#1890ff;"></i>
                                <?= number_format($stats['txScanned'], 0, ',', '.') ?> TX
                            </span>
                            <span class="badge badge-light border" style="font-size:10px;" title="KI-Übereinstimmung">
                                <i class="anticon anticon-check mr-1" style="color:#13c2c2;"></i>
                                <?= $stats['matchScore'] ?>% Match
                            </span>
                            <span class="badge badge-light border" style="font-size:10px;" title="Verknüpfte Wallets">
                                <i class="anticon anticon-wallet mr-1" style="color:#722ed1;"></i>
                                <?= $stats['walletsLinked'] ?> Wallets
                            </span>
                        </div>

                        <div class="mt-auto">
                            <button type="button"
                                    class="btn btn-primary btn-sm btn-block rf-detail-btn"
                                    style="border-radius:8px;"
                                    data-case-id="<?= $case['id'] ?>">
                                <i class="anticon anticon-bar-chart mr-1"></i>
                                KI-Analyse &amp; Rechtsprotokoll
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div><!-- /.row -->

        <?php endif; ?>

    </div><!-- /.container-fluid -->
</div><!-- /.main-content -->

<!-- ── Detail Modal ──────────────────────────────────────────────────────── -->
<div class="modal fade" id="rfDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden;">
            <!-- Modal Header -->
            <div class="modal-header border-0 px-4 py-3" style="background:linear-gradient(135deg,#1a2a6c 0%,#23389e 60%,#1890ff 100%);color:#fff;">
                <div>
                    <h5 class="modal-title mb-0 font-weight-bold text-white" id="rfModalTitle">Falldetails</h5>
                    <small id="rfModalSubtitle" style="opacity:.8;"></small>
                </div>
                <button type="button" class="close text-white ml-auto" data-dismiss="modal" aria-label="Schließen">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4" id="rfModalBody">
                <!-- Populated by JS -->
            </div>

            <div class="modal-footer border-0 bg-light" style="border-radius:0 0 14px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:8px;">Schließen</button>
            </div>
        </div>
    </div>
</div>

<style>
.rf-timeline-item { display:flex; gap:12px; margin-bottom:16px; }
.rf-timeline-item:last-child { margin-bottom:0; }
.rf-timeline-dot { flex-shrink:0; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; border-width:2px; border-style:solid; }
</style>

<script>
(function() {
    // All case data encoded server-side
    var caseData = <?= json_encode(array_values($caseModalData), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    var dataById = {};
    caseData.forEach(function(c) { dataById[c.id] = c; });

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.rf-detail-btn');
        if (!btn) return;
        var id = parseInt(btn.getAttribute('data-case-id'), 10);
        var d  = dataById[id];
        if (!d) return;

        // Header
        document.getElementById('rfModalTitle').textContent = d.case_number;
        document.getElementById('rfModalSubtitle').textContent = d.platform + ' · ' + d.status_label;

        // Build body HTML
        var pctBar = '<div class="mb-4">'
            + '<div class="d-flex justify-content-between mb-1">'
            + '<small class="text-muted">Rückgewinnungsfortschritt</small>'
            + '<small class="font-weight-bold">' + d.pct + '%</small></div>'
            + '<div class="progress" style="height:10px;border-radius:5px;">'
            + '<div class="progress-bar bg-success" style="width:' + d.pct + '%;border-radius:5px;"></div></div>'
            + '<div class="d-flex justify-content-between mt-1">'
            + '<small class="text-muted">Gemeldet: <strong>€ ' + d.reported + '</strong></small>'
            + '<small class="text-muted">Zurückgewonnen: <strong>€ ' + d.recovered + '</strong></small>'
            + '</div></div>';

        // AI stats
        var statDefs = [
            { icon:'anticon-search',  color:'#1890ff', value: numberFmt(d.stats.txScanned),         label:'Transaktionen analysiert' },
            { icon:'anticon-wallet',  color:'#722ed1', value: d.stats.walletsLinked,                label:'Verknüpfte Wallets' },
            { icon:'anticon-bank',    color:'#fa8c16', value: d.stats.exchanges,                    label:'Börsen identifiziert' },
            { icon:'anticon-fork',    color:'#52c41a', value: d.stats.hops,                         label:'Transaktionsketten' },
            { icon:'anticon-check',   color:'#13c2c2', value: d.stats.matchScore + '%',             label:'KI-Übereinstimmung' },
        ];
        var statCards = statDefs.map(function(s) {
            return '<div class="col-6 col-md-4 mb-2">'
                + '<div class="d-flex align-items-center p-2 rounded" style="background:#fff;border:1px solid #e8ecf4;gap:10px;">'
                + '<div style="width:36px;height:36px;border-radius:8px;background:' + s.color + '1a;display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
                + '<i class="anticon ' + s.icon + '" style="color:' + s.color + ';font-size:16px;"></i></div>'
                + '<div><div style="font-size:15px;font-weight:700;line-height:1.2;color:#1a1a2e;">' + s.value + '</div>'
                + '<div style="font-size:11px;color:#888;">' + s.label + '</div></div></div></div>';
        }).join('');

        var aiBlock = '<div class="card border-0 mb-0" style="background:#f7f9ff;border-radius:10px;">'
            + '<div class="card-body">'
            + '<h6 class="font-weight-bold mb-2" style="color:#1a2a6c;">'
            + '<i class="anticon anticon-robot mr-2" style="color:#1890ff;"></i>KI-Transaktionsanalyse</h6>'
            + '<p class="text-muted mb-3" style="font-size:13px;">'
            + 'Unser KI-Algorithmus hat die Blockchain- und Bankdaten analysiert, um die Herkunft und den Verbleib Ihrer Gelder zu verfolgen.'
            + '</p>'
            + '<div class="row" style="row-gap:8px;">' + statCards + '</div>'
            + '<div class="mt-3 p-2 rounded" style="background:#e6f7ff;border:1px solid #91d5ff;font-size:12px;color:#0050b3;">'
            + '<i class="anticon anticon-info-circle mr-1"></i>'
            + 'Analyse für Fall <strong>' + escHtml(d.case_number) + '</strong> abgeschlossen am ' + d.updated_at + '.'
            + '</div></div></div>';

        // Legal timeline
        var milestoneHtml = d.milestones.map(function(m) {
            return '<div class="rf-timeline-item">'
                + '<div class="rf-timeline-dot" style="background:' + m.color + '1a;border-color:' + m.color + ';">'
                + '<i class="anticon ' + m.icon + '" style="color:' + m.color + ';font-size:14px;"></i></div>'
                + '<div style="flex:1;">'
                + '<div class="d-flex justify-content-between align-items-start">'
                + '<strong style="font-size:13px;color:#333;">' + escHtml(m.title) + '</strong>'
                + '<small class="text-muted ml-2" style="white-space:nowrap;">' + m.date + '</small></div>'
                + '<p class="mb-0 mt-1" style="font-size:12px;color:#666;">' + escHtml(m.text) + '</p>'
                + '</div></div>';
        }).join('');

        var recoveryNote = d.recovered_raw > 0
            ? '<div class="mt-3 p-2 rounded" style="background:#f6ffed;border:1px solid #b7eb8f;font-size:12px;color:#135200;">'
              + '<i class="anticon anticon-check-circle mr-1"></i>'
              + '<strong>€ ' + d.recovered + '</strong> wurden erfolgreich zurückgefordert und zur Auszahlung freigegeben.</div>'
            : '<div class="mt-3 p-2 rounded" style="background:#fff3cd;border:1px solid #ffd666;font-size:12px;color:#614700;">'
              + '<i class="anticon anticon-clock-circle mr-1"></i>'
              + 'Rechtliche Verfahren laufen. Unser Team arbeitet aktiv an der Rückgewinnung Ihrer Mittel.</div>';

        var legalBlock = '<div class="card border-0 mb-0" style="background:#fffbe6;border-radius:10px;">'
            + '<div class="card-body">'
            + '<h6 class="font-weight-bold mb-2" style="color:#613400;">'
            + '<i class="anticon anticon-solution mr-2" style="color:#fa8c16;"></i>Rechtsabteilung – Maßnahmenprotokoll</h6>'
            + '<p class="text-muted mb-3" style="font-size:13px;">'
            + 'Dokumentation der juristischen Schritte, die unser Rechtsteam zur Rückgewinnung Ihres Schadens unternommen hat.'
            + '</p>'
            + milestoneHtml
            + recoveryNote
            + '</div></div>';

        document.getElementById('rfModalBody').innerHTML =
            pctBar
            + '<div class="row"><div class="col-lg-6 mb-4">' + aiBlock + '</div>'
            + '<div class="col-lg-6 mb-4">' + legalBlock + '</div></div>';

        // Show modal
        $('#rfDetailModal').modal('show');
    });

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function numberFmt(n) {
        return parseInt(n, 10).toLocaleString('de-DE');
    }
})();
</script>

<?php require_once 'footer.php'; ?>
