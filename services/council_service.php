<?php
declare(strict_types=1);
/**
 * UC10 – Quản lý Hội đồng thi (Actor: Ban Tuyển sinh Sở GD&ĐT).
 * Báo cáo mô tả xem + cập nhật; thêm và xóa dùng cùng khuôn kiểm tra → lưu → thông báo.
 * Bảng dùng: HoiDongThi, KyThi, CanBo.
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';

const HD_LOAI = ['Ra đề', 'Coi thi', 'Chấm thi', 'Phúc khảo', 'Tuyển sinh'];
const HD_KHONG_THE_LUU = 'Không thể lưu thông tin Hội đồng thi.';
const HD_KHONG_TIM_THAY = 'Không tìm thấy Hội đồng thi được chọn.';

function hd_ds_ky_thi(): array {
    return db()->query('SELECT maKyThi, tenKyThi FROM KyThi ORDER BY thoiGianKetThuc DESC, maKyThi')->fetchAll();
}

/** Bước 2: danh sách Hội đồng thi hiện có. */
function layDanhSachHoiDongThi(): array {
    return db()->query(
        'SELECT h.maHoiDongThi, h.tenHoiDongThi, h.loaiHoiDong, h.thongTinHoiDong, k.tenKyThi, COUNT(c.maCanBo) AS soCanBo
         FROM HoiDongThi h
         JOIN KyThi k ON k.maKyThi = h.maKyThi
         LEFT JOIN CanBo c ON c.maHoiDongThi = h.maHoiDongThi
         GROUP BY h.maHoiDongThi, h.tenHoiDongThi, h.loaiHoiDong, h.thongTinHoiDong, k.tenKyThi
         ORDER BY h.maHoiDongThi'
    )->fetchAll();
}

/** Bước 4: thông tin Hội đồng thi được chọn, kèm danh sách cán bộ thuộc hội đồng. */
function layThongTinHoiDongThi(string $ma): array {
    $st = db()->prepare(
        'SELECT h.*, k.tenKyThi FROM HoiDongThi h JOIN KyThi k ON k.maKyThi = h.maKyThi WHERE h.maHoiDongThi = ?'
    );
    $st->execute([$ma]);
    $hd = $st->fetch();
    if (!$hd) throw new BusinessException(HD_KHONG_TIM_THAY);
    $st = db()->prepare('SELECT maCanBo, hoTen, chucVu, soDienThoai FROM CanBo WHERE maHoiDongThi = ? ORDER BY maCanBo');
    $st->execute([$ma]);
    $hd['canBo'] = $st->fetchAll();
    return $hd;
}

/**
 * Bước 9: kiểm tra thông tin. Trả về [dữ liệu đã chuẩn hóa, danh sách lỗi].
 * @param array{tenHoiDongThi:string,loaiHoiDong:string,maKyThi:string,thongTinHoiDong:string} $in
 */
function kiemTraThongTinHoiDong(PDO $pdo, array $in, ?string $maDangSua): array {
    $d = [
        'tenHoiDongThi' => trim($in['tenHoiDongThi'] ?? ''),
        'loaiHoiDong' => trim($in['loaiHoiDong'] ?? ''),
        'maKyThi' => trim($in['maKyThi'] ?? ''),
        'thongTinHoiDong' => trim($in['thongTinHoiDong'] ?? ''),
    ];
    $loi = [];
    if ($d['tenHoiDongThi'] === '') $loi[] = 'Tên Hội đồng thi không được để trống.';
    elseif (mb_strlen($d['tenHoiDongThi'], 'UTF-8') > 100) $loi[] = 'Tên Hội đồng thi tối đa 100 ký tự.';
    if (!in_array($d['loaiHoiDong'], HD_LOAI, true)) $loi[] = 'Vui lòng chọn loại hội đồng hợp lệ.';
    if (mb_strlen($d['thongTinHoiDong'], 'UTF-8') > 255) $loi[] = 'Thông tin hội đồng tối đa 255 ký tự.';
    if ($d['maKyThi'] === '') {
        $loi[] = 'Vui lòng chọn kỳ thi.';
    } else {
        $st = $pdo->prepare('SELECT 1 FROM KyThi WHERE maKyThi = ?');
        $st->execute([$d['maKyThi']]);
        if (!$st->fetchColumn()) $loi[] = 'Kỳ thi đã chọn không tồn tại.';
    }
    if ($d['tenHoiDongThi'] !== '' && $d['maKyThi'] !== '') {
        $st = $pdo->prepare('SELECT maHoiDongThi FROM HoiDongThi WHERE tenHoiDongThi = ? AND maKyThi = ? AND maHoiDongThi <> ?');
        $st->execute([$d['tenHoiDongThi'], $d['maKyThi'], $maDangSua ?? '']);
        $trung = $st->fetchColumn();
        if ($trung) $loi[] = "Tên Hội đồng thi đã được dùng cho hội đồng {$trung} trong cùng kỳ thi.";
    }
    if ($d['thongTinHoiDong'] === '') $d['thongTinHoiDong'] = null;
    return [$d, $loi];
}

/** Chạy $fn trong transaction; lỗi nghiệp vụ giữ nguyên, lỗi CSDL đổi thành thông báo “không thể lưu”. */
function hd_trong_transaction(callable $fn): mixed {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $kq = $fn($pdo);
        $pdo->commit();
        return $kq;
    } catch (BusinessException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('UC10: ' . $e->getMessage());
        throw new BusinessException(HD_KHONG_THE_LUU, ['Thông tin Hội đồng thi trước đó không bị thay đổi.']);
    }
}

/** Bước 8–10: cập nhật thông tin Hội đồng thi. */
function capNhatHoiDongThi(string $ma, array $in): void {
    hd_trong_transaction(function (PDO $pdo) use ($ma, $in) {
        $st = $pdo->prepare('SELECT 1 FROM HoiDongThi WHERE maHoiDongThi = ? FOR UPDATE');
        $st->execute([$ma]);
        if (!$st->fetchColumn()) throw new BusinessException(HD_KHONG_TIM_THAY);
        [$d, $loi] = kiemTraThongTinHoiDong($pdo, $in, $ma);
        if ($loi) throw new BusinessException('Thông tin cập nhật không hợp lệ.', $loi);
        $pdo->prepare('UPDATE HoiDongThi SET tenHoiDongThi = ?, thongTinHoiDong = ?, loaiHoiDong = ?, maKyThi = ? WHERE maHoiDongThi = ?')
            ->execute([$d['tenHoiDongThi'], $d['thongTinHoiDong'], $d['loaiHoiDong'], $d['maKyThi'], $ma]);
    });
}

/** Thêm Hội đồng thi mới, mã tự sinh dạng HD06, HD07… Trả về mã mới. */
function themHoiDongThi(array $in): string {
    return hd_trong_transaction(function (PDO $pdo) use ($in) {
        [$d, $loi] = kiemTraThongTinHoiDong($pdo, $in, null);
        if ($loi) throw new BusinessException('Thông tin Hội đồng thi không hợp lệ.', $loi);
        $max = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(maHoiDongThi, 3) AS UNSIGNED)), 0) FROM HoiDongThi WHERE maHoiDongThi LIKE 'HD%' FOR UPDATE")->fetchColumn();
        $ma = 'HD' . str_pad((string)($max + 1), 2, '0', STR_PAD_LEFT);
        $pdo->prepare('INSERT INTO HoiDongThi (maHoiDongThi, tenHoiDongThi, thongTinHoiDong, loaiHoiDong, maKyThi) VALUES (?, ?, ?, ?, ?)')
            ->execute([$ma, $d['tenHoiDongThi'], $d['thongTinHoiDong'], $d['loaiHoiDong'], $d['maKyThi']]);
        return $ma;
    });
}

/** Xóa Hội đồng thi; chặn khi còn cán bộ thuộc hội đồng. */
function xoaHoiDongThi(string $ma): void {
    hd_trong_transaction(function (PDO $pdo) use ($ma) {
        $st = $pdo->prepare('SELECT 1 FROM HoiDongThi WHERE maHoiDongThi = ? FOR UPDATE');
        $st->execute([$ma]);
        if (!$st->fetchColumn()) throw new BusinessException(HD_KHONG_TIM_THAY);
        $st = $pdo->prepare('SELECT COUNT(*) FROM CanBo WHERE maHoiDongThi = ?');
        $st->execute([$ma]);
        $n = (int)$st->fetchColumn();
        if ($n > 0) throw new BusinessException("Không thể xóa Hội đồng thi vì đang có {$n} cán bộ thuộc hội đồng này.", ['Hãy chuyển các cán bộ sang hội đồng khác trước khi xóa.']);
        $pdo->prepare('DELETE FROM HoiDongThi WHERE maHoiDongThi = ?')->execute([$ma]);
    });
}
