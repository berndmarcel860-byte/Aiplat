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
<title>Passwort vergessen | Scam Recovery Dashboard</title>
<link href="assets/css/app.min.css" rel="stylesheet">
<style>
.login-container{min-height:100vh;background:linear-gradient(135deg,#f5f7fa 0%,#c3cfe2 100%);}
.login-card{border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.1);}
.login-logo{text-align:center;margin-bottom:30px;}
.login-logo img{height:60px;}
.btn-primary{background:#5c6bc0;border-color:#5c6bc0;}
.btn-primary:hover{background:#3f51b5;}
</style>
</head>
<body>
<div class="login-container d-flex align-items-center">
<div class="container">
<div class="row justify-content-center">
<div class="col-md-5">
<div class="card login-card">
<div class="card-body p-4">
<div class="login-logo">
<img src="assets/images/logo/logo.png" alt="Scam Recovery">
</div>
<h4 class="text-center mb-4">Passwort vergessen?</h4>
<?= $message ?>
<form method="POST">
    <div class="form-group">
        <label for="email">E-Mail-Adresse</label>
        <input type="email" class="form-control" name="email" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Link senden</button>
    <div class="text-center mt-3">
        <a href="login.php">Zurück zum Login</a>
    </div>
</form>
</div></div></div></div></div></div>
</body>
</html>

