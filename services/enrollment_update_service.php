<?php
declare(strict_types=1);

/**
 * UC20 - CẬP NHẬT TÌNH TRẠNG NHẬP HỌC
 * Người thực hiện: Nguyễn Thị Cẩm Vân
 * Actor: Hội đồng tuyển sinh
 *
 * Chức năng:
 * 1. Lấy danh sách trường THPT
 * 2. Tìm kiếm thí sinh theo mã, SBD, trường THPT
 * 3. Cập nhật tình trạng nhập học
 * 4. Ghi lịch sử cập nhật
 * 5. Xem lịch sử thay đổi
 *
 * Quy tắc:
 * - Chỉ cập nhật thí sinh trúng tuyển
 * - Kết quả tuyển sinh phải được công bố
 * - Trạng thái mới phải hợp lệ
 * - Cập nhật và ghi lịch sử trong một transaction
 * - Thất bại phải rollback
 */

require_once dirname(__DIR__) . '/config/database.php';

// =============================================
// CÁC TRẠNG THÁI NHẬP HỌC HỢP LỆ
// =============================================

const UC20_TRANG_THAI_HOP_LE = [
    'Chưa xác nhận',
    'Đã xác nhận',
    'Không nhập học'
];

// =============================================
// 1. LẤY DANH SÁCH TRƯỜNG THPT
// =============================================

function uc20_layDanhSachTruong(): array
{
    $sql = "
        SELECT
            maTruongTHPT,
            tenTruongTHPT
        FROM TruongTHPT
        ORDER BY tenTruongTHPT ASC
    ";

    $stmt = db()->query($sql);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =============================================
// 2. KIỂM TRA ĐỊNH DẠNG MÃ
// =============================================

function uc20_kiemTraMa(string $ma): bool
{
    if ($ma === '') {
        return true;
    }

    return (bool)preg_match(
        '/\A[A-Za-z0-9_-]{1,20}\z/D',
        $ma
    );
}

// =============================================
// 3. TÌM KIẾM THÍ SINH
// =============================================


/**
 * UC20 - Tìm kiếm thí sinh trúng tuyển
 * Khắc phục lỗi collation MySQL 1267.
 */
function uc20_layDanhSach(
    string $maThiSinh = '',
    string $soBaoDanh = '',
    string $maTruong = ''
): array {

    $maThiSinh = trim($maThiSinh);
    $soBaoDanh = trim($soBaoDanh);
    $maTruong = trim($maTruong);

    // Kiểm tra định dạng mã thí sinh và SBD
    foreach ([$maThiSinh, $soBaoDanh] as $ma) {
        if (
            $ma !== '' &&
            !preg_match('/\A[A-Za-z0-9_-]{1,20}\z/D', $ma)
        ) {
            throw new InvalidArgumentException(
                'Mã thí sinh hoặc số báo danh không đúng định dạng.'
            );
        }
    }

    $sql = "
        SELECT
            tt.maTinhTrang,
            ts.maThiSinh,
            ts.hoTen,
            ts.soBaoDanh,
            kq.maKetQua,
            kq.trangThaiXetTuyen,
            thpt.tenTruongTHPT,
            tt.trangThaiNhapHoc,
            tt.thoiGianXacNhanNhapHoc

        FROM KetQuaXetTuyen AS kq

        INNER JOIN ThiSinh AS ts
            ON ts.maThiSinh COLLATE utf8mb4_unicode_ci
             = kq.maThiSinh COLLATE utf8mb4_unicode_ci

        INNER JOIN TinhTrangNhapHoc AS tt
            ON tt.maKetQua COLLATE utf8mb4_unicode_ci
             = kq.maKetQua COLLATE utf8mb4_unicode_ci

        LEFT JOIN TruongTHPT AS thpt
            ON thpt.maTruongTHPT COLLATE utf8mb4_unicode_ci
             = kq.maTruongTHPT COLLATE utf8mb4_unicode_ci

        WHERE kq.trangThaiXetTuyen = 'Trúng tuyển'

          AND EXISTS (
              SELECT 1

              FROM KetQuaTuyenSinh AS kts

              INNER JOIN DanhSachKetQuaTuyenSinh AS ds
                  ON ds.maDanhSach COLLATE utf8mb4_unicode_ci
                   = kts.maDanhSach COLLATE utf8mb4_unicode_ci

              WHERE
                  kts.maKetQuaXetTuyen COLLATE utf8mb4_unicode_ci
                    = kq.maKetQua COLLATE utf8mb4_unicode_ci

                AND ds.trangThaiPheDuyet = 'Đã phê duyệt'
                AND ds.trangThaiCongBo = 'Đã công bố'
          )
    ";

    $params = [];

    // Tìm theo mã thí sinh
    if ($maThiSinh !== '') {

        $sql .= "
            AND ts.maThiSinh = :maThiSinh
        ";

        $params[':maThiSinh'] = $maThiSinh;
    }

    // Tìm theo số báo danh
    if ($soBaoDanh !== '') {

        $sql .= "
            AND ts.soBaoDanh = :soBaoDanh
        ";

        $params[':soBaoDanh'] = $soBaoDanh;
    }

    // Lọc theo trường THPT
    if ($maTruong !== '') {

        $sql .= "
            AND kq.maTruongTHPT = :maTruong
        ";

        $params[':maTruong'] = $maTruong;
    }

    $sql .= "
        ORDER BY
            ts.hoTen ASC,
            ts.maThiSinh ASC
    ";

    $stmt = db()->prepare($sql);

    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =============================================
// 4. CẬP NHẬT TÌNH TRẠNG NHẬP HỌC
// =============================================

function uc20_capNhatTinhTrang(
    string $maTinhTrang,
    string $trangThaiMoi,
    string $nguoiCapNhat
): void {

    $maTinhTrang = trim($maTinhTrang);
    $trangThaiMoi = trim($trangThaiMoi);
    $nguoiCapNhat = trim($nguoiCapNhat);

    // Kiểm tra mã tình trạng
    if ($maTinhTrang === '') {
        throw new InvalidArgumentException(
            'Vui lòng chọn thí sinh cần cập nhật.'
        );
    }

    // Kiểm tra trạng thái mới
    if (!in_array(
        $trangThaiMoi,
        UC20_TRANG_THAI_HOP_LE,
        true
    )) {
        throw new InvalidArgumentException(
            'Trạng thái nhập học không hợp lệ.'
        );
    }

    // Kiểm tra người thực hiện
    if ($nguoiCapNhat === '') {
        throw new InvalidArgumentException(
            'Không xác định được người cập nhật.'
        );
    }

    $pdo = db();

    try {

        // Bắt đầu transaction
        $pdo->beginTransaction();

        // =====================================
        // 4.1. KIỂM TRA THÍ SINH
        // =====================================

        $sqlKiemTra = "
            SELECT
                tt.maTinhTrang,
                tt.trangThaiNhapHoc,
                kq.trangThaiXetTuyen

            FROM TinhTrangNhapHoc AS tt

            INNER JOIN KetQuaXetTuyen AS kq
                ON kq.maKetQua = tt.maKetQua

            WHERE tt.maTinhTrang = :maTinhTrang

              AND kq.trangThaiXetTuyen = 'Trúng tuyển'

              AND EXISTS (
                  SELECT 1
                  FROM KetQuaTuyenSinh AS kts

                  INNER JOIN DanhSachKetQuaTuyenSinh AS ds
                      ON ds.maDanhSach = kts.maDanhSach

                  WHERE kts.maKetQuaXetTuyen COLLATE utf8mb4_unicode_ci
                    = kq.maKetQua COLLATE utf8mb4_unicode_ci
                    AND ds.trangThaiPheDuyet = 'Đã phê duyệt'
                    AND ds.trangThaiCongBo = 'Đã công bố'
              )

            FOR UPDATE
        ";

        $stmt = $pdo->prepare($sqlKiemTra);

        $stmt->execute([
            ':maTinhTrang' => $maTinhTrang
        ]);

        $duLieuCu = $stmt->fetch(PDO::FETCH_ASSOC);

        // Không tìm thấy thí sinh hợp lệ
        if (!$duLieuCu) {
            throw new RuntimeException(
                'Thí sinh không thuộc diện trúng tuyển hoặc chưa đủ điều kiện cập nhật tình trạng nhập học.'
            );
        }

        $trangThaiCu = $duLieuCu['trangThaiNhapHoc'];

        // Không cho lưu trạng thái trùng
        if ($trangThaiCu === $trangThaiMoi) {
            throw new InvalidArgumentException(
                'Tình trạng nhập học không thay đổi.'
            );
        }

        // =====================================
        // 4.2. CẬP NHẬT TRẠNG THÁI
        // =====================================

        $sqlUpdate = "
            UPDATE TinhTrangNhapHoc

            SET
                trangThaiNhapHoc = :trangThaiMoi,

                thoiGianXacNhanNhapHoc =
                    CASE
                        WHEN :trangThaiXacNhan = 'Đã xác nhận'
                        THEN NOW()
                        ELSE NULL
                    END

            WHERE maTinhTrang = :maTinhTrang
        ";

        $stmtUpdate = $pdo->prepare($sqlUpdate);

        $stmtUpdate->execute([
            ':trangThaiMoi' => $trangThaiMoi,
            ':trangThaiXacNhan' => $trangThaiMoi,
            ':maTinhTrang' => $maTinhTrang
        ]);

        // =====================================
        // 4.3. TẠO MÃ LỊCH SỬ
        // =====================================

        $maCapNhat = 'CN' . strtoupper(
            bin2hex(random_bytes(8))
        );

        // Nội dung lịch sử
        $noiDungCapNhat = sprintf(
            'Cập nhật tình trạng nhập học từ "%s" sang "%s".',
            $trangThaiCu,
            $trangThaiMoi
        );

        // =====================================
        // 4.4. GHI LỊCH SỬ CẬP NHẬT
        // =====================================

        $sqlLichSu = "
            INSERT INTO LichSuCapNhatNhapHoc (
                maCapNhat,
                maTinhTrang,
                noiDungCapNhat,
                thoiGianCapNhat,
                nguoiCapNhat
            )

            VALUES (
                :maCapNhat,
                :maTinhTrang,
                :noiDung,
                NOW(),
                :nguoiCapNhat
            )
        ";

        $stmtLichSu = $pdo->prepare($sqlLichSu);

        $stmtLichSu->execute([
            ':maCapNhat' => $maCapNhat,
            ':maTinhTrang' => $maTinhTrang,
            ':noiDung' => $noiDungCapNhat,
            ':nguoiCapNhat' => $nguoiCapNhat
        ]);

        // =====================================
        // 4.5. LƯU TRANSACTION
        // =====================================

        $pdo->commit();

    } catch (Throwable $e) {

        // Nếu có lỗi, hoàn tác toàn bộ
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

// =============================================
// 5. LẤY LỊCH SỬ CẬP NHẬT
// =============================================

function uc20_layLichSu(
    string $maTinhTrang
): array {

    $maTinhTrang = trim($maTinhTrang);

    if ($maTinhTrang === '') {
        return [];
    }

    $sql = "
        SELECT
            maCapNhat,
            maTinhTrang,
            noiDungCapNhat,
            thoiGianCapNhat,
            nguoiCapNhat

        FROM LichSuCapNhatNhapHoc

        WHERE maTinhTrang = :maTinhTrang

        ORDER BY
            thoiGianCapNhat DESC,
            maCapNhat DESC
    ";

    $stmt = db()->prepare($sql);

    $stmt->execute([
        ':maTinhTrang' => $maTinhTrang
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
