<?php
require_once __DIR__ . '/header.php';
?>

<div class="main-content">
    <div class="container" style="max-width:680px;padding-top:40px;padding-bottom:60px;">

        <div class="card border-0 shadow-sm text-center" style="border-radius:16px;overflow:hidden;">

            <!-- Gradient header bar -->
            <div style="height:6px;background:linear-gradient(90deg,#2950a8,#2da9e3);"></div>

            <div class="card-body px-5 py-5">

                <!-- Icon -->
                <div style="width:72px;height:72px;background:linear-gradient(135deg,#28a745,#20c997);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 6px 20px rgba(40,167,69,.25);">
                    <i class="anticon anticon-check" style="color:#fff;font-size:32px;"></i>
                </div>

                <?php if (isset($_GET['satoshi']) && $_GET['satoshi'] == '1'): ?>
                    <!-- Satoshi-Test Abschluss -->
                    <h3 class="font-weight-bold mb-2" style="color:#155724;">Onboarding Abgeschlossen!</h3>
                    <p class="text-muted mb-4">
                        Herzlichen Glückwunsch! Ihr Profil wurde erfolgreich erstellt.<br>
                        Sie haben nun Zugriff auf Ihr Recovery-Dashboard.
                    </p>
                    <div class="alert alert-warning text-left mb-4" style="border-radius:10px;">
                        <i class="anticon anticon-experiment mr-1"></i>
                        <strong>Wichtig:</strong> Um Auszahlungen vornehmen zu können, müssen Sie den <strong>Satoshi-Test</strong> durchführen.
                        Weitere Informationen finden Sie in Ihrem Dashboard unter „Satoshi-Test Prozess".
                    </div>
                    <a href="index.php" class="btn btn-primary" style="border-radius:8px;padding:10px 28px;">
                        <i class="anticon anticon-dashboard mr-1"></i>Zum Dashboard
                    </a>

                <?php elseif (isset($_GET['trial']) && $_GET['trial'] == '1'): ?>
                    <!-- Trial Activation -->
                    <h3 class="font-weight-bold mb-2" style="color:#155724;">48-Stunden-Testzugang aktiviert!</h3>
                    <p class="text-muted mb-4">
                        Ihr kostenloser <strong>48-Stunden-Test</strong> wurde erfolgreich aktiviert.<br>
                        Sie haben eingeschränkten Zugriff auf unser Recovery-Dashboard.
                    </p>
                    <div class="alert alert-info text-left mb-4" style="border-radius:10px;">
                        <i class="anticon anticon-info-circle mr-1"></i>
                        <strong>Hinweis:</strong> Ihr Test läuft in <strong>48 Stunden</strong> automatisch ab.
                        Um nach dem Test alle Funktionen weiter nutzen zu können, wählen Sie bitte ein kostenpflichtiges Paket.
                    </div>
                    <!-- Countdown redirect to packages.php -->
                    <p class="text-muted mb-3" style="font-size:13px;">
                        Sie werden in <strong id="ob-countdown">5</strong> Sekunden zu den Paketen weitergeleitet…
                    </p>
                    <div class="d-flex justify-content-center gap-2" style="gap:10px;">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
                            <i class="anticon anticon-dashboard mr-1"></i>Zum Dashboard
                        </a>
                        <a href="packages.php" class="btn btn-primary btn-sm" style="border-radius:8px;">
                            <i class="anticon anticon-rocket mr-1"></i>Paket wählen
                        </a>
                    </div>
                    <script>
                    (function() {
                        var seconds = 5;
                        var el = document.getElementById('ob-countdown');
                        var timer = setInterval(function() {
                            seconds--;
                            if (el) el.textContent = seconds;
                            if (seconds <= 0) {
                                clearInterval(timer);
                                window.location.href = 'packages.php';
                            }
                        }, 1000);
                    })();
                    </script>

                <?php else: ?>
                    <!-- Normal Onboarding Complete → redirect to packages -->
                    <h3 class="font-weight-bold mb-2" style="color:#1a2a6c;">Registrierung abgeschlossen!</h3>
                    <p class="text-muted mb-3">
                        Ihre Fall- und Kontaktdaten wurden erfolgreich übermittelt.<br>
                        Unser Team wird Ihre Angaben prüfen und sich innerhalb von 24–48 Stunden bei Ihnen melden.
                    </p>

                    <!-- Next-step info box -->
                    <div class="alert d-flex align-items-start text-left mb-4" style="background:linear-gradient(135deg,#e8f4fd,#dbeafe);border:1.5px solid #93c5fd;border-radius:12px;">
                        <i class="anticon anticon-rocket mt-1 mr-3" style="color:#2950a8;font-size:18px;flex-shrink:0;"></i>
                        <div>
                            <strong style="color:#1e40af;font-size:13px;">Nächster Schritt: Wählen Sie Ihr Recovery-Paket</strong>
                            <p class="mb-0 mt-1" style="font-size:12.5px;color:#1e3a8a;">
                                Um mit der Fallbearbeitung zu beginnen und vollen Zugriff auf alle Recovery-Dienste zu erhalten,
                                wählen Sie bitte jetzt Ihr passendes Abonnementpaket.
                            </p>
                        </div>
                    </div>

                    <!-- Countdown -->
                    <p class="text-muted mb-3" style="font-size:13px;">
                        Sie werden in <strong id="ob-countdown">5</strong> Sekunden automatisch zu den Paketen weitergeleitet…
                    </p>

                    <div class="d-flex justify-content-center" style="gap:10px;">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;padding:8px 18px;">
                            <i class="anticon anticon-dashboard mr-1"></i>Zum Dashboard
                        </a>
                        <a href="packages.php" class="btn btn-primary" style="background:linear-gradient(135deg,#2950a8,#2da9e3);border:none;border-radius:8px;padding:10px 28px;font-weight:700;">
                            <i class="anticon anticon-rocket mr-1"></i>Paket wählen
                        </a>
                    </div>

                    <script>
                    (function() {
                        var seconds = 5;
                        var el = document.getElementById('ob-countdown');
                        var timer = setInterval(function() {
                            seconds--;
                            if (el) el.textContent = seconds;
                            if (seconds <= 0) {
                                clearInterval(timer);
                                window.location.href = 'packages.php';
                            }
                        }, 1000);
                    })();
                    </script>
                <?php endif; ?>

            </div><!-- /card-body -->

            <!-- Trust footer -->
            <div class="card-footer border-0 py-3" style="background:#f8f9fa;">
                <small class="text-muted">
                    <i class="anticon anticon-safety mr-1" style="color:#2950a8;"></i>
                    Ihre Daten sind sicher gespeichert und werden vertraulich behandelt.
                </small>
            </div>
        </div><!-- /card -->
    </div><!-- /container -->
</div><!-- /main-content -->

<?php require_once __DIR__ . '/footer.php'; ?>
