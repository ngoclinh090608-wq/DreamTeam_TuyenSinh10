<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
// Intentionally never uses the application's default development database.
putenv('DB_NAME=dreamteam_tuyensinh10_demo');
require dirname(__DIR__, 2) . '/config/database.php';
$pdo = db(); $checks = [];
$check = function (string $name, string $sql, int $expected) use ($pdo, &$checks): void {
    $actual = (int)$pdo->query($sql)->fetchColumn();
    $checks[$name] = ['expected'=>$expected,'actual'=>$actual,'passed'=>$actual===$expected];
};
$check('THCS_moi', "SELECT COUNT(*) FROM TruongTHCS WHERE maTruongTHCS IN('THCS_DEMO1','THCS_DEMO2','THCS_DEMO3','THCS_DEMO4','THCS_DEMO5')", 5);
$check('ThiSinh_moi', "SELECT COUNT(*) FROM ThiSinh WHERE maThiSinh BETWEEN 'TSD000001' AND 'TSD001750'", 1750);
$check('NguyenVong_moi', "SELECT COUNT(*) FROM NguyenVong WHERE maNguyenVong BETWEEN 'NVD0000001' AND 'NVD0005250'", 5250);
foreach (['THCS_DEMO1'=>320,'THCS_DEMO2'=>335,'THCS_DEMO3'=>350,'THCS_DEMO4'=>365,'THCS_DEMO5'=>380] as $id=>$count) {
    $check($id, "SELECT COUNT(*) FROM ThiSinh WHERE maTruongTHCS='$id'", $count);
}
foreach (['THPT_1'=>320,'THPT_2'=>335,'THPT_3'=>350,'THPT_4'=>365,'THPT_5'=>380] as $id=>$count) {
    $check('chi_tieu_'.$id, "SELECT chiTieuTuyenSinh FROM ThongTinTuyenSinh WHERE maThongTin='TT_$id' AND maTruongTHPT='$id' AND maDot='DOT2026'", $count);
}
$check('NV_thieu_hoac_trung_truong', "SELECT COUNT(*) FROM(SELECT t.maThiSinh FROM ThiSinh t LEFT JOIN NguyenVong n ON n.maThiSinh=t.maThiSinh WHERE t.maThiSinh BETWEEN 'TSD000001' AND 'TSD001750' GROUP BY t.maThiSinh HAVING COUNT(n.maNguyenVong)<>3 OR COUNT(DISTINCT n.maTruongTHPT)<>3)x", 0);
$check('FK_THCS', 'SELECT COUNT(*) FROM ThiSinh t LEFT JOIN TruongTHCS c ON c.maTruongTHCS=t.maTruongTHCS WHERE c.maTruongTHCS IS NULL', 0);
$check('FK_NV', 'SELECT COUNT(*) FROM NguyenVong n LEFT JOIN ThiSinh t ON t.maThiSinh=n.maThiSinh LEFT JOIN TruongTHPT p ON p.maTruongTHPT=n.maTruongTHPT LEFT JOIN QuyDinhNguyenVong q ON q.maQuyDinh=n.maQuyDinh WHERE t.maThiSinh IS NULL OR p.maTruongTHPT IS NULL OR q.maQuyDinh IS NULL', 0);
$check('Trung_SBD', 'SELECT COUNT(*) FROM(SELECT soBaoDanh FROM ThiSinh WHERE soBaoDanh IS NOT NULL GROUP BY soBaoDanh HAVING COUNT(*)>1)x', 0);
$check('Trung_ma_ho_so', 'SELECT COUNT(*) FROM(SELECT maHoSo FROM ThiSinh GROUP BY maHoSo HAVING COUNT(*)>1)x', 0);
$check('Trung_thu_tu_NV', 'SELECT COUNT(*) FROM(SELECT maThiSinh,thuTuNguyenVong FROM NguyenVong GROUP BY maThiSinh,thuTuNguyenVong HAVING COUNT(*)>1)x', 0);
$check('NV_ngoai_quy_dinh', 'SELECT COUNT(*) FROM NguyenVong n JOIN QuyDinhNguyenVong q ON q.maQuyDinh=n.maQuyDinh WHERE n.thuTuNguyenVong>q.soLuongNguyenVongToiDa OR n.ngayDangKy NOT BETWEEN q.thoiGianBatDau AND q.thoiGianKetThuc', 0);
$check('Diem_khong_hop_le', 'SELECT COUNT(*) FROM KetQuaChamThi WHERE diemThi<0 OR diemThi>10 OR MOD(diemThi*100,25)<>0', 0);
$totals = [];
foreach (['TruongTHCS','TruongTHPT','ThiSinh','NguyenVong','TaiKhoan','BaiThi','KetQuaChamThi','LichSuChinhSuaDiem'] as $table) $totals[$table]=(int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
$report = ['database'=>$pdo->query('SELECT DATABASE()')->fetchColumn(),'checks'=>$checks,'totals'=>$totals,'publication_scenarios'=>$pdo->query("SELECT b.maMonThi,COUNT(*) AS baiThi,SUM(k.maKetQuaChamThi IS NOT NULL) AS daCham,SUM(CASE WHEN k.trangThaiCongBo='Đã công bố' THEN 1 ELSE 0 END) AS daCongBo FROM BaiThi b LEFT JOIN KetQuaChamThi k ON k.maBaiThi=b.maBaiThi GROUP BY b.maMonThi ORDER BY b.maMonThi")->fetchAll()];
$report['passed'] = !in_array(false,array_column($checks,'passed'),true);
echo json_encode($report, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR),PHP_EOL;
exit($report['passed'] ? 0 : 1);
