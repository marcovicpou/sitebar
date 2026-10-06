<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
function render_app(string $page = 'home', bool $admin = false): void {
    global $site;
    $base = app_base();
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    $titles = ['home'=>'Seu ponto de encontro','menu'=>'Cardápio','events'=>'Agenda','event'=>'Evento','gallery'=>'Galeria','about'=>'Nossa história','contact'=>'Contato','admin'=>'Painel administrativo'];
    $title = ($titles[$page] ?? 'Encontro dos Amigos') . ' — ' . $site['nome'];
    $manifestPath = cms_root().'/assets/build/.vite/manifest.json';
    $manifest = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath),true) : [];
    $entry = $manifest['src/main.tsx'] ?? null;
    $data = ['base'=>$base,'page'=>$page,'admin'=>$admin,'settings'=>$site,'menu'=>cms_active('menu'),'events'=>cms_active('events'),'gallery'=>cms_active('gallery')];
    if ($page === 'event') {
        $data['eventId'] = (string)($_GET['id']??'');
        $event = cms_find('events',$data['eventId']);
        if (!$event || empty($event['ativo'])) http_response_code(404);
    }
    $logo = $site['logo_image'] ?? 'assets/img/logo.png';
    if (!preg_match('~^assets/(img|uploads)/[a-zA-Z0-9_.-]+\.(png|jpe?g|webp)$~',$logo)) $logo='assets/img/logo.png';
    if ($logo === 'assets/img/logo.png') $logo = 'assets/img/logo.webp';
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#203c2b">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($site['seo_description']) ?>">
<?php if ($admin): ?><meta name="robots" content="noindex,nofollow"><?php endif; ?>
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($site['seo_description']) ?>">
<meta property="og:type" content="website">
<link rel="icon" href="<?= e($base . '/' . $logo) ?>">
<?php foreach (($entry['css']??[]) as $css): ?><link rel="stylesheet" href="<?= e($base.'/assets/build/'.$css) ?>"><?php endforeach; ?>
</head>
<body>
<script id="app-data" type="application/json"><?= app_json($data) ?></script>
<div id="root"><div style="padding:40px;font-family:system-ui;background:#f7f4ed;color:#203c2b"><h1><?= e($site['nome']) ?></h1><p><?= $entry ? 'Preparando seu próximo encontro…' : 'Execute npm ci e npm run build para preparar o site.' ?></p><noscript>Ative o JavaScript para navegar pelo cardápio e pela agenda.</noscript></div></div>
<?php if ($entry): ?><script type="module" src="<?= e($base.'/assets/build/'.$entry['file']) ?>"></script><?php endif; ?>
</body></html>
<?php }
