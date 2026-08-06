<?php
include_once 'includes/site_settings.php';
$page_title = 'Über uns – Das ' . ($siteSettings['brand_name'] ?? 'Novalnet AI') . ' Team';
$page_description = 'Lernen Sie das Expertenteam von ' . ($siteSettings['brand_name'] ?? 'Novalnet AI') . ' kennen. Spezialisiert auf KI-gestützte Blockchain-Analyse und Krypto-Wiederherstellung.';
$page_keywords = 'Über uns, Team, Experten, Blockchain Analyse, ' . ($siteSettings['brand_name'] ?? 'Novalnet AI');
$page_url = ($siteSettings['site_url'] ?? 'https://novalnet-ai.de') . '/Frontend/ueber-uns.php';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- SEO H1 -->
<h1 class="visually-hidden">KI-gestützte Blockchain Analyse bei Krypto-Betrug</h1>

<!-- Über uns -->
<section class="section text-center mt-5">
  <div class="container">
    <h2 class="section-title mb-3">Über uns</h2>
    <p class="section-subtitle">Erfahren Sie mehr über unsere Vision, Mission und das Team, das TradeVest zu dem macht, was es ist.</p>
  </div>
</section>

<!-- Vision -->
<section id="vision" class="section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <h2><span>Our</span> Vision</h2>
        <p class="mt-4">Wir glauben an eine Welt, in der Eigentum so frei fließt wie Informationen, und in der Vermögenswerte ohne Hindernisse zugänglich sind und übertragen werden können – was den Weg zur wirtschaftlichen Freiheit ebnet.</p>
      </div>
      <div class="col-lg-6 text-center">
        <div class="icon-card">
          <i class="fas fa-globe-europe"></i>
          <h5 class="fw-bold">Globale Freiheit</h5>
          <p class="text-muted">Finanzielle Freiheit für alle durch digitale Vermögenswerte</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Mission -->
<section id="mission" class="section bg-light">
  <div class="container">
    <div class="row align-items-center flex-lg-row-reverse">
      <div class="col-lg-6">
        <h2><span>Our</span> Mission</h2>
        <p class="mt-4">Wir bei TradeVest stellen die Infrastruktur zur Verfügung, um den Übergang der Finanzwelt vom Status quo in ein neues Zeitalter digitaler Vermögenswerte zu beschleunigen und zu ermöglichen – und somit die Brücke zwischen den beiden Welten zu schlagen.</p>
      </div>
      <div class="col-lg-6 text-center">
        <div class="icon-card">
          <i class="fas fa-rocket"></i>
          <h5 class="fw-bold">Innovation</h5>
          <p class="text-muted">Beschleunigung des Übergangs zur digitalen Finanzwelt</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Werte -->
<section id="werte" class="section text-center">
  <div class="container">
    <h2 class="section-title mb-5">Unsere Werte</h2>
    <div class="row justify-content-center g-4">
      <div class="col-md-4">
        <i class="fas fa-lock fa-3x text-primary mb-3"></i>
        <h5>Sicherheit</h5>
        <p class="text-muted">Höchste Sicherheitsstandards für Ihre digitalen Vermögenswerte</p>
      </div>
      <div class="col-md-4">
        <i class="fas fa-bolt fa-3x text-warning mb-3"></i>
        <h5>Innovation</h5>
        <p class="text-muted">Kontinuierliche Weiterentwicklung unserer Technologie</p>
      </div>
      <div class="col-md-4">
        <i class="fas fa-handshake fa-3x text-success mb-3"></i>
        <h5>Vertrauen</h5>
        <p class="text-muted">Transparente Kommunikation und verlässliche Partnerschaften</p>
      </div>
    </div>
  </div>
</section>



<!-- Footer -->
<?php include 'footer.php'; ?>
