<?php include 'header.php'; ?>

<!-- Content Wrapper START -->
<div class="main-content">
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="p-4" style="background:linear-gradient(135deg,#0f4c81 0%,#1a6b3a 100%);border-radius:16px;color:#fff;">
                    <div class="d-flex align-items-center" style="gap:16px;">
                        <span style="font-size:40px;">❓</span>
                        <div>
                            <h3 class="mb-1 font-weight-bold">Häufig gestellte Fragen (FAQ)</h3>
                            <p class="mb-0" style="opacity:.9;font-size:14px;">Alles, was Sie über unsere KI-Plattform, Treuhandsystem, Einzahlungen und Auszahlungen wissen müssen.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick navigation -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius:12px;">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap" style="gap:8px;">
                            <a href="#faq-algorithm" class="btn btn-sm" style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border-radius:20px;">🤖 KI-Algorithmus</a>
                            <a href="#faq-deposit" class="btn btn-sm" style="background:linear-gradient(135deg,#0f4c81,#1a6b3a);color:#fff;border-radius:20px;">💳 Einzahlungen</a>
                            <a href="#faq-withdrawal" class="btn btn-sm" style="background:linear-gradient(135deg,#1a6b3a,#0f4c81);color:#fff;border-radius:20px;">💸 Auszahlungen</a>
                            <a href="#faq-escrow" class="btn btn-sm" style="background:linear-gradient(135deg,#6f42c1,#9561e2);color:#fff;border-radius:20px;">🔒 Treuhand (Escrow)</a>
                            <a href="#faq-packages" class="btn btn-sm" style="background:linear-gradient(135deg,#fd7e14,#e83e8c);color:#fff;border-radius:20px;">📦 Pakete</a>
                            <a href="#faq-security" class="btn btn-sm" style="background:linear-gradient(135deg,#20c997,#0dcaf0);color:#fff;border-radius:20px;">🛡️ Sicherheit</a>
                            <a href="#faq-refund" class="btn btn-sm" style="background:linear-gradient(135deg,#dc3545,#fd7e14);color:#fff;border-radius:20px;">↩️ Rückerstattungen</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== KI-ALGORITHMUS ===================== -->
        <div class="row mb-4" id="faq-algorithm">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="card-header border-0 p-3" style="background:linear-gradient(135deg,#2950a8,#2da9e3);">
                        <h5 class="mb-0 text-white font-weight-bold"><span style="margin-right:8px;">🤖</span>KI-Algorithmus — Wie funktioniert er?</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="accordion-algorithm">

                            <div class="faq-item border-bottom p-4">
                                <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="alg-1">
                                    <strong style="color:#2950a8;">Was ist der KI-basierte Algorithmus?</strong>
                                    <i class="anticon anticon-down text-muted" style="transition:.2s;"></i>
                                </button>
                                <div id="alg-1" class="faq-body mt-3" style="display:none;">
                                    <p>Unser KI-basierter Algorithmus analysiert Blockchain-Transaktionen, Krypto-Wallet-Aktivitäten und digitale Zahlungsflüsse in Echtzeit. Er erkennt gestohlene oder betrügerisch übertragene Gelder und verfolgt diese automatisch über mehrere Netzwerke hinweg.</p>
                                    <div class="row mt-3">
                                        <div class="col-md-4 mb-3">
                                            <div class="p-3 text-center" style="background:#f0f4ff;border-radius:10px;">
                                                <div style="font-size:28px;margin-bottom:8px;">🔍</div>
                                                <strong>Analyse</strong>
                                                <p class="mb-0 text-muted" style="font-size:12px;">Echtzeit-Scan aller verbundenen Wallets</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="p-3 text-center" style="background:#f0fff4;border-radius:10px;">
                                                <div style="font-size:28px;margin-bottom:8px;">🧠</div>
                                                <strong>KI-Verfolgung</strong>
                                                <p class="mb-0 text-muted" style="font-size:12px;">Mustererkennung über Blockchain-Netzwerke</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="p-3 text-center" style="background:#fff4f0;border-radius:10px;">
                                                <div style="font-size:28px;margin-bottom:8px;">💰</div>
                                                <strong>Rückgewinnung</strong>
                                                <p class="mb-0 text-muted" style="font-size:12px;">Automatisierte Rückführung auf Ihr Konto</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="faq-item border-bottom p-4">
                                <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="alg-2">
                                    <strong style="color:#2950a8;">Wie kann der Algorithmus gestohlene Gelder zurückholen?</strong>
                                    <i class="anticon anticon-down text-muted"></i>
                                </button>
                                <div id="alg-2" class="faq-body mt-3" style="display:none;">
                                    <p>Der Algorithmus nutzt eine Kombination aus:</p>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><span class="badge badge-primary mr-2">1</span> <strong>On-Chain-Forensik:</strong> Verfolgung von Transaktionspfaden über Bitcoin, Ethereum, Tron und weitere Netzwerke</li>
                                        <li class="mb-2"><span class="badge badge-primary mr-2">2</span> <strong>Exchange-Kooperationen:</strong> Direkte Verbindungen zu Kryptobörsen zur Einfrierung gestohlener Gelder</li>
                                        <li class="mb-2"><span class="badge badge-primary mr-2">3</span> <strong>Rechtliche Einleitung:</strong> Automatisierte Erstellung von Behördenberichten für koordinierte Rückforderungen</li>
                                        <li class="mb-2"><span class="badge badge-primary mr-2">4</span> <strong>Smart-Contract-Reversal:</strong> Technische Umkehrung bei angreifbaren DeFi-Protokollen</li>
                                    </ul>
                                    <div class="alert alert-info mt-3">
                                        <strong>💡 Wichtig:</strong> Die Erfolgsrate hängt davon ab, wie schnell der Fall gemeldet wird. Je früher, desto höher die Chancen.
                                    </div>
                                </div>
                            </div>

                            <div class="faq-item p-4">
                                <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="alg-3">
                                    <strong style="color:#2950a8;">Wie lange dauert der Rückgewinnungsprozess?</strong>
                                    <i class="anticon anticon-down text-muted"></i>
                                </button>
                                <div id="alg-3" class="faq-body mt-3" style="display:none;">
                                    <p>Die Bearbeitungszeit variiert je nach Komplexität des Falls:</p>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered" style="border-radius:8px;overflow:hidden;">
                                            <thead style="background:#f0f4ff;">
                                                <tr><th scope="col">Fall-Typ</th><th scope="col">Geschätzte Zeit</th><th scope="col">Erfolgswahrscheinlichkeit</th></tr>
                                            </thead>
                                            <tbody>
                                                <tr><td>Einfache Krypto-Überweisung</td><td>3–7 Werktage</td><td style="color:#1a6b3a;font-weight:700;">Hoch (70–90%)</td></tr>
                                                <tr><td>Börsen-Betrug</td><td>7–21 Werktage</td><td style="color:#fd7e14;font-weight:700;">Mittel (40–70%)</td></tr>
                                                <tr><td>DeFi / Smart-Contract-Betrug</td><td>14–30 Werktage</td><td style="color:#dc3545;font-weight:700;">Fallabhängig</td></tr>
                                                <tr><td>Banküberweisung / SWIFT</td><td>10–30 Werktage</td><td style="color:#fd7e14;font-weight:700;">Mittel (30–60%)</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== EINZAHLUNGEN ===================== -->
        <div class="row mb-4" id="faq-deposit">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="card-header border-0 p-3" style="background:linear-gradient(135deg,#0f4c81,#1a6b3a);">
                        <h5 class="mb-0 text-white font-weight-bold"><span style="margin-right:8px;">💳</span>Einzahlungen</h5>
                    </div>
                    <div class="card-body p-0">

                        <div class="faq-item border-bottom p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="dep-1">
                                <strong style="color:#0f4c81;">Wie mache ich eine Einzahlung?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="dep-1" class="faq-body mt-3" style="display:none;">
                                <ol>
                                    <li class="mb-2">Navigieren Sie zu <a href="deposit.php"><strong>Einzahlungen</strong></a> im Seitenmenü</li>
                                    <li class="mb-2">Klicken Sie auf <strong>„Neue Einzahlung"</strong></li>
                                    <li class="mb-2">Geben Sie den gewünschten Betrag ein (Minimum: $10,00)</li>
                                    <li class="mb-2">Wählen Sie Ihre bevorzugte Zahlungsmethode</li>
                                    <li class="mb-2">Folgen Sie den Zahlungsanweisungen und laden Sie den Zahlungsbeleg hoch</li>
                                    <li class="mb-2">Ihre Einzahlung wird automatisch auf einem <strong>Treuhandkonto</strong> gesichert</li>
                                </ol>
                            </div>
                        </div>

                        <div class="faq-item border-bottom p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="dep-2">
                                <strong style="color:#0f4c81;">Welche Zahlungsmethoden werden unterstützt?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="dep-2" class="faq-body mt-3" style="display:none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary">Kryptowährungen</h6>
                                        <ul>
                                            <li>Bitcoin (BTC)</li>
                                            <li>Ethereum (ETH)</li>
                                            <li>Tether (USDT – TRC20/ERC20)</li>
                                            <li>USD Coin (USDC)</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-success">Banküberweisung</h6>
                                        <ul>
                                            <li>SEPA-Überweisung (EU)</li>
                                            <li>SWIFT / Internationale Überweisung</li>
                                            <li>Online-Banking</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="dep-3">
                                <strong style="color:#0f4c81;">Was passiert nach der Einzahlung?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="dep-3" class="faq-body mt-3" style="display:none;">
                                <p>Nach Ihrer Einzahlung:</p>
                                <div class="d-flex flex-column" style="gap:10px;">
                                    <div class="d-flex align-items-start" style="gap:10px;">
                                        <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">1</div>
                                        <div><strong>Treuhand-Sicherung:</strong> Ihr Geld wird sofort auf einem sicheren Treuhandkonto gehalten.</div>
                                    </div>
                                    <div class="d-flex align-items-start" style="gap:10px;">
                                        <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">2</div>
                                        <div><strong>Algorithmus-Aktivierung:</strong> Unser KI-System beginnt mit der Analyse und Verfolgung Ihrer gestohlenen Gelder.</div>
                                    </div>
                                    <div class="d-flex align-items-start" style="gap:10px;">
                                        <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">3</div>
                                        <div><strong>Auszahlungsbenachrichtigung:</strong> Sie werden benachrichtigt, sobald rückgewonnene Gelder auf Ihr Konto überwiesen werden.</div>
                                    </div>
                                    <div class="d-flex align-items-start" style="gap:10px;">
                                        <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#1a6b3a,#0f4c81);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">4</div>
                                        <div><strong>Treuhand-Freigabe:</strong> Nachdem Sie Ihre Auszahlung bestätigt haben, werden die Treuhandmittel freigegeben.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== AUSZAHLUNGEN ===================== -->
        <div class="row mb-4" id="faq-withdrawal">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="card-header border-0 p-3" style="background:linear-gradient(135deg,#1a6b3a,#0f4c81);">
                        <h5 class="mb-0 text-white font-weight-bold"><span style="margin-right:8px;">💸</span>Auszahlungen</h5>
                    </div>
                    <div class="card-body p-0">

                        <div class="faq-item border-bottom p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="wd-1">
                                <strong style="color:#1a6b3a;">Wie erhalte ich meine Auszahlung?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="wd-1" class="faq-body mt-3" style="display:none;">
                                <p>Sobald unser KI-Algorithmus Gelder rückgewonnen hat, wird die Auszahlung automatisch an Ihre hinterlegte Wallet-Adresse oder Ihr Bankkonto veranlasst. Sie erhalten eine Benachrichtigung per E-Mail und im Dashboard.</p>
                                <div class="alert alert-success">
                                    <strong>✅ Bestätigung erforderlich:</strong> Nach Eingang Ihrer Auszahlung müssen Sie die <strong>Treuhand-Freigabe</strong> bestätigen, damit die Einzahlungsgebühr an uns übertragen wird.
                                </div>
                            </div>
                        </div>

                        <div class="faq-item border-bottom p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="wd-2">
                                <strong style="color:#1a6b3a;">Was tue ich nach Eingang der Auszahlung?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="wd-2" class="faq-body mt-3" style="display:none;">
                                <ol>
                                    <li class="mb-2">Überprüfen Sie den Eingang in Ihrer Wallet / Ihrem Bankkonto</li>
                                    <li class="mb-2">Gehen Sie zu <a href="deposit.php"><strong>Einzahlungen</strong></a> im Dashboard</li>
                                    <li class="mb-2">Klicken Sie auf die betreffende Einzahlung</li>
                                    <li class="mb-2">Klicken Sie auf den grünen Button <strong>„✅ Auszahlung bestätigen &amp; Treuhand freigeben"</strong></li>
                                    <li class="mb-2">Die Treuhandmittel werden automatisch freigegeben</li>
                                </ol>
                                <div class="alert alert-warning">
                                    <strong>⚠️ Wichtig:</strong> Bestätigen Sie nur, wenn Sie die Auszahlung tatsächlich erhalten haben. Bei Problemen wenden Sie sich bitte an den <a href="support.php">Support</a>.
                                </div>
                            </div>
                        </div>

                        <div class="faq-item p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="wd-3">
                                <strong style="color:#1a6b3a;">Was passiert, wenn ich keine Auszahlung erhalte?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="wd-3" class="faq-body mt-3" style="display:none;">
                                <p>Wenn Sie die erwartete Auszahlung <strong>nicht</strong> erhalten haben:</p>
                                <ul>
                                    <li class="mb-2">Warten Sie den vereinbarten Zeitraum ab (siehe Paket-Details)</li>
                                    <li class="mb-2">Kontaktieren Sie unseren <a href="support.php">Support</a> mit Ihrer Fall-Referenznummer</li>
                                    <li class="mb-2"><strong>Klicken Sie niemals auf die Freigabe-Schaltfläche</strong>, wenn Sie nichts erhalten haben</li>
                                    <li class="mb-2">Ihre Einzahlung bleibt solange sicher auf dem Treuhandkonto, bis das Problem gelöst ist</li>
                                </ul>
                                <div class="alert alert-info">
                                    <strong>🔒 Treuhand-Schutz:</strong> Ihre Einzahlung wird <strong>sofort und vollständig zurückerstattet</strong>, falls die Auszahlung nicht eintrifft oder ein Problem besteht.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== TREUHAND (ESCROW) ===================== -->
        <div class="row mb-4" id="faq-escrow">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="card-header border-0 p-3" style="background:linear-gradient(135deg,#6f42c1,#9561e2);">
                        <h5 class="mb-0 text-white font-weight-bold"><span style="margin-right:8px;">🔒</span>Treuhand (Escrow) — Sicherheit &amp; Funktionsweise</h5>
                    </div>
                    <div class="card-body p-0">

                        <div class="faq-item border-bottom p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="esc-1">
                                <strong style="color:#6f42c1;">Was ist ein Treuhandkonto (Escrow)?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="esc-1" class="faq-body mt-3" style="display:none;">
                                <p>Ein <strong>Treuhandkonto (Escrow)</strong> ist ein neutrales, gesichertes Konto, das Gelder verwahrt, bis bestimmte Bedingungen erfüllt sind. Es schützt beide Parteien bei einer Transaktion.</p>
                                <div class="row mt-3">
                                    <div class="col-md-4 mb-3">
                                        <div class="p-3" style="background:#f8f0ff;border-radius:10px;border-left:4px solid #6f42c1;">
                                            <strong>Ohne Treuhand</strong>
                                            <p class="mb-0 mt-2 text-muted" style="font-size:12px;">Sie zahlen → Risiko eines Betrugs → Kein Schutz</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="p-3" style="background:#f0fff4;border-radius:10px;border-left:4px solid #1a6b3a;">
                                            <strong>Mit Treuhand</strong>
                                            <p class="mb-0 mt-2 text-muted" style="font-size:12px;">Sie zahlen → Treuhand hält → Erst freigeben nach Bestätigung</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="p-3" style="background:#fff4f0;border-radius:10px;border-left:4px solid #fd7e14;">
                                            <strong>Bei Problemen</strong>
                                            <p class="mb-0 mt-2 text-muted" style="font-size:12px;">Treuhand → Automatische Rückerstattung an Sie</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item border-bottom p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="esc-2">
                                <strong style="color:#6f42c1;">Wie sicher ist mein Geld auf dem Treuhandkonto?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="esc-2" class="faq-body mt-3" style="display:none;">
                                <p>Ihr Geld ist auf dem Treuhandkonto vollständig geschützt:</p>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="anticon anticon-check-circle text-success mr-2"></i>AES-256 verschlüsselte Konten</li>
                                    <li class="mb-2"><i class="anticon anticon-check-circle text-success mr-2"></i>Multi-Signatur-Autorisierung für alle Transaktionen</li>
                                    <li class="mb-2"><i class="anticon anticon-check-circle text-success mr-2"></i>Gelder können nur mit Ihrer ausdrücklichen Bestätigung freigegeben werden</li>
                                    <li class="mb-2"><i class="anticon anticon-check-circle text-success mr-2"></i>Automatische Rückerstattung bei nicht erfüllten Bedingungen</li>
                                    <li class="mb-2"><i class="anticon anticon-check-circle text-success mr-2"></i>Vollständige Audit-Protokolle aller Treuhand-Transaktionen</li>
                                </ul>
                            </div>
                        </div>

                        <div class="faq-item border-bottom p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="esc-3">
                                <strong style="color:#6f42c1;">Was bedeuten die verschiedenen Treuhand-Status?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="esc-3" class="faq-body mt-3" style="display:none;">
                                <div class="table-responsive">
                                    <table class="table table-bordered" style="border-radius:8px;overflow:hidden;">
                                        <thead style="background:#f8f0ff;">
                                            <tr><th scope="col">Status</th><th scope="col">Bedeutung</th><th scope="col">Aktion</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><span class="badge badge-info">🏦 In Treuhand</span></td>
                                                <td>Ihre Einzahlung wird sicher verwahrt; Algorithmus läuft</td>
                                                <td>Warten auf Auszahlung</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-primary">✅ Verifiziert</span></td>
                                                <td>Auszahlung wurde veranlasst und verifiziert</td>
                                                <td>Bitte Eingang prüfen und bestätigen</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">🎯 Freigegeben</span></td>
                                                <td>Sie haben bestätigt — Treuhand wurde freigegeben</td>
                                                <td>Abgeschlossen</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-warning">↩️ Erstattet</span></td>
                                                <td>Einzahlung wurde vollständig zurückerstattet</td>
                                                <td>Prüfen Sie Ihr Konto</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="esc-4">
                                <strong style="color:#6f42c1;">Kann die Plattform meine Treuhandmittel ohne meine Zustimmung nehmen?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="esc-4" class="faq-body mt-3" style="display:none;">
                                <div class="alert alert-success">
                                    <strong>🔒 Absolut nicht.</strong> Die Treuhandmittel werden ausschließlich mit Ihrer ausdrücklichen Bestätigung freigegeben. Nur wenn Sie auf den Button <strong>„Auszahlung bestätigen &amp; Treuhand freigeben"</strong> klicken, werden die Mittel übertragen. Ohne diese Aktion bleiben Ihre Gelder sicher.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== PAKETE ===================== -->
        <div class="row mb-4" id="faq-packages">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="card-header border-0 p-3" style="background:linear-gradient(135deg,#fd7e14,#e83e8c);">
                        <h5 class="mb-0 text-white font-weight-bold"><span style="margin-right:8px;">📦</span>Pakete &amp; Tarife</h5>
                    </div>
                    <div class="card-body p-0">

                        <div class="faq-item border-bottom p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="pkg-1">
                                <strong style="color:#fd7e14;">Welche Pakete gibt es?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="pkg-1" class="faq-body mt-3" style="display:none;">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-0 shadow-sm text-center" style="border-radius:12px;border-top:4px solid #2da9e3 !important;">
                                            <div class="card-body p-4">
                                                <h5 class="text-primary">Starter</h5>
                                                <div style="font-size:28px;font-weight:700;color:#2950a8;">$99</div>
                                                <p class="text-muted" style="font-size:12px;">einmalig</p>
                                                <ul class="list-unstyled text-left" style="font-size:13px;">
                                                    <li>✅ Bis zu $5.000 Rückgewinnung</li>
                                                    <li>✅ 1 aktiver Fall</li>
                                                    <li>✅ E-Mail-Support</li>
                                                    <li>✅ 30 Tage Bearbeitungszeit</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-0 shadow-sm text-center" style="border-radius:12px;border-top:4px solid #1a6b3a !important;">
                                            <div class="card-body p-4">
                                                <h5 class="text-success">Professional</h5>
                                                <div style="font-size:28px;font-weight:700;color:#1a6b3a;">$299</div>
                                                <p class="text-muted" style="font-size:12px;">einmalig</p>
                                                <ul class="list-unstyled text-left" style="font-size:13px;">
                                                    <li>✅ Bis zu $50.000 Rückgewinnung</li>
                                                    <li>✅ 3 aktive Fälle</li>
                                                    <li>✅ Prioritäts-Support</li>
                                                    <li>✅ 21 Tage Bearbeitungszeit</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-0 shadow-sm text-center" style="border-radius:12px;border-top:4px solid #6f42c1 !important;">
                                            <div class="card-body p-4">
                                                <h5 style="color:#6f42c1;">Enterprise</h5>
                                                <div style="font-size:28px;font-weight:700;color:#6f42c1;">$999</div>
                                                <p class="text-muted" style="font-size:12px;">einmalig</p>
                                                <ul class="list-unstyled text-left" style="font-size:13px;">
                                                    <li>✅ Unbegrenzte Rückgewinnung</li>
                                                    <li>✅ Unbegrenzte Fälle</li>
                                                    <li>✅ Dedizierter Account-Manager</li>
                                                    <li>✅ 10 Tage Express-Bearbeitung</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="pkg-2">
                                <strong style="color:#fd7e14;">Wird das Paket zurückerstattet, wenn nichts rückgewonnen wird?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="pkg-2" class="faq-body mt-3" style="display:none;">
                                <p>Ja. Dank unserem Treuhandsystem:</p>
                                <ul>
                                    <li class="mb-2">Ihre Einzahlung wird nur dann freigegeben, wenn Sie Ihre Auszahlung bestätigt haben</li>
                                    <li class="mb-2">Wenn keine Rückgewinnung erfolgt, werden Ihre Treuhandmittel vollständig erstattet</li>
                                    <li class="mb-2">Keine versteckten Gebühren, keine Risiken für Sie</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== SICHERHEIT ===================== -->
        <div class="row mb-4" id="faq-security">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="card-header border-0 p-3" style="background:linear-gradient(135deg,#20c997,#0dcaf0);">
                        <h5 class="mb-0 text-white font-weight-bold"><span style="margin-right:8px;">🛡️</span>Plattform-Sicherheit</h5>
                    </div>
                    <div class="card-body p-0">

                        <div class="faq-item border-bottom p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="sec-1">
                                <strong style="color:#20c997;">Wie sind meine Daten geschützt?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="sec-1" class="faq-body mt-3" style="display:none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="list-unstyled">
                                            <li class="mb-2"><i class="anticon anticon-lock text-success mr-2"></i>SSL/TLS-Verschlüsselung (256-bit)</li>
                                            <li class="mb-2"><i class="anticon anticon-lock text-success mr-2"></i>Zwei-Faktor-Authentifizierung (2FA)</li>
                                            <li class="mb-2"><i class="anticon anticon-lock text-success mr-2"></i>GDPR-konformer Datenschutz</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-unstyled">
                                            <li class="mb-2"><i class="anticon anticon-lock text-success mr-2"></i>Regelmäßige Sicherheitsaudits</li>
                                            <li class="mb-2"><i class="anticon anticon-lock text-success mr-2"></i>KYC-Verifizierung aller Nutzer</li>
                                            <li class="mb-2"><i class="anticon anticon-lock text-success mr-2"></i>Vollständige Transaktions-Protokolle</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="sec-2">
                                <strong style="color:#20c997;">Was soll ich tun, wenn ich verdächtige Aktivitäten bemerke?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="sec-2" class="faq-body mt-3" style="display:none;">
                                <ol>
                                    <li class="mb-2">Ändern Sie sofort Ihr Passwort unter <a href="security.php">Sicherheitseinstellungen</a></li>
                                    <li class="mb-2">Aktivieren Sie die Zwei-Faktor-Authentifizierung</li>
                                    <li class="mb-2">Wenden Sie sich umgehend an unseren <a href="support.php">Support</a></li>
                                    <li class="mb-2">Überprüfen Sie Ihre aktiven Sitzungen und beenden Sie unbekannte</li>
                                </ol>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== RÜCKERSTATTUNGEN ===================== -->
        <div class="row mb-4" id="faq-refund">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="card-header border-0 p-3" style="background:linear-gradient(135deg,#dc3545,#fd7e14);">
                        <h5 class="mb-0 text-white font-weight-bold"><span style="margin-right:8px;">↩️</span>Rückerstattungen</h5>
                    </div>
                    <div class="card-body p-0">

                        <div class="faq-item border-bottom p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="ref-1">
                                <strong style="color:#dc3545;">Wie beantrage ich eine Rückerstattung?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="ref-1" class="faq-body mt-3" style="display:none;">
                                <p>Eine Rückerstattung erfolgt automatisch wenn:</p>
                                <ul>
                                    <li class="mb-2">Die vereinbarte Auszahlung nicht innerhalb des Bearbeitungszeitraums erfolgt</li>
                                    <li class="mb-2">Sie keinen Erhalt bestätigen (Treuhand bleibt gesichert)</li>
                                    <li class="mb-2">Ein technisches Problem vorliegt, das wir nicht beheben können</li>
                                </ul>
                                <p>Für manuelle Rückerstattungsanfragen: <a href="support.php" class="btn btn-sm btn-outline-danger">Support kontaktieren</a></p>
                            </div>
                        </div>

                        <div class="faq-item p-4">
                            <button class="faq-toggle btn btn-link w-100 text-left p-0 d-flex justify-content-between align-items-center" data-target="ref-2">
                                <strong style="color:#dc3545;">Wie lange dauert eine Rückerstattung?</strong>
                                <i class="anticon anticon-down text-muted"></i>
                            </button>
                            <div id="ref-2" class="faq-body mt-3" style="display:none;">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" style="border-radius:8px;overflow:hidden;">
                                        <thead style="background:#fff0f0;">
                                            <tr><th scope="col">Methode</th><th scope="col">Bearbeitungszeit</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr><td>Kryptowährung (BTC/ETH/USDT)</td><td>1–3 Werktage</td></tr>
                                            <tr><td>SEPA-Überweisung</td><td>3–5 Werktage</td></tr>
                                            <tr><td>SWIFT/International</td><td>5–10 Werktage</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Contact / Support CTA -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="p-4 text-center" style="background:linear-gradient(135deg,#2950a8,#1a6b3a);border-radius:16px;color:#fff;">
                    <h5 class="font-weight-bold mb-2">Haben Sie weitere Fragen?</h5>
                    <p class="mb-3" style="opacity:.9;">Unser Support-Team steht Ihnen jederzeit zur Verfügung.</p>
                    <a href="support.php" class="btn btn-light btn-lg" style="border-radius:10px;font-weight:600;color:#0f4c81;">
                        <i class="anticon anticon-customer-service mr-2"></i>Support kontaktieren
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- Content Wrapper END -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.faq-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var body = document.getElementById(targetId);
            var icon = this.querySelector('.anticon');
            if (!body) return;
            var isOpen = body.style.display !== 'none';
            body.style.display = isOpen ? 'none' : 'block';
            if (icon) {
                icon.classList.toggle('anticon-down', isOpen);
                icon.classList.toggle('anticon-up', !isOpen);
            }
        });
    });
});
</script>

<?php include 'footer.php'; ?>
