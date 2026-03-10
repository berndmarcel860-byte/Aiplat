<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Access | Fund Recovery Services</title>
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
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <img src="assets/images/logo/logo.png" alt="Fund Recovery Services">
                <p class="header-title">New Client Registration</p>
                <p class="header-subtitle">Professional Fund Recovery Services</p>
            </div>
            <div class="register-body">
                <div class="notice-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                </div>

                <h2 class="notice-title">Registration Temporarily Unavailable</h2>

                <p class="notice-text">
                    We apologise for the inconvenience. Due to an exceptionally <strong>high volume of new client requests</strong>,
                    we have temporarily suspended direct online registration to ensure every case receives the dedicated attention it deserves.<br><br>
                    Please complete our <strong>Client Request Form</strong> and one of our recovery specialists
                    will contact you within <strong>24 hours</strong>.
                </p>

                <div class="steps">
                    <h6>How it works</h6>
                    <div class="step-item">
                        <span class="step-number">1</span>
                        <span>Fill in the Client Request Form with your case details.</span>
                    </div>
                    <div class="step-item">
                        <span class="step-number">2</span>
                        <span>Our team reviews your request and assesses your recovery options.</span>
                    </div>
                    <div class="step-item">
                        <span class="step-number">3</span>
                        <span>A specialist contacts you within <strong>24 hours</strong> to discuss next steps.</span>
                    </div>
                </div>

                <a href="../contact.php" class="btn-request">Submit a Client Request Form</a>
            </div>
            <div class="register-footer">
                Already have an account? &nbsp;<a href="login.php" style="color:#1a3a5c; font-weight:600; text-decoration:none;">Sign In</a>
                &nbsp;&middot;&nbsp; <a href="../contact.php" style="color:#9ca3af; text-decoration:none;">Contact Support</a>
            </div>
        </div>
    </div>
</body>
</html>
