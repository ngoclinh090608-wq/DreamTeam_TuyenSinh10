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
/** Format existing catalogue labels without changing stored records or keys. */
function catalog_display_name(string $name): string {
    $name = preg_replace('/\s*\(mẫu\)\s*$/iu', '', $name) ?? $name;
    $name = preg_replace('/Phòng\s+demo\s+(\d+)-(\d{2})/iu', 'Phòng thi $1$2', $name) ?? $name;
    $name = preg_replace('/\bdemo\b\s*/iu', '', $name) ?? $name;
    return trim(preg_replace('/[ \t]{2,}/u', ' ', $name) ?? $name);
}
function school_display_name(string $name): string {
    return catalog_display_name($name);
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
function url(string $path = '', array $query = []): string {
    $prefix = rtrim(base_path(), '/');
    $clean = ltrim($path, '/');
    $target = $prefix . '/' . $clean;
    if (!$query) return $target;
    $parts = explode('#', $target, 2);
    $parts[0] .= (str_contains($parts[0], '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    return $parts[0] . (isset($parts[1]) ? '#' . $parts[1] : '');
}
function demo_mode(): bool { return (bool) app_config()['demo_mode']; }
function ui_lower(string $value): string {
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}
function render_module(string $key): void {
    $modules = require __DIR__ . '/module_config.php';
    if (!isset($modules[$key])) { http_response_code(404); require dirname(__DIR__) . '/404.php'; return; }
    require_once __DIR__ . '/auth.php';
    $user = require_module($key); // authenticated account is the only source of ownership
    $module = $modules[$key];
    $isStudent = has_role($user, ['HOC_SINH']);
    $studentId = $isStudent ? (string)($user['maThiSinh'] ?? '') : null;
    $studentDashboard = null; $admissionNotices = null;
    $admissionSearch = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 120) : '';
    require_once dirname(__DIR__) . '/services/live_data.php';
    $module['rows'] = []; // Never retain illustrative rows after a database failure.
    $liveStats = $liveAssign = null; $live = false;
    $kindNow = $module['kind'] ?? 'table';
    if ($isStudent && $key === 'admin') {
        $studentDashboard = live_student_dashboard($studentId);
        $live = $studentDashboard !== null;
        $module['title'] = 'Trang cá nhân'; $module['eyebrow'] = 'Hồ sơ tuyển sinh';
        $module['desc'] = 'Theo dõi hồ sơ và nguyện vọng của bạn.';
    }
    elseif ($isStudent && $key === 'admission-info') {
        $admissionNotices = live_student_admission_info($admissionSearch);
        $live = $admissionNotices !== null;
    }
    elseif ($kindNow === 'table') { $rows = live_rows($key, $studentId); if ($rows !== null) { $module['rows'] = $rows; $live = true; } }
    elseif ($kindNow === 'dashboard' || $kindNow === 'stats') { $liveStats = live_stats(); $live = $liveStats !== null; }
    elseif ($kindNow === 'assignments') { $liveAssign = live_assignments(); $live = $liveAssign !== null; }
    if ($isStudent && $key === 'admission-info') $module['desc'] = 'Xem thông tin tuyển sinh và chỉ tiêu đã công bố.';
    if ($isStudent && $key === 'appeals') { $module['title'] = 'Phúc khảo của tôi'; $module['desc'] = 'Theo dõi các yêu cầu phúc khảo của bạn.'; }
    if ($isStudent && $key === 'enrollment') { $module['title'] = 'Tình trạng nhập học'; $module['desc'] = 'Xem tình trạng nhập học gắn với hồ sơ của bạn.'; }
    $page_title = $module['title']; $layout = 'dashboard'; $active = $key;
    $moduleTemplate = $isStudent && $key === 'admin' ? 'student_dashboard.php'
        : ($isStudent && $key === 'admission-info' ? 'student_admission_info.php' : 'module_page.php');
    require __DIR__ . '/header.php';
    require __DIR__ . '/' . $moduleTemplate;
    require __DIR__ . '/footer.php';
}
