<?php
include_once 'includes/site_settings.php';
$page_title = 'Kontakt – ' . ($siteSettings['brand_name'] ?? 'Novalnet AI') . ' Support';
$page_description = 'Kontaktieren Sie unser Expertenteam für Krypto-Wiederherstellung. BaFin-lizenziert, kostenlose Erstberatung.';
$page_keywords = 'Kontakt, Support, Krypto Beratung, BaFin lizenziert';
$page_url = ($siteSettings['site_url'] ?? 'https://novalnet-ai.de') . '/Frontend/kontakt.php';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- SEO H1 -->
<h1 class="visually-hidden">Kontakt – <?php echo htmlspecialchars($siteSettings['brand_name'] ?? 'Novalnet AI'); ?> Support</h1>

<style>
  .section {
    padding: 100px 0;
  }
  h2 {
    font-weight: 700;
    margin-bottom: 10px;
    color: #0d1b2a;
  }
  .lead {
    color: #6c757d;
    margin-bottom: 40px;
  }
  .contact-box, .form-box {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.05);
    padding: 30px;
    margin-bottom: 30px;
  }
  .contact-icon {
    width: 50px;
    height: 50px;
    background: #e8f0fe;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
  }
  .btn-primary {
    background: #0066ff;
    border: none;
    border-radius: 30px;
    padding: 12px 30px;
    font-weight: 600;
  }
  .btn-primary:hover {
    background: #004bcc;
  }
</style>


<!-- Kontakt Section -->
<section class="section mt-5">
  <div class="container">
    <h2>Kontakt</h2>
    <p class="lead">Haben Sie Fragen? Wir sind für Sie da!</p>

    <!-- E-Mail Support -->
    <div class="contact-box d-flex align-items-center">
      <div class="contact-icon">
        <img src="https://cdn-icons-png.flaticon.com/512/561/561127.png" width="28" alt="Mail Icon">
      </div>
      <div>
        <h5 class="mb-1">E-Mail Support</h5>
        <p class="mb-0 text-muted">Für allgemeine Anfragen und Support</p>
        <a href="mailto:<?php echo htmlspecialchars($siteSettings['contact_email']); ?>" class="fw-bold text-decoration-none text-primary"><?php echo htmlspecialchars($siteSettings['contact_email']); ?></a>
        <p class="text-muted small mb-0">Antwortzeit: 1–2 Werktage</p>
      </div>
    </div>

    <!-- Kontaktformular -->
    <div class="form-box">
      <h5 class="mb-3">📩 Kontaktformular</h5>
      <form action="send_contact.php" method="POST">
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Vorname *</label>
            <input type="text" name="vorname" class="form-control" placeholder="Ihr Vorname" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nachname *</label>
            <input type="text" name="nachname" class="form-control" placeholder="Ihr Nachname" required>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">E-Mail *</label>
            <input type="email" name="email" class="form-control" placeholder="ihre@email.de" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Telefon</label>
            <input type="text" name="telefon" class="form-control" placeholder="069 12001194">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Nachricht *</label>
          <textarea name="nachricht" class="form-control" rows="5" maxlength="5000" placeholder="Beschreiben Sie Ihr Anliegen..." required></textarea>
          <div class="text-end text-muted small">0/5000 Zeichen</div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mt-2">Nachricht senden</button>
      </form>
    </div>
  </div>
</section>

<!-- ========================================================= -->
<!-- 🌐 FOOTER – TRADEVEST CRYPTO -->
<!-- ========================================================= -->
<?php include 'footer.php'; ?>
