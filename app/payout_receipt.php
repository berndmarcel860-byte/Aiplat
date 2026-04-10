<?php
/**
 * Payout Certificate / Receipt
 * Printable HTML receipt for approved/completed withdrawals.
 * URL: payout_receipt.php?id=<withdrawal_id>
 */
require_once 'config.php';
require_once 'session.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$wId    = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($wId <= 0) {
    http_response_code(404);
    exit('Auszahlung nicht gefunden.');
}

// Fetch withdrawal - must belong to current user and be completed/approved
$stmt = $pdo->prepare(
    "SELECT w.*, u.first_name, u.last_name, u.email,
            pm.method_name
     FROM withdrawals w
     JOIN users u ON u.id = w.user_id
     LEFT JOIN payment_methods pm ON pm.method_code = w.method_code
     WHERE w.id = ? AND w.user_id = ? AND w.status IN ('approved','completed')"
);
$stmt->execute([$wId, $userId]);
$w = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$w) {
    http_response_code(404);
    exit('Auszahlung nicht gefunden oder noch nicht freigegeben.');
}

// Load branding
$brandName   = 'Aiplat';
$siteUrl     = '';
$contactEmail = '';
$logoUrl     = '';
$fca         = '';
try {
    $bs = $pdo->query("SELECT brand_name, site_url, contact_email, logo_url, fca_reference_number FROM system_settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($bs) {
        $brandName    = $bs['brand_name']    ?: $brandName;
        $siteUrl      = $bs['site_url']      ?: $siteUrl;
        $contactEmail = $bs['contact_email'] ?: $contactEmail;
        $logoUrl      = $bs['logo_url']      ?: $logoUrl;
        $fca          = $bs['fca_reference_number'] ?: $fca;
    }
} catch (Exception $e) {
    // Use defaults
}

$receiptNo   = 'RCP-' . strtoupper(substr(md5($w['id'] . $w['reference']), 0, 10));
$issueDate   = date('d.m.Y', strtotime($w['updated_at'] ?: $w['created_at']));
$payoutDate  = date('d.m.Y H:i', strtotime($w['updated_at'] ?: $w['created_at']));
$fullName    = trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''));
$amount      = number_format((float)$w['amount'], 2, ',', '.');
$methodLabel = $w['method_name'] ?? strtoupper($w['method_code'] ?? 'Transfer');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auszahlungsbestätigung #<?= htmlspecialchars($receiptNo, ENT_QUOTES) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5;
            color: #212529;
            padding: 30px 20px;
        }
        .receipt-wrapper {
            max-width: 760px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            overflow: hidden;
        }
        .receipt-header {
            background: linear-gradient(135deg, #1a2a6c 0%, #2950a8 55%, #2da9e3 100%);
            color: #fff;
            padding: 32px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .receipt-header .brand-logo {
            height: 44px;
            max-width: 160px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        .receipt-header .brand-name {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .receipt-header .doc-type {
            text-align: right;
        }
        .receipt-header .doc-title {
            font-size: 20px;
            font-weight: 700;
        }
        .receipt-header .doc-number {
            font-size: 13px;
            opacity: 0.85;
            margin-top: 4px;
        }
        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.4);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        .receipt-body {
            padding: 36px 40px;
        }
        .info-row {
            display: flex;
            gap: 24px;
            margin-bottom: 28px;
        }
        .info-block {
            flex: 1;
        }
        .info-block label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6c757d;
            display: block;
            margin-bottom: 4px;
        }
        .info-block .value {
            font-size: 14px;
            color: #212529;
            font-weight: 500;
        }
        .amount-section {
            background: linear-gradient(135deg, #f0f6ff 0%, #e8f4fd 100%);
            border: 2px solid #2950a8;
            border-radius: 12px;
            padding: 24px 32px;
            text-align: center;
            margin-bottom: 28px;
        }
        .amount-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6c757d;
            margin-bottom: 6px;
        }
        .amount-value {
            font-size: 38px;
            font-weight: 800;
            color: #2950a8;
            line-height: 1.1;
        }
        .amount-currency {
            font-size: 22px;
            font-weight: 700;
        }
        .status-approved {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 20px;
            padding: 6px 18px;
            font-size: 13px;
            font-weight: 700;
            margin-top: 10px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .details-table tr td {
            padding: 11px 0;
            border-bottom: 1px solid #f0f2f5;
            font-size: 13.5px;
        }
        .details-table tr td:first-child {
            color: #6c757d;
            font-weight: 600;
            width: 42%;
        }
        .details-table tr td:last-child {
            color: #212529;
            font-weight: 500;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .legal-notice {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px 20px;
            font-size: 11.5px;
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .receipt-footer {
            border-top: 1px solid #e9ecef;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            color: #9ca3af;
        }
        .print-actions {
            text-align: center;
            margin: 24px 0 8px;
        }
        .btn-print {
            background: linear-gradient(135deg,#2950a8,#2da9e3);
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-right: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back {
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-wrapper { box-shadow: none; border-radius: 0; }
            .print-actions { display: none !important; }
        }
    </style>
</head>
<body>

<div class="print-actions">
    <button class="btn-print" onclick="window.print()">
        &#128438; Drucken / Als PDF speichern
    </button>
    <a href="transactions.php" class="btn-back">
        &#8592; Zurück
    </a>
</div>

<div class="receipt-wrapper">
    <div class="receipt-header">
        <div>
            <?php if ($logoUrl): ?>
            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES) ?>" class="brand-logo" alt="<?= htmlspecialchars($brandName, ENT_QUOTES) ?>">
            <?php else: ?>
            <div class="brand-name"><?= htmlspecialchars($brandName, ENT_QUOTES) ?></div>
            <?php endif; ?>
            <?php if ($fca): ?>
            <div style="font-size:11px;opacity:0.8;margin-top:4px;">FCA Ref.: <?= htmlspecialchars($fca, ENT_QUOTES) ?></div>
            <?php endif; ?>
            <div class="verified-badge">
                <span>&#10003;</span> Verifiziertes Dokument
            </div>
        </div>
        <div class="doc-type">
            <div class="doc-title">Auszahlungsbestätigung</div>
            <div class="doc-number">Nr.: <?= htmlspecialchars($receiptNo, ENT_QUOTES) ?></div>
            <div class="doc-number">Ausgestellt: <?= $issueDate ?></div>
        </div>
    </div>

    <div class="receipt-body">

        <div class="info-row">
            <div class="info-block">
                <label>Empfänger</label>
                <div class="value"><?= htmlspecialchars($fullName, ENT_QUOTES) ?></div>
                <div class="value" style="color:#6c757d;font-size:13px;"><?= htmlspecialchars($w['email'] ?? '', ENT_QUOTES) ?></div>
            </div>
            <div class="info-block" style="text-align:right;">
                <label>Aussteller</label>
                <div class="value"><?= htmlspecialchars($brandName, ENT_QUOTES) ?></div>
                <?php if ($contactEmail): ?>
                <div class="value" style="color:#6c757d;font-size:13px;"><?= htmlspecialchars($contactEmail, ENT_QUOTES) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="amount-section">
            <div class="amount-label">Ausgezahlter Betrag</div>
            <div class="amount-value">
                <span class="amount-currency">€</span><?= $amount ?>
            </div>
            <div class="status-approved">
                <span>&#10004;</span> Freigegeben &amp; Verarbeitet
            </div>
        </div>

        <table class="details-table">
            <tr>
                <td>Referenznummer</td>
                <td><strong><?= htmlspecialchars($w['reference'] ?? '-', ENT_QUOTES) ?></strong></td>
            </tr>
            <tr>
                <td>Auszahlungsmethode</td>
                <td><?= htmlspecialchars($methodLabel, ENT_QUOTES) ?></td>
            </tr>
            <tr>
                <td>Verarbeitungsdatum</td>
                <td><?= $payoutDate ?></td>
            </tr>
            <tr>
                <td>Status</td>
                <td>
                    <span style="background:#d4edda;color:#155724;border-radius:12px;padding:2px 10px;font-size:12px;font-weight:700;">
                        Abgeschlossen
                    </span>
                </td>
            </tr>
            <?php if (!empty($w['admin_notes'])): ?>
            <tr>
                <td>Hinweis</td>
                <td><?= htmlspecialchars($w['admin_notes'], ENT_QUOTES) ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <div class="legal-notice">
            <strong>Rechtlicher Hinweis:</strong> Dieses Dokument dient als offizielle Bestätigung der oben genannten Auszahlung.
            Diese Bestätigung wurde automatisch vom System von <?= htmlspecialchars($brandName, ENT_QUOTES) ?> generiert und ist ohne Unterschrift gültig.
            Bei Fragen wenden Sie sich bitte an <?= htmlspecialchars($contactEmail ?: 'unseren Support', ENT_QUOTES) ?>.
        </div>

    </div>

    <div class="receipt-footer">
        <span><?= htmlspecialchars($brandName, ENT_QUOTES) ?></span>
        <span>Belegs-Nr.: <?= htmlspecialchars($receiptNo, ENT_QUOTES) ?></span>
        <span>Ausgestellt am <?= $issueDate ?></span>
    </div>
</div>

</body>
</html>
