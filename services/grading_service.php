<?php
declare(strict_types=1);
/**
 * Nghiệp vụ chấm thi và công bố điểm (UC12 – UC15).
 * Bảng dùng: KyThi, MonThi, BaiThi, KetQuaChamThi, LichSuChinhSuaDiem.
 * Tên hàm bám theo thiết kế lớp trong báo cáo Tuần 08.
 *
 * Lỗi nghiệp vụ được ném ra bằng GradingException, nội dung là thông báo hiển thị cho người dùng.
 * Các lỗi khác (mất kết nối CSDL...) để trang tự hiển thị thông báo chung của từng use case.
 */
require_once dirname(__DIR__) . '/config/database.php';

/** Tạm thời khi chưa có đăng nhập thật. */
const GRADING_NGUOI_THUC_HIEN = 'Hội đồng chấm thi';
const GRADING_DA_CONG_BO = 'Đã công bố';
const GRADING_DA_CHAM = 'Đã chấm';

final class GradingException extends RuntimeException
{
    /** @param string[] $details */
    public function __construct(string $message, private array $details = [])
    {
        parent::__construct($message);
    }

    /** @return string[] */
    public function details(): array { return $this->details; }
}

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

// ---------- UC12: Xem lịch sử chỉnh sửa điểm ----------

/** Tìm kết quả chấm thi theo kỳ thi + môn thi + mã phách. */
function timBaiThi(string $maKyThi, string $maMonThi, string $soPhach): array {
    $soPhach = grading_chuan_hoa_phach($soPhach);
    if ($maKyThi === '' || $maMonThi === '' || $soPhach === '') {
        throw new GradingException('Vui lòng chọn đầy đủ điều kiện tra cứu.');
    }
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
    if (!$row) throw new GradingException('Không tìm thấy kết quả chấm thi phù hợp.');
    return $row;
}

/** Các lần chỉnh sửa điểm của một kết quả chấm thi, mới nhất trước. */
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

function grading_sql_theo_phach(bool $forUpdate): string {
    return 'SELECT b.maBaiThi, b.soPhach, kt.maKyThi, kt.tenKyThi, m.maMonThi, m.tenMonThi, m.thangDiem,
                   k.maKetQuaChamThi, k.diemThi, k.nguoiNhap, k.thoiDiemNhap, k.trangThaiCongBo
            FROM BaiThi b
            JOIN KyThi kt ON kt.maKyThi = b.maKyThi
            JOIN MonThi m ON m.maMonThi = b.maMonThi
            LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi
            WHERE b.soPhach = ?' . ($forUpdate ? ' FOR UPDATE' : '');
}

/** Tìm kết quả chấm thi theo mã phách (phải đã có điểm thì mới cập nhật được). */
function timBaiThiTheoMaPhach(string $soPhach, ?PDO $pdo = null, bool $forUpdate = false): array {
    $soPhach = grading_chuan_hoa_phach($soPhach);
    if ($soPhach === '') throw new GradingException('Vui lòng nhập mã phách cần cập nhật.');
    $st = ($pdo ?? db())->prepare(grading_sql_theo_phach($forUpdate));
    $st->execute([$soPhach]);
    $row = $st->fetch();
    if (!$row) throw new GradingException('Không tìm thấy kết quả chấm thi theo mã phách đã nhập.');
    if ($row['maKetQuaChamThi'] === null) throw new GradingException('Bài thi có mã phách này chưa có kết quả chấm thi nên chưa thể cập nhật.');
    return $row;
}

/**
 * Kiểm tra điểm mới và lý do (bước 6 của UC13).
 * @return array{0: ?float, 1: string[]} điểm đã chuẩn hóa và danh sách lỗi
 */
function kiemTraKetQuaCapNhat(array $ketQua, string $diemMoiRaw, string $lyDo): array {
    $loi = [];
    $diem = null;
    $thang = (float)$ketQua['thangDiem'];
    $raw = str_replace(',', '.', trim($diemMoiRaw));
    if ($raw === '') {
        $loi[] = 'Vui lòng nhập điểm mới.';
    } elseif (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $raw)) {
        $loi[] = 'Điểm mới phải là số không âm, tối đa 2 chữ số thập phân.';
    } else {
        $diem = round((float)$raw, 2);
        if ($diem < 0 || $diem > $thang) {
            $loi[] = sprintf('Điểm mới phải nằm trong thang điểm 0 – %s của môn %s.', rtrim(rtrim(grading_fmt_diem($thang), '0'), '.'), $ketQua['tenMonThi']);
        } elseif (abs($diem - (float)$ketQua['diemThi']) < 0.001) {
            $loi[] = 'Điểm mới trùng với điểm hiện tại.';
        }
    }
    $lyDo = trim($lyDo);
    if ($lyDo === '') $loi[] = 'Lý do chỉnh sửa không được để trống.';
    elseif (mb_strlen($lyDo, 'UTF-8') > 255) $loi[] = 'Lý do chỉnh sửa tối đa 255 ký tự.';
    return [$loi ? null : $diem, $loi];
}

/** Ghi một dòng LichSuChinhSuaDiem, trả về mã lịch sử mới. Gọi bên trong transaction. */
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
 * Cập nhật điểm và ghi lịch sử trong cùng một transaction (bước 8–9 của UC13).
 * Nếu lỗi, toàn bộ thay đổi được hoàn tác nên điểm cũ được giữ nguyên.
 */
function capNhatDiem(string $soPhach, string $diemMoiRaw, string $lyDo, string $nguoiThucHien = GRADING_NGUOI_THUC_HIEN): array {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $kq = timBaiThiTheoMaPhach($soPhach, $pdo, true);
        [$diemMoi, $loi] = kiemTraKetQuaCapNhat($kq, $diemMoiRaw, $lyDo);
        if ($loi) throw new GradingException('Kết quả chấm thi không hợp lệ.', $loi);
        $diemCu = (float)$kq['diemThi'];
        $pdo->prepare('UPDATE KetQuaChamThi SET diemThi = ? WHERE maKetQuaChamThi = ?')->execute([$diemMoi, $kq['maKetQuaChamThi']]);
        $maLichSu = ghiNhanLichSu($pdo, $kq['maKetQuaChamThi'], $diemCu, $diemMoi, $nguoiThucHien, trim($lyDo));
        $pdo->commit();
        return ['soPhach' => $kq['soPhach'], 'diemTruoc' => $diemCu, 'diemSau' => $diemMoi, 'maLichSu' => $maLichSu];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

// ---------- UC14: Thống kê kết quả chấm thi ----------

function thongKeKetQuaChamThi(string $maKyThi, string $maMonThi): array {
    if ($maKyThi === '' || $maMonThi === '') throw new GradingException('Vui lòng chọn đầy đủ kỳ thi và môn thi cần thống kê.');
    $st = db()->prepare(
        'SELECT COUNT(b.maBaiThi) AS tongBai, COUNT(k.maKetQuaChamThi) AS soBaiDaCham,
                AVG(k.diemThi) AS diemTrungBinh, MAX(k.diemThi) AS diemCaoNhat, MIN(k.diemThi) AS diemThapNhat
         FROM BaiThi b LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi
         WHERE b.maKyThi = ? AND b.maMonThi = ?'
    );
    $st->execute([$maKyThi, $maMonThi]);
    $tk = $st->fetch();
    if (!$tk || (int)$tk['soBaiDaCham'] === 0) {
        throw new GradingException('Chưa có dữ liệu kết quả chấm thi của kỳ thi hoặc môn thi được chọn.');
    }
    return $tk;
}

// ---------- UC15: Công bố điểm thi ----------

/** Tổng quan từng phạm vi công bố (kỳ thi × môn thi) cho bước 2 của UC15. */
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

/** Danh sách kết quả của một kỳ thi + môn thi kèm số liệu trạng thái. */
function layKetQuaTheoPhamVi(string $maKyThi, string $maMonThi, ?PDO $pdo = null, bool $forUpdate = false): array {
    if ($maKyThi === '' || $maMonThi === '') throw new GradingException('Vui lòng chọn đầy đủ kỳ thi và môn thi cần công bố.');
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

/** Trả về lý do không thể công bố, hoặc null nếu được công bố. */
function kiemTraCongBo(array $tk): ?string {
    if ($tk['daCham'] === 0) return 'Chưa có dữ liệu kết quả chấm thi của kỳ thi hoặc môn thi được chọn.';
    if ($tk['chuaCongBo'] === 0) return 'Kết quả thi đã được công bố.';
    if ($tk['chuaCham'] > 0) return sprintf('Kết quả chấm thi chưa được cập nhật đầy đủ (còn %d bài chưa chấm), không thể công bố.', $tk['chuaCham']);
    return null;
}

/** Chuyển các kết quả của phạm vi sang “Đã công bố”. Trả về số bài được cập nhật. */
function capNhatTrangThaiCongBo(string $maKyThi, string $maMonThi): int {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pv = layKetQuaTheoPhamVi($maKyThi, $maMonThi, $pdo, true);
        if ($pv['loiCongBo'] !== null) throw new GradingException($pv['loiCongBo']);
        $st = $pdo->prepare(
            'UPDATE KetQuaChamThi k JOIN BaiThi b ON b.maBaiThi = k.maBaiThi
             SET k.trangThaiCongBo = ?
             WHERE b.maKyThi = ? AND b.maMonThi = ? AND k.trangThaiCongBo <> ?'
        );
        $st->execute([GRADING_DA_CONG_BO, $maKyThi, $maMonThi, GRADING_DA_CONG_BO]);
        $n = $st->rowCount();
        $pdo->commit();
        return $n;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
