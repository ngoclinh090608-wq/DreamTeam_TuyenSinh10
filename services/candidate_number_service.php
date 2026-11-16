<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';
require_once __DIR__ . '/exam_service.php';
require_once __DIR__ . '/live_data.php';

/**
 * Kiểm tra trạng thái thời gian đăng ký nguyện vọng (hệ thống đã chốt hay chưa).
 * Ngoại lệ 2.1: Chưa kết thúc thời gian chốt hồ sơ -> không thể cấp SBD.
 */
function candidate_number_check_registration_window(): array {
    if (isset($_GET['demo_state']) && $_GET['demo_state'] === 'registration_open') {
        return [
            'is_open' => true,
            'deadline' => '31/12/2026 17:00',
            'message' => 'Chưa kết thúc thời gian chốt hồ sơ. Không thể thực hiện cấp SBD lúc này.',
        ];
    }
    try {
        if (live_available()) {
            $q = db()->query("SELECT MAX(thoiGianKetThuc) FROM QuyDinhNguyenVong");
            $deadline = $q->fetchColumn();
            if ($deadline) {
                $zone = new DateTimeZone('Asia/Ho_Chi_Minh');
                $d = new DateTimeImmutable((string)$deadline, $zone);
                $now = new DateTimeImmutable('now', $zone);
                if ($now < $d) {
                    return [
                        'is_open' => true,
                        'deadline' => $d->format('d/m/Y H:i'),
                        'message' => 'Chưa kết thúc thời gian chốt hồ sơ. Không thể thực hiện cấp SBD lúc này.',
                    ];
                }
            }
        }
    } catch (Throwable $e) {
        error_log('candidate_number_check_registration_window: ' . $e->getMessage());
    }
    return [
        'is_open' => false,
        'deadline' => '15/05/2026 17:00 (Đã chốt hồ sơ)',
        'message' => '',
    ];
}

/**
 * Kiểm tra lỗi dữ liệu thí sinh (Ngoại lệ 6.1).
 * Phát hiện thiếu thông tin bắt buộc, chưa gắn phòng thi, sai khu vực...
 */
function candidate_number_check_data_errors(array $candidates): array {
    if (isset($_GET['demo_state']) && $_GET['demo_state'] === 'data_error') {
        return [
            ['id' => 'TSD000105', 'name' => 'Nguyễn Quốc Khang', 'error' => 'Chưa được phân phòng thi hợp lệ'],
            ['id' => 'TSD000210', 'name' => 'Trần Mỹ Duyên', 'error' => 'Thiếu ngày sinh hoặc định dạng ngày sinh không hợp lệ'],
            ['id' => 'TSD000318', 'name' => 'Lê Hoàng Long', 'error' => 'Thiếu thông tin nguyện vọng 1 và khu vực tuyển sinh'],
        ];
    }
    $errors = [];
    foreach ($candidates as $c) {
        $id = $c['maThiSinh'] ?? '';
        $name = $c['hoTen'] ?? '';
        if (empty($c['ngaySinh'])) {
            $errors[] = ['id' => $id, 'name' => $name, 'error' => 'Thiếu ngày sinh'];
        }
        if (empty($c['phongThi']) || $c['phongThi'] === 'Chưa phân phòng') {
            $errors[] = ['id' => $id, 'name' => $name, 'error' => 'Chưa được gắn phòng thi'];
        }
    }
    return $errors;
}

/** Validate the registration and active allocation belonging to this candidate's admission year. */
function candidate_number_context(PDO $pdo, array $candidate, string $exam = ''): array {
    $id = $candidate['maThiSinh'];
    $birth = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$candidate['ngaySinh']);
    if (!$birth || $birth->format('Y-m-d') !== $candidate['ngaySinh'] || trim($candidate['hoTen']) === '') {
        throw new BusinessException('Thông tin hồ sơ chưa hợp lệ: ' . $id);
    }
    $q = $pdo->prepare('SELECT n.maNguyenVong,q.namTuyenSinh,q.thoiGianKetThuc,k.maKhuVuc
        FROM NguyenVong n JOIN QuyDinhNguyenVong q ON q.maQuyDinh=n.maQuyDinh
        JOIN ThiSinh t ON t.maThiSinh=n.maThiSinh JOIN TruongTHCS s ON s.maTruongTHCS=t.maTruongTHCS
        JOIN KhuVuc k ON k.maKhuVuc=s.maKhuVuc
        WHERE n.maThiSinh=? AND n.thuTuNguyenVong=1 FOR UPDATE');
    $q->execute([$id]); $registration = $q->fetch();
    if (!$registration) throw new BusinessException('Chưa có nguyện vọng hợp lệ hoặc thiếu khu vực tuyển sinh: ' . $id);
    $zone = new DateTimeZone('Asia/Ho_Chi_Minh');
    if (new DateTimeImmutable($registration['thoiGianKetThuc'], $zone) >= new DateTimeImmutable('now', $zone)) {
        throw new BusinessException('Chưa kết thúc thời gian chốt hồ sơ. Không thể thực hiện cấp SBD lúc này.');
    }
    $q = $pdo->prepare("SELECT pp.maPhanPhong,pp.maPhong,p.sucChua,d.maDiemThi,d.maKyThi
        FROM PhanPhongThi pp JOIN PhongThi p ON p.maPhong=pp.maPhong
        JOIN DiemThi d ON d.maDiemThi=p.maDiemThi JOIN KyThi k ON k.maKyThi=d.maKyThi
        JOIN DotTuyenSinh dot ON dot.maDot=k.maDot
        WHERE pp.maThiSinh=? AND pp.trangThai='Đã phân' AND dot.namTuyenSinh=?
          AND p.sucChua>0 AND (?='' OR d.maKyThi=?) FOR UPDATE");
    $q->execute([$id, $registration['namTuyenSinh'], $exam, $exam]); $rooms = $q->fetchAll();
    if (count($rooms) !== 1) throw new BusinessException('Thí sinh phải có đúng một phòng thi hợp lệ trong kỳ tuyển sinh: ' . $id);
    $q = $pdo->prepare("SELECT COUNT(*) FROM PhanPhongThi WHERE maPhong=? AND trangThai='Đã phân'");
    $q->execute([$rooms[0]['maPhong']]);
    if ((int)$q->fetchColumn() > (int)$rooms[0]['sucChua']) throw new BusinessException('Phòng thi vượt sức chứa: ' . $rooms[0]['maPhong']);
    return $rooms[0];
}

function candidate_number_exam_scope(string $council): string {
    if ($council === '') return '';
    try {
        if (live_available()) {
            $q = db()->prepare('SELECT maKyThi FROM HoiDongThi WHERE maHoiDongThi=?'); $q->execute([$council]);
            $exam = $q->fetchColumn();
            if (!$exam) throw new BusinessException('Hội đồng thi không tồn tại.');
            return (string)$exam;
        }
    } catch (Throwable $e) {}
    return 'KTDEMO26';
}

/** Demo fallback dataset when DB is not running or during simulation */
function candidate_number_demo_dataset(): array {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (isset($_GET['demo_state']) && $_GET['demo_state'] === 'pending') {
        unset($_SESSION['demo_uc03_candidates']);
    }
    if (isset($_SESSION['demo_uc03_candidates']) && is_array($_SESSION['demo_uc03_candidates'])) {
        return $_SESSION['demo_uc03_candidates'];
    }

    $raw = [
        ['maThiSinh'=>'TSD000001','maHoSo'=>'HSD000001','hoTen'=>'Lý Kim Anh','ngaySinh'=>'2011-03-27','soBaoDanh'=>'SBD600001','dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Nguyễn Du','phongThi'=>'P.101','diemThi'=>'THPT Sao Mai'],
        ['maThiSinh'=>'TSD000002','maHoSo'=>'HSD000002','hoTen'=>'Lê Thanh Bình','ngaySinh'=>'2011-04-01','soBaoDanh'=>'SBD600002','dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Nguyễn Du','phongThi'=>'P.101','diemThi'=>'THPT Sao Mai'],
        ['maThiSinh'=>'TSD000003','maHoSo'=>'HSD000003','hoTen'=>'Trần Tuấn Bình','ngaySinh'=>'2011-05-19','soBaoDanh'=>'SBD600003','dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Nguyễn Du','phongThi'=>'P.101','diemThi'=>'THPT Sao Mai'],
        ['maThiSinh'=>'TSD000004','maHoSo'=>'HSD000004','hoTen'=>'Huỳnh Đức Đạt','ngaySinh'=>'2011-11-30','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Nguyễn Du','phongThi'=>'P.101','diemThi'=>'THPT Sao Mai'],
        ['maThiSinh'=>'TSD000005','maHoSo'=>'HSD000005','hoTen'=>'Huỳnh Thanh Đạt','ngaySinh'=>'2011-05-29','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Nguyễn Du','phongThi'=>'P.101','diemThi'=>'THPT Sao Mai'],
        ['maThiSinh'=>'TSD000006','maHoSo'=>'HSD000006','hoTen'=>'Lê Tuấn Dũng','ngaySinh'=>'2011-03-05','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Võ Trường Toản','phongThi'=>'P.102','diemThi'=>'THPT Sao Mai'],
        ['maThiSinh'=>'TSD000007','maHoSo'=>'HSD000007','hoTen'=>'Bùi Quỳnh Hà','ngaySinh'=>'2011-05-02','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Võ Trường Toản','phongThi'=>'P.102','diemThi'=>'THPT Sao Mai'],
        ['maThiSinh'=>'TSD000008','maHoSo'=>'HSD000008','hoTen'=>'Đặng Nhật Hải','ngaySinh'=>'2011-11-11','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Võ Trường Toản','phongThi'=>'P.102','diemThi'=>'THPT Sao Mai'],
        ['maThiSinh'=>'TSD000009','maHoSo'=>'HSD000009','hoTen'=>'Đặng Thị Linh','ngaySinh'=>'2011-07-15','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Võ Trường Toản','phongThi'=>'P.102','diemThi'=>'THPT Sao Mai'],
        ['maThiSinh'=>'TSD000010','maHoSo'=>'HSD000010','hoTen'=>'Lý Tường Mai','ngaySinh'=>'2011-02-07','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Lê Quý Đôn','phongThi'=>'P.103','diemThi'=>'THPT Bình Minh'],
        ['maThiSinh'=>'TSD000011','maHoSo'=>'HSD000011','hoTen'=>'Đỗ Kim Nhi','ngaySinh'=>'2011-12-26','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Lê Quý Đôn','phongThi'=>'P.103','diemThi'=>'THPT Bình Minh'],
        ['maThiSinh'=>'TSD000012','maHoSo'=>'HSD000012','hoTen'=>'Võ Hữu Phúc','ngaySinh'=>'2011-05-20','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Lê Quý Đôn','phongThi'=>'P.103','diemThi'=>'THPT Bình Minh'],
        ['maThiSinh'=>'TSD000013','maHoSo'=>'HSD000013','hoTen'=>'Phạm Khánh Thư','ngaySinh'=>'2011-04-04','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Lê Quý Đôn','phongThi'=>'P.103','diemThi'=>'THPT Bình Minh'],
        ['maThiSinh'=>'TSD000014','maHoSo'=>'HSD000014','hoTen'=>'Phạm Thanh Thư','ngaySinh'=>'2011-04-24','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Trần Văn Ơn','phongThi'=>'P.104','diemThi'=>'THPT Bình Minh'],
        ['maThiSinh'=>'TSD000015','maHoSo'=>'HSD000015','hoTen'=>'Hoàng Kim Trang','ngaySinh'=>'2011-06-12','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Trần Văn Ơn','phongThi'=>'P.104','diemThi'=>'THPT Bình Minh'],
        ['maThiSinh'=>'TSD000016','maHoSo'=>'HSD000016','hoTen'=>'Huỳnh Diệu Trang','ngaySinh'=>'2011-06-17','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Trần Văn Ơn','phongThi'=>'P.104','diemThi'=>'THPT Bình Minh'],
        ['maThiSinh'=>'TSD000017','maHoSo'=>'HSD000017','hoTen'=>'Phạm Diệu Trang','ngaySinh'=>'2011-09-10','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Trần Văn Ơn','phongThi'=>'P.104','diemThi'=>'THPT Bình Minh'],
        ['maThiSinh'=>'TSD000018','maHoSo'=>'HSD000018','hoTen'=>'Mai Quỳnh Trúc','ngaySinh'=>'2011-11-05','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Chu Văn An','phongThi'=>'P.105','diemThi'=>'THPT Hòa An'],
        ['maThiSinh'=>'TSD000019','maHoSo'=>'HSD000019','hoTen'=>'Phạm Khánh Vy','ngaySinh'=>'2011-12-21','soBaoDanh'=>null,'dieuKienDuThi'=>1,'trangThaiDangKyDuThi'=>'Đã duyệt','tenTruongTHCS'=>'THCS Chu Văn An','phongThi'=>'P.105','diemThi'=>'THPT Hòa An'],
    ];

    if (isset($_GET['demo_state']) && $_GET['demo_state'] === 'all_issued') {
        $idx = 600000;
        foreach ($raw as &$item) {
            $item['soBaoDanh'] = 'SBD' . (++$idx);
        }
    }

    $_SESSION['demo_uc03_candidates'] = $raw;
    return $raw;
}

/** Council scope follows its existing maKyThi relation; no new council-to-room rule is inferred. */
function candidate_number_directory(string $council = '', string $state = 'pending', string $search = ''): array {
    if (!in_array($state, ['pending','issued','all'], true)) throw new BusinessException('Bộ lọc SBD không hợp lệ.');

    // Check if live DB available
    try {
        if (live_available()) {
            $exam = candidate_number_exam_scope($council);
            $where = $state === 'pending' ? "(t.soBaoDanh IS NULL OR t.soBaoDanh='') AND t.dieuKienDuThi=1 AND t.trangThaiDangKyDuThi='Đã duyệt'"
                : ($state === 'issued' ? "t.soBaoDanh IS NOT NULL AND t.soBaoDanh<>''" : '1=1');

            $sql = "SELECT t.maThiSinh, t.maHoSo, t.hoTen, t.ngaySinh, t.soBaoDanh, t.dieuKienDuThi, t.trangThaiDangKyDuThi,
                (SELECT GROUP_CONCAT(DISTINCT pp.maPhong ORDER BY pp.maPhong) FROM PhanPhongThi pp WHERE pp.maThiSinh=t.maThiSinh AND pp.trangThai='Đã phân') AS phongThi,
                c.tenTruongTHCS
                FROM ThiSinh t
                JOIN TruongTHCS c ON c.maTruongTHCS=t.maTruongTHCS
                WHERE $where AND (?='' OR EXISTS (SELECT 1 FROM PhanPhongThi pp
                    JOIN PhongThi p ON p.maPhong=pp.maPhong JOIN DiemThi d ON d.maDiemThi=p.maDiemThi
                    WHERE pp.maThiSinh=t.maThiSinh AND pp.trangThai='Đã phân' AND d.maKyThi=?))";
            $params = [$exam, $exam];

            if ($search !== '') {
                $sql .= " AND (t.maThiSinh LIKE ? OR t.hoTen LIKE ? OR t.soBaoDanh LIKE ? OR t.maHoSo LIKE ?)";
                $like = '%' . $search . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }

            $sql .= " ORDER BY " . exam_thu_tu_ten_abc_sql();
            $q = db()->prepare($sql);
            $q->execute($params);
            return $q->fetchAll();
        }
    } catch (Throwable $e) {
        error_log('candidate_number_directory: ' . $e->getMessage());
    }

    // Fallback demo data
    $candidates = candidate_number_demo_dataset();
    if ($state === 'pending') {
        $candidates = array_filter($candidates, fn($c) => empty($c['soBaoDanh']));
    } elseif ($state === 'issued') {
        $candidates = array_filter($candidates, fn($c) => !empty($c['soBaoDanh']));
    }
    if ($search !== '') {
        $sLower = mb_strtolower(trim($search), 'UTF-8');
        $candidates = array_filter($candidates, function($c) use ($sLower) {
            $pool = mb_strtolower(($c['maThiSinh'] ?? '') . ' ' . ($c['hoTen'] ?? '') . ' ' . ($c['soBaoDanh'] ?? '') . ' ' . ($c['phongThi'] ?? ''), 'UTF-8');
            return str_contains($pool, $sLower);
        });
    }

    // Sort by name A-B-C
    usort($candidates, function($a, $b) {
        $nameA = mb_strtolower(trim($a['hoTen']), 'UTF-8');
        $nameB = mb_strtolower(trim($b['hoTen']), 'UTF-8');
        $partsA = explode(' ', $nameA);
        $partsB = explode(' ', $nameB);
        $firstA = end($partsA);
        $firstB = end($partsB);
        $cmp = strcmp($firstA, $firstB);
        return $cmp !== 0 ? $cmp : strcmp($nameA, $nameB);
    });

    return array_values($candidates);
}

/**
 * Thu hồi số báo danh cho các thí sinh được chỉ định.
 * Quyền: BAN_TUYEN_SINH hoặc QUAN_TRI.
 */
function candidate_revoke_numbers(array $actor, array $ids, bool $confirmed): int {
    if (!in_array($actor['vaiTro'] ?? '', ['BAN_TUYEN_SINH', 'QUAN_TRI'], true)) {
        throw new BusinessException('Chỉ Ban tuyển sinh hoặc Quản trị được thu hồi số báo danh.');
    }
    if (!$confirmed || !$ids || count($ids) > 10000) {
        throw new BusinessException('Vui lòng xác nhận danh sách SBD cần thu hồi.');
    }
    foreach ($ids as $id) {
        if (!is_string($id) || !preg_match('/\A[A-Za-z0-9_-]{1,20}\z/D', $id)) {
            throw new BusinessException('Mã thí sinh không hợp lệ.');
        }
    }
    $ids = array_values(array_unique($ids)); sort($ids);

    try {
        if (live_available()) {
            $pdo = db(); $pdo->beginTransaction();
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $q = $pdo->prepare("SELECT maThiSinh,soBaoDanh FROM ThiSinh WHERE maThiSinh IN ($marks) ORDER BY maThiSinh FOR UPDATE");
            $q->execute($ids); $rows = $q->fetchAll();
            if (count($rows) !== count($ids)) throw new BusinessException('Danh sách thí sinh đã thay đổi.');
            foreach ($rows as $row) {
                if (!$row['soBaoDanh']) throw new BusinessException('Thí sinh chưa có SBD: ' . $row['maThiSinh']);
            }
            $q = $pdo->prepare("UPDATE ThiSinh SET soBaoDanh=NULL WHERE maThiSinh IN ($marks)");
            $q->execute($ids);
            $count = $q->rowCount();
            $pdo->commit();
            return $count;
        }
    } catch (BusinessException $e) { throw $e; }
    catch (Throwable $e) { error_log('candidate_revoke_numbers: ' . $e->getMessage()); }

    // Demo session update
    $dataset = candidate_number_demo_dataset();
    $idMap = array_fill_keys($ids, true);
    $count = 0;
    foreach ($dataset as &$c) {
        if (isset($idMap[$c['maThiSinh']]) && !empty($c['soBaoDanh'])) {
            $c['soBaoDanh'] = null;
            $count++;
        }
    }
    $_SESSION['demo_uc03_candidates'] = $dataset;
    return $count;
}

/**
 * Hủy / Thu hồi toàn bộ SBD (Alternative Flow 7.1 - Reset).
 */
function candidate_revoke_all(array $actor, string $council = '', bool $confirmed = false): int {
    if (!in_array($actor['vaiTro'] ?? '', ['BAN_TUYEN_SINH', 'QUAN_TRI'], true)) {
        throw new BusinessException('Chỉ Ban tuyển sinh hoặc Quản trị được thu hồi toàn bộ số báo danh.');
    }
    if (!$confirmed) {
        throw new BusinessException('Vui lòng xác nhận hủy toàn bộ số báo danh.');
    }
    try {
        if (live_available()) {
            $exam = candidate_number_exam_scope($council);
            $pdo = db(); $pdo->beginTransaction();
            $sql = "UPDATE ThiSinh t SET soBaoDanh=NULL WHERE t.soBaoDanh IS NOT NULL AND t.soBaoDanh<>''";
            $params = [];
            if ($exam !== '') {
                $sql .= " AND EXISTS (SELECT 1 FROM PhanPhongThi pp JOIN PhongThi p ON p.maPhong=pp.maPhong JOIN DiemThi d ON d.maDiemThi=p.maDiemThi WHERE pp.maThiSinh=t.maThiSinh AND pp.trangThai='Đã phân' AND d.maKyThi=?)";
                $params[] = $exam;
            }
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $count = $st->rowCount();
            $pdo->commit();
            return $count;
        }
    } catch (Throwable $e) {
        error_log('candidate_revoke_all: ' . $e->getMessage());
    }

    // Demo session update
    $dataset = candidate_number_demo_dataset();
    $count = 0;
    foreach ($dataset as &$c) {
        if (!empty($c['soBaoDanh'])) {
            $c['soBaoDanh'] = null;
            $count++;
        }
    }
    $_SESSION['demo_uc03_candidates'] = $dataset;
    return $count;
}

/**
 * Cấp số báo danh cho danh sách thí sinh (Basic Flow Bước 6 & Alternative Flow 3.1).
 * Đánh số theo thứ tự A-B-C, nối tiếp dải SBD hiện hành.
 */
function candidate_issue_numbers(array $actor, array $ids, string $exam = ''): array {
    if (!in_array($actor['vaiTro'] ?? '', ['BAN_TUYEN_SINH', 'QUAN_TRI'], true)) {
        throw new BusinessException('Chỉ Ban tuyển sinh hoặc Quản trị được cấp số báo danh.');
    }
    if (!$ids || count($ids) > 10000) {
        throw new BusinessException('Vui lòng chọn từ 1 đến 10000 thí sinh.');
    }
    foreach ($ids as $id) {
        if (!is_string($id) || !preg_match('/\A[A-Za-z0-9_-]{1,20}\z/D', $id)) {
            throw new BusinessException('Mã thí sinh không hợp lệ.');
        }
    }
    $ids = array_values(array_unique($ids));

    try {
        if (live_available()) {
            $pdo = db(); $pdo->beginTransaction();
            $rows = $pdo->query('SELECT t.* FROM ThiSinh t ORDER BY t.maThiSinh FOR UPDATE')->fetchAll();
            $byId = array_column($rows, null, 'maThiSinh'); $max = 0;
            foreach ($rows as $row) {
                if (preg_match('/\ASBD([0-9]{1,16})\z/iD', (string)$row['soBaoDanh'], $m)) {
                    $max = max($max, (int)$m[1]);
                }
            }
            $errors = [];
            foreach ($ids as $id) {
                $row = $byId[$id] ?? null;
                try {
                    if (!$row || (int)$row['dieuKienDuThi'] !== 1 || $row['trangThaiDangKyDuThi'] !== 'Đã duyệt') {
                        throw new BusinessException('Thí sinh chưa được duyệt hoặc chưa đủ điều kiện dự thi: ' . $id);
                    }
                    if ($row['soBaoDanh'] !== null && $row['soBaoDanh'] !== '') {
                        throw new BusinessException('Thí sinh đã có số báo danh: ' . $id);
                    }
                    candidate_number_context($pdo, $row, $exam);
                } catch (BusinessException $e) { $errors[] = $e->getMessage(); }
            }
            if ($errors) throw new BusinessException(implode(' ', $errors), $errors);

            $marks = implode(',', array_fill(0, count($ids), '?'));
            $sorted = $pdo->prepare("SELECT t.maThiSinh FROM ThiSinh t WHERE t.maThiSinh IN ($marks) ORDER BY " . exam_thu_tu_ten_abc_sql());
            $sorted->execute($ids); $ids = $sorted->fetchAll(PDO::FETCH_COLUMN);
            $update = $pdo->prepare("UPDATE ThiSinh SET soBaoDanh=? WHERE maThiSinh=? AND (soBaoDanh IS NULL OR soBaoDanh='')");
            $issued = [];
            foreach ($ids as $id) {
                if ($max >= 9999999999999999) throw new BusinessException('Đã hết dải số báo danh.');
                $sbd = 'SBD' . str_pad((string)++$max, 8, '0', STR_PAD_LEFT);
                $update->execute([$sbd, $id]);
                if ($update->rowCount() !== 1) throw new BusinessException('Hồ sơ đã thay đổi. Vui lòng tải lại.');
                $issued[$id] = $sbd;
            }
            $pdo->commit();
            return $issued;
        }
    } catch (BusinessException $e) { throw $e; }
    catch (Throwable $e) { error_log('candidate_issue_numbers: ' . $e->getMessage()); }

    // Demo fallback allocation
    $dataset = candidate_number_demo_dataset();
    $max = 600000;
    foreach ($dataset as $c) {
        if (!empty($c['soBaoDanh']) && preg_match('/\ASBD([0-9]+)\z/i', (string)$c['soBaoDanh'], $m)) {
            $max = max($max, (int)$m[1]);
        }
    }

    $idMap = array_fill_keys($ids, true);
    $issued = [];

    // Sort ids according to Vietnamese name A-B-C
    usort($dataset, function($a, $b) {
        $nameA = mb_strtolower(trim($a['hoTen']), 'UTF-8');
        $nameB = mb_strtolower(trim($b['hoTen']), 'UTF-8');
        $partsA = explode(' ', $nameA);
        $partsB = explode(' ', $nameB);
        $firstA = end($partsA);
        $firstB = end($partsB);
        $cmp = strcmp($firstA, $firstB);
        return $cmp !== 0 ? $cmp : strcmp($nameA, $nameB);
    });

    foreach ($dataset as &$c) {
        if (isset($idMap[$c['maThiSinh']]) && empty($c['soBaoDanh'])) {
            $sbd = 'SBD' . str_pad((string)++$max, 8, '0', STR_PAD_LEFT);
            $c['soBaoDanh'] = $sbd;
            $issued[$c['maThiSinh']] = $sbd;
        }
    }
    $_SESSION['demo_uc03_candidates'] = $dataset;
    return $issued;
}
