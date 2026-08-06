<?php
if (!isset($siteSettings)) {
    include_once __DIR__ . '/site_settings.php';
}
$_brand  = htmlspecialchars($siteSettings['brand_name'] ?? 'Novalnet AI', ENT_QUOTES, 'UTF-8');
$_title  = isset($page_title)       ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8')       : $_brand;
$_desc   = isset($page_description) ? htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') : '';
$_kw     = isset($page_keywords)    ? htmlspecialchars($page_keywords, ENT_QUOTES, 'UTF-8')    : '';
$_url    = isset($page_url)         ? htmlspecialchars($page_url, ENT_QUOTES, 'UTF-8')         : '';
$_ogImage = !empty($siteSettings['logo_url']) ? htmlspecialchars($siteSettings['logo_url'], ENT_QUOTES, 'UTF-8') : '';
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $_title; ?></title>
  <?php if ($_desc): ?><meta name="description" content="<?php echo $_desc; ?>"><?php endif; ?>
  <?php if ($_kw):   ?><meta name="keywords"    content="<?php echo $_kw; ?>"><?php endif; ?>
  <?php if ($_url):  ?><link rel="canonical"    href="<?php echo $_url; ?>"><?php endif; ?>
  <meta property="og:title"       content="<?php echo $_title; ?>"/>
  <meta property="og:description" content="<?php echo $_desc; ?>"/>
  <?php if ($_url):     ?><meta property="og:url"   content="<?php echo $_url; ?>"><?php endif; ?>
  <?php if ($_ogImage): ?><meta property="og:image" content="<?php echo $_ogImage; ?>"><?php endif; ?>
  <meta property="og:type" content="website"/>
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="/style.css"/>
</head>
<body>
