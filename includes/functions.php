<?php
declare(strict_types=1);
function app_config(): array {
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require dirname(__DIR__) . '/config/app.php';
        date_default_timezone_set($cfg['timezone']);
    }
    return $cfg;
}
function e(mixed $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
/** Works both from XAMPP subdirectory and from project's built-in PHP server. */
function base_path(): string {
    $forced = app_config()['base_path'];
    if ($forced !== null && $forced !== '') return '/' . trim($forced, '/');
    $root = realpath(dirname(__DIR__));
    $docroot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    if (!$root || !$docroot) return '';
    $r = strtolower(str_replace('\\','/',$root));
    $d = rtrim(strtolower(str_replace('\\','/',$docroot)),'/');
    if ($r === $d) return '';
    if (str_starts_with($r, $d . '/')) return substr(str_replace('\\','/',$root), strlen($docroot));
    return '';
}
function url(string $path = ''): string {
    $prefix = rtrim(base_path(), '/');
    $clean = ltrim($path, '/');
    return $prefix . '/' . $clean;
}
function demo_mode(): bool { return (bool) app_config()['demo_mode']; }
function ui_lower(string $value): string {
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}
function render_module(string $key): void {
    $modules = require __DIR__ . '/module_config.php';
    if (!isset($modules[$key])) { http_response_code(404); require dirname(__DIR__) . '/404.php'; return; }
    require_once __DIR__ . '/auth.php';
    require_auth();
    $module = $modules[$key];
    require_once dirname(__DIR__) . '/services/live_data.php';
    $liveStats = $liveAssign = null; $live = false;
    $kindNow = $module['kind'] ?? 'table';
    if ($kindNow === 'table') { $rows = live_rows($key); if ($rows !== null) { $module['rows'] = $rows; $live = true; } }
    elseif ($kindNow === 'dashboard' || $kindNow === 'stats') { $liveStats = live_stats(); $live = $liveStats !== null; }
    elseif ($kindNow === 'assignments') { $liveAssign = live_assignments(); $live = $liveAssign !== null; }
    $page_title = $module['title']; $layout = 'dashboard'; $active = $key;
    require __DIR__ . '/header.php';
    require __DIR__ . '/module_page.php';
    require __DIR__ . '/footer.php';
}
