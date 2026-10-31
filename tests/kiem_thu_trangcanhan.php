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
foreach (['metric-grid', 'Hồ sơ dự kiến', 'Nguyện vọng 1 so với chỉ tiêu', 'Số học sinh theo trường THCS', 'Bảng điều khiển'] as $forbidden) {
    if (str_contains($html, $forbidden)) throw new RuntimeException('Management content leaked into student page: ' . $forbidden);
}
echo "PASS full student dashboard renders profile and personal wishes after including header/sidebar\n";
