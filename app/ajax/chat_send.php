<?php
/**
 * chat_send.php — User sends a message; AI bot responds automatically.
 */
require_once '../I.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
$uid = (int)$_SESSION['user_id'];

$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = (int)($input['session_id'] ?? 0);
$message   = trim($input['message'] ?? '');

if (!$sessionId || $message === '') {
    echo json_encode(['success'=>false,'message'=>'Invalid input']);
    exit;
}

try {
    // Verify session belongs to user and is active
    $st = $pdo->prepare("SELECT id FROM live_chat_sessions WHERE id=? AND user_id=? AND status='active'");
    $st->execute([$sessionId, $uid]);
    if (!$st->fetch()) { echo json_encode(['success'=>false,'message'=>'Session not found']); exit; }

    // Save user message
    $ins = $pdo->prepare("INSERT INTO live_chat_messages (session_id, sender_type, message, is_read) VALUES (?,?,?,0)");
    $ins->execute([$sessionId, 'user', $message]);
    $userMsgId = (int)$pdo->lastInsertId();

    // Increment unread counter for admin
    $pdo->prepare("UPDATE live_chat_sessions SET unread_admin=unread_admin+1, updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    // ── AI Bot Auto-Response ───────────────────────────────────────────────
    $botReply = _getBotReply($message);

    $ins->execute([$sessionId, 'bot', $botReply]);
    $botMsgId = (int)$pdo->lastInsertId();

    // Increment unread for admin again (bot msg also shows in admin view)
    $pdo->prepare("UPDATE live_chat_sessions SET unread_admin=unread_admin+1, updated_at=NOW() WHERE id=?")->execute([$sessionId]);

    echo json_encode([
        'success'    => true,
        'user_msg'   => ['id'=>$userMsgId,'sender_type'=>'user','message'=>$message,'is_read'=>0,'created_at'=>date('Y-m-d H:i:s')],
        'bot_msg'    => ['id'=>$botMsgId, 'sender_type'=>'bot', 'message'=>$botReply,'is_read'=>0,'created_at'=>date('Y-m-d H:i:s')],
    ]);
} catch (PDOException $e) {
    error_log('chat_send: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}

// ── Simple keyword-based AI response engine ────────────────────────────────
function _getBotReply(string $msg): string {
    $m = mb_strtolower($msg);

    if (_has($m, ['falldetail','fall detail','case','fall nummer','fallnummer','mein fall'])) {
        return "Für Ihre Falldetails navigieren Sie bitte zu **Meine Fälle** im Dashboard-Menü. Dort finden Sie alle aktiven und abgeschlossenen Fälle mit aktuellen Status-Updates, KI-Matchscores und Fortschrittsbalken.\n\nBenötigen Sie Hilfe zu einem spezifischen Fall? Nennen Sie mir die Referenznummer.";
    }
    if (_has($m, ['kyc','identit','verifizier','dokument','ausweis','id verif','passport'])) {
        return "Für die **KYC-Verifizierung** benötigen wir:\n• Gültiger Personalausweis oder Reisepass\n• Wohnsitznachweis (max. 3 Monate alt)\n• Selfie mit Ihrem Dokument\n\nLaden Sie Ihre Dokumente bitte unter **KYC / Identität** hoch. Die Überprüfung dauert in der Regel 24–48 Stunden. 📋";
    }
    if (_has($m, ['einzahl','deposit','einzahlun','geld einleg','geld überweis'])) {
        return "Für eine **Einzahlung** stehen Ihnen folgende Methoden zur Verfügung:\n• Banküberweisung (SEPA)\n• Kreditkarte / Debitkarte\n• Kryptowährungen (BTC, ETH, USDT)\n\nNavigieren Sie zu **Einzahlen** und wählen Sie Ihre bevorzugte Methode. Bei Fragen zur Verarbeitungszeit stehe ich Ihnen gerne zur Verfügung. 💳";
    }
    if (_has($m, ['auszahl','withdrawal','auszahlun','geld abheb','geld raus','abheben'])) {
        return "Für eine **Auszahlung** beachten Sie bitte:\n• KYC muss abgeschlossen sein\n• Pflichtgebühr (Regulatory Administration Fee) muss bezahlt werden\n• Bearbeitungszeit: 3–5 Werktage\n\nNavigieren Sie zu **Auszahlen** im Dashboard. Bei Problemen mit der Gebühr kontaktieren Sie bitte unseren Support direkt. 💰";
    }
    if (_has($m, ['gebühr','gebuehr','fee','pflichtgebühr','regulat','administrativ'])) {
        return "Die **Pflichtgebühr (Regulatory Administration Fee)** ist eine gesetzlich vorgeschriebene Verwaltungsgebühr gemäß:\n• 4./5. EU-Geldwäscherichtlinie (AMLD4/AMLD5)\n• MiFID II\n• FATF-Empfehlungen\n• BaFin/FCA-Anforderungen\n\nSie muss vor Freigabe der Auszahlung bezahlt werden und kann nicht nachträglich verrechnet werden. Klicken Sie auf den ℹ️-Button bei der Gebühr für mehr Details.";
    }
    if (_has($m, ['finanz','finanzie','portfolio','investit','anlage','kapital'])) {
        return "Für **Finanzfragen** empfehle ich Ihnen:\n• Überprüfen Sie Ihr Portfolio unter **Mein Konto**\n• Aktive Wiederherstellungsoperationen finden Sie im **KI-Dashboard**\n• Für Investitionsberatung wenden Sie sich an unser Finanzteam\n\nMöchten Sie zu einem bestimmten Finanzthema mehr erfahren?";
    }
    if (_has($m, ['technisch','technical','fehler','error','bug','problem','funktion','seite lädt','laden'])) {
        return "Bei **technischen Problemen** empfehle ich:\n1. Browser-Cache leeren (Strg+Shift+Del)\n2. Seite neu laden (F5)\n3. Anderen Browser testen\n4. Bei anhaltenden Problemen: Screenshot erstellen\n\nBeschreiben Sie das Problem genauer, und ich leite es an unser technisches Team weiter. 🔧";
    }
    if (_has($m, ['passwort','password','anmeld','login','zugang','konto gesperrt'])) {
        return "Bei **Anmeldeproblemen**:\n• Passwort vergessen? Nutzen Sie die **Passwort-zurücksetzen**-Funktion auf der Login-Seite\n• Konto gesperrt? Wenden Sie sich per E-Mail an unseren Support\n• 2FA-Problem? Bitte Support-Ticket erstellen\n\nKann ich Ihnen noch anderweitig helfen? 🔒";
    }
    if (_has($m, ['paket','package','upgrade','abonnement','plan','tarif','testversion','trial'])) {
        return "Unsere **Pakete & Abonnements**:\n• **Starter** – Grundlegende Wiederherstellungsfunktionen\n• **Professional** – Erweiterte KI-Analyse, Priority Support\n• **Enterprise** – Vollzugriff, dedizierter Account Manager\n\nEin Upgrade können Sie unter **Pakete** im Menü durchführen. Benötigen Sie eine Beratung zu den Paketen? 📦";
    }
    if (_has($m, ['wiederherst','recovery','rückgewinn','rückgewin','funds','gelder'])) {
        return "Der **KI-Wiederherstellungsprozess** läuft in mehreren Phasen:\n1. 🔍 Transaktionsanalyse (TX-Scanning)\n2. 🔗 Wallet-Verknüpfung & Exchange-Identifikation\n3. ⚖️ Rechtsabteilung – Demand Letter & regulatorische Eskalation\n4. ✅ Freigabe & Rücküberweisung\n\nDen aktuellen Status finden Sie unter **Rückgewonnene Mittel** oder im **KI-Dashboard**. 🤖";
    }
    if (_has($m, ['danke','dankeschön','vielen dank','thank','merci','grazie'])) {
        return "Gerne! 😊 Wenn Sie weitere Fragen haben, stehe ich jederzeit zur Verfügung. Unser Support-Team ist auch per Ticket erreichbar. Einen schönen Tag!";
    }
    if (_has($m, ['hallo','hello','hi','guten morgen','guten tag','guten abend','hey'])) {
        return "Hallo! 👋 Schön, dass Sie sich melden. Wie kann ich Ihnen helfen?\n\nWählen Sie ein Thema oder stellen Sie mir direkt Ihre Frage:\n• Falldetails\n• KYC-Hilfe\n• Einzahlungshilfe\n• Auszahlungshilfe\n• Finanzhilfe\n• Technische Hilfe";
    }
    // Default response
    return "Danke für Ihre Nachricht! Ich habe Ihre Anfrage erhalten und leite sie an unser Support-Team weiter. 📨\n\nFür schnellere Hilfe wählen Sie bitte ein Thema:\n• **Falldetails** – Informationen zu Ihren Fällen\n• **KYC-Hilfe** – Identitätsverifizierung\n• **Einzahlungshilfe** – Einzahlungsoptionen\n• **Auszahlungshilfe** – Auszahlungsprozess\n• **Finanzhilfe** – Finanzfragen\n• **Technische Hilfe** – Technische Probleme\n\nEin Mitarbeiter wird sich baldmöglichst bei Ihnen melden.";
}

function _has(string $haystack, array $needles): bool {
    foreach ($needles as $n) {
        if (str_contains($haystack, $n)) return true;
    }
    return false;
}
