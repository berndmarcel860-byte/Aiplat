<?php
require_once 'config.php';

$message = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Validate token and check expiration
    $stmt = $pdo->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset) {
        // ✅ FIX: Update the correct column name (password_hash)
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
            ->execute([$newPassword, $reset['user_id']]);

        // Delete used token
        $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

        $message = '<div class="alert alert-success">Passwort erfolgreich geändert. <a href="login.php">Jetzt anmelden</a></div>';
    } else {
        $message = '<div class="alert alert-danger">Ungültiger oder abgelaufener Link.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort zurücksetzen | Fund Recovery Services</title>
    <link href="assets/css/app.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0d1b2a 0%, #1b2a3b 60%, #102030 100%);
            min-height: 100vh;
        }
        .reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .reset-card {
            border-radius: 12px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255,255,255,0.07);
            background: #ffffff;
            max-width: 460px;
            width: 100%;
        }
        .reset-header {
            background: linear-gradient(135deg, #1a3a5c 0%, #0d2137 100%);
            border-radius: 12px 12px 0 0;
            padding: 28px 32px 22px;
            text-align: center;
        }
        .lock-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
        }
        .lock-icon svg {
            width: 30px;
            height: 30px;
            fill: #ffffff;
        }
        .reset-header .header-title {
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .reset-header .header-subtitle {
            color: rgba(255,255,255,0.65);
            font-size: 0.82rem;
            margin-top: 4px;
        }
        .reset-body {
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
        .reset-footer {
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
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <div class="lock-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                </div>
                <p class="header-title">Neues Passwort festlegen</p>
                <p class="header-subtitle">Bitte wählen Sie ein sicheres neues Passwort</p>
            </div>
            <div class="reset-body">
                <?php if ($message): ?>
                    <?= $message ?>
                    <div class="text-center mt-3">
                        <a href="login.php" class="back-link">← Zurück zum Login</a>
                    </div>
                <?php endif; ?>

                <?php if(empty($message) || str_contains($message,'Ungültig')===false): ?>
                <form method="POST" action="reset-password.php">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="form-group mb-4">
                        <label for="password">Neues Passwort</label>
                        <input type="password" class="form-control" id="password" name="password"
                               required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn-submit">Passwort ändern</button>
                </form>

                <div class="text-center mt-3">
                    <a href="login.php" class="back-link">← Zurück zum Login</a>
                </div>
                <?php endif; ?>

                <div class="security-badge">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                    256-Bit SSL-verschlüsselt &amp; sichere Verbindung
                </div>
            </div>
            <div class="reset-footer">
                <a href="login.php" style="color:#9ca3af; text-decoration:none;">Anmelden</a>
                &nbsp;&middot;&nbsp; <a href="../contact.php" style="color:#9ca3af; text-decoration:none;">Support kontaktieren</a>
            </div>
        </div>
    </div>
</body>
</html>

