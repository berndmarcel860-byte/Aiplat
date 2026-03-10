<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zugang anfragen | Fund Recovery Services</title>
    <link href="assets/css/app.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0d1b2a 0%, #1b2a3b 60%, #102030 100%);
            min-height: 100vh;
        }
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .register-card {
            border-radius: 12px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255,255,255,0.07);
            background: #ffffff;
            max-width: 520px;
            width: 100%;
        }
        .register-header {
            background: linear-gradient(135deg, #1a3a5c 0%, #0d2137 100%);
            border-radius: 12px 12px 0 0;
            padding: 28px 32px 22px;
            text-align: center;
        }
        .register-header img {
            height: 56px;
            margin-bottom: 14px;
        }
        .register-header .header-title {
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .register-header .header-subtitle {
            color: rgba(255,255,255,0.65);
            font-size: 0.82rem;
            margin-top: 4px;
        }
        .register-body {
            padding: 32px 36px 28px;
        }
        .notice-icon {
            width: 58px;
            height: 58px;
            background: #fff8e1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
        }
        .notice-icon svg {
            width: 30px;
            height: 30px;
            fill: #f59e0b;
        }
        .notice-title {
            text-align: center;
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a3a5c;
            margin-bottom: 12px;
        }
        .notice-text {
            text-align: center;
            font-size: 0.92rem;
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 24px;
        }
        .notice-text strong {
            color: #1a3a5c;
        }
        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 20px 0;
        }
        .steps {
            background: #f8fafc;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 24px;
        }
        .steps h6 {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #6b7280;
            margin-bottom: 12px;
        }
        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
            font-size: 0.88rem;
            color: #374151;
        }
        .step-item:last-child {
            margin-bottom: 0;
        }
        .step-number {
            width: 22px;
            height: 22px;
            min-width: 22px;
            background: #1a3a5c;
            color: #fff;
            border-radius: 50%;
            font-size: 0.72rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1px;
        }
        .btn-request {
            background: linear-gradient(135deg, #1a3a5c 0%, #0d2137 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: opacity 0.2s;
            letter-spacing: 0.3px;
        }
        .btn-request:hover {
            opacity: 0.88;
            color: #ffffff;
            text-decoration: none;
        }
        .register-footer {
            border-top: 1px solid #f0f0f0;
            padding: 16px 32px 18px;
            text-align: center;
            font-size: 0.8rem;
            color: #9ca3af;
        }

        /* ── Modal ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(10, 20, 35, 0.72);
            z-index: 1000;
            overflow-y: auto;
            padding: 30px 15px;
        }
        .modal-overlay.active {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .modal-box {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 12px 50px rgba(0,0,0,0.45);
            width: 100%;
            max-width: 560px;
            overflow: hidden;
            animation: slideIn 0.25s ease;
        }
        @keyframes slideIn {
            from { transform: translateY(-18px); opacity: 0; }
            to   { transform: translateY(0);     opacity: 1; }
        }
        .modal-header {
            background: linear-gradient(135deg, #1a3a5c 0%, #0d2137 100%);
            padding: 20px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-header h5 {
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }
        .modal-close {
            background: none;
            border: none;
            color: rgba(255,255,255,0.7);
            font-size: 1.4rem;
            line-height: 1;
            cursor: pointer;
            padding: 0 2px;
            transition: color 0.15s;
        }
        .modal-close:hover { color: #fff; }
        .modal-body {
            padding: 26px 28px 20px;
        }
        .modal-body .form-group label {
            font-size: 0.84rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
            display: block;
        }
        .modal-body .form-control,
        .modal-body select.form-control {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 9px 13px;
            font-size: 0.88rem;
            color: #111827;
            width: 100%;
            transition: border-color 0.2s, box-shadow 0.2s;
            margin-bottom: 14px;
            background: #fff;
        }
        .modal-body .form-control:focus,
        .modal-body select.form-control:focus {
            border-color: #1a3a5c;
            box-shadow: 0 0 0 3px rgba(26,58,92,0.13);
            outline: none;
        }
        .modal-body textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        .modal-body .row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .modal-body .row-2 .form-control {
            margin-bottom: 0;
        }
        .modal-body .row-2-wrap {
            margin-bottom: 14px;
        }
        .platform-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 6px;
            min-height: 28px;
        }
        .platform-tag {
            background: #1a3a5c;
            color: #fff;
            border-radius: 20px;
            padding: 3px 10px 3px 12px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .platform-tag .rm {
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
            opacity: 0.75;
        }
        .platform-tag .rm:hover { opacity: 1; }
        .platform-input-wrap {
            display: flex;
            gap: 8px;
        }
        .platform-input-wrap .form-control {
            margin-bottom: 0;
            flex: 1;
        }
        .btn-add-platform {
            background: #1a3a5c;
            border: none;
            color: #fff;
            border-radius: 8px;
            padding: 0 14px;
            font-size: 1.1rem;
            cursor: pointer;
            flex-shrink: 0;
            transition: opacity 0.2s;
        }
        .btn-add-platform:hover { opacity: 0.8; }
        .modal-footer-btns {
            padding: 16px 28px 22px;
            display: flex;
            gap: 10px;
            border-top: 1px solid #f0f0f0;
        }
        .btn-cancel {
            flex: 1;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            color: #374151;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-cancel:hover { background: #e5e7eb; }
        .btn-submit-modal {
            flex: 2;
            background: linear-gradient(135deg, #1a3a5c 0%, #0d2137 100%);
            border: none;
            color: #fff;
            font-weight: 700;
            font-size: 0.92rem;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: opacity 0.2s;
            letter-spacing: 0.3px;
        }
        .btn-submit-modal:hover { opacity: 0.87; }
        .success-msg {
            display: none;
            text-align: center;
            padding: 28px 24px;
        }
        .success-msg .s-icon {
            width: 56px; height: 56px;
            background: #ecfdf5;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
        }
        .success-msg .s-icon svg { width: 28px; height: 28px; fill: #059669; }
        .success-msg h5 { color: #1a3a5c; font-weight: 700; margin-bottom: 8px; }
        .success-msg p  { color: #6b7280; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <img src="assets/images/logo/logo.png" alt="Fund Recovery Services">
                <p class="header-title">Neukundenregistrierung</p>
                <p class="header-subtitle">Professionelle Rückforderungsdienste</p>
            </div>
            <div class="register-body">
                <div class="notice-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                </div>

                <h2 class="notice-title">Registrierung vorübergehend deaktiviert</h2>

                <p class="notice-text">
                    Wir entschuldigen uns für die Unannehmlichkeiten. Aufgrund der außergewöhnlich hohen
                    <strong>Anzahl neuer Kundenanfragen</strong> haben wir die direkte Online-Registrierung
                    vorübergehend ausgesetzt, um sicherzustellen, dass jeder Fall die gebührende Aufmerksamkeit
                    erhält.<br><br>
                    Bitte füllen Sie unser <strong>Anfrageformular</strong> aus — ein unserer
                    Rückforderungsspezialisten wird Sie innerhalb von <strong>24 Stunden</strong> kontaktieren.
                </p>

                <div class="steps">
                    <h6>So funktioniert es</h6>
                    <div class="step-item">
                        <span class="step-number">1</span>
                        <span>Füllen Sie das Anfrageformular mit Ihren Falldaten aus.</span>
                    </div>
                    <div class="step-item">
                        <span class="step-number">2</span>
                        <span>Unser Team prüft Ihre Anfrage und bewertet Ihre Rückforderungsmöglichkeiten.</span>
                    </div>
                    <div class="step-item">
                        <span class="step-number">3</span>
                        <span>Ein Spezialist kontaktiert Sie innerhalb von <strong>24 Stunden</strong>, um die nächsten Schritte zu besprechen.</span>
                    </div>
                </div>

                <button type="button" class="btn-request" onclick="openModal()">Anfrageformular ausfüllen</button>
            </div>
            <div class="register-footer">
                Bereits ein Konto? &nbsp;<a href="login.php" style="color:#1a3a5c; font-weight:600; text-decoration:none;">Anmelden</a>
                &nbsp;&middot;&nbsp; <a href="../contact.php" style="color:#9ca3af; text-decoration:none;">Support kontaktieren</a>
            </div>
        </div>
    </div>

    <!-- ── Contact Request Modal ── -->
    <div class="modal-overlay" id="requestModal">
        <div class="modal-box">
            <div class="modal-header">
                <h5>&#128196; Kundenanfrageformular</h5>
                <button class="modal-close" onclick="closeModal()" aria-label="Schließen">&times;</button>
            </div>

            <!-- Form content -->
            <div id="modalFormContent">
                <div class="modal-body">

                    <!-- Name row -->
                    <div class="row-2-wrap">
                        <div class="row-2">
                            <div>
                                <label for="rf_first_name">Vorname <span style="color:#e02020">*</span></label>
                                <input type="text" class="form-control" id="rf_first_name" placeholder="Max" required>
                            </div>
                            <div>
                                <label for="rf_last_name">Nachname <span style="color:#e02020">*</span></label>
                                <input type="text" class="form-control" id="rf_last_name" placeholder="Mustermann" required>
                            </div>
                        </div>
                    </div>

                    <!-- Email -->
                    <label for="rf_email">E-Mail-Adresse <span style="color:#e02020">*</span></label>
                    <input type="email" class="form-control" id="rf_email" placeholder="ihre@email.de" required>

                    <!-- Phone -->
                    <label for="rf_phone">Telefonnummer <span style="color:#e02020">*</span></label>
                    <input type="tel" class="form-control" id="rf_phone" placeholder="+49 123 456789" required>

                    <!-- Loss amount range -->
                    <label for="rf_amount">Geschätzter Verlustbetrag <span style="color:#e02020">*</span></label>
                    <select class="form-control" id="rf_amount" required>
                        <option value="" disabled selected>Bitte auswählen …</option>
                        <option value="5000-20000">5.000 € – 20.000 €</option>
                        <option value="20000-50000">20.000 € – 50.000 €</option>
                        <option value="50000-100000">50.000 € – 100.000 €</option>
                        <option value="100000-250000">100.000 € – 250.000 €</option>
                        <option value="250000-500000">250.000 € – 500.000 €</option>
                        <option value="500000+">500.000 € und mehr</option>
                    </select>

                    <!-- Year of loss -->
                    <label for="rf_year">Jahr des Verlusts <span style="color:#e02020">*</span></label>
                    <select class="form-control" id="rf_year" required>
                        <option value="" disabled selected>Jahr auswählen …</option>
                        <?php for ($y = 2026; $y >= 2000; $y--): ?>
                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>

                    <!-- Platforms -->
                    <label>Plattformen / Anbieter <span style="color:#e02020">*</span></label>
                    <div class="platform-list" id="platformList"></div>
                    <div class="platform-input-wrap" style="margin-bottom:14px;">
                        <input type="text" class="form-control" id="rf_platform_input"
                               placeholder="z.B. Binance, eToro, MetaTrader …"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();addPlatform();}">
                        <button type="button" class="btn-add-platform" onclick="addPlatform()" title="Hinzufügen">+</button>
                    </div>
                    <input type="hidden" id="rf_platforms">

                    <!-- Details -->
                    <label for="rf_details">Fallbeschreibung / Details <span style="color:#e02020">*</span></label>
                    <textarea class="form-control" id="rf_details" rows="4"
                              placeholder="Bitte beschreiben Sie kurz, wie es zu dem Verlust gekommen ist, welche Schritte Sie bereits unternommen haben und alle weiteren relevanten Informationen …"></textarea>

                    <p id="rf_error" style="color:#dc2626; font-size:0.82rem; margin-top:-8px; display:none;">
                        Bitte füllen Sie alle Pflichtfelder aus.
                    </p>
                </div>
                <div class="modal-footer-btns">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Abbrechen</button>
                    <button type="button" class="btn-submit-modal" onclick="submitRequest()">Anfrage absenden &rarr;</button>
                </div>
            </div>

            <!-- Success message -->
            <div class="success-msg" id="modalSuccess">
                <div class="s-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                </div>
                <h5>Anfrage erfolgreich gesendet!</h5>
                <p>Vielen Dank für Ihre Anfrage. Eines unserer Teammitglieder wird sich innerhalb von <strong>24 Stunden</strong> bei Ihnen melden.</p>
                <button type="button" class="btn-request" style="max-width:220px; margin: 18px auto 0;" onclick="closeModal()">Schließen</button>
            </div>
        </div>
    </div>

    <script>
        var platforms = [];

        function openModal() {
            document.getElementById('requestModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            document.getElementById('requestModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close on backdrop click
        document.getElementById('requestModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        function addPlatform() {
            var inp = document.getElementById('rf_platform_input');
            var val = inp.value.trim();
            if (!val) { inp.value = ''; return; }
            // Case-insensitive duplicate check
            var valLower = val.toLowerCase();
            if (platforms.some(function(p){ return p.toLowerCase() === valLower; })) {
                inp.value = '';
                return;
            }
            platforms.push(val);
            renderPlatforms();
            inp.value = '';
        }

        function removePlatform(idx) {
            platforms.splice(idx, 1);
            renderPlatforms();
        }

        function renderPlatforms() {
            var list = document.getElementById('platformList');
            list.innerHTML = '';
            platforms.forEach(function(p, i) {
                var tag = document.createElement('span');
                tag.className = 'platform-tag';
                tag.innerHTML = p + '<span class="rm" onclick="removePlatform(' + i + ')">&times;</span>';
                list.appendChild(tag);
            });
            document.getElementById('rf_platforms').value = platforms.join(', ');
        }

        function submitRequest() {
            var firstName = document.getElementById('rf_first_name').value.trim();
            var lastName  = document.getElementById('rf_last_name').value.trim();
            var email     = document.getElementById('rf_email').value.trim();
            var phone     = document.getElementById('rf_phone').value.trim();
            var amount    = document.getElementById('rf_amount').value;
            var year      = document.getElementById('rf_year').value;
            var details   = document.getElementById('rf_details').value.trim();
            var errEl     = document.getElementById('rf_error');

            if (!firstName || !lastName || !email || !phone || !amount || !year || platforms.length === 0 || !details) {
                errEl.style.display = 'block';
                return;
            }
            errEl.style.display = 'none';

            // Build form data and POST to contact request handler
            var fd = new FormData();
            fd.append('first_name', firstName);
            fd.append('last_name',  lastName);
            fd.append('email',      email);
            fd.append('phone',      phone);
            fd.append('amount',     amount);
            fd.append('year',       year);
            fd.append('platforms',  platforms.join(', '));
            fd.append('details',    details);

            // POST to contact request handler; show success only on 2xx response
            var submitBtn = document.querySelector('.btn-submit-modal');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Wird gesendet …';

            fetch('../ajax/contact_request.php', { method: 'POST', body: fd })
                .then(function(resp) {
                    if (resp.ok) {
                        document.getElementById('modalFormContent').style.display = 'none';
                        document.getElementById('modalSuccess').style.display = 'block';
                    } else {
                        errEl.textContent = 'Fehler beim Senden. Bitte versuchen Sie es erneut.';
                        errEl.style.display = 'block';
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Anfrage absenden →';
                    }
                })
                .catch(function() {
                    errEl.textContent = 'Netzwerkfehler. Bitte prüfen Sie Ihre Verbindung und versuchen Sie es erneut.';
                    errEl.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Anfrage absenden →';
                });
        }
    </script>
</body>
</html>

