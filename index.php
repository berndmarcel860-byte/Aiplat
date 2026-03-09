<?php
include_once 'includes/site_settings.php';
$page_title = ($siteSettings['brand_name'] ?? 'Novalnet AI') . ' – Sichere Krypto-Rückführung & Wiederherstellung';
$page_description = 'KI-gestützte Blockchain-Analyse zur Identifizierung und Wiederherstellung betrügerisch entwendeter Kryptowährungen. BaFin-lizenziert mit 87% Erfolgsquote.';
$page_keywords = 'Krypto Wiederherstellung, Blockchain Analyse, Betrugsaufklärung, KI Krypto, BaFin lizenziert';
$page_url = 'https://novalnet-ai.de/index.php';

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
        <div class="mx-auto" style="max-width:640px;">
            <div class="card shadow-lg border-0" style="border-radius:16px; background:rgba(255,255,255,0.95);">
                <div class="card-body p-4 text-start">
                    <h5 class="fw-bold text-center mb-3" style="color:#1a1a2e;">
                        🔍 Schnellbewertung – Wie hoch ist Ihr Verlust?
                    </h5>
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-5">
                            <label class="form-label fw-semibold text-muted" style="font-size:.85rem;">
                                Verlorener Betrag (€)
                            </label>
                            <select id="lossAmount" class="form-select form-select-lg"
                                    style="border-radius:10px; border:2px solid #e1e8ed;">
                                <option value="">Betrag wählen…</option>
                                <option value="5000">Bis €5.000</option>
                                <option value="25000">€5.000 – €25.000</option>
                                <option value="50000">€25.000 – €50.000</option>
                                <option value="100000">€50.000 – €100.000</option>
                                <option value="250000">Über €100.000</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold text-muted" style="font-size:.85rem;">
                                Art des Verlusts
                            </label>
                            <select id="lossType" class="form-select form-select-lg"
                                    style="border-radius:10px; border:2px solid #e1e8ed;">
                                <option value="">Art wählen…</option>
                                <option value="exchange">Fake Exchange</option>
                                <option value="investment">Investment-Betrug</option>
                                <option value="romance">Romance Scam</option>
                                <option value="rug">Rug Pull / Token-Betrug</option>
                                <option value="phishing">Phishing / Wallet-Hack</option>
                                <option value="other">Sonstiges</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <button id="estimatorBtn" class="btn btn-primary btn-lg w-100 fw-bold"
                                    style="border-radius:10px; background:linear-gradient(135deg,#667eea,#764ba2); border:none;">
                                Prüfen
                            </button>
                        </div>
                    </div>
                    <!-- Validation hint (shown when fields empty) -->
                    <div id="estimatorHint" class="mt-3 d-none">
                        <div class="alert alert-warning mb-0 py-2" style="border-radius:10px;">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Bitte wählen Sie Betrag und Art des Verlusts aus.
                        </div>
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
  <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
    <div class="modal-content border-0" style="border-radius:16px; overflow:hidden;">
      <!-- Header -->
      <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,#667eea,#764ba2); padding:24px 28px 16px;">
        <div>
          <h5 class="modal-title fw-bold text-white mb-1" id="contactLeadModalLabel">
            <i class="fas fa-search me-2"></i>Kostenlose Verlustanalyse
          </h5>
          <p class="text-white mb-0" style="opacity:.85; font-size:.9rem;">
            Wir melden uns innerhalb von 24 Stunden bei Ihnen.
          </p>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Schließen"></button>
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
            <label for="clm-phone" class="form-label fw-semibold" style="font-size:.9rem;">Telefonnummer <span class="text-muted fw-normal">(optional)</span></label>
            <input type="tel" class="form-control" id="clm-phone" name="phone" maxlength="50"
                   placeholder="+49 123 456789" autocomplete="tel">
          </div>
          <div class="mb-3">
            <label for="clm-message" class="form-label fw-semibold" style="font-size:.9rem;">Kurze Beschreibung <span class="text-muted fw-normal">(optional)</span></label>
            <textarea class="form-control" id="clm-message" name="message" rows="3" maxlength="2000"
                      placeholder="Beschreiben Sie kurz, was passiert ist…"></textarea>
          </div>

          <!-- Success / Error feedback -->
          <div id="clm-feedback" class="d-none"></div>

          <button type="submit" id="clm-submit" class="btn btn-lg w-100 fw-bold text-white"
                  style="background:linear-gradient(135deg,#667eea,#764ba2); border:none; border-radius:10px;">
            <span id="clm-submit-text"><i class="fas fa-paper-plane me-2"></i>Anfrage absenden</span>
            <span id="clm-submit-spinner" class="d-none">
              <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Wird gesendet…
            </span>
          </button>

          <p class="text-center text-muted mt-3 mb-0" style="font-size:.78rem;">
            <i class="fas fa-lock me-1"></i>Ihre Daten werden vertraulich behandelt und nicht weitergegeben.
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
    <div class="container text-center">
        <h2 class="section-title">Plattform-Features</h2>
        <p class="section-subtitle">Fokussiert, verlässlich und konform</p>
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
      <a href="https://novalnet-ai.de/app" class="btn btn-primary btn-lg px-5 py-3 btn-glow">
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
// Animated counter effect
document.addEventListener('DOMContentLoaded', function() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const counters = entry.target.querySelectorAll('[data-count]');
        counters.forEach(counter => {
          const target = parseInt(counter.getAttribute('data-count'));
          const duration = 2000;
          const steps = 60;
          const increment = target / steps;
          let current = 0;
          
          const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
              counter.textContent = target;
              clearInterval(timer);
            } else {
              counter.textContent = Math.floor(current);
            }
          }, duration / steps);
        });
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });

  const statsSection = document.getElementById('stats');
  if (statsSection) observer.observe(statsSection);

  // ========== ENHANCED AI SECTION ANIMATIONS ==========
  
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
.stat-card {
  padding: 40px 20px;
  border-radius: 16px;
  background: rgba(255,255,255,0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
  transition: all 0.3s ease;
}
.stat-card:hover {
  background: rgba(255,255,255,0.15);
  transform: translateY(-5px);
}
.stat-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(255,255,255,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  font-size: 32px;
  border: 2px solid rgba(255,255,255,0.3);
}
.badge-item {
  text-align: center;
  padding: 20px;
  background: rgba(255,255,255,0.1);
  border-radius: 12px;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
}
.badge-item:hover {
  transform: scale(1.05) rotate(2deg);
  background: rgba(255,255,255,0.15);
}
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
  background: rgba(255,255,255,0.1);
  border-radius: 16px;
  padding: 40px;
  border: 2px solid rgba(255,255,255,0.2);
  backdrop-filter: blur(10px);
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
                </div><!-- /accordion -->
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
                
                // Animate counters
                const counters = [
                    { el: document.querySelector('[data-count="727"]'), target: 727 },
                    { el: document.querySelector('[data-count="87"]'), target: 87 },
                    { el: document.querySelector('[data-count="47"]'), target: 47 },
                    { el: document.querySelector('[data-count="14"]'), target: 14 }
                ];
                
                counters.forEach(counter => {
                    if (!counter.el) return;
                    let current = 0;
                    const increment = counter.target / 60;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= counter.target) {
                            counter.el.textContent = counter.target;
                            clearInterval(timer);
                        } else {
                            counter.el.textContent = Math.floor(current);
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
