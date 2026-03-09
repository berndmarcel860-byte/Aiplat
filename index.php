<?php
include_once 'includes/site_settings.php';
$page_title = ($siteSettings['brand_name'] ?? 'Novalnet AI') . ' – Sichere Krypto-Rückführung & Wiederherstellung';
$page_description = 'KI-gestützte Blockchain-Analyse zur Identifizierung und Wiederherstellung betrügerisch entwendeter Kryptowährungen. BaFin-lizenziert mit 87% Erfolgsquote.';
$page_keywords = 'Krypto Wiederherstellung, Blockchain Analyse, Betrugsaufklärung, KI Krypto, BaFin lizenziert';
$page_url = ($siteSettings['site_url'] ?? 'https://novalnet-ai.de') . '/index.php';

include 'includes/header.php';
include 'includes/navbar.php';
?>

<style>
    .hero-section {
        padding: 120px 0 80px;
        background: linear-gradient(135deg, #f8f9ff 0%, #eef4ff 100%);
    }
    .section { padding: 80px 0; }

    .feature-card {
        background:#fff; border-radius:12px; padding:30px;
        box-shadow:0 5px 15px rgba(0,0,0,.05);
        transition: transform .3s ease; height:100%;
    }
    .feature-card:hover { transform: translateY(-5px); }

    .logo-grid {
        display:flex; flex-wrap:wrap; justify-content:center; gap:30px; margin:40px 0;
    }
    .logo-item { height:50px; display:flex; align-items:center; opacity:.85; transition:opacity .3s; }
    .logo-item:hover { opacity:1; }

    .step-icon {
        width:60px; height:60px; border-radius:50%;
        background: linear-gradient(135deg, var(--primary), #0b5ed7);
        display:flex; align-items:center; justify-content:center; color:#fff; font-size:24px; margin-bottom:20px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), #0b5ed7);
        border:none; padding:12px 30px; font-weight:600; border-radius:8px;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow:0 5px 15px rgba(13,110,253,.4);
    }
    .btn-outline-primary { padding:12px 30px; border-width:2px; }

    .section-title { font-weight:700; margin-bottom:20px; font-size:2rem; }
    .section-subtitle { color:var(--gray); margin-bottom:40px; font-size:1.05rem; }

    .trusted-by { background:#f8f9fa; padding:60px 0; text-align:center; }

    .feature-list li { margin-bottom:10px; display:flex; align-items:flex-start; }
    .feature-list li i { color:var(--primary); margin-right:10px; margin-top:4px; }

    /* AI section */
    .ai-section img {
        border-radius:12px;
        box-shadow:0 10px 24px rgba(0,0,0,.08);
    }
    .kpi-badge {
        display:inline-flex; align-items:center; gap:10px;
        background:#fff; border:1px solid #e9ecef; border-radius:999px;
        padding:8px 14px; font-weight:600;
    }

    /* Animated progress (87%) */
    .progress-wrap { margin-top:10px; }
    .progress {
        height:12px; border-radius:999px; background:#e9ecef;
    }
    .progress-bar {
        background: linear-gradient(90deg, var(--primary), #37a0ff);
        width:0%; transition: width 1.6s ease-out;
    }
    .progress-label { font-size:.9rem; color:#6c757d; margin-top:8px; }

    /* Smooth anchor offset for fixed navbar */
    .anchor-offset { scroll-margin-top: 90px; }
    
    .team-card {
        background: #fff;
        border-radius: 12px;
        padding: 30px 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    .team-card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        transform: translateY(-4px);
    }
    .team-card img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #00b5d6;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    .team-card img:hover {
        transform: scale(1.05);
    }
    .team-card h5 {
        font-weight: 600;
        margin-bottom: 4px;
        color: #1a1a2e;
    }
    .team-card p {
        color: #00b5d6;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    /* ========== ANIMATED VISUAL ENHANCEMENTS ========== */
    
    /* Particle canvas background */
    #particles-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        pointer-events: none;
    }
    
    /* Floating crypto icons */
    .crypto-float {
        position: absolute;
        font-size: 2.5rem;
        opacity: 0.15;
        animation: float 20s infinite ease-in-out;
        z-index: 2;
        pointer-events: none;
        filter: drop-shadow(0 0 10px currentColor);
    }
    .crypto-float:nth-child(2) { animation: float-slow 25s infinite ease-in-out; animation-delay: -5s; }
    .crypto-float:nth-child(3) { animation: float 18s infinite ease-in-out; animation-delay: -10s; }
    .crypto-float:nth-child(4) { animation: float-slow 22s infinite ease-in-out; animation-delay: -15s; }
    
    /* Floating animations */
    @keyframes float {
        0%, 100% { transform: translate(0, 0) rotate(0deg); }
        25% { transform: translate(20px, -30px) rotate(5deg); }
        50% { transform: translate(-15px, -60px) rotate(-5deg); }
        75% { transform: translate(30px, -40px) rotate(3deg); }
    }
    @keyframes float-slow {
        0%, 100% { transform: translate(0, 0) rotate(0deg); }
        25% { transform: translate(-25px, -35px) rotate(-5deg); }
        50% { transform: translate(20px, -70px) rotate(5deg); }
        75% { transform: translate(-30px, -45px) rotate(-3deg); }
    }
    
    /* Pulse effect for statistics */
    @keyframes pulse-subtle {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }
    .stat-pulse {
        animation: pulse-subtle 3s ease-in-out infinite;
    }
    
    /* Shimmer effect */
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .shimmer-wrapper {
        position: relative;
        overflow: hidden;
    }
    .shimmer-wrapper::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: shimmer 3s infinite;
    }
    
    /* Fade in up animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-on-scroll {
        opacity: 0;
        animation: fadeInUp 0.8s ease-out forwards;
    }
    
    /* Gradient shift animation */
    @keyframes gradientShift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    .animated-gradient {
        background: linear-gradient(135deg, #0d6efd, #0b5ed7, #37a0ff, #0d6efd);
        background-size: 300% 300%;
        animation: gradientShift 15s ease infinite;
    }

    /* ========== ENHANCED AI SECTION STYLES ========== */
    
    /* AI Section Background */
    .ai-section-enhanced {
        position: relative;
        background: linear-gradient(135deg, #f8f9ff 0%, #e8f0ff 50%, #f0f8ff 100%);
        overflow: hidden;
    }
    
    .ai-bg-animated {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: 
            radial-gradient(circle at 20% 30%, rgba(13, 110, 253, 0.05) 0%, transparent 50%),
            radial-gradient(circle at 80% 70%, rgba(55, 160, 255, 0.05) 0%, transparent 50%);
        animation: bgPulse 10s ease-in-out infinite;
        z-index: 0;
    }
    
    @keyframes bgPulse {
        0%, 100% { opacity: 0.5; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.1); }
    }
    
    /* Fade-in-up animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fade-in-up {
        animation: fadeInUp 0.8s ease-out;
    }
    
    /* AI Feature Cards */
    .ai-feature-card {
        background: white;
        border-radius: 20px;
        padding: 35px;
        height: 100%;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }
    
    .ai-feature-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #0d6efd, #37a0ff);
        transform: scaleX(0);
        transition: transform 0.4s ease;
    }
    
    .ai-feature-card:hover::before {
        transform: scaleX(1);
    }
    
    .ai-feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 50px rgba(13, 110, 253, 0.2);
        border-color: rgba(13, 110, 253, 0.3);
    }
    
    .ai-feature-icon {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        background: linear-gradient(135deg, #0d6efd, #37a0ff);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: white;
        margin-bottom: 20px;
        box-shadow: 0 10px 25px rgba(13, 110, 253, 0.3);
        animation: iconFloat 3s ease-in-out infinite;
    }
    
    @keyframes iconFloat {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    .feature-list-enhanced {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .feature-list-enhanced li {
        padding: 8px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
    }
    
    .feature-list-enhanced li i {
        flex-shrink: 0;
    }
    
    /* Scroll Animation */
    .animate-on-scroll {
        opacity: 0;
        transform: translateY(50px);
        transition: all 0.8s ease-out;
    }
    
    .animate-on-scroll.animated {
        opacity: 1;
        transform: translateY(0);
    }
    
    /* Success Metrics Card */
    .success-metrics-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(13, 110, 253, 0.1);
    }
    
    .metric-item {
        padding: 20px;
        border-radius: 15px;
        transition: all 0.3s ease;
    }
    
    .metric-item:hover {
        background: rgba(13, 110, 253, 0.05);
        transform: scale(1.05);
    }
    
    .metric-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        background: linear-gradient(135deg, #f0f8ff, #e8f0ff);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: #0d6efd;
        margin: 0 auto 15px;
    }
    
    .counter {
        display: inline-block;
        transition: transform 0.3s ease;
    }
    
    .counter.counting {
        animation: countPulse 0.5s ease;
    }
    
    @keyframes countPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
    
    .progress-enhanced {
        background: #e9ecef;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .progress-enhanced .progress-bar {
        transition: width 2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Process Timeline */
    .process-timeline {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 30px 20px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .process-timeline {
            flex-direction: column;
        }
        .process-arrow {
            transform: rotate(90deg);
            margin: 10px 0;
        }
    }
    
    .process-step {
        flex: 1;
        min-width: 200px;
        text-align: center;
        position: relative;
    }
    
    .process-number {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0d6efd, #37a0ff);
        color: white;
        font-size: 1.5rem;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        animation: numberPulse 2s ease-in-out infinite;
    }
    
    @keyframes numberPulse {
        0%, 100% { box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3); }
        50% { box-shadow: 0 5px 25px rgba(13, 110, 253, 0.5); }
    }
    
    .process-content h5 {
        font-size: 1rem;
        margin-bottom: 8px;
        color: #1a1a2e;
    }
    
    .process-content p {
        font-size: 0.85rem;
    }
    
    .process-arrow {
        color: #0d6efd;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    
    @media (max-width: 768px) {
        .process-arrow {
            display: none;
        }
    }
    
    /* Trust Indicators */
    .trust-indicators {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }
    
    .trust-badge {
        padding: 20px;
        border-radius: 15px;
        transition: all 0.3s ease;
    }
    
    .trust-badge:hover {
        background: rgba(13, 110, 253, 0.05);
        transform: translateY(-5px);
    }
    
    .trust-badge i {
        transition: transform 0.3s ease;
    }
    
    .trust-badge:hover i {
        transform: scale(1.1);
    }
    
    .pulse-animation {
        animation: trustPulse 2s ease-in-out infinite;
    }
    
    @keyframes trustPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    /* Button Glow Effect */
    .btn-glow {
        position: relative;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(13, 110, 253, 0.4);
    }
    
    .btn-glow::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    
    .btn-glow:hover::before {
        width: 300px;
        height: 300px;
    }
    
    .btn-glow:hover {
        box-shadow: 0 8px 30px rgba(13, 110, 253, 0.6);
        transform: translateY(-2px);
    }
</style>

<!-- Hero Section -->
<header class="hero-section text-center" style="position: relative; overflow: hidden;">
    <!-- Animated Particle Background -->
    <canvas id="particles-canvas"></canvas>
    
    <!-- Floating Crypto Icons -->
    <div class="crypto-float" style="top: 10%; left: 5%; color: #f7931a;">₿</div>
    <div class="crypto-float" style="top: 70%; left: 8%; color: #627eea;">Ξ</div>
    <div class="crypto-float" style="top: 40%; right: 10%; color: #26a17b;">₮</div>
    <div class="crypto-float" style="top: 15%; right: 5%; color: #f3ba2f;">B</div>
    <div class="crypto-float" style="top: 60%; right: 15%; color: #0033ad;">₳</div>
    <div class="crypto-float" style="top: 25%; left: 12%; color: #00ffa3;">◎</div>
    <div class="crypto-float" style="top: 80%; right: 20%; color: #23292f;">✕</div>
    <div class="crypto-float" style="top: 35%; left: 88%; color: #e6007a;">●</div>
    
    <div class="container" style="position: relative; z-index: 10;">
        <div class="mb-3">
            <span class="badge bg-primary-subtle text-primary px-3 py-2" style="font-size: 0.9rem;">
                <i class="fas fa-certificate me-2"></i>BaFin-lizenziert | BaFin-Reg.: <?php echo htmlspecialchars($siteSettings['fca_reference_number']); ?>
            </span>
        </div>
        <h1 class="display-4 fw-bold mb-4">Professionelle Blockchain-Forensik<br>
            <span class="text-primary">zur Wiederherstellung betrügerisch entwendeter Kryptowährungen</span>
        </h1>
        <p class="lead mb-4" style="max-width:800px;margin:0 auto;">
            <?= htmlspecialchars($siteSettings['brand_name']) ?> nutzt fortschrittliche KI-Algorithmen zur Analyse und Nachverfolgung betrügerischer 
            Krypto-Transaktionen. Als <strong>BaFin-lizenziertes Unternehmen</strong> führen wir identifizierte Vermögenswerte 
            rechtskonform an die rechtmäßigen Eigentümer zurück.
        </p>
        <div class="mb-4" style="max-width:700px;margin:0 auto;">
            <div class="row text-center">
                <div class="col-4">
                    <div class="fw-bold fs-4 text-primary">727</div>
                    <small class="text-muted">Klienten betreut</small>
                </div>
                <div class="col-4">
                    <div class="fw-bold fs-4 text-primary">87%</div>
                    <small class="text-muted">Erfolgsquote</small>
                </div>
                <div class="col-4">
                    <div class="fw-bold fs-4 text-primary">€47M</div>
                    <small class="text-muted">Wiederhergestellt</small>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center gap-3 flex-wrap mb-5">
            <a href="app/register.php" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-rocket me-2"></i>Kostenlosen Fall einreichen
            </a>
            <a href="app/login.php" class="btn btn-outline-primary btn-lg px-5">
                <i class="fas fa-sign-in-alt me-2"></i>Anmelden
            </a>
        </div>

        <!-- Quick Loss Estimator -->
        <div class="mx-auto" style="max-width:660px;">
            <div class="card shadow-lg border-0" style="border-radius:18px; background:rgba(255,255,255,0.97);">
                <div class="card-body p-4 text-start">
                    <!-- Card Header -->
                    <div class="text-center mb-3">
                        <span class="badge text-white mb-2 px-3 py-2"
                              style="background:linear-gradient(135deg,#667eea,#764ba2); border-radius:20px; font-size:.78rem; letter-spacing:.04em;">
                            <i class="fas fa-calendar-check me-1"></i>KOSTENLOSE ERSTBERATUNG
                        </span>
                        <h5 class="fw-bold mb-1" style="color:#1a1a2e; font-size:1.15rem;">
                            Wie hoch ist Ihr Verlust? Lassen Sie ihn jetzt professionell prüfen.
                        </h5>
                        <p class="text-muted mb-0" style="font-size:.88rem; line-height:1.5;">
                            Schildern Sie uns Ihren Fall – wir melden uns innerhalb von <strong>24 Stunden</strong>
                            mit einer unverbindlichen Einschätzung unserer zertifizierten Blockchain-Analysten.
                        </p>
                    </div>
                    <hr class="my-3" style="border-color:#e8ecf0;">
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-5">
                            <label class="form-label fw-semibold" style="font-size:.84rem; color:#444;">
                                <i class="fas fa-euro-sign me-1 text-primary"></i>Geschädigter Betrag
                            </label>
                            <select id="lossAmount" class="form-select form-select-lg"
                                    style="border-radius:10px; border:2px solid #e1e8ed;">
                                <option value="">Betrag wählen …</option>
                                <option value="5000">Bis € 5.000</option>
                                <option value="25000">€ 5.000 – € 25.000</option>
                                <option value="50000">€ 25.000 – € 50.000</option>
                                <option value="100000">€ 50.000 – € 100.000</option>
                                <option value="250000">Über € 100.000</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold" style="font-size:.84rem; color:#444;">
                                <i class="fas fa-tag me-1 text-primary"></i>Art des Betrugs
                            </label>
                            <select id="lossType" class="form-select form-select-lg"
                                    style="border-radius:10px; border:2px solid #e1e8ed;">
                                <option value="">Kategorie wählen …</option>
                                <option value="exchange">Gefälschte Handelsplattform</option>
                                <option value="investment">Betrügerisches Investment</option>
                                <option value="romance">Romance Scam</option>
                                <option value="rug">Rug Pull / Token-Betrug</option>
                                <option value="phishing">Phishing / Wallet-Diebstahl</option>
                                <option value="other">Sonstiger Betrug</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <button id="estimatorBtn" class="btn btn-primary btn-lg w-100 fw-bold"
                                    style="border-radius:10px; background:linear-gradient(135deg,#667eea,#764ba2); border:none; white-space:nowrap;">
                                <i class="fas fa-arrow-right me-1"></i>Termin buchen
                            </button>
                        </div>
                    </div>
                    <!-- Validation hint (shown when fields empty) -->
                    <div id="estimatorHint" class="mt-3 d-none">
                        <div class="alert alert-warning mb-0 py-2" style="border-radius:10px;">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Bitte wählen Sie den geschädigten Betrag und die Betrugsart aus.
                        </div>
                    </div>
                    <!-- Trust signals -->
                    <div class="d-flex flex-wrap justify-content-center gap-3 mt-3 pt-2" style="border-top:1px solid #f0f3f7;">
                        <span class="text-muted" style="font-size:.78rem;"><i class="fas fa-shield-alt me-1 text-success"></i>BaFin-lizenziert</span>
                        <span class="text-muted" style="font-size:.78rem;"><i class="fas fa-lock me-1 text-success"></i>100 % vertraulich</span>
                        <span class="text-muted" style="font-size:.78rem;"><i class="fas fa-clock me-1 text-success"></i>Antwort in 24 Stunden</span>
                        <span class="text-muted" style="font-size:.78rem;"><i class="fas fa-check-circle me-1 text-success"></i>Unverbindlich &amp; kostenlos</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ============================================================
     CONTACT MODAL – Schnellbewertung / Quick Loss Estimator
     ============================================================ -->
<div class="modal fade" id="contactLeadModal" tabindex="-1" aria-labelledby="contactLeadModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
    <div class="modal-content border-0" style="border-radius:18px; overflow:hidden;">
      <!-- Header -->
      <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,#667eea,#764ba2); padding:28px 32px 18px;">
        <div>
          <p class="text-white mb-1" style="opacity:.85; font-size:.78rem; letter-spacing:.06em; text-transform:uppercase; font-weight:600;">
            <i class="fas fa-calendar-check me-1"></i>Kostenlose Erstberatung
          </p>
          <h5 class="modal-title fw-bold text-white mb-1" id="contactLeadModalLabel" style="font-size:1.15rem;">
            Kostenlose Fallprüfung anfragen
          </h5>
          <p class="text-white mb-0" style="opacity:.85; font-size:.88rem; line-height:1.45;">
            Unsere zertifizierten Analysten melden sich werktags innerhalb von&nbsp;<strong>24&nbsp;Stunden</strong> – unverbindlich und kostenfrei.
          </p>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto mt-n1" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <!-- Body -->
      <div class="modal-body px-4 py-4">
        <!-- Pre-filled summary badge -->
        <div id="clm-summary" class="mb-3 d-none">
          <span class="badge rounded-pill bg-light text-dark border me-1 px-3 py-2" id="clm-badge-amount"></span>
          <span class="badge rounded-pill bg-light text-dark border px-3 py-2" id="clm-badge-type"></span>
        </div>

        <form id="contactLeadForm" novalidate>
          <input type="hidden" id="clm-loss-amount" name="loss_amount">
          <input type="hidden" id="clm-loss-type"   name="loss_type">

          <div class="mb-3">
            <label for="clm-name" class="form-label fw-semibold" style="font-size:.9rem;">Vollständiger Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="clm-name" name="name" maxlength="255"
                   placeholder="Max Mustermann" required autocomplete="name">
          </div>
          <div class="mb-3">
            <label for="clm-email" class="form-label fw-semibold" style="font-size:.9rem;">E-Mail-Adresse <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="clm-email" name="email" maxlength="255"
                   placeholder="max@beispiel.de" required autocomplete="email">
          </div>
          <div class="mb-3">
            <label for="clm-phone" class="form-label fw-semibold" style="font-size:.9rem;">Telefonnummer <span class="text-muted fw-normal">(empfohlen für schnellen Rückruf)</span></label>
            <input type="tel" class="form-control" id="clm-phone" name="phone" maxlength="50"
                   placeholder="+49 123 456789" autocomplete="tel">
          </div>
          <div class="mb-3">
            <label for="clm-message" class="form-label fw-semibold" style="font-size:.9rem;">Kurze Fallbeschreibung <span class="text-muted fw-normal">(optional)</span></label>
            <textarea class="form-control" id="clm-message" name="message" rows="3" maxlength="2000"
                      placeholder="Beschreiben Sie kurz, wann und wie es zum Verlust kam – je mehr Details, desto präziser unsere Ersteinschätzung."></textarea>
          </div>

          <!-- Success / Error feedback -->
          <div id="clm-feedback" class="d-none"></div>

          <button type="submit" id="clm-submit" class="btn btn-lg w-100 fw-bold text-white"
                  style="background:linear-gradient(135deg,#667eea,#764ba2); border:none; border-radius:10px; padding:.85rem;">
            <span id="clm-submit-text"><i class="fas fa-calendar-check me-2"></i>Kostenlosen Termin vereinbaren</span>
            <span id="clm-submit-spinner" class="d-none">
              <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Anfrage wird übermittelt…
            </span>
          </button>

          <p class="text-center text-muted mt-3 mb-0" style="font-size:.78rem;">
            <i class="fas fa-lock me-1"></i>Ihre Angaben werden streng vertraulich behandelt und nicht an Dritte weitergegeben.
            Es entstehen Ihnen keinerlei Kosten oder Verpflichtungen.
          </p>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
    'use strict';

    var amountLabels = {
        '5000':'Bis €5.000','25000':'€5.000–€25.000',
        '50000':'€25.000–€50.000','100000':'€50.000–€100.000','250000':'Über €100.000'
    };
    var typeLabels = {
        exchange:'Fake Exchange',investment:'Investment-Betrug',
        romance:'Romance Scam',rug:'Rug Pull',phishing:'Phishing / Wallet-Hack',other:'Sonstiges'
    };

    document.getElementById('estimatorBtn').addEventListener('click', function(){
        var amount = document.getElementById('lossAmount').value;
        var type   = document.getElementById('lossType').value;
        var hint   = document.getElementById('estimatorHint');

        if (!amount || !type) {
            hint.classList.remove('d-none');
            return;
        }
        hint.classList.add('d-none');

        // Pre-fill hidden fields + summary badges
        document.getElementById('clm-loss-amount').value = amount;
        document.getElementById('clm-loss-type').value   = type;

        var badgeAmount = document.getElementById('clm-badge-amount');
        var badgeType   = document.getElementById('clm-badge-type');
        badgeAmount.textContent = amountLabels[amount] || amount;
        badgeType.textContent   = typeLabels[type]     || type;
        document.getElementById('clm-summary').classList.remove('d-none');

        // Reset form state (hidden fields are re-applied after reset since reset() clears them)
        document.getElementById('contactLeadForm').reset();
        document.getElementById('clm-loss-amount').value = amount;
        document.getElementById('clm-loss-type').value   = type;
        var fb = document.getElementById('clm-feedback');
        fb.className = 'd-none';
        fb.textContent = '';
        document.getElementById('clm-submit').disabled = false;
        document.getElementById('clm-submit-text').classList.remove('d-none');
        document.getElementById('clm-submit-spinner').classList.add('d-none');

        var modal = new bootstrap.Modal(document.getElementById('contactLeadModal'));
        modal.show();
    });

    // Dismiss hint on dropdown change
    ['lossAmount','lossType'].forEach(function(id){
        document.getElementById(id).addEventListener('change', function(){
            document.getElementById('estimatorHint').classList.add('d-none');
        });
    });

    // Form submission
    document.getElementById('contactLeadForm').addEventListener('submit', function(e){
        e.preventDefault();
        var form   = this;
        var btn    = document.getElementById('clm-submit');
        var txt    = document.getElementById('clm-submit-text');
        var spin   = document.getElementById('clm-submit-spinner');
        var fb     = document.getElementById('clm-feedback');

        if (!form.checkValidity()) { form.reportValidity(); return; }

        btn.disabled = true;
        txt.classList.add('d-none');
        spin.classList.remove('d-none');

        var payload = {
            name:        document.getElementById('clm-name').value.trim(),
            email:       document.getElementById('clm-email').value.trim(),
            phone:       document.getElementById('clm-phone').value.trim(),
            message:     document.getElementById('clm-message').value.trim(),
            loss_amount: document.getElementById('clm-loss-amount').value,
            loss_type:   document.getElementById('clm-loss-type').value
        };

        fetch('contact.php', {
            method:  'POST',
            headers: {'Content-Type': 'application/json'},
            body:    JSON.stringify(payload)
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            fb.className = 'alert ' + (res.success ? 'alert-success' : 'alert-danger');
            fb.textContent = res.message;
            if (res.success) {
                form.reset();
                btn.disabled = true;
                txt.textContent = '✓ Gesendet';
                txt.classList.remove('d-none');
                spin.classList.add('d-none');
            } else {
                btn.disabled = false;
                txt.classList.remove('d-none');
                spin.classList.add('d-none');
            }
        })
        .catch(function(){
            fb.className = 'alert alert-danger';
            fb.textContent = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
            btn.disabled = false;
            txt.classList.remove('d-none');
            spin.classList.add('d-none');
        });
    });
})();
</script>

<!-- ===== KI-Betrugserkennung – 3D-Animations-Sektion ===== -->
<section id="ai-recovery-scene" style="position:relative;overflow:hidden;background:#04091a;padding:0;min-height:720px;display:flex;align-items:center;">

  <!-- THREE.JS CANVAS fills the background -->
  <canvas id="recovery-canvas" style="position:absolute;inset:0;width:100%;height:100%;display:block;"></canvas>

  <!-- Scanline overlay for "live video" feel -->
  <div style="position:absolute;inset:0;background:repeating-linear-gradient(0deg,transparent,transparent 3px,rgba(0,255,180,.018) 3px,rgba(0,255,180,.018) 4px);pointer-events:none;z-index:1;"></div>

  <!-- Live badge -->
  <div style="position:absolute;top:24px;left:32px;z-index:10;display:flex;align-items:center;gap:8px;">
    <span style="width:10px;height:10px;border-radius:50%;background:#ff3c3c;display:inline-block;animation:livePulse 1.2s infinite;box-shadow:0 0 8px #ff3c3c;"></span>
    <span style="color:#fff;font-size:.82rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;opacity:.9;">LIVE</span>
    <span style="color:#64748b;font-size:.78rem;margin-left:6px;" id="live-clock">–</span>
  </div>

  <!-- Live transaction ticker (top-right) -->
  <div id="live-ticker-wrap" style="position:absolute;top:22px;right:24px;z-index:10;max-width:320px;overflow:hidden;">
    <div id="live-ticker" style="color:#00ffb4;font-size:.78rem;font-weight:600;font-family:monospace;white-space:nowrap;background:rgba(0,255,180,.08);border:1px solid rgba(0,255,180,.2);border-radius:8px;padding:6px 14px;">
      <i class="fas fa-satellite-dish me-1" style="color:#ff3c3c;animation:livePulse 1.2s infinite;"></i>
      <span id="ticker-text">Verbinde mit Blockchain-Netzwerk …</span>
    </div>
  </div>

  <!-- Overlay content -->
  <div class="container position-relative" style="z-index:5;padding:96px 16px 80px;">
    <div class="row align-items-center gy-5">

      <!-- Left: copy -->
      <div class="col-lg-6">
        <span style="display:inline-block;background:rgba(0,255,180,.12);border:1px solid rgba(0,255,180,.35);color:#00ffb4;font-size:.8rem;font-weight:700;letter-spacing:.1em;border-radius:20px;padding:5px 16px;margin-bottom:18px;text-transform:uppercase;">
          <i class="fas fa-shield-alt me-2"></i>KI-gestützte Krypto-Verlust-Rückgewinnung
        </span>

        <h2 style="color:#fff;font-size:clamp(1.9rem,4.5vw,2.9rem);font-weight:800;line-height:1.18;margin-bottom:20px;">
          Gestohlene Gelder zurückholen<br>
          <span style="background:linear-gradient(90deg,#00ffb4,#00c6ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">mit Deep-Chain AI&trade;</span>
        </h2>

        <p style="color:#94a3b8;font-size:1.05rem;max-width:470px;line-height:1.8;margin-bottom:28px;">
          Unser autonomes KI-System verfolgt betrügerische Transaktionen über <strong style="color:#fff;">120+ Blockchains</strong>,
          rekonstruiert Wallet-Graphen in Millisekunden und leitet BaFin-konforme Rückgewinnungsverfahren ein –
          vollautomatisch, rund um die Uhr.
        </p>

        <!-- Live stats row -->
        <div style="display:flex;flex-wrap:wrap;gap:14px;margin-bottom:32px;">
          <div style="background:rgba(0,255,180,.07);border:1px solid rgba(0,255,180,.18);border-radius:12px;padding:14px 20px;min-width:120px;">
            <div style="color:#00ffb4;font-size:1.65rem;font-weight:800;line-height:1;" id="stat-cases">0</div>
            <div style="color:#64748b;font-size:.78rem;margin-top:5px;">Aktive Fälle</div>
          </div>
          <div style="background:rgba(0,198,255,.07);border:1px solid rgba(0,198,255,.18);border-radius:12px;padding:14px 20px;min-width:120px;">
            <div style="color:#00c6ff;font-size:1.65rem;font-weight:800;line-height:1;" id="stat-recovered">€0M</div>
            <div style="color:#64748b;font-size:.78rem;margin-top:5px;">Zurückgeholt</div>
          </div>
          <div style="background:rgba(167,139,250,.07);border:1px solid rgba(167,139,250,.18);border-radius:12px;padding:14px 20px;min-width:120px;">
            <div style="color:#a78bfa;font-size:1.65rem;font-weight:800;line-height:1;" id="stat-rate">0%</div>
            <div style="color:#64748b;font-size:.78rem;margin-top:5px;">Erfolgsquote</div>
          </div>
          <div style="background:rgba(255,215,0,.07);border:1px solid rgba(255,215,0,.18);border-radius:12px;padding:14px 20px;min-width:120px;">
            <div style="color:#ffd700;font-size:1.65rem;font-weight:800;line-height:1;" id="stat-chains">0+</div>
            <div style="color:#64748b;font-size:.78rem;margin-top:5px;">Blockchains</div>
          </div>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <a href="#" class="btn" data-bs-toggle="modal" data-bs-target="#contactLeadModal" style="background:linear-gradient(135deg,#00c6ff,#00ffb4);border:none;color:#04091a;font-weight:700;padding:14px 30px;border-radius:10px;font-size:1rem;box-shadow:0 0 28px rgba(0,198,255,.35);">
            <i class="fas fa-search-dollar me-2"></i>Wiederherstellung starten
          </a>
          <a href="#verluste-info" class="btn" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.18);color:#fff;font-weight:600;padding:14px 26px;border-radius:10px;font-size:.95rem;">
            <i class="fas fa-info-circle me-2"></i>Mehr erfahren
          </a>
        </div>
      </div>

      <!-- Right: info panels -->
      <div class="col-lg-6">
        <div style="display:flex;flex-direction:column;gap:13px;">

          <div style="background:rgba(255,255,255,.05);border:1px solid rgba(0,255,180,.2);border-radius:14px;padding:18px 22px;backdrop-filter:blur(8px);">
            <div style="display:flex;align-items:center;gap:14px;">
              <div style="width:44px;height:44px;border-radius:10px;background:rgba(0,255,180,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-network-wired" style="color:#00ffb4;font-size:1.2rem;"></i>
              </div>
              <div>
                <div style="color:#fff;font-weight:700;font-size:.95rem;">Blockchain-Verfolgungsengine</div>
                <div style="color:#64748b;font-size:.82rem;margin-top:3px;">Echtzeit-Graphanalyse: BTC, ETH, BSC, SOL, TRON und 115 weitere Netzwerke</div>
              </div>
              <div style="margin-left:auto;flex-shrink:0;">
                <span style="background:rgba(0,255,180,.15);color:#00ffb4;font-size:.7rem;font-weight:700;border-radius:20px;padding:3px 10px;">AKTIV</span>
              </div>
            </div>
          </div>

          <div style="background:rgba(255,255,255,.05);border:1px solid rgba(0,198,255,.2);border-radius:14px;padding:18px 22px;backdrop-filter:blur(8px);">
            <div style="display:flex;align-items:center;gap:14px;">
              <div style="width:44px;height:44px;border-radius:10px;background:rgba(0,198,255,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-brain" style="color:#00c6ff;font-size:1.2rem;"></i>
              </div>
              <div>
                <div style="color:#fff;font-weight:700;font-size:.95rem;">Deep-Learning Mustererkennung</div>
                <div style="color:#64748b;font-size:.82rem;margin-top:3px;">Erkennt Mixer-Verschleierung, Exchange-Sprünge und Money-Mule-Netzwerke</div>
              </div>
              <div style="margin-left:auto;flex-shrink:0;">
                <span style="background:rgba(0,198,255,.15);color:#00c6ff;font-size:.7rem;font-weight:700;border-radius:20px;padding:3px 10px;">KI</span>
              </div>
            </div>
          </div>

          <div style="background:rgba(255,255,255,.05);border:1px solid rgba(167,139,250,.2);border-radius:14px;padding:18px 22px;backdrop-filter:blur(8px);">
            <div style="display:flex;align-items:center;gap:14px;">
              <div style="width:44px;height:44px;border-radius:10px;background:rgba(167,139,250,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-gavel" style="color:#a78bfa;font-size:1.2rem;"></i>
              </div>
              <div>
                <div style="color:#fff;font-weight:700;font-size:.95rem;">Regulierte Rechtseskalation</div>
                <div style="color:#64748b;font-size:.82rem;margin-top:3px;">Automatisierte Beweispakete an BaFin, Europol & Interpol übermittelt</div>
              </div>
              <div style="margin-left:auto;flex-shrink:0;">
                <span style="background:rgba(167,139,250,.15);color:#a78bfa;font-size:.7rem;font-weight:700;border-radius:20px;padding:3px 10px;">BaFin</span>
              </div>
            </div>
          </div>

          <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,215,0,.2);border-radius:14px;padding:18px 22px;backdrop-filter:blur(8px);">
            <div style="display:flex;align-items:center;gap:14px;">
              <div style="width:44px;height:44px;border-radius:10px;background:rgba(255,215,0,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-fingerprint" style="color:#ffd700;font-size:1.2rem;"></i>
              </div>
              <div>
                <div style="color:#fff;font-weight:700;font-size:.95rem;">Kryptografische Forensik</div>
                <div style="color:#64748b;font-size:.82rem;margin-top:3px;">Wallet-Fingerabdruck, Signaturanalyse und On-Chain-Beweissicherung</div>
              </div>
              <div style="margin-left:auto;flex-shrink:0;">
                <span style="background:rgba(255,215,0,.12);color:#ffd700;font-size:.7rem;font-weight:700;border-radius:20px;padding:3px 10px;">FORENSIK</span>
              </div>
            </div>
          </div>

          <!-- Progress bars -->
          <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:16px 22px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
              <span style="color:#94a3b8;font-size:.82rem;">Gesamterfolgsquote</span>
              <span style="color:#00ffb4;font-weight:700;font-size:.82rem;" id="progress-label">87%</span>
            </div>
            <div style="background:rgba(255,255,255,.07);border-radius:999px;height:6px;overflow:hidden;margin-bottom:10px;">
              <div id="recovery-bar" style="height:100%;width:0%;border-radius:999px;background:linear-gradient(90deg,#00c6ff,#00ffb4);transition:width 2.2s ease-out;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
              <span style="color:#94a3b8;font-size:.82rem;">Kundenzufriedenheit</span>
              <span style="color:#a78bfa;font-weight:700;font-size:.82rem;">96%</span>
            </div>
            <div style="background:rgba(255,255,255,.07);border-radius:999px;height:6px;overflow:hidden;">
              <div id="satisfaction-bar" style="height:100%;width:0%;border-radius:999px;background:linear-gradient(90deg,#a78bfa,#00c6ff);transition:width 2.5s ease-out;"></div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div><!-- /container -->

</section>

<!-- ===== Recovery Section CSS ===== -->
<style>
@keyframes livePulse {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:.5; transform:scale(1.4); }
}
@keyframes tickerSlide {
  0%   { opacity:0; transform:translateY(6px); }
  10%  { opacity:1; transform:translateY(0); }
  85%  { opacity:1; transform:translateY(0); }
  100% { opacity:0; transform:translateY(-6px); }
}
.ticker-animate { animation: tickerSlide 4s ease forwards; }
</style>

<!-- ===== Three.js 3D Recovery Canvas ===== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js" crossorigin="anonymous"></script>
<script>
(function () {
  'use strict';

  /* ---- Live clock ---- */
  function updateClock() {
    var el = document.getElementById('live-clock');
    if (!el) return;
    /* Display time in Europe/Berlin timezone (CET/CEST) regardless of user locale */
    var now = new Date();
    var timeStr = now.toLocaleTimeString('de-DE', { timeZone: 'Europe/Berlin', hour12: false });
    el.textContent = timeStr + ' MEZ';
  }
  updateClock();
  setInterval(updateClock, 1000);

  /* ---- Live transaction ticker ---- */
  var tickerMessages = [
    '✅ Transaktion gesichert: +€ 34.820 — München',
    '🔍 Wallet-Cluster identifiziert: Binance Smart Chain',
    '✅ Rückgewinnung abgeschlossen: +€ 12.450 — Hamburg',
    '⚡ KI-Analyse läuft: 4.217 Transaktionen geprüft',
    '✅ SEPA-Überweisung erfolgreich: +€ 89.000 — Berlin',
    '🔍 Betrüger-Adresse gesperrt: 0x4f3a…9c2e',
    '✅ Fall abgeschlossen: +€ 156.300 — Frankfurt',
    '⚡ Neue Spur entdeckt: Tornado Cash Bypass erkannt',
    '✅ Gelder freigegeben: +€ 22.700 — Düsseldorf',
    '🔍 Blockchain-Forensik: 7 Wallet-Hops zurückverfolgt',
    '✅ Interpol-Kooperation: Täter identifiziert — Wien',
    '⚡ Echtzeit-Scan: 14 Blockchains überwacht',
  ];
  var tickerIdx = 0;
  function rotateTicker() {
    var el = document.getElementById('ticker-text');
    if (!el) return;
    el.textContent = tickerMessages[tickerIdx % tickerMessages.length];
    el.classList.remove('ticker-animate');
    void el.offsetWidth; /* Force reflow to restart CSS animation */
    el.classList.add('ticker-animate');
    tickerIdx++;
  }
  setTimeout(function () { rotateTicker(); setInterval(rotateTicker, 4200); }, 1800);

  /* ---- Counter animation helper ---- */
  function animateCounter(el, target, suffix, duration, isFloat) {
    if (!el) return;
    var startTime = null;
    function step(ts) {
      if (!startTime) startTime = ts;
      var progress = Math.min((ts - startTime) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      var val = isFloat ? (eased * target).toFixed(1) : Math.round(eased * target);
      el.textContent = val + suffix;
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  /* Trigger counters + progress bar once section enters viewport */
  var STAT_CASES     = 2134;
  var STAT_RECOVERED = 47.3;  /* millions EUR */
  var STAT_RATE      = 87;    /* percent */
  var STAT_CHAINS    = 120;
  var statsTriggered = false;
  var recoverySection = document.getElementById('ai-recovery-scene');
  var io = new IntersectionObserver(function (entries) {
    if (statsTriggered) return;
    entries.forEach(function (e) {
      if (e.isIntersecting) {
        statsTriggered = true;
        animateCounter(document.getElementById('stat-cases'),     STAT_CASES,     '',   1800, false);
        animateCounter(document.getElementById('stat-recovered'),  STAT_RECOVERED, 'M',  2000, true);
        animateCounter(document.getElementById('stat-rate'),       STAT_RATE,      '%',  1600, false);
        animateCounter(document.getElementById('stat-chains'),     STAT_CHAINS,    '+',  1400, false);
        setTimeout(function () {
          document.getElementById('recovery-bar').style.width = '87%';
          document.getElementById('satisfaction-bar').style.width = '96%';
        }, 300);
      }
    });
  }, { threshold: 0.2 });
  if (recoverySection) io.observe(recoverySection);

  /* ---- THREE.JS 3D scene ---- */
  var canvas = document.getElementById('recovery-canvas');
  if (!canvas || typeof THREE === 'undefined') {
    console.warn('[AI Recovery] Three.js canvas or library unavailable – 3D scene skipped.');
    return;
  }

  var W = canvas.parentElement.offsetWidth  || window.innerWidth;
  var H = canvas.parentElement.offsetHeight || 720;

  /* Renderer */
  var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setSize(W, H);
  renderer.setClearColor(0x04091a, 1);

  /* Scene + Camera */
  var scene  = new THREE.Scene();
  var camera = new THREE.PerspectiveCamera(52, W / H, 0.1, 2000);
  camera.position.set(0, 30, 270);

  /* Fog */
  scene.fog = new THREE.FogExp2(0x04091a, 0.002);

  /* ---------- Starfield background ---------- */
  var starGeo = new THREE.BufferGeometry();
  var starCnt = 1800;
  var starPos = new Float32Array(starCnt * 3);
  for (var i = 0; i < starCnt * 3; i++) starPos[i] = (Math.random() - 0.5) * 1800;
  starGeo.setAttribute('position', new THREE.BufferAttribute(starPos, 3));
  var starMat = new THREE.PointsMaterial({ color: 0xffffff, size: 0.65, transparent: true, opacity: 0.6 });
  scene.add(new THREE.Points(starGeo, starMat));

  /* ---------- Wireframe globe ---------- */
  var globeGeo  = new THREE.IcosahedronGeometry(100, 4);
  var globeMat  = new THREE.MeshBasicMaterial({ color: 0x00c6ff, wireframe: true, transparent: true, opacity: 0.14 });
  var globe     = new THREE.Mesh(globeGeo, globeMat);
  scene.add(globe);

  /* ---------- Outer shell (semi-transparent) ---------- */
  var shellGeo = new THREE.SphereGeometry(103, 32, 32);
  var shellMat = new THREE.MeshBasicMaterial({ color: 0x001a33, transparent: true, opacity: 0.15, side: THREE.BackSide });
  scene.add(new THREE.Mesh(shellGeo, shellMat));

  /* ---------- Inner glowing core ---------- */
  var coreGeo = new THREE.SphereGeometry(16, 32, 32);
  var coreMat = new THREE.MeshBasicMaterial({ color: 0x00ffb4, transparent: true, opacity: 0.6 });
  var core    = new THREE.Mesh(coreGeo, coreMat);
  scene.add(core);

  /* ---------- Core glow rings (orbital planes) ---------- */
  var rings = [];
  var ringColors = [0x00ffb4, 0x00c6ff, 0xa78bfa];
  for (var r = 0; r < 4; r++) {
    var rGeo = new THREE.TorusGeometry(22 + r * 9, 0.45, 6, 80);
    var rMat = new THREE.MeshBasicMaterial({
      color: ringColors[r % ringColors.length],
      transparent: true,
      opacity: 0.28 - r * 0.05
    });
    var ring = new THREE.Mesh(rGeo, rMat);
    ring.rotation.x = Math.PI / 2.2 + r * 0.45;
    ring.rotation.z = r * 0.8;
    scene.add(ring);
    rings.push({ mesh: ring, speedX: 0.003 + r * 0.001, speedZ: 0.002 + r * 0.0008 });
  }

  /* ---------- Network nodes on sphere surface ---------- */
  var nodeGroup = new THREE.Group();
  scene.add(nodeGroup);
  var nodeMeshes = [];
  var nodeCount  = 80;
  /* Color coding: green=recovered, cyan=tracked, purple=analyzing, yellow=flagged */
  var nodeColors = [0x00ffb4, 0x00c6ff, 0xa78bfa, 0xffd700, 0xff6b6b];

  for (var n = 0; n < nodeCount; n++) {
    var phi   = Math.acos(-1 + (2 * n) / nodeCount);
    var theta = Math.sqrt(nodeCount * Math.PI) * phi;
    var nSize = 1.2 + Math.random() * 1.4;
    var nodeGeo = new THREE.SphereGeometry(nSize, 8, 8);
    var nodeMat = new THREE.MeshBasicMaterial({
      color: nodeColors[n % nodeColors.length],
      transparent: true,
      opacity: 0.85 + Math.random() * 0.15
    });
    var node = new THREE.Mesh(nodeGeo, nodeMat);
    node.position.setFromSphericalCoords(101, phi, theta);
    nodeGroup.add(node);
    nodeMeshes.push(node);
  }

  /* ---------- Connection lines ---------- */
  var lineGroup = new THREE.Group();
  scene.add(lineGroup);

  /* Two line styles: main cyan + secondary purple */
  var lineMats = [
    new THREE.LineBasicMaterial({ color: 0x00c6ff, transparent: true, opacity: 0.18 }),
    new THREE.LineBasicMaterial({ color: 0xa78bfa, transparent: true, opacity: 0.12 }),
  ];

  for (var a = 0; a < nodeCount; a++) {
    for (var b = a + 1; b < nodeCount; b++) {
      var dist = nodeMeshes[a].position.distanceTo(nodeMeshes[b].position);
      if (dist < 50) {
        var lGeo = new THREE.BufferGeometry().setFromPoints([
          nodeMeshes[a].position.clone(),
          nodeMeshes[b].position.clone()
        ]);
        lineGroup.add(new THREE.Line(lGeo, lineMats[b % 2]));
      }
    }
  }

  /* ---------- Flowing data particles (green – recovered funds) ---------- */
  var Y_BOUNDARY   = 95;
  var particleCount = 280;
  var pGeo = new THREE.BufferGeometry();
  var pPositions  = new Float32Array(particleCount * 3);
  var pVelocities = [];
  for (var p = 0; p < particleCount; p++) {
    var angle  = Math.random() * Math.PI * 2;
    var radius = 38 + Math.random() * 85;
    pPositions[p * 3]     = Math.cos(angle) * radius;
    pPositions[p * 3 + 1] = (Math.random() - 0.5) * 190;
    pPositions[p * 3 + 2] = Math.sin(angle) * radius;
    pVelocities.push({
      r: radius, angle: angle,
      speed:  0.004 + Math.random() * 0.010,
      y:      pPositions[p * 3 + 1],
      ySpeed: (Math.random() - 0.5) * 0.3
    });
  }
  pGeo.setAttribute('position', new THREE.BufferAttribute(pPositions, 3));
  var pMat = new THREE.PointsMaterial({ color: 0x00ffb4, size: 1.9, transparent: true, opacity: 0.78 });
  var particles = new THREE.Points(pGeo, pMat);
  scene.add(particles);

  /* ---------- Shooting comets (fast bright traces) ---------- */
  var cometCount = 12;
  var cometGeo   = new THREE.BufferGeometry();
  var cometPos   = new Float32Array(cometCount * 3);
  var cometData  = [];
  function resetComet(i) {
    var startAngle = Math.random() * Math.PI * 2;
    var startPhi   = Math.random() * Math.PI;
    var r = 110 + Math.random() * 80;
    cometData[i] = {
      x: Math.sin(startPhi) * Math.cos(startAngle) * r,
      y: Math.cos(startPhi) * r,
      z: Math.sin(startPhi) * Math.sin(startAngle) * r,
      vx: (Math.random() - 0.5) * 5,
      vy: (Math.random() - 0.5) * 5,
      vz: (Math.random() - 0.5) * 5,
      life: 0,
      maxLife: 60 + Math.random() * 60
    };
  }
  for (var c = 0; c < cometCount; c++) { resetComet(c); cometData[c].life = Math.random() * 60; }
  cometGeo.setAttribute('position', new THREE.BufferAttribute(cometPos, 3));
  var cometMat  = new THREE.PointsMaterial({ color: 0xffffff, size: 3.5, transparent: true, opacity: 0.95 });
  var comets    = new THREE.Points(cometGeo, cometMat);
  scene.add(comets);

  /* ---------- Ambient light ---------- */
  scene.add(new THREE.AmbientLight(0xffffff, 0.5));

  /* ---------- Animation loop ---------- */
  var clock = new THREE.Clock();
  function animate() {
    requestAnimationFrame(animate);
    var t = clock.getElapsedTime();

    /* Rotate globe + attached groups */
    globe.rotation.y  += 0.0018;
    globe.rotation.x  += 0.0004;
    nodeGroup.rotation.y = globe.rotation.y;
    nodeGroup.rotation.x = globe.rotation.x;
    lineGroup.rotation.y = globe.rotation.y;
    lineGroup.rotation.x = globe.rotation.x;

    /* Animate orbital rings independently */
    rings.forEach(function (rObj) {
      rObj.mesh.rotation.x += rObj.speedX;
      rObj.mesh.rotation.z += rObj.speedZ;
    });

    /* Pulse core with multi-frequency oscillation */
    var pulse = 0.5 + 0.5 * Math.sin(t * 2.2 + 0.5);
    var pulse2= 0.5 + 0.5 * Math.sin(t * 3.7);
    core.scale.setScalar(0.85 + 0.2 * pulse + 0.05 * pulse2);
    coreMat.opacity = 0.38 + 0.28 * pulse;

    /* Move data particles */
    var pos = particles.geometry.attributes.position.array;
    for (var i = 0; i < particleCount; i++) {
      var v = pVelocities[i];
      v.angle += v.speed;
      v.y     += v.ySpeed;
      if (v.y >  Y_BOUNDARY) { v.y = -Y_BOUNDARY; }
      if (v.y < -Y_BOUNDARY) { v.y =  Y_BOUNDARY; }
      pos[i * 3]     = Math.cos(v.angle) * v.r;
      pos[i * 3 + 1] = v.y;
      pos[i * 3 + 2] = Math.sin(v.angle) * v.r;
    }
    particles.geometry.attributes.position.needsUpdate = true;

    /* Shoot comets */
    var cPos = comets.geometry.attributes.position.array;
    for (var j = 0; j < cometCount; j++) {
      var cd = cometData[j];
      cd.life++;
      if (cd.life > cd.maxLife) { resetComet(j); }
      cd.x += cd.vx; cd.y += cd.vy; cd.z += cd.vz;
      cPos[j * 3]     = cd.x;
      cPos[j * 3 + 1] = cd.y;
      cPos[j * 3 + 2] = cd.z;
    }
    comets.geometry.attributes.position.needsUpdate = true;
    /* Fade comets opacity with time */
    cometMat.opacity = 0.7 + 0.3 * Math.sin(t * 4);

    /* Camera dramatic orbit */
    camera.position.x = Math.sin(t * 0.09) * 28;
    camera.position.y = 30 + Math.cos(t * 0.07) * 18;
    camera.position.z = 260 + Math.sin(t * 0.05) * 15;
    camera.lookAt(scene.position);

    renderer.render(scene, camera);
  }
  animate();

  /* ---------- Responsive resize ---------- */
  window.addEventListener('resize', function () {
    var parent = canvas.parentElement;
    W = parent.offsetWidth;
    H = Math.max(parent.offsetHeight, 720);
    renderer.setSize(W, H);
    camera.aspect = W / H;
    camera.updateProjectionMatrix();
  });
})();
</script>

<!-- ===== Verluste Zurückgewinnen – Informationssektion ===== -->
<section id="verluste-info" style="background:#060d1f;padding:100px 0;border-top:1px solid rgba(0,198,255,.1);">
  <div class="container">

    <!-- Section header -->
    <div class="text-center mb-5">
      <span style="display:inline-block;background:rgba(0,198,255,.1);border:1px solid rgba(0,198,255,.25);color:#00c6ff;font-size:.78rem;font-weight:700;letter-spacing:.12em;border-radius:20px;padding:5px 18px;margin-bottom:16px;text-transform:uppercase;">
        <i class="fas fa-coins me-2"></i>Krypto-Verlust-Rückgewinnung
      </span>
      <h2 style="color:#fff;font-size:clamp(1.8rem,4vw,2.7rem);font-weight:800;margin-bottom:16px;">
        Welche Verluste können wir zurückgewinnen?
      </h2>
      <p style="color:#64748b;font-size:1.05rem;max-width:680px;margin:0 auto;line-height:1.8;">
        Unsere KI-gestützte Forensik deckt ein breites Spektrum an Kryptowährungsbetrug auf –
        von einfachem Wallet-Diebstahl bis hin zu komplexen internationalen Betrugsnetzwerken.
        Im Folgenden finden Sie die häufigsten Kategorien, bei denen wir erfolgreich helfen konnten.
      </p>
    </div>

    <!-- Loss types grid -->
    <div class="row g-4 mb-5">

      <div class="col-md-6 col-lg-4">
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,107,107,.2);border-radius:16px;padding:28px;height:100%;transition:border-color .3s,transform .3s;" onmouseover="this.style.borderColor='rgba(255,107,107,.5)';this.style.transform='translateY(-4px)';" onmouseout="this.style.borderColor='rgba(255,107,107,.2)';this.style.transform='translateY(0)';">
          <div style="width:52px;height:52px;border-radius:14px;background:rgba(255,107,107,.15);display:flex;align-items:center;justify-content:center;margin-bottom:18px;">
            <i class="fas fa-heart-broken" style="color:#ff6b6b;font-size:1.4rem;"></i>
          </div>
          <h5 style="color:#fff;font-weight:700;margin-bottom:10px;">Romance Scam</h5>
          <p style="color:#64748b;font-size:.9rem;line-height:1.7;margin-bottom:14px;">
            Betrüger bauen über Monate emotionale Bindungen auf und fordern dann „Investitionen" oder
            „Notfallzahlungen" in Kryptowährungen. Durchschnittlicher Schaden: <strong style="color:#ff6b6b;">€ 38.000 – € 180.000</strong>.
          </p>
          <div style="background:rgba(255,107,107,.08);border-radius:8px;padding:10px 14px;font-size:.82rem;color:#94a3b8;">
            <i class="fas fa-check-circle me-2" style="color:#00ffb4;"></i>Häufig vollständig rückgewinnbar bei früher Meldung
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,215,0,.2);border-radius:16px;padding:28px;height:100%;transition:border-color .3s,transform .3s;" onmouseover="this.style.borderColor='rgba(255,215,0,.5)';this.style.transform='translateY(-4px)';" onmouseout="this.style.borderColor='rgba(255,215,0,.2)';this.style.transform='translateY(0)';">
          <div style="width:52px;height:52px;border-radius:14px;background:rgba(255,215,0,.12);display:flex;align-items:center;justify-content:center;margin-bottom:18px;">
            <i class="fas fa-exchange-alt" style="color:#ffd700;font-size:1.4rem;"></i>
          </div>
          <h5 style="color:#fff;font-weight:700;margin-bottom:10px;">Gefälschte Handelsplattformen</h5>
          <p style="color:#64748b;font-size:.9rem;line-height:1.7;margin-bottom:14px;">
            Professionell gestaltete Fake-Exchanges ermöglichen zunächst kleine Gewinne, frieren dann
            Konten ein oder verlangen unzählige „Verifizierungsgebühren". Schaden: <strong style="color:#ffd700;">bis zu € 500.000</strong>.
          </p>
          <div style="background:rgba(255,215,0,.07);border-radius:8px;padding:10px 14px;font-size:.82rem;color:#94a3b8;">
            <i class="fas fa-check-circle me-2" style="color:#00ffb4;"></i>On-Chain-Verfolgung möglich – auch über mehrere Exchanges
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(167,139,250,.2);border-radius:16px;padding:28px;height:100%;transition:border-color .3s,transform .3s;" onmouseover="this.style.borderColor='rgba(167,139,250,.5)';this.style.transform='translateY(-4px)';" onmouseout="this.style.borderColor='rgba(167,139,250,.2)';this.style.transform='translateY(0)';">
          <div style="width:52px;height:52px;border-radius:14px;background:rgba(167,139,250,.15);display:flex;align-items:center;justify-content:center;margin-bottom:18px;">
            <i class="fas fa-chart-line" style="color:#a78bfa;font-size:1.4rem;"></i>
          </div>
          <h5 style="color:#fff;font-weight:700;margin-bottom:10px;">Pig Butchering (SHA ZHU PAN)</h5>
          <p style="color:#64748b;font-size:.9rem;line-height:1.7;margin-bottom:14px;">
            Eine der gefährlichsten Betrugsmaschen: Opfer werden über Monate „gemästet" –
            kleine Gewinne auszahlen, dann alles auf einmal abziehen. Schaden: <strong style="color:#a78bfa;">€ 50.000 – € 1 Mio+</strong>.
          </p>
          <div style="background:rgba(167,139,250,.08);border-radius:8px;padding:10px 14px;font-size:.82rem;color:#94a3b8;">
            <i class="fas fa-check-circle me-2" style="color:#00ffb4;"></i>Spezialisiertes KI-Modell für SHA ZHU PAN-Muster
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(0,198,255,.2);border-radius:16px;padding:28px;height:100%;transition:border-color .3s,transform .3s;" onmouseover="this.style.borderColor='rgba(0,198,255,.5)';this.style.transform='translateY(-4px)';" onmouseout="this.style.borderColor='rgba(0,198,255,.2)';this.style.transform='translateY(0)';">
          <div style="width:52px;height:52px;border-radius:14px;background:rgba(0,198,255,.12);display:flex;align-items:center;justify-content:center;margin-bottom:18px;">
            <i class="fas fa-fish" style="color:#00c6ff;font-size:1.4rem;"></i>
          </div>
          <h5 style="color:#fff;font-weight:700;margin-bottom:10px;">Phishing & Wallet-Diebstahl</h5>
          <p style="color:#64748b;font-size:.9rem;line-height:1.7;margin-bottom:14px;">
            Gefälschte Websites, Seed-Phrase-Diebstahl und Drainer-Smart-Contracts leeren Wallets
            in Sekunden. Schaden variiert: <strong style="color:#00c6ff;">€ 1.000 – mehrere Millionen</strong>.
          </p>
          <div style="background:rgba(0,198,255,.07);border-radius:8px;padding:10px 14px;font-size:.82rem;color:#94a3b8;">
            <i class="fas fa-check-circle me-2" style="color:#00ffb4;"></i>Schnelles Einfrieren gestohlener Gelder möglich
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(0,255,180,.2);border-radius:16px;padding:28px;height:100%;transition:border-color .3s,transform .3s;" onmouseover="this.style.borderColor='rgba(0,255,180,.5)';this.style.transform='translateY(-4px)';" onmouseout="this.style.borderColor='rgba(0,255,180,.2)';this.style.transform='translateY(0)';">
          <div style="width:52px;height:52px;border-radius:14px;background:rgba(0,255,180,.12);display:flex;align-items:center;justify-content:center;margin-bottom:18px;">
            <i class="fas fa-rug" style="color:#00ffb4;font-size:1.4rem;"></i>
          </div>
          <h5 style="color:#fff;font-weight:700;margin-bottom:10px;">Rug Pull & Token-Betrug</h5>
          <p style="color:#64748b;font-size:.9rem;line-height:1.7;margin-bottom:14px;">
            Entwickler ziehen nach Token-Launch abrupt alle Liquidität ab. Projektbetrug,
            Honeypots und Exit-Scams vernichten oft <strong style="color:#00ffb4;">Millionen innerhalb von Minuten</strong>.
          </p>
          <div style="background:rgba(0,255,180,.07);border-radius:8px;padding:10px 14px;font-size:.82rem;color:#94a3b8;">
            <i class="fas fa-check-circle me-2" style="color:#00ffb4;"></i>Smart-Contract-Analyse & Entwickler-Deanonymisierung
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(251,146,60,.2);border-radius:16px;padding:28px;height:100%;transition:border-color .3s,transform .3s;" onmouseover="this.style.borderColor='rgba(251,146,60,.5)';this.style.transform='translateY(-4px)';" onmouseout="this.style.borderColor='rgba(251,146,60,.2)';this.style.transform='translateY(0)';">
          <div style="width:52px;height:52px;border-radius:14px;background:rgba(251,146,60,.12);display:flex;align-items:center;justify-content:center;margin-bottom:18px;">
            <i class="fas fa-user-secret" style="color:#fb923c;font-size:1.4rem;"></i>
          </div>
          <h5 style="color:#fff;font-weight:700;margin-bottom:10px;">Investitionsbetrüger & Falsche Berater</h5>
          <p style="color:#64748b;font-size:.9rem;line-height:1.7;margin-bottom:14px;">
            Lizenzlose „Trader" versprechen garantierte Renditen und verwalten Gelder eigenständig –
            bis sie verschwinden. Schaden: <strong style="color:#fb923c;">€ 10.000 – € 800.000</strong>.
          </p>
          <div style="background:rgba(251,146,60,.07);border-radius:8px;padding:10px 14px;font-size:.82rem;color:#94a3b8;">
            <i class="fas fa-check-circle me-2" style="color:#00ffb4;"></i>Behördliche Strafanzeige und Zivilklage unterstützt
          </div>
        </div>
      </div>

    </div><!-- /row loss types -->

    <!-- Recovery process timeline -->
    <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:20px;padding:48px;margin-bottom:60px;">
      <div class="text-center mb-5">
        <h3 style="color:#fff;font-weight:800;font-size:1.8rem;margin-bottom:10px;">
          <i class="fas fa-route me-3" style="color:#00c6ff;"></i>So läuft die Rückgewinnung ab
        </h3>
        <p style="color:#64748b;max-width:560px;margin:0 auto;font-size:.95rem;">
          Ein strukturierter, transparenter Prozess – von der ersten Kontaktaufnahme bis zur Auszahlung auf Ihr Bankkonto.
        </p>
      </div>

      <div class="row g-0" style="position:relative;">
        <!-- Timeline connector line -->
        <div class="d-none d-lg-block" style="position:absolute;top:36px;left:calc(8.33% + 36px);right:calc(8.33% + 36px);height:2px;background:linear-gradient(90deg,#00ffb4,#00c6ff,#a78bfa,#ffd700,#00ffb4);z-index:0;"></div>

        <div class="col-lg col-md-6 col-12 mb-4 mb-lg-0 text-center px-3" style="position:relative;z-index:1;">
          <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#00ffb4,#00c6ff);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 24px rgba(0,255,180,.4);">
            <i class="fas fa-comment-dots" style="color:#04091a;font-size:1.5rem;"></i>
          </div>
          <div style="background:rgba(0,255,180,.07);border:1px solid rgba(0,255,180,.2);border-radius:12px;padding:16px 12px;">
            <div style="color:#00ffb4;font-weight:800;font-size:.78rem;letter-spacing:.08em;margin-bottom:6px;">SCHRITT 1</div>
            <div style="color:#fff;font-weight:700;margin-bottom:6px;font-size:.95rem;">Kostenlose Erstberatung</div>
            <div style="color:#64748b;font-size:.82rem;line-height:1.6;">Schildern Sie uns Ihren Fall. Innerhalb von 24 h erhalten Sie eine Ersteinschätzung.</div>
          </div>
        </div>

        <div class="col-lg col-md-6 col-12 mb-4 mb-lg-0 text-center px-3" style="position:relative;z-index:1;">
          <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#00c6ff,#a78bfa);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 24px rgba(0,198,255,.4);">
            <i class="fas fa-search" style="color:#fff;font-size:1.5rem;"></i>
          </div>
          <div style="background:rgba(0,198,255,.07);border:1px solid rgba(0,198,255,.2);border-radius:12px;padding:16px 12px;">
            <div style="color:#00c6ff;font-weight:800;font-size:.78rem;letter-spacing:.08em;margin-bottom:6px;">SCHRITT 2</div>
            <div style="color:#fff;font-weight:700;margin-bottom:6px;font-size:.95rem;">KI-Forensik-Analyse</div>
            <div style="color:#64748b;font-size:.82rem;line-height:1.6;">Unser System verfolgt alle Transaktionen und erstellt einen lückenlosen Beweisbericht.</div>
          </div>
        </div>

        <div class="col-lg col-md-6 col-12 mb-4 mb-lg-0 text-center px-3" style="position:relative;z-index:1;">
          <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#a78bfa,#ffd700);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 24px rgba(167,139,250,.4);">
            <i class="fas fa-balance-scale" style="color:#fff;font-size:1.5rem;"></i>
          </div>
          <div style="background:rgba(167,139,250,.07);border:1px solid rgba(167,139,250,.2);border-radius:12px;padding:16px 12px;">
            <div style="color:#a78bfa;font-weight:800;font-size:.78rem;letter-spacing:.08em;margin-bottom:6px;">SCHRITT 3</div>
            <div style="color:#fff;font-weight:700;margin-bottom:6px;font-size:.95rem;">Rechtliche Eskalation</div>
            <div style="color:#64748b;font-size:.82rem;line-height:1.6;">Beweispakete gehen an BaFin, Europol und wenn nötig internationale Strafverfolgungsbehörden.</div>
          </div>
        </div>

        <div class="col-lg col-md-6 col-12 mb-4 mb-lg-0 text-center px-3" style="position:relative;z-index:1;">
          <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#ffd700,#fb923c);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 24px rgba(255,215,0,.4);">
            <i class="fas fa-lock-open" style="color:#04091a;font-size:1.5rem;"></i>
          </div>
          <div style="background:rgba(255,215,0,.07);border:1px solid rgba(255,215,0,.2);border-radius:12px;padding:16px 12px;">
            <div style="color:#ffd700;font-weight:800;font-size:.78rem;letter-spacing:.08em;margin-bottom:6px;">SCHRITT 4</div>
            <div style="color:#fff;font-weight:700;margin-bottom:6px;font-size:.95rem;">Gelder einfrieren & sichern</div>
            <div style="color:#64748b;font-size:.82rem;line-height:1.6;">Identifizierte Wallets werden via Exchange-Kooperationen gesperrt und Gelder gesichert.</div>
          </div>
        </div>

        <div class="col-lg col-md-6 col-12 text-center px-3" style="position:relative;z-index:1;">
          <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#00ffb4,#fb923c);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 24px rgba(0,255,180,.4);">
            <i class="fas fa-euro-sign" style="color:#04091a;font-size:1.5rem;"></i>
          </div>
          <div style="background:rgba(0,255,180,.07);border:1px solid rgba(0,255,180,.2);border-radius:12px;padding:16px 12px;">
            <div style="color:#00ffb4;font-weight:800;font-size:.78rem;letter-spacing:.08em;margin-bottom:6px;">SCHRITT 5</div>
            <div style="color:#fff;font-weight:700;margin-bottom:6px;font-size:.95rem;">Auszahlung in Euro</div>
            <div style="color:#64748b;font-size:.82rem;line-height:1.6;">Nach Abschluss: SEPA-Überweisung auf Ihr Konto. Keine Vorleistungen, erfolgsbasierte Vergütung.</div>
          </div>
        </div>

      </div>
    </div><!-- /timeline -->

    <!-- Two-column: FAQ + Trustmarks -->
    <div class="row g-4">

      <!-- FAQ accordion -->
      <div class="col-lg-7">
        <h3 style="color:#fff;font-weight:800;font-size:1.4rem;margin-bottom:24px;">
          <i class="fas fa-question-circle me-2" style="color:#00c6ff;"></i>Häufige Fragen zur Rückgewinnung
        </h3>
        <div style="display:flex;flex-direction:column;gap:10px;" id="recovery-faq">

          <div style="border:1px solid rgba(255,255,255,.1);border-radius:12px;overflow:hidden;">
            <button onclick="toggleFaq(this)" style="width:100%;background:rgba(255,255,255,.04);border:none;padding:18px 22px;text-align:left;color:#fff;font-weight:600;font-size:.95rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center;">
              Kann ich wirklich gestohlene Kryptowährungen zurückbekommen?
              <i class="fas fa-chevron-down" style="color:#00c6ff;transition:transform .3s;"></i>
            </button>
            <div style="display:none;padding:0 22px 18px;color:#94a3b8;font-size:.9rem;line-height:1.7;background:rgba(0,0,0,.15);">
              Ja – in vielen Fällen ist es möglich, da Blockchain-Transaktionen dauerhaft und öffentlich nachvollziehbar sind.
              Der Schlüssel ist das schnelle Handeln: Je früher Sie uns kontaktieren, desto größer ist die Chance,
              die Gelder zu sichern, bevor sie über Mixer oder dezentralisierte Börsen verschleiert werden.
              Unsere Erfolgsquote liegt bei <strong style="color:#00ffb4;">87 %</strong> bei Fällen, die innerhalb von 30 Tagen gemeldet werden.
            </div>
          </div>

          <div style="border:1px solid rgba(255,255,255,.1);border-radius:12px;overflow:hidden;">
            <button onclick="toggleFaq(this)" style="width:100%;background:rgba(255,255,255,.04);border:none;padding:18px 22px;text-align:left;color:#fff;font-weight:600;font-size:.95rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center;">
              Was kostet die Rückgewinnung?
              <i class="fas fa-chevron-down" style="color:#00c6ff;transition:transform .3s;"></i>
            </button>
            <div style="display:none;padding:0 22px 18px;color:#94a3b8;font-size:.9rem;line-height:1.7;background:rgba(0,0,0,.15);">
              Die <strong style="color:#fff;">Erstberatung und KI-Analyse sind vollständig kostenlos und unverbindlich</strong>.
              Wir arbeiten erfolgsbasiert: Unsere Vergütung richtet sich nach dem tatsächlich zurückgewonnenen Betrag –
              ohne Vorabzahlungen. So tragen Sie kein Risiko. Die genaue Gebührenstruktur besprechen wir transparent
              in Ihrem persönlichen Beratungsgespräch.
            </div>
          </div>

          <div style="border:1px solid rgba(255,255,255,.1);border-radius:12px;overflow:hidden;">
            <button onclick="toggleFaq(this)" style="width:100%;background:rgba(255,255,255,.04);border:none;padding:18px 22px;text-align:left;color:#fff;font-weight:600;font-size:.95rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center;">
              Wie lange dauert ein Rückgewinnungsverfahren?
              <i class="fas fa-chevron-down" style="color:#00c6ff;transition:transform .3s;"></i>
            </button>
            <div style="display:none;padding:0 22px 18px;color:#94a3b8;font-size:.9rem;line-height:1.7;background:rgba(0,0,0,.15);">
              Die Dauer hängt von der Komplexität des Falls ab. Einfache Wallet-Diebstähle können innerhalb von
              <strong style="color:#fff;">2–4 Wochen</strong> abgeschlossen werden. Komplexe internationale Betrugsfälle mit
              mehreren Mixer-Ebenen können <strong style="color:#fff;">3–6 Monate</strong> in Anspruch nehmen.
              Sie werden transparent über jeden Fortschritt informiert.
            </div>
          </div>

          <div style="border:1px solid rgba(255,255,255,.1);border-radius:12px;overflow:hidden;">
            <button onclick="toggleFaq(this)" style="width:100%;background:rgba(255,255,255,.04);border:none;padding:18px 22px;text-align:left;color:#fff;font-weight:600;font-size:.95rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center;">
              Welche Informationen benötige ich für den Start?
              <i class="fas fa-chevron-down" style="color:#00c6ff;transition:transform .3s;"></i>
            </button>
            <div style="display:none;padding:0 22px 18px;color:#94a3b8;font-size:.9rem;line-height:1.7;background:rgba(0,0,0,.15);">
              Je mehr Informationen Sie haben, desto besser – aber selbst mit wenig Angaben können wir starten.
              Hilfreich sind: Wallet-Adressen, Transaktions-Hashes (TxIDs), Kommunikationsprotokolle mit dem Betrüger,
              Screenshots und der ungefähre Zeitraum der Transaktionen. Auch ohne diese Daten lässt sich oft
              eine Erstspur finden.
            </div>
          </div>

          <div style="border:1px solid rgba(255,255,255,.1);border-radius:12px;overflow:hidden;">
            <button onclick="toggleFaq(this)" style="width:100%;background:rgba(255,255,255,.04);border:none;padding:18px 22px;text-align:left;color:#fff;font-weight:600;font-size:.95rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center;">
              Sind meine Daten bei Ihnen sicher?
              <i class="fas fa-chevron-down" style="color:#00c6ff;transition:transform .3s;"></i>
            </button>
            <div style="display:none;padding:0 22px 18px;color:#94a3b8;font-size:.9rem;line-height:1.7;background:rgba(0,0,0,.15);">
              Absolut. Wir unterliegen als BaFin-lizenziertes Unternehmen strengen Datenschutzvorschriften
              (DSGVO/GDPR). Alle Daten werden ausschließlich auf deutschen Servern mit
              <strong style="color:#fff;">256-Bit AES-Verschlüsselung</strong> gespeichert und niemals an Dritte weitergegeben –
              ausgenommen behördlich angeordnete Offenlegungen im Rahmen des Strafverfahrens.
            </div>
          </div>

        </div>
      </div>

      <!-- Trustmarks + stats -->
      <div class="col-lg-5">
        <h3 style="color:#fff;font-weight:800;font-size:1.4rem;margin-bottom:24px;">
          <i class="fas fa-award me-2" style="color:#ffd700;"></i>Warum uns vertrauen?
        </h3>

        <div style="display:flex;flex-direction:column;gap:14px;">

          <div style="background:rgba(255,255,255,.04);border:1px solid rgba(0,255,180,.15);border-radius:14px;padding:20px 22px;display:flex;align-items:flex-start;gap:16px;">
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(0,255,180,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-certificate" style="color:#00ffb4;font-size:1.2rem;"></i>
            </div>
            <div>
              <div style="color:#fff;font-weight:700;margin-bottom:4px;">BaFin-lizenziert & reguliert</div>
              <div style="color:#64748b;font-size:.85rem;line-height:1.6;">
                Wir sind offiziell lizenziert und unterliegen den strengsten deutschen Finanzaufsichtsstandards –
                keine graue Zone, nur geprüfte Seriosität.
              </div>
            </div>
          </div>

          <div style="background:rgba(255,255,255,.04);border:1px solid rgba(0,198,255,.15);border-radius:14px;padding:20px 22px;display:flex;align-items:flex-start;gap:16px;">
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(0,198,255,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-handshake" style="color:#00c6ff;font-size:1.2rem;"></i>
            </div>
            <div>
              <div style="color:#fff;font-weight:700;margin-bottom:4px;">Kein Erfolg – keine Kosten</div>
              <div style="color:#64748b;font-size:.85rem;line-height:1.6;">
                Unser Honorar ist vollständig erfolgsbasiert. Sie zahlen ausschließlich dann,
                wenn wir Ihre Gelder tatsächlich zurückholen.
              </div>
            </div>
          </div>

          <div style="background:rgba(255,255,255,.04);border:1px solid rgba(167,139,250,.15);border-radius:14px;padding:20px 22px;display:flex;align-items:flex-start;gap:16px;">
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(167,139,250,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-globe-europe" style="color:#a78bfa;font-size:1.2rem;"></i>
            </div>
            <div>
              <div style="color:#fff;font-weight:700;margin-bottom:4px;">Internationale Netzwerke</div>
              <div style="color:#64748b;font-size:.85rem;line-height:1.6;">
                Kooperationen mit Europol, Interpol, FCA, BaFin und über 200 regulierten
                Kryptowährungsbörsen weltweit ermöglichen grenzüberschreitende Rückgewinnungen.
              </div>
            </div>
          </div>

          <!-- Mini stat grid -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px;">
            <div style="background:rgba(0,255,180,.07);border:1px solid rgba(0,255,180,.15);border-radius:12px;padding:16px;text-align:center;">
              <div style="color:#00ffb4;font-size:1.5rem;font-weight:800;">€ 47M+</div>
              <div style="color:#64748b;font-size:.78rem;margin-top:4px;">Zurückgewonnen</div>
            </div>
            <div style="background:rgba(0,198,255,.07);border:1px solid rgba(0,198,255,.15);border-radius:12px;padding:16px;text-align:center;">
              <div style="color:#00c6ff;font-size:1.5rem;font-weight:800;">2.134</div>
              <div style="color:#64748b;font-size:.78rem;margin-top:4px;">Fälle abgeschlossen</div>
            </div>
            <div style="background:rgba(167,139,250,.07);border:1px solid rgba(167,139,250,.15);border-radius:12px;padding:16px;text-align:center;">
              <div style="color:#a78bfa;font-size:1.5rem;font-weight:800;">87 %</div>
              <div style="color:#64748b;font-size:.78rem;margin-top:4px;">Erfolgsquote</div>
            </div>
            <div style="background:rgba(255,215,0,.07);border:1px solid rgba(255,215,0,.15);border-radius:12px;padding:16px;text-align:center;">
              <div style="color:#ffd700;font-size:1.5rem;font-weight:800;">24 h</div>
              <div style="color:#64748b;font-size:.78rem;margin-top:4px;">Erste Rückmeldung</div>
            </div>
          </div>

        </div>
      </div>

    </div><!-- /row faq + trust -->

    <!-- Final CTA -->
    <div class="text-center mt-5 pt-2">
      <div style="background:linear-gradient(135deg,rgba(0,198,255,.1),rgba(0,255,180,.08));border:1px solid rgba(0,255,180,.2);border-radius:20px;padding:48px 32px;max-width:700px;margin:0 auto;">
        <h3 style="color:#fff;font-weight:800;font-size:1.6rem;margin-bottom:12px;">
          Bereit, Ihre Gelder zurückzuholen?
        </h3>
        <p style="color:#94a3b8;margin-bottom:28px;line-height:1.7;">
          Jede Stunde zählt – betrügerische Transaktionen werden schnell verschleiert.
          Kontaktieren Sie uns jetzt für eine <strong style="color:#fff;">kostenlose, unverbindliche Ersteinschätzung</strong>.
        </p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
          <a href="#" data-bs-toggle="modal" data-bs-target="#contactLeadModal"
             style="background:linear-gradient(135deg,#00c6ff,#00ffb4);border:none;color:#04091a;font-weight:700;padding:16px 36px;border-radius:12px;font-size:1.05rem;text-decoration:none;display:inline-flex;align-items:center;gap:8px;box-shadow:0 0 30px rgba(0,198,255,.35);">
            <i class="fas fa-rocket"></i>Kostenlose Beratung starten
          </a>
          <a href="tel:+4930123456789"
             style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.2);color:#fff;font-weight:600;padding:16px 30px;border-radius:12px;font-size:1rem;text-decoration:none;display:inline-flex;align-items:center;gap:8px;">
            <i class="fas fa-phone"></i>Direkt anrufen
          </a>
        </div>
        <p style="color:#475569;font-size:.8rem;margin-top:16px;margin-bottom:0;">
          <i class="fas fa-lock me-1"></i>DSGVO-konform &nbsp;·&nbsp;
          <i class="fas fa-shield-alt me-1"></i>BaFin-lizenziert &nbsp;·&nbsp;
          <i class="fas fa-star me-1" style="color:#ffd700;"></i>4,9 / 5 Kundenbewertung
        </p>
      </div>
    </div>

  </div><!-- /container -->
</section>

<script>
function toggleFaq(btn) {
  var content = btn.nextElementSibling;
  var icon    = btn.querySelector('.fa-chevron-down');
  var isOpen  = content.style.display === 'block';
  /* Close all panels and reset icons in a single DOM pass */
  var faq = document.getElementById('recovery-faq');
  faq.querySelectorAll('button').forEach(function (b) {
    b.nextElementSibling.style.display = 'none';
    b.querySelector('.fa-chevron-down').style.transform = '';
  });
  if (!isOpen) {
    content.style.display = 'block';
    icon.style.transform  = 'rotate(180deg)';
  }
}
</script>

<!-- Security Alerts & Fraud Warnings -->
<section id="security-alerts" class="section bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Aktuelle Sicherheitswarnungen & Betrugswarnungen</h2>
            <p class="lead text-muted">Bleiben Sie informiert über aktuelle Betrugsmaschen und schützen Sie sich vor Krypto-Betrügern</p>
        </div>
        
        <div class="row g-4">
            <!-- Alert 1: Platform Scam Shutdown -->
            <div class="col-lg-4">
                <div class="alert-card alert-danger-custom">
                    <div class="alert-header">
                        <div class="alert-icon bg-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="alert-meta">
                            <span class="badge bg-danger">KRITISCH</span>
                            <small class="text-muted">Vor 2 Tagen</small>
                        </div>
                        <button class="alert-dismiss" onclick="dismissAlert(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <h5 class="alert-title">
                        <i class="fas fa-building me-2"></i>
                        "CryptoXchange Pro" als Betrug identifiziert
                    </h5>
                    <p class="alert-description">
                        Die Plattform wurde als Betrug entlarvt und geschlossen. Über 380 Investoren verloren insgesamt 4,2 Millionen Euro. 
                        <strong>Seien Sie vorsichtig bei ähnlichen Plattformen</strong> mit unrealistischen Renditeversprechen.
                    </p>
                    <div class="alert-footer">
                        <i class="fas fa-shield-alt me-2"></i>
                        <small>Unsere KI hat diesen Betrug frühzeitig erkannt</small>
                    </div>
                </div>
            </div>

            <!-- Alert 2: Phishing Campaign -->
            <div class="col-lg-4">
                <div class="alert-card alert-warning-custom">
                    <div class="alert-header">
                        <div class="alert-icon bg-warning">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="alert-meta">
                            <span class="badge bg-warning text-dark">WARNUNG</span>
                            <small class="text-muted">Vor 1 Woche</small>
                        </div>
                        <button class="alert-dismiss" onclick="dismissAlert(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <h5 class="alert-title">
                        <i class="fas fa-envelope-open-text me-2"></i>
                        Neue Phishing-E-Mail-Kampagne aktiv
                    </h5>
                    <p class="alert-description">
                        Gefälschte E-Mails geben sich als bekannte Banken und Krypto-Börsen aus. 
                        <strong>Klicken Sie nicht auf Links</strong> und geben Sie keine persönlichen Daten ein. 
                        Überprüfen Sie immer die Absenderadresse sorgfältig.
                    </p>
                    <div class="alert-footer">
                        <i class="fas fa-lightbulb me-2"></i>
                        <small>Tipp: Echte Banken fragen nie per E-Mail nach Passwörtern</small>
                    </div>
                </div>
            </div>

            <!-- Alert 3: Fake Social Media -->
            <div class="col-lg-4">
                <div class="alert-card alert-warning-custom">
                    <div class="alert-header">
                        <div class="alert-icon bg-warning">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="alert-meta">
                            <span class="badge bg-warning text-dark">WARNUNG</span>
                            <small class="text-muted">Vor 3 Tagen</small>
                        </div>
                        <button class="alert-dismiss" onclick="dismissAlert(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <h5 class="alert-title">
                        <i class="fas fa-users-slash me-2"></i>
                        Gefälschte Social-Media-Konten entdeckt
                    </h5>
                    <p class="alert-description">
                        Betrüger erstellen gefälschte Konten, die sich als legitime Krypto-Börsen ausgeben. 
                        <strong>Überprüfen Sie immer offizielle Verifizierungs-Badges</strong> und melden Sie verdächtige Konten.
                    </p>
                    <div class="alert-footer">
                        <i class="fas fa-check-circle me-2"></i>
                        <small>Offizielle Accounts haben blaue Verifizierungs-Häkchen</small>
                    </div>
                </div>
            </div>

            <!-- Alert 4: WhatsApp/Telegram Scams -->
            <div class="col-lg-4">
                <div class="alert-card alert-info-custom">
                    <div class="alert-header">
                        <div class="alert-icon bg-info">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="alert-meta">
                            <span class="badge bg-info">TIPP</span>
                            <small class="text-muted">Vor 5 Tagen</small>
                        </div>
                        <button class="alert-dismiss" onclick="dismissAlert(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <h5 class="alert-title">
                        <i class="fab fa-whatsapp me-2"></i>
                        WhatsApp/Telegram Investment-Gruppen
                    </h5>
                    <p class="alert-description">
                        99% der Investment-Gruppen auf WhatsApp und Telegram sind Betrug! 
                        <strong>Investieren Sie niemals</strong> basierend auf Nachrichten in solchen Gruppen. 
                        Typische Maschen: Pump & Dump, Schneeballsysteme.
                    </p>
                    <div class="alert-footer">
                        <i class="fas fa-ban me-2"></i>
                        <small>Seriöse Anbieter werben nicht in Chat-Gruppen</small>
                    </div>
                </div>
            </div>

            <!-- Alert 5: NFT Phishing -->
            <div class="col-lg-4">
                <div class="alert-card alert-info-custom">
                    <div class="alert-header">
                        <div class="alert-icon bg-info">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="alert-meta">
                            <span class="badge bg-info">PRÄVENTION</span>
                            <small class="text-muted">Vor 1 Woche</small>
                        </div>
                        <button class="alert-dismiss" onclick="dismissAlert(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <h5 class="alert-title">
                        <i class="fas fa-image me-2"></i>
                        NFT-Phishing und gefälschte Airdrops
                    </h5>
                    <p class="alert-description">
                        Gefälschte NFT-Websites und Airdrop-Seiten stehlen Wallet-Zugangsdaten. 
                        <strong>Verbinden Sie Ihr Wallet niemals</strong> mit unbekannten Websites. 
                        Überprüfen Sie URLs sehr genau vor der Verbindung.
                    </p>
                    <div class="alert-footer">
                        <i class="fas fa-lock me-2"></i>
                        <small>Prüfen Sie immer die Domain-URL auf Rechtschreibfehler</small>
                    </div>
                </div>
            </div>

            <!-- Alert 6: General Security Tips -->
            <div class="col-lg-4">
                <div class="alert-card alert-success-custom">
                    <div class="alert-header">
                        <div class="alert-icon bg-success">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="alert-meta">
                            <span class="badge bg-success">SCHUTZ-TIPPS</span>
                            <small class="text-muted">Aktualisiert</small>
                        </div>
                    </div>
                    <h5 class="alert-title">
                        <i class="fas fa-user-shield me-2"></i>
                        So schützen Sie sich effektiv
                    </h5>
                    <p class="alert-description">
                        <strong>✓</strong> Aktivieren Sie 2-Faktor-Authentifizierung<br>
                        <strong>✓</strong> Nutzen Sie Hardware-Wallets<br>
                        <strong>✓</strong> Prüfen Sie URLs vor dem Zugriff<br>
                        <strong>✓</strong> Seien Sie skeptisch bei hohen Renditen<br>
                        <strong>✓</strong> Recherchieren Sie vor Investitionen
                    </p>
                    <div class="alert-footer">
                        <i class="fas fa-lightbulb me-2"></i>
                        <small>Prävention ist der beste Schutz</small>
                    </div>
                </div>
            </div>

            <!-- Alert 7: Romance Scam -->
            <div class="col-lg-4">
                <div class="alert-card alert-danger-custom">
                    <div class="alert-header">
                        <div class="alert-icon bg-danger">
                            <i class="fas fa-heart-broken"></i>
                        </div>
                        <div class="alert-meta">
                            <span class="badge bg-danger">KRITISCH</span>
                            <small class="text-muted">Vor 4 Tagen</small>
                        </div>
                        <button class="alert-dismiss" onclick="dismissAlert(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <h5 class="alert-title">
                        <i class="fas fa-heart me-2"></i>
                        Romance Scam: Neue Welle auf Dating-Apps
                    </h5>
                    <p class="alert-description">
                        Betrüger bauen über Monate Vertrauen auf Dating-Plattformen auf, bevor sie um Krypto-Investitionen bitten.
                        <strong>Überweisen Sie niemals Kryptowährungen</strong> an Personen, die Sie nur online kennen – unabhängig wie verlässlich sie wirken.
                    </p>
                    <div class="alert-footer">
                        <i class="fas fa-user-secret me-2"></i>
                        <small>Pig-Butchering ist die häufigste Romance-Scam-Variante</small>
                    </div>
                </div>
            </div>

            <!-- Alert 8: Fake Recovery Services -->
            <div class="col-lg-4">
                <div class="alert-card alert-warning-custom">
                    <div class="alert-header">
                        <div class="alert-icon bg-warning">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="alert-meta">
                            <span class="badge bg-warning text-dark">WARNUNG</span>
                            <small class="text-muted">Vor 6 Tagen</small>
                        </div>
                        <button class="alert-dismiss" onclick="dismissAlert(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <h5 class="alert-title">
                        <i class="fas fa-mask me-2"></i>
                        Gefälschte Wiederherstellungsdienste im Umlauf
                    </h5>
                    <p class="alert-description">
                        Kriminelle geben sich als Krypto-Recovery-Dienste aus und verlangen Vorauszahlungen – nur um danach zu verschwinden.
                        <strong>Seriöse Anbieter verlangen niemals Gebühren im Voraus.</strong>
                        Prüfen Sie immer Lizenz und Registrierung (BaFin-Register).
                    </p>
                    <div class="alert-footer">
                        <i class="fas fa-search me-2"></i>
                        <small>BaFin-Register unter bafin.de überprüfen</small>
                    </div>
                </div>
            </div>

            <!-- Alert 9: AI-powered Deepfake Scams -->
            <div class="col-lg-4">
                <div class="alert-card alert-info-custom">
                    <div class="alert-header">
                        <div class="alert-icon bg-info">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="alert-meta">
                            <span class="badge bg-info">NEU 2025</span>
                            <small class="text-muted">Vor 2 Tagen</small>
                        </div>
                        <button class="alert-dismiss" onclick="dismissAlert(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <h5 class="alert-title">
                        <i class="fas fa-video me-2"></i>
                        KI-Deepfake-Videos von Prominenten
                    </h5>
                    <p class="alert-description">
                        Betrüger verwenden KI-generierte Deepfake-Videos bekannter Persönlichkeiten (Elon Musk, Warren Buffett), um für gefälschte Krypto-Plattformen zu werben.
                        <strong>Ignorieren Sie</strong> Investitionsempfehlungen aus solchen Videos vollständig.
                    </p>
                    <div class="alert-footer">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <small>Prominente empfehlen niemals Krypto via Video-Werbung</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Security Alerts Styling */
.alert-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
    border-left: 4px solid;
}

.alert-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.alert-danger-custom {
    border-left-color: #dc3545;
    background: linear-gradient(135deg, #fff 0%, #fff5f5 100%);
}

.alert-warning-custom {
    border-left-color: #ffc107;
    background: linear-gradient(135deg, #fff 0%, #fffef5 100%);
}

.alert-info-custom {
    border-left-color: #0dcaf0;
    background: linear-gradient(135deg, #fff 0%, #f0fcff 100%);
}

.alert-success-custom {
    border-left-color: #198754;
    background: linear-gradient(135deg, #fff 0%, #f0fff5 100%);
}

.alert-header {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 0.75rem;
}

.alert-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.alert-meta {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.alert-dismiss {
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
}

.alert-dismiss:hover {
    color: #dc3545;
}

.alert-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #212529;
}

.alert-description {
    color: #495057;
    line-height: 1.6;
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.alert-footer {
    padding-top: 0.75rem;
    border-top: 1px solid rgba(0,0,0,0.05);
    color: #6c757d;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
}

/* Animation for alert dismissal */
@keyframes fadeOutUp {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-20px);
    }
}

.dismissing {
    animation: fadeOutUp 0.3s ease forwards;
}
</style>

<script>
// Dismiss alert functionality
function dismissAlert(button) {
    const alertCard = button.closest('.col-lg-4');
    alertCard.classList.add('dismissing');
    setTimeout(() => {
        alertCard.style.display = 'none';
    }, 300);
}
</script>

<!-- Security Section -->
<section id="process" class="section bg-light anchor-offset">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h2 class="section-title">Unser Wiederherstellungsprozess</h2>
                <p class="section-subtitle">Transparenz und Sicherheit in jedem Schritt – von der Analyse bis zur Auszahlung</p>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="mb-3"><i class="fas fa-search fa-2x text-primary"></i></div>
                            <h5 class="fw-bold mb-3">1. Blockchain-Analyse</h5>
                            <p class="text-muted">Unsere KI durchsucht über 15 Blockchains nach betrügerischen Transaktionsmustern und identifiziert gestohlene Vermögenswerte.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="mb-3"><i class="fas fa-user-check fa-2x text-primary"></i></div>
                            <h5 class="fw-bold mb-3">2. Identitätsverifizierung</h5>
                            <p class="text-muted">Zweistufiger KYC-Prozess mit behördlich anerkanntem Ausweisdokument und Video-Identifikation für maximale Sicherheit.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="mb-3"><i class="fas fa-key fa-2x text-primary"></i></div>
                            <h5 class="fw-bold mb-3">3. Wallet-Besitznachweis</h5>
                            <p class="text-muted">Kryptografische Signatur zur Verifizierung des Wallet-Besitzes. Ohne gültigen Nachweis erfolgt keine Auszahlung.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="mb-3"><i class="fas fa-euro-sign fa-2x text-primary"></i></div>
                            <h5 class="fw-bold mb-3">4. Sichere Auszahlung</h5>
                            <p class="text-muted">Nach erfolgreicher Verifizierung: Umwandlung in Euro über lizenzierte Börsen und SEPA-Überweisung auf Ihr Bankkonto.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="mb-3"><i class="fas fa-gavel fa-2x text-primary"></i></div>
                            <h5 class="fw-bold mb-3">5. Behördenkooperation</h5>
                            <p class="text-muted">Bei Bedarf koordinieren wir die Zusammenarbeit mit BKA, Europol und internationalen Strafverfolgungsbehörden für eine strafrechtliche Verfolgung der Täter.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="mb-3"><i class="fas fa-tachometer-alt fa-2x text-primary"></i></div>
                            <h5 class="fw-bold mb-3">6. Echtzeit-Dashboard</h5>
                            <p class="text-muted">Verfolgen Sie den Fortschritt Ihres Falls in Echtzeit über unser verschlüsseltes Mandanten-Portal – transparent, jederzeit und von überall abrufbar.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side callout -->
            <div class="col-lg-6">
                <div class="bg-primary text-white p-5 rounded-3">
                    <h3 class="fw-bold mb-4"><i class="fas fa-shield-check me-2"></i>Höchste Sicherheitsstandards</h3>

                    <div class="d-flex mb-4">
                        <div class="step-icon me-3"><i class="fas fa-certificate"></i></div>
                        <div>
                            <h5 class="fw-bold text-white">BaFin-Lizenzierung</h5>
                            <p class="text-white-75">
                                Als offiziell lizenziertes Finanzdienstleistungsinstitut (BaFin-Reg.: <?php echo htmlspecialchars($siteSettings['fca_reference_number']); ?>) 
                                unterliegen wir strengen regulatorischen Kontrollen und regelmäßigen Audits.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex mb-4">
                        <div class="step-icon me-3"><i class="fas fa-lock"></i></div>
                        <div>
                            <h5 class="fw-bold text-white">Datenschutz & GDPR</h5>
                            <p class="text-white-75">
                                Alle personenbezogenen Daten werden nach <strong>GDPR-Standards</strong> verarbeitet. 
                                Verschlüsselung auf Bankniveau (256-Bit SSL) und Server-Standort in Deutschland.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex mb-4">
                        <div class="step-icon me-3"><i class="fas fa-file-contract"></i></div>
                        <div>
                            <h5 class="fw-bold text-white">Rechtssichere Dokumentation</h5>
                            <p class="text-white-75">
                                Jeder Prozessschritt wird lückenlos dokumentiert. Bei Bedarf arbeiten wir mit 
                                Rechtsanwälten und Sachverständigen zusammen, um rechtliche Absicherung zu gewährleisten.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-white bg-opacity-20 rounded text-center">
                        <strong class="text-white">Transparenz ist unser Versprechen</strong><br/>
                        <small class="text-white-75">
                            Echtzeit-Dashboard zeigt Ihnen jederzeit den aktuellen Status Ihres Falles. 
                            Keine versteckten Kosten – Gebühr nur bei erfolgreicher Wiederherstellung.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section id="features" class="section anchor-offset">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Plattform-Features</h2>
            <p class="section-subtitle">Fokussiert, verlässlich und konform – entwickelt für maximalen Schutz Ihrer Vermögenswerte</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card text-center h-100">
                    <div class="mb-3"><i class="fas fa-robot fa-3x" style="color:var(--primary);"></i></div>
                    <h5 class="fw-bold mb-2">KI-Erstanalyse in Sekunden</h5>
                    <p class="text-muted mb-0">Sobald Sie Ihren Fall einreichen, startet unsere KI automatisch mit der Blockchain-Analyse – keine Wartezeit, keine manuelle Vorprüfung nötig.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card text-center h-100">
                    <div class="mb-3"><i class="fas fa-lock fa-3x" style="color:var(--primary);"></i></div>
                    <h5 class="fw-bold mb-2">Ende-zu-Ende-Verschlüsselung</h5>
                    <p class="text-muted mb-0">Alle Falldaten werden AES-256-verschlüsselt übertragen und gespeichert. Ihre sensiblen Informationen verlassen niemals unsere gesicherte Infrastruktur.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card text-center h-100">
                    <div class="mb-3"><i class="fas fa-balance-scale fa-3x" style="color:var(--primary);"></i></div>
                    <h5 class="fw-bold mb-2">Rechtskonforme Dokumentation</h5>
                    <p class="text-muted mb-0">Alle Analyseergebnisse werden gerichtsverwertbar aufbereitet – ideal als Beweissicherung für Strafanzeigen oder zivilrechtliche Verfahren.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card text-center h-100">
                    <div class="mb-3"><i class="fas fa-headset fa-3x" style="color:var(--primary);"></i></div>
                    <h5 class="fw-bold mb-2">Persönlicher Fallmanager</h5>
                    <p class="text-muted mb-0">Jeder Kunde erhält einen dedizierten Fallmanager, der Sie auf Deutsch durch den gesamten Prozess begleitet – per E-Mail, Telefon oder sicherem Chat.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card text-center h-100">
                    <div class="mb-3"><i class="fas fa-euro-sign fa-3x" style="color:var(--primary);"></i></div>
                    <h5 class="fw-bold mb-2">Keine Vorabgebühren</h5>
                    <p class="text-muted mb-0">Sie zahlen ausschließlich bei Erfolg. Unsere Vergütung basiert auf einer Erfolgsprovision – ohne versteckte Kosten oder Vorleistungen Ihrerseits.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card text-center h-100">
                    <div class="mb-3"><i class="fas fa-globe fa-3x" style="color:var(--primary);"></i></div>
                    <h5 class="fw-bold mb-2">Internationale Reichweite</h5>
                    <p class="text-muted mb-0">Wir arbeiten mit Strafverfolgungsbehörden, Regulatoren und forensischen Partnern in über 30 Ländern zusammen, um Täter aufzuspüren.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card text-center h-100">
                    <div class="mb-3"><i class="fas fa-chart-area fa-3x" style="color:var(--primary);"></i></div>
                    <h5 class="fw-bold mb-2">Verhaltensanalyse & Mustererkennung</h5>
                    <p class="text-muted mb-0">Unsere KI erstellt Verhaltensprofile verdächtiger Wallets und erkennt koordinierte Betrugsringe – auch wenn Gelder über Dutzende Zwischenadressen geleitet wurden.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card text-center h-100">
                    <div class="mb-3"><i class="fas fa-phone-alt fa-3x" style="color:var(--primary);"></i></div>
                    <h5 class="fw-bold mb-2">24/7 Notfall-Hotline</h5>
                    <p class="text-muted mb-0">Wurde Ihre Wallet soeben kompromittiert? Unsere Notfall-Hotline ist rund um die Uhr erreichbar – schnelles Handeln in den ersten Stunden erhöht die Erfolgsquote deutlich.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services -->
<section id="services" class="section bg-light anchor-offset">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Unsere Services im Detail</h2>
            <p class="section-subtitle">Alles, was Sie für professionelle Krypto-Wiederherstellung benötigen</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="feature-card">
                    <h4 class="fw-bold mb-4"><i class="fas fa-exchange-alt text-primary me-2"></i> Ein- und Auszahlungen</h4>
                    <p class="text-muted mb-4">Sichere SEPA-Überweisungen mit deutscher IBAN. Alle Transaktionen werden manuell geprüft und innerhalb von 1–2 Werktagen bearbeitet.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Deutsche Bankverbindung</li>
                        <li><i class="fas fa-check-circle"></i> Manuelle Prüfung aller Transaktionen</li>
                        <li><i class="fas fa-check-circle"></i> Schnelle Bearbeitung (1–2 Werktage)</li>
                        <li><i class="fas fa-check-circle"></i> Persönlicher Verwendungszweck</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="feature-card">
                    <h4 class="fw-bold mb-4"><i class="fas fa-key text-primary me-2"></i> Wiederherstellungsservice</h4>
                    <p class="text-muted mb-4">Spezialisierter Service zur Wiederherstellung vergessener Mnemonic-Phrasen. 369 erfolgreich gelöste Fälle mit dokumentierter Erfolgsquote.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> 12/24-Wörter Wiederherstellung</li>
                        <li><i class="fas fa-check-circle"></i> Reihenfolge-Rekonstruktion</li>
                        <li><i class="fas fa-check-circle"></i> Wallet-Adresse Recovery</li>
                        <li><i class="fas fa-check-circle"></i> DSGVO-konform & sicher</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="feature-card">
                    <h4 class="fw-bold mb-4"><i class="fas fa-balance-scale text-primary me-2"></i> Rechtliche Unterstützung</h4>
                    <p class="text-muted mb-4">Unsere forensischen Berichte sind gerichtsverwertbar aufbereitet und unterstützen Strafanzeigen sowie zivilrechtliche Verfahren gegen Täter.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Gerichtsverwertbare Berichte</li>
                        <li><i class="fas fa-check-circle"></i> Strafanzeige-Vorbereitung</li>
                        <li><i class="fas fa-check-circle"></i> Kooperation mit Staatsanwaltschaft</li>
                        <li><i class="fas fa-check-circle"></i> Internationale Rechtshilfe</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="feature-card">
                    <h4 class="fw-bold mb-4"><i class="fas fa-search-dollar text-primary me-2"></i> Blockchain-Forensik</h4>
                    <p class="text-muted mb-4">Professionelle Analyse von Transaktionsketten über 15+ Blockchains hinweg – inklusive De-Anonymisierung von Mixer-Diensten und Cross-Chain-Bridges.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Multi-Chain Transaction Tracing</li>
                        <li><i class="fas fa-check-circle"></i> Mixer & Tumbler Analyse</li>
                        <li><i class="fas fa-check-circle"></i> Smart-Contract-Analyse (DeFi)</li>
                        <li><i class="fas fa-check-circle"></i> Exchange-Identifizierung</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="feature-card">
                    <h4 class="fw-bold mb-4"><i class="fas fa-shield-virus text-primary me-2"></i> Prävention & Schutzberatung</h4>
                    <p class="text-muted mb-4">Schützen Sie sich proaktiv: Unsere Experten prüfen Ihre aktuelle Sicherheitskonfiguration und helfen Ihnen, künftigen Betrug zu erkennen und abzuwehren.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Sicherheits-Audit Ihrer Wallets</li>
                        <li><i class="fas fa-check-circle"></i> Hardware-Wallet-Einrichtung</li>
                        <li><i class="fas fa-check-circle"></i> Schulung zur Betrugserkennung</li>
                        <li><i class="fas fa-check-circle"></i> Persönlicher Sicherheitsplan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- KI-Rückerstattungs-Sektion - Enhanced with Professional Content and Animations -->
<section id="refund-ai" class="section ai-section-enhanced anchor-offset">
  <div class="ai-bg-animated"></div>
  
  <div class="container position-relative" style="z-index: 2;">
    <div class="text-center mb-5 fade-in-up">
      <div class="badge bg-primary bg-gradient mb-3 px-4 py-2">
        <i class="fas fa-microchip me-2"></i>Künstliche Intelligenz der nächsten Generation
      </div>
      <h2 class="section-title display-4 fw-bold">KI-gestützte Vermögenswiederherstellung</h2>
      <p class="section-subtitle lead">
        Professionelle Blockchain-Forensik mit fortschrittlicher Künstlicher Intelligenz – 
        BaFin-lizenziert (BaFin-Reg.: <?php echo htmlspecialchars($siteSettings['fca_reference_number']); ?>) und nach höchsten Sicherheitsstandards
      </p>
    </div>

    <!-- AI Technology Showcase -->
    <div class="row mb-5">
      <div class="col-lg-4 mb-4">
        <div class="ai-feature-card animate-on-scroll">
          <div class="ai-feature-icon">
            <i class="fas fa-brain"></i>
          </div>
          <h4 class="fw-bold mb-3">Deep Learning Analyse</h4>
          <p class="text-muted mb-3">
            Neuronale Netzwerke mit über <strong>100.000 Betrugsfällen</strong> trainiert. 
            Unsere KI erkennt komplexe Transaktionsmuster mit <strong>94% Genauigkeit</strong>.
          </p>
          <ul class="feature-list-enhanced">
            <li><i class="fas fa-check-circle text-success"></i> Multi-Layer Perceptron Architektur</li>
            <li><i class="fas fa-check-circle text-success"></i> Convolutional Neural Networks</li>
            <li><i class="fas fa-check-circle text-success"></i> Recurrent Pattern Recognition</li>
            <li><i class="fas fa-check-circle text-success"></i> Ensemble Learning Methods</li>
          </ul>
        </div>
      </div>

      <div class="col-lg-4 mb-4">
        <div class="ai-feature-card animate-on-scroll" style="animation-delay: 0.2s;">
          <div class="ai-feature-icon">
            <i class="fas fa-network-wired"></i>
          </div>
          <h4 class="fw-bold mb-3">Multi-Chain-Tracking</h4>
          <p class="text-muted mb-3">
            Simultane Analyse von <strong>15+ Blockchains</strong> in Echtzeit. 
            Verfolgung über Mixing-Services und Cross-Chain-Bridges hinweg.
          </p>
          <ul class="feature-list-enhanced">
            <li><i class="fas fa-check-circle text-success"></i> Bitcoin & Lightning Network</li>
            <li><i class="fas fa-check-circle text-success"></i> Ethereum & ERC-20 Tokens</li>
            <li><i class="fas fa-check-circle text-success"></i> BSC, Polygon, Avalanche</li>
            <li><i class="fas fa-check-circle text-success"></i> Monero, Zcash Analyse</li>
          </ul>
        </div>
      </div>

      <div class="col-lg-4 mb-4">
        <div class="ai-feature-card animate-on-scroll" style="animation-delay: 0.4s;">
          <div class="ai-feature-icon">
            <i class="fas fa-shield-alt"></i>
          </div>
          <h4 class="fw-bold mb-3">Rechtskonform & Sicher</h4>
          <p class="text-muted mb-3">
            <strong>BaFin-lizenziert</strong> mit vollständiger AML/KYC-Compliance. 
            GDPR-konforme Datenverarbeitung auf deutschen Servern.
          </p>
          <ul class="feature-list-enhanced">
            <li><i class="fas fa-check-circle text-success"></i> Zweistufige KYC-Verifizierung</li>
            <li><i class="fas fa-check-circle text-success"></i> Kryptografischer Besitznachweis</li>
            <li><i class="fas fa-check-circle text-success"></i> Rechtsgutachten verfügbar</li>
            <li><i class="fas fa-check-circle text-success"></i> 256-Bit Verschlüsselung</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Success Metrics with Animated Counters -->
    <div class="row mb-5">
      <div class="col-12">
        <div class="success-metrics-card">
          <h3 class="text-center mb-4"><i class="fas fa-chart-line me-2"></i>Nachweisbare Erfolgsbilanz</h3>
          <div class="row text-center">
            <div class="col-md-3 col-6 mb-3">
              <div class="metric-item">
                <div class="metric-icon">
                  <i class="fas fa-users"></i>
                </div>
                <h2 class="display-5 fw-bold text-primary mb-0">
                  <span class="counter" data-target="727">0</span>
                </h2>
                <p class="text-muted mb-0">Zufriedene Klienten</p>
              </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
              <div class="metric-item">
                <div class="metric-icon">
                  <i class="fas fa-percentage"></i>
                </div>
                <h2 class="display-5 fw-bold text-success mb-0">
                  <span class="counter" data-target="87">0</span>%
                </h2>
                <p class="text-muted mb-0">Erfolgsquote</p>
              </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
              <div class="metric-item">
                <div class="metric-icon">
                  <i class="fas fa-euro-sign"></i>
                </div>
                <h2 class="display-5 fw-bold text-primary mb-0">
                  €<span class="counter" data-target="47">0</span>M
                </h2>
                <p class="text-muted mb-0">Wiederhergestellt</p>
              </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
              <div class="metric-item">
                <div class="metric-icon">
                  <i class="fas fa-clock"></i>
                </div>
                <h2 class="display-5 fw-bold text-info mb-0">
                  <span class="counter" data-target="14">0</span>
                </h2>
                <p class="text-muted mb-0">Tage Durchschnitt</p>
              </div>
            </div>
          </div>

          <!-- Progress Visualization -->
          <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-bold"><i class="fas fa-chart-line me-2"></i>KI-Analysen erfolgreich</span>
              <span class="badge bg-success">94% Genauigkeit</span>
            </div>
            <div class="progress progress-enhanced" style="height: 20px;">
              <div class="progress-bar bg-gradient progress-bar-striped progress-bar-animated" 
                   id="aiProgressBar" role="progressbar" style="width: 0%">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Process Flow Visualization -->
    <div class="row mb-5">
      <div class="col-12">
        <h3 class="text-center mb-4"><i class="fas fa-project-diagram me-2"></i>Unser KI-gestützter Prozess</h3>
        <div class="process-timeline">
          <div class="process-step">
            <div class="process-number">1</div>
            <div class="process-content">
              <h5 class="fw-bold">Einreichung & KYC</h5>
              <p class="text-muted mb-0">Sichere Falleinreichung mit zweistufiger Identitätsverifizierung</p>
            </div>
            <div class="process-arrow"><i class="fas fa-arrow-right"></i></div>
          </div>
          <div class="process-step">
            <div class="process-number">2</div>
            <div class="process-content">
              <h5 class="fw-bold">KI-Analyse</h5>
              <p class="text-muted mb-0">Deep Learning Algorithmen analysieren Blockchain-Transaktionen</p>
            </div>
            <div class="process-arrow"><i class="fas fa-arrow-right"></i></div>
          </div>
          <div class="process-step">
            <div class="process-number">3</div>
            <div class="process-content">
              <h5 class="fw-bold">Rechtsprüfung</h5>
              <p class="text-muted mb-0">Compliance-Check und rechtliche Dokumentation</p>
            </div>
            <div class="process-arrow"><i class="fas fa-arrow-right"></i></div>
          </div>
          <div class="process-step">
            <div class="process-number">4</div>
            <div class="process-content">
              <h5 class="fw-bold">Auszahlung</h5>
              <p class="text-muted mb-0">Sichere EUR-Konvertierung via lizenzierte Börsen</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Trust Indicators -->
    <div class="row">
      <div class="col-12">
        <div class="trust-indicators">
          <div class="row align-items-center">
            <div class="col-md-3 col-6 text-center mb-3">
              <div class="trust-badge pulse-animation">
                <i class="fas fa-shield-alt fa-3x text-primary mb-2"></i>
                <p class="fw-bold mb-0">BaFin-Lizenziert</p>
                <small class="text-muted">BaFin-Reg.: <?php echo htmlspecialchars($siteSettings['fca_reference_number']); ?></small>
              </div>
            </div>
            <div class="col-md-3 col-6 text-center mb-3">
              <div class="trust-badge">
                <i class="fas fa-lock fa-3x text-success mb-2"></i>
                <p class="fw-bold mb-0">256-Bit SSL</p>
                <small class="text-muted">Verschlüsselt</small>
              </div>
            </div>
            <div class="col-md-3 col-6 text-center mb-3">
              <div class="trust-badge">
                <i class="fas fa-check-circle fa-3x text-info mb-2"></i>
                <p class="fw-bold mb-0">GDPR Konform</p>
                <small class="text-muted">EU-Standard</small>
              </div>
            </div>
            <div class="col-md-3 col-6 text-center mb-3">
              <div class="trust-badge">
                <i class="fas fa-award fa-3x text-warning mb-2"></i>
                <p class="fw-bold mb-0">ISO 27001</p>
                <small class="text-muted">Zertifiziert</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA -->
    <div class="text-center mt-5">
      <p class="lead mb-4">
        <i class="fas fa-clock me-2"></i>
        Keine Vorauszahlung – <strong>3% Gebühr nur bei Erfolg</strong>
      </p>
      <a href="<?= htmlspecialchars(($siteSettings['site_url'] ?? 'https://novalnet-ai.de') . '/app') ?>" class="btn btn-primary btn-lg px-5 py-3 btn-glow">
        <i class="fas fa-rocket me-2"></i>Kostenlose KI-Analyse starten
      </a>
      <p class="text-muted mt-3 small">
        <i class="fas fa-info-circle me-1"></i>
        Durchschnittliche Bearbeitungszeit: 14 Werktage | Persönlicher Ansprechpartner inklusive
      </p>
    </div>
  </div>
</section>



<!-- ========================================================= -->
<!-- 📊 SECTION: ERFOLGE IN ZAHLEN (AI STATISTICS) -->
<!-- ========================================================= -->
<section id="stats" class="section animated-gradient">
  <div class="container text-center text-white">
    <h2 class="section-title text-white mb-4">Unsere Erfolge in Zahlen</h2>
    <p class="section-subtitle text-white opacity-90 mb-5">
      Vertrauen durch nachweisbare Ergebnisse – KI-gestützte Wiederherstellung mit messbarem Erfolg
    </p>

    <div class="row g-4 mb-5">
      <!-- Clients -->
      <div class="col-md-6 col-lg-3">
        <div class="stat-card stat-pulse shimmer-wrapper">
          <div class="stat-icon">
            <i class="fas fa-users"></i>
          </div>
          <h2 class="display-3 fw-bold mb-2" data-count="727">0</h2>
          <p class="h5 mb-0">Zufriedene Klienten</p>
          <p class="small opacity-75 mt-2">Weltweit vertrauen uns</p>
        </div>
      </div>

      <!-- Success Rate -->
      <div class="col-md-6 col-lg-3">
        <div class="stat-card stat-pulse shimmer-wrapper">
          <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          <h2 class="display-3 fw-bold mb-2"><span data-count="87">0</span>%</h2>
          <p class="h5 mb-0">Erfolgsquote</p>
          <p class="small opacity-75 mt-2">Bei Identifizierung</p>
        </div>
      </div>

      <!-- Amount Recovered -->
      <div class="col-md-6 col-lg-3">
        <div class="stat-card shimmer-wrapper">
          <div class="stat-icon">
            <i class="fas fa-euro-sign"></i>
          </div>
          <h2 class="display-3 fw-bold mb-2">€<span data-count="47">0</span>M</h2>
          <p class="h5 mb-0">Wiederhergestellt</p>
          <p class="small opacity-75 mt-2">Gesamtvolumen</p>
        </div>
      </div>

      <!-- Processing Time -->
      <div class="col-md-6 col-lg-3">
        <div class="stat-card shimmer-wrapper">
          <div class="stat-icon">
            <i class="fas fa-clock"></i>
          </div>
          <h2 class="display-3 fw-bold mb-2"><span data-count="14">0</span></h2>
          <p class="h5 mb-0">Tage Durchschnitt</p>
          <p class="small opacity-75 mt-2">Bearbeitungszeit</p>
        </div>
      </div>
    </div>

    <!-- Second stats row -->
    <div class="row g-4 justify-content-center mb-5">
      <!-- Partner Networks -->
      <div class="col-md-6 col-lg-3">
        <div class="stat-card shimmer-wrapper">
          <div class="stat-icon">
            <i class="fas fa-handshake"></i>
          </div>
          <h2 class="display-3 fw-bold mb-2"><span data-count="30">0</span>+</h2>
          <p class="h5 mb-0">Partnerländer</p>
          <p class="small opacity-75 mt-2">Globales Netzwerk</p>
        </div>
      </div>

      <!-- Blockchains -->
      <div class="col-md-6 col-lg-3">
        <div class="stat-card shimmer-wrapper">
          <div class="stat-icon">
            <i class="fas fa-cubes"></i>
          </div>
          <h2 class="display-3 fw-bold mb-2"><span data-count="15">0</span>+</h2>
          <p class="h5 mb-0">Blockchains</p>
          <p class="small opacity-75 mt-2">Simultane Analyse</p>
        </div>
      </div>

      <!-- Fraud Cases in Database -->
      <div class="col-md-6 col-lg-3">
        <div class="stat-card shimmer-wrapper">
          <div class="stat-icon">
            <i class="fas fa-database"></i>
          </div>
          <h2 class="display-3 fw-bold mb-2"><span data-count="100">0</span>K+</h2>
          <p class="h5 mb-0">Betrugsmuster</p>
          <p class="small opacity-75 mt-2">KI-Trainingsdaten</p>
        </div>
      </div>
    </div>

    <!-- Trust Badges -->
    <div class="row justify-content-center mt-5">
      <div class="col-auto">
        <div class="d-flex align-items-center gap-4 flex-wrap justify-content-center">
          <div class="badge-item">
            <i class="fas fa-shield-alt fa-2x mb-2"></i>
            <p class="small mb-0">BaFin-Lizenziert</p>
            <p class="small mb-0 opacity-75">BaFin-Reg.: <?php echo htmlspecialchars($siteSettings['fca_reference_number']); ?></p>
          </div>
          <div class="badge-item">
            <i class="fas fa-lock fa-2x mb-2"></i>
            <p class="small mb-0">256-Bit SSL</p>
            <p class="small mb-0 opacity-75">Verschlüsselt</p>
          </div>
          <div class="badge-item">
            <i class="fas fa-check-circle fa-2x mb-2"></i>
            <p class="small mb-0">GDPR Konform</p>
            <p class="small mb-0 opacity-75">EU-Standard</p>
          </div>
          <div class="badge-item">
            <i class="fas fa-robot fa-2x mb-2"></i>
            <p class="small mb-0">KI-Technologie</p>
            <p class="small mb-0 opacity-75">Advanced ML</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
/* Ensure stats section is always visible */
#stats {
  opacity: 1 !important;
  visibility: visible !important;
  display: block !important;
}
#stats * {
  color: white !important; /* Force all text to be white */
}

.stat-card {
  background: rgba(255,255,255,0.1);
  border-radius: 16px;
  padding: 40px 20px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
  transition: all 0.3s ease;
  color: white !important; /* Ensure text is visible */
}
.stat-card h2, .stat-card p, .stat-card span {
  color: white !important; /* Force white text on all elements */
}
.stat-card .stat-icon i {
  color: white !important;
}
.stat-card:hover {
  background: rgba(255,255,255,0.15);
  transform: translateY(-5px);
}
.stat-icon {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  background: rgba(255,255,255,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  font-size: 28px;
}
.badge-item {
  background: rgba(255,255,255,0.1);
  padding: 20px 30px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.2);
}
</style>

<script>
// ========== ENHANCED AI SECTION ANIMATIONS ==========
document.addEventListener('DOMContentLoaded', function() {
  // Counter animation for .counter elements in AI section
  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
          const target = parseInt(counter.getAttribute('data-target'));
          const duration = 2000;
          const steps = 60;
          const increment = target / steps;
          let current = 0;
          
          counter.classList.add('counting');
          
          const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
              counter.textContent = target;
              clearInterval(timer);
              counter.classList.remove('counting');
            } else {
              counter.textContent = Math.floor(current);
            }
          }, duration / steps);
        });
        counterObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.2 });

  // Observe success metrics card
  const metricsCard = document.querySelector('.success-metrics-card');
  if (metricsCard) counterObserver.observe(metricsCard);

  // Animate progress bar in AI section
  const progressObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const progressBar = document.getElementById('aiProgressBar');
        if (progressBar) {
          setTimeout(() => {
            progressBar.style.width = '94%';
          }, 300);
        }
        progressObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });

  const progressElement = document.getElementById('aiProgressBar');
  if (progressElement) progressObserver.observe(progressElement.closest('.success-metrics-card'));

  // Scroll-triggered animations for AI feature cards
  const scrollObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animated');
      }
    });
  }, { threshold: 0.1 });

  // Observe all elements with animate-on-scroll class
  document.querySelectorAll('.animate-on-scroll').forEach(el => {
    scrollObserver.observe(el);
  });

  // Add hover effect enhancements
  document.querySelectorAll('.ai-feature-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-10px) scale(1.02)';
    });
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0) scale(1)';
    });
  });

  // Process timeline animation
  const processSteps = document.querySelectorAll('.process-number');
  processSteps.forEach((step, index) => {
    step.style.animationDelay = `${index * 0.2}s`;
  });
});
</script>


<!-- ========================================================= -->
<!-- 🤖 SECTION: KI-GESTÜTZTE FUNKTIONEN -->
<!-- ========================================================= -->
<section id="ai-features" class="section bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">KI-gestützte Blockchain-Analyse</h2>
      <p class="section-subtitle">
        Modernste Technologie für maximale Erfolgschancen bei der Wiederherstellung Ihrer Krypto-Guthaben
      </p>
    </div>

    <div class="row g-4 mb-5">
      <!-- AI Feature 1: Machine Learning -->
      <div class="col-md-6 col-lg-4">
        <div class="feature-card">
          <div class="feature-icon-lg mb-4">
            <i class="fas fa-brain fa-3x text-primary"></i>
          </div>
          <h4 class="fw-bold mb-3">Deep Learning Algorithmen</h4>
          <p class="text-muted">
            Unsere KI analysiert Millionen von Blockchain-Transaktionen in Echtzeit und erkennt Betrugsmuster mit einer Genauigkeit von 94%.
          </p>
          <ul class="list-unstyled text-start mt-3">
            <li><i class="fas fa-check-circle text-success me-2"></i> Mustererkennung in Transaktionen</li>
            <li><i class="fas fa-check-circle text-success me-2"></i> Verhaltensanalyse von Wallets</li>
            <li><i class="fas fa-check-circle text-success me-2"></i> Betrugserkennung in Echtzeit</li>
          </ul>
        </div>
      </div>

      <!-- AI Feature 2: Blockchain Tracking -->
      <div class="col-md-6 col-lg-4">
        <div class="feature-card">
          <div class="feature-icon-lg mb-4">
            <i class="fas fa-project-diagram fa-3x text-primary"></i>
          </div>
          <h4 class="fw-bold mb-3">Multi-Chain Tracking</h4>
          <p class="text-muted">
            Verfolgen Sie Ihre Kryptowährungen über 15+ Blockchains hinweg. Unsere KI identifiziert verdächtige Transaktionen und verfolgt Geldflüsse.
          </p>
          <ul class="list-unstyled text-start mt-3">
            <li><i class="fas fa-check-circle text-success me-2"></i> Bitcoin, Ethereum, BSC, Polygon</li>
            <li><i class="fas fa-check-circle text-success me-2"></i> Cross-Chain Analyse</li>
            <li><i class="fas fa-check-circle text-success me-2"></i> Mixer & Tumbler Erkennung</li>
          </ul>
        </div>
      </div>

      <!-- AI Feature 3: Fraud Detection -->
      <div class="col-md-6 col-lg-4">
        <div class="feature-card">
          <div class="feature-icon-lg mb-4">
            <i class="fas fa-shield-alt fa-3x text-primary"></i>
          </div>
          <h4 class="fw-bold mb-3">Betrugs-Identifikation</h4>
          <p class="text-muted">
            Mit über 100.000 bekannten Betrugsfällen trainiert, identifiziert unsere KI neue Betrugsmaschen und schützt Ihr Vermögen.
          </p>
          <ul class="list-unstyled text-start mt-3">
            <li><i class="fas fa-check-circle text-success me-2"></i> Phishing-Erkennung</li>
            <li><i class="fas fa-check-circle text-success me-2"></i> Ponzi-Schema Analyse</li>
            <li><i class="fas fa-check-circle text-success me-2"></i> Exit-Scam Früherkennung</li>
          </ul>
        </div>
      </div>

      <!-- AI Feature 4: Risk Assessment -->
      <div class="col-md-6 col-lg-4">
        <div class="feature-card">
          <div class="feature-icon-lg mb-4">
            <i class="fas fa-chart-bar fa-3x text-primary"></i>
          </div>
          <h4 class="fw-bold mb-3">Risiko-Bewertung</h4>
          <p class="text-muted">
            Automatische Bewertung Ihrer Wiederherstellungschancen basierend auf historischen Daten und aktuellen Blockchain-Informationen.
          </p>
          <ul class="list-unstyled text-start mt-3">
            <li><i class="fas fa-check-circle text-success me-2"></i> Erfolgswahrscheinlichkeit</li>
            <li><i class="fas fa-check-circle text-success me-2"></i> Zeitschätzung</li>
            <li><i class="fas fa-check-circle text-success me-2"></i> Kostenprognose</li>
          </ul>
        </div>
      </div>

      <!-- AI Feature 5: Automated Reports -->
      <div class="col-md-6 col-lg-4">
        <div class="feature-card">
          <div class="feature-icon-lg mb-4">
            <i class="fas fa-file-alt fa-3x text-primary"></i>
          </div>
          <h4 class="fw-bold mb-3">Automatische Berichte</h4>
          <p class="text-muted">
            Erhalten Sie detaillierte Analyseberichte automatisch generiert durch unsere KI – transparent und nachvollziehbar für Behörden.
          </p>
          <ul class="list-unstyled text-start mt-3">
            <li><i class="fas fa-check-circle text-success me-2"></i> Transaktionsanalyse</li>
            <li><i class="fas fa-check-circle text-success me-2"></i> Wallet-Verbindungen</li>
            <li><i class="fas fa-check-circle text-success me-2"></i> Beweissicherung</li>
          </ul>
        </div>
      </div>

      <!-- AI Feature 6: Real-Time Monitoring -->
      <div class="col-md-6 col-lg-4">
        <div class="feature-card">
          <div class="feature-icon-lg mb-4">
            <i class="fas fa-eye fa-3x text-primary"></i>
          </div>
          <h4 class="fw-bold mb-3">Echtzeit-Überwachung</h4>
          <p class="text-muted">
            24/7 Blockchain-Monitoring durch KI. Werden verdächtige Bewegungen erkannt, werden Sie sofort informiert.
          </p>
          <ul class="list-unstyled text-start mt-3">
            <li><i class="fas fa-check-circle text-success me-2"></i> Automatische Benachrichtigungen</li>
            <li><i class="fas fa-check-circle text-success me-2"></i> Wallet-Bewegungen tracken</li>
            <li><i class="fas fa-check-circle text-success me-2"></i> Sofortige Alerts</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- AI Technology Highlight -->
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="ai-highlight-box">
          <div class="row align-items-center">
            <div class="col-md-2 text-center mb-3 mb-md-0">
              <i class="fas fa-microchip fa-4x opacity-75"></i>
            </div>
            <div class="col-md-10 text-start">
              <h4 class="fw-bold mb-3">Modernste KI-Technologie im Einsatz</h4>
              <p class="mb-0 opacity-90">
                Unsere proprietären Machine-Learning-Algorithmen wurden mit über 100.000 Betrugsfällen trainiert und können selbst komplexeste Geldflüsse über mehrere Blockchains hinweg verfolgen. Mit einer Identifizierungsrate von 87% und kontinuierlichem Lernen aus neuen Fällen bieten wir die fortschrittlichste Lösung zur Krypto-Wiederherstellung auf dem Markt.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.feature-icon-lg {
  width: 100px;
  height: 100px;
  border-radius: 20px;
  background: linear-gradient(135deg, #e7f3ff 0%, #cfe8ff 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
}
.ai-highlight-box {
  background: linear-gradient(135deg, var(--primary, #0d6efd) 0%, #0b5ed7 100%);
  color: white;
  border-radius: 16px;
  padding: 40px;
  border: 1px solid rgba(255,255,255,0.15);
  box-shadow: 0 8px 32px rgba(13,110,253,0.25);
}
.ai-highlight-box h4,
.ai-highlight-box p {
  color: white;
}
.ai-highlight-box p {
  opacity: .92;
}
.testimonial-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 700;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.testimonial-type {
  font-size: .85rem;
}
</style>


<!-- FAQ -->
<section id="faq" class="section anchor-offset">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Häufige Fragen</h2>
            <p class="section-subtitle">Antworten auf die wichtigsten Fragen zu <?= htmlspecialchars($siteSettings['brand_name']) ?></p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Wie funktioniert der Wiederherstellungsservice?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Unser spezialisierter Service unterstützt Sie bei der Wiederherstellung verlorener Zugänge (z. B. Seed-Phrase).
                                KI-gestützte Analyse, manuelle Verifikation und transparente Dokumentation sorgen für eine rechtssichere Rückführung.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Wie lange dauern Ein- und Auszahlungen?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Alle SEPA-Überweisungen werden manuell geprüft und in der Regel innerhalb von 1–2 Werktagen bearbeitet.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Ist die Plattform reguliert?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Wir arbeiten nach europäischen Compliance-Standards (u. a. AML-Richtlinien) und dokumentieren alle Schritte revisionssicher.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Welche Arten von Krypto-Betrug können Sie untersuchen?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Wir unterstützen Sie bei nahezu allen Formen von Krypto-Betrug: gefälschte Investment-Plattformen (Scam-Exchanges), Pig-Butchering-Betrug, Romance Scams, Phishing-Angriffe, Rug-Pulls, ICO-Betrug, Wallet-Hacks sowie gestohlene Seed-Phrasen. Auch komplexe Fälle mit mehrfacher Geldwäsche über verschiedene Blockchains können wir analysieren.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Was passiert nach meiner Falleinreichung?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Nach Einreichung Ihres Falls erhalten Sie innerhalb von 24 Stunden eine persönliche Ersteinschätzung per E-Mail. Unser Team prüft Ihre Angaben, startet die KI-gestützte Blockchain-Analyse und kontaktiert Sie dann mit einem detaillierten Analyseplan sowie realistischen Erfolgsaussichten – vollständig kostenlos und unverbindlich.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                Wie hoch ist Ihre Erfolgsquote und wovon hängt sie ab?
                            </button>
                        </h2>
                        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Unsere dokumentierte Erfolgsquote liegt bei 87 %. Sie hängt von mehreren Faktoren ab: wie schnell nach dem Betrug gehandelt wird (je früher, desto besser), ob die Kryptowährungen bereits über Mixer bewegt wurden, und wie kooperativ die beteiligten Exchanges sind. Wir geben Ihnen bereits in der Erstanalyse eine realistische Einschätzung Ihrer individuellen Chancen.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                Sind meine Daten bei Ihnen sicher?
                            </button>
                        </h2>
                        <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Absolut. Alle Ihre Falldaten werden nach DSGVO-Standards verarbeitet und AES-256-verschlüsselt gespeichert. Wir geben keinerlei Informationen an Dritte weiter – außer an kooperierende Behörden mit Ihrer ausdrücklichen Zustimmung im Rahmen eines laufenden Verfahrens. Unsere Server stehen ausschließlich in Deutschland.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                                Was kostet der Service und wann muss ich zahlen?
                            </button>
                        </h2>
                        <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Die Erstanalyse und Erstberatung sind vollständig kostenlos. Im Erfolgsfall erheben wir eine Erfolgsprovision, die individuell vereinbart wird und sich nach der Komplexität des Falls sowie der wiederhergestellten Summe richtet. Es entstehen <strong>keinerlei Vorabgebühren</strong>. Sollte keine Rückführung möglich sein, fallen für Sie keine Kosten an.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                                Wie lange dauert eine vollständige Fallbearbeitung?
                            </button>
                        </h2>
                        <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Die Dauer hängt von der Komplexität des Falls ab. Einfachere Fälle (z. B. einzelne Betrugs-Transaktionen) können in 7–14 Werktagen abgeschlossen werden. Komplexe Fälle mit mehreren Blockchains, Mixing-Services oder internationalen Tätern können 4–12 Wochen in Anspruch nehmen. Sie erhalten in jedem Schritt transparente Statusupdates über unser verschlüsseltes Mandanten-Portal.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                                Kann ich auch Geld zurückbekommen, wenn der Betrug schon lange zurückliegt?
                            </button>
                        </h2>
                        <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Grundsätzlich gilt: Je früher, desto besser – allerdings sind auch ältere Fälle oft noch lösbar. Da Blockchain-Transaktionen dauerhaft und unveränderlich gespeichert sind, können wir Transaktionsspuren auch Jahre später noch nachverfolgen. Wir empfehlen, auch bei älteren Betrugsfällen eine kostenlose Erstanalyse einzureichen – oft gibt es mehr Möglichkeiten, als Opfer erwarten.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq11">
                                Arbeiten Sie mit der Polizei und Strafverfolgungsbehörden zusammen?
                            </button>
                        </h2>
                        <div id="faq11" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Ja. Wir kooperieren aktiv mit dem Bundeskriminalamt (BKA), Europol, Interpol und verschiedenen nationalen Polizeibehörden. Unsere forensischen Berichte sind bewusst so aufgebaut, dass sie als Beweismittel in Strafverfahren verwendet werden können. Mit Ihrer Zustimmung leiten wir relevante Informationen direkt an die zuständigen Behörden weiter, um eine strafrechtliche Verfolgung der Täter zu unterstützen.
                            </div>
                        </div>
                    </div>
                </div><!-- /accordion -->
            </div>
        </div>
    </div>
</section>

<!-- Testimonials / Erfahrungsberichte -->
<section id="testimonials" class="section bg-light anchor-offset">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Was unsere Mandanten sagen</h2>
            <p class="section-subtitle">Echte Erfahrungen – dokumentierte Rückführungen. Namen wurden auf Wunsch anonymisiert.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="testimonial-avatar" style="background:linear-gradient(135deg,#0d6efd,#37a0ff)">M.H.</div>
                        <div class="ms-3">
                            <div class="fw-bold">M. H., Frankfurt</div>
                            <div class="text-muted testimonial-type">Pig-Butchering-Betrug · €38.400 zurück</div>
                        </div>
                    </div>
                    <div class="text-warning mb-2">★★★★★</div>
                    <p class="text-muted mb-0">„Ich hatte keine Hoffnung mehr. Über eine gefälschte Trading-Plattform verlor ich fast meine gesamten Ersparnisse. Das Team hat innerhalb von 6 Wochen den Großteil meiner Bitcoin zurückgeführt. Professionell, transparent und immer erreichbar."</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="testimonial-avatar" style="background:linear-gradient(135deg,#198754,#20c997)">S.K.</div>
                        <div class="ms-3">
                            <div class="fw-bold">S. K., München</div>
                            <div class="text-muted testimonial-type">Romance Scam · €22.100 zurück</div>
                        </div>
                    </div>
                    <div class="text-warning mb-2">★★★★★</div>
                    <p class="text-muted mb-0">„Die Blockchain-Analyse hat Täter identifiziert, die ich für unaufspürbar hielt. Die vollständige Dokumentation hat uns bei der Strafanzeige enorm geholfen. Ich kann diesen Service jedem empfehlen, der Opfer von Krypto-Betrug wurde."</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="testimonial-avatar" style="background:linear-gradient(135deg,#dc3545,#fd7e14)">T.W.</div>
                        <div class="ms-3">
                            <div class="fw-bold">T. W., Berlin</div>
                            <div class="text-muted testimonial-type">Phishing / Wallet-Hack · €15.700 zurück</div>
                        </div>
                    </div>
                    <div class="text-warning mb-2">★★★★★</div>
                    <p class="text-muted mb-0">„Mein Wallet wurde über einen Phishing-Link kompromittiert. Dank der Echtzeit-Überwachung konnten weitere Transaktionen gestoppt und ein Großteil zurückgeholt werden. Schnelle Reaktion, klare Kommunikation – absolut empfehlenswert."</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="testimonial-avatar" style="background:linear-gradient(135deg,#6f42c1,#0d6efd)">A.R.</div>
                        <div class="ms-3">
                            <div class="fw-bold">A. R., Hamburg</div>
                            <div class="text-muted testimonial-type">Fake Investment Platform · €67.000 zurück</div>
                        </div>
                    </div>
                    <div class="text-warning mb-2">★★★★★</div>
                    <p class="text-muted mb-0">„Ich investierte in eine vermeintlich seriöse KI-Handelsplattform und verlor alles. Das Analyseberichts-System half der Staatsanwaltschaft, die Verantwortlichen zu identifizieren. Die Zusammenarbeit war erstklassig."</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="testimonial-avatar" style="background:linear-gradient(135deg,#0dcaf0,#0d6efd)">L.M.</div>
                        <div class="ms-3">
                            <div class="fw-bold">L. M., Köln</div>
                            <div class="text-muted testimonial-type">Rug-Pull / DeFi-Betrug · €9.800 zurück</div>
                        </div>
                    </div>
                    <div class="text-warning mb-2">★★★★★</div>
                    <p class="text-muted mb-0">„Nach einem DeFi-Rug-Pull dachte ich, das Geld sei für immer weg. Das Team analysierte den Smart Contract und verfolgte die Gelder über drei verschiedene Netzwerke. Das Ergebnis hat mich wirklich überrascht."</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="testimonial-avatar" style="background:linear-gradient(135deg,#20c997,#0d6efd)">B.S.</div>
                        <div class="ms-3">
                            <div class="fw-bold">B. S., Stuttgart</div>
                            <div class="text-muted testimonial-type">NFT-Betrug · €11.200 zurück</div>
                        </div>
                    </div>
                    <div class="text-warning mb-2">★★★★★</div>
                    <p class="text-muted mb-0">„Meinen Kindern zuliebe habe ich auf einen NFT-Marktplatz-Betrug reingefallen. Die Erstberatung war kostenlos und extrem hilfreich. Am Ende haben wir einen substanziellen Teil zurückbekommen – mehr als ich gehofft hatte."</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="testimonial-avatar" style="background:linear-gradient(135deg,#fd7e14,#dc3545)">K.M.</div>
                        <div class="ms-3">
                            <div class="fw-bold">K. M., Düsseldorf</div>
                            <div class="text-muted testimonial-type">Pig-Butchering / Fake-Broker · €51.000 zurück</div>
                        </div>
                    </div>
                    <div class="text-warning mb-2">★★★★★</div>
                    <p class="text-muted mb-0">„Über einen Fake-Broker habe ich monatelang meine Rentenrücklagen verloren. Die Blockchain-Analyse lieferte entscheidende Beweise, die die Staatsanwaltschaft für die Anklage nutzte. Das Team war bis zum Ende für mich da."</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="testimonial-avatar" style="background:linear-gradient(135deg,#6f42c1,#e83e8c)">J.V.</div>
                        <div class="ms-3">
                            <div class="fw-bold">J. V., Leipzig</div>
                            <div class="text-muted testimonial-type">Gefälschte Börse · €29.500 zurück</div>
                        </div>
                    </div>
                    <div class="text-warning mb-2">★★★★★</div>
                    <p class="text-muted mb-0">„Ich habe mich von einer täuschend echten Handelsplattform blenden lassen. Nach der Erstanalyse hatte ich zum ersten Mal wieder Hoffnung. Der Fallmanager hat mich wöchentlich informiert – man fühlt sich wirklich aufgehoben und nicht allein gelassen."</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Warum uns wählen – Vergleichstabelle -->
<section id="why-us" class="section anchor-offset">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Warum <?= htmlspecialchars($siteSettings['brand_name']) ?>?</h2>
            <p class="section-subtitle">Ein transparenter Vergleich zeigt, was uns von anderen unterscheidet</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center" style="border-radius:12px;overflow:hidden;">
                        <thead style="background:linear-gradient(135deg,#0d6efd,#0b5ed7);color:#fff;">
                            <tr>
                                <th class="text-start py-3 px-4">Merkmal</th>
                                <th class="py-3"><?= htmlspecialchars($siteSettings['brand_name']) ?></th>
                                <th class="py-3 text-muted">Typische Konkurrenten</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-start px-4 fw-semibold">Kostenlose Erstanalyse</td>
                                <td><i class="fas fa-check-circle text-success fa-lg"></i></td>
                                <td><i class="fas fa-times-circle text-danger fa-lg"></i></td>
                            </tr>
                            <tr class="table-light">
                                <td class="text-start px-4 fw-semibold">Keine Vorabgebühren</td>
                                <td><i class="fas fa-check-circle text-success fa-lg"></i></td>
                                <td><i class="fas fa-times-circle text-danger fa-lg"></i></td>
                            </tr>
                            <tr>
                                <td class="text-start px-4 fw-semibold">KI-gestützte Blockchain-Analyse</td>
                                <td><i class="fas fa-check-circle text-success fa-lg"></i></td>
                                <td><span class="text-muted">Teilweise</span></td>
                            </tr>
                            <tr class="table-light">
                                <td class="text-start px-4 fw-semibold">Dokumentierte 87 % Erfolgsquote</td>
                                <td><i class="fas fa-check-circle text-success fa-lg"></i></td>
                                <td><i class="fas fa-times-circle text-danger fa-lg"></i></td>
                            </tr>
                            <tr>
                                <td class="text-start px-4 fw-semibold">DSGVO-konform (Server in Deutschland)</td>
                                <td><i class="fas fa-check-circle text-success fa-lg"></i></td>
                                <td><span class="text-muted">Selten</span></td>
                            </tr>
                            <tr class="table-light">
                                <td class="text-start px-4 fw-semibold">Persönlicher Fallmanager (Deutsch)</td>
                                <td><i class="fas fa-check-circle text-success fa-lg"></i></td>
                                <td><i class="fas fa-times-circle text-danger fa-lg"></i></td>
                            </tr>
                            <tr>
                                <td class="text-start px-4 fw-semibold">Gerichtsverwertbare Berichte</td>
                                <td><i class="fas fa-check-circle text-success fa-lg"></i></td>
                                <td><span class="text-muted">Selten</span></td>
                            </tr>
                            <tr class="table-light">
                                <td class="text-start px-4 fw-semibold">24/7 Echtzeit-Monitoring</td>
                                <td><i class="fas fa-check-circle text-success fa-lg"></i></td>
                                <td><i class="fas fa-times-circle text-danger fa-lg"></i></td>
                            </tr>
                            <tr>
                                <td class="text-start px-4 fw-semibold">Kooperation mit BKA/Europol/Interpol</td>
                                <td><i class="fas fa-check-circle text-success fa-lg"></i></td>
                                <td><span class="text-muted">Selten</span></td>
                            </tr>
                            <tr class="table-light">
                                <td class="text-start px-4 fw-semibold">ISO 27001 Informationssicherheit</td>
                                <td><i class="fas fa-check-circle text-success fa-lg"></i></td>
                                <td><i class="fas fa-times-circle text-danger fa-lg"></i></td>
                            </tr>
                            <tr>
                                <td class="text-start px-4 fw-semibold">Notfall-Hotline 24/7</td>
                                <td><i class="fas fa-check-circle text-success fa-lg"></i></td>
                                <td><i class="fas fa-times-circle text-danger fa-lg"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-center text-muted mt-3" style="font-size:.9rem;"><i class="fas fa-info-circle me-1"></i>Vergleich basiert auf öffentlich verfügbaren Angaben typischer Recovery-Dienstleister. Stand: 2025.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section text-white text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="mb-3">
            <span class="badge bg-white text-primary px-3 py-2" style="font-size:0.9rem; border-radius:20px;">
                <i class="fas fa-shield-alt me-2"></i>Kostenlose Erstberatung · Keine Vorabkosten
            </span>
        </div>
        <h2 class="display-5 fw-bold mb-3">Haben Sie Krypto durch Betrug verloren?</h2>
        <p class="lead mb-4" style="max-width:700px;margin:0 auto; opacity:.92;">
            Registrieren Sie sich jetzt kostenlos, schildern Sie Ihren Fall und lassen Sie unsere KI 
            die Blockchain nach Ihren Vermögenswerten durchsuchen. Keine Gebühren bis zur erfolgreichen Rückführung.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mb-4">
            <a href="app/register.php" class="btn btn-light btn-lg fw-bold px-5 py-3" style="border-radius:12px; color:#764ba2;">
                <i class="fas fa-rocket me-2"></i>Jetzt kostenlosen Fall einreichen
            </a>
            <a href="app/login.php" class="btn btn-outline-light btn-lg px-5 py-3" style="border-radius:12px;">
                <i class="fas fa-sign-in-alt me-2"></i>Einloggen
            </a>
            <button type="button" class="btn btn-warning btn-lg fw-bold px-5 py-3" style="border-radius:12px;" data-bs-toggle="modal" data-bs-target="#contactLeadModal">
                <i class="fas fa-user-plus me-2"></i>Jetzt kostenlos anmelden
            </button>
        </div>
        <div class="row justify-content-center g-3" style="max-width:600px; margin:0 auto;">
            <div class="col-auto">
                <span style="opacity:.85;"><i class="fas fa-check-circle me-1"></i>100% vertraulich</span>
            </div>
            <div class="col-auto">
                <span style="opacity:.85;"><i class="fas fa-check-circle me-1"></i>BaFin-lizenziert</span>
            </div>
            <div class="col-auto">
                <span style="opacity:.85;"><i class="fas fa-check-circle me-1"></i>87% Erfolgsquote</span>
            </div>
        </div>
    </div>
</section>

<!-- JavaScript for Animations -->
<script>
// ========== Particle Network Animation ==========
(function() {
    const canvas = document.getElementById('particles-canvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
    
    const particles = [];
    const particleCount = 30;
    const connectionDistance = 120;
    
    class Particle {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.vx = (Math.random() - 0.5) * 0.5;
            this.vy = (Math.random() - 0.5) * 0.5;
            this.radius = 2;
        }
        
        update() {
            this.x += this.vx;
            this.y += this.vy;
            
            if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
            if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
        }
        
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(13, 110, 253, 0.5)';
            ctx.fill();
        }
    }
    
    // Create particles
    for (let i = 0; i < particleCount; i++) {
        particles.push(new Particle());
    }
    
    // Animation loop
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Update and draw particles
        particles.forEach(particle => {
            particle.update();
            particle.draw();
        });
        
        // Draw connections
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < connectionDistance) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = `rgba(13, 110, 253, ${0.15 * (1 - distance / connectionDistance)})`;
                    ctx.lineWidth = 0.5;
                    ctx.stroke();
                }
            }
        }
        
        requestAnimationFrame(animate);
    }
    
    animate();
    
    // Resize handler
    window.addEventListener('resize', () => {
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
    });
})();

// ========== Scroll-Based Animations ==========
(function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-on-scroll');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    // Observe sections for animation
    document.querySelectorAll('.section').forEach(section => {
        observer.observe(section);
    });
})();

// ========== Enhanced Statistics Counter ==========
(function() {
    const statsSection = document.querySelector('#stats');
    if (!statsSection) return;
    
    let animated = false;
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !animated) {
                animated = true;
                
                // Animate all [data-count] elements inside stats section
                statsSection.querySelectorAll('[data-count]').forEach(el => {
                    const target = parseInt(el.getAttribute('data-count'));
                    let current = 0;
                    const increment = target / 60;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            el.textContent = target;
                            clearInterval(timer);
                        } else {
                            el.textContent = Math.floor(current);
                        }
                    }, 33);
                });
                
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    
    observer.observe(statsSection);
})();
</script>

<!-- Footer -->
<!-- ========================================================= -->
<!-- 🌐 FOOTER – Novalnet AI -->
<!-- ========================================================= -->
<?php include 'footer.php'; ?>
