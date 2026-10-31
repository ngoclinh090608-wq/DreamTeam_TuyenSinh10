<?php
declare(strict_types=1);
/**
 * Nghiệp vụ quản lý phòng thi, phân phòng thi và phân công ra đề thi (UC06, UC07, UC08).
 * Bảng dùng: KyThi, MonThi, DiemThi, PhongThi, PhanPhongThi, ThiSinh, TruongTHCS, PhanCongRaDe, CanBo.
 * Tên hàm bám theo thiết kế lớp trong báo cáo Tuần 07; câu thông báo bám nguyên văn Test Case Tuần 04.
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';

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
 * Tự động sinh mã phòng tiếp theo cho một điểm thi (vd P101, P102 -> P103).
 */
function sinhMaPhongTuDong(string $maDiemThi): string {
    $st = db()->prepare('SELECT maPhong FROM PhongThi WHERE maDiemThi = ? ORDER BY maPhong DESC LIMIT 1');
    $st->execute([$maDiemThi]);
    $last = (string)$st->fetchColumn();
    if ($last !== '' && preg_match('/^([A-Za-z]+)(\d+)$/', $last, $m)) {
        $prefix = $m[1];
        $num = (int)$m[2] + 1;
        $len = strlen($m[2]);
        return sprintf('%s%0' . $len . 'd', $prefix, $num);
    }
    // Gợi ý theo mã điểm thi: DT01 -> P101, DT02 -> P201
    if (preg_match('/(\d+)$/', $maDiemThi, $m)) {
        $prefixNum = (int)$m[1];
        return sprintf('P%d01', $prefixNum);
    }
    return 'P001';
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
        throw new ExamException(EXAM_TB_LOI_LUU_PHONG, [$e->getMessage()]);
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
 * Lấy danh sách thí sinh đủ điều kiện dự thi phù hợp với Điểm thi.
 * Quy tắc phân điểm thi:
 * - Điểm thi gắn với trường THCS (hoặc THPT) thuộc khu vực `maKhuVuc`.
 * - Thí sinh thuộc các trường THCS nằm trong cùng khu vực với điểm thi.
 * - Chỉ lấy thí sinh đã duyệt (dieuKienDuThi = 1, trangThaiDangKyDuThi = 'Đã duyệt').
 */
function layDanhSachThiSinhPhuHopDiemThi(string $maKyThi, string $maDiemThi): array {
    // Thông tin điểm thi và khu vực
    $st = db()->prepare('SELECT d.maDiemThi, d.tenDiemThi, d.maKyThi,
                                COALESCE(c.maKhuVuc, p.maKhuVuc, "") AS maKhuVuc,
                                k.tenKhuVuc
                         FROM DiemThi d
                         LEFT JOIN TruongTHCS c ON c.maTruongTHCS = d.maTruongTHCS
                         LEFT JOIN TruongTHPT p ON p.maTruongTHPT = d.maTruongTHPT
                         LEFT JOIN KhuVuc k ON k.maKhuVuc = COALESCE(c.maKhuVuc, p.maKhuVuc)
                         WHERE d.maDiemThi = ?');
    $st->execute([$maDiemThi]);
    $dt = $st->fetch();
    if (!$dt) {
        throw new ExamException('Điểm thi không tồn tại trong hệ thống.');
    }

    $maKhuVuc = (string)$dt['maKhuVuc'];

    // Lấy thí sinh thuộc các trường THCS cùng khu vực với điểm thi (nếu điểm thi có khu vực xác định)
    // Nếu điểm thi không có khu vực (chưa gán trường), lấy tất cả thí sinh hợp lệ của kỳ thi
    $sql = 'SELECT t.maThiSinh, t.maHoSo, t.hoTen, t.ngaySinh, t.soBaoDanh,
                   t.dieuKienDuThi, t.trangThaiDangKyDuThi,
                   c.maTruongTHCS, c.tenTruongTHCS, c.maKhuVuc, kv.tenKhuVuc,
                   pp.maPhanPhong, pp.maPhong, pt.tenPhong, dt.maDiemThi AS diemThiHienTai, dt.tenDiemThi AS tenDiemThiHienTai
            FROM ThiSinh t
            JOIN TruongTHCS c ON c.maTruongTHCS = t.maTruongTHCS
            JOIN KhuVuc kv ON kv.maKhuVuc = c.maKhuVuc
            LEFT JOIN PhanPhongThi pp ON pp.maThiSinh = t.maThiSinh AND pp.trangThai = "Đã phân"
            LEFT JOIN PhongThi pt ON pt.maPhong = pp.maPhong
            LEFT JOIN DiemThi dt ON dt.maDiemThi = pt.maDiemThi
            WHERE t.dieuKienDuThi = 1 AND t.trangThaiDangKyDuThi = "Đã duyệt"';

    $params = [];
    if ($maKhuVuc !== '') {
        $sql .= ' AND c.maKhuVuc = ?';
        $params[] = $maKhuVuc;
    }
    $sql .= ' ORDER BY t.soBaoDanh, t.maThiSinh';

    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/**
 * Kiểm tra điều kiện phân phòng và tính toán phân phòng tự động.
 */
function kiemTraVaTinhToanPhanPhong(string $maKyThi, string $maDiemThi): array {
    $maKyThi = trim($maKyThi);
    $maDiemThi = trim($maDiemThi);

    if ($maKyThi === '' || $maDiemThi === '') {
        throw new ExamException(EXAM_TB_CHUA_DU_DIEU_KIEN_PHAN_PHONG, [
            'Vui lòng chọn đầy đủ Kỳ thi và Điểm thi cần phân phòng.',
        ]);
    }

    // 1. Kiểm tra phòng thi tại điểm thi
    $dsPhong = exam_ds_phong_thi($maDiemThi);
    if (empty($dsPhong)) {
        throw new ExamException(EXAM_TB_CHUA_DU_DIEU_KIEN_PHAN_PHONG, [
            'Điểm thi được chọn chưa có phòng thi nào. Vui lòng tạo phòng thi trước khi phân phòng.',
        ]);
    }

    $tongSucChua = array_sum(array_column($dsPhong, 'sucChua'));

    // 2. Lấy danh sách thí sinh phù hợp
    $dsThiSinh = layDanhSachThiSinhPhuHopDiemThi($maKyThi, $maDiemThi);
    $tongThiSinh = count($dsThiSinh);

    if ($tongThiSinh === 0) {
        throw new ExamException('Không có thí sinh đủ điều kiện dự thi thuộc khu vực điểm thi này.');
    }

    // 3. Kiểm tra sức chứa
    if ($tongSucChua < $tongThiSinh) {
        $thieu = $tongThiSinh - $tongSucChua;
        throw new ExamException(EXAM_TB_SUC_CHUA_KHONG_DU, [
            "Tổng sức chứa của các phòng thi tại điểm thi ({$tongSucChua} chỗ) không đủ cho {$tongThiSinh} thí sinh.",
            "Số lượng chỗ còn thiếu: {$thieu} chỗ. Vui lòng tạo thêm phòng thi hoặc tăng sức chứa.",
        ]);
    }

    // 4. Thuật toán phân phòng tự động: lấp đầy từng phòng theo sức chứa
    $phanBo = []; // [maPhong => [phongInfo, dsThiSinh]]
    $tsIndex = 0;

    foreach ($dsPhong as $p) {
        $phongKey = $p['maPhong'];
        $sucChua = (int)$p['sucChua'];
        $tsPhong = [];

        for ($i = 0; $i < $sucChua && $tsIndex < $tongThiSinh; $i++) {
            $ts = $dsThiSinh[$tsIndex];
            $ts['phongDuKien'] = $p['maPhong'];
            $ts['tenPhongDuKien'] = $p['tenPhong'];
            $tsPhong[] = $ts;
            $tsIndex++;
        }

        $phanBo[$phongKey] = [
            'phong' => $p,
            'soLuong' => count($tsPhong),
            'sucChua' => $sucChua,
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
 * Lưu kết quả phân phòng thi vào CSDL (trong 1 Transaction).
 * @param array $phanBo Danh sách phân phòng [maThiSinh => maPhong]
 */
function luuKetQuaPhanPhong(string $maKyThi, string $maDiemThi, array $phanBoMap): array {
    if (empty($phanBoMap)) {
        throw new ExamException('Dữ liệu phân phòng thi trống.');
    }

    $pdo = db();
    $soLuongLuu = 0;

    try {
        $pdo->beginTransaction();

        // Kiểm tra sức chứa từng phòng trong map
        $phongCounts = array_count_values($phanBoMap);
        foreach ($phongCounts as $maPhong => $count) {
            $st = $pdo->prepare('SELECT sucChua FROM PhongThi WHERE maPhong = ? FOR UPDATE');
            $st->execute([$maPhong]);
            $sc = (int)$st->fetchColumn();
            if ($count > $sc) {
                throw new ExamException(EXAM_TB_SUC_CHUA_KHONG_DU, [
                    "Phòng {$maPhong} được phân {$count} thí sinh, vượt quá sức chứa ({$sc} chỗ).",
                ]);
            }
        }

        // Tạo mã phân phòng duy nhất
        $stMax = $pdo->query('SELECT MAX(CAST(SUBSTRING(maPhanPhong, 3) AS UNSIGNED)) FROM PhanPhongThi WHERE maPhanPhong LIKE "PP%"');
        $maxNum = (int)$stMax->fetchColumn();

        $stCheck = $pdo->prepare('SELECT maPhanPhong FROM PhanPhongThi WHERE maThiSinh = ? AND trangThai = "Đã phân" FOR UPDATE');
        $stUpdate = $pdo->prepare('UPDATE PhanPhongThi SET maPhong = ?, ngayPhan = NOW(), trangThai = "Đã phân" WHERE maPhanPhong = ?');
        $stInsert = $pdo->prepare('INSERT INTO PhanPhongThi (maPhanPhong, maThiSinh, maPhong, ngayPhan, trangThai) VALUES (?, ?, ?, NOW(), "Đã phân")');

        foreach ($phanBoMap as $maThiSinh => $maPhong) {
            $stCheck->execute([$maThiSinh]);
            $cur = $stCheck->fetch();

            if ($cur) {
                // Cập nhật phòng thi mới nếu khác
                $stUpdate->execute([$maPhong, $cur['maPhanPhong']]);
            } else {
                $maxNum++;
                $maPP = sprintf('PP%03d', $maxNum);
                $stInsert->execute([$maPP, $maThiSinh, $maPhong]);
            }
            $soLuongLuu++;
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('luuKetQuaPhanPhong: ' . $e->getMessage());
        if ($e instanceof BusinessException) throw $e;
        throw new ExamException(EXAM_TB_LOI_LUU_PHAN_PHONG, [$e->getMessage()]);
    }

    return [
        'soLuong' => $soLuongLuu,
        'maDiemThi' => $maDiemThi,
    ];
}

/**
 * Chuyển một thí sinh từ phòng hiện tại sang phòng mới còn chỗ.
 */
function chuyenPhongThiSinh(string $maPhanPhong, string $maPhongMoi, string $lyDo = ''): void {
    $maPhanPhong = trim($maPhanPhong);
    $maPhongMoi = trim($maPhongMoi);

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare('SELECT pp.maThiSinh, pp.maPhong AS maPhongCu, p.maDiemThi
                             FROM PhanPhongThi pp
                             JOIN PhongThi p ON p.maPhong = pp.maPhong
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

        // Kiểm tra phòng mới và sức chứa
        $st = $pdo->prepare('SELECT sucChua, maDiemThi FROM PhongThi WHERE maPhong = ? FOR UPDATE');
        $st->execute([$maPhongMoi]);
        $pm = $st->fetch();
        if (!$pm) {
            throw new ExamException('Phòng thi mới không tồn tại.');
        }

        $st = $pdo->prepare('SELECT COUNT(*) FROM PhanPhongThi WHERE maPhong = ? AND trangThai = "Đã phân"');
        $st->execute([$maPhongMoi]);
        $daPhan = (int)$st->fetchColumn();

        if ($daPhan >= (int)$pm['sucChua']) {
            throw new ExamException(EXAM_TB_SUC_CHUA_KHONG_DU, [
                "Phòng thi {$maPhongMoi} đã đủ {$pm['sucChua']} thí sinh, không thể chuyển thêm vào phòng này.",
            ]);
        }

        $st = $pdo->prepare('UPDATE PhanPhongThi SET maPhong = ?, ngayPhan = NOW() WHERE maPhanPhong = ?');
        $st->execute([$maPhongMoi, $maPhanPhong]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('chuyenPhongThiSinh: ' . $e->getMessage());
        if ($e instanceof BusinessException) throw $e;
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
 * Lấy danh sách phân công ra đề thi (chỉ đọc, phục vụ đối chiếu và giám sát).
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

