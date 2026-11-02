<?php
declare(strict_types=1);

/** UC22: Công bố kết quả tuyển sinh; tất cả mã chuỗi JOIN dùng cùng collation. */
require_once dirname(__DIR__) . '/config/database.php';

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

function uc22_chiTiet(string $maDanhSach): ?array
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
        SELECT kq.maKetQua, kq.maThiSinh, ts.hoTen, ts.soBaoDanh,
               kq.diemXetTuyen, kq.trangThaiXetTuyen,
               th.tenTruongTHPT
        FROM KetQuaTuyenSinh k
        JOIN KetQuaXetTuyen kq
          ON kq.maKetQua COLLATE utf8mb4_unicode_ci
           = k.maKetQuaXetTuyen COLLATE utf8mb4_unicode_ci
        JOIN ThiSinh ts
          ON ts.maThiSinh COLLATE utf8mb4_unicode_ci
           = kq.maThiSinh COLLATE utf8mb4_unicode_ci
        LEFT JOIN TruongTHPT th
          ON th.maTruongTHPT COLLATE utf8mb4_unicode_ci
           = kq.maTruongTHPT COLLATE utf8mb4_unicode_ci
        WHERE k.maDanhSach = :ma
        ORDER BY ts.hoTen, ts.maThiSinh
    ");
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

    $tienDo = uc22_tienDoChamThi((string)$ds['maDot']);
    if ($tienDo['tong'] === 0) {
        $loi[] = 'Chưa có bài thi trong đợt tuyển sinh.';
    } elseif ($tienDo['daCham'] < $tienDo['tong'] || $tienDo['daCongBo'] < $tienDo['tong']) {
        $loi[] = 'Điểm thi chưa chốt/công bố đầy đủ (' . $tienDo['daCongBo'] . '/' . $tienDo['tong'] . ' bài).';
    }
    return $loi;
}

/** Giao dịch chỉ cập nhật trạng thái công bố; không sửa điểm/kết quả xét tuyển. */
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

        $ds = uc22_chiTiet($maDanhSach);
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
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
