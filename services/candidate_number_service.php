<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';
require_once __DIR__ . '/exam_service.php';

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
    $q = db()->prepare('SELECT maKyThi FROM HoiDongThi WHERE maHoiDongThi=?'); $q->execute([$council]);
    $exam = $q->fetchColumn();
    if (!$exam) throw new BusinessException('Hội đồng thi không tồn tại.');
    return (string)$exam;
}

/** Council scope follows its existing maKyThi relation; no new council-to-room rule is inferred. */
function candidate_number_directory(string $council = '', string $state = 'pending'): array {
    if (!in_array($state, ['pending','issued','all'], true)) throw new BusinessException('Bộ lọc SBD không hợp lệ.');
    $exam = candidate_number_exam_scope($council);
    $where = $state === 'pending' ? "(t.soBaoDanh IS NULL OR t.soBaoDanh='') AND t.dieuKienDuThi=1 AND t.trangThaiDangKyDuThi='Đã duyệt'"
        : ($state === 'issued' ? "t.soBaoDanh IS NOT NULL AND t.soBaoDanh<>''" : '1=1');
    $q = db()->prepare("SELECT t.maThiSinh,t.maHoSo,t.hoTen,t.soBaoDanh,t.dieuKienDuThi,t.trangThaiDangKyDuThi,
        (SELECT GROUP_CONCAT(DISTINCT pp.maPhong ORDER BY pp.maPhong) FROM PhanPhongThi pp WHERE pp.maThiSinh=t.maThiSinh AND pp.trangThai='Đã phân') AS phongThi
        FROM ThiSinh t WHERE $where AND (?='' OR EXISTS (SELECT 1 FROM PhanPhongThi pp
            JOIN PhongThi p ON p.maPhong=pp.maPhong JOIN DiemThi d ON d.maDiemThi=p.maDiemThi
            WHERE pp.maThiSinh=t.maThiSinh AND pp.trangThai='Đã phân' AND d.maKyThi=?))
        ORDER BY " . exam_thu_tu_ten_abc_sql());
    $q->execute([$exam,$exam]); return $q->fetchAll();
}

function candidate_revoke_numbers(array $actor, array $ids, bool $confirmed): int {
    if (($actor['vaiTro'] ?? '') !== 'BAN_TUYEN_SINH') throw new BusinessException('Chỉ Ban tuyển sinh được thu hồi số báo danh.');
    if (!$confirmed || !$ids || count($ids)>10000) throw new BusinessException('Vui lòng xác nhận danh sách SBD cần thu hồi.');
    foreach ($ids as $id) if (!is_string($id) || !preg_match('/\A[A-Za-z0-9_-]{1,20}\z/D',$id)) throw new BusinessException('Mã thí sinh không hợp lệ.');
    $ids = array_values(array_unique($ids)); sort($ids); $pdo=db(); $pdo->beginTransaction();
    try {
        $marks=implode(',',array_fill(0,count($ids),'?'));
        $q=$pdo->prepare("SELECT maThiSinh,soBaoDanh FROM ThiSinh WHERE maThiSinh IN ($marks) ORDER BY maThiSinh FOR UPDATE"); $q->execute($ids);$rows=$q->fetchAll();
        if (count($rows)!==count($ids)) throw new BusinessException('Danh sách thí sinh đã thay đổi.');
        foreach ($rows as $row) if (!$row['soBaoDanh']) throw new BusinessException('Thí sinh chưa có SBD: '.$row['maThiSinh']);
        $q=$pdo->prepare("UPDATE ThiSinh SET soBaoDanh=NULL WHERE maThiSinh IN ($marks)");$q->execute($ids);$count=$q->rowCount();
        $pdo->commit();return $count;
    } catch (Throwable $e) {if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}

/** One atomic batch; existing numbers are immutable. The unique index is the final guard. */
function candidate_issue_numbers(array $actor, array $ids, string $exam = ''): array {
    if (($actor['vaiTro'] ?? '') !== 'BAN_TUYEN_SINH') throw new BusinessException('Chỉ Ban tuyển sinh được cấp số báo danh.');
    if (!$ids || count($ids) > 10000) throw new BusinessException('Vui lòng chọn từ 1 đến 10000 thí sinh.');
    foreach ($ids as $id) if (!is_string($id) || !preg_match('/\A[A-Za-z0-9_-]{1,20}\z/D', $id)) throw new BusinessException('Mã thí sinh không hợp lệ.');
    $ids = array_values(array_unique($ids));
    $pdo = db(); $pdo->beginTransaction();
    try {
        // Lock in a consistent order, including existing SBDs, so two batches cannot allocate the same next number.
        $rows = $pdo->query('SELECT t.* FROM ThiSinh t ORDER BY t.maThiSinh FOR UPDATE')->fetchAll();
        $byId = array_column($rows, null, 'maThiSinh'); $max = 0;
        foreach ($rows as $row) if (preg_match('/\ASBD([0-9]{1,16})\z/iD', (string)$row['soBaoDanh'], $m)) $max = max($max, (int)$m[1]);
        $errors = [];
        foreach ($ids as $id) {
            $row = $byId[$id] ?? null;
            try {
                if (!$row || (int)$row['dieuKienDuThi'] !== 1 || $row['trangThaiDangKyDuThi'] !== 'Đã duyệt') throw new BusinessException('Thí sinh chưa được duyệt hoặc chưa đủ điều kiện dự thi: ' . $id);
                if ($row['soBaoDanh'] !== null && $row['soBaoDanh'] !== '') throw new BusinessException('Thí sinh đã có số báo danh: ' . $id);
                candidate_number_context($pdo, $row, $exam);
            } catch (BusinessException $e) { $errors[] = $e->getMessage(); }
        }
        if ($errors) throw new BusinessException(implode(' ', $errors), $errors);
        // Use the exact Vietnamese name ordering already shared by UC07; ID is only a final tie-breaker.
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
        $pdo->commit(); return $issued;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
