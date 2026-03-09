<?php
$page_title = 'Datenschutzerklärung – Novalnet AI';
$page_description = 'Datenschutzerklärung von Novalnet AI. Erfahren Sie, wie wir Ihre Daten schützen und verarbeiten.';
$page_keywords = 'Datenschutz, DSGVO, Datenschutzerklärung, Privatsphäre';
$page_url = 'https://novalnet-ai.de/Frontend/datenschutz.php';

include_once 'includes/site_settings.php';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- SEO H1 -->
<h1 class="visually-hidden">KI-gestützte Blockchain Analyse bei Krypto-Betrug</h1>

<!-- Datenschutz Section -->
<section class="section mt-5">
  <div class="container">
    <h2>Datenschutzerklärung</h2>
    <p class="subtitle">Informationen zum Umgang mit personenbezogenen Daten</p>

    <div class="card-box">
      <h5>1. Verantwortlicher</h5>
      <p>Verantwortlicher für die Datenverarbeitung auf dieser Website ist:</p>
      <p>
        <strong><?php echo htmlspecialchars($siteSettings['brand_name']); ?></strong><br>
        Vertreten durch: Michael Reinhard und Roman Schmidt<br>
        <?php echo nl2br(htmlspecialchars($siteSettings['company_address'])); ?><br>
        E-Mail: <a href="mailto:<?php echo htmlspecialchars($siteSettings['contact_email']); ?>"><?php echo htmlspecialchars($siteSettings['contact_email']); ?></a>
      </p>
    </div>

    <div class="card-box">
      <h5>2. Erhebung und Speicherung personenbezogener Daten</h5>
      <p>
        Beim Besuch unserer Website werden automatisch Informationen allgemeiner Natur erfasst. 
        Diese Informationen (Server-Logfiles) beinhalten etwa die Art des Webbrowsers, 
        das verwendete Betriebssystem, den Domainnamen Ihres Internet-Service-Providers 
        und ähnliche technische Daten.
      </p>
      <p>
        Diese Informationen sind technisch notwendig, um von Ihnen angeforderte Inhalte von Webseiten 
        korrekt auszuliefern und fallen bei Nutzung des Internets zwingend an.
      </p>
    </div>

    <div class="card-box">
      <h5>3. Verwendung von Cookies</h5>
      <p>
        Wie viele andere Webseiten verwenden wir auch sogenannte „Cookies“. 
        Cookies sind kleine Textdateien, die von einem Webserver auf Ihre Festplatte übertragen werden. 
        Hierdurch erhalten wir automatisch bestimmte Daten wie z. B. IP-Adresse, 
        verwendeter Browser, Betriebssystem und Ihre Verbindung zum Internet.
      </p>
      <p>
        Cookies können nicht verwendet werden, um Programme zu starten oder Viren auf einen Computer zu übertragen. 
        Anhand der in Cookies enthaltenen Informationen können wir Ihnen die Navigation erleichtern 
        und die korrekte Anzeige unserer Webseiten ermöglichen.
      </p>
      <p>
        Sie können die Verwendung von Cookies jederzeit über die Einstellungen Ihres Browsers deaktivieren. 
        Bitte beachten Sie jedoch, dass dadurch einzelne Funktionen der Website eingeschränkt sein können.
      </p>
    </div>

    <div class="card-box">
      <h5>4. Ihre Rechte</h5>
      <p>Sie haben jederzeit das Recht:</p>
      <ul>
        <li>unentgeltlich Auskunft über Ihre gespeicherten personenbezogenen Daten zu erhalten</li>
        <li>Berichtigung unrichtiger Daten zu verlangen</li>
        <li>Löschung Ihrer bei uns gespeicherten Daten zu verlangen</li>
        <li>Einschränkung der Datenverarbeitung zu verlangen</li>
        <li>Widerspruch gegen die Verarbeitung Ihrer Daten einzulegen</li>
        <li>Datenübertragbarkeit zu verlangen</li>
      </ul>
      <p>
        Sofern Sie uns eine Einwilligung erteilt haben, können Sie diese jederzeit 
        mit Wirkung für die Zukunft widerrufen.
      </p>
    </div>

    <div class="card-box">
      <h5>5. Kontakt</h5>
      <p>
        Bei Fragen zur Erhebung, Verarbeitung oder Nutzung Ihrer personenbezogenen Daten, 
        bei Auskünften, Berichtigung, Sperrung oder Löschung von Daten sowie 
        Widerruf erteilter Einwilligungen wenden Sie sich bitte an:
      </p>
      <p>
        <strong><?php echo htmlspecialchars($siteSettings['brand_name']); ?></strong><br>
        E-Mail: <a href="mailto:<?php echo htmlspecialchars($siteSettings['contact_email']); ?>"><?php echo htmlspecialchars($siteSettings['contact_email']); ?></a><br>
        Adresse: <?php echo htmlspecialchars($siteSettings['company_address']); ?>
      </p>
      <p class="text-muted mt-4"><small>Stand: Oktober 2025</small></p>
    </div>
  </div>
</section>

<!-- ========================================================= -->
<!-- 🌐 FOOTER – TRADEVEST CRYPTO -->
<!-- ========================================================= -->
<?php include 'footer.php'; ?>
