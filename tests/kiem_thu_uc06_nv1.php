<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/services/exam_service.php';
function check_nv1(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS: $message\n";
}
$pdo = db();
$before = array_column(exam_ds_diem_thi('KT2026'), 'maDiemThi');
$rooms = (int)$pdo->query('SELECT COUNT(*) FROM PhongThi')->fetchColumn();
$scores = (int)$pdo->query('SELECT COUNT(*) FROM KetQuaChamThi')->fetchColumn();
try {
    $created = exam_tao_diem_thi_theo_nv1('KT2026');
    $sites = exam_ds_diem_thi('KT2026', true);
    check_nv1(count(array_unique(array_column($sites, 'maTruongTHPT'))) === 5, 'Each NV1 high school has an exam site');
    check_nv1(!in_array('DT01', array_column($sites, 'maDiemThi')), 'THCS-only site is excluded from UC06/07 choices');
    check_nv1(in_array('DT02', array_column($sites, 'maDiemThi')), 'Existing correctly linked high-school site is retained');
    check_nv1($created === 4, 'Only four missing school sites are created');
    check_nv1(exam_tao_diem_thi_theo_nv1('KT2026') === 0, 'Repeated setup does not duplicate sites');
    try { kiemTraPhongThi('DT01', 'TESTNV1', 'Phong test', 30); throw new RuntimeException('Legacy site accepted'); }
    catch (ExamException $e) { check_nv1(str_contains(implode(' ', $e->details()), 'chưa được gắn'), 'Reject creating rooms at a THCS-only site'); }
    check_nv1((int)$pdo->query('SELECT COUNT(*) FROM PhongThi')->fetchColumn() === $rooms, 'Existing rooms are unchanged');
    check_nv1((int)$pdo->query('SELECT COUNT(*) FROM KetQuaChamThi')->fetchColumn() === $scores, 'Existing grades are unchanged');
} finally {
    $after = array_column(exam_ds_diem_thi('KT2026'), 'maDiemThi');
    $delete = $pdo->prepare('DELETE FROM DiemThi WHERE maDiemThi = ?');
    foreach (array_diff($after, $before) as $id) $delete->execute([$id]);
}
echo "UC06/07 NV1 site checks passed; original sites restored.\n";
