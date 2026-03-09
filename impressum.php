<?php
include_once 'includes/site_settings.php';
$page_title = 'Impressum – ' . ($siteSettings['brand_name'] ?? 'Novalnet AI');
$page_description = 'Impressum und rechtliche Informationen von Novalnet AI. Gutenbergstraße 7, 85748 Garching b.München.';
$page_keywords = 'Impressum, Kontakt, Rechtliches, Novalnet AI';
$page_url = ($siteSettings['site_url'] ?? 'https://novalnet-ai.de') . '/Frontend/impressum.php';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- SEO H1 -->
<h1 class="visually-hidden">KI-gestützte Blockchain Analyse bei Krypto-Betrug</h1>

<!-- Impressum Section -->
<section class="section mt-5">
  <div class="container">
    <h2 class="mb-4">Impressum</h2>
    <p class="text-muted mb-5">Angaben gemäß § 5 TMG</p>

    <!-- Firmeninformationen -->
    <div class="impressum-box">
      <h4>Firmeninformationen</h4>
      <p><strong>Firmenname:</strong> <?php echo htmlspecialchars($siteSettings['brand_name']); ?></p>
      <p><strong>Vertreten durch:</strong> Mark Senger und Natalie Stenzel</p>
      <p><strong>Adresse:</strong><br>
        <?php echo nl2br(htmlspecialchars($siteSettings['company_address'])); ?>
      </p>
      <p><strong>E-Mail:</strong> <a href="mailto:<?php echo htmlspecialchars($siteSettings['contact_email']); ?>"><?php echo htmlspecialchars($siteSettings['contact_email']); ?></a></p>
    <!--<p><strong>Handelsre:</strong> HRB 11885733</p>-->
      <!--<p><strong>USt-IdNr:</strong> DE 345 987 210</p>-->
     <!-- <p><strong>LEI:</strong> 529900TVESTCRYPTO54M10</p>-->
    </div>

    <!-- Aufsichtsbehörde -->
    <div class="impressum-box">
      <h4>Aufsichtsbehörde</h4>
      <p><strong>Zuständige Aufsichtsbehörde:</strong><br>
      Bundesanstalt für Finanzdienstleistungsaufsicht (BaFin)</p>

      <p><strong>Adresse:</strong><br>
      Graurheindorfer Straße 108<br>
      53117 Bonn<br><br>
      und<br><br>
      Marie-Curie-Straße 24–28<br>
      60439 Frankfurt am Main<br>
      Deutschland
      </p>

      <p><strong>BaFin-Registernummer:</strong> <?php echo htmlspecialchars($siteSettings['fca_reference_number']); ?></p>
      <p><strong>Überprüfung:</strong>
        <a href="<?php echo htmlspecialchars($siteSettings['licens_url']); ?>" target="_blank" rel="noopener noreferrer">
          🔗 BaFin-Unternehmensdatenbank
        </a>
      </p>
    </div>

    <!-- Zweck der Website -->
    <div class="impressum-box">
      <h4>Zweck der Website</h4>
      <p>Der Zweck dieser Website ist die Bereitstellung von Informationen über <?php echo htmlspecialchars($siteSettings['brand_name']); ?> für B2B-affine Zielgruppen wie:</p>
      <ul>
        <li>Mitarbeiter von Research-Teams</li>
        <li>Procurement-Teams</li>
        <li>AFC-Teams</li>
        <li>Mitarbeiter der Banken und Wertpapierhandelshäuser</li>
        <li>Berechtigte Gegenparteien</li>
        <li>Allgemeine Finanzdienstleister</li>
      </ul>

      <p><strong>Wichtiger Hinweis:</strong> Ein Onboarding oder ein Service-Abonnement über die Website ist nicht möglich.</p>
      <p><?php echo htmlspecialchars($siteSettings['brand_name']); ?> wickelt ausschließlich Geschäfte mit professionellen Kunden und geeigneten Gegenparteien im Sinne von MiFID II ab.</p>
      <p><?php echo htmlspecialchars($siteSettings['brand_name']); ?> bietet ausdrücklich keine Dienstleistungen an und schließt keine Verträge mit Verbrauchern oder Einzelpersonen ab. Die Definition des Begriffs „Verbraucher“ basiert auf der EU-Richtlinie 2011/83/EU und dem UK Consumer Rights Act 2015.</p>
    </div>

    <!-- Partner -->
    <div class="impressum-box text-center">
      <h4>🏆 Stolzer Partner</h4>
      <p>Wir sind stolzer Partner des Blockchain Bundesverbands</p>
      <img src="/assets/img/blockchain-bundesverband.png" alt="Blockchain Bundesverband" width="240" class="my-3">
      <p class="text-muted">Gemeinsam für die Zukunft der Blockchain-Technologie in Deutschland</p>
    </div>

  </div>
</section>

<!-- ========================================================= -->
<!-- 🌐 FOOTER – TRADEVEST CRYPTO -->
<!-- ========================================================= -->
<?php include 'footer.php'; ?>
