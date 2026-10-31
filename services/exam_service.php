<?php
declare(strict_types=1);
/**
 * Nghiệp vụ quản lý phòng thi, phân phòng thi và phân công ra đề thi (UC06, UC07, UC08).
 * Bảng dùng: KyThi, MonThi, DiemThi, PhongThi, PhanPhongThi, ThiSinh, TruongTHCS, PhanCongRaDe, CanBo.
 * Tên hàm bám theo thiết kế lớp trong báo cáo Tuần 07; câu thông báo bám nguyên văn Test Case Tuần 04.
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';
require_once dirname(__DIR__) . '/includes/permissions.php';

// ---------- Câu thông báo theo Test Case Tuần 04 ----------
// UC06: Tạo thông tin phòng thi
const EXAM_TB_PHONG_THANH_CONG = 'Tạo thông tin phòng thi thành công.';
const EXAM_TB_PHONG_KHONG_HOP_LE = 'Thông tin phòng thi không hợp lệ.';
const EXAM_TB_PHONG_TRUNG = 'Thông tin phòng thi bị trùng.';
const EXAM_TB_LOI_LUU_PHONG = 'Không thể lưu thông tin phòng thi.';
const EXAM_TB_XOA_PHONG_THANH_CONG = 'Xóa phòng thi thành công.';
const EXAM_TB_KHONG_THE_XOA_PHONG = 'Không thể xóa phòng thi đã có thí sinh được phân phòng.';

// UC07: Phân phòng thi cho thí sinh
const EXAM_TB_PHAN_PHONG_THANH_CONG = 'Phân phòng thi thành công.';
const EXAM_TB_CHUA_DU_DIEU_KIEN_PHAN_PHONG = 'Chưa đủ điều kiện phân phòng.';
const EXAM_TB_SUC_CHUA_KHONG_DU = 'Sức chứa phòng thi không đủ.';
const EXAM_TB_DIEM_THI_KHONG_PHU_HOP = 'Điểm thi không phù hợp với khu vực trường THCS.';
const EXAM_TB_LOI_LUU_PHAN_PHONG = 'Không thể lưu kết quả phân phòng.';
const EXAM_TB_CHUYEN_PHONG_THANH_CONG = 'Chuyển phòng thi cho thí sinh thành công.';
const EXAM_TB_HUY_PHAN_PHONG_THANH_CONG = 'Hủy phân phòng thi cho thí sinh thành công.';

// UC08: Xem phân công ra đề thi
const EXAM_TB_CHUA_CO_PHAN_CONG_RA_DE = 'Chưa có phân công ra đề thi.';
const EXAM_TB_KHONG_DU_QUYEN = 'Không đủ quyền truy cập.';
const EXAM_TB_LOI_TAI_PHAN_CONG = 'Không thể lấy thông tin tại thời điểm hiện tại.';

final class ExamException extends BusinessException {}

// =============================================================================
// DANH MỤC DÙNG CHUNG
// =============================================================================

function exam_ds_ky_thi(): array {
    return db()->query('SELECT maKyThi, tenKyThi, thoiGianKetThuc, maDot FROM KyThi ORDER BY maKyThi DESC')->fetchAll();
}

function exam_ds_diem_thi(string $maKyThi = ''): array {
    $sql = 'SELECT d.maDiemThi, d.tenDiemThi, d.maKyThi, d.maTruongTHCS, d.maTruongTHPT,
                   k.tenKyThi,
                   COALESCE(c.tenTruongTHCS, p.tenTruongTHPT, "") AS tenTruongDatDiem,
                   COALESCE(c.maKhuVuc, p.maKhuVuc, "") AS maKhuVuc
            FROM DiemThi d
            JOIN KyThi k ON k.maKyThi = d.maKyThi
            LEFT JOIN TruongTHCS c ON c.maTruongTHCS = d.maTruongTHCS
            LEFT JOIN TruongTHPT p ON p.maTruongTHPT = d.maTruongTHPT';
    if ($maKyThi !== '') {
        $st = db()->prepare($sql . ' WHERE d.maKyThi = ? ORDER BY d.maDiemThi');
        $st->execute([$maKyThi]);
        return $st->fetchAll();
    }
    return db()->query($sql . ' ORDER BY d.maDiemThi')->fetchAll();
}

function exam_ds_phong_thi(string $maDiemThi = ''): array {
    $sql = 'SELECT p.maPhong, p.tenPhong, p.sucChua, p.maDiemThi, d.tenDiemThi, d.maKyThi,
                   COALESCE(daPhan.soLuong, 0) AS soLuongThiSinh
            FROM PhongThi p
            JOIN DiemThi d ON d.maDiemThi = p.maDiemThi
            LEFT JOIN (
                SELECT maPhong, COUNT(*) AS soLuong
                FROM PhanPhongThi
                WHERE trangThai = "Đã phân"
                GROUP BY maPhong
            ) daPhan ON daPhan.maPhong = p.maPhong';
    if ($maDiemThi !== '') {
        $st = db()->prepare($sql . ' WHERE p.maDiemThi = ? ORDER BY p.maPhong');
        $st->execute([$maDiemThi]);
        return $st->fetchAll();
    }
    return db()->query($sql . ' ORDER BY d.maDiemThi, p.maPhong')->fetchAll();
}

function exam_ds_mon_thi(): array {
    return db()->query('SELECT maMonThi, tenMonThi, thangDiem FROM MonThi ORDER BY maMonThi')->fetchAll();
}

// =============================================================================
// UC06: TẠO THÔNG TIN PHÒNG THI (Ban Tuyển sinh Sở GD&ĐT)
// =============================================================================

/**
 * Bước 5: tự động sinh mã phòng tiếp theo cho một điểm thi (vd P101, P102 -> P103).
 * Mã phòng là khóa chính toàn hệ thống nên bỏ qua mã đã dùng ở điểm thi khác (8.1: sinh lại khi trùng).
 */
function sinhMaPhongTuDong(string $maDiemThi): string {
    $st = db()->prepare('SELECT maPhong FROM PhongThi WHERE maDiemThi = ? ORDER BY LENGTH(maPhong) DESC, maPhong DESC LIMIT 1');
    $st->execute([$maDiemThi]);
    $last = (string)$st->fetchColumn();
    if ($last !== '' && preg_match('/^([A-Za-z]+)(\d+)$/', $last, $m)) {
        $prefix = $m[1];
        $num = (int)$m[2] + 1;
        $len = strlen($m[2]);
    } else {
        // Gợi ý theo mã điểm thi: DT01 -> P101, DT02 -> P201
        $prefix = 'P';
        $num = preg_match('/(\d+)$/', $maDiemThi, $m) ? (int)$m[1] * 100 + 1 : 1;
        $len = 3;
    }
    $stTonTai = db()->prepare('SELECT COUNT(*) FROM PhongThi WHERE maPhong = ?');
    for ($i = 0; $i < 1000; $i++, $num++) {
        $ma = sprintf('%s%0' . $len . 'd', $prefix, $num);
        $stTonTai->execute([$ma]);
        if ((int)$stTonTai->fetchColumn() === 0) return $ma;
    }
    return $prefix . date('His');
}

/**
 * Kiểm tra tính hợp lệ và trùng lặp thông tin phòng thi.
 */
function kiemTraPhongThi(string $maDiemThi, string $maPhong, string $tenPhong, int $sucChua, ?string $maPhongCu = null): void {
    $maDiemThi = trim($maDiemThi);
    $maPhong = trim($maPhong);
    $tenPhong = trim($tenPhong);

    if ($maDiemThi === '' || $maPhong === '' || $tenPhong === '' || $sucChua <= 0) {
        throw new ExamException(EXAM_TB_PHONG_KHONG_HOP_LE, [
            'Mã điểm thi, mã phòng và tên phòng không được để trống.',
            'Sức chứa tối đa của phòng thi phải là số nguyên dương lớn hơn 0.',
        ]);
    }

    if (strlen($maPhong) > 20 || strlen($tenPhong) > 100) {
        throw new ExamException(EXAM_TB_PHONG_KHONG_HOP_LE, [
            'Mã phòng không vượt quá 20 ký tự, tên phòng không vượt quá 100 ký tự.',
        ]);
    }

    // Kiểm tra điểm thi tồn tại
    $st = db()->prepare('SELECT COUNT(*) FROM DiemThi WHERE maDiemThi = ?');
    $st->execute([$maDiemThi]);
    if ((int)$st->fetchColumn() === 0) {
        throw new ExamException(EXAM_TB_PHONG_KHONG_HOP_LE, ['Địa điểm thi không tồn tại trong hệ thống.']);
    }

    // Kiểm tra trùng mã phòng trên toàn hệ thống (nếu thêm mới hoặc đổi mã)
    if ($maPhongCu === null || $maPhong !== $maPhongCu) {
        $st = db()->prepare('SELECT COUNT(*) FROM PhongThi WHERE maPhong = ?');
        $st->execute([$maPhong]);
        if ((int)$st->fetchColumn() > 0) {
            throw new ExamException(EXAM_TB_PHONG_TRUNG, ["Mã phòng thi '{$maPhong}' đã tồn tại trong hệ thống."]);
        }
    }

    // Kiểm tra trùng tên phòng trong cùng một điểm thi
    $sql = 'SELECT COUNT(*) FROM PhongThi WHERE maDiemThi = ? AND tenPhong = ?';
    $params = [$maDiemThi, $tenPhong];
    if ($maPhongCu !== null) {
        $sql .= ' AND maPhong <> ?';
        $params[] = $maPhongCu;
    }
    $st = db()->prepare($sql);
    $st->execute($params);
    if ((int)$st->fetchColumn() > 0) {
        throw new ExamException(EXAM_TB_PHONG_TRUNG, ["Tên phòng '{$tenPhong}' đã tồn tại tại điểm thi này."]);
    }
}

/**
 * Lưu phòng thi mới vào CSDL (UC06).
 */
function luuPhongThi(string $maDiemThi, string $maPhong, string $tenPhong, int $sucChua): array {
    $maDiemThi = trim($maDiemThi);
    $maPhong = trim($maPhong);
    $tenPhong = trim($tenPhong);

    kiemTraPhongThi($maDiemThi, $maPhong, $tenPhong, $sucChua);

    $pdo = db();
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('INSERT INTO PhongThi (maPhong, tenPhong, sucChua, maDiemThi) VALUES (?, ?, ?, ?)');
        $st->execute([$maPhong, $tenPhong, $sucChua, $maDiemThi]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('luuPhongThi: ' . $e->getMessage());
        throw new ExamException(EXAM_TB_LOI_LUU_PHONG, ['Tạo phòng thi không thành công. Phòng thi mới không được ghi nhận.']);
    }

    return [
        'maPhong' => $maPhong,
        'tenPhong' => $tenPhong,
        'sucChua' => $sucChua,
        'maDiemThi' => $maDiemThi,
    ];
}

/**
 * Xóa phòng thi (chỉ cho phép xóa khi phòng chưa có thí sinh phân vào).
 */
function xoaPhongThi(string $maPhong): void {
    $maPhong = trim($maPhong);
    $st = db()->prepare('SELECT COUNT(*) FROM PhanPhongThi WHERE maPhong = ?');
    $st->execute([$maPhong]);
    if ((int)$st->fetchColumn() > 0) {
        throw new ExamException(EXAM_TB_KHONG_THE_XOA_PHONG, [
            "Phòng thi {$maPhong} đã có thí sinh được phân phòng, không thể xóa.",
        ]);
    }

    $st = db()->prepare('DELETE FROM PhongThi WHERE maPhong = ?');
    $st->execute([$maPhong]);
}

// =============================================================================
// UC07: PHÂN PHÒNG THI CHO THÍ SINH (Ban Tuyển sinh Sở GD&ĐT)
// =============================================================================

/**
 * Thông tin điểm thi kèm khu vực của trường đặt điểm thi. Kiểm tra điểm thi thuộc kỳ thi (nếu truyền kỳ thi).
 */
function layThongTinDiemThi(string $maDiemThi, string $maKyThi = ''): array {
    $st = db()->prepare('SELECT d.maDiemThi, d.tenDiemThi, d.maKyThi,
                                COALESCE(c.maKhuVuc, p.maKhuVuc, "") AS maKhuVuc,
                                kv.tenKhuVuc
                         FROM DiemThi d
                         LEFT JOIN TruongTHCS c ON c.maTruongTHCS = d.maTruongTHCS
                         LEFT JOIN TruongTHPT p ON p.maTruongTHPT = d.maTruongTHPT
                         LEFT JOIN KhuVuc kv ON kv.maKhuVuc = COALESCE(c.maKhuVuc, p.maKhuVuc)
                         WHERE d.maDiemThi = ?');
    $st->execute([$maDiemThi]);
    $dt = $st->fetch();
    if (!$dt) {
        throw new ExamException(EXAM_TB_CHUA_DU_DIEU_KIEN_PHAN_PHONG, ['Điểm thi không tồn tại trong hệ thống.']);
    }
    if ($maKyThi !== '' && $dt['maKyThi'] !== $maKyThi) {
        throw new ExamException(EXAM_TB_CHUA_DU_DIEU_KIEN_PHAN_PHONG, ['Điểm thi được chọn không thuộc kỳ thi đã chọn.']);
    }
    return $dt;
}

/** Thứ tự tiếng Việt: tên gọi → họ và tên đệm; không ưu tiên trường THCS.
 * Chỉ trả về SQL cố định, không nhận đầu vào từ người dùng.
 */
function exam_thu_tu_ten_abc_sql(): string {
    $hoTen = "TRIM(REGEXP_REPLACE(t.hoTen, '[[:space:]]+', ' '))";
    $ten = "SUBSTRING_INDEX($hoTen, ' ', -1)";
    $hoDem = "TRIM(LEFT($hoTen, CHAR_LENGTH($hoTen) - CHAR_LENGTH($ten)))";
    return "$ten COLLATE utf8mb4_vietnamese_ci, $hoDem COLLATE utf8mb4_vietnamese_ci, t.maThiSinh";
}

/**
 * Bước 4, 6: danh sách thí sinh cần phân phòng tại điểm thi.
 * Quy tắc phân điểm thi (đặc tả UC07 bước 7):
 * - Điểm thi phục vụ thí sinh của các trường THCS cùng khu vực (phường/quận lân cận) với trường đặt điểm thi.
 * - Chỉ lấy thí sinh đủ điều kiện dự thi (dieuKienDuThi = 1, trangThaiDangKyDuThi = 'Đã duyệt').
 * - Bỏ qua thí sinh đã được phân phòng ở một điểm thi khác của cùng kỳ thi (không “kéo” thí sinh của điểm thi khác).
 */
function layDanhSachThiSinhPhuHopDiemThi(string $maKyThi, string $maDiemThi): array {
    $dt = layThongTinDiemThi($maDiemThi, $maKyThi);
    $maKhuVuc = (string)$dt['maKhuVuc'];

    $sql = 'SELECT t.maThiSinh, t.maHoSo, t.hoTen, t.ngaySinh, t.soBaoDanh,
                   t.dieuKienDuThi, t.trangThaiDangKyDuThi,
                   c.maTruongTHCS, c.tenTruongTHCS, c.maKhuVuc, kv.tenKhuVuc,
                   hp.maPhanPhong, hp.maPhong, hp.tenPhong, hp.maDiemThi AS diemThiHienTai, hp.tenDiemThi AS tenDiemThiHienTai
            FROM ThiSinh t
            JOIN TruongTHCS c ON c.maTruongTHCS = t.maTruongTHCS
            JOIN KhuVuc kv ON kv.maKhuVuc = c.maKhuVuc
            LEFT JOIN (
                SELECT pp.maPhanPhong, pp.maThiSinh, pp.maPhong, pt.tenPhong, d.maDiemThi, d.tenDiemThi
                FROM PhanPhongThi pp
                JOIN PhongThi pt ON pt.maPhong = pp.maPhong
                JOIN DiemThi d ON d.maDiemThi = pt.maDiemThi
                WHERE pp.trangThai = "Đã phân" AND d.maKyThi = ?
            ) hp ON hp.maThiSinh = t.maThiSinh
            WHERE t.dieuKienDuThi = 1 AND t.trangThaiDangKyDuThi = "Đã duyệt"
              AND (hp.maDiemThi IS NULL OR hp.maDiemThi = ?)';
    $params = [$dt['maKyThi'], $maDiemThi];
    if ($maKhuVuc !== '') {
        $sql .= ' AND c.maKhuVuc = ?';
        $params[] = $maKhuVuc;
    }
    // Xếp ABC theo tên gọi trên toàn điểm thi; mã thí sinh giữ thứ tự ổn định khi trùng tên.
    $sql .= ' ORDER BY ' . exam_thu_tu_ten_abc_sql();

    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/**
 * Exception 6.3: thí sinh đang được phân vào điểm thi nhưng trường THCS không cùng khu vực với điểm thi.
 * Những thí sinh này không được phân tự động vào điểm thi; họ vẫn chiếm chỗ trong phòng cho tới khi được chuyển/hủy.
 */
function layThiSinhKhongPhuHopDiemThi(string $maDiemThi): array {
    $dt = layThongTinDiemThi($maDiemThi);
    if ((string)$dt['maKhuVuc'] === '') return [];
    $st = db()->prepare('SELECT pp.maPhanPhong, pp.maPhong, t.maThiSinh, t.hoTen, c.tenTruongTHCS, c.maKhuVuc, kv.tenKhuVuc
                         FROM PhanPhongThi pp
                         JOIN PhongThi p ON p.maPhong = pp.maPhong
                         JOIN ThiSinh t ON t.maThiSinh = pp.maThiSinh
                         JOIN TruongTHCS c ON c.maTruongTHCS = t.maTruongTHCS
                         JOIN KhuVuc kv ON kv.maKhuVuc = c.maKhuVuc
                         WHERE p.maDiemThi = ? AND pp.trangThai = "Đã phân" AND c.maKhuVuc <> ?
                         ORDER BY pp.maPhong, t.hoTen');
    $st->execute([$maDiemThi, $dt['maKhuVuc']]);
    return $st->fetchAll();
}

/**
 * Bước 6–8: kiểm tra điều kiện phân phòng và tính kết quả phân phòng dự kiến (chưa lưu).
 * Thí sinh được xếp lần lượt vào từng phòng theo ABC của tên gọi, rồi họ và tên đệm, không vượt sức chứa còn trống của phòng.
 */
function kiemTraVaTinhToanPhanPhong(string $maKyThi, string $maDiemThi): array {
    $maKyThi = trim($maKyThi);
    $maDiemThi = trim($maDiemThi);

    if ($maKyThi === '' || $maDiemThi === '') {
        throw new ExamException(EXAM_TB_CHUA_DU_DIEU_KIEN_PHAN_PHONG, [
            'Vui lòng chọn đầy đủ Kỳ thi và Điểm thi cần phân phòng.',
        ]);
    }
    $dt = layThongTinDiemThi($maDiemThi, $maKyThi);

    // 6.1: điểm thi chưa có phòng thi
    $dsPhong = exam_ds_phong_thi($maDiemThi);
    if (empty($dsPhong)) {
        throw new ExamException(EXAM_TB_CHUA_DU_DIEU_KIEN_PHAN_PHONG, [
            'Điểm thi được chọn chưa có phòng thi nào. Vui lòng thực hiện chức năng Tạo thông tin phòng thi (UC06) trước khi phân phòng.',
        ]);
    }

    // 6.3: điểm thi không phục vụ thí sinh nào thuộc khu vực của mình
    $dsThiSinh = layDanhSachThiSinhPhuHopDiemThi($maKyThi, $maDiemThi);
    $tongThiSinh = count($dsThiSinh);
    if ($tongThiSinh === 0) {
        throw new ExamException(EXAM_TB_DIEM_THI_KHONG_PHU_HOP, [
            'Không có thí sinh đủ điều kiện dự thi thuộc các trường THCS cùng khu vực' . ($dt['tenKhuVuc'] ? " ({$dt['tenKhuVuc']})" : '') . ' với điểm thi này cần phân phòng.',
            'Vui lòng chọn điểm thi phù hợp khác.',
        ]);
    }

    // Chỗ đang bị chiếm bởi thí sinh không thuộc danh sách cần phân (vd thí sinh khác khu vực)
    $maCanPhan = array_flip(array_column($dsThiSinh, 'maThiSinh'));
    $chiemCho = [];
    foreach (layThiSinhKhongPhuHopDiemThi($maDiemThi) as $r) {
        if (!isset($maCanPhan[$r['maThiSinh']])) $chiemCho[$r['maPhong']] = ($chiemCho[$r['maPhong']] ?? 0) + 1;
    }

    // 6.2: tổng sức chứa còn trống không đủ
    $tongSucChua = 0;
    foreach ($dsPhong as $p) $tongSucChua += max(0, (int)$p['sucChua'] - ($chiemCho[$p['maPhong']] ?? 0));
    if ($tongSucChua < $tongThiSinh) {
        $thieu = $tongThiSinh - $tongSucChua;
        throw new ExamException(EXAM_TB_SUC_CHUA_KHONG_DU, [
            "Tổng sức chứa còn trống của các phòng thi tại điểm thi ({$tongSucChua} chỗ) không đủ cho {$tongThiSinh} thí sinh.",
            "Số lượng chỗ còn thiếu: {$thieu} chỗ. Vui lòng tạo thêm phòng thi hoặc tăng sức chứa.",
        ]);
    }

    // Bước 7: lấp đầy lần lượt từng phòng theo sức chứa còn trống
    $phanBo = [];
    $tsIndex = 0;
    foreach ($dsPhong as $p) {
        $conTrong = max(0, (int)$p['sucChua'] - ($chiemCho[$p['maPhong']] ?? 0));
        $tsPhong = [];
        for ($i = 0; $i < $conTrong && $tsIndex < $tongThiSinh; $i++) {
            $ts = $dsThiSinh[$tsIndex++];
            $ts['phongDuKien'] = $p['maPhong'];
            $ts['tenPhongDuKien'] = $p['tenPhong'];
            $tsPhong[] = $ts;
        }
        $phanBo[$p['maPhong']] = [
            'phong' => $p,
            'soLuong' => count($tsPhong),
            'sucChua' => (int)$p['sucChua'],
            'chiemCho' => $chiemCho[$p['maPhong']] ?? 0,
            'thiSinh' => $tsPhong,
        ];
    }

    return [
        'maKyThi' => $maKyThi,
        'maDiemThi' => $maDiemThi,
        'tongThiSinh' => $tongThiSinh,
        'tongSucChua' => $tongSucChua,
        'dsPhong' => $dsPhong,
        'phanBo' => $phanBo,
    ];
}

/**
 * Bước 10: lưu kết quả phân phòng thi (1 transaction). Dữ liệu gửi lên được kiểm tra lại toàn bộ:
 * thí sinh phải thuộc danh sách cần phân của điểm thi, phòng phải thuộc điểm thi, không vượt sức chứa.
 * @param array<string,string> $phanBoMap [maThiSinh => maPhong]
 */
function luuKetQuaPhanPhong(string $maKyThi, string $maDiemThi, array $phanBoMap): array {
    $phanBoMap = array_filter($phanBoMap, fn($v, $k) => is_string($v) && trim($v) !== '' && is_string($k) && $k !== '', ARRAY_FILTER_USE_BOTH);
    if (empty($phanBoMap)) {
        throw new ExamException(EXAM_TB_LOI_LUU_PHAN_PHONG, ['Dữ liệu phân phòng thi trống.']);
    }

    // 9.1 bước 3 / 6.3: chỉ phân thí sinh phù hợp khu vực điểm thi
    $maCanPhan = array_flip(array_column(layDanhSachThiSinhPhuHopDiemThi($maKyThi, $maDiemThi), 'maThiSinh'));
    $khongPhuHop = array_values(array_diff(array_map('strval', array_keys($phanBoMap)), array_keys($maCanPhan)));
    if ($khongPhuHop) {
        throw new ExamException(EXAM_TB_DIEM_THI_KHONG_PHU_HOP, [
            'Thí sinh ' . implode(', ', array_slice($khongPhuHop, 0, 10)) . ' không thuộc danh sách cần phân phòng của điểm thi đã chọn.',
        ]);
    }

    $pdo = db();
    $soLuongLuu = 0;
    try {
        $pdo->beginTransaction();

        // Kiểm tra phòng thuộc điểm thi và sức chứa (tính cả thí sinh đang ở phòng mà không nằm trong lần phân này)
        $stPhong = $pdo->prepare('SELECT sucChua, maDiemThi FROM PhongThi WHERE maPhong = ? FOR UPDATE');
        $stDangO = $pdo->prepare('SELECT maThiSinh FROM PhanPhongThi WHERE maPhong = ? AND trangThai = "Đã phân" FOR UPDATE');
        foreach (array_count_values($phanBoMap) as $maPhong => $count) {
            $stPhong->execute([$maPhong]);
            $ph = $stPhong->fetch();
            if (!$ph || $ph['maDiemThi'] !== $maDiemThi) {
                throw new ExamException(EXAM_TB_LOI_LUU_PHAN_PHONG, ["Phòng {$maPhong} không thuộc điểm thi đã chọn."]);
            }
            $stDangO->execute([$maPhong]);
            $khac = count(array_filter($stDangO->fetchAll(PDO::FETCH_COLUMN), fn($ma) => !isset($phanBoMap[$ma])));
            if ($count + $khac > (int)$ph['sucChua']) {
                throw new ExamException(EXAM_TB_SUC_CHUA_KHONG_DU, [
                    "Phòng {$maPhong} sẽ có " . ($count + $khac) . " thí sinh, vượt quá sức chứa ({$ph['sucChua']} chỗ).",
                ]);
            }
        }

        $stMax = $pdo->query('SELECT MAX(CAST(SUBSTRING(maPhanPhong, 3) AS UNSIGNED)) FROM PhanPhongThi WHERE maPhanPhong LIKE "PP%" FOR UPDATE');
        $maxNum = (int)$stMax->fetchColumn();

        $stCheck = $pdo->prepare('SELECT maPhanPhong, maPhong FROM PhanPhongThi WHERE maThiSinh = ? AND trangThai = "Đã phân" FOR UPDATE');
        $stUpdate = $pdo->prepare('UPDATE PhanPhongThi SET maPhong = ?, ngayPhan = NOW() WHERE maPhanPhong = ?');
        $stInsert = $pdo->prepare('INSERT INTO PhanPhongThi (maPhanPhong, maThiSinh, maPhong, ngayPhan, trangThai) VALUES (?, ?, ?, NOW(), "Đã phân")');

        foreach ($phanBoMap as $maThiSinh => $maPhong) {
            $stCheck->execute([$maThiSinh]);
            $cur = $stCheck->fetch();
            if ($cur) {
                if ($cur['maPhong'] !== $maPhong) $stUpdate->execute([$maPhong, $cur['maPhanPhong']]);
            } else {
                $stInsert->execute([sprintf('PP%03d', ++$maxNum), $maThiSinh, $maPhong]);
            }
            $soLuongLuu++;
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e instanceof BusinessException) throw $e;
        error_log('luuKetQuaPhanPhong: ' . $e->getMessage());
        // 10.1: dữ liệu phân phòng chính thức không bị thay đổi (đã rollback)
        throw new ExamException(EXAM_TB_LOI_LUU_PHAN_PHONG, ['Kết quả chưa được ghi nhận. Dữ liệu phân phòng chính thức không bị thay đổi.']);
    }

    return [
        'soLuong' => $soLuongLuu,
        'maDiemThi' => $maDiemThi,
    ];
}

/**
 * Alternative 9.1: chuyển một thí sinh sang phòng khác còn chỗ.
 * Nếu chuyển sang điểm thi khác thì điểm thi mới phải cùng kỳ thi và cùng khu vực với trường THCS của thí sinh.
 */
function chuyenPhongThiSinh(string $maPhanPhong, string $maPhongMoi): void {
    $maPhanPhong = trim($maPhanPhong);
    $maPhongMoi = trim($maPhongMoi);

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare('SELECT pp.maThiSinh, pp.maPhong AS maPhongCu, p.maDiemThi, d.maKyThi, c.maKhuVuc AS khuVucThiSinh
                             FROM PhanPhongThi pp
                             JOIN PhongThi p ON p.maPhong = pp.maPhong
                             JOIN DiemThi d ON d.maDiemThi = p.maDiemThi
                             JOIN ThiSinh t ON t.maThiSinh = pp.maThiSinh
                             JOIN TruongTHCS c ON c.maTruongTHCS = t.maTruongTHCS
                             WHERE pp.maPhanPhong = ? AND pp.trangThai = "Đã phân"
                             FOR UPDATE');
        $st->execute([$maPhanPhong]);
        $pp = $st->fetch();
        if (!$pp) {
            throw new ExamException('Không tìm thấy thông tin phân phòng của thí sinh.');
        }
        if ($pp['maPhongCu'] === $maPhongMoi) {
            throw new ExamException('Phòng thi mới trùng với phòng thi hiện tại của thí sinh.');
        }

        $st = $pdo->prepare('SELECT p.sucChua, p.maDiemThi, d.maKyThi, COALESCE(c.maKhuVuc, t.maKhuVuc, "") AS maKhuVuc
                             FROM PhongThi p
                             JOIN DiemThi d ON d.maDiemThi = p.maDiemThi
                             LEFT JOIN TruongTHCS c ON c.maTruongTHCS = d.maTruongTHCS
                             LEFT JOIN TruongTHPT t ON t.maTruongTHPT = d.maTruongTHPT
                             WHERE p.maPhong = ? FOR UPDATE');
        $st->execute([$maPhongMoi]);
        $pm = $st->fetch();
        if (!$pm) {
            throw new ExamException('Phòng thi mới không tồn tại.');
        }
        if ($pm['maDiemThi'] !== $pp['maDiemThi']
            && ($pm['maKyThi'] !== $pp['maKyThi'] || ($pm['maKhuVuc'] !== '' && $pm['maKhuVuc'] !== $pp['khuVucThiSinh']))) {
            throw new ExamException(EXAM_TB_DIEM_THI_KHONG_PHU_HOP, [
                "Phòng {$maPhongMoi} thuộc điểm thi không phù hợp với khu vực trường THCS của thí sinh.",
            ]);
        }

        $st = $pdo->prepare('SELECT COUNT(*) FROM PhanPhongThi WHERE maPhong = ? AND trangThai = "Đã phân"');
        $st->execute([$maPhongMoi]);
        if ((int)$st->fetchColumn() >= (int)$pm['sucChua']) {
            throw new ExamException(EXAM_TB_SUC_CHUA_KHONG_DU, [
                "Phòng thi {$maPhongMoi} đã đủ {$pm['sucChua']} thí sinh, không thể chuyển thêm vào phòng này.",
            ]);
        }

        $st = $pdo->prepare('UPDATE PhanPhongThi SET maPhong = ?, ngayPhan = NOW() WHERE maPhanPhong = ?');
        $st->execute([$maPhongMoi, $maPhanPhong]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e instanceof BusinessException) throw $e;
        error_log('chuyenPhongThiSinh: ' . $e->getMessage());
        throw new ExamException('Không thể chuyển phòng thi cho thí sinh. Vui lòng thử lại.');
    }
}

/**
 * Hủy phân phòng cho một thí sinh.
 */
function huyPhanPhongThiSinh(string $maPhanPhong): void {
    $st = db()->prepare('UPDATE PhanPhongThi SET trangThai = "Đã hủy" WHERE maPhanPhong = ?');
    $st->execute([$maPhanPhong]);
}

// =============================================================================
// UC08: XEM PHÂN CÔNG RA ĐỀ THI (Hội đồng ra đề thi)
// =============================================================================

/**
 * Kỳ thi mà tài khoản được xem phân công ra đề (bước 2, 4).
 * - Ban Tuyển sinh, Quản trị: mọi kỳ thi.
 * - Hội đồng ra đề: kỳ thi của Hội đồng ra đề mà cán bộ gắn với tài khoản là thành viên.
 * @return list<string>|null null = không giới hạn
 */
function layPhamViKyThiRaDe(array $user): ?array {
    if (has_role($user, ['BAN_TUYEN_SINH', 'QUAN_TRI'])) return null;
    $maCanBo = (string)($user['maCanBo'] ?? '');
    if ($maCanBo === '') return [];
    $st = db()->prepare('SELECT DISTINCT h.maKyThi
                         FROM CanBo c
                         JOIN HoiDongThi h ON h.maHoiDongThi = c.maHoiDongThi
                         WHERE c.maCanBo = ? AND h.loaiHoiDong = "Ra đề"');
    $st->execute([$maCanBo]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

/** Bước 2: các kỳ thi có phân công ra đề thuộc phạm vi tài khoản. */
function timKyThiDuocPhanCong(?array $phamVi): array {
    if ($phamVi === []) return [];
    $sql = 'SELECT k.maKyThi, k.tenKyThi, k.thoiGianKetThuc, COUNT(*) AS soPhanCong
            FROM PhanCongRaDe pc JOIN KyThi k ON k.maKyThi = pc.maKyThi';
    $params = [];
    if ($phamVi !== null) {
        $sql .= ' WHERE pc.maKyThi IN (' . implode(',', array_fill(0, count($phamVi), '?')) . ')';
        $params = $phamVi;
    }
    $st = db()->prepare($sql . ' GROUP BY k.maKyThi, k.tenKyThi, k.thoiGianKetThuc ORDER BY k.maKyThi DESC');
    $st->execute($params);
    return $st->fetchAll();
}

/** Bước 4 / exception 4.1: kiểm tra quyền xem phân công của kỳ thi đã chọn. */
function kiemTraQuyenXemPhanCong(?array $phamVi, string $maKyThi): void {
    if ($phamVi !== null && !in_array($maKyThi, $phamVi, true)) {
        throw new ExamException(EXAM_TB_KHONG_DU_QUYEN, ['Tài khoản không thuộc Hội đồng ra đề thi của kỳ thi đã chọn nên không được cung cấp thông tin phân công.']);
    }
}

/**
 * Bước 5, 7: danh sách phân công ra đề (chỉ đọc). Lọc theo kỳ thi, cán bộ, môn thi.
 */
function layDanhSachPhanCongRaDe(string $maKyThi = '', ?string $maCanBo = null, ?string $maMonThi = null): array {
    $sql = 'SELECT pc.maPhanCong, pc.maKyThi, k.tenKyThi, pc.maMonThi, m.tenMonThi, m.thangDiem,
                   pc.maCanBo, c.hoTen AS tenCanBo, c.chucVu,
                   COALESCE(t.tenTruongTHCS, p.tenTruongTHPT, "") AS donViCongTac,
                   pc.thoiGianThucHien, pc.thongTinNhiemVu
            FROM PhanCongRaDe pc
            JOIN KyThi k ON k.maKyThi = pc.maKyThi
            JOIN MonThi m ON m.maMonThi = pc.maMonThi
            JOIN CanBo c ON c.maCanBo = pc.maCanBo
            LEFT JOIN TruongTHCS t ON t.maTruongTHCS = c.maTruongTHCS
            LEFT JOIN TruongTHPT p ON p.maTruongTHPT = c.maTruongTHPT
            WHERE 1=1';

    $params = [];
    if ($maKyThi !== '') {
        $sql .= ' AND pc.maKyThi = ?';
        $params[] = $maKyThi;
    }
    if ($maCanBo !== null && $maCanBo !== '') {
        $sql .= ' AND pc.maCanBo = ?';
        $params[] = $maCanBo;
    }
    if ($maMonThi !== null && $maMonThi !== '') {
        $sql .= ' AND pc.maMonThi = ?';
        $params[] = $maMonThi;
    }

    $sql .= ' ORDER BY k.maKyThi DESC, m.maMonThi, pc.maPhanCong';

    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Bước 5: gom phân công theo môn thi để hiển thị danh sách môn được phân công ra đề. */
function nhomPhanCongTheoMon(array $dsPhanCong): array {
    $mon = [];
    foreach ($dsPhanCong as $pc) {
        $k = $pc['maMonThi'];
        $mon[$k] ??= ['maMonThi' => $k, 'tenMonThi' => $pc['tenMonThi'], 'thangDiem' => $pc['thangDiem'], 'soNhiemVu' => 0, 'canBo' => []];
        $mon[$k]['soNhiemVu']++;
        $mon[$k]['canBo'][$pc['maCanBo']] = $pc['tenCanBo'];
    }
    return array_values($mon);
}
