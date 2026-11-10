<?php
declare(strict_types=1);

/** UC22: Công bố kết quả tuyển sinh; tất cả mã chuỗi JOIN dùng cùng collation. */
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/appeal_service.php';

function uc22_danhSachNam(): array
{
    return db()->query('SELECT DISTINCT namTuyenSinh FROM DanhSachKetQuaTuyenSinh ORDER BY namTuyenSinh DESC')
        ->fetchAll(PDO::FETCH_COLUMN);
}


function uc22_danhSach(int $nam): array
{
    $sql = "
        SELECT
            ds.maDanhSach,
            ds.namTuyenSinh,
            ds.maDot,

            (
                SELECT MIN(d.tenDot)
                FROM DotTuyenSinh d
                WHERE d.maDot COLLATE utf8mb4_unicode_ci
                    = ds.maDot COLLATE utf8mb4_unicode_ci
            ) AS tenDot,

            ds.trangThaiPheDuyet,
            ds.trangThaiCongBo,

            (
                SELECT COUNT(*)
                FROM KetQuaTuyenSinh k
                WHERE k.maDanhSach COLLATE utf8mb4_unicode_ci
                    = ds.maDanhSach COLLATE utf8mb4_unicode_ci
            ) AS tongKetQua

        FROM DanhSachKetQuaTuyenSinh ds

        WHERE (:nam = 0 OR ds.namTuyenSinh = :nam2)

        ORDER BY
            ds.namTuyenSinh DESC,
            ds.maDanhSach
    ";

    $stmt = db()->prepare($sql);

    $stmt->execute([
        ':nam' => $nam,
        ':nam2' => $nam
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function uc22_chiTiet(string $maDanhSach, bool $lock = false): ?array
{
    $stmt = db()->prepare("
        SELECT
            ds.maDanhSach,
            ds.namTuyenSinh,
            ds.maDot,

            (
                SELECT MIN(d.tenDot)
                FROM DotTuyenSinh d
                WHERE d.maDot COLLATE utf8mb4_unicode_ci
                    = ds.maDot COLLATE utf8mb4_unicode_ci
            ) AS tenDot,

            ds.trangThaiPheDuyet,
            ds.trangThaiCongBo

        FROM DanhSachKetQuaTuyenSinh ds

        WHERE ds.maDanhSach = :ma

        LIMIT 1
    ");

    $stmt->execute([
        ':ma' => $maDanhSach
    ]);

    $ds = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ds) {
        return null;
    }


    $stmt = db()->prepare("
        SELECT k.maKetQuaXetTuyen AS maLienKet, kq.maKetQua, kq.maThiSinh, ts.hoTen, ts.soBaoDanh,
               kq.diemXetTuyen, kq.trangThaiXetTuyen,
               kq.maNguyenVong, kq.maTruongTHPT, th.tenTruongTHPT,
               nv.maNguyenVong AS nguyenVongTonTai, nv.maThiSinh AS chuNguyenVong,
               nv.thuTuNguyenVong, nv.maTruongTHPT AS truongNguyenVong,
               nt.maTruongTHPT AS truongNguyenVongTonTai,
               qd.namTuyenSinh AS namNguyenVong, qd.soLuongNguyenVongToiDa,
               ts.maThiSinh AS thiSinhTonTai
        FROM KetQuaTuyenSinh k
        LEFT JOIN KetQuaXetTuyen kq
          ON kq.maKetQua COLLATE utf8mb4_unicode_ci
           = k.maKetQuaXetTuyen COLLATE utf8mb4_unicode_ci
        LEFT JOIN ThiSinh ts
          ON ts.maThiSinh COLLATE utf8mb4_unicode_ci
           = kq.maThiSinh COLLATE utf8mb4_unicode_ci
        LEFT JOIN TruongTHPT th
          ON th.maTruongTHPT COLLATE utf8mb4_unicode_ci
           = kq.maTruongTHPT COLLATE utf8mb4_unicode_ci
        LEFT JOIN NguyenVong nv ON nv.maNguyenVong COLLATE utf8mb4_unicode_ci = kq.maNguyenVong COLLATE utf8mb4_unicode_ci
        LEFT JOIN TruongTHPT nt ON nt.maTruongTHPT COLLATE utf8mb4_unicode_ci = nv.maTruongTHPT COLLATE utf8mb4_unicode_ci
        LEFT JOIN QuyDinhNguyenVong qd ON qd.maQuyDinh COLLATE utf8mb4_unicode_ci = nv.maQuyDinh COLLATE utf8mb4_unicode_ci
        WHERE k.maDanhSach = :ma
        ORDER BY ts.hoTen, ts.maThiSinh
    " . ($lock ? ' FOR UPDATE' : ''));
    $stmt->execute([':ma' => $maDanhSach]);
    $ds['ketQua'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $ds['tong'] = count($ds['ketQua']);
    $ds['trungTuyen'] = 0;
    $ds['chuaXet'] = 0;
    foreach ($ds['ketQua'] as $row) {
        if ($row['trangThaiXetTuyen'] === 'Trúng tuyển') $ds['trungTuyen']++;
        if ($row['trangThaiXetTuyen'] === 'Chưa xét') $ds['chuaXet']++;
    }
    return $ds;
}

/** Trạng thái chốt điểm thi của đúng đợt tuyển sinh. */
function uc22_tienDoChamThi(string $maDot): array
{
    $stmt = db()->prepare("
        SELECT COUNT(*) AS tong,
               COUNT(ch.maKetQuaChamThi) AS daCham,
               SUM(CASE WHEN ch.trangThaiCongBo = 'Đã công bố' THEN 1 ELSE 0 END) AS daCongBo
        FROM BaiThi b
        JOIN KyThi kt
          ON kt.maKyThi COLLATE utf8mb4_unicode_ci
           = b.maKyThi COLLATE utf8mb4_unicode_ci
        LEFT JOIN KetQuaChamThi ch
          ON ch.maBaiThi COLLATE utf8mb4_unicode_ci
           = b.maBaiThi COLLATE utf8mb4_unicode_ci
        WHERE kt.maDot = :maDot
    ");
    $stmt->execute([':maDot' => $maDot]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return ['tong' => (int)$row['tong'], 'daCham' => (int)$row['daCham'],
            'daCongBo' => (int)$row['daCongBo']];
}

/** Kiểm tra trước khi công bố, chỉ trả về các nguyên nhân chưa đủ điều kiện. */
function uc22_kiemTra(array $ds): array
{
    $loi = [];
    if ($ds['trangThaiPheDuyet'] !== 'Đã phê duyệt') {
        $loi[] = 'Danh sách chưa được phê duyệt.';
    }
    if ($ds['trangThaiCongBo'] === 'Đã công bố') {
        $loi[] = 'Danh sách đã được công bố trước đó.';
    }
    if ($ds['tong'] === 0) $loi[] = 'Danh sách chưa có kết quả xét tuyển.';
    if ($ds['chuaXet'] > 0) $loi[] = 'Còn ' . $ds['chuaXet'] . ' kết quả chưa xét tuyển.';
    $winners = [];
    foreach ($ds['ketQua'] as $row) {
        $id = (string)($row['maLienKet'] ?? $row['maKetQua'] ?? '');
        $invalid = !$row['maKetQua'] || !$row['thiSinhTonTai'] || !$row['nguyenVongTonTai']
            || $row['chuNguyenVong'] !== $row['maThiSinh'] || !$row['truongNguyenVongTonTai']
            || (int)$row['namNguyenVong'] !== (int)$ds['namTuyenSinh']
            || (int)$row['thuTuNguyenVong'] < 1 || (int)$row['thuTuNguyenVong'] > min(3, (int)$row['soLuongNguyenVongToiDa'])
            || !is_numeric($row['diemXetTuyen']) || !is_finite((float)$row['diemXetTuyen']) || (float)$row['diemXetTuyen'] < 0
            || !in_array($row['trangThaiXetTuyen'], ['Trúng tuyển', 'Không trúng tuyển'], true);
        if ($row['trangThaiXetTuyen'] === 'Trúng tuyển') {
            $invalid = $invalid || !$row['maTruongTHPT'] || !$row['tenTruongTHPT']
                || $row['maTruongTHPT'] !== $row['truongNguyenVong'];
            if (isset($winners[$row['maThiSinh']])) $invalid = true;
            $winners[$row['maThiSinh']] = $row['maKetQua'];
        } elseif ($row['maTruongTHPT'] !== null && $row['maTruongTHPT'] !== ''
            && $row['maTruongTHPT'] !== $row['truongNguyenVong']) $invalid = true;
        if ($invalid) $loi[] = 'Dữ liệu kết quả xét tuyển chưa đầy đủ hoặc chưa hợp lệ: ' . $id . '.';
    }
    $dot = db()->prepare('SELECT namTuyenSinh FROM DotTuyenSinh WHERE maDot=?' . (db()->inTransaction() ? ' FOR UPDATE' : ''));
    $dot->execute([$ds['maDot']]);
    if ((int)$dot->fetchColumn() !== (int)$ds['namTuyenSinh']) $loi[] = 'Năm tuyển sinh của danh sách không khớp đợt tuyển sinh.';
    if ($winners) {
        $other = db()->prepare("SELECT r.maThiSinh,r.maKetQua FROM KetQuaTuyenSinh l
            JOIN DanhSachKetQuaTuyenSinh d ON d.maDanhSach=l.maDanhSach
            JOIN KetQuaXetTuyen r ON r.maKetQua=l.maKetQuaXetTuyen
            WHERE d.maDot=? AND d.maDanhSach<>? AND r.trangThaiXetTuyen='Trúng tuyển'
              AND (d.trangThaiPheDuyet='Đã phê duyệt' OR d.trangThaiCongBo='Đã công bố')" . (db()->inTransaction() ? ' FOR UPDATE' : ''));
        $other->execute([$ds['maDot'], $ds['maDanhSach']]);
        foreach ($other as $row) if (isset($winners[$row['maThiSinh']]) && $winners[$row['maThiSinh']] !== $row['maKetQua']) {
            $loi[] = 'Thí sinh đã trúng tuyển ở kết quả khác: ' . $row['maThiSinh'] . '.';
        }
    }

    $tienDo = uc22_tienDoChamThi((string)$ds['maDot']);
    if ($tienDo['tong'] === 0) {
        $loi[] = 'Chưa có bài thi trong đợt tuyển sinh.';
    } elseif ($tienDo['daCham'] < $tienDo['tong'] || $tienDo['daCongBo'] < $tienDo['tong']) {
        $loi[] = 'Điểm thi chưa chốt/công bố đầy đủ (' . $tienDo['daCongBo'] . '/' . $tienDo['tong'] . ' bài).';
    }
    if (appeal_has_publication_time()) {
        $q = db()->prepare('SELECT g.thoiDiemCongBo FROM BaiThi b JOIN KyThi k ON k.maKyThi=b.maKyThi LEFT JOIN KetQuaChamThi g ON g.maBaiThi=b.maBaiThi WHERE k.maDot=?');
        $q->execute([$ds['maDot']]);
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        foreach ($q as $grade) {
            $window = appeal_window($grade['thoiDiemCongBo']);
            if (!$window['end'] || $now < $window['end']) { $loi[] = 'Chưa hết thời hạn phúc khảo 7 ngày hoặc chưa xác định được thời điểm công bố điểm.'; break; }
        }
    } else $loi[] = 'Chưa xác định được thời điểm công bố điểm để kiểm tra hạn phúc khảo.';
    $q = db()->prepare("SELECT COUNT(*) FROM YeuCauPhucKhao y JOIN BaiThi b ON b.maBaiThi=y.maBaiThi JOIN KyThi k ON k.maKyThi=b.maKyThi LEFT JOIN KetQuaPhucKhao r ON r.maYeuCau=y.maYeuCau WHERE k.maDot=? AND (r.maKetQua IS NULL OR y.trangThai<>'Đã xử lý')");
    $q->execute([$ds['maDot']]);
    if ((int)$q->fetchColumn() > 0) $loi[] = 'Còn yêu cầu phúc khảo chưa được xử lý.';
    return $loi;
}

/** Publish and create missing enrollment records atomically; preserve any existing confirmations. */
function uc22_congBo(string $maDanhSach): void
{
    if ($maDanhSach === '' || strlen($maDanhSach) > 20) {
        throw new InvalidArgumentException('Mã danh sách không hợp lệ.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT maDanhSach FROM DanhSachKetQuaTuyenSinh WHERE maDanhSach = :ma FOR UPDATE');
        $lock->execute([':ma' => $maDanhSach]);
        if (!$lock->fetch()) throw new DomainException('Không tìm thấy danh sách kết quả.');

        $ds = uc22_chiTiet($maDanhSach, true);
        if ($ds === null) throw new DomainException('Không tìm thấy danh sách kết quả.');
        $loi = uc22_kiemTra($ds);
        if ($loi) throw new DomainException(implode(' ', $loi));

        $stmt = $pdo->prepare("
            UPDATE DanhSachKetQuaTuyenSinh
            SET trangThaiCongBo = 'Đã công bố'
            WHERE maDanhSach = :ma
              AND trangThaiPheDuyet = 'Đã phê duyệt'
              AND trangThaiCongBo <> 'Đã công bố'
        ");
        $stmt->execute([':ma' => $maDanhSach]);
        if ($stmt->rowCount() !== 1) throw new DomainException('Trạng thái danh sách đã thay đổi. Vui lòng tải lại.');
        $winners = $pdo->prepare("SELECT kq.maKetQua, tt.maTinhTrang
            FROM KetQuaTuyenSinh k
            JOIN KetQuaXetTuyen kq ON kq.maKetQua COLLATE utf8mb4_unicode_ci=k.maKetQuaXetTuyen COLLATE utf8mb4_unicode_ci
            LEFT JOIN TinhTrangNhapHoc tt ON tt.maKetQua COLLATE utf8mb4_unicode_ci=kq.maKetQua COLLATE utf8mb4_unicode_ci
            WHERE k.maDanhSach=? AND kq.trangThaiXetTuyen='Trúng tuyển'
            ORDER BY kq.maKetQua FOR UPDATE");
        $winners->execute([$maDanhSach]);
        $insertEnrollment = $pdo->prepare("INSERT INTO TinhTrangNhapHoc(maTinhTrang,maKetQua,trangThaiNhapHoc) VALUES(?,?,'Chưa xác nhận')");
        foreach ($winners->fetchAll() as $winner) {
            if ($winner['maTinhTrang'] === null) $insertEnrollment->execute(['NH'.bin2hex(random_bytes(8)), $winner['maKetQua']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
