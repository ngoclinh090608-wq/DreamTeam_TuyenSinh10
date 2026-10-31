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
