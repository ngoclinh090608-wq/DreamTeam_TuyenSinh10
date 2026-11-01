<?php
declare(strict_types=1);

/**
 * Nghiệp vụ Đăng ký tuyển sinh lớp 10 và Quản lý nguyện vọng (NV1, NV2, NV3) phía Học sinh.
 * Sử dụng PDO, Prepared Statements, Transactions và kiểm tra CSRF/Quyền sở hữu.
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';

// Các thông báo nghiệp vụ nguyên văn tiếng Việt
const TB_NV_THANH_CONG = 'Đăng ký nguyện vọng tuyển sinh lớp 10 thành công.';
const TB_NV_CAP_NHAT_THANH_CONG = 'Cập nhật nguyện vọng tuyển sinh lớp 10 thành công.';
const TB_NV_KHONG_CO_QUYEN = 'Bạn không có quyền thực hiện chức năng này.';
const TB_NV_KHONG_TIM_THAY_THI_SINH = 'Không tìm thấy thông tin hồ sơ thí sinh tương ứng với tài khoản.';
const TB_NV_CHUA_DU_DIEU_KIEN = 'Hồ sơ thí sinh chưa đủ điều kiện tham gia tuyển sinh lớp 10.';
const TB_NV_HO_SO_TU_CHOI = 'Hồ sơ tuyển sinh của bạn đã bị từ chối, không thể đăng ký nguyện vọng.';
const TB_NV_HET_HAN = 'Đã hết thời hạn đăng ký và điều chỉnh nguyện vọng tuyển sinh.';
const TB_NV_CHUA_MO = 'Thời gian đăng ký nguyện vọng tuyển sinh chưa bắt đầu.';
const TB_NV_THIEU_NV1 = 'Nguyện vọng 1 là bắt buộc. Vui lòng chọn trường THPT cho Nguyện vọng 1.';
const TB_NV_TRUNG_TRUONG = 'Các nguyện vọng không được chọn trùng trường THPT.';
const TB_NV_TRUONG_KHONG_HOP_LE = 'Trường THPT được chọn không hợp lệ hoặc không tồn tại trong hệ thống.';
const TB_NV_THU_TU_KHONG_HOP_LE = 'Thứ tự nguyện vọng không hợp lệ.';
const TB_NV_NGAT_QUANG = 'Vui lòng chọn các nguyện vọng theo thứ tự liên tục (không bỏ trống nguyện vọng trước).';
const TB_NV_VUOT_SO_LUONG = 'Số lượng nguyện vọng vượt quá quy định cho phép (tối đa %d nguyện vọng).';

/**
 * Lấy quy định nguyện vọng hiệu lực (mới nhất hoặc theo năm).
 */
function layQuyDinhNguyenVong(?int $namTuyenSinh = null): ?array {
    $sql = 'SELECT maQuyDinh, namTuyenSinh, soLuongNguyenVongToiDa, thoiGianBatDau, thoiGianKetThuc, ghiChu FROM QuyDinhNguyenVong';
    $params = [];
    if ($namTuyenSinh !== null) {
        $sql .= ' WHERE namTuyenSinh = ? ORDER BY thoiGianKetThuc DESC LIMIT 1';
        $params[] = $namTuyenSinh;
    } else {
        $sql .= ' ORDER BY namTuyenSinh DESC, thoiGianKetThuc DESC LIMIT 1';
    }
    $st = db()->prepare($sql);
    $st->execute($params);
    $qd = $st->fetch();
    if (!$qd) return null;

    $now = time();
    $start = strtotime((string)$qd['thoiGianBatDau']);
    $end = strtotime((string)$qd['thoiGianKetThuc']);

    $qd['soLuongNguyenVongToiDa'] = (int)$qd['soLuongNguyenVongToiDa'];
    $qd['namTuyenSinh'] = (int)$qd['namTuyenSinh'];
    $qd['is_open'] = ($now >= $start && $now <= $end);
    $qd['is_expired'] = ($now > $end);
    $qd['is_upcoming'] = ($now < $start);
    $qd['start_formatted'] = date('d/m/Y H:i', $start);
    $qd['end_formatted'] = date('d/m/Y H:i', $end);

    return $qd;
}

/**
 * Lấy thông tin cá nhân và tình trạng hồ sơ thí sinh theo mã thí sinh từ phiên đăng nhập.
 */
function layThongTinThiSinhDangKy(string $maThiSinh): ?array {
    if (trim($maThiSinh) === '') return null;
    $sql = 'SELECT t.maThiSinh, t.maHoSo, t.hoTen, t.ngaySinh, t.soBaoDanh, t.dieuKienDuThi, t.trangThaiDangKyDuThi,
                   c.maTruongTHCS, c.tenTruongTHCS, c.diaChi AS diaChiTHCS, c.quanHuyen AS quanHuyenTHCS,
                   k.maKhuVuc, k.tenKhuVuc,
                   dot.maDot, dot.tenDot, dot.namTuyenSinh
            FROM ThiSinh t
            JOIN TruongTHCS c ON c.maTruongTHCS = t.maTruongTHCS
            JOIN KhuVuc k ON k.maKhuVuc = c.maKhuVuc
            LEFT JOIN DotTuyenSinh dot ON dot.namTuyenSinh = (
                SELECT MAX(d.namTuyenSinh) FROM DotTuyenSinh d
            )
            WHERE t.maThiSinh = ?';
    $st = db()->prepare($sql);
    $st->execute([$maThiSinh]);
    $ts = $st->fetch();
    if (!$ts) return null;

    $ts['dieuKienDuThi'] = (int)$ts['dieuKienDuThi'];
    $ts['ngaySinhFormatted'] = date('d/m/Y', strtotime((string)$ts['ngaySinh']));
    
    // Đủ điều kiện khi dieuKienDuThi = 1 và hồ sơ không bị từ chối
    $ts['duDieuKien'] = ($ts['dieuKienDuThi'] === 1 && $ts['trangThaiDangKyDuThi'] !== 'Từ chối');

    return $ts;
}

/**
 * Lấy danh sách tất cả các trường THPT và thông tin chỉ tiêu tuyển sinh công bố.
 */
function layDanhSachTruongTHPT(?string $maDot = null): array {
    $sql = 'SELECT p.maTruongTHPT, p.tenTruongTHPT, p.maKhuVuc, k.tenKhuVuc,
                   i.maThongTin, i.chiTieuTuyenSinh, i.heDaoTao, i.phuongThucTuyenSinh, i.hocPhi, i.noiDungChiTiet
            FROM TruongTHPT p
            JOIN KhuVuc k ON k.maKhuVuc = p.maKhuVuc
            LEFT JOIN ThongTinTuyenSinh i ON i.maTruongTHPT = p.maTruongTHPT AND i.trangThaiCongBo = \'Đã công bố\'';
    $params = [];
    if ($maDot !== null) {
        $sql .= ' AND i.maDot = ?';
        $params[] = $maDot;
    }
    $sql .= ' ORDER BY p.maTruongTHPT ASC';
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/**
 * Lấy danh sách nguyện vọng hiện có của thí sinh.
 * Trả về mảng có key là thuTuNguyenVong (1, 2, 3).
 */
function layDanhSachNguyenVongThiSinh(string $maThiSinh): array {
    if (trim($maThiSinh) === '') return [];
    $sql = 'SELECT n.maNguyenVong, n.maThiSinh, n.maTruongTHPT, n.maQuyDinh, n.thuTuNguyenVong,
                   n.ngayDangKy, n.trangThaiNguyenVong,
                   p.tenTruongTHPT, p.maKhuVuc, k.tenKhuVuc,
                   i.chiTieuTuyenSinh, i.heDaoTao, i.phuongThucTuyenSinh, i.hocPhi
            FROM NguyenVong n
            JOIN TruongTHPT p ON p.maTruongTHPT = n.maTruongTHPT
            JOIN KhuVuc k ON k.maKhuVuc = p.maKhuVuc
            LEFT JOIN ThongTinTuyenSinh i ON i.maTruongTHPT = p.maTruongTHPT AND i.trangThaiCongBo = \'Đã công bố\'
            WHERE n.maThiSinh = ?
            ORDER BY n.thuTuNguyenVong ASC';
    $st = db()->prepare($sql);
    $st->execute([$maThiSinh]);
    $wishes = [];
    foreach ($st->fetchAll() as $row) {
        $wishes[(int)$row['thuTuNguyenVong']] = $row;
    }
    return $wishes;
}

/**
 * Kiểm tra tính hợp lệ của việc đăng ký/sửa nguyện vọng.
 * @throws BusinessException khi có lỗi nghiệp vụ
 */
function kiemTraHopLeNguyenVong(array $account, array $dsTruongChon, bool $boQuaThoiHan = false): array {
    // 1. Kiểm tra vai trò tài khoản
    if (($account['vaiTro'] ?? '') !== 'HOC_SINH' || empty($account['maThiSinh'])) {
        throw new BusinessException(TB_NV_KHONG_CO_QUYEN);
    }
    $maThiSinh = (string)$account['maThiSinh'];

    // 2. Kiểm tra hồ sơ thí sinh
    $thiSinh = layThongTinThiSinhDangKy($maThiSinh);
    if (!$thiSinh) {
        throw new BusinessException(TB_NV_KHONG_TIM_THAY_THI_SINH);
    }
    if ($thiSinh['trangThaiDangKyDuThi'] === 'Từ chối') {
        throw new BusinessException(TB_NV_HO_SO_TU_CHOI);
    }
    if ($thiSinh['dieuKienDuThi'] !== 1) {
        throw new BusinessException(TB_NV_CHUA_DU_DIEU_KIEN);
    }

    // 3. Kiểm tra quy định và thời hạn đăng ký
    $quyDinh = layQuyDinhNguyenVong($thiSinh['namTuyenSinh'] ?? null);
    if (!$quyDinh) {
        throw new BusinessException('Hệ thống chưa thiết lập quy định thời gian đăng ký nguyện vọng.');
    }

    if (!$boQuaThoiHan) {
        if ($quyDinh['is_upcoming']) {
            throw new BusinessException(TB_NV_CHUA_MO . ' Thời gian mở: ' . $quyDinh['start_formatted']);
        }
        if ($quyDinh['is_expired']) {
            throw new BusinessException(TB_NV_HET_HAN . ' Hạn chót đã kết thúc vào: ' . $quyDinh['end_formatted']);
        }
    }

    // 4. Chuẩn hóa và kiểm tra danh sách trường chọn
    $maxWishes = (int)$quyDinh['soLuongNguyenVongToiDa'];
    $normalized = [];

    // Nhận dữ liệu từ form: mảng 1-indexed (wishes[1]) hoặc 0-indexed (wishes[0])
    $isZeroIndexed = array_key_exists(0, $dsTruongChon);
    for ($i = 1; $i <= $maxWishes; $i++) {
        $k = $isZeroIndexed ? ($i - 1) : $i;
        $val = $dsTruongChon[$k] ?? '';
        $normalized[$i] = is_string($val) ? trim($val) : '';
    }

    // NV1 là bắt buộc
    if (empty($normalized[1])) {
        throw new BusinessException(TB_NV_THIEU_NV1);
    }

    // Lọc ra các nguyện vọng có chọn trường
    $activeWishes = [];
    foreach ($normalized as $thuTu => $maTruong) {
        if ($maTruong !== '') {
            $activeWishes[$thuTu] = $maTruong;
        }
    }

    // Kiểm tra không ngắt quãng (nếu NV3 có mà NV2 rỗng -> lỗi)
    $hasGap = false;
    $lastSet = 0;
    foreach ($normalized as $thuTu => $maTruong) {
        if ($maTruong !== '') {
            if ($lastSet !== 0 && $thuTu > $lastSet + 1) {
                $hasGap = true;
                break;
            }
            $lastSet = $thuTu;
        }
    }
    if ($hasGap) {
        throw new BusinessException(TB_NV_NGAT_QUANG);
    }

    // Kiểm tra không vượt quá số lượng tối đa
    if (count($activeWishes) > $maxWishes) {
        throw new BusinessException(sprintf(TB_NV_VUOT_SO_LUONG, $maxWishes));
    }

    // Kiểm tra trùng trường giữa các nguyện vọng
    $distinctSchools = array_unique(array_values($activeWishes));
    if (count($distinctSchools) !== count($activeWishes)) {
        throw new BusinessException(TB_NV_TRUNG_TRUONG);
    }

    // 5. Kiểm tra trường THPT có tồn tại trong database
    $truongHopLe = array_column(layDanhSachTruongTHPT(), 'maTruongTHPT');
    foreach ($activeWishes as $thuTu => $maTruong) {
        if (!in_array($maTruong, $truongHopLe, true)) {
            throw new BusinessException(TB_NV_TRUONG_KHONG_HOP_LE . " (Mã trường: {$maTruong})");
        }
    }

    return [
        'thiSinh' => $thiSinh,
        'quyDinh' => $quyDinh,
        'activeWishes' => $activeWishes,
    ];
}

/**
 * Lưu đăng ký hoặc cập nhật nguyện vọng cho học sinh.
 * Sử dụng Transaction, chống nhân bản bản ghi, cập nhật bản ghi cũ nếu đã có.
 */
function luuNguyenVong(array $account, array $dsTruongChon, bool $boQuaThoiHan = false): array {
    $validation = kiemTraHopLeNguyenVong($account, $dsTruongChon, $boQuaThoiHan);
    $thiSinh = $validation['thiSinh'];
    $quyDinh = $validation['quyDinh'];
    $activeWishes = $validation['activeWishes'];
    $maThiSinh = (string)$thiSinh['maThiSinh'];
    $maQuyDinh = (string)$quyDinh['maQuyDinh'];

    $pdo = db();
    $needsTx = !$pdo->inTransaction();
    if ($needsTx) {
        $pdo->beginTransaction();
    }
    try {
        // Khóa hàng thí sinh
        $st = $pdo->prepare('SELECT maThiSinh FROM ThiSinh WHERE maThiSinh = ? FOR UPDATE');
        $st->execute([$maThiSinh]);
        if (!$st->fetchColumn()) {
            throw new BusinessException(TB_NV_KHONG_TIM_THAY_THI_SINH);
        }

        // Lấy các bản ghi nguyện vọng hiện có của thí sinh kèm khóa FOR UPDATE
        $st = $pdo->prepare('SELECT maNguyenVong, thuTuNguyenVong, maTruongTHPT FROM NguyenVong WHERE maThiSinh = ? FOR UPDATE');
        $st->execute([$maThiSinh]);
        $existingRows = [];
        foreach ($st->fetchAll() as $row) {
            $existingRows[(int)$row['thuTuNguyenVong']] = $row;
        }

        $isUpdate = !empty($existingRows);

        // Lưu từng nguyện vọng (1, 2, 3)
        foreach ($activeWishes as $thuTu => $maTruong) {
            if (isset($existingRows[$thuTu])) {
                // ĐÃ CÓ BẢN GHI: Cập nhật trường đã chọn, giữ nguyên maNguyenVong để không phá khóa ngoại KetQuaXetTuyen
                $stUp = $pdo->prepare('UPDATE NguyenVong SET maTruongTHPT = ?, maQuyDinh = ?, ngayDangKy = NOW(), trangThaiNguyenVong = \'Chờ xử lý\' WHERE maThiSinh = ? AND thuTuNguyenVong = ?');
                $stUp->execute([$maTruong, $maQuyDinh, $maThiSinh, $thuTu]);
            } else {
                // CHƯA CÓ BẢN GHI: Tạo mới maNguyenVong dạng duy nhất không quá 20 ký tự
                $newMaNV = 'NV_' . $maThiSinh . '_' . $thuTu;
                // Nếu vượt quá 20 ký tự hoặc đã tồn tại ở thí sinh khác (vô cùng hiếm), sinh mã ngẫu nhiên an toàn
                if (strlen($newMaNV) > 20) {
                    $newMaNV = 'NV' . substr(md5($maThiSinh . '_' . $thuTu . '_' . microtime()), 0, 16);
                }
                // Đảm bảo không trùng PK
                $checkSt = $pdo->prepare('SELECT COUNT(*) FROM NguyenVong WHERE maNguyenVong = ?');
                $checkSt->execute([$newMaNV]);
                if ((int)$checkSt->fetchColumn() > 0) {
                    $newMaNV = 'NV' . substr(md5(uniqid((string)$thuTu, true)), 0, 16);
                }

                $stIn = $pdo->prepare('INSERT INTO NguyenVong (maNguyenVong, maThiSinh, maTruongTHPT, maQuyDinh, thuTuNguyenVong, ngayDangKy, trangThaiNguyenVong) VALUES (?, ?, ?, ?, ?, NOW(), \'Chờ xử lý\')');
                $stIn->execute([$newMaNV, $maThiSinh, $maTruong, $maQuyDinh, $thuTu]);
            }
        }

        // Nếu thí sinh giảm số lượng nguyện vọng (ví dụ trước đây có NV3 nhưng giờ không chọn NV3 nữa)
        // Kiểm tra xem NV có liên kết với KetQuaXetTuyen chưa, nếu chưa có thì có thể xóa, nếu đã có thì giữ nguyên hoặc cập nhật
        $maxWishes = (int)$quyDinh['soLuongNguyenVongToiDa'];
        for ($i = 1; $i <= $maxWishes; $i++) {
            if (!isset($activeWishes[$i]) && isset($existingRows[$i])) {
                $maNVCanXoa = $existingRows[$i]['maNguyenVong'];
                $stKq = $pdo->prepare('SELECT COUNT(*) FROM KetQuaXetTuyen WHERE maNguyenVong = ?');
                $stKq->execute([$maNVCanXoa]);
                $coKetQua = (int)$stKq->fetchColumn() > 0;
                if (!$coKetQua) {
                    $stDel = $pdo->prepare('DELETE FROM NguyenVong WHERE maNguyenVong = ?');
                    $stDel->execute([$maNVCanXoa]);
                }
            }
        }

        if ($needsTx) {
            $pdo->commit();
        }

        return [
            'success' => true,
            'message' => $isUpdate ? TB_NV_CAP_NHAT_THANH_CONG : TB_NV_THANH_CONG,
            'wishes' => layDanhSachNguyenVongThiSinh($maThiSinh),
        ];
    } catch (Throwable $e) {
        if ($needsTx && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}


require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';

/**
 * Danh mục thông tin lớp thường và lớp chuyên của các trường THPT
 */
function aspiration_school_tracks(): array {
    return [
        'THPT_1' => [
            'name' => 'Trường THPT Sao Mai',
            'has_specialized' => true,
            'tracks' => [
                'Thường' => 'Lớp thường (Đại trà)',
                'Chuyên Toán' => 'Lớp Chuyên Toán',
                'Chuyên Tin học' => 'Lớp Chuyên Tin học',
                'Chuyên Vật lý' => 'Lớp Chuyên Vật lý',
                'Chuyên Hóa học' => 'Lớp Chuyên Hóa học',
                'Chuyên Ngữ văn' => 'Lớp Chuyên Ngữ văn',
                'Chuyên Tiếng Anh' => 'Lớp Chuyên Tiếng Anh',
            ],
        ],
        'THPT_2' => [
            'name' => 'Trường THPT Bình Minh',
            'has_specialized' => true,
            'tracks' => [
                'Thường' => 'Lớp thường (Đại trà)',
                'Chuyên Toán' => 'Lớp Chuyên Toán',
                'Chuyên Tiếng Anh' => 'Lớp Chuyên Tiếng Anh',
                'Chuyên Ngữ văn' => 'Lớp Chuyên Ngữ văn',
            ],
        ],
        'THPT_3' => [
            'name' => 'Trường THPT Hòa An',
            'has_specialized' => false,
            'tracks' => [
                'Thường' => 'Lớp thường (Đại trà)',
            ],
        ],
        'THPT_4' => [
            'name' => 'Trường THPT Ánh Dương',
            'has_specialized' => false,
            'tracks' => [
                'Thường' => 'Lớp thường (Đại trà)',
            ],
        ],
        'THPT_5' => [
            'name' => 'Trường THPT Trần Quốc Khải',
            'has_specialized' => false,
            'tracks' => [
                'Thường' => 'Lớp thường (Đại trà)',
            ],
        ],
    ];
}

function aspiration_can_update(array $account, string $owner): bool {
    $studentId = $account['maThiSinh'] ?? null;
    return ($account['vaiTro'] ?? null) === 'HOC_SINH'
        && is_string($studentId)
        && $studentId !== ''
        && hash_equals($studentId, $owner);
}

/**
 * Lấy danh sách trường THPT kèm thông tin khu vực và hệ đào tạo
 */
function aspiration_get_schools(): array {
    $st = db()->query('SELECT t.maTruongTHPT, t.tenTruongTHPT, t.maKhuVuc, k.tenKhuVuc
                       FROM TruongTHPT t
                       LEFT JOIN KhuVuc k ON k.maKhuVuc = t.maKhuVuc
                       ORDER BY t.maTruongTHPT');
    $rows = $st->fetchAll();
    $tracksInfo = aspiration_school_tracks();
    foreach ($rows as &$r) {
        $info = $tracksInfo[$r['maTruongTHPT']] ?? null;
        $r['has_specialized'] = $info['has_specialized'] ?? false;
        $r['available_tracks'] = $info['tracks'] ?? ['Thường' => 'Lớp thường (Đại trà)'];
    }
    return $rows;
}

/**
 * Lấy quy định đăng ký nguyện vọng hiện hành
 */
function aspiration_get_regulation(?int $nam = null): ?array {
    $pdo = db();
    if ($nam !== null) {
        $st = $pdo->prepare('SELECT * FROM QuyDinhNguyenVong WHERE namTuyenSinh = ? LIMIT 1');
        $st->execute([$nam]);
    } else {
        $st = $pdo->query('SELECT * FROM QuyDinhNguyenVong ORDER BY namTuyenSinh DESC, maQuyDinh DESC LIMIT 1');
    }
    $reg = $st->fetch();
    if (!$reg) return null;

    $now = new DateTimeImmutable();
    $start = new DateTimeImmutable($reg['thoiGianBatDau']);
    $end = new DateTimeImmutable($reg['thoiGianKetThuc']);
    $reg['is_started'] = $now >= $start;
    $reg['is_expired'] = $now > $end;
    $reg['is_open'] = $reg['is_started'] && !$reg['is_expired'];
    return $reg;
}

/**
 * Lấy thông tin học sinh và kiểm tra tiền điều kiện
 */
function aspiration_get_candidate(string $maThiSinh): ?array {
    $st = db()->prepare('
        SELECT t.maThiSinh, t.maHoSo, t.hoTen, t.ngaySinh, t.soBaoDanh,
               t.dieuKienDuThi, t.trangThaiDangKyDuThi, t.maTruongTHCS,
               c.tenTruongTHCS
        FROM ThiSinh t
        LEFT JOIN TruongTHCS c ON c.maTruongTHCS = t.maTruongTHCS
        WHERE t.maThiSinh = ?
    ');
    $st->execute([$maThiSinh]);
    $student = $st->fetch();
    if (!$student) return null;

    // Lấy thông tin kết quả học tập lớp 9
    $stGrade9 = db()->prepare('SELECT diemTrungBinh, hocLuc, hanhKiem FROM HocTapTHCS WHERE maThiSinh = ? AND lop = 9');
    $stGrade9->execute([$maThiSinh]);
    $g9 = $stGrade9->fetch() ?: ['diemTrungBinh' => null, 'hocLuc' => '', 'hanhKiem' => ''];
    $student['grade9'] = $g9;

    // Kiểm tra tiêu chuẩn đăng ký chuyên (Hạnh kiểm Tốt/Khá, Học lực Tốt/Giỏi/Khá)
    $hl = mb_strtolower(trim((string)$g9['hocLuc']), 'UTF-8');
    $hk = mb_strtolower(trim((string)$g9['hanhKiem']), 'UTF-8');
    $qualifiesAcademic = in_array($hl, ['tốt', 'giỏi', 'khá'], true);
    $qualifiesConduct = in_array($hk, ['tốt', 'khá'], true);
    $student['eligible_specialized'] = $qualifiesAcademic && $qualifiesConduct;
    $student['specialized_reason'] = $student['eligible_specialized']
        ? 'Đủ điều kiện đăng ký lớp chuyên (Học lực và Hạnh kiểm lớp 9 đạt loại Khá trở lên).'
        : 'Chưa đủ điều kiện đăng ký lớp chuyên (Yêu cầu Học lực và Hạnh kiểm lớp 9 đạt loại Khá trở lên).';

    // Tiền điều kiện 2: Đã đăng ký dự thi lớp 10 và được duyệt
    $student['registered_exam_ok'] = ((int)$student['dieuKienDuThi'] === 1 && $student['trangThaiDangKyDuThi'] === 'Đã duyệt');

    return $student;
}

/**
 * Lấy danh sách nguyện vọng hiện có của học sinh
 */
function aspiration_get_wishes(string $maThiSinh): array {
    $st = db()->prepare('
        SELECT nv.maNguyenVong, nv.maThiSinh, nv.maTruongTHPT, nv.maQuyDinh,
               nv.thuTuNguyenVong, nv.ngayDangKy, nv.trangThaiNguyenVong,
               COALESCE(nv.lopChuyen, "Thường") as lopChuyen,
               t.tenTruongTHPT, t.maKhuVuc, k.tenKhuVuc
        FROM NguyenVong nv
        JOIN TruongTHPT t ON t.maTruongTHPT = nv.maTruongTHPT
        LEFT JOIN KhuVuc k ON k.maKhuVuc = t.maKhuVuc
        WHERE nv.maThiSinh = ?
        ORDER BY nv.thuTuNguyenVong ASC
    ');
    $st->execute([$maThiSinh]);
    return $st->fetchAll();
}

/**
 * Kiểm tra tính hợp lệ của danh sách nguyện vọng (Basic Flow 4 & Exception Flow 4.1)
 *
 * @throws BusinessException khi có vi phạm quy định
 */
function aspiration_validate(string $maThiSinh, array $rawWishes, array $regulation, array $candidate): array {
    // 1. Kiểm tra tiền điều kiện
    if (!$candidate['registered_exam_ok']) {
        throw new BusinessException('Học sinh chưa được phê duyệt điều kiện dự thi lớp 10 nên chưa thể đăng ký/điều chỉnh nguyện vọng.');
    }
    if (empty($regulation['is_open'])) {
        throw new BusinessException('Hệ thống hiện không trong thời gian cho phép đăng ký hoặc điều chỉnh nguyện vọng.');
    }

    $maxWishes = (int)($regulation['soLuongNguyenVongToiDa'] ?? 3);
    $count = count($rawWishes);

    if ($count === 0) {
        throw new BusinessException('Học sinh cần đăng ký ít nhất 1 nguyện vọng vào lớp 10.');
    }
    if ($count > $maxWishes) {
        throw new BusinessException("Số lượng nguyện vọng ({$count}) vượt quá quy định của Sở GD&ĐT (tối đa {$maxWishes} nguyện vọng).");
    }

    $schoolsList = aspiration_get_schools();
    $schoolsById = [];
    foreach ($schoolsList as $sch) {
        $schoolsById[$sch['maTruongTHPT']] = $sch;
    }

    $ordersSeen = [];
    $normalSchoolsSeen = [];
    $exactWishesSeen = [];
    $specializedCount = 0;
    $validated = [];

    foreach ($rawWishes as $index => $item) {
        $order = (int)($item['thuTuNguyenVong'] ?? ($index + 1));
        $schoolId = trim((string)($item['maTruongTHPT'] ?? ''));
        $track = trim((string)($item['lopChuyen'] ?? 'Thường'));
        if ($track === '') $track = 'Thường';

        // Kiểm tra thứ tự ưu tiên
        $expectedOrder = $index + 1;
        if ($order !== $expectedOrder || isset($ordersSeen[$order])) {
            throw new BusinessException("Thứ tự nguyện vọng không hợp lệ hoặc bị trùng lặp (Thứ tự phải liên tục từ 1 đến {$count}).");
        }
        $ordersSeen[$order] = true;

        // Kiểm tra trường học
        if (!isset($schoolsById[$schoolId])) {
            throw new BusinessException("Mã trường THPT '{$schoolId}' không tồn tại trong hệ thống tuyển sinh.");
        }
        $schoolInfo = $schoolsById[$schoolId];

        // Kiểm tra trùng lặp trường hệ thường
        if ($track === 'Thường') {
            if (isset($normalSchoolsSeen[$schoolId])) {
                throw new BusinessException("Không được chọn trùng Trường {$schoolInfo['tenTruongTHPT']} cho nhiều nguyện vọng hệ thường (NV{$normalSchoolsSeen[$schoolId]} và NV{$order}).");
            }
            $normalSchoolsSeen[$schoolId] = $order;
        }

        // Kiểm tra trùng lặp chính xác cùng trường + cùng lớp chuyên
        $wishKey = $schoolId . '|' . $track;
        if (isset($exactWishesSeen[$wishKey])) {
            throw new BusinessException("Nguyện vọng {$order} bị trùng lặp hoàn toàn với nguyện vọng trước đó (cùng Trường {$schoolInfo['tenTruongTHPT']} và cùng hệ {$track}).");
        }
        $exactWishesSeen[$wishKey] = true;

        // Kiểm tra điều kiện lớp chuyên
        if ($track !== 'Thường') {
            $specializedCount++;
            if ($specializedCount > 2) {
                throw new BusinessException('Quy chế Sở GD&ĐT quy định mỗi thí sinh chỉ được đăng ký tối đa 2 nguyện vọng chuyên.');
            }
            if (empty($schoolInfo['has_specialized']) || !isset($schoolInfo['available_tracks'][$track])) {
                throw new BusinessException("Trường {$schoolInfo['tenTruongTHPT']} không có chỉ tiêu tuyển sinh {$track}.");
            }
            if (empty($candidate['eligible_specialized'])) {
                throw new BusinessException("Học sinh không đủ điều kiện đăng ký lớp chuyên tại NV{$order}: " . $candidate['specialized_reason']);
            }
        }

        $validated[] = [
            'thuTuNguyenVong' => $order,
            'maTruongTHPT' => $schoolId,
            'lopChuyen' => $track,
            'maNguyenVong' => $item['maNguyenVong'] ?? null,
        ];
    }

    return $validated;
}

/**
 * Lưu danh sách nguyện vọng vào CSDL (Basic Flow 6)
 */
function aspiration_save(string $maThiSinh, array $validatedWishes, string $maQuyDinh): void {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Lấy danh sách nguyện vọng hiện có
        $stCurrent = $pdo->prepare('SELECT maNguyenVong, thuTuNguyenVong FROM NguyenVong WHERE maThiSinh = ? ORDER BY thuTuNguyenVong ASC FOR UPDATE');
        $stCurrent->execute([$maThiSinh]);
        $currentRows = $stCurrent->fetchAll();
        $currentCount = count($currentRows);

        // Lấy mã số numeric lớn nhất của NVD để cấp mã mới khi cần
        $stMax = $pdo->query('SELECT MAX(CAST(SUBSTRING(maNguyenVong, 4) AS UNSIGNED)) FROM NguyenVong WHERE maNguyenVong LIKE "NVD%"');
        $maxNum = (int)$stMax->fetchColumn();

        $updateStmt = $pdo->prepare('
            UPDATE NguyenVong
            SET maTruongTHPT = ?, maQuyDinh = ?, thuTuNguyenVong = ?, lopChuyen = ?,
                ngayDangKy = NOW(), trangThaiNguyenVong = "Chờ xử lý"
            WHERE maNguyenVong = ? AND maThiSinh = ?
        ');

        $insertStmt = $pdo->prepare('
            INSERT INTO NguyenVong (maNguyenVong, maThiSinh, maTruongTHPT, maQuyDinh, thuTuNguyenVong, lopChuyen, ngayDangKy, trangThaiNguyenVong)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), "Chờ xử lý")
        ');

        $reusedIds = [];

        foreach ($validatedWishes as $idx => $item) {
            $order = $idx + 1;
            if (isset($currentRows[$idx])) {
                // Tái sử dụng bản ghi hiện có
                $maNV = $currentRows[$idx]['maNguyenVong'];
                $updateStmt->execute([
                    $item['maTruongTHPT'],
                    $maQuyDinh,
                    $order,
                    $item['lopChuyen'],
                    $maNV,
                    $maThiSinh
                ]);
                $reusedIds[] = $maNV;
            } else {
                // Thêm mới
                $maxNum++;
                $maNV = 'NVD' . sprintf('%07d', $maxNum);
                $insertStmt->execute([
                    $maNV,
                    $maThiSinh,
                    $item['maTruongTHPT'],
                    $maQuyDinh,
                    $order,
                    $item['lopChuyen']
                ]);
                $reusedIds[] = $maNV;
            }
        }

        // Nếu số nguyện vọng mới ít hơn số nguyện vọng cũ (Alternative flow 5.1 Xóa NV)
        if ($currentCount > count($validatedWishes)) {
            $toDelete = array_slice($currentRows, count($validatedWishes));
            $resultStmt = $pdo->prepare('SELECT maKetQua FROM KetQuaXetTuyen WHERE maNguyenVong = ? LIMIT 1 FOR UPDATE');
            $delStmt = $pdo->prepare('DELETE FROM NguyenVong WHERE maNguyenVong = ? AND maThiSinh = ?');
            foreach ($toDelete as $delItem) {
                $resultStmt->execute([$delItem['maNguyenVong']]);
                if ($resultStmt->fetchColumn() !== false) {
                    throw new BusinessException('Không thể xóa nguyện vọng đã có kết quả xét tuyển liên kết.');
                }
                $delStmt->execute([$delItem['maNguyenVong'], $maThiSinh]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
