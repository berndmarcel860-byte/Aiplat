<?php
$page_title = 'AGB – Allgemeine Geschäftsbedingungen | Novalnet AI';
$page_description = 'Allgemeine Geschäftsbedingungen für die Nutzung von Novalnet AI. Transparente Bedingungen für Krypto-Wiederherstellungsservices.';
$page_keywords = 'AGB, Geschäftsbedingungen, Nutzungsbedingungen, Novalnet AI';
$page_url = 'https://novalnet-ai.de/Frontend/agb.php';

include_once 'includes/site_settings.php';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- SEO H1 -->
<h1 class="visually-hidden">KI-gestützte Blockchain Analyse bei Krypto-Betrug</h1>

<!-- AGB Section -->
<section class="section mt-5">
  <div class="container">
    <h2>Allgemeine Geschäftsbedingungen</h2>
    <p class="subtitle">Geschäftsbedingungen der <?php echo htmlspecialchars($siteSettings['brand_name']); ?></p>

    <div class="card-box">
      <h5>1. Geltungsbereich</h5>
      <p>
        Diese Allgemeinen Geschäftsbedingungen gelten für alle Geschäftsbeziehungen zwischen der <?php echo htmlspecialchars($siteSettings['brand_name']); ?> 
        und ihren professionellen Kunden sowie geeigneten Gegenparteien im Sinne von MiFID II.
      </p>
      <p>
        Die <?php echo htmlspecialchars($siteSettings['brand_name']); ?> wickelt ausschließlich Geschäfte mit professionellen Kunden und geeigneten Gegenparteien ab. 
        Verbraucher und Privatkunden sind ausdrücklich ausgeschlossen.
      </p>
    </div>

    <div class="card-box">
      <h5>2. Zielgruppe und Zugang</h5>
      <p>
        Diese Website richtet sich ausschließlich an B2B-affine Zielgruppen, insbesondere:
      </p>
      <ul>
        <li>Mitarbeiter von Research-Teams</li>
        <li>Procurement-Teams</li>
        <li>AFC-Teams</li>
        <li>Mitarbeiter der Banken und Wertpapierhandelshäuser</li>
        <li>Berechtigte Gegenparteien</li>
        <li>Finanzdienstleister</li>
      </ul>
      <p>Ein Onboarding oder Service-Abonnement über die Website ist nicht möglich.</p>
    </div>

    <div class="card-box">
      <h5>3. Rechtliche Beschränkungen</h5>
      <p>
        Der Zugang zu den auf dieser Website beschriebenen Dienstleistungen kann durch Gesetze und Vorschriften eingeschränkt sein, 
        die für <?php echo htmlspecialchars($siteSettings['brand_name']); ?> und/oder Personen mit Wohnsitz in bestimmten Ländern gelten.
      </p>
      <p>
        Anwendbare gesetzliche Bestimmungen können es bestimmten Besuchern verwehren, Dienstleistungen in Anspruch zu nehmen oder anzubieten 
        und/oder Geschäfte mit der <?php echo htmlspecialchars($siteSettings['brand_name']); ?> zu tätigen.
      </p>
      <p>
        Die auf dieser Website beschriebenen Dienstleistungen dienen ausschließlich der Information für berechtigte Kunden 
        und stellen kein Angebot zum Abschluss von Geschäften in Ländern dar, in denen ein solches Angebot gesetzlich untersagt ist.
      </p>
    </div>

    <div class="card-box">
      <h5>4. Haftungsausschluss</h5>
      <p>
        Wenn Sie sich entscheiden, auf diese Website zuzugreifen, tun Sie dies auf eigene Initiative und eigenes Risiko 
        und bestätigen, dass Sie verstehen, dass die hier beschriebenen Dienstleistungen Ihnen nur angeboten werden, 
        wenn Sie dazu berechtigt sind.
      </p>
      <p>
        Darüber hinaus bietet die <?php echo htmlspecialchars($siteSettings['brand_name']); ?> keine Dienstleistungen für Privatkunden an, 
        auf die sich das FCA UK Crypto Asset Financial Promotions Regime bezieht.
      </p>
    </div>

    <div class="card-box">
      <h5>5. Schlussbestimmungen</h5>
      <p>
        Es gilt das Recht der Bundesrepublik Deutschland. Erfüllungsort und Gerichtsstand ist Frankfurt am Main, 
        sofern der Kunde Kaufmann, juristische Person des öffentlichen Rechts oder öffentlich-rechtliches Sondervermögen ist.
      </p>
      <p>
        Sollten einzelne Bestimmungen dieser AGB unwirksam oder undurchführbar sein oder werden, 
        bleibt die Wirksamkeit der übrigen Bestimmungen unberührt.
      </p>
      <p class="text-muted mt-4"><small>Stand: Oktober 2025</small></p>
    </div>
  </div>
</section>

<!-- ========================================================= -->
<!-- 🌐 FOOTER – TRADEVEST CRYPTO -->
<!-- ========================================================= -->
<?php include 'footer.php'; ?>