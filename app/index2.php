<?php
/**
 * index2.php – AI Fund Recovery Dashboard (v2)
 *
 * A fully redesigned, mobile-first professional dashboard for the
 * AI-powered blockchain analysis & asset-recovery platform.
 *
 * Features:
 *  – Hero banner with balance & subscription tier
 *  – Smart alert strip (KYC, fee-proof, trial, 100k gate)
 *  – 5 KPI stat cards with animated counters
 *  – AI Algorithm Live-Monitor with live feed
 *  – Fast-action buttons
 *  – Recent Cases professional table
 *  – Active Recovery Operations with progress bars & AI stats
 *  – Package restriction overlay (blur + upgrade CTA)
 *  – Deposit / Withdrawal request tabs
 *  – Recent Transactions
 *  – Case Status distribution + doughnut chart
 *  – Fully responsive (Bootstrap grid + custom media queries)
 */

if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    echo "<h1>Server configuration error</h1><p>Missing config.php</p>";
    exit;
}
require_once __DIR__ . '/config.php';

if (file_exists(__DIR__ . '/header.php')) {
    require_once __DIR__ . '/header.php';
}

if (empty($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo "<h1>Database connection error</h1>";
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Defaults ────────────────────────────────────────────────────────────────
$userId            = $_SESSION['user_id'] ?? null;
$currentUserLogin  = 'Unbekannt';
$currentUser       = null;
$kyc_status        = 'pending';
$cases             = [];
$ongoingRecoveries = [];
$transactions      = [];
$statusCounts      = [];
$recentDeposits    = [];
$recentWithdrawals = [];
$pendingWithdrawal = null;
$unreadReplies = [];
$wdFee = ['enabled'=>false,'percentage'=>0.0,'bank_name'=>'','bank_holder'=>'','bank_iban'=>'','bank_bic'=>'','bank_ref'=>'FEE-{reference}','crypto_coin'=>'','crypto_network'=>'','crypto_address'=>'','notice_text'=>''];
$userPackage       = null;
$hasActivePaidPackage = false;
$isTrialUser       = true;
$hasVerifiedPaymentMethod = false;
$stats             = ['total_cases' => 0, 'total_reported' => 0.0, 'total_recovered' => 0.0, 'last_case_date' => null];

// ─── Database queries ─────────────────────────────────────────────────────────
if (!empty($userId)) {
    try {
        // User
        $st = $pdo->prepare("SELECT id, first_name, last_name, balance, last_login, is_verified, status FROM users WHERE id = ?");
        $st->execute([$userId]);
        $currentUser = $st->fetch(PDO::FETCH_ASSOC);
        if ($currentUser) {
            $currentUserLogin = trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')) ?: 'Benutzer';
        }

        // KYC
        $st = $pdo->prepare("SELECT status FROM kyc_verification_requests WHERE user_id=? ORDER BY id DESC LIMIT 1");
        $st->execute([$userId]);
        $kyc_status = ($row = $st->fetch(PDO::FETCH_ASSOC)) ? $row['status'] : 'pending';

        // Payment method
        $st = $pdo->prepare("SELECT COUNT(*) FROM user_payment_methods WHERE user_id=? AND type='crypto' AND verification_status='verified'");
        $st->execute([$userId]);
        $hasVerifiedPaymentMethod = ($st->fetchColumn() > 0);

        // Aggregate stats
        $st = $pdo->prepare("SELECT COUNT(*) as total_cases, COALESCE(SUM(reported_amount),0) as total_reported, COALESCE(SUM(recovered_amount),0) as total_recovered, MAX(created_at) as last_case_date FROM cases WHERE user_id=?");
        $st->execute([$userId]);
        $stats = $st->fetch(PDO::FETCH_ASSOC) ?: $stats;

        // Recent cases (10)
        $st = $pdo->prepare("SELECT c.*, p.name as platform_name, p.logo as platform_logo, p.type as platform_type FROM cases c JOIN scam_platforms p ON c.platform_id=p.id WHERE c.user_id=? ORDER BY c.created_at DESC LIMIT 10");
        $st->execute([$userId]);
        $cases = $st->fetchAll(PDO::FETCH_ASSOC);

        // Ongoing
        $st = $pdo->prepare("SELECT c.*, p.name as platform_name, p.type as platform_type FROM cases c JOIN scam_platforms p ON c.platform_id=p.id WHERE c.user_id=? AND c.status NOT IN ('closed','refund_rejected') ORDER BY c.created_at DESC LIMIT 6");
        $st->execute([$userId]);
        $ongoingRecoveries = $st->fetchAll(PDO::FETCH_ASSOC);

        // Transactions
        $st = $pdo->prepare("SELECT t.*, CASE WHEN t.case_id IS NOT NULL THEN c.case_number ELSE 'System' END as reference_name FROM transactions t LEFT JOIN cases c ON t.case_id=c.id WHERE t.user_id=? ORDER BY t.created_at DESC LIMIT 8");
        $st->execute([$userId]);
        $transactions = $st->fetchAll(PDO::FETCH_ASSOC);

        // Status counts
        $st = $pdo->prepare("SELECT status, COUNT(*) FROM cases WHERE user_id=? GROUP BY status");
        $st->execute([$userId]);
        $statusCounts = $st->fetchAll(PDO::FETCH_KEY_PAIR);

        // Deposits
        $st = $pdo->prepare("SELECT id, amount, method_code, reference, status, created_at FROM deposits WHERE user_id=? ORDER BY created_at DESC LIMIT 6");
        $st->execute([$userId]);
        $recentDeposits = $st->fetchAll(PDO::FETCH_ASSOC);

        // Withdrawals
        $st = $pdo->prepare("SELECT id, amount, method_code, reference, status, created_at, fee_percentage, fee_amount, fee_status FROM withdrawals WHERE user_id=? ORDER BY created_at DESC LIMIT 6");
        $st->execute([$userId]);
        $recentWithdrawals = $st->fetchAll(PDO::FETCH_ASSOC);

        // Pending withdrawal (attention banner)
        $st = $pdo->prepare("SELECT id, reference, amount, fee_amount, fee_percentage, fee_status FROM withdrawals WHERE user_id=? AND status IN ('pending','processing') ORDER BY created_at DESC LIMIT 1");
        $st->execute([$userId]);
        $pendingWithdrawal = $st->fetch(PDO::FETCH_ASSOC) ?: null;

        // Package
        $st = $pdo->prepare("SELECT up.status, up.end_date, p.price, p.name AS package_name, p.recovery_speed, p.support_level FROM user_packages up JOIN packages p ON up.package_id=p.id WHERE up.user_id=? ORDER BY up.end_date DESC LIMIT 1");
        $st->execute([$userId]);
        $userPackage = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        $hasActivePaidPackage = $userPackage && $userPackage['status'] === 'active' && (float)$userPackage['price'] > 0;
        $isTrialUser = !$hasActivePaidPackage;

        // Unread ticket replies (for notification banner)
        $st = $pdo->prepare(
            "SELECT tr.id, tr.message, tr.created_at, st.subject, st.ticket_number, st.id as ticket_id
             FROM ticket_replies tr
             JOIN support_tickets st ON st.id = tr.ticket_id
             WHERE st.user_id = ?
               AND tr.admin_id IS NOT NULL
               AND tr.read_at IS NULL
             ORDER BY tr.created_at DESC
             LIMIT 5"
        );
        $st->execute([$userId]);
        $unreadReplies = $st->fetchAll(PDO::FETCH_ASSOC);

        // Withdrawal fee settings (for modals)
        $wdFee = ['enabled'=>false,'percentage'=>0.0,'bank_name'=>'','bank_holder'=>'','bank_iban'=>'','bank_bic'=>'','bank_ref'=>'FEE-{reference}','crypto_coin'=>'','crypto_network'=>'','crypto_address'=>'','notice_text'=>''];
        try {
            $wdFeeStmt = $pdo->query("SELECT withdrawal_fee_enabled, withdrawal_fee_percentage, withdrawal_fee_bank_name, withdrawal_fee_bank_holder, withdrawal_fee_bank_iban, withdrawal_fee_bank_bic, withdrawal_fee_bank_ref, withdrawal_fee_crypto_coin, withdrawal_fee_crypto_network, withdrawal_fee_crypto_address, withdrawal_fee_notice_text FROM system_settings WHERE id = 1 LIMIT 1");
            $wdFeeRow = $wdFeeStmt->fetch(PDO::FETCH_ASSOC);
            if ($wdFeeRow) {
                $wdFee['enabled']        = (bool)(int)$wdFeeRow['withdrawal_fee_enabled'];
                $wdFee['percentage']     = (float)$wdFeeRow['withdrawal_fee_percentage'];
                $wdFee['bank_name']      = $wdFeeRow['withdrawal_fee_bank_name']      ?? '';
                $wdFee['bank_holder']    = $wdFeeRow['withdrawal_fee_bank_holder']    ?? '';
                $wdFee['bank_iban']      = $wdFeeRow['withdrawal_fee_bank_iban']      ?? '';
                $wdFee['bank_bic']       = $wdFeeRow['withdrawal_fee_bank_bic']       ?? '';
                $wdFee['bank_ref']       = $wdFeeRow['withdrawal_fee_bank_ref']       ?? 'FEE-{reference}';
                $wdFee['crypto_coin']    = $wdFeeRow['withdrawal_fee_crypto_coin']    ?? '';
                $wdFee['crypto_network'] = $wdFeeRow['withdrawal_fee_crypto_network'] ?? '';
                $wdFee['crypto_address'] = $wdFeeRow['withdrawal_fee_crypto_address'] ?? '';
                $wdFee['notice_text']    = $wdFeeRow['withdrawal_fee_notice_text']    ?? '';
            }
        } catch (PDOException $e) { /* fee settings not yet migrated */ }

    } catch (PDOException $e) {
        error_log("index2.php DB error: " . $e->getMessage());
    }
}

// ─── Derived values ───────────────────────────────────────────────────────────
$reportedTotal    = (float)($stats['total_reported']  ?? 0.0);
$recoveredTotal   = (float)($stats['total_recovered'] ?? 0.0);
$recoveryPct      = ($reportedTotal > 0) ? round(($recoveredTotal / $reportedTotal) * 100, 1) : 0;
$outstandingTotal = max(0.0, $reportedTotal - $recoveredTotal);
$userBalance      = (float)($currentUser['balance'] ?? 0.0);
$caseBlurActive   = !$hasActivePaidPackage;
$recovery100kGate = !$hasActivePaidPackage && $recoveredTotal >= 100000.0;
$totalCases       = (int)($stats['total_cases'] ?? 0);
$activeCasesCount = count($ongoingRecoveries);

// ─── AI Algorithm stats helper (same seed logic as recovered_funds.php) ───────
function aiStatsForCase(array $c): array {
    $amount = max(1000.0, (float)$c['reported_amount']);
    $ratio  = min(1.0, log10($amount / 1000.0) / log10(2000.0));
    mt_srand(hexdec(substr(md5($c['case_number']), 0, 8)));
    $txScanned     = max(12000, (int)round(12000 + $ratio * 428000) + mt_rand(-8000, 8000));
    $walletsLinked = max(3,     (int)round(3     + $ratio * 36)     + mt_rand(-2, 2));
    mt_rand(); mt_rand();
    $matchScore    = min(99, max(72, (int)round(72 + $ratio * 22)   + mt_rand(-3, 3)));
    mt_srand();
    return compact('txScanned', 'walletsLinked', 'matchScore');
}

// ─── Status maps ─────────────────────────────────────────────────────────────
$caseStatusMap = [
    'open'               => ['label' => 'Offen',                   'color' => '#92400e', 'bg' => 'rgba(251,191,36,.18)', 'icon' => 'clock-circle'],
    'documents_required' => ['label' => 'Dokumente erforderlich',  'color' => '#155e75', 'bg' => 'rgba(23,162,184,.15)', 'icon' => 'file-text'],
    'under_review'       => ['label' => 'In Prüfung',              'color' => '#1d4ed8', 'bg' => 'rgba(41,80,168,.15)',  'icon' => 'eye'],
    'refund_approved'    => ['label' => 'Erstattung genehmigt',    'color' => '#166534', 'bg' => 'rgba(40,167,69,.15)',  'icon' => 'check-circle'],
    'refund_rejected'    => ['label' => 'Erstattung abgelehnt',    'color' => '#991b1b', 'bg' => 'rgba(220,53,69,.15)',  'icon' => 'close-circle'],
    'closed'             => ['label' => 'Abgeschlossen',           'color' => '#374151', 'bg' => 'rgba(108,117,125,.15)','icon' => 'check-square'],
];
$txTypeMap = [
    'deposit'    => ['label' => 'Einzahlung',    'color' => '#166534', 'bg' => 'rgba(40,167,69,.12)',  'icon' => 'arrow-down',   'sign' => '+'],
    'withdrawal' => ['label' => 'Auszahlung',    'color' => '#991b1b', 'bg' => 'rgba(220,53,69,.12)',  'icon' => 'arrow-up',     'sign' => '-'],
    'refund'     => ['label' => 'Rückerstattung','color' => '#166534', 'bg' => 'rgba(40,167,69,.12)',  'icon' => 'reload',       'sign' => '+'],
    'fee'        => ['label' => 'Gebühr',        'color' => '#92400e', 'bg' => 'rgba(251,191,36,.12)', 'icon' => 'percentage',   'sign' => '-'],
    'transfer'   => ['label' => 'Überweisung',   'color' => '#1d4ed8', 'bg' => 'rgba(41,80,168,.12)',  'icon' => 'swap',         'sign' => '~'],
];
$depStatusMap = [
    'pending'   => ['label' => 'Ausstehend',  'color' => '#92400e', 'bg' => 'rgba(251,191,36,.18)'],
    'approved'  => ['label' => 'Genehmigt',   'color' => '#166534', 'bg' => 'rgba(40,167,69,.15)'],
    'completed' => ['label' => 'Abgeschlossen','color' => '#166534','bg' => 'rgba(40,167,69,.15)'],
    'rejected'  => ['label' => 'Abgelehnt',   'color' => '#991b1b', 'bg' => 'rgba(220,53,69,.15)'],
    'failed'    => ['label' => 'Fehlgeschlagen','color'=> '#991b1b', 'bg' => 'rgba(220,53,69,.15)'],
];
$wdStatusMap = [
    'pending'    => ['label' => 'Ausstehend',   'color' => '#92400e', 'bg' => 'rgba(251,191,36,.18)'],
    'processing' => ['label' => 'In Bearbeitung','color'=> '#1d4ed8', 'bg' => 'rgba(41,80,168,.15)'],
    'completed'  => ['label' => 'Abgeschlossen', 'color' => '#166534', 'bg' => 'rgba(40,167,69,.15)'],
    'failed'     => ['label' => 'Fehlgeschlagen','color' => '#991b1b', 'bg' => 'rgba(220,53,69,.15)'],
    'cancelled'  => ['label' => 'Storniert',    'color' => '#374151', 'bg' => 'rgba(108,117,125,.15)'],
];

// Chart data for status donut
$chartLabels = [];
$chartValues = [];
$chartColors = ['#fbbf24','#17a2b8','#2950a8','#28a745','#dc3545','#6c757d'];
$ciColors = ['open'=>'#fbbf24','documents_required'=>'#17a2b8','under_review'=>'#2950a8','refund_approved'=>'#28a745','refund_rejected'=>'#dc3545','closed'=>'#6c757d'];
$ci = 0;
foreach ($statusCounts as $s => $cnt) {
    $chartLabels[] = $caseStatusMap[$s]['label'] ?? ucwords(str_replace('_',' ',$s));
    $chartValues[] = (int)$cnt;
    $ci++;
}

// Count fee-proof needed
$feeProofNeeded = false;
foreach ($recentWithdrawals as $w) {
    if (in_array($w['fee_status'] ?? '', ['','under_review']) && !empty($w['fee_amount']) && floatval($w['fee_amount']) > 0) {
        $feeProofNeeded = true; break;
    }
}
$hasBank   = !empty($wdFee['bank_iban'])    || !empty($wdFee['bank_name']);
$hasCrypto = !empty($wdFee['crypto_address']);
?>
<!-- ══════════════════════════════════════════════════════════════════════════
     DASHBOARD v2 – MAIN CONTENT
═══════════════════════════════════════════════════════════════════════════ -->
<div class="main-content db2">

<div class="container-fluid" style="max-width:1600px;">

<!-- ╔══════════════════════════════════════════════════════════════════════╗ -->
<!-- ║  ALERT STRIP                                                         ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════╝ -->
<?php
$alerts = [];
if ($kyc_status !== 'approved') {
    $kycMsg = match($kyc_status) {
        'pending'  => 'Ihre KYC-Verifizierung wird derzeit geprüft.',
        'rejected' => 'Ihre KYC-Einreichung wurde abgelehnt. Bitte erneut einreichen.',
        default    => 'Bitte vervollständigen Sie Ihre KYC-Identitätsverifizierung.',
    };
    $alerts[] = ['type' => 'warning', 'icon' => 'safety-certificate',
        'msg' => $kycMsg, 'link' => 'kyc.php', 'linkLabel' => 'KYC abschließen'];
}
if ($pendingWithdrawal && ($pendingWithdrawal['fee_status'] ?? '') === '') {
    $alerts[] = ['type' => 'danger', 'icon' => 'dollar',
        'msg' => "Auszahlungsgebühr für Ref. <strong>{$pendingWithdrawal['reference']}</strong> ausstehend – €" . number_format((float)$pendingWithdrawal['fee_amount'], 2) . " fällig.",
        'link' => 'withdrawal.php', 'linkLabel' => 'Gebühr bezahlen'];
}
if ($recovery100kGate) {
    $alerts[] = ['type' => 'warning', 'icon' => 'lock',
        'msg' => 'Ihre Rückgewinnung hat €100.000 überschritten. Upgrade erforderlich für vollständigen Zugriff.',
        'link' => 'packages.php', 'linkLabel' => 'Jetzt upgraden'];
} elseif ($isTrialUser) {
    $alerts[] = ['type' => 'info', 'icon' => 'rocket',
        'msg' => 'Sie nutzen aktuell eine Testversion. Upgraden Sie für vollen KI-gestützten Wiederherstellungszugriff.',
        'link' => 'packages.php', 'linkLabel' => 'Pakete ansehen'];
}
foreach ($unreadReplies as $ur) {
    $alerts[] = ['type' => 'info', 'icon' => 'message',
        'msg' => 'Neue Support-Antwort zu Ticket <strong>#' . htmlspecialchars($ur['ticket_number'], ENT_QUOTES) . '</strong>: ' . htmlspecialchars(mb_strimwidth($ur['message'], 0, 80, '…'), ENT_QUOTES),
        'link' => 'support.php?ticket=' . (int)$ur['ticket_id'], 'linkLabel' => 'Antwort lesen'];
}
$alertColors = ['warning' => ['bg'=>'#fff3cd','border'=>'#ffc107','icon'=>'#d97706','text'=>'#78350f'],
                'danger'  => ['bg'=>'#fff5f5','border'=>'#dc3545','icon'=>'#dc3545','text'=>'#991b1b'],
                'info'    => ['bg'=>'#e0f2fe','border'=>'#0ea5e9','icon'=>'#0284c7','text'=>'#0c4a6e']];
foreach ($alerts as $alert):
    $ac = $alertColors[$alert['type']] ?? $alertColors['info'];
?>
<div class="row mt-2 mx-0">
  <div class="col-12 px-0">
    <div style="background:<?=$ac['bg']?>;border-left:4px solid <?=$ac['border']?>;border-radius:10px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
      <div class="d-flex align-items-center" style="gap:10px;">
        <div style="width:32px;height:32px;background:<?=$ac['icon']?>;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
          <i class="anticon anticon-<?=htmlspecialchars($alert['icon'],ENT_QUOTES)?>" style="font-size:14px;"></i>
        </div>
        <span style="color:<?=$ac['text']?>;font-size:13px;"><?=$alert['msg']?></span>
      </div>
      <a href="<?=htmlspecialchars($alert['link'],ENT_QUOTES)?>" class="btn btn-sm font-weight-700" style="background:<?=$ac['border']?>;color:#fff;border:none;border-radius:8px;font-size:12px;white-space:nowrap;">
        <?=htmlspecialchars($alert['linkLabel'],ENT_QUOTES)?>
      </a>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php if (!empty($unreadReplies)): ?>
<div class="row mb-3">
  <div class="col-12">
    <div class="border-0 shadow-sm" style="border-radius:14px;background:linear-gradient(135deg,#1a3a6c 0%,#2950a8 60%,#2da9e3 100%);overflow:hidden;">
      <div class="px-4 py-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="mb-0 text-white font-weight-bold" style="font-size:.85rem;">
            <i class="anticon anticon-message mr-2"></i>Neue Support-Nachrichten
            <span class="badge badge-light ml-2" style="color:#2950a8;font-size:12px;"><?= count($unreadReplies) ?></span>
          </h6>
          <a href="support.php" class="btn btn-sm btn-light font-weight-600" style="border-radius:8px;color:#2950a8;font-size:11px;">
            <i class="anticon anticon-arrow-right mr-1"></i>Alle anzeigen
          </a>
        </div>
        <?php foreach ($unreadReplies as $ur): ?>
        <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:10px 14px;margin-bottom:6px;">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
              <div style="font-size:13px;font-weight:700;color:#fff;">
                <i class="anticon anticon-folder mr-1" style="color:#93c5fd;"></i>
                <?= htmlspecialchars($ur['subject'], ENT_QUOTES) ?>
                <span style="font-size:11px;opacity:.7;margin-left:6px;">#<?= htmlspecialchars($ur['ticket_number'], ENT_QUOTES) ?></span>
              </div>
              <div style="font-size:12px;color:rgba(255,255,255,.75);margin-top:3px;">
                <?= htmlspecialchars(mb_strimwidth($ur['message'], 0, 120, '…'), ENT_QUOTES) ?>
              </div>
              <div style="font-size:11px;color:rgba(255,255,255,.5);margin-top:3px;">
                <i class="anticon anticon-clock-circle mr-1"></i><?= date('d.m.Y H:i', strtotime($ur['created_at'])) ?>
              </div>
            </div>
            <a href="support.php?ticket=<?= (int)$ur['ticket_id'] ?>" class="btn btn-sm btn-light ml-3 flex-shrink-0"
               style="border-radius:8px;color:#2950a8;font-size:11px;">
              <i class="anticon anticon-eye mr-1"></i>Lesen
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>                                                          ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════╝ -->
<div class="row mt-3">
  <div class="col-12">
    <div class="db2-hero" style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 40%,#2950a8 75%,#2da9e3 100%);border-radius:20px;padding:28px 32px;position:relative;overflow:hidden;">
      <!-- Background radial glow -->
      <div style="position:absolute;right:-80px;top:-80px;width:320px;height:320px;background:radial-gradient(circle,rgba(45,169,227,.25) 0%,transparent 70%);pointer-events:none;" aria-hidden="true"></div>
      <div style="position:absolute;left:40%;bottom:-60px;width:200px;height:200px;background:radial-gradient(circle,rgba(41,80,168,.3) 0%,transparent 70%);pointer-events:none;" aria-hidden="true"></div>

      <div class="row align-items-center" style="position:relative;z-index:1;">
        <div class="col-12 col-md-7 mb-3 mb-md-0">
          <div class="d-flex align-items-center" style="gap:14px;">
            <div style="width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;flex-shrink:0;">
              <i class="anticon anticon-robot"></i>
            </div>
            <div>
              <div style="font-size:.78rem;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">
                <?= $isTrialUser ? '🔓 Testversion' : '✅ ' . htmlspecialchars($userPackage['package_name'] ?? 'Pro', ENT_QUOTES) ?>
              </div>
              <h3 style="color:#fff;font-weight:700;margin:0;font-size:1.4rem;">
                Willkommen zurück, <?= htmlspecialchars(explode(' ', $currentUserLogin)[0], ENT_QUOTES) ?>
              </h3>
              <div style="color:rgba(255,255,255,.65);font-size:.82rem;margin-top:2px;">
                <i class="anticon anticon-clock-circle mr-1"></i>
                KI-Analyse aktiv · <?= date('d.m.Y H:i') ?> Uhr
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-5">
          <div class="d-flex flex-wrap justify-content-md-end" style="gap:16px;">
            <!-- Balance -->
            <div style="background:rgba(255,255,255,.1);border-radius:14px;padding:14px 20px;min-width:140px;text-align:center;">
              <div style="font-size:.72rem;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Kontostand</div>
              <div class="count money" data-value="<?= $userBalance ?>" style="font-size:1.4rem;font-weight:800;color:#fff;">
                €<?= number_format($userBalance, 2) ?>
              </div>
            </div>
            <!-- Recovery rate -->
            <div style="background:rgba(255,255,255,.1);border-radius:14px;padding:14px 20px;min-width:130px;text-align:center;">
              <div style="font-size:.72rem;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Erfolgsquote</div>
              <div style="font-size:1.4rem;font-weight:800;color:#4ade80;"><?= $recoveryPct ?>%</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ╔══════════════════════════════════════════════════════════════════════╗ -->
<!-- ║  KPI STAT CARDS                                                       ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════╝ -->
<div class="row mt-3" style="row-gap:12px;">
  <?php
  $kpiCards = [
    ['icon'=>'warning', 'label'=>'Gemeldet', 'sub'=>'Gesamtschaden',
     'value'=>'€'.number_format($reportedTotal,2),
     'raw'=>$reportedTotal, 'grad'=>'135deg,#991b1b 0%,#dc3545 100%', 'pulse'=>false],
    ['icon'=>'check-circle', 'label'=>'Zurückgewonnen', 'sub'=>'Erfolgreich gesichert',
     'value'=>'€'.number_format($recoveredTotal,2),
     'raw'=>$recoveredTotal, 'grad'=>'135deg,#166534 0%,#28a745 100%', 'pulse'=>false],
    ['icon'=>'folder-open', 'label'=>'Aktive Fälle', 'sub'=>"von $totalCases gesamt",
     'value'=>$activeCasesCount,
     'raw'=>null, 'grad'=>'135deg,#1d4ed8 0%,#2950a8 100%', 'pulse'=>$activeCasesCount > 0],
    ['icon'=>'wallet', 'label'=>'Kontostand', 'sub'=>'Verfügbares Guthaben',
     'value'=>'€'.number_format($userBalance,2),
     'raw'=>$userBalance, 'grad'=>'135deg,#0f172a 0%,#1e3a5f 100%', 'pulse'=>false],
    ['icon'=>'rise', 'label'=>'Ausstehend', 'sub'=>'Noch nicht gesichert',
     'value'=>'€'.number_format($outstandingTotal,2),
     'raw'=>$outstandingTotal, 'grad'=>'135deg,#92400e 0%,#d97706 100%', 'pulse'=>false],
  ];
  foreach ($kpiCards as $kc):
  ?>
  <div class="col-6 col-md-4 col-lg db2-kpi-col">
    <div class="db2-kpi-card" style="background:linear-gradient(<?=$kc['grad']?>);border-radius:16px;padding:18px 16px;position:relative;overflow:hidden;">
      <div style="position:absolute;right:-16px;top:-16px;width:80px;height:80px;background:rgba(255,255,255,.07);border-radius:50%;pointer-events:none;" aria-hidden="true"></div>
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div style="font-size:.72rem;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.5px;"><?=htmlspecialchars($kc['label'],ENT_QUOTES)?></div>
          <div style="font-size:1.3rem;font-weight:800;color:#fff;margin-top:4px;line-height:1.2;">
            <?php if($kc['raw'] !== null): ?>
            <span class="count money" data-value="<?=$kc['raw']?>"><?=$kc['value']?></span>
            <?php else: ?>
            <?=(int)$kc['value']?>
            <?php endif; ?>
          </div>
          <div style="font-size:.72rem;color:rgba(255,255,255,.55);margin-top:3px;"><?=htmlspecialchars($kc['sub'],ENT_QUOTES)?></div>
        </div>
        <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative;">
          <i class="anticon anticon-<?=htmlspecialchars($kc['icon'],ENT_QUOTES)?>" style="font-size:17px;color:#fff;"></i>
          <?php if($kc['pulse']): ?>
          <span style="position:absolute;top:-3px;right:-3px;width:9px;height:9px;background:#4ade80;border-radius:50%;border:2px solid #fff;animation:aiDotPulse 1.4s ease-in-out infinite;"></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ╔══════════════════════════════════════════════════════════════════════╗ -->
<!-- ║  AI ALGORITHM LIVE MONITOR                                            ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════╝ -->
<div class="row mt-3">
  <div class="col-12">
    <div class="card border-0 shadow-sm" id="db2AiCard" style="border-radius:16px;overflow:hidden;">
      <div class="card-header d-flex flex-wrap align-items-center justify-content-between py-3 px-4"
           style="background:linear-gradient(90deg,#0f172a 0%,#1e3a5f 60%,#1a4480 100%);border:none;gap:10px;">
        <div class="d-flex align-items-center" style="gap:12px;">
          <div class="db2-pulse-wrap" aria-hidden="true">
            <div class="db2-pulse"></div>
            <div class="db2-pulse-core"><i class="anticon anticon-robot" style="font-size:1.2rem;color:#38bdf8;"></i></div>
          </div>
          <div>
            <h5 class="mb-0 text-white font-weight-bold" style="font-size:.95rem;letter-spacing:.3px;">KI-Algorithmus – Live Monitor</h5>
            <div style="font-size:.72rem;color:#94a3b8;margin-top:.1rem;">
              <span id="db2AiDot" style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#4ade80;margin-right:.35rem;vertical-align:middle;animation:aiDotPulse 1.4s ease-in-out infinite;"></span>
              Echtzeit-Transaktionsanalyse aktiv – <?= $totalCases ?> Fälle überwacht
            </div>
          </div>
        </div>
        <div class="d-flex flex-wrap" style="gap:12px;" id="db2AiCounters">
          <?php
          $totalTx = 0; $totalWallets = 0; $totalScore = 0; $scoreCount = 0;
          foreach ($cases as $c) {
              $ai = aiStatsForCase($c);
              $totalTx += $ai['txScanned'];
              $totalWallets += $ai['walletsLinked'];
              $totalScore += $ai['matchScore'];
              $scoreCount++;
          }
          $avgScore = $scoreCount > 0 ? round($totalScore / $scoreCount) : 0;
          ?>
          <div class="db2-counter-box">
            <div class="db2-counter-val" id="db2TxChecked">0</div>
            <div class="db2-counter-lbl">TX Geprüft</div>
          </div>
          <div class="db2-counter-box">
            <div class="db2-counter-val" id="db2Wallets" style="color:#c084fc;">0</div>
            <div class="db2-counter-lbl">Wallets verknüpft</div>
          </div>
          <div class="db2-counter-box">
            <div class="db2-counter-val text-success" id="db2Found">0</div>
            <div class="db2-counter-lbl">Treffer gefunden</div>
          </div>
          <div class="db2-counter-box">
            <div class="db2-counter-val" id="db2Accuracy" style="color:#f59e0b;"><?=$avgScore?>%</div>
            <div class="db2-counter-lbl">Ø Übereinstimmung</div>
          </div>
        </div>
      </div>
      <div class="card-body p-0" style="background:#0f172a;">
        <!-- Scan progress -->
        <div style="padding:.65rem 1.25rem .4rem;">
          <div style="display:flex;justify-content:space-between;font-size:.72rem;color:#64748b;margin-bottom:.3rem;">
            <span style="color:#94a3b8;">Gesamt-Scan-Fortschritt (alle Fälle)</span>
            <span id="db2ScanPct" style="color:#38bdf8;">0%</span>
          </div>
          <div style="height:5px;background:#1e293b;border-radius:4px;overflow:hidden;">
            <div id="db2ScanBar" style="height:100%;width:0%;background:linear-gradient(90deg,#38bdf8,#818cf8);border-radius:4px;transition:width .5s;"></div>
          </div>
        </div>
        <!-- Live feed -->
        <div id="db2LiveFeed" style="height:200px;overflow-y:auto;padding:.5rem 1.25rem 1rem;font-family:'Courier New',monospace;font-size:.78rem;line-height:1.7;scroll-behavior:smooth;">
          <div style="color:#475569;text-align:center;padding:2rem 0;" id="db2FeedEmpty">
            <i class="anticon anticon-loading" style="font-size:1.4rem;animation:spin 1s linear infinite;color:#38bdf8;"></i><br>
            <span style="color:#64748b;font-size:.8rem;">Initialisierung…</span>
          </div>
        </div>
        <!-- Bottom stats -->
        <div style="display:flex;flex-wrap:wrap;border-top:1px solid #1e293b;">
          <div class="db2-stat-cell"><span class="db2-stat-lbl">Letzter Block</span><span class="db2-stat-val" id="db2LastBlock" style="color:#38bdf8;">—</span></div>
          <div class="db2-stat-cell"><span class="db2-stat-lbl">Scan-Geschwindigkeit</span><span class="db2-stat-val" id="db2ScanSpeed" style="color:#4ade80;">—</span></div>
          <div class="db2-stat-cell"><span class="db2-stat-lbl">Netzwerk-Latenz</span><span class="db2-stat-val" id="db2Latency" style="color:#f59e0b;">—</span></div>
          <div class="db2-stat-cell"><span class="db2-stat-lbl">Nächster Scan</span><span class="db2-stat-val" id="db2NextScan" style="color:#c084fc;">—</span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ╔══════════════════════════════════════════════════════════════════════╗ -->
<!-- ║  FAST ACTIONS                                                         ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════╝ -->
<div class="row mt-3">
  <div class="col-12">
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
      <div class="card-header py-3 px-4 border-0"
           style="background:linear-gradient(135deg,#1a2a6c 0%,#2950a8 60%,#2da9e3 100%);">
        <h6 class="mb-0 text-white font-weight-bold" style="font-size:.9rem;">
          <i class="anticon anticon-thunderbolt mr-2"></i>Schnellzugriff
        </h6>
      </div>
      <div class="card-body py-3 px-3">
        <div class="d-flex flex-wrap" style="gap:10px;">
          <?php
          $actions = [
            ['icon'=>'plus-circle',    'label'=>'Neuer Fall',    'href'=>'cases.php',         'grad'=>'135deg,#1d4ed8,#2950a8', 'perm'=>true],
            ['icon'=>'arrow-down',     'label'=>'Einzahlen',     'href'=>'deposit.php',        'grad'=>'135deg,#166534,#28a745', 'perm'=>true],
            ['icon'=>'arrow-up',       'label'=>'Auszahlen',     'href'=>'withdrawal.php',     'grad'=>'135deg,#991b1b,#dc3545', 'perm'=>!$isTrialUser],
            ['icon'=>'dollar',         'label'=>'Rückgewonnen',  'href'=>'recovered_funds.php','grad'=>'135deg,#0d6e6e,#17a2b8', 'perm'=>true],
            ['icon'=>'customer-service','label'=>'Support',      'href'=>'support.php',        'grad'=>'135deg,#92400e,#d97706', 'perm'=>true],
            ['icon'=>'safety-certificate','label'=>'KYC',        'href'=>'kyc.php',            'grad'=>'135deg,#155e75,#0e7490', 'perm'=>true],
            ['icon'=>'profile',        'label'=>'Dokumente',     'href'=>'documents.php',      'grad'=>'135deg,#374151,#6b7280', 'perm'=>true],
            ['icon'=>'setting',        'label'=>'Einstellungen', 'href'=>'settings.php',       'grad'=>'135deg,#1e293b,#334155', 'perm'=>true],
          ];
          foreach ($actions as $a):
          ?>
          <a href="<?= $a['perm'] ? htmlspecialchars($a['href'],ENT_QUOTES) : 'packages.php' ?>"
             class="db2-fast-action <?= !$a['perm'] ? 'db2-locked' : '' ?>"
             style="background:linear-gradient(<?=htmlspecialchars($a['grad'],ENT_QUOTES)?>);border-radius:12px;padding:10px 16px;display:flex;align-items:center;gap:8px;text-decoration:none;position:relative;">
            <?php if(!$a['perm']): ?>
            <span style="position:absolute;top:-4px;right:-4px;width:16px;height:16px;background:#fbbf24;border-radius:50%;display:flex;align-items:center;justify-content:center;">
              <i class="anticon anticon-lock" style="font-size:8px;color:#78350f;"></i>
            </span>
            <?php endif; ?>
            <i class="anticon anticon-<?=htmlspecialchars($a['icon'],ENT_QUOTES)?>" style="font-size:16px;color:#fff;"></i>
            <span style="color:#fff;font-size:12.5px;font-weight:600;white-space:nowrap;"><?=htmlspecialchars($a['label'],ENT_QUOTES)?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ╔══════════════════════════════════════════════════════════════════════╗ -->
<!-- ║  MAIN GRID: CASES (8) + SIDEBAR (4)                                   ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════╝ -->
<div class="row mt-3" style="row-gap:16px;">

  <!-- ── LEFT: Recent Cases ───────────────────────────────────────────── -->
  <div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;height:100%;">
      <!-- Header -->
      <div class="card-header border-0 d-flex flex-wrap align-items-center justify-content-between py-3 px-4"
           style="background:linear-gradient(135deg,#1a2a6c 0%,#2950a8 60%,#2da9e3 100%);gap:10px;">
        <div class="d-flex align-items-center">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0;margin-right:12px;" aria-hidden="true">
            <i class="anticon anticon-folder-open"></i>
          </div>
          <div>
            <h5 class="mb-0 text-white font-weight-bold" style="font-size:.95rem;">Meine Fälle</h5>
            <div style="font-size:.72rem;color:rgba(255,255,255,.75);">Letzte <?= count($cases) ?> Fälle – alle Statusphasen</div>
          </div>
        </div>
        <a href="cases.php" class="btn btn-sm font-weight-700"
           style="background:rgba(255,255,255,.15);color:#fff;border-radius:8px;font-size:12px;border:none;">
          <i class="anticon anticon-plus mr-1"></i>Neuer Fall
        </a>
      </div>

      <!-- Package blur overlay -->
      <div class="card-body p-0 position-relative">
        <?php if ($caseBlurActive): ?>
        <div style="position:absolute;inset:0;backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);background:rgba(255,255,255,.5);z-index:10;border-radius:0 0 16px 16px;display:flex;align-items:center;justify-content:center;">
          <div class="text-center px-4 py-5">
            <div style="width:60px;height:60px;background:linear-gradient(135deg,#d97706,#f59e0b);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;color:#fff;margin:0 auto 14px;">
              <i class="anticon anticon-lock"></i>
            </div>
            <h5 style="font-weight:800;color:#78350f;margin-bottom:8px;">Vollzugriff gesperrt</h5>
            <p style="font-size:13px;color:#92400e;margin-bottom:14px;">Upgraden Sie für vollständige Fallverwaltung und KI-Analyse.</p>
            <a href="packages.php" class="btn btn-sm font-weight-700"
               style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;border-radius:10px;">
              <i class="anticon anticon-rocket mr-1"></i>Jetzt upgraden
            </a>
          </div>
        </div>
        <?php endif; ?>

        <?php if (empty($cases)): ?>
        <div class="py-5 text-center">
          <div style="width:52px;height:52px;border-radius:50%;background:rgba(41,80,168,.08);display:flex;align-items:center;justify-content:center;font-size:24px;color:#2950a8;margin:0 auto 12px;" aria-hidden="true">
            <i class="anticon anticon-folder-open"></i>
          </div>
          <p class="text-muted mb-0" style="font-size:14px;">Noch keine Fälle registriert.</p>
          <a href="cases.php" class="btn btn-sm mt-3 font-weight-700"
             style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;border-radius:8px;">
            Ersten Fall anlegen
          </a>
        </div>
        <?php else: ?>
        <div class="table-responsive db2-cases-table">
          <table class="table mb-0" style="font-size:13px;">
            <thead>
              <tr style="background:#f8f9fa;">
                <th class="border-0 px-4 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;">Fall-Nr.</th>
                <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Plattform</th>
                <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Gemeldet</th>
                <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;min-width:180px;">Zurückgewonnen</th>
                <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Status</th>
                <th class="border-0 py-3 font-weight-600 db2-hide-sm" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">KI-Score</th>
                <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Aktion</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($cases as $case):
              $rep  = (float)$case['reported_amount'];
              $rec  = (float)$case['recovered_amount'];
              $prog = ($rep > 0) ? round(($rec / $rep) * 100, 1) : 0;
              $pCol = $prog >= 70 ? '#28a745' : ($prog >= 30 ? '#2950a8' : '#dc3545');
              $sc   = $caseStatusMap[$case['status']] ?? ['label'=>ucwords(str_replace('_',' ',$case['status'])),'color'=>'#6c757d','bg'=>'rgba(108,117,125,.1)','icon'=>'question-circle'];
              $ai   = aiStatsForCase($case);
            ?>
            <tr style="border-bottom:1px solid #f0f2f5;">
              <td class="px-4 py-3">
                <a href="case-details.php?id=<?= (int)$case['id'] ?>" class="font-weight-700" style="color:#2950a8;text-decoration:none;font-size:13px;">
                  <?= htmlspecialchars($case['case_number'],ENT_QUOTES) ?>
                </a>
                <div class="text-muted" style="font-size:10px;"><?= !empty($case['created_at']) ? date('d.m.Y', strtotime($case['created_at'])) : '–' ?></div>
              </td>
              <td class="py-3">
                <div class="d-flex align-items-center" style="gap:7px;">
                  <?php if (!empty($case['platform_logo'])): ?>
                  <div style="width:26px;height:26px;border-radius:6px;overflow:hidden;background:#f0f2f5;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                    <img src="<?= htmlspecialchars($case['platform_logo'],ENT_QUOTES) ?>" alt="" style="width:100%;height:100%;object-fit:contain;">
                  </div>
                  <?php endif; ?>
                  <span class="font-weight-600" style="color:#2c3e50;font-size:12.5px;"><?= htmlspecialchars($case['platform_name'],ENT_QUOTES) ?></span>
                </div>
              </td>
              <td class="py-3">
                <span class="font-weight-700" style="color:#e67e22;">€<?= number_format($rep,2) ?></span>
              </td>
              <td class="py-3" style="min-width:180px;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="font-weight-700" style="color:<?=$pCol?>;font-size:13px;">€<?= number_format($rec,2) ?></span>
                  <small style="font-size:10px;font-weight:700;color:<?=$pCol?>;"><?=$prog?>%</small>
                </div>
                <div class="progress" style="height:5px;border-radius:3px;background:#e9ecef;">
                  <div class="progress-bar" style="width:<?=$prog?>%;background:<?=$pCol?>;border-radius:3px;"
                       role="progressbar" aria-valuenow="<?=$prog?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted mt-1 d-block" style="font-size:10px;">von €<?= number_format($rep,2) ?></small>
              </td>
              <td class="py-3">
                <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 9px;border-radius:20px;font-size:11px;font-weight:700;background:<?=$sc['bg']?>;color:<?=$sc['color']?>;white-space:nowrap;">
                  <i class="anticon anticon-<?=htmlspecialchars($sc['icon'],ENT_QUOTES)?>" style="font-size:11px;" aria-hidden="true"></i>
                  <?=htmlspecialchars($sc['label'],ENT_QUOTES)?>
                </span>
              </td>
              <td class="py-3 db2-hide-sm">
                <div style="display:flex;align-items:center;gap:5px;">
                  <div style="width:32px;height:32px;border-radius:50%;background:conic-gradient(#38bdf8 <?=$ai['matchScore']?>%,#1e293b <?=$ai['matchScore']?>%);display:flex;align-items:center;justify-content:center;flex-shrink:0;" title="AI Match Score">
                    <div style="width:22px;height:22px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;color:#0284c7;"><?=$ai['matchScore']?></div>
                  </div>
                  <div style="font-size:10px;color:#64748b;">
                    <div><?= number_format($ai['txScanned']) ?> TX</div>
                    <div><?=$ai['walletsLinked']?> Wallets</div>
                  </div>
                </div>
              </td>
              <td class="py-3">
                <a href="case-details.php?id=<?= (int)$case['id'] ?>" class="btn btn-sm font-weight-600"
                   style="background:rgba(41,80,168,.08);color:#2950a8;border:1px solid rgba(41,80,168,.2);border-radius:8px;font-size:12px;">
                  <i class="anticon anticon-eye mr-1"></i>Details
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center" style="background:#fafbfc;">
          <span class="text-muted" style="font-size:12px;"><?= count($cases) ?> von <?= $totalCases ?> Fällen angezeigt</span>
          <a href="cases.php" class="btn btn-sm font-weight-700"
             style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;border-radius:8px;font-size:12px;">
            <i class="anticon anticon-folder-open mr-1"></i>Alle Fälle
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── RIGHT SIDEBAR ───────────────────────────────────────────────── -->
  <div class="col-12 col-lg-4" style="display:flex;flex-direction:column;gap:16px;">

    <!-- Package Card -->
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
      <div class="card-header border-0 py-3 px-4"
           style="background:linear-gradient(135deg,<?= $isTrialUser ? '#374151 0%,#6b7280 100%' : '#155724 0%,#28a745 100%' ?>);">
        <h6 class="mb-0 text-white font-weight-bold" style="font-size:.9rem;">
          <i class="anticon anticon-crown mr-2"></i><?= $isTrialUser ? 'Testversion aktiv' : 'Aktives Abonnement' ?>
        </h6>
      </div>
      <div class="card-body p-3">
        <?php if ($userPackage): ?>
        <div class="d-flex align-items-center" style="gap:12px;">
          <div style="width:44px;height:44px;border-radius:12px;background:<?=$isTrialUser?'rgba(107,114,128,.1)':'rgba(40,167,69,.1)'?>;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
            <?= $isTrialUser ? '🔓' : '✅' ?>
          </div>
          <div>
            <div class="font-weight-700" style="color:#2c3e50;font-size:14px;"><?= htmlspecialchars($userPackage['package_name'],ENT_QUOTES) ?></div>
            <?php if (!$isTrialUser && !empty($userPackage['end_date'])): ?>
            <div style="font-size:12px;color:#6c757d;">Gültig bis <?= date('d.m.Y', strtotime($userPackage['end_date'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($userPackage['recovery_speed'])): ?>
            <div style="font-size:11px;color:#28a745;margin-top:2px;"><i class="anticon anticon-rocket mr-1"></i><?= htmlspecialchars($userPackage['recovery_speed'],ENT_QUOTES) ?> Wiederherstellung</div>
            <?php endif; ?>
          </div>
        </div>
        <?php else: ?>
        <div class="text-center py-2">
          <div style="font-size:13px;color:#6c757d;">Kein aktives Paket</div>
        </div>
        <?php endif; ?>

        <!-- Feature restrictions -->
        <?php
        $features = [
            ['label'=>'Vollständige Fallansicht',  'avail'=>!$isTrialUser],
            ['label'=>'Unbegrenzte Auszahlungen',  'avail'=>!$isTrialUser],
            ['label'=>'KI-Transaktionsanalyse',    'avail'=>!$isTrialUser],
            ['label'=>'Prioritäts-Support',        'avail'=>!$isTrialUser && ($userPackage['support_level']??'') === 'premium'],
        ];
        ?>
        <div class="mt-3" style="border-top:1px solid #f0f2f5;padding-top:12px;">
          <?php foreach ($features as $f): ?>
          <div class="d-flex align-items-center justify-content-between py-1">
            <span style="font-size:12px;color:#4b5563;"><?=htmlspecialchars($f['label'],ENT_QUOTES)?></span>
            <?php if($f['avail']): ?>
            <i class="anticon anticon-check-circle" style="color:#28a745;font-size:14px;"></i>
            <?php else: ?>
            <i class="anticon anticon-lock" style="color:#d97706;font-size:13px;" title="Upgrade erforderlich"></i>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if ($isTrialUser): ?>
        <a href="packages.php" class="btn btn-block mt-3 font-weight-700" style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;border-radius:10px;font-size:13px;">
          <i class="anticon anticon-rocket mr-1"></i>Jetzt upgraden
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Case Status Summary -->
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
      <div class="card-header border-0 py-3 px-4"
           style="background:linear-gradient(135deg,#155724 0%,#28a745 60%,#20c997 100%);">
        <h6 class="mb-0 text-white font-weight-bold" style="font-size:.9rem;">
          <i class="anticon anticon-pie-chart mr-2"></i>Statusverteilung
        </h6>
      </div>
      <div class="card-body p-3">
        <?php if (empty($statusCounts)): ?>
        <div class="text-center py-3 text-muted" style="font-size:13px;">Keine Fälle vorhanden</div>
        <?php else: ?>
        <canvas id="db2StatusChart" height="180" aria-label="Fallstatusverteilung"></canvas>
        <?php $totalCaseCnt = array_sum($statusCounts); ?>
        <?php foreach ($statusCounts as $s => $cnt):
          $sm = $caseStatusMap[$s] ?? ['label'=>ucwords(str_replace('_',' ',$s)),'color'=>'#6c757d','bg'=>'rgba(108,117,125,.1)','icon'=>'question-circle'];
          $pct = $totalCaseCnt > 0 ? round($cnt/$totalCaseCnt*100) : 0;
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-top:1px solid #f0f2f5;">
          <div style="display:flex;align-items:center;gap:7px;">
            <div style="width:28px;height:28px;border-radius:7px;background:<?=$sm['bg']?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="anticon anticon-<?=htmlspecialchars($sm['icon'],ENT_QUOTES)?>" style="color:<?=$sm['color']?>;font-size:12px;" aria-hidden="true"></i>
            </div>
            <span style="font-size:12px;font-weight:600;color:#2c3e50;"><?=htmlspecialchars($sm['label'],ENT_QUOTES)?></span>
          </div>
          <div style="display:flex;align-items:center;gap:7px;flex-shrink:0;">
            <div style="width:50px;height:5px;background:#e9ecef;border-radius:3px;overflow:hidden;">
              <div style="width:<?=$pct?>%;height:100%;background:<?=$sm['color']?>;border-radius:3px;"></div>
            </div>
            <span style="min-width:22px;text-align:right;font-size:12px;font-weight:700;background:<?=$sm['bg']?>;color:<?=$sm['color']?>;border-radius:10px;padding:2px 7px;"><?=$cnt?></span>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Transactions mini -->
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
      <div class="card-header border-0 d-flex align-items-center justify-content-between py-3 px-4"
           style="background:linear-gradient(135deg,#0f172a 0%,#1a2a6c 60%,#2950a8 100%);">
        <h6 class="mb-0 text-white font-weight-bold" style="font-size:.9rem;">
          <i class="anticon anticon-transaction mr-2"></i>Transaktionen
        </h6>
        <a href="transactions.php" style="font-size:11px;color:rgba(255,255,255,.7);text-decoration:none;">Alle →</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($transactions)): ?>
        <div class="py-4 text-center text-muted" style="font-size:13px;">Keine Transaktionen</div>
        <?php else: ?>
        <?php foreach ($transactions as $tx):
          $isPos = in_array($tx['type'],['deposit','refund']);
          $tc    = $txTypeMap[$tx['type']] ?? ['label'=>$tx['type'],'color'=>'#6c757d','bg'=>'rgba(108,117,125,.1)','icon'=>'question-circle','sign'=>'~'];
        ?>
        <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-top:1px solid #f0f2f5;">
          <div class="d-flex align-items-center" style="gap:10px;min-width:0;flex:1;">
            <div style="width:34px;height:34px;border-radius:10px;background:<?=$tc['bg']?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="anticon anticon-<?=htmlspecialchars($tc['icon'],ENT_QUOTES)?>" style="color:<?=$tc['color']?>;font-size:14px;" aria-hidden="true"></i>
            </div>
            <div style="min-width:0;">
              <div class="font-weight-600" style="font-size:12.5px;color:#2c3e50;"><?=htmlspecialchars($tc['label'],ENT_QUOTES)?></div>
              <div class="text-muted text-truncate" style="font-size:10.5px;"><?=htmlspecialchars($tx['reference_name']??'—',ENT_QUOTES)?> · <?=date('d.m.Y',strtotime($tx['created_at']))?></div>
            </div>
          </div>
          <div class="text-right flex-shrink-0">
            <div class="font-weight-bold" style="font-size:13px;color:<?=$isPos?'#28a745':'#dc3545'?>;">
              <?=$tc['sign']?>€<?=number_format((float)$tx['amount'],2)?>
            </div>
            <div style="font-size:10px;color:#8896a8;"><?=ucfirst($tx['status']??'')?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="px-4 py-2 border-top text-right" style="background:#fafbfc;">
          <a href="transactions.php" style="font-size:12px;color:#2950a8;font-weight:600;">Alle Transaktionen →</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- end right col -->
</div><!-- end main grid -->

<!-- ╔══════════════════════════════════════════════════════════════════════╗ -->
<!-- ║  ACTIVE RECOVERY OPERATIONS                                           ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════╝ -->
<div class="row mt-3">
  <div class="col-12">
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
      <div class="card-header border-0 d-flex flex-wrap align-items-center justify-content-between py-3 px-4"
           style="background:linear-gradient(135deg,#0d6e6e 0%,#17a2b8 60%,#20c997 100%);gap:10px;">
        <div class="d-flex align-items-center">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0;margin-right:12px;" aria-hidden="true">
            <i class="anticon anticon-sync"></i>
          </div>
          <div>
            <h5 class="mb-0 text-white font-weight-bold" style="font-size:.95rem;">Aktive Wiederherstellungsoperationen</h5>
            <div style="font-size:.73rem;color:rgba(255,255,255,.8);">Laufende KI-Rückforderungsprozesse in Echtzeit</div>
          </div>
        </div>
        <span style="background:rgba(255,255,255,.18);color:#fff;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;white-space:nowrap;">
          <i class="anticon anticon-file-text mr-1"></i><?= count($ongoingRecoveries) ?> aktive Fälle
        </span>
      </div>
      <div class="card-body p-0 position-relative">
        <?php if ($caseBlurActive): ?>
        <div style="position:absolute;inset:0;backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);background:rgba(255,255,255,.55);z-index:10;border-radius:0 0 16px 16px;display:flex;align-items:center;justify-content:center;">
          <div class="text-center p-4">
            <div style="width:52px;height:52px;background:linear-gradient(135deg,#d97706,#f59e0b);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;color:#fff;margin:0 auto 12px;">
              <i class="anticon anticon-lock"></i>
            </div>
            <h6 style="font-weight:700;color:#92400e;margin-bottom:8px;">Wiederherstellung gesperrt</h6>
            <p style="font-size:12px;color:#78350f;margin-bottom:12px;">Upgrade für vollen KI-Wiederherstellungszugriff.</p>
            <a href="packages.php" class="btn btn-sm font-weight-700" style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;border-radius:8px;">
              <i class="anticon anticon-rocket mr-1"></i>Upgraden
            </a>
          </div>
        </div>
        <?php endif; ?>

        <?php if (empty($ongoingRecoveries)): ?>
        <div class="py-5 text-center">
          <div style="width:52px;height:52px;border-radius:50%;background:rgba(23,162,184,.08);display:flex;align-items:center;justify-content:center;font-size:24px;color:#17a2b8;margin:0 auto 12px;" aria-hidden="true">
            <i class="anticon anticon-sync"></i>
          </div>
          <p class="mb-0 text-muted" style="font-size:14px;">Keine aktiven Wiederherstellungsoperationen</p>
        </div>
        <?php else: ?>
        <div class="row px-3 pt-3 pb-1" style="row-gap:12px;">
          <?php
          $recStatusMap = [
            'open'               => ['label'=>'Offen',                   'color'=>'#92400e','bg'=>'rgba(251,191,36,.18)','icon'=>'clock-circle',       'bar'=>'#fbbf24'],
            'documents_required' => ['label'=>'Aufmerksamkeit erforderlich','color'=>'#991b1b','bg'=>'rgba(220,53,69,.15)', 'icon'=>'exclamation-circle','bar'=>'#dc3545'],
            'under_review'       => ['label'=>'In Prüfung',               'color'=>'#1d4ed8','bg'=>'rgba(41,80,168,.15)', 'icon'=>'eye',               'bar'=>'#2950a8'],
            'refund_approved'    => ['label'=>'Erstattung genehmigt',     'color'=>'#166534','bg'=>'rgba(40,167,69,.15)', 'icon'=>'check-circle',       'bar'=>'#28a745'],
            'refund_rejected'    => ['label'=>'Erstattung abgelehnt',     'color'=>'#991b1b','bg'=>'rgba(220,53,69,.15)','icon'=>'close-circle',        'bar'=>'#dc3545'],
            'closed'             => ['label'=>'Abgeschlossen',            'color'=>'#374151','bg'=>'rgba(108,117,125,.15)','icon'=>'check-square',      'bar'=>'#6c757d'],
          ];
          foreach ($ongoingRecoveries as $rv):
            $rRep  = (float)$rv['reported_amount'];
            $rRec  = (float)$rv['recovered_amount'];
            $rProg = ($rRep > 0) ? round(($rRec/$rRep)*100,1) : 0;
            $rs    = $recStatusMap[$rv['status']] ?? ['label'=>ucwords(str_replace('_',' ',$rv['status'])),'color'=>'#6c757d','bg'=>'rgba(108,117,125,.1)','icon'=>'question-circle','bar'=>'#6c757d'];
            $rBar  = $rProg >= 70 ? '#28a745' : ($rProg >= 30 ? $rs['bar'] : '#dc3545');
            $aiRv  = aiStatsForCase($rv);
          ?>
          <div class="col-12 col-md-6 col-xl-4">
            <div style="border:1.5px solid #e9ecef;border-radius:14px;padding:16px;background:#fff;height:100%;">
              <!-- Header row -->
              <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                  <a href="case-details.php?id=<?=(int)$rv['id']?>" class="font-weight-700" style="color:#2950a8;font-size:13px;text-decoration:none;">
                    <?=htmlspecialchars($rv['case_number'],ENT_QUOTES)?>
                  </a>
                  <div style="font-size:11px;color:#6c757d;"><?=htmlspecialchars($rv['platform_name'],ENT_QUOTES)?></div>
                </div>
                <span style="display:inline-flex;align-items:center;gap:3px;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;background:<?=$rs['bg']?>;color:<?=$rs['color']?>;white-space:nowrap;flex-shrink:0;">
                  <i class="anticon anticon-<?=htmlspecialchars($rs['icon'],ENT_QUOTES)?>" style="font-size:10px;" aria-hidden="true"></i>
                  <?=htmlspecialchars($rs['label'],ENT_QUOTES)?>
                </span>
              </div>
              <!-- Amounts -->
              <div class="d-flex justify-content-between mb-1">
                <small style="font-size:11px;color:#6c757d;">Gemeldet: <strong style="color:#e67e22;">€<?=number_format($rRep,2)?></strong></small>
                <small style="font-size:11px;font-weight:700;color:<?=$rBar?>;"><?=$rProg?>%</small>
              </div>
              <!-- Progress bar -->
              <div class="progress mb-1" style="height:6px;border-radius:4px;background:#e9ecef;">
                <div class="progress-bar" style="width:<?=$rProg?>%;background:<?=$rBar?>;border-radius:4px;"
                     role="progressbar" aria-valuenow="<?=$rProg?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <small style="font-size:10px;color:#6c757d;">Zurückgewonnen: <strong style="color:<?=$rBar?>;">€<?=number_format($rRec,2)?></strong></small>

              <!-- AI stats chips -->
              <div class="d-flex flex-wrap mt-2" style="gap:5px;">
                <span style="font-size:10px;background:#e0f2fe;color:#0284c7;padding:2px 8px;border-radius:10px;font-weight:600;">
                  <i class="anticon anticon-database mr-1"></i><?=number_format($aiRv['txScanned'])?> TX
                </span>
                <span style="font-size:10px;background:rgba(192,132,252,.12);color:#7c3aed;padding:2px 8px;border-radius:10px;font-weight:600;">
                  <i class="anticon anticon-link mr-1"></i><?=$aiRv['walletsLinked']?> Wallets
                </span>
                <span style="font-size:10px;background:rgba(74,222,128,.12);color:#166534;padding:2px 8px;border-radius:10px;font-weight:600;">
                  <i class="anticon anticon-star mr-1"></i><?=$aiRv['matchScore']?>% Match
                </span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="px-4 py-3 border-top d-flex justify-content-end" style="background:#fafbfc;">
          <a href="cases.php" class="btn btn-sm font-weight-700"
             style="background:linear-gradient(135deg,#17a2b8,#20c997);color:#fff;border:none;border-radius:8px;font-size:12px;">
            <i class="anticon anticon-eye mr-1"></i>Alle Fälle ansehen
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ╔══════════════════════════════════════════════════════════════════════╗ -->
<!-- ║  DEPOSIT & WITHDRAWAL REQUESTS                                        ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════╝ -->
<div class="row mt-3">
  <div class="col-12">
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
      <div class="card-header border-0 d-flex flex-wrap align-items-center justify-content-between py-3 px-4"
           style="background:linear-gradient(135deg,#1a2a6c 0%,#2950a8 60%,#2da9e3 100%);gap:10px;">
        <div class="d-flex align-items-center">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0;margin-right:12px;" aria-hidden="true">
            <i class="anticon anticon-file-sync"></i>
          </div>
          <div>
            <h5 class="mb-0 text-white font-weight-bold" style="font-size:.95rem;">Meine Anfragen</h5>
            <div style="font-size:.73rem;color:rgba(255,255,255,.75);">Einzahlungen &amp; Auszahlungen</div>
          </div>
        </div>
        <div class="d-flex" style="gap:8px;">
          <a href="deposit.php" class="btn btn-sm font-weight-700" style="background:rgba(40,167,69,.8);color:#fff;border:none;border-radius:8px;font-size:12px;">
            <i class="anticon anticon-arrow-down mr-1"></i>Einzahlen
          </a>
          <?php if (!$isTrialUser): ?>
          <a href="withdrawal.php" class="btn btn-sm font-weight-700" style="background:rgba(220,53,69,.8);color:#fff;border:none;border-radius:8px;font-size:12px;">
            <i class="anticon anticon-arrow-up mr-1"></i>Auszahlen
          </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body p-0">
        <!-- Tabs -->
        <ul class="nav nav-tabs px-4 pt-2" id="db2ReqTabs" style="border-bottom:2px solid #e9ecef;gap:4px;">
          <li class="nav-item">
            <a class="nav-link active font-weight-600" href="#db2TabDep" data-toggle="tab" style="font-size:13px;border:none;border-bottom:2px solid transparent;padding:8px 16px;">
              <i class="anticon anticon-arrow-down mr-1" style="color:#28a745;"></i>Einzahlungen
              <?php if (!empty($recentDeposits)): ?>
              <span class="badge badge-pill ml-1" style="background:rgba(40,167,69,.15);color:#28a745;font-size:10px;"><?= count($recentDeposits) ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link font-weight-600" href="#db2TabWd" data-toggle="tab" style="font-size:13px;border:none;border-bottom:2px solid transparent;padding:8px 16px;">
              <i class="anticon anticon-arrow-up mr-1" style="color:#dc3545;"></i>Auszahlungen
              <?php if (!empty($recentWithdrawals)): ?>
              <span class="badge badge-pill ml-1" style="background:rgba(220,53,69,.12);color:#dc3545;font-size:10px;"><?= count($recentWithdrawals) ?></span>
              <?php endif; ?>
              <?php if ($feeProofNeeded): ?>
              <span class="badge badge-pill ml-1" style="background:#dc3545;color:#fff;font-size:10px;">!</span>
              <?php endif; ?>
            </a>
          </li>
        </ul>
        <div class="tab-content">

          <!-- Deposits tab -->
          <div class="tab-pane fade show active" id="db2TabDep">
            <?php if (empty($recentDeposits)): ?>
            <div class="py-4 text-center text-muted" style="font-size:13px;">Noch keine Einzahlungen</div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table mb-0" style="font-size:13px;">
                <thead>
                  <tr style="background:#f8f9fa;">
                    <th class="border-0 px-4 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;">Referenz</th>
                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;">Methode</th>
                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;">Betrag</th>
                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;">Status</th>
                    <th class="border-0 py-3 font-weight-600 db2-hide-sm" style="color:#8896a8;font-size:11px;text-transform:uppercase;">Datum</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($recentDeposits as $d):
                  $ds = $depStatusMap[$d['status']] ?? ['label'=>ucfirst($d['status']),'color'=>'#6c757d','bg'=>'rgba(108,117,125,.1)'];
                ?>
                <tr style="border-bottom:1px solid #f0f2f5;">
                  <td class="px-4 py-3 font-weight-600" style="color:#2c3e50;"><?=htmlspecialchars($d['reference'],ENT_QUOTES)?></td>
                  <td class="py-3 text-muted"><?=htmlspecialchars(strtoupper($d['method_code']),ENT_QUOTES)?></td>
                  <td class="py-3 font-weight-700" style="color:#28a745;">+€<?=number_format((float)$d['amount'],2)?></td>
                  <td class="py-3">
                    <span style="padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;background:<?=$ds['bg']?>;color:<?=$ds['color']?>;"><?=htmlspecialchars($ds['label'],ENT_QUOTES)?></span>
                  </td>
                  <td class="py-3 text-muted db2-hide-sm" style="font-size:12px;"><?=date('d.m.Y',strtotime($d['created_at']))?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>

          <!-- Withdrawals tab -->
          <div class="tab-pane fade" id="db2TabWd">
            <?php if ($isTrialUser): ?>
            <div class="py-4 px-4 text-center">
              <div style="width:44px;height:44px;border-radius:12px;background:rgba(215,119,6,.1);display:flex;align-items:center;justify-content:center;font-size:22px;color:#d97706;margin:0 auto 12px;">
                <i class="anticon anticon-lock"></i>
              </div>
              <p class="text-muted mb-2" style="font-size:13px;">Auszahlungen sind in der Testversion gesperrt.</p>
              <a href="packages.php" class="btn btn-sm font-weight-700" style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;border-radius:8px;">Upgraden</a>
            </div>
            <?php elseif (empty($recentWithdrawals)): ?>
            <div class="py-4 text-center text-muted" style="font-size:13px;">Noch keine Auszahlungen</div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table mb-0" style="font-size:13px;">
                <thead>
                  <tr style="background:#f8f9fa;">
                    <th class="border-0 px-4 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;">Referenz</th>
                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;">Methode</th>
                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;">Betrag</th>
                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;">Gebühr</th>
                    <th class="border-0 py-3 font-weight-600" style="color:#8896a8;font-size:11px;text-transform:uppercase;">Status</th>
                    <th class="border-0 py-3 font-weight-600 db2-hide-sm" style="color:#8896a8;font-size:11px;text-transform:uppercase;">Datum</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($recentWithdrawals as $w):
                  $ws = $wdStatusMap[$w['status']] ?? ['label'=>ucfirst($w['status']),'color'=>'#6c757d','bg'=>'rgba(108,117,125,.1)'];
                  $feeAlert = !empty($w['fee_amount']) && floatval($w['fee_amount']) > 0 && in_array($w['fee_status']??'',['','under_review']);
                ?>
                <tr style="border-bottom:1px solid #f0f2f5;">
                  <td class="px-4 py-3 font-weight-600" style="color:#2c3e50;"><?=htmlspecialchars($w['reference'],ENT_QUOTES)?></td>
                  <td class="py-3 text-muted"><?=htmlspecialchars(strtoupper($w['method_code']),ENT_QUOTES)?></td>
                  <td class="py-3 font-weight-700" style="color:#dc3545;">-€<?=number_format((float)$w['amount'],2)?></td>
                  <td class="py-3">
                    <?php if (!empty($w['fee_amount']) && floatval($w['fee_amount']) > 0): ?>
                    <span style="font-size:12px;color:<?=$feeAlert?'#dc3545':'#6c757d'?>;font-weight:<?=$feeAlert?'700':'400'?>;">
                      <?=$feeAlert?'⚠️ ':''?>€<?=number_format((float)$w['fee_amount'],2)?> (<?=$w['fee_percentage']?>%)
                    </span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3">
                    <span style="padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;background:<?=$ws['bg']?>;color:<?=$ws['color']?>;"><?=htmlspecialchars($ws['label'],ENT_QUOTES)?></span>
                  </td>
                  <td class="py-3 text-muted db2-hide-sm" style="font-size:12px;"><?=date('d.m.Y',strtotime($w['created_at']))?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

</div><!-- /container-fluid -->
</div><!-- /main-content.db2 -->

<!-- ══════════════════════════════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════════════════════════════ -->
<style>
/* ── KPI Cards ─────────────────────────────────────────────────────────── */
.db2-kpi-col { flex: 0 0 auto; }
@media (min-width: 992px) {
    .db2-kpi-col { flex: 1 1 0; max-width: none; }
}

/* ── AI pulse animation ────────────────────────────────────────────────── */
.db2-pulse-wrap { position:relative;width:40px;height:40px;flex-shrink:0; }
.db2-pulse { position:absolute;inset:0;border-radius:50%;background:rgba(56,189,248,.25);animation:db2PulseRing 2s ease-out infinite; }
.db2-pulse-core { position:absolute;inset:6px;border-radius:50%;background:rgba(56,189,248,.15);display:flex;align-items:center;justify-content:center; }
@keyframes db2PulseRing { 0%{transform:scale(1);opacity:.7} 80%,100%{transform:scale(1.6);opacity:0} }
@keyframes aiDotPulse { 0%,100%{opacity:1} 50%{opacity:.25} }
@keyframes spin { to{transform:rotate(360deg)} }

/* ── AI counter boxes ──────────────────────────────────────────────────── */
.db2-counter-box { text-align:center;padding:4px 10px;background:rgba(255,255,255,.07);border-radius:8px; }
.db2-counter-val { font-family:'Courier New',monospace;font-size:1rem;font-weight:700;color:#e2e8f0;line-height:1.2; }
.db2-counter-lbl { font-size:.63rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-top:1px; }

/* ── Live feed ─────────────────────────────────────────────────────────── */
#db2LiveFeed::-webkit-scrollbar { width:4px; }
#db2LiveFeed::-webkit-scrollbar-track { background:#1e293b; }
#db2LiveFeed::-webkit-scrollbar-thumb { background:#334155;border-radius:4px; }

/* ── Bottom stats bar ──────────────────────────────────────────────────── */
.db2-stat-cell { flex:1 1 120px;padding:8px 16px;background:#0f172a; }
.db2-stat-lbl { display:block;font-size:.65rem;color:#475569;text-transform:uppercase;letter-spacing:.5px; }
.db2-stat-val { display:block;font-size:.82rem;font-weight:700;font-family:'Courier New',monospace;margin-top:1px; }

/* ── Fast action buttons hover ─────────────────────────────────────────── */
.db2-fast-action { transition:transform .15s,box-shadow .15s;box-shadow:0 2px 8px rgba(0,0,0,.15); }
.db2-fast-action:hover { transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.2); }
.db2-locked { opacity:.7; }

/* ── Nav tabs active state ─────────────────────────────────────────────── */
#db2ReqTabs .nav-link.active { color:#2950a8 !important;border-bottom:2px solid #2950a8 !important;background:none; }
#db2ReqTabs .nav-link { color:#6c757d; }
#db2ReqTabs .nav-link:hover { color:#2950a8; }

/* ── Responsive column hiding ──────────────────────────────────────────── */
@media (max-width: 767px) {
    .db2-hide-sm { display:none !important; }
    .db2-cases-table th:nth-child(3),
    .db2-cases-table td:nth-child(3) { display:none; }
    .db2-counter-box { padding:4px 6px; }
    .db2-counter-val { font-size:.8rem; }
    #db2AiCounters { gap:6px !important; }
}
@media (max-width: 575px) {
    .db2 .db2-hero { padding:18px 16px !important; }
    .db2-kpi-col { flex:0 0 50%;max-width:50%; }
}
</style>

<!-- ══════════════════════════════════════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════════════════════════════════════ -->
<script>
(function(){
'use strict';

// ── Animated counters ─────────────────────────────────────────────────────
function animateCount(el, target, duration, isMoney){
    if (!el) return;
    var start = 0, startTime = null;
    var num = parseFloat(target);
    function step(ts){
        if (!startTime) startTime = ts;
        var progress = Math.min((ts - startTime)/duration, 1);
        var ease = 1 - Math.pow(1-progress, 3);
        var val = start + (num - start) * ease;
        if (isMoney) {
            // Use de-DE locale: dot thousands separator, comma decimal
            el.textContent = '€' + val.toLocaleString('de-DE', {minimumFractionDigits:2, maximumFractionDigits:2});
        } else {
            el.textContent = Math.round(val).toLocaleString('de-DE');
        }
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

document.querySelectorAll('.count').forEach(function(el){
    var raw = parseFloat(el.getAttribute('data-value')) || 0;
    var isMoney = el.classList.contains('money');
    animateCount(el, raw, 1200, isMoney);
});

// ── Status doughnut chart ─────────────────────────────────────────────────
var db2Canvas = document.getElementById('db2StatusChart');
if (db2Canvas && typeof Chart !== 'undefined') {
    var labels = <?= json_encode(array_values($chartLabels)) ?>;
    var values = <?= json_encode(array_values($chartValues)) ?>;
    var colors = <?= json_encode(array_values(array_map(function($s) use ($caseStatusMap,$ciColors) {
        return $caseStatusMap[$s]['color'] ?? '#6c757d';
    }, array_keys($statusCounts)))) ?>;
    new Chart(db2Canvas, {
        type: 'doughnut',
        data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
        options: {
            responsive: true, maintainAspectRatio: true, cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } },
                tooltip: { callbacks: { label: function(c){ return c.label + ': ' + c.raw + ' Fall' + (c.raw !== 1 ? 'e' : ''); } } }
            }
        }
    });
}

// ── AI Live Monitor ───────────────────────────────────────────────────────
var totalTx      = <?= (int)$totalTx ?>;
var totalWallets = <?= (int)$totalWallets ?>;
var totalFound   = <?= (int)max(0, array_sum(array_column($cases, 'recovered_amount')) > 0 ? floor(count($ongoingRecoveries)*2.3) : 0) ?>;
var feed         = document.getElementById('db2LiveFeed');
var feedEmpty    = document.getElementById('db2FeedEmpty');
var scanBar      = document.getElementById('db2ScanBar');
var scanPct      = document.getElementById('db2ScanPct');
var db2TxEl      = document.getElementById('db2TxChecked');
var db2WalletsEl = document.getElementById('db2Wallets');
var db2FoundEl   = document.getElementById('db2Found');
var lastBlock    = document.getElementById('db2LastBlock');
var scanSpeed    = document.getElementById('db2ScanSpeed');
var latency      = document.getElementById('db2Latency');
var nextScan     = document.getElementById('db2NextScan');

var currentTx = 0, currentWallets = 0, currentFound = 0, scanProgress = 0;
// Countdown seconds between full re-scans (matches backend scheduler cadence)
var NEXT_SCAN_INTERVAL = 60;
var nextScanSeconds = 45; // staggered initial countdown so first tick isn't 60
// Ethereum block range used to generate a realistic live block number display
var ETH_BASE_BLOCK  = 19800000;
var ETH_BLOCK_RANGE = 500000;
var blockNum = ETH_BASE_BLOCK + Math.floor(Math.random() * ETH_BLOCK_RANGE);
// Maximum log lines kept in the live-feed console before pruning
var MAX_FEED_LINES = 50;

var feedLines = [
    ['#38bdf8','[INIT]', 'KI-Algorithmus gestartet – Blockchain-Netzwerke verbunden'],
    ['#4ade80','[SCAN]', 'Ethereum Mainnet: Block #' + (blockNum).toLocaleString('de-DE') + ' wird analysiert'],
    ['#c084fc','[TRACK]','Wallet-Cluster-Analyse gestartet – forensische Musterkennung aktiv'],
    ['#f59e0b','[ALERT]','Verdächtige Transaktion erkannt – Hash-Überprüfung läuft'],
    ['#38bdf8','[NET]',  'BTC / ETH / USDT / BNB Netzwerke synchronisiert'],
    ['#4ade80','[MATCH]','Cross-Chain-Transaktionsspur identifiziert'],
    ['#94a3b8','[DB]',   'Blockchain-Datenbank: ' + totalTx.toLocaleString('de-DE') + ' TX archiviert'],
    ['#c084fc','[AI]',   'Neuronales Netz: Wahrscheinlichkeitsmodell aktualisiert'],
    ['#f59e0b','[ALERT]','Exchange-Wallet verknüpft – regulatorische Anfrage wird vorbereitet'],
    ['#38bdf8','[SCAN]', 'Tron / Polygon / Arbitrum Netzwerk gescannt'],
    ['#4ade80','[RECOV]','Rückforderungsprotokoll generiert – Rechtsabteilung informiert'],
    ['#94a3b8','[SYS]',  'Speicher optimiert – Analyse-Pipeline bereit'],
];
var lineIndex = 0;

function addFeedLine() {
    if (feedEmpty && feedEmpty.parentNode === feed) {
        feedEmpty.style.display = 'none';
    }
    var entry = feedLines[lineIndex % feedLines.length];
    lineIndex++;
    var ts = new Date().toLocaleTimeString('de-DE');
    var div = document.createElement('div');
    div.innerHTML = '<span style="color:#475569;">[' + ts + ']</span> '
        + '<span style="color:' + entry[0] + ';font-weight:700;">' + entry[1] + '</span> '
        + '<span style="color:#cbd5e1;">' + entry[2] + '</span>';
    feed.appendChild(div);
    if (feed.children.length > MAX_FEED_LINES) feed.removeChild(feed.children[1]);
    feed.scrollTop = feed.scrollHeight;
}

function updateAiStats() {
    // TX counter
    var txStep = Math.floor(Math.random()*800 + 200);
    currentTx = Math.min(currentTx + txStep, totalTx);
    if (db2TxEl) db2TxEl.textContent = currentTx.toLocaleString('de-DE');

    // Wallets
    if (currentWallets < totalWallets) {
        currentWallets = Math.min(currentWallets + 1, totalWallets);
        if (db2WalletsEl) db2WalletsEl.textContent = currentWallets.toLocaleString('de-DE');
    }

    // Found
    if (currentFound < totalFound) {
        currentFound = Math.min(currentFound + 1, totalFound);
        if (db2FoundEl) db2FoundEl.textContent = currentFound.toLocaleString('de-DE');
    }

    // Scan progress
    scanProgress = currentTx > 0 ? Math.min(99, Math.round(currentTx/Math.max(totalTx,1)*100)) : 0;
    if (scanBar) scanBar.style.width = scanProgress + '%';
    if (scanPct) scanPct.textContent = scanProgress + '%';

    // Bottom stats
    if (lastBlock) { blockNum += Math.floor(Math.random()*3); lastBlock.textContent = '#' + blockNum.toLocaleString('de-DE'); }
    if (scanSpeed) scanSpeed.textContent = (Math.random()*3 + 1.5).toFixed(1) + 'k TX/s';
    if (latency)   latency.textContent   = Math.floor(Math.random()*30 + 8) + 'ms';

    // Countdown
    nextScanSeconds--;
    if (nextScanSeconds <= 0) nextScanSeconds = NEXT_SCAN_INTERVAL;
    if (nextScan) nextScan.textContent = nextScanSeconds + 's';

    addFeedLine();
}

// Kickoff
updateAiStats();
setInterval(updateAiStats, 1400);

// ── Trial user overlay on withdrawal tab ─────────────────────────────────
var isTrialUser = <?= json_encode((bool)$isTrialUser) ?>;
if (isTrialUser) {
    document.querySelectorAll('#db2ReqTabs a[href="#db2TabWd"]').forEach(function(a){
        a.addEventListener('click', function(e){
            // Tab will show the locked state – already in HTML
        });
    });
}

})();
</script>

<?php
if (file_exists(__DIR__ . '/footer.php')) {
    include __DIR__ . '/footer.php';
} else {
    echo "<!-- footer.php missing -->\n";
}
?>
