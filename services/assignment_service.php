<?php
declare(strict_types=1);
/**
 * UC11 – Phân công coi thi (Actor: Hội đồng coi thi).
 * Luồng: chọn điểm thi → phòng thi → môn thi → chọn cán bộ + vai trò → kiểm tra → xác nhận → lưu (1 transaction).
 * Quy tắc:
 *  - Mỗi phòng thi, mỗi môn phải đủ 2 giám thị: có “Giám thị 1” và “Giám thị 2” (mỗi vai trò đúng 1 người).
 *  - Một cán bộ không được phân công 2 lần cùng phòng + môn (trùng phân công).
 *  - Một cán bộ không được coi cùng một môn ở 2 phòng khác nhau trong cùng kỳ thi (trùng lịch).
 *  - Chỉ cán bộ thuộc Hội đồng coi thi của kỳ thi đó mới được phân công.
 * Bảng dùng: DiemThi, PhongThi, MonThi, CanBo, HoiDongThi, PhanCongCoiThi.
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';

const PC_GT1 = 'Giám thị 1';
const PC_GT2 = 'Giám thị 2';
const PC_GIAM_SAT = 'Cán bộ giám sát';
const PC_VAI_TRO = [PC_GT1, PC_GT2, PC_GIAM_SAT];
const PC_KHONG_HOP_LE = 'Thông tin phân công không hợp lệ hoặc chưa đủ 2 giám thị.';
const PC_KHONG_THE_LUU = 'Không thể lưu thông tin phân công coi thi.';

// ---------- Danh sách cho các bước chọn ----------

/** Bước 2: danh sách điểm thi. */
function pc_ds_diem_thi(): array {
    return db()->query('SELECT d.maDiemThi, d.tenDiemThi, d.maKyThi FROM DiemThi d ORDER BY d.maDiemThi')->fetchAll();
}

/** Bước 4: phòng thi thuộc điểm thi đã chọn. */
function pc_ds_phong(string $maDiemThi): array {
    $st = db()->prepare("SELECT maPhong, CONCAT(tenPhong, ' (', maPhong, ')') AS tenHienThi FROM PhongThi WHERE maDiemThi = ? ORDER BY maPhong");
    $st->execute([$maDiemThi]);
    return $st->fetchAll();
}

/** Bước 6: danh sách môn thi. */
function pc_ds_mon(): array {
    return db()->query('SELECT maMonThi, tenMonThi FROM MonThi ORDER BY maMonThi')->fetchAll();
}

/** Thông tin phòng (kèm kỳ thi) nếu phòng thuộc điểm thi, ngược lại null. */
function pc_phong(PDO $pdo, string $maDiemThi, string $maPhong): ?array {
    $st = $pdo->prepare('SELECT p.maPhong, p.tenPhong, d.maDiemThi, d.tenDiemThi, d.maKyThi FROM PhongThi p JOIN DiemThi d ON d.maDiemThi = p.maDiemThi WHERE p.maPhong = ? AND p.maDiemThi = ?');
    $st->execute([$maPhong, $maDiemThi]);
    return $st->fetch() ?: null;
}

/** Các phân công hiện có của phòng + môn. */
function layPhanCongPhong(string $maPhong, string $maMonThi, ?PDO $pdo = null): array {
    $st = ($pdo ?? db())->prepare(
        "SELECT pc.maPhanCong, pc.vaiTro, c.maCanBo, c.hoTen
         FROM PhanCongCoiThi pc JOIN CanBo c ON c.maCanBo = pc.maCanBo
         WHERE pc.maPhong = ? AND pc.maMonThi = ?
         ORDER BY FIELD(pc.vaiTro, 'Giám thị 1', 'Giám thị 2', 'Cán bộ giám sát'), c.maCanBo"
    );
    $st->execute([$maPhong, $maMonThi]);
    return $st->fetchAll();
}

/**
 * Bước 8: cán bộ thuộc Hội đồng coi thi của kỳ thi, kèm tình trạng với phòng + môn đang chọn.
 * Mỗi dòng có: daOPhongNay (đã phân công phòng này), phongKhac (đã coi môn này ở phòng khác), coThe.
 */
function layCanBoCoThePhanCong(string $maKyThi, string $maPhong, string $maMonThi, ?PDO $pdo = null): array {
    $st = ($pdo ?? db())->prepare(
        "SELECT c.maCanBo, c.hoTen, c.chucVu,
                MAX(pc.maPhong = ?) AS daOPhongNay,
                GROUP_CONCAT(CASE WHEN pc.maPhong <> ? THEN pc.maPhong END ORDER BY pc.maPhong SEPARATOR ', ') AS phongKhac
         FROM CanBo c
         JOIN HoiDongThi h ON h.maHoiDongThi = c.maHoiDongThi AND h.loaiHoiDong = 'Coi thi' AND h.maKyThi = ?
         LEFT JOIN PhanCongCoiThi pc ON pc.maCanBo = c.maCanBo AND pc.maMonThi = ?
              AND pc.maPhong IN (SELECT p.maPhong FROM PhongThi p JOIN DiemThi d ON d.maDiemThi = p.maDiemThi WHERE d.maKyThi = ?)
         GROUP BY c.maCanBo, c.hoTen, c.chucVu
         ORDER BY c.maCanBo"
    );
    $st->execute([$maPhong, $maPhong, $maKyThi, $maMonThi, $maKyThi]);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) {
        $r['daOPhongNay'] = (bool)$r['daOPhongNay'];
        $r['coThe'] = !$r['daOPhongNay'] && $r['phongKhac'] === null;
    }
    return $rows;
}

/** Tình trạng giám thị của một phòng + môn từ danh sách phân công. */
function pc_tinh_trang(array $phanCong): array {
    $vt = array_count_values(array_column($phanCong, 'vaiTro'));
    $du = ($vt[PC_GT1] ?? 0) >= 1 && ($vt[PC_GT2] ?? 0) >= 1;
    return ['soGiamThi' => ($vt[PC_GT1] ?? 0) + ($vt[PC_GT2] ?? 0), 'du' => $du];
}

// ---------- Kiểm tra (bước 11) ----------

/**
 * Kiểm tra thông tin phân công. $chon: [maCanBo => vaiTro] lấy từ form.
 * Trả về [ngữ cảnh (phòng, môn, phân công hiện có, cán bộ được chọn), danh sách lỗi].
 */
function kiemTraPhanCong(PDO $pdo, string $maDiemThi, string $maPhong, string $maMonThi, array $chon): array {
    $loi = [];
    $phong = null; $mon = null; $hienCo = []; $canBo = [];
    if ($maDiemThi === '' || $maPhong === '' || $maMonThi === '') {
        return [[], ['Vui lòng chọn đầy đủ điểm thi, phòng thi và môn thi.']];
    }
    $phong = pc_phong($pdo, $maDiemThi, $maPhong);
    if (!$phong) return [[], ['Phòng thi đã chọn không thuộc điểm thi đã chọn.']];
    $st = $pdo->prepare('SELECT maMonThi, tenMonThi FROM MonThi WHERE maMonThi = ?');
    $st->execute([$maMonThi]);
    $mon = $st->fetch();
    if (!$mon) return [[], ['Môn thi đã chọn không tồn tại.']];

    $hienCo = layPhanCongPhong($maPhong, $maMonThi, $pdo);
    $dsCanBo = [];
    foreach (layCanBoCoThePhanCong($phong['maKyThi'], $maPhong, $maMonThi, $pdo) as $c) $dsCanBo[$c['maCanBo']] = $c;

    if (!$chon) $loi[] = 'Vui lòng chọn cán bộ/giám thị cần phân công.';
    foreach ($chon as $ma => $vaiTro) {
        $ma = (string)$ma;
        if (!isset($dsCanBo[$ma])) { $loi[] = "Cán bộ {$ma} không thuộc Hội đồng coi thi của kỳ thi này."; continue; }
        $c = $dsCanBo[$ma];
        $ten = "{$c['hoTen']} ({$ma})";
        if (!in_array($vaiTro, PC_VAI_TRO, true)) $loi[] = "Chưa chọn vai trò hợp lệ cho cán bộ {$ten}.";
        if ($c['daOPhongNay']) $loi[] = "Cán bộ {$ten} đã được phân công coi thi phòng {$maPhong} môn {$mon['tenMonThi']} (trùng phân công).";
        elseif ($c['phongKhac'] !== null) $loi[] = "Cán bộ {$ten} đã được phân công coi thi môn {$mon['tenMonThi']} tại phòng {$c['phongKhac']} (trùng lịch coi thi).";
        $canBo[$ma] = ['maCanBo' => $ma, 'hoTen' => $c['hoTen'], 'vaiTro' => $vaiTro];
    }

    // Mỗi vai trò chỉ một người, tính cả phân công đã có của phòng.
    $sauKhiLuu = array_merge($hienCo, array_values($canBo));
    $dem = array_count_values(array_filter(array_column($sauKhiLuu, 'vaiTro'), fn($v) => in_array($v, PC_VAI_TRO, true)));
    foreach (PC_VAI_TRO as $vt) {
        if (($dem[$vt] ?? 0) > 1) $loi[] = "Vai trò “{$vt}” chỉ được phân công 1 người cho mỗi phòng thi, mỗi môn.";
    }
    if (!pc_tinh_trang($sauKhiLuu)['du']) {
        $loi[] = 'Phòng thi chưa đủ 2 giám thị: cần có đủ “Giám thị 1” và “Giám thị 2” (tính cả phân công đã có).';
    }
    return [['phong' => $phong, 'mon' => $mon, 'hienCo' => $hienCo, 'canBo' => array_values($canBo)], array_values(array_unique($loi))];
}

/** Kiểm tra trước khi hiện bước xác nhận (không ghi dữ liệu). */
function kiemTraTruocXacNhan(string $maDiemThi, string $maPhong, string $maMonThi, array $chon): array {
    [$ctx, $loi] = kiemTraPhanCong(db(), $maDiemThi, $maPhong, $maMonThi, $chon);
    if ($loi) throw new BusinessException(PC_KHONG_HOP_LE, $loi);
    return $ctx;
}

// ---------- Lưu (bước 12) ----------

/** Lưu phân công trong một transaction. Lỗi thì không thay đổi phân công trước đó. Trả về các dòng đã thêm. */
function luuPhanCongCoiThi(string $maDiemThi, string $maPhong, string $maMonThi, array $chon): array {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Khóa các phân công của môn này để hai người không cùng lúc xếp trùng.
        $pdo->prepare('SELECT maPhanCong FROM PhanCongCoiThi WHERE maMonThi = ? FOR UPDATE')->execute([$maMonThi]);
        [$ctx, $loi] = kiemTraPhanCong($pdo, $maDiemThi, $maPhong, $maMonThi, $chon);
        if ($loi) throw new BusinessException(PC_KHONG_HOP_LE, $loi);

        $max = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(maPhanCong, 4) AS UNSIGNED)), 0) FROM PhanCongCoiThi WHERE maPhanCong LIKE 'PCT%' FOR UPDATE")->fetchColumn();
        $ins = $pdo->prepare('INSERT INTO PhanCongCoiThi (maPhanCong, vaiTro, maPhong, maMonThi, maCanBo) VALUES (?, ?, ?, ?, ?)');
        $daThem = [];
        foreach ($ctx['canBo'] as $c) {
            $ma = 'PCT' . str_pad((string)++$max, 3, '0', STR_PAD_LEFT);
            $ins->execute([$ma, $c['vaiTro'], $maPhong, $maMonThi, $c['maCanBo']]);
            $daThem[] = $c + ['maPhanCong' => $ma];
        }
        $pdo->commit();
        return $daThem;
    } catch (BusinessException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('UC11 luu: ' . $e->getMessage());
        throw new BusinessException(PC_KHONG_THE_LUU, ['Thông tin phân công trước đó không bị thay đổi.']);
    }
}

/** Hủy một phân công (để điều chỉnh lại). Trả về thông tin phòng + môn và tình trạng giám thị sau khi hủy. */
function huyPhanCongCoiThi(string $maPhanCong): array {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT pc.maPhanCong, pc.maPhong, pc.maMonThi, pc.vaiTro, c.hoTen FROM PhanCongCoiThi pc JOIN CanBo c ON c.maCanBo = pc.maCanBo WHERE pc.maPhanCong = ? FOR UPDATE');
        $st->execute([$maPhanCong]);
        $pc = $st->fetch();
        if (!$pc) throw new BusinessException('Không tìm thấy phân công coi thi cần hủy.');
        $pdo->prepare('DELETE FROM PhanCongCoiThi WHERE maPhanCong = ?')->execute([$maPhanCong]);
        $pc['tinhTrang'] = pc_tinh_trang(layPhanCongPhong($pc['maPhong'], $pc['maMonThi'], $pdo));
        $pdo->commit();
        return $pc;
    } catch (BusinessException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('UC11 huy: ' . $e->getMessage());
        throw new BusinessException(PC_KHONG_THE_LUU, ['Thông tin phân công trước đó không bị thay đổi.']);
    }
}

/** Tổng quan tình trạng giám thị theo phòng × môn của một điểm thi. */
function tongQuanPhanCong(string $maDiemThi): array {
    $st = db()->prepare(
        "SELECT p.maPhong, m.maMonThi, m.tenMonThi,
                SUM(pc.vaiTro = 'Giám thị 1') AS gt1, SUM(pc.vaiTro = 'Giám thị 2') AS gt2,
                GROUP_CONCAT(CONCAT(c.hoTen, ' – ', pc.vaiTro) ORDER BY pc.vaiTro SEPARATOR '; ') AS danhSach
         FROM PhongThi p CROSS JOIN MonThi m
         LEFT JOIN PhanCongCoiThi pc ON pc.maPhong = p.maPhong AND pc.maMonThi = m.maMonThi
         LEFT JOIN CanBo c ON c.maCanBo = pc.maCanBo
         WHERE p.maDiemThi = ?
         GROUP BY p.maPhong, m.maMonThi, m.tenMonThi
         ORDER BY p.maPhong, m.maMonThi"
    );
    $st->execute([$maDiemThi]);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) $r['du'] = (int)$r['gt1'] >= 1 && (int)$r['gt2'] >= 1;
    return $rows;
}
