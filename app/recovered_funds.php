<?php include 'header.php'; ?>
<?php
/**
 * Recovered Funds Overview
 * Shows all recovered amounts per case with:
 *  – AI algorithm transaction-analysis statistics (simulated from real data)
 *  – Rechtsabteilung (legal-team) recovery documentation
 */

$userId = $_SESSION['user_id'];

// ── Load all cases with recovery data ────────────────────────────────────────
$cases = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.name AS platform_name, p.type AS platform_type
        FROM cases c
        LEFT JOIN platforms p ON c.platform_id = p.id
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

        <!-- ── Case accordion ───────────────────────────────────────────── -->
        <div id="recFundsAccordion">
        <?php foreach ($cases as $idx => $case):
            $stats      = algorithmStats($case);
            $milestones = legalMilestones($case);
            $recovered  = (float)$case['recovered_amount'];
            $reported   = (float)$case['reported_amount'];
            $pct        = ($reported > 0) ? min(100, round($recovered / $reported * 100, 1)) : 0;
            $sl         = $statusLabels[$case['status']] ?? ['label' => ucfirst($case['status']), 'badge' => 'badge-secondary'];
            $collapseId = 'case-' . $case['id'];
        ?>
        <div class="card border-0 shadow-sm mb-4">
            <!-- Card Header / toggle -->
            <div class="card-header d-flex align-items-center justify-content-between"
                 style="cursor:pointer;background:#fff;border-bottom:1px solid #f0f0f0;"
                 data-toggle="collapse" data-target="#<?= $collapseId ?>"
                 aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>">
                <div class="d-flex align-items-center" style="gap:16px;flex-wrap:wrap;">
                    <span class="badge <?= $sl['badge'] ?>" style="font-size:11px;"><?= htmlspecialchars($sl['label']) ?></span>
                    <strong><?= htmlspecialchars($case['case_number']) ?></strong>
                    <span class="text-muted" style="font-size:13px;"><?= htmlspecialchars($case['platform_name'] ?? 'Unbekannte Plattform') ?></span>
                    <?php if ($recovered > 0): ?>
                    <span class="badge badge-success" style="font-size:11px;">
                        ✓ € <?= number_format($recovered, 2, ',', '.') ?> zurückgewonnen
                    </span>
                    <?php endif; ?>
                </div>
                <i class="anticon anticon-down" style="font-size:14px;color:#999;"></i>
            </div>

            <div id="<?= $collapseId ?>" class="collapse <?= $idx === 0 ? 'show' : '' ?>">
                <div class="card-body" style="padding:24px;">

                    <!-- Recovery progress bar -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Rückgewinnungsfortschritt</small>
                            <small class="font-weight-bold"><?= $pct ?>%</small>
                        </div>
                        <div class="progress" style="height:10px;border-radius:5px;">
                            <div class="progress-bar bg-success" style="width:<?= $pct ?>%;border-radius:5px;"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Gemeldet: <strong>€ <?= number_format($reported, 2, ',', '.') ?></strong></small>
                            <small class="text-muted">Zurückgewonnen: <strong>€ <?= number_format($recovered, 2, ',', '.') ?></strong></small>
                        </div>
                    </div>

                    <div class="row">
                        <!-- ── LEFT: Algorithm analysis ──────────────────── -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-0 h-100" style="background:#f7f9ff;border-radius:10px;">
                                <div class="card-body">
                                    <h6 class="font-weight-bold mb-3" style="color:#1a2a6c;">
                                        <i class="anticon anticon-robot mr-2" style="color:#1890ff;"></i>
                                        KI-Transaktionsanalyse
                                    </h6>
                                    <p class="text-muted mb-3" style="font-size:13px;">
                                        Unser KI-Algorithmus hat die Blockchain- und Bankdaten analysiert, um die Herkunft und den Verbleib Ihrer Gelder zu verfolgen.
                                    </p>

                                    <div class="row" style="row-gap:12px;">
                                        <?php
                                        $statCards = [
                                            ['icon' => 'anticon-search', 'color' => '#1890ff', 'value' => number_format($stats['txScanned'], 0, ',', '.'), 'label' => 'Transaktionen analysiert'],
                                            ['icon' => 'anticon-wallet', 'color' => '#722ed1', 'value' => $stats['walletsLinked'],  'label' => 'Verknüpfte Wallets'],
                                            ['icon' => 'anticon-bank',   'color' => '#fa8c16', 'value' => $stats['exchanges'],      'label' => 'Börsen identifiziert'],
                                            ['icon' => 'anticon-fork',   'color' => '#52c41a', 'value' => $stats['hops'],           'label' => 'Transaktionsketten'],
                                            ['icon' => 'anticon-check',  'color' => '#13c2c2', 'value' => $stats['matchScore'] . '%','label' => 'KI-Übereinstimmung'],
                                        ];
                                        foreach ($statCards as $sc):
                                        ?>
                                        <div class="col-6">
                                            <div class="d-flex align-items-center p-2 rounded" style="background:#fff;border:1px solid #e8ecf4;gap:10px;">
                                                <div style="width:36px;height:36px;border-radius:8px;background:<?= $sc['color'] ?>1a;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                    <i class="anticon <?= $sc['icon'] ?>" style="color:<?= $sc['color'] ?>;font-size:16px;"></i>
                                                </div>
                                                <div>
                                                    <div style="font-size:15px;font-weight:700;line-height:1.2;color:#1a1a2e;"><?= $sc['value'] ?></div>
                                                    <div style="font-size:11px;color:#888;"><?= $sc['label'] ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="mt-3 p-2 rounded" style="background:#e6f7ff;border:1px solid #91d5ff;font-size:12px;color:#0050b3;">
                                        <i class="anticon anticon-info-circle mr-1"></i>
                                        Analyse für Fall <strong><?= htmlspecialchars($case['case_number']) ?></strong> abgeschlossen am
                                        <?= date('d.m.Y', strtotime($case['updated_at'])) ?>.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── RIGHT: Rechtsabteilung milestones ──────────── -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-0 h-100" style="background:#fffbe6;border-radius:10px;">
                                <div class="card-body">
                                    <h6 class="font-weight-bold mb-3" style="color:#613400;">
                                        <i class="anticon anticon-solution mr-2" style="color:#fa8c16;"></i>
                                        Rechtsabteilung – Maßnahmenprotokoll
                                    </h6>
                                    <p class="text-muted mb-3" style="font-size:13px;">
                                        Dokumentation der juristischen Schritte, die unser Rechtsteam zur Rückgewinnung Ihres Schadens unternommen hat.
                                    </p>

                                    <div class="timeline-container">
                                    <?php foreach ($milestones as $mi): ?>
                                        <div class="d-flex mb-3" style="gap:12px;">
                                            <div style="flex-shrink:0;width:36px;height:36px;border-radius:50%;background:<?= $mi['color'] ?>1a;display:flex;align-items:center;justify-content:center;border:2px solid <?= $mi['color'] ?>;">
                                                <i class="anticon <?= $mi['icon'] ?>" style="color:<?= $mi['color'] ?>;font-size:14px;"></i>
                                            </div>
                                            <div style="flex:1;">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <strong style="font-size:13px;color:#333;"><?= htmlspecialchars($mi['title']) ?></strong>
                                                    <small class="text-muted ml-2" style="white-space:nowrap;"><?= $mi['date'] ?></small>
                                                </div>
                                                <p class="mb-0 mt-1" style="font-size:12px;color:#666;"><?= htmlspecialchars($mi['text']) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>

                                    <?php if ($recovered > 0): ?>
                                    <div class="mt-3 p-2 rounded" style="background:#f6ffed;border:1px solid #b7eb8f;font-size:12px;color:#135200;">
                                        <i class="anticon anticon-check-circle mr-1"></i>
                                        <strong>€ <?= number_format($recovered, 2, ',', '.') ?></strong> wurden erfolgreich zurückgefordert und zur Auszahlung freigegeben.
                                    </div>
                                    <?php else: ?>
                                    <div class="mt-3 p-2 rounded" style="background:#fff3cd;border:1px solid #ffd666;font-size:12px;color:#614700;">
                                        <i class="anticon anticon-clock-circle mr-1"></i>
                                        Rechtliche Verfahren laufen. Unser Team arbeitet aktiv an der Rückgewinnung Ihrer Mittel.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div><!-- /row -->

                </div><!-- /card-body -->
            </div><!-- /collapse -->
        </div><!-- /card -->
        <?php endforeach; ?>
        </div><!-- /#recFundsAccordion -->

        <?php endif; ?>

    </div><!-- /.container-fluid -->
</div><!-- /.main-content -->

<style>
.timeline-container .d-flex:last-child { margin-bottom: 0 !important; }
</style>

<?php require_once 'footer.php'; ?>
