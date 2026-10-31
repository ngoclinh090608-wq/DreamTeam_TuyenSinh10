<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/services/exam_service.php';
function check_nv1(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS: $message\n";
}
$pdo = db();
$before = array_column(exam_ds_diem_thi('KT2026'), 'maDiemThi');
$roomsBefore = $pdo->query('SELECT * FROM PhongThi ORDER BY maPhong')->fetchAll();
$roomIdsBefore = array_column($roomsBefore, 'maPhong');
$scores = (int)$pdo->query('SELECT COUNT(*) FROM KetQuaChamThi')->fetchColumn();
try {
    $created = exam_tao_diem_thi_theo_nv1('KT2026');
    $sites = exam_ds_diem_thi('KT2026', true);
    check_nv1(count(array_unique(array_column($sites, 'maTruongTHPT'))) === 5, 'Each NV1 high school has an exam site');
    check_nv1(!in_array('DT01', array_column($sites, 'maDiemThi')), 'THCS-only site is excluded from UC06/07 choices');
    check_nv1(in_array('DT02', array_column($sites, 'maDiemThi')), 'Existing correctly linked high-school site is retained');
    check_nv1($created['diemThi'] === 4, 'Only four missing school sites are created');
    check_nv1($created['phongThi'] >= 4, 'Missing school rooms are created automatically');
    $newRooms = array_filter($pdo->query('SELECT * FROM PhongThi')->fetchAll(), static fn($r) => !in_array($r['maPhong'], $roomIdsBefore, true));
    check_nv1(count($newRooms) === $created['phongThi'] && !array_filter($newRooms, static fn($r) => (int)$r['sucChua'] !== 30), 'Every new room has exactly 30 seats');
    check_nv1(exam_tao_diem_thi_theo_nv1('KT2026') === ['diemThi' => 0, 'phongThi' => 0], 'Repeated setup does not duplicate sites');
    try { kiemTraPhongThi('DT01', 'TESTNV1', 'Phong test', 30); throw new RuntimeException('Legacy site accepted'); }
    catch (ExamException $e) { check_nv1(str_contains(implode(' ', $e->details()), 'chưa được gắn'), 'Reject creating rooms at a THCS-only site'); }
    $originalRooms = array_values(array_filter($pdo->query('SELECT * FROM PhongThi ORDER BY maPhong')->fetchAll(), static fn($r) => in_array($r['maPhong'], $roomIdsBefore, true)));
    check_nv1($originalRooms === $roomsBefore, 'Existing rooms are unchanged');
    foreach ($sites as $site) {
        $preview = kiemTraVaTinhToanPhanPhong('KT2026', $site['maDiemThi']);
        check_nv1($preview['tongSucChua'] >= $preview['tongThiSinh'], 'Prepared school has sufficient seats for automatic allocation');
    }
    check_nv1((int)$pdo->query('SELECT COUNT(*) FROM KetQuaChamThi')->fetchColumn() === $scores, 'Existing grades are unchanged');
} finally {
    $deleteRoom = $pdo->prepare('DELETE FROM PhongThi WHERE maPhong = ?');
    foreach ($pdo->query('SELECT maPhong FROM PhongThi')->fetchAll(PDO::FETCH_COLUMN) as $roomId) {
        if (!in_array($roomId, $roomIdsBefore, true)) $deleteRoom->execute([$roomId]);
    }
    $after = array_column(exam_ds_diem_thi('KT2026'), 'maDiemThi');
    $delete = $pdo->prepare('DELETE FROM DiemThi WHERE maDiemThi = ?');
    foreach (array_diff($after, $before) as $id) $delete->execute([$id]);
}
echo "UC06/07 NV1 site checks passed; original sites restored.\n";
