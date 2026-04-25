<?php
/**
 * chat_send.php — User sends a message; AI bot responds automatically (if enabled).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';
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

    // ── Check admin AI auto-reply toggle ─────────────────────────────────────
    $aiEnabled = true;
    try {
        $aiSt = $pdo->prepare("SELECT chat_ai_enabled FROM system_settings WHERE id=1 LIMIT 1");
        $aiSt->execute();
        $aiRow = $aiSt->fetch();
        if ($aiRow !== false && isset($aiRow['chat_ai_enabled'])) {
            $aiEnabled = (bool)(int)$aiRow['chat_ai_enabled'];
        }
    } catch (PDOException $e) { /* column not yet added – default enabled */ }

    // ── Suppress bot if a live admin has replied in the last 15 minutes ──────
    if ($aiEnabled) {
        $adminActiveSt = $pdo->prepare(
            "SELECT id FROM live_chat_messages
             WHERE session_id=? AND sender_type='admin'
               AND created_at >= NOW() - INTERVAL 15 MINUTE
             LIMIT 1"
        );
        $adminActiveSt->execute([$sessionId]);
        if ($adminActiveSt->fetch()) {
            $aiEnabled = false; // live agent is handling — skip bot
        }
    }

    $botMsgData = null;

    if ($aiEnabled) {
        // ── Detect live-agent request ────────────────────────────────────────
        $ml = mb_strtolower($message);
        $liveAgentRequest = _hasKeyword($ml, [
            'live agent','live support','human agent','live-agent','live-support',
            'echter agent','echten berater','menschlicher berater','echten mitarbeiter',
            'mit mitarbeiter','zum mitarbeiter','agent verbinden','echten support',
            'person sprechen','mensch sprechen',
        ]);
        if ($liveAgentRequest) {
            // Flag session so admin sees it requires live takeover
            try {
                $pdo->prepare("UPDATE live_chat_sessions SET topic='live_agent_requested', updated_at=NOW() WHERE id=?")
                    ->execute([$sessionId]);
            } catch (PDOException $e) { /* ignore */ }
        }

        // ── AI Bot Auto-Response ─────────────────────────────────────────────
        $botReply = _getBotReply($message, $liveAgentRequest);

        $ins->execute([$sessionId, 'bot', $botReply]);
        $botMsgId = (int)$pdo->lastInsertId();

        // Increment unread for admin (bot msg also visible in admin view)
        $pdo->prepare("UPDATE live_chat_sessions SET unread_admin=unread_admin+1, updated_at=NOW() WHERE id=?")->execute([$sessionId]);

        $botMsgData = ['id'=>$botMsgId,'sender_type'=>'bot','message'=>$botReply,'is_read'=>0,'created_at'=>date('Y-m-d H:i:s')];
    }

    echo json_encode([
        'success'  => true,
        'user_msg' => ['id'=>$userMsgId,'sender_type'=>'user','message'=>$message,'is_read'=>0,'created_at'=>date('Y-m-d H:i:s')],
        'bot_msg'  => $botMsgData,
    ]);
} catch (PDOException $e) {
    error_log('chat_send: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}

// ── Professional AI response engine ───────────────────────────────────────────
function _getBotReply(string $msg, bool $liveAgentRequest = false): string {
    $m = mb_strtolower($msg);

    // ── Live-agent escalation ────────────────────────────────────────────────
    if ($liveAgentRequest) {
        return "Vielen Dank für Ihre Geduld. 🔄 **Wir leiten Sie sofort an einen Live-Agenten weiter.**\n\n" .
               "Ein Mitglied unseres qualifizierten Support-Teams wird Ihre Anfrage in Kürze persönlich übernehmen.\n\n" .
               "⏳ **Geschätzte Wartezeit:** 2–5 Minuten\n\n" .
               "_Bitte verlassen Sie den Chat nicht. Ihre Konversation wird vollständig gespeichert._";
    }

    // ── Package / subscription requirements ─────────────────────────────────
    if (_hasKeyword($m, ['paket','package','upgrade','abonnement','plan','tarif','testversion','trial',
                          'subscription','mitgliedschaft','aktivier','freischalten','zugang','account status'])) {
        return "Um unsere vollständigen KI-Wiederherstellungsdienste nutzen zu können, ist ein **aktives Abonnement** erforderlich.\n\n" .
               "**Warum ist ein aktives Paket notwendig?**\n" .
               "Gemäß den Anforderungen unserer internationalen Finanzpartner (FCA, BaFin, MiFID II) und den FATF-Empfehlungen zur Bekämpfung von Geldwäsche (AML) sind alle Dienstleistungen — einschließlich Auszahlungen — nur für verifizierte, aktive Konten zugänglich. Dies dient dem Schutz Ihrer Gelder und der regulatorischen Compliance.\n\n" .
               "**Verfügbare Pakete:**\n" .
               "• 🔹 **Starter** — Grundlegende KI-Analyse & Falleinreichung\n" .
               "• 🔷 **Professional** — Erweiterte Forensik, Priority-Support, Auszahlungszugang\n" .
               "• 💎 **Enterprise** — Vollständiger Zugang, dedizierter Account Manager, beschleunigtes Verfahren\n\n" .
               "Ein Upgrade können Sie direkt unter **Pakete & Tarife** im Menü durchführen. Benötigen Sie eine persönliche Beratung? 📦";
    }

    // ── Withdrawal fee (3% Regulatory Administration Fee) ────────────────────
    if (_hasKeyword($m, ['gebühr','gebuehr','fee','pflichtgebühr','regulat','administrativ','3%','drei prozent',
                          'vorauszahlung','advance','warum gebühr','warum fee','gebühr zahlen','fee zahlen'])) {
        return "Die **Regulatory Administration Fee (Pflichtgebühr)** ist eine gesetzlich vorgeschriebene Verwaltungsgebühr, die vor der Freigabe jeder Auszahlung entrichtet werden muss.\n\n" .
               "**Rechtliche Grundlage:**\n" .
               "• EU-Geldwäscherichtlinien (AMLD4 / AMLD5 / AMLD6)\n" .
               "• MiFID II — Richtlinie über Märkte für Finanzinstrumente\n" .
               "• FATF Recommendation 16 (Wire Transfer Regulation / TFR)\n" .
               "• BaFin / FCA Compliance-Anforderungen\n\n" .
               "**Warum muss sie im Voraus bezahlt werden?**\n" .
               "Gemäß den AML-Vorschriften unserer internationalen Finanzpartner darf die Gebühr **nicht** nachträglich mit dem Auszahlungsbetrag verrechnet werden. Sie sichert die regulatorische Abwicklung und die Identitätsprüfung des Empfängerkontos ab.\n\n" .
               "**Gebührensatz:** 3 % des Auszahlungsbetrags (Mindestgebühr gemäß Regulierungsvorschrift)\n\n" .
               "_Die Zahlung bestätigt Ihre Compliance und gibt die Transaktion für die finale Bankfreigabe frei._ 🏦\n\n" .
               "Benötigen Sie weitere Informationen zu den Zahlungsoptionen?";
    }

    // ── Case / Fall details ───────────────────────────────────────────────────
    if (_hasKeyword($m, ['falldetail','fall detail','case','fall nummer','fallnummer','mein fall','case details',
                          'status meines falls','wo ist mein fall','meinen fall','fall einsehen'])) {
        return "Alle Informationen zu Ihren aktiven und abgeschlossenen Fällen finden Sie im **KI-Dashboard** und unter **Meine Fälle**.\n\n" .
               "**Verfügbare Fallinformationen:**\n" .
               "• 🔍 Aktueller KI-Analysestatus & Matchscore\n" .
               "• 📊 Transaktionsscanning-Fortschritt\n" .
               "• ⚖️ Rechtsabteilung — Maßnahmenprotokoll (Demand Letter, Behördeneinschaltung)\n" .
               "• 💰 Gebuchte Rückgewinnungsbeträge\n\n" .
               "Für detaillierte Forensikberichte navigieren Sie zu **Rückgewonnene Mittel** in der Seitenleiste.\n\n" .
               "Bitte teilen Sie mir Ihre Fallreferenznummer mit, falls Sie spezifische Informationen benötigen. 📁";
    }

    // ── KYC verification ──────────────────────────────────────────────────────
    if (_hasKeyword($m, ['kyc','identit','verifizier','dokument','ausweis','id verif','passport','reisepass',
                          'personalausweis','identifizier','verificat','id check'])) {
        return "Die **KYC-Verifizierung (Know Your Customer)** ist ein gesetzlich vorgeschriebener Prozess gemäß AMLD5, FATF und BaFin-Anforderungen zum Schutz vor Identitätsmissbrauch und Geldwäsche.\n\n" .
               "**Benötigte Dokumente:**\n" .
               "• ✅ Gültiger Lichtbildausweis (Personalausweis oder Reisepass)\n" .
               "• ✅ Adressnachweis (Kontoauszug oder Stromrechnung, max. 3 Monate alt)\n" .
               "• ✅ Selfie mit Ihrem Ausweisdokument\n\n" .
               "**So reichen Sie ein:**\n" .
               "1. Menü → **KYC / Identität**\n" .
               "2. Dokumente hochladen (JPG, PNG oder PDF, max. 10 MB)\n" .
               "3. Bestätigung abwarten — Überprüfung dauert in der Regel **24–48 Stunden**\n\n" .
               "_Ohne gültige KYC-Verifizierung können gemäß Finanzregulierung keine Auszahlungen durchgeführt werden._ 📋";
    }

    // ── Deposit / Einzahlung ──────────────────────────────────────────────────
    if (_hasKeyword($m, ['einzahl','deposit','einzahlun','geld einleg','geld überweis','einzahlen','how to deposit'])) {
        return "Eine **Einzahlung** ist jederzeit möglich und wird sicher über unsere regulierten Zahlungspartner abgewickelt.\n\n" .
               "**Verfügbare Einzahlungsmethoden:**\n" .
               "• 🏦 Banküberweisung (SEPA — innerhalb der EU kostenfrei)\n" .
               "• 💳 Kreditkarte / Debitkarte (Visa, Mastercard)\n" .
               "• ₿ Kryptowährungen (BTC, ETH, USDT-TRC20 / ERC20)\n\n" .
               "**Einzahlungsschritte:**\n" .
               "1. Menü → **Einzahlen**\n" .
               "2. Betrag und Zahlungsmethode wählen\n" .
               "3. Zahlungsinformationen folgen\n\n" .
               "Alle Transaktionen werden gemäß **PCI-DSS** und **ISO 27001** Standards verarbeitet.\n\n" .
               "Bei Fragen zur Verarbeitungszeit oder Währungskonversion stehe ich gerne zur Verfügung. 💳";
    }

    // ── Withdrawal / Auszahlung ───────────────────────────────────────────────
    if (_hasKeyword($m, ['auszahl','withdrawal','auszahlun','geld abheb','geld raus','abheben','auszahlen',
                          'geld zurück','geld rückbuchen','how to withdraw'])) {
        return "Für eine erfolgreiche **Auszahlung** müssen folgende Voraussetzungen erfüllt sein:\n\n" .
               "**Voraussetzungen gemäß Finanzregulierung:**\n" .
               "1. ✅ **Aktives Paket** — Auszahlungen sind ausschließlich für aktive Abonnements verfügbar\n" .
               "2. ✅ **KYC-Verifizierung** — Vollständige Identitätsprüfung abgeschlossen\n" .
               "3. ✅ **Regulatory Administration Fee** — 3 % des Auszahlungsbetrags im Voraus beglichen\n\n" .
               "**Warum diese Anforderungen?**\n" .
               "Unsere internationalen Finanzpartner sind verpflichtet, alle Transaktionen gemäß AMLD5, MiFID II und FATF-Empfehlungen zu prüfen. Dies schützt Sie vor unberechtigten Zugriffen und sichert die Compliance.\n\n" .
               "**Bearbeitungszeit nach Freigabe:** 3–5 Werktage (SEPA: 1–2 Werktage)\n\n" .
               "Navigieren Sie zu **Auszahlen** im Dashboard. Benötigen Sie Hilfe mit der Pflichtgebühr? 💰";
    }

    // ── Financial / portfolio questions ──────────────────────────────────────
    if (_hasKeyword($m, ['finanz','finanzie','portfolio','investit','anlage','kapital','vermögen','returns',
                          'rendite','gewinn','verlust','gelder wiederherstellen'])) {
        return "Für eine professionelle Übersicht Ihrer **Finanzdaten** stehen Ihnen folgende Bereiche zur Verfügung:\n\n" .
               "• 📊 **KI-Dashboard** — Echtzeit-Analyse Ihrer Wiederherstellungsoperationen\n" .
               "• 💼 **Mein Konto** — Kontosaldo, Transaktionshistorie\n" .
               "• 📁 **Rückgewonnene Mittel** — Detaillierter Bericht inkl. Rechtsabteilungsprotokoll\n\n" .
               "**Wichtiger Hinweis zur Vermögensverwaltung:**\n" .
               "Alle Wiederherstellungsmaßnahmen werden in Übereinstimmung mit den Richtlinien unserer zugelassenen Finanzpartner (FCA-reguliert, BaFin-konform) durchgeführt. Rückgewonnene Mittel werden ausschließlich auf das verifizierte Referenzkonto überwiesen.\n\n" .
               "Haben Sie eine spezifische Frage zu Ihrer Wiederherstellungsoperation? 📈";
    }

    // ── Recovery / AI algorithm ───────────────────────────────────────────────
    if (_hasKeyword($m, ['wiederherst','recovery','rückgewinn','rückgewin','funds','gelder',
                          'algorithmus','ki analyse','blockchain','transaktion suche','tx scan'])) {
        return "Unser **KI-Wiederherstellungsalgorithmus** nutzt fortschrittliche Blockchain-Forensik-Technologie:\n\n" .
               "**Analysephasen:**\n" .
               "1. 🔍 **TX-Scanning** — Analyse von Millionen Blockchain-Transaktionen\n" .
               "2. 🔗 **Wallet-Forensik** — Verknüpfung verbundener Adressen & Exchanges\n" .
               "3. 🏦 **Exchange-Identifikation** — Lokalisierung der Zielwallet bei regulierten Börsen\n" .
               "4. ⚖️ **Rechtliche Maßnahmen** — Demand Letter durch unsere Rechtsabteilung\n" .
               "5. 🏛️ **Regulatorische Eskalation** — Einschaltung zuständiger Behörden (BaFin/FCA/Interpol)\n" .
               "6. ✅ **Freigabe & Rücküberweisung** — Verifizierte Rückbuchung auf Ihr Konto\n\n" .
               "Den aktuellen Fortschritt Ihrer Fälle finden Sie unter **Rückgewonnene Mittel** oder im **KI-Dashboard**. 🤖";
    }

    // ── Technical issues ──────────────────────────────────────────────────────
    if (_hasKeyword($m, ['technisch','technical','fehler','error','bug','problem','funktion','seite lädt',
                          'laden','nicht angezeigt','läuft nicht','funktioniert nicht','seite leer'])) {
        return "Bei **technischen Problemen** empfehlen wir folgende Schritte:\n\n" .
               "**Sofortmaßnahmen:**\n" .
               "1. 🔄 Browser-Cache leeren (Strg+Shift+Del) und Seite neu laden\n" .
               "2. 🌐 Anderen Browser testen (Chrome, Firefox, Edge)\n" .
               "3. 📱 Mobilen Browser testen (iOS Safari / Android Chrome)\n" .
               "4. 🔒 VPN deaktivieren, falls aktiv\n\n" .
               "**Support-Ticket erstellen:**\n" .
               "Falls das Problem weiterhin besteht, erstellen Sie bitte ein Support-Ticket unter **Hilfe & Support** mit:\n" .
               "• Screenshot des Fehlers\n" .
               "• Verwendeter Browser & Version\n" .
               "• Uhrzeit des Auftretens\n\n" .
               "Unser technisches Team antwortet innerhalb von 24 Stunden. 🔧";
    }

    // ── Login / account access ────────────────────────────────────────────────
    if (_hasKeyword($m, ['passwort','password','anmeld','login','zugang','konto gesperrt','2fa','otp',
                          'zwei-faktor','code eingeben','anmeldung','einloggen'])) {
        return "Bei **Zugangs- und Authentifizierungsproblemen:**\n\n" .
               "• 🔑 **Passwort vergessen?** — Nutzen Sie die **Passwort zurücksetzen**-Funktion auf der Login-Seite\n" .
               "• 🔐 **Konto gesperrt?** — Wenden Sie sich per E-Mail an unseren Sicherheits-Support\n" .
               "• 📱 **OTP / 2FA-Problem?** — Stellen Sie sicher, dass Ihre Uhr korrekt synchronisiert ist; alternativ Support-Ticket erstellen\n" .
               "• 🌐 **Neues Gerät?** — Aus Sicherheitsgründen (AML-Compliance) wird bei neuen IP-Adressen eine erneute OTP-Verifizierung angefordert\n\n" .
               "_Die Zwei-Faktor-Authentifizierung ist eine gesetzlich empfohlene Sicherheitsmaßnahme gemäß AMLD5._ 🔒";
    }

    // ── AML / compliance general inquiry ─────────────────────────────────────
    if (_hasKeyword($m, ['aml','geldwäsche','compliance','regulier','gesetz','vorschrift','bafin','fca',
                          'fatf','mifid','richtlinie','regulatorisch'])) {
        return "Unser Unternehmen unterliegt strengen **regulatorischen Anforderungen** unserer internationalen Finanzpartner:\n\n" .
               "**Angewandte Richtlinien & Standards:**\n" .
               "• 🇪🇺 EU-Geldwäscherichtlinien (AMLD4 / AMLD5 / AMLD6)\n" .
               "• 📊 MiFID II — EU-Richtlinie über Finanzinstrumentenmärkte\n" .
               "• 🌍 FATF Recommendations (inkl. TFR — Travel Rule)\n" .
               "• 🇬🇧 FCA (Financial Conduct Authority) — UK Compliance\n" .
               "• 🇩🇪 BaFin — Bundesanstalt für Finanzdienstleistungsaufsicht\n\n" .
               "Diese Vorschriften schützen Ihre Gelder, verhindern Geldwäsche und sichern die Integrität aller Transaktionen.\n\n" .
               "Für spezifische Compliance-Fragen steht unser Rechts- und Compliance-Team zur Verfügung. ⚖️";
    }

    // ── Greetings ─────────────────────────────────────────────────────────────
    if (_hasKeyword($m, ['hallo','hello','hi ','guten morgen','guten tag','guten abend','hey','servus','grüß gott'])) {
        return "Willkommen beim **Crypto Finanze AI Support**! 👋\n\n" .
               "Ich bin Ihr KI-Assistent und stehe Ihnen bei allen Fragen rund um unsere Wiederherstellungsdienste zur Verfügung.\n\n" .
               "**Wie kann ich Ihnen helfen?**\n" .
               "• 📁 Falldetails & Status\n" .
               "• 🪪 KYC-Verifizierung\n" .
               "• 💳 Einzahlung\n" .
               "• 💰 Auszahlung & Gebühren\n" .
               "• 📦 Pakete & Abonnements\n" .
               "• ⚖️ AML / Compliance\n" .
               "• 🔧 Technische Hilfe\n\n" .
               "_Für persönliche Beratung durch einen Live-Agenten schreiben Sie bitte: **\"Live Agent\"**_ 🤖";
    }

    // ── Thanks ────────────────────────────────────────────────────────────────
    if (_hasKeyword($m, ['danke','dankeschön','vielen dank','thank','merci','grazie','alles klar'])) {
        return "Gerne! 😊 Es freut mich, Ihnen helfen zu können.\n\n" .
               "Sollten Sie weitere Fragen haben, stehe ich jederzeit zur Verfügung. Unser Support-Team ist auch über ein **Support-Ticket** erreichbar.\n\n" .
               "_Crypto Finanze AI — Ihr vertrauenswürdiger Partner für professionelles Vermögensrecovery._ ✅";
    }

    // ── Default response ──────────────────────────────────────────────────────
    return "Ihre Nachricht wurde empfangen. Ich analysiere Ihre Anfrage. 📨\n\n" .
           "**Wählen Sie ein Thema für eine sofortige Antwort:**\n" .
           "• 📁 **Falldetails** — Status Ihrer Wiederherstellungsfälle\n" .
           "• 🪪 **KYC-Hilfe** — Identitätsverifizierung & Dokumente\n" .
           "• 💳 **Einzahlung** — Zahlungsmethoden & Prozesse\n" .
           "• 💰 **Auszahlung** — Anforderungen & Regulatory Fee\n" .
           "• 📦 **Pakete** — Abonnements & Zugangsvoraussetzungen\n" .
           "• ⚖️ **AML / Compliance** — Regulatorische Fragen\n" .
           "• 🔧 **Technisch** — Technische Probleme\n\n" .
           "_Für sofortige Hilfe durch einen Mitarbeiter schreiben Sie: **\"Live Agent\"**_";
}

function _hasKeyword(string $haystack, array $needles): bool {
    foreach ($needles as $n) {
        if (str_contains($haystack, $n)) return true;
    }
    return false;
}
