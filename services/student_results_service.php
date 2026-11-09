<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';

/** Ownership comes only from the authenticated account, never a URL parameter. */
function student_results_owner(array $actor): string {
    if (($actor['vaiTro'] ?? '') !== 'HOC_SINH' || empty($actor['maThiSinh'])) {
        throw new BusinessException('Không tìm thấy hồ sơ học sinh gắn với tài khoản.');
    }
    return (string)$actor['maThiSinh'];
}

function student_published_scores(array $actor): array {
    $q = db()->prepare("SELECT b.maBaiThi, k.tenKyThi, m.tenMonThi, g.diemThi
        FROM BaiThi b JOIN KyThi k ON k.maKyThi=b.maKyThi
        JOIN MonThi m ON m.maMonThi=b.maMonThi
        JOIN KetQuaChamThi g ON g.maBaiThi=b.maBaiThi
        WHERE b.maThiSinh=? AND g.trangThaiCongBo='Đã công bố'
        ORDER BY k.thoiGianKetThuc DESC, b.maKyThi, m.maMonThi");
    $q->execute([student_results_owner($actor)]);
    return $q->fetchAll();
}

function student_published_admissions(array $actor): array {
    $q = db()->prepare("SELECT DISTINCT d.namTuyenSinh, dot.tenDot, r.maKetQua,
            n.thuTuNguyenVong, p.tenTruongTHPT, r.diemXetTuyen, r.trangThaiXetTuyen,
            COALESCE(e.trangThaiNhapHoc, 'Chưa xác nhận') AS trangThaiNhapHoc,
            e.thoiGianXacNhanNhapHoc
        FROM KetQuaXetTuyen r JOIN NguyenVong n ON n.maNguyenVong=r.maNguyenVong
        JOIN TruongTHPT p ON p.maTruongTHPT=n.maTruongTHPT
        JOIN KetQuaTuyenSinh l ON l.maKetQuaXetTuyen=r.maKetQua
        JOIN DanhSachKetQuaTuyenSinh d ON d.maDanhSach=l.maDanhSach
        JOIN DotTuyenSinh dot ON dot.maDot=d.maDot
        LEFT JOIN TinhTrangNhapHoc e ON e.maKetQua=r.maKetQua
        WHERE r.maThiSinh=? AND n.maThiSinh=r.maThiSinh
          AND d.trangThaiPheDuyet='Đã phê duyệt' AND d.trangThaiCongBo='Đã công bố'
        ORDER BY d.namTuyenSinh DESC, dot.tenDot, n.thuTuNguyenVong");
    $q->execute([student_results_owner($actor)]);
    return $q->fetchAll();
}
