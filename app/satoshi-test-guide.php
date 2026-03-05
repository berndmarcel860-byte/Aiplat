<?php
require_once 'header.php';
?>

<!-- Main Content START -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <!-- Page Header -->
                <div class="page-header">
                    <h2 class="header-title">Satoshi-Test Verifizierung</h2>
                    <div class="header-sub-title">
                        <nav class="breadcrumb">
                            <a class="breadcrumb-item" href="index.php">Dashboard</a>
                            <a class="breadcrumb-item" href="payment-methods.php">Zahlungsmethoden</a>
                            <span class="breadcrumb-item active">Satoshi-Test Guide</span>
                        </nav>
                    </div>
                </div>

                <!-- Hero Section -->
                <div class="card bg-gradient-primary text-white">
                    <div class="card-body text-center py-5">
                        <h3 class="text-white mb-3">
                            <i class="anticon anticon-safety-certificate" style="font-size: 48px;"></i>
                        </h3>
                        <h2 class="text-white">Satoshi-Test</h2>
                        <h5 class="text-white-50">Verifizierung Ihrer Bankverbindung für sichere Krypto-Auszahlungen</h5>
                    </div>
                </div>

                <!-- What is Satoshi Test -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="card-title text-white mb-0">
                            <i class="anticon anticon-experiment"></i>
                            Was ist ein Satoshi-Test?
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info border-left-4 border-info">
                            <h5 class="alert-heading"><strong>🧪 Definition</strong></h5>
                            <p class="mb-0">
                                Ein <strong>Satoshi-Test</strong> ist eine geringe Testeinzahlung (maximal €10), die dazu dient, 
                                Ihre Bankverbindung mit Ihrem Krypto-Konto zu verifizieren. Dies ermöglicht, zukünftige 
                                Auszahlungen korrekt und erfolgreich durchzuführen.
                            </p>
                        </div>
                        
                        <div class="alert alert-success border-left-4 border-success mt-3">
                            <h5 class="alert-heading"><strong>ℹ️ Wichtige Information</strong></h5>
                            <p class="mb-0">
                                Der überwiesene Betrag wird <strong>selbstverständlich Ihrem Depot gutgeschrieben</strong> 
                                und geht nicht verloren. Es handelt sich um eine reine Verifizierungsmaßnahme.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- How It Works -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h4 class="card-title text-white mb-0">
                            <i class="anticon anticon-ordered-list"></i>
                            So funktioniert der Verifizierungsprozess
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="verification-step text-center p-4 border rounded mb-3 h-100">
                                    <div class="step-number mb-3">
                                        <span class="badge badge-pill badge-primary" style="font-size: 24px; width: 50px; height: 50px; line-height: 35px;">1</span>
                                    </div>
                                    <h5><strong>Testeinzahlung</strong></h5>
                                    <p class="text-muted">
                                        Überweisen Sie einen kleinen Betrag (max. €10) zur Verifizierung Ihrer Bankverbindung.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="verification-step text-center p-4 border rounded mb-3 h-100">
                                    <div class="step-number mb-3">
                                        <span class="badge badge-pill badge-info" style="font-size: 24px; width: 50px; height: 50px; line-height: 35px;">2</span>
                                    </div>
                                    <h5><strong>Automatische Prüfung</strong></h5>
                                    <p class="text-muted">
                                        Unser System prüft automatisch die Bankverbindung und verifiziert Ihre Identität.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="verification-step text-center p-4 border rounded mb-3 h-100">
                                    <div class="step-number mb-3">
                                        <span class="badge badge-pill badge-success" style="font-size: 24px; width: 50px; height: 50px; line-height: 35px;">3</span>
                                    </div>
                                    <h5><strong>Bestätigung</strong></h5>
                                    <p class="text-muted">
                                        Nach erfolgreicher Verifizierung erhalten Sie eine Bestätigung und können Auszahlungen vornehmen.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Verification -->
                <div class="card border-warning">
                    <div class="card-header bg-warning">
                        <h4 class="card-title mb-0">
                            <i class="anticon anticon-robot"></i>
                            Erweiterte KI-Verifizierung
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-3">
                            <p class="mb-0">
                                <strong>🤖 Zusätzliche Sicherheitsmaßnahme:</strong><br>
                                Sollte Ihr Konto von Fake-Agenten erstellt worden sein oder bereits mehrere fehlgeschlagene 
                                Auszahlungsversuche aufgetreten sein, kann unsere <strong>Blockchain-KI</strong> einen zusätzlichen 
                                Verifizierungsbetrag anfordern.
                            </p>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card bg-light h-100">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">📊 Verifizierungsbetrag</h6>
                                        <h2 class="mb-0 text-primary">0,3% – 4%</h2>
                                        <p class="text-muted mb-0">des gesamten Depotwerts</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light h-100">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">🛡️ Sicherheitsmaßnahme</h6>
                                        <h2 class="mb-0 text-success">100%</h2>
                                        <p class="text-muted mb-0">wird gutgeschrieben</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-success border-left-4 border-success mt-3">
                            <p class="mb-0">
                                <strong>Wichtig:</strong> Diese Maßnahme dient der Sicherheit und der korrekten Verifizierung 
                                Ihrer Identität. Der überwiesene Betrag wird <strong>selbstverständlich Ihrem Depot gutgeschrieben</strong> 
                                und geht nicht verloren.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bank Details -->
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h4 class="card-title text-white mb-0">
                            <i class="anticon anticon-bank"></i>
                            Bankdaten für den Test
                        </h4>
                    </div>
                    <div class="card-body">
                        <p class="lead mb-4">
                            Verwenden Sie die folgenden Bankdaten für Ihre Satoshi-Test Überweisung:
                        </p>

                        <div class="bank-details-box p-4 border rounded bg-light">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="detail-item">
                                        <label class="text-muted mb-1"><small>Bankname:</small></label>
                                        <div class="detail-value h5 mb-0">Musterbank Berlin</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="detail-item">
                                        <label class="text-muted mb-1"><small>Kontoinhaber:</small></label>
                                        <div class="detail-value h5 mb-0">Max Mustermann</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="detail-item">
                                        <label class="text-muted mb-1"><small>IBAN:</small></label>
                                        <div class="detail-value h5 mb-0 font-monospace">DE89 3704 0044 0532 0130 00</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="detail-item">
                                        <label class="text-muted mb-1"><small>BIC/SWIFT:</small></label>
                                        <div class="detail-value h5 mb-0 font-monospace">COBADEFFXXX</div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="detail-item">
                                        <label class="text-muted mb-1"><small>Verwendungszweck:</small></label>
                                        <div class="detail-value h5 mb-0">
                                            Satoshi Test – Benutzer-ID 
                                            <span class="badge badge-primary"><?php echo isset($userId) ? $userId : '123456'; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info border-left-4 border-info mt-4">
                            <h6 class="alert-heading"><strong>ℹ️ Nach der Überweisung</strong></h6>
                            <p class="mb-0">
                                Sobald die Testüberweisung eingegangen ist, wird sie <strong>automatisch überprüft</strong>. 
                                Sie erhalten eine Bestätigung, wenn der Test erfolgreich abgeschlossen wurde.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Important Notice -->
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h4 class="card-title text-white mb-0">
                            <i class="anticon anticon-warning"></i>
                            Wichtiger Hinweis
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger border-left-4 border-danger">
                            <h5 class="alert-heading"><strong>⚠️ Bitte beachten Sie</strong></h5>
                            <p class="mb-0">
                                Führen Sie den Satoshi-Test nur durch, wenn Sie den Prozess <strong>vollständig verstanden haben</strong>. 
                                Bei Fragen kontaktieren Sie bitte <strong>unseren Support vor der Überweisung</strong>.
                            </p>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded mb-3">
                                    <h6><i class="anticon anticon-check-circle text-success"></i> <strong>Richtig</strong></h6>
                                    <ul class="mb-0">
                                        <li>Genauen Betrag überweisen</li>
                                        <li>Korrekte Bankdaten verwenden</li>
                                        <li>Verwendungszweck genau angeben</li>
                                        <li>Bestätigung abwarten</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded mb-3">
                                    <h6><i class="anticon anticon-close-circle text-danger"></i> <strong>Falsch</strong></h6>
                                    <ul class="mb-0">
                                        <li>Falschen Betrag senden</li>
                                        <li>Andere Bankdaten verwenden</li>
                                        <li>Verwendungszweck vergessen</li>
                                        <li>Mehrfach überweisen ohne Rücksprache</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="anticon anticon-question-circle"></i>
                            Häufig gestellte Fragen
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="faqAccordion">
                            <div class="card">
                                <div class="card-header" id="faq1">
                                    <h6 class="mb-0">
                                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse1">
                                            <i class="anticon anticon-down"></i> Warum ist der Satoshi-Test notwendig?
                                        </button>
                                    </h6>
                                </div>
                                <div id="collapse1" class="collapse" data-parent="#faqAccordion">
                                    <div class="card-body">
                                        Der Satoshi-Test dient der Verifizierung Ihrer Bankverbindung und Ihrer Identität. Dies ist eine Standardsicherheitsmaßnahme, um Betrug zu verhindern und sicherzustellen, dass Auszahlungen an die richtige Person gehen.
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header" id="faq2">
                                    <h6 class="mb-0">
                                        <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapse2">
                                            <i class="anticon anticon-down"></i> Wie lange dauert die Verifizierung?
                                        </button>
                                    </h6>
                                </div>
                                <div id="collapse2" class="collapse" data-parent="#faqAccordion">
                                    <div class="card-body">
                                        Die automatische Prüfung erfolgt in der Regel innerhalb von 1-3 Werktagen nach Eingang Ihrer Überweisung. Sie erhalten eine E-Mail-Benachrichtigung, sobald die Verifizierung abgeschlossen ist.
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header" id="faq3">
                                    <h6 class="mb-0">
                                        <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapse3">
                                            <i class="anticon anticon-down"></i> Was passiert mit dem überwiesenen Betrag?
                                        </button>
                                    </h6>
                                </div>
                                <div id="collapse3" class="collapse" data-parent="#faqAccordion">
                                    <div class="card-body">
                                        Der komplette Betrag wird <strong>zu 100% Ihrem Depot gutgeschrieben</strong>. Sie verlieren also kein Geld durch den Satoshi-Test. Es handelt sich lediglich um eine Verifizierungsmaßnahme.
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header" id="faq4">
                                    <h6 class="mb-0">
                                        <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapse4">
                                            <i class="anticon anticon-down"></i> Was passiert bei der KI-Verifizierung?
                                        </button>
                                    </h6>
                                </div>
                                <div id="collapse4" class="collapse" data-parent="#faqAccordion">
                                    <div class="card-body">
                                        Unsere KI analysiert Kontomuster, Transaktionshistorie und Sicherheitsrisiken. Bei Auffälligkeiten kann ein höherer Verifizierungsbetrag (0,3%-4% des Depotwerts) angefordert werden. Auch dieser Betrag wird vollständig gutgeschrieben.
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header" id="faq5">
                                    <h6 class="mb-0">
                                        <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapse5">
                                            <i class="anticon anticon-down"></i> Kann ich die Verifizierung umgehen?
                                        </button>
                                    </h6>
                                </div>
                                <div id="collapse5" class="collapse" data-parent="#faqAccordion">
                                    <div class="card-body">
                                        Nein, die Verifizierung ist obligatorisch für alle Auszahlungen. Dies ist eine regulatorische Anforderung und dient Ihrer Sicherheit sowie der Einhaltung von Geldwäschevorschriften (AML/KYC).
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card bg-gradient-success text-white">
                    <div class="card-body text-center py-5">
                        <h4 class="text-white mb-3">Bereit für die Verifizierung?</h4>
                        <p class="text-white-50 mb-4">Starten Sie jetzt den Satoshi-Test und verifizieren Sie Ihre Bankverbindung</p>
                        <div class="btn-group" role="group">
                            <a href="payment-methods.php" class="btn btn-light btn-lg">
                                <i class="anticon anticon-credit-card"></i> Zu den Zahlungsmethoden
                            </a>
                            <a href="mailto:support@example.com" class="btn btn-outline-light btn-lg">
                                <i class="anticon anticon-mail"></i> Support kontaktieren
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Main Content END -->

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #0ba360 0%, #3cba92 100%);
}

.border-left-4 {
    border-left: 4px solid;
}

.verification-step {
    transition: all 0.3s ease;
}

.verification-step:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.bank-details-box {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.detail-item {
    padding: 10px;
    background: white;
    border-radius: 5px;
}

.font-monospace {
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}

.accordion .btn-link {
    width: 100%;
    text-align: left;
    text-decoration: none;
    color: #333;
    font-weight: 500;
}

.accordion .btn-link:hover {
    text-decoration: none;
    color: #1890ff;
}

.accordion .card {
    border: 1px solid #e0e0e0;
    margin-bottom: 10px;
}

.accordion .card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.step-number {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}
</style>

<?php include 'footer.php'; ?>