<?php if (!isset($siteSettings)) { include_once __DIR__ . '/includes/site_settings.php'; } ?>
<!-- ========================================================= -->
<!-- Footer – Novalnet AI -->
<!-- ========================================================= -->
<footer class="footer bg-light">
    <div class="container">
        <div class="row py-5">
            <!-- Company Info -->
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($siteSettings['brand_name']); ?></h5>
                <p class="text-muted mb-3">
                    KI-gestützte Blockchain-Analyse für sichere Krypto-Wiederherstellung. 
                    BaFin-lizenziert und nach europäischen Compliance-Standards.
                </p>
                <div class="mb-2">
                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                    <small><?php echo htmlspecialchars($siteSettings['company_address']); ?></small>
                </div>
                <div class="mb-2">
                    <i class="fas fa-envelope me-2 text-primary"></i>
                    <small><?php echo htmlspecialchars($siteSettings['contact_email']); ?></small>
                </div>
                <div class="mb-2">
                    <i class="fas fa-shield-alt me-2 text-primary"></i>
                    <small>BaFin-Reg.: <?php echo htmlspecialchars($siteSettings['fca_reference_number']); ?></small>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="col-lg-2 col-md-4 mb-4 mb-lg-0">
                <h6 class="fw-bold mb-3">Unternehmen</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="ueber-uns.php" class="text-muted text-decoration-none">Über uns</a></li>
                    <li class="mb-2"><a href="mission.php" class="text-muted text-decoration-none">Unsere Mission</a></li>
                    <li class="mb-2"><a href="kontakt.php" class="text-muted text-decoration-none">Kontakt</a></li>
                    <li class="mb-2"><a href="/app" class="text-muted text-decoration-none">Login</a></li>
                </ul>
            </div>
            
            <!-- Services -->
            <div class="col-lg-2 col-md-4 mb-4 mb-lg-0">
                <h6 class="fw-bold mb-3">Services</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php#refund-ai" class="text-muted text-decoration-none">KI-Analyse</a></li>
                    <li class="mb-2"><a href="satoshi-test.php" class="text-muted text-decoration-none">Satoshi-Test</a></li>
                    <li class="mb-2"><a href="preise.php" class="text-muted text-decoration-none">Preise</a></li>
                    <li class="mb-2"><a href="faq.php" class="text-muted text-decoration-none">FAQ</a></li>
                </ul>
            </div>
            
            <!-- Legal -->
            <div class="col-lg-2 col-md-4 mb-4 mb-lg-0">
                <h6 class="fw-bold mb-3">Rechtliches</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="impressum.php" class="text-muted text-decoration-none">Impressum</a></li>
                    <li class="mb-2"><a href="datenschutz.php" class="text-muted text-decoration-none">Datenschutz</a></li>
                    <li class="mb-2"><a href="agb.php" class="text-muted text-decoration-none">AGB</a></li>
                </ul>
            </div>
            
            <!-- Social & CTA -->
            <div class="col-lg-2 col-md-12">
                <h6 class="fw-bold mb-3">Folgen Sie uns</h6>
                <div class="d-flex gap-2 mb-3">
                    <a href="#" class="btn btn-sm btn-outline-secondary" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-secondary" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-secondary" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                </div>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="row border-top pt-4 pb-3">
            <div class="col-md-6 text-center text-md-start">
                <small class="text-muted">
                    &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteSettings['brand_name']); ?>. Alle Rechte vorbehalten.
                </small>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <small class="text-muted">
                    <i class="fas fa-shield-alt text-success me-1"></i>
                    BaFin-lizenziert | BaFin-Reg.: <?php echo htmlspecialchars($siteSettings['fca_reference_number']); ?>
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
