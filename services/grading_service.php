<?php
declare(strict_types=1);
/**
 * Nghiệp vụ chấm thi và công bố điểm (UC12 – UC15).
 * Bảng dùng: KyThi, MonThi, BaiThi, KetQuaChamThi, LichSuChinhSuaDiem.
 * Tên hàm bám theo thiết kế lớp trong báo cáo Tuần 08; câu thông báo bám nguyên văn báo cáo giữa kỳ (mục 3.4).
 *
 * Lỗi nghiệp vụ ném ra bằng GradingException: message là câu thông báo trong báo cáo,
 * details() là các gợi ý thêm (không bắt buộc) hiện bên dưới.
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';

/** Mặc định khi gọi từ nơi không có người đăng nhập (trang cập nhật truyền tên tài khoản đang đăng nhập). */
const GRADING_NGUOI_THUC_HIEN = 'Hội đồng chấm thi';
const GRADING_DA_CONG_BO = 'Đã công bố';
const GRADING_DA_CHAM = 'Đã chấm';
/** Bước nhảy điểm hợp lệ (UC13 – 9.1 “sai bước nhảy điểm”). */
const GRADING_BUOC_DIEM = 0.25;

// ---------- Câu thông báo theo báo cáo giữa kỳ ----------
// UC05
const TB_NHAP_DIEM_THANH_CONG = 'Nhập điểm thi thành công.';
const TB_KHONG_CO_BAI_THI = 'Không có bài thi cần nhập điểm.';
const TB_DIEM_NHAP_KHONG_HOP_LE = 'Điểm thi không hợp lệ.';
const TB_BAI_THI_CHUA_DU_DIEU_KIEN = 'Bài thi chưa đủ điều kiện nhập điểm.';
const TB_DIEM_DA_XAC_NHAN = 'Điểm bài thi đã được xác nhận chính thức.';
const TB_LOI_LUU_DIEM = 'Không thể lưu điểm thi.';

// UC12
const TB_TRA_CUU_THIEU = 'Vui lòng nhập đầy đủ điều kiện tra cứu.';
const TB_KHONG_TIM_THAY_KQ = 'Không tìm thấy kết quả chấm thi phù hợp.';
const TB_CHUA_CO_LICH_SU = 'Kết quả chấm thi này chưa có lịch sử chỉnh sửa điểm.';
const TB_LOI_TRUY_XUAT_LICH_SU = 'Không thể truy xuất lịch sử chỉnh sửa điểm. Vui lòng thử lại sau.';
// UC13
const TB_PHACH_KHONG_HOP_LE = 'Mã phách không hợp lệ. Vui lòng kiểm tra lại.';
const TB_KHONG_TIM_THAY_BAI = 'Không tìm thấy bài thi tương ứng với mã phách.';
const TB_CHUA_CO_KET_QUA = 'Bài thi chưa có kết quả chấm thi để cập nhật.';
const TB_DIEM_KHONG_HOP_LE = 'Điểm cập nhật không hợp lệ.';
const TB_THIEU_LY_DO = 'Vui lòng nhập lý do chỉnh sửa điểm.';
const TB_CAP_NHAT_THANH_CONG = 'Cập nhật kết quả chấm thi thành công.';
const TB_LOI_CAP_NHAT = 'Không thể cập nhật kết quả chấm thi. Vui lòng thực hiện lại.';
const TB_LOI_GHI_LICH_SU = 'Không thể ghi nhận lịch sử chỉnh sửa điểm. Kết quả chấm thi chưa được cập nhật.';
// UC14
const TB_CHON_KY_MON = 'Vui lòng chọn đầy đủ Kỳ thi và Môn thi.';
const TB_CHUA_CO_DU_LIEU = 'Chưa có dữ liệu kết quả chấm thi cho Kỳ thi và Môn thi được chọn.';
const TB_LOI_THONG_KE = 'Không thể thực hiện thống kê tại thời điểm hiện tại. Vui lòng thử lại sau.';
// UC15
const TB_DA_CONG_BO = 'Kết quả chấm thi đã được công bố.';
const TB_CHUA_DU_DIEU_KIEN = 'Kết quả chấm thi chưa đủ điều kiện công bố.';
const TB_CONG_BO_THANH_CONG = 'Công bố điểm thi thành công.';
const TB_LOI_CONG_BO = 'Không thể công bố điểm thi tại thời điểm hiện tại. Vui lòng thử lại sau.';

final class GradingException extends BusinessException {}

// ---------- Danh mục dùng chung ----------

function grading_ds_ky_thi(): array {
    return db()->query('SELECT maKyThi, tenKyThi FROM KyThi ORDER BY thoiGianKetThuc DESC, maKyThi')->fetchAll();
}

function grading_ds_mon_thi(): array {
    return db()->query('SELECT maMonThi, tenMonThi, thangDiem FROM MonThi ORDER BY maMonThi')->fetchAll();
}

function grading_chuan_hoa_phach(string $soPhach): string {
    return strtoupper(trim($soPhach));
}

function grading_fmt_diem(mixed $diem): string {
    if ($diem === null || $diem === '') return '—';
    return number_format((float)$diem, 2, '.', '');
}

/** 10.00 → “10”, 7.50 → “7.5”. */
function grading_fmt_gon(mixed $diem): string {
    return rtrim(rtrim(grading_fmt_diem($diem), '0'), '.');
}

// ---------- UC12: Xem lịch sử chỉnh sửa điểm ----------

/** Bước 4–6: tìm kết quả chấm thi theo kỳ thi + môn thi + mã phách. */
function timBaiThi(string $maKyThi, string $maMonThi, string $soPhach): array {
    $soPhach = grading_chuan_hoa_phach($soPhach);
    if ($maKyThi === '' || $maMonThi === '' || $soPhach === '') throw new GradingException(TB_TRA_CUU_THIEU);
    $st = db()->prepare(
        'SELECT b.maBaiThi, b.soPhach, b.trangThaiCham, kt.maKyThi, kt.tenKyThi, m.maMonThi, m.tenMonThi, m.thangDiem,
                k.maKetQuaChamThi, k.diemThi, k.nguoiNhap, k.thoiDiemNhap, k.trangThaiCongBo
         FROM BaiThi b
         JOIN KyThi kt ON kt.maKyThi = b.maKyThi
         JOIN MonThi m ON m.maMonThi = b.maMonThi
         JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi
         WHERE b.maKyThi = ? AND b.maMonThi = ? AND b.soPhach = ?'
    );
    $st->execute([$maKyThi, $maMonThi, $soPhach]);
    $row = $st->fetch();
    if (!$row) throw new GradingException(TB_KHONG_TIM_THAY_KQ);
    return $row;
}

/** Bước 9: các lần chỉnh sửa điểm của một kết quả chấm thi, mới nhất trước. */
function layLichSuChinhSua(string $maKetQuaChamThi): array {
    $st = db()->prepare(
        'SELECT maLichSu, diemTruocChinhSua, diemSauChinhSua, thoiGianChinhSua, nguoiThucHien, lyDoChinhSua
         FROM LichSuChinhSuaDiem WHERE maKetQuaChamThi = ?
         ORDER BY thoiGianChinhSua DESC, maLichSu DESC'
    );
    $st->execute([$maKetQuaChamThi]);
    return $st->fetchAll();
}

// ---------- UC13: Cập nhật kết quả chấm thi ----------

/** Bước 4: mã phách không trống, chỉ gồm chữ không dấu và số (vd P0052). */
function grading_phach_hop_le(string $soPhach): bool {
    return (bool)preg_match('/^[A-Z0-9]{2,20}$/', $soPhach);
}

/** Bước 4–6: tìm bài thi theo mã phách và kết quả chấm tương ứng. */
function timBaiThiTheoMaPhach(string $soPhach, ?PDO $pdo = null, bool $forUpdate = false): array {
    $soPhach = grading_chuan_hoa_phach($soPhach);
    if (!grading_phach_hop_le($soPhach)) throw new GradingException(TB_PHACH_KHONG_HOP_LE);
    $st = ($pdo ?? db())->prepare(
        'SELECT b.maBaiThi, b.soPhach, kt.maKyThi, kt.tenKyThi, m.maMonThi, m.tenMonThi, m.thangDiem,
                k.maKetQuaChamThi, k.diemThi, k.nguoiNhap, k.thoiDiemNhap, k.trangThaiCongBo
         FROM BaiThi b
         JOIN KyThi kt ON kt.maKyThi = b.maKyThi
         JOIN MonThi m ON m.maMonThi = b.maMonThi
         LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi
         WHERE b.soPhach = ?' . ($forUpdate ? ' FOR UPDATE' : '')
    );
    $st->execute([$soPhach]);
    $row = $st->fetch();
    if (!$row) throw new GradingException(TB_KHONG_TIM_THAY_BAI);
    if ($row['maKetQuaChamThi'] === null) throw new GradingException(TB_CHUA_CO_KET_QUA);
    return $row;
}

/**
 * Bước 9: kiểm tra điểm mới (thang điểm, bước nhảy) và lý do.
 * @return array{0: ?float, 1: list<array{0:string,1:?string}>} điểm đã chuẩn hóa, danh sách [câu thông báo, gợi ý]
 */
function kiemTraKetQuaCapNhat(array $ketQua, string $diemMoiRaw, string $lyDo): array {
    $loi = [];
    $diem = null;
    $thang = (float)$ketQua['thangDiem'];
    $goiY = sprintf('Điểm phải từ 0 đến %s, bước nhảy %s điểm.', grading_fmt_gon($thang), grading_fmt_gon(GRADING_BUOC_DIEM));
    $raw = str_replace(',', '.', trim($diemMoiRaw));
    if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $raw)) {
        $loi[] = [TB_DIEM_KHONG_HOP_LE, $goiY];
    } else {
        $diem = round((float)$raw, 2);
        $soBuoc = $diem / GRADING_BUOC_DIEM;
        if ($diem > $thang || abs($soBuoc - round($soBuoc)) > 1e-9) {
            $loi[] = [TB_DIEM_KHONG_HOP_LE, $goiY];
        } elseif (abs($diem - (float)$ketQua['diemThi']) < 0.001) {
            $loi[] = [TB_DIEM_KHONG_HOP_LE, 'Điểm mới trùng với điểm hiện tại.'];
        }
    }
    $lyDo = trim($lyDo);
    if ($lyDo === '') $loi[] = [TB_THIEU_LY_DO, null];
    elseif (mb_strlen($lyDo, 'UTF-8') > 255) $loi[] = [TB_THIEU_LY_DO, 'Lý do chỉnh sửa tối đa 255 ký tự.'];
    return [$loi ? null : $diem, $loi];
}

/** Bước 12: ghi một dòng LichSuChinhSuaDiem, trả về mã lịch sử mới. Gọi bên trong transaction. */
function ghiNhanLichSu(PDO $pdo, string $maKetQuaChamThi, float $diemTruoc, float $diemSau, string $nguoiThucHien, string $lyDo): string {
    $max = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(maLichSu, 3) AS UNSIGNED)), 0) FROM LichSuChinhSuaDiem WHERE maLichSu LIKE 'LS%' FOR UPDATE")->fetchColumn();
    $maLichSu = 'LS' . str_pad((string)($max + 1), 3, '0', STR_PAD_LEFT);
    $pdo->prepare(
        'INSERT INTO LichSuChinhSuaDiem (maLichSu, diemTruocChinhSua, diemSauChinhSua, thoiGianChinhSua, nguoiThucHien, lyDoChinhSua, maKetQuaChamThi)
         VALUES (?, ?, ?, NOW(), ?, ?, ?)'
    )->execute([$maLichSu, $diemTruoc, $diemSau, $nguoiThucHien, $lyDo, $maKetQuaChamThi]);
    return $maLichSu;
}

/**
 * Bước 11–12: cập nhật điểm và ghi lịch sử trong cùng một transaction.
 * Lỗi khi cập nhật điểm → 11.1; lỗi khi ghi lịch sử → 12.1 (điểm được khôi phục nhờ rollback).
 * Lỗi kiểm tra: GradingException có message là câu thông báo đầu tiên, details() là toàn bộ [câu, gợi ý].
 */
function capNhatDiem(string $soPhach, string $diemMoiRaw, string $lyDo, string $nguoiThucHien = GRADING_NGUOI_THUC_HIEN): array {
    $pdo = db();
    $buoc = 'cap_nhat';
    $pdo->beginTransaction();
    try {
        $kq = timBaiThiTheoMaPhach($soPhach, $pdo, true);
        [$diemMoi, $loi] = kiemTraKetQuaCapNhat($kq, $diemMoiRaw, $lyDo);
        if ($loi) throw new GradingException($loi[0][0], $loi);
        $diemCu = (float)$kq['diemThi'];
        $pdo->prepare('UPDATE KetQuaChamThi SET diemThi = ? WHERE maKetQuaChamThi = ?')->execute([$diemMoi, $kq['maKetQuaChamThi']]);
        $buoc = 'ghi_lich_su';
        $maLichSu = ghiNhanLichSu($pdo, $kq['maKetQuaChamThi'], $diemCu, $diemMoi, $nguoiThucHien, trim($lyDo));
        $pdo->commit();
        return ['soPhach' => $kq['soPhach'], 'diemTruoc' => $diemCu, 'diemSau' => $diemMoi, 'maLichSu' => $maLichSu];
    } catch (GradingException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("UC13 {$buoc}: " . $e->getMessage());
        throw new GradingException($buoc === 'ghi_lich_su' ? TB_LOI_GHI_LICH_SU : TB_LOI_CAP_NHAT);
    }
}

// ---------- UC14: Thống kê kết quả chấm thi ----------

/** Bước 4–6: tổng số bài đã có kết quả, điểm trung bình, cao nhất, thấp nhất. */
function thongKeKetQuaChamThi(string $maKyThi, string $maMonThi): array {
    if ($maKyThi === '' || $maMonThi === '') throw new GradingException(TB_CHON_KY_MON);
    $st = db()->prepare(
        'SELECT COUNT(b.maBaiThi) AS tongBai, COUNT(k.maKetQuaChamThi) AS soBaiDaCham,
                AVG(k.diemThi) AS diemTrungBinh, MAX(k.diemThi) AS diemCaoNhat, MIN(k.diemThi) AS diemThapNhat
         FROM BaiThi b LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi
         WHERE b.maKyThi = ? AND b.maMonThi = ?'
    );
    $st->execute([$maKyThi, $maMonThi]);
    $tk = $st->fetch();
    if (!$tk || (int)$tk['soBaiDaCham'] === 0) throw new GradingException(TB_CHUA_CO_DU_LIEU);
    return $tk;
}

// ---------- UC15: Công bố điểm thi ----------

/** Tổng quan từng phạm vi công bố (kỳ thi × môn thi). */
function layTongQuanCongBo(): array {
    $rows = db()->query(
        "SELECT kt.maKyThi, kt.tenKyThi, m.maMonThi, m.tenMonThi,
                COUNT(b.maBaiThi) AS tong,
                SUM(k.maKetQuaChamThi IS NOT NULL AND b.trangThaiCham = 'Đã chấm') AS daCham,
                SUM(k.maKetQuaChamThi IS NULL OR b.trangThaiCham <> 'Đã chấm') AS chuaCham,
                SUM(k.trangThaiCongBo = 'Đã công bố') AS daCongBo,
                SUM(k.maKetQuaChamThi IS NOT NULL AND k.trangThaiCongBo <> 'Đã công bố') AS chuaCongBo
         FROM BaiThi b
         JOIN KyThi kt ON kt.maKyThi = b.maKyThi
         JOIN MonThi m ON m.maMonThi = b.maMonThi
         LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi
         GROUP BY kt.maKyThi, kt.tenKyThi, m.maMonThi, m.tenMonThi
         ORDER BY kt.maKyThi, m.maMonThi"
    )->fetchAll();
    foreach ($rows as &$r) {
        foreach (['tong', 'daCham', 'chuaCham', 'daCongBo', 'chuaCongBo'] as $c) $r[$c] = (int)$r[$c];
        $r['trangThai'] = $r['daCongBo'] > 0 && $r['chuaCongBo'] === 0 ? 'Đã công bố'
            : ($r['daCongBo'] > 0 ? 'Đã công bố một phần' : 'Chưa công bố');
    }
    return $rows;
}

/** Bước 3–4: danh sách kết quả của một kỳ thi + môn thi kèm số liệu trạng thái. */
function layKetQuaTheoPhamVi(string $maKyThi, string $maMonThi, ?PDO $pdo = null, bool $forUpdate = false): array {
    if ($maKyThi === '' || $maMonThi === '') throw new GradingException(TB_CHON_KY_MON);
    $st = ($pdo ?? db())->prepare(
        'SELECT b.maBaiThi, b.soPhach, b.maThiSinh, b.trangThaiCham, k.maKetQuaChamThi, k.diemThi, k.trangThaiCongBo
         FROM BaiThi b LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi
         WHERE b.maKyThi = ? AND b.maMonThi = ?
         ORDER BY b.soPhach' . ($forUpdate ? ' FOR UPDATE' : '')
    );
    $st->execute([$maKyThi, $maMonThi]);
    $rows = $st->fetchAll();
    $tk = ['tong' => count($rows), 'daCham' => 0, 'chuaCham' => 0, 'daCongBo' => 0, 'chuaCongBo' => 0];
    foreach ($rows as $r) {
        $daCham = $r['maKetQuaChamThi'] !== null && $r['trangThaiCham'] === GRADING_DA_CHAM;
        $tk[$daCham ? 'daCham' : 'chuaCham']++;
        if ($r['maKetQuaChamThi'] !== null) $tk[$r['trangThaiCongBo'] === GRADING_DA_CONG_BO ? 'daCongBo' : 'chuaCongBo']++;
    }
    return ['rows' => $rows, 'thongKe' => $tk, 'loiCongBo' => kiemTraCongBo($tk)];
}

/**
 * Bước 6: kiểm tra điều kiện công bố. Trả về null nếu được công bố,
 * ngược lại [câu thông báo (6.1 hoặc 6.2), gợi ý].
 */
function kiemTraCongBo(array $tk): ?array {
    if ($tk['daCham'] > 0 && $tk['chuaCongBo'] === 0) return [TB_DA_CONG_BO, null];
    if ($tk['daCham'] === 0) return [TB_CHUA_DU_DIEU_KIEN, 'Chưa có bài thi nào có kết quả chấm.'];
    if ($tk['chuaCham'] > 0) return [TB_CHUA_DU_DIEU_KIEN, "Còn {$tk['chuaCham']} bài thi chưa chấm."];
    return null;
}

/** Bước 8: chuyển các kết quả của phạm vi sang “Đã công bố”. Trả về số bài được cập nhật. */
function capNhatTrangThaiCongBo(string $maKyThi, string $maMonThi): int {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pv = layKetQuaTheoPhamVi($maKyThi, $maMonThi, $pdo, true);
        if ($pv['loiCongBo'] !== null) throw new GradingException($pv['loiCongBo'][0], array_filter([$pv['loiCongBo'][1]]));
        $st = $pdo->prepare(
            'UPDATE KetQuaChamThi k JOIN BaiThi b ON b.maBaiThi = k.maBaiThi
             SET k.trangThaiCongBo = ?
             WHERE b.maKyThi = ? AND b.maMonThi = ? AND k.trangThaiCongBo <> ?'
        );
        $st->execute([GRADING_DA_CONG_BO, $maKyThi, $maMonThi, GRADING_DA_CONG_BO]);
        $n = $st->rowCount();
        $pdo->commit();
        return $n;
    } catch (GradingException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('UC15 cong bo: ' . $e->getMessage());
        throw new GradingException(TB_LOI_CONG_BO);
    }
}

// =============================================================================
// UC05: NHẬP ĐIỂM THI (Hội đồng chấm thi)
// =============================================================================

/**
 * Tìm danh sách bài thi cần nhập điểm theo kỳ thi và môn thi.
 * Ẩn thông tin nhận diện thí sinh theo nguyên tắc bảo mật phách.
 */
function timBaiThiCanNhapDiem(string $maKyThi, string $maMonThi, ?string $timKiem = null): array {
    $maKyThi = trim($maKyThi);
    $maMonThi = trim($maMonThi);
    $timKiem = trim((string)$timKiem);

    if ($maKyThi === '' || $maMonThi === '') {
        throw new GradingException(TB_CHON_KY_MON);
    }

    $sql = 'SELECT b.maBaiThi, b.soPhach, b.maLoBaiThi, b.trangThaiCham,
                   m.maMonThi, m.tenMonThi, m.thangDiem,
                   k.maKyThi, k.tenKyThi,
                   kq.maKetQuaChamThi, kq.diemThi, kq.trangThaiCongBo
            FROM BaiThi b
            JOIN MonThi m ON m.maMonThi = b.maMonThi
            JOIN KyThi k ON k.maKyThi = b.maKyThi
            LEFT JOIN KetQuaChamThi kq ON kq.maBaiThi = b.maBaiThi
            WHERE b.maKyThi = ? AND b.maMonThi = ?';

    $params = [$maKyThi, $maMonThi];
    if ($timKiem !== '') {
        $sql .= ' AND (b.soPhach = ? OR b.maBaiThi = ?)';
        $params[] = $timKiem;
        $params[] = $timKiem;
    }
    $sql .= ' ORDER BY b.soPhach, b.maBaiThi';

    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/**
 * Lấy thông tin bài thi để phục vụ biểu mẫu nhập điểm.
 */
function layThongTinBaiThiNhapDiem(string $maBaiThi): array {
    $st = db()->prepare(
        'SELECT b.maBaiThi, b.soPhach, b.maLoBaiThi, b.trangThaiCham,
                m.maMonThi, m.tenMonThi, m.thangDiem,
                k.maKyThi, k.tenKyThi,
                kq.maKetQuaChamThi, kq.diemThi, kq.trangThaiCongBo, kq.nguoiNhap, kq.thoiDiemNhap
         FROM BaiThi b
         JOIN MonThi m ON m.maMonThi = b.maMonThi
         JOIN KyThi k ON k.maKyThi = b.maKyThi
         LEFT JOIN KetQuaChamThi kq ON kq.maBaiThi = b.maBaiThi
         WHERE b.maBaiThi = ? OR b.soPhach = ?'
    );
    $st->execute([$maBaiThi, $maBaiThi]);
    $row = $st->fetch();
    if (!$row) {
        throw new GradingException(TB_KHONG_TIM_THAY_BAI, ["Không tìm thấy bài thi với mã/số phách '{$maBaiThi}'."]);
    }
    return $row;
}

/**
 * Nhập điểm thi cho bài thi theo mã phách / mã bài thi (UC05).
 * Kiểm tra hợp lệ: thang điểm 0 - 10, bước nhảy 0.25, bài chưa có kết quả chính thức.
 */
function nhapDiemThi(string $maBaiThi, float $diem, string $nguoiNhap = GRADING_NGUOI_THUC_HIEN): array {
    $maBaiThi = trim($maBaiThi);
    $nguoiNhap = trim($nguoiNhap) ?: GRADING_NGUOI_THUC_HIEN;

    if ($maBaiThi === '') {
        throw new GradingException(TB_KHONG_TIM_THAY_BAI, ['Vui lòng chọn hoặc nhập mã bài thi/mã phách.']);
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare(
            'SELECT b.maBaiThi, b.soPhach, b.trangThaiCham, m.thangDiem, m.tenMonThi,
                    kq.maKetQuaChamThi, kq.trangThaiCongBo, kq.diemThi
             FROM BaiThi b
             JOIN MonThi m ON m.maMonThi = b.maMonThi
             LEFT JOIN KetQuaChamThi kq ON kq.maBaiThi = b.maBaiThi
             WHERE b.maBaiThi = ? OR b.soPhach = ?
             FOR UPDATE'
        );
        $st->execute([$maBaiThi, $maBaiThi]);
        $bt = $st->fetch();

        if (!$bt) {
            throw new GradingException(TB_KHONG_TIM_THAY_BAI);
        }

        $thangDiem = (float)$bt['thangDiem'];
        $thucTeMaBai = (string)$bt['maBaiThi'];

        // Kiểm tra hợp lệ điểm thi: 0 <= điểm <= thangDiem và chia hết cho 0.25
        if (is_nan($diem) || $diem < 0 || $diem > $thangDiem || fmod(round($diem * 100), 25) != 0) {
            throw new GradingException(TB_DIEM_NHAP_KHONG_HOP_LE, [
                "Điểm phải nằm trong thang điểm 0 - {$thangDiem} và tuân thủ bước nhảy 0.25.",
            ]);
        }

        // Kiểm tra nếu bài thi đã có kết quả
        if ($bt['maKetQuaChamThi'] !== null) {
            if ($bt['trangThaiCongBo'] === GRADING_DA_CONG_BO) {
                throw new GradingException(TB_DIEM_DA_XAC_NHAN, [
                    'Điểm bài thi này đã được công bố chính thức, không thể thay đổi bằng chức năng nhập điểm.',
                ]);
            }
            throw new GradingException(TB_DIEM_DA_XAC_NHAN, [
                'Bài thi đã có kết quả chấm thi. Để điều chỉnh điểm, vui lòng sử dụng chức năng Cập nhật kết quả chấm thi (UC13).',
            ]);
        }

        // Sinh mã kết quả chấm thi mới dạng KQCxxx
        $stMax = $pdo->query('SELECT MAX(CAST(SUBSTRING(maKetQuaChamThi, 4) AS UNSIGNED)) FROM KetQuaChamThi WHERE maKetQuaChamThi LIKE "KQC%"');
        $maxNum = (int)$stMax->fetchColumn();
        $maKQ = sprintf('KQC%03d', $maxNum + 1);

        // Lưu KetQuaChamThi
        $stIns = $pdo->prepare(
            'INSERT INTO KetQuaChamThi (maKetQuaChamThi, diemThi, nguoiNhap, thoiDiemNhap, trangThaiCongBo, maBaiThi)
             VALUES (?, ?, ?, NOW(), "Chưa công bố", ?)'
        );
        $stIns->execute([$maKQ, $diem, $nguoiNhap, $thucTeMaBai]);

        // Cập nhật trạng thái bài thi thành 'Đã chấm'
        $stUpd = $pdo->prepare('UPDATE BaiThi SET trangThaiCham = ? WHERE maBaiThi = ?');
        $stUpd->execute([GRADING_DA_CHAM, $thucTeMaBai]);

        $pdo->commit();

        return [
            'maKetQuaChamThi' => $maKQ,
            'maBaiThi' => $thucTeMaBai,
            'soPhach' => (string)$bt['soPhach'],
            'diemThi' => $diem,
            'nguoiNhap' => $nguoiNhap,
            'tenMonThi' => (string)$bt['tenMonThi'],
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('nhapDiemThi: ' . $e->getMessage());
        if ($e instanceof BusinessException) throw $e;
        throw new GradingException(TB_LOI_LUU_DIEM, [$e->getMessage()]);
    }
}
