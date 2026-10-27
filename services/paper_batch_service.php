<?php
declare(strict_types=1);
/**
 * UC09 – Tra cứu thông tin lô bài thi (Actor: Bộ phận cắt phách). Chỉ đọc dữ liệu.
 * Bảng dùng: LoBaiThi, BaiThi, MonThi, PhongThi, DiemThi, ThiSinh.
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';

const LO_KHONG_HOP_LE = 'Điều kiện tra cứu không hợp lệ.';
const LO_KHONG_TIM_THAY = 'Không tìm thấy lô bài thi phù hợp.';
const LO_KHONG_TRUY_XUAT = 'Không thể truy xuất thông tin của lô bài thi được chọn.';

function lo_ds_diem_thi(): array {
    return db()->query('SELECT maDiemThi, tenDiemThi FROM DiemThi ORDER BY maDiemThi')->fetchAll();
}

function lo_ds_phong_thi(): array {
    return db()->query("SELECT p.maPhong, CONCAT(p.tenPhong, ' (', p.maPhong, ')') AS tenHienThi, p.maDiemThi FROM PhongThi p ORDER BY p.maPhong")->fetchAll();
}

function lo_ds_mon_thi(): array {
    return db()->query('SELECT maMonThi, tenMonThi FROM MonThi ORDER BY maMonThi')->fetchAll();
}

/**
 * Bước 5: kiểm tra điều kiện tra cứu. Ném BusinessException(LO_KHONG_HOP_LE) kèm lý do nếu sai.
 * @param array{maLo:string,diemThi:string,phong:string,monThi:string} $dk
 */
function kiemTraDieuKienTraCuuLo(array $dk): void {
    $loi = [];
    if ($dk['maLo'] === '' && $dk['diemThi'] === '' && $dk['phong'] === '' && $dk['monThi'] === '') {
        $loi[] = 'Vui lòng nhập mã lô hoặc chọn ít nhất một trong: điểm thi, phòng thi, môn thi.';
    }
    if ($dk['maLo'] !== '' && !preg_match('/^[A-Za-z0-9_\-]{1,20}$/', $dk['maLo'])) {
        $loi[] = 'Mã lô bài thi chỉ gồm chữ không dấu, số, dấu gạch dưới hoặc gạch ngang, tối đa 20 ký tự.';
    }
    if ($dk['phong'] !== '' && $dk['diemThi'] !== '') {
        $st = db()->prepare('SELECT 1 FROM PhongThi WHERE maPhong = ? AND maDiemThi = ?');
        $st->execute([$dk['phong'], $dk['diemThi']]);
        if (!$st->fetchColumn()) $loi[] = 'Phòng thi đã chọn không thuộc điểm thi đã chọn.';
    }
    if ($loi) throw new BusinessException(LO_KHONG_HOP_LE, $loi);
}

/** Bước 6–7: danh sách lô bài thi phù hợp, kèm số bài để đối chiếu. */
function timLoBaiThi(array $dk): array {
    kiemTraDieuKienTraCuuLo($dk);
    $where = []; $params = [];
    if ($dk['maLo'] !== '')   { $where[] = 'l.maLoBaiThi LIKE ?'; $params[] = '%' . $dk['maLo'] . '%'; }
    if ($dk['diemThi'] !== '') { $where[] = 'p.maDiemThi = ?'; $params[] = $dk['diemThi']; }
    if ($dk['phong'] !== '')  { $where[] = 'l.maPhong = ?'; $params[] = $dk['phong']; }
    if ($dk['monThi'] !== '') { $where[] = 'l.maMonThi = ?'; $params[] = $dk['monThi']; }
    $st = db()->prepare(
        'SELECT l.maLoBaiThi, l.thongTinLoBaiThi, m.tenMonThi, p.maPhong, p.tenPhong, d.tenDiemThi,
                COUNT(b.maBaiThi) AS soBai, COUNT(b.soPhach) AS soBaiCoPhach
         FROM LoBaiThi l
         JOIN MonThi m ON m.maMonThi = l.maMonThi
         JOIN PhongThi p ON p.maPhong = l.maPhong
         JOIN DiemThi d ON d.maDiemThi = p.maDiemThi
         LEFT JOIN BaiThi b ON b.maLoBaiThi = l.maLoBaiThi
         WHERE ' . implode(' AND ', $where) . '
         GROUP BY l.maLoBaiThi, l.thongTinLoBaiThi, m.tenMonThi, p.maPhong, p.tenPhong, d.tenDiemThi
         ORDER BY l.maLoBaiThi'
    );
    $st->execute($params);
    $rows = $st->fetchAll();
    if (!$rows) throw new BusinessException(LO_KHONG_TIM_THAY);
    return $rows;
}

/** Bước 9–10: thông tin chi tiết lô bài thi và danh sách bài thi trong lô. */
function layThongTinLoBaiThi(string $maLo): array {
    $st = db()->prepare(
        'SELECT l.maLoBaiThi, l.thongTinLoBaiThi, m.tenMonThi, p.maPhong, p.tenPhong, p.sucChua, d.tenDiemThi
         FROM LoBaiThi l
         JOIN MonThi m ON m.maMonThi = l.maMonThi
         JOIN PhongThi p ON p.maPhong = l.maPhong
         JOIN DiemThi d ON d.maDiemThi = p.maDiemThi
         WHERE l.maLoBaiThi = ?'
    );
    $st->execute([$maLo]);
    $lo = $st->fetch();
    if (!$lo) throw new BusinessException(LO_KHONG_TRUY_XUAT);
    $st = db()->prepare(
        'SELECT b.maBaiThi, t.soBaoDanh, b.soPhach, b.trangThaiCham
         FROM BaiThi b JOIN ThiSinh t ON t.maThiSinh = b.maThiSinh
         WHERE b.maLoBaiThi = ? ORDER BY t.soBaoDanh'
    );
    $st->execute([$maLo]);
    $lo['baiThi'] = $st->fetchAll();
    $lo['soBai'] = count($lo['baiThi']);
    $lo['soBaiCoPhach'] = count(array_filter($lo['baiThi'], fn($b) => $b['soPhach'] !== null));
    return $lo;
}
