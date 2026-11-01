<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

$account = require_module('aspirations');
$isStudent = has_role($account, ['HOC_SINH']);

// Nếu là học sinh hoặc cán bộ muốn xem thử giao diện học sinh
if ($isStudent || (isset($_GET['view']) && $_GET['view'] === 'student')) {
    if (!$isStudent && empty($_GET['preview_student'])) {
        $_GET['preview_student'] = 'TSD000001';
    }
    require __DIR__ . '/student_aspirations.php';
    exit;
}

// Đối với cán bộ, hội đồng tuyển sinh, quản trị viên: hiển thị bảng tra cứu toàn bộ thí sinh
$directoryModule = 'aspirations';
require dirname(__DIR__) . '/includes/candidate_directory.php';
