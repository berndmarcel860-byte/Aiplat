<?php
require_once __DIR__ . '/header.php';
?>

<div class="page-container">
    <div class="main-content">
        <div class="card">
            <div class="card-body text-center">

                <i class="anticon anticon-check-circle text-success" style="font-size: 56px;"></i>

                <?php if (isset($_GET['satoshi']) && $_GET['satoshi'] == '1'): ?>
                    <!-- ✅ SATOSHI TEST COMPLETION MESSAGE -->
                    <h3 class="m-t-20 text-success">Onboarding Abgeschlossen!</h3>
                    <p class="text-muted mt-3">
                        Herzlichen Glückwunsch! Ihr Profil wurde erfolgreich erstellt.<br>
                        Sie haben nun Zugriff auf Ihr Recovery-Dashboard.
                    </p>
                    <div class="alert alert-warning text-left mt-4" style="max-width: 600px; margin: 0 auto;">
                        <i class="anticon anticon-experiment"></i>
                        <strong>Wichtig:</strong> Um Auszahlungen vornehmen zu können, müssen Sie den <strong>Satoshi-Test</strong> durchführen.<br>
                        Weitere Informationen finden Sie in Ihrem Dashboard unter "Satoshi-Test Prozess".
                    </div>
                    <a href="index.php" class="btn btn-primary mt-4">
                        <i class="anticon anticon-dashboard mr-2"></i>Zum Dashboard
                    </a>

                <?php elseif (isset($_GET['trial']) && $_GET['trial'] == '1'): ?>
                    <!-- ✅ TRIAL COMPLETION MESSAGE -->
                    <h3 class="m-t-20 text-success">48H Test Access Activated!</h3>
                    <p class="text-muted mt-3">
                        Congratulations! Your free <strong>48-hour trial</strong> has been successfully activated.<br>
                        You now have limited access to our recovery dashboard and services.
                    </p>
                    <div class="alert alert-info text-left mt-4" style="max-width: 600px; margin: 0 auto;">
                        <i class="anticon anticon-info-circle"></i>
                        <strong>Important:</strong> Your trial will automatically expire in <strong>48 hours</strong>.<br>
                        To continue using all recovery features after the trial, please upgrade to a full package anytime.
                    </div>
                    <a href="index.php" class="btn btn-primary mt-4">
                        Go to Dashboard
                    </a>

                <?php else: ?>
                    <!-- ✅ NORMAL PAID PACKAGE MESSAGE -->
                    <h3 class="m-t-20">Onboarding Completed!</h3>
                    <p class="text-muted">
                        Your case and bank details have been submitted successfully.<br>
                        Our team will review your information and contact you within 24–48 hours.
                    </p>
                    <a href="index.php" class="btn btn-primary mt-4">
                        Go to Dashboard
                    </a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/footer.php';
?>
