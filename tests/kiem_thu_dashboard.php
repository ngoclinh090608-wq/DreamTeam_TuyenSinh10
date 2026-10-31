<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/functions.php';
require dirname(__DIR__) . '/includes/permissions.php';
$expected = [
    'HOC_SINH' => ['aspirations/','admission-info/','appeals/','enrollment/'],
    'GVCN' => ['candidates/'],
    'BAN_TUYEN_SINH' => ['candidates/','admission-info/','exam/tao-phong.php','exam/phan-phong.php','exam/phan-cong-ra-de.php','publication/diem-thi.php','councils/','statistics/'],
    'HD_TUYEN_SINH' => ['aspirations/','admission/','enrollment/','publication/tuyen-sinh.php'],
    'HD_COI_THI' => ['assignments/'],
    'HD_CHAM_THI' => ['grading/nhap-diem.php','grading/lich-su.php','grading/cap-nhat.php','grading/thong-ke.php'],
    'CAT_PHACH' => ['papers/'],
    'HD_PHUC_KHAO' => ['appeals/'],
    'HD_RA_DE' => ['exam/phan-cong-ra-de.php'],
    'QUAN_TRI' => ['exam/tao-phong.php','exam/phan-phong.php','exam/phan-cong-ra-de.php'],
];
foreach ($expected as $role => $paths) {
    $actual = array_column(dashboard_quick_links(['vaiTro'=>$role]), 1);
    if ($actual !== $paths) throw new RuntimeException('Unexpected dashboard permissions: ' . $role);
    echo "PASS dashboard role: $role\n";
}
if (dashboard_quick_links(null) !== [] || dashboard_quick_links(['vaiTro'=>'UNKNOWN']) !== []) throw new RuntimeException('Unknown account received links');
if (can_access_module(['vaiTro'=>'HD_RA_DE'], 'assignments')) throw new RuntimeException('Exam authors cannot assign invigilators');
if (!can_access_module(['vaiTro'=>'QUAN_TRI'], 'exam')) throw new RuntimeException('Admin exam navigation missing');
if (school_display_name('Trường THCS Sao Mai (mẫu)') !== 'Trường THCS Sao Mai'
    || school_display_name('Trường THPT Bình Minh') !== 'Trường THPT Bình Minh') throw new RuntimeException('School label mismatch');
echo "PASS unknown roles, module boundaries and school labels\n";

$labels = [
    'Điểm thi demo THCS Sao Mai' => 'Điểm thi THCS Sao Mai',
    'Phòng demo 1-01' => 'Phòng thi 101',
    'Phòng demo 5-13' => 'Phòng thi 513',
    'Lô bài thi DEMO TOAN - phòng RD1P01' => 'Lô bài thi TOAN - phòng RD1P01',
    'Trường THCS Sao Mai (mẫu)' => 'Trường THCS Sao Mai',
    'Phòng 103' => 'Phòng 103',
];
foreach ($labels as $raw=>$expectedLabel) {
    if (catalog_display_name($raw)!==$expectedLabel) throw new RuntimeException('Catalogue display name mismatch');
}
echo "PASS room, exam site and bag display names; existing normal labels preserved\n";
