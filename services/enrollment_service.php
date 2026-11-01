<?php
declare(strict_types=1);

/**
 * UC19 - Xem tình trạng nhập học
 * Nguyễn Thị Cẩm Vân
 * Chỉ đọc dữ liệu, không thay đổi database.
 */

require_once dirname(__DIR__) . '/config/database.php';

/**
 * Danh mục năm tuyển sinh đã có trong database.
 */
function uc19_layDanhSachNam(): array
{
    return db()->query("
        SELECT DISTINCT namTuyenSinh
        FROM DotTuyenSinh
        ORDER BY namTuyenSinh DESC
    ")->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Danh mục trường THPT.
 */
function uc19_layDanhSachTruong(): array
{
    return db()->query("
        SELECT maTruongTHPT, tenTruongTHPT
        FROM TruongTHPT
        ORDER BY tenTruongTHPT
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Kiểm tra mã thí sinh hoặc số báo danh.
 * Theo schema, hai mã có độ dài tối đa 20 ký tự.
 */
function uc19_kiemTraMa(string $ma): bool
{
    if ($ma === '') {
        return true;
    }

    return (bool)preg_match(
        '/\A[A-Za-z0-9_-]{1,20}\z/D',
        $ma
    );
}

/**
 * Lấy danh sách kết quả tuyển sinh đã công bố.
 *
 * Bộ lọc:
 * - Năm tuyển sinh (bắt buộc)
 * - Mã thí sinh
 * - Số báo danh
 * - Trường THPT
 * - Trạng thái nhập học
 */
function layDanhSachTinhTrangNhapHoc(
    int $namTuyenSinh,
    string $maThiSinh = '',
    string $soBaoDanh = '',
    string $maTruongTHPT = '',
    string $trangThai = ''
): array {

    if ($namTuyenSinh <= 0) {
        throw new InvalidArgumentException(
            'Vui lòng chọn năm tuyển sinh.'
        );
    }

    if (
        !uc19_kiemTraMa($maThiSinh) ||
        !uc19_kiemTraMa($soBaoDanh)
    ) {
        throw new InvalidArgumentException(
            'Mã thí sinh hoặc số báo danh không đúng định dạng.'
        );
    }

    $trangThaiHopLe = [
        '',
        'Chưa xác nhận',
        'Đã xác nhận',
        'Không nhập học'
    ];

    if (!in_array($trangThai, $trangThaiHopLe, true)) {
        throw new InvalidArgumentException(
            'Trạng thái nhập học không hợp lệ.'
        );
    }

    $sql = "
        SELECT DISTINCT
            ts.maThiSinh,
            ts.hoTen,
            ts.soBaoDanh,
            kq.maKetQua,
            kq.diemXetTuyen,
            kq.trangThaiXetTuyen,
            kq.maTruongTHPT,
            thpt.tenTruongTHPT,
            tt.maTinhTrang,
            tt.trangThaiNhapHoc,
            tt.thoiGianXacNhanNhapHoc,
            ds.namTuyenSinh

        FROM KetQuaTuyenSinh kts

        INNER JOIN DanhSachKetQuaTuyenSinh ds
            ON ds.maDanhSach COLLATE utf8mb4_unicode_ci
            = kts.maDanhSach COLLATE utf8mb4_unicode_ci

        INNER JOIN KetQuaXetTuyen kq
            ON kq.maKetQua COLLATE utf8mb4_unicode_ci
            = kts.maKetQuaXetTuyen COLLATE utf8mb4_unicode_ci

        INNER JOIN ThiSinh ts
            ON ts.maThiSinh COLLATE utf8mb4_unicode_ci
            = kq.maThiSinh COLLATE utf8mb4_unicode_ci

        LEFT JOIN TruongTHPT thpt
            ON thpt.maTruongTHPT COLLATE utf8mb4_unicode_ci
            = kq.maTruongTHPT COLLATE utf8mb4_unicode_ci

        LEFT JOIN TinhTrangNhapHoc tt
            ON tt.maKetQua COLLATE utf8mb4_unicode_ci
            = kq.maKetQua COLLATE utf8mb4_unicode_ci

        WHERE ds.namTuyenSinh = :nam
          AND ds.trangThaiPheDuyet = 'Đã phê duyệt'
          AND ds.trangThaiCongBo = 'Đã công bố'
    ";

    $params = [
        ':nam' => $namTuyenSinh
    ];

    if ($maThiSinh !== '') {
        $sql .= " AND ts.maThiSinh = :maThiSinh";
        $params[':maThiSinh'] = $maThiSinh;
    }

    if ($soBaoDanh !== '') {
        $sql .= " AND ts.soBaoDanh = :soBaoDanh";
        $params[':soBaoDanh'] = $soBaoDanh;
    }

    if ($maTruongTHPT !== '') {
        $sql .= " AND kq.maTruongTHPT = :maTruong";
        $params[':maTruong'] = $maTruongTHPT;
    }

    if ($trangThai !== '') {
        $sql .= " AND tt.trangThaiNhapHoc = :trangThai";
        $params[':trangThai'] = $trangThai;
    }

    $sql .= "
        ORDER BY ts.hoTen, ts.maThiSinh, kq.maKetQua
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Xem chi tiết kết quả của một thí sinh
 * thuộc danh sách tuyển sinh đã công bố.
 */
function layChiTietTinhTrangNhapHoc(
    string $maKetQua,
    int $namTuyenSinh
): ?array {

    $sql = "
        SELECT
            ts.maThiSinh,
            ts.hoTen,
            ts.soBaoDanh,
            kq.maKetQua,
            kq.diemXetTuyen,
            kq.trangThaiXetTuyen,
            thpt.tenTruongTHPT,
            tt.trangThaiNhapHoc,
            tt.thoiGianXacNhanNhapHoc,
            ds.namTuyenSinh

        FROM KetQuaXetTuyen kq

        INNER JOIN ThiSinh ts
            ON ts.maThiSinh = kq.maThiSinh

        INNER JOIN KetQuaTuyenSinh kts
            ON kts.maKetQuaXetTuyen = kq.maKetQua

        INNER JOIN DanhSachKetQuaTuyenSinh ds
            ON ds.maDanhSach = kts.maDanhSach

        LEFT JOIN TruongTHPT thpt
            ON thpt.maTruongTHPT = kq.maTruongTHPT

        LEFT JOIN TinhTrangNhapHoc tt
            ON tt.maKetQua = kq.maKetQua

        WHERE kq.maKetQua = :maKetQua
          AND ds.namTuyenSinh = :nam
          AND ds.trangThaiPheDuyet = 'Đã phê duyệt'
          AND ds.trangThaiCongBo = 'Đã công bố'

        LIMIT 1
    ";

    $stmt = db()->prepare($sql);

    $stmt->execute([
        ':maKetQua' => $maKetQua,
        ':nam' => $namTuyenSinh
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ?: null;
}
