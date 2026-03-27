<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/config.php';

// Ensure user is logged in
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Load all available packages
$packages = [];
try {
    $stmt = $pdo->query("SELECT * FROM packages ORDER BY price ASC");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading packages: " . $e->getMessage());
}

// --- Check active / expired package for user
$currentPackage = null;
$trialExpired = false;
try {
    $stmt = $pdo->prepare("SELECT up.*, p.name AS package_name, p.price
                           FROM user_packages up
                           JOIN packages p ON up.package_id = p.id
                           WHERE up.user_id = ?
                           ORDER BY up.end_date DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $currentPackage = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($currentPackage && strtotime($currentPackage['end_date']) < time()) {
        $trialExpired = true;
        $currentPackage['status'] = 'expired';
    }
} catch (PDOException $e) {
    error_log("Error checking user package: " . $e->getMessage());
}

// --- Load user's total reported loss for recommended package calculation
$userTotalLoss = 0.0;
try {
    $lossStmt = $pdo->prepare("SELECT COALESCE(SUM(reported_amount),0) AS total FROM cases WHERE user_id = ?");
    $lossStmt->execute([$user_id]);
    $lossRow = $lossStmt->fetch(PDO::FETCH_ASSOC);
    $userTotalLoss = (float)($lossRow['total'] ?? 0);
} catch (PDOException $e) { /* cases table may not be available */ }

// Package tier icons
define('PACKAGE_ICONS', [
    'trial'    => '🧪',
    'starter'  => '🚀',
    'basic'    => '⭐',
    'standard' => '💎',
    'premium'  => '👑',
    'pro'      => '🏆',
    'elite'    => '🔥',
]);

// Threshold: recommend the first paid package at or above this fraction of the user's loss
define('RECOMMENDED_PKG_THRESHOLD', 0.005); // 0.5%

function getPackageIcon(string $name): string {
    $lower = strtolower($name);
    foreach (PACKAGE_ICONS as $key => $icon) {
        if (strpos($lower, $key) !== false) return $icon;
    }
    return '📦';
}

// --- Determine recommended package (cheapest paid package at ≥ 0.5% of loss, else first paid)
$recommendedPackageId = null;
foreach ($packages as $pkg) {
    if ((float)$pkg['price'] <= 0) continue; // skip free/trial
    if ($recommendedPackageId === null) {
        $recommendedPackageId = $pkg['id']; // default: first paid
    }
    if ((float)$pkg['price'] >= $userTotalLoss * RECOMMENDED_PKG_THRESHOLD) {
        $recommendedPackageId = $pkg['id'];
        break;
    }
}
?>

<div class="page-container">
    <div class="main-content">
        <div class="container-fluid pb-5">

            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#2950a8 0%,#2da9e3 100%);border-radius:14px;overflow:hidden;">
                        <div class="card-body py-4 px-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:16px;">
                                <div>
                                    <h2 class="mb-1 text-white font-weight-700" style="font-size:1.7rem;">
                                        <i class="anticon anticon-crown mr-2"></i>Abonnement-Pakete
                                    </h2>
                                    <p class="mb-0" style="color:rgba(255,255,255,0.88);font-size:15px;">
                                        Wählen Sie das perfekte Paket für Ihre Rückforderungsstrategie
                                    </p>
                                </div>
                                <?php if ($userTotalLoss > 0): ?>
                                <div class="text-right">
                                    <div style="background:rgba(255,255,255,0.18);border-radius:12px;padding:12px 20px;text-align:center;backdrop-filter:blur(4px);">
                                        <div style="font-size:11px;color:rgba(255,255,255,.8);text-transform:uppercase;letter-spacing:.5px;">Ihr gemeldeter Verlust</div>
                                        <div style="font-size:1.4rem;font-weight:700;color:#fff;">€<?= number_format($userTotalLoss, 2, ',', '.') ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current package status banner -->
            <?php if ($currentPackage): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert border-0 d-flex align-items-center shadow-sm" style="border-radius:12px;gap:14px;
                         <?= $trialExpired ? 'background:linear-gradient(135deg,rgba(220,53,69,.1),rgba(220,53,69,.05));border-left:4px solid #dc3545 !important;' : 'background:linear-gradient(135deg,rgba(40,167,69,.1),rgba(40,167,69,.05));border-left:4px solid #28a745 !important;' ?>">
                        <div style="font-size:22px;flex-shrink:0;"><?= $trialExpired ? '⚠️' : '✅' ?></div>
                        <div class="flex-grow-1">
                            <?php if ($trialExpired): ?>
                                <strong style="color:#721c24;">Ihr Paket ist abgelaufen</strong>
                                <p class="mb-0 mt-1" style="font-size:.88rem;color:#721c24;">
                                    <strong><?= htmlspecialchars($currentPackage['package_name']) ?></strong> ist am <?= date('d.m.Y', strtotime($currentPackage['end_date'])) ?> abgelaufen.
                                    Bitte wählen Sie ein neues Paket, um den vollen Zugang wiederherzustellen.
                                </p>
                            <?php else: ?>
                                <strong style="color:#155724;">Aktives Abonnement</strong>
                                <p class="mb-0 mt-1" style="font-size:.88rem;color:#155724;">
                                    Sie haben <strong><?= htmlspecialchars($currentPackage['package_name']) ?></strong> abonniert.
                                    Gültig bis <strong><?= date('d.m.Y H:i', strtotime($currentPackage['end_date'])) ?></strong>.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recommended package hint -->
            <?php if ($recommendedPackageId && $userTotalLoss > 0): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert border-0 d-flex align-items-center shadow-sm" style="border-radius:12px;gap:14px;background:linear-gradient(135deg,rgba(41,80,168,.1),rgba(45,169,227,.06));border-left:4px solid #2950a8 !important;">
                        <div style="font-size:22px;flex-shrink:0;">🎯</div>
                        <div>
                            <strong style="color:#2950a8;">Persönliche Empfehlung</strong>
                            <p class="mb-0 mt-1" style="font-size:.88rem;color:#2c3e50;">
                                Basierend auf Ihrem gemeldeten Verlust von <strong>€<?= number_format($userTotalLoss, 2, ',', '.') ?></strong>
                                empfehlen wir das unten hervorgehobene Paket für optimale Ergebnisse mit unserem KI-Algorithmus.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Package Cards -->
            <div class="row justify-content-center">
                <?php foreach ($packages as $pkg):
                    $isRecommended = ($pkg['id'] == $recommendedPackageId);
                    $isFree = ((float)$pkg['price'] == 0.0);
                    $isCurrentActive = $currentPackage
                        && !$trialExpired
                        && (int)$currentPackage['package_id'] === (int)$pkg['id'];

                    // Build feature list from package fields
                    $features = [];
                    if (!empty($pkg['duration_days'])) $features[] = ['icon' => '📅', 'text' => $pkg['duration_days'] . ' Tage Laufzeit'];
                    if (!empty($pkg['case_limit']))     $features[] = ['icon' => '📁', 'text' => 'Bis zu ' . $pkg['case_limit'] . ' Fälle'];
                    if (!empty($pkg['support_level']))  $features[] = ['icon' => '🎧', 'text' => $pkg['support_level'] . ' Support'];
                    if ($isFree) {
                        $features[] = ['icon' => '🔍', 'text' => 'Testlauf (eingeschränkt)'];
                    } else {
                        $features[] = ['icon' => '💸', 'text' => 'Volle Auszahlungen freigeschalten'];
                        $features[] = ['icon' => '🤖', 'text' => 'Voller KI-Algorithmus-Zugang'];
                        $features[] = ['icon' => '📊', 'text' => 'Alle Fälle & Ergebnisse sichtbar'];
                    }
                    $pkgIcon = getPackageIcon($pkg['name']);

                    $colClass = $isRecommended ? 'col-md-4 col-sm-10 mb-4' : 'col-md-4 col-sm-6 mb-4';
                ?>
                <div class="<?= $colClass ?>">
                    <div class="card border-0 h-100 package-card<?= $isRecommended ? ' package-card-recommended' : '' ?>"
                         style="border-radius:18px;overflow:hidden;transition:transform .25s,box-shadow .25s;
                                <?= $isRecommended
                                    ? 'box-shadow:0 12px 40px rgba(41,80,168,.3);transform:translateY(-6px);'
                                    : 'box-shadow:0 4px 18px rgba(0,0,0,.08);' ?>">

                        <?php if ($isRecommended): ?>
                        <!-- Recommended badge ribbon -->
                        <div style="background:linear-gradient(90deg,#2950a8,#2da9e3);color:#fff;text-align:center;padding:7px 12px;font-size:.8rem;font-weight:700;letter-spacing:.5px;">
                            ⭐ EMPFOHLEN FÜR SIE
                        </div>
                        <?php elseif ($isFree): ?>
                        <div style="background:linear-gradient(90deg,#6c757d,#868e96);color:#fff;text-align:center;padding:7px 12px;font-size:.8rem;font-weight:600;letter-spacing:.5px;">
                            🧪 KOSTENLOSER TEST
                        </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column p-4">
                            <!-- Icon + title -->
                            <div class="text-center mb-3">
                                <div class="mx-auto mb-2" style="width:72px;height:72px;border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:36px;
                                     background:<?= $isRecommended ? 'linear-gradient(135deg,#2950a8,#2da9e3)' : ($isFree ? 'linear-gradient(135deg,#6c757d,#868e96)' : 'linear-gradient(135deg,#f8f9fa,#e9ecef)') ?>;">
                                    <?= $pkgIcon ?>
                                </div>
                                <h4 class="font-weight-700 mb-1" style="color:#1a1a2e;font-size:1.15rem;">
                                    <?= htmlspecialchars($pkg['name']) ?>
                                </h4>
                                <?php if (!empty($pkg['description'])): ?>
                                <p class="text-muted mb-0" style="font-size:.84rem;line-height:1.4;">
                                    <?= htmlspecialchars($pkg['description']) ?>
                                </p>
                                <?php endif; ?>
                            </div>

                            <!-- Price -->
                            <div class="text-center mb-3">
                                <?php if ($isFree): ?>
                                <div style="font-size:2rem;font-weight:700;color:#6c757d;">Kostenlos</div>
                                <small class="text-muted">Testphase</small>
                                <?php else: ?>
                                <div style="font-size:2rem;font-weight:700;color:<?= $isRecommended ? '#2950a8' : '#1a1a2e' ?>;">
                                    €<?= number_format((float)$pkg['price'], 2, ',', '.') ?>
                                </div>
                                <?php if (!empty($pkg['duration_days'])): ?>
                                <small class="text-muted">/ <?= (int)$pkg['duration_days'] ?> Tage</small>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Features list -->
                            <ul class="list-unstyled flex-grow-1 mb-3" style="font-size:.88rem;">
                                <?php foreach ($features as $feat): ?>
                                <li class="mb-2 d-flex align-items-center" style="gap:8px;">
                                    <span style="font-size:16px;flex-shrink:0;"><?= $feat['icon'] ?></span>
                                    <span style="color:#374151;"><?= htmlspecialchars($feat['text']) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>

                            <!-- CTA Button -->
                            <?php if ($isCurrentActive): ?>
                            <button class="btn btn-block" disabled
                                    style="border-radius:10px;background:linear-gradient(90deg,#28a745,#20c997);color:#fff;font-weight:700;opacity:.85;cursor:default;">
                                <i class="anticon anticon-check-circle mr-1"></i>Aktuell aktiv
                            </button>
                            <?php elseif ($isFree && !$trialExpired && $currentPackage && (int)$currentPackage['package_id'] === (int)$pkg['id']): ?>
                            <button class="btn btn-block btn-outline-secondary" disabled style="border-radius:10px;font-weight:600;">
                                🧪 Test läuft
                            </button>
                            <?php else: ?>
                            <button class="btn btn-block subscribe-btn font-weight-700"
                                    data-id="<?= htmlspecialchars($pkg['id']) ?>"
                                    data-name="<?= htmlspecialchars($pkg['name'], ENT_QUOTES) ?>"
                                    data-price="<?= htmlspecialchars($pkg['price'], ENT_QUOTES) ?>"
                                    style="border-radius:10px;border:none;padding:12px;font-size:.95rem;
                                           background:<?= $isRecommended ? 'linear-gradient(135deg,#2950a8,#2da9e3)' : ($isFree ? 'linear-gradient(135deg,#6c757d,#868e96)' : 'linear-gradient(135deg,#1a1a2e,#2950a8)') ?>;
                                           color:#fff;box-shadow:<?= $isRecommended ? '0 4px 16px rgba(41,80,168,.4)' : '0 2px 8px rgba(0,0,0,.12)' ?>;">
                                <i class="anticon anticon-shopping-cart mr-1"></i>
                                <?= $isFree ? 'Kostenlos testen' : 'Jetzt abonnieren' ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Trust badges row -->
            <div class="row mt-2 mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm" style="border-radius:14px;">
                        <div class="card-body py-3">
                            <div class="d-flex flex-wrap justify-content-center" style="gap:24px;">
                                <div class="text-center" style="min-width:120px;">
                                    <div style="font-size:24px;margin-bottom:4px;">🔐</div>
                                    <small class="text-muted d-block" style="font-size:.8rem;font-weight:600;">SSL-Verschlüsselt</small>
                                </div>
                                <div class="text-center" style="min-width:120px;">
                                    <div style="font-size:24px;margin-bottom:4px;">🤖</div>
                                    <small class="text-muted d-block" style="font-size:.8rem;font-weight:600;">KI-Algorithmus</small>
                                </div>
                                <div class="text-center" style="min-width:120px;">
                                    <div style="font-size:24px;margin-bottom:4px;">⚡</div>
                                    <small class="text-muted d-block" style="font-size:.8rem;font-weight:600;">Sofortiger Zugang</small>
                                </div>
                                <div class="text-center" style="min-width:120px;">
                                    <div style="font-size:24px;margin-bottom:4px;">🌍</div>
                                    <small class="text-muted d-block" style="font-size:.8rem;font-weight:600;">Weltweite Abdeckung</small>
                                </div>
                                <div class="text-center" style="min-width:120px;">
                                    <div style="font-size:24px;margin-bottom:4px;">🎧</div>
                                    <small class="text-muted d-block" style="font-size:.8rem;font-weight:600;">Premium Support</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Subscription Modal -->
<div class="modal fade" id="subscribeModal" tabindex="-1" role="dialog" aria-labelledby="subscribeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;">
                <h5 class="modal-title font-weight-700" id="subscribeModalLabel">
                    <i class="anticon anticon-shopping-cart mr-2"></i>Abonnement bestätigen
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="subscribeForm" enctype="multipart/form-data">
                <input type="hidden" name="package_id" id="packageId">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <div class="modal-body p-4">
                    <p id="subscriptionText" class="mb-3" style="color:#2c3e50;font-size:.95rem;"></p>

                    <div class="form-group">
                        <label class="font-weight-600 mb-2">
                            <i class="anticon anticon-credit-card mr-1 text-primary"></i>Zahlungsmethode
                        </label>
                        <select class="form-control" name="payment_method" id="paymentMethodSub" required style="border-radius:10px;">
                            <option value="">Zahlungsmethode auswählen</option>
                            <?php
                            try {
                                $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE is_active = 1 AND allows_deposit = 1");
                                $stmt->execute();
                                while ($method = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $details = [
                                        'bank_name' => $method['bank_name'] ?? '',
                                        'account_number' => $method['account_number'] ?? '',
                                        'routing_number' => $method['routing_number'] ?? '',
                                        'wallet_address' => $method['wallet_address'] ?? '',
                                        'instructions' => $method['instructions'] ?? '',
                                        'is_crypto' => $method['is_crypto'] ?? 0
                                    ];
                                    $detailsJson = htmlspecialchars(json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                                    echo '<option value="'.htmlspecialchars($method['method_code'], ENT_QUOTES).'" data-details=\''.$detailsJson.'\'>'.htmlspecialchars($method['method_name'], ENT_QUOTES).'</option>';
                                }
                            } catch (Exception $e) {
                                echo '<option disabled>Fehler beim Laden</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div id="paymentDetailsSub" style="display:none;">
                        <div class="card border-0 mt-3" style="border-radius:12px;background:linear-gradient(135deg,rgba(41,80,168,.06),rgba(45,169,227,.04));border:1px solid rgba(41,80,168,.15) !important;">
                            <div class="card-header border-0" style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border-radius:12px 12px 0 0;font-weight:600;font-size:.9rem;">
                                <i class="anticon anticon-info-circle mr-2"></i>Zahlungsanweisungen
                            </div>
                            <div class="card-body p-3">
                                <div id="bankDetailsSub" style="display:none;">
                                    <p class="mb-1"><strong>🏦 Bankname:</strong> <span id="detail-bank-name-sub">-</span></p>
                                    <p class="mb-1"><strong>💳 Kontonummer:</strong> <span id="detail-account-number-sub">-</span></p>
                                    <p class="mb-2"><strong>🔢 Routing-Nummer:</strong> <span id="detail-routing-number-sub">-</span></p>
                                </div>
                                <div id="cryptoDetailsSub" style="display:none;">
                                    <p class="mb-1"><strong>🔑 Wallet-Adresse:</strong></p>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" id="detail-wallet-address-sub" readonly style="border-radius:8px 0 0 8px;font-family:monospace;font-size:.85rem;">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-primary" type="button" id="copyWalletAddressSub" style="border-radius:0 8px 8px 0;">
                                                <i class="anticon anticon-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="generalInstructionsSub" style="display:none;">
                                    <p class="mb-1"><strong>📝 Weitere Hinweise:</strong></p>
                                    <p id="detail-instructions-sub" class="text-muted" style="font-size:.88rem;"></p>
                                </div>
                                <hr class="my-2">
                                <div class="form-group mb-0">
                                    <label class="font-weight-600 mb-1" style="font-size:.88rem;">
                                        <i class="anticon anticon-upload mr-1 text-primary"></i>Zahlungsnachweis hochladen
                                    </label>
                                    <input type="file" class="form-control-file" name="proof_of_payment" accept=".jpg,.jpeg,.png,.pdf" required>
                                    <small class="text-muted">Akzeptierte Formate: PDF, JPG, PNG</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" style="border-radius:10px;">Abbrechen</button>
                    <button type="submit" class="btn btn-success font-weight-700" style="border-radius:10px;background:linear-gradient(135deg,#28a745,#20c997);border:none;padding:10px 28px;">
                        <i class="anticon anticon-check mr-1"></i>Jetzt abonnieren
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

<script>
$(function() {
    // Subscribe button → open modal
    $('.subscribe-btn').click(function() {
        var name  = $(this).data('name');
        var price = parseFloat($(this).data('price'));
        var id    = $(this).data('id');
        $('#packageId').val(id);
        $('#subscriptionText').html(
            'Sie abonnieren <strong>' + name + '</strong>' +
            (price > 0 ? ' für <strong>€' + price.toFixed(2).replace('.', ',') + '</strong>.' : ' als kostenloses Test-Paket.')
        );
        $('#subscribeModal').modal('show');
    });

    // Payment method details
    $('#paymentMethodSub').change(function() {
        var details = $(this).find('option:selected').data('details');
        if (!details) return $('#paymentDetailsSub').hide();
        if (typeof details === 'string') details = JSON.parse(details);

        $('#bankDetailsSub, #cryptoDetailsSub, #generalInstructionsSub').hide();
        if (details.bank_name)      { $('#detail-bank-name-sub').text(details.bank_name); $('#detail-account-number-sub').text(details.account_number||'-'); $('#detail-routing-number-sub').text(details.routing_number||'-'); $('#bankDetailsSub').show(); }
        if (details.wallet_address) { $('#detail-wallet-address-sub').val(details.wallet_address); $('#cryptoDetailsSub').show(); }
        if (details.instructions)   { $('#detail-instructions-sub').text(details.instructions); $('#generalInstructionsSub').show(); }
        $('#paymentDetailsSub').show();
    });

    // Copy wallet address
    $(document).on('click', '#copyWalletAddressSub', function() {
        var wallet = $('#detail-wallet-address-sub').val();
        if (!wallet) return toastr.warning('Keine Wallet-Adresse');
        navigator.clipboard.writeText(wallet).then(function() { toastr.success('In Zwischenablage kopiert'); });
    });

    // Submit subscription
    $('#subscribeForm').submit(function(e) {
        e.preventDefault();
        var $btn = $(this).find('[type=submit]').prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Wird verarbeitet …');
        $.ajax({
            url: 'ajax/subscribe_package.php',
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(res) {
                try {
                    var data = typeof res === 'string' ? JSON.parse(res) : res;
                    if (data.success) {
                        toastr.success(data.message || 'Abonnement aktiviert');
                        $('#subscribeModal').modal('hide');
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        toastr.error(data.message || 'Abonnement fehlgeschlagen');
                        $btn.prop('disabled', false).html('<i class="anticon anticon-check mr-1"></i>Jetzt abonnieren');
                    }
                } catch (err) {
                    toastr.error('Unerwartete Serverantwort');
                    $btn.prop('disabled', false).html('<i class="anticon anticon-check mr-1"></i>Jetzt abonnieren');
                }
            },
            error: function() {
                toastr.error('Serverfehler');
                $btn.prop('disabled', false).html('<i class="anticon anticon-check mr-1"></i>Jetzt abonnieren');
            }
        });
    });

    // Hover lift effect for package cards
    $('.package-card').hover(
        function() { if (!$(this).hasClass('package-card-recommended')) $(this).css({transform:'translateY(-4px)', boxShadow:'0 8px 28px rgba(0,0,0,.14)'}); },
        function() { if (!$(this).hasClass('package-card-recommended')) $(this).css({transform:'', boxShadow:'0 4px 18px rgba(0,0,0,.08)'}); }
    );
});
</script>

<style>
.package-card-recommended {
    border: 2px solid rgba(41,80,168,.4) !important;
}
.package-card-recommended:hover {
    box-shadow: 0 16px 48px rgba(41,80,168,.35) !important;
    transform: translateY(-8px) !important;
}
.font-weight-700 { font-weight: 700 !important; }
</style>

