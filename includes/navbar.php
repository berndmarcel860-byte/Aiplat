<?php
if (!isset($siteSettings)) {
    include_once __DIR__ . '/site_settings.php';
}
$_navBrand = htmlspecialchars($siteSettings['brand_name'] ?? 'Novalnet AI', ENT_QUOTES, 'UTF-8');
$_siteUrl  = htmlspecialchars($siteSettings['site_url']   ?? 'https://novalnet-ai.de', ENT_QUOTES, 'UTF-8');
$_logoUrl  = !empty($siteSettings['logo_url'])
             ? htmlspecialchars($siteSettings['logo_url'], ENT_QUOTES, 'UTF-8')
             : '/assets/img/logo.png';
$_appUrl   = $_siteUrl . '/app';
?>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand fw-bold fs-4 d-flex align-items-center" href="index.php">
      <img src="<?php echo $_logoUrl; ?>" alt="<?php echo $_navBrand; ?> Logo">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Menü umschalten">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php#services">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#process">Prozess</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#refund-ai">KI-Rückerstattung</a></li>
        <li class="nav-item"><a class="nav-link" href="ueber-uns.php">Über uns</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#faq">FAQ</a></li>
      </ul>
      <a href="<?php echo $_appUrl; ?>" class="btn btn-primary ms-lg-3 mt-2 mt-lg-0">
        <i class="fa-solid fa-user-plus me-2"></i>Konto erstellen
      </a>
    </div>
  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  window.addEventListener('scroll', () => {
    const nav = document.getElementById('mainNav');
    if (window.scrollY > 10) nav.classList.add('scrolled');
    else nav.classList.remove('scrolled');
  });
</script>
