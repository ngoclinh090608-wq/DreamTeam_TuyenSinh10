<?php
declare(strict_types=1);
/** Đăng xuất (chỉ nhận POST có CSRF để trang khác không tự đăng xuất người dùng được). */
require_once dirname(__DIR__) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    dang_xuat();
    flash_set('success', 'Bạn đã đăng xuất.');
}
header('Location: ' . url('auth/login.php'), true, 303);
exit;
