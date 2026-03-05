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
    }
} catch (PDOException $e) {
    error_log("Error checking user package: " . $e->getMessage());
}
?>

<div class="page-container">
    <div class="main-content">
        <div class="container-fluid">

            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-header-gradient">
                        <h2><i class="anticon anticon-shopping mr-2"></i>Subscription Packages</h2>
                        <p>Choose the perfect plan for your recovery needs</p>
                    </div>
                </div>
            </div>

            <?php if ($currentPackage): ?>
                <div class="alert <?= $trialExpired ? 'alert-danger' : 'alert-success' ?> shadow-sm border-0 d-flex align-items-center mb-4" style="border-radius: 10px;">
                    <i class="anticon <?= $trialExpired ? 'anticon-warning' : 'anticon-check-circle' ?> mr-2" style="font-size: 20px;"></i>
                    <div>
                        <?php if ($trialExpired): ?>
                            Your <strong><?= htmlspecialchars($currentPackage['package_name']) ?></strong> package has expired.
                            Please select a new package below to continue using your account.
                        <?php else: ?>
                            You are currently subscribed to <strong><?= htmlspecialchars($currentPackage['package_name']) ?></strong>
                            (Expires on <?= htmlspecialchars(date('M d, Y H:i', strtotime($currentPackage['end_date']))) ?>).
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row">
                <?php foreach ($packages as $pkg): ?>
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="card shadow-sm border-0 package-card h-100" style="transition: transform 0.2s ease, box-shadow 0.2s ease;">
                            <div class="card-body text-center d-flex flex-column">
                                <div class="mb-3">
                                    <div class="avatar-icon mx-auto" style="width: 70px; height: 70px; background: linear-gradient(135deg, #6b7280, #4b5563); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px;">
                                        <i class="anticon anticon-gift" style="color: #ffffff;"></i>
                                    </div>
                                </div>
                                <h4 class="font-weight-bold mb-2" style="color: #374151;"><?= htmlspecialchars($pkg['name']) ?></h4>
                                <p class="text-muted mb-3" style="color: #6b7280;"><?= htmlspecialchars($pkg['description']) ?></p>
                                <div class="h2 mb-4" style="color: #374151; font-weight: 700;">
                                    <?= $pkg['price'] > 0 ? '$' . number_format($pkg['price'], 2) : 'Free Trial' ?>
                                </div>
                                <ul class="list-group list-group-flush mb-4 text-left flex-grow-1">
                                    <li class="list-group-item border-0 px-0">
                                        <i class="anticon anticon-check-circle text-success mr-2"></i>
                                        Duration: <strong><?= htmlspecialchars($pkg['duration_days'] ?? '30', ENT_QUOTES) ?> days</strong>
                                    </li>
                                    <li class="list-group-item border-0 px-0">
                                        <i class="anticon anticon-check-circle text-success mr-2"></i>
                                        Case Limit: <strong><?= htmlspecialchars($pkg['case_limit'] ?? '1', ENT_QUOTES) ?></strong>
                                    </li>
                                    <li class="list-group-item border-0 px-0">
                                        <i class="anticon anticon-check-circle text-success mr-2"></i>
                                        Support: <strong><?= htmlspecialchars($pkg['support_level'] ?? 'Standard', ENT_QUOTES) ?></strong>
                                    </li>
                                </ul>
                                <?php if ($pkg['price'] == 0 && !$trialExpired): ?>
                                    <button class="btn btn-outline-secondary btn-block" disabled>Trial Active</button>
                                <?php else: ?>
                                    <button class="btn btn-primary btn-block btn-pulse subscribe-btn"
                                            data-id="<?= htmlspecialchars($pkg['id']) ?>"
                                            data-name="<?= htmlspecialchars($pkg['name']) ?>"
                                            data-price="<?= htmlspecialchars($pkg['price']) ?>">
                                        <i class="anticon anticon-shopping-cart mr-1"></i>Subscribe Now
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<!-- Subscription Modal -->
<div class="modal fade" id="subscribeModal" tabindex="-1" role="dialog" aria-labelledby="subscribeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <div class="modal-header" style="background: rgba(240, 240, 242, 0.95);">
                <h5 class="modal-title" id="subscribeModalLabel" style="color: #374151;">Confirm Subscription</h5>
                <button type="button" class="close" data-dismiss="modal" style="color: #374151;"><i class="anticon anticon-close" style="color: #374151;"></i></button>
            </div>
            <form id="subscribeForm" enctype="multipart/form-data">
                <input type="hidden" name="package_id" id="packageId">

                <div class="modal-body">
                    <p id="subscriptionText"></p>

                    <div class="form-group mt-3">
                        <label class="font-weight-semibold">Payment Method</label>
                        <select class="form-control" name="payment_method" id="paymentMethodSub" required>
                            <option value="">Select Payment Method</option>
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
                                echo '<option disabled>Error loading methods</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div id="paymentDetailsSub" style="display:none;">
                        <div class="card border-primary mt-3">
                            <div class="card-header bg-primary text-white">Payment Instructions</div>
                            <div class="card-body">
                                <div id="bankDetailsSub" style="display:none;">
                                    <h6 class="text-primary"><i class="anticon anticon-bank"></i> Bank Transfer</h6>
                                    <p><strong>Bank Name:</strong> <span id="detail-bank-name-sub">-</span></p>
                                    <p><strong>Account Number:</strong> <span id="detail-account-number-sub">-</span></p>
                                    <p><strong>Routing Number:</strong> <span id="detail-routing-number-sub">-</span></p>
                                </div>

                                <div id="cryptoDetailsSub" style="display:none;">
                                    <h6 class="text-primary"><i class="anticon anticon-block"></i> Crypto Wallet</h6>
                                    <p><strong>Wallet Address:</strong></p>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" id="detail-wallet-address-sub" readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="copyWalletAddressSub">
                                                <i class="anticon anticon-copy"></i> Copy
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div id="generalInstructionsSub" style="display:none;">
                                    <h6 class="text-primary"><i class="anticon anticon-info-circle"></i> Additional Instructions</h6>
                                    <p id="detail-instructions-sub"></p>
                                </div>

                                <hr>
                                <div class="form-group">
                                    <label>Upload Proof of Payment</label>
                                    <input type="file" class="form-control-file" name="proof_of_payment" accept=".jpg,.jpeg,.png,.pdf" required>
                                    <small class="text-muted">Accepted formats: PDF, JPG, PNG</small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="anticon anticon-check"></i> Confirm Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

<script>
$(function() {
    $('.subscribe-btn').click(function() {
        var name = $(this).data('name');
        var price = parseFloat($(this).data('price'));
        var id = $(this).data('id');

        $('#packageId').val(id);
        $('#subscriptionText').html(
            'You are subscribing to <strong>' + name + '</strong> ' +
            (price > 0 ? 'for <strong>$' + price.toFixed(2) + '</strong>.' : 'as a Free Plan.')
        );
        $('#subscribeModal').modal('show');
    });

    $('#paymentMethodSub').change(function() {
        var selected = $(this).find('option:selected');
        var details = selected.data('details');
        var $container = $('#paymentDetailsSub');

        if (!details) return $container.hide();
        if (typeof details === 'string') details = JSON.parse(details);

        $('#bankDetailsSub, #cryptoDetailsSub, #generalInstructionsSub').hide();

        if (details.bank_name) {
            $('#detail-bank-name-sub').text(details.bank_name);
            $('#detail-account-number-sub').text(details.account_number || '-');
            $('#detail-routing-number-sub').text(details.routing_number || '-');
            $('#bankDetailsSub').show();
        }
        if (details.wallet_address) {
            $('#detail-wallet-address-sub').val(details.wallet_address);
            $('#cryptoDetailsSub').show();
        }
        if (details.instructions) {
            $('#detail-instructions-sub').text(details.instructions);
            $('#generalInstructionsSub').show();
        }
        $container.show();
    });

    $(document).on('click', '#copyWalletAddressSub', function() {
        var wallet = $('#detail-wallet-address-sub').val();
        if (!wallet) return toastr.warning('No wallet address');
        navigator.clipboard.writeText(wallet).then(function() {
            toastr.success('Copied to clipboard');
        });
    });

    $('#subscribeForm').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: 'ajax/subscribe_package.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                try {
                    var data = typeof res === 'string' ? JSON.parse(res) : res;
                    if (data.success) {
                        toastr.success(data.message || 'Subscription activated');
                        $('#subscribeModal').modal('hide');
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        toastr.error(data.message || 'Subscription failed');
                    }
                } catch (err) {
                    toastr.error('Unexpected server response');
                }
            },
            error: function() {
                toastr.error('Server error');
            }
        });
    });
});
</script>

<style>
/* Light Gray Theme */
body {
    background: linear-gradient(135deg, #f5f5f7 0%, #e8e8ea 100%) !important;
    background-attachment: fixed !important;
}

/* Transparent Cards with Backdrop Blur */
.card, .card-body, .table-container, .info-box, .stats-box {
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(10px) !important;
    -webkit-backdrop-filter: blur(10px) !important;
    border: 1px solid rgba(200, 200, 200, 0.3) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
    transition: all 0.3s ease !important;
}

.card:hover, .stats-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12) !important;
}

/* Dark Text for Readability */
h1, h2, h3, h4, h5, h6, .card-body, .table {
    color: #1f2937 !important;
}

p, span, td, th, label {
    color: #374151 !important;
}

.text-muted {
    color: #6b7280 !important;
}

/* Table Styling */
.table {
    background: transparent !important;
}

.table thead th {
    background: rgba(240, 240, 242, 0.95) !important;
    backdrop-filter: blur(10px) !important;
    border-color: rgba(200, 200, 200, 0.3) !important;
    color: #374151 !important;
    font-weight: 600;
}

.table tbody tr {
    background: rgba(255, 255, 255, 0.5) !important;
    backdrop-filter: blur(5px) !important;
    transition: all 0.2s ease !important;
}

.table tbody tr:hover {
    background: rgba(240, 240, 242, 0.8) !important;
    transform: translateX(2px);
}

.table tbody td {
    color: #1f2937 !important;
}

/* Page Header Gradient */
.page-header-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.page-header-gradient h2 {
    color: white !important;
    margin-bottom: 10px;
}

.page-header-gradient p {
    color: rgba(255, 255, 255, 0.9) !important;
    margin: 0;
}

.page-header-gradient .anticon {
    color: white !important;
}

/* Blue Gradient Buttons */
.btn-primary, .btn-outline-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    color: #ffffff !important;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4) !important;
    transition: all 0.3s !important;
}

.btn-primary:hover, .btn-outline-primary:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6) !important;
    color: white !important;
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: #ffffff !important;
}

/* Dark Icons */
.anticon {
    color: #374151 !important;
}

/* Modal Styling */
.modal-content {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(200, 200, 200, 0.3) !important;
    color: #1f2937 !important;
}

.modal-header {
    background: rgba(240, 240, 242, 0.95) !important;
    color: #1f2937 !important;
}

/* Form Controls */
.form-control {
    background: rgba(255, 255, 255, 0.9) !important;
    border: 1px solid rgba(200, 200, 200, 0.5) !important;
    color: #1f2937 !important;
}

.form-control:focus {
    background: rgba(255, 255, 255, 1) !important;
    border-color: #6b7280 !important;
    box-shadow: 0 0 0 0.2rem rgba(107, 114, 128, 0.25) !important;
}

.package-card {
    border-top: 4px solid #6b7280;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.package-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}
</style>

