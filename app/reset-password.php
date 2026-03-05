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
<title>Passwort zurücksetzen | Scam Recovery Dashboard</title>
<link href="assets/css/app.min.css" rel="stylesheet">
<style>
.login-container{min-height:100vh;background:linear-gradient(135deg,#f5f7fa,#c3cfe2);}
.login-card{border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.1);}
.login-logo{text-align:center;margin-bottom:30px;}
.login-logo img{height:60px;}
.btn-primary{background:#5c6bc0;border-color:#5c6bc0;}
</style>
</head>
<body>
<div class="login-container d-flex align-items-center">
<div class="container">
<div class="row justify-content-center">
<div class="col-md-5">
<div class="card login-card">
<div class="card-body p-4">
<div class="login-logo"><img src="assets/images/logo/logo.png" alt="Scam Recovery"></div>
<h4 class="text-center mb-4">Neues Passwort festlegen</h4>
<?= $message ?>
<?php if(empty($message) || str_contains($message,'Ungültig')===false): ?>
<form method="POST">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <div class="form-group">
        <label>Neues Passwort</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block mt-3">Passwort ändern</button>
</form>
<?php endif; ?>
</div></div></div></div></div></div>
</body>
</html>

