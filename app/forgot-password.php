<?php
require_once 'config.php';
require_once __DIR__ . '/mailer/password_reset_mailer.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate token (valid for 24h)
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at)
                       VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)")
            ->execute([$user['id'], $token, $expires]);

        // Send email
        sendPasswordResetEmail($pdo, $user, $token);

        $message = '<div class="alert alert-success">Ein Link zum Zurücksetzen Ihres Passworts wurde an Ihre E-Mail-Adresse gesendet.</div>';
    } else {
        $message = '<div class="alert alert-danger">Keine Benutzer mit dieser E-Mail-Adresse gefunden.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort vergessen | Fund Recovery Services</title>
    <link href="assets/css/app.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0d1b2a 0%, #1b2a3b 60%, #102030 100%);
            min-height: 100vh;
        }
        .forgot-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .forgot-card {
            border-radius: 12px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255,255,255,0.07);
            background: #ffffff;
            max-width: 460px;
            width: 100%;
        }
        .forgot-header {
            background: linear-gradient(135deg, #1a3a5c 0%, #0d2137 100%);
            border-radius: 12px 12px 0 0;
            padding: 28px 32px 22px;
            text-align: center;
        }
        .forgot-header img {
            height: 56px;
            margin-bottom: 14px;
        }
        .forgot-header .header-title {
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .forgot-header .header-subtitle {
            color: rgba(255,255,255,0.65);
            font-size: 0.82rem;
            margin-top: 4px;
        }
        .forgot-body {
            padding: 30px 32px 28px;
        }
        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.9rem;
            color: #111827;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #1a3a5c;
            box-shadow: 0 0 0 3px rgba(26, 58, 92, 0.15);
            outline: none;
        }
        .btn-submit {
            background: linear-gradient(135deg, #1a3a5c 0%, #0d2137 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 11px;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            transition: opacity 0.2s;
            letter-spacing: 0.3px;
        }
        .btn-submit:hover {
            opacity: 0.88;
        }
        .back-link {
            font-size: 0.82rem;
            color: #1a3a5c;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .forgot-footer {
            border-top: 1px solid #f0f0f0;
            padding: 16px 32px 20px;
            text-align: center;
            font-size: 0.8rem;
            color: #9ca3af;
        }
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 0.78rem;
            color: #6b7280;
            margin-top: 14px;
        }
        .security-badge svg {
            width: 14px;
            height: 14px;
            fill: #6b7280;
        }
        .info-box {
            background: #f0f6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 0.83rem;
            color: #1e40af;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .info-box svg {
            width: 16px;
            height: 16px;
            fill: #3b82f6;
            flex-shrink: 0;
            margin-top: 1px;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <div class="forgot-header">
                <img src="assets/images/logo/logo.png" alt="Fund Recovery Services">
                <p class="header-title">Passwort vergessen?</p>
                <p class="header-subtitle">Wir senden Ihnen einen Link zum Zurücksetzen</p>
            </div>
            <div class="forgot-body">
                <?php if ($message): ?>
                    <?= $message ?>
                <?php else: ?>
                    <div class="info-box">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        Geben Sie Ihre E-Mail-Adresse ein. Wir senden Ihnen einen sicheren Link zum Zurücksetzen Ihres Passworts.
                    </div>
                <?php endif; ?>

                <form method="POST" action="forgot-password.php">
                    <div class="form-group mb-4">
                        <label for="email">E-Mail-Adresse</label>
                        <input type="email" class="form-control" id="email" name="email"
                               required autofocus placeholder="ihre@email.de">
                    </div>

                    <button type="submit" class="btn-submit">Link senden</button>
                </form>

                <div class="text-center mt-3">
                    <a href="login.php" class="back-link">← Zurück zum Login</a>
                </div>

                <div class="security-badge">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                    256-Bit SSL-verschlüsselt &amp; sichere Verbindung
                </div>
            </div>
            <div class="forgot-footer">
                Kein Konto? &nbsp;<a href="register.php" style="color:#1a3a5c; font-weight:600; text-decoration:none;">Zugang anfragen</a>
                &nbsp;&middot;&nbsp; <a href="../contact.php" style="color:#9ca3af; text-decoration:none;">Support kontaktieren</a>
            </div>
        </div>
    </div>
</body>
</html>

