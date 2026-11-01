<?php
declare(strict_types=1);

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
