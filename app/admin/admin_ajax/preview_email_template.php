<?php
header('Content-Type: text/html; charset=utf-8');

$subject = $_POST['subject'] ?? '(No Subject)';
$content = $_POST['content'] ?? '';
$variables = $_POST['variables'] ?? '[]';

// Replace variables with mock data
$vars = preg_split('/[,\s]+/', $variables);
foreach ($vars as $v) {
    $content = str_replace('{{' . trim($v) . '}}', ucfirst($v) . ' Beispiel', $content);
}

echo <<<HTML
<!DOCTYPE html>
<html><head>
<meta charset="utf-8">
<title>$subject</title>
<style>
body{font-family:Arial,sans-serif;background:#f9f9f9;color:#333}
.container{max-width:700px;margin:40px auto;background:white;border-radius:8px;box-shadow:0 3px 10px rgba(0,0,0,0.1)}
.header{background:linear-gradient(90deg,#2950a8 0,#2da9e3 100%);color:white;padding:20px;text-align:center}
.content{padding:30px;line-height:1.6}
.footer{background:#f1f1f1;text-align:center;padding:15px;font-size:13px;color:#777}
.button{background:#2da9e3;color:white;padding:10px 20px;border-radius:5px;text-decoration:none}
</style>
</head><body>
<div class="container">
<div class="header"><h2>$subject</h2></div>
<div class="content">$content</div>
<div class="footer">© 2025 ScamRecovery. Alle Rechte vorbehalten.</div>
</div>
</body></html>
HTML;

