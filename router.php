<?php
// Router for PHP's local development server. Apache uses .htaccess instead.
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
if (preg_match('~(?:^|/)(?:storage|includes|database|node_modules|src|tests|scripts|\.git|\.env|\.htaccess)(?:/|$)~i',$uri) || str_contains($uri,'..') || str_contains($uri,"\0")) {
    http_response_code(404); exit('Não encontrado.');
}
if ($uri === '/api' || $uri === '/api/') { require __DIR__.'/api/index.php'; return true; }
if ($uri === '/admin' || $uri === '/admin/') { require __DIR__.'/admin/index.php'; return true; }
if ($uri === '/') { require __DIR__.'/index.php'; return true; }
$path = __DIR__ . $uri;
if (is_file($path) && (preg_match('~\.php$~',$path) || preg_match('~^/assets/[^.].*\.(?:css|js|mjs|png|jpe?g|webp|svg|woff2?)$~i',$uri))) return false;
http_response_code(404); echo 'Página não encontrada.';
