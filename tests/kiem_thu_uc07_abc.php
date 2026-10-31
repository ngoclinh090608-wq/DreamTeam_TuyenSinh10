<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/services/exam_service.php';
function assert_abc(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS: $message\n";
}
$pdo = db();
$pdo->beginTransaction();
try {
    $pdo->exec("UPDATE DiemThi SET maTruongTHPT='THPT_1' WHERE maDiemThi='DT01'; UPDATE DiemThi SET maTruongTHPT='THPT_2' WHERE maDiemThi='DT02'");
    $pdo->exec("UPDATE NguyenVong SET maTruongTHPT='THPT_2' WHERE thuTuNguyenVong=1");
    $pdo->exec("UPDATE NguyenVong SET maTruongTHPT='THPT_1' WHERE thuTuNguyenVong=1 AND maThiSinh IN ('TS001','TS002','TS005','TS006','TS009','TS010')");
    $candidates = layDanhSachThiSinhPhuHopDiemThi('KT2026', 'DT01');
    assert_abc(count($candidates) === 6, 'Use six eligible seed candidates');
    // Reversed names span both schools, exposing the previous school-first/full-name ordering.
    $expected = ['Nguyễn Văn An', 'Trần Ngọc An', 'Lê Thị Bình', 'Phạm Văn Đạt', 'Đỗ Minh Hà', 'Bùi Văn Yến'];
    $update = $pdo->prepare('UPDATE ThiSinh SET hoTen = ? WHERE maThiSinh = ?');
    foreach ($candidates as $i => $candidate) $update->execute([$expected[5 - $i], $candidate['maThiSinh']]);
    $actual = layDanhSachThiSinhPhuHopDiemThi('KT2026', 'DT01');
    assert_abc(array_column($actual, 'hoTen') === $expected, 'Vietnamese given name first, family/middle names break ties across schools');
    $preview = kiemTraVaTinhToanPhanPhong('KT2026', 'DT01');
    $blocked = $preview['phanBo']['P101']['chiemCho'];
    $pdo->prepare('UPDATE PhongThi SET sucChua = ? WHERE maPhong = ?')->execute([$blocked + 2, 'P101']);
    $preview = kiemTraVaTinhToanPhanPhong('KT2026', 'DT01');
    assert_abc(array_column($preview['phanBo']['P101']['thiSinh'], 'hoTen') === array_slice($expected, 0, 2), 'First room receives first two names after reserving occupied seats');
    assert_abc(array_column($preview['phanBo']['P102']['thiSinh'], 'hoTen') === array_slice($expected, 2), 'Remaining ABC names continue in next room');
    foreach ($preview['phanBo'] as $room) assert_abc($room['soLuong'] + $room['chiemCho'] <= $room['sucChua'], 'Room capacity is respected');
    $pdo->prepare('UPDATE PhongThi SET sucChua = ? WHERE maPhong = ?')->execute([$blocked + 12, 'P101']);
    $fullFirst = kiemTraVaTinhToanPhanPhong('KT2026', 'DT01');
    assert_abc($fullFirst['phanBo']['P101']['soLuong'] === 6 && $fullFirst['phanBo']['P102']['soLuong'] === 0, 'Do not use a later room while the first has capacity');
    $pdo->exec("UPDATE NguyenVong SET maTruongTHPT='THPT_2' WHERE maThiSinh='TS001' AND thuTuNguyenVong=1");
    assert_abc(!in_array('TS001', array_column(layDanhSachThiSinhPhuHopDiemThi('KT2026', 'DT01'), 'maThiSinh')), 'Same middle-school area does not override NV1');
    $pdo->exec("UPDATE NguyenVong SET maTruongTHPT='THPT_1' WHERE maThiSinh IN ('TS001','TS004') AND thuTuNguyenVong=1");
    assert_abc(in_array('TS004', array_column(layDanhSachThiSinhPhuHopDiemThi('KT2026', 'DT01'), 'maThiSinh')), 'NV1 admits candidate from another middle-school area and corrects wrong-school assignment');
    $pdo->exec("UPDATE NguyenVong SET maTruongTHPT='THPT_2' WHERE maThiSinh='TS004' AND thuTuNguyenVong=1");
    $pdo->exec("UPDATE QuyDinhNguyenVong SET namTuyenSinh=2025 WHERE maQuyDinh='QD2026'");
    assert_abc(layDanhSachThiSinhPhuHopDiemThi('KT2026', 'DT01') === [], 'Prior-year registrations are not used');
    $pdo->exec("UPDATE QuyDinhNguyenVong SET namTuyenSinh=2026 WHERE maQuyDinh='QD2026'");
    $pdo->exec("UPDATE DiemThi SET maTruongTHPT=NULL WHERE maDiemThi='DT01'");
    try { layDanhSachThiSinhPhuHopDiemThi('KT2026', 'DT01'); throw new RuntimeException('Missing school link was accepted'); }
    catch (ExamException $e) { assert_abc(str_contains(implode(' ', $e->details()), 'chưa được gắn'), 'Missing school link is reported instead of falling back to area'); }
    $pdo->exec("UPDATE DiemThi SET maTruongTHPT='THPT_1' WHERE maDiemThi='DT01'");
    $orderedIds = array_column($actual, 'maThiSinh');
    $update->execute(['  Trần   Văn An  ', $orderedIds[0]]);
    $update->execute(['Nguyễn Văn An', $orderedIds[1]]);
    $actual = layDanhSachThiSinhPhuHopDiemThi('KT2026', 'DT01');
    assert_abc($actual[0]['maThiSinh'] === $orderedIds[1] && $actual[1]['maThiSinh'] === $orderedIds[0], 'Whitespace is normalized for sorting and family name breaks equal given names');
    $update->execute(['Nguyễn Văn An', $orderedIds[0]]);
    $actual = layDanhSachThiSinhPhuHopDiemThi('KT2026', 'DT01');
    $ids = [$orderedIds[0], $orderedIds[1]]; sort($ids, SORT_STRING);
    assert_abc(array_slice(array_column($actual, 'maThiSinh'), 0, 2) === $ids, 'Identical names use candidate code for a stable order');
} finally { $pdo->rollBack(); }
echo "UC07 ABC checks passed; seed data restored.\n";
