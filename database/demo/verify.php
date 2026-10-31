<?php
declare(strict_types=1);
// Read-only diagnostic: select demo explicitly, preserve the configured host/user/password.
putenv('DB_NAME=dreamteam_tuyensinh10_demo');
require_once dirname(__DIR__, 2) . '/services/live_data.php';
require_once dirname(__DIR__, 2) . '/services/student_profile.php';
try {
    $stats = live_stats();
    if ($stats === null) throw new RuntimeException('Không đọc được database demo. Kiểm tra cấu hình kết nối.');
    $profileCounts = student_profile_ready() ? [
        'hoSoBoSung'=>live_val('SELECT COUNT(*) FROM HoSoBoSung'),
        'nguoiThan'=>live_val('SELECT COUNT(*) FROM GiaDinhThiSinh'),
        'namHoc'=>live_val('SELECT COUNT(*) FROM HocTapTHCS'),
    ] : ['hoSoBoSung'=>0,'nguoiThan'=>0,'namHoc'=>0];
    $checks = [
        '1750_hoc_sinh' => $stats['thiSinh'] === 1750,
        '5250_nguyen_vong' => $stats['nguyenVong'] === 5250,
        '5250_bai_thi' => $stats['baiThi'] === 5250,
        'ket_qua_thuc_te' => $stats['ketQuaChamThi'] >= 4870 && $stats['ketQuaChamThi'] <= $stats['baiThi'],
        'tong_theo_THCS' => array_sum(array_column($stats['theoTHCS'], 1)) === $stats['thiSinh'],
        'ho_so_bo_sung_day_du' => $profileCounts['hoSoBoSung'] === $stats['thiSinh'] && $profileCounts['nguoiThan'] === $stats['thiSinh']*3 && $profileCounts['namHoc'] === $stats['thiSinh']*4,
        'mot_ky_thi' => live_val('SELECT COUNT(*) FROM KyThi') === 1,
        'khong_mo_coi_ket_qua' => live_val('SELECT COUNT(*) FROM KetQuaChamThi k LEFT JOIN BaiThi b ON b.maBaiThi=k.maBaiThi WHERE b.maBaiThi IS NULL') === 0,
        'khong_mo_coi_nguyen_vong' => live_val('SELECT COUNT(*) FROM NguyenVong n LEFT JOIN ThiSinh t ON t.maThiSinh=n.maThiSinh WHERE t.maThiSinh IS NULL') === 0,
        'diem_thi_dung_nguyen_vong_1' => live_val("SELECT COUNT(*) FROM PhanPhongThi pp
            JOIN ThiSinh t ON t.maThiSinh=pp.maThiSinh
            JOIN NguyenVong nv ON nv.maThiSinh=t.maThiSinh AND nv.thuTuNguyenVong=1
            JOIN PhongThi p ON p.maPhong=pp.maPhong
            JOIN DiemThi d ON d.maDiemThi=p.maDiemThi
            WHERE pp.trangThai='Đã phân' AND (d.maTruongTHPT IS NULL OR d.maTruongTHPT<>nv.maTruongTHPT)") === 0,
        'phong_khong_vuot_suc_chua' => live_val("SELECT COUNT(*) FROM (
            SELECT p.maPhong FROM PhongThi p
            LEFT JOIN PhanPhongThi pp ON pp.maPhong=p.maPhong AND pp.trangThai='Đã phân'
            GROUP BY p.maPhong,p.sucChua HAVING COUNT(pp.maPhanPhong)>p.sucChua
        ) vuot") === 0,
    ];
    echo json_encode(['database'=>$stats['database'], 'counts'=>[
        'hocSinh'=>$stats['thiSinh'], 'nguyenVong'=>$stats['nguyenVong'],
        'baiThi'=>$stats['baiThi'], 'ketQuaCham'=>$stats['ketQuaChamThi'],
        'lichSuDiem'=>live_val('SELECT COUNT(*) FROM LichSuChinhSuaDiem'),
    ], 'supplementaryCounts'=>$profileCounts, 'checks'=>$checks], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
    exit(in_array(false, $checks, true) ? 1 : 0);
} catch (Throwable $e) {
    fwrite(STDERR, "Không xác minh được database demo. Kiểm tra host/port/user/password và tên database; không có dữ liệu nào được sửa.\n");
    exit(2);
}
