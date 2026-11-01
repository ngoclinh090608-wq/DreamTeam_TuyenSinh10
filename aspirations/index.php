<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

// Kiểm tra quyền truy cập module aspirations theo QUYEN_MODULE (HOC_SINH, HD_TUYEN_SINH)
$user = require_module('aspirations');

if (($_GET['view'] ?? '') === 'student') {
    // Giao diện quản lý nguyện vọng bổ sung; quyền ghi được kiểm tra theo chủ hồ sơ.
    require __DIR__ . '/student_aspirations.php';
} elseif (has_role($user, ['HOC_SINH'])) {
    // Giao diện học sinh: Đăng ký tuyển sinh lớp 10 và Quản lý nguyện vọng NV1, NV2, NV3
    require __DIR__ . '/hoc_sinh_dang_ky.php';
} else {
    // Giao diện Hội đồng tuyển sinh: Tra cứu danh sách nguyện vọng toàn hệ thống
    $directoryModule = 'aspirations';
    require dirname(__DIR__) . '/includes/candidate_directory.php';
}
