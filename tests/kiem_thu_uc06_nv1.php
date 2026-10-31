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
    try { exam_tao_diem_thi_theo_nv1('KT2026', ''); throw new RuntimeException('Empty selection accepted'); }
    catch (ExamException $e) { check_nv1(true, 'Require an explicit exam-site selection'); }
    $created = exam_tao_diem_thi_theo_nv1('KT2026', 'truong:THPT_4');
    $target = $created['maDiemThi'];
    check_nv1($created['diemThi'] === 1 && $created['phongThi'] > 0, 'Create exactly the selected high-school site and its rooms');
    check_nv1(count(exam_ds_diem_thi('KT2026')) === count($before) + 1, 'Other schools do not get new exam sites');
    $newRooms = array_values(array_filter($pdo->query('SELECT * FROM PhongThi')->fetchAll(), static fn($r) => !in_array($r['maPhong'], $roomIdsBefore, true)));
    check_nv1(!array_filter($newRooms, static fn($r) => $r['maDiemThi'] !== $target || (int)$r['sucChua'] !== 30), 'Only selected site gets new 30-seat rooms');
    check_nv1(!array_filter($newRooms, static fn($r) => !str_starts_with($r['maPhong'], 'P4')), 'Selected school 4 receives P401, P402 style codes');
    $again = exam_tao_diem_thi_theo_nv1('KT2026', $target);
    check_nv1($again['diemThi'] === 0 && $again['phongThi'] === 0, 'Repeated preparation at the same site adds nothing');
    $preview = kiemTraVaTinhToanPhanPhong('KT2026', $target);
    check_nv1($preview['tongSucChua'] >= $preview['tongThiSinh'], 'Selected site has sufficient seats for its eligible cohort');
    $pdo->prepare('INSERT INTO DiemThi (maDiemThi,tenDiemThi,maTruongTHPT,maKyThi) VALUES (?, ?, ?, ?)')->execute(['TEST_NV1_SECOND', 'Điểm thi phụ kiểm thử', 'THPT_4', 'KT2026']);
    $snapshot = exam_ds_phong_thi($target);
    $second = exam_tao_diem_thi_theo_nv1('KT2026', 'TEST_NV1_SECOND');
    check_nv1($second['maDiemThi'] === 'TEST_NV1_SECOND', 'Respect selected site when one school has multiple sites');
    check_nv1(exam_ds_phong_thi($target) === $snapshot, 'Another site of the same school is unchanged');
    try { exam_tao_diem_thi_theo_nv1('KT2026', 'DT01'); throw new RuntimeException('Legacy site accepted'); }
    catch (ExamException $e) { check_nv1(true, 'Reject a THCS-only site'); }
    $originalRooms = array_values(array_filter($pdo->query('SELECT * FROM PhongThi ORDER BY maPhong')->fetchAll(), static fn($r) => in_array($r['maPhong'], $roomIdsBefore, true)));
    check_nv1($originalRooms === $roomsBefore, 'All pre-existing rooms remain unchanged');
    check_nv1((int)$pdo->query('SELECT COUNT(*) FROM KetQuaChamThi')->fetchColumn() === $scores, 'Existing grades are unchanged');
    $pdo->beginTransaction();
    try {
        $pdo->exec("INSERT INTO PhongThi (maPhong,tenPhong,sucChua,maDiemThi) VALUES ('Rabcdef0123_0001','Phòng thi 001',30,'DT02'),('Rabcdef0123_0002','Phòng thi 002',30,'DT02')");
        $pdo->exec("UPDATE PhanPhongThi SET maPhong='Rabcdef0123_0001' WHERE maPhanPhong='PP001'");
        $pdo->exec("UPDATE LoBaiThi SET maPhong='Rabcdef0123_0001' WHERE maLoBaiThi='LO_P101_TOAN'");
        $assignment = $pdo->query('SELECT maPhanCong FROM PhanCongCoiThi ORDER BY maPhanCong LIMIT 1')->fetchColumn();
        $pdo->prepare("UPDATE PhanCongCoiThi SET maPhong='Rabcdef0123_0001' WHERE maPhanCong=?")->execute([$assignment]);
        exam_doi_ma_phong_tu_sinh('DT02');
        $newCode = $pdo->query("SELECT maPhong FROM PhanPhongThi WHERE maPhanPhong='PP001'")->fetchColumn();
        check_nv1((bool)preg_match('/^P1[0-9]{2,}$/', $newCode), 'Old hashed code is converted to the registered school prefix');
        check_nv1($pdo->query("SELECT maPhong FROM LoBaiThi WHERE maLoBaiThi='LO_P101_TOAN'")->fetchColumn() === $newCode, 'Exam-bag foreign key follows the new code');
        $st = $pdo->prepare('SELECT maPhong FROM PhanCongCoiThi WHERE maPhanCong=?'); $st->execute([$assignment]);
        check_nv1($st->fetchColumn() === $newCode, 'Invigilation foreign key follows the new code');
        check_nv1((int)$pdo->query("SELECT COUNT(*) FROM PhongThi WHERE maPhong LIKE 'Rabcdef0123_%'")->fetchColumn() === 0, 'All old hashed rooms are removed after relinking');
        check_nv1((int)$pdo->query('SELECT COUNT(*) FROM KetQuaChamThi')->fetchColumn() === $scores, 'Renaming rooms retains all grades');
    } finally { $pdo->rollBack(); }

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
