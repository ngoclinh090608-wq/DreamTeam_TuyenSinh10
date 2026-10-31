<?php
declare(strict_types=1);
putenv('APP_BASE_PATH=/DreamTeam_TuyenSinh10');
require dirname(__DIR__) . '/includes/functions.php';
$tests = [
    [url('grading/nhap-diem.php'), '/DreamTeam_TuyenSinh10/grading/nhap-diem.php'],
    [url('grading/nhap-diem.php', ['tuiBai' => 'LO_P101_NN', 'download' => '1']), '/DreamTeam_TuyenSinh10/grading/nhap-diem.php?tuiBai=LO_P101_NN&download=1'],
    [url('page.php?x=1#diem', ['phong' => 'Phòng 1']), '/DreamTeam_TuyenSinh10/page.php?x=1&phong=Ph%C3%B2ng%201#diem'],
];
foreach ($tests as [$actual, $expected]) {
    if ($actual !== $expected) throw new RuntimeException('URL mismatch: ' . $actual);
}
echo "PASS: query parameters, existing queries, anchors and XAMPP subdirectory URLs\n";
