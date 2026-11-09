<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';
require_once __DIR__ . '/student_results_service.php';
require_once __DIR__ . '/grading_service.php';

/** Seven calendar days, starting at midnight after publication, in Vietnam time. */
function appeal_window(?string $publishedAt, ?DateTimeImmutable $now = null): array {
    $zone = new DateTimeZone('Asia/Ho_Chi_Minh');
    if (!$publishedAt) return ['open' => false, 'start' => null, 'end' => null];
    $published = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $publishedAt, $zone);
    if (!$published || $published->format('Y-m-d H:i:s') !== $publishedAt) return ['open' => false, 'start' => null, 'end' => null];
    $start = $published->setTime(0, 0)->modify('+1 day');
    $end = $start->modify('+7 days'); // exclusive: 00:00 on the eighth day
    $now ??= new DateTimeImmutable('now', $zone);
    return ['open' => $now >= $start && $now < $end, 'start' => $start, 'end' => $end];
}

/** Old databases remain readable, but cannot accept appeals without a real publication timestamp. */
function appeal_has_publication_time(): bool {
    return (bool)db()->query("SHOW COLUMNS FROM KetQuaChamThi LIKE 'thoiDiemCongBo'")->fetch();
}

function appeal_staff_exam(array $actor, string $exam): void {
    if (($actor['vaiTro'] ?? '') !== 'HD_PHUC_KHAO' || empty($actor['maCanBo'])) throw new BusinessException('Chỉ Hội đồng phúc khảo được lưu kết quả.');
    $q = db()->prepare("SELECT 1 FROM CanBo c JOIN HoiDongThi h ON h.maHoiDongThi=c.maHoiDongThi
        WHERE c.maCanBo=? AND h.maKyThi=? AND h.loaiHoiDong='Phúc khảo'");
    $q->execute([$actor['maCanBo'], $exam]);
    if (!$q->fetchColumn()) throw new BusinessException('Bạn không thuộc Hội đồng phúc khảo của kỳ thi này.');
}

/** Lock admission lists too; a completed round must not receive new score changes. */
function appeal_lock_exam(PDO $pdo, string $exam): void {
    $q = $pdo->prepare('SELECT maDot,thoiGianKetThuc FROM KyThi WHERE maKyThi=? FOR UPDATE');
    $q->execute([$exam]); $row = $q->fetch();
    if (!$row || new DateTimeImmutable($row['thoiGianKetThuc'], new DateTimeZone('Asia/Ho_Chi_Minh')) > new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'))) throw new BusinessException('Kỳ thi chưa kết thúc.');
    $q = $pdo->prepare('SELECT trangThaiPheDuyet,trangThaiCongBo FROM DanhSachKetQuaTuyenSinh WHERE maDot=? FOR UPDATE');
    $q->execute([$row['maDot']]);
    foreach ($q as $list) if ($list['trangThaiPheDuyet'] === 'Đã phê duyệt' || $list['trangThaiCongBo'] === 'Đã công bố') throw new BusinessException('Kết quả tuyển sinh đã được phê duyệt hoặc công bố. Không thể thay đổi dữ liệu phúc khảo.');
}

function appeal_student_papers(array $actor): array {
    $owner = student_results_owner($actor);
    $time = appeal_has_publication_time() ? 'g.thoiDiemCongBo' : 'NULL';
    $q = db()->prepare("SELECT b.maBaiThi,b.maKyThi,k.tenKyThi,m.tenMonThi,g.diemThi,
        $time AS thoiDiemCongBo,y.maYeuCau
        FROM BaiThi b JOIN KyThi k ON k.maKyThi=b.maKyThi
        JOIN MonThi m ON m.maMonThi=b.maMonThi
        JOIN KetQuaChamThi g ON g.maBaiThi=b.maBaiThi
        LEFT JOIN YeuCauPhucKhao y ON y.maBaiThi=b.maBaiThi
        WHERE b.maThiSinh=? AND g.trangThaiCongBo='Đã công bố'
        ORDER BY k.thoiGianKetThuc DESC,b.maBaiThi");
    $q->execute([$owner]); return $q->fetchAll();
}

function appeal_requests(array $actor, int $page = 1): array {
    $page = max(1, min(100000, $page));
    $role = $actor['vaiTro'] ?? ''; $params = [];
    if ($role === 'HOC_SINH') { $scope = 'y.maThiSinh=? AND b.maThiSinh=y.maThiSinh'; $params[] = student_results_owner($actor); }
    elseif ($role === 'HD_PHUC_KHAO' && !empty($actor['maCanBo'])) {
        $scope = "EXISTS (SELECT 1 FROM CanBo c JOIN HoiDongThi h ON h.maHoiDongThi=c.maHoiDongThi WHERE c.maCanBo=? AND h.maKyThi=b.maKyThi AND h.loaiHoiDong='Phúc khảo')";
        $params[] = $actor['maCanBo'];
    } else throw new BusinessException('Không có quyền xem yêu cầu phúc khảo.');
    $q = db()->prepare("SELECT y.maYeuCau,y.maThiSinh,y.maBaiThi,y.lyDo,y.trangThai,y.ngayYeuCau,
        k.tenKyThi,m.tenMonThi,r.diemTruocPhucKhao,r.diemSauPhucKhao,r.ketLuan,
        r.ngayHoanThanh,m.thangDiem,
        (SELECT MAX(bb.noiDung) FROM BienBanPhucKhao bb WHERE bb.maKetQua=r.maKetQua) AS bienBan,
        (SELECT MAX(mc.thongTinMinhChung) FROM MinhChungPhucKhao mc WHERE mc.maKetQua=r.maKetQua) AS minhChung
        FROM YeuCauPhucKhao y JOIN BaiThi b ON b.maBaiThi=y.maBaiThi
        JOIN KyThi k ON k.maKyThi=b.maKyThi JOIN MonThi m ON m.maMonThi=b.maMonThi
        LEFT JOIN KetQuaPhucKhao r ON r.maYeuCau=y.maYeuCau WHERE $scope
        ORDER BY y.ngayYeuCau DESC,y.maYeuCau DESC LIMIT 200 OFFSET ?");
    foreach ($params as $i => $value) $q->bindValue($i + 1, $value, PDO::PARAM_STR);
    $q->bindValue(count($params) + 1, ($page - 1) * 200, PDO::PARAM_INT);
    $q->execute(); return $q->fetchAll();
}

function appeal_submit(array $actor, string $paper, string $reason): string {
    $owner = student_results_owner($actor); $reason = trim($reason);
    if ($paper === '' || strlen($paper) > 20 || $reason === '' || mb_strlen($reason) > 500) throw new BusinessException('Vui lòng chọn bài thi và nhập lý do từ 1 đến 500 ký tự.');
    if (!appeal_has_publication_time()) throw new BusinessException('Chưa có lịch tiếp nhận phúc khảo. Vui lòng liên hệ Ban tuyển sinh.');
    $pdo = db(); $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('SELECT maKyThi FROM BaiThi WHERE maBaiThi=? AND maThiSinh=?');
        $q->execute([$paper, $owner]); $exam = $q->fetchColumn();
        if (!$exam) throw new BusinessException('Không tìm thấy bài thi thuộc hồ sơ của bạn.');
        appeal_lock_exam($pdo, (string)$exam);
        $q = $pdo->prepare("SELECT g.trangThaiCongBo,g.thoiDiemCongBo,b.trangThaiCham
            FROM BaiThi b JOIN KetQuaChamThi g ON g.maBaiThi=b.maBaiThi
            WHERE b.maBaiThi=? AND b.maThiSinh=? FOR UPDATE");
        $q->execute([$paper, $owner]); $score = $q->fetch();
        if (!$score || $score['trangThaiCham'] !== 'Đã chấm' || $score['trangThaiCongBo'] !== 'Đã công bố') throw new BusinessException('Bài thi chưa được công bố điểm.');
        if (!appeal_window($score['thoiDiemCongBo'])['open']) throw new BusinessException('Ngoài thời hạn phúc khảo: từ ngày sau công bố điểm, trong 7 ngày.');
        $q = $pdo->prepare('SELECT maYeuCau FROM YeuCauPhucKhao WHERE maBaiThi=? FOR UPDATE');
        $q->execute([$paper]);
        if ($q->fetchColumn()) throw new BusinessException('Bài thi đã có yêu cầu phúc khảo.');
        $id = 'YC' . bin2hex(random_bytes(8));
        $pdo->prepare('INSERT INTO YeuCauPhucKhao(maYeuCau,maThiSinh,maBaiThi,lyDo) VALUES(?,?,?,?)')->execute([$id,$owner,$paper,$reason]);
        $pdo->commit(); return $id;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}

/** Result, evidence, minutes, official score and audit either all commit or all roll back. */
function appeal_complete(array $actor, string $request, string $scoreRaw, string $conclusion, string $evidence, string $minutes): void {
    if (($actor['vaiTro'] ?? '') !== 'HD_PHUC_KHAO' || empty($actor['maCanBo'])) throw new BusinessException('Chỉ Hội đồng phúc khảo được lưu kết quả.');
    $conclusion = trim($conclusion); $evidence = trim($evidence); $minutes = trim($minutes); $scoreRaw = trim($scoreRaw);
    if ($request === '' || strlen($request) > 20 || !preg_match('/\A\d{1,3}(?:\.\d{1,2})?\z/D', $scoreRaw)) throw new BusinessException('Điểm phúc khảo phải là số không âm, tối đa hai chữ số thập phân.');
    if ($conclusion === '' || mb_strlen($conclusion) > 100 || $evidence === '' || mb_strlen($evidence) > 255 || $minutes === '' || mb_strlen($minutes) > 1000) throw new BusinessException('Vui lòng nhập kết luận (tối đa 100), minh chứng (255) và biên bản (1000 ký tự).');
    $pdo = db(); $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('SELECT b.maKyThi FROM YeuCauPhucKhao y JOIN BaiThi b ON b.maBaiThi=y.maBaiThi WHERE y.maYeuCau=?');
        $q->execute([$request]); $exam = $q->fetchColumn();
        if (!$exam) throw new BusinessException('Không tìm thấy yêu cầu phúc khảo.');
        appeal_staff_exam($actor, (string)$exam); appeal_lock_exam($pdo, (string)$exam);
        $q = $pdo->prepare("SELECT y.trangThai,g.maKetQuaChamThi,g.diemThi,m.thangDiem,b.maThiSinh,y.maThiSinh AS owner
            FROM YeuCauPhucKhao y JOIN BaiThi b ON b.maBaiThi=y.maBaiThi
            JOIN MonThi m ON m.maMonThi=b.maMonThi JOIN KetQuaChamThi g ON g.maBaiThi=b.maBaiThi
            WHERE y.maYeuCau=? FOR UPDATE");
        $q->execute([$request]); $row = $q->fetch();
        if (!$row || $row['maThiSinh'] !== $row['owner']) throw new BusinessException('Dữ liệu bài thi của yêu cầu không hợp lệ.');
        if ($row['trangThai'] !== 'Chờ xử lý') throw new BusinessException('Yêu cầu đã được xử lý hoặc không còn chờ xử lý.');
        $q = $pdo->prepare('SELECT 1 FROM KetQuaPhucKhao WHERE maYeuCau=? FOR UPDATE'); $q->execute([$request]);
        if ($q->fetchColumn()) throw new BusinessException('Yêu cầu đã có kết quả phúc khảo.');
        $score = (float)$scoreRaw;
        if ($score > (float)$row['thangDiem']) throw new BusinessException('Điểm vượt quá thang điểm của môn thi.');
        if (abs($score / GRADING_BUOC_DIEM - round($score / GRADING_BUOC_DIEM)) > 0.00001) throw new BusinessException('Điểm phúc khảo phải theo bước nhảy ' . GRADING_BUOC_DIEM . ' điểm.');
        $id = 'PK' . bin2hex(random_bytes(8));
        $pdo->prepare('INSERT INTO KetQuaPhucKhao(maKetQua,maYeuCau,diemTruocPhucKhao,diemSauPhucKhao,ketLuan,maCanBo) VALUES(?,?,?,?,?,?)')->execute([$id,$request,$row['diemThi'],$score,$conclusion,$actor['maCanBo']]);
        $pdo->prepare('INSERT INTO MinhChungPhucKhao(maMinhChung,thongTinMinhChung,maKetQua) VALUES(?,?,?)')->execute(['MC'.bin2hex(random_bytes(8)),$evidence,$id]);
        $pdo->prepare('INSERT INTO BienBanPhucKhao(maBienBan,maKetQua,noiDung,nguoiLap) VALUES(?,?,?,?)')->execute(['BB'.bin2hex(random_bytes(8)),$id,$minutes,(string)($actor['hoTen'] ?? $actor['maCanBo'])]);
        if ($score !== (float)$row['diemThi']) {
            $pdo->prepare('UPDATE KetQuaChamThi SET diemThi=? WHERE maKetQuaChamThi=?')->execute([$score,$row['maKetQuaChamThi']]);
            $pdo->prepare('INSERT INTO LichSuChinhSuaDiem(maLichSu,diemTruocChinhSua,diemSauChinhSua,nguoiThucHien,lyDoChinhSua,maKetQuaChamThi) VALUES(?,?,?,?,?,?)')->execute(['LP'.bin2hex(random_bytes(8)),$row['diemThi'],$score,(string)($actor['hoTen'] ?? $actor['maCanBo']),'Phúc khảo '.$request.': '.$conclusion,$row['maKetQuaChamThi']]);
        }
        $pdo->prepare("UPDATE YeuCauPhucKhao SET trangThai='Đã xử lý' WHERE maYeuCau=?")->execute([$request]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
