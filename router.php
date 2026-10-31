<?php
/** Local PHP development router. Start: php -S 127.0.0.1:8080 router.php */
declare(strict_types=1);
$path = rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
if (preg_match('~(?:^|/)(?:config|includes|services|database|storage|tests|docs|\\.git)(?:/|$)~i', $path)
    || preg_match('~(?:^|/)(?:\\.env[^/]*|README\\.md|router\\.php|[^/]+\\.(?:sql|log))$~i', $path)
    || str_contains($path, '..')) {
    http_response_code(403); echo 'Forbidden'; return true;
}
if (preg_match('~(?:^|/)api/v1(?:/|$)~i', $path)) {
    require __DIR__ . '/api/index.php'; return true;
}
$file = __DIR__ . $path;
if (is_file($file)) return false;
if (is_dir($file) && is_file(rtrim($file,'/') . '/index.php')) {
    require rtrim($file,'/') . '/index.php'; return true;
}
http_response_code(404); require __DIR__ . '/404.php';
