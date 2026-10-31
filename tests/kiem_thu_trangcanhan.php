<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/auth.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
$user = db()->query("SELECT " . tk_cot_phien() . " FROM TaiKhoan WHERE vaiTro='HOC_SINH' AND maThiSinh IS NOT NULL LIMIT 1")->fetch();
if (!$user) throw new RuntimeException('Student fixture missing');
$st = db()->prepare('SELECT maHoSo FROM ThiSinh WHERE maThiSinh=?');
$st->execute([$user['maThiSinh']]); $fileId = $st->fetchColumn();
app_session_start();
$_SESSION['user'] = $user; $_SESSION['last_seen'] = time();
ob_start(); render_module('admin'); $html = ob_get_clean();
foreach (['Thông tin của tôi', 'Nguyện vọng của tôi', (string)$fileId] as $required) {
    if (!str_contains($html, $required)) throw new RuntimeException('Personal content missing: ' . $required);
}
foreach (['class="sidebar"', 'Mở menu quản trị', 'metric-grid', 'Hồ sơ dự kiến', 'Nguyện vọng 1 so với chỉ tiêu', 'Số học sinh theo trường THCS', 'Bảng điều khiển'] as $forbidden) {
    if (str_contains($html, $forbidden)) throw new RuntimeException('Management content leaked into student page: ' . $forbidden);
}
foreach (['student-site-header', 'student-portal-nav', 'Hồ sơ tuyển sinh', 'Văn bản', 'Hướng dẫn', 'Đổi mật khẩu', 'Đăng xuất', 'csrf_token'] as $required) {
    if (!str_contains($html, $required)) throw new RuntimeException('Student portal shell missing: ' . $required);
}
echo "PASS student portal shell, account actions and personal dashboard without management sidebar\n";

$dom = new DOMDocument(); libxml_use_internal_errors(true); $dom->loadHTML($html);
$xpath = new DOMXPath($dom); $tabPaths = [];
foreach ($xpath->query('//nav[@class="student-portal-nav"]/a') as $tab) $tabPaths[] = $tab->getAttribute('href');
if ($tabPaths !== [url('admin/'),url('admission-info/huong-dan.php'),url('admission-info/')]) throw new RuntimeException('Student must have exactly three navigation tabs');
if (str_contains($html, 'class="quick-links"')) throw new RuntimeException('Extra student dashboard menu remains');
echo "PASS exactly three tabs; preferences, published scores and status stay inside personal profile\n";

foreach (['student-score-dialog','student-exam-dialog'] as $dialogId) {
    if ($xpath->query('//dialog[@id="'.$dialogId.'"]')->length!==1 || $xpath->query('//button[@data-student-dialog-open="'.$dialogId.'"]')->length!==1) throw new RuntimeException('Modal or trigger missing: '.$dialogId);
}
if ($xpath->query('//dialog/button[@data-student-dialog-close] | //dialog/header/button[@data-student-dialog-close]')->length!==2) throw new RuntimeException('Modal close actions missing');
echo "PASS score and exam dialogs, triggers, accessible headings and close controls\n";
