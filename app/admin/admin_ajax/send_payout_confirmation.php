<?php
// admin_ajax/send_payout_confirmation.php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use setasign\Fpdi\FpdfTpl as FPDF;

ini_set('display_errors', 0);
error_reporting(E_ALL);

/* ---------- Autoload & App Includes ---------- */
$phpMailerAvailable = false;
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    $phpMailerAvailable = class_exists(PHPMailer::class);
}

require_once '../admin_session.php';
require_once '../../config.php'; // provides $pdo

header('Content-Type: application/json');

/* =========================
   Visual constants & spacing
   ========================= */
const BRAND_RGB = [28, 41, 69];       // deep blue
const LINE_RGB  = [222, 226, 230];    // light divider
const TH_RGB    = [233, 236, 239];    // table header
const ZEBRA_RGB = [247, 249, 252];    // table zebra

const GAP_SM = 6.0;
const GAP_MD = 10.0;
const GAP_LG = 14.0;

/* =========================
   FPDF wrapper (professional layout)
   ========================= */
class PDF extends FPDF {
    public array $company = [];
    public array $cust    = [];
    /** header rendering mode: 'company_right_only' or 'default' */
    public string $headerMode = 'default';

    function Header() {
        $y = 12;

        if ($this->headerMode === 'company_right_only') {
            // --- ONLY company header on the RIGHT ---
            $x = 130; $w = 60; $h = 28;

            $this->SetDrawColor(...LINE_RGB);
            $this->Rect($x, $y, $w, $h);

            $this->SetFont('Helvetica','B',10);
            $this->SetXY($x+4, $y+3);
            $this->Cell(0,5, pdf_txt($this->company['name']), 0, 1, 'L');

            $this->SetFont('Helvetica','',9);
            $this->SetX($x+4); $this->Cell(0,4.6, pdf_txt($this->company['addr1']), 0, 1, 'L');
            if (!empty($this->company['addr2'])) { $this->SetX($x+4); $this->Cell(0,4.6, pdf_txt($this->company['addr2']), 0, 1, 'L'); }
            if (!empty($this->company['reg']))   { $this->SetX($x+4); $this->Cell(0,4.6, pdf_txt($this->company['reg']),   0, 1, 'L'); }

            $this->SetDrawColor(...LINE_RGB);
            $this->Line(20, 44, 190, 44);
            $this->SetDrawColor(0,0,0);
            return;
        }

        /* ------------ DEFAULT: company LEFT (card) + customer RIGHT (card) ------------ */

        // LEFT company panel: styled like the right card
        $xL = 20;  $wL = 90;  $h  = 28;
        $this->SetDrawColor(...LINE_RGB);
        $this->Rect($xL, $y, $wL, $h);

        $this->SetFont('Helvetica','B',10);
        $this->SetXY($xL+4, $y+3);
        $this->Cell(0,5, pdf_txt($this->company['name']), 0, 1, 'L');

        $this->SetFont('Helvetica','',9);
        $this->SetX($xL+4); $this->Cell(0,4.6, pdf_txt($this->company['addr1']), 0, 1, 'L');
        if (!empty($this->company['addr2'])) { $this->SetX($xL+4); $this->Cell(0,4.6, pdf_txt($this->company['addr2']), 0, 1, 'L'); }
        if (!empty($this->company['reg']))   { $this->SetX($xL+4); $this->Cell(0,4.6, pdf_txt($this->company['reg']),   0, 1, 'L'); }

        // RIGHT customer panel
        $xR = 130; $wR = 60;
        $this->SetDrawColor(...LINE_RGB);
        $this->Rect($xR, $y, $wR, $h);

        $this->SetFont('Helvetica','B',9);
        $this->SetXY($xR+4, $y+3);
        $this->Cell(0,5, pdf_txt('Kunde'), 0, 1, 'L');

        $this->SetFont('Helvetica','',9);
        $this->SetX($xR+4); $this->Cell(0,4.8, pdf_txt($this->cust['full_name']), 0, 1, 'L');
        if (!empty($this->cust['street'])) { $this->SetX($xR+4); $this->Cell(0,4.8, pdf_txt($this->cust['street']), 0, 1, 'L'); }
        $cityLine = trim(($this->cust['postal_code'] ?? '').' '.($this->cust['state'] ?? ''));
        if ($cityLine !== '') { $this->SetX($xR+4); $this->Cell(0,4.8, pdf_txt($cityLine), 0, 1, 'L'); }
        if (!empty($this->cust['country'])) { $this->SetX($xR+4); $this->Cell(0,4.8, pdf_txt($this->cust['country']), 0, 1, 'L'); }

        // Bottom divider
        $this->SetDrawColor(...LINE_RGB);
        $this->Line(20, 44, 190, 44);
        $this->SetDrawColor(0,0,0);
    }

    function Footer() {
        $this->SetY(-18);
        $this->SetDrawColor(...LINE_RGB);
        $this->Line(20, $this->GetY(), 190, $this->GetY());
        $this->SetDrawColor(0,0,0);
        $this->SetFont('Helvetica','',9);
        $this->Cell(0,10, pdf_txt('Seite '.$this->PageNo()), 0, 0, 'R');
    }

    /* ---- helpers ---- */
    function sectionTitle(string $text, float $x, float $y) {
        $this->SetFont('Helvetica','B',12);
        $this->SetTextColor(...BRAND_RGB);
        $this->SetXY($x, $y);
        $this->Cell(0, 8, pdf_txt($text), 0, 1, 'L');
        $this->SetTextColor(0,0,0);
    }

    // Grid table helpers
    function gridHeader(array $cols, float $x, float $y, float $h = 9): float {
        $this->SetFillColor(...TH_RGB);
        $this->SetFont('Helvetica','B',10);
        $this->SetXY($x,$y);
        foreach ($cols as [$text, $w, $align]) {
            $this->Cell($w, $h, pdf_txt($text), 1, 0, $align, true);
        }
        $this->Ln();
        return $y + $h;
    }
    function gridRow(array $cols, float $x, float $y, float $h = 9, bool $zebra=false): float {
        $fill = false;
        if ($zebra) { $this->SetFillColor(...ZEBRA_RGB); $fill = true; }
        $this->SetFont('Helvetica','',10);
        $this->SetXY($x,$y);
        foreach ($cols as [$text, $w, $align]) {
            $this->Cell($w, $h, pdf_txt($text), 1, 0, $align, $fill);
        }
        $this->Ln();
        return $y + $h;
    }
    function money(float $v): string { return number_format($v, 2, ',', '.').' €'; }
}

/* ---------- Encoding helper (shows € correctly) ---------- */
function pdf_txt(string $s): string {
    // FPDF core fonts expect Windows-1252 for the euro symbol
    return mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
}

/* =========================
   Main
   ========================= */
try {
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success'=>false,'message'=>'Unauthorized: Admin not logged in']);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    $post    = $payload ?: $_POST;

    if (empty($post['id']) || !ctype_digit((string)$post['id'])) {
        throw new RuntimeException('Ungültige Withdrawal-ID');
    }
    $withdrawalId = (int)$post['id'];

    // Data
    $stmt = $pdo->prepare("
        SELECT w.*, u.email, u.first_name, u.last_name, u.country,
               ob.street, ob.postal_code, ob.state, COALESCE(ob.country,u.country) AS ob_country,
               ob.bank_name, ob.account_holder, ob.iban, ob.bic, ob.lost_amount
        FROM withdrawals w
        LEFT JOIN users u ON u.id = w.user_id
        LEFT JOIN user_onboarding ob ON ob.user_id = w.user_id
        WHERE w.id = ?
        LIMIT 1
    ");
    $stmt->execute([$withdrawalId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) throw new RuntimeException('Auszahlung nicht gefunden');

    $userId   = (int)$r['user_id'];
    $fullName = trim(($r['first_name'] ?? '').' '.($r['last_name'] ?? '')) ?: 'Kunde';
    $emailTo  = $r['email'] ?? '';
    if (!$emailTo) throw new RuntimeException('Kunde hat keine E-Mail');

    #$lost = (float)($r['lost_amount'] ?? 0);
    $lost = (float)($r['amount'] ?? 0);

    $fee  = round($lost * 0.03, 2);

    $settings = $pdo->query("SELECT * FROM system_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC) ?: [];
    $siteUrl  = rtrim($settings['site_url'] ?? ((isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST']), '/');

    $company = [
        'name'   => $settings['brand_name'] ?? 'KryptoX',
        'addr1'  => $settings['company_addr1'] ?? 'Office 366',
        'addr2'  => $settings['company_addr2'] ?? 'Davidson House, Forbury Square, Reading, RG1 3EU, UK',
        'reg'    => $settings['company_reg']   ?? 'Registered Company Number: 11885733, Ref Nr: 910584',
        'iban'   => $settings['company_iban']  ?? 'DE98 7654 3210 9876 5432 10',
        'bic'    => $settings['company_bic']   ?? 'GENODEF1S10',
        'bank'   => $settings['company_bank']  ?? 'Musterbank AG',
        'holder' => $settings['company_account_holder'] ?? ($settings['brand_name'] ?? 'Kryptosuchmaschine GmbH'),
    ];

    $D = [
        'full_name'      => $fullName,
        'street'         => (string)($r['street'] ?? ''),
        'postal_code'    => (string)($r['postal_code'] ?? ''),
        'state'          => (string)($r['state'] ?? ''),
        'country'        => (string)($r['ob_country'] ?? ''),
        'bank_name'      => (string)($r['bank_name'] ?? ''),
        'account_holder' => (string)($r['account_holder'] ?: $fullName),
        'iban'           => (string)($r['iban'] ?? ''),
        'bic'            => (string)($r['bic'] ?? ''),
        'lost_amount'    => $lost,
        'service_fee'    => $fee,
        'invoice_no'     => 'KS-'.date('Y').'-'.$userId.'-'.date('YmdHis'),
        'invoice_date'   => date('d.m.Y'),
    ];

    /* ---------- Build PDF (3 pages) ---------- */
    $pdf = new PDF('P','mm','A4');
    $pdf->SetAutoPageBreak(false);
    $pdf->company = $company;
    $pdf->cust    = $D;
    // $pdf->headerMode = 'default'; // default shows both cards

    // ====== PAGE 1 ======
    $pdf->AddPage();

    // Title
    $pdf->SetFont('Helvetica','B',18);
    $pdf->SetTextColor(...BRAND_RGB);
    $pdf->SetXY(20, 56);
    $pdf->Cell(0,10, pdf_txt('Auszahlungsbestätigung & Rechnung'), 0, 1, 'C');
    $pdf->SetTextColor(0,0,0);

    // Intro
    $pdf->SetFont('Helvetica','',11);
    $pdf->SetXY(20, 56 + 10 + GAP_MD);
    $intro = "Sehr geehrte/r {$D['full_name']},\n"
           . "wir bestätigen Ihnen hiermit die bevorstehende Rückerstattung auf Ihr unten genanntes Konto. "
           . "Bitte beachten Sie, dass die Auszahlung erst nach Eingang der Servicegebühr freigeschaltet wird.";
    $pdf->MultiCell(170, 6, pdf_txt($intro), 0, 'L');
    $y = $pdf->GetY() + GAP_LG;

    // Bankverbindung (Feld | Wert)
    $pdf->sectionTitle('Bankverbindung für die Rückerstattung:', 20, $y);
    $y += GAP_MD;
    $colsKV = [
        ['Feld', 50, 'L'],
        ['Wert',120, 'L'],
    ];
    $y = $pdf->gridHeader($colsKV, 20, $y);
    $y = $pdf->gridRow([['Kontoinhaber',50,'L'], [$D['account_holder'],120,'L']], 20, $y, 9, false);
    $y = $pdf->gridRow([['IBAN',50,'L'],         [$D['iban'],120,'L']],           20, $y, 9, true);
    $y = $pdf->gridRow([['BIC',50,'L'],          [$D['bic'],120,'L']],            20, $y, 9, false);
    $y = $pdf->gridRow([['Bank',50,'L'],         [$D['bank_name'],120,'L']],      20, $y, 9, true);
    $y += GAP_LG;

    // Berechnung
    $pdf->sectionTitle('Berechnung der Rückerstattung', 20, $y);
    $y += GAP_MD;
    $colsAmt = [
        ['Beschreibung', 120, 'L'],
        ['Betrag (€)',   50,  'R'],
    ];
    $y = $pdf->gridHeader($colsAmt, 20, $y);
    $y = $pdf->gridRow([['Gesamterstattungsbetrag',120,'L'], [$pdf->money($D['lost_amount']),50,'R']], 20, $y, 9, false);
    $y = $pdf->gridRow([['Servicegebühr (3%)',120,'L'],       [$pdf->money($D['service_fee']),50,'R']], 20, $y, 9, true);
    $y += GAP_LG;

    // Hinweis zur Servicegebühr
    $pdf->sectionTitle('Hinweis zur Servicegebühr', 20, $y);
    $y += GAP_MD;
    $pdf->SetFont('Helvetica','',11);
    $pdf->SetXY(20, $y);
    $pdf->MultiCell(170, 6, pdf_txt(
        "Die Servicegebühr ist im Voraus zu begleichen, da es gesetzlich nicht erlaubt ist, Auszahlungsbeträge zu kürzen. ".
        "Nach Eingang wird die Auszahlung sofort freigeschaltet."
    ), 0, 'L');

    // ====== PAGE 2 ======
    $pdf->AddPage();

    $pdf->sectionTitle('Rechnung', 20, 50);
    $y = 50 + 8 + GAP_SM;

    // Rechnung-Meta
    $colsMeta = [
        ['Feld', 50, 'L'],
        ['Wert',120, 'L'],
    ];
    $y = $pdf->gridHeader($colsMeta, 20, $y);
    $y = $pdf->gridRow([['Rechnungsnummer',50,'L'], [$D['invoice_no'],120,'L']], 20, $y, 9, false);
    $y = $pdf->gridRow([['Rechnungsdatum',50,'L'],  [$D['invoice_date'],120,'L']], 20, $y, 9, true);
    $y = $pdf->gridRow([['Kunde',50,'L'],           [$D['full_name'],120,'L']], 20, $y, 9, false);
    $y += GAP_LG;

    // Leistungstabelle
    $colsInv = [
        ['Leistung',    120, 'L'],
        ['Betrag (EUR)', 50, 'R'],
    ];
    $y = $pdf->gridHeader($colsInv, 20, $y);
    $y = $pdf->gridRow([['Servicegebühr für Rückerstattungsauszahlung',120,'L'], [$pdf->money($D['service_fee']),50,'R']], 20, $y, 9, false);
    $y += GAP_LG;

    // Firmen-Bankverbindung
    $pdf->sectionTitle('Bankverbindung für die Zahlung der Servicegebühr', 20, $y);
    $y += GAP_MD;
    $y = $pdf->gridHeader($colsKV, 20, $y);
    $y = $pdf->gridRow([['Kontoinhaber',50,'L'], [$company['holder'],120,'L']], 20, $y, 9, false);
    $y = $pdf->gridRow([['IBAN',50,'L'],         [$company['iban'],120,'L']],   20, $y, 9, true);
    $y = $pdf->gridRow([['BIC',50,'L'],          [$company['bic'],120,'L']],    20, $y, 9, false);
    $y = $pdf->gridRow([['Bank',50,'L'],         [$company['bank'],120,'L']],   20, $y, 9, true);
    $y = $pdf->gridRow([['Verwendungszweck',50,'L'], ['Servicegebühr '.$D['invoice_no'],120,'L']], 20, $y, 9, false);
    $y += GAP_MD;

    // Abschluss
    $pdf->SetFont('Helvetica','',11);
    $pdf->SetXY(20, $y);
    $pdf->MultiCell(170, 6, pdf_txt(
        "Nach Eingang der Servicegebühr wird Ihre Rückerstattung umgehend auf das oben angegebene Konto überwiesen.\n\n".
        "Mit freundlichen Grüßen,\nIhre ".$company['name']
    ), 0, 'L');

    // ====== PAGE 3 ======
    $pdf->AddPage();
    $pdf->sectionTitle('Richtlinien zur Auszahlung & Servicegebühr', 20, 50);

    $pdf->SetFont('Helvetica','B',11);
    $pdf->SetXY(20, 50 + 8 + GAP_SM);
    $pdf->Cell(0,6, pdf_txt('1. Vorauszahlung der Servicegebühr'), 0, 1, 'L');
    $pdf->SetFont('Helvetica','',11);
    $pdf->MultiCell(170, 6, pdf_txt(
        "Die Servicegebühr in Höhe von 3 % der Rückerstattungssumme ist vor Freigabe der Auszahlung vollständig zu entrichten. ".
        "Dies dient der rechtlichen Absicherung, da eine Kürzung der Auszahlungssumme nicht gestattet ist."
    ), 0, 'L');

    $pdf->Ln(GAP_SM);
    $pdf->SetFont('Helvetica','B',11);
    $pdf->Cell(0,6, pdf_txt('2. Haftung für die Auszahlung'), 0, 1, 'L');
    $pdf->SetFont('Helvetica','',11);
    $pdf->MultiCell(170, 6, pdf_txt(
        "Die KyptoX haftet ausschließlich für die ordnungsgemäße Durchführung der Auszahlung auf ".
        "das vom Kunden angegebene Konto, sofern die Servicegebühr vollständig eingegangen ist. Für Verzögerungen ".
        "oder Fehler, die durch Drittbanken oder Zahlungsanbieter entstehen, übernimmt die KryptoX keine Haftung."
    ), 0, 'L');

    $pdf->Ln(GAP_SM);
    $pdf->SetFont('Helvetica','B',11);
    $pdf->Cell(0,6, pdf_txt('3. Transparenz und Dokumentation'), 0, 1, 'L');
    $pdf->SetFont('Helvetica','',11);
    $pdf->MultiCell(170, 6, pdf_txt(
        "Alle Transaktionen werden in Übereinstimmung mit den gesetzlichen Vorschriften dokumentiert. ".
        "Der Kunde erhält jederzeit Einblick über das geschützte Kundenportal."
    ), 0, 'L');

    $pdf->Ln(GAP_SM);
    $pdf->SetFont('Helvetica','B',11);
    $pdf->Cell(0,6, pdf_txt('4. Rechtliche Grundlagen'), 0, 1, 'L');
    $pdf->SetFont('Helvetica','',11);
    $pdf->MultiCell(170, 6, pdf_txt(
        "Die Abwicklung erfolgt nach geltendem deutschen und europäischem Recht. ".
        "Insbesondere sind die Vorschriften des Bürgerlichen Gesetzbuches (BGB) ".
        "sowie die EU-Richtlinien zur Geldwäscheprävention maßgeblich."
    ), 0, 'L');

    $pdf->Ln(GAP_SM);
    $pdf->SetFont('Helvetica','B',11);
    $pdf->Cell(0,6, pdf_txt('5. Gerichtsstand'), 0, 1, 'L');
    $pdf->SetFont('Helvetica','',11);
    $pdf->MultiCell(170, 6, pdf_txt(
        "Für sämtliche Streitigkeiten aus diesem Vertragsverhältnis ist der Sitz der KryptoX England ".
        "maßgeblich, sofern keine zwingenden gesetzlichen Regelungen entgegenstehen."
    ), 0, 'L');

    // Confidential note ONLY on this last page (bottom)
    $pdf->SetFont('Helvetica','',8);
    $pdf->SetTextColor(120,120,120);
    $pdf->SetXY(20, 270);
    $pdf->MultiCell(170, 4.6, pdf_txt('Dieses Dokument ist vertraulich und dient ausschließlich dem vorgesehenen Empfänger.'), 0, 'C');
    $pdf->SetTextColor(0,0,0);

    // Save
    $appRoot = realpath(__DIR__ . '/../../');
    $saveDir = $appRoot . '/uploads/payouts';
    if (!is_dir($saveDir)) mkdir($saveDir, 0775, true);
    $fileName = 'Auszahlungsbestaetigung_'.date('Ymd_His').'_U'.$userId.'_W'.$withdrawalId.'.pdf';
    $absPath  = $saveDir . '/' . $fileName;
    $relPath  = '../uploads/payouts/' . $fileName;
    $pdf->Output('F', $absPath);

    /* ---------- Log start ---------- */
    $tracking = bin2hex(random_bytes(16));
    $pdo->prepare("
        INSERT INTO payout_confirmation_logs
        (user_id, withdrawal_id, admin_id, email_to, subject, pdf_path, status, tracking_token, created_at)
        VALUES (?,?,?,?,?,?, 'queued', ?, NOW())
    ")->execute([
        $userId,
        $withdrawalId,
        $_SESSION['admin_id'] ?? 1,
        $emailTo,
        'Ihre Auszahlungsbestätigung & Rechnung',
        $relPath,
        $tracking
    ]);
    $logId = (int)$pdo->lastInsertId();

    /* ---------- Email from template ---------- */
    #$lost = (float)($r['amount'] ?? 0);

/* ---------- Email from template ---------- */
$tplKey = 'payout_confirmation_document_send';
$tplStmt = $pdo->prepare("SELECT id, subject, content FROM email_templates WHERE template_key = ? LIMIT 1");
$tplStmt->execute([$tplKey]);
$template = $tplStmt->fetch(PDO::FETCH_ASSOC);
if (!$template) { throw new RuntimeException("Email-Template nicht gefunden: {$tplKey}"); }
$templateId = (int)$template['id'];

$vars = [
    '{full_name}'    => $D['full_name'],
    '{invoice_no}'   => $D['invoice_no'],
    '{invoice_date}' => $D['invoice_date'],
    '{lost_amount}'  => number_format($lost, 2, ',', '.'),   // ✅ show withdrawal/lost amount first
    '{service_fee}'  => number_format((float)$D['service_fee'], 2, ',', '.'),
    '{brand_name}'   => ($settings['brand_name'] ?? 'Kryptosuchmaschine GmbH'),
];

$subject  = strtr($template['subject'], $vars);
$htmlBody = strtr($template['content'], $vars);

$trackingPixelUrl = rtrim($settings['site_url'] ?? $siteUrl, '/') . '/track.php?token=' . urlencode($tracking);
$htmlBodyToSend   = $htmlBody . '<img src="' . htmlspecialchars($trackingPixelUrl, ENT_QUOTES, 'UTF-8') . '" width="1" height="1" alt="" style="display:none;" />';

$textBody = "Guten Tag {$D['full_name']},\n"
          . "anbei erhalten Sie Ihre Auszahlungsbestätigung und die dazugehörige Rechnung.\n"
          . "Rechnungsnummer: {$D['invoice_no']}\n"
          . "Rechnungsdatum: {$D['invoice_date']}\n"
          . "Erstattungsbetrag: ".number_format($lost,2,',','.')." €\n"    // ✅ lost first
          . "Servicegebühr: ".number_format((float)$D['service_fee'],2,',','.')." €\n" // ✅ fee after
          . "Viele Grüße\n".($settings['brand_name'] ?? 'Kryptosuchmaschine GmbH');


    $smtp = $pdo->query("SELECT * FROM smtp_settings WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$smtp && !$phpMailerAvailable) {
        throw new RuntimeException('Keine SMTP-Konfiguration und kein PHPMailer verfügbar.');
    }

    $sent = false; $err = null;
    $reply = $settings['contact_email'] ?? ('no-reply@'.parse_url($siteUrl, PHP_URL_HOST));

    if ($phpMailerAvailable) {
        try {
            $mail = new PHPMailer(true);
$mail->CharSet  = 'UTF-8';   // ensure UTF-8 headers (Subject) and body
$mail->Encoding = 'base64';  // safe transfer encoding for UTF-8

            $mail->isSMTP();
            $mail->Host       = $smtp['host'] ?? '';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['username'] ?? '';
            $mail->Password   = $smtp['password'] ?? '';
            $enc              = strtolower((string)($smtp['encryption'] ?? 'tls'));
            $mail->SMTPSecure = ($enc === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($smtp['port'] ?? 587);

            $fromEmail = $smtp['from_email'] ?? ('no-reply@' . parse_url($siteUrl, PHP_URL_HOST));
            $fromName  = $smtp['from_name']  ?? ($settings['brand_name'] ?? 'ScamRecovery');
            $mail->setFrom($fromEmail, $fromName);
            $mail->addReplyTo($reply, $fromName);

            $mail->addAddress($emailTo, $fullName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBodyToSend;
            $mail->AltBody = $textBody;

            $mail->addAttachment($absPath, $fileName);
            $sent = $mail->send();
        } catch (\Throwable $e) { $err = $e->getMessage(); }
    } else {
        // mail() fallback
        $boundary = md5(uniqid());
        $headers  = "From: " . ($reply) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $htmlBodyToSend . "\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: application/pdf; name=\"$fileName\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";
        $body .= chunk_split(base64_encode(file_get_contents($absPath))) . "\r\n";
        $body .= "--$boundary--";
        $sent = @mail($emailTo, $subject, $body, $headers);
        if (!$sent) $err = 'mail() returned false';
    }

    if ($sent) {
        $pdo->prepare("UPDATE payout_confirmation_logs SET status='sent', sent_at=NOW() WHERE id=?")
            ->execute([$logId]);

        $pdo->prepare("
            INSERT INTO email_logs (template_id, recipient, subject, content, status, tracking_token)
            VALUES (?, ?, ?, ?, 'sent', ?)
        ")->execute([$templateId, $emailTo, $subject, $htmlBodyToSend, $tracking]);
    } else {
        $pdo->prepare("UPDATE payout_confirmation_logs SET status='failed', error_message=?, sent_at=NULL WHERE id=?")
            ->execute([$err ?: 'send() returned false', $logId]);

        $pdo->prepare("
            INSERT INTO email_logs (template_id, recipient, subject, content, status, tracking_token, error_message)
            VALUES (?, ?, ?, ?, 'failed', ?, ?)
        ")->execute([$templateId, $emailTo, $subject, $htmlBodyToSend, $tracking, $err ?: 'send() returned false']);
    }

    echo json_encode([
        'success' => (bool)$sent,
        'message' => $sent ? 'Auszahlungsbestätigung gesendet.' : ('E-Mail-Versand fehlgeschlagen' . ($err ? ": $err" : '')),
        'pdf'     => $relPath
    ]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
