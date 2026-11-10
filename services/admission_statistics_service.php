<?php
declare(strict_types=1);

/**
 * UC21 - THỐNG KÊ KẾT QUẢ TUYỂN SINH
 * Người thực hiện: Nguyễn Thị Cẩm Vân
 * Actor: Ban Tuyển sinh Sở GD&ĐT
 *
 * Chỉ thực hiện SELECT, không thay đổi database.
 */

require_once dirname(__DIR__) . '/config/database.php';

/**
 * Danh sách năm tuyển sinh.
 */
function uc21_layDanhSachNam(): array
{
    $sql = "
        SELECT DISTINCT namTuyenSinh
        FROM DotTuyenSinh
        ORDER BY namTuyenSinh DESC
    ";

    return db()->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Các loại thống kê được hỗ trợ.
 */
function uc21_loaiThongKe(): array
{
    return [
        'thcs' => 'Theo trường THCS',
        'thpt' => 'Theo trường THPT',
        'nguyenvong' => 'Theo nguyện vọng',
        'nhaphoc' => 'Theo tình trạng nhập học',
        'ketqua' => 'Theo kết quả xét tuyển'
    ];
}

/**
 * Truy vấn nguồn thống kê của một năm.
 *
 * Dùng QuyDinhNguyenVong để xác định năm,
 * tránh trộn nguyện vọng của các năm.
 */
function uc21_duLieuNguon(int $nam): array
{
    if ($nam <= 0) {
        throw new InvalidArgumentException(
            'Vui lòng chọn năm tuyển sinh.'
        );
    }

    $sql = "
        SELECT
            nv.maNguyenVong,
            nv.maThiSinh,
            nv.thuTuNguyenVong,
            nv.maTruongTHPT,
            ts.maTruongTHCS,
            ts.dieuKienDuThi,
            thcs.tenTruongTHCS,
            thpt.tenTruongTHPT,
            kq.maKetQua,
            kq.trangThaiXetTuyen,
            tt.trangThaiNhapHoc

        FROM NguyenVong nv

        INNER JOIN QuyDinhNguyenVong qd
            ON nv.maQuyDinh COLLATE utf8mb4_unicode_ci
             = qd.maQuyDinh COLLATE utf8mb4_unicode_ci

        INNER JOIN ThiSinh ts
            ON ts.maThiSinh COLLATE utf8mb4_unicode_ci
             = nv.maThiSinh COLLATE utf8mb4_unicode_ci

        LEFT JOIN TruongTHCS thcs
            ON thcs.maTruongTHCS COLLATE utf8mb4_unicode_ci
             = ts.maTruongTHCS COLLATE utf8mb4_unicode_ci

        LEFT JOIN TruongTHPT thpt
            ON thpt.maTruongTHPT COLLATE utf8mb4_unicode_ci
             = nv.maTruongTHPT COLLATE utf8mb4_unicode_ci

        LEFT JOIN KetQuaXetTuyen kq
            ON kq.maNguyenVong COLLATE utf8mb4_unicode_ci
             = nv.maNguyenVong COLLATE utf8mb4_unicode_ci

        LEFT JOIN TinhTrangNhapHoc tt
            ON tt.maKetQua COLLATE utf8mb4_unicode_ci
             = kq.maKetQua COLLATE utf8mb4_unicode_ci

        WHERE qd.namTuyenSinh = :nam
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([':nam' => $nam]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Tập thí sinh có bài thi thuộc kỳ thi của năm tuyển sinh.
 * Có bài thi là dấu hiệu dữ liệu ghi nhận dự thi; không suy diễn từ điểm chấm.
 */
function uc21_maThiSinhCoBaiThi(int $nam): array
{
    $stmt = db()->prepare("
        SELECT DISTINCT bt.maThiSinh
        FROM BaiThi bt
        JOIN KyThi kt
          ON kt.maKyThi COLLATE utf8mb4_unicode_ci
           = bt.maKyThi COLLATE utf8mb4_unicode_ci
        JOIN DotTuyenSinh d
          ON d.maDot COLLATE utf8mb4_unicode_ci
           = kt.maDot COLLATE utf8mb4_unicode_ci
        WHERE d.namTuyenSinh = :nam
    ");
    $stmt->execute([':nam' => $nam]);
    return array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
}

/**
 * Tính tỷ lệ phần trăm.
 */
function uc21_tinhTyLe(int $tuSo, int $mauSo): float
{
    if ($mauSo === 0) {
        return 0.0;
    }

    return round(($tuSo / $mauSo) * 100, 2);
}

/**
 * Tổng hợp thống kê theo loại.
 *
 * Mỗi nhóm dùng tập mã thí sinh duy nhất,
 * tránh đếm trùng khi thí sinh có nhiều NV.
 */
function uc21_thongKe(
    int $nam,
    string $loai,
    string $tieuChi = ''
): array {

    if (!array_key_exists($loai, uc21_loaiThongKe())) {
        throw new InvalidArgumentException(
            'Loại thống kê không hợp lệ.'
        );
    }

    $nguon = uc21_duLieuNguon($nam);
    $daDuThi = uc21_maThiSinhCoBaiThi($nam);

    // Outcome groups are mutually exclusive per student, before applying the outcome filter.
    // A winning preference is final even if the student's other preferences were rejected/not considered.
    if ($loai === 'ketqua') {
        $students = [];
        foreach ($nguon as $row) $students[$row['maThiSinh']][] = $row;
        $nguon = [];
        foreach ($students as $wishes) {
            $winners = array_values(array_filter($wishes, static fn($row) => $row['trangThaiXetTuyen'] === 'Trúng tuyển'));
            if (count($winners) > 1) throw new RuntimeException('Dữ liệu có thí sinh trúng tuyển nhiều nguyện vọng.');
            if ($winners) { $nguon[] = $winners[0]; continue; }
            $pending = array_values(array_filter($wishes, static fn($row) => $row['trangThaiXetTuyen'] === null || $row['trangThaiXetTuyen'] === 'Chưa xét'));
            $final = $pending[0] ?? $wishes[0];
            $final['trangThaiXetTuyen'] = $pending ? 'Chưa xét' : 'Không trúng tuyển';
            $nguon[] = $final;
        }
    }

    // Tình trạng nhập học chỉ có ý nghĩa với thí sinh đã trúng tuyển.
    if ($loai === 'nhaphoc') {
        $nguon = array_values(array_filter($nguon, static fn(array $row): bool =>
            $row['trangThaiXetTuyen'] === 'Trúng tuyển'
        ));
    }

    
    // Kiểm tra tiêu chí thuộc danh mục cho phép.
    $danhMuc = uc21_danhMucTieuChi($loai);
    $maHopLe = array_map(
        fn($item) => (string)$item['ma'],
        $danhMuc
    );

    if ($tieuChi !== '' && !in_array($tieuChi, $maHopLe, true)) {
        throw new InvalidArgumentException(
            'Tiêu chí thống kê không hợp lệ.'
        );
    }

    // Lọc nguồn dữ liệu trước khi tổng hợp.
    if ($tieuChi !== '') {
        $nguon = array_values(array_filter(
            $nguon,
            function (array $row) use ($loai, $tieuChi): bool {
                return match ($loai) {
                    'thcs' =>
                        (string)$row['maTruongTHCS'] === $tieuChi,

                    'thpt' =>
                        (string)$row['maTruongTHPT'] === $tieuChi,

                    'nguyenvong' =>
                        (string)$row['thuTuNguyenVong'] === $tieuChi,

                    'nhaphoc' =>
                        (string)($row['trangThaiNhapHoc']
                            ?? 'Chưa ghi nhận') === $tieuChi,

                    'ketqua' =>
                        (string)($row['trangThaiXetTuyen']
                            ?? 'Chưa xét') === $tieuChi,

                    default => false
                };
            }
        ));
    }

    $nhom = [];

    foreach ($nguon as $row) {

        switch ($loai) {
            case 'thcs':
                $key = (string)$row['maTruongTHCS'];
                $label = (string)($row['tenTruongTHCS'] ?? $key);
                break;

            case 'thpt':
                $key = (string)$row['maTruongTHPT'];
                $label = (string)($row['tenTruongTHPT'] ?? $key);
                break;

            case 'nguyenvong':
                $key = (string)$row['thuTuNguyenVong'];
                $label = 'Nguyện vọng ' . $key;
                break;

            case 'nhaphoc':
                $key = (string)($row['trangThaiNhapHoc'] ?? 'Chưa ghi nhận');
                $label = $key;
                break;

            default:
                $key = (string)($row['trangThaiXetTuyen'] ?? 'Chưa xét');
                $label = $key;
                break;
        }

        if (!isset($nhom[$key])) {
            $nhom[$key] = [
                'tenNhom' => $label,
                'hoSoIds' => [],
                'duDieuKienIds' => [],
                'duThiIds' => [],
                'trungTuyenIds' => [],
                'nhapHocIds' => [],
                'chuaXacNhanIds' => [],
                'khongNhapHocIds' => []
            ];
        }

        $maThiSinh = (string)$row['maThiSinh'];

        $nhom[$key]['hoSoIds'][$maThiSinh] = true;
        if ((int)$row['dieuKienDuThi'] === 1) {
            $nhom[$key]['duDieuKienIds'][$maThiSinh] = true;
        }
        if (isset($daDuThi[$maThiSinh])) {
            $nhom[$key]['duThiIds'][$maThiSinh] = true;
        }

        if ($row['trangThaiXetTuyen'] === 'Trúng tuyển') {
            $nhom[$key]['trungTuyenIds'][$maThiSinh] = true;

            if ($row['trangThaiNhapHoc'] === 'Đã xác nhận') {
                $nhom[$key]['nhapHocIds'][$maThiSinh] = true;
            } elseif ($row['trangThaiNhapHoc'] === 'Không nhập học') {
                $nhom[$key]['khongNhapHocIds'][$maThiSinh] = true;
            } else {
                $nhom[$key]['chuaXacNhanIds'][$maThiSinh] = true;
            }
        }
    }

    $ketQua = [];

    foreach ($nhom as $item) {

        $hoSo = count($item['hoSoIds']);
        $duDieuKien = count($item['duDieuKienIds']);
        $duThi = count($item['duThiIds']);
        $trungTuyen = count($item['trungTuyenIds']);
        $nhapHoc = count($item['nhapHocIds']);

        $ketQua[] = [
            'tenNhom' => $item['tenNhom'],
            'soHoSo' => $hoSo,
            'soDuDieuKien' => $duDieuKien,
            'soDuThi' => $duThi,
            'soChuaXacNhan' => count($item['chuaXacNhanIds']),
            'soKhongNhapHoc' => count($item['khongNhapHocIds']),
            'soTrungTuyen' => $trungTuyen,
            'soNhapHoc' => $nhapHoc,
            'tyLeTrungTuyen' => uc21_tinhTyLe(
                $trungTuyen,
                $hoSo
            ),
            'tyLeNhapHoc' => uc21_tinhTyLe(
                $nhapHoc,
                $trungTuyen
            )
        ];
    }

    return $ketQua;
}

/**
 * Danh sách tiêu chí chi tiết cho UC21.
 */
function uc21_danhMucTieuChi(string $loai): array
{
    $pdo = db();

    switch ($loai) {
        case 'thcs':
            $rows = $pdo->query("
                SELECT maTruongTHCS AS ma,
                       tenTruongTHCS AS ten
                FROM TruongTHCS
                ORDER BY tenTruongTHCS
            ")->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'thpt':
            $rows = $pdo->query("
                SELECT maTruongTHPT AS ma,
                       tenTruongTHPT AS ten
                FROM TruongTHPT
                ORDER BY tenTruongTHPT
            ")->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'nguyenvong':
            $rows = [
                ['ma' => '1', 'ten' => 'Nguyện vọng 1'],
                ['ma' => '2', 'ten' => 'Nguyện vọng 2'],
                ['ma' => '3', 'ten' => 'Nguyện vọng 3']
            ];
            break;

        case 'nhaphoc':
            $rows = [
                ['ma' => 'Đã xác nhận', 'ten' => 'Đã xác nhận'],
                ['ma' => 'Chưa xác nhận', 'ten' => 'Chưa xác nhận'],
                ['ma' => 'Không nhập học', 'ten' => 'Không nhập học'],
                ['ma' => 'Chưa ghi nhận', 'ten' => 'Chưa ghi nhận']
            ];
            break;

        case 'ketqua':
            $rows = [
                ['ma' => 'Trúng tuyển', 'ten' => 'Trúng tuyển'],
                ['ma' => 'Không trúng tuyển', 'ten' => 'Không trúng tuyển'],
                ['ma' => 'Chưa xét', 'ten' => 'Chưa xét']
            ];
            break;

        default:
            throw new InvalidArgumentException(
                'Loại thống kê không hợp lệ.'
            );
    }

    return $rows;
}
