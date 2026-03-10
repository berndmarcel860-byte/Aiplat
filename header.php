<?php
if (!isset($siteSettings)) {
    include_once __DIR__ . '/includes/site_settings.php';
}
$_brand   = htmlspecialchars($siteSettings['brand_name'] ?? 'Novalnet AI', ENT_QUOTES, 'UTF-8');
$_siteUrl = htmlspecialchars($siteSettings['site_url']   ?? 'https://novalnet-ai.de', ENT_QUOTES, 'UTF-8');
$_logoUrl = !empty($siteSettings['logo_url'])
            ? htmlspecialchars($siteSettings['logo_url'], ENT_QUOTES, 'UTF-8')
            : '/assets/img/logo.png';
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $_brand; ?> – Sichere Krypto-Rückführung &amp; Wiederherstellung</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

  <link rel="stylesheet" href="/style.css">

</head>

<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand fw-bold fs-4 d-flex align-items-center" href="index.php">
      <img src="<?php echo $_logoUrl; ?>" alt="<?php echo $_brand; ?> Logo">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Menü umschalten">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="#process">Prozess</a></li>
        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="#refund-ai">KI-Rückerstattung</a></li>
        <li class="nav-item"><a class="nav-link" href="ueber-uns.php">Über uns</a></li>
        <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
      </ul>
      <a href="<?php echo $_siteUrl; ?>/app" class="btn btn-primary ms-lg-3 mt-2 mt-lg-0">
        <i class="fa-solid fa-user-plus me-2"></i>Konto erstellen
      </a>
    </div>
  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Add shadow on scroll
  window.addEventListener('scroll', () => {
    const nav = document.getElementById('mainNav');
    if(window.scrollY > 10) nav.classList.add('scrolled');
    else nav.classList.remove('scrolled');
  });
</script>

